<?php




function nDPI(){


    $EnablenDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));
    $EnablenDPIFistInstall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPIFistInstall"));
    $KERNEL_VERSION=$GLOBALS["CLASS_UNIX"]->KERNEL_VERSION();
    $ndpi_path="/lib/modules/$KERNEL_VERSION/extra/xt_ndpi.ko";


    if($EnablenDPIFistInstall==0){
        if(!is_file($ndpi_path)){return;}
        if(!is_file("/etc/init.d/nDPI")){
            squid_admin_mysql(0, "Install nDPI by default... [action=install]", null,__FILE__,__LINE__);
            $cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.firehol.php --enable-ndpi");
            shell_exec2($cmd);
            return;
        }
    }




    if($EnablenDPI==0){
        if(is_file("/etc/init.d/nDPI")){
            squid_admin_mysql(0, "nDPI is disabled but service is installed [action=uninstall]", null,__FILE__,__LINE__);
            $cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.firehol.php --disable-ndpi");
            shell_exec2($cmd);
            return;
        }
        return;
    }

    if(!is_file("/etc/init.d/nDPI")){
        squid_admin_mysql(0, "nDPI is enabled but service is uninstalled [action=install]", null,__FILE__,__LINE__);
        $cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.firehol.php --enable-ndpi");
        shell_exec2($cmd);
        return;
    }

    if(!is_file($ndpi_path)){
        squid_admin_mysql(0, "nDPI is enabled but module for $KERNEL_VERSION doesn`t exists [action=uninstall]",
            "$ndpi_path no such file!",__FILE__,__LINE__);
        $cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.firehol.php --disable-ndpi");
        shell_exec2($cmd);

    }


}

