
sylma.xml.Update = new Class({

  Extends : sylma.ui.Template,

  element : null,
  previous : null,
  previousValue : '',

  onLoad : function () {

    this.node = this.getNode();
  },

  attachNode: function (node, target, callback) {

    this.validated = false;
    
    this.schema = this.getParent('editor').schema;
    this.callback = callback;

    this.element = node;
    var container = this.getParent('editor').getNode();
    //node.grab(this.node, 'after');
    var np = target.getPosition();
    var cp = container.getPosition();

    this.node.setStyles({
      left : np.x - cp.x,
      top : np.y - cp.y,
    });

    this.show();

    this.getNode()
      .removeClass('attribute')
      .removeClass('text')
      .addClass(node.element);

    var input = this.getNode('input');
    input.set('value', node.value);
    
    input.focus.delay(200, input);
    input.select.delay(200, input);
  },

  updateValue: function () {

  },

  validate: function () {

    if (!this.validated) {

      this.validated = true;

      this.element.updateValue(this.getNode('input').get('value'), this.callback);
      this.hide();
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
});