<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
// Les repartiteurs ?zoom-js=, ?button= et ?whitelist-js= visaient zoom_js(),
// button_white() et whitelist_js(): AUCUNE de ces trois fonctions n'existe
// dans ce fichier, les trois URL repondaient un fatal « Call to undefined
// function ». Aucun appelant ne les utilisait.


page();

function ifisright(){
    $users=new usersMenus();
    if($users->AsProxyMonitor){return true;}
    if($users->AsWebStatisticsAdministrator){return true;}
    if($users->AsDnsAdministrator){return true;}
    if($users->AsFirewallManager){return true;}
}
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    if(!isset($_SESSION["WEBF_SEARCH"])){$_SESSION["WEBF_SEARCH"]="50 events";}
    // La valeur de session partait BRUTE dans un attribut value="...": elle est
    // ecrite par d'autres pages, elle est echappee ici.
    $WEBF_SEARCH=htmlspecialchars((string)$_SESSION["WEBF_SEARCH"],ENT_QUOTES);
    $addPLUS="";
    if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}

    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{DNS_QUERIES}</h1></div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"$WEBF_SEARCH\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row' id='spinner'>
		<div id='progress-firehol-restart'></div>
		<div  class='ibox-content'>
		<div class='sk-spinner sk-spinner-wave'>
			<div class='sk-rect1'></div>
			<div class='sk-rect2'></div>
			<div class='sk-rect3'></div>
			<div class='sk-rect4'></div>
			<div class='sk-rect5'></div>
		</div>
		
		
			<div id='table-loader'></div>
		</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/dns-queries');
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss+'$addPLUS');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}


// preg_split() ne garantit pas la presence d'un index: la ligne de log est de
// longueur variable et le code lisait $TR[7] a $TR[12] sans jamais verifier.
function dnsfq_tr($TR,$i):string{
    return isset($TR[$i])?(string)$TR[$i]:"";
}
function dnsfq_join($TR,$from):string{
    $out=array();
    for($i=$from;$i<count($TR);$i++){$out[]=$TR[$i];}
    return trim(@implode(" ",$out));
}

// --- Cellules du tableau: une cellule = une fonction ------------------------
// Chaque <td> portait DEUX attributs style ("color" puis "width"): le second
// etait purement et simplement ignore par le navigateur, la largeur n'a jamais
// ete appliquee. Et un </span> orphelin fermait un <span> jamais ouvert.
function dnsfq_cell($color,$html,$narrow=true):string{
    $st="color:$color";
    if($narrow){$st.=";width:1%";}
    $nw=$narrow?" nowrap":"";
    return "<td style='$st'$nw>$html</td>";
}

// L'annotation de regle du pare-feu DNS: pays / AS / FAI, puis l'action et le
// nom de la regle. Construite a part, deja echappee.
// Pentest 2026-11-04 : requetes DNS, IP clientes, FAI (donnee externe) : htmlspecialchars UTF-8 rendait "" sur du Latin-1, remplace par req_html (XSS stockee).
function dnsfq_annotation($TR,$COUNTRIES,$FWRULES,&$strong,&$color):string{
    if(!isset($TR[11])){return "";}
    $red="rgb(237, 85, 101)";
    $action=null;$ruleid=null;$direction=null;
    $fpattern=dnsfq_tr($TR,11)." ".dnsfq_tr($TR,12);
    if(preg_match("#\[([A-Z]+):([0-9]+)\]:(.+)#",$fpattern,$re)){
        $strong=false;
        $action=$re[1];
        $ruleid=intval($re[2]);
        $direction=$re[3];
    }

    $tb=explode(";",dnsfq_tr($TR,11));
    $cc=isset($tb[0])?$tb[0]:"";
    $AS=isset($tb[1])?$tb[1]:"-";
    $ISP=isset($tb[2])?$tb[2]:"-";
    $COUNTRY=isset($COUNTRIES[$cc])?$COUNTRIES[$cc]:null;

    $sst=array();
    if($COUNTRY<>null){$sst[]=$COUNTRY;}
    if($AS<>"-" && $AS<>""){$sst[]="AS$AS";}
    if($ISP<>"-" && $ISP<>""){$sst[]=$ISP;}

    $h="";
    if(count($sst)>0){
        $h.=" <strong>(".req_html(@implode(" / ",$sst)).")</strong>";
    }
    if($direction<>null){
        $h.=" ".req_html($direction);
    }
    if($action<>null){
        $rulename=isset($FWRULES[$ruleid])?(string)$FWRULES[$ruleid]:"";
        $h.=" <strong style='color:$red'>[".req_html($action)."] ".req_html($rulename)."</strong>";
    }
    return $h;
}

function dnsfq_rules():array{
    $FWRULES=array();
    $rows = acls_api_get("/dnsfw/acls") ?? [];
    if(!is_array($rows)){return $FWRULES;}
    foreach ($rows as $ligne){
        $FWRULES[intval($ligne["id"] ?? 0)]=$ligne["ruleName"] ?? "";
    }
    return $FWRULES;
}

