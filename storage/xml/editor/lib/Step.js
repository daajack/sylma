
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
    }
    
    this.disabled = false;
    this.updateNode();
  },
  
  undoUpdate : function()
  {
    var history = this.getParent();
    var el = this.findElement().children[0];
    
    el.updateValue(this.arguments.previous, function()
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
    var el = this.findElement().children[0];
    
    el.updateValue(this.options.content, function()
    {
      history.steps.push({
        type : 'redo'
      });
      
      history.save();
    });
  },
  
  findElement: function () {
    
    var paths = this.options.path.split('/');
    paths.shift();
    
    var element = this.getParent('editor').getObject('container').getObject('document')[0].element;

    paths.each(function(path)
    {
      element = element.children[path];
    });
    
    return element;
  },
  
  updateNode : function()
  {
    this.getNode().toggleClass('disabled', this.disabled);
  }
});