<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.hacluster-backends.inc");
include_once(dirname(__FILE__) . "/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["explain"])){explain_js();exit;}
if(isset($_GET["explain-popup"])){explain_popup();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){search();exit;}
page();


function explain_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$_GET["title"];
    $text=$_GET["text"];
    $titleenc=urlencode($title);
    $textenc=urlencode($text);
    $tpl->js_dialog1($title,"$page?explain-popup=$title&text=$textenc");
}

function explain_popup(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$_GET["title"];
    $text=$_GET["text"];
    $html[]="<H2>$title</H2>";
    $html[]=$tpl->div_explain($text);
    echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header(
		"{trace_connexions}",
		ico_eye,
		"{hacluster_connections_explain}",
		"$page?start=yes",
		"hacluster-connections",
		"progress-firehol-restart",false,
		"table-hacluster-connections"
	);

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_HACLUSTER} {trace_connexions}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

function start():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=array();
	$html[]="<div style='margin-top:10px'>";
	$html[]=$tpl->search_block($page,"","","","");
	$html[]="</div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
	return true;
}

function search(){


	$tpl=new template_admin();
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));


    $results=hacluster_db_list_backends("bweight", "ASC");
    foreach ($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $listen_ip = $ligne["listen_ip"];
        $listen_port = $ligne["listen_port"];
        $backendname = $tpl->td_href($ligne["backendname"], "$listen_ip:$listen_port", "Loadjs('fw.hacluster.backends.php?backend-js=$ID')");
        $MAIN_BACKENDS[$ID] = $backendname;
    }
    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}
    $EndPoint="/hacluster/queries/$ss/$MAX";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);


    $rows=array();
    if(!isset($MAIN_BACKENDS)){ $MAIN_BACKENDS=array(); }
    $page=CurrentPageName();
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>".htmlspecialchars($data).json_last_error_msg())."<script>NoSpinner();</script>";
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error)."<script>NoSpinner();</script>";
        return false;
    }





    $icoArrow=ico_arrow_right;
    $icoIface=ico_nic;
    $logs=isset($json->Logs) && is_array($json->Logs) ? $json->Logs : array();
    foreach ($logs as $line) {
        $line = trim($line);
        $line=str_replace("\u003c","<",$line);
        if(!preg_match("#rt-requests=<(.+?)>rt-requests#", $line,$re)){
            continue;
        }
        $jsonLine=json_decode($re[1]);

        if (json_last_error()> JSON_ERROR_NONE) {
            VERBOSE("[$re[1]] unparsable",__LINE__);
            VERBOSE("[$re[1]] ".json_last_error_msg(),__LINE__);
           continue;
        }

        //var_dump($jsonLine);

        $pid=null;
        $client=$jsonLine->client_ip;
        $frontend_name=$jsonLine->frontend_name_transport;
        $status=null;
        $rpxyname=$jsonLine->server_name;
        $queue_ms=$jsonLine->Tw;
        $backend_ms=$jsonLine->Tc;;
        $class=null;
        $ID=null;
        $connexions=null;
        $URI_column=null;
        $PROTO=$jsonLine->http_method;
        $bytes=$jsonLine->bytes_read;
        $FTime=date("Y-m-d H:i:s",$jsonLine->timestamp);
        $backendname=$tpl->icon_nothing();
        $termination_state=$jsonLine->termination_state;
        $http_request_uri_without_query=$jsonLine->http_request_uri_without_query;
        $status_code=intval($jsonLine->status_code);
        if($PROTO=="TCP"){
            $http_request_uri_without_query="tcp://$http_request_uri_without_query";
        }

        $outface=$jsonLine->outface;
        $BackendAddr=$jsonLine->backend_address.":".$jsonLine->backend_port;
        $total_session_time_ms=$jsonLine->total_session_time_ms;

        if(preg_match("#^(T|)(proxy|proxyNoAuth)([0-9]+)#",$rpxyname,$re)){
            $ID=$re[3];
            if(isset($MAIN_BACKENDS[$ID])){
                $backendname=$MAIN_BACKENDS[$ID];
            }
        }

		$time_ms=$total_session_time_ms;
		if($time_ms<1000){
		    $time="{$time_ms}ms";
        }else{
		    $time=round($time_ms/1000,1)."s";
        }

        if($bytes>1024){
            $bytes=FormatBytes($bytes/1024);
        }else{
            $bytes="$bytes Bytes";
        }
        list($status,$class)=TERMINAISON($termination_state,$status_code);

        $rows[]=hacluster_cnx_row($class,$FTime,$client,$PROTO,$http_request_uri_without_query,$outface,$BackendAddr,$backendname,$bytes,$time,$status);
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$html[]="<th class='awc-nowrap'>{date}</th>";
	$html[]="<th class='awc-nowrap'>{client}</th>";
	$html[]="<th class='awc-nowrap'>{type}</th>";
	$html[]="<th>{website}</th>";
	$html[]="<th class='awc-nowrap'>{backend}</th>";
	$html[]="<th class='awc-nowrap' style='text-align:right'>{size}</th>";
	$html[]="<th class='awc-nowrap' style='text-align:right'>{duration}</th>";
	$html[]="<th class='center'>{status}</th>";
	$html[]="</tr></thead><tbody id='awc-rows'>";
	if(count($rows)==0){ $html[]="<tr><td colspan='8'><div class='awc-empty'>{no_data}</div></td></tr>"; }
	else { $html[]=implode("\n",$rows); }
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} ".count($rows)." / $MAX {items}</span><span class='awc-pg' id='awc-pager'></span></div>";
	$html[]="</div></div>";

	// TINY : titre + boutons, emis en DERNIER, apres le tableau (skill §2, §7d)
	$page=CurrentPageName();
	$topbuttons=array();
	$topbuttons[]=array("LoadAjax('table-hacluster-connections','$page?start=yes')",ico_refresh,"{refresh}");
	$TINY=array();
	$TINY["TITLE"]="{APP_HACLUSTER}&nbsp;&raquo;&nbsp;{trace_connexions}";
	$TINY["ICO"]=ico_eye;
	$TINY["EXPL"]="{hacluster_connections_explain}";
	$TINY["URL"]="hacluster-connections";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

	// CSS, scripts et pager concatenes AUTOUR du parse, jamais dedans (skill §17 regle 1)
	$css="<style>.hcx-st{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;padding:3px 7px;border-radius:4px;color:#fff;background:#26a69a}
