<?php
function GetMacVendors():array{
    $MACVENDORS=unserialize(base64_decode(@file_get_contents("/usr/share/artica-postfix/ressources/databases/MACVENDORS.db")));
    if(!is_array($MACVENDORS)){return array();}
    return $MACVENDORS;

}