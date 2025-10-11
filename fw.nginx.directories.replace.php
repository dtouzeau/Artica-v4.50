<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}

function suffix_RQ():string{
    $directory_id=intval($_GET["directory_id"]);
    $serviceid=intval($_GET["serviceid"]);
    $md5=$_GET["md5"];
    $f[]="directory_id=$directory_id";
    $f[]="serviceid=$serviceid";
    $f[]="md5=$md5";
    if(isset($_GET["refreshjs"])){
          $f[]="refreshjs=".$_GET["refreshjs"];
    }

    return @implode("&",$f);
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function get_directoryname($ID):string{
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT directory FROM ngx_directories WHERE ID=$ID");
    return $ligne["directory"];
}
function get_data($ID):array{
    $ID=intval($ID);
    if($ID==0){return array();}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT replace FROM ngx_directories WHERE ID=$ID");
    $array=unserialize(base64_decode($ligne["replace"]));
    if(!is_array($array)){return array();}
    return $array;
}
function set_data($array=array(),$ID){
    $NEWDATA=base64_encode(serialize($array));
    $q                          = new lib_sqlite(NginxGetDB());

    if(!$q->FIELD_EXISTS("ngx_directories","replace")){
        $q->QUERY_SQL("ALTER TABLE ngx_directories ADD replace TEXT");
    }

    $q->QUERY_SQL("UPDATE ngx_directories set replace='$NEWDATA' WHERE ID=$ID");

}


function service_js():bool{
    $suffix = suffix_RQ();
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{content_replacement}","$page?popup-main=yes&$suffix");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $directory_id=intval($_GET["directory_id"]);
    $suffix = suffix_RQ();
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();


    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}

    $sitename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);

    return $tpl->js_dialog5("$sitename/$dirname: {content_replacement}: $title","$page?popup-rule=$rule&$suffix");
}


function rule_remove():bool{
    $ruleid=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $directory_id=intval($_GET["directory_id"]);
    $data       = get_data($directory_id);
    unset($data[$ruleid]);
    set_data($data,$directory_id);

    $sitename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);
    admin_tracks_post("Delete replace rule $ruleid inside $dirname of $sitename");

    header("content-type: application/x-javascript");
    echo "$('#$ruleid').remove();Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');\n";
    return true;

}

function rule_enable(){
    $ruleid=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $directory_id=intval($_GET["directory_id"]);
    $data       = get_data($directory_id);
    if(intval($data[$ruleid]["enable"])==1){
        $data[$ruleid]["enable"]=0;
    }else{
        $data[$ruleid]["enable"]=1;
    }
    $sitename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);
    admin_tracks_post("Save replace rule inside $dirname of $sitename");
    set_data($data,$directory_id);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');";
}

function rule_popup(){
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $directory_id=intval($_GET["directory_id"]);
    $data       = get_data($directory_id);
    $refreshjs=base64_decode($_GET["refreshjs"]);
    $ligne["enable"] = 1;
    $bt="{add}";
    if($ruleid>0){ $ligne=$data[$ruleid];$bt="{apply}"; }
    $jsrestart="dialogInstance5.close();$refreshjs;Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_hidden("directory_id",$directory_id);
    $form[]=$tpl->field_checkbox("enable","{enable}",$ligne["enable"]);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    $form[]=$tpl->field_textareacode("pattern","{pattern}",$ligne["pattern"]);
    $form[]=$tpl->field_textareacode("replace","{replaceby}",$ligne["replace"]);
    $html[]=$tpl->form_outside("{rule} $ruleid",$form,null,$bt,$jsrestart,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);

}

function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $directory_id  = intval($_POST["directory_id"]);
    $ruleid     = intval($_POST["ruleid"]);
    $data       = get_data($directory_id);
    if($ruleid==0){
        $ruleid=time()+rand(0,5);
    }

    $sitename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);

    $data[$ruleid]=$_POST;
    set_data($data,$directory_id);
    return admin_tracks("Save replace rule $ruleid inside $dirname in $sitename");
}

function popup_main(){
    $directory_id=intval($_GET["directory_id"]);
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    $suffix=suffix_RQ();
    $js="LoadAjax('main-popup-$serviceid-$directory_id','$page?popup-table=yes&$suffix')";
    $jsenc=base64_encode($js);

    echo "<div id='main-popup-$serviceid-$directory_id'></div>
    <script>LoadAjax('main-popup-$serviceid-$directory_id','$page?popup-table=yes&$suffix&refreshjs=$jsenc')</script>";
}

function popup_table():bool{
    $suffix=suffix_RQ();
    $serviceid  = intval($_GET["serviceid"]);
    $directory_id=intval($_GET["directory_id"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tableid    = time();
    $forcediv=  "progress-replace-$serviceid-$directory_id";
    $refreshjs = $_GET["refreshjs"];

    $topbuttons[] = array("Loadjs('$page?rule-js=0&$suffix');",
        ico_plus,"{new_rule}");

    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="</div>";
    $html[]="<div id='$forcediv'></div>";
    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{search}</th>
        	<th nowrap>{replace}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data=get_data($directory_id);

    foreach ($data as $num=>$ligne){
        $enable=intval($ligne["enable"]);
        $description=trim($ligne["description"]);
        $pattern=$ligne["pattern"];
        $replace=$ligne["replace"];
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        if(strlen($replace)>128){$replace=substr($replace,0,125)."...";}
        $pattern=htmlentities($pattern);
        $replace=htmlentities($replace);
        if($description<>null){
            $description="<br><small>$description</small>";
        }

    $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$num&$suffix')","","AsWebMaster");
    $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$num&$suffix')","AsWebMaster");
    $pattern=$tpl->td_href($pattern,"","Loadjs('$page?rule-js=$num&$suffix');");

    $html[]="<tr id='$num'>
				<td width=50%>$pattern{$description}</td>
				<td width=50% >$replace</td>
				<td width=1%  nowrap >$enable</td>
				<td width=1%  nowrap >$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='4'>";
        $html[]="<ul class='pagination pull-right'></ul>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</tfoot>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="$(document).ready(function() { $('#$tableid').footable( { \"filtering\": { \"enabled\": false";
        $html[]="},\"sorting\": { \"enabled\": true },";
        $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
}

function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}