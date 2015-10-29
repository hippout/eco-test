<?php 

namespace Ecoplay\Draw;

require_once dirname(__FILE__).'/../../vendors/pChart2.1.4/class/pData.class.php';
require_once dirname(__FILE__).'/../../vendors/pChart2.1.4/class/pDraw.class.php';
require_once dirname(__FILE__).'/../../vendors/pChart2.1.4/class/pImage.class.php';
require_once dirname(__FILE__).'/../../vendors/pChart2.1.4/class/pRadar.class.php';

class VimpelcomHelper extends Helper
{
  public function drawCompetencyExpertsRadar($img, $points, $competences, $roles, $skale, $palettes)
  {
    $MyData = new \pData();
  
    /*$palettes = array(
        array("R"=>84,"G"=>85,"B"=>86),
        array("R"=>21,"G"=>101,"B"=>112),
        array("R"=>223,"G"=>72,"B"=>11),
        array("R"=>10,"G"=>120,"B"=>40),
        array("R"=>200,"G"=>150,"B"=>20),
    );*/
    
    /*$palettes = array(
      0  => array('R' => 191, 'G' => 191, 'B' => 191),
      1  => array('R' => 226, 'G' => 24, 'B' => 54),
      2  => array('R' => 244, 'G' => 122, 'B' => 32),
      3  => array('R' => 146, 'G' => 0, 'B' => 61),
      4  => array('R' => 91, 'G' => 74, 'B' => 63),
      5  => array('R' => 55, 'G' => 96, 'B' => 146),
      6  => array('R' => 119, 'G' => 147, 'B' => 60),
    );*/
  
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
  
    $myPicture = new \pImage(460*1.53, 330*1.53, $MyData);
  
    $myPicture->setFontProperties(array("FontName"=> dirname(__FILE__).'/fonts/calibri.ttf',"FontSize"=>round(7*1.53),"R"=>80,"G"=>80,"B"=>80));
  
    /* Create the pRadar object */
    $SplitChart = new \pRadar();
  
    /* Draw a radar chart */
    $myPicture->setGraphArea(70*1.53, 30*1.53, 340*1.53, 300*1.53);
    $Options = array("Layout"=>RADAR_LAYOUT_STAR, 'SegmentHeight' => ceil($skale['max']/4), "FontName"=> dirname(__FILE__).'/fonts/calibri.ttf', "FontSize"=> round(7*1.53), 'LabelPos' => RADAR_LABELS_HORIZONTAL, 'LineWidth' => 3);
    $SplitChart->drawRadar($myPicture,$MyData,$Options);
  
    $myPicture->render($img);
  }
  
  public function drawCompetencyConsistency($imgFile, $roles, $skale, $colors)
  {
    $cnt = count($roles);
    $scaler = /*1; */ 1.53;
    $width = round(500*$scaler);
    $height = round((35 + 20*$cnt)*$scaler);
    
    $img = imagecreatetruecolor($width, $height);
    
    $bgColor = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bgColor);
    
    /*$colors = array(
      0  => array('r' => 191, 'g' => 191, 'b' => 191),
      1  => array('r' => 226, 'g' => 24, 'b' => 54),
      2  => array('r' => 244, 'g' => 122, 'b' => 32),
      3  => array('r' => 146, 'g' => 0, 'b' => 61),
      4  => array('r' => 91, 'g' => 74, 'b' => 63),
      5  => array('r' => 55, 'g' => 96, 'b' => 146),
      6  => array('r' => 119, 'g' => 147, 'b' => 60),
    );*/
    
    $min = round($skale['min']);
    $max = round($skale['max']);
    
    // рисуем оси
    $axisColor = imagecolorallocate($img, 134, 134, 134);
    imageline($img, 200*$scaler, 30*$scaler, 490*$scaler, 30*$scaler, $axisColor);
    imageline($img, 200*$scaler, round((30 + 20*$cnt)*$scaler), 490*$scaler, round((30 + 20*$cnt)*$scaler), $axisColor);
    imageline($img, 200*$scaler, 27*$scaler, 200*$scaler, round((30 + 20*$cnt)*$scaler), $axisColor);
    //imageline($img, 490*$scaler, 27*$scaler, 490*$scaler, round((30 + 20*$cnt)*$scaler), $axisColor);
    
    imagettftext($img, round(10*$scaler), 0, 197*$scaler, 18*$scaler, $axisColor, dirname(__FILE__).'/fonts/calibri.ttf', $min);
    
    // рисуем вспомогательные линии
    $steps = $max - $min;
    $onePointWidth = ((490-200)/$steps)*$scaler;
    for ($i = $min; $i < $max; $i++) {
      $x = 200*$scaler + $i*$onePointWidth;
      imageline($img, $x, 27*$scaler, $x, round((30 + 20*$cnt)*$scaler), $axisColor);
      // подписываем шкалу
      imagettftext($img, round(10*$scaler), 0, $x - 4*$scaler, 18*$scaler, $axisColor, dirname(__FILE__).'/fonts/calibri.ttf', $i+1);
    }
    
