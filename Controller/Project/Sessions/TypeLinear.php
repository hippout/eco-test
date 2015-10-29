<?php 

namespace Ecoplay\Controller\Project\Sessions;
use Ecoplay\Controller\Base as Base;

class TypeLinear extends Base
{
  public function execute($context)
  {
    $availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID']);
    $viewHelper = new \Ecoplay\View\Helper();
    
    $sessionsCnt = 0;
    if (count($availableSessionsIDs)) {      
      $sessions = $this->registry->getDbHelper('LinearProjectsHelper')->getSessionsInfoBySessionsIDs($context['project']['projectID'], $availableSessionsIDs);
      $this->component->arResult['sessions'] = $sessions;
      $sessionsCnt = count($sessions);      
      
      $this->component->arResult['table_data'] = str_replace("'", "\'", $viewHelper->prepareJsonDataForTable($sessions, array('sessionID', 'name', 'status', 'start_dt', 'finish_dt', 'blank_name', 'respondents_count', 'myCSS_path'), array('start_dt' => 'date', 'finish_dt' => 'date')));
    }
    else {
      $this->component->arResult['table_data'] = null;
    }
    
    $this->component->arResult['cnt'] = $sessionsCnt;
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Группы оцениваемых');
    $context['APPLICATION']->SetPageProperty('navigation', $viewHelper->generateNavigation(array(
      0  => array(
        'title' => 'Главная',
        'link'  => '/'
      ),
      1  => array(
        'title' => 'Проекты',
        'link'  => '/projects/',
      ),
      2  => array(
        'title' => $context['project']['project_name'],
        'link'  => '/projects/'.$context['project']['projectID'].'/continuing/stat/',
      ),
      3  => array(
        'title' => 'Группы оцениваемых',
      ),
    )));
    
    $this->component->arResult['projectID'] = $context['project']['projectID'];
    
    $this->component->IncludeComponentTemplate('linear/template');
  }
}