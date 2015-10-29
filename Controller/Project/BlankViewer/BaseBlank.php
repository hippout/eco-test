<?php 

namespace Ecoplay\Controller\Project\BlankViewer;
use Ecoplay\Controller\Base as Base;

/**
 * Абстрактный класс, который реализцет базовое поведение анкеты, основанной на бланке
 */
abstract class BaseBlank extends Base
{
  protected $session = array();
  protected $inputParams = array(); // параметры для фильтрации ветвления
  protected $finished = false; // флаг, закончили ли заполнение анкеты
  protected $msddAnswers = array(); // ответы чебурашки
  protected $BlankDataSource;
  protected $groupLink = false;
  protected $hiddenQuestionsData = array();
  protected $member;
  protected $langID;
  protected $paginationInfo;
  protected $questionsOnPage;
  protected $blank;  
  protected $questionsBlocks = array();
  protected $haveRealBlock = false;
  protected $grouppedQuestions;
  protected $errors = array();
  protected $answers = array();
  protected $answersAddons = array();
  protected $isEnteredAnswers = false;
  
  /**
   * Определяем сессию анкеты
   * @param array $context
   */
  protected function detectSession(&$context)
  {
    $sessionID = 0;
    switch ($this->component->arParams['MODE']) {
      case 'key':
        $sessionID = $context['seance']['sessionID'];
        break;
      case 'blank':
        if (isset($_GET['SESSION_ID'])) {
          $sessionID = $_GET['SESSION_ID'];
          $this->component->arResult['sessionID'] = $sessionID;
        }
        $sessions = $this->registry->getDbHelper('ProjectsHelper')->getSessionsByProjectID($context['project']['projectID']);
        $this->component->arResult['sessions'] = $sessions;
        break;
    }
    
    if ($sessionID) {
      $this->session = $this->registry->getDbHelper('ProjectsHelper')->findSessionById($sessionID);
    }
  }
  
