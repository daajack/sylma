<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr-ch" lang="fr-ch">
  
  <head>
    
    <title><?php echo $this->getBloc('title'); ?></title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="content-language" content="fr, fr-ch" />
    
    <?php echo $this->getBloc('header'); ?>
    
  </head>
  
  <body <?php echo $this->getBloc('body_attributes'); ?>>
    
    <div id="content" class="clear-block">
      <?php echo $this->getBloc('content'); ?>
    </div>
    
  </body>
</html>
 