<?php 

namespace Ecoplay\Controller\Project\Lk;
use Ecoplay\Controller\Base as Base;

class TypeTesting extends Base
{
  public function execute($context)
  {
    //$context['APPLICATION']->SetPageProperty('TEMPLATE', 'testing');
    $_GET['TESTING_TEMPLATE'] = 1;
    
    $this->component->arResult['member'] = $context['member'];
    $context['APPLICATION']->setPageProperty('memberName', $context['member']['name'].' '.$context['member']['surname']);
    $context['APPLICATION']->setPageProperty('memberKey', $context['member']['private_lk_access_key']);
    $this->component->arResult['project'] = $context['project'];
    
    $availableLanguages = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs(json_decode($context['project']['~languages']));
    $this->component->arResult['languages'] = $availableLanguages;
    
    // обработка языка
    if (isset($_POST['language']) && array_key_exists(intval($_POST['language']), $availableLanguages)) {
      $langID = intval($_POST['language']);
      $this->registry->getDbHelper('MembersHelper')->editMember($context['member']['projects_memberID'], array(
          'langID'  => $langID,
      ));
    }
    else {
      $langID = $context['member']['langID'];
    }
    $this->component->arResult['langID'] = $langID;
    
    // Шапка ЛК
    $translator = new \Ecoplay\Model\Translation($this->registry->getDbConnect(), $availableLanguages[$langID]['abbr'], $_SERVER["DOCUMENT_ROOT"].'/lang/', $this->registry);
    $this->component->arResult['translator'] = $translator;
     
    $projectViewSettings = ($context['project']['param_lk']) ? (array)json_decode($context['project']['~param_lk']) : array();
    $this->component->arResult['projectViewSettings'] = $projectViewSettings;
     
    /*$context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
      'TRANSLATOR'  => $translator,
      'MODE'  => 'lk',
      'NAME'  => $translator->translateObject($context['member']['name'], 'member_name',
          $context['member']['projects_memberID'], $langID).' '.$translator->translateObject($context['member']['surname'], 'member_surname', $context['member']['projects_memberID'], $langID),
      'SETTINGS'  => $projectViewSettings,
      'ADDON_CSS'  => false,
    ));*/
    
    // выбор языка
    //$this->component->includeComponentTemplate('languages');
    
    // логируем посещение
    $this->registry->getModel('Stat')->addHit(0, $context['member']['projects_memberID']);
    
    // получаем респондентов участника, чтобы вывести назначенные ему тесты
    $respondents = $this->registry->getDbHelper('TestsSeancesHelper')->getMemberRespondents($context['project']['projectID'], $context['member']['projects_memberID']);
    $this->component->arResult['respondents'] = $respondents;
    
    $tests = array();
    if (count($respondents)) {
      $packegesIDs = array();
      foreach ($respondents as $respondent) {
        $packegesIDs[] = $respondent['packageID'];        
      }
      
      // получаем тесты заданных пакеджей
      $tests = $this->registry->getDbHelper('TestsHelper')->getTestsByPackages($packegesIDs);
    }
    $this->component->arResult['tests'] = $tests;
    
    $this->component->IncludeComponentTemplate('template_testing');
    
    /*$context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
      'TRANSLATOR'  => $translator,
      'SETTINGS'  => $projectViewSettings,
    ));*/
  }
}