<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}

js();


function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog5("{load_balancer} {events}","$page?popup=yes",950);
}

function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,null);
    echo "</div>";
}

function search(){

    $tpl        = new template_admin();
    $MAIN       = $tpl->format_search_protocol($_GET["search"],false,false,false,true);
    $line       = base64_encode(serialize($MAIN));
    $tfile      = PROGRESS_DIR."/proxylb.events.syslog";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?proxy-lb-events=$line");
    $data=explode("\n",(string)@file_get_contents($tfile));
    krsort($data);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{events}</th>";
    $html[]="</tr></thead><tbody id='lb-rows'>";

    $c=0;
    foreach ($data as $rawline){
        // $line servait deja plus haut a transporter le motif de recherche
        // serialise: la boucle l'ecrasait.
        $rawline=trim($rawline);
        if($rawline===""){continue;}
        $c++;
        // La ligne de journal partait BRUTE dans le HTML, et sa cellule portait
        // width:1% + nowrap: l'evenement etait force sur une seule ligne dans
        // une colonne de largeur nulle.
        $html[]="<tr><td>".htmlspecialchars($rawline)."</td></tr>";

    }
    if($c==0){
        $html[]="<tr><td class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c</span>";
    $html[]="<span class='awc-pg' id='lb-pager'></span>";
    $html[]="</div></div></div>";
    // Il y avait ici un @file_get_contents($pat) avec $pat=null: passer null a
    // file_get_contents() est deprecie depuis PHP 8.1, et le bloc etait de
    // toute facon toujours vide.

    // Feuille de style et pager concatenes APRES l'analyse: la regex #{(.+?)}#
    // de _ENGINE_parse_body prend chaque corps d'accolade CSS ou JavaScript
    // pour un {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(50,"lb-rows","lb-pager");


}