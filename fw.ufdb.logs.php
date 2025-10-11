<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom"])){zoom();exit;}
if(isset($_GET["opts"])){search_opts_js();exit;}
if(isset($_POST["remote_addr"])){search_opts_save();exit;}
if(isset($_GET["search-opts-popup"])){search_opts_popup();exit;}
if(isset($_GET["search-opts-reset"])){search_opts_reset();exit;}
page();

function zoom_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$data=urlencode($_GET["data"]);
	$title=$tpl->_ENGINE_parse_body("{realtime_requests}::ZOOM");
	$tpl->js_dialog($title, "$page?zoom=yes&data=$data");
}
function zoom(){

	$data=unserialize(base64_decode($_GET["data"]));
$html[]="<div class=ibox-content>";
$html[]="<table class='table table table-bordered'>";

    foreach ($data as $key=>$val){
		$html[]="<tr>
		<td class=text-capitalize>$key:</td>
		<td><strong>$val</strong></td>
		</tr>";

	}

	echo @implode("", $html)."</table></div>";

}

function ifisright(){
	$users=new usersMenus();
	if($users->AsProxyMonitor){return true;}
	if($users->AsWebStatisticsAdministrator){return true;}

}
function search_opts_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog4("{options}","$page?search-opts-popup=yes&function=$function");
}
function search_opts_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    if(!isset($_SESSION["UFDBGSEARCH"]["remote_addr"])){
        $_SESSION["UFDBGSEARCH"]["remote_addr"]="";
    }
    if(!isset($_SESSION["UFDBGSEARCH"]["category"])){
        $_SESSION["UFDBGSEARCH"]["category"]="";
    }
    $form[]=$tpl->field_ipaddr("remote_addr","{src}",$_SESSION["UFDBGSEARCH"]["remote_addr"]);

    $form[]=$tpl->field_array_hash_categories("category","{category}", $_SESSION["UFDBGSEARCH"]["category"]);

    $js="dialogInstance4.close();$function()";
    $tpl->form_add_button("{reset}","Loadjs('$page?search-opts-reset=yes&function=$function')");
    echo $tpl->form_outside("{search}",$form,null,"{save}",$js);
    return true;
}
function search_opts_reset():bool{
    $function=$_GET["function"];
    unset($_SESSION["UFDBGSEARCH"]);
    unset($_SESSION["field_array_hash_categories"]);
    header("content-type: application/x-javascript");
    echo "dialogInstance4.close();$function()";
    return true;
}
function search_opts_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["UFDBGSEARCH"]=$_POST;
    return true;
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["WEBF_SEARCH"])){$_SESSION["WEBF_SEARCH"]="";}
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
    $options["WRENCH"]="Loadjs('$page?opts=yes&function=%s')";
    $search_block=$tpl->search_block($page,null,null,null,"",$options);

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{WEB_FILTERING}: {events} </h1></div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
            $search_block
        </div>
    </div>
	";

	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	include_once('ressources/class.ufdbguard-tools.inc');
	$sock=new sockets();
	$tpl=new template_admin();
	$GLOBALS["TPLZ"]=$tpl;
	$c=0;
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	$MAIN=$tpl->format_search_protocol($_GET["search"]);

    if(strlen($MAIN["TERM"])==0){
        $MAIN["TERM"]="NONE";
    }else{
        $MAIN["TERM"]="*{$MAIN["TERM"]}";
    }

    if(!isset($_SESSION["UFDBGSEARCH"]["remote_addr"])){
        $_SESSION["UFDBGSEARCH"]["remote_addr"]="";
    }
    if(!isset($_SESSION["UFDBGSEARCH"]["category"])){
        $_SESSION["UFDBGSEARCH"]["category"]=0;
    }
    if(strlen($_SESSION["UFDBGSEARCH"]["remote_addr"])>3){
        if($MAIN["TERM"]=="NONE"){$MAIN["TERM"]="";}
        $MAIN["TERM"]="*{$_SESSION["UFDBGSEARCH"]["remote_addr"]}*{$MAIN["TERM"]}";
    }
    if(intval($_SESSION["UFDBGSEARCH"]["category"])>0){
        if($MAIN["TERM"]=="NONE"){$MAIN["TERM"]="";}
        $MAIN["TERM"]="{$MAIN["TERM"]}*P{$_SESSION["UFDBGSEARCH"]["category"]}";
    }
    $MAIN["TERM"]=str_replace("**","*",$MAIN["TERM"]);
    $MAIN["TERM"]=str_replace("**","*",$MAIN["TERM"]);

	//$sock->getFrameWork("squid.php?ufdb-real=yes&rp={$MAIN["MAX"]}&query=".base64_encode($MAIN["TERM"]));
    $terms=base64_encode($MAIN["TERM"]);
    $EndPoint="/ufdb/real/{$MAIN["MAX"]}/$terms";
    $data=$sock->REST_API($EndPoint);

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $q=new postgres_sql();
	$ipaddr=$tpl->javascript_parse_text("{members}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$proto=$tpl->javascript_parse_text("{proto}");
	$today=date("Y-m-d");


	
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$zdate</th>
        	<th>$ipaddr</th>
			<th>$rulename</th>
            <th>&nbsp;</th>
            <th>$proto</th>
            <th>$hostname</th>
            <th>$uri</th>
        </tr>
  	</thead>
	<tbody>
";
	

	$zcat=new squid_familysite();
    $tcp=new IP();


    foreach ($json->Logs as $line){
		$TR=preg_split("/[\s]+/", $line);
		
		if(count($TR)<5){continue;}
		
		$c++;
        $RULE="";
        $CLIENT_IP="";
		$color="black";
		$date=$TR[0];
		$TIME=$TR[1];
		$PID=$TR[2];
		$ALLOW=$TR[3];
		$CLIENT=$TR[4];

        if(isset($TR[5])) {
            $CLIENT_IP=$TR[5];
        }

        if(isset($TR[6])) {
            $RULE = $TR[6];
        }
        if(!isset($TR[7])){$TR[7]=0;}
        if(!isset($TR[8])){$TR[8]="NONE";}
        if(!isset($TR[9])){$TR[9]="NONE";}
		$CATEGORY=categoryCodeTocatz($TR[7]);
		$URI=$TR[8];
		$PROTO=$TR[9];

		$parse=parse_url($URI);
        if(!isset($parse["host"])){continue;}
        $hostname=$parse["host"];
		if($CLIENT==null){$CLIENT="-";}

		if($ALLOW=="BLOCK-LD"){$color="#DE8011";}
		if($ALLOW=="BLOCK"){$color="#D0080A";}
		if($ALLOW=="REDIR"){$color="#BAB700";}
		if($ALLOW=="PASS"){$color="#009223";}
		
        if($CLIENT==$CLIENT_IP){$CLIENT_IP=null;}else{$CLIENT_IP="/$CLIENT_IP";}

		if(preg_match("#([0-9]+)\.addr#", $hostname,$re)){
			$ton=$re[1];
			$ipaddr=long2ip($ton);
			$hostname=str_replace("$ton.addr", $ipaddr , $hostname);
			$URI=str_replace("$ton.addr", $ipaddr, $URI);
        }

		if(preg_match("#^http(s|):\/\/(.+)#",$URI,$re)){
		    $URI=$re[2];
        }

		if($date==$today){$date=null;}


        if($tcp->isValid($CLIENT_IP)){

            $sql="SELECT mac,proxyalias FROM hostsnet WHERE ipaddr='$CLIENT_IP'";
            $ligne = $q->mysqli_fetch_array($sql);
            if($tcp->IsvalidMAC($ligne["mac"])){
                $js="Loadjs('fw.edit.computer.php?mac=".urlencode($ligne["mac"])."&CallBackFunction=ss1560867994');";
                $CLIENT_IP=$tpl->td_href("$CLIENT_IP/{$ligne["mac"]}","{edit}",$js);
            }
        }





        $URI=$tpl->td_href($URI,"{actions}","Loadjs('fw.proxy.relatime.actions.php?dom=".urlencode($hostname)."')");
        $hostname=$tpl->td_href($hostname,"{actions}","Loadjs('fw.proxy.relatime.actions.php?dom=".urlencode($hostname)."')");
		
		
		$html[]="<tr>
				<td><span style='color:$color'>$date $TIME</span></td>
				<td><span style='color:$color'>$CLIENT$CLIENT_IP</span></td>
				<td <span style='color:$color'>$RULE/$CATEGORY</span></td>
                <td><span style='color:$color'>{$ALLOW}</span></td>  
                <td><span style='color:$color'>{$PROTO}</span></td>                  
                <td><span style='color:$color'>{$hostname}</span></td>
                <td><span style='color:$color'>$URI</span></td>

                </tr>";
		
	}
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</tbody></table>";
	$html[]="<div></div>
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";
	echo @implode("\n", $html);
	
	
	
}

function categoryCodeTocatz($category){
	if(preg_match("#P([0-9]+)#", $category,$re)){$category=$re[1];}
	if($category==0){return "($category) Unknown(0)";}

	$catz=new mysql_catz(true);
	$categories_descriptions=$catz->categories_descriptions();
	if(!isset($categories_descriptions[$category]["categoryname"])){
		return "($category) <strong>Unkown</strong>";
	}

	$name=$categories_descriptions[$category]["categoryname"];
	$category_description=$categories_descriptions[$category]["category_description"];
	$js="Loadjs('fw.ufdb.categories.php?category-js=$category')";
	return $GLOBALS["TPLZ"]->td_href($name,$category_description,$js);
}
