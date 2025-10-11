<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["rule-save"])){save();exit;}
page();


function page(){
	$tpl        = new template_admin();
	$ID         = $_GET["rule-id"];
	$q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ID'");
	$title="{$ligne["rulename"]} - {time_restriction}";

	$TTIME=unserialize($ligne["time_restriction"]);
	if($TTIME["ftime"]==null){$TTIME["ftime"]="20:00:00";}
	if($TTIME["ttime"]==null){$TTIME["ttime"]="23:59:00";}
	
	$array_days=array(1=>"monday",2=>"tuesday",3=>"wednesday",4=>"thursday",5=>"friday",6=>"saturday",7=>"sunday");
	
	$tpl->field_hidden("rule-save", $ID);
	$form[]=$tpl->field_checkbox("enablet", "{enabled}", $ligne["enablet"],true);
	$form[]=$tpl->field_clock("ftime", "{from_time}", $TTIME["ftime"]);
	$form[]=$tpl->field_clock("ttime", "{to_time}", $TTIME["ttime"]);
	
	
	
	foreach ($array_days as $num=>$maks){
		$form[]=$tpl->field_checkbox("D{$num}", "{{$maks}}", $TTIME["D{$num}"]);
	}
	
	echo $tpl->form_outside($title, @implode("\n", $form),"{fwtime_explain}","{apply}","Loadjs('fw.rules.php?fill=$ID');");
	
	
	
	
	
	
}
function save(){
	$ID=$_POST["rule-save"];
	
	$array_days=array(
			1=>"monday",
			2=>"tuesday",
			3=>"wednesday",
			4=>"thursday",
			5=>"friday",
			6=>"saturday",
			7=>"sunday",
	);
	
	foreach ($array_days as $num=>$maks){
		if($_POST["D{$num}"]==1){$TTIME["D{$num}"]=1;}
	}
	$TTIME["ttime"]=$_POST["ttime"];
	$TTIME["ftime"]=$_POST["ftime"];
	
	
	$rule1=strtotime(date("Y-m-d")." {$TTIME["ftime"]}");
	$rule2=strtotime(date("Y-m-d")." {$TTIME["ttime"]}");
	
	if($rule1>$rule2){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{fwtime_explain}");
		return;
	}
	
	$TTIMEZ=sqlite_escape_string2(serialize($TTIME));
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="UPDATE iptables_main SET `enablet`='{$_POST["enablet"]}',`time_restriction`='$TTIMEZ' WHERE ID='$ID'";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
}