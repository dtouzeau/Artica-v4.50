<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
//$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["compile"])){compile_rules();exit;}
if(isset($_GET["ruleid-tabs"])){ruleid_tabs();exit;}
if(isset($_GET["ruleid-js"])){ruleid_js();exit;}
if(isset($_GET["ruleid-delete"])){ruleid_delete();exit;}
if(isset($_POST["ruleid-delete"])){ruleid_delete_perform();exit;}
if(isset($_GET["ruleid-enable"])){ruleid_enable();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["subid"])){subrules_save();exit;}
if(isset($_POST["ID"])){rule_save();exit;}


if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["rule-params"])){ruleid_parameters();exit;}
if(isset($_POST["rulename"])){ruleid_parameters_save();exit;}
if(isset($_GET["subrules-start"])){subrules_start();exit;}
if(isset($_GET["subrules-table"])){subrules_table();exit;}
if(isset($_GET["subrules-new"])){subrules_new();exit;}
if(isset($_GET["subrules-popup"])){subrules_popup();exit;}
if(isset($_GET["subrules-enable"])){subrules_enable();exit;}
if(isset($_GET["subrules-delete"])){subrules_delete();exit;}
if(isset($_POST["subrules-delete"])){subrules_delete_perform();exit;}
if(isset($_GET["description-js"])){description_js();exit;}


if(isset($_GET["subrules-id-js"])){subrules_id_js();exit;}
if(isset($_GET["subrules-tabs"])){subrules_tabs();exit;}
if(isset($_GET["subrules-variables-start"])){subrules_variables_start();exit;}
if(isset($_GET["subrules-variables-table"])){subrules_variables_table();exit;}
if(isset($_GET["subrules-variable-js"])){subrules_variable_js();exit;}
if(isset($_GET["subrules-variable-addtable"])){subrules_variable_add_table();}

if(isset($_GET["fields-js"])){fields_js();exit;}
if(isset($_GET["builded-conf"])){builded_conf();exit;}
if(isset($_GET["buildedconf-popup"])){builded_conf_popup();exit;}


VERBOSE("Return page.?",__LINE__);
page();

