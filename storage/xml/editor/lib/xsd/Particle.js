
sylma.xsd.Particle = new Class({

  children : null,

  initialize : function(schema, datas) {

    this.schema = schema;
    this.load(datas);
  },

  load: function (datas) {

    this.element = datas.element;
    this.content = datas.content;

    this.prepareChildren();
  },

  prepareChildren: function () {

    this.children = this.schema.loadCollection(this.content);
    this.schema.prepare(this.children);
  },
});