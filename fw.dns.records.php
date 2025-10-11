<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["CAA"])){record_CAA_save();exit;}
if(isset($_POST["xdomain_id"])){exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["search"])){main();exit;}
if(isset($_GET["record-js"])){record_js();exit;}
if(isset($_GET["record-info-js"])){record_info_js();exit;}
if(isset($_GET["record-info"])){record_info();exit;}
if(isset($_GET["record-popup"])){record_popup();exit;}
if(isset($_GET["enable-js"])){record_disable();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["td-column"])){td_column();exit;}

if(isset($_POST["id"])){record_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){record_delete();exit;}
page();

function record_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_record}");
	$id=intval($_GET["record-js"]);
	if($id>0){
		$q=new mysql_pdns();
		$sql="SELECT name,`type`,content FROM records WHERE id=$id";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$ligne["content"]=substr($ligne["content"],0,20);
		$title="{$ligne["name"]}::{$ligne["type"]}::{$ligne["content"]}...";
		
	}
	$domainid=intval($_GET["domain-id"]);
	$tpl->js_dialog7($title, "$page?record-popup=$id&domain-id=$domainid&function={$_GET["function"]}",810);

	
}
function record_disable(){
	$q=new mysql_pdns();
	$id=$_GET['enable-js'];
	$sql="SELECT disabled FROM records WHERE id=$id";
	$ligne2=mysqli_fetch_array($q->QUERY_SQL($sql));
	$disabled=intval($ligne2["disabled"]);

	if($disabled==0){
		$q->QUERY_SQL("UPDATE records SET disabled=1 WHERE id=$id");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	$q->QUERY_SQL("UPDATE records SET disabled=0 WHERE id=$id");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";}
	return;
}

function record_info_js(){
	$type=$_GET["type"];
	$id=$_GET["id"];
	$q=new mysql_pdns();
	$tpl=new template_admin();
	$prefix="{new_record} ";
	
	if($id>0){
		$sql="SELECT * FROM records WHERE id=$id";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$domainid=$ligne["domain_id"];
		$type=$ligne["type"];
		$name=$ligne["name"];
		$prefix="{record}: $id $name >> ";
		
	}else{
		$domainid=$_GET["domainid"];
	}
	
	$sql="SELECT name FROM domains WHERE id=$domainid";
	$ligne2=mysqli_fetch_array($q->QUERY_SQL($sql));
	$title="{$prefix}Type:$type ({$ligne2["name"]})";

	$page=CurrentPageName();
	$tpl->js_dialog7($title, "$page?record-info=yes&domain-id=$domainid&type=$type&id=$id&function={$_GET["function"]}",810);
	
}
function record_delete(){
	$id=$_POST["delete"];
	$q=new mysql_pdns();
    $sql="SELECT * FROM records WHERE id=$id";
    $ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
    $domainid=$ligne["domain_id"];
    $type=$ligne["type"];
    $name=$ligne["name"];
	$q->QUERY_SQL("DELETE FROM records WHERE id=$id");
	if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Removing DNS record $name DNS record type $type from domain ID: $domainid");
	$sock=new sockets();
	$sock->getFrameWork("pdns.php?dnscheck=yes");

	return true;


}


function record_wizard(){
	$q=new mysql_pdns();
	$tpl=new template_admin();
	$domainid=intval($_GET["domain-id"]);
	$page=CurrentPageName();
	$title="{new_record}";
	$bname="{add}";

    $EnablePDNS      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
    $GetRtypes=GetRtypes();
    if($EnablePDNS==1){
        VERBOSE("EnablePDNS OK",__LINE__);
        $GetRtypes[]="ALIAS";
    }


	foreach ($GetRtypes as $record_type) {$TYPES[$record_type]=$record_type;}
	if($domainid==0){
		$sql="SELECT * FROM domains ORDER BY name";
		$results=$q->QUERY_SQL($sql);
		$DOMAINS=array();
	
	
		while ($ligne2 = mysqli_fetch_assoc($results)) {
			$id=$ligne2["id"];
			if(intval($id)==0){continue;}
			$name=$ligne2["name"];
			if($name==null){continue;}
			$DOMAINS[$id]=$name;
		}
	
		if(count($DOMAINS)==0){
			echo $tpl->_ENGINE_parse_body("<div class=alert alert-danger style='font-size:18px'>{error_dns_record_no_domain}</div>");
			return;
		}
	
		$form[]=$tpl->field_array_hash($DOMAINS, "xdomain_id", "{domain}", 0);
	
	
	}else{
		$form[]=$tpl->field_hidden("xdomain_id", $domainid);
	}
	
	$fielddomain_id=md5("xdomain_id$tpl->suffixid");
	$fieldtype_id=md5("xtype$tpl->suffixid");
	
	$form[]=$tpl->field_array_hash($TYPES, "xtype", "{type} (IN)", null,true,"blur()");
	$html[]=$tpl->form_outside("$title", @implode("\n", $form),"{ADD_DNS_ENTRY_TEXT}",$bname,"jsAfter$fielddomain_id()","AsDnsAdministrator");
	
	$html[]="
	<script>
		function jsAfter$fielddomain_id(){
			var domain_id=$domainid;
			if(domain_id==0){
				domain_id=parseInt(document.getElementById('$fielddomain_id').value);
			}
			var type=document.getElementById('$fieldtype_id').value;
			Loadjs('$page?record-info-js=yes&domainid='+domain_id+'&type='+type+'&id=0&function={$_GET["function"]}');
		}
	</script>";
	
	echo @implode("\n", $html);
	
	
}

function record_CAA($MAIN_DOMAIN_NAME):bool{
    if (isset($_GET["function"])) {
        if ($_GET["function"] <> null) {
            $functionToLoad = "{$_GET["function"]}()";
        }
    }

    $q              = new mysql_pdns();
    $tpl            = new template_admin();
    $domainid       = intval($_GET["domain-id"]);
    $id             = intval($_GET["id"]);
    $title          = "$MAIN_DOMAIN_NAME  <small>(Certification Authority Authorization)</small>";
    $explain_form   = "{DNS_CAA_EXPLAIN}";
    $btname         = "{add}";
    $jsafter        = "dialogInstance7.close();$functionToLoad;Loadjs('fw.dns.records.php?filltable=$id');";
    $page           = CurrentPageName();

    $sql = "SELECT * FROM records LIMIT 0,1";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->div_error($q->mysql_error);}
    $heads=mysqli_fetch_field($results);
    foreach ($heads as $FieldName) {
        $FIELDS[$FieldName]=true;
    }


    if ($id > 0) {
        $sql = "SELECT * FROM records WHERE id=$id";
        $results=$q->QUERY_SQL($sql);
        $ligne = mysqli_fetch_array($results);
        $domainid = $ligne["domain_id"];
        $btname = "{apply}";
        $tpl->form_add_button("{delete}", "DeleteDNSR$id()");
    }

    if(!isset($ligne["ttl"])){$ligne["ttl"]=3600;}
    if (!is_numeric($ligne["ttl"])) {$ligne["ttl"] = 3600;}

    // pdnsutil add-record "touzeau.biz" @ CAA "3600" "0 issue \"touzeau.biz\""

    if(!isset($FIELDS["change_date"])){
        $form[] = $tpl->field_hidden("change_date", time());
    }

    $form[] = $tpl->field_hidden("CAA", $id);
    $form[] = $tpl->field_hidden("id", $id);
    $form[] = $tpl->field_hidden("domain_id", $domainid);
    $form[] = $tpl->field_hidden("domainname",$MAIN_DOMAIN_NAME);
    $form[] = $tpl->field_hidden("type", "CAA");

    $TAGS["issue"]="issue";
    $TAGS["issuewild"]="issuewild";
    $TAGS["iodef"]="iodef";
    $flag=0;
    $tag="issue";
    $value=$MAIN_DOMAIN_NAME;

    if(preg_match("#^([0-9]+)\s+([a-z]+)\s+\"(.+?)\"#",$ligne["content"],$re)){
        $flag=$re[1];
        $tag=$re[2];
        $value=$re[3];
    }

    $form[]=$tpl->field_numeric("{flag}","flag",$flag);
    $form[]=$tpl->field_array_hash($TAGS,"tag","{tag}",$tag,true);
    $form[]=$tpl->field_text("value","{value}",$value,true);
    $form[]=$tpl->field_numeric("{ttl}","ttl",$ligne["ttl"]);
    $html[]=$tpl->form_outside($title, @implode("\n", $form),$explain_form,$btname,$jsafter,"AsDnsAdministrator");
    $html[]="<script>";
    $html[]="function DeleteDNSR$id(){";
    $html[]="Loadjs('$page?delete-js=$id&byPOP=yes&function={$_GET["function"]}');";
    $html[]="}";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function record_CAA_save():bool{
    $now            = time();
    $id             = intval($_POST["id"]);
    $domainid       = intval($_POST["domain_id"]);
    $name           = trim($_POST["domainname"]);
    $type           = "CAA";
    $flag           = intval($_POST["flag"]);
    $tag            = trim($_POST["tag"]);
    $value          = trim($_POST["value"]);
    $ttl            = intval($_POST["ttl"]);
    $q              = new mysql_pdns();

    if($flag<0){$flag=0;}
    if($flag>255){$flag=255;}

    $content        ="$flag $tag \"$value\"";



    $AdzF[]="domain_id";
    $AdzV[]="'$domainid'";
    $Modz[]="domain_id='$domainid'";


    $AdzF[]="name";
    $AdzV[]="'$name'";
    $Modz[]="name='$name'";

    $AdzF[]="type";
    $AdzV[]="'$type'";
    $Modz[]="type='$type'";

    $AdzF[]="content";
    $AdzV[]="'$content'";
    $Modz[]="content='$content'";

    $AdzF[]="ttl";
    $AdzV[]="'$ttl'";
    $Modz[]="ttl='$ttl'";

    $AdzF[]="prio";
    $AdzV[]="'0'";
    $Modz[]="prio='0'";

    if(!isset($_POST["change_date"])) {
        $AdzF[] = "change_date";
        $AdzV[]="'$now'";
        $Modz[]="change_date='$now'";
    }
    $AdzF[] = "disabled";
    $AdzV[]="'0'";


    if(!$q->FIELD_EXISTS("records","change_date")){
        $q->QUERY_SQL("ALTER TABLE records ADD change_date INT DEFAULT NULL");
    }




    if($id==0){
        $sql="INSERT INTO records (".@implode(",",$AdzF).") VALUES (".@implode(",",$AdzV).")";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "jserror:" .$q->mysql_error;return false;}
        return true;
    }

    $sql="UPDATE records SET ".@implode("," ,$Modz)." WHERE id=$id";


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return false;}
    return true;
}


