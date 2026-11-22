<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["main-page"])){page();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["opts"])){search_opts_js();exit;}
if(isset($_GET["search-opts-popup"])){search_opts_popup();exit;}
if(isset($_GET["search-opts-reset"])){search_opts_reset();exit;}
if(isset($_POST["remote_addr"])){search_opts_save();exit;}

page();


function rule_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $rulename="{default}";
    $ID=intval($_GET["rule-js"]);
    if($ID>0) {
        $row = acls_api_get("/dns/dnsdist/rules/$ID");
        if(is_array($row) && isset($row["ruleName"])){
            $rulename = $row["ruleName"];
        }
    }
    $tpl->js_dialog6("$rulename: {events}","$page?filter-by-rule=$ID",1200);
    return true;
}

function page(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t          = time();
    $addPLUS    = null;
    if(isset($_GET["filter-by-rule"])){
        $addPLUS="&filter-by-rule=".intval($_GET["filter-by-rule"]);
    }
    $options["WRENCH"]="Loadjs('$page?opts=yes&function=%s')";
    $html[]="<div style='margin-top:15px'>";
    $html[]=$tpl->search_block($page,null,null,null,$addPLUS,$options);
    $html[]="</div>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
       echo $tpl->_ENGINE_parse_body($html);

}
// $_GET["function"] est le nom de la fonction JS de rafraichissement generee par
// search_block(). Il repart tel quel dans du JS ("$function()") et dans des URL:
// il ne peut donc contenir qu'un identifiant JavaScript.
function dnsl_function():string{
    $f=isset($_GET["function"])?(string)$_GET["function"]:"";
    return preg_replace('/[^A-Za-z0-9_]/','',$f);
}
// Le formulaire d'options n'a que ces cinq champs. search_opts_save() recopiait
// $_POST EN ENTIER dans la session, et search() serialise cette session pour
// l'expedier au daemon: n'importe quelle cle postee y arrivait.
function dnsl_opts_fields():array{
    return array("ruleid","category","remote_addr","DateFrom","TimeFrom");
}
function dnsl_opts_sanitize($opts):array{
    if(!is_array($opts)){return array();}
    $out=array();
    foreach(dnsl_opts_fields() as $k){
        if(!isset($opts[$k])){continue;}
        if(is_array($opts[$k])){continue;}
        $out[$k]=(string)$opts[$k];
    }
    return $out;
}
function dnsl_opt($key):string{
    if(!isset($_SESSION["DNSFWSEARCH"][$key])){return "";}
    if(is_array($_SESSION["DNSFWSEARCH"][$key])){return "";}
    return (string)$_SESSION["DNSFWSEARCH"][$key];
}
// "$function()" avec un $function vide emet un "();" litteral: SyntaxError, et
// c'est tout le bloc <script> -- fermeture du dialogue comprise -- qui avorte.
function dnsl_after_js($function):string{
    $js="dialogInstance4.close();";
    if($function!==""){$js.="$function();";}
    return $js;
}
function search_opts_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=dnsl_function();
    return $tpl->js_dialog4("{options}","$page?search-opts-popup=yes&function=$function");
}
function search_opts_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=dnsl_function();

    $rrNames = [];
    $rules = acls_api_get("/dns/dnsdist/rules");
    if(is_array($rules)){
        foreach($rules as $r){
            $rrNames[intval($r["id"])] = $r["ruleName"];
        }
    }

    $categories=categories_list();

    $DateFrom=dnsl_opt("DateFrom");
    if($DateFrom==""){$DateFrom="0000-00-00";}

    $form[]=$tpl->field_array_hash($rrNames,"ruleid","{rulename}",dnsl_opt("ruleid"));

    $form[]=$tpl->field_array_hash($categories,"category","{category}",dnsl_opt("category"));

    $form[]=$tpl->field_ipaddr("remote_addr","{src}",dnsl_opt("remote_addr"));
    $form[]=$tpl->field_date("DateFrom","{from_date}",$DateFrom);
    $form[]=$tpl->field_clock("TimeFrom","{from_time}",dnsl_opt("TimeFrom"));
    $js=dnsl_after_js($function);
    $tpl->form_add_button("{reset}","Loadjs('$page?search-opts-reset=yes&function=$function')");
    echo $tpl->form_outside("{search}",$form,null,"{save}",$js);
    return true;
}
function search_opts_reset():bool{
    $function=dnsl_function();
    unset($_SESSION["DNSFWSEARCH"]);
    header("content-type: application/x-javascript");
    echo dnsl_after_js($function);
    return true;
}
function search_opts_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["DNSFWSEARCH"]=dnsl_opts_sanitize($_POST);
    return true;
}
function categories_list(){
    $tpl=new template_admin();
    $HASH[0]="{not_categorized}";

    $cats = acls_api_get("/webfilters/categories");
    if(!is_array($cats)){ return $HASH; }

    foreach($cats as $c){
        if(preg_match("#^reserved#", $c["categoryName"])){ continue; }
        $categoryId = $c["categoryId"];
        $HASH[$categoryId] = $tpl->utf8_encode($c["categoryName"]);
    }

    return $HASH;
}

