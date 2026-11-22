<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Inclusion INCONDITIONNELLE: awc_table_style()/awc_pager_js()/awc_search_bar() vivent ici.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
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
    if(!isset($_GET["t"])){$t=time();}else{$t=intval($_GET["t"]);}
    if(!isset($_SESSION["POSTGRESQL_SEARCH"]) || $_SESSION["POSTGRESQL_SEARCH"]==null){$_SESSION["POSTGRESQL_SEARCH"]="limit 200";}
    // Valeur injectee dans un litteral JS: json_encode + HEX_TAG, pour qu'un "</script>"
    // ou un guillemet stocke en session ne puisse pas sortir de la chaine.
    $initial=json_encode((string)$_SESSION["POSTGRESQL_SEARCH"],JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT);

    // Meme barre que les tableaux awc (awc_search_bar) mais cablee sur le serveur:
    // la requete "<terme> limit <n>" est interpretee par format_search_protocol() et
    // c'est le daemon qui filtre le journal, pas le navigateur.
    $html=array();
    $html[]=awc_search_bar("pge-search-$t");
    $html[]="<div class='row'><div id='progress-firehol-restart'></div>";
    $html[]="<div id='table-loader'></div>";
    $html[]="</div>";

    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="PostgreSQL $version &raquo;&raquo; {events}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{PostgreSQL_explain}";
    $TINY_ARRAY["BUTTONS"]="&nbsp;";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Script concatene APRES le parsing: _ENGINE_parse_body lirait chaque bloc {...}
    // du JavaScript comme un jeton a traduire.
    $js="<script>
    (function(){
        var inp=document.getElementById('pge-search-$t');
        if(!inp){return;}
        inp.value=$initial;
        function ss$t(){
            LoadAjax('table-loader','$page?table=yes&t=$t&search='+encodeURIComponent(inp.value));
        }
        inp.onkeypress=function(e){ if(checkEnter(e)){ ss$t(); } };
        var go=document.getElementById('pge-search-$t-go');
        if(go){ go.onclick=function(){ ss$t(); return false; }; }
        window.ss$t=ss$t;
        ss$t();
    })();
    $jstiny
    </script>";

    echo $tpl->search_block_css().$tpl->_ENGINE_parse_body(@implode("\n", $html)).$js;

}

function ParsLine($line):array{
    $tpl=new template_admin();
    if(preg_match("#^([a-zA-Z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\].*?GMT\s+.*?\]\s+([A-Z]+):\s+(.+)#",$line,$re)) {

        $date = strtotime($re[1] . " " . $re[2] . " " . $re[3]);
        $status = $re[5];
        $line = $re[6];
        $datetext = $tpl->time_to_date($date, true);
        return array("DATE"=>$datetext,"STATUS"=>$status,"LINE"=>$line,"TIME"=>$date);
    }

    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?postgres.*?\]:(.+)#",$line,$re)) {

        $Month=$tpl->MonthToInteger($re[1]);
        $Day=$re[2];
        $Time=$re[3];
        $line = $re[4];
        $date=strtotime(date("Y")."-".$Month."-".$Day." ".$Time);
        $datetext = $tpl->time_to_date($date, true);
        $status="";
        return array("DATE"=>$datetext,"STATUS"=>$status,"LINE"=>$line,"TIME"=>$date);
    }
    return array();

}

/**
 * CSS propre a la page: uniquement l'echelle de severite. Le reste vient de
 * awc_table_style() (class.statistics_appliance.inc), partage par tous les tableaux.
 * Concatene autour du parsing, jamais dedans: _ENGINE_parse_body prendrait chaque
 * corps de regle CSS pour un {token}.
 */
