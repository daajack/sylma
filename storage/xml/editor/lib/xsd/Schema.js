
sylma.xsd = {};

sylma.xsd.parseTypes = [];

sylma.xsd.SchemaClass = {

  editor : null,
  children : null,
  built : 0,
  prefixes : {},

  initialize : function(datas, namespaces) {

    this.prefixes = namespaces;
    this.load(datas);
  },

  load: function (datas, namespaces) {

    //console.log(datas);

    this.namespace = datas.namespace;

    var children = this.children = [];
    var schema = this;

    var elements = this.elements = [];
    var types = this.types = {};
    var groups = this.groups = {};
    var attributes = this.attributes = {};
    var attributeGroups = this.attributeGroups = {};

    Object.each(datas, function(item) {

      var child;
//console.log(item);
      switch (item.element) {

        case 'annotation' : child = new sylma.xsd.Annotation(schema, item); break;
        case 'group' : child = this.addChild(groups, new sylma.xsd.Group(schema, item)); break;
        case 'element' : child = this.addChild(elements, new sylma.xsd.Element(schema, item)); break;
        case 'baseType' : child = this.addChild(types, new sylma.xsd.BaseType(schema, item)); break;
        case 'simpleType' : child = this.addChild(types, new sylma.xsd.SimpleType(schema, item)); break;
        case 'complexType' : child = this.addChild(types, new sylma.xsd.ComplexType(schema, item)); break;
        case 'attribute' : child = this.addChild(attributes, new sylma.xsd.Attribute(schema, item)); break;
        case 'attributeGroup' : child = this.addChild(attributeGroups, new sylma.xsd.AttributeGroup(schema, item)); break;
        default : throw new Error('Unknown element : ' + item.element);
      }

      children.push(child);
      this.built++;

    }, this);

    //this.prepare(this.children);
    console.info(this.built + ' objects built');
  },

  addChild: function (collection, child) {

    if (!collection[child.namespace]) collection[child.namespace] = {};
    collection[child.namespace][child.name] = child;
  },

  prepare: function (children) {

    children.each(function(item) {
//if (item.prepare) console.log(item);
      item.prepare && item.prepare();
    });
  },

  getPrefix: function (namespace) {

    return this.prefixes[namespace];
  },

  loadCollection: function (datas) {

    var result = [];
    var schema = this;

    Object.each(datas, function(item) {

      var child;

      switch (item.element) {

        case 'annotation' : child = new sylma.xsd.Annotation(schema, item); break;
        case 'element' : child = new sylma.xsd.Element(schema, item); break;
        case 'any' : child = new sylma.xsd.Any(schema, item); break;
        case 'group' : child = new sylma.xsd.Group(schema, item); break;
        case 'sequence' : child = new sylma.xsd.Sequence(schema, item); break;
        case 'choice' : child = new sylma.xsd.Choice(schema, item); break;
        case 'all' : child = new sylma.xsd.All(schema, item); break;
        case 'attributeGroup' : child = new sylma.xsd.AttributeGroup(schema, item); break;
        case 'anyAttribute' : child = new sylma.xsd.AnyAttribute(schema, item); break;
        case 'attribute' : child = new sylma.xsd.Attribute(schema, item); break;
        default : throw new Error('Unknown element : ' + item.element);
      }

      result.push(child);
      this.built++;

    }, this);

    return result;
  },

  findChild: function (children, namespace, name, element, debug) {
    
    debug = debug === undefined ? true : false;
    var ns = children[namespace];
    var result;

    if (ns) {

      result = ns[name];
    }

    if (!result && debug) {

      throw new Error('Cannot find ' + element + ' : ' + namespace + ':' + name);
    }

    return result;
  },

  find: function (alias, namespace, name, debug) {
    
    var result;

    switch (alias) {

      case 'element' : result = this.findElement(namespace, name, debug); break;
      case 'attribute' : result = this.findAttribute(namespace, name); break;
      case 'group' : result = this.findGroup(namespace, name); break;
      case 'attributeGroup' : result = this.findAttributeGroup(namespace, name); break;

      default : throw 'Unknown alias : ' + alias;
    }

    return result;
  },

  findElement: function (namespace, name, debug) {

    return this.findChild(this.elements, namespace, name, 'element', debug);
  },

  findAttribute: function (namespace, name) {

    return this.findChild(this.attributes, namespace, name, 'attribute');
  },

  findGroup: function (namespace, name) {

    return this.findChild(this.groups, namespace, name, 'group');
  },

  findAttributeGroup: function (namespace, name) {

    return this.findChild(this.attributeGroups, namespace, name, 'attributeGroup');
  },

  findType: function (namespace, name) {

    return this.findChild(this.types, namespace, name, 'type');
  },

  findElements: function (namespace) {

    var result = Object.values(this.elements[namespace]);

    result.each(function(element) {

      element.prepare();
    });

    return result;
  },

  findAttributes: function (namespace) {

    var result = Object.values(this.attributes[namespace]);

    result.each(function(element) {

      element.prepare();
    });

    return result;
  },

  validate: function (document) {

    this.document = document;

    var root = document.element;

    var element = this.findElement(root.namespace, root.name);
    element.prepare();
    
    this.attachElement(root, element);
  },

  attachElement: function (el, ref) {

    el.ref = ref;
//console.log(el, ref);
    if (!ref)
    {
      console.log('No ref given');
      return;
    }
//console.log('Attach ' + el);
//console.log(el, ref);
    if (ref.element !== 'element') {
      
      throw new Error('Not element');
    }
    
    var type = ref.type;

    if (!type) {

      throw new Error('No type found');
    }

    type.prepareChildren();
if (0) {
    Object.values(el.attributes).each(function(item) {

      this.lookupAttribute(item, type.children);

      if (!item.ref) {

        console.log('Cannot attach', item.namespace + ':' + item.name);
      }
    }.bind(this));
}
    if (el.children.length === 1 && el.children[0].type === 'text') {

      if (type.element === 'complexType' && !type.mixed) {

        console.log(el + ' should be complex');
      }
    }
    else {

      if (type.element === 'simpleType') {

        console.log(el + ' should be simple');
      }
      else {

        el.children.each(function(child) {

          if (child.type !== 'text') {

            child.ref = null;

//              type.prepareChildren();

            this.lookupElement(child, type.children);
            this.checkElement(child);
//console.log(child.toToken(), child.ref);
          }

        }, this);
      }
    }
  },
  
  checkElement: function (child)
  {
//console.log('check', child.toToken(), child.getNode());
    if (child.ref) {

      child.getNode().removeClass('invalid');
    }
    else {

      child.getNode().addClass('invalid');
      console.log('Cannot attach : ' + child.toToken());
    }
  },

  lookupAttribute: function (attribute, collection) {

    var len = collection.length;

//console.log('Find ' + el, collection.length);
    for (var key = 0; key < len; key++) {

      var item = collection[key];
      item.prepare && item.prepare();

      switch (item.element) {

        case 'attribute' :

          if (item.name === attribute.name && item.namespace === attribute.namespace) {

            this.attachAttribute(attribute, item);
          }

          break;

        case 'anyAttribute' :

          if (item.namespace === attribute.namespace) {

            item = this.findAttribute(attribute.namespace, attribute.name);
            
            if (item)
            {
              item.prepare();
              this.attachAttribute(attribute, item);
            }
          }

          break;

        case 'attributeGroup' : this.lookupAttribute(attribute, item.children); break;

      }
    }
  },

  attachAttribute: function (attribute, ref) {

    attribute.ref = ref;
  },

  lookupElement: function (el, collection) {

    var len = collection.length;
//console.log('attach : ' + el, collection);
    element:
    for (var key = 0; key < len; key++) {

      var item = collection[key];
      item.prepare && item.prepare();

      switch (item.element) {

        case 'element' :
//console.log((item.name === el.name && item.namespace === el.namespace), el.name, item)
          if (item.name === el.name && item.namespace === el.namespace) {
//console.log(el.toToken(), item);
            this.attachElement(el, item);
            break element;
          }
//console.log(el.toToken());
          break;

        case 'any' :
//console.log(item);
          if (item.namespace === el.namespace) {

            item = this.findElement(el.namespace, el.name);

            if (item)
            {
              item.prepare();
              this.attachElement(el, item);
//console.log(el, item);
            }
            else
            {
              console.warn('Cannot find element : ' + el.toToken());
            }
          }

          break;
        
        case 'group' :
        case 'all' :
        case 'sequence' :
        case 'choice' : this.lookupElement(el, item.children); break;

        case 'complexType' :
        case 'simpleType' :
        case 'attribute' :
        case 'attributeGroup' :
        case 'anyAttribute' :
        case 'annotation' :
          break;

        default : console.log(key, item); throw new Error('Unknown element : ' + item.element);
      }
    }
    
//    console.log(el.toToken());
  },

  loadChildren: function(container) {
//console.log(element);
    var result = [];
//console.log(container);
    container.children.each(function(item) {

      switch (item.element) {

        case 'any' :

          result.push.apply(result, this.findElements(item.namespace));
          break;

        case 'element' :

          result.push(item);
          break;

        case 'group' :
        case 'choice' :
        case 'sequence' :
        case 'all' :

          item.prepare && item.prepare();
          result.push.apply(result, this.loadChildren(item));
          break;

        case 'attribute' :
        case 'attributeGroup' :
      }
    }, this);

    var uniques = [];
    result.each(function(item) { if (uniques.indexOf(item) === -1) uniques.push(item); });

    return uniques;
  },

  loadAttributes: function(container) {
//console.log(element);
    var result = [];

    container.children.each(function(item) {

      switch (item.element) {

        case 'attribute' :

          item.prepare();
          result.push(item);
          break;

        case 'anyAttribute' :

          result.push.apply(result, this.findAttributes(item.namespace));
          break;

        case 'attributeGroup' :

          item.prepare && item.prepare();
          result.push.apply(result, this.loadAttributes(item));
          break;

        case 'complexType' :
        case 'simpleType' :
        case 'any' :
        case 'element' :
        case 'group' :
        case 'choice' :
        case 'sequence' :
        case 'all' :
        case 'annotation' :
      }
    }, this);

    var uniques = [];
    result.each(function(item) {

      item = item.ref ? item.ref : item;

      if (uniques.indexOf(item) === -1) {

        uniques.push(item);
      }
    });

    return uniques;
  }
};

sylma.xsd.Schema = new Class(sylma.xsd.SchemaClass);