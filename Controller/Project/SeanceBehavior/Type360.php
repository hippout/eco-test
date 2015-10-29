<?php 

namespace Ecoplay\Controller\Project\SeanceBehavior;
use Ecoplay\Controller\Base as Base;

class Type360 extends Base
{
  public function execute($context)
  {     
    // проверяем чтобы опрашиваемый был в нужном статусе
    $assess = $this->registry->getDbHelper('MembersHelper')->findAssessById($context['seance']['stat1_assessID']);
    if ($assess['status'] != \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS) {
      // редиректим в лк
      $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($context['seance']['respondentID']);
      $respondentMember = $this->registry->getDbHelper('MembersHelper')->findById($respondent['project_memberID']);
      LocalRedirect('/enter/'.$respondentMember['private_lk_access_key'].'/');
    }
    
    // логируем посещение    
    $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($context['seance']['respondentID']);
    $this->registry->getModel('Stat')->addHit($context['seance']['sessionID'], $respondent['project_memberID']);
    
    if ($context['seance']['state'] == \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE) {
      $this->component->arResult['error'] = "Опрос по Вашему ключу уже пройден";
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