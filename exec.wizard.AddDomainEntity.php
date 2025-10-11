<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.ldap.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);



if($argv[1]=="--org"){CreateOrg();exit;}
if($argv[1]=="--dom"){CreateDom();exit;}



function CreateOrg():bool{
    $savedsettings=unserialize(@file_get_contents("/etc/artica-postfix/TMP_SAVED_SETTINGS"));
    $ldap = new clladp();
    if(!$ldap->AddOrganization($savedsettings["organization"])){
        sleep(2);
        $ldap->AddOrganization($savedsettings["organization"]);
    }
    return true;
}
function CreateDom():bool{
    $savedsettings=unserialize(@file_get_contents("/etc/artica-postfix/TMP_SAVED_SETTINGS"));
    $ldap = new clladp();
    if(!isset($savedsettings["smtp_domainname"])){
        $savedsettings["smtp_domainname"]="";
    }
    $ldap->AddDomainEntity($savedsettings["organization"],$savedsettings["smtp_domainname"]);
    return true;
}
