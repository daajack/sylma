
sylma.locale = sylma.locale || {};

sylma.locale.Container = new Class({

  Extends : sylma.ui.Container,
  
  onLoad: function () {
      
    var actions = this.objects.actions.objects;
    var fields = this.objects.inputs.objects;

    Object.each(fields, function(field, key) {

      field.action = actions[key];
    });
    
    this.fields = fields;
    
    Object.values(fields).pick().select();
  },
  
  select: function (lang) {
    
    this.unselect();
    this.objects.inputs.objects[lang].select();
  },
  
  unselect: function () {
    
    Object.each(this.fields, function(field) {
      
      field.unselect();
    });
  },

});
