<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


$GLOBALS["ZTYPES"][100]="{proxypr}";
$GLOBALS["ZTYPES"][1]="{ftppr}";
$GLOBALS["ZTYPES"][2]="{sockspr}";
$GLOBALS["ZTYPES"][3]="{smtpp}";
$GLOBALS["ZTYPES"][4]="{tcppm}";
$GLOBALS["ZTYPES"][5]="{udppm}";
$GLOBALS["ZTYPES"][6]="{dnspr}";

$GLOBALS["ZTYPES_EXPLAIN"][100]="{proxypr}";
$GLOBALS["ZTYPES_EXPLAIN"][1]="{ftppr_explain}";
$GLOBALS["ZTYPES_EXPLAIN"][3]="{smtpp_explain}";
$GLOBALS["ZTYPES_EXPLAIN"][4]="{tcppm_explain}";
$GLOBALS["ZTYPES_EXPLAIN"][5]="{udppm_explain}";
$GLOBALS["ZTYPES_EXPLAIN"][6]="{dnspr_explain}";

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["lastid-js"])){service_wizard_lastid();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["service-wizard"])){service_wizard();exit;}
if(isset($_POST["service-id"])){service_save();exit;}
if(isset($_POST["new-service"])){service_wizard_save();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["service-popup"])){service_popup();exit;}
if(isset($_GET["service-delete"])){service_delete_js();exit;}
if(isset($_POST["service-delete"])){service_delete();exit;}
if(isset($_GET["service-enable"])){service_enable();exit;}
if(isset($_GET["service-move"])){service_move();exit;}

page();


function service_wizard_lastid(){
    header("content-type: application/x-javascript");
    if(isset($_SESSION["WIZARD_3PROXY_LAST_ID"])){$_GET["service-js"]=$_SESSION["WIZARD_3PROXY_LAST_ID"];service_js();}
}

function service_move(){
    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $tpl=new template_admin();
    $dir=$_GET["dir"];
    $ID=intval($_GET["id"]);
    $ligne=$q->mysqli_fetch_array("SELECT zorder,servicename FROM `3proxy_services` WHERE ID='$ID'");
    if(!$q->ok){$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "alert('$q->mysql_error');";return;}

    $zorder=intval($ligne["zorder"]);
    $CurOrder=$zorder;
    echo "// {$ligne["servicename"]} [$ID] Current order = $zorder\n";

    if($dir=="up"){
        $zorder=$zorder-1;
        if($zorder<0){$zorder=0;}
    }
    else{
        $zorder=$zorder+1;
    }
    echo "//$ID --> {$ligne["servicename"]} New order = $zorder\n";

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $q->QUERY_SQL("UPDATE `3proxy_services` SET zorder='$zorder' WHERE ID='$ID'");
    if(!$q->ok){$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "alert('$q->mysql_error');";return;}

    $q->QUERY_SQL("UPDATE `3proxy_services` SET zorder='$CurOrder' WHERE zorder='$zorder' AND ID<>'$ID'");




    $c=0;
    $results=$q->QUERY_SQL("SELECT ID,servicename,zorder FROM `3proxy_services` ORDER BY zorder");
    foreach($results as $indedx=>$ligne){
        $ID=$ligne["ID"];
        echo "// {$ligne["servicename"]} ($ID) New order = $c was {$ligne["zorder"]}\n";
        $q->QUERY_SQL("UPDATE `3proxy_services` SET zorder='$c' WHERE ID='$ID'");
        if(!$q->ok){$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "alert('$q->mysql_error');";return;}
        $c++;
    }
}

