<?php
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.mysql.xapian.builder.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');

$EnableDHCPServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer");
if($EnableDHCPServer==0){
    die();
}


$qlite=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
$dhcpd_MacsList=$qlite->COUNT_ROWS("dhcpd_MacsList");
echo "$dhcpd_MacsList rows\n";
if($dhcpd_MacsList>10000){
    $sock=new sockets();
    $sock->SET_INFO("MACVENDORS_FILLED",1);
    die();
}
echo "[".__LINE__."]: Loading macvendors...\n";
include_once(dirname(__FILE__)."/ressources/class.macvendors.inc");
$MACS=GetMacVendors();
echo "[".__LINE__."]: Migrate table [".count($MACS). "] items to import\n";
foreach ($MACS as $MacID=>$description) {
    $description = $qlite->sqlite_escape_string2($description);
    $description = str_replace("\t", " ", $description);
    $description = str_replace("  ", " ", $description);
    $description = str_replace("  ", " ", $description);
    $description = str_replace("  ", " ", $description);
    $f[] = "('$MacID','$description')";
    if (count($f) > 1000) {
        echo "[" . __LINE__ . "]: Migrate table dhcpd_MacsList 500+\n";
        $sql = "INSERT OR IGNORE INTO `dhcpd_MacsList` (MacID,description) VALUES " . @implode(",", $f);
        $qlite->QUERY_SQL($sql);
        if (!$qlite->ok) {
        echo "[" . __LINE__ . "]: Migrate table dhcpd_MacsList $qlite->mysql_error\n$sql\n";
        $f = array();
        break;
    }
    $f = array();
    }
}


if(count($f)>0){
    $qlite->QUERY_SQL("INSERT OR IGNORE INTO dhcpd_MacsList (MacID,description) VALUES ".@implode(",",$f));
    if(!$qlite->ok){echo "[".__LINE__."]: Migrate table dhcpd_MacsList $qlite->mysql_error\n";}
}