
sylma.xml.Text = new Class({

  Extends : sylma.xml.Node,
  type : 'text',

  onLoad: function () {

    this.element = 'text';
    this.parentElement = this.getParent();
    this.value = this.getNode().get('html');
  },

  getPosition: function () {

    return this.parentElement.children.indexOf(this);
  },

  openValue : function (callback) {

    this.getParent('editor').getObject('update').attachNode(this, this.getNode(), callback);
  },

  remove : function () {

    var path = this.parentElement.toPath(true);

    this.getParent('editor').getObject('history').addStep('remove', path, '', {
      type : 'text',
      position : this.getPosition()
    });

    this.parent();
    this.destroy();
  },

  updateValue: function (value, callback, save) {
    
    var previous = this.value;
    save = save === undefined ? true : save;
    
    if (value === previous)
    {
      callback && callback();
      return;
    }
    
    this.value = value;
    this.getNode().set('html', value);

    if (!value) 
    {
      this.remove();
    }
    else
    {
      if (callback) 
      {
        callback();
      }
      else if (save)
      {
        var editor = this.getParent('editor');
        var path = this.parentElement.toPath(true);

        editor.getObject('history').addStep('update', path, this.value, {
          previous : previous,
          type : 'text',
          position : this.getPosition()
        });
      }
    }
  },

  toXML: function () {

    return this.value;
  },
});