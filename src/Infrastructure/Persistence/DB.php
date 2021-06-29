<?php

namespace App\Infrastructure\Persistence;

use App\Infrastructure\Persistence\DB\DBFactory;
use Psr\Container\ContainerInterface;

/**
 * Class DB
 * @package App\Infrastructure\Persistence
 */
class DB
{
    /**
     * @var array
     */
  protected static $connections = [];
    
    /**
     * @var int
     */
  protected static $findAllLimit = 100;

  /**
   * @param string $key
   * @TODO if the key exist dont override it
   * @TODO $connection should be generic interface
   * @param $connection
   */
  public static function addConnection(string $key, $connection)
  {
    self::$connections[$key] = $connection;
  }

  /**
   * @return int
   */
  public static function getFindAllLimit(): int
  {
    return self::$findAllLimit;
  }

  /**
   * @param string $connection
   * @return false|mixed
   * @throws \Exception
   */
  public static function getConnection(string $connection)
  {

    if(!isset(self::$connections[$connection])){
      throw new \Exception('No orm found. ('.$connection.')');
    }

    return self::$connections[$connection];
  }
}