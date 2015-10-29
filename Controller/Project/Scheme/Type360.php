<?php 

namespace Ecoplay\Controller\Project\Scheme;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Helper\DataFilter as DataFilter;
use Ecoplay\Helper\Db\LogsHelper;

class Type360 extends Base
{
  public function execute($context)
  {     
    $context['APPLICATION']->AddHeadScript('/js/forms.js');
    $context['APPLICATION']->AddHeadScript('/js/icheck/icheck.min.js');
    $context['APPLICATION']->SetAdditionalCSS('/js/icheck/skins/minimal/eco.css');
    
    // проверка прав доступа к разделу    
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'members', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
    
    $this->component->arResult['canImport'] = $this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'imports', $context['project']['projectID']);
    $isProjectAdmin = $this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'project_edit', $context['project']['projectID']);
    $this->component->arResult['isProjectAdmin'] = $isProjectAdmin;
    
    $selectedIDs = array();
    
    // обнуление заполнения
    $this->component->arResult['isAdmin'] = $_SESSION['accesses']['is_admin'] ? true : false;
    if (/*$this->component->arResult['isAdmin'] && */isset($_POST['reseted_id'])) {
      $selectedIDs[] = $_POST['reseted_id'];      
      $seance = $this->registry->getDbHelper('SeancesHelper')->findSeanceByRespondentAndProject($_POST['reseted_id'], $context['project']['projectID']);
    
      if ($seance /*&& $seance['last_screenID'] > 0*/) {
        // деактивируем текущий сеанс
        $this->registry->getDbHelper('SeancesHelper')->editSeance($seance['seanceID'], array('active' => 0, 'private_access_key' => ''));
        $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'seance', $seance['seanceID'], LogsHelper::ACTION_TYPE_DELETE, $seance);
    
        // создаем новый сеанс
        $BlankDataSource = new \Ecoplay\Model\BlankDataSource($this->registry->getDbConnect(), 0, 0);
        $seanceKey = $BlankDataSource->addSeanceAndCookies($seance['private_access_key']);
        $seance = $this->registry->getDbHelper('SeancesHelper')->findSeanceByKey($seanceKey);
        $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'seance', $seance['seanceID'], LogsHelper::ACTION_TYPE_ADD, null);
    
        //ставим флаг чтобы данные по отчету для оцениваемого пересчитались
        $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($seance['respondentID']);        
        $assessCompetencyData = $this->registry->getDbHelper('CompetencyHelper')->getAssessCompetencyData($respondent['stat1_assessID']);
        if ($assessCompetencyData) {
          $this->registry->getDbHelper('CompetencyHelper')->editAssessCompetency($assessCompetencyData['ID'], array(
            'need_recount'  => 1,
          ));
        }
      }
    }
    
    $availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID']);
    
    $sessions = $this->registry->getDbHelper('ProjectsHelper')->getUserAvailableSessionsByProjectID($context['project']['projectID'], $availableSessionsIDs);
    $this->component->arResult['jsessions'] = json_encode($sessions);
    $this->component->arResult['sessions'] = $sessions;
    
    $sorts = array(
      'respondentID'  => 'respondentID',
      'assess'  => 'assess',
      'assess_email'  => 'assess_email',
      'assess_position'  => 'assess_position',
      'role'  => 'role',
      'respondent'  => 'respondent',
      'email'  => 'email',
      'position'  => 'position',
      'percent'  => 'percent',
      'sessionID'  => 'sessionID',
      'seanceID'  => 'seanceID',
      'sortby'    => 'sortby', 
    );
    if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sorts)) {
      $sort = $_GET['sort'];
    }
    else {
      $sort = 'respondentID';
    }
    $order = (isset($_GET['order']) && in_array($_GET['order'], array('asc', 'desc'))) ? $_GET['order'] : 'asc';
    
    $filterQueryString = DataFilter::getFilterQueryString($_GET);
    $this->component->arResult['filterQueryString'] = $filterQueryString;
    
    // настройки фильтров для данных
    $filters = array(
      0  => array('title' => 'Оцениваемый Имя', 'name' => 'assess_name', 'type' => 'text', 'data_type' => 'string', 'field' => 'pma.`name`'),
      1  => array('title' => 'Оцениваемый Фамилия', 'name' => 'assess_surname', 'type' => 'text', 'data_type' => 'string', 'field' => 'pma.`surname`'),
      2  => array('title' => 'Оцениваемый ID', 'name' => 'assess_id', 'type' => 'text', 'data_type' => 'number', 'field' => 'a.`assessID`'),
      3  => array('title' => 'Респондент Имя', 'name' => 'respondent_name', 'type' => 'text', 'data_type' => 'string', 'field' => 'pm.`name`'),
      4  => array('title' => 'Респондент Фамилия', 'name' => 'respondent_surname', 'type' => 'text', 'data_type' => 'string', 'field' => 'pm.`surname`'),
      5  => array('title' => 'Респондент ID', 'name' => 'respondent_id', 'type' => 'text', 'data_type' => 'number', 'field' => 'r.`respondentID`'),
      6  => array('title' => 'Текст', 'name' => 'text', 'type' => 'text', 'data_type' => 'string', 'field' => array('pma.`search_text`', 'pm.`search_text`', 'rt.`name`')),
      7  => array('title' => 'Группа ', 'name' => 'session_id', 'type' => 'select', 'data_type' => 'number', 'field' => 's.`sessionID`', 'values' => (array(0 => 'Любая') + $sessions)),
    );
    $filterData = array(
      'FILTERS'  => $filters,
      'BASE_URL'  => '/projects/'.$context['project']['projectID'].'/members/scheme360/',
      'SORT'  => $sort,
      'ORDER'  => $order,
    );
    // проверяем, введен ли фильтр
    $filterValue = DataFilter::getFilterValue($filters, $_GET, $this->registry->getDbConnect());
    if ($filterValue['valid']) {
      $filterData = array_merge($filterData, $filterValue);
    }
    
    // компонент отображения фильтра
    $context['APPLICATION']->IncludeComponent('ecoplay:data.filter', '', $filterData);
    
    $cnt = $this->registry->getDbHelper('MembersHelper')->getRespondentsForScheme360Cnt($context['project']['projectID'], $availableSessionsIDs, $filterValue['filter_strings']);
    $onPage = 100;
    $pagesCnt = ceil($cnt / $onPage);
    $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;
    
    if ($isProjectAdmin && isset($_POST['deny'])) {
      if (isset($_POST['all']) && $_POST['all']) {
        $selectedIDs = $this->registry->getDbHelper('MembersHelper')->getRespondentsIDsForScheme360($context['project']['projectID'], $availableSessionsIDs, $filterValue['filter_strings']);
      }
      else {
        $selectedIDs = $_POST['respondents_ids'];
      }
    
      $this->registry->getDbHelper('MembersHelper')->editRespondents($selectedIDs, array('deny_deletion' => 1));
    }
    
    $this->component->arResult['selectedIDs'] = $selectedIDs;
    
    $respondents = $this->registry->getDbHelper('MembersHelper')->getRespondentsForScheme360($context['project']['projectID'], $availableSessionsIDs, $page, $onPage, $sorts[$sort], $order, $filterValue['filter_strings']);
    $this->component->arResult['respondents'] = $respondents;
    
    if (isset($_GET['checkAll'])) {
      $selectedIDs = array();
      foreach ($respondents as $responent) {
        $selectedIDs[] = $responent['respondentID'];
      }
      $this->component->arResult['selectedIDs'] = $selectedIDs;
    }
    
    $viewHelper = new \Ecoplay\View\Helper();
    
    $this->component->arResult['sort'] = $sort;
    $this->component->arResult['order'] = $order;
    
    $this->component->arResult['cntInfo'] = ($pagesCnt == 1) ? $cnt : 0;
    
    $roles = $this->registry->getDbHelper('ProjectsHelper')->getProjectRoles($context['project']['projectID']);
    $selfRoleID = 0;
    foreach ($roles as $role) {
      if ($role['fixed_role'] == 'self') {
        $selfRoleID = $role['roleID'];
        break;
      }
    }
    $this->component->arResult['selfRoleID'] = $selfRoleID;
    
    // выводим таблицу с данными
    $this->component->IncludeComponentTemplate('table');
    
    $navResult = new \CDBResult();
    $navResult->NavPageCount = ceil($cnt / $onPage);
    $navResult->NavPageNomer = $page;
    $navResult->NavNum = 1;
    $navResult->NavPageSize = $onPage;
    $navResult->NavRecordCount = $cnt;
    $navResult->nPageWindow = 11;
    
    $context['APPLICATION']->IncludeComponent('ecoplay:system.pagenavigation', '', array(
      'NAV_RESULT' => $navResult,
      'NAV_URL'  => '/projects/'.$context['project']['projectID'].'/members/scheme360/',
      'NAV_QUERY_STRING' => 'sort='.$sort.'&order='.$order.($filterQueryString ? '&'.$filterQueryString : '').(isset($_GET['checkAll']) ? '&checkAll=1' : ''),
    ));
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Схема 360&deg;');
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
      3 => array(
        'title'  => 'Схема 360&deg;',
      ),
    )));
    
    $this->component->IncludeComponentTemplate();
  }
}