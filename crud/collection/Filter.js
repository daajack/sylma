
sylma.crud.collection.Filter = new Class({

  Extends: sylma.ui.Container,
  empty: false,
  currentOperator : 0,
  template : true,

  initialize : function(props) {

    this.parent(props);
    this.props = props;

    this.prepare();
  },

  prepare: function () {

    var inputs = this.getNode().getElements('input, select');

    this.operator = inputs[0];
    this.input = inputs[1];

    this.input.set('name');
  },

  init: function (name, position) {

    this.setValue();
    this.setName(name, position);
    this.setOperator();
  },

  setName: function (name, position) {

    name = name + '[' + position + ']';

    this.operator.set('name', name + '[operator]');
    this.input.set('name', name + '[value]');
  },

  setValue: function (val) {

    this.input.set('value', val);
  },

  getValue: function () {

    return this.input.get('value');
  },

  setOperator: function (val) {

    var ops = this.getParent().operators;

    if (!val) {

      val = ops.pick();
    }

    this.currentOperator = ops.indexOf(val);

    if (this.currentOperator === -1) {

      throw 'Unknown operator';
    }

    this.getNode('operator_display').set('html', val);
    this.getNode('operator').set('value', val);
  },

  toggleOperator: function () {

    var val = this.currentOperator;
    var ops = this.getParent().operators;

    val++;

    if (val >= ops.length) {

      val = 0;
    }

    this.setOperator(ops[val]);

    if (this.getValue()) {

      this.update();
    }

    this.input.focus();
  },

  update : function() {

    this.getParent('table').update(true, true);
    this.setEmpty(!this.getValue());
  },

  enter: function () {

    if (this.leaveCallback) {

      window.clearTimeout(this.leaveCallback);
    }
  },

  leave: function () {

    if (!this.getValue()) {

      this.leaveCallback = this.unset.delay(1000, this);
    }
  },

  setEmpty: function (val) {

    this.getNode().toggleClass('empty', val);
  },

  clear : function() {

    var val = this.getValue();

    this.setValue('');

    if (val) {

      this.update();
    }

    this.unset();
  },

  unset: function () {

    if (!this.getParent('filters').isShowed()) {

      this.hide();
    }

    if (this.getParent().count > 1) {

      this.getParent().removeFilter(this);
      this.hide();
      this.remove.delay(1000, this);
    }
  }
});