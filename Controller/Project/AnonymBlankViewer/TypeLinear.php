<?php 

namespace Ecoplay\Controller\Project\AnonymBlankViewer;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Controller\EventListener;

class TypeLinear extends Base
{
  public function execute($context)
  {
    // проверяем валидность ключа
    if ($context['groupKey']['is_registration_available']) {
      LocalRedirect('/g/'.$context['groupKey']['access_key'].'/');
    }
    
    $seanceValid = false;
    $groupKeyValid = true;
    
    // проверяем наличие ключа сеанса в куке
    if (isset($_COOKIE['gk'.$context['groupKey']['group_keyID'].'_seance_key'])) {
      $seance = $this->registry->getDbHelper('SeancesHelper')->findSeanceByKey($_COOKIE['gk'.$context['groupKey']['group_keyID'].'_seance_key']);
      if ($seance && $seance['state'] != \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE) {
        $seanceValid = true;
      }
    }
    
    if (!$seanceValid) { 
      // проверяем возможность регистрации по ключу
      $existCnt = $this->registry->getDbHelper('TestsHelper')->getGroupKeyRespondentsCount($context['project']['projectID'], $context['groupKey']['group_keyID']);
      if ($context['groupKey']['fact_count'] && $existCnt >= $context['groupKey']['fact_count']) {
        $groupKeyValid = false;
        $this->component->IncludeComponentTemplate('linear/limit', '/bitrix/components/ecoplay/group.key.enter/templates/.default');        
      }
      elseif ($context['groupKey']['status'] != \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_STATUS_ACTIVE) {
        $groupKeyValid = false;
        $this->component->IncludeComponentTemplate('linear/disabled', '/bitrix/components/ecoplay/group.key.enter/templates/.default');
      }
      else { // ключ валидный, места есть - создаем участника, респондента, сеанс и кладем его в куку        
        $memberKey = $this->registry->getModel('Auth')->generateUid('prep_projects_members', 'private_lk_access_key');
        $name = 'Авторегистрация '.date('H:i d.m.Y').' по ключу #'.$context['groupKey']['group_keyID'].' '.$context['groupKey']['name'];
        $langID = $this->registry->getModel('Projects')->getDefaultLangID($context['project']);
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
        
        $seance = $this->registry->getModel('Blanks')->createSeance($respondentKey);        
        setcookie('gk'.$context['groupKey']['group_keyID'].'_seance_key', $seance['seance_key'], strtotime( '+1 years' ));
      }
    }
    
    if ($groupKeyValid) {
      
      $this->component->arParams['MODE'] = 'key';
      
      $factory = new \Ecoplay\Controller\Factory($context['project'], $this->registry, $_SERVER["DOCUMENT_ROOT"].'/');
      $controller = $factory->getBlankViewerController($this->component);
      
      $controller->execute(array(
        'seance'  => $seance,  
        'project'  => $context['project'],
        'APPLICATION'  => $context['APPLICATION'],
        'anonym'  => true,
      ));
      
    }    
  }
}