<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.icon.top.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["ecap-clamav-uninstall-js"])){ecap_clamav_uninstall_ask();exit;}
if(isset($_POST["ecap-clamav-uninstall"])){ecap_clamav_install_confirm();exit;}
if(isset($_GET["section-main-js"])){section_main_js();exit;}
if(isset($_GET["section-template-js"])){section_template_js();exit;}
if(isset($_GET["section-template-popup"])){section_template_popup();exit;}
if(isset($_GET["section-main-popup"])){section_main_popup();exit;}
if(isset($_POST["eCAPClamavMaxSize"])){save();exit;}
if(isset($_POST["template"])){section_template_save();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){events_search();exit;}



page();

function tabs():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array["{parameters}"]="$page?table=yes";
    $array["{av_exclusions}"]='fw.proxy.ecap.whitelist.php';
    $array["{events}"]="$page?events=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{integrated_antivirus}",
        ico_antivirus,"{integrated_antivirus_explain}","$page?tabs=yes","ecapav","progress-squid-ecap-restart",
        false,"table-loader-proxy-ecapav");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{file_descriptors}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function events():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   echo "<div style='margin-top:10px'>".$tpl->search_block($page)."</div>";
    return true;
}
function events_search():bool{
    $tpl=new template_admin();

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    $data=$sock->REST_API("/proxy/ecap/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }

    $html[]="
<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>
	<thead>
    	<tr>
    	    <th style='width:1%' nowrap>{virus}</th>
        	<th style='width:1%' nowrap>{date}</th>
        	<th style='width:1%' nowrap>PID</th>
        	<th style='width:99%' nowrap>URL</th>
        	<th style='width:1%' nowrap>{member}</th>
        </tr>
  	</thead>
	<tbody id='ecapav-rows'>
";



    foreach ($json->Logs as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:(.+?)\s+\[(.+?)\]\s+(.+)#", $line,$re)){continue;}
        $FTime=$re[1]." $re[2] $re[3]";
        $PID=$re[4];
        $URL=$re[5];
        $VIRUS=$re[6];
        $IP=$re[7];
        $html[]="<tr>
                <td style='width:1%' nowrap><span class='label label-danger'>$VIRUS</span></td>
				<td style='width:1%' nowrap>$FTime</td>
				<td style='width:1%' nowrap>$PID</td>
				<td style='width:99%'>$URL</td>
				<td style='width:1%' nowrap>$IP</td>
				</tr>";

    }

    $html[]="</tbody></table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='ecapav-pager'></span></div></div></div>";

    $TINY_ARRAY["TITLE"]="{integrated_antivirus}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{ecapav_log_explain}";
    $TINY_ARRAY["URL"]="ecapav";

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ecapav-rows","ecapav-pager");
return true;

}

function table(){
    $page=CurrentPageName();
    echo "<div id='ecap-clamav-table'></div>
        <script>LoadAjaxSilent('ecap-clamav-table','$page?table1=yes');</script>";
}
function section_main_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{parameters}","$page?section-main-popup=yes");
}
function section_template_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{template}","$page?section-template-popup=yes");
}
function section_template_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $tpl->CLUSTER_CLI=True;

    $template=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavTemplate"));
    if(strlen($template)<50){
        $template=@file_get_contents("/usr/share/artica-postfix/ressources/databases/ecap.template");
    }
    $form[]=$tpl->field_textareacode("template",null,$template);

    $jsafter=section_js_form();
    $html[]=$tpl->form_outside("", $form,null,"{apply}",$jsafter,$security,true)."</td>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_template_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("eCAPClamavTemplate",base64_encode(trim($_POST["template"])));
    admin_tracks("Saving template for eCAP Clamav antivirus");
    return true;
}

function section_js_form():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsinstall=$tpl->framework_buildjs(
        "/proxy/ecap/install",
        "squid.ecap.progress",
        "squid.ecap.progress.log","progress-squid-ecap-restart");



    return "BootstrapDialog1.close();LoadAjaxSilent('ecap-clamav-table','$page?table1=yes');$jsinstall";
}

