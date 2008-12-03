<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
  <head>
    
    <title><?php echo $this->getBloc('title'); ?> - <?php echo $this->getBloc('content-title')->implodeChildren(); ?></title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="content-language" content="fr, fr-ch" />
    
    <?php echo $this->getBloc('header'); ?>
    
  </head>

  <body <?php echo $this->getBloc('body_attributes'); ?>>
    
    <div id="header">
      <h1>On Site</h1>
      <?php echo $this->getBloc('user-info'); ?>
    </div>
    
    <div id="center" class="clear-block">
      
      <div id="sidebar">
        <?php echo $this->getBloc('system'); ?>
        <?php echo $this->getBloc('menu-primary'); ?>
      </div>
      
      <?php echo $this->getBloc('content'); ?>
      
    </div>
    
    <?php echo $this->getBloc('forms'); ?>
    
  </body>
</html>