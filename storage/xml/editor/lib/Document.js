
sylma.xml.Document = new Class({

  Extends : sylma.xml.Node,

  onReady : function() {
    
    //this.models = {};
    
//    var parser = new DOMParser();
//    var doc = parser.parseFromString(this.options.content, "text/xml");
//console.log(doc);
    this.elementTemplate = this.buildObject('element');
    this.objects.element.shift();
  },
  
  getModel : function(key) {

    if (!this.models[key]) {
      
      this.models[key] = this.add(key);
    }
    console.log(this.models, key);
    return this.models[key];
  },
  
  onLoad : function() {
    
    this.init();
  },

  init: function () {

    this.element = this.objects.element[0];
  }
});