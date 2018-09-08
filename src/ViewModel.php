<?php

namespace elanpl\L3;

abstract class ViewModel{
    private $module;
    private $controller;
    private $action;
                    
    public function __construct()
    {
        global $_L3;
        $match = $_L3->FindControllerInCallStack();

        $this->controller = $match['controller'];
        $this->module = $match['module'];
        $this->action = $match['action'];
    }

    public function GetController(){
        if(!isset($this->controller)){
            throw new \Exception("Controller not found in ViewModel \"".get_class($this)."! Use parent::__construct() in the class constructor or SetController method.");
        }
        else
            return $this->controller;
    }

    public function GetAction(){
        if(!isset($this->controller)){
            throw new \Exception("Action not found in ViewModel \"".get_class($this)."! Use parent::___construct() in the class constructor or SetAction method.");
        }
        return $this->action;
    }

    public function GetModule(){
        return $this->module;
    }

    public function SetController($controller){ 
        return $this->controller = $controller;
    }

    public function SetAction($action){ 
        return $this->action = $action;
    }

    public function SetModule($module){ 
        return $this->module = $module;
    }

    public abstract function GetDTO();
}