<?php
//$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.rbldnsd.tools.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["source-events-search"])){source_events_search();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["src-js"])){src_js();exit;}
if(isset($_GET["ipaddr-import-js"])){ipaddr_import_js();exit;}
if(isset($_GET["src-popup"])){src_popup();exit;}
if(isset($_POST["src"])){src_save();exit;}
if(isset($_POST["ipaddr"])){ipaddr_save();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}
if(isset($_POST["import"])){import_save();exit;}
if(isset($_GET["ipaddr-import-popup"])){ipaddr_import_popup();exit;}
if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_POST["reset"])){reset_perform();exit;}
if(isset($_GET["rundown"])){rundown();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["source-events"])){source_events_js();exit;}
if(isset($_GET["source-events-start"])){source_events_start();exit;}

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


	$html[]=$tpl->search_block($page,"postgres","rbl_sources","rbl_sources","");
	echo $tpl->_ENGINE_parse_body($html);

}
function reset_js(){
	$tpl=new template_admin();
	$function=$_GET["function"];
	$tpl->js_confirm_delete("{reset} {database}","reset","yes","$function()");
}
function reset_perform():bool{
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/rbldnsd/reset"));
	if(!$data->Status){
		echo $data->Error;
		return false;
	}
	return admin_tracks("Reseting DNSBL service data");
}
function rundown():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $id=intval($_GET["rundown"]);
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/rbldnsd/database/update/$id"));
    if(!$data->Status){
        return $tpl->js_error($data->Error);
    }
    header("content-type: application/x-javascript");
    echo "$function();";
    return admin_tracks("Run the single update of DNSBL source #$id");
}
function enable():bool{
    $tpl=new template_admin();
    $id=intval($_GET["enable"]);
    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT enabled FROM rbl_sources WHERE id='$id'"));
    $zEnable=intval($ligne["enabled"]);
    if($zEnable==0){
        $q->QUERY_SQL("UPDATE rbl_sources SET enabled='1' WHERE id='$id'");
        if(!$q->ok){
            echo $tpl->js_error($q->mysql_error);
            return false;
        }
        return admin_tracks("Enabled DNSBL source #$id");
    }
    $q->QUERY_SQL("UPDATE rbl_sources SET enabled='0' WHERE id='$id'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    return admin_tracks("Disabled DNSBL source #$id");
}
function source_events_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["source-events"]);
    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT description FROM rbl_sources WHERE id='$ID'"));
    $description=$ligne["description"];
    return $tpl->js_dialog2($description,"$page?source-events-start=$ID",1024);
}
function source_events_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["source-events-start"]);
    echo $tpl->search_block($page,"","","","&source-events-search=$ID");
    return true;
}
function source_events_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["source-events-search"]);
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    $search=urlencode(base64_encode($search));
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/reputation/source/events/$ID/$search/$rp");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
        return false;
    }

    foreach ($json->Logs as $line){
        $line=trim($line);
        if(!preg_match("#^(.+?)\s+(.+?)\s+(.+)#", $line,$re)){
            echo "<strong style='color:red'>$line</strong><br>";
            continue;}
        $date="$re[1] $re[2]";
        $xtime=strtotime($date);
        $FTime=date("Y-m-d H:i:s",$xtime);
        $curDate=date("Y-m-d");
        $FTime=trim(str_replace($curDate, "", $FTime));
        $line=$re[3];

        if(preg_match("#(Fatal error|syntax error|unexpected|exiting on|format error|unable to|TIME_ERROR)#",$line)){
            $line="<span class='text-danger'>$line</span>";

        }
        if(preg_match("#(Listen normally|Selected source)#",$line)){
            $line="<span class='text-info'>$line</span>";

        }



        $html[]="<tr>
				<td style='width: 1%' nowrap>$FTime</td>
				<td>$line</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function src_save():bool{
	$tpl=new template_admin();

	$id=intval($_POST["src"]);
	$tpl->CLEAN_POST();
	$q=new postgres_sql();
	$url=pg_escape_string($_POST["url"]);
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM rbl_sources WHERE id='$id'"));
	$OLDB=$ligne["database"];
    $resetrecords=intval($_POST["resetrecords"]);

	$descr=pg_escape_string($_POST["description"]);
	$database=$_POST["database"];
	$ttl=intval($_POST["ttl"]);
	if($id==0) {
		$q->QUERY_SQL("INSERT INTO rbl_sources (url , description,database,ttl,resetrecords) VALUES ('$url' , '$descr','$database',$ttl,$resetrecords)");
	}else{
		$q->QUERY_SQL("UPDATE rbl_sources SET description='$descr',url='$url',
                       database='$database',ttl=$ttl,resetrecords=$resetrecords WHERE id=$id");
	}
	if(!$q->ok){
		echo $tpl->post_error($q->mysql_error);
		return false;
	}
	$sock=new sockets();
	if($database<>$OLDB){
		$sock->REST_API("/rbldnsd/movedata/$id/$OLDB/$database");
	}
	$sock->REST_API("/rbldnsd/update");
	return admin_tracks("Edit/add new DNSBL source $url");

}

function delete(){
	$id=$_GET["delete"];
	$tpl=new template_admin();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT description FROM rbl_sources WHERE id='$id'"));
	$description=$ligne["description"];
	$tpl->js_confirm_delete("{delete} {source} #$id $description","delete",$id,"$('#{$_GET["md"]}').remove()");
}
function delete_perform():bool{
	$id=$_POST["delete"];
	$sock=new sockets();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT description FROM rbl_sources WHERE id='$id'"));
	$description=$ligne["description"];
	$json=json_decode($sock->REST_API("/rbldnsd/source/delete/$id"));
	if(!$json->Status){
		echo $json->Error;
		return false;
	}
	return admin_tracks("Delete DNSBL source $id $description");
}


function src_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$id=intval($_GET["src-js"]);
	if($id==0){$title="{new_entry}";}else{$title="{source} #$id";}
	$tpl->js_dialog($title, "$page?src-popup=$id&function={$_GET["function"]}");
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

function src_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new postgres_sql();
	$id=$_GET["src-popup"];


	$jsafter="BootstrapDialog1.close();{$_GET["function"]}()";
	if($id==0){
		$bt="{add}";
		$title="{new_rule}";
		$description="Added - ".date("Y-m-d H:i:s");
		$database="1270002";
        $resetrecords=0;
		$ttl=2160;
	}else{
		$bt="{apply}";

		$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM rbl_sources WHERE id='$id'"));
		$description=$ligne["description"];
		$title=null;
		$database=$ligne["database"];
		$ttl=intval($ligne["ttl"]);
        $resetrecords=intval($ligne["resetrecords"]);
	}
	$DBZ=TranslateTables();
	foreach ($DBZ as $key=>$array){
		$DBS[$key]=$array["title"];
	}

	$form[]=$tpl->field_hidden("src",$id);
	$form[]=$tpl->field_text("url","{url}", $ligne["url"]);
	$form[]=$tpl->field_numeric("ttl","{record_lifetime} ({hours})",$ttl);
    $form[]=$tpl->field_checkbox("resetrecords","{reset_records}",$resetrecords);
	$form[]=$tpl->field_array_hash($DBS,"database","nonull:{database}", $database);
	$form[]=$tpl->field_text("description", "{description}", $description);
	echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
}

function search(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new postgres_sql();
	$function=$_GET["function"];
	$t=time();

	$DBS=TranslateTables();
	$jsRestart=$tpl->framework_buildjs("/rbldnsd/update","rbldnsd.sources.progress",
		"rbldnsd.sources.log","progress-rbldnsd-restart","$function()");

	$topbuttons[] = array("Loadjs('$page?src-js=0&function={$_GET["function"]}');", ico_plus, "{new_source}");
	$topbuttons[] = array("Loadjs('$page?reset-js=yes&function={$_GET["function"]}');", ico_trash, "{reset}");
	$topbuttons[] = array($jsRestart, ico_run, "{launch_updates}");

	
	$search=trim($_GET["search"]);

	$querys=$tpl->query_pattern($search);
	$MAX=$querys["MAX"];
	if($MAX==0){$MAX=150;}
	$query="";
	if($search<>null){
		$search="*$search*";
		$search=str_replace("**","*",$search);
		$search=str_replace("*","%",$search);
		$query="WHERE description LIKE '$search' OR url LIKE '$search'";
	}


	$sql="SELECT * FROM rbl_sources $query ORDER BY ID ASC LIMIT $MAX";
	$results = $q->QUERY_SQL($sql);


	$TRCLASS=null;
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{updated}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{checked}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>TTL</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{source}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{records}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{database}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$TD1="tyle='width:1%' nowrap";

	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$url=$ligne["url"];
		$zDate=strtotime($ligne["updated"]);
		$description=$ligne["description"];
		$description=str_replace("\n","<br>",$description);
		$description=wordwrap($description, 90, "<br />\n");
		$checked=strtotime($ligne["checked"]);
		$enabled=$ligne["enabled"];
		$iserror=intval($ligne["iserror"]);
		$lasterror=$ligne["lasterror"];
		$time=$tpl->time_to_date($zDate,true);
		$checked=$tpl->time_to_date($checked,true);
		$records=$tpl->FormatNumber(intval($ligne["records"]));
		$database=$ligne["database"];
        $resetrecords=intval($ligne["resetrecords"]);
		$class_text=null;
		$ID=intval($ligne["id"]);
		$databaseName=$DBS[$database]["title"];
        $ttl=intval($ligne["ttl"]);
        if($ttl==0){$ttl=2160;}
        $ttltext=convertHoursToDHM($ttl);
        if($resetrecords==1){$ttltext="{reset}";}

		$hostname = parse_url($url, PHP_URL_HOST);
		$enabled=$tpl->icon_check($enabled,"Loadjs('$page?enable=$ID&function=$function')");
		$description=$tpl->td_href($description,null,"Loadjs('$page?src-js=$ID&function=$function')");

		if($iserror==1){
			$class_text="text-danger";
			$lasterror=str_replace("<br>","\n",$lasterror);
			$lasterror=strip_tags($lasterror);
			$lasterror=str_replace("\n","<br>",$lasterror);
			$lasterror=wordwrap($lasterror, 90, "<br />\n");
			$description="$description<br><small><span class='text-danger'>$lasterror</small></span>";
		}
        if(is_file("/var/log/reputation.$ID.log")) {
            $checked = $tpl->td_href($checked, null, "Loadjs('$page?source-events=$ID')");
        }
        $update=$tpl->icon_download("Loadjs('$page?rundown=$ID&function=$function')");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td $TD1><i class='".ico_download." $class_text'></i></td>";
		$html[]="<td $TD1><span class='$class_text'>$time</span></td>";
		$html[]="<td $TD1><span class='$class_text'>$checked</span></td>";
        $html[]="<td $TD1><span class='$class_text'>$ttltext</span></td>";
		$html[]="<td style='width:99%' nowrap><span class='$class_text'>$description</span></td>";
		$html[]="<td $TD1><span class='$class_text'>$hostname</span></td>";
		$html[]="<td $TD1><span class='$class_text'>$records</span></td>";
		$html[]="<td $TD1><span class='$class_text'>$databaseName</span></td>";
        $html[]="<td $TD1>$update</td>";
        $html[]="<td $TD1>$enabled</td>";
		$html[]="<td $TD1>".$tpl->icon_delete("Loadjs('$page?delete=$ID&md=$md')")."</td>";
		$html[]="</tr>";
	
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='8'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

	$TINY_ARRAY["TITLE"]="{APP_RBLDNSD}: {sources}";
	$TINY_ARRAY["ICO"]=ico_download;
	$TINY_ARRAY["EXPL"]="{APP_RBLDNSD_EXPLAIN}";
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
function convertHoursToDHM($totalHours) {
    $totalMinutes = $totalHours * 60;

    $days = floor($totalMinutes / 1440); // 1440 minutes in a day
    $minutesRemaining = $totalMinutes % 1440;

    $hours = floor($minutesRemaining / 60);
    $minutes = $minutesRemaining % 60;

    $parts = [];

    if ($days > 0) {
        if ($days == 1) {
            $parts[] = "$days {day}";
        }else{
            $parts[] = "$days {days}";
        }
    }
    if ($hours > 0 || $days > 0) { // Show hours if days is non-zero or hours > 0
        if($hours>0) {
            if($hours==1) {
                $parts[] = "$hours {hour}";
            }else{
                $parts[] = "$hours {hours}";
            }
        }
    }
    if ($minutes > 0) {
    $parts[] = "$minutes {minutes}";
    }

    return implode(", ", $parts);
}
?>
