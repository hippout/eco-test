<?php 

namespace Ecoplay\Controller\Project\MemberInfo;

use Ecoplay\Controller\Base as Base;

class TypeTesting extends Base
{
  public function execute($context)
  {    
    $context['APPLICATION']->AddHeadScript('/js/forms.js');
    
    // проверка прав доступа к разделу
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'members', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
    
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
    
    // отчеты участника    
    /*$reports = $this->registry->getDbHelper('ReportsHelper')->getReportsByMemberID($member['projects_memberID']);
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
    }*/
    
    $sessions = $this->registry->getDbHelper('TestsHelper')->getSessionsByProjectID($context['project']['projectID']);
    $this->component->arResult['sessions'] = $sessions;
    
    $packagesIDs = array();
    foreach ($sessions as $session) {
      if (!in_array($session['packageID'], $packagesIDs)) {
        $packagesIDs[] = $session['packageID'];
      }
    }
    $this->component->arResult['packages'] = $this->registry->getDbHelper('TestsHelper')->getPackagesByIDs($packagesIDs);
    
    $this->component->arResult['progress_settings'] = $this->registry->getModel('Tests')->getProjectProgressSettings($context['project']['projectID']);
    
    $statuses = $this->registry->getDbHelper('MembersHelper')->getTestingRespondentsStatusesNames();
    $this->component->arResult['statuses'] = $statuses;
    
    $respondents = $this->registry->getDbHelper('TestsSeancesHelper')->getRespondentsByProjectIDAndMemberID($context['project']['projectID'], $member['projects_memberID']);
    $this->component->arResult['respondents'] = $respondents;
        
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
    
    $this->component->IncludeComponentTemplate('testing/template');
  }
}