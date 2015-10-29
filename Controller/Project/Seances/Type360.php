<?php 
namespace Ecoplay\Controller\Project\Seances;
use Ecoplay\Controller\Base as Base;

class Type360 extends Base
{
  public function execute($context)
  { 
    // проверка прав доступа к разделу    
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'seances', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }    
    
    $availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID']);
    $viewHelper = new \Ecoplay\View\Helper();
    
    if (count($availableSessionsIDs)) {      
      $seances = $this->registry->getDbHelper('ProjectsHelper')->getSeancesByProjectIDAndSessionsIds($context['project']['projectID'], $availableSessionsIDs);      
      $this->component->arResult['seances'] = $seances;
  
      $this->component->arResult['table_data'] = str_replace("'", "\'", $viewHelper->prepareJsonDataForTable($seances, array('seanceID', 'name', 'surname', 'email', 'start_dt', 'finish_dt', 'assess', 'role', 'answered')));
      $this->component->arResult['cnt'] = count($seances);
    }
    else {
      $this->component->arResult['table_data'] = null;
      $this->component->arResult['cnt'] = 0;
    }
  
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Сеансы заполнения');
    $context['APPLICATION']->SetPageProperty('navigation', $viewHelper->generateNavigation(array(
      0  => array(
        'link' => '/',
        'title' => 'Главная',
      ),
      1  => array(
        'link' => '/projects/',
        'title' => 'Проекты',
      ),
      2  => array(
        'link' => '/projects/'.$context['project']['projectID'].'/continuing/stat/',
        'title' => $context['project']['project_name'],
      ),
      3  => array(
        'link' => '/projects/'.$context['project']['projectID'].'/groups/',
        'title' => 'Группы оцениваемых',
      ),
      4 => array(
        'title'  => 'Сеансы заполнения',
      ),
    )));  
    
    $this->component->IncludeComponentTemplate();
  }
}