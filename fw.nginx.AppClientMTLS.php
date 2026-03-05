<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["generate-js"])){generate_js();exit;}
if(isset($_GET["generate-popup"])){generate_popup();exit;}
if(isset($_POST["email"]) && (isset($_POST["service_ids"]) || isset($_POST["service_id"]))){generate_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete-config"])){delete_perform();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["certificate-view-js"])){certificate_view_js();exit;}
if(isset($_GET["certificate-view-popup"])){certificate_view_popup();exit;}
if(isset($_GET["start"])){start();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{admin_clients}",ico_admin,
        "{admin_clients_explain}","$page?start=yes",
        "admin-clients","progress-appreverse-restart",false);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{admin_clients}",$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);
}

function start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&search-configs=yes");
    echo "</div>";
}

function _fetch_sites_hash(){
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/ids");
    $json=json_decode($raw);
    $sites=array();
    if(isset($json->Status) && $json->Status && isset($json->sites)){
        foreach($json->sites as $id=>$host){
            $sites[intval($id)]=htmlspecialchars($host)." (#$id)";
        }
    }
    return $sites;
}

function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $search=trim($tpl->CLEAN_BAD_XSS($_GET["search"] ?? ""));
    $function=$_GET["function"] ?? "";

    if($search=="*" || $search==""){
        $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/appclient-mtls/list");
    }else{
        $q=urlencode($search);
        $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/appclient-mtls/search?q=$q");
    }

    $json=json_decode($raw);
    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->Error ?? "{error}"));
        return;
    }

    $records=array();
    if(isset($json->Data) && is_array($json->Data)){
        $records=$json->Data;
    }

    $t=time();
    $html[]="<table id='table-$t' class='footable table table-stripped' data-page-size='100'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{email}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{label}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{rules}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{expire}</th>";
    $html[]="<th data-sortable=false style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=false style='width:1%'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $icon_cert='<i class="fas fa-file-certificate" style="color:#1ab394;font-size:18px"></i>';
    $TRCLASS=null;

    foreach($records as $rec){
        $ID=intval($rec->id);
        $email=htmlspecialchars($rec->email ?? "");
        $label=htmlspecialchars($rec->label ?? "");
        $service_id=intval($rec->service_id ?? 0);
        $service_ids_str=htmlspecialchars($rec->service_ids ?? "");
        if(strlen($service_ids_str)<1){$service_ids_str=(string)$service_id;}
        $created_at=htmlspecialchars($rec->created_at ?? "");
        $expires_at=htmlspecialchars($rec->expires_at ?? "");
        $cert_pem=$rec->cert_pem ?? "";
        $md=md5("mtls-$ID-$email");

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $email_link=$email;
        if(strlen($cert_pem)>10){
            $email_link=$tpl->td_href($email,"{view_certificate}","Loadjs('$page?certificate-view-js=$ID')");
        }

        $download_btn=$tpl->icon_download("window.location.href='$page?download=$ID'","AsWebMaster");
        $delete_btn=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md&function=$function')","AsWebMaster");

        $expires_color=null;
        if(strlen($expires_at)>4){
            $exp_ts=strtotime($expires_at);
            if($exp_ts!==false && $exp_ts<time()){
                $expires_color="color:#ed5565;font-weight:bold";
            }
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'>$icon_cert</td>";
        $html[]="<td>$email_link</td>";
        $html[]="<td>$label</td>";
        $html[]="<td style='text-align:center'>$service_ids_str</td>";
        $html[]="<td nowrap>$created_at</td>";
        $html[]="<td nowrap><span style='$expires_color'>$expires_at</span></td>";
        $html[]="<td style='width:1%'>$download_btn</td>";
        $html[]="<td style='width:1%'>$delete_btn</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='8'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";

    $topbuttons[] = array("Loadjs('$page?generate-js=yes&function=$function');", ico_plus, "{new_client}");

    $Title="{admin_clients}";
    $TINY_ARRAY["TITLE"]=$Title;
    $TINY_ARRAY["ICO"]=ico_admin;
    $TINY_ARRAY["EXPL"]="{admin_clients_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable({";
    $html[]="\"filtering\": { \"enabled\": false },";
    $html[]="\"sorting\": { \"enabled\": true },";
    $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} }";
    $html[]="});});";
    $html[]="$jstiny";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function generate_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl->js_dialog7("{configuration}","$page?generate-popup=yes&function=$function",700);
}

