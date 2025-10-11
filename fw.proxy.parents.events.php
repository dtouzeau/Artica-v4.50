<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsFirewallManager){
    $tpl=new template_admin();
    echo $tpl->div_error("Privileges error");
    exit();
}

if(isset($_GET["form"])){start_form();exit;}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
    $page               = CurrentPageName();
    $tpl                = new template_admin();

    $html=$tpl->page_header("{parent_proxies} {events}",
        ico_eye,"{proxy_events_explain}","$page?form=yes","parents-events",
        "progress-parents-restart",false,"parents-events");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{parent_proxies}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function start_form():bool{
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}

function search(){
    $tpl=new template_admin();
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/proxy/parents/events/$rp/$search");

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
        	<th>&nbsp;</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
   $LEVELS["info"]="<span class='label label-default'>INFO</span>";
    $LEVELS["warning"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["error"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["fatal"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["debug"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["trace"]="<span class='label label-default'>TRACE</span>";

    $FONTS["warning"]="text-marning";
    $FONTS["info"]="text-muted";
    $FONTS["error"]="text-danger";
    $Peers=ListPeers();

    foreach ($json->Logs as $line){
	    $line=trim($line);
        $status="<span class='label label-default'>INFO</span>";
		$color="text-muted";
		if(!preg_match('#^(.+?)\s+([0-9:]+)\s+(.+)#', $line,$re)){
			echo "<strong style='color:red'>$line</strong><br>"; 
			continue;}
		
		$date=trim($re[1]." ".$re[2]);
        $line=$re[3];

        if(preg_match("#Peer([0-9:]+)#",$line,$re)){
            $PEERNum=$re[1];
            if(!isset($Peers["Peer$PEERNum"])){
                $Peers["Peer$PEERNum"]="Unknown Peer $PEERNum";
            }
            $PEERName=$Peers["Peer$PEERNum"]." #$PEERNum";
            $line=str_replace("Peer$PEERNum",$PEERName,$line);
        }
        if(preg_match("#(ERROR|DEAD)#",$line)){
            $status="<span class='label label-danger'>ERROR</span>";
        }
        if(preg_match("#REVIVED#",$line)){
            $status="<span class='label label-primary'>{connected2}</span>";
        }
		
		$html[]="<tr>
				<td style='width:1%' nowrap>$date</td>
				<td style='width:1%' nowrap>$status</td>
				<td><span class='$color'>$line</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";

    $TINY_ARRAY["TITLE"]="{parent_proxies} {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{proxy_events_explain}";
    $TINY_ARRAY["URL"]="parents-events";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="$jstiny";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function ListPeers():array
{

    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/peers/status");
    $jsonMain = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return array();
    }
    $ARRAY = array();
    if (!property_exists($jsonMain, "peers")) {
        return array();
    }

    foreach ($jsonMain->peers as $index => $json) {
        $Peer = $json->parent_name;
        $hostname = $json->parent_addr . ":" . $json->parent_port;
        $ARRAY[$Peer] = $hostname;
    }
    return $ARRAY;
}
