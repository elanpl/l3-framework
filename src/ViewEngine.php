<?php

namespace elanpl\L3;

class ViewEngine
{
    protected static $viewengines; //registered view engines dictionary

    public function __construct()
    {
        if (!isset(self::$viewengines)) {
            self::$viewengines = array();
        }
    }

    public static function Register($FileExtension, $ViewEngine, $ViewEngineConfig)
    {
        self::$viewengines[$FileExtension] = array('class' => $ViewEngine, 'config' => $ViewEngineConfig);
    }

    public static function Get($FileExtension)
    {
        if (array_key_exists($FileExtension, self::$viewengines)) {
            return self::$viewengines[$FileExtension];
        } else {
            return null;
        }
    }

    public static function GetRegisteredFileExtensions()
    {
        return array_keys(self::$viewengines);
    }
}
