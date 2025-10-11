<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["failedloginattempts-js"])){failed_logging_js();exit;}
if(isset($_GET["licenseserver-js"])){failed_license_server_js();exit;}
if(isset($_GET["licenseserver-popup"])){failed_license_server_popup();exit;}


if(isset($_GET["failed-logging"])){failed_logging_start();exit;}
if(isset($_GET["failed-login-search"])){failed_logging_search();exit;}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["trackadmin"])){search_admin();exit;}
if(isset($_GET["search-admin"])){search_admins();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["remove-events"])){remove_events_js();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}
if(isset($_GET["smtp-js"])){smtp_js();exit;}
if(isset($_GET["smtp-popup"])){smtp_popup();exit;}
if(isset($_POST["ENABLED_SQUID_WATCHDOG"])){smtp_save();exit;}
if(isset($_POST["remove-events"])){remove_events_perform();exit;}
if(isset($_GET["sys-events"])){search_system();exit;}
if(isset($_GET["sys-events-block"])){search_system_block();exit;}
if(isset($_GET["button-fw-system-watchdog"])){echo base64_decode($_GET["button-fw-system-watchdog"]);exit;}
page();

function ShowID_js():bool{
	$id=$_GET["ShowID-js"];
	if(!is_numeric($id)){
		return false;
	}$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT subject FROM squid_admin_mysql WHERE ID=$id";
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$subject=$tpl->javascript_parse_text($ligne["subject"]);
    if(!isset($_GET["function"])){$_GET["function"]="";}
	return $tpl->js_dialog($subject, "$page?ShowID=$id&function={$_GET["function"]}");
}
function failed_logging_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog1("{failedloginattempts}", "$page?failed-login-search=yes",1024);

}
function failed_license_server_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    return $tpl->js_dialog1("License server", "$page?licenseserver-popup=yes",800);

}
function failed_license_server_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LicensingServerError=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicensingServerError"));
    echo $tpl->div_error($LicensingServerError);
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{system_events}"]="$page?sys-events=yes&scriptname={$_GET["scriptname"]}";
    $TrackAdmins=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TrackAdmins"));
    if($TrackAdmins==1) {
        if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            $array["{track_administrators}"] = "$page?trackadmin=yes";
        }
    }
    $array["{failedloginattempts}"]="$page?failed-logging=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function empty_js(){
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

	$title="{system_events}";
	$tpl->js_confirm_empty($title,"empty","yes","{$_GET["function"]}();");
	
}

function empty_table(){
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$q->QUERY_SQL("DROP TABLE squid_admin_mysql");
    admin_tracks("Empty table of system events");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");
	
}

