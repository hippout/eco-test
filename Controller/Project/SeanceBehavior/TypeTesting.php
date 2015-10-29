<?php 

namespace Ecoplay\Controller\Project\SeanceBehavior;
use Ecoplay\Controller\Base as Base;

class TypeTesting extends Base
{
  public function execute($context)
  {
    // логируем посещение
    $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($context['seance']['respondentID']);
    $this->registry->getModel('Stat')->addHit($context['seance']['sessionID'], $respondent['project_memberID']);
    
    if ($context['seance']['state'] == \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE) {
      $this->component->arResult['error'] = "Опрос по Вашему ключу уже пройден";
    }
    else { // показываем бланк
      $context['APPLICATION']->IncludeComponent("ecoplay:behavior.blank.viewer", "", array(
        'MODE'  => 'key',
        'SEANCE_ID'  => $context['seance']['tests_seanceID'],
        'SEANCE_KEY'  => $context['seance']['seance_key'],
      ));
    }
  }
}