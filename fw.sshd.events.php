<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["SSHD_SEARCH"])){$_SESSION["SSHD_SEARCH"]="today this hour 50 events";}
	
	$html[]="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>";

    $TINY_ARRAY["TITLE"]="{APP_OPENSSH}: {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{OPENSSH_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshd";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$jstiny;
    $html[]="function Search$t(e){";
    $html[]="\tif(!checkEnter(e) ){return;}";
    $html[]="ss$t();";
    $html[]="}";
    $html[]="function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

	
	echo $tpl->_ENGINE_parse_body($html);

}
function re_preg($line):array{


    if(preg_match("#^(.+?)\s+([0-9])+\s+([0-9:]+)\s+(.+?)\s+sshd\[([0-9]+)\]:\s+(.+)#", $line,$re)) {
        return $re;
    }
    if(preg_match("#(.+?)\s+([0-9])+\s+([0-9:]+)\s+(.+?)\s+sshd\[(.+?)\]:\s+(.+)#", $line,$re)) {
        return $re;
    }

    if(preg_match("#(.+?)\s+([0-9])+\s+([0-9:]+)\s+(.+?)\s+sshproxy\[(.+?)\]:\s+(.+)#", $line,$re)) {
        return $re;
    }

    return array();

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$date=null;
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("sshd.php?syslog=$line");
	$filename=PROGRESS_DIR."/sshd.syslog";
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
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["SSHD_SEARCH"]=$_GET["search"];}
	rsort($data);

	
	foreach ($data as $line){
		$line=trim($line);
		$rulename=null;
		$ACTION=null;
        $re=re_preg($line);

        if(count($re)==0){
            continue;
           // echo "<span style='color:red'>$line</span><br>";
        }

		$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
		$xtime=strtotime($date);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		$pid=$re[5];
		$line=$re[6];

		if(preg_match("#(error:|fatal|Failed password|Invalid user|user unknown)#i", $line)){
			$line="<span class='text-danger'>$line</span>";
		}
		if(preg_match("#(Accepted password|proxy running)#i", $line)){
			$line="<span class='text-success'>$line</span>";
		}
		if(preg_match("#(Server listening|Accepted keyboard-interactive)#i", $line)){
			$line="<span class='text-success'>$line</span>";
		}
		
		if(preg_match("#(warning notify|restarting|authentication failures|authentication failure|Did not receive identification string)#i", $line)){
			$line="<span class='text-warning'>$line</span>";
		}
        $line=str_replace("[DENY]","<span class='label label-danger'>Deny</span>",$line);
        $line=str_replace("[ALLOW]","<span class='label label-primary'>Allow</span>",$line);
        $line=str_replace("CountryChecker:","<span class='label label-default'>Country checker</span>&nbsp;",$line);
        $line=str_ireplace("reputation:","<span class='label label-default'>Reputation</span>&nbsp;",$line);
        $line=str_ireplace("aclexec returned 1","<span class='label label-default'>plugin</span>&nbsp;Access denied by extra rules",$line);

		
		$html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td>$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/sshd.syslog.pattern")."</i></div>";
	echo @implode("\n", $html);
	
	
	
}
