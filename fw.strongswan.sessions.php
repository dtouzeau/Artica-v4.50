<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc"); if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
// Include INCONDITIONNEL de premier niveau (CLAUDE.md, awc-* regle 1):
// place dans le bootstrap if(!class_exists("sockets")) ci-dessus il ne
// s'executerait jamais, et la page mourrait sur awc_table_style() indefinie. // Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus(); if (!$users->AsVPNManager) {
    exit();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}

if (isset($_GET["download"])) {
    download();
    exit;
}
if (isset($_GET["delete-js"])) {
    delete_rule_js();
    exit;
}
if (isset($_GET["ruleid-js"])) {
    rule_js();
    exit;
}
if (isset($_GET["rule-popup"])) {
    rule_popup();
    exit;
}
if (isset($_GET["main"])) {
    main();
    exit;
}
if (isset($_REQUEST["GenerateProgress"])) {
    GenerateProgress();
    exit;
}
if (isset($_POST["connection_name"])) {
    buildconfig();
    exit;
}
page();



function page()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("strongswan.php?refresh-sessions=yes");
    $strongSwanCNXNUmber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanCNXNUmber"));
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{APP_STRONGSWAN} {strongswan_real_time_sessions}</h1><p>$btn</p><p>$strongSwanCNXNUmber {sessions}</p></div>
	<div class=\"col-sm-8\" style='padding-top:20px'></div>
	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/ipsec-sessions');	
	LoadAjax('table-loader','$page?main=yes');

	</script>";

    $tpl=new templates();
    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Sessions',$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);
}

