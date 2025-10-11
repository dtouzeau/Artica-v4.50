<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.rtmm.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
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
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["FAIL2BAN_SEARCH"]==null){$_SESSION["FAIL2BAN_SEARCH"]="today src * limit 200";}
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{ids_events}</h1></div>
	</div>
		
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["FAIL2BAN_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>	
	
	
	
		
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
		
		
		
<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?table=yes&t=$t&search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		$.address.state('/');
	    $.address.value('/fail2ban-events');
		Start$t();
	</script>";


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

	
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new mysql();
	$eth_sql=null;
	$token=null;
	$class=null;
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$title=$tpl->_ENGINE_parse_body("{nat_title}");
	$nic_from=$tpl->javascript_parse_text("{nic}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	$add=$tpl->_ENGINE_parse_body("{new_nat}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$about=$tpl->javascript_parse_text("{about2}");
	$type=$tpl->javascript_parse_text("{type}");
	$reconstruct=$tpl->javascript_parse_text("{apply_firewall_rules}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$packets_from=$tpl->_ENGINE_parse_body("{packets_from}");
	$should_be_forwarded_to=$tpl->_ENGINE_parse_body("{should_be_forwarded_to}");
	$masquerading=$tpl->_ENGINE_parse_body("{masquerading}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$title=$tpl->_ENGINE_parse_body("{rules}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$rulename=$tpl->_ENGINE_parse_body("{signature}");
	$title=$tpl->_ENGINE_parse_body("{signatures}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$firewall=$tpl->_ENGINE_parse_body("{firewall}");
	$signature=$tpl->_ENGINE_parse_body("{signature}");
	$zdate=$tpl->_ENGINE_parse_body("{date}");
	$zDate=$tpl->javascript_parse_text("{zDate}");
	$src_ip=$tpl->javascript_parse_text("{src_ip}");
	$dst_ip=$tpl->javascript_parse_text("{dst_ip}");
	$proto=$tpl->javascript_parse_text("{proto}");
	$severity=$tpl->javascript_parse_text("{severity}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$title=$tpl->_ENGINE_parse_body("{IDS} {events}");
	$events=$tpl->javascript_parse_text("{events}");
	$signature=$tpl->javascript_parse_text("{signature} (numeric)");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
	$t=$_GET["t"];
	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$js="OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
	$t=time();
	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-fail2banthreats-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	
	$_SESSION["FAIL2BAN_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
	
	$q=new postgres_sql();

	if(!$q->TABLE_EXISTS("fail2ban_events")){
	    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS fail2ban_events (
		zdate timestamp,
		country varchar(90),
		city varchar(90),
		service varchar(40),
		src_ip inet,
		xcount BIGINT)");
    }

	$sql="SELECT * FROM fail2ban_events {$search["Q"]}ORDER BY zdate DESC LIMIT {$search["MAX"]}";
	$results=$q->QUERY_SQL($sql);

	
	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zDate</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$src_ip</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{country}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{city}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	
	if(!$q->ok){
		
		echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";
		return;
	}
    $services["smtp"]="<span class='label label-warning'>SMTP</span>";
	$services["ssh"]="<span class='label label-danger'>SSH</span>";
	$services["ids"]="<span class='label label-warning'>IDS</span>";
	
	$TRCLASS=null;
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$square_class="text-navy";
		$text_class=null;
		$square="fa-check-square-o";
		$color="black";

		
		$color="black";
	
		$src_ip=$ligne["src_ip"];
		$zDate=$ligne["zdate"];
		$country=$ligne["country"];
		$cirty=$ligne["city"];
		$hostname=$ligne["hostname"];
		$service=$ligne["service"];
		
		$flaf=GetFlags($country);
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\">$zDate</a></td>";
		$html[]="<td class=\"$text_class\">$src_ip</td>";
		$html[]="<td class=\"$text_class\" width=1% nowrap>{$services[$service]}</td>";
		$html[]="<td class=\"$text_class\">$hostname</td>";
		$html[]="<td class=\"$text_class\" width=1% nowrap><img src='img/$flaf'></td>";
		$html[]="<td class=\"$text_class\">$country</td>";
		$html[]="<td class=\"$text_class\">$cirty</td>";
		$html[]="</tr>";
		

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table><div><i>{$search["Q"]}</i><br><small>$sql</small></div>";
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-fail2banthreats-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });

var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	LoadAjax('table-loader','$page?table=yes');
}

function RuleGroupUpDown$t(ID,direction,eth){
	var XHR = new XHRConnection();
	XHR.appendData('rule-order', ID);
	XHR.appendData('direction', direction);
	XHR.appendData('eth', eth);
	XHR.sendAndLoad('firehol.nic.rules.php', 'POST',xRuleGroupUpDown$t);
}
</script>";

			echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

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