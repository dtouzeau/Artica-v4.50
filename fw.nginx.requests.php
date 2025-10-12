<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__).'/ressources/class.modsectools.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["search-file-js"])){search_file_js();exit;}
if(isset($_GET["proxy_upstream_name-search"])){proxy_upstream_name_search();exit;}
if(isset($_GET["form"])){search_form();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["search-opts-js"])){search_opts_js();exit;}
if(isset($_GET["search-opts-popup"])){search_opts_popup();exit;}
if(isset($_POST["proxy_upstream_name"])){search_opts_save();exit;}
if(isset($_GET["filter-bysite"])){filter_bysite();exit;}
if(isset($_GET["harmptabs"])){harmptabs();exit;}
if(isset($_GET["proxy_upstream_name-js"])){proxy_upstream_name_js();exit;}
if(isset($_GET["proxy_upstream_name-popup"])){proxy_upstream_name_popup();exit;}
if(isset($_GET["save-field"])){saveField();exit;}
if(isset($_GET["jsScanIPInfos"])){jsScanIPInfos();exit;}
if(isset($_GET["jsFillIPInfos"])){jsFillIPInfos();exit;}

page();


function search_file_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FileID=intval($_GET["search-file-js"]);
    $q      = new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM proxy_search WHERE ID='$FileID'");
    $datefrom=$ligne["datefrom"];
    $timefrom=$ligne["timefrom"];
    $dateto=$ligne["dateto"];
    $timeto=$ligne["timeto"];
    return $tpl->js_dialog5("{search}: $datefrom $timefrom - $dateto $timeto","$page?form=yes&fileid=$FileID",1350);

}

function zoom_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $encoded=$_GET["zoom-js"];
    $decoded=json_decode(base64_decode($encoded));
    return $tpl->js_dialog4($decoded->host,"$page?zoom-popup=$encoded",1050);
}
function search_opts_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog4("{options}","$page?search-opts-popup=yes&function=$function");
}
function proxy_upstream_name_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["id"];
    $suffix_form=$_GET["suffix-form"];
    return $tpl->js_dialog10("{servicename}","$page?proxy_upstream_name-popup=yes&id=$id&suffix-form=$suffix_form");
}
function proxy_upstream_name_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["id"];
    $suffix_form=$_GET["suffix-form"];
    echo $tpl->search_block($page, null, null, null, "&proxy_upstream_name-search=yes&id=$id&suffix-form=$suffix_form");
    return true;
}
function proxy_upstream_name_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["id"];
    $search=$_GET["search"];
    $search="*".$search."*";
    $search=str_replace("**","*",$search);
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);
    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT ID,servicename,hosts FROM nginx_services WHERE enabled=1 AND (servicename LIKE '$search' OR hosts LIKE '$search' ) ORDER BY zorder";
    $results=$q->QUERY_SQL($sql);
    $html[]="<table style='width:100%' class='table'>";
    foreach ($results as $ligne) {
        $ID=$ligne["ID"];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $servicename=$ligne["servicename"];
        $key=base64_encode("$servicename-$ID");
        $prc1="style='width:1%;text-align:right;padding-right:10px;padding-left:10px;vertical-align:top' nowrap";

        $Zhosts=str_replace("||",", ",$ligne["hosts"]);
        $idF=md5(serialize($ligne));
        $js[]="function Main$idF(){";
        $js[]="document.getElementById('$id').value=base64_decode('$key');";
        $js[]="dialogInstance10.close();";
        $js[]="}";

        $button="<button class='btn btn-primary btn-xs' OnClick=\"Main$idF()\">{select}</button>";


        $html[]="<tr style='height: 43px'>";
        $html[]="<td $prc1>{sitename}:</td>";
        $html[]="<td ><strong>$servicename</strong><br><i>$Zhosts</i></strong></td>";
        $html[]="<td $prc1>$button</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="<script>";
    $html[]=@implode("\n",$js);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
return true;

}