function compile_rules(){
    $sock=new sockets();
    $tpl=new template_admin();

    $data=$sock->REST_API_NGINX("/reverse-proxy/waf/rules");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error(json_last_error_msg());
    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    return $tpl->js_ok("");

}
function rule_move_js(){
    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $tpl=new template_admin();
    $dir=$_GET["dir"];
    $aclid=intval($_GET["ruleid"]);
    $results=$q->QUERY_SQL("SELECT zorder FROM mod_security_rules WHERE ID='$aclid'");
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        return;
    }
    $ligne=$results[0];
    $zorder=intval($ligne["zorder"]);
    echo "// Current order = $zorder\n";

    if($dir=="up"){
        $zorder=$zorder-1;
        if($zorder<0){$zorder=0;}
    }
    else{
        $zorder=$zorder+1;
    }
    echo "// New order = $zorder\n";
    $q->QUERY_SQL("UPDATE mod_security_rules SET zorder='$zorder' WHERE id='$aclid'");
    if(!$q->ok){$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "alert('$q->mysql_error');";return;}


    if($dir=="up"){
        $zorder2=$zorder+1;
        if($zorder2<0){$zorder2=0;}
        $sql="UPDATE mod_security_rules SET zorder=$zorder2 WHERE `id`<>'$aclid' AND zorder=$zorder";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    }
    if($dir=="down"){
        $zorder2=$zorder-1;
        if($zorder2<0){$zorder2=0;}
        $sql="UPDATE mod_security_rules SET zorder=$zorder2 WHERE `id`<>'$aclid' AND zorder=$zorder";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    }


    $c=0;
    $results=$q->QUERY_SQL("SELECT ID FROM mod_security_rules ORDER BY zorder");
    foreach ($results as $index=>$ligne){
        $aclid=$ligne["ID"];
        echo "// $aclid New order = $c";
        $q->QUERY_SQL("UPDATE mod_security_rules SET zorder='$c' WHERE ID='$aclid'");
        $c++;
    }
}
function subrules_variable_js(){
    $subruleid=intval($_GET["subrules-variable-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog3("{link_variable} N.$subruleid","$page?subrules-variable-addtable=$subruleid",850);
}
function subrules_variable_add_table(){
    $subruleid=intval($_GET["subrules-variable-addtable"]);
    $sql="SELECT variables FROM mod_security_patterns WHERE ID=$subruleid";
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $variables=unserialize(base64_decode($q->mysqli_fetch_array($sql)));
    foreach ($variables as $variable){$ALREADY[$variable]=true;}

}
function builded_conf(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{file_configuration}", "$page?buildedconf-popup=yes");

}
function builded_conf_popup(){
    $tpl=new template_admin();
    $f=explode("\n",@file_get_contents("/etc/nginx/owasp-modsecurity-crs/ARTICA-RULES.conf"));

    foreach ($f as $line){
        $t[]=$line;
    }

    $form=$tpl->field_textareacode("xxx", null, @implode("\n", $t));
    echo $tpl->form_outside(null, $form,null,null);
}

function subrules_variables_start(){
    $page=CurrentPageName();
    $ruleid=$_GET["subrules-variables-start"];
    echo "<div id='subrules-variables-table' style='margin-top:10px'></div>
     <script>LoadAjax('subrules-variables-table','$page?subrules-variables-table=$ruleid')</script>
";

}
function description_js(){

}

function fields_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["id"];
    $alert=$tpl->javascript_parse_text("{fields} {not_supported} {with}:");
    $suffix_form=$_GET["suffix-form"];
    $scopeid=md5("scope$suffix_form");
    $operatorid=md5("operator$suffix_form");

    $js[]="function CheckFields(){";
    $js[]="var scontinue=false;";
    $js[]="subid=document.getElementById('subid').value;";
    $js[]="value=document.getElementById('$scopeid').value;";
    $js[]="operator=document.getElementById('$operatorid').value;";
    $js[]="if(operator=='Countries'){";
    $js[]="\tLoadjs('fw.modsecurity.rules.countries.php?id='+subid+'&suffix-form=$suffix_form');";
    $js[]="\treturn;";
    $js[]="}";
    $js[]="if(value=='ARGS'){ scontinue = true;}";
    $js[]="if(value=='REQUEST_HEADERS'){ scontinue = true;}";
    $js[]="if(!scontinue){alert('$alert'+value);return false;}";
    $js[]="document.getElementById('$id').disabled=false;";
    $js[]="document.getElementById('$id').readOnly=false;";
    $js[]="}";
    $js[]="CheckFields();";
    echo @implode("\n",$js);

}

function table_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>&nbsp;</div>";
    echo $tpl->search_block($page);
}

function subrules_start(){
    $page=CurrentPageName();
    $ruleid=$_GET["subrules-start"];
    echo "<div id='subrules-table' style='margin-top:10px'></div>
     <script>LoadAjax('subrules-table','$page?subrules-table=$ruleid')</script>
";

}

