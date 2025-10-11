<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");
define("rewrite_flags",
        array(
            "last"=>"{ngix_rwfl_last}",
            "break"=>"{ngix_rwfl_break}",
            "permanent"=>"{ngix_rwfl_permanent}",
            "redirect"=>"{ngix_rwfl_redirect}",
        )
);


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}

if(isset($_GET["cipher-js"])){cipher_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["cipher"])){cipher_save();exit;}
if(isset($_POST["expert"])){expert_save();exit;}

if(isset($_GET["cipher-remove"])){cipher_remove();exit;}
if(isset($_GET["cipher-enable"])){cipher_enable();exit;}
if(isset($_GET["cipher-expert"])){cipher_expert();exit;}
if(isset($_GET["cipher-expert-popup"])){cipher_expert_popup();}
if(isset($_GET["curcipher"])){current_ciphers_table();exit;}
if(isset($_GET["cipher-not"])){cipher_not();exit;}

function service_js(){
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog4("{ssl_ciphers} [$serviceid]","$page?popup-main=$serviceid");
}
function cipher_js(){
    $serviceid  = intval($_GET["serviceid"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog5("{new_cipher_suite}","$page?popup-rule=yes&serviceid=$serviceid");
}
function cipher_expert(){
    $serviceid  = intval($_GET["serviceid"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog5("{ssl_ciphers}: {expert_mode}","$page?cipher-expert-popup=yes&serviceid=$serviceid");
}

function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function cipher_not():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $cipher     = $_GET["cipher-not"];
    $sock       = new socksngix($serviceid);
    $ARRAY=GetMyCiphersArray($serviceid);
    if(!isset($ARRAY[$cipher])){
        $ARRAY[$cipher]=1;
        $sock->SET_INFO("ssl_ciphers",BuildMyCipherFromArray($ARRAY));
        return true;
    }
    if($ARRAY[$cipher]==0){$ARRAY[$cipher]=1;}else{$ARRAY[$cipher]=0;}
    $sock->SET_INFO("ssl_ciphers",BuildMyCipherFromArray($ARRAY));
    header("content-type: application/x-javascript");
    return true;
}

function js_reload_main($serviceid){
    return "LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');";
}

function rule_popup(){
    $serviceid  = intval($_GET["serviceid"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $bt="{add}";
    $js_reload_main=js_reload_main($serviceid);
    $jsrestart="dialogInstance5.close();LoadAjax('main-popup-ciphers-$serviceid','$page?popup-table=$serviceid');$js_reload_main";

    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("negative","{notfor}");
    $form[]=$tpl->field_text("cipher","{ssl_ciphers}",null,true);
    $html[]=$tpl->form_outside("{new_cipher_suite}",$form,null,$bt,$jsrestart,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);

}

function cipher_expert_popup(){
    $serviceid  =intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $ssl_ciphers = $sock->GET_INFO("ssl_ciphers");

    $js=js_reload_main($serviceid);
    $jsrestart="LoadAjax('current-ciph-$serviceid','$page?curcipher=$serviceid');LoadAjax('main-popup-ciphers-$serviceid','$page?popup-table=$serviceid');$js";

    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_textarea("expert",null,$ssl_ciphers,"100%","160px");
    $html[]=$tpl->form_outside("{expert_mode}",$form,null,"{apply}",$jsrestart,"AsWebMaster");


    $html[]="<div id='current-ciph-$serviceid' style='margin:10px'></div>";
    $html[]="<script>LoadAjax('current-ciph-$serviceid','$page?curcipher=$serviceid');</script>";



    echo $tpl->_ENGINE_parse_body($html);
}

function cipher_remove():bool{
    //cipher-remove=$cipherencode&serviceid=$serviceid&md=$md
    $serviceid=intval($_GET["serviceid"]);
    $cipher     = $_GET["cipher-remove"];
    $ARRAY      = GetMyCiphersArray($serviceid);
    $sock       = new socksngix($serviceid);
    $md         = $_GET["md"];

    unset($ARRAY[$cipher]);
    $sock->SET_INFO("ssl_ciphers",BuildMyCipherFromArray($ARRAY));

    header("content-type: application/x-javascript");
    echo js_reload_main($serviceid)."\n";
    echo "$('#$md').remove();\n";
    return true;

}

function GetMyCiphersArray($serviceid):array{
    $sock               = new socksngix($serviceid);
    $ssl_ciphers        = $sock->GET_INFO("ssl_ciphers");


    if($ssl_ciphers==null){$ssl_ciphers="ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256";}

    $ssl_ciphers_conf   = explode(":",$ssl_ciphers);
    $ARRAY              = array();

    foreach ($ssl_ciphers_conf as $mycphier){
        if(preg_match("#^\!(.+)#",$mycphier,$re)){
            $ciph=$re[1];
            $ARRAY[$ciph]=1;
            continue;
        }
        $ARRAY[$mycphier]=0;
    }
    return $ARRAY;
}

function BuildMyCipherFromArray($ARRAY):string{
    $final=array();
    foreach ($ARRAY as $cipher=>$neg){
        $negation=null;
        if($neg==1){$negation="!";}
        $final[]="{$negation}$cipher";

    }
    return @implode(":",$final);
}

function cipher_enable(){
    $cipher         = trim($_GET["cipher-enable"]);
    $serviceid      = intval($_GET["serviceid"]);
    $ARRAY          = GetMyCiphersArray($serviceid);
    $sock           = new socksngix($serviceid);


    if(!isset($ARRAY[$cipher])){$ARRAY[$cipher]=0;}else{
        unset($ARRAY[$cipher]);
    }
    $sock->SET_INFO("ssl_ciphers",BuildMyCipherFromArray($ARRAY));
    header("content-type: application/x-javascript");
    echo js_reload_main($serviceid);


}
function expert_save():bool{
    $tpl            = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid      = intval($_POST["serviceid"]);
    $sock           = new socksngix($serviceid);
    $ssl_cipher     = trim($_POST["expert"]);
    $ssl_cipher     = str_replace("\n","",$ssl_cipher);
    $ssl_cipher     = str_replace(" ","",$ssl_cipher);

    exec("/usr/bin/openssl ciphers -v '$ssl_cipher' 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#:error:#",$line)){
            echo "jserror:".htmlentities($line);
            return false;
        }
    }
    $sock->SET_INFO("ssl_ciphers",$ssl_cipher);
    return true;
}

function cipher_save(){
    $tpl            = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid      = intval($_POST["serviceid"]);
    $sock           = new socksngix($serviceid);
    $ARRAY          = GetMyCiphersArray($serviceid);
    $negation       = 0;

    if($_POST["negation"]==1){$negation=1;}
    $ARRAY[$_POST["cipher"]]=$negation;
    $sock->SET_INFO("ssl_ciphers",BuildMyCipherFromArray($ARRAY));

}

function popup_main(){
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-ciphers-$serviceid'></div>
    <script>LoadAjax('main-popup-ciphers-$serviceid','$page?popup-table=$serviceid')</script>";
}

function current_ciphers_table(){
    $serviceid  =    intval($_GET["curcipher"]);
    $tpl            = new template_admin();
    $sock           = new socksngix($serviceid);
    $ssl_ciphers    = $sock->GET_INFO("ssl_ciphers");
    $t              = time();
    $ssl_ciphers_available=array();


    exec("/usr/bin/openssl ciphers -v '$ssl_ciphers' 2>&1",$results);
    $stmamps["SSLv3"]="<span class='label label-danger'>&nbsp;SSLv3</span>";
    $stmamps["TLSv1"]="<span class='label label-warning'>&nbsp;TLSv1</span>";
    $stmamps["TLSv1.2"]="<span class='label label-info'>TLSv1.2</span>";
    $stmamps["TLSv1.3"]="<span class='label label-primary'>TLSv1.3</span>";

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^([A-Z0-9\-]+)\s+(.+?)\s+#",$line,$re)){
            $SSLCIPH=$re[1];
            $tlsver=$re[2];
            $ssl_ciphers_available[$SSLCIPH]=$stmamps[$tlsver];
        }

    }

    $html[]="
<table class=\"table table-hover\" id='table-$t'>
	<thead>
    	<tr>
        	<th nowrap>{ssl_ciphers}</th>
        	<th nowrap>&nbsp;</th>
        </tr>
  	</thead>
	<tbody>
";



    foreach ($ssl_ciphers_available as $cipher=>$level){

        $html[]="<tr>
                    <td width=50%><strong>$cipher&nbsp;</strong></td>
                    <td width=1% nowrap>$level</td>
                    </tr>";



    }

    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

}

function popup_table(){
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tableid    = time();
    $ssl_ciphers_available=array();
    $openssl_nginx_version=null;

    $compile_js_progress=compile_js_progress($serviceid);

    exec("/usr/sbin/nginx -V 2>&1",$versions);

    foreach ($versions as $line){
        if(preg_match("#built with OpenSSL\s+([0-9\.]+)#",$line,$re)){
            $openssl_nginx_version=trim($re[1]);
        }
    }
    VERBOSE("Built with openssl v [$openssl_nginx_version]",__LINE__);
    $bvers=explode(".",$openssl_nginx_version);
    $bversMajor=intval($bvers[0]);
    $major=intval($bvers[0]);
    $minor=intval($bvers[1]);
    $rev=intval($bvers[2]);
    $verstring=$openssl_nginx_version;
    $OpenSSLV=false;

    VERBOSE("Major=$major",__LINE__);
    if($major>1){
        $OpenSSLV=true;
    }else {
        if ($major > 0) {
            if ($minor > 0) {
                if ($rev > 0) {
                    $OpenSSLV = true;
                }
            }
        }
    }

    if(!$OpenSSLV){
        $error_opensslv=$tpl->_ENGINE_parse_body("{error_opensslv}");
        $error_opensslv=str_replace("%s",$verstring,$error_opensslv);
        $html[]=$tpl->div_error($error_opensslv);
    }

    $results=array();



    VERBOSE("bversMajor = $bversMajor",__LINE__);
    if($bversMajor>2){
        VERBOSE("open ressources/databases/ciphers-3.0.1.db",__LINE__);
        $results=explode("\n",@file_get_contents("ressources/databases/ciphers-3.0.1.db"));
    }

    if(count($results)==0) {
        exec("/usr/bin/openssl ciphers -s -v -psk -srp 2>&1", $results);
    }
    $stmamps["SSLv3"]="<span class='label label-danger'>&nbsp;SSLv3</span>";
    $stmamps["TLSv1"]="<span class='label label-warning'>&nbsp;TLSv1</span>";
    $stmamps["TLSv1.2"]="<span class='label label-info'>TLSv1.2</span>";
    $stmamps["TLSv1.3"]="<span class='label label-primary'>TLSv1.3</span>";

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^([A-Z0-9\-]+)\s+(.+?)\s+#",$line,$re)){
            $SSLCIPH=$re[1];
            $tlsver=$re[2];
            $ssl_ciphers_available[$SSLCIPH]=$stmamps[$tlsver];
        }

    }



    $ARRAY=GetMyCiphersArray($serviceid);


    $html[]="<div id='progress-compile-ciphers-$serviceid'></div>";

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";

    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?cipher-js=yes&serviceid=$serviceid');\">
	<i class='fa fa-plus'></i> {new_cipher_suite} </label>";

    $html[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?cipher-expert=yes&serviceid=$serviceid');\">
	<i class='fa fa-wrench'></i> {expert_mode} </label>";



    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$compile_js_progress\">
	<i class='fa fa-save'></i> {apply_parameters_to_the_system} </label>";
    $html[]="</div>";

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{ssl_ciphers} - v{$openssl_nginx_version}</th>
        	<th nowrap>&nbsp;</th>
        	<th nowrap>{notfor}</th>
        	<th nowrap>{enable}</small></th>
        </tr>
  	</thead>
	<tbody>
";



    foreach ($ssl_ciphers_available as $cipher=>$level){
        $enable=0;
        $cipherencode=urlencode($cipher);
        if(isset($ARRAY[$cipher])){
            $enable=1;

        }

        $enablebt=$tpl->icon_check($enable,"Loadjs('$page?cipher-enable=$cipherencode&serviceid=$serviceid')","","AsWebMaster");
        $notfor=$tpl->icon_check($ARRAY[$cipher],"Loadjs('$page?cipher-not=$cipherencode&serviceid=$serviceid')","","AsWebMaster");


        $html[]="<tr>
                    <td width=50%><strong>$cipher&nbsp;</strong></td>
                    <td width=1% nowrap>$level</td>
                    <td width=1% nowrap>$notfor</td>
                    <td width=1% nowrap>$enablebt</td>
                    </tr>";

        unset($ARRAY[$cipher]);

    }

    foreach ($ARRAY as $cipher=>$notfor) {
        $cipherencode   = urlencode($cipher);
        $md             = md5($cipher);
        $notforbt       = $tpl->icon_check($notfor, "Loadjs('$page?cipher-not=$cipherencode&serviceid=$serviceid')", "", "AsWebMaster");
        $delete          =$tpl->icon_delete("Loadjs('$page?cipher-remove=$cipherencode&serviceid=$serviceid&md=$md')","AsWebMaster");

        $html[]="<tr id='$md'>
                    <td width=50%><strong>$cipher</strong></td>
                    <td width=1% nowrap>&nbsp;</td>
                    <td width=1% nowrap>$notforbt</td>
                    <td width=1% nowrap>$delete</td>
                    </tr>";

        
    }

        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='4'>";
        $html[]="<ul class='pagination pull-right'></ul>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</tfoot>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="$(document).ready(function() { $('#$tableid').footable( { \"filtering\": { \"enabled\": true";
        $html[]="},\"sorting\": { \"enabled\": true },";
        $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        }