function harmptabs():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $sql="SELECT * FROM hamrp WHERE groupid={$_SESSION["HARMPID"]} ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);

    $array=array();

    foreach ($results as $ligne){
        $uuid=$ligne["uuid"];
        $nodename=$ligne["nodename"];
        $array[$nodename] = "$page?form=yes&uuid=$uuid";
    }


    echo $tpl->tabs_default($array);
    return true;
}
function zoom_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Unencoded=base64_decode($_GET["zoom-popup"]);

    $decoded=json_decode($Unencoded);
    $src=$decoded->src_ip;

    $modsectool=new modesctools();
    $modsectool->hostinfo($src);

    $EnableNginxFW=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"));


    $tpl->table_form_field_text("{ipaddr}","$src <br><span style='text-transform: initial;font-size:12px'>$decoded->hostname</span>",ico_server);
    if($decoded->continent<>null) {
        $tpl->table_form_field_text("{continent}", $decoded->continent, ico_earth);
    }
    if($decoded->country_name<>null) {
        $tpl->table_form_field_text("{country}", $decoded->country_name, ico_location);
    }
    if($decoded->city<>null) {
        $tpl->table_form_field_text("{city}", $decoded->city, ico_city);
    }
    if($decoded->organization<>null) {
        $tpl->table_form_field_text("{ISP}", $decoded->organization, ico_networks);
    }


    $ficheClient_text=$tpl->table_form_compile();

    $srcencoded=urlencode($src);
    $html[]="<table style='width:100%;border-bottom:2px solid #CCCCCC;'>";
    $html[]="<tr>";
    $html[]="<td width='1%' nowrap='' style='vertical-align: top'>$modsectool->Flag256</td>";
    $html[]="<td style='width:99%;padding-left:10px;vertical-align: top'>$ficheClient_text";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan=2><code></code></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan=2>&nbsp;</td>";
    $html[]="</tr>";
    $html[]="</table>";


    if($EnableNginxFW==1) {
        //$bts[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('fw.wordpress.firewall.inbound.php?autoadd=$srcencoded')\">
   //             <i class='fa-solid fa-shield-plus'></i> {block} $src</label>";

    }
    $html[]="<table style='width:100%;border-bottom:2px solid #CCCCCC;'>";
    $html[]="<tr>";
    $html[]="<td width='49%' nowrap='' style='vertical-align: top'>";
    $tpl->table_form_section("{reverse_proxy}");
    $tpl->table_form_field_text("{website}", $decoded->host, ico_earth);
    $tpl->table_form_field_text("{http_status_code}", $decoded->status, ico_proto);
    $tpl->table_form_field_text("{protocol}", "$decoded->protocol/$decoded->http_method", ico_proto);
    $tpl->table_form_field_text("{response_time}", $decoded->response_time, ico_timeout);
    $tpl->table_form_field_text("{size}", FormatBytes($decoded->body_bytes_sent/1024), ico_weight);



    $html[]=$tpl->table_form_compile();
    $html[]="<td width='2%' nowrap='' style='vertical-align: middle !important;padding:5px'>";
    $html[]="<i class='".ico_arrow_right." fa-10x'></i>";
    $html[]="</td>";
    $html[]="<td width='49%' nowrap='' style='vertical-align: top'>";

    $upstream_response_length=0;
    if(property_exists($decoded,"upstream_response_length")) {
        $upstream_response_length=intval($decoded->upstream_response_length);
    }

    $tpl->table_form_section("{backend}");
    $tpl->table_form_field_text("{backend}", $decoded->upstream_addr, ico_server);
    $tpl->table_form_field_text("{http_status_code}", $decoded->upstream_status, ico_proto);
    $tpl->table_form_field_text("{protocol}", "$decoded->protocol/$decoded->http_method", ico_proto);
    $tpl->table_form_field_text("{response_time}", $decoded->upstream_response_time, ico_timeout);
    if($upstream_response_length>0) {
        $tpl->table_form_field_text("{size}", FormatBytes($upstream_response_length / 1024), ico_weight);
    }
    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $tpl->table_form_section("{request}");
    $path=$tpl->td_href($decoded->path,$decoded->request,"");
    $tpl->table_form_field_text("{url}/{cache}","<span style='text-transform: initial'>$path</span>($decoded->cache_status)", ico_link);
    $tpl->table_form_field_text("{size}", FormatBytes($decoded->body_bytes_sent/1024), ico_weight);
    $tpl->table_form_field_text("{referer}","<span style='text-transform: initial'>$decoded->http_referer</span>" , ico_link);
    $tpl->table_form_field_text("{http_user_agent}","<span style='text-transform: initial'>$decoded->user_agent</span>",ico_html);


    $html[]=$tpl->table_form_compile();


    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $skiped["src"]=true;
    $skiped["flag"]=true;
    $skiped["host"]=true;
    $skiped["subdivisions"]=true;
    $skiped["latitude"]=true;
    $skiped["longitude"]=true;
    $skiped["scheme"]=true;
    $skiped["continentCode"]=true;
    $skiped["asnNumber"]=true;
    $skiped["remote_addr"]=true;
    $skiped["timestamp"]=true;
    $skiped["nginx_version"]=true;
    $skiped["source"]=true;
    $Trans["time_local"]="time";
    $Trans["status"]="http_status_code";
    $Trans["upstream_addr"]="{remote_server} {ipaddr}";
    $Trans["proxy_upstream_name"]="servicename";
    $Trans["user_agent"]="http_user_agent";
    $Trans["upstream_status"]="{remote_server} {http_status_code}";
    $Trans["upstream_response_time"]="{remote_server} {response_time}";
    $Trans["uri_path"]="path";
    $Trans["http_method"]="protocol";
    $Trans["body_bytes_sent"]="size";
    $Trans["cache_status"]="{cached}/{not_cached}";
    $Trans["bytes_out"]="uploaded";
    $Trans["bytes_in"]="downloaded";
    $Trans["request_time"]="{query} {duration}";
    $Trans["upstream_response_length"]="{size} {response} {remote_server}";
    $Trans["http_referer"]="referer";
    
    $html[]="<div style='margin-top:20px;margin-left:30px'>";
    $html[]="<table style='width:100%'>";
    /*
    foreach ($decoded as $key=>$value){
        echo "<li>$key === $value</li>";
        if(isset($skiped[$key])){continue;}

        if(!is_array($value)) {
            $value = trim($value);
        }
        if($value==null){continue;}
        if(isset($Trans[$key])){
            $key=$Trans[$key];
        }
        if(strpos("  $key","}")==0){
            $key="{{$key}}";
        }

        $html[]="<tr>";
        $html[]="<td width='1%' nowrap=''><strong>$key</strong>:</td>";
        $html[]="<td style='width:99%;padding-left:10px'>$value</td>";
        $html[]="</tr>";
    }
    */
    $html[]="</table></div>";
    echo $tpl->_ENGINE_parse_body($html);
