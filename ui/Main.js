/**
 *
 */

var sylma = sylma || {};

sylma.debug = {
  log : false
}

sylma.modules = {};

sylma.binder = {
  classes : {},
  objects : {}
};

sylma.uiClass = {

  roots : [],
  windowLoaded : false,
  uID : 1,
  // log value
  objectCount : 0,

  cookie : {

    name : 'sylma-main'
  },

  tmp : {},

  load : function(parent, objects) {

    this.loadMessages();
    this.buildClasses(sylma.binder.classes);
    this.loadObjects(parent, objects);

    if (sylma.debug.log) {

      console.log(this.objectCount + ' objects created');
    }
  },

  buildClasses: function (classes) {

    Object.each(classes, function(item, key) {

      this.buildClass(classes, item, key);

    }, this);
  },

  buildClass: function (classes, item, key) {

    if ('Extends' in item.props && !item.props.Extends) {

      this.showMessage('An error has occured');
      throw 'Cannot find class : ' + item.extends;
    }

    classes[key] = new Class(item.props);
  },

  loadObjects : function(parent, objects) {

    if (parent && objects) {

      var length = Object.getLength(objects);

      if (length > 1) {

        this.loadMultiple(objects, parent);
      }
      else {

        this.loadOne(objects, parent);
      }
    }
  },

  loadPath : function(path) {

    var result = window;
    var lastPath = 'window';
    var error = false;

    path.split('.').some(function(item) {

      result = result[item];

      if (!result) {

        error = true;
      }

      lastPath += '.' + item;
    });

    return error ? false : result;
  },

  loadMultiple : function(objects, parent) {

    var result;

    for (var obj in objects) {

      result = this.createObject(objects[obj]);
      parent[obj] = result;

      this.loadResult(result);
    }
  },

  extractFirst : function(object) {

    for (var result in object) return result;
  },

  loadOne : function(objects, parent) {

    var first = this.extractFirst(objects);
    var result = this.createObject(objects[first]);

    parent[first] = result;
    this.loadResult(result);
  },

  loadResult : function(result) {

    if (this.windowLoaded) {

      this.loadArray([result]);
    }
    else {

      this.roots.push(result);
    }
  },

  createObject : function(props) {

    this.objectCount++;

    if (!props.extend) {

      sylma.log(props);
      throw new Error('No path defined');
    }

    if (sylma.debug.log) {

      sylma.log('Create object : key=' + (props.sylma ? props.sylma.key : '[unknown]') + ' id=' + props.id);
    }

    var parent = this.loadPath(props.extend);

    if (!parent) {

      var path;

      if (props.source) {

        path = props.source.match('@file (.+$)')[1];
      }
      else {

        path = 'id : ' + props.id;
      }

      throw new Error('Cannot create object from : ' + path + ', class does not exist');
    }

    var result = new parent(props);

    return result;
  },

  onWindowLoad : function() {

    this.windowLoaded = true;
    this.loadArray(this.roots);
  },

  loadArray : function(objs) {

    var obj, len = objs.length;

    for (var i = 0; i < len; i++) {

      obj = objs[i];

      if (!obj.windowLoaded) {

        obj.windowLoaded = true;

        if (obj.onWindowLoad) obj.onWindowLoad();
        if (obj.onLoad) obj.onLoad();
        if (obj.fireEvent) obj.fireEvent('load');
      }
    }
  },

  importNode : function(val, name, root) {

    name = name || 'div';

    var el = new Element(name, {
      html : val
    });

    return root ? el : el.getChildren();
  },

  objectToString : function(val) {

    return JSON.stringify(val);
  },

  stringToObject : function(val) {

    return JSON.parse(val);
  },
/*
  arrayToObject : function(val) {

    var result = {};

    val.each(function(item, key) {
      result[key] = item;
    });

    return result;
  },
*/
  loadMessages : function() {

    var val = Cookie.read(this.cookie.name);

    if (val) {

      var result = this.stringToObject(val);
      this.parseMessages(result);
      Cookie.dispose(this.cookie.name);
    }
  },

  addMessage : function(content, container) {

    container = container || $('messages');
    container.adopt(content);
  },

  parseMessages : function(result, container, delay) {

    if (result.errors && delay) {

      sylma.log('Cannot redirect while exception occured');
    }

    if (!result.errors && delay) {

      this.cookie.handler = Cookie.write(this.cookie.name, this.objectToString(result));
    }
    else {

      if (result.messages) {

        if (!$('sylma-messages')) {

          $(document.body).grab(new Element('div', {id : 'sylma-messages'}), 'top');
        }

        if (result.messages) {

          if (result.messages instanceof Array) {

            result.messages.forEach(function(message) {

              this.showMessage(message.content);
              //el.addClass('sylma-visible');
            }.bind(this));
          }
        }
      }

      if (result.errors) {

        for (var i in result.errors) {

          this.addMessage(this.importNode(result.errors[i].content), container);
        }
      }
    }

    return result;
  },

  showMessage : function(msg) {

    var el = new Element('div', {html : msg, 'class' : 'sylma-message sylma-hidder'});
    var id = 'sylma-messages';
    var container = $(id);

    if (!container) {

      container = new Element('div', {
        id : id
      });

      window.document.body.grab(container, 'top');
    }

    this.addMessage(el, container);
    window.getComputedStyle(el).opacity;
    el.addClass('sylma-visible');

    (function() {

      el.removeClass('sylma-visible');

      (function() {

        el.destroy();

      }).delay(2000);

    }).delay(5000);
  },

  /**
   * @param {bool} [get=FALSE] - Send request in GET, POST by default
   */
  send : function(path, args, callback, get, redirect) {

    args = args || {};
    //var self = this;

    var req = new Request.JSON({

      url : path + '.json',
      onSuccess: function(response)
      {
        sylma.ui.parseMessages(response, null, redirect);

        if (callback) callback(response);
      },
      onError : function(response)
      {
        if (callback) callback(response);
        
        sylma.ui.showMessage('Request error');
        throw new Error('Request error');
      },
      onFailure : function(response)
      {
        if (callback) callback(response);
        
        sylma.ui.showMessage('Request failure');
        throw new Error('Request failure');
      },
      onException : function(response)
      {
        if (callback) callback(response);
        
        sylma.ui.showMessage('Request exception');
        throw new Error('Request exception');
      }
    });

    return get ? req.get(args) : req.post(args);
  },

  isTouched : function() {

    return /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase());
  },

  getHash : function() {

    var hash = window.location.hash;
    return hash.indexOf('#') == 0 ? hash.substr(1) : hash;
  },

  generateID : function(prefix) {

    return prefix + this.uID++;
  },

  getVendorPrefix : function(style) {

    var result;

    switch (Browser.name) {

      case 'firefox' : result = 'Moz'; break;
      case 'safari' :
      case 'chrome' : result = 'webkit'; break;
      case 'opera' : result = 'O'; break;
      default : result = style; break;
    }

    return result + style.capitalize();
  },

  addEventTransition : function(el, callback, property, remove) {

    property = property || 'opacity';
    var name = Browser.name === 'firefox' ? 'transitionend' : this.getVendorPrefix('transition') + 'End';

    switch (property) {

      case 'margin-top' :
      case 'margin-right' :
      case 'margin-bottom' :
      case 'margin-left' :

        property = Browser.firefox ? property : 'margin';
        break;
    }

    var handler = function(e) {

      if (e.propertyName === property) {

        if (remove) {

          this.removeEventListener(name, handler);
        }

        callback && callback(e);
      }
    };

    el.addEventListener(name, handler);
  },

  debugSource : function() {

    $$('*').each(function(el) {

      var source = el.get('data-source');

      if (source) {

        var link = new Element('a', {
          href : source,
          html : 'E',
          'class' : 'hidder sylma-source',
        });

        link.inject(el, 'top');

        el.addEvents({
          mouseenter : function() {
            link.addClass('visible');
          },
          mouseleave : function() {
            link.removeClass('visible');
          }
        });
      }

    });
  }
};

sylma.uiTmp = new Class(sylma.uiClass);
sylma.ui = new sylma.uiTmp; // @todo

sylma.log = function(msg) {

  if (sylma.debug.log) {

    console.log(msg);
  }
}

var log = function()
{
  console.log.apply(console, arguments);
}
