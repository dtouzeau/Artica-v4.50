<?php


function cgroups(){
    $prefixex="{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix";
    $cgroupsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsEnabled"));
    if($cgroupsEnabled==1){
        if(!is_file("/etc/init.d/cgconfig")){
            shell_exec("$prefixex/exec.cgroups.php --install");
            return null;
        }
    }else{
        if(is_file("/etc/init.d/cgconfig")){
            shell_exec("$prefixex/exec.cgroups.php --uninstall");
            return null;
        }
    }

}
