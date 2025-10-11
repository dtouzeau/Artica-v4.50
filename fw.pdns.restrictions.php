<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["main"])){main();exit;}
if(isset($_GET["newitem-js"])){newitem_js();exit;}
if(isset($_GET["new-item-popup"])){newitem_popup();exit;}
if(isset($_POST["newitem"])){newitem_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){newitem_delete();exit;}
if(isset($_GET["tinypage-dnsdist"])){tinypage_dnsdist();exit;}
if(isset($_GET["tinypage-unbound"])){tinypage_unbound();exit;}
if(isset($_GET["ViaSpopup"])){ViaSpopup();exit;}
page();



function newitem_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_item}");
    $tinydnsdist=null;
    if(isset($_GET["tinydnsdist"])){
        $tinydnsdist="&tinydnsdist=yes";
    }
	return $tpl->js_dialog3($title, "$page?new-item-popup=yes&$tinydnsdist");
	
}
function ViaSpopup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{networks_restrictions}");
    return $tpl->js_dialog($title, "$page?tinypage-dnsdist=yes");
}

function delete_js():bool{
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$id=$_GET["delete-js"];
	$ask=$tpl->javascript_parse_text("{delete} $id");
    $tinydnsdist="";
    if(isset($_GET["tinydnsdist"])){
       $tinydnsdist="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');";
    }
	
	$html="			
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}	
	$('#{$_GET["id"]}').remove();
	$tinydnsdist
}
function Save$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$id');
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
	
Save$t()";
	echo $html;
	return true;
}



function newitem_popup():bool{
		$page=CurrentPageName();
		$tpl=new template_admin();
        $tt[]="dialogInstance3.close();";
        if(isset($_GET["tinydnsdist"])){
            $tt[]="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');";
            $tt[]="LoadAjax('table-loader-recursor','$page?main=yes&tinydnsdist=yes');";
        }else{
            $tt[]="LoadAjax('table-loader-recursor','$page?main=yes');";
        }

    $js=@implode("",$tt);
        $form[]=$tpl->field_text("newitem", "{network_item}", null);
		$html[]=$tpl->form_outside(null,  $form,"{PowerDNS-allow-from}<hr>{pdns_network_item_add}","{add}",
				$js,"AsDnsAdministrator");
		echo $tpl->_ENGINE_parse_body( $html);
        return true;
}
function newitem_save():bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$item=url_decode_special_tool($_POST["newitem"]);
    $item=$tpl->CLEAN_BAD_CHARSNET($item);

	if($item==null){
		echo $tpl->post_error("No posted data");
		return false;
	}
	$sql="INSERT OR IGNORE INTO pdns_restricts (`address`) VALUES('$item')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    return admin_tracks_post("Adding DNS Network restrictions");
}
function newitem_delete():bool{
    $tpl=new template_admin();
	$item=url_decode_special_tool($_POST["delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("DELETE FROM pdns_restricts WHERE address='$item'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Remove $item network DNS restriction");

}

function tinypage_dnsdist():bool{
    $page=CurrentPageName();
    $html[]="<div id='progress-recursor-restart'></div>";
    $html[]="<div id='table-loader-recursor'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('table-loader-recursor','$page?main=yes&tinydnsdist=yes');";
    $html[]="</script>";

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tinypage_unbound(){
    $page=CurrentPageName();
    $html[]="<div id='progress-recursor-restart'></div>";
    $html[]="<div id='table-loader-recursor'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('table-loader-recursor','$page?main=yes&tinyunbound=yes');";
    $html[]="</script>";

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><p>{PowerDNS-allow-from}</p></div>
	</div>



	<div class='row'><div id='progress-recursor-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-recursor'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-recursor','$page?main=yes');

	</script>";


	echo $tpl->_ENGINE_parse_body($html);

}
function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$database='artica_backup';
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $BUTTON=true;
    if(isset($_GET["tinydnsdist"])) {$BUTTON=false;}


    $jsrestart=$tpl->framework_buildjs("pdns.php?restart-recusor=yes",
    "recusor.restart.progress",
    "recusor.restart.log",
    "progress-recursor-restart",
    "document.getElementById('progress-recursor-restart').innerHTML='';");


	if($UnboundEnabled==1){
        $jsrestart=$tpl->framework_buildjs("/unbound/restart",
            "unbound.restart.progress","unbound.restart.log",
                "progress-recursor-restart",
            "document.getElementById('progress-recursor-restart').innerHTML='';");
    }

    if($EnableDNSDist==1){
        $jsrestart=$tpl->framework_buildjs("/dnsfw/service/php/restart",
            "dnsdist.restart","dnsdist.restart.log",
            "progress-recursor-restart",
            "document.getElementById('progress-recursor-restart').innerHTML='';"
        );

    }


	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
    $DelAdd="";
    if(isset($_GET["tinydnsdist"])) {
        $topbuttons[] = array("Loadjs('$page?newitem-js=yes&tinydnsdist=yes')", ico_plus, $new_entry);
        $topbuttons[] = array($jsrestart, ico_save, "{reconfigure_service}");
        $html[]=$tpl->th_buttons($topbuttons);
        $DelAdd="&tinydnsdist=yes";
    }


	$buttons=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?newitem-js=yes');\"><i class='fa fa-plus'></i> $new_entry </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {reconfigure_service} </label>
			</div>");


	$html[]="<table id='table-dns-forward-zones' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";


	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$items}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{$delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	$sql="SELECT * FROM pdns_restricts";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	$c=0;


	if(count($results)==0){
        $ACLREST[] = "192.168.0.0/16";
        $ACLREST[] = "10.0.0.0/8";
        $ACLREST[] = "172.16.0.0/12";
        $ACLREST[] = "127.0.0.0/8";

        foreach ($ACLREST as $address){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $md=md5(serialize($address));
            $item=$address;
            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td><strong>$item</strong></td>";
            $html[]="<td style='vertical-align:middle' width=1%></td>";
            $html[]="</tr>";
        }

    }

	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$item=$ligne["address"];
		$item_encode=urlencode($item);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>$item</strong></td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?delete-js=$item_encode&id=$md$DelAdd')","AsDnsAdministrator")."</td>";
		$html[]="</tr>";
		$c++;
	}
	
	if($c==0){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize(time()));
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>{PowerDNS-allow-from-default}</td>";
		$html[]="<td style='vertical-align:middle'></center></td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $jstiny=null;

    if(isset($_GET["tinyunbound"])){
        $title = "{networks_restrictions}";
        $TINY_ARRAY["TITLE"] =$title;
        $TINY_ARRAY["ICO"] = "fa fas fa-database";
        $TINY_ARRAY["EXPL"] = "{PowerDNS-allow-from}";
        $TINY_ARRAY["BUTTONS"] = $buttons;
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    }




	$html[]="
<script>
    $jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-dns-forward-zones').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}