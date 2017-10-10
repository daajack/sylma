
sylma.xml.TextClass = {

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
    
    var step = {
      type : 'remove',
      path : this.toPath(),
      token : this.toToken(),
      content : previous,
      arguments :
      {
        type : 'text',
      }
    };

    var file = this.getParent('file');

    file.history.applyStep(this.getParent('document').document, step, step.arguments)

    if (previous)
    {
      file.history.addStep(step);
    }
    
    file.fireEvent('update');
    
    this.parent();
    this.destroy();
  },

  updateValue: function (value, callback, save) {
//    console.log('update');
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
      var step = {
        type : 'update',
        path : this.toPath(),
        token : this.toToken(),
        content : this.value,
        arguments :
        {
          type : 'text',
          previous : previous
        }
      };

      var file = this.getParent('file');

      if (callback) 
      {
        callback();
      }
      else if (save)
      {
        file.history.addStep(step);
      }

      file.history.applyStep(this.getParent('document').document, step, step.arguments)
      file.fireEvent('update');
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
};

sylma.xml.Text = new Class(sylma.xml.TextClass);