function service_delete_js(){
    $md=$_GET["md"];
    $ID=intval($_GET["service-delete"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM `3proxy_services` WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $tpl->js_confirm_delete($servicename, "service-delete", $ID,"$('#$md').remove()");

}
function service_delete():bool{
    $ID=intval($_POST["service-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $q->QUERY_SQL("DELETE FROM `3proxy_services` WHERE ID='$ID'");
    if(!$q->ok){echo $q->mysql_error;}

    $q->QUERY_SQL("DELETE FROM `3proxy_acls_rules` WHERE serviceid='$ID'");
    if(!$q->ok){echo $q->mysql_error;}


    $q->QUERY_SQL("DELETE FROM `3proxy_acls_parent` WHERE serviceid=$ID");
    if(!$q->ok){echo $q->mysql_error;}

    return admin_tracks("Remove Universal proxy service #$ID");

}

function service_enable():bool{
    $ID=intval($_GET["service-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM `3proxy_services` WHERE ID=$ID");
    $enable=intval($ligne["enabled"]);
    if($enable==1){$enable=0;}else{$enable=1;}
    $q->QUERY_SQL("UPDATE `3proxy_services` SET `enabled`=$enable WHERE ID=$ID");
    return admin_tracks("Enable/disable Universal proxy service #$ID enable=$enable");
}


function service_js():bool{
    $ID=intval($_GET["service-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    if($ID==0){
        return $tpl->js_dialog1("{new_service}", "$page?service-wizard=0",900);

    }
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM `3proxy_services` WHERE ID=$ID");
    $servicename=$ligne["servicename"];
    $listen_port=$ligne["listen_port"];
    $listen_interface=$ligne["listen_interface"];
    $service_type=$GLOBALS["ZTYPES"][$ligne["service_type"]];
    return $tpl->js_dialog1("$servicename:: $listen_interface:$listen_port $service_type", "$page?service-popup=$ID");

}

function service_wizard(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));

    if($UnboundEnabled==1){unset($GLOBALS["ZTYPES"][6]);}
    if($PowerDNSEnableRecursor==1){unset($GLOBALS["ZTYPES"][6]);}

    foreach ($GLOBALS["ZTYPES"] as $value=>$name){
        $Types[$value]="<strong>$name:</strong>&nbsp;<small>{$GLOBALS["ZTYPES_EXPLAIN"][$value]}</small>";

    }

    $form[]=$tpl->field_hidden("new-service", "yes");
    $form[]=$tpl->field_text("servicename", "{service_name2}", "New service",true);
    $form[]=$tpl->field_array_checkboxes($Types, "ztype", 1);
    echo $tpl->form_outside("{new_service}", $form,"{3proxy_service_explain}","{add}",
        "dialogInstance1.close();Loadjs('$page?lastid-js=yes');","AsFirewallManager");


}

function service_wizard_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $service_type=$_POST["ztype"];

    $servicename=$q->sqlite_escape_string2($_POST["servicename"]);
    $listen_port=rand(1024,65535);

    $q->QUERY_SQL("INSERT INTO `3proxy_services` (service_type,servicename,listen_port) VALUES ('$service_type','$servicename','$listen_port')");
    if(!$q->ok){echo $q->mysql_error;return;}

    $_SESSION["WIZARD_3PROXY_LAST_ID"]=$q->last_id;


}

function service_popup():bool{

    $HOWTO[2]="{sockdpr_explain}";
    $HOWTO[3]="{smtpp_howto}";


    $page=CurrentPageName();
    $tpl=new template_admin();
    $FIREHOLE=false;
    $ID=intval($_GET["service-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    if($FireHolEnable==1){$FIREHOLE=true;}
    $EnableRedSocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedSocks"));

    $jafter="LoadAjax('table-3proxy-table','$page?table=yes');dialogInstance1.close();";

    $ligne["servicename"]=$tpl->_ENGINE_parse_body("{new_service}");
    $bt="{add}";

    if($ID>0){$ligne=$q->mysqli_fetch_array("SELECT * FROM `3proxy_services` WHERE ID=$ID");$bt="{apply}";}
    if(intval($ligne["maxconn"])==0){$ligne["maxconn"]=100;}

    $tpl->field_hidden("service-id", $ID);
    $tpl->field_hidden("service_type", $ligne["service_type"]);
    if(!$FIREHOLE){
        $tpl->field_hidden("transparent",0);
        $tpl->field_hidden("redsocks",0);
    }
    $form[]=$tpl->field_text("servicename", "{service_name}", $ligne["servicename"],true);

    $form[]=$tpl->field_numeric("listen_port","{listen_port}",$ligne["listen_port"]);
    $form[]=$tpl->field_interfaces("listen_interface", "{listen_interface}", $ligne["listen_interface"]);
    $form[]=$tpl->field_interfaces("outgoing_interface", "{outgoing_interface}", $ligne["outgoing_interface"]);
    $form[]=$tpl->field_numeric("maxconn","{max_connections}",$ligne["maxconn"]);


    $options=unserialize(base64_decode($ligne["options"]));

    if(!isset($HOWTO[$ligne["service_type"]])){
        $HOWTO[$ligne["service_type"]]=$GLOBALS["ZTYPES_EXPLAIN"][$ligne["service_type"]];
    }

    $logdumpEnabled=intval($options["logdump_enabled"]);
    $logdumpVal=trim($options["logdump_val"]);
    if (empty($logdumpVal)){
        $logdumpVal="16 16";
    }
    $form[]=$tpl->field_checkbox("logdump_enabled","logdump",$logdumpEnabled);
    $form[]=$tpl->field_text("logdump_val", "logdump N+M", $logdumpVal,true,"");

    switch ($ligne["service_type"]) {
        case 1:
            $form[]=$tpl->field_text("default_destination", "{default_destination}", $options["default_destination"],false,"{ftppr_default_destination}");
            break;
        case 3:
            $form[]=$tpl->field_text("default_destination", "{default_destination}", $options["default_destination"],false,"{ftppr_default_destination}");
            break;

        case 4:
            $form[]=$tpl->field_ipaddr("dst_addr", "{dst_addr}", $options["dst_addr"],true);
            $form[]=$tpl->field_numeric("dst_port","{destination_port}",$options["dst_port"],true);
            break;

        case 5:
            $form[]=$tpl->field_ipaddr("dst_addr", "{dst_addr}", $options["dst_addr"],true);
            $form[]=$tpl->field_numeric("dst_port","{destination_port}",$options["dst_port"],true);
            break;

        case 6:
            $FIREHOLE=false;
            if(intval($options["DNS_CACHE"])==0){$options["DNS_CACHE"]=65535;}
            $form[]=$tpl->field_numeric("DNS_CACHE","{cache_items}",$options["DNS_CACHE"],true);
            $form[]=$tpl->field_text("DNS1", "{primary_dns}", $options["DNS1"],false,"{primary_dns}");
            $form[]=$tpl->field_text("DNS2", "{secondary_dns}",$options["DNS2"],false,"{secondary_dns}");
            $form[]=$tpl->field_text("DNS3", "{DNS_SERVER} 3",$options["DNS3"],false,"{secondary_dns}");
            $form[]=$tpl->field_text("DNS4", "{DNS_SERVER} 4",$options["DNS3"],false,"{secondary_dns}");

        default:
            ;
            break;
    }

    if(intval($ligne["redsocks_port"])==0){$ligne["redsocks_port"]=rand(10000,65500);}

    if($FIREHOLE){
        $form[]=$tpl->field_checkbox("transparent","{transparent}",$ligne["transparent"],false);

        if( ($ligne["service_type"]==2) OR ($ligne["service_type"]==100) ){

            if($EnableRedSocks==1){
                $form[]=$tpl->field_checkbox("redsocks","{socksify}",$ligne["redsocks"],"redsocks_port","{socksify_explain}");}
            $form[]=$tpl->field_numeric("redsocks_port","{listen_port} <small>({APP_REDSOCKS})</small>",$ligne["redsocks_port"],true);
            if($EnableRedSocks==0){
                $form[]=$tpl->field_checkbox("redsocks","{socksify}",0,false,"{socksify_explain}",true);
                $form[]=$tpl->field_numeric("redsocks_port","{listen_port} <small>({APP_REDSOCKS})</small>",$ligne["redsocks_port"],true);

            }

            if($ligne["service_type"]==100){
                if($EnableRedSocks==1){
                    if(intval($ligne["redsocks_type"])==0){$ligne["redsocks_type"]=1;}
                    $szredsocks_type[1]="http-connect";
                    $szredsocks_type[2]="http-relay";
                    $form[]=$tpl->field_array_hash($szredsocks_type, "redsocks_type", "{method}", $ligne["redsocks_type"]);


                }

            }


        }

    }

    echo $tpl->form_outside("{$ligne["servicename"]}<br><small>{$GLOBALS["ZTYPES"][$ligne["service_type"]]}</small>", $form,$HOWTO[$ligne["service_type"]],$bt,$jafter,"AsFirewallManager");
    return true;
}

function service_save():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

    $tpl->CLEAN_POST();
    $ID=intval($_POST["service-id"]);
    unset($_POST["service-id"]);



    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT options FROM `3proxy_services` WHERE ID=$ID");
        $options=unserialize(base64_decode($ligne["options"]));

    }
    if(isset($_POST["logdump_enabled"])){
        $options["logdump_enabled"]=intval($_POST["logdump_enabled"]);
        unset($_POST["logdump_enabled"]);
    }
    if(isset($_POST["logdump_val"])){
        if (trim($_POST["logdump_val"])=="0 0"){
            $options["logdump_enabled"]=0;
        }
        if (empty(trim($_POST["logdump_val"]))){
            $_POST["logdump_val"]="16 16";
        }
        if (substr_count(trim($_POST["logdump_val"]), ' ')!=1){
            $_POST["logdump_val"]="16 16";
        }
        $options["logdump_val"]=$_POST["logdump_val"];

        unset($_POST["logdump_val"]);
    }
    if(isset($_POST["default_destination"])){
        $options["default_destination"]=$_POST["default_destination"];
        unset($_POST["default_destination"]);
    }
    if(isset($_POST["dst_addr"])){
        $options["dst_addr"]=$_POST["dst_addr"];
        $options["dst_port"]=$_POST["dst_port"];
        unset($_POST["dst_addr"]);
        unset($_POST["dst_port"]);
    }
    if(isset($_POST["DNS_CACHE"])){
        $options["DNS_CACHE"]=$_POST["DNS_CACHE"];
        $options["DNS1"]=$_POST["DNS1"];
        $options["DNS2"]=$_POST["DNS2"];
        $options["DNS3"]=$_POST["DNS3"];
        $options["DNS4"]=$_POST["DNS4"];
        unset($_POST["DNS_CACHE"]);
        unset($_POST["DNS1"]);
        unset($_POST["DNS2"]);
        unset($_POST["DNS3"]);
        unset($_POST["DNS4"]);

    }

    if($_POST["redsocks"]==1){$_POST["transparent"]=1;}





    $_POST["options"]=base64_encode(serialize($options));

    foreach ($_POST as $key=>$value){
        $value=$q->sqlite_escape_string2($value);
        $ADD1[]="`$key`";
        $ADD2[]="'$value'";
        $EDIT[]="`$key`='$value'";
    }

    if($ID==0){
        $q->QUERY_SQL("INSERT INTO `3proxy_services`(".@implode(",", $ADD1).") VALUES (".@implode(",", $ADD2).")");
    }else{
        $q->QUERY_SQL("UPDATE `3proxy_services` SET ".@implode(",", $EDIT)." WHERE ID=$ID");
    }

    if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks_post("Save Universal proxy service parameters");

}


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_VERSION");

    $html=$tpl->page_header("{APP_3PROXY} v$PrivoxyVersion &raquo;&raquo; {services}",
        "fa fa-align-justify","{APP_3PROXY_EXPLAIN}",
        "$page?table=yes","universal-proxy-services",
        "progress-3proxy-restart",false,"table-3proxy-table");




    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_3PROXY} v$PrivoxyVersion &raquo;&raquo; {services}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $TRCLASS=null;
    $t=time();

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $sql="SELECT * FROM `3proxy_services` ORDER BY zorder";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

    $jsrestart=$tpl->framework_buildjs("/3proxy/restart",
        "3proxy.progress",
        "3proxy.progress.log",
        "progress-3proxy-restart","LoadAjax('table-3proxy-status','$page?table=yes')");



    $add="Loadjs('$page?service-js=0')";

    $topbuttons[] = array($add, ico_plus, "{new_service}");
    $topbuttons[] = array($jsrestart, ico_save, "{apply_parameters}");


    $html[]="<table id='table-$t' class=\"table table-stripped\" style='margin-top:10px' data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{service_name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{port}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' style='width:1%'>{transparent}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' style='width:1%'>ACLS</th>";
    $html[]="<th data-sortable=true class='text-capitalize' style='width:1%'>{enable}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' style='width:1%'>{move}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));
        $servicename=$ligne["servicename"];
        $transparent=$ligne["transparent"];
        $service_type=$GLOBALS["ZTYPES"][$ligne["service_type"]];
        $transparent_icon=$tpl->icon_nothing();
        $EXPLAIN=EXPLAIN_THIS($ligne);


        $up=$tpl->icon_up("Loadjs('$page?service-move=yes&id={$ligne["ID"]}&dir=up')","AsFirewallManager");
        $down=$tpl->icon_down("Loadjs('$page?service-move=yes&id={$ligne["ID"]}&dir=down')","AsFirewallManager");

        if(intval($ligne["zorder"])==0){$up=null;}

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><strong>". $tpl->td_href($servicename,null,"Loadjs('$page?service-js={$ligne['ID']}')")."</strong>";
        $html[]="<br><small>$service_type</small></td>";
        $html[]="<td>$EXPLAIN</td>";

        if($transparent==1){
            $transparent_icon=$tpl->icon_parameters("Loadjs('fw.3proxy.transparent.php?ruleid=$ID')");
        }

        $html[]="<td style='width:1%' class='center' nowrap>$transparent_icon</center></td>";
        $html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_parameters("Loadjs('fw.3proxy.acls.php?ruleid=$ID')")."</center></td>";
        $html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_check($ligne["enabled"],"Loadjs('$page?service-enable=$ID')",null,"AsFirewallManager")."</center></td>";
        $html[]="<td style='width:1%' nowrap>$up&nbsp;$down</td>";
        $html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_delete("Loadjs('$page?service-delete={$ligne['ID']}&md=$md')","AsFirewallManager")."</center></td>";
        $html[]="</tr>";

    }


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_3PROXY} v$PrivoxyVersion &raquo;&raquo; {services}";
    $TINY_ARRAY["ICO"]="fa fa-align-justify";
    $TINY_ARRAY["EXPL"]="{APP_3PROXY_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$headsjs
