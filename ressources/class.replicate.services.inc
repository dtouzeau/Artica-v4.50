<?php


function replicate_services_after_restoring():bool{
    shell_exec("/usr/sbin/artica-phpfpm-service -sync-after-backup");
    return true;
}