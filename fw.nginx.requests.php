<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__).'/ressources/class.modsectools.inc');
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["search-file-js"])){search_file_js();exit;}
if(isset($_GET["proxy_upstream_name-search"])){proxy_upstream_name_search();exit;}
if(isset($_GET["form"])){search_form();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
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
    // Pentest 2026-11-04 : zoom-js etait recopie sans encodage dans la page (XSS).
    $host=is_object($decoded)&&isset($decoded->host)?(string)$decoded->host:"";
    return $tpl->js_dialog4(req_html(req_html($host)),"$page?zoom-popup=".req_url($encoded),1050);
}
function proxy_upstream_name_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Pentest 2026-11-04 : id/suffix-form etaient recopies sans encodage dans la page (XSS).
    $id=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    $suffix_form=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["suffix-form"] ?? ""));
    return $tpl->js_dialog10("{servicename}","$page?proxy_upstream_name-popup=yes&id=$id&suffix-form=$suffix_form");
}
function proxy_upstream_name_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Pentest 2026-11-04 : id/suffix-form etaient recopies sans encodage dans la page (XSS).
    $id=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    $suffix_form=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["suffix-form"] ?? ""));
    echo $tpl->search_block($page, null, null, null, "&proxy_upstream_name-search=yes&id=$id&suffix-form=$suffix_form");
    return true;
}


function proxy_upstream_name_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // $id designe un element du DOM et repartait BRUT dans
    // document.getElementById('...') -- donc dans du JavaScript.
    $id=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    $search=(string)($_GET["search"] ?? "");
    $needle=strtolower(trim(str_replace("*","",$search)));
    // Daemon API gives all enabled rows ordered by zorder; the LIKE on
    // (servicename, hosts) is applied PHP-side because /nginx-db/list only
    // supports ORDER BY, not multi-column WHERE.
    $rows=fetch_enabled_nginx_services("zorder");
    $results=array();
    foreach($rows as $r){
        if($needle===""){
            $results[]=$r;continue;
        }
        $hayName=strtolower(isset($r["servicename"])?$r["servicename"]:"");
        $hayHosts=strtolower(isset($r["hosts"])?$r["hosts"]:"");
        if(strpos($hayName,$needle)!==false || strpos($hayHosts,$needle)!==false){
            $results[]=$r;
        }
    }
    $html=array();
    $js=array();
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    // Ce tableau n'avait AUCUN <thead>: trois colonnes sans intitule, dont la
    // premiere ne contenait que le libelle repete "{sitename}:".
    $html[]="<th>{sitename}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";

    $n=0;
    foreach ($results as $ligne) {
        if(!is_array($ligne)){continue;}
        $n++;
        $idF=md5(serialize($ligne));
        $key=base64_encode(((string)($ligne["servicename"] ?? ""))."-".intval($ligne["ID"] ?? 0));
        $js[]="function Main$idF(){";
        $js[]="document.getElementById('$id').value=base64_decode('$key');";
        $js[]="dialogInstance10.close();";
        $js[]="}";
        $html[]=upstream_service_row($ligne,$idF);
    }
    if($n==0){
        $html[]="<tr><td colspan='2' class='awc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div></div></div>";

    // $TRCLASS etait calcule a chaque tour puis jamais applique: la ligne portait
    // un style inline et aucune classe.
    $script="<script>\n".@implode("\n",$js)."\n</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;

}

/**
 * Un service reverse selectionnable.
 * Le nom du service et la liste des hotes venaient de la base et partaient
 * BRUTS dans le HTML, et la cellule du nom se fermait sur DEUX </strong> pour
 * un seul <strong> ouvrant.
 */
function upstream_service_row(array $ligne,$idF):string{
    $servicename=(string)($ligne["servicename"] ?? "");
    $hosts=str_replace("||",", ",(string)($ligne["hosts"] ?? ""));

    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-globe'></i></span>";
    $h.="<span><div class='awc-name'>".htmlspecialchars($servicename)."</div>";
    $h.="<div class='awc-meta'>".htmlspecialchars($hosts)."</div></span>";
    $h.="</div></td>";
    $h.="<td class='center awc-nowrap'>"
       ."<a href=\"javascript:blur();\" class='awc-btn' style='padding:4px 10px;font-size:11px' "
       ."OnClick=\"Main$idF()\">{select}</a></td>";
    $h.="</tr>";
    return $h;
}

