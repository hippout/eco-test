<?php 
namespace Ecoplay\Controller;

/**
* For second commit
**/
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * Registry
 *
 * Класс-синглетон, предназначенный для реализации единого места хранения/получения контекста
 * HTTP запроса, а также служебных объектов (например объектов DB хелперов, моделей и т.п.)
 *
 * @author Илья Петров hippout@gmail.com
 * @version 1.0.2
 */
class Registry
{
  /**
   * Объект-одиночка данного класса
   * @var Registry
   */
  private static $instances = array();
  
  /**
   * Массив с классами DB хелперов
   * @var array
   */
  protected $dbHelpers = array();
  
  /**
   * Массив с классами моделей
   * @var array
   */
  protected $models = array();
  
  /**
   * Ссылка на коннект для работы с БД 
   * @var CDatabase
   */
  protected $dbConnect = null;
  
  /**
   * Путь к корню исходников сайта
   * @var string
   */
  protected $rootDir = null;
  
  /**
   * Объект логгера приложения
   * @var Logger
   */
  protected $logger = null;
  
  /**
   * Сырой коннект к БД
   * @var unknown
   */
  protected $rawDbConnect = null;
  
  /**
   * Массив с префиксами сущностей
   * @var array
   */
  protected $entitiesPrefixes = array();
  
  /**
   * Конструктор класса, инициализирует коннект к БД
   *
   * @param \CDatabase $dbConnect - коннект к БД
   */
  private function __construct(\CDatabase $dbConnect)
  {
    $this->dbConnect = $dbConnect;
    
    $this->logger = new Logger('eco_logger');
    $this->logger->pushHandler(new RotatingFileHandler($this->getRootDir().'/temp/log/monolog/eco_app.log', 0, Logger::DEBUG, true, 0666));    
  }
  
  /**
   * Возвращает экземпляр класса
   *
   * @param \CDatabase $dbConnect - коннект к БД
   */
  public static function getInstance(\CDatabase $dbConnect)
  {
    /*if (!self::$instance) {
      self::$instance = new self($dbConnect);
    }
  
    return self::$instance;*/
      
      if (!array_key_exists($dbConnect->DBName, self::$instances)) {
          self::$instances[$dbConnect->DBName] = new self($dbConnect);
      }
      
      return self::$instances[$dbConnect->DBName];
  }
  
  /**
   * Возвращает заданный DB хелпер
   * @param string $helperName - название хелпера
   */
  public function getDbHelper($helperName)
  {
    if (!array_key_exists($helperName, $this->dbHelpers)) {      
      $helperClassName = "Ecoplay\\Helper\\Db\\".$helperName;
      $this->dbHelpers[$helperName] = new $helperClassName($this->dbConnect, $this);
    }
    return $this->dbHelpers[$helperName];
  }
  
  /**
   * Возвращает workflow DB хелпер
   * @param ште $projectID
   */
  public function getWorkflowDbHelper($projectID)
  {
    if (!array_key_exists('Helper'.$projectID, $this->dbHelpers)) {
      require_once $this->getRootDir().'/workflow/projects/'.$projectID.'/Helper/DbHelper.php';      
      $helperClassName = 'Workflow\Project\Helper\DbHelper'.$wfClassPostfix;
      $this->dbHelpers['Helper'.$projectID] = new $helperClassName($this->dbConnect, $this);
    }
    return $this->dbHelpers['Helper'.$projectID];
  }
  
  /**
   * Возвращает заданную модель
   * @param string $modelName - название модели
   */
  public function getModel($modelName)
  {
    if (!array_key_exists($modelName, $this->models)) {
      $modelClassName = "Ecoplay\\Model\\".$modelName;
      $this->models[$modelName] = new $modelClassName($this->dbConnect, $this);
    }
    return $this->models[$modelName];
  }
  
  /**
   * Возвращает workflow модель
   * @param ште $projectID
   */
  public function getWorkflowModel($projectID)
  {
    if (!array_key_exists('Model'.$projectID, $this->models)) {
      require_once $this->getRootDir().'/workflow/projects/'.$projectID.'/Model/Model.php';
      $modelClassName = 'Workflow\Project\Model\Model'.$wfClassPostfix;
      $this->models['Model'.$projectID] = new $modelClassName($this->dbConnect, $this);
    }
    return $this->models['Model'.$projectID];
  }
  
  /**
   * Возвращает коннект к БД
   * @return \Ecoplay\Controller\CDatabase
   */
  public function getDbConnect()
  {
    return $this->dbConnect;
  }
  
  /**
   * Задает папку корня сайта
   * @param string $rootDir
   */
  public function setRootDir($rootDir)
  {
    $this->rootDir = $rootDir;
  }
  
  /**
   * Возвращает папку корня сайта
   */
  public function getRootDir()
  {
    return $this->rootDir ? $this->rootDir : $_SERVER["DOCUMENT_ROOT"];
  }
  
  /**
   * Возвращает логгер приложения
   * @return \Monolog\Logger
   */
  public function getLogger()
  {
      return $this->logger;
  }
  
  /**
   * Возвращает сырой коннект к БД
   */
  public function getRawDbConnect()
  {
      if (!$this->rawDbConnect) {          
          $this->rawDbConnect = new \mysqli($this->dbConnect->DBHost, $this->dbConnect->DBLogin, $this->dbConnect->DBPassword, $this->dbConnect->DBName);
          $this->rawDbConnect->query('SET NAMES utf8'); 
      }
      
      return $this->rawDbConnect;
  }
  
  /**
   * Возвращает экземпляр wf сущности
   * @param int $projectID
   * @param string $entityName
   */
  public function getWfEntity($projectID, $entityName)
  {
      if (file_exists($this->getRootDir().'/workflow/projects/'.$projectID.'/Entities/'.$entityName.'.php')) {
          require_once $this->getRootDir().'/workflow/projects/'.$projectID.'/Entities/'.$entityName.'.php';
          
          if (!array_key_exists($projectID.'_'.$entityName, $this->entitiesPrefixes)) {
              $this->entitiesPrefixes[$projectID.'_'.$entityName] = $wfClassPostfix;
          }
          
          $entityClassName = 'Workflow\Project\Entities\\'.$entityName.$this->entitiesPrefixes[$projectID.'_'.$entityName];          
          $numArgs = func_num_args();          
          if ($numArgs > 2) {
              $constructArgs = array();
              for ($i = 3; $i <= $numArgs; $i++) {
                  $constructArgs[] = func_get_arg($i - 1);
              }
              
              $ref = new \ReflectionClass($entityClassName);              
              return $ref->newInstanceArgs($constructArgs);              
          }
          else {              
              return new $entityClassName();
          }
      }
      else {
          throw new \Exception('Wf entity class file not found');
      }
  }
}