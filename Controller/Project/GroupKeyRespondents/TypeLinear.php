<?php 

namespace Ecoplay\Controller\Project\GroupKeyRespondents;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Controller\EventListener;

class TypeLinear extends Base
{
  public function execute($context)
  {    
      $context['APPLICATION']->AddHeadScript('/js/forms.js');
    $context['APPLICATION']->AddHeadScript('/js/icheck/icheck.min.js');
    $context['APPLICATION']->SetAdditionalCSS('/js/icheck/skins/minimal/eco.css');
    
    // выполняем с респондентами действия
    if (isset($_POST['respondents_ids'])) {
        $selectedIDs = $_POST['respondents_ids'];
        if (isset($_POST['mode'])) {
            if ($_POST['mode'] == 'reset') {
                $seances = $this->registry->getDbHelper('SeancesHelper')->findSeanceByProjectAndRespondentsIDs($context['project']['projectID'], $selectedIDs);
                
                foreach ($seances as $seance) {
                    // деактивируем текущий сеанс
                    $this->registry->getDbHelper('SeancesHelper')->editSeance($seance['seanceID'], array('active' => 0, 'private_access_key' => ''));
                    $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'seance', $seance['seanceID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_DELETE, $seance);
                
                    $this->registry->getDbHelper('MembersHelper')->editRespondent($seance['respondentID'], array(
                        'status'    => 'active',
                    ));
                    
                    // создаем новый сеанс
                    $BlankDataSource = new \Ecoplay\Model\BlankDataSource($this->registry->getDbConnect(), 0, 0);
                    $seanceKey = $BlankDataSource->addSeanceAndCookies($seance['private_access_key']);
                    $newSeance = $this->registry->getDbHelper('SeancesHelper')->findSeanceByKey($seanceKey);
                    $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'seance', $newSeance['seanceID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
                }
            }
        }
    }
       
    $cnt = $this->registry->getDbHelper('LinearProjectsHelper')->getGroupKeyRespondentsCount($context['project']['projectID'], $context['groupKey']['group_keyID']);
    $onPage = 100;
    $pagesCnt = ceil($cnt / $onPage);
    $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;
    
    $this->component->arResult['cntInfo'] = ($pagesCnt == 1) ? $cnt : 0;
    
    $respondents = $this->registry->getDbHelper('LinearProjectsHelper')->getGroupKeyRespondents($context['project']['projectID'], $context['groupKey']['group_keyID'], $page, $onPage);
    $this->component->arResult['respondents'] = $respondents;
    $this->component->arResult['groupKey'] = $context['groupKey'];    
    
    $languagesIDs = json_decode($context['project']['~languages']);
    $languagesSrc = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs($languagesIDs);
    $languages = array();
    foreach ($languagesSrc as $language) {
      $languages[$language['langID']] = $language['name'];
    }
    $this->component->arResult['languages'] = $languages;
    
    $this->component->arResult['statuses'] = $this->registry->getDbHelper('MembersHelper')->getLinearRespondentsStatusesNames();
    
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