<?php 

namespace Ecoplay\Draw;

require_once dirname(__FILE__).'/../../vendors/pChart2.1.4/class/pData.class.php';
require_once dirname(__FILE__).'/../../vendors/pChart2.1.4/class/pDraw.class.php';
require_once dirname(__FILE__).'/../../vendors/pChart2.1.4/class/pImage.class.php';
require_once dirname(__FILE__).'/../../vendors/pChart2.1.4/class/pRadar.class.php';

class Helper
{ 
  public function createVerticalTextImage($width, $height, $text, $size, $color, $fileName, $isCentered = false)
  {
    $img = imagecreatetruecolor($width, $height);
    imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
    $textColor = imagecolorallocate($img, $color[0], $color[1], $color[2]);    
    //imagerectangle($img, 0, 0, $width-1, $height-1, imagecolorallocate($img, 255, 0, 0));
    
    // определяем размеры рисуемой строки
    $textSizes = imagettfbbox($size, 90, dirname(__FILE__).'/fonts/time.ttf', $text);
    while (abs($textSizes[3]) > $height || abs($textSizes[4]) > $width) {
      $size--;
      $textSizes = imagettfbbox($size, 90, dirname(__FILE__).'/fonts/time.ttf', $text);
    }
    
    // определяем смещение координат левого нижнего символа чтобы нарисовать строку где нужно
    // 1. высота строки (ширина картинки)
    $x = $width - floor(($width - abs($textSizes[4]))/2);
    // 2. длина строки (высота картинки)
    $y = ($isCentered) ? $height - floor(($height - abs($textSizes[3]))/2) : $height;
    
    imagettftext($img, $size, 90, $x, $y, $textColor, dirname(__FILE__).'/fonts/time.ttf', $text);
    imagejpeg($img, $fileName, 100);
    imagedestroy($img);
  }
  
  public function drawCompetencyChart($imgFile, $points, $competences, $average, $skale)
  {
    $cnt = count($points);
    $scaler = /*1; */ 1.53;
    $width = round(600*$scaler);
    $height = round((100 + 60*$cnt)*$scaler);
    
    $img = imagecreatetruecolor($width, $height);
    
    $bgColor = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bgColor);
    
    $min = round($skale['min']);
    $max = round($skale['max']);
    
    // рисуем оси
    $axisColor = imagecolorallocate($img, 134, 134, 134);
    imageline($img, 200*$scaler, 100*$scaler, 580*$scaler, 100*$scaler, $axisColor);
    imageline($img, 200*$scaler, 100*$scaler+1, 580*$scaler, 100*$scaler+1, $axisColor);
    imageline($img, 200*$scaler, 98*$scaler, 200*$scaler, (100 + $cnt*55)*$scaler, $axisColor);
    imageline($img, 200*$scaler+1, 100*$scaler, 200*$scaler+1, (100 + $cnt*55)*$scaler, $axisColor);
    
