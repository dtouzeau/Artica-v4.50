<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!getRights()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["run-js"])){run_js();exit;}
if(isset($_GET["run-popup"])){run_popup();exit;}
if(isset($_GET["table"])){table_loader();exit;}
if(isset($_GET["new-query-js"])){query_new_js();exit;}
if(isset($_GET["query-js"])){query_js();exit;}
if(isset($_GET["query-form"])){query_form();exit;}
if(isset($_POST["query-zmd5"])){query_save();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["delete-popup"])){delete_popup();exit;}
if(isset($_POST["delete-confirm"])){delete_confirm();exit;}

page();


function getRights(){
	$users=new usersMenus();
	if($users->AsProxyMonitor){return true;}
	if($users->AsWebStatisticsAdministrator){return true;}
	return false;
}
function query_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT title FROM reports_cache WHERE `zmd5`='{$_GET["run-js"]}'"));
	$title=$tpl->javascript_parse_text($ligne["title"]);
	$type=$_GET["type"];
	$tpl->js_dialog("$title","$page?query-form=yes&zmd5={$_GET["zmd5"]}&type={$_GET["type"]}");	
	
}
function query_new_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$type=$_GET["type"];
	$tpl->js_dialog("{new_query}: $type","$page?query-form=yes&zmd5={$_GET["zmd5"]}&type={$_GET["type"]}");
}
function run_js(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new template_admin();	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT title FROM reports_cache WHERE `zmd5`='{$_GET["run-js"]}'"));
	$title=$tpl->javascript_parse_text($ligne["title"]);
	$tpl->js_dialog($title, "$page?run-popup={$_GET["run-js"]}");
}
function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{delete}: {report}");
	$tpl->js_dialog_confirm($title, "$page?delete-popup={$_GET["delete-js"]}&page={$_GET["page"]}");
}
function delete_popup(){
	$page=CurrentPageName();
	$jspage=$page;
	$q=new mysql_squid_builder();
	$tpl=new template_admin();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT title FROM reports_cache WHERE `zmd5`='{$_GET["delete-popup"]}'"));
	$t=time();
	
	if(trim($_GET["page"])<>null){$jspage=$_GET["page"];}
	$html="
	<div class=row>
	<div class=\"alert alert-danger\">{delete} {report} {$ligne["title"]} ?</div>
	<div style='text-align:right;margin-top:20px'><button class='btn btn-danger btn-lg' type='button'
	OnClick=\"javascript:Remove$t()\">{yes_delete_it}</button></div>
	</div>
	<script>
	var xPost$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	DialogConfirm.close();
	LoadAjax('table-loader','$jspage?table=yes');
	}
	
	function Remove$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete-confirm', '{$_GET["delete-popup"]}');
	XHR.sendAndLoad('$page', 'POST',xPost$t);
	}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function delete_confirm(){
	$zmd5=$_POST["delete-confirm"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");
	
	$table="{$zmd5}report";
	$postgres=new postgres_sql();
	$postgres->QUERY_SQL("DROP TABLE \"$table\"");
	
	
	$table="chronos$zmd5";
	if($q->TABLE_EXISTS($table)){
		$q->QUERY_SQL("DROP TABLE `$table`");
	}
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?remove-report=$zmd5");
	
	
}


function download(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new template_admin();
	$zmd5=$_GET["download"];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT title,report_type,values_size FROM reports_cache WHERE `zmd5`='$zmd5'"));
	if(!$q->ok){
		echo $q->mysql_error_html();
	}
	$title=$tpl->javascript_parse_text($ligne["title"]);
	$title=str_replace(" ", "-", $title);
	$title=str_replace(";", "-", $title);
	$title=str_replace(",", "-", $title);
	$size=$ligne["values_size"];
	$type=$ligne["report_type"];
	$fsize = $size;
	
	if($type=="EXTRACT"){
		$filedata="/home/squid/statistics-extractor/{$zmd5}.csv";
		
		
	}
	
	if($GLOBALS["VERBOSE"]){
		echo $filedata."<br>\n";
	}
	
	if(!$GLOBALS["VERBOSE"]){
		header('Content-type: text/csv');
		header('Content-Transfer-Encoding: binary');
		header("Content-Disposition: attachment; filename=\"$title.csv\"");
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
		header("Content-Length: ".$fsize);
		ob_clean();
		flush();
		readfile($filedata);
	}
	
	
}

function run_popup(){
	$page=CurrentPageName();
	if(isset($_GET["page"])){$page=$_GET["page"];}
	$zmd5=$_GET["run-popup"];

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.statistics-{$zmd5}.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.statistics-{$zmd5}.log";
	$ARRAY["CMD"]="squid.php?build-110-report={$zmd5}";
	$ARRAY["TITLE"]="{please_wait_preparing_settings}";
	$ARRAY["BEFORE"]="BootstrapDialog1.close();";
	$ARRAY["AFTER"]="LoadAjax('table-loader','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";
	
	$html="
			<p><center>
			<button class='btn btn-primary btn-lg' type='button' OnClick=\"javascript:BootstrapDialog1.close();$jsrestart\">{build_statistics}</button>
			</center></p>
	
			
			";
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);
}

