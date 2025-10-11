<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if(isset($_POST["KWTSIPAddr"])){kwts_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["enabled"])){enabled();exit;}
if(isset($_GET["bypass"])){bypass();exit;}
if(isset($_GET["move-item-js"])){move_items_js();exit;}
if(isset($_POST["move-item"])){move_items();exit;}
if(isset($_GET["ruleid-delete"])){ruleid_delete();exit;}
if(isset($_POST["ruleid-delete"])){ruleid_delete_confirm();exit;}

if(isset($_GET["kwts-js"])){kwts_js();exit;}
if(isset($_GET["kwts-popup"])){kwts_popup();exit;}
if(isset($_GET["kwts-disable"])){kwts_disable();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-start"])){table_start();exit;}

page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{icap_center}",
        "fas fa-plug","{icap_center_explain}","$page?tabs=yes","icap-center",
        "progress-icap-restart",false,"table-loader-icap-tabs");



    if(isset($_GET["main-page"])){$tpl=new template_admin("{icap_center}",$html);
    echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);

}
function table_start(){
    $page=CurrentPageName();
    echo "<div id='table-loader-icap-pages'></div>";
    echo "<script>LoadAjax('table-loader-icap-pages','$page?table=yes');</script>";

}
function ruleid_delete():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["md"];
    $id=intval($_GET["ruleid-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne = $q->mysqli_fetch_array("SELECT ipaddr,listenport,service_name FROM c_icap_services WHERE ID='$id'");
    if(!$q->ok){
        return $tpl->js_mysql_alert($q->mysql_error);

    }
   $text="{$ligne["ipaddr"]}:{$ligne["listenport"]}/{$ligne["service_name"]}";
    return $tpl->js_confirm_delete("$text","ruleid-delete",$id,"$('#$md').remove();");
}
function ruleid_delete_confirm():bool{
    $id=intval($_POST["ruleid-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne = $q->mysqli_fetch_array("SELECT ipaddr,listenport,service_name FROM c_icap_services WHERE ID='$id'");
    $text="{$ligne["ipaddr"]}:{$ligne["listenport"]}/{$ligne["service_name"]}";
    $q->QUERY_SQL("DELETE FROM c_icap_services WHERE ID='$id'");
    if(!$q->ok){
        echo $q->mysql_error;
        return false;
    }
    return admin_tracks("Remove ICAP service $text");
}

function tabs(){
//
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{icap_center}"]="$page?table-start=yes";
    $array["ICAP {policies}"]="fw.proxy.icap.acls.php?table-start=yes";
    echo $tpl->tabs_default($array);
}

function item_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM c_icap_services WHERE ID='$ID'");
    $title=$ligne["service_name"];
    $tpl->js_dialog1($title,"$page?item-popup=$ID",990);
}
function kwts_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{APP_KWTS_CONNECTOR}","$page?kwts-popup=yes",990);
}

function kwts_disable(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $KWTSDisable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSDisable"));
    if($KWTSDisable==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KWTSDisable",1);
        admin_tracks("Kaspersky Web Traffic Security link is passed to disabled mode");
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KWTSDisable",0);
        admin_tracks("Kaspersky Web Traffic Security link is passed to enabled mode");
    }

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?icap-silent=yes");
    echo "LoadAjax('table-loader-icap-pages','$page?table=yes');\n";

}

function enabled(){
    $ID=$_GET["enabled"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT service_name,enabled FROM c_icap_services WHERE ID='$ID'");
    $service_name=$ligne["service_name"];
    if(intval($ligne["enabled"])==1){
        $q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=$ID");
        if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return false;}
        admin_tracks("Change ICAP service $service_name to disabled");
    }else{
        $q->QUERY_SQL("UPDATE c_icap_services SET enabled=1 WHERE ID=$ID");
        if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
        admin_tracks("Change ICAP service $service_name to enabled");
    }

    if($ID==1){
        $page=CurrentPageName();
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?icap-silent=yes");
        header("content-type: application/x-javascript");
        echo "if(document.getElementById('cicap-main-status-start') ){\n";
        echo "\tLoadAjax('cicap-main-status-start','fw.proxy.c-icap.php?main-status=yes');\n";
        echo "}\n";
        echo "if(document.getElementById('table-loader-icap-pages') ){\n";
        echo "\tLoadAjax('table-loader-icap-pages','$page?table=yes');\n";
        echo "}\n";
    }

    return true;
}

function bypass(){
    $ID=$_GET["bypass"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT service_name,bypass FROM c_icap_services WHERE ID='$ID'");
    $service_name=$ligne["service_name"];
    if(intval($ligne["enabled"])==1){
        $q->QUERY_SQL("UPDATE c_icap_services SET bypass=0 WHERE ID=$ID");
        if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return false;}
        admin_tracks("Change ICAP service $service_name to not bypass when encounter error");
    }else{
        $q->QUERY_SQL("UPDATE c_icap_services SET bypass=1 WHERE ID=$ID");
        if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return false;}
        admin_tracks("Change ICAP service $service_name to bypass if error");
    }
    return true;
}

