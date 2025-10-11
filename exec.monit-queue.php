<?php
xstart();
function xstart():bool{

    if(!file_exists("/usr/share/artica-postfix/bin/artwatch")){
        return false;
    }
    if(!file_exists("/etc/monit/conf.d/APP_REST_WATCHDOG.monitrc")){
        system("/usr/share/artica-postfix/bin/artwatch -install-you");
    }

    unlink("/etc/cron.d/monit-queue");
    unlink(__FILE__);
    return true;
}

?>