function pge_style():string{
    return "<style>
.pge-ico-err{background:rgba(186,26,26,.10);color:#ba1a1a}
.pge-ico-warn{background:rgba(134,83,0,.10);color:#865300}
.pge-b{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;padding:3px 7px;border-radius:4px;color:#fff;white-space:nowrap}
.pge-b-err{background:#ba1a1a}
.pge-b-warn{background:#865300}
.pge-b-note{background:#006a62}
.pge-b-info{background:#e8ecec;color:#3d4947}
.pge-msg{font-size:13px;line-height:19px;color:#111d23;word-break:break-word}
.pge-msg .text-danger{color:#ba1a1a}
.pge-msg .text-warning{color:#865300}
.pge-msg .text-info{color:#006a62}
</style>";
}

/**
 * Severite d'une entree de journal -> (suffixe d'icone, icone, classe de pastille, intitule).
 * Reprend la table STATICO de l'ancien code: les memes niveaux PostgreSQL, rendus
 * a l'echelle du tableau awc au lieu des <span class='label'> de Bootstrap.
 */
function pge_severity($status):array{
    $status=strtoupper(trim((string)$status));
    if($status=="ERROR" || $status=="FATAL" || $status=="PANIC"){
        return array("err","fas fa-circle-exclamation","pge-b-err","ERROR");
    }
    if($status=="WARNING"){
        return array("warn","fas fa-triangle-exclamation","pge-b-warn","WARN.");
    }
    if($status=="HINT"){    return array("none","fas fa-circle-info","pge-b-note","HINT"); }
    if($status=="STATEMENT"){return array("none","fas fa-circle-info","pge-b-note","STAT."); }
    if($status=="NOTICE"){  return array("none","fas fa-circle-info","pge-b-note","NOTICE"); }
    return array("none","fas fa-circle-info","pge-b-info","INFO.");
}

/**
 * Une entree de journal: horodatage en identite, pastille de severite, message.
 * $raw sert aux lignes qu'aucun motif ne reconnait: elles etaient echo'ees telles
 * quelles AVANT le tableau, ce qui cassait la structure et sortait sans echappement.
 * Elles deviennent une ligne normale, texte echappe, sans horodatage.
 */
function pge_row($datetext,$status,$line,$raw=false):string{
    if($raw){
        $sev="none"; $ico="fas fa-circle-question"; $bcls="pge-b-info"; $blabel="&mdash;";
        $msg=req_html($line);
    }else{
        list($sev,$ico,$bcls,$blabel)=pge_severity($status);
        // Pentest 2026-11-04 : message du journal PostgreSQL (donnee externe) affiche sans encodage (XSS stockee).
        $msg=req_html($line);
        // Coloration du message, heritee de l'ancien code (motifs inchanges).
        if(preg_match("#(does not exist|Error while|Can not)#i",$msg)){$msg="<span class='text-danger'>$msg</span>";}
        elseif(preg_match("#VIRUS DETECTED#i",$msg)){$msg="<span class='text-warning'>$msg</span>";}
        elseif(preg_match("#Setting antivirus default engine#i",$msg)){$msg="<span class='text-info'>$msg</span>";}
        elseif(preg_match("#incomplete startup packet#",$msg)){$msg="<span style='color:#CCCCCC'>$msg</span>";}
    }
    $icocls=($sev=="none")?"awc-ico":"awc-ico pge-ico-$sev";
    $name=($datetext=="")?"<span class='awc-none'>&mdash;</span>":req_html($datetext);

    $h ="<tr>";
    $h.="<td style='width:1%' nowrap><div class='awc-ident'>";
    $h.="<span class='$icocls'><i class='$ico'></i></span>";
    $h.="<span><span class='awc-name'>$name</span></span>";
    $h.="</div></td>";
    $h.="<td class='center' style='width:1%' nowrap><span class='pge-b $bcls'>$blabel</span></td>";
    $h.="<td><div class='pge-msg'>$msg</div></td>";
    $h.="</tr>";
    return $h;
}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
        return false;
    }

    $rows=array();
    foreach ($json->Logs as $line){
        if(trim($line)==null){continue;}
        if($GLOBALS['VERBOSE']){echo "FOUND ".req_html($line)."\n";}

        $Array=ParsLine($line);
        if(!isset($Array["DATE"])){
            $rows[]=pge_row("","",$line,true);
            continue;
        }
        $rows[]=pge_row($Array["DATE"],$Array["STATUS"],$Array["LINE"]);
    }
    $total=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{date}</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th>{events}</th>";
    $html[]="</tr></thead><tbody id='pge-rows'>";
    if($total==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $total / $total {events}</span>";
    $html[]="<span class='awc-pg' id='pge-pager'></span>";
    $html[]="</div></div></div>";

    // Feuilles et scripts concatenes AUTOUR du parsing: la regex #{(.+?)}# de
    // _ENGINE_parse_body lit chaque corps de regle CSS et chaque bloc JS comme un jeton.
    $foot="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
    echo awc_table_style().pge_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(50,"pge-rows","pge-pager")
        .$foot;

}
