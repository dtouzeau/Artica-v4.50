<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");
define("rewrite_flags",
        array(
            "last"=>"{ngix_rwfl_last}",
            "break"=>"{ngix_rwfl_break}",
            "permanent"=>"{ngix_rwfl_permanent}",
            "redirect"=>"{ngix_rwfl_redirect}",
        )
);


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-search"])){popup_search();exit;}

if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}

if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["top-buttons"])){top_buttons();exit;}


function service_js(){
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog4("{rewrite_rules} [$serviceid]","$page?popup-search=$serviceid");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function   = $_GET["function"];
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog5("{rewrite_rules}: $title","$page?popup-rule=$rule&serviceid=$serviceid&function=$function");
}
function popup_search():bool{
    $serviceid  = intval($_GET["popup-search"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();

    $html[]="<div id='top-buttons-$serviceid' style='margin-bottom:5px;margin-top:5px'></div>";
    $html[]=$tpl->search_block($page,null,null,null,"&popup-table=$serviceid");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_popup(){
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function =$_GET["function"];


    $sock       = new socksngix($serviceid);
    $data       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("rewrite_rules"));
    $ligne["enable"] = 1;
    $bt="{add}";
    if($ruleid>0){ $ligne=$data[$ruleid];$bt="{apply}"; }
    $jsrestarts[]="dialogInstance5.close();";
    if(strlen($function)>2){
        $jsrestarts[]="$function();";
    }
    $jsrestarts[]="LoadAjaxSilent('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    $jsApply=@implode("", $jsrestarts);

    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("enable","{enable}",$ligne["enable"]);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    $form[]=$tpl->field_text("replace","{request}",$ligne["replace"]);
    $form[]=$tpl->field_text("pattern","{service_rewrite} {to}",$ligne["pattern"],true);

    $form[]=$tpl->field_array_hash(rewrite_flags,"flag","{option}",$ligne["flag"]);

    if($ruleid==0){
        $html[]=$tpl->div_explain("{new_rewrite_rule}||{rewrite_rules_fdb_explain}");
    }
    $html[]=$tpl->form_outside("{rule} $ruleid",$form,null,$bt,$jsApply,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);

}
function rule_remove():bool{
    $ruleid=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("rewrite_rules"));
    unset($data[$ruleid]);
    $encoded=serialize($data);
    $sock->SET_INFO("rewrite_rules",base64_encode($encoded));
    echo "$('#$ruleid').remove();\n";
    echo "LoadAjaxSilent('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;

}
function rule_enable(){
    $ruleid=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("rewrite_rules"));
    if(intval($data[$ruleid]["enable"])==1){
        $data[$ruleid]["enable"]=0;
    }else{
        $data[$ruleid]["enable"]=1;
    }
    $encoded=serialize($data);
    $sock->SET_INFO("rewrite_rules",base64_encode($encoded));
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
}
function rule_save(){
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $ruleid     = intval($_POST["ruleid"]);
    $sock       = new socksngix($serviceid);
    $data       = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("rewrite_rules"));
    if($ruleid==0){
        $ruleid=time()+rand(0,500);
    }
    $data[$ruleid]=$_POST;
    $encoded=serialize($data);
    $sock->SET_INFO("rewrite_rules",base64_encode($encoded));
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
}


function top_buttons():bool{
    $serviceid  = intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();

    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid&function=$function')", ico_plus, "{new_rewrite_rule}");

    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function popup_table(){
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $function = $_GET["function"];
    $tableid    = time();

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{requests}</th>
        	<th nowrap>{service_rewrite} {to}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($sock->GET_INFO("rewrite_rules"));

    foreach ($data as $num=>$ligne){
        $enable=intval($ligne["enable"]);
        $description=trim($ligne["description"]);
        $pattern=trim($ligne["pattern"]);
        $replace=$ligne["replace"];
        if($replace==null){$replace="{everything}";}
        if($replace=="*"){$replace="{everything}";}
        $flag=rewrite_flags[$ligne["flag"]];
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        if(strlen($replace)>128){$replace=substr($replace,0,125)."...";}
        $pattern=htmlentities($pattern);
        $replace=htmlentities($replace);
        if($description<>null){
            $description="<br><small>$description</small>";
        }
        if($flag<>null){$flag=" <small>($flag)</small>";}

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$num&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$num&serviceid=$serviceid')","AsWebMaster");
        $pattern=$tpl->td_href($pattern,"","Loadjs('$page?rule-js=$num&serviceid=$serviceid');");

    $html[]="<tr>
				<td width=50% ><strong>$replace</strong>{$description}</td>
				<td width=50%>$pattern$flag</td>
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
        $html[]="LoadAjaxSilent('top-buttons-$serviceid','$page?top-buttons=$serviceid&function=$function');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
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