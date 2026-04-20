<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once("/usr/share/artica-postfix/ressources/class.nginx.params.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["md5"])){port_save();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["outgoing"])){outgoing_js();exit;}
if(isset($_POST["outgoing"])){outgoing_save();exit;}
if(isset($_GET["outgoing-popup"])){outgoing_popup();exit;}
table_start();


function table_start():bool{
	$page=CurrentPageName();
	$ID=$_GET["service"];
	echo "<div id='stream-listen-ports-$ID'></div>
	<script>LoadAjax('stream-listen-ports-$ID','$page?table=$ID')</script>";
    return true;
}
function delete_js():bool{
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=$_GET["delete"];
	$md5=$_GET["md5"];
	$q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT serviceid,`interface`,`port` FROM stream_ports WHERE ID=$ID");
    $serviceid=$ligne["serviceid"];
	return $tpl->js_confirm_delete("{$ligne["interface"]}:{$ligne["port"]}", "delete", "$ID","$('#$md5').remove();Loadjs('fw.nginx.sites.php?td-row=$serviceid');");
}
function delete():bool{
	preg_match("#([0-9]+):(.+)#", $_POST["delete"],$re);
	$ID=$_POST["delete"];
	$q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT serviceid,port FROM stream_ports WHERE ID=$ID");
    $serviceid=$ligne["serviceid"];
    $port=$ligne["port"];
	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"stream_ports","where"=>["ID"=>$ID]]);
    $ligne=$q->mysqli_fetch_array("SELECT serviceid FROM stream_ports WHERE ID=$ID");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($ligne["serviceid"]);
    $servername=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Delete port $port from $servername reverse-proxy site");
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}

function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function outgoing_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=$_GET["outgoing"];
    $title="{outgoing_interface}";
    return $tpl->js_dialog3($title, "$page?outgoing-popup=$serviceid",550);
}
function outgoing_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=$_GET["outgoing-popup"];
    $html[]=$tpl->field_hidden("outgoing",$serviceid);
    $sock=new socksngix($serviceid);
    $ligne=$sock->GetCache();
    $OutGoingInterface=$ligne["OutGoingInterface"];
    $OutGoingTransparent=$ligne["OutGoingTransparent"];

    $html[]=$tpl->field_interfaces("OutGoingInterface","{outgoing_interface}",$OutGoingInterface);
    $html[]=$tpl->field_checkbox("OutGoingTransparent","{transparent}",$OutGoingTransparent);
    $js="dialogInstance3.close();LoadAjax('stream-listen-ports-$serviceid','$page?table=$serviceid');Loadjs('fw.nginx.sites.php?td-row=$serviceid');";
    echo $tpl->form_outside("", $html,"","{apply}",$js,"AsSystemWebMaster");
    return true;
}
function outgoing_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid=$_POST["outgoing"];
    $sock=new socksngix($serviceid);
    $ligne=$sock->GetCache();
    $sock->SET_MULTI(array("OutGoingInterface"=>$_POST["OutGoingInterface"],"OutGoingTransparent"=>$_POST["OutGoingTransparent"]));
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    $servicename=$ligne["servicename"];
    return admin_tracks("Save reverse-proxy $servicename Outgoing interface to {$_POST["OutGoingInterface"]} transparent={$_POST["OutGoingTransparent"]}");
}

