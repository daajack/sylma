
sylma.xsd.Any = new Class({

  initialize : function(schema, datas) {

    this.schema = schema;
    this.load(datas);
  },

  load: function (datas) {

    this.element = datas.element;
    this.namespace = datas.namespace;
  },
});