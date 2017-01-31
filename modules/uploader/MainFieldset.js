
sylma.uploader.MainFieldset = new Class({

  Extends : sylma.uploader.MainPosition,

  sendComplete : function(response, test) {

    if (response.content) {
      
      var node = sylma.ui.importNode(response.content);
      this.fieldset.getObject('content').updateContent(response, node);
      this.position++;
    }
    
    this.parent(response, test);
  }
});