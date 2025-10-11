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
    $html=$tpl->page_header("{IDS} {service_events}",
        ico_eye,"{about_ids}","$page?form=yes","ids-events",
        "progress-idsev-restart",false,"ids-events");

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
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("suricata.php?service-events=$line");
	$filename=PROGRESS_DIR."/suricata.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>&nbsp;</th>
        	<th width='1%'>PID</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
    rsort($data);

    $LEVELS["info"]="<span class='label label-default'>INFO</span>";
    $LEVELS["warning"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["error"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["fatal"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["debug"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["trace"]="<span class='label label-default'>TRACE</span>";
    $LEVELS["success"]="<span class='label label-primary'>Success</span>";

    $LEVELS[1]="<span class='label label-danger'>Prio 1</span>";
    $LEVELS[2]="<span class='label label-warning'>Prio 2</span>";
    $LEVELS[3]="<span class='label label-warning'>Prio 3</span>";
    $LEVELS[4]="<span class='label label-warning'>Prio 4</span>";

    $FONTS["warning"]="text-marning";
    $FONTS["info"]="text-muted";
    $FONTS["error"]="text-danger";
	
	foreach ($data as $line){
		$line=trim($line);
		$color="text-muted";
		if(!preg_match('#^([A-Z-a-z]+)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:(.+)#', $line,$re)){
			echo "<strong style='color:red'>$line</strong><br>"; 
			continue;}

        $level=$LEVELS["info"];
        $date=$re[1]." {$re[2]} {$re[3]}";
        $pid=$re[4];
        $content=$re[5];

        if(preg_match("#\[Priority:\s+([0-9]+)#",$content,$ri)){
            if(isset( $LEVELS[$ri[1]])){
                $level=$LEVELS[$ri[1]];
            }

        }
        if(preg_match("#ERR#",$content,$ri)){
                $level=$LEVELS["fatal"];
        }
        if(preg_match("#Success#",$content,$ri)){
            $level=$LEVELS["success"];
        }


		$html[]="<tr>
				<td width=1% nowrap>$date</td>
				<td width=1% nowrap>$level</td>
				<td width=1% nowrap>$pid</td>
				<td><span class='$color'>$content</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
    $page=CurrentPageName();
    $topbuttons[] = array("document.location.href='$page?download=yes'", ico_download, "{download} {logfile}");

    $TINY_ARRAY["TITLE"]="{IDS} {service_events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{about_ids}";
    $TINY_ARRAY["URL"]="ids-events";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="$jstiny";
    $html[]="</script>";

    echo @implode("\n", $html);
	
	
	
}
function download():bool{
    $FinalPath = "/var/log/suricata/suricata-service.log";
    $size = filesize($FinalPath);
    if (!$GLOBALS["VERBOSE"]) {
        header("Content-Type:  text/plain");
        header("Content-Disposition: attachment; filename=\"ids-service.log\"");
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
        header("Pragma: no-cache"); // HTTP 1.0
        header("Expires: 0"); // Proxies
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
        header("Content-Length: $size");
        ob_clean();
        flush();
        readfile("/var/log/suricata/suricata-service.log");
    } else {
        echo "Filename: $FinalPath\n<br>";
        echo "Content-Type:  \n<br>";
        echo "Content-Length:  " . filesize($FinalPath) . "<br>\n";
    }

    return true;
}
