<?php 

namespace Ecoplay\Controller\Project\ProjectStat;
use Ecoplay\Controller\Base as Base;

class Type360 extends Base
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
      $assessStatSrc = $this->registry->getDbHelper('MembersHelper')->getSessionsAssessStat($context['project']['projectID'], $availableSessionsIDs);
    
      // статусы выбора респондентов и оцениваемых аггрегируем в подготовку
      $newAddon = 0;
      $newIndex = false;
      $excludedIndexes = array();
      $assessAllCnt = 0;
      foreach ($assessStatSrc as $index => $data) {
        $assessAllCnt += $data['val'];
        if (in_array($data['name'], array(
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_RESPONDENTS,
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_RESPONDENTS_SELECTED,
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_SELECT_ASSESS,
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS_SELECTED,
        ))) {
          $newAddon += $data['val'];
          $excludedIndexes[] = $index;
        }
        elseif ($data['name'] == \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_NEW) {
          $newIndex = $index;
        }
      }
      if ($newAddon) {
        foreach ($excludedIndexes as $index) {
          unset($assessStatSrc[$index]);
        }
        if ($newIndex !== false) {
          $assessStatSrc[$newIndex]['val'] += $newAddon;
        }
        else {
          $assessStatSrc[] = array(
            'name'  => \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_NEW,
            'val'  => $newAddon,
          );
        }
      }
    
      if ($assessAllCnt) {
        $statuses = array(
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_COMPLETE  => 'Оценка завершена',
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS  => 'Оценивается',
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_NEW  => 'Подготовка',
        );
        $statusesColors = array(
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_NEW  => 'c8331f',
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS  => 'ffcc33',
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_COMPLETE  => '336600',
        );
        $statusesSorts = array(
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_COMPLETE  => 0,
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_ASSESS  => 1,
          \Ecoplay\Helper\Db\MembersHelper::ASSESS_STATUS_NEW  => 2,
        );
    
        $assessStat = array();
        foreach ($statusesSorts as $key => $sort) {
          $assessStat[$sort] = array(
            'color'  => $statusesColors[$key],
            'name'  => $statuses[$key],
            'val'  => 0,
          );
        }
    
        foreach ($assessStatSrc as $assess) {
          $assessStat[$statusesSorts[$assess['name']]]['val']  = $assess['val'];
        }
      }
      else {
        $assessStat = false;
      }
    
      // стата по анкетам
      $seancesStat = $this->registry->getDbHelper('MembersHelper')->getSessionsSeancesStat($context['project']['projectID'], $availableSessionsIDs);      
      $states = array(
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_NEW  => 'Заполнение не начато',
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_PROGRESS  => 'Идёт заполнение',
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE  => 'Заполнение завершено',
      );
      $statesCombines = array(
          \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_RESULTS => \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_PROGRESS,
      );
      $statesColors = array(
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_NEW  => 'c8331f',
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_PROGRESS  => 'ffcc33',
        \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE  => '336600',
      );
    
      // объединяем нужные статусы
      $statesAddons = array();
      $keysToUnset = array();
      foreach ($seancesStat as $key => $seances) {
          if (array_key_exists($seances['name'], $statesCombines)) {
              $statesAddons[$statesCombines[$seances['name']]] = $seances['val'];                            
              $keysToUnset[] = $key;
          }
      }
      foreach ($keysToUnset as $key) {
          unset($seancesStat[$key]);
      } 
      
      $busyStates = array();
      $seancesAllCnt = 0;
      foreach ($seancesStat as $key => $seances) {
        $busyStates[] = $seances['name'];
        $seancesStat[$key]['color'] = (array_key_exists($seances['name'], $statesColors)) ? $statesColors[$seances['name']] : '606060';
        $seancesStat[$key]['name']  = (array_key_exists($seances['name'], $states)) ? $states[$seances['name']] : 'Неизвестно';
        $addon = array_key_exists($seances['name'], $statesAddons) ? $statesAddons[$seances['name']] : 0;
        $seancesStat[$key]['val'] += $addon;
        $seancesAllCnt += ($seances['val'] + $addon);
      }
      foreach ($states as $key => $name) {
        if (!in_array($key, $busyStates)) {
            $addon = array_key_exists($name, $statesAddons) ? $statesAddons[$name] : 0;
            $seancesStat[] = array(
                'color'  => $statesColors[$key],
                'name'  => $name,
                'val'  => $addon,
            );
            $seancesAllCnt += $addon;
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
    
    $this->component->IncludeComponentTemplate();
  }
} 