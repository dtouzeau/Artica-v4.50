<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
// Pentest 2026-11-04 : mac, remove, prependip, idFlat, id et CallBackFunction etaient recopies sans encodage dans la page (XSS).
foreach (array("mac","remove") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=preg_replace('#[^A-Za-z0-9:._-]#','',(string)$_GET[$xk]);}}
if(isset($_GET["prependip"])){$_GET["prependip"]=preg_replace('#[^A-Fa-f0-9:.]#','',(string)$_GET["prependip"]);}
foreach (array("idFlat","id") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=preg_replace('#[^A-Za-z0-9_-]#','',(string)$_GET[$xk]);}}
if(isset($_GET["CallBackFunction"])){$_GET["CallBackFunction"]=preg_replace('#[^A-Za-z0-9_+/=]#','',(string)$_GET["CallBackFunction"]);}
if(isset($_GET["computer-dhcp"])){computer_dhcp_js();exit;}
if(isset($_GET["computer-dhcp-popup"])){computer_dhcp_popup();exit;}
if(isset($_GET["computer-identity"])){computer_identity_js();exit;}
if(isset($_GET["computer-identity-popup"])){computer_identity_popup();exit;}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["gateway"])){host_edit();exit;}
if(isset($_POST["mac"])){save();exit;}
if(isset($_GET["settings"])){settings_start();exit;}
if(isset($_GET["idFlat"])){settings_flat();exit;}
if(isset($_GET["remove"])){remove_js();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["dhcp"])){computer_dhcp_popup();exit;}
if(isset($_GET["infos"])){settings_infos();exit;}
if(isset($_GET["infos2"])){settings_infos2();exit;}
if(isset($_GET["scanreport"])){scanreport();exit;}

js();


function remove_js():bool{
	$tpl=new template_admin();
	$CallBackFunction=CallBack();
    if(strlen($CallBackFunction)>3){
        $CallBackFunction="$CallBackFunction()";
    }

	return $tpl->js_confirm_delete($_GET["remove"],"remove",$_GET["remove"],"dialogInstance3.close();$CallBackFunction");
		
}
function CallBack():string{

    $tpl=new template_admin();
    $CallBackFunctionValue=$_GET["CallBackFunction"];
    $CallBackFunction=$CallBackFunctionValue;
    if($tpl->IsBase64($CallBackFunctionValue)){
        $CallBackFunction=trim(base64_decode($_GET["CallBackFunction"]));
    }



    $CallBackFunction_text="blur()";
    if($CallBackFunction<>null){

        if(preg_match("#=$#",$CallBackFunction)){
            $CallBackFunction=base64_decode($CallBackFunction);
        }

        if(strpos($CallBackFunction,")")==0){
            $CallBackFunction_text="$CallBackFunction()";
        }else{
            $CallBackFunction_text=$CallBackFunction;
        }
    }
    if($CallBackFunction_text=="()"){$CallBackFunction_text=null;}
    // Pentest 2026-11-04 : CallBackFunction decode (base64) etait recopie sans encodage dans la page (XSS).
    return preg_replace('#[^A-Za-z0-9_]#','',str_replace("()","",(string)$CallBackFunction_text));
}

function remove_perform():bool{
	// Pentest 2026-11-04 : remove (adresse MAC) etait concatene dans la requete.
	$mac=strval($_POST["remove"]);
	// Le bouton est cadenasse sans droit ; la suppression le verifie aussi cote serveur.
	$tpl=new template_admin();
	if(!$tpl->IsSecurity("computer")){echo $tpl->post_error("{ERROR_NO_PRIVS}");return false;}
	$q=new postgres_sql();
	$q->QUERY_SQL_PARAMS("DELETE FROM hostsnet WHERE mac=$1",array($mac));
	$q->QUERY_SQL_PARAMS("DELETE FROM dhcpd_hosts WHERE mac=$1",array($mac));
	$q->QUERY_SQL_PARAMS("DELETE FROM dhcpd_leases WHERE mac=$1",array($mac));
    return admin_tracks("Remove computer $mac from database");
}

function computer_identity_js():bool{
    $url=buildurl();
    $mac=$_GET["mac"];
    $tpl=new template_admin();
    return $tpl->js_dialog4("{computer} $mac","$url&computer-identity-popup=yes",550);
}
function computer_dhcp_js():bool{
    $url=buildurl();
    $mac=$_GET["mac"];
    $tpl=new template_admin();
    return $tpl->js_dialog4("{computer} >> DHCP >> $mac","$url&computer-dhcp-popup=yes",550);
}


function js():bool{
	
	$prepend_ip=null;$ByProxy=null;
	$mac2=urlencode($_GET["mac"]);
	$tpl=new template_admin();
	$host=new hosts($_GET["mac"]);
	$CallBackFunction=urlencode($_GET["CallBackFunction"]);
	if(isset($_GET["prependip"])){$prepend_ip="&prependip={$_GET["prependip"]}";}
	if(isset($_GET["ByProxy"])){$ByProxy="&ByProxy=yes";}
	
	
	$title=$tpl->javascript_parse_text("{computer} {$_GET["mac"]} $host->hostname");
	$page=CurrentPageName();
	$tpl->js_dialog3($title, "$page?tabs=yes&mac=$mac2$prepend_ip$ByProxy&CallBackFunction=$CallBackFunction");
	return true;
}

