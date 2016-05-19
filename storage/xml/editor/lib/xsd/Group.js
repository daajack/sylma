
sylma.xsd.Group = new Class({

  children : null,

  initialize : function(schema, datas) {

    this.schema = schema;
    this.load(datas);
  },

  load: function (datas) {

    this.element = datas.element;
    this.namespace = datas.namespace;
    this.ref = datas.ref;
    this.name = datas.name;
    this.source = datas.source;
    this.content = datas.content;
  },

  prepare: function () {

    if (!this.children) {

      this.prepareChildren();
    }
  },

  prepareChildren: function () {

    if (this.ref) {

      var ref = this.schema.find(this.element, this.namespace, this.name);
      ref.prepare();

      this.ref = ref;
      this.children = ref.children;
    }
    else {

      this.children = this.schema.loadCollection(this.content);
      this.schema.prepare(this.children);
    }
  },
});