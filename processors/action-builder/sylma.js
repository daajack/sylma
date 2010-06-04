/* Document JS */

var SYLMA_MODE_EXECUTION = 1, SYLMA_MODE_WRITE = 2, SYLMA_MODE_READ = 4;


var sylma = {
  
  classes : {},
  
  inttobool : function(sValue) {
    
    return parseInt(sValue) === 1 ? true : false;
  },
  
  importNode : function(oElement) {
    
    return document.importNode(oElement, true);
  },
  
  loadTree : function(sName, sPath) {
    
    var oResult = new Request.JSON({
      
      'url' : sPath, 
      'onSuccess' : function(oResult) {
        
        //sylma.dsp(' - DEBUT - ');
        sylma[sName] = sylma.buildRoot(oResult);
        //sylma.dsp(' - FIN - ');
    }}).get();
  },
  
  buildArray: function(object, sClassBase, parentLayer, iDepth) {
    
    
  },
  
  buildRoot: function(object, oParent) {
    
    for (var i in object) { var bluh; }
    
    return sylma.buildObject(object[i], oParent);
  },
  
  buildObject: function(object, parentLayer, iDepth) {
    
    var sKey, sName, oSub, bRoot, eNode;
    var oResult = {};
    var bResult = true;
    
    if (object['init']) {
      
      var sClass = null;
      
      if (parentLayer) sClassBase = parentLayer._classBase;
      else sClassBase = '';
      
      if (!parentLayer) parentLayer = oResult;
      
      if (!iDepth) iDepth = 0;
      else if (iDepth > 10) {
        
        this.dsp('Trop de récursion !');
        return;
      }
      
      if (object['init']['extend-base']) {
        
        sClassBase = object['init']['extend-base'];
        if (!sClassBase) this.dsp('Classe de base vide !');
      }
      
      if (object['init']['extend-class']) {
        
        sName = object['init']['extend-class'];
        bRoot = sName[0] == '/';
        
        if (bRoot || !sClassBase) {
          
          if (bRoot) sName = sName.substr(1);
          sName = sName[0] == '[' ? sName : '.' + sName;
          
          try { eval('oResult = new window' + sName); }
          catch(e) { this.dsp('Nom de classe introuvable : window' + sName); bResult = false; };
          
        } else {
          
          try { eval('oResult = new window' + sClassBase + sName); }
          catch(e) { this.dsp("Classe '" + sName + "' introuvable dans la classe de base window'" + sClassBase + "'"); bResult = false; };
        }
      }
      
      oResult._classBase = sClassBase;
      
      if (bResult) {
        
        // Add default properties
        
        if (object['init']['id-node']) { // Attach reference node
          
          eNode = $(object['init']['id-node']);
          
          if (eNode) {
            
            oResult.node =  eNode;
            eNode.store('ref-object', oResult);
            
          } else this.dsp("Erreur : Element '" + eNode + "' lié à l'objet introuvable !");
        }
        
        oResult.parentObject = parentLayer; // Attach parent object
        
      } else this.dsp('Erreur :: Impossible de créer l\'objet');
    }
    
    if (bResult) {
    
      if (object['properties'] && object['properties'].length != 0) {
        
        var sType;
        
        for (sKey in object['properties']) {
          
          oSub = object['properties'][sKey];
          sType = $type(oSub);
          
          if (sType == 'object') { // JS Object
            
            if (oSub['is-sylma-object']) oResult[sKey] = this.buildObject(oSub, oResult, iDepth + 1); // Sylma object
            else if (oSub['is-sylma-array']) { // Sylma array
              
              oResult[sKey] = new Array();
              for (var sSubKey in oSub) oResult[sKey][sSubKey] = this.buildObject(oSub[sSubKey], oResult, iDepth + 1);
              
            } else {this.dsp('Type d\'object inconnu : ' + sKey); this.dsp(this.view(object['properties'])); }// Sylma others
            
          } else if (sType == 'string') oResult[sKey] = oSub;// JS String
          else this.dsp('Type \'' + sType + '\' inconnu dans ' + sKey + ' !'); // JS Others
        }
      }
      
      if (object['methods']) this.buildMethods(object, oResult);
      
      return oResult;
      
    }
    
    return false;
  },
  
  buildMethods: function(object, oParent) {
    
    var method, eNode;
    
    for (var sMethod in object.methods) {
      
      method = object.methods[sMethod];
      
      if (sylma.methods[sMethod]) {
        
        if (method.event) {
          
          //event
          
          if (method.name && (method['path-node'] || method['id-node'])) {
            
            // get target node
            
            if (method['path-node']) {
              
              eNode = $$(method['path-node']);
              if (eNode.length) eNode = eNode[0];
              
            } else eNode = $(method['id-node']);
            
            if ($type(eNode) == 'element') {
              
              eNode.store('ref-object', oParent); // store parent object in node
              
              if (method.delay) {
                
                eNode.addEvent(method.name, this.delayFunc.create({
                  
                  arguments : [sMethod, method.timer, parseInt(method.delay), oParent],
                  event : true,
                  bind : eNode
                }));
                
              } else eNode.addEvent(method.name, sylma.methods[sMethod]); // add event
              
            } else {
              
              //sylma.dsp_f(eNode);
              this.dsp('Erreur :: Objet DOM introuvable - path : "' + method['path-node'] + '" - id : ' + method['id-node']);
            }
            
          } else {
            
            this.dsp("Erreur :: Méthode '" + sMethod + "' invalide !");
            this.dsp(this.view(method));
          }
          
        } else {
          
          // method
          
          oParent[method.name] = sylma.methods[sMethod];
        }
        
      } else {
        
        this.dsp("Erreur :: Méthode '" + sMethod + "' introuvable !");
        this.dsp(this.view(method));
      }
    }
  },
  
  delayFunc : function(e, sMethod, sTimer, iDelay, oParent) {
    
    if (!oParent.timer) oParent.timer = [];
    
    oParent.timer[sTimer] = sylma.methods[sMethod].delay(iDelay, this, e);
  },
  
  dsp_message : function(mContent, sTargetId) {
    
    if (!sTargetId) sTargetId = 'sylma-messages-default';
    
    var eMessages = $(sTargetId);
    
    if (!($type(eMessages) == 'element')) {
      
      eMessages = new Element('div', {'id' : sTargetId, 'class' : 'sylma-messages'});
      $('content').grab(eMessages, 'bottom');
    }
    
    eMessages.grab(mContent, 'top');
  },
  
  dsp : function(sContent, sTargetId) {
    
    var sStyle = 'border-bottom: 1px solid gray; margin-bottom: 0.5em;';
    this.dsp_message(new Element('div', {'html' : sContent, 'style' : sStyle}));
  },
  
  dsp_f : function(obj) {
    
    this.dsp(this.view(obj));
  },
  
  view : function(obj, parent, recursion) {
    
    if (!recursion) recursion = 0;
    
    var sContent = '';
    // var iMaxRecursion = 10;
    
    for (var i in obj) {
      
      try {
        
        sContent += '<div style="margin-left: ' + (6 - recursion) + 'em;">';
        
        // if (parent) sContent = parent + "." + i + " : " + obj[i];
        sContent += '<strong>' + i + '</strong>' + " : " + obj[i];
        
        if (typeof obj[i] == "object" && recursion) {
          
          sContent += '<div style="margin-left: ' + (6 - recursion + 1) + 'em">';
          
          // if (parent) sContent += this.view(obj[i], parent + "." + i, recursion - 1);
          if (recursion) sContent += this.view(obj[i], i, recursion - 1);
          
          sContent += '</div>';
        }
        
        sContent += '</div>';
        
      } catch (t) { sContent += '<br/>Erreur :: Propriété : ' + i + ' (' + t + ')<br/>'; }
    }
    
    return sContent;
    //this.dsp(sContent);
  }
};


