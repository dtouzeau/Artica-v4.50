<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}

if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-table2"])){popup_table2();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}

js();



function js():bool{
    $tpl        = new template_admin();
    $Mainfunc=$_GET["Mainfunc"];
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{temporary_permissions}","$page?popup-main=yes&Mainfunc=$Mainfunc");
}
function rule_js():bool{
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    $function=$_GET["function"];
    $Mainfunc=$_GET["Mainfunc"];
    return $tpl->js_dialog5("{sitename}: $title","$page?popup-rule=$rule&function=$function&Mainfunc=$Mainfunc");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $tpl        = new template_admin();
    $md=$_GET["md"];
    $ID=intval($_GET["pattern-remove"]);

    // Fetch current row to get pattern for audit log
    $rows = acls_api_get("/proxy/url-rewrite/temp");
    $pattern = "";
    if(is_array($rows)){
        foreach($rows as $r){
            if(intval($r["id"])==$ID){ $pattern=$r["pattern"]; break; }
        }
    }

    list($ok,$data) = acls_api_send("DELETE", "/proxy/url-rewrite/temp/$ID");
    if(!$ok){
        echo $tpl->div_error(htmlspecialchars($data));
        return false;
    }

    echo "$('#$md').remove();\n";
    echo refresh_global();
    return admin_tracks("Remove url-filtering exclusion $pattern");
}


function rule_enable():bool{
    $ID=intval($_GET["pattern-enable"]);

    // Fetch current row to read enabled state
    $rows = acls_api_get("/proxy/url-rewrite/temp");
    $pattern = "";
    $currentEnabled = 0;
    if(is_array($rows)){
        foreach($rows as $r){
            if(intval($r["id"])==$ID){
                $pattern=$r["pattern"];
                $currentEnabled=intval($r["enabled"]);
                break;
            }
        }
    }

    $newEnabled = ($currentEnabled==1) ? 0 : 1;
    acls_api_send("PUT", "/proxy/url-rewrite/temp/$ID", ["enabled"=>$newEnabled]);

    echo refresh_global();
    return admin_tracks("Enable/disable web-filtering exclusion $pattern");
}

function rule_popup():bool{
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $bt="{add}";

    $ligne = [];
    if($ruleid>0){
        $bt="{apply}";
        $rows = acls_api_get("/proxy/url-rewrite/temp");
        if(is_array($rows)){
            foreach($rows as $r){
                if(intval($r["id"])==$ruleid){
                    $ligne = [
                        "maxmins"  => intval($r["maxMins"]),
                        "pattern"  => $r["pattern"],
                        "finaltime"=> intval($r["finalTime"]),
                    ];
                    break;
                }
            }
        }
    }

    if(intval($ligne["maxmins"])==0){$ligne["maxmins"]=15;}
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_numeric("maxmins","{ttl} ({minutes})",$ligne["maxmins"]);
    $form[]=$tpl->field_text("pattern","{website}",$ligne["pattern"],true);
    $html[]=$tpl->form_outside(null,$form,null,$bt,refresh_global(),"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function refresh_global():string{
    $page = CurrentPageName();
    if (isset($_GET["function"])) {
        $function = $_GET["function"];
        if (strlen($function) > 1) {
            $f[] = "$function();";
        }
    }
    if (isset($_GET["Mainfunc"])) {
        $Mainfunc = $_GET["Mainfunc"];
        if (strlen($Mainfunc) > 1) {
            $f[] = "$Mainfunc();";
        }
    }


    $f[] = "dialogInstance5.close();";
    return @implode(";", $f);
}



function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();

    $ruleid=intval($_POST["ruleid"]);
    $pattern=trim($_POST["pattern"]);
    $maxmins=intval($_POST["maxmins"]);
    if($maxmins<5){$maxmins=5;}
    $finaltime=time()+$maxmins*60;

    if($ruleid==0){
        $body = [
            "pattern"   => $pattern,
            "maxmins"   => $maxmins,
            "finaltime" => $finaltime,
            "enabled"   => 1,
        ];
        list($ok,$data) = acls_api_send("POST", "/proxy/url-rewrite/temp", $body);
    }else{
        $body = [
            "pattern"   => $pattern,
            "maxMins"   => $maxmins,
            "finalTime" => $finaltime,
        ];
        list($ok,$data) = acls_api_send("PUT", "/proxy/url-rewrite/temp/$ruleid", $body);
    }

    if(!$ok){
        echo $tpl->post_error(htmlspecialchars($data));
        return false;
    }

    return admin_tracks("Add or edit cache Webfiltering $pattern exclusion for $maxmins minutes");

}

function popup_main():bool{
    $Mainfunc=$_GET["Mainfunc"];
    $page       = CurrentPageName();
    echo "<div id='main-popup-temp'></div>
    <script>LoadAjax('main-popup-temp','$page?popup-table=yes&Mainfunc=$Mainfunc')</script>";
    return true;
}
function popup_table():bool{
    $page       = CurrentPageName();
    $Mainfunc=$_GET["Mainfunc"];
    $tpl        = new template_admin();
    echo "<div id='popup-temp-div-btn' style='margin-bottom:10px;margin-top:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table2=yes&Mainfunc=$Mainfunc");
    return true;
}
function OnlyActive():bool{

    $function=$_GET["function"];
    $Key=basename(__FILE__)."OnlyActive";
    if(!isset($_SESSION[$Key])){
        $_SESSION[$Key]=true;
    }else{
        unset($_SESSION[$Key]);
    }
    header("content-type: application/x-javascript");
    echo "$function();";
    return true;
}