function tabs(){
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $EnableDHCPServer=0;
    $users=new usersMenus();
    $dhcp_installed=$users->dhcp_installed;
    if($users->ASDCHPAdmin) {
        $EnableDHCPServer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
        $EnableKEA = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
        if ($EnableKEA == 1) {
            $EnableDHCPServer = 1;
            $dhcp_installed = true;
        }
    }
	$page=CurrentPageName();

	$prepend_ip=null;$ByProxy=null;
	$host=new hosts($_GET["mac"]);
	$mac=urlencode($_GET["mac"]);
	$CallBackFunction=urlencode($_GET["CallBackFunction"]);
	if(isset($_GET["prependip"])){$prepend_ip="&prependip={$_GET["prependip"]}";}
	if(isset($_GET["ByProxy"])){$ByProxy="&ByProxy=yes";}

	
	$array[$host->hostname]="$page?settings=yes&mac=$mac$prepend_ip$ByProxy&CallBackFunction=$CallBackFunction";
	
    if($host->scanreport<>null){$array["{scan_report}"]="$page?scanreport=yes&mac=$mac&CallBackFunction=$CallBackFunction";}
    echo $tpl->tabs_default($array);
}

function buildurl(){
    $prepend_ip="";
    $ByProxy="";
    $macenc=urlencode($_GET["mac"]);
    $page=currentPageName();
    if(isset($_GET["prependip"])){$prepend_ip="{$_GET["prependip"]}";}
    $CallBackFunction=base64_encode("blur()");
    if(isset($_GET["CallBackFunction"])) {
        $CallBackFunction =trim($_GET["CallBackFunction"]);
        if(strlen($CallBackFunction)<2){
            $CallBackFunction=base64_encode("blur()");
        }

    }

    $idFlat="";

    if(isset($_GET["ByProxy"])){
        $ByProxy="&ByProxy=yes";

    }
    if(isset($_GET["idFlat"])){
        $idFlat="&idFlat=".$_GET["idFlat"];
    }
    return "$page?mac=$macenc&prependip=$prepend_ip$ByProxy&CallBackFunction=$CallBackFunction$idFlat";
}

function settings_start(){
    $macenc=urlencode($_GET["mac"]);
    $ByProxy="";
    $ByProxyToken="";

    if(isset($_GET["ByProxy"])){
        $ByProxy=true;
        $ByProxyToken="&ByProxy=yes";

    }
    $_GET["idFlat"]=md5($macenc.$ByProxy.$ByProxyToken);
    echo "<div id='{$_GET["idFlat"]}'></div>";
    $url=buildurl();
    echo "<script>LoadAjaxSilent('{$_GET["idFlat"]}','$url');</script>";

}

function computer_identity_popup():bool{
    $host=new hosts($_GET["mac"]);
    $tpl=new template_admin();
    $form[]=$tpl->field_hidden("mac",$_GET["mac"]);
    $form[]=$tpl->field_text("hostname", "{hostname}", $host->hostname,true);
    $form[]=$tpl->field_text("domainname","{domain}",$host->domainname);
    $form[]=$tpl->field_ipaddr("ipaddr", "{ipaddr}", $host->ipaddr,true);
    $form[]=$tpl->field_text("proxyalias", "{proxy_alias}", $host->proxyalias,false,"{my_proxy_aliases_text}");
    $form[]=$tpl->field_text("hostalias1", "{alias} 1", $host->hostalias1);
    $form[]=$tpl->field_text("hostalias2", "{alias} 2", $host->hostalias2);
    $form[]=$tpl->field_text("hostalias3", "{alias} 3", $host->hostalias3);
    $form[]=$tpl->field_text("hostalias4", "{alias} 4", $host->hostalias4);

    $url=buildurl();
    $js[]="dialogInstance4.close();";
    $js[]="LoadAjaxSilent('{$_GET["idFlat"]}','$url');";
    $js[]=CallBack()."()";


    $html[]=$tpl->form_outside("",$form,
        "","{apply}",@implode(";",$js),"computer");
    echo $tpl->_ENGINE_parse_body( $html);
return true;

}

// ---------------------------------------------------------------------------
// Fiche d'un poste (maquette stitch_network_device_details_dialog, 2026-11-11).
// Un modele lu une fois (edc_model), une fonction par widget. Chaque widget rend
// du HTML DEJA analyse : la sortie de table_form_compile() porte un <style> qui
// ne doit jamais retraverser _ENGINE_parse_body().
// ---------------------------------------------------------------------------
function settings_flat():bool{
    $tpl=new template_admin();
    $m=edc_model();
    $html=array();
    if(!$m["known"]){
        $html[]=edc_unknown($tpl,$m);
        echo edc_style()."<div class='edc'>".@implode("\n",$html)."</div>".edc_js();
        return true;
    }
    $html[]=edc_identity($tpl,$m);
    $html[]=edc_dates($tpl,$m);
    // Mode ByProxy (ouverture depuis le proxy) : ni DHCP ni services, comme avant.
    if(!$m["byproxy"]){
        $dhcp=edc_dhcp($tpl,$m);
        $services=edc_services($tpl,$m);
        if($services==""){
            $html[]=$dhcp;
        }else{
            $html[]="<div class='edc-grid'><div class='edc-cell'>$dhcp</div><div class='edc-cell'>$services</div></div>";
        }
    }
    $html[]=edc_aliases($tpl,$m);
    $html[]=edc_danger($tpl,$m);
    echo edc_style()."<div class='edc'>".@implode("\n",$html)."</div>".edc_js();
    return true;
}

