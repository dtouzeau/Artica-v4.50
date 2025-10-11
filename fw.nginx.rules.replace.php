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


function service_js(){
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog4("{content_replacement}","$page?popup-main=$serviceid");
}
function rule_js(){
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();

    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}

    $tpl->js_dialog5("{content_replacement}: $title","$page?popup-rule=$rule&serviceid=$serviceid");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $ruleid=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("sub_filters"));
    unset($data[$ruleid]);
    $encoded=serialize($data);
    $sock->SET_INFO("sub_filters",base64_encode($encoded));
    echo "$('#$ruleid').remove();\n";
    echo "LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";
    return true;

}

function rule_enable(){
    $ruleid=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("sub_filters"));
    if(intval($data[$ruleid]["enable"])==1){
        $data[$ruleid]["enable"]=0;
    }else{
        $data[$ruleid]["enable"]=1;
    }
    $encoded=serialize($data);
    $sock->SET_INFO("sub_filters",base64_encode($encoded));
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $data       =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("sub_filters"));
    $ligne["enable"] = 1;
    $bt="{add}";
    $ligne=array();
    if($ruleid>0){
        if(isset($data[$ruleid])) {
            $ligne = $data[$ruleid];
        }
        $bt="{apply}";
    }
    $jsrestart="dialogInstance5.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    if(!isset($ligne["description"])){
        $ligne["description"]="";
    }
    if(!isset($ligne["pattern"])){
        $ligne["pattern"]="";
    }
    if(!isset($ligne["replace"])){
        $ligne["replace"]="";
    }

    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("enable","{enable}",$ligne["enable"]);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    $form[]=$tpl->field_textareacode("pattern","{pattern}",$ligne["pattern"]);
    $form[]=$tpl->field_textareacode("replace","{replaceby}",$ligne["replace"]);
    $html[]=$tpl->form_outside("{rule} $ruleid",$form,null,$bt,$jsrestart,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function rule_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $ruleid     = intval($_POST["ruleid"]);
    $sock       = new socksngix($serviceid);
    $data       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("sub_filters"));
    if($ruleid==0){
        $ruleid=time()+rand(0,5);
    }
    $data[$ruleid]=$_POST;
    $encoded=serialize($data);
    $sock->SET_INFO("sub_filters",base64_encode($encoded));
    return admin_tracks_post("Saving reverse Proxy SUB-FILTERS");
}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}

function popup_table(){
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $tableid    = time();

    $compile_js_progress=compile_js_progress($serviceid);

    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0&serviceid=$serviceid');\">
	<i class='fa fa-plus'></i> {new_rule} </label>";
    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$compile_js_progress\">
	<i class='fa fa-save'></i> {apply_parameters_to_the_system} </label>";
    $html[]="</div>";

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

    $data= $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("sub_filters"));

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

    $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$num&serviceid=$serviceid')","","AsWebMaster");
    $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$num&serviceid=$serviceid')","AsWebMaster");
    $pattern=$tpl->td_href($pattern,"","Loadjs('$page?rule-js=$num&serviceid=$serviceid');");

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
        $html[]="$(document).ready(function() { $('#$tableid').footable( { \"filtering\": { \"enabled\": true";
        $html[]="},\"sorting\": { \"enabled\": true },";
        $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        }