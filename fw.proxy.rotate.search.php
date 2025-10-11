<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["proxy-search-graph"])){graph1();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["page2"])){page2();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_main_save();exit;}
if(isset($_GET["launch"])){launch();exit;}
if(isset($_GET["proxies-list"])){proxies_list();exit;}
if(isset($_GET["fiche-proxy-js"])){proxy_fiche_js();exit;}
if(isset($_GET["fiche-proxy"])){proxy_fiche();exit;}
if(isset($_GET["delete-proxy-js"])){proxy_delete_js();exit;}
if(isset($_POST["proxy-aclid"])){proxy_fiche_save();exit;}
if(isset($_POST["delete_rule"])){rule_delete();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["run-js"])){run_js();exit;}
if(isset($_POST["run"])){run_launch();exit;}
if(isset($_GET["proxy-search-status"])){proxy_search_status();exit;}
if(isset($_GET["datefrom-js"])){date_from_js();exit;}
if(isset($_GET["dateto-js"])){date_to_start_js();exit;}
if(isset($_GET["dateto-popup"])){date_to_popup();exit;}
if(isset($_GET["dateto2-js"])){date_to_js();exit;}
if(isset($_GET["datefrom-popup"])){date_from_popup();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{legal_logs}",ico_search_in_file,"{legal_logs_explain}",
        "$page?page2=yes","proxy-legal-search","progress-logrotate-restart",false,"table-loader-proxy-search");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{legal_logs}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function date_from_js(){
    $id=$_GET["id"];
    $suffixform=$_GET["suffix-form"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog10("{date_from}","$page?datefrom-popup=yes&id=$id&suffix-form=$suffixform",500);
}
function date_to_start_js(){
    $id=$_GET["id"];
    $suffixform=$_GET["suffix-form"];
    $page=CurrentPageName();

    $dateFromID=md5("datefrom$suffixform");

    $js[]="datefrom=document.getElementById('$dateFromID').value;";
    $js[]="Loadjs('$page?dateto2-js=yes&id=$id&suffix-form=$suffixform&datefrom='+datefrom);";
    echo @implode("\n",$js);

}
function date_to_popup(){
    $tpl=new template_admin();
    $id=$_GET["id"];
    $from=$_GET["from"];

    $html[]="<table class='table table-striped'>";
    $q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $results=$q->QUERY_SQL("SELECT zDateTo FROM proxy_time WHERE ztype=0 AND zdate >=$from AND zDateTo>=$from ORDER BY zDateTo ASC");
    $js=array();



    foreach ($results as $ligne) {

        $fname=md5($ligne["zDateTo"].$id);
        $zdate=$ligne["zDateTo"];
        $zdateF=date("Y-m-d",$zdate);
        $timeD=date("H:i",$zdate);
        $js[]="function F$fname(){";
        $js[]="document.getElementById('$id').value='$zdateF $timeD';";
        $js[]="dialogInstance10.close();";
        $js[]="}";
        $choose="<button class='btn btn-primary btn-xs' type='button' OnClick=\"F$fname();\">{select}</button>";
        $html[]="<tr>";
        $html[]="<td>".$tpl->time_to_date($zdate,true)."</td>";
        $html[]="<td style='width:1%' nowrap>$choose</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="<script>";
    $html[]=@implode("\n",$js);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}
function date_to_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["id"];
    $suffixform=$_GET["suffix-form"];
    $datefrom=$_GET["datefrom"];
    $xtime=strtotime($datefrom);
    return $tpl->js_dialog10("{date_to}","$page?dateto-popup=yes&id=$id&suffix-form=$suffixform&from=$xtime",500);

}

function date_from_popup():bool{
    $tpl=new template_admin();

    $id=$_GET["id"];
    $html[]="<table class='table table-striped'>";
    $q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $results=$q->QUERY_SQL("SELECT zdate FROM proxy_time ORDER BY zdate DESC");
    $COMPRESSOR=array();
    foreach ($results as $ligne) {

        $fname = md5($ligne["zdate"] . $id);
        $zdate = $ligne["zdate"];
        $zdateF = date("Y-m-d", $zdate);
        $timeD = date("H", $zdate) . "00";
        $COMPRESSOR["$zdateF $timeD"]=true;
    }
    foreach ($COMPRESSOR as $ftime) {
        $js[]="function F$fname(){";
        $js[]="document.getElementById('$id').value='$ftime';";
        $js[]="dialogInstance10.close();";
        $js[]="}";
        $choose="<button class='btn btn-primary btn-xs' type='button' OnClick=\"F$fname();\">{select}</button>";
        $html[]="<tr>";
        $html[]="<td>".$tpl->time_to_date($zdate,true)."</td>";
        $html[]="<td style='width:1%' nowrap>$choose</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="<script>";
    $html[]=@implode("\n",$js);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function page2(){
    $tpl=new template_admin();
	$page=CurrentPageName();
    $html[]="<input type=hidden name=cleartimeout id='cleartimeout' value='0'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:250px;vertical-align:top'>";
    $html[]="<div id='proxy-search-graph' style='width:250px'></div>";
    $html[]="<div id='proxy-search-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top'>";
    $html[]=$tpl->search_block($page,"","","","&table=yes");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("proxy-search-status",$page,"proxy-search-status=yes");
    $html[]="Loadjs('$page?proxy-search-graph=yes')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function run_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["run-js"]);
    $function=$_GET["function"];
    $tpl->js_confirm_execute("{launch_the_search} $ID ?","run",$ID,"$function();");

}
function run_launch(){
    $ID=intval($_POST["run"]);
    $edit[]="`executed`='0'";
    $edit[]="`lines`='0'";
    $edit[]="`percentage`='0'";
    $q      = new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $sqledit="UPDATE proxy_search SET ".@implode(",",$edit)." WHERE ID='$ID'";
    $q->QUERY_SQL($sqledit);
    if(!$q->ok){echo $q->mysql_error;return false;}
    $sock=new sockets();
    $sock->REST_API("/legal/logs/search/$ID");
}


function launch():bool{
    $sock=new sockets();
    $function=$_GET["function"];
    $sock->REST_API("/legal/logs/search/all");
    echo "$function()";
    return true;
}

function enabled_js(){
	$aclid=$_GET["enabled-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_parents_acls WHERE aclid='$aclid'");
	$enabled=$ligne["enabled"];
	if($enabled==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE squid_parents_acls SET enabled=$enabled WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;}
}

function download(){
    $aclid=intval($_GET["download"]);
    $content_type="text/plain";
    $mainpath="/home/artica/squidsearchs/$aclid.log";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"search-$aclid.log\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize($mainpath);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($mainpath);

}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["delete-rule-js"]);
    $md=$_GET["md"];
	//header("content-type: application/x-javascript");
    $tpl->js_confirm_delete("{delete} {search} $aclid ?","delete_rule",$aclid,"$('#$md').remove()");
}
function proxy_search_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html="";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function graph1(){
    $BackupMaxDaysDirSize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDirSize");
    if(strpos($BackupMaxDaysDirSize,":")==0) {
        return false;
    }
    $tb=explode(":",$BackupMaxDaysDirSize);+
    $DirSize=$tb[0];
    $PartSize=$tb[1];

    $tpl=new templates();
    $Free=$PartSize-$DirSize;
    $PartitionText=FormatBytes($Free);

    $MAIN["Partition"]=$Free;
    $MAIN["Directory"]=$DirSize;

    $PieData=$MAIN;
    $highcharts=new Chartjs();
    $highcharts->container="proxy-search-graph";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->DataToSize=true;
    $highcharts->PiePlotTitle="{directory_size}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("{directory_size} ".FormatBytes($DirSize)."/$PartitionText");
    echo $highcharts->Doughnut2rows();
}

function rule_delete():bool{

	$aclid      = $_POST["delete_rule"];
    $mainpath="/home/artica/squidsearchs/$aclid.log";
    if(is_file($mainpath)) {
        $sock=new sockets();
        $sock->getFrameWork("squid.php?delete-searchs=$aclid");
        if (is_file($mainpath)) {
            echo "Cannot delete source file data (permission denied)";
            return false;
        }
    }
    $zmd5="";
    $q          = new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM proxy_search WHERE ID='$aclid'");
    if(isset($ligne["zmd5"])) {
        $zmd5 = $ligne["zmd5"];
    }
    $q->QUERY_SQL("DELETE FROM proxy_reports WHERE zmd5='$zmd5");
	$q->QUERY_SQL("DELETE FROM proxy_search WHERE ID=$aclid");
    if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("DELETE Search rule $aclid");
    return true;
}


function proxy_fiche_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$proxname=urlencode($_GET["proxname"]);
	$title=$proxname;
	$aclid=$_GET["aclid"];
	if($proxname==null){$title=$tpl->javascript_parse_text("{new_proxy}");}
	$tpl->js_dialog1($title,"$page?fiche-proxy=$proxname&aclid=$aclid");
}

