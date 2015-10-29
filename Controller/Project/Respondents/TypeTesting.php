<?php 

namespace Ecoplay\Controller\Project\Respondents;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Helper\DataFilter as DataFilter;
use Ecoplay\Controller\EventListener;

class TypeTesting extends Base
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
    
    $availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID'], $context['project']['project_type']);    
    $viewHelper = new \Ecoplay\View\Helper();
    
    if (count($availableSessionsIDs)) {
        
        // нормогруппы для генераторов отчетов
        $projectSettings = json_decode($context['project']['~param_roles'], true);
        $haveValuation = false;
        if (array_key_exists('rgenerators', $projectSettings) && $projectSettings['rgenerators']) {
            $generatorsSrc = $this->registry->getDbHelper('ReportsHelper')->getGeneratorsCollectionByIds($projectSettings['rgenerators']);
            foreach ($generatorsSrc as $generator) {
                if ($generator['use_valuation_group']) {
                    $haveValuation = true;
                    break;
                }
            }
        }
        if ($haveValuation) {
            //  определяем тип админа (экопси или нет)
            $isEcopsy = false;
            $groups = $this->registry->getDbHelper('UsersHelper')->getUserGroups($context['USER']->GetID());
            if (count($groups) > 1) {
                $isEcopsy = true;
            }
            else {
                $clientID = $this->registry->getDbHelper('UsersHelper')->getUserClient($context['USER']->GetID());
                if ($clientID == \Ecoplay\Helper\Db\ClientsHelper::ECOPSY_CLIENT_ID) {
                    $isEcopsy = true;
                }
            }
        
            $this->component->arResult['valuationGroups'] = $this->registry->getDbHelper('TestsHelper')->getProjectValuationGroups($context['project']['projectID'], $isEcopsy);
        }
        $this->component->arResult['haveValuation'] = $haveValuation;
        
      if (isset($_POST['type'])) {
        if ($_POST['type'] == 'edit') {
          // находим респондента
          $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($_POST['respondentID']);
          if ($respondent) {
            // обновляем участника
            $member = $this->registry->getDbHelper('MembersHelper')->findById($respondent['project_memberID']);
            if ($member['email'] != $_POST['email'] && $_POST['email']) {
              $sameEmailMember = $this->registry->getDbHelper('MembersHelper')->findByEmailAndProjectId($_POST['email'], $context['project']['projectID']);
              $email = $sameEmailMember ? $member['email'] : $_POST['email'];
            }
            else {
              $email = $member['email'];
            }
            $this->registry->getDbHelper('MembersHelper')->updateMember($respondent['project_memberID'], $_POST['name'], $_POST['surname'], $_POST['position'],
              $email, $_POST['language'], $_POST['department']);
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'member', $respondent['project_memberID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $member);
            
            if ($respondent['sessionID'] != $_POST['session_id']) {
              $seance = $this->registry->getDbHelper('TestsSeancesHelper')->findSeanceByProjectIDAndRespondentID($context['project']['projectID'], $respondent['respondentID']);
              if (!$seance) {
                $this->registry->getDbHelper('MembersHelper')->editRespondent($respondent['respondentID'], array(
                  'sessionID' => $_POST['session_id'],
                ));
                $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondent['respondentID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $respondent);
              }
            }
          }
          
          echo('Редактирование<br/>');
        }
        elseif ($_POST['type'] == 'exist') {
          if (isset($_POST['member_id'])) {
            
            // проверяем, может уже есть респондент с таким участником
            //$existRespondent = $this->registry->getDbHelper('MembersHelper')->findRespondentByMemberID($context['project']['projectID'], $_POST['member_id']);
            
            //if (!$existRespondent) {          
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
          //}
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
          $session = $this->registry->getDbHelper('TestsHelper')->findSessionByID($sessionID);
        }
      
        // обрабатываем загруженный файл
        $csvImporter = new \Ecoplay\Import\Csv($_FILES["filepath"]["tmp_name"], $_SERVER["DOCUMENT_ROOT"]."/temp/csv/");
        $data = $csvImporter->parse();
      
        $sessionIndex = $csvImporter->getColumnIndex('Группа');
        if (!((!$sessionIndex || !$data[0][$sessionIndex]) && (!$sessionID || !$session))) {
          // определяем группу
          if ($sessionIndex && $data[0][$sessionIndex]) {
            // ищем группу
            $session = $this->registry->getDbHelper('TestsHelper')->findSessionByProjectIDAndName($context['project']['projectID'], $data[0][$sessionIndex]);
            if ($session) {
              $sessionID = $session['sessionID'];
            }
            else {
              /*$session = array(
                'name'  => $data[0][$sessionIndex],
                'active'  => 1,
                'status'  => 'active',
                'projectID'  => $context['project']['projectID'],
              );
              $sessionID = $this->registry->getDbHelper('TestsHelper')->addSession($session);
              $session['sessionID'] = $sessionID;*/
                $sessionID = 0;
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
      
          $rowIndex = 0;
          $skippedRows = array();
          foreach ($data as $row) {
              $rowIndex++;
      
            if ($sessionIndex && $row[$sessionIndex] && (!$sessionID || $row[$sessionIndex] != $session['name'])) {
              // ищем группу
              $session = $this->registry->getDbHelper('TestsHelper')->findSessionByProjectIDAndName($context['project']['projectID'], $row[$sessionIndex]);
              if ($session) {
                $sessionID = $session['sessionID'];
              }
              else {
                /*$session = array(
                  'name'  => $row[$sessionIndex],
                  'active'  => 1,
                  'status'  => 'active',
                  'projectID'  => $context['project']['projectID'],
                );
                $sessionID = $this->registry->getDbHelper('TestsHelper')->addSession($session);
                $session['sessionID'] = $sessionID;*/
                $sessionID = 0;
              }
            }
            
            if (!$sessionID) {
                $skipped++;
                $skippedRows[] = $rowIndex;
                continue;
            }
      
            // валидируем исходные данные
            if (!$validator->validate($row)) {
              $skipped++;
              $skippedRows[] = $rowIndex;
            }
            elseif (!$row[0] && !$row[1]) { // должно быть хотябы или имя, или фамилия
              $skipped++;
              $skippedRows[] = $rowIndex;
            }
            else {
              // ищем такого участника уже в базе
              $member = $this->registry->getDbHelper('MembersHelper')->findByEmailAndProjectId($row[2], $context['project']['projectID']);
      
              if (!$member) { // добавляем участника
                // определяем язык по структуре
                $staff = $this->registry->getDbHelper('MembersHelper')->findStaffByProjectIDAndEmail($context['project']['projectID'], $row[2]);
                $langID = ($languageIndex && $row[$languageIndex] && array_key_exists($row[$languageIndex], $languagesIDs)) ? $languagesIDs[$row[$languageIndex]] : $projectLangID;
      
                $memberID = $this->registry->getDbHelper('MembersHelper')->addMember($row[1], $row[0], $row[3], $row[2], $context['project']['projectID'],
                  $this->registry->getModel('Auth')->generateUid('prep_projects_members', 'private_lk_access_key', false), $langID, 1, 'new', $row[4]);
                
                $eventListener = new EventListener($this->registry, $context['project'], $_SERVER["DOCUMENT_ROOT"].'/');
                $eventListener->executeEvent('MemberAdded', array(
                  'memberID'  => $memberID,
                ), true);
                
                $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'member', $memberID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
              }
              else {
                $memberID = $member['projects_memberID'];
              }
      
              if ($this->registry->getDbHelper('MembersHelper')->findRespondentByProjectIDAndSessionIDAndMemberID($context['project']['projectID'], $sessionID, $memberID)) { // такой оцениваемый уже есть
                $skipped++;
                $skippedRows[] = $rowIndex;
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
      
          $this->component->arResult['statusSuccess'] = 'Импортировано: '.$inserted.', проигнорировано: '.$skipped.($skipped ? ' (строки '.implode(', ', $skippedRows).')' : '');
        }
      }
      
      $statuses = $this->registry->getDbHelper('MembersHelper')->getTestingRespondentsStatusesNames();
      $this->component->arResult['statuses'] = $statuses;
      
      $sorts = array(
        'respondentID'  => 'respondentID',
        'name'  => 'name',
        'surname'  => 'surname',
        'email'  => 'email',
        'position'  => 'position',
        'session'  => 'sessionID',
        'status'  => 'status',
        'private_lk_access_key'  => 'private_lk_access_key',
        'last_email'  => 'last_email',
        'last_action'  => 'last_entrance',
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
      
      $sessions = $this->registry->getDbHelper('TestsHelper')->getSessionsByProjectID($context['project']['projectID']);
      $userSessions = array();
      foreach ($sessions as $session) {
        if (in_array($session['sessionID'], $availableSessionsIDs)) {
          $userSessions[$session['sessionID']] = $session['name'];
        }
      }
      $this->component->arResult['userSessions'] = $userSessions;
      
      // настройки фильтров для данных
      $filters = array(
        0  => array('title' => 'Имя', 'name' => 'name', 'type' => 'text', 'data_type' => 'string', 'field' => 'm.`name`'),
        1  => array('title' => 'Фамилия', 'name' => 'surname', 'type' => 'text', 'data_type' => 'string', 'field' => 'm.`surname`'),
        2  => array('title' => 'ID', 'name' => 'id', 'type' => 'text', 'data_type' => 'number', 'field' => 'r.`respondentID`'),
        3  => array('title' => 'Текст', 'name' => 'text', 'type' => 'text', 'data_type' => 'string', 'field' => array('m.`search_text`')),
        4  => array('title' => 'Статус ', 'name' => 'status', 'type' => 'select', 'data_type' => 'string', 'field' => 'r.`status`', 'values' => (array(0 => 'Любой') + $statuses)),
        5  => array('title' => 'Группа ', 'name' => 'session_id', 'type' => 'select', 'data_type' => 'number', 'field' => 'r.`sessionID`', 'values' => (array(0 => 'Любая') + $userSessions)),
        6  => array('title' => 'В архиве ', 'name' => 'is_archive', 'type' => 'select', 'data_type' => 'number', 'field' => 'r.`is_archive`', 'values' => array(-1 => '-', 0 => 'Нет', 1 => 'Да'), 'explicit' => 1),
      );
      $filterData = array(
        'FILTERS'  => $filters,
        'BASE_URL'  => '/projects/'.$context['project']['projectID'].'/groups/respondents/',
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
          $selectedIDs = $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsIDsByProjectIDAndSessionsIDs($context['project']['projectID'], $availableSessionsIDs, $filterValue['filter_strings']);
        }
        else {
          $selectedIDs = $_POST['respondents_ids'];
        }
      
        if (isset($_POST['mode'])) {
          if ($_POST['mode'] == 'delete') {
            $deletedRespondents =  $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsByIDs($selectedIDs);
            
            $this->registry->getDbHelper('MembersHelper')->deleteRespondentsByIDs($selectedIDs);
            
            foreach ($deletedRespondents as $respondent) {
              $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondent['respondentID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_DELETE, $respondent);
            }
          }
          elseif ($_POST['mode'] == 'zip') {
            $editedRespondents =  $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsByIDs($selectedIDs);
          
             $this->registry->getDbHelper('MembersHelper')->editRespondents($selectedIDs, array(
                'is_archive'  => 1,
              ));
          
            foreach ($editedRespondents as $respondent) {
              $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondent['respondentID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $respondent);
            }
          }
          elseif ($_POST['mode'] == 'unzip') {
            $editedRespondents =  $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsByIDs($selectedIDs);
          
            $this->registry->getDbHelper('MembersHelper')->editRespondents($selectedIDs, array(
              'is_archive'  => 0,
            ));
          
            foreach ($editedRespondents as $respondent) {
              $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'respondent', $respondent['respondentID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $respondent);
            }
          }
          elseif ($_POST['mode'] == 'mail_resend')
          {
              $editedRespondents =  $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsByIDs($selectedIDs);
              $membersIds=array(0);
              foreach ($editedRespondents as $respondent) {
                  $membersIds[intval($respondent["project_memberID"])]=intval($respondent["project_memberID"]);
              }
              $selectedMemebers=$this->registry->getDbHelper('MembersHelper')->getMembersByIDs($membersIds);
              $languagesIDs = json_decode($context['project']['~languages']);
              foreach($selectedMemebers as $selectedMemeber)
              {
                  if($selectedMemeber["email"]!="")
                  {
                      $smtpMail = \Ecoplay\Model\SmtpMail::getInstance($this->registry->getDbConnect(), SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD);
                      $smtpMail->sendFromTemplate($context['project']['projectID'], 'MailEvent_Testing_MailResend', $languagesIDs[0], array(
                          "RESPONDENT" => $selectedMemeber["name"]." ".$selectedMemeber["surname"],
                          "RESPONDENT_LK_LINK" => 'http://'.SITE_HOST.'/enter/'.$selectedMemeber['private_lk_access_key']."/",
                          'EMAIL_TO'  => $selectedMemeber["email"],
                          'EMAIL_FROM'  => EMAIL_FROM,
                      ), 'member', $selectedMemeber['projects_memberID']);
                  }
              }
          }
          elseif ($_POST['mode'] == 'reports') {
              $valid = false;
              $params = array();
              
              if ($haveValuation) {
                  if ($_POST['valuation_group']) {
                      // валидируем выбранную нормогруппу
                      $valuationGroup = $this->registry->getDbHelper('TestsHelper')->findValuationGroupByID(intval($_POST['valuation_group']));
                      if ($valuationGroup) {
                          $params['valuationGroupID'] = $valuationGroup['vgroupID'];
                          $valid = true;
                      }
                  }
              }
              else {
                  $valid = true;
              }
              
              if ($valid) {
                  // Создаем задачу на генерацию отчетов              
                  $taskID = $this->registry->getDbHelper('TasksHelper')->addTask(array(
                      'name'  => '',
                      'type'  => \Ecoplay\Helper\Db\TasksHelper::TASK_TYPE_REPORT,
                      'params'  => json_encode(array_merge($params, array('projectID' => $context['project']['projectID'], 'respondentsIDs' => $selectedIDs))),
                      'userID'  => $context['USER']->GetID(),
                      'status'  => \Ecoplay\Helper\Db\TasksHelper::TASK_STATUS_NEW,
                      'dt_created'  => date('d-m-Y H:i:s'),
                  ));
                  
                  $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'task', $taskID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);              
              }
          }
          else {
            $newStatus = false;
            switch ($_POST['mode']) {
              case 'to_new':
                $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_NEW;
                break;
              case 'to_waiting':
                $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_WAITING;
                break;
              case 'to_active':
                $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE;
                break;
              case 'to_complete':
                $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_COMPLETE;
                break;
              /*case 'to_report':
                $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_REPORT;
                break;
              case 'to_archive':
                $newStatus = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ARCHIVE;
                break;*/
            }
            
            if ($newStatus) {
              $editedRespondents =  $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsByIDs($selectedIDs);
              
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
      $cnt = $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsCountByProjectIDAndSessionsIDs($context['project']['projectID'], $availableSessionsIDs, $filterValue['filter_strings']);
      $onPage = 100;
      $pagesCnt = ceil($cnt / $onPage);
      $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;    
      
      $respondents = $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsByProjectIDAndSessionsIDsPaged($context['project']['projectID'], $availableSessionsIDs, $page, $onPage, $sorts[$sort], $order, $filterValue['filter_strings']);
      $this->component->arResult['respondents'] = $respondents;
      $this->component->arResult['sort'] = $sort;
      $this->component->arResult['order'] = $order;
      
      $this->component->arResult['cntInfo'] = ($pagesCnt == 1) ? $cnt : 0;
      
      // инфа для редактирования
      $respondentsJson = array();
      $respondentsIDs = array();
      foreach ($respondents as $respondent) {
        $respondentsJson[$respondent['respondentID']] = array(
          'name'  => $respondent['name'],
          'surname' => $respondent['surname'],
          'email' => $respondent['email'],
          'position' => $respondent['position'],
          'department' => $respondent['department'],
          'langID' => $respondent['langID'],
          'sessionID' => $respondent['sessionID'],
        );
        $respondentsIDs[] = $respondent['respondentID']; 
      }
      
      $jsonRespondents = json_encode($respondentsJson);
      $jsonRespondents = str_replace("'", "\'", $jsonRespondents);
      $jsonRespondents = str_replace("\\", "\\\\", $jsonRespondents);
      
      $this->component->arResult['json_respondents'] = $jsonRespondents;
      
      // отчеты респондентов
      $reports = array();
      if (count($respondentsIDs)) { 
          $reportsSrc = $this->registry->getDbHelper('ReportsHelper')->getReportsByProjectIDAndRespondentsIDs($context['project']['projectID'], $respondentsIDs);      
          foreach ($reportsSrc as $report) {          
              if (!array_key_exists($report['stat1_respondentID'], $reports)) {              
                  $reports[$report['stat1_respondentID']] = array(
                      'reportID'    => $report['generated_reportID'],
                      'cnt' => 1,
                  );
              }
              else {
                  $reports[$report['stat1_respondentID']]['cnt']++;
                  if ($report['generated_reportID'] > $reports[$report['stat1_respondentID']]['reportID']) {
                      $reports[$report['stat1_respondentID']]['reportID'] = $report['generated_reportID'];
                  }
              }
          }
      }
      $this->component->arResult['reports'] = $reports;
      
      // инфа для построения прогресса
      $this->component->arResult['progress_settings'] = $this->registry->getModel('Tests')->getProjectProgressSettings($context['project']['projectID']);
            
      // статистика по статусам
      $get = $_GET;
      if (array_key_exists('status_fvalue', $get)) {
        unset($get['status_fvalue']);
      }
      $filterValue = DataFilter::getFilterValue($filters, $get, $this->registry->getDbConnect());
      $statusesStatSrc = $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsStatusesStat($context['project']['projectID'],
          $availableSessionsIDs, $filterValue['filter_strings']);    
      $statusesStat = array();
      $allStatusesCnt = 0;
      foreach ($statuses as $status => $name) {
        $statusesStat[$status] = 0;
      }
      foreach ($statusesStatSrc as $stat) {        
      //foreach ($respondents as $respondent) {
          $statusesStat[$stat['status']] += $stat['cnt'];
          $allStatusesCnt += $stat['cnt'];;
      }
      $this->component->arResult['statuses_stat'] = $statusesStat;
      $this->component->arResult['all_statuses_cnt'] = $allStatusesCnt;
      
      $this->component->IncludeComponentTemplate("testing_statuses_filter");
      
      $navResult = new \CDBResult();
      $navResult->NavPageCount = ceil($cnt / $onPage);
      $navResult->NavPageNomer = $page;
      $navResult->NavNum = 1;
      $navResult->NavPageSize = $onPage;
      $navResult->NavRecordCount = $cnt;
      $navResult->nPageWindow = 11;
      
      $context['APPLICATION']->IncludeComponent('ecoplay:system.pagenavigation', '', array(
        'NAV_RESULT' => $navResult,
        'NAV_URL'  => '/projects/'.$context['project']['projectID'].'/groups/respondents/',
        'NAV_QUERY_STRING' => 'sort='.$sort.'&order='.$order.($filterQueryString ? '&'.$filterQueryString : '').(isset($_GET['checkAll']) ? '&checkAll=1' : ''),
      ));
      
      $this->component->arResult['sessions'] = $sessions;
      
      $packagesIDs = array();
      foreach ($sessions as $session) {
        if (!in_array($session['packageID'], $packagesIDs)) {
          $packagesIDs[] = $session['packageID'];
        }
      }
      $this->component->arResult['packages'] = $this->registry->getDbHelper('TestsHelper')->getPackagesByIDs($packagesIDs);
      
      $languagesIDs = json_decode($context['project']['~languages']);
      $languagesSrc = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs($languagesIDs);
      $languages = array();
      foreach ($languagesSrc as $language) {
        $languages[$language['langID']] = $language['name'];
      }
      $this->component->arResult['languages'] = $languages;
    }
    else {      
      $this->component->arResult['respondents'] = null;
    }
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Тестируемые');
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
        'title'  => 'Тестируемые',
      ),
    )));
    
    $this->component->arResult['canReports'] = $this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'reports', $context['project']['projectID']);
    
    $this->component->IncludeComponentTemplate("template_testing");
  }
}