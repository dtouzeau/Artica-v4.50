<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.maillogs.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsPostfixAdministrator){exit();}
if(isset($_GET["csv"])){download_csv();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["RTMMail-search"])){RTMMail_search();exit;}
if(isset($_GET["RTMMail"])){RTMMail();exit;}
if(isset($_GET["search"])){RunInvestigate();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["options"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_POST["MAX_LINES"])){options_save();exit;}
if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["investigate"])){investigate();exit;}
page();

function options_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$tpl->js_dialog1("{options}", "$page?options-popup=yes");
	
}
function report_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{report}", "$page?report-popup={$_GET["report"]}");
}
function report_popup(){
    echo base64_decode($_SESSION["score_tootip"][$_GET["report-popup"]]);
}
function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["delete-js"];
	$tpl->js_confirm_delete($id, "delete", $id,"$('#{$_GET["md"]}').remove()");
}
function delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("postfix2.php?history-delete={$_POST["delete"]}");
	$q=new lib_sqlite("/home/artica/SQLITE/postfix_events.db");
	$q->QUERY_SQL("DELETE FROM postfix_search WHERE ID='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function download(){

    $destfile=PROGRESS_DIR."/artica-milter.gz";
    $psize=filesize($destfile);
    header('Content-type: application/gz');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"artica-milter.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$psize);
    ob_clean();
    flush();
    readfile($destfile);
}
function download_csv(){
    $destfile=PROGRESS_DIR."/artica-milter.csv";
    $psize=filesize($destfile);
    header('Content-type: text/csv');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"artica-milter.csv\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$psize);
    ob_clean();
    flush();
    readfile($destfile);
}


function options_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();	

	if(!isset($_SESSION["MAX_LINES"])){$_SESSION["MAX_LINES"]=200;}
    if(!isset($_SESSION["DAYS_LEFT"])){$_SESSION["DAYS_LEFT"]=0;}
	$form[]=$tpl->field_numeric("DAYS_LEFT","{last_days}",$_SESSION["DAYS_LEFT"]);
	$form[]=$tpl->field_numeric("MAX_LINES", "{max_lines}", $_SESSION["MAX_LINES"]);
	echo $tpl->form_outside("{options}", $form,null,"{apply}","dialogInstance1.close();Loadjs('$page?RunInvestigate=yes')","AsPostfixAdministrator");

}

function options_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["MAX_LINES"]=$_POST["MAX_LINES"];
    $_SESSION["DAYS_LEFT"]=$_POST["DAYS_LEFT"];
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableArticaMilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMilter"));

    if($EnableArticaMilter==1){
        $array["{RTMMail}"]="$page?RTMMail=yes";
    }
    $array["{investigate}"]="$page?investigate=yes";
    echo $tpl->tabs_default($array);
}
function investigate(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'></div>";
    echo $tpl->search_block($page);

    $TINY_ARRAY["TITLE"]="{messaging}:{investigate} &laquo;*&raquo";
    $TINY_ARRAY["ICO"]="fas fa-eye";
    $TINY_ARRAY["EXPL"]="{mail_investigate_explain}";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "\n<script>$jstiny</script>";
}

function RTMMail(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&RTMMail-search=yes");

    $jsApply=$tpl->framework_buildjs("articamilter.php?export=yes",
        "articamilter.progress",
        "articamilter.log","progress-postfix-maillog",
        "document.location.href='/$page?download=yes'");

    $btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"$jsApply\"><i class='fas fa-file-export'></i> {download} </label>
			</div>"
    );


    $TINY_ARRAY["BUTTONS"]=$btns;
    $TINY_ARRAY["TITLE"]="{messaging}: {RTMMail}";
    $TINY_ARRAY["ICO"]="fas fa-eye";
    $TINY_ARRAY["EXPL"]="{mail_investigate_explain}";

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";
}



