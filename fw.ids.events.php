<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["severity"])){severity_choose();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();

function severity_choose(){
    $_SESSION["ids_severity"]=$_GET["severity"];
    $function=$_GET["function"];
    echo "$function();";
}
function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
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
    $tpl=new template_admin();


    $html=$tpl->page_header(
        "{ids_events}",ico_hacker2cols,"{ids_events_explain}",
        "","ids-threats","ids-threats-progress",true

    );

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
    $function=$_GET["function"];
	$zDate=$tpl->javascript_parse_text("{zDate}");
	$src_ip=$tpl->javascript_parse_text("{src_ip}");
	$dst_ip=$tpl->javascript_parse_text("{dst_ip}");
	$severity=$tpl->javascript_parse_text("{severity}");
	$rule=$tpl->javascript_parse_text("{rule}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
    if(!isset($_SESSION["ids_severity"])){
        $_SESSION["ids_severity"]=1;
    }



	$q=new postgres_sql();
	$results=$q->QUERY_SQL("SELECT signature FROM suricata_sig WHERE enabled=1 and firewall=1");
	if(!$q->ok){events($q->mysl_error);}
	while ($ligne = pg_fetch_assoc($results)) {
		$GLOBALS["FIREWALL"][$ligne["signature"]]=true;
	}
    $severity_array_filter[1]="Critic";
    $severity_array_filter[2]="Warn.";
    $severity_array_filter[3]="Infor.";
    $severity_array_filter[4]="None.";
    $severity_array_filter[5]="OK.";

    $realseverity=array();
    $sql="SELECT severity FROM suricata_events GROUP BY severity";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $sev=$ligne["severity"];
        $realseverity[$sev]=$sev;
        $js="Loadjs('$page?severity=$sev&function=$function');";
        $topbuttons[]=array($js,ico_filter,$severity_array_filter[$sev]);
    }

	$html[]="<table id='table-ids-events' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";

    $sev=intval($_SESSION["ids_severity"]);
    if(!isset($realseverity[$sev])){
        foreach($realseverity as $sev1=>$none){
            $sev=$sev1;
            break;
        }
    }

    $QUERY="WHERE severity=$sev";
    $search="";
    if(isset($_GET["search"])){
        $search=$_GET["search"];
    }
    $IPClass=new IP();

    if(strlen($search)>1) {
        if ($IPClass->isIPAddress($search)) {
            $QUERY = "WHERE (src_ip='$search' OR dst_ip='$search') AND severity=$sev";
        }
        if ($IPClass->IsACDIR($search)) {
            $QUERY = "WHERE (src_ip::inet << '$search'::inet OR dst_ip::inet << '$search'::inet) AND severity=$sev";
        }
        if(is_numeric($search)){
            $QUERY = "WHERE (dst_port=$search OR signature=$search) AND severity=$sev";
        }
    }

	$sql="SELECT * FROM suricata_events $QUERY ORDER BY zdate DESC LIMIT 250";
	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return;
    }

	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$severity</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zDate</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$src_ip</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$dst_ip</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>FW</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rule</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hits}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	
	if(!$q->ok){
		echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";
	}
	
	$severity_array[1]="<span class='label label-danger'>Critic.</span>";
	$severity_array[2]="<span class='label label-warning'>Warn.</span>";
	$severity_array[3]="<span class='label label-success'>Infor.</span>";
	$severity_array[4]="<span class='label'>None.</span>";
	$severity_array[5]="<span class='label label-info'>OK.</span>";


	
	$TRCLASS=null;
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$color="black";
		$icon=$severity_array[$ligne["severity"]];
		$src_ip=$ligne["src_ip"];
		$zDate=$ligne["zdate"];
		$dst_ip=$ligne["dst_ip"];
		$dst_port=$ligne["dst_port"];
		$proto=$ligne["proto"];
		$signature=$ligne["signature"];
        $xcount=$ligne["xcount"];
		$ligne2=pg_fetch_assoc($q->QUERY_SQL("SELECT description FROM suricata_sig WHERE signature='$signature'"));
		if(!$q->ok){$ligne2["description"]=$q->mysql_error;}
		$FW_ACT="NOTIFY";
		$FW_CCL=null;
		if(isset($GLOBALS["FIREWALL"][$signature])){
			$FW_ACT="BLOCK";
			$FW_CCL="label-danger";
		}
		
		
		$description=$ligne2["description"];
		$signature_js="<a href=\"javascript:blur();\"
		OnClick=\"Loadjs('fw.ids.rule.zoom.php?signature=$signature');\"
		style='color:$color;text-decoration:underline'>";
		

		
		if($signature==0){$signature_js=null;}

		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>$icon</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>$zDate</a></td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>$src_ip</td>";
		$html[]="<td class='$text_class' style='vertical-align:middle;width:1%' nowrap>$proto $dst_ip:$dst_port</span></td>";
		$html[]="<td class=\"$text_class\" style='width:1%' class='center' nowrap><span class='label $FW_CCL'>$FW_ACT</span></center></td>";
		$html[]="<td>[$signature_js$signature</a>]: $description</td>";
        $html[]="<td style='width:1%' nowrap>$xcount</td>";
		$html[]="</tr>";
		

	}




    $TINY_ARRAY["TITLE"]="{ids_events}";
    $TINY_ARRAY["ICO"]=ico_hacker2cols;
    $TINY_ARRAY["EXPL"]="{ids_events_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	$jstiny
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-ids-events').footable( {\"filtering\": {\"enabled\": false },\"sorting\": {\"enabled\": true } } ); });
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