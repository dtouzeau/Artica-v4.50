<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
//$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
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
	$t=time();
	$id=$_GET["enable-firewall"];
	$rulefile=$_GET["cat"];
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM suricata_sig WHERE signature='$id'"));
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	$tpl=new template_admin();
	
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

    $html=$tpl->page_header("{IDS} {rules}",
        "fa fa-list",
        "{ids_rules_explain}",
        "$page?table=yes",
        "ids-rules","progress-ids-restart",false);


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{IDS}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);


}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$rulename=$tpl->_ENGINE_parse_body("{signature}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$firewall=$tpl->_ENGINE_parse_body("{firewall}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}

    $html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rulename</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>$enabled</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>$firewall</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	
	$q=new postgres_sql();
	$sql="SELECT * FROM suricata_sig ORDER BY description";
	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }
	$TRCLASS=null;
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$color="black";
		$id=$ligne["signature"];
		if($ligne["enabled"]==0){
			$color="#8a8a8a";
		}
	
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\"><span style='color:$color;font-weight:bold' id='id-$id'>{$id}</span></td>";
		$html[]="<td class='$text_class' style='vertical-align:middle'><span style='color:$color;font-weight:bold' id='cat-$id'>{$ligne["description"]}</span></td>";
		$html[]="<td style='vertical-align:middle'><center>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-signature=$id')","signature-$id")."</center></td>";
		$html[]="<td style='vertical-align:middle'><center>".$tpl->icon_check($ligne["firewall"],"Loadjs('$page?enable-firewall=$id')","firewall-$id")."</center></td>";
		$html[]="</tr>";
		

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $jscompile=  $tpl->framework_buildjs(
        "/suricata/restart",
        "suricata.progress",
        "suricata.progress.txt","progress-ids-restart"
    );

    $topbuttons[] = array($jscompile,ico_save,"{apply_changes}");
    $TINY_ARRAY["TITLE"]="{IDS} {rules}";
    $TINY_ARRAY["ICO"]="fa fa-list";
    $TINY_ARRAY["EXPL"]="{ids_rules_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	$headsjs
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
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