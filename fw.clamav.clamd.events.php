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
	if(!isset($_SESSION["CLAMD_SEARCH"])){$_SESSION["CLAMD_SEARCH"]="50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["CLAMD_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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

	$sock=new sockets();
	$tpl=new template_admin();



    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    $data=$sock->REST_API("/clamd/events/$rp/$search");

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
        	<th>PID</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	

	if(count($json->Logs)>3){$_SESSION["CLAMD_SEARCH"]=$_GET["search"];}


    foreach ($json->Logs as $line){
        $line=trim($line);
		$color="text-muted";

        $main=ParseLine($line);
        if(count($main)==0){
            continue;
        }

		$date=$main["DATE"];
		$xtime=strtotime($date);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		$line=trim($main["EVENTS"]);
        $PID=$main["PID"];
		if(preg_match("#^[\-]+$#", $line)){continue;}
		
		
		if(preg_match("#Downloading#", $line)){$color="text-info font-bold";}
		if(preg_match("#Can't connect#", $line)){$color="text-danger";}
		if(preg_match("#errno=[0-9]+#", $line)){$color="text-danger";}
		if(preg_match("#Database updated#", $line)){$color="text-success";}
		if(preg_match("#Started at#", $line)){$color="text-success";}
		if(preg_match("#WARNING:#", $line)){$color="text-warning";}
		if(preg_match("#failed:#", $line)){$color="text-danger font-bold";}
		if(preg_match("#Disabling#", $line)){$color="text-warning";}
		if(preg_match("#Not loading#", $line)){$color="text-warning";}
		if(preg_match("#ERROR:#", $line)){$color="text-danger";}
		
		$html[]="<tr>
				<td style='width:1%' nowrap>$FTime</td>
				<td style='width:1%' nowrap>$PID</td>
				<td><span class='$color'>$line</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
    $TINY_ARRAY["TITLE"]="{APP_CLAMAV} {service_events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_CLAMAV_TEXT}";
    $TINY_ARRAY["URL"]="clamav";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="$jstiny";
    $html[]="</script>";

    echo @implode("\n", $html);
	
	
	
}
function ParseLine($line):array{
    $line=trim($line);
    if(preg_match("#(.+?)->\s+(.+)#", $line,$re)){
        return array("DATE"=>$re[1],"EVENTS"=>$re[2],"PID"=>"");
    }
    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]: (.+)#", $line,$re)) {
        return array("DATE" => $re[1] . " " . $re[2]." ".$re[3], "PID"=>$re[4],"EVENTS" => $re[5]);

    }
    echo "<strong style='color:red'>$line</strong><br>";
    return array();
}