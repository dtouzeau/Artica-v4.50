<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["status"])){status();exit;}


function status(){
    $tpl=new template_admin();
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));

    if($LighttpdArticaClientAuth==0) {
        $btn[]=array("name"=>"{activate}","js"=>"Loadjs('fw.articaweb.cert.php?check=yes')","icon"=>"fad fa-badge-check","color"=>null);
        echo $tpl->widget_grey("{authenticate_ssl_client}", "{disabled}",$btn);
        return false;
    }

    $LighttpdServerCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdServerCertificate");
    $array=unserialize(base64_decode($LighttpdServerCertificate));
    if(!isset($array["ca_data"])){$LighttpdServerCertificate=null;}
    if($LighttpdServerCertificate==null){
        $btn[]=array("name"=>"{edit}","js"=>"Loadjs('fw.articaweb.cert.php')","icon"=>"fas fa-cogs","color"=>null);
        echo $tpl->widget_jaune("{authenticate_ssl_client}", "{no_certificate}",$btn);
        return false;

    }
    $MAIN=unserialize(base64_decode($LighttpdServerCertificate));
    $crt_data=base64_decode($MAIN["crt_data"]);
    $array=openssl_x509_parse($crt_data);
    if(!isset($array["subject"])){
        $btn[]=array("name"=>"{edit}","js"=>"Loadjs('fw.articaweb.cert.php')","icon"=>"fas fa-cogs","color"=>null);
        echo $tpl->widget_jaune("{authenticate_ssl_client}", "{no_certificate}",$btn);
        return false;
    }
    $f=explode("\n",@file_get_contents("/etc/artica-postfix/webconsole.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^ssl_client_certificate\s+#",$line)){
            $btn[]=array("name"=>"{remove}","js"=>"Loadjs('fw.articaweb.cert.php?remove=yes')","icon"=>"fas fa-trash-alt","color"=>null);
            echo $tpl->widget_vert("{authenticate_ssl_client}", "{installed}",$btn);
            return true;
        }
    }



    $LighttpdManagerEnforce=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdManagerEnforce"));
    if($LighttpdManagerEnforce==1){

    }

}