function edc_model():array{
    $mac=(string)$_GET["mac"];
    $host=new hosts($mac);
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $m["mac"]=$mac;
    $m["macenc"]=urlencode($mac);
    $m["url"]=buildurl();
    $m["callback"]=isset($_GET["CallBackFunction"])?urlencode((string)$_GET["CallBackFunction"]):"";
    $m["prependip"]=isset($_GET["prependip"])?(string)$_GET["prependip"]:"";
    $m["byproxy"]=isset($_GET["ByProxy"]);
    $m["known"]=strlen(trim((string)$host->ipaddr))>0;

    $hostname=trim((string)$host->hostname);
    $domain=trim((string)$host->domainname);
    $m["fqdn"]=strlen($domain)>2?"$hostname.$domain":$hostname;
    $m["ipaddr"]=trim((string)$host->ipaddr);
    $m["vendor"]=trim((string)$host->vendor);
    $m["os"]=trim((string)$host->ComputerOS);
    $m["created"]=intval(strtotime((string)$host->createdate));
    $m["updated"]=intval(strtotime((string)$host->updated));

    // Serveur DHCP actif : ISC (installe + active) ou Kea, comme la modale DHCP.
    // L'ancienne fiche ignorait Kea et cachait le bloc DHCP sur ces appliances.
    $isc=intval($sock->GET_INFO("DHCPDInstalled"))==1 && intval($sock->GET_INFO("EnableDHCPServer"))==1;
    $kea=intval($sock->GET_INFO("EnableKEA"))==1;
    $m["dhcp_on"]=$isc || $kea;
    $m["isc"]=dbc_isc_dhcpd();
    $m["dnsdist"]=intval($sock->GET_INFO("EnableDNSDist"))==1;
    $m["hotspot"]=intval($sock->GET_INFO("EnableSquidMicroHotSpot"))==1;

    $m["dhcpfixed"]=intval($host->dhcpfixed);
    $m["dhcpiface"]=trim((string)$host->dhcpiface);
    $m["iface_net"]="";
    if($m["dhcp_on"] && $m["dhcpfixed"]==1 && $m["dhcpiface"]<>""){
        if(!class_exists("dhcpd")){include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");}
        $nets=KeaInterfaces();
        if(isset($nets[$m["dhcpiface"]])){$m["iface_net"]=(string)$nets[$m["dhcpiface"]];}
    }
    $m["gateway"]=(string)$host->gateway;
    $m["gateway2"]=(string)$host->gateway2;
    $m["dns1"]=(string)$host->dns1;
    $m["dns2"]=(string)$host->dns2;
    $m["pxe_enabled"]=intval($host->pxe_enabled);
    $m["pxe_server"]=(string)$host->pxe_server;
    $m["pxe_file"]=(string)$host->pxe_file;
    $m["dhcpbroadcast"]=intval($host->dhcpbroadcast);
    $m["dnsfixed"]=intval($host->dnsfixed);
    $m["hotspotwhite"]=intval($host->hotspotwhite);
    $m["proxyalias"]=trim((string)$host->proxyalias);
    // Les quatre alias, sans doublon (l'ancienne fiche listait alias1 deux fois).
    $aliases=array();
    foreach(array($host->hostalias1,$host->hostalias2,$host->hostalias3,$host->hostalias4) as $a){
        $a=trim((string)$a);
        if($a==""){continue;}
        $aliases[$a]=true;
    }
    $m["aliases"]=array_keys($aliases);
    return $m;
}

// Adresse IP : monospace, ou un tiret quand elle est absente (0.0.0.0 = non definie).
function edc_ip($v):string{
    $v=trim((string)$v);
    if($v=="" || $v=="0.0.0.0"){return "<span class='edc-none'>&mdash;</span>";}
    return "<span class='ak-mono'>".htmlspecialchars($v,ENT_QUOTES)."</span>";
}

function edc_copy_btn($value,$label):string{
    $v=htmlspecialchars((string)$value,ENT_QUOTES);
    return "<button type='button' class='edc-copy' data-copy='$v' aria-label=\"$label\" title=\"$label\" OnClick=\"EdcCopy(this)\"><i class='fa-regular fa-copy' aria-hidden='true'></i></button>";
}

function edc_pill($label,$value,$copyLabel=null,$mono=true):string{
    $v=htmlspecialchars((string)$value,ENT_QUOTES);
    $cls=$mono?"edc-pv ak-mono":"edc-pv";
    $copy=$copyLabel===null?"":edc_copy_btn($value,$copyLabel);
    return "<span class='edc-pill'><span class='edc-pk'>$label</span><span class='$cls'>$v</span>$copy</span>";
}

function edc_identity($tpl,$m):string{
    $h=array();
    $h[]="<div class='ak ak-strip edc-id'>";
    $h[]="<span class='ak-ico ak-ico-sq edc-id-ico'><i class='fa-solid fa-computer' aria-hidden='true'></i></span>";
    $h[]="<div class='ak-strip-main'>";
    $h[]="<div class='edc-id-t'>".htmlspecialchars($m["fqdn"],ENT_QUOTES)."</div>";
    $h[]="<div class='edc-pills'>";
    $h[]=edc_pill("MAC",$m["mac"],"{edc_copy_mac}");
    $h[]=edc_pill("{ipaddr}",$m["ipaddr"],"{edc_copy_ip}");
    if($m["vendor"]<>""){$h[]=edc_pill("{edc_vendor}",$m["vendor"],null,false);}
    $h[]="</div>";
    if($m["os"]<>""){
        $h[]="<div class='edc-os'>{edc_os}: <strong>".htmlspecialchars($m["os"],ENT_QUOTES)."</strong></div>";
    }
    $h[]="</div>";
    $h[]="<div class='ak-strip-side'>";
    $h[]=$tpl->button_inline("{edc_edit_identity}","Loadjs('{$m["url"]}&computer-identity=yes')","fa-solid fa-pen","computer",0,"abt-p");
    $h[]="</div>";
    $h[]="</div>";
    return $tpl->_ENGINE_parse_body(@implode("",$h));
}

function edc_date($tpl,$epoch):string{
    if($epoch<=0){return "<span class='edc-none'>&mdash;</span>";}
    return "<span class='ak-mono edc-dv'>".$tpl->time_to_date($epoch,true)."</span>";
}

// "Mise a jour" = derniere ecriture de la fiche, pas une presence sur le reseau :
// l'infobulle le dit, la page n'a aucune date de derniere activite.
function edc_dates($tpl,$m):string{
    $help=str_replace(array("[br]","'"),array(" ","&#39;"),$tpl->_ENGINE_parse_body("{edc_record_updated_help}"));
    $help=trim(strip_tags($help));
    $h=array();
    $h[]="<div class='edc-dates'>";
    $h[]="<i class='fa-regular fa-clock edc-dates-i' aria-hidden='true'></i>";
    $h[]="<span>{edc_created}: ".edc_date($tpl,$m["created"])."</span>";
    $h[]="<span class='edc-sep' aria-hidden='true'>&bull;</span>";
    $h[]="<span>{edc_record_updated}: ".edc_date($tpl,$m["updated"])."</span>";
    $h[]="<span class='edc-help' tabindex='0' role='img' aria-label='$help' title='$help'><i class='fa-solid fa-circle-info' aria-hidden='true'></i></span>";
    $h[]="</div>";
    return $tpl->_ENGINE_parse_body(@implode("",$h));
}

function edc_dhcp($tpl,$m):string{
    if(!$m["dhcp_on"]){
        $h="<div class='ak ak-banner ak-off edc-dhcp-off'><i class='fa-solid fa-circle-info' aria-hidden='true'></i><span class='ak-banner-m'>{edc_dhcp_disabled}</span></div>";
        return $tpl->_ENGINE_parse_body($h);
    }
    $button=array("label"=>"{edc_edit_dhcp}","js"=>"Loadjs('{$m["url"]}&computer-dhcp=yes')","ico"=>"fa-solid fa-pen","priv"=>"ASDCHPAdmin");
    // Edition par le bouton d'en-tete : aucune ligne ne porte de crayon.
    $tpl->table_form_field_js("");
    $tpl->table_form_section("{edc_dhcp_reservation}",null,"fa-solid fa-network-wired",$button);
    $tpl->table_form_field_bool("{dhcpfixed}",$m["dhcpfixed"],"fa-solid fa-lock");
    if($m["dhcpfixed"]==1){
        $iface="<span class='edc-none'>&mdash;</span>";
        if($m["dhcpiface"]<>""){
            $iface=htmlspecialchars($m["dhcpiface"],ENT_QUOTES);
            if($m["iface_net"]<>""){$iface.=" &middot; ".htmlspecialchars($m["iface_net"],ENT_QUOTES);}
            $iface="<span class='ak-mono'>$iface</span>";
        }
        $tpl->table_form_field_text("{interface}",$iface,"fa-solid fa-ethernet");
        $tpl->table_form_field_text("{gateway}",edc_ip($m["gateway"]),"fa-solid fa-route");
        $g2=trim($m["gateway2"]);
        if($g2<>"" && $g2<>"0.0.0.0"){
            $tpl->table_form_field_text("{gateway} 2",edc_ip($g2),"fa-solid fa-route");
        }
        $tpl->table_form_field_text("{DNSServer} 1",edc_ip($m["dns1"]),"fa-solid fa-globe");
        $tpl->table_form_field_text("{DNSServer} 2",edc_ip($m["dns2"]),"fa-solid fa-globe");
        $pxe="<span class='edc-none'>{edc_pxe_off}</span>";
        if($m["pxe_enabled"]==1){
            $pxe="<span class='ak-mono'>".htmlspecialchars($m["pxe_server"],ENT_QUOTES)
                ." &middot; ".htmlspecialchars($m["pxe_file"],ENT_QUOTES)."</span>";
        }
        $tpl->table_form_field_text("PXE",$pxe,"fa-solid fa-server");
    }
    // ISC dhcpd seulement : Kea n'a pas de diffusion forcee par poste.
    if($m["isc"]){
        $tpl->table_form_field_bool("{dbc_always_broadcast}",$m["dhcpbroadcast"],"fa-solid fa-tower-broadcast");
    }
    return $tpl->table_form_compile();
}

// Etats en lecture seule : aucune modale de cette page ne les modifie.
function edc_services($tpl,$m):string{
    if(!$m["dnsdist"] && !$m["hotspot"]){return "";}
    $tpl->table_form_field_js("");
    $tpl->table_form_section("{services}",null,"fa-solid fa-shield-halved");
    if($m["dnsdist"]){$tpl->table_form_field_bool("{dns_entry}",$m["dnsfixed"],"fa-solid fa-globe");}
    if($m["hotspot"]){$tpl->table_form_field_bool("{hotspotwhite}",$m["hotspotwhite"],"fa-solid fa-wifi");}
    return $tpl->table_form_compile();
}

function edc_aliases($tpl,$m):string{
    $button=array("label"=>"{edc_edit_identity}","js"=>"Loadjs('{$m["url"]}&computer-identity=yes')","ico"=>"fa-solid fa-pen","priv"=>"computer");
    $explain=$m["byproxy"]?"{byproxy_explain_alias}":null;
    $tpl->table_form_field_js("");
    $tpl->table_form_section("{edc_aliases_title}",$explain,"fa-solid fa-tags",$button);
    $alias="<span class='edc-none'>&mdash;</span>";
    if($m["proxyalias"]<>""){$alias="<span class='ak-mono'>".htmlspecialchars($m["proxyalias"],ENT_QUOTES)."</span>";}
    $tpl->table_form_field_text("{proxy_alias}||{my_proxy_aliases_text}",$alias,"fa-solid fa-user-tag");
    $chips="<span class='edc-none'>{edc_aliases_none}</span>";
    if(count($m["aliases"])>0){
        $t=array();
        foreach($m["aliases"] as $a){$t[]="<span class='ak-tag ak-mono'>".htmlspecialchars($a,ENT_QUOTES)."</span>";}
        $chips="<span class='edc-tags'>".@implode("",$t)."</span>";
    }
    $tpl->table_form_field_text("{aliases}",$chips,"fa-solid fa-tag");
    return $tpl->table_form_compile();
}

function edc_danger($tpl,$m):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?remove={$m["macenc"]}&CallBackFunction={$m["callback"]}')";
    $h=array();
    $h[]="<div class='ak edc-danger'>";
    $h[]="<div class='edc-danger-txt'>";
    $h[]="<div class='edc-danger-t'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>{edc_delete_title}</div>";
    $h[]="<div class='edc-danger-s'>{edc_delete_explain}</div>";
    $h[]="</div>";
    $h[]=$tpl->button_inline("{edc_delete}",$js,"fa-solid fa-trash-can","computer",0,"abt-x");
    $h[]="</div>";
    return $tpl->_ENGINE_parse_body(@implode("",$h));
}