function harmptabs():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    // Pentest 2026-11-04 : HARMPID (session alimentee par le GET harmpid) etait concatene dans la requete.
    $sql="SELECT * FROM hamrp WHERE groupid=".intval($_SESSION["HARMPID"] ?? 0)." ORDER BY zOrder";
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
    // Pentest 2026-11-04 : zoom-popup (JSON de la requete) etait recopie sans encodage dans la page (XSS).
    if(!is_object($decoded)){$decoded=new stdClass();}
    foreach (get_object_vars($decoded) as $zk=>$zv){
        if(is_scalar($zv)){$decoded->$zk=req_html($zv);}
    }
    $src=isset($decoded->src_ip)?$decoded->src_ip:"";

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
function nginxsearch_get($key):string{
    if(!isset($_SESSION["NGINXSEARCH"][$key])){return "";}
    $v=trim(strval($_SESSION["NGINXSEARCH"][$key]));
    // jQuery < 3 encode un DropDown/DropDownMultiple totalement desamorce en la
    // chaine litterale "null" (encodeURIComponent(null)) : sans cette garde le
    // demon recoit "status=null" en permanence et le filtre ne peut plus etre efface.
    if($v==="null"){return "";}
    return $v;
}

// Ancien format : codes « 3[0-9][0-9] » url-encodés, méthodes mêlées au statut,
// time_local = liste d'heures. Traduit une fois, puis sauvegardé.
function nginxsearch_migrate_session(){
    if(!isset($_SESSION["NGINXSEARCH"]) || !is_array($_SESSION["NGINXSEARCH"])){return;}
    $S=$_SESSION["NGINXSEARCH"];
    $changed=false;
    if(isset($S["time_local"])){
        $hours=array();
        foreach(explode(",",strval($S["time_local"])) as $h){
            $h=trim($h);
            if($h==="" || !is_numeric($h)){continue;}
            $h=intval($h);
            if($h<0 || $h>23){continue;}
            $hours[]=$h;
        }
        if(count($hours)>0){
            $S["time_from"]=sprintf("%02d:00",min($hours));
            $S["time_to"]=sprintf("%02d:59",max($hours));
        }
        unset($S["time_local"]);
        $changed=true;
    }
    $statusLooksOld=false;
    if(isset($S["status"]) && $S["status"]!==""){
        if(strpos($S["status"],"[")!==false || strpos($S["status"],"%")!==false){
            $statusLooksOld=true;
        }else{
            foreach(explode(",",$S["status"]) as $tok){
                $tok=trim($tok);
                if($tok!=="" && preg_match("#^[A-Za-z]+$#",$tok)){$statusLooksOld=true;break;}
            }
        }
    }
    if($statusLooksOld){
        $codes=array();
        $methods=array();
        if(isset($S["http_method"]) && strlen($S["http_method"])>0){$methods[]=strtoupper($S["http_method"]);}
        foreach(explode(",",urldecode(strval($S["status"]))) as $tok){
            $tok=trim(str_replace("\\","",$tok));
            if($tok===""){continue;}
            if(preg_match("#^[A-Za-z]+$#",$tok)){$methods[]=strtoupper($tok);continue;}
            if(strpos($tok,"[0-9]")!==false){$tok=substr($tok,0,1)."xx";}
            $codes[]=$tok;
        }
        $S["status"]=implode(",",array_unique($codes));
        $S["http_method"]=implode(",",array_unique($methods));
        $changed=true;
    }
    if(isset($S["request"])){
        if(!isset($S["uri_path"]) || strlen($S["uri_path"])==0){$S["uri_path"]=$S["request"];}
        unset($S["request"]);
        $changed=true;
    }
    if($changed){
        $_SESSION["NGINXSEARCH"]=$S;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SearchNginx{$_SESSION["uid"]}",serialize($S));
    }
}