function subrules_id_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["subrules-id-js"]);
    $tpl->js_dialog2("{rule} N.$id","$page?subrules-tabs=$id",1024);
}
function ruleid_delete(){
    $tpl=new template_admin();
    $ruleid=intval($_GET["ruleid-delete"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM mod_security_rules WHERE ID='$ruleid'");
    $NAT_TYPE_TEXT="{rulename}: {$ligne["rulename"]}";
    $tpl->js_confirm_delete($NAT_TYPE_TEXT,"ruleid-delete",$ruleid,"$('#$md').remove()");
}
function ruleid_enable(){
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename,enabled FROM mod_security_rules WHERE ID='$ID'");
    $rulename=$ligne["rulename"];
    if(intval($ligne["enabled"])==0){$enabled=1;}else{$enabled=0;}
    $q->QUERY_SQL("UPDATE mod_security_rules SET enabled=$enabled WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_error_stop($q->mysql_error);return false;}
    admin_tracks("Change Web application Firewall #$ID $rulename to enabled=$enabled");
    return true;
}
function ruleid_delete_perform(){
    $ruleid=intval($_POST["ruleid-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM mod_security_rules WHERE ID='$ruleid'");
    $NAT_TYPE_TEXT="{$ligne["rulename"]}";
    $q->QUERY_SQL("DELETE FROM  mod_security_patterns WHERE ruleid='$ruleid'");
    if(!$q->ok){echo $q->mysql_error;return false;}
    $q->QUERY_SQL("DELETE FROM mod_security_rules WHERE ID='$ruleid'");
    if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Delete Web Application Firewall rule ID: $ruleid ($NAT_TYPE_TEXT)");
    return true;
}

function ruleid_js(){
	$page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$tpl=new template_admin();
	$ruleid=intval($_GET["ruleid-js"]);
    $function=$_GET["function"];
    if($ruleid==0){
		$NAT_TYPE_TEXT="{new_rule}";
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT * FROM mod_security_rules WHERE ID='$ruleid'");
		$NAT_TYPE_TEXT="{rulename}: {$ligne["rulename"]}";
	}
	$tpl->js_dialog1("$NAT_TYPE_TEXT","$page?ruleid-tabs=$ruleid&function=$function",1024);
}
function subrules_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["subrules-tabs"]);
    $array["{parameters}"]="$page?subrules-popup=$id";
    echo $tpl->tabs_default($array);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{firewall_rules}"]="$page?table-start=yes";
    $array["{global_rules}"] = "fw.modsecurity.defrules.php";

//PostfixAutoBlockCompileFW
    echo $tpl->tabs_default($array);
}

function ruleid_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["ruleid-tabs"]);
    $function=$_GET["function"];
    $array["{parameters}"]="$page?rule-params=$id&function=$function";
    if($id>0) {
        $array["{rules}"] = "$page?subrules-start=$id&function=$function";
    }

    echo $tpl->tabs_default($array);
}

