
sylma.ui.Loader = new Class({

  Extends : sylma.ui.Container,

  loaderNode : null,

  options : {
    loader : {
      position : true,
      format : ''
    }
  },

  startLoading : function() {

    if (this.options.loader.format === 'mask') {

      this.startLoadingMask();
    }
    else {

      this.startLoadingDefault();
    }
  },

  startLoadingDefault : function() {

    var node = this.loaderNode;

    if (!node) {

      this.loaderNode = node = this.buildLoader();
      node.addClass('inside');
      this.getNode().grab(node, 'top');
    }

    node.addClass('active');
  },

  startLoadingMask : function() {

    var node = this.loaderNode;

    if (!node) {

      this.loaderNode = node = this.buildLoader();
    }

    this.getNode().grab(node, 'before');
    var el = this.getNode();

    var x = 0;
    var y = 0;

    if (this.options.loader.position !== '0') {

      x = el.getPosition().x;
      y = el.getPosition().y;
    }

    node.setStyles({
      width : el.getSize().x,
      height : this.getContainer().getSize().y,
      position: 'absolute',
      top : y,
      left : x
    });

    node.addClass('active');

    var mainNode = this.getNode();

    if (mainNode) {

      mainNode.addClass('loading');
    }
  },

  buildLoader : function() {

    return new Element('div', {
      'class' : 'loader'
    });
  },

  getContainer : function() {

    return this.getNode();
  },

  stopLoading: function() {

    if (this.options.loader.format === 'mask') {

      this.stopLoadingMask();
    }
    else {

      this.stopLoadingDefault();
    }
  },

  stopLoadingDefault: function() {

    var node = this.loaderNode;

    if (node) {

      node.removeClass('active');
    }
  },

  stopLoadingMask: function() {

    var node = this.loaderNode;

    if (node) {
      node.removeClass('active');
      node.setStyles({
        height : 0
      });
    }

    var mainNode = this.getNode();

    if (mainNode) {

      mainNode.removeClass('loading');
    }
  }
});