return true;
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}

function search_opts_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $logfile_daemon=new logfile_daemon();
    $CODES=$logfile_daemon->codeToString(null,true);
    $zCODES=array();
    $zCODES[""]="{all}";
    $zCODES["3[0-9][0-9]"]="[3xx]: {all}";
    $zCODES["4[0-9][0-9]"]="[4xx]: {all}";
    $zCODES["496"]="[496]: {certificate}";
    $zCODES["50[0-9]"]="[50x]: {all}";
    foreach ($CODES as $Code=>$Name){
        $zCODES[$Code]="[$Code]: $Name";
    }

    $PROTOS["GET"]="GET";
    $PROTOS["PUT"]="PUT";
    $PROTOS["POST"]="POST";
    $PROTOS["DELETE"]="DELETE";
    $PROTOS["PATCH"]="PATCH";
    $PROTOS["HEAD"]="HEAD";
    $PROTOS["OPTIONS"]="OPTIONS";
    $PROTOS["TRACE"]="TRACE";
    $PROTOS["CONNECT"]="CONNECT";



    if(!isset($_SESSION["NGINXSEARCH"]["status"])){
        $_SESSION["NGINXSEARCH"]["status"]="";
    }

    $form[]=$tpl->field_text_button("proxy_upstream_name","{server_name}",$_SESSION["NGINXSEARCH"]["proxy_upstream_name"],null,"{edit}");
    $form[]=$tpl->field_array_hash($zCODES,"status","{http_status_code}",$_SESSION["NGINXSEARCH"]["status"]);
    $form[]=$tpl->field_array_hash($PROTOS,"http_method","{method}",$_SESSION["NGINXSEARCH"]["http_method"]);
    $form[]=$tpl->field_ipaddr("remote_addr","{src}",$_SESSION["NGINXSEARCH"]["remote_addr"]);
    $form[]=$tpl->field_text("user_agent","{http_user_agent}",$_SESSION["NGINXSEARCH"]["user_agent"]);
    $form[]=$tpl->field_text("request","{request}",$_SESSION["NGINXSEARCH"]["request"]);

    $js="dialogInstance4.close();$function()";
    echo $tpl->form_outside("{search}",$form,null,"{save}",$js);

}
function search_opts_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["NGINXSEARCH"]=$_POST;
    return true;
}

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html=$tpl->page_header("{APP_NGINX}: {requests}",
        ico_eye,"{APP_NGINX_REQUESTS_EXPLAIN}","$page?form=yes","nginx-requestss",null,false,"div-nginx-requests"
    );

    if($_SESSION["HARMPID"]>0){
        $html=$tpl->page_header("{APP_NGINX}: {requests}",
            ico_eye,"{APP_NGINX_REQUESTS_EXPLAIN}","$page?harmptabs=yes","nginx-requestss",null,false,"div-nginx-requests"
        );

    }

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {APP_NGINX}: {requests}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}
function search_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $urltoadd="";
    $fileid=0;
    if(isset($_GET["fileid"])){
        $fileid=intval($_GET["fileid"]);-
        $urltoadd="&fileid=$fileid";

    }
    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT ID,servicename FROM nginx_services WHERE enabled=1 ORDER BY servicename";
    $results=$q->QUERY_SQL($sql);

    if(!isset($_SESSION["NGINXSEARCH"])){
        $Key="SearchNginx{$_SESSION["uid"]}";
        $data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($Key);
        if(!is_null($data)){
            $unser=unserialize($data);
            if($unser){
                if(is_array($unser)) {
                    $_SESSION["NGINXSEARCH"] = $unser;
                }
            }
        }
    }



    $proxy_upstream_name="";
    $status_code_saved="";

    if(isset($_SESSION["NGINXSEARCH"]["status"])){
        $status_code_saved=urlencode($_SESSION["NGINXSEARCH"]["status"]);
    }
    if(isset($_SESSION["NGINXSEARCH"]["proxy_upstream_name"])){
        $proxy_upstream_name=$_SESSION["NGINXSEARCH"]["proxy_upstream_name"];
    }


    $logfile_daemon=new logfile_daemon();
    $CODES=$logfile_daemon->codeToString(null,true);
    $zCODES=array();
    $zCODES["3[0-9][0-9]"]="[3xx]: {all}";
    $zCODES["4[0-9][0-9]"]="[4xx]: {all}";
    $zCODES["496"]="[496]: {certificate}";
    $zCODES["50[0-9]"]="[50x]: {all}";
    foreach ($zCODES as $Code=>$Name){
        $CodeVal=urlencode($Code);
        $Name=$tpl->_ENGINE_parse_body($Name);
        $AzCODES["$CodeVal"]=$Name;
    }
    foreach ($CODES as $num=>$text){
        $AzCODES[$num]="$num $text";
    }
    $available=explode(",","GET,HEAD,POST,OPTIONS,PUT,PATCH,DELETE,CHECKOUT,COPY,DELETE,LOCK,MERGE,MKACTIVITY,MKCOL,MOVE,PROPFIND,PROPPATCH,PUT,UNLOCK,TRACE,CONNECT");

    foreach ($available as $text){
        $AzCODES[$text]=$text;
    }
    $AArry=array();

    foreach ($results as $index=>$ligne){
        $servicename=$ligne["servicename"];
        $ID=$ligne["ID"];
        $zids[$ID]=$servicename;
        $Key="$servicename-$ID";
        $AArry[$Key]=$servicename;

    }


    if($fileid==0) {
        $ttlocal="";
        if(isset($_SESSION["NGINXSEARCH"]["time_local"])){
            $ttlocal=$_SESSION["NGINXSEARCH"]["time_local"];
        }
        $LINES[] = $tpl->field_text_online("time_local", "{time}",$ttlocal, 150, "$page?save-field=time_local&value=%s$urltoadd");
    }
    $LINES[]=$tpl->DropDownMultiple($AzCODES,$status_code_saved,"{http_status_code}","$page?save-field=status&value=%s$urltoadd",380);

    $LINES[]=$tpl->field_text_online("remote_addr","0.0.0.0",$_SESSION["NGINXSEARCH"]["remote_addr"],150,"$page?save-field=remote_addr&value=%s");

    if($fileid==0) {
        $LINES[] = $tpl->DropDown($AArry, $proxy_upstream_name, "{sitename}", "$page?save-field=proxy_upstream_name&value=%s$urltoadd");
    }


    if(!isset($_SESSION["NGINXSEARCH"]["max_records"])){
    $_SESSION["NGINXSEARCH"]["max_records"]=300;
}
    $ico_help=ico_support;
    $js_help="s_PopUpFull('https://wiki.articatech.com/reverse-proxy/realtime-events','1024','900');";

    $LINES[]=$tpl->field_text_online("uri_path","{request}",$_SESSION["NGINXSEARCH"]["uri_path"],200,"$page?save-field=uri_path&value=%s$urltoadd");
    $LINES[]=$tpl->field_text_online("user_agent","{http_user_agent}",$_SESSION["NGINXSEARCH"]["user_agent"],250,"$page?save-field=user_agent&value=%s$urltoadd");
    $LINES[]=$tpl->field_text_online("max_records","{records}",$_SESSION["NGINXSEARCH"]["max_records"],120,"$page?save-field=max_records&value=%s$urltoadd");

    if($fileid==0) {
        $LINES[] = "<button class='btn btn-primary btn-xs' type='button' OnClick=\"LoadAjax('nginx-search-results','$page?search=')\" style='height:34px'>Go</button>";
        $LINES[] = "<button class='btn btn-default btn-circle' type='button' OnClick=\"$js_help\"><i class='$ico_help'></i></button>";
    }

    echo "<div style='margin-top:5px;margin-bottom:5px'>&nbsp;</div>";
    echo @implode("&nbsp;&nbsp;",$LINES);
    echo "</div>";
    echo "<div id='nginx-search-results' style='margin-top:25px'></div>;";
    echo "<script>LoadAjax('nginx-search-results','$page?search=$urltoadd');</script>";
    return true;
}