function query_form(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new template_admin();
	$type=$_GET["type"];
	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	$title=null;
	$tpl->field_hidden("zmd5", $_GET["zmd5"]);
	$btname="{add}";


	$members_default=null;
	$search_default=null;
	$from_date_default=null;
	$to_date_default=null;
	$zmd5=trim($_GET["zmd5"]);
	$title="{new_query}: {$_GET["type"]}";
	$CRONOLOGY=1;
	$btname="{add}";
	if($zmd5<>null){
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT title,report_type,values_size,params FROM reports_cache WHERE `zmd5`='$zmd5'"));
		$title="{$ligne["title"]}";
		$array=unserialize($ligne["params"]);
		
		$date1=strtotime($array["FROM"]);
		$date2=strtotime($array["TO"]);
		$from_date_default=date("Y-m-d",$date1);
		$to_date_default=date("Y-m-d",$date2);
		$from_time=date("H:i:s",$date1);
		$to_time=date("H:i:s",$date2);
		$members_default=$array["USER"];
		$_GET["type"]=$ligne["report_type"];
		$CRONOLOGY=intval($array["CRONOLOGY"]);
		$btname="{apply}";
	}
	
	
	
	$BootstrapDialog="BootstrapDialog1.close();";
	if($from_date_default==null){$from_date_default=date("Y-m-d");}
	if($to_date_default==null){$to_date_default=date("Y-m-d");}
	if($from_time==null){$from_time="00:00:00";}
	if($to_time==null){$to_time="23:59:00";}
	
	$members["ALL"]="{all_values}";
	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	
	if($zmd5<>null){
		$form[]=$tpl->field_text("title", "{title2}", $ligne["title"]);
	}
	$form[]=$tpl->field_hidden("query-zmd5", $_GET["zmd5"]);
	$form[]=$tpl->field_hidden("type", $_GET["type"]);
	$form[]=$tpl->field_array_hash($members,"members","{members}",$members_default,false);
	$form[]=$tpl->field_date("from-date","{from_date}",$from_date_default);
	$form[]=$tpl->field_clock("from-time", "{from_time}", $from_time);
	$form[]=$tpl->field_date("to-date","{to_date}",$to_date_default);
	$form[]=$tpl->field_clock("to-time", "{to_time}", $to_time);
	if($_GET["type"]=="EXTRACT"){
		$form[]=$tpl->field_checkbox("CRONOLOGY","{chronology}",$CRONOLOGY,false,"{squid_statistics_chronology}");
	}

	echo $tpl->form_outside($title,@implode("\n", $form),null,$btname,
			"LoadAjax('table-loader','$page?table=yes');$BootstrapDialog");
	
}

