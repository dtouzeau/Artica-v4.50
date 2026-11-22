<?php
// stamp 01/11/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.rdpproxy.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["access-events"])){ACCESS_EVENTS();exit;}
if(isset($_GET["access-events-table"])){ACCESS_EVENTS_TABLE();exit;}
if(isset($_GET["APP_RDPPROXY_AUTHHOOK"])){APP_RDPPROXY_AUTHHOOK_EVENTS();exit;}
if(isset($_GET["AUTHHOOK-SEARCH"])){APP_RDPPROXY_AUTHHOOK_SEARCH();exit;}
if(isset($_GET["APP_RDPPROXY"])){APP_RDPPROXY_EVENTS();exit;}
if(isset($_GET["APP-RDPPROXY-SEARCH"])){APP_RDPPROXY_SEARCH();exit;}



page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {events}</h1>
	<p>{rdpproxy_explain_web}</p>

	</div>

	</div>
		

		
	<div class='row'><div id='progress-rdpproxy-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-rdpproxy-events'></div>

	</div>
	</div>
		
		
		
	<script>
	$.address.state('/');
	$.address.value('/rdpproxy-events');
	LoadAjax('table-rdpproxy-events','$page?tabs=yes');
		
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {events}",$html);
        echo $tpl->build_firewall();
        return;
    }



    echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{rdpproxy_access_events}"]="$page?access-events=yes";
    $array["{APP_RDPPROXY_AUTHHOOK}"]="$page?APP_RDPPROXY_AUTHHOOK=yes";
    $array["{APP_RDPPROXY}"]="$page?APP_RDPPROXY=yes";
    echo $tpl->tabs_default($array);
}

function APP_RDPPROXY_EVENTS(){
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Valeur echappee : elle revient de la session, donc d'une saisie precedente.
    $searchval=htmlspecialchars((string)(isset($_SESSION["RDPPROXY-SEARCH"])?$_SESSION["RDPPROXY-SEARCH"]:""),ENT_QUOTES);
    $html[]="<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"$searchval\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	      		<span class=\"input-group-btn\"><button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button></span>
	     	</div>
    	</div>
 <div id='table-$t'></div>";


    $html[]="<script>function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-$t','$page?APP-RDPPROXY-SEARCH='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";


    echo $tpl->_ENGINE_parse_body($html);

}

function APP_RDPPROXY_AUTHHOOK_EVENTS(){
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $searchval=htmlspecialchars((string)(isset($_SESSION["AUTHHOOK-SEARCH"])?$_SESSION["AUTHHOOK-SEARCH"]:""),ENT_QUOTES);
    $html[]="<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"$searchval\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	      		<span class=\"input-group-btn\"><button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button></span>
	     	</div>
    	</div>
 <div id='table-$t'></div>";


    $html[]="<script>function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-$t','$page?AUTHHOOK-SEARCH='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";


    echo $tpl->_ENGINE_parse_body($html);
    
}
function APP_RDPPROXY_SEARCH(){
    $tpl=new template_admin();
    $_SESSION["RDPPROXY-SEARCH"]=$_GET["APP-RDPPROXY-SEARCH"];
    $MAIN=$tpl->format_search_protocol($_GET["APP-RDPPROXY-SEARCH"]);
    $error="";
    $exploded=rdpproxy_events("daemon",$MAIN["TERM"],$MAIN["MAX"],$error);
    if($error!=""){echo $tpl->FATAL_ERROR_SHOW_128($error);return;}
    if(!is_array($exploded)){$exploded=array();}

    $LABELS["INFO"]="label label-info";
    $LABELS["DEBUG"]="label";
    $LABELS["ERR"]="label label-danger";
    $LABELS["WARN"]="label label-warning";

    $LABELST["INFO"]="text-primary";
    $LABELST["DEBUG"]="text-primary";
    $LABELST["ERR"]="text-danger font-bold";
    $LABELST["WARN"]="text-warning font-bold";

    $rows=array();
    foreach ($exploded as $line){
        $line=trim($line);
        if($line==null){continue;}
        // Une ligne qu'aucun des deux motifs ne reconnait reste affichee telle
        // quelle, sans date : $date/$type herites de la ligne precedente
        // l'antidataient a tort.
        $date="";
        $type="INFO";

        if(preg_match("#(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?\s+rdpproxy:\s+\[rdpproxy\]\s+(.+)#",$line,$re)){
            $date="{$re[1]} {$re[2]} {$re["3"]}";
            $type="INFO";
            $line=$re[4];
        }

        if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?\s+rdpproxy:\s+([A-Z]+)\s+.+?\s+(.+)#",$line,$re)){
            $date="{$re[1]} {$re[2]} {$re["3"]}";
            $type=$re[4];
            $line=$re[5];
        }
        if($type=="WARNING"){$type="WARN";}
        $xtime=$date==""?0:strtotime($date);
        $rows[]=rdp_event_row($tpl,$xtime,$type,htmlentities($line),$LABELS,$LABELST);
    }
    echo rdp_events_table($tpl,$rows);
}

