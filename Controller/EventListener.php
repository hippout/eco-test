<?php 
/**
 * EventListner
 *
 * Обработчик событий
 *
 * @author Илья Петров hippout@gmail.com
 * @version 1.0.1
 */

namespace Ecoplay\Controller;

class EventListener
{ 
  /**
   * Ссылка на реестр
   * @var Registry
   */
  protected $registry = null;
  
  /**
   * Ссылка на проект
   * @var array
   */
  protected $project = null;
  
  /**
   * Корень к библиотеке Ecoplay
   * @var string
   */
  protected $rootPath = '';
  
  /**
   * Конструктор класса, инициализирует реестр и проект
   *
   * @param Registry $registry - реестр
   * @param array $project
   * @param string $rootPath
   */
  public function __construct(Registry $registry, $project, $rootPath)
  {
    $this->registry = $registry;
    $this->project = $project;
    $this->rootPath = $rootPath;
  }
  
  /**
   * Статичный метод обработки сгенерированного события:
   * - событие может просто записаться в базу для дальнейшего выполнения 
   * - а может сразу выполниться
   * @param string $event - событие
   * @param array $params - массив с параметрами события
   * @param bool $needRealExecute - нужно ли сразу выполнять событие
   */
  public function executeEvent($event, $params, $needRealExecute = false)
  {
    // проверяем, есть ли для данного проекта обработчик этого события
    if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Event/' .$event.'.php')) {
      
      if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
        include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
      }
      
      $eventData = array(
        'dt'  => date('d-m-Y H:i:s'),
        'projectID' => $this->project['projectID'],
        'event_type'  => $event,
        'params'  => json_encode($params),
        'done'  => 0,
        'locked'  => 0,
      );
      $eventID = $this->registry->getDbHelper('WorkflowHelper')->addEvent($eventData);
      $eventData['eventID'] = $eventID;
      
      if ($needRealExecute) {
        $this->registry->getModel('Tasks')->executeEvent($eventData);
      }
    }
    
    return true;
  }
  
  /**
   * Возвращает обработчик события
   * @param array $eventData
   */
  public function getEvent($eventData)
  { 
    if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Event/' .$eventData['event_type'].'.php')) {
      
      if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
        include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
      }
      
      require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Event/' .$eventData['event_type'].'.php';      
      $classNamme = "\\Workflow\\Project\\Event\\".$eventData['event_type'];      
      return new $classNamme($this->registry, $this->project);
    }
    
    return false;
  }
}