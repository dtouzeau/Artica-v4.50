<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["form"])){form();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();



function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["SYSLOGSEVNTS"]==null){$_SESSION["SYSLOGSEVNTS"]="limit 200";}

    $html=$tpl->page_header("{events}: {APP_SYSLOGD}",
        ico_eye,"{APP_SYSLOG_SERVER_EXPLAIN}","$page?form=yes","syslogd-events",
        "progress-syslod-events",false,"table-loader-syslog-events");
	


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{icap_events}");
        return true;
    }
	
	echo $tpl->_ENGINE_parse_body( $html);
    return true;

}
function form():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    echo $tpl->search_block($page);
    return true;

}

function table():bool{
	$tpl                    = new template_admin();
	$page                   = CurrentPageName();
	$t                      = $_GET["t"];
	$target_file            = PROGRESS_DIR."/syslog-events.log";
    $_SESSION["SYSLOGSEVNTS"]   = trim(strtolower($_GET["search"]));
    $search=$tpl->format_search_protocol($_GET["search"],false,true,false,false);
    $ss                     = base64_encode($search["TERM"]);
    if(!is_numeric($t)){$t=time();}
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("syslog.php?syslog-events=$ss&rp={$search["MAX"]}");
	$datas=explode("\n",@file_get_contents($target_file));

    $fields[]="{status}";
	$fields[]="{time}";
    $fields[]="{pid}";
    $fields[]="{events}";

    $html[]=$tpl->table_head($fields,"table-$t");

	$TRCLASS=null;
	krsort($datas);
    $pregm="^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:\s+(.+)";
    $pregm2="^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?:(.+)";


    if(strpos($datas[0],"tail: cannot open")>0){
        echo $tpl->div_warning("{no_data}");
        return false;
    }
    $css_labled="width:100%;display:block !important;padding:5px";
    $text_icon["text-warning font-bold"]="<div class='label label-warning' style='$css_labled'>{warning}</div>";
    $text_icon["text-info font-bold"]="<div class='label label-info' style='$css_labled'>{info}</div>";
    $text_icon["text-danger font-bold"]="<div class='label label-danger' style='$css_labled'>{error}</div>";

    $text_icon["arbackup"]="<div class='label label-info' style='$css_labled'>{backup}</div>";

    $text_icon[null]="<div class='label label' style='$css_labled'>{event}</div>";

	foreach ($datas as $key=>$line){
        $line       = trim($line);
        if($line==null){continue;}
        $text_class=null;
        $p1=false;
        $p2=false;
        $content=null;
	    if(!preg_match("#$pregm#",$line,$re)){
            if(!preg_match("#$pregm2#",$line,$re)) {
                echo "<H1>$line <code>$pregm</code></H1>\n";
                continue;
            }else{
                $p2=true;

            }
        }else{
            $p1=true;
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $date=$re[1]." {$re[2]} {$re[3]}";
        $pid=$re[4];
        if(isset($re[5])) {
            $content = $re[5];
        }
        if($p2){
            $pid="000";
            $content=$re[4];
        }

        if(strpos($content,'logsink_backup')>0){$text_class="arbackup";}

        if(strpos($content,'OpenSSL Error')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'Certificate error at depth')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'certificate verify failed')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'Handshake failed with error code')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'no shared curve between')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'initiated with')>0){$text_class="text-info font-bold";}
        if(strpos($content,'SSL_ERROR_SSL')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'SSL_ERROR_SYSCALL')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'Syntax Error in')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'will be closed due to error')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'failed to process incoming connection')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'retry returned error')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'error on handshake')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'invalid or yet-unknown')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'Could not create')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'queue files exist on')>0){$text_class="text-info font-bold";}
        if(strpos($content,'error: ')>0){$text_class="text-warning font-bold";}
        if(strpos($content,'suspended (')>0){$text_class="text-warning font-bold";}
        if(stripos($content,'warning during parsing')>0){$text_class="text-warning font-bold";}
        if(stripos($content,'warning:')>0){$text_class="text-warning font-bold";}
        if(strpos($content,'resumed (')>0){$text_class="text-warning font-bold";}
        if(strpos($content,'suspended,')>0){$text_class="text-warning font-bold";}
        if(strpos($content,'Error in received')>0){$text_class="text-warning font-bold";}
        if(strpos($content,'suspended,')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'origin software="rsyslogd"')>0){$text_class="text-info font-bold";}
        if(strpos($content,'could not interpret')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'error during parsing file')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'is unknown ')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'could not load module')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'Failed to connect to ')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'checkResult error')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'opening error file')>0){$text_class="text-danger font-bold";}
        if(strpos($content,']: lost ')>0){$text_class="text-danger font-bold";}
        if(preg_match("#\[.*?try (.+?)\]#",$content,$re)){
            $srep=$tpl->td_href("{SAMBA_ERROR_CLICK}","{SAMBA_ERROR_CLICK}",
                "s_PopUpFull('{$re[1]}','1024','900');");
            $content=str_replace("try {$re[1]}","<small>$srep</small>"
                ,$content);
        }

		$html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"\" width='1%' nowrap=''>{$text_icon[$text_class]}</td>";
        $html[]="<td class=\"\" width='1%' nowrap=''>$date</td>";
        $html[]="<td class=\"\" width='1%' nowrap=''>$pid</td>";
		$html[]="<td class=\"\"  width='99%' >$content</td>";
		$html[]="</tr>";
		

	}

    $html[]=$tpl->table_footer("table-$t",count($fields),false);
    echo $tpl->_ENGINE_parse_body($html);
    return false;
}

