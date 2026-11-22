<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

//
$GLOBALS["POSTFIX_RULES"][1]="<strong>{ACCEPT}</strong><br>{POSTFIX_REGEX_ACCEPT}";
$GLOBALS["POSTFIX_RULES"][2]="<strong>{INFO}</strong><br>{POSTFIX_REGEX_INFO}";
$GLOBALS["POSTFIX_RULES"][3]="<strong>{DISCARD}</strong><br>{POSTFIX_REGEX_DISCARD}";
$GLOBALS["POSTFIX_RULES"][4]="<strong>{REJECT}</strong><br>{POSTFIX_REGEX_REJECT}";
$GLOBALS["POSTFIX_RULES"][5]="<strong>{BCC}</strong><br>{BCC_help}";
$GLOBALS["POSTFIX_RULES"][6]="<strong>{PREPEND}</strong><br>{POSTFIX_REGEX_PREPEND}";


$GLOBALS["POSTFIX_RULES_STATUS"][1]="label-primary";
$GLOBALS["POSTFIX_RULES_STATUS"][2]="label-info";
$GLOBALS["POSTFIX_RULES_STATUS"][3]="label-danger";
$GLOBALS["POSTFIX_RULES_STATUS"][5]="label-danger";
$GLOBALS["POSTFIX_RULES_STATUS"][5]="label-pink";
$GLOBALS["POSTFIX_RULES_STATUS"][6]="label-info";

$GLOBALS["POSTFIX_RULES_TITLE"][1]="{ACCEPT}";
$GLOBALS["POSTFIX_RULES_TITLE"][2]="{INFO}";
$GLOBALS["POSTFIX_RULES_TITLE"][3]="{DISCARD}";
$GLOBALS["POSTFIX_RULES_TITLE"][5]="{REJECT}";
$GLOBALS["POSTFIX_RULES_TITLE"][5]="{BCC}";
$GLOBALS["POSTFIX_RULES_TITLE"][6]="{PREPEND}";


if(isset($_GET["popjs"])){popup_js();exit;}
if(isset($_GET["rule-safesearch"])){rule_safesearch();exit;}
if(isset($_GET["replace-rule"])){replace_rule();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}

if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}


if(isset($_GET["ch-method-js"])){change_method_js();exit;}
if(isset($_GET["ch-method-popup"])){change_method_popup();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["default-popup"])){default_popup();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["view-rules-popup"])){view_rules_popup();exit;}
page();

function popup_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $title=$tpl->javascript_parse_text("{events}");
    $instanceid=intval($_GET["instance-id"]);
    $ruleid=intval($_GET["ruleid"]);
    $tpl->js_dialog2($title, "$page?table-start=yes&instance-id=$instanceid&ruleid=$ruleid",1024);
}
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=intval($_GET["instance-id"]);

    $html=$tpl->page_header("{APP_POSTFIX}&nbsp;&raquo;&nbsp;{transactions}",
        ico_eye,"&nbsp;","$page?table-start=yes&instance-id=$instanceid","postfix-transactions",
        "progress-transactions-restart",false,"table-transactions-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function fillRulesList_explain():bool{
    $GLOBALS["POSTFIX_RULES"][1]="<strong>{ACCEPT}</strong><br>{POSTFIX_REGEX_ACCEPT}";
    $GLOBALS["POSTFIX_RULES"][2]="<strong>{INFO}</strong><br>{POSTFIX_REGEX_INFO}";
    $GLOBALS["POSTFIX_RULES"][3]="<strong>{DISCARD}</strong><br>{POSTFIX_REGEX_DISCARD}";
    $GLOBALS["POSTFIX_RULES"][4]="<strong>{REJECT}</strong><br>{POSTFIX_REGEX_REJECT}";
    $GLOBALS["POSTFIX_RULES"][5]="<strong>{BCC}</strong><br>{BCC_help}";
    $GLOBALS["POSTFIX_RULES"][6]="<strong>{PREPEND}</strong><br>{POSTFIX_REGEX_PREPEND}";
    return true;
}
function fillRules_canchange():array{
    $RULESSWITH[2]=true;
    $RULESSWITH[3]=true;
    $RULESSWITH[4]=true;
    return $RULESSWITH;
}
function table_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleparam="";
    $instanceid=intval($_GET["instance-id"]);
    if(isset($_GET["ruleid"])){
        // Pentest 2026-11-04 : ruleid etait recopie sans encodage dans la page (XSS).
        $ruleparam="&ruleid=".intval($_GET["ruleid"]);
    }
    echo $tpl->search_block($page,null,null,null,"&table=yes&instance-id=$instanceid&$ruleparam");
    return true;
}
/** Pastille de verdict d'une transaction milter. */
function pfxacc_status_badge($status):string{
    if(strlen($status)==0){$status="PASS";}
    $bg="#263238";
    if(preg_match("#(PREPEND|INFO|BCC)#",$status)){$bg="#006a62";}
    if(preg_match("#(ACCEPT|PASS)#",$status)){$bg="#26a69a";}
    if(preg_match("#(DISCARD|REJECT)#",$status)){$bg="#ba1a1a";}
    return "<span class='awc-badge' style='background:$bg'>".req_html($status)."</span>";
}