.hcx-st-danger{background:#ba1a1a}.hcx-st-warning{background:#865300}
.hcx-uri{word-break:break-all}</style>";
	$js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$tiny</script>";
	echo awc_table_style().$css.$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js.awc_pager_js(50);
}

// une ligne du journal (skill §13b). $backendname est deja du HTML (td_href), $status un {token}.
function hacluster_cnx_row(string $class,string $FTime,$client,$PROTO,$uri,$outface,$BackendAddr,string $backendname,$bytes,$time,$status):string{
	$badge="hcx-st";
	if(strpos($class,"text-danger")!==false){ $badge.=" hcx-st-danger"; }
	if(strpos($class,"text-warning")!==false){ $badge.=" hcx-st-warning"; }
	$h ="<tr>";
	$h.="<td class='awc-nowrap awc-mono'>".htmlspecialchars($FTime)."</td>";
	$h.="<td class='awc-nowrap'><span class='awc-seen'>".htmlspecialchars((string)$client)."</span></td>";
	$h.="<td class='awc-nowrap'><span class='awc-tag'>".htmlspecialchars((string)$PROTO)."</span></td>";
	$h.="<td class='hcx-uri' $class>".htmlspecialchars((string)$uri)."</td>";
	$h.="<td class='awc-nowrap'><div class='awc-chip'><span class='awc-chip-ico'><i class='".ico_nic."'></i></span><span>".htmlspecialchars((string)$outface)." <i class='".ico_arrow_right."'></i> <span class='awc-mono'>".htmlspecialchars((string)$BackendAddr)."</span> $backendname</span></div></td>";
	$h.="<td class='awc-nowrap awc-mono' style='text-align:right'>".htmlspecialchars((string)$bytes)."</td>";
	$h.="<td class='awc-nowrap awc-mono' style='text-align:right'>".htmlspecialchars((string)$time)."</td>";
	$h.="<td class='center'><span class='$badge'>$status</span></td>";
	$h.="</tr>";
	return $h;
}

function TERMINAISON($termination_state,$status_code=0):array{

    if($status_code>0){
        $t=new logfile_daemon();
        if($status_code>=200 && $status_code<300){
            $class="class='text-muted'";
            return array($t->codeToString($status_code),$class);
        }
        $class="class='text-danger'";
        return array($t->codeToString($status_code),$class);
    }


    $TERMINAISON_EXPLAIN["cD"]="{explain_HaProxycD}";
    $TERMINAISON_EXPLAIN["sD"]="{explain_HaProxysD}";
    $TERMINAISON_EXPLAIN["CD"]="{explain_HaProxyCD}";

    $TERMINAISON_WARN["cD"]="{client_timeout}";
    $TERMINAISON_WARN["sD"]="{timeout}";
    $TERMINAISON_WARN["CD"]="{client_terminated}";

    $TERMINAISON_SUCCESS["sD--"]="{success}";


    $TERMINAISON["----"]="{broken}";
    $TERMINAISON["CD--"]="{rejected}";
    $TERMINAISON["cH"]="{timeout}";
    $TERMINAISON["cR"]="{timeout}";
    $TERMINAISON["sC"]="{timeout}";

    $TERMINAISON["sH"]="{timeout}";
    $TERMINAISON["sQ"]="{timeout}";
    $TERMINAISON["CT"]="{aborted}";
    $TERMINAISON["CC"]="{aborted}";

    $TERMINAISON["CH"]="{aborted}";
    $TERMINAISON["CQ"]="{aborted}";
    $TERMINAISON["CR"]="{aborted}";
    $TERMINAISON["SH"]="{aborted}";
    $TERMINAISON["LR"]="{redirect}";
    $TERMINAISON["SC"]="{dropped}";
    $TERMINAISON["SD"]="{broken}";
    $TERMINAISON["PC"]="{max_connections}";
    $TERMINAISON["PD"]="{refused}";
    $TERMINAISON["PH"]="{refused}";
    $TERMINAISON["PR"]="{refused}";
    $TERMINAISON["PT"]="{refused}";
    $TERMINAISON["RC"]="{ressource_error}";

    if(isset($TERMINAISON_SUCCESS[$termination_state])){
        $class="class='text-muted'";
        return array($TERMINAISON_SUCCESS[$termination_state],$class);
    }
    if(isset($TERMINAISON[$termination_state])){
        $class="class='text-danger'";
        return array($TERMINAISON[$termination_state],$class);
    }
    if(isset($TERMINAISON_WARN[$termination_state])){
        $class="class='text-warning'";
        return array($TERMINAISON_WARN[$termination_state],$class);
    }
    $class="class='text-muted'";
    return array("{unknown}",$class);

}