// Une ligne de journal. $content arrive deja echappe.
function rdp_event_row($tpl,$xtime,$type,$content,$LABELS,$LABELST):string{
    $label=isset($LABELS[$type])?$LABELS[$type]:"label";
    $text=isset($LABELST[$type])?$LABELST[$type]:"";
    $when=$xtime>0?$tpl->time_to_date($xtime,true):"-";
    $type=htmlspecialchars((string)$type);
    $h=array();
    $h[]="<tr>";
    $h[]="<td style='width:1%;white-space:nowrap'>$when</td>";
    $h[]="<td style='width:1%;white-space:nowrap'><span class='$label'>$type</span></td>";
    $h[]="<td style='width:99%'><span class='$text'>$content</span></td>";
    $h[]="</tr>";
    return @implode("",$h);
}

// Le tableau commun aux deux journaux (daemon et authhook).
function rdp_events_table($tpl,$rows):string{
    $t=md5(microtime(true).count($rows));
    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table id='table-$t' class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th>{time}</th>";
    $html[]="<th>{type}</th>";
    $html[]="<th>{events}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody id='rows-$t'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>".count($rows)." {events}</span>";
    $html[]="<span class='awc-pg' id='pager-$t'></span></div>";
    $html[]="</div></div>";
    return awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();</script>".awc_pager_js(50,"rows-$t","pager-$t");
}


function APP_RDPPROXY_AUTHHOOK_SEARCH(){
    $tpl=new template_admin();
    $_SESSION["AUTHHOOK-SEARCH"]=$_GET["AUTHHOOK-SEARCH"];
    $MAIN=$tpl->format_search_protocol($_GET["AUTHHOOK-SEARCH"]);
    $error="";
    $exploded=rdpproxy_events("auth",$MAIN["TERM"],$MAIN["MAX"],$error);
    if($error!=""){echo $tpl->FATAL_ERROR_SHOW_128($error);return;}
    if(!is_array($exploded)){$exploded=array();}

    $LABELS["INFO"]="label label-info";
    $LABELS["DEBUG"]="label";
    $LABELS["ERROR"]="label label-danger";

    $LABELST["INFO"]="text-primary";
    $LABELST["DEBUG"]="text-primary";
    $LABELST["ERROR"]="text-danger font-bold";

    $rows=array();
    foreach ($exploded as $line){
        $line=trim($line);
        if($line==null){continue;}
        // Une ligne non reconnue etait echo brute AVANT le tableau, dans un
        // <code> : hors du tableau, et non echappee. Elle devient une ligne.
        if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+).*?rdpproxy-auth\[.*?\[([A-Z]+)\]([:|\s+])(.+)#",$line,$re)){
            $rows[]=rdp_event_row($tpl,0,"DEBUG",htmlentities($line),$LABELS,$LABELST);
            continue;
        }
        $Month=$tpl->MonthToInteger($re[1]);
        $Day=$re[2];
        $time=$re[3];
        $type=$re[4];
        $content=htmlentities(trim($re[6]));
        $content=str_replace("#012","<br>",$content);
        $xtime=strtotime(date("Y")."-$Month-$Day $time");
        $rows[]=rdp_event_row($tpl,$xtime,$type,$content,$LABELS,$LABELST);
    }
    echo rdp_events_table($tpl,$rows);
}


// ------------------------------------------------------------ Access events (refus et detections)

