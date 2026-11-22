<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["clients-list"])){clients_list();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["delete-server"])){delete_server();exit;}
if(isset($_POST["delete-server"])){delete_server_perform();exit;}
if(isset($_GET["certificate-view-js"])){certificate_view_js();exit;}
if(isset($_GET["certificate-view-popup"])){certificate_view_popup();exit;}
if(isset($_GET["button-page"])){button_page();exit;}
if(isset($_GET["clients-list"])){clients_list();exit;}




page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $service_reconfigure="NgixSitesReconfigure();";
    $service_reconfigure_all="Loadjs('$page?reconfigure-all-js=yes');";

    $html=$tpl->page_header("{client_certificates}","fas fa-file-certificate","<div id='button-page'></div>",null,
        "client-certificates","progress-certificates-restart",true);
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{client_certificates}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}
function certificate_view_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["certificate-view-js"]);
    $client=null;
    if(isset($_GET["client"])){$client="&client=yes";}
    if(isset($_GET["client"])){
        $ligne=fetch_client_cert_by_id($ID);
        $CertificateName=isset($ligne["ClientName"])?$ligne["ClientName"]:"#$ID";
    }else{
        $ligne=fetch_server_cert_by_id($ID);
        $CertificateName=isset($ligne["CertificateName"])?$ligne["CertificateName"]:"#$ID";
    }
    $tpl->js_dialog7("{view}: $CertificateName","$page?certificate-view-popup=$ID$client",950);
}

