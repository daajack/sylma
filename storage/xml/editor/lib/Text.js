
sylma.xml.Text = new Class({

  Extends : sylma.xml.Node,
  type : 'text',

  onLoad: function () {

    this.element = 'text';
    this.parentElement = this.getParent();
    this.value = this.getNode().get('html');
  },

  openValue : function (callback) {

    this.getParent('editor').getObject('update').attachNode(this, this.getNode(), callback);
  },

  remove : function () {

    var path = this.parentElement.toPath(true);

    this.getParent('editor').getObject('history').addStep('remove', path, '', {
      type : 'text',
      //position : this.parentElement.children.indexOf(this)
    });

    this.parent();
    this.destroy();
  },

  updateValue: function (value) {

    if (!value) {

      this.remove();
    }
    else {

      var editor = this.getParent('editor');
      var path = this.parentElement.toPath(true);

      this.value = value;
      this.getNode().set('html', value);

      editor.getObject('history').addStep('update', path, this.value, {
        type : 'text',
      });
    }
  },

  toXML: function () {

    return this.value;
  },
});