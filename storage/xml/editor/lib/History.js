
sylma.xml.History = new Class({

  Extends : sylma.ui.Container,
  steps : [],
  sendSpeed : 60000,
  sending : false,

  onLoad : function() 
  {
    var history = this;
    
    if (this.options.steps)
    {
      this.options.steps.reverse().each(function(step)
      {
        history.add('step', step);
      });
    }
  },

  addStep: function(type, path, content, args) 
  {
    var step = {
      type : type,
      path : path,
      update : new Date,
      content : content,
      arguments : JSON.stringify(args)
    };
    
    this.add('step', step);
    
    var steps = this.objects.step;
    var k = steps.length;
    var clear = false;
//console.log(steps, k);
    while (k)
    {
      k--;
//console.log(k, steps[k].disabled, steps[k])
      if (steps[k].disabled) 
      {
        steps[k].remove(); clear = true; 
      }
    }

    if (clear)
    {
      this.steps.push({
        type : 'clear'
      });
    }
    
    this.steps.push(step);
    this.save();
  },

  save: function() 
  {
    if (!this.sending && this.steps.length) 
    {
      this.sending = true;

      var editor = this.getParent('editor');

      var steps = this.steps;
      this.steps = [];

      this.send(this.options.pathUpdate, {
        steps : steps,
        file : editor.file,
        update : editor.updateTime
      }, function(response) {

        if (response.content) {

          console.info('File saved');
        }
        else {

          throw new Error('Bad request');
        }
        
        this.sending = false;

      }.bind(this));
    }

    window.setTimeout(this.save.bind(this), this.sendSpeed);
  },
  
  stepBackward : function()
  {
    var steps = this.objects.step;
    var k = steps.length;
    
    do
    {
      k--;
    }
    while (k && steps[k].disabled);

    if (steps[k]) steps[k].undo();
    else sylma.ui.showMessage('No step');
  },
  
  stepForward : function()
  {
    var steps = this.objects.step;
    var k = steps.length - 1;

    while (k >= 0 && steps[k].disabled)
    {
      k--;
    }

    k++;

    if (steps[k]) steps[k].redo();
    else sylma.ui.showMessage('No step');
  }
});