// MAC sans fiche : un etat vide, et la creation de la fiche pre-remplie.
function edc_unknown($tpl,$m):string{
    $add="fw.add.computer.php?mac={$m["macenc"]}";
    if($m["prependip"]<>""){$add.="&ipaddr=".urlencode($m["prependip"]);}
    if($m["callback"]<>""){$add.="&CallBackFunction={$m["callback"]}";}
    $h=array();
    $h[]="<div class='ak ak-card edc-unknown'><div class='ak-empty-c'>";
    $h[]="<i class='fa-solid fa-circle-question' aria-hidden='true'></i>";
    $h[]="<div class='edc-unknown-t'>{edc_unknown_device}</div>";
    $h[]="<div class='edc-unknown-l'><span class='ak-mono'>".htmlspecialchars($m["mac"],ENT_QUOTES)."</span>".edc_copy_btn($m["mac"],"{edc_copy_mac}");
    if($m["prependip"]<>""){
        $h[]="<span class='edc-sep' aria-hidden='true'>&bull;</span>{ipaddr} <span class='ak-mono'>".htmlspecialchars($m["prependip"],ENT_QUOTES)."</span>";
    }
    $h[]="</div>";
    $h[]=$tpl->button_inline("{new_computer}","Loadjs('$add')","fa-solid fa-plus","computer",0,"abt-p");
    $h[]="</div></div>";
    return $tpl->_ENGINE_parse_body(@implode("",$h));
}