function saveField(){
    $page=CurrentPageName();
    $field=$_GET["save-field"];
    $fileid=0;
    if(isset($_GET["fileid"])){
        $fileid=$_GET["fileid"];
    }
    $value=urldecode($_GET["value"]);
    if(is_null($value)){$value="";}
    unset( $_SESSION["NGINXSEARCH"]["request"]);
//fileid
    if(strlen($value)==0 OR is_null($value)){
        if(isset( $_SESSION["NGINXSEARCH"][$field])){
            unset( $_SESSION["NGINXSEARCH"][$field]);
        }
    }else{
        $_SESSION["NGINXSEARCH"][$field]=$value;
    }

    $Key="SearchNginx{$_SESSION["uid"]}";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($Key,serialize($_SESSION["NGINXSEARCH"]));
    header("content-type: application/x-javascript");
    echo "LoadAjax('nginx-search-results','$page?search=&fileid=$fileid');";
}

function filter_bysite(){
    header("content-type: application/x-javascript");
    $key=$_GET["filter-bysite"];
    $function=$_GET["function"];
    $servname=base64_encode($_GET["srvname"]);
    $_SESSION["NGINXSEARCH"]["proxy_upstream_name"]=$key;

    $f[]="if(document.getElementById('SearchBlockDropDownTitle')){";
    $f[]="\tdocument.getElementById('SearchBlockDropDownTitle').innerHTML=base64_decode('$servname');";
    $f[]="}";
    $f[]="$function();";
    echo @implode("\n",$f);
}