// Liste fermee des motifs (RDPProxy/store_events.go + store_mfa.go + session_rights.go).
// ssh_* et pattern_refuse : cibles SSH (authhook 1.6.0), voir RDPProxy/CLAUDE.md.
// charter_* : charte d'utilisation (authhook 1.6.2) ; charter_accepted porte l'empreinte du texte accepte.
// ssh_file_* : transferts de fichiers SSH (lot 3, authhook 1.6.1) ; ssh_file_failed couvre tout
// statut autre que ok (echec, annulation, refus, partiel), ecrit dans le sujet.
// Une valeur hors liste fait repondre 400 par le daemon : elle ne part jamais.
function rdp_access_reasons():array{
    return array("no_member","bad_password","member_expired","no_policy","policy_disabled",
        "network_not_allowed","outside_schedule","target_not_linked","target_disabled","license_exceeded",
        "mfa_bad_code","mfa_locked","mfa_cancelled","mfa_timeout","mfa_unavailable",
        "pattern_kill","pattern_notify","ssh_hostkey_mismatch","pattern_refuse","ssh_engine_missing",
        "ssh_file_up","ssh_file_down","ssh_file_failed","charter_refused","charter_accepted",
        "web_engine_missing","web_visit","web_blocked","web_popup","web_copy","web_paste",
        "web_login","web_login_failed","web_login_unavailable","web_netns_unavailable","web_proxy_unavailable",
        "tunnel_agent_offline","web_engine_outdated","web_pac_unavailable",
        // RWE 2026-11-02 (authhook 1.9.6) : echecs apres l'authentification
        "target_connection_failed","target_credentials_cancelled","credentials_missing",
        // RWE 2026-11-04 (authhook 1.9.11) : refus Active Directory detailles
        "ad_no_group","ad_account_refused","ad_unreachable",
        // authhook 1.10.0 : NLA Kerberos du client
        "nla_untrusted","nla_realm_unknown",
        // authhook 1.11.0 : cible leurre ouverte (alerte critique, IP bloquee)
        "decoy_target",
        // authhook 1.13.0 : fichier telecharge depuis une cible WEB (pret ou remis) ou refuse/en echec
        "web_file_down","web_file_failed",
// authhook 1.14.0 : sessions Web gardees apres une deconnexion (mise en attente, reprise, refus, fin)
"web_session_parked","web_session_resumed","web_session_resume_failed","web_session_taken_over",
"web_session_keep_refused","web_session_expired","web_session_closed_by_admin");
}

