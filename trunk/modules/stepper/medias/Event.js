sylma.stepper.Event = new Class({

  Extends : sylma.stepper.Step,

  //isgo : false,

  onReady : function() {

    var e = this.options.event;

    if (e) {

      this.options = {
        event : e,
        name : e.type,
        selector : [{
          target : e.target,
          frames : this.options.frames
        }]
      };
    }
  },

  onLoad : function() {

    var element = this.options.element;

    if (element) {

      this.add('selector', {element : element});
    }
  },

  go: function(callback) {

    var page = this.getParent('page'),
        steps = page.getSteps().tmp,
        length = steps.length;
/*
    if (this.getKey() === length - 1) {

      this.isgo = true;
    }
*/
    this.parent(callback);
  },

  test : function(callback) {

    var el = this.getSelector().getElement();

    if (el) {

      //if (!this.isgo) {

        this.log('Run');
        el.focus();
        el.click();
      //}

      //this.isgo = false;
    }
    else {

      this.hasError(true);
      console.log('cannot find ' + this.options.element);
    }

    callback && callback();
  },

  toJSON : function() {

    return {event : {
      '@name' : this.get('name'),
      '@element' : this.getSelector()
    }};
  }
});
