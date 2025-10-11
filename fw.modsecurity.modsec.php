<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["search-reports"])){search_reports();exit;}
if(isset($_GET["report-path-js"])){report_path_js();exit;}
if(isset($_GET["report-path-popup"])){report_path_popup();exit;}
if(isset($_GET["button"])){button_white();exit;}
if(isset($_GET["whitelist-js"])){whitelist_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["events"])){section_events();exit;}
if(isset($_GET["reports"])){section_reports();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom"])){zoom();exit;}

page();
function zoom_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=urlencode($_GET["zoom-js"]);
    $title=$tpl->_ENGINE_parse_body("{realtime_requests}::ZOOM");
    $tpl->js_dialog($title, "$page?zoom=yes&data=$data");
}
function zoom(){

    $data=unserialize(base64_decode($_GET["data"]));
    $html[]="<div class=ibox-content>";
    $html[]="<table class='table table table-bordered'>";
    foreach ($data as $key=>$val){
        $html[]="<tr>
		<td class=text-capitalize>$key:</td>
		<td><strong>$val</strong></td>
		</tr>";

    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html)."</table></div>";

}
function ifisright(){
    $users=new usersMenus();
    if($users->AsProxyMonitor){return true;}
    if($users->AsWebStatisticsAdministrator){return true;}
    if($users->AsWebSecurity){return true;}
    if($users->AsDnsAdministrator){return true;}
    if($users->AsFirewallManager){return true;}
}
function report_path_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=$_GET["report-path-js"];
    $title=$tpl->_ENGINE_parse_body("{report}");
    $tpl->js_dialog($title, "$page?report-path-popup=$data");
}
function report_path_popup(){
    $tpl=new template_admin();

    $data=base64_decode($_GET["report-path-popup"]);
    $fpath="/home/artica/modsecurity/$data";
    if(!is_file($fpath)){
        echo $tpl->div_warning("{no_such_file}||{modsec_report_not_found}");
        return false;
    }
    $content=@file_get_contents($fpath);
    $f[]=$tpl->field_textareacode("none",null,$content);
    echo $tpl->form_outside(basename($fpath),$f,null,null);
    return true;

}
function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array["{realtime}"]="$page?events=yes";
    $array["{reports}"]="$page?reports=yes";
    echo $tpl->tabs_default($array);
}
function section_events(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();

    if(!isset($_SESSION["WEBF_SEARCH"])){$_SESSION["WEBF_SEARCH"]="50 events";}

    $html[]="<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["WEBF_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
     <div id='table-loader-$t'></div>
    </div>
    
    <script>
    		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader-$t','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
    
</script>
    ";
    echo $tpl->_ENGINE_parse_body($html);
}

