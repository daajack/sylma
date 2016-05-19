
sylma.xml.Element = new Class({

  Extends : sylma.xml.Node,

  namespace : null,
  name : null,
  children : [],
  attributes : [],

  onReady: function () {
//return;
    if (!this.sylma.template.classes) {

      var el = this.getParent('element');
//console.log(this.getParent('element').sylma, this.sylma);
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

    if (this.objects.children) {

      this.children = this.objects.children[0].tmp;
    }

    if (this.objects.attributes) {

      this.attributes = this.objects.attributes[0].tmp;
    }
  },

  prepareChild : function (child) {

    child.parentElement = this;
  },

  addElement : function (element, previous) {

    var container = this.getObject('children')[0];
    var key;

    if (previous) {

      key = container.tmp.indexOf(previous) + 1;
    }
    else {

      key = 0;//undefined;//container.tmp.length - 1;
    }

    var child = container.add('element', {
      prefix : element.prefix,
      namespace : element.namespace,
      name : element.name,
      sylma : {
        splice : true,
      }
    }, key === container.tmp.length ? undefined : key);

    this.prepareChildren();
    this.prepareChild(child);

    var editor = this.getParent('editor');
    editor.schema.attachElement(child, element);

    editor.getObject('history').addStep('add', this.toPath(true), child.toXML(true), {
      position : key,
      type : 'element'
    });
//console.log(this.children);
  },

  addAttribute : function (attribute) {

    //var container = this.getObject('attribute')[0];

    var child = this.add('attribute', {
      prefix : attribute.prefix,
      namespace : attribute.namespace,
      name : attribute.name,
      value : '',
    });

    this.prepareChildren();
    this.prepareChild(child);

    var editor = this.getParent('editor');
    editor.schema.attachAttribute(child, attribute);
    var path = this.toPath(true);

    child.openValue(function() {

      editor.getObject('history').addStep('add', path, '', {
        type : 'attribute',
        namespace : attribute.namespace,
        name : attribute.shortname,
        value : child.value
      });

    });
/*
*/
//console.log(this.children);
  },

  remove : function () {

    this.getParent('editor').getObject('history').addStep('remove', this.toPath(true), '', {
      type : 'element',
    });

    this.parent();
    this.destroy();
  },

  getShortName : function () {

    return this.prefix ? this.prefix + ':' + this.name : this.name;
  },

  getPosition : function () {

    return this.parentElement.children.indexOf(this);
  },

  toPath : function (last) {

    var el = this.parentElement;
    var position = '';

    if (el) {

      position = el.children.indexOf(this);
    }

    return (el ? el.toPath() : '') + position + (last ? '' : '/');
  },

  toString : function () {

    return this.namespace + ':' + this.name;
  },

  toXML : function (first) {

    var xmlns = '';

    if (first) {

      var prefix = this.prefix ? ':' + this.prefix : '';
      xmlns = ' xmlns' + prefix + '="' + this.namespace + '"';
    }

    var name = this.getShortName();
    var attributes = this.attributes.join(' ');
    var content = '';

    if (this.children.length) {

      content = '>' + this.children.map(function(item) { return item.toXML(); }).join('')
    }

    var end = content ? '</' + name + '>' : '/>';

    return '<' + name + xmlns + attributes + content + end;
  },
});