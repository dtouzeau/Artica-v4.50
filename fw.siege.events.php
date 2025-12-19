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
	if(!isset($_SESSION["PROXYPAC_SEARCH"])){$_SESSION["PROXYPAC_SEARCH"]="";}
//fad fa-scroll-old


    $html=$tpl->page_header("{APP_SIEGE} {events}",
        ico_eye,
    "{APP_SIEGE_EXPLAIN}",
        "$page?table=yes",
        "siege-events","progress-firehol-restart",true);


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_PROXY_PAC}",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$tpl=new template_admin();
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));
    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}
    $EndPoint="/siege/events/$ss/$MAX";

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>&nbsp;</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    $LEVELS["INFO"]="<span class='label label-default'>INFO</span>";
    $LEVELS["WARNING"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["FATAL"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["DEBUG"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["NOTICE"]="<span class='label label-default'>INFO</span>";
    $LEVELS["TRACE"]="<span class='label label-default'>TRACE</span>";
    $LEVELS["SUCCESS"]="<span class='label label-primary'>Success</span>";



    $FONTS["WARNING"]="text-warning";
    $FONTS["NOTICE"]="text-muted";
    $FONTS["ERROR"]="text-danger";
    $FONTS["INFO"]="text-muted";

    $tb=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/siege-daemon.log"));

    foreach ($tb as $line){

		$line=trim($line);
        if($line==null){continue;}
		if(!preg_match("#^(.+?)\s+\[(.+?)\] (.+?):(.+?):(.+)#", $line,$re)) {continue;}
        $zdate = $re[1];
        $Type = $re[2];
        $file = $re[3];
        $function=$re[4];
        $line=$re[5];
        $text=null;
        if(preg_match("#(error|failed|unable)#i",$line)){
            $text="text-danger";
        }
        if(preg_match("#(starting|success)#i",$line)){
            $text="text-info";
        }
        $text=$FONTS[$Type];
        $status=$LEVELS[$Type];

        

        $line="<span class='$text'>$line</span>";

		$html[]="<tr>
				<td style='width:1%' nowrap>$zdate</td>
				<td style='width:1%' nowrap>$status</td>
				<td>$line ($file/$function)</td>
				</tr>";
		
	}





	
	$html[]="</tbody></table>";

    $TINY_ARRAY["TITLE"]="{APP_SIEGE} {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_SIEGE_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=@implode("",array());
    $html[]= "<script>";
    $html[]= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]= "</script>";


	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
