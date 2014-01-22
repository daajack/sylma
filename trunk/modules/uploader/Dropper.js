
sylma.uploader.Dropper = new Class({

  Extends : sylma.crud.fieldset.Template,
  position : 1,

  initialize : function(props) {

    this.parent(props);
  },

  onLoad : function() {

    this.position = this.getParent('fieldset').getCount() + 1;
  },

  sendFile : function() {

    var form = this.getParent('uploader-container').getObject('uploader');

    var input = this.getInput();
    var clone = input.clone(true);

    clone.cloneEvents(input);
    this.prepareNodes(clone);

    input.grab(clone, 'after');
    form.getNode().grab(input);

    this.highlight();

    form.setPosition(this.position);
    form.set('callback', this.updateFile.bind(this));

    form.getNode().submit();
  },

  getInput : function() {

    return this.getNode().getElement('input');
  },

  updateFile : function(content) {

    var response = JSON.parse(content);

    sylma.ui.parseMessages(response);

    if (response.content) {

      var content = this.getParent('fieldset').getObject('content');

      sylma.ui.importNode(response.content).inject(content.getNode());

      var props = this.importResponse(response, this);

      content.initObject(props);

      this.position++;
    }
    else {

      sylma.ui.showMessage('No valid response');
      //throw new Error('No valid response');
    }

    this.getInput().set('value');
    this.downlight();
  },

  highlight : function() {

    this.getInput().set('disabled', true);
    this.getNode('loading').addClass('sylma-visible');
  },

  downlight : function() {

    this.getInput().set('disabled');
    this.getNode('loading').removeClass('sylma-visible');
  }

});
