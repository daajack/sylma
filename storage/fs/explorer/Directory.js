
sylma.storage.fs.explorer.Directory = new Class({

  Extends : sylma.ui.Template,

  onLoad : function() {

  },

  open : function() {

    this.getParent('tree').updateJSON({
      dir : this.get('path')
    }, this.getNode().get('html') === '..');
  }
});