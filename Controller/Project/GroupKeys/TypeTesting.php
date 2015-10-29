<?php 

namespace Ecoplay\Controller\Project\GroupKeys;
use Ecoplay\Controller\Base as Base;

class TypeTesting extends Base
{
  public function execute($context)
  {
    $context['APPLICATION']->AddHeadScript('/js/forms.js');
    $context['APPLICATION']->AddHeadScript('/js/icheck/icheck.min.js');
    $context['APPLICATION']->SetAdditionalCSS('/js/icheck/skins/minimal/eco.css');
  
    // проверка прав доступа к разделу
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'project_edit', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
  
    $sessionsSrc = $this->registry->getDbHelper('TestsHelper')->getSessionsByProjectID($context['project']['projectID']);
    $sessions = array();
    $sessionsNames = array();
    foreach ($sessionsSrc as $session) {
      $sessions[$session['sessionID']] = $session['name'];
      $sessionsNames[$session['name']] = $session['sessionID'];
    }
  
    if (isset($_POST['session_id']) && $_POST['mode'] == 'add') { // добавляем ключ
      $keyData = array(
        'projectID'  => $context['project']['projectID'],
        'sessionID'  => intval($_POST['session_id']),
        'active'  => 1,
        'status'  => 'active',
        'access_key'  => \Ecoplay\Helper\Db\BlanksHelper::uuid(),
        'name'  => $_POST['name'],
        'is_need_complete_previous' => intval($_POST['is_need_complete_previous']),
        'fact_count' => intval($_POST['fact_count']),
        'member_type' => $_POST['member_type'],
      );
  
      if ($_POST['member_type'] == \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_MEMBER_TYPE_FLEXIBLE) {
        $keyData['plan_count'] = intval($_POST['plan_count']);
        $keyData['is_registration_available'] = intval($_POST['is_registration_available']);
      }
      else {
        $keyData['plan_count'] = 0;
        $keyData['is_registration_available'] = 0;
      }
  
      $keyID = $this->registry->getDbHelper('TestsHelper')->addGroupKey($keyData);
      $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $keyID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
    }
    elseif (isset($_POST['session_id']) && $_POST['mode'] == 'update') {
      $key = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID(intval($_POST['key_id']));
      if ($key) {
        $keyData = array(
          'sessionID'  => intval($_POST['session_id']),
          'name'  => $_POST['name'],
          'is_need_complete_previous' => intval($_POST['is_need_complete_previous']),
          'fact_count' => intval($_POST['fact_count']),
          'member_type' => $_POST['member_type']
        );
  
        if ($_POST['member_type'] == \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_MEMBER_TYPE_FLEXIBLE) {
          $keyData['plan_count'] = intval($_POST['plan_count']);
          $keyData['is_registration_available'] = intval($_POST['is_registration_available']);
        }
        else {
          $keyData['plan_count'] = 0;
          $keyData['is_registration_available'] = 0;
        }
  
        $this->registry->getDbHelper('TestsHelper')->editGroupKey($key['group_keyID'], $keyData);
        $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $key['group_keyID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $key);
      }
    }
    elseif ($_POST['mode'] == 'delete') {
      foreach ($_POST['keys_ids'] as $keyID) {
        $key = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID(intval($keyID));
        if ($key) {
          $this->registry->getDbHelper('TestsHelper')->editGroupKey($key['group_keyID'], array(
            'status'  => 'deleted',
            'active'  => '0',
          ));
          $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $key['group_keyID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_DELETE, $key);
        }
      }
    }
    elseif ($_POST['mode'] == 'activate') {
      foreach ($_POST['keys_ids'] as $keyID) {
        $key = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID(intval($keyID));
        if ($key) {
          $this->registry->getDbHelper('TestsHelper')->editGroupKey($key['group_keyID'], array(
            'status'  => \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_STATUS_ACTIVE,
          ));
          $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $key['group_keyID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $key);
        }
      }
    }
    elseif ($_POST['mode'] == 'deactivate') {
      foreach ($_POST['keys_ids'] as $keyID) {
        $key = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID(intval($keyID));
        if ($key) {
          $this->registry->getDbHelper('TestsHelper')->editGroupKey($key['group_keyID'], array(
            'status'  => \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_STATUS_DISABLED,
          ));
          $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $key['group_keyID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $key);
        }
      }
    }
    elseif ($_POST['mode'] == 'delay') {
      foreach ($_POST['keys_ids'] as $keyID) {
        $key = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID(intval($keyID));
        if ($key) {
          $this->registry->getDbHelper('TestsHelper')->editGroupKey($key['group_keyID'], array(
            'status'  => \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_STATUS_ENDING,
          ));
          $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $key['group_keyID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $key);
        }
      }
    }
    elseif ($_POST['mode'] == 'export') {
      $context['APPLICATION']->RestartBuffer();
  
      $csvContent = '"Название";"Группа";"ID ключа";"С регистрацией или без";"Плановое кол-во";"Жесткое ограничение на кол-во";"Заставлять завершать предыдущее заполнение с того же ключа";"Ссылка"';
      $exportedKeys = $this->registry->getDbHelper('TestsHelper')->getGroupKeysByIDs($_POST['keys_ids']);
      foreach ($exportedKeys as $key) {
        $csvContent .= "\n".'"'.addslashes($key['~name']).'";"'.addslashes($sessions[$key['sessionID']]).'";"'.$key['group_keyID'].'";'.
          '"'.$key['is_registration_available'].'";"'.$key['plan_count'].'";"'.$key['fact_count'].'";"'.$key['is_need_complete_previous'].'";"http://'.SITE_HOST.'/g/'.$key['access_key'].'"';
      }
  
      header("Cache-Control: public");
      header("Content-Description: File Transfer");
      header("Content-Disposition: attachment; filename=export.csv");
      header("Content-type: text/csv");
  
      $csvContent = iconv('UTF-8', 'Windows-1251', $csvContent);
  
      header("Content-Length: ".mb_strlen($csvContent, 'Windows-1251'));
      echo($csvContent);
  
      die();
    }
  
    if ($_FILES["filepath"] && !$_FILES['filepath']['error']) {
      // обрабатываем загруженный файл
      $csvImporter = new \Ecoplay\Import\Csv($_FILES["filepath"]["tmp_name"], $_SERVER["DOCUMENT_ROOT"]."/temp/csv/");
      $data = $csvImporter->parse();
  
      $inserted = $skipped = 0;
      $validations = array(
        0  => 'anything',
        1  => 'anything',
      );
      $required = array(0, 1);
      $validator = new \Ecoplay\Form\Validator($validations, $required, array());
  
      $registrationAvailableIndex = $csvImporter->getColumnIndex('С регистрацией или без');
      $needCompletePreviousIndex = $csvImporter->getColumnIndex('Заставлять завершать предыдущее заполнение с того же ключа');
      $planCountIndex = $csvImporter->getColumnIndex('Плановое кол-во');
      $factCountIndex = $csvImporter->getColumnIndex('Жесткое ограничение на кол-во');
      $memberTypeIndex = $csvImporter->getColumnIndex('Список участников');
      $keyIDIndex = $csvImporter->getColumnIndex('ID ключа');
  
      $notExistSessions = array();
  
      foreach ($data as $row) {
        // валидируем исходные данные
        if (!$validator->validate($row)) {
          $skipped++;
        }
        else {
          if (!array_key_exists($row[1], $sessionsNames)) {
            if (!in_array($row[1], $notExistSessions)) {
              $notExistSessions[] = $row[1];
            }
            $skipped++;
          }
          else {
            $memberType = ($memberTypeIndex && $row[$memberTypeIndex] == 'Известен') ? \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_MEMBER_TYPE_FIXED_LIST :
            \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_MEMBER_TYPE_FLEXIBLE;
            $keyData = array(              
              'sessionID'  => $sessionsNames[$row[1]],              
              'name'  => $row[0],
              'is_need_complete_previous' => ($needCompletePreviousIndex && $row[$needCompletePreviousIndex] == 1) ? 1 : 0,
              'fact_count' => ($factCountIndex) ? intval($row[$factCountIndex]) : 0,
              'member_type' => $memberType,
            );
  
            if ($memberType == \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_MEMBER_TYPE_FLEXIBLE) {
              $keyData['plan_count'] = ($planCountIndex) ? intval($row[$planCountIndex]) : 0;
              $keyData['is_registration_available'] = ($registrationAvailableIndex && $row[$registrationAvailableIndex] == 0) ? 0 : 1;
            }
            else {
              $keyData['plan_count'] = 0;
              $keyData['is_registration_available'] = 0;
            }
  
            $updatedKeyID = 0;
            if ($keyIDIndex && $row[$keyIDIndex]) {
              $updatedKey = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID($row[$keyIDIndex]);
              if ($updatedKey && $updatedKey['projectID'] == $context['project']['projectID']) {
                $updatedKeyID = $updatedKey['group_keyID'];
              }
            }
            
            if ($updatedKeyID) {
              $keyID = $updatedKeyID;
              $this->registry->getDbHelper('TestsHelper')->editGroupKey($keyID, $keyData);
              $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $keyID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $updatedKey);
            }
            else {
              $keyData['access_key'] = \Ecoplay\Helper\Db\BlanksHelper::uuid();
              $keyData['active'] = 1;
              $keyData['status'] = 'active';
              $keyData['projectID'] = $context['project']['projectID'];
              $keyID = $this->registry->getDbHelper('TestsHelper')->addGroupKey($keyData);
              $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $keyID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
            }
            
            $inserted++;
          }
        }
  
        $this->component->arResult['statusSuccess'] = 'Импортировано: '.$inserted.', проигнорировано: '.$skipped;
        if (count($notExistSessions)) {
          $this->component->arResult['action_warning'] = 'Групповые ключи для групп '.implode(', ', $notExistSessions).' проигнорированы, так как указанные группы не существуют.';
        }
      }
    }
  
    // получаем список существующих групповых ключей
    $groupKeys = $this->registry->getDbHelper('TestsHelper')->getAllGroupKeysByProjectID($context['project']['projectID']);
  
    // количество респондентов для известных
    $fixedKeysIDs = array();
    foreach ($groupKeys as $key) {
      if ($key['member_type'] == \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_MEMBER_TYPE_FIXED_LIST) {
        $fixedKeysIDs[] = $key['group_keyID'];
      }
    }
    if (count($fixedKeysIDs)) {
      $this->component->arResult['fixedCnt'] = $this->registry->getDbHelper('TestsHelper')->getFixedKeysMembersCount($fixedKeysIDs);
    }
  
    $viewHelper = new \Ecoplay\View\Helper();
  
    /*foreach ($groupKeys as $ind => $key) {
     $groupKeys[$ind]['real_sessionID'] = $key['sessionID'];
     $groupKeys[$ind]['statusd'] = $key['status'];
     }
  
     $this->component->arResult['table_data'] = str_replace("'", "\'", $viewHelper->prepareJsonDataForTable($groupKeys,
     array('group_keyID', 'access_key', 'sessionID', 'status', 'statusd', 'name', 'cnt', 'is_registration_available', 'is_need_complete_previous', 'plan_count', 'fact_count', 'real_sessionID'),
     array(), array('sessionID' => $sessions)));
    $this->component->arResult['cnt'] = count($groupKeys);*/
    $this->component->arResult['sessions'] = $sessions;
  
    $this->component->arResult['memberTypes'] = \Ecoplay\Helper\Db\TestsHelper::getGroupKeyMemberTypes();
    $this->component->arResult['memberTypesDetailed'] = \Ecoplay\Helper\Db\TestsHelper::getGroupKeyMemberTypesDetailed();
    $this->component->arResult['statuses'] = \Ecoplay\Helper\Db\TestsHelper::getGroupKeyStatuses();
  
    $this->component->arResult['groupKeys'] = $groupKeys;
    // выводим таблицу с данными
    $this->component->IncludeComponentTemplate('linear/table');
  
  
    $this->component->arResult['projectID'] = $context['project']['projectID'];
  
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Групповые ключи');
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
        'title'  => 'Групповые ключи',
      ),
    )));
  
    $this->component->IncludeComponentTemplate('linear/template');
  }
  
