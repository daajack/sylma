/* Document JS */

/*
 * Alias de la fonction getElementById()
 **/
function getElement(sId) { return document.getElementById(sId); }
function createElement(sId) { return document.createElement(sId); }

/*
 * Modification de l'action et soumission d'un formulaire
 **/
function updateAction(sAdd) {
  
  var sFormId = 'main_form';
  
  var nForm = document.getElementById(sFormId);
  nForm.action += '/' + sAdd;
  
  nForm.submit();
}

/*
 * Arrondi d'un nombre en spécifiant la précision
 **/
function round(fNumber, iPrecision) {
  
  if (!iPrecision) iPrecision = 2;
  
  var iMultiple = 10 * iPrecision;
  var fResult = parseFloat(Math.round(fNumber * iMultiple) / iMultiple);
  
  return fResult.toFixed(iPrecision)
}

/*
 * TODO: Compléter la fonction pour afficher correctement les prix
 **/
function formatPrice(fNumber) {
  
  fNumber = round(fNumber);
  
  var sNumber = sResult = fNumber.toString();
  var iCount = 0;
  
  for (var i = sNumber.length - 4; i > 0; i--) {
    
    if (iCount == 3) {
      iCount = 0;
      sResult += "'";
    }
  }
}

/*
 * Ouverture d'une popup centrée
 **/
function openPopup(href, title, width, height) {
  
  var left = (screen.width - width) / 2;
  var top = (screen.height - height) / 2;
  
  window.open(href, title, 'toolbar=no, location=no, scrollbars=yes, resizable=no, copyhistory=no, width=' + width + ', height=' + height + ', left=' + left + ', top=' + top).focus();
  
  return false;
}