function section_reports(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();

    if(!isset($_SESSION["WEBR_SEARCH"])){$_SESSION["WEBR_SEARCH"]="50 events";}

    $html[]="<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["WEBR_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
     <div id='table-loader-$t'></div>
    </div>
    
    <script>
    		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader-$t','$page?search-reports='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
    
</script>
    ";
    echo $tpl->_ENGINE_parse_body($html);

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("Web Firewall {threats}",ico_eye,"null","$page?tabs=yes","waf-modsec");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}
function search_reports(){
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $GLOBALS["TPLZ"]=$tpl;
    $MAIN=$tpl->format_search_protocol($_GET["search-reports"]);
    if($MAIN["MAX"]>1500){$MAIN["MAX"]=1500;}
    $sock->getFrameWork("nginx.php?waf-modrep=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));
    $zdate=$tpl->_ENGINE_parse_body("{zDate}");
    $colspan=7;

    $html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>
        	<th>{report}</th>
        	<th>Phase</th>
        	<th>{domain}</th>
        	<th nowrap>{src_ip}</th>
            <th>{query}</th>
            <th>{allow}</th>
            
        </tr>
  	</thead>
	<tbody>
";

    $targetfile="/usr/share/artica-postfix/ressources/logs/modsec_audit.log.tmp";
    $cmdfile=$targetfile.".cmd";
    $data=explode("\n",@file_get_contents($targetfile));

    krsort($data);

    $severity_icon[0]="<span class='label label-danger'>{emergency}</span>";
    $severity_icon[1]="<span class='label label-danger'>{alert}</span>";
    $severity_icon[2]="<span class='label label-danger'>{critic}</span>";
    $severity_icon[3]="<span class='label label-danger'>{error}</span>";
    $severity_icon[4]="<span class='label label-warning'>{warning}</span>";
    $severity_icon[5]="<span class='label label-info'>{notice}</span>";
    $severity_icon[6]="<span class='label label'>Info</span>";


    foreach ($data as $line){
        $line=trim($line);
        if($line==null){continue;}
        $md=md5($line);
        if(!preg_match("#^(.+?)\s+([0-9]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:\s+(.+)#",$line,$re)){
            $html[]="<tr id='$md'>";
            $html[]="<td colspan=$colspan><span class='text-danger'>$line</span></td>";
            $html[]="</tr>";
            continue;
        }
        $Month=$re[1];
        $day=$re[2];
        $time=$re[3];
        $server_process=$re[4];
        $pid=$re[5];
        $FULL=$re[6];
        $method=null;
        $phase=2;
        $sArray=zexplode_line($re[6]);
        $domain=$sArray["hostname"];
        $srcip=$sArray["clientname"];
        $ruleid=$sArray["ruleid"];
        $rule_explain=official_rules($ruleid);
        if(isset($sArray["phase"])){$phase=$sArray["phase"];}
        $ruleidsrc=intval($ruleid);
        if(isset($severity_icon[$sArray["severity"]])){
            $severity=$severity_icon[$sArray["severity"]];
        }else{
            $severity=$sArray["severity"];
        }

        $srcdomain=$domain;
        $report=$sArray["report"];

        $query=urldecode($sArray["url"]);
        $color="rgb(191, 194, 196)";
        if(intval($sArray["severity"])<5){
            $color="black";
        }
        $stime="$Month $day $time";
        if(isset($sArray["created"])){
            $stime=$tpl->time_to_date($sArray["created"],true);
        }
        if(isset($sArray["method"])){$method=$sArray["method"];}

        $arrayZoom=array();
        $arrayZoom["{zDate}"]=$stime;
        $arrayZoom["{src_ip}"]=$srcip;
        $arrayZoom["path"]=$report;
        $arrayZoom["{rule}"]=$ruleid;
        $arrayZoom["{method}"]=$method;

        if($rule_explain<>null){
            $arrayZoom["{rulename}"]=$rule_explain;
        }
        $arrayZoom["{domain}"]=$domain;
        $arrayZoom["{query}"]=$query;
        if(isset($sArray["savereport"])) {
            if ($sArray["savereport"] == 1) {
                $arrayZoom["{report}"] = "{yes}";
            } else {
                $arrayZoom["{report}"] = "{no}";
            }
        }
        $strlenquery=strlen($query);

        if($strlenquery>80){
            $query=substr($query,0,77)."...";
        }
        $content_zoom=base64_encode(serialize($arrayZoom));
        $domainid=$tpl->NGINX_HOSTNAME_TO_ID($domain);

        if($domainid>0) {
            $jsdomain   = "Loadjs('fw.nginx.sites.modsecurity.php?serviceid=$domainid')";
            $domain     = $tpl->td_href($srcdomain,null,$jsdomain);
        }

        $rule_explain_text=null;
        if($rule_explain<>null){
            $rule_explain_text="<br><strong><small>
". $tpl->td_href($rule_explain,null,"Loadjs('fw.modsecurity.defrules.php?ruleid-js=$ruleid')")."</small></strong>";
        }

        $date=$tpl->td_href($stime,null,"Loadjs('$page?zoom-js=$content_zoom')");
        $srcip=$tpl->td_href($srcip,null,"Loadjs('$page?zoom-js=$content_zoom')");
        $ruleid=$tpl->td_href($ruleid,null,"Loadjs('$page?zoom-js=$content_zoom')");
        $sdomain=urlencode($srcdomain);
        $squery=urlencode($query);

        $allow=$tpl->icon_run("Loadjs('fw.modsecurity.white.php?ruleid-js=0&domain=$sdomain&query=$squery&rid=$ruleidsrc')");
        if($ruleidsrc<600){
            $allow="-";
        }

        $html[]="<tr id='$md'>
				<td style='color:$color' width=1% nowrap>$date</td>
				<td style='color:$color' width=1% nowrap>$severity - #$ruleid</td>
				<td style='color:$color' width=1% nowrap>$phase</td>
				<td style='color:$color' width=1% nowrap>$domain <strong>($method)</strong></span></td>
				<td style='color:$color' width=1% nowrap>$srcip</span></td>
                <td style='color:$color' width=99% nowrap>$query$rule_explain_text</span></td>
                <td style='color:$color'style='width:1%;' nowrap class='center'>$allow</span></td>                      
                </tr>";

    }
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='$colspan'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</tbody></table>";
    $html[]="<div>".@file_get_contents($cmdfile)."</div>
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);


}

