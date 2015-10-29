<?php 
/**
 * Factory
 *
 * Фабрика по созданию управляющих элементов (контроллеры, меню и т.п.) в зависимости от типа проекта.
 *
 * @author Илья Петров hippout@gmail.com
 * @version 1.0.1
 */

namespace Ecoplay\Controller;

class Factory
{
  protected $project = null; // ссылка на проект
  protected $registry = null; // ссылка на реестр
  protected $rootPath = ''; // корень к библиотеке Ecoplay

  /**
   * Конструктор, задающий текущий проект
   * @param array $project
   */
  public function __construct($project, \Ecoplay\Controller\Registry $registry, $rootPath)
  {
    $this->project = $project;    
    $this->registry = $registry;
    $this->rootPath = $rootPath;
  }
  
  /**
   * Возвращает класс, который генерирует информацию о меню проекта
   */
  public function getMenu()
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Menu\Type360($this->registry);
        break;
      case 'testing':
        return new \Ecoplay\Menu\TypeTesting($this->registry);
        break;
      case 'linear':
        return new \Ecoplay\Menu\TypeLinear($this->registry);
        break;
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Menu.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Menu.php';
          $classNamme = '\Workflow\Project\Menu';
          return new $classNamme($this->registry);
        }
        else {
          return new \Ecoplay\Menu\TypeWorkflow($this->registry);
        }
        
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий показ статы проекта
   */
  public function getProjectStatController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\ProjectStat\Type360($component, $this->registry);
        break;
        
      case 'testing':
        return new \Ecoplay\Controller\Project\ProjectStat\TypeTesting($component, $this->registry);
        break;
        
      case 'linear':
        return new \Ecoplay\Controller\Project\ProjectStat\TypeLinear($component, $this->registry);
        break;
        
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/ProjectStat.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/ProjectStat.php';
          $classNamme = '\Workflow\Project\Page\ProjectStat';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\ProjectStat\Type360($component, $this->registry);
        }    
        break;
    }
  }

  /**
   * Возвращает контроллер, реализующий показ сеансов
   */
  public function getSeancesController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\Seances\Type360($component, $this->registry);
        break;  

      case 'testing':
        return new \Ecoplay\Controller\Project\Seances\TypeTesting($component, $this->registry);
        break;
        
      case 'linear':
        return new \Ecoplay\Controller\Project\Seances\Type360($component, $this->registry);
        break;
        
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Seances.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Seances.php';
          $classNamme = '\Workflow\Project\Page\Seances';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\Seances\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий показ схемы 360
   */
  public function getShemeController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\Scheme\Type360($component, $this->registry);
        break;
    
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Sheme.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Sheme.php';
          $classNamme = '\Workflow\Project\Page\Sheme';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\Scheme\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий показ списка оцениваемых   
   */
  public function getAssessController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\Assess\Type360($component, $this->registry);
        break;
    
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Assess.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Assess.php';
          $classNamme = '\Workflow\Project\Page\Assess';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\Assess\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий показ списка респондентов
   */
  public function getRespondentsController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\Respondents\Type360($component, $this->registry);
        break;
    
      case 'testing':
        return new \Ecoplay\Controller\Project\Respondents\TypeTesting($component, $this->registry);
        break;
    
      case 'linear':
        return new \Ecoplay\Controller\Project\Respondents\TypeLinear($component, $this->registry);
        break;
    
      case 'workflow':
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
          include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
        }
          
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Respondents.php')) {          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Respondents.php';
          $classNamme = '\Workflow\Project\Page\Respondents';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\Respondents\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий показ групповых ключей
   */
  public function getGroupKeysController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case 'testing':
        return new \Ecoplay\Controller\Project\GroupKeys\TypeTesting($component, $this->registry);
        break;
    
      case 'linear':
        return new \Ecoplay\Controller\Project\GroupKeys\TypeLinear($component, $this->registry);
        break;
    
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/GroupKeys.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/GroupKeys.php';
          $classNamme = '\Workflow\Project\Page\GroupKeys';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\GroupKeys\TypeLinear($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий показ участников группового ключа
   */
  public function getGroupKeyMembersController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case 'testing':
        return new \Ecoplay\Controller\Project\GroupKeyMembers\TypeLinear($component, $this->registry);
        break;
  
      case 'linear':
        return new \Ecoplay\Controller\Project\GroupKeyMembers\TypeLinear($component, $this->registry);
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий показ респондентов группового ключа
   */
  public function getGroupKeyRespondentsController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case 'testing':
        return new \Ecoplay\Controller\Project\GroupKeyRespondents\TypeTesting($component, $this->registry);
        break;
  
      case 'linear':
        return new \Ecoplay\Controller\Project\GroupKeyRespondents\TypeLinear($component, $this->registry);
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий ввод группового ключа
   */
  public function getGroupKeyEnterController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case 'testing':
        return new \Ecoplay\Controller\Project\GroupKeyEnter\TypeTesting($component, $this->registry);
        break;
  
      case 'linear':
        return new \Ecoplay\Controller\Project\GroupKeyEnter\TypeLinear($component, $this->registry);
        break;
  
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/GroupKeyEnter.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/GroupKeyEnter.php';
          $classNamme = '\Workflow\Project\Page\GroupKeyEnter';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\GroupKeyEnter\TypeLinear($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий управление email шаблонами проекта
   */
  public function getProjectEmailsController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }    
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\ProjectEmails\Type360($component, $this->registry);
        break;
    
      case 'testing':
        return new \Ecoplay\Controller\Project\ProjectEmails\TypeTesting($component, $this->registry);
        break;
    
      case 'linear':
        return new \Ecoplay\Controller\Project\ProjectEmails\TypeLinear($component, $this->registry);
        break;
    
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/ProjectEmails.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/ProjectEmails.php';
          $classNamme = '\Workflow\Project\Page\ProjectEmails';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\ProjectEmails\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, который реалиузет доп. страницу
   */
  public function getPageController($component, $page)
  { 
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case 'workflow':        
        $pageClassName = str_replace(' ', '', ucwords(str_replace('_', ' ', $page)));
        $pageClassName = ucfirst($pageClassName);        
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/'.$pageClassName.'.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
                    
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/'.$pageClassName.'.php';
          $classNamme = "\\Workflow\\Project\\Page\\".$pageClassName;
          return new $classNamme($component, $this->registry);
        }        
        break;
    }
    
    return false;
  }
  
  /**
   * Возвращает контроллер, который выводит личный кабинет
   */
  public function getLkController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\Lk\Type360($component, $this->registry);
        break;
    
      case 'testing':
        return new \Ecoplay\Controller\Project\Lk\TypeTesting($component, $this->registry);
        break;
    
      case 'linear':
        return new \Ecoplay\Controller\Project\Lk\TypeLinear($component, $this->registry);
        break;
    
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Lk.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Lk.php';
          $classNamme = '\Workflow\Project\Page\Lk';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\Lk\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контролле, который выводит подчиненных
   */
  public function getSubordinatesController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\Subordinates\Type360($component, $this->registry);
        break;  
    
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Subordinates.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Subordinates.php';
          $classNamme = '\Workflow\Project\Page\Subordinates';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\Subordinates\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контролле, который выводит инфу о подчиненном
   */
  public function getSubordinateController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\Subordinate\Type360($component, $this->registry);
        break;
  
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Subordinate.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Subordinate.php';
          $classNamme = '\Workflow\Project\Page\Subordinate';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\Subordinate\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, который управляет обработкой ключа респондента
   */
  public function getKeyBehaviorController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\KeyBehavior\Type360($component, $this->registry);
        break;
  
      case 'testing':
        return new \Ecoplay\Controller\Project\KeyBehavior\TypeTesting($component, $this->registry);
        break;
  
      case 'linear':
        return new \Ecoplay\Controller\Project\KeyBehavior\TypeLinear($component, $this->registry);
        break;
  
      case 'workflow':
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
        }
          
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/KeyBehavior.php')) {
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/KeyBehavior.php';
          $classNamme = '\Workflow\Project\Page\KeyBehavior';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\KeyBehavior\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, который управляет прохождением анкеты
   */
  public function getBlankViewerController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\BlankViewer\Type360($component, $this->registry);
        break;
  
      case 'testing':
        return new \Ecoplay\Controller\Project\BlankViewer\TypeTesting($component, $this->registry);
        break;
  
      case 'linear':
        return new \Ecoplay\Controller\Project\BlankViewer\TypeLinear($component, $this->registry);
        break;
  
      case 'workflow':
          
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
          include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
        }
          
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/BlankViewer.php')) {
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/BlankViewer.php';
          $classNamme = '\Workflow\Project\Page\BlankViewer';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\BlankViewer\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, который управляет сеансом
   */
  public function getSeanceBehaviorController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\SeanceBehavior\Type360($component, $this->registry);
        break;
  
      case 'testing':
        return new \Ecoplay\Controller\Project\SeanceBehavior\TypeTesting($component, $this->registry);
        break;
  
      case 'linear':
        return new \Ecoplay\Controller\Project\SeanceBehavior\TypeLinear($component, $this->registry);
        break;
  
      case 'workflow':
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
              include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          // проверяем, возможно для данного проекта есть собственная настройка меню
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/SeanceBehavior.php')) {
              require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/SeanceBehavior.php';
              $classNamme = '\Workflow\Project\Page\SeanceBehavior';
              return new $classNamme($component, $this->registry);
          }
          else {
              return new \Ecoplay\Controller\Project\SeanceBehavior\Type360($component, $this->registry);
          }
          break;
    }
  }
  
  /**
   * Возвращает контроллер, который выводит группы проекта
   */
  public function getSessionsController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\Sessions\Type360($component, $this->registry);
        break;
    
      case 'testing':
        return new \Ecoplay\Controller\Project\Sessions\TypeTesting($component, $this->registry);
        break;        
    
      case 'linear':
        return new \Ecoplay\Controller\Project\Sessions\TypeLinear($component, $this->registry);
        break;
    
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Sessions.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
          
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Sessions.php';
          $classNamme = '\Workflow\Project\Page\Sessions';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\Sessions\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий показ списка участников
   */
  public function getMembersController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\Members\Type360($component, $this->registry);
        break;
        
      case 'testing':
        return new \Ecoplay\Controller\Project\Members\Type360($component, $this->registry);
        break;
        
      case 'linear':
        return new \Ecoplay\Controller\Project\Members\Type360($component, $this->registry);
        break;
  
      case 'workflow':
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
        }
          
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Members.php')) {
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/Members.php';
          $classNamme = '\Workflow\Project\Page\Members';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\Members\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает модельактуализации проекта
   */
  public  function getProjectMaintenanceModel()
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    if ($this->project['project_type'] == '360') {
      return new \Ecoplay\Maintenance\Type360($this->registry->getDbConnect(), $this->registry);
    }
    elseif ($this->project['project_type'] == 'workflow') {
      if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Model/Maintenance.php')) {
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
          include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
        }
        
        require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Model/Maintenance.php';
        $classNamme = '\Workflow\Project\Model\Maintenance';
        return new $classNamme($this->registry->getDbConnect(), $this->registry);
      }
      
      return false;
    }
    
    return false;
  }
  
  /**
   * Возвращает контроллер, реализующий показ списка участников
   */
  public function getWorkflowScriptsController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
    
    if ($this->project['project_type'] != 'workflow') {
      throw new \Exception('Bad project type');
    }
    
    // проверяем, возможно для данного проекта есть собственная настройка меню
    if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/WorkflowScripts.php')) {
      if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
        include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
      }

      require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/WorkflowScripts.php';
      $classNamme = '\Workflow\Project\Page\WorkflowScripts';
      return new $classNamme($component, $this->registry);
    }
    else {      
      return new \Ecoplay\Controller\Project\WorkflowScripts\TypeWorkflow($component, $this->registry);
    }
  }
  
  /**
   * Возвращает контроллер, реализующий показ инфы об участнике
   */
  public function getMemberInfoController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Menu class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case '360':
        return new \Ecoplay\Controller\Project\MemberInfo\Type360($component, $this->registry);
        break;
  
      case 'testing':
        return new \Ecoplay\Controller\Project\MemberInfo\TypeTesting($component, $this->registry);
        break;
  
      case 'linear':
        return new \Ecoplay\Controller\Project\MemberInfo\Type360($component, $this->registry);
        break;
  
      case 'workflow':
        // проверяем, возможно для данного проекта есть собственная настройка меню
        if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/MemberInfo.php')) {
          if (file_exists($this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php')) {
            include_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/settings.php';
          }
  
          require_once $this->rootPath.'workflow/projects/'.$this->project['projectID'].'/Page/MemberInfo.php';
          $classNamme = '\Workflow\Project\Page\MemberInfo';
          return new $classNamme($component, $this->registry);
        }
        else {
          return new \Ecoplay\Controller\Project\MemberInfo\Type360($component, $this->registry);
        }
        break;
    }
  }
  
  /**
   * Возвращает контроллер, реализующий анонимного заполнения анкете по групповому ключу
   */
  public function getAnonymBlankViewerController($component)
  {
    if (!$this->project) {
      throw new \Exception('Could not detect Controller class when project not setted');
    }
  
    switch ($this->project['project_type']) {
      case 'linear':        
        return new \Ecoplay\Controller\Project\AnonymBlankViewer\TypeLinear($component, $this->registry);
        break;
    }
  }
  
    /**
     * Возвращает контроллер, реализующий работу с администратором проекта
     */
    public function getUserFormController($component)
    {
        if (!$this->project) {
            throw new \Exception('Could not detect Menu class when project not setted');
        }
  
        switch ($this->project['project_type']) {
  
            case 'testing':
                return new \Ecoplay\Controller\Project\UserForm\TypeTesting($component, $this->registry);
                break;
              
            default:
                return new \Ecoplay\Controller\Project\UserForm\Type360($component, $this->registry);
                break;
        }
    }
    
    /**
     * Возвращает контроллер, реализующий вывод списка групповых отчетов
     */
    public function getGroupReportsListController($component)
    {
        if (!$this->project) {
            throw new \Exception('Could not detect Menu class when project not setted');
        }
    
        switch ($this->project['project_type']) {
    
            case 'testing':
                return new \Ecoplay\Controller\Project\GroupReportsList\TypeTesting($component, $this->registry);
                break;
    
            default:
                return new \Ecoplay\Controller\Project\GroupReportsList\Type360($component, $this->registry);
                break;
        }
    }
  
}