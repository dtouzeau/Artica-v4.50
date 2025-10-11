<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');



$q=new postgres_sql();

// REMOVE proxy_traffic table..... ----------------------------------------------------------------------------

$firstDayOfMonth=strtotime('first day of this month', time());
$now=date("Y-m-d 00:00:00",$firstDayOfMonth);
echo "Remove entries before $now\n";
$sql="DELETE FROM proxy_traffic WHERE zdate < '$now'";
$results=$q->QUERY_SQL($sql);
echo "Done....\n";


echo "VACUUM FULL proxy_traffic\n";
$q->QUERY_SQL("VACUUM FULL proxy_traffic");
echo "Done....\n";
//---------------------------------------------------------------------------------------------------------------