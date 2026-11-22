<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["item-enable"])){item_enable();exit;}
if(isset($_GET["move-item-js"])){move_items_js();exit;}
if(isset($_GET["ruleid-delete"])){item_delete();exit;}
if(isset($_POST["item-delete"])){item_delete_perform();exit;}
if(isset($_GET["dumpcatz-js"])){dumpcatz_js();exit;}
if(isset($_GET["dumpcatz-popup"])){dumpcatz_popup();exit;}

if(isset($_GET["settings-js"])){settings_js();exit;}
if(isset($_GET["settings-popup"])){settings_popup();exit;}
if(isset($_POST["HardCategoriesRemove"])){settings_save();exit;}


page();

function settings_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{settings}");
    $tpl->js_dialog7($title, "$page?settings-popup=yes",810);
}

function settings_popup(){
    $tpl=new template_admin();
    $HardCategoriesRemove=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HardCategoriesRemove"));
    $jsafter="dialogInstance7.close();";

    $form[]=$tpl->field_checkbox("HardCategoriesRemove","{remove_hardcoded_categories}",$HardCategoriesRemove,false,"{remove_hardcoded_categories_explain}");
    $html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}","$jsafter","AsSquidAdministrator");
    $html[]="";
    echo $tpl->_ENGINE_parse_body($html);

}
function settings_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{categories_service}</h1><p>{categories_services_explain}</p></div>
	</div>
	<div class='row'>
	    <div id='progress-catz-restart'></div>
    </div>
    <div class='row'>
        <div class='ibox-content'>
    	    <div id='table-loader-catz-pages'></div>
        </div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/categories-services');
	LoadAjax('table-loader-catz-pages','$page?table=yes');
	</script>";

    if(isset($_GET["main-page"])){$tpl=new template_admin("{categories_services}",@implode("\n", $html));
        echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function dumpcatz_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["dumpcatz-js"]);
    $tpl->js_dialog6("{categories}","$page?dumpcatz-popup=$ID",990);

}

function item_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-js"]);
    if($ID>0) {
        $results = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CATEGORIES_SERVICES"));
        $hostname_ti = $results[$ID]["hostname"];
    }else{
        $hostname_ti="{new_service}";
    }
    $tpl->js_dialog1($hostname_ti,"$page?item-popup=$ID",990);
}


function item_delete(){
    $tpl=new template_admin();
    // $service_id partait brut dans le titre ET dans la valeur POST; $md, brut
    // dans un selecteur jQuery. Les deux viennent de l'URL. $md est toujours un
    // md5, l'identifiant toujours un entier.
    $service_id=intval($_GET["ruleid-delete"]);
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));

    $js="$('#$md').remove();";
    $tpl->js_confirm_delete("{service} $service_id","item-delete",$service_id,$js);
}

function item_delete_perform(){

    // $_POST["item-delete"] partait BRUT dans deux requetes SQL (PostgreSQL puis
    // SQLite): injection directe depuis le navigateur.
    $ID=intval($_POST["item-delete"]);
    if($ID==0){return;}
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM personal_categories WHERE serviceid=$ID");
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM categories_services WHERE ID=$ID");
    $catz=new mysql_catz();
    $catz->BuildDnsRemotes();
}