function search():bool{
    $tpl=new template_admin();
    clean_xss_deep();
    if(!isset($_GET["search"])){$_GET["search"]="";}
    $fileid=0;
    $index=0;
    if(isset($_GET["fileid"])){
        $fileid=intval($_GET["fileid"]);
    }

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $line=base64_encode(serialize($MAIN));
    $proxy_upstream_name=null;
    if(!isset($_SESSION["NGINXSEARCH"])){$_SESSION["NGINXSEARCH"]=array();}
    $SearchArray=$_SESSION["NGINXSEARCH"];
    if(isset($_SESSION["NGINXSEARCH"]["proxy_upstream_name"])){
        $proxy_upstream_name=$_SESSION["NGINXSEARCH"]["proxy_upstream_name"];
    }else{
        VERBOSE("SESSSION[NGINXSEARCH][proxy_upstream_name]=UNKNOWN",__LINE__);
    }


    if(is_null($proxy_upstream_name)){
       echo $tpl->div_warning("{select_www_requests_search}");
        return true;
    }

    unset($SearchArray["proxy_upstream_name"]);
    $opts=urlencode(base64_encode(serialize($SearchArray)));

    if(preg_match("#-([0-9]+)$#",$proxy_upstream_name,$re)){
        $index=$re[1];
    }
    if($index==0 && $fileid==0 ){
        echo $tpl->div_warning("{select_www_requests_search}");
        return true;
    }

    $PFile=PROGRESS_DIR."/nginx-search.pattern";
    $sock=new sockets();
    $sock->REST_API_TIMEOUT=30;
    $json=json_decode($sock->REST_API_NGINX("/reverse-proxy/access/$line/$opts/$index/$fileid"));
    if(!$json->Status){
        echo $tpl->div_warning($json->Error);
    }

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
    	    <th>&nbsp;</th>
        	<th>{date}</th>
        	<th>&nbsp;</th>
        	<th nowrap>{src}</th>
        	<th>{sitename}</th>
        	<th>&nbsp;</th>
        	
        	<th>{total}</th>
        	<th>&nbsp;</th>
        	<th>{path}</th>
        	<th>{size}</th>
        	<th>{duration}</th>
            <th colspan='2'>{destination}</th>
        </tr>
  	</thead>
	<tbody>
	
";
    $GLOBALS["logfile_daemon"]=new logfile_daemon();
    $start = microtime(true);



    foreach ($json->lines as $line){
        $html[]=ProcessLine($line);
    }
    $end = microtime(true);
    $duration=$end - $start;
    $took=sprintf("Took %.6f seconds", $duration);
    writelogs("Processing : ".count($html)." lines $took",__FUNCTION__,__FILE__,__LINE__);

    $TINY_ARRAY["TITLE"]="{APP_NGINX}: {requests} &laquo;{$_GET["search"]}&raquo;";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_NGINX_REQUESTS_EXPLAIN}";
    $TINY_ARRAY["URL"]="nginx-requests";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $page=CurrentPageName();
    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents($PFile)."</i></div>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="Loadjs('$page?jsScanIPInfos=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function jsScanIPInfos(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $f[]="var idDataHash = {};";
    $f[]="";
    $f[]="    // Populate the hash";
    $f[]="    $('.ipinfo').each(function() {";
    $f[]="        const id = $(this).attr('id');";
    $f[]="        const data = $(this).attr('basedata');";
    $f[]="        if (id && data) {";
    $f[]="            idDataHash[id] = data;";
    $f[]="        }";
    $f[]="    });";
    $f[]="";
    $f[]="    // Loop over the hash and make API calls";
    $f[]="    $.each(idDataHash, function(id, data) {";
    $f[]="    let result = id.replaceAll(\"-flag\", \"\");";
    $f[]="    Loadjs('$page?jsFillIPInfos='+data+'&id='+result);";
    $f[]="    });";

    echo @implode("\n", $f);

}
function RealIP($json):string{

    $srcip=$json->remote_addr;
    if(property_exists($json,"http_x_forwarded_for")){
        if(strlen($json->http_x_forwarded_for)>4){
            return $json->http_x_forwarded_for;
        }
    }
    return strval($srcip);
}