function GetRules():array{
    $MAINB=array();
    $MAINB[0]="<span class='awc-badge awc-badge-on'>{RESOLVED}</span>";
    $MAINB[1]="<span class='awc-badge awc-badge-on'>{RESOLVED}</span>";
    $MAINB[2]="<span class='awc-badge awc-badge-alert'>{deny}</span>";
    $MAINB[3]="<span class='awc-badge awc-badge-warn'>Spoofing</span>";
    $MAINB[4]="<span class='awc-badge awc-badge-warn'>Spoofing</span>";
    $MAINB[6]="<span class='awc-badge awc-badge-on'>{RESOLVED}</span>";
    $MAINB[9]="<span class='awc-badge awc-badge-warn'>SafeSearch</span>";
    $MAINB[10]="<span class='awc-badge awc-badge-on'>Active Directory</span>";

    $rules = acls_api_get("/dns/dnsdist/rules");
    // Sans regle en base, $RULES restait NON DEFINIE et partait telle quelle dans
    // le return: avertissement sur PHP 8 et second element a null.
    $MAIN=array();
    $RULES=array();
    $MAIN[0]=$MAINB[0];
    if(is_array($rules)){
        foreach($rules as $r){
            $ID=intval($r["id"]);
            $ruletype=intval($r["ruleType"]);
            $MAIN[$ID]=$MAINB[$ruletype] ?? "";
            $RULES[$ID]=$r["ruleName"];
        }
    }
    return array($MAIN,$RULES);

}


// --- Cellules du tableau: une cellule = une fonction ------------------------
// Toutes les valeurs viennent du fichier de log de dnsdist. Le nom de domaine
// interroge (domain=, q=) y est integralement choisi par le client qui emet la
// requete DNS: il est echappe sans exception. "rulename=" l'est aussi, car le
// motif #rulename=(.+)# capture la PREMIERE occurrence de la ligne, qui peut
// tres bien se trouver dans le domaine interroge.
// Pentest 2026-11-04 : champs du journal dnsdist (donnee externe) : htmlspecialchars UTF-8 rendait "" sur du Latin-1, remplace par req_html (XSS stockee).
function dnsl_cell_time($sdate):string{
    return "<td style='width:1%' nowrap><span class='awc-mono awc-meta'>".req_html((string)$sdate)."</span></td>";
}
function dnsl_cell_rule($ico):string{
    if($ico==null){$ico="";}
    return "<td class='center' style='width:1%' nowrap>$ico</td>";
}
function dnsl_cell_src($ipsrc,$edns):string{
    $h="<td style='width:1%' nowrap><span class='awc-mono'>".req_html((string)$ipsrc)."</span>";
    if(strlen((string)$edns)>0){
        $h.="<div class='awc-meta awc-mono'>EDNS ".req_html((string)$edns)."</div>";
    }
    return $h."</td>";
}
function dnsl_cell_country($flag,$countryName):string{
    $h="<td style='width:1%' nowrap><img src='/img/".req_html((string)$flag)."'>";
    if(strlen((string)$countryName)>0){
        $h.=" <span class='awc-meta'>".req_html((string)$countryName)."</span>";
    }
    return $h."</td>";
}
function dnsl_cell_query($domain,$hrefRule,$categoryName,$badcat):string{
    $h="<td><span class='awc-name'>".req_html((string)$domain)."</span>";
    if(strlen((string)$hrefRule)>0){$h.=" $hrefRule";}
    if(strlen((string)$categoryName)>0){
        // .awc-badge ne porte que color:#fff, sans fond: seul, il est invisible.
        // Categorie ordinaire = .awc-tag (pastille claire), categorie signalee =
        // .awc-badge-alert (rouge).
        $cls=$badcat?"awc-badge awc-badge-alert":"awc-tag";
        $h.="<div class='awc-meta'><span class='$cls'>".req_html((string)$categoryName)."</span></div>";
    }
    return $h."</td>";
}
function dnsl_cell_type($qtype):string{
    if(strlen((string)$qtype)==0){return "<td class='center' style='width:1%'><span class='awc-muted'>-</span></td>";}
    return "<td class='center' style='width:1%' nowrap><span class='awc-tag'>".req_html((string)$qtype)."</span></td>";
}

// Les categories dont la presence est en soi une alerte: le libelle passe en
// rouge dans la cellule de requete.
function dnsl_badcats():array{
    return array("46"=>true,"135"=>true,"92"=>true,"105"=>true,"111"=>true,
        "5027"=>true,"5010"=>true,"5024"=>true,"5094"=>true);
}

