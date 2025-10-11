<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["domain"])){rule_save();exit;}

service_js();
function service_js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog4("{excludes}: {domains}","$page?popup-main=yes");
}
function rule_js(){
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();

    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}

    $tpl->js_dialog5("{excludes}: $title","$page?popup-rule=$rule");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $domain=$_GET["pattern-remove"];
    $md=md5($domain);
    $data       = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCBlacklistDoms"));
    unset($data[$domain]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSCBlacklistDoms",serialize($data));
    admin_tracks("remove $domain in DNS statistics Exclusion list");
    echo "$('#$md').remove();\n";
    return true;

}



function rule_popup(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $DSCBlacklistDoms       = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCBlacklistDoms"));

    if(!is_array($DSCBlacklistDoms) or empty($DSCBlacklistDoms)){
        $DSCBlacklistDoms[".ntp.org"]=true;
        $DSCBlacklistDoms[".tld"]=true;
        $DSCBlacklistDoms[".lab"]=true;
        $DSCBlacklistDoms[".local"]=true;
        $DSCBlacklistDoms[".int"]=true;
        $DSCBlacklistDoms[".infra"]=true;

    }

    $bt="{add}";

    $jsrestart="dialogInstance5.close();LoadAjax('main-popup-domblks','$page?popup-table');";

    $form[]=$tpl->field_text("domain","{domain}",null,true);
    $html[]=$tpl->form_outside("{excludes}",$form,null,$bt,$jsrestart,"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();

    $DSCBlacklistDoms       = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCBlacklistDoms"));

    if(!is_array($DSCBlacklistDoms) or empty($DSCBlacklistDoms)){
        $DSCBlacklistDoms[".ntp.org"]=true;
        $DSCBlacklistDoms[".tld"]=true;
        $DSCBlacklistDoms[".lab"]=true;
        $DSCBlacklistDoms[".local"]=true;
        $DSCBlacklistDoms[".int"]=true;
        $DSCBlacklistDoms[".infra"]=true;
        $DSCBlacklistDoms[".filter.artica.center"]=true;

    }

    $DSCBlacklistDoms[$_POST["domain"]]=true;
    admin_tracks("Added {$_POST["domain"]} in DNS statistics Exclusion list");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSCBlacklistDoms",serialize($DSCBlacklistDoms));
    return true;
}

function popup_main(){
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-domblks'></div>
    <script>LoadAjax('main-popup-domblks','$page?popup-table=$serviceid')</script>";
}

function popup_table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tableid    = time();

    $html[]="<div id='progress-compile-replace-domblks'></div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0');\">
	<i class='fa fa-plus'></i>{excludes} {new_domain} </label>";
    $html[]="</div>";

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{domains}</th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $DSCBlacklistDoms=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCBlacklistDoms"));
    if(!is_array($DSCBlacklistDoms) or empty($DSCBlacklistDoms)){
        $DSCBlacklistDoms[".ntp.org"]=true;
        $DSCBlacklistDoms[".tld"]=true;
        $DSCBlacklistDoms[".lab"]=true;
        $DSCBlacklistDoms[".local"]=true;
        $DSCBlacklistDoms[".int"]=true;
        $DSCBlacklistDoms[".infra"]=true;
        $DSCBlacklistDoms[".filter.artica.center"]=true;
    }


    foreach ($DSCBlacklistDoms as $domain=>$none){

        $domain_enc=urlencode($domain);
        if(strlen($domain)>128){$domain=substr($domain,0,125)."...";}
         $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$domain_enc')","AsDnsAdministrator");

    $domainmd=md5($domain);
    $html[]="<tr id='$domainmd'>
				<td width=100%>$domain</td>
				<td width=1%  nowrap >$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='3'>";
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