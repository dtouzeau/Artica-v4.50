<?php
$GLOBALS["Units"]["bit"]="Bit (b)";
$GLOBALS["Units"]["kbit"]="Kilobit (kbit)";
$GLOBALS["Units"]["Mbit"]="Megabit (Mbit)";
//$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["element-js"])){element_js();exit;}
if(isset($_GET["ipaddr-import-js"])){ipaddr_import_js();exit;}
if(isset($_GET["element-popup"])){element_popup();exit;}
if(isset($_POST["ID"])){element_save();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["import"])){import_save();exit;}
if(isset($_GET["ipaddr-import-popup"])){ipaddr_import_popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["enable"])){element_enable();exit;}
if(isset($_GET["fill"])){fill();exit;}
if(isset($_GET["row"])){row_fill();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ruleid=intval($_GET["ruleid"]);
	if($ruleid==0){
		page();
		exit;
	}
	$tpl->js_dialog4("{traffic_shaping} {elements}","$page?table-start=yes&ruleid=$ruleid",1030);

}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


	$html="
		<div class=\"row border-bottom white-bg dashboard-header\">
			<div class=\"col-sm-12\">
				<h1 class=ng-binding>{traffic_shaping}</h1>
				<p>&nbsp;</p>
			</div>
		</div>
		<div class='row'><div id='progress-traffic-restart'></div>
		<div class='ibox-content'>
			<div id='traffic-shaping-main'></div>
		</div>
	<script>
		$.address.state('/');
		$.address.value('/traffic-shaping');
		LoadAjax('traffic-shaping-main','$page?table=yes&ruleid=0');
	</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	echo $tpl->_ENGINE_parse_body($html);

}

function element_enable(){
	$tpl=new template_admin();
	$ID=intval($_GET["enable"]);
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM traffic_shaping WHERE ID='$ID'");
	$pattern=urlencode($ligne["pattern"]);
	$ruleid=$ligne["ruleid"];
	if(intval($ligne["enabled"])==1){
		$q->QUERY_SQL("UPDATE traffic_shaping SET enabled=0 WHERE ID=$ID");
		if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
		$sock=new sockets();
		$sock->getFrameWork("firehol.php?traffic-shap-remove=$pattern&ruleid=$ruleid");
		echo "Loadjs('$page?fill=$ID');\n";
		return;
	}
	$q->QUERY_SQL("UPDATE traffic_shaping SET enabled=1 WHERE ID=$ID");
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
	element_push($ID);
	echo "Loadjs('$page?fill=$ID');\n";
}

function element_push($ID){
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM traffic_shaping WHERE ID='$ID'");
	$pattern=urlencode($ligne["pattern"]);
	$ruleid=$ligne["ruleid"];
	$limit_unit=$ligne["limit_unit"];
	$limit=$ligne["limit"];

	if($limit_unit=="kbit"){
		$final=$limit*1024;
	}
	if($limit_unit=="Mbit"){
		$final=$limit*1024;
		$final=$final*1024;
	}

	$sock=new sockets();
	$sock->getFrameWork("firehol.php?traffic-shap-add=$pattern&ruleid=$ruleid&value=$final");
}

function table_start(){
	$ruleid=intval($_GET["ruleid"]);
	$page=CurrentPageName();
	echo "<div id='traffic-shaping-main'></div>
	<script>LoadAjax('traffic-shaping-main','$page?table=yes&ruleid=$ruleid');</script>";

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="CREATE TABLE IF NOT EXISTS `traffic_shaping` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`pattern` TEXT,
		`enabled` INTEGER NOT NULL DEFAULT 0,
		`ruleid` INTEGER NOT NULL DEFAULT 0,
		`limit` INTEGER NOT NULL DEFAULT 10000000,
		`limit_unit` TEXT NOT NULL DEFAULT 'bit')";

	$q->QUERY_SQL($sql);
	$ruleid=intval($_GET["ruleid"]);


	$html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/firewall.db","traffic_shaping",null,"&ruleid=$ruleid");
	echo $tpl->_ENGINE_parse_body($html);



}

