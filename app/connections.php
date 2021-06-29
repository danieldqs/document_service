<?php
declare(strict_types=1);

use App\Infrastructure\Persistence\DB;
use App\Infrastructure\Environment as Env;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;

/*
 * Eloquent ORM
 */
$capsule = new Capsule;

$capsule->addConnection([
  'driver'    => 'mysql',
  'host'      => Env::getValue("DB_HOST", "0.0.0.0"),
  'database'  => Env::getValue("DB_NAME", "document_service"),
  'username'  => Env::getValue("DB_USER"),
  'password'  => Env::getValue("DB_PASSWORD"),
  'charset'   => 'utf8',
  'collation' => 'utf8_unicode_ci',
  'prefix'    => '',
]);

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();

DB::addConnection('eloquent', $capsule);