// Liste blanche des jetons de statut et de méthode. DropDownMultiple() (partagé,
// non modifiable ici) réinjecte la valeur courante telle quelle dans un bloc
// <script> : une valeur de session arbitraire casserait le script. On ne garde
// donc que les jetons valides ; les autres sont ignorés (jamais enregistrés,
// jamais rendus, jamais envoyés au démon).
function nginxsearch_clean_tokens($value,$pattern):string{
    $out=array();
    foreach(explode(",",strval($value)) as $tok){
        $tok=trim($tok);
        if($tok===""){continue;}
        if(!preg_match($pattern,$tok)){continue;}
        if(in_array($tok,$out,true)){continue;}
        $out[]=$tok;
    }
    return implode(",",$out);
}
function nginxsearch_clean_status($value):string{
    return nginxsearch_clean_tokens($value,'#^[1-5]([0-9]{2}|xx)$#');
}
function nginxsearch_clean_methods($value):string{
    return nginxsearch_clean_tokens($value,'#^[A-Z]+$#');
}

// Nombres saisis dans la console : un texte non numérique est refusé ici, sans
// appeler le démon (sinon intval/floatval le changeaient silencieusement en 0).
function nginxsearch_numeric_error():string{
    $md=str_replace(",",".",nginxsearch_get("min_duration"));
    if($md!=="" && !is_numeric($md)){
        return "min_duration: ".htmlspecialchars(nginxsearch_get("min_duration"),ENT_QUOTES)." is not a number";
    }
    $mx=nginxsearch_get("max_records");
    if($mx!=="" && !is_numeric($mx)){
        return "max: ".htmlspecialchars($mx,ENT_QUOTES)." is not a number";
    }
    return "";
}

function nginxsearch_filters($source,$id,$text):array{
    // max 1..1500 côté démon : les anciennes sessions gardaient 5000.
    $max=intval(nginxsearch_get("max_records"));
    if($max>1500){$max=1500;}

    return array(
        "source"=>$source,
        "id"=>intval($id),
        "max"=>$max,
        "time_from"=>nginxsearch_get("time_from"),
        "time_to"=>nginxsearch_get("time_to"),
        "status"=>nginxsearch_clean_status(nginxsearch_get("status")),
        "method"=>nginxsearch_clean_methods(nginxsearch_get("http_method")),
        "src_ip"=>nginxsearch_get("remote_addr"),
        "uri"=>nginxsearch_get("uri_path"),
        "user_agent"=>nginxsearch_get("user_agent"),
        "server_name"=>nginxsearch_get("server_name"),
        "upstream_addr"=>nginxsearch_get("upstream_addr"),
        "upstream_status"=>nginxsearch_get("upstream_status"),
        "cache_status"=>nginxsearch_get("cache_status"),
        "min_duration"=>floatval(str_replace(",",".",nginxsearch_get("min_duration"))),
        "text"=>trim(strval($text)),
        "use_regex"=>(nginxsearch_get("use_regex")=="1"),
    );
}

