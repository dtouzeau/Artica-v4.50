<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["tmpwhite"])){tmpwhite();exit();}
if(isset($_GET["permwhite"])){permwhite();exit();}
if(isset($_GET["white-js"])){white_js();exit;}
if(isset($_GET["white-popup"])){white_popup();exit;}
if(isset($_POST["whiteperms"])){white_save();exit;}
page();

function tmpwhite():bool{
    $sitename   = $_GET["tmpwhite"];
    $mem        = new lib_memcached();
    $key        = "WHITEDOM:$sitename";

    $keymem     = intval($mem->getKey($key));
    if($keymem==1){
        $mem->Delkey($key);
        admin_tracks("$sitename website removed from SRN temporary whitelist (1800 seconds)");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?whitelist=yes");
        return true;
    }
    $mem->saveKey($key,1,1800);
    admin_tracks("$sitename website added from SRN temporary whitelist (1800 seconds)");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?whitelist=yes");
    return true;
}
function permwhite():bool{
    $sitename   = $_GET["permwhite"];
    $tpl        = new template_admin();
    $whitelistP = 0;
    $q          = new postgres_sql();
    $sql        = "SELECT zdate FROM ksrn_white WHERE sitename='$sitename'";

    if(!$q->CREATE_KSRN()){
        $tpl->js_error($q->mysql_error);
        return false;
    }

    $lign2=$q->mysqli_fetch_array($sql);
    if(intval($lign2["zdate"])>0){$whitelistP=1;}

    if($whitelistP==1){
        $q->QUERY_SQL("DELETE FROM ksrn_white WHERE sitename='$sitename'");
        if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}
        admin_tracks("$sitename website removed from SRN permanent whitelist");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?whitelist=yes");
        return true;

    }
    $time=time();
    $q->QUERY_SQL("INSERT INTO ksrn_white (zdate,sitename) VALUES ('$time','$sitename')");
    if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}
    admin_tracks("$sitename website added from SRN permanent whitelist");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?whitelist=yes");
    return true;
}

function white_save():bool{
    $tpl        = new template_admin();
    $ip         = new IP();
    $q          = new postgres_sql();
    $tpl->CLEAN_POST();
    $q->QUERY_SQL("TRUNCATE TABLE ksrn_white");
    $tbl=explode("\n",$_POST["whiteperms"]);
    $time=time()-count($tbl)+1;
    $c=0;

    foreach ($tbl as $sitename){
        $c++;
        $time++;
        $sitename=trim(strtolower($sitename));
        if($ip->isValid($sitename)){continue;}
        $q->QUERY_SQL("INSERT INTO ksrn_white (zdate,sitename) VALUES ('$time','$sitename')");
        if(!$q->ok){echo "jserror:$q->mysql_error";return false;}
    }

    admin_tracks("$c websites saved in SRN permanent whitelist");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ksrn.php?whitelist=yes");
    return true;

}

