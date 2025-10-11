<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsSquidAdministrator){die();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["port-id"])){port_id_js();exit;}
if(isset($_GET["port-id-popup"])){port_id_popup();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["step1"])){step1();exit;}
if(isset($_GET["step2"])){step2();exit;}
if(isset($_GET["step3"])){step3();exit;}
if(isset($_POST["CERTIFICATE"])){SAVE_CERTIFICATE();exit;}

js();

function js(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $tpl->js_dialog4("{activate_ssl_decryption}", "$page?start=yes",650);
}
function start(){
    $page = CurrentPageName();
    $html="<div id='activate_ssl_decryption'></div>
    <script>LoadAjaxTiny('activate_ssl_decryption','$page?step1=yes')</script>
";
    echo $html;
}

function port_id_js(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $port=intval($_GET["port-id"]);
    $tpl->js_dialog4("{listen_port} $port {activate_ssl_decryption}", "$page?port-id-popup=$port",650);
}
function port_id_popup(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $port=intval($_GET["port-id-popup"]);

    if($port==0){
        $html[]=$tpl->div_error("{certificate}||{no_backend_port_defined}");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }




    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT sslcertificate FROM proxy_ports WHERE ID=$port");
    $certificate=$ligne["sslcertificate"];
    if($certificate==null){
        $html[]=$tpl->div_error("{certificate} {listen_port} $port||{no_certificate}");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    $js="Loadjs('fw.certificates-center.php?certificate-js=$certificate')";
    $html[]=$tpl->div_explain("[$port] $certificate||{squid_wizard_ssl2}");
    $html[]="<center style='margin:30px'>";
    $html[]=$tpl->button_autnonome("{certificate_details}",$js,"fas fa-file-certificate",null,350);
    $html[]="</center>";


    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $sql="SELECT ID,pks12  FROM sslcertificates WHERE CommonName='$certificate'";
    $ligne=$q->mysqli_fetch_array($sql);
    $pks12=strlen($ligne["pks12"]);

    if($pks12>50) {
        $ID=$ligne["ID"];
        $js="document.location.href='fw.certificates-center.php?download-pfx=$ID'";
        $html[]="<center style='margin:30px'>";
        $html[]=$tpl->button_autnonome("{download} PFX",$js,"fa-download",null,350);
        $html[]="</center>";

    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;



}

function step1():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $html[]="<H1>{activate_ssl_decryption} (1)</H1>";
    $html[]=$tpl->div_wizard("{activate_ssl_decryption_1}");
    $form[]=$tpl->field_certificate("CERTIFICATE","{certificate}",null);


  /*
  Wizard non personnalisé, change vers wizard spécifique au proxy

  $newjs=$tpl->framework_buildjs("openssl.php?new-self-signed-auto=yes",
    "ufdberror.compile.progress",
    "ufdberror.compile.log","activate_ssl_decryption",
    "LoadAjaxTiny('activate_ssl_decryption','$page?step2=yes&new-cert=yes')");
*/

    $newjs="Loadjs('fw.proxy.ports.sslwizard.php?main-wizard=yes')";
    $tpl->form_add_button("{new_certificate}",$newjs);

    $html[]=$tpl->form_outside(null, $form,null,'{apply}',
        "LoadAjaxTiny('activate_ssl_decryption','$page?step2=yes')","AsSquidAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function SAVE_CERTIFICATE(){

    $_SESSION["WIZARD_CERTIFICATE"]=$_POST["CERTIFICATE"];
}

function step2():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $certificate=$_SESSION["WIZARD_CERTIFICATE"];
    if(isset($_GET["new-cert"])){
        $certificate=php_uname("n");
    }

    if(isset($_GET["ssl-generated"])){
        $certificate=$_GET["ssl-generated"];
    }

    $html[]="<H1>{activate_ssl_decryption} (2)</H1>";

    if($certificate==null){
        $html[]=$tpl->div_error("{no_certificate}");
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM proxy_ports");
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $UseSSL=intval($ligne["UseSSL"]);
        if($UseSSL==1){continue;}
        $q->QUERY_SQL("UPDATE proxy_ports SET UseSSL=1, sslcertificate='$certificate' WHERE ID=$ID");
    }

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }

    $html[]="<script>Loadjs('$page?step3=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function step3():bool{
    $tpl = new template_admin();
    header("content-type: application/x-javascript");

    $after[]="if( document.getElementById('proxy-transparent-ports') ){LoadAjax('proxy-transparent-ports','fw.proxy.transparent.php?table=yes');}";

    $after[]="if( document.getElementById('table-connected-proxy-ports') ){LoadAjax('table-connected-proxy-ports','fw.proxy.ports.php?connected-ports-list=yes');}";

    $after[]="if( document.getElementById('table-acls-ssl-status') ){LoadAjax('table-acls-ssl-status','fw.proxy.ssl.status.php?tabs=yes');}";

    $after[]="dialogInstance4.close();";

    $newjs=$tpl->framework_buildjs("/proxy/general/nohup/restart",
        "squid.articarest.nohup",
        "squid.articarest.nohup.log","activate_ssl_decryption",
        @implode(";",$after),@implode(";",$after));



    echo $newjs;
    return true;
}





