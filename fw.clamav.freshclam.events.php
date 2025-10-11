<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(isset($_SESSION["FRESHCLAM_SEARCH"])){
        $_SESSION["FRESHCLAM_SEARCH"]="";
    }
    echo "<div style='margin-top:20px'>";
	echo $tpl->search_block($page,"","","","",);
    echo "</div>";
    return true;
}


function PtoRegex($line):array{

    if(preg_match("#(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:(.+)#", $line,$re)) {
        $date=strtotime(trim($re[1]." ".$re[2]." ".$re[3]));
        $pid=$re[4];
        $message=trim($re[5]);
        return array($date,$pid,$message);
    }

    if(preg_match("#^(.+?)\s+->(.+)#", $line,$re)){
        $date=strtotime($re[1]);
        $message=trim($re[2]);
        $pid="";
        return array($date,$pid,$message);
    }
    echo "<strong style='color:red'>$line</strong><br>";
    return array(0,"","");
}
function search(){
	$tpl=new template_admin();
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));

    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}
    $EndPoint="/freshclam/events/$ss/$MAX";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);


	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th style=''width:1%' nowrap>$date_text</th>
        	<th style=''width:1%' nowrap>PID</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    foreach ($json->Logs as $line){
		$line=trim($line);
		$color="text-muted";

        $array=PtoRegex($line);
        if ($array[0]==0){
            continue;
        }

        if(strpos($array[2],"-------------------------")!==false){
            continue;
        }
		
		$date=$tpl->time_to_date($array[0],true);
		$pid=$array[1];
		$message=trim($array[2]);
        $label=null;
        if(preg_match("#\[Artica\]:\s+.+?>(.+)#",$message,$re)){
            $label="<span class='label label-default'>Artica</span>&nbsp;";
            $message=$re[1];
        }

		
		
		if(preg_match("#Downloading#", $line)){$color="text-info font-bold";}
		if(preg_match("#Can't connect#", $message)){$color="text-danger";}
		if(preg_match("#errno=[0-9]+#", $message)){$color="text-danger";}
		if(preg_match("#Database updated#", $message)){$color="text-success";}
		if(preg_match("#Started at#", $message)){$color="text-success";}
		if(preg_match("#WARNING:#", $message)){$color="text-warning font-bold";}
		if(preg_match("#failed:#", $message)){$color="text-danger font-bold";}
		if(preg_match("#Disabling#", $message)){$color="text-warning font-bold";}
		if(preg_match("#Not loading#", $message)){$color="text-warning font-bold";}
		if(preg_match("#ERROR:#", $message)){$color="text-danger";}
		
		$html[]="<tr>
				<td style='width:1%' nowrap>$date</td>
				<td style='width:1%' nowrap>$pid</td>
				<td><span class='$color'>$label$message</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/freshclam.syslog.pattern")."</i></div>";




    $TINY_ARRAY["TITLE"]="{APP_CLAMAV}: {update_events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{clamav_antivirus_databases_explain}";
    $TINY_ARRAY["URL"]="clamav";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="$jstiny";
    $html[]="</script>";

    echo  $tpl->_ENGINE_parse_body($html);
	
	
	
}