/** Adresse raccourcie: la partie locale est tronquee, le domaine reste lisible. */
function pfxacc_shorten_addr($addr):string{
    $addr=(string)$addr;
    if(strlen($addr)<=30){return $addr;}
    $dm=explode("@",$addr);
    if(count($dm)<2){return substr($addr,0,30)."...";}
    return substr($dm[0],0,27)."...@".$dm[1];
}

/**
 * Une transaction: sujet en identite, expediteur et destinataires en sous-titre.
 * Le sujet, l'expediteur, les destinataires et le nom d'hote proviennent des
 * en-tetes du message -- donc d'un tiers: ils sont echappes. Ils etaient rendus
 * bruts, ce qui faisait de la page un vecteur de XSS stocke declenche a la simple
 * consultation du journal par un administrateur.
 */
function pfxacc_row($tpl,$ligne,$rulename,$ruleField):string{
    // Pentest 2026-11-04 : subject, mailfrom, recipients, hostname (donnee externe) affiche sans encodage (XSS stockee).
    // htmlspecialchars() en UTF-8 rendait "" sur un sujet Latin-1 : req_html() est neutre sur les octets.
    $subject=trim((string)($ligne["subject"] ?? ""));
    $mailfrom=pfxacc_shorten_addr($ligne["mailfrom"] ?? "");
    $recipients=(string)($ligne["recipients"] ?? "");
    $hostname=(string)($ligne["hostname"] ?? "");
    $ipaddr=(string)($ligne["ipaddr"] ?? "");
    $host=($hostname===$ipaddr)?$ipaddr:"$hostname [$ipaddr]";
    $zdate=$tpl->time_to_date(strtotime((string)($ligne["zdate"] ?? "")),true);

    $rcpts=array();
    foreach(explode(",",$recipients) as $r){
        $r=trim($r);
        if($r!==""){$rcpts[]=req_html(pfxacc_shorten_addr($r));}
    }

    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-envelope'></i></span>";
    $h.="<span><span class='awc-name'>"
        .($subject===""?"<span class='awc-none'>{no_subject}</span>":req_html($subject))."</span>";
    $h.="<div class='awc-meta'>".req_html($mailfrom);
    if(count($rcpts)>0){$h.=" &rarr; ".@implode(", ",$rcpts);}
    $h.="</div></span></div></td>";
    if(!$ruleField){
        $h.="<td class='awc-mono'>$rulename</td>";
    }
    $h.="<td class='center'>".pfxacc_status_badge((string)($ligne["ruleaction"] ?? ""))."</td>";
    $h.="<td class='awc-mono'>".req_html($host)."</td>";
    $h.="<td class='awc-mono'>".FormatBytes(intval($ligne["mailsize"] ?? 0)/1024)."</td>";
    $h.="<td class='awc-mono'>".req_html($zdate)."</td>";
    $h.="</tr>";
    return $h;
}

/**
 * Nom(s) de regle d'une transaction. Quand plusieurs regles ont trouve une
 * correspondance, le CSV matchedrules les porte toutes -- pas seulement le
 * ruleid unique conserve dans la ligne.
 */