// Feuille de style de l'onglet Parametres (maquette stitch_proxy_antivirus_status).
// Concatenee hors de _ENGINE_parse_body: chaque bloc {...} y serait lu comme un jeton.
// Palette identique a l'onglet Exclusions (ecapwl-*), pour que les deux onglets se ressemblent.
function ecapav_style():string{
    if(isset($GLOBALS["ECAPAV_STYLE"])){return "";}
    $GLOBALS["ECAPAV_STYLE"]=true;
    return "<style>
.ecapav{color:#111d23;margin-top:10px;display:flex;flex-direction:column;gap:20px}
.ecapav *{box-sizing:border-box}
.ecapav-emg{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:#ffddb9;color:#2b1700;border:1px solid #feb965;border-radius:12px;padding:16px 20px}
.ecapav-emg-l{display:flex;align-items:flex-start;gap:14px}
.ecapav-emg-ico{width:40px;height:40px;border-radius:10px;background:#865300;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:18px;flex:0 0 auto}
.ecapav-emg-t{font-size:15px;font-weight:700;line-height:22px}
.ecapav-emg-b{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;background:#663e00;color:#ffddb9;margin-left:8px;white-space:nowrap}
.ecapav-emg-p{font-size:13px;line-height:19px;margin:4px 0 0;color:#663e00}
.ecapav-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.ecapav-kpi{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:18px 20px;display:flex;flex-direction:column;gap:10px;min-height:150px}
.ecapav-kpi-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.ecapav-kpi-lbl{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#3d4947;line-height:16px}
.ecapav-kpi-ico{width:36px;height:36px;border-radius:10px;background:#e3f0f8;color:#3d4947;display:inline-flex;align-items:center;justify-content:center;font-size:16px;flex:0 0 auto}
.ecapav-kpi-ico.ecapav-red{background:#ffdad6;color:#ba1a1a}
.ecapav-kpi-ico.ecapav-teal{background:#a0f1e7;color:#00504a}
.ecapav-kpi-val{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
.ecapav-kpi-num{font-size:36px;font-weight:700;line-height:40px;color:#111d23}
.ecapav-kpi-num.ecapav-red{color:#ba1a1a}
.ecapav-kpi-num.ecapav-teal{color:#00504a}
.ecapav-kpi-unit{font-size:14px;color:#3d4947}
.ecapav-delta{font-size:12px;font-weight:700;padding:2px 8px;border-radius:999px;background:#ffdad6;color:#93000a}
.ecapav-kpi-sub{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:13px;color:#6d7a77;margin-top:auto}
.ecapav-link{font-weight:700;color:#006a62!important;text-decoration:none!important;cursor:pointer;white-space:nowrap}
.ecapav-link:hover{color:#00504a!important;text-decoration:underline!important}
.ecapav-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.ecapav-card{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:12px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.ecapav-card:hover{border-color:#006a62;box-shadow:0 4px 14px rgba(0,80,74,.08)}
.ecapav-card-top{display:flex;justify-content:space-between;align-items:flex-start}
.ecapav-card-ico{width:44px;height:44px;border-radius:12px;background:#e3f0f8;color:#00504a;display:inline-flex;align-items:center;justify-content:center;font-size:19px}
.ecapav-pen{width:32px;height:32px;border-radius:8px;color:#3d4947;display:inline-flex;align-items:center;justify-content:center;font-size:14px}
.ecapav-card:hover .ecapav-pen{background:#e3f0f8;color:#006a62}
.ecapav-card-t{font-size:17px;font-weight:600;line-height:24px;color:#111d23}
.ecapav-card-p{font-size:13px;line-height:19px;color:#3d4947;margin:0}
.ecapav-well{margin-top:auto;background:#e9f6fd;border-radius:10px;padding:14px 16px}
.ecapav-well-lbl{font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#3d4947;margin-bottom:8px}
.ecapav-size{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.ecapav-size-n{font-size:34px;font-weight:700;line-height:38px;color:#00504a}
.ecapav-size-u{font-size:20px;font-weight:600;color:#3d4947;margin-left:6px}
.ecapav-bytes{font-size:12px;font-weight:600;padding:3px 8px;border-radius:6px;background:#84f5e8;color:#005049;white-space:nowrap}
.ecapav-mimes{display:flex;flex-wrap:wrap;gap:8px}
.ecapav-mime{font-size:13px;padding:4px 10px;border-radius:6px;background:#fff;border:1px solid #d7e4ec;color:#111d23}
.ecapav-none{font-size:13px;color:#6d7a77;font-style:italic}
.ecapav-file{display:flex;align-items:center;gap:12px}
.ecapav-file-b{font-size:12px;font-weight:700;padding:10px 8px;border-radius:8px;background:#ffdad6;color:#ba1a1a;flex:0 0 auto}
.ecapav-file-t{font-size:14px;font-weight:600;color:#111d23}
.ecapav-file-s{font-size:12px;color:#3d4947}
.ecapav-file-e{margin-left:auto;font-size:13px;font-weight:600;padding:6px 12px;border-radius:8px;background:#fff;color:#006a62;white-space:nowrap}
.ecapav-arch{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:24px}
.ecapav-arch-h{display:flex;align-items:center;gap:14px;margin-bottom:18px}
.ecapav-arch-ico{width:44px;height:44px;border-radius:12px;background:#a0f1e7;color:#00504a;display:inline-flex;align-items:center;justify-content:center;font-size:19px;flex:0 0 auto}
.ecapav-arch-t{font-size:17px;font-weight:700;color:#111d23}
.ecapav-arch-s{font-size:13px;color:#3d4947;margin-top:2px}
.ecapav-cmp{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.ecapav-side{border-radius:12px;padding:18px 20px}
.ecapav-side-on{background:#e9f6fd}
.ecapav-side-off{background:#fff;border:1px solid #e3f0f8}
.ecapav-side-h{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}
.ecapav-side-t{font-size:16px;font-weight:700;color:#111d23;display:flex;align-items:center;gap:8px}
.ecapav-side-on .ecapav-side-t{color:#00504a}
.ecapav-tag-on{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:#84f5e8;color:#00201d;white-space:nowrap}
.ecapav-tag-off{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:#e3f0f8;color:#3d4947;white-space:nowrap}
.ecapav-ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
.ecapav-ul li{display:flex;align-items:flex-start;gap:10px;font-size:13px;line-height:19px;color:#3d4947}
.ecapav-ul li strong{color:#111d23;font-weight:600}
.ecapav-ok{color:#006a62;font-size:15px;flex:0 0 auto;margin-top:2px}
.ecapav-ko{color:#ba1a1a;font-size:15px;flex:0 0 auto;margin-top:2px}
.ecapav-nf{color:#6d7a77;font-size:15px;flex:0 0 auto;margin-top:2px}
@media(max-width:1200px){.ecapav-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.ecapav-cards{grid-template-columns:1fr}}
@media(max-width:820px){.ecapav-cmp{grid-template-columns:1fr}}
@media(max-width:600px){.ecapav-kpis{grid-template-columns:1fr}}
</style>";
}

// Lignes du journal des detections: meme format que l'onglet Evenements
// ("Mon DD HH:MM:SS ... [pid]:URL [virus] ip"). L'annee n'est pas dans la ligne:
// une date dans le futur est celle de l'an dernier.
function ecapav_detections():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ecap/events/2000/NONE"));
    if(!is_object($json) || !isset($json->Logs) || !is_array($json->Logs)){return array();}
    $now=time();
    $out=array();
    foreach($json->Logs as $line){
        $line=trim((string)$line);
        if($line==""){continue;}
        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:(.+?)\s+\[(.+?)\]\s+(.+)#",$line,$re)){continue;}
        $ts=strtotime("$re[1] $re[2] $re[3]");
        if($ts===false){continue;}
        if($ts>$now+86400){$ts=strtotime("-1 year",$ts);}
        $out[]=array("time"=>$ts,"ip"=>trim($re[7]));
    }
    return $out;
}

// Exclusions actives (null si l'API ne repond pas: le compteur affiche alors un tiret).
function ecapav_active_exclusions():?int{
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ecap-whitelist");
    $resp=json_decode((string)$raw,true);
    if(!is_array($resp) || empty($resp["success"]) || !is_array($resp["data"] ?? null)){return null;}
    $n=0;
    foreach($resp["data"] as $r){if(intval($r["enabled"] ?? 0)==1){$n++;}}
    return $n;
}

function table1():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eCAPClamavMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavMaxSize"));
    $eCAPClamavSkipScanning=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavSkipScanning"));
    if($eCAPClamavSkipScanning==null){
        $eCAPClamavSkipScanning="video/.*,image/.*,text/html";
    }
    $eCAPClamavEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavEmergency"));

    $templateCustom=true;
    $template=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavTemplate"));
    if(strlen($template)<50){
        $templateCustom=false;
        $template=@file_get_contents("/usr/share/artica-postfix/ressources/databases/ecap.template");
    }
    if($eCAPClamavMaxSize==0){$eCAPClamavMaxSize=4;}

    // Indicateurs calcules sur le journal des detections.
    $now=time(); $d24=0; $d1=0; $ips=array(); $last=0;
    foreach(ecapav_detections() as $d){
        if($d["time"]>$last){$last=$d["time"];}
        if($d["time"]<$now-86400){continue;}
        $d24++;
        if($d["time"]>=$now-3600){$d1++;}
        if($d["ip"]!=""){$ips[$d["ip"]]=true;}
    }
    $endpoints=count($ips);
    $exclusions=ecapav_active_exclusions();

    $jsMain="Loadjs('$page?section-main-js=yes')";
    $jsTpl="Loadjs('$page?section-template-js=yes')";
    // L'onglet Exclusions est un onglet de tabs_default(): un clic sur son lien le charge.
    $jsExcl="\$('a[data-url=\'fw.proxy.ecap.whitelist.php\']').first().trigger('click');";
    $jsEvents="\$('a[data-url=\'$page?events=yes\']').first().trigger('click');";

    $h=array();
    $h[]="<div class='ecapav'>";

    if($eCAPClamavEmergency==1){
        $h[]="<div class='ecapav-emg'><div class='ecapav-emg-l'>";
        $h[]="<span class='ecapav-emg-ico'><i class='fas fa-triangle-exclamation'></i></span><div>";
        $h[]="<div class='ecapav-emg-t'>{ecapav_emergency_title}<span class='ecapav-emg-b'>{ecapav_emergency_badge}</span></div>";
        $h[]="<p class='ecapav-emg-p'>{ecapav_emergency_explain}</p>";
        $h[]="</div></div>";
        $h[]="<a class='awc-btn awc-btn-warn' href=\"javascript:blur();\" OnClick=\"Loadjs('fw.proxy.emergency.remove.php');\">"
            ."<i class='fas fa-shield-heart'></i> {ecapav_restore}</a>";
        $h[]="</div>";
    }

    // Indicateurs
    $lastTxt=$last>0?"{ecapav_last_detection} ".htmlspecialchars($tpl->time_to_date($last,true)):"{ecapav_no_detection}";
    $redNum=$d24>0?" ecapav-red":"";
    $h[]="<div class='ecapav-kpis'>";
    $h[]="<div class='ecapav-kpi'><div class='ecapav-kpi-top'><span class='ecapav-kpi-lbl'>{ecapav_threats_blocked}</span>"
        ."<span class='ecapav-kpi-ico ecapav-red'><i class='fas fa-shield-virus'></i></span></div>"
        ."<div class='ecapav-kpi-val'><span class='ecapav-kpi-num$redNum'>$d24</span>"
        .($d1>0?"<span class='ecapav-delta'>+$d1 {ecapav_last_hour}</span>":"")."</div>"
        ."<div class='ecapav-kpi-sub'><span>{last_24h}</span>"
        ."<a class='ecapav-link' href=\"javascript:blur();\" OnClick=\"$jsEvents\">{ecapav_see_journal} <i class='fas fa-arrow-right'></i></a></div></div>";
    $h[]="<div class='ecapav-kpi'><div class='ecapav-kpi-top'><span class='ecapav-kpi-lbl'>{ecapav_endpoints}</span>"
        ."<span class='ecapav-kpi-ico'><i class='fas fa-laptop'></i></span></div>"
        ."<div class='ecapav-kpi-val'><span class='ecapav-kpi-num'>$endpoints</span><span class='ecapav-kpi-unit'>{ecapav_endpoints_unit}</span></div>"
        ."<div class='ecapav-kpi-sub'><span>$lastTxt</span></div></div>";
    $h[]="<div class='ecapav-kpi'><div class='ecapav-kpi-top'><span class='ecapav-kpi-lbl'>{ecapav_max_scan}</span>"
        ."<span class='ecapav-kpi-ico ecapav-teal'><i class='fas fa-file-zipper'></i></span></div>"
        ."<div class='ecapav-kpi-val'><span class='ecapav-kpi-num ecapav-teal'>$eCAPClamavMaxSize {ecapav_mb}</span><span class='ecapav-kpi-unit'>{ecapav_per_file}</span></div>"
        ."<div class='ecapav-kpi-sub'><span>{ecapav_max_scan_sub}</span>"
        ."<a class='ecapav-link' href=\"javascript:blur();\" OnClick=\"$jsMain\">{ecapav_adjust}</a></div></div>";
    $h[]="<div class='ecapav-kpi'><div class='ecapav-kpi-top'><span class='ecapav-kpi-lbl'>{ecapav_exclusion_rules}</span>"
        ."<span class='ecapav-kpi-ico'><i class='fas fa-folder-minus'></i></span></div>"
        ."<div class='ecapav-kpi-val'><span class='ecapav-kpi-num'>".($exclusions===null?"-":$exclusions)."</span><span class='ecapav-kpi-unit'>{ecapav_active_filters}</span></div>"
        ."<div class='ecapav-kpi-sub'><span>{ecapav_exclusion_sub}</span>"
        ."<a class='ecapav-link' href=\"javascript:blur();\" OnClick=\"$jsExcl\">{manage} <i class='fas fa-arrow-right'></i></a></div></div>";
    $h[]="</div>";

    // Cartes de reglage: toute la carte ouvre la modale correspondante.
    $mimes=array();
    foreach(explode(",",$eCAPClamavSkipScanning) as $m){
        $m=trim($m);
        if($m==""){continue;}
        $mimes[]="<span class='ecapav-mime'>".htmlspecialchars($m)."</span>";
    }
    $mimeCell=count($mimes)>0?"<div class='ecapav-mimes'>".@implode("",$mimes)."</div>":"<span class='ecapav-none'>{ecapav_no_mime}</span>";
    $bytes=number_format($eCAPClamavMaxSize*1048576,0,","," ");
    $tplSize=number_format(strlen((string)$template),0,","," ");
    $tplName=$templateCustom?"{ecapav_tpl_custom}":"{ecapav_tpl_default}";

    $h[]="<div class='ecapav-cards'>";
    $h[]="<div class='ecapav-card' OnClick=\"$jsMain\"><div class='ecapav-card-top'>"
        ."<span class='ecapav-card-ico'><i class='fas fa-box-archive'></i></span><span class='ecapav-pen'><i class='fas fa-pen'></i></span></div>"
        ."<div class='ecapav-card-t'>{ecapav_max_scan}</div><p class='ecapav-card-p'>{ecapav_card_maxsize_explain}</p>"
        ."<div class='ecapav-well'><div class='ecapav-well-lbl'>{ecapav_configured_limit}</div><div class='ecapav-size'>"
        ."<span><span class='ecapav-size-n'>$eCAPClamavMaxSize</span><span class='ecapav-size-u'>{ecapav_megabytes}</span></span>"
        ."<span class='ecapav-bytes'>$bytes {bytes}</span></div></div></div>";
    $h[]="<div class='ecapav-card' OnClick=\"$jsMain\"><div class='ecapav-card-top'>"
        ."<span class='ecapav-card-ico'><i class='fas fa-filter-circle-xmark'></i></span><span class='ecapav-pen'><i class='fas fa-pen'></i></span></div>"
        ."<div class='ecapav-card-t'>{ecapav_card_mime_title}</div><p class='ecapav-card-p'>{ecapav_card_mime_explain}</p>"
        ."<div class='ecapav-well'><div class='ecapav-well-lbl'>{ecapav_mime_ignored}</div>$mimeCell</div></div>";
    $h[]="<div class='ecapav-card' OnClick=\"$jsTpl\"><div class='ecapav-card-top'>"
        ."<span class='ecapav-card-ico'><i class='fas fa-window-maximize'></i></span><span class='ecapav-pen'><i class='fas fa-pen'></i></span></div>"
        ."<div class='ecapav-card-t'>{ecapav_card_tpl_title}</div><p class='ecapav-card-p'>{ecapav_card_tpl_explain}</p>"
        ."<div class='ecapav-well'><div class='ecapav-file'><span class='ecapav-file-b'>HTML</span>"
        ."<span><div class='ecapav-file-t'>$tplName</div><div class='ecapav-file-s'>$tplSize {bytes}</div></span>"
        ."<span class='ecapav-file-e'><i class='fas fa-eye'></i> {ecapav_edit}</span></div></div></div>";
    $h[]="</div>";

    // Comparatif: pourquoi l'analyse dans le proxy.
    $h[]="<div class='ecapav-arch'><div class='ecapav-arch-h'>"
        ."<span class='ecapav-arch-ico'><i class='fas fa-diagram-project'></i></span>"
        ."<div><div class='ecapav-arch-t'>{ecapav_arch_title}</div><div class='ecapav-arch-s'>{ecapav_arch_sub}</div></div></div>";
    $h[]="<div class='ecapav-cmp'>";
    $h[]="<div class='ecapav-side ecapav-side-on'><div class='ecapav-side-h'>"
        ."<span class='ecapav-side-t'><i class='fas fa-bolt'></i> {ecapav_arch_ecap}</span><span class='ecapav-tag-on'>{recommended}</span></div>"
        ."<ul class='ecapav-ul'>"
        ."<li><span class='ecapav-ok'><i class='far fa-circle-check'></i></span><span><strong>{ecapav_pro1_t}</strong> {ecapav_pro1}</span></li>"
        ."<li><span class='ecapav-ok'><i class='far fa-circle-check'></i></span><span><strong>{ecapav_pro2_t}</strong> {ecapav_pro2}</span></li>"
        ."<li><span class='ecapav-nf'><i class='fas fa-circle-info'></i></span><span>{ecapav_pro3}</span></li>"
        ."</ul></div>";
    $h[]="<div class='ecapav-side ecapav-side-off'><div class='ecapav-side-h'>"
        ."<span class='ecapav-side-t'><i class='fas fa-server'></i> {ecapav_arch_ext}</span><span class='ecapav-tag-off'>{ecapav_classic}</span></div>"
        ."<ul class='ecapav-ul'>"
        ."<li><span class='ecapav-ko'><i class='far fa-circle-xmark'></i></span><span><strong>{ecapav_con1_t}</strong> {ecapav_con1}</span></li>"
        ."<li><span class='ecapav-ko'><i class='far fa-circle-xmark'></i></span><span><strong>{ecapav_con2_t}</strong> {ecapav_con2}</span></li>"
        ."<li><span class='ecapav-nf'><i class='fas fa-gears'></i></span><span>{ecapav_con3}</span></li>"
        ."</ul></div>";
    $h[]="</div></div>";
    $h[]="</div>";

    $jsinstall=$tpl->framework_buildjs(
        "/proxy/ecap/install",
        "squid.ecap.progress",
        "squid.ecap.progress.log","progress-squid-ecap-restart");


    $jsreconfigure=$tpl->framework_buildjs(
        "/proxy/ecap/install",
        "squid.ecap.progress",
        "squid.ecap.progress.log","progress-squid-ecap-restart");

    $topbuttons[] = array("Loadjs('$page?ecap-clamav-uninstall-js=yes');",ico_trash,"{uninstall}");
    $topbuttons[] = array($jsinstall,ico_refresh,"{reload}");
    $topbuttons[] = array($jsreconfigure,ico_refresh,"{reconfigure}");
    $topbuttons[] = array("Loadjs('fw.proxy.emergency.remove.php');",ico_bug,"{disable_emergency_mode}");

    $TINY_ARRAY["TITLE"]="{integrated_antivirus}";
    $TINY_ARRAY["ICO"]=ico_antivirus;
    $TINY_ARRAY["EXPL"]="{integrated_antivirus_explain}";
    $TINY_ARRAY["URL"]="ecapav";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    // awc_table_style() porte .awc-btn / .awc-btn-warn du bouton du bandeau d'urgence.
    echo awc_table_style().ecapav_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$h))
        ."<script>NoSpinner();$jstiny</script>";
    return true;
}
function ecap_clamav_uninstall_ask():bool{
    $tpl=new template_admin();
    $jsinstall=$tpl->framework_buildjs(
        "/proxy/ecap/uninstall",
        "squid.ecap.progress",
        "squid.ecap.progress.log","progress-squid-ecap-restart",
        "document.location.href='/index';");

    echo $tpl->js_confirm_execute("{disable_feature} {antivirus_proxy} eCAP",
        "ecap-clamav-uninstall","{disable_feature} {antivirus_proxy}",$jsinstall);
    return true;
}
function ecap_clamav_install_confirm():bool{
    admin_tracks("Uninstall Antivirus for Proxy (eCAP) mode");
    return true;
}
function save():bool{
    $tpl=new template_admin();
    if(isset($_POST["eCAPClamavSkipScanning"])){
        $_POST["eCAPClamavSkipScanning"]=str_replace("\n",",",$_POST["eCAPClamavSkipScanning"]);
    }
    $tpl->SAVE_POSTs();
    return true;

}

function section_main_popup():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $security="AsSquidAdministrator";
    $tpl->CLUSTER_CLI=True;

    $eCAPClamavMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavMaxSize"));
    if($eCAPClamavMaxSize==0){$eCAPClamavMaxSize=4;}
    $eCAPClamavSkipScanning=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavSkipScanning"));
    if($eCAPClamavSkipScanning==null){
        $eCAPClamavSkipScanning="video/.*,image/.*,text/html";
    }
    $eCAPClamavSkipScanning=str_replace(",","\n",$eCAPClamavSkipScanning);
    $form[]=$tpl->field_numeric("eCAPClamavMaxSize","{srv_clamav.MaxScanSize} (MB)",$eCAPClamavMaxSize,"");
    $form[]=$tpl->field_textarea("eCAPClamavSkipScanning","{exclude} {ExcludeMimeType}",$eCAPClamavSkipScanning);




    $jsafter=section_js_form();
    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",$jsafter,$security,true)."</td>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}




