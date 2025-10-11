<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}

try{
    $users=new usersMenus();if(!$users->AsHotSpotManager){$users->pageDie();}
}catch (Exception $e) {
    echo "<H1 style='color:red'>".$e->getMessage()."</H1>";
    die();
}

try{
    if(isset($_POST["HotSpotRedirectUI"])){Save();exit;}
    if(isset($_GET["table"])){table();exit;}
    if(isset($_GET["tabs"])){tabs();exit;}
    if(isset($_GET["uncheck"])){uncheck();exit;}
    if(isset($_GET["remove"])){remove();exit;}
    if(isset($_POST["remove"])){remove_perform();exit;}
    if(isset($_GET["import-js"])){import_js();exit;}
    if(isset($_GET["import-popup"])){import_popup();exit;}
    if(isset($_POST["import"])){import_save();exit;}
    if(isset($_GET["delete-all"])){delete_all_js();exit;}
    if(isset($_POST["delete-all"])){delete_all_perform();exit;}
    if(isset($_GET["start-schedule"])){start_schedule();exit;}
    if(isset($_POST["start-schedule"])){start_schedule_save();exit;}
    if(isset($_GET["edit"])){edit_js();exit;}
    if(isset($_GET["edit-popup"])){edit_popup();exit;}
    if(isset($_POST["ID"])){edit_save();exit;}
    if(isset($_GET["start"])){start();exit;}
    if(isset($_GET["search"])){table();exit;}
    page();
} catch (Exception $e) {
    echo "<H1 style='color:red'>".$e->getMessage()."</H1>";
    die();
}

function remove(){
    $ID=$_GET["remove"];
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_delete("{remove} $ID","remove",$ID,"$('#$md').remove();");
}
function remove_perform():bool{
    $ID=$_POST["remove"];
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $memcached=new lib_memcached();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='voucher$ID'");
    $macaddress=$ligne["macaddress"];
    $ipaddr=$ligne["ipaddr"];
    $memcached->Delkey("MICROHOTSPOT:$ipaddr");
    if($macaddress<>null){$memcached->Delkey("MICROHOTSPOT:$macaddress");}
    $q->QUERY_SQL("DELETE FROM sessions WHERE sessionkey='voucher$ID'");
    $q->QUERY_SQL("DELETE FROM vouchers WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Remove Voucher HotSpot session $macaddress/$ipaddr");
}

function start_schedule():bool{
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ID=$_GET["start-schedule"];
    $ligne=$q->mysqli_fetch_array("SELECT ttl FROM vouchers WHERE ID='$ID'");
    $tpl=new template_admin();
    $ttl=$ligne["ttl"];
    $page=CurrentPageName();
    $text=$tpl->_ENGINE_parse_body("{open_internet_access_for} {$ttl} {hours}");
    return $tpl->js_dialog_confirm_action($text,"start-schedule",$ID,"LoadAjax('table-hotspot-vouchers','$page?table=yes');");
}

function start_schedule_save():bool{
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ID=$_POST["start-schedule"];
    $ligne=$q->mysqli_fetch_array("SELECT ttl FROM vouchers WHERE ID='$ID'");
    $ttl=intval($ligne["ttl"]);
    $ttlmn=$ttl*60;
    $ttls=$ttlmn*60;
    $expire=time()+$ttls;
    $q->QUERY_SQL("UPDATE vouchers SET expire='$expire' WHERE ID=$ID");
    return admin_tracks("Extend HotSpot Expiration time of ID $ID to {$ttlmn}mn");
}
function edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["edit"];
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM vouchers WHERE ID='$ID'");
    $title="$ID:{$ligne["member"]}";
    $function=$_GET["function"];
    return $tpl->js_dialog1($title,"$page?edit-popup=$ID&function=$function",650);
}

function edit_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=intval($_GET["edit-popup"]);
    $HotSpotVoucherRemovePass=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotVoucherRemovePass"));
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $tpl->field_hidden("ID",$ID);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM vouchers WHERE ID=$ID");
    if($ID>0){
        $title=$ligne["member"];
        $btn="{apply}";
    }else{
        $title="{new_voucher}";
        $btn="{add}";
        $ligne["ttl"]=24;
    }



    $form[]=$tpl->field_text("member","{voucher_room}",$ligne["member"]);
    if($HotSpotVoucherRemovePass==0) {
        $form[] = $tpl->field_password("password", "{password}", $ligne["password"]);
    }
    $form[]=$tpl->field_numeric("ttl","{ttl} ({hours})",$ligne["ttl"]);

    echo $tpl->form_outside($title,$form,null,$btn,"dialogInstance1.close();$function();","AsHotSpotManager",true);
    return true;
}

