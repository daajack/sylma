
sylma.xml.FileClass = {

  Extends : sylma.ui.Container,
  Implements: Events,
  
  documents : [],
  updateTime : 1,

  onLoad : function() 
  {
    this.container = this.getObject('container');
  
    var history = this.getObject('history');
    this.history = history;
    
    window.addEvent('unload', function() {

      history.save();
    });
  },
  
  prepare : function(schema)
  {
    this.path = this.options.path;
    this.schema = schema;
    
    this.current = this.prepareDocument(this.options.document, schema, true);
  },

  prepareDocument: function (content, schema, parse) {
    
    var doc = this.buildDocument(content, parse);
    
    schema.validate(doc);
    
//    this.updateTime = this.options.update;
//    this.setReady();
    
    return doc;
  },
  
  buildDocument : function(options, parse) {
    
    var container = this.container;
    var doc;
    var editor = this.getParent('editor');
    
    if (parse)
    {
      var parser = new DOMParser();
      doc = parser.parseFromString(options, "text/xml");
    }
    else
    {
      doc = options;
    }
    
    var content = {element : [editor.buildElement(doc.documentElement)], document : doc};
    
    this.documents.each(function(document)
    {
      document.hide();
    });

    var result = container.add('document', content);

    this.documents.push(result);

//    this.setReady();
    
    return result;
  },
  
  hideDocuments: function ()
  {
    this.documents.each(function(doc)
    {
      doc.hide();
    });
  },
  
  openDocument: function (document)
  {
    this.hideDocuments();
    this.current = document;
    
    document.show();
    this.fireEvent('update');
  },
  
  findNode: function (path, type, name, prefix)
  {
    var result;
    var paths = path.split('/');
//console.log(path, paths);
    paths.shift();

    var element = this.current.element;

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
  },
  
  publish: function ()
  {
    var compiler = new window.titan.Compiler;
    var document = this.current.options.document;
    var editor = this.getParent('editor');
    var file = this.path;
    
    compiler.prepareDOM(document, 1, function(result)
    {
      editor.send(editor.options.publishPath, {
        file : file,
        scripts : result.map(function(window) { return { name : window.name, content : window.content }})
      }, function()
      {
        sylma.ui.showMessage('Document published');
      });
    });
  }

}

sylma.xml.File = new Class(sylma.xml.FileClass);