<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
//$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["ruleid-tabs"])){ruleid_tabs();exit;}
if(isset($_GET["ruleid-js"])){ruleid_js();exit;}
if(isset($_GET["ruleid-popup"])){ruleid_popup();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-whitelist"])){enable_whitelist();exit;}

page();

function ruleid_js():bool{
	$page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/modsecurity_rules.db");
	$tpl=new template_admin();
	$ruleid=intval($_GET["ruleid-js"]);
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rules WHERE ID=$ruleid");
    $rulename=$ligne["rulename"];
    if(strlen($rulename)>100){$rulename=substr($rulename,0,97)."...";}
	$tpl->js_dialog1("$rulename","$page?ruleid-popup=$ruleid",995);
    return true;
}
function ruleid_popup(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/modsecurity_rules.db");
    $tpl=new template_admin();
    $ruleid=intval($_GET["ruleid-popup"]);
    $ligne=$q->mysqli_fetch_array("SELECT rulename,content FROM rules WHERE ID=$ruleid");
    $rulename=$ligne["rulename"];
    $content=$ligne["content"];
    $content=htmlspecialchars($content);
    $content=str_replace(",",",<br>",$content);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist?ruleid=$ruleid&serviceid=0"),true);
    $isWhitelisted=false;
    if(is_array($json) && $json["Status"]){
        foreach($json["entries"] as $e){if(strlen($e["spath"])==0){$isWhitelisted=true;break;}}
    }
    if(!$isWhitelisted){
        $type="- {active2}";
        $panel="panel-primary";}
    else{
        $type=" - {inactive}";
        $panel="panel-default";
    }

    $html="<div class=\"panel $panel\">
                                        <div class=\"panel-heading\">
                                           $rulename <strong>$type</strong>
                                        </div>
                                        <div class=\"panel-body\">
                                           $content
                                        </div>

                                    </div>";
    echo $tpl->_ENGINE_parse_body($html);


}

function enable_whitelist(){
    $id=intval($_GET["enable-whitelist"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist?ruleid=$id&serviceid=0"),true);
    $isWhitelisted=false;
    if(is_array($json) && $json["Status"]){
        foreach($json["entries"] as $e){if(strlen($e["spath"])==0){$isWhitelisted=true;break;}}
    }
    if($isWhitelisted){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/modsec/whitelist", ["action"=>"delete","ruleid"=>$id,"serviceid"=>0]);
        admin_tracks("Remove WAF rule $id from global whitelist");
    }else{
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/modsec/whitelist", ["action"=>"add","ruleid"=>$id,"serviceid"=>0]);
        admin_tracks("Add WAF rule $id To global whitelist");
    }

    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $t=time();

    $html[]="<div class=\"row\" style='margin-top: 10px'>";
    $html[]="<div class='ibox-content'>";
			$html[]="<div class=\"input-group\" style='margin-top:10px'>
	      		<input type=\"text\" class=\"form-control\" value=\"\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
	      		<span class=\"input-group-btn\"><button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button></span>
	     	</div>
    	</div>
	</div>
	<div class='row'><div id='progress-modesec-def'></div>
	<div class='ibox-content'>";


    $html[]="<div id='table-ModSecdefRules'></div>

	</div>
	</div>
	<script>

		function Search$t(e){";
$html[]="    
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-ModSecdefRules','$page?search='+ss+'&function=ss$t');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		
		function modsecurity_js_rules_search(){
		    ss$t();
		}
		
		Start$t();
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

    $MAIN_WHITE=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist?serviceid=0"),true);
    if(is_array($json) && $json["Status"]){
        foreach($json["entries"] as $e){
            if(strlen($e["spath"])==0){$MAIN_WHITE[$e["wfrule"]]=true;}
        }
    }
    $TRCLASS=null;

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{disabled}</th>";
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
        $sql="SELECT * FROM rules WHERE ( (rulename LIKE '$search') OR (ID LIKE '$search') ) ORDER BY rulename";
    }else{
        $sql="SELECT * FROM rules ORDER BY ID DESC LIMIT 250";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/modsecurity_rules.db");
	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error("<b>{please_click_synchronize}</b><p>$q->mysql_error</p>");}
	$TRCLASS=null;
	foreach ($results as $ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $id=$ligne["ID"];
        $stype="strong";
        $whitelisted=0;
        if(isset($MAIN_WHITE[$id])){
            $whitelisted=1;
            $stype="span";
        }

        $ligne["rulename"]=str_replace("'","`",$ligne["rulename"]);
        if(strlen($ligne["rulename"])>100){
            $ligne["rulename"]=substr($ligne["rulename"],0,97)."...";
        }
        $ligne["rulename"]=$tpl->td_href("{$ligne["rulename"]}",null,"Loadjs('$page?ruleid-js=$id')");
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td style='width:1%' nowrap><$stype>{$id}</$stype></td>";
        $html[]="<td style='vertical-align:middle;width:99%' nowrap><$stype>{$ligne["rulename"]}</$stype></td>";
		$html[]="<td style='vertical-align:middle;width:1%' nowrap><center>".$tpl->icon_check($whitelisted,"Loadjs('$page?enable-whitelist=$id')","signature-$id")."</center></td>";
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
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	";


    $button=@implode("",array());

    $TINY_ARRAY["TITLE"]="{WAF_LONG} {global_rules}";
    $TINY_ARRAY["ICO"]="fa fa-bars";
    $TINY_ARRAY["EXPL"]="{ModSecurityGLobalRulesExplain}";
    $TINY_ARRAY["BUTTONS"]=$button;
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
	$q->QUERY_SQL("UPDATE suricata_rules_packages SET `enabled`='$enabled' WHERE rulefile='$filename'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}