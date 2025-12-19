<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.modsectools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once("ressources/class.resolv.conf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["form"])){form_search();exit;}
if(isset($_GET["zoom-js"])){zoomjs();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
page();



function zoomjs(){
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ip     = $_GET["zoom-js"];
    $ipenc  = urlencode($ip);
    $tpl->js_dialog($ip,"$page?zoom-popup=$ipenc");

}

function zoom_popup(){
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $ip     = $_GET["zoom-popup"];
    $ipenc  = urlencode($ip);
    $resolv = new resolv_conf(true);
    $hostname = $resolv->gethostbyaddr($ip);
    if($resolv->mysql_error<>null){
        $html[]=$tpl->div_error($resolv->mysql_error);
    }

    $html[]="<H1>$ip</H1>";
    $html[]="<H2>$hostname</H2>";

    echo $tpl->_ENGINE_parse_body($html);

}

function page(): bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html[]= $tpl->page_header("DecisionIP {firewall_events}","fas fa-eye","{firewall_events_explain}",
        "$page?form=yes","decisionip-events","progress-decifw-events",false,"table-decifw-syslog");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function form_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $search="";
    echo $tpl->search_block($page,null,null,"value=$search");
    return true;

}



function search(){
    $tpl=new template_admin();
    $_SESSION["FW_SEARCH"]=$_GET["search"];
	$MAIN=$tpl->format_search_protocol($_GET["search"]);

    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API("/decisionip/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
        return false;
    }
    if(!property_exists($json,"Logs") or !$json->Logs){
        echo $tpl->div_warning("{error}||{no_data}<hr>");
        return false;
    }

	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$port=$tpl->_ENGINE_parse_body("{port}");
	$src=$tpl->_ENGINE_parse_body("{src}");
	$dst=$tpl->_ENGINE_parse_body("{dst}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>$date_text</th>
        	<th></th>
        	<th></th>
            <th>$src</th>
            <th></th>
            <th>$port/$dst</th>
        </tr>
  	</thead>
	<tbody>
";


    $modtools=new modesctools();

    foreach ($json->Logs as $line){
		$line=json_decode(trim($line));
        if (json_last_error()> JSON_ERROR_NONE) {
            continue;
        }
		$rulename=null;
        $OUT=null;
        $ico="";
        if(!property_exists($line,"timestamp")){
            continue;
        }
        $FTime=$tpl->time_to_date($line->timestamp,true);

        $ACTION="<span class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;$line->prefix&nbsp;&nbsp;&nbsp;&nbsp;</span>";


		$IN=$line->in_iface;
		$MAC_DEST=$line->mac_dst;
		$MAC_SRC=$line->mac_src;
        //$OUT=$line->out_iface;
		

        $modtools->hostinfo($line->src_ip,true);
        $flag="";
        $hostname="";
        if(strlen($modtools->flag)>3){
            $flag="<img src='img/".$modtools->flag."'>&nbsp;&nbsp;";
        }
        if(strlen($modtools->hostname)>3){
            if(!preg_match("#^[0-9\.]+$#",$modtools->hostname)) {
                $hostname = "<br><small>(" . $modtools->hostname . ")</small>";
            }
        }
        $srcipEn=urlencode($line->src_ip);
        $src=$tpl->td_href($line->src_ip,"","Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$srcipEn')");
        $fleche=ico_arrow_right;
		$html[]="<tr>
				<td style='width:1%' nowrap>$FTime</td>
				<td style='width:1%' nowrap>$ACTION</td>
				<td style='width:1%' nowrap>$flag</td>
                <td style='width:50%' nowrap>$ico&nbsp;$src:$line->sport&nbsp;<small>$MAC_SRC</small>&nbsp;$hostname</td>             
                <td style='width:1%' nowrap><i class='$fleche'></i>&nbsp;</td>
                <td style='width:50%' nowrap><strong style='font-size:16px'>$line->dport</strong>&nbsp;[$IN]&nbsp;$line->dst_ip&nbsp;<small>$MAC_DEST</small></td>  
                </tr>";
		
	}
	
	$html[]="</tbody></table>";
	echo @implode("\n", $html);
	
	
	
}
