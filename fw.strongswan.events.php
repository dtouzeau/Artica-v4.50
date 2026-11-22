<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Include INCONDITIONNEL de premier niveau: dans le bootstrap
// if(!class_exists("sockets")) il ne s'executerait jamais et la page mourrait
// sur "Call to undefined function awc_table_style()" (CLAUDE.md, regle 1). // Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}

page();




function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["STRONGSWAN_SEARCH"])){$_SESSION["STRONGSWAN_SEARCH"]="today max 500 everything";}
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{APP_STRONGSWAN} ({events})</h1><p>$btn</p></div>
	</div>
	<div class=\"row\">
	<div class='ibox-content'>
	<div class=\"input-group\">
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["STRONGSWAN_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	$.address.value('/ipsec-events');	
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
	if(ss.length >0){ss$t();}
}
Start$t();
</script>";
	if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Events',$html);echo $tpl->build_firewall();return;}
echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$vpn=new strongswan();
	$nic=new networking();
	$sock=new sockets();
	$page=CurrentPageName();
	
	$date=$tpl->javascript_parse_text("{connection_date}");
	$deamon=$tpl->javascript_parse_text("{daemon}");;
	$events=$tpl->javascript_parse_text("{events}");
	

	$_SESSION["STRONGSWAN_SEARCH"]=$_GET["search"];
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$end="";
	foreach ($MAIN as $val=>$key){ $end.=" $val=$key,"; }
	reset($MAIN);

	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("strongswan.php?syslog=$line");
	$data=explode("\n",@file_get_contents(PROGRESS_DIR."/charon.syslog"));
	ksort($data);

	$tpl2=new templates();
	$rows=array();
	foreach ($data as $line){
		$line=trim($line);
		if($line==null){continue;}
		$line=str_replace("RwFri","",$line);
		if(!preg_match("#([a-zA-Z]+)\s+([a-zA-Z]+)\s+([0-9]+)\s+([0-9:]+)\s+([0-9]+)\s+us=[0-9]+\s+(.+)#", $line,$re)){
			if(preg_match("#([a-zA-Z]+)\s+([a-zA-Z]+)\s+([0-9]+)\s+([0-9:]+)\s+([0-9]+)\s+(.+)#", $line,$re)){
			}else{continue;}
		}
		$zDate="{$re[1]} {$re[2]} {$re[3]} {$re[4]} {$re[5]}";
		$ztime=strtotime($zDate);
		$date=($ztime!==false && $ztime>0)?$tpl2->time_to_date($ztime,true):"{unknown}";

		// explode(' ',$line,2) ne garantit PAS deux elements: une ligne sans
		// espace laisse $msg[1] indefini, ce qui est une notice en PHP 7 mais
		// se lit comme une cellule vide dans les deux cas. On nomme donc le
		// sous-systeme et le message separement, avec un repli explicite.
		$msg=explode(' ',$re[6],2);
		$daemon=isset($msg[0])?$msg[0]:"";
		$text=isset($msg[1])?$msg[1]:"";
		if($text===""){ $text=$daemon; $daemon=""; }

		// La gravite se lit sur le message, comme avant -- mais elle devient
		// une pastille d'etat plutot qu'une couleur de ligne: une ligne rouge
		// sur fond blanc se confond avec un lien visite, un badge non.
		$low=strtolower($text);
		$bad=(strpos($low,'failed')!==false || strpos($low,'fatal')!==false || strpos($low,'error')!==false);

		$h ="<tr>";
		// Pentest 2026-11-04 : membre/adresses/journal (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1 (XSS stockee).
		$h.="<td><div class='awc-ident'>";
		$h.="<span class='awc-ico".($bad?"":" awc-ico-off")."'><i class='fas ".($bad?"fa-triangle-exclamation":"fa-gears")."'></i></span>";
		$h.="<span><span class='awc-name'>".($daemon!==""?req_html($daemon):"charon")."</span>";
		$h.="<div class='awc-meta'>".req_html($date)."</div></span></div></td>";
		$h.="<td>".req_html($text)."</td>";
		$h.="<td class='center'>".($bad?"<span class='awc-badge awc-badge-alert'>{error}</span>":"<span class='awc-none'>&mdash;</span>")."</td>";
		$h.="</tr>";
		$rows[]=$h;
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$html[]="<th nowrap>{daemon}</th>";
	$html[]="<th>{events}</th>";
	$html[]="<th class='center' nowrap>{status}</th>";
	$html[]="</tr></thead><tbody id='sswe-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} ".count($rows)." {items}</span>"
	       ."<span class='awc-pg' id='sswe-pager'></span></div>";
	$html[]="</div></div>";

	// CSS et pager concatenes HORS _ENGINE_parse_body (ses accolades seraient
	// lues comme des {token}); ICON_SCRIPTS_COMPILE() et non ICON_SCRIPTS.
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>"
		.awc_pager_js(50,"sswe-rows","sswe-pager");
}
