
sylma.xml.Attribute = new Class({

  Extends : sylma.xml.Node,

  onLoad: function () 
  {
    this.prepare();
  },

  prepare: function () 
  {
    this.sylma.splice = true;

    this.element = 'attribute';
    this.name = this.options.name;
    this.prefix = this.options.prefix;
    this.namespace = this.prefix ? this.options.namespace : '';
    this.shortname = (this.prefix ? this.prefix + ':' : '') + this.name;
    this.value = this.options.value;
    this.parentElement = this.getParent();

    this.node = this.getNode();
  },

  openValue : function (callback) 
  {
    this.getParent('editor').getObject('update').attachNode(this, this.getNode('value'), callback);
  },

  remove : function (save) 
  {
    save = save === undefined ? true : save;
    
    if (save)
    {
      var path = this.parentElement.toPath(true);
      
      this.getParent('editor').getObject('history').addStep('remove', path, this.toToken(), this.value, {
        type : 'attribute',
        namespace : this.namespace,
        prefix : this.prefix,
        name : this.name,
      });
    }

    this.parent();
    this.destroy();
  },

  updateValue: function (value, callback) 
  {
    var previous = this.value;
    
    if (value === previous)
    {
      callback && callback();
      return;
    }
    
    if (!value) {

      this.remove();
    }
    else 
    {
      this.value = value;
      this.getNode('value').set('html', value);

      if (callback) 
      {
        callback();
      }
      else 
      {
        var editor = this.getParent('editor');
        var path = this.parentElement.toPath(true);

        editor.getHistory().addStep('update', path, this.toToken(), this.value, {
          type : 'attribute',
          namespace : this.namespace,
          name : this.name,
          prefix : this.prefix,
          previous : previous
        });
      }
    }
  },

  toString: function () 
  {
    var prefix = this.prefix ? this.prefix + ':' : '';

    return prefix + this.name + '="' + this.value + '"';
  },

  toToken : function()
  {
    var prefix = this.prefix ? this.prefix + ':' : '';
    return this.parentElement.toToken() + '@' + prefix + this.name;
  },
});