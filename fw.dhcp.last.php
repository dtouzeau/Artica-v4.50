<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_GET["switch-dns"])){switch_dns();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{last_computers}","fa fa-computer","{dhcp_leases_explain}","$page?search-form=yes","dhcp-last","progress-dhcplast-restart");


if(isset($_GET["main-page"])){
	$tpl=new template_admin("{APP_DHCP}",$html);
	echo $tpl->build_firewall();
	return;
}
	
	echo $tpl->_ENGINE_parse_body($html);

}
function search_form(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$token=null;
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");

    $function=$_GET["function"];


    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log",
            "progress-dhcplast-restart",
            "$function()"
        );

        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";

        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
	
	
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
    $t=time();

	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";


	
	$_SESSION["DHCPL_SEARCH"]=trim(strtolower($_GET["search"]));
    $search="*{$_GET["search"]}*";
    $search=str_replace("**","%",$search);
    $search=str_replace("*","%",$search);
    $search=str_replace("%%","%",$search);
    $qeury="WHERE (( TEXT(mac) LIKE '$search') OR (TEXT(ipaddr) LIKE '$search') OR (hostname LIKE '$search'))";

    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	$q=new postgres_sql();
	$sql="SELECT * FROM dhcpd_lasts $qeury ORDER BY zdate DESC LIMIT 500";
	$results=$q->QUERY_SQL($sql);

    $html[]="<th>&nbsp;</th>";
	$html[]="<th>{time}</th>";
	$html[]="<th nowrap>{since}</th>";
	$html[]="<th nowrap>$hostname</th>";
	$html[]="<th nowrap>$addr</th>";
	$html[]="<th nowrap>$ComputerMacAddress</th>";
    if($UnboundEnabled==1){
        $html[]="<th  nowrap>DNS</th>";
    }
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
    $CURDNS=json_decode(json_encode(array()));
    if($UnboundEnabled==1){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/dump/hosts/a"));
        if(property_exists($json,"Hosts")){
            $CURDNS = json_decode(json_encode($json->Hosts), true);

        }
    }
	if(!$q->ok){
		echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";
	}
	

	
	$TRCLASS=null;
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$ligne["hostname"]=trim($ligne["hostname"]);
		if($ligne["mac"]==null){continue;}
        if(isset($ligne["starts"])) {
            $tooltip = "<div style=font-size:11px>start {$ligne["starts"]} cltt:{$ligne["cltt"]} tstp:{$ligne["tstp"]}</div>";
        }

		$href=null;
		$CallBackFunction=base64_encode($_GET["function"]."()");
		if($ligne["hostname"]==null){$ligne["hostname"]="&nbsp;";}
		if($ligne["ipaddr"]==null){$ligne["ipaddr"]="&nbsp;";}
		if($ligne["mac"]==null){$ligne["mac"]="&nbsp;";}
		$MacAddress=$ligne["mac"];
        $IPStr=$ligne["ipaddr"];
        $DNS=0;
        $Hosts="";
        $Hostsjs="";
        $MacEncoded=urlencode($MacAddress);
        $IPStrEncoded=urlencode($IPStr);
        if(isset($CURDNS[$IPStr])){
            $hostsA = CleanDNSHosts($CURDNS[$IPStr]);
            if(count($hostsA)>0) {
                $DNS=1;
                $Hosts = "<br><i>" . @implode(", ", $hostsA) . "</i>";
                $Hostsjs = urlencode(@implode("|", $hostsA));
            }
        }
		
		$ligne2=@pg_fetch_array($q->QUERY_SQL("SELECT mac,fullhostname,proxyalias FROM hostsnet WHERE mac='$MacAddress'"));
		
		if($ligne2["mac"]<>null){
			$jshost="Loadjs('fw.edit.computer.php?mac=".urlencode($ligne["mac"])."&CallBackFunction=$CallBackFunction')";
			$href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
			$ligne["hostname"]=$ligne2["fullhostname"];
			if($ligne2["proxyalias"]<>null){$ligne["hostname"]=$ligne["hostname"]." ({$ligne2["proxyalias"]})";}
            $bton=$tpl->icon_loupe(true,$jshost);
        }else{
            $ffields[]="mac=$MacEncoded";
            $ffields[]="hostname=".urlencode($ligne["hostname"]);
            $ffields[]="ipaddr=$IPStrEncoded";
            $ffields[]="CallBackFunction=".$CallBackFunction;
            $jshost="Loadjs('fw.add.computer.php?".@implode("&",$ffields)."')";
            $bton=$tpl->icon_add($jshost);
        }

		$HostN=$ligne["hostname"];
        $HostNEncoded=urlencode($HostN);
		$ztime=strtotime($ligne["zdate"]);
		$zdate=$tpl->time_to_date($ztime,true);
		$distance=distanceOfTimeInWords($ztime,time());
		$clock=ico_clock_desk;
        $clock2=ico_clock;
        $nic=ico_nic;
        $comp=ico_computer;
		$html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$bton</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><i class='$clock'></i>&nbsp;$zdate</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap><i class='$clock2'></i>&nbsp;$distance</td>";
		$html[]="<td class=\"$text_class\" style='width:99%'><strong><i class='$comp'></i>&nbsp;$HostN</strong>$Hosts</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap><i class='$nic'></i>&nbsp;$IPStr</a></td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap><i class='$nic'></i>&nbsp;$href$MacAddress</a></td>";

        if($UnboundEnabled==1){
            $id = md5($IPStr . $Hostsjs);
            if($DNS==1) {
                $ico_dns = $tpl->icon_unlink("Loadjs('$page?switch-dns=$DNS&function=$function&IP=$IPStrEncoded&id=$id&hostname=$HostNEncoded');");
                $html[] = "<td class=\"$text_class\" style='width:1%' nowrap><span id='$id'>$ico_dns</span></td>";
            }else{
                $ico_dns = $tpl->icon_add("Loadjs('$page?switch-dns=$DNS&function=$function&IP=$IPStrEncoded&id=$id&hostname=$HostNEncoded');");
                $html[] = "<td class=\"$text_class\" style='width:1%' nowrap><span id='$id'>$ico_dns</span></td>";
            }
        }

		$html[]="</tr>";
		

	}
    $Column=6;
    if($UnboundEnabled==1){
        $Column=7;
    }
	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='$Column'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body($html);

}
function  switch_dns(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    //switch-dns=$DNS&function=$function&mac=$MacEncoded&hosts=$Hostsjs
    $IPStr=$_GET["IP"];
    $id=$_GET["id"];
    $hostname=$_GET["hostname"];
    $function=$_GET["function"];
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    if($UnboundEnabled==1){
        $HostArray=array();
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/dump/hosts/a"));
        if(!property_exists($json,"Hosts")) {
            return false;
        }
        $CURDNS = json_decode(json_encode($json->Hosts), true);
        $CountDeHost=0;
        if(isset($CURDNS[$IPStr])) {
            $HostArray = CleanDNSHosts($CURDNS[$IPStr]);
            $CountDeHost=count($HostArray);
        }

        writelogs("$IPStr: $CountDeHost hosts",__FUNCTION__,__FILE__, __LINE__);
        if($CountDeHost>0){
            foreach ($HostArray as $host){
                writelogs("$IPStr: remove [$host]",__FUNCTION__,__FILE__, __LINE__);
                if(strlen($host)>1){
                    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/remove/hosts/$host");
                    admin_tracks("Removing Host $host from DNS cache memory database");
                }
            }
            header("content-type: application/x-javascript");
            echo "$('#$id').remove();\n$function()\n";
            return true;
        }else{
            if(strlen($hostname)>1) {
                VERBOSE("/unbound/add/host/$hostname/$IPStr", __LINE__);
                $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/add/host/$hostname/$IPStr"));
                if (!$json->Status) {
                    return $tpl->js_error($json->Error);
                }
                admin_tracks("Adding Host $hostname/$IPStr from DNS cache memory database");
                header("content-type: application/x-javascript");
                echo "$('#$id').remove();\n$function()\n";
                return true;
            }
        }
    }

    return true;
}

function CleanDNSHosts($array):array{
        $HostArray=array();
        foreach ($array as $host){
            if(strlen($host)<2){
                continue;
            }
            $HostArray[]=$host;
        }
    return $HostArray;
}