function ACCESS_EVENTS(){
    $t="ae".time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return;}
    $opts="<option value=''>{all}</option>";
    foreach (rdp_access_reasons() as $code){
        $opts.="<option value='$code'>{rdpproxy_reason_$code}</option>";
    }
    $lim="";
    foreach (array(100,250,500) as $n){$lim.="<option value='$n'>$n {rows}</option>";}
    // Cible et historique des sessions (RWE 2026-11-02) : qui, quand, où
    $terr="";
    $topts="<option value='0'>{rdpproxy_events_all_targets}</option>";
    $tl=rdpproxy_targets($terr);
    if(is_array($tl)){
        foreach ($tl as $tg){
            if(!is_array($tg)){continue;}
            $tid=intval($tg["id"] ?? 0);
            if($tid<1){continue;}
            $topts.="<option value='$tid'>".rdp_esc_user((string)($tg["alias"] ?? "#$tid"))."</option>";
        }
    }
    $kopts="<option value=''>{rdpproxy_events_kind_all}</option><option value='sessions'>{rdpproxy_events_kind_sessions}</option>";
    $html="<div class='ibox-content'>
    <div class='row'>
      <div class='col-sm-3'><input type='text' class='form-control' maxlength='128' id='ae-user-$t' placeholder='{username}' OnKeyPress=\"javascript:if(checkEnter(event)){ae$t();}\"></div>
      <div class='col-sm-2'><select class='form-control' id='ae-target-$t' OnChange=\"javascript:ae$t();\">$topts</select></div>
      <div class='col-sm-2'><select class='form-control' id='ae-kind-$t' OnChange=\"javascript:ae$t();\">$kopts</select></div>
      <div class='col-sm-2'><select class='form-control' id='ae-reason-$t' OnChange=\"javascript:ae$t();\">$opts</select></div>
      <div class='col-sm-2'><select class='form-control' id='ae-limit-$t' OnChange=\"javascript:ae$t();\">$lim</select></div>
      <div class='col-sm-1'><button type='button' class='btn btn-default' OnClick=\"javascript:ae$t();\">Go!</button></div>
    </div>
    </div>
    <div id='ae-table-$t'></div>
    <script>
    // Seule la réponse de la DERNIèRE requête est affichée : deux filtres changés vite lançaient deux
    // LoadAjax, et la réponse la plus lente (souvent la moins filtrée) écrasait l'autre (RWE 2026-11-03).
    var aeSeq$t=0;
    function ae$t(){
        var u=encodeURIComponent(document.getElementById('ae-user-$t').value);
        var r=encodeURIComponent(document.getElementById('ae-reason-$t').value);
        var l=encodeURIComponent(document.getElementById('ae-limit-$t').value);
        var tg=encodeURIComponent(document.getElementById('ae-target-$t').value);
        var k=encodeURIComponent(document.getElementById('ae-kind-$t').value);
        var my=++aeSeq$t;
        var box=document.getElementById('ae-table-$t');
        box.style.opacity='0.5';
        jQuery.get('$page?access-events-table=yes&user='+u+'&reason='+r+'&limit='+l+'&target='+tg+'&kind='+k,function(d){
            if(my!==aeSeq$t){return;}
            box.style.opacity='';
            jQuery(box).html(d);
        });
    }
    ae$t();
    </script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function rdp_access_badge($reason):string{
    $red=array("no_member","bad_password","member_expired","no_policy","policy_disabled","network_not_allowed",
        "outside_schedule","target_not_linked","target_disabled","license_exceeded","mfa_bad_code","mfa_locked",
        "mfa_unavailable","pattern_kill","ssh_hostkey_mismatch","ssh_engine_missing","ssh_file_failed",
        "web_login_unavailable","web_netns_unavailable","web_proxy_unavailable","tunnel_agent_offline",
        "web_engine_outdated","web_pac_unavailable","target_connection_failed","credentials_missing",
        "ad_no_group","ad_account_refused","ad_unreachable","nla_untrusted","nla_realm_unknown",
        "decoy_target","web_file_failed");
    $orange=array("pattern_notify","pattern_refuse","mfa_timeout","mfa_cancelled","charter_refused","web_login_failed","target_credentials_cancelled");
    // web_* (cibles WEB, authhook 1.7.0) : trace d'activite, sauf web_blocked et web_engine_missing
    // web_login (connexion injectée) = trace ; web_login_failed / web_login_unavailable = alerte
    $info=array("ssh_file_up","ssh_file_down","charter_accepted","web_visit","web_popup","web_copy","web_paste","web_login","web_file_down");
    $cls="label";
    if(in_array($reason,$red,true)){$cls="label label-danger";}
    elseif(in_array($reason,$orange,true)){$cls="label label-warning";}
    elseif(in_array($reason,$info,true)){$cls="label label-info";}
    if(in_array($reason,rdp_access_reasons(),true)){$txt="{rdpproxy_reason_$reason}";}
    else{$txt=htmlspecialchars((string)$reason,ENT_QUOTES);}
    return "<span class='$cls'>$txt</span>";
}

function rdp_esc_user($v):string{
    // Defense en profondeur (revue finale du lot 3, I1) : retire les caracteres qu'un nom de
    // fichier ou de dossier ne peut pas porter cote bastion (sanitize_component, R9) avant
    // l'echappement. Un sujet ou une raison qui en porterait (cible hostile, ancien authhook)
    // s'afficherait sinon inverse ou masque dans la ligne d'audit.
    $v=rdp_strip_invisible((string)$v);
    // Echappe puis neutralise les accolades : _ENGINE_parse_body remplacerait un {jeton} saisi par un utilisateur.
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($v,ENT_QUOTES));
}

// rdp_strip_invisible : memes classes que la regle des noms du module SSH (ReDemPtion, patch
// 0005, ssh_hidden_or_bidi + sanitize_component) : controles C1, Bidi_Control (dont U+061C),
// Default_Ignorable_Code_Point (U+00AD, U+034F, U+115F-1160, U+17B4-17B5, U+180B-180F,
// U+200B-200F, U+202A-202E, U+2060-206F, U+3164, U+FE00-FE0F, U+FEFF, U+FFA0, U+1BCA0-1BCA3,
// U+1D173-1D17A, U+E0000-E0FFF), U+FFF0-FFF8, U+2028 et U+2029. UTF-8 invalide : texte rendu
// tel quel (htmlspecialchars le refusera ensuite, comme avant).
function rdp_strip_invisible(string $s):string{
    if($s===""){return "";}
    $r=@preg_replace('/[\x{80}-\x{9F}\x{AD}\x{34F}\x{61C}\x{115F}\x{1160}\x{17B4}\x{17B5}\x{180B}-\x{180F}\x{200B}-\x{200F}\x{2028}-\x{202E}\x{2060}-\x{206F}\x{3164}\x{FE00}-\x{FE0F}\x{FEFF}\x{FFA0}\x{FFF0}-\x{FFF8}\x{1BCA0}-\x{1BCA3}\x{1D173}-\x{1D17A}\x{E0000}-\x{E0FFF}]/u','',$s);
    return is_string($r)?$r:$s;
}