function record_info()
{
    $q              = new mysql_pdns();
    $tpl            = new template_admin();
    $domainid       = intval($_GET["domain-id"]);
    $record_type    = $_GET["type"];
    $page           = CurrentPageName();
    $PowerDNSEnableClusterSlave = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $sql = "SELECT name FROM domains WHERE id=$domainid";
    $ligne2 = mysqli_fetch_array($q->QUERY_SQL($sql));
    $MAIN_DOMAIN_NAME = $ligne2["name"];
    $title_domain = "&nbsp;&raquo;&raquo;" . $ligne2["name"];
    $explain_form = null;
    $LABEL_NAME = "{name}";
    $REMOVE_NAME = false;
    $functionToLoad = null;
    $record_type_explain = $record_type;
    if($record_type=="CAA"){
        return record_CAA($MAIN_DOMAIN_NAME);exit;
    }


    $NONAME = false;
    $RRCODE_LABEL["SOA"] = "{zone}";
    $RRCODE["NS"] = "Name Server Record";
    $RRCODE["SOA"] = "Start of Authority Record";


    $RRCODE_EXPLAIN["SOA"] = "{SOA_RECORD_EXPLAIN}";
    $RRCODE_EXPLAIN["NS"] = "{NS_RECORD_EXPLAIN}";

    if ((strtoupper($record_type) == "PTR")  OR (strtoupper($record_type) == "SPF") ){
        $REMOVE_NAME = true;
    }

    if (isset($RRCODE["$record_type"])) {
        $record_type_explain = $RRCODE[$record_type];
    }
    if (isset($RRCODE_EXPLAIN["$record_type"])) {
        $explain_form = $RRCODE_EXPLAIN[$record_type];
    }
    if (isset($RRCODE_LABEL["$record_type"])) {
        $LABEL_NAME = $RRCODE_LABEL[$record_type];
    }
    if (isset($_GET["function"])) {
        if ($_GET["function"] <> null) {
            $functionToLoad = "{$_GET["function"]}()";
        }
    }

    $title = "$record_type_explain$title_domain";
    $SHOW_TTL = true;
    $id = intval($_GET["id"]);
    $btname = "{add}";
    $jsafter = "dialogInstance7.close();$functionToLoad;Loadjs('fw.dns.records.php?filltable=$id');";


    if ( (strtoupper($record_type) == "TXT") or (strtoupper($record_type) == "SPF") ){
        $SHOW_TTL = false;
    }



    if ($id > 0) {
        $sql = "SELECT * FROM records WHERE id=$id";
        $ligne = mysqli_fetch_array($q->QUERY_SQL($sql));
        $domainid = $ligne["domain_id"];
        $btname = "{apply}";
        $tpl->form_add_button("{delete}", "DeleteDNSR$id()");
    } else {

        if (strtoupper($record_type) == "MX") {
            $ligne["name"] = $ligne2["name"];
        }
        if (strtoupper($record_type) == "SPF") {
            $ligne["name"] = $ligne2["name"];
        }
        if (strtoupper($record_type) == "TXT") {
            $ligne["name"] = "@";
        }

    }

    if (!is_numeric($ligne["ttl"])) {$ligne["ttl"] = 3600;}

    $form[] = $tpl->field_hidden("id", $id);
    $form[] = $tpl->field_hidden("domain_id", $domainid);
    $form[] = $tpl->field_hidden("type", $record_type);

    if ((strtoupper($record_type) == "NS") OR (strtoupper($record_type) == "SOA") OR (strtoupper($record_type) == "MX")) {
        $NONAME = true;
        $tpl->field_hidden("name", $MAIN_DOMAIN_NAME);
    }

    if ((strtoupper($record_type) == "A") OR (strtoupper($record_type) == "AAAA") ) {
        if($ligne["name"]==$MAIN_DOMAIN_NAME){
            $NONAME = true;
            $tpl->field_hidden("name", $MAIN_DOMAIN_NAME);
        }
    }


    if (!$REMOVE_NAME) {
       if (!$NONAME) {
            $form[] = $tpl->field_text("name", "$LABEL_NAME", $ligne["name"], true, "");
        }
    }


	
	switch (strtoupper($record_type)) {
		case "A":
			$ADDPTR=0;
			if($id>0){
				$content_array = preg_split("/\./", $ligne["content"]);
				$content_rev = sprintf("%d.%d.%d.%d.in-addr.arpa", $content_array[3], $content_array[2], $content_array[1], $content_array[0]);
				$sql="SELECT id FROM records WHERE name='$content_rev'";
				$ligne2=mysqli_fetch_array($q->QUERY_SQL($sql));
				$id_reverse=intval($ligne2["id"]);
				
				if($id_reverse>0){
					$ADDPTR=1;
					$form[]=$tpl->field_hidden("ptr_id",$id_reverse);
				}
			}
		
			$form[]=$tpl->field_ipaddr("content", "{ipaddr}", $ligne["content"]);
			$form[]=$tpl->field_checkbox("ADDPTR","{dns_ptr_text}",$ADDPTR);
			break;
			
			
		case "TXT":
			$form[]=$tpl->field_textarea("content", "{content}", $ligne["content"],"636px","110px");
			break;
			
		case "CERT":
			$form[]=$tpl->field_textarea("content", "{certificate}", $ligne["content"],"636px","110px");
			break;		

		case "SSHFP":
			$form[]=$tpl->field_textarea("content", "SSH KEY", $ligne["content"],"636px","110px");
			break;

		case "IPSECKEY":
			$form[]=$tpl->field_textarea("content", "IP SECKEY", $ligne["content"],"636px","110px");
			break;	

			
		case "TKEY":
			$form[]=$tpl->field_textarea("content", "Secret Key Establishment for DNS", $ligne["content"],"636px","110px");
			break;			
			
		case "SIG":
			$form[]=$tpl->field_textarea("content", "Domain Name System Security Extensions", $ligne["content"],"636px","110px");
			break;			
			
		case "SPF":
			$form[]=$tpl->field_textarea("content", "SPF", $ligne["content"],"636px","50px");
			break;

        case "MX":
            $SHOW_TTL=false;
            $form[]=$tpl->field_text("content", "{smtp_server_address}", $ligne["content"],true);
            if(intval($ligne["prio"])==0){$ligne["prio"]=5;}
            if($id==0){$form[]=$tpl->field_numeric("prio","PRIO",$ligne["prio"]);}
            break;

		case "SOA":
			$SHOW_TTL=false;
			$TR=preg_split('/\s+/', $ligne["content"]);
			$form[]=$tpl->field_text("SOA_MNAME", "MNAME", $TR[0],true,"{SOA_COMPUTER_EXPLAIN}");
			$form[]=$tpl->field_text("SOA_EMAIL", "RNAME", $TR[1],true,"{SOA_EMAIL_EXPLAIN}");
			if(intval($TR[2])==0){$TR[2]=date("YmdHi");}
			if(intval($TR[3])==0){$TR[3]=86400;}
			if(intval($TR[4])==0){$TR[4]=7200;}
			if(intval($TR[5])==0){$TR[5]=2419200;}
			if(intval($TR[6])==0){$TR[6]=240;}
			
			$form[]=$tpl->field_numeric("SOA_SERIAL","{serial}",$TR[2],"{DNS_SOA_SERIAL_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_REFRESH","{refresh}",$TR[3],"{SOA_REFRESH_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_RETRY","{retry}",$TR[4],"{SOA_RETRY_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_EXPIRE","{expire}",$TR[5],"{SOA_EXPIRE_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_NEG","{negative_cache}",$TR[6],"{SOA_NEG_EXPLAIN}");
			break;

        case "PTR":
            $form[] = $tpl->field_text("content", "{hostname}",$ligne["content"]);

            if($ligne["name"]==null) {
                $form[] = $tpl->field_ipaddr("name", "{ipaddr}", $ligne["name"], true, "");
            }else{
                $form[] = $tpl->field_text("name", "PTR {record}", $ligne["name"], true, "");
            }

            break;

        case "NS":
            $form[]=$tpl->field_text("content", "{hostname}", $ligne["content"]);
            break;

			
		default:
			$form[]=$tpl->field_text("content", "{value}", $ligne["content"]);
	}
	
	if($id>0){$form[]=$tpl->field_numeric("prio","PRIO",$ligne["prio"]);}
	if($SHOW_TTL){$form[]=$tpl->field_numeric("ttl","TTL ({seconds})",$ligne["ttl"],"{DNS_TTL_EXPLAIN}");}
	
	
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	VERBOSE("PowerDNSEnableClusterSlave=$PowerDNSEnableClusterSlave", __LINE__);
	
	if($PowerDNSEnableClusterSlave==1){
		$tpl->FORM_LOCKED=true;
		$tpl->this_form_locked_explain="{form_locked_cluster_client}";
	}
	
	$html[]=$tpl->form_outside($title, @implode("\n", $form),$explain_form,$btname,$jsafter,"AsDnsAdministrator");
	$html[]="<script>";
	$html[]="function DeleteDNSR$id(){";
	$html[]="Loadjs('$page?delete-js=$id&byPOP=yes&function={$_GET["function"]}');";
	$html[]="}";
	$html[]="</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function record_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$id=intval($_POST["id"]);
	unset($_POST["id"]);
	$ptr_id=0;
	$q=new mysql_pdns();
	$now=time();
	$ipClass=new IP();
    $fqdn_name=null;
    $UPDATE_DNS_DOMAIN=false;

    if(!$q->FIELD_EXISTS("records","change_date")){$q->QUERY_SQL("ALTER TABLE records ADD change_date INT DEFAULT NULL");}

    $domain_id=intval($_POST["domain_id"]);

    if($domain_id==0){
        echo "jserror:Domain ID not posted";
        return false;
    }

	$sql="SELECT name FROM domains WHERE id={$domain_id}";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$zone_name=$ligne["name"];
	if(isset($_POST["name"])) {$fqdn_name = $_POST["name"];}

    writelogs("domain id: $domain_id",__FUNCTION__,__FILE__,__LINE__);
    writelogs("Zone.....: $zone_name",__FUNCTION__,__FILE__,__LINE__);
    writelogs("Type.....: {$_POST["type"]}",__FUNCTION__,__FILE__,__LINE__);
    writelogs("name.....: {$_POST["name"]}",__FUNCTION__,__FILE__,__LINE__);




	if(($_POST["type"]=="A") OR ($_POST["type"]=="AAAA")){
        if(strpos($_POST["name"],";")>0){
            echo "Invalid character ';' in {$_POST["name"]}\n";
            return false;
        }
		$_POST["name"]=str_replace(".$zone_name", "", $_POST["name"]);
        if($_POST["name"]=="*"){$_POST["name"]="$zone_name";}
        if($_POST["name"]=="@"){$_POST["name"]="$zone_name";}

        $zone_namerx=str_replace(".","\.",$zone_name);
        $fqdn_name=$_POST["name"];
        if(!preg_match("#$zone_namerx#",$_POST["name"])){
            writelogs("#$zone_namerx# no match in {$_POST["name"]}",__FUNCTION__,__FILE__,__LINE__);
            $fqdn_name = sprintf("%s.%s", $_POST["name"], $zone_name);
            writelogs("#fqdn_name=$fqdn_name",__FUNCTION__,__FILE__,__LINE__);

        }
	}

	if($_POST["type"]=="PTR"){
        if(strpos($_POST["name"],";")>0){
            echo "Invalid character ';' in {$_POST["name"]}\n";
            return false;
        }
        if(strpos($_POST["content"],";")>0){
            echo "Invalid character ';' in {$_POST["content"]}\n";
            return false;
        }

	    if(!preg_match("#in-addr\.arpa#",$_POST["name"])) {
            if(!$ipClass->isValid($_POST["name"])){
                echo "Not a valid IP {$_POST["name"]} address";
                return false;
             }

            $content_array = preg_split("/\./", $_POST["name"]);
            $_POST["name"] = sprintf("%d.%d.%d.%d.in-addr.arpa", $content_array[3], $content_array[2], $content_array[1], $content_array[0]);
        }else{
            $content_array = preg_split("/\./", $_POST["name"]);
            $Ipaddr=sprintf("%d.%d.%d.%d",$content_array[3], $content_array[2], $content_array[1], $content_array[0]);
            if(!$ipClass->isValid($Ipaddr)){
                echo "Not a valid PTR {$Ipaddr} address";
                return false;
            }

        }
    }
    if( ($_POST["type"]=="CNAME") OR ($_POST["type"]=="ALIAS")){
        if(strpos($_POST["name"],";")>0){
            echo "Invalid character ';' in {$_POST["name"]}\n";
            return false;
        }
        $_POST["name"]=str_replace(".$zone_name", "", $_POST["name"]);
        if($_POST["name"]=="*"){$_POST["name"]="$zone_name";}
        if($_POST["name"]=="@"){$_POST["name"]="$zone_name";}

    }

	
	if($_POST["type"]=="SOA"){
		$SOA_MNAME=$_POST["SOA_MNAME"];
		$SOA_EMAIL=$_POST["SOA_EMAIL"];
		$SOA_SERIAL=$_POST["SOA_SERIAL"];
		$SOA_REFRESH=$_POST["SOA_REFRESH"];
		$SOA_RETRY=$_POST["SOA_RETRY"];
		$SOA_EXPIRE=$_POST["SOA_EXPIRE"];
		$SOA_NEG=$_POST["SOA_NEG"];
		$_POST["ttl"]=$SOA_NEG;
		$_POST["content"]="$SOA_MNAME $SOA_EMAIL $SOA_SERIAL $SOA_REFRESH $SOA_RETRY $SOA_EXPIRE $SOA_NEG";
        $UPDATE_DNS_DOMAIN=true;
		
	}
    if(!isset($_POST["ttl"])){$_POST["ttl"]=3600;}
    if(!isset($_POST["prio"])){$_POST["prio"]=1;}

	if($id==0){
	    $MAIN["domain_id"]=$domain_id;
        $MAIN["name"]=$fqdn_name;
        $MAIN["type"]=$_POST["type"];
        $MAIN["content"]=$_POST["content"];
        $MAIN["ttl"]=$_POST["ttl"];
        $MAIN["prio"]=intval($_POST["prio"]);


	    $sock=new sockets();
        $sock->SET_INFO("PDNSAddRecord",base64_encode(serialize($MAIN)));
        $sock->getFrameWork("pdns.php?add-record=yes");

        $sql="SELECT id from records WHERE domain_id={$domain_id} and type='{$_POST["type"]}' and content='{$_POST["content"]}'";

        $ligne=$q->mysqli_fetch_array($sql);
        $id=intval($ligne["id"]);
        if($id==0){ echo pdns_parse_command_line(); return false; }
        admin_tracks("Creating $fqdn_name DNS record {$_POST["type"]} with {$MAIN["content"]} value");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?check-domain=$id");
        return true;
        // ON S ARRETE LA
	}

	if($id>0){
	$sql="UPDATE records SET
		domain_id='{$_POST["domain_id"]}',
		name='$fqdn_name',
		type='{$_POST["type"]}',
		content='{$_POST["content"]}',
		ttl='{$_POST["ttl"]}',
		prio='{$_POST["prio"]}',
		change_date=$now
		WHERE id=$id";
	}

	$q->QUERY_SQL($sql);

	if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Modify $fqdn_name DNS record with {$_POST["content"]} value");

	if($UPDATE_DNS_DOMAIN){
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?check-domain={$_POST["domain_id"]}");
    }

	if(isset($_POST["ptr_id"])){$ptr_id=intval($_POST["ptr_id"]);}

	if($ptr_id==0){
		if($_POST["type"]=="A"){
			$content=$_POST["content"];
			$content_array = preg_split("/\./", $content);
			$content_rev = sprintf("%d.%d.%d.%d.in-addr.arpa", $content_array[3], $content_array[2], $content_array[1], $content_array[0]);
			$sql="SELECT id FROM records WHERE name='$content_rev'";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
			$ptr_id=intval($ligne["id"]);
			$q->QUERY_SQL("DELETE FROM records WHERE name='$content_rev'");
		}

	}

	if($ptr_id>0){
		$sql="DELETE FROM records WHERE id=$ptr_id";
		$q->QUERY_SQL($sql);
	}


	if(isset($_POST["ADDPTR"])){
		if(intval($_POST["ADDPTR"])==1){
			$content=$_POST["content"];
			$content_array = preg_split("/\./", $content);
			$domain = sprintf("%d.%d.%d.in-addr.arpa", $content_array[2], $content_array[1], $content_array[0]);
			$content_rev = sprintf("%d.%d.%d.%d.in-addr.arpa", $content_array[3], $content_array[2], $content_array[1], $content_array[0]);

				
			$domainid=get_best_matching_zone_id_from_ipaddr($content);
			if($domainid<1){
                $domainid=intval($q->AddDomain($domain));
				if(!$q->ok){echo $q->mysql_error."\n$sql\n";return false;}
				if($domainid==0) {
                    $domainid = intval(get_best_matching_zone_id_from_ipaddr($content));
                }
				if($domainid<1){echo "Unable to add reverse PTR '$domain' ($domainid)??!";return false;}
			}
				
			$sql="INSERT INTO records (domain_id, name, type, content, ttl, prio, change_date)
			VALUES ('$domainid','$content_rev','PTR','$fqdn_name','{$_POST["ttl"]}','{$_POST["prio"]}',$now)";
			$q->QUERY_SQL($sql);
            admin_tracks("Modify PTR DNS record of $fqdn_name with $content_rev value");
            if(!$q->ok){echo $q->mysql_error;return false;}
		}
	}



    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();

    if($domain_id>0){
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?rectify-zone=$domain_id");
    }else {
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?dnscheck=yes");
    }


	return true;

}
function pdns_parse_command_line(){
    $tpl=new template_admin();
    $main=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSAddRecordResults");
    writelogs($main. " len(".strlen($main).")",__FUNCTION__,__FILE__,__LINE__);
    $data=explode("<br>",$main);

    foreach ($data as $line){
        $line=str_replace("\n","",$line);
        $line=str_replace("\\n","",$line);
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#Attempting#i",$line)){
            return $tpl->post_error($line);
        }
        if(preg_match("#Reading random#i",$line)){continue;}
        if(preg_match("#UeberBackend#i",$line)){continue;}
        $f[]=$line;
    }

    $text=@implode("<br>",$f);
   return $tpl->post_error($text);

}


