<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.ipinfo.inc");
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsWebSecurity){exit();}


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["white-js"])){white_js();exit;}

page();

function white_js(){
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $address=$_GET["white-js"];
    $port=intval($_GET["port"]);
    $bt=htmlentities(base64_decode($_GET["bt"]));
    $tpl=new template_admin();
    $_GET["bt"]=str_replace("&nbsp;"," ",$_GET["bt"]);

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM wp_firewall WHERE address='$address' AND port='$port'");
    if(intval($ligne["ID"])>0){
        echo $tpl->js_error("$address:$port {alreadyexists}");
        return false;
    }

    $description="Added on ". date("Y-m-d H:i:s"). " $bt";
    $sql="INSERT INTO wp_firewall (address,port,description) VALUES('$address','$port','$description')";
     $q->QUERY_SQL("$sql");
        if(!$q->ok){
            writelogs($q->mysql_error." $sql",__FUNCTION__,__FILE__,__LINE__);
            echo $tpl->js_error($q->mysql_error);
            return;
        }
    admin_tracks("Add a new whitelisted destination for Web Firewall $address:$port");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reverse-proxy/firewall/ipsets");
    echo $tpl->js_display_results("{success}");
    return true;
}
function page(){
	$tpl=new template_admin();

    $html=$tpl->page_header(
        "{firewall_events} {deny} ({threats})",
        ico_eye,
        "{APP_NGINX_FW_EXPLAIN}",
        "","nginx-status",null,true


    );

	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	$page=CurrentPageName();
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("wordpress.php?syslog=$line");
	$filename=PROGRESS_DIR."/firehol-wordpress.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$src=$tpl->_ENGINE_parse_body("{src}");
	$srcport=$tpl->_ENGINE_parse_body("{source_port}");
	$dst=$tpl->_ENGINE_parse_body("{dst}");
	$dstport=$tpl->_ENGINE_parse_body("{destination_port}");
	$incoming=$tpl->_ENGINE_parse_body("{incoming2}");

    $EnableNginxFW=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"));

    if($EnableNginxFW==0){
        $TINY_ARRAY["TITLE"]="{firewall_events} ({outgoing})";
        $TINY_ARRAY["ICO"]=ico_eye;
        $TINY_ARRAY["EXPL"]="{APP_NGINX_FW_EXPLAIN}";
        $TINY_ARRAY["URL"]="nginx-status";
        $TINY_ARRAY["BUTTONS"]=null;
        $TINY_ARRAY["DANGER"]=true;
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

        echo $tpl->div_error("{feature_not_installed}||{error_feature_not_installed}").
            "<script>$jstiny</script>";
        return false;

    }


	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>$date_text</th>
        	<th nowrap></th>
            <th nowrap>IN</th>
            <th nowrap>$src</th>
            <th nowrap>$srcport</th>
            <th nowrap>OUT</th>
            <th nowrap></th>
            <th nowrap>$dst</th>
            <th nowrap>$dstport</th>
            <th nowrap>{action}</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["FW_SEARCH"]=$_GET["search"];}
	rsort($data);
	
	$loglimit["PACKET FRAGMENTS"]=true;
	$loglimit["NEW TCP w\/o SYN"]=true;
	$loglimitX["([A-Z]+)\s+(FLOOD)"]=true;
	$loglimitX["(MALFORMED)\s+([A-Z]+)"]=true;
	$loglimitX["(IN|OUT|PASS)-(.+?)"]=true;
	$ipinfo=new ipinfo();
	$q=new mysql();
    $ip2host=new ip2host();
	
	foreach ($data as $line){
		$line=trim($line);
        $CyberIPFeed=false;
        $FW_IN=false;
		$ruleid=0;
		$rulename=null;
		$ACTION=null;
        $SRCINFO=false;
		$FF=false;
		if(!preg_match("#^(.+?)\s+([0-9])+\s+([0-9:]+)\s+.+?kernel.+?:(.+)#", $line,$re)){
            if($GLOBALS["VERBOSE"]){
                VERBOSE("NOT FOUND [$line]",__LINE__);
            }
            continue;
        }
        if($GLOBALS["VERBOSE"]){
            VERBOSE("FOUND [$line]",__LINE__);
        }

        if(strpos(" $line","fw-nginx-cyber")>0){
            $CyberIPFeed=true;
        }
        if(strpos(" $line","fw-nginx-in")>0){
            $FW_IN=true;
        }



		$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
		$xtime=strtotime($date);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		$line=$re[4];
        $ACTION="<span class='label label-danger'>DENY</span>";

		
		$ri=preg_split("#\s+#", $line);
		foreach ($ri as $tron){
			if(!preg_match("#(.+?)=(.*)#", $tron,$re)){$KEYS[trim($tron)]=true;continue;}
			$KEYS[trim($re[1])]=trim($re[2]);
		}
		if(!isset($KEYS["IN"])){$KEYS["IN"]=null;}
		if(!isset($KEYS["OUT"])){$KEYS["OUT"]=null;}
		if($KEYS["IN"]<>null){
			if(!isset($INTERFACES[$KEYS["IN"]])){$nic=new system_nic($KEYS["IN"]);$INTERFACES[$KEYS["IN"]]="{$KEYS["IN"]} - $nic->NICNAME"; }
		}
		if($KEYS["OUT"]<>null){
			if(!isset($INTERFACES[$KEYS["OUT"]])){$nic=new system_nic($KEYS["OUT"]);$INTERFACES[$KEYS["OUT"]]="{$KEYS["OUT"]} - $nic->NICNAME"; }
		}	

		if($ACTION==null){
			$ACTION="<span class='label label-info'> - </span>";
		}
        $IN=null;
		$OUT=$INTERFACES[$KEYS["OUT"]];
        if(isset($INTERFACES[$KEYS["IN"]])) {
            $IN = $INTERFACES[$KEYS["IN"]];
        }
		$MAC_DEST=null;
		$MAC_SRC=null;
		if(isset($KEYS["MAC"])){
			$MACZ=explode(":",$KEYS["MAC"]);
			$MAC_DEST="&nbsp;|&nbsp;{$MACZ[0]}:{$MACZ[1]}:{$MACZ[2]}:{$MACZ[3]}:{$MACZ[4]}:{$MACZ[5]}";
			$MAC_SRC="&nbsp;|&nbsp;{$MACZ[6]}:{$MACZ[7]}:{$MACZ[8]}:{$MACZ[9]}:{$MACZ[10]}:{$MACZ[11]}";
		}


		if(!isset($_SESSION["RESOLV"][$KEYS["SRC"]])){
            $_SESSION["RESOLV"][$KEYS["SRC"]]=gethostbyaddr($KEYS["SRC"]);
        }
        if(!isset($_SESSION["RESOLV"][$KEYS["DST"]])){
            $_SESSION["RESOLV"][$KEYS["DST"]]=gethostbyaddr($KEYS["DST"]);
        }

        if($GLOBALS["VERBOSE"]){ unset($_SESSION["IPINFO"][$KEYS["DST"]]); }

		if(!isset($_SESSION["IPINFO"][$KEYS["DST"]])){
            if($ipinfo->get($KEYS["DST"])){
                $_SESSION["IPINFO"][$KEYS["DST"]]=$ipinfo->results;
            }else{
                $_SESSION["IPINFO"][$KEYS["DST"]]=null;
            }

        }
        $ipinfo_enc=$_SESSION["IPINFO"][$KEYS["DST"]];
		$resolved=$_SESSION["RESOLV"][$KEYS["SRC"]];
        $resolvedd=$_SESSION["RESOLV"][$KEYS["DST"]];
        $IpsDesc=array();
        $IpsDesc_text=null;
		if($OUT==null){$OUT=" - ";}
		if($IN==null){$IN=" - ";}

		$prefix=null;
		if($KEYS["DPT"]==25){$prefix="smtp:";}
        if($KEYS["DPT"]==80){$prefix="http:";}
        if($KEYS["DPT"]==443){$prefix="https:";}
        if($KEYS["DPT"]==21){$prefix="ftp:";}
        if($ipinfo_enc<>null){
            $ipinfo_dec=json_decode($ipinfo_enc);
            if (property_exists($ipinfo_dec, "continent")) {
                if($ipinfo_dec->continent<>null) {
                    $IpsDesc[] = $ipinfo_dec->continent;
                }
            }
            if (property_exists($ipinfo_dec, "countryName")) {
                if($ipinfo_dec->countryName<>null) {
                    $IpsDesc[] = $ipinfo_dec->countryName;
                }
            }
            if (property_exists($ipinfo_dec, "isp")) {
                if($ipinfo_dec->isp<>null) {
                    $IpsDesc[] = $ipinfo_dec->isp;
                }
            }

        }
        $IpsDesc_button=null;
        if(count($IpsDesc)>0){

            $IpsDesc_text="<small>".@implode("&nbsp;|&nbsp;",$IpsDesc)."</small>";
            $IpsDesc_button=base64_encode($IpsDesc_text);
        }

        $js = "Loadjs('$page?white-js={$KEYS["DST"]}&port={$KEYS["DPT"]}&bt=$IpsDesc_button')";
        $button = "<button class='btn btn-primary btn-xs' OnClick=\"$js\">{whitelist}</button>";

        if($CyberIPFeed){
            $SRCINFO=true;
            $button="&nbsp;";
            $MAC_SRC="&nbsp;<i class='text-danger fas fa-hockey-mask'></i>&nbsp;";
        }
        if($FW_IN){
            $button="&nbsp;";
            $MAC_SRC="&nbsp;<i class='text-danger fa-sharp fa-solid fa-shield'></i>&nbsp;";
            $SRCINFO=true;
        }
        $flag=null;
        $fff=array();
        if($SRCINFO){
            $ipinfoApi=$ip2host->ipinfoApi($KEYS["SRC"]);
            if($ipinfoApi["flag"]==null){$ipinfoApi["flag"]="flags/info.png";}
            $flag="<img src='/img/{$ipinfoApi["flag"]}'>&nbsp;&nbsp;";
            $ipinfos[]="<strong>{country}</strong>:&nbsp;{$ipinfoApi["country"]}/{$ipinfoApi["countryName"]}";
            $ipinfos[]="<strong>{city}</strong>:&nbsp;{$ipinfoApi["city"]}";
            $ipinfos[]="<strong>ISP</strong>:&nbsp;{$ipinfoApi["isp"]}";
            if($resolved<>null) {
                $fff[] = "$resolved";
            }
            if($ipinfoApi["countryName"]<>null){$fff[]=$ipinfoApi["countryName"];}
            if($ipinfoApi["city"]<>null){$fff[]=$ipinfoApi["city"];}
            if($ipinfoApi["isp"]<>null){$fff[]=$ipinfoApi["isp"];}
            $resolved=@implode(" / ",$fff);
        }

		$html[]="<tr>
				<td width='1%' nowrap>$FTime</td>
				<td width='1%' nowrap>$ACTION</td>
				<td width='1%' nowrap>{$IN}</td>
                <td nowrap>$flag{$KEYS["SRC"]}{$MAC_SRC} <small>({$resolved})</small></td>  
                <td width='1%' nowrap>{$KEYS["SPT"]}</td>                  
                <td width='1%' nowrap>{$OUT}</td>
                <td width='1%' nowrap><i class='fas fa-arrow-alt-to-right'></i></td>
                <td><strong>{$prefix}[{$KEYS["DST"]}]{$MAC_DEST}</strong> ({$resolvedd}:{$KEYS["DPT"]}) $IpsDesc_text</td>
                <td width='1% nowrap'>{$KEYS["DPT"]}</td>
                <td width='1% nowrap'>$button</td>        
                </tr>";
		
	}

    $TINY_ARRAY["TITLE"]="{firewall_events} {deny} &laquo;{$_GET["search"]}&raquo; ({threats})";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_NGINX_FW_EXPLAIN}";
    $TINY_ARRAY["URL"]="nginx-status";
    $TINY_ARRAY["BUTTONS"]=null;

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</tbody></table>";
    $html[]="<script>$jstiny</script>";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
	
	
}
