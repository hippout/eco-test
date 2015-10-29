<?php 
/**
 * Base
 * 
 * Базовый класс контроллера. В рамках проекта "ЛИНКИС" контроллер - это класс который выполняет действие
 * компонента. Так как поведение компонента может в значительной степени отличаться в зависимости от внешних,
 * но в большей степени от внутренних (например тип проекта) характеристик, то и осуществлять это поведение
 * удобней в отдельном файле-классе
 * 
 * @author Илья Петров hippout@gmail.com
 * @version 1.0.1
 */

namespace Ecoplay\Controller;

use Ecoplay\Controller\Registry as Registry;

abstract class Base
{
  /**
   * Ссылка на компонент, чье поведение обрабатываем
   * @var CBitrixComponent
   */
  protected $component = null;
  
  /**
   * Ссылка на реестр, т.к. все равно хелперы, модели и т.п. всегда нужны
   * @var Registry
   */
  protected $registry = null;
  
  /**
   * Конструктор, для контроллера обязательно нужен компонент, который его вызывает
   * @param \CBitrixComponent $component
   */
  final public function __construct(\CBitrixComponent $component, Registry $registry)
  {
    $this->component = $component;
    $this->registry = $registry;
  }
  
  /**
   * Реализация поведения компонента
   * @param array $context - различные характеристики контекста, в котором выполняется компонент
   */
  abstract public function execute($context);
} 