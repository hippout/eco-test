<?php 

namespace Ecoplay\Controller\Project\Respondents;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Helper\DataFilter as DataFilter;
use Ecoplay\Controller\EventListener;

class TypeLinear extends Base
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
    
    if (isset($_POST['type'])) {
      if ($_POST['type'] == 'exist') {
        if (isset($_POST['member_id'])) {
    
          // проверяем, может уже есть респондент с таким участником
          $existRespondent = $this->registry->getDbHelper('MembersHelper')->findRespondentByMemberIDAndSessionID($context['project']['projectID'], $_POST['member_id'], $_POST['session_id']);
                    
          if (!$existRespondent) {
            // добавляем респондента
            $respondentID = $this->registry->getDbHelper('MembersHelper')->addRespondent(array(
              'project_memberID'  => intval($_POST['member_id']),
              'active'  => 1,
              'projectID'  => $context['project']['projectID'],
              'sessionID'  => intval($_POST['session_id']),
              'stat1_assessID'  => 0,
              'status'  => \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_NEW,
              'private_access_key'  => $this->registry->getModel('Auth')->generateUid('prep_respondents', 'private_access_key'),
              'stat1_roleID'  => 0,
              'last_email'  => '0000-00-00 00:00:00',
              'langID'  => 0,
              'last_entrance'  => '0000-00-00 00:00:00',
              'deny_deletion'  => 0,
              'addedd_by_assess'  => 0,
              'need_remind'  => 0,
            ));
    
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondentID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
          }
        }
      }
      else {
        // пробуем добавить участника
        $validations = array(
          'name'  => 'anything',
          'surname'  => 'anything',
          'email'  => 'email',
          'position'  => 'anything',
          'department'  => 'anything',
          'language'  => 'identifier',
        );
    
        $required = array('name', 'email', 'language');
        $validator = new \Ecoplay\Form\Validator($validations, $required, array());
    
        if ($validator->validate($_POST)) {
          // проверяем чтобы не было уже участника с таким email
          if (!$this->registry->getDbHelper('MembersHelper')->findByEmailAndProjectId($_POST['email'], $context['project']['projectID'])) {
            $memberID = $this->registry->getDbHelper('MembersHelper')->addMember(trim($_POST["name"]), trim($_POST["surname"]), trim($_POST["position"]), trim($_POST["email"]), $context['project']['projectID'],
              $this->registry->getModel('Auth')->generateUid('prep_projects_members', 'private_lk_access_key'), intval($_POST["language"]), 1, 'new', trim($_POST['department']));
            
            $eventListener = new EventListener($this->registry, $context['project'], $_SERVER["DOCUMENT_ROOT"].'/');
            $eventListener->executeEvent('MemberAdded', array(
              'memberID'  => $memberID,
            ), true);
            
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'member', $memberID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
    
            // добавляем респондента
            $respondentID = $this->registry->getDbHelper('MembersHelper')->addRespondent(array(
              'project_memberID'  => $memberID,
              'active'  => 1,
              'projectID'  => $context['project']['projectID'],
              'sessionID'  => intval($_POST['session_id']),
              'stat1_assessID'  => 0,
              'status'  => \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_NEW,
              'private_access_key'  => $this->registry->getModel('Auth')->generateUid('prep_respondents', 'private_access_key'),
              'stat1_roleID'  => 0,
              'last_email'  => '0000-00-00 00:00:00',
              'langID'  => 0,
              'last_entrance'  => '0000-00-00 00:00:00',
              'deny_deletion'  => 0,
              'addedd_by_assess'  => 0,
              'need_remind'  => 0,
            ));
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondentID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
          }
          else {
            $this->component->arResult['statusError'] = 'Участник с таким email ('.$_POST['email'].') уже существует';
          }
        }
      }
    }
    
    if ($_FILES["filepath"] && !$_FILES['filepath']['error']) {
      $sessionID = (int)$_POST["fgroup"];
      if ($sessionID) {
        $session = $this->registry->getDbHelper('ProjectsHelper')->findSessionById($sessionID);
      }
    
      // обрабатываем загруженный файл
      $csvImporter = new \Ecoplay\Import\Csv($_FILES["filepath"]["tmp_name"], $_SERVER["DOCUMENT_ROOT"]."/temp/csv/");
      $data = $csvImporter->parse();
    
      $sessionIndex = $csvImporter->getColumnIndex('Группа');
      if (!((!$sessionIndex || !$data[0][$sessionIndex]) && (!$sessionID || !$session))) {
        // определяем группу
        if ($sessionIndex && $data[0][$sessionIndex]) {
          // ищем группу
          $session = $this->registry->getDbHelper('ProjectsHelper')->findSessionByProjectIDAndName($context['project']['projectID'], $data[0][$sessionIndex]);
          if ($session) {
            $sessionID = $session['sessionID'];
          }
          else {
            $session = array(
              'name'  => $data[0][$sessionIndex],
              'active'  => 1,
              'status'  => 'active',
              'projectID'  => $context['project']['projectID'],
            );
            $sessionID = $this->registry->getDbHelper('ProjectsHelper')->addSession($session);
            $session['sessionID'] = $sessionID;
          }
        }
    
        $inserted = $skipped = 0;
        $validations = array(
          0  => 'anything',
          1  => 'anything',
          2  => 'email',
          3  => 'anything',
          4  => 'anything',
        );
        $required = array(2);
    
        $validator = new \Ecoplay\Form\Validator($validations, $required, array());
        $projectLangID = $this->registry->getModel('Projects')->getDefaultLangID($context['project']);
    
        $languageIndex = $csvImporter->getColumnIndex('Язык');
        $languagesIDs = array();
        $availableLanguages = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs(json_decode($context['project']['~languages']));
        foreach ($availableLanguages as $language) {
          $languagesIDs[$language['abbr']] = $language['langID'];
        }
    
        foreach ($data as $row) {
    
          if ($sessionIndex && $row[$sessionIndex] && $row[$sessionIndex] != $session['name']) {
            // ищем группу
            $session = $this->registry->getDbHelper('ProjectsHelper')->findSessionByProjectIDAndName($context['project']['projectID'], $row[$sessionIndex]);
            if ($session) {
              $sessionID = $session['sessionID'];
            }
            else {
              $session = array(
                'name'  => $row[$sessionIndex],
                'active'  => 1,
                'status'  => 'active',
                'projectID'  => $context['project']['projectID'],
              );
              $sessionID = $this->registry->getDbHelper('ProjectsHelper')->addSession($session);
              $session['sessionID'] = $sessionID;
            }
          }
    
          // валидируем исходные данные
          if (!$validator->validate($row)) {
            $skipped++;
          }
          elseif (!$row[0] && !$row[1]) { // должно быть хотябы или имя, или фамилия
            $skipped++;
          }
          else {
            // ищем такого участника уже в базе
            $member = $this->registry->getDbHelper('MembersHelper')->findByEmailAndProjectId($row[2], $context['project']['projectID']);
    
            if (!$member) { // добавляем участника
              // определяем язык по структуре
              $staff = $this->registry->getDbHelper('MembersHelper')->findStaffByProjectIDAndEmail($context['project']['projectID'], $row[2]);
              $langID = ($languageIndex && $row[$languageIndex] && array_key_exists($row[$languageIndex], $languagesIDs)) ? $languagesIDs[$row[$languageIndex]] : $projectLangID;
    
              $memberID = $this->registry->getDbHelper('MembersHelper')->addMember($row[0], $row[1], $row[3], $row[2], $context['project']['projectID'],
                $this->registry->getModel('Auth')->generateUid('prep_projects_members', 'private_lk_access_key', false), $langID, 1, 'new', $row[4]);
              
              $eventListener = new EventListener($this->registry, $context['project'], $_SERVER["DOCUMENT_ROOT"].'/');
              $eventListener->executeEvent('MemberAdded', array(
                'memberID'  => $memberID,
                'staff' => $staff,
              ), true);
              
              $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'member', $memberID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
            }
            else {
              $memberID = $member['projects_memberID'];
            }
    
            if ($this->registry->getDbHelper('MembersHelper')->findRespondentByProjectIDAndSessionIDAndMemberID($context['project']['projectID'], $sessionID, $memberID)) { // такой оцениваемый уже есть
              $skipped++;
            }
            else {
              $respondentID = $this->registry->getDbHelper('MembersHelper')->addRespondent(array(
                'projectID'  => $context['project']['projectID'],
                'sessionID'  => $sessionID,
                'project_memberID'  => $memberID,
                'active'  => 1,
                'status'  => 'new',
                'stat1_assessID'  => 0,
                'private_access_key'  => $this->registry->getModel('Auth')->generateUid('prep_respondents', 'private_access_key', false),
                'stat1_roleID'  => 0,
              ));
              $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondentID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
              $inserted++;
            }
          }
    
        }
    
        $this->component->arResult['statusSuccess'] = 'Импортировано: '.$inserted.', проигнорировано: '.$skipped;
      }
    }
    
    $statuses = $this->registry->getDbHelper('MembersHelper')->getLinearRespondentsStatusesNames();
    $this->component->arResult['statuses'] = $statuses;
    
    $sorts = array(
      'respondentID'  => 'respondentID',
      'name'  => 'name',
      'surname'  => 'surname',
      'email'  => 'email',
      'position'  => 'position',
      'status'  => 'status',
      'private_lk_access_key'  => 'private_lk_access_key',
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
      0  => array('title' => 'Имя', 'name' => 'name', 'type' => 'text', 'data_type' => 'string', 'field' => 'm.`name`'),
      1  => array('title' => 'Фамилия', 'name' => 'surname', 'type' => 'text', 'data_type' => 'string', 'field' => 'm.`surname`'),
      2  => array('title' => 'ID', 'name' => 'id', 'type' => 'text', 'data_type' => 'number', 'field' => 'r.`respondentID`'),
      3  => array('title' => 'Текст', 'name' => 'text', 'type' => 'text', 'data_type' => 'string', 'field' => array('m.`search_text`')),
      4  => array('title' => 'Статус ', 'name' => 'status', 'type' => 'select', 'data_type' => 'string', 'field' => 'r.`status`', 'values' => (array(0 => 'Любой') + $statuses)),
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
    
    // выполняем с респондентами действия
    if (isset($_POST['respondents_ids'])) {
    
      if (isset($_POST['all']) && $_POST['all']) {
        $selectedIDs = $this->registry->getDbHelper('LinearProjectsHelper')->getProjectRespondentsIDs($context['project']['projectID'], $filterValue['filter_strings']);
      }
      else {
        $selectedIDs = $_POST['respondents_ids'];
      }
    
      if (isset($_POST['mode'])) {
        if ($_POST['mode'] == 'send_notifications') {
          $editedRespondents =  $this->registry->getDbHelper('LinearProjectsHelper')->getRespondentsByIDs($selectedIDs);
          
          $this->registry->getDbHelper('MembersHelper')->editRespondents($selectedIDs, array(
            'need_remind'  => 1,
          ));
          
          foreach ($editedRespondents as $respondent) {
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondent['respondentID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $respondent);
          }
        }
        elseif ($_POST['mode'] == 'reset') {
          /*$editedRespondents =  $this->registry->getDbHelper('LinearProjectsHelper')->getRespondentsByIDs($selectedIDs);
          
          $this->registry->getDbHelper('MembersHelper')->editRespondents($selectedIDs, array(
            'status'  => \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE,
          ));
          
          foreach ($editedRespondents as $respondent) {
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondent['respondentID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $respondent);
          }*/            
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
        elseif ($_POST['mode'] == 'return') {
            if ($context['project']['allow_edit_seance_answers']) {
                $seances = $this->registry->getDbHelper('SeancesHelper')->findSeanceByProjectAndRespondentsIDs($context['project']['projectID'], $selectedIDs);
            
                foreach ($seances as $seance) {
                    if ($seance['state'] == \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE) { 
                        $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($seance['respondentID']);        
                        $this->registry->getDbHelper('MembersHelper')->editRespondent($seance['respondentID'], array(
                            'status'  => \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE,
                        ));
            
                        // обновляем сеанс
                        $lastScreen = $this->registry->getDbHelper('BlanksHelper')->getBlankPrevScreen($seance['blankID'], $seance['last_screenID']);                    
                        $this->registry->getDbHelper('SeancesHelper')->editSeance($seance['seanceID'], array(
                            'state' => \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_PROGRESS,
                            'last_screenID' => $lastScreen ? $lastScreen['screenID'] : 0,
                        ));
                    }
                }
            }
        }
        else {
          $newStatus = false;
          switch ($_POST['mode']) {
            case 'to_new':
              $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_NEW;
              break;          
            case 'to_active':
              $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE;
              break;
            case 'to_complete':
              $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_COMPLETE;
              break;
            case 'to_archive':
              $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ARCHIVE;
              break;
          }
      
          if ($newStatus) {
            $editedRespondents =  $this->registry->getDbHelper('LinearProjectsHelper')->getRespondentsByIDs($selectedIDs);
      
            $this->registry->getDbHelper('MembersHelper')->editRespondents($selectedIDs, array(
              'status'  => $newStatus,
            ));
      
            foreach ($editedRespondents as $respondent) {
              $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondent['respondentID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $respondent);
            }
          }
        }
      }
    }    
    
    $arResult['selectedIDs'] = $selectedIDs;
    
    // получаем респондентов проекта
    $cnt = $this->registry->getDbHelper('LinearProjectsHelper')->getProjectRespondentsCount($context['project']['projectID'], $filterValue['filter_strings']);
    $onPage = 100;
    $pagesCnt = ceil($cnt / $onPage);
    $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;    
    
    $respondents = $this->registry->getDbHelper('LinearProjectsHelper')->getProjectRespondents($context['project']['projectID'], $page, $onPage, $sorts[$sort], $order, $filterValue['filter_strings']);
    $this->component->arResult['respondents'] = $respondents;
    $this->component->arResult['sort'] = $sort;
    $this->component->arResult['order'] = $order;
    
    $this->component->arResult['cntInfo'] = ($pagesCnt == 1) ? $cnt : 0;
    
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
    
    $this->component->arResult['sessions'] = $this->registry->getDbHelper('ProjectsHelper')->getSessionsByProjectID($context['project']['projectID']);
    
    $languagesIDs = json_decode($context['project']['~languages']);
    $languagesSrc = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs($languagesIDs);
    $languages = array();
    foreach ($languagesSrc as $language) {
      $languages[$language['langID']] = $language['name'];
    }
    $this->component->arResult['languages'] = $languages;
    
    $this->component->arResult['project'] = $context['project'];
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Респонденты');
    $viewHelper = new \Ecoplay\View\Helper();
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
    
    $this->component->IncludeComponentTemplate("template_linear");
  }
}