</script>";


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function EXPLAIN_THIS($ligne){
    $tpl=new template_admin();
    $enabled=$ligne["enabled"];
    $listen_port=$ligne["listen_port"];
    $outgoing_interface=$ligne["outgoing_interface"];
    $listen_interface=$ligne["listen_interface"];
    $transparent=$ligne["transparent"];
    $service_type=$GLOBALS["ZTYPES"][$ligne["service_type"]];
    $redsocks=intval($ligne["redsocks"]);
    $redsocks_port=intval($ligne["redsocks_port"]);
    $redsocks_type=intval($ligne["redsocks_type"]);

    $szredsocks_type[1]="http-connect";
    $szredsocks_type[2]="http-relay";

    if($ligne["service_type"]==2){
        $redsocks_type_text="Socksv5";
    }
    if($ligne["service_type"]==100){
        $redsocks_type_text="Proxy {$szredsocks_type[$redsocks_type]}";
    }

    if($enabled==0){
        $f[]="[{disabled}]:&nbsp;";
    }



    $f[]="{listen} <strong>$listen_port</strong>";

    if($transparent==1){
        $data=base64_decode($ligne["transparentport"]);
        if($redsocks==0){$f[]="{in_transparent_mode_for_port}: <strong>".str_replace("\n", ",", $data)." </strong>";}
        if($redsocks==1){$f[]="{socksify} ($redsocks_type_text): <strong>".str_replace("\n", ",", $data)." </strong> {to} $redsocks_port {port}";}
    }

    if($listen_interface<>null){
        if($listen_interface<>"lo"){
            $eth=new system_nic($listen_interface);
            $f[]="{on_interface} <strong>$eth->IPADDR</strong> ($eth->NICNAME)";
        }else{
            $f[]="{on_interface} <strong>127.0.0.1</strong> (loopback)";
        }
    }
    if($outgoing_interface<>null){
        if($outgoing_interface<>"lo"){
            $eth1=new system_nic($outgoing_interface);
            $f[]="{and_use} <strong>$eth1->IPADDR ($eth1->NICNAME)</strong> {inroderto_forward_requests}";

        }else{
            $f[]="{and_use} <strong>127.0.0.1 (loopback)</strong> {inroderto_forward_requests}";
        }
    }

    if($ligne["service_type"]==4){
        $options=unserialize(base64_decode($ligne["options"]));
        $dst_addr=$options["dst_addr"];
        $dst_port=$options["dst_port"];
        if($dst_addr==null){$dst_addr="#!Error";}
        $f[]="{and_forward_tcp_connections_to} <strong>$dst_addr:$dst_port</strong>";
    }
    if($ligne["service_type"]==5){
        $options=unserialize(base64_decode($ligne["options"]));
        $dst_addr=$options["dst_addr"];
        $dst_port=$options["dst_port"];
        if($dst_addr==null){$dst_addr="#!Error";}
        $f[]="{and_forward_udp_connections_to} <strong>$dst_addr:$dst_port</strong>";
    }




    return $tpl->_ENGINE_parse_body(@implode(" ", $f));


}

function Save(){
    $sock=new sockets();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $_POST["zipproxy_MaxSize"]=$_POST["zipproxy_MaxSize"]*1024;

    foreach ($_POST as $key=>$value){
        $sock->SET_INFO($key, $value);
    }

}


