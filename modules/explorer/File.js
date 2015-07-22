
sylma.modules.explorer.File = new Class({

  Extends : sylma.ui.Template,

  onLoad : function() {
//console.log(this.options);
  },

  open : function(callback) {

    var explorer = this.getParent('explorer');
    var view = explorer.getObject('view');
    var node = this.getNode();

    node.addClass('open');
//console.log(this.get('path'));
    switch (this.options.extension) {

      case 'php' :

        view.update(function() {

          node.removeClass('open');
          callback && callback();
          
        }, {
          url : explorer.get('inspector') + '.json',
          data : {
            file : this.get('path')
          }
        });

        break;

      default :


    }
  }
});