function edit_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    if(!isset($_POST["password"])){$_POST["password"]=null;}
    if($ID==0){
        $date=time();
        $sql="INSERT OR IGNORE INTO vouchers (created,member,password,ttl,expire,enabled) 
      VALUES ('$date','{$_POST["member"]}','{$_POST["password"]}','{$_POST["ttl"]}','0','1')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;}
        return false;
    }

    $sql="UPDATE vouchers SET 
        member='{$_POST["member"]}',
        password='{$_POST["password"]}',
        ttl='{$_POST["ttl"]}' WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}
    return true;


}


function delete_all_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_confirm_delete("{all}","delete-all","yes","LoadAjax('table-hotspot-vouchers','$page?table=yes');");
}
function delete_all_perform():bool{
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $q->QUERY_SQL("DELETE FROM vouchers");
    $q->QUERY_SQL("UPDATE SQLITE_SEQUENCE SET seq = 0 WHERE name = 'vouchers'");
    $q->QUERY_SQL("VACUUM;");
    $q->QUERY_SQL("DELETE FROM vouchers");
    $memcached=new lib_memcached();
    $results=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE autocreate='10'");
    $c=0;
    foreach ($results as $index=>$ligne) {
        $c++;
        $sessionkey = $ligne["sessionkey"];
        $macaddress = $ligne["macaddress"];
        $ipaddr = $ligne["ipaddr"];
        $memcached->Delkey("MICROHOTSPOT:$ipaddr");
        if ($macaddress <> null) {
            $memcached->Delkey("MICROHOTSPOT:$macaddress");
        }
        $q->QUERY_SQL("DELETE FROM sessions WHERE sessionkey='$sessionkey'");
        if (!$q->ok) {
            echo $q->mysql_error;
        }
    }

    return admin_tracks("Remove $c vouchers HotSpot sessions...");

}

function import_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog1("{import_vouchers}","$page?import-popup=yes&function=$function",950);
}
function import_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $form[]=$tpl->field_textarea("import","{vouchers_rooms}","");

    echo $tpl->form_outside("{import_vouchers}",$form,"{import_vouchers_explain}","{import}","dialogInstance1.close();$function();","AsHotSpotManager",true);
    return true;
}

function import_save():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $lines=explode("\n",$_POST["import"]);
    $date=time();

    if(!$q->FIELD_EXISTS("vouchers","created")){
        $q->QUERY_SQL("ALTER TABLE vouchers add created INTEGER");
    }
    if(!$q->FIELD_EXISTS("vouchers","hotspotkey")){
        $q->QUERY_SQL("ALTER TABLE vouchers add hotspotkey TEXT");
    }


    $prefix="INSERT OR IGNORE INTO vouchers (created,member,password,ttl,expire,enabled) VALUES ";
    $c=0;
    foreach ($lines as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^\##",$line)){continue;}
        $line=str_replace(";",",",$line);
        if(strpos($line,",")==0){continue;}
        $TF=explode(",",$line);
        $member=$TF[0];
        $password=$q->sqlite_escape_string2($TF[1]);
        $ttl=intval($TF[2]);
        if($ttl==0){continue;}
        $expire=0;
        $enabled=1;
        $c++;
        $q->QUERY_SQL("$prefix ('$date','$member','$password','$ttl','$expire',$enabled)");
        if(!$q->ok){ $tpl->post_error($q->mysql_error);return false;}
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    return admin_tracks("Importing $c HotSpot Vouchers");
}


