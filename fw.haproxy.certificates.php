<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}


function service_js(){
    $serviceid  = trim($_GET["service-js"]);
    $serviceidC = urlencode($serviceid);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog4("{certificates} $serviceid","$page?popup-main=$serviceidC");
}
function rule_js(){
    $serviceid  = trim($_GET["serviceid"]);
    $serviceidC = urlencode($serviceid);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();

    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}

    $tpl->js_dialog5("{certificates}: $title","$page?popup-rule=$rule&serviceid=$serviceidC");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $ruleid=intval($_GET["pattern-remove"]);
    $serviceid=trim($_GET["serviceid"]);
    $sock       = new haproxy_multi($serviceid);
    unset($sock->certificates[$ruleid]);
    $sock->save();
    echo "$('#$ruleid').remove();\n";
    echo "HaProxyBalancerParametersMain();";
    return true;

}

function rule_enable(){
    $ruleid=intval($_GET["pattern-enable"]);
    $serviceid=trim($_GET["serviceid"]);
    $sock       = new haproxy_multi($serviceid);
    $data       = $sock->certificates;
    if(intval($data[$ruleid]["enable"])==1){
        $data[$ruleid]["enable"]=0;
    }else{
        $data[$ruleid]["enable"]=1;
    }
    $sock->certificates=$data;
    $sock->save();
}

function rule_popup(){
    $serviceid  = $_GET["serviceid"];
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $sock       = new haproxy_multi($serviceid);
    $data       = $sock->certificates;
    $ligne["enable"] = 1;
    $bt="{add}";

    $sslmin_ver_ar=array(
    "SSLv3"=>"SSLv3",
        "TLSv1.0"=>"TLSv1.0",
        "TLSv1.1"=>"TLSv1.1",
        "TLSv1.2"=>"TLSv1.2",
        "TLSv1.3"=>"TLSv1.3"
    );

    $ciphers=$ligne["ciphers"];
    if($ciphers==null){$ciphers="none";}
    if($ruleid>0){ $ligne=$data[$ruleid];$bt="{apply}"; }
    $jsrestart="dialogInstance5.close();LoadHaProxyCertificates();HaProxyBalancerParametersMain();";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("enable","{enable}",$ligne["enable"]);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    $form[]=$tpl->field_certificate("certificate","{certificate}",$ligne["certificate"]);
    $form[]=$tpl->field_array_hash($sslmin_ver_ar,"ssl-min-ver","{aboveeq}/{eq2}",$ligne["ssl-min-ver"]);
    $form[]=$tpl->field_text("ciphers","{ssl_ciphers}",$ciphers);
    $form[]=$tpl->field_text("snifilter","{snifilter}",$ligne["snifilter"]);






    $html[]=$tpl->form_outside("{certificate} $ruleid",$form,null,$bt,$jsrestart,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid  = trim($_POST["serviceid"]);
    $ruleid     = intval($_POST["ruleid"]);
    $sock       = new haproxy_multi($serviceid);
    $data       = $sock->certificates;
    if($ruleid==0){
        $ruleid=time()+rand(0,5);
    }
    $data[$ruleid]=$_POST;
    $sock->certificates=$data;
    $sock->save();
}

function popup_main(){
    $serviceid  = urlencode($_GET["popup-main"]);
    $servicemd  = md5($serviceid);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$servicemd'></div>
    <script>
        function LoadHaProxyCertificates(){ LoadAjax('main-popup-$servicemd','$page?popup-table=$serviceid');}
        LoadHaProxyCertificates();
        </script>";
}

function popup_table(){
    $serviceid  = trim($_GET["popup-table"]);
    $serviceidC = urlencode($serviceid);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $sock       = new haproxy_multi($serviceid);
    $tableid    = time();

    $compile_js_progress=compile_js_progress($serviceid);

    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0&serviceid=$serviceidC');\">
	<i class='fa fa-plus'></i> {add} {certificate} </label>";
    //$html[]="<label class=\"btn btn btn-warning\" OnClick=\"$compile_js_progress\"><i class='fa fa-save'></i> {apply_parameters_to_the_system} </label>";
    $html[]="</div>";

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{certificates}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data=$sock->certificates;

    foreach ($data as $num=>$ligne){
        $ss_min_ver_text=null;
        $enable=intval($ligne["enable"]);
        $description=trim($ligne["description"]);
        $pattern=$ligne["certificate"];
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        $pattern=htmlentities($pattern);
        $ss_min_ver=$ligne["ssl-min-ver"];
        if($ss_min_ver<>null){$ss_min_ver_text="&nbsp;<small>{aboveeq}/{eq2} <strong>$ss_min_ver</strong>/small>";}
        if($description<>null){ $description="<br><small>$description</small>"; }

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$num&serviceid=$serviceidC')","","AsSquidAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$num&serviceid=$serviceidC')","AsSquidAdministrator");
        $pattern=$tpl->td_href($pattern,"","Loadjs('$page?rule-js=$num&serviceid=$serviceidC');");

    $html[]="<tr id='$num'>
				<td width=50%>$pattern{$ss_min_ver_text}{$description}</td>
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