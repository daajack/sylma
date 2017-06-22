
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
//console.log(node, this.options.path);
    node.remove(false)
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
  
  undoMove: function ()
  {
    var history = this.getParent();

    var node = this.findNode(this.options.path);
    var to = this.arguments.from.split('/');
    var from = this.options.path.split('/');
    var len = to.length;
    var k = 1;

    while (k < len)
    {
      if (to[k] < from[k])
      {
        to[k]++;
        break;
      }
      else if (to[k] > from[k])
      {
        break;
      }

      k++;
    }

    var position = to.pop();
    var parent = this.findNode(to.join('/'));
    var previous = parent.children[position];

    node.validateMove(parent, previous, false);

//    history.save();
  },
  
  findNode: function (path, type)
  {
    var result;
    var paths = path.split('/');
    paths.shift();
    
    var element = this.getParent('editor').getObject('container').getObject('document')[0].element;

    paths.each(function(path)
    {
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