function port_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=0;
	$ID=$_GET["port-js"];
    if(isset($_GET["serviceid"])) {
        $serviceid = intval($_GET["serviceid"]);
    }
	$md5=$_GET["md5"];
	$title="{listen_port}";
	if($md5==null){$title="{new_entry}";}
	return $tpl->js_dialog3($title, "$page?port-popup=$ID&serviceid=$serviceid&md5=$md5");
}
function port_popup():bool{
    $nginxsock=new socksngix(0);
    $NginxProxyProtocol=$nginxsock->GET_INFO("NginxProxyProtocol");
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=intval($_GET["port-popup"]);
	$serviceid=$_GET["serviceid"];
	$md5=$_GET["md5"];
	$title="{new_item}";
	
	$http2_disabled=false;

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginxHTTP2Module"))==0){
        $http2_disabled=true;
    }


	
	$btname="{add}";
	$q=new lib_sqlite(NginxGetDB());
    $ligne["port"]=443;
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM stream_ports WHERE ID=$ID");
		$btname="{apply}";
		$title="{$ligne["interface"]}:{$ligne["port"]}";
		$serviceid=$ligne["serviceid"];
		$md5=$ligne["zmd5"];
	}
	$js="dialogInstance3.close();LoadAjax('stream-listen-ports-$serviceid','$page?table=$serviceid');Loadjs('fw.nginx.sites.php?td-row=$serviceid');";

	if(intval($ligne["port"])<5){$ligne["port"]=80;}

	$options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_hidden("md5", $md5);
	$form[]=$tpl->field_hidden("serviceid", $serviceid);
    if(!isHarmpID()) {
        if(!isset($ligne["interface"])){$ligne["interface"]="eth0";}
        $form[] = $tpl->field_interfaces("interface", "nooloop:nodef:{listen_interface}", $ligne["interface"]);
    }
	$form[]=$tpl->field_numeric("port","{listen_port}",$ligne["port"]);
	
	$form[]=$tpl->field_section("{options}");
	$form[]=$tpl->field_checkbox("ssl","{UseSSL}",$options["ssl"]);
	$form[]=$tpl->field_checkbox("http2","HTTP/2",$options["http2"],false,null,$http2_disabled);

	if($NginxProxyProtocol==1) {
        $form[] = $tpl->field_checkbox("proxy_protocol", "{proxy_protocol}", $options["proxy_protocol"]);
    }else{
	    $form[]=$tpl->field_info("proxy_protocol","{proxy_protocol}","{disabled}");
    }
	
	echo $tpl->form_outside($title, $form,"",$btname,"$js","AsSystemWebMaster");
    return true;
}

function port_save():bool{
    $nginxsock=new socksngix(0);

    $NginxProxyProtocol=$nginxsock->GET_INFO("NginxProxyProtocol");
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$tpl->CLEAN_POST();
	$ID=intval($_POST["ID"]);
	$serviceid=intval($_POST['serviceid']);
    $nginxsock2=new socksngix($serviceid);
	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"stream_ports","where"=>["serviceid"=>"0"]]);
    if(!isset($_POST["interface"])){$_POST["interface"]="";}

    $interface=$_POST["interface"];
    $port=intval($_POST["port"]);
    if($port<5){
        $_POST["ssl"]=1;
        $port=443;
    }
	$md5=md5("$interface$port$serviceid");
	if($NginxProxyProtocol==0){$_POST["proxy_protocol"]=0;}
    $options=base64_encode(serialize($_POST));
    $ssl_certificate=$nginxsock2->GET_INFO("ssl_certificate");

	if($ID==0){
        if($port==443){
            $_POST["ssl"]=1;
            $options=base64_encode(serialize($_POST));
            if(strlen($ssl_certificate)<3){
                $nginxsock2->SET_INFO("ssl_certificate","__DEFAULT__");
            }
        }

        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"stream_ports","where"=>["zmd5"=>$md5]]);
	    $sql="INSERT INTO stream_ports(serviceid,interface,port,zmd5,options) VALUES ($serviceid,'$interface',$port,'$md5','$options')";
	    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"insert","table"=>"stream_ports","values"=>["serviceid"=>$serviceid,"interface"=>$interface,"port"=>$port,"zmd5"=>$md5,"options"=>$options]]);
		return false;
	}

    if($port==443){
        $_POST["ssl"]=1;
        $options=base64_encode(serialize($_POST));
        if(strlen($ssl_certificate)<3){
            $nginxsock2->SET_INFO("ssl_certificate","__DEFAULT__");
        }
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"stream_ports","where"=>["zmd5"=>$md5]]);
	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"insert","table"=>"stream_ports","values"=>["serviceid"=>$serviceid,"interface"=>$interface,"port"=>$port,"zmd5"=>$md5,"options"=>$options]]);
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
    $servername=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Add new port $interface:$port for $servername reverse-proxy website");
	
}