function RTMMail_search(){
    $search=null;
    $page=CurrentPageName();
    clean_xss_deep();
    if(isset($_GET["search"])) {
        $search = $_GET["search"];
    }
    $t=time();
    $tpl=new template_admin();
    $sock=new sockets();
    $sock->getFrameWork("articamilter.php?syslog=".urlencode($search));
    $file_result=PROGRESS_DIR."/articamilter.syslog";
    $lines=explode("\n",@file_get_contents($file_result));
    krsort($lines);

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{from}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{to}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{subject}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    foreach ($lines as $line){
        $line=trim($line);
        $class=null;
        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\]:(.+)#",$line,$re)){continue;}

        $month=$re[1];$day=$re[2];$time=$re[3];$line=$re[4];
        $sdate=strtotime(date('Y')."-$month-$day $time");
        $MAIN=array();
        if(!preg_match_all("#(.+?)=\"(.+?)\";#",$line,$re)){continue;}
        foreach ($re[1] as $index=>$line){
                $key=trim($line);
                $value=trim($re[2][$index]);
                $MAIN[$key]=$value;
        }
        $subject=clean_header($MAIN["subject"]);
        $recipients=parse_recipitents(clean_header($MAIN["to"]),true);



        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $stime=$tpl->time_to_date($sdate,true);
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td width=1% nowrap>$stime</td>";
        $html[]="<td style='width:1%;' nowrap class='$class'>{$MAIN["ipaddr"]}</td>";
        $html[]="<td style='width:1%;' nowrap class='$class'>{$MAIN["from"]}</td>";
        $html[]="<td width=1% class='$class'>$recipients</td>";
        $html[]="<td width=99% class='$class'>$subject</td>";
        $html[]="</tr>";
        


    }
    $jsApply=$tpl->framework_buildjs("articamilter.php?export=yes",
        "articamilter.progress",
        "articamilter.log","progress-postfix-maillog",
        "document.location.href='/$page?download=yes'");

    $jsApply_csv=$tpl->framework_buildjs("articamilter.php?csv=yes",
        "articamilter.progress",
        "articamilter.log","progress-postfix-maillog",
        "document.location.href='/$page?csv=yes'");


    $btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"$jsApply\"><i class='fas fa-file-export'></i> {download} </label>
			<label class=\"btn btn btn-blue\" OnClick=\"$jsApply_csv\"><i class='fas fa-file-csv'></i> {export} </label>
			</div>"
			
    );


    $TINY_ARRAY["BUTTONS"]=$btns;
    $TINY_ARRAY["TITLE"]="{RTMMail}: &laquo;$search&raquo;";
    $TINY_ARRAY["ICO"]="fas fa-eye";
    $TINY_ARRAY["EXPL"]="{mail_investigate_explain}";

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
}



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html[]= $tpl->page_header("{messaging}:{investigate}","fas fa-eye","{mail_investigate_explain}",
        "$page?tabs=yes","mail-source-logs","progress-postfix-maillog",false,"table-postfix-maillog");

	//{investigate}
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,@implode("\n", $html));
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