function top_buttons():bool{
    $Mainfunc=$_GET["Mainfunc"];
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();

    $topbuttons[] = array("Loadjs('$page?rule-js=0&function=$function&Mainfunc=$Mainfunc')", ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&function=$function&Mainfunc=$Mainfunc')", ico_filter, "{OnlyActive}");
    $topbuttons[] = array("Loadjs('$page?disableall=yes&function=$function&Mainfunc=$Mainfunc')", ico_disabled, "{disable_all}");
    $topbuttons[] = array("Loadjs('$page?enableall=yes&function=$function&Mainfunc=$Mainfunc')", ico_check, "{enable_all}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all():bool{
    $function=$_GET["function"];
    $rows = acls_api_get("/proxy/url-rewrite/temp");
    if(is_array($rows)){
        foreach($rows as $r){
            if(intval($r["enabled"])==1){
                acls_api_send("PUT", "/proxy/url-rewrite/temp/".intval($r["id"]), ["enabled"=>0]);
            }
        }
    }
    echo refresh_global();
    echo "$function();";
    return admin_tracks("Disable all web-filtering exclusions");
}
function rule_enable_all():bool{
    $function=$_GET["function"];
    $rows = acls_api_get("/proxy/url-rewrite/temp");
    if(is_array($rows)){
        foreach($rows as $r){
            if(intval($r["enabled"])==0){
                acls_api_send("PUT", "/proxy/url-rewrite/temp/".intval($r["id"]), ["enabled"=>1]);
            }
        }
    }
    echo refresh_global();
    echo "$function();";
    return admin_tracks("Enable all web-filtering exclusions");
}
function popup_table2():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function =$_GET["function"];
    $Mainfunc=$_GET["Mainfunc"];
    $tableid    = time();

    $html[]="</div>";
    $search=$_GET["search"];

    $html[]="
<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>
	<thead>
    	<tr>
    	    <th nowrap>{created}</small></th>
    	    <th nowrap>{duration}</small></th>
    	    <th nowrap></th>
        	<th nowrap>{sitename}</th>
        	<th nowrap>{expire}</small></th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody id='urlrewritete-rows'>
";


    $c=0;

    $OnlyActive=false;
    $KeyActive=basename(__FILE__)."OnlyActive";
    if(isset($_SESSION[$KeyActive])){
        $OnlyActive=true;
    }

    // Normalize search term
    $searchPattern = null;
    if($search != null){
        $searchPattern = strtolower(str_replace("*", "", $search));
    }

    $rows = acls_api_get("/proxy/url-rewrite/temp");
    if(!is_array($rows)){ $rows=[]; }

    VERBOSE("GET /proxy/url-rewrite/temp → ".count($rows)." rows",__LINE__);

    foreach ($rows as $r){
        if($OnlyActive && intval($r["enabled"])!=1){ continue; }
        if($searchPattern!=null && strpos(strtolower($r["pattern"]),$searchPattern)===false){ continue; }

        $ID=intval($r["id"]);
        $pattern=$r["pattern"];
        $enable=intval($r["enabled"]);
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        // Pentest 2026-11-04 : motif (donnee externe) affiche sans encodage (XSS stockee).
        $pattern=req_html($pattern);
        $md=md5(serialize($r));
        $created=$tpl->time_to_date(intval($r["created"]),true);

        $enableIcon=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$ID&Mainfunc=$Mainfunc')","","AsDansGuardianAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$ID&md=$md&Mainfunc=$Mainfunc')","AsDansGuardianAdministrator");

        $c++;

        if($c>250){break;}
        $icoset=$tpl->icon_parameters("Loadjs('$page?rule-js=$ID&function=$function&Mainfunc=$Mainfunc')","AsDansGuardianAdministrator");

        $maxmins=intval($r["maxMins"]);
        if($maxmins==0){$maxmins=15;}
        $finaltime=intval($r["finalTime"]);
        $duration=$tpl->time_to_date($finaltime,true);
        $color="";
        if($finaltime<time()){
            $color="color:#CCCCCC;background-color:unset;";
            $expire=" ({expired})";
        }else{
            $expire="";
        }

    $html[]="<tr id='$md'>
                <td style='width:1%' nowrap>$created</td>
                <td style='width:1%' nowrap>{$maxmins}mn</td>
                <td style='width:1%'  nowrap>$icoset</td>
				<td style='width:100%'><code style='font-size: 14px;font-weight: bold;$color'>$pattern</code>$expire</td>
				<td style='width:1%' nowrap>$duration</td>
				<td style='width:1%'  nowrap >$enableIcon</td>
				<td style='width:1%'  nowrap >$delete</td>
				</tr>";

    }
        $html[]="</tbody>";
        $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='urlrewritete-pager'></span></div></div></div>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('popup-temp-div-btn','$page?top-buttons=yes&function=$function&Mainfunc=$Mainfunc');";
        $html[]="</script>";
        echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"urlrewritete-rows","urlrewritete-pager");
        return true;
}
