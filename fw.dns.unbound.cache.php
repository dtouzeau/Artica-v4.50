<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["zoom"])){zoom();exit;}

page();

function ifisright(){
	$users=new usersMenus();
	if($users->AsDnsAdministrator){return true;}
}

function delete_js(){
	$tpl=new template_admin();
	$hostname=urlencode($_GET["delete-js"]);
	$md=$_GET["md"];
	$sock=new sockets();
	$sock->getFrameWork("unbound.php?cache-clear=$hostname");
	header("content-type: application/x-javascript");
	echo "$('#$md').remove();";
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $addPLUS="";
	if(!isset($_SESSION["DNSCACHE_SEARCH"])){$_SESSION["DNSCACHE_SEARCH"]="50 events";}
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}


    $TINY_ARRAY["TITLE"] ="DNS {cache}";
    $TINY_ARRAY["ICO"] = "fa fas fa-database";
    $TINY_ARRAY["EXPL"] = "{cached_items}";
    $TINY_ARRAY["BUTTONS"] = null;
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

	
	$html="
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["DNSCACHE_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row' id='spinner'>
		<div id='progress-$t-restart'></div>
		<div  class='ibox-content'>
		<div class='sk-spinner sk-spinner-wave'>
			<div class='sk-rect1'></div>
			<div class='sk-rect2'></div>
			<div class='sk-rect3'></div>
			<div class='sk-rect4'></div>
			<div class='sk-rect5'></div>
		</div>
		
		
			<div id='table-$t'></div>
		</div>
	</div>
	</div>
	<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-$t','$page?search='+ss+'$addPLUS&function=Search$t');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
        $jstiny;
		Start$t();
	</script>";

	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$page=CurrentPageName();
	include_once('ressources/class.ufdbguard-tools.inc');
	$tpl=new template_admin();
	$GLOBALS["TPLZ"]=$tpl;
	$max=0;$date=null;$c=0;

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/cache/dump"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }





	
	$html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
    	    <th>{status}</th>
        	<th>{domain}</th>
        	<th>{hostname}</th>
			<th>{type}</th>
            <th>TTL</th>
            <th>{content}</th>
          	<th>{delete}</th>
        </tr>
  	</thead>
	<tbody>
";

    $results_color["NXDOMAIN"]="#f8ac59";
    $results_color["REFUSED"]="rgb(237, 85, 101)";
    $results_color["SERVFAIL"]="#f8ac59";
    $results_color["FORMERR"]="#f8ac59";
    $results_color["YXDOMAIN"]="#f8ac59";
    $results_color["XRRSET"]="#f8ac59";
    $results_color["NOERROR"]="#000000";


    $tooltip["NXDOMAIN"]="<span class='label label-warning'>NXDOMAIN</span>";
    $tooltip["REFUSED"]="<span class='label label-danger'>REFUSED</span>";
    $tooltip["SERVFAIL"]="<span class='label label-danger'>SERVFAIL</span>";
    $tooltip["NOERROR"]="<span class='label label-primary'>NOERROR</span>";
    $tooltip["FORMERR"]="<span class='label label-warning'>FORMERR</span>";
    $tooltip["YXDOMAIN"]="<span class='label label-warning'>YXDOMAIN</span>";
    $tooltip["XRRSET"]="<span class='label label-warning'>XRRSET</span>";

	$zcat=new squid_familysite();
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    //var_dump($json->Cache->MsgCache);
	
	$c=1;
	foreach ($json->Cache->RRSetCache as $d) {

        foreach ($d->RRs as $RRSet) {
            $hostname = $RRSet->Name;
            $TTL = $RRSet->TTL;
            $Class = $RRSet->Class;
            $Type = $RRSet->Type;
            $RData = $RRSet->RData;

            $HASH[$hostname][$Class][$Type]["TTL"] = $TTL;
            $HASH[$hostname][$Class][$Type]["RRDATA"] = $RData;
        }
    }
    $c=0;
    foreach ($json->Cache->MsgCache as $d) {
        if(strlen($d->QName)<2){
            continue;
        }
        if(strlen($search)<2) {
            $c++;
            if ($c > $rp) {
                break;
            }
        }
        $QName=$d->QName;
        $QClass=$d->QClass;
        $QType=$d->QType;
        $Flags=$d->Flags->RCODEName;
        $TTL=0;
        $RData="";
        if(isset($HASH[$QName][$QClass][$QType])){
            $TTL=$HASH[$QName][$QClass][$QType]["TTL"];
            $RData=$HASH[$QName][$QClass][$QType]["RRDATA"];
        }
        if(strlen($search)>2) {
            if(!preg_match("#$search#","$QName $RData")){
                continue;
            }
            $c++;
            if ($c > $rp) {
                break;
            }
        }


     //   echo "$QName $QClass $QType $Flags $TTL $RData\n";
        $hostname=$QName;
        $TYPE=$QType;
        if (preg_match("#(.+?)\.$#", $hostname, $re)) {
                $hostname = $re[1];
            }
            $ttl = intval($TTL);
            $data = $RData;
            $color=$results_color[$Flags];
            $familysite = $zcat->GetFamilySites($hostname);
            $md = md5(json_encode($d));
            $next = time() + $ttl;
            $distance = distanceOfTimeInWords(time(), $next, true);
            $hostnameenc = urlencode($hostname);
            $len=strlen($hostname);
            $DataLen=strlen($data);
            $tip=$tooltip[$Flags];
            if($len>50){
                $hostname=substr($hostname,0,47)."...";
            }
            if($DataLen>60){
                $data=substr($data,0,57)."...";
            }
            $html[] = "<tr id='$md'>
                <td style='width:1%;color:$color' nowrap>$tip</span></td>
				<td style='width:1%;color:$color' nowrap>$familysite</span></td>
				<td style='color:$color'>$hostname</span></td>
                <td style='width:1%;color:$color' nowrap>$TYPE</span></td>
                <td style='width:1%;color:$color' nowrap>{$ttl}s (<small>$distance</small>)</span></td>               
                <td style='color:$color'>$data</span></td>
                <td style='width:1%' class='center' nowrap>" . $tpl->icon_delete("Loadjs('$page?delete-js=$hostnameenc&md=$md')", "AsDnsAdministrator") . "</center></td>

                </tr>";

        }





	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</tbody></table>";
	$html[]="<div>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/dnsfilterd.log.cmd")."</div>
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
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