function edc_style():string{
    if(isset($GLOBALS["EDC_STYLE"])){return "";}
    $GLOBALS["EDC_STYLE"]=true;
    $c=array();
    $c[]="<style>";
    $c[]=".edc{padding:4px 0 8px 0}";
    $c[]=".edc .ak-mono{font-size:13px}";
    $c[]=".edc-none{color:#94a3b8;font-weight:400}";
    // En-tete d'identite
    $c[]=".edc-id{background:#f8fafc;border-color:#e2e8f0;padding:16px;min-height:112px;margin-bottom:12px;align-items:flex-start}";
    $c[]=".edc-id-ico{flex-basis:48px;width:48px;height:48px;font-size:20px;background:#e0f2fe;color:#0369a1}";
    $c[]=".edc-id-t{font-size:20px;font-weight:600;color:#0f172a;line-height:1.35;overflow-wrap:anywhere}";
    $c[]=".edc-pills{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px}";
    $c[]=".edc-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 6px 3px 10px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;color:#475569}";
    $c[]=".edc-pk{color:#64748b}";
    $c[]=".edc-pv{color:#0f172a;font-weight:500}";
    $c[]=".edc-copy{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border:0;border-radius:6px;background:transparent;color:#64748b;font-size:12px;cursor:pointer;padding:0}";
    $c[]=".edc-copy:hover{background:#f1f5f9;color:#0f766e}";
    $c[]=".edc-copy:focus-visible,.edc-help:focus-visible{outline:2px solid #0f766e;outline-offset:2px}";
    $c[]=".edc-copy.edc-copied{color:#166534;background:#dcfce7}";
    $c[]=".edc-os{font-size:13px;color:#475569;margin-top:6px}";
    $c[]=".edc-os strong{font-weight:500;color:#334155}";
    // Bandeau des dates
    $c[]=".edc-dates{display:flex;align-items:center;flex-wrap:wrap;gap:8px;min-height:44px;padding:8px 12px;margin-bottom:16px;border:1px dashed #e2e8f0;border-radius:8px;background:#fbfdfe;font-size:13px;color:#475569}";
    $c[]=".edc-dates-i{color:#94a3b8}";
    $c[]=".edc-dv{color:#334155;font-weight:500}";
    $c[]=".edc-sep{color:#cbd5e1;margin:0 4px}";
    $c[]=".edc-help{display:inline-flex;color:#94a3b8;cursor:help;font-size:13px;border-radius:4px}";
    // Grille DHCP / services : les deux cartes a la meme hauteur
    $c[]=".edc-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;align-items:stretch}";
    $c[]=".edc-cell{display:flex;flex-direction:column;min-width:0}";
    $c[]=".edc-cell>.tfrm{flex:1 1 auto}";
    $c[]="@media (max-width:760px){.edc-grid{grid-template-columns:minmax(0,1fr)}}";
    $c[]=".edc-dhcp-off{margin-bottom:16px}";
    // Alias : les etiquettes passent a la ligne au lieu d'etre coupees
    $c[]=".edc .tfrm-val .edc-tags{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;white-space:normal}";
    $c[]=".edc .tfrm-val{white-space:normal}";
    // Zone de danger
    $c[]=".edc-danger{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:24px;padding-top:20px;border-top:1px solid #fecaca}";
    $c[]=".edc-danger-t{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:#991b1b}";
    $c[]=".edc-danger-s{font-size:13px;color:#7f1d1d;margin-top:2px}";
    // MAC inconnue
    $c[]=".edc-unknown .ak-empty-c{min-height:240px;justify-content:center}";
    $c[]=".edc-unknown .ak-empty-c>svg,.edc-unknown .ak-empty-c>i{font-size:40px}";
    $c[]=".edc-unknown-t{font-size:16px;font-weight:600;color:#0f172a}";
    $c[]=".edc-unknown-l{display:flex;align-items:center;gap:6px;font-size:13px;color:#475569;margin-bottom:6px}";
    $c[]="@media (prefers-reduced-motion:reduce){.edc *{transition:none!important}}";
    $c[]="</style>";
    return @implode("",$c);
}