function main()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $vpn=new strongswan();
    $nic=new networking();
    $sock=new sockets();
    $page=CurrentPageName();
    
    $since=$tpl->javascript_parse_text("{since}");
    $conn_name=$tpl->javascript_parse_text("{tunnel}");
    $status=$tpl->javascript_parse_text("{status}");
    $local_ip_address=$tpl->javascript_parse_text("{remote_ip_address}");
    $ipaddr=$tpl->javascript_parse_text("{local_ip_address}");
    $userid=$tpl->javascript_parse_text("{member}");
    $BytesReceived=$tpl->javascript_parse_text("{received}");
    $BytesSent=$tpl->javascript_parse_text("{sended}");
    
    // Les cellules etaient emises AU FIL DES CLES rencontrees dans la reponse
    // vici, avec un $GLOBALS['bp'] pour arbitrer entre remote-vips et
    // remote-ts. Une session a qui il manquait une cle decalait donc toutes
    // les colonnes suivantes de la LIGNE, en silence -- un tableau a colonnes
    // fixes exige une extraction par cles NOMMEES. La forme est celle de
    // "vici list-sas" (voir exec.strongswan.sessions.php, qui lit $w['state'],
    // $w['established'] et $w['remote-vips'] exactement de la meme facon).
    $sessions=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanClientsArray"));
    if(!is_array($sessions)){$sessions=array();}

    $rows=array();
    foreach($sessions as $v){
        if(!is_array($v)){continue;}
        foreach($v as $conn=>$w){
            if(!is_array($w)){continue;}
            $state=isset($w["state"])?(string)$w["state"]:"";
            $rhost=isset($w["remote-host"])?(string)$w["remote-host"]:"";
            $user=isset($w["remote-eap-id"])?(string)$w["remote-eap-id"]
                 :(isset($w["remote-id"])?(string)$w["remote-id"]:"");
            $since=isset($w["established"])?secondsToTime($w["established"]):"";

            // remote-vips vit au niveau de la CONNEXION; le selecteur de trafic
            // distant vit dans la child-sa. On prefere le premier quand il
            // existe -- c'est l'adresse reellement attribuee au client -- et on
            // retombe sur le second, ce que faisait le drapeau $bp.
            $vips="";
            if(isset($w["remote-vips"])){
                $vips=is_array($w["remote-vips"])?@implode(", ",$w["remote-vips"]):(string)$w["remote-vips"];
            }
            $in=0;$out=0;$ts="";
            if(isset($w["child-sas"]) && is_array($w["child-sas"]) && count($w["child-sas"])>0){
                // L'original ne gardait que la DERNIERE child-sa (il decalait le
                // tableau jusqu'a n'en laisser qu'une): meme choix ici.
                $sas=$w["child-sas"];
                $last=end($sas);
                if(is_array($last)){
                    $in=isset($last["bytes-in"])?floatval($last["bytes-in"]):0;
                    $out=isset($last["bytes-out"])?floatval($last["bytes-out"]):0;
                    if(isset($last["remote-ts"]) && is_array($last["remote-ts"]) && isset($last["remote-ts"][0])){
                        $ts=(string)$last["remote-ts"][0];
                    }
                }
            }
            if($vips===""){$vips=$ts;}

            $up=(strtoupper($state)==="ESTABLISHED");
            $named=(trim($user)!=="");

            $h ="<tr>";
            // Pentest 2026-11-04 : connexion/utilisateur/hote distant (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1 (XSS stockee).
            $h.="<td><div class='awc-ident'>";
            $h.="<span class='awc-ico".($up?"":" awc-ico-off")."'><i class='fas fa-shield-halved'></i></span>";
            $h.="<span><span class='awc-name".($up?"":" awc-name-off")."'>".req_html((string)$conn)."</span>";
            $h.="<div class='awc-meta'>".($named?req_html($user):"{unknown}")."</div></span></div></td>";
            $h.="<td class='center'><span class='awc-badge ".($up?"awc-badge-on":"awc-badge-off")."'>"
               .req_html($state!==""?$state:"-")."</span></td>";
            $h.="<td class='awc-mono'>".req_html($rhost)."</td>";
            $h.="<td class='awc-mono'>".($vips!==""?req_html($vips):"<span class='awc-none'>&mdash;</span>")."</td>";
            $h.="<td class='center awc-mono'>".FormatBytes($in/1024)."</td>";
            $h.="<td class='center awc-mono'>".FormatBytes($out/1024)."</td>";
            $h.="<td class='awc-mono'>".($since!==""?req_html($since):"<span class='awc-none'>&mdash;</span>")."</td>";
            $h.="</tr>";
            $rows[]=$h;
        }
    }

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{tunnel}</th>";
    $html[]="<th class='center' nowrap>{status}</th>";
    $html[]="<th nowrap>{remote_ip_address}</th>";
    $html[]="<th nowrap>{local_ip_address}</th>";
    $html[]="<th class='center' nowrap>{received}</th>";
    $html[]="<th class='center' nowrap>{sended}</th>";
    $html[]="<th nowrap>{since}</th>";
    $html[]="</tr></thead><tbody id='ssws-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='7'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)." {items}</span>"
           ."<span class='awc-pg' id='ssws-pager'></span></div>";
    $html[]="</div></div>";

    // CSS et pager HORS _ENGINE_parse_body; ICON_SCRIPTS_COMPILE() et non
    // ICON_SCRIPTS (propriete d'instance, vide des qu'une autre instance a
    // bati la ligne).
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>"
        .awc_pager_js(50,"ssws-rows","ssws-pager");

    echo "
	<script>
function CreateNewVPNClient(){
	Loadjs('$page?ruleid-js=');
}
var xxXGenerateVPNConfig= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){ Loadjs(tempvalue); }
	
}

function XGenerateVPNConfig(uid){
	var XHR = new XHRConnection();
	XHR.appendData('GenerateProgress',uid);
	XHR.sendAndLoad('$page', 'POST',xxXGenerateVPNConfig);		
}	

	</script>";
    // Le tableau a deja ete emis plus haut. Ce second echo @implode($html)
    // reimprimait le MEME balisage une deuxieme fois, non parse -- une
    // duplication silencieuse introduite par la conversion.
}
function secondsToTime($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%h hours, %i minutes and %s seconds');
}