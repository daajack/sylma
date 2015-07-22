
sylma.modules.explorer.Tree = new Class({

  Extends : sylma.ui.Container,

  onLoad : function() {

    this.add('root', this.get('datas'));
  },

  updateJSON : function(args) {

    this.send(this.get('path'), args, function(response) {

      this.getObject('root').pick().remove();
      this.add('root', response.content);

    }.bind(this), true);
    //this.getParent('explorer').updateLocation(args);
  }
});