
sylma.crud.foreign.Filter = new Class({

  Extends: sylma.crud.collection.Filter,

  prepare: function () {

    this.input = this.getNode().getElements('input, select').getLast();
  },

  init: function (name, position) {

    //this.setEmpty(!this.getValue());
  },

  setValue: function (val) {

    this.input.set('checked', val ? 'checked' : null);
    this.setEmpty(!this.getValue());
  },

  getValue: function () {

    return this.input.get('checked');
  },

  getKey: function () {

    return this.getNode().get('data-key');
  },

  update: function () {

    this.parent();

    if (this.getValue()) {

      this.show();
    }
    else {

      this.hide();
    }

    this.getParent().update();
    //this.leave();
  },

  unset: function () {

    //this.setEmpty(true);
    this.hide();
  }
});