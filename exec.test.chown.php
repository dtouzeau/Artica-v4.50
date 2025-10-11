<?php




$ldapConnection = ldap_connect("192.168.1.9", 389);
ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);

if(!$ldapConnection){echo "CONNECT FAILED\n";}

 if(!@ldap_bind($ldapConnection, "administrateur@touzeau.biz","P@$$"."word180872")){
 	echo "FAILED\n";
 }
 
 echo "SUCCESS\n";