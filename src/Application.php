<?php

namespace elanpl\L3;

class Application{
    public $Request; // build from received HTTP request
    public $Response; // response to be send
    public $Router; // routing engine
    public $Serialization; // serialization engine
    public $BaseDirectory; // directory root of the application
    public $ConfigDirectory; // directory containing application configuration files
    public $ControllersDirectory; // subdirectory/namespace part added to controller class 
    public $ViewsDirectory; // subdirectory to search for the view files
    public $ApplicationDirectory; // subdirectory/namespace part of the application
    //public $Controller; //Active controller
    //public $Module; //Active module
    //public $Action; //Active action
    public $Config;

    public function __construct(){
        //create request object
        $this->Request = new Request();
        //create response objcet
        $this->Response = new Response();
        //create router object
        $this->Router = new Router();
        //create serialization object
        $this->Serialization = new Serialization(); 
    }

    public function basePath(){
        return strstr($_SERVER['REQUEST_URI'], $this->Request->Path, true);
    }

    public function relPath($file){
        return ($this->Router->depth>0 ? str_repeat("../", substr($this->Request->Path,-1)=='/'?$this->Router->depth:$this->Router->depth-1) : "" ).$file; 
    }

    public function link($name, $parameters){
        return $this->Router->link($name, $parameters);
    }

    public function LoadApplicationConfiguration(){
        //include routing configuration
        include_once($this->ConfigDirectory."routing.php");
        //include the application configuration
        include_once($this->ConfigDirectory.'app.php');

        $this->Config = require($this->ConfigDirectory."config.php");
    }

    public function LoadConfigFile($file){
        //include configuration file
        return include_once($this->ConfigDirectory.$file);
    }

    public function IncludeFromConfigDir($file){
        //include configuration file
        return include($this->ConfigDirectory.$file);
    }

    /*
    //not need - PSR-4 autoload handles that
    function IncludeControler($Controller){
        include_once(_L3_CONTROLLER_PATH.$Controller.'.php');
    }*/

    public function FindControllerInCallStack($search_level=5){
        //search for controller, action and module if present
        $controller = "";
        $module = "";
        $action = "";
        $match = array();

        //analize the call stack
          $call_stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $search_level);
        
        for($i=0; $i<$search_level; $i++){
            if(preg_match(
                    $pattern = '#('.$this->ApplicationDirectory.')(\\\\(?P<module>.+))?\\\\'.$this->ControllersDirectory.'\\\\(?P<controller>.+)#',
                    $call_stack[$i]['class'],
                    $match
                )
            ){
                $action = $call_stack[$i]['function'];
                $module = $match['module'];
                $controller = $match['controller'];
                break;
            }
        }

