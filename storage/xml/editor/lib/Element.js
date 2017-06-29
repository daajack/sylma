
sylma.xml.Element = new Class({

  Extends : sylma.xml.Node,

  namespace : null,
  name : null,
  children : [],
  attributes : null,
  sylma : {
    splice : true
  },

  onReady: function () {
//console.log(this.options);
    if (!this.sylma.template.classes) {

      //var el = this.getParent('element');
      var el = this.getParent('document').elementTemplate;

      this.sylma = el.sylma;
      this.buildTemplate = el.buildTemplate.bind(this);
    }
  },

  initNode : function(props, deep) {

    this.parent(props, deep);

    var children = this.getObject('children');

    if (children) {

      var _class = children[0].sylma.template.classes['element'];
      var spacer = this.getNode().getElement('.' + _class.node);

      var id = sylma.ui.generateID('element');
      spacer.set('class', 'spacer ' + id);

      _class.node = id;
    }
  },

  onLoad: function () {

    this.prepare();
  },

  prepare: function () {

    this.sylma.splice = true;

    this.namespace = this.options.namespace;
    this.name = this.options.name;
    this.prefix = this.options.prefix;

    this.prepareChildren();
    this.children.each(this.prepareChild, this);

    this.node = this.getNode();

    //this.position = this.node.getParent().getChildren().indexOf(this.node);
    //console.log(this.children);
  },

  insert : function (previous, attribute) {

    this.getParent('editor').getObject('insert').attach(this, previous, attribute);
  },

  prepareChildren : function () {

    if (this.objects.children) 
    {
      this.children = this.objects.children[0].tmp;
    }
    
    this.attributes = {};

    if (this.objects.attribute)
    {
      this.objects.attribute.each(function(attribute)
      {
        this.attributes[attribute.shortname] = attribute;
      }.bind(this));
    }
  },

  prepareChild : function (child) {

    child.parentElement = this;
  },

  getChildren : function () {

    var collection = this.getObject('children');
    var children;

    if (collection) {

      children = collection[0];
    }
    else {

      children = this.add('children');
    }

    return children;
  },

  addSibling : function () {

    console.log('add sibling', this);
  },

  addElement : function (element, previous) {

    var child = this.addChild({
      prefix : element.prefix,
      namespace : element.namespace,
      name : element.name
    }, 'element', previous);

    var editor = this.getParent('editor');

    editor.getObject('history').addStep('add', this.toPath(true), child.toXML(true), {
      position : child.key,
      type : 'element'
    });

    editor.schema.attachElement(child, element);
  },

  addText : function (previous) {

    var child = this.addChild({
      content : ''
    }, 'text', previous);

    var editor = this.getParent('editor');

    child.openValue(function() {

      editor.getObject('history').addStep('add', this.toPath(true), child.toXML(true), {
        position : child.key,
        type : child.element
      });

    }.bind(this));
  },

  addChild : function (options, type, previous) {

    var container = this.getChildren();
    var key;

    if (previous) {

      key = container.tmp.indexOf(previous) + 1;
    }
    else {

      key = 0;//undefined;//container.tmp.length - 1;
    }

    return this.addIndexedChild(options, type, key);
  },

  addIndexedChild : function (options, type, key) {

    var container = this.getChildren();

    var result = container.add(type, options, key === container.tmp.length ? undefined : key);
    result.key = key;

    this.prepareChildren();
    this.prepareChild(result);

    return result;
  },

  remove : function (save) 
  {
    save = save === undefined ? true : save;
    
    if (save)
    {
      this.getParent('editor').getObject('history').addStep('remove', this.toPath(true), this.toXML(true), {
        type : 'element',
      });
    }

    var parent = this.parentElement;
    this.sylma.key = parent.children.indexOf(this);

    this.parent();
    //this.destroy();

    parent.prepareChildren();
  },

  addAttributeFromType : function (attribute) {
    
    var editor = this.getParent('editor');
    var child = this.addAttribute(attribute.namespace, attribute.name, attribute.prefix, '');
    
    editor.schema.attachAttribute(child, attribute);

    var path = this.toPath(true);

    child.openValue(function() {

      editor.getObject('history').addStep('add', path, child.value, {
        type : 'attribute',
        namespace : attribute.namespace,
        name : attribute.shortname,
      });
    });
  },
  
  addAttribute: function (namespace, name, prefix, value) 
  {
    var child = this.add('attribute', {
      prefix : prefix,
      namespace : namespace,
      name : name,
      value : value,
    });

    this.prepareChildren();
    this.prepareChild(child);

    return child;
  },

  initMove : function () {

    var confirm = false;
    
    var doc = this.getParent('editor').getObject('container').getObject('document')[0];
//console.log(doc);
    var tests = doc.element.getNode().getElements('.spacing');
    var editor = this.getParent('editor');
    var enode = editor.getNode();
    var cnode = this.getNode();

    var spacings = tests.filter(function(item) {

      return item.getParents().indexOf(cnode) === -1;

    }).map(function(item) {

      return [
        item,
        item.getPosition(enode).y
      ];
    });

    var padding = editor.getNode().getPosition();
    var dy = spacings[0][0].getSize().y / 2;

    this.mousemove = function(e) {

      if (!confirm) {

        confirm = true;
        this.confirmMove();
      }
      else {

        var mouse = e.page;
        tests.removeClass('target');

        var closer = spacings.reduce(function(current, previous) {

          var my = mouse.y - padding.y - dy;

          return Math.abs(my - current[1]) < Math.abs(my - previous[1]) ? current : previous;
        });

        closer[0].addClass('target');
        this.closer = closer[0];

        this.dummy.setStyles({
          left : e.client.x,
          top : e.client.y
        });
      }

    }.bind(this);

    window.addEvent('mousemove', this.mousemove);
  },

  cancelMove : function () {

    this.resetMove();
  },

  resetMove : function () {

    if (this.dummy)
    {
      this.dummy.dispose();
    }
    
    this.getNode().removeClass('moving');

    window.removeEvent('mousemove', this.mousemove);
    window.removeEvent('mouseup', this.mouseup);

    var editor = this.getParent('editor');
    editor.stopMove();
  },

  confirmMove : function () {

    var editor = this.getParent('editor');
    editor.startMove();

    this.getNode().addClass('moving');

    var dummy = this.toElement();
    dummy.addClass('editor-dummy');
    editor.getNode().grab(dummy);

    this.mouseup = function(e) {

      var target = this.closer;
      //var target = e.target;

      if (target) {

        target.removeClass('target');
        var obj = target.retrieve('sylma-object');
        var el, previous;

        if (target.hasClass('parent')) {

          previous = obj;
          el = obj.parentElement;
        }
        else {

          el = obj;
        }

        this.validateMove(el, previous);
      }
      else {

        this.cancelMove();
      }

    }.bind(this);

    window.addEvent('mouseup', this.mouseup);

    this.dummy = dummy;
  },

  validateMove : function (parent, previous, save) 
  {
    save = save === undefined ? true : save;
    
    var editor = this.getParent('editor');
    var paths = this.applyMove(parent, previous);
console.log(paths);
    var position = paths[1].pop();

    editor.getObject('history').addStep('move', paths[0].join('/'), '', {
      type : 'element',
      parent : paths[1].join('/'),
      position : position
    });
  },
  
  applyMove : function (parent, previous) 
  {
    var editor = this.getParent('editor');
    var node = this.getNode();
    var copy = node.clone(true);

    this.resetMove();

    node.grab(copy, 'after');
    node.setStyle('height', 0);

    var height = copy.getSize().y;
    var options = {
      duration: 200,
      property: 'height'
    };

    var hide = new Fx.Tween(copy, options);
    var show = new Fx.Tween(node, options);
 
    if (previous)
    {
      node.inject(previous.getNode(), 'after');
    }
    else
    {
      node.inject(parent.getObject('children')[0].getNode(), 'top');
    }
    
    var source = this.toPathArray();

    if (previous)
    {
      var target = previous.toPathArray();
      target[target.length - 1]++;
    }
    else
    {
      var target = parent.toPathArray();
      target.push(0);
    }
    
    var k = 0;
    var len = source.length;
    
    while (k < len)
    {
console.log('check', source[k], target[k]);
      if (source[k] < target[k])
      {
console.log('inc', k === len - 1);
        if (k === len - 1) target[k]--;
        break;
      }
      else if (source[k] > target[k])
      {
console.log('dec', k === len - 1);
//        if (k === len - 1) source[k]++;
        break;
      }

      k++;
    }

    var sk = source[source.length - 1];
    
    var children = this.parentElement.getObject('children')[0].tmp;
    children.splice(sk, 1);
    this.parentElement.prepareChildren();

    var tk = target[target.length - 1];

    var children = parent.getObject('children')[0].tmp;
    children.splice(tk, 0, this);
    parent.prepareChildren();

    this.parentElement = parent;
console.log('key', source, target);//, previous.getNode());
parent.children.each(function(child, k)
{
  console.log('child', k, child.node);
});

    editor.schema.attachElement(parent, parent.ref);

    hide.addEvent('complete', function(node)
    {
      node.dispose();
    });

    hide.start(0);

    show.addEvent('complete', function(node)
    {
      node.setStyle('height');
    });

    show.start(height);

    return [
      source,
      target
    ];
  },
  
  getShortName : function () {

    return this.prefix ? this.prefix + ':' + this.name : this.name;
  },

  getPosition : function () {

    return this.parentElement.children.indexOf(this);
  },

  toPathArray : function (last) {

    var el = this.parentElement;
    var result;

    if (el) {

      result = el.toPathArray();
      result.push(el.children.indexOf(this));
    }
    else
    {
      result = [];
    }

    return result;
  },
  
  toPath : function (last) {

    var el = this.parentElement;
    var position = '';

    if (el) {

      position = el.children.indexOf(this);
    }

    return (el ? el.toPath() : '') + position + (last ? '' : '/');
  },

  valueOf : function () {
    
    return this.namespace + ':' + this.name;
  },
  
  toString : function () {

    return this.valueOf();
  },

  toElement : function() {

    var prefix = this.prefix;
    var start = prefix ? '<span class="prefix">' + prefix + '</span>' : '';
    var element = this;
    var insert = this.getParent('editor').getObject('insert');

    var result = new Element('div', {
      html : '<div class="fullname">' + start + this.name + '</div>',
      'class' : 'node ' + this.element + ' node-' + prefix,
      events : {
        mousedown : function() {
          insert.addChild(element);
        }
      }
    });

    result.store('ref', this);

    return result;
  },

  toXML : function (first) {

    var namespaces = {};

    if (first || (this.parentElement && this.parentElement.namespace !== this.namespace)) {

      namespaces[this.namespace] = this.prefix;
    }

    var name = this.getShortName();
    
    var attributes = Object.values(this.attributes);
    
    attributes.each(function(attr)
    {
      var ns = attr.namespace;
      if (ns && !namespaces[ns])  namespaces[ns] = attr.prefix;
    });
    
    var attributes = attributes.join(' ');
    var content = '';

    if (this.children.length) {

      content = '>' + this.children.map(function(item) { return item.toXML(); }).join('')
    }
    
    var xmlns = Object.values(Object.map(namespaces, function(prefix, ns) { return ' xmlns' + (prefix ? ':' + prefix : '') + '="' + ns + '"'; })).join(' ')
    var end = content ? '</' + name + '>' : '/>';

    return '<' + name + xmlns + (xmlns || attributes ? ' ' : '') + attributes + content + end;
  },
});