function dumpcatz_popup(){
    $q=new postgres_sql();
    $tpl=new template_admin();
    $remotecatz=intval($_GET["dumpcatz-popup"]);

    // L'ancienne version ouvrait <thead> sans le refermer, fermait un </tbody>
    // jamais ouvert, et placait le pied dans le corps du tableau.
    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th class='center'>{ID}</th>";
    $html[]="<th>{category}</th>";
    $html[]="</tr></thead><tbody id='dcz-rows'>";

    $results= $q->QUERY_SQL("SELECT * FROM personal_categories WHERE remotecatz=$remotecatz ORDER BY category_id");
    $c=0;
    while($ligne=@pg_fetch_assoc($results)) {
        $c++;
        $html[]=dumpcatz_row($ligne);
    }
    if($c==0){
        $html[]="<tr><td colspan='2' class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c</span>";
    $html[]="<span class='awc-pg' id='dcz-pager'></span>";
    $html[]="</div></div></div>";

    // Feuille de style et pager concatenes APRES l'analyse: la regex #{(.+?)}#
    // de _ENGINE_parse_body prend chaque corps d'accolade CSS ou JavaScript
    // pour un {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
        .awc_pager_js(50,"dcz-rows","dcz-pager");

}

/**
 * Une categorie personnalisee d'un service distant.
 * Les deux champs viennent de PostgreSQL et partaient bruts dans le HTML.
 */
function dumpcatz_row($ligne):string{
    $h ="<tr>";
    $h.="<td class='center awc-mono'>".htmlspecialchars((string)($ligne["category_id"] ?? ""))."</td>";
    $h.="<td><span class='awc-name'>".htmlspecialchars((string)($ligne["categoryname"] ?? ""))."</span></td>";
    $h.="</tr>";
    return $h;
}



function move_items_js(){

    $direction=$_GET["dir"];
    // $ID partait BRUT dans quatre requetes SQL: injection directe depuis l'URL.
    $ID=intval($_GET["ID"]);
    if($ID==0){return;}
    $table="categories_services";


    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT zorder FROM $table WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!is_array($ligne)){return;}


    $OlOrder=intval($ligne["zorder"]);
    if($direction=="down"){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
    $sql="UPDATE $table SET zorder='$OlOrder' WHERE zorder='$NewOrder'";


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}

    $sql="UPDATE $table SET zorder='$NewOrder' WHERE ID='$ID'";
    $q->QUERY_SQL($sql);

    if(!$q->ok){echo $q->mysql_error;}

    // La renumerotation finale ne fonctionnait pas: lib_sqlite::QUERY_SQL rend
    // un TABLEAU de lignes, pas une ressource -- mysqli_fetch_assoc() dessus
    // leve une TypeError en PHP 8 -- et le compteur $c n'etait jamais
    // incremente, si bien que chaque service aurait recu zorder=1.
    $results=$q->QUERY_SQL("SELECT ID FROM $table ORDER BY zorder");
    if(is_array($results)){
        $c=0;
        foreach($results as $row){
            if(!is_array($row)){continue;}
            $c++;
            $rowID=intval($row["ID"]);
            if($rowID==0){continue;}
            $q->QUERY_SQL("UPDATE $table SET zorder='$c' WHERE ID='$rowID'");
        }
    }

    $catz=new mysql_catz();
    $catz->BuildDnsRemotes();
}
function item_enable(){
    $ID=intval($_GET["item-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM categories_services WHERE ID='$ID'");
    if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}

    $enabled=intval($ligne["enabled"]);
    if($enabled==1){$enabled=0;}else{$enabled=1;}
    $sql="UPDATE categories_services SET enabled=$enabled WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}

    if($enabled==0){
        $q=new postgres_sql();
        $q->QUERY_SQL("DELETE FROM personal_categories WHERE serviceid=$ID");
    }

    $catz=new mysql_catz();
    $catz->BuildDnsRemotes();

}



function item_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-popup"]);
    $bt="{add}";
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM categories_services WHERE ID=$ID");

    if($ID>0){
        $bt="{apply}";
    }

    if (intval($ligne["port"]) == 0) {$ligne["port"] = 3477;}
    if(trim($ligne["domain"])==null){$ligne["domain"]="categories.tld";}

    $tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_text("hostname","{address}",$ligne["hostname"]);
    $form[]=$tpl->field_numeric("port","{listen_port}",$ligne["port"]);
    $form[]=$tpl->field_text("domain","{domain}",$ligne["domain"]);
    $form[]=$tpl->field_checkbox("crypt","{decrypt}",$ligne["crypt"],False);
    $form[]=$tpl->field_text("passphrase","{passphrase}",$ligne["passphrase"],False);



    $js="LoadAjax('table-loader-catz-pages','$page?table=yes');dialogInstance1.close();";
    echo $tpl->form_outside("{service}",$form,null,$bt,$js,"AsSquidAdministrator");


}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ID=intval($_POST["ID"]);
    $port=$_POST["port"];
    $hostname=$_POST["hostname"];
    $domain=$_POST["domain"];
    $crypt=intval($_POST["crypt"]);
    $passphrase=$_POST["passphrase"];



    // Pentest 2026-11-04 : hostname/port/domain/passphrase (POST) etaient concatenes dans la requete.
    if($ID==0){
        $q->QUERY_SQL_PARAMS("INSERT INTO categories_services (hostname,port,domain,crypt,passphrase,enabled) 
        VALUES (?,?,?,?,?,1)",array($hostname,$port,$domain,$crypt,$passphrase));

        if(!$q->ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($q->mysql_error));return;}
        $catz=new mysql_catz();
        $catz->BuildDnsRemotes();
        return;
    }

    $q->QUERY_SQL_PARAMS("UPDATE categories_services SET 
    hostname=?,
    port=?,
    domain=?,
    crypt=?,
    passphrase=? WHERE ID=?",array($hostname,$port,$domain,$crypt,$passphrase,$ID));

    if(!$q->ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($q->mysql_error));return;}
    $catz=new mysql_catz();
    $catz->BuildDnsRemotes();

}

