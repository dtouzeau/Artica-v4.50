<?php
if(!isset($_SESSION["PHPSEVNTS"])){$_SESSION["PHPSEVNTS"]="";}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["form"])){form();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["download"])){download_phplog();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();


function download_phplog(){
    $path="/var/log/php.log";
    header('Content-type: text/plain');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"php.log\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize($path);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($path);

}
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}




    $html=$tpl->page_header("{events}: PHP",
        "fab fa-php","{events}: Artica PHP","$page?form=yes","fw.php.logs.php",
        "progress-phplod-events",false,"table-loader-php-events");
	


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("PHP {events}");
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

function ParseContent($line):array{

    if(preg_match("#ArticaWeb: ([0-9\/]+)\s+([0-9:]+)\s+(.+)#",$line,$m)){
        return array($m[1]. " ".$m[2],$m[3]);
    }

    if(preg_match("#\[(.+?)\s+(.+?)\] (.+)#",$line,$m)){
        return array($m[1],$m[3]);
    }
    return array("",$line);
}


function table():bool{
	$tpl                    = new template_admin();
    $_SESSION["PHPSEVNTS"]   = trim(strtolower($_GET["search"]));
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $t=time();
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/php/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }



    $fields[]="{status}";
	$fields[]="{time}";
    $fields[]="{events}";

    $html[]=$tpl->table_head($fields,"table-$t");

	$TRCLASS=null;



    $css_labled="width:100%;display:block !important;padding:5px";
    $text_icon["text-warning font-bold"]="<div class='label label-warning' style='$css_labled'>{warning}</div>";
    $text_icon["text-info font-bold"]="<div class='label label-info' style='$css_labled'>{info}</div>";
    $text_icon["text-danger font-bold"]="<div class='label label-danger' style='$css_labled'>{error}</div>";

    $text_icon["arbackup"]="<div class='label label-info' style='$css_labled'>{backup}</div>";

    $text_icon[null]="<div class='label label' style='$css_labled'>{event}</div>";

    foreach ($json->Logs as $line){
        $line       = trim($line);
        if($line==null){continue;}
        $text_class=null;
        $p1=false;
        $p2=false;
        $content=null;

        list($date,$content)=ParseContent($line);


        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}


        if(strpos($content,'logsink_backup')>0){$text_class="arbackup";}

        if(strpos("  $content",'[error]')>0){$text_class="text-danger font-bold";}
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
        if(strpos($content,'PHP message')>0){$text_class="text-info font-bold";}
        if(stripos($content,'PHP Notice:')>0){$text_class="text-warning font-bold";}
        if(strpos($content,'resumed (')>0){$text_class="text-warning font-bold";}
        if(strpos($content,'suspended,')>0){$text_class="text-warning font-bold";}
        if(strpos($content,'Error in received')>0){$text_class="text-warning font-bold";}
        if(strpos($content,'suspended,')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'origin software="rsyslogd"')>0){$text_class="text-info font-bold";}
        if(strpos($content,'could not interpret')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'error during parsing file')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'is unknown ')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'could not load module')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'PHP Fatal error')>0){$text_class="text-danger font-bold";}
        if(strpos($content,'Uncaught Error:')>0){$text_class="text-danger font-bold";}
        if(strpos("  $content",'PHP Parse error')>0){$text_class="text-danger font-bold";}

        if(preg_match("#\[.*?try (.+?)\]#",$content,$re)){
            $srep=$tpl->td_href("{SAMBA_ERROR_CLICK}","{SAMBA_ERROR_CLICK}",
                "s_PopUpFull('{$re[1]}','1024','900');");
            $content=str_replace("try {$re[1]}","<small>$srep</small>"
                ,$content);
        }
        $content=wordwrap($content,180,"<br>");
		$html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"\" width='1%' nowrap=''>{$text_icon[$text_class]}</td>";
        $html[]="<td class=\"\" width='1%' nowrap=''>$date</td>";
		$html[]="<td class=\"\"  style='width:99%' >$content</td>";
		$html[]="</tr>";
		

	}
    $page=CurrentPageName();
    $topbuttons[] = array("document.location.href='$page?download=yes'",ico_download,"{download}");
    $TINY_ARRAY["TITLE"]="{events}: PHP";
    $TINY_ARRAY["ICO"]="fab fa-php";
    $TINY_ARRAY["EXPL"]="Artica PHP {events}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$tpl->table_footer("table-$t",count($fields),false);
    echo $tpl->_ENGINE_parse_body($html);
    echo "<script>$jstiny</script>";
    return false;
}

