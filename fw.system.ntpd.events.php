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
	if(!isset($_SESSION["NTPD_SEARCH"])){$_SESSION["NTPD_SEARCH"]="";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["NTPD_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	$tpl=new template_admin();
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

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    $search=urlencode(base64_encode($search));
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/events/$search/$rp");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }

    foreach ($json->Logs as $line){
        $line=trim($line);
		if(!preg_match("#^(.+?)\s+([0-9])+\s+([0-9:]+)\s+(.+?)\s+chronyd\[([0-9]+)\]:\s+(.+)#", $line,$re)){
			echo "<strong style='color:red'>$line</strong><br>"; 
			continue;}
		
		$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
		$xtime=strtotime($date);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		$pid=$re[5];
		$line=$re[6];

		if(preg_match("#(Fatal error|syntax error|unexpected|exiting on|format error|unable to|TIME_ERROR)#",$line)){
            $line="<span class='text-danger'>$line</span>";

        }
        if(preg_match("#(Listen normally|Selected source)#",$line)){
            $line="<span class='text-info'>$line</span>";

        }


		
		$html[]="<tr>
				<td style='width: 1%' nowrap>$FTime</td>
				<td style='width: 1%' nowrap>$pid</td>
				<td>$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	echo @implode("\n", $html);
	
	
	
}
