<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["item-enable"])){item_enable();exit;}
if(isset($_GET["move-item-js"])){move_items_js();exit;}
if(isset($_POST["move-item"])){move_items();exit;}
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
    $service_id=$_GET["ruleid-delete"];
    $md=$_GET["md"];

    $js="$('#$md').remove();";
    $tpl->js_confirm_delete("{service} $service_id","item-delete",$service_id,$js);
}

function item_delete_perform(){

    $ID=$_POST["item-delete"];
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM personal_categories WHERE serviceid=$ID");
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM categories_services WHERE ID=$ID");
    $catz=new mysql_catz();
    $catz->BuildDnsRemotes();
}

function dumpcatz_popup(){
    $q=new postgres_sql();
    $t=time();
    $tpl=new template_admin();
    $remotecatz=intval($_GET["dumpcatz-popup"]);
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ID}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";


    $TRCLASS=null;
    $results= $q->QUERY_SQL("SELECT * FROM personal_categories WHERE remotecatz=$remotecatz");

    while($ligne=@pg_fetch_assoc($results)) {
        $categoryname=$ligne["categoryname"];
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td style='text-align: left' nowrap='' width='1%'>{$ligne["category_id"]}</td>";
        $html[] = "<td style='text-align: left' nowrap=''>{$categoryname}</td>";
        $html[] = "</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body($html);

}



function move_items_js(){

    $direction=$_GET["dir"];
    $ID=$_GET["ID"];
    $table="categories_services";


    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT zorder FROM $table WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);


    $OlOrder=$ligne["zorder"];
    if($direction=="down"){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
    $sql="UPDATE $table SET zorder='$OlOrder' WHERE zorder='$NewOrder'";


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}

    $sql="UPDATE $table SET zorder='$NewOrder' WHERE ID='$ID'";
    $q->QUERY_SQL($sql);

    if(!$q->ok){echo $q->mysql_error;}

    $results=$q->QUERY_SQL("SELECT * FROM $table ORDER BY zorder");
    $c=1;
    while ($ligne = mysqli_fetch_assoc($results)) {
        $ID=$ligne["ID"];
        $q->QUERY_SQL("UPDATE $table SET zorder='$c' WHERE ID='$ID'");
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



    if($ID==0){
        $q->QUERY_SQL("INSERT INTO categories_services (hostname,port,domain,crypt,passphrase,enabled) 
        VALUES ('$hostname','$port','$domain','$crypt','$passphrase',1)");

        if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}
        $catz=new mysql_catz();
        $catz->BuildDnsRemotes();
        return;
    }

    $q->QUERY_SQL("UPDATE categories_services SET 
    hostname='$hostname',
    port='$port',
    domain='$domain',
    crypt='$crypt',
    passphrase='$passphrase' WHERE ID=$ID");

    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}
    $catz=new mysql_catz();
    $catz->BuildDnsRemotes();

}

