<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
$tpl=new templates();
$q=new mysql_squid_builder();
$sql="SELECT sslcertificate FROM proxy_ports WHERE UseSSL=1 ORDER BY ID DESC LIMIT 0,1";
$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql));
$CommonName=$ligne["sslcertificate"];
$f[]="[CERTIFICATE]";
$f[]="certificatename=$CommonName";
$f[]="";
echo @implode("\r\n", $f);

