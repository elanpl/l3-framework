<?php

namespace elanpl\L3\ViewEngines\TwigWrapper;

class TwigWrapper implements \elanpl\L3\ViewEngines\IViewEngine{

    public function render($view, $context){
        $loader = new \Twig_Loader_Filesystem(dirname($view));
        /*$twig = new Twig_Environment($loader, array(
            'cache' => '/path/to/compilation_cache',
        ));*/

        $twig = new \Twig_Environment($loader);

        return $twig->render(basename($view), $context);
    }

}