// rdp_event_safe : meme transformation qu'authhook (RDPValidator eventSafe) sur l'alias d'une
// cible avant de l'ecrire dans un evenement, pour retrouver la cible depuis le sujet.
function rdp_event_safe($s):string{
    $s=preg_replace('/[\x00-\x1F\x7F]/',' ',(string)$s);
    return strtr($s,array("<"=>"\u{2039}",">"=>"\u{203A}","\""=>"'","'"=>"\u{2019}","&"=>"+","{"=>"(","}"=>")","\\"=>"/"));
}

// rdp_hostkey_target : cible d'un refus ssh_hostkey_mismatch. Le sujet vaut
// « <alias ou target N> | <empreintes> » (authhook ecrit « target N » quand la cible n'a pas
// d'alias). Alias exact d'abord ; « target N » seulement si aucune cible SSH ne porte ce nom
// (une cible nommee « target 5 » n'est pas la cible n° 5) ; 0 si ambigu ou introuvable.
function rdp_hostkey_target($subject,&$targets):int{
    $p=strpos((string)$subject," | ");
    $who=trim($p===false?(string)$subject:substr((string)$subject,0,$p));
    if($targets===null){
        $targets=array();
        $terr="";
        foreach(rdpproxy_targets($terr) as $t){
            if(!is_array($t)||strtoupper(trim((string)(isset($t["proto_dest"])?$t["proto_dest"]:"")))!=="SSH"){continue;}
            $targets[]=array(intval(isset($t["id"])?$t["id"]:0),rdp_event_safe(isset($t["alias"])?$t["alias"]:""));
        }
    }
    $found=0;
    $n=0;
    foreach($targets as $t){
        if($t[1]!=="" && $t[1]===$who){$n++;$found=$t[0];}
    }
    if($n==1){return $found;}
    if($n>1){return 0;}
    if(preg_match('/^target ([0-9]+)$/',$who,$m)){
        foreach($targets as $t){if($t[0]===intval($m[1])){return $t[0];}}
    }
    return 0;
}