function search(){
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $GLOBALS["TPLZ"]=$tpl;
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    if($MAIN["MAX"]>1500){$MAIN["MAX"]=1500;}
    $sock->getFrameWork("nginx.php?waf-modsec=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));
    $zdate=$tpl->_ENGINE_parse_body("{zDate}");
    $colspan=7;

    $html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>
        	<th>{report}</th>
        	<th>{domain}</th>
        	<th nowrap>{src_ip}</th>
			<th nowrap>{http_status_code}</th>
            <th>Proto</th>
            <th>{query}</th>
            <th>{size}</th>
            <th>{allow}</th>
            
        </tr>
  	</thead>
	<tbody>
";

    $targetfile="/usr/share/artica-postfix/ressources/logs/modsec_audit.log.tmp";
    $cmdfile=$targetfile.".cmd";
    $data=explode("\n",@file_get_contents($targetfile));

    krsort($data);



    foreach ($data as $line){

        if(!preg_match("#^(.+?)\s+(.+?)\s+(.+?)\s+\[(.+?)\]\s+\"(.+?)\"\s+([0-9]+)\s+([0-9]+)\s+(.+?)\s+\"(.+?)\"\s+([0-9]+)\s+(.+?)\s+(.+?)\s+([0-9]+)\s+([0-9\.]+)\s+md5:(.+?)$#",$line,$re)){
            $md=md5($line);
            $html[]="<tr id='$md'>";
            $html[]="<td colspan=$colspan>$line</td>";
            $html[]="</tr>";
            continue;
        }

        $domain=$re[1];
        $srcip=$re[2];
        $user=$re[3];
        $date=$re[4];
        $query=$re[5];
        $HTTP_CODE=$re[6];
        $bytes=$re[7];
        $referrer1=$re[8];
        $UserAgent=$re[9];
        $timestamp=$re[10];
        $referer=$re[11];
        $report_path=$re[12];
        $level=$re[13];
        $timestamp2=$re[14];
        $md5=$re[15];
        $proto="&nbsp;";
        $color="rgb(191, 194, 196)";
        $arrayZoom=array();
        $arrayZoom["{zDate}"]=$date;
        $report_icon=null;
        $srcdomain=$domain;


        $arrayZoom["{src_ip}"]=$srcip;
        $arrayZoom["{user}"]=$user;
        $arrayZoom["path"]=$report_path;
        $arrayZoom["{useragent}"]=$UserAgent;
        $arrayZoom["{size}"]=$referer;
        $arrayZoom["Referer"]=$bytes;
        $arrayZoom["{http_status_code}"]=$HTTP_CODE;


        $date=$tpl->time_to_date($timestamp,true);
        if($HTTP_CODE==403){
            $color="#ed5565";
        }
        if($HTTP_CODE==405){
            $color="#ed5565";
        }
        if($HTTP_CODE==504){
            $color="#f7a54a";
        }
        if($HTTP_CODE==400){
            $color="#f7a54a";
        }

        $HTTP_CODE=http_codes_to_text($HTTP_CODE)." ($HTTP_CODE)";
        if(preg_match("#^([A-Z]+)\s+(.+)\s+HTTP\/([0-9\.]+)#",$query,$re)){
            $proto=$re[1]." v{$re[3]}";
            $query=$re[2];

        }
        $arrayZoom["{proto}"]="{$re[1]} HTTP/{$re[3]}";
        $arrayZoom["{domain}"]=$domain;
        $arrayZoom["{query}"]=$query;
        $strlenquery=strlen($query);


        if($strlenquery>80){
            $query=substr($query,0,77)."...";
        }

        $size="{$bytes}bytes";
        if($size>1024){
            $size=FormatBytes($bytes/1024);
        }

        $content_zoom=base64_encode(serialize($arrayZoom));
        $domain=$tpl->td_href($domain,null,"Loadjs('$page?zoom-js=$content_zoom')");
        $date=$tpl->td_href($date,null,"Loadjs('$page?zoom-js=$content_zoom')");
        $srcip=$tpl->td_href($srcip,null,"Loadjs('$page?zoom-js=$content_zoom')");
        $HTTP_CODE=$tpl->td_href($HTTP_CODE,null,"Loadjs('$page?zoom-js=$content_zoom')");

        if(is_file("$report_path")){
            if(preg_match("#artica\/modsecurity\/(.+)#",$report_path,$re)){
                $report_path=$re[1];
            }
            $report_icon=$tpl->icon_excel("Loadjs('$page?report-path-js=".base64_encode($report_path)."')");
        }
        $sdomain=urlencode($srcdomain);
        $squery=urlencode($query);

        $allow=$tpl->icon_run("Loadjs('fw.modsecurity.white.php?ruleid-js=0&domain=$sdomain&query=$squery')");

        $html[]="<tr id='$md'>
				<td style='color:$color' width=1% nowrap>$date</td>
				<td style='color:$color' width=1% nowrap>$report_icon</td>
				<td style='color:$color' width=1% nowrap>$domain</span></td>
				<td style='color:$color' width=1% nowrap>$srcip</span></td>
                <td style='color:$color' width=1% nowrap>$HTTP_CODE</td>
                <td style='color:$color' width=1% nowrap>$proto</td>
                <td style='color:$color' width=99% nowrap>$query</span></td>
                <td style='color:$color' width=1% nowrap>$size</span></td>
                <td style='color:$color' width=1% nowrap>$allow</span></td>                      
                </tr>";

    }
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='$colspan'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</tbody></table>";
    $html[]="<div>".@file_get_contents($cmdfile)."</div>
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);



}
function zexplode_line($line){
    $MAIN=explode(";",$line);
    $sArray=array();
    foreach ($MAIN as $xline){
        if(strpos($xline,":")==0){continue;}
        $tb=explode(":",$xline);
        $sArray[trim($tb[0])]=$tb[1];
    }
    return $sArray;
}

