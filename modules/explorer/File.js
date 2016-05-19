
sylma.modules.explorer.File = new Class({

  Extends : sylma.ui.Template,

  onLoad : function() {
//console.log(this.options);
  },

  open : function(callback) {

    this.getParent('tree').openFile(callback, this.get('path'), this.options.extension);
  }
});