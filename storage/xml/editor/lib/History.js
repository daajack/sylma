
sylma.xml.History = new Class({

  Extends : sylma.ui.Container,
  steps : [],
  sendSpeed : 60000,
  sending : false,

  onLoad : function() {

    this.save();
  },

  addStep: function(type, path, content, args) {

    var step = {
      type : type,
      path : path,
      content : content,
      arguments : JSON.stringify(args),
    };

    this.steps.push(step);
    this.save();
  },

  save: function() {
console.log('check steps');
    if (!this.sending && this.steps.length) {

      this.sending = true;

      var editor = this.getParent('editor');

      var steps = this.steps;
      this.steps = [];

      this.send(this.options.path, {
        steps : steps,
        file : editor.file,
        update : editor.updateTime
      }, function(response) {

        if (response.content) {

          console.info('File saved');

          this.sending = false;
        }
        else {

          console.info('Error when sending');
        }

      }.bind(this));
    }

    window.setTimeout(this.save.bind(this), this.sendSpeed);
  }

});