function official_rules($ruleid){
    if(isset($GLOBALS["official_rules"][$ruleid])){return $GLOBALS["official_rules"][$ruleid];}
    $q=new lib_sqlite("/home/artica/SQLITE/modsecurity_rules.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rules WHERE ID=$ruleid");
    if($ligne["rulename"]<>null){
        $GLOBALS["official_rules"][$ruleid]=$ligne["rulename"];
        return $GLOBALS["official_rules"][$ruleid];
    }
}


function http_codes_to_text($code):string{
    $HTTP_CODES[200]="Success";
    $HTTP_CODES[201]="Created ";
    $HTTP_CODES[202]="Accepted ";
    $HTTP_CODES[203]="Non-Authoritative Information ";
    $HTTP_CODES[204]="No Content ";
    $HTTP_CODES[205]="Reset Content ";
    $HTTP_CODES[206]="Partial Content ";
    $HTTP_CODES[207]="Multi-Status ";
    $HTTP_CODES[208]="Already Reported ";
    $HTTP_CODES[226]="IM Used ";
    $HTTP_CODES[300]="Multiple Choices ";
    $HTTP_CODES[301]="Moved Permanently ";
    $HTTP_CODES[302]="Found ";
    $HTTP_CODES[303]="See Other ";
    $HTTP_CODES[304]="Not Modified ";
    $HTTP_CODES[305]="Use Proxy ";
    $HTTP_CODES[306]="(Unused) ";
    $HTTP_CODES[307]="Temporary Redirect ";
    $HTTP_CODES[308]="Permanent Redirect ";
    $HTTP_CODES[400]="Bad Request ";
    $HTTP_CODES[401]="Unauthorized ";
    $HTTP_CODES[402]="Payment Required ";
    $HTTP_CODES[403]="Forbidden ";
    $HTTP_CODES[404]="Not Found ";
    $HTTP_CODES[405]="Method Not Allowed ";
    $HTTP_CODES[406]="Not Acceptable ";
    $HTTP_CODES[407]="Proxy Authentication Required ";
    $HTTP_CODES[408]="Request Timeout ";
    $HTTP_CODES[409]="Conflict ";
    $HTTP_CODES[410]="Gone ";
    $HTTP_CODES[411]="Length Required ";
    $HTTP_CODES[412]="Precondition Failed ";
    $HTTP_CODES[413]="Payload Too Large ";
    $HTTP_CODES[414]="URI Too Long ";
    $HTTP_CODES[415]="Unsupported Media Type ";
    $HTTP_CODES[416]="Range Not Satisfiable ";
    $HTTP_CODES[417]="Expectation Failed ";
    $HTTP_CODES[421]="Misdirected Request ";
    $HTTP_CODES[422]="Unprocessable Entity ";
    $HTTP_CODES[423]="Locked ";
    $HTTP_CODES[424]="Failed Dependency ";
    $HTTP_CODES[425]="Too Early ";
    $HTTP_CODES[426]="Upgrade Required ";
    $HTTP_CODES[427]="Unassigned ";
    $HTTP_CODES[428]="Precondition Required ";
    $HTTP_CODES[429]="Too Many Requests ";
    $HTTP_CODES[430]="Unassigned ";
    $HTTP_CODES[431]="Request Header Fields Too Large ";
    $HTTP_CODES[451]="Unavailable For Legal Reasons ";
    $HTTP_CODES[500]="Internal Server Error ";
    $HTTP_CODES[501]="Not Implemented ";
    $HTTP_CODES[502]="Bad Gateway ";
    $HTTP_CODES[503]="Service Unavailable ";
    $HTTP_CODES[504]="Gateway Timeout ";
    $HTTP_CODES[505]="HTTP Version Not Supported ";
    $HTTP_CODES[506]="Variant Also Negotiates ";
    $HTTP_CODES[507]="Insufficient Storage ";
    $HTTP_CODES[508]="Loop Detected ";
    $HTTP_CODES[509]="Unassigned ";
    $HTTP_CODES[510]="Not Extended ";
    $HTTP_CODES[511]="Network Authentication Required ";
    if(!isset($HTTP_CODES[$code])){return "";}
    return trim($HTTP_CODES[$code]);

}