function jsFillIPInfos(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $ZOOM=array();$ipinfos=array();
    $tpl=new template_admin();
    $json=json_decode(base64_decode($_GET["jsFillIPInfos"]));
    if(is_null($json)){
        return;
    }
    $srcip=RealIP($json);
    $page=CurrentPageName();
    $ip2Host=new ip2host();
    $ipinfos[]="<h3>$srcip</h3>";
    $ipinfoApi=$ip2Host->ipinfoApi($srcip);
    if(!isset($ipinfoApi["flag"])){
        $ipinfoApi["flag"]=null;
    }
    if(!isset($ipinfoApi["countryName"])){
        $ipinfoApi["countryName"]="{unknown}";
    }
    if($ipinfoApi["flag"]==null){$ipinfoApi["flag"]="flags/info.png";}
    $flag="<img src='/img/{$ipinfoApi["flag"]}'>";

    foreach ($ipinfoApi as $key=>$val){
        if(is_null($val)){
            continue;
        }
        $json->$key=$val;
    }
    if(isset($ipinfoApi["country"])) {
        $ipinfos[] = "<strong>{country}</strong>:&nbsp;{$ipinfoApi["country"]}/{$ipinfoApi["countryName"]}";
    }

    if(isset($ipinfoApi["city"])) {
        $ipinfos[] = "<strong>{city}</strong>:&nbsp;{$ipinfoApi["city"]}";
    }
    if(isset($ipinfoApi["isp"])) {
        $ipinfos[] = "<strong>ISP</strong>:&nbsp;{$ipinfoApi["isp"]}";
    }
    $ZOOM_TEXT=base64_encode(json_encode($json));

    $flag=base64_encode($tpl->td_href($flag,@implode("<br>",$ipinfos),"Loadjs('$page?zoom-js=$ZOOM_TEXT')"));
    $BigMd5=$_GET["id"];
    $loup=base64_encode($tpl->icon_loupe(1,"Loadjs('$page?zoom-js=$ZOOM_TEXT')"));
    $srcipData =base64_encode($tpl->td_href($srcip,$ipinfoApi["countryName"],"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$srcip')"));
    $f[]="if (document.getElementById('$BigMd5-flag') ){";
    $f[]="document.getElementById('$BigMd5-flag').innerHTML=base64_decode('$flag');";
    $f[]="}";
    $f[]="if (document.getElementById('$BigMd5-loupe') ){";
    $f[]="document.getElementById('$BigMd5-loupe').innerHTML=base64_decode('$loup');";
    $f[]="}";
    $f[]="if (document.getElementById('$BigMd5-src') ){";
    $f[]="document.getElementById('$BigMd5-src').innerHTML=base64_decode('$srcipData');";
    $f[]="}";

    echo @implode("\n", $f);

   // $erroruri=$tpl->td_href("$error_code_label$error_code</span>",$error_code_str,"Loadjs('$page?zoom-js=$ZOOM_TEXT&md=$BigMd5')");
}

