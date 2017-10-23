
sylma.xml.StepClass = {
  
  Extends : sylma.ui.Template,
  
  onLoad: function () {
    
    if (this.options.arguments)
    {
      this.arguments = JSON.parse(this.options.arguments);
    }
    else
    {
      this.arguments = {};
    }
    
    this.disabled = this.options.disabled === '1';
    this.id = this.options.id;
    this.content = this.options.content;
    this.document = this.options.document;
    this.type = this.options.type;
    this.path = this.options.path;

    this.updateNode();
  },
  
  addTo : function(node) {

    var el = this.initTemplate();

    if (this.options.old)
    {
      el.inject(node, 'before');
    }
    else
    {
      el.inject(node.getParent(), 'top');
    }

    this.initNode({node : el}, true);
    
    var types = {
      add : '+',
      update : '*',
      move : '>',
      remove : '-',
      revision : 'R',
    };
    
    var type = this.getNode('type');
    type.set('html', types[type.get('html')]);

    sylma.ui.loadArray([this]);
  },
  
  select: function () {
    
    var history = this.getParent('history');
    history.load(this, function(doc)
    {
      doc.getNode().addClass('revision');
    });
  },
  
  undo : function()
  {
    switch (this.type)
    {
      case 'revision' : this.undoRevision(); break;
      case 'add' : this.undoAdd(); break;
      case 'update' : this.undoUpdate(); break;
      case 'delete' : this.undoDelete(); break;
      case 'remove' : this.undoRemove(); break;
      case 'move' : this.undoMove(); break;
      default : throw new Error('Unknown step type');
    }
    
    this.disabled = true;
    this.updateNode();
  },
  
  redo : function()
  {
    switch (this.options.type)
    {
      case 'revision' : this.redoRevision(); break;
      case 'add' : this.redoAdd(); break;
      case 'update' : this.redoUpdate(); break;
      case 'delete' : this.redoDelete(); break;
      case 'remove' : this.redoRemove(); break;
      case 'move' : this.redoMove(); break;
      default : throw new Error('Unknown step type');
    }
    
    this.disabled = false;
    this.updateNode();
  },
  
  applyRevision: function (key, step)
  {
    var history = this.getParent('history');
    
    history.load(step, function(doc)
    {
      history.stepsAdded.push({
        type : key
      });
      
      history.save();
    });

  },
  
  undoRevision: function ()
  {
    var history = this.getParent('history');
    this.applyRevision('undo', history.steps[history.steps.indexOf(this) - 1]);
  },
  
  redoRevision: function ()
  {
    this.applyRevision('redo', this);
  },
  
  undoUpdate : function()
  {
    var history = this.getParent('history');
    var node = this.findNode(this.options.path);

    node.updateValue(this.arguments.previous, function()
    {
      history.stepsAdded.push({
        type : 'undo'
      });
      
      history.save();
    });
  },
  
  redoUpdate : function()
  {
    var history = this.getParent('history');
    var node = this.findNode(this.options.path);
    
    node.updateValue(this.options.content, function()
    {
      history.stepsAdded.push({
        type : 'redo'
      });
      
      history.save();
    });
  },
  
  undoAdd: function ()
  {
    var history = this.getParent('history');
    var node;
    
    switch (this.arguments.type)
    {
      case 'text' :
      case 'element' : node = this.findNode(this.options.path + '/' + this.arguments.position); break;
      case 'attribute' : node = this.findNode(this.options.path); break;
    }
    
    node.remove(false);
    
    history.stepsAdded.push({
      type : 'undo'
    });
      
    history.save();
  },
  
  applyAdd: function (type, add) {
    
    var editor = this.getParent('editor');
    var file = this.getParent('file');
    var history = this.getParent('history');
    
    switch (this.arguments.type)
    {
      case 'text' :
      case 'element' :
        
        var path, position;
        
        if (add)
        {
          path = this.path;
          position = this.arguments.position;
        }
        else
        {
          path = this.path.split('/');
          position = path.pop();
          path = path.join('/');
        }

        var node = this.findNode(path);

        if (this.arguments.type === 'element')
        {
          var doc = editor.parseDocument(this.content);
          var options = editor.buildElement(doc.documentElement);
          var child = node.addIndexedChild(options, this.arguments.type, parseInt(position));
          
          file.schema.lookupElement(child, node.ref.type.children);
          file.schema.checkElement(child);
        }
        else
        {
          var options = {
            _alias : 'text',
            content : this.options.content,
          };
          
          var child = node.addIndexedChild(options, this.arguments.type, parseInt(position));
        }

        var step = {
          type : 'add',
          path : child.parentElement.toPath(true),
          token : child.toToken(),
          content : child.toXML(true),
          arguments :
          {
            type : this.arguments.type,
            position : position
          }
        };

        history.applyStep(child.getParent('document').document, step, step.arguments);

        break;
        
      case 'attribute' : 
        
        var node = this.findNode(this.path, 'element');
        var child = node.addAttribute(this.arguments.namespace, this.arguments.name, this.arguments.prefix || '', this.content);
        
        if (node.ref)
        {
          var attribute = file.schema.lookupAttribute(child, node.ref.type.children);
          file.schema.attachAttribute(child, attribute);
        }
        
        var step = {
          type : 'add',
          path : child.parentElement.toPath(true),
          token : child.toToken(),
          content : child.value,
          arguments :
          {
            type : 'attribute',
            namespace : child.namespace,
            prefix : child.prefix,
            name : child.name
          }
        };

        history.applyStep(child.getParent('document').document, step, step.arguments);

        break;
        
      default :
        
        throw new Error('Unknown step type');
    }
    
    history.stepsAdded.push({
      type : type
    });

    file.fireEvent('update');
    
    history.save();
  },
  
  redoAdd: function ()
  {
    this.applyAdd('redo', true);
  },
  
  undoRemove: function () 
  {
    this.applyAdd('undo', false);
  },
  
  redoRemove : function()
  {
    var history = this.getParent('history');
    var node = this.findNode(this.options.path);
    
    node.remove(false);
    history.stepsAdded.push({
      type : 'redo'
    });
    
    history.save();
  },

  applyMove: function (source, target, key, type) 
  {
    var node = this.findNode(source);
    var history = this.getParent('history')
    node.applyMove(target, key - 0);

    history.stepsAdded.push({
      type : type
    });

    history.save();
  },
  
  undoMove: function ()
  {
    var position = this.arguments.position;
    var p = this.arguments.parent
    p += (p !== '/' ? '/' : '') + position;
    
    var target = this.options.path;

    var target = target.split('/');
    var key = target.pop();
    
    this.applyMove(p, target.join('/'), key, 'undo');
  },
  
  redoMove: function () 
  {
    var p = this.arguments.parent;
    var targetPath = (p !== '/' ? p + '/' : p) + this.arguments.position;
    var source = this.options.path;
    
    var target = targetPath.split('/');
    var key = target.pop();

    this.applyMove(source, target.join('/'), key, 'redo');
  },
  
  findNode: function (path, type)
  {
    return this.getParent('file').findNode(path, type || this.arguments.type, this.arguments.name, this.arguments.prefix);
  },
  
  updateNode : function()
  {
    this.getNode().toggleClass('disabled', this.disabled);
  }
};

sylma.xml.Step = new Class(sylma.xml.StepClass);