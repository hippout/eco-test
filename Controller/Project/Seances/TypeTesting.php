<?php 

namespace Ecoplay\Controller\Project\Seances;
use Ecoplay\Controller\Base as Base;

class TypeTesting extends Base
{
  public function execute($context)
  { 
    // проверка прав доступа к разделу    
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'seances', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
    
    $availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID'], $context['project']['project_type']);
    $viewHelper = new \Ecoplay\View\Helper();
    
    if (count($availableSessionsIDs)) {
      if (isset($_POST['reseted_id'])) { // обнуляем заполнение
        $seance = $this->registry->getDbHelper('TestsSeancesHelper')->findSeanceByID($_POST['reseted_id']);
        if ($seance) {
          $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($seance['respondentID']);
          $this->registry->getModel('Tests')->deleteSeance($seance);
          
          if ($respondent) {
            $this->registry->getModel('Tests')->createSeance($respondent);
          }
        }
      }
      
      $cnt = $this->registry->getDbHelper('TestsSeancesHelper')->getProjectsSeancesCount($context['project']['projectID']);
      $onPage = 100;
      $pagesCnt = ceil($cnt / $onPage);
      $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;
  
      $navResult = new \CDBResult();
      $navResult->NavPageCount = ceil($cnt / $onPage);
      $navResult->NavPageNomer = $page;
      $navResult->NavNum = 1;
      $navResult->NavPageSize = $onPage;
      $navResult->NavRecordCount = $cnt;
      
      $context['APPLICATION']->IncludeComponent('ecoplay:system.pagenavigation', '', array(
        'NAV_RESULT' => $navResult,
        'NAV_URL'  => '/projects/'.$context['project']['projectID'].'/groups/seances/',
      ));
      
      $seances = $this->registry->getDbHelper('TestsSeancesHelper')->getSeancesByProjectIDAndSessionsIds($context['project']['projectID'], $availableSessionsIDs, $page, $onPage);
      
      $statesNames = $this->registry->getDbHelper('BlanksHelper')->getSeancesStatesNames();
      $this->component->arResult['table_data'] = str_replace("'", "\'", $viewHelper->prepareJsonDataForTable($seances, array('tests_seanceID', 'name', 'surname', 'email', 'state'),
        array(), array('state' => $statesNames)));    
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
    
    $this->component->IncludeComponentTemplate('template_testing');
  }
}