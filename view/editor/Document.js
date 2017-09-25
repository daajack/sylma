
sylma.view = sylma.view || {};

sylma.view.Document = new Class({
  
  Extends : sylma.xml.Document,
  
  onReady: function ()
  {
    if (!this.sylma.template.classes) {

      //var el = this.getParent('element');
      var doc = this.getParent('editor').container.buildObject('document');
//console.log(doc);
      this.sylma = doc.sylma;
      this.sylma.parents.document = this;
      this.buildTemplate = doc.buildTemplate.bind(this);
    }
    
    this.parent();
  },
  
  onLoad: function()
  {
    this.parent();
//    console.log(this);
    var document = this.options.document;
    var container = this.getParent('explorer').getObject('preview');
    var editor = this.getParent('editor');
    
    if (!container.objects.compiler)
    {
      container.update(function()
      {
        var compiler = container.objects.compiler;
  //console.log(compiler);
        compiler.compile(document);

        editor.addEvent('update', function()
        {
  //console.log(document);
          compiler.compile(editor.current.options.document);
        });

      });
    }
  }
});