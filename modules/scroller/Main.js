
sylma.modules = sylma.modules || {};
sylma.modules.scroller = {};

sylma.modules.scroller.Main = new Class({

  Extends : sylma.html.Main,

  needUpdate : true,

  onLoad : function() {

    var delay = 200;

    var menus = $$(this.options.menus);
    var links = $$(this.options.links);
    var anchors = $$(this.options.anchors);

    this.anchors = this.loadAnchors(anchors);
    this.menus = this.loadMenus(menus, this.anchors);

    this.loadLinks(links.append(menus));

    var callback = function() {

      this.checkPosition();
      this.needUpdate = true;

    }.bind(this);

    window.addEvent('scroll', function() {

      if (this.needUpdate) {

        window.setTimeout(callback, delay);
        this.needUpdate = false;
      }

    }.bind(this));
    
    callback();
  },

  loadAnchors: function (nodes) {

    var anchors = {};

    nodes.each(function(anchor) {

      var name = anchor.getAttribute('name');

      anchors[name] = {
        node : anchor,
        //links : [],
        menu : null
      };
    });

    return anchors;
  },

  loadMenus: function (nodes, anchors) {

    var menus = {};

    nodes.each(function(node) {

      var name = node.get('href').replace(/\/?#/, '');
      var menu = {
        node : node
      };

      menus[name] = menu;

      if (anchors[name]) {

        anchors[name].menu = menu;
      }
    });

    return menus;
  },

  loadLinks : function(links, anchors) {

    var main = this;

    new Fx.SmoothScroll({
        links: links,
        wheelStops: false
    });

    links.each(function(link) {

      var name = link.getAttribute('href').replace(/\/?#/, '');

      link.store('name', name);
      //anchors[name].links.push(link);
    });

    var menus = this.menus;

    links.addEvent('click', function() {

      main.activateMenu(menus[this.retrieve('name')]);
    });

  },

  loadCurrent : function() {

    var hash = window.location.hash;

    if (hash) {

      var current = this.menus[hash];

      if (current) {

        current.node.click();
      }
    }
  },

  checkPosition : function() {

    var offset = Infinity;
    var windowY = window.getScroll().y;
    var current;

    Object.each(this.anchors, function(anchor) {

      var currentOffset = Math.abs(anchor.node.getPosition().y - windowY);

      if (currentOffset < offset) {

        current = anchor;
        offset = currentOffset;
      }
    });

    if (current) {

      this.activateMenu(current.menu);
    }
  },

  activateMenu : function(menu) {

    Object.each(this.menus, function(menu) {

      menu.node.getParent().removeClass('active');
    });

    if (menu) {

      menu.node.getParent().addClass('active');
    }
  }
});