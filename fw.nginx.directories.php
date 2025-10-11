<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["directory-js"])){directory_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["directory-settings"])){directory_settings();exit;}
if(isset($_GET["directory-tab"])){directory_tab();exit;}
if(isset($_POST["ID"])){directory_save();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_GET["items"])){print_r(items($_GET["items"]));exit;}
if(isset($_GET["enabled"])){directory_enabled();exit;}

table_start();

function directory_js():bool{
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["directory-js"];
    $serviceid=intval($_GET["serviceid"]);
    $md5=$_GET["md5"];
    $title="{rule}: $ID";
    if($ID==0){$title="{new_path}";}

    if($ID>0){
        $q=new lib_sqlite(NginxGetDB());
        $ligne=$q->mysqli_fetch_array("SELECT * FROM ngx_directories WHERE ID=$ID");
       return  $tpl->js_dialog3($ligne["directory"], "$page?directory-tab=$ID&serviceid=$serviceid&md5=$md5&function=$function");
    }

    return $tpl->js_dialog3($title, "$page?directory-settings=$ID&serviceid=$serviceid&md5=$md5&function=$function");
}
function directory_tab():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["directory-tab"]);
    $serviceid  = intval($_GET["serviceid"]);
    $md5        = $_GET["md5"];
    $function   = $_GET["function"];
    $array["{settings}"]="$page?directory-settings=$ID&serviceid=$serviceid&md5=$md5&function=$function";
    $array["{ACLS}"]="fw.nginx.directories.items.php?directory_id=$ID&serviceid=$serviceid&md5=$md5&function=$function";
    $array["{backends}"]="fw.nginx.directories.backends.php?directory_id=$ID&serviceid=$serviceid&md5=$md5&function=$function";
    $array["{replace_rules}"]="fw.nginx.directories.replace.php?popup-main=yes&directory_id=$ID&serviceid=$serviceid&md5=$md5&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}

