<?php 

namespace Ecoplay\Controller\Project\Members;

use Ecoplay\Controller\Base as Base;
use Ecoplay\Helper\DataFilter as DataFilter;

class Type360 extends Base
{
  public function execute($context)
  {    
    $context['APPLICATION']->AddHeadScript('/js/forms.js');    
     
    // проверка прав доступа к разделу
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'members', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
    
    $this->component->arResult['canImport'] = $this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'imports', $context['project']['projectID']);
    
    $languagesIDs = json_decode($context['project']['~languages']);
    $languagesSrc = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs($languagesIDs);
    $languages = array();
    foreach ($languagesSrc as $language) {
      $languages[$language['langID']] = $language['name'];
    }
    $this->component->arResult['languages'] = $languages;
    $this->component->arResult['languagesFull'] = $languagesSrc;
    
    $sorts = array(
      'projects_memberID'  => 'projects_memberID',
      'FIO'  => 'FIO',
      'position'  => 'position',
      'email'  => 'email',
      'private_lk_access_key'  => 'private_lk_access_key',
      'assess_count'  => 'assess_count',
      'respondent_count'  => 'respondent_count',
      'userID'  => 'userID',
      'projectID'  => 'projectID',
      'status'  => 'status',
      'langID'  => 'langID',
      'language'  => 'language',
      'department'  => 'department',
    );
    if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sorts)) {
      $sort = $_GET['sort'];
    }
    else {
      $sort = 'projects_memberID';
    }
    $order = (isset($_GET['order']) && in_array($_GET['order'], array('asc', 'desc'))) ? $_GET['order'] : 'asc';
    
    $filterQueryString = DataFilter::getFilterQueryString($_GET);
    $this->component->arResult['filterQueryString'] = $filterQueryString;
    
    // настройки фильтров для данных
    $filters = array(
      0  => array('title' => 'Имя', 'name' => 'name', 'type' => 'text', 'data_type' => 'string', 'field' => '`name`'),
      1  => array('title' => 'Фамилия', 'name' => 'surname', 'type' => 'text', 'data_type' => 'string', 'field' => '`surname`'),
      2  => array('title' => 'ID', 'name' => 'id', 'type' => 'text', 'data_type' => 'number', 'field' => '`projects_memberID`'),
      3  => array('title' => 'Текст', 'name' => 'text', 'type' => 'text', 'data_type' => 'string', 'field' => array('`search_text`')),
      4  => array('title' => 'Язык ', 'name' => 'language', 'type' => 'select', 'data_type' => 'number', 'field' => '`langID`', 'values' => (array(0 => 'Любой') + $languages)),
      5  => array('title' => 'Оценивается', 'name' => 'assess', 'type' => 'range', 'data_type' => 'number', 'field' => '`assess_count`', 'sql_type' => 'where'),
      6  => array('title' => 'Оценивает ', 'name' => 'respondent', 'type' => 'range', 'data_type' => 'number', 'field' => '`respondent_count`', 'sql_type' => 'where'),
    );
    $filterData = array(
      'FILTERS'  => $filters,
      'BASE_URL'  => '/projects/'.$context['project']['projectID'].'/settings/members/',
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
    
    $cnt = $this->registry->getDbHelper('MembersHelper')->getProjectsMembersCount($context['project']['projectID'], $filterValue['filter_strings']);
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
      'NAV_URL'  => '/projects/'.$context['project']['projectID'].'/settings/members/',
      'NAV_QUERY_STRING' => 'sort='.$sort.'&order='.$order.($filterQueryString ? '&'.$filterQueryString : ''),
    ));
    
    $membersSrc = $this->registry->getDbHelper('MembersHelper')->getProjectsMembersForView($context['project']['projectID'], $page, $onPage, $sorts[$sort], $order, $filterValue['filter_strings']);
    $members = array();
    foreach ($membersSrc as $member) {        
        $member['FIO'] = ($member['name'] && $member['surname']) ?
            '<span class="surname">'.$member['surname'].'</span> <span class="name">'.$member['name'].'</span>' :
            '<span class="surname">'.$member['surname'].'</span><span class="name">'.$member['name'].'</span>';
        $members[] = $member;
    }
    
    $viewHelper = new \Ecoplay\View\Helper();
    $this->component->arResult['cnt'] = count($this->component->arResult['members']);
    $tData = str_replace("'", "\'", $viewHelper->prepareJsonDataForTable($members, array('projects_memberID', 'FIO', 'position', 'email', 'private_lk_access_key', 'assess_count', 'respondent_count', 'userID', 'projectID', 'status', 'langID', 'language', 'department'),
      array(), array('language' => $languages)));
    $tData = str_replace("\\", "\\\\", $tData);
    $this->component->arResult['table_data'] = $tData;
    
    $this->component->arResult['projectID'] = $context['project']['projectID'];
    $this->component->arResult['sort'] = $sort;
    $this->component->arResult['order'] = $order;
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Все участники');
    $context['APPLICATION']->SetPageProperty('navigation', $viewHelper->generateNavigation(array(
      0  => array(
        'title' => 'Главная',
        'link'  => '/'
      ),
      1  => array(
        'title' => 'Проекты',
        'link' => '/projects/',
      ),
      2  => array(
        'link' => '/projects/'.$context['project']['projectID'].'/continuing/info/',
        'title' => $context['project']['project_name'],
      ),
      3  => array(
        'title' => 'Участники проекта',
      ),
    )));
    
    $this->component->IncludeComponentTemplate();
  }
}