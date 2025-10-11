<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Clam AntiVirus userspace daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");

search();
function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/fw.search.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function search(){
	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
	
	$stringtofind=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FWTOPSEARCH"));
	
	$tpl=new template_admin();
	
	build_progress(10, "{search} $stringtofind");
	
	if(preg_match("#^\s+(.+)#", $stringtofind,$re)){$stringtofind=$re[1];}
	$prefix_search=null;
	$MACONLY=false;
	$users=new usersMenus();
	
	
	if(preg_match("#(computer|hostname|ordinateur|computador|ordenador|komputer)#", $stringtofind)){
		$f["QUEST".$stringtofind]["CONTENT"]=null;
		$f["QUEST".$stringtofind]["TYPE"]="QUEST_COMP";
		
	}
	
	if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>search [$stringtofind]</span><br>";}
	
	$OR[]="( fullhostname ~ '$stringtofind' )";
	$OR[]="( hostname ~ '$stringtofind' )";
	$OR[]="( hostalias1 ~ '$stringtofind' )";
	$OR[]="( hostalias2 ~ '$stringtofind' )";
	$OR[]="( hostalias3 ~ '$stringtofind' )";
	$OR[]="( hostalias4 ~ '$stringtofind' )";
	
	$ip=new IP();
	if($ip->IsvalidMAC($stringtofind)){
		$MACONLY=true;
		$OR=array();
		$prefix_search="&nbsp;".$tpl->javascript_parse_text("{ComputerMacAddress}");
		$OR[]="mac='$stringtofind'";
		
	}
	
	build_progress(10, "{search} hostsnet");
	$q=new postgres_sql();
	$sql="SELECT * FROM hostsnet WHERE ( ".@implode(" OR ", $OR).") LIMIT 50";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		$f["00:00:00:00:00:00"]["CONTENT"]=$q->mysql_error;
		$f["00:00:00:00:00:00"]["TYPE"]="COMPUTER";
	}
	while ($ligne = pg_fetch_assoc($results)) {
		$f[$ligne["mac"]]["CONTENT"]=serialize($ligne);
		$f[$ligne["mac"]]["TYPE"]="COMPUTER";
	}
	
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	build_progress(15, "{search} webfilters_sqitems");
	
	$sql="SELECT * FROM webfilters_sqitems WHERE pattern LIKE '%$stringtofind%' LIMIT 0,50";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error</div>";}
	foreach ($results as $index=>$ligne) {
		$f["PXY".$ligne["pattern"]]["CONTENT"]=serialize($ligne);
		$f["PXY".$ligne["pattern"]]["TYPE"]="PROXY_RULES";
	}
    build_progress(15, "{search} webfilters_sqgroups");
    $sql="SELECT * FROM webfilters_sqgroups WHERE GroupName LIKE '%$stringtofind%' ORDER BY GroupName";
    $results=$q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne) {
        $f["SQGROUPS".$ligne["GroupName"]]["CONTENT"]=serialize($ligne);
        $f["SQGROUPS".$ligne["GroupName"]]["TYPE"]="SQGROUPS";
    }

    $qf=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
    build_progress(15, "{search} firehol_services_def");
    $sql="SELECT * FROM firehol_services_def WHERE ( (service LIKE '%$stringtofind%') OR (server_port LIKE '%$stringtofind%')) ORDER BY service";
    $results=$qf->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne) {
        $f["000_firehol_services_def".$ligne["service"]]["CONTENT"]=serialize($ligne);
        $f["000_firehol_services_def".$ligne["service"]]["TYPE"]="FIREWALL_SERVICE";
    }






	
	
	build_progress(20, "{search} acls_whitelist");
	$sql="SELECT * FROM acls_whitelist WHERE (pattern LIKE '%$stringtofind%') OR (description LIKE '%$stringtofind%') ORDER BY pattern LIMIT 0,50";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error</div>";}
	foreach ($results as $index=>$ligne) {
		$f["PXYWHITE".$ligne["pattern"]]["CONTENT"]=serialize($ligne);
		$f["PXYWHITE".$ligne["pattern"]]["TYPE"]="PROXY_GWL";
	}
	
	
	build_progress(30, "{search} deny_cache_domains");
	$sql="SELECT items  FROM deny_cache_domains WHERE (items LIKE '%$stringtofind%') ORDER BY items";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	foreach ($results as $index=>$ligne) {
		$ligne["items"]=trim(strtolower($ligne["items"]));
		if($ligne["items"]==null){continue;}
		$f["PXYNOCACHE".$ligne["items"]]["CONTENT"]=serialize($ligne);
		$f["PXYNOCACHE".$ligne["items"]]["TYPE"]="PROXY_NOCACHE";
	}
	
	
	
	if(!$MACONLY){
		$EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
		if($EnablePDNS==1){
			$q=new mysql_pdns();
			build_progress(35, "{search} records");
			$sql="SELECT * FROM records WHERE ((name LIKE '%$stringtofind%') OR (content LIKE '%$stringtofind%')) LIMIT 0,50";
			$results = $q->QUERY_SQL($sql);
			if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error</div>";}
			foreach ($results as $index=>$ligne) {
				$f["DNS".$ligne["id"]]["CONTENT"]=serialize($ligne);
				$f["DNS".$ligne["id"]]["TYPE"]="DNS";
				
			}
		}
		
		$qProxy=new mysql_squid_builder(true);
		$www=$qProxy->WebsiteStrip($stringtofind);
		build_progress(35, "{search} {category}");
		$catz=$qProxy->GET_FULL_CATEGORIES($www);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>CATZ $www === $catz</span><br>\n";}
		if(trim($catz)<>null){
				if(strpos(" $catz", ",")==0){$CATEGORIES[]=$catz;}else{$CATEGORIES=explode(",",$catz);}
				$dans=new dansguardian_rules();
				$cats=$dans->LoadBlackListes();

                foreach ($CATEGORIES as $num=>$categoryF){
					if(isset($ALREADY_PARSED[$categoryF])){continue;}
					$ALREADY_PARSED[$categoryF]=true;
					$categoryF=trim($categoryF);
					if(!isset($cats[$categoryF])){$cats[$categoryF]=null;}
					if($cats[$categoryF]==null){
						$sql="SELECT category_description FROM personal_categories WHERE category='$categoryF'";
						$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
						$content=$ligne["category_description"];
							
					}else{
						$content=$cats[$categoryF];
						if(isset($dans->array_blacksites[$categoryF])){
							$content=$dans->array_blacksites[$categoryF];
						}
					}
					$pic="<img src='img/20-categories-personnal.png'>";
					if(isset($dans->array_pics[$categoryF])){$pic="<img src='img/{$dans->array_pics[$categoryF]}'>";}
					$content=$tpl->_ENGINE_parse_body($content);
					$ARRAY=array();
					$ARRAY["CONTENT"]=$categoryF;
					$ARRAY["PIC"]=$pic;
					$ARRAY["DESC"]=$content;
					
					$f["CATZ$stringtofind"]["CONTENT"]=serialize($ARRAY);
					$f["CATZ$stringtofind"]["TYPE"]="CATEGORY";
				}
		}
		
		

		
		
		
		$ldap=new clladp();
		$IsKerbAuth=$ldap->IsKerbAuth();
		
		if($IsKerbAuth==0){
			if($EnableOpenLDAP==1){
				build_progress(40, "{search} {members} LDAP");
				$hash_full=$ldap->UserSearch(null,$stringtofind,50);
			}
			$hash1=$hash_full[0];
			$hash2=$hash_full[1];
            if(!is_array($hash1)){
                $hash1=array();
            }
            if(!is_array($hash2)){
                $hash2=array();
            }
			$MAIN_HASH=array_merge($hash1, $hash2);
		}else{
			include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
			build_progress(40, "{search} {members} Active Directory");
			$ad=new external_ad_search(null,0);
			$hash_full=$ad->UserSearch(null,$stringtofind,50);
			$hash1=$hash_full[0];
			$hash2=$hash_full[1];
			$MAIN_HASH=array_merge($hash1, $hash2);		
			
		}
		foreach ($MAIN_HASH as $line){
			$ARRAY=array();
            foreach ($line as $key=>$ligne){
				$ARRAY[$key]=$ligne[0];
			}
			$f[$line["dn"]]["CONTENT"]=serialize($ARRAY);
			$f[$line["dn"]]["TYPE"]="MEMBER";
		}
	}

	$EnableSSHPortal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHPortal"));
	if($EnableSSHPortal==1){
        $q=new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
        $results=$q->QUERY_SQL("SELECT * FROM hosts WHERE ( (name LIKE '%$stringtofind%') OR (url LIKE '%$stringtofind%') OR (comment LIKE '%$stringtofind%'))");

        foreach ($results as $index=>$ligne){
            $f["001".$ligne["id"]]["CONTENT"]=serialize($ligne);
            $f["001".$ligne["id"]]["TYPE"]="SSHPORTABLE_HOST";
        }

        $results=$q->QUERY_SQL("SELECT * FROM users WHERE ( (name LIKE '%$stringtofind%') OR (email LIKE '%$stringtofind%') OR (comment LIKE '%$stringtofind%'))");

        foreach ($results as $index=>$ligne){
            $f["002".$ligne["id"]]["CONTENT"]=serialize($ligne);
            $f["002".$ligne["id"]]["TYPE"]="SSHPORTABLE_USER";
        }

        $results=$q->QUERY_SQL("SELECT * FROM user_groups WHERE ( (name LIKE '%$stringtofind%') OR (comment LIKE '%$stringtofind%'))");

        foreach ($results as $index=>$ligne){
            $f["003".$ligne["id"]]["CONTENT"]=serialize($ligne);
            $f["003".$ligne["id"]]["TYPE"]="SSHPORTABLE_GROUP";
        }

        $results=$q->QUERY_SQL("SELECT * FROM host_groups WHERE ( (name LIKE '%$stringtofind%') OR (comment LIKE '%$stringtofind%'))");

        foreach ($results as $index=>$ligne){
            $f["004".$ligne["id"]]["CONTENT"]=serialize($ligne);
            $f["004".$ligne["id"]]["TYPE"]="SSHPORTABLE_HGROUP";
        }
    }

	
	build_progress(40, "{search} {members} googleapis");
	$curl=new ccurl("https://wiki.articatech.com/graphql");
    $curl->add_header("Content-Type:application/json");
    $curl->parms["graphsql4searchwiki"]='{ "query": "{ pages {search(query: \"'.$stringtofind.'\" ){results{id,title,description,path}}}}"}';
	$curl->NoLocalProxy();
	$curl->get();
	$json=$curl->data;
	$array=json_decode($json);
	foreach ($array->data->pages->search->results as $items){
		$ligne=array();
		$ligne["explain"]=$items->description;
		$ligne["htmlSnippet"]=$items->title;
		$ligne["link"]='https://wiki.articatech.com/'.$items->path;
		$ligne["pagemap"]=str_replace("Home","",$items->path);
		//print_r($ligne);
		$f[md5($ligne["link"])]["CONTENT"]=serialize($ligne);
		$f[md5($ligne["link"])]["TYPE"]="WIKI";

		
	}
	

	ksort($f);
	$stringtofind=trim($stringtofind);
	$items=$tpl->_ENGINE_parse_body("{items}");
	$nocache=$tpl->_ENGINE_parse_body("{no_cache}");
	
	$html[]=$tpl->_ENGINE_parse_body("
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\" id='TABLEAU-TOP-RECHERCHE'><h1 class=ng-binding>{search}{$prefix_search} &laquo;$stringtofind&raquo;</h1></div>
	</div>
			<div class=row><div class='ibox-content'>	
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$items</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TYPE["MEMBER"]="fa fa-user";
	$TYPE["COMPUTER"]="fa fa-desktop";
    $TYPE["SSHPORTABLE_HOST"]="fas fa-server";
    $TYPE["SSHPORTABLE_USER"]="fa fa-user";
    $TYPE["SSHPORTABLE_GROUP"]="fa fa-users";
    $TYPE["SSHPORTABLE_HGROUP"]="fa fa-users";
    $TYPE["FIREWALL_SERVICE"]="fab fa-free-code-camp";



	$TYPE["DNS"]="fa fas fa-database";
	$TYPE["PROXY_RULES"]="fa fa-list-ul";
	$TYPE["PROXY_GWL"]="fa fa-thumbs-up";
	$TYPE["PROXY_NOCACHE"]="fa fa-thumbs-down";
	$TYPE["QUEST_COMP"]="fa fa-desktop";
	$TYPE["WIKI"]="fas fa-book";
	$TYPE["CATEGORY"]="fa-tag";
	
	$TRCLASS=null;
	
	$c=50;
	$whiteliste=$tpl->_ENGINE_parse_body("{whitelist}");
    foreach ($f as $key=>$ligne){
		$c++;
		if($c>98){$c=98;}
		
		$href=null;
		$subitem=null;
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class =null;
		$zTYPE      = $ligne["TYPE"];
        $item3      = null;
		build_progress($c, "{search} {build} $zTYPE");
		$CONTENT=unserialize($ligne["CONTENT"]);

        if($zTYPE=="FIREWALL_SERVICE"){
            $item=$CONTENT["service"];
            $item2=$CONTENT["server_port"];
            $item3="{firewall_services}";
            $jshost="Loadjs('fw.services.php?service-js={$item}')";
            $href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
        }

		if($zTYPE=="COMPUTER"){
			$item=$CONTENT["hostname"];
			$item2=$CONTENT["ipaddr"];
			$item3=$CONTENT["mac"];
			$jshost="Loadjs('fw.edit.computer.php?mac=".urlencode($item3)."&CallBackFunction={$_GET["CallBackFunction"]}')";
			$href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
		}
		if($zTYPE=="MEMBER"){
			$item=$CONTENT["sn"]." {$CONTENT["givenname"]}";
			$item2=$CONTENT["mail"];
			$item3=$CONTENT["uid"];
			if($item3==null){continue;}
		}
		if($zTYPE=="DNS"){
			$item=$CONTENT["name"].". {$CONTENT["domain"]}";
			$item2=$CONTENT["type"];
			$item3=$CONTENT["content"];
			$domain_id=$CONTENT["domain_id"];
			if($item3==null){continue;}
			$jshost="Loadjs('fw.dns.records.php?record-info-js=yes&domainid=$domain_id&type=$item2&id={$CONTENT["id"]}');";
			$href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
		}		
		if($zTYPE=="PROXY_RULES"){
			$q=new mysql_squid_builder();
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='{$CONTENT["gpid"]}'"));
			$item=$CONTENT["pattern"];
			$item2=$ligne["GroupName"];
			$item3="ACL:".$ligne["GroupType"];
			if($item3==null){continue;}
		}

		if($zTYPE=="SSHPORTABLE_HOST"){
            $item=$CONTENT["name"];
            $item2=$CONTENT["comment"];
            $item3="{APP_SSHPORTAL} {host}";
            $jshost="Loadjs('fw.sshportal.hosts.php?server-js={$CONTENT["id"]}');";
            $href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
        }
        if($zTYPE=="SSHPORTABLE_USER"){
            $item=$CONTENT["name"];
            $item2=$CONTENT["comment"];
            $item3="{APP_SSHPORTAL} {member}";
            $jshost="Loadjs('fw.sshportal.users.php?user-js={$CONTENT["id"]}');";
            $href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
        }
        if($zTYPE=="SSHPORTABLE_GROUP"){
            $item=$CONTENT["name"];
            $item2=$CONTENT["comment"];
            $item3="{APP_SSHPORTAL} {groups2}";
            $jshost="Loadjs('fw.sshportal.groups.php?group-js={$CONTENT["id"]}');";
            $href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
        }
        if($zTYPE=="SSHPORTABLE_HGROUP"){
            $item=$CONTENT["name"];
            $item2=$CONTENT["comment"];
            $item3="{APP_SSHPORTAL} {groups2} {hosts}";
            $jshost="Loadjs('fw.sshportal.hostsgroups.php?group-js={$CONTENT["id"]}');";
            $href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
        }


		
		if($zTYPE=="PROXY_NOCACHE"){
			$item=$CONTENT["items"];
			$item2=$nocache;
			$item3="Proxy";
			
		}		
		
		if($zTYPE=="PROXY_GWL"){
			$item=$CONTENT["pattern"];
			$item2=$ligne["description"];
			$item3="Proxy $whiteliste";
		}
		if($zTYPE=="QUEST_COMP"){
			$item=$tpl->_ENGINE_parse_body("{create_new_computer}");
			$item2=$tpl->_ENGINE_parse_body("{action}");
			$item3=$tpl->_ENGINE_parse_body("{computer}");
			$jshost="Loadjs('fw.add.computer.php');";
			$href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
		}
		if($zTYPE=="WIKI"){
			$item=$CONTENT["htmlSnippet"];
			$item2=$CONTENT["pagemap"];
			$subitem=$CONTENT["explain"];
			if($item2==null){$item2="Google...";}
            $item3="Artica v4.x";
			$jshost="s_PopUpFull('{$CONTENT["link"]}',1024,768,'Google')";
			$href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
		}
		
		if($zTYPE=="CATEGORY"){
			
			$subitem=$CONTENT["DESC"];
			$item2=$CONTENT["PIC"];
			$item3=$tpl->_ENGINE_parse_body("{category}");
			$item="$item3: &laquo;".$CONTENT["CONTENT"]."&raquo;";
			
		}
		
		if($subitem<>null){$subitem="<br><small class=text-muted>$subitem</small>";}
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" style='width:1%'><i class='{$TYPE[$zTYPE]}'></td>";
		$html[]="<td class=\"$text_class\">&nbsp;$href<strong>{$item}</strong></a>$subitem</td>";
		$html[]="<td class=\"$text_class\">$href$item2</a></td>";
		$html[]="<td class=\"$text_class\">$href$item3</a></td>";
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
	$html[]="</div></div>
	<script>
		NoSpinner();
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": {\"enabled\": true} } ); });
	</script>";	
	build_progress(100, "{search} {done}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FwSearchResults", @implode("\n", $html));
	
}