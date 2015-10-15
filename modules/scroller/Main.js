
sylma.modules = sylma.modules || {};
sylma.modules.scroller = {};

sylma.modules.scroller.Main = new Class({

  Extends : sylma.html.Main,

  onLoad : function() {

    this.loadLinks(this.getMenuItems());
  },

  getMenuItems : function() {

    return $$(this.get('menu'));
  },

  loadLinks : function(links) {

    var main = this;

    new Fx.SmoothScroll({
        links: links,
        wheelStops: false
    });

    links.addEvent('click', function() {

      main.activateMenu(this);
    });

    var hash = window.location.hash;

    if (hash) {

      var current = $$('a[href=' + hash + ']');

      if (current.length) {

        current[0].click();
      }

    }
  },

  activateMenu : function(el) {

    this.getMenuItems().each(function(link) {

      link.getParent().removeClass('active');
    });

    if (el) {

      var item = el.getParent();
      item.addClass('active');
    }
  }
});