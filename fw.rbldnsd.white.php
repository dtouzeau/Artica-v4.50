<?php
//$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["ipaddr-js"])){ipaddr_js();exit;}
if(isset($_GET["ipaddr-import-js"])){ipaddr_import_js();exit;}
if(isset($_GET["ipaddr-popup"])){ipaddr_popup();exit;}
if(isset($_POST["ipaddr"])){ipaddr_save();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["import"])){import_save();exit;}
if(isset($_GET["ipaddr-import-popup"])){ipaddr_import_popup();exit;}

table();

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$html[]="

	</div>
	<div class='ibox-content'>
	<div id='postfix-transactions'></div>

	</div>
	</div>
	";


	$html[]=$tpl->search_block($page,"postgres","rbl_whitelists","rbl_whitelists","");
	echo $tpl->_ENGINE_parse_body($html);

}

function delete(){
	$ipaddr=$_GET["delete"];
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
	echo "$('#{$_GET["md"]}').remove()\n";
}


function ipaddr_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ipaddr=$_GET["ipaddr-js"];
	if($ipaddr==null){$title="{new_entry}";}else{$title=$ipaddr;}
	$tpl->js_dialog($title, "$page?ipaddr-popup=$ipaddr&function={$_GET["function"]}");
}
function ipaddr_import_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $title="{import}";
    $tpl->js_dialog($title, "$page?ipaddr-import-popup=yes&function={$_GET["function"]}");
}
function ipaddr_import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();

    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="Manager";}
    $jsafter="BootstrapDialog1.close();{$_GET["function"]}()";

    $bt="{add}";
    $title="{new_address}";
    $form[]=$tpl->field_textareacode("import","{address}", null);
    echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
}

function ipaddr_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ipaddr=$_GET["ipaddr-popup"];
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$jsafter="BootstrapDialog1.close();{$_GET["function"]}()";
	if($ipaddr==null){
		$bt="{add}";
		$title="{new_address}";
		$form[]=$tpl->field_text("ipaddr","{address}", $ipaddr);
		$description="Added $uid - ".date("Y-m-d H:i:s");
	}else{
		$bt="{apply}";
		$form[]=$tpl->field_ipaddr("ipaddr", "{address}", null,true);
		$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM rbl_whitelists WHERE ipaddr='$ipaddr'"));
		$description=$ligne["description"];
		$title=$ipaddr." - {$ligne["zdate"]}";
	}
	$form[]=$tpl->field_text("description", "{description}", $description);
	echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
}

function ipaddr_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ipclass=new IP();
	$ipaddr=$_POST["ipaddr"];
	$description=$_POST["description"];
	$date=date("Y-m-d H:i:s");
	$q=new postgres_sql();


	
	
	if(preg_match("#^([0-9]+)\.([0-9]+)\.0\.0\/16$#", $ipaddr,$re)){
		$sock=new sockets();
		$sock->getFrameWork("rbldnsd.php?white16=".urlencode($ipaddr));
		return;
	}
	if(strpos($ipaddr,"/255.255.255.0")>0){$expl=explode("/",$ipaddr);$ipaddr=$expl[0]."/24";}

	
	if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.0\/24$#", $ipaddr,$re)){

		for($i=1;$i<255;$i++){
			$ipaddr="{$re[1]}.{$re[2]}.{$re[3]}.$i";
			if(!$ipclass->isIPAddress($ipaddr)){continue;}
			$description=gethostbyaddr($ipaddr);
			$date=date("Y-m-d H:i:s");
			$q->QUERY_SQL("DELETE FROM rbl_blacklists WHERE ipaddr='$ipaddr'");
			$q->QUERY_SQL("DELETE FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
			$q->QUERY_SQL("INSERT INTO rbl_whitelists (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");
		}
		
		return;

	}
	if(preg_match("#^([0-9\.]+)#",$ipaddr,$re)){$ipaddr=$re[1];}
	if(!$ipclass->isIPAddress($ipaddr)) {
		$ipaddr = gethostbyaddr($ipaddr);
	}
	if(!$ipclass->isIPAddress($ipaddr)) {
		echo "$ipaddr not an IP address";
		return;
	}

	$q->QUERY_SQL("DELETE FROM rbl_blacklists WHERE ipaddr='$ipaddr'");
	$q->QUERY_SQL("DELETE FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
    $description="(".gethostbyaddr($ipaddr).") - $description";
	$q->QUERY_SQL("INSERT INTO rbl_whitelists (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");
	if(!$q->ok){echo "$ipaddr: $q->mysql_error";}


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


function search(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new postgres_sql();
	$t=time();


	$jsRestart=$tpl->framework_buildjs("/rbldnsd/compile",
	"rbldnsd.compile.progress",
	"rbldnsd.compile.progress.log",
		"progress-rbldnsd-restart");

	$topbuttons[] = array("Loadjs('$page?ipaddr-js=&function={$_GET["function"]}');", ico_plus, "{new_address}");
	$topbuttons[] = array("Loadjs('$page?ipaddr-import-js=yes&function={$_GET["function"]}');", ico_import, "{import}");
	$topbuttons[] = array($jsRestart, ico_run, "{compile_rules}");

	
	$search=trim($_GET["search"]);
	$aliases["ipaddr"]="ipaddr";
	$querys=$tpl->query_pattern($search,$aliases);
	$MAX=$querys["MAX"];
	if($MAX==0){$MAX=150;}
	$sql="SELECT * FROM rbl_whitelists {$querys["Q"]} ORDER BY zdate DESC LIMIT $MAX";
	
	if(preg_match("#^([0-9\.]+)#", $search)){
		$sql="SELECT * FROM rbl_whitelists WHERE ipaddr='$search' ORDER BY zdate DESC LIMIT $MAX";
	}
	
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}
	

	$TRCLASS=null;
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$ipaddr=$ligne["ipaddr"];
		$zDate=strtotime($ligne["zdate"]);
		$time=$tpl->time_to_date($zDate,true);
		$class_text=null;
		$description=$ligne["description"];
		$ipaddr=$tpl->td_href($ipaddr,null,"Loadjs('$page?ipaddr-js=$ipaddr&function={$_GET["function"]}');");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>{$time}</td>";
		$html[]="<td style='width:1%' nowrap><span class='$class_text'>$ipaddr</span></td>";
		$html[]="<td style='width:99%' nowrap><span class='$class_text'>$description</span></td>";
		$html[]="<td style='width:1%' class='center'nowrap>".$tpl->icon_delete("Loadjs('$page?delete={$ligne["ipaddr"]}&md=$md')")."</td>";
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

	$dnsblanswerwith=$tpl->_ENGINE_parse_body("{dnsblanswerwith}");
	$dnsblanswerwith=str_replace("%s","127.0.0.9",$dnsblanswerwith);
	$TINY_ARRAY["TITLE"]="{APP_RBLDNSD}: {rules} {whitelist}";
	$TINY_ARRAY["ICO"]="fa fa-ban";
	$TINY_ARRAY["EXPL"]="{APP_RBLDNSD_EXPLAIN}<br>$dnsblanswerwith";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="<small>$sql</small>
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"false\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
?>