function delete_js():bool{
//delete=$ID&md5=$md5
    $ID         = intval($_GET["delete"]);
    $md5        = $_GET["md5"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT serviceid FROM ngx_directories WHERE ID=$ID");
    $get_servicename=get_servicename($ID);
    $serviceid=$ligne["serviceid"];
    $q->QUERY_SQL("DELETE FROM ngx_subdir_items WHERE directoryid=$ID");
    $q->QUERY_SQL("DELETE FROM ngx_directories WHERE ID=$ID");
    header("content-type: application/x-javascript");
    echo "$('#$md5').remove();\n";
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');\n";
    return admin_tracks("Remove path $ID from $get_servicename service");

}

function get_servicename($ngx_directories_id=0):string{
    $ID=intval($ngx_directories_id);
    if($ID==0){return "Unknown";}
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT serviceid FROM ngx_directories WHERE ID=$ngx_directories_id");
    $serviceid=$ligne["serviceid"];
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$serviceid");
    return strval($ligne["servicename"]);
}

function directory_enabled():bool{
    $ID         = intval($_GET["enabled"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT serviceid,enabled FROM ngx_directories WHERE ID=$ID");
    $serviceid=$ligne["serviceid"];
    $sql="UPDATE ngx_directories SET enabled=0 WHERE ID=$ID";
    $get_servicename=get_servicename($ID);
    if($ligne["enabled"]==1){
        $q->QUERY_SQL($sql);
        header("content-type: application/x-javascript");
        echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');\n";
        return  admin_tracks("Disable path $ID from $get_servicename service");
    }
    $q->QUERY_SQL("UPDATE ngx_directories SET enabled=1 WHERE ID=$ID");
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');\n";
    return admin_tracks("Enable path $ID from $get_servicename service");

}

function ifModSecurityDisabled($serviceid):bool{

    $NginxHTTPModSecurity=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    if($NginxHTTPModSecurity==0){
        return true;
    }
    $EnableModSecurityIngix = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    if($EnableModSecurityIngix==0){
        return true;}
    $socknginx= new socksngix($serviceid);
    $EnableModSecurity=intval($socknginx->GET_INFO("EnableModSecurity"));
    if($EnableModSecurity==0){
        return true;}
    VERBOSE("EnableModSecurity = [SUCCESS]",__LINE__);
    return false;
}

function directory_settings():bool{
        $page       = CurrentPageName();
        $tpl        = new template_admin();
        $fieldH     = array();
        $ID         = intval($_GET["directory-settings"]);
        $serviceid  = intval($_GET["serviceid"]);
        $zproxy_http_version["1.0"]="1.0: {default} ";
        $zproxy_http_version["1.1"]="1.1: KeepAlive {or} NTLM";
        $function=null;
        if(isset($_GET["function"])) {
            $function = $_GET["function"];
        }
        $title      = "{new_path}";
        $options["REDIRECT_HTTP"]=null;
        $btname="{add}";
        $q=new lib_sqlite(NginxGetDB());
        for($i=1;$i<24;$i++){$fieldH[$i]="$i {hours}";}
        for($i=2;$i<30;$i++){$hours=$i*24;$fieldH[$hours]="$i {days}";}

        $directory="/";
        if($ID>0){
            $ligne=$q->mysqli_fetch_array("SELECT * FROM ngx_directories WHERE ID=$ID");
            $btname="{apply}";
            $title="{$ligne["directory"]}";
            $serviceid=intval($ligne["serviceid"]);
            $directory=$ligne["directory"];
            $options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
        }
        if(!isset($options["REDIRECT_HTTP"])){$options["REDIRECT_HTTP"]=null;}
        if(!isset($options["proxy_http_version"])){$options["proxy_http_version"]="1.0";}
        if(!isset($options["HostHeader"])){$options["HostHeader"]="";}
        if(!isset($options["WebSocketsSupport"])){$options["WebSocketsSupport"]=0;}
        if(!isset($options["sslclient"])){$options["sslclient"]=0;}

        if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
        if(!isset($ligne["deny"])){$ligne["deny"]=0;}
        if(!isset($ligne["cachetime"])){$ligne["cachetime"]=0;}
        if(!isset($ligne["cacheargs"])){$ligne["cacheargs"]=0;}

        if(!isset($ligne["bcacheimages"])){$ligne["bcacheimages"]=0;}
        if(!isset($ligne["bcachehtml"])){$ligne["bcachehtml"]=0;}
        if(!isset($ligne["bcachebinaries"])){$ligne["bcachebinaries"]=0;}

        $bcacheimages=$ligne["bcacheimages"];
        $bcachehtml=$ligne["bcachehtml"];
        $bcachebinaries=$ligne["bcachebinaries"];

        $tt[]="dialogInstance3.close();";
        if($function<>null){
            $tt[]="$function()";
        }
        $tt[]="LoadAjax('ngx_directories_access_module-$serviceid','$page?table=$serviceid')";
        $tt[]="Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');";
        $js=@implode(";",$tt);

        $proxy_http_version=$options["proxy_http_version"];
        $HostHeader=$options["HostHeader"];
        $WebSocketsSupport=intval($options["WebSocketsSupport"]);

        $form[]=$tpl->field_hidden("ID", $ID);
        $form[]=$tpl->field_hidden("serviceid", $serviceid);

        if($ID>0){
            $form[]=$tpl->field_checkbox("enabled", "{enabled}", intval($ligne["enabled"]));
        }else {
            $form[]=$tpl->field_hidden("enabled", 1);
        }
        $LockCertif=LockCertif($serviceid);
        VERBOSE("LockCertif = $LockCertif",__LINE__);
        if($LockCertif){
            $options["sslclient"]=0;
        }

        $form[] = $tpl->field_text("directory", "{path}", $directory,true);
        $form[] = $tpl->field_text("REDIRECT_HTTP","{label_redirect}",$options["REDIRECT_HTTP"]);
        $form[] = $tpl->field_array_hash($zproxy_http_version,"proxy_http_version",
                    "{proxy_http_version}",$proxy_http_version);
        $form[] = $tpl->field_text("HostHeader","{HostHeader}",$HostHeader);
        $form[] = $tpl->field_checkbox("WebSocketsSupport","{websockets_support}",$WebSocketsSupport);
        $form[] = $tpl->field_section("{caching}");
        $form[] = $tpl->field_numeric("cachetime","{cachetime} ({minutes})",$ligne["cachetime"]);
        $form[] = $tpl->field_checkbox("cacheargs", "{cache_arguments}", intval($ligne["cacheargs"]));
        $form[] = $tpl->field_section("{browser_caching}||{browser_caching_explain}");
        $form[] = $tpl->field_array_hash($fieldH,"bcacheimages", "{cache_images}",$bcacheimages);
        $form[] = $tpl->field_array_hash($fieldH,"bcachehtml", "{cache_htmlext}",$bcachehtml);
        $form[] = $tpl->field_array_hash($fieldH,"bcachebinaries", "{cache_binaries}",$bcachebinaries);


        $form[] = $tpl->field_section("{security}");
        $form[] = $tpl->field_checkbox("deny", "{deny}", intval($ligne["deny"]));
        $ifModSecurityEnabled=ifModSecurityDisabled($serviceid);
        $form[] = $tpl->field_checkbox("wafwhite", "{wafwhite}", intval($ligne["wafwhite"]),false,null,$ifModSecurityEnabled);

        $form[] = $tpl->field_checkbox("sslclient", "{verify_client_certificate}", intval($options["sslclient"]),false,null,$LockCertif);

        $help="https://wiki.articatech.com/en/reverse-proxy/paths";
        echo $tpl->form_outside($title, $form,$help,$btname,$js,"AsSystemWebMaster");
        return true;
}

function table_start():bool{
    $page=CurrentPageName();
    $ID=$_GET["service"];
    $tpl=new template_admin();
    echo "<div style='margin-top:15px;margin-bottom: 5px' id='nginx-dirs-$ID'></div>";
    echo "<div style='margin-top:5px;width:99%'>";
    echo $tpl->search_block($page,null,null,"ngx_directories_access_module-$ID","&table=$ID");
    echo "</div>";
    return true;
}
function ActiveCertif($serviceid):bool{
    $sockngix=new socksngix($serviceid);
    $ssl_certificate=$sockngix->GET_INFO("ssl_certificate");
    if (strlen($ssl_certificate)<4){
        VERBOSE("[$serviceid]: ssl_certificate len is false",__LINE__);
        return false;
    }
    $EnableClientCertificate    = intval($sockngix->GET_INFO("EnableClientCertificate"));
    if($EnableClientCertificate==0){
        VERBOSE("EnableClientCertificate =0",__LINE__);
        return false;
    }
    return true;
}
function LockCertif($serviceid):bool{
    $sockngix=new socksngix($serviceid);
    if(!ActiveCertif($serviceid)){
        return true;
    }
    $OptionalClientCertificate=intval($sockngix->GET_INFO("OptionalClientCertificate"));
    if($OptionalClientCertificate==0){
        VERBOSE("[$serviceid]: OptionalClientCertificate = 0 -> LOCK",__LINE__);
        return true;
    }
    return false;
}

function directory_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $options["REDIRECT_HTTP"]=$_POST["REDIRECT_HTTP"];
    $options["deny"]=intval($_POST["deny"]);
    $options["sslclient"]=$_POST["sslclient"];
    $options["proxy_http_version"]=$_POST["proxy_http_version"];
    $options["HostHeader"]=$_POST["HostHeader"];
    $options["WebSocketsSupport"]=$_POST["WebSocketsSupport"];

    $wafwhite=intval($_POST["wafwhite"]);
    $cachetime=intval($_POST["cachetime"]);
    $cacheargs=intval($_POST["cacheargs"]);
    $bcacheimages=intval($_POST["bcacheimages"]);
    $bcachehtml=intval($_POST["bcachehtml"]);
    $bcachebinaries=intval($_POST["bcachebinaries"]);


    $options=base64_encode(serialize($options));
    $directory=$_POST["directory"];
    if( $directory =="/"){
        echo $tpl->post_error(" / {bad_configuration}");
        return true;
    }

    $q=new lib_sqlite(NginxGetDB());
    $directory = $q->sqlite_escape_string2($directory);
    $enabled = intval($_POST["enabled"]);
    $options = $q->sqlite_escape_string2($options);
    $deny=$_POST["deny"];

    $sql="UPDATE ngx_directories SET
            deny=$deny,
            directory='$directory',
            enabled=$enabled,
            options='$options',
            wafwhite=$wafwhite,
            cachetime=$cachetime,
            cacheargs=$cacheargs,
            bcacheimages=$bcacheimages,
            bcachehtml=$bcachehtml,
            bcachebinaries=$bcachebinaries
          WHERE ID=$ID";


    $get_servicename=get_servicename($ID);

    if($ID==0){

        if(intval($_POST["serviceid"])==0){
            echo $tpl->post_error("serviceid = {$_POST["serviceid"]}, corrupted post");
            return false;
        }

        $_keys[]="directory";
        $_keys[]="serviceid";
        $_keys[]="deny";
        $_keys[]="enabled";
        $_keys[]="options";
        $_keys[]="wafwhite";
        $_keys[]="cachetime";
        $_keys[]="cacheargs";

        $_keys[]="bcacheimages";
        $_keys[]="bcachehtml";
        $_keys[]="bcachebinaries";

        $f_vals[]="'$directory'";
        $f_vals[]=intval($_POST["serviceid"]);
        $f_vals[]=$deny;
        $f_vals[]=$enabled;
        $f_vals[]="'$options'";
        $f_vals[]=$wafwhite;
        $f_vals[]=$cachetime;
        $f_vals[]=$cacheargs;
        $f_vals[]=$bcacheimages;
        $f_vals[]=$bcachehtml;
        $f_vals[]=$bcachebinaries;

        $sql=sprintf("INSERT INTO ngx_directories(%s) VALUES(%s)",@implode(",",$_keys),@implode(",",$f_vals));
        $get_servicename=intval($_POST["serviceid"]);
    }


    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error."\n$sql");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($_POST["serviceid"]);
    return admin_tracks_post("Add or Modify Web service Path $get_servicename");

}

function getDirList($serviceid):array{
    $tpl=new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API_NGINX("/reverse-proxy/checkdirs/$serviceid");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error($tpl->_ENGINE_parse_body(json_last_error_msg()));
        return array();
    }


    if (!$json->Status){
        $json->Error=str_replace("::","<br>",$json->Error);
        $json->Traces=str_replace("::","<br>",$json->Traces);
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->Error."<br>".$json->Traces));
        return array();
    }
    $MAIN=array();
    $tb=explode(",",$json->Info);
    foreach ($tb as $line){
        $MAIN[$line]=true;
    }
