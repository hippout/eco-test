<?php 

namespace Ecoplay\Controller\Project\KeyBehavior;
use Ecoplay\Controller\Base as Base;

class Type360 extends Base
{
  public function execute($context)
  {
    // ищем оцениваемого по ключу респондента
    $assess = $this->registry->getDbHelper('MembersHelper')->findAssessByRespondentKey($context['respondent']['private_access_key']);
    
    if (!$assess) {
      $this->component->arResult['error'] = 'Неверный ключ';
    }
    else {
      // проверяем статус оцениваемого
      if ($assess['status'] != \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS) {
        // редиректим в лк
        $respondentMember = $this->registry->getDbHelper('MembersHelper')->findById($context['respondent']['project_memberID']);
        LocalRedirect('/enter/'.$respondentMember['private_lk_access_key'].'/');
      }
      
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