// Copie dans le presse-papiers, avec repli execCommand pour les navigateurs
// sans Clipboard API (ou hors contexte securise, cas d'un certificat refuse).
function edc_js():string{
    $copied=str_replace(array("[br]","'"),array("","\\'"),(new template_admin())->javascript_parse_text("{edc_copied}"));
    return "<script>
window.EdcCopy=function(btn){
  var v=btn.getAttribute('data-copy')||'';
  var done=function(){ var t=btn.getAttribute('title'); btn.className+=' edc-copied'; btn.setAttribute('title','$copied');
    setTimeout(function(){ btn.className=btn.className.replace(' edc-copied',''); btn.setAttribute('title',t); },1200); };
  var fallback=function(){ var ta=document.createElement('textarea'); ta.value=v; ta.style.position='fixed'; ta.style.opacity='0';
    document.body.appendChild(ta); ta.select(); try{document.execCommand('copy');}catch(e){} document.body.removeChild(ta); done(); };
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(v).then(done,fallback); }else{ fallback(); }
};
NoSpinner();
</script>";
}

function scanreport(){
    $macenc=urlencode($_GET["mac"]);
    $host=new hosts($_GET["mac"]);

    echo "<textarea style='margin-top:10px;width:99%;height:450px'>$host->scanreport</textarea>";

}

