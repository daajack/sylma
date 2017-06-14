
sylma.xml.Step = new Class({
  
  Extends : sylma.ui.Template,
  
  onLoad: function () {
    
    this.arguments = JSON.parse(this.options.arguments);
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
      case 'add' : this.revertAdd(); break;
      case 'update' : this.revertUpdate(); break;
      case 'delete' : this.revertDelete(); break;
    }
  },
  
  revertUpdate : function()
  {
    var history = this.getParent();
    var el = this.findElement().children[0];
    
    el.updateValue(this.arguments.previous, function()
    {
      var step = {
        type : 'revert'
      };
      
      history.steps.push(step);
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
  }
});