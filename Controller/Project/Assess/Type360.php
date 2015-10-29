<?php 

namespace Ecoplay\Controller\Project\Assess;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Helper\DataFilter as DataFilter;
use Ecoplay\Helper\Db\LogsHelper;
use Ecoplay\Controller\EventListener;

class Type360 extends Base
{
  public function execute($context)
  {    
    $context['APPLICATION']->AddHeadScript('/js/forms.js');
    $context['APPLICATION']->AddHeadScript('/js/icheck/icheck.min.js');
    $context['APPLICATION']->SetAdditionalCSS('/js/icheck/skins/minimal/eco.css');
        
    $this->component->arResult['canSelectRespondents'] = $context['project']['allow_assess_to_select_respondents'];
    $this->component->arResult['canSelectAssess'] = $context['project']['allow_respondent_to_select_assess'];
    
    // проверка прав доступа к разделу    
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'members', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
    
    $this->component->arResult['canImport'] = $this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'imports', $context['project']['projectID']);
    
    $availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID']);
    $viewHelper = new \Ecoplay\View\Helper();
    
    if (count($availableSessionsIDs)) {
      $userSessions = $this->registry->getDbHelper('ProjectsHelper')->getUserAvailableSessionsByProjectID($context['project']['projectID'], $availableSessionsIDs);
      $this->component->arResult['jsessions'] = json_encode($userSessions);
      $this->component->arResult['sessions'] = $userSessions;
      
      $selectedIDs = array();
    
      $statuses = $this->registry->getDbHelper('MembersHelper')->getAssessStatusesNames();
    
      $sorts = array(
        'assessID'  => 'assessID',
        'name'  => 'name',
        'surname'  => 'surname',
        'email'  => 'email',
        'position'  => 'position',
        'private_lk_access_key'  => 'private_lk_access_key',
        'respondents'  => 'respondents',
        'report'  => 'report',
        'sessionID'  => 'sessionID',
        'status'  => 'status',
      );
      if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sorts)) {
        $sort = $_GET['sort'];
      }
      else {
        $sort = 'assessID';
      }
      $order = (isset($_GET['order']) && in_array($_GET['order'], array('asc', 'desc'))) ? $_GET['order'] : 'asc';
    
      $filterQueryString = DataFilter::getFilterQueryString($_GET);
      $this->component->arResult['filterQueryString'] = $filterQueryString;
    
      $reportsStates = array(
        \Ecoplay\Helper\Db\MembersHelper::ASSESS_REPORT_STATE_EMPTY => 'Нет данных',
        \Ecoplay\Helper\Db\MembersHelper::ASSESS_REPORT_STATE_NOT_READY => 'Отчёт строить нельзя',
        \Ecoplay\Helper\Db\MembersHelper::ASSESS_REPORT_STATE_MINIMAL => 'Не полностью заполнено, но можно строить отчёт',
        \Ecoplay\Helper\Db\MembersHelper::ASSESS_REPORT_STATE_FULL => 'Заполнено на 100%',
      );
      $this->component->arResult['reportsStates'] = $reportsStates;
      
      // настройки фильтров для данных
      $filters = array(
        0  => array('title' => 'Имя', 'name' => 'name', 'type' => 'text', 'data_type' => 'string', 'field' => 'pm.`name`'),
        1  => array('title' => 'Фамилия', 'name' => 'surname', 'type' => 'text', 'data_type' => 'string', 'field' => 'pm.`surname`'),
        2  => array('title' => 'ID', 'name' => 'id', 'type' => 'text', 'data_type' => 'number', 'field' => 'a.`assessID`'),
        3  => array('title' => 'Текст', 'name' => 'text', 'type' => 'text', 'data_type' => 'string', 'field' => array('pm.`search_text`')),
        4  => array('title' => 'Группа ', 'name' => 'session_id', 'type' => 'select', 'data_type' => 'number', 'field' => 'a.`sessionID`', 'values' => (array(0 => 'Любая') + $userSessions)),
        5  => array('title' => 'Респондентов', 'name' => 'respondents', 'type' => 'range', 'data_type' => 'number', 'field' => '`respondents`'),
        6  => array('title' => 'Статус ', 'name' => 'status', 'type' => 'select', 'data_type' => 'string', 'field' => 'a.`status`', 'values' => (array(0 => 'Любой') + $statuses)),
        7  => array('title' => 'Утверждено ', 'name' => 'direct_viewed', 'type' => 'select', 'data_type' => 'number', 'field' => 'a.`viewed_by_direct_head`', 'values' => array(-1 => '-', 0 => 'Нет', 1 => 'Да'), 'explicit' => 1),
        8  => array('title' => 'Отчеты ', 'name' => 'report_state', 'type' => 'select', 'data_type' => 'string', 'field' => 'a.`report_state`', 'values' => (array(0 => 'Не учитывать') + $reportsStates)),
      );
      $filterData = array(
        'FILTERS'  => $filters,
        'BASE_URL'  => '/projects/'.$context['project']['projectID'].'/groups/assess/',
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
    
      $cnt = $this->registry->getDbHelper('MembersHelper')->getAssessCountByProjectIDAndSessionsIds($context['project']['projectID'], $availableSessionsIDs, $filterValue['filter_strings']);
      $onPage = 100;
      $pagesCnt = ceil($cnt / $onPage);
      $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;
    
      // обрабатываем статусы опрашиваемых
      if (isset($_POST['assess_ids'])) {
    
        if (isset($_POST['all']) && $_POST['all']) {
          $selectedIDs = $this->registry->getDbHelper('MembersHelper')->getAssessIdsByProjectIDAndSessionsIds($context['project']['projectID'], $availableSessionsIDs, $filterValue['filter_strings']);
        }
        else {
          $selectedIDs = $_POST['assess_ids'];
        }
    
        $assesses = $this->registry->getDbHelper('MembersHelper')->getAssessByIDs($selectedIDs);
        $newStatus = '';
        $needLog = true;
        
        if (isset($_POST['mode']) && $_POST['mode'] == 'report') { // переводим в отчеты
          $assessData = array(
            'status'  => \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_COMPLETE,
          );
          if ($context['project']['auto_send_report_email']) {
            $assessData['last_report_email'] = '0000-00-00 00:00:00';
          }
          else {
            $assessData['last_report_email'] = date('d-m-Y H:i:s');
          }
          $this->registry->getDbHelper('MembersHelper')->editSeveralAssess($selectedIDs, $assessData);
    
          //$this->registry->getDbHelper('MembersHelper')->changeAssessesStatus($selectedIDs, Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_COMPLETE);
          $newStatus = \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_COMPLETE;
          $this->component->arResult['statusSuccess'] = 'Оцениваемые переведены в статус отчета';
        }
        elseif (isset($_POST['mode']) && $_POST['mode'] == 'activate') {
          // проверяем, чтобы у сессий оцениваемых был задан бланк!
          $sessions = $this->registry->getDbHelper('MembersHelper')->getAssessSessions($selectedIDs);
          $emptySession = false;
          foreach ($sessions as $session) {
            if (!$session['blankID']) {
              $emptySession = $session['name'];
              break;
            }
          }
    
          if (!$emptySession) {
            $this->registry->getDbHelper('MembersHelper')->changeAssessesStatus($selectedIDs, \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS);
            $this->component->arResult['statusSuccess'] = 'Оцениваемые переведены в статус опроса';
            $newStatus = \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS;
          }
          else {
            $this->component->arResult['statusError'] = 'Для группы "'.$emptySession.'" не задан бланк!';
          }
        }
        elseif (isset($_POST['mode']) && $_POST['mode'] == 'select_respondents' && $context['project']['allow_assess_to_select_respondents']) {
          $this->registry->getDbHelper('MembersHelper')->changeAssessesStatus($selectedIDs, \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_RESPONDENTS);
          $newStatus = \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_RESPONDENTS;
          $this->component->arResult['statusSuccess'] = 'Оцениваемые переведены в статус выбора респондента';
        }
        elseif (isset($_POST['mode']) && $_POST['mode'] == 'select_assess' && $context['project']['allow_respondent_to_select_assess']) {
          $this->registry->getDbHelper('MembersHelper')->changeAssessesStatus($selectedIDs, \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_ASSESS);
          $newStatus = \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_ASSESS;
          $this->component->arResult['statusSuccess'] = 'Оцениваемые переведены в статус выбора оцениваемых';
        }
        elseif (isset($_POST['mode']) && $_POST['mode'] == 'new') {
          $this->registry->getDbHelper('MembersHelper')->changeAssessesStatus($selectedIDs, \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_NEW);
          $newStatus = \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_NEW;
          $this->component->arResult['statusSuccess'] = 'Оцениваемые переведены в статус "Новый"';
        }
        elseif (isset($_POST['mode']) && $_POST['mode'] == 'email') { // рассылка напоминалок респондентам
    
          if ($context['project']['freeze_email']) {
            $this->component->arResult['statusError'] = 'Невозможно отправить напоминания, так как проект заблокирован на рассылку почты';
          }
          else {
            $emailedAssessIDs = array();
            $fillingEmailedAssessIDs = array();
    
            foreach ($assesses as $assess) {
              if ($assess['status'] == \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS) {
                $fillingEmailedAssessIDs[] = $assess['assessID'];
              }
              elseif ($assess['status'] == \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_RESPONDENTS) {
                if ($assess['last_select_respondents_email'] != '0000-00-00 00:00:00') {
                  $emailedAssessIDs[] = $assess['assessID'];
                }
              }
              elseif ($assess['status'] == \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_ASSESS) {
                if ($assess['last_select_assess_email'] != '0000-00-00 00:00:00') {
                  $emailedAssessIDs[] = $assess['assessID'];
                }
              }
            }
    
            if (count($emailedAssessIDs)) {
              $this->registry->getDbHelper('MembersHelper')->editSeveralAssess($emailedAssessIDs, array(
                'need_remind'  => 1,
              ));
              $this->registry->getModel('ActionsLogger')->logMultiSimple($context['USER']->GetID(), 'assess', $emailedAssessIDs, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_NOTIFY);
            }
    
            if (count($fillingEmailedAssessIDs)) {
              $emailedRespondents = $this->registry->getDbHelper('MembersHelper')->getRespondentsForAssessNotification($context['project']['projectID'], $fillingEmailedAssessIDs);
              if ($emailedRespondents) {
                $emailedRespondentsIDs = array();
                foreach ($emailedRespondents as $respondent) {
                  $emailedRespondentsIDs[] = $respondent['respondentID'];
                }
                $this->registry->getDbHelper('MembersHelper')->editRespondents($emailedRespondentsIDs, array(
                  'need_remind'  => 1,
                ));
                $this->registry->getModel('ActionsLogger')->logMultiSimple($context['USER']->GetID(), 'respondent', $emailedRespondentsIDs, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_NOTIFY);
              }
            }
    
            $this->component->arResult['statusSuccess'] = 'Оцениваемые поставлены в очередь для email - напоминания';
          }
        }
        elseif (isset($_POST['mode']) && $_POST['mode'] == 'report_email') { // выслать уведомления о получении отчетов
          $this->registry->getDbHelper('MembersHelper')->editSeveralAssess($selectedIDs, array(
            'last_report_email' => '0000-00-00 00:00:00',
          ));
          $this->component->arResult['statusSuccess'] = 'Оцениваемым будут высланы уведомления о получении отчета';
        }
        elseif (isset($_POST['mode']) && $_POST['mode'] == 'report_zip') { // задача на архив отчетов
    
          // Создаем задачу          
          $taskID = $this->registry->getDbHelper('TasksHelper')->addTask(array(
            'name'  => '',
            'type'  => \Ecoplay\Helper\Db\TasksHelper::TASK_TYPE_REPORT_ZIP,
            'params'  => json_encode(array('projectID' => $context['project']['projectID'], 'assessIDs' => $selectedIDs)),
            'userID'  => $context['USER']->GetID(),
            'status'  => \Ecoplay\Helper\Db\TasksHelper::TASK_STATUS_NEW,
            'dt_created'  => date('d-m-Y H:i:s'),
          ));
          
          $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'task', $taskID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
    
          $this->component->arResult['statusSuccess'] = 'Генерация архива отчетов поставлена в очередь. Он будет доступен <a href="/projects/'.$context['project']['projectID'].'/reportsArchives/">тут</a>';
        }
        
        $eventListener = new EventListener($this->registry, $context['project'], $_SERVER["DOCUMENT_ROOT"].'/');
        if (isset($this->component->arResult['statusSuccess']) && $this->component->arResult['statusSuccess']) {
          foreach ($assesses as $assess) {
            if ($newStatus) {
              $eventListener->executeEvent('Poll360_AssessStatusChanged', array(
                'assessID'  => $assess['assessID'],
                'status_old'  => $assess['status'],
                'status_new'  => $newStatus,
              ));        
            }

            if ($needLog) {
              $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'assess', $assess['assessID'], LogsHelper::ACTION_TYPE_CHANGE, $assess);
            }
          }
        }
      }
    
      $this->component->arResult['selectedIDs'] = $selectedIDs;
    
      $assess = $this->registry->getDbHelper('MembersHelper')->getAssessByProjectIDAndSessionsIdsPaged($context['project']['projectID'], $availableSessionsIDs, $page, $onPage, $sorts[$sort], $order, $filterValue['filter_strings']);
      $this->component->arResult['assess'] = $assess;
      $this->component->arResult['statuses'] = $statuses;
    
      // кол-во email нотификаций для оцениваемого
      $emailsCnt = array();
      $editsAbility = array();
      if (count($assess)) {
        $assessIDs = array();
        foreach ($assess as $oneAssess) {
          $assessIDs[] = $oneAssess['assessID'];
        }
    
        $emailsCnt = $this->registry->getDbHelper('MembersHelper')->getRespondentsForAssessNotificationCnt($context['project']['projectID'], $assessIDs);
    
        // чек, можно ли оцениваемому сменить группу
        $editsAbility = $this->registry->getDbHelper('MembersHelper')->getAssessEditsAbiliy($context['project']['projectID'], $assessIDs);
      }
      $this->component->arResult['emailsCnt'] = $emailsCnt;
      $this->component->arResult['editsAbility'] = $editsAbility;
    
      if (isset($_GET['checkAll'])) {
        $selectedIDs = array();
        foreach ($assess as $oneAssess) {
          $selectedIDs[] = $oneAssess['assessID'];
        }
        $this->component->arResult['selectedIDs'] = $selectedIDs;
      }    
    
      $this->component->arResult['sort'] = $sort;
      $this->component->arResult['order'] = $order;
    
      $this->component->arResult['cntInfo'] = ($pagesCnt == 1) ? $cnt : 0;
    
      $roles = $this->registry->getDbHelper('ProjectsHelper')->getProjectRoles($context['project']['projectID']);
      $this->component->arResult['roles'] = $roles;
    
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
        'NAV_URL'  => '/projects/'.$context['project']['projectID'].'/groups/assess/',
        'NAV_QUERY_STRING' => 'sort='.$sort.'&order='.$order.($filterQueryString ? '&'.$filterQueryString : '').(isset($_GET['checkAll']) ? '&checkAll=1' : ''),
      ));
    
      $sessions = $this->registry->getDbHelper('ProjectsHelper')->getSessionsByIDs(array_keys($userSessions));
      $sessionRoles = array();
      foreach ($sessions as $session) {
        if ($session['param_roles']) {
          $sessionParams = (array)json_decode($session['~param_roles']);
          foreach ($sessionParams as $roleID => $param) {
            if (!array_key_exists($roleID, $sessionRoles)) {
              $sessionRoles[$roleID] = $param;
            }
            else {
              if ($param->min < $sessionRoles[$roleID]->min) {
                $sessionRoles[$roleID]->min = $param->min;
              }
              if ($param->max > $sessionRoles[$roleID]->max) {
                $sessionRoles[$roleID]->max = $param->max;
              }
            }
          }
        }
      }
    
      $this->component->arResult['sessionsRoles'] = $sessionRoles;
    }
    else {
      $this->component->arResult['assess'] = null;
    }
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Оцениваемые');
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
        'link' => '/projects/'.$context['project']['projectID'].'/settings/groups/',
        'title' => 'Группы оцениваемых',
      ),
      4 => array(
        'title'  => 'Оцениваемые',
      ),
    )));
    
    $this->component->arResult['canReports'] = $this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'reports', $context['project']['projectID']);
    
    $this->component->IncludeComponentTemplate();
  }
}