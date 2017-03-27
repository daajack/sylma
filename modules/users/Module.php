<?php
namespace sylma\modules\users;
use sylma\core;

class Module extends core\module\Domed {

  const NS = 'http://www.sylma.org/modules/users';
  const GROUP_AUTH = 'user';

  public function __construct(core\argument $args, core\argument $post, core\argument $contexts) {

    $this->setDirectory(__file__);

    $this->aContext = array(
      'arguments' => $args,
      'post' => $post,
      'contexts' => $contexts,
    );

    $this->setSettings(include('core/user/settings.xml.php'));
  }

  /**
   * @return string
   */
  public function login() {

    $this->loadDefaultArguments();

    $contexts = $this->aContext['contexts'];
    $post = $this->aContext['post'];

    $aUser = $this->authenticate($post->read('name', false), $post->read('password', false));

    $msg = $contexts->get('messages');

    sleep($this->read('login/delay'));

    if (!$aUser['id']) {

      $msg->add(array('content' => $this->translate('Authentication failed'), 'arguments' => array('error' => true)));
    }
    else {

      $aGroups = $aUser['groups'];
      $aGroups[] = self::GROUP_AUTH;

      $bRemember = true; //(bool) $post->get('remember', false);
      $user = $this->getManager('user');
      $user->authenticate($post->read('name'), $aUser['id'], $aGroups);

      \Sylma::setManager('user', $user);
      $user->load($bRemember);

      $msg->add(array('content' => $this->translate('Authentication successed')));
    }

    return $aUser['id'];
  }

  public function authenticate($sName, $sPassword) {

    $sResult = '';
    $groups = array();
    $contexts = $this->aContext['contexts']->query();

    $doc = $this->getScript('login/default/check', array(), array(
      'name' => $sName,
    ), $contexts);

    if (!$doc->isEmpty()) {

      list($sID, $sPasswordHash, $sGroups) = explode(' ', $doc->readx());
      $rgroups = array_filter(explode(',', $sGroups));

      $this->getSubGroups($groups, $rgroups, $contexts);
      
      // if (hash_equals($sPasswordHash, crypt($sPassword, $sPasswordHash))) {
      if ($sPasswordHash == crypt($sPassword, $sPasswordHash)) {

        $sResult = $sID;
      }
    }

    return array(
      'id' => $sResult,
      'groups' => $groups
    );
  }
  
  protected function getSubGroups(array &$groups, array $sgroups, array $contexts) {
    
    foreach ($sgroups as $group)
    {
      if (!in_array($group, $groups))
      {
        $groups[] = $group;

        $ssgroups = $this->getScript('login/group', array(), array(
          'name' => $group,
        ), $contexts);
        
        if ($ssgroups)
        {
          $this->getSubGroups($groups, $ssgroups, $contexts);
        }
      }
    }
  }

  public function logout() {

    return $this->getManager('user')->logout();
  }
}


