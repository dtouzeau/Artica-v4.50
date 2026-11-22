<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style()/awc_pager_js()/awc_search_bar()/awc_filter_js(): kit des
// tableaux d'entites. Include au PREMIER NIVEAU obligatoire.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.mem.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbrules.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["missing-databases-ufdb-alert"])){missing_databases_alert();exit;}
if(isset($_POST["UfdbReloadBySchedule"])){save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_rule();exit;}
if(isset($_POST["rule-order"])){move_rule();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["zoom-errors-js"])){zoom_errors_js();exit;}
if(isset($_GET["zoom-errors-popup"])){zoom_error_popup();exit;}
if(isset($_GET["dynamics"])){dynamics();exit;}
page();



function delete_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["delete-js"]);
    if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_refuse_js(); }
    // Le nom AFFICHE est relu en base, pas recu dans l'URL. L'ancienne version prenait
    // $_GET["rule"] et $_GET["md"] au navigateur et les posait dans du JavaScript
    // genere: une apostrophe dans un nom de regle cassait la confirmation, et #md
    // designait la ligne a retirer -- n'importe laquelle.
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_rules WHERE ID=?",array($ID));
    // js_confirm_delete() insere ce texte en HTML (puis l'encode en base64, donc les
    // guillemets ne cassent pas le JavaScript) -- mais un nom porteur de balises y
    // serait rendu comme du balisage. Il vient de la base, pas du moteur de
    // traduction: l'echapper est ici correct, et necessaire.
    $rulename=htmlspecialchars(isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$ID");
    // Le tableau se recharge: retirer la ligne a la main laissait le compteur du pied
    // et la numerotation zOrder sur leurs anciennes valeurs.
    $js="LoadAjax('table-loader-ufdbrules-service','$page?table=yes');";
    return $tpl->js_confirm_delete($rulename,"delete",$ID,$js);
}
function zoom_errors_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["zoom-errors-js"]);
    if($ID==0){$rulename="{default}";}
    if($ID==-1){$rulename="{new_rule}";}
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_rules WHERE ID=?",array($ID));
        $rulename=isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$ID";
    }
    // buildADialog() pose le titre dans « title: '$title' » SANS echappement. Son
    // passage par javascript_parse_text() ne neutralise les apostrophes que des
    // jetons resolus, pas d'un nom relu en base: « L'ecole » cassait donc le
    // litteral et la modale ne s'ouvrait jamais -- une erreur en console, rien a
    // l'ecran. On neutralise les deux caracteres qui ferment ou echappent ce litteral.
    // Deux dangers distincts, deux traitements. htmlspecialchars(ENT_QUOTES) neutralise
    // a la fois le balisage (le titre est insere en HTML par la boite de dialogue) et
    // l'apostrophe qui fermerait le litteral. Reste l'antislash, que htmlspecialchars
    // laisse passer et qui ouvrirait une sequence d'echappement dans ce meme litteral.
    $rulename=str_replace("\\","",htmlspecialchars($rulename,ENT_QUOTES));
    return $tpl->js_dialog9($rulename. " {errors}", "$page?zoom-errors-popup=$ID");

}

