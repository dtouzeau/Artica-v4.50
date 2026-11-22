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
	if(!isset($_SESSION["BACKUP_SEARCH"])){$_SESSION["BACKUP_SEARCH"]="today this hour 50 events";}
	
	$html[]="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["BACKUP_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>";

    $TINY_ARRAY["TITLE"]="Backup: {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{SNAPSHOTS_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$jstiny;
    $html[]="function Search$t(e){";
    $html[]="\tif(!checkEnter(e) ){return;}";
    $html[]="ss$t();";
    $html[]="}";
    $html[]="function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

	
	echo $tpl->_ENGINE_parse_body($html);

}

/**
 * CSS propre a la page: uniquement l'echelle de severite des evenements.
 * Le reste vient de awc_table_style() (class.statistics_appliance.inc), partage.
 * Concatene autour du parsing, jamais dedans: _ENGINE_parse_body prendrait chaque
 * corps de regle CSS pour un {token}.
 */
function bke_style():string{
    return "<style>
.bke-ico-err{background:rgba(186,26,26,.10);color:#ba1a1a}
.bke-ico-warn{background:rgba(134,83,0,.10);color:#865300}
.bke-ico-ok{background:rgba(0,106,98,.10);color:#006a62}
.bke-msg{font-size:13px;line-height:19px;color:#111d23;word-break:break-word}
.bke-msg .text-danger{color:#ba1a1a}
.bke-msg .text-warning{color:#865300}
.bke-msg .text-success{color:#006a62}
</style>";
}

/**
 * Severite d'une ligne de journal -> (suffixe de classe, icone, classe de texte).
 * Les motifs sont ceux de l'ancien code, regroupes en un seul point de decision:
 * ils s'appliquaient en cascade, chacun re-encapsulant la ligne dans un <span>,
 * la derniere correspondance l'emportant visuellement.
 */
function bke_severity($line):array{
    if(preg_match("#(Failed password|Invalid user|user unknown|error)#i",$line)){
        return array("err","fas fa-circle-exclamation","text-danger");
    }
    if(preg_match("#(warning notify|restarting|authentication failures|authentication failure|Did not receive identification string)#i",$line)){
        return array("warn","fas fa-triangle-exclamation","text-warning");
    }
    if(preg_match("#(success|Server listening|Accepted keyboard-interactive)#i",$line)){
        return array("ok","fas fa-circle-check","text-success");
    }
    return array("none","fas fa-circle-info","");
}

/** Une ligne de journal: horodatage + PID en identite, message a droite. */
function bke_row($FTime,$pid,$line,$raw=false):string{
    if($raw){
        $sev="none"; $ico="fas fa-circle-question"; $textclass="";
        $msg=req_html($line);
    }else{
        list($sev,$ico,$textclass)=bke_severity($line);
        // Pentest 2026-11-04 : ligne de journal (donnee externe) affichee sans encodage (XSS stockee).
        $msg=req_html($line);
        if($textclass<>""){$msg="<span class='$textclass'>$msg</span>";}
    }
    $icocls=($sev=="none")?"awc-ico":"awc-ico bke-ico-$sev";
    $name=($FTime=="")?"<span class='awc-none'>&mdash;</span>":req_html($FTime);
    $meta=($pid=="")?"":"<div class='awc-meta'>PID ".req_html(strval($pid))."</div>";

    $h ="<tr>";
    $h.="<td style='width:1%' nowrap><div class='awc-ident'>";
    $h.="<span class='$icocls'><i class='$ico'></i></span>";
    $h.="<span><span class='awc-name'>$name</span>$meta</span>";
    $h.="</div></td>";
    $h.="<td><div class='bke-msg'>$msg</div></td>";
    $h.="</tr>";
    return $h;
}

function search(){
	$sock=new sockets();
	$tpl=new template_admin();
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("artica.php?snapshots-events=$line");
	$filename=PROGRESS_DIR."/snapshots-backup.log";

	$data=explode("\n",strval(@file_get_contents($filename)));
	if(count($data)>3){$_SESSION["BACKUP_SEARCH"]=$_GET["search"];}
	rsort($data);

	$rows=array();
	foreach ($data as $line){
		$line=trim($line);
		if($line==""){continue;}
		// Les lignes non reconnues etaient echo'ees brutes en rouge, hors du tableau:
		// elles cassaient la structure et sortaient sans echappement. Elles deviennent
		// une ligne normale, texte echappe, sans horodatage.
		if(!preg_match("#^(.+?)\s+([0-9])+\s+([0-9:]+)\s+(.+?)\s+snapshots\[([0-9]+)\]:\s+(.+)#", $line,$re)){
			$rows[]=bke_row("","",$line,true);
			continue;
		}

		$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
		$xtime=strtotime($date);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));

		$rows[]=bke_row($FTime,$re[5],$re[6]);
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{date}</th>";
	$html[]="<th>{events}</th>";
	$html[]="</tr></thead><tbody id='bke-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$total=count($rows);
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $total / $total {events}</span>";
	$html[]="<span class='awc-pg' id='bke-pager'></span>";
	$html[]="</div></div></div>";

	// Motif syslog utilise pour le decoupage: contenu de fichier, donc echappe.
	$pattern=trim(strval(@file_get_contents(PROGRESS_DIR."/snapshots-backup.log.pattern")));
	if(strlen($pattern)>0){
		$html[]="<div class='awc-meta' style='padding:0 16px 10px 16px'>"
			."<span style='font-family:monospace'>".req_html($pattern)."</span></div>";
	}

	echo awc_table_style().bke_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.awc_pager_js(50,"bke-rows","bke-pager");
}
