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
    
    replace : function(sPath) {
      
      this.parent(this.getPath(), 'resources[\'' + sPath + '\']', {'resource' : sPath});
    },
    
    update : function() {
      
      this.parent({'resource' : this.path});
    }
    
  }),
  
  'tools' : new Class({
    
    Extends: sylma.classes['menu-common'],
    resource : undefined,
    
    'show' : function(eTarget) {
      
      if (this.firstShow(eTarget)) {
        
        this.resource = eTarget.retrieve('ref-object');
        this.sub.rights.hide(true);
        
      }
      
      return this.parent(eTarget);
    },
    
    'hide' : function(bQuick) {
      
      this.updateRights();
      
      for (var i in this.sub) this.sub[i].hide(bQuick);
      
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
          
          var eInput = this.sub.rights.node.getElement('input[name=resource-' + sModeKey + '-' + sRightKey + ']');
          
          if (sModeValue & sRightValue) eInput.set('checked', 'checked');
          else eInput.removeProperty('checked');
          
        }, this);
      }, this);
      
      // owner, group
      
      this.sub.rights.node.getElement('input[name=resource-owner]').set('value', this.resource.owner);
      this.sub.rights.node.getElement('input[name=resource-group]').set('value', this.resource.group);
    },
    
    updateRights : function() {
      
      if (this.sub.rights.isOpen) {
        
        var oResource = this.resource;
        
        var sOwner = this.sub.rights.node.getElement('input[name=resource-owner]').get('value');
        var sGroup = this.sub.rights.node.getElement('input[name=resource-group]').get('value');
        
        var aNodes = this.sub.rights.node.getElements('input[type=checkbox]');
        
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
      
      var bChecked, aNodes = this.sub.rights.node.getElements('input' + sName + '[type=checkbox]');
      
      $each(aNodes, function(eInput) { if (eInput.get('checked')) bChecked = true; });
      $each(aNodes, function(eInput) {
        
        if (bChecked) eInput.removeProperty('checked');
        else eInput.set('checked', 'checked');
      });
      
      return false;
    },
    
    loadName : function() {
      
      var oInput = this.sub.editName.node.getElement('input[name=resource-name]');
      
      oInput.set('value', this.resource.name);
      oInput.focus();
    },
    
    updateName : function() {
      
      var sName = this.sub.editName.node.getElement('input[name=resource-name]').get('value');
      var oResource = this.resource;
      
      if (sName) {
        
        var oArguments = {
          'resource' : this.resource.path,
          'directory' : this.resource.isDirectory(),
          'name' : sName
        }
        
        var oCaller = this;
        var oRequest = new sylma.classes.request({
          
          'url' : sylma.explorer.pathInterface + oCaller.pathName + '.action',
          'data' : oArguments,
          'onSuccess' : function(sResult, oResult) {
            
            oCaller.onUpdateName(this.parseAction(oResult), oResource, oArguments);
          }
          
        }).send();
      }
      
      return false;
    },
    
    onUpdateName : function(mResult, oResource, oArguments) {
      
      var sPath = mResult.get('text');
      
      if (sPath) oResource.replace(sPath);
    },
    
    deleteResource : function() {
      
      var oArguments = {
        'resource' : this.resource.path,
        'directory' : this.resource.isDirectory()
      }
      
      var oCaller = this;
      var oRequest = new sylma.classes.request({
        
        'url' : sylma.explorer.pathInterface + oCaller.pathDelete + '.action',
        'data' : oArguments,
        'onSuccess' : function(sResult, oResult) {
          
          oCaller.onDeleteResource(this.parseAction(oResult), oResource);
        }
        
      }).send();
      
      return false;
    },
    
    onDeleteResource : function(mResult, oResource) {
      
      if (strtobool(mResult.get('text'))) oResource.remove();
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
      
      sylma.explorer.tools.reset();
      
      return this.parentObject.update({'path' : this.path});
    }
  })
  
});