function ACCESS_EVENTS_TABLE(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return;}
    // Liste blanche avant l'appel : rien de brut ne part vers le daemon.
    $user=isset($_GET["user"])?mb_substr(preg_replace('/[^\p{L}\p{N} _.@\\\\-]/u','',(string)$_GET["user"]),0,128):"";
    $reason=isset($_GET["reason"])?(string)$_GET["reason"]:"";
    if($reason!=="" && !in_array($reason,rdp_access_reasons(),true)){$reason="";}
    $limit=isset($_GET["limit"])?intval($_GET["limit"]):100;
    if(!in_array($limit,array(100,250,500),true)){$limit=100;}

    $target=isset($_GET["target"])?intval($_GET["target"]):0;
    if($target<0){$target=0;}
    $kind=(isset($_GET["kind"]) && $_GET["kind"]==="sessions")?"sessions":"";
    $error="";
    $events=rdpproxy_access_events($user,$reason,$limit,$error,$target,$kind);
    if($error!=""){echo $tpl->div_error(htmlspecialchars($error,ENT_QUOTES));return;}
    if(!is_array($events)){$events=array();}
    // Filtre aussi appliqué ici : un démon qui ignorerait target/kind (binaire plus ancien que la
    // console) afficherait tout sous un filtre choisi (RWE 2026-11-03). Jamais plus large que le démon.
    if($target>0 || $kind==="sessions"){
        $events=array_values(array_filter($events,function($e) use($target,$kind){
            if(!is_array($e)){return false;}
            if($target>0 && intval($e["target_id"] ?? 0)!==$target){return false;}
            if($kind==="sessions" && !preg_match('/^(Open|Close|KILL) \S+ session$/',(string)($e["subject"] ?? ""))){return false;}
            return true;
        }));
    }

    $perr="";
    $names=array();
    foreach (rdpproxy_policies($perr) as $p){
        if(!isset($p["id"])){continue;}
        $names[intval($p["id"])]=isset($p["name"])?(string)$p["name"]:"";
    }

    $canEdit=$tpl->IsSecurity("AllowAddUsers");
    $sshTargets=null;
    $t=md5(microtime(true).count($events));
    $h=array();
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    // Largeurs fixes : la table gardait la largeur de son contenu et changeait de forme selon le filtre
    // (toutes les cibles, une cible, historique) (RWE 2026-11-03). Texte long : retour à la ligne.
    $h[]="<table id='table-$t' class='awc-tbl awc-tbl-compact' style='table-layout:fixed;width:100%'>";
    $h[]="<style>#table-$t .label{white-space:normal;display:inline-block;text-align:left}#table-$t .label:empty{display:none}</style>";
    $h[]="<colgroup><col style='width:11%'><col style='width:15%'><col style='width:9%'><col style='width:12%'><col style='width:13%'><col style='width:10%'><col style='width:30%'></colgroup>";
    $h[]="<thead><tr><th>{time}</th><th>{username}</th><th>{ipaddr}</th><th>{target}</th><th>{reason}</th><th>{policy}</th><th>{events}</th></tr></thead>";
    $h[]="<tbody id='rows-$t'>";
    if(count($events)==0){
        $h[]="<tr><td colspan='7'><div class='awc-empty'>{no_data}</div></td></tr>";
    }
    foreach ($events as $e){
        if(!is_array($e)){continue;}
        $time=isset($e["time"])?intval($e["time"]):0;
        $when=$time>0?$tpl->time_to_date($time,true):"-";
        $u=rdp_esc_user(isset($e["username"])?$e["username"]:"");
        $ip=rdp_esc_user(isset($e["client_ip"])?$e["client_ip"]:"");
        $pid=isset($e["policy_id"])?intval($e["policy_id"]):0;
        $pol="&ndash;";
        if($pid>0){
            $pol=rdp_esc_user(isset($names[$pid])&&$names[$pid]!==""?$names[$pid]:"#$pid");
        }
        // cible de l'événement (authhook >= 1.9.7) : alias actuel, « #id » si elle a été supprimée
        $tid=isset($e["target_id"])?intval($e["target_id"]):0;
        $tname=isset($e["target"])?(string)$e["target"]:"";
        $tgt="&ndash;";
        // texte seul : un lien vers la fiche de la cible exposait à une modification par erreur
        // depuis la page des événements (demande RWE 2026-11-03)
        if($tid>0){$tgt=rdp_esc_user($tname!==""?$tname:"#$tid");}
        $rawSubject=isset($e["subject"])?(string)$e["subject"]:"";
        $subject=rdp_esc_user($rawSubject);
        $reasonCode=isset($e["reason"])?(string)$e["reason"]:"";
        $badge=rdp_access_badge($reasonCode);
        // Cle d'hote changee : ouvrir la fiche de la cible, qui montre l'empreinte memorisee et sa
        // date et porte « Oublier la cle » avec sa confirmation. Pas de raccourci d'oubli ici :
        // remettre la confiance a zero sans voir ce qui est oublie se ferait a l'aveugle.
        if($reasonCode==="ssh_hostkey_mismatch" && $canEdit){
            $tid=rdp_hostkey_target($rawSubject,$sshTargets);
            if($tid>0){
                $subject.="<div style='margin-top:4px'><a href=\"javascript:blur();\" onclick=\"Loadjs('fw.rdpproxy.connections.php?target-js=$tid')\"><i class='fas fa-pen'></i> {rdpproxy_edit_target}</a></div>";
            }
        }
        $h[]="<tr><td style='white-space:nowrap'>$when</td><td style='overflow-wrap:anywhere'>$u</td><td style='overflow-wrap:anywhere'>$ip</td><td style='overflow-wrap:anywhere'>$tgt</td><td>$badge</td><td style='overflow-wrap:anywhere'>$pol</td><td style='overflow-wrap:anywhere'>$subject</td></tr>";
    }
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'><span>".count($events)." {events}</span><span class='awc-pg' id='pager-$t'></span></div>";
    $h[]="</div></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))
        ."<script>NoSpinner();</script>".awc_pager_js(50,"rows-$t","pager-$t");
}