function ProcessLine($line):string{
    $line=trim($line);
    if(strlen($line)<5){
        return "";
    }
    $srcip="";
    $url="";
    $cacheType="";
    $ClientCert="";
    $request_time="";
    $error="";
    $upstream_response_time="";
    $upstream_response_org=0;
    $BigMd5=md5($line);
    $cacheIcon["BYPASS"]="<i class='".ico_database."' id='ico-$BigMd5' style='color:#b0afaf'></i>";
    $cacheIcon["MISS"]="<i class='".ico_database."' id='ico-$BigMd5' style='color:#060606'></i>";
    $cacheIcon["HIT"]="<i class='".ico_database."' id='ico-$BigMd5' style='color:#1ab394'></i>";
    //writelogs("Processing : $line",__FUNCTION__,__FILE__,__LINE__);
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode($line);
    if (json_last_error()> JSON_ERROR_NONE) {
        writelogs("Unable to decode $line ",__FUNCTION__,__FILE__,__LINE__);
        return "";
    }
    if(property_exists($json,"realip_remote_addr ")){
    if($json->realip_remote_addr=="0.0.0.0"){
        $json->realip_remote_addr="";

    }
        $srcip=$json->remote_addr;
    }


    if(property_exists($json,"http_x_forwarded_for")) {
        if (strlen($json->http_x_forwarded_for) > 4) {
            $srcip = $json->http_x_forwarded_for;
        }
    }
    $sitename="-";
    if(property_exists($json,"server_name")) {
        $sitename = $json->server_name;
    }
    $destip=$json->upstream_addr;
    $apache_time=intval($json->timestamp);
    $upstream_status=$json->upstream_status;
    if(property_exists($json,"request_time")) {
        $request_time = $json->request_time;
    }
    $request_time_class="";
    $proto="";
    if(property_exists($json,"Protocol")) {
        $proto = $json->Protocol;
    }
    if(property_exists($json,"request")) {
        if (preg_match("#^([A-Z]+)\s+(.+?)\s+([A-Z]+)\/([0-9\.]+)$#", $json->request, $uri)) {
            $proto = $uri[1];
            $url = $uri[2];
        }
    }

    $turtle="";
    $error_code=0;
    $waf_id=0;
    if(!property_exists($json,"error_code")) {
        $error_code=$json->status;
    }


    $size=$json->bytes_out;
    $UserAgent=$json->user_agent;
    $block_country=0;
    $block_ip=0;
    $block_url=0;
    $block_ext=0;

    if(property_exists($json,"cache_status")) {
        $cacheType = $json->cache_status;
    }
    if(property_exists($json,"waf_id")) {
        $waf_id = $json->waf_id;
    }

    if(property_exists($json,"block_country")) {
        $block_country = intval($json->block_country);
    }
    if(property_exists($json,"block_ip")) {
        $block_ip = intval($json->block_ip);
    }
    if(property_exists($json,"block_url")) {
        $block_url = intval($json->block_url);
    }
    if(property_exists($json,"block_ext")) {
        $block_ext = intval($json->block_ext);
    }


    if(property_exists($json,"ClientCert")) {
        $ClientCert = $json->ClientCert;
    }
    if(property_exists($json,"error")) {
        $error = "<br><i>$json->error</i>";
    }

    $date=$tpl->time_to_date($apache_time,true);
    if(property_exists($json,"upstream_response_time")) {
        $upstream_response_time = $json->upstream_response_time;
        $upstream_response_org=floatval($upstream_response_time);
    }

    $cache_icon="";
    $upstream_response_class="";
    if(isset($cacheIcon[$cacheType])) {
        $cache_icon = $cacheIcon[$cacheType];
    }

    if(strlen($ClientCert)>0){
        if(preg_match("#CN=(.+)#",$ClientCert,$re)){
            $ClientCert=$re[1];
        }
        $ClientCert="&nbsp<i class='".ico_user."'></i>&nbsp;$ClientCert";
    }

    if($sitename==null){
        $defaulthost=gethostname();
        $sitename=$defaulthost;
    }
    if($block_country==1){
        $error="<br><i class='".ico_flag."'></i>&nbsp;{country_block}";
    }
    if($block_ip==1){
        $error="<br><i class='".ico_deny."'></i>&nbsp;{deny_access} {src}";
    }
    if($block_url==1){
        $error="<br><i class='".ico_flag."'></i>&nbsp;{url_filtering}";
    }
    if($block_ext==1){
        $error="<br><i class='".ico_flag."'></i>&nbsp;{deny_ext}";
    }


    $json->src=$srcip;
    $hton=ip2long($srcip);

    $flag="<img src='/img/flags/info.png'>";


    $fulluri="$sitename/$url";
    $fulluri="http://".str_replace("//","/",$fulluri);
    VERBOSE("Fulluri: $fulluri",__LINE__);
    $URIS=parse_url($fulluri);



    if(isset($URIS["path"])){
        foreach ($URIS as $key=>$val){$json->$key=$val;}
        $path=$URIS["path"];
    }
    $path=str_replace("//","/",$path);



    if(strlen($destip)>2) {
        $turtle = "<i class=\"fas fa-rabbit-fast\" style='color:#1ab394'></i>&nbsp;&nbsp;";
        if($error_code>200){$turtle="&nbsp;";}
        $upstream_response_class="text-primary";
        $upstream_response_time_inf = intval($upstream_response_time);
        if(strlen($upstream_response_time)>0) {
            $upstream_response_time_tooltip = $upstream_response_time;
            $upstream_response_time_tooltip_array = explode(".", $upstream_response_time_tooltip);
            $upstream_response_time = "{$upstream_response_time}s";
        }
        if ($upstream_response_time_inf > 0) {

            $upstream_response_class="text-danger font-bold";
            $turtle = "<i class=\"text-danger fad fa-turtle\"></i>&nbsp;&nbsp;";
            $unit="{second}";
            if($upstream_response_time_tooltip_array[0]>1){
                $unit="{seconds}";
            }
            $upstream_response_time_tooltip=$upstream_response_time_tooltip_array[0].
                " $unit ".$upstream_response_time_tooltip_array[1]."ms";
            $upstream_response_time=$tpl->td_href($upstream_response_time,"<H2>$upstream_response_time_tooltip </H2>{reverse_bad_backend_perfs}","blur('$BigMd5')");
        }
    }
    if($waf_id>0){
        $turtle = "<i class=\"text-danger fas fa-hockey-mask\"></i>&nbsp;&nbsp;";
    }

    $colordest="";
    $BackendStatus="";
    $request_time_time="";
    $error_code_str=$GLOBALS["logfile_daemon"]->codeToString($error_code);
    VERBOSE("$error_code==>$error_code_str",__LINE__);
    if(intval($upstream_status)>0) {
        $upstream_status_code_str = $GLOBALS["logfile_daemon"]->codeToString($upstream_status);
        list($colorbackend,$error_code_label_backend)=ErroCodeToLabel($upstream_status);
        if(strlen($colorbackend)>0){
            $colordest="color:$colorbackend";

        }
        $BackendStatus="$error_code_label_backend</span>&nbsp;<span style='$colordest'>$upstream_status_code_str</span>";
    }

    if(floatval($request_time)>0){
        $timeDiff=floatval($request_time)-$upstream_response_org;
        $request_time_class="text-primary";
        $request_time_time_inf = intval($request_time);
        $request_time_time_tooltip=$request_time;
        $request_time_time_tooltip_array=explode(".",$request_time_time_tooltip);
        $BigMd52=md5("AAA$BigMd5$request_time");

        $request_time_time = "{$request_time}s";

        if ($request_time_time_inf > 0) {
            $request_time_class="text-danger font-bold";

            if($request_time_time_tooltip_array[0]>1){
                $unit="{seconds}";
            }
            $request_time_time_tooltip=$request_time_time_tooltip_array[0].
                " $unit ".$request_time_time_tooltip_array[1]."ms (~{$timeDiff}ms)";
            $request_time_time=$tpl->td_href($request_time_time,"<H2>$request_time_time_tooltip </H2>{frontend_reverse_perfs}","blur('$BigMd52')");
        }
    }
    $color_style="";


    list($color,$error_code_label)=ErroCodeToLabel($error_code);
    if(strlen($color)>2){
        $color_style="color:$color";
    }
    $tdhead1="style='width:1%;$color_style' nowrap";
    $tdhead2="style='width:99%;$color_style'";
    if($size>0) {
        if ($size > 1024) {
            $size = FormatBytes($size / 1024);
        } else {
            $size = "$size bytes";
        }
    }


    $data=urlencode(base64_encode(json_encode($json)));
    $html[]="<tr id='$BigMd5'>";
    $html[]="<td $tdhead1><span class='ipinfo' id='$BigMd5-flag' basedata='$data'>$flag</td>";
    $html[]="<td $tdhead1>$date</td>";
    $html[]="<td $tdhead1><span id='$BigMd5-errcode'>$error_code_label$error_code_str</span></td>";
    $html[]="<td $tdhead1><span id='$BigMd5-src'>$srcip</span>$ClientCert</td>";
    $html[]="<td $tdhead1>$sitename</td>";
    $html[]="<td $tdhead1>$cache_icon</td>";

    $len=strlen($path);
    if($len>75){
        $path=substr($path,0,72)."...";
    }
    $path=htmlspecialchars($path);
    $html[]="<td $tdhead1><span class='$request_time_class'>$request_time_time</span></td>";
    $html[]="<td $tdhead1><span id='$BigMd5-loupe'></span></td>";
    $html[]="<td $tdhead2>$turtle&nbsp;$proto&nbsp;$path$error</td>";
    $html[]="<td $tdhead1>$size</td>";
    $html[]="<td $tdhead1><span class='$upstream_response_class'>$upstream_response_time</span></td>";
    $html[]="<td $tdhead1>$BackendStatus</td>";
    $html[]="<td $tdhead1><span style='$colordest'>$destip</span></td>";
    $html[]="</tr>";
    return @implode("\n",$html);
}

