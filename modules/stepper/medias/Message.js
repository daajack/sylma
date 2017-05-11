sylma.stepper.Message = new Class({

  Extends : sylma.stepper.Step,
  
  onLoad : function()
  {
    if (!this.options.value)
    {
      var message = this.getMessage();
      
      this.options.value = message ? message.get('html') : '';
      this.updateValue();
    }
  },
  
  getValue : function() {

    return this.getNode('value').get('value');
  },
  
  updateValue: function () {
    
    this.getNode('value').set('value', this.options.value);
  },
  
  getMessage : function()
  {
    var messages = this.getWindow().document.body.getElement('#sylma-messages');
    var message;

    if (messages) 
    {
      message = messages.getChildren()[0];
    }
    
    if (!message)
    {
      this.addError('message', 'Cannot find message');
      this.log('Cannot find message');
    }
    
    return message;
  },

  refresh: function() {

    var message = this.getMessage();
    
    if (message)
    {
      this.getNode('value').set('value', message.get('value'));
    }
    else
    {
      this.getNode('value').set('value', '');
    }
  },
  
  test : function(callback) {
    
    var message = this.getMessage();

    if (message && message.get('html') !== this.getValue())
    {
      this.addError('message', 'Incorrect message');
      this.log('Incorrect message');
    }
    
    callback && callback();
  },

  toJSON : function() {

    return {message : {
      0 : this.getValue()
    }};
  },

  asToken: function() {

    return this.getAlias() + '(' + this.getKey() + ')';
  }
});