  /**
   * Сохранение ответов на текущую страницу вопросов
   * @param array $context
   * @param bool $wasBack - флаг, были ли даны ответы возвратом на предыдущий экран
   */
  protected function saveAnswers(&$context, $wasBack = false)
  { 
    $answersAddons = array(); // какие-то дополнительные данные по ответу (из-за расширения базовых элементов управления)
    
    // собираем все вопросы - ответы
    foreach ($_POST as $question => $answer) {
      if (preg_match('/^_answer_qt_([0-9]+)$/', $question, $matches)) {
        $answers[$matches[1]] = $answer;
      }
      
      // textarea
      if (preg_match('/^_answer_qt_([0-9]+)_an_([0-9]+)$/', $question, $matches)) {
          if (!array_key_exists($matches[1], $answers)) {
              $answers[$matches[1]] = array();
          }
          $answers[$matches[1]][$matches[2]] = $answer;
      }
      
      // текстовые пояснения для варианта "Другое"
      if (preg_match('/^_answer_text_qt_([0-9]+)$/', $question, $matches)) {
        if (!array_key_exists($matches[1], $answersAddons)) {
          $answersAddons[$matches[1]] = array();
        }
        $answersAddons[$matches[1]]['text_answer'] = $answer;
      }
    }
        
    // если чебурашка, то преобразуем ответы
    $msddDetails = array();    
    
    if (isset($_POST['is_msdd']) && $_POST['is_msdd'] == '1') {
      $this->msddAnswers = $answers;
      $answers = $this->registry->getModel('Blanks')->transformMsddAnswers($answers, $msddDetails);
    }
    
    // валидируем ответы
    $this->inputParams = $this->registry->getModel('Blanks')->getSeanceInputParams($context['seance']['seanceID'], $context['project'], $this->session);
    $this->inputParams = $this->registry->getModel('Blanks')->extendSeanceInputParamsByCurrentAnswers($this->inputParams, $answers);
    
    $answersValid = $this->registry->getModel('Blanks')->validateAnswers($answers, $context['seance'], $this->inputParams, $wasBack, $msddDetails, $answersAddons);
    
    if ($answersValid['status'] == 'error') {
      $this->component->arResult['have_errors'] = true;
      $this->errors = $answersValid['errors'];
      $this->isEnteredAnswers = true;
      $this->answers = $answersValid['valid_answers'];      
    }
    else {
      
      if ($wasBack) {
        $answers = $answersValid['valid_answers'];
      }
      
      // возможно уже есть ответы на этой страницы - их нужно удалить
      if ($context['project']['allow_edit_seance_answers'] || $context['project']['allow_edit_seance_answers_at_complete']) {
        $existAnswers =  $this->registry->getDbHelper('BlanksHelper')->getSeanceScreenAnswers($context['seance']['seanceID'], $answersValid['screenID']);
                
        if ($existAnswers) {
          $answersIDs = array();
          $oldAnswers = array();
          $loggedOldAnswersQuestionsIDs = array();
          foreach ($existAnswers as $answer) {
            $answersIDs[] = $answer['seance_answerID'];
            if ($answer['question_type'] == 'checkbox') {
              if (!array_key_exists($answer['questionID'], $oldAnswers)) {
                $oldAnswers[$answer['questionID']] = array();
              }
              $oldAnswers[$answer['questionID']][] = $answer['answerID'];
            }
            elseif ($answer['question_type'] == 'radio' || $answer['question_type'] == 'dichotomy') {
              $oldAnswers[$answer['questionID']] = $answer['answerID'];
            }
            else {
              $oldAnswers[$answer['questionID']] = $answer['answer_value'];
            }
          }
          $this->registry->getDbHelper('BlanksHelper')->deleteseanceAnswersByIDs($answersIDs);
        }
      }
      
      $inputParams = array(); // очищаем, чтобы получить для следующего экрана      
      foreach ($answers as $questionID => $answer) {
        if (is_array($answer)) {
          foreach ($answer as $kk => $answer1) {
            $answerAddon = array_key_exists($answer1, $answersValid['valid_addon_answers']) ? $answersValid['valid_addon_answers'][$answer1] : false;
                        
            if ($answersValid['answersDetails'][$answer1]['type'] == 'textarea') {
                $answersValid['answersDetails'][$answer1]['answerID'] = $kk;
            }
                         
            $this->registry->getModel('Blanks')->addAnswer($context['seance']['seanceID'], $questionID, $answer1, $answersValid['answersDetails'][$answer1], $context['project']['projectID'], $answerAddon);
          }
        }
        else {          
          $answerAddon = array_key_exists($answer, $answersValid['valid_addon_answers']) ? $answersValid['valid_addon_answers'][$answer] : false;
          $this->registry->getModel('Blanks')->addAnswer($context['seance']['seanceID'], $questionID, $answer, $answersValid['answersDetails'][$answer], $context['project']['projectID'], $answerAddon);
        }
        
        // логируем изменение ответов
        if (($context['project']['allow_edit_seance_answers'] || $context['project']['allow_edit_seance_answers_at_complete']) && $existAnswers) {
          $loggedOldAnswersQuestionsIDs[] = $questionID;
          $this->registry->getDbHelper('LogsHelper')->addAnswerReplace(array(
            'seanceID'  => $context['seance']['seanceID'],
            'respondentID'  => $context['seance']['respondentID'],
            'projectID' => $context['seance']['projectID'],
            'dt'  => date('d-m-Y H:i:s'),
            'questionID'  => $questionID,
            'value_before'  => array_key_exists($questionID, $oldAnswers) ? json_encode($oldAnswers[$questionID]) : '',
            'value_after' => json_encode($answer),
            'ip'  => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',  
            'ua'  => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', 
          ));
        }
      }
      
      // проверяем, возможно "удаленные" вопросы незалогировлись
      if (($context['project']['allow_edit_seance_answers'] || $context['project']['allow_edit_seance_answers_at_complete']) && $existAnswers) {
        foreach ($oldAnswers as $questionID => $oldAnswer) {
          if (!in_array($questionID, $loggedOldAnswersQuestionsIDs)) {
            $this->registry->getDbHelper('LogsHelper')->addAnswerReplace(array(
              'seanceID'  => $context['seance']['seanceID'],
              'respondentID'  => $context['seance']['respondentID'],
              'projectID' => $context['seance']['projectID'],
              'dt'  => date('d-m-Y H:i:s'),
              'questionID'  => $questionID,
              'value_before'  => json_encode($oldAnswer),
              'value_after' => '',
              'ip'  => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
              'ua'  => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            ));
          }
        }
      }
      
      if (!$wasBack) {
        $this->finished = ($answersValid['currentPage'] == $answersValid['pagesCnt']) ? true : false;
      
        if ($answersValid['currentPage'] == 1 && $context['seance']['state'] != \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_COMPLETE) { // помечаем, что сеанс начали отвечать
          $this->registry->getDbHelper('BlanksHelper')->setSeanceState($context['seance']['seanceID'], \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_PROGRESS);
          // добавляем начало заполнения бланка в статистику
          $this->registry->getModel('Stat')->addSeanceStart($context['seance']['sessionID']);
        }
      }
    
      // добавляем ответы в статистику
      $this->registry->getModel('Stat')->addAnswers($context['seance']['sessionID'], count($answers));
      if (!$wasBack) {
        $context['seance']['last_screenID'] = $answersValid['screenID'];
      }
      $this->registry->getDbHelper('BlanksHelper')->increaseSeanceAnswers($context['seance']['seanceID'], count($answers), $answersValid['screenID']);
    }
  }
  
