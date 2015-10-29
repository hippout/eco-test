<?php 

namespace Ecoplay\Controller\Project\Subordinate;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Controller\EventListener;

class Type360 extends Base
{
  public function execute($context)
  { 
    if (!$context['project']['allow_subordinates_controlling'] || !$this->registry->getDbHelper('MembersHelper')->isHaveSubordinates($context['member']['email'], $context['project']['projectID'])) {
      LocalRedirect('/enter/'.$context['member']['private_lk_access_key'].'/');
    }
    
    $assess = $this->registry->getDbHelper('MembersHelper')->findAssessById($_GET['ASSESS_ID']);
    if (!$assess || !$assess['active'] || $assess['projectID'] != $context['project']['projectID']) {
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
    
    $memberAssess = $this->registry->getDbHelper('MembersHelper')->findFirstAssessByMemberID($context['project']['projectID'], $context['member']['projects_memberID']);
    $addonCss = false;
    if ($memberAssess) {
      $addonCss = $this->registry->getDbHelper('ProjectsHelper')->findCSSBySessionsIDs(array($memberAssess['sessionID']));
    }
    
    if (!$addonCss && $context['project']['myCSS_path']) {
      $addonCss = $context['project']['myCSS_path'];
    }
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
      'TRANSLATOR'  => $translator,
      'MODE'  => 'subordinate',
      'MEMEBER_KEY'  => $context['member']['private_lk_access_key'],
      'NAME'  => $translator->translateObject($context['member']['name'], 'member_name', $context['member']['projects_memberID'], $langID).' '.$translator->translateObject($context['member']['surname'], 'member_surname', $context['member']['projects_memberID'], $langID),
      //'TEXT'  => $translator->translateObject($project['~lk_subordinate_text'], 'project_lk_subordinate_text', $project['projectID'], $langID),
      'SETTINGS'  => $projectViewSettings,
      'ADDON_CSS'  => $addonCss,
    ));
    
    // ищем участников по структуре
    $subordinatesMembers = $this->registry->getDbHelper('MembersHelper')->getSubordinatesMembers($context['member']['email'], $context['project']['projectID']);
    
    if (!array_key_exists($assess['project_memberID'], $subordinatesMembers)) {
      LocalRedirect('/enter/'.$context['member']['private_lk_access_key'].'/');
    }
    
    $this->component->arResult['assess'] = $assess;
    $this->component->arResult['assessMember'] = $subordinatesMembers[$assess['project_memberID']];
    
    // выбор языка
    $this->component->includeComponentTemplate('languages');
    $this->component->arResult['TEXT']  = $translator->translateObject($context['project']['~lk_subordinate_text'], 'project_lk_subordinate_text', $context['project']['projectID'], $langID);
    
    // определяем роли, по которым можем управлять
    $rolesIDs = array();
    $rolesSrc = $this->registry->getDbHelper('ProjectsHelper')->getProjectRoles($context['project']['projectID']);
    $roles = array();
    $deniedRoles = array('self', 'direct_head', 'functional_head');
    foreach ($rolesSrc as $role) {
      if (!in_array($role['fixed_role'], $deniedRoles)) {
        $rolesIDs[] = $role['roleID'];
      }
      $roles[$role['roleID']] = $role['name'];
    }
    $this->component->arResult['roles'] = $roles;
    $this->component->arResult['controlledRoles'] = $rolesIDs;
    
    
    // проверяем, можно ли управлять респондентами
    $canControllRespondents = ($assess['status'] == \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_RESPONDENTS_SELECTED) ? true : false;
    $this->component->arResult['canControllRespondents'] = $canControllRespondents;
    
