<?php 

namespace Ecoplay\Controller\Project\KeyBehavior;
use Ecoplay\Controller\Base as Base;

class TypeLinear extends Base
{
  public function execute($context)
  { 
    if ($context['respondent']['status'] != \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE) {      
      $this->component->arResult['error'] = 'Неверный ключ';
    }
    else {
      // ключ не ЛК, значит ищем сеанс для этого ключа
      $seance = $this->registry->getDbHelper('SeancesHelper')->findSeanceByAccessKey($context['respondent']['private_access_key']);
      if (!$seance) {
        $seance = $this->registry->getModel('Blanks')->createSeance($context['respondent']['private_access_key']);
      }
      
      if (!$seance) {
        $this->component->arResult['error'] = 'Неверный ключ';
      }
      else {
        LocalRedirect('/s/'.$seance['seance_key'].'/');
      }
    }
  }
}