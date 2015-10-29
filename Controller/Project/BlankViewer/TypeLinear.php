<?php 

namespace Ecoplay\Controller\Project\BlankViewer;

use Ecoplay\Controller\EventListener;

class TypeLinear extends BaseBlank
{
  protected $wasBack = false;
  
  public function execute($context)
  { 
    try {
      $this->registry->getDbConnect()->StartTransaction();
      
      $this->component->arResult['project'] = $context['project'];
      
      // определяем сессию, в которой заполняется анкета
      $this->detectSession($context);
            
      $this->member = $this->registry->getDbHelper('MembersHelper')->findMemberByRespondentID($context['seance']['respondentID']);
      $groupKey = ($this->member['stat2_group_keyID']) ? $this->registry->getDbHelper('TestsHelper')->findGroupKeyByID($this->member['stat2_group_keyID']) : false;
      
      $this->registry->getModel('Stat')->addHit($context['seance']['sessionID'], $this->member['projects_memberID']);
      
      if ($groupKey && (!isset($context['anonym']) || !$context['anonym']) && !$groupKey['is_registration_available']) {
        LocalRedirect('/a/'.$groupKey['access_key'].'/');
        $this->registry->getDbConnect()->Commit();
      }
      
      if (isset($_GET['first']) && $context['project']['allow_edit_seance_answers_at_complete']) {
        if (!$groupKey || $groupKey['is_registration_available']) {        
          $this->registry->getDbHelper('SeancesHelper')->editSeance($context['seance']['seanceID'], array('last_screenID' => 0));           
          $this->registry->getDbConnect()->Commit();
          LocalRedirect('/s/'.$context['seance']['seance_key'].'/');
        }
      }
      
      // сохраняем ответы на вопросы
      if ($this->component->arParams['MODE'] == 'key' && $_POST["action"] == "save_answers") {
        
        // возможно просто пытаемся вернуться на предыдущую страницу
        if (isset($_POST['back']) && $_POST['back']) {
          if ($_SESSION['form_token'] == @$_POST['token'] &&
              ($context['project']['allow_edit_seance_answers'] || $context['project']['allow_edit_seance_answers_at_complete'])) {
            // пробуем ответить на вопросы со страницы
            $this->saveAnswers($context, true);
            $this->backToPrevScreen($context);
          }
        }
        else {
          $this->saveAnswers($context);
        }
      }
      
      $availableLanguages = $this->registry->getDbHelper('TranslationHelper')->getLanguagesByIDs(json_decode($context['project']['~languages']));
      $this->component->arResult['languages'] = $availableLanguages;
      
      $groupLink = false;
      $this->hiddenQuestionsData = array(); // скрытые вопросы, которые могут быть отображены при определенных ответах на другие вопросы с той же страницы
            
      $this->getQuestions($context, $availableLanguages);
      
      $translator = new \Ecoplay\Model\Translation($this->registry->getDbConnect(), $availableLanguages[$this->langID]['abbr'], $_SERVER["DOCUMENT_ROOT"].'/lang/', $this->registry);
      $this->component->arResult['translator'] = $translator;      
      
      $projectViewSettings = ($context['project']['param_lk']) ? (array)json_decode($context['project']['~param_lk']) : array();
      $this->component->arResult['projectViewSettings'] = $projectViewSettings;
      
      // проверяем статус группового ключа      
      $memberName = $this->member['name'].' '.$this->member['surname'];
      $this->component->arResult["disable_breadcrumbs"] = false;
      
      if ($this->member['stat2_group_keyID']) {        
        if ($groupKey['status'] == \Ecoplay\Helper\Db\TestsHelper::GROUP_KEY_STATUS_DISABLED) {
          $context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
            'TRANSLATOR'  => $translator,
            'MODE'  => 'blank',
            'MEMEBER_KEY'  => null,
            'SETTINGS'  => $projectViewSettings,
            'ADDON_CSS' => isset($this->session['myCSS_path']) ? $this->session['myCSS_path'] : ($context['project']['myCSS_path'] ?  $context['project']['myCSS_path'] : ''),
            'TEXT'  => '',
          ));
          
          $this->component->IncludeComponentTemplate('linear/disabled', '/bitrix/components/ecoplay/behavior.blank.viewer/templates/.default');
          
          $context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
            'TRANSLATOR'  => $translator,
            'SETTINGS'  => $projectViewSettings,
          ));
          $this->registry->getDbConnect()->Commit();
          die();
        }
        
        // для перехода по ключу всегда скрываем крошки
        $this->component->arResult["disable_breadcrumbs"] = true;
        
        /*if (!$groupKey['is_registration_available']) {
          $memberName = 'Опрос';          
        }*/
      } 
      
      // получаем инфу о вопросах      
      $this->component->arResult["groupLink"] = $groupLink;
      
      $this->component->arResult["memberName"] = $memberName;      
      
      $context['APPLICATION']->IncludeComponent("ecoplay:front.header", '', array(
        'TRANSLATOR'  => $translator,
        'MODE'  => 'blank',
        'MEMEBER_KEY'  => $this->member['private_lk_access_key'],
        'SETTINGS'  => array_merge($projectViewSettings, array('name' => $memberName)),        
        'ADDON_CSS' => isset($this->session['myCSS_path']) ? $this->session['myCSS_path'] : ($context['project']['myCSS_path'] ?  $context['project']['myCSS_path'] : ''),
        'TEXT'  => '',
      ));
      
      if (!$this->finished) {
      
        // вывод бланка
        $this->component->arResult["BlankDataSource"] = &$this->BlankDataSource;
        $this->component->arResult["pagination_info"] = $this->paginationInfo;
        $this->component->arResult["blank_view_type"] = $this->component->arParams['MODE'];
        $this->component->arResult['have_hidden'] = count($this->hiddenQuestionsData['controllIDs']) ? true : false;
      
        $this->component->IncludeComponentTemplate("linear/before", '/bitrix/components/ecoplay/behavior.blank.viewer/templates/.default');
      
        # 1. ГРУППИРУЕМ вопросы на блоки
        $this->groupQuestions($context);
        if ($this->haveRealBlock) {
          $this->component->IncludeComponentTemplate("block_script", '/bitrix/components/ecoplay/behavior.blank.viewer/templates/.default');
        }
      
        # 2. ВЫВОДИМ вопросы
        $this->showQuestions($context, 0, $availableLanguages);
      
        // токен для формы, чтобы точно знать что обрабатываем
        $token = md5(uniqid(rand(), true));
        $_SESSION['form_token'] = $token;
        $this->component->arResult['token'] = $token;        
        
        // buttons at the end
        $this->component->IncludeComponentTemplate('linear/after', '/bitrix/components/ecoplay/behavior.blank.viewer/templates/.default');
      }
      else {
        // завершение сеанса
        $this->registry->getModel('Blanks')->finishLinearSeance($context['seance']);
        
        // кидаем событие завершения бланка, возможно кто-то слушает        
        $eventListener = new EventListener($this->registry, $context['project'], $_SERVER["DOCUMENT_ROOT"].'/');
        $eventListener->executeEvent('PollLinear_BlankCompleted', array(
          'respondentID'  => $context['seance']['respondentID'],
        ), true);
         
        $this->component->IncludeComponentTemplate("linear/success", '/bitrix/components/ecoplay/behavior.blank.viewer/templates/.default');
      }
      
      $context['APPLICATION']->IncludeComponent("ecoplay:front.footer", '', array(
        'TRANSLATOR'  => $translator,
        'SETTINGS'  => $projectViewSettings,
      ));
      $this->registry->getDbConnect()->Commit();
    }
    catch (\Exception $e) {
      $this->component->arResult['error'] = $e->getMessage();
      $this->component->IncludeComponentTemplate("error", '/bitrix/components/ecoplay/behavior.blank.viewer/templates/.default');
    }
  }
  
  /**
   * Возврат на предыдущий экран
   * @param array $context
   */
  protected function backToPrevScreen(&$context)
  {
    // ищем предыдущий скрин
    $screen = $this->registry->getDbHelper('BlanksHelper')->getBlankPrevScreen($context['seance']['blankID'], $context['seance']['last_screenID']);
        
    $context['seance']['last_screenID'] = $screen ? $screen['screenID'] : 0;
    $this->registry->getDbHelper('SeancesHelper')->editSeance($context['seance']['seanceID'], array('last_screenID' => $context['seance']['last_screenID']));
    
    $this->wasBack = true;
  }
}