function computer_dhcp_popup(){

    $EnableDHCPServer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    $EnableKEA = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    if($EnableKEA==1){
        $EnableDHCPServer=1;
    }
	$host=new hosts($_GET["mac"]);
	$tpl=new template_admin();

    $dhcp=new dhcpd();
    if($host->gateway=="0.0.0.0"){
        $host->gateway=$dhcp->gateway;
	}
	if($host->dns1=="0.0.0.0"){$host->dns1=$dhcp->DNS_1;}
	if($host->dns2=="0.0.0.0"){$host->dns2=$dhcp->DNS_2;}


	$form[]=$tpl->field_hidden("mac", $_GET["mac"]);
    if($EnableDHCPServer==1){
        $Interfaces=KeaInterfaces();
        $form[]=$tpl->field_checkbox("dhcpfixed","{dhcpfixed}",$host->dhcpfixed);
        $form[]=$tpl->field_array_hash($Interfaces,"dhcpiface","DHCP",$host->dhcpiface);
    }else{
        $form[]=$tpl->field_hidden("dhcpfixed", $host->dhcpfixed);
    }
    // ISC dhcpd only: Kea has no per-host always-broadcast.
    if(dbc_isc_dhcpd()){
        $form[]=$tpl->field_checkbox("dhcpbroadcast","{dbc_always_broadcast}",$host->dhcpbroadcast,false,"{dbc_always_broadcast_explain}");
    }else{
        $form[]=$tpl->field_hidden("dhcpbroadcast", $host->dhcpbroadcast);
    }
    $form[]=$tpl->field_text("domainname","{domain}",$host->domainname);
    $form[]=$tpl->field_ipaddr("gateway","{gateway}",$host->gateway);
	$form[]=$tpl->field_ipaddr("gateway2","{gateway} 2",$host->gateway2);
	$form[]=$tpl->field_ipaddr("dns1","{DNSServer} 1",$host->dns1);
	$form[]=$tpl->field_ipaddr("dns2","{DNSServer} 2",$host->dns2);
	$form[]=$tpl->field_checkbox("pxe_enabled","{enable_feature} PXE",$host->pxe_enabled,"pxe_server,pxe_file",null);
	$form[]=$tpl->field_ipaddr("pxe_server","{pxe_server}",$host->pxe_server);
	$form[]=$tpl->field_text("pxe_file","{pxe_file}",$host->pxe_file);

    $url=buildurl();
    $js[]="dialogInstance4.close();";
    $js[]="LoadAjaxSilent('{$_GET["idFlat"]}','$url');";
    $js[]=CallBack()."()";

    $html[]=$tpl->form_outside("" ,$form,null,"{apply}",@implode(";",$js),"ASDCHPAdmin");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	

}

function settings_infos2(){
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
	$id=$_GET["id"];
	$macenc=urlencode($_GET["mac"]);
	$host=new hosts($_GET["mac"]);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$data=unserialize(base64_decode($host->scanreport));
    if(!$data){
        $data=array();
        $data["OS"]=null;
        $data["UPTIME"]=null;
        $data["PORTS"]=array();
    }
	
	if($data["OS"]==null){$data["OS"]="{unknown}";}
	$html[]="<table style='width:100%' class='table table-striped'>
	<tr>
	<td><label>{OS}:</label></td>
	<td>{$data["OS"]}</td>
	</tr>
	<tr>
	<td><label>{uptime}:</label></td>
	<td>{$data["UPTIME"]}</td>
	</tr>
	
	";
    if(!is_array($data["PORTS"])){$data["PORTS"]=array();}
    foreach ($data["PORTS"] as $port=>$explain){
    	$html[]="	<tr>
	    <td><label>$port:</label></td>
	    <td>{$explain}</td>
	    </tR>";
	}
	$html[]="</table>";
	
	if(isset($_GET["with-results"])){
		
		if(is_file(PROGRESS_DIR."/nmap_single_progress.results")){
			$value=@file_get_contents(PROGRESS_DIR."/nmap_single_progress.results");
			$html[]="<textarea id='NONE' name='NONE' style='width:100%;height:350px;margin-bottom:20px'>$value</textarea>";
		}
		
	}

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/nmap.single.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/nmap_single_progress.txt";
	$ARRAY["CMD"]="system.php?nmap-scan-single=yes&MAC=$macenc&ipaddr=".urlencode($host->ipaddr);
	$ARRAY["TITLE"]="{scan_this_computer}: {$_GET["mac"]} - {$host->ipaddr}";
	$ARRAY["AFTER"]="LoadAjaxSilent('$id','$page?infos2=yes&mac=$macenc&id=$id&with-results=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-$id')";

	
	$html[]="<div style='text-align:right'>".$tpl->button_autnonome("{scan_this_computer}",$jsrestart,"fa-bolt")."</div>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function settings_infos(){
	$macenc=urlencode($_GET["mac"]);
	$id=md5($macenc);
	$page=CurrentPageName();
	$html="
	<div id='progress-$id'></div>
	<div id='$id'></div>
	<script>LoadAjaxSilent('$id','$page?infos2=yes&mac=$macenc&id=$id');</script>";
	echo $html;
}

