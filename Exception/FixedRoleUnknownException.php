<?php 

namespace Ecoplay\Exception;

/**
 * Ошибка когда пытаемся выполнить какое-то действие с ролью, основываясь на ее fixed_role
 * и не знаем как обработать этот fixed_role 
 */

class FixedRoleUnknownException extends \Exception
{
  
}