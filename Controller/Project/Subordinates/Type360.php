<?php 

namespace Ecoplay\Controller\Project\Subordinates;
use Ecoplay\Controller\Base as Base;

class Type360 extends Base
{
  public function execute($context)
  { 
    $this->component->arResult['member'] = $context['member'];
    $this->component->arResult['project'] = $context['project'];
    
    if (!$context['project']['allow_subordinates_controlling'] || !$this->registry->getDbHelper('MembersHelper')->isHaveSubordinates($context['member']['email'], $context['project']['projectID'])) {
      LocalRedirect('/enter/'.$context['member']['private_lk_access_key'].'/');
    }
    
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
    $translator = new \Ecoplay\Model\Translation($this->registry->getDbConnect(), $availableLanguages[$langID]['abbr'], $_SERVER["DOCUMENT_ROOT"].'/lang/');
    $this->component->arResult['translator'] = $translator;
    
    $projectViewSettings = ($context['project']['param_lk']) ? (array)json_decode($context['project']['~param_lk']) : array();
    $this->component->arResult['projectViewSettings'] = $projectViewSettings;
    
    $assess = $this->registry->getDbHelper('MembersHelper')->findFirstAssessByMemberID($context['project']['projectID'], $context['member']['projects_memberID']);
    $addonCss = false;
    if ($assess) {
      $addonCss = $this->registry->getDbHelper('ProjectsHelper')->findCSSBySessionsIDs(array($assess['sessionID']));
    }
    
    if (!$addonCss && $context['project']['myCSS_path']) {
      $addonCss = $context['project']['myCSS_path'];
    }
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
      'TRANSLATOR'  => $translator,
      'MODE'  => 'subordinates',
      'MEMEBER_KEY'  => $context['member']['private_lk_access_key'],
      'NAME'  => $translator->translateObject($context['member']['name'], 'member_name', $context['member']['projects_memberID'], $langID).' '.$translator->translateObject($context['member']['surname'], 'member_surname', $context['member']['projects_memberID'], $langID),
      //'TEXT'  => $translator->translateObject($project['~lk_subordinates_text'], 'project_lk_subordinates_text', $project['projectID'], $langID),
      'SETTINGS'  => $projectViewSettings,
      'ADDON_CSS'  => $addonCss,
    ));
    
    // выбор языка
    $this->component->includeComponentTemplate('languages');
    $this->component->arResult['TEXT']  = $translator->translateObject($context['project']['~lk_subordinates_text'], 'project_lk_subordinates_text', $context['project']['projectID'], $langID);
    
    // ищем участников по структуре
    $subordinatesMembers = $this->registry->getDbHelper('MembersHelper')->getSubordinatesMembers($context['member']['email'], $context['project']['projectID']);
    $subordinatesAssess = $this->registry->getDbHelper('MembersHelper')->getAssessByMembersIDs($context['project']['projectID'], array_keys($subordinatesMembers));
    
    $this->component->arResult['assess'] = $subordinatesAssess;
    $this->component->arResult['members'] = $subordinatesMembers;
    
    $this->component->IncludeComponentTemplate();
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
      'TRANSLATOR'  => $translator,
      'SETTINGS'  => $projectViewSettings,
    ));
  }
}