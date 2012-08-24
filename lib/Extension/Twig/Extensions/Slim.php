<?php

class Twig_Extensions_Slim extends Twig_Extension {
    
    public function getName() {
        return 'slim';
    }

    public function getFunctions() {
        return array(
            'urlFor' => new Twig_Function_Method($this, 'urlFor'),
        );
    }

    public function getFilters() {
        return array(
            'slug' => new Twig_Filter_Method($this, 'slug'),
        );
    }

    public function urlFor($name, $params = array(), $appName = 'default') {
        return Slim::getInstance($appName)->urlFor($name, $params);
    }

    public function slug($string, $cut = 35) {
        return substr($string, 0, $cut) . '...';
    }

    
}
