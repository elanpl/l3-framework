<?php

namespace elanpl\L3;

class BaseController{

    protected $L3;

    public function __construct(){
        global $_L3;
        $this->L3 = $_L3;
    }

}