function white_js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog1("{whitelist} {permanent}","$page?white-popup=yes&function={$_GET["function"]}");

}
function white_popup(){
    $tpl        = new template_admin();
    $q          = new postgres_sql();
    $function   = $_GET["function"];

    $sql="SELECT * FROM ksrn_white";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $sitename=strtolower($ligne["sitename"]);
        if(preg_match("#^www\.(.+)#",$sitename,$re)){$sitename=$re[1];}
        if(isset($ALREADY[$sitename])){continue;}
        $ALREADY[$sitename]=true;
        $sites[]=$sitename;

    }

    $form[]=$tpl->field_textareacode("whiteperms",null,@implode("\n",$sites));
    $html=$tpl->form_outside("{websites}", $form,null,"{apply}","$function()","AsProxyMonitor");
	echo $html;
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["KSRN_SEARCH"])){$_SESSION["KSRN_SEARCH"]="50 events";}
	
	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{KSRN}: {DETECTED_THREATS}</h1><p>{DETECTED_THREATS_10MN}</div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["KSRN_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row'>
	    <div class='ibox-content'>
    	    <div id='table-loader'></div>
	    </div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/ksrn-events');
		function Search$t(e){";
$html[]="\tif(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss+'&function=ss$t');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,@implode("\n",$html));echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}

function sync_js():string{
    $function=$_GET["function"];
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ksrn-stats.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/ksrn-stats.log";
    $ARRAY["CMD"]="ksrn.php?sync-stats=yes";
    $ARRAY["TITLE"]="{KSRN} {synchronize}";
    $ARRAY["AFTER"]="$function();";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-ksrnthreats-restart')";
}

function search(){
    $catz       = new mysql_catz();
	$tpl        = new template_admin();
	$search     = $_GET["search"];
    $q          = new postgres_sql();
    $LIMIT      = 250;
    $mem        = new lib_memcached();
    $page       = CurrentPageName();
    $fam        = new squid_familysite();

    $q->CREATE_KSRN();

    if(preg_match("#([0-9]+) (events|rows|lignes|limit)#",$search,$re)){
        $LIMIT=$re[1];
        $search=trim(str_replace("$LIMIT {$re[2]}","",$search));
    }
    if(preg_match("#(events|rows|lignes|limit) ([0-9]+)#",$search,$re)){
        $LIMIT=$re[2];
        $search=trim(str_replace("{$re[1]} $LIMIT","",$search));
    }

    $sql        = "SELECT ksrn.zdate, ksrn.username, ksrn.ipaddr, ksrn.mac, 
                 ksrn.category,ksrn.provider,statscom_websites.sitename
                 FROM  ksrn,statscom_websites
                 WHERE ksrn.siteid=statscom_websites.siteid
                 ORDER BY zdate DESC
                 LIMIT $LIMIT";
                 
                 
                        
        

    if($search<>null) {
        $search = "*$search*";
        $search = str_replace("**", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*","%",$search);
        $sql = "SELECT ksrn.zdate, ksrn.username, ksrn.ipaddr, ksrn.mac, 
                 ksrn.category,ksrn.provider,statscom_websites.sitename
                 FROM  ksrn,statscom_websites
                 WHERE (ksrn.siteid=statscom_websites.siteid) 
                 AND (statscom_websites.sitename LIKE '$search' OR ksrn.username LIKE '$search')
                 ORDER BY zdate DESC
                 LIMIT $LIMIT";
    }

    $q->CREATE_KSRN();
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $html[]="<div id='progress-ksrnthreats-restart'></div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom: 10px'>";
    $sync_js=sync_js();
    $whitejs="Loadjs('$page?white-js=yes&function={$_GET["function"]}');";


    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$sync_js\"><i class='fa fa-recycle'></i> {synchronize} </label>";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"$whitejs\"><i class='fa fa-wrench'></i> {whitelist} <small>({permanent})</small> </label>";
    $html[]="</div>";


	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{date}</th>
        	<th nowrap>{user}</th>
        	<th nowrap>{sitename}</th>
        	<th nowrap>{category}</th>
        	<th nowrap>{provider}</th>
        	<th nowrap>{whitelist}</th>
        </tr>
  	</thead>
	<tbody>
";
    $c=0;
    while ($ligne = pg_fetch_assoc($results)) {

        foreach ($ligne as $key=>$value){
            if(trim($value)=="-"){
                $ligne[$key]=null;
            }
        }


		$zdate      = $tpl->time_to_date( strtotime($ligne["zdate"]) ,true);
		$username   = $ligne["username"];
        $ipaddr     = $ligne["ipaddr"];
		$mac        = $ligne["mac"];
        $sitename   = $ligne["sitename"];
        $provider   = $ligne["provider"];
        $keymem     = intval($mem->getKey("WHITEDOM:$sitename"));
        $usr        = array();
        $color      = null;
        $whitelistP = 0;
        VERBOSE("Category: {$ligne["category"]} - provider = $provider",__LINE__);
        $category   = $catz->CategoryIntToStr($ligne["category"]);
        if($mac=="00:00:00:00:00:00"){$mac=null;}

        $whitelist=$tpl->icon_check($keymem,"Loadjs('$page?tmpwhite=".urlencode($sitename)."')");

        $sql="SELECT zdate FROM ksrn_white WHERE sitename='$sitename'";

        $permw=$sitename;
        $lign2=$q->mysqli_fetch_array($sql);
        if(intval($lign2["zdate"])>0){$whitelistP=1;}
        if($whitelistP==0){
            $family=$fam->GetFamilySites($sitename);
            if($family<>$sitename){
                $lign2=$q->mysqli_fetch_array("SELECT zdate FROM ksrn_white WHERE sitename='$family'");
                if(intval($lign2["zdate"])>0){$whitelistP=1;}
                $permw=$family;
            }
        }

        $c++;
        $whitelist_per=$tpl->icon_check($whitelistP,"Loadjs('$page?permwhite=".urlencode($permw)."&c=$c')");



		if($username<>null){$usr[]=$username;}
        if($ipaddr<>null){$usr[]=$ipaddr;}
        if($mac<>null){$usr[]=$mac;}
		$html[]="<tr>
				<td width=1% class='$color' nowrap>$zdate</td>
				<td width=1% class='$color' nowrap >".@implode(", ",$usr)."</td>
				<td width=99% class='$color font-bold' nowrap >$sitename</td>
				<td width=1% class='$color' nowrap ><span class='$color'>$category</span></td>
				<td width=1% class='$color' nowrap ><span class='$color'>$provider</span></td>
				<td width=1% class='$color' nowrap >$whitelist_per</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<script>";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

