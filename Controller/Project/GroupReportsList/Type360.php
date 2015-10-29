<?php 

namespace Ecoplay\Controller\Project\GroupReportsList;
use Ecoplay\Controller\Base as Base;
use Ecoplay\View\Helper as ViewHelper;
use Ecoplay\Helper\Db\ReportsHelper;
use Ecoplay\Helper\Db\TasksHelper;
use Ecoplay\Helper\Db\LogsHelper;

class Type360 extends Base
{
    public function execute($context)
    {
        // проверка прав доступа к разделу
        if (!$this->registry->getModel('Auth')->checkAccess($_SESSION['accesses'], 'group_reports', $context['project']['projectID'])) {
            $this->registry->getModel('Auth')->restrict();
        }
                
        $viewHelper = new ViewHelper();
        
        // генераторы для групповых отчетов
        $generators = $this->registry->getDbHelper('ReportsHelper')->getGeneratorsByProjectTypeAndReportTypes($context['project']['project_type'],
            array(ReportsHelper::REPORT_TYPE_GROUP));
        $this->component->arResult['generators'] = $generators;
        
        $generatorsNames = array();
        foreach ($generators as $generator) {
            $generatorsNames[$generator['report_generatorID']] = $generator['name'];
        }
        
        $cnt = $this->registry->getDbHelper('ReportsHelper')->getGroupReportsCountByProjectIDAndUserID($context['project']['projectID'],
            $context['USER']->GetID());
        $onPage = 100;
        $pagesCnt = ceil($cnt / $onPage);
        $page = ($_GET['PAGE'] && $_GET['PAGE'] <= $pagesCnt) ? intval($_GET['PAGE']) : 1;
        
        $navResult = new \CDBResult();
        $navResult->NavPageCount = ceil($cnt / $onPage);
        $navResult->NavPageNomer = $page;
        $navResult->NavNum = 1;
        $navResult->NavPageSize = $onPage;
        $navResult->NavRecordCount = $cnt;
        
        $context['APPLICATION']->IncludeComponent('ecoplay:system.pagenavigation', '', array(
            'NAV_RESULT' => $navResult,
            'NAV_URL'  => '/projects/'.$context['project']['projectID'].'/groupReports/',
        ));
        
        $reports = $this->registry->getDbHelper('ReportsHelper')->getGroupReportsByProjectIDAndUserID($context['project']['projectID'],
            $context['USER']->GetID(), $page, $onPage);
        $this->component->arResult['cnt'] = count($reports);
        
        $this->component->arResult['table_data'] = $viewHelper->prepareJsonDataForTable($reports, array('ID', 'dt', 'report_generatorID'), array(),
            array('report_generatorID' => $generatorsNames));
        
        // создаем таск на генерацию
        if (isset($_POST['generator']) && array_key_exists($_POST['generator'], $generators)) {
        
            // Создаем задачу            
            $taskID = $this->registry->getDbHelper('TasksHelper')->addTask(array(
                'name'  => '',
                'type'  => TasksHelper::TASK_TYPE_GROUP_REPORT,
                'params'  => json_encode(array('projectID' => $context['project']['projectID'], 'generatorID' => $_POST['generator'])),
                'userID'  => $context['USER']->GetID(),
                'status'  => TasksHelper::TASK_STATUS_NEW,
                'dt_created'  => date('d-m-Y H:i:s'),
            ));
                    
            $this->registry->getModel('ActionsLogger')->log($context['USER']->GetID(), 'task', $taskID, LogsHelper::ACTION_TYPE_ADD, null);
        
            $this->component->arResult['reportAdded'] = true;
        }
        
        $context['APPLICATION']->SetPageProperty('pageMainTitle', 'Групповые отчёты');
        $links = array(
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
                'title' => 'Групповые отчёты',
            ),
        );
        $context['APPLICATION']->SetPageProperty('navigation', $viewHelper->generateNavigation($links));
        
        
        $this->component->IncludeComponentTemplate();
    }
}