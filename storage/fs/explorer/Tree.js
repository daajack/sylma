
if (!sylma.storage)
{
  sylma.storage = { fs : { explorer : {} } };
}

sylma.storage.fs.explorer.Tree = new Class({

  Extends : sylma.ui.Loader,

  onLoad : function() {

    this.add('root', this.get('datas'));

    var file = this.options.file;

    if (file) {

      var ext = file.match(/\w+$/);

      if (ext) {

        this.openFile(null, file, ext[0]);
      }
    }
  },
  
  build: function () {

    var root = this.getObject('root')[0];
    var files = root.getObject('file');
    var current = 0;
    var length = files.length;
    var callback;
    var container = root.getNode('children');
    var top = container.offsetTop;

    callback = function() {

      if (current < length) {

        var file = files[current];

        file.open(callback);
        container.scrollTop = file.getNode().offsetTop - top;
//console.log(file.getNode().offsetTop);
      }

      current++;
    };

    callback(current);
  },
  
  openFile: function (callback, path, extension) {

    this.getParent('explorer').open(path, extension);
  },

  updateJSON : function(args, isParent) {

    this.startLoading();

    this.send(this.get('path'), args, function(response) {

      this.getObject('root').pick().remove();
      var root = this.add('root', response.content);

      if (isParent) {

        root.getNode().addClass('parent');
      }

      this.stopLoading();

    }.bind(this), true);
    //this.getParent('explorer').updateLocation(args);
  }
});