function table(){
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $add="Loadjs('$page?item-js=');";

    $sql="CREATE TABLE IF NOT EXISTS `categories_services` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`port` INTEGER NOT NULL DEFAULT 0,
		`hostname` TEXT NULL,
		`domain` TEXT NOT NULL,
		`zorder` INTEGER NOT NULL DEFAULT 0,        
		`crypt` INTEGER NOT NULL DEFAULT 0,
		`passphrase` TEXT NULL,
		`enabled`  INTEGER NOT NULL DEFAULT 1)";

    $q->QUERY_SQL($sql);
    $catz->BuildDnsRemotes();
    $UseCloudArticaCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseCloudArticaCategories"));
    $useCGuardCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCGuardCategories"));
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/categories.services.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/categories.services.log";
    $ARRAY["CMD"]="dnscat.php?sync=yes";
    $ARRAY["TITLE"]="{synchronize}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-catz-pages','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-catz-restart')";
    $t=time();

    $config["PROGRESS_FILE"]=PROGRESS_DIR."/filebeat.progress";
    $config["LOG_FILE"]=PROGRESS_DIR."/filebeat.log";
    $config["CMD"]="filebeat.php?cloud-uninstall=yes";
    $config["TITLE"]="{UseCloudArticaCategories}";
    $config["AFTER"]="dialogInstance4.close();if(document.getElementById('table-loader-catz-pages')){LoadAjax('table-loader-catz-pages','fw.proxy.categories.services.php?table=yes');}";
    $prgress=base64_encode(serialize($config));
    // Il y avait ici un guillemet simple en trop apres progress-catz-restart:
    // le OnClick ne se compilait pas et le bouton de desinstallation du service
    // de categories Artica ne faisait rien du tout, sans message.
    $CloudUninstall="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-catz-restart')";

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"LoadAjax('table-loader-catz-pages','$page?table=yes');\"><i class='fas fa-sync'></i> {refresh} </label>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_service} </label>";

    if($UseCloudArticaCategories==0){
        $html[]="<label class=\"btn btn btn-info\" 
        OnClick=\"Loadjs('fw.proxy.use.cloud.categories.php');\"><i class=\"fa fa-plus\"></i> {UseCloudArticaCategories} </label>";

    }else{
        $html[]="<label class=\"btn btn btn-danger\" 
        OnClick=\"$CloudUninstall\"><i class=\"fa fa-minus\"></i> {DisableCloudArticaCategories} </label>";
    }

    $html[]="<label class=\"btn btn btn-primary\" 
        OnClick=\"Loadjs('fw.proxy.use.cguard.categories.php');\"><i class=\"fa fa-plus\"></i> {use_lemnia_cloud_service} </label>";



    $html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fas fa-sync'></i> {synchronize} </label>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?settings-js=yes');\"><i class='far fa-sliders-v'></i> {options} </label>";

    $html[]="</div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\"></div>";




    // Neuf colonnes dont trois d'une icone chacune (ordre / actif / supprimer).
    // Le nom, le sous-titre hote:port et le message d'erreur sont regroupes
    // dans la colonne d'identite; les trois icones dans une colonne d'actions.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{service_name}</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th class='center'>{crypted}</th>";
    $html[]="<th class='center'>{items}</th>";
    $html[]="<th class='center'>{categories}</th>";
    $html[]="<th class='center'>{last_update}</th>";
    $html[]="<th class='center'>{actions}</th>";
    $html[]="</tr></thead><tbody id='cts-rows'>";

    $ROWS=0;
    $STATUS_ARRAY=catzsrv_status_badges();



    $EnableLocalUfdbCatService = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));


    if($useCGuardCategories==1){
        $lastUpdate_text="&nbsp;";
        // unserialize() rend FALSE quand le reglage est vide ou illisible:
        // count(false) leve une TypeError en PHP 8 et l'acces $x["99999999"]
        // sur un booleen emet un avertissement.
        $CguardCatzData=unserialize(base64_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CguardCatzData")));
        if(!is_array($CguardCatzData)){$CguardCatzData=array();}
        // Enregistrement de tete lu SANS l'ajouter au tableau: $CategoriesCount
        // plus bas compte les entrees, une cle injectee ici la fausserait.
        $head=$CguardCatzData["99999999"] ?? array();
        if(!is_array($head)){$head=array();}
        $Crypted="<i class='fas fa-check'></i>";
        $NAME="{use_lemnia_cloud_service}";
        $Categories=0;
        $CategoriesCount=0;
        $ico_enabled="<i class='fas fa-check'></i>";

        $DnscatzDomain=(string)($head["DOMAIN"] ?? "");
        if($DnscatzDomain==""){$DnscatzDomain="{unknown}";}
        $DnsCatzPort=intval($head["PORT"] ?? 0);
        // Cette valeur etait lue dans $catlist, un tableau local cree juste
        // au-dessus avec la seule cle TIME: elle etait donc TOUJOURS indefinie
        // et la mention "(chiffre)" ne s'affichait jamais. Elle vient du meme
        // enregistrement que DOMAIN et PORT.
        $DnsCatzCrypt=intval($head["CRYPT"] ?? 0);
        if($DnsCatzPort>0){
            $DnscatzDomain=$DnscatzDomain.":$DnsCatzPort";
        }


        if(count($CguardCatzData)==0){
            $status=2;
        }else{
            $CguardCatzTime=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CguardCatzTime");
            $status=1;
            $CategoriesCount=count($CguardCatzData);
            $lastUpdate=$CguardCatzTime;

            foreach ($CguardCatzData as $ID=>$array){
                if(!isset($array["ITEMS"])){continue;}
                $Categories=$Categories + intval($array["ITEMS"]);
            }

            $Categories=$tpl->FormatNumber($Categories);
            $lastUpdate_text=distanceOfTimeInWords($lastUpdate,time(),true);
            $NAME=$tpl->td_href("$NAME","{display}","Loadjs('fw.proxy.use.cguard.categories.php?getlist-js=yes')");
        }

        $ROWS++;
        $html[]=catzsrv_row(array(
            "id"       =>"useCGuardCategories",
            "name"     =>$NAME,
            "meta"     =>$DnscatzDomain.($DnsCatzCrypt==1?" ({crypted})":""),
            "status"   =>$STATUS_ARRAY[$status],
            "crypted"  =>$Crypted,
            "items"    =>$Categories,
            "count"    =>$CategoriesCount,
            "updated"  =>$lastUpdate_text,
            "enabled"  =>$ico_enabled,
            "actions"  =>$tpl->icon_delete("Loadjs('fw.proxy.use.cguard.categories.php?remove-js=yes')","AsSquidAdministrator")));


    }





    $results=$q->QUERY_SQL("SELECT * FROM categories_services ORDER BY zorder");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["ID"]);
        if($ID==0){continue;}
        $port=$ligne["port"];
        $hostname=$ligne["hostname"];
        $domain=$ligne["domain"];
        $zorder=$ligne["zorder"];
        $crypt=$ligne["crypt"];
        $enabled=$ligne["enabled"];
        $md=md5(serialize($ligne));
        $error=null;
        $Categories=$tpl->icon_nothing();
        $lastUpdate_text=$tpl->icon_nothing();
        $CategoriesCount=$tpl->icon_nothing();
        $Crypted            =   "&nbsp;";


        if($domain==null){$domain="{error} {no_domain}";}
        if($crypt==1){$Crypted="<i class='fas fa-check'></i>";}

        if($enabled==0){
            $status=0;
        }else{
            $catz->ufdbcat_remote("articatech-non-existent-domain.zva",$ID);
            if(!$catz->ok){
                $status=2;
                $error="<br><small class='text-danger'>$catz->mysql_error</small>";
            }else{
                if(count($catz->CategoriesList)==0){
                    $status=3;

                }else{
                    $status=1;
                    $Categories=intval($catz->CategoryNumbers);
                    $lastUpdate=intval($catz->CategoryTime);
                    $CategoriesCount=count($catz->CategoriesList);
                    $lastUpdate_text=distanceOfTimeInWords($lastUpdate,time(),true);
                }

            }
        }

        // $domain_addon a disparu: le sous-titre hote:port est desormais rendu
        // par catzsrv_row() dans la colonne d'identite.


        $up=$tpl->icon_up("Loadjs('$page?move-item-js=yes&ID=$ID&dir=up')","AsProxyMonitor");
        $down=$tpl->icon_down("Loadjs('$page?move-item-js=yes&ID=$ID&dir=down')","AsProxyMonitor");
        $del=$tpl->icon_delete("Loadjs('$page?ruleid-delete=$ID&md=$md')","AsSquidAdministrator");
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?item-enable=$ID')",null,"AsProxyMonitor");

        if($CategoriesCount>0){
            $CategoriesCount=$tpl->td_href($CategoriesCount,null,"Loadjs('$page?dumpcatz-js=$ID')");
        }

        // Le domaine vient de SQLite et partait BRUT dans le HTML.
        $ROWS++;
        $html[]=catzsrv_row(array(
            "id"       =>$md,
            "name"     =>$tpl->td_href(htmlspecialchars($domain),null,"Loadjs('$page?item-js=$ID');"),
            "meta"     =>($hostname<>null?htmlspecialchars("$hostname:$port"):"").$error,
            "status"   =>$STATUS_ARRAY[$status],
            "crypted"  =>$Crypted,
            "items"    =>$Categories,
            "count"    =>$CategoriesCount,
            "updated"  =>$lastUpdate_text,
            "enabled"  =>$enabled,
            "actions"  =>"$up$down$del"));
    }


    if($UseCloudArticaCategories==1){
        $NAME="{UseCloudArticaCategories}";
        $catz->ufdbcat_dns_infos();
        $ico_enabled="<i class='fas fa-check'></i>";
        if($catz->CategoryNumbers==0){
            $status=2;
            $Categories=$tpl->icon_nothing();
            $lastUpdate_text=$tpl->icon_nothing();

        }else{
            $status=1;
            $Categories=$tpl->FormatNumber($catz->CategoryNumbers);
            $lastUpdate=$catz->CategoryTime;
            $lastUpdate_text=distanceOfTimeInWords($lastUpdate,time(),true);

        }

        $ROWS++;
        $html[]=catzsrv_row(array(
            "id"       =>"UseCloudArticaCategories",
            "name"     =>$NAME,
            "meta"     =>"",
            "status"   =>$STATUS_ARRAY[$status],
            "crypted"  =>"",
            "items"    =>$Categories,
            "count"    =>150,
            "updated"  =>$lastUpdate_text,
            "enabled"  =>$ico_enabled,
            "actions"  =>""));
    }

    if($ROWS==0){
        $html[]="<tr><td colspan='7' class='awc-empty'>{no_data}</td></tr>";
    }

    // L'ancienne version placait le pied A L'INTERIEUR du corps du tableau.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $ROWS</span>";
    $html[]="<span class='awc-pg' id='cts-pager'></span>";
    $html[]="</div></div></div>";

    // Feuille de style et pager concatenes APRES l'analyse: la regex #{(.+?)}#
    // de _ENGINE_parse_body prend chaque corps d'accolade CSS ou JavaScript
    // pour un {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
        .awc_pager_js(50,"cts-rows","cts-pager");
}

