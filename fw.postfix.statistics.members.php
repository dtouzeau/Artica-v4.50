<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["events-head"])){events_head();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["status-table"])){status_table();exit;}
if(isset($_GET["status-graph"])){status_graph();exit;}
page();




/**
 * Le demon renvoie soit un token i18n nu (ex. postfix.members.error.database, voir membersFail dans
 * postfix/maillog/MembersApi.go), soit de la prose fabriquee par REST_API_POSTFIX lui-meme pour un
 * echec de TRANSPORT ("Error Connecting to the REST API server...", "404 Not found..."). Comme
 * _ENGINE_parse_body ne substitue que du texte de la forme {token}, un token nu s'affichait tel quel
 * a l'ecran. On ne met entre accolades QUE ce qui ressemble a un identifiant de token (pas d'espace,
 * forme point/underscore) -- jamais la prose, qui deviendrait un second charabia si on l'entourait
 * d'accolades a son tour.
 */
function pfxsm_error_text($err){
    $err=(string)($err ?? "{error}");
    if($err!=="" && strpos($err," ")===false && preg_match('/^[A-Za-z][A-Za-z0-9_]*(\.[A-Za-z0-9_]+)*$/',$err)){
        return "{".$err."}";
    }
    return $err;
}

/**
 * container/container2 finissent dans un id de conteneur Highcharts et dans du JavaScript emis
 * directement (LoadAjax('...')) -- deux contextes JS sans le moindre echappement. Ce sont des ids
 * DOM generes par la page elle-meme: on les CONTRAINT plutot que de les echapper, ce qui est plus
 * strict et ne peut pas etre contourne par un encodage astucieux.
 */
function pfxsm_safe_container_id($raw,$fallback){
    $raw=(string)$raw;
    if($raw!=="" && preg_match('/^[A-Za-z0-9_-]+$/',$raw)){
        return $raw;
    }
    return $fallback;
}

function tabs(){
    // Le rapport est calcule a la volee par le demon: plus d'identifiant de table a transporter.
    $email=$_GET["tabs"];
    $emailenc=urlencode($email);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?status=$emailenc";
    $array["{events}"]="$page?events-head=$emailenc";
    echo $tpl->tabs_default($array);
}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];

    $email=$_GET["status"];
    $emailenc=urlencode($email);
    $md5=md5($email);

    $raw=$sock->REST_API_POSTFIX("/postfix/maillog/members/summary?email=".urlencode($email));
    $js=json_decode($raw,true);
    if(!is_array($js) || !isset($js["status"]) || $js["status"]!==true){
        echo $tpl->_ENGINE_parse_body("<div class='awc-wrap'>".pfxsm_error_text($js["Error"] ?? "{error}")."</div>");
        return;
    }

    $row=is_array($js["rows"] ?? null)?$js["rows"]:array();
    $sentN=intval($row["sent"] ?? 0);
    $refusedN=intval($row["refused"] ?? 0);

    $html[]="";
    // Mise en page en flex. Le bloc de titre fermait un </div> qu'il n'avait pas
    // ouvert, et les tuiles etaient figees a 50% chacune.
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:10px;align-items:center'>";
    $html[]="<div style='flex:1 1 320px;min-width:0'>";
    if($sentN==0 && $refusedN==0){
        $html[]="<h1>".htmlspecialchars((string)$email)."</h1><small>{no_activity_in_period}</small>";
    }else{
        $date_start=!empty($row["first_seen"])?$tpl->time_to_date(strtotime($row["first_seen"])):"-";
        $date_to=!empty($row["last_seen"])?$tpl->time_to_date(strtotime($row["last_seen"])):"-";
        $html[]="<h1>{period} $date_start {to} $date_to</h1><small>".htmlspecialchars((string)$email)."</small>";
    }
    $html[]="</div>";
    $html[]="</div>";

    if(!empty($js["window"]["clamped"])){
        $html[]="<div class='alert alert-warning'>{period_clamped}</div>";
    }

    $html[]="<div style='display:flex;flex-wrap:wrap;gap:6px;align-items:flex-start;margin-top:6px'>";
    $html[]="<div style='flex:1 1 300px;min-width:0'>".$tpl->widget_h("red","fa-thumbs-down",FormatNumber($refusedN),"{refused}")."</div>";
    $html[]="<div style='flex:1 1 300px;min-width:0'>".$tpl->widget_h("green","fa-thumbs-up",FormatNumber($sentN),"{sent}")."</div>";
    $html[]="</div>";
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:6px;align-items:flex-start'>";
    $html[]="<div style='flex:1 1 300px;min-width:0'><div id='$md5-2'></div></div>";
    $html[]="<div style='flex:1 1 300px;min-width:0'><div id='$md5-1'></div></div>";
    $html[]="</div>
