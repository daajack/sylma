
sylma.storage.fs.explorer.Root = new Class({

  Extends : sylma.storage.fs.explorer.Directory,

  onLoad : function() {

    this.show.delay(100, this);
  }
});