function ErroCodeToLabel($error_code):array{

    $color="rgb(103, 106, 108)";
    $error_code_label="<span class='label label-primary'>$error_code</span>&nbsp;";

    if($error_code==206){
        return array($color,$error_code_label);
    }

    if($error_code>200){
        $color="#ed5565";
        $error_code_label="<span class='label label-danger'>$error_code</span>&nbsp;";
    }
    if($error_code==204){
        $color="#888888";
        $error_code_label="<span class='label label-default'>$error_code</span>&nbsp;";}


    if($error_code==304){
        $color="#888888";
        $error_code_label="<span class='label label-default'>$error_code</span>&nbsp;";}

    if($error_code==499){
        $color="#f8ac59";
        $error_code_label="<span class='label label-warning'>$error_code</span>&nbsp;";}

    if($error_code==404){
        $color="#f8ac59";
        $error_code_label="<span class='label label-warning'>$error_code</span>&nbsp;";}

    if($error_code==403){
        $color="#ed5565";
        $error_code_label="<span class='label label-danger'>$error_code</span>&nbsp;";}

    if($error_code==444){
        $color="#ed5565";
        $error_code_label="<span class='label label-danger'>$error_code</span>&nbsp;";}

    if($error_code==499){
        $color="#ed5565";
        $error_code_label="<span class='label label-danger'>$error_code</span>&nbsp;";}

    if($error_code==302){
        $color="#888888";
        $error_code_label="<span class='label label-default'>$error_code</span>&nbsp;";}
    if($error_code==301){
        $color="#888888";
        $error_code_label="<span class='label label-default'>$error_code</span>&nbsp;";}

    if($error_code==500){
        $color="#ed5565";
        $error_code_label="<span class='label label-danger'>$error_code</span>&nbsp;";
    }
    if($error_code>500){
        $color="#ed5565";
        $error_code_label="<span class='label label-danger'>$error_code</span>&nbsp;";
    }
    if($error_code>500){
        $color="#ed5565";
        $error_code_label="<span class='label label-danger'>$error_code</span>&nbsp;";
    }
    if($error_code==1024){
        $color="#ed5565";
        $error_code_label="<span class='label label-danger'>$error_code</span>&nbsp;";
    }

    return array($color,$error_code_label);
}