function RunInvestigate(){
    $tpl=new template_admin();
    clean_xss_deep();
    if(!isset($_SESSION["MAX_LINES"])){$_SESSION["MAX_LINES"]=200;}
    if(!isset($_SESSION["DAYS_LEFT"])){$_SESSION["DAYS_LEFT"]=0;}

    $array["DAYS_LEFT"]=$_SESSION["DAYS_LEFT"];
    $array["MAX_LINES"]=$_SESSION["MAX_LINES"];
    $array["search"]=$_GET["search"];
    $array["TIME"]=date("YmdHi");
    $pattern=serialize($array);
    $md5=md5($pattern);
    $patternenc=urlencode(base64_encode($pattern));


	$page=CurrentPageName();
    $jsApply=$tpl->framework_buildjs("postfix2.php?history-search=$patternenc&md5=$md5",
        "postfix.events.$md5.progress","postfix.events.$md5.progress.txt",
        "progress-postfix-maillog",
        "LoadAjax('{$_GET["div-id"]}','$page?table=$md5');");



    $TINY_ARRAY["TITLE"]="{messaging}:{investigate} &laquo;{$_GET["search"]}&raquo;";
    $TINY_ARRAY["ICO"]="fas fa-eye";
    $TINY_ARRAY["EXPL"]="{mail_investigate_explain}";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	echo "<script>
            $jstiny
            $jsApply
        </script>";
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $md5=$_GET["table"];
    unset( $_SESSION["score_tootip"]);
    if(!isset($_SESSION["MAX_LINES"])){$_SESSION["MAX_LINES"]=200;}
    if(!isset($_SESSION["DAYS_LEFT"])){$_SESSION["DAYS_LEFT"]=0;}

    $array["DAYS_LEFT"]=$_SESSION["DAYS_LEFT"];
    $array["MAX_LINES"]=$_SESSION["MAX_LINES"];

    $finalfile="/usr/share/artica-postfix/ressources/logs/postfix.events.$md5.results";

    $finalLogs=PROGRESS_DIR."/postfix.events.$md5.progress.txt";
	
	$html[]="<table id='table-my-postfix-search' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{service}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{pid}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{last_days} {$array["DAYS_LEFT"]} {$_SESSION["MAX_LINES"]} {max_lines}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";	
	
VERBOSE("$finalfile = ".filesize($finalfile));
    $results=array();
    $file = fopen($finalfile, "r");
    if($file){
        while(!feof($file)) {
            $line=fgets($file);
            $line=str_replace("\r\n","",$line);
            $line=str_replace("\n","",$line);
            $results[]=$line;
        }
        fclose($file);
    }


	
    krsort($results);
	$TRCLASS=null;
	foreach ($results as $ligne){
        if($GLOBALS["VERBOSE"]){echo "<hr>ANALYZE ($ligne)";}
        if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#",$ligne,$re)){
            if($GLOBALS["VERBOSE"]){echo "<strong>REFUSED ($ligne)<br></strong>";}
            continue;
        }


        $class=null;
        $sdate=$re[1]." ".$re[2]." ".$re[3];
        $service=$re[5];
        $pid=$re[6];
        $text=trim($re[7]);
        $mdgid_parsed=false;
        $status=null;
        $stime=strtotime($sdate);
        $date=$tpl->time_to_date($stime,true);
        $text=str_replace("<","&laquo;",$text);
        $text=str_replace(">","&raquo;",$text);

        if(preg_match("#MIMEDEFANG=&laquo;(.+?)&raquo;#",$text,$re)){
            $status="<span class='label'>ANTISPAM</span>";
            $service="Policy server";
            $text=scanispam($text);
            $html[]="<tr class='$TRCLASS'>";
            $html[]="<td width=1% nowrap>{$status}</td>";
            $html[]="<td style='width:1%;' nowrap class='$class'>$date</td>";
            $html[]="<td style='width:1%;' nowrap class='$class'>$service</td>";
            $html[]="<td style='width:1%;' nowrap class='$class'>$pid</td>";
            $html[]="<td width=99% class='$class'>$text</td>";
            $html[]="</tr>";
            continue;
        }


        $text=str_replace("; from=",";<br>from=",$text);

        if(preg_match("#from=&laquo;(.+?)&raquo;#i",$text,$re)){
            $dd=explode("@",$re[1]);
            $domain=urlencode($dd[1]);
            $encode=urlencode($re[1]);
            $text = str_replace("{$re[1]}", $tpl->td_href("{$re[1]}", null,
                "Loadjs('fw.postfix.transactions.php?zoom-from-js=$encode&domain=$domain')"), $text);
        }
        if(preg_match("#to=&laquo;(.+?)&raquo;#",$text,$re)){
            $text = str_replace("{$re[1]}", "<strong>{$re[1]}</strong>",$text);
        }

        if(preg_match("#from=(.+?)\s+action=#",$text,$re)){
            $dd=explode("@",$re[1]);
            $domain=urlencode($dd[1]);
            $encode=urlencode($re[1]);
            $text = str_replace("{$re[1]}", $tpl->td_href("{$re[1]}", null,
                "Loadjs('fw.postfix.transactions.php?zoom-from-js=$encode&domain=$domain')"), $text);
        }


        $status="<span class='label label-primary'>INFO</span>";
        if($service=="mimedefang.pl"){
            $status="<span class='label'>ANTISPAM</span>";
            $service="Policy server";
        }
        if($service=="mimedefang-multiplexor"){
            $status="<span class='label'>ANTISPAM</span>";
            $service="Policy server";
        }

        $service=str_replace("postfix/","",$service);


        if(preg_match("#client_address=([0-9\.]+)#",$text,$re)){
            $text=str_replace("{$re[1]}",$tpl->td_href("{$re[1]}",null,
                "Loadjs('fw.postfix.transactions.php?zoom-ip-js={$re[1]}&hostname=unknown&maillog=yes')"),$text);
        }

        if(preg_match("#\[([0-9\.]+)\]#",$text,$re)){
            $text=str_replace("{$re[1]}",$tpl->td_href("{$re[1]}",null,
                "Loadjs('fw.postfix.transactions.php?zoom-ip-js={$re[1]}&hostname=unknown&maillog=yes')"),$text);
        }

        if(preg_match("#to address\s+([0-9\.]+)#",$text,$re)) {
            $text = str_replace("{$re[1]}", $tpl->td_href("{$re[1]}", null,
                "Loadjs('fw.postfix.transactions.php?zoom-ip-js={$re[1]}&hostname=unknown&maillog=yes')"), $text);
        }

        if(preg_match("#size=([0-9]+)#",$text,$re)){
            $size=$re[1];
            $k=$size/1024;
            $text=str_replace($size,"<strong>".FormatBytes($k)."</strong>",$text);
        }

        if(!$mdgid_parsed) {
            if (preg_match("#^([A-Z0-9]+):\s+#", $text, $re)) {
                $msgid = $re[1];
                $js_msgid = "Loadjs('fw.postfix.transactions.php?second-query=" . base64_encode("WHERE msgid='$msgid'") . "&title=" . urlencode("{see_messages_with}:$msgid") . "')";
                $msgid = $tpl->td_href($msgid, "{see_messages_with}:$msgid", $js_msgid);
                $text = str_replace($re[1], $msgid, $text);
            }
        }
        $text = str_ireplace("cannot find your hostname","<strong class='text-danger'>&laquo;&nbsp;Cannot find your hostname&nbsp;&raquo;</strong>", $text);
        $text = str_ireplace("Relay access denied","<strong class='text-danger'>Relay access denied</strong>", $text);
        $text = str_ireplace("Name or service not known","<strong class='text-danger'>Name or service not known</strong>", $text);
        $text = str_ireplace("reject: RCPT","<strong class='text-danger'>reject: RCPT</strong>", $text);
        $text = str_ireplace("Greylisting in action, please come back","<strong class='text-danger'>Greylisting in action, please come back</strong>", $text);




        $text = str_ireplace("Service unavailable","<strong>Service unavailable</strong>", $text);

        if(preg_match("#Message score ([0-9\.]+)#",$ligne,$re)){
            $text = str_replace($re[1], "<strong class='text-danger'>{$re[1]}</strong>", $text);
        }


        if(preg_match("#statistics:\s+#",$text)){
            $class="text-success";
            $status="<span class='label label-success'>STATS</span>";
        }


        if(preg_match("#reject:#",$text)){
            $status="<span class='label label-danger'>REJECT</span>";
        }




        if(preg_match("#redirect: (header|body)#",$text)){
            $status="<span class='label label-warning'>REDIRECT</span>";
        }
        if(preg_match("#warning:#",$text)){
            $status="<span class='label label-warning'>WARN</span>";
        }

        if(preg_match("#lost connection after#",$text)){
            $class="text-warning";
            $status="<span class='label label-warning'>CONNECT</span>";
        }


        if(preg_match("#Connection timed out#",$text)){
            $status="<span class='label label-danger'>TIMEOUT</span>";
        }

        if(preg_match("#too many errors#",$text)){
            $status="<span class='label label-danger'>ERRORS</span>";
        }


        if(preg_match("#,\s+status=sent#",$text)){
            $class="text-success";
            $status="<span class='label label-success'>SENT</span>";
        }

        if(preg_match("#: removed$#",$text)){
            $class="text-success";
            $status="<span class='label label-success'>SENT</span>";
        }

        if(preg_match("#Worker [0-9]+ stderr:#",$text)){
            $status="<span class='label label-warning'>ANTISPAM ERROR</span>";
        }

        if(preg_match("#Service unavailable; Client host.*?blocked using#",$text)){
            $status="<span class='label label-danger'>RBL</span>";
        }
        if(preg_match("#Connection refused#",$text)){
            $status="<span class='label label-danger'>REFUSED</span>";
        }

        $text=str_replace("Client host rejected","<strong class=text-danger>Client host rejected</strong>",$text);

        if(preg_match("#blocked using (.+?);#",$text,$re)){
            $text=str_replace("blocked using {$re[1]}",
                "<strong class=text-danger>blocked using {$re[1]}</strong>",$text);
        }
        if(preg_match("#status=(deferred|bounced)#i",$text,$re)){
            $status="<span class='label label-warning'>".strtoupper($re[1])."</span>";
            $text=str_replace($re[1],
                "<strong class=text-danger>{$re[1]}</strong>",$text);
        }
        if(preg_match("#ARTICA-ACTION: (.+?)\s+#",$text,$re)){
            $status="<span class='label label-danger'>".strtoupper($re[1])."</span>";
        }
        if(preg_match("#Greylisting in action#",$text)){
            $status="<span class='label label-warning'>GREYLIST</span>";
        }

        //Loadjs('fw.postfix.transactions.php?second-query=V0hFUkUgbXNnaWQ9J0Q3NEZGMjgwMEQxJw==&title=%7Bsee_messages_with%7D%3AD74FF2800D1')
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td width=1% nowrap>{$status}</td>";
		$html[]="<td style='width:1%;' nowrap class='$class'>$date</td>";
		$html[]="<td style='width:1%;' nowrap class='$class'>$service</td>";
        $html[]="<td style='width:1%;' nowrap class='$class'>$pid</td>";
		$html[]="<td width=99% class='$class'>$text</td>";
		$html[]="</tr>";
		
	}

	$restuls=explode("\n",@file_get_contents("$finalLogs"));
    foreach ($restuls as $ligne){
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td width=1% nowrap-</td>";
        $html[]="<td style='width:1%;' nowrap class='$class'>-</td>";
        $html[]="<td style='width:1%;' nowrap class='$class'>-</td>";
        $html[]="<td style='width:1%;' nowrap class='$class'>-</td>";
        $html[]="<td width=99% class='$class'>$ligne</td>";
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
	$html[]="</table><div><i></i></div>";
	$html[]="
	<script>
	document.getElementById('progress-postfix-maillog').innerHTML='';
	NoSpinner();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function scanispam($content){
$tpl=new template_admin();
$page=CurrentPageName();
    $TT=explode("|||",$content);
    foreach ($TT as $index=>$content){
        $content=str_replace("'","",$content);
        $TT[$index]=$content;
    }
    $FROM=$TT[4];
    $domainFROM=$TT[5];
    $IP=$TT[2];
    $hostname=$TT[3];
    $msgid=$TT[1];
    $recipient=$TT[6];
    $encode=urlencode($FROM);
    $subject=htmlentities($TT[8]);
    $size=FormatBytes($TT[9]/1024);
    $score=floatval($TT[10]);
    $content_filter=$TT[11];
    $score_tootip=null;
    $content_filter=str_replace("#012","<br>",$content_filter);
    $content_filter=str_replace("\n","<br>",$content_filter);
    if(intval($score)>0) {
        $_SESSION["score_tootip"][$msgid]=base64_encode($content_filter);
        $score_tootip = "<span class='text-danger'><strong>".$tpl->td_href($score,"{report}","Loadjs('$page?report-js=yes&report=$msgid')")."</strong></span>";
    }

    $sender=$tpl->td_href($FROM, null,
        "Loadjs('fw.postfix.transactions.php?zoom-from-js=$encode&domain=$domainFROM')");

    $ipaddr=$tpl->td_href($IP,null,
        "Loadjs('fw.postfix.transactions.php?zoom-ip-js=$IP&hostname=$hostname&maillog=yes')");

    $js_msgid=$tpl->td_href($msgid,null,"Loadjs('fw.postfix.transactions.php?second-query=".base64_encode("WHERE msgid='$msgid'")."&title=".urlencode("{see_messages_with}:$msgid")."')");

    $text="$js_msgid: $score_tootip from=$sender, To: $recipient  $ipaddr:[$hostname] $size<br><small>$subject</small>";

    return $text;
}
