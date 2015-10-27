<?php

namespace sylma\core\window\classes;
use sylma\core, sylma\storage\fs;

class ImageBuilder extends core\module\Filed {

  public function __construct(fs\file $file) {

    $this->setFile($file);
  }

  protected function resize($sExtension, $iMaxWidth, $iMaxHeight, $bCrop = false) {

    $wm = $iMaxWidth;
    $hm = $iMaxHeight;

    $aExtensions = array('jpeg', 'png', 'gif');

    if (!in_array($sExtension, $aExtensions)) {

      $this->launchException('Cannot edit image, unknown extension');
    }

    $file = $this->getFile();
    list($w, $h) = getimagesize($file->getRealPath());

    $ws = $w; // source
    $hs = $h; // source
    $wr = $hr = 1; // ratio
    $x = $y = 0; // position
    $wp = $hp = 0; // preview/result

    // look up for ratios

    if ($w > $wm) {

      $wr = $w / $wm;
      $wp = $wm;

    } else {

      $wp = $w;
    }

    if ($h > $hm) {

      $hr = $h / $hm;
      $hp = $hm;

    } else {

      $hp = $h;
    }

    // crop

    if ($wr > $hr) {

      if ($bCrop) {

        $ws = $wp * $hr;
        $x = ($w - $ws) / 2;

      } else {

        $hp = $h / $wr;
      }

    } else if ($wr < $hr) {

      if ($bCrop) {

        $hs = $hp * $wr;
        $y = ($h - $hs) / 2;

      } else {

        $wp = $w / $hr;
      }
    }

    $preview = imagecreatetruecolor($wp, $hp);

    if ($sExtension == 'png' || $sExtension == 'gif') {

      imagealphablending($preview, false);
      $iTransparent = imagecolortransparent($preview, imagecolorallocatealpha($preview, 0, 0, 0, 127));
      imagefill($preview, 0, 0, $iTransparent);
      imagesavealpha($preview, true);
    }

    $sFunction = 'imagecreatefrom'.$sExtension;
    $source = @$sFunction($file->getRealPath()) or die("Cannot Initialize new GD image stream");

    imagecopyresampled($preview, $source, 0, 0, $x, $y, $wp, $hp, $ws, $hs);

    return $preview;
  }

  public function build(fs\editable\file $file, $iWidth, $iHeight, $sFilter = '', $bCrop = true) {

    $sExtension = strtolower($file->getExtension());

    if ($sExtension == 'jpg') {

      $sExtension = 'jpeg';
    }

    $img = $this->resize($sExtension, $iWidth, $iHeight, $bCrop);

    if ($sFilter) {

      $this->filter($img, $sFilter);
    }

    $sFunction = 'image'.$sExtension;

    $sFunction($img, $file->getRealPath());
    imagedestroy($img);

    $file->updateStatut();
  }

  protected function filter($img, $sFilter) {

    //imagestring($img, 2, 5, 15, date('H:i:s'), imagecolorallocate($img, 255, 216, 147));

    switch ($sFilter) {

      case 'grayscale' :

        imagefilter($img, IMG_FILTER_GRAYSCALE);
        break;

      default :

        $this->launchException("Unknown filter : '$sFilter'");
    }
  }
}
