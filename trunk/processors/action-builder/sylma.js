/* Document JS */

var SYLMA_MODE_EXECUTION = 1, SYLMA_MODE_WRITE = 2, SYLMA_MODE_READ = 4;

window.addEvent('domready', function() {
  
  var oResult = new Request.JSON({
    
    'url' : '/users/root/explorer.txt', 
    'onSuccess' : function(oResult) {
      
      //sylma.dsp(' - DEBUT - ');
      sylma.loadTree(oResult);
      //sylma.dsp(' - FIN - ');
  }}).get();
});

var sylma = {
  
  classes : {},
  
  loadTree : function(oTree) {
    
    //var aKeys = this.extend(this, oTree, true);
    this.explorer = this.buildObject(oTree.explorer);
  },
  
  buildArray: function(object, sClassBase, parentLayer, iDepth) {
    
    
  },
  
  buildObject: function(object, sClassBase, parentLayer, iDepth) {
    
    var sKey, sName, oSub, bRoot, eNode;
    var oResult = {};
    var bResult = true;
    
    if (object['init']) {
      
      var sClass = null;
      
      if (!sClassBase) sClassBase = '';
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
          
          try { eval('oResult = new window' + sName); }
          catch(e) { this.dsp('Nom de classe introuvable : ' + sName); bResult = false; };
          
        } else {
          
          try { eval('oResult = new window' + sClassBase + sName); }
          catch(e) { this.dsp("Classe '" + sName + "' introuvable dans la classe de base '" + sClassBase + "'"); bResult = false; };
        }
      }
      
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
            
            if (oSub['is-sylma-object']) oResult[sKey] = this.buildObject(oSub, sClassBase, oResult, iDepth + 1); // Sylma object
            else if (oSub['is-sylma-array']) { // Sylma array
              
              oResult[sKey] = new Array();
              for (var sSubKey in oSub) oResult[sKey][sSubKey] = this.buildObject(oSub[sSubKey], sClassBase, oResult, iDepth + 1);
              
            } else {this.dsp('Type d\'object inconnu : ' + sKey); this.dsp(this.view(object['properties'])); }// Sylma others
            
          } else if (sType == 'string') oResult[sKey] = oSub;// JS String
          else this.dsp('Type \'' + sType + '\' inconnu !'); // JS Others
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
                
                eNode.addEvent(method.name, function() {
                  
                  oParent.timer = sylma.methods[sMethod].delay(parseInt(method.delay), eNode);
                  // sylma.dsp('[run-hide] ' + oParent.node.id);
                });
                
              } else eNode.addEvent(method.name, sylma.methods[sMethod]); // add event
              
            } else {
              
              this.dsp('Erreur :: Objet DOM introuvable - path : ' + method['path-node'] + ' - id : ' + method['id-node']);
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
  
  dsps : function(mContent, sTargetId) {
    
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
    this.dsps(new Element('div', {'html' : sContent, 'style' : sStyle}));
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
    
    if (oMessages.children.length) {
      
      var oContainer = $('explorer-messages');
      
      if (!oContainer) {
        
        oContainer = new Element('div', {'id' : 'explorer-messages'});
        sylma.explorer.node.grab(oContainer, 'top');
      }
      
      var oContent = oMessages.getFirst();
      
      oContent.setStyles({'opacity' : 0, 'height' : 0});
      oContainer.adopt(oContent, 'top')
      
      var oFx = new Fx.Morph(oContent);
      
      oFx.start({
        'opacity' : 1,
        'height' : '100%'});
      
      (function() {
        oFx.start({
          'opacity' : 0,
          'height' : 0});
      }).delay(5000);
      
    }
    //if ();
  }
});

sylma.classes.layer = new Class({
  
  runUpdate : function(sPath, oArguments) {
    
    this.request = new sylma.classes.request({
      
      'url' : sPath + '.action',
      'data' : oArguments,
      'onSuccess' : function(sResult, oResult) {
        
        this.parseAction(oResult);
      }
    });
    
    this.request.send();
  }
}),
  
sylma.classes.layout = new Class({
  
}),

sylma.classes.menu = new Class({
  
  Extends : sylma.classes.layer,
  isOpen : false,
  timer : undefined,
  
  'isVisible' : function() { return (this.node.getStyle('visibility') == 'visible'); },
  
  'clearTimer' : function() {
    
    if (this.timer) {
      
      $clear(this.timer);
      this.timer = undefined;
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
      
      var oTween = this.node.get('tween');
      
      if (oTween) oTween.cancel();
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
  
  'show' : function(eTarget) {
    
    if (this.firstShow(eTarget)) {
      
      this.hide(true);
      $(eTarget).grab(this.node, 'top');
      
      this.parentNode = eTarget;
    }
    
    return this.parent();
  },
  
  'firstShow' : function(eTarget) {
    
    return (this.parentNode !== eTarget);
  }
});


