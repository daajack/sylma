
sylma.modules.explorer.Root = new Class({

  Extends : sylma.modules.explorer.Directory,

  onLoad : function() {

    this.show.delay(100, this);
  }
});