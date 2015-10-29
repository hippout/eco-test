<?php 

namespace Ecoplay\Controller\Project\GroupKeyRespondents;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Controller\EventListener;

class TypeTesting extends Base
{
  public function execute($context)
  {    
    $context['APPLICATION']->AddHeadScript('/js/icheck/icheck.min.js');
    $context['APPLICATION']->SetAdditionalCSS('/js/icheck/skins/minimal/eco.css');
       
    $cnt = $this->registry->getDbHelper('LinearProjectsHelper')->getGroupKeyRespondentsCount($context['project']['projectID'], $context['groupKey']['group_keyID']);
    $onPage = 100;
    $pagesCnt = ceil($cnt / $onPage);
    $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;
    
    $this->component->arResult['cntInfo'] = ($pagesCnt == 1) ? $cnt : 0;
    
    $respondents = $this->registry->getDbHelper('TestsSeancesHelper')->getGroupKeyRespondents($context['project']['projectID'], $context['groupKey']['group_keyID'], $page, $onPage);
    $this->component->arResult['respondents'] = $respondents;
    $this->component->arResult['groupKey'] = $context['groupKey'];    
    
    $languagesIDs = json_decode($context['project']['~languages']);
    $languagesSrc = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs($languagesIDs);
    $languages = array();
    foreach ($languagesSrc as $language) {
      $languages[$language['langID']] = $language['name'];
    }
    $this->component->arResult['languages'] = $languages;
    
    $this->component->arResult['statuses'] = $this->registry->getDbHelper('MembersHelper')->getTestingRespondentsStatusesNames();
    
    $this->component->IncludeComponentTemplate('linear/table');
    
    $navResult = new \CDBResult();
    $navResult->NavPageCount = ceil($cnt / $onPage);
    $navResult->NavPageNomer = $page;
    $navResult->NavNum = 1;
    $navResult->NavPageSize = $onPage;
    $navResult->NavRecordCount = $cnt;
    $navResult->nPageWindow = 11;
    
    $context['APPLICATION']->IncludeComponent('ecoplay:system.pagenavigation', '', array(
      'NAV_RESULT' => $navResult,
      'NAV_URL'  => '/projects/'.$context['project']['projectID'].'/settings/groupkeys/'.$context['groupKey']['group_keyID'].'/respondents/',
      'NAV_QUERY_STRING' => isset($_GET['checkAll']) ? 'checkAll=1' : '',
    ));
    
    $viewHelper = new \Ecoplay\View\Helper();
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Респонденты группового ключа');
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
        'link' => '/projects/'.$context['project']['projectID'].'/settings/groupkeys/',
        'title' => ' Групповые ключи',
      ),
      4  => array(        
        'title' => $context['groupKey']['name'],
      ),
      5  => array(
        'title' => 'Список Респондентов',
      ),
    )));
    
    $this->component->IncludeComponentTemplate('linear/template');
  }
}