<script>
    Loadjs('$page?status-graph=yes&email=$emailenc&container=$md5-1&container2=$md5-2');

</script>

";

    echo $tpl->_ENGINE_parse_body($html);
}


function events_head(){
    // Le rapport n'accepte pas de requete libre cote demon: on charge directement le rapport,
    // il n'y a plus de recherche SQL a brancher ici.
    $email=$_GET["events-head"];
    $emailenc=urlencode($email);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $html[]="<div style='margin:10px' id='pfxsm-events-$t'></div>";
    $html[]="<script>LoadAjax('pfxsm-events-$t','$page?search=yes&email=$emailenc');</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function search(){
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $email=$_GET["email"];
    $limit=250;
    $offset=intval($_GET["offset"] ?? 0);

    $raw=$sock->REST_API_POSTFIX("/postfix/maillog/members/report?email=".urlencode($email)."&limit=$limit&offset=$offset");
    $js=json_decode($raw,true);
    if(!is_array($js) || !isset($js["status"]) || $js["status"]!==true){
        echo $tpl->_ENGINE_parse_body("<div class='awc-wrap'>".pfxsm_error_text($js["Error"] ?? "{error}")."</div>");
        return;
    }

    $window=is_array($js["window"] ?? null)?$js["window"]:array();
    $days=intval($window["days"] ?? 0);
    $rows=is_array($js["rows"] ?? null)?$js["rows"]:array();

    $head[]="<div class='awc-wrap'>";
    $head[]="<div style='margin-bottom:8px'>".sprintf($tpl->_ENGINE_parse_body("{showing_last_days}"),$days)."</div>";
    if(!empty($window["clamped"])){
        $head[]="<div class='alert alert-warning'>{period_clamped}</div>";
    }

    if(count($rows)==0){
        $head[]="<div>{no_activity_in_period}</div></div>";
        echo $tpl->_ENGINE_parse_body(@implode("\n",$head));
        return;
    }

    $html=$head;
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{correspondent}</th>";
    $html[]="<th>{direction}</th>";
    $html[]="<th class='center'>{messages}</th>";
    $html[]="<th>{time}</th>";
    $html[]="</tr></thead><tbody id='pfxsm-rows'>";

    foreach($rows as $ligne){
        $md=md5(serialize($ligne));
        $correspondent=$ligne["correspondent"] ?? "";
        $direction=(($ligne["direction"] ?? "")==="in")?"{inbound}":"{outbound}";
        $reason=$ligne["reason"] ?? "";
        $hits=FormatNumber($ligne["hits"] ?? 0);
        $refused=intval($ligne["refused"] ?? 0);
        $sent=intval($ligne["sent"] ?? 0);
        $zdate=$tpl->time_to_date(strtotime($ligne["hour"] ?? ""),true);

        $html[]=pfxsm_event_row($md,$correspondent,$direction,$reason,$hits,$zdate,$refused,$sent);
    }

    $html[]="</tbody></table></div>";
    if(!empty($js["truncated"])){
        $html[]="<div class='alert alert-warning' style='margin-top:8px'>{results_truncated}</div>";
    }
    $html[]="<div class='awc-foot'>";
    $html[]="<span class='awc-pg' id='pfxsm-pager'></span>";
    $html[]="</div></div>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .awc_pager_js(100,"pfxsm-rows","pfxsm-pager");

}

/**
 * Une seule requete demon pour /members/reasons, partagee par status_graph() (le camembert) et
 * status_table() (le tableau) -- avant, la meme requete SQL etait dupliquee dans les deux fonctions.
 * $window est renseignee par reference avec la fenetre effectivement calculee.
 */
function memberReasonsRows($email,&$window){
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $raw=$sock->REST_API_POSTFIX("/postfix/maillog/members/reasons?email=".urlencode($email));
    $js=json_decode($raw,true);
    if(!is_array($js) || !isset($js["status"]) || $js["status"]!==true){
        return array("error"=>($js["Error"] ?? "{error}"));
    }
    $window=is_array($js["window"] ?? null)?$js["window"]:array();
    return array("rows"=>is_array($js["rows"] ?? null)?$js["rows"]:array());
}

