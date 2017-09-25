
sylma.xml.ElementClass = {

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

  onLoad: function () {

    this.prepare();
  },

  prepare: function () {

    this.sylma.splice = true;

    this.namespace = this.options.namespace;
    this.name = this.options.name;
    this.prefix = this.options.prefix;
    this.node = this.getNode();
//    this.ref = null;
    
    this.prepareChildren();
    this.children.each(this.prepareChild, this);

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
    
    this.updateFormat();
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

    var step = {
      type : 'add',
      path : this.toPath(true),
      token : child.toToken(),
      content : child.toXML(true),
      arguments :
      {
        type : 'element',
        position : child.key
      }
    };
    
    var history = editor.getObject('history');

    history.addStep(step);
    history.applyStep(this.getParent('document').document, step, step.arguments);
    
    editor.schema.attachElement(child, element);

    editor.fireEvent('update');
  },

  addText : function (previous) {
//console.log('insert');
    var child = this.addChild({
      content : ''
    }, 'text', previous);

    child.openValue(function() {

      var step = {
        type : 'add',
        path : this.toPath(true),
        token : child.toToken(),
        content : child.toXML(true),
        arguments :
        {
          position : child.key,
          type : child.element
        }
      };

      var editor = this.getParent('editor');
      var history = editor.getObject('history');

      history.addStep(step);
      history.applyStep(this.getParent('document').document, step, step.arguments);

      editor.fireEvent('update');

    }.bind(this));
  },

  addContent : function (options, previous) {

    var child = this.addChild(options, 'element', previous);

    var step = {
      type : 'add',
      path : this.toPath(true),
      token : child.toToken(),
      content : child.toXML(true),
      arguments :
      {
        type : 'element',
        position : child.key
      }
    };
    
    var editor = this.getParent('editor');
    var history = editor.getObject('history');
    
    history.applyStep(this.getParent('document').document, step, step.arguments);

    editor.fireEvent('update');
    
    history.addStep(step);

//    editor.schema.attachElement(child, element);
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
  
  addIndexedChild : function (args, alias, position) {

    var container = this.getChildren();

    var key = position === container.tmp.length ? undefined : position;
    
    var target;
    var _class = container.sylma.template.classes[alias];
    var result = container.buildObject(alias, args, key);

    if (key >= 0) {

      ++key;

      var next = container.tmp[key];
      target = next.getNode();
    }
    else {

      target = container.getNode().getElements('.' + _class.node).getLast();
    }
    
    if (!target)
    {
      throw new Error('No target found');
    }
    
    result.addTo(target);
    result.key = position;

    this.prepareChildren();
    this.prepareChild(result);

    return result;
  },

  remove : function (save) 
  {
    save = save === undefined ? true : save;
    
    var step = {
      type : 'remove',
      path : this.toPath(true),
      token : this.toToken(),
      content : this.toXML(true),
      arguments :
      {
        type : 'element',
      }
    };
    
    var editor = this.getParent('editor');
    var history = editor.getObject('history');
    
    history.applyStep(this.getParent('document').document, step, step.arguments);
    
    if (save)
    {
      history.addStep(step);
    }

    editor.fireEvent('update');
    
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
    var document = this.getParent('document').document;

    child.openValue(function() {

      var step = {
        type : 'add',
        path : path,
        token : child.toToken(),
        content : child.value,
        arguments :
        {
          type : 'attribute',
          namespace : attribute.namespace,
          name : attribute.name,
          prefix : attribute.prefix
        }
      };

      var history = editor.getObject('history');
      
      history.addStep(step);
      history.applyStep(document, step, step.arguments)

      editor.fireEvent('update');
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
    
    var doc = this.getParent('document');
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

  validateMove : function (parent, previous) 
  {
    var editor = this.getParent('editor');
    
    this.applyMove(parent, previous, true);
  },
  
  applyMove : function (parent, key, save)
  {
    if (!this.parentElement)
    {
      throw new Error('Cannot move root');
    }
    
    var editor = this.getParent('editor');
    
    if (editor.updating)
    {
      sylma.ui.showMessage('... updating ...');
      return;
    }
    
    editor.updating = true;
    
    var source = this.toPath(true);
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
    
    var children = this.parentElement.getObject('children')[0].tmp;
    children.splice(this.getPosition(), 1);
    this.parentElement.prepareChildren();
    
    if (typeOf(parent) === 'string')
    {
      parent = this.getParent('editor').findNode(parent, 'element');
    }
    
    if (!parent.objects.children) 
    {
      parent.add('children');
    }
    
    if (key !== undefined)
    {
      if (typeOf(key) !== 'number')
      {
        key = key.getPosition() + 1;
      }
    }
    else
    {
      key = 0;
    }
    
    var parentPath = parent.toPath(true);
    
    var children = parent.getObject('children')[0].tmp;
    children.splice(key, 0, this);
    parent.prepareChildren();

    this.parentElement = parent;

    if (key !== 0)
    {
      var previous = parent.children[key - 1];
      node.inject(previous.getNode(), 'after');
    }
    else
    {
      node.inject(parent.getObject('children')[0].getNode(), 'top');
    }
    
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
    
    editor.updating = false;
    
    var step = {
      type : 'move',
      path : source,
      token : this.toToken(),
      content : '',
      arguments :
      {
        type : 'element',
        parent : parentPath,
        position : this.getPosition()
      }
    };
    
    var history = editor.getObject('history');
    
    if (save)
    {
      history.addStep(step);
    }
    
    history.applyStep(this.getParent('document').document, step, step.arguments);
    editor.fireEvent('update');
//    return parentPath;
  },
  
  getShortName : function () {

    return this.prefix ? this.prefix + ':' + this.name : this.name;
  },

  getPosition : function () {

    return this.parentElement.children.indexOf(this);
  },

  updateFormat : function()
  {
    var el = this.getNode();
    var children = this.children;
    
    el.removeClass('format-empty');
    el.removeClass('format-text');
    el.removeClass('format-complex');
    el.removeClass('text-long');

    if (!children.length)
    {
      el.addClass('format-empty');
    }
    else
    {
      if (children.length === 1 && children[0].element === 'text')
      {
        el.addClass('format-text');
        
        if (children[0].value.length > 100)
        {
          el.addClass('text-long');
        }
      }
      else
      {
        el.addClass('format-complex');
      }
    }
  },
  
  copy: function () 
  {
    var input = new Element('input', { type : 'text', style : 'width: 0; height: 0;', value : this.toXML() });
    this.getNode().grab(input);

    input.select();

    var successful = document.execCommand('copy');

    if (successful)
    {
      sylma.ui.showMessage('Element copied');
    }
    else
    {
      sylma.ui.showMessage('Error, cannot copy');
    }
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
  
  toToken : function()
  {
    var p = this.prefix;
    return (p ? p + ':' : '') + this.name;
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
};

sylma.xml.Element = new Class(sylma.xml.ElementClass);