/**
 * Pastilles d'etat d'un service de categories.
 * Les <span class=label> Bootstrap ne portaient pas de color:#fff -- sur fond
 * sombre le texte restait gris.
 */
function catzsrv_status_badges():array{
    return array(
        0=>"<span class='awc-badge awc-badge-off'>{disabled}</span>",
        1=>"<span class='awc-badge awc-badge-on'>{active2}</span>",
        2=>"<span class='awc-badge' style='background:#ba1a1a'>{error}</span>",
        3=>"<span class='awc-badge' style='background:#865300'>{error}</span>");
}

/**
 * Un service de categories (base locale, service Lemnia ou nuage Artica).
 * Les trois producteurs de lignes repetaient les memes neuf cellules.
 * Le nom, le sous-titre hote:port et le message d'erreur tiennent dans la
 * colonne d'identite; ordre, activation et suppression dans une seule colonne
 * d'actions -- c'etaient trois colonnes d'une icone chacune.
 * Les valeurs arrivent deja construites par l'appelant (liens td_href, icones):
 * elles ne peuvent pas etre echappees ici.
 */
function catzsrv_row($r):string{
    $meta=trim((string)$r["meta"]);
    $h ="<tr id='{$r["id"]}'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-list-tree'></i></span>";
    $h.="<span><span class='awc-name'>{$r["name"]}</span>";
    if($meta<>""){$h.="<div class='awc-meta'>$meta</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center awc-nowrap'>{$r["status"]}</td>";
    $h.="<td class='center'>".($r["crypted"]<>""?$r["crypted"]:"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="<td class='center awc-mono'>{$r["items"]}</td>";
    $h.="<td class='center awc-mono'>{$r["count"]}</td>";
    $h.="<td class='center awc-nowrap'>{$r["updated"]}</td>";
    $h.="<td><div class='awc-acts'>{$r["enabled"]}{$r["actions"]}</div></td>";
    $h.="</tr>";
    return $h;
}