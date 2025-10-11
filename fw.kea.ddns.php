<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["ddnskeys"])){tsigkeys_save();exit;}
if(isset($_GET["tsigkeys-search"])){tsigkeys_table();exit;}
if(isset($_GET["tsigkeys"])){tsigkeys_js();exit;}
if(isset($_GET["tsigkeys-form"])){tsigkeys_form();exit;}
if(isset($_GET["key-js"])){key_js();exit;}
if(isset($_GET["key-form"])){key_form();exit;}
if(isset($_GET["key-delete"])){key_delete();exit;}

if(isset($_GET["ddns-js"])){ddns_js();exit;}
if(isset($_GET["ddns-form"])){ddns_form();exit;}
if(isset($_POST["dhpddns"])){ddns_save();exit;}

if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_GET["secretkey-js"])){secretkey_js();exit;}
if(isset($_GET["secretkey-start"])){secretkey_start();exit;}
if(isset($_GET["secretkey-popup"])){secretkey_popup();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}

page();
function delete():bool{
    $ID=intval($_GET["delete"]);
    $md=$_GET["md"];
    $q = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $ligne=$q->mysqli_fetch_array("SELECT domain FROM dhpddns WHERE ID=$ID");
    $tpl=new template_admin();
    return $tpl->js_confirm_delete($ligne["domain"],"delete",$ID,"$('#$md').remove()");
}
function delete_perform():bool{
    $ID=intval($_POST["delete"]);
    $q = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $ligne=$q->mysqli_fetch_array("SELECT domain FROM dhpddns WHERE ID=$ID");
    $q->QUERY_SQL("DELETE FROM dhpddns WHERE ID=$ID");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/kea/ddns/reconfigure");
    return admin_tracks("Remove DDNS rule for domain {$ligne["domain"]}");
}
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

function enable():bool{
    $ID=intval($_GET["enable"]);
    $q = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $ligne=$q->mysqli_fetch_array("SELECT enable FROM dhpddns WHERE ID=$ID");

    if($ligne["enable"]==1){
        $enable=0;
    }else{
        $enable=1;
    }
    $q->QUERY_SQL("UPDATE dhpddns SET enable=$enable WHERE ID=$ID");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/kea/ddns/reconfigure");
    return admin_tracks("Enable=$enable DDNS update rule #$ID");

}

