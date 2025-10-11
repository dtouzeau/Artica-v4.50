<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
startx();
function startx()
{
    $tpl=new template_admin();
    if (!isset($_GET["ip"])) {
        die();
    }

    $ip = $_GET["ip"];

    $f[] = "192.168.0.0/16";
    $f[] = "10.0.0.0/8";
    $f[] = "172.16.0.0/12";

    foreach ($f as $cdir) {
        if (ip_in_range($ip, $cdir) ) {
            $tpl->squid_admin_mysql(0,"Hack! Wrong URI from $ip [action=nothing]",null,__FILE__,__LINE__);
            die("LocalNet");
        }

    }
    $ip = urlencode($ip);
    $tpl->squid_admin_mysql(0,"Hack! Wrong URI from $ip [action=Deny IP]",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("firehol.php?block-this=$ip");
    return true;
}

function ip_in_range( $ip, $range ) {
    if ( strpos( $range, '/' ) == false ) { $range .= '/32';}

    list( $range, $netmask ) = explode( '/', $range, 2 );
    $range_decimal = ip2long( $range );
    $ip_decimal = ip2long( $ip );
    $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}