function search(){

    $tpl=new template_admin();
    $GLOBALS["TPLZ"]=$tpl;

    $MAIN=$tpl->format_search_protocol(isset($_GET["search"])?(string)$_GET["search"]:"");
    // articarest cherche dans /var/log/dns-firewall.log (identifiant "dns-firewall"
    // de /system/logfile/search, motif passe a grep sans shell) et rend les lignes
    // les plus recentes en premier : plus de krsort(). Remplace
    // framework/unbound.php?fw-requests, qui montait un grep en root.
    $data=$tpl->logfile_search("dns-firewall",$MAIN,false);

    $ipaddr=$tpl->javascript_parse_text("{ipaddr}");
    $zdate=$tpl->_ENGINE_parse_body("{zDate}");

    $COUNTRIES=GEO_IP_COUNTRIES_LIST();
    if(!is_array($COUNTRIES)){$COUNTRIES=array();}
    $FWRULES=dnsfq_rules();

    $rows=array();
    foreach ($data as $line){
        $TR=preg_split("/[\s]+/", $line);
        if(!is_array($TR)){continue;}
        if(count($TR)<5){continue;}

        $md=md5($line);
        $color="rgb(103, 106, 108);";
        $red="rgb(237, 85, 101)";
        $strong=true;
        $fullconcat=false;
        $mono=false;

        $date=$tpl->time_to_date(strtotime(dnsfq_tr($TR,0)." ".dnsfq_tr($TR,1)." ".dnsfq_tr($TR,2)),true);
        if(dnsfq_tr($TR,0)=='TypeError:' || dnsfq_tr($TR,0)=='NameError:'){$fullconcat=true;}

        // $query reste du TEXTE BRUT tout au long de la boucle; le balisage
        // n'est ajoute qu'a la toute fin, sur une valeur echappee. Le nom de
        // domaine interroge vient du client qui a emis la requete DNS.
        $query=dnsfq_tr($TR,8);
        $type=dnsfq_tr($TR,9);
        $CLIENT_IP=dnsfq_tr($TR,7);

        VERBOSE($line,__LINE__);
        VERBOSE("query=$query type=$type [$CLIENT_IP]",__LINE__);

        $annot=dnsfq_annotation($TR,$COUNTRIES,$FWRULES,$strong,$color);

        $concat=false;
        // "#_out(){2375}#" quantifiait un groupe VIDE 2375 fois: le motif ne
        // vaut rien de plus que "_out", pour un cout d'analyse absurde.
        if(strpos(dnsfq_tr($TR,5),"_out")!==false){
            $concat=true;
        }

        $t5=dnsfq_tr($TR,5);
        if($t5=="init" || $t5=="start" || $t5=="service" || $t5=="DNS" || $t5=="_sasl_plugin_load" || $t5=="module" || $t5=="MODULE_INIT:" || $t5=="error:" || $t5=="pythonmod"){
            $concat=true;
        }

        if(dnsfq_tr($TR,6)=="ERROR"){$concat=true;$color=$red;}

        if(preg_match("#^[A-Z\_]+:#",$t5)){
            $type=$t5;
            $CLIENT_IP=str_replace(array('[',']'),"",dnsfq_tr($TR,6));
            // $re[2] etait relu HORS du if qui le definit: quand le second motif
            // ne correspondait pas, $re portait encore la capture du premier et
            // l'index 2 n'existait pas.
            $head="";
            if(preg_match("#^([A-Z]+):(.+)#",dnsfq_tr($TR,7),$re)){
                $type=$re[1];
                $head=$re[2];
            }
            $query=trim($head." $t5 ".dnsfq_join($TR,6));
        }

        if($concat){
            $type="-";
            $CLIENT_IP="-";
            $strong=false;
            $query=dnsfq_join($TR,5);
        }

        if($t5=="--------------:"){
            $strong=false;
            $CLIENT_IP="-";
            $type="DEBUG";
            $query=dnsfq_join($TR,6);
            // <code> entre en conflit avec le theme: monospace en ligne.
            $mono=true;
        }

        if($type=="BLOCK"){$color=$red;}

        if($fullconcat){
            $query=dnsfq_join($TR,0);
            $date="-";
            $CLIENT_IP="-";
            $type="-";
            $color=$red;
        }
        if(preg_match("#(error|Exception)#",$query)){$color=$red;}

        $q=req_html($query);
        if($mono){$q="<span style='font-family:monospace'>$q</span>";}
        if($annot!==""){$q.=$annot;}
        if($strong){$q="<strong>$q</strong>";}

        $rows[]="<tr id='$md'>"
            .dnsfq_cell($color,"<span class='awc-mono awc-meta'>".req_html((string)$date)."</span>")
            .dnsfq_cell($color,"<span class='awc-mono'>".req_html($CLIENT_IP)."</span>")
            .dnsfq_cell($color,"<span class='awc-tag'>".req_html($type)."</span>")
            .dnsfq_cell($color,$q,false)
            ."</tr>";
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th nowrap>$zdate</th>";
    $html[]="<th nowrap>$ipaddr</th>";
    $html[]="<th nowrap>{type}</th>";
    $html[]="<th>{query}</th>";
    $html[]="</tr></thead><tbody id='dnsfq-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    // Le <tfoot> etait emis A L'INTERIEUR du <tbody> (le </tbody> venait apres
    // le </tfoot>), et son <ul class='pagination'> n'etait jamais alimente:
    // l'init footable ciblait $('.footable'), un selecteur GLOBAL, alors que ce
    // tableau porte .awc-tbl.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='dnsfq-pager'></span>";
    $html[]="</div></div></div>";

    // Contenu de fichier: c'est la ligne de commande montee par le framework,
    // elle n'a jamais eu vocation a etre du HTML.
    $cmd=trim((string)@file_get_contents("/usr/share/artica-postfix/ressources/logs/unbound.log.cmd"));
    if($cmd!==""){
        $html[]="<div class='awc-meta' style='padding:0 16px 10px 16px'>"
            ."<span style='font-family:monospace'>".req_html($cmd)."</span></div>";
    }

    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"dnsfq-rows","dnsfq-pager");
}
