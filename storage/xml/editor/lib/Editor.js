
sylma.xml = {};

sylma.xml.Editor = new Class({

  Extends : sylma.ui.Container,

  onLoad : function () {

    var options = this.options.document;
    var doc = this.add('document', options);
  }

});