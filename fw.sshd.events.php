<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$t=time();
	if(!isset($_SESSION["SSHD_SEARCH"])){$_SESSION["SSHD_SEARCH"]="today this hour 50 events";}
    $html[]="<div style='margin-top:10px'>";
	$html[]=$tpl->search_block($page);
    $html[]="</div>";
    $TINY_ARRAY["TITLE"]="{APP_OPENSSH}: {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{OPENSSH_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshd";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="</script>";

	
	echo $tpl->_ENGINE_parse_body($html);

}
function re_preg($line):array{


    if(preg_match("#^(.+?)\s+([0-9])+\s+([0-9:]+)\s+(.+?)\s+sshd\[([0-9]+)\]:\s+(.+)#", $line,$re)) {
        return $re;
    }
    if(preg_match("#(.+?)\s+([0-9])+\s+([0-9:]+)\s+(.+?)\s+sshd\[(.+?)\]:\s+(.+)#", $line,$re)) {
        return $re;
    }

    if(preg_match("#(.+?)\s+([0-9])+\s+([0-9:]+)\s+(.+?)\s+sshproxy\[(.+?)\]:\s+(.+)#", $line,$re)) {
        return $re;
    }

    return array();

}

/**
 * CSS propre a la page: uniquement l'echelle de severite des evenements.
 * Le reste vient de awc_table_style() (class.statistics_appliance.inc), partage.
 * Concatene autour du parsing, jamais dedans: _ENGINE_parse_body prendrait chaque
 * corps de regle CSS pour un {token}.
 */
function ssh_style():string{
    return "<style>
.ssh-ico-err{background:rgba(186,26,26,.10);color:#ba1a1a}
.ssh-ico-warn{background:rgba(134,83,0,.10);color:#865300}
.ssh-ico-ok{background:rgba(0,106,98,.10);color:#006a62}
.ssh-msg{font-size:13px;line-height:19px;color:#111d23}
.ssh-msg .text-danger{color:#ba1a1a}
.ssh-msg .text-warning{color:#865300}
.ssh-msg .text-success{color:#006a62}
</style>";
}

/**
 * Severite d'une ligne de journal -> (suffixe de classe, icone).
 * Les motifs sont ceux de l'ancien code, regroupes en un seul point de decision:
 * ils s'appliquaient auparavant en cascade, la derniere correspondance gagnant.
 */
function ssh_severity($line):array{
    if(preg_match("#(error:|fatal|Failed password|Invalid user|user unknown)#i",$line)){
        return array("err","fas fa-circle-exclamation","text-danger");
    }
    if(preg_match("#(warning notify|restarting|authentication failures|authentication failure|Did not receive identification string)#i",$line)){
        return array("warn","fas fa-triangle-exclamation","text-warning");
    }
    if(preg_match("#(Accepted password|proxy running|Server listening|Accepted keyboard-interactive)#i",$line)){
        return array("ok","fas fa-circle-check","text-success");
    }
    return array("none","fas fa-circle-info","");
}

/** Enrichit le message: etiquettes DENY/ALLOW et prefixes de modules. */
function ssh_decorate($line):string{
    $line=str_replace("[DENY]","<span class='label label-danger'>Deny</span>",$line);
    $line=str_replace("[ALLOW]","<span class='label label-primary'>Allow</span>",$line);
    $line=str_replace("CountryChecker:","<span class='label label-default'>Country checker</span>&nbsp;",$line);
    $line=str_ireplace("reputation:","<span class='label label-default'>Reputation</span>&nbsp;",$line);
    $line=str_ireplace("aclexec returned 1","<span class='label label-default'>plugin</span>&nbsp;Access denied by extra rules",$line);
    return $line;
}

/** Une ligne de journal: horodatage + PID en identite, message a droite. */
function ssh_row($FTime,$pid,$line):string{
    list($sev,$ico,$textclass)=ssh_severity($line);
    // Pentest 2026-11-04 : ligne sshd (utilisateurs/IP saisis par le client SSH : donnee externe) affichee sans encodage (XSS stockee).
    // Encodee AVANT la decoration : les marqueurs remplaces ne contiennent ni < > & ni quote.
    $msg=ssh_decorate(req_html($line));
    if($textclass<>""){$msg="<span class='$textclass'>$msg</span>";}
    $icocls=($sev=="none")?"awc-ico":"awc-ico ssh-ico-$sev";

    $h ="<tr>";
    $h.="<td style='width:1%' nowrap><div class='awc-ident'>";
    $h.="<span class='$icocls'><i class='$ico'></i></span>";
    $h.="<span><span class='awc-name'>".req_html($FTime)."</span>";
    $h.="<div class='awc-meta'>PID ".req_html(strval($pid))."</div>";
    $h.="</span></div></td>";
    $h.="<td><div class='ssh-msg'>$msg</div></td>";
    $h.="</tr>";
    return $h;
}

function search(){
	$tpl=new template_admin();
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    // Le terme est concatene dans le CHEMIN de l'appel REST: sans encodage, un "/"
    // ou un "?" saisi par l'operateur casse la route (ou la detourne).
    $data=$sock->REST_API("/ssh/events/$rp/".rawurlencode($search));

    $json=json_decode($data);
    // Les deux gardes affichaient l'erreur puis laissaient la fonction continuer
    // jusqu'a $json->Logs, inexistant.
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
        return;
    }
    if(!is_object($json)){
        echo $tpl->div_error("{error}<hr>{no_data}");
        return;
    }
    if(!$json->Status){
        // div_error() encode deja son texte (pas de double encodage).
        echo $tpl->div_error("{error}<br>Framework return false!<hr>".strval($json->Error));
        return;
    }
    if(!property_exists($json,"Logs")){
        echo $tpl->div_error("{error}<hr>Undefined property Logs");
        return;
    }

    $rows=array();
    foreach ($json->Logs as $line){
		$line=trim($line);
	    $re=re_preg($line);
        if(count($re)==0){
            continue;
        }

		$date="$re[1] $re[2] ".date('Y')." $re[3]";
		$xtime=strtotime($date);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));

		$rows[]=ssh_row($FTime,$re[5],$re[6]);
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{date}</th>";
	$html[]="<th>{events}</th>";
	$html[]="</tr></thead><tbody id='ssh-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$total=count($rows);
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $total / $total {events}</span>";
	$html[]="<span class='awc-pg' id='ssh-pager'></span>";
	$html[]="</div></div></div>";

	// Motif syslog utilise pour le decoupage: contenu de fichier, donc echappe.
	$pattern=trim(strval(@file_get_contents(PROGRESS_DIR."/sshd.syslog.pattern")));
	if(strlen($pattern)>0){
		$html[]="<div class='awc-meta' style='padding:0 16px 10px 16px'>"
			."<span style='font-family:monospace'>".req_html($pattern)."</span></div>";
	}

	echo awc_table_style().ssh_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"ssh-rows","ssh-pager");
}
