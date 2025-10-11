<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){main();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["newdomain-js"])){newdomain_js();exit;}
if(isset($_GET["new-domain-popup"])){newdomain_popup();exit;}
if(isset($_POST["newdomain"])){newdomain_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){domain_delete();exit;}
if(isset($_GET["domain-js"])){domain_js();exit;}
if(isset($_GET["domain-popup"])){domain_popup();exit;}
if(isset($_POST["domainid"])){domain_save();exit;}
if(isset($_GET["ddns-agent-status"])){ddns_status();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["service-popup"])){service_popup();exit;}
if(isset($_POST["DDNSInterface"])){service_save();exit;}
if(isset($_GET["secretkey-js"])){secretkey_js();exit;}
if(isset($_GET["secretkey-start"])){secretkey_start();exit;}
if(isset($_GET["secretkey-popup"])){secretkey_popup();exit;}
page();



function secretkey_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["id"];
    $value=urlencode($_GET["value"]);
    $suffix_form=$_GET["suffix-form"];
    $tpl->js_dialog11("TSIG Key", "$page?secretkey-start=yes&id=$id&value=$value&suffix-form=$suffix_form");
}
function secretkey_start(){
    $id=$_GET["id"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $value=urlencode($_GET["value"]);
    $suffix_form=$_GET["suffix-form"];
    $html[]="<div id='secret-$id'></div>";

    $js="LoadAjax('secret-$id','$page?secretkey-popup=yes&id=$id&value=$value&suffix-form=$suffix_form');";

    $refresh=$tpl->button_autnonome("{refresh}",$js,ico_refresh,"",335,"btn-primary");
    $html[]="<div style='margin-top:30px;text-align: right'>$refresh</div>";
    $html[]="<script>$js</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function secretkey_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["id"];
    $value=$_GET["value"];
    $prc1="style='width:1%;text-align:right;padding-right:10px;padding-left:10px' nowrap";
    $suffix_form=$_GET["suffix-form"];
    $Aivailable="HMAC-MD5,HMAC-SHA1,HMAC-SHA224,HMAC-SHA256,HMAC-SHA384,HMAC-SHA512";


    $idF=md5($value.$id);
    $js[]="function Main$id(){";
    $js[]="val=document.getElementById('$idF').value;";
    $js[]="document.getElementById('$id').value=val;";
    $js[]="dialogInstance11.close();";
    $js[]="}";

    $button="<button class='btn btn-primary btn-xs' OnClick=\"Main$id()\">{select}</button>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr style='height:90px'>";
    $html[]="<td $prc1><strong>{current}/{edit}:</strong></td>";
    $html[]="<td><input type='text' id='$idF' name='$idF' value='$value'\" class='form-control'></td>";
    $html[]="<td $prc1>$button</td>";
    $html[]="</tr>";


    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crypt/tsigkeys"));
    $Aivailable=explode(",",$Aivailable);
    foreach ($Aivailable as $algo){
        $pass=$data->Info->{$algo};
        $idF=md5($pass);
        $value=base64_encode("$algo|$pass");
        $js[]="function Main$idF(){";
        $js[]="document.getElementById('$id').value=base64_decode('$value');";
        $js[]="dialogInstance11.close();";
        $js[]="}";

        $button="<button class='btn btn-primary btn-xs' OnClick=\"Main$idF()\">{select}</button>";


        $html[]="<tr style='height: 43px'>";
        $html[]="<td $prc1>{key} $algo:</td>";
        $html[]="<td><input type='text' id='$idF' name='$idF' value='$pass'\" class='form-control' readonly></td>";
        $html[]="<td $prc1>$button</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="<script>";
    $html[]=@implode("\n",$js);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function newdomain_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=$_GET["function"];
	$title=$tpl->javascript_parse_text("{new_domain}");
	$tpl->js_dialog($title, "$page?new-domain-popup=yes&function=$function");
}
function service_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $title=$tpl->javascript_parse_text("{service-parameters}");
    $tpl->js_dialog($title, "$page?service-popup=yes&function=$function");
}
function service_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ddns/agent/restart");
}
function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px'>
            <div id='ddns-agent-status'></div>
            </td>";
    $html[]="<td style='vertical-align:top;width:99%;padding-left:15px'>";
    $html[]=$tpl->search_block($page);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function service_popup(){
    $tpl=new template_admin();
    $function=$_GET["function"];
    $DDNSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DDNSPort"));
    $DDNSInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DDNSInterface"));
    $form[]=$tpl->field_interfaces("DDNSInterface","{listen_interface}",$DDNSInterface);
    if($DDNSPort==0){
        $DDNSPort=8053;
    }
    $form[]=$tpl->field_numeric("DDNSPort","{listen_port}",$DDNSPort);
    $html[]=$tpl->form_outside("",$form,null,"{apply}",
        "$function();BootstrapDialog1.close();","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}
function domain_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $id=intval($_GET["domain-popup"]);
    $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM localdomains WHERE id=$id");
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $form[]=$tpl->field_hidden("domainid",$id);
    $form[]=$tpl->field_text("explainthis","{description}",$ligne["explainthis"]);
    $HMAC["HMAC-MD5"]="HMAC-MD5";
    $HMAC["HMAC-SHA1"]="HMAC-SHA1";
    $HMAC["HMAC-SHA224"]="HMAC-SHA224";
    $HMAC["HMAC-SHA256"]="HMAC-SHA256";
    $HMAC["HMAC-SHA384"]="HMAC-SHA384";
    $HMAC["HMAC-SHA512"]="HMAC-SHA512";
    $form[]=$tpl->field_checkbox("allowddnsupdate","DDNS",$ligne["allowddnsupdate"]);

    if(strlen($ligne["algorithm"])<3){
        $ligne["algorithm"]="HMAC-SHA256";
    }
    $form[]=$tpl->field_text("tsigkeyname","{key_name}",$ligne["tsigkeyname"]);

    $form[]=$tpl->field_text_button("secretkey","TSIG key",$ligne["secretkey"],false,"","{generate_key}");
    $form[]=$tpl->field_array_hash($HMAC,"algorithm","{algorithm}",$ligne["algorithm"]);
    $html[]=$tpl->form_outside("",$form,null,"{apply}",
        "$function();BootstrapDialog1.close();","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function domain_save():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $allowddnsupdate=$_POST["allowddnsupdate"];
    $id=intval($_POST["domainid"]);
    $secretkey=$_POST["secretkey"];
    $algorithm=$_POST["algorithm"];

    if(strpos($secretkey,"|")>0){
        $tb=explode("|",$secretkey);
        $secretkey=$tb[1];
        $algorithm=$tb[0];
    }


    $tsigkeyname=$_POST["tsigkeyname"];
    $_POST["explainthis"]=$q->sqlite_escape_string2($_POST["explainthis"]);
    $explainthis=$_POST["explainthis"];
    $sql="UPDATE localdomains SET allowddnsupdate=$allowddnsupdate,
                        tsigkeyname='$tsigkeyname',
                        secretkey='$secretkey',
                        algorithm='$algorithm',
                        explainthis='$explainthis'
                    WHERE id=$id";


    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks_post("Edit local DNS domain");

}

function delete_js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	 $tpl=new template_admin();
	$t=time();
	$ask=$tpl->javascript_parse_text("{pdns_delete_domain_ask}");	
	$id=$_GET["delete-js"];
	
	$q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
	$sql="SELECT name FROM domains WHERE id=$id";
	$ligne=$q->mysqli_fetch_array($q->QUERY_SQL($sql));
	$domain=$ligne["name"];
	$sql="SELECT COUNT(id) AS tcount FROM records WHERE domain_id=$id";
	$ligne=$q->mysqli_fetch_array($q->QUERY_SQL($sql));
	$items=intval($ligne["tcount"]);
	
	$ask=str_replace("%d", "$domain", $ask);
	$ask=str_replace("%c", "$items", $ask);
	
	
	$html="			
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}	
	$('#{$_GET["id"]}').remove();
}
function Save$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$id');
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
	
