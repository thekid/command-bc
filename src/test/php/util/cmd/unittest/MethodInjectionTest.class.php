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
use util\RegisteredPropertySource;
use xp\command\CmdRunner;
use rdbms\ConnectionManager;
use rdbms\DBConnection;
use rdbms\DriverManager;
use io\streams\MemoryOutputStream;
use unittest\TestCase;
use lang\reflect\TargetInvocationException;

class MethodInjectionTest extends TestCase {

  /** @return void */
  #[@beforeClass]
  public static function registerTestConnection() {
    DriverManager::register('test', ClassLoader::defineClass(self::class.'TestConnection', DBConnection::class, [], '{
      public function selectdb($db) { }
      public function identity($field= null) { }
      public function begin($transaction) { }
      public function rollback($transaction) { }
      public function commit($transaction) { }
      public function close() { }
    }'));
  }

  /**
   * Defines a new command type
   *
   * @param  [:var] $definition
   * @return lang.XPClass
   */
  private function newCommand($definitions) {
    return ClassLoader::defineType(
      self::class.$this->name,
       ['kind' => 'class', 'extends' => [Command::class], 'implements' => [], 'use' => [MethodInjection::class]],
       $definitions
    );
  }

  /**
   * Runs a command type
   *
   * @param  lang.XPClass $command
   * @param  util.cmd.Config $config Optional configuration
   * @return io.OutputStream command's output
   */
  private function run($command, $config= null) {
    $stream= new MemoryOutputStream();

    $runner= new CmdRunner();
    $runner->setOut($stream);
    $runner->run(new ParamString([$command->getName()]), $config ?: new Config());

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

    $conn= DriverManager::getConnection('test://db');
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
    $config= new Config(new RegisteredPropertySource('test', $prop));
    $this->assertEquals($prop->getFilename(), $this->run($command, $config)->getBytes());
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

  #[@test]
  public function logger_configuration_is_read_from_log_properties() {
    $command= $this->newCommand([
      'used' => null,
      '#[@inject(name= "test")] useLogger' => function(LogCategory $cat) { $this->used= $cat; },
      'run' => function() { $this->out->write($this->used->getFlags()); }
    ]);

    $config= new Config(new RegisteredPropertySource('log', Properties::fromString('
      [test]
      flags=1 ; INFO
    ')));
    $this->assertEquals('1', $this->run($command, $config)->getBytes());
  }

  #[@test]
  public function connection_configuration_is_read_from_database_properties() {
    $command= $this->newCommand([
      'used' => null,
      '#[@inject(name= "test")] useConn' => function(DBConnection $conn) { $this->used= $conn; },
      'run' => function() { $this->out->write($this->used->getDSN()->getHost()); }
    ]);

    $config= new Config(new RegisteredPropertySource('database', Properties::fromString('
      [test]
      dsn="test://from-config"
    ')));
    $this->assertEquals('from-config', $this->run($command, $config)->getBytes());
  }
}