function button_page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $html_bts[]="<p>{generate_x509_client_explain}</p>";

    $topbuttons[]=array("Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-server-generate=0&function=$function');",
        ico_plus,"{create_ca_certificate}");

    $topbuttons[]=array("Loadjs('fw.nginx.ban.clientsCerts.php?serverid=0&function=$function');",
        ico_plus,"{ban_clients}");

    $html_bts[]=$tpl->table_buttons($topbuttons);
    echo $tpl->_ENGINE_parse_body($html_bts);
    return true;

}
function certificate_view_popup_table($array):string{
    $html2[]="<table style='width:100%'>";
    // Pentest 2026-11-04 : champs du certificat client (donnee externe) affiche sans encodage (XSS stockee).
    foreach ($array as $a=>$b) {
        if (is_array($b)) {$b=certificate_view_popup_table($b);}else{$b=req_html($b);}
        if(is_numeric($a)){
            $html2[] = "<tr>";
            $html2[] = "<td style='width:99%;padding-left: 10px;vertical-align: top' nowrap colspan='2'><span class='font-bold'>$b</span></td>";
            $html2[] = "</tr>";
            continue;
        }

        if(strtolower($a)=="keyusage"){
            $keyUsageAr=explode(",",$b);
            foreach ($keyUsageAr as $c){
                $ttb[]="<label class='label label-primary'>$c</label>";
            }
            $b=@implode(" ",$ttb);
        }

        if(strtolower($a)==strtolower("extendedKeyUsage")){
            $keyUsageAr=explode(",",$b);
            $ttb=array();
            foreach ($keyUsageAr as $c){
                $ttb[]="<label class='label label-primary'>$c</label>";
            }
            $b=@implode(" ",$ttb);
        }
        if(strtolower($a)==strtolower("authorityKeyIdentifier")){
            continue;
        }
        if(strtolower($a)==strtolower("basicConstraints")){
            if($b=="CA:FALSE"){
                continue;
            }
            $b="<span class='label label-info'>{CA_CERTIFICATE}</span>";
        }


        $html2[] = "<tr>";
        $html2[] = "<td style='width:1%;vertical-align:top;padding-top: 5px'><strong>".req_html($a).":</strong></td>";
        $html2[] = "<td style='width:99%;padding-left: 10px;padding-top: 5px' nowrap>$b</td>";
        $html2[] = "</tr>";
    }
    $html2[] = "</table>";
    return @implode("",$html2);
}
function certificate_view_popup(){
    $tpl=new template_admin();
    $ID=intval($_GET["certificate-view-popup"]);

    if(isset($_GET["client"])){
        $ligne=fetch_client_cert_by_id($ID);
        $ca_crt=base64_decode(isset($ligne["user_crt"])?$ligne["user_crt"]:"");
    }else{
        $ligne=fetch_server_cert_by_id($ID);
        $ca_crt=base64_decode(isset($ligne["ca_crt"])?$ligne["ca_crt"]:"");
    }
    $t=time();
    $array=openssl_x509_parse($ca_crt);
    // Vue de detail cle/valeur: awc-kv-k aligne la colonne de cles.
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th class='awc-kv-k'>{type}</th>";
    $html[]="<th>{value}</th>";
    $html[]="</tr></thead><tbody>";
    $tt1=array();
    foreach ($array as $key=>$val){
        $TRCLASS=null;
        if(strtolower($key)=="hash"){continue;}
        if(strtolower($key)=="version"){continue;}
        if(strtolower($key)==strtolower("serialNumberHex")){continue;}
        if(strtolower($key)==strtolower("validFrom")){continue;}
        if(strtolower($key)==strtolower("validTo")){continue;}
        if(strtolower($key)==strtolower("validFrom_time_t")){continue;}
        if(strtolower($key)==strtolower("signatureTypeSN")){continue;}
        if(strtolower($key)==strtolower("signatureTypeLN")){continue;}
        if(strtolower($key)==strtolower("signatureTypeNID")){continue;}
        if(strtolower($key)==strtolower("serialNumber")){continue;}

        if(strtolower($key)==strtolower("validTo_time_t")){
            $key="{expire}";
            $val=$tpl->time_to_date($val);
        }



        if(strtolower($key)=="purposes"){
            $html[]="<tr id='Purposes'>";
            $html[]="<td class='awc-kv-k' style='vertical-align:top'>".htmlspecialchars((string)$key)."</td>";
            foreach ($val as $index=>$val2){
                foreach ($val2 as $index=>$val3) {
                    if (is_numeric($val3)) {
                        continue;
                    }
                    if(strlen($val3)==1){continue;}
                    $tt1[] = "<span class='awc-tag'>".htmlspecialchars((string)$val3)."</span>";
                }

            }
            $html[]="<td><span class='font-bold'>".@implode(" ",$tt1)."</span></td>";
            $html[]="</tr>";
            continue;
        }


        // Pentest 2026-11-04 : champs du certificat client (donnee externe) affiche sans encodage (XSS stockee).
        if(is_array($val)){$val=certificate_view_popup_table($val);}else{$val=req_html($val);}
        $md=md5($val);



        if($key=="validFrom_time_t"){$val="$val (".$tpl->time_to_date($val).")"; }
        if($key=="validTo_time_t"){$val="$val (".$tpl->time_to_date($val).")"; }

        $html[]="<tr id='$md'>"
            ."<td class='awc-kv-k' style='vertical-align:top'>".htmlspecialchars((string)$key)."</td>"
            ."<td><span class='font-bold'>$val</span></td></tr>";

    }

    // L'ancien pied fermait un </tfoot> JAMAIS OUVERT, n'emettait aucun
    // </tbody>, et donnait colspan='3' a un tableau de DEUX colonnes.
    $html[]="</tbody></table></div></div></div>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
}


function delete_server(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-server"]);
    $md=$_GET["md"];
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ligne=fetch_server_cert_by_id($ID);
    $CertificateName=isset($ligne["CertificateName"])?$ligne["CertificateName"]:"#$ID";
    $tpl->js_confirm_delete($CertificateName,"delete-server",$ID,"$('#$md').remove();$function();");
}
function delete_server_perform(){
    $ID=intval($_POST["delete-server"]);
    $ligne=fetch_server_cert_by_id($ID);
    $CertificateName=isset($ligne["CertificateName"])?$ligne["CertificateName"]:"#$ID";

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"delete","table"=>"nginx_clients_certs","where"=>array("certid"=>"$ID")));

    // Disable client-cert auth on every site that referenced this CA.
    foreach(fetch_service_ids_with_param("ssl_client_certificate",$ID) as $serviceid){
        $sockngix=new socksngix($serviceid);
        $sockngix->SET_INFO("EnableClientCertificate",0);
        $sockngix->SET_INFO("ssl_client_certificate",0);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"delete","table"=>"nginx_servers_certs","where"=>array("ID"=>"$ID")));
    admin_tracks("Deleted Server Certificate $CertificateName #$ID");
}

