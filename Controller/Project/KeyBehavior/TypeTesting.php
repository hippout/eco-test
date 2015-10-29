<?php 

namespace Ecoplay\Controller\Project\KeyBehavior;
use Ecoplay\Controller\Base as Base;

class TypeTesting extends Base
{
  public function execute($context)
  {
    // проверяем, возможно сеанс уже существует
    $seance = $this->registry->getDbHelper('TestsSeancesHelper')->findSeanceByAccessKey($context['respondent']['private_access_key']);
        
    if (!$seance) {
      $seance = $this->registry->getModel('Tests')->createSeance($context['respondent']);
    }
    
    if (!$seance) {
      $this->component->arResult['error'] = 'Неверный ключ';
    }
    else {
      LocalRedirect('/s/'.$seance['seance_key'].'/');
    }
  }
}