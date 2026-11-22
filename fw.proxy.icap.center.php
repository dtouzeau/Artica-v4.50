<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["enabled"])){enabled();exit;}
if(isset($_GET["bypass"])){bypass();exit;}
if(isset($_GET["move-item-js"])){move_items_js();exit;}
if(isset($_POST["move-item"])){move_items();exit;}
if(isset($_GET["ruleid-delete"])){ruleid_delete();exit;}
if(isset($_POST["ruleid-delete"])){ruleid_delete_confirm();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-start"])){table_start();exit;}

page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{icap_center}",
        "fas fa-plug","{icap_center_explain}","$page?tabs=yes","icap-center",
        "progress-icap-restart",false,"table-loader-icap-tabs");



    if(isset($_GET["main-page"])){$tpl=new template_admin("{icap_center}",$html);
    echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);

}
function table_start(){
    $page=CurrentPageName();
    echo "<div id='table-loader-icap-pages'></div>";
    echo "<script>LoadAjax('table-loader-icap-pages','$page?table=yes');</script>";

}
function ruleid_delete():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["md"];
    $id=intval($_GET["ruleid-delete"]);
    $ligne=acls_api_get("/proxy/icap/services/$id");
    if(!is_array($ligne)){
        return $tpl->js_mysql_alert("ICAP service #$id not found");
    }
   $text="{$ligne["ipaddr"]}:{$ligne["listenport"]}/{$ligne["service_name"]}";
    return $tpl->js_confirm_delete("$text","ruleid-delete",$id,"$('#$md').remove();");
}
function ruleid_delete_confirm():bool{
    $id=intval($_POST["ruleid-delete"]);
    $ligne=acls_api_get("/proxy/icap/services/$id");
    $text=is_array($ligne)?"{$ligne["ipaddr"]}:{$ligne["listenport"]}/{$ligne["service_name"]}":"#$id";
    list($ok,$data)=acls_api_send("DELETE","/proxy/icap/services/$id");
    if(!$ok){
        echo is_string($data)?$data:"API error";
        return false;
    }
    return admin_tracks("Remove ICAP service $text");
}

function tabs(){
//
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{icap_center}"]="$page?table-start=yes";
    $array["ICAP {policies}"]="fw.proxy.icap.acls.php?table-start=yes";
    echo $tpl->tabs_default($array);
}

function item_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-js"]);
    $ligne=acls_api_get("/proxy/icap/services/$ID");
    $title=is_array($ligne)?$ligne["service_name"]:"";
    $tpl->js_dialog1($title,"$page?item-popup=$ID",990);
}


function enabled(){
    $ID=intval($_GET["enabled"]);
    $tpl=new template_admin();
    $ligne=acls_api_get("/proxy/icap/services/$ID");
    if(!is_array($ligne)){echo $tpl->js_mysql_alert("ICAP service #$ID not found");return false;}
    $service_name=$ligne["service_name"];
    if(intval($ligne["enabled"])==1){
        list($ok,$data)=acls_api_send("PUT","/proxy/icap/services/$ID",["enabled"=>0]);
        if(!$ok){echo $tpl->js_mysql_alert($data);return false;}
        admin_tracks("Change ICAP service $service_name to disabled");
    }else{
        list($ok,$data)=acls_api_send("PUT","/proxy/icap/services/$ID",["enabled"=>1]);
        if(!$ok){echo $tpl->js_mysql_alert($data);return;}
        admin_tracks("Change ICAP service $service_name to enabled");
    }

    if($ID==1){
        $page=CurrentPageName();
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/icap");
        header("content-type: application/x-javascript");
        echo "if(document.getElementById('cicap-main-status-start') ){\n";
        echo "\tLoadAjax('cicap-main-status-start','fw.proxy.c-icap.php?main-status=yes');\n";
        echo "}\n";
        echo "if(document.getElementById('table-loader-icap-pages') ){\n";
        echo "\tLoadAjax('table-loader-icap-pages','$page?table=yes');\n";
        echo "}\n";
    }

    return true;
}