// Resume lisible du filtre courant, affiche dans l'en-tete de la colonne des
// requetes. Les valeurs sont echappees, les {tokens} ne le sont pas: ils
// doivent survivre jusqu'a _ENGINE_parse_body().
function dnsl_filter_label($OPTS,$zrulesNames):string{
    $search_text=array();
    if(isset($OPTS["ruleid"])){
        if(intval($OPTS["ruleid"])>0){
            $rn=isset($zrulesNames[$OPTS["ruleid"]])?$zrulesNames[$OPTS["ruleid"]]:$OPTS["ruleid"];
            $search_text[]="{rulename} ".htmlspecialchars((string)$rn);
        }
    }
    if(isset($OPTS["DateFrom"])){
        if(strlen($OPTS["DateFrom"])>0){
            $tf=isset($OPTS["TimeFrom"])?$OPTS["TimeFrom"]:"";
            $search_text[]="{from_date} ".htmlspecialchars($OPTS["DateFrom"]." ".$tf);
        }
    }
    if(isset($OPTS["remote_addr"])){
        if(strlen($OPTS["remote_addr"])>0){
            $search_text[]="{src} ".htmlspecialchars($OPTS["remote_addr"]);
        }
    }
    if(count($search_text)==0){
        return " <i style='font-weight: normal'>({filter}:{all_events})</i>";
    }
    return " <i style='font-weight: normal'>({filter}: ".@implode(", ",$search_text).")</i>";
}