  /**
   * Получение текущего списка вопросов
   * @param array $context
   * @param array $availableLanguages
   */
  protected function getQuestions(&$context, $availableLanguages)
  {
    switch ($this->component->arParams['MODE']) {
      case 'key':
        $this->component->arResult['seance'] = $context['seance'];
        $this->blank = $this->registry->getDbHelper('BlanksHelper')->findById($context['seance']['blankID']);
    
        // обновляем респонденту дату последнего входа
        if (!$this->member) {
          $this->member = $this->registry->getDbHelper('MembersHelper')->findMemberByRespondentID($context['seance']['respondentID']);
        }
        $this->registry->getDbHelper('MembersHelper')->editRespondent($context['seance']['respondentID'], array(
          'last_entrance'  => date('d-m-Y H:i:s'),
        ));
        $this->component->arResult['member'] = $this->member;
    
        $sign = $this->member['name'].' '.$this->member['surname'];
    
        // обработка языка
        if (isset($_POST['language']) && array_key_exists(intval($_POST['language']), $availableLanguages)) {
          $this->langID = intval($_POST['language']);
          $this->registry->getDbHelper('MembersHelper')->editMember($this->member['projects_memberID'], array(
            'langID'  => $this->langID,
          ));
        }
        else {
          $this->langID = $this->member['langID'];
        }
        $this->component->arResult['langID'] = $this->langID;
    
        if (!$this->finished) {
          $haveQuestions = false;
    
          while (!$haveQuestions) {
            // поиск текущих вопросов
            $this->paginationInfo = $this->registry->getModel('Blanks')->getSeanceScreenData($context['seance']);
            if (!$this->paginationInfo) { // какой-то непонятный экран, выкидываем юзера в кабинет
              LocalRedirect('/enter/'.$this->member['private_lk_access_key'].'/');
            }
    
            $this->questionsOnPageSrc = $this->registry->getDbHelper('BlanksHelper')->getScreenQuestions($this->paginationInfo['screen_id'], $this->langID);
            
            if (!count($this->questionsOnPageSrc)) { // на экране вообще нет вопросов, выкидываем юзера в лк
              LocalRedirect('/enter/'.$this->member['private_lk_access_key'].'/');
            }
    
            // тестовое собирание параметров для ветвления
            //if (!count($this->inputParams)) { // т.к. могли быть ответы, коорые добавили выполняемых условий
            $this->inputParams = $this->registry->getModel('Blanks')->getSeanceInputParams($context['seance']['seanceID'], $context['project'], $this->session);            
            //}
            $sortedQuestions = $this->registry->getModel('Blanks')->filterAvailableQuestions($this->questionsOnPageSrc, $this->inputParams, false, $context['seance']['blankID']);
            $this->questionsOnPage = $sortedQuestions['appropriate'];
            $this->hiddenQuestionsData = $this->registry->getModel('Blanks')->getHiddenQuestionsData($sortedQuestions);
            
            if (count($this->questionsOnPage)) { // есть вопросы - выводим
              $haveQuestions = true;
              
              // возможно есть ответы
              if ($context['project']['allow_edit_seance_answers'] || $context['project']['allow_edit_seance_answers_at_complete']) {
                $existAnswers =  $this->registry->getDbHelper('BlanksHelper')->getSeanceScreenAnswers($context['seance']['seanceID'], $this->paginationInfo['screen_id']);                
                if ($existAnswers) {
                  foreach ($existAnswers as $answer) {
                    if ($answer['question_type'] == 'checkbox') {
                      if (!array_key_exists($answer['questionID'], $this->answers)) {
                        $this->answers[$answer['questionID']] = array();
                      }
                      $this->answers[$answer['questionID']][] = $answer['answerID'];
                      if ($answer['answer_value']) { // допы
                        if (!array_key_exists($answer['questionID'], $this->answersAddons)) {
                          $this->answersAddons[$answer['questionID']] = array();
                        }
                        $this->answersAddons[$answer['questionID']][$answer['answerID']] = $answer['answer_value'];
                      }
                    }
                    elseif ($answer['question_type'] == 'radio') {
                      $this->answers[$answer['questionID']] = $answer['answerID'];
                      if ($answer['answer_value']) { // допы
                        if (!array_key_exists($answer['questionID'], $this->answersAddons)) {
                          $this->answersAddons[$answer['questionID']] = array();
                        }
                        $this->answersAddons[$answer['questionID']][$answer['answerID']] = $answer['answer_value'];
                      }
                    }
                    elseif ($answer['question_type'] == 'ranging') {
                      if (!array_key_exists($answer['questionID'], $this->answers)) {
                        $this->answers[$answer['questionID']] = array();
                      }
                      $this->answers[$answer['questionID']][] = $answer['answerID'];
                    }
                    elseif ($answer['question_type'] == 'dichotomy') {
                        $this->answers[$answer['questionID']] = $answer['answerID'];
                    }
                    elseif ($answer['question_type'] == 'textarea') {
                        if (!array_key_exists($answer['questionID'], $this->answers)) {
                            $this->answers[$answer['questionID']] = array();
                        }
                        $this->answers[$answer['questionID']][$answer['answerID']] = $answer['answer_value'];
                    }
                    else {
                      $this->answers[$answer['questionID']] = $answer['answer_value'];
                    }                    
                  }
                }
              }
            }
            elseif ($this->paginationInfo['current_page'] == $this->paginationInfo['page_count']) { // последняя страница с вопросами (которую скипаем), завершаем заполнение
              $haveQuestions = true; // условно
              $this->finished = true;
            }
            else {
              // обновляем текущий экран бланку
              $this->registry->getDbHelper('SeancesHelper')->editSeance($context['seance']['seanceID'], array('last_screenID' => $this->paginationInfo['screen_id']));
              $context['seance']['last_screenID'] = $this->paginationInfo['screen_id'];
            }
          }
          
        }
        $this->BlankDataSource = new \Ecoplay\Model\BlankDataSource($this->registry->getDbConnect(), 0, 0, $context['seance']['seance_key']);
    
        break;
    
      case 'blank':
        if (!isset($this->component->arParams['BLANK_ID']) || !isset($this->component->arParams['PROJECT_ID'])) {
          throw new \Exception('Bad component params');
        }
    
        $this->blank = $this->registry->getDbHelper('BlanksHelper')->findById($this->component->arParams['BLANK_ID']);
    
        $this->langID = isset($_SESSION['langID']) ? $_SESSION['langID'] : 1;
        if (isset($_POST['language']) && array_key_exists(intval($_POST['language']), $availableLanguages)) {
          $this->langID = intval($_POST['language']);
          $_SESSION['langID'] = $this->langID;
        }
        $this->component->arResult['langID'] = $this->langID;
    
        $this->BlankDataSource = new \Ecoplay\Model\BlankDataSource($this->registry->getDbConnect(), $this->component->arParams['BLANK_ID'], $this->component->arParams['PROJECT_ID']);
        $this->paginationInfo = $this->BlankDataSource->getPaginationInfo();
        $this->questionsOnPage = $this->BlankDataSource->getQuestionsOnPage($this->paginationInfo["current_page"], 0, $this->langID);
    
        $groupLink = true;
    
        $sign = 'Респондент Тестовый';
        break;
    
      default:
        throw new \Exception('Unknown component mode: '.$this->component->arParams['MODE']);
        break;
    }
  }
  
