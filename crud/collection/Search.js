
sylma.crud.collection.Search = new Class({

  Extends: sylma.ui.Container,
  
  onLoad : function()
  {
    this.input = this.getNode('input');
  },
  
  setValue: function (val) {

    this.input.set('value', val);
  },

  getValue: function () {

    return this.input.get('value');
  },

  update : function() {

    this.getParent('table').update(true, true);
  },

  clear : function() {

    var val = this.getValue();

    this.setValue('');

    if (val) {

      this.update();
    }
  }
});