function ShowID(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sql="SELECT * FROM squid_admin_mysql WHERE ID={$_GET["ShowID"]}";
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$content=$tpl->_ENGINE_parse_body($ligne["content"]);
	$content=nl2br($content);
	echo "<p>$content</p>";

	$filename=$ligne["filename"];
	$line=$ligne["line"];
    $html[]="<div style='margin-top:10px' id='progress-remove-event'></div>";
	$html[]="<div style='text-align:right;margin-top:10px'>";

    $users=new usersMenus();

    if($users->AsSystemAdministrator) {
        $html[] = $tpl->button_autnonome("{remove_all_same}", "Loadjs('$page?remove-events=yes&filename=$filename&line=$line&function={$_GET["function"]}');",
            "fas fa-trash-alt", "AsSystemAdministrator", 0, "btn-danger");
    }
	$html[]="</div>";
	echo $tpl->_ENGINE_parse_body($html);

}
function remove_events_js(){
    $filename=$_GET["filename"];
    $line=$_GET["line"];
    $tpl=new template_admin();
    $funcadd=null;

    if($_GET["function"]<>null){
        $funcadd="{$_GET["function"]}();";
    }

    $tpl->js_confirm_empty("{remove_all_same} $filename ($line)","remove-events","$filename;$line",
        "BootstrapDialog1.close();{$funcadd}RefreshNotifs();");
}
function remove_events_perform(){
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $tt=explode(";",$_POST["remove-events"]);
    $filename=$tt[0];
    $line=intval($tt[1]);

    $q->QUERY_SQL("DELETE FROM squid_admin_mysql WHERE filename='$filename' AND line='$line'");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");
    admin_tracks("Removed all system events with $filename filename and line $line");

}
function search_system():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $scriptname=$_GET["scriptname"];
    $html[]="<div style='margin-top:15px' id='search-block'></div>";
    $html[]="<script>LoadAjaxSilent('search-block','$page?sys-events-block=yes&scriptname=$scriptname');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function search_system_block():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $method=null;
    if(isset($_GET["method"])) {
        $method = $_GET["method"];
    }

    $html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/system_events.db","squid_admin_mysql","table-loader","&table=yes&scriptname={$_GET["scriptname"]}&method=$method");
    $html[]="<div id='table-loader'></div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function search_admin(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $method=null;
    if(isset($_GET["method"])) {
        $method = "&method=".$_GET["method"];
    }
    $scriptanme=null;
    if(isset($_GET["scriptname"])){
        $scriptanme="&scriptname={$_GET["scriptname"]}";
    }

    $html[]="<div style='margin-top:15px'>";
    $html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/admins.db","admintracks","table-sysadmin","&search-admin=yes$scriptanme$method");
    $html[]="<div id='table-sysadmin'></div>";
    $html[]="</div>";
    $html[]="<script>LoadAjaxSilent('button-fw-system-watchdog','$page?button-fw-system-watchdog=');</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $scriptanme=null;
    if(isset($_GET["scriptname"])){
        $scriptanme="&scriptname={$_GET["scriptname"]}";
    }

    $html=$tpl->page_header("{system_events}",ico_eye,"<div id='button-fw-system-watchdog'></div>",
        "$page?tabs=yes$scriptanme","events","progress-firehol-restart",false,"table-start");

if(isset($_GET["main-page"])){
	$tpl=new template_admin(null, $html);
	echo $tpl->build_firewall();
	return;
}
echo $tpl->_ENGINE_parse_body($html);
}

function failed_logging_search(){
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $tpl=new template_admin();
    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{service}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(intval($search["MAX"])>1500){$search["MAX"]=1500;}
    $ZZZ="";
    if(strlen($search["Q"])>1){
        $s=$search["Q"];
        $ZZZ="WHERE (user LIKE '$s' OR terminal LIKE '$s' OR timestamp LIKE '$s' OR host LIKE '$s')";
    }

    $sql="SELECT *  FROM btmp_records $ZZZ ORDER BY timestamp DESC LIMIT {$search["MAX"]}";
    $results=$q->QUERY_SQL($sql);

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text_class=null;
        $id=md5(serialize($ligne));
        $timestamp=strtotime($ligne["timestamp"]);
        $zdate=$tpl->time_to_date($timestamp,true);
        $username=$ligne["user"];
        $ipaddr=$ligne["host"];
        $terminal=$ligne["terminal"];
        $cloock=ico_clock;
        $useri=ico_user;
        $comp=ico_computer;
        $Serv=ico_server;
        $html[]="<tr class='$TRCLASS' id='$id'>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><i class='$cloock'></i>&nbsp;$zdate</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><i class='$useri'></i>&nbsp;$username</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><i class='$comp'></i>&nbsp;$ipaddr</td>";
        $html[]="<td class=\"$text_class\" style='width:99%'><i class='$Serv'></i>&nbsp;$terminal</td>";
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
    $html[]="</table><div><i></i></div>";
    $headsjs="";
    if(isset($_GET["tiny"])){
        $TINY_ARRAY["TITLE"]="{failedloginattempts}";
        $TINY_ARRAY["ICO"]=ico_eye;
        $TINY_ARRAY["EXPL"]="{BTMMPExplains}";
        $TINY_ARRAY["BUTTONS"]="";
        $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    }

    $html[]="
	<script>
	$headsjs
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });

</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function search_admins(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
    $t=$_GET["t"];
    if(!is_numeric($t)){$t=time();}
    $date=$tpl->_ENGINE_parse_body("{date}");

    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$date</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{events}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(intval($search["MAX"])>1500){$search["MAX"]=1500;}
    $sql="SELECT *  FROM admintracks {$search["Q"]} ORDER BY time DESC LIMIT {$search["MAX"]}";
    $results=$q->QUERY_SQL($sql);


   if(!$q->ok){

        echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";
    }


    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text_class=null;
        $id=md5(serialize($ligne));
        $zdate=$tpl->time_to_date($ligne["time"],true);
        //time,username,ipaddr,operation

        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $text=$tpl->_ENGINE_parse_body($ligne["operation"]);

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$zdate</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$username - $ipaddr</td>";
        $html[]="<td class=\"$text_class\" style='width:99%'>$text</td>";
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
    $html[]="</table><div><i>$sql</i></div>";
    $html[]="
	<script>
	
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });

</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function failed_logging_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&failed-login-search=yes&tiny=yes");
    echo "</div>";
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$eth_sql=null;
	$token=null;
	$class=null;

	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$date=$tpl->_ENGINE_parse_body("{date}");
	$title=$tpl->_ENGINE_parse_body("{IDS} {events}");
	$events=$tpl->javascript_parse_text("{events}");
	$daemon=$tpl->_ENGINE_parse_body("{daemon}");
    $method=$_GET["method"];
	
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
	$nic=new networking();
	$js="OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
	if($_GET["eth"]==null){$class=" active";}

	$t=time();
	$add="Loadjs('$page?ruleid-js=0$token',true);";


    //LoadAjaxSilent('search-block','$page?sys-events-block=yes&scriptname=$scriptname');

    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==1) {
        $js="LoadAjaxSilent('search-block','$page?sys-events-block=yes&method=squid');";
        $topbuttons[] = array($js, ico_filter, "{APP_SQUID}");
    }



    $topbuttons[]=array("Loadjs('fw.export.sqlite.php?table=squid_admin_mysql&file=system_events');",ico_csv,"{export}");
    $topbuttons[]=array("Loadjs('$page?empty-js=yes&function={$_GET["function"]}')",ico_trash,"{empty}");



    $buttons=$tpl->table_buttons($topbuttons);

    $buttons_encoded=urlencode(base64_encode($buttons));
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $buttons_encoded=null;
    }



	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	$aliases["src_ip"]="ipaddr";
	$aliases["ipaddr"]="ipaddr";
	$aliases["address"]="ipaddr";
	$aliases["mac"]="mac";
	$aliases["text"]="subject,function,filename";
	if(preg_match("#(file|script|fichier|process|processus)([\s|=]+)([0-9a-z\.]+)#i", $_GET["search"],$re)){
		$_GET["search"]=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $_GET["search"]);
		$_GET["scriptname"]=$re[3];
	}
	
	$critic_aliases="critic|urgence|urgency|emergency|important";
	if(preg_match("#($critic_aliases)#", $_GET["search"])){
		$ADDON="severity=0";
		$tt=explode("|",$critic_aliases);
		foreach ($tt as $word){
			$_GET["search"]=str_replace($word, "", $_GET["search"]);
		}
	}
	
	$warning_aliases="warn|warning|attention";
	if(preg_match("#($warning_aliases)#", $_GET["search"])){
		$ADDON="severity=1";
		$tt=explode("|",$warning_aliases);
		foreach ($tt as $word){
			$_GET["search"]=str_replace($word, "", $_GET["search"]);
		}
	}

	$info_aliases="info|information";
	if(preg_match("#($info_aliases)#", $_GET["search"])){
		$ADDON="severity=2";
		$tt=explode("|",$info_aliases);
		foreach ($tt as $word){
			$_GET["search"]=str_replace($word, "", $_GET["search"]);
		}
	}
	$_GET["search"]=trim($_GET["search"]);
	$_SESSION["SYSEVS_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])),$aliases,$ADDON);
	

	
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	@chmod("/home/artica/SQLITE/system_events.db", 0644);
	@chown("/home/artica/SQLITE/system_events.db", "www-data");

    if(intval($search["MAX"])>1500){$search["MAX"]=1500;}
	$sql="SELECT ID,zDate,subject,severity,function,line,filename, LENGTH(content) as content  FROM squid_admin_mysql {$search["Q"]} ORDER BY zDate DESC LIMIT {$search["MAX"]}";

    if($method=="squid"){
        $_GET["scriptname"]="exec.squid.disable.php,class.status.squid.inc,exec.squid.watchdog.php,squid-service,web-filtering,squid,class.squid.automatic-tasks.inc,exec.squid.php";

    }
	
	if($_GET["scriptname"]<>null){
		$WHERE="WHERE filename='{$_GET["scriptname"]}'";
		if(strpos($_GET["scriptname"], ",")>0){
			$scripts=explode(",",$_GET["scriptname"]);
			foreach ($scripts as $ffile){$OR[]="(filename='{$ffile}')";}
			$WHERE="WHERE (".@implode(" OR ", $OR).")";
		}
		
		$sql="SELECT * FROM (SELECT ID,zDate,subject,severity,function,line,filename, 
		LENGTH(content) as content  FROM squid_admin_mysql $WHERE ORDER BY zDate DESC LIMIT {$search["MAX"]}) as t {$search["Q"]}";
	}
	
	
	$results=$q->QUERY_SQL($sql);
	

	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$date</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$daemon</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	
	if(!$q->ok){
		
		echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";
	}
	
	$severityCL[0]="label-danger";
	$severityCL[1]="label-warning";
	$severityCL[2]="label-primary";
	
	$severityTX[0]="text-danger";
	$severityTX[1]="text-warning";
	$severityTX[2]="text-primary";
	$curs="OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='auto'\"";
	
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$id=md5(serialize($ligne));
		$zdate=$tpl->time_to_date($ligne["zDate"],true);
		$severity_class=$severityCL[$ligne["severity"]];
		$js="Loadjs('$page?ShowID-js={$ligne["ID"]}&function={$_GET["function"]}')";
		$link="<span><i class='fa fa-search ' id='$id'></i>&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"$js\" class='{$severityTX[$ligne["severity"]]}' style='font-weight:bold'>";
	    if(!isset($ligne["hostname"])){$ligne["hostname"]=null;}
		if($ligne["content"]==0){$link="<span style='font-weight:bold'>";$js="blur()";}
		
		if(preg_match("#\/var\/lib\/ufdbartica\/([0-9]+)#", $ligne["subject"],$re)){
			$zcat=new mysql_catz();
			$_CATEGORIES_NAME=$zcat->CategoryIntToStr($re[1]);
			$ligne["subject"]=str_replace("/var/lib/ufdbartica/{$re[1]}", $_CATEGORIES_NAME, $ligne["subject"]);
		}
		
		
		
		
		$text=$link.$tpl->_ENGINE_parse_body($ligne["subject"]."</a></span>
		<div style='font-size:10px'>{host}:{$ligne["hostname"]} {function}:{$ligne["function"]}, {line}:{$ligne["line"]}</div>");
		if(strpos(" ".$ligne["filename"], "/")>0){$ligne["filename"]=basename($ligne["filename"]);}
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap><div class='label $severity_class' style='font-size:13px;padding:10px;width:100%' $curs OnClick=\"$js\" >$zdate</a></div></td>";
		$html[]="<td class=\"$text_class\" style='width:99%'>$text</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>{$ligne["filename"]}</a></td>";
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
	$html[]="</table><div><i>$sql</i></div>";
	$html[]="
	<script>
	LoadAjaxSilent('button-fw-system-watchdog','$page?button-fw-system-watchdog=$buttons_encoded');
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });

</script>";

			echo @implode("\n", $html);

}
// admintracks (time,username,ipaddr,operation) VALUES ('$time','$ipaddr','$uid','$text')