function move_items_js(){
    $page=CurrentPageName();
     $t=time();
    header("content-type: application/x-javascript");
    $html="

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();
";

    echo $html;

}
function move_items(){
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ID=$_POST["move-item"];
    $dir=$_POST["dir"];
    $ligne=$q->mysqli_fetch_array("SELECT zOrder FROM c_icap_services WHERE ID='$ID'");
    if(!$q->ok){echo $q->mysql_error;}


    $CurrentOrder=$ligne["zOrder"];

    if($dir==0){
        $NextOrder=$CurrentOrder-1;
    }else{
        $NextOrder=$CurrentOrder+1;
    }

    $sql="UPDATE c_icap_services SET zOrder=$CurrentOrder WHERE zOrder='$NextOrder'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}


    $sql="UPDATE c_icap_services SET zOrder=$NextOrder WHERE ID='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}

    $results=$q->QUERY_SQL("SELECT ID FROM c_icap_services ORDER by zOrder");
    if(!$q->ok){echo $q->mysql_error;}
    $c=1;
   foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $sql="UPDATE c_icap_services SET zOrder=$c WHERE ID='$ID'";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;}
        $c++;
    }

}
function save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $ID=$_POST["ID"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $service_name=$_POST["service_name"];
    $service_name=$q->sqlite_escape_string2($service_name);

    if($_POST["service_key"]==null){
        $_POST["service_key"]=md5("{$_POST["address"]}{$_POST["listenport"]}{$_POST["respmod"]}{$_POST["icap_server"]}");
    }


    if($ID==0){
        $sql="INSERT INTO c_icap_services (service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload)
			VALUES('$service_name','{$_POST["service_key"]}','{$_POST["respmod"]}',
			{$_POST["routing"]},{$_POST["bypass"]},{$_POST["enabled"]},
			{$_POST["zOrder"]},'{$_POST["address"]}','{$_POST["listenport"]}','{$_POST["icap_server"]}','{$_POST["maxconn"]}','{$_POST["overload"]}')";
    }


    if($ID>0){
        $sql="UPDATE c_icap_services SET
		`service_name`='$service_name',
		`service_key`='{$_POST["service_key"]}',
		`listenport`='{$_POST["listenport"]}',
		`icap_server`='{$_POST["icap_server"]}',
		`respmod`='{$_POST["respmod"]}',
		`routing`='{$_POST["routing"]}',
		`bypass`='{$_POST["bypass"]}',
		`enabled`='{$_POST["enabled"]}',
		`zOrder`='{$_POST["zOrder"]}',
		`maxconn`='{$_POST["maxconn"]}',
		`ipaddr`='{$_POST["address"]}',
		`overload`='{$_POST["overload"]}' WHERE ID=$ID";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$q->mysql_error;return;}

}

function kwts_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $KWTSIPAddr=$_POST["KWTSIPAddr"];
    $KWTSPort=$_POST["KWTSPort"];

    $fp=@fsockopen($KWTSIPAddr, $KWTSPort, $errno, $errstr, 1);
    if(!$fp){
        echo "jserror:".$tpl->javascript_parse_text($errstr);
        return false;
    }
    $sock=new sockets();
    $sock->getFrameWork("squid2.php?kwts-monit=yes");
    admin_tracks("Kaspersky Web Traffic settings modified $KWTSIPAddr:$KWTSPort");
    return true;

}

