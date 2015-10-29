<?php 

namespace Ecoplay\Controller\Project\MemberInfo;

use Ecoplay\Controller\Base as Base;

class Type360 extends Base
{
  public function execute($context)
  {    
    $context['APPLICATION']->AddHeadScript('/js/forms.js');
    
    // проверка прав доступа к разделу
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'members', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
    
    $this->component->arResult['project'] = $context['project'];
    
    $availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID']);
        
    $member =  $this->registry->getDbHelper('MembersHelper')->findById(intval($_GET['MEMBER_ID']));
    if (!$member) {
      LocalRedirect('/projects/'.$context['project']['projectID'].'/settings/members/');
    }
    $this->component->arResult['member'] = $member;
        
    $languagesIDs = json_decode($context['project']['~languages']);
    $languagesSrc = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs($languagesIDs);
    $languages = array();
    foreach ($languagesSrc as $language) {
      $languages[$language['langID']] = $language['name'];
    }
    $this->component->arResult['languages'] = $languages;
    $this->component->arResult['languagesFull'] = $languagesSrc;
    
    // переводы участника
    $translationsSrc = $this->registry->getDbHelper('TranslationHelper')->getObjectAllTranslations(array('member_name', 'member_surname'), $member['projects_memberID']);
    $translations = array();
    foreach ($translationsSrc as $translation) {
      if (!array_key_exists($translation['langID'], $translations)) {
        $translations[$translation['langID']] = array();
      }
      $translations[$translation['langID']][$translation['objectType']] = $translation['value'];
    }
    $this->component->arResult['translations'] = $translations;
    
    // информация об оцениваемом участника
    $memberAssess = $this->registry->getDbHelper('MembersHelper')->findAssessByProjectIDAndMemberID($context['project']['projectID'], $member['projects_memberID']);
    $this->component->arResult['memberAssess'] = $memberAssess;
    if ($memberAssess) {
      $this->component->arResult['assessStatuses'] = $this->registry->getDbHelper('MembersHelper')->getAssessStatusesNames();
    
      $lastSend = null;
      $lastSendTime = 0;
      if ($memberAssess['status'] == \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_RESPONDENTS) {
        $lastSend = ($memberAssess['last_select_respondents_email'] == '0000-00-00 00:00:00') ? false : date('d.m.Y H:i:s', strtotime($memberAssess['last_select_respondents_email']));
        $lastSendTime = ($memberAssess['last_select_respondents_email'] == '0000-00-00 00:00:00') ? 0 : strtotime($memberAssess['last_select_respondents_email']);
      }
      elseif ($memberAssess['status'] == \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_ASSESS) {
        $lastSend = ($memberAssess['last_select_assess_email'] == '0000-00-00 00:00:00') ? false : date('d.m.Y H:i:s', strtotime($memberAssess['last_select_assess_email']));
        $lastSendTime = ($memberAssess['last_select_assess_email'] == '0000-00-00 00:00:00') ? 0 : strtotime($memberAssess['last_select_assess_email']);
      }
      $this->component->arResult['lastSend'] = $lastSend;
    
      $nextSend = false;
      if (!$context['project']['freeze_email'] && $lastSend) {
        if ($lastSendTime) {
          $nextSend = date('d.m.Y H:i:s', $lastSendTime + $context['project']['remind_after']*24*60*60);
        }
        else {
          $mod = time() % 60;
          $nextSend = date('d.m.Y H:i:s', time() + 60 - $mod);
        }
      }
      $this->component->arResult['nextSend'] = $nextSend;
    }
    
    // отчеты участника    
    $reports = $this->registry->getDbHelper('ReportsHelper')->getReportsByMemberID($member['projects_memberID']);
    $this->component->arResult['reports'] = $reports;
    if (count($reports)) {
      $sessionsIDs = array();
      $generatorsIDs = array();
      foreach ($reports as $report) {
        if (!in_array($report['sessionID'], $sessionsIDs)) {
          $sessionsIDs[] = $report['sessionID'];
        }
        if (!in_array($report['report_generatorID'], $generatorsIDs)) {
          $generatorsIDs[] = $report['report_generatorID'];
        }
      }
      $this->component->arResult['sessions'] = $this->registry->getDbHelper('ProjectsHelper')->getSessionsByIDsKeyed($sessionsIDs);
      $this->component->arResult['generators'] = $this->registry->getDbHelper('ReportsHelper')->getGeneratorsCollectionByIds($generatorsIDs, true);
    }
    
    // оценивающие участника
    $respondents = $this->registry->getDbHelper('MembersHelper')->getRespondentsInfoByAssessMemberID($member['projects_memberID'], $context['project']['projectID'], $availableSessionsIDs);
    $this->component->arResult['respondents'] = $respondents;
    
    // оцениваемые участника
    $assess = $this->registry->getDbHelper('MembersHelper')->getAssessInfoByRespondentMemberID($member['projects_memberID'], $context['project']['projectID'], $availableSessionsIDs);
    $this->component->arResult['assess'] = $assess;
    
    $viewHelper = new \Ecoplay\View\Helper();
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Участник '.$member['name'].' '.$member['surname']);
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
        'link' => '/projects/'.$context['project']['projectID'].'/settings/members/',
        'title' => 'Все участники',
      ),
      4  => array(
        'title' => $member['name'].' '.$member['surname'],
      ),
    )));
    
    $this->component->IncludeComponentTemplate();
  }
}