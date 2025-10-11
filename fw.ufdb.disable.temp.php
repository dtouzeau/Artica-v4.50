<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


js();

function js():bool{

   $tpl=new template_admin();
   $UfdbGuardDisabledTemp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardDisabledTemp"));
   if($UfdbGuardDisabledTemp==1){
       $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbGuardDisabledTemp",0);
       admin_tracks("Turn ON the Web-Filtering engine");
   }else{
       admin_tracks("Turn OFF the Web-Filtering engine");
       $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbGuardDisabledTemp",1);
   }


   $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/filter"));
   if(!$json->Status){
       $tpl->js_error($json->Error);
       return false;
   }
   echo "Loadjs('fw.sidebar.php?call=yes');\nLoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
   return true;
}