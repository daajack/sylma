
sylma.xsd.Attribute = new Class({

  Extends : sylma.xsd.Typed,

  toString : function () {

    return '@' + this.parent();
  },

});