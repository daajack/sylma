
sylma.locale = sylma.locale || {};

sylma.locale.Field = new Class({

  Extends : sylma.crud.Field,
  
  select: function () {
    
    
    this.action.getNode().addClass('visible');
    this.getNode().addClass('visible');
  },
  
  unselect: function () {
    
    this.action.getNode().removeClass('visible');
    this.getNode().removeClass('visible');
  },
});
