<?php 

namespace Ecoplay\Controller\Project\SeanceBehavior;
use Ecoplay\Controller\Base as Base;

class TypeLinear extends Base
{
  public function execute($context)
  { 
    // логируем посещение    
    $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($context['seance']['respondentID']);    
    
    $validRespondent = false;
    if (
      ($respondent['status'] == \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE)
      || ($respondent['status'] == \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_COMPLETE && $context['project']['allow_edit_seance_answers_at_complete'])
      ) {
      $validRespondent = true;
    }
    
    if (!$validRespondent) {
      $respondentMember = $this->registry->getDbHelper('MembersHelper')->findById($respondent['project_memberID']);
      LocalRedirect('/enter/'.$respondentMember['private_lk_access_key'].'/');
    }
    
    if ($context['seance']['state'] == \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE 
      && !$context['project']['allow_edit_seance_answers_at_complete']) {
      $this->component->arResult['error'] = "Опрос по Вашему ключу уже пройден";
      $this->registry->getModel('Stat')->addHit($context['seance']['sessionID'], $respondent['project_memberID']);
    }
    else { // показываем бланк
      $context['APPLICATION']->IncludeComponent("ecoplay:behavior.blank.viewer", "", array(
        'MODE'  => 'key',
        'SEANCE_ID'  => $context['seance']['seanceID'],
        'SEANCE_KEY'  => $context['seance']['seance_key'],
      ));
    }
  }
}