Save$t()";
	echo $html;
	
}

function domain_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $id=intval($_GET["domain-js"]);
    $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $ligne=$q->mysqli_fetch_array("SELECT domain FROM localdomains WHERE id=$id");
    return $tpl->js_dialog($ligne["domain"], "$page?domain-popup=$id&function=$function");
}

function newdomain_popup():bool{
		$tpl=new template_admin();
        $function=$_GET["function"];
        if($function<>null){$function="$function();";}
		$form[]=$tpl->field_text("newdomain", "{domain}", null);
        $form[]=$tpl->field_text("explainthis", "{description}", null);
		$html[]=$tpl->form_outside("{new_domain}", @implode("\n", $form),null,"{add}",
				"BootstrapDialog1.close();$function","AsDnsAdministrator");
		echo $tpl->_ENGINE_parse_body($html);
        return true;
}
function newdomain_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
	 $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $domain=trim(strtolower($_POST["newdomain"]));
	$domain=url_decode_special_tool($domain);
    if(strpos(" $domain","%")>0){
        echo $tpl->post_error("[$domain] {invalid}");
        return false;
    }
    if(strpos(" $domain",";")>0){
        echo $tpl->post_error("[$domain] {invalid}");
        return false;
    }
    if(strpos(" $domain",",")>0){
        echo $tpl->post_error("[$domain] {invalid}");
        return false;
    }
    $explainthis=$_POST["explainthis"];

	if($domain==null){
        echo $tpl->post_error("{no_data}");
		return false;
	}
	$q->QUERY_SQL("INSERT OR IGNORE INTO localdomains (domain,explainthis) VALUES ('$domain','$explainthis')");
    return admin_tracks("Create a new DNS Cache local domain $domain ($explainthis)");
}
function domain_delete(){
	$id=$_POST["delete"];
	 $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");

	$Domain=$q->GetDomainName($id);

	$q->QUERY_SQL("DELETE FROM records WHERE domain_id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM domains WHERE id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM domainmetadata WHERE id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM pdnsutil_dnssec WHERE domain_id=$id");
	$q->QUERY_SQL("DELETE FROM pdnsutil_chkzones WHERE domain_id=$id");
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("DELETE FROM dnsinfos WHERE domain_id=$id");
	admin_tracks("Removed all datas from domain $Domain");

	$sock=new sockets();
	$sock->getFrameWork("pdns.php?dnssec=yes");
	

}

