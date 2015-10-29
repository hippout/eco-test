<?php 

namespace Ecoplay\Events;

class Auth
{
  public function afterLogin($arUser)
  {
    global $DB;
    $usersHelper = new \Ecoplay\Helper\Db\UsersHelper($DB);
    
    // ищем группы пользователя, чтобы определить его статус
    $userGroups = $usersHelper->getUserGroups($arUser['user_fields']['ID']);
    $isAdmin = false;
    foreach ($userGroups as $group) {
      if ($group['GROUP_ID'] == \Ecoplay\Helper\Db\UsersHelper::USER_GROUP_ADMIN) {
        $isAdmin = true;
        break;
      }
    }
    
    if ($isAdmin) {
      $_SESSION['accesses']['is_admin'] = 1;
    }
    else {
      $_SESSION['accesses']['is_admin'] = 0;
      
      // ищем пользователя в админах проектов
      $projectsAdmin = $usersHelper->getUserProjects($arUser['user_fields']['ID']);      
      $_SESSION['accesses']['user_projects'] = array();
      if ($projectsAdmin && count($projectsAdmin)) {
        foreach ($projectsAdmin as $adminData) {
          if ($adminData['active']) {
            if ($adminData['access_type'] == \Ecoplay\Helper\Db\UsersHelper::USER_ACCESS_TYPE_FULL) {
              $_SESSION['accesses']['user_projects'][$adminData['projectID']] = array(
                'is_full'  => 1,
              );
            }
            else {
              $_SESSION['accesses']['user_projects'][$adminData['projectID']] = array(
                'is_full'  => 0,
                'at_blanks'  => $adminData['at_blanks'],
                'at_members'  => $adminData['at_members'],
                'at_export'  => $adminData['at_export'],
                'at_reports'  => $adminData['at_reports'],
                'at_seances'  => $adminData['at_seances'],
                'at_structure'  => $adminData['at_structure'],
                'at_competences'  => $adminData['at_competences'],
                'groups'  => array(),
              );
              
              // данные о группах
              $sessions = $usersHelper->getProjectUserSessions($arUser['user_fields']['ID'], $adminData['projectID']);
              if ($sessions && count($sessions)) {
                foreach ($sessions as $session) {
                  $_SESSION['accesses']['user_projects'][$adminData['projectID']]['groups'][] = $session['sessionID'];
                }
              }
            }
          }
        }
      }
    }    
  }
}