function host_edit(){
	$tpl=new template_admin();
	if(!$tpl->IsSecurity("computer")){echo $tpl->post_error("{ERROR_NO_PRIVS}");return false;}
	$tpl->CLEAN_POST();

	$host=new hosts($_POST["mac"]);
	
	foreach ($_POST as $key=>$val){
		$val=strtolower(trim($val));
		$host->$key=$val;

	}
	$host->Save();
	if(!$host->ok){echo $tpl->post_error($host->mysql_error);return;}
	
	
	if(isset($_POST["proxyalias"])<>null){
		$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
		if($SQUIDEnable==1){
			$memcached=new lib_memcached();
			$memcached->saveKey($_POST["mac"].":alias", $_POST["proxyalias"]);
			$GLOBALS["CLASS_SOCKETS"]->REST_API("/hostnets/aliases/rebuild");
		}
	}

}


function save():bool{
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $tpl=new template_admin();
    // Droit verifie cote serveur : masquer le bouton ne protege pas la route.
    if(!$tpl->IsSecurity("computer")){echo $tpl->post_error("{ERROR_NO_PRIVS}");return false;}
    $tpl->CLEAN_POST();

    foreach ($_POST as $key=>$val){
        writelogs("$key=>$val",__FUNCTION__,__FILE__,__LINE__);
    }

    if(!isset($_POST["mac"])){
        echo $tpl->post_error("No Mac!");
        return false;
    }

	$q=new postgres_sql();
	$_POST["mac"]=str_replace("-", ":", $_POST["mac"]);
	$_POST["mac"]=strtolower($_POST["mac"]);

	$ipClass=new IP();
	if(!$ipClass->IsvalidMAC($_POST["mac"])){
        echo $tpl->post_error("MAC: {$_POST["mac"]} Invalid!");
		return false;
	}
    if(isset($_POST["ipaddr"])) {
        if (!$ipClass->isValid($_POST["ipaddr"])) {
            echo $tpl->post_error("IP: {$_POST["ipaddr"]} Invalid!");
            return false;
        }
    }

    if(isset($_POST["dhcpfixed"])) {
        if ($_POST["dhcpfixed"] == 1) {
            if ($EnableKEA == 0) {
                include_once('ressources/class.dhcpd.inc');
                $dhcp = new dhcpd();
                if($dhcp->gateway<>"0.0.0.0") {
                    $_POST["gateway"] = $dhcp->gateway;
                }
                if($dhcp->DNS_1<>"0.0.0.0") {
                    $_POST["dns1"] = $dhcp->DNS_1;
                }
                if($dhcp->DNS_2<>"0.0.0.0") {
                    $_POST["dns2"] = $dhcp->DNS_2;
                }
            }
        }
    }

    writelogs("\$hostClass=new hosts({$_POST["mac"]});",__FUNCTION__,__FILE__,__LINE__);
	$hostClass=new hosts($_POST["mac"]);
	reset($_POST);
	foreach ($_POST as $key=>$val){
		$val=strtolower(trim($val));
        $hostClass->$key=$val;

	}

    if (isset($_POST["hostname"]) && !empty(trim($_POST["hostname"]))) {
        $hostname = trim($_POST["hostname"]);
        // Remove domain part if present
        if (strpos($hostname, ".") !== false) {
            $hostname = explode(".", $hostname)[0];
        }
        $hostClass->hostname = $hostname;

        // Set fullhostname with domainname if provided
        if (isset($_POST["domainname"]) && !empty(trim($_POST["domainname"]))) {
            $hostClass->fullhostname = "$hostname." . trim($_POST["domainname"]);
        } else {
            $hostClass->fullhostname = $hostname;
        }
    }
    $hostClass->Save();
	if(!$hostClass->ok){echo $tpl->post_error($hostClass->mysql_error);return false;}



    if (isset($_POST["proxyalias"]) && !empty(trim($_POST["proxyalias"]))) {
        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
		if($SQUIDEnable==1){
		    $memcached=new lib_memcached();
		    $memcached->saveKey($_POST["mac"].":alias", $_POST["proxyalias"]);
		    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hostnets/aliases/rebuild");
		}
	}
    return admin_tracks_post("Add/Edit a new computer");
}
// The ISC DHCP server (not Kea) is installed and enabled: the only one that
// honours the per-device always-broadcast flag.
function dbc_isc_dhcpd():bool{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"))==1){return false;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInstalled"))==0){return false;}
    return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"))==1;
}
function KeaInterfaces():array{
    $net=new networking();
    $interfaces=$net->Local_interfaces();
    $MAIN=array();
    foreach ($interfaces as $interface=>$Ipaddress){
        $dhcpd=new dhcpd(0,1,$interface);
        if($dhcpd->service_enabled==0){
            continue;
        }
        $MAIN[$interface]="$dhcpd->subnet/$dhcpd->netmask";
    }
    return $MAIN;
}