        return array(
            'module' => $module,
            'controller' => $controller,
            'action' => $action
        );
    }

    function RunControllerAction($ControllerAction, $Parameters){
        
        //prepare object and action name
        /*
        TODO ...
        if(is_object($this->controler) && (is_a($this->controler,'L2_controler') || is_subclass_of($this->controler,'L2_controler'))){
            if(is_string($this->action)){
                $controler_object = $this->controler;
                $controler_action = $this->action;
            }
        }
        else if(is_string($this->controler)){
            if(is_string($this->action)){
                $this->includeControler($this->controler);
                $controler_object = new $this->controler();
                $controler_action = $this->action;
            }
        }*/

        if(is_array($ControllerAction)){
            //$this->IncludeControler($ControllerAction['controller']);
            $controller_class = "\\".$this->ApplicationDirectory."\\"
                .(isset($ControllerAction['module'])?$ControllerAction['module']."\\":"")
                .$this->ControllersDirectory."\\"
                .$ControllerAction['controller'];
            $controller_object = new $controller_class;
            $controller_action = $ControllerAction['action'];
        }

        //fire the action
        if(isset($controller_object) && isset($controller_action)){
            $this->Module = (isset($ControllerAction['module'])?$ControllerAction['module']:"");
            $this->Controller = $ControllerAction['controller'];
            $this->Action = $controller_action;
            // prepare args and start the action
            if(!isset($reflection)){
                $reflection = new \ReflectionMethod($controller_object, $controller_action);
            }
            if($reflection->getNumberOfParameters()){
                $fire_args=array();

                foreach($reflection->getParameters() AS $arg)
                {
                    if(isset($Parameters[$arg->name]) || (is_array($ControllerAction) && isset($ControllerAction['defaults'][$arg->name])))
                        if(isset($Parameters[$arg->name]))
                            $fire_args[$arg->name]=$Parameters[$arg->name];
                        else
                            $fire_args[$arg->name]=$ControllerAction['defaults'][$arg->name];
                    else
                    {
                        if($arg->isDefaultValueAvailable()){
                            $fire_args[$arg->name]=$arg->getDefaultValue();
                        }
                        else{
                            $fire_args[$arg->name]=null;
                        }
                    }
                }
                return $reflection->invokeArgs ($controller_object , $fire_args );
            }
            else{
                return $reflection->invoke($controller_object);
            }
        }
    }

    public function HandleActionResult($result){
        if(is_string($result)){
            $content = $result;
        }
        else if(is_object($result)){
            if($result instanceof ViewModel){
                if($SerializationContentType = $this->Serialization->Match($this->Request->AcceptTypes, $result)){
                    $content = $this->Serialization->Serialize($SerializationContentType, $result);
                    if(is_object($content)){
                        if($content instanceof Response){
                            $this->Response = $content;
                            unset($content);
                        }
                    }
                }
                else{
                    echo "Serializer not defined for Content-Type: ".$this->Request->Accept."<br>".PHP_EOL;
                    throw new \Exception("Not implemented!");
                }
            }
            else if($result instanceof View){
                $content = $result->render();
            }
            else if($result instanceof Response){
                $this->Response = $result;
            }  
        }
        if(isset($content))
                $this->Response->withBody($content);
        $this->Response->send();
    }

    public function BeforeAction(){
        if(!empty($this->Router->RouteInfo->BeforeAction)){
            foreach($this->Router->RouteInfo->BeforeAction as $event_handler){
                $this->Router->RouteInfo::EventHandlerFormatCheck($event_handler, $eh);
                if(isset($eh['class'])){
                    $object = new $eh['class'];
                    $reflection = new \ReflectionMethod($object, $eh['function']);
                    $fire_args = array();
                    $fire_args[] = $this->Request;
                    $fire_args[] = $this->Router->RouteInfo;
                    if(isset($eh['arguments'])){
                        $this->Router->RouteInfo::EventHandlerArgumentsFormatCheck($eh['arguments'], $args);
                        $fire_args = array_merge($fire_args, $args);
                    }
                    $result = $reflection->invokeArgs ($object , $fire_args );
                }
                else if (is_callable($eh['function'])){
                    throw new \Exception("Not implemented!");
                }
                if(isset($result)){
                    if(is_object($result)){
                        if($result instanceof Response){
                            $this->HandleActionResult($result);
                            exit();
                        }
                    }
                }
            }
        }
    }

    public function Run(){
        // Check if routing path is found
        if($action = $this->Router->match($this->Request)){
            // routing match found decide what to do next...
            // BeforeAction event
            $this->BeforeAction();

            // function -> call it...
            if(is_callable($action)){
                $reflection = new \ReflectionFunction($action);
                if($reflection->getNumberOfParameters()){
                    $fire_args=array();
      
                    foreach($reflection->getParameters() AS $arg)
                    {
                        if(isset($this->Router->parsedParameters[$arg->name]))
                            $fire_args[$arg->name]=$this->Router->parsedParameters[$arg->name];
                        else
                            $fire_args[$arg->name]=null;
                    }
                    
                    $result = call_user_func_array($action, $fire_args);
                }
                else{
                    $result = call_user_func($action);
                }
            }
            else if(is_object($action) && is_a($action, 'L2_controler_info')){
                // not implemented yet.
                throw new \Exception("Not implemented!");
                ///$action->runControlerAction($this->routing->param);
            }
            // array with controller, and action -> run the controller action
            else if(is_array($action) && array_key_exists('controller',$action) && array_key_exists('action', $action)){
                $result = $this->RunControllerAction($action, $this->Router->parsedParameters);
                
            }
            // string
            else if(is_string($action)){
                $result = $action;
            }

            // Handle the action result
            if(isset($result)){
                $this->HandleActionResult($result);
            }
        }
        // routing path not found -> generate 404 response
        else{
            echo "Routing not found: ".$this->Request->Path."!<br>".PHP_EOL;
            throw new \UnexpectedValueException ("Routing not found: ".$this->Request->Path."!");
        }
 
    }
}