function delete(){
	$ID=intval($_GET["delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("DELETE FROM traffic_shaping WHERE ID='$ID'");
	if(!$q->ok){
		$tpl=new template_admin();
		$tpl->js_mysql_alert($q->mysql_error);
		return;
	}
	echo "$('#{$_GET["md"]}').remove()\n";
}


function element_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["element-js"]);
	$ruleid=intval($_GET["ruleid"]);
	if($ID==0){$title="{new_entry}";}else{$title="{item} $ID";}
	$tpl->js_dialog($title, "$page?element-popup=$ID&ruleid=$ruleid&function={$_GET["function"]}");
}
function element_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ruleid=$_GET["ruleid"];
	$RemoveRuleField=false;
	$explain=null;

	$xRULES=dump_rules();
	if(count($xRULES)==0){
		echo $tpl->div_error("{no_traffic_shaping_rules}");
		return;
	}
	$ID=intval($_GET["element-popup"]);
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$jsafter="{$_GET["function"]}()";
	$ligne=$q->mysqli_fetch_array("SELECT * FROM traffic_shaping WHERE ID='$ID'");

	$Units=$GLOBALS["Units"];

	if($ID==0){
		$jsafter="BootstrapDialog1.close();{$_GET["function"]}()";
		$bt="{add}";
		$title="{new_item}";
		$ligne["limit"]=512;
		$ligne["enabled"]=1;
		$ligne["limit_unit"]="kbit";
		$ligne["ruleid"]=$ruleid;
		if($ruleid>0){$RemoveRuleField=true;}

	}else{
		$bt="{apply}";
		$title="{$ligne["pattern"]}";
		$jsafter="Loadjs('$page?fill=$ID');";
	}
	$form[]=$tpl->field_hidden("ID",$ID);
	if($ID==0){$form[]=$tpl->field_text("pattern", "{address}", null,true);}

	if(!$RemoveRuleField) {
		$form[] = $tpl->field_array_hash($xRULES, "ruleid", "nonull:{rule}", $ligne["ruleid"]);
	}else{
		$tpl->field_hidden("ruleid",$ligne["ruleid"]);
	}
	if($ligne["ruleid"]>0){
		$explain=$xRULES[$ruleid];
	}

	$form[]=$tpl->field_numeric("limit", "{MaxRateBw}", $ligne["limit"]);
	$form[]=$tpl->field_array_hash($Units,"limit_unit","nonull:{unit}",$ligne["limit_unit"]);
	echo $tpl->form_outside($title, $form,$explain,$bt,$jsafter,"AsFirewallManager",true);
}

function element_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	$ID=$_POST["ID"];
	$pattern=$_POST["pattern"];
	$limit=intval($_POST["limit"]);
	$limit_unit=$_POST["limit_unit"];
	$ruleid=intval($_POST["ruleid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

	if(!$q->FIELD_EXISTS("traffic_shaping","stmp")){
		$q->QUERY_SQL("ALTER TABLE ADD stmp TEXT NULL");
	}

	if(!is_file("/proc/net/ipt_ratelimit/rule{$ruleid}")){
		$error_not_ip_not_cdir=$tpl->_ENGINE_parse_body("{error_fwrule_not_applied}");
		echo "jserror: $error_not_ip_not_cdir";
		return;
	}

	if($ID==0){

		$ipclass=new IP();
		if(!$ipclass->IsACDIROrIsValid($pattern)){
			$error_not_ip_not_cdir=$tpl->_ENGINE_parse_body("{error_not_ip_not_cdir}");
			$error_not_ip_not_cdir=str_replace("%s",$pattern,$error_not_ip_not_cdir);
			echo "jserror: $error_not_ip_not_cdir";
			return;

		}
		$stmp=md5(time()."$pattern$ruleid");
		$q->QUERY_SQL("INSERT OR IGNORE INTO traffic_shaping (`pattern`,`limit`,`limit_unit`,`enabled`,`ruleid`,`stmp`)
			VALUES ('$pattern','$limit','$limit_unit',1,$ruleid,'$stmp')");
		if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}

		$ligne=$q->mysqli_fetch_array("SELECT ID FROM traffic_shaping WHERE stmp='$stmp'");
		element_push($ligne["ID"]);



		return;
	}


	$q->QUERY_SQL("UPDATE traffic_shaping SET 
				`limit`='$limit', 
				`limit_unit`='$limit_unit', 
				`ruleid` = '$ruleid'
				WHERE `ID`='$ID'");
	if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}
	element_push($ID);


}

function import_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("\n",$_POST["import"]);
    unset($_POST["import"]);
    foreach($tb as $item) {
        $_POST["ipaddr"]=$item;
        ipaddr_save();
    }
}

