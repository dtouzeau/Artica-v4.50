<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ip_addr"])){net_save();exit;}
if(isset($_GET["net-js"])){net_js();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_POST["PortExclude"])){port_save();exit;}

if(isset($_GET["net-popup"])){net_popup();exit;}
if(isset($_GET["netmask-unlink"])){net_del();exit;}




page();

function net_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$net=intval($_GET["net-js"]);

	if($net==0){$title="{new_network}";}else{
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT pattern FROM hotspot_networks WHERE ID=$net");
	    $title=$ligne["pattern"];
	}
	return $tpl->js_dialog1($title, "$page?net-popup=$net",750);
}
function port_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $net=intval($_GET["port-js"]);
    return $tpl->js_dialog1("{listen_port} $net", "$page?port-popup=$net",550);
}

function net_del(){
    $page=CurrentPageName();
	$netid=intval($_GET["netmask-unlink"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM hotspot_networks WHERE ID=$netid");
    if(!$q->ok){echo $q->mysql_error;return;}
	echo "LoadAjax('table-loader-network-hotspot','$page?table=yes');";
}
function port_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $port=intval($_GET["port-popup"]);

    $exclude=0;
    $safter="dialogInstance1.close();";
    $excludePortsArray=excludePortsArray();
    if(isset($excludePortsArray[$port])){
        $exclude=1;
    }
    $form[]=$tpl->field_hidden("PortExclude",$port);
    $form[]=$tpl->field_checkbox("exclude", "{hotspotwhite}", $exclude);

    $html=$tpl->form_outside(null, @implode("\n", $form),"","{apply}","LoadAjax('table-loader-network-hotspot','$page?table=yes');$safter","AsHotSpotManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function port_save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $port=intval($_POST["PortExclude"]);
    $excludePortsArray=excludePortsArray();
    $exclude=intval($_POST["exclude"]);
    if($exclude==1){
        $excludePortsArray[$port]=true;

    }else{
        if(isset($excludePortsArray[$port])){
            unset($excludePortsArray[$port]);
        }
    }
    $tt=array();
    foreach ($excludePortsArray as $port=>$none){
        $tt[]=$port;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HotSpotDisablePort",@implode(",",$tt));
}


function net_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["net-popup"]);

	$bt="{add}";
	$safter="dialogInstance1.close();";

	$form[]=$tpl->field_hidden("netid",$ID);
	if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        if(!$q->FIELD_EXISTS("hotspot_networks","exclude")){
            $q->QUERY_SQL("ALTER TABLE hotspot_networks ADD exclude INT DEFAULT NULL");
        }

        $ligne=$q->mysqli_fetch_array("SELECT exclude,pattern FROM hotspot_networks WHERE ID=$ID");
        $network=$ligne["pattern"];
		$net=explode("/",$ligne["pattern"]);
        $exclude=intval($ligne["exclude"]);
		$title=$network;
		$bt="{apply}";
		if(intval($net[1])>0){
			$ipv=new ipv4($net[0],$net[1]);
			$net[0]=$ipv->address();
			$netmask=$ipv->netmask();
			$safter=null;
		} 	
	}
	

	if($ID==0){
		$form[]=$tpl->field_ipaddr("ip_addr", "{ip_address}", null);
		$form[]=$tpl->field_maskcdir("netmask", "{netmask}", null);
        $form[]=$tpl->field_checkbox("exclude", "{exclude}", null);

	}else{
		$form[]=$tpl->field_info("ip_addr", "{ip_address}", $net[0]);
		$form[]=$tpl->field_info("netmask", "{netmask}", $netmask);
		$form[]=$tpl->field_info("netmaskcdir", "{cdir}", $network);
        $form[]=$tpl->field_checkbox("exclude", "{exclude}", $exclude);
	}
	$html=$tpl->form_outside($title, @implode("\n", $form),"",$bt,"LoadAjax('table-loader-network-hotspot','$page?table=yes');$safter","AsHotSpotManager");
	echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html="
<div style='margin-top: 20px'>
	<div class='ibox-content'>
        <div id='table-loader-network-hotspot'></div>
    </div>
</div>
    <script>
    LoadAjax('table-loader-network-hotspot','$page?table=yes');
    </script>";
	echo $tpl->_ENGINE_parse_body($html);


}

function tinyurl():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/hotspot-web.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/hotspot-web.progress.log";
    $ARRAY["CMD"]="/proxy/hotspot/install";
    $ARRAY["TITLE"]="{reconfiguring}";
    $prgress=base64_encode(serialize($ARRAY));
    $add="Loadjs('$page?net-js=0');";
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-hotspot-restart')";


    $bouts="<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_network} </label>
    	<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_configuration} </label>
     </div>	";

    $TINY_ARRAY["TITLE"]="{web_portal_authentication}: {networks}";
    $TINY_ARRAY["ICO"]="fad fa-network-wired";
    $TINY_ARRAY["EXPL"]="{hotspot_network_explain}";
    $TINY_ARRAY["URL"]="hotspot-config";
    $TINY_ARRAY["BUTTONS"]=$bouts;
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

}

