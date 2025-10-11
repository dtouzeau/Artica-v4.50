<?php

function winbindd_privileges():bool{

    $dirs["/var/run/samba"]=true;
    $dirs["/var/lib/samba"]=true;

    if(is_file("/etc/init.d/squid")){
        shell_exec("/usr/sbin/usermod -a -G winbindd_priv squid");
    }
    if(is_file("/etc/init.d/freeradius")){
        shell_exec("/usr/sbin/usermod -a -G winbindd_priv freerad");
    }

    foreach ($dirs as $dir=>$val){
        if(is_dir($dir)){
            if(!is_dir("$dir/winbindd_privileged")) {
                @mkdir("$dir/winbindd_privileged", 0750, true);
            }
            @chmod("$dir/winbindd_privileged",0750);
            @chgrp("$dir/winbindd_privileged", "winbindd_priv");
        }
    }

	$Array0755[]="/var/run/samba/winbindd";
	$Array0755[]="/var/cache/samba/lck";
    $Array0755[]="/var/run/samba/msg.lock";
    $Array0755[]="/var/lib/samba/private";
    $Array0755[]="/run/samba";
    $Array0755[]="/var/lib/samba";
    $Array0755[]="/var/cache/samba";
    $Array0755[]="/var/log/samba";



 	foreach ($Array0755 as $dir){
		if(!is_dir($dir)){@mkdir($dir,0755,true);}
		@chmod($dir,0755);
	}

    $Array0700[]="/var/run/samba/ncalrpc/np";
	$Array0700[]="/var/cache/samba/msg";
	$Array0700[]="/var/lib/samba/private/smbd.tmp/msg";
    $Array0700[]="/var/lib/samba/private/msg.sock";

    if(!is_dir("/var/lib/samba/smb_krb5")){
        @mkdir("/var/lib/samba/smb_krb5",0777,true);

    }
    shell_exec("/bin/chmod 1775 /var/lib/samba/smb_krb5 >/dev/null 2>&1");



	foreach ($Array0700 as $dir){
		if(!is_dir($dir)){@mkdir($dir,0700,true);}
		@chmod($dir,0700);
	}
    if(is_file("/etc/init.d/squid")) {
        if (is_dir("/var/run/samba/winbindd_privileged")) {
            shell_exec("/usr/sbin/usermod -G winbindd_priv squid >/dev/null 2>&1");
            shell_exec("/bin/chmod 0750 /var/run/samba/winbindd_privileged/ >/dev/null 2>&1");

        }
    }
    shell_exec("/bin/chmod 0750 /var/lib/samba/winbindd_privileged/ >/dev/null 2>&1");
    shell_exec("/usr/bin/chown root:winbindd_priv /var/lib/samba/winbindd_privileged/");

    if(is_dir("/var/run/samba/msg.lock")){
        @chmod("/var/run/samba/msg.lock",0755);
        @chown("/var/run/samba/msg.lock","root");
        @chgrp("/var/run/samba/msg.lock","root");
    }
    return setfacl();

}

function setfacl():bool{
    $unix=new unix();
    $setfacl=$unix->find_program("setfacl");
    $chmod=$unix->find_program("chmod");
    $dirs["/var/run/samba/winbindd_privileged"]=true;
    $dirs["/var/lib/samba/winbindd_privileged"]=true;


    if(strlen($setfacl)>5) {
        $possibleDirsACLS[] = "/var/cache/samba";
        $possibleDirsACLS[] = "/var/lib/samba/smb_krb5";

        foreach ($possibleDirsACLS as $Directory) {
            if (!is_dir($Directory)) {continue;}
            shell_exec("$setfacl -R -m u:squid:rwx $Directory >/dev/null 2>&1 &");
            shell_exec("$setfacl -R -m g:squid:rwx $Directory >/dev/null 2>&1 &");
        }
    }

     foreach ($dirs as $dir=>$val){
            $array=$unix->alt_stat("$dir/pipe");
            if(!isset($array["file"])){continue;}
            shell_exec("$setfacl -R -m g:squid:rwx $dir/pipe >/dev/null 2>&1");
            shell_exec("$chmod 1777 $dir/pipe");
            return true;

     }
     return false;
}