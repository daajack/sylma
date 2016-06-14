
sylma.crud.DateFilter = new Class({

  Extends: sylma.crud.collection.Filter,
  showFormat : '%e%o %B %Y',

  prepare: function () {

    this.parent();

    this.prepareContainer();
    this.prepareWidget();
  },

  prepareContainer: function () {

    var inputs = this.getNode().getElements('input, select');

    this.select = inputs[1];
    this.input = inputs[2];

    inputs.set('name');
  },

  setSelect: function (val) {

    var showFormat = this.showFormat;
    var date = Date.parse(val);

    this.select.set('value', date ? date.format(showFormat) : '');
  },

  setValue: function (val) {

    var inputFormat = '%Y-%m-%d'; //%H:%m:%S
    var date = Date.parse(val);

    this.input.set('value', date ? date.format(inputFormat) : '');
    this.setSelect(val);
  },

  prepareWidget : function () {

    var el = this.select;

    var options = {

      pickerClass : 'datepicker_jqui',
      format : this.showFormat,
      yearsPerPage : 16,
      //columns : 5,
      onSelect: function(val) {

        this.setValue(val);

      }.bind(this),
    };

    var picker = new Picker.Date(el, options);
    picker.addEvent('select', this.update.bind(this));
  },

  clone: function () {

    var result = this.parent();

    return result;
  },
});