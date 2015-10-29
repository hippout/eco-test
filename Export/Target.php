<?php 

namespace Ecoplay\Export;

/**
 * Класс - обертка целевой БД
 */
class Target
{
  protected $connect = null;  // коннект к БД
  protected $schema = null; // схема БД
  
  /**
   * 
   * @param array $connectionSettings - настройки коннекта
   */
  public function __construct(array $connectionSettings)
  {
    $config = new \Doctrine\DBAL\Configuration();
    $this->connect = \Doctrine\DBAL\DriverManager::getConnection($connectionSettings, $config);
   
    $sm = $this->connect->getSchemaManager();
    $this->schema = $sm->createSchema();
  }
  
  public function getConnect()
  {
    return $this->connect;
  }
  
  public function getSchema()
  {
    return $this->schema;
  }
  
  /**
   * Создание таблиц
   * @param array $queries - массив запросов создания таблиц
   */
  public function createTables($queries)
  {
    foreach ($queries as $query) {
      $this->getConnect()->executeQuery($query);
    }
  }
  
  /**
   * Выгрузка данных
   * @param array $data
   */
  public function loadData($data)
  {
    //$startTime = microtime(true);
    
    //$query = 'INSERT INTO `'.$data['table'].'` VALUES ';    
    $this->getConnect()->beginTransaction();
    foreach ($data['data'] as $row) {      
      $this->getConnect()->insert($data['table'], $row);
    }
    $this->getConnect()->commit();
    
    //$settingTime = microtime(true);
    //\Ecoplay\Log\Message::log('Data load time: '.($settingTime - $startTime), 'sqllite_perf.log');
    
    /*if (($settingTime - $startTime) > 10) {
      die();
    }*/
  }
}