<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["proxies-table"])){proxies_table();exit;}
if(isset($_GET["proxies-newjs"])){rule_proxies_new_js();exit;}
if(isset($_GET["proxies-newjspop"])){rule_proxies_direct();exit;}
if(isset($_GET["rule-proxies-newpopup"])){rule_proxies_new_popup();exit;}
if(isset($_GET["rules-proxy-secure"])){rule_proxies_secure();exit;}
if(isset($_GET["rules-proxy-unlink"])){rule_proxies_unlink();exit;}
if(isset($_POST["gpid"])){rule_proxies_new_save();exit;}
xstart();

function rule_proxies_new_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["proxies-newjs"]);
    $title="{new_proxy}";
    unset($_GET["proxies-newjs"]);
    foreach ($_GET as $key=>$val){$parms[]="$key=".urlencode($val);}
    $zparms=@implode("&",$parms);


    if(isset($_GET["md"])){
        $title="{proxy}: N.{$_GET["md"]}";
    }

    $tpl->js_dialog5($title, "$page?rule-proxies-newpopup=$id&$zparms");
}
function rule_proxies_secure(){
    $tpl        = new template_admin();
    $gpid       = intval($_GET["gpid"]);
    $md=$_GET["rules-proxy-secure"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sligne=$q->mysqli_fetch_array("SELECT pacpxy FROM webfilters_sqgroups WHERE ID=$gpid");
    $pacpxy=unserializeb64($sligne["pacpxy"]);
    $ligne=$pacpxy[$md];
    $secure=intval($ligne["secure"]);
    if($secure==1){$secure=0;}else{$secure=1;}
    $pacpxy[$md]["secure"]=$secure;

    $pacpxy_ser=serialize($pacpxy);
    writelogs("UPDATE webfilters_sqgroups SET pacpxy='$pacpxy_ser' WHERE ID=$gpid",__FUNCTION__,__FILE__,__LINE__);
    $pacpxy_ser=base64_encode($pacpxy_ser);
    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET pacpxy='$pacpxy_ser' WHERE ID=$gpid");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    admin_tracks("Saving proxy.pac proxy secure=$secure for group id $gpid and proxy id $md");

}
function rule_proxies_unlink(){
    $tpl            = new template_admin();
    $zmd5           = $_GET["rules-proxy-unlink"];
    $zmd            = $_GET["zid"];
    $gpid           = intval($_GET["gpid"]);
    $q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $jsafter        = base64_decode($_GET["js-after"]);
    $RefreshTable   = base64_decode($_GET["RefreshTable"]);


    $sligne=$q->mysqli_fetch_array("SELECT pacpxy FROM webfilters_sqgroups WHERE ID=$gpid");
    $pacpxy=unserializeb64($sligne["pacpxy"]);

    unset($pacpxy[$zmd5]);

    $pacpxy_ser=serialize($pacpxy);
    $pacpxy_ser=base64_encode($pacpxy_ser);
    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET pacpxy='$pacpxy_ser' WHERE ID=$gpid");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }


    admin_tracks("Unlink proxy.pac proxy $zmd5");

    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "$('#$zmd').remove();
    $jsafter
    $RefreshTable
    ";


}

function rule_proxies_new_popup(){
    $tpl        = new template_admin();
    $gpid       = intval($_GET["gpid"]);
    $proxyserver= null;
    $proxyport  = 8080;
    $bt         = "{add}";
    $title=     "{new_proxy}";
    $jsafter        = base64_decode($_GET["js-after"]);
    $RefreshTable   = base64_decode($_GET["RefreshTable"]);


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sligne=$q->mysqli_fetch_array("SELECT pacpxy FROM webfilters_sqgroups WHERE ID=$gpid");
    $pacpxy=unserializeb64($sligne["pacpxy"]);

    if(isset($_GET["md"])){
        $ligne=$pacpxy[$_GET["md"]];
        $tpl->field_hidden("md",$_GET["md"]);
        $proxyserver=$ligne["hostname"];
        $proxyport=$ligne["port"];
        $bt="{apply}";
        $title="$proxyserver {listen_port} $proxyport";
    }
    unset($_GET["rule-proxies-newpopup"]);
    unset($_GET["md"]);


    $js[]="dialogInstance5.close()";
    $js[]="RefreshProxiesInAclGroup()";
    $js[]=$jsafter;
    $js[]=$RefreshTable;

    $form[]=$tpl->field_hidden("gpid", $gpid);
    $form[]=$tpl->field_text("hostname", "{hostname}", $proxyserver,true);
    $form[]=$tpl->field_numeric("port","{port}",$proxyport);
    $form[]=$tpl->field_checkbox("secure","{UseSSL}",intval($ligne["secure"]));

    echo $tpl->form_outside($title, @implode("\n", $form),null,$bt, @implode(";", $js),"AsSquidAdministrator",true);
}

function rule_proxies_direct():bool{
    $gpid=intval($_GET["proxies-newjspop"]);
    $hostname="0.0.0.0";
    $port=0;
    $secure=0;
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sligne=$q->mysqli_fetch_array("SELECT pacpxy FROM webfilters_sqgroups WHERE ID=$gpid");
    $data=base64_decode($sligne["pacpxy"]);
    writelogs("$gpid - $data",__FUNCTION__,__FILE__,__LINE__);
    $pacpxy=unserialize($data);
    $md=time();
    $pacpxy[$md]["hostname"]=$hostname;
    $pacpxy[$md]["port"]=$port;
    $pacpxy[$md]["secure"]=$secure;
    $pacpxy_ser=serialize($pacpxy);
    writelogs("UPDATE webfilters_sqgroups SET pacpxy='$pacpxy_ser' WHERE ID=$gpid",__FUNCTION__,__FILE__,__LINE__);
    $pacpxy_ser=base64_encode($pacpxy_ser);
    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET pacpxy='$pacpxy_ser' WHERE ID=$gpid");
    if(!$q->ok){
        echo "jserror:".$q->mysql_error;
        return false;
    }

    $js1=null;
    $js2=null;
    $jsafter=$_GET["js-after"];
    $RefreshTable=$_GET["RefreshTable"];

    if($jsafter<>null){
        $js1=base64_decode($jsafter);
    }
    if($RefreshTable<>null){
        $js2=base64_decode($RefreshTable);
    }

    header("content-type: application/x-javascript");
    echo "$js1\n";
    echo "$js2\n";
    echo "RefreshProxiesInAclGroup();\n";

    admin_tracks("Saving proxy.pac proxy $hostname:$port for group id $gpid");
    return true;

}
function rule_proxies_new_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $gpid=intval($_POST["gpid"]);


    $hostname=$_POST["hostname"];
    $port=intval($_POST["port"]);
    if($port==0){$port="3128";}
    if($hostname==null){$hostname="1.2.3.4";}
    $secure=$_POST["secure"];

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sligne=$q->mysqli_fetch_array("SELECT pacpxy FROM webfilters_sqgroups WHERE ID=$gpid");
    $data=base64_decode($sligne["pacpxy"]);
    writelogs("$gpid - $data",__FUNCTION__,__FILE__,__LINE__);
    $pacpxy=unserialize($data);

    if(!is_array($pacpxy)){$pacpxy=array();}
    if(!isset($_POST["md"])) {$_POST["md"]=time();}
    $md=intval($_POST["md"]);

    $pacpxy[$md]["hostname"]=$hostname;
    $pacpxy[$md]["port"]=$port;
    $pacpxy[$md]["secure"]=$secure;

    $pacpxy_ser=serialize($pacpxy);
    writelogs("UPDATE webfilters_sqgroups SET pacpxy='$pacpxy_ser' WHERE ID=$gpid",__FUNCTION__,__FILE__,__LINE__);
    $pacpxy_ser=base64_encode($pacpxy_ser);
    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET pacpxy='$pacpxy_ser' WHERE ID=$gpid");


    if(!$q->ok){
        echo "jserror:".$q->mysql_error;
        return false;
    }

    admin_tracks("Saving proxy.pac proxy $hostname:$port for group id $gpid");
    return true;
}

