<?php 

namespace Ecoplay\Controller\Project\Respondents;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Helper\DataFilter as DataFilter;

class Type360 extends Base
{
  public function execute($context)
  { 
    // проверка прав доступа к разделу    
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'members', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
    
    $context['APPLICATION']->AddHeadScript('/js/forms.js');
    $context['APPLICATION']->AddHeadScript('/js/icheck/icheck.min.js');
    $context['APPLICATION']->SetAdditionalCSS('/js/icheck/skins/minimal/eco.css');
  
    $availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID']);
    $viewHelper = new \Ecoplay\View\Helper();
  
    if (count($availableSessionsIDs)) {      
      $selectedIDs = array();
  
      $sorts = array(
        'project_memberID'  => 'project_memberID',
        'name'  => 'name',
        'surname'  => 'surname',
        'email'  => 'email',
        'position'  => 'position',
        'private_lk_access_key'  => 'private_lk_access_key',
        'amount'  => 'amount',
        'seances'  => 'seances',
      );
      if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sorts)) {
        $sort = $_GET['sort'];
      }
      else {
        $sort = 'project_memberID';
      }
      $order = (isset($_GET['order']) && in_array($_GET['order'], array('asc', 'desc'))) ? $_GET['order'] : 'asc';
  
      $filterQueryString = DataFilter::getFilterQueryString($_GET);
      $this->component->arResult['filterQueryString'] = $filterQueryString;
  
      // настройки фильтров для данных
      $filters = array(
        0  => array('title' => 'Имя', 'name' => 'name', 'type' => 'text', 'data_type' => 'string', 'field' => 'pm.`name`'),
        1  => array('title' => 'Фамилия', 'name' => 'surname', 'type' => 'text', 'data_type' => 'string', 'field' => 'pm.`surname`'),
        2  => array('title' => 'ID', 'name' => 'id', 'type' => 'text', 'data_type' => 'number', 'field' => 'r.`respondentID`'),
        3  => array('title' => 'Текст', 'name' => 'text', 'type' => 'text', 'data_type' => 'string', 'field' => array('pm.`search_text`')),
        4  => array('title' => 'Анкет', 'name' => 'amount', 'type' => 'range', 'data_type' => 'number', 'field' => '`amount`'),
      );
      $filterData = array(
        'FILTERS'  => $filters,
        'BASE_URL'  => '/projects/'.$context['project']['projectID'].'/groups/'.((isset($_GET['TYPE']) && $_GET['TYPE']) ? $_GET['TYPE'].'/' : '').'respondents/',
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
  
      $cnt = $this->registry->getDbHelper('MembersHelper')->getRespondentsCountByProjectIDAndSessionsIds($context['project']['projectID'], $availableSessionsIDs, $filterValue['filter_strings']);
      $onPage = 100;
      $pagesCnt = ceil($cnt / $onPage);
      $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;
  
      // отправляем email уведомления
      if (isset($_POST['respondents_ids'])) {
  
        if (isset($_POST['all']) && $_POST['all']) {
          $selectedIDs = $this->registry->getDbHelper('MembersHelper')->getRespondentsIDsByProjectIDAndSessionsIds($context['project']['projectID'], $availableSessionsIDs, $filterValue['filter_strings']);
        }
        else {
          $selectedIDs = $_POST['respondents_ids'];
        }
  
        if (isset($_POST['mode']) && $_POST['mode'] == 'send_notifications') {
          $emailedRespondents = $this->registry->getDbHelper('MembersHelper')->getMembersRespondentsForNotification($context['project']['projectID'], $selectedIDs);
  
          $this->registry->getDbHelper('MembersHelper')->editRespondents(array_keys($emailedRespondents), array(
            'need_remind'  => 1,
          ));
          $this->registry->getModel('ActionsLogger')->logMultiSimple($context['USER']->GetID(), 'respondent', array_keys($emailedRespondents), \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_NOTIFY);
        }
      }
  
      $this->component->arResult['selectedIDs'] = $selectedIDs;
  
      $respondents = $this->registry->getDbHelper('MembersHelper')->getRespondentsByProjectIDAndSessionsIdsPaged($context['project']['projectID'], $availableSessionsIDs, $page, $onPage, $sorts[$sort], $order, $filterValue['filter_strings']);
      $this->component->arResult['respondents'] = $respondents;
  
      if (isset($_GET['checkAll'])) {
        $selectedIDs = array();
        foreach ($respondents as $responent) {
          $selectedIDs[] = $responent['project_memberID'];
        }
        $this->component->arResult['selectedIDs'] = $selectedIDs;
      }
  
      $fillings =  array();
      $emailsCnt = array();
      if (count($respondents)) {
        $membersIDs = array();
        foreach ($respondents as $respondent) {
          $membersIDs[] = $respondent['project_memberID'];
        }
  
        $fillings = $this->registry->getDbHelper('MembersHelper')->getRespondentsFillingsByMembersIDs($context['project']['projectID'], $availableSessionsIDs, $membersIDs);
        $emailsCnt = $this->registry->getDbHelper('MembersHelper')->getMembersRespondentsAssessForNotificationCnt($context['project']['projectID'], $membersIDs);
      }
      $this->component->arResult['fillings'] = $fillings;
      $this->component->arResult['emailsCnt'] = $emailsCnt;
  
      $this->component->arResult['sort'] = $sort;
      $this->component->arResult['order'] = $order;
  
      $this->component->arResult['cntInfo'] = ($pagesCnt == 1) ? $cnt : 0;
  
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
        'NAV_URL'  => '/projects/'.$context['project']['projectID'].'/groups/'.((isset($_GET['TYPE']) && $_GET['TYPE']) ? $_GET['TYPE'].'/' : '').'respondents/',
        'NAV_QUERY_STRING' => 'sort='.$sort.'&order='.$order.($filterQueryString ? '&'.$filterQueryString : '').(isset($_GET['checkAll']) ? '&checkAll=1' : ''),
      ));
  
      /*$this->component->arResult['cnt'] = count($respondents);
       $this->component->arResult['table_data'] = $viewHelper->prepareJsonDataForTable($respondents, array('respondentID', 'name', 'surname', 'email', 'position', 'private_lk_access_key', 'amount', 'seances'));*/
       
    }
    else {
      //$this->component->arResult['table_data'] = null;
      $this->component->arResult['respondents'] = null;
    }
  
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Респонденты');
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
        'title'  => 'Респонденты',
      ),
    )));
  
    $this->component->IncludeComponentTemplate();
  }
}