<?php
/**
 * Created by PhpStorm.
 * User: zpx
 * Date: 11/15/18
 * Time: 4:56 PM
 */

namespace App\System;


use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlockFactory;

class Router extends \Laravel\Lumen\Routing\Router
{
    public function __construct(\Laravel\Lumen\Application $app)
    {
        parent::__construct($app);
    }

    /**
     * @param string $prefix
     * @param string $controller_name
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function controller(string $prefix, string $controller_name){
        $class_name = 'App\Http\Controllers\\' . $controller_name;
        if (! class_exists($class_name)){
            throw new \Exception("class $class_name not found.");
        }
        $cls = new \ReflectionClass($class_name);
        $methods = $cls->getMethods( \ReflectionMethod::IS_PUBLIC);
        foreach($methods as $method){
            if ($method->isStatic())    continue;
            $decl_name = $method->getDeclaringClass()->name;
            if ( 0 == strcmp($decl_name, 'Laravel\Lumen\Routing\Controller') )  continue;
            $method_name = $method->getShortName();
            if ('_' == $method_name[0])    continue;
            list($http_methods, $path) = $this->_parseMethodName($cls, $method_name);
            foreach($http_methods as $http_method){
                $this->$http_method("$prefix/$path", "$controller_name@$method_name");
            }
        }
    }

    /**
     * @param $cls  \ReflectionClass
     * @param $name string
     * @return array
     */
    private function _parseMethodName($cls, $name){
        $re     = '/[A-Z]/';
        $tmp    = strtolower( preg_replace($re, '_$0', $name) );
        $tokens = explode('_', $tmp);
        $path   = $tmp;
        $method = $tokens[0];
        if('post' == $tokens[0]){
            $path   = substr($tmp, 5);
        }elseif('put' == $tokens[0]){
            $path   = substr($tmp, 4);
        }elseif('delete' == $tokens[0]){
            $path   = substr($tmp, 7);
        }else{
            $method = 'get';
            if ('get' == $tokens[0]){
                $path = substr($tmp, 4);
            }
            else{
                $method_list = $this->_parseMethodDoc($cls, $name);
                if (count($method_list) > 0){
                    $method = $method_list;
                }
            }
        }
        return [(is_array($method) ? $method: [$method]), $path];
    }

    /**
     * @param $cls  \ReflectionClass
     * @param $name string
     * @return array
     */
    private function _parseMethodDoc($cls, $name){
        $doc = $cls->getMethod($name)->getDocComment();
        $factory = DocBlockFactory::createInstance();
        if (strlen(trim($doc)) == 0)    return [];
        $doc_block = $factory->create($doc);
        if (is_null($doc_block))        return [];
        $tags = $doc_block->getTags();
        $methods = [];
        /** @var Generic $tag */
        foreach($tags as $tag){
            if ($tag->getName() == 'methods'){
                $params = $tag->getDescription()->render();
                $params = strtolower($params);
                $params = preg_replace('/[\(\)\s]+/','', $params);
                $methods = explode(',', $params);
            }
        }
        return $methods;
    }
}