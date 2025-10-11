<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__) . "/ressources/class.logfile_daemon.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["explain"])){explain_js();exit;}
if(isset($_GET["explain-popup"])){explain_popup();exit;}
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
	$t=time();
	if(!isset($_SESSION["PULSEREVERSE_CNX"])){$_SESSION["PULSEREVERSE_CNX"]="";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["PULSEREVERSE_CNX"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
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
	$.address.state('/');
	$.address.value('/hacluster-connections');
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

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_HACLUSTER}",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){

	$sock=new sockets();
	$tpl=new template_admin();
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));


    $q          = new lib_sqlite("/home/artica/SQLITE/PulseReverse.db");

    $sql="SELECT ID,backendname FROM backends";
    $results = $q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $uri="fw.pulsereverse.backends.php?backend-js=$ID&function=blur";
        $backendname = $tpl->td_href($ligne["backendname"], "", $uri);
        $MAIN_BACKENDS[$ID] = $backendname;
    }
    $results=$q->QUERY_SQL("SELECT ID,servicename FROM `connectors`");
    foreach ($results as $index=>$ligne) {
        $CONNECTORS[$ligne["ID"]] = $ligne["servicename"];
    }


    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}
    $EndPoint="/pulsereverse/queries/$ss/$MAX";

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);





	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$html[]=$tpl->_ENGINE_parse_body("
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>{client}</th>
        	<th>{website}</th>
        	<th>{service}</th>
        	<th>{backend}</th>
        	<th>{size}</th>
        	<th>{duration}</th>
        	<th>{status}</th>
        </tr>
  	</thead>
	<tbody>
");
    $page=CurrentPageName();
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }





    $icoArrow=ico_arrow_right;
    $icoIface=ico_nic;
    foreach ($json->Logs as $line) {
        $line = trim($line);
        $line=str_replace("\u003c","<",$line);
        if(!preg_match("#rt-requests=<(.+?)>rt-requests#", $line,$re)){
            continue;
        }
        $jsonLine=json_decode($re[1]);

        if (json_last_error()> JSON_ERROR_NONE) {
            echo "<strong style='color:red;'>$re[1]</strong>";
            VERBOSE("[$re[1]] ".json_last_error_msg(),__LINE__);
           continue;
        }



        $pid=null;
        $client=$jsonLine->client_ip;
        $frontend_name=$jsonLine->frontend_name_transport;
        $status=null;

        $queue_ms=$jsonLine->Tw;
        $backend_ms=$jsonLine->Tc;;
        $class=null;
        $ID=null;

        $URI_column=null;
        $PROTO=$jsonLine->http_method;
        $bytes=$jsonLine->bytes_read;
        $FTime=date("Y-m-d H:i:s",$jsonLine->timestamp);
        $backendname=$tpl->icon_nothing();
        $termination_state=$jsonLine->termination_state;
        $hostname=$jsonLine->hostname;
        $http_request_uri_without_query=$jsonLine->http_request_uri_without_query;
        $server_name=$jsonLine->server_name;
        if(preg_match("#^Backend([0-9]+)$#",$server_name,$re)){
            if(isset($MAIN_BACKENDS[$re[1]])) {
                $backendname = $MAIN_BACKENDS[$re[1]];
            }
        }
        $status_code=intval($jsonLine->status_code);
        if($PROTO=="TCP"){
            $http_request_uri_without_query="tcp://$http_request_uri_without_query";
        }

        $outface=$jsonLine->outface;
        $BackendAddr=$jsonLine->backend_address.":".$jsonLine->backend_port;
        $total_session_time_ms=$jsonLine->total_session_time_ms;
        $frontend_name=$jsonLine->frontend_name;
        if(preg_match("#frontend-([0-9]+)#",$frontend_name,$re)){
            $frontend_name=$CONNECTORS[$re[1]];
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

        if(strlen($http_request_uri_without_query)>0){
            if(preg_match("#^(http|https)://#",$http_request_uri_without_query,$re)){
                $urls=parse_url($http_request_uri_without_query);
                $hostname=$urls["host"];
                if(isset($urls["path"])) {
                    $http_request_uri_without_query = $urls["path"];
                }
            }
            $hostname="$hostname<br><small>$http_request_uri_without_query</small>";
        }
        $lineBackends="<i class='$icoIface'></i>&nbsp;$outface&nbsp;<i class='$icoArrow'></i>&nbsp;$BackendAddr&nbsp;<small>($backendname)</small>";
        if( (strlen($outface)<3) && strlen($BackendAddr)<3){
            $lineBackends="";
        }

		$html[]="<tr>
				<td $class style='width:1%' nowrap>$FTime</td>
				<td $class style='width:1%' nowrap>$client</td>
				<td $class style='width:1%' nowrap>$hostname</td>
				<td $class style='width:1%' nowrap>$frontend_name</td>
				<td $class style='width:1%' nowrap>$lineBackends</td>
				<td $class style='width:1%' nowrap>$bytes</td>
				<td $class style='width:1%' nowrap>$time</td>	
				<td $class style='width:1%' nowrap>$status</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";

	echo $tpl->_ENGINE_parse_body($html);
	
	
	
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
