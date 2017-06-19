
sylma.xml.Step = new Class({
  
  Extends : sylma.ui.Template,
  
  onLoad: function () {
    
    this.arguments = JSON.parse(this.options.arguments);
    this.disabled = this.options.disabled === '1';
    
    this.updateNode();
  },
  
  addTo : function(node) {

    var el = this.initTemplate();

    el.inject(node.getParent(), 'top');

    this.initNode({node : el}, true);

    sylma.ui.loadArray([this]);
  },
  
  undo : function()
  {
    switch (this.options.type)
    {
      case 'add' : this.undoAdd(); break;
      case 'update' : this.undoUpdate(); break;
      case 'delete' : this.undoDelete(); break;
      case 'remove' : this.undoRemove(); break;
      default : throw new Error('Unknown step type');
    }
    
    this.disabled = true;
    this.updateNode();
  },
  
  redo : function()
  {
    switch (this.options.type)
    {
      case 'add' : this.redoAdd(); break;
      case 'update' : this.redoUpdate(); break;
      case 'delete' : this.redoDelete(); break;
      case 'remove' : this.redoRemove(); break;
      default : throw new Error('Unknown step type');
    }
    
    this.disabled = false;
    this.updateNode();
  },
  
  undoUpdate : function()
  {
    var history = this.getParent();
    var node = this.findNode(this.options.path);
    
    node.updateValue(this.arguments.previous, function()
    {
      history.steps.push({
        type : 'undo'
      });
      
      history.save();
    });
  },
  
  redoUpdate : function()
  {
    var history = this.getParent();
    var node = this.findNode(this.options.path);
    
    node.updateValue(this.options.content, function()
    {
      history.steps.push({
        type : 'redo'
      });
      
      history.save();
    });
  },
  
  undoAdd: function ()
  {
    var history = this.getParent();
    var node;
    
    switch (this.arguments.type)
    {
      case 'element' : node = this.findNode(this.options.path + '/' + this.arguments.position); break;
      case 'attribute' : node = this.findNode(this.options.path); break;
    }
//console.log(node, this.options.path);
    node.remove(false)
    history.steps.push({
      type : 'undo'
    });
      
    history.save();
  },
  
  redoAdd: function ()
  {
    this.undoRemove('redo');
  },
  
  undoRemove: function (type) 
  {
    var history = this.getParent();
    var editor = this.getParent('editor');
    
    switch (this.arguments.type)
    {
      case 'element' :
        
        var path = this.options.path.split('/');
        var position = path.pop();

        var node = this.findNode(path.join('/'));

        var doc = editor.parseDocument(this.options.content);
        var options = editor.buildElement(doc.documentElement);
        var child = node.addIndexedChild(options, this.arguments.type, parseInt(position));
        editor.schema.lookupElement(child, node.ref.type.children);

        break;
        
      case 'text' : throw new Error('Not implemented'); break;
      case 'attribute' : 
        
        var node = this.findNode(this.options.path, 'element');
        var child = node.addAttribute(this.arguments.namespace, this.arguments.name, this.arguments.prefix || '', this.options.content);
        var attribute = editor.schema.lookupAttribute(child, node.ref.type.children);
        editor.schema.attachAttribute(child, attribute);
        
        break;
    }
    
    history.steps.push({
      type : type || 'undo'
    });

    history.save();
  },
  
  redoRemove : function()
  {
    var history = this.getParent();
    var node = this.findNode(this.options.path);
    
    node.remove(false)
    history.steps.push({
      type : 'redo'
    });
    
    history.save();
  },
  
  findNode: function (path, type) {
    
    var result;
    var paths = path.split('/');
    paths.shift();
    
    var element = this.getParent('editor').getObject('container').getObject('document')[0].element;
console.log(paths);
    paths.each(function(path)
    {
console.log(path, element);
      element = element.children[path];
    });
    
    type = type || this.arguments.type;
    
    switch (type)
    {
      case 'element' : result = element; break;
      case 'text' : result = element.children[0]; break;
      case 'attribute' : result = element.attributes[this.arguments.name]; break;
      default : throw new Error('Unknown step type');
    }
    
    return result;
  },
  
  updateNode : function()
  {
    this.getNode().toggleClass('disabled', this.disabled);
  }
});