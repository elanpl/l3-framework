<?php

namespace elanpl\L3;

class View{
    protected $ViewFileExtension;
    protected $ViewFile;
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
            throw new \Exception("View file:\"$view\" not found!");
        }
        $this->Context = $context;
    }

    public function Render(){
        if ($viewEngineClass = ViewEngine::Get($this->ViewFileExtension)){
            $view = new $viewEngineClass();
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

            $ViewsPath = $_L3->BaseDirectory.$_L3->ApplicationDirectory.DIRECTORY_SEPARATOR
                .($module!=""?$module.DIRECTORY_SEPARATOR:"").$_L3->ViewsDirectory.DIRECTORY_SEPARATOR;

            $dirs = array();

            $dirs[] = $ViewsPath.$controller.DIRECTORY_SEPARATOR.$action;
            $dirs[] = $ViewsPath.$controller.DIRECTORY_SEPARATOR;
            $dirs[] = $ViewsPath;

            foreach($dirs as $dir){
                // check if the file exists
                if(is_file($found = $dir.($action!=""&&$view!=""?DIRECTORY_SEPARATOR:"").$view)){
                    return $found;
                }
                // try to add extension and check again
                else foreach(ViewEngine::GetRegisteredFileExtensions() as $RegisteredExtension){
                    if(is_file($found = $dir.($action!=""&&$view!=""?DIRECTORY_SEPARATOR:"").$view.$RegisteredExtension)){
                        return $found;
                    }
                }
            }
            

        }
        return null;
    }
}