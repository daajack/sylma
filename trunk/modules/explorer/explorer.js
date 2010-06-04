/* $Id$ : */

var sExplorerClasses = 'explorer-classes';

window.addEvent('domready', function() {
  
  sylma.loadTree('explorer', SYLMA_EXPLORER_PATH + '.txt');
});

var oExplorerClasses = sylma[sExplorerClasses] = {
  
  'resource' : new Class({
    
    Extends : sylma.classes.layer,
    initialize : function(sPath) {
      
      this.path = sPath
    },
  }),
  
  'tools' : new Class({
    
    Extends: sylma.classes['menu-common'],
    resource : undefined,
    
    'show' : function(eTarget) {
      
      if (this.firstShow(eTarget)) {
        
        this.resource = eTarget.retrieve('ref-object');
        this.rights.hide(true);
        
      }
      
      return this.parent(eTarget);
    },
    
    'hide' : function(bQuick) {
      
      this.updateRights();
      
      this.rights.hide(bQuick);
      this.editName.hide(bQuick);
      
      return this.parent(bQuick);
    },
    
    loadRights : function() {
      
      // mode
      
      var aMode = {
        'owner' : parseInt(this.resource.mode[0]),
        'group' : parseInt(this.resource.mode[1]),
        'public' : parseInt(this.resource.mode[2])};
      
      var aRights = {
        'read' : SYLMA_MODE_READ,
        'write' : SYLMA_MODE_WRITE,
        'execution' : SYLMA_MODE_EXECUTION};
      
      $each(aMode, function(sModeValue, sModeKey) {
        $each(aRights, function(sRightValue, sRightKey) {
          
          var eInput = this.rights.node.getElement('input[name=resource-' + sModeKey + '-' + sRightKey + ']');
          
          if (sModeValue & sRightValue) eInput.set('checked', 'checked');
          else eInput.removeProperty('checked');
          
        }, this);
      }, this);
      
      // owner, group
      
      this.rights.node.getElement('input[name=resource-owner]').set('value', this.resource.owner);
      this.rights.node.getElement('input[name=resource-group]').set('value', this.resource.group);
    },
    
    updateRights : function() {
      
      if (this.rights.isOpen) {
        
        var oResource = this.resource;
        
        var sOwner = this.rights.node.getElement('input[name=resource-owner]').get('value');
        var sGroup = this.rights.node.getElement('input[name=resource-group]').get('value');
        
        var aNodes = this.rights.node.getElements('input[type=checkbox]');
        
        var iMode = 0, iTarget = 0, aMode = [0, 0, 0], aModes = [SYLMA_MODE_READ, SYLMA_MODE_WRITE, SYLMA_MODE_EXECUTION];
        
        $each(aNodes, function(eInput) {
          
          if (eInput.get('checked')) aMode[iTarget] |= aModes[iMode];
          
          if (iTarget == 2) {
            
            iMode++;
            iTarget = 0;
            
          } else iTarget++;
          
        });
        
        var sMode = aMode.join('');
        
        if (sOwner != this.resource.owner || sGroup != this.resource.group || sMode != this.resource.mode) {
          
          var oArguments = {
            'resource' : oResource.path,
            'directory' : oResource.isDirectory(),
            'owner' : sOwner,
            'group' : sGroup,
            'mode' : sMode }
          
          var oCaller = this;
          var oRequest = new sylma.classes.request({
            
            'url' : sylma.explorer.pathInterface + oCaller.pathRights + '.action',
            'data' : oArguments,
            'onSuccess' : function(sResult, oResult) {
              
              oCaller.onUpdateRights(this.parseAction(oResult), oCaller.resource, oArguments);
            }
          });
          
          return oRequest.send();;
        }
      }
      
      return false;
    },
    
    onUpdateRights: function(mResult, oResource, oArguments) {
      
      var bResult = sylma.inttobool(mResult.get('text'));
      
      if (bResult) {
        
        oResource.owner = oArguments.owner;
        oResource.group = oArguments.group;
        oResource.mode = oArguments.mode;
        
        //this.resetParent();
      }
    },
    
    invertRights : function(sName) {
      
      if (sName) sName = '[' + sName + ']';
      else sName = '';
      
      var bChecked, aNodes = this.rights.node.getElements('input' + sName + '[type=checkbox]');
      
      $each(aNodes, function(eInput) { if (eInput.get('checked')) bChecked = true; });
      $each(aNodes, function(eInput) {
        
        if (bChecked) eInput.removeProperty('checked');
        else eInput.set('checked', 'checked');
      });
      
      return false;
    },
    
    loadName : function() {
      
      var oInput = this.editName.node.getElement('input[name=resource-name]');
      
      oInput.set('value', this.resource.name);
      oInput.focus();
    },
    
    updateName : function() {
      
      var sName = this.editName.node.getElement('input[name=resource-name]').get('value');
      //alert('ok + ' + sName);
      if (sName) {
        
        var oArguments = {
          'resource' : this.resource.path,
          'directory' : this.resource.isDirectory(),
          'name' : sName}
        
        var oCaller = this;
        var oRequest = new sylma.classes.request({
          
          'url' : sylma.explorer.pathInterface + oCaller.pathName + '.action',
          'data' : oArguments,
          'onSuccess' : function(sResult, oResult) {
            
            oCaller.onUpdateName(this.parseAction(oResult), oCaller.resource, oArguments);
          }
        }).send();
      }
      
      return false;
    },
    
    updateFile : function() {
      
      var oArguments = {'resource' : this.resource.path}
      
      var oCaller = this;
      var oResource = this.resource;
      var sPath = sylma.explorer.pathInterface + '/update';
      
      this.request = new sylma.classes.request({
        
        'url' : sPath + '.action',
        'data' : oArguments,
        'onSuccess' : function(sResult, oResult) {
          
          var mContent = sylma.importNode(this.parseAction(oResult).getFirst());
          
          mContent.setStyle('opacity', 0.2);
          mContent.replaces(oResource.node);
          
          var oSubResult = new Request.JSON({
            
            'url' : sPath + '.txt', 
            'onSuccess' : function(oResponse) {
              
              sylma.explorer.mozaic.resources[oResource.path] = sylma.buildRoot(oResponse, sylma.explorer.mozaic);
              mContent.setStyle('opacity', 1);
              
          }}).get();
        }
      }).post();
    },
    
    onUpdateName : function(mResult) {
      
      //var bResult = sylma.inttobool(mResult.get('text'));
      
      if (bResult) {
        
        alert('Nom mis-à-jour');
      }
    }
  })
};

$extend(oExplorerClasses, {
  
  'file' : new Class({
    
    Extends: oExplorerClasses.resource,
    isDirectory : function() { return 0; }
  }),
  
  'directory' : new Class({
    
    Extends: oExplorerClasses.resource,
    isDirectory : function() { return 1; },
    
    open : function() {
      
      var layer = this.parentObject;
      
      sylma.explorer.tools.reset();
      
      return layer.update({'path' : this.path});
    }
  })
  
});

