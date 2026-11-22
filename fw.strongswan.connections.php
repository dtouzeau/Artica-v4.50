<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style()/awc_pager_js(): la charte des tableaux d'entites. Include
// INCONDITIONNEL et de premier niveau -- glisse dans le bootstrap
// if(!class_exists("sockets")) ci-dessus il ne s'executerait JAMAIS
// (class.template-admin.inc a deja charge class.sockets.inc) et la page
// mourrait sur "Call to undefined function awc_table_style()", c'est-a-dire un
// chargeur qui ne s'arrete plus. // Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["delete-js"])){delete_rule_js();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_REQUEST["GenerateProgress"])){GenerateProgress();exit;}
if(isset($_POST["connection_name"])){buildconfig();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    if(!isset($_SESSION["STRONGSWAN_SEARCH2"])){$_SESSION["STRONGSWAN_SEARCH2"]="today max 500 everything";}
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{APP_STRONGSWAN} ({connections})</h1><p>$btn</p></div>
	</div>
	<div class=\"row\">
	<div class='ibox-content'>
	<div class=\"input-group\">
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["STRONGSWAN_SEARCH2"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	<script>
	$.address.state('/');
	$.address.value('/ipsec-connections');	
function Search$t(e){
	if(!checkEnter(e) ){return;}
	ss$t();
}

function ss$t(){
	var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
	LoadAjax('table-loader','$page?search='+ss);
}

function Start$t(){
	var ss=document.getElementById('search-this-$t').value;
	ss$t();
}
Start$t();
</script>";

    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Connections',$html);echo $tpl->build_firewall();return;}
echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$vpn=new strongswan();
	$nic=new networking();
	$sock=new sockets();

	$_SESSION["STRONGSWAN_SEARCH2"]=$_GET["search"];
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));

	$q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
	$count=$q->mysqli_fetch_array("SELECT COUNT(*) as count FROM strongswan_cnx");
	if(strlen($search["Q"])<2){$search["Q"]="";}
	$sql="SELECT * FROM strongswan_cnx {$search["Q"]} ORDER BY id DESC LIMIT {$search["MAX"]}";
	$results=$q->QUERY_SQL($sql);
	$Items=intval($count['count']);

	if(!$q->ok){
		// Ni la requete SQL ni son texte d'erreur ne partent au navigateur: la
		// premiere decrit le schema, le second nomme des colonnes. Et pas de
		// balise <code>, qui entre en conflit avec le theme (CLAUDE.md).
		if(function_exists("writelogs")){writelogs("strongswan_cnx: $q->mysql_error [$sql]",__FUNCTION__,__FILE__,__LINE__);}
		echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
		return;
	}

	// L'evenement porte le sens de la ligne: on le rend en pastille d'etat, pas
	// en texte brut. "up" = liaison etablie, "down" = liaison rompue; tout le
	// reste reste neutre plutot que d'etre range de force dans l'un des deux.
	$icons=array(
		"host-up"=>"fa-link","host-ipv6-up"=>"fa-link","host-down"=>"fa-unlink","host-ipv6-down"=>"fa-unlink",
		"client-up"=>"fa-link","client-ipv6-up"=>"fa-link","client-down"=>"fa-unlink","client-ipv6-down"=>"fa-unlink");

	$rows=array();
	$tpl2=new templates();
	foreach($results as $ligne){
		// zdate est declaree INTEGER au schema mais SQLite est faiblement type:
		// selon l'ecrivain, la colonne porte un epoch ou une chaine de date.
		// strtotime("1788573541") renvoie FALSE -- la date affichee serait
		// alors celle du 1er janvier 1970, sans le moindre signal. On traite
		// donc les deux formes, et une valeur inexploitable devient {unknown}
		// plutot qu'une date fausse.
		$rawz=isset($ligne["zdate"])?$ligne["zdate"]:"";
		$ztime=0;
		if(is_numeric($rawz)){
			$ztime=intval($rawz);
		}elseif(trim((string)$rawz)!==""){
			$t=strtotime((string)$rawz);
			if($t!==false){$ztime=$t;}
		}
		$date=($ztime>0)?$tpl2->time_to_date($ztime,true):"{unknown}";
		$action=(string)$ligne["action"];
		$up=(strpos($action,"-up")!==false);
		$down=(strpos($action,"-down")!==false);
		$ico=isset($icons[$action])?$icons[$action]:"fa-circle-info";
		$badge=$up ? "awc-badge-on" : ($down ? "awc-badge-off" : "awc-badge-warn");

		// Le membre peut etre nul (connexion non authentifiee ou anterieure a
		// l'association): la ligne reste une ligne, elle est simplement grisee
		// et nommee {unknown} -- jamais une cellule vide, qui se lit comme un
		// defaut de rendu.
		$member=trim((string)$ligne["member"]);
		$named=($member!=="");
		$mtxt=$named?req_html($member):"{unknown}";

		$h ="<tr>";
		// Pentest 2026-11-04 : membre/adresses/journal (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1 (XSS stockee).
		$h.="<td><div class='awc-ident'>";
		$h.="<span class='awc-ico".($named?"":" awc-ico-off")."'><i class='fas ".($named?"fa-user":"fa-user-slash")."'></i></span>";
		$h.="<span><span class='awc-name".($named?"":" awc-name-off")."'>$mtxt</span>";
		// htmlspecialchars n'echappe pas les accolades: le jeton {unknown} de la
		// branche sans date le traverse intact et sera resolu par
		// _ENGINE_parse_body(). Et time_to_date() renvoie du texte UTF-8, pas
		// du HTML -- l'echapper ici est correct (CLAUDE.md).
		$h.="<div class='awc-meta'>".req_html($date)."</div></span></div></td>";
		$h.="<td class='awc-mono'>".req_html((string)$ligne["ipaddr_local"])."</td>";
		$h.="<td class='awc-mono'>".req_html((string)$ligne["ipaddr_vip"])."</td>";
		$h.="<td class='center'><span class='awc-badge $badge'><i class='fas $ico'></i> "
		   .req_html($action)."</span></td>";
		$h.="</tr>";
		$rows[]=$h;
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$html[]="<th>{member}</th>";
	$html[]="<th nowrap>{remote_ip_address}</th>";
	$html[]="<th nowrap>{local_ip_address}</th>";
	$html[]="<th class='center' nowrap>{events}</th>";
	// Identifiants propres a la page: awc_pager_js() pagine le premier <tbody>
	// qu'il trouve quand on ne lui en nomme pas un.
	$html[]="</tr></thead><tbody id='sswc-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} ".count($rows)." / $Items {items}</span>"
	       ."<span class='awc-pg' id='sswc-pager'></span></div>";
	$html[]="</div></div>";

	// Feuille de style et pager concatenes HORS _ENGINE_parse_body: son moteur
	// lirait chaque corps de regle CSS et chaque bloc JS comme un {token}.
	// ICON_SCRIPTS_COMPILE() et non ICON_SCRIPTS: le second est une propriete
	// d'instance, vide des que la ligne est batie par une autre instance.
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>"
		.awc_pager_js(50,"sswc-rows","sswc-pager");
}
