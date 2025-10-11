<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_DNSDIST}&nbsp;&raquo;&nbsp;{events}",ico_eye,
        "{APP_DNSDIST_EXPLAIN2}",null,"dnsfw-service-events","progress-dnsdist-restart",
        true,"table-loader");




    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("dnsfw.php?backends-events=$line");
    $tfile=PROGRESS_DIR."/dnsfw.backends";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>PID</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($tfile));
	if(count($data)>3){$_SESSION["DNSDIST_SEARCH"]=$_GET["search"];}
	rsort($data);
    $months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");
	
	foreach ($data as $line){
		$line=trim($line);
		$rulename=null;
		$ACTION=null;
		$color="text-muted";
        if(preg_match("#can increase this value#",$line)){continue;}
        if(preg_match("#this configuration can use more than#",$line)){continue;}
        if(preg_match("#lease consider generating#",$line)){continue;}
        if(preg_match("#ABSOLUTELY NO WARRANTY#",$line)){continue;}
        $line=str_replace("ksrn_white()","[INFO]: Whitelisting",$line);
        $FOUND=false;
            if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]: (.+)#",$line,$re)){
                $FOUND=true;
                $month=$re[1];
                $day=$re[2];
                $hour=$re[3];
                $pid=$re[4];
                $line=trim($re[5]);
                $FTime="$month $day $hour";
            }

        $type="NONE";
        $color="text-default";
        if(!$FOUND){
            echo "<strong style='color:red'>$line</strong><br>";
            continue;
        }
        if($type=="ERROR"){$color="text-danger";}
        if($type=="DETECTED"){$color="text-danger font-bold";}
        if($type=="INFO"){$color="text-success font-bold";}
        if($type=="DEBUG"){$color="text-muted";}
        if($type=="CLIENT"){$color="text-success";}
        if($type=="NONE"){$color="text-default";}

        if(trim($line)=="None"){$color="text-danger";}
        if(preg_match("#as 'up'#i", $line)){$color="text-success";}
        if(preg_match("#: OK#", $line)){$color="text-success";}
        if(preg_match("#as 'down'#i", $line)){$color="text-danger font-bold";}
		if(preg_match("#Timeout while#i", $line)){$color="text-warning font-bold";}
        if(preg_match("#(Stopping|Exiting)\s+#i", $line)){$color="text-warning font-bold";}
        if(preg_match("#(unable|failed|error|fatal)\s+#i", $line)){$color="text-danger font-bold";}


		
		$html[]="<tr>
				<td width=1% class='$color' nowrap>$FTime</td>
				<td width=1% class='$color' nowrap >$pid</td>
				<td><span class='$color'>$line</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