function uncheck():bool{

    $ID=$_GET["uncheck"];
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='voucher$ID'");
    $macaddress=$ligne["macaddress"];
    $ipaddr=$ligne["ipaddr"];

    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM vouchers WHERE ID='$ID'");
    $enabled=$ligne["enabled"];

    $newenabled=0;
    if($enabled==1){$newenabled=0;}else{$newenabled=1;}
    $q->QUERY_SQL("UPDATE vouchers SET enabled=$newenabled WHERE ID='$ID'");
    $memcached=new lib_memcached();


    if($newenabled==0){
        if($macaddress<>null){$memcached->Delkey("MICROHOTSPOT:$macaddress");}
        if($ipaddr<>null){$memcached->Delkey("MICROHOTSPOT:$ipaddr");}
    }

    return admin_tracks("Modify HotSpot session $ID/$macaddress/$ipaddr enabled=$enabled");

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{vouchers_mananger}",
        "fad fa-ticket","{vouchers_mananger_explain}","$page?start=yes",
        "voucher-manager","progress-hotspot-voucher-restart",false,"table-hotspot-vouchers");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{vouchers_mananger}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
    return true;
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $function=$_GET["function"];

    $t=time();

    $import="Loadjs('$page?import-js=yes&function=$function');";
    $add="Loadjs('$page?edit=0&function=$function');";
    $delete_all="Loadjs('$page?delete-all=yes&function=$function');";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_voucher}</label>";
    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$import\"><i class='fa fa-plus'></i> {import_vouchers}</label>";
    $btns[]="<label class=\"btn btn btn-danger\" OnClick=\"$delete_all\"><i class='fa fa-trash'></i> {delete_all}</label>";
    $btns[]="</div>";

    $html[]="<table id='table-hotspot-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{vouchers_rooms}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{ttl}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{expire}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `vouchers` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`created` INTEGER,
			`member` TEXT UNIQUE NOT NULL ,
			`password` TEXT NOT NULL,
			`ttl` INTEGER NOT NULL,
			`expire` INTEGER NOT NULL,
			`enabled` INTEGER NOT NULL DEFAULT '1',
			`bandwidth` INTEGER NOT NULL DEFAULT '0',
			`hotspotKey` TEXT
			) ");


    if($_GET["search"]==null) {
        $results = $q->QUERY_SQL("SELECT * FROM vouchers ORDER BY member LIMIT 150");
    }else{
        if(strpos(" ".$_GET["search"],'*')>0) {
            $search = str_replace("*", "%", $_GET["search"]);
        }else{
            $search = "%".$_GET["search"]."%";
        }
        $results = $q->QUERY_SQL("SELECT * FROM vouchers WHERE member LIKE '$search' ORDER BY member LIMIT 150");
    }
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return false;}
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $Time=time();
        $md=md5(serialize($ligne));
        $enabled=intval($ligne["enabled"]);
        $member=$ligne["member"];
        $ttl=intval($ligne["ttl"]);
        $expire=intval($ligne["expire"]);
        $expire_text=null;
        $ID=$ligne["ID"];
        $bt_class="btn-primary";

        $tooltip=null;

        if($expire==0){
            $expire_text=$tpl->icon_nothing();
            $bt_class="btn-default";
        }else{
            $expire_text=distanceOfTimeInWords(time(),$expire,true);
            if(time()>$expire){
                $bt_class="btn-danger";
                $expire_text="{expired}";
            }
        }

        $ttl=$ttl*60;
        $ttl=$ttl*60;
        $distancettl=distanceOfTimeInWords(time(),time()+$ttl);
        $js="Loadjs('$page?start-schedule=$ID');";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=1% nowrap>".$tpl->time_to_date($ligne["created"],true)."</td>";
        $html[]="<td width=99%>".$tpl->td_href("$member",null,"Loadjs('$page?edit=$ID')")." $tooltip</td>";
        $html[]="<td width=1%>".$tpl->button_autnonome("{schedule}",$js,"fa fa-clock","AsHotSpotManager",0,$bt_class,"small")."</td>";
        $html[]="<td width=1% nowrap>".$distancettl."</td>";
        $html[]="<td width=1% nowrap>".$expire_text."</td>";
        $html[]="<td style='width:1%;' nowrap class='center'>".$tpl->icon_check($enabled,
                "Loadjs('$page?uncheck=$ID')","AsHotSpotManager")."</td>";
        $html[]="<td style='width:1%;' nowrap class='center'>".$tpl->icon_delete("Loadjs('$page?remove=$ID&md=$md')",
                "AsHotSpotManager")."</center></td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";


    $HotSpotAuthentVoucher=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentVoucher"));
    $explain_error=null;
    if($HotSpotAuthentVoucher==0){
        $explain_error=$tpl->div_error("{voucher_not_enabled}");
    }
    $TINY_ARRAY["TITLE"]="{vouchers_mananger}";
    $TINY_ARRAY["ICO"]="fad fa-ticket";
    $TINY_ARRAY["EXPL"]="{vouchers_mananger_explain}$explain_error";
    $TINY_ARRAY["URL"]="voucher-manager";
    if($HotSpotAuthentVoucher==1) {
        $TINY_ARRAY["BUTTONS"] = @implode("", $btns);
    }

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	
	$jstiny
	</script>";


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if($_POST["HotSpotRedirectUI2"]<>null){
        $_POST["HotSpotRedirectUI"]=$_POST["HotSpotRedirectUI2"];
        unset($_POST["HotSpotRedirectUI2"]);
    }
    $tpl->SAVE_POSTs();
    return true;


}