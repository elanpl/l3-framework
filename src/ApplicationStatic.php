<?php

namespace elanpl\L3;

class ApplicationStatic{
    public static function __callStatic($name, $arguments)
    {
        global $_L3;
        $reflection = new \ReflectionMethod($_L3, $name);
        if(count($arguments)){
            return $reflection->invokeArgs ($_L3 , $arguments );
        }
        else{
            return $reflection->invoke($_L3);
        }
    }
}