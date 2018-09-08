<?php

namespace elanpl\L3\Serializers;

use elanpl\L3\ViewModel;


class HTMLSerializer implements ISerializer{

    public function get_class_name($object)
    {
        $classname = get_class($object);
        if ($pos = strrpos($classname, '\\')) return substr($classname, $pos + 1);
        return $pos;
    }

    public function Serialize($ViewModel)
    {
        if(is_object($ViewModel) && ($ViewModel instanceof ViewModel)){
            /*
            if(method_exists($ViewModel, 'GetContext')){
                $context = $ViewModel->GetContext();
            }
            else{
                $context = array();
                $context['vm'] = $ViewModel;
            }

            if(method_exists($ViewModel, 'GetView')){
                $view = $ViewModel->GetView();
            }
            else{
                $view = get_class($ViewModel);//$this->get_class_name($ViewModel);
            }

            $View = new \elanpl\L3\View($view, $context);
            */
            $View = new \elanpl\L3\View($ViewModel);

            return $View->render();
        }
        throw new \Exception("Invalid argument type for HTML Serializer!");
    }
}
