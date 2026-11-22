<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");


// Pentest 2026-11-04 : serviceid etait recopie sans encodage dans la page (XSS).
if(isset($_GET["serviceid"])){$_GET["serviceid"]=intval($_GET["serviceid"]);}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-table2"])){popup_table2();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["UserAgent"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}

service_js();

function enable_feature():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["serviceid"]);
    $enable=intval($_GET["enable-rule-js"]);
    $sockngix=new socksngix(($serviceid));
    $sockngix->SET_INFO("FilterUserAgents",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);

    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    return admin_tracks("Turn feature to $enable for deny User-Agents on  $get_servicename reverse-proxy site");

}

function service_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{cache}","$page?popup-main=$serviceid",1200);
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog5("{useragent_database}: $title","$page?popup-rule=$rule&serviceid=$serviceid");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $md=$_GET["md"];
    $UserAgent=$_GET["pattern-remove"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FUserAgents")));
    unset($data[$UserAgent]);
    $encoded=serialize($data);
    $sock->SET_INFO("FUserAgents",base64_encode($encoded));
    echo "$('#$md').remove();\n";
    echo refresh_global_no_close($serviceid);
    return true;

}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}


function rule_enable():bool{
    $UserAgent=$_GET["pattern-enable"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FUserAgents")));
    if(intval($data[$UserAgent])==1){
        $data[$UserAgent]=0;
    }else{
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUserAgents",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Enable={$data[$UserAgent]} For reverse-proxy $get_servicename User-Agent rule $UserAgent");
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $bt="{add}";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("UserAgent","{http_user_agent}","",true);
    $html[]=$tpl->form_outside(null,$form,null,$bt,refresh_global($serviceid),"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function refresh_global_no_close($serviceid):string{
    $f[]="LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');";
    return @implode(";",$f)."\n";

}

function refresh_global($serviceid):string{
    $page=CurrentPageName();
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');";
    $f[]="dialogInstance5.close();";
    return @implode(";",$f);
}


function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FUserAgents")));

    if(trim($_POST["UserAgent"])==null){return false;}
    $data[$_POST["UserAgent"]]=1;
    $encoded=serialize($data);
    $sock->SET_INFO("FUserAgents",base64_encode($encoded));
    $get_servicename=get_servicename($serviceid);
    return admin_tracks("Add a new User-Agent {$_POST["UserAgent"]} to deny for reverse-proxy $get_servicename");

}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "
<div id='main-popup-progress-$serviceid'></div>
<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}



function popup_table():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();
    echo $tpl->search_block($page,null,null,null,"&popup-table2=$serviceid");

    return true;
}



function rule_disable_all(){
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FUserAgents")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=0;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUserAgents",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all user-Agent Deny For reverse-proxy $get_servicename");
}
function rule_enable_all(){
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FUserAgents")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUserAgents",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Enable all user-Agent Deny For reverse-proxy $get_servicename");
}
function popup_table2():bool{
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $q = new postgres_sql();
    $function =$_GET["function"];
    $tableid    = time();

    $html[]="</div>";

    $search=$_GET["search"];

    $html[]="
<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>
<table class='awc-tbl awc-tbl-compact'>
	<thead>
    	<tr>
        	<th nowrap>{date}</th>
        	<th nowrap>{duration}</th>
        	<th nowrap>{path}</th>
        	<th nowrap>{size}</th>
        </tr>
  	</thead>
	<tbody>
";

    // Pentest 2026-11-04 : search etait concatene dans la requete.
    $sql="SELECT * FROM nginxcached WHERE serviceid=$serviceid ORDER BY zdate DESC LIMIT 500 ";
    $params=array();
    if($search<>null){
        $search=str_replace("*","%",$search);
        $sql="SELECT * FROM nginxcached WHERE serviceid=$serviceid AND path LIKE $1 ORDER BY zdate DESC LIMIT 500 ";
        $params=array($search);
    }

    $results=$q->QUERY_SQL_PARAMS($sql,$params);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $zdate=strtotime($ligne["zdate"]);
        $path=htmlspecialchars($ligne["path"]);
        $sizebytes=FormatBytes($ligne["sizebytes"]/1024);
        $zTime=$tpl->time_to_date($zdate,true);
        $duration=distanceOfTimeInWords($zdate,time());
        $md=md5(serialize($ligne));
        $len=strlen($path);
        if($len>110){
            $path_text=substr($path,0,97);
            $path_tool=str_replace("+","+<br>",$path);
            $path=$tpl->td_href("$path_text...","<div>$path_tool</div>","blur()");
        }
    $html[]="<tr id='$md'>
				<td width=1%  nowrap>$zTime</td>
				<td width=1%  nowrap>$duration</td>
				<td width=99%  nowrap >$path</td>
				<td style='width:1%' nowrap>$sizebytes</td>
				</tr>";

    }

        $html[]="</tbody></table></div></div></div>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="</script>";
        echo awc_table_style().$tpl->_ENGINE_parse_body($html);
        return true;
        }