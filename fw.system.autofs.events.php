<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.rsyslogd.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["search"])){search_results();exit;}

page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{automount_center} {events}",
        ico_eye,"{browse_events}","$page?search-section=yes","logs-sink-realtime",
        "progress-syslod-restart",true,"table-loader-syslod-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{automount_center} {events}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function search_results():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();



    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/autofs/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }




    $html[]="<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
            <th>PID</th>
            <th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";
    foreach ($json->Events as $line){
        $line=trim($line);
        if($line==null){continue;}
        $class=null;

        if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:(.*)#",$line,$re)){continue;}
        $Month=$re[1];
        $Day=$re[2];
        $time=$re[3];
        $pid=$re[4];
        $line=trim($re[5]);



        if(preg_match("#(No such file|Invalid|Error querying)#i",$line)){
            $class="text-warning font-bold";
        }
        if(preg_match("#(syntax error|failed to|Error connecting)#",$line)){
            $class="text-danger text-bold";
        }

        $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$Month $Day $time</td>
				<td style='width:1%;' nowrap class='$class'>$pid</td>
				<td class='$class'>$line</td>
				</tr>";
    }

    /*
    $TINY_ARRAY["TITLE"]="{logs_sink} {realtime_events_squid}";
    $TINY_ARRAY["ICO"]=ico_search_in_file;
    $TINY_ARRAY["EXPL"]="{browse_events} {filesize} <strong>$file_size/{$LogSynRTMaxSize}MB</strong>";
    $TINY_ARRAY["URL"]="logs-sink-realtime";
    //$TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    */

    $html[]="</table>";
    $html[]="<script>";
    //$html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}