
sylma.xsd.Type = new Class({

  initialize : function(schema, datas) {

    this.schema = schema;
    this.load(datas);
  },

  load: function (datas) {

    this.namespace = datas.namespace;
    this.name = datas.name;
    this.element = datas.element;
  },

  prepare: function () {

  },

  prepareChildren: function () {


  },
  
  toToken: function()
  {
    return this.namespace + ':' + this.name;
  }
});