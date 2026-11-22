<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Politique Meta : les proxys PAC d'un objet tenu (metaid) sont en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.proxypac.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["gpid"])){$m=proxyobject_meta_refuse_post(intval($_POST["gpid"]));if($m!==""){echo $m;exit;}}
foreach(array("proxies-newjs","proxies-newjspop","rules-proxy-secure","rules-proxy-unlink") as $k){
    if(!isset($_GET[$k])){continue;}
    $g=isset($_GET["gpid"])?intval($_GET["gpid"]):($k=="proxies-newjspop"?intval($_GET[$k]):0);
    if($g==0 && $k=="proxies-newjs"){$g=intval($_GET[$k]);}
    if(proxyobject_meta_refuse_js($g)){exit;}
}
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

    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!is_array($grp)){echo $tpl->js_error("Failed to load group $gpid");return false;}
    $pacpxy=unserializeb64($grp["pacpxy"]);
    $ligne=$pacpxy[$md];
    $secure=intval($ligne["secure"]);
    if($secure==1){$secure=0;}else{$secure=1;}
    $pacpxy[$md]["secure"]=$secure;

    $pacpxy_ser=base64_encode(serialize($pacpxy));
    writelogs("PUT pacpxy for group id $gpid secure=$secure",__FUNCTION__,__FILE__,__LINE__);
    list($ok,$err)=acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",["pacpxy"=>$pacpxy_ser]);
    if(!$ok){
        echo $tpl->js_error($err);
        return false;
    }
    admin_tracks("Saving proxy.pac proxy secure=$secure for group id $gpid and proxy id $md");

}
function rule_proxies_unlink(){
    $tpl            = new template_admin();
    $zmd5           = $_GET["rules-proxy-unlink"];
    $zmd            = $_GET["zid"];
    $gpid           = intval($_GET["gpid"]);
    $jsafter        = jsafter_sanitize(base64_decode($_GET["js-after"]));
    $RefreshTable   = jsafter_sanitize(base64_decode($_GET["RefreshTable"]));

    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!is_array($grp)){echo $tpl->js_error("Failed to load group $gpid");return false;}
    $pacpxy=unserializeb64($grp["pacpxy"]);

    unset($pacpxy[$zmd5]);

    $pacpxy_ser=base64_encode(serialize($pacpxy));
    list($ok,$err)=acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",["pacpxy"=>$pacpxy_ser]);
    if(!$ok){
        echo $tpl->js_error($err);
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
    $jsafter        = jsafter_sanitize(base64_decode($_GET["js-after"]));
    $RefreshTable   = jsafter_sanitize(base64_decode($_GET["RefreshTable"]));

    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    $pacpxy=unserializeb64(is_array($grp) ? $grp["pacpxy"] : "");

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

    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    $data=base64_decode(is_array($grp) ? $grp["pacpxy"] : "");
    writelogs("$gpid - $data",__FUNCTION__,__FILE__,__LINE__);
    $pacpxy=unserialize($data);
    $md=time();
    $pacpxy[$md]["hostname"]=$hostname;
    $pacpxy[$md]["port"]=$port;
    $pacpxy[$md]["secure"]=$secure;
    $pacpxy_ser=base64_encode(serialize($pacpxy));
    writelogs("PUT pacpxy for group id $gpid",__FUNCTION__,__FILE__,__LINE__);
    list($ok,$err)=acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",["pacpxy"=>$pacpxy_ser]);
    if(!$ok){
        echo (new template_admin())->post_error("$err");
        return false;
    }

    $js1=null;
    $js2=null;
    $jsafter=jsafter_token($_GET["js-after"]);
    $RefreshTable=jsafter_token($_GET["RefreshTable"]);

    if($jsafter<>null){
        $js1=base64_decode($jsafter);
    }
    if($RefreshTable<>null){
        $js2=jsafter_sanitize(base64_decode($RefreshTable));
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

    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    $data=base64_decode(is_array($grp) ? $grp["pacpxy"] : "");
    writelogs("$gpid - $data",__FUNCTION__,__FILE__,__LINE__);
    $pacpxy=unserialize($data);

    if(!is_array($pacpxy)){$pacpxy=array();}
    if(!isset($_POST["md"])) {$_POST["md"]=time();}
    $md=intval($_POST["md"]);

    $pacpxy[$md]["hostname"]=$hostname;
    $pacpxy[$md]["port"]=$port;
    $pacpxy[$md]["secure"]=$secure;

    $pacpxy_ser=base64_encode(serialize($pacpxy));
    writelogs("PUT pacpxy for group id $gpid",__FUNCTION__,__FILE__,__LINE__);
    list($ok,$err)=acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",["pacpxy"=>$pacpxy_ser]);

    if(!$ok){
        echo (new template_admin())->post_error("$err");
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
    $page=CurrentPageName();
    $tpl=new template_admin();
    $parms          = array();
    $gpid           = intval($_GET["gpid"]);

    unset($_GET["proxies-table"]);
    foreach ($_GET as $key=>$val){$parms[]="$key=".urlencode($val);}
    $zparms=@implode("&",$parms);

    $add="Loadjs('$page?proxies-newjs=$gpid&$zparms');";
    $nop="Loadjs('$page?proxies-newjspop=$gpid&$zparms');";
    $metaHeld=proxyobject_meta_held($gpid);
    $psec=$metaHeld?"FalseLicense":null;
    if($metaHeld){
        $html[]=proxyobject_meta_banner($tpl,$gpid);
    }else{
        $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
        $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_proxy} </label>";
        $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$nop\"><i class='fa-solid fa-ban'></i> {direct_to_internet} </label>";
        $html[]="</div>";
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{proxy}</th>";
    $html[]="<th class='awc-nowrap'>Secure Proxy</th>";
    $html[]="<th>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='rulesitemspa-rows'>";

    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    $pacpxy=unserializeb64(is_array($grp) ? $grp["pacpxy"] : "");


    $TRCLASS=null;
    foreach($pacpxy as $index=>$ligne) {
        $TRCLASS=null;
        $proxyserver=$ligne["hostname"];
        $proxyport=$ligne["port"];
        $mkey=$index;
        $secure_ico=null;
        $zmd5=md5(serialize($ligne));

        $proxyserver=$tpl->td_href($proxyserver,null,"Loadjs('$page?proxies-newjs=$mkey&md=$mkey&$zparms')");
        $delete=$tpl->icon_delete("Loadjs('$page?rules-proxy-unlink=$mkey&zid=$zmd5&$zparms')",$psec);
        $secure=$tpl->icon_check($ligne["secure"],"Loadjs('$page?rules-proxy-secure=$mkey&$zparms')",null,$psec);
        if(intval($ligne["secure"])==1){$secure_ico="&nbsp;<span class='label label-primary'>Secure Proxy</span>";}

        $html[]="<tr class='$TRCLASS' id='$zmd5'>";
        $html[]="<td><strong>{$proxyserver}:{$proxyport}{$secure_ico}</strong></td>";
        $html[]="<td style='width:1%' nowrap>$secure</td>";
        $html[]="<td width=1% class='center' nowrap>$delete</center></td>";
        $html[]="</tr>";

    }


    $html[]="</tbody>";
    $html[]="";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='rulesitemspa-pager'></span></div></div></div>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"rulesitemspa-rows","rulesitemspa-pager");
}