function dump_rules(){
	$xRULES=array();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

	if(!$q->FIELD_EXISTS("iptables_main","xt_ratelimit")){
		$q->QUERY_SQL("ALTER TABLE iptables_main ADD xt_ratelimit INTEGER NOT NULL DEFAULT 0");
		$q->QUERY_SQL("ALTER TABLE iptables_main ADD xt_ratelimit_dir TEXT NOT NULL DEFAULT 'src'");
	}

	$sql="SELECT ID,rulename FROM iptables_main WHERE xt_ratelimit=1 AND enabled=1";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$xRULES[$ligne["ID"]]=$ligne["rulename"];
	}

	return $xRULES;
}

function fill(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$md=md5("TRAFFIC.".$_GET["fill"]);


	echo "
	var elm = document.getElementById('$md');
	var className=elm.className;
	var str='';
	$.get(\"$page?row={$_GET["fill"]}\", {class:className}, function(data){
		$(\"#$md\").replaceWith(data);
	});";

	#echo "LoadAjax('$md','$page?row={$_GET["fill"]}');\n";
}
function row_fill(){
	$ID=$_GET["row"];
	$tpl=new template_admin();
	$class=$_GET["class"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="SELECT * FROM traffic_shaping WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	echo $tpl->_ENGINE_parse_body(rows($ligne,$class));

}

function rows($ligne,$TRCLASS=null){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$td1=$tpl->table_td1prc();
	$tdf=$tpl->table_tdfree();
	$Units=$GLOBALS["Units"];
	$xRULES=dump_rules();
	$pattern=$ligne["pattern"];
	$enabled=$ligne["enabled"];
	$ruleid=$ligne["ruleid"];
	$limit=$ligne["limit"];
	$limit_unit=$ligne["limit_unit"];
	$normal_burst=$tpl->icon_nothing();
	$extended_burst=$tpl->icon_nothing();
	$pkts=$tpl->icon_nothing();
	$bps=$tpl->icon_nothing();
	$RuleName=$xRULES[$ruleid];
	$ID=$ligne["ID"];
	$IPTSTATS=IPTSTATS($ruleid);
	$md=md5("TRAFFIC.".$ligne["ID"]);


	$html[]="<tr class='$TRCLASS' id='$md'>";


	if(!isset($IPTSTATS[$pattern])){

		if($enabled==1) {
			$ico_status = "<span class='label label-danger'>{inactive2}</span>";
		}else{
			$ico_status = "<span class='label label-danger'>{disabled}</span>";
		}
	}else{
		$ico_status="<span class='label label-primary'>{active2}</span>";
		//ebs

		$normal_burst=intval($IPTSTATS[$pattern]["cbs"]);
		$extended_burst=intval($IPTSTATS[$pattern]["ebs"]);
		$bps=intval($IPTSTATS[$pattern]["bps"]);
		$pkts=FormatNumber(intval($IPTSTATS[$pattern]["pkts"]));
		$bytes=intval($IPTSTATS[$pattern]["bytes"]);
		if($extended_burst>1024) {
			$extended_burst = FormatBytes($extended_burst / 1024);
		}else{
			$extended_burst="{$extended_burst}Bytes";
		}
		if($normal_burst>1024) {
			$normal_burst = FormatBytes($normal_burst / 1024);
		}else{
			$normal_burst="{$normal_burst}Bytes";
		}
		if($bps>0) {
			if ($bps > 1024) {
				$bps = FormatBytes($bps / 1024);
			} else {
				$bps = "{$bps}Bytes";
			}
			$bps = "$bps/s";
		}
		if($bytes>0) {
			if ($bytes > 1024) {
				$bytes = FormatBytes($bytes / 1024);
			} else {
				$bytes = "{$bytes}Bytes";
			}
		}


	}
	$limit_text="$limit&nbsp;{$Units[$limit_unit]}";

	$pattern=$tpl->td_href($pattern,null,
		"Loadjs('$page?element-js=$ID&ruleid=$ruleid&function={$_GET["function"]}');");

	$enable_ico=$tpl->icon_check($enabled,"Loadjs('$page?enable=$ID')","AsFirewallManager");
	$ico_status=$tpl->td_href($ico_status,"{refresh}","Loadjs('$page?fill=$ID')");

	$html[]="<td $td1>{$ico_status}</span></td>";
	$html[]="<td $td1>{$pattern}</td>";
	$html[]="<td $tdf>$RuleName</td>";
	$html[]="<td $td1>$limit_text</td>";
	$html[]="<td $td1>$normal_burst</td>";
	$html[]="<td $td1>$extended_burst</td>";
	$html[]="<td $td1>$bps</td>";
	$html[]="<td $td1>$pkts</td>";
	$html[]="<td $td1>$bytes</td>";
	$html[]="<td $td1>$enable_ico</td>";
	$html[]="<td $td1>".$tpl->icon_delete("Loadjs('$page?delete=$ID&md=$md')","AsFirewallManager")."</td>";
	$html[]="</tr>";
	$html[]="<script>".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
	return @implode("\n",$html);
}

function search(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

	$t=time();
	$ruleid=intval($_GET["ruleid"]);

	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?element-js=0&ruleid=$ruleid&function={$_GET["function"]}');\">";
	$html[]="<i class='fa fa-plus'></i> {new_item} </label>";
	$html[]="</div>";




	$search=trim($_GET["search"]);
	$querys=$tpl->query_pattern($search);
	$MAX=$querys["MAX"];
	if($MAX==0){$MAX=150;}
	$sql="SELECT * FROM traffic_shaping {$querys["Q"]} LIMIT $MAX";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}
	

	
	$TRCLASS=null;

	$html[]="<table id='table-$t' class=\"table table-stripped\" style='margin-top:10px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th >{status}</th>";
	$html[]="<th >{items}</th>";
	$html[]="<th >{rules}</th>";
	$html[]="<th  nowrap>{MaxRateBw}</th>";
	$html[]="<th  nowrap>{normal_burst}</th>";
	$html[]="<th  nowrap>{extended_burst}</th>";
	$html[]="<th  nowrap>{rate}</th>";
	$html[]="<th  nowrap>{packets}</th>";
	$html[]="<th  nowrap>{limited}</th>";
	$html[]="<th class='text-capitalize center'>{enable}</center></th>";
	$html[]="<th class='text-capitalize center' data-type='text'>DEL</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$html[]=rows($ligne,$TRCLASS);

	
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='11'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<small>$sql</small>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function IPTSTATS($ruleid){

	if(isset($GLOBALS[$ruleid])){return $GLOBALS[$ruleid];}
	$MAIN=explode("\n",@file_get_contents("/proc/net/ipt_ratelimit/rule{$ruleid}"));

	foreach ($MAIN as $line){
		$line=trim($line);
		if($line==null){continue;}

		if(!preg_match("#(.+?)\s+cir\s+.*?cbs\s+([0-9]+)\s+ebs\s+([0-9]+);\s+tc\s+([0-9]+)\s+te\s+([0-9]+)\s+last\s+(.+?);\s+conf\s+([0-9]+)\/([0-9]+)\s+([0-9]+)\s+bps,\s+rej\s+([0-9]+)\/([0-9]+)#",$line,$re)){
			//echo "NO MATCH $line\n";

			continue;}
		$pattern=$re[1];
		$cbs=$re[2];
		$ebs=$re[3];
		$tc=$re[4];
		$te=$re[5];
		$last=$re[6];
		$pkts=$re[7];
		$bytes=$re[8];
		$bps=$re[9];
		$rej1=$re[10];
		$rej2=$re[11];

		$GLOBALS[$ruleid][$pattern]=array(
			"cbs"=>$cbs,
			"ebs"=>$ebs,
			"tc"=>$tc,
			"te"=>$te,
			"last"=>$last,
			"pkts"=>$pkts,
			"bytes"=>$bytes,
			"bps"=>$bps,
			"rej"=>$rej1,
			"rejt"=>$rej2,
			"LINE"=>$line
		);



	}

	if(isset($GLOBALS[$ruleid])){return $GLOBALS[$ruleid];}
	return array();
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

?>

