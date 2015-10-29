<?php 

namespace Ecoplay\Controller\Project\BlankViewer;
use Ecoplay\Controller\Base as Base;

class Type360 extends Base
{
  public function execute($context)
  {
    // обработка ответов
    $errors = array();
    $answers = array();
        
    try {
      $this->registry->getDbConnect()->StartTransaction();
      
      $sessionID = 0;
      switch ($this->component->arParams['MODE']) {
        case 'key':          
          $sessionID = $context['seance']['sessionID'];
          break;
        case 'competency':
        case 'blank':          
          if (isset($_GET['SESSION_ID'])) { 
            $sessionID = $_GET['SESSION_ID'];
            $this->component->arResult['sessionID'] = $sessionID;
          }
          $sessions = $this->registry->getDbHelper('ProjectsHelper')->getSessionsByProjectID($context['project']['projectID']);
          $this->component->arResult['sessions'] = $sessions;
          break;
      }
      
      $this->component->arResult['project'] = $context['project'];
      
      $session = array();
      if ($sessionID) {
        $session = $this->registry->getDbHelper('ProjectsHelper')->findSessionById($sessionID);
      }
      
      $inputParams = array(); // параметры для фильтрации ветвления
      $finished = false;
      $msddAnswers = array();
      
      if ($this->component->arParams['MODE'] == 'key' && $_POST["action"] == "save_answers") {
        $BlankDataSource = new \Ecoplay\Model\BlankDataSource($this->registry->getDbConnect(), 0, 0, $context['seance']['seance_key']);
      
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
        }
      
        // если чебурашка, то преобразуем ответы
        $msddDetails = array();
        if (isset($_POST['is_msdd']) && $_POST['is_msdd'] == '1') {
          $msddAnswers = $answers;          
          $answers = $this->registry->getModel('Blanks')->transformMsddAnswers($answers, $msddDetails);          
        }
      
        // валидируем ответы
        $inputParams = $this->registry->getModel('Blanks')->getSeanceInputParams($context['seance']['seanceID'], $context['project'], $session);
        $inputParams = $this->registry->getModel('Blanks')->extendSeanceInputParamsByCurrentAnswers($inputParams, $answers);
      
        $answersValid = $this->registry->getModel('Blanks')->validateAnswers($answers, $context['seance'], $inputParams, false, $msddDetails);
      
        if ($answersValid['status'] == 'error') {
          $this->component->arResult['have_errors'] = true;
          $errors = $answersValid['errors'];
        }
        else {
          $inputParams = array(); // очищаем, чтобы получить для следующего экрана
          foreach ($answers as $questionID => $answer) {
            if (is_array($answer)) {
              foreach ($answer as $kk => $answer1) {
                  
                  if ($answersValid['answersDetails'][$answer1]['type'] == 'textarea') {
                      $answersValid['answersDetails'][$answer1]['answerID'] = $kk;
                  }
                                    
                  $BlankDataSource->addAnswer($context['seance']['seanceID'], $questionID, $answer1, $answersValid['answersDetails'][$answer1], $context['project']['projectID']);
              }
            }
            else {
              $BlankDataSource->addAnswer($context['seance']['seanceID'], $questionID, $answer, $answersValid['answersDetails'][$answer], $context['project']['projectID']);
            }
          }
          $finished = ($answersValid['currentPage'] == $answersValid['pagesCnt']) ? true : false;
      
          if ($answersValid['currentPage'] == 1) { // помечаем, что сеанс начали отвечать
            $this->registry->getDbHelper('BlanksHelper')->setSeanceState($context['seance']['seanceID'], \Ecoplay\Helper\Db\BlanksHelper::SEANCE_STATE_PROGRESS);
            // добавляем начало заполнения бланка в статистику
            $this->registry->getModel('Stat')->addSeanceStart($context['seance']['sessionID']);
          }
      
          // добавляем ответы в статистику
          $this->registry->getModel('Stat')->addAnswers($context['seance']['sessionID'], count($answers));
          $context['seance']['last_screenID'] = $answersValid['screenID'];
          $this->registry->getDbHelper('BlanksHelper')->increaseSeanceAnswers($context['seance']['seanceID'], count($answers), $answersValid['screenID']);
      
          // проверяем, возможно наступил момент, когда опршиваемому можно сгенерировать отчет
          //$reportsModel = new Ecoplay\Model\Reports($DB);
          //$reportsModel->checkGenerationAvailable($seance['stat1_assessID']); // временно отключено из-за тормозов при большом объеме респондентов / вопросов в бланке
      
          $this->registry->getDbHelper('SeancesHelper')->setNeedCompetencesRecount($context['seance']['stat1_assessID']);
        }
      }
      
      $availableLanguages = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs(json_decode($context['project']['~languages']));
      $this->component->arResult['languages'] = $availableLanguages;
      
      $groupLink = false;
      $hiddenQuestionsData = array(); // скрытые вопросы, которые могут быть отображены при определенных ответах на другие вопросы с той же страницы
      
      $member = false;
      
      switch ($this->component->arParams['MODE']) {
        case 'key':
          $this->component->arResult['seance'] = $context['seance'];
          $blank = $this->registry->getDbHelper('BlanksHelper')->findById($context['seance']['blankID']);
      
          // обновляем респонденту дату последнего входа
          $member = $this->registry->getDbHelper('MembersHelper')->findMemberByRespondentID($context['seance']['respondentID']);
          $this->registry->getDbHelper('MembersHelper')->editRespondent($context['seance']['respondentID'], array(
            'last_entrance'  => date('d-m-Y H:i:s'),
          ));
          $this->component->arResult['member'] = $member;
      
          $sign = $member['name'].' '.$member['surname'];
      
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
          
          if (!$finished) {
            $haveQuestions = false;
      
            while (!$haveQuestions) {
              // поиск текущих вопросов
              $paginationInfo = $this->registry->getModel('Blanks')->getSeanceScreenData($context['seance']);
              if (!$paginationInfo) { // какой-то непонятный экран, выкидываем юзера в кабинет                
                LocalRedirect('/enter/'.$member['private_lk_access_key'].'/');
              }
      
              $questionsOnPageSrc = $this->registry->getDbHelper('BlanksHelper')->getScreenQuestions($paginationInfo['screen_id'], $langID);
              
              if (!count($questionsOnPageSrc)) { // на экране вообще нет вопросов, выкидываем юзера в лк
                LocalRedirect('/enter/'.$member['private_lk_access_key'].'/');
              }
      
              // тестовое собирание параметров для ветвления
              //if (!count($inputParams)) { // т.к. могли быть ответы, коорые добавили выполняемых условий
              $inputParams = $this->registry->getModel('Blanks')->getSeanceInputParams($context['seance']['seanceID'], $context['project'], $session);
              //}
              $sortedQuestions = $this->registry->getModel('Blanks')->filterAvailableQuestions($questionsOnPageSrc, $inputParams, false, $context['seance']['blankID']);
              $questionsOnPage = $sortedQuestions['appropriate'];
              $hiddenQuestionsData = $this->registry->getModel('Blanks')->getHiddenQuestionsData($sortedQuestions);
               
              if (count($questionsOnPage)) { // есть вопросы - выводим
                $haveQuestions = true;
              }
              elseif ($paginationInfo['current_page'] == $paginationInfo['page_count']) { // последняя страница с вопросами (которую скипаем), завершаем заполнение
                $haveQuestions = true; // условно
                $finished = true;
              }
              else {
                // обновляем текущий экран бланку
                $this->registry->getDbHelper('SeancesHelper')->editSeance($context['seance']['seanceID'], array('last_screenID' => $paginationInfo['screen_id']));
                $context['seance']['last_screenID'] = $paginationInfo['screen_id'];
              }
            }
      
            // рудимент для генерации ссылок
            $BlankDataSource = new \Ecoplay\Model\BlankDataSource($this->registry->getDbConnect(), 0, 0, $context['seance']['seance_key']);
          }
      
          break;
      
        case 'competency':
          $blank = false;
          if (!isset($this->component->arParams['COMPETENCY_ID']) || !isset($this->component->arParams['PROJECT_ID'])) {
            throw new \Exception('Bad component params');
          }
      
          $langID = isset($_SESSION['langID']) ? $_SESSION['langID'] : 1;
          if (isset($_POST['language']) && array_key_exists(intval($_POST['language']), $availableLanguages)) {
            $langID = intval($_POST['language']);
            $_SESSION['langID'] = $langID;
          }
          $this->component->arResult['langID'] = $langID;
      
          $BlankDataSource = new \Ecoplay\Model\CompetencyDataSource($this->registry->getDbConnect(), $this->component->arParams['COMPETENCY_ID'], $this->component->arParams['PROJECT_ID']);
          $paginationInfo = $BlankDataSource->getPaginationInfo();
          $questionsOnPage = $BlankDataSource->getQuestionsOnPage($paginationInfo["current_page"], $langID);
          break;
      
        case 'blank':
          if (!isset($this->component->arParams['BLANK_ID']) || !isset($this->component->arParams['PROJECT_ID'])) {
            throw new \Exception('Bad component params');
          }
      
          $blank = $this->registry->getDbHelper('BlanksHelper')->findById($this->component->arParams['BLANK_ID']);
      
          $langID = isset($_SESSION['langID']) ? $_SESSION['langID'] : 1;
          if (isset($_POST['language']) && array_key_exists(intval($_POST['language']), $availableLanguages)) {
            $langID = intval($_POST['language']);
            $_SESSION['langID'] = $langID;
          }
          $this->component->arResult['langID'] = $langID;
      
          $BlankDataSource = new \Ecoplay\Model\BlankDataSource($this->registry->getDbConnect(), $this->component->arParams['BLANK_ID'], $this->component->arParams['PROJECT_ID']);
          $paginationInfo = $BlankDataSource->getPaginationInfo();
          $questionsOnPage = $BlankDataSource->getQuestionsOnPage($paginationInfo["current_page"], 0, $langID);
      
          $groupLink = true;
      
          $sign = 'Респондент Тестовый';
          break;
      
        default:
          throw new \Exception('Unknown component mode: '.$this->component->arParams['MODE']);
          break;
      }
      $this->component->arResult["groupLink"] = $groupLink;
      
      $translator = new \Ecoplay\Model\Translation($this->registry->getDbConnect(), $availableLanguages[$langID]['abbr'], $_SERVER["DOCUMENT_ROOT"].'/lang/', $this->registry);
      $this->component->arResult['translator'] = $translator;
      
      $projectViewSettings = ($context['project']['param_lk']) ? (array)json_decode($context['project']['~param_lk']) : array();
      $this->component->arResult['projectViewSettings'] = $projectViewSettings;
            
      $context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
        'TRANSLATOR'  => $translator,
        'MODE'  => 'blank',
        'MEMEBER_KEY'  => $member['private_lk_access_key'],
        'SETTINGS'  => array_merge($projectViewSettings, array('name' => $member['name'].' '.$member['surname'])),
        'ADDON_CSS' => isset($session['myCSS_path']) ? $session['myCSS_path'] : ($context['project']['myCSS_path'] ?  $context['project']['myCSS_path'] : ''),
        'TEXT'  => /*$translator->translateObject($project['~lk_text'], 'project_lk_text', $project['projectID'], $langID)*/'',
      ));
      
      if (!$finished) {
      
        // вывод бланка
        $this->component->arResult["BlankDataSource"] = &$BlankDataSource;
        $this->component->arResult["pagination_info"] = $paginationInfo;
        $this->component->arResult["blank_view_type"] = $this->component->arParams['MODE'];
        $this->component->arResult['have_hidden'] = count($hiddenQuestionsData['controllIDs']) ? true : false;
      
        $this->component->arResult['hide_assess_hint'] = ($blank && $blank['hide_assess_hint']) ? true : false;
        
        if (isset($projectViewSettings['show_name_in_header']) && $projectViewSettings['show_name_in_header']) {
          $this->component->arResult["assess"] = $BlankDataSource->ocenivaemy['name'].' '.$BlankDataSource->ocenivaemy['surname'];
        }
      
        $this->component->IncludeComponentTemplate("before");
      
        $grouppedQuestions = array(
          'visible'  => $questionsOnPage,
          'hidden'  => $hiddenQuestionsData['hidden'],
        );
        
        # 1. ГРУППИРУЕМ вопросы на блоки
        $questionsBlocks = array();
        $haveRealBlock = false;
        foreach ($grouppedQuestions as $group => $questions) {
          foreach($questions as $question) {
            if ($question['blockID']) {
              $question['visible'] = ($group == 'visible') ? true : false;
              $question['is_controll'] = in_array($question['questionID'], $hiddenQuestionsData['controllIDs']) ? true : false;
              if (!array_key_exists($question['blockID'], $questionsBlocks)) { // такой группы еще нет
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
                $questionsBlocks[$question['blockID']] = $block;
              }
              else { // группа уже есть, проверяем, чтобы подходили под ее условия
                $block = $questionsBlocks[$question['blockID']];
                if ($question['question_type'] == $questionsBlocks[$question['blockID']]['question_type']) { // группировать можно только вопросы одного типа
                  if (in_array($question['question_type'], array('radio', 'checkbox'))) { // идентичность ответов
                    $answersNames = array();
                    foreach ($question['answers'] as $answer) {
                      $answersNames[] = $answer['name'];
                    }
                    $answersString = implode(';', $answersNames);
                    if ($questionsBlocks[$question['blockID']]['answers_string'] == $answersString) {
                      $questionsBlocks[$question['blockID']]['questions'][] = $question;
                      $questionsBlocks[$question['blockID']]['questions_ids'][] = $question['questionID'];
                      $haveRealBlock = true;
                    }
                  }
                  else {
                    $questionsBlocks[$question['blockID']]['questions'][] = $question;
                    $questionsBlocks[$question['blockID']]['questions_ids'][] = $question['questionID'];
                    $haveRealBlock = true;
                  }
                }
              }
            }
          }
        }
      
        if ($haveRealBlock) {
          $this->component->IncludeComponentTemplate("block_script");
        }
      
        # 2. ВЫВОДИМ вопросы
        $doneBlocks = array(); // блоки, которые уже были выведены
      
        foreach ($grouppedQuestions as $group => $questions) {
          foreach($questions as $question) {
      
            $show = false;
             
            if ($question['blockID'] && (count($questionsBlocks[$question['blockID']]['questions']) > 0)
              && in_array($question['questionID'], $questionsBlocks[$question['blockID']]['questions_ids'])) { // вывод в блоке
              if (!in_array($question['blockID'], $doneBlocks)) {
                $show = true;
             
                $contextParams = $this->registry->getModel('Blanks')->getContextParams($context['project'], $session, $context['seance'], $member);
                $blockQuestions = $questionsBlocks[$question['blockID']]['questions'];
                foreach ($blockQuestions as $key => $blockQuestion) {
                  $blockQuestions[$key]['~name'] = $this->registry->getModel('Blanks')->prepareQuestionName($blockQuestion['~name'], $contextParams);
                  
                  foreach ($blockQuestion['answers'] as $aKey => $answer) {
                      $blockQuestions[$key]['answers'][$aKey]['~name'] = $this->registry->getModel('Blanks')->prepareQuestionName($answer['~name'], $contextParams);
                  }
                }
                                
                // вывод блока
                $param_array = Array(
                  "questions" => $blockQuestions,
                  "blockParams" => $questionsBlocks[$question['blockID']]['params'],
                  "mode" => 'block',
                  'errors'  => array(),
                  'values'  => array(),
                  'lang'  => $availableLanguages[$langID]['abbr'],
                  'alternation'  => $context['project']['blank_group_alternation'],
                  'msddAnswers' => $msddAnswers,
                );
               
                foreach ($questionsBlocks[$question['blockID']]['questions_ids'] as $questionID) {
                  if (array_key_exists($questionID, $errors)) {
                    $param_array['errors'][$questionID] = $errors[$questionID];
                  }
                  if (!array_key_exists($questionID, $errors) && array_key_exists($questionID, $answers)) {
                    $param_array['values'][$questionID] = $answers[$questionID];
                  }
                }
                       
                $questionType = $questionsBlocks[$question['blockID']]['question_type'];
                $doneBlocks[] = $question['blockID'];
              }
            }
            else {
              $show = true;
              $questionType = $question["question_type"];
                            
              $contextParams = $this->registry->getModel('Blanks')->getContextParams($context['project'], $session, $context['seance'], $member);
              $question['~name'] = $this->registry->getModel('Blanks')->prepareQuestionName($question['~name'], $contextParams);
              
              foreach ($question['answers'] as $key => $answer) {
                  $question['answers'][$key]['~name'] = $this->registry->getModel('Blanks')->prepareQuestionName($answer['~name'], $contextParams);
              }
              
              $param_array = Array(
                "BlankDataSource" => &$BlankDataSource,
                "question" => $question,
                "question_params" => json_decode($question['~params'], true),
                'error' => array_key_exists($question['questionID'], $errors) ? $errors[$question['questionID']] : false,
                'value' => (!array_key_exists($question['questionID'], $errors) && array_key_exists($question['questionID'], $answers)) ? $answers[$question['questionID']] : false,
                'lang'  => $availableLanguages[$langID]['abbr'],
                'is_controll' => in_array($question['questionID'], $hiddenQuestionsData['controllIDs']) ? true : false,
                'visible'  => ($group == 'visible') ? true : false,
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
                  
                case 'dichotomy':
                    $context['APPLICATION']->IncludeComponent("ecoplay:behavior.questions.dichotomy", "", $param_array);
                    break;
      
                default:
    	            throw new \Exception('При импорте вопроса был задан неизвестный тип вопроса: "'.$questionType.'"');
                  break;
              }
            }
          }
        }
      
        // buttons at the end
  	    $this->component->IncludeComponentTemplate("after");
      }
      else {
        $this->registry->getModel('Blanks')->finishSeance($context['seance']['seanceID']);
         
        $this->component->IncludeComponentTemplate("success");
      }
      
      $context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
        'TRANSLATOR'  => $translator,
        'SETTINGS'  => $projectViewSettings,
      ));
      $this->registry->getDbConnect()->Commit();      
    }
    catch (\Exception $e) {
      $this->component->arResult['error'] = $e->getMessage();
      $this->component->IncludeComponentTemplate("error");
    }
  }
}