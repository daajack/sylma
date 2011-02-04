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
  
  booltostring : function(bValue) {
    
    return bValue ? 'true' : 'false';
  },
  
  importNodes : function(mElements) {
    
    if ($type(mElements) == 'array') {
      
      var aResults = new Array;
      
      mElements.each(function(oElement) {
        
        aResults.push(document.importNode(oElement, true));
      });
      
      return aResults;
      
    } else {
      
      if (document.importNode) return window.document.importNode(mElements, true);
      else return mElements;
    }
  },
  
  loadTree : function(sName, sID, oSuccess) {
    
    var oResult = new Request.JSON({
      
      'url' : '/index.txt?sylma-result-id=' + sID, 
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
    
    var sKey, sName, oSub, bRoot, eNode, isRoot;
    var oResult = {};
    var bResult = true;
    
    if (!rootObject) isRoot = true;
    else isRoot = false;
    
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
        
        var bHidden = object['init']['hidden'] ? true : false;
        
        var sArgs = "({'object' : object, 'parent' : parentLayer, 'base' : sClassBase, 'path' : sPath, 'root' : rootObject, 'hidden' : bHidden})"
        
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
      
      // if (isRoot && oResult.node) oResult.node.setStyle('opacity', 0.25);
      // if (isRoot) alert(oResult.node);
      if (sClassBase) oResult['sylma-classbase'] = sClassBase;
      
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
      //sylma.dsp(sPath);
      if (object['methods']) this.buildMethods(object, oResult);
      
      if (isRoot  && oResult.node) oResult.node.removeClass('sylma-loading');
      
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
              //sPath = method['path-node'] ? method['path-node'] : '#' + method['id-node']
              if ($type(eNode) == 'element') {
                //sylma.dsp(sPath + ' (' + oParent['sylma-path'] + ') / ' + sMethod);
                eNode.store('ref-object', oParent); // store parent object in node
                //sylma.dsp(method['path-node'] + ' :: ' + method['id-node']);
                
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
                  
                } else if (method.name == 'keydown' && method.key) {
                  
                  eNode.addEvent(method.name, this.keyDownFunc.create({
                    
                    arguments : [sMethod, method.key],
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
    
    return true;
  },
  
  limitFunc : function(e, sMethod, sTargets) {
    
    var oTarget;
    var bResult = false;
    var aTargets = sTargets.split(',');
    
    for (var i = 0; i < aTargets.length; i++) {
      //alert(sTarget);
      var sPath = aTargets[i].replace(/^\s+/g,'').replace(/\s+$/g,'');
      
      if (sPath[0] == '$') {
        //sylma.dsp(new Date())
        
        if (sPath[1] == '>') var aChildren = this.getChildren(sPath.substring(2));
        else var aChildren = this.getElements(sPath.substring(1));
        
        aChildren.each(function(eNode) {
          
          if (!bResult && eNode === e.target) bResult = true;
        });
        
        if (bResult) break;
        
      } else {
        
        switch (sPath) {
          
          case 'self' : oTarget = this; break;
          case 'first' : oTarget = this.getFirst(); break;
        }
        
        if (e.target === oTarget) {
          
          bResult = true;
          break;
        }
      }
    }
    
    if (bResult) {
      
      var oBounded = sylma.methods[sMethod].bind(this, e);
      return oBounded();
      
    } else return true;
  },
  
  keyDownFunc : function(e, sMethod, sKeys) {
    
    var aKeys = sKeys.split(',');
    var aResults = new Array();
    // sylma.dsp(e.code);
    for (var i = 0; i < aKeys.length; i++) {
      // alert(aKeys[i]);
      switch (aKeys[i]) {
        
        case 'Enter' : iKey = 13; break;
        case 'Esc' : iKey = 27; break;
        case 'Tab' : iKey = 9; break;
        default : iKey = aKeys[i];
      }
      
      aResults.push(iKey);
    }
    //sylma.dsp(e.code + ' - ' + aResults.contains(e.code));
    if (e.code && aResults.contains(e.code)) {
      
      var oBounded = sylma.methods[sMethod].bind(this, e);
      return oBounded();
      
    } else return true;
  },
  
  createXML : function(xml, parent) {
    
    switch (xml.nodeType) {
      
      case  1 : // Element type
        
        var tmp;
        var oElement = new Element(xml.nodeName);
        
        for(var j = 0; j < xml.attributes.length; j++) {
          
          tmp = xml.attributes[j];
          oElement.setProperty(tmp.nodeName, tmp.nodeValue);
        }
        
        if (xml.childNodes.length)
          for(var i = 0; i < xml.childNodes.length; i++)
            this.createXML(xml.childNodes[i], oElement);
        
        if (parent) {
          //alert(parent.ownerDocument);
          //oElement = parent.ownerDocument.importNode(oElement, true);
          parent.adopt(oElement);
        }
        
        return oElement;
        
      break;
      
      case 3 :
        
        parent.set({'text' : xml.nodeValue});
        
      break;
      
      default : sylma.dsp('Impossible d\'ajouter ' + xml.nodeValue + ' de type ' + xml.nodeType);
    }
    
    return null;
  },
  
  load : function(oOptions) {
    
    var hOptions = $H(oOptions);
    
    if (!hOptions.has('method')) hOptions.set('method', 'post');
    
    var sPath = hOptions.get('path');
    var self = this;
    
    var sWindow = hOptions.has('window') ? hOptions.get('window') : 'action';
    
    this.request = new this.classes.request({
      
      'url' : sPath + '.' + sWindow,
      'data' : hOptions.get('arguments'),
      'method' : hOptions.get('method'),
      //'async ' : true,
      'onSuccess' : function(sResult, oXML) {
        
        if (Browser.Engine.trident) {
          
          //oXML = { documentElement: self.createXML(oXML.documentElement) }; // not working yet
          oXML = self.createXML(oXML.documentElement); // not working yet
          //oXML = oXML.ownerDocument;
        }
        
        var oContentContainer = this.parseAction(oXML);
        var oContent = self.importNodes(oContentContainer.getFirst());
        
        oContent.setStyle('opacity', 0.2);
        
        if (hOptions.get('replace')) oContent.replaces(hOptions.get('html'));
        else {
          
          if (hOptions.has('html-position')) oContent.inject(hOptions.get('html'), hOptions.get('html-position'));
          else hOptions.get('html').grab(oContent);
        }
        
        if (hOptions.has('onLoad')) hOptions.get('onLoad')();
        
        if (hOptions.get('position') == 'center') {
          
          var iLeft = (window.getSize().x - oContent.getSize().x) / 2;
          var iTop = ($(window).getSize().y - oContent.getSize().y) / 2;
          
          oContent.setStyles({'left' : iLeft, 'top' : iTop});
        }
        
        var iOpacity = hOptions.has('opacity') ? hOptions.get('opacity') : 1;
        
        // TODO kill old layer
        //layer.node.destroy(); 
        
        var sRecall = oContentContainer.getProperty('recall');
        
        if (sRecall) {
          
          // get new object
          
          var sMethods = oContentContainer.getProperty('methods');
          
          if (sMethods) {
            
            // has methods, first load em
            
            var oMethods = new Request.JSON({
              
              'url' : '/index.txt?sylma-result-id=' + sMethods,
              //'evalResponse' : true,
              'onSuccess' : function(oResponse, sResponse) {
                //alert(sResponse);
                eval(sResponse);
                self.replace(sRecall, hOptions, oContent);
                
                oContent.setStyle('opacity', iOpacity);
                
            }}).get();
            
          } else {
            
            // no methods
            
            self.replace(sRecall, hOptions);
            oContent.setStyle('opacity', iOpacity);
            
            if (hOptions.has('onSuccess')) hOptions.get('onSuccess')(oContent);
          }
          
        } else {
          
          // only change content node
          oContent.setStyle('opacity', iOpacity);
          
          if (hOptions.has('onSuccess')) hOptions.get('onSuccess')(oContent);
          //oCaller.node = oContent;
        }
      }
    }).send();
    
    return true;
  },
  
  replace : function(sID, hOptions, oResult) {
    
    var self = this;
    
    var oJSON = new Request.JSON({
      
      'url' : '/index.txt?sylma-result-id=' + sID, 
      'onSuccess' : function(oResponse) {
        
        if (!hOptions.get('parent')) hOptions.set('parent', sylma);
        
        var oNewObject = self.buildRoot(oResponse, hOptions.get('name'), hOptions.get('parent'), hOptions.get('root'));
        
        if (hOptions.has('position')) oNewObject['sylma-position'] = hOptions.get('position');
        
        if (hOptions.has('old-name')) eval('delete(hOptions.get(\'parent\').' + hOptions.get('old-name') + ')'); // delete old object
        if (oNewObject) eval('hOptions.get(\'parent\').' + hOptions.get('name') + ' = oNewObject'); // insert new object
        
        // at last : onSuccess function
        if (hOptions.has('onSuccess')) hOptions.get('onSuccess')(oResult);
        
    }}).get();
  },
  
  'methods' : {},
  
  dsp_message : function(mContent, sTargetId) {
    
    if (!sTargetId) sTargetId = 'sylma-messages-default';
    
    var eMessages = $(sTargetId);
    
    if (!($type(eMessages) == 'element')) {
      
      eMessages = new Element('div', {'id' : sTargetId, 'class' : 'sylma-messages'});
      
      var oContent = $('content');
      if (!oContent) oContent = $(document.body);
      
      oContent.grab(eMessages, 'bottom');
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
  
  dspf : function(obj) {
    
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
        //sylma.dsp(oArgs['path'] + ' : ' + oArgs['parent']['sylma-path'] + ' / ' + eNode.get('class'));
        this.node =  eNode;
        eNode.store('ref-object', this);
        
      } else sylma.dsp("Erreur : Element lié à l'objet '" + oArgs['object']['init']['id-node'] + "' introuvable !");
    }
    
    //sylma.dsp(oArgs['path'] + ' : ' + oArgs['parent']['sylma-path']);
    
    this.parentObject = oArgs['parent']; // Attach parent object
    this['sylma-path'] = oArgs['path']; // Attach parent object ref
    this.rootObject = oArgs['root']; // Attach root object (layout)
  },
  
  getName : function() {
    
    return this['sylma-path'];
  },
  
  clearTimer : function(sName) {
    
    if (this.timer) {
      
      $clear(this.timer[sName]);
      this.timer[sName] = undefined;
    }
  }
});

sylma.classes.request = new Class({
  
  Extends : Request,
  
  cleanStack : function(oContainer, iMaxLength) {
    
    var oKilled;
    if (!iMaxLength) var iMaxLength = 5;
    
    var iLength = oContainer.getChildren().length;
    
    while (iLength > iMaxLength) {
      
      oKilled = oContainer.getFirst().getNext();
      oKilled.set('tween', {onComplete : function() { oKilled.destroy(); }});
      oKilled.tween('opacity', 0);
      
      iLength--;
    }
  },
  
  'parseAction' : function(oResult, sMessages, oTarget) {
    
    var oContainer = $('msg-admin');
    // sylma.dsp(oResult.childNodes[0].tagName);
    var oMessages = $(oResult).getElement('messages');
    var oContent = $(oResult).getElement('content');
    var oInfos = $(oResult).getElement('infos');
    
    if (oContainer && oInfos) oContainer.adopt(sylma.importNodes(oInfos.getFirst()), 'top');
    
    if (!sMessages) sMessages = sylma.defaultMessagesId;
    // if (!oTarget) oTarget = sylma.defaultMessagesContainer;
    
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
    
    this.cleanStack(oContainer, 8);
    
    return oContent;
  }
});

sylma.classes.layer = new Class({
  
  Extends : sylma.classes.Base,
  isOpen : true,
  timer : undefined,
  
  initialize : function(oArgs) {
    
    if (oArgs['hidden']) this.isOpen = false;
    this.parent(oArgs);
  },
  
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
  
  insert : function(oOptions) {
    
    oOptions = $extend({
      'html': this.node,
      'parent' : this,
      'root' : this.rootObject,
      'name' : 'images',
      'path' : this.getPath()
      
    }, oOptions);
    
    return sylma.load(oOptions);
  },
  
  center : function() {
    
    var iLeft = (window.getSize().x - this.node.getSize().x) / 2;
    var iTop = ($(window).getSize().y - this.node.getSize().y) / 2;
    
    //this.node.setStyle('top', iTop);
    
    iTop = iTop >= 0 ? iTop : 0;
    
    var oFX = new Fx.Tween(this.node, {'transition' : 'sine:in:out' });
    oFX.start('top', iTop);
    
    //this.node.tween('left', iLeft);
    //.setStyles({'left' : iLeft, 'top' : iTop});
  },
  
  replace : function(oOptions) {
    
    this.node.setStyle('opacity', 0.2);
    
    oOptions = $extend({
      
      'html' : this.node,
      'old-name' : this['sylma-path'], // optional
      'name' : this['sylma-path'], // temp : replace js + html
      'parent' : this.parentObject, // optional
      'root' : this.rootObject, // optional
      'replace' : true
    }, oOptions);
    
    if (this['sylma-send-method']) oOptions.method = this['sylma-send-method'];
    if (this['sylma-position']) oOptions.position = this['sylma-position'];
    
    return sylma.load(oOptions);
  },
  
  update : function(oArguments, oOptions) {
    
    if (!oOptions) oOptions = {};
    
    oOptions = $extend({
      'path' : this.getPath(),
      'arguments' : oArguments
    }, oOptions);
    
    return this.replace(oOptions);
  },
  
  remove : function() {
    
    var oElement = this.node;
    
    oElement.fade('out').get('tween').chain(function() { oElement.dispose(); });
    eval('delete(this.parentObject.' + this['sylma-path'] + ')');
  },
  
  'show' : function() {
    
    if (!this.isOpen) {
      
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
  }
  
});
  
sylma.classes.menu = new Class({
  
  Extends : sylma.classes.layer,
  isOpen : false,
  
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