sylma.classes.request = new Class({
  
  Extends : Request,
  
  'parseAction' : function(oResult) {
    
    var oMessages = $(oResult).getElement('messages');
    var oContent = $(oResult).getElement('content');
    
    if (oMessages && oMessages.getChildren().length) {
      
      var oContainer = $('explorer-messages');
      
      if (!oContainer) {
        
        oContainer = new Element('div', {'id' : 'explorer-messages'});
        sylma.explorer.node.grab(oContainer, 'top');
      }
      
      var oMessagesContent = oMessages.getFirst();
      
      if (oMessagesContent) {
        
        oMessagesContent.setStyles({'opacity' : 0, 'height' : 0});
        
        oMessagesContent = sylma.importNode(oMessagesContent);
        oContainer.adopt(oMessagesContent, 'top');
      }
      
      var oFx = new Fx.Morph(oMessagesContent, {'unit' : '%'});
      
      oFx.start({
        'opacity' : 1,
        'height' : 100});
        
      (function() {
        /*
        oMessagesContent.addEvent('mousemove', function() {
          
          this.get('tween').cancel();
          //this.fade('out').delay(5000);
        });
        
        oMessagesContent.addEvent('mouseleave', function() {
          
          this.fade('out').delay(5000);
        });
        */
        oFx.options.unit = 'px';
        oFx.start({
          'opacity' : 0,
          'height' : 0});
      }).delay(5000);
    }
    
    return oContent;
  }
});

