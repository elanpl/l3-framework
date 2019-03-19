<?php

namespace elanpl\L3\ViewEngines\TwigWrapper;

class TwigWrapper implements \elanpl\L3\ViewEngines\IViewEngine
{
    protected $twig;

    public function __construct($currentModule, $configPsr)
    {
        global $_L3;

        $config = $configPsr();

        $loader = new \Twig\Loader\FileSystemLoader();

        if ($currentModule != '') {
            $moduleViewPath = $_L3->BaseDirectory.$_L3->ApplicationDirectory.DIRECTORY_SEPARATOR
                    .($currentModule != '' ? $currentModule.DIRECTORY_SEPARATOR : '').$_L3->ViewsDirectory;
            $loader->addPath($moduleViewPath);
        }

        $baseViewPath = $_L3->BaseDirectory.$_L3->ApplicationDirectory.DIRECTORY_SEPARATOR.$_L3->ViewsDirectory;
        $loader->addPath($baseViewPath);

        $this->twig = new \Twig\Environment($loader,
                ['cache' => $config['cache'], 'debug' => $config['debug']]);

        if ($config['debug']) {
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        $this->twig->addFunction(
                new \Twig\TwigFunction('asset', function ($string) use ($config) {
                    return trim($config['pageUrl'].'/'.trim($string, '/'), '/');
                })
            );

        $this->twig->addFunction(
                new \Twig\TwigFunction('route', function ($string) use ($config) {
                    return trim($config['pageUrl'] //.\Helpers\translate::addLanguageToUrl()
                            .'/'.trim($string, '/'), '/');
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

        foreach ($config['extensions'] as $extension) {
            $this->twig->addExtension(new $extension());
        }
    }

    public function render($view, $context)
    {
        return $this->twig->render($view, $context);
    }
}
