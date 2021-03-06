
sylma.crud.collection.Filters = new Class({

  Extends : sylma.ui.Container,

  onLoad : function() {

    var filters = this.initFilters();

    this.filters = filters;

    Object.each(this.options.datas, function(vals, name) {

      var container = filters[name];

      if (container) {

        container.setValues(vals);
      }
    });

    this.tmp.each(function(filter) {

      filter.init();
    });

  },

  initFilters: function () {

    var filters = {};

    this.tmp.each(function(filter) {

      var name = filter.options.name;

      if (name) {

        filters[name] = filter;
      }
    });

    return filters;
  },

  toggleShow: function (el, val) {

    var val = this.parent(el, val);
    this.toggleEmpties(val);
  },

  toggleEmpties: function (show) {

    this.tmp.each(function(container) {

      container.tmp.each(function(filter) {

        if (!filter.getValue() && !filter.template) filter.toggleShow(null, show);
      });
    });
  }
});