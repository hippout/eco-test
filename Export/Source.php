<?php 

namespace Ecoplay\Export;

/**
 * Класс - обертка источника конвертируемых данных
 */
class Source
{
  protected $connect = null;  // коннект к БД
  protected $connectSettings = null;  // настройки коннекта к БД
  protected $schema = null; // схема БД
  protected $filters = null;
  protected $exportingTables = array(  // массив с таблицами, которые экспортируем (в порядке согласно логики экспорта)
    'prep_projects', 'prep_sessions', 'prep_respondents', 'prep_assess_360', 'prep_blanks', 'prep_blanks_questions', 'prep_blanks_questions_params',
    'prep_blanks_answers', 'prep_blanks_answers_params', 'prep_blanks_screens', 'prep_company_structure_departments', 'prep_company_structure_staff',
    'prep_competency_models', 'prep_competency_models_tree', 'prep_competency_models_questions', 'prep_competency_models_questions_params',
    'prep_competency_models_answers', 'prep_competency_models_answers_params', 'prep_projects_members', 'prep_respondents_role_types_360',
    'prep_sessions_group_keys', 'prep_translations', 'carry_seances', 'carry_seances_answers', 'carry_seances_answers_texts',
      
    'carry_tests_seances', 'carry_tests_seances_answers', 'carry_tests_seances_answers_texts', 'prep_tests_sessions', 'prep_tests_packages',
    'prep_tests_packages_items', 'prep_tests', 'prep_tests_sections', 'prep_tests_sections_items', 'prep_tests_tasks', 'prep_tests_tasks_answers',
    'prep_tests_texts', 'prep_tests_versions', 'prep_tests_tasks_banks'
  );
  protected $tablesFilters = array(  // колонки в таблицах из которых добавляем данные для фильтров
    'prep_projects'  => array(
      array (
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_sessions'  => array(
      array (
        'field'  => 'sessionID',
        'filter'  => 'sessionID',
      ),
    ),
    /*'prep_respondents'  => array(
      array (
        'field'  => 'respondentID',
        'filter'  => 'respondentID',
      ),
    ),
    'prep_assess_360'  => array(
      array (
        'field'  => 'assessID',
        'filter'  => 'assessID',
      ),
    ),*/
    'prep_blanks'  => array(
      array (
        'field'  => 'blankID',
        'filter'  => 'blankID',
      ),
    ),
    /*'prep_blanks_questions'  => array(
      array (
        'field'  => 'questionID',
        'filter'  => 'questionID',
      ),
    ),
    'prep_blanks_answers'  => array(
      array (
        'field'  => 'answerID',
        'filter'  => 'answerID',
      ),
    ),*/
    'prep_competency_models'  => array(
      array (
        'field'  => 'competencyID',
        'filter'  => 'competencyID',
      ),
    ),
    'prep_competency_models_questions'  => array(
      array (
        'field'  => 'questionID',
        'filter'  => 'cquestionID',
      ),
    ),
    'prep_competency_models_answers'  => array(
      array (
        'field'  => 'answerID',
        'filter'  => 'canswerID',
      ),
    ),
    /*'prep_projects_members'  => array(
      array (
        'field'  => 'projects_memberID',
        'filter'  => 'memberID',
      ),
    ),*/
    /*'carry_seances'  => array(
      array (
        'field'  => 'seanceID',
        'filter'  => 'seanceID',
      ),
    ),
    'carry_seances_answers'  => array(
      array (
        'field'  => 'seance_answerID',
        'filter'  => 'sanswerID',
      ),
    ),*/
    'prep_tests_sessions'  => array(
      array (
        'field'  => 'packageID',
        'filter'  => 'packageID',
      ),
    ),
    'prep_tests_packages_items'  => array(
      array (
        'field'  => 'testID',
        'filter'  => 'testID',
      ),
    ),
    'prep_tests_sections'  => array(
      array (
        'field'  => 'sectionID',
        'filter'  => 'sectionID',
      ),
    ),
    'prep_tests_sections_items'  => array(
      array (
        'field'  => 'taskID',
        'filter'  => 'taskID',
      ),
      array (
        'field'  => 'task_versionID',
        'filter'  => 'task_versionID',
      ),
      array (
        'field'  => 'bankID',
        'filter'  => 'bankID',
      ),
    ),
      
  );
  protected $filtersToApply = array(  // настройки какие фильтры применять при получении данных из таблицы
    'prep_projects'  => array(
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ), 
    'prep_sessions'  => array(
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
      array(
        'field'  => 'sessionID',
        'filter'  => 'sessionID',
      ),
    ),
    'prep_respondents'  => array(
      array(
        'field'  => 'respondentID',
        'filter'  => 'respondentID',
      ),
      /*array(
        'field'  => 'sessionID',
        'filter'  => 'sessionID',
      ),*/
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_assess_360'  => array(
      array(
        'field'  => 'assessID',
        'filter'  => 'assessID',
      ),
      /*array(
        'field'  => 'sessionID',
        'filter'  => 'sessionID',
      ),*/
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_blanks'  => array(
      array(
        'field'  => 'blankID',
        'filter'  => 'blankID',
      ),
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_blanks_questions'  => array(
      array(
        'field'  => 'questionID',
        'filter'  => 'questionID',
      ),
      array(
        'field'  => 'blankID',
        'filter'  => 'blankID',
      ),
    ),
    'prep_blanks_questions_params'  => array(
      /*array(
        'field'  => 'questionID',
        'filter'  => 'questionID',
      ),*/
        array(
            'field'  => 'blankID',
            'filter'  => 'blankID',
        ),
    ),
    'prep_blanks_answers'  => array(
      array(
        'field'  => 'answerID',
        'filter'  => 'answerID',
      ),
      /*array(
        'field'  => 'questionID',
        'filter'  => 'questionID',
      ),*/
        array(
            'field'  => 'blankID',
            'filter'  => 'blankID',
        ),
    ),
    'prep_blanks_answers_params'  => array(
      /*array(
        'field'  => 'answerID',
        'filter'  => 'answerID',
      ),*/
        array(
            'field'  => 'blankID',
            'filter'  => 'blankID',
        ),
    ),
    'prep_blanks_screens'  => array(
      array(
        'field'  => 'blankID',
        'filter'  => 'blankID',
      ),
    ),
    'prep_company_structure_departments'  => array(
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_company_structure_staff'  => array(
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_competency_models'  => array(
      array(
        'field'  => 'competencyID',
        'filter'  => 'competencyID',
      ),
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_competency_models_tree'  => array(
      array(
        'field'  => 'itemID',
        'filter'  => 'itemID',
      ),
      array(
        'field'  => 'competencyID',
        'filter'  => 'competencyID',
      ),
    ),
    'prep_competency_models_questions'  => array(
      array(
        'field'  => 'questionID',
        'filter'  => 'cquestionID',
      ),
      array(
        'field'  => 'competencyID',
        'filter'  => 'competencyID',
      ),
    ),
    'prep_competency_models_questions_params'  => array(
      array(
        'field'  => 'questionID',
        'filter'  => 'cquestionID',
      ),
    ),
    'prep_competency_models_answers'  => array(
      array(
        'field'  => 'answerID',
        'filter'  => 'canswerID',
      ),
      array(
        'field'  => 'questionID',
        'filter'  => 'cquestionID',
      ),
    ),
    'prep_competency_models_answers_params'  => array(
      array(
        'field'  => 'answerID',
        'filter'  => 'canswerID',
      ),
    ),
    'prep_projects_members'  => array(
      array(
        'field'  => 'projects_memberID',
        'filter'  => 'memberID',
      ),
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_respondents_role_types_360'  => array(
      array(
        'field'  => 'roleID',
        'filter'  => 'roleID',
      ),
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_sessions_group_keys'  => array(
      array(
        'field'  => 'sessionID',
        'filter'  => 'sessionID',
      ),
    ),
    'prep_translations'  => array(
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'carry_seances'  => array(
      array(
        'field'  => 'seanceID',
        'filter'  => 'seanceID',
      ),
      /*array(
        'field'  => 'sessionID',
        'filter'  => 'sessionID',
      ),*/
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'carry_seances_answers'  => array(
      /*array(
        'field'  => 'seanceID',
        'filter'  => 'seanceID',
      ),*/
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'carry_seances_answers_texts'  => array(
      /*array(
        'field'  => 'seance_answerID',
        'filter'  => 'sanswerID',
      ),*/
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
      
    /**      тестирование       **/
    'carry_tests_seances'  => array(
      array(
        'field'  => 'tests_seanceID',
        'filter'  => 'tests_seanceID',
      ),
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'carry_tests_seances_answers'  => array(
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'carry_tests_seances_answers_texts'  => array(        
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),      
    'prep_tests_sessions'  => array(
      array(
        'field'  => 'projectID',
        'filter'  => 'projectID',
      ),
    ),
    'prep_tests_packages'  => array(
      array(
        'field'  => 'packageID',
        'filter'  => 'packageID',
      ),
    ),
    'prep_tests_packages_items'  => array(
      array(
        'field'  => 'packageID',
        'filter'  => 'packageID',
      ),
    ),
    'prep_tests'  => array(
      array(
        'field'  => 'testID',
        'filter'  => 'testID',
      ),
    ),
    'prep_tests_sections'  => array(
      array(
        'field'  => 'testID',
        'filter'  => 'testID',
      ),
    ),
    'prep_tests_sections_items'  => array(
      array(
        'field'  => 'sectionID',
        'filter'  => 'sectionID',
      ),
    ),
    'prep_tests_tasks'  => array(
      array(
        'field'  => 'taskID',
        'filter'  => 'taskID',
      ),
    ),
    'prep_tests_tasks_answers'  => array(
      array(
        'field'  => 'taskID',
        'filter'  => 'taskID',
      ),
    ),
    'prep_tests_texts'  => array(
      array(
        'field'  => 'testID',
        'filter'  => 'testID',
      ),
    ),
    'prep_tests_versions'  => array(
      array(
        'field'  => 'versionID',
        'filter'  => 'task_versionID',
      ),
    ),
    'prep_tests_tasks_banks'  => array(
      array(
        'field'  => 'bankID',
        'filter'  => 'bankID',
      ),
    ),
  );
  
  protected $joinedTables = array(
      'prep_blanks_questions_params'  => 'JOIN `prep_blanks_questions` AS pbq ON src.`questionID` = pbq.`questionID`',
      'prep_blanks_answers'  => 'JOIN `prep_blanks_questions` AS pbq ON src.`questionID` = pbq.`questionID`',
      'prep_blanks_answers_params'  => '
            JOIN `prep_blanks_answers` AS pba ON pba.`answerID` = src.`answerID` 
            JOIN `prep_blanks_questions` AS pbq ON pba.`questionID` = pbq.`questionID`
      ',
  );
  
  protected $mandatoryFilteredTables = array(
    'prep_tests_packages', 'prep_tests_packages_items', 'prep_tests', 'prep_tests_sections', 'prep_tests_sections_items',
    'prep_tests_tasks', 'prep_tests_tasks_answers', 'prep_tests_texts', 'prep_tests_versions', 'prep_tests_tasks_banks'
  );
  
  protected $iterator = null;
  protected $iterationDataLimit = 50000;
  protected $iteratorFilters = array();
  
  /**
   * 
   * @param array $connectionSettings - настройки коннекта   
   */
  public function __construct(array $connectionSettings)
  {
    $this->connectSettings = $connectionSettings;
    $this->initConnect();
    
    $sm = $this->connect->getSchemaManager();
    $this->schema = $sm->createSchema();
  }
  
  public function initConnect()
  {
    $config = new \Doctrine\DBAL\Configuration();
    $this->connect = \Doctrine\DBAL\DriverManager::getConnection($this->connectSettings, $config);
    
    // для MySQL устанавливаем кодировку
    if (isset($this->connectSettings['driver']) && $this->connectSettings['driver'] == 'pdo_mysql') {
      $this->connect->executeQuery('SET NAMES utf8');
    }
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
   * Возвращает запросы на создание таблиц базы -цели
   * @param Target $target - целевая БД   
   */
  public function getTablesCreationQueries(Target $target)
  { 
    $queriesSrc = $target->getSchema()->getMigrateToSql($this->getSchema(), $target->getConnect()->getDatabasePlatform());
    
    $tablesQueries = array();
    $indexesQueries = array();
    
    foreach ($queriesSrc as $query) {
      if (preg_match('/^CREATE TABLE ([a-z0-9_]*) .*/', $query, $matched) && in_array($matched[1], $this->exportingTables)) {        
        $tablesQueries[] = $query;
      }
      elseif (preg_match('/^CREATE[ UNIQUE]* INDEX .* ON ([a-z0-9_]*) .*/', $query, $matched)
                 && in_array($matched[1], $this->exportingTables)) {        
        $indexesQueries[] = $query;
      }
    }
    
    return array_merge($tablesQueries, $indexesQueries);
  }
  
  /**
   * Установка фильтров
   * @param array $filters - фильтры для выбора только подходящих данных в таблицах
   */
  public function setFilters(array $filters = array())
  {
    // TODO: валидировать формат фильтра
    $this->filters = $filters;
  }
  
  /**
   * Итеративно выгружаем данные
   */
  public function getData()
  {    
    // определяем итератор
    if (!$this->iterator) {
      $this->iterator = array(
        'index'  => 0,
        'offset'  => 0,
      );
    }
    
    # пробуем получить данные
    $appliedFilters = array();
    if (array_key_exists($this->exportingTables[$this->iterator['index']], $this->filtersToApply)) {  // для текущей таблицы возможны фильтры      
      foreach ($this->filtersToApply[$this->exportingTables[$this->iterator['index']]] as $filter) {
        if (array_key_exists($filter['filter'], $this->filters)) { // фильтр задан, применяем его
          $appliedFilters[] = array(
            'field'  => $filter['field'],
            'value'  => $this->filters[$filter['filter']],
          );
        }
      }
    }
    
    //$startTime = microtime(true);
    
    if (count($appliedFilters)) { // получаем отфильтрованные данные
      $filterStr = '';
      for ($i = 0; $i < count($appliedFilters); $i++) {
        if (count($appliedFilters[$i]['value'])) {        
          $filterStr .= (($i == 0) ? '' : ' AND ').'`'.$appliedFilters[$i]['field'].'` IN (?)';
        } 
      }
      if (!$filterStr) {
        $filterStr = '1 = 0'; // фильтров нет, значит вероятно родительских данных нет, поэтому невыполняемое условие ставим
      }
      $sql = '
        SELECT src.*
        FROM '.$this->exportingTables[$this->iterator['index']].' AS src
            '.((array_key_exists($this->exportingTables[$this->iterator['index']], $this->joinedTables)) ? $this->joinedTables[$this->exportingTables[$this->iterator['index']]] : '').'
        WHERE '.$filterStr.'            
        LIMIT '.$this->iterator['offset'].', '.$this->iterationDataLimit.'
      ';      
      
      $prepareArgs = array($sql);
      for ($i = 0; $i < count($appliedFilters); $i++) {
        if (count($appliedFilters[$i]['value'])) {        
          $prepareArgs[] = array($appliedFilters[$i]['value']);
          $prepareArgs[] = array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }
      }      
      $stmt = call_user_func_array(array($this->getConnect(), 'executeQuery'), $prepareArgs);   
      $data = $stmt->fetchAll();      
    }
    elseif (in_array($this->exportingTables[$this->iterator['index']], $this->mandatoryFilteredTables)) {
      $data = false;
    }
    else {  // запрос без фильтров
      $data = $this->getConnect()->fetchAll('
        SELECT *
        FROM '.$this->exportingTables[$this->iterator['index']].'
        LIMIT '.$this->iterator['offset'].', '.$this->iterationDataLimit.'
      ');
    }
    
    
    //$gettingTime = microtime(true);    
    //\Ecoplay\Log\Message::log('Data get ('.$this->exportingTables[$this->iterator['index']].' - cnt: '.count($data).') time: '.($gettingTime - $startTime), 'sqllite_perf.log');
    
    if (!$data || !count($data) /*|| $this->iterator['offset'] >= 20000*/) { // выборка не дала результатов
      
      // проверяем, есть ли еще таблицы для выгрузки
      if ($this->iterator['index'] >= (count($this->exportingTables) - 1)) { // таблиц больше нет, завершаем выполнение
        $this->iterator = null; // обнуляем итератор
        $this->iteratorFilters = array();
        
        return false;
      }
      else {
        // обновляем фильтр        
        if (array_key_exists($this->exportingTables[$this->iterator['index']], $this->tablesFilters)) {
          foreach ($this->tablesFilters[$this->exportingTables[$this->iterator['index']]] as $filter) {
            $filterName = $filter['filter'];
            if (!array_key_exists($filterName, $this->filters)) {
              $this->filters[$filterName] = array();
            }
            foreach ($this->iteratorFilters[$filterName] as $filterValue) {
              if (!in_array($filterValue, $this->filters[$filterName])) {
                $this->filters[$filterName][] = $filterValue;
              }
            }
          }       
        }
        $this->iteratorFilters = array();
        
        // увеличиваем итератор и рекурсивно получаем данные
        $this->iterator['index']++;        
        $this->iterator['offset'] = 0;
        return $this->getData();
      }
    }
    else {  // выборка успешная
      
      //увеличиваем смещение для следующей выборки в этой же таблице
      $this->iterator['offset'] += $this->iterationDataLimit;
      
      // добавляем значения, по которым в дальнейшем будем фильтровать
      if (array_key_exists($this->exportingTables[$this->iterator['index']], $this->tablesFilters)) {
        foreach ($this->tablesFilters[$this->exportingTables[$this->iterator['index']]] as $filter) {
          $field = $filter['field'];
          $filterName = $filter['filter'];
          if (!array_key_exists($filterName, $this->iteratorFilters)) {
            $this->iteratorFilters[$filterName] = array();
          }
          foreach ($data as $row) {
            $this->iteratorFilters[$filterName][] = $row[$field];
          }
        }
      } 
      
      // возвращаем выбранные данные
      return array(
        'table'  => $this->exportingTables[$this->iterator['index']],
        'data'  => $data,
      );
    }
  }
}