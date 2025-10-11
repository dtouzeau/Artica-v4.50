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
$field="SquidCert";
$sql="SELECT $field  FROM sslcertificates WHERE CommonName='$CommonName'";
$q=new mysql();
$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
$data=$ligne[$field];
$fsize=strlen($data);
header('Content-type: application/x-x509-ca-cert');
header('Content-Transfer-Encoding: binary');
header("Content-Disposition: attachment; filename=\"$CommonName.crt\"");
header("Pragma: public");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
header("Content-Length: ".$fsize);
ob_clean();
flush();
echo $data;