function xstart(){
    $page           = CurrentPageName();
    $time           = time();
    foreach ($_GET as $key=>$val){$parms[]="$key=".urlencode($val);}
    $zparms=@implode("&",$parms);
    $html="<div id='rule-proxies-$time' style='margin-top:20px'></div>
	<script>
	    function RefreshProxiesInAclGroup(){
	        LoadAjaxSilent('rule-proxies-$time','$page?proxies-table=yes&$zparms');
	    }
	    
	    RefreshProxiesInAclGroup();
	 </script>";
    echo $html;
}
function proxies_table() {
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $parms          = array();
    $gpid           = intval($_GET["gpid"]);

    unset($_GET["proxies-table"]);
    foreach ($_GET as $key=>$val){$parms[]="$key=".urlencode($val);}
    $zparms=@implode("&",$parms);

    $add="Loadjs('$page?proxies-newjs=$gpid&$zparms');";
    $nop="Loadjs('$page?proxies-newjspop=$gpid&$zparms');";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_proxy} </label>";
    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$nop\"><i class='fa-solid fa-ban'></i> {direct_to_internet} </label>";
    $html[]="</div>";
    $html[]="<table id='table-proxypac-proxies-$gpid' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{proxy}</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>Secure Proxy</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $ligne=$q->mysqli_fetch_array("SELECT pacpxy FROM webfilters_sqgroups WHERE ID=$gpid");
    $pacpxy=unserializeb64($ligne["pacpxy"]);


    $TRCLASS=null;
    foreach($pacpxy as $index=>$ligne) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $proxyserver=$ligne["hostname"];
        $proxyport=$ligne["port"];
        $mkey=$index;
        $secure_ico=null;
        $zmd5=md5(serialize($ligne));

        $proxyserver=$tpl->td_href($proxyserver,null,"Loadjs('$page?proxies-newjs=$mkey&md=$mkey&$zparms')");
        $delete=$tpl->icon_delete("Loadjs('$page?rules-proxy-unlink=$mkey&zid=$zmd5&$zparms')");
        $secure=$tpl->icon_check($ligne["secure"],"Loadjs('$page?rules-proxy-secure=$mkey&$zparms')");
        if(intval($ligne["secure"])==1){$secure_ico="&nbsp;<span class='label label-primary'>Secure Proxy</span>";}

        $html[]="<tr class='$TRCLASS' id='$zmd5'>";
        $html[]="<td><strong>{$proxyserver}:{$proxyport}{$secure_ico}</strong></td>";
        $html[]="<td width=1% nowrap>$secure</td>";
        $html[]="<td width=1% class='center' nowrap>$delete</center></td>";
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
	$(document).ready(function() { $('#table-proxypac-proxies-$gpid').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}