function search_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $urltoadd="";
    $fileid=0;
    $latencyID=0;
    $Latencies=array();
    if(isset($_GET["fileid"])){
        $fileid=intval($_GET["fileid"]);
        $urltoadd="&fileid=".$fileid;

    }
    if(isset($_GET["latencyID"])){
        $latencyID=intval($_GET["latencyID"]);
        $urltoadd="&latencyID=".$latencyID;

    }
    $results=fetch_enabled_nginx_services("servicename");

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



    nginxsearch_migrate_session();

    $proxy_upstream_name="";
    $proxy_latency_name="";

    if(isset($_SESSION["NGINXSEARCH"]["proxy_upstream_name"])){
        $proxy_upstream_name=$_SESSION["NGINXSEARCH"]["proxy_upstream_name"];
    }
    if(isset($_SESSION["NGINXSEARCH"]["proxy_latency_name"])){
        $proxy_latency_name=$_SESSION["NGINXSEARCH"]["proxy_latency_name"];
    }


    $logfile_daemon=new logfile_daemon();
    $AArry=array();

    foreach ($results as $index=>$ligne){
        $servicename=$ligne["servicename"];
        $ID=$ligne["ID"];
        $zids[$ID]=$servicename;
        $Key="$servicename-$ID";
        $AArry[$Key]=$servicename;

    }

    $Tenants=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/events/latency/scan"),true);
    if(!isset($Tenants["Status"]) && isset($Tenants["tenants"]) && is_array($Tenants["tenants"]) && count($Tenants["tenants"])>0) {
            foreach ($Tenants["tenants"] as $tenant) {
                if (isset($zids[$tenant])) {
                    $Latencies[$tenant] = $zids[$tenant];
                }
            }
        }



    $CODES=array("2xx"=>"2xx","3xx"=>"3xx","4xx"=>"4xx","5xx"=>"5xx");
    foreach ($logfile_daemon->codeToString(null,true) as $num=>$text){
        if(preg_match("#^[1-5][0-9][0-9]$#",$num)){$CODES[$num]="$num $text";}
    }
    $METHODS=array();
    foreach (explode(",","GET,HEAD,POST,OPTIONS,PUT,PATCH,DELETE,PROPFIND,PROPPATCH,MKCOL,COPY,MOVE,LOCK,UNLOCK,TRACE,CONNECT") as $m){
        $METHODS[$m]=$m;
    }
    $CACHES=array(""=>"{all}","HIT"=>"HIT","MISS"=>"MISS","BYPASS"=>"BYPASS","EXPIRED"=>"EXPIRED","STALE"=>"STALE","UPDATING"=>"UPDATING","REVALIDATED"=>"REVALIDATED");
    $MODES=array("0"=>"{access_search_literal}","1"=>"{access_search_regex}");
    $v=function($k){return htmlspecialchars(nginxsearch_get($k),ENT_QUOTES);};
    $save="$page?save-field=%s&value=%%s$urltoadd";
    $ico_help=ico_support;
    $js_help="s_PopUpFull('https://wiki.articatech.com/reverse-proxy/realtime-events','1024','900');";

    $L1=array();
    if($fileid==0) {
        $L1[] = $tpl->DropDown($AArry, $proxy_upstream_name, "{sitename}", sprintf($save,"proxy_upstream_name"));
    }
    if(count($Latencies)>0){
        $L1[] = $tpl->DropDown($Latencies, $proxy_latency_name, "{latency}", sprintf($save,"proxy_latency_name"));
    }
    $L1[]=$tpl->field_text_online("time_from","{access_search_from}",$v("time_from"),110,sprintf($save,"time_from"));
    $L1[]=$tpl->field_text_online("time_to","{access_search_to}",$v("time_to"),110,sprintf($save,"time_to"));
    $L1[]=$tpl->DropDownMultiple($CODES,nginxsearch_clean_status(nginxsearch_get("status")),"{http_status_code}",sprintf($save,"status"),260);
    $L1[]=$tpl->DropDownMultiple($METHODS,nginxsearch_clean_methods(nginxsearch_get("http_method")),"{method}",sprintf($save,"http_method"),200);
    if(nginxsearch_get("max_records")==""){$_SESSION["NGINXSEARCH"]["max_records"]=300;}
    $L1[]=$tpl->field_text_online("max_records","{records}",$v("max_records"),90,sprintf($save,"max_records"));
    if($fileid==0) {
        $L1[] = "<button class='btn btn-primary btn-xs' type='button' OnClick=\"LoadAjax('nginx-search-results','$page?search=')\" style='height:34px'>Go</button>";
        $L1[] = "<button class='btn btn-default btn-circle' type='button' OnClick=\"$js_help\"><i class='$ico_help'></i></button>";
    }

    $L2=array();
    $L2[]=$tpl->field_text_online("remote_addr","{src} (IP, CIDR)",$v("remote_addr"),170,sprintf($save,"remote_addr"));
    $L2[]=$tpl->field_text_online("uri_path","{request}",$v("uri_path"),200,sprintf($save,"uri_path"));
    $L2[]=$tpl->field_text_online("user_agent","{http_user_agent}",$v("user_agent"),200,sprintf($save,"user_agent"));
    $L2[]=$tpl->field_text_online("server_name","{server_name}",$v("server_name"),170,sprintf($save,"server_name"));
    $L2[]=$tpl->field_text_online("upstream_addr","{upstream_addr}",$v("upstream_addr"),170,sprintf($save,"upstream_addr"));
    $L2[]=$tpl->field_text_online("upstream_status","{upstream_status}",$v("upstream_status"),120,sprintf($save,"upstream_status"));
    $L2[]=$tpl->DropDown($CACHES,nginxsearch_get("cache_status"),"{cache}",sprintf($save,"cache_status"));
    $L2[]=$tpl->field_text_online("min_duration","{access_search_min_duration}",$v("min_duration"),120,sprintf($save,"min_duration"));
    $L2[]=$tpl->DropDown($MODES,nginxsearch_get("use_regex"),"{access_search_literal}",sprintf($save,"use_regex"));

    // Les options des DropDown ({select}, {all}, {access_search_*}) ne sont
    // traduites que par ce passage : DropDown() ne traduit que le placeholder.
    $form=array();
    $form[]="<div style='margin-top:5px;margin-bottom:5px'>&nbsp;</div>";
    $form[]="<div>".@implode("&nbsp;&nbsp;",$L1)."</div>";
    $form[]="<div style='margin-top:8px'>".@implode("&nbsp;&nbsp;",$L2)."</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$form));
    echo "<div id='nginx-search-results' style='margin-top:25px'></div>";
    echo "<script>LoadAjax('nginx-search-results','$page?search=$urltoadd');</script>";
    return true;
}