return $MAIN;
}

function table():bool{
    $page=CurrentPageName();
    $search="";
    $tpl=new template_admin();
    $serviceid=intval($_GET["table"]);
    $DirectoriesStatus=getDirList($serviceid);
     $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    if(isset($_GET["search"])) {
        $search = $_GET["search"];
    }
    $refreshjs="";
    if(isset($_GET["refreshjs"])) {
        $refreshjs = $_GET["refreshjs"];
    }
    $nginxsockGen=new socksngix(0);
    $nginxCachesDir=intval($nginxsockGen->GET_INFO("nginxCachesDir"));

    $forcediv="directory-restart-div$serviceid";

    $topbuttons[] = array("Loadjs('$page?directory-js=0&serviceid={$_GET["table"]}&md5=&function=$function');",
        ico_plus,"{new_path}");

    if(!isHarmpID()) {
        $topbuttons[] = array("Loadjs('fw.nginx.apply.php?serviceid=$serviceid&function=$function&addjs=$refreshjs');", ico_save, "{build_configuration}");
    }

    $buttonsjs=base64_encode($tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons)));

    $html[]="<div id='$forcediv'></div>";
    $html[]="<table id='table-ngx_stream_access_module-$serviceid' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{paths}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=false>{enabled}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT * FROM ngx_directories WHERE serviceid='{$_GET["table"]}' ORDER BY directory");
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return false;}




    $TRCLASS=null;
    $ActiveCertif=ActiveCertif($serviceid);
    $LockCertif=LockCertif($serviceid);
    if($search=="*"){$search="";}

    if(strlen($search)==0) {
        list($TRCLASS, $row) = bulk_redirects($TRCLASS, $serviceid);
        $html[] = $row;
    }

    $search=str_replace(".","\.",$search);
    $search=str_replace("*",".*?",$search);

    foreach ($results as $md5=>$ligne){
        $error=null;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $status="<i class='".ico_directory."'></i>";
        $md5            = md5(serialize($ligne).$md5);
        $ID             = $ligne["ID"];
        $options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
        $ico_ssl_client="<i class='fad fa-user-lock' style='color:#CCCCCC'></i>";
        if(!$ActiveCertif){
            $ico_ssl_client="&nbsp;";
        }
        if(!isset($options["sslclient"])){
            $options["sslclient"]=0;
        }
        $js="Loadjs('$page?directory-js=$ID&serviceid={$_GET["table"]}&md5=$md5&function=$function')";

        if(!isHarmpID()) {
            $status = "<span class='label label-default'>{inactive}</span>";
            if(isset($DirectoriesStatus[$ID])) {
                $status = "<span class='label label-primary'>{active2}</span>";
            }
        }

        if($search<>null){
            if(!preg_match("#$search#",$ligne["directory"])){
                continue;
            }
        }

        $explain = explain_this($ligne,$nginxCachesDir);
        if($ligne["directory"]=="/"){
                $error="&nbsp;<span class='label label-danger'>{bad_configuration} &laquo;/&raquo;</span>&nbsp;";
        }

        if($LockCertif){
            $options["sslclient"]=0;
        }
        if($options["sslclient"]==1){
            $ico_ssl_client="<i class='fad fa-user-lock'  style='color:#18a689'></i>";
        }

        $html[]="<tr class='$TRCLASS' id='$md5'>";
        $html[]="<td style='width:1%;vertical-align: top !important;' class='center'>$status</td>";
        $html[]="<td style='width:99%'>". $tpl->td_href($ligne["directory"],null,$js)."&nbsp;&nbsp;$error<span id='itemsofdir-$ID'>$explain</span></td>";
        $html[]="<td style='width:1%' class='center'>$ico_ssl_client</td>";
        $html[]="<td style='width:1%' class='center'>". $tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled=$ID&md5=$md5&function=$function')",null,"AsSystemWebMaster")."</td>";
        $html[]="<td style='width:1%' class='center'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md5=$md5')","AsSystemWebMaster")."</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="document.getElementById('nginx-dirs-$serviceid').innerHTML=base64_decode('$buttonsjs');";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function explain_this($ligne,$nginxCachesDir):string{
    $ID=$ligne["ID"];
    $wafwhite=intval($ligne["wafwhite"]);
    $options=unserialize(base64_decode($ligne["options"]));
    $deny           = intval($ligne["deny"]);
    $cachetime      = intval($ligne["cachetime"]);
    $items          = items($ID);
    $CountOfitems=count($items);

    if(!isset($options["REDIRECT_HTTP"])){
        $options["REDIRECT_HTTP"]=null;
    }

    if($CountOfitems==0){
        $deny_text="<span class='text-success'>{allow}</span>";
        if($deny==1){
            return "<span class='text-danger'>{deny}</span>";
        }
    }else{
        $itemslist=@implode(", ",$items);
        $deny_text      = "<span class='text-success'>{allow_access_except}</span> ($CountOfitems {items}) $itemslist";

        if($deny==1){
            $deny_text="<span class='text-danger'>{deny_access_except}</span> $itemslist";
        }
    }
    $f[]=$deny_text;

    if(!isset($ligne["bcacheimages"])){$ligne["bcacheimages"]=0;}
    if(!isset($ligne["bcachehtml"])){$ligne["bcachehtml"]=0;}
    if(!isset($ligne["bcachebinaries"])){$ligne["bcachebinaries"]=0;}

    $bcacheimages=$ligne["bcacheimages"];
    $bcachehtml=$ligne["bcachehtml"];
    $bcachebinaries=$ligne["bcachebinaries"];
    $bcaches=array();
    $icoie=ico_ie;

    if($bcacheimages>0){
        $bcaches[]="{cache_images} {$bcacheimages}h";
    }
    if($bcachehtml>0){
        $bcaches[]="{cache_htmlext} {$bcachehtml}h";
    }
    if($bcachebinaries>0){
        $bcaches[]="{cache_binaries} {$bcachebinaries}h";
    }

    if(count($bcaches)>0){
        $f[]="<span style='color:black;'><i class='$icoie' style='color:#1ab394'></i>&nbsp;{browser_caching}: ".@implode(", ", $bcaches)."</span>";
    }

    if($cachetime>0){
        $ico=ico_database;
        if ($nginxCachesDir==0){
            $f[]="<span style='color:#999;'><i class='$ico' style='color:#999'></i>&nbsp;{cachetime} {$cachetime}mn ({disabled})</span>";
        }else{
            $cacheargs="";
            if(intval($ligne["cacheargs"])==1){
                $cacheargs=" <small>({cache_arguments}))</small>";
            }
            $f[]="<span style='color:black;font-weight: bold'><i class='$ico' style='color:#1ab394'></i>&nbsp;{cachetime} {$cachetime}mn$cacheargs</span>";
        }

    }

    if(!isset($options["REDIRECT_HTTP"])){
        $options["REDIRECT_HTTP"]=null;
    }
    if(!isset($options["proxy_http_version"])){
        $options["proxy_http_version"]="1.0";
    }
    if(!isset($options["HostHeader"])){
        $options["HostHeader"]="";
    }
    if(!isset($options["WebSocketsSupport"])){
        $options["WebSocketsSupport"]=0;
    }

    if($options["REDIRECT_HTTP"]<>null){
        $s=array();
        $s[]="{and} {redirect_to} ";
        if (substr($options["REDIRECT_HTTP"],0,1)=="/"){
            $s[]="{alias} ";
        }
        $s[]="<strong>{$options["REDIRECT_HTTP"]}</strong>";
        $f[]=@implode(" ",$s);
        return @implode("<br>",$f);
    }

    $tt=array();
    if($options["WebSocketsSupport"]==1){
        $options["proxy_http_version"]="1.1";
    }
    $tt[]="{proxy_http_version} {$options["proxy_http_version"]}";
    if($options["WebSocketsSupport"]==1){
        $tt[]="<strong>{websockets_support}</strong>";
    }

    if(strlen($options["HostHeader"])>0){
        $tt[]="{HostHeader}: <strong>{$options["HostHeader"]}</strong>";
    }

    $ttopts=@implode(", ",$tt);
    $itemproxies=items_proxies($ID);
    VERBOSE("ItemsProxies: ".count($itemproxies),__LINE__);
    if(count($itemproxies)>0){
        $f[]="$ttopts {and} {reverse_proxy} ".@implode(", ",$itemproxies);
    }


    $ifModSecurityEnabled=ifModSecurityDisabled($ligne["serviceid"]);
    if(!$ifModSecurityEnabled) {
        if ($wafwhite == 1) {
            $ico=ico_shield_disabled;
            $f[] = "<i class='$ico'></i>&nbsp;{wafwhitepathoff}";
        } else {
            $ico=ico_shield;
            $f[] = "<i class='$ico' style='color:#1ab394'></i>&nbsp;{wafwhitepathon}";
        }
    }
    return @implode("<br>",$f);

}

