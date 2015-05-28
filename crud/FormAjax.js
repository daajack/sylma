sylma.crud = sylma.crud || {} ;

sylma.crud.FormAjax = new Class({

  Extends : sylma.crud.Form,

  updateList : function() {

    this.getParent('tab').getObject('list').update();
  },

  submitSuccess : function (result, args) {

    this.getParent('container').hide();
    this.fireEvent('success', [result, args]);
    this.updateList();
  },

  deleteSuccess : function () {

    this.getParent('container').hide();
    this.updateList();
  },

  cancel : function () {

    this.getParent('container').hide();
  }
});