function query_save(){
	$tpl=new templates();
	$zmd5=$_POST["query-zmd5"];
	$type=$_POST["type"];
	$date1=$_POST["from-date"]." ".$_POST["from-time"];
	$date2=$_POST["to-date"]." ".$_POST["to-time"];
	$search=$_POST["search"];
	if(isset($_POST["title"])){$_POST["title"]=url_decode_special_tool($_POST["title"]);}
	if($_POST["members"]==null){$_POST["members"]="ALL";}
	$date_int=strtotime($date1);
	$timetext1=$tpl->time_to_date($date_int);
	
	$date_int=strtotime($date2);
	$timetext2=$tpl->time_to_date($date_int);
	
	$members["ALL"]="{all_values}";
	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	
	$array["FROM"]=$date1;
	$array["TO"]=$date2;
	$array["INTERVAL"]=null;
	$array["USER"]=$_POST["members"];
	$array["SEARCH"]=$search;
	$array["CRONOLOGY"]=$_POST["CRONOLOGY"];
	$serialize=mysql_escape_string2(serialize($array));
	
	if($_POST["type"]=="EXTRACT"){
		if($search<>null){$text_search="{$members[$_POST["members"]]} {like} $search";}
		$title="{extract}: $timetext1 - {to} $timetext2 $text_search";
	}
	$title=mysql_escape_string2($title);
	$q=new mysql_squid_builder();
	$md5=md5($serialize.$type);
	if($zmd5==null){
		$sql="INSERT IGNORE INTO `reports_cache` (`zmd5`,`title`,`report_type`,`zDate`,`params`) VALUES
		('$md5','$title','{$_POST["type"]}',NOW(),'$serialize')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html(true);}
		return;
	}
	
	if($md5<>$zmd5){$size=",`values_size`='0'";}
	$sql="UPDATE reports_cache SET `title`='{$_POST["title"]}',params='$serialize',zDate=NOW()$size WHERE `zmd5`='$zmd5'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);}
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{statistics_extractor}</h1><i>{statistics_extractor_explain}</i></div>
	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>
		<div class='row'>
			<div id='table-loader'></div>
		</div>
	</div>



	<script>
	LoadAjax('table-loader','$page?table=yes');

	</script>";


	echo $tpl->_ENGINE_parse_body($html);

}

function table_loader(){
	$q=new mysql_squid_builder();
	$q->CheckReportTable();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$filter="WHERE report_type='EXTRACT'";
	$sql="SELECT title,zmd5,values_size,report_type,zDate FROM reports_cache  $filter ORDER BY zDate DESC";
	$report=$tpl->_ENGINE_parse_body("{report}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$familysite=$tpl->_ENGINE_parse_body("{familysite}");
	$title=$tpl->javascript_parse_text("{browse_cache}:{$_GET["report_type"]}");
	$delete_all=$tpl->javascript_parse_text("{delete_all}");
	$date=$tpl->javascript_parse_text("{date}");
	$delete_text=$tpl->_ENGINE_parse_body("{delete}");

	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
				<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.proxy.statistics.extractor.php?new-query-js=yes&type=EXTRACT');\"><i class='fa fa-plus'></i> {new_query} </label>
			</div>	
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$report</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$size}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'><center>RUN</center></th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'><center>CSV</center></th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'><center>$delete_text</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";	
	$TRCLASS=null;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);}
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=$ligne["zmd5"];
		$title=$tpl->javascript_parse_text($ligne["title"]);
		$color="black";
		$values_size=$ligne["values_size"];
		$csv=$tpl->icon_excel();
		$filedata="/home/squid/statistics-extractor/{$zmd5}.csv";
		$type=$ligne["report_type"];
		$addon=null;
		$download_uri="fw.proxy.statistics.extractor.php?download=$zmd5";
		if($type=="EXTRACT"){
			
			if(!is_file($filedata)){$values_size=0;} }
		
		$ahref=null;
		if($values_size>1024){
			$values_size=FormatBytes($values_size/1024);
		}else{
			if($values_size>0){$values_size="{$values_size} Bytes";}
		}
		if($values_size==0){
			
			$color="#8a8a8a";$csv=null;
			$addon="&nbsp;<strong>(".$tpl->_ENGINE_parse_body("{click_on_run_to_build_report}").")</strong>";
		
		}
		
	
		$icon_run=$tpl->icon_run("Loadjs('fw.proxy.statistics.extractor.php?run-js=$zmd5')");
		$delete=$tpl->icon_delete("Loadjs('fw.proxy.statistics.extractor.php?delete-js=$zmd5')");
		
		$ligne["title"]=$tpl->javascript_parse_text($ligne["title"]);
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td><span style='color:$color;font-weight:bold'><a href=\"javascript:blur();\"
		OnClick=\"Loadjs('fw.proxy.statistics.extractor.php?query-js=yes&zmd5={$zmd5}&type={$type}')\">{$ligne["title"]}</a></span>$addon</td>";
		$html[]="<td><span style='color:$color;'>{$ligne["zDate"]}</span></td>";
		$html[]="<td>$values_size</td>";
		$html[]="<td style='vertical-align:middle'><center>$icon_run</center></td>";
		$html[]="<td style='vertical-align:middle'><center><a href=\"fw.proxy.statistics.extractor.php?download=$zmd5\">$csv</a></center></td>";
		$html[]="<td style='vertical-align:middle'><center>$delete</center></td>";
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
	$html[]="<script>";
	$html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."";
	$html[]="$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
	$html[]="</script>";
	echo @implode("\n", $html);
}
	
	function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
	
