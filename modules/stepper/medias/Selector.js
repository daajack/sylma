sylma.stepper.Selector = new Class({

  Extends : sylma.stepper.Framed,

  mask : null,
  activated : false,
  masked : false,

  options : {
    target : null, // dom node
    element : null, // string
    frames : [] // stack of parent frames
  },

  onReady : function() {

    var target = this.options.target;

    if (!target) {

      if (!this.options.element) {

        this.activated = true;
      }
    }
    else {

      this.set('element', this.buildPath(target));
    }
  },

  activate : function(callback, e) {

    this.onSelect = callback;
    this.activated = true;

    var mask = this.getMask();
    //this.show(mask);
    var overlay = this.overlay = new Element('div', {
      id : 'overlay'
    });

    this.getFrame().toggleClass('sylma-visible', true);
    this.getFrame().setStyle('z-index', 70);

    document.body.adopt(overlay);
    overlay.tween('opacity', 0.35);

    if (e) {

      e.preventDefault();
    }

    this.startCapture();
  },

  getMask : function() {

    var result = this.getParent('main').selectorMask;

    if (!result) {

      var result = this.getParent('main').selectorMask = new Element('div', {
        'class' : 'selector-mask sylma-hidder'
      });

      document.body.adopt(result);
    }

    return result;
  },

  startCapture : function() {

    this.getParent('main').pauseRecord();
    this.startCaptureFrame(this.getFrame());
  },

  startCaptureFrame : function(frame) {

    var events = {
      click : function(e) {

        this.selectElement(e.target);
        this.select(e.target);

        e.preventDefault();

      }.bind(this),
      mousemove : function(e) {

        this.selectElement(e.target);

      }.bind(this)
    };

    this.getWindow(frame).addEvents(events);
/*
    this.getParent('main').getFrames(frame).each(function(item) {

      this.startCaptureFrame(item);
    }.bind(this));
*/
    frame.store('selector', events);
  },

  stopCapture: function() {

    this.getParent('main').resumeRecord();
    this.stopCaptureFrame(this.getFrame());
  },

  stopCaptureFrame : function(frame) {

    var events = frame.retrieve('selector');

    if (events) {

      this.getWindow(frame).removeEvents(events);
/*
      this.getParent('main').getFrames(frame).each(function(item) {

        this.stopCaptureFrame(item);
      }.bind(this));
*/
    }
  },

  windowMove : function(e) {


  },

  windowClick : function(e) {

  },

  toggleMask : function(val) {

    var mask = this.getMask();
    var element = this.getElement();

    if (val && element) {

      this.selectElement(element);
    }

    this.toggleShow(mask, val);
  },

  selectElement : function(target) {

    var el = this.getMask();
    var margin = this.getFrame().getPosition();
    var position = target.getPosition();

    position.y += margin.y;

    el.setPosition(position);
    var size = target.getSize();
    el.setStyles({
      width: size.x + 2,
      height: size.y + 2
    });
  },

  select : function(target) {

    this.activated = false;
if (!target) throw new Error('Target not found');
    //this.set('target', target);
    this.updateElement(target);

    this.stopCapture();

    new Fx.Morph(this.overlay, {
      onComplete : function() {

        this.overlay.remove();
        this.getFrame().setStyle('z-index', 50);

      }.bind(this),
      duration : 500
    }).start({
      opacity : 0
    });

    if (this.onSelect) {

      this.onSelect(target);
    }

    if (this.onSelectAdd) {

      this.onSelectAdd();
      delete this.onSelectAdd;
    }
  },

  updateElement : function(target) {

    this.set('element', this.buildPath(target));
  },

  changeElement: function(target) {

    if (!target) {

      sylma.ui.showMessage('No element found');
    }
    else {

      this.selectElement(target);
      this.updateElement(target);

      var path = this.get('element');

      this.getNode('display').set({
        html : path,
        title : path
      });
    }
  },

  selectParent : function() {

    var parent = this.getElement().getParent();

    if (parent.get('tag') !== 'html') {

      this.changeElement(parent);
    }
    else {

      sylma.ui.showMessage('Top elements reached');
    }
  },

  selectChild : function() {

    this.changeElement(this.getElement().getFirst());
  },

  selectNext : function() {

    this.changeElement(this.getElement().getNext());
  },

  selectPrevious : function() {

    this.changeElement(this.getElement().getPrevious());
  },

  getElement : function() {

    var path = this.options.element;

    var win = this.getWindow();
    var result;

    if (path) {

      path.split(';').each(function(path) {

        win = this.getWindow(result);
        result = win.document.body.getElement(path);

      }.bind(this));
    }

    return result;
  },

  getPath : function() {

    var result = this.get('element');

    return result;
  },

  buildPath : function(target) {

    var result = '';

    this.options.frames.slice(1).each(function(frame) {

      result += this.buildElementPath(frame) + ';';

    }.bind(this));

    if (target.getParents().length > 1) {

      result += this.buildElementPath(target);
    }
    else {

      result += target.get('tag');
    }

    return result;
  },

  buildElementPath : function(target) {

    var useID = false;

    var result = $$([target]).append(target.getParents().slice(0, -2)).map(function(el, key, array) {

      var result = null;

      if (!useID) {

        var id = el.get('id');

        if (id && !id.match(/^sylma/) && id.indexOf('[') === -1) {

          result = '#' + el.id;
          useID = true;
        }
        else {

          var name = el.get('tag');
          var previous = '';

          if (el.getSiblings().filter(name).length) {

            var count = el.getAllPrevious().length;
            previous = ':nth-child(' + (count + 1) + ')';
          }

          result = name + previous;

          if (key === array.length - 1) result = ' > ' + result;
        }
      }

      return result;

    }).clean().reverse().join(' > ');

    return result;
  },

  addTo : function(node) {

    if (this.activated) {

      this.onSelectAdd = function() {

        this.set('element', this.getPath());
        this.addTo(node);

      }.bind(this);
    }
    else {

      this.parent(node);
    }
  },

  toJSON : function() {

    return this.options.element;
  }
});