function ruleid_parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["rule-params"]);
    $function=$_GET["function"];

    $allow[0]="{allow}";
    $allow[1]="{deny}";

    $phases=phase_list();

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    if(!$q->FIELD_EXISTS("mod_security_rules","action")){
        $q->QUERY_SQL("ALTER TABLE mod_security_rules ADD action TEXT");
    }
    if(!$q->FIELD_EXISTS("mod_security_rules","phase")){
        $q->QUERY_SQL("ALTER TABLE mod_security_rules ADD phase INTEGER NOT NULL DEFAULT 1");
    }

    $ligne=$q->mysqli_fetch_array("SELECT * FROM mod_security_rules WHERE ID='$ruleid'");
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_choose_websites("siteid","{websites}",intval($ligne["siteid"]));
    $form[]=$tpl->field_array_hash($allow, "action", "nonull:{access}", $ligne["action"],true,null,false);
    $form[]=$tpl->field_array_hash($phases, "phase", "nonull:Phase", $ligne["phase"],true,null,false);
    $form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"]);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);


    if($ruleid>0){
        $title=$ligne["rulename"];
        $btname="{apply}";
        $js="$function();";
    }else{
        $title="{new_rule}";
        $js="dialogInstance1.close();$function();";
        $btname="{add}";
    }

    echo $tpl->form_outside($title,$form,null,$btname,$js,"AsWebMaster");

}
function ruleid_parameters_save():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=$_POST["ruleid"];
    $phase=$_POST["phase"];
    $_POST["siteid"]=intval($_POST["siteid"]);
    if(!$q->FIELD_EXISTS("mod_security_rules","siteid")){
        $q->QUERY_SQL("ALTER TABLE mod_security_rules ADD siteid INTEGER NOT NULL DEFAULT 0");
    }
    $_POST["description"]=$q->sqlite_escape_string2($_POST["description"]);
    $_POST["rulename"]=$q->sqlite_escape_string2($_POST["rulename"]);

    if($id==0){
        $q->QUERY_SQL("INSERT INTO mod_security_rules (rulename,description,enabled,action,siteid,phase)
        VALUES ('{$_POST["rulename"]}','{$_POST["description"]}',1,'{$_POST["action"]}','{$_POST["siteid"]}','$phase')");
        if(!$q->ok){
           echo $tpl->post_error($q->mysql_error);
           return false;
        }
        return admin_tracks("Create new Web Application Firewall rule {$_POST["rulename"]} {$_POST["action"]}");

    }

    $q->QUERY_SQL("UPDATE mod_security_rules SET rulename='{$_POST["rulename"]}',
    description='{$_POST["description"]}', action='{$_POST["action"]}',siteid='{$_POST["siteid"]}',phase='$phase' WHERE ID=$id");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
    }
    return admin_tracks("Update Web Application Firewall rule {$_POST["rulename"]} {$_POST["action"]}");

}
function subrules_new(){
    $page=CurrentPageName();
    $ruleid=$_GET["subrules-new"];
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $tpl=new template_admin();

    $q->QUERY_SQL("INSERT INTO mod_security_patterns (ruleid,zorder,enabled) VALUES ($ruleid,0,1)");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return;
    }

    echo "LoadAjax('subrules-table','$page?subrules-table=$ruleid')";

}
function subrules_delete(){
    $tpl=new template_admin();
    $ID=intval($_GET["subrules-delete"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM mod_security_patterns WHERE ID='$ID'");
    $id=$ligne["ID"];
    $scope=$ligne["scope"];
    $operator=$ligne["operator"];
    $description=$ligne["description"];
    if($description==null){$description="{unknown}";}
    if($operator==null){$operator="{unknown}";}
    $text="$scope $operator $description";
    $tpl->js_confirm_delete($text,"subrules-delete",$ID,"$('#$md').remove();");


}
function subrules_delete_perform():bool{
    $ID=intval($_POST["subrules-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM mod_security_patterns WHERE ID='$ID'");
    $scope=$ligne["scope"];
    $operator=$ligne["operator"];
    $description=$ligne["description"];
    if($description==null){$description="{unknown}";}
    if($operator==null){$operator="{unknown}";}
    $text="$scope $operator $description";
    $tpl=new template_admin();
    $text=$tpl->_ENGINE_parse_body($text);
    $q->QUERY_SQL("DELETE FROM mod_security_patterns WHERE ID=$ID");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
   return  admin_tracks("Delete Web Application Firewall subrule #$ID $text");
}

function subrules_enable(){
    $tpl=new template_admin();
    $ID=intval($_GET["subrules-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM mod_security_patterns WHERE ID='$ID'");
    if($ligne["enabled"]==1){
        $q->QUERY_SQL("UPDATE mod_security_patterns SET enabled=0 WHERE ID=$ID");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        admin_tracks("Disable Web Application Firewall subrule #$ID");
        return true;
    }
    $q->QUERY_SQL("UPDATE mod_security_patterns SET enabled=1 WHERE ID=$ID");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    admin_tracks("Enable Web Application Firewall subrule #$ID");
    return true;
}

function subrules_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["subrules-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM mod_security_patterns WHERE ID='$ID'");
    if(!$q->FIELD_EXISTS("mod_security_patterns","scope")){
        $q->QUERY_SQL("ALTER TABLE mod_security_patterns ADD `scope` NOT NULL DEFAULT 'QUERY_STRING'");
    }
    if(!$q->FIELD_EXISTS("mod_security_patterns","writelog")){
        $q->QUERY_SQL("ALTER TABLE mod_security_patterns ADD `writelog` INTEGER NULL DEFAULT '0'");
    }
    if(!$q->FIELD_EXISTS("mod_security_patterns","negative")){
        $q->QUERY_SQL("ALTER TABLE mod_security_patterns ADD `negative` INTEGER NULL DEFAULT '0'");
    }
    if(!$q->FIELD_EXISTS("mod_security_patterns","fields")){
        $q->QUERY_SQL("ALTER TABLE mod_security_patterns ADD `fields` TEXT NULL");
    }

    $mainrule=$ligne["ruleid"];
    $js[]="LoadAjax('subrules-table','$page?subrules-table=$mainrule')";
    $js[]="dialogInstance2.close();";


    $BROWSER_OPERATORS_POPUP=BROWSER_OPERATORS_POPUP();

    $form[]=$tpl->field_hidden("subid",$ID);
    $form[]=$tpl->field_hidden("phase",2); // A voir plus tard

    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_text("explain","{name}",$ligne["explain"]);
    $form[]=$tpl->field_browse_waf_scope("scope","{scope}",$ligne["scope"]);
    $form[]=$tpl->field_array_hash($BROWSER_OPERATORS_POPUP,"operator","nonull:{operator}",$ligne["operator"]);
    $form[]=$tpl->field_checkbox("negative","{negation}",$ligne["negative"]);
    $form[]=$tpl->field_text_button("fields","{fields}",$ligne["fields"]);
    $form[]=$tpl->field_text("description","{pattern}",$ligne["description"],false);


    echo $tpl->form_outside("{rule} #$ID",
        @implode("\n", $form),null,"{apply}",
        @implode(";", $js),"AsSquidAdministrator");

}

function subrules_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["subid"]);
    $enabled=intval($_POST["enabled"]);
    $scope=trim(strtoupper($_POST["scope"]));
    $operator=$_POST["operator"];
    $description=$_POST["description"];
    $negative=intval($_POST["negative"]);
    $phase=intval($_POST["phase"]);
    $fields=trim($_POST["fields"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $explain=$q->sqlite_escape_string2($_POST["explain"]);
    $sql="UPDATE mod_security_patterns SET enabled=$enabled, scope='$scope',operator='$operator',description='$description',negative='$negative',phase=$phase,fields='$fields', explain='$explain' WHERE ID=$ID";
    $q->QUERY_SQL($sql);

    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    admin_tracks("UPDATE Web application firewall rule #$ID enabled=$enabled $scope $operator $description");
    return true;

}


function enable_signature(){
	$t=time();
	$id=$_GET["enable-signature"];
	$rulefile=$_GET["cat"];
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM suricata_sig WHERE signature='$id'"));
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	$tpl=new template_admin();
	
	if($ligne["enabled"]==0){
		$q->QUERY_SQL("UPDATE suricata_sig SET enabled=1 WHERE signature='$id'");
		if(!$q->ok){echo "alert('$q->mysql_error')";return;}
		
		echo "
document.getElementById('id-$id').style.color = 'black';
document.getElementById('cat-$id').style.color = 'black';					
document.getElementById('signature-$id').className= 'fas fa-check-square-o';				
				
";
return;		
	}
	
	$q->QUERY_SQL("UPDATE suricata_sig SET enabled=0 WHERE signature='$id'");
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}	
	echo "
	document.getElementById('id-$id').style.color = '#8a8a8a';
	document.getElementById('cat-$id').style.color = '#8a8a8a';
	document.getElementById('signature-$id').className= 'fa fa-square-o';
	
	";	
}

function enable_firewall(){
	$t=time();
	$id=$_GET["enable-firewall"];
	$rulefile=$_GET["cat"];
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM suricata_sig WHERE signature='$id'"));
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	$tpl=new template_admin();
	
	if($ligne["firewall"]==0){
		$q->QUERY_SQL("UPDATE suricata_sig SET firewall=1 WHERE signature='$id'");
		if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	
		echo "
		document.getElementById('firewall-$id').className= 'fas fa-check-square-o';
	
		";
		return;
	}
	
	$q->QUERY_SQL("UPDATE suricata_sig SET firewall=0 WHERE signature='$id'");
			if(!$q->ok){echo "alert('$q->mysql_error')";return;}
			echo "
			document.getElementById('firewall-$id').className= 'fa fa-square-o';
	
			";	
	
	
}



function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $sql="CREATE TABLE IF NOT EXISTS `mod_security_rules` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`zorder` INTEGER,
		`enabled` INTEGER,
        `siteid` INTEGER,
		`rulename` TEXT,
		`description` TEXT
	)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error("$q->mysql_error (".__LINE__.")");}

    $sql="CREATE TABLE IF NOT EXISTS `mod_security_patterns` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`ruleid` INTEGER,
		`zorder` INTEGER,
		`enabled` INTEGER,
		`variables` TEXT,
		`operator` TEXT,
		`phase` INTEGER,
		`transformation` TEXT,
		`action` TEXT,
		`explain` TEXT,
		`description` TEXT
	)";

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error("$q->mysql_error (".__LINE__.")");}

    $html=$tpl->page_header("{WAF_LEFT} {rules}",
        "fa fa-bars","{ModSecurityExplain}","$page?tabs=yes","web-firewall-rules","progress-waf-rules",false);


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {WAF} {rules}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}



