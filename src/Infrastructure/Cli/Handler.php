<?php

namespace App\Infrastructure\Cli;

class Handler
{
    public function handle()
    {
        global $argv;
        $domain = $argv[1] ?? false;
        if(!$domain)
        {
          throw new \Exception('Missing argument domain');
        }

        $cls = sprintf('App\Domain\%s\Manage', $domain);
        if(!class_exists($cls))
        {
          throw new \Exception('Invalid domain');
        }

        $method = $argv[2] ?? 'index';
        if(!method_exists($cls, $method)){
          throw new \Exception('Invalid method');
        }
        $cls::$method();
    }
}