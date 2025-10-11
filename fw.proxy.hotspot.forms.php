<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$users=new usersMenus();if(!$users->AsSquidAdministrator){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["HotSpotRedirectUI"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["uncheck"])){uncheck();exit;}
if(isset($_GET["remove"])){remove();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["delete-all"])){delete_all_js();exit;}
if(isset($_POST["delete-all"])){delete_all_perform();exit;}
if(isset($_GET["start-schedule"])){start_schedule();exit;}
if(isset($_POST["start-schedule"])){start_schedule_save();exit;}
if(isset($_GET["edit"])){edit_js();exit;}
if(isset($_GET["edit-popup"])){edit_popup();exit;}
if(isset($_POST["ID"])){edit_save();exit;}
if(isset($_GET["dropdown-js"])){dropdown_js();exit;}
if(isset($_GET["dropdown-popup"])){dropdow_popup();exit;}
if(isset($_POST["dropdown"])){dropdown_save();exit;}
page();


function remove():bool{
    $ID=$_GET["remove"];
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_confirm_delete("{remove} $ID","remove",$ID,"$('#$md').remove();");
}
function remove_perform():bool{
    $ID=$_POST["remove"];
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $memcached=new lib_memcached();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='forms$ID'");
    $macaddress=$ligne["macaddress"];
    $ipaddr=$ligne["ipaddr"];
    $memcached->Delkey("MICROHOTSPOT:$ipaddr");
    if($macaddress<>null){$memcached->Delkey("MICROHOTSPOT:$macaddress");}
    $q->QUERY_SQL("DELETE FROM sessions WHERE sessionkey='forms$ID'");
    $q->QUERY_SQL("DELETE FROM forms WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return false;}
    return true;

}

function edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["edit"]);
    $title="{new_field}";
    if($ID>0) {
        $q = new lib_sqlite("/home/squid/hotspot/database.db");
        $ligne = $q->mysqli_fetch_array("SELECT * FROM forms WHERE ID='$ID'");
        $title = "$ID:{$ligne["label"]}";
    }
    return $tpl->js_dialog1($title,"$page?edit-popup=$ID",650);

}
function dropdown_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["dropdown-js"]);
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM forms WHERE ID=$ID");
    return $tpl->js_dialog1("{dropdown}:{$ligne["label"]}","$page?dropdown-popup=$ID",650);
}
function dropdow_popup():bool{
    $ID=intval($_GET["dropdown-popup"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT label,params FROM forms WHERE ID=$ID");

    $label=$ligne["label"];
    $values=explode(";;",$ligne["params"]);
    $f=array();
    foreach ($values as $sline){
        if(!preg_match("#(.+?)>>(.+)#",$sline,$re)){continue;}
        $f[]=trim($re[1])." >> " .trim($re[2]);
    }
    if(count($f)==0){$f[]="Label >> value";}
    $form[]=$tpl->field_hidden("dropdown",$ID);
    $form[]=$tpl->field_textareacode("params",null,@implode("\n",$f));
    echo $tpl->form_outside("$label: {dropdown}",$form,null,"{apply}","dialogInstance1.close();LoadAjax('table-hotspot-forms','$page?table=yes');","AsHotSpotManager",true);
    return true;
}
function dropdown_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["dropdown"]);

    writelogs("POST $ID {$_POST["params"]}",__FUNCTION__,__FILE__,__LINE__);

    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT label,params FROM forms WHERE ID=$ID");
    $label=$ligne["label"];
    $tp=explode("\n",$_POST["params"]);
    $f=array();
    foreach ($tp as $line){
        if(!preg_match("#(.+?)>>(.+)#",$line,$re)){continue;}
        $f[]=trim($re[1]).">>" .trim($re[2]);
    }


    $final=$q->sqlite_escape_string2(@implode(";;",$f));
    $q->QUERY_SQL("UPDATE forms SET params='$final' WHERE ID=$ID");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    admin_tracks("Change drop-down value of HotSpot field $label");
    return true;


}

function edit_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["edit-popup"]);
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $tpl->field_hidden("ID",$ID);

    $ligne=$q->mysqli_fetch_array("SELECT * FROM forms WHERE ID=$ID");
    if($ID>0){
        $title=$ligne["label"];
        $btn="{apply}";
    }else{
        $title="{new_field}";
        $ligne["enabled"]=1;
        $ligne["format"]=0;
        $btn="{add}";
    }

    $formats[0]="{field_text}";
    $formats[1]="{checkbox}";
    $formats[2]="{dropdown}";

    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_text("label","{label}",$ligne["label"]);
    $form[]=$tpl->field_radio_hash($formats,"format","{type}",$ligne["format"]);
    $form[]=$tpl->field_checkbox("mandatory","{mandatory}",$ligne["mandatory"]);
    $form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
    echo $tpl->form_outside($title,$form,null,$btn,"dialogInstance1.close();LoadAjax('table-hotspot-forms','$page?table=yes');","AsHotSpotManager",true);
