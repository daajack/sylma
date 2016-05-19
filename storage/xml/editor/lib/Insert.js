
sylma.xml.Insert = new Class({

  Extends : sylma.ui.Template,

  element : null,
  previous : null,
  attribute : false,

  onLoad : function () {

    this.node = this.getNode();
  },

  attach: function (element, previous, attribute) {

    this.schema = this.getParent('editor').schema;

    var children = element.getObject('children');

    if (children && children.length) {

      var node = previous ? previous.getNode() : children[0].getNode().getPrevious();
      node.grab(this.node, 'after');
    }
    else {

      element.node.grab(this.node);
    }

    this.element = element;
    this.previous = previous;
    this.attribute = attribute;

//console.log(previous);
    this.updateChildren();
    this.show();

    var input = this.getNode('input');
    input.focus.delay(200, input);
  },

  updateChildren: function () {

    var element = this.element;
    var container = this.getNode('container');

    container.empty();

    if (element.ref) {

      var result = [];
      var max = 10;
      //var max = Infinity;
      var input = this.getNode('input').get('value');

      var children = this.schema.loadAttributes(element.ref.type);

      if (!this.attribute) {

        children.push.apply(children, this.schema.loadChildren(element.ref.type));
      }

      if (input) {

        var reg = new RegExp(input.toLowerCase());

        children = children.filter(function(item) {

          return item.shortname.toLowerCase().match(reg);
        });
      }

      children.each(function(item) {

        if (result.indexOf(item) === -1) {

          result.push(item);
        }
      });

      var attribute = this.attribute;

      result.sort(function(a, b) {

        var diff = attribute ? a.element > b.element : a.element < b.element;
        return a.element !== b.element ? diff : a.name > b.name;

      });

      container.adopt(result.slice(0, max));
    }
  },

  pressKey : function (e) {
//console.log(e);
    switch (e.code) {

      case 13 : // enter

        this.validate();
        break;

      case 27 : // esc

        this.hide();
        break;

      case 52 : // shift
      case 9 : // tab
      case 17 : // ctrl
      case 18 : // alt

      case 37 : // left
      case 38 : // up
      case 39 : // right
      case 40 : // down
      case 106 : // multiply
      case 111 : // divide
      case 107 : // add
      case 109 : // sub
        break;
    }
  },

  addChild: function (node) {
console.log('Add', node);
    this.hide();

    switch (node.element) {

      case 'element' :

        if (!this.element.objects.children) {

          this.element.add('children');

          var node = this.element.getNode();
          node.removeClass('format-text').addClass('format-complex');
        }

        this.element.addElement(node, this.previous);

        break;

      case 'attribute' :

        this.element.addAttribute(node);
        break;
    }




  }
});