function search():bool{
    $page=CurrentPageName();
    // Daemon owns the schema (nginx_clients_certs is created in nginxsites.FixTables).
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $search=$_GET["search"];
    if($search=="*"){
        $search="";

    }
    if(strpos($search,"*")>0){
        $search=str_replace("*","%",$search);
    }

    $searchEncoded=urlencode($search);

    // L'ancien en-tete declarait CINQ colonnes pour des lignes de SEPT cellules:
    // les deux colonnes d'actions n'avaient aucun intitule et tout le tableau
    // etait decale de deux crans.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{CertificateName}</th>";
    $html[]="<th>{websites}</th>";
    $html[]="<th class='center'>{created}</th>";
    $html[]="<th class='center'>{expire}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";

    $icon_server='<i class="fad fa-server"></i>';

    $scripts=array();
    $TRCLASS=null;

    // Build the candidate set of server-cert IDs. With a search term, the
    // legacy SQL did a JOIN: a server cert matches if its name matches OR if
    // any of its client certs' names match. /nginx-db/query has no JOIN; we
    // emulate by pulling both lists and computing the union in PHP.
    $needle=strtolower(str_replace("%","",strval($search)));
    $serverCerts=fetch_server_certs_all();
    $MAIN=array();
    if(strlen($needle)>0){
        foreach($serverCerts as $sr){
            $sid=isset($sr["ID"])?intval($sr["ID"]):0;
            $name=isset($sr["CertificateName"])?strtolower($sr["CertificateName"]):"";
            if($sid>0 && strpos($name,$needle)!==false){
                $MAIN[$sid]=true;
            }
        }
        // Also match server certs whose client certs contain the needle.
        $cliResp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_clients_certs");
        $cliData=json_decode($cliResp,true);
        if(is_array($cliData) && !empty($cliData["Status"]) && isset($cliData["rows"])){
            foreach($cliData["rows"] as $cr){
                $cn=isset($cr["ClientName"])?strtolower($cr["ClientName"]):"";
                if(strpos($cn,$needle)===false){continue;}
                $cid=isset($cr["certid"])?intval($cr["certid"]):0;
                if($cid>0){$MAIN[$cid]=true;}
            }
        }
    }else{
        foreach($serverCerts as $sr){
            $sid=isset($sr["ID"])?intval($sr["ID"]):0;
            if($sid>0){$MAIN[$sid]=true;}
        }
    }
    if(count($MAIN)>150){$MAIN=array_slice($MAIN,0,150,true);}

    // Index server certs by ID so the loop below can look up rows without
    // another round-trip per certificate.
    $serverCertById=array();
    foreach($serverCerts as $sr){
        $sid=isset($sr["ID"])?intval($sr["ID"]):0;
        if($sid>0){$serverCertById[$sid]=$sr;}
    }

    foreach ($MAIN as $ID=>$none){
        $color=null;
        $ligne=isset($serverCertById[$ID])?$serverCertById[$ID]:fetch_server_cert_by_id($ID);
        $CertificateName=isset($ligne["CertificateName"])?$ligne["CertificateName"]:"#$ID";
        $md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        // openssl_x509_parse() rend FALSE sur un certificat illisible: indexer
        // FALSE emet un avertissement en PHP 8 pour chacune des trois lectures.
        $ssl_array=openssl_x509_parse(base64_decode((string)($ligne["ca_crt"] ?? "")));
        if(!is_array($ssl_array)){$ssl_array=array();}
        $notAfter=intval($ssl_array["validTo_time_t"] ?? 0);
        $validTo_time_t=$notAfter>0?$tpl->time_to_date($notAfter):"-";
        $validFrom_time_t=intval($ssl_array["validFrom_time_t"] ?? 0)>0
            ?$tpl->time_to_date(intval($ssl_array["validFrom_time_t"])):"-";
        $distanceOfTimeInWords=$notAfter>0?distanceOfTimeInWords(time(),$notAfter):"";
        // $SERVERS_ID[]="( OR certid=$ID )" etait construit ici -- un fragment SQL
        // debutant par OR -- et n'etait relu NULLE PART.
        $delete=$tpl->icon_delete("Loadjs('$page?delete-server=$ID&md=$md&function=$function')","AsWebMaster");

        $CertificateName=$tpl->td_href($CertificateName,"{view}","Loadjs('$page?certificate-view-js=$ID')");
        $icon_add=$tpl->icon_add("Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-create-js=$ID&function=$function')","AsWebMaster");

        $websites=GetSitesNames($ID);
        if(strlen($websites)>4){
            $delete="&nbsp;";
        }
        // Un <H2> etait imbrique DANS une cellule, nowrap='' portait une valeur
        // vide, et l'identifiant de la sous-ligne s'ecrivait id=clients-2'$md'
        // -- non quote, puis une apostrophe egaree.
        $html[]="<tr id='$md'>"
            ."<td><div class='awc-ident'>"
            ."<span class='awc-ico'><i class='fad fa-server'></i></span>"
            ."<span class='awc-name'>$CertificateName</span>"
            ."</div></td>"
            ."<td>$websites</td>"
            ."<td class='center awc-nowrap awc-mono'>$validFrom_time_t</td>"
            ."<td class='center awc-nowrap awc-mono'>$validTo_time_t"
            .($distanceOfTimeInWords!==""?"<div class='awc-meta'>$distanceOfTimeInWords</div>":"")."</td>"
            ."<td><div class='awc-acts'>$icon_add$delete</div></td>"
            ."</tr>";
        $html[]="<tr id='clients-2-$md'>"
            ."<td colspan='5'><div id='ClientsForCert$ID' style='margin-left:30px;border:1px solid #bcc9c6;"
            ."border-radius:10px;background:#fff;padding-top:10px'></div></td>"
            ."</tr>";
        $scripts[]="LoadAjaxSilent('ClientsForCert$ID','$page?clients-list=$ID&function=$function&search=$searchEncoded&trclass=$TRCLASS');";
    }





    // Le colspan du pied s'ecrivait colspan=10' -- non quote, avec une
    // apostrophe fermante orpheline -- pour un tableau de cinq colonnes.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($MAIN)."</span></div>";
    $html[]="</div></div>";

    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n"
        ."LoadAjaxSilent('button-page','$page?button-page=yes&function=$function');\n"
        .@implode("\n",$scripts)."\n</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;
}
function clients_list():bool{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $search=$_GET["search"];
    $CertID=intval($_GET["clients-list"]);
    if($CertID==0){return true;}
    $serviceid=0;
    if(isset($_GET["serviceid"])) {
        $serviceid = intval($_GET["serviceid"]);
    }
    $tpl=new template_admin();
    $icon_user='<i class="fas fa-user-lock"></i>';
    $TRCLASS=$_GET["trclass"];

    $btn=null;
    if($serviceid>0) {
        $add = "Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-create-js=$CertID&serviceid=$serviceid&function=$function');";
        $topbuttons[] = array($add, ico_plus, "{create_client_certificate}");
        $btn = $tpl->th_buttons($topbuttons);
    }

    // Pull all client certs for this CA, then PHP-side filter by ClientName.
    // If the filter matches nothing, fall back to the unfiltered list (matches
    // the original behaviour: "show me everything when search is empty").
    $allRows=fetch_client_certs_for_cert($CertID);
    $needle=strtolower(str_replace("%","",strval($search)));
    if(strlen($needle)>0){
        $results=array_values(array_filter($allRows,function($r) use($needle){
            $n=isset($r["ClientName"])?strtolower($r["ClientName"]):"";
            return strpos($n,$needle)!==false;
        }));
        if(count($results)>150){$results=array_slice($results,0,150);}
        if(count($results)==0){$results=$allRows;}
    }else{
        $results=$allRows;
    }


    $html[]=$btn;
    // Les trois telechargements (PFX / PEM / TXT) occupaient trois colonnes
    // distinctes; ils tiennent dans la colonne d'actions, avec leur intitule en
    // infobulle.
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{CertificateName}</th>";
    $html[]="<th class='center'>{created}</th>";
    $html[]="<th class='center'>{expire}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";


    foreach ($results as $index=>$ligne){
        $color=null;
        $CertificateName=$ligne["ClientName"];
        $md=md5("CLIENT".serialize($ligne));
        $ID=$ligne["ID"];
        $delete=$tpl->icon_delete("Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-delete-js=$ID&md=$md&function=$function')","AsWebMaster");
        $TRCLASS=null;
        $icon_add=null;
        $icon_download=$tpl->icon_download("window.location.href='fw.nginx.sites.ServerCertificate.php?pfx=$ID'","AsWebMaster");
        $icon_download_pem=$tpl->icon_download("window.location.href='fw.nginx.sites.ServerCertificate.php?pfx=$ID&pem=yes'","AsWebMaster");
        $icon_download_txt=$tpl->icon_download("window.location.href='fw.nginx.sites.ServerCertificate.php?pfx=$ID&txt=yes'","AsWebMaster");

        // openssl_x509_parse() rend FALSE sur un certificat illisible.
        $ssl_array=openssl_x509_parse(base64_decode((string)($ligne["user_crt"] ?? "")));
        if(!is_array($ssl_array)){$ssl_array=array();}
        $notAfter=intval($ssl_array["validTo_time_t"] ?? 0);
        $validTo_time_t=$notAfter>0?$tpl->time_to_date($notAfter):"-";
        $validFrom_time_t=intval($ssl_array["validFrom_time_t"] ?? 0)>0
            ?$tpl->time_to_date(intval($ssl_array["validFrom_time_t"])):"-";
        $distanceOfTimeInWords=$notAfter>0?distanceOfTimeInWords(time(),$notAfter):"";

        $html[]="<tr id='$md'>"
            ."<td><div class='awc-ident'>"
            ."<span class='awc-ico'>$icon_user</span>"
            ."<span class='awc-name'>".htmlspecialchars((string)$CertificateName)."</span>"
            ."</div></td>"
            ."<td class='center awc-nowrap awc-mono'>$validFrom_time_t</td>"
            ."<td class='center awc-nowrap awc-mono'>$validTo_time_t"
            .($distanceOfTimeInWords!==""?"<div class='awc-meta'>$distanceOfTimeInWords</div>":"")."</td>"
            ."<td><div class='awc-acts'>$icon_download$icon_download_pem$icon_download_txt$delete</div></td>"
            ."</tr>";
    }
    // Ni </tbody> ni pied: seul </table> fermait ce tableau.
    $html[]="</tbody></table></div></div></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;

}
function GetSitesNames($certid):string{
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if(isset($GLOBALS["CertClientSitesNames"][$certid])){
        return $GLOBALS["CertClientSitesNames"][$certid];
    }

    $tt=array();
    foreach (fetch_service_ids_with_param("ssl_client_certificate",$certid) as $serviceid){
        $servicename=get_servicename($serviceid);
        $tt[]=$tpl->td_href($servicename,"","Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-js=$serviceid&function=$function')");
    }

    if(count($tt)==0){
        $GLOBALS["CertClientSitesNames"][$certid]="";
        return $GLOBALS["CertClientSitesNames"][$certid];}
    $GLOBALS["CertClientSitesNames"][$certid]= @implode(", ",$tt);
    return $GLOBALS["CertClientSitesNames"][$certid];
}

function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}