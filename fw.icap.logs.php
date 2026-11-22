<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// Les repartiteurs ?enable-signature= et ?enable-firewall= appelaient
// enable_signature() et enable_firewall(), qui ne sont definies NI dans ce
// fichier NI ailleurs: les deux URL repondaient un fatal « Call to undefined
// function ». La paire appartient a la famille Suricata (elle bascule une
// signature dans suricata-rules.db et un drapeau dans suricata_sig) et n'a
// rien a faire ici; seul fw.ids.rules.php produit reellement
// « ?enable-signature= ».
// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["ICAEVNTS"]==null){$_SESSION["ICAEVNTS"]="limit 200";}
	
	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{icap_events}</h1></div>
	</div>
		

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["ICAEVNTS"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>	
<div class='row'><div id='progress-firehol-restart'></div>";

	$html[]="<div class='ibox-content'>
	<div id='table-loader'></div>
	</div>
	</div>
		
		
		
<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?table=yes&t=$t&search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		$.address.state('/');
	    $.address.value('icap-events');		
		Start$t();
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{icap_events}");
        return;
    }
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl                    = new template_admin();
	$page                   = CurrentPageName();
	$t                      = $_GET["t"];
	$target_file            = PROGRESS_DIR."/icap.log";
    $_SESSION["ICAEVNTS"]   = trim(strtolower($_GET["search"]));
    $search                 = $tpl->query_pattern(trim(strtolower($_GET["search"])));
    $ss                     = base64_encode($search["S"]);
    if(!is_numeric($t)){$t=time();}
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cicap.php?icap-events=$ss&rp={$search["MAX"]}");
	$datas=explode("\n",@file_get_contents($target_file));


	$fields[]="{time}";
    $fields[]="{members}";
    $fields[]="{domain}";
    $fields[]="{service}";
    $fields[]="ICAP";
    $fields[]="{mode}";
    $fields[]="{response}";
    $fields[]="{duration}";

    $html[]=$tpl->table_head($fields,"table-$t");

	$TRCLASS=null;
	krsort($datas);
	$qt='"';

	$fielz[]="service";
    $fielz[]="ip";
    $fielz[]="mode";
    $fielz[]="icap-time";
    $fielz[]="icap-code";
    $fielz[]="mac";
    $fielz[]="client";
    $fielz[]="user";
    $fielz[]="domain";
    $fielz[]="server";
    $fielz[]="icap-command";
    $ztokens=array();
    foreach ($fielz as $token){
        $ztokens[]="$qt$token=(.*?)$qt";

    }
    $pregm="time=$qt([0-9]+)$qt.*?".@implode("\s+",$ztokens);


    if(strpos($datas[0],"tail: cannot open")>0){
        echo $tpl->div_warning("{no_data}");
        return false;
    }

	foreach ($datas as $key=>$line){
        $line       = trim($line);
        if($line==null){continue;}

	    if(!preg_match("#$pregm#",$line,$re)){
	        // Pentest 2026-11-04 : ligne du journal ICAP (donnee externe) affiche sans encodage (XSS stockee).
	        echo "<H1>".req_html($line)." <code>".req_html($pregm)."</code></H1>\n";
	        continue;
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        foreach ($re as $index=>$val){
            $re[$index]=trim($re[$index]);
            if($re[$index]=="-"){$re[$index]=null;}
            if($re[$index]=="00:00:00:00:00:00"){$re[$index]=null;}
        }
        $time=$re[1];
        $service=$re[2];
        $ip=$re[3];
        $mode=$re[4];
        $icap_time=$re[5];
        $icap_code=$re[6];
        $mac=$re[7];
        $client=$re[8];
        $user=$re[9];
        $domain=$re[10];
        $server=$re[11];
        $icap_command=$re[12];
		$text_class=null;
		$date=$tpl->time_to_date($time,true);
		$suers=array();
		if($client<>null){$suers[]=$client;}
        if($user<>null){$suers[]=$user;}
        if($mac<>null){$suers[]=$mac;}

		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\">$date</td>";
        // Pentest 2026-11-04 : client/utilisateur/domaine/serveur du journal ICAP (donnee externe) affiche sans encodage (XSS stockee).
        $html[]="<td class=\"$text_class\">".@implode("&nbsp;/&nbsp;",array_map("req_html",$suers))."</td>";
        $html[]="<td class=\"$text_class\">".req_html($domain)."</td>";
		$html[]="<td class=\"$text_class\">".req_html($ip)."&nbsp;-&nbsp;".req_html($service)."</td>";
		$html[]="<td class=\"$text_class\">".req_html($server)."</td>";
        $html[]="<td class=\"$text_class\">".req_html($mode)." / ".req_html($icap_command)."</td>";
        $html[]="<td class=\"$text_class\">".req_html($icap_code)."</td>";
        $html[]="<td class=\"$text_class\">".req_html($icap_time)."ms</td>";
		$html[]="</tr>";
		

	}

    $html[]=$tpl->table_footer("table-$t",count($fields),false);
    echo $tpl->_ENGINE_parse_body($html);

}
