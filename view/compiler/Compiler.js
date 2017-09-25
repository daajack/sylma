
sylma.view = sylma.view || {};

sylma.view.Compiler = new Class({
  
  Extends : sylma.ui.Container,
  
  onLoad : function()
  {

  },
  
  compile : function(document)
  {
//    var parser = new DOMParser();
//    var document = parser.parseFromString(this.options.document, "text/xml");
    var compiler = new window.titan.Compiler;
//    var compiler = require('./Compiler.js');
//console.log(document);
    var result = compiler.prepare(document);
    
    var View = window.titan.View;
    var tree = {
      hello : 'world',
      person : [
        {
          firstname : 'Charles',
          lastname : 'Xavier'
        },
        {
          firstname : 'Bill',
          lastname : 'Gates'
        }
      ]
    };
    
    var view = eval(result);
//console.log(view.content);
//    var document = parser.parseFromString(view.content, "text/xml");
//    console.log(this.getNode('iframe'));
    function htmlEntities(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    
    this.getNode('iframe').contentDocument.body.innerHTML = view.content;
//    this.getNode('iframe').contentDocument.body.innerHTML = '<pre>' + htmlEntities(view.content) + '</pre>';
//    this.getNode().grab(document.documentElement);

//    this.send(this.options.path, { file : ''}, function(response)
//    {
//    })

  }
});