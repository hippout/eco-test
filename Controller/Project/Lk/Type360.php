<?php 

namespace Ecoplay\Controller\Project\Lk;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Helper\Db\MembersHelper as MembersHelper;

class Type360 extends Base
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
    
    $roles = $this->registry->getDbHelper('ProjectsHelper')->getProjectRoles($context['project']['projectID']);
    // ищем роли проекта, чтобы самооценку выводить на первом месте
    $selfRoleID = 0;
    foreach ($roles as $role) {
      if ($role['fixed_role'] == 'self') {
        $selfRoleID = $role['roleID'];
        break;
      }
    }
     
    // получаем анкеты
    $seances = $this->registry->getDbHelper('BlanksHelper')->getMemberSeances($context['member']['projects_memberID'], $context['member']['projectID'], $selfRoleID);    
    
    $projectViewSettings = ($context['project']['param_lk']) ? (array)json_decode($context['project']['~param_lk']) : array();
    
    if (array_key_exists('group_roles', $projectViewSettings) && $projectViewSettings['group_roles']) {
        $groupedRoles = $this->registry->getModel('Members')->getGroupedRoles();
        $groupedSeances = array();
        foreach ($seances as $seance) {
            $groupedRoleIndex = $this->registry->getModel('Members')->getGroupedRoleIndex($seance['fixed_role']);
            if (!array_key_exists($groupedRoleIndex, $groupedSeances)) {
                $groupedSeances[$groupedRoleIndex] = $groupedRoles[$groupedRoleIndex];
                $groupedSeances[$groupedRoleIndex]['seances'] = array();
            }
            $groupedSeances[$groupedRoleIndex]['seances'][] = $seance;
        }
        ksort($groupedSeances);
        $this->component->arResult['grouped_seances'] = $groupedSeances;
    }
    else {
        $this->component->arResult['grouped_seances'] = false;        
    }
    
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
    
    $this->component->arResult['projectViewSettings'] = $projectViewSettings;
     
    $memberName = $translator->translateObject($context['member']['name'], 'member_name',
      $context['member']['projects_memberID'], $langID).' '.$translator->translateObject($context['member']['surname'], 'member_surname', $context['member']['projects_memberID'], $langID);    
    $this->component->arResult["memberName"] = $memberName;
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
      'TRANSLATOR'  => $translator,
      'MODE'  => 'lk',
      'NAME'  => $memberName,      
      'SETTINGS'  => array_merge($projectViewSettings, array('name' => $memberName)),
      'ADDON_CSS'  => $addonCss,
    ));
    
    // выбор языка
    $this->component->includeComponentTemplate('languages');
     
    $this->component->arResult['TEXT'] = $translator->translateObject($context['project']['~lk_text'], 'project_lk_text', $context['project']['projectID'], $langID);
    if ($this->component->arResult['TEXT']) {
      $this->component->includeComponentTemplate('text');
    }
    
    // удаляем если нужно респондента
    if ($context['project']['allow_assess_deletion'] && isset($_POST['del_respondent'])) {
      $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($_POST['del_respondent']);
      if ($respondent && $respondent['project_memberID'] == $context['member']['projects_memberID'] && $respondent['active']
        && $respondent['stat1_roleID'] != $selfRoleID && !$respondent['deny_deletion']) {
          $respondentSeance = $this->registry->getDbHelper('SeancesHelper')->findByRespondentId($respondent['respondentID']);
          if (!$respondentSeance || $respondentSeance['state'] == \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_NEW) {
            $this->registry->getDbHelper('MembersHelper')->deleteRespondent($respondent['respondentID']);
            LocalRedirect('/enter/'.$context['member']['private_lk_access_key']);
          }
      }
    }
     
    // проверяем, возможно можно управлять подчиненными
    $subordinatesLink = false;
    if ($context['project']['allow_subordinates_controlling']) {
      if ($this->registry->getDbHelper('MembersHelper')->isHaveSubordinates($context['member']['email'], $context['project']['projectID'])) {
        $subordinatesLink = true;
      }
    }    
    
    // проверяем, возможно нужно выбирать оцениваемых
    $assess = $this->registry->getDbHelper('MembersHelper')->findFirstAssessByMemberID($context['member']['projectID'], $context['member']['projects_memberID']);
    if ($assess &&
      ($assess['status'] == MembersHelper::ASSESS_STATUS_SELECT_RESPONDENTS
          || ($context['project']['allow_select_respondents_at_assess'] && $assess['status'] == MembersHelper::ASSESS_STATUS_ASSESS)
          || $assess['status'] == MembersHelper::ASSESS_STATUS_SELECT_ASSESS
          || ($context['project']['allow_select_assess_at_assess'] && $assess['status'] == MembersHelper::ASSESS_STATUS_ASSESS)
      )
    ) {
      $context['APPLICATION']->IncludeComponent("ecoplay:lk.members.selector", "", array(
        'ROLES'  => $roles,
        'TRANSLATOR'  => $translator,
        'PROJECT'  => $context['project'],
        'ASSESS'  => $assess,
        'MEMBER'  => $context['member'],
        'SUBORDINATES_LINK'  => $subordinatesLink,
      ));
    }
    else {
      $this->component->arResult['subordinatesLink'] = $subordinatesLink;
    }
     
    $this->component->arResult['report'] = ($assess && $assess['last_reportID']) ? $assess['last_reportID'] : 0;
     
    // валидируем существующие группы
    $groupsIDs = array();
    foreach ($seances as $seance) {
      if ($seance['group_keyID'] && !in_array($seance['group_keyID'], $groupsIDs) && $seance['state'] != \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE) {
        $groupsIDs[] = $seance['group_keyID'];
      }
    }
    if (count($groupsIDs)) {      
      $groupsValid = $this->registry->getModel('Seances')->validateGroupSeances($groupsIDs);
      if (!$groupsValid) {
        $seances = $this->registry->getDbHelper('BlanksHelper')->getMemberSeances($context['member']['projects_memberID'], $selfRoleID);
      }
    }
    
    // проверяем возможность группового заполнения
    $groups = array();
    $existGroups = array();
    if ($context['project']['group_filling_available']) {
    
      // первоначальная группировка - по бланкам, без учета количества
      $groupsSrc = array();
       
      foreach ($seances as $seance) {
        if ($seance['assess_status'] == \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS) {
          if (($seance['state'] == \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_NOT_EXIST || $seance['state'] == \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_NEW)
          && !$seance['group_keyID']) {
            if (!array_key_exists($seance['blankID'], $groupsSrc)) {
              $groupsSrc[$seance['blankID']] = array();
            }
            $groupsSrc[$seance['blankID']][] = $seance;
          }
          elseif ($seance['group_keyID']) {
            if (!array_key_exists($seance['group_keyID'], $existGroups)) {
              $existGroups[$seance['group_keyID']] = array(
  		          'key'  => '',
  		          'state'  => \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_NEW,
  		          'seances'  => array(),
              );
            }
            $existGroups[$seance['group_keyID']]['seances'][] = $seance;
          }
        }
      }
    
      // разбиваем на реальные группы
      foreach ($groupsSrc as $blankSeances) {
        if (count($blankSeances) > 1) {
          $amount = 0;
          $group = array();
          foreach ($blankSeances as $seance) {
            $group[] = $seance;
            $amount++;
            if ($amount == GROUP_FILLING_MAX_RESPONDENTS_COUNT) {
              $groups[] = $group;
              $amount = 0;
              $group = array();
            }
          }
          if ($amount > 1) {
            $groups[] = $group;
          }
        }
      }
    }
    $this->component->arResult['groups'] = $groups;
    
    if (count($existGroups)) {
      $groupSeances = $this->registry->getDbHelper('SeancesHelper')->getGroupSeancesByIDs(array_keys($existGroups));
      foreach ($groupSeances as $groupSeance) {
        $existGroups[$groupSeance['ID']]['key'] = $groupSeance['group_key'];
        $existGroups[$groupSeance['ID']]['state'] = $groupSeance['state'];
      }
    }
    $this->component->arResult['existGroups'] = $existGroups;
    
    // логируем посещение    
    $this->registry->getModel('Stat')->addHit(0, $context['member']['projects_memberID']);
    
    $this->component->IncludeComponentTemplate();
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
      'TRANSLATOR'  => $translator,
      'SETTINGS'  => $projectViewSettings,
    ));
    
  }
}