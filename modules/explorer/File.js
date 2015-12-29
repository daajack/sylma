
sylma.modules.explorer.File = new Class({

  Extends : sylma.ui.Template,

  onLoad : function() {
//console.log(this.options);
  },

  open : function(callback) {

    var explorer = this.getParent('explorer');
    var node = this.getNode();
    var view = explorer.getObject('view');

    Object.each(view.objects, function(obj) {

      obj.getNode().removeClass('open');
    });

    node.addClass('loading');

    var container;

    switch (this.options.extension) {

      case 'php' :

        container = view.getObject('tab-inspector');

        break;

      case 'crd' :
      case 'vml' :
      case 'tpl' :
      case 'xml' :
      case 'sml' :
      case 'tml' :
      case 'xql' :
      case 'xsd' :

        container = view.getObject('tab-editor');

        break;

      default :


    }

    if (container) {

      container.update(function() {

        container.getNode().addClass('open');
        node.removeClass('loading');
        callback && callback();

      }, {
        data : {
          file : this.get('path')
        }
      });
    }
    else {

      sylma.ui.showMessage('No editor defined');
    }
  }
});