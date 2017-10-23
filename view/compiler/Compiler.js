
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
    var iframe = this.getNode('iframe');
//    var compiler = require('./Compiler.js');
//console.log(document);
//console.log(document.documentElement.innerHTML);
    compiler.prepareDOM(document, 0, function(view, result)
    {
      var View = window.titan.View;
      var tree = {
        hello : 'world',
        items : [
          {
            name : 'Charles',
            lastname : 'Xavier'
          },
          {
            name : 'Bill',
            lastname : 'Gates'
          }
        ]
      };
      
      var scripts = [];
      
//      console.log(result, result[1].content);

      var result = eval(result[1].content);
//  console.log(result);
  //    var document = parser.parseFromString(view.content, "text/xml");
  //    console.log(this.getNode('iframe'));
      function htmlEntities(str) {
          return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
      }

      iframe.contentDocument.body.innerHTML = result;
  //    this.getNode('iframe').contentDocument.body.innerHTML = '<pre>' + htmlEntities(view.content) + '</pre>';
  //    this.getNode().grab(document.documentElement);

  //    this.send(this.options.path, { file : ''}, function(response)
  //    {
  //    })
    });
  }
});