function generate_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $host=$_SERVER["HTTP_HOST"];
    if(strpos($host,":")>0){
        $tb=explode(":",$host);
        $host=$tb[0];
    }

    // Fetch available sites for the multi-select picklist
    $sites_hash=_fetch_sites_hash();
    if(count($sites_hash)==0){
        // Fallback: show a plain numeric field if site list unavailable
        $form[]=$tpl->field_numeric("service_id","{rule}",1);
    }else{
        $form[]=$tpl->field_picklist($sites_hash,"service_ids","{rules}",array());
    }

    $form[]=$tpl->field_text("email","{email}",null,true,"{email_address}");
    $form[]=$tpl->field_text("username","{username}",null,false,"{username}");
    $form[]=$tpl->field_numeric("max_days","{max_days}",365);
    $form[]=$tpl->field_text("target_server","{server}",$host,false,"IP {or} hostname");
    $form[]=$tpl->field_numeric("remote_port","{port}",8080);

    $jsafter="dialogInstance7.close();$function();";

    echo $tpl->_ENGINE_parse_body($tpl->form_outside("",$form,
        "","{add}",$jsafter,"AsWebMaster"));
}

function generate_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_POST["label"]="{created}: ".date("Y-m-d H:i:s");

    // Parse service_ids from multi-select (comma-separated string) or fall back to service_id
    $service_ids_raw=trim($_POST["service_ids"] ?? "");
    $service_ids_array=array();
    if(strlen($service_ids_raw)>0){
        foreach(explode(",",$service_ids_raw) as $s){
            $v=intval(trim($s));
            if($v>0){$service_ids_array[]=$v;}
        }
    }
    // Backward compat: fall back to single service_id
    if(count($service_ids_array)==0){
        $v=intval($_POST["service_id"] ?? 1);
        if($v>0){$service_ids_array[]=$v;}
    }

    $first_id=count($service_ids_array)>0?$service_ids_array[0]:1;

    $data=array(
        "service_id"    => $first_id,
        "service_ids"   => $service_ids_array,
        "email"         => trim($tpl->CLEAN_BAD_XSS($_POST["email"] ?? "")),
        "label"         => trim($tpl->CLEAN_BAD_XSS($_POST["label"] ?? "")),
        "username"      => trim($tpl->CLEAN_BAD_XSS($_POST["username"] ?? "")),
        "max_days"      => intval($_POST["max_days"] ?? 365),
        "target_server" => trim($tpl->CLEAN_BAD_XSS($_POST["target_server"] ?? "")),
        "remote_port"   => intval($_POST["remote_port"] ?? 8080),
    );

    if(!filter_var($data["email"], FILTER_VALIDATE_EMAIL)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{email}: {$data["email"]} error"));
        return;
    }

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/appclient-mtls/generate",$data);
    $json=json_decode($raw);

    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->Error ?? "{error}"));
        return;
    }

    if(isset($json->error)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->error));
        return;
    }

    $ids_str=implode(",",$service_ids_array);
    admin_tracks("Generated mTLS config for {$data["email"]} service_ids=$ids_str");
}

function delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-js"]);
    $md=$_GET["md"] ?? "";
    $function=$_GET["function"] ?? "blur";
    $tpl->js_confirm_delete("mTLS Config #$ID","delete-config",$ID,"$('#$md').remove();");
}

function delete_perform(){
    $tpl=new template_admin();
    $ID=intval($_POST["delete-config"]);
    if($ID==0){return;}

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_DELETE("/appclient-mtls/delete/$ID");
    $json=json_decode($raw);

    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->Error ?? "{error}"));
        return;
    }

    if(isset($json->error)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->error));
        return;
    }

    admin_tracks("Deleted mTLS config #$ID");
}

function download(){
    $ID=intval($_GET["download"]);
    if($ID==0){return;}

    $unixSocketPath="/run/reverse-proxy.sock";
    $uri="http://localhost/appclient-mtls/download/$ID";

    $ch=curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $unixSocketPath);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $resp=curl_exec($ch);
    $http_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type=curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if($http_code!=200 || $resp===false){
        $json=json_decode($resp);
        $err="Download failed (HTTP $http_code)";
        if(isset($json->Error)){$err=$json->Error;}
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    $filename="mtls-config-$ID.enc";
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Length: ".mb_strlen($resp,'8bit'));
    header("Cache-Control: no-cache, no-store, must-revalidate");
    echo $resp;
}

function certificate_view_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["certificate-view-js"]);
    $tpl->js_dialog7("{view_certificate}","$page?certificate-view-popup=$ID",950);
}