function bypass(){
    $ID=intval($_GET["bypass"]);
    $tpl=new template_admin();
    $ligne=acls_api_get("/proxy/icap/services/$ID");
    if(!is_array($ligne)){echo $tpl->js_mysql_alert("ICAP service #$ID not found");return false;}
    $service_name=$ligne["service_name"];
    // Toggle on the *bypass* value. The legacy code tested $ligne["enabled"]
    // while only selecting bypass, so enabled was always unset (0) and bypass
    // could never be turned back off — fixed here to flip the real flag.
    if(intval($ligne["bypass"])==1){
        list($ok,$data)=acls_api_send("PUT","/proxy/icap/services/$ID",["bypass"=>0]);
        if(!$ok){echo $tpl->js_mysql_alert($data);return false;}
        admin_tracks("Change ICAP service $service_name to not bypass when encounter error");
    }else{
        list($ok,$data)=acls_api_send("PUT","/proxy/icap/services/$ID",["bypass"=>1]);
        if(!$ok){echo $tpl->js_mysql_alert($data);return false;}
        admin_tracks("Change ICAP service $service_name to bypass if error");
    }
    return true;
}

function move_items_js(){
    $page=CurrentPageName();
     $t=time();
    header("content-type: application/x-javascript");
    // Pentest 2026-11-04 : ID/t/dir etaient recopies sans encodage dans la page (XSS).
    $mvID=intval($_GET["ID"] ?? 0);
    $mvT=req_js($_GET["t"] ?? "");
    $mvDir=intval($_GET["dir"] ?? 0);
    $html="

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','$mvID');
	XHR.appendData('t','$mvT');
	XHR.appendData('dir','$mvDir');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();
";

    echo $html;

}
function move_items(){
    // The daemon's move endpoint swaps with the neighbour and renumbers all
    // rows in one transaction. dir convention (matching moveSimpleRule and the
    // sibling fw.proxy.icap.acls.php): 1 = up (smaller zOrder), 0 = down.
    $ID=intval($_POST["move-item"]);
    $dir=intval($_POST["dir"]);
    list($ok,$data)=acls_api_send("PUT","/proxy/icap/services/$ID/move",["dir"=>$dir]);
    if(!$ok){echo is_string($data)?$data:"API error";}
}
function save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $ID=intval($_POST["ID"]);
    $service_name=$_POST["service_name"];

    if($_POST["service_key"]==null){
        $_POST["service_key"]=md5("{$_POST["address"]}{$_POST["listenport"]}{$_POST["respmod"]}{$_POST["icap_server"]}");
    }

    // Form field "address" maps to the ipaddr column. Parameterized server-side,
    // so no SQL escaping is needed here anymore.
    $body=array(
        "service_name"=>$service_name,
        "service_key"=>$_POST["service_key"],
        "respmod"=>$_POST["respmod"],
        "routing"=>intval($_POST["routing"]),
        "bypass"=>intval($_POST["bypass"]),
        "enabled"=>intval($_POST["enabled"]),
        "zOrder"=>intval($_POST["zOrder"]),
        "ipaddr"=>$_POST["address"],
        "listenport"=>intval($_POST["listenport"]),
        "icap_server"=>$_POST["icap_server"],
        "maxconn"=>intval($_POST["maxconn"]),
        "overload"=>$_POST["overload"],
    );

    if($ID==0){
        list($ok,$data)=acls_api_send("POST","/proxy/icap/services",$body);
    }else{
        list($ok,$data)=acls_api_send("PUT","/proxy/icap/services/$ID",$body);
    }
    if(!$ok){echo (new template_admin())->post_error("".(is_string($data)?$data:"API error"));return;}

}





function item_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-popup"]);
    $bt="{add}";
    $ligne=array();
    $ligne["enabled"]=1;
    $ligne["zOrder"]=0;
    if($ID>0) {
        $row=acls_api_get("/proxy/icap/services/$ID");
        if(is_array($row)){$ligne=$row;}
        $bt = "{apply}";
    }

    if($ligne["overload"]==null){$ligne["overload"]="bypass";}

    $respmod["reqmod_precache"]="REQMOD";
    $respmod["respmod_precache"]="RESPMOD";

    $overload["block"]="{block}";
    $overload["bypass"]="{bypass}";
    $overload["wait"]="{wait}";
    $overload["force"]="{force}";

    $tpl->field_hidden("ID",$ID);
    $tpl->field_hidden("service_key",$ligne["service_key"]);
    if($ID==1 OR $ID == 2){
        $form[] = $tpl->field_hidden("service_name",  $ligne["service_name"]);
    }else {
        $form[] = $tpl->field_text("service_name", "{service_name}", $ligne["service_name"]);
    }
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],True);
    $form[]=$tpl->field_numeric("zOrder","{order}",$ligne["zOrder"]);

    if($ID==1 or $ID==2){
        $ListenAddress=CICAP_Addr();
        $form[]=$tpl->field_info("address","{address}",$ListenAddress);
        $form[]=$tpl->field_info("listenport","{listen_port}","1345");
        $form[]=$tpl->field_info("icap_server","{icap_service_name}","srv_clamav");
        if($ID==1){
            $form[]=$tpl->field_hidden("respmod","respmod_precache");
        }
        if($ID==2){
            $form[]=$tpl->field_hidden("respmod","reqmod_precache");
        }
    }else {
        $form[] = $tpl->field_ipv4("address", "{address}", $ligne["ipaddr"]);
        $form[] = $tpl->field_numeric("listenport", "{listen_port}", $ligne["listenport"]);
        $form[] = $tpl->field_text("icap_server", "{icap_service_name}", $ligne["icap_server"]);
        $form[] = $tpl->field_array_hash($respmod, "respmod", "{type}", $ligne["respmod"]);
    }
    $form[]=$tpl->field_array_hash($overload,"overload","{if_overloaded}",$ligne["overload"]);
    $form[]=$tpl->field_checkbox("routing","X-Next-Services",$ligne["routing"],False);
    $form[]=$tpl->field_checkbox("bypass","{bypass}",$ligne["bypass"],False);
    $form[]=$tpl->field_numeric("maxconn","{max_connections}",$ligne["maxconn"]);

    $reconf=reconfigure_progress();
    $js="LoadAjax('table-loader-icap-pages','$page?table=yes');dialogInstance1.close();";
    echo $tpl->form_outside($ligne["service_name"],$form,null,$bt,$js,"AsSquidAdministrator");


}