    imagettftext($img, round(10*$scaler), 0, 197*$scaler, 92*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', $min);
    
    // рисуем вспомогательные линии
    $steps = $max - $min;
    $onePointWidth = ((580-200)/$steps)*$scaler;    
    for ($i = $min; $i < $max; $i++) {
      $x = 200*$scaler + $i*$onePointWidth;      
      imageline($img, $x, 98*$scaler, $x, (100 + $cnt*55)*$scaler, $axisColor);
      // подписываем шкалу
      imagettftext($img, round(10*$scaler), 0, $x - 3*$scaler, (98-6)*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', $i+1);
    }
    
    imagettftext($img, round(10*$scaler), 0, 110*$scaler, 70*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', 'баллы');
    imagettftext($img, round(10*$scaler), 0, 110*$scaler+1, 70*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', 'баллы');
    
    $competencesColor = imagecolorallocate($img, 12, 156, 0);
    $textColor = imagecolorallocate($img, 0, 0, 0);
    $averageColor = imagecolorallocate($img, 210, 142, 5);
    $averageCoords = array();
    
    $ind = 0;
    foreach ($points as $key => $competency) {
      // подписываем шкалы деструкторов
      $sizes = imagettfbbox(round(12*$scaler), 0, dirname(__FILE__).'/fonts/georgia.ttf', $competences[$key]);
      if ($sizes[2] > 180*$scaler) {
        $name = $this->divideString($competences[$key]);
        $nameY = (125 + $ind*55)*$scaler;
        if (!$name) {
          $name = $competences[$key];
          $nameY = (134 + $ind*55)*$scaler;
        }
      }
      else {
        $name = $competences[$key];
        $nameY = (134 + $ind*55)*$scaler;
      }
      imagettftext($img, round(12*$scaler), 0, (190*$scaler-$sizes[2]), $nameY, $textColor, dirname(__FILE__).'/fonts/georgia.ttf', $name);
    
      // рисуемы бары деструкторов
      $barWidth = ($competency - $min)*$onePointWidth;
      $barHeight = 39*$scaler;      
      imagefilledrectangle($img, 200*$scaler+1, (108 + $ind*55)*$scaler, 200*$scaler+$barWidth, (147 + $ind*55)*$scaler, $competencesColor);
    
      // выводим значение бара
      imagettftext($img, round(12*$scaler), 0, (200*$scaler+$barWidth + 15*$scaler), (134 + $ind*55)*$scaler, $textColor, dirname(__FILE__).'/fonts/georgia.ttf', number_format(round($competency, 1), 1));
      imagettftext($img, round(12*$scaler), 0, (200*$scaler+$barWidth + 15*$scaler) - 1, (134 + $ind*55)*$scaler, $textColor, dirname(__FILE__).'/fonts/georgia.ttf', number_format(round($competency, 1), 1));
    
      // отмечаем средние + подготавливаем их для кривой
      if (isset($average[$key])) {
        $averageCoords[(128 + $ind*55)*$scaler] = 200*$scaler + ($average[$key] - $min)*$onePointWidth;
        $this->drawDiamond($img, 200*$scaler + ($average[$key] - $min)*$onePointWidth - 5*$scaler, (128 + $ind*55)*$scaler - 5*$scaler, 10*$scaler, $averageColor);
      }
    
      $ind++;
    }
    
    // делаем легенду
    imagefilledrectangle($img, 30*$scaler, 20*$scaler, 55*$scaler, 30*$scaler, $competencesColor);
    imagettftext($img, 10*$scaler, 0, 60*$scaler, 30*$scaler, $textColor, dirname(__FILE__).'/fonts/georgia.ttf', 'Все без самооценки');
    
    if (count($averageCoords) && count($averageCoords) > 1) {      
      $style = array($averageColor, $averageColor, $averageColor, $averageColor, $averageColor, $averageColor, $averageColor, $bgColor, $bgColor, $bgColor);
      imagesetstyle($img, $style);
      imagesetthickness($img, 3);
      imageline($img, 210*$scaler, 25*$scaler, 255*$scaler, 25*$scaler, IMG_COLOR_STYLED);
      imagettftext($img, 10*$scaler, 0, 264*$scaler, 30*$scaler, $textColor, dirname(__FILE__).'/fonts/georgia.ttf', 'Среднее по всем оцениваемым руководителям');
      $this->drawDiamond($img, 227*$scaler, 20*$scaler, 10*$scaler, $averageColor);
    
      // рисуем кривую среднюю
      //imageantialias($img, true);
      $curve = new CubicSplines();
      $curve->setInitCoords($averageCoords, 1);
      $curveCoords = $curve->processCoords();
      $this->drawCurve($img, $curveCoords, $averageColor);
    }
    
    imagepng($img, $imgFile);
    imagedestroy($img);
  }
  
  public function drawCompetencyExpertsRadar($img, $points, $competences, $roles, $skale)
  {
    $MyData = new \pData();
    
    $palettes = array(
      array("R"=>84,"G"=>85,"B"=>86),
      array("R"=>21,"G"=>101,"B"=>112),
      array("R"=>223,"G"=>72,"B"=>11),
      array("R"=>10,"G"=>120,"B"=>40),
      array("R"=>200,"G"=>150,"B"=>20),
    );
    
    $i = 0;
    foreach ($points as $roleID => $data) {      
      $MyData->addPoints($data, "Score".$roleID);
      $MyData->setSerieDescription("Score".$roleID, $roles[$roleID]['name']);
      $MyData->setPalette("Score".$roleID, $palettes[$i++]);
    }
    
    $labels = array();
    foreach ($competences as $competency) {
      $labels[] = (strpos($competency, ' ') !== false) ? $this->divideString($competency) : $competency; 
    }    
    $MyData->addPoints($labels, "Labels");
    $MyData->setAbscissa("Labels");
        
    $myPicture = new \pImage(700*1.53, 700*1.53, $MyData);
    
    $myPicture->setFontProperties(array("FontName"=> dirname(__FILE__).'/fonts/georgia.ttf',"FontSize"=>round(14*1.53),"R"=>80,"G"=>80,"B"=>80));
    
    /* Create the pRadar object */
    $SplitChart = new \pRadar();
    
    /* Draw a radar chart */
    $myPicture->setGraphArea(10*1.53, 80*1.53, 640*1.53, 640*1.53);
    $Options = array("Layout"=>RADAR_LAYOUT_STAR, 'SegmentHeight' => ceil($skale['max']/4), "FontName"=> dirname(__FILE__).'/fonts/georgia.ttf', "FontSize"=> round(14*1.53), 'LabelPos' => RADAR_LABELS_HORIZONTAL, 'LineWidth' => 3);    
    $SplitChart->drawRadar($myPicture,$MyData,$Options);
        
    /* Write the chart legend */
    $myPicture->setFontProperties(array("FontName"=> dirname(__FILE__).'/fonts/georgia.ttf',"FontSize"=> round(12*1.53)));
    $myPicture->drawLegend(220*1.53, 20*1.53, array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));
        
    $myPicture->render($img);
  }
  
  public function drawDestructorsChart($imgFile, $destructors, $skale)
  {
    $destructorsCnt = count($destructors);
    $scaler = /*1; */ 1.53;
    $width = round(600*$scaler);
    $height = round((50 + 60*$destructorsCnt)*$scaler);
    
    $img = imagecreatetruecolor($width, $height);

    $min = round($skale['min']);
    $max = round($skale['max']);
    
    $bgColor = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bgColor);
    
    // рисуем оси
    $axisColor = imagecolorallocate($img, 134, 134, 134);
    imageline($img, 20*$scaler, 50*$scaler, 400*$scaler, 50*$scaler, $axisColor);
    imageline($img, 20*$scaler, 50*$scaler+1, 400*$scaler, 50*$scaler+1, $axisColor);
    imageline($img, 400*$scaler, 50*$scaler, 400*$scaler, (50 + $destructorsCnt*55)*$scaler, $axisColor);
    imageline($img, 401*$scaler, 48*$scaler, 401*$scaler, (48 + $destructorsCnt*55)*$scaler, $axisColor);
    
    imagettftext($img, round(10*$scaler), 0, 397*$scaler, 42*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', $min);
    imagettftext($img, round(10*$scaler), 0, 430*$scaler, 42*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', 'баллы');
    
    // рисуем вспомогательные линии
    $steps = $max - $min;
    $onePointWidth = ((400-20)/$steps)*$scaler;
    
    for ($i = $min; $i < $max; $i++) {
      $x = 400*$scaler - $i*$onePointWidth;
      imageline($img, $x, 48*$scaler, $x, (50 + $destructorsCnt*55)*$scaler, $axisColor);      
      // подписываем шкалу
      imagettftext($img, round(10*$scaler), 0, $x - 3*$scaler, (48-6)*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', $i+1);
    }
    
    // рассчитываем какой деструктор каким цветом красим
    $maxValue = 0;
    $warningDestructorsKeys = array();
    $badDestructorsKeys = array();
    foreach ($destructors as $dKey => $desructor) {
      if ($desructor['val'] > $maxValue) {
        $badDestructorsKeys = array();
        $badDestructorsKeys[] = $dKey;
        $maxValue = $desructor['val'];
      }
      elseif ($desructor['val'] == $maxValue) {
        $badDestructorsKeys[] = $dKey;
      }
      
      if (($desructor['val'] - 1) > 0.4) {
        $warningDestructorsKeys[] = $dKey;
      }
    }
    
    // обрабатываем деструкторы
    $ind = 0;
    $destructorsTextColor = imagecolorallocate($img, 0, 0, 0);
    $destructorBadColor = imagecolorallocate($img, 227, 133, 163);
    $destructorComColor = imagecolorallocate($img, 255, 255, 255);
    $destructorWarnColor = imagecolorallocate($img, 232, 198, 130);
    $averageColor = imagecolorallocate($img, 210, 142, 5);
    $bordersColor = imagecolorallocate($img, 127, 127, 127);    
    $averageCoords = array();    
    foreach ($destructors as $dKey => $desructor) {
      // подписываем шкалы деструкторов
      $sizes = imagettfbbox(round(12*$scaler), 0, dirname(__FILE__).'/fonts/time.ttf', $desructor['name']);
      if ($sizes[2] > (600 - 410)*$scaler) {
        $name = $this->divideString($desructor['name']);
        $nameY = (75 + $ind*55)*$scaler;
        if (!$name) {
          $name = $desructor['name'];
          $nameY = (84 + $ind*55)*$scaler;
        }
      }
      else {
        $name = $desructor['name'];
        $nameY = (84 + $ind*55)*$scaler;
      }
      
      imagettftext($img, round(12*$scaler), 0, 405*$scaler, $nameY, $destructorsTextColor, dirname(__FILE__).'/fonts/time.ttf', $name);
      
      // рисуемы бары деструкторов
      $barWidth = ($desructor['val']-$min)*$onePointWidth;
      $barHeight = 39*$scaler;      
      $dColor = (in_array($dKey, $badDestructorsKeys)) ? $destructorBadColor : (in_array($dKey, $warningDestructorsKeys) ? $destructorWarnColor : $destructorComColor);
      $this->drawBar($img, (400*$scaler-$barWidth), (58 + $ind*55)*$scaler, 400*$scaler-1, (97 + $ind*55)*$scaler, $dColor);      
      
      // рисуем границу бара
      $style = array($axisColor, $axisColor, $axisColor, $axisColor, $axisColor, $axisColor, $axisColor, $bgColor, $bgColor, $bgColor);
      imagesetstyle($img, $style);
      imageline($img, (400*$scaler-$barWidth), (58 + $ind*55)*$scaler, 400*$scaler - 1, (58 + $ind*55)*$scaler, IMG_COLOR_STYLED);
      imageline($img, (400*$scaler-$barWidth), (58 + $ind*55)*$scaler, (400*$scaler-$barWidth), (97 + $ind*55)*$scaler, IMG_COLOR_STYLED);
      imageline($img, (400*$scaler-$barWidth), (97 + $ind*55)*$scaler, 400*$scaler - 1, (97 + $ind*55)*$scaler, IMG_COLOR_STYLED);

      // выводим значение бара
      imagettftext($img, round(12*$scaler), 0, (400*$scaler-$barWidth - 26*$scaler), (84 + $ind*55)*$scaler, $axisColor, dirname(__FILE__).'/fonts/time.ttf', str_replace('.', ',', number_format(round($desructor['val'], 1), 1)));
      imagettftext($img, round(12*$scaler), 0, (400*$scaler-$barWidth - 26*$scaler) - 1, (84 + $ind*55)*$scaler, $axisColor, dirname(__FILE__).'/fonts/time.ttf', str_replace('.', ',', number_format(round($desructor['val'], 1), 1)));
      
      // отмечаем средние + подготавливаем их для кривой
      if ($desructor['av']) {
        $averageCoords[(78 + $ind*55)*$scaler] = 400*$scaler - ($desructor['av']-$min)*$onePointWidth;
        $this->drawDiamond($img, 400*$scaler - ($desructor['av']-$min)*$onePointWidth - 5*$scaler, (78 + $ind*55)*$scaler - 5*$scaler, 10*$scaler, $averageColor);
      }      
          
      $ind++;
    } 
    
    // делаем легенду
    $style = array($axisColor, $axisColor, $axisColor, $axisColor, $axisColor, $axisColor, $axisColor, $bgColor, $bgColor, $bgColor);
    imagesetstyle($img, $style);
    imageline($img, 30*$scaler, 10*$scaler, 55*$scaler, 10*$scaler, IMG_COLOR_STYLED);
    imageline($img, 30*$scaler, 10*$scaler, 30*$scaler, 20*$scaler, IMG_COLOR_STYLED);
    imageline($img, 30*$scaler, 20*$scaler, 55*$scaler, 20*$scaler, IMG_COLOR_STYLED);
    imageline($img, 55*$scaler, 10*$scaler, 55*$scaler, 20*$scaler, IMG_COLOR_STYLED);
    imagettftext($img, 10*$scaler, 0, 60*$scaler, 20*$scaler, $destructorsTextColor, dirname(__FILE__).'/fonts/time.ttf', 'Все без самооценки');
        
    if (count($averageCoords)) {
      $style = array($averageColor, $averageColor, $averageColor, $averageColor, $averageColor, $averageColor, $averageColor, $bgColor, $bgColor, $bgColor);
      imagesetstyle($img, $style);
      imagesetthickness($img, 3);
      imageline($img, 210*$scaler, 15*$scaler, 255*$scaler, 15*$scaler, IMG_COLOR_STYLED);
      imagettftext($img, 10*$scaler, 0, 264*$scaler, 20*$scaler, $destructorsTextColor, dirname(__FILE__).'/fonts/time.ttf', 'Среднее по всем оцениваемым руководителям');
      $this->drawDiamond($img, 227*$scaler, 10*$scaler, 10*$scaler, $averageColor);
    
      // рисуем кривую среднюю    
      //imageantialias($img, true);
      if (count($averageCoords) > 1) {
        $curve = new CubicSplines();
        $curve->setInitCoords($averageCoords, 1);        
        $curveCoords = $curve->processCoords();        
        $this->drawCurve($img, $curveCoords, $averageColor);
      }
    }
        
    imagepng($img, $imgFile);
    imagedestroy($img);
  }
  
  public function drawBar($img, $x1, $y1, $x2, $y2, $dColor)
  {
    $whiteColor = imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, $x1, $y1, $x2, $y2, $whiteColor);
    for ($y = $y1; $y <= $y2; $y = $y + 3) {      
      imageline($img, round($x1), $y, $x2, $y, $dColor);
    }
    for ($x = $x1; $x <= $x2; $x = $x + 3) {
      imageline($img, $x, $y1, $x, $y2, $dColor);
    }
  }
  
  public function drawDiamond($img, $x, $y, $width, $colour) {
    $x = round($x);
    $y = round($y);
    $halfWidth = round($width/2);
    $width = $halfWidth*2;
    $p1_x = $x;
    $p1_y = $y + $halfWidth;
    $p2_x = $x + $halfWidth;
    $p2_y = $y;
    $p3_x = $x + $width;
    $p3_y = $y + $halfWidth;
    $p4_x = $x + $halfWidth;
    $p4_y = $y + $width;
    
    $points = array($p1_x, $p1_y, $p2_x, $p2_y, $p3_x, $p3_y, $p4_x, $p4_y);  
    $num_of_points = 4;  
    imagefilledpolygon($img, $points, $num_of_points, $colour);
  }
  
  public function drawCurve($img, $coords, $color)
  {    
    $step = 0;
    $draw = true;
    foreach ($coords as $y => $x) {
      $step++;
      if ($draw && $step > 6) {
        $step = 0;
        $draw = false;
      }
      elseif (!$draw && $step > 3) {
        $step = 0;
        $draw = true;
      }
    
      if ($draw) {
        imageellipse($img, round($x), round($y), 1, 1, $color);
        imageellipse($img, round($x+1), round($y), 1, 1, $color);
        imageellipse($img, round($x-1), round($y), 1, 1, $color);
      }
    }
  }
  
  public function drawRelationsChart($imgFile, $relations, $skale)
  {
    $relationsCnt = count($relations);
    $scaler = /*1; */ 1.53;
    $width = round(600*$scaler);
    $height = round(450*$scaler);
    
    $img = imagecreatetruecolor($width, $height);
      
    $bgColor = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bgColor);
    
    $min = round($skale['min']);
    $max = round($skale['max']);
  
    // рисуем оси
    $axisColor = imagecolorallocate($img, 134, 134, 134);
    imageline($img, 40*$scaler - 2, 400*$scaler, 550*$scaler, 400*$scaler, $axisColor);
    imageline($img, 40*$scaler, 400*$scaler+1, 550*$scaler, 400*$scaler+1, $axisColor);
    imageline($img, 40*$scaler, 50*$scaler, 40*$scaler, 400*$scaler, $axisColor);
    imageline($img, 40*$scaler+1, 50*$scaler, 40*$scaler+1, 400*$scaler, $axisColor);
    imagettftext($img, round(10*$scaler), 0, 28*$scaler, 402*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', $min);
  
    // рисуем вспомогательные линии
    $steps = $max - $min;
    $onePointHeight = ((400-50)/$steps)*$scaler;
    for ($i = $min; $i < $max; $i++) {
      $y = 50*$scaler + ($i - 1)*$onePointHeight;
      imageline($img, 40*$scaler - 2, $y, 550*$scaler, $y, $axisColor);
      // подписываем шкалу
      imagettftext($img, round(10*$scaler), 0, 28*$scaler, $y + 4*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', $max + 1 - $i);
    }
    
    imagettftext($img, round(10*$scaler), 90, 33*$scaler, 446*$scaler, $axisColor, dirname(__FILE__).'/fonts/georgia.ttf', 'баллы');
  
    // обрабатываем отношения
    $ind = 0;
    $relationsTextColor = imagecolorallocate($img, 0, 0, 0);
    $relationsColor = imagecolorallocate($img, 12, 156, 0);    
    $averageColor = imagecolorallocate($img, 127, 127, 127);    
    $lastAverage = 0;
    imageantialias($img, true);
    foreach ($relations as $rKey => $relation) {      
      // рисуемы бары деструкторов      
      if ($relation['val']) {
        $barHeight = ($relation['val'] - $min)*$onePointHeight;
        $barWidth = 140*$scaler;  
        imagefilledrectangle($img, (100 + $ind*200)*$scaler, 400*$scaler-1, (100 + $ind*200)*$scaler + $barWidth, 400*$scaler - $barHeight, $relationsColor);
      
    
        // подписываем шкалы деструкторов
        $textSizes = imagettfbbox(round(10*$scaler), 0, dirname(__FILE__).'/fonts/time.ttf', $relation['name']);
        imagettftext($img, round(10*$scaler), 0, (100 + $ind*200)*$scaler + round($barWidth/2) - round($textSizes[2]/2), 420*$scaler, $relationsTextColor, dirname(__FILE__).'/fonts/time.ttf', $relation['name']);
        
        // выводим значение бара
        imagettftext($img, round(12*$scaler), 0, (100 + $ind*200)*$scaler + round($barWidth/2) - 6*$scaler, 400*$scaler - $barHeight - 6*$scaler, $axisColor, dirname(__FILE__).'/fonts/time.ttf', str_replace('.', ',', number_format(round($relation['val'], 1), 1)));
        imagettftext($img, round(12*$scaler), 0, (100 + $ind*200)*$scaler + round($barWidth/2) - 6*$scaler + 1, 400*$scaler - $barHeight - 6*$scaler, $axisColor, dirname(__FILE__).'/fonts/time.ttf', str_replace('.', ',', number_format(round($relation['val'], 1), 1)));
      }
  
      // отмечаем средние + рисуем участок от предыдущего   
      if ($relation['av']) {   
        $this->drawDiamond($img, (100 + $ind*200)*$scaler + round($barWidth/2) - 5*$scaler, 400*$scaler - ($relation['av'] - $min)*$onePointHeight - 5*$scaler, 10*$scaler, $averageColor);
      }
      if ($lastAverage) {
        /*$style = array($averageColor, $averageColor, $averageColor, $averageColor, $averageColor, $averageColor, $averageColor, $bgColor, $bgColor, $bgColor);
        imagesetstyle($img, $style);*/        
        imageline($img, (100 + ($ind - 1)*200)*$scaler + round($barWidth/2), 400*$scaler - $lastAverage*$onePointHeight, (100 + $ind*200)*$scaler + round($barWidth/2), 400*$scaler - ($relation['av'] - $min)*$onePointHeight, $averageColor);
        imageline($img, (100 + ($ind - 1)*200)*$scaler + round($barWidth/2), 400*$scaler - $lastAverage*$onePointHeight-1, (100 + $ind*200)*$scaler + round($barWidth/2), 400*$scaler - ($relation['av'] - $min)*$onePointHeight-1, $averageColor);
        imageline($img, (100 + ($ind - 1)*200)*$scaler + round($barWidth/2), 400*$scaler - $lastAverage*$onePointHeight+1, (100 + $ind*200)*$scaler + round($barWidth/2), 400*$scaler - ($relation['av'] - $min)*$onePointHeight+1, $averageColor);
      }
  
      if ($relation['av']) {
        $lastAverage = ($relation['av'] - $min);
      }
      $ind++;
    }
  
    // делаем легенду
    imagefilledrectangle($img, 30*$scaler, 15*$scaler, 55*$scaler, 20*$scaler, $relationsColor);
    imagettftext($img, 10*$scaler, 0, 60*$scaler, 20*$scaler, $relationsTextColor, dirname(__FILE__).'/fonts/time.ttf', 'Все без самооценки');
  
    imageline($img, 210*$scaler, 15*$scaler, 255*$scaler, 15*$scaler, $averageColor);
    imageline($img, 210*$scaler, 15*$scaler-1, 255*$scaler, 15*$scaler-1, $averageColor);
    imageline($img, 210*$scaler, 15*$scaler+1, 255*$scaler, 15*$scaler+1, $averageColor);
    imagettftext($img, 10*$scaler, 0, 264*$scaler, 20*$scaler, $relationsTextColor, dirname(__FILE__).'/fonts/time.ttf', 'Среднее по всем оцениваемым руководителям');
    $this->drawDiamond($img, 227*$scaler, 10*$scaler, 10*$scaler, $averageColor);    
  
    imagepng($img, $imgFile);
    imagedestroy($img);
  }
  
  public function drawIndexes($imgFile, $data, $skale)
  {
    $scaler = /*1; */ 1.53;
    $width = round(600*$scaler);
    $height = round(400*$scaler);
    
    $img = imagecreatetruecolor($width, $height);
    
    $bgColor = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bgColor);
    
    $headerTextColor = imagecolorallocate($img, 128, 128, 128);
    $positiveTextColor = imagecolorallocate($img, 37, 156, 0); 
    $negativeTextColor = imagecolorallocate($img, 230, 40, 60);
    $graphColor = imagecolorallocate($img, 127, 127, 127);
    $whiteColor = imagecolorallocate($img, 255, 255, 255);
    
    imageantialias($img, true);
    
    $indexes = array(
      0  => array(
        'name'  => 'Индекс эффективности',
        'key'  => 'competency',
        'reverse'  => false,
      ),
      1  => array(
        'name'  => 'Индекс деструктивности',
        'key'  => 'destructors',
        'reverse'  => true,
      ),
      2  => array(
        'name'  => 'Индекс отношения',
        'key'  => 'relations',
        'reverse'  => false,
      ),
    );
    
    $delta = 0;
    foreach ($indexes as $index) {
      $key = $index['key'];
      if ($data[$key]['val']) {        
        $min = (isset($data[$key]['min'])) ? (($data[$key]['min'] > $data[$key]['val']) ? $data[$key]['val'] : $data[$key]['min']) : $skale['min'];   $data[$key]['min'];
        $max = (isset($data[$key]['max'])) ? (($data[$key]['max'] < $data[$key]['val']) ? $data[$key]['val'] : $data[$key]['max']) : $skale['max'];   $data[$key]['max'];      
        $average = (isset($data[$key]['av'])) ?$data[$key]['av'] : 0;
        $value = $data[$key]['val'];
        $unitWidth = round((520*$scaler - 90*$scaler) / (($max-$min)*10));
        if ($average) {
          $this->drawDiamond($img, 81*$scaler + ($average-$min)*10*$unitWidth, ($delta + 142)*$scaler, 18*$scaler, $graphColor);
          $this->drawDiamond($img, 85*$scaler + ($average-$min)*10*$unitWidth, ($delta + 146)*$scaler, 10*$scaler, $whiteColor);  
          $textSizes = imagettfbbox(9*$scaler, 0, dirname(__FILE__).'/fonts/time.ttf', $average);
          imagettftext($img, 9*$scaler, 0, round(90*$scaler + ($average-$min)*10*$unitWidth - $textSizes[2]/2), ($delta + 140)*$scaler, $headerTextColor, dirname(__FILE__).'/fonts/time.ttf', str_replace('.', ',', number_format(round($average, 1), 1)));
        }
        imagettftext($img, 12*$scaler, 0, 15*$scaler, ($delta + 120)*$scaler, $headerTextColor, dirname(__FILE__).'/fonts/time.ttf', $index['name']);
        imagettftext($img, 12*$scaler, 0, 15*$scaler+1, ($delta + 120)*$scaler, $headerTextColor, dirname(__FILE__).'/fonts/time.ttf', $index['name']);      
        imagettftext($img, 12*$scaler, 0, 30*$scaler, ($delta + 155)*$scaler, $index['reverse'] ? $positiveTextColor : $negativeTextColor, dirname(__FILE__).'/fonts/time.ttf', 'min');
        imagettftext($img, 12*$scaler, 0, 30*$scaler+1, ($delta + 155)*$scaler, $index['reverse'] ? $positiveTextColor : $negativeTextColor, dirname(__FILE__).'/fonts/time.ttf', 'min');
  
        //$this->drawGradientRect($img, 90*$scaler, ($delta + 151)*$scaler-5, 520*$scaler, ($delta + 151)*$scaler+5, $index['reverse'] ? '259c00' : 'e6283c', $index['reverse'] ? 'e6283c' : '259c00');
        $this->drawGradientRect($img, 90*$scaler, ($delta + 151)*$scaler-1, 520*$scaler, ($delta + 151)*$scaler+1, $index['reverse'] ? '00ff00' : 'ff0000', $index['reverse'] ? 'ff0000' : '00ff00');
        
        /*imageline($img, 90*$scaler, ($delta + 151)*$scaler, 520*$scaler, ($delta + 151)*$scaler, $graphColor);
        imageline($img, 90*$scaler, ($delta + 151)*$scaler+1, 520*$scaler, ($delta + 151)*$scaler+1, $graphColor);
        imageline($img, 90*$scaler, ($delta + 151)*$scaler-1, 520*$scaler, ($delta + 151)*$scaler-1, $graphColor);*/      
        imagettftext($img, 12*$scaler, 0, 560*$scaler, ($delta + 155)*$scaler, $index['reverse'] ? $negativeTextColor : $positiveTextColor, dirname(__FILE__).'/fonts/time.ttf', 'max');
        imagettftext($img, 12*$scaler, 0, 560*$scaler+1, ($delta + 155)*$scaler, $index['reverse'] ? $negativeTextColor : $positiveTextColor, dirname(__FILE__).'/fonts/time.ttf', 'max');      
        imagefilledellipse($img, 90*$scaler, ($delta + 151)*$scaler, 5*$scaler, 5*$scaler, $graphColor);
        imagefilledellipse($img, 520*$scaler, ($delta + 151)*$scaler, 8*$scaler, 8*$scaler, $graphColor); 
        $textSizes = imagettfbbox(8*$scaler, 0, dirname(__FILE__).'/fonts/time.ttf', $min);      
        imagettftext($img, 8*$scaler, 0, 82*$scaler - $textSizes[2], ($delta + 155)*$scaler, $headerTextColor, dirname(__FILE__).'/fonts/time.ttf', str_replace('.', ',', number_format(round($min, 1), 1)));
        imagettftext($img, 8*$scaler, 0, 528*$scaler, ($delta + 155)*$scaler, $headerTextColor, dirname(__FILE__).'/fonts/time.ttf', str_replace('.', ',', number_format(round($max, 1), 1)));      
        $this->drawDiamond($img, 85*$scaler + ($value-$min)*10*$unitWidth, ($delta + 146)*$scaler, 10*$scaler, $positiveTextColor);
        $textSizes = imagettfbbox(9*$scaler, 0, dirname(__FILE__).'/fonts/time.ttf', $value);
        imagettftext($img, 9*$scaler, 0, (90*$scaler + ($value-$min)*10*$unitWidth - $textSizes[2]/2), ($delta + 170)*$scaler, $headerTextColor, dirname(__FILE__).'/fonts/time.ttf', str_replace('.', ',', number_format(round($value, 1), 1)));
        $delta += 100;
      }
    }
    
    // легенда
    $this->drawDiamond($img, 200*$scaler, 32*$scaler, 18*$scaler, $graphColor);
    $this->drawDiamond($img, 204*$scaler, 36*$scaler, 10*$scaler, $whiteColor);
    imagettftext($img, 8*$scaler, 0, 222*$scaler, 45*$scaler, $headerTextColor, dirname(__FILE__).'/fonts/time.ttf', '- Среднее по всем оцениваемым руководителям');
    $this->drawDiamond($img, 204*$scaler, 56*$scaler, 10*$scaler, $positiveTextColor);
    imagettftext($img, 8*$scaler, 0, 222*$scaler, 65*$scaler, $headerTextColor, dirname(__FILE__).'/fonts/time.ttf', '- Как Вас оценили (все без самооценки)');
    
    imagepng($img, $imgFile);
    imagedestroy($img);
  }
  
  // применимо только от красного к зеленому (точнее от одного полного цвета - кдругому)..
  protected function drawGradientRect($img,$x,$y,$x1,$y1,$start,$end) {
    if($x > $x1 || $y > $y1) {
      return false;
    }
    $s = array(
      hexdec(substr($start,0,2)),
      hexdec(substr($start,2,2)),
      hexdec(substr($start,4,2))
    );
    $e = array(
      hexdec(substr($end,0,2)),
      hexdec(substr($end,2,2)),
      hexdec(substr($end,4,2))
    );    
    
    $halfSteps = round(($x1 - $x)/2);
    $steps = $x1 - $x;
    
    // первая половина
    if ($s[0] == 255) {
      $const = 0;
    }
    elseif ($s[1] == 255) {
      $const = 1;
    }
    else {
      $const = 2;
    }
     
    for ($i = 0; $i < $halfSteps; $i++) {      
      $r = ($const != 0) ? $s[0] - ((($s[0]-$e[0])/$halfSteps)*$i) : $s[0];
      $g = ($const != 1) ? $s[1] - ((($s[1]-$e[1])/$halfSteps)*$i) : $s[1];      
      $b = ($const != 2) ? $s[2] - ((($s[2]-$e[2])/$halfSteps)*$i) : $s[2];
      $color = imagecolorallocate($img,$r,$g,$b);
      imagefilledrectangle($img, $x+$i, $y, $x+$i+1, $y1, $color);
    }
    
    // вторая половина
    if ($e[0] == 255) {
      $const = 0;
    }
    elseif ($e[1] == 255) {
      $const = 1;
    }
    else {
      $const = 2;
    }
    for ($i = $halfSteps; $i < $steps; $i++) {
      $r = ($const != 0) ? $s[0] - ((($s[0]-$e[0])/($steps - $halfSteps))*($i - $halfSteps)) : $e[0];
      $g = ($const != 1) ? $s[1] - ((($s[1]-$e[1])/($steps - $halfSteps))*($i - $halfSteps)) : $e[1];
      $b = ($const != 2) ? $s[2] - ((($s[2]-$e[2])/($steps - $halfSteps))*($i - $halfSteps)) : $e[2];      
      $color = imagecolorallocate($img,$r,$g,$b);
      imagefilledrectangle($img, $x+$i, $y, $x+$i+1, $y1, $color);
    }
    return true;
  }
  
  /**
   * Разбивает строку символом перевода строки на месте проблема наиболее приближенного к центру строки
   * @param string $string
   */
  protected function divideString($string)
  {
    $lastPos = 0;
    $positions = array();
    while (($pos = iconv_strpos($string, ' ', $lastPos, 'UTF-8')) !== false) {
      $positions[abs(iconv_strlen($string, 'UTF-8') - $pos*2)] = $pos;
      $lastPos = $pos + 1;
    }
    
    if (count($positions)) {
      ksort($positions);
      $centeredPos = 0;
      foreach ($positions as $pos) {
        $centeredPos = $pos;
        break;
      }
    
      return iconv_substr($string, 0, $pos, 'UTF-8')."\n".iconv_substr($string, $pos+1, iconv_strlen($string, 'UTF-8'), 'UTF-8');
    }
    
    return false;
  }
}

class CubicSplines {
  protected $aCoords;
  protected $aCrdX;
  protected $aCrdY;
  protected $aSplines = array();
  protected $iMinX;
  protected $iMaxX;
  protected $iStep;

  protected function prepareCoords(&$aCoords, $iStep, $iMinX = -1, $iMaxX = -1) {
    $this->aCrdX = array();
    $this->aCrdY = array();
    $this->aCoords = array();
    
    ksort($aCoords);
    foreach ($aCoords as $x => $y) {
      $this->aCrdX[] = $x;
      $this->aCrdY[] = $y;
    }

    $this->iMinX = $iMinX;
    $this->iMaxX = $iMaxX;

    if ($this->iMinX == -1)
      $this->iMinX = min($this->aCrdX);
    if ($this->iMaxX == -1)
      $this->iMaxX = max($this->aCrdX);

    $this->iStep = $iStep;
  }

  public function setInitCoords(&$aCoords, $iStep = 1, $iMinX = -1, $iMaxX = -1) {
    $this->aSplines = array();

    if (count($aCoords) < 2) {      
      return false;
    }

    $this->prepareCoords($aCoords, $iStep, $iMinX, $iMaxX);
    $this->buildSpline($this->aCrdX, $this->aCrdY, count($this->aCrdX));
  }

  public function processCoords() {    
    for ($x = $this->iMinX; $x <= $this->iMaxX; $x += $this->iStep) {
      $this->aCoords[$x] = $this->funcInterp($x);      
    }

    return $this->aCoords;
  }

  private function buildSpline($x, $y, $n) {
    for ($i = 0; $i < $n; ++$i) {
      $this->aSplines[$i]['x'] = $x[$i];
      $this->aSplines[$i]['a'] = $y[$i];
    }

    $this->aSplines[0]['c'] = $this->aSplines[$n - 1]['c'] = 0;
    $alpha[0] = $beta[0] = 0;
    for ($i = 1; $i < $n - 1; ++$i) {
      $h_i = $x[$i] - $x[$i - 1];
      $h_i1 = $x[$i + 1] - $x[$i];
      $A = $h_i;
      $C = 2.0 * ($h_i + $h_i1);
      $B = $h_i1;
      $F = 6.0 * (($y[$i + 1] - $y[$i]) / $h_i1 - ($y[$i] - $y[$i - 1]) / $h_i);
      $z = ($A * $alpha[$i - 1] + $C);
      $alpha[$i] = - $B / $z;
      $beta[$i] = ($F - $A * $beta[$i - 1]) / $z;
    }

    for ($i = $n - 2; $i > 0; --$i) {
      $this->aSplines[$i]['c'] = $alpha[$i] * $this->aSplines[$i + 1]['c'] + $beta[$i];
    }

    for ($i = $n - 1; $i > 0; --$i) {
      $h_i = $x[$i] - $x[$i - 1];
      $this->aSplines[$i]['d'] = ($this->aSplines[$i]['c'] - $this->aSplines[$i - 1]['c']) / $h_i;
      $this->aSplines[$i]['b'] = $h_i * (2.0 * $this->aSplines[$i]['c'] + $this->aSplines[$i - 1]['c']) / 6.0 + ($y[$i] - $y[$i - 1]) / $h_i;
    }
  }

  private function funcInterp($x) {
    $n = count($this->aSplines);
    if ($x <= $this->aSplines[0]['x'])  {
      $s = $this->aSplines[1];
    } else {
      if ($x >= $this->aSplines[$n - 1]['x']) {
        $s = $this->aSplines[$n - 1];
      } else {
        $i = 0;
        $j = $n - 1;
        while ($i + 1 < $j) {
          $k = $i + ($j - $i) / 2;
          if ($x <= $this->aSplines[$k]['x']) {
            $j = $k;
          } else {
            $i = $k;
          }
        }

        $s = $this->aSplines[$j];
      }
    }

    $dx = ($x - $s['x']);
    return $s['a'] + ($s['b'] + ($s['c'] / 2.0 + $s['d'] * $dx / 6.0) * $dx) * $dx;
  }
}