function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["table"]);
    $function="";
    if(isset($_GET["function"])){$function=$_GET["function"];}
    $nginxServ=new socksngix($serviceid);
    $ligne=$nginxServ->GetCache();
    $OutGoingInterface=$ligne["OutGoingInterface"];
    $OutGoingTransparent=$ligne["OutGoingTransparent"];
    $NginxProxyProtocol=$ligne["NginxProxyProtocol"];
    $OutGoingInterfaceText=$OutGoingInterface;

    if(strlen($OutGoingInterface)<3){
        $OutGoingInterface="{none}";
        $OutGoingInterfaceText="";
    }
    if ($OutGoingTransparent==1){
        $OutGoingInterface="$OutGoingInterface {transparent}";

    }

    $topbuttons[]=array("Loadjs('$page?port-js=0&serviceid=$serviceid&md5=');",ico_plus,"{new_entry}");
    $topbuttons[]=array("Loadjs('$page?outgoing=$serviceid&function=$function')",ico_nic,"{outgoing_interface}:$OutGoingInterface");
    $buttons=$tpl->th_buttons($topbuttons);

	$html[]="<div style='margin-top:10px;margin-bottom:10px'>$buttons</div>";
	$html[]="<table id='table-listenport-$serviceid' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{interfaces}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap></th>";
    $html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	

	$q=new lib_sqlite(NginxGetDB());
	$results=$q->QUERY_SQL("SELECT * FROM stream_ports WHERE serviceid='{$_GET["table"]}'");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return false;}

    $OutGoingInterfaceArrow="&nbsp;";
    $icob="";$arroww="1%";
    if(strlen($OutGoingInterfaceText)>3){
        $eth=new system_nic($OutGoingInterfaceText);
        $arroww="20%";
        if(trim($eth->NETWORK)<>null) {
            $NICNAME = $eth->NICNAME . " -  <small>($eth->NETWORK)</small>";
        }else{
            $NICNAME = $eth->NICNAME;
        }
        $icob="<i class='".ico_nic." fa-2x'>";
        $OutGoingInterfaceText="<strong style='font-size:18px'>$eth->IPADDR</strong><br>$NICNAME";
        $OutGoingInterfaceArrow="<i class='".ico_arrow_right." fa-4x'></i>";
    }
	
	
	$TRCLASS=null;
	foreach ($results as $md5=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
		$md5=md5(serialize($ligne));
		$interface=$ligne["interface"];
        $pproto=null;$phttp2=null;
        $NICNAME="";
		if($interface==null){$interface="{all}";}else{
			$eth=new system_nic($interface);
            $Ipaddr=$eth->IPADDR;
            if(trim($eth->NETWORK)<>null) {
                $NICNAME = $eth->NICNAME . " -  <small>($eth->NETWORK)</small>";
            }else{
                $NICNAME = $eth->NICNAME;
            }
            if($Ipaddr=="0.0.0.0"){
                if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"))==1 || intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"))==1){

                    $interfaceE=explode(":",$interface);
                    $interfaceID=intval($interfaceE[1]);
                    if($interfaceID>0){
                        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
                        $ligne2=$q->mysqli_fetch_array("SELECT * FROM nics_virtuals WHERE ID={$interfaceID}");
                        if(strlen($ligne2["ipaddr"])>3){
                            $Ipaddr=$ligne2["ipaddr"];
                        }

                    }

                }
            }
		}
		$proto="http";
		$ID=intval($ligne["ID"]);
		$port=$ligne["port"];
		if($options["ssl"]==1){
            $proto="https";
            $phttp2="&nbsp;<span class='label label-warning'>SSL</span>";
            if($options["http2"]==1){
                $phttp2="&nbsp;<span class='label label-warning'>SSL - http2</span>";
            }
        }
		if($NginxProxyProtocol==1) {
            if (intval($options["proxy_protocol"]) == 1) {
                $pproto = "&nbsp;<span class='label label-info'>PROXY PROTOCOL</span>";
            }
        }


		$js="Loadjs('$page?port-js=$ID&md5=$md5')";
		if(count($options)==0){$options[]=$tpl->icon_nothing();}

        //$OutGoingInterface
		
		$html[]="<tr class='$TRCLASS' id='$md5'>";
        $html[]="<td style='width:1%' nowrap><i class='".ico_nic." fa-2x'></i></td>";
		$html[]="<td style='width:50%'>".$tpl->td_href("<span style='font-size:18px'>$proto://$Ipaddr:$port</span>",null,$js)."<br>$interface ($NICNAME) $pproto$phttp2</td>";
		$html[]="<td style='width:$arroww' nowrap>$OutGoingInterfaceArrow</td>";
        $html[]="<td style='width:1%'>$icob</td>";
        $html[]="<td style='width:50%'>$OutGoingInterfaceText</td>";
		$html[]="<td style='width:1%' class='center'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md5=$md5')","AsSystemWebMaster")."</td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}