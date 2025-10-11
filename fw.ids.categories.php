<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();

function rule_js(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
	$ruleid=intval($_GET["ruleid-js"]);

	
	
	if($ruleid==0){
		$NAT_TYPE_TEXT="{new_router}";
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_bridges WHERE ID='$ruleid'");
		$NAT_TYPE_TEXT="{router} N.$ruleid {$ligne["nic_from"]} -- &raquo; {$ligne["nic_to"]}";
	}
	$tpl->js_dialog("$NAT_TYPE_TEXT","$page?rule-popup=$ruleid");
}

function enable_js(){
	$t=time();
	$id=$_GET["enable-js"];
	$rulefile=$_GET["cat"];
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM suricata_rules_packages WHERE rulefile='$rulefile'");
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	$tpl=new template_admin();
	
	if($ligne["enabled"]==0){
		$q->QUERY_SQL("UPDATE suricata_rules_packages SET enabled=1 WHERE rulefile='$rulefile'","artica_backup");
		if(!$q->ok){echo "alert('$q->mysql_error')";return;}
		
		echo "
document.getElementById('name-$id').style.color = 'black';
document.getElementById('cat-$id').style.color = 'black';					
document.getElementById('icon-$id').className= 'fas fa-check-square-o';				
				
";
return;		
	}
	
	$q->QUERY_SQL("UPDATE suricata_rules_packages SET enabled=0 WHERE rulefile='$rulefile'","artica_backup");
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}	
	echo "
	document.getElementById('name-$id').style.color = '#8a8a8a';
	document.getElementById('cat-$id').style.color = '#8a8a8a';
	document.getElementById('icon-$id').className= 'fa fa-square-o';
	
	";	
}
function delete_remove(){
	$ruleid=$_POST["delete-remove"];
	$eth=$_POST["eth"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	
	if(!$q->QUERY_SQL("DELETE FROM pnic_bridges WHERE ID='$ruleid'")){echo $q->mysql_error;return;}

	$results=$q->QUERY_SQL("SELECT ID FROM iptables_main WHERE eth='$eth'");
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$q->QUERY_SQL("DELETE FROM iptables_main WHERE ID='$ID'","artica_backup");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
		
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("DELETE FROM firewallfilter_sqacllinks WHERE aclid='$ID'");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
		
	}
}

function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-popup"];
	if(!is_numeric($ID)){$ID=0;}
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_bridges WHERE ID='$ID'");
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	
	$t=$_GET["t"];
	foreach ($interfaces as $eth){
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		$array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		
	}
	
	if(count($array)<2){
		
		echo"<div class='alert alert-danger'>". $tpl->_ENGINE_parse_body("{error_need_at_lease_2_pvinterfaces}")."</div>";
		return;
	}
	
	$but="{add}";
	$title="{new_router}";
	if($ID>0){
			$but="{apply}";
			$title="{router} {$ligne["nic_from"]}2{$ligne["nic_to"]}";
		}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["DenyDHCP"])){$ligne["DenyDHCP"]=1;}
	if(!is_numeric($ligne["masquerading"])){$ligne["masquerading"]=0;}
	if(!is_numeric($ligne["masquerading_invert"])){$ligne["masquerading_invert"]=0;}
	if(!is_numeric($ligne["DenyCountries"])){$ligne["DenyCountries"]=0;}
	if($ID==0){$BootstrapDialog="BootstrapDialog1.close();";}

	$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_array_hash($array,"nic_from","{packets_from}",$ligne["nic_from"],true);
	$form[]=$tpl->field_array_hash($array2,"nic_to","{should_be_forwarded_to}",$ligne["nic_to"],true);
	$form[]=$tpl->field_checkbox("DenyDHCP","{deny_dhcp_requests}",$ligne["DenyDHCP"],false);
	$form[]=$tpl->field_checkbox("DenyCountries","{enable_ipblocks}",$ligne["DenyCountries"],false);
	$form[]=$tpl->field_checkbox("masquerading","{masquerading}",$ligne["masquerading"],false);
	$form[]=$tpl->field_checkbox("masquerading_invert","{masquerading_invert}",$ligne["masquerading_invert"],false);
	$form[]=$tpl->form_add_button("tests", $BootstrapDialog);
	echo $tpl->form_outside($title,@implode("\n", $form),null,$but,
			"LoadAjax('table-loader','$page?table=yes');$BootstrapDialog");


}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{categories_to_detect}",
        "fa fa-sitemap","{ids_categories_explain}",
        "$page?table=yes","ids-categories","progress-suricata-restart",false,"table-loader");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);


}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
    $t=time();

	$html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$category</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{$enabled}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$sql="SELECT * FROM suricata_rules_packages";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		
		echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);
		return;
		
	}
	
	$TRCLASS=null;
foreach ($results as $index=>$ligne) {
	$text_class=null;
	$color="black";
	if($ligne["enabled"]==0){
		$text_class=" text-muted";
		$color="#8a8a8a";
	
	}
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$explain=$tpl->_ENGINE_parse_body("{{$ligne["rulefile"]}}");
		$id=md5($ligne["rulefile"]);
		
		if($ligne["enabled"]==0){
			$color="#8a8a8a";
		}
		if(is_numeric($ligne["category"])){
			if($ligne["category"]==0){$ligne["category"]="ALL";}
		}
	
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\"><span style='color:$color;font-weight:bold' id='name-$id'>{$ligne["rulefile"]}</span><div><i>$explain</i></div></td>";
		$html[]="<td class='$text_class' style='vertical-align:middle'><span style='color:$color;font-weight:bold' id='cat-$id'>{$ligne["category"]}</span></td>";
		$html[]="<td style='vertical-align:middle'><center>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$id&cat={$ligne["rulefile"]}')","icon-$id")."</center></td>";
		$html[]="</tr>";
		

	}


    $jscompile=  $tpl->framework_buildjs(
        "/suricata/restart",
        "suricata.progress",
        "suricata.progress.txt","progress-suricata-restart"
    );

    $topbuttons[] = array($jscompile,ico_save,"{apply_changes}");
    $TINY_ARRAY["TITLE"]="{categories_to_detect}";
    $TINY_ARRAY["ICO"]="fa fa-sitemap";
    $TINY_ARRAY["EXPL"]="{ids_categories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

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
	<script>$headsjs
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('.footable').footable(
{ \"filtering\": { \"enabled\": true },
\"sorting\": { \"enabled\": true } } ); });

</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
function enable(){
	
	$filename=$_POST["filename"];
	$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM suricata_rules_packages WHERE rulefile='$filename'");
	$enabled=intval($ligne["enabled"]);
	if($enabled==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE suricata_rules_packages SET `enabled`='$enabled' WHERE rulefile='$filename'");
	if(!$q->ok){echo $q->mysql_error;}
}