  /**
   * Группировка текущих вопросов
   * @param array $context
   */
  protected function groupQuestions(&$context)
  { 
    $this->grouppedQuestions = array(
      'visible'  => $this->questionsOnPage,
      'hidden'  => $this->hiddenQuestionsData['hidden'],
    );
    
    foreach ($this->grouppedQuestions as $group => $questions) {
      foreach($questions as $question) {
        if ($question['blockID']) {
          $question['visible'] = ($group == 'visible') ? true : false;
          $question['is_controll'] = in_array($question['questionID'], $this->hiddenQuestionsData['controllIDs']) ? true : false;
          if (!array_key_exists($question['blockID'], $this->questionsBlocks)) { // такой группы еще нет
            $blockInfo = $this->registry->getDbHelper('BlanksHelper')->findBlockById($question['blockID']);
            $block = array(
              'question_type' => $question['question_type'],
              'questions'  => array($question),
              'questions_ids'  => array($question['questionID']),
              'params'  => (array)json_decode($blockInfo['~params']),
            );
            if (in_array($question['question_type'], array('radio', 'checkbox'))) { // идентичность ответов
              $answersNames = array();
              foreach ($question['answers'] as $answer) {
                $answersNames[] = $answer['name'];
              }
              $block['answers_string'] = implode(';', $answersNames);
            }
            $this->questionsBlocks[$question['blockID']] = $block;
          }
          else { // группа уже есть, проверяем, чтобы подходили под ее условия
            $block = $this->questionsBlocks[$question['blockID']];
            if ($question['question_type'] == $this->questionsBlocks[$question['blockID']]['question_type']) { // группировать можно только вопросы одного типа
              if (in_array($question['question_type'], array('radio', 'checkbox'))) { // идентичность ответов
                $answersNames = array();
                foreach ($question['answers'] as $answer) {
                  $answersNames[] = $answer['name'];
                }
                $answersString = implode(';', $answersNames);
                if ($this->questionsBlocks[$question['blockID']]['answers_string'] == $answersString) {
                  $this->questionsBlocks[$question['blockID']]['questions'][] = $question;
                  $this->questionsBlocks[$question['blockID']]['questions_ids'][] = $question['questionID'];
                  $this->haveRealBlock = true;
                }
              }
              else {
                $this->questionsBlocks[$question['blockID']]['questions'][] = $question;
                $this->questionsBlocks[$question['blockID']]['questions_ids'][] = $question['questionID'];
                $this->haveRealBlock = true;
              }
            }
          }
        }
      }
    }
  }
  
