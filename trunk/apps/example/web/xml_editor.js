/* Document Javascript */
var bKillFirebug = true;
var oXMLEditor;

$(document).ready(function() {

  oXMLEditor = new XML_Editor('#xml-editor', '/users/editor/xml-editor/interface/load.xml', 'bright');
});

if (window.console && window.console.firebug && bKillFirebug) {
  
  alert('kill your firebug !');
  // alert('kill \'em all !');
  
} else {
  
  $(document).ready(function() {
    
    $("#xml-caller").click(function(e) {
      $(this).blur();
      e.preventDefault();
      
      if (window.console && window.console.firebug && bKillFirebug) alert('Please kill your firebug !');
      else oXMLEditor.load();
    });
    
    $("#xml-saver").click(function(e) {
      $(this).blur();
      e.preventDefault();
      oXMLEditor.save();
    });
    
    $("#xml-backuper").click(function(e) {
      $(this).blur();
      e.preventDefault();
      oXMLEditor.save('backup');
    });
    
    $("#xml-file").data('mode', 'file');
    $("#xml-file").click(function(e) {
      
      var sMode = $(this).data('mode');
      var sDisplay;
      
      if (sMode == 'file') {
        
        sMode = 'db';
        sDisplay = 'DB';
        
      } else {
        
        sMode = 'file';
        sDisplay = 'Fichier';
      }
      
      $(this).data('mode', sMode)
      $(this).val(sDisplay);
      
      e.preventDefault();
    });
    
    $("#xml-path").click(function(e) {
      
      var sPath = prompt('Nouveau chemin :', this.getAttribute('value'));
      if (sPath) this.setAttribute('value', sPath);
      
      e.preventDefault();
    });
    
    $("#xml-width").click(function(e) {
      
      $(this).blur();
      e.preventDefault();
      oXMLEditor.switchWidth();
    });
  });
}

$(window).keydown(function(event){
  
  // $('#xml-messages').html(event.keyCode);
  
  switch (event.keyCode) {
    // ...
    // different keys do different things
    // Different browsers provide different codes
    // see here for details: http://unixpapa.com/js/key.html    
    // ...
  }
  // event.preventDefault();
});

function XML_Editor(sId, sPath, sStyle) {
  
  this.init = function() {
    
    this.iWidth = 70;
    this.oDocument = undefined;
    
    this.oSettings = document.implementation.createDocument('', 'settings', null);
    this.oClipboard = this.oSettings.createElement('clipboard');
    this.oSettings.documentElement.appendChild(this.oClipboard);
    
    // Editor
    
    $(this.sId).addClass('edit-structure edit-value edit-attribute edit-name');
    $(this.sId).rightClick(function() { return false; });
    $(this.sId).width(this.iWidth + '%');
  }
  
  this.save = function(sType) {
    
    if (!this.isLoaded) { alert('Aucun document chargé'); return false; }
    
    var sUrl = '/users/editor/xml-editor/interface/save.xml';
    var sMode = $("#xml-file").data('mode');
    
    var oDocument = document.implementation.createDocument("", "", null);
    var oRoot = oDocument.createElement('root');
    
    var oSetup = oDocument.createElement('setup');
    var oPathNode = oDocument.createElement('path');
    
    if (sType == 'backup') oPathNode.setAttribute('backup', 'true');
    oPathNode.setAttribute('mode', sMode);
    
    var oPath = oDocument.createTextNode($('#xml-path').val());
    
    oPathNode.appendChild(oPath);
    oSetup.appendChild(oPathNode);
    
    var oData = oDocument.createElement('data');
    
    oData.appendChild(this.oDocument.firstChild.cloneNode(true));
    
    oRoot.appendChild(oSetup);
    oRoot.appendChild(oData);
    oDocument.appendChild(oRoot);
    
    $.ajax({
      
      type: 'POST',
      url: sUrl,
      contentType: 'text/xml',
      processData: false,
      data: oDocument,
      dataType: 'text',
      success: function(sResult) {
        
        alert(sResult);
      },
      complete : function(sResult, sState) { // sState : parsererror, success
        
        if (sState != 'success') alert(sState + ' : Problème dans la réponse !');
      }
    });
    
    return true;
  }
  
  this.load = function() {
    
    var self = this;
    $.ajax({
      
      type: 'POST',
      url: this.sPath,
      data: 'path=' + $('#xml-path').val() + '&mode=' + $("#xml-file").data('mode'),
      dataType: 'xml',
      success: function(sResult) {
        
        self.isLoaded = true;
        
        // Effets
        switch (self.sStyle) {
          
          case 'bright' : 
          case 'none' : self.build(sResult); break;
          case 'fade' : $(self.sId).fadeOut("slow", function() { self.build(sResult); }); break;
          default : break;
        }
      },
      complete : function(sResult, sState) { // sState : parsererror, success
        
        if (sState == 'parsererror') alert('XML invalide');
      }
    });
  }
  
  this.build = function(oDocument) {
    
    this.oDocument = oDocument;
    
    $(this.sId).empty();
    
    // Tools
    
    this.oTools = HTML_Tag('div', '', {'class' : 'circle-tools'});
    $(this.oTools).data(
      'action-element', {'method' : 'elementRename'}).data(
      'action-spacer-element', {'method' : 'elementCreateNodeText'}).data(
      'action-spacer-attribute', {'method' : 'attributeCreate'}).data(
      'action-text', {'method' : 'elementEditText'}).data(
      'action-attribute', {'method' : 'attributeRename'}).data(
      'invert-mouse', false).data(
      'clipboard', this.oClipboard);
    
    $(this.oTools).hover(function() {}, function() { $(this).data('ref-node').hideTools(); });
    
    var oNode = XML_Extend(oDocument.firstChild).build(this.oTools);
    $(this.sId).append(oNode);
    
    // Effects
    
    switch (this.sStyle) {
      
      case 'bright' : $(this.sId).css('background-color', '#feff9f').animate({ backgroundColor : '#fff'}, 700); break;
      case 'fade' : $(this.sId).fadeIn("slow"); break;
      default : break;
    }
  }
  
  this.switchWidth = function() {
    
    this.iWidth += 20;
    
    if (this.iWidth > 90) this.iWidth = 50;
    $(this.sId).width(this.iWidth + '%');
  }
  
  this.switchMouse = function() {
    
    $(this.oTools).data('invert-mouse', !$(this.oTools).data('invert-mouse'));
  }
  
  this.sId = sId;
  this.sPath = sPath;
  this.sStyle = sStyle;
  
  this.init();
}

