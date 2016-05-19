
sylma.xml.Document = new Class({

  Extends : sylma.xml.Node,

  onLoad : function() {

    this.init();
  },

  init: function () {

    this.element = this.objects.element[0];
  },

});