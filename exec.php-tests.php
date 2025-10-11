<?php

if(class_exists("SNMP")){echo "SNMP OK\n";}else{echo "SNMP FAILED!\n";}
if(function_exists("posix_getuid")){echo "posix_getuid OK\n";}
if(function_exists("pg_connect")){echo "pg_connect OK\n";}
if(function_exists("mysql_fetch_array")){echo "mysql_fetch_array OK\n";}else{echo "mysql_fetch_array FAILED!\n";}
if(function_exists("mysqli_connect")){echo "mysqli_connect OK\n";}else{echo "mysqli_connect FAILED!\n";}
?>