return true;
}

function edit_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    unset($_POST["ID"]);
    $q=new lib_sqlite("/home/squid/hotspot/database.db");

    foreach ($_POST as $key=>$val){
        $fields[]=$key;
        $val=$q->sqlite_escape_string2($val);
        $fields_add[]="'$val'";
        $fields_edit[]="$key = '$val'";
    }

    if($ID==0){
        $sql="INSERT OR IGNORE INTO forms (".@implode(",",$fields).") VALUES (".@implode(",",$fields_add).")";

    }else {
        $sql = "UPDATE forms SET " . @implode(",", $fields_edit) . " WHERE ID='$ID'";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    return true;
}


function delete_all_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_delete("{all}","delete-all","yes","LoadAjax('table-hotspot-forms','$page?table=yes');");
}



function uncheck(){

    $ID=$_GET["uncheck"];
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='forms$ID'");
    $macaddress=$ligne["macaddress"];
    $ipaddr=$ligne["ipaddr"];

    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM forms WHERE ID='$ID'");
    $enabled=$ligne["enabled"];

    $newenabled=0;
    if($enabled==1){$newenabled=0;}else{$newenabled=1;}
    $q->QUERY_SQL("UPDATE forms SET enabled=$newenabled WHERE ID='$ID'");
    $memcached=new lib_memcached();


    if($newenabled==0){
        if($macaddress<>null){$memcached->Delkey("MICROHOTSPOT:$macaddress");}
        if($ipaddr<>null){$memcached->Delkey("MICROHOTSPOT:$ipaddr");}
    }



}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();



    $html=$tpl->page_header("{custom_form}",
        "fa-brands fa-wpforms","{hotspot_custom_form_explain}","$page?table=yes",
        "hotspot-forms","progress-hotspot-forms-restart",false,"table-hotspot-forms");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{forms_mananger}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/squid/hotspot/database.db");

    $jsrestart= $tpl->framework_buildjs("/proxy/hotspot/install",
        "hotspot-web.progress",
        "hotspot-web.log","progress-hotspot-forms-restart");


    $t=time();
    $add="Loadjs('$page?edit=0');";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_field}</label>";
    $btns[] = "<label class=\"btn btn btn-info\" OnClick=\"$jsrestart;\"><i class='fa-solid fa-retweet'></i> {apply_settings} </label>";
    $btns[]="</div>";

    $html[]="<table id='table-hotspot-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' colspan='2'>{label}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{format}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{mandatory}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `forms` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`label` TEXT,
			`format` TEXT NOT NULL ,
			`mandatory` INTEGER NOT NULL DEFAULT 0,
			`enabled` INTEGER NOT NULL DEFAULT '1',
			 params TEXT NULL,
            `zorder` INTEGER NOT NULL DEFAULT '0'
			) ");


    $formats[0]="{field_text}";
    $formats[1]="{checkbox}";
    $formats[2]="{dropdown}";

    $results=$q->QUERY_SQL("SELECT * FROM forms ORDER BY zorder ASC");
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        $label=$ligne["label"];
        $format=$formats[$ligne["format"]];
        $mandatory=$ligne["mandatory"];
        $enabled=$ligne["enabled"];
        $mandatory_ico="&nbsp;";
        $values_link=null;
        if($mandatory==1){
            $mandatory_ico="<i class='fa-solid fa-badge-check'></i>";
        }

        if($ligne["format"]==2){
            $values_link="&nbsp;&laquo;&nbsp;<i class='".ico_dropdown."'></i>&nbsp;".
                $tpl->td_href("{values}",null,"Loadjs('$page?dropdown-js=$ID')")."&nbsp;&raquo;";

        }
        //
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=1% nowrap><i class='fa-solid fa-pen-field'></i></td>";
        $html[]="<td width=99%>".$tpl->td_href("$label",null,"Loadjs('$page?edit=$ID')")."</td>";
        $html[]="<td width=1% nowrap>$format$values_link</td>";
        $html[]="<td style='width:1%;' nowrap class='center'>$mandatory_ico</td>";
        $html[]="<td style='width:1%;' nowrap class='center'>".$tpl->icon_check($enabled,
                "Loadjs('$page?uncheck=$ID')","AsHotSpotManager")."</td>";
        $html[]="<td style='width:1%;' nowrap class='center'>".$tpl->icon_delete("Loadjs('$page?remove=$ID&md=$md')",
                "AsHotSpotManager")."</center></td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="</table>";

    $TINY_ARRAY["TITLE"]="{custom_form}";
    $TINY_ARRAY["ICO"]="fa-brands fa-wpforms";
    $TINY_ARRAY["EXPL"]="{hotspot_custom_form_explain}";
    $TINY_ARRAY["URL"]="hotspot-forms";
    $TINY_ARRAY["BUTTONS"] = @implode("", $btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$jstiny
	</script>";


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}