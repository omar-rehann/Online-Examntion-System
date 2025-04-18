<?php
include_once 'autoloader.inc.php';

function correctAnswer($answer)
{
  if (substr($answer, 0, 2) === '#!')
    return 1;
  else
    return 0;
}
function deleteImage($url) {
    $path = '../../../style/images/uploads/' . basename($url);
    @unlink($path);
}
function uploadFile($tmpName) {
  	$imageTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
    $info = getimagesize($tmpName);
    if(!in_array($info['mime'], $imageTypes)){
        return false;
    }
  $pictureName = time(). rand(0,999999999);
	$location = "../../../style/images/uploads/";
	compressImage($tmpName,$location.$pictureName.'.jpg',30);
	return $pictureName;
}
function compressImage($source, $destination, $quality) {
  $info = getimagesize($source);
  if ($info['mime'] == 'image/jpeg')
    $image = imagecreatefromjpeg($source);

  elseif ($info['mime'] == 'image/gif')
    $image = imagecreatefromgif($source);

  elseif ($info['mime'] == 'image/png')
    $image = imagecreatefrompng($source);
  imagejpeg($image, $destination, $quality);
}



 ?>