function kwts_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $KWTSIPAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSIPAddr");
    $KWTSByPass=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSByPass"));
    $KWTSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSPort"));
    $KWTSReqMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSReqMode"));
    $KWTSRepMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSRepMode"));
    $KWTSOverload=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSOverload"));
    $KWTSMaxCon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSMaxCon"));
    if($KWTSPort==0){$KWTSPort=1344;}
    if($KWTSReqMode==null){$KWTSReqMode="av/reqmod";}
    if($KWTSRepMode==null){$KWTSRepMode="av/respmod";}
    if($KWTSOverload==null){$KWTSOverload="wait";}
    if($KWTSMaxCon==null){$KWTSMaxCon=5000;}


    $overload["block"]="{block}";
    $overload["bypass"]="{bypass}";
    $overload["wait"]="{wait}";
    $overload["force"]="{force}";

    $form[] = $tpl->field_ipv4("KWTSIPAddr", "{address}", $KWTSIPAddr);
    $form[] = $tpl->field_numeric("KWTSPort", "{listen_port}", $KWTSPort);
    $form[] = $tpl->field_text("KWTSReqMode", "{icap_reqmode}", $KWTSReqMode);
    $form[] = $tpl->field_text("KWTSRepMode", "{icap_repmode}", $KWTSRepMode);

    $form[]=$tpl->field_array_hash($overload,"overload","{if_overloaded}",$KWTSOverload);
    $form[]=$tpl->field_checkbox("KWTSByPass","{bypass}",$KWTSByPass,False);
    $form[]=$tpl->field_numeric("maxconn","{max_connections}",$KWTSMaxCon);

    $reconf=reconfigure_progress();
    $js="$reconf;dialogInstance1.close();";
    echo $tpl->form_outside("{APP_KWTS_CONNECTOR}",$form,"{APP_KWTS_CONNECTOR_ABOUT}","{apply}",$js,"AsSquidAdministrator");

}

function item_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-popup"]);
    $bt="{add}";
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=array();
    $ligne["enabled"]=1;
    $ligne["zOrder"]=0;
    if($ID>0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM c_icap_services WHERE ID='$ID'");
        $bt = "{apply}";
    }

    if($ligne["overload"]==null){$ligne["overload"]="bypass";}

    $respmod["reqmod_precache"]="REQMOD";
    $respmod["respmod_precache"]="RESPMOD";

    $overload["block"]="{block}";
    $overload["bypass"]="{bypass}";
    $overload["wait"]="{wait}";
    $overload["force"]="{force}";

    $tpl->field_hidden("ID",$ID);
    $tpl->field_hidden("service_key",$ligne["service_key"]);
    if($ID==1 OR $ID == 2){
        $form[] = $tpl->field_hidden("service_name",  $ligne["service_name"]);
    }else {
        $form[] = $tpl->field_text("service_name", "{service_name}", $ligne["service_name"]);
    }
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],True);
    $form[]=$tpl->field_numeric("zOrder","{order}",$ligne["zOrder"]);

    if($ID==1 or $ID==2){
        $ListenAddress=CICAP_Addr();
        $form[]=$tpl->field_info("address","{address}",$ListenAddress);
        $form[]=$tpl->field_info("listenport","{listen_port}","1345");
        $form[]=$tpl->field_info("icap_server","{icap_service_name}","srv_clamav");
        if($ID==1){
            $form[]=$tpl->field_hidden("respmod","respmod_precache");
        }
        if($ID==2){
            $form[]=$tpl->field_hidden("respmod","reqmod_precache");
        }
    }else {
        $form[] = $tpl->field_ipv4("address", "{address}", $ligne["ipaddr"]);
        $form[] = $tpl->field_numeric("listenport", "{listen_port}", $ligne["listenport"]);
        $form[] = $tpl->field_text("icap_server", "{icap_service_name}", $ligne["icap_server"]);
        $form[] = $tpl->field_array_hash($respmod, "respmod", "{type}", $ligne["respmod"]);
    }
    $form[]=$tpl->field_array_hash($overload,"overload","{if_overloaded}",$ligne["overload"]);
    $form[]=$tpl->field_checkbox("routing","X-Next-Services",$ligne["routing"],False);
    $form[]=$tpl->field_checkbox("bypass","{bypass}",$ligne["bypass"],False);
    $form[]=$tpl->field_numeric("maxconn","{max_connections}",$ligne["maxconn"]);

    $reconf=reconfigure_progress();
    $js="LoadAjax('table-loader-icap-pages','$page?table=yes');dialogInstance1.close();";
    echo $tpl->form_outside($ligne["service_name"],$form,null,$bt,$js,"AsSquidAdministrator");


}

