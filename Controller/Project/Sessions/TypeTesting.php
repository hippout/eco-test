<?php 

namespace Ecoplay\Controller\Project\Sessions;
use Ecoplay\Controller\Base as Base;

class TypeTesting extends Base
{
  public function execute($context)
  {
    $context['APPLICATION']->AddHeadScript('/js/forms.js');
    
    //$availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID']);
    $viewHelper = new \Ecoplay\View\Helper();
    
    if (isset($_POST['action'])) {
      if ($_POST['action'] == 'add') {
        if ($_POST['name'] && $_POST['package']) {
          $this->registry->getDbHelper('TestsHelper')->addSession(array(
            'projectID' => $context['project']['projectID'],
            'packageID' => intval($_POST['package']),
            'name'  => $_POST['name'],
            'status'  => 'active',
            'active'  => 1,
          ));
        }
      }
      elseif ($_POST['action'] == 'edit') {
        $session = $this->registry->getDbHelper('TestsHelper')->findSessionByID(intval($_POST['session_id'])); 
        if ($session && $_POST['name']) {          
          $res = $this->registry->getDbHelper('TestsHelper')->editSession($session['sessionID'], array(
            'name'  => $_POST['name'],
          ));
        }
      }
    }
    
    $sessionsCnt = 0;
    //if (count($availableSessionsIDs)) {
      $sessions = $this->registry->getDbHelper('TestsHelper')->getSessionsInfoByProjectID($context['project']['projectID']);      
      $sessionsCnt = count($sessions);
    
      $this->component->arResult['table_data'] = str_replace("'", "\'", $viewHelper->prepareJsonDataForTable($sessions, array('sessionID', 'name', 'status', 'package_name', 'cnt')));
    /*}
    else {
      $this->component->arResult['table_data'] = null;
    }*/
    
    $this->component->arResult['cnt'] = $sessionsCnt;
    $this->component->arResult['packages'] = $this->registry->getDbHelper('TestsHelper')->getAllPackages();
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Группы тестируемых');
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
      'title' => 'Группы тестируемых',
      ),
    )));
    
    $this->component->IncludeComponentTemplate('template_testing');
  }
}