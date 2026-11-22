<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ip_addr"])){net_save();exit;}
if(isset($_GET["net-js"])){net_js();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_POST["PortExclude"])){port_save();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_POST["import_networks"])){import_save();exit;}

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

function import_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{hotspot_import_networks}", "$page?import-popup=yes",750);
}

function import_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_textarea("import_networks", "{networks}", "","100%","350px");
    $form[]=$tpl->field_checkbox("exclude", "{exclude}", 0);
    $html[]=$tpl->div_explain("{hotspot_import_networks}||{hotspot_import_networks_explain}");
    $html[]=$tpl->form_outside("", @implode("\n", $form),"","{import}","dialogInstance1.close();LoadAjax('table-loader-network-hotspot','$page?table=yes');","AsHotSpotManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

/**
 * Normalise une entree importee en "adresse/prefixe".
 * Une adresse seule devient un hote (/32 ou /128). Renvoie "" si invalide.
 */
function import_normalize($entry):string{
    $entry=trim($entry);
    $prefix=null;
    if(strpos($entry,"/")!==false){
        list($entry,$prefix)=explode("/",$entry,2);
        if(!preg_match('#^[0-9]{1,3}$#',$prefix)){return "";}
        $prefix=intval($prefix);
    }
    if(filter_var($entry,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
        if($prefix===null){$prefix=32;}
        if($prefix<1 || $prefix>32){return "";}
        return "$entry/$prefix";
    }
    if(filter_var($entry,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)){
        if($prefix===null){$prefix=128;}
        if($prefix<1 || $prefix>128){return "";}
        return "$entry/$prefix";
    }
    return "";
}

function import_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsHotSpotManager){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $exclude=intval($_POST["exclude"]);
    // form_outside encode deja le champ : decoder completement (les "/" et "," en %2F/%2C).
    $raw=rawurldecode(url_decode_special_tool((string)$_POST["import_networks"]));

    // Une entree par ligne ; virgules, points-virgules et espaces separent aussi.
    // Tout ce qui suit un # est un commentaire.
    $valid=array();
    $invalid=array();
    foreach(preg_split('#\r?\n#',$raw) as $line){
        $line=preg_replace('/#.*$/','',$line);
        foreach(preg_split('#[\s,;]+#',$line,-1,PREG_SPLIT_NO_EMPTY) as $entry){
            $cdir=import_normalize($entry);
            if($cdir==""){$invalid[]=$entry;continue;}
            $valid[$cdir]=true;
        }
    }
    // Import tout-ou-rien : une seule entree invalide refuse le lot, pour que
    // l'administrateur corrige sa liste au lieu d'un import partiel silencieux.
    if(count($invalid)>0){
        echo $tpl->post_error("{invalid_ipaddr}:\n".@implode("\n",array_slice($invalid,0,20)));
        return false;
    }
    if(count($valid)==0){
        echo $tpl->post_error("{hotspot_import_empty}");
        return false;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    if(!$q->FIELD_EXISTS("hotspot_networks","exclude")){
        $q->QUERY_SQL("ALTER TABLE hotspot_networks ADD exclude INT DEFAULT NULL");
    }
    // pattern est UNIQUE : un reseau deja present est ignore.
    foreach($valid as $cdir=>$none){
        $q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO hotspot_networks (pattern,exclude) VALUES (?,?)",array($cdir,$exclude));
        if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    }
    admin_tracks("HotSpot: imported ".count($valid)." network(s) (exclude=$exclude)");
    return true;
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
    $import="Loadjs('$page?import-js=yes');";
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-hotspot-restart')";


    $bouts="<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_network} </label>
    	<label class=\"btn btn btn-primary\" OnClick=\"$import\"><i class='fas fa-file-import'></i> {hotspot_import_networks} </label>
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

	// INJECTION SQL: $cdir vient directement des champs du formulaire et etait
	// concatene TEL QUEL dans l'INSERT comme dans l'UPDATE. Une apostrophe
	// suffisait a sortir de la chaine SQLite. On valide la forme attendue --
	// une adresse IPv4/IPv6 suivie d'un prefixe -- et on refuse le reste.
	$cdir=trim((string)$cdir);
	if(!preg_match('#^[0-9a-fA-F:.]{1,45}/[0-9]{1,3}$#',$cdir)){
		echo "{invalid_ipaddr}: ".htmlspecialchars($cdir);
		return false;
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
	$tinyurl=tinyurl();

	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	if(!$q->FIELD_EXISTS("hotspot_networks","exclude")){
		$q->QUERY_SQL("ALTER TABLE hotspot_networks ADD exclude INT DEFAULT NULL");
	}
	$results=$q->QUERY_SQL("SELECT * FROM hotspot_networks");
	if(!is_array($results)){$results=array();}

	$rows=array();
	if(count($results)==0){
		// Aucun reseau enregistre: le portail couvre tout le trafic.
		$rows[]=hsnet_row("000","fad fa-network-wired","{all_networks}",hsnet_mode_badge(0),"&nbsp;");
	}else{
		foreach($results as $ligne){
			$pattern=trim((string)($ligne["pattern"] ?? ""));
			if($pattern==""){continue;}
			$rows[]=hsnet_network_row($tpl,$page,$ligne,$pattern);
		}
	}
	$rows[]=PortsList();

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	// L'ancien en-tete etait un <th colspan='3'> suivi d'un <th> vide: le titre
	// {networks} chapeautait donc trois colonnes distinctes a la fois.
	$html[]="<th>{networks}</th>";
	$html[]="<th class='center'>{type}</th>";
	$html[]="<th class='center'>&nbsp;</th>";
	$html[]="</tr></thead><tbody>";
	$html[]=@implode("\n",$rows);
	$html[]="</tbody></table></div></div></div>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		."<script>NoSpinner();\n$tinyurl</script>";
	return true;
}

/**
 * Pastille de mode d'une entree.
 * L'ancienne version fermait ces pastilles par un "<span>" au lieu de
 * "</span>" -- QUATRE fois -- laissant a chaque ligne un element ouvert que le
 * navigateur refermait de force a la fin de la cellule.
 */
function hsnet_mode_badge($exclude):string{
	if(intval($exclude)==1){return "<span class='awc-badge' style='background:#865300'>{exclude}</span>";}
	return "<span class='awc-badge awc-badge-on'>{include}</span>";
}

/**
 * Une ligne du tableau, quel que soit son type (reseau, port, valeur par
 * defaut). $ico est une classe d'icone COMPLETE, prefixe de style compris:
 * ico_interface vaut "fa-solid fa-ethernet", pas seulement "fa-ethernet".
 * $name et $acts contiennent du HTML deja construit par l'appelant.
 */
function hsnet_row($id,$ico,$name,$badge,$acts):string{
	$h ="<tr id='".htmlspecialchars((string)$id)."'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='$ico'></i></span>";
	$h.="<span class='awc-name'>$name</span>";
	$h.="</div></td>";
	$h.="<td class='center awc-nowrap'>$badge</td>";
	$h.="<td><div class='awc-acts'>$acts</div></td>";
	$h.="</tr>";
	return $h;
}

/** Une ligne de reseau declare. */
function hsnet_network_row($tpl,$page,$ligne,$pattern):string{
	$num=intval($ligne["ID"] ?? 0);
	$id=md5($pattern);
	// $excludetxt etait declare a null puis concatene au nom: toujours vide.
	return hsnet_row($id,"fad fa-network-wired",
		$tpl->td_href(htmlspecialchars($pattern),"{click_to_edit}","Loadjs('$page?net-js=$num');"),
		hsnet_mode_badge($ligne["exclude"] ?? 0),
		$tpl->icon_delete("Loadjs('$page?netmask-unlink=$num&md=$id')","AsHotSpotManager"));
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
    $rows=array();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT port FROM proxy_ports WHERE enabled=1");
    if(!is_array($results)){$results=array();}
    foreach($results as $ligne){
        $port=intval($ligne["port"] ?? 0);
        if($port==0){continue;}
        $rows[]=hsnet_row("tt-$port",ico_interface,
            $tpl->td_href("{listen_port} $port ({APP_PROXY})","{click_to_edit}","Loadjs('$page?port-js=$port');"),
            hsnet_mode_badge(isset($excludePortsArray[$port])?1:0),
            "&nbsp;");
    }
    return @implode("\n",$rows);
}



