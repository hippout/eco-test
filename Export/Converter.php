<?php 

namespace Ecoplay\Export;

/**
 * Конвертация данных из одного источника в другой
 * (в частности из базы MySQL в SQLite) 
 */
class Converter
{
  protected $source; // объект-обертка источнкиа данных
  protected $target; // объект-обертка целевой БД
  
  /**
   * Конструктор класса - создание источников данных
   * @param array $sourceSettings - настройки коннекта к источнику данных
   * @param array $targetSettings - настройки коннекта к целевой БД
   */
  public function __construct($sourceSettings, $targetSettings)
  {    
    $this->source = new Source($sourceSettings);    
    $this->target = new Target($targetSettings);
  }
  
  /**
   * Экспортирует данные из источника в цель
   * @param array $sourceFilters - фильтры исходных данных
   */
  public function export($sourceFilters)
  {
    // задаем фильтры данных
    $this->source->setFilters($sourceFilters);
        
    // создаем таблицы в целевой БД
    $tablesQueries = $this->source->getTablesCreationQueries($this->target);
    $this->target->createTables($tablesQueries);
        
    // производим выгрузку в цикле данных
    while ($data = $this->source->getData()) {
      $this->target->loadData($data);      
    }
    
    
    unset($this->target);    
    unset($this->source);
        
    return true;
  }
}