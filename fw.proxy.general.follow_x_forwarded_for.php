<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["server-add"])){server_add();exit;}
if(isset($_GET["popup"])){popup_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["haip"])){server_save();exit;}
if(isset($_GET["server-check"])){server_check();exit;}
if(isset($_GET["server-delete"])){server_delete();exit;}
if(isset($_POST["server-delete"])){server_delete_real();exit;}

js();

function js(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->js_dialog5("{follow_x_forwarded_for}","$page?popup=yes");
}
function popup_start(){
    $page   = CurrentPageName();
    $html   = "<div id='follow_x_forwarded_for-table' style='margin-top: 10px'></div><script>LoadAjax('follow_x_forwarded_for-table','$page?table=yes');</script>";
    echo $html;
}
function server_delete(){
    $id=intval($_GET["server-delete"]);
    $md=$_GET["md"];
    $tpl    = new template_admin();
    $q      = new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne  = $q->mysqli_fetch_array("SELECT * FROM squid_balancers WHERE ID=$id");
    $ipsrc  = $ligne["ipsrc"];
    $tpl->js_confirm_delete($ipsrc,"server-delete",$id,"$('#$md').remove()");

}
function server_delete_real(){
    $id=intval($_POST["server-delete"]);
    $q      = new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM squid_balancers WHERE ID=$id");
    if(!$q->ok){echo $q->mysql_error;return;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/general/nohup/restart");

}


function server_check(){
    $id     = intval($_GET["server-check"]);
    $q      = new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $tpl    = new template_admin();

    $ligne=$q->mysqli_fetch_array("SELECT * FROM squid_balancers WHERE ID=$id");
    if(intval($ligne["enabled"])==1){
        $q->QUERY_SQL("UPDATE squid_balancers SET enabled=0 WHERE ID=$id");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/general/nohup/restart");
        return;
    }
    $q->QUERY_SQL("UPDATE squid_balancers SET enabled=1 WHERE ID=$id");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/general/nohup/restart");
}

function server_add(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->js_prompt("{new_server}","{server_ip_address}","fas fa-server",$page,"haip","LoadAjax('follow_x_forwarded_for-table','$page?table=yes');",null,"");

}

function server_save(){
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $IPClass    = new IP();

    if(!$IPClass->isIPAddressOrRange($_POST["haip"])){
        echo "{$_POST["haip"]} Wrong format";
        return;
    }

    $sql        =   "INSERT INTO squid_balancers (ipsrc,enabled) VALUES ('{$_POST["haip"]}',1)";
    $q          =   new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/general/nohup/restart");

}


function table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();

    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{new_server}", "Loadjs('$page?server-add=yes')", "fas fa-plus-circle","AsSquidAdministrator")."</div>");

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{servers}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{enabled}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT * FROM squid_balancers ORDER BY ipsrc";
     $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

    $sql="CREATE TABLE IF NOT EXISTS `squid_balancers` (
			`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`ipsrc` VARCHAR( 255 ) NOT NULL ,
			`enabled` INT( 1 ) NOT NULL DEFAULT '1',
			INDEX ( `enabled`,`ipsrc` )) ENGINE=MYISAM;";

//	print_r($hash_full);
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID         = $ligne["ID"];
        $md         = md5(serialize($ligne));
        $ipsrc      = $ligne["ipsrc"];
        $enabled    = $ligne["enabled"];
        $delete     = $tpl->icon_delete("Loadjs('$page?server-delete=$ID&md=$md')","AsSquidAdministrator");
        $check      = $tpl->icon_check($enabled,"Loadjs('$page?server-check=$ID')",null,"AsSquidAdministrator");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='90%' nowrap><i class='fas fa-server'></i>&nbsp;<strong>$ipsrc</strong></td>";
        $html[]="<td width='1%' nowrap>$check</td>";
        $html[]="<td class=\"center\" width=1% nowrap>$delete</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));




}

