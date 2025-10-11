<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(!is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")) {
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/resolvapply");
}
?>

