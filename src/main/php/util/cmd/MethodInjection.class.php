<?php namespace util\cmd;

use lang\System;
use util\log\Logger;
use util\log\context\EnvironmentAware;
use util\PropertyManager;
use rdbms\ConnectionManager;
use lang\IllegalArgumentException;

/**
 * Backwards compatibility for xp-framework/command 8.0+
 *
 * @test  xp://util.cmd.unittest.MethodInjectionTest
 */
trait MethodInjection {

  /**
   * Creates a new instance. Perfoms injection for methods decorated with
   * `inject`, supporting the following types:
   *
   * - util.log.LogCategory
   * - util.Properties
   * - rdbms.DBConnection
   *
   * @param  util.cmd.Config $config
   * @return self
   */
  public static function newInstance($config) {
    $instance= new self();

    $prop= PropertyManager::getInstance();
    $prop->setSources($config->sources());

    $log= Logger::getInstance();
    $prop->hasProperties('log') && $log->configure($prop->getProperties('log'));

    $conn= ConnectionManager::getInstance();
    $prop->hasProperties('database') && $conn->configure($prop->getProperties('database'));

    // Setup logger context for all registered log categories
    foreach (Logger::getInstance()->getCategories() as $category) {
      if (null === ($context= $category->getContext()) || !($context instanceof EnvironmentAware)) continue;
      $context->setHostname(System::getProperty('host.name'));
      $context->setRunner('xp.command.CmdRunner');
      $context->setInstance(typeof($instance)->getName());
      $context->setResource(null);
      $context->setParams(implode(' ', $GLOBALS['argv']));
    }

    foreach (typeof($instance)->getMethods() as $method) {
      if ($method->hasAnnotation('inject')) {
        $annotation= $method->getAnnotation('inject');
        $param= $method->getParameter(0);

        $name= isset($annotation['name']) ? $annotation['name'] : $param->getName();
        $type= isset($annotation['type']) ? $annotation['type'] : $param->getType()->getName();

        switch ($type) {
          case 'util.log.LogCategory':
            $args= [$log->getCategory($name)];
            break;

          case 'util.Properties':
            $args= [$prop->getProperties($name)];
            break;

          case 'rdbms.DBConnection':
            $args= [$conn->getByHost($name, 0)];
            break;

          default:
            throw new IllegalArgumentException('Unknown injection type '.$type);
        }

        $method->invoke($instance, $args);
      }
    }

    return $instance;
  }
}