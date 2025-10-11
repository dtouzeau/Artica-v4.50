<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
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
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    if(isset($_GET["client"])){
        $ligne=$q->mysqli_fetch_array("SELECT ClientName FROM nginx_clients_certs WHERE ID=$ID");
        $CertificateName = $ligne["ClientName"];

    }else {
        $ligne = $q->mysqli_fetch_array("SELECT CertificateName FROM nginx_servers_certs WHERE ID=$ID");
        $CertificateName = $ligne["CertificateName"];

    }
    $tpl->js_dialog7("{view}: $CertificateName","$page?certificate-view-popup=$ID$client",950);
}

function button_page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
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
    foreach ($array as $a=>$b) {
        if (is_array($b)) {$b=certificate_view_popup_table($b);}
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
        $html2[] = "<td style='width:1%;vertical-align:top;padding-top: 5px'><strong>$a:</strong></td>";
        $html2[] = "<td style='width:99%;padding-left: 10px;padding-top: 5px' nowrap>$b</td>";
        $html2[] = "</tr>";
    }
    $html2[] = "</table>";
    return @implode("",$html2);
}
function certificate_view_popup(){
    $tpl=new template_admin();
    $ID=intval($_GET["certificate-view-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");

    if(isset($_GET["client"])){
        $ligne=$q->mysqli_fetch_array("SELECT ClientName,user_crt FROM nginx_clients_certs WHERE ID=$ID");
        $ca_crt = base64_decode($ligne["user_crt"]);
    }else {
        $ligne = $q->mysqli_fetch_array("SELECT CertificateName,ca_crt FROM nginx_servers_certs WHERE ID=$ID");
        $ca_crt = base64_decode($ligne["ca_crt"]);
    }
    $t=time();
    $array=openssl_x509_parse($ca_crt);
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{value}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($array as $key=>$val){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
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
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $html[]="<tr class='$TRCLASS' id='Purposes'>";
            $html[]="<td style='width:1%;vertical-align: top !important;text-transform: capitalize'><strong>$key</strong>:</td>";
            foreach ($val as $index=>$val2){
                foreach ($val2 as $index=>$val3) {
                    if (is_numeric($val3)) {
                        continue;
                    }
                    if(strlen($val3)==1){continue;}
                    $tt1[] = "<label class='label label-default'>$val3</label>";
                }

            }
            $html[]="<td style='width:99%;padding-left: 10px;' nowrap><span class='font-bold'>".@implode(" ",$tt1)."</span></td>";
            $html[]="</tr>";
            continue;
        }


        if(is_array($val)){$val=certificate_view_popup_table($val);}
        $md=md5($val);



        if($key=="validFrom_time_t"){$val="$val (".$tpl->time_to_date($val).")"; }
        if($key=="validTo_time_t"){$val="$val (".$tpl->time_to_date($val).")"; }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%;vertical-align: top !important;text-transform: capitalize'>$key:</td>";
        $html[]="<td style='width:99%;padding-left: 10px;' nowrap><span class='font-bold'>$val</span></td>";
        $html[]="</tr>";

    }

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false },";
    $html[]="\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}


function delete_server(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-server"]);
    $md=$_GET["md"];
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT CertificateName FROM nginx_servers_certs WHERE ID=$ID");
    $CertificateName=$ligne["CertificateName"];
    $tpl->js_confirm_delete($CertificateName,"delete-server",$ID,"$('#$md').remove();$function();");
}
function delete_server_perform(){
    $ID=$_POST["delete-server"];
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");

    $ligne=$q->mysqli_fetch_array("SELECT CertificateName FROM nginx_servers_certs WHERE ID=$ID");
    $CertificateName=$ligne["CertificateName"];
    $q->QUERY_SQL("DELETE FROM nginx_clients_certs WHERE certid='$ID'");

    $results=$q->QUERY_SQL("SELECT serviceid FROM service_parameters WHERE zkey='ssl_client_certificate' AND zvalue='$ID'");
    foreach ($results as $index=>$ligne){
        $sockngix=new socksngix($ligne["serviceid"]);
        $sockngix->SET_INFO("EnableClientCertificate",0);
        $sockngix->SET_INFO("ssl_client_certificate",0);
    }
    $q->QUERY_SQL("DELETE FROM nginx_servers_certs WHERE ID='$ID'");
    admin_tracks("Deleted Server Certificate $CertificateName #$ID");
}

