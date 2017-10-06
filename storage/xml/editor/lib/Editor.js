
sylma.xml = {};

sylma.xml.EditorClass = {

  Extends : sylma.ui.Container,
  Implements: Events,
  
  enable : false,
  namespaces : {},
  updating : false,
  document : null,
  documents : [],

  onLoad : function () {
    
    var document = this.options.document;

    this.file = this.options.file;
    this.container = this.getObject('container');
  
    this.prepareSchema();
    this.document = this.prepareDocument(document, true);
    this.current = this.document;

    var history = this.getHistory();
    
    window.addEvent('unload', function() {

      history.save();
    });
  },
  
  prepareSchema: function () {
    
    var root = this.options.schemas.root;
    var schema = new sylma.xsd.Schema(root, this.options.namespaces);
    schema.editor = this;
    
    this.schema = schema;
  },
  
  prepareDocument: function (content, parse) {
    
    var doc = this.buildDocument(content, parse);
    var schema = this.schema;
    
    schema.validate(doc);
    
    this.updateTime = this.options.update;
    this.setReady();
    
    return doc;
  },
  
  setReady : function()
  {
    this.enable = true;
  },
  
  setDisabled : function()
  {
    this.enable = false;
    this.getNode().addClass('disabled');
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

  buildDocument : function(options, parse) {
    
    var container = this.container;
    var doc;
    
    if (parse)
    {
      var parser = new DOMParser();
      doc = parser.parseFromString(options, "text/xml");
    }
    else
    {
      doc = options;
    }
//    console.log(doc);
    var content = {element : [this.buildElement(doc.documentElement)], document : doc};
    
    this.documents.each(function(document)
    {
      document.hide();
    });
    
    var result;

    switch (doc.documentElement.namespaceURI)
    {
      case 'http://2017.sylma.org/view' :
        
        result = container.add('view', content);
        break;
      
      default :
        result = container.add('document', content);
    }
    
    this.documents.push(result);

    this.setReady();
    
    return result;
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
//      format : this.isComplex(el) ? 'complex' : !el.childNodes.length || el.childNodes[0].nodeValue.length < 100 ? 'text' : 'complex'
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
        if (0 && child.namespace === 'http://2016.sylma.org/storage/xml/editor')
        {
          children.push({
            _alias : 'spacer',
          });
        }
        else
        {
          children.push(this.buildElement(child));
        }
      }
      else if (child.nodeType === child.COMMENT_NODE) 
      {
        children.push({
          _alias : 'comment',
          content : this.escapeHtml(child.nodeValue.trim()),
        });
      }
      else {

        var content = child.nodeValue;

        if (content)
        {
//console.log(content, content.match(/\s*[\n\r]\s*[\n\r]/g));
          if (0 && i !== len - 1 && content.match(/\s*[\n\r]\s*[\n\r]/g))
          {
        children.push({
              _alias : 'spacer',
            });
          }
          else
          {
            children.push({
          _alias : 'text',
          content : content.trim(),
        });
      }
    }
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
  },
  
  publish: function ()
  {
    var compiler = new window.titan.Compiler;
    var document = this.current.options.document;
    var editor = this;
    
    compiler.prepareDOM(document, 1, function(result)
    {
      editor.send(editor.options.publish, {
        file : editor.file,
        scripts : result.map(function(window) { return { name : window.name, content : window.content }})
      }, function()
      {
        sylma.ui.showMessage('Document published');
      });
    });
  },
  
  findNode: function (path, type, name, prefix)
  {
    var result;
    var paths = path.split('/');
//console.log(path, paths);
    paths.shift();

    var element = this.document.element;

    if (paths.length) 
    {
      paths.each(function(path)
      {
//console.log(element);
        if (path) element = element.children[path];
      });
    }
    
    switch (type)
    {
      case 'text' : //result = element.children[0]; break;
      case 'element' : result = element; break;
      case 'attribute' : result = element.attributes[(prefix ? prefix + ':' : '') + name]; break;
      default : throw new Error('Unknown step type');
    }
    
    if (!result)
    {
      throw new Error('No node found with path ' + path);
    }
    
    return result;
  }
  
};

sylma.xml.Editor = new Class(sylma.xml.EditorClass);