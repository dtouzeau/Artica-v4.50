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
    $IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_VERSION");
    $html=$tpl->page_header("{APP_CROWDSEC} v$IPTABLES_VERSION {events}",
        ico_eye,"{APP_CROWDSEC_EXPLAIN}","$page?form=yes","crowdsec-events",
        "progress-crowdsec-restart",false,"crowdsec-events");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_CROWDSEC}",$html);
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

    $data=$sock->REST_API("/crowdsec/events/$rp/$search");

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

    foreach ($json->Logs as $line){
		$line=trim($line);
		$color="text-muted";
		if(!preg_match('#time="(.+?)"\s+level=(.+?)\s+msg="(.+?)"#', $line,$re)){
			echo "<strong style='color:red'>$line</strong><br>"; 
			continue;}
		
		$date=trim($re[1]);
        if(isset($FONTS[$re[2]])) {
            $color = $FONTS[$re[2]];
        }

        if(isset($LEVELS[$re[2]])) {
            $level = $LEVELS[$re[2]];
        }else{
            $level="<strong>{$re[2]}</strong>";
        }
        $line=$re[3];

		
		$html[]="<tr>
				<td width=1% nowrap>$date</td>
				<td width=1% nowrap>$level</td>
				<td><span class='$color'>$line</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";

    $TINY_ARRAY["TITLE"]="{APP_CROWDSEC} {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_CROWDSEC_EXPLAIN}";
    $TINY_ARRAY["URL"]="crowdsec-events";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="$jstiny";
    $html[]="</script>";

    echo @implode("\n", $html);
	
	
	
}
