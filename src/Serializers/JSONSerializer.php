<?php

namespace elanpl\L3\Serializers;

class JSONSerializer implements ISerializer{
    public function Serialize($ViewModel)
    {
        return \json_encode($ViewModel->GetDTO());
    }
}