window.aAjax = new Array();

window.addAJAX = function(sHref, sContainer, iWidth, iHeight, sCaller) {
  
  window.aAjax[sContainer] = new AJAX_Form(sHref, sContainer, iWidth, iHeight, sCaller);
}

window.getAJAX = function(sContainer) {
  
  return window.aAjax[sContainer];
}

window.setAJAX = function(sHref, sContainer) {
  
  window.aAjax[sContainer] = new AJAX(sHref, sContainer);
}

function updateAJAXList(sHref, sContainer) {
  
  window.setAJAX(sHref, sContainer);
  new Effect.Highlight(sContainer, {duration:0.8, fps:25, from:0.0, to:1.0, startcolor:'#feff9f'});
}

function AJAX(sHref, sContainer) {
  
  this.load = AJAX_load;
  
  this.run = function(sResponse) {
    
    this.oContainer.innerHTML = sResponse;
    
    return false;
  }
  
  this.sContainer = sContainer;
  this.oContainer = getElement(sContainer);
  this.sHref = sHref;
  
  this.load();
}

function AJAX_load(aPost) {
  
  // for (var i in window.aAjax) if (window.aAjax[i].sHref) window.aAjax[i].unload();
  
  if (window.XMLHttpRequest) // Mozilla, Safari, ...
    var httpRequest = new XMLHttpRequest();
  
  else if (window.ActiveXObject) // IE
    var httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
  
  httpRequest.open('POST', this.sHref, false);
  httpRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
  httpRequest.send(aPost);
  
  if (httpRequest.readyState == 4) this.run(httpRequest.responseText);
  
  return false;
}


function AJAX_Form (sHref, sId, iWidth, iHeight, sCaller) {
  
  this.load = AJAX_load;
  
  this.submit = function() {
    
    this.sHref = this.oContainer.getAttribute('action');
    this.load(this.getForm());
    
    return false;
  }
  
  this.reload = function(sHref) {
    
    if (sHref) this.sHref = sHref;
    this.load();
    
    return false;
  }
  
  this.run = function(sResponse) {
    
    /*
     * aResponse[0] = méthode du formulaire
     * aResponse[1] = action du formulaire
     * aResponse[2] = contenu du formulaire
     **/
    
    sResponse = trim(sResponse);
    var aResponse = sResponse.split('<>');
    
    if (aResponse) {
      
      switch (aResponse[0]) {
        
        case 'script' :
          
          this.unload();
          
          try { eval(aResponse[1]); }
          catch (e) { alert(e); return false; }
          
        break;
        
        case 'redirect' :
          
          // this.unload();
          window.addAJAX(aResponse[1], this.sId, this.iWidth, this.iHeight, this.sCaller);
          
        break;
        
        case 'display' :
          
          this.oContainer.setAttribute('action', aResponse[1]);
          this.oContainer.innerHTML = aResponse[2];
          
          this.oContainer.style.width = this.iWidth + 'px';
          this.oContainer.style.height = this.iHeight + 'px';
          
          if (this.oCaller) {
            
            // Relatif à un élément
            
            var iTop = this.oCaller.offsetTop - this.iHeight - 35;
            var iLeft = this.oCaller.offsetLeft + 35;
            
          } else {
            
            // Centré
            
            var iTop = (window.innerHeight - this.iHeight) / 2;
            var iLeft = (window.innerWidth - this.iWidth) / 2;
            
            this.oContainer.style.position = 'absolute';
            var sClass = this.oContainer.getAttribute('class');
            this.oContainer.setAttribute('class', sClass + ' ajax-center');
          }
          
          this.oContainer.style.top = iTop + 'px';
          this.oContainer.style.left = iLeft + 'px';
          
          if (this.oMessages) this.oMessages.style.top = iLeft + 'px';
          
          Effect.Appear(this.sContainer, {duration:0.4});
          
        break;
      }
    }
  }
  
  this.getForm = function() {
    
    var sPost = '';
    
    var sValue = '';
    var sType = '';
    var oField = '';
    
    var aElements = getElement(this.sContainer).elements;
    
    for (i = 0; i < aElements.length; i++) {
      
      oField = aElements[i];
      if (oField.name) {
        
        if (oField.tagName == 'INPUT') {
          
          sType = oField.type;
          
          if (sType == 'text' || sType == 'hidden') sValue = oField.value;
          else if ((sType == 'checkbox' || sType == 'radio') && oField.checked) {
            
            sValue = oField.value;
          }
          
        } else if (oField.tagName == "SELECT") sValue = oField.options[oField.selectedIndex].value;
        
        sPost += oField.name + "=" + sValue + "&";
      }
    }
    
    return sPost;
  }
  
  this.unload = function() {
    
    var self = this;
    Effect.Fade(this.sContainer, {duration:0.4}, self.empty());
  }
  
  this.empty = function() {
    
    // this.oContainer.innerHTML = '';
    // this.sHref = '';
  }
  
  this.sHref = sHref;
  
  this.sId = sId;
  
  this.sContainer = sId + '-container';
  this.oContainer = getElement(this.sContainer);
  
  this.sCaller = sCaller;
  this.oCaller = getElement(sCaller);
  
  this.oMessages = getElement(sId + '-messages');
  
  this.iWidth = iWidth;
  this.iHeight = iHeight;
  
  this.load();
}