function subrules_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $ruleid=$_GET["subrules-table"];
    $class=null;


    $add="Loadjs('$page?subrules-new=$ruleid');";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $BROWSER_OPERATORS_POPUP=BROWSER_OPERATORS_POPUP();
    $sql="SELECT * FROM mod_security_patterns WHERE ruleid=$ruleid ORDER BY zorder DESC LIMIT 250";
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $results=$q->QUERY_SQL($sql);
    $TRCLASS=null;
    $phase_list=phase_list();
    foreach ($results as $ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $id=$ligne["ID"];
        $isnot=null;
        $md=md5(serialize($ligne));
        $scope=$ligne["scope"];
        $operator=$ligne["operator"];
        $negative=$ligne["negative"];
        $descriptionsrc=$ligne["description"];
        $OPERATOR_TEXT="";
        $explain=$ligne["explain"];
        if(strlen($explain)>1 && $ligne["description"]==null){
            $ligne["description"]=$explain;
        }
        if($ligne["description"]==null){$ligne["description"]="{new_rule} $id";}
        else{
            $expl="";
            $OPERATOR_TEXT=$BROWSER_OPERATORS_POPUP[$operator];
            if(strlen($explain)>1){
                $expl=$ligne["explain"].":&nbsp;";
            }
            if(strlen($descriptionsrc)>1){
                $descriptionsrc=" &laquo;$descriptionsrc&raquo;";
            }
            $ligne["description"]="$expl$scope $OPERATOR_TEXT$descriptionsrc";
        }

        if($scope=="ARGS"){
            $expl="";
            if(strlen($explain)>1){
                $expl=$ligne["explain"].":&nbsp;";
            }
            if($ligne["fields"]<>null){
                if(strlen($descriptionsrc)>1){
                    $descriptionsrc=" &laquo;$descriptionsrc&raquo;";
                }
                $ligne["description"]="$expl$scope &laquo;{$ligne["fields"]}&raquo; $OPERATOR_TEXT$descriptionsrc";
            }
        }


        if($negative==1){$isnot="<span class='label label-info'>{isnot}</span>&nbsp;";}
        $description=$tpl->td_href($ligne["description"],null,"Loadjs('$page?subrules-id-js=$id')");



        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%' nowrap>{$id}.</td>";
        $html[]="<td style='vertical-align:middle'>$isnot{$description}</span></td>";
        $html[]="<td style='vertical-align:middle;width:1%' nowrap><center>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?subrules-enable=$id')")."</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%' nowrap><center>".$tpl->icon_delete("Loadjs('$page?subrules-delete=$id&md=$md')")."</center></td>";
        $html[]="</tr>";
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
$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });

