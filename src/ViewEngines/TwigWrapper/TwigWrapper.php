<?php

namespace elanpl\L3\ViewEngines\TwigWrapper;



class TwigWrapper implements \elanpl\L3\ViewEngines\IViewEngine{

    protected $twig;
    
    public function __construct($currentModule){
        global $_L3;
        
        $config = require( $_L3->ConfigDirectory.DIRECTORY_SEPARATOR.'twig.php' );
        
        $loader = new \Twig_Loader_Filesystem( );
        
        if ( $currentModule != '') {
            $moduleViewPath = $_L3->BaseDirectory.$_L3->ApplicationDirectory.DIRECTORY_SEPARATOR
                    .($currentModule!=""?$currentModule.DIRECTORY_SEPARATOR:"").$_L3->ViewsDirectory;
            $loader->addPath($moduleViewPath);
        } 
        
        $baseViewPath = $_L3->BaseDirectory.$_L3->ApplicationDirectory.DIRECTORY_SEPARATOR.$_L3->ViewsDirectory;
        $loader->addPath($baseViewPath);
         
        $this->twig = new \Twig_Environment($loader, 
                [ 'cache' => $config['cache'], 'debug' => $config['debug'] ]);

        if( $config['debug'] ){
            $this->twig->addExtension(new \Twig_Extension_Debug());
        }
                
        
        $this->twig->addFunction(
                new \Twig_SimpleFunction ('asset', function ($string) use ($config) {
                    return trim( $config['pageUrl'].'/'.trim($string,'/') ,'/');
                })
            );

        $this->twig->addFunction(
                new \Twig_SimpleFunction ('route', function ($string) use ($config) {
                    return trim( $config['pageUrl'] //.\Helpers\translate::addLanguageToUrl() 
                            .'/'.trim($string,'/') ,'/');
                })
            ); 
        
//        $this->twig->addFunction(
//                new Twig_SimpleFunction ('logged', function () {
//                    return Auth::user();
//                })
//            );             
                
//        $this->twig->addFunction(
//                    new Twig_SimpleFunction ('translate', function ($phrase, $defaultLabel ,$params = []) {
//                        return \Helpers\translate::translate($phrase, $defaultLabel ,$params);
//                    })
//                );       
                
        foreach ( $config['extensions'] as $extension)        
            $this->twig->addExtension(new $extension());         
                
    }
    
    public function render($view, $context){
        return $this->twig->render( $view,  $context);
    }

}