
sylma.xml = {};

sylma.xml.Editor = new Class({

  Extends : sylma.ui.Container,
  
  namespaces : {},

  onLoad : function () {
    
    this.prepareDocument();
  },
  
  prepareDocument: function () {
    
    var container = this.getObject('container');

    var options = this.options.document;
    var doc = container.add('document', options);
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
  
  stepBackward : function()
  {
    var steps = this.getHistory().objects.step;
    steps[steps.length - 1].undo();
  },
  
  stepForward : function()
  {
    
  }
});