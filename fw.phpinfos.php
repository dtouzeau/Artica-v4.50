<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
$users=new usersMenus();
if(!$users->AsArticaAdministrator){die();}
phpinfo();
?>