function reconfigure_progress(){
    $page           = CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    $ARRAY["CMD"]="squid2.php?icap-silent=yes";
    $ARRAY["AFTER"]="LoadAjax('table-loader-icap-pages','$page?table=yes');";
    $ARRAY["TITLE"]="{apply}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-icap-restart')";
    return $jsrestart;
}

function CICAP_Addr():string{
    $tpl=new template_admin();
    $CICAPListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPListenInterface"));
    if($CICAPListenInterface==null){$CICAPListenInterface="lo";}
    if($CICAPListenInterface=="lo"){return "127.0.0.1";}
    $ListenAddress = $tpl->InterfaceTOIP($CICAPListenInterface);
    if ($ListenAddress == null) {
        $ListenAddress = "127.0.0.1";
    }
    if(preg_match("#^127\.0\.0#",$ListenAddress)){return "127.0.0.1";}
    return $ListenAddress;
}

function table(){
    $TRCLASS        = null;
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $add            = "Loadjs('$page?item-js=0');";
    $error_gen      = null;
    $textclassip    = null;
    $kwts_info      = null;
    $jsrestart      = reconfigure_progress();
    $t=time();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cicap.php?clients-scan=yes");

    $btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"LoadAjax('table-loader-icap-pages','$page?table=yes');\"><i class='fas fa-sync'></i> {refresh} </label>
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_service} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {save_config_to_server} </label>
			</div>
			<div class=\"btn-group\" data-toggle=\"buttons\"></div>");




    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{order}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{service_name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' width=1%>{address}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' width=1%>{mode}</th>";
    $html[]="<th data-sortable=false width=1%>{bypass}</th>";
    $html[]="<th data-sortable=false width=1%>{move}</th>";
    $html[]="<th data-sortable=false width=1%>{enabled}</th>";
    $html[]="<th data-sortable=false width=1%>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $sql="SELECT * FROM c_icap_services ORDER BY zOrder";
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL($sql);


    $STATUS_ARRAY[0]="<span class=label>{disabled}</span>";
    $STATUS_ARRAY[1]="<span class='label label-primary'>{active2}</span>";
    $STATUS_ARRAY[2]="<span class='label label-danger'>{error}</span>";
    $STATUS_ARRAY[3]="<span class='label label-warning'>{error}</span>";
    $STATUS_ARRAY[4]="<span class='label label-warning'>{inactive}</span>";
    $STATUS_ARRAY[5]="<span class='label label-danger'>{emergency2}</span>";

    $RESMODE["respmod_precache"]="{response}";
    $RESMODE["reqmod_precache"]="{request}";
    $RESMODE_ALL="{all}";

    $KWTSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSEnabled"));


    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]=cicap_status($TRCLASS);




    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    if($KWTSEnabled==1){
        $KWTS_STATUS=1;
        $KWTSIPAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSIPAddr");
        $KWTSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSPort"));
        $KWTSByPass=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSByPass"));
        $KWTSDisable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSDisable"));
        if($KWTSPort==0){$KWTSPort=1344;}
        if($KWTSIPAddr==null){
            $textclassip="text-danger";
            $KWTSIPAddr="x.x.x.x";
            $KWTS_STATUS=3;
        }else{
            $fp=@fsockopen($KWTSIPAddr, $KWTSPort, $errno, $errstr, 1);
            if(!$fp){
                $textclassip="text-danger";
                $KWTS_STATUS=2;
                $error_gen=$errstr;
            }else{

                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?kwts-check=yes");
                $KWTS_INFOS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTS_INFOS"));
                $kwts_service=$KWTS_INFOS["SERVICE"];
                $kwts_tag=$KWTS_INFOS["ISTag"];
                $kwts_info=" <small>($kwts_service - $kwts_tag)</small>";
            }
        }
        $bypass=null;
        if($KWTSDisable==0){
            $disabled="
            <a href='#' OnClick=\"javascript:Loadjs('$page?kwts-disable=yes');\">
            <span class='label label-primary'>{running}</span>
            </a>";
        }else{
            $disabled="
            <a href='#' OnClick=\"javascript:Loadjs('$page?kwts-disable=yes');\">
            <span class='label label-danger'>{stopped}</span>
            </a>";
        }

        if($KWTSDisable){$KWTS_STATUS=4;}
        if($KWTSByPass==1){$bypass="<span class='fas fa-check'></span>";}
        $html[]="<tr class='$TRCLASS' id='none'>";
        $html[]="<td width='1%' nowrap>$STATUS_ARRAY[$KWTS_STATUS]</td>";
        $html[]="<td width='1%'>0</td>";
        $html[]="<td><strong>".$tpl->td_href("{APP_KWTS_CONNECTOR}",null,"Loadjs('$page?kwts-js=yes');")."&nbsp;<small class='$textclassip'>$error_gen</small></strong>$kwts_info</td>";
        $html[]="<td><strong class='$textclassip'>$KWTSIPAddr:$KWTSPort</strong></td>";
        $html[]="<td width='1%' nowrap>$RESMODE_ALL</td>";
        $html[]="<td width='1%' nowrap align='center'>$bypass</td>";
        $html[]="<td width=1% align='center' nowrap=''>&nbsp;</td>";
        $html[]="<td width='1%' nowrap align='center'>$disabled</td>";
        $html[]="<td width=1% align='center'>".$tpl->icon_nothing()."</td>";
        $html[]="</tr>";
    }else{
        $html[]="<tr class='$TRCLASS' id='none'>";
        $html[]="<td width='1%' nowrap>$STATUS_ARRAY[0]</td>";
        $html[]="<td width='1%'>0</td>";
        $html[]="<td><strong class='text-muted'>{APP_KWTS_CONNECTOR} ({APP_KWTS_CONNECTOR_ABOUT})</strong></td>";
        $html[]="<td><strongclass='text-muted'>".$tpl->icon_nothing()."</strong></td>";
        $html[]="<td width='1%' nowrap>$RESMODE_ALL</td>";
        $html[]="<td width='1%' nowrap align='center'>".$tpl->icon_nothing()."</td>";
        $html[]="<td width=1% align='center' nowrap=''>&nbsp;</td>";
        $html[]="<td width='1%' nowrap align='center'>&nbsp;</span></td>";
        $html[]="<td width=1% align='center'>".$tpl->icon_nothing()."</td>";
        $html[]="</tr>";

    }


    $C_ICAP_CLIENTS_SCAN=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_CLIENTS_SCAN"));


    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $colortext=null;
        if($ID==1 OR $ID == 2){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne).$index);
        $up=$tpl->icon_up("Loadjs('$page?move-item-js=yes&ID={$ligne['ID']}&dir=0')","AsProxyMonitor");
        $down=$tpl->icon_down("Loadjs('$page?move-item-js=yes&ID={$ligne['ID']}&dir=1')","AsProxyMonitor");
        $del=$tpl->icon_delete("Loadjs('$page?ruleid-delete={$ligne["ID"]}&md=$md')","AsSquidAdministrator");
        $bypass=$tpl->icon_check($ligne["bypass"],"Loadjs('$page?bypass={$ligne['ID']}')","AsProxyMonitor");
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled={$ligne['ID']}')","AsProxyMonitor");

        if($ligne["ID"]<20){$del=$tpl->icon_nothing();}
        if($ligne["ID"]==1 or $ligne["ID"]==2) {
            $ListenAddress = CICAP_Addr();
            $ligne["ipaddr"]=$ListenAddress;
            $ligne["listenport"]=1345;
        }

        if($ligne["enabled"]==0){
            $colortext="#CCCCCC";
        }
        if($ligne["enabled"]==1){
            if($ligne["status"]<5){
                if(!isset($C_ICAP_CLIENTS_SCAN[$ligne['ID']])){
                    $ligne["status"]=4;
                }
            }
        }
        $status=$STATUS_ARRAY[$ligne["status"]];

        if($colortext<>null) {
            $color = "style='color:$colortext'";
        }
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%' nowrap>$status</td>";
        $html[]="<td width='1%' $color>{$ligne["zOrder"]}</td>";
        $html[]="<td><strong $color>".$tpl->td_href($ligne["service_name"],null,"Loadjs('$page?item-js=$ID');")."</strong></td>";
        $html[]="<td><strong $color>{$ligne["ipaddr"]}:{$ligne["listenport"]}</strong></td>";
        $html[]="<td width='1%' nowrap><strong $color>{$RESMODE[$ligne["respmod"]]}</strong></td>";
        $html[]="<td width='1%' nowrap align='center' $color>$bypass</td>";
        $html[]="<td width=1% align='center' nowrap='' $color>$up&nbsp;$down</td>";
        $html[]="<td width='1%' nowrap align='center'>$enabled</td>";
        $html[]="<td width=1% align='center'>$del</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='9'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $TINY_ARRAY["TITLE"]="{icap_center}";
    $TINY_ARRAY["ICO"]="fas fa-plug";
    $TINY_ARRAY["EXPL"]="{icap_center_explain}";
    $TINY_ARRAY["URL"]="icap-center";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
