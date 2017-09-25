
sylma.xsd.Typed = new Class({

  namespace : null,
  name : null,
  type : null,
  prepared : false,

  initialize : function(schema, datas) {

    this.schema = schema;
    this.load(datas);
  },

  load: function (datas) {

    var prefix;

    if (datas.qualified) {

      prefix = this.schema.getPrefix(datas.namespace)
    }
//console.log(datas.ref);
    this.dref = datas.ref;
    this.namespace = datas.namespace;
    this.name = datas.name;
    this.prefix =  prefix || '';
    this.shortname = (prefix ? prefix + ':' : '') + this.name;
    this.element = datas.element;
    this.type = datas.type;
    this.typeName = datas.typeName;
    this.source = datas.source;
  },

  prepare : function () {
//if (this.name === 'classes') console.log('start', this, this.type);
    if (!this.prepared) {
//console.log(this);
      this.prepared = true;
      this.prepareContent();

      if (!this.ref && !this.type) {

        throw new Error('Node not ready');
      }
    }
//    console.log(this.toString());
//if (this.name === 'classes') console.log('stop', this, this.type);
  },

  prepareContent : function () {

    if (this.dref) {

      var ref = this.schema.find(this.element, this.namespace, this.name);
      ref.prepare();
      
      this.ref = ref;
      this.type = ref.type;
    }
    else {

      var type = this.type;
      var typeName = this.typeName;
      var schema = this.schema;

      if (!type) {

        type = this.schema.findType(typeName[0], typeName[1]);
      }
      else {

        switch (type.element) {

          case 'complexType' : type = new sylma.xsd.ComplexType(schema, type); break;
          case 'simpleType' : type = new sylma.xsd.SimpleType(schema, type); break;
          case 'baseType' : type = new sylma.xsd.BaseType(schema, type); break;
          default : throw new Error('Unknown element : ' + type.element);
        }
      }

      this.type = type;
      
      type.prepare();
//      type.prepareChildren();
    }
//if (!type.prepare) console.log(type, this.ref);
  },

  _toElement : function() {

    var element = this.schema.document.element;
//console.log(element)
    var root = new sylma.xml.Element({
      options : {
        prefix : this.prefix,
        namespace : this.namespace,
        name : this.name,
      },
      sylma : element.sylma
    });
//console.log(root);
    root.buildTemplate = element.buildTemplate.bind(root);
    sylma.ui.loadArray([root]);
    var result = root.initTemplate();
    result.store('ref', this);

    return result;
  },

  toElement : function() {

    var prefix = this.prefix;
    var start = prefix ? '<span class="prefix">' + prefix + '</span>' : '';
    var element = this;
    var insert = this.schema.editor.getObject('insert');

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

  toString : function () {

    return this.namespace + ':' + this.name;
  }
});