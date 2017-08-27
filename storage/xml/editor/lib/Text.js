
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

  remove : function (previous) {

    if (previous)
    {
      this.getParent('editor').getObject('history').addStep('remove', this.toPath(), this.toToken(), previous, {
        type : 'text',
      });
    }
    
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
      this.remove(previous);
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

        editor.getObject('history').addStep('update', this.toPath(), this.toToken(), this.value, {
          previous : previous,
          type : 'text',
        });
      }
    }
  },

  toPath : function () {

    var el = this.parentElement;
    var position = el.children.indexOf(this);

    return el.toPath() + position;
  },
  
  toToken : function()
  {
    return this.parentElement.toToken() + '/text()';
  },

  toXML: function () {

    return this.value;
  },
});