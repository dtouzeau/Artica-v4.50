<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["ICAP-start"])){icap_start();exit;}
if(isset($_POST["INTERFACE"])){save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["icap-step1"])){icap_step1();exit;}
if(isset($_GET["icap-step2"])){icap_step2();exit;}
if(isset($_POST["SimulateICAPURL"])){icap_save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded_js();exit;}
js();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{download}"]="$page?popup=yes";
    $array["ICAP"]="$page?ICAP-start=yes";
    echo $tpl->tabs_default($array);
}
function icap_start(){
    $page=CurrentPageName();
    echo "<div id='icap-section'></div>\n";
    echo "<script>LoadAjaxSilent('icap-section','$page?icap-step1=yes');</script>";
}

function icap_step1(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sql="SELECT * FROM c_icap_services WHERE enabled=1 ORDER BY zOrder";
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $ligne){
        $text="{$ligne["ipaddr"]}:{$ligne["listenport"]} - {$ligne["service_name"]}";
        $ID=$ligne["ID"];
        $HASH[$ID]=$text;
    }

    if(count($HASH)==0){
        echo $tpl->div_warning("{error}||No ICAP connection set");
        return true;

    }

    $SimulateICAPID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SimulateICAPID"));
    $SimulateICAPURL=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SimulateICAPURL"));
    if(strlen($SimulateICAPURL)< 5){
        $SimulateICAPURL="http://dummyurl.simulated.com";
    }
    $html[]="<div id='icap-section2'>".icap_results()."</div>";
    $form[]=$tpl->field_array_hash($HASH,"SimulateICAPID","nonull:{icap_service}",$SimulateICAPID);
    $form[]=$tpl->field_text("SimulateICAPURL","URL",$SimulateICAPURL);
    $after="LoadAjaxSilent('icap-section2','$page?icap-step2=yes');";
    $html[]=$tpl->form_outside("{simulate} ICAP",$form,"{icap_simulate_1}","{next}",$after);
    echo $tpl->_ENGINE_parse_body($html);
}
function icap_step2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<center style='margin:30px'>".$tpl->button_upload("{threat_sample}",$page)."</center>";

}

function icap_results(){
    $tpl=new template_admin();
    if(isset($_GET["clean"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SimulateICAP",serialize(array()));
    }
    $SimulateICAP=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SimulateICAP"));
    if(count($SimulateICAP)<2){return null;}
    $page=CurrentPageName();
    if(isset($SimulateICAP["HEADERS"])){
        $html[]="<table style='width:100%'>";
        foreach ($SimulateICAP["HEADERS"] as $key=>$val){
            $html[]="<tr>";
            $html[]="<td style='width:1%;text-align:right' nowrap>$key:</td>";
            $html[]="<td style='width:100%;text-align:left;padding-left:10px'><strong>$val</strong></td>";
            $html[]="</tr>";
        }
//<i class="fa-solid fa-broom"></i>
        $html[]="<tr><td colspan='2' style='text-align: right'><hr>";
        $html[]=$tpl->button_autnonome("{clean}",
            "LoadAjaxSilent('icap-section','$page?icap-step1=yes&clean=yes');",
            "fa-solid fa-broom",null,178,"btn-danger");

            $html[]="</td> </tr>";

        $html[]="</table>";
    }
    $TITLE=$SimulateICAP["TITLE"];
    if(isset($SimulateICAP["HEADERS"]["x-infection-found"])){
        $SimulateICAP["CONN_STATUS"]=false;
    }

    if(!$SimulateICAP["CONN_STATUS"]){
        if(isset($SimulateICAP["DESCRIPTION"])){
            $html[]="<p><strong>{$SimulateICAP["DESCRIPTION"]}</strong></p>";
        }

        return $tpl->div_error("$TITLE: {results}||".@implode("\n",$html));
    }
    return $tpl->div_explain("$TITLE: {results}||".@implode("\n",$html));
}

function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog6("{request_simulation}","$page?tabs=yes",890);
}
function icap_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}

function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/curl.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/curl.txt";
    $ARRAY["CMD"]="curl.php?simulate=yes";
    $ARRAY["TITLE"]="{request_simulation}";
    //$ARRAY["AFTER"]="Loadjs('$page?results=yes');";
    //$ARRAY["AFTER_FAILED"]="Loadjs('$page?results=yes');";
    $ARRAY["REFRESH-MENU"]="no";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=curl-rqs')";

    $CONF=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("request_simulation"));
    if($CONF["URL"]==null){$CONF["URL"]="https://www.ibm.com/industries/banking-financial-markets?lnk=hpmps_buin&lnk2=learn";}
    if($CONF["USERAGENT"]==null){$CONF["USERAGENT"]="Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:80.0) Gecko/20100101 Firefox/80.0";}
    if($CONF["ACCEPTLANG"]==null){$CONF["ACCEPTLANG"]="fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3";}
    if($CONF["CONNECTION"]==null){$CONF["CONNECTION"]="keep-alive";}
    if($CONF["REFERER"]==null){$CONF["REFERER"]=$CONF["URL"];}
    if($CONF["ACCEPTENCODING"]==null){$CONF["ACCEPTENCODING"]="gzip, deflate, br";}


    $form[]=$tpl->field_text("URL","{url}",$CONF["URL"],true);
    $form[]=$tpl->field_text("USERAGENT","User-Agent",$CONF["USERAGENT"]);
    $form[]=$tpl->field_text("ACCEPTLANG","Accept-Language",$CONF["ACCEPTLANG"]);
    $form[]=$tpl->field_text("ACCEPTENCODING","Accept-Encoding",$CONF["ACCEPTENCODING"]);
    $form[]=$tpl->field_text("ACCEPTLANG","Accept-Language",$CONF["ACCEPTLANG"]);
    $form[]=$tpl->field_text("CONNECTION","Connection",$CONF["CONNECTION"]);
    $form[]=$tpl->field_text("REFERER","Referer",$CONF["REFERER"]);
    $form[]=$tpl->field_text("XFORWARDEDFOR","X-Forwarded-For",$_SERVER["REMOTE_ADDR"]);
    $form[]=$tpl->field_interfaces("INTERFACE","{outgoing_interface}",$CONF["INTERFACE"]);
    $form[]=$tpl->field_checkbox("LOCAL_PROXY","{local_proxy}",$CONF["LOCAL_PROXY"]);
    $form[]=$tpl->field_text("REMOTE_PROXY","{proxy_address}",$CONF["REMOTE_PROXY"]);
    $form[]=$tpl->field_text("REMOTE_PROXY_PORT","{proxy_port}",$CONF["REMOTE_PROXY"]);

    $html[]="<div id='curl-rqs' style='margin:5px'></div>";
    $html[]=$tpl->form_outside("{request_simulation}",$form,null,"{submit}",$jsafter);
    echo $tpl->_ENGINE_parse_body($html);
}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS("URL");
    $_POST["URL"]=url_decode_special_tool($_POST["URL"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("request_simulation",serialize($_POST));
}
function file_uploaded_js(){
    $page=CurrentPageName();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);
    $sock=new sockets();
    $after="LoadAjaxSilent('icap-section','$page?icap-step1=yes');";
    $sock->getFrameWork("cicap.php?simulate=$fileencode");
    echo "$after\n";
}



