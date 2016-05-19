
sylma.xsd.Annotation = new Class({

  initialize : function(schema, datas) {

    this.schema = schema;
    this.load(datas);
  },

  load: function (datas) {

    this.element = datas.element;
    this.content = datas.content;
  },
});