<?php

namespace elanpl\L3\ViewEngines;

interface IViewEngine{
    public function render($ViewFile, $ViewParams);
}