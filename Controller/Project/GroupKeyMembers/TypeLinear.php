<?php 

namespace Ecoplay\Controller\Project\GroupKeyMembers;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Controller\EventListener;

class TypeLinear extends Base
{
  public function execute($context)
  {
    $context['APPLICATION']->AddHeadScript('/js/forms.js');
    $context['APPLICATION']->AddHeadScript('/js/icheck/icheck.min.js');
    $context['APPLICATION']->SetAdditionalCSS('/js/icheck/skins/minimal/eco.css');
    
    if (isset($_POST['type'])) {
      if ($_POST['type'] == 'edit') {
        // находим участника
        $member = $this->registry->getDbHelper('MembersHelper')->findById($_POST['memberID']);
        if ($member['email'] != $_POST['email'] && $_POST['email']) {
          $sameEmailMember = $this->registry->getDbHelper('MembersHelper')->findByEmailAndProjectId($_POST['email'], $context['project']['projectID']);
          $email = $sameEmailMember ? $member['email'] : $_POST['email'];
        }
        else {
          $email = $member['email'];
        }
        
        $this->registry->getDbHelper('MembersHelper')->updateMember($member['projects_memberID'], $_POST['name'], $_POST['surname'], $_POST['position'],
          $email, $_POST['language'], $_POST['department']);
        $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'member', $member['projects_memberID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $member);
      }      
      elseif ($_POST['type'] == 'exist') {
        if (isset($_POST['member_id'])) {
          
          // проверяем, может этот участник уже привязан к ключу
          $linkExist = $this->registry->getDbHelper('LinearProjectsHelper')->isMemberLinkedToGroupKey($context['project']['projectID'], $context['groupKey']['group_keyID'], intval($_POST['member_id']));
          if (!$linkExist) {
            // добавляем привязку
            $itemID = $this->registry->getDbHelper('LinearProjectsHelper')->addGroupKeyMember(array(
              'projectID' => $context['project']['projectID'],
              'group_keyID' => $context['groupKey']['group_keyID'],
              'memberID'  => intval($_POST['member_id']),
            ));
            
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key_member', $itemID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
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
      
            // добавляем привязку
            $itemID = $this->registry->getDbHelper('LinearProjectsHelper')->addGroupKeyMember(array(
              'projectID' => $context['project']['projectID'],
              'group_keyID' => $context['groupKey']['group_keyID'],
              'memberID'  => $memberID,
            ));
            
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key_member', $itemID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
          }
          else {
            $this->component->arResult['statusError'] = 'Участник с таким email ('.$_POST['email'].') уже существует';
          }
        }
      }
    }
    
    $selectedIDs = array();
    
    if (isset($_POST['items_ids'])) {
      if (isset($_POST['all']) && $_POST['all']) {
        $selectedIDs = $this->registry->getDbHelper('LinearProjectsHelper')->getGroupKeyMembersIDsByProjectIDAndKeyID($context['project']['projectID'], $context['groupKey']['group_keyID']);        
      }
      else {
        $selectedIDs = $_POST['items_ids'];
      }
      
      if (isset($_POST['mode'])) {
        if ($_POST['mode'] == 'delete') {
          $deletedItems =  $this->registry->getDbHelper('LinearProjectsHelper')->getGroupKeyMembersByIDs($selectedIDs);      
          $this->registry->getDbHelper('LinearProjectsHelper')->deleteGroupKeyMembersByIDs($selectedIDs);
      
          foreach ($deletedItems as $item) {
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key_member', $item['itemID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_DELETE, $item);
          }
        }
        elseif ($_POST['mode'] == 'email') {
          $emailedItems =  $this->registry->getDbHelper('LinearProjectsHelper')->getGroupKeyMembersByIDs($selectedIDs);
          $this->registry->getDbHelper('LinearProjectsHelper')->editGroupKeyMembers($selectedIDs, array(
            'send_email'  => 1,
          ));
          
          foreach ($emailedItems as $item) {
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key_member', $item['itemID'], \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $item);
          }
        }
      }
    }
    
    $arResult['selectedIDs'] = $selectedIDs;
    
    if ($_FILES["filepath"] && !$_FILES['filepath']['error']) {      
    
      // обрабатываем загруженный файл
      $csvImporter = new \Ecoplay\Import\Csv($_FILES["filepath"]["tmp_name"], $_SERVER["DOCUMENT_ROOT"]."/temp/csv/");
      $data = $csvImporter->parse();
      
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
            ), true);
  
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'member', $memberID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
          }
          else {
            $memberID = $member['projects_memberID'];
          }          
    
          if ($this->registry->getDbHelper('LinearProjectsHelper')->isMemberLinkedToGroupKey($context['project']['projectID'], $context['groupKey']['group_keyID'], $memberID)) { // такой оцениваемый уже есть
            $skipped++;
          }
          else {
            // добавляем привязку
            $itemID = $this->registry->getDbHelper('LinearProjectsHelper')->addGroupKeyMember(array(
              'projectID' => $context['project']['projectID'],
              'group_keyID' => $context['groupKey']['group_keyID'],
              'memberID'  => intval($memberID),
            ));
            
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'group_key_member', $itemID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);            
            $inserted++;
          }
        }    
      }
    
      $this->component->arResult['statusSuccess'] = 'Импортировано: '.$inserted.', проигнорировано: '.$skipped;     
    }
    
    $cnt = $this->registry->getDbHelper('TestsHelper')->getGroupKeyLinkedMembersCount($context['project']['projectID'], $context['groupKey']['group_keyID']);
    $onPage = 100;
    $pagesCnt = ceil($cnt / $onPage);
    $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;
    
    $this->component->arResult['cntInfo'] = ($pagesCnt == 1) ? $cnt : 0;
    
    $members = $this->registry->getDbHelper('TestsHelper')->getGroupKeyLinkedMembers($context['project']['projectID'], $context['groupKey']['group_keyID'], $page, $onPage);
    $this->component->arResult['members'] = $members;
    $this->component->arResult['groupKey'] = $context['groupKey'];    
    
    $languagesIDs = json_decode($context['project']['~languages']);
    $languagesSrc = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs($languagesIDs);
    $languages = array();
    foreach ($languagesSrc as $language) {
      $languages[$language['langID']] = $language['name'];
    }
    $this->component->arResult['languages'] = $languages;
    
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
      'NAV_URL'  => '/projects/'.$context['project']['projectID'].'/settings/groupkeys/'.$context['groupKey']['group_keyID'].'/members/',
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