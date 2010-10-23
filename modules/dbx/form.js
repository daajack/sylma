/* Document JS */

var sylmaCalendarOptions = {
  // months: ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
  // days: ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
  // draggable : false
  'startMonday' : true,
  'format' : '%D %B %Y',
  'theme' : 'osx-dashboard',
  'createHiddenInput' : true,
  'hiddenInputFormat' : '%Y-%m-%d'
};

window.addEvent('domready', function() {
  
  $$('form textarea').each(function(el) {
    
    if (el.get('text') == ' ') el.empty();
  });
  
  $$('input.field-input-date').each(function(el) {
    
    var aValues = el.getAttribute('value').split(';;');
    
    // var sID = el.getAttribute('id');
    // var sName = el.getAttribute('name');
    // var sDate = el.getAttribute('value');
    var sID = aValues[0];
    var sName = aValues[1];
    var sDate = aValues[2];
    
    if (!sDate) {
      
      var oNow = new Date();
      
      var sMonth = oNow.getMonth().toString().length == 1 ? '0' + oNow.getMonth() + 1 : oNow.getMonth() + 1;
      var sDay = oNow.getDate().toString().length == 1 ? '0' + oNow.getDate() : oNow.getDate();
      
      sDate = oNow.getFullYear() + '-' + sMonth + '-' + sDay;
      //alert(sDate);
    }
    
    sylmaCalendarOptions['defaultDate'] = sDate;
    sylmaCalendarOptions['hiddenInputName'] = sName;
    new CalendarEightysix(sID, sylmaCalendarOptions);
    
    // new Calendar({sID : 'D B Y'}, sylmaCalendarOptions);
  });
});