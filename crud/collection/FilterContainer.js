
sylma.crud.collection.FilterContainer = new Class({

  Extends : sylma.ui.Container,
  operators : [],
  count : 0,

  onLoad: function () {

    this.template = this.tmp.pick();

    if (this.options.operators) {

      this.operators = this.options.operators;
    }
  },

  updateSize : function(width) {

    this.getNode().setStyle('width', width);
  },

  init: function () {

    if (this.template && !this.count) {

      var result = this.buildFilter();
    }
  },

  setValues: function (vals) {

    var children = vals[0].children;

    if (typeOf(children) === 'object') {

      children = Object.values(children);
    }

    children.each(function(val) {

      if (val.value) {

        var filter = this.buildFilter();

        filter.setValue(val.value);
        filter.setOperator(val.operator);
        filter.show();
      }
    }, this);
  },

  buildFilter: function () {

    var result = this.template.clone();
    result.getNode().removeClass('template');
    result.template = false;

    this.getNode().getFirst().grab(result.getNode(), 'after');
    this.tmp.push(result);

    var name = this.options.name + '[0][children]';
    var position = this.tmp.length - 2;

    result.init(name, position);

    this.count++;

    return result;
  },

  addEmptyFilter: function () {

    var result = this.buildFilter();
    var node = result.input;

    result.show();
    node.focus.delay(200, node);

    return result;
  },

  addFilter: function (val, show, op) {

    var filter = this.buildFilter();

    filter.setValue(val);
    filter.setOperator(op);

    if (show) {

      filter.show();
      filter.input.focus.delay(200, filter.input);
    }
  },

  removeFilter: function (filter) {

    this.count--;
  },
});