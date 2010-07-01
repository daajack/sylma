var oContent = /* Document JS */

function addSlashes(sValue) {
  
  sValue = sValue.replace(/\\/g,"\\\\");
  sValue = sValue.replace(/\'/g,"\\'");
  //sValue = sValue.replace(/\"/g,"\\\"");
  
  return sValue;
}

function removeSlashes(sValue) {
  
  sValue = sValue.replace(/\\/g,"\\\\");
  sValue = sValue.replace(/\'/g,"\\'");
  sValue = sValue.replace(/\"/g,"\\\"");
  
  return sValue;
}

$(document).ready(function() {
  
  if ($.browser.msie) {
    
    $(document.body).append($.ajax({'url' : '/xml/fuck-ie.xml', 'async' : false}).responseText);
    
    var iVerticalMove = 45;
    
    $('#browser-warning').hover(
      function() {
        
        $('#browser-warning').animate({'top' : '+=' + iVerticalMove + 'px', 'opacity' : '0.9', 'width' : '300px'}, 500);
      },
      function() {
        
        $('#browser-warning').animate({'opacity' : '0.9'}, 3000).animate({'top' : '-=' + iVerticalMove + 'px', 'opacity' : '0.65', 'width' : '30px'});
      }
    );
    
    $('#browser-warning').hover()
  }
});

function dsp(obj) {
  
  $('#explorer').append(dumpProps(obj, undefined, 0));
}

function HTML_Tag(sName, mContent, aAttributes) {
  
  var oElement = document.createElement(sName);
  // typeof sContent == 'string';
  
  if (mContent) {
    
    if (jQuery.isFunction(mContent)) oElement.appendChild(mContent);
    else oElement.innerHTML = mContent;
  }
  
  if (aAttributes) for (var i in aAttributes) oElement.setAttribute(i, aAttributes[i]);
  
  oElement.add = function() {
    
    for (var i = 0; i < arguments.length; i++) {
      
      if (is_array(arguments[i])) for (var j in arguments[i]) this.add(arguments[i][j]);
      else this.appendChild(arguments[i]);
    }
  }
  
  return oElement;
}

/*
 * AJAX action update
 **/
function updateAction(sId, sHref, oFunction, sStyle) {
  
  $.ajax({
    
    type: 'GET',
    url: sHref,
    contentType: 'text/xml',
    dataType: 'xml',
    // async: false,
    success: function(oDocument) {
      
      oDocument = $.extend(oDocument, xpath);
      // xpath
      var oContent = oDocument.getElementsByTagName('content')[0].childNodes[1];
      // var oContent = oDocument.getElementsByTagName('content')[0];
      var oMessages = oDocument.getElementsByTagName('messages')[0].childNodes[1];
      
      if (oMessages) {
        
        $(oMessages).css('display', 'none');
        $('#content').prepend(oMessages);
        $(oMessages).show('slow');
        
        window.setTimeout(function() { $(oMessages).hide('slow'); }, 7000);
      }
      
      if (sId) {
        
        $('#' + sId).empty();
        var oResult = document.importNode(oContent, true);
        
        switch (sStyle) {
          
          case 'fade' : $('#' + sId).append(oResult).fadeIn(); break;
          default : $('#' + sId).append(oResult); break;
        }
      }
      
      if (oFunction) oFunction(oContent);
    },
    complete : function(sResult, sState) { // sState : parsererror, success
      
      if (sState == 'parsererror') alert('XML invalide');
    }
  });
  
  return false;
}

function htmlspecialchars_decode(string, quote_style) {

   string = string.toString();
  
   string = string.replace(/&amp;/g, '&');
   string = string.replace(/&lt;/g, '<');
   string = string.replace(/&gt;/g, '>');
  
   if (quote_style == 'ENT_QUOTES') {
       string = string.replace(/&quot;/g, '"');
       string = string.replace(/&#039;/g, '\'');
   } else if (quote_style != 'ENT_NOQUOTES') {
       string = string.replace(/&quot;/g, '"');
   }
  
   return string;
}

var xpath = {
  
  'get' : function(sPath) {
    
    var iterator = this.evaluate(sPath, this, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null );
    
    var oNode = iterator.iterateNext();
    var aResult = new Array();
    
    while (oNode) {
      
      aResult.push(oNode);
      oNode = iterator.iterateNext();
    }
    
    if (aResult.length) return aResult[0];
    else return null;
  }
};

/*
 * AJAX simple update
 **/
function updateContainer(sId, sHref) {
  
  var sResult = $.ajax({'url' : sHref, 'async' : false}).responseText;
  $('#' + sId).html(sResult);
  
  return false;
}

/*
 * Modification de l'action et soumission d'un formulaire
 **/
 /*
function updateAction(sAdd) {
  
  var sFormId = 'main_form';
  
  var nForm = document.getElementById(sFormId);
  nForm.action += '/' + sAdd;
  
  nForm.submit();
}
*/
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

// Supprime les espaces inutiles en début et fin de la chaîne passée en paramètre.

function trim(aString) {
  
  return aString.replace(/^\s+/, "").replace(/\s+$/, "");
}

function dumpProps(obj, parent, recursion) {
  
  var sContent = '';
  recursion++;
  
  for (var i in obj) {
    
    try {
      if (parent) {
        
        sContent = +parent + "." + i + " : " + obj[i];
        
      } else {
        
        sContent += i + " : " + obj[i];
        // if (!confirm(sContent)) { return; }
      }
      
      if (typeof obj[i] == "object" && recursion < 1) {
        
        sContent += '<div style="margin-left: 1em">';
        
        if (parent) sContent += dumpProps(obj[i], parent + "." + i, recursion);
        else sContent += dumpProps(obj[i], i, recursion);
        
        sContent += '</div>';
      }
      
      sContent += '<br/>';
      
    } catch (t) { sContent += 'Impossible !<br/>'; }
  }
  
  return sContent;
}

function is_array (mixed_var) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Legaev Andrey
    // +   bugfixed by: Cord
    // +   bugfixed by: Manish
    // +   improved by: Onno Marsman
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: In php.js, javascript objects are like php associative arrays, thus JavaScript objects will also
    // %        note 1: return true  in this function (except for objects which inherit properties, being thus used as objects),
    // %        note 1: unless you do ini_set('phpjs.objectsAsArrays', true), in which case only genuine JavaScript arrays
    // %        note 1: will return true
    // *     example 1: is_array(['Kevin', 'van', 'Zonneveld']);
    // *     returns 1: true
    // *     example 2: is_array('Kevin van Zonneveld');
    // *     returns 2: false
    // *     example 3: is_array({0: 'Kevin', 1: 'van', 2: 'Zonneveld'});
    // *     returns 3: true
    // *     example 4: is_array(function tmp_a(){this.name = 'Kevin'});
    // *     returns 4: false
 
    var key = '';
    var getFuncName = function (fn) {
        var name = (/\W*function\s+([\w\$]+)\s*\(/).exec(fn);
        if (!name) {
            return '(Anonymous)';
        }
        return name[1];
    };
 
    if (!mixed_var) {
        return false;
    }
 
    // BEGIN REDUNDANT
    this.php_js = this.php_js || {};
    this.php_js.ini = this.php_js.ini || {};
    // END REDUNDANT
 
    if (typeof mixed_var === 'object') {
 
        if (this.php_js.ini['phpjs.objectsAsArrays'] &&  // Strict checking for being a JavaScript array (only check this way if call ini_set('phpjs.objectsAsArrays', 0) to disallow objects as arrays)
            (
            (this.php_js.ini['phpjs.objectsAsArrays'].local_value.toLowerCase &&
                    this.php_js.ini['phpjs.objectsAsArrays'].local_value.toLowerCase() === 'off') ||
                parseInt(this.php_js.ini['phpjs.objectsAsArrays'].local_value, 10) === 0)
            ) {
            return mixed_var.hasOwnProperty('length') && // Not non-enumerable because of being on parent class
                            !mixed_var.propertyIsEnumerable('length') && // Since is own property, if not enumerable, it must be a built-in function
                                getFuncName(mixed_var.constructor) !== 'String'; // exclude String()
        }
 
        if (mixed_var.hasOwnProperty) {
            for (key in mixed_var) {
                // Checks whether the object has the specified property
                // if not, we figure it's not an object in the sense of a php-associative-array.
                if (false === mixed_var.hasOwnProperty(key)) {
                    return false;
                }
            }
        }
 
        // Read discussion at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_is_array/
        return true;
    }
 
    return false;
}
