<?php

namespace elanpl\L3;

class Serialization{
    protected static $serializers; //registered serializers dictionary

    public function __construct()
    {
        if(!isset(self::$serializers)) self::$serializers = array(); 
    }

    public static function Register($ContentType, $Serializer, $ViewModelClass = null){
        if(isset($ViewModelClass) && $ViewModelClass!=''){
            $class_key = $ViewModelClass.'|';
        }
        else{
            $class_key = '';
        }
        self::$serializers[$class_key.$ContentType] = $Serializer;
    }

    public function Match($AcceptTypes, $ViewModel=null){
        if(isset($ViewModel)){
            if(is_object($ViewModel)){
                $ViewModelClass = get_class($ViewModel);
            }
            if(is_string($ViewModel)){
                $ViewModelClass = $ViewModel;
            }
        }

        if(is_array($AcceptTypes)){
            $RegisteredTypes = array_keys(self::$serializers);

            foreach ($AcceptTypes as $type){
                //Dedicated config for ViewModel class first...
                if(array_key_exists($ViewModelClass."|".$type, self::$serializers)){
                    return $type;
                }
                foreach($RegisteredTypes as $rtype_with_class){
                    $rtype_parts = explode("|", $rtype_with_class);
                    if(count($rtype_parts)==2){
                        $rclass = $rtype_parts[0];
                        $t = explode("/", $type);
                        $rt = explode("/", $rtype_parts[1]);
                        if($rclass==$ViewModelClass && ($t[0]=="*" || $rt[0]=="*" || $t[0]==$rt[0]) && ($t[1]=="*" || $rt[1]=="*" || $t[1]==$rt[1])){
                            return $rtype_with_class;
                        }
                    }
                }

                //Then check configs without the ViewModel class name
                if(array_key_exists($type, self::$serializers)){
                    return $type;
                }
                foreach($RegisteredTypes as $rtype){
                    $t = explode("/", $type);
                    $rt = explode("/", $rtype);
                    if(($t[0]=="*" || $rt[0]=="*" || $t[0]==$t[0]) && ($t[1]=="*" || $rt[1]=="*" || $t[1]==$t[1])){
                        return $rtype;
                    }
                }
            }
        }
        return false;
    }

    public function Serialize($ContentType, $ViewModel){
        if(isset(Serialization::$serializers[get_class($ViewModel).'|'.$ContentType])){
            $serializerClass = Serialization::$serializers[get_class($ViewModel).'|'.$ContentType];
        }
        else if(isset(Serialization::$serializers[$ContentType])){
            $serializerClass = Serialization::$serializers[$ContentType];
        }

        if(isset($serializerClass)){
            $serializer = new $serializerClass();
            return $serializer->Serialize($ViewModel);
        }
        else{
            return null;
        }
    }
}