function certificate_view_popup(){
    $tpl=new template_admin();
    $ID=intval($_GET["certificate-view-popup"]);

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/appclient-mtls/list");
    $json=json_decode($raw);

    $cert_pem=null;
    $email="";
    if(isset($json->Data) && is_array($json->Data)){
        foreach($json->Data as $rec){
            if(intval($rec->id)==$ID){
                $cert_pem=$rec->cert_pem ?? null;
                $email=$rec->email ?? "";
                break;
            }
        }
    }

    if($cert_pem==null || strlen($cert_pem)<10){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_certificate_data}"));
        return;
    }

    $array=openssl_x509_parse($cert_pem);
    if(!is_array($array)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{unable_to_parse_certificate}"));
        return;
    }

    $t=time();
    $html[]="<table id='table-$t' class='footable table table-stripped' data-page-size='100'>";
    $html[]="<thead><tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{value}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach($array as $key=>$val){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $lkey=strtolower($key);
        if(in_array($lkey,array("hash","version","serialnumberhex","validfrom","validto",
            "validfrom_time_t","signaturetypesn","signaturetypeln","signaturetypenid","serialnumber"))){
            continue;
        }

        if($lkey=="validto_time_t"){
            $key="{expire}";
            $val=$tpl->time_to_date($val);
        }

        if($lkey=="purposes"){
            $tt1=array();
            foreach($val as $val2){
                foreach($val2 as $val3){
                    if(is_numeric($val3)){continue;}
                    if(strlen($val3)==1){continue;}
                    $tt1[]="<label class='label label-default'>".htmlspecialchars($val3)."</label>";
                }
            }
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $html[]="<tr class='$TRCLASS'>";
            $html[]="<td style='width:1%;vertical-align:top !important;text-transform:capitalize'><strong>$key</strong>:</td>";
            $html[]="<td style='width:99%;padding-left:10px' nowrap><span class='font-bold'>".@implode(" ",$tt1)."</span></td>";
            $html[]="</tr>";
            continue;
        }

        if(is_array($val)){
            $val=cert_view_table($val);
        }else{
            $val=htmlspecialchars((string)$val);
        }
        $key_safe=htmlspecialchars($key);
        $md=md5("$key$val");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%;vertical-align:top !important;text-transform:capitalize'>$key_safe:</td>";
        $html[]="<td style='width:99%;padding-left:10px' nowrap><span class='font-bold'>$val</span></td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='2'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable({";
    $html[]="\"filtering\": { \"enabled\": false },";
    $html[]="\"sorting\": { \"enabled\": true },";
    $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} }";
    $html[]="});});";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function cert_view_table($array):string{
    $html2[]="<table style='width:100%'>";
    foreach($array as $a=>$b){
        if(is_array($b)){$b=cert_view_table($b);}
        if(is_numeric($a)){
            $b_safe=is_string($b)?htmlspecialchars($b):$b;
            $html2[]="<tr><td style='width:99%;padding-left:10px;vertical-align:top' colspan='2'><span class='font-bold'>$b_safe</span></td></tr>";
            continue;
        }
        if(strtolower($a)=="keyusage" || strtolower($a)==strtolower("extendedKeyUsage")){
            $ttb=array();
            foreach(explode(",",$b) as $c){
                $ttb[]="<label class='label label-primary'>".htmlspecialchars(trim($c))."</label>";
            }
            $b=@implode(" ",$ttb);
            $a_safe=htmlspecialchars($a);
            $html2[]="<tr>";
            $html2[]="<td style='width:1%;vertical-align:top;padding-top:5px'><strong>$a_safe:</strong></td>";
            $html2[]="<td style='width:99%;padding-left:10px;padding-top:5px' nowrap>$b</td>";
            $html2[]="</tr>";
            continue;
        }
        if(strtolower($a)==strtolower("authorityKeyIdentifier")){continue;}
        if(strtolower($a)==strtolower("basicConstraints") && $b=="CA:FALSE"){continue;}
        $a_safe=htmlspecialchars($a);
        $b_safe=is_string($b)?htmlspecialchars($b):$b;
        $html2[]="<tr>";
        $html2[]="<td style='width:1%;vertical-align:top;padding-top:5px'><strong>$a_safe:</strong></td>";
        $html2[]="<td style='width:99%;padding-left:10px;padding-top:5px' nowrap>$b_safe</td>";
        $html2[]="</tr>";
    }
    $html2[]="</table>";
    return @implode("",$html2);
}
