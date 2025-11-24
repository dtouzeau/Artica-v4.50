<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsFirewallManager){
    $tpl=new template_admin();
    echo $tpl->div_error("Privileges error");
    exit();
}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["form"])){start_form();exit;}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $html=$tpl->page_header("{APP_ARTICA_SURICATA} {service_events}",
        ico_eye,"{about_ids}","$page?form=yes","ids-aevents",
        "progress-aevents-restart",false,"ids-aevents");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{IDS}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function start_form():bool{
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/suricataserv/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>&nbsp;</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
    $LEVELS["INFO"]="<span class='label label-default'>INFO</span>";
    $LEVELS["WARNING"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["FATAL"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["DEBUG"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["NOTICE"]="<span class='label label-default'>INFO</span>";
    $LEVELS["TRACE"]="<span class='label label-default'>TRACE</span>";
    $LEVELS["SUCCESS"]="<span class='label label-primary'>Success</span>";



    $FONTS["WARNING"]="text-warning";
    $FONTS["NOTICE"]="text-muted";
    $FONTS["ERROR"]="text-danger";
    $FONTS["INFO"]="text-muted";


    foreach ($json->Logs as $line){
		$sline=json_decode(trim($line));


        $date=$tpl->time_to_date($sline->time,true);
        $dLevel=$LEVELS[strtoupper($sline->level)];
        $color=$FONTS[strtoupper($sline->level)];

        $content=$sline->message;
        $content=str_replace("LogForward.go[LogForward.Start.","[",$content);
        $ss="style='width:1%' class='$color' nowrap";

		$html[]="<tr>
				<td $ss>$date</td>
				<td $ss>$dLevel</td>
				<td><span class='$color'>$content</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
    $page=CurrentPageName();
    $topbuttons[] = array("document.location.href='$page?download=yes'", ico_download, "{download} {logfile}");

    $TINY_ARRAY["TITLE"]="{APP_ARTICA_SURICATA} {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{about_ids}";
    $TINY_ARRAY["URL"]="ids-aevents";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="$jstiny";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}
function parseit($line):array{
    if(preg_match('#^\[([0-9]+)\s+.*?[0-9]+-(.+?)\] (.+?) ([0-9:]+) (.+?): (.+?): (.+)#', $line,$re)) {
     return array("PID" => $re[1],  "INTERFACE" => $re[2],  "DATE" =>"$re[3] $re[4]",  "LEVEL" => strtoupper($re[5]),  "FUNCTION" => $re[6],  "CONTENT" => $re[7] );
    }

    if(preg_match("#^\[([0-9]+)\s+.*?\] (.+?) ([0-9:]+) (.+?): (.+?): (.+)#",$line,$re)) {
        return array("PID" => $re[1],  "INTERFACE" => "-",  "DATE" =>"$re[2] $re[3]",  "LEVEL" => strtoupper($re[4]),  "FUNCTION" => $re[5],  "CONTENT" => $re[6] );
    }
    echo "<strong style='color:red'>$line</strong><br>";
    return array("PID" => "0",  "INTERFACE" => "",  "DATE" =>"",  "LEVEL" => "ERROR",  "FUNCTION" => "NONE",  "CONTENT" => $line );
}
function download():bool{
    $FinalPath = "/var/log/artica-suricata.log";
    $size = filesize($FinalPath);
    if (!$GLOBALS["VERBOSE"]) {
        header("Content-Type:  text/plain");
        header("Content-Disposition: attachment; filename=\"artica-suricata.log\"");
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
        header("Pragma: no-cache"); // HTTP 1.0
        header("Expires: 0"); // Proxies
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
        header("Content-Length: $size");
        ob_clean();
        flush();
        readfile("/var/log/artica-suricata.log");
    } else {
        echo "Filename: $FinalPath\n<br>";
        echo "Content-Type:  \n<br>";
        echo "Content-Length:  " . filesize($FinalPath) . "<br>\n";
    }

    return true;
}
