<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["certificate"])){rule_save();exit;}

if(isset($_GET["rule-export-js"])){rule_export_js();exit;}
if(isset($_GET["rule-export-popup"])){rule_export_popup();exit;}
if(isset($_POST["importid"])){rule_export_save();exit;}

service_js();
function service_js(){
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog4("{trusted_certificates}","$page?popup-main=$serviceid",650);
}
function rule_js(){
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{use_certificate_from_certificate_center}";}
    $tpl->js_dialog5($title,"$page?popup-rule=$rule",550);
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";
}

function rule_remove():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ruleid=intval($_GET["pattern-remove"]);
    $ID=intval($_GET["ID"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM sslproxy_cafile WHERE ID=$ID");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);

    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ssl/build");
    header("content-type: application/x-javascript");
    echo "$('#$ruleid').remove();\n";
    echo "LoadAjaxSilent('table-flat','fw.proxy.ssl.status.php?table-flat=yes');\n";
    echo "LoadAjax('proxy-ssl-trustedcert','fw.nginx.reverse-options.php?main=yes');";
    return admin_tracks("Removed Proxy trusted SSL certificate #$ID");

}

function rule_enable(){
    $ruleid=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("header_rules")));
    if(intval($data[$ruleid]["enable"])==1){
        $data[$ruleid]["enable"]=0;
    }else{
        $data[$ruleid]["enable"]=1;
    }
    $encoded=serialize($data);
    $sock->SET_INFO("header_rules",base64_encode($encoded));
}



function rule_popup(){

    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $bt="{add}";

    $jsrestart="dialogInstance5.close();LoadAjax('main-popup-trustedcert','$page?popup-table=yes'); LoadAjaxSilent('table-flat','fw.proxy.ssl.status.php?table-flat=yes');";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_certificate("certificate","{certificate}","","",1);
    $html[]=$tpl->form_outside("",$form,null,$bt,$jsrestart,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function rule_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $certificate     = trim($_POST["certificate"]);
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sslcertificates WHERE CommonName='$certificate'");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $ID=intval($ligne["ID"]);
    if($ID==0){
        $tb=array();
        foreach ($ligne as $key=>$value){
            $tb[]="$key=$value";
        }

        echo $tpl->post_error("certificate [$certificate] not found".implode(",\n",$tb));
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("INSERT INTO sslproxy_cafile (certid,CommonName) VALUES ('$ID','$certificate');");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ssl/build");
    return admin_tracks("Add $certificate to trusted proxy ssl certificates database");

}

function popup_main(){
    $page       = CurrentPageName();
    echo "<div id='main-popup-trustedcert'></div>
    <script>LoadAjax('main-popup-trustedcert','$page?popup-table=yes')</script>";
}


function popup_table(){

    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tableid    = time();



    $html[]="<div id='progress-compile-replace-trustedcert'></div>";
    $topbuttons[] = array("Loadjs('$page?rule-js=0');", ico_plus
    , "{use_certificate_from_certificate_center}");

    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="</div>";

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{certificates}</th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $data=$q->QUERY_SQL("SELECT * FROM sslproxy_cafile");
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return;
    }

    foreach ($data as $num=>$ligne){
        $certid=intval($ligne["certid"]);
        $ID=intval($ligne["ID"]);
        $CommonName=$ligne["CommonName"];
        $keyEnc=urlencode($CommonName);
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/certificate/details/$keyEnc"));

        if(!$json->Status){
            $error="<br><i class='text-danger'>$json->Error</i>";
        }else{
            $Info=$json->Info;
            if(property_exists($Info,"OrganizationName")){
                if($Info->OrganizationName=="Let`s Encrypt"){
                    $opts[]="<span class='label label-success'>Let`s Encrypt</span>";
                }
            }

            if(property_exists($Info,"isCA")){
                if($Info->isCA){
                    $opts[]="<span class='label label-success'>Root CA</span>";

                }
            }
            if(property_exists($Info,"IsSelfSigned")){
                if($Info->IsSelfSigned){
                    $opts[]="<span class='label label-warning'>{SelfSignedCert}</span>";
                }
            }
            if(property_exists($Info,"ExpireDate")){
                $expdate=distanceOfTimeInWords(time(),$Info->ExpireDate);
                $opts[]="<br>{expire}: $expdate";
            }
        }
        if(property_exists($Info,"OrganizationalUnitName")){
            $opts[]="&nbsp;|&nbsp;<i>$Info->OrganizationalUnitName</i>";
        }
        if(property_exists($Info,"DNSNames")){
            $uls=array();
            $ico=ico_earth;
            $CLEAN=array();
            foreach ($Info->DNSNames as $dnsname) {
                $CLEAN[$dnsname] = true;
            }
            foreach ($CLEAN as $dnsname=>$none) {
                $uls[]="<div><i class='$ico'></i>&nbsp;$dnsname</div>";
            }
            if(count($uls)>0) {
                $opts[] = @implode("",$uls);
            }
        }



    $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$num&ID=$ID')","AsWebMaster");


        if(count($opts)>0){
            $optsTxt="<div>".@implode(" ",$opts)."</div>";
        }

    $html[]="<tr id='$num'>
				<td style='width:99%'><div style='margin-bottom:5px'><strong style='font-size:16px'><i class=\"".ico_certificate."\"></i>&nbsp;$CommonName</strong></div>$error$optsTxt</td>
				<td style='width:1%'  nowrap >$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='2'>";
        $html[]="<ul class='pagination pull-right'></ul>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</tfoot>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="$(document).ready(function() { $('#$tableid').footable( { \"filtering\": { \"enabled\": true";
        $html[]="},\"sorting\": { \"enabled\": false },";
        $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        }