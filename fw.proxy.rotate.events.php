<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["page2"])){page2();exit;}
if(isset($_GET["search"])){search();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{legal_logs}",ico_eye,"{legal_logs_explain}",
        "$page?page2=yes","proxy-legal-events","progress-logrotate-restart",false,"table-loader-proxy-list");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{legal_logs}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function page2(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    echo $tpl->search_block($page,"","","","","?urltoadd=yes");


}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>
	<thead>
    	<tr>
        	
        	<th>$date_text</th>
        	<th>{process}</th>
        	<th>PID</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody id='rotateevents-rows'>
";
	
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/proxy/logrotate/events/$rp/$search");



    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }

    if(is_array($json->Events) && count($json->Events)>3){$_SESSION["LEGAL_LOGS_SEARCH"]=$_GET["search"];}
    foreach ($json->Events as $line){
		$line=trim($line);
		$array=ParseLine($line);
        if(count($array)<2){
            continue;
        }
        $FTime=$array["TIME"];
        $pid=$array["PID"];
        $Binary=$array["BINARY"];
        $line=$array["LINE"];

        $class=null;

		if(preg_match("#(Failed password|Invalid user|user unknown|fatal|error)#i", $line)){
		    $class="text-danger";
			$line="<span class='text-danger'>$line</span>";
		}
		if(preg_match("#(Success)#i", $line)){
			$line="<span class='text-success'>$line</span>";
            $class="text-success";
		}

		
		if(preg_match("#(warning notify|restarting|authentication failures|authentication failure|Did not receive identification string)#i", $line)){
			$line="<span class='text-warning'>$line</span>";
		}

		
		$html[]="<tr>
				<td style='width:1%' nowrap><span class='$class'>$FTime</span></td>
				<td style='width:1%' nowrap><span class='$class'>$Binary</span></td>
				<td style='width:1%' nowrap><span class='$class'>$pid</span></td>
				<td>$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='rotateevents-pager'></span></div></div></div>";
    // Le titre reprend le terme saisi (l'ancien motif grep du framework, supprime, y
    // laissait des fragments de regex comme "Oct\s+.*?").
    $term=trim(strval($MAIN["TERM"]));
    $TINY_ARRAY["TITLE"]="{legal_logs}: {events}";
    if($term<>""){$TINY_ARRAY["TITLE"].=" &laquo;".htmlspecialchars($term,ENT_QUOTES)."&raquo;";}
    $TINY_ARRAY["ICO"]="fas fa-eye";
    $TINY_ARRAY["EXPL"]="{legal_logs_explain}";
    $TINY_ARRAY["URL"]="logs-rotate";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="<script>$jstiny</script>";
    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"rotateevents-rows","rotateevents-pager");
}
// Pentest 2026-11-04 : ligne de journal logrotate (donnee externe) affichee sans encodage (XSS stockee).
function ParseLine($line):array{
    $MONTHS=array("Jan"=>1,"Feb"=>2,"Mar"=>3,"Apr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Aug"=>8,"Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);

    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]: (.+?)\[(.+?)\] (.+)#",$line,$re)){
        $MonthText = $re[1];
        $MonthNum = $MONTHS[$MonthText];
        $DayNum = $re[2];
        $Time = $re[3];
        $pid = $re[4];
        $Binary=req_html($re[5]);
        $line = req_html($re[7]) . "&nbsp;(<i>".req_html($re[6])."</i>)"; $xtime = strtotime(date("Y") . "-" . $MonthNum . "-" . $DayNum . " $Time");
        $FTime = date("Y-m-d H:i:s", $xtime);
        $curDate = date("Y-m-d");
        $FTime = trim(str_replace($curDate, "", $FTime));
        return array(
            "TIME" => $FTime,
            "PID" => $pid,
            "BINARY" => $Binary,
            "LINE" => $line
        );
    }

    if(preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\s+(.*?)\[([0-9]+)\]:(.+)#", $line,$re)) {

        $MonthText = $re[1];
        $MonthNum = $MONTHS[$MonthText];
        $DayNum = $re[2];
        $Time = $re[3];
        $Binary = req_html($re[4]);
        $pid = $re[5];
        $line = req_html($re[6]);
        $xtime = strtotime(date("Y") . "-" . $MonthNum . "-" . $DayNum . " $Time");
        $FTime = date("Y-m-d H:i:s", $xtime);
        $curDate = date("Y-m-d");
        $FTime = trim(str_replace($curDate, "", $FTime));
        return array(
            "TIME" => $FTime,
            "PID" => $pid,
            "BINARY" => $Binary,
            "LINE" => $line
        );
    }



    VERBOSE("NOUT FOUND [$line]",__LINE__);
    return array();
}
