/* Document JS */

var SYLMA_MODE_EXECUTION = 1, SYLMA_MODE_WRITE = 2, SYLMA_MODE_READ = 4;
var SYLMA_HIDE_MESSAGES = true;
var SYLMA_USE_CONSOLE = false;

var sylma = {
  
  classes : {},
  
  defaultMessagesId : false,
  defaultMessagesContainer : false,
  
  inttobool : function(sValue) {
    
    return parseInt(sValue) === 1 ? true : false;
  },
  
  importNodes : function(mElements) {
    
    if ($type(mElements) == 'array') {
      
      var aResults = new Array;
      
      mElements.each(function(oElement) {
        
        aResults.push(document.importNode(oElement, true));
      });
      
      return aResults;
      
    } else return document.importNode(mElements, true);
  },
  
  loadTree : function(sName, sPath, oSuccess) {
    
    var oResult = new Request.JSON({
      
      'url' : sPath, 
      'onSuccess' : function(oResult) {
        
        //sylma.dsp(' - DEBUT - ');
        sylma[sName] = sylma.buildRoot(oResult);
        if (oSuccess) oSuccess();
        //sylma.dsp(' - FIN - ');
    }}).get();
  },
  
  buildRoot: function(object, sPath, oParent, oRoot) {
    
    if (!object) this.dsp('Aucun objet reçu pour "' + sPath + '"');
    else {
      for (var i in object) { var bluh; } // TODO ??
      
      //if (!oRoot) oRoot = oParent;
      if (!sPath) sPath = i;
      
      return this.buildObject(object[i], sPath, oParent, oRoot);
    }
    
    return null;
  },
  
  buildObject: function(object, sPath, parentLayer, rootObject, iDepth) {
    
    var sKey, sName, oSub, bRoot, eNode;
    var oResult = {};
    var bResult = true;
    
    if (object['init']) {
      
      var sClass = null;
      
      if (parentLayer) sClassBase = parentLayer['sylma-classbase'];
      else sClassBase = '';
      
      if (!parentLayer) parentLayer = oResult;
      
      if (!iDepth) iDepth = 0;
      else if (iDepth > 10) {
        
        this.dsp('Trop de récursion !');
        return;
      }
      
      if (object['init']['extend-base']) {
        
        var sClassBase = object['init']['extend-base'];
        if (!sClassBase) this.dsp('Classe de base vide !');
      }
      
      if (object['init']['extend-class']) {
        
        var sClassName = object['init']['extend-class'];
        bRoot = sClassName[0] == '/';
        
        var sArgs = "({'object' : object, 'parent' : parentLayer, 'base' : sClassBase, 'path' : sPath, 'root' : rootObject})"
        
        if (bRoot || !sClassBase) {
          
          if (bRoot) sClassName = sClassName.substr(1);
          sClassName = sClassName[0] == '[' ? sClassName : '.' + sClassName;
          
          try { eval('oResult = new window' + sClassName + sArgs); }
          catch(e) { this.dsp('Nom de classe introuvable : window' + sClassName + '<br/>' + e); bResult = false; };
          
        } else {
          
          try { eval('oResult = new window' + sClassBase + sClassName + sArgs); }
          catch(e) {
            
            this.dsp("Classe '" + sClassName + "' introuvable ou invalide dans la classe de base window'" + sClassBase + "'<br/>" + e); 
            bResult = false;
          };
        }
      }
      
      if (!bResult) this.dsp('Erreur :: Impossible de créer l\'objet');
      else if (!rootObject) rootObject = oResult;
    }
    
    if (bResult) {
    
      if (object['properties'] && object['properties'].length != 0) {
        
        var sType;
        
        for (sKey in object['properties']) {
          
          oSub = object['properties'][sKey];
          sType = $type(oSub);
          
          if (sType == 'object') { // JS Object
            
            if (oSub['is-sylma-object']) oResult[sKey] = this.buildObject(oSub, sKey, oResult, rootObject, iDepth + 1); // Sylma object
            else if (oSub['is-sylma-array']) { // Sylma array
              
              delete(oSub['is-sylma-array']);
              oResult[sKey] = new Array();
              
              for (var sSubKey in oSub) {
                
                sPath = sKey + '[\'' + sSubKey + '\']';
                oResult[sKey][sSubKey] = this.buildObject(oSub[sSubKey], sPath, oResult, rootObject, iDepth + 1);
              }
              
            } else if (oSub['is-sylma-property']) { // Sylma others
              
              delete(oSub['is-sylma-property']);
              oResult[sKey] = new Object;
              
              for (var sSubKey in oSub) {
                
                sPath = sKey + '.' + sSubKey;
                oResult[sKey][sSubKey] = this.buildObject(oSub[sSubKey], sPath, oResult, rootObject, iDepth + 1);
              }
              
            } else { // Sylma others
              
              this.dsp('Type d\'object inconnu : ' + sKey);
              this.dsp(this.view(object['properties']));
            }
            
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
      
      if (!sylma.methods) sylma.dsp('Liste des méthodes introuvable');
      else {
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
                
                if (method.limit) {
                  
                  eNode.addEvent(method.name, this.limitFunc.create({
                    
                    arguments : [sMethod, method.limit],
                    event : true,
                    bind : eNode
                  }));
                  
                } else if (method.delay) {
                  
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
    }
  },
  
  delayFunc : function(e, sMethod, sTimer, iDelay, oParent) {
    
    if (!oParent.timer) oParent.timer = [];
    
    oParent.timer[sTimer] = sylma.methods[sMethod].delay(iDelay, this, e);
  },
  
  limitFunc : function(e, sMethod, sTargets) {
    
    var oTarget;
    var bResult = false;
    var aTargets = sTargets.split(',');
    
    for (var i = 0; i < aTargets.length; i++) {
      //alert(sTarget);
      switch (aTargets[i].replace(/^\s+/g,'').replace(/\s+$/g,'')) {
        
        case 'self' : oTarget = this; break;
        case 'first' : oTarget = this.getFirst(); break;
      }
      
      if (e.target === oTarget) {
        
        bResult = true;
        break;
      }
    }
    
    if (bResult) {
      
      var oBounded = sylma.methods[sMethod].bind(this, e);
      oBounded();
    }
    
    return true;
  },
  
  load : function(hOptions) {
    
    if (!hOptions.has('method')) hOptions.set('method', 'post');
    
    var sPath = hOptions.get('path');
    var self = this;
    
    var sWindow = hOptions.has('window') ? hOptions.get('window') : 'action';
    
    this.request = new this.classes.request({
      
      'url' : sPath + '.' + sWindow,
      'data' : hOptions.get('arguments'),
      'method' : hOptions.get('method'),
      'async ' : false,
      'onSuccess' : function(sResult, oHTML) {
        
        var oContentContainer = this.parseAction(oHTML);
        var oContent = self.importNodes(oContentContainer.getFirst());
        
        oContent.setStyle('opacity', 0.2);
        
        if (hOptions.get('replace')) oContent.replaces(hOptions.get('html'));
        else hOptions.get('html').grab(oContent);
        
        if (hOptions.get('position') == 'center') {
          
          var iLeft = (window.getSize().x - oContent.getSize().x) / 2;
          var iTop = ($(window).getSize().y - oContent.getSize().y) / 2;
          
          oContent.setStyles({'left' : iLeft, 'top' : iTop});
        }
        
        // TODO kill old layer
        //layer.node.destroy(); 
        
        if (oContentContainer.getProperty('recall') == 'true') {
          
          // get new object
          
          if (oContentContainer.getProperty('methods') == 'true') {
            
            // has methods, first load em
            
            var oMethods = new Request.JSON({
              
              'url' : sPath + '.txt',
              //'evalResponse' : true,
              'onSuccess' : function(oResponse, sResponse) {
                //alert(sResponse);
                eval(sResponse);
                self.replace(sPath, hOptions);
                if (hOptions.has('callback')) hOptions.get('callback')();
                
                oContent.setStyle('opacity', 1);
                
            }}).get();
            
          } else {
            
            // no methods
            
            self.replace(sPath, hOptions);
            oContent.setStyle('opacity', 1);
          }
          
        } else {
          
          // only change content node
          oContent.setStyle('opacity', 1);
          oCaller.node = oContent;
        }
      }
    }).send();
    
    return true;
  },
  
  replace : function(sPath, hOptions) {
    
    var self = this;
    
    var oJSON = new Request.JSON({
      
      'url' : sPath + '.txt', 
      'onSuccess' : function(oResponse) {
        
        if (!hOptions.get('parent')) hOptions.set('parent', sylma);
        
        var oNewObject = self.buildRoot(oResponse, hOptions.get('name'), hOptions.get('parent'), hOptions.get('root'));
        
        if (hOptions.has('position')) oNewObject['sylma-position'] = hOptions.get('position');
        
        if (hOptions.has('old-name')) eval('delete(hOptions.get(\'parent\').' + hOptions.get('old-name') + ')'); // delete old object
        if (oNewObject) eval('hOptions.get(\'parent\').' + hOptions.get('name') + ' = oNewObject'); // insert new object
        
    }}).get();
  },
  
  dsp_message : function(mContent, sTargetId) {
    
    if (!sTargetId) sTargetId = 'sylma-messages-default';
    
    var eMessages = $(sTargetId);
    
    if (!($type(eMessages) == 'element')) {
      
      eMessages = new Element('div', {'id' : sTargetId, 'class' : 'sylma-messages'});
      
      var oContent = $('content');
      
      if (!oContent) alert('Contenu introuvable !');
      else $('content').grab(eMessages, 'bottom');
    }
    
    eMessages.grab(mContent, 'top');
  },
  
  dsp : function(sContent, sTargetId) {
    
    if (SYLMA_USE_CONSOLE) console.log(sContent);
    else {
      var sStyle = 'border-bottom: 1px solid gray; margin-bottom: 0.5em;';
      this.dsp_message(new Element('div', {'html' : sContent, 'style' : sStyle}));
    }
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

sylma.classes.Base = new Class({
  
  initialize : function(oArgs) {
    
    this['sylma-classbase'] = oArgs['base'];
    
    // Add default properties
    
    if (oArgs['object']['init']['id-node']) { // Attach reference node
      
      var eNode = $(oArgs['object']['init']['id-node']);
      
      if (eNode) {
        
        this.node =  eNode;
        eNode.store('ref-object', this);
        
      } else sylma.dsp("Erreur : Element '" + eNode + "' lié à l'objet introuvable !");
    }
    
    this.parentObject = oArgs['parent']; // Attach parent object
    this['sylma-path'] = oArgs['path']; // Attach parent object ref
    this.rootObject = oArgs['root']; // Attach root object (layout)
  }
  
});

sylma.classes.request = new Class({
  
  Extends : Request,
  
  'parseAction' : function(oResult, sMessages, oTarget) {
    
    var oContainer = $('msg-admin');
    
    var oMessages = $(oResult).getElement('messages');
    var oContent = $(oResult).getElement('content');
    var oInfos = $(oResult).getElement('infos');
    
    oContainer.adopt(sylma.importNodes(oInfos.getFirst()), 'top');
    
    if (!sMessages) sMessages = sylma.defaultMessagesId;
    if (!oTarget) oTarget = sylma.defaultMessagesContainer;
    
    if (oMessages && oMessages.getChildren().length) {
      
      var oMessagesContent = oMessages.getFirst();
      
      if (oMessagesContent) {
        
        oMessagesContent = sylma.importNodes(oMessagesContent);
        
        //oMessagesContent.fade('hide');
        
        oMessagesContent.addClass('messages-float');
        oContainer.adopt(oMessagesContent, 'top');
        
        var pf = new PulseFade(oMessagesContent, {'times':  6, 'duration':  600, 'max' : 1, 'min' : 0.2}); //
        pf.start();
        
        //oMessagesContent.fade('in').;
      }
    }
    
    return oContent;
  }
});

sylma.classes.layer = new Class({
  
  Extends : sylma.classes.Base,
  isOpen : true,
  timer : undefined,
  
  getPath : function() {
    
    var sPath = this['sylma-update-path'];
    
    if (this['sylma-update-origin']) {
      
      switch (this['sylma-update-origin']) {
        
        case 'interface' : sPath = this.rootObject.pathInterface + sPath; break;
        case 'action' : sPath = this.rootObject.path + sPath; break;
      }
    }
    
    return sPath;
  },
  
  replace : function(hOptions) {
    
    this.node.setStyle('opacity', 0.2);
    
    hOptions.extend({
      
      'html' : this.node,
      'old-name' : this['sylma-path'], // optional
      'parent' : this.parentObject, // optional
      'root' : this.rootObject, // optional
      'replace' : true
    });
    
    if (this['sylma-send-method']) hOptions.set('method', this['sylma-send-method']);
    if (this['sylma-position']) hOptions.set('position', this['sylma-position']);
    
    return sylma.load(hOptions);
  },
  
  update : function(oArguments, oCall) {
    
    var hOptions = new Hash({
      'path' : this.getPath(),
      'arguments' : oArguments,
    });
    
    if (oCall) hOptions.set('callback', oCall);
    
    return this.replace(hOptions);
  },
  
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
       //sylma.dsp('[hide] ' + this.node.id);
    }
    
    this.isOpen = false;
    
    return true;
  },
  
}),
  
sylma.classes.menu = new Class({
  
  Extends : sylma.classes.layer,
  
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

var PulseFade = new Class({
			
	//implements
	Implements: [Options,Events],

	//options
	options: {
		min: 0,
		max: 1,
		duration: 200,
		times: 5
	},
	
	//initialization
	initialize: function(el,options) {
		//set options
		this.setOptions(options);
		this.element = $(el);
		this.times = 0;
	},
	
	//starts the pulse fade
	start: function(times) {
		if(!times) times = this.options.times * 2;
		this.running = 1;
		this.fireEvent('start').run(times -1);
	},
	
	//stops the pulse fade
	stop: function() {
		this.running = 0;
		this.fireEvent('stop');
	},
	
	//runs the shizzle
	run: function(times) {
		//make it happen
		var self = this;
		var to = self.element.get('opacity') == self.options.min ? self.options.max : self.options.min;
		self.fx = new Fx.Tween(self.element,{
			duration: self.options.duration / 2,
			onComplete: function() {
				self.fireEvent('tick');
				if(self.running && times)
				{
					self.run(times-1);
				}
				else
				{
					self.fireEvent('complete');
				}
			}
		}).start('opacity',to);
	}
});
