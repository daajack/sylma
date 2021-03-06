
sylma.modules = sylma.modules = {};
sylma.modules.explorer = sylma.modules.explorer = {};

sylma.modules.explorer.Explorer = new Class({

  Extends : sylma.ui.Template,

  location : null,

  onLoad : function() {

    //this.location = this.parseLocation(location.href);
    this.getObject('sidebar').update(null, {
      data : {
        file : this.options.file,
        dir : this.options.dir
      }
    });
  },
  
  open: function (path, extension)
  {
    var view = this.getObject('view');

    Object.each(view.objects, function(obj) {

      obj.getNode().removeClass('open');
    });

    var container;
    
    switch (extension) {

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

    if (container) 
    {
      view.startLoading();

      container.update(function() {

        container.getNode().addClass('open');
        view.stopLoading();

      }, {
        data : {
          file : path
        }
      });
    }
    else 
    {
      sylma.ui.showMessage('No editor defined');
    }
  },

  updateLocation : function(args) {

    for (var key in args) {

      this.location[key] = args[key];
    }

    location.search = this.stringifyLocation(this.location);
  },

  /**
   * from https://github.com/sindresorhus/query-string
   */
  parseLocation : function (str) {

    if (typeof str !== 'string') {
        return {};
    }

    str = str.trim().replace(/^(\?|#)/, '');

    if (!str) {
        return {};
    }

    return str.trim().split('&').reduce(function (ret, param) {
        var parts = param.replace(/\+/g, ' ').split('=');
        var key = parts[0];
        var val = parts[1];

        key = decodeURIComponent(key);
        // missing `=` should be `null`:
        // http://w3.org/TR/2012/WD-url-20120524/#collect-url-parameters
        val = val === undefined ? null : decodeURIComponent(val);

        if (!ret.hasOwnProperty(key)) {
            ret[key] = val;
        } else if (Array.isArray(ret[key])) {
            ret[key].push(val);
        } else {
            ret[key] = [ret[key], val];
        }

        return ret;
    }, {});
  },

  /**
   * from https://github.com/sindresorhus/query-string
   */
  stringifyLocation : function (obj) {

    return obj ? Object.keys(obj).map(function (key) {
        var val = obj[key];

        if (Array.isArray(val)) {
            return val.map(function (val2) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(val2);
            }).join('&');
        }

        return encodeURIComponent(key) + '=' + encodeURIComponent(val);
    }).join('&') : '';
  }
});