    if ($canControllRespondents) {
      // удаляем
      if (isset($_POST['del_id'])) {
        $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($_POST['del_id']);
        if ($respondent && $respondent['active'] && $respondent['stat1_assessID'] == $assess['assessID'] && in_array($respondent['stat1_roleID'], $rolesIDs)) {
          $this->registry->getDbHelper('MembersHelper')->deleteRespondent($_POST['del_id']);
        }
      }
    
      // добавляем
      if (isset($_POST['respondent_id'])) {        
        $projectLangID = $this->registry->getModel('Projects')->getDefaultLangID($context['project']);
        $respondentStaff = $this->registry->getDbHelper('MembersHelper')->findStaffByID($_POST['respondent_id']);
        if ($respondentStaff && $respondentStaff['active'] && $respondentStaff['projectID'] == $context['project']['projectID']
          && isset($_POST['role']) && in_array($_POST['role'], $rolesIDs)) {
            // ищем соответствующего участника
            $respondentMember = $this->registry->getDbHelper('MembersHelper')->findByEmailAndProjectId($respondentStaff['email'], $context['project']['projectID']);
            if (!$respondentMember) {
              $respondentMember = array(
                'active' => 1,
                'projectID' => $context['project']['projectID'],
                'email'  => $respondentStaff['email'],
              );
              $name = $respondentStaff['name'].($respondentStaff['name2'] ? ' '.$respondentStaff['name2'] : '');
              $respondentMember['projects_memberID'] = $this->registry->getDbHelper('MembersHelper')->addMember($name, $respondentStaff['surname'], $respondentStaff['position'],
                $respondentStaff['email'], $context['project']['projectID'], $this->registry->getModel('Auth')->generateUid('prep_projects_members', 'private_lk_access_key'),
                $respondentStaff['langID'] ? $respondentStaff['langID'] : $projectLangID, 1, 'new', $respondentStaff['department_text']);
              
              $eventListener = new EventListener($this->registry, $context['project'], $_SERVER["DOCUMENT_ROOT"].'/');
              $eventListener->executeEvent('MemberAdded', array(
                'memberID'  => $respondentMember['projects_memberID'],
              ), true);
            }
    
            if ($respondentMember && $respondentMember['active'] && $respondentMember['projectID'] == $context['project']['projectID']) {
              $existRespondent = $this->registry->getDbHelper('MembersHelper')->findRespondentByAssessIDAndMemberID($context['project']['projectID'], $assess['sessionID'], $assess['assessID'], $respondentMember['projects_memberID']);
              if (!$existRespondent) {                
    
                $roleID = $_POST['role'];
    
                $respondentID = $this->registry->getDbHelper('MembersHelper')->addRespondent(array(
                  'projectID'  => $context['project']['projectID'],
                  'sessionID'  => $assess['sessionID'],
                  'project_memberID'  => $respondentMember['projects_memberID'],
                  'active'  => 1,
                  'status'  => 'new',
                  'stat1_assessID'  => $assess['assessID'],
                  'private_access_key'  => $this->registry->getModel('Auth')->generateUid('prep_respondents', 'private_access_key'),
                  'stat1_roleID'  => $roleID,
                  'deny_deletion'  => 0,
                ));
              }
            }
          }
      }
    
      // подтверждение
      if (isset($_POST['view'])) {
        $this->registry->getDbHelper('MembersHelper')->editAssess($assess['assessID'], array('viewed_by_direct_head' => 1));
        LocalRedirect('/enter/'.$context['member']['private_lk_access_key'].'/subordinates/');
      }
    }
    
    $grouppedRespondents = array();
    $subordinateRespondents = $this->registry->getDbHelper('MembersHelper')->getSubordinatesRespondents($context['project']['projectID'], array($assess['assessID']));
    foreach ($subordinateRespondents as $respondent) {
      if (!array_key_exists($respondent['stat1_roleID'], $grouppedRespondents)) {
        $grouppedRespondents[$respondent['stat1_roleID']] = array();
      }
      $grouppedRespondents[$respondent['stat1_roleID']][] = $respondent;
    }
    $this->component->arResult['respondents'] = $grouppedRespondents;
    
    $this->component->IncludeComponentTemplate();
    
    $context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
      'TRANSLATOR'  => $translator,
      'SETTINGS'  => $projectViewSettings,
    ));
  }
}