
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
      case 'move' : this.undoMove(); break;
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
      case 'move' : this.redoMove(); break;
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
    
    node.remove(false);
    
    history.steps.push({
      type : 'undo'
    });
      
    history.save();
  },
  
  applyAdd: function (type, add) {
    
    var history = this.getParent();
    var editor = this.getParent('editor');
    
    switch (this.arguments.type)
    {
      case 'element' :
        
        var path, position;
        
        if (add)
        {
          path = this.options.path;
          position = this.arguments.position;
        }
        else
        {
          path = this.options.path.split('/');
          position = path.pop();
          path = path.join('/');
        }

        var node = this.findNode(path);
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
      type : type
    });

    history.save();
  },
  
  redoAdd: function ()
  {
    this.applyAdd('redo', true);
  },
  
  undoRemove: function () 
  {
    this.applyAdd('undo', false);
  },
  
  redoRemove : function()
  {
    var history = this.getParent();
    var node = this.findNode(this.options.path);
    
    node.remove(false);
    history.steps.push({
      type : 'redo'
    });
    
    history.save();
  },

  applyMove: function (source, target, key, type) 
  {
    var node = this.findNode(source);
    node.applyMove(target, key - 0);

    this.getParent().steps.push({
      type : type
    });

    this.getParent().save();
  },
  
  undoMove: function ()
  {
    var position = this.arguments.position;
    var p = this.arguments.parent
    p += (p !== '/' ? '/' : '') + position;
    
    var target = this.options.path;

    var target = target.split('/');
    var key = target.pop();
    
    this.applyMove(p, target.join('/'), key, 'undo');
  },
  
  redoMove: function () 
  {
    var p = this.arguments.parent;
    var targetPath = (p !== '/' ? p + '/' : p) + this.arguments.position;
    var source = this.options.path;
    
    var target = targetPath.split('/');
    var key = target.pop();

    this.applyMove(source, target.join('/'), key, 'redo');
  },
  
  findNode: function (path, type)
  {
    return this.getParent('editor').findNode(path, type || this.arguments.type, this.arguments.name);
  },
  
  updateNode : function()
  {
    this.getNode().toggleClass('disabled', this.disabled);
  }
});