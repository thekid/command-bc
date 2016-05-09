<?php namespace util\cmd\unittest;

use lang\ClassLoader;
use util\cmd\Command;
use util\cmd\ParamString;
use util\cmd\Config;
use util\cmd\MethodInjection;
use util\log\Logger;
use util\log\LogCategory;
use util\Properties;
use util\PropertyManager;
use xp\command\CmdRunner;
use rdbms\ConnectionManager;
use rdbms\DBConnection;
use rdbms\DriverManager;
use rdbms\DSN;
use io\streams\MemoryOutputStream;
use unittest\TestCase;
use lang\reflect\TargetInvocationException;

class MethodInjectionTest extends TestCase {

  private function newCommand($definitions) {
    return ClassLoader::defineType(
      self::class.$this->name,
       ['kind' => 'class', 'extends' => [Command::class], 'implements' => [], 'use' => [MethodInjection::class]],
       $definitions
    );
  }

  private function run($command) {
    $stream= new MemoryOutputStream();
    $runner= new CmdRunner();
    $runner->setOut($stream);
    $runner->run(new ParamString([$command->getName()]), new Config());
    return $stream;
  }

  #[@test]
  public function trait_usage() {
    $this->newCommand(['run' => function() { }]);
  }

  #[@test]
  public function inject_a_logger() {
    $command= $this->newCommand([
      'used' => null,
      '#[@inject(name= "test")] useLogger' => function(LogCategory $cat) { $this->used= $cat; },
      'run' => function() { $this->out->write($this->used->identifier); }
    ]);

    $cat= Logger::getInstance()->getCategory('test');
    $this->assertEquals($cat->identifier, $this->run($command)->getBytes());
  }

  #[@test]
  public function inject_a_connection() {
    $command= $this->newCommand([
      'used' => null,
      '#[@inject(name= "test")] useConn' => function(DBConnection $conn) { $this->used= $conn; },
      'run' => function() { $this->out->write($this->used->getDSN()->getDriver()); }
    ]);

    $conn= newinstance(DBConnection::class, [new DSN('test://db')], [
      'selectdb' => function($db) { },
      'identity' => function($field= null) { },
      'begin' => function($transaction) { },
      'rollback'  => function($transaction) { },
      'commit' => function($transaction) { },
      'close' => function() { },
    ]);
    ConnectionManager::getInstance()->register($conn, 'test');
    $this->assertEquals($conn->getDSN()->getDriver(), $this->run($command)->getBytes());
  }

  #[@test]
  public function inject_some_properties() {
    $command= $this->newCommand([
      'used' => null,
      '#[@inject(name= "test")] withConfig' => function(Properties $prop) { $this->used= $prop; },
      'run' => function() { $this->out->write($this->used->getFilename()); }
    ]);

    $prop= new Properties('test.ini');
    PropertyManager::getInstance()->register('test', $prop);
    $this->assertEquals($prop->getFilename(), $this->run($command)->getBytes());
  }

  #[@test, @expect(TargetInvocationException::class)]
  public function inject_raises_exceptions_for_unknown_types() {
    $command= $this->newCommand([
      'used' => null,
      '#[@inject] useTest' => function(TestCase $cat) { /* Never called */ },
      'run' => function() { /* Never called */ }
    ]);

    $this->run($command);
  }
}