function delete_rule():bool{
    $users=new usersMenus();
    // Cacher l'icone de suppression n'est pas un controle: ce POST repond a toute
    // session authentifiee, quelle que soit l'interface qu'on lui a montree.
    if(!$users->AsDansGuardianAdministrator){ echo "{access_denied}"; return false; }

    // $_POST["delete"] etait CONCATENE dans cinq DELETE. « 1' OR '1'='1 » y effacait
    // toutes les regles de filtrage web et toutes leurs associations, en une requete.
    $ID=intval($_POST["delete"]);
    if($ID<=0){ echo "{error}"; return false; }
    // Regle ecrite par Artica Meta : en lecture seule (relu ici, pas seulement l'icone masquee).
    if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_refuse_text(); }

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $tables=array(
        "DELETE FROM webfilter_assoc_groups WHERE webfilter_id=?",
        "DELETE FROM webfilter_blklnk WHERE webfilter_ruleid=?",
        "DELETE FROM webfilter_blks WHERE webfilter_id=?",
        "DELETE FROM webfilter_rules WHERE ID=?",
        "DELETE FROM ufdb_page_rules WHERE webruleid=?",
    );
    foreach($tables as $sql){
        $q->QUERY_SQL_PARAMS($sql,array($ID));
        if(!$q->ok){
            writelogs("delete_rule($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            echo "{database_error}";
            return false;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    return admin_tracks("Removing Web-filter rule #$ID");
}

function page(){
    $tpl=new template_admin();

    $html=$tpl->page_header("{WEB_FILTERING}: {rules}",
    "fa fa-align-justify","{ufdbgdb_rules_explain}",
        "fw.ufdb.rules.php?table-start=yes","webfiltering-rules","progress-ufdbrules-restart",false,"table-loader-ufdbrules-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{WEB_FILTERING}: {rules}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function table_start():bool{
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/webfilter/patchdb");
    echo "<div id='artica-ufdbrules-table' style='margin-top:10px'></div>
    <script>LoadAjax('artica-ufdbrules-table','$page?table=yes');</script>";
    return true;
}
// ---------------------------------------------------------------------------
// Redesign 2026-11-18 (stitch_web_filtering_rules_redesign): value banner, summary,
// one card per rule read as a sentence, problems shown in the card.
// One model (ufdbr_model) feeds every widget; CSS classes ufdbr-, tokens ufdbr_.
//
// DOM ids rewritten by dynamics() every few seconds (keep them):
//   #table-filtragewebrules-rules  sentinel of RefreshInterval_Loadjs (timer destroyed if absent)
//   #td-status-<ID>                status pill of a rule (0 = default rule)
//   #ufdbr-err-<ID>                source errors line of a rule
//   #ufdbr-k-active, #ufdbr-k-people, #ufdbr-chip-people   live counters
// ---------------------------------------------------------------------------

// Translated plain text of a token, [br] turned into spaces, ready for str_replace of placeholders.
function ufdbr_txt(string $token):string{
    static $cache=array();
    if(isset($cache[$token])){return $cache[$token];}
    $tpl=new template_admin();
    $t=(string)$tpl->_ENGINE_parse_body("{".$token."}");
    $t=preg_replace('#\s*<br\s*/?>\s*#i'," ",$t);
    $cache[$token]=trim($t);
    return $cache[$token];
}

// Text typed by an administrator (rule name): escaped, braces neutralised for the token engine.
function ufdbr_e($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

function ufdbr_num(int $n):string{
    return number_format($n,0,".",",");
}

// Rules of webfilter.db, in evaluation order.
function ufdbr_db_rules():array{
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE group_id=''");
    $results=$q->QUERY_SQL("SELECT * FROM webfilter_rules ORDER BY zOrder, ID");
    if(!$q->ok){
        // Le message SQLite nomme tables et colonnes: journal, pas ecran.
        writelogs("webfilter_rules: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        return array();
    }
    return is_array($results)?$results:array();
}

// Sources, blocked and always-allowed category counts of a rule (0 = default rule).
function ufdbr_counts(int $id):array{
    $w=new webfilter_rules();
    return array(
        "sources"=>$id>0?intval($w->COUNTDEGROUPES($id)):0,
        "black"=>intval($w->COUNTDEGBLKS($id)),
        "white"=>intval($w->COUNTDEGBWLS($id)),
    );
}

// Schedule of a rule as a sentence ("" when none).
function ufdbr_timespace($ts,int $id):string{
    if($ts===null || $ts===""){return "";}
    $w=new webfilter_rules();
    $t=(string)$w->rule_time_list_explain($ts,$id,time());
    return trim(str_replace('\n\n',"<br>",$t));
}

// Live state from /ufdb/usercur: requests per rule, people filtered, source errors per rule.
// known=false when the filter wrote no metrics yet: the page then says it waits for counters.
function ufdbr_live():array{
    $out=array("known"=>false,"requests"=>array(),"people"=>-1,"errors"=>array());
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/usercur"),true);
    if(!is_array($json)){return $out;}
    if(isset($json["Users"])){
        $u=json_decode((string)$json["Users"],true);
        if(is_array($u) && isset($u["records"])){$out["people"]=intval($u["records"]);}
    }
    if(isset($json["Metrics"])){
        $m=json_decode((string)$json["Metrics"],true);
        if(is_array($m)){
            $out["known"]=true;
            foreach($m as $id=>$main){
                if(is_array($main)){$out["requests"][intval($id)]=intval($main["requests"] ?? 0);}
            }
        }
    }
    if(isset($json["Errors"]) && is_array($json["Errors"])){
        foreach($json["Errors"] as $id=>$s){
            $e=json_decode((string)$s,true);
            if(is_array($e) && isset($e["Errors"]) && is_array($e["Errors"]) && count($e["Errors"])>0){
                $out["errors"][intval($id)]=count($e["Errors"]);
            }
        }
    }
    return $out;
}

function ufdbr_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $users=new usersMenus();
    $tpl=new template_admin();
    $m=array();
    $m["installed"]=intval($sock->GET_INFO("EnableUfdbGuard"))==1;
    $m["urgency_proxy"]=intval($sock->GET_INFO("SquidUrgency"))==1;
    $m["urgency_filter"]=intval($sock->GET_INFO("SquidUFDBUrgency"))==1;
    $m["slave"]=intval($sock->GET_INFO("PowerDNSEnableClusterSlave"))==1;
    $m["can_write"]=(bool)$users->AsDansGuardianAdministrator;
    if(!$m["installed"]){return $m;}
    $m["live"]=ufdbr_live();
    $metaRules=ufdbrules_meta_rules();
    $metaMax=ufdbrules_meta_max_zorder();

    $rules=array();
    foreach(ufdbr_db_rules() as $l){
        $id=intval($l["ID"] ?? 0);
        if($id<=0){continue;}
        $c=ufdbr_counts($id);
        $end=(string)($l["endofrule"] ?? "");
        if($end!=="none"){$end="any";}
        $name=trim((string)$tpl->utf8_encode((string)($l["groupname"] ?? "")));
        $locked=($metaRules===null || isset($metaRules[$id]));
        $rules[]=array(
            "id"=>$id,"zorder"=>intval($l["zOrder"] ?? 0),
            "name"=>$name===""?"noname":$name,
            "enabled"=>intval($l["enabled"] ?? 0)==1,
            "groupmode"=>intval($l["groupmode"] ?? 1),
            "end"=>$end,
            "all"=>intval($l["AllSystems"] ?? 0)==1,
            "sources"=>$c["sources"],"black"=>$c["black"],"white"=>$c["white"],
            "schedule"=>ufdbr_timespace($l["TimeSpace"] ?? "",$id),
            "locked"=>$locked,
            "can_up"=>($metaMax===null)?false:($metaMax<0 || intval($l["zOrder"] ?? 0)-1>$metaMax),
            "errors"=>intval($m["live"]["errors"][$id] ?? 0),
        );
    }
    foreach($rules as $i=>$r){$rules[$i]["attention"]=ufdbr_attention($r);}
    $m["rules"]=$rules;

    $d=$sock->unserializeb64($sock->GET_INFO("DansGuardianDefaultMainRule"));
    if(!is_array($d)){$d=array();}
    $c=ufdbr_counts(0);
    $end=(string)($d["endofrule"] ?? "");
    $m["default"]=array(
        "id"=>0,
        "groupmode"=>is_numeric($d["groupmode"] ?? null)?intval($d["groupmode"]):1,
        "end"=>$end==="none"?"none":"any",
        "black"=>$c["black"],"white"=>$c["white"],
        "schedule"=>ufdbr_timespace($d["TimeSpace"] ?? "",0),
        "position"=>intval($d["defaultPosition"] ?? 0),
        "safesearch"=>intval($sock->GET_INFO("EnableGoogleSafeSearch"))==0 && intval($d["GoogleSafeSearch"] ?? 0)==1,
        "locked"=>ufdbrules_meta_default_held(),
        "errors"=>intval($m["live"]["errors"][0] ?? 0),
    );
    return $m;
}

// Why a rule needs attention: list of cause codes (empty = fine). A disabled rule never does.
// "No category" only matters in normal mode: with "other websites blocked" the rule already blocks everything else.
function ufdbr_attention(array $r):array{
    $a=array();
    if(!$r["enabled"]){return $a;}
    if(!$r["all"] && $r["sources"]==0){$a[]="nosource";}
    if($r["groupmode"]==1 && $r["end"]==="any" && $r["black"]+$r["white"]==0){$a[]="nocat";}
    if($r["groupmode"]==0){$a[]="blockall";}
    if($r["errors"]>0){$a[]="errors";}
    return $a;
}

function ufdbr_style():string{
    if(isset($GLOBALS["UFDBR_STYLE_DONE"])){return "";}
    $GLOBALS["UFDBR_STYLE_DONE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$soft=$a["accent_soft"];$dark=$a["accent_dark"];
    }
    $c=array();
    $c[]=".ufdbr{display:flex;flex-direction:column;gap:20px;padding:6px 2px 20px;color:#0f172a;font-size:14px}";
    $c[]=".ufdbr-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".ufdbr-alert{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 20px;border:1px solid;border-radius:12px;font-weight:600}";
    $c[]=".ufdbr-alert.red{border-color:#fecaca;background:#fef2f2;color:#991b1b}";
    $c[]=".ufdbr-alert.amber{border-color:#fde68a;background:#fffbeb;color:#92400e}";
    $c[]=".ufdbr-alert .i{display:inline-flex;width:32px;height:32px;border-radius:8px;align-items:center;justify-content:center;margin-right:12px;background:rgba(255,255,255,.7)}";
    $c[]=".ufdbr-abtn{display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;border:1px solid currentColor;background:#fff;color:inherit;font-weight:600;font-size:13px;cursor:pointer;white-space:nowrap}";
    $c[]=".ufdbr-ban{display:flex;gap:20px;padding:24px 28px;border-left:4px solid $accent}";
    $c[]=".ufdbr-ban-ico{flex:0 0 48px;height:48px;border-radius:10px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:22px}";
    $c[]=".ufdbr-ban h2{margin:0 0 6px;font-size:20px;font-weight:600;color:#0f172a}";
    $c[]=".ufdbr-ban p{margin:0;line-height:1.6;color:#475569;max-width:1100px}";
    $c[]=".ufdbr-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}";
    $c[]=".ufdbr-bchip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;color:#334155;font-size:13px}";
    $c[]=".ufdbr-bchip.live{background:$soft;border-color:$accent;color:#065f46;font-weight:600}";
    $c[]=".ufdbr-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}";
    $c[]=".ufdbr-kpi{padding:16px 20px;display:flex;flex-direction:column;gap:6px}";
    $c[]=".ufdbr-kpi-h{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.04em}";
    $c[]=".ufdbr-kpi b{font-size:28px;font-weight:600;line-height:1.1}";
    $c[]=".ufdbr-kpi p{margin:0;font-size:13px;color:#64748b}";
    $c[]=".ufdbr-kpi.bad{border-color:#fecaca;background:#fffafa;cursor:pointer}.ufdbr-kpi.bad .ufdbr-kpi-h,.ufdbr-kpi.bad b,.ufdbr-kpi.bad p{color:#b91c1c}";
    $c[]=".ufdbr-kpi.bad:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".ufdbr-tools{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}";
    $c[]=".ufdbr-search{position:relative;flex:0 1 520px}";
    $c[]=".ufdbr-search>svg,.ufdbr-search>i{position:absolute;left:14px;top:13px;color:#94a3b8}";
    $c[]=".ufdbr-search input{width:100%;height:42px;padding:0 14px 0 40px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:#fff;color:#0f172a}";
    $c[]=".ufdbr-search input:focus{outline:2px solid $accent;outline-offset:1px;border-color:$accent}";
    $c[]=".ufdbr-tright{display:flex;flex-wrap:wrap;align-items:center;gap:10px}";
    $c[]=".ufdbr-toggle{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border:1px solid #fecaca;border-radius:8px;background:#fff;color:#b91c1c;font-weight:600;font-size:13px;cursor:pointer}";
    $c[]=".ufdbr-toggle[aria-pressed=true]{background:#fef2f2}";
    $c[]=".ufdbr-toggle .n{display:inline-block;min-width:20px;padding:0 6px;border-radius:999px;background:#dc2626;color:#fff;text-align:center}";
    $c[]=".ufdbr-toggle:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".ufdbr-order{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:13px;color:#475569}";
    $c[]=".ufdbr-list{display:flex;flex-direction:column;gap:12px}";
    $c[]=".ufdbr-rule{display:flex;gap:16px;align-items:flex-start;padding:16px 20px}";
    $c[]=".ufdbr-rule.off{background:#fbfcfd}.ufdbr-rule.off .ufdbr-name{color:#64748b}";
    $c[]=".ufdbr-rule.def{border:2px dashed #cbd5e1;box-shadow:none}";
    $c[]=".ufdbr-no{flex:0 0 32px;height:32px;border-radius:50%;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:600;color:#334155}";
    $c[]=".ufdbr-no.def{background:$soft;border-color:$accent;color:$dark}";
    $c[]=".ufdbr-body{flex:1 1 auto;min-width:0}";
    $c[]=".ufdbr-head{display:flex;flex-wrap:wrap;align-items:center;gap:8px 10px;margin-bottom:6px}";
    $c[]=".ufdbr-name{font-size:16px;font-weight:600;color:#0f172a;text-decoration:none;cursor:pointer;background:none;border:0;padding:0}";
    $c[]=".ufdbr-name:hover{text-decoration:underline}.ufdbr-name:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".ufdbr-sub{font-size:13px;color:#64748b}";
    $c[]=".ufdbr-pill{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;border:1px solid;font-size:13px;font-weight:600;white-space:nowrap}";
    $c[]=".ufdbr-pill.on{background:#ecfdf5;border-color:#a7f3d0;color:#047857}";
    $c[]=".ufdbr-pill.idle,.ufdbr-pill.wait{background:#f8fafc;border-color:#e2e8f0;color:#475569;font-weight:500}";
    $c[]=".ufdbr-pill.off{background:#f1f5f9;border-color:#cbd5e1;color:#475569}";
    $c[]=".ufdbr-pill.meta{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}";
    $c[]=".ufdbr-pill.safe{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;font-weight:500}";
    $c[]=".ufdbr-sched{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#475569}";
    $c[]=".ufdbr-sent{display:flex;flex-wrap:wrap;align-items:center;gap:6px 8px;color:#334155;line-height:1.6}";
    $c[]=".ufdbr-chip{display:inline-flex;align-items:center;gap:6px;padding:1px 10px;border-radius:6px;border:1px solid #e2e8f0;background:#f8fafc;color:#0f172a;font-size:14px;font-weight:500;white-space:nowrap;cursor:default}";
    $c[]="button.ufdbr-chip{cursor:pointer}button.ufdbr-chip:hover{border-color:#94a3b8}button.ufdbr-chip:focus-visible{outline:2px solid $accent;outline-offset:1px}";
    $c[]=".ufdbr-chip.red{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
    $c[]=".ufdbr-chip.green{background:#ecfdf5;border-color:#a7f3d0;color:#047857}";
    $c[]=".ufdbr-chip.zero{color:#64748b}";
    $c[]=".ufdbr-warn{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:10px;padding:8px 12px;border-radius:8px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;font-size:13px}";
    $c[]=".ufdbr-warn.red{border-color:#fecaca;background:#fef2f2;color:#b91c1c}";
    $c[]=".ufdbr-warn a{color:inherit;font-weight:600;text-decoration:underline;cursor:pointer;white-space:nowrap}";
    $c[]=".ufdbr-acts{flex:0 0 auto;display:flex;gap:4px;align-items:center}";
    $c[]=".ufdbr-act{width:34px;height:34px;border-radius:8px;border:1px solid transparent;background:none;color:#64748b;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}";
    $c[]=".ufdbr-act:hover{background:#f1f5f9;border-color:#e2e8f0;color:#0f172a}.ufdbr-act.danger:hover{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
    $c[]=".ufdbr-act:disabled{opacity:.35;cursor:not-allowed;background:none;border-color:transparent}";
    $c[]=".ufdbr-act:focus-visible{outline:2px solid $accent;outline-offset:1px}";
    $c[]=".ufdbr-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:8px;border:1px solid $accent;background:$accent;color:#fff;font-weight:600;font-size:14px;cursor:pointer}";
    $c[]=".ufdbr-btn.ghost{background:#fff;color:#0f172a;border-color:#cbd5e1}";
    $c[]=".ufdbr-btn:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".ufdbr-empty{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding:40px 24px}";
    $c[]=".ufdbr-empty-i{width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;background:$soft;color:$dark;margin-bottom:6px}";
    $c[]=".ufdbr-empty h3{margin:0;font-size:18px;font-weight:600}.ufdbr-empty p{margin:0;max-width:640px;color:#475569;line-height:1.6}";
    $c[]=".ufdbr-hidden{display:none !important}";
    $c[]="@media (max-width:1200px){.ufdbr-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.ufdbr-rule{flex-wrap:wrap}.ufdbr-acts{margin-left:48px}}";
    $c[]="@media (prefers-reduced-motion:reduce){.ufdbr *{transition:none !important}}";
    return "<style>".implode("\n",$c)."</style>";
}

// Web filter not installed: replaces the whole page.
function ufdbr_not_installed($tpl):string{
    $restart=$tpl->framework_buildjs("/ufdb/install","ufdb.enable.progress","ufdb.enable.progress.log",
        "progress-ufdbrules-restart","location.reload();");
    $button=$tpl->button_autnonome("{install_feature}",$restart,ico_cd,"AsSquidAdministrator");
    return "<div class='ufdbr'><div class='ufdbr-card ufdbr-empty'><div class='ufdbr-empty-i'><i class='fas fa-compact-disc' aria-hidden='true'></i></div>"
        ."<h3>{ufdbr_install_title}</h3><p>{ufdbr_install_text}</p><div style='margin-top:10px'>$button</div></div></div>";
}

// 1. Problem banners: emergency modes (the missing databases one is loaded apart).
function ufdbr_urgency(array $m):string{
    $h="";
    if($m["urgency_filter"]){
        $h.="<div class='ufdbr-alert red' role='alert'><span><span class='i'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i></span>{ufdbr_urgency_filter}</span>"
            ."<button type='button' class='ufdbr-abtn' OnClick=\"Loadjs('fw.ufdb.emergency.remove.php')\">{ufdbr_leave_urgency}</button></div>";
    }
    if($m["urgency_proxy"]){
        $h.="<div class='ufdbr-alert red' role='alert'><span><span class='i'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i></span>{ufdbr_urgency_proxy}</span>"
            ."<button type='button' class='ufdbr-abtn' OnClick=\"Loadjs('fw.proxy.emergency.remove.php')\">{ufdbr_leave_urgency}</button></div>";
    }
    return $h;
}

// 2. Value banner. The people chip is live (dynamics) and hidden while unknown.
function ufdbr_banner(array $m):string{
    $people=intval($m["live"]["people"]);
    $ptxt=$people>=0?str_replace("%N%",ufdbr_num($people),ufdbr_txt("ufdbr_chip_people")):"";
    $hide=$people>=0?"":" ufdbr-hidden";
    return "<section class='ufdbr-card ufdbr-ban'><div class='ufdbr-ban-ico'><i class='fas fa-filter' aria-hidden='true'></i></div>"
        ."<div><h2>{ufdbr_banner_title}</h2><p>{ufdbr_banner_text}</p><div class='ufdbr-chips'>"
        ."<span class='ufdbr-bchip'><i class='fas fa-list-ol' aria-hidden='true'></i>{ufdbr_chip_order}</span>"
        ."<span class='ufdbr-bchip'><i class='fas fa-user-shield' aria-hidden='true'></i>{ufdbr_chip_default}</span>"
        ."<span class='ufdbr-bchip live$hide' id='ufdbr-chip-people'><i class='fas fa-users' aria-hidden='true'></i><span>$ptxt</span></span>"
        ."<span class='ufdbr-bchip'><i class='fas fa-layer-group' aria-hidden='true'></i>{ufdbr_chip_categories}</span>"
        ."</div></div></section>";
}

function ufdbr_count_active(array $m):int{
    $n=0;
    foreach($m["rules"] as $r){if($r["enabled"] && intval($m["live"]["requests"][$r["id"]] ?? 0)>0){$n++;}}
    return $n;
}

function ufdbr_count_attention(array $rules):int{
    $n=0;
    foreach($rules as $r){if(count($r["attention"])>0){$n++;}}
    return $n;
}

// 3. Summary: four counters, all from the rules and the live state.
function ufdbr_kpis(array $m):string{
    $total=count($m["rules"]);
    $on=0;
    foreach($m["rules"] as $r){if($r["enabled"]){$on++;}}
    $sub=str_replace(array("%A%","%B%"),array((string)$on,(string)($total-$on)),ufdbr_txt("ufdbr_k_rules_sub"));
    $active=$m["live"]["known"]?(string)ufdbr_count_active($m):"&mdash;";
    $people=intval($m["live"]["people"]);
    $att=ufdbr_count_attention($m["rules"]);
    $h="<div class='ufdbr-kpis'>";
    $h.="<div class='ufdbr-card ufdbr-kpi'><span class='ufdbr-kpi-h'>{ufdbr_k_rules}<i class='fas fa-filter' aria-hidden='true'></i></span><b>$total</b><p>$sub</p></div>";
    $h.="<div class='ufdbr-card ufdbr-kpi'><span class='ufdbr-kpi-h'>{ufdbr_k_active}<i class='fas fa-circle-check' aria-hidden='true'></i></span><b id='ufdbr-k-active'>$active</b><p>{ufdbr_k_active_sub}</p></div>";
    $h.="<div class='ufdbr-card ufdbr-kpi'><span class='ufdbr-kpi-h'>{ufdbr_k_people}<i class='fas fa-users' aria-hidden='true'></i></span><b id='ufdbr-k-people'>".($people>=0?ufdbr_num($people):"&mdash;")."</b><p>{ufdbr_k_people_sub}</p></div>";
    if($att>0){
        $h.="<button type='button' class='ufdbr-card ufdbr-kpi bad' style='text-align:left;font:inherit' OnClick=\"UfdbrAttention(true)\"><span class='ufdbr-kpi-h'>{ufdbr_k_att}<i class='fas fa-triangle-exclamation' aria-hidden='true'></i></span><b>$att</b><p>{ufdbr_k_att_sub}</p></button>";
    }else{
        $h.="<div class='ufdbr-card ufdbr-kpi'><span class='ufdbr-kpi-h'>{ufdbr_k_att}<i class='fas fa-circle-check' aria-hidden='true'></i></span><b style='color:#94a3b8'>0</b><p>{ufdbr_k_att_none}</p></div>";
    }
    return $h."</div>";
}

// 4. Toolbar: search on the name, "needing attention" toggle, order reminder.
function ufdbr_tools(array $m):string{
    $att=ufdbr_count_attention($m["rules"]);
    $h="<div class='ufdbr-tools'><div class='ufdbr-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='ufdbr-search' aria-label=\"".htmlspecialchars(ufdbr_txt("ufdbr_search_ph"),ENT_QUOTES,"UTF-8",false)."\" placeholder=\"".htmlspecialchars(ufdbr_txt("ufdbr_search_ph"),ENT_QUOTES,"UTF-8",false)."\" autocomplete='off'></div>"
        ."<div class='ufdbr-tright'>";
    if($att>0){
        $h.="<button type='button' class='ufdbr-toggle' id='ufdbr-att-toggle' aria-pressed='false' OnClick=\"UfdbrAttention()\"><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>{ufdbr_only_attention} <span class='n'>$att</span></button>";
    }
    if(count($m["rules"])>0){
        $h.="<span class='ufdbr-order'><i class='fas fa-arrow-down-1-9' aria-hidden='true'></i>".str_replace("%N%",(string)count($m["rules"]),ufdbr_txt("ufdbr_order_note"))."</span>";
    }
    return $h."</div></div>";
}

// Inner HTML of a status pill; shared with dynamics() so both render the same thing.
function ufdbr_status_html(bool $enabled,bool $known,int $requests):string{
    if(!$enabled){return "<span class='ufdbr-pill off'><i class='fas fa-pause' aria-hidden='true'></i>{ufdbr_st_disabled}</span>";}
    if(!$known){return "<span class='ufdbr-pill wait'><i class='fas fa-hourglass-half' aria-hidden='true'></i>{ufdbr_st_wait}</span>";}
    if($requests<=0){return "<span class='ufdbr-pill idle'><i class='fas fa-circle-pause' aria-hidden='true'></i>{ufdbr_st_idle}</span>";}
    $t=str_replace("%N%",ufdbr_num($requests),ufdbr_txt("ufdbr_st_active"));
    return "<span class='ufdbr-pill on'><i class='fas fa-circle-check' aria-hidden='true'></i>$t</span>";
}

function ufdbr_status(array $r,array $live):string{
    $id=intval($r["id"]);
    $enabled=isset($r["enabled"])?(bool)$r["enabled"]:true;
    return "<span id='td-status-$id'>".ufdbr_status_html($enabled,$live["known"],intval($live["requests"][$id] ?? 0))."</span>";
}

// Clickable chip of the sentence line (or a plain one when $js is empty).
function ufdbr_chip(string $text,string $cls,string $js):string{
    if($js===""){return "<span class='ufdbr-chip $cls'>$text</span>";}
    return "<button type='button' class='ufdbr-chip $cls' OnClick=\"$js\">$text</button>";
}

function ufdbr_cats(int $n):string{
    return $n==1?ufdbr_txt("ufdbr_cat_1"):str_replace("%N%",(string)$n,ufdbr_txt("ufdbr_cat_n"));
}

// The rule as a sentence: who, what is blocked, what is always allowed, the other websites.
function ufdbr_sentence(array $r,bool $isDefault):string{
    $id=intval($r["id"]);
    $jsB="Loadjs('fw.ufdb.rules.categories.php?js-ID=$id&modeblk=0');";
    $jsW="Loadjs('fw.ufdb.rules.categories.php?js-ID=$id&modeblk=1');";
    $h=array();
    if($isDefault){
        $who=ufdbr_chip("<i>{ufdbr_who_default}</i>","","");
    }elseif($r["all"]){
        $who=ufdbr_chip("<i class='fas fa-asterisk' aria-hidden='true'></i>{ufdbr_who_everyone}","","");
    }else{
        $n=intval($r["sources"]);
        $t=$n==1?ufdbr_txt("ufdbr_src_1"):str_replace("%N%",(string)$n,ufdbr_txt("ufdbr_src_n"));
        $who=ufdbr_chip($t,$n==0?"red":"","Loadjs('fw.ufdb.rules.sources.php?ID=$id')");
    }
    $h[]="<span>{ufdbr_applies_to}</span>$who";
    if($r["groupmode"]==0){
        $h[]="<span>&mdash; {ufdbr_blocks}</span>".ufdbr_chip("{ufdbr_every_website}","red","");
        return "<div class='ufdbr-sent'>".implode("",$h)."</div>";
    }
    if($r["groupmode"]==2){
        $h[]="<span>&mdash; {ufdbr_allows}</span>".ufdbr_chip("{ufdbr_every_website}","green","");
        return "<div class='ufdbr-sent'>".implode("",$h)."</div>";
    }
    if($r["end"]==="none"){
        $h[]="<span>&mdash; {ufdbr_allows_only}</span>".ufdbr_chip(ufdbr_cats(intval($r["white"])),intval($r["white"])>0?"green":"zero",$jsW);
        $h[]="<span>&mdash; {ufdbr_other_blocked}</span>";
        return "<div class='ufdbr-sent'>".implode("",$h)."</div>";
    }
    $h[]="<span>&mdash; {ufdbr_blocks}</span>".ufdbr_chip(ufdbr_cats(intval($r["black"])),intval($r["black"])>0?"red":"zero",$jsB);
    $h[]="<span>&mdash; {ufdbr_always_allows}</span>".ufdbr_chip(ufdbr_cats(intval($r["white"])),intval($r["white"])>0?"green":"zero",$jsW);
    $h[]="<span>&mdash; {ufdbr_other_websites}</span>".ufdbr_chip("{ufdbr_allowed}","","");
    return "<div class='ufdbr-sent'>".implode("",$h)."</div>";
}

// Problems shown inside the card, each with the action that fixes it.
function ufdbr_warnings(array $r,bool $isDefault):string{
    $id=intval($r["id"]);
    $h="";
    if(!$isDefault && !empty($r["enabled"])){
        if(!$r["all"] && intval($r["sources"])==0){
            $h.="<div class='ufdbr-warn red'><span><i class='fas fa-user-slash' aria-hidden='true'></i> {ufdbr_w_nosource_t}</span><a OnClick=\"Loadjs('fw.ufdb.rules.sources.php?ID=$id')\">{ufdbr_add_sources}</a></div>";
        }
        if($r["groupmode"]==1 && $r["end"]==="any" && intval($r["black"])+intval($r["white"])==0){
            $h.="<div class='ufdbr-warn'><span><i class='fas fa-layer-group' aria-hidden='true'></i> {ufdbr_w_nocat_t}</span><a OnClick=\"Loadjs('fw.ufdb.rules.categories.php?js-ID=$id&modeblk=0');\">{ufdbr_choose_categories}</a></div>";
        }
    }
    $err=intval($r["errors"]);
    $inner=$err>0?ufdbr_errors_html($id,$err):"";
    return $h."<div id='ufdbr-err-$id'>$inner</div>";
}

// Source errors line; shared with dynamics().
function ufdbr_errors_html(int $id,int $n):string{
    $t=$n==1?ufdbr_txt("ufdbr_err_1"):str_replace("%N%",(string)$n,ufdbr_txt("ufdbr_err_n"));
    $page=CurrentPageName();
    return "<div class='ufdbr-warn red'><span><i class='fas fa-bug' aria-hidden='true'></i> $t</span><a OnClick=\"Loadjs('$page?zoom-errors-js=$id')\">{ufdbr_view_errors}</a></div>";
}

function ufdbr_actions(array $r,array $m,bool $first,bool $last):string{
    $id=intval($r["id"]);
    $page=CurrentPageName();
    $lockedTitle=$r["locked"]?" title=\"".htmlspecialchars(ufdbr_txt("ufdbr_locked_tip"),ENT_QUOTES,"UTF-8",false)."\"":"";
    $canMove=!$m["slave"] && !$r["locked"] && $m["can_write"];
    $canDel=!$m["slave"] && !$r["locked"] && $m["can_write"];
    $aria=function($tok)use($r){
        return htmlspecialchars(str_replace("%S%",$r["name"],ufdbr_txt($tok)),ENT_QUOTES,"UTF-8");
    };
    $h="<div class='ufdbr-acts'>";
    $h.="<button type='button' class='ufdbr-act' title=\"{ufdbr_duplicate}\" aria-label=\"".$aria("ufdbr_aria_dup")."\" OnClick=\"Loadjs('fw.webfiltering.rule.duplicate.php?from=$id&page=$page')\"><i class='fas fa-clone' aria-hidden='true'></i></button>";
    $up=$canMove && !$first && $r["can_up"];
    $down=$canMove && !$last;
    $h.="<button type='button' class='ufdbr-act' title=\"{ufdbr_move_up}\" aria-label=\"".$aria("ufdbr_aria_up")."\"".($up?" OnClick=\"RuleGroupUpDown($id,1)\"":" disabled$lockedTitle")."><i class='fas fa-arrow-up' aria-hidden='true'></i></button>";
    $h.="<button type='button' class='ufdbr-act' title=\"{ufdbr_move_down}\" aria-label=\"".$aria("ufdbr_aria_down")."\"".($down?" OnClick=\"RuleGroupUpDown($id,0)\"":" disabled$lockedTitle")."><i class='fas fa-arrow-down' aria-hidden='true'></i></button>";
    $h.="<button type='button' class='ufdbr-act danger' title=\"{ufdbr_delete}\" aria-label=\"".$aria("ufdbr_aria_del")."\"".($canDel?" OnClick=\"Loadjs('$page?delete-js=$id')\"":" disabled$lockedTitle")."><i class='fas fa-trash' aria-hidden='true'></i></button>";
    return $h."</div>";
}

// 5. One rule card.
function ufdbr_card(array $r,array $m,int $index,int $count):string{
    $id=intval($r["id"]);
    $name=ufdbr_e($r["name"]);
    $off=$r["enabled"]?"":" off";
    $att=count($r["attention"])>0?"1":"0";
    $dataName=htmlspecialchars(mb_strtolower($r["name"],"UTF-8"),ENT_QUOTES,"UTF-8");
    $sr=str_replace(array("%A%","%B%"),array((string)($index+1),(string)$count),ufdbr_txt("ufdbr_sr_order"));
    $head="<button type='button' class='ufdbr-name' OnClick=\"Loadjs('fw.ufdb.rules.edit.php?ID=$id')\"><span class='sr-only'>$sr </span>$name</button>";
    if(!$r["enabled"]){$head.="<span class='ufdbr-pill off'><i class='fas fa-pause' aria-hidden='true'></i>{ufdbr_st_disabled}</span>";}
    else{$head.=ufdbr_status($r,$m["live"]);}
    if($r["locked"]){$head.="<span class='ufdbr-pill meta'><i class='fas fa-lock' aria-hidden='true'></i>{ufdbr_meta_badge}</span>";}
    if($r["schedule"]!==""){$head.="<span class='ufdbr-sched'><i class='fas fa-clock' aria-hidden='true'></i>{$r["schedule"]}</span>";}
    return "<div class='ufdbr-card ufdbr-rule$off' data-name=\"$dataName\" data-att='$att'>"
        ."<span class='ufdbr-no' aria-hidden='true'>".($index+1)."</span>"
        ."<div class='ufdbr-body'><div class='ufdbr-head'>$head</div>".ufdbr_sentence($r,false).ufdbr_warnings($r,false)."</div>"
        .ufdbr_actions($r,$m,$index==0,$index==$count-1)."</div>";
}

// Default rule card: never moved nor deleted, applies to anyone no other rule matches.
function ufdbr_default_card(array $m):string{
    $d=$m["default"];
    $d["enabled"]=true;$d["all"]=false;$d["sources"]=0;
    $head="<button type='button' class='ufdbr-name' OnClick=\"Loadjs('fw.ufdb.rules.edit.php?ID=0')\">{ufdbr_default_title}</button>"
        .ufdbr_status($d,$m["live"]);
    if($d["safesearch"]){$head.="<span class='ufdbr-pill safe'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>{ufdbr_safesearch}</span>";}
    if($d["locked"]){$head.="<span class='ufdbr-pill meta'><i class='fas fa-lock' aria-hidden='true'></i>{ufdbr_meta_badge}</span>";}
    if($d["schedule"]!==""){$head.="<span class='ufdbr-sched'><i class='fas fa-clock' aria-hidden='true'></i>{$d["schedule"]}</span>";}
    return "<div class='ufdbr-card ufdbr-rule def' data-default='1'>"
        ."<span class='ufdbr-no def' aria-hidden='true'><i class='fas fa-shield-halved'></i></span>"
        ."<div class='ufdbr-body'><div class='ufdbr-head'>$head</div><div class='ufdbr-sub' style='margin:-2px 0 6px'>{ufdbr_default_sub}</div>"
        .ufdbr_sentence($d,true).ufdbr_warnings($d,true)."</div>"
        ."<div class='ufdbr-acts'><button type='button' class='ufdbr-btn ghost' OnClick=\"Loadjs('fw.ufdb.rules.edit.php?ID=0')\"><i class='fas fa-sliders' aria-hidden='true'></i>{ufdbr_edit_default}</button></div></div>";
}

// 6. Empty state: only the default rule exists.
function ufdbr_empty(array $m):string{
    $btn=(!$m["slave"])?"<button type='button' class='ufdbr-btn' OnClick=\"Loadjs('fw.ufdb.rules.edit.php?ID=-1')\"><i class='fas fa-plus' aria-hidden='true'></i>{ufdbr_new_rule}</button>":"";
    return "<div class='ufdbr-card ufdbr-empty'><div class='ufdbr-empty-i'><i class='fas fa-filter-circle-xmark' aria-hidden='true'></i></div>"
        ."<h3>{ufdbr_empty_title}</h3><p>{ufdbr_empty_text}</p>$btn</div>";
}

// List: default card first or last (defaultPosition), rules in order between.
function ufdbr_list(array $m):string{
    $cards=array();
    $n=count($m["rules"]);
    foreach($m["rules"] as $i=>$r){$cards[]=ufdbr_card($r,$m,$i,$n);}
    $def=ufdbr_default_card($m);
    $h="<div class='ufdbr-list' id='table-filtragewebrules-rules'>";
    if($m["default"]["position"]==0){$h.=$def;}
    $h.=implode("",$cards);
    if($n==0){$h.=ufdbr_empty($m);}
    if($m["default"]["position"]!=0){$h.=$def;}
    $h.="<div class='ufdbr-card ufdbr-empty ufdbr-hidden' id='ufdbr-nomatch'><h3>{ufdbr_nomatch}</h3>"
        ."<button type='button' class='ufdbr-btn ghost' OnClick=\"UfdbrReset()\">{ufdbr_show_all}</button></div>";
    return $h."</div>";
}

function ufdbr_js(string $page):string{
    return "<script>
(function(){
  var att=false;
  function apply(){
    var q=document.getElementById('ufdbr-search');var s=q?q.value.trim().toLowerCase():'';
    var cards=document.querySelectorAll('.ufdbr-rule[data-name]');var shown=0;
    for(var i=0;i<cards.length;i++){
      var c=cards[i];var ok=(s===''||c.getAttribute('data-name').indexOf(s)>-1)&&(!att||c.getAttribute('data-att')==='1');
      if(ok){c.classList.remove('ufdbr-hidden');shown++;}else{c.classList.add('ufdbr-hidden');}
    }
    var nm=document.getElementById('ufdbr-nomatch');
    if(nm){if(cards.length>0&&shown===0){nm.classList.remove('ufdbr-hidden');}else{nm.classList.add('ufdbr-hidden');}}
    var t=document.getElementById('ufdbr-att-toggle');if(t){t.setAttribute('aria-pressed',att?'true':'false');}
  }
  window.UfdbrAttention=function(force){att=(force===true)?true:!att;apply();};
  window.UfdbrReset=function(){att=false;var q=document.getElementById('ufdbr-search');if(q){q.value='';}apply();};
  var q=document.getElementById('ufdbr-search');if(q){q.oninput=apply;}
})();
var xRuleGroupUpDown=function(obj){
  var res=obj.responseText;
  if(res.length>3){alert(res);return;}
  LoadAjax('table-loader-ufdbrules-service','$page?table=yes');
};
function RuleGroupUpDown(ID,direction){
  var XHR=new XHRConnection();
  XHR.appendData('rule-order',ID);
  XHR.appendData('direction',direction);
  XHR.sendAndLoad('$page','POST',xRuleGroupUpDown);
}
</script>";
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=ufdbr_model();
    if(!$m["installed"]){
        echo ufdbr_style().$tpl->_ENGINE_parse_body(ufdbr_not_installed($tpl));
        return true;
    }

    $jsrestart=$tpl->framework_buildjs("/ufdb/compile",
        "dansguardian2.mainrules.progress",
        "dansguardian2.mainrules.progress.log",
        "progress-ufdbrules-restart",
        "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');"
    );
    $jsUpdate=$tpl->framework_buildjs("/category/ufdb/update",
        "artica-webfilterdb.progress","artica-webfilterdb.log","progress-ufdbrules-restart",
        "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');",
        "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');");
    $topbuttons=array();
    if(!$m["slave"]){
        $topbuttons[]=array("Loadjs('fw.ufdb.rules.edit.php?ID=-1')",ico_plus,"{new_rule}");
    }
    $topbuttons[]=array($jsrestart,ico_save,"{build_web_filtering_rules}");
    $topbuttons[]=array("Loadjs('fw.ufdb.categories.groups.php')","fas fa-folder-tree","{categories_groups}");
    $topbuttons[]=array("Loadjs('fw.ufdb.verify.rules.php')","fas fa-check","{verify_rules}");
    $topbuttons[]=array("Loadjs('fw.ufdb.used.databases.php')","fas fa-database","{used_databases}");
    $topbuttons[]=array($jsUpdate,ico_download,"{update_databases}");

    $TINY_ARRAY=array();
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{WEB_FILTERING}: {rules}";
    $TINY_ARRAY["ICO"]="fa fa-align-justify";
    $TINY_ARRAY["EXPL"]="{ufdbgdb_rules_explain}";
    $headsjs="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $jsClock=$tpl->RefreshInterval_Loadjs("table-filtragewebrules-rules",$page,"dynamics=yes");

    $h=array();
    $h[]="<div class='ufdbr'>";
    $h[]=ufdbr_urgency($m);
    $h[]="<div id='missing-databases-ufdb-alert'></div>";
    $h[]=ufdbrules_meta_banner($tpl,null);
    $h[]=ufdbr_banner($m);
    $h[]=ufdbr_kpis($m);
    $h[]=ufdbr_tools($m);
    $h[]=ufdbr_list($m);
    $h[]="</div>";

    echo ufdbr_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).ufdbr_js($page)
        ."<script>NoSpinner();$headsjs;LoadAjaxSilent('missing-databases-ufdb-alert','$page?missing-databases-ufdb-alert=yes');$jsClock</script>";
    return true;
}

function move_rule(){
    $users=new usersMenus();
    if(!$users->AsDansGuardianAdministrator){ echo "{access_denied}"; return; }

    // rule-order et direction etaient concatenes dans quatre requetes dont trois
    // UPDATE. « 1' OR '1'='1 » y reecrivait le zOrder de TOUTES les regles, ce qui
    // reordonnait silencieusement l'ensemble du filtrage web.
    $ID=intval($_POST["rule-order"]);
    $direction=intval($_POST["direction"]);
    if($ID<=0){ echo "{error}"; return; }
    if($direction!==0 && $direction!==1){ echo "{error}"; return; }

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rows=$q->QUERY_SQL_PARAMS("SELECT zOrder FROM webfilter_rules WHERE ID=?",array($ID));
    if(!isset($rows[0]["zOrder"])){ echo "{error}"; return; }
    $xORDER_ORG=intval($rows[0]["zOrder"]);

    $xORDER=($direction==1)?$xORDER_ORG-1:$xORDER_ORG+1;
    if($xORDER<0){$xORDER=0;}

    // Verrou Artica Meta : une regle Meta ne bouge pas, et une regle locale ne passe pas
    // devant une regle Meta (elles occupent les premiers rangs). Lecture illisible = refus.
    if(ufdbrules_meta_locked_rule($ID)){ ufdbrules_meta_refuse_text(); return; }
    if(ufdbrules_meta_zorder_refused($xORDER)){ ufdbrules_meta_refuse_text("{ufdbrules_meta_move_above}"); return; }

    $q->QUERY_SQL_PARAMS("UPDATE webfilter_rules SET zOrder=? WHERE ID=?",array($xORDER,$ID));
    if(!$q->ok){
        writelogs("move_rule($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "{database_error}"; return;
    }

    // La regle qui occupait la place echangee prend l'ancienne. Le calcul est le meme
    // dans les deux sens, seul le decalage change de signe.
    $xORDER2=($direction==1)?$xORDER+1:$xORDER-1;
    if($xORDER2<0){$xORDER2=0;}
    $q->QUERY_SQL_PARAMS("UPDATE webfilter_rules SET zOrder=? WHERE ID<>? AND zOrder=?",
        array($xORDER2,$ID,$xORDER));
    if(!$q->ok){
        writelogs("move_rule($ID) swap: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "{database_error}"; return;
    }

    // Renumerotation compacte: sans elle, deux echanges successifs laissent des trous
    // et deux regles finissent par partager le meme rang.
    $c=0;
    $results=$q->QUERY_SQL("SELECT ID FROM webfilter_rules ORDER BY zOrder");
    if(is_array($results)){
        foreach($results as $ligne){
            $q->QUERY_SQL_PARAMS("UPDATE webfilter_rules SET zOrder=? WHERE ID=?",
                array($c,intval($ligne["ID"])));
            $c++;
        }
    }
    admin_tracks("Web-filter rule #$ID moved ".($direction==1?"up":"down"));
}

function missing_databases_alert():bool{
    $catz=new mysql_catz();
    $f=array();
    $UfdbUsedDatabases=unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUsedDatabases"));
    if(!is_array($UfdbUsedDatabases)){$UfdbUsedDatabases=array();}
    if(!isset($UfdbUsedDatabases["MISSING"]) || !is_array($UfdbUsedDatabases["MISSING"])){$UfdbUsedDatabases["MISSING"]=array();}
    $CATMEM=FILL_CATEGORIES_MEM();
    $SKIPPED=array();
    if(isset($CATMEM["categories_descriptions"]["SKIPPED"]) && is_array($CATMEM["categories_descriptions"]["SKIPPED"])){
        $SKIPPED=$CATMEM["categories_descriptions"]["SKIPPED"];
    }
    foreach ($UfdbUsedDatabases["MISSING"] as $category_id=>$none){
        if(isset($SKIPPED[$category_id])){continue;}
        $name=(string)$catz->CategoryIntToStr($category_id);
        if($name===""){$name="#".intval($category_id);}
        $f[]=$name;
    }
    if(count($f)==0){return true;}
    // Amber banner of the redesign: what is missing, its consequence, and the action that fixes it.
    $list=htmlspecialchars(implode(", ",$f),ENT_QUOTES,"UTF-8");
    $list=str_replace(array("{","}"),array("&#123;","&#125;"),$list);
    $t=str_replace(array("%N%","%S%"),array((string)count($f),$list),ufdbr_txt(count($f)==1?"ufdbr_missing_1":"ufdbr_missing_n"));
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("<div class='ufdbr-alert amber' role='alert'><span><span class='i'><i class='fas fa-database' aria-hidden='true'></i></span>$t</span>"
        ."<button type='button' class='ufdbr-abtn' OnClick=\"document.location.href='/webfiltering-update-databases'\">{ufdbr_update_db}</button></div>");
    return true;
}

function zoom_error_popup():bool{
    $tpl=new template_admin();
    $RequestedID=intval($_GET["zoom-errors-popup"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/usercur"),true);
    if(!isset($json["Errors"])){
        $html[]=$tpl->div_warning("{errors_was_disapears}");
        echo $tpl->_ENGINE_parse_body( $html);
        return true;
    }
    $jsError=array();
    foreach ($json["Errors"] as $ID=>$jsonString){
        if($RequestedID==$ID){
            $jsError=json_decode($jsonString,true);
        }
    }
    if(count($jsError)==0){
        $html[]=$tpl->div_warning("#$RequestedID: <strong>{errors_was_disapears}</strong>");
        echo $tpl->_ENGINE_parse_body( $html);
        return true;
    }
    if(!isset($jsError["Errors"])){
        $html[]=$tpl->div_warning("#$RequestedID/Errors: <strong>{errors_was_disapears}</strong>");
        echo $tpl->_ENGINE_parse_body( $html);
        return true;
    }
    if(count($jsError["Errors"])==0){
        $html[]=$tpl->div_warning("#$RequestedID/Errors/Count: <strong>{errors_was_disapears}</strong>");
        echo $tpl->_ENGINE_parse_body( $html);
        return true;
    }



    $ico="<i class='".ico_bug."'></i>";
    $TRCLASS="";

    $times=array();
    foreach ($jsError["Errors"] as $index=>$ligne){

        $time=$tpl->time_to_date($ligne["Time"],true);
        $times[$time]=true;
        $subtext="";
        $TRCLASS=null;
        if(is_array($ligne["Params"])){
            $tt=array();
            foreach ($ligne["Params"] as $key=>$value){
                if(strlen($value)<1){continue;}
                $tt[]="<li>$key: $value</li>";
            }
            if(count($tt)>0){
                $subtext="<div style='margin-top:5px;padding-left: 10px'><small class='text-muted'><ul>".@implode(" ", $tt)."</ul></small></div>";
            }
        }

        $tds[]="<tr>";
        $tds[]="<td style='width:1%;vertical-align: top !important;font-weight:bold'>$ico</td>";
        $tds[]="<td style='width:1%;vertical-align: top !important;font-weight:bold' nowrap>{$ligne["type"]}</td>";
        $tds[]="<td style='width:99%;padding-left:5px'><span class='text-danger'>{$ligne["Error"]}</span>$subtext</td>";
        $tds[]="</tr>";

    }
    foreach ($times as $tt=>$none){
        $stimes[]=$tt;
    }
    $stimes_text="&nbsp;|&nbsp;<strong class='text-warning'>{time}: ".@implode(", ", $stimes)."</strong>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
           ."<table id='table-errors-$RequestedID' class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th class='text-capitalize' >&nbsp;</th>";
    $html[]="<th class='text-capitalize' >{type}</th>";
    $html[]="<th class='text-capitalize' data-type='text'>{error}$stimes_text</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $html[]=@implode("\n", $tds);

    $html[]="</tbody>";
    $html[]="</table></div></div></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body( $html);
    return true;
}

function dynamics(){
    // Live refresh of the redesigned cards (ids documented above ufdbr_txt()).
    $tpl=new template_admin();
    $page=CurrentPageName();
    $f=array();
    $live=ufdbr_live();
    if($live["people"]>=0){
        $n=ufdbr_num($live["people"]);
        $chip=str_replace("%N%",$n,ufdbr_txt("ufdbr_chip_people"));
        $f[]="$('#ufdbr-k-people').text(".json_encode($n).");";
        $f[]="$('#ufdbr-chip-people').removeClass('ufdbr-hidden').find('span').text(".json_encode(html_entity_decode($chip,ENT_QUOTES,"UTF-8")).");";
    }
    // Every rule, enabled or not: a rule whose errors disappeared must lose its line too.
    $ENABLED=array(0=>true);
    $qd=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rowsd=$qd->QUERY_SQL("SELECT ID,enabled FROM webfilter_rules");
    if(is_array($rowsd)){
        foreach ($rowsd as $ld){$ENABLED[intval($ld["ID"])]=intval($ld["enabled"])==1;}
    }
    $active=0;
    foreach($ENABLED as $ID=>$enabled){
        $ID=intval($ID);
        $req=intval($live["requests"][$ID] ?? 0);
        if($ID>0 && $enabled && $req>0){$active++;}
        if($enabled){
            $html=base64_encode($tpl->_ENGINE_parse_body(ufdbr_status_html(true,$live["known"],$req)));
            $f[]="$('#td-status-$ID').html(base64_decode('$html'));";
        }
        $err=intval($live["errors"][$ID] ?? 0);
        $eh=$err>0?base64_encode($tpl->_ENGINE_parse_body(ufdbr_errors_html($ID,$err))):"";
        $f[]="$('#ufdbr-err-$ID').html(".($eh===""?"''":"base64_decode('$eh')").");";
    }
    if($live["known"]){$f[]="$('#ufdbr-k-active').text('$active');";}
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
}
