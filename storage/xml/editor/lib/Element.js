
sylma.xml.Element = new Class({

  Extends : sylma.xml.Node,

  onReady: function () {
//return;
    if (!this.sylma.template.classes) {

      var el = this.getParent('element');
//console.log(this.getParent('element').sylma, this.sylma);
      this.sylma = el.sylma;
      this.buildTemplate = el.buildTemplate.bind(this);
    }
  },

  add: function (alias, options) {

    var test = this.parent(alias, options);
console.log(test);
    return test;
  },
});