function search(){
    $tpl                        = new template_admin();
    $GLOBALS["TPLZ"]            = $tpl;
    $filename                   = "/usr/share/artica-postfix/ressources/logs/dnsdist.log.tmp";
    $qr                         = new mysql_catz();
    if(!isset($_SESSION["DNSFWSEARCH"])){$_SESSION["DNSFWSEARCH"]=array();}
    $OPTS=dnsl_opts_sanitize($_SESSION["DNSFWSEARCH"]);
    if(isset($_GET["filter-by-rule"])){
        $OPTS["ruleid"]=intval($_GET["filter-by-rule"]);
    }

    // "if($OPTS["DateFrom"]="0000-00-00")" etait une AFFECTATION, pas une
    // comparaison: la condition valait toujours vrai et les deux bornes etaient
    // effacees a chaque recherche. Le filtre {from_date}/{from_time} du
    // formulaire d'options n'atteignait donc jamais le daemon, qui les attend
    // pourtant (restDnsdistSearchLogs lit options["DateFrom"]/["TimeFrom"]).
    if(!isset($OPTS["DateFrom"])||$OPTS["DateFrom"]==""||$OPTS["DateFrom"]=="0000-00-00"){
        unset($OPTS["DateFrom"]);
        unset($OPTS["TimeFrom"]);
    }
    if(isset($OPTS["TimeFrom"])){
        if($OPTS["TimeFrom"]==""||$OPTS["TimeFrom"]=="00:00"){unset($OPTS["TimeFrom"]);}
    }

    $searchTerm=isset($_GET["search"])?(string)$_GET["search"]:"";
    if(trim($searchTerm)==""){$searchTerm="50 events";}
    $MAIN=$tpl->format_search_protocol($searchTerm);
    $zopts=base64_encode(serialize($OPTS));
    // format_search_protocol() renvoie TERM a null sur "all events": strlen(null)
    // et rawurlencode(null) sont deprecies a partir de PHP 8.1.
    $MAIN["TERM"]=isset($MAIN["TERM"])?(string)$MAIN["TERM"]:"";
    if(strlen($MAIN["TERM"])==0){
        $MAIN["TERM"]="NONE";
    }
    // Le terme part dans un SEGMENT d'URL et peut contenir des espaces.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/accesses/".intval($MAIN["MAX"])."/".rawurlencode($MAIN["TERM"])."/$zopts");

    $badcats=dnsl_badcats();
    list($icons,$zrulesNames)=GetRules();
    if(!is_array($icons)){$icons=array();}
    if(!is_array($zrulesNames)){$zrulesNames=array();}
    $zrulesNames[9999999]="{global}";
    $icons[9999999]="<span class='awc-badge awc-badge-off'>Default</span>";

    $filter=dnsl_filter_label($OPTS,$zrulesNames);

    $MONTHS=array("Jan"=>1,"Feb"=>2,"Mar"=>3,"Apr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Aug"=>8,"Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);
    $data=explode("\n",(string)@file_get_contents($filename));
    $ip2host=new ip2host();
    $rows=array();

    foreach ($data as $line){
        VERBOSE("---- [$line] ----",__LINE__);
        $edns="";
        if(strpos($line,"edns=none")>0){
            $line=str_replace(" edns=none", "", $line);
        }else {
            if (preg_match("# edns=([0-9\.]+)#", $line, $ri)) {
                $line = str_replace("edns=$ri[1]", "", $line);
                $edns = $ri[1];
                if ($edns == "none") {
                    $edns = "";
                }
                VERBOSE("---- [$line] ----[edns=$ri[1]]", __LINE__);
            }
        }

        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?:\s+rule=([0-9]+)\s+src=(.+?)\s+domain=(.+?)\s+q=(.*?)\s+category=([0-9a-z]+)#", $line,$re)){
            continue;
        }

        $Month  = $re[1];
        $Day    = $re[2];
        $time   = $re[3];
        $ruleid = intval($re[4]);
        $ipsrc  = $re[5];
        $domain = $re[6];
        $QTYPE  = $re[7];

        // Un mois inconnu (rotation, format syslog different) ne doit pas faire
        // disparaitre l'evenement: on retombe sur l'horodatage brut.
        if(isset($MONTHS[$Month])){
            $sday  = date("Y")."-".$MONTHS[$Month]."-$Day $time";
            $sdate = $tpl->time_to_date(strtotime($sday), true);
        }else{
            $sdate = "$Month $Day $time";
        }

        if(!is_numeric($re[8])){$re[8]=0;}
        $Category=intval($re[8]);
        $Category_name="";
        if($Category>0){
            $Category_name=$qr->CategoryIntToStr($Category);
        }
        $badcat=($Category>0 && isset($badcats[$Category]));

        $rulename=null;
        if(preg_match("#rulename=(.+)#",$line,$ri)){$rulename=$ri[1];}

        $HrefRule="({default})";
        if($ruleid>0){
            if(is_null($rulename)){$rulename="";}
            if(strlen($rulename)<3){
                $rulename=isset($zrulesNames[$ruleid])?(string)$zrulesNames[$ruleid]:"";
            }
            $HrefRule="(".$tpl->td_href(req_html((string)$rulename),null,
                    "Loadjs('fw.dns.dnsdist.rules.php?rule-id-js=$ruleid')").")";
        }

        $ipinfoApi=$ip2host->ipinfoApi($ipsrc);
        if(!is_array($ipinfoApi)){$ipinfoApi=array();}
        $flag=isset($ipinfoApi["flag"])?$ipinfoApi["flag"]:null;
        if($flag==null){$flag="flags/info.png";}
        $countryName=isset($ipinfoApi["countryName"])?$ipinfoApi["countryName"]:"";

        $ico=isset($icons[$ruleid])?$icons[$ruleid]:"";
        $rows[]="<tr>"
            .dnsl_cell_time($sdate)
            .dnsl_cell_rule($ico)
            .dnsl_cell_src($ipsrc,$edns)
            .dnsl_cell_country($flag,$countryName)
            .dnsl_cell_query($domain,$HrefRule,$Category_name,$badcat)
            .dnsl_cell_type($QTYPE)
            ."</tr>";
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th>{time}</th>";
    $html[]="<th class='center'>{rule}</th>";
    $html[]="<th nowrap>{src}</th>";
    $html[]="<th nowrap>{country}</th>";
    $html[]="<th>{DNS_QUERIES}$filter</th>";
    $html[]="<th class='center'>{type}</th>";
    $html[]="</tr></thead><tbody id='dnsl-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    // La colonne {duration} a disparu: le format ecrit par dnsdist
    // ("rule=%s src=%s domain=%s q=%s category=%s cache=%s rulename=%s edns=%s")
    // ne porte aucune duree, $duration valait "&nbsp;" a chaque ligne. Le
    // <tfoot> qui la suivait etait de surcroit emis A L'INTERIEUR du <tbody>,
    // avec un colspan de 6 pour 7 colonnes, et son <ul class='pagination'>
    // n'etait jamais alimente: l'init footable ciblait $('.footable'), un
    // selecteur GLOBAL, alors que ce tableau porte .awc-tbl.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='dnsl-pager'></span>";
    $html[]="</div></div></div>";

    // Contenu de fichier: echappe, il n'a jamais eu vocation a etre du HTML.
    $cmd=trim((string)@file_get_contents("/usr/share/artica-postfix/ressources/logs/dnsdist.log.cmd"));
    if($cmd!==""){
        $html[]="<div class='awc-meta' style='padding:0 16px 10px 16px'>"
            ."<span style='font-family:monospace'>".req_html($cmd)."</span></div>";
    }

    // Feuille de style et script restent en dehors de l'analyse: le motif
    // #{(.+?)}# de _ENGINE_parse_body() lit chaque corps de regle CSS et chaque
    // bloc JS comme un {token}.
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"dnsl-rows","dnsl-pager");
}
