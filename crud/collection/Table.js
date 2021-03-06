sylma.crud = sylma.crud || {};
sylma.crud.collection = sylma.crud.collection || {};

sylma.crud.collection.Table = new Class({

  Extends : sylma.crud.Form,

  updating : false,
  obsolete : false,
  delay : 200,

  onLoad : function()
  {
    if (this.options.show) {

      this.toggleShow();
    }
    
    this.updateHead();
  },
  
  updateHead: function () {

    var button = this.getObject('head').getNode('filter_toggle');
    button.setStyle('height', button.getParent().getSize().y);
    button.setStyle('width', button.getParent().getSize().x);
  },

  update : function(reset, delay) {

    if (this.updating) {

      this.obsolete = true;
    }
    else {

      window.clearTimeout(this.updater);

      delay = delay ? this.delay : 0;

      this.updater = function() {

        this.updating = true;

        if (reset) {

          var pager = this.getObject('container').getObject('pager');
          
          if (pager) {

            pager.setPage(1);
          }
        }

        this.submit();

      }.delay(this.delay, this);
    }
  },
  
  toggleShow: function (el, val) {
    
    this.getObject('filters').toggleShow(el, val);
    
    var val = this.parent(el, val);
  },
  
  submitReturn : function(response) {

    this.getObject('container').updateSuccess(response);
    this.hideMask();

    this.updating = false;

    if (this.obsolete) {

      this.obsolete = false;
      this.update(true);
    }
  },

  getInputs : function() {

    return this.getObject('container').getNode().getElements('input, select, textarea');
  },
});