function reconfigure_progress(){
    $page           = CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    $ARRAY["CMD"]="/proxy/icap/builder";
    $ARRAY["AFTER"]="LoadAjax('table-loader-icap-pages','$page?table=yes');";
    $ARRAY["TITLE"]="{apply}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-icap-restart')";
    return $jsrestart;
}

function CICAP_Addr():string{
    $tpl=new template_admin();
    $CICAPListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPListenInterface"));
    if($CICAPListenInterface==null){$CICAPListenInterface="lo";}
    if($CICAPListenInterface=="lo"){return "127.0.0.1";}
    $ListenAddress = $tpl->InterfaceTOIP($CICAPListenInterface);
    if ($ListenAddress == null) {
        $ListenAddress = "127.0.0.1";
    }
    if(preg_match("#^127\.0\.0#",$ListenAddress)){return "127.0.0.1";}
    return $ListenAddress;
}

function table(){
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $add            = "Loadjs('$page?item-js=0');";
    $jsrestart      = reconfigure_progress();
    $t=time();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cicap.php?clients-scan=yes");

    $topbuttons[] = array($add,ico_plus,"{new_service}");
    $topbuttons[] = array($jsrestart,ico_save,"{apply_rules}");





    // Neuf colonnes dont quatre d'une icone chacune (contournement / deplacer /
    // actif / supprimer). Le nom, l'adresse:port et les messages d'etat sont
    // regroupes dans la colonne d'identite; les icones dans une colonne
    // d'actions.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{service_name}</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th class='center'>{mode}</th>";
    $html[]="<th class='center'>{order}</th>";
    $html[]="<th class='center'>{bypass}</th>";
    $html[]="<th class='center'>{actions}</th>";
    $html[]="</tr></thead><tbody id='ic-rows'>";

    $results=acls_api_get("/proxy/icap/services");
    if(!is_array($results)){$results=array();}


    $STATUS_ARRAY=icap_status_badges();

    $RESMODE["respmod_precache"]="{response}";
    $RESMODE["reqmod_precache"]="{request}";
    $html[]=cicap_status();
    $ROWS=1;
    // unserialize() rend FALSE quand le reglage est vide: isset() sur un
    // booleen est faux, donc l'ancien code marquait tous les services
    // "inactifs" sans avertissement. Le tableau vide donne le meme resultat
    // mais sans dependre d'un type inattendu.
    $C_ICAP_CLIENTS_SCAN=unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_CLIENTS_SCAN"));
    if(!is_array($C_ICAP_CLIENTS_SCAN)){$C_ICAP_CLIENTS_SCAN=array();}


    foreach ($results as $index=>$ligne){
        // The API returns the PK as lowercase "id"; alias it to "ID" so the
        // existing downstream {$ligne['ID']} references keep working.
        $ID=intval($ligne["id"]);
        $ligne["ID"]=$ID;
        if($ID==1 OR $ID == 2){continue;}
        $ROWS++;
        $md=md5(serialize($ligne).$index);
        // dir: 1 = up (smaller zOrder), 0 = down — matches the daemon move endpoint.
        $up=$tpl->icon_up("Loadjs('$page?move-item-js=yes&ID=$ID&dir=1')","AsProxyMonitor");
        $down=$tpl->icon_down("Loadjs('$page?move-item-js=yes&ID=$ID&dir=0')","AsProxyMonitor");
        $del=$tpl->icon_delete("Loadjs('$page?ruleid-delete=$ID&md=$md')","AsSquidAdministrator");
        $bypass=$tpl->icon_check($ligne["bypass"],"Loadjs('$page?bypass=$ID')",null,"AsProxyMonitor");
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled=$ID')",null,"AsProxyMonitor");

        if($ID<20){$del=$tpl->icon_nothing();}
        // Un bloc reaffectait ici ipaddr/listenport pour les identifiants 1 et 2,
        // alors que le "continue" ci-dessus les a deja ecartes: code mort.
        // Le grisage des services desactives passait par une variable $color qui
        // n'etait JAMAIS remise a zero entre deux tours de boucle: des qu'un
        // service desactive etait rendu, tous les suivants heritaient du gris.

        if($ligne["enabled"]==1){
            if($ligne["status"]<5){
                if(!isset($C_ICAP_CLIENTS_SCAN[$ID])){
                    $ligne["status"]=4;
                }
            }
        }
        $status=$STATUS_ARRAY[intval($ligne["status"])] ?? $STATUS_ARRAY[0];

        // Le nom du service et l'adresse viennent de la base et partaient BRUTS
        // dans le HTML.
        $html[]=icap_row(array(
            "id"      =>$md,
            "off"     =>(intval($ligne["enabled"])==0),
            "name"    =>$tpl->td_href(htmlspecialchars((string)$ligne["service_name"]),null,"Loadjs('$page?item-js=$ID');"),
            "meta"    =>htmlspecialchars((string)$ligne["ipaddr"].":".(string)$ligne["listenport"]),
            "status"  =>$status,
            "mode"    =>($RESMODE[$ligne["respmod"]] ?? "{all}"),
            "order"   =>intval($ligne["zOrder"]),
            "bypass"  =>$bypass,
            "actions" =>"$up$down$enabled$del"));
    }

    // L'ancienne version placait le pied A L'INTERIEUR du corps du tableau.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $ROWS</span>";
    $html[]="<span class='awc-pg' id='ic-pager'></span>";
    $html[]="</div></div></div>";

    $TINY_ARRAY["TITLE"]="{icap_center}";
    $TINY_ARRAY["ICO"]="fas fa-plug";
    $TINY_ARRAY["EXPL"]="{icap_center_explain}";
    $TINY_ARRAY["URL"]="icap-center";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Feuille de style, script et pager concatenes APRES l'analyse: la regex
    // #{(.+?)}# de _ENGINE_parse_body prend chaque corps d'accolade CSS ou
    // JavaScript pour un {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>"
        .awc_pager_js(50,"ic-rows","ic-pager");
}