function status_graph(){


    $tpl=new template_admin();
    $page=CurrentPageName();
    $email=$_GET["email"];
    $emailenc=urlencode($email);

    $window=array();
    $result=memberReasonsRows($email,$window);
    if(isset($result["error"])){
        echo $tpl->_ENGINE_parse_body("<div class='awc-wrap'>".pfxsm_error_text($result["error"])."</div>");
        return;
    }

    $PieData=array();
    foreach($result["rows"] as $ligne){
        $reason=(string)($ligne["reason"] ?? "");
        if($GLOBALS["VERBOSE"]){echo $reason."\t".$ligne["total"]."\n";}

        if(preg_match("#SecuriteInfo\.com\.(.*?)\.UNOFFICIAL#",$reason,$re)){
            $reason=$re[1];
        }

        $PieData[$reason]=intval($ligne["total"] ?? 0);
    }


    // container/container2 sont des ids de conteneur DOM generes par la page (status()); recus ici
    // via $_GET puisque cette route peut etre appelee directement. Ils finissent l'un dans un id
    // Highcharts, l'autre interpole tel quel dans un LoadAjax('...') EMIS en JavaScript -- deux
    // contextes sans le moindre echappement. On les CONTRAINT a un id sur plutot que de les
    // echapper (plus strict, non contournable par un encodage).
    $container=pfxsm_safe_container_id($_GET["container"] ?? "","pfxsm-chart-".md5($email));
    $container2=pfxsm_safe_container_id($_GET["container2"] ?? "","pfxsm-table-".md5($email));

    $highcharts=new highcharts();
    $highcharts->container=$container;
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle=$tpl->_ENGINE_parse_body("{top} {refused}");
    $highcharts->Title=$tpl->_ENGINE_parse_body("{top} {refused}/{description}");
    echo $highcharts->BuildChart();
    echo "\nLoadAjax('$container2','$page?status-table=yes&email=$emailenc')";



}

function status_table(){
    $tpl=new template_admin();
    $email=$_GET["email"];

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{reason}</th>";
    $html[]="<th style='text-align:right'>{hits}</th>";
    $html[]="</tr></thead><tbody>";

    $window=array();
    $result=memberReasonsRows($email,$window);
    if(isset($result["error"])){
        $html[]="</tbody></table></div></div></div>";
        echo awc_table_style().$tpl->_ENGINE_parse_body($html);
        echo $tpl->_ENGINE_parse_body("<div class='awc-wrap'>".pfxsm_error_text($result["error"])."</div>");
        return;
    }

    foreach($result["rows"] as $ligne){
        if($GLOBALS["VERBOSE"]){echo ($ligne["reason"] ?? "")."\t".($ligne["total"] ?? "")."\n";}
        $hits=$ligne["total"] ?? 0;
        $reason=(string)($ligne["reason"] ?? "");
        $reason_text=$reason;
        if(preg_match("#SecuriteInfo\.com\.(.*?)\.UNOFFICIAL#",$reason_text,$re)){
            $reason_text=$re[1];
        }

        $html[]=pfxsm_reason_row($tpl,$reason_text,FormatNumber($hits));
    }
    $html[]="</tbody></table></div></div></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body($html);
}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 : t recopie dans des ids et du JS : entier seulement.
	if(!isset($_GET["t"])){$t=time();}else{$t=intval($_GET["t"]);}
	// Pentest 2026-11-04 : recherche memorisee en session recopiee sans encodage dans value="" (XSS stockee).
	if($_SESSION["MEMBERS_SEARCH"]==null){$_SESSION["MEMBERS_SEARCH"]="";}

	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{my_members}</h1></div>
	</div>


	<div class=\"row\">
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"".req_html($_SESSION["MEMBERS_SEARCH"])."\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:TableLoaderMyMemberearch();\">{go}!</button>
      	</span>
     </div>
    </div>
</div>




	<div class='row'><div id='progress-firehol-restart'></div>";

	$html[]="<div class='ibox-content'>

	<div id='table-loader-my-members'></div>

	</div>
	</div>