function get_best_matching_zone_id_from_ipaddr($ipaddr){


	$content_array = preg_split("/\./", $ipaddr);
	$domain0 = sprintf("%d.%d.%d.in-addr.arpa", $content_array[3], $content_array[2], $content_array[1]);
	$domain1 = sprintf("%d.%d.in-addr.arpa", $content_array[3], $content_array[2], $content_array[1]);
	$domain2 = sprintf("%d.in-addr.arpa", $content_array[3], $content_array[2], $content_array[1]);

	$q=new mysql_pdns();
	$query = "SELECT domain_id FROM records WHERE name='$domain0' and type='SOA' ";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($query));
	$zone_name=intval($ligne["domain_id"]);
	if($zone_name>0){return $zone_name;}

	$query = "SELECT domain_id FROM records WHERE name='$domain1' and type='SOA' ";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($query));
	$zone_name=intval($ligne["domain_id"]);
	if($zone_name>0){return $zone_name;}

	$query = "SELECT domain_id FROM records WHERE name='$domain2' and type='SOA' ";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($query));
	$zone_name=intval($ligne["domain_id"]);
	if($zone_name>0){return $zone_name;}
}

function record_popup(){
	$id=intval($_GET["record-popup"]);
	if($id==0){record_wizard();return;}
	$q=new mysql_pdns();
	$tpl=new template_admin();
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

	$title_domain=null;
	$t=time();
	$bname="{add}";
	$title="{new_record}";
	$domainid=intval($_GET["domain-id"]);
    $jsafter=null;
	VERBOSE("PowerDNSEnableClusterSlave=$PowerDNSEnableClusterSlave", __LINE__);
	//ALTER TABLE records MODIFY content VARCHAR(60000);
	
	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM records WHERE id=$id";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$title="{type} {$ligne["type"]}&nbsp;&laquo;&nbsp;{$ligne["name"]}&nbsp;&laquo;&nbsp{$ligne["content"]}";

	}


    $EnablePDNS      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
    $GetRtypes=GetRtypes();
    if($EnablePDNS==1){
        VERBOSE("EnablePDNS OK",__LINE__);
        $GetRtypes[]="ALIAS";
        $GetRtypes[]="LUA";
    }
	foreach ($GetRtypes as $record_type) {$TYPES[$record_type]=$record_type;}


	if($domainid==0){
		$sql="SELECT * FROM domains ORDER BY name";
		$results=$q->QUERY_SQL($sql);
		$DOMAINS=array();


		while ($ligne2 = mysqli_fetch_assoc($results)) {
			$id=$ligne2["id"];
			if(intval($id)==0){continue;}
			$name=$ligne2["name"];
			if($name==null){continue;}
			$DOMAINS[$id]=$name;
		}

		if(count($DOMAINS)==0){
			echo $tpl->_ENGINE_parse_body("<div class=alert alert-danger style='font-size:18px'>{error_dns_record_no_domain}</div>");
			return;
		}
	
		$form[]=$tpl->field_array_hash($DOMAINS, "domain_id", "{domain}", $ligne["domain_id"]);


	}

	if($domainid>0){
		$sql="SELECT name FROM domains WHERE id=$domainid";
		$ligne2=mysqli_fetch_array($q->QUERY_SQL($sql));
		$title_domain="&nbsp;&raquo;&raquo;".$ligne2["name"];
	}
	
	
	if($PowerDNSEnableClusterSlave==1){$btname=null;}
	
	$form[]=$tpl->field_array_hash_simple($TYPES, "type", "{type} (IN)", $ligne["type"],true,"FormRecordType$t()");
	$html[]=$tpl->form_outside("$title{$title_domain}", @implode("\n", $form),"{ADD_DNS_ENTRY_TEXT}",$bname,"$jsafter","AsDnsAdministrator");
	$html[]="";
}

