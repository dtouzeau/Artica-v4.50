<?php

class Elasticz{


    function __construct(){
        spl_autoload_register('__autoload_elastica');

    }


}


function __autoload_elastica ($class) {
    $path = str_replace('\\', '/', substr($class, 1));
    if (file_exists('/usr/share/artica-postfix/ressources/externals/' . $path . '.php')) {
    require_once('/usr/share/artica-postfix/ressources/externals/' . $path . '.php');
    }

    return true;
}