<script>
	$.address.state('/');
	$.address.value('smtp-members-statistics');
	$.address.title('SMTP: {members_stats}');
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			TableLoaderMyMemberearch();
		}

		function TableLoaderMyMemberearch(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader-my-members','$page?table=yes&t=$t&search='+ss+'&function=TableLoaderMyMemberearch');
		}

		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			TableLoaderMyMemberearch();
		}
		Start$t();
	</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("SMTP: {members_stats}",$html);
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=$GLOBALS["CLASS_SOCKETS"];

	$stringtofind=url_decode_special_tool($_GET["search"]);
	$stringtofind=str_replace("*","",$stringtofind);   // le demon fait lui-meme la recherche par sous-chaine

	$raw=$sock->REST_API_POSTFIX("/postfix/maillog/members?search=".urlencode($stringtofind)."&limit=250");
	$js=json_decode($raw,true);
	if(!is_array($js) || !isset($js["status"]) || $js["status"]!==true){
		echo $tpl->_ENGINE_parse_body("<div class='awc-wrap'>".pfxsm_error_text($js["Error"] ?? "{error}")."</div>");
		return;
	}

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{email}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='pfxsmm-rows'>";

	foreach($js["rows"] as $ligne){
		$tomail=$ligne["address"];
		$tomailenc=urlencode($tomail);
		// Sans identifiant de table, et sans icone de suppression: plus rien n'est materialise.
		$jsclick="Loadjs('$page?tabs=$tomailenc')";
		$html[]=pfxsm_member_row($tpl,$tomail,$jsclick,$tpl->icon_nothing());
	}

	$html[]="</tbody></table></div>";
	if(!empty($js["truncated"])){
		// La requete demande toujours limit=250: sur un relais a 50000 membres la page montrait
		// les 250 premiers par ordre alphabetique et AVAIT L'AIR complete -- seule la recherche
		// permettait de deviner qu'il en manquait. On ne construit pas de pagination ici (portee
		// plus large, non demandee) : on se contente de dire la limite au lieu de la taire.
		$html[]="<div class='alert alert-warning' style='margin-top:8px'>{results_truncated}</div>";
	}
	$html[]="<div class='awc-foot'><span class='awc-pg' id='pfxsmm-pager'></span></div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.awc_pager_js(50,"pfxsmm-rows","pfxsmm-pager");

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

/**
 * Un evenement du rapport: correspondant + direction en identite, motif en sous-titre. Le motif
 * occupait une colonne a 99% marquee nowrap -- il debordait au lieu de se replier.
 * Correspondant et motif viennent du rapport du demon et sont deja normalises cote Go.
 */
function pfxsm_event_row($md,$correspondent,$direction,$reason,$hits,$zdate,$refused,$sent):string{
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='".($refused==1?"awc-ico awc-ico-off":"awc-ico")."'><i class='fas fa-envelope'></i></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars((string)$correspondent)."</span>";
    if(trim((string)$reason)!==""){$h.="<div class='awc-meta'>".htmlspecialchars((string)$reason)."</div>";}
    $h.="</span></div></td>";
    $h.="<td>".$direction."</td>";
    $h.="<td class='center'>".($refused==1
        ?"<span class='awc-badge' style='background:#ba1a1a'>$hits</span>"
        :"<span class='awc-seen'>$hits</span>")."</td>";
    $h.="<td class='awc-mono'>".htmlspecialchars((string)$zdate)."</td>";
    $h.="</tr>";
    return $h;
}

/** Un motif de refus du rapport, avec son nombre d'occurrences. */
function pfxsm_reason_row($tpl,$reason_text,$hits):string{
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico awc-ico-off'><i class='fas fa-circle-xmark'></i></span>";
    $h.="<span><span class='awc-name'>".$tpl->td_href(htmlspecialchars((string)$reason_text),"{see_messages_with}","blur()")."</span></span>";
    $h.="</div></td>";
    $h.="<td style='text-align:right'><span class='awc-seen'>$hits</span></td>";
    $h.="</tr>";
    return $h;
}

/** Un destinataire suivi: son adresse ouvre son rapport. */
function pfxsm_member_row($tpl,$tomail,$js,$delete):string{
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-users'></i></span>";
    $h.="<span><span class='awc-name'>".$tpl->td_href(htmlspecialchars((string)$tomail),"{click_to_edit}",$js)."</span></span>";
    $h.="</div></td>";
    $h.="<td><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}
