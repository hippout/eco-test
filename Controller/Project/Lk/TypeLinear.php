<?php 

namespace Ecoplay\Controller\Project\Lk;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Helper\Db\MembersHelper as MembersHelper;

class TypeLinear extends Base
{
  public function execute($context)
  {
    $this->component->arResult['member'] = $context['member'];
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
     
    // получаем анкеты
    $seances = $this->registry->getDbHelper('LinearProjectsHelper')->getMemberSeances($context['member']['projects_memberID'], $context['member']['projectID']);
    $this->component->arResult['seances'] = $seances;
    
    // определяем доп. CSS для первой настроенной сессии
    $addonCss = false;
    if ($seances && count($seances)) {
      $sessionsIDs = array();
      foreach ($seances as $seance) {
        if (!in_array($seance['sessionID'], $sessionsIDs)) {
          $sessionsIDs[] = $seance['sessionID'];
        }
      }      
      $addonCss = $this->registry->getDbHelper('ProjectsHelper')->findCSSBySessionsIDs($sessionsIDs);
    }
    
    if (!$addonCss && $context['project']['myCSS_path']) {
      $addonCss = $context['project']['myCSS_path'];
    }
     
    // Шапка ЛК
    $translator = new \Ecoplay\Model\Translation($this->registry->getDbConnect(), $availableLanguages[$langID]['abbr'], $_SERVER["DOCUMENT_ROOT"].'/lang/', $this->registry);
    $this->component->arResult['translator'] = $translator;
     
    $projectViewSettings = ($context['project']['param_lk']) ? (array)json_decode($context['project']['~param_lk']) : array();
    $this->component->arResult['projectViewSettings'] = $projectViewSettings;
     
    $memberName = $translator->translateObject($context['member']['name'], 'member_name',
      $context['member']['projects_memberID'], $langID).' '.$translator->translateObject($context['member']['surname'], 'member_surname', $context['member']['projects_memberID'], $langID);
    /*if ($context['member']['stat2_group_keyID']) {
      $groupKey = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID($context['member']['stat2_group_keyID']);
      if (!$groupKey['is_registration_available']) {
        $memberName = 'Опрос';
      }
    }*/
    $this->component->arResult["memberName"] = $memberName;
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
      'TRANSLATOR'  => $translator,
      'MODE'  => 'lk',
      'NAME'  => $memberName,      
      'SETTINGS'  => array_merge($projectViewSettings, array('name' => $memberName)),
      'ADDON_CSS'  => $addonCss,
    ));
    
    $lkNotAvailable = false;
    if ($context['member']['stat2_group_keyID']) {
      $groupKey = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID($context['member']['stat2_group_keyID']);
      if (!$groupKey['is_registration_available']) {
        $lkNotAvailable = true;
        $this->component->arResult["groupKey"] = $groupKey;
      }
    }
    
    if ($lkNotAvailable) {
      $this->component->IncludeComponentTemplate('linear/group_key', '/bitrix/components/ecoplay/member.lk/templates/.default');
    }
    else {    
      // выбор языка
      $this->component->includeComponentTemplate('languages', '/bitrix/components/ecoplay/member.lk/templates/.default');
       
      $this->component->arResult['TEXT'] = $translator->translateObject($context['project']['~lk_text'], 'project_lk_text', $context['project']['projectID'], $langID);
      if ($this->component->arResult['TEXT']) {
        $this->component->includeComponentTemplate('text', '/bitrix/components/ecoplay/member.lk/templates/.default');
      }
      
      // логируем посещение    
      $this->registry->getModel('Stat')->addHit(0, $context['member']['projects_memberID']);
      
      $this->component->IncludeComponentTemplate('template_linear', '/bitrix/components/ecoplay/member.lk/templates/.default');
    }
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
      'TRANSLATOR'  => $translator,
      'SETTINGS'  => $projectViewSettings,
    ));
    
  }
}