<?php 

namespace Ecoplay\Controller\Project\WorkflowScripts;

use Ecoplay\Controller\Base as Base;

class TypeWorkflow extends Base
{
  public function execute($context)
  { 
    $scripts = $this->registry->getDbHelper('WorkflowHelper')->getProjectScripts($context['project']['projectID']);
    
    $viewHelper = new \Ecoplay\View\Helper();
    $this->component->arResult['cnt'] = count($scripts);
    $tData = $viewHelper->prepareJsonDataForTable($scripts, array('scriptID', 'name', 'text'));    
    $this->component->arResult['table_data'] = $tData;
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'WorkFlow скрипты');
    $context['APPLICATION']->SetPageProperty('navigation', $viewHelper->generateNavigation(array(
      0  => array(
        'title' => 'Главная',
        'link'  => '/'
      ),
      1  => array(
        'title' => 'Проекты',
        'link' => '/projects/',
      ),
      2  => array(
        'link' => '/projects/'.$context['project']['projectID'].'/continuing/info/',
        'title' => $context['project']['project_name'],
      ),
      3  => array(
        'title' => 'WorkFlow скрипты',
      ),
    )));
    
    $this->component->IncludeComponentTemplate();
  }
}