
sylma.crud.foreign = sylma.crud.foreign || {};

sylma.crud.foreign.FilterContainer = new Class({

  Extends : sylma.crud.collection.FilterContainer,
  filters : [],

  onLoad: function () {

    this.initFilters();
  },

  initFilters: function () {

    var filters = [];

    if (this.tmp.length > 20) {

      console.log('Too many values in ' + this.options.name + ' (' + this.tmp.length + ')');
    }

    this.tmp.each(function(filter) {

      filter.setValue();
      filters[filter.getKey()] = filter;
    });

    this.filters = filters;
  },

  update: function () {

    var val = this.tmp.some(function(filter) {

      return filter.getValue();
    });
//console.log(val);
    //if (!this.getParent().isShowed()) {

      this.toggleShow(this.getNode('empty'), !val);
    //}
  },

  setValues: function (vals) {

    Object.each(vals[0].children, function(val, key) {

      val = val.toInt();

      this.filters[key].setValue(!!val);

      if (val) {

        this.filters[key].show();
      }

    }, this);

    this.update();
  },

  clear: function () {

    this.tmp.each(function(filter) {

      filter.clear();
    });
  },

  addFilter: function () {


  },
});