  /*public function execute($context)
  {
    // проверка прав доступа к разделу
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'project_edit', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
        
    if (isset($_POST['session_id']) && $_POST['mode'] == 'add') { // добавляем ключ
      $keyID = $this->registry->getDbHelper('TestsHelper')->addGroupKey(array(
        'projectID'  => $context['project']['projectID'],
        'sessionID'  => intval($_POST['session_id']),
        'active'  => 1,
        'status'  => 'active',
        'access_key'  => \Ecoplay\Helper\Db\BlanksHelper::uuid(),
        'name'  => $_POST['name'],
        'is_registration_available' => intval($_POST['is_registration_available']),
        'is_need_complete_previous' => intval($_POST['is_need_complete_previous']),
        'plan_count' => intval($_POST['plan_count']),
        'fact_count' => intval($_POST['fact_count']),
      ));
      $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $keyID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);    
    }
    elseif (isset($_POST['session_id']) && $_POST['mode'] == 'update') {
      $key = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID(intval($_POST['key_id']));
      if ($key) { 
        $this->registry->getDbHelper('TestsHelper')->editGroupKey($key['group_keyID'], array(        
          'sessionID'  => intval($_POST['session_id']),
          'name'  => $_POST['name'],
          'is_registration_available' => intval($_POST['is_registration_available']),
          'is_need_complete_previous' => intval($_POST['is_need_complete_previous']),
          'plan_count' => intval($_POST['plan_count']),
          'fact_count' => intval($_POST['fact_count']),
        ));        
        $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $key['group_keyID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $key);
      }
    }
    elseif ($_POST['mode'] == 'delete') {
      foreach ($_POST['keys_ids'] as $keyID) {
        $key = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID(intval($keyID));
        if ($key) {
          $this->registry->getDbHelper('TestsHelper')->editGroupKey($key['group_keyID'], array(
            'status'  => 'deleted',
            'active'  => '0',
          ));
          $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $key['group_keyID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_DELETE, $key);
        }        
      }
    }
    elseif ($_POST['mode'] == 'activate') {
      foreach ($_POST['keys_ids'] as $keyID) {
        $key = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID(intval($keyID));
        if ($key) {
          $this->registry->getDbHelper('TestsHelper')->editGroupKey($key['group_keyID'], array(
            'status'  => 'active',
          ));
          $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $key['group_keyID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $key);
        }
      }
    }
    elseif ($_POST['mode'] == 'deactivate') {
      foreach ($_POST['keys_ids'] as $keyID) {
        $key = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID(intval($keyID));
        if ($key) {
          $this->registry->getDbHelper('TestsHelper')->editGroupKey($key['group_keyID'], array(
            'status'  => 'disabled',
          ));
          $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $key['group_keyID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $key);
        }
      }
    }
    
    $sessionsSrc = $this->registry->getDbHelper('TestsHelper')->getSessionsByProjectID($context['project']['projectID']);
    $sessions = array();
    $sessionsNames = array();
    foreach ($sessionsSrc as $session) {
      $sessions[$session['sessionID']] = $session['name'];
      $sessionsNames[$session['name']] = $session['sessionID'];
    }
    
    if ($_FILES["filepath"] && !$_FILES['filepath']['error']) {
      // обрабатываем загруженный файл
      $csvImporter = new \Ecoplay\Import\Csv($_FILES["filepath"]["tmp_name"], $_SERVER["DOCUMENT_ROOT"]."/temp/csv/");
      $data = $csvImporter->parse();
    
      $inserted = $skipped = 0;
      $validations = array(
        0  => 'anything',
        1  => 'anything',
      );
      $required = array(0, 1);
      $validator = new \Ecoplay\Form\Validator($validations, $required, array());
    
      $registrationAvailableIndex = $csvImporter->getColumnIndex('С регистрацией или без');
      $needCompletePreviousIndex = $csvImporter->getColumnIndex('Заставлять завершать предыдущее заполнение с того же ключа');
      $planCountIndex = $csvImporter->getColumnIndex('Плановое кол-во');
      $factCountIndex = $csvImporter->getColumnIndex('Жесткое ограничение на кол-во');
    
      $notExistSessions = array();
    
      foreach ($data as $row) {
        // валидируем исходные данные
        if (!$validator->validate($row)) {
          $skipped++;
        }
        else {
          if (!array_key_exists($row[1], $sessionsNames)) {
            if (!in_array($row[1], $notExistSessions)) {
              $notExistSessions[] = $row[1];
            }
            $skipped++;
          }
          else {
            $keyID = $this->registry->getDbHelper('TestsHelper')->addGroupKey(array(
              'projectID'  => $context['project']['projectID'],
              'sessionID'  => $sessionsNames[$row[1]],
              'active'  => 1,
              'status'  => 'active',
              'access_key'  => \Ecoplay\Helper\Db\BlanksHelper::uuid(),
              'name'  => $row[0],
              'is_registration_available' => ($registrationAvailableIndex && $row[$registrationAvailableIndex] == 0) ? 0 : 1,
              'is_need_complete_previous' => ($needCompletePreviousIndex && $row[$needCompletePreviousIndex] == 1) ? 1 : 0,
              'plan_count' => ($planCountIndex) ? intval($row[$planCountIndex]) : 0,
              'fact_count' => ($factCountIndex) ? intval($row[$factCountIndex]) : 0,
            ));
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key', $keyID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
            $inserted++;
          }
        }
    
        $this->component->arResult['statusSuccess'] = 'Импортировано: '.$inserted.', проигнорировано: '.$skipped;
        if (count($notExistSessions)) {
          $this->component->arResult['action_warning'] = 'Групповые ключи для групп '.implode(', ', $notExistSessions).' проигнорированы, так как указанные группы не существуют.';
        }
      }
    }
    
    // получаем список существующих групповых ключей
    $groupKeys = $this->registry->getDbHelper('TestsHelper')->getAllGroupKeysByProjectID($context['project']['projectID']);
    
    foreach ($groupKeys as $ind => $key) {
      $groupKeys[$ind]['real_sessionID'] = $key['sessionID'];
      $groupKeys[$ind]['statusd'] = $key['status'];
    }
    
    $viewHelper = new \Ecoplay\View\Helper();    
    $this->component->arResult['table_data'] = str_replace("'", "\'", $viewHelper->prepareJsonDataForTable($groupKeys,
      array('group_keyID', 'access_key', 'sessionID', 'status', 'statusd', 'name', 'cnt', 'is_registration_available', 'is_need_complete_previous', 'plan_count', 'fact_count', 'real_sessionID'),
      array(), array('sessionID' => $sessions)));
    $this->component->arResult['cnt'] = count($groupKeys);
    $this->component->arResult['sessions'] = $sessions;
        
    $this->component->arResult['projectID'] = $context['project']['projectID'];
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Групповые ключи');
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
        'title'  => 'Групповые ключи',
      ),
    )));
        
    $this->component->IncludeComponentTemplate('testing/template');
  }*/
}