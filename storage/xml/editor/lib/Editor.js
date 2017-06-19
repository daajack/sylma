
sylma.xml = {};

sylma.xml.Editor = new Class({

  Extends : sylma.ui.Container,
  
  namespaces : {},

  onLoad : function () {
    
    this.prepareDocument();
  },
  
  prepareDocument: function () {
    
    var doc = this.buildDocument(this.options.document);

    var history = this.getHistory();
    
    window.addEvent('unload', function() {

      history.save();
    });

    var root = this.options.schemas.root;

    var schema = new sylma.xsd.Schema(root, this.options.namespaces);
    schema.validate(doc);
    
    schema.editor = this;

    this.schema = schema;
    this.file = this.options.file;
    this.updateTime = this.options.update;
  },
  
  isComplex : function(el)
  {
    var len = el.childNodes.length;
    
    return len && (len > 1 || el.childNodes[0].nodeType === el.ELEMENT_NODE);
  },
  
  /**
   * @from https://stackoverflow.com/a/4835406
   */
  escapeHtml : function(text) 
  {
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };

    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
  },

  buildDocument : function(options) {
    
    var container = this.getObject('container');
    var parser = new DOMParser();
    var doc = parser.parseFromString(options, "text/xml");
    var content = {element : [this.buildElement(doc.documentElement)]};

    return container.add('document', content);
  },
  
  parseDocument: function (content) 
  {
    var parser = new DOMParser();
    var doc = parser.parseFromString(content, "text/xml");
    
    return doc;
  },
  
  buildElement : function(el) {
    
    var result = {
      _alias : 'element',
      namespace : el.namespaceURI,
      prefix : el.prefix ? el.prefix : '',
      name : el.localName,
      attribute : new Array(),
      format : this.isComplex(el) ? 'complex' : !el.childNodes.length || el.childNodes[0].nodeValue.length < 100 ? 'text' : 'complex'
    };

    var len = el.attributes.length;
    var attr, child;
    
    for (var i = 0; i < len; i++)
    {
      attr = el.attributes[i];
      
      if (attr.prefix === 'xmlns') continue;
      if (attr.name === 'xmlns') continue;
      
      result.attribute.push({
        prefix : attr.prefix ? attr.prefix : '',
        name : attr.localName,
        namespace : attr.namespaceURI,
        value : attr.nodeValue,
      });
    }

    var children = new Array();

    var len = el.childNodes.length;
    
    for (var i = 0; i < len; i++)
    {
      child = el.childNodes[i];

      if (child.nodeType === child.ELEMENT_NODE)
      {
        children.push(this.buildElement(child));
      }
      else if (child.nodeType === child.COMMENT_NODE) 
      {
        children.push({
          _alias : 'comment',
          content : this.escapeHtml(child.nodeValue.trim()),
        });
      }
      else {

        var content = child.nodeValue.trim();

        children.push({
          _alias : 'text',
          content : content,
        });
      }
    }

    if (children.length) {

      result.children = [{
        _all : children
      }];
    }

    return result;
  },
  
  getHistory: function () {
    
    return this.getObject('history');
  },

  startMove: function () {

    this.getNode().removeClass('edit');
    this.getNode().addClass('move');
  },

  stopMove: function () {

    this.getNode().removeClass('move');
    this.getNode().addClass('edit');
  }
});