
sylma.xml.HistoryClass = {

  Extends : sylma.ui.Container,
  steps : [],
  stepsAdded : [],
  sendSpeed : 60000,
  sending : false,
  timer : null,
  full : false,

  onLoad : function() 
  {
    this.add('steps');

    if (this.options.steps)
    {
      this.buildSteps(this.options.steps.reverse());
    }
    
    var container = this.getObject('steps')[0].getNode();
    container.addEvent('scroll', this.checkScroll.bind(this));
    
    this.container = container;
  },
  
  buildSteps: function (steps, old) {
    
    var container = this.objects.steps[0];
    
    steps.each(function(options)
    {
      var name;

      switch (options.type)
      {
        case 'revision' : name = 'revision'; break;
        default : name = 'action';
      }
      
      options.old = old;
      
      container.add(name, options);
    });

    this.steps = container.tmp;
  },
    
  load: function (step) 
  {
    
    this.save();
    var editor = this.getParent('editor');

    if (step.id)// && step.id < 98
    {
      this.send(this.options.pathLoad, {
        step : step.id,
        file : editor.file,
  //      update : editor.updateTime
      }, function(response) {

        if (response.error) {

  //        editor.setDisabled();
          sylma.ui.showMessage('Cannot load revision');
        }
        if (response.content) {

          var doc = editor.prepareDocument(response.content, true);

          doc.getNode().addClass('revision');
          console.info('File loaded');
        }
        else {

          throw new Error('Bad request');
        }

        this.sending = false;

      }.bind(this));
    }
    else
    {
      var key = this.steps.indexOf(step);
      var k = key;
      var todos = new Array();
      var content;
      
      while (k >= 0)
      {
        var step = this.steps[k];

        if (step.document)
        {
          content = step.document;
          break;
        }
        else
        {
          todos.push(step);
        }
        
        k--;
      }
      
      if (!content)
      {
        throw new Error('No document found');
        // || editor.original
      }
      
      var options = editor.parseDocument(content);

      this.applySteps(options, todos.reverse());
      var doc = editor.prepareDocument(options);
      
      doc.getNode().addClass('revision');
    }
  },
  
  goCurrent: function () {
    
    var editor = this.getParent('editor');
    var documents = editor.container.objects.document;

    documents.each(function(doc)
    {
      doc.hide();
    });
    
    documents[0].show();
  },
  
  checkScroll: function()
  {
    if (this.full) return;
    
    var container = this.container;
    
    if (this.sending)
    {
      window.setTimeout(this.checkScroll.bind(this), 1000);
    }
    else
    {
      if (container.scrollHeight - container.getScroll().y < container.getSize().y * 2)
      {
        this.sending = true;
        var editor = this.getParent('editor');
        
        var history = this;

        this.send(history.options.pathSteps, {
          file : editor.file,
          offset : this.steps.length
        }, function(response)
        {
          if (response.content)
          {
            var container = history.objects.steps[0];
            container.tmp.reverse();
            history.buildSteps(response.content, true);
            container.tmp.reverse();
          }
          else
          {
            history.full = true;
          }
          
          history.sending = false;
        }, true);
      }
    }
  },
  
  findElement: function (result, pathString)
  {
    var path = pathString.split('/').filter(function(k) { return k; });

    while (path.length) 
    {
      var position = path.shift();
      result = result.childNodes[position];
    }

    if (!result) {

      throw new Error('Cannot find element in : ' + pathString);
    }

    return result;
  },
  
  clear: function () {
    
    var editor = this.getParent('editor');

    this.send(this.options.pathClear, { file : editor.file });
  },
  
  applySteps: function (doc, steps) {
    
    var history = this;
    
    log('apply ' + steps.length + ' steps');
    
    steps.each(function(step)
    {
      history.applyStep(doc, step, step.arguments);
    });
  },
  
  applyStep : function(doc, step, args)
  {
//    log('apply step ' + step.options.display);
    var el = this.findElement(doc.documentElement, step.path);

    switch (args.type) 
    {
      case 'element' : this.updateElement(doc, el, step, args); break;
      case 'text' : this.updateText(el, step, args); break;
      case 'attribute' : this.updateAttribute(el, step, args); break;
      default : throw new Error('Unknown step type');
    }
  },
  
  insertElement : function(el, content, position)
  {
    if (position !== null) {

      position--;

      if (position < el.childNodes.length)
      {
//log(el, content, position);
        el.insertBefore(content, el.childNodes[position + 1]);
      }
      else
      {
        el.appendChild(content)
      }
    }
    else {

      el.appendChild(content);
    }
  },
  
  updateElement : function(doc, el, step, args) {
    
    var editor = this.getParent('editor');
    
    switch (step.type) {

      case 'add' :

        var position = args.position;
        var content = editor.parseDocument(step.content).documentElement;
//console.log(el);

        this.insertElement(el, content, position);

        break;

      case 'move' :
        
        var path = args.parent;

        el.parentNode.removeChild(el);
        
        var parent = path === '/' ? doc.documentElement : this.findElement(doc.documentElement, path);
        position = args.position;
        
        this.insertElement(parent, el, position);
        
        break;

      case 'remove' :

        el.remove();
        break;

      default : this.launchException('Unknown step type');
    }
  },

  updateText : function(el, step, args) {

    switch (step.type) {

      case 'add' :

        this.insertElement(step.content, args.position);
        break;

      case 'update' :

        var position = args.position;
        el.childNodes[position].nodeValue = step.content;
        break;

      case 'remove' :

        position = args.read('position');
        el.remove(el.childNodes[position]);
        break;

      default : this.launchException('Unknown step type');
    }
  },

  updateAttribute : function(el, step, args) {

    switch (step.type) {

      case 'add' :
      case 'update' :

        if (args.name.indexOf(':') !== -1) {

          el.setAttributeNS(args.namespace, args.name, step.content);
        }
        else {
          
          el.setAttribute(args.name, step.content);
        }

        break;

      case 'remove' :
        
        if (args.prefix)
        {
          el.removeAttributeNS(args.namespace, args.name);
        }
        else
        {
          el.removeAttribute(args.name);
        }
        break;

      default : this.launchException('Unknown step type');
    }
  },

  addStep: function(type, path, display, content, args) 
  {
    var options = {
      type : type,
      path : path,
      display : display,
      update : new Date,
      content : content,
      arguments : JSON.stringify(args)
    };
    
    var container = this.objects.steps[0];
    
    var step = container.add('action', options);
    this.steps.push(step);

    var steps = this.steps;
    var k = steps.length;
    var clear = false;
//console.log(steps, k);
    while (k)
    {
      k--;
//console.log(k, steps[k].disabled, steps[k])
      if (steps[k].disabled) 
      {
        steps[k].remove(); clear = true; 
      }
    }

    if (clear)
    {
      this.stepsAdded.push({
        type : 'clear'
      });
    }

    this.stepsAdded.push(options);

    this.save();
  },
  
  save: function () {
    
    var history = this;
    
    if (this.timer)
    {
      window.clearTimeout(this.timer);
    }
    
    this.timer = window.setTimeout(function()
    {
      history.timer = null;
      history.validateSave();
    }, 500);
  },

  validateSave: function() 
  {
//return;
    if (!this.sending && this.stepsAdded.length) 
    {
      this.sending = true;

      var editor = this.getParent('editor');

      var steps = this.stepsAdded;
      this.stepsAdded = [];

      this.send(this.options.pathUpdate, {
        steps : steps,
        file : editor.file,
        update : editor.updateTime
      }, function(response) {

        if (response.error) {
          
          editor.setDisabled();
        }
        if (response.content) {

          console.info('File saved');
        }
        else {

          throw new Error('Bad request');
        }
        
        this.sending = false;

      }.bind(this));
    }
    else
    {
      window.setTimeout(this.save.bind(this), this.sendSpeed);
    }
  },
  
  stepBackward : function()
  {
    var steps = this.steps || [];
    var k = steps.length;

    do
    {
      k--;
    }
    while (steps[k] && steps[k].disabled);

    if (steps[k]) steps[k].undo();
    else sylma.ui.showMessage('No step');
  },
  
  stepForward : function()
  {
    var steps = this.steps || [];
    var k = steps.length - 1;

    while (steps[k] && steps[k].disabled)
    {
      k--;
    }

    k++;

    if (steps[k]) steps[k].redo();
    else sylma.ui.showMessage('No step');
  }
};

sylma.xml.History = new Class(sylma.xml.HistoryClass);