function  ddns_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ddns-js"]);
    $title="{new_dns_server}";
    $function=$_GET["function"];
    if($ID>0){
        $q = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM dhpddns WHERE ID=$ID");
        $title="{$ligne["dnsserverip"]}:{$ligne["dnsserverport"]}";
    }
    $tpl->js_dialog7($title, "$page?ddns-form=$ID&function=$function",650);
}
function ddns_form(){
    $ID=intval($_GET["ddns-form"]);
    $function=$_GET["function"];
    $tpl=new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM dhpddns WHERE ID=$ID");
    $dnsserverip=$ligne["dnsserverip"];
    $dnsserverport=$ligne["dnsserverport"];
    $domain=$ligne["domain"];
    $keyid=$ligne["keyid"];
    $ID=$_GET["ID"];
    if(intval($dnsserverport)==0){
        $dnsserverport="53";
    }
    $bton="{apply}";
    if($ID==0){
        $bton="{add}";
    }

    $sql="SELECT * FROM ddnskeys ORDER BY keyname";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index => $row) {
        $KEYS[$row["ID"]]=$row["keyname"];
    }
    $form[]=$tpl->field_hidden("dhpddns",$ID);
    $form[]=$tpl->field_text("domain","{domain}",$domain);
    $form[]=$tpl->field_text("dnsserverip","{dns addresses}",$dnsserverip,true);
    $form[]=$tpl->field_numeric("dnsserverport","{remote_port} (udp)",$dnsserverport,true);
    $form[]=$tpl->field_array_hash($KEYS,"keyid","{key}",$keyid,true);
    $html[]=$tpl->form_outside("",$form,null,$bton,
        "$function();dialogInstance7.close();","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}
function ddns_save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $dnsserverip=$_POST["dnsserverip"];
    $dnsserverport=$_POST["dnsserverport"];
    $domain=$_POST["domain"];
    $keyid=intval($_POST["keyid"]);
    $ID=intval($_POST["dhpddns"]);
    $q = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    if($ID==0){
        $sql="INSERT OR IGNORE INTO dhpddns (dnsserverip,dnsserverport,domain,keyid,enable) VALUES ('$dnsserverip','$dnsserverport','$domain','$keyid',1)";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error."<br>$sql");
            return false;
        }
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/kea/ddns/reconfigure");
        return admin_tracks("Create the DDNS domain $domain for dns server $dnsserverip:$dnsserverport");
    }


    $q->QUERY_SQL("UPDATE dhpddns SET dnsserverip='$dnsserverip',dnsserverport='$dnsserverport',keyid='$keyid' WHERE ID=$ID");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error."<br>(UPDATE)");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/kea/ddns/reconfigure");
    return admin_tracks("Edit the DDNS domain for dns server $dnsserverip:$dnsserverport");

}
function tsigkeys_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{AuthorizedKeys} TSIG");
    $tpl->js_dialog7($title, "$page?tsigkeys-form=yes",650);
}
function key_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["key-js"]);
    $function=$_GET["function"];
    $sub="#$ID";
    if($ID==0){
        $sub="{new_record}";
    }
    $title=$tpl->javascript_parse_text("{AuthorizedKeys}:$sub");
    $tpl->js_dialog8($title, "$page?key-form=$ID&function=$function",780);
}
function key_delete(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["key-delete"]);
    $md=$_GET["md"];
    $q = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $q->QUERY_SQL("DELETE FROM ddnskeys WHERE ID=$ID");
    $q->QUERY_SQL("UPDATE dhpddns SET keyid=0 WHERE keyid=$ID");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();";
}
function key_form(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $id=intval($_GET["key-form"]);
    $q = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM ddnskeys WHERE ID=$id");
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $form[]=$tpl->field_hidden("ddnskeys",$id);
    $HMAC["HMAC-MD5"]="HMAC-MD5";
    $HMAC["HMAC-SHA1"]="HMAC-SHA1";
    $HMAC["HMAC-SHA224"]="HMAC-SHA224";
    $HMAC["HMAC-SHA256"]="HMAC-SHA256";
    $HMAC["HMAC-SHA384"]="HMAC-SHA384";
    $HMAC["HMAC-SHA512"]="HMAC-SHA512";

    $bton="{apply}";
    if(strlen($ligne["algorithm"])<3){
        $ligne["algorithm"]="HMAC-SHA256";
    }
    if($id==0) {
        $bton="{add}";
        $form[] = $tpl->field_text("keyname", "{key_name}", $ligne["keyname"]);
    }

    $form[]=$tpl->field_text_button("secretkey","TSIG key",$ligne["password"],false,"","{generate_key}");
    $form[]=$tpl->field_array_hash($HMAC,"algorithm","{algorithm}",$ligne["algorithm"]);
    $html[]=$tpl->form_outside($ligne["keyname"],$form,null,$bton,
        "$function();dialogInstance8.close();","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tsigkeys_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $password=$_POST["secretkey"];
    $algorithm=$_POST["algorithm"];
    $keyname=$_POST["keyname"];
    $ID=intval($_POST["ddnskeys"]);
    if(strpos($password,"|")>0){
        $tb=explode("|",$password);
        $password=$tb[1];
        $algorithm=$tb[0];
    }

    if ($ID==0){
        $sql="INSERT INTO ddnskeys (password,keyname,algorithm) VALUES ('$password','$keyname','$algorithm')";
        $q->QUERY_SQL($sql);
        writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        return admin_tracks_post("Create the TSIG Key");
    }
    $sql="UPDATE ddnskeys SET password='$password',algorithm='$algorithm' WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error."<br>$sql");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/kea/ddns/reconfigure");
    return admin_tracks_post("Edit the TSIG Key");
}
function tsigkeys_form(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div id='tsig-keys-btns' style='margin-top:10px;margin-bottom: 10px'></div>";
    echo $tpl->search_block($page,"","","","&tsigkeys-search=yes");
}
function tsigkeys_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q           = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");

    if(isset($_GET["search"])) {
        if(strlen($_GET["search"])>1) {
            $ss = "*{$_GET["search"]}*";
            $ss = str_replace("**", "%", $ss);
            $ss = str_replace("*", "%", $ss);
            $ss = str_replace("%%", "%", $ss);
            $qeury = "WHERE (keyname LIKE '$ss' OR algorithm LIKE '$ss')";
        }
    }

    $sql="SELECT * FROM ddnskeys $qeury ORDER BY keyname";
    $results=$q->QUERY_SQL($sql);
    $function=$_GET["function"];

    $topbuttons[] = array("Loadjs('$page?key-js=0&function=$function')", ico_plus, "{new_record}");
    $btnec=base64_encode($tpl->th_buttons($topbuttons));
    $html[]="<table id='table-tsigkeys' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{key}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{algorithm}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text_class=null;
        $md=md5(serialize($ligne));
        $keyname=$ligne["keyname"];
        $algorithm=$ligne["algorithm"];
        $ID=$ligne["ID"];
        $keyname=$tpl->td_href($keyname,"","Loadjs('$page?key-js=$ID&function=$function')");
        $delete=$tpl->icon_delete("Loadjs('$page?key-delete=$ID&md=$md')","AsDnsAdministrator");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"$text_class\" style='width:99%'><strong>$keyname</strong></td>";
        $html[]="<td class=\"$text_class\" style='width:1px' nowrap>$algorithm</td>";
        $html[]="<td class=\"$text_class\" style='width:1px'>$delete</td>";
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
$(document).ready(function() { $('#table-tsigkeys').footable({\"filtering\": {\"enabled\": false},\"sorting\": {\"enabled\": true } } ); });
    
    document.getElementById('tsig-keys-btns').innerHTML=base64_decode('$btnec');
    

</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_KEA_DDNS}",ico_retweet,"{APP_KEA_DDNS_EXPLAIN}","$page?search-form=yes","ddns","progress-ddns-restart");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_DHCP}",$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);

}
function search_form(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
}
function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $function=$_GET["function"];

    if(isset($_GET["search"])) {
        if(strlen($_GET["search"])>1) {
            $ss = "*{$_GET["search"]}*";
            $ss = str_replace("**", "%", $ss);
            $ss = str_replace("*", "%", $ss);
            $ss = str_replace("%%", "%", $ss);
            $qeury = "WHERE (dnsserverip LIKE '$ss' OR dnsserverport LIKE '$ss')";
        }
    }



    $q           = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
	$sql="SELECT * FROM dhpddns $qeury ORDER BY domain";
	$results=$q->QUERY_SQL($sql);

    $html[]="<table id='table-dhcpddns' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domain}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{dns_servers}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{AuthorizedKeys}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
	$TRCLASS=null;
    $td1="style='width:1%' nowrap";
foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$md=md5(serialize($ligne));
        $dnsserverip=$ligne["dnsserverip"];
        $dnsserverport=$ligne["dnsserverport"];
        $domain=$ligne["domain"];
        $keyid=$ligne["keyid"];
        $ID=$ligne["ID"];
        $domain=$tpl->td_href($domain,"","Loadjs('$page?ddns-js=$ID&function=$function')");
        $ligne2=$q->mysqli_fetch_array("SELECT keyname FROM ddnskeys WHERE ID=$keyid");
        $keyname=$ligne2["keyname"];
        $keyname=$tpl->td_href($keyname,"","Loadjs('$page?key-js=$keyid&function=$function')");
        $icoserv=ico_server;

        $enable=$tpl->icon_check($ligne["enable"],"Loadjs('$page?enable=$ID')","AsDnsAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?delete=$ID&md=$md')","AsDnsAdministrator");

		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $td1><i class='$icoserv'></i></td>";
		$html[]="<td class=\"$text_class\"><strong>$domain</strong></td>";
		$html[]="<td class=\"$text_class\" width='1%' nowrap>$dnsserverip:$dnsserverport</td>";
		$html[]="<td $td1>$keyname</td>";
		$html[]="<td $td1>$enable</td>";
		$html[]="<td $td1>$delete</td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{APP_KEA_DDNS}";
    $TINY_ARRAY["ICO"]=ico_retweet;
    $TINY_ARRAY["EXPL"]="{APP_KEA_DDNS_EXPLAIN}";

    $topbuttons[]=array("Loadjs('$page?ddns-js=0&function=$function')",ico_plus,"{new_dns_server}");
    $topbuttons[]=array("Loadjs('$page?tsigkeys=yes')",ico_key,"{AuthorizedKeys}");

    $jscompile=$tpl->framework_buildjs(
        "/kea/ddns/reload","kea.service.progress",
        "kea.service.log","progress-ddns-restart");
    $topbuttons[] = array($jscompile,ico_save,"{apply_parameters}");

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);



    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-dhcpddns').footable({\"filtering\": {\"enabled\": false},\"sorting\": {\"enabled\": true } } ); });
$headsjs
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}


