<?php
//extension_loaded('ffmpeg');
//$ffmpegInstance = new ffmpeg_movie('Uploads/Download/VID_20150822_104811.mp4');
//echo "getDuration: " . $ffmpegInstance->getDuration()."<br>" .
//    "getFrameCount: " . $ffmpegInstance->getFrameCount()."<br>" .
//    "getFrameRate: " . $ffmpegInstance->getFrameRate()."<br>" .
//    "getFilename: " . $ffmpegInstance->getFilename()."<br>"  ;
//$ff_frame = $ffmpegInstance->getFrame(2);//截取视频第20帧的图像
//if($ff_frame===false){
//    echo 1;
//}
//
//$imgs=$ff_frame->toGDImage();
////$gd_image = $ff_frame->toGDImage();
//if($imgs===false){
//    echo 2;die;
//}
//$img="Uploads/Download/test2.jpg";//要生成图片的绝对路径
//imagejpeg($imgs, $img);//创建jpg图像
//imagedestroy($imgs);//销毁一图像
//echo '<img src="./'.$img.'" />';die;
$path="Uploads/Picture/2015-05-26/test2.jpg";
$path1="Uploads/Picture/2015-05-26/test_200.jpg";
$path2="Uploads/Picture/2015-05-26/test_300.jpg";
$path3="Uploads/Picture/2015-05-26/test_400.jpg";
$path4="Uploads/Picture/2015-05-26/test_600.jpg";
$new="Uploads/Picture/2015-05-26/556466fb137fe1.jpg";
//extension_loaded('Imagick');
//$image1=new Imagick($path1);
$image2=new Imagick($path);
//$image3=new Imagick($path3);
//$image4=new Imagick($path4);
//$image1->gaussianBlurImage(25,10);
$image2->gaussianBlurImage(25,10);
//$image3->gaussianBlurImage(25,10);
//$image4->gaussianBlurImage(25,10);
header('Content-type: image/jpeg');
//echo $image1;
echo $image2;
//echo $image3;
//echo $image4;
die;
$img=imagecreatefromjpeg($path);
//$newimg=imageCreatetruecolor(imagesx($img),imagesy($img));
//imagecopyresampled($newimg,$img,0,0,0,0,imagesx($img),imagesy($img),imagesx($img),imagesy($img));
//imagecopyresampled($newimg,$img,0,0,10,10,imagesx($img),imagesy($img),imagesx($img),imagesy($img));
//imagefilter($img,IMG_FILTER_EMBOSS);
//imagefilter($img,IMG_FILTER_MEAN_REMOVAL);
for($i=0;$i<=25;$i++){
    imagefilter($img,IMG_FILTER_GAUSSIAN_BLUR);
    imagefilter($img,IMG_FILTER_SELECTIVE_BLUR);
}
//imagefilter($img,IMG_FILTER_GRAYSCALE);
//imagefilter($img,IMG_FILTER_SMOOTH,1);
//$size=getimagesize($path);
//$path1=gaussianBlur($img,$size[0],$size[1],2.0);
imagejpeg($img,$new);
imagedestroy($img);
//imagedestroy($newimg);
echo '<img src="./'.$path.'" />';
echo '<img src="./'.$new.'" />';die;

function gaussianBlur($img,$width,$height,$radius=1.0) {
    $sigma = $radius / 3;
    $sigma2 = 2 * pow($sigma, 2);
    $matrix = array();
    $newimage = imagecreatetruecolor($width, $height);
    /* 生成高斯矩阵(单纬度矩阵) */
    for ($x = -$radius; $x <= $radius; $x++) {
        $x2 = pow($x, 2) + pow($x, 2);
        $matrix[] = exp(-$x2 / $sigma2) / ($sigma2 * M_PI);
    }
    /* 构建模糊图像 */
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $bright = $red = $green = $blue = 0;
            /* 垂直模糊 */
            for ($yy = -$radius; $yy <= $radius; $yy++) {
                $yyy = $y + $yy;
                if ($yyy >= 0 && $yyy < $height) {
                    $weight = $matrix[$yy + $radius];
                    $bright += $weight;
                    $color = getColorAt($img,$x, $yyy);
                    $red += ($color['red'] * $weight);
                    $green += ($color['green'] * $weight);
                    $blue += ($color['blue'] * $weight);
                }
            }
            /* 水平模糊 */
            for ($xx = -$radius; $xx <= $radius; $xx++) {
                $xxx = $x + $xx;
                if ($xxx >= 0 && $xxx < $width && $xx != 0) {
                    $weight = $matrix[$xx + $radius];
                    $bright+=$weight;
                    $color = getColorAt($img,$xxx, $y);
                    $red += ($color['red'] * $weight);
                    $green += ($color['green'] * $weight);
                    $blue += ($color['blue'] * $weight);
                }
            }
            $z = 1 / $bright;
            imagesetpixel($newimage, $x, $y, ($red * $z << 16) | ($green * $z << 8) | $blue * $z);
        }
    }
    imagedestroy($img);
    $new="Uploads/Picture/2015-05-26/556466fb137fe1.jpg";
    imagejpeg($newimage,$new);
    imagedestroy($newimage);
    return $new;
}
/**
 * 获取指定坐标像素颜色
 * @param type $x
 * @param type $y
 * @return type
 */
function getColorAt($img,$x, $y) {
    $rgb = imageColorAt($img, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    return array('red' => $r, 'green' => $g, 'blue' => $b);
}
?>