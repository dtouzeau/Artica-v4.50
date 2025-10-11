<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["autofill"])){autofill();exit;}
page();


function page(){
    //fw.wordpress.firewall.php
    //fa-solid fa-block-brick-fire
    //APP_NGINX_FW_EXPLAIN
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{firewall} {outgoing_wbl}",
        "fa-solid fa-block-brick-fire","{APP_NGINX_FW_EXPLAIN}","$page?table=yes","nginx-outfw","reconfigure-wordpress-firewall",false,"wordpress-secure-firewall");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{outgoing_wbl}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);

}

function delete(){

    $ID=intval($_GET['delete']);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $q->QUERY_SQL("DELETE FROM wp_firewall WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return;}
    header("content-type: application/x-javascript");
    echo "$('#{$_GET["md"]}').remove();";

}

function enable(){
    $tpl=new template_admin();
    $ID=intval($_GET['enable']);
    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM wp_firewall WHERE ID=$ID");
    echo "// $ID == {$ligne["enabled"]}\n";
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    if(intval($ligne["enabled"])==0){
        $ena=1;
    }else{$ena=0;}
    echo "// UPDATE wp_firewall SET enabled='{$ena}' WHERE ID=$ID\n";
    $q->QUERY_SQL("UPDATE wp_firewall SET enabled='{$ena}' WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

}

function js(){
    $page=CurrentPageName();
    $ID=intval($_GET["js"]);
    $wpid=intval($_GET["wpid"]);
    $tpl=new template_admin();




    if($ID==0){$title="{new_rule}";}else{
        $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
        $ligne=$q->mysqli_fetch_array("SELECT address,port FROM wp_firewall WHERE ID=$ID");
        $title=$ligne["address"].":".$ligne["port"];
    }
    $tpl->js_dialog1($title,"$page?popup=$ID");


}

function autofill(){
    $ID=intval($_GET["autofill"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_firewall WHERE ID=$ID");
    $description=$tpl->javascript_parse_text($ligne["description"]);

    if($ligne["port"]==80){$ligne["port"]="80 OR 443";}
    if($ligne["port"]==443){$ligne["port"]="443 OR 80";}

    header("content-type: application/x-javascript");
    echo "document.getElementById('wpfw2-$ID').innerHTML='$description';\n";
    echo "document.getElementById('wpfw1-$ID').innerHTML='{$ligne["address"]}:{$ligne["port"]}';\n";

}

function popup(){
    $page=CurrentPageName();
    $ID=intval($_GET["popup"]);
    $wpid=intval($_GET["wpid"]);
    $tpl=new template_admin();
    if($ID==0){
        $title="{new_rule}";
        $ligne["address"]="0.0.0.0";
        $btname="{create}";
        $jsafter="dialogInstance1.close();LoadAjax('wordpress-secure-firewall','$page?table=yes');";
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_firewall WHERE ID=$ID");
        $title=$ligne["address"];
        $btname="{apply}";
        $jsafter="Loadjs('$page?autofill=$ID');";
    }

    $tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_text("address","{address}",$ligne["address"],true);
    $form[]=$tpl->field_text("port","{remote_port}",$ligne["port"],true);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    echo $tpl->form_outside($title,$form,"{wordpress_firewall_explain}","$btname",$jsafter,"AsWebSecurity",true);



}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $port=intval($_POST["port"]);


    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $description=$q->sqlite_escape_string2($_POST["description"]);

    if($ID==0){
        $q->QUERY_SQL("INSERT INTO wp_firewall (address,port,description) VALUES('{$_POST['address']}','$port','$description')");
        if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";return;}
        admin_tracks("Add a new whitelisted destination for Web Firewall {$_POST['address']}:$port");
        return;
    }

    $q->QUERY_SQL("UPDATE wp_firewall SET address='{$_POST['address']}',port=$port,description='$description' WHERE ID=$ID");
    if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";return;}
    admin_tracks("Update whitelisted destination for Web Firewall {$_POST['address']}:$port");

}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,"","");


}

function search(){
    $search=trim($_GET["search"]);
    $tpl=new template_admin();
    $EnableNginxFW=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"));


    $search="*$search*";
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);

    if($EnableNginxFW==0){
        $TINY_ARRAY["TITLE"]="{firewall} {outgoing_wbl}";
        $TINY_ARRAY["ICO"]="far fa-shield";
        $TINY_ARRAY["EXPL"]="{APP_NGINX_FW_EXPLAIN}";
        $TINY_ARRAY["URL"]="nginx-status";
        $TINY_ARRAY["BUTTONS"]=null;
        $TINY_ARRAY["DANGER"]=true;
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

        echo $tpl->div_error("{feature_not_installed}||{error_feature_not_installed}").
            "<script>$jstiny</script>";
        return false;

    }

    $t=time();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $page=CurrentPageName();
    $tpl=new template_admin();

    $sql="CREATE TABLE IF NOT EXISTS `wp_firewall` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`address` TEXT,
	`description` text,
	`enabled` INTEGER NOT NULL DEFAULT 1,
	`port` INTEGER NOT NULL DEFAULT 80)";
    $q->QUERY_SQL($sql);

    $service_reconfigure=$tpl->framework_buildjs("/reverse-proxy/firewall/build",
        "wordpress.firewall.progress",
        "wordpress.firewall.progress.txt","reconfigure-wordpress-firewall");


    $html[] = "<div id='reconfigure-wordpress-firewall' style='margin-top:10px;margin-bottom:10px'></div>";
    $bts[] = "<div class=\"btn-group\" data-toggle=\"buttons\" >";
    $bts[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?js=0');\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $bts[] = "<label class=\"btn btn btn-info\" OnClick=\"$service_reconfigure\"><i class='fa fa-save'></i> {apply_configuration} </label>";
    $bts[] = "</div>";
    $html[] = "<table id='table-$t-main' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{enabled}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{address}</th>";
    $html[] = "<th data-sortable=false></th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    $results=$q->QUERY_SQL("SELECT * FROM wp_firewall 
         WHERE (address LIKE '$search' OR description LIKE '$search')
        order by cast ( case
    when substr(address,-2) like \"._\" then substr(address,-1)
    when substr(address,-3) like \".__\" then substr(address,-2)
    else substr(address,-3) end
  as integer )");

    if(!$q->ok){
        echo $tpl->div_error("SQL ERROR||$q->mysql_error");
    }

    $TRCLASS=null;
    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $ID = $ligne["ID"];
        $port=intval($ligne["port"]);
        if($port==80){$port="80 {or} 443";}
        if($port==443){$port="443 {or} 80";}
        $address = $tpl->td_href($ligne["address"].":".$port,null,"Loadjs('$page?js=$ID')");
        $description = $ligne["description"];
        $md = md5(serialize($ligne));
        $enabled = intval($ligne['enabled']);


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'>". $tpl->icon_check($enabled,"Loadjs('$page?enable=$ID')",null,"AsWebSecurity")."</td>";
        $html[]="<td><strong id='wpfw1-$ID'>TCP:&nbsp;$address</strong>&nbsp;<small id='wpfw2-$ID'>$description</small></td>";
        $html[]="<td style='width:1%'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md=$md')","AsWebSecurity")."</td>";
        $html[]="</tr>";
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

    $TINY_ARRAY["TITLE"]="{firewall} {outgoing_wbl}";
    $TINY_ARRAY["ICO"]="fa-solid fa-block-brick-fire";
    $TINY_ARRAY["EXPL"]="{APP_NGINX_FW_EXPLAIN}";
    $TINY_ARRAY["URL"]="nginx-outfw";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t-main').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function GET_REMOTE_ADDR(){
    if(isset($_SERVER["REMOTE_ADDR"])){
        $IPADDR=$_SERVER["REMOTE_ADDR"];
        if($GLOBALS["VERBOSE"]){echo "REMOTE_ADDR = $IPADDR<br>\n";}
    }
    if(isset($_SERVER["HTTP_X_REAL_IP"])){
        $IPADDR=$_SERVER["HTTP_X_REAL_IP"];
        if($GLOBALS["VERBOSE"]){echo "HTTP_X_REAL_IP = $IPADDR<br>\n";}
    }
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
        $IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];
        if($GLOBALS["VERBOSE"]){echo "HTTP_X_FORWARDED_FOR = $IPADDR<br>\n";}
    }
    $GLOBALS["HTTP_USER_AGENT"]=$_SERVER["HTTP_USER_AGENT"];
    if($GLOBALS["VERBOSE"]){echo "HTTP_USER_AGENT = {$GLOBALS["HTTP_USER_AGENT"]}<br>\n";}

    if($GLOBALS["VERBOSE"]){
        while (list ($num, $Linz) = each ($_SERVER) ){
            if(is_array($Linz)){
                while (list ($a, $b) = each ($Linz) ){
                    echo "<li style='font-size:10px'>\$_SERVER[\"$num\"][\"$a\"]=\"$b\"</li>\n";
                }
                continue;
            }
            echo "<li style='font-size:10px'>\$_SERVER[\"$num\"]=\"$Linz\"</li>\n";
        }

    }


    return $IPADDR;
}