#!/usr/bin/php -n
<?php
$datas=unserialize(@file_get_contents("/etc/squid3/sslpass"));
echo $datas[$argv[1]];
?>