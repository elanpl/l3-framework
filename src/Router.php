<?php

namespace elanpl\L3;

class Router{

    //protected static $_instance; // object instance (for fluent api)
    protected static $routes; // The defined routes collection
    protected static $parsedParameters; // Parameters parsed from Request Path
    protected static $depth; // Number of nested nodes in Request Path
    protected static $routeNameIndex; // An array with elements that reference to the routes ordered by a route names
    public static $RouteInfo; // The RouteInfo objcet if the route was matched

    public function __construct()
    {
        //if(!isset(self::$_instance)) self::$_instance = new self;
        if(!isset(self::$routes)) self::$routes = array(); 
        if(!isset(self::$routeNameIndex)) self::$routeNameIndex= array();
    }

    public static function add($Method, $Path, $Result, $Name = ''){
        self::$routes[] = new RouteInfo($Method, $Path, $Result, $Name);
        if(isset($Name)&&$Name!='') self::$routeNameIndex[$Name] = &self::$routes[count(self::$routes)-1];
        return new self;
    }

    public static function get($Path, $Result, $Name = ''){
        return self::add('GET', $Path, $Result, $Name);
    }

    public static function post($Path, $Result, $Name = ''){
        return self::add('POST', $Path, $Result, $Name);
    }

    public static function put($Path, $Result, $Name = ''){
        return self::add('PUT', $Path, $Result, $Name);
    }

    public static function patch($Path, $Result, $Name = ''){
        return self::add('PATCH', $Path, $Result, $Name);
    }

    public static function delete($Path, $Result, $Name = ''){
        return self::add('DELETE', $Path, $Result, $Name);
    }

    public static function any($Path, $Result, $Name = ''){
        return self::add('ANY', $Path, $Result, $Name);
    }

    public static function AddBeforeAction($event_handler){
        self::$routes[count(self::$routes)-1]->AddBeforeAction($event_handler);
        return new self;
    }

    public static function AddAfterAction($event_handler){
        self::$routes[count(self::$routes)-1]->AddAfterAction($event_handler);
        return new self;
    }

    public static function AddAfterResult($event_handler){
        self::$routes[count(self::$routes)-1]->AddAfterAction($event_handler);
        return new self;
    }

    public static function match($Request){

        $auri = explode('/', trim($Request->Path, "/ \t\n\r\0\x0B"));
        $curi = count($auri);
			
        foreach (self::$routes as $routeInfo) {
            
            $route = $routeInfo->Path;
            $method = $routeInfo->Method;
            if($method=='ANY' || strpos($Request->Method,$method)!==false){
                $aroute = explode('/', trim($route, "/ \t\n\r\0\x0B"));
                //print_r($aroute);
                if($curi==count($aroute)){ //compare path element count
                    //optimistic assumption :)
                    $matchResult = true;
                    for($i = 0; $i<$curi; $i++){
                        $pathPartName = trim($aroute[$i],'{}');
                        if($aroute[$i]==$pathPartName){
                            if($auri[$i]!=$pathPartName){
                                //echo "diffrence found";
                                $matchResult = false;
                                break;
                            }
                        }
                        else{ // {...} found -> catch $uri variable
                            $value = $auri[$i];
                            $valueKey = explode(':', $pathPartName);
                            //validation
                            if(isset($valueKey[1]) && $valueKey[1]=='int'){
                                $value = intval($value);
                            }
                            //value store...
                            self::$parsedParameters[$valueKey[0]] = $value;
                        }
                    }
                    if($matchResult){ // match found
                        self::$depth = $curi;
                        self::$RouteInfo = $routeInfo;
                        return $routeInfo->Result;
                    }
                }
            }
        }
        return false;
    }

    public static function link($name, $parameters){
        $route = self::$routeNameIndex[$name];
        $fields = array_keys($parameters);
        $values = array_values($parameters);
        array_walk($fields, function (&$item, $key){
            $item = "/\{".$item."\}/";
        });
        return preg_replace($fields, $values, $route->Path);
    }

    public function __get($name)
    {
        if($name=="routes") return self::$routes;
        if($name=="parsedParameters") return self::$parsedParameters;
        if($name=="depth") return self::$depth;
        if($name=="RouteInfo") return self::$RouteInfo;
    }

    public function __call($name, $arguments)
    {
        if($name == 'GetParameter'){
            return self::$parsedParameters[$arguments[0]];
        }
        // Note: value of $name is case sensitive.
        $allowed_methods = ['add', 'post', 'any', 'get', 'match', 'AddBeforeAction', 'AddAfterAction', 'AddAfterResult'];

        if(in_array($name, $allowed_methods)){
            $the_method = new \ReflectionMethod($this, $name);
            $the_method->invokeArgs(NULL,$arguments);
        }
        else{
            throw new \Exception("Call undefined or inaccesible method ".get_class($this)."::$name");
        }
        
    }

}