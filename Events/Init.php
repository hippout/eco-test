<?php 

namespace Ecoplay\Events;

class Init
{
  /**
   * Переопределение картинки лого клиента
   */
  public function logo()
  {
    global $DB;
    global $APPLICATION;
    
    $projectsHelper = new \Ecoplay\Helper\Db\ProjectsHelper($DB);
    $clientLogo = false;
    
    if (isset($_GET['PROJECT_ID'])) {          
      $clientLogo = $projectsHelper->getProjectClientLogo(intval($_GET['PROJECT_ID']));      
    }
    elseif (isset($_GET["KEY"])) {
      $clientLogo = $projectsHelper->getMemberProjectClientLogo($_GET["KEY"]);
      if (!$clientLogo) {
          $clientLogo = $projectsHelper->getRespondentProjectClientLogo($_GET["KEY"]);
      }
    }
    elseif (isset($_GET["SEANCE_KEY"])) {
      $clientLogo = $projectsHelper->getSeanceProjectClientLogo($_GET["SEANCE_KEY"]);
    }
    elseif (isset($_GET["SEANCE_GROUP_KEY"])) {
      $clientLogo = $projectsHelper->getGroupSeanceProjectClientLogo($_GET["SEANCE_GROUP_KEY"]);
    }
    elseif (isset($_GET["GROUP_KEY"])) {
      $clientLogo = $projectsHelper->getGroupKeyProjectClientLogo($_GET["GROUP_KEY"]);
    }
    
    if ($clientLogo) {
      $APPLICATION->SetPageProperty('logoImg', $clientLogo);
    }
  }
}