function title_extention():string{
    $Key="PowerDNSEnableClusterSlave";
    $keydate="PowerDNSClusterClientDate";
    $ClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Key));
    if($ClusterSlave==1){return "";}
    $ClusterClientDate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($keydate));
    if($ClusterClientDate==0){return "";}
    $tpl=new template_admin();
    $text_date=$tpl->time_to_date($ClusterClientDate,true);
    return "&nbsp;<small>{last_sync}: $text_date</small>";

}

function page(){
    $page       = CurrentPageName();
	$tpl        = new template_admin();
	$title_add  = title_extention();


    $html=$tpl->page_header("{local_domains}$title_add","fab fab fa-soundcloud",
    "{local_domains_dns_explain}","$page?start=yes","dnscache-local-domains","progress-dnslocaldomains-restart",false,"table-unbound-domains");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }

	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ddns_status():bool{
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();

    $data = $sock->REST_API("/ddns/agent/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $APP_DDNS_AGENT=$tpl->framework_buildjs(
        "/ddns/agent/restart","ddns-agent.service.progress",
        "ddns-agent.service.log","progress-dnslocaldomains-restart");

    echo $tpl->SERVICE_STATUS($ini, "APP_DDNS_AGENT",$APP_DDNS_AGENT);
    return true;
}

