
sylma.xml = {};

sylma.xml.EditorClass = {

  Extends : sylma.ui.Container,
  Implements: Events,
  
  enable : false,
  namespaces : {},
  updating : false,
  file : null,

  onLoad : function () 
  {
    this.files = {};
    this.file = this.options.file;
    var container = this.getObject('container');
//console.log(container);
    var file = container.tmp[0];
    
    this.files[file.options.path] = file;
    
    this.container = file.getObject('container');
    this.schema = this.prepareSchema(file.options.schemas.root);
    
    file.prepare(this.schema);
  },
  
  prepareSchema: function (root) {
    
    var schema = new sylma.xsd.Schema(root, this.options.namespaces);
    schema.editor = this;
    
    return schema;
  },
//  
//  setReady : function()
//  {
//    this.enable = true;
//  },
  
  setDisabled : function()
  {
    this.enable = false;
    this.getNode().addClass('disabled');
  },
  
  open: function (file)
  {
    var editor = this;
    var container = this.getObject('container');
    
    Object.each(this.files, function(file) { file.hide(); } );
    
    if (this.files[file])
    {
      this.files[file].show();
    }
    else
    {
      container.options['sylma-inside'] = true;
      var node = container.getNode();
      
      node.addClass('loading');

      this.send(container.options.path,
      {
        file : file,
      },
      function(response)
      {
        var result = sylma.ui.parseMessages(response);
        var childNode = sylma.ui.importNode(result.content)[0];
        var props = container.importResponse(result, container);
  //console.log(node);
        container.getNode().grab(childNode);
        var file = container.initObject(props);

        container.onWindowLoad();

        var schema = editor.prepareSchema(file.options.schemas.root);
        file.prepare(schema);

        editor.files[file.path] = file;

        node.removeClass('loading');
        editor.fireEvent('update');
      });
    }
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

  startMove: function () {

    this.getNode().removeClass('edit');
    this.getNode().addClass('move');
  },

  stopMove: function () {

    this.getNode().removeClass('move');
    this.getNode().addClass('edit');
  }
};

sylma.xml.Editor = new Class(sylma.xml.EditorClass);