function saveField(){
    $page=CurrentPageName();
    $field=$_GET["save-field"];
    $fileid=0;
    $latencyID=0;
    // Pentest 2026-11-04 : fileid/latencyID etaient recopies sans encodage dans la page (XSS).
    if(isset($_GET["fileid"])){
        $fileid=intval($_GET["fileid"]);
    }

    if(isset($_GET["latencyID"])){
        $latencyID=intval($_GET["latencyID"]);
    }



    // $_GET est deja decode par PHP : un urldecode() supplementaire cassait les
    // valeurs contenant "+" (converti en espace) ou "%xx" (decode deux fois) --
    // regex, user-agent "+http://...", URI avec "+"/"%".
    $value=isset($_GET["value"])?$_GET["value"]:"";
    if(is_null($value)){$value="";}
    if($field=="status"){$value=nginxsearch_clean_status($value);}
    if($field=="http_method"){$value=nginxsearch_clean_methods($value);}
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
    echo "LoadAjax('nginx-search-results','$page?search=&fileid=$fileid&latencyid=$latencyID');";
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
    $AddTitle="";
    $fileid=0;
    $index=0;
    if(isset($_GET["fileid"])){
        $fileid=intval($_GET["fileid"]);
    }
    $proxy_latency_name=0;
    if(isset($_SESSION["NGINXSEARCH"]["proxy_latency_name"])){
        $proxy_latency_name=intval($_SESSION["NGINXSEARCH"]["proxy_latency_name"]);

    }

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $proxy_upstream_name=null;
    if(!isset($_SESSION["NGINXSEARCH"])){$_SESSION["NGINXSEARCH"]=array();}
    if(isset($_SESSION["NGINXSEARCH"]["proxy_upstream_name"])){
        $proxy_upstream_name=$_SESSION["NGINXSEARCH"]["proxy_upstream_name"];
    }else{
        VERBOSE("SESSSION[NGINXSEARCH][proxy_upstream_name]=UNKNOWN",__LINE__);
    }

    if($fileid==0) {
        if (is_null($proxy_upstream_name)) {
            echo $tpl->div_warning("{select_www_requests_search}");
            return true;
        }
    }

    if(preg_match("#-([0-9]+)$#",$proxy_upstream_name,$re)){
        $index=$re[1];
    }
    if($index==0 && $fileid==0 ){
        echo $tpl->div_warning("{select_www_requests_search}");
        return true;
    }

    $source="realtime";
    $id=$index;
    if($fileid>0){$source="fileid";$id=$fileid;}
    if($proxy_latency_name>0){$AddTitle="{latency}";$source="latency";$id=$proxy_latency_name;}

    $numErr=nginxsearch_numeric_error();
    if($numErr!==""){
        echo $tpl->div_error($numErr);
        return true;
    }
    $sock=new sockets();
    $term="";
    if(isset($MAIN["TERM"])){$term=$MAIN["TERM"];}
    $raw=$sock->REST_API_NGINX_POST_JSON("/reverse-proxy/access/search",nginxsearch_filters($source,$id,$term),30);
    $json=json_decode($raw,true);
    if(!is_array($json) || !isset($json["Status"])){
        if(is_array($json) && isset($json["error"])){
            echo $tpl->div_error("{access_search_daemon_unavailable}<br>".htmlspecialchars(strval($json["error"])));
            return true;
        }
        echo $tpl->div_error("{access_search_daemon_unavailable}");
        return true;
    }
    if(!$json["Status"]){
        echo $tpl->div_error(htmlspecialchars(strval($json["Error"])));
        return true;
    }
    $R=$json["result"];
    if(intval($R["file_size"])<0){
        echo $tpl->div_warning("{no_recent_access_log}");
        return true;
    }
    $period="-";
    if(floatval($R["oldest_ts"])>0){
        $period=date("H:i:s",intval($R["oldest_ts"]))." &rarr; ".date("H:i:s",intval($R["newest_ts"]));
    }
    $summary=sprintf("%d {access_search_results} &middot; {access_search_period} %s &middot; %.1f MB {access_search_read_in} %.2f s",
        intval($R["matched"]),$period,floatval($R["scanned_bytes"])/1048576,floatval($R["duration_ms"])/1000);
    $banner="<div style='margin-bottom:8px'><i>$summary</i></div>";
    if($R["truncated"]=="budget"){$banner.=$tpl->div_warning("{access_search_truncated_budget}");}
    if($R["truncated"]=="max"){$banner.=$tpl->div_warning("{access_search_truncated_max}");}

    $GLOBALS["logfile_daemon"]=new logfile_daemon();
    $tb=array();
    if(isset($R["lines"]) && is_array($R["lines"])){$tb=$R["lines"];}
    $html=array();
    foreach ($tb as $line){
        $html[]=ProcessLine($line);
    }
    writelogs("Processing : ".count($html)." lines",__FUNCTION__,__FILE__,__LINE__);

    $TINY_ARRAY["TITLE"]="{APP_NGINX}: $AddTitle
    {requests} &laquo;".req_html($_GET["search"])."&raquo;";
    // Pentest 2026-11-04 : search etait recopie sans encodage dans la page (XSS).
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_NGINX_REQUESTS_EXPLAIN}";
    $TINY_ARRAY["URL"]="nginx-requests";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $page=CurrentPageName();
    $rows=$html;
    $html=array();
    $html[]=$banner;
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
    foreach ($rows as $rowhtml){$html[]=$rowhtml;}
    $html[]="</tbody></table>";
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
    // Pentest 2026-11-04 : jsFillIPInfos (adresse source) et id etaient recopies sans encodage dans la page (XSS).
    $srcip=preg_replace('/[^A-Za-z0-9.:%_, -]/','',(string)$srcip);
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
    $BigMd5=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
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
        $request_time = req_html($json->request_time);
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
    // Pentest 2026-11-04 : champs du journal d'acces (donnee externe : X-Forwarded-For, Host, URI, certificat client, erreur) affiches sans encodage (XSS stockee).
    if(property_exists($json,"error")) {
        $error = "<br><i>".req_html($json->error)."</i>";
    }

    $date=$tpl->time_to_date($apache_time,true);
    if(property_exists($json,"upstream_response_time")) {
        $upstream_response_time = req_html($json->upstream_response_time);
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
        $ClientCert="&nbsp<i class='".ico_user."'></i>&nbsp;".req_html($ClientCert);
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
    $html[]="<td $tdhead1><span id='$BigMd5-src'>".req_html($srcip)."</span>$ClientCert</td>";
    $html[]="<td $tdhead1>".req_html($sitename)."</td>";
    $html[]="<td $tdhead1>$cache_icon</td>";

    $len=strlen($path);
    if($len>75){
        $path=substr($path,0,72)."...";
    }
    $path=req_html($path);
    $proto=req_html($proto);
    $html[]="<td $tdhead1><span class='$request_time_class'>$request_time_time</span></td>";
    $html[]="<td $tdhead1><span id='$BigMd5-loupe'></span></td>";
    $html[]="<td $tdhead2>$turtle&nbsp;$proto&nbsp;$path$error</td>";
    $html[]="<td $tdhead1>$size</td>";
    $html[]="<td $tdhead1><span class='$upstream_response_class'>$upstream_response_time</span></td>";
    $html[]="<td $tdhead1>$BackendStatus</td>";
    $html[]="<td $tdhead1><span style='$colordest'>".req_html($destip)."</span></td>";
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