/**
 * Pastilles d'etat d'un service ICAP.
 * Elles etaient declarees deux fois, a l'identique, dans table() et dans
 * cicap_status(). Les <span class=label> Bootstrap sans color:#fff rendaient
 * un texte gris sur fond sombre.
 */
function icap_status_badges():array{
    return array(
        0=>"<span class='awc-badge awc-badge-off'>{disabled}</span>",
        1=>"<span class='awc-badge awc-badge-on'>{active2}</span>",
        2=>"<span class='awc-badge' style='background:#ba1a1a'>{error}</span>",
        3=>"<span class='awc-badge' style='background:#865300'>{error}</span>",
        4=>"<span class='awc-badge' style='background:#865300'>{inactive}</span>",
        5=>"<span class='awc-badge' style='background:#7d1218'>{emergency2}</span>");
}

/**
 * Etiquette d'un module c-icap (antimalware, bac a sable).
 */
function icap_module_chip($name,$on):string{
    if($on){return "<span class='awc-badge awc-badge-on'>$name</span>";}
    return "<span class='awc-badge awc-badge-off'>$name [OFF]</span>";
}

/**
 * Un service ICAP.
 * Les trois producteurs de lignes (service integre, service integre desactive,
 * services declares) repetaient les memes neuf cellules. Le nom, l'adresse:port,
 * les messages d'etat et les etiquettes de modules tiennent dans la colonne
 * d'identite; contournement mis a part, les icones vont dans la colonne
 * d'actions.
 * Les valeurs arrivent deja construites par l'appelant: elles ne peuvent pas
 * etre echappees ici.
 */