function net_save():bool{
    $netid=intval($_POST["netid"]);
    $exclude=intval($_POST["exclude"]);

	if(!isset($_POST["netmaskcdir"])){
		$ipaddr=url_decode_special_tool($_POST["ip_addr"]);
		$netmask=url_decode_special_tool($_POST["netmask"]);
		$cdir=$ipaddr."/".$netmask;
	}else{
		$cdir=url_decode_special_tool($_POST["netmaskcdir"]);
	}

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

    if($netid==0) {
        $sql = "INSERT OR IGNORE INTO hotspot_networks (pattern,exclude) VALUES ('$cdir','$exclude')";
    }
    if($netid>0){
        $sql = "UPDATE hotspot_networks SET pattern='$cdir',  exclude='$exclude' WHERE ID='$netid'";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}
    return true;
}


function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
    $tinyurl=tinyurl();
	$table[]="<table class='table table-hover'><thead>
	<tr>
	<th colspan='3'>{networks}</th>
	<th style='width:1%' nowrap>&nbsp;</th>
	</tr>
	</thead>
	<tbody>
	";



    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    if(!$q->FIELD_EXISTS("hotspot_networks","exclude")){
        $q->QUERY_SQL("ALTER TABLE hotspot_networks ADD exclude INT DEFAULT NULL");
    }
    $results=$q->QUERY_SQL("SELECT * FROM hotspot_networks");

    if(count($results)==0){
        $table[]="<tr id='000'>";
        $table[]="<td style='width:1%'><span class='label label-info'>{include}<span></td>";
        $table[]="<td style='width:1%'><i class='fad fa-network-wired'></i></td>";
        $table[]="<td>{all_networks}</td>";
        $table[]="<td>&nbsp;</td>";
        $table[]="</tr>";
        $table[]=PortsList();
        $table[]="</tbody></table><script>NoSpinner();\n$tinyurl</script>";
        echo $tpl->_ENGINE_parse_body(@implode("\n", $table));
        return true;
    }


foreach ($results as $index=>$ligne){
    $maks=$ligne["pattern"];
    $num=$ligne["ID"];
    $exclude=$ligne["exclude"];
    $excludetxt=null;
    $label="<span class='label label-info'>{include}<span>";
    if($exclude==1){
        $label="<span class='label label-primary'>{exclude}<span>";
    }
	if(trim($maks)==null){continue;}
	$id=md5($maks);
	$table[]="<tr id='$id'>";
    $table[]="<td style='width:1%'>$label</td>";
    $table[]="<td style='width:1%'><i class='fad fa-network-wired'></i></td>";
	$table[]="<td>". $tpl->td_href($maks.$excludetxt,"{click_to_edit}","Loadjs('$page?net-js=$num');")."</td>";
	$table[]="<td>". $tpl->icon_delete("Loadjs('$page?netmask-unlink=$num&md=$id')","AsHotSpotManager")."</td>";
	$table[]="</tr>";

	}
    $table[]=PortsList();
	$table[]="</tbody>";
    $table[]="</table>";
    $table[]="<script>NoSpinner();
        $tinyurl
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $table));
    return true;
}

function excludePortsArray():array{
    $ports=array();
    $HotSpotDisablePort=explode(",",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotDisablePort"));
    foreach ($HotSpotDisablePort as $port){
        if(!is_numeric($port)){continue;}
        $ports[$port]=true;
    }
    return $ports;
}

function PortsList():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $excludePortsArray=excludePortsArray();
    $table=array();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT port FROM proxy_ports WHERE enabled=1";
    $results = $q->QUERY_SQL($sql);
    foreach($results as $index=>$ligne){
        $port=$ligne["port"];
        $id="tt-$port";
        $exclude=0;
        if(isset($excludePortsArray[$port])){
            $exclude=1;
        }

        $label="<span class='label label-info'>{include}<span>";
        if($exclude==1){
            $label="<span class='label label-primary'>{exclude}<span>";
        }


        $table[]="<tr id='$id'>";
        $table[]="<td style='width:1%'>$label</td>";
        $table[]="<td style='width:1%'><i class='".ico_interface."'></i></td>";
        $table[]="<td>". $tpl->td_href("{listen_port} $port ({APP_PROXY})","{click_to_edit}","Loadjs('$page?port-js=$port');")."</td>";
        $table[]="<td>&nbsp;</td>";
        $table[]="</tr>";

    }
    return @implode("\n",$table);


}