function pfxacc_rulename($ligne,$MAINRULES,$ruleField):string{
    $ruleid=intval($ligne["ruleid"] ?? 0);
    $matched=trim((string)($ligne["matchedrules"] ?? ""));
    if(!$ruleField && strlen($matched)>0){
        $names=array();
        foreach(explode(",",$matched) as $rid){
            $rid=intval($rid);
            if($rid<=0){continue;}
            $nm=isset($MAINRULES[$rid])?(string)$MAINRULES[$rid]:"";
            if(strlen($nm)<1){$nm="#$rid";}
            $names[]=req_html($nm);
        }
        if(count($names)>0){return @implode("<br>",array_unique($names));}
    }
    if($ruleid<=0){return "{default}";}
    $nm=isset($MAINRULES[$ruleid])?(string)$MAINRULES[$ruleid]:"";
    return (strlen($nm)<2)?"#$ruleid":htmlspecialchars($nm);
}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceid=intval($_GET["instance-id"]);
    // Liste blanche au point de lecture: ce nom part BRUT dans du JavaScript
    // (`function RefreshPostfixRules(){ X(); }`), ce qui en faisait un vecteur de
    // XSS reflechi. search_block ne genere jamais que "ss<time>".
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ruleid=0;
    $ruleField=false;
    if(isset($_GET["ruleid"])){
        $ruleid=intval($_GET["ruleid"]);
        if($ruleid>0){
            $ruleField=true;
        }
    }

    $MAINRULES=array();
    if(!$ruleField) {
        $allRules = acls_api_get("/postfix/acls") ?? [];
        foreach ($allRules as $r) {
            if(intval($r["instanceID"] ?? 0) !== $instanceid){ continue; }
            if($ruleid > 0 && intval($r["id"] ?? 0) !== $ruleid){ continue; }
            $MAINRULES[intval($r["id"] ?? 0)] = $tpl->utf8_encode($r["ruleName"] ?? "");
        }
    }

    $QUERY="";
    $q=new postgres_sql();

    // La saisie partait BRUTE dans le LIKE PostgreSQL: une quote suffisait a
    // detourner la requete. pg_escape_string2 neutralise quotes et antislashs.
    $search=trim((string)($_GET["search"] ?? ""));
    if(strlen($search)>2){
        $like=pg_escape_string2(str_replace("*","%","*$search*"));
        $like=str_replace("%%","%",$like);
        $QUERY=" AND ( (mailfrom LIKE '$like') OR (recipients LIKE '$like') OR (subject LIKE '$like') OR (hostname LIKE '$like') OR (ipaddr::text LIKE '$like') )";
    }
    $ruleFilter="";
    if(isset($_GET["ruleid"])){
        $rid=intval($_GET["ruleid"]);
        if($rid>0){
            // include events where the rule was one of several matched (matchedrules)
            $ruleFilter=" AND (ruleid=$rid OR '$rid' = ANY(string_to_array(matchedrules, ',')))";
        }else{
            $ruleFilter=" AND ruleid=0";
        }
    }
    $sql="SELECT * FROM articamilter WHERE instanceid=$instanceid $QUERY $ruleFilter ORDER BY zdate DESC LIMIT 250";
    $results=$q->QUERY_SQL($sql);

    $rows=array();
    if($results) {
        while ($ligne = @pg_fetch_assoc($results)) {
            $rows[]=pfxacc_row($tpl,$ligne,pfxacc_rulename($ligne,$MAINRULES,$ruleField),$ruleField);
        }
    }

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{subject}</th>";
    if(!$ruleField){ $html[]="<th>{rulename}</th>"; }
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th>{hostname}</th>";
    $html[]="<th>{size}</th>";
    $html[]="<th>{time}</th>";
    $html[]="</tr></thead><tbody id='pfxacc-rows'>";
    $colspan=$ruleField?5:6;
    if(count($rows)==0){
        $html[]="<tr><td colspan='$colspan'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $shown=count($rows);
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown / $shown</span>";
    $html[]="<span class='awc-pg' id='pfxacc-pager'></span>";
    $html[]="</div></div></div>";

    $jstiny="";
    if(!$ruleField) {
        $TINY_ARRAY=array();
        $TINY_ARRAY["TITLE"] = "Artica Milter&nbsp;&raquo;&nbsp;{transactions}";
        $TINY_ARRAY["ICO"] = ico_eye;
        $TINY_ARRAY["EXPL"] = "{postfix_acls_rules_explain}";
        $TINY_ARRAY["BUTTONS"] = "";
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
    }

    $js="";
    if($function!=""){ $js="function RefreshPostfixRules(){ $function(); }\n"; }
    $html[]="<script>$js$jstiny\nNoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .awc_pager_js(50,"pfxacc-rows","pfxacc-pager");
    return true;
}