function btns(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $new_entry=$tpl->_ENGINE_parse_body("{new_domain}");
    $function=$_GET["function"];

    $jsrestart=$tpl->framework_buildjs("/unbound/build/zones",
            "unbound.zones.progress","unbound.zones.progress.log",
            "progress-dnslocaldomains-restart");

    $topbuttons=array();
    if($PowerDNSEnableClusterSlave==0){
        $topbuttons[] = array("Loadjs('$page?newdomain-js=yes&function=$function')",ico_plus,$new_entry);
        $topbuttons[] = array("Loadjs('$page?service-js=yes&function=$function')",ico_params,"{service_parameters}");
    }
    $topbuttons[] = array($jsrestart,ico_save,"{reconfigure_service}");
    return  $tpl->table_buttons($topbuttons);

}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	 $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $function=$_GET["function"];
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	$rowToAdd=0;
	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$domains=$tpl->_ENGINE_parse_body("{domains}");

	$html[]="<table id='table-dns-forward-zones' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";


	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$domains}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$items}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>TSIG</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>DDNS</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    $LIKE="";
    $search="*{$_GET["search"]}*";
    $search=str_replace("**","*",$search);
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);
    if(strlen($search)>1){
        $LIKE="WHERE `domain` LIKE '$search'";
    }

	$sql="SELECT * FROM localdomains $LIKE ORDER BY domain";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error."<br>$sql");}

    $td1="style='width:1%' nowrap";
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$dnssec_row=null;
		$PDNSStatus_row=null;
        $ID=$ligne["id"];
		$addTodomain=array();
		$md=md5(serialize($ligne));
		$domain=$ligne["domain"];
		$sql="SELECT COUNT(*) AS tcount FROM records WHERE name LIKE '%$domain'";
		$ligne2=$q->mysqli_fetch_array($sql);
		$items=$ligne2["tcount"];
        $duplicate=null;
		$explain=$ligne["explainthis"];
		$allowddnsupdate=$ligne["allowddnsupdate"];
        $tsigkeyname=$ligne["tsigkeyname"];
        $algorithm=$ligne["algorithm"];
		$distance_color="text-primary";

		if($explain<>null){$addTodomain[]="<small>$explain</small>";}

		$domain=$tpl->td_href($domain,"{domain}","Loadjs('$page?domain-js=$ID&function=$function')");
		$html[]="<tr class='$TRCLASS' id='$md'>";

		$delete_icon=$tpl->icon_nothing();
		if($PowerDNSEnableClusterSlave==0){
			$delete_icon=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&id=$md')","AsDnsAdministrator");
		}

        $enableDDNS=$tpl->icon_check($allowddnsupdate,"Loadjs('$page?enable-ddns=$ID&id=$md')");

		$addTodomain_text=null;
		if(count($addTodomain)>0){$addTodomain_text=@implode("<br>", $addTodomain);}
		$html[]="<td><strong>$domain</strong>{$duplicate}$addTodomain_text</td>";
        $html[]="<td $td1>".FormatNumber($items)."</td>";
        $html[]="<td $td1>$tsigkeyname&nbsp;($algorithm)</td>";
        $html[]="<td $td1>$enableDDNS</td>";
		$html[]="<td $td1>$delete_icon</center></td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";

	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";


    $title_add  = title_extention();
    $TINY_ARRAY["TITLE"]="{local_domains} $title_add";
    $TINY_ARRAY["ICO"]="fab fab fa-soundcloud";
    $TINY_ARRAY["EXPL"]="{local_domains_dns_explain}";
    $TINY_ARRAY["URL"]="dnscache-local-domains";
    $TINY_ARRAY["BUTTONS"]=btns();
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $jj=$tpl->RefreshInterval_js("ddns-agent-status",$page,"ddns-agent-status=yes",3);
    $html[]="
	<script>
	$jj
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-dns-forward-zones').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
function DistanceInMns($time){
	$data1 = $time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}




?>