function search():bool{
    $page=CurrentPageName();
    $q=new lib_sqlite(NginxGetDB());

    $sql="CREATE TABLE IF NOT EXISTS `nginx_clients_certs` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`certid` INTEGER,
	`ClientName` text,
	`levelenc` INTEGER NOT NULL DEFAULT 4096,
	`user_key` text,
	`user_crt` text,
	`user_pfx` text
	 )";
    $q->QUERY_SQL($sql);


    $tpl=new template_admin();
    $function=$_GET["function"];
    $search=$_GET["search"];
    if($search=="*"){
        $search="";

    }
    if(strpos($search,"*")>0){
        $search=str_replace("*","%",$search);
    }

    $searchEncoded=urlencode($search);

    $html[]="<table id='table-websites-main' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{CertificateName}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{websites}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{expire}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $icon_server='<i class="fad fa-server"></i>';

    $scripts=array();
    $TRCLASS=null;

    $sql="SELECT ID FROM nginx_servers_certs ORDER BY CertificateName LIMIT 150";
    if($search<>null){


        $sql="SELECT nginx_servers_certs.ID FROM nginx_servers_certs,nginx_clients_certs
        WHERE nginx_clients_certs.certid=nginx_servers_certs.ID
        AND ( (nginx_servers_certs.CertificateName LIKE '%$search%' ) OR (nginx_clients_certs.ClientName LIKE '%$search%' ) ) LIMIT 150";
    }
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error."<hr>$sql");
    }

    foreach ($results as $index=>$ligne){

        $MAIN[$ligne["ID"]]=true;
    }


    foreach ($MAIN as $ID=>$none){
        $color=null;
        $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_servers_certs WHERE ID=$ID");
        $CertificateName=$ligne["CertificateName"];
        $md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        $ssl_array=openssl_x509_parse(base64_decode($ligne["ca_crt"]));
        $validTo_time_t=$tpl->time_to_date($ssl_array["validTo_time_t"]);
        $validFrom_time_t=$tpl->time_to_date($ssl_array["validFrom_time_t"]);
        $distanceOfTimeInWords=distanceOfTimeInWords(time(),$ssl_array["validTo_time_t"]);
        $SERVERS_ID[]="( OR certid=$ID )";
        $delete=$tpl->icon_delete("Loadjs('$page?delete-server=$ID&md=$md&function=$function')","AsWebMaster");
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $CertificateName=$tpl->td_href($CertificateName,"{view}","Loadjs('$page?certificate-view-js=$ID')");
        $icon_add=$tpl->icon_add("Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-create-js=$ID&function=$function')","AsWebMaster");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'>$icon_server</td>";
        $html[]="<td style='width:1%' nowrap><span style='$color'><H2>$CertificateName</H2></span></td>";
        $websites=GetSitesNames($ID);
        if(strlen($websites)>4){
            $delete="&nbsp;";
        }
        $html[]="<td style='width:70%'><span style='$color'>$websites</span></td>";
        $html[]="<td style='width:1%' nowrap=''>$validFrom_time_t</td>";
        $html[]="<td style='width:1%' nowrap=''>$validTo_time_t <small>($distanceOfTimeInWords)</small></td>";
        $html[]="<td style='width:1%'>$icon_add</td>";
        $html[]="<td style='width:1%'>$delete</td>";
        $html[]="</tr>";
        $html[]="<tr style='background-color: white' id=clients-2'$md'>";
        $html[]="<td colspan=7><div id='ClientsForCert$ID' style='margin-left:30px;border:1px solid #dbd8d8;border-radius: 10px;background-color: white;padding-top:10px'></div></td>";
        $html[]="</tr>";
        $scripts[]="LoadAjaxSilent('ClientsForCert$ID','$page?clients-list=$ID&function=$function&search=$searchEncoded&trclass=$TRCLASS');";
    }





    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan=10'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="LoadAjaxSilent('button-page','$page?button-page=yes&function=$function');";
    $html[]=@implode("\n",$scripts);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function clients_list():bool{
    $function=$_GET["function"];
    $search=$_GET["search"];
    $CertID=intval($_GET["clients-list"]);
    if($CertID==0){return true;}
    $serviceid=0;
    if(isset($_GET["serviceid"])) {
        $serviceid = intval($_GET["serviceid"]);
    }
    $q=new lib_sqlite(NginxGetDB());
    $tpl=new template_admin();
    $icon_user='<i class="fas fa-user-lock"></i>';
    $TRCLASS=$_GET["trclass"];

    $sql="SELECT * FROM nginx_clients_certs WHERE certid='$CertID' ORDER BY ClientName";
    if($search<>null){
        $sql="SELECT * FROM nginx_clients_certs WHERE ClientName LIKE '%$search%' AND certid='$CertID' ORDER BY ClientName LIMIT 150";
    }
    $btn=null;
    if($serviceid>0) {
        $add = "Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-create-js=$CertID&serviceid=$serviceid&function=$function');";
        $topbuttons[] = array($add, ico_plus, "{create_client_certificate}");
        $btn = $tpl->th_buttons($topbuttons);
    }
    $results=$q->QUERY_SQL($sql);
    if(count($results)==0){
        $sql="SELECT * FROM nginx_clients_certs WHERE certid='$CertID' ORDER BY ClientName";
        $results=$q->QUERY_SQL($sql);
    }


    $html[]=$btn;
    $html[]="<table id='table-websites-$CertID' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{CertificateName}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{expire}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false>PFX</th>";
    $html[]="<th data-sortable=false>PEM</th>";
    $html[]="<th data-sortable=false>TXT</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    foreach ($results as $index=>$ligne){
        $color=null;
        $CertificateName=$ligne["ClientName"];
        $md=md5("CLIENT".serialize($ligne));
        $ID=$ligne["ID"];
        $delete=$tpl->icon_delete("Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-delete-js=$ID&md=$md&function=$function')","AsWebMaster");
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $icon_add=null;
        $icon_download=$tpl->icon_download("window.location.href='fw.nginx.sites.ServerCertificate.php?pfx=$ID'","AsWebMaster");
        $icon_download_pem=$tpl->icon_download("window.location.href='fw.nginx.sites.ServerCertificate.php?pfx=$ID&pem=yes'","AsWebMaster");
        $icon_download_txt=$tpl->icon_download("window.location.href='fw.nginx.sites.ServerCertificate.php?pfx=$ID&txt=yes'","AsWebMaster");

        $ssl_array=openssl_x509_parse(base64_decode($ligne["user_crt"]));
        $validTo_time_t=$tpl->time_to_date($ssl_array["validTo_time_t"]);
        $validFrom_time_t=$tpl->time_to_date($ssl_array["validFrom_time_t"]);
        $distanceOfTimeInWords=distanceOfTimeInWords(time(),$ssl_array["validTo_time_t"]);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'>$icon_user</td>";
        $html[]="<td style='width:99%' nowrap><span style='$color'>$CertificateName</span></td>";
        $html[]="<td style='width:1%' nowrap=''>$validFrom_time_t</td>";
        $html[]="<td style='width:1%' nowrap=''>$validTo_time_t <small>($distanceOfTimeInWords)</small></td>";

        $html[]="<td style='width:1%'>$icon_add</td>";
        $html[]="<td style='width:1%'>$icon_download</td>";
        $html[]="<td style='width:1%'>$icon_download_pem</td>";
        $html[]="<td style='width:1%'>$icon_download_txt</td>";
        $html[]="<td style='width:1%'>$delete</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
return true;

}
function GetSitesNames($certid):string{
    $tpl=new template_admin();
    $q=new lib_sqlite(NginxGetDB());
    $function=$_GET["function"];
    if(isset($GLOBALS["CertClientSitesNames"][$certid])){
        return $GLOBALS["CertClientSitesNames"][$certid];
    }

    $results=$q->QUERY_SQL("SELECT serviceid FROM service_parameters WHERE zkey='ssl_client_certificate' AND zvalue=$certid");
    $tt=array();
    foreach ($results as $index=>$ligne){
        $serviceid=intval($ligne["serviceid"]);
        if($serviceid==0){continue;}
        $servicename=get_servicename($serviceid);
        $tt[]=$tpl->td_href($servicename,"","Loadjs('fw.nginx.sites.ServerCertificate.php?client-certificate-js=$serviceid&function=$function')");
    }

    if(count($tt)==0){
        $GLOBALS["CertClientSitesNames"][$certid]="";
        return $GLOBALS["CertClientSitesNames"][$certid];}
    $GLOBALS["CertClientSitesNames"][$certid]= @implode(", ",$tt);
    return $GLOBALS["CertClientSitesNames"][$certid];

}

function HarmpID():int{
    if(!isset($_SESSION["HARMPID"])){return 0;}
    if(intval($_SESSION["HARMPID"])==0){
        return 0;
    }
    return intval($_SESSION["HARMPID"]);
}

function isHarmpID():bool{
    $HarmpID=HarmpID();
    if($HarmpID==0){return false;}

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return $ligne["servicename"];
}