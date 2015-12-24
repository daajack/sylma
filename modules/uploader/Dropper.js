
sylma.uploader.Dropper = new Class({

  Extends : sylma.ui.Container,

  onLoad : function() {

    var fieldset = this.getParent('fieldset');

    if (fieldset) {

      fieldset.addEvent('remove', this.checkCount.bind(this, true));
      this.checkCount();
    }
  },

  checkCount: function (remove) {

    //if (!this.options.max) return;

    var fieldset = this.getParent('fieldset');
    var count = fieldset.getCount();

    if (remove) count--;

    if (count >= this.options.max) {

      this.hide();
    }
    else {

      this.show();
    }
  },

  getMain : function() {

    return this.getParent('uploader-container');
  },

  sendFile : function() {

    var input = this.getInput();
    var clone = input.clone(true);

    clone.cloneEvents(input);
    this.prepareNodes(clone);

    input.grab(clone, 'after');

    input.dispose();

    this.highlight();

    this.getMain().sendFile(this.getParent('fieldset'), input, this.options.action);
  },

  getInput : function() {

    return this.getNode().getElement('input');
  },

  sendComplete : function() {

    this.getInput().set('value');
    this.downlight();

    this.checkCount();
  },

  highlight : function() {

    this.getInput().set('disabled', true);
    this.getNode().addClass('loading');
  },

  downlight : function() {

    this.getInput().set('disabled');
    this.getNode().removeClass('loading');
  }

});
