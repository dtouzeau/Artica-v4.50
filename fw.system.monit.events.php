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
	if(!isset($_SESSION["MONIT_SEARCH"])){$_SESSION["MONIT_SEARCH"]="50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["MONIT_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
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

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($tpl->CLEAN_BAD_XSS($_GET["search"]));
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("monit.php?events=$line");
	$filename=PROGRESS_DIR."/monit.syslog";

	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
    	    <th>{time}</th>
    	    <th>PID</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["MONIT_SEARCH"]=$_GET["search"];}
	rsort($data);
    $months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");
	
	foreach ($data as $line){
		$line=trim($line);
		if($line==null){continue;}

		if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?monit\[([0-9]+)\]: (.+)#",$line,$re)){

            if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?monit(.*?)(.+)#",$line,$re)) {
                echo $line . "<br>";
                continue;
            }
        }

		$Month=$months[$re[1]];
		$Year=date("Y");
		$Day=$re[2];
		$Time=$re[3];
		$PID=$re[4];
		$line=$re[5];
        $color=null;
		$zTime=strtotime("$Day-$Month-$Year $Time");
        $zDate=$tpl->time_to_date($zTime,true);

        if(preg_match("#'APP_(.+?)'#",$line,$re)){
            $line=$line." ({APP_{$re[1]}})";
        }


        if(preg_match("#(started|starting)#i", $line)){$color="text-success";}
		if(preg_match("#\s+no\s+#", $line)){$color="text-warning";}
		if(preg_match("#(not running|syntax error|Cannot|conflict)#", $line)){$color="text-danger";}
		if(preg_match("#Database updated#", $line)){$color="text-success";}
		if(preg_match("#Starting Zabbix#", $line)){$color="text-success font-bold";}
		if(preg_match("#\s+stopped(\s+|\.)#i", $line)){$color="text-warning";}
		if(preg_match("#failed:#", $line)){$color="text-danger font-bold";}
		if(preg_match("#(Disabling|stopped)#i", $line)){$color="text-warning";}
		if(preg_match("#Not loading#", $line)){$color="text-warning";}
		if(preg_match("#ERROR:#", $line)){$color="text-danger";}
		
		$html[]="<tr>
                <td style='width:1%' nowrap><span class='$color'>$zDate</span></td>
                <td style='width:1%' nowrap><span class='$color'>$PID</span></td>
				<td><span class='$color'>$line</span></td>
				
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
    $TINY_ARRAY["TITLE"]="{system_health_checking}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{monit_logs_explain}";
    $TINY_ARRAY["URL"]="system-watchdog";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";
	echo $tpl->_ENGINE_parse_body($html);

	
	
}