function table(){
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $add="Loadjs('$page?item-js=');";
    $TRCLASS=null;

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
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-catz-restart')";
    $t=time();

    $config["PROGRESS_FILE"]=PROGRESS_DIR."/filebeat.progress";
    $config["LOG_FILE"]=PROGRESS_DIR."/filebeat.log";
    $config["CMD"]="filebeat.php?cloud-uninstall=yes";
    $config["TITLE"]="{UseCloudArticaCategories}";
    $config["AFTER"]="dialogInstance4.close();if(document.getElementById('table-loader-catz-pages')){LoadAjax('table-loader-catz-pages','fw.proxy.categories.services.php?table=yes');}";
    $prgress=base64_encode(serialize($config));
    $CloudUninstall="Loadjs('fw.progress.php?content=$prgress&mainid=progress-catz-restart'')";

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




    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{service_name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{crypted}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{categories}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' width=1% nowrap=''>{last_update}</th>";
    $html[]="<th data-sortable=false width=1%>{order}</th>";
    $html[]="<th data-sortable=false width=1%>{enabled}</th>";
    $html[]="<th data-sortable=false width=1%>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $TRCLASS=null;
    $STATUS_ARRAY[0]="<span class=label>{disabled}</span>";
    $STATUS_ARRAY[1]="<span class='label label-primary'>{active2}</span>";
    $STATUS_ARRAY[2]="<span class='label label-danger'>{error}</span>";
    $STATUS_ARRAY[3]="<span class='label label-warning'>{error}</span>";



    $EnableLocalUfdbCatService = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));


    if($useCGuardCategories==1){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='useCGuardCategories'>";
        $lastUpdate_text="&nbsp;";
        $CguardCatzData=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CguardCatzData")));
        $Crypted="<i class='fas fa-check'></i>";
        $NAME="{use_lemnia_cloud_service}";
        $Categories=0;
        $CategoriesCount=0;
        $ico_enabled="<i class='fas fa-check'></i>";

        $catlist["99999999"]["TIME"]=time();
        $DnscatzDomain=$CguardCatzData["99999999"]["DOMAIN"];
        if($DnscatzDomain==null){$DnscatzDomain="{unknown}";}
        $DnsCatzPort=intval($CguardCatzData["99999999"]["PORT"]);
        $DnsCatzCrypt=$catlist["99999999"]["CRYPT"];
        if($DnsCatzPort>0){
            $DnscatzDomain=$DnscatzDomain.":$DnsCatzPort";
        }
        $textaddon="&nbsp;<small>$DnscatzDomain</small>";
        if($DnsCatzCrypt==1){
            $textaddon="&nbsp;<small>$DnscatzDomain ({crypted})</small>";
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

        $html[]="<td width='1%' style='text-align: center' nowrap=''>{$STATUS_ARRAY[$status]}</td>";
        $html[]="<td><strong>$NAME</strong>$textaddon</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>$Crypted</td>";
        $html[]="<td width='1%'  style='text-align: right' nowrap=''>$Categories</td>";
        $html[]="<td width='1%'  style='text-align: right' nowrap=''>$CategoriesCount</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>$lastUpdate_text</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>".$tpl->icon_nothing()."</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>$ico_enabled</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>".$tpl->icon_delete("Loadjs('fw.proxy.use.cguard.categories.php?remove-js=yes')","AsSquidAdministrator")."</td>";
        $html[]="</tr>";


    }





    $results=$q->QUERY_SQL("SELECT * FROM categories_services ORDER BY zorder");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["ID"]);
        if($ID==0){continue;}
        $port=$ligne["port"];
        $domain_addon=null;
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

        if($hostname<>null){
            $domain_addon="<br><small>$hostname:$port</small>";
        }


        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $up=$tpl->icon_up("Loadjs('$page?move-item-js=yes&ID={$ligne['ID']}&dir=up')","AsProxyMonitor");
        $down=$tpl->icon_down("Loadjs('$page?move-item-js=yes&ID={$ligne['ID']}&dir=down')","AsProxyMonitor");
        $del=$tpl->icon_delete("Loadjs('$page?ruleid-delete={$ligne["ID"]}&md=$md')","AsSquidAdministrator");
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?item-enable={$ligne['ID']}')","AsProxyMonitor");

        if($CategoriesCount>0){
            $CategoriesCount=$tpl->td_href($CategoriesCount,null,"Loadjs('$page?dumpcatz-js=$ID')");
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='text-align: center' nowrap=''>{$STATUS_ARRAY[$status]}</td>";
        $html[]="<td><strong>".$tpl->td_href($domain,null,"Loadjs('$page?item-js=$ID');")."</strong>{$domain_addon}$error</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>$Crypted</td>";
        $html[]="<td width='1%'  style='text-align: right' nowrap=''>$Categories</td>";
        $html[]="<td width='1%'  style='text-align: right' nowrap=''>$CategoriesCount</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>$lastUpdate_text</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>$up&nbsp;$down</td>";
        $html[]="<td width='1%' nowrap align='center'>$enabled</td>";
        $html[]="<td width=1% align='center'>$del</td>";
        $html[]="</tr>";
    }


    if($UseCloudArticaCategories==1){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
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

        $html[]="<tr class='$TRCLASS' id='UseCloudArticaCategories'>";
        $html[]="<td width='1%' style='text-align: center' nowrap=''>{$STATUS_ARRAY[$status]}</td>";
        $html[]="<td><strong>$NAME</strong></td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>&nbsp;</td>";
        $html[]="<td width='1%'  style='text-align: right' nowrap=''>$Categories</td>";
        $html[]="<td width='1%'  style='text-align: right' nowrap=''>150</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>$lastUpdate_text</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>".$tpl->icon_nothing()."</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>$ico_enabled</td>";
        $html[]="<td width='1%'  style='text-align: center' nowrap=''>".$tpl->icon_nothing()."</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='9'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
}