</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $t=time();
	$eth_sql=null;
	$token=null;
	$class=null;
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
    $function=$_GET["function"];

    $prc1="style='vertical-align:middle;width:1%'";
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' $prc1>ID</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{action}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{website}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' $prc1>{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' $prc1>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' $prc1>DEL</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

    $search=$_GET["search"];
    $search="*$search*";
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);

    if(strpos("  $search","%")>0){

        $sql="SELECT * FROM mod_security_rules WHERE ( (description LIKE '$search') OR (rulename LIKE '$search') ) ORDER BY zorder DESC";
    }else{
        $sql="SELECT * FROM mod_security_rules ORDER BY zorder DESC LIMIT 250";
    }

    $allow[0]="<span class='label label-primary'>{allow}</span>";
    $allow[1]="<span class='label label-danger'>{deny}</span>";
    $phases=phase_list();

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$results=$q->QUERY_SQL($sql);
	$TRCLASS=null;

	foreach ($results as $ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $id=$ligne["ID"];
        $md=md5(serialize($ligne));
        $phase=$phases[$ligne["phase"]];
        $siteid=$ligne["siteid"];
        $ligne["rulename"]=$tpl->td_href("{$ligne["rulename"]}",null,"Loadjs('$page?ruleid-js=$id&function=$function')");

        $delete=$tpl->icon_delete("Loadjs('$page?ruleid-delete=$id&md=$md')");

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td $prc1 nowrap>{$id}.</td>";
        if($siteid==0){$sitename="<div class='center'>*</div>";}else{
            $sql = "SELECT servicename FROM nginx_services WHERE ID=$siteid";
            $WebService=$q->mysqli_fetch_array($sql);
            $sitename=$WebService["servicename"];
        }
        $up=$tpl->icon_up("Loadjs('$page?move-js=yes&id=$id&dir=up&ruleid=$id')");
        $down=$tpl->icon_down("Loadjs('$page?move-js=yes&id=$id&dir=down&ruleid=$id')");

        $html[]="<td $prc1 nowrap>{$allow[$ligne["action"]]}</td>";
        $html[]="<td $prc1 nowrap>$sitename</td>";
        $html[]="<td style='width:50%'>{$ligne["rulename"]} <sm
>$phase</small></td>";
		$html[]="<td style='width:50%' >{$ligne["description"]}</span></td>";
		$html[]="<td $prc1 class='center' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?ruleid-enable=$id')","signature-$id")."</td>";
        $html[]="<td $prc1 class='center' nowrap>$up&nbsp;$down</td>";
        $html[]="<td $prc1 class='center' nowrap>$delete</td>";
		$html[]="</tr>";
		

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='8'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);

    $add="Loadjs('$page?ruleid-js=0&function=$function');";

    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?compile=yes');", ico_save, "{PostfixAutoBlockCompileFW}");

    $TINY_ARRAY["TITLE"]="{WAF_LONG} {rules}";
    $TINY_ARRAY["ICO"]="fa fa-bars";
    $TINY_ARRAY["EXPL"]="{ModSecurityExplain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $html[]= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);






}
function enable(){
	
	$filename=$_POST["filename"];
	$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM suricata_rules_packages WHERE rulefile='$filename'");
	$enabled=intval($ligne["enabled"]);
	if($enabled==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE suricata_rules_packages SET `enabled`='$enabled' WHERE rulefile='$filename'");
	if(!$q->ok){echo $q->mysql_error;}
}

function phase_list():array{

    $phase[1]="Phase 1: {LimitRequestFields}";
    $phase[2]="Phase 2: {request_body}";
    $phase[3]="Phase 3: {response_headers}";
    $phase[4]="Phase 4: {response_body}";
    return $phase;

}



function BROWSER_OPERATORS_POPUP(){
    $VARIABLES["rx"]="{regular_expression}";
    $VARIABLES["beginsWith"]="{begins_with}";
    $VARIABLES["endsWith"]="{End With}";
    $VARIABLES["contains"]="{Contains}";
    $VARIABLES["ipMatch"]="{ip_match}";
    $VARIABLES["Countries"]="{countries}";
    $VARIABLES["containsWord"]="{contains_word}";
    $VARIABLES["detectXSS"]="{detect_XSS}";
    $VARIABLES["detectSQLi"]="found SQL injection payload";
    $VARIABLES["isNULL"]="{does_not_exists}";
    return $VARIABLES;

}