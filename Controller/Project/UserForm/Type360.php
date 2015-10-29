<?php 

namespace Ecoplay\Controller\Project\UserForm;
use Ecoplay\Controller\Base as Base;
use Ecoplay\Helper\Db\LogsHelper;
use Ecoplay\Helper\Db\ClientsHelper;
use Ecoplay\Helper\Db\UsersHelper;
use Ecoplay\View\Helper as ViewHelper;

class Type360 extends Base
{
    public function execute($context)
    {
        // проверка прав доступа к разделу
        if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'project_edit', $context['project']['projectID'])) {
            $this->registry->getModel('Auth')->restrict();
        }
        
        $context['APPLICATION']->AddHeadScript('/js/forms.js'); 
        
        
        // подгружаем список пользователей, которых можно назначить админом (по клиенту проекта + ЭКОПСИ)
        if ($this->component->arParams['ACTION_MODE'] == 'add') {
            $availableUsers = $this->registry->getDbHelper('UsersHelper')->getUsersForAdminCreation($context['project']['projectID'],
                array($context['project']['clientID'], ClientsHelper::ECOPSY_CLIENT_ID));
            $this->component->arResult['availableUsers'] = $availableUsers;
        }
        else {
            $userID = (int)$_GET["USER_ID"];
            $user = $this->registry->getDbHelper('UsersHelper')->findById($userID);
            if (!$user) {
                LocalRedirect('/projects/settings/users/');
            }
            $this->component->arResult['user'] = $user;
        
            $projectAdmin = $this->registry->getDbHelper('UsersHelper')->findAdminByUserId($userID);
            if (!$projectAdmin) {
                LocalRedirect('/projects/settings/users/');
            }
        
            $this->component->arResult['form_data'] = $projectAdmin;
        
            if ($projectAdmin['access_type'] == UsersHelper::USER_ACCESS_TYPE_FLAGS) {  // получаем установленные группы
                $sessions = $this->registry->getDbHelper('UsersHelper')->getTestProjectUserSessions($userID, $context['project']['projectID']);
                if ($sessions && count($sessions)) {
                    foreach ($sessions as $session) {
                        $this->component->arResult['form_data']['group_'.$session['sessionID']] = 1;
                    }
                }
            }
        }
        
        $viewHelper = new ViewHelper();
        $context['APPLICATION']->SetPageProperty('pageMainTitle', 
            (($this->component->arParams['ACTION_MODE'] == 'add') ? 'Добавление' : 'Редактирование').' администратора проекта');
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
                'link' => '/projects/'.$context['project']['projectID'].'/settings/',
                'title' => 'Настройки',
            ),
            4  => array(
                'link' => '/projects/'.$context['project']['projectID'].'/settings/users/',
                'title' => 'Администраторы',
            ),
        );
        if ($this->component->arParams['ACTION_MODE'] == 'add') {
            $navigationLinks[5] = array(
                'title' => 'Добавление',
            );
        }
        else {
            $navigationLinks[3] = array(
                'link' => '/projects/'.$context['project']['projectID'].'/settings/users/edit/'.$userID.'/',
                'title' => $user['LOGIN'],
            );
            $navigationLinks[4] = array(
                'title' => 'Редактирование',
            );
        }
        
        $context['APPLICATION']->SetPageProperty('navigation', $viewHelper->generateNavigation($navigationLinks));
        
        if ($this->component->arParams['ACTION_MODE'] == 'add' && !count($this->component->arResult['availableUsers'])) { // нет незадействованных пользователей
            $this->component->IncludeComponentTemplate('no_users');
        }
        else {
            
            // типы прав
            $this->component->arResult['rightsTypes'] = array(
                UsersHelper::USER_ACCESS_TYPE_FULL  => 'Полные',
                UsersHelper::USER_ACCESS_TYPE_FLAGS  => 'Выборочно',
            );
            $this->component->arResult['exType'] = UsersHelper::USER_ACCESS_TYPE_FLAGS;
            $this->component->arResult['rights'] = array(
                'blanks'  => 'к бланкам',
                'members'  => 'к участникам',
                'export'  => 'к выгрузке данных',
                'reports'  => 'к отчетам',
                'group_reports'  => 'к групповым отчетам',
                'seances'  => 'к сеансам заполнения',
                'structure'  => 'к структуре компании',
                'competences'  => 'к моделям компетенции',
                'imports'  => 'к импорту данных',
                //'privileged'  => 'привилегированный',
            );
            // группы проекта
            $this->component->arResult['groups'] = ($context['project']['project_type'] == 'testing') ?
                $this->registry->getDbHelper('TestsHelper')->getSessionsByProjectID($context['project']['projectID']) :
                $this->registry->getDbHelper('ProjectsHelper')->getSessionsByProjectID($context['project']['projectID']);
        
            // Обработка полученных данных
            $errors = array();
            if (isset($_POST['send'])) {
                if (!array_key_exists($_POST['rights_type'], $this->component->arResult['rightsTypes'])) {
                    $errors['rights_type'] = 'identifier';
                }
                elseif ($_POST['rights_type'] == UsersHelper::USER_ACCESS_TYPE_FLAGS && count($_POST) <= 3) { // TODO: поумней способ проверки
                    $errors['rights_type'] = 'identifier';
                    $this->component->arResult['form_data']['access_type'] = $_POST['rights_type'];
                }
                else {        
                    if ($this->component->arParams['ACTION_MODE'] == 'add') {
                        $userData = array(
                            'userID'  => intval($_POST['user']),
                            'projectID'  => $context['project']['projectID'],
                            'access_type'  => $_POST['rights_type'],
                        );
                        // флаги прав
                        if ($_POST['rights_type'] == UsersHelper::USER_ACCESS_TYPE_FLAGS) {
                            foreach ($_POST as $key => $value) {
                                if (array_key_exists($key, $this->component->arResult['rights'])) {
                                    $userData['at_'.$key] = 1;
                                }
                            }
                        }
                        $projectUserID = $this->registry->getDbHelper('UsersHelper')->addProjectUser($userData);
                        $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'project_user', $projectUserID,
                            LogsHelper::ACTION_TYPE_ADD, null);
        
                        // группы
                        if ($_POST['rights_type'] ==UsersHelper::USER_ACCESS_TYPE_FLAGS) {
                            foreach ($_POST as $key => $value) {
                                if (strpos($key, 'group_') === 0) {
                                    $sessionID = intval(substr($key, 6));
                                    $projectUserSessionID = $this->registry->getDbHelper('UsersHelper')->addProjectUserSession(array(
                                        'userID'  => intval($_POST['user']),
                                        'sessionID'  => $sessionID,
                                    ));
                                    $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'project_user_session', $projectUserSessionID,
                                        LogsHelper::ACTION_TYPE_ADD, null);
                                }
                            }
                        }
        
                        LocalRedirect($this->component->arParams['RETURN_URL']);
                    }
                    else {
                        if ($_POST['rights_type'] == UsersHelper::USER_ACCESS_TYPE_FULL
                            && $projectAdmin['access_type'] == UsersHelper::USER_ACCESS_TYPE_FLAGS) { // перешли с выборочного на полного
                            $userData = array('access_type' => UsersHelper::USER_ACCESS_TYPE_FULL);
                            foreach ($this->component->arResult['rights'] as $right => $name) {
                                $userData['at_'.$right] = 0;
                            }
                            $this->registry->getDbHelper('UsersHelper')->editProjectUser($projectAdmin['ID'], $userData); // обновляем самого админа
                            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'project_user', $projectAdmin['ID'],
                                LogsHelper::ACTION_TYPE_CHANGE, $projectAdmin);
                            $this->registry->getDbHelper('UsersHelper')->deleteTestProjectUserSessions($projectAdmin['userID'], $context['project']['projectID']);
                            if ($sessions && count($sessions)) {
                                foreach ($sessions as $session) {
                                    $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'project_user_session', $session['ID'],
                                        LogsHelper::ACTION_TYPE_DELETE, $session);
                                }
                            }
        
                        }
                        elseif ($_POST['rights_type'] == UsersHelper::USER_ACCESS_TYPE_FLAGS) { // флаги всегда обновляем                            
                            $userData = array('access_type' => UsersHelper::USER_ACCESS_TYPE_FLAGS);
                            foreach ($this->component->arResult['rights'] as $right => $name) { // обнуляем все
                                $userData['at_'.$right] = 0;
                            }
                            foreach ($_POST as $key => $value) { // задаем переанные
                                if (array_key_exists($key, $this->component->arResult['rights'])) {
                                    $userData['at_'.$key] = 1;
                                }
                            }
                            $this->registry->getDbHelper('UsersHelper')->editProjectUser($projectAdmin['ID'], $userData); // обновляем самого админа
                            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'project_user', $projectAdmin['ID'],
                                LogsHelper::ACTION_TYPE_CHANGE, $projectAdmin);
        
                            $this->registry->getDbHelper('UsersHelper')->deleteTestProjectUserSessions($projectAdmin['userID'],
                                $context['project']['projectID']); // очищаем старые ссылки на группы
                            if ($sessions && count($sessions)) {
                                foreach ($sessions as $session) {
                                    $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'project_user_session',
                                        $session['ID'], LogsHelper::ACTION_TYPE_DELETE, $session);
                                }
                            }
                            foreach ($_POST as $key => $value) { // добавляем новые
                                if (strpos($key, 'group_') === 0) {
                                    $sessionID = intval(substr($key, 6));
                                    $projectUserSessionID = $this->registry->getDbHelper('UsersHelper')->addProjectUserSession(array(
                                        'userID'  => $projectAdmin['userID'],
                                        'sessionID'  => $sessionID,
                                    ));
                                    $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'project_user_session',
                                        $projectUserSessionID, LogsHelper::ACTION_TYPE_ADD, null);
                                }
                            }
                        }
        
                        LocalRedirect($this->component->arParams['RETURN_URL']);
                    }
                }
            }
            $this->component->arResult['errors'] = $viewHelper->prepareJsonErrors($errors);
            
            $this->component->IncludeComponentTemplate();
        }
    }
}