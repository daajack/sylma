/* Document JS */

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
      //this.dsp(sMethod);
      method = object.methods[sMethod];
      
      if (method.name && (method['path-node'] || method['id-node']) && sylma.methods[sMethod]) {
        
        if (method['path-node']) {
          
          eNode = $$(method['path-node']);
          if (eNode.length) eNode = eNode[0];
          
        } else eNode = $(method['id-node']);
        
        if ($type(eNode) == 'element') {
          
          eNode.store('ref-object', oParent);
          eNode.addEvent(method.name, sylma.methods[sMethod]);
          
        } else {
          
          this.dsp('Erreur :: Objet DOM introuvable - path : ' + method['path-node'] + ' - id : ' + method['id-node']);
        }
        
      } else {
        
        this.dsp("Erreur :: Méthode '" + method.name + "' invalide !");
        this.dsp(this.view(method));
      }
    }
  },
  
  dsp : function(sContent) {
    
    var sId = 'sylma-messages';
    
    var eMessages = $(sId);
    
    if (!($type(eMessages) == 'element')) {
      
      eMessages = new Element('div', {'id' : sId});
      $('content').grab(eMessages, 'top');
    }
    
    var sStyle = 'border-bottom: 1px solid gray; margin-bottom: 1em;';
    eMessages.grab(new Element('div', {'html' : sContent, 'style' : sStyle}));
  },
  
  view : function(obj, parent, recursion) {
    
    if (!recursion) recursion = 5;
    
    var sContent = '';
    var iMaxRecursion = 10;
    
    for (var i in obj) {
      
      try {
        
        sContent += '<div style="margin-left: ' + (6 - recursion) + 'em;">';
        
        // if (parent) sContent = parent + "." + i + " : " + obj[i];
        sContent += '<strong>' + i + '</strong>' + " : " + obj[i];
        
        if (typeof obj[i] == "object" && recursion) {
          
          sContent += '<div style="margin-left: ' + (6 - recursion + 1) + 'em">';
          
          // if (parent) sContent += this.view(obj[i], parent + "." + i, recursion - 1);
          sContent += this.view(obj[i], i, recursion - 1);
          
          sContent += '</div>';
        }
        
        sContent += '</div>';
        
      } catch (t) { sContent += '<br/>Erreur :: Propriété : ' + i + ' (' + t + ')<br/>'; }
    }
    
    return sContent;
    //this.dsp(sContent);
  }
};

sylma['classes'] = {
  
  'menu' : new Class({
    
    'reveal' : function() {
      
      this.node.setStyle('display', 'block');
      this.node.fade('in');
      
      return true;
    },
    
    'show' : function() {
      
      return this.reveal();
    },
    
    'hide' : function() {
      
      this.node.fade('out');
      
      return true;
    }
  }),
  
  'layer' : new Class({
    
    'test' : 'bonsoir',
    
    'salut' : function() {
      
      alert(this.test);
    },
    
    'update' : function() {
    
    }
  }),
  
  'layout' : new Class({
  
  })
  
};

sylma['classes']['menu-common'] = new Class({
  
  Extends : sylma.classes.menu,
  
  'show' : function(eTarget) {
    
    $(eTarget).grab(this.node); //, 'top'
    
    return this.reveal();
  }
});


