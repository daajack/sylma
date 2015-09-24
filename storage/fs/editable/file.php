<?php

namespace sylma\storage\fs\editable;
use sylma\storage\fs;

interface file {

  /**
   * Change rights in corresponding SECURITY_FILE
   */
  public function updateRights($sOwner, $sGroup, $sMode);

  /**
   * Move a file WITH security rights
   *
   * @return null|fs\file The resulting new file if move successed or NULL if not
   */
  public function move(fs\directory $dir, $sName = '');

  /**
   * Move a file WITHOUT security rights
   *
   * @param string $sDirectory Targeted directory
   * @param string $sName Optional new name
   * @return null|string The file's path if move successed or NULL if not
   */
  public function moveFree($sDirectory, $sName = '');
  public function rename($sNewName);
  public function delete($bUpdateDirectory = true);
  public function saveText($sContent);
  public function copy(directory $dir);

  /**
   * Call parent directory to re-create object, self is lost
   * @return fs\file new object
   */
  public function update();
}