    $ind = 0;
    $black = imagecolorallocate($img, 0, 0, 0);
    foreach ($roles as $role) {
      imagefilledrectangle($img, 200*$scaler+1, (30 + 20*$ind)*$scaler, 200*$scaler + ($role['val'] - 1)*$onePointWidth, (50 + 20*$ind)*$scaler, imagecolorallocate($img, $colors[$ind]['R'], $colors[$ind]['G'], $colors[$ind]['B']));
      imagettftext($img, round(9*$scaler), 0, 205*$scaler + ($role['val'] - 1)*$onePointWidth, (45 + 20*$ind)*$scaler, $black, dirname(__FILE__).'/fonts/calibri.ttf', $role['text']);
      imagettftext($img, round(9*$scaler), 0, 205*$scaler + ($role['val'] - 1)*$onePointWidth + 1, (45 + 20*$ind)*$scaler, $black, dirname(__FILE__).'/fonts/calibri.ttf', $role['text']);
      
      $sizes = imagettfbbox(round(8*$scaler), 0, dirname(__FILE__).'/fonts/calibri.ttf', $role['name']);
      imagettftext($img, round(8*$scaler), 0, 195*$scaler - $sizes[2], (43 + 20*$ind)*$scaler, $black, dirname(__FILE__).'/fonts/calibri.ttf', $role['name']);
      
      $ind++;
    }
    
    imagepng($img, $imgFile);
    imagedestroy($img);
  }
  
  public function drawCompetencyDetailed($imgFile, $competences, $skale)
  {
    $cnt = count($competences['childs']) + 1;
    $scaler = /*1; */ 1.53;
    $width = round(500*$scaler);
    $height = round(350*$scaler);
    
    $img = imagecreatetruecolor($width, $height);
    
    $bgColor = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bgColor);
    
    $min = round($skale['min']);
    $max = round($skale['max']);
    
    // рисуем оси
    $axisColor = imagecolorallocate($img, 134, 134, 134);    
    imageline($img, 20*$scaler, 10*$scaler, 20*$scaler, 150*$scaler, $axisColor);
    imageline($img, 17*$scaler, 150*$scaler, 500*$scaler, 150*$scaler, $axisColor);
    imagettftext($img, round(10*$scaler), 0, 5*$scaler, 155*$scaler, $axisColor, dirname(__FILE__).'/fonts/calibri.ttf', $min);
        
    // рисуем вспомогательные линии
    $steps = $max - $min;
    $onePointHeight = ((150-10)/$steps)*$scaler;
    for ($i = $min; $i < $max; $i++) {
      $y = 150*$scaler - $i*$onePointHeight;      
      imageline($img, 17*$scaler, $y, 500*$scaler, $y, $axisColor);
      // подписываем шкалу
      imagettftext($img, round(10*$scaler), 0, 5*$scaler, ($y + 5), $axisColor, dirname(__FILE__).'/fonts/calibri.ttf', $i+1);
    }
    
    $oneBlockWidth = ((500 - 20)/$cnt)*$scaler;
    $quarterWidth = round($oneBlockWidth/4);
    $deltaX = 20*$scaler;
    $black = imagecolorallocate(0, 0, 0);
    foreach ($competences['childs'] as $competency) {
      
      $color = $competency['color'] ? imagecolorallocate($img, hexdec(substr($competency['color'], 0, 2)), hexdec(substr($competency['color'], 2, 2)), hexdec(substr($competency['color'], 4, 2)))
        : imagecolorallocate($img, 123, 136, 133);
     
      imagefilledrectangle($img, $deltaX+$quarterWidth, 150*$scaler-1, $deltaX+3*$quarterWidth, 150*$scaler - ($competency['value'][999999]['val'] - 1)*$onePointHeight, $color);
      
      imagettftext($img, round(10*$scaler), 0, $deltaX+$quarterWidth, 150*$scaler - ($competency['value'][999999]['val'] - 1)*$onePointHeight - 5*$scaler, $black, dirname(__FILE__).'/fonts/calibri.ttf', $competency['value'][999999]['val']);
      imagettftext($img, round(7*$scaler), 90, $deltaX+$quarterWidth, 300*$scaler, $black, dirname(__FILE__).'/fonts/calibri.ttf', $competency['name']);
      
      $deltaX += $oneBlockWidth;
    }
    
    // корень
    imagefilledrectangle($img, $deltaX+$quarterWidth, 150*$scaler-1, $deltaX+3*$quarterWidth, 150*$scaler - ($competences['value'][999999]['val'] - 1)*$onePointHeight, imagecolorallocate($img, 123, 136, 133));
    imagettftext($img, round(10*$scaler), 0, $deltaX+$quarterWidth, 150*$scaler - ($competences['value'][999999]['val'] - 1)*$onePointHeight - 5*$scaler, $black, dirname(__FILE__).'/fonts/calibri.ttf', $competences['value'][999999]['val']);
    
    imagepng($img, $imgFile);
    imagedestroy($img);
    
    die();
  }
}