<?php 

namespace Ecoplay\Controller\Project\BlankViewer;
use Ecoplay\Controller\Base as Base;

class TypeTesting extends Base
{
  public function execute($context)
  {
    try {
      $this->registry->getDbConnect()->StartTransaction();
      
      $this->component->arResult['project'] = $context['project'];
      
      $availableLanguages = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs(json_decode($context['project']['~languages']));
      $this->component->arResult['languages'] = $availableLanguages;
      
      // проверяем статус респондента
      $respondent = $this->registry->getDbHelper('MembersHelper')->findRespondentById($context['seance']['respondentID']);
      if ($respondent['status'] != \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_WAITING
        && $respondent['status'] != \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE) {
        $this->registry->getModel('Auth')->restrict();
      }
      
      // обновляем респонденту дату последнего входа
      $member = $this->registry->getDbHelper('MembersHelper')->findMemberByRespondentID($context['seance']['respondentID']);
      
      if ($member['stat2_group_keyID']) {
        $groupKey = $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID($member['stat2_group_keyID']);        
        if ($groupKey['status'] == \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_STATUS_DISABLED) {         
      
          $this->component->IncludeComponentTemplate('testing/disabled');
          
          $this->registry->getDbConnect()->Commit();
          return true;
        }
      }
      
      $respondentData = array(
        'last_entrance'  => date('d-m-Y H:i:s'),
      );
      if ($respondent['status'] == \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_WAITING) { // переводим респондента в состояние тестирования
        $respondentData['status'] = \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_ACTIVE;
      }
      $this->registry->getDbHelper('MembersHelper')->editRespondent($context['seance']['respondentID'], $respondentData);
      $this->component->arResult['member'] = $member;
      $context['APPLICATION']->setPageProperty('memberName', $member['name'].' '.$member['surname']);
      $context['APPLICATION']->setPageProperty('memberKey', $member['private_lk_access_key']);
      
      // обработка языка
      if (isset($_POST['language']) && array_key_exists(intval($_POST['language']), $availableLanguages)) {
        $langID = intval($_POST['language']);
        $this->registry->getDbHelper('MembersHelper')->editMember($member['projects_memberID'], array(
          'langID'  => $langID,
        ));
      }
      else {
        $langID = $member['langID'];
      }
      $this->component->arResult['langID'] = $langID;
      
      
      $translator = new \Ecoplay\Model\Translation($this->registry->getDbConnect(), $availableLanguages[$langID]['abbr'], $_SERVER["DOCUMENT_ROOT"].'/lang/', $this->registry);
      $this->component->arResult['translator'] = $translator;
      
      $projectViewSettings = ($context['project']['param_lk']) ? (array)json_decode($context['project']['~param_lk']) : array();
      $this->component->arResult['projectViewSettings'] = $projectViewSettings;
      
      /*$context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
        'TRANSLATOR'  => $translator,
        'MODE'  => 'blank',
        'MEMEBER_KEY'  => $member['private_lk_access_key'],
        'SETTINGS'  => $projectViewSettings,
        'ADDON_CSS' => false,
        'TEXT'  => '',
      ));*/
      
      $context['seance']['updated_fields'] = array(); // массив со свойствами сеанса которые нужно обновить в конце контроллера (чтобы не апдейтить несколькими запросами
      
      // получаем инфу по текущему тесту      
      $test = $this->registry->getModel('Tests')->getCurrentTest($context['seance']);
      $this->component->arResult['test'] = $test;
      
      // получаем инфу по текущему таску
      $taskData = $this->registry->getModel('Tests')->getCurrentTaskData($context['seance'], false, isset($_POST['level']) ? intval($_POST['level']) : 0);
             
      // получаем инфу по ответам на таск
      $task = $taskData['task'];
      $task['questionID'] = $task['taskID'];
      $task['answers']  = $this->registry->getModel('Tests')->getTaskAnswers($task['taskID']);
      
      $testCompleted = false; // флаг о том, что прохождение теста завершено
      $lastTaskNotification = false; // флаг о том, что нужно проинформировать как завершился прошлый таск
      $errors = array();
      
      if (isset($_POST['action']) && $_POST['action'] == 'save_answers' && !array_key_exists('task_selected', $taskData)) {
        if ($taskData['task']['question_type'] == 'html') { // отмечаем что тексмт просмотрели
          $taskData = $this->registry->getModel('Tests')->getCurrentTaskData($context['seance'], true, isset($_POST['level']) ? intval($_POST['level']) : 0);
          
          // получаем инфу по текущему таску
          $taskData = $this->registry->getModel('Tests')->getCurrentTaskData($context['seance']);
          
          // получаем инфу по ответам на таск
          $task = $taskData['task'];
          $task['questionID'] = $task['taskID'];
          $task['answers']  = $this->registry->getModel('Tests')->getTaskAnswers($task['taskID']);
        }
        else { // обрабатываем ответ на таск
                    
          //обновляем время
          $isLate = false; // флаг что не вписались в лимит времени
          if ($taskData['task']['task_time_limit']) {
            $elapsed = time() - strtotime($context['seance']['timer_task_start']); // прошло времени с начала вывода таска
            if ($elapsed > $taskData['task']['task_time_limit']) {
              $isLate = true;
              // пустой ответ              
              $this->registry->getModel('Tests')->addEmptyAnswer($context['seance'], $task,
                time() - strtotime($context['seance']['timer_section_start']),
                time() - strtotime($context['seance']['timer_task_start'])
              );
              // обновляем сеанс текущим состоянием с учетом неответа на таск              
              $this->registry->getModel('Tests')->updateSeanceCurrentState($context['seance'], $task, $taskData['section']);
              
              if ($context['seance']['current_sectionID']) {
                // получаем инфу по текущему таску
                $taskData = $this->registry->getModel('Tests')->getCurrentTaskData($context['seance']);
              
                // получаем инфу по ответам на таск
                $task = $taskData['task'];
                $task['questionID'] = $task['taskID'];
                $task['answers']  = $this->registry->getModel('Tests')->getTaskAnswers($task['taskID']);
              }
              else {
                $testCompleted = true;
              }
            }
          }
          elseif ($taskData['section']['section_time_limit']) {
            /*$leftTime = $_POST['left_time']; // оставшееся время исходя из таймера на странице
            $context['seance']['timer_current_section_time'] = $taskData['section']['section_time_limit'] - $leftTime;*/
            
            $elapsed = time() - strtotime($context['seance']['timer_section_start']); // прошло времени с начала секции
            if ($elapsed > $taskData['section']['section_time_limit']) {
              $isLate = true;
              
              // пустой ответ
              $this->registry->getModel('Tests')->addEmptyAnswer($context['seance'], $task,
                time() - strtotime($context['seance']['timer_section_start']),
                time() - strtotime($context['seance']['timer_task_start'])
              );
              
              // обновляем сеанс текущим состоянием с учетом завершения секции
              $this->registry->getModel('Tests')->updateSeanceCurrentState($context['seance'], $task, $taskData['section'], true);
                            
              if ($context['seance']['current_sectionID']) {
                // получаем инфу по текущему таску
                $taskData = $this->registry->getModel('Tests')->getCurrentTaskData($context['seance']);
              
                // получаем инфу по ответам на таск
                $task = $taskData['task'];
                $task['questionID'] = $task['taskID'];
                $task['answers']  = $this->registry->getModel('Tests')->getTaskAnswers($task['taskID']);
              }
              else {
                $testCompleted = true;
              }
            }
            else {            
              $context['seance']['timer_current_section_time'] = $elapsed;
              $context['seance']['updated_fields'][] = 'timer_current_section_time';
            }
          }
          elseif ($test['test_time_limit']) {            
            $elapsed = time() - strtotime($context['seance']['timer_test_start']); // прошло времени с начала nеста            
            if ($elapsed > $test['test_time_limit']) { // время теста истекло
              $isLate = true;   

              // пустой ответ
              $this->registry->getModel('Tests')->addEmptyAnswer($context['seance'], $task,
                time() - strtotime($context['seance']['timer_section_start']),
                time() - strtotime($context['seance']['timer_task_start'])
              );
              
              // обновляем сеанс текущим состоянием с учетом завершения теста
              $this->registry->getModel('Tests')->updateSeanceCurrentState($context['seance'], $task, $taskData['section'], false, true);
              $testCompleted = true;
            }
          }

          if (!$isLate) {
            // собираем все ответы
            $answer = false;
            $taskID = false;
            if ($task['question_type'] == 'msdd') {
                $answer = array();
                $taskID = $task['taskID'];
                foreach ($_POST as $parID => $answerData) {
                    if (preg_match('/^_answer_qt_([0-9]+)$/', $parID, $matches)) {
                        $answer[$matches[1]] = $answerData;
                    }
                }                
            }
            else {
                foreach ($_POST as $question => $answerData) {
                    if (preg_match('/^_answer_qt_([0-9]+)$/', $question, $matches)) {
                        $answer = $answerData;
                        $taskID = $matches[1];
                    }
                }
            }
                                  
            // валидируем введенные данные
            $answerValid = $this->registry->getModel('Tests')->validateTaskAnswer($task, $answer, $taskID);
                        
            if ($answerValid['status'] == 'success') {                                       
              $this->registry->getModel('Tests')->addAnswer($context['seance'], $task, $answer, $taskData['section'],
                time() - strtotime($context['seance']['timer_section_start']),
                time() - strtotime($context['seance']['timer_task_start'])
              );
              
              // обновляем сеанс текущим состоянием
              $this->registry->getModel('Tests')->updateSeanceCurrentState($context['seance'], $task, $taskData['section']);
              
              if (array_key_exists('last_correct', $context['seance'])) {
                $lastTaskNotification = true;
              }
              else {
                if ($context['seance']['current_sectionID']) {            
                  // получаем инфу по текущему таску
                  $taskData = $this->registry->getModel('Tests')->getCurrentTaskData($context['seance']);
                  
                  // получаем инфу по ответам на таск
                  $task = $taskData['task'];
                  $task['questionID'] = $task['taskID'];
                  $task['answers']  = $this->registry->getModel('Tests')->getTaskAnswers($task['taskID']);
                }
                else {
                  $testCompleted = true;
                }
              }
            }
            else {
              $errors = $answerValid['errors'];
            }
          }
        } 
      }
            
      if ($context['seance']['state'] == \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE) {
        // переводим респондента в состояние завершенного тестирования        
        $this->registry->getDbHelper('MembersHelper')->editRespondent($context['seance']['respondentID'], array(
          'status'  => \Ecoplay\Helper\Db\MembersHelper::RESPONDENT_STATUS_COMPLETE,
        ));
        
        // выводим завершающий текст теста
        $testText = $this->registry->getDbHelper('TestsHelper')->findTestText($context['seance']['current_testID'], 'finish');
        if ($testText) {
          $this->component->arResult['testText'] = $testText;
        }
        
        $this->component->IncludeComponentTemplate("testing_completed");
      }
      elseif ($lastTaskNotification) {
        $this->component->arResult['last_correct'] = $context['seance']['last_correct'];
        $this->component->arResult['score'] = round($context['seance']['stat_score']);
        $this->component->arResult['show_level_hint'] = true;
        $this->component->IncludeComponentTemplate("before_testing");
        
        $this->component->arResult['last_score'] = round($context['seance']['last_score']);
        $sectionParams = json_decode($taskData['section']['~params'], true);
        if ($sectionParams && isset($sectionParams['algorithm']) && $sectionParams['algorithm'] == 2) {
          $this->component->arResult['algorithm'] = 2;
        }
        
        $this->component->IncludeComponentTemplate("testing/ornarez_result");        
      }
      elseif ($testCompleted) { // завершено заполнение одного теста
        $this->component->arResult['testText'] = false;
        if (array_key_exists('previous_testID', $context['seance'])) {
          // выводим завершающий текст теста
          $testText = $this->registry->getDbHelper('TestsHelper')->findTestText($context['seance']['previous_testID'], 'finish');
          if ($testText) {
            $this->component->arResult['testText'] = $testText;
          }
        }
        $this->component->IncludeComponentTemplate("testing_test_completed");
      }
      elseif (array_key_exists('show_level_hint', $taskData) && $taskData['show_level_hint']) { // выводим окно с запросом уровня вопроса
        $this->component->arResult['show_level_hint'] = true;
        $this->component->arResult['last_correct'] = $taskData['last_correct'];
        $this->component->arResult['left_tasks'] = $taskData['left_tasks'];
        $this->component->arResult['score'] = round($context['seance']['stat_score']);
        $this->component->arResult['last_score'] = round($context['seance']['last_score']);
                
        $this->component->IncludeComponentTemplate("before_testing");
        
        $sectionParams = json_decode($taskData['section']['~params'], true);              
        if ($sectionParams && isset($sectionParams['algorithm']) && $sectionParams['algorithm'] == 2) {
          $this->component->arResult['algorithm'] = 2;
        }
                
        $this->component->IncludeComponentTemplate("testing/ornarez_level");
        $this->component->IncludeComponentTemplate("after_testing");
      }
      else { // продолжаем заполнение теста
        $this->component->arResult['is_text'] = $taskData['is_text'];
        if (!$taskData['is_text']) {
          $this->component->arResult['index'] = $taskData['index'];
          $this->component->arResult['count'] = $taskData['count'];
          
          // таймер          
          if ($taskData['task']['task_time_limit']) {
            $this->component->arResult['left'] = $taskData['task']['task_time_limit'] - (time() - strtotime($context['seance']['timer_task_start']));
          }
          elseif ($taskData['section']['section_time_limit']) {
            $this->component->arResult['left'] = $taskData['section']['section_time_limit'] - (time() - strtotime($context['seance']['timer_section_start'])); 
          }
          elseif ($test['test_time_limit']) {
            $this->component->arResult['left'] = $test['test_time_limit'] - (time() - strtotime($context['seance']['timer_test_start']));
          }
        }
        else {
          $this->component->arResult['buttonText'] = 'Начать';
        }
        
        $this->component->IncludeComponentTemplate("before_testing");
                
        $param_array = Array(        
          'question' => $task,
          'error' => count($errors) ? $errors : false,
          'value' => false,
          'lang'  => $availableLanguages[$langID]['abbr'],
          'is_controll' => false,
          'visible'  => true,
          'cleanView'  => true,
          'testingView'  => true,
        );
        
        switch ($task['question_type']) {
          case 'radio':
            $context['APPLICATION']->IncludeComponent('ecoplay:behavior.questions.radio', '', $param_array);
            break;
            
          case 'checkbox':          
            $context['APPLICATION']->IncludeComponent('ecoplay:behavior.questions.checkbox', '', $param_array);
            break;
            
          case 'ranging':
            $context['APPLICATION']->IncludeComponent('ecoplay:behavior.questions.ranging', '', $param_array);
            break;
            
          case 'likert':
            $context['APPLICATION']->IncludeComponent('ecoplay:behavior.questions.likert', '', $param_array);
            break;
            
          case 'text':
            $context['APPLICATION']->IncludeComponent('ecoplay:behavior.questions.text', '', $param_array);
            break;
            
          case 'textarea':
            $context['APPLICATION']->IncludeComponent('ecoplay:behavior.questions.textarea', '', $param_array);
            break;
            
          case 'html':
            $context['APPLICATION']->IncludeComponent('ecoplay:behavior.questions.html', '', $param_array);
            break;
            
          case 'msdd':
              $context['APPLICATION']->IncludeComponent('ecoplay:behavior.questions.msdd', '', $param_array);
              break;
        }
        
        if ($taskData['is_text'] && $taskData['section']['section_type'] == 'ornarez') {
          $this->component->IncludeComponentTemplate("testing/ornarez_level");
        }
        
        $this->component->IncludeComponentTemplate("after_testing");
      }
      
      if (count($context['seance']['updated_fields'])) {
        $seanceNewData = array();
        foreach ($context['seance']['updated_fields'] as $field) {
          $seanceNewData[$field] = $context['seance'][$field];
        }
        $this->registry->getDbHelper('TestsSeancesHelper')->editSeance($context['seance']['tests_seanceID'], $seanceNewData);
      }
      
      /*$context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
        'TRANSLATOR'  => $translator,
        'SETTINGS'  => $projectViewSettings,
      ));*/
      
      $this->registry->getDbConnect()->Commit();
    }
    catch (\Exception $e) {
      $this->component->arResult['error'] = $e->getMessage();
      $this->component->IncludeComponentTemplate("error");
    }
  }
}