function XML_Extend(oNode) {
  
  for (var property in XML_Element)
    oNode[property] = XML_Element[property];
  
  if (oNode.nodeType == 1) {
    
    for (var i = 0; i < oNode.childNodes.length; i++) XML_Extend(oNode.childNodes[i]);
    for (var i = 0; i < oNode.attributes.length; i++) XML_Extend(oNode.attributes[i]);
  }
  
  oNode.isXMLElement = true;
  
  return oNode;
}

var XML_Element = {
  
  path : function(sPath) {
    
    $(this).data('tools').data('action-element', {'method' : 'path'});
    
    $('.selected').removeClass('selected');
    
    if (sPath == undefined) sPath = prompt('Indiquez un XPath :', '//');
    var iterator = this.ownerDocument.evaluate(sPath, this, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null );
    
    try {
      
      var thisNode = iterator.iterateNext();
      
      while (thisNode) {
        
        $(thisNode).data('html-node').addClass('selected');
        thisNode = iterator.iterateNext();
      }	
    }
    
    catch (e) {
      dump( 'Erreur : L\'arbre du document a été modifié pendant l\'itération ' + e );
    }

  },
  
  build : function(oTools) {
    
    $(this).data('tools', $(oTools));
    
    var oNode;
    
    switch(this.nodeType) {
      
      case 1 : oNode = this.buildElement(); break;
      case 2 : oNode = this.buildAttribute(); break;
      case 3 : oNode = this.buildText(); break;
    }
    
    $(oNode).data('ref-node', this);
    $(this).data('html-node', $(oNode));
    
    return oNode;
  },
  
  rebuild : function() {
    
    var oOldNode = $(this).data('html-node');
    
    this.build($(this).data('tools'));
    
    $(this).data('html-node').append($(this).data('tools'));
    //if ($(this).data('tools').parentNode == oOldNode) 
    $(oOldNode).hide();
    
    $(this).data('html-node').insertBefore(oOldNode);//.css('background-color', '#feff9f').animate({ backgroundColor : '#fff'}, 700);
    $(oOldNode).remove();
  },
  
  buildElement : function(oChild, oAttribute) {
    
    var oXMLDocument = this.ownerDocument;
    var oTools = $(this).data('tools');
    
    var oNode = HTML_Tag('div', '', {});
    // Node
    
    $(oNode).addClass('element clear-block')
    
    if (this.parentNode && (this.parentNode.lastChild == this)) $(oNode).addClass('last-child');
    
    // Name
    
    var oName = this.buildName();
    
    $(oNode).data('name', $(oName));
    oNode.appendChild(oName);
    
    // Attributes
    
    var oAttributes = HTML_Tag('div', '', {'class' : 'attributes inline'});
    var isFirst, isLast, isAlone, isText = false;
    
    if (this.attributes.length) {
      
      if (this.attributes.length == 1) isAlone = true;
      
      for (var i = 0; i < this.attributes.length; i++) {
        
        if (!this.attributes[i].isXMLElement) {
          
          alert('Attribut incorrect ! [' + this.attributes[i].nodeName + ']');
          continue;
        }
        
        isFirst = (i == 0);
        isLast = ((i + 1) == this.attributes.length);
        
        $(this.attributes[i]).data('o-parent', this);
        
        if (isFirst) oAttributes.appendChild(this.buildSpacer(i, 'attribute'));
        
        if (oAttribute == undefined || this.attributes[i] == oAttribute) $(oAttributes).append(this.attributes[i].build(oTools));
        else $(oAttributes).append($(this.attributes[i]).data('html-node'))
        
        if (isLast) oAttributes.appendChild(this.buildSpacer(i + 1, 'attribute'));
        else oAttributes.appendChild(document.createTextNode(' '));
      }
      
    } else oAttributes.appendChild(this.buildSpacer(1, 'attribute'));
    
    oNode.appendChild(oAttributes);
    
    // Children
    
    var oChildren = HTML_Tag('div', '', {'class' : 'children'});
    var bTextNode = false;
    var lastSpacer;
    
    isFirst = isLast = isAlone = isText = false;
    
    if (this.childNodes.length) {
      
      if (this.childNodes.length == 1) isAlone = true;
      
      for (var i = 0; i < this.childNodes.length; i++) {
        
        if (!this.childNodes[i].isXMLElement) {
          
          alert('Noeud XMLElement requis : [' + this.childNodes[i].nodeName + ']');
          continue;
        }
        
        isFirst = (i == 0);
        isLast = ((i + 1) == this.childNodes.length);
        isText = (this.childNodes[i].nodeType == 3);
        
        if (!isText && i) isText = (this.childNodes[i - 1].nodeType == 3);
        
        if (isText) bTextNode = true;
        
        // Spacer
        
        firstSpacer = this.buildSpacer(i, 'element');
        if (isFirst && !isAlone) $(firstSpacer).html(this.childNodes.length);
        if (!isText) oChildren.appendChild(firstSpacer);
        
        // Child
        
        if (oChild == undefined || oChild == this.childNodes[i]) $(oChildren).append(this.childNodes[i].build(oTools));
        else alert(oChild);$(oChildren).append($(this.childNodes[i]).data('html-node'));
        
        // Spacer
        
        lastSpacer = this.buildSpacer(i + 1, 'element');
        if (!isText && isLast) $(oChildren).append(lastSpacer);
      }
      
    } else { $(oChildren).append(this.buildSpacer(1, 'element')); bTextNode = true; }
    
    var sDisplay;
    
    // Styles
    
    if (!this.childNodes.length || (isAlone && bTextNode && this.childNodes[0].nodeValue.length < 45)) sDisplay = 'inline';
    else sDisplay = 'block';
    
    $(oNode).addClass(sDisplay);
    
    if (bTextNode) $(oChildren).addClass('inline');
    else $(oChildren).addClass('block');
    
    oNode.appendChild(oChildren);
    
    return $(oNode);
  },
  
  buildName : function() {
    
    var oName = HTML_Tag('span', this.nodeName , {'class' : 'name'}); //+ ' :'
    
    $(oName).data('ref-node', this).mousedown(function(e) {
      
      var isClicked = true, refNode = $(this).data('ref-node');
      
      if ($(refNode).data('tools').data('invert-mouse')) isClicked = e.button == 0;
      else isClicked = e.button == 2;
      
      if (isClicked) {
        
        refNode.showTools(e, [
          ['Modifier', 'M', 'elementRename'],
          
          ['Supprimer', 'S', 'elementDelete'],
          // ['Supprimer les enfants', 'E-', 'elementRemoveChildren'],
          // ['Supprimer les attributs', 'A-', 'elementRemoveAttributes'],
          
          ['Copier', 'C', 'elementCopy'],
          // ['Dupliquer', '', 'elementDuplicate'],
          
          // ['Déplacer', 'D', 'elementMove'],
          ['Couper', 'X', 'elementCut'],
          
          // ['Coller / Remplacer', 'R', 'elementPaste'],
          // ['Remplacer par du texte', 'V', 'elementReplaceNode'],
          ['Coller', 'V', 'elementPaste'],
          
          // ['Insérer dans ...', 'I', 'elementAppendTo'],
          ['Ajouter un enfant', 'E', 'elementCreateNode'],
          
          ['Replier', '-', 'elementFold'],
          // ['Tout replier', '--', 'elementFoldAll'],
          
          // ['Sélectionner', 'H', 'elementSelectAttributes'],
          ['Afficher', 'Y', 'elementDisplay'],
          // ['Sélectionner les attributs', 'A+', 'elementSelectAttributes'],
          // ['Sélectionner les enfants', 'E+', 'elementSelectChildren'],
          
          // ['Déplier', '+', 'elementUnfold'],
          ['XPath', '$', 'path'],
          // ['Tout déplier', '++', 'elementUnfoldAll'],
        ]);
        
      } else refNode.elementAction(e, $(refNode).data('tools').data('action-element')['method']); 
      
      return false;
      
    });
    
    return oName;
  },
  
  buildText : function() {
    
    var oNode = HTML_Tag('div', '', {});
    
    $(oNode).addClass('text');
    $(oNode).html(this.nodeValue);
    
    $(oNode).mousedown(function(e) {
      
      var isClicked = false, refNode = $(this).data('ref-node');
      
      if ($(refNode).data('tools').data('invert-mouse')) isClicked = e.button == 0;
      else isClicked = e.button == 2;
      
      if (isClicked) {
        
        refNode.showTools(e, [
          ['Modifier', 'M', 'elementEditText'],
          // ['Ajouter un noeud avant', '&lt;', 'elementCreateNode'],
          // ['Ajouter un noeud après', '&gt;', 'elementCreateNode'],
          ['Supprimer', 'S', 'elementDelete'],
        ]);
        
      } else refNode.elementAction(e, $(refNode).data('tools').data('action-text')['method']);
      
      return false;
    });
    
    return $(oNode);
  },
  
  buildAttribute : function() {
    
    var oNode = HTML_Tag('div', '', {});
    
    $(oNode).addClass('attribute');
    
    oName = HTML_Tag('span', this.nodeName, {'class' : 'attribute-name'});
    oEqual = HTML_Tag('span', ':', {'class' : 'attribute-equal'});
    oValue = HTML_Tag('span', this.nodeValue, {'class' : 'attribute-value'});
    
    $(oNode).mousedown(function(e) {
      
      var isClicked = false, refNode = $(this).data('ref-node');
      
      if ($(refNode).data('tools').data('invert-mouse')) isClicked = e.button == 0;
      else isClicked = e.button == 2;
      
      if (isClicked) {
        
        refNode.showTools(e, [
          ['Modifier', 'M', 'attributeRename'],
          ['Supprimer', 'S', 'attributeDelete'],
          ['Couper', 'X', 'attributeCut'],
          ['Copier', 'C', 'attributeCopy'],
        ]);
        
      } else refNode.elementAction(e, $(refNode).data('tools').data('action-attribute')['method']); 
      
      return false;
      
    });
    
    oNode.appendChild(oName);
    oNode.appendChild(oEqual);
    oNode.appendChild(oValue);
    
    return $(oNode);
  },
  
  buildSpacer : function(iIndex, sType) {
    
    var oSpacer = HTML_Tag('div', '', {'class' : 'edit-spacer'});
    
    $(oSpacer).data('type', sType);
    $(oSpacer).data('index', iIndex);
    
    $(oSpacer).data('ref-node', this).mousedown(function(e) {
      
      var isClicked = false, refNode = $(this).data('ref-node');
      
      if ($(refNode).data('tools').data('invert-mouse')) isClicked = e.button == 0;
      else isClicked = e.button == 2;
      
      if (isClicked) {
        
        switch (sType) {
          
          case 'element' : 
            
            var aTools = [
              ['Ajouter un noeud et son contenu', '*', 'elementCreateNodeText'],
              ['Ajouter un noeud', '#', 'elementCreateNode'],
              // ['Ajouter un attribut', '@', 'attributeCreate'],
              ['Ajouter du texte', 'T', 'elementCreateText'],
              ['Coller un noeud', 'V', 'elementPaste'],
            ];
            
          break;
          
          case 'attribute' :
            
            var aTools = [
              ['Ajouter un attribut', '@', 'attributeCreate'],
              ['Coller', 'V', 'attributePaste'],
            ];
            
          break;
        }
        
        $(this).data('ref-node').showTools(e, aTools);
        
      } else {
        
        refNode.elementAction(e, $(refNode).data('tools').data('action-spacer-' + sType)['method']);
      }
      
      return false;
      
    });//.rightClick(function() {
      
      // $(this).data('ref-node').elementAction($($(this).data('ref-node').oTools).data('action-spacer')['method']);
      // return false;
      
    // });
    
    return oSpacer;
  },
  
  elementAction : function(e, sName) {
    
    $(this).data('tools').data('n-caller', $(e.currentTarget));
    
    if (this[sName]) this[sName]();
    else alert('Cette fonction n\'existe pas !');
  },
  
  elementMove : function() {
    
    alert('Déplacer !');
  },
  
  elementPaste : function() {
    
    $(this).data('tools').data('action-spacer-element', {'method' : 'elementPaste'});
    
    var oChild = $(this).data('tools').data('clipboard').lastChild;
    
    this.elementAdd(XML_Extend(oChild.cloneNode(true)));
  },
  
  elementCopy : function() {
    
    $(this).data('tools').data('action-element', {'method' : 'elementCopy'});
    
    this.clipboardPush(this.cloneNode(true));
  },
  
  elementCut : function() {
    
    var parentNode = this.parentNode;
    
    $(this).data('tools').data('action-element', {'method' : 'elementCut'});
    
    this.clipboardPush(this.cloneNode(true));
    
    if (this.parentNode && this.parentNode != this.ownerDocument) {
      
      this.parentNode.removeChild(this);
      $(this).data('html-node').slideUp('slow', function() { parentNode.rebuild(); });
      
    } else alert('Impossible de supprimer l\'élément racine !');
    
  },
  
  elementDelete : function(oChild) {
    
    if (this.parentNode && this.parentNode != this.ownerDocument) {
      
      $(this).data('tools').data('action-element', {'method' : 'elementDelete'});
      
      if (confirm('Supprimer le noeud ?')) {
        
        var self = this;
        
        $(this).data('html-node').slideUp('fast', function() {
          
          var parentNode = self.parentNode;
          
          self.parentNode.removeChild(self);
          parentNode.rebuild(self);
        });
      }
    } else alert('Impossible de supprimer l\'élément racine !');
  },
  
  elementAdd : function(oChild) {
    
    var iIndex = $(this).data('tools').data('n-caller').data('index');
    
    if (this.childNodes.length > iIndex && this.childNodes[iIndex])
      this.insertBefore(oChild, this.childNodes[iIndex]);
    else
      this.appendChild(oChild);
    
    this.rebuild(oChild);
  },
  
  elementCreateNode : function() {
    
    $(this).data('tools').data('action-spacer-element', {'method' : 'elementCreateNode'});
    
    var oChild = undefined;
    var sName = prompt('Nom (sans espace) :');
    
    if (sName) {
      
      oChild = XML_Extend(this.ownerDocument.createElement(sName));
      
      if (oChild) this.elementAdd(oChild);
      else alert('Nom invalide !');
    }
    
    return oChild;
  },
  
  elementCreateText : function() {
    
    $(this).data('tools').data('action-spacer-element', {'method' : 'elementCreateText'});
    
    var oChild = undefined;
    var sValue = prompt('Nouveau contenu :');
    
    if (sValue) {
      
      oChild = XML_Extend(this.ownerDocument.createTextNode(sValue));
      
      // this.nodeValue = sValue;
      this.elementAdd(oChild);
    }
    
    return oChild;
  },
  
  elementCreateNodeText : function() {
    
    $(this).data('tools').data('action-spacer-element', {'method' : 'elementCreateText'});
    
    var oNode = this.elementCreateNode();
    
    if (oNode) {
      
      oNode.elementCreateText();
      $(this).data('tools').data('action-spacer-element', {'method' : 'elementCreateNodeText'});
    }
  },
  
  elementEditText : function() {
    
    $(this).data('tools').data('action-text', {'method' : 'elementEditText'});
    
    var sValue = prompt('Nouveau contenu :', this.nodeValue);
    
    if (sValue) {
      
      this.nodeValue = sValue;
      this.parentNode.rebuild(this);
    }
  },
  
  elementDisplay : function() {
    
    $(this).data('tools').data('action-element', {'method' : 'elementDisplay'});
    
    // var xmlobject = (new DOMParser()).parseFromString(xmlstring, "text/xml");
    var sContent = (new XMLSerializer()).serializeToString(this);
    
    prompt('Take it easy !', sContent);
  },
  
  elementRename : function() {
    
    $(this).data('tools').data('action-element', {'method' : 'elementRename'});
    
    // Modifification du nom du noeud
    
    var sNom = prompt('Nouveau nom (sans espace) :', this.nodeName);
    
    if (sNom) {
      
      var oXMLNode = XML_Extend(this.ownerDocument.createElement(sNom));
      
      if (!oXMLNode) alert('Nom incorrect !');
      else {
        
        var oParentNode = this.parentNode;
        
        // Création d'un nouveau noeud avec le nouveau nom choisi
        
        $(oXMLNode).data('tools', $(this).data('tools'));
        $(oXMLNode).data('html-node', $(this).data('html-node'));
        
        // Transfert des éléments enfants vers le nouveau noeud
        
        while (this.childNodes.length) oXMLNode.appendChild(this.childNodes[0]);
        
        // Transfert des attributs vers le nouveau noeud
        
        var oAttribute;
        
        while (this.attributes.length) {
          
          oAttribute = this.attributes[0];
          this.removeAttributeNode(oAttribute);
          oXMLNode.setAttributeNode(oAttribute);
        }
        
        // Remplacement de l'ancien noeud
        
        oParentNode.replaceChild(oXMLNode, this);
        oXMLNode.rebuild();
      }
    }
  },
  
  attributeCut : function() {
    
    $(this).data('tools').data('action-attribute', {'method' : 'attributeCut'});
    
    this._attributeCopy();
    this._attributeDelete();
  },
  
  attributeCopy : function() {
    
    $(this).data('tools').data('action-attribute', {'method' : 'attributeCopy'});
    
    this._attributeCopy();
  },
  
  attributePaste : function() {
    
    $(this).data('tools').data('action-spacer-attribute', {'method' : 'attributePaste'});
    
    var oChild = $(this).data('tools').data('clipboard').lastChild;
    var oNewAttribute = undefined;
    
    if (oChild.attributes.length) {
      
      for (var i = 0; i < oChild.attributes.length; i++) {
        
        var oAttribute = oChild.attributes[i];
        
        if (!this.getAttribute(oAttribute.nodeName) || confirm("Ecraser l'attribut '" + oAttribute.nodeName + "' existant ?")) {
          
          // Can't just copy cause of the xmlns that are not well read
          
          var oNewAttribute = XML_Extend(this.ownerDocument.createAttribute(oAttribute.nodeName));
          oNewAttribute.nodeValue = oAttribute.nodeValue;
          
          this.setAttributeNode(oNewAttribute);
        }
      }
      
      this.rebuild(undefined, oNewAttribute);
    }
  },
  
  _attributeCopy : function() {
    
    var oClipboard = $(this).data('tools').data('clipboard');
    
    var oEmpty = oClipboard.ownerDocument.createElement('empty');
    oEmpty.setAttribute(this.nodeName, this.nodeValue);
    
    this.clipboardPush(oEmpty);
  },
  
  clipboardPush : function(oElement) {
    
    var oClipboard = $(this).data('tools').data('clipboard');
    
    oClipboard.appendChild(oElement);
    if (oClipboard.childNodes.length > 10) oClipboard.removeChild(oClipboard.firstChild);
  },
  
  attributeRename : function() {
    
    $(this).data('tools').data('action-attribute', {'method' : 'attributeRename'});
    
    var sName = prompt('Nouveau nom (sans espace) :', this.nodeName);
    
    if (sName) {
      
      var sValue = prompt('Nouveau contenu :', this.nodeValue);
      
      var oAttribute = XML_Extend(this.ownerDocument.createAttribute(sName));
      
      if (oAttribute) {
        
        oAttribute.nodeValue = sValue;
        
        var oParentNode = $(this).data('o-parent');
        
        oParentNode.removeAttributeNode(this);
        oParentNode.setAttributeNode(oAttribute);
        oParentNode.rebuild();
        
      } else alert('Nom d\'attribut invalide !');
    }
  },
  
  attributeCreate : function() {
    
    $(this).data('tools').data('action-spacer-attribute', {'method' : 'attributeCreate'});
    
    var sName = prompt('Nom (sans espace) :');
    
    if (sName) {
      
      var sValue = prompt('Valeur :');
      
      if (sValue) {
        
        var oAttribute = XML_Extend(this.ownerDocument.createAttribute(sName));
        
        if (oAttribute) {
          
          oAttribute.nodeValue = sValue;
          
          this.setAttributeNode(oAttribute);
          this.rebuild(undefined, oAttribute);
          
        } else alert('Nom d\'attribut invalide !');
      }
    }
  },
  
  attributeDelete : function() {
    
    $(this).data('tools').data('action-attribute', {'method' : 'attributeDelete'});
    
    if (confirm('Supprimer l\'attribut ?')) this._attributeDelete();
  },
  
  _attributeDelete : function() {
    
    var self = this;
    
    $(this).data('html-node').fadeOut('fast', function() {
      
      var parentNode = $(self).data('o-parent');
      
      parentNode.removeAttributeNode(self);
      parentNode.rebuild(self);
    });
  },
  
  hideTools : function(e) {
    
    $(this).data('tools').data('n-caller').removeClass('navigate');
    
    var iCenterX = $(this).data('tools').width() / 2 - ($(this).data('tools').data('tool-size').width / 2);
    var iCenterY = $(this).data('tools').height() / 2 - ($(this).data('tools').data('tool-size').height / 2);
    
    $(this).data('tools').find('.tool').animate({marginLeft: iCenterX + 'px', marginTop: iCenterY + 'px'}, 500);
    $(this).data('tools').fadeOut('fast');
  },
  
  runTool : function(e, sParam, aTools) {
    
    
  },
  
  showTools : function(e, aTools) {
    
    $(this).data('tools').data('n-caller', $(e.currentTarget));
    $(this).data('tools').data('n-caller').addClass('navigate');
    // $(this).data('html-node').addClass('navigate');
    
    switch (this.nodeType) {
      
      case 1 : $(this).data('tools').data('n-parent', $(this).data('html-node')); break;
      case 2 : $(this).data('tools').data('n-parent', $($(this).data('o-parent')).data('html-node')); break;
      case 3 : $(this).data('tools').data('n-parent', $(this).data('html-node').parent()); break;
    }
    
    $(this).data('tools').data('ref-node', this);
    
    if ($('#xml-editor.rounded').length) this.buildCircleMenus(e, aTools);
    else this.buildSquareMenus(e, aTools);
  },
  
  buildCircleMenus : function(e, aTools) {
    
    var iToolWidth = 12
    var iToolHeight = 12;
    var iToolFullWidth = (iToolWidth) + (3 * 2);
    var iToolFullHeight = (iToolHeight) + (3 * 2);
    
    // Circles
    
    var aCircleCount = [1, 7, 14, 49];
    var iCircle = 0;
    var iStart = Math.PI * 2.5;
    
    var iAdded = 0;
    
    for (var i = 0; i < aCircleCount.length; i++) {
      
      if ((aTools.length - iAdded) > aCircleCount[i]) iAdded += aCircleCount[i];
      else { iAdded = aTools.length - iAdded; break; }
    }
    
    var iCountSide = ((2 * Math.PI) / aCircleCount[i]) * iAdded;
    
    var iCircleCount = i + 1;
    // alert(iCircleCount + '/' + aTools.length)
    var doSquare = false;
    
    // Tools
    
    var iToolsMarginTop = 0;
    var iToolsMarginLeft = 0;
    
    var iToolsWidth = Math.round(((iCircleCount * 2) - 1) * iToolFullWidth);
    var iToolsHeight = Math.round(((iCircleCount * 2) - 1) * iToolFullHeight);
    
    if (aTools.length < 3) { iToolsHeight -= iToolFullHeight; }
    if (aTools.length < 4) { iToolsWidth -= iToolFullWidth; iToolsMarginLeft += iToolFullWidth / 2; }
    if (aTools.length < 6) { iToolsHeight -= iToolFullHeight; iToolsMarginTop += iToolFullHeight / 2; }
    
    // if (iStart - iCountSide < Math.PI && iStart >= Math.PI / 2) iToolsHeight -= iToolHeight;
    // if (iCountSide < Math.PI) iToolsWidth -= iToolWidth;
    
    // if (iStart >= Math.PI / 2) {//alert('a' + iStart + '/' + Math.PI / 2);
      
      // if (iCountSide < Math.PI * 1.5) iToolsWidth -= (Math.sin(iCountSide) * iToolFullWidth); // quart
      
    // } else if (iStart > -Math.PI * 1.5) {//alert('b');
      
      // if (iCountSide < Math.PI / 2) iToolsWidth -= iToolWidth; 
      // if (iCountSide < Math.PI) iToolsHeight -= iToolHeight; 
      // else if (iCountSide < Math.PI * 1.5) iToolsHeight -= (Math.cos(iCountSide) * iToolFullHeight); // moitié
    // }
    
    // alert(iStart);
    //alert(Math.atan(iCountSide));
    
    var oTools = $(this).data('tools');
    
    $(oTools).data('tool-size', {'width' : iToolWidth, 'height' : iToolHeight});
    $(oTools).data('ref-node', $(this));
    $(this).addClass('navigate');
    
    $(oTools).empty();
    $(oTools).fadeIn();
    
    var iCenterX = Math.round(iToolsWidth / 2);
    var iCenterY = Math.round(iToolsHeight / 2);
    
    var iToolsLeft = e.pageX - iCenterX + 1;
    var iToolsTop = e.pageY - iCenterY - 1; // - (iToolHeight / 4)
    
    $(oTools).css({
      
      'width' : iToolsWidth + 'px',
      'height' : iToolsHeight + 'px',
      'left' : iToolsLeft + 'px',
      'top' : iToolsTop + 'px'
    }).show();
    
    // Tool
    
    var oTool;
    
    var iDelta = 0;
    var iAngle = 0;
    var iX = 0;
    var iY = 0;
    
    for (var i = 0; i < aTools.length; i++) { // Côté
      
      oTool = HTML_Tag('div', aTools[i][1], {'class' : 'tool', 'title' : aTools[i][0]});
      
      $(oTool).data('ref-node', this).data('method', aTools[i][2]).click(function(e) {
        
        this.hideTools();
        $(this).data('ref-node').elementAction(e, $(this).data('method'));
        
      });
      
      if (i == aCircleCount[iCircle]) {
        
        iCircle++;
        
        iDelta = (Math.PI * 2) / (aCircleCount[iCircle] - 1);
        iAngle = iStart;
      }
      
      if (iCircle) iX = Math.round(Math.sin(iAngle) * iCircle * iToolFullWidth);
      if (iCircle) iY = Math.round(Math.cos(iAngle) * iCircle * iToolFullHeight);
      
      iX += iCenterX - (iToolFullWidth / 2) ;
      iY += iCenterY - (iToolFullHeight / 2);
      
      iAngle -= iDelta;
      // oTool.setAttribute('title', iY + 'px');
      $(oTool).appendTo(oTools).css({
        
        'width' : iToolWidth + 'px',
        'height' : iToolHeight + 'px',
        'margin-left' : iCenterX - (iToolWidth / 2) + 'px',
        'margin-top' : iCenterY - (iToolWidth / 2) + 'px',
        'opacity' : 0.01
        
      }).animate({
        
        'opacity' : 1.0,
        marginTop : iY + 'px',
        marginLeft : iX + 'px'
        
      }, 300);
    }
    
    return $(this);
  },
  
  buildSquareMenus : function(e, aTools) {
    
    var iToolWidth = 13, iToolHeight = 13;
    var iToolFullWidth = iToolWidth + 4, iToolFullHeight = iToolHeight + 3;
    
    var aCircleCount = [1, 9, 25, 49], iCircle = 0, iStart = -Math.PI / 2;
    
    var iAdded = 0;
    
    for (var i = 0; i < aCircleCount.length; i++) {
      
      if ((aTools.length - iAdded) > aCircleCount[i]) iAdded += aCircleCount[i];
      else { iAdded = aTools.length - iAdded; break; }
    }
    
    var iCountSide = (4 / aCircleCount[i]) * iAdded;
    
    var iCircleCount = i + 1;
    
    var doSquare = true;
    
    var iToolsWidth = Math.round(((iCircleCount * 2) - 1) * iToolFullWidth);
    var iToolsHeight = Math.round(((iCircleCount * 2) - 1) * iToolFullHeight);
    
    var oTools = $(this).data('tools');
    //alert($(this).data('tools'));
    
    $(oTools).data('n-parent').prepend(oTools);
    $(oTools).data('tool-size', {'width' : iToolWidth, 'height' : iToolHeight});
    
    $(oTools).empty();
    $(oTools).show().fadeIn('fast');
    
    var iCenterX = Math.round(iToolsWidth / 2);
    var iCenterY = Math.round(iToolsHeight / 2);
    
    var oTool;
    
    var iCircleHalfX = 0, iCircleHalfY = 0;
    
    var iDelta = 0, iAngle = 0, iForce = 1;
    var iX = 0, iY = 0;
    
    if (iCountSide <= 0.5) { iToolsHeight -= iToolHeight; iCenterY -= iToolHeight; }
    if (iCountSide <= 1.5) iToolsWidth -= iToolWidth;
    if (iCountSide <= 2.5) iToolsHeight -= iToolHeight;
    
    $(oTools).css({'width' : iToolsWidth + 'px', 'height' : iToolsHeight + 'px', 'left' : (e.pageX - iCenterX) + 'px', 'top' : (e.pageY - iCenterY - (iToolHeight / 4)) + 'px'}).show();
    
    var oSourceEvent = e;
    
    for (var i = 0; i < aTools.length; i++) { // Côté
      
      oTool = HTML_Tag('div', aTools[i][1], {'class' : 'tool', 'title' : aTools[i][0]});
      
      $(oTool).data('ref-node', this).data('method', aTools[i][2]).mousedown(function(e) {
        
        $(this).data('ref-node').hideTools();
        $(this).data('ref-node').elementAction(oSourceEvent, $(this).data('method'));
        
        return false;
      });
      
      if (i == aCircleCount[iCircle]) {
        
        iCircle++;
        
        iCircleHalfX = (iCircle * iToolFullWidth);
        iCircleHalfY = (iCircle * iToolFullHeight);
        
        if (doSquare) iDelta = (Math.PI * 2) / (aCircleCount[iCircle] - 1);
        else iDelta = (Math.PI * 2) / (aTools.length);
        
        iAngle = iStart;
      }
      
      if (doSquare) iForce = 2;
      else iForce = 1;
      
      if (iCircle) iX = Math.round(Math.sin(iAngle) * iCircle * (iToolFullWidth * iForce) );
      if (iCircle) iY = Math.round(Math.cos(iAngle) * iCircle * (iToolFullHeight * iForce));
      
      if (iCircle && doSquare) {
        
        if (iX > iCircleHalfX) iX = iCircleHalfX;
        else if (iX < -iCircleHalfX) iX = -iCircleHalfX;
        
        if (iY > iCircleHalfY) iY = iCircleHalfY;
        else if (iY < -iCircleHalfY) iY = -iCircleHalfY;
      }
      
      iX += iCenterX - (iToolFullWidth / 2);
      iY += iCenterY - (iToolFullHeight / 2);
      
      iAngle -= iDelta;
      
      $(oTool).css({'width' : iToolWidth + 'px', 'height' : iToolHeight + 'px', 'margin-left' : iCenterX + 'px', 'margin-top' : iCenterY + 'px', opacity: 1.0, fontSize: '0.8em'});
      $(oTool).css({'margin-left' : iX + 'px', 'margin-top' : iY + 'px', 'opacity' : 1.0, 'display' : 'none'}); // TODO : à virer
      $(oTool).appendTo(oTools).fadeIn('fast');
      // $(oTool).appendTo(oTools).animate({marginLeft :  + 'px', marginTop : iY + 'px', opacity: 1.0, fontSize: '0.8em'}, 50);
    }
    
    return this;
  }
  

};

