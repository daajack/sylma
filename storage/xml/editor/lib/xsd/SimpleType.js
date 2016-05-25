
sylma.xsd.SimpleType = new Class({

  Extends : sylma.xsd.Type,

  toElement : function() {

    var name = this.shortname;

    var element = this;
    var insert = this.schema.editor.getObject('insert');

    var result = new Element('div', {
      html : name,
      'class' : 'node text',
      events : {
        mousedown : function() {
          insert.addChild(element);
        }
      }
    });

    result.store('ref', this);

    return result;
  }

});