
sylma.view = sylma.view || {};

sylma.view.FileClass = {

  Extends : sylma.xml.File,

  prepare : function(schema) 
  {
    this.parent(schema);

    this.compiler = this.objects.compiler;
    this.addEvent('update', this.compile.bind(this));
    this.fireEvent('update');
  },
  
  compile: function ()
  {
    this.compiler.compile(this.current.options.document);
  }
}

sylma.view.File = new Class(sylma.view.FileClass);