
sylma.xml = {};

sylma.xml.Editor = new Class({

  Extends : sylma.ui.Container,

  onLoad : function () {

    var container = this.getObject('container');

    var options = this.options.document;
    var doc = container.add('document', options);

    var root = this.options.schemas.root;

    var schema = new sylma.xsd.Schema(root, this.options.namespaces);
    schema.validate(doc);

    this.schema = schema;
    this.file = this.options.file;
    this.updateTime = this.options.update;
  },

});