function icap_row($r):string{
    $meta=trim((string)$r["meta"]);
    $off=!empty($r["off"]);
    $h ="<tr id='{$r["id"]}'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($off?" awc-ico-off":"")."'><i class='fas fa-plug'></i></span>";
    $h.="<span><span class='awc-name".($off?" awc-name-off":"")."'>{$r["name"]}</span>";
    if($meta<>""){$h.="<div class='awc-meta'>$meta</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center awc-nowrap'>{$r["status"]}</td>";
    $h.="<td class='center'>".($r["mode"]<>""?$r["mode"]:"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="<td class='center awc-mono'>{$r["order"]}</td>";
    $h.="<td class='center'>{$r["bypass"]}</td>";
    $h.="<td><div class='awc-acts'>{$r["actions"]}</div></td>";
    $h.="</tr>";
    return $h;
}

function cicap_status():string{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $error_gen  = null;
    $icap_info  = null;
    $STATUS     = 1;
    $CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
    $ListenAddress=CICAP_Addr();



    $STATUS_ARRAY=icap_status_badges();




    if($CicapEnabled==0) {
        // L'ancienne version ecrivait ici <strongclass='text-muted'> (espace
        // manquant) et fermait un </span> jamais ouvert.
        return icap_row(array(
            "id"      =>"none",
            "off"     =>true,
            "name"    =>"{SERVICE_WEBAVEX}",
            "meta"    =>htmlspecialchars("$ListenAddress:1345"),
            "status"  =>$STATUS_ARRAY[0],
            "mode"    =>"",
            "order"   =>0,
            "bypass"  =>$tpl->icon_nothing(),
            "actions" =>$tpl->icon_nothing()));
    }
    $MAIN=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cicap/check"));
    if(isset($json->data)){
        if(strlen($json->data->error)>0){$MAIN["ERROR"]=$json->data->error;}
        if(strlen($json->data->service)>0){$MAIN["SERVICE"]=$json->data->service;}
    }

    if(isset($MAIN["ERROR"])){
        $STATUS=2;
        $error_gen= "<br>".htmlspecialchars($MAIN["ERROR"]);
    }

    if(isset($MAIN["SERVICE"])){
        $MAIN["SERVICE"]=str_replace(" - Url_Check demo service","",$MAIN["SERVICE"]);
        $icap_info=" <small>(".htmlspecialchars($MAIN["SERVICE"]).")</small>";

    }


    $ligne=acls_api_get("/proxy/icap/services/1");
    if(!is_array($ligne)){$ligne=array("enabled"=>0,"bypass"=>0);}
    if(intval($ligne["enabled"] ?? 0)==0){$STATUS=4;}

    if(!isset($MAIN["ERROR"])) {
        $fp = @fsockopen($ListenAddress, "1345", $errno, $errstr, 1);
        if (!$fp) {
            $textclassip = "text-danger";
            $STATUS = 2;
            $error_gen = "<br>$errstr";
        }
    }

    if($ligne["enabled"]==1){
        $enabled="
            <a href='#' OnClick=\"javascript:Loadjs('$page?enabled=1');\">
            <span class='label label-primary'>{running}</span>
            </a>";
    }else{
        $enabled="
            <a href='#' OnClick=\"javascript:Loadjs('$page?enabled=1');\">
            <span class='label label-danger'>{stopped}</span>
            </a>";
    }

    $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
    $CICAPEnableSandBox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPEnableSandBox"));
    if($ligne["enabled"]==0){$EnableClamavInCiCap=0;$CICAPEnableSandBox=0;}
    // Les deux etiquettes de modules tenaient dans un <table style='width:5%'>
    // de MISE EN PAGE d'une seule ligne.
    $modules=array();
    $modules[]=icap_module_chip("antimalware",$EnableClamavInCiCap==1);
    $modules[]=icap_module_chip("SandBox",$CICAPEnableSandBox==1);
    $mmods="<div style='margin-top:5px'>".@implode("&nbsp;",$modules)."</div>";


    $bypass=$tpl->icon_check($ligne["bypass"],"Loadjs('$page?bypass=1')",null,"AsProxyMonitor");
    $link=$tpl->td_href("{SERVICE_WEBAVEX}",null,"Loadjs('$page?item-js=1');");
    return icap_row(array(
        "id"      =>"ICAPAV",
        "off"     =>false,
        "name"    =>"<span class='$textclassip'>$link</span>",
        "meta"    =>htmlspecialchars("$ListenAddress:1345").$icap_info.$error_gen.$mmods,
        "status"  =>$STATUS_ARRAY[$STATUS],
        "mode"    =>"{all}",
        "order"   =>0,
        "bypass"  =>$bypass,
        "actions" =>$enabled));



}