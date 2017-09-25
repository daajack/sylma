
sylma.xml.Content = new Class({

  initialize : function(schema, value) {

    this.schema = schema;
    this.value = value;
  },

  toElement : function() {
    
    var editor = this.schema.editor;
    var insert = editor.getObject('insert');
    var self = this;

    var result = new Element('div', {
      html : '[custom]',
      'class' : 'node text',
      events : {
        mousedown : function() {
          
          var doc = document.implementation.createDocument("", "", null);
          doc.appendChild(editor.document.document.documentElement.cloneNode());
          var root = doc.documentElement;
          
          var node = root;
          
          var serializer = new XMLSerializer();
          var container = serializer.serializeToString(root);

          var content = container.substr(0, container.length - 2) + '>' + self.value.trim() + '</' + root.nodeName + '>';

          var doc = editor.parseDocument(content);
          var options = editor.buildElement(doc.documentElement.childNodes[0]);
          var node = insert.element;
          var child = node.addContent(options, insert.previous);
          
//          editor.schema.lookupElement(child, node.ref.type.children);
        }
      }
    });

    result.store('ref', this);

    return result;
  }
});