  /**
   * Показ вопросов
   * @param array $context
   */
  protected function showQuestions(&$context, $blockMinQuestions = 1, $availableLanguages)
  {
    $doneBlocks = array(); // блоки, которые уже были выведены
    
    $resultQuestions = array();
    foreach ($this->grouppedQuestions as $group => $questions) {      
      foreach($questions as $question) {        
        $question['visible']  = ($group == 'visible') ? true : false;
        $resultQuestions[$question['sortby']] = $question;        
      }
    }    
    ksort($resultQuestions);
        
    //foreach ($this->grouppedQuestions as $group => $questions) {
      //foreach($questions as $question) {
      foreach($resultQuestions as $question) {
    
        $show = false;
         
        if ($question['blockID'] && (count($this->questionsBlocks[$question['blockID']]['questions']) > $blockMinQuestions)
          && in_array($question['questionID'], $this->questionsBlocks[$question['blockID']]['questions_ids'])) { // вывод в блоке
          if (!in_array($question['blockID'], $doneBlocks)) {
            $show = true;
             
            if (!$this->isEnteredAnswers && ($context['project']['allow_edit_seance_answers'] || $context['project']['allow_edit_seance_answers_at_complete'])) {
              $existAnswers =  $this->registry->getDbHelper('BlanksHelper')->getSeanceScreenAnswers($context['seance']['seanceID'], $this->paginationInfo['screen_id']);              
              if ($existAnswers) {                
                $questionsIDs = array();
                $keyedQuestions = array();
                foreach ($this->questionsBlocks[$question['blockID']]['questions'] as $question) {
                  $questionsIDs[] = $question['questionID'];
                  $keyedQuestions[$question['questionID']] = $question;
                }
                $answers = array();
                foreach ($existAnswers as $answer) {
                  if (in_array($answer['questionID'], $questionsIDs)) {
                    $answers[] = $answer;
                  }
                }
                
                $this->msddAnswers = $this->registry->getModel('Blanks')->transformAnswersToMsdd($keyedQuestions, $answers);
              }
            }
            
            $contextParams = $this->registry->getModel('Blanks')->getContextParams($context['project'], $this->session, $context['seance'], $this->member);
            $blockQuestions = $this->questionsBlocks[$question['blockID']]['questions'];
            foreach ($blockQuestions as $key => $blockQuestion) {
              $blockQuestions[$key]['~name'] = $this->registry->getModel('Blanks')->prepareQuestionName($blockQuestion['~name'], $contextParams);
              
              foreach ($blockQuestion['answers'] as $aKey => $answer) {
                  $blockQuestions[$key]['answers'][$aKey]['~name'] = $this->registry->getModel('Blanks')->prepareQuestionName($answer['~name'], $contextParams);
              }
            }
            
            // вывод блока
            $param_array = Array(
              "questions" => $blockQuestions,
              "blockParams" => $this->questionsBlocks[$question['blockID']]['params'],
              "mode" => 'block',
              'errors'  => array(),
              'values'  => array(),
              'lang'  => $availableLanguages[$this->langID]['abbr'],
              'alternation'  => $context['project']['blank_group_alternation'],
              'msddAnswers' => $this->msddAnswers,
            );
             
            foreach ($this->questionsBlocks[$question['blockID']]['questions_ids'] as $questionID) {
              if (array_key_exists($questionID, $this->errors)) {
                $param_array['errors'][$questionID] = $this->errors[$questionID];
              }
              if (!array_key_exists($questionID, $this->errors) && array_key_exists($questionID, $this->answers)) {                
                $param_array['values'][$questionID] = $this->answers[$questionID];
              }
            }
             
            $questionType = $this->questionsBlocks[$question['blockID']]['question_type'];
            $doneBlocks[] = $question['blockID'];
          }
        }
        else {
          $show = true;
          $questionType = $question["question_type"];          
          
          $contextParams = $this->registry->getModel('Blanks')->getContextParams($context['project'], $this->session, $context['seance'], $this->member);          
          $question['~name'] = $this->registry->getModel('Blanks')->prepareQuestionName($question['~name'], $contextParams);
          
          foreach ($question['answers'] as $key => $answer) {
              $question['answers'][$key]['~name'] = $this->registry->getModel('Blanks')->prepareQuestionName($answer['~name'], $contextParams);              
          }
                     
          $param_array = Array(
            "BlankDataSource" => &$this->BlankDataSource,
            "question" => $question,
            "question_params" => json_decode($question['~params'], true),
            'error' => array_key_exists($question['questionID'], $this->errors) ? $this->errors[$question['questionID']] : false,
            'value' => (!array_key_exists($question['questionID'], $this->errors) && array_key_exists($question['questionID'], $this->answers)) ? $this->answers[$question['questionID']] : false,
            'value_addons'  => (!array_key_exists($question['questionID'], $this->errors) && array_key_exists($question['questionID'], $this->answersAddons)) ? $this->answersAddons[$question['questionID']] : false,
            'lang'  => $availableLanguages[$this->langID]['abbr'],
            'is_controll' => in_array($question['questionID'], $this->hiddenQuestionsData['controllIDs']) ? true : false,
            'visible'  =>  $question['visible'] ? true : false,
          );
        }
         
        if ($show) {
          switch ($questionType) {
            case 'radio':
              $context['APPLICATION']->IncludeComponent("ecoplay:behavior.questions.radio", "", $param_array);
              break;
               
            case 'checkbox':
              $context['APPLICATION']->IncludeComponent("ecoplay:behavior.questions.checkbox", "", $param_array);
              break;
    
            case 'text':
              $context['APPLICATION']->IncludeComponent("ecoplay:behavior.questions.text", "", $param_array);
              break;
    
            case 'textarea':
              $context['APPLICATION']->IncludeComponent("ecoplay:behavior.questions.textarea", "", array_merge($param_array, array('is_signed' => false)));
              break;
    
            case 'textarea_signed':
              $context['APPLICATION']->IncludeComponent("ecoplay:behavior.questions.textarea", "", array_merge($param_array, array('is_signed' => true, 'sign' => $sign)));
              break;
    
            case 'html':
              $context['APPLICATION']->IncludeComponent("ecoplay:behavior.questions.html", "", $param_array);
              break;
              
            case 'ranging':
              $context['APPLICATION']->IncludeComponent("ecoplay:behavior.questions.ranging", "", $param_array);
              break;
              
            case 'dichotomy':
                $context['APPLICATION']->IncludeComponent("ecoplay:behavior.questions.dichotomy", "", $param_array);
                break;
    
            default:
              throw new \Exception('При импорте вопроса был задан неизвестный тип вопроса: "'.$questionType.'"');
              break;
          }
        }
      }
    //}
  }
}