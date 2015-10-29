<?php 

namespace Ecoplay\Controller\Project\ProjectEmails;
use Ecoplay\Controller\Base as Base;

abstract class BaseEmails extends Base
{
  abstract public function getType();
  
  public function execute($context)
  {
    $context['APPLICATION']->AddHeadScript('/js/forms.js');
        
    // проверка прав доступа к разделу    
    if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'project_edit', $context['project']['projectID'])) {
      $this->registry->getModel('Auth')->restrict();
    }
        
    // выбираем языки проекта
    $projectLanguagesIds = json_decode($context['project']['~languages']);    
    $languages = $this->registry->getDbHelper('TranslationHelper')->getSortedLanguages();
    $projectLanguages = array();
    foreach ($languages as $language) {
      if (in_array($language['langID'], $projectLanguagesIds)) {
        $projectLanguages[] = $language;
      }
    }
    $this->component->arResult['projectLanguages'] = $projectLanguages;
    
    switch ($this->getType()) {
      case '360':
    
        $events = array(
          'fi'  => array(
            'name'  => 'MailEvent_360_FirstInvite',
            'title'  => 'Первое уведомление респондента',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#ASSESS#' => 'Имя Фамилия оцениваемого (или список при групповой отправке)',
              '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              '#RESPONDENT_LK_LINK#' => 'Ссылка на ЛК респондента',
              '#RESPONDENT_FILL_LINK#' => 'Ссылка на опросник респондента (недоступно при групповой отправке)',
            ),
          ),
          'nrn'  => array(
            'name'  => 'MailEvent_360_NextRespondentNotification',
            'title'  => 'Последующее уведомление респондента',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#ASSESS#' => 'Имя Фамилия оцениваемого (или список при групповой отправке)',
              '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              '#RESPONDENT_LK_LINK#' => 'Ссылка на ЛК респондента',
              '#RESPONDENT_FILL_LINK#' => 'Ссылка на опросник респондента (недоступно при групповой отправке)',
            ),
          ),
          'asr'  => array(
            'name'  => 'MailEvent_360_AssessSelectRespondents',
            'title'  => 'Информирование оцениваемого о выборе респондентов',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#ASSESS#' => 'Имя Фамилия оцениваемого',
              '#ASSESS_LK_LINK#' => 'Ссылка на ЛК оцениваемого',
            ),
          ),
          'rsa'  => array(
            'name'  => 'MailEvent_360_RespondentSelectAssess',
            'title'  => 'Информирование респондента о выборе оцениваемых',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#ASSESS#' => 'Имя Фамилия оцениваемого',
              '#ASSESS_LK_LINK#' => 'Ссылка на ЛК оцениваемого',
            ),
          ),
          'rasr'  => array(
            'name'  => 'MailEvent_360_AssessSelectRespondentsRemind',
            'title'  => 'Напоминание оцениваемому о выборе респондентов',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#ASSESS#' => 'Имя Фамилия оцениваемого',
              '#ASSESS_LK_LINK#' => 'Ссылка на ЛК оцениваемого',
            ),
          ),
          'rrsa'  => array(
            'name'  => 'MailEvent_360_RespondentSelectAssessRemind',
            'title'  => 'Напоминание респонденту о выборе оцениваемых',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#ASSESS#' => 'Имя Фамилия оцениваемого',
              '#ASSESS_LK_LINK#' => 'Ссылка на ЛК оцениваемого',
            ),
          ),
          'mr'  => array(
            'name'  => 'MailEvent_360_MemeberReportNotification',
            'title'  => 'Информирование участника о доступности отчета',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#ASSESS#' => 'Имя Фамилия оцениваемого',
              '#REPORT_LINK#' => 'Ссылка на отчет',
            ),
          ),
          'dhr'  => array(
            'name'  => 'MailEvent_360_DirectHeadReportNotification',
            'title'  => 'Информирование руководителя о доступности отчета',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#ASSESS#' => 'Имя Фамилия руководителя',
              '#REPORT_LINK#' => 'Ссылка на отчет',
              '#RESPONDENT_LK_LINK#' => 'Ссылка на ЛК респондента',
            ),
          ),
          'dn'  => array(
              'name'  => 'MailEvent_360_DirectHeadNotification',
              'title'  => 'Информирование руководителя о необходимости подтвердить списки респондентов по подчиненным',
              'replaces'  => array(
                  '#EMAIL_FROM#'  => 'email адрес отправителя письма',
                  '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
                  '#DIRECT_HEAD#' => 'Имя Фамилия руководителя',
                  '#ASSESS#' => 'Имя Фамилия подчиненного',
                  '#LINK#' => 'Ссылка на страницу подтверждения',
              ),
          ),
        );
    
        break;
    
      case 'testing':
    
        $events = array(
          'fi'  => array(
            'name'  => 'MailEvent_Testing_FirstInvite',
            'title'  => 'Первое уведомление респондента',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              '#RESPONDENT_LK_LINK#' => 'Ссылка на ЛК респондента',
            ),
          ),
          'gkfi'  => array(
            'name'  => 'MailEvent_Testing_GroupKey_FirstInvite',
            'title'  => 'Уведомление о групповой ссылке',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              '#GROUP_KEY_LINK#' => 'Групповая ссылка',
            ),
          ),
          'gknrn'  => array(
            'name'  => 'MailEvent_Testing_GroupKey_Notification',
            'title'  => 'Напоминание о групповой ссылке',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              '#GROUP_KEY_LINK#' => 'Групповая ссылка',
            ),
          ),
          'mr'  => array(
              'name'  => 'MailEvent_Testing_MailResend',
              'title'  => 'Напоминание о заполнении',
              'replaces'  => array(
                  '#EMAIL_FROM#'  => 'email адрес отправителя письма',
                  '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
                  '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
                  '#RESPONDENT_LK_LINK#' => 'Ссылка на ЛК респондента',
              ),
          ),
          'rr'  => array(
              'name'  => 'MailEvent_Testing_RespondentReport',
              'title'  => 'Письмо с последним отчетом тестируемого',
              'replaces'  => array(
                  '#EMAIL_FROM#'  => 'email адрес отправителя письма',
                  '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
                  '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              ),
          ),
        );
    
        break;
    
      case 'linear':
        $events = array(
          'fi'  => array(
            'name'  => 'MailEvent_Linear_FirstInvite',
            'title'  => 'Первое уведомление респондента',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              '#RESPONDENT_LK_LINK#' => 'Ссылка на ЛК респондента',
              '#SEANCE_LINK#' => 'Ссылка на анкету',
            ),
          ),
          'nrn'  => array(
            'name'  => 'MailEvent_Linear_NextRespondentNotification',
            'title'  => 'Последующее уведомление респондента',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              '#RESPONDENT_LK_LINK#' => 'Ссылка на ЛК респондента',
              '#SEANCE_LINK#' => 'Ссылка на анкету',
            ),
          ),
          'gkfi'  => array(
            'name'  => 'MailEvent_Linear_GroupKey_FirstInvite',
            'title'  => 'Уведомление о групповой ссылке',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              '#GROUP_KEY_LINK#' => 'Групповая ссылка',
            ),
          ),
          'gknrn'  => array(
            'name'  => 'MailEvent_Linear_GroupKey_Notification',
            'title'  => 'Напоминание о групповой ссылке',
            'replaces'  => array(
              '#EMAIL_FROM#'  => 'email адрес отправителя письма',
              '#EMAIL_TO#'  => 'email адрес на который отправляется письмо',
              '#RESPONDENT#' => 'Имя Фамилия того, кому отправляем письмо',
              '#GROUP_KEY_LINK#' => 'Групповая ссылка',
            ),
          ),
        );
        break;
    }
    
    
    $this->component->arResult['events'] = $events;
    
    // ищем шаблоны проекта
    $projectTemplatesSrc = $this->registry->getDbHelper('EmailHelper')->getProjectTemplates($context['project']['projectID']);
    
    $projectTemplates = array();
    foreach ($projectTemplatesSrc as $template) {
      // определяем ключ ивента
      $ek = null;
      foreach ($events as $key => $event) {
        if ($event['name'] == $template['event']) {
          $ek = $key;
          break;
        }
      }
      if ($ek) {
        $templateData = array(
          'from'  => $template['from'],
          'to'  => $template['to'],
          'is_html'  => $template['is_html'],
          'id'  => $template['ID'],
          'lang'  => array(),
        );
    
        foreach ($projectLanguages as $language) {
          if ($language['is_default']) {
            $templateData['lang'][$language['abbr']] = array(
              'theme'  => $template['theme'],
              'body'  => $template['body'],
            );
          }
          else {
            $translation = $this->registry->getDbHelper('TranslationHelper')->findTranslation('email_theme', $template['ID'], $language['langID']);
            if ($translation) {
              $templateData['lang'][$language['abbr']]['theme'] = $translation['value'];
              $templateData['lang'][$language['abbr']]['theme_id'] = $translation['translationID'];
            }
            $translation = $this->registry->getDbHelper('TranslationHelper')->findTranslation('email_body', $template['ID'], $language['langID']);
            if ($translation) {
              $templateData['lang'][$language['abbr']]['body'] = $translation['value'];
              $templateData['lang'][$language['abbr']]['body_id'] = $translation['translationID'];
            }
          }
        }
        $projectTemplates[$ek] = $templateData;
      }
    }
    $this->component->arResult['projectTemplates'] = $projectTemplates;
    
    $errors = array();
    if (isset($_POST['send'])) {
      $validations = array();
      $required = array();
      foreach ($events as $ek => $event) {
        $validations[$ek.'_from'] = 'anything';
        $validations[$ek.'_to'] = 'anything';
        $required[] = $ek.'_from';
        $required[] = $ek.'_to';
      }
    
      $validator = new \Ecoplay\Form\Validator($validations, $required, array());
    
      if (!$validator->validate($_POST)) {
        $errors = $validator->getErrors();
      }
      else {        
        foreach ($events as $ek => $event) { // заполняем шаблоны для каждого события
          $translations = array();
          $templateID = 0;
    
          if (array_key_exists($ek, $projectTemplates)) { // шаблон уже есть в базе, обновляем его
            $templateData = array(
              'from'  => trim($_POST[$ek.'_from']),
              'to'  => trim($_POST[$ek.'_to']),
              'is_html'  => isset($_POST[$ek.'_is_html']) ? 1 : 0,
            );
    
            // обрабатываем языки
            foreach ($projectLanguages as $language) {
              if ($language['is_default']) {
                $templateData['theme']  = trim($_POST[$ek.'_'.$language['abbr'].'_theme']);
                $templateData['body']  = trim($_POST[$ek.'_'.$language['abbr'].'_body']);
              }
              else {
                $translations[$language['langID']] = array(
                  'theme'  => trim($_POST[$ek.'_'.$language['abbr'].'_theme']),
                  'body'  => trim($_POST[$ek.'_'.$language['abbr'].'_body']),
                  'lang'  => $language['abbr'],
                );
              }
            }
    
            $this->registry->getDbHelper('EmailHelper')->editProjectTemplate($projectTemplates[$ek]['id'], $templateData);
            $templateID = $projectTemplates[$ek]['id'];
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'email_template', $templateID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_CHANGE, $projectTemplatesSrc[$templateID]);
          }
          else { // шаблона в базе нет, добавляем его
            $templateData = array(
              'projectID'  => $context['project']['projectID'],
              'event'  => $event['name'],
              'from'  => trim($_POST[$ek.'_from']),
              'to'  => trim($_POST[$ek.'_to']),
              'is_html'  => isset($_POST[$ek.'_is_html']) ? 1 : 0,
            );
    
            // обрабатываем языки
            foreach ($projectLanguages as $language) {
              if ($language['is_default']) {
                $templateData['theme']  = trim($_POST[$ek.'_'.$language['abbr'].'_theme']);
                $templateData['body']  = trim($_POST[$ek.'_'.$language['abbr'].'_body']);
              }
              else {
                $translations[$language['langID']] = array(
                  'theme'  => trim($_POST[$ek.'_'.$language['abbr'].'_theme']),
                  'body'  => trim($_POST[$ek.'_'.$language['abbr'].'_body']),
                  'lang'  => $language['abbr'],
                );
              }
            }
    
            $templateID = $this->registry->getDbHelper('EmailHelper')->addProjectTemplate($templateData);
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'email_template', $templateID, \Ecoplay\Helper\Db\LogsHelper::ACTION_TYPE_ADD, null);
          }
    
          foreach ($translations as $langID => $translation) {
            if ($translation['theme']) {
              $translationData = array(
                'objectType'  => 'email_theme',
                'objectID'  => $templateID,
                'langID'  => $langID,
                'value'  => $translation['theme'],
                'projectID'  => $context['project']['projectID'],
              );
              if ($projectTemplates[$ek]['lang'][$translation['lang']]['theme_id']) {
                $this->registry->getDbHelper('TranslationHelper')->editTranslation($projectTemplates[$ek]['lang'][$translation['lang']]['theme_id'], $translationData);
              }
              else {
                $this->registry->getDbHelper('TranslationHelper')->addTranslation($translationData);
              }
            }
            elseif ($projectTemplates[$ek]['lang'][$translation['lang']]['theme_id']) { // удаляем перевод
              $this->registry->getDbHelper('TranslationHelper')->deleteTranslationByID($projectTemplates[$ek]['lang'][$translation['lang']]['theme_id']);
            }
    
    
            if ($translation['body']) {
              $translationData = array(
                'objectType'  => 'email_body',
                'objectID'  => $templateID,
                'langID'  => $langID,
                'value'  => $translation['body'],
                'projectID'  => $context['project']['projectID'],
              );
              if ($projectTemplates[$ek]['lang'][$translation['lang']]['body_id']) {
                $this->registry->getDbHelper('TranslationHelper')->editTranslation($projectTemplates[$ek]['lang'][$translation['lang']]['body_id'], $translationData);
              }
              else {
                $this->registry->getDbHelper('TranslationHelper')->addTranslation($translationData);
              }
            }
            elseif ($projectTemplates[$ek]['lang'][$translation['lang']]['body_id']) { // удаляем перевод
              $this->registry->getDbHelper('TranslationHelper')->deleteTranslationByID($projectTemplates[$ek]['lang'][$translation['lang']]['body_id']);
            }
    
          }
        }
        
        if (!isset($_GET['TYPE']) || !$_GET['TYPE']) {
            LocalRedirect('/projects/'.$context['project']['projectID'].'/settings/email/');
        }
        else {
            LocalRedirect('/projects/'.$context['project']['projectID'].'/settings/'.$_GET['TYPE'].'/email/');
        }
      }
    }
    
    $viewHelper = new \Ecoplay\View\Helper();
    $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Почтовые шаблоны');
    $navigationLinks = array(
      0  => array(
        'link' => '/',
        'title' => 'Главная',
      ),
      1  => array(
        'link' => '/projects/',
        'title' => 'Проекты',
      ),
      2  => array(
        'link' => '/projects/'.$context['project']['projectID'].'/continuing/stat/',
        'title' => $context['project']['project_name'],
      ),
      3  => array(
        'title'  => 'Почтовые шаблоны'
      )
    );
    $context['APPLICATION']->SetPageProperty('navigation', $viewHelper->generateNavigation($navigationLinks));
    
    $this->component->arResult['errors'] = $viewHelper->prepareJsonErrors($errors);
    
    $this->component->IncludeComponentTemplate();
  }
}