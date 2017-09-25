
sylma.xsd.ComplexType = new Class({

  Extends : sylma.xsd.Type,
  children : null,
  datas : null,

  load: function (datas) {

    this.parent(datas);
    this.content = datas.content;
    this.mixed = datas.mixed;
    this.datas = datas.base;
  },

  prepare: function () 
  {

    if (!this.children) 
    {
      this.children = true;
      
      var children = [];
      var base = this.datas;

      if (base) {

        var type = this.schema.findType(base.namespace, base.name);
        type.prepare();
        
        this.base = type;
      }
      
      children.push.apply(children, this.schema.loadCollection(this.content));

      this.children = children;
    }
  },

  prepareChildren: function () {
    
    if (this.base && !this.baseReady) 
    {
      this.baseReady = true;

      var collection = [];
      
      collection.push.apply(collection, this.base.children);
      collection.push.apply(collection, this.children);

      this.children = collection;
    }
  },
});