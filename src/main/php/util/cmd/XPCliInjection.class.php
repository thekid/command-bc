<?php namespace util\cmd;

use util\log\Logger;
use util\PropertyManager;
use rdbms\ConnectionManager;
use lang\IllegalArgumentException;

trait XPCliInjection {

  /**
   * Creates a new instance. Perfoms injection for methods decorated with
   * `inject`, supporting the following types:
   *
   * - util.log.LogCategory
   * - util.Properties
   * - rdbms.DBConnection
   *
   * @return self
   */
  public static function newInstance() {
    $instance= new self();

    $log= Logger::getInstance();
    $prop= PropertyManager::getInstance();
    $conn= ConnectionManager::getInstance();

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