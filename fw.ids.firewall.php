<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new mysql();
	$uduniq=$_GET["delete"];
	$t=$_GET["t"];
	
	
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM suricata_firewall WHERE uduniq='$uduniq'");
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	
	
	
	echo "
	var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
	LoadAjax('table-loader','$page?table=yes&t=$t&search='+ss);";
	
}

function enable_signature(){
	$t=time();
	$id=$_GET["enable-signature"];
	$rulefile=$_GET["cat"];
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM suricata_sig WHERE signature='$id'"));
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	$tpl=new template_admin();
	
	if($ligne["enabled"]==0){
		$q->QUERY_SQL("UPDATE suricata_sig SET enabled=1 WHERE signature='$id'");
		if(!$q->ok){echo "alert('$q->mysql_error')";return;}
		
		echo "
document.getElementById('id-$id').style.color = 'black';
document.getElementById('cat-$id').style.color = 'black';					
document.getElementById('signature-$id').className= 'fas fa-check-square-o';				
				
";
return;		
	}
	
	$q->QUERY_SQL("UPDATE suricata_sig SET enabled=0 WHERE signature='$id'");
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}	
	echo "
	document.getElementById('id-$id').style.color = '#8a8a8a';
	document.getElementById('cat-$id').style.color = '#8a8a8a';
	document.getElementById('signature-$id').className= 'fa fa-square-o';
	
	";	
}

function enable_firewall(){
	$id=$_GET["enable-firewall"];
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM suricata_sig WHERE signature='$id'"));
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}

	
	if($ligne["firewall"]==0){
		$q->QUERY_SQL("UPDATE suricata_sig SET firewall=1 WHERE signature='$id'");
		if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	
		echo "
		document.getElementById('firewall-$id').className= 'fas fa-check-square-o';
	
		";
		return;
	}
	
	$q->QUERY_SQL("UPDATE suricata_sig SET firewall=0 WHERE signature='$id'");
			if(!$q->ok){echo "alert('$q->mysql_error')";return;}
			echo "
			document.getElementById('firewall-$id').className= 'fa fa-square-o';
	
			";
}



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["IDSFW_SEARCH"]==null){$_SESSION["IDSFW_SEARCH"]="today src * dstport >-1 limit 500";}

    $html=$tpl->page_header("{IDS} {firewall_rules}",
        "fab fa-free-code-camp","{ids_firewall_rules_explain}",
        "$page?main=yes","firewall-ids","progress-suricata-restart",true,"table-loader");



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
	$SuricataFail2ban=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataFail2ban"));
	
	if($SuricataFail2ban==1){
		echo $tpl->FATAL_ERROR_SHOW_128("{section_use_fail2ban}");
		return;
		
	}
    $token=null;
	$delete=$tpl->javascript_parse_text("{delete}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$signature=$tpl->_ENGINE_parse_body("{signature}");
	$zdate=$tpl->_ENGINE_parse_body("{date}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
	$t=time();


	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	
	$_SESSION["IDSFW_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
	
	$q=new postgres_sql();
	$sql="SELECT * FROM suricata_firewall {$search["Q"]}ORDER BY zdate DESC LIMIT {$search["MAX"]}";
	$results=$q->QUERY_SQL($sql);
	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$signature</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zdate</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$explain}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{$delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($q->mysql_error));
		return;
	}

	
	$TRCLASS=null;

	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$square_class="text-navy";
		$text_class=null;
		$square="fa-check-square-o";
		$color="black";
		$signature=intval($ligne["signature"]);
		$explain="{block} {from} {$ligne["src_ip"]} {$ligne["proto"]}";
		if($ligne["dst_port"]>0){$explain=$explain." {port} {$ligne["dst_port"]}";}
		$explain=$tpl->javascript_parse_text($explain);
			
		$signature_js="<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('fw.ids.rule.zoom.php?signature={$ligne["signature"]}');\"
			style='color:$color;text-decoration:underline'>";
			
		if($signature>0){
			if(!isset($DESCZ[$ligne["signature"]])){
				$ligne2=pg_fetch_assoc($q->QUERY_SQL("SELECT description FROM suricata_sig WHERE signature='{$ligne["signature"]}'"));
				if(!$q->ok){$DESCZ[$ligne["signature"]]=$q->mysql_error;}else{
				$DESCZ[$ligne["signature"]]=$ligne2["description"];
				}
			}
		}
		
		if($signature==0){$signature_js=null;}
		$uduniq=$ligne["uduniq"];
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\">$signature_js{$ligne["signature"]}</a></td>";
		$html[]="<td class=\"$text_class\">{$ligne["zdate"]}</a></td>";
		$html[]="<td class='$text_class' style='vertical-align:middle'>$explain</span></td>";
		$html[]="<td style='vertical-align:middle'><center>".$tpl->icon_delete("Loadjs('$page?delete=$uduniq&t=$t')")."</center></td>";
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
	$html[]="</table><div><i>{$search["Q"]}</i></div>";
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('.footable').footable(
{\"filtering\": {\"enabled\": false},\"sorting\": {\"enabled\": true}}); });
</script>";

			echo @implode("\n", $html);

}
function enable(){
	
	$filename=$_POST["filename"];
	$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM suricata_rules_packages WHERE rulefile='$filename'");
	$enabled=intval($ligne["enabled"]);
	if($enabled==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE suricata_rules_packages SET `enabled`='$enabled' WHERE rulefile='$filename'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}