<?php 

namespace Ecoplay\Controller\Project\ProjectStat;
use Ecoplay\Controller\Base as Base;

class TypeLinear extends Base
{
  public function execute($context)
  {
    $context['APPLICATION']->AddHeadScript('https://www.google.com/jsapi');
    
    // проверка прав доступа к разделу
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'projects')) {
      $this->registry->getModel('Auth')->restrict();
    }
    
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'project', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
    
    $availableSessionsIDs = $this->registry->getModel('Auth')->getAvailableSessions($_SESSION['accesses'], $context['project']['projectID']);
    
    // определяем даты
    $from = $context['project']['dt_from'] ? strtotime($context['project']['dt_from']) : strtotime($context['project']['dt_create']);
    $to = $context['project']['dt_to'] ? strtotime($context['project']['dt_to']) : time();
    
    //получаем опрашиваемых проекта    
    if (count($availableSessionsIDs)) {
      // стата по анкетам
      $seancesStat = $this->registry->getDbHelper('MembersHelper')->getSessionsSeancesStat($context['project']['projectID'], $availableSessionsIDs);
      $states = array(
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_NEW  => 'Заполнение не начато',
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_PROGRESS  => 'Идёт заполнение',
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE  => 'Заполнение завершено',
      );
      $statesColors = array(
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_NEW  => 'c8331f',
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_PROGRESS  => 'ffcc33',
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE  => '336600',
      );
    
      $busyStates = array();
      $seancesAllCnt = 0;
      foreach ($seancesStat as $key => $seances) {
        $busyStates[] = $seances['name'];
        $seancesStat[$key]['color'] = (array_key_exists($seances['name'], $statesColors)) ? $statesColors[$seances['name']] : '606060';
        $seancesStat[$key]['name']  = (array_key_exists($seances['name'], $states)) ? $states[$seances['name']] : 'Неизвестно';
        $seancesAllCnt += $seances['val'];
      }
      foreach ($states as $key => $name) {
        if (!in_array($key, $busyStates)) {
          $seancesStat[] = array(
            'color'  => $statesColors[$key],
            'name'  => $name,
            'val'  => 0,
          );
        }
      }
    
      if (!$seancesAllCnt) {
        $seancesStat = false;
      }
    
      ### таймлайн      
      $timelineStat = $this->registry->getModel('Stat')->getTimelineStat($from, $to, $availableSessionsIDs);
    }
    else {
      $assessStat = false;
      $seancesStat = false;
      $timelineStat = false;
    }
    $this->component->arResult['assessStat'] = $assessStat;
    $this->component->arResult['seancesStat'] = $seancesStat;
    $this->component->arResult['timelineStat'] = $timelineStat;
    $this->component->arResult['from'] = $from;
    $this->component->arResult['to'] = $to;
    
    $viewHelper = new \Ecoplay\View\Helper();
    
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Статистика'/* проекта "'.$project['project_name'].'"'*/);
    $context['APPLICATION']->SetPageProperty('navigation', $viewHelper->generateNavigation(array(
      0  => array(
        'title' => 'Главная',
        'link'  => '/'
      ),
      1  => array(
        'title' => 'Проекты',
        'link'  => '/projects/'
      ),
      2  => array(
        'title' => $context['project']['project_name'],
      ),
    )));
    
    $this->component->IncludeComponentTemplate('linear/template');
  }
} 