$jstiny
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function cicap_status($TRCLASS):string{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $error_gen  = null;
    $icap_info  = null;
    $STATUS     = 1;
    $CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
    $ListenAddress=CICAP_Addr();



    $STATUS_ARRAY[0]="<span class=label>{disabled}</span>";
    $STATUS_ARRAY[1]="<span class='label label-primary'>{active2}</span>";
    $STATUS_ARRAY[2]="<span class='label label-danger'>{error}</span>";
    $STATUS_ARRAY[3]="<span class='label label-warning'>{error}</span>";
    $STATUS_ARRAY[4]="<span class='label label-warning'>{inactive}</span>";




    if($CicapEnabled==0) {
        $html[] = "<tr class='$TRCLASS' id='none'>";
        $html[] = "<td width='1%' nowrap>$STATUS_ARRAY[0]</td>";
        $html[] = "<td width='1%'>0</td>";
        $html[] = "<td><strong class='text-muted'>{SERVICE_WEBAVEX}</strong></td>";
        $html[] = "<td><strongclass='text-muted'>$ListenAddress:1345</strong></td>";
        $html[] = "<td width='1%' nowrap>" . $tpl->icon_nothing() . "</td>";
        $html[] = "<td width='1%' nowrap align='center'>" . $tpl->icon_nothing() . "</td>";
        $html[] = "<td width=1% align='center' nowrap=''>&nbsp;</td>";
        $html[] = "<td width='1%' nowrap align='center'>&nbsp;</span></td>";
        $html[] = "<td width=1% align='center'>" . $tpl->icon_nothing() . "</td>";
        $html[] = "</tr>";
        return @implode("\n", $html);
    }
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cicap.php?checks=yes");
    $MAIN=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPCHK_INFOS"));

    if(isset($MAIN["ERROR"])){
        $STATUS=2;
        $error_gen= "<br>{$MAIN["ERROR"]}";
    }

    if(isset($MAIN["SERVICE"])){
        $MAIN["SERVICE"]=str_replace(" - Url_Check demo service","",$MAIN["SERVICE"]);
        $icap_info=" <small>({$MAIN["SERVICE"]})</small>";

    }


    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM c_icap_services WHERE ID=1");
    if($ligne["enabled"]==0){$STATUS=4;}

    if(!isset($MAIN["ERROR"])) {
        $fp = @fsockopen($ListenAddress, "1345", $errno, $errstr, 1);
        if (!$fp) {
            $textclassip = "text-danger";
            $STATUS = 2;
            $error_gen = "<br>$errstr";
        }
    }

    if($ligne["enabled"]==1){
        $enabled="
            <a href='#' OnClick=\"javascript:Loadjs('$page?enabled=1');\">
            <span class='label label-primary'>{running}</span>
            </a>";
    }else{
        $enabled="
            <a href='#' OnClick=\"javascript:Loadjs('$page?enabled=1');\">
            <span class='label label-danger'>{stopped}</span>
            </a>";
    }

    $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
    $CICAPEnableSandBox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPEnableSandBox"));
    if($ligne["enabled"]==0){$EnableClamavInCiCap=0;$CICAPEnableSandBox=0;}
    $modules[]="<table style='width:5%;margin-top:5px'>";
    $modules[]="<tr>";
    if($EnableClamavInCiCap==1) {
        $modules[] = "<td width='1%' nowrap><span class='label label-primary'>antimalware</span></td>";
    }else{
        $modules[] = "<td width='1%' nowrap><span class='label'>antimalware [OFF]</span></td>";
    }
    if($CICAPEnableSandBox==1){
        $modules[] = "<td style='width:1%;padding-left:5px' nowrap><span class='label label-primary'>SandBox</span></td>";
    }else{
        $modules[] = "<td style='width:1%;padding-left:5px' nowrap><span class='label'>SandBox [OFF]</span></td>";
    }
    $modules[]="</tr>";
    $modules[]="</table>";
    $mmods=@implode("",$modules);


    $bypass=$tpl->icon_check($ligne["bypass"],"Loadjs('$page?bypass=1')","AsProxyMonitor");
    $link=$tpl->td_href("{SERVICE_WEBAVEX}",null,"Loadjs('$page?item-js=1');");
    $html[]="<tr class='$TRCLASS' id='ICAPAV'>";
    $html[]="<td width='1%' nowrap>{$STATUS_ARRAY[$STATUS]}</td>";
    $html[]="<td width='1%'>0</td>";
    $html[]="<td><strong class='$textclassip'>$link</strong>$icap_info$error_gen$mmods</td>";
    $html[]="<td><strong class='$textclassip'>$ListenAddress:1345</strong></td>";
    $html[]="<td width='1%' nowrap><strong>{all}</strong></td>";
    $html[]="<td width='1%' nowrap align='center'>$bypass</td>";
    $html[]="<td width=1% align='center' nowrap=''>" . $tpl->icon_nothing() . "</td>";
    $html[]="<td width='1%' nowrap align='center'>$enabled</td>";
    $html[]="<td width=1% align='center'>" . $tpl->icon_nothing() . "</td>";
    $html[]="</tr>";
    return @implode("\n", $html);



}