function proxy_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$proxname=$_GET["delete-proxy-js"];
	$aclid=$_GET["aclid"];
    $md=$_GET["md"];
	header("content-type: application/x-javascript");

	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete} $proxname ?");
	$t=time();
	$html="
	
var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#$md').remove();
	if(document.getElementById('table-loader-proxy-parents') ){ 
	    LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes'); 
	}
}
	
function Action$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-proxy','$proxname');
	XHR.appendData('aclid','$aclid');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
	
Action$t();";
echo $html;
}

function rule_id_js():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $users      = new usersMenus();
    $function=$_GET["function"];
    if(!$users->AsSquidAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return false;
    }

    $ztype=intval($_GET["type"]);
	$id=$_GET["ruleid-js"];
	$title="{new_search}";

	if($id>0){
            $title="{search}: N.$id";
	}
	$title=$tpl->javascript_parse_text($title);
	$tpl->js_dialog($title,"$page?rule-popup=$id&function=$function&type=$ztype");
	return true;
}
function proxy_delete(){
	$aclid=$_POST["aclid"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT proxies FROM squid_parents_acls WHERE aclid='$aclid'");
	$MAIN=unserialize(base64_decode($ligne["proxies"]));
	unset($MAIN[$_POST["delete-proxy"]]);
	$MAIN_FINAL=base64_encode(serialize($MAIN));
	$sql="UPDATE squid_parents_acls SET proxies='$MAIN_FINAL' WHERE aclid='$aclid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}

}
function rule_settings():bool{
	$ID=intval($_GET["rule-settings"]);
	$page=CurrentPageName();
    $function=$_GET["function"];
	$tpl=new template_admin();
    $logfileD                   = new logfile_daemon();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
	$ligne["enabled"]=1;
	$btname="{add}";
	$title="";
    $ligne["maxlines"]=500;
    $ligne["squidcode"]=0;
    $ligne["timefrom"]="00:00";
    $ligne["timeto"]="23:59";
	$BootstrapDialog="BootstrapDialog1.close();";
    $uuid="";
    $datefrom="";
    $timefrom="";
    $jsafter="$function();";
    $dateto="";
    $timeto="";
    $username="";
    $ipsrc="";
    $ipdest="";
    $category="";
    $sitename="";
    $squidcode="";
    $uri="";
    $enabled=1;
    $ztype=1;
    if(isset($_GET["type"])){
        $ztype=intval($_GET["type"]);
    }

    $maxlines=$ligne["maxlines"];
	if($ID>0){
		$btname="{apply}";
		$ligne=$q->mysqli_fetch_array("SELECT * FROM proxy_search WHERE ID='$ID'");
		$title=explainThis($ligne);
        $uuid=$ligne["uuid"];
        $datefrom=$ligne["datefrom"];
        $timefrom=$ligne["timefrom"];
        $jsafter="$function();";
        $dateto=$ligne["dateto"];
        $timeto=$ligne["timeto"];
        $username=$ligne["username"];
        $ipsrc=$ligne["ipsrc"];
        $ipdest=$ligne["ipdest"];
        $category=$ligne["category"];
        $sitename=$ligne["sitename"];
        $squidcode=$ligne["squidcode"];
        $enabled=$ligne["enabled"];
        $maxlines=$ligne["maxlines"];
        $uri=$ligne["uri"];
        $ztype=intval($ligne["ztype"]);
	}
    $ligneF=$q->mysqli_fetch_array("SELECT zdate FROM proxy_time WHERE ztype=$ztype ORDER BY zdate ASC LIMIT 1");
    $dateDefaultFrom=date("Y-m-d H:i:00",$ligneF["zdate"]);
    $ligneE=$q->mysqli_fetch_array("SELECT zdate FROM proxy_time WHERE ztype=$ztype ORDER BY zdate DESC LIMIT 1");
    $dateDefaultTo=date("Y-m-d H:i:00",$ligneE["zdate"]);



    $squid_codes=$logfileD->codeToString(0,true);

    if(strlen($timeto)<3){
        $timeto="00:00:00";
    }
    if(strlen($timefrom)<3){
        $timefrom="00:00:00";
    }

	$tpl->field_hidden("rule-save", $ID);
    $form[]=$tpl->field_hidden("uuid",$uuid);
    $form[]=$tpl->field_hidden("ztype",$ztype);
    $Defaultvalue="$datefrom $timefrom - $dateto $timeto";
    $form[]=$tpl->field_daterange("datefromto","{period}",$Defaultvalue,$dateDefaultFrom,$dateDefaultTo);
   // $form[]=$tpl->field_text_button("datefrom","{from_date}",$datefrom." ".$timefrom);
   // $form[]=$tpl->field_text_button("dateto","{to_date}",$dateto." ".$timeto);


    $form[]=$tpl->field_section("{source}");
    if ($ztype==1) {
        $form[] = $tpl->field_text("username", "{username}", $username);
    }
    $form[]=$tpl->field_text("ipsrc","{ipsrc}",$ipsrc);

    if ($ztype==2) {
        $form[] = $tpl->field_text("username","User-Agent",$username);
        $form[] = $tpl->field_text("uri","{url}",$uri);
    }

    $form[]=$tpl->field_section("{destination}");

    if ($ztype==1) {
        $form[] = $tpl->field_text("ipdest", "{dst}", $ipdest);
        $form[]=$tpl->field_categories_list("category","{category}",$category);
    }
    $form[]=$tpl->field_text("sitename","{sitename}",$sitename);

    $form[]=$tpl->field_section("{options}");
    $form[]=$tpl->field_array_hash($squid_codes,"squidcode","{http_status_code}",$squidcode);
    $form[]=$tpl->field_numeric("maxlines","{max_lines}",$maxlines);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled);
	echo $tpl->form_outside($title,@implode("\n", $form),null,$btname,"$jsafter$BootstrapDialog");
    return true;
}
function rule_main_save():bool{
	$tpl    = new template_admin();
    $tpl->CLEAN_POST();
    $q      = new lib_sqlite("/home/artica/SQLITE/proxy_search.db");

    $ID=intval($_POST["rule-save"]);
    $uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");

    $dateto=$_POST["dateto"];
    $username=$_POST["username"];
    $ipsrc=$_POST["ipsrc"];
    $ipdest=$_POST["ipdest"];
    $category=$_POST["category"];
    $sitename=$_POST["sitename"];
    $squidcode=$_POST["squidcode"];
    $maxlines=intval($_POST["maxlines"]);
    $enabled=$_POST["enabled"];

    $datefromto=$_POST["datefromto"];
    if(preg_match("#^(.+?)\s+([0-9:]+)\s+-\s+(.+?)\s+([0-9:]+)#",$datefromto,$re)){
        $datefrom="$re[1] $re[2]:00";
        $dateto="$re[3] $re[4]:00";
    }
    $datefromInt=strtotime($datefrom);
    $datetoInt=strtotime($dateto);
    $datefrom=date("Y-m-d",$datefromInt);
    $timefrom=date("H:i",$datefromInt);
    $timeto=date("H:i",$datetoInt);
    $dateto=date("Y-m-d",$datetoInt);

    $fields[]="uuid";
    $values[]=$uuid;
    $edit[]="`uuid`='$uuid'";

    $fields[]="maxlines";
    $values[]=$maxlines;
    $edit[]="`maxlines`='$maxlines'";

    $fields[]="datefrom";
    $values[]=$datefrom;
    $edit[]="`datefrom`='$datefrom'";

    $fields[]="timefrom";
    $values[]=$timefrom;
    $edit[]="`timefrom`='$timefrom'";

    $fields[]="timeto";
    $values[]=$timeto;
    $edit[]="`timeto`='$timeto'";

    $fields[]="ztype";
    $values[]=intval($_POST["ztype"]);
    $edit[]="`ztype`=".intval($_POST["ztype"]);


    $fields[]="dateto";
    $values[]=$dateto;
    $edit[]="`dateto`='$dateto'";

    $fields[]="username";
    $values[]=$username;
    $edit[]="`username`='$username'";
    $fields[]="ipsrc";
    $values[]=$ipsrc;
    $edit[]="`ipsrc`='$ipsrc'";
    $fields[]="ipdest";
    $values[]=$ipdest;
    $edit[]="`ipdest`='$ipdest'";
    $fields[]="category";
    $values[]=$category;
    $edit[]="`category`='$category'";
    $fields[]="sitename";
    $values[]=$sitename;
    $edit[]="`sitename`='$sitename'";
    $fields[]="squidcode";
    $values[]=$squidcode;
    $edit[]="`squidcode`='$squidcode'";
    $fields[]="logspath";
    $values[]='';
    $fields[]="lines";
    $values[]=0;
    $fields[]="size";
    $values[]=0;
    $fields[]="executed";
    $values[]=0;
    $fields[]="percentage";
    $values[]=0;
    $fields[]="enabled";
    $values[]=$enabled;
    $edit[]="`enabled`='$enabled'";


    $edit[]="`executed`='0'";
    $edit[]="`lines`='0'";
    $edit[]="`percentage`='0'";

    foreach ($values as $index=>$val){
        $values[$index]="'$val'";
    }

    $sqladd="INSERT INTO proxy_search (".@implode(",",$fields).") VALUES (".@implode(",",$values).")";
    $sqledit="UPDATE proxy_search SET ".@implode(",",$edit)." WHERE ID='$ID'";

	if($ID==0){
        $q->QUERY_SQL($sqladd);
        if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error)."<br>".$sqladd;return false;}
        admin_tracks("Successfully add a search query ". @implode(", ",$edit));
        return true;
	}


    $q->QUERY_SQL($sqledit);
	if(!$q->ok){echo "javascript:".$tpl->javascript_parse_text($q->mysql_error);return false;}
	admin_tracks("UPDATE search rule $ID ". @implode(", ",$edit));
	return true;
}
function rule_tab(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["rule-popup"]);
    $function=$_GET["function"];
    $ztype=1;
    if(isset($_GET["type"])){
        $ztype=intval($_GET["type"]);
    }
    $q      = new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM proxy_time WHERE ztype=$ztype");
    $tcount=intval($ligne["tcount"]);
    $array["{search} ($tcount {files})"]      = "$page?rule-settings=$aclid&function=$function&type=$ztype";
	echo $tpl->tabs_default($array);


}
function explainThis($ligne){
    $tpl=new template_admin();
    $ID=$ligne["ID"];
    $username=$ligne["username"];
    $ipsrc=$ligne["ipsrc"];
    $ipdest=$ligne["ipdest"];
    $category=$ligne["category"];
    $sitename=$ligne["sitename"];
    $squidcode=$ligne["squidcode"];

    $datefrom=$ligne["datefrom"];
    $timefrom=$ligne["timefrom"];


    $dateto=$ligne["dateto"];
    $timeto=$ligne["timeto"];
    $zType=intval($ligne["ztype"]);

    if($zType==0){
        $ex[]="<i class='".ico_servcloud."'></i>&nbsp;Proxy&nbsp;&raquo;";
    }
    if($zType==1){
        $ex[]="<i class='".ico_firewall."'></i>&nbsp;FortiGate&nbsp;&raquo;";
    }
    if($zType==2){
        $ex[]="<i class='".ico_earth."'></i>&nbsp;Reverse-Proxy&nbsp;&raquo;";
    }


    $strto1=strtotime($datefrom." ".$timefrom);
    $strto2=strtotime($dateto." ".$timeto);
    $ff=array();
    $ffa=array();



    if($username<>null){
        $ff[]="{username} {like} <strong>$username</strong>";
    }
    if($ipsrc<>null){
        $ff[]="{ipsrc} {like} <strong>$ipsrc</strong>";
    }

    if($category>0){
        $catz=new mysql_catz();
        $categoryname=$catz->CategoryIntToStr($category);
        $ffa[]="{category} <strong>$categoryname</strong>";
    }
    if($sitename<>null){
        $ffa[]="{sitename} {like} <strong>$sitename</strong>";
    }
    if($ipdest<>null){
        $ffa[]="{dst} {like} <strong>$ipdest</strong>";
    }
    if($squidcode >0){
        $ffa[]="{with} {http_status_code} <strong>$squidcode</strong>";
    }

    if(count($ff)>0){
        $ex[]="{search} {source} ".@implode("{and}",$ff);
    }


    if(count($ffa)>0 && count($ff)>0){
        $ex[]="{and} {search} {destination} ".@implode(" {and} ",$ffa);
    }
    if(count($ffa)>0 && count($ff)==0){
        $ex[]="{search} {destination} ".@implode(" {and} ",$ffa);
    }

    $ex[]="{between} $datefrom $timefrom {and} $dateto $timeto";
    $sql="SELECT COUNT(*) as tcount FROM proxy_time WHERE ztype=$zType AND zdate >= $strto1 AND zDateTo <= $strto2";

    $q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){
        $ex[]="<strong class='text-danger'>$q->mysql_error</strong>";
    }
    $tcount=$ligne["tcount"];

    if($tcount==0){
        $ex[]="<br><span class='text-danger'>{logsearch_err_date}</span>";
    }else{
        $ff=$tpl->_ENGINE_parse_body("{logsearch_count}");
        $ff=str_replace("%s","<strong>$tcount</strong>",$ff);
        $ex[]="<br>$ff";
    }


    $mainpath="/home/artica/squidsearchs/$ID.log";
    if(is_file($mainpath)){
        $tpl=new template_admin();
        $size=@filesize($mainpath);
        $ex[]="[".$tpl->td_href("search-$ID.log (".FormatBytes($size/1024).")","{download}","document.location.href='fw.proxy.rotate.search.php?download=$ID'")."]";
    }

    return @implode(" ",$ex);

}
function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $function=$_GET["function"];
    $SquidRotateEnableCrypt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateEnableCrypt"));

    if($SquidRotateEnableCrypt==1){
        echo $tpl->div_error("{decrypt_backups}||{decrypt_backups_no_feature}");
        return false;
    }
	$q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $t=time();
    $launch="Loadjs('$page?launch=yes&function=$function')";
    $refresh="$function();";

    $ztypes=array();
    $ztypes[0]=0;
    $ztypes[1]=0;
    $ztypes[2]=0;
    $results=$q->QUERY_SQL("SELECT ztype,count(*) as tcount FROM proxy_time GROUP BY ztype");
    if($q->ok) {
        foreach ($results as $ligne) {
            $ztypes[$ligne["ztype"]] = $ligne["tcount"];
        }
    }

    if( $ztypes[0]>0) {
        $add="Loadjs('$page?ruleid-js=0&type=0&function=$function',true);";
        $topbuttons[] = array($add, ico_plus, "{new_search} {APP_SQUID}");
    }
    if( $ztypes[2]>0) {
        $add="Loadjs('$page?ruleid-js=0&type=2&function=$function',true);";
        $topbuttons[] = array($add, ico_plus, "{new_search} {reverse_proxy}");
    }
    $topbuttons[] = array($launch,ico_rocket,"{launch_searchs}");
    $topbuttons[] = array($refresh,ico_refresh,"{refresh}");

    $TINY_ARRAY["TITLE"]="{APP_SQUID} &raquo; {legal_logs}: {history_search}";
    $TINY_ARRAY["ICO"]="fas fa-file-search";
    $TINY_ARRAY["EXPL"]="{legal_logs_explain}";
    $TINY_ARRAY["URL"]="logs-rotate";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true style='width:99%'>{search}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{run}</th>";
    $html[]="<th data-sortable=false style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=false style='width:1%'>{view2}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{download}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{rows}</th>";
    $html[]="<th data-sortable=false>DEL.</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$results=$q->QUERY_SQL("SELECT * FROM proxy_search ORDER BY ID DESC");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
	$TRCLASS=null;
	foreach($results as $index=>$ligne) {
            $md=md5(serialize($ligne));
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $explain=explainThis($ligne);
            $ID=$ligne["ID"];
            $zmd5=$ligne["zmd5"];
            $ligne2=$q->mysqli_fetch_array("SELECT zmd5 FROM proxy_reports WHERE zmd5='$zmd5'");
            $zmd5_report=$ligne2["zmd5"];
            $lines=$ligne["lines"];
            $executed=$ligne["executed"];
            $percentage=$ligne["percentage"];
            $Type=$ligne["ztype"];
            $js="Loadjs('$page?ruleid-js=$ID&function=$function&type=$Type',true);";
			$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js=$ID&md=$md&function=$function&type=$Type')");
            $icon_graph="";
            if(strlen($zmd5)>5){
               $jsPop="s_PopUp('fw.proxy.rotate.list.php?report=$zmd5_report&type=$Type','1024','900');";
                $icon_graph=$tpl->icon_pie($jsPop);
                if($Type==2){
                    $icon_graph="&nbsp;";
                }
            }

            if($executed==0){
                $read=$tpl->icon_loupe();
                $lines="$percentage%";
                $run=$tpl->icon_nothing();
                $download="<i class='fas fa-sync-alt'></i>";
                if($percentage < 5 ){$download=$tpl->icon_nothing();}
            }else{
                $read=$tpl->icon_loupe(true,"Loadjs('fw.proxy.relatime.php?search-file-js=$ID&function=$function')");

                if($Type==2){
                    $read=$tpl->icon_loupe(true,"Loadjs('fw.nginx.requests.php?search-file-js=$ID&function=$function')");
                }


                $download=$tpl->icon_download("document.location.href='$page?download=$ID'","AsProxyMonitor");
                $run=$tpl->icon_run("Loadjs('$page?run-js=$ID&function=$function&type=$Type')","AsProxyMonitor");
                $lines=$tpl->FormatNumber($lines);
            }




			$html[]="<tr class='$TRCLASS' id='$md'>";

			$html[]="<td style='vertical-align:middle'>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>{report}: $ID</a>&nbsp;&raquo;&nbsp;$explain</span></td>";
            $html[]="<td class=\"center\"  style='width:1%'>$run</td>";
            $html[]="<td class=\"center\"  style='width:1%'>$icon_graph</td>";
            $html[]="<td class=\"center\"  style='width:1%'>$read</td>";
            $html[]="<td class=\"center\"  style='width:1%'>$download</td>";
            $html[]="<td class=\"center\"  style='width:1%'>$lines</td>";
			$html[]="<td class=center style='width:1%' nowrap>$delete</td>";
			$html[]="</tr>";

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
    </script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function isRights():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

function proxy_objects($aclid,$tablelink="parents_sqacllinks",$returndef=true):string{

    $tt         = array();
	$q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
	$qProxy     = new mysql_squid_builder(true);

	$sql="SELECT
	$tablelink.gpid,
	$tablelink.zmd5,
	$tablelink.negation,
	$tablelink.zOrder,
	webfilters_sqgroups.GroupType,
	webfilters_sqgroups.GroupName,
	webfilters_sqgroups.ID
	FROM $tablelink,webfilters_sqgroups
	WHERE $tablelink.gpid=webfilters_sqgroups.ID
	AND $tablelink.aclid=$aclid
	ORDER BY $tablelink.zorder";

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return "";}

	foreach($results as $index=>$ligne) {
		$gpid=$ligne["gpid"];
		$js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
		$neg_text="{is}";
		if($ligne["negation"]==1){$neg_text="{is_not}";}
		$GroupName=$ligne["GroupName"];
		$tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-weight:bold'>$GroupName</a> <small>(".$qProxy->acl_GroupType[$ligne["GroupType"]].")</small>";
	}

	if(count($tt)>0){
		return @implode("<br>{and} ", $tt);

	}

    if(!$returndef){return "";}
	return "{all}";



}