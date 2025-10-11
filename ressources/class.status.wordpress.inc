<?php


function __is_firewall_enabled(){
    $cachefile="/etc/artica-postfix/iptables-wordpress-status.conf";
    $iptables_save=$GLOBALS["CLASS_UNIX"]->find_program("iptables-save");
    shell_exec("$iptables_save > $cachefile");
    $datas=explode("\n",file_get_contents($cachefile));
    $pattern2="#comment\s+fw-wordpress#";

    foreach ($datas as $ligne) {
        $ligne = trim($ligne);
        if ($ligne == null) {
            continue;
        }
        if (preg_match($pattern2, $ligne)) {
            return true;
        }
    }
    return false;
}

function wordpress(){
    $PREFIX="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
    $ROOT="/usr/share/artica-postfix";
	$EnableWordpressManagement=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWordpressManagement"));
	if($EnableWordpressManagement==0){
		return;
	}

    __is_firewall_enabled();
	$ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
	if($ServerRunSince>3){
        $cmd=trim("$PREFIX $ROOT/exec.wordpress.install.php --firewall >/dev/null 2>&1");
	    shell_exec2($cmd);
    }

    if(!is_file("/etc/crond.d/wordpress-backup")){
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("wordpress-backup","30 2 * * *","exec.wordpress.install.php --backup-all");
        shell_exec2("/etc/init.d/cron reload");

    }


}



