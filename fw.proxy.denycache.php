<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["ztype"][0]="{dstdomain}";
$GLOBALS["ztype"][1]="{dst}";
$GLOBALS["ztype"][2]="{src}";

if(isset($_GET["main"])){main();exit;}
if(isset($_GET["deny-cache-js"])){deny_cache_js();exit;}
if(isset($_GET["deny-cache-popup"])){deny_cache_popup();exit;}
if(isset($_GET["deny-cache-delete"])){deny_cache_delete();exit;}

if(isset($_GET["pattern-import-popup"])){pattern_import_popup();exit;}
if(isset($_GET["pattern-import-js"])){pattern_import_js();exit;}
if(isset($_POST["import"])){pattern_import_save();exit;}

if(isset($_POST["item"])){add_nocache_save();exit;}

page();



function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("/proxy/acls/denycache",
        "squid.nocache.progress",
        "squid.nocache.log",
        "progress-firehol-restart");

    $add="Loadjs('$page?deny-cache-js=');";
    $import="Loadjs('$page?pattern-import-js=yes');";

    $bts[]="<p>{notcaching_websites}</p><div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$import\"><i class='fas fa-file-import'></i> {import} </label>";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart\"><i class='fas fa-retweet'></i> {apply_rules} </label>";

    $bts[]="</div>";



    $html=$tpl->page_header("{deny_from_cache}","fa fa-ban",@implode("",$bts),"$page?main=yes","proxy-deny-cache","progress-firehol-restart",false,"table-loader");

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);



}

function deny_cache_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$title=$_GET["deny-cache-js"];
	$items=$_GET["deny-cache-js"];
	if($items==null){$title="{new_item}";}
	$itemsenc=urlencode($items);
	$tpl->js_dialog($title, "$page?deny-cache-popup=$itemsenc");
}

function deny_cache_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$title=$_GET["deny-cache-popup"];
	$items=$_GET["deny-cache-popup"];
	$btn="{add}";
	$jsAdd=";BootstrapDialog1.close()";
	$ligne=[];
	if($items==null){
		$form[]=$tpl->field_text("item", "{item}", null);
		$title="{new_item}";
	}else{
		$jsAdd=null;
		$btn="{apply}";
		$row=acls_api_get("/proxy/deny-cache/".urlencode($items));
		if(is_array($row)){$ligne=$row;}
		$tpl->field_hidden("item", $items);
	}
	$form[]=$tpl->field_array_hash($GLOBALS["ztype"], "ztype", "{type}", intval($ligne["ztype"]));
	echo $tpl->form_outside($title,@implode("\n", $form),"{squid_ask_domain}<br>{deny_from_cache_explain}",$btn,"LoadAjax('table-loader','$page?main=yes');$jsAdd","AsSquidAdministrator");


}

function pattern_import_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("\n",$_POST["import"]);

    $acl=new squid_acls();
    $IP=new IP();

    foreach($tb as $item) {
        $www=trim(strtolower($item));
        if($www==null){continue;}
        acls_api_send("DELETE","/proxy/deny-cache/".urlencode($www));
        $ztype=0;
        if($IP->isIPAddressOrRange($www)){
            $ztype=1;
            list($ok,$err)=acls_api_send("POST","/proxy/deny-cache",["items"=>$www,"ztype"=>$ztype]);
            if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return;}
            continue;
        }
        $www=$tpl->CleanWebSite($www);
        if(substr($www,0, 1)<>"^"){$www=$acl->dstdomain_parse($www);}
        list($ok,$err)=acls_api_send("POST","/proxy/deny-cache",["items"=>$www,"ztype"=>$ztype]);
        if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return;}
    }
}

function deny_cache_delete(){
    $item=$_GET["deny-cache-delete"];
    list($ok,$err)=acls_api_send("DELETE","/proxy/deny-cache/".urlencode($item));
    if(!$ok){
        $tp=new template_admin();
        $tp->js_mysql_alert($err);
        return;
    }
    header("content-type: application/x-javascript");
    echo "$('#{$_GET["md"]}').remove()";
}

function pattern_import_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $title="{import}";
    $tpl->js_dialog($title, "$page?pattern-import-popup=yes");
}
function pattern_import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsafter="BootstrapDialog1.close();LoadAjax('table-loader','$page?main=yes');";
    $bt="{import}";
    $title="{bulk_import}";
    $form[]=$tpl->field_textarea("import","{websites}", null);
    echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsProxyMonitor",true);
}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$TRCLASS=null;

	$t=time();

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>{items}</th>";
	$html[]="<th>{type}</th>";
	$html[]="<th class='center'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='denycache-rows'>";

	$rows=acls_api_get("/proxy/deny-cache");
	if(!is_array($rows)){$rows=[];}

	foreach ($rows as $ligne){
		$TRCLASS=null;
		$md=md5(serialize($ligne));

		$itemenc=urlencode($ligne["items"]);
		$item=$tpl->td_href($ligne["items"],null,"Loadjs('$page?deny-cache-js=$itemenc');");

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td nowrap>$item</td>";
		$html[]="<td style='width:1%' nowrap>".$GLOBALS["ztype"][intval($ligne["ztype"])]."</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('$page?deny-cache-delete=$itemenc&md=$md')","AsSquidAdministrator")."</td>";
		$html[]="</tR>";
	}


	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='denycache-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"denycache-rows","denycache-pager");


}

function add_nocache_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$acl=new squid_acls();
	$IP=new IP();

	$www=strtolower($_POST["item"]);
	if($www==null){
		echo $tpl->_ENGINE_parse_body("{DOMAIN_CANNOT_BE_NULL}");
		return;
	}
	$ztype=intval($_POST["ztype"]);

	acls_api_send("DELETE","/proxy/deny-cache/".urlencode($_POST["item"]));
	if($IP->isIPAddressOrRange($www)){if($ztype==0){$ztype=1;}}else{
		$www=$tpl->CleanWebSite($www);
		if(substr($www,0, 1)<>"^"){$www=$acl->dstdomain_parse($www);}
	}

	if($www==null){
		echo $tpl->_ENGINE_parse_body("{DOMAIN_CANNOT_BE_NULL}");
		return;
	}

	list($ok,$err)=acls_api_send("POST","/proxy/deny-cache",["items"=>$www,"ztype"=>$ztype]);
	if(!$ok){echo $err;return;}


}