function delete_js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$id=$_GET["delete-js"];

	$q=new mysql_pdns();
	$sql="SELECT content,`type`,`name` FROM records WHERE id=$id";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$value=$ligne["content"];
	$type=$ligne["type"];
	$name=$ligne["name"];
	$delete=$tpl->javascript_parse_text("{delete}");
	$final="$('#{$_GET["id"]}').remove();";

	if(isset($_GET["function"])){
	    if($_GET["function"]<>null){
	        $function=$_GET["function"]."()";
            $final=$final.$function;
        }
    }
    if(strlen( (string) $value)>30){$value=substr($value,0,27)."...";}
	if(isset($_GET["byPOP"])){
		$final="dialogInstance7.close(); {$function}();if(document.getElementById('table-dns-records-loader')){LoadAjax('table-dns-records-loader','$page?main=yes');}";
	}
	
	$html="			
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}	
	$final
	if(document.getElementById('TABLEAU-TOP-RECHERCHE')){
		var Search=encodeURIComponent(document.getElementById('top-search').value);
		LoadAjax('MainContent','fw.top.search.php?search='+Search);
	}
}
function Save$t(){
	if(!confirm('$delete:\\n$name\\n$type\\n$value\\nID: ($id) ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$id');
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
	
Save$t()";
	echo $html;
	
}



function GetRtypes(){
	$rtypes = array(
			'A',
			'AAAA',
			'AFSDB','CAA',
			'CERT',
			'CNAME',
			'DHCID',
			'DLV',
			'DNSKEY',
			'DS',
			'EUI48',
			'EUI64',
			'HINFO',
			'IPSECKEY',
			'KEY',
			'KX',
			'LOC',
			'MINFO',
			'MR',
			'MX',
			'NAPTR',
			'NS',
			'NSEC',
			'NSEC3',
			'NSEC3PARAM',
			'OPT',
			'PTR',
			'RKEY',
			'RP',
			'RRSIG',
			'SIG',
			'SOA',
			'SPF',
			'SRV',
			'SSHFP',
			'TLSA',
			'TSIG',
			
			'TXT',
			'WKS',
	);

	return $rtypes;


}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	$title_add=null;
	if($PowerDNSEnableClusterSlave==1){
		$PowerDNSClusterClientDate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientDate"));
		if($PowerDNSClusterClientDate>0){
			$title_add="&nbsp;<small>{last_sync}: ".$tpl->time_to_date($PowerDNSClusterClientDate,true)."</small>";
		}
		
	}
    $domain_id=intval($_GET["master-domain"]);
    $new_entry=$tpl->_ENGINE_parse_body("{new_record}");

    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/recusor.restart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/recusor.restart.log";
    $ARRAY["CMD"]="pdns.php?restart-recusor=yes";
    $ARRAY["TITLE"]="{reconfigure_service} {APP_PDNS_RECURSOR}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";
    if($UnboundEnabled==1){
        $jsrestart=$tpl->framework_buildjs("/unbound/restart",
            "unbound.restart.progress","unbound.restart.log",
            "progress-unbound-restart");
    }





    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    if($PowerDNSEnableClusterSlave==0){
        $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?record-js=0&domain-id=$domain_id&function=%sfunction');\">
				<i class='fa fa-plus'></i> $new_entry </label>";}
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {reconfigure_service} </label>";
    $bts[]="</div>";


    $html=$tpl->page_header("{DNS_RECORDS}$title_add","fa fa-list-ol",
        @implode("",$bts),"$page?main=yes","dns-records","progress-firehol-restart",true,"table-dns-records-loader");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
	

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}




function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_pdns();
    $search_type=null;
	
	$database='powerdns';
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	$delete=$tpl->javascript_parse_text("{delete}");
	$content=$tpl->_ENGINE_parse_body("{content}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$record=$tpl->_ENGINE_parse_body("{record}");
	$domains=$tpl->_ENGINE_parse_body("{domains}");

	$html[]="<table id='table-dns-forward-zones' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	


	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$record}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$domains}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$type}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$content}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'></center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{$delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	if(!$q->TABLE_EXISTS("records")){
		$sql="CREATE TABLE records ( id INT auto_increment, domain_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, type VARCHAR(10) DEFAULT NULL, content VARCHAR(64000) DEFAULT NULL, ttl INT DEFAULT NULL, prio INT DEFAULT NULL, change_date INT DEFAULT NULL, disabled BOOLEAN DEFAULT 0, primary key(id) ) Engine=InnoDB;";
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $q->mysql_error");return;}
		$q->QUERY_SQL("CREATE INDEX nametype_index ON records(name,type);","powerdns");
		$q->QUERY_SQL("CREATE INDEX domain_id ON records(domain_id);","powerdns");
		$q->QUERY_SQL("alter table records add ordername VARCHAR(255) BINARY;","powerdns");
		$q->QUERY_SQL("alter table records add auth bool;","powerdns");
		$q->QUERY_SQL("create index recordorder on records (domain_id, ordername);","powerdns");
		$q->QUERY_SQL("alter table records change column type type VARCHAR(10);","powerdns");
	}
    $search=trim($_GET["search"]);
	$sql=$q->search_sql($search);
	
	
	
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $q->mysql_error");return;}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$md=md5(serialize($ligne));
		$id=$ligne["id"];
		$color="#000000";
		$domain_id=$ligne["domain_id"];
		$domain=$ligne["domain"];
		$type=$ligne["type"];
		$name=$ligne["name"];
		$content=$ligne["content"];
		$disabled=intval($ligne["disabled"]);
		if(strlen($content)>90){$content=substr($content, 0,87)."...";}
		$jshost="Loadjs('$page?record-info-js=yes&domainid=$domain_id&type=$type&id=$id');";


		if($disabled==0){$enable=true;}else{$enable=false;}
        $href_domain="Loadjs('fw.dns.domain.php?domain-id=$domain_id')";
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>".$tpl->td_href($id,null,$jshost)."</td>";
		$html[]="<td><span id='dns-name-$id'>".$tpl->td_href($name,null,$jshost)."</span></td>";
		$html[]="<td><span id='dns-domain-$id'>".$tpl->td_href($domain,null,$href_domain)."</span></td>";
		$html[]="<td><span id='dns-type-$id'>".$tpl->td_href($type,null,$jshost)."</span></td>";
		$html[]="<td style='font-weight:bold'><span id='dns-content-$id'>$content</span></td>";
		
		$PowerDNSEnableClusterSlave;
		$icon_check=$tpl->icon_nothing();
		$icon_delete=$tpl->icon_nothing();
		if($PowerDNSEnableClusterSlave==0){
			$icon_check=$tpl->icon_check($enable,"Loadjs('$page?enable-js=$id&id=$md')","AsDnsAdministrator");
			$icon_delete=$tpl->icon_delete("Loadjs('$page?delete-js=$id&id=$md')","AsDnsAdministrator");
		}
		$html[]="<td style='vertical-align:middle' class='center'>$icon_check</td>";
		$html[]="<td style='vertical-align:middle' class='center'>$icon_delete</td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<div><small>$sql</small></div>
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

