<?php
/**
 * Created by PhpStorm.
 * User: zpx
 * Date: 11/15/18
 * Time: 4:54 PM
 */

namespace App\System;

class Application extends \Laravel\Lumen\Application
{
    public function __construct(?string $basePath = null)
    {
        parent::__construct($basePath);
    }

    public function  bootstrapRouter()
    {
        $this->router = new Router($this) ;
    }
}
