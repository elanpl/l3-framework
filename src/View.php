<?php

namespace elanpl\L3;

class View{
    protected $ViewFileExtension;
    protected $ViewFile;
    protected $currentModule;
    protected $Context;

    public function __construct(){
        $numargs = func_num_args();
     
        if ($numargs == 0) {
            $view = "";
            $context = array();
        }
        else if ($numargs == 1) {
            $arg = func_get_arg(0);
            if(is_object($arg) && $arg instanceof ViewModel){
                if(method_exists($arg, 'GetContext')){
                    $context = $arg->GetContext();
                }
                else{
                    $context = array();
                    $context['vm'] = $arg;
                }

                if(method_exists($arg, 'GetView')){
                    $view = $arg->GetView();
                }
                else{
                    $view = $arg;
                }
            }
            else{
                $view = "";
                $context = $arg;
            }
        }
        else {
            $view = func_get_arg(0);
            $context = func_get_arg(1);
        }
        

        if($this->ViewFile = $this->find($view)){
            $this->ViewFileExtension = ".".pathinfo($this->ViewFile, PATHINFO_EXTENSION);
        }
        else{
            $viewError = $view;
            if(is_object($view) && $view instanceof ViewModel){
                $viewError = $view->GetModule().'/'.$view->GetController().'/'.$view->GetAction();
            }
            
            throw new \Exception("View file:\"$viewError\" not found!");
        }
        $this->Context = $context;
    }

    public function Render(){
        if ($viewEngineClass = ViewEngine::Get($this->ViewFileExtension)){
            $view = new $viewEngineClass($this->currentModule);
            return $view->render($this->ViewFile, $this->Context);
        }
        else{
            throw new \Exception("View Engine not found for the view file extension:\"".$this->ViewFileExtension."\"!");
        }
    }

    public function find($view){
        //find the view file
        if(is_string($view) && is_file($view)){
            // full path was provided...
            return $view;
        }
        else{
            // search for the view file
            
            //search for controller, action and module if present  
            global $_L3;
            if(is_object($view) && $view instanceof ViewModel){
                $controller = $view->GetController();
                $module = $view->GetModule();
                $action = $view->GetAction();
                $view = "";
            }
            else{                    
                
                $match = $_L3->FindControllerInCallStack();

                $controller = $match['controller'];
                $module = $match['module'];
                $action = $match['action'];     
            }
            //locate the view file
            
            // The search order:
            // check [module/]views/controller/action/ directory (file)
            // check [module/]views/controller/ directory (file)
            // check [module/]views/

            if(\strpos($controller, "\\")){
                $controller = \str_ireplace("\\", "/", $controller);
            }


            $ViewsPath = $_L3->BaseDirectory.$_L3->ApplicationDirectory.DIRECTORY_SEPARATOR
                .($module!=""?$module.DIRECTORY_SEPARATOR:"").$_L3->ViewsDirectory.DIRECTORY_SEPARATOR;

            $this->currentModule = $module;
            
            $dirs = array();

            $dirs[] = $controller.DIRECTORY_SEPARATOR.$action;
            $dirs[] = $controller.DIRECTORY_SEPARATOR;
            $dirs[] = '';

            foreach($dirs as $dir){
                $viewName = $dir.($action!=""&&$view!=""?DIRECTORY_SEPARATOR:"").$view;
                // check if the file exists
                if(is_file($ViewsPath.$viewName)){
                    return $viewName;
                }
                // try to add extension and check again
                else {
                    foreach(ViewEngine::GetRegisteredFileExtensions() as $RegisteredExtension){
                        if(is_file($ViewsPath.$viewName.$RegisteredExtension)){
                            return $viewName.$RegisteredExtension;
                        }
                    }
                }
            }
            

        }
        return null;
    }
}