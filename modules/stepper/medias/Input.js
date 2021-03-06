sylma.stepper.Input = new Class({

  Extends : sylma.stepper.Event,

  onReady : function() {

    this.parent();

    var e = this.options.event;

    if (e && e.target.get('tag') === 'option') {

      this.options.selector[0].target = e.target.getParent();
    }
  },

  updateValue : function() {

    var val = this.getElement().get('value');

    this.getInput().set('value', val);
  },

  updateElement: function() {

    var val = this.parseVariables(this.getValue()).content;

    var el = this.getElement();

    if (!el) {
      
      this.addError('input', 'Element not found');
      this.log('Element not found');
    }
    else {

      el.set('value', val);
      el.fireEvent('input');
      el.fireEvent('change');
    }
  },

  getInput: function() {

    return this.getNode('input');
  },

  getValue : function() {

    return this.getInput().get('value');
  },

  test : function(callback) {

    this.log('Run');

    this.updateElement();

    callback && callback();
  },

  toJSON : function() {

    return {input : {
      '@element' : this.getSelector(),
      0 : this.getValue()
    }};
  }
});