function items_proxies($directoryid):array{
    $tt=array();
    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT * FROM  directories_backends WHERE directory_id='$directoryid'");

    foreach ($results as $index=>$ligne) {
        $port = $ligne["port"];
        $hostname = $ligne["hostname"];
        $options = unserialize(base64_decode($ligne["options"])); //UseSSL
        $UseSSL = $options["UseSSL"];
        $proto = "http";
        if ($UseSSL == 1) {
            $proto = "https";
        }
        $hostname = "$proto://$hostname:$port" . $ligne["root"];
        $tt[]=$hostname;
    }
    return $tt;
}


function items($directoryid):array{
    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT * FROM ngx_subdir_items WHERE directoryid='$directoryid' ORDER BY zorder");
    $tt=array();
    if($results) {
        foreach ($results as $index => $ligne) {
            $tt[] = $ligne["item"];
        }
    }

    return $tt;

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

function bulk_redirects($TRCLASS,$serviceid):array{
    $tpl=new template_admin();
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $sock       = new socksngix($serviceid);
    $BulkRedirects=intval($sock->GET_INFO("BulkRedirects"));
    $data       = unserialize(base64_decode($sock->GET_INFO("FBulkRedirectsData")));
    if(!is_array($data)){
        $data=array();
    }
    if($BulkRedirects==0){
        $data=array();
    }
    $text=$tpl->_ENGINE_parse_body("{redirectsCounts}");
    $text=sprintf($text,count($data));
    $md5=md5(serialize($data));
    $status = "<span class='label label-default'>{inactive}</span>";
    if($BulkRedirects==1) {
        $status = "<span class='label label-primary'>{active2}</span>";
    }

    $js="Loadjs('fw.nginx.bulkredirects.php?function=$function&service-js=$serviceid')";
    $html[]="<tr class='$TRCLASS' id='$md5'>";
    $html[]="<td style='width:1%;vertical-align: top !important;' class='center'>$status</td>";
    $html[]="<td style='width:99%'>". $tpl->td_href($text,null,$js)."</td>";
    $html[]="<td style='width:1%' class='center'>&nbsp;</td>";
    $html[]="<td style='width:1%' class='center'>&nbsp;</td>";
    $html[]="<td style='width:1%' class='center'>&nbsp;</td>";
    $html[]="</tr>";
    return array($TRCLASS,@implode("\n",$html));

}