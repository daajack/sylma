
sylma.ui.tab.Tab = new Class({

  Extends : sylma.crud.Group,

  width : 0,
  position : 0,
  obsolete : false,

  initialize : function(options) {

    this.parent(options);
    this.getNode().addClass('sylma-tab');
  },

  prepare : function(width, position) {

    this.width = width;
    this.position = position;

    this.prepareNode();
  },

  prepareNode : function() {

    this.getNode().setStyle('width', this.width);
  },

  updateSuccess : function(response, callback) {

    this.parent(response, callback);
    this.prepareNode();
  },

  needUpdate : function(val) {

    if (val !== undefined) {

      this.obsolete = val;
    }

    return this.obsolete || (this.get('path') && !this.getNode().getChildren().length);
  },

  show : function(callback) {

    var tabs = this.getParent('tabs');
    
    tabs.startLoading();

    var callback2 = function() {
      
      tabs.stopLoading();
      this.getNode().addClass('active');
      callback && callback();

    }.bind(this);

    if (this.needUpdate()) {

      this.obsolete = false;
      this.updateDeprecated(this.get('arguments'), this.get('path'), undefined, callback2);
    }
    else {

      callback2();
    }
  },

  go : function(callback) {

    this.getParent('tabs').go(this.getKey(), callback);
  },

  hide : function() {

    this.getNode().removeClass('active');
  },

  getCaller : function() {

    return this.getParent(1).getHead().getCaller(this.position);
  },

});