echo $tpl->_ENGINE_parse_body($html);
}
function filltable(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $id=intval($_GET["filltable"]);
    $f[]="LoadAjaxSilent('dns-name-$id','$page?id=$id&td-column=name');";
    $f[]="LoadAjaxSilent('dns-domain-$id','$page?id=$id&td-column=domain');";
    $f[]="LoadAjaxSilent('dns-type-$id','$page?id=$id&td-column=type');";
    $f[]="LoadAjaxSilent('dns-content-$id','$page?id=$id&td-column=content');";

echo @implode("\n",$f);



}

function tests_query($query,$type){

    $r = new Net_DNS2_Resolver( array('nameservers' => array("127.0.0.1"), "timeout" => 1) );

    try {
        $result = $r->query($query, $type);

    } catch(Net_DNS2_Exception $e) {
        $message=$e->getMessage();
        return array("ERROR"=>true,"MESSAGE"=>$message);
    }

    $AA["ERROR"]=false;
    foreach ($result->answer as $index=>$rr) {
        $AA["RECORDS"][] = $rr->text[0];
    }
    return $AA;
}

function td_column(){
    $page=CurrentPageName();
    $id=$_GET["id"];
    $field=$_GET["td-column"];
    $tpl=new template_admin();
    $q = new mysql_pdns();
    $sql = "SELECT domain_id,$field FROM records WHERE id=$id";
    $ligne = mysqli_fetch_array($q->QUERY_SQL($sql));
    $domain_id=$ligne["domain_id"];
    $type=$ligne["type"];

    $jshost="Loadjs('$page?record-info-js=yes&domainid=$domain_id&type=$type&id=$id');";

    if($field=="domain"){
        $domain=$q->GetDomainName($domain_id);
        $href_domain="Loadjs('fw.dns.domain.php?domain-id=$domain_id')";
        echo $tpl->td_href($domain,null,$href_domain);
        return;
    }
    if($field=="content"){
        $content=$ligne["content"];
        if(strlen($content)>90){$content=substr($content, 0,87)."...";}
        echo $content;
        return;
    }

    echo $tpl->td_href($ligne[$field],null,$jshost);

}