function dumpProps(obj, parent, recursion) {
  
  var sContent = '';
  recursion++;
  
  for (var i in obj) {
    
    try {
      if (parent) {
        
        sContent = +parent + "." + i + " : " + obj[i];
        
      } else {
        
        sContent += i + " : " + obj[i];
        // if (!confirm(sContent)) { return; }
      }
      
      if (typeof obj[i] == "object" && recursion < 1) {
        
        sContent += '<div style="margin-left: 1em">';
        
        if (parent) sContent += dumpProps(obj[i], parent + "." + i, recursion);
        else sContent += dumpProps(obj[i], i, recursion);
        
        sContent += '</div>';
      }
      
      sContent += '<br/>';
      
    } catch (t) { sContent += 'Impossible !<br/>'; }
  }
  
  return sContent;
}

function dsp(obj) {
  
  $('#xml-messages').html(dumpProps(obj, undefined, 0));
}

function HTML_Tag(sName, sContent, aAttributes) {
  
  var oElement = document.createElement(sName);
  // typeof sContent == 'string';
  
  if (jQuery.isFunction(sContent)) oElement.appendChild(sContent);
  else oElement.innerHTML = sContent;
  
  if (aAttributes) for (var i in aAttributes) oElement.setAttribute(i, aAttributes[i]);
  
  return oElement;
}

