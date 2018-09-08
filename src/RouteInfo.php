<?php

namespace elanpl\L3;

class RouteInfo{
    // URL pattern path
    public $Path;
    // Http method (ex. GET, POST, ANY)
    public $Method;
    // The assigned structure (string, array, function...)
    public $Result;
    // The route name
    public $Name;
    // BeforeAction event handlers;
    public $BeforeAction;
    // AfterAction event handlers;
    public $AfterAction;
    // AfterResult event handlers;
    public $AfterResult;
       
    public function __construct($Method, $Path, $Result, $Name = '')
    {
        $this->Path = $Path;
        $this->Method = $Method;
        $this->Result = $Result;
        $this->Name = $Name;
        $this->BeforeAction = array();
        $this->AfterAction = array();
        $this->AfterResult = array();
    }

    public function AddBeforeAction($event_handler){
        if($this->EventHandlerFormatCheck($event_handler, $match)){
            $this->BeforeAction[] = $event_handler;
            return $this;
        }
        else{
            throw new \Exception("Wrong event handler format: $event_handler");
        }
    }

    public function AddAfterAction($event_handler){
        if($this->EventHandlerFormatCheck($event_handler, $match)){
            $this->AfterAction[] = $event_handler;
            return $this;
        }
        else{
            throw new \Exception("Wrong event handler format: $event_handler");
        }
    }

    public function AddAfterResult($event_handler){
        if($this->EventHandlerFormatCheck($event_handler, $match)){
            $this->AfterResult[] = $event_handler;
            return $this;
        }
        else{
            throw new \Exception("Wrong event handler format: $event_handler");
        }
    }

    public static function EventHandlerFormatCheck($event_handler, &$match){
        $pattern = '#^((?<class>[a-z0-9\\\\]+)::)?(?<function>[a-z0-9]+)(\\((?<arguments>[a-z0-9, ]+)?\\))?$#i';
        if(preg_match($pattern, $event_handler, $match)){
            if(isset($match['arguments'])){
                $result = self::EventHandlerArgumentsFormatCheck($match['arguments'],$arguments_match);
                return $result;
            }
            else{
                return 1;
            }
        }
        else{
            return 0;
        }
    }

    public static function EventHandlerArgumentsFormatCheck($arguments, &$match){
        $pattern = '#[^$|^]([0-9a-z; ]*)(?:$|,)#i';
        $result = preg_match_all($pattern, $arguments, $match_all_args);
        if($result){
            $match = $match_all_args[1];
        }
        return $result;
    }

 }