sylma.classes.layer = new Class({
  
  update : function(oArguments) {
    
    var layer = this;
    this.node.setStyle('opacity', 0.2);
    this.request = new sylma.classes.request({
      
      'url' : layer.path + '.action',
      'data' : oArguments,
      'onSuccess' : function(sResult, oResult) {
        
        var mContent = sylma.importNode(this.parseAction(oResult).getFirst());
        
        mContent.setStyle('opacity', 0.2);
        mContent.replaces(layer.node);
        
        // TODO kill old layer
        //layer.node.destroy(); 
        
        var oSubResult = new Request.JSON({
          
          'url' : layer.path + '.txt', 
          'onSuccess' : function(oResponse) {
            
            sylma.explorer.mozaic = sylma.buildRoot(oResponse, sylma.explorer);
            //sylma.explorer.mozaic.parentObject = sylma.explorer;
            mContent.setStyle('opacity', 1);
            
        }}).get();
      }
    }).get();
  }
  
}),
  
sylma.classes.layout = new Class({
  
  
}),

sylma.classes.menu = new Class({
  
  Extends : sylma.classes.layer,
  isOpen : false,
  timer : undefined,
  
  'isVisible' : function() { return (this.node.getStyle('visibility') == 'visible'); },
  
  'clearTimer' : function(sName) {
    
    if (this.timer) {
      
      $clear(this.timer[sName]);
      this.timer[sName] = undefined;
    }
  },
  
  'show' : function() {
    
    if (!this.isOpen) {
      
      // sylma.dsp('[show] ' + this.node.id);
      this.node.fade('in');
      this.isOpen = true;
    }
    
    return true;
  },
  
  'hide' : function(bQuick) {
    
    if (bQuick) {
      
      this.node.get('tween').cancel();
      this.node.fade('hide');
      
    } else if (this.isOpen) {
      
      this.node.fade('out');
      // sylma.dsp('[hide] ' + this.node.id);
    }
    
    this.isOpen = false;
    
    return true;
  },
  
  valueOf : function() {
    
    return '[obj] ' + this.node + ' #' + this.node.id;
  }
});

sylma.classes['menu-common'] = new Class({
  
  Extends : sylma.classes.menu,
  parentNode : undefined,
  originNode : undefined,
  
  'show' : function(eTarget) {
    
    if (this.firstShow(eTarget)) {
      
      if (!this.originNode) this.originNode = this.node.getParent();
      
      this.hide(true);
      
      $(eTarget).grab(this.node, 'top');
      
      this.parentNode = eTarget;
    }
    
    return this.parent();
  },
  
  'resetParent' : function() {
    
    this.parentNode = undefined;
  },
  
  'firstShow' : function(eTarget) {
    
    return (this.parentNode !== eTarget);
  },
  
  'reset' : function() {
    
    this.hide(true);
    
    this.resetParent();
    if (this.originNode) this.originNode.grab(this.node);
    if (!this.node.getChildren().length) sylma.dsp('tools perdus [a] !');
  }
});