function XML_Tag(sName, sContent, aAttributes) {
  
  var oXMLDocument = document.implementation.createDocument("", "", null);
  var oElement = oXMLDocument.createElement(sName);
  // var oElement = document.createElement(sName);
  
  if (jQuery.isFunction(sContent)) oElement.appendChild(sContent);
  else oElement.nodeValue = sContent;
  
  for (var i in aAttributes) oElement.setAttribute(i, aAttributes[i]);
  
  return oElement;
}

if(jQuery) (function($) {
    
    $.extend({
      ahover: {
        version: 1.0,
        defaults: {
          toggleSpeed: 75,
          toggleEffect: 'both',
          hoverEffect: null,
          moveSpeed: 250,
          easing: 'swing',
          className: 'ahover'
        },
        effects: {
          'width': {width: 0},
          'height': {height: 0},
          'both': {width: 0, height: 0}
        }
      }
    });
  
	$.extend($.fn, {
    
		rightClick: function(handler) {
			$(this).each( function() {
				$(this).mousedown( function(e) {
					var evt = e;
					$(this).mouseup( function() {
						$(this).unbind('mouseup');
						if( evt.button == 2 ) {
							handler.call( $(this), evt );
							return false;
						} else {
							return true;
						}
					});
				});
				$(this)[0].oncontextmenu = function() {
					return false;
				}
			});
			return $(this);
		},		
		
		rightMouseDown: function(handler) {
			$(this).each( function() {
				$(this).mousedown( function(e) {
          var evt = e;
					if( evt.button == 2 ) {
						handler.call( $(this), evt );
						return false;
					} else {
						return true;
					}
				});
				$(this)[0].oncontextmenu = function() {
					return false;
				}
			});
			return $(this);
		},
		
		rightMouseUp: function(handler) {
			$(this).each( function() {
				$(this).mouseup( function(e) {
					if( e.button == 2 ) {
						handler.call( $(this), e );
						return false;
					} else {
						return true;
					}
				});
				$(this)[0].oncontextmenu = function() {
					return false;
				}
			});
			return $(this);
		},
		
		noContext: function() {
			$(this).each( function() {
				$(this)[0].oncontextmenu = function() {
					return false;
				}
			});
			return $(this);
		},
    
    /*
     * Extension Mask pour JQuery, qui créer un masque au dessus des tags sélectionnés
     **/
    mask: function(options) {
      
      this.mask = document.createElement('div');
      // var oOffset = $(this.sId).offset();
      
      $(this.mask).css({
        
        position: 'absolute',
        width : this.css('width'),
        height : this.css('height'),
        top : this.offset().top + 'px',
        left : this.offset().left + 'px',
        backgroundColor : '#feff9f',
        opacity : 1.0
      });
      
      $('body').append(this.mask);
      $(this.mask).animate({ opacity: 0 }, 1000);
      $(this.mask).remove();
    }
		
	});
})(jQuery);
