<?php 

namespace Ecoplay\Controller\Project\GroupKeyEnter;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Controller\EventListener;

class TypeLinear extends Base
{
  public function execute($context)
  {
    $langID = $this->registry->getModel('Projects')->getDefaultLangID($context['project']);
    $lang = $this->registry->getDbHelper('TranslationHelper')->findLanguageByID($langID);
    
    $translator = new \Ecoplay\Model\Translation($this->registry->getDbConnect(), $lang['abbr'], $_SERVER["DOCUMENT_ROOT"].'/lang/', $this->registry);
    $this->component->arResult['translator'] = $translator;
    
    $projectViewSettings = ($context['project']['param_lk']) ? (array)json_decode($context['project']['~param_lk']) : array();
    $this->component->arResult['projectViewSettings'] = $projectViewSettings;
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
      'TRANSLATOR'  => $translator,
      'MODE'  => 'blank',
      'MEMEBER_KEY'  => false,
      'SETTINGS'  => $projectViewSettings,
      'ADDON_CSS' => false,
      'TEXT'  => '',
    ));
    
    // кол-во респондентов, зрарегистрированных по этому ключу
    $existCnt = $this->registry->getDbHelper('TestsHelper')->getGroupKeyRespondentsCount($context['project']['projectID'], $context['groupKey']['group_keyID']);
    if ($context['groupKey']['fact_count'] && $existCnt >= $context['groupKey']['fact_count']) {
      $this->component->IncludeComponentTemplate('linear/limit');
    }
    elseif ($context['groupKey']['status'] != \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_STATUS_ACTIVE) {
      $this->component->IncludeComponentTemplate('linear/disabled');
    }
    else {
      $added = false; // флаг добавления респондента
      
      // проверяем, возможно есть авторегистраци
      if (!$context['groupKey']['is_registration_available']) {
        /*
        $memberKey = $this->registry->getModel('Auth')->generateUid('prep_projects_members', 'private_lk_access_key');
        $name = 'Авторегистрация '.date('H:i d.m.Y').' по ключу #'.$context['groupKey']['group_keyID'].' '.$context['groupKey']['name'];
        $memberID = $this->registry->getDbHelper('MembersHelper')->addMember($name, '', '', '',
          $context['project']['projectID'], $memberKey, $langID, 1, 'new', '', $context['groupKey']['group_keyID'], 1);
      
        $eventListener = new EventListener($this->registry, $context['project'], $_SERVER["DOCUMENT_ROOT"].'/');
        $eventListener->executeEvent('MemberAdded', array(
          'memberID'  => $memberID,
        ), true);
      
        $respondentKey = $this->registry->getModel('Auth')->generateUid('prep_respondents', 'private_access_key');
        $respondentID = $this->registry->getDbHelper('MembersHelper')->addRespondent(array(
          'project_memberID'  => $memberID,
          'active'  => 1,
          'projectID'  => $context['project']['projectID'],
          'sessionID'  => $context['groupKey']['sessionID'],
          'stat1_assessID'  => 0,
          'status'  => \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE,
          'private_access_key'  => $respondentKey,
          'stat1_roleID'  => 0,
          'last_email'  => '0000-00-00 00:00:00',
          'langID'  => 0,
          'last_entrance'  => '0000-00-00 00:00:00',
          'deny_deletion'  => 0,
          'addedd_by_assess'  => 0,
          'need_remind'  => 0,
        ));
      
        $added = true;
      
        LocalRedirect('/enter/'.$respondentKey.'/');
        */
        // редиректим на общую ссылку
        LocalRedirect('/a/'.$context['groupKey']['access_key'].'/');
      }
      else {      
        if (isset($_POST['send'])) {
          $validations = array(
            'name'  => 'anything',
            'surname'  => 'anything',
            'email'  => 'email',
            'position'  => 'anything',
          );
          $required = array('name');
        
          $validator = new \Ecoplay\Form\Validator($validations, $required, array());
        
          if ($validator->validate($_POST)) { // добавляем участника и респондента
        
            $memberKey = $this->registry->getModel('Auth')->generateUid('prep_projects_members', 'private_lk_access_key');
            $memberID = $this->registry->getDbHelper('MembersHelper')->addMember(trim($_POST["name"]), trim($_POST["surname"]), trim($_POST["position"]), trim($_POST["email"]),
              $context['project']['projectID'], $memberKey, $langID, 1, 'new', '', $context['groupKey']['group_keyID']);
        
            $eventListener = new EventListener($this->registry, $context['project'], $_SERVER["DOCUMENT_ROOT"].'/');
            $eventListener->executeEvent('MemberAdded', array(
              'memberID'  => $memberID,
            ), true);
        
            $respondentKey = $this->registry->getModel('Auth')->generateUid('prep_respondents', 'private_access_key');
            $respondentID = $this->registry->getDbHelper('MembersHelper')->addRespondent(array(
              'project_memberID'  => $memberID,
              'active'  => 1,
              'projectID'  => $context['project']['projectID'],
              'sessionID'  => $context['groupKey']['sessionID'],
              'stat1_assessID'  => 0,
              'status'  => \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE,
              'private_access_key'  => $respondentKey,
              'stat1_roleID'  => 0,
              'last_email'  => '0000-00-00 00:00:00',
              'langID'  => 0,
              'last_entrance'  => '0000-00-00 00:00:00',
              'deny_deletion'  => 0,
              'addedd_by_assess'  => 0,
              'need_remind'  => 0,
            ));
        
            $added = true;
            //$this->component->arResult['access_key'] = $memberKey;
            //$this->component->IncludeComponentTemplate('testing/success');
        
            LocalRedirect('/enter/'.$respondentKey.'/');
          }
        }
      }
      
      if (!$added) {
        $this->component->IncludeComponentTemplate('testing/template');
      }
    }
    
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
      'TRANSLATOR'  => $translator,
      'SETTINGS'  => $projectViewSettings,
    ));
  }
}