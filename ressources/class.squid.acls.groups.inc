<?php

include_once(dirname(__FILE__)."/class.squid.acls.quotas.inc");
include_once(dirname(__FILE__)."/class.ldap-extern.inc");
include_once(dirname(__FILE__)."/class.squid.templates-simple.inc");
class squid_acls_groups{
	var $GroupTypesTxt=array();
	
	var $RequestedGroupeType=null;
	var $UseDynamicGroupsAcls=1;
	var $smartphones_port=0;
	var $EnableKerbAuth=0;
	var $AUTH=0;	
	var $GroupTypes=array();
	var $ASROOT=false;
	var $error=null;
	public $AsMeta=false;
	public $ErrorsArray=array();
	public $DEBUG=array();
	public $buildacls_bytype_logs=array();
	private $hasProxyTransparent=0;
    private $buildacls_bytype;
	private $EnableTransparent27=0;
	private $SquidBubbleMode=0;
	private $ArticaSquidParameters=null;
	private $LockActiveDirectoryToKerberos=0;
	private $isSquid5=false;
	public $HLOGS=array();
    private $LDAP_EXTERNAL_AUTH=0;
	private $LDAP_AUTH=0;

	public $ARRAY_NO_ITEM=array();
	function __construct(){
		if(posix_getuid()==0){$this->ASROOT=true;}

		$qProxy=new mysql_squid_builder(true);
		$this->GroupTypes=$qProxy->acl_GroupType;
		$sock=new sockets();

	
		$this->UseDynamicGroupsAcls=1;
		$this->ArticaSquidParameters=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaSquidParameters");
		$this->xload();
		$this->EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $this->LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$this->LockActiveDirectoryToKerberos=1;}

        if( $this->LockActiveDirectoryToKerberos==1) {
            if (!is_file("/etc/squid3/krb5.keytab")) {
                $this->LockActiveDirectoryToKerberos = 0;
            }
            if(is_file("/etc/squid3/krb5.keytab")){
                $this->LockActiveDirectoryToKerberos = 1;
            }
        }

        if($this->LockActiveDirectoryToKerberos==1){$this->EnableKerbAuth=1;}
		$this->hasProxyTransparent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("hasProxyTransparent"));
		$this->EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
		$this->SquidBubbleMode=$sock->GET_INFO("SquidBubbleMode");
		$this->smartphones_port=$sock->GET_INFO("smartphones_port");
		$this->LDAP_AUTH=$sock->GET_INFO("SquidStandardLDAPAuth");
		$ini=new Bs_IniHandler();
		$ini->loadString($this->ArticaSquidParameters);
		if(isset($ini->_params["NETWORK"]["LDAP_AUTH"])){
			if(intval($ini->_params["NETWORK"]["LDAP_AUTH"])==1){$this->LDAP_AUTH=1;$this->AUTH=1;}
		}
		
		
		$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
		if($EnableOpenLDAP==0){$this->LDAP_AUTH=0;}
		
		
		if(!is_numeric($this->smartphones_port)){$this->smartphones_port=0;}
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		if(!is_numeric($this->SquidBubbleMode)){$this->SquidBubbleMode=0;}
		if($this->EnableKerbAuth==1){$this->AUTH=1;$this->UseDynamicGroupsAcls=1;}
		if($this->LDAP_AUTH==1){$this->AUTH=1;}
		$this->ARRAY_NO_ITEM=$qProxy->acl_ARRAY_NO_ITEM;
        $SquidVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        if(preg_match("#^(5|6|7)\.#",$SquidVersion)){$this->isSquid5=true;}

		
	}
	
	private function xload(){
		$ini=new Bs_IniHandler();
		$this->LDAP_EXTERNAL_AUTH=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/SquidExternLDAPAUTH'));
		if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
			$ini->loadString($this->ArticaSquidParameters);
			if(isset($ini->_params)){
				$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;
			}
		}else{
			$ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
		}

	}
	
	private function debugOutput($text,$function=null,$line=null){
		$this->DEBUG[]="# -- ACLS:$function::$text [$line]";
		if(!$GLOBALS["AS_ROOT"]){return;}

		if(!is_numeric($line)){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$function=$trace[1]["function"];
					$line=$trace[1]["line"];
					
				}
			}
		}
        $this->HLOGS[]="# DEBUG ACLS:$function::$text [$line]";
        if(!$GLOBALS["VERBOSE"]){return;}
		echo "DEBUG ACLS:$function::$text [$line]\n";
	}

	function isTransparentPort():bool{

	    $f=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
	    foreach ($f as $line){
	       $line=trim($line);
	       if($line==null){continue;}
	       if(substr($line,0,1)=="#"){continue;}
	       if(preg_match("#^acl.*?MyTransparentPort#",$line)){return true;}
        }
	    return false;

    }
	
	function buildacls_order($aclport=0,$aclgpid=0):string{
        $HaClusterClient    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
		$mysql_for_port     = null;
		$MASTER_PORT        = 0;
		$function           = __FUNCTION__;
		$file               = basename(__FILE__);
		$logext             = "$file/$function";
        $conf               = array();
		$q                  = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $HEADS              = array();
        $conf[]="#\t buildacls_order($aclport,$aclgpid)";

		if(!is_numeric($aclport)){$aclport=0;}
		if(!is_numeric($aclgpid)){$aclgpid=0;}

        $conf[]="#\tHaClusterClient=$HaClusterClient $logext";
		
		if($aclport>0){
            $conf[]="# Add aclport=$aclport in query";
		    $mysql_for_port=" AND webfilters_sqacls.aclport=$aclport"; }

		if($aclgpid>0){
		    $ligneMaster=$q->mysqli_fetch_array("SELECT aclport FROM webfilters_sqacls WHERE ID=$aclgpid");
            $MASTER_PORT=$ligneMaster["aclport"];
        }

        $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
		$sql="SELECT 
		webfilters_sqacls.ID,
		webfilters_sqacls.aclname,
		webfilters_sqacls.zTemplate,
		webfilters_sqacls.PortDirection,
		webfilters_sqacls.xORDER,
		webfilters_sqacls.aclport,
		webfilters_sqacls.aclgroup
		FROM webfilters_sqacls 
		WHERE webfilters_sqacls.enabled=1$mysql_for_port
		AND webfilters_sqacls.aclgpid=$aclgpid
		ORDER BY webfilters_sqacls.xORDER";
        $conf[]="#\tmysql_for_port='$mysql_for_port' aclgpid=$aclgpid [L.".__LINE__."]";
		$this->debugOutput("$sql", __FUNCTION__, __LINE__);
        $PrefixAcls=null;
		$results = $q->QUERY_SQL($sql);

        if($SquidUrgency==1) {
            $conf[]="# $function * * * * THe Proxy is on Emergency * * * * [L.".__LINE__."]";
            return @implode("\n",$conf);
        }

		if(!$q->ok){
			$conf[]="# $function MySQL ERROR ! [L.".__LINE__."]";
		}
		if($aclport==0){
            $PrefixAcls="";
			if($this->SquidBubbleMode==1){$PrefixAcls=" MyStandPort ";}
		}
		if($aclport>0){ $PrefixAcls=" MyPort$aclport "; }

        if($HaClusterClient==1){
            $PortDirectionS[500]=" HaClusterT ";
        }
		$PortDirectionS[1]=" NormalPorts ";

        if($this->isTransparentPort()) {
            $PortDirectionS[2] = " MyTransparentPort ";
        }
		$PortDirectionS[3]=" MyMobilesPort ";

		$ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$resultsPorts = $ql->QUERY_SQL("SELECT ID FROM proxy_ports WHERE enabled=1");
		foreach ($resultsPorts as $index=>$lignePorts){
			$ID=$lignePorts["ID"];
			$PortDirectionS[$ID]=" MyPort$ID ";
		}


		$conf[]="#\t[". count($results)."] rules [".__LINE__."]";
        $conf[]=" ";
        $conf[]=" ";
		
		foreach ($results as $index=>$ligne) {
			$PrefixAclsStandard=null;
            $PrefixAcls=null;
            $annotate_transaction=null;
            $annotate_final=null;
			$PortDirection=$ligne["aclport"];
			if($MASTER_PORT>0){$PortDirection=$MASTER_PORT;}
			$aclid=$ligne["ID"];
            if($this->isSquid5) {
                $annotate_transaction = " AnnotateRule$aclid";
                $annotate_final= "acl AnnotateRule$aclid annotate_transaction accessrule=Rule$aclid";
            }


            if(isset($PortDirectionS[$PortDirection])){$PrefixAcls=$PortDirectionS[$PortDirection];}

			if($PrefixAcls==null){
				if($PortDirection>0){
					if($PortDirection==2){if($this->isTransParent()==0){continue;}}
					if($PortDirection==3){if($this->smartphones_port==0){continue;}}
                    if(!isset($PortDirectionS[$PortDirection])){$conf[]="# webfilters_sqacls #$aclid : $PortDirection no matches PortDirections  [".__LINE__."] [".basename(__FILE__)."]"; }
					if(isset($PortDirectionS[$PortDirection])){ $PrefixAclsStandard=$PortDirectionS[$PortDirection]; }
				}
			}
			$ligneWhat=$q->mysqli_fetch_array("SELECT httpaccess,httpaccess_value,httpaccess_data 
					FROM webfilters_sqaclaccess WHERE aclid='$aclid'");
			if(!$q->ok){
                $conf[] = "# MySQL Error ! ".__LINE__." - ".basename(__FILE__);
            }
			

			$httpaccess=$ligneWhat["httpaccess"];
			$aclgroup=intval($ligne["aclgroup"]);

			if($aclgroup==1){
                $conf[]="# SquidUrgency = $SquidUrgency [".__LINE__."] - " . basename(__FILE__);
                if($SquidUrgency==0) {
                    $conf[] = "#";
                    $conf[] = "#-------- ACL Group `{$ligne["aclname"]}` [" . __LINE__ . "]";
                    $aclport = 0;
                    $conf[] = $this->buildacls_order($aclport, $aclid);
                    $conf[] = "#----------------------------------------";
                    $conf[] = "#";
                }
				continue;
			}
			
			$firstToken=null;
			if($httpaccess==null){
				$conf[]="# $aclid httpaccess is null, aborting";
				continue;
			}
			if($httpaccess=="tcp_outgoing_tos"){continue;}
			if($httpaccess=="reply_body_max_size"){continue;}
			if($httpaccess=="url_rewrite_access_deny"){continue;}
			if($httpaccess=="url_rewrite_access_allow"){continue;}
			if($httpaccess=="adaptation_access_deny"){continue;}
			if($httpaccess=="delay_access"){continue;}
			if($httpaccess=="tcp_outgoing_address"){continue;}
			if($httpaccess=="cache_parent"){continue;}
			if($httpaccess=="snmp_access_allow"){continue;}
			if($httpaccess=="log_access"){continue;}
			if($httpaccess=="request_header_add"){continue;}
			if($httpaccess=="deny_log"){continue;}
			if($httpaccess=="always_direct"){continue;}
			
			$reverse=false;
			
			//$conf[]="# [L.".__LINE__."]: rule id: $aclid $httpaccess Port Direction=$PortDirection ($PrefixAclsStandard)";
			
			if($httpaccess=="deny_access_except"){$reverse=true;$firstToken="http_access deny$PrefixAclsStandard$PrefixAcls";}
			if($httpaccess=="access_allow"){$firstToken="http_access allow$PrefixAclsStandard$PrefixAcls";}
			if($httpaccess=="access_deny"){
                $sslcrtd_disable_bump_error_page=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_disable_bump_error_page"));
                if(intval($sslcrtd_disable_bump_error_page)==1){
                    $firstToken="http_access deny HTTP$PrefixAclsStandard$PrefixAcls";
                }
                else{
                    $firstToken="http_access deny$PrefixAclsStandard$PrefixAcls";
                }

			}
			if($httpaccess=="cache_deny"){$firstToken="cache deny$PrefixAclsStandard$PrefixAcls";}
			if($httpaccess=="http_reply_access_deny"){$firstToken="http_reply_access deny$PrefixAclsStandard$PrefixAcls";}
			if($httpaccess=="http_reply_access_allow"){$firstToken="http_reply_access allow$PrefixAclsStandard$PrefixAcls";}
			
			
			
			if($httpaccess=="deny_quota_rule"){
                continue;
			}

            //$conf[]="# [L.".__LINE__."]: ".basename(__FILE__)." buildacls_bytype_items($aclid,..)";
			$groups=$this->buildacls_bytype_items($aclid,$reverse);
			$this->debugOutput("Checks: ACL $aclid Access type:`$httpaccess` ($firstToken) groups:". count($groups),__FUNCTION__,__LINE__);
			if(count($groups)==0){
                $conf[]="# [L.".__LINE__."]: ".basename(__FILE__)." SKIP! NO GROUP FOR THIS RULE !!";
				continue;
			}
			$FINAL="$firstToken ".@implode(" ", $groups).$annotate_transaction;
			$FINAL=str_replace("!!", "!", $FINAL);
            if(!is_null($ligne["zTemplate"])) {
                if (strlen($ligne["zTemplate"]) > 2) {
                    $zTemplate = unserialize(base64_decode($ligne["zTemplate"]));
                    if (!is_array($zTemplate)) {
                        $zTemplate = array();
                    }
                    if (!isset($zTemplate["ENABLE"])) {
                        $zTemplate["ENABLE"] = 0;
                    }
                    if (!isset($zTemplate["ENABLE_ERROR_PAGE"])) {
                        $zTemplate["ENABLE_ERROR_PAGE"] = 0;
                    }
                    if (!isset($zTemplate["FREE_REDIRECT"])) {
                        $zTemplate["FREE_REDIRECT"] = 0;
                    }
                    if (!isset($zTemplate["FREE_REDIRECT_URL"])) {
                        $zTemplate["FREE_REDIRECT_URL"] = "";
                    }
                    if (!isset($zTemplate["FREE_REDIRECT_JAVASCRIPT"])) {
                        $zTemplate["FREE_REDIRECT_JAVASCRIPT"] = 0;
                    }



                    if ($zTemplate["FREE_REDIRECT"] == 1) {
                        $zTemplate["ENABLE"] = 0;
                        $zTemplate["ENABLE_ERROR_PAGE"] = 0;
                    }

                    if ($zTemplate["ENABLE"] == 1) {
                        $conf[] = "# Template Enabled for this ACL.";
                        $conf[] = $this->Templatev2($aclid, $groups, $zTemplate);
                    }
                    if ($zTemplate["ENABLE_ERROR_PAGE"] == 1) {
                        $conf[] = "# Template Enabled for this ACL.";
                        $conf[] = $this->Templatev2_error_page($aclid, $groups);
                    }
                    if ($zTemplate["FREE_REDIRECT"] == 1) {
                        if (strlen($zTemplate["FREE_REDIRECT_URL"]) > 6) {
                            $conf[] = "# Template Enabled for this ACL.";
                            $conf[] = $this->Templatev2_redirects($aclid, $groups, $zTemplate["FREE_REDIRECT_URL"],$zTemplate["FREE_REDIRECT_JAVASCRIPT"]);
                        }
                    }


                }
            }
			if($annotate_final<>null){
			    $conf[]=$annotate_final;
            }
            if($aclgpid>0){
                $HEADS["STATUS_RULES"][$aclgpid]=1;
            }
            $HEADS["STATUS_RULES"][$aclid]=1;
            $conf[]=$FINAL;

			
			
		}

        $FF[]="#\tSTATUS=[".base64_encode(serialize($HEADS))."]";
        $FF[]=@implode("\n", $conf);
		return @implode("\n", $FF);
		
	}


	private function Templatev2($aclid,$groups,$zTemplate):string{
        $TemplateDir="/usr/share/squid-langpack/templates";
	    $TEMPLATE_ID=$zTemplate["TEMPLATE_ID"];
	    $BODY=$zTemplate["BODY"];
	    $TITLE=$zTemplate["TITLE"];
	    $tpl=new template_simple($TEMPLATE_ID);
	    $TEMPLATE_FINAL=$tpl->buildTemplate($TEMPLATE_ID,$TITLE,$BODY);
        @file_put_contents("$TemplateDir/TEMPLATE_$aclid",$TEMPLATE_FINAL);
        $FINAL_GRP=null;
        foreach ($groups as $grp){
            $grp=trim($grp);
            if($grp==null){continue;}
            if($grp=="all"){continue;}
            $FINAL_GRP=$grp;
        }

        $conf[]="# Final acl is \"$FINAL_GRP\", Template ID=$TEMPLATE_ID";
        if(strlen($FINAL_GRP)==0){
            return @implode("\n",$conf);
        }


        $conf[]="deny_info TEMPLATE_$aclid $FINAL_GRP";
        return @implode("\n",$conf);

    }

    private function Templatev2_error_page($aclid,$groups):string{
        $UfdbUseInternalService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"));
        if($UfdbUseInternalService==0){return "";}

        if($this->isSquid5){
            $conf[]="# Squid5 Final acl is AnnotateRule$aclid";
            $url=$this->ufdbweb_url($aclid);
            $conf[]="deny_info 302:$url AnnotateRule$aclid";
            return @implode("\n",$conf);
        }
        $FINAL_GRP="";
        foreach ($groups as $grp){
            $grp=trim($grp);
            if($grp==null){continue;}
            if($grp=="all"){continue;}
            $FINAL_GRP=$grp;
        }
        $conf[]="# Final acl is \"$FINAL_GRP\"";
        if(strlen($FINAL_GRP)==0){
            return @implode("\n",$conf);
        }
        $url=$this->ufdbweb_url($aclid);
        $conf[]="deny_info 302:$url $FINAL_GRP";
        return @implode("\n",$conf);
    }

    private function Templatev2_redirects_parseurl($aclid,$url):string{

        if(!preg_match("#^http.*?#",$url)){
            $url="http://$url";
        }
        $path=null;
        $port=null;
        $main=parse_url($url);
        $scheme=$main["scheme"];
        $host=$main["host"];
        if(isset($main["path"])){
            $path=$main["path"];
            if($path=="/"){$path=null;}
        }
        if(isset($main["port"])){
            $port=":".$main["port"];
        }
        if(isset($main["query"])){
            return $url;
        }
        if(is_null($path)){$path="/";}

        return "$scheme://$host$port$path?clientuser=%a&clientaddr=%i&aclid=$aclid&url=%u&err=%E&errordesc=%E&domain=%H&ename=%x";
    }

    private function Templatev2_javascript($aclid,$url):string{
        $TemplateDir="/usr/share/squid-langpack/templates";
        if(!is_dir($TemplateDir)) {
            mkdir($TemplateDir, 0755);
        }
        $CurrDir=dirname(__FILE__);
        if(is_file("/usr/share/artica-postfix/css/tailwin.css")) {
            $TailWin = @file_get_contents("/usr/share/artica-postfix/css/tailwin.css");
        }else{
            $TailWin = "alert('artica-postfix/css/tailwin.css not found');";
        }

        $TEMPLATE_FINAL=@file_get_contents("$CurrDir/databases/deny_info.javascript.tpl");
        $TEMPLATE_FINAL=str_replace("__TAIL_WIN__",$TailWin,$TEMPLATE_FINAL);
        $TEMPLATE_FINAL=str_replace("__REDIRECT__",$url,$TEMPLATE_FINAL);
        @file_put_contents("$TemplateDir/TEMPLATE_$aclid",$TEMPLATE_FINAL);
        $conf[]="# Templatev2_javascript Final acl is AnnotateRule$aclid";
        $conf[]="deny_info TEMPLATE_$aclid AnnotateRule$aclid";
        return @implode("\n",$conf);
    }

    private function Templatev2_redirects($aclid,$groups,$url,$javascript=0):string{
        $UseJavascript=intval($javascript);
        $url=$this->Templatev2_redirects_parseurl($aclid,$url);
        if($this->isSquid5) {
            if ($UseJavascript == 1) {
                return $this->Templatev2_javascript($aclid, $url);
            }
        }


        if($this->isSquid5){
            $conf[]="# Squid5 Final acl is AnnotateRule$aclid";
            $conf[]="deny_info 302:$url AnnotateRule$aclid";
            return @implode("\n",$conf);
        }
        $FINAL_GRP="";
        foreach ($groups as $grp){
            $grp=trim($grp);
            if($grp==null){continue;}
            if($grp=="all"){continue;}
            $FINAL_GRP=$grp;
        }
        $conf[]="# Final acl is \"$FINAL_GRP\"";
        if(strlen($FINAL_GRP)==0){
            return @implode("\n",$conf);
        }
        $conf[]="deny_info 302:$url $FINAL_GRP";
        return @implode("\n",$conf);
    }

    private function ufdbweb_url($aclid):string{
        $proto="http";
        $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
        $EnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceEnableSSL"));
        $Hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHostname"));
        $Certificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceCertificate"));
        $HTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
        if($HTTPPort==0){$HTTPPort=9025;}
        $HTTPSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPSPort"));
        if($HTTPSPort==0){$HTTPSPort=9026;}

        if($Certificate==null){$EnableSSL=0;}
        if($EnableSSL==1) {
            $proto = "https";
            $HTTPPort=$HTTPSPort;
        }
        if($EnableNginx==1){
            $proto = "http";
            $HTTPPort=80;
        }
        $url="$proto://$Hostname:$HTTPPort/webfilter?clientuser=%a&clientaddr=%i&aclid=$aclid&url=%u&err=%E&errordesc=%E&domain=%H&ename=%x";
        return $url;

    }
	

	public function external_acl_helper_quota_builder(){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$sql="SELECT * FROM webfilters_quotas WHERE enabled=1";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){return;}
		foreach ($results as $index=>$ligne) {
			$array[$ligne["xtype"]]["MAX"]=$ligne["maxquota"];
			$array[$ligne["xtype"]]["DUR"]=$ligne["duration"];
			$array[$ligne["xtype"]]["PATTERN"]=$ligne["value"];
			$DESTINATIONS=$this->external_acl_helper_quota_builder_dest($ligne["ID"]);
			$array[$ligne["xtype"]]["DESTINATIONS"]=$DESTINATIONS;
			
		}
		
		
		@file_put_contents("/etc/squid3/quotas_artica.db", serialize($array));
		@chmod("/etc/squid3/quotas_artica.db",0755);
		@chown("/etc/squid3/quotas_artica.db","squid");
		
	}
	
	private function external_acl_helper_quota_builder_dest($RULEID){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		
		$sql="SELECT webfilters_quotas_grp.gpid,
		webfilters_quotas_grp.zmd5, webfilters_quotas_grp.ID as LINKID,
		webfilters_sqgroups.* FROM webfilters_quotas_grp, webfilters_sqgroups
		WHERE webfilters_quotas_grp.gpid=webfilters_sqgroups.ID 
		AND webfilters_sqgroups.enabled =1
		AND webfilters_quotas_grp.ruleid=$RULEID";
		
		
		$results = $q->QUERY_SQL($sql);
		foreach ($results as $index=>$ligne) {
			$items=$this->ItemsInArray($ligne["gpid"]);
			$array[$ligne["GroupType"]]=$this->ItemsInArray($ligne["gpid"]);
			
		}
		
		return $array;
		
	}
	
	private function ItemsInArray($gpid){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
		$results = $q->QUERY_SQL($sql);
		foreach ($results as $index=>$ligne) {
			$array[$ligne["pattern"]]=$ligne["pattern"];
			
		}
		return $array;
	}


	function DUPLICATE_ACLS_OBJECTS($TableLink=null,$ID_SRC=0,$ID_DEST=0){
        if($TableLink==null){$TableLink="webfilters_sqacllinks";}
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql="SELECT {$TableLink}.gpid,{$TableLink}.negation,
        {$TableLink}.zOrder,{$TableLink}.zmd5 as mkey,
        webfilters_sqgroups.* FROM {$TableLink},webfilters_sqgroups
        WHERE {$TableLink}.gpid=webfilters_sqgroups.ID
        AND {$TableLink}.aclid=$ID_SRC
        ORDER BY {$TableLink}.zOrder";

        $results=$q->QUERY_SQL($sql);

        foreach ($results as $index=>$ligne){
            $gpid=$ligne["gpid"];
            $negation=$ligne["negation"];
            $zOrder=$ligne["zOrder"];
            $md5=md5($ID_DEST.$gpid);
            $sql="INSERT OR IGNORE INTO {$TableLink} (zmd5,negation,aclid,gpid,zOrder) VALUES('$md5','$negation','$ID_DEST','$gpid',$zOrder)";
            $q->QUERY_SQL($sql);
        }



    }

	
	function buildacls_bytype($ruletype,$aclport=0){
		$this->buildacls_bytype=array();
		$this->error=null;
		$LOCALSIPS=array();
		if($ruletype=="tcp_outgoing_address"){
			if($this->ASROOT){
				if(class_exists("unix")){
					$unix=new unix();
					$LOCALSIPS=$unix->NETWORK_ALL_INTERFACES(true);
					
				}
			}
		}
		
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$PortDirectionS[1]=" NormalPorts";
        if($this->isTransparentPort()) {
            $PortDirectionS[2] = " MyTransparentPort ";
        }
		$PortDirectionS[3]=" MyMobilesPort ";
		$ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$resultsPorts = $ql->QUERY_SQL("SELECT ID FROM proxy_ports WHERE enabled=1");
		foreach ($resultsPorts as $index=>$lignePorts){
			$ID=$lignePorts["ID"];
			$PortDirectionS[$ID]=" MyPort$ID ";
		}

        $PortDirectionS[500]=" HaClusterT";
		
		
		if(isset($GLOBALS["buildacls_bytype$ruletype$aclport"])){return $GLOBALS["buildacls_bytype$ruletype$aclport"];}
		
		if($GLOBALS["VERBOSE"]){echo "######### $ruletype:$aclport #########\n";}
		
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ACLS: Find `$ruletype`\n";}
		$rulesAcls=array();
		$sql="SELECT webfilters_sqacls.ID,
		webfilters_sqacls.aclport, 
		webfilters_sqacls.xORDER,
		webfilters_sqacls.PortDirection,
		webfilters_sqaclaccess.httpaccess_data
		FROM webfilters_sqacls, webfilters_sqaclaccess 
		WHERE webfilters_sqacls.ID=webfilters_sqaclaccess.aclid
		AND webfilters_sqacls.enabled=1
		AND webfilters_sqacls.aclport=$aclport
		AND webfilters_sqaclaccess.httpaccess='$ruletype' 
		ORDER BY webfilters_sqacls.xORDER";
		
		
		$reverse=false;
		
		if($ruletype=="deny_access_except"){$reverse=true;}
		$this->debugOutput("$ruletype:$aclport MySQL AND webfilters_sqacls.aclport=$aclport",__FUNCTION__,__LINE__);
		
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){if(preg_match("#Error Table.*?doesn't exist#",$q->mysql_error)){$q->CheckTables();$results = $q->QUERY_SQL($sql);}}
		if(!$q->ok){$this->error=str_replace("\n"," ",$q->mysql_error);}
		
		if(count($results)==0){
			$this->buildacls_bytype_logs[]="# $ruletype:$aclport no item";
			$this->debugOutput("$ruletype:$aclport MySQL > ABORTING ! NO items",__FUNCTION__,__LINE__);
			$this->error="MySQL:No item";
		}
		
		$mysql_num_rows=count($results);
		$this->debugOutput("$ruletype:$aclport MySQL > $mysql_num_rows items",__FUNCTION__,__LINE__);
		
		$this->buildacls_bytype_logs[]="# $ruletype/$aclport $mysql_num_rows items";
		
		foreach ($results as $index=>$ligne) {
			$MainRuleID=$ligne["ID"];
			
			$AddPort=true;
			$PrefixAclsPort=null;
			if($this->SquidBubbleMode==0){
				$PortDirection=$ligne["PortDirection"];
				if($PortDirection>0){
					if($PortDirection==2){if($this->isTransParent()==0){continue;}}
					if(isset($PortDirectionS[$PortDirection])){
						$PrefixAclsPort=$PortDirectionS[$PortDirection];
					}
				}	
			}
			
			$aclid=$ligne["ID"];
			
			
			
			$this->debugOutput("$ruletype:$aclport RuleID=$MainRuleID buildacls_bytype_items($aclid,$reverse)",__FUNCTION__,__LINE__);
			$groups=$this->buildacls_bytype_items($aclid,$reverse,"webfilters_sqacllinks",$ruletype);
			if(count($groups)==0){
				$this->buildacls_bytype_logs[]="# $ruletype:$aclport RuleID=$MainRuleID groups=0 (aborting)";
				$this->debugOutput("$ruletype:$aclport No groups..");
				$this->error=$this->error." $aclid=0;";
				continue;
			}
			$valueToAdd=null;
			
			
			if($ruletype=="tcp_outgoing_tos"){
				$valueToAdd=$ligne["httpaccess_data"];
				if($valueToAdd==null){continue;}
				if(strpos($valueToAdd, "x")==0){
					$ayDscp = array(0 => '0x00',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
					$valueToAdd=$ayDscp[$valueToAdd];
				}
				
				$valueToAdd=$valueToAdd." ";
			}


			if($ruletype=="reply_body_max_size"){
				$valueToAdd=intval($ligne["httpaccess_data"]);
				if($valueToAdd==0){continue;}
				$valueToAdd=$valueToAdd." MB ";
			}
            if($ruletype=="tcp_outgoing_address"){
				$valueToAdd=$ligne["httpaccess_data"];
				if($GLOBALS["VERBOSE"]){echo "tcp_outgoing_address \"$valueToAdd\" PORT:{$aclport}\n";}
				if($valueToAdd==null){continue;}
				if(count($LOCALSIPS)>0){
					if(preg_match("#[0-9\.]+#", $valueToAdd)){
						$valueToAdd=trim($valueToAdd);
						if(!isset($LOCALSIPS[$valueToAdd])){
							$GLOBALS["tcp_outgoing_address_errors"][]="Error tcp_outgoing_address $valueToAdd NO SUCH ADDRESS";
							if($GLOBALS["VERBOSE"]){echo "tcp_outgoing_address \"$valueToAdd\" PORT:{$aclport} NO SUCH ADDRESS !!!\n";}
							continue;
						}
					}
				}else{
					$GLOBALS["tcp_outgoing_address_errors"][]="LOCALSIPS Array = 0";
				}
				
				
				$valueToAdd=$valueToAdd." ";
			}
			

			
			if($ruletype=="request_header_add"){
				$httpaccess_data=unserialize(base64_decode($ligne["httpaccess_data"]));
				$request_header_add_name=$httpaccess_data["header_name"];
				$request_header_add_value=$httpaccess_data["header_value"];
				if(trim($request_header_add_name)==null){continue;}
				if(trim($request_header_add_value)==null){continue;}				
				$valueToAdd="$request_header_add_name \"$request_header_add_value\" ";
				
			}
			
			if($ruletype=="log_access"){
				$valueToAdd="stdio:/var/log/squid/access_acl_$aclid.csv csv_acls ";
			}	
				
			$FINAL=@implode(" ", $groups);
			$FINAL=str_replace("!!", "!", $FINAL);
			$rulesAcls[]=$valueToAdd.$PrefixAclsPort." ".$FINAL;
			
			
		}
		
		$GLOBALS["buildacls_bytype$ruletype"]=$rulesAcls;
		return $rulesAcls;
	}
	
	public function buildacls_bytype_items($aclid=null,$reverse=false,$tablelink="webfilters_sqacllinks",$RuleType=null){
		$KSRNAsACls                 = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNAsACls"));
		$NoAuthPorts=$this->NoAuthPorts();
		$this->DEBUG=array();
		$prefix=null;
		$zOrder="zOrder";
		if($reverse){$prefix="!";}
		$acls_name=array();
		
		$this->debugOutput("$aclid: $tablelink", __FUNCTION__, __LINE__);
		
		if(!is_numeric($aclid)){
			
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$function=$trace[1]["function"];
					$line=$trace[1]["line"];
						
				}
			}
			
			$this->debugOutput("$aclid: no aclid as been sent function:$function() in $line", __FUNCTION__, __LINE__);
		}
		
		
		if($this->AsMeta){
			$tablelink="meta_webfilters_sqacllinks";
			$zOrder="zorder";
		}
		
		$sql="SELECT 
		$tablelink.gpid,
		$tablelink.negation,
		$tablelink.zOrder,
		webfilters_sqgroups.GroupType,
		webfilters_sqgroups.GroupName,
		webfilters_sqgroups.params,
		webfilters_sqgroups.ID 
		FROM $tablelink,webfilters_sqgroups 
		WHERE $tablelink.gpid=webfilters_sqgroups.ID 
		AND $tablelink.aclid=$aclid
		AND webfilters_sqgroups.enabled=1
		ORDER BY $tablelink.$zOrder";
		
		$this->debugOutput("$aclid: $tablelink", __FUNCTION__, __LINE__);
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$qlProxy=new mysql_squid_builder();
        $results = $q->QUERY_SQL($sql);
		
		if(!$q->ok){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$function=$trace[1]["function"];
					$line=$trace[1]["line"];
				}
			}
			$this->debugOutput("$aclid: MySQL Error",__FUNCTION__,__LINE__);
			echo "Sent by function:$function() in $line -> FATAL !!! $q->mysql_error ".__FUNCTION__."() in line: ".__LINE__."\n";
			echo "$sql\n";
		}
		
		$this->debugOutput("$aclid: ". count($results)." items", __FUNCTION__, __LINE__);
		
		$ARRAY_AUTH["radius_auth"]=true;
		$ARRAY_AUTH["ad_auth"]=true;
		$ARRAY_AUTH["ldap_auth"]=true;
		$ARRAY_AUTH["dynamic_acls"]=true;
		$ISAUTH=false;
		$GroupSuffix="Group";
		if($this->AsMeta){$GroupSuffix="MetGroup";}
		$squid_acls=new squid_acls();
		foreach ($results as $index=>$ligne) {
			$GroupType=$ligne["GroupType"];
			$GroupName=$ligne["GroupName"];
			$this->debugOutput("$aclid: GroupName: `$GroupType` ID:{$ligne['ID']}",__FUNCTION__,__LINE__);
			$this->buildacls_bytype_logs[]="# GroupName: `$GroupType` ID:{$ligne['ID']}";
			if($squid_acls->NotUsedGroup($ligne['ID'])){
                $this->buildacls_bytype_logs[]="# GroupName: `$GroupType` Not used";
				$this->debugOutput("$aclid: GroupName: `$GroupType` ID:{$ligne['ID']} -> NotUsedGroup aborting",__FUNCTION__,__LINE__);
				continue;}
			$neg=null;
			
			$SuperPrefix=$prefix;
			$this->debugOutput("$aclid $GroupName: `$GroupType` ID:{$ligne['ID']}", __FUNCTION__, __LINE__);
			if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $GroupType,$re)){
				$this->debugOutput("$aclid: $GroupName / $GroupType NO ITEM", __FUNCTION__, __LINE__);
				$qlProxy->acl_ARRAY_NO_ITEM[$GroupType]=true;
			}
			
			
			if($GroupType=="dynamic_acls"){
				if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){continue;}
			}
			
			
			if($GroupType=="ad_auth"){
				if($this->EnableKerbAuth==0){continue;}
			}

			if($RuleType=="always_direct"){
				if($GroupType=="proxy_auth"){
					$this->debugOutput("$aclid: $GroupName / $GroupType / $RuleType --> Incompatible !", __FUNCTION__, __LINE__);
					continue;
				}
			}

            if($GroupType=="the_shields"){
                if($KSRNAsACls==0){continue;}
                if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
                $acls_name[]="$neg{$prefix}krsn";
                continue;
            }
	
			if($GroupType=="proxy_auth_adou"){
                if($this->EnableKerbAuth==0){continue;}
                $ISAUTH=true;
                if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
                $acls_name[]="$neg{$prefix}Group{$ligne["ID"]}";
                continue;
            }
			
			if($GroupType=="proxy_auth_statad"){
                if($this->EnableKerbAuth==0){continue;}
                if($this->UseDynamicGroupsAcls==0){continue;}
				$ISAUTH=true;
				if($ligne["negation"]==1){$neg="!";}
				$acls_name[]="$neg{$prefix}Group{$ligne["ID"]}";
				continue;
			}

			if($GroupType=="ldap_group"){
                $SquidStandardLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
                if($SquidStandardLDAPAuth==0){continue;}
                $ISAUTH=true;
                if($ligne["negation"]==1){$neg="!";}
                $acls_name[]="$neg{$prefix}Group{$ligne["ID"]}";
                continue;
            }
			
			if($GroupType=="proxy_auth_tagad"){
				if($this->EnableKerbAuth==0){continue;}
				if($this->UseDynamicGroupsAcls==0){continue;}
				$ISAUTH=true;
				if($ligne["negation"]==1){$neg="!";}
				$acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
				continue;
				
			}
            if($GroupType=="proxy_auth_multiad"){
                if($this->EnableKerbAuth==0){continue;}
                $ISAUTH=true;
                if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
                $acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
                continue;
            }

            if($GroupType=="proxy_auth_ads"){
                if($this->EnableKerbAuth==0){continue;}
                if($this->UseDynamicGroupsAcls==0){continue;}
				$ISAUTH=true;
				if($ligne["negation"]==1){$neg="!";}
				$acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
				continue;
			}

            if($GroupType=="proxy_auth_authenticated"){
                if($this->EnableKerbAuth==0){continue;}
                $ISAUTH=true;
                if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
                $acls_name[]="{$neg}AUTHENTICATED";
                continue;
            }
			
			if($GroupType=="proxy_auth_extad"){
				// depreciated
				continue;
				
			}

			if($GroupType=="categories"){
                if($ligne["negation"]==1){$neg="!";}
				$acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
				continue;
			}
            if($GroupType=="accessrule"){
                $this->buildacls_bytype_logs[]="#$aclid: accessrule -> ";
                if($ligne["negation"]==1){$neg="!";}
                $acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
                continue;
            }

            if($GroupType=="url_db"){
                if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
                    $acls_name[]="$aclid: License error";
                    continue;
                }
                if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
                $acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
                continue;
            }

			if(isset($ARRAY_AUTH[$GroupType])){
				$ISAUTH=true;
				if($ligne["negation"]==1){$neg="!";}
				$acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
				continue;
			}
			
			

			if($GroupType=="all"){
				if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
				$this->debugOutput("$aclid: all -> {$neg}{$prefix}all", __FUNCTION__, __LINE__);
				$acls_name[]="$neg{$prefix}all";
				continue;
			}	

			if(isset($qlProxy->acl_ARRAY_NO_ITEM[$GroupType])){
				if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
				$acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
				continue;
				
			}

			if($GroupType=="NudityScan"){continue;}

			if($GroupType=="proxy_auth"){
				if($this->AUTH==0){
					$this->buildacls_bytype_logs[]="#Group{$ligne['ID']} $GroupType BUT AUTH = 0";
					continue;
				}
				$acls=new squid_acls();
				$fCountArray=$acls->GetItems($ligne['ID'],null,$this->AsMeta);
				$items=count($fCountArray);
				if($items==0){
					$this->buildacls_bytype_logs[]="#Group{$ligne['ID']} $GroupType NO ITEM! Aborting";
					continue;
				}
				$this->buildacls_bytype_logs[]="#Group{$ligne['ID']} $GroupType $items item(s)";
				if($ligne["negation"]==1){$neg="!";}
				$acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
				$ISAUTH=true;
				continue;
			}
			
			
			if($GroupType=="ssl_sni"){
				if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
                $this->buildacls_bytype_logs[]="#$aclid: SNIGroup -> ";
				$acls_name[]="$neg{$prefix}SNIGroup{$ligne["ID"]}";
				continue;
			}

            if($GroupType=="ssl_sni_regex"){
                if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
                $this->buildacls_bytype_logs[]="#$aclid: SNIGroup (regex) -> ";
                $acls_name[]="$neg{$prefix}SNIGroup{$ligne["ID"]}";
                continue;
            }
            if($GroupType=="AclsGroup"){
                if($ligne["negation"]==1){$neg="!";$SuperPrefix=null;}
                $this->buildacls_bytype_logs[]="#$aclid: - AclsGroup OK";
                $acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
                continue;
            }

            $acls=new squid_acls();
			$fCountArray=$acls->GetItems($ligne['ID'],null,$this->AsMeta);
			foreach ($acls->buildacls_bytype_logs as $line){
                $this->buildacls_bytype_logs[]=$line;
            }
			$items=count($fCountArray);
            $this->buildacls_bytype_logs[]="# {$ligne['ID']} GetItems: $items L.". __LINE__;
			
			$this->debugOutput("$aclid `$GroupType` ID:GetItems({$ligne['ID']}) $items items", __FUNCTION__, __LINE__);
			
			
			
			if($items==0){
				$this->debugOutput("$aclid:GroupID {$ligne['ID']} 0 items, STOP", __FUNCTION__, __LINE__);
				continue;
			}
			
			if($ligne["negation"]==1){$neg="!";}
			$acls_name[]="$neg$prefix$GroupSuffix{$ligne["ID"]}";
			
		}	
		
		
		if($NoAuthPorts<>null){
			if($ISAUTH){
				array_unshift($acls_name, $NoAuthPorts);
			}
		}
		$this->buildacls_bytype_logs[]="# $aclid:". count($acls_name)." final items";
		$this->debugOutput("$aclid:". count($acls_name)." final items", __FUNCTION__, __LINE__);
		return $acls_name;
	}
	
	private function NoAuthPorts(){
		if(isset($GLOBALS["NoAuthPorts"])){return $GLOBALS["NoAuthPorts"];}
        $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
        $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

        if($HaClusterClient==1) {
            if ($HaClusterTransParentMode == 1) {
                $GLOBALS["NoAuthPorts"] = "!NoAuthPort";
                return $GLOBALS["NoAuthPorts"];
            }
        }


		$sql="SELECT COUNT(*) as TCOUNT FROM proxy_ports WHERE enabled=1 and NoAuth=1";
		$ligne=$q->mysqli_fetch_array($sql);
		if(intval($ligne["TCOUNT"])==0){
			$GLOBALS["NoAuthPorts"]=null;
			return null;
		}
		$GLOBALS["NoAuthPorts"]= "!NoAuthPort";
		return $GLOBALS["NoAuthPorts"];
	}
	
	private function acl_tpl($aclid){
		
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		if(!$q->FIELD_EXISTS("webfilters_sqgroups", "tplreset")){$q->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `tplreset` smallint(1) ,ADD INDEX ( `tplreset` ) ");}
		$sql="SELECT acltpl,tplreset FROM webfilters_sqgroups WHERE ID='$aclid'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		
		if(intval($ligne["tplreset"])==1){return "TCP_RESET";}
		if($ligne["acltpl"]==null){return;}
		if($ligne["acltpl"]=="ARTICA_SLASH_SCREEN"){return;}
		$acltpl=$ligne["acltpl"];
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$sql="SELECT template_name,lang FROM squidtpls WHERE `zmd5`='$acltpl'";
		if(is_numeric($ligne["lang"])){$ligne["lang"]="en";}
		$ligne=$q->mysqli_fetch_array($sql);
		if(!preg_match("#ERR_.+#", $ligne["template_name"])){$ligne["template_name"]="ERR_".$ligne["template_name"];}
		$template_path="/usr/share/squid-langpack/templates/{$ligne["lang"]}/{$ligne["template_name"]}";
		if(!is_file($template_path)){return;}
		
	}
	
	
	function GroupTypeToText($GroupType){
		return $this->GroupTypes[$GroupType];
		
	}
	
	
	function aclrule_edittype($aclid,$type,$value,$NexValue=null){
		$md5=md5("$aclid$type");
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid='$aclid'");
        $q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE zmd5='$md5'");

		//$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid='$aclid'");
		$sql="INSERT OR IGNORE INTO webfilters_sqaclaccess 
		(zmd5,aclid,httpaccess,httpaccess_value,httpaccess_data) VALUES('$md5','$aclid','$type',1,'$NexValue')";
		//$sql="UPDATE webfilters_sqaclaccess SET httpaccess='$type',httpaccess_value=1,httpaccess_data='$NexValue' WHERE aclid='$aclid'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return false;}
		
		if($NexValue<>null){
			$sql="UPDATE webfilters_sqaclaccess SET httpaccess_data='$NexValue' WHERE  aclid='$aclid'";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;return false;}
		}
		
		
		return true;
		
	}
	private function isTransParent(){
		if(isset($GLOBALS[__CLASS__."/".__FUNCTION__])){return $GLOBALS[__CLASS__."/".__FUNCTION__];}
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM transparent_ports WHERE enabled=1");
		$tcount=intval($ligne["tcount"]);
		if($tcount>0) {
            $GLOBALS[__CLASS__ . "/" . __FUNCTION__] = 1;
            return 1;
        }

        $GLOBALS[__CLASS__."/".__FUNCTION__]=0;
		return 0;


	}
	private function isBubble(){
		if(isset($GLOBALS[__CLASS__."/".__FUNCTION__])){return $GLOBALS[__CLASS__."/".__FUNCTION__];}		
		$sock=new sockets();
		$GLOBALS[__CLASS__."/".__FUNCTION__]=$sock->GET_INFO("SquidBubbleMode");
		if($GLOBALS[__CLASS__."/".__FUNCTION__]==null){$GLOBALS[__CLASS__."/".__FUNCTION__]=0;}
		return $GLOBALS[__CLASS__."/".__FUNCTION__];
	}
	
	
	private function _ports_list(){
	
	if(isset($GLOBALS["ACL_PORTS_LIST"])){return $GLOBALS["ACL_PORTS_LIST"];}
	
	$qPorts=new mysql_squid_builder();
	if(!$qPorts->FIELD_EXISTS("webfilters_sqgroups", "PortDirection")){$qPorts->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `PortDirection` smallint(1) NOT NULL DEFAULT '0',ADD INDEX(`PortDirection`)");}
	
	$ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT * FROM proxy_ports WHERE enabled=1";
	$resultsPorts = $ql->QUERY_SQL($sql);
	
	$ip=new networking();
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	
	$arrayNICZ[null]="0.0.0.0";
	
	foreach ($interfaces as $eth){
		if(preg_match("#^gre#", $eth)){continue;}
		$nic=new system_nic($eth);
		$arrayNICZ[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	}
	$PortDirectionS[0]="{all}";
	foreach ($resultsPorts as $index=>$lignePorts){
		$eth=$lignePorts["nic"];
		$ipaddr=$arrayNICZ[$eth];
		$port=$lignePorts["port"];
		$IDPort=$lignePorts["ID"];
		$PortDirectionS[$IDPort]="{port} $port [$ipaddr]";
	}
	
	$GLOBALS["ACL_PORTS_LIST"]=$PortDirectionS;
	return $GLOBALS["ACL_PORTS_LIST"];
}
	
	public function PORTS_DIRECTIONS(){
	    if(isset($GLOBALS["PORTSDIRECTIONS"])){return $GLOBALS["PORTSDIRECTIONS"];}
        $tpl=new template_admin();
        $GLOBALS["PORTSDIRECTIONS"]=$tpl->ProxyPortsDirections();
        return $GLOBALS["PORTSDIRECTIONS"];
	}



    function ACL_MULTIPLE_EXPLAIN($aclid,$ACCESSEnabled,$aclgroup=0,$TableLink=null,$force=false){

        $phpFunc="nil";
        $aclObjectAfter="nil";
        $toexplainorg="nil";
        if(isset($GLOBALS["ACL_OBJECTS_JS_AFTER"])){
            $aclObjectAfter=urlencode("{$GLOBALS["ACL_OBJECTS_JS_AFTER"]}");
        }
        if(is_null($TableLink)){
            $TableLink="nil";
        }
        $forceD=0;
        if($force){
            $forceD=1;
        }

        if(isset($_GET["function"])){
            $phpFunc=urlencode("{$_GET["function"]}");
        }
        if(isset($_GET["toexplainorg"])){
            $toexplainorg=urlencode("{$_GET["toexplainorg"]}");
        }
        $cmd="-list-acls=$aclid&$ACCESSEnabled&$aclgroup&$TableLink$phpFunc$aclObjectAfter$toexplainorg";
        if ($force){
            $cmd="-build-acls=$aclid&$ACCESSEnabled&$aclgroup&$TableLink$phpFunc$aclObjectAfter$toexplainorg";
        }

        $Path="/proxy/acls/explain/$aclid/$ACCESSEnabled/$aclgroup/$TableLink/$phpFunc/$aclObjectAfter/$toexplainorg/$forceD";


        VERBOSE("RESTAPI $Path",__LINE__);
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($Path));
        if(!$json->Status){
            return "<span class='text-danger'>$json->Error</span>";
        }

        return base64_decode($json->Info);

        $RSP= $unix->go_exec_out("/usr/sbin/artica-phpfpm-service $cmd");
        if($GLOBALS["VERBOSE"]){print_r($RSP);echo "\n";}
        return $RSP[0];

    }
	
	private function template_name($groupid,$linkcolor="black"){
		
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		if(!$q->FIELD_EXISTS("webfilters_sqgroups", "tplreset")){
			$q->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `tplreset` smallint(1) ,ADD INDEX ( `tplreset` ) ");
		}
		
		
		$sql="SELECT acltpl,tplreset FROM webfilters_sqgroups WHERE ID='$groupid'";
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		
		if(intval($ligne["tplreset"])==1){
			return "{reset_connection}";
		}
		
		if($ligne["acltpl"]==null){return;}
		$zmd5=$ligne["acltpl"];
		
		if($ligne["acltpl"]=="ARTICA_SLASH_SCREEN"){
			$js="Loadjs('squid.webauth.php');";
			return "<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style=\"text-decoration:underline;\">HotSpot</a>";
		}
		
		$acltpl=$ligne["acltpl"];
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$sql="SELECT template_name,zmd5,lang,emptytpl FROM squidtpls WHERE `zmd5`='$acltpl'";
		$ligne=$q->mysqli_fetch_array($sql);
		$subject=base64_encode(addslashes($ligne["template_name"]));
		$js="Loadjs('squid.templates.php?Zoom-js=$zmd5&subject=$subject');";
		if($ligne["emptytpl"]==1){
			$js="Loadjs('squid.templates.php?template-settings-js=yes&zmd5={$ligne["zmd5"]}');";
		}
		
		if(trim($ligne["template_name"])==null){return;}
		return "<a href=\"javascript:blur();\" OnClick=\"$js\" style=\"text-decoration:underline;\">{$ligne["template_name"]}</a>";
		}
    public function utf8_encode($string):string{

        $phpver=phpversion();
        if(strpos(" $phpver","8.2")){
            return strval(mb_convert_encoding($string, 'UTF-8'));
        }
        if(function_exists("utf8_encode")){
            return utf8_encode($string);
        }
        return strval(mb_convert_encoding($string, 'UTF-8'));
    }
    private function utf8_decode($string):string{
        if(is_null($string)){
            return "";
        }
        if(function_exists("mb_convert_encoding") ){
            return strval(mb_convert_encoding($string, 'ISO-8859-1'));
        }

        if(function_exists("utf8_decode")){
            $ret=utf8_decode($string);
            return strval($ret);
        }
        return strval(mb_convert_encoding($string, 'ISO-8859-1'));
    }
	
	public function getobjectsNameFromAclrule($aclid,$linkcolor="black",$webfilters_sqacllinks="webfilters_sqacllinks"){
		if(isset($GLOBALS["getobjectsNameFromAclrule$aclid"])){return $GLOBALS["getobjectsNameFromAclrule$aclid"];}
        $remove_dst=0;
		
		VERBOSE("getobjectsNameFromAclrule($aclid...)",__LINE__);

		if($webfilters_sqacllinks=="dnsdist_sqacllinks"){
            $remove_dst=1;

        }
		$f                      = array();
		$tableorg               = null;
		$sock                   = new sockets();
		$text_license           = null;
        $markExcl               = null;
		$webfilters_sqacllinks  = trim($webfilters_sqacllinks);
		if($webfilters_sqacllinks==null){$webfilters_sqacllinks="webfilters_sqacllinks";}
		$EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
		$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
		if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
		if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
		$SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
		if(trim($SplashScreenURI)==null){$EnableSplashScreen=0;}
		$URLAR=parse_url($SplashScreenURI);
		if(isset($URLAR["host"])){$SplashScreenURI="http://$SplashScreenURI";}	
        $function=null;
        $jsafter=null;
		if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){ $text_license="<span class='text-danger'>&laquo;&laquo;{acl_no_license_explain}$markExcl&raquo;&raquo;</span>"; }
		$markExcl="<span style='font-size:30px;font-weight:bold'>&nbsp;!</span>";
		
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$qlProxy=new mysql_squid_builder(true);
		
		$ARRAY_AUTH["radius_auth"]=true;
		$ARRAY_AUTH["ad_auth"]=true;
		$ARRAY_AUTH["ldap_auth"]=true;
		$ARRAY_AUTH["hotspot_auth"]=true;

		if(isset($GLOBALS["ACL_OBJECTS_JS_AFTER"])){
            $jsafter="&js-after={$GLOBALS["ACL_OBJECTS_JS_AFTER"]}";
        }
		
		if(isset($_GET["function"])){
            $function="&function={$_GET["function"]}";
        }

		$sql="SELECT $webfilters_sqacllinks.gpid,$webfilters_sqacllinks.negation,$webfilters_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM $webfilters_sqacllinks,webfilters_sqgroups 
		WHERE $webfilters_sqacllinks.gpid=webfilters_sqgroups.ID 
		AND $webfilters_sqacllinks.aclid=$aclid ORDER BY $webfilters_sqacllinks.zOrder";
		
		$results = $q->QUERY_SQL($sql);
        writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){$f[]="<div class=text-error>$aclid: $q->mysql_error</div>";}

        if($webfilters_sqacllinks=="dnsdist_sqacllinks") {
            $qProxy = new mysql_squid_builder();
            $dnsdist_acls_errors = $qProxy->dnsdist_acls_errors();
        }
		
		VERBOSE($sql,__LINE__);
		VERBOSE(count($results)." Group(s)",__LINE__);
		foreach ($results as $index=>$ligne) {
			$error_object=null;
			$items_text=null;
            $finalItemText=null;
            $groups_lists=null;
			$ports_text=null;
			$PREFIX_TEXT=null;
            $params=$ligne["params"];
            $bulkimport=$ligne["bulkimport"];
			VERBOSE("ACL[$aclid]: Checking Group[{$ligne['ID']}] type:{$ligne["GroupType"]} enabled:{$ligne["enabled"]}",__LINE__);
			if($ligne["enabled"]==0){
				VERBOSE("ACL[$aclid]: Checking Group[{$ligne['ID']}] Not enabled, aborting", __LINE__);
				continue;
			}
            if(is_null($bulkimport)){
                $bulkimport="";
            }
			
			$butnot=null;

			if($remove_dst==1){
                if($ligne["GroupType"]=="dst"){
                    continue;
                }
                if($ligne["GroupType"]=="opendns"){
                    continue;
                }
                if($ligne["GroupType"]=="opendnsf"){
                    continue;
                }
            }


			if($ligne["GroupType"]=="proxy_auth_ldap"){
				preg_match("#ExtLDAP:(.+?):#",$ligne["GroupName"],$re);
				$ligne["GroupName"]=$re[1];
			}
			if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $ligne["GroupType"],$re)){$qlProxy->acl_ARRAY_NO_ITEM[$ligne["GroupType"]]=true;}
			
			
			$GroupName=$this->utf8_encode($ligne["GroupName"]);
			VERBOSE("--> webfilters_sqitems",__LINE__);
			$ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems 
                           WHERE gpid='{$ligne['ID']}'");
			if(!$q->ok){VERBOSE("$q->mysql_error",__LINE__);}
            if(is_null($ligne2["tcount"])){$ligne2["tcount"]=0;}

            if($ligne["GroupType"]=="AclsGroup"){
                $items=0;
                $results=$q->QUERY_SQL("SELECT gpid FROM webfilters_gpslink 
                WHERE groupid={$ligne['ID']} AND enabled=1");
                $TheItems=array();
                foreach ($results as $num=>$ligneGPS){
                    $gpsid=$ligneGPS["gpid"];
                    $items++;
                    $lineTheGroup=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpsid");
                    VERBOSE("$GroupName [{$ligne['ID']}] = {$lineTheGroup["GroupName"]} $items item(s)", __LINE__);
                    $TheItems[]="<a href=\"javascript:blur();\" OnClick=\"Loadjs('fw.rules.items.php?groupid=$gpsid$function',true);\">".$lineTheGroup["GroupName"]."</a>";

                }
                $ligne2["tcount"]=count($TheItems);
                $groups_lists=":&nbsp;".@implode("&nbsp;|&nbsp;",$TheItems);
            }

            if(strlen($bulkimport)==0) {
                $items = $ligne2["tcount"];
                VERBOSE("$GroupName [{$ligne['ID']}] = $items item(s)", __LINE__);
                $items_text = "$items {items}$groups_lists";
                if($ligne["GroupType"]!="spf" && $ligne["GroupType"]!="dmarc" && $ligne["GroupType"]!="spamc") {
                if (!isset($qlProxy->acl_ARRAY_NO_ITEM[$ligne["GroupType"]])) {

                    if ($items == 0) {
                        VERBOSE("ACL[$aclid]: Checking Group[{$ligne['ID']}] No item, aborting", __LINE__);
                        continue;
                    }

                }
            }
            }else{
                $items_text="{bulk_import}";
            }

            if($ligne["GroupType"]=="the_shields"){$items_text=null;}
			if($ligne["GroupType"]=="proxy_auth_adou"){$items_text=null;}
            if($ligne["GroupType"]=="proxy_auth_ads"){$items_text=null;}
            if($ligne["GroupType"]=="proxy_auth_authenticated"){$items_text=null;}
			if($ligne["GroupType"]=="proxy_auth_tagad"){$items_text=null;}
			if($ligne["GroupType"]=="proxy_auth_statad"){$items_text=null;}
			if($ligne["GroupType"]=="proxy_auth_ldap"){$items_text=null;}
			if($ligne["GroupType"]=="NudityScan"){$items_text=null;}
			//if($ligne["GroupType"]=="quota_time"){ $PREFIX_TEXT=$text_license; }
			if($ligne["GroupType"]=="quota_size"){ $PREFIX_TEXT=$text_license; }
            if($ligne["GroupType"]=="netbiosname"){ $finalItemText="{append_domain} &laquo;$params&raquo;"; }


			if(intval($ligne["PortDirection"])>0){
				$portslist=$this->_ports_list();
				$ports_text="&nbsp;{on_port}: ".$portslist[$ligne["PortDirection"]];
			}

			if($ligne["GroupType"]=="dynamic_acls"){
				$DynamicACLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DynamicACLUrgency"));
				VERBOSE("DynamicACLUrgency = $DynamicACLUrgency",__LINE__);
				$ligne3=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilter_aclsdynamic WHERE gpid='{$ligne['ID']}'");
				if($DynamicACLUrgency==1){
					$error_object="<span style='color:#A71A05'> ({proxy_in_dynamic_emergency_mode})</span><br>";}
				
				if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$error_object=$text_license;}
				$items_text="&nbsp;<a href=\"javascript:blur();\" 
				OnClick=\"Loadjs('squid.acls.dynamics.php?ByJs=yes&gpid={$ligne['ID']}$function');\"
				style=\"font-weight:normal;text-decoration:underline;\">{$ligne3["tcount"]} {rules}</a>&nbsp;";
				
			}


			if($ligne["GroupType"]=="all"){$items_text="{all}";}

			if(isset($qlProxy->acl_ARRAY_NO_ITEM[$ligne["GroupType"]])){
				VERBOSE("$GroupName -> acl_ARRAY_NO_ITEM = True",__LINE__);
				$items_text=null;
			}


            if($ligne["GroupType"]=="spf" || $ligne["GroupType"]=="dmarc" || $ligne["GroupType"]=="spamc"){
                $items_text=$ligne["GroupType"];
            }



			$template=null;
			if(isset($_GET["toexplainorg"])){$tableorg="&table-org={$_GET["toexplainorg"]}";}
			
			if(isset($ARRAY_AUTH[$ligne["GroupType"]])){
				$butnot="{authenticated_trough}&nbsp;";
			}
			
			if($ligne["negation"]==1){
				$butnot="{notfor}&nbsp;";
				if(isset($ARRAY_AUTH[$ligne["GroupType"]])){
					$butnot="{not_authenticated_trough}&nbsp;";
				}
				$template=null;
			}
			
			if(isset($ARRAY_AUTH[$ligne["GroupType"]])){
				$items_text="<a href=\"javascript:blur();\" 
				OnClick=\"Loadjs('squid.auth-objects-events.php?ID={$ligne['ID']}$function',true);\"
				style=\"text-decoration:underline;\">{events}</a>
				";
			}
			
			if($ligne["GroupType"]=="hotspot_auth"){
				$warn="<img src='img/status_warning.png' style='margin-right:4px;float: left'>";
				$items_text="<a href=\"javascript:blur();\" 
				OnClick=\"Loadjs('squid.webauth.php');\"
				style=\"text-decoration:underline;\">{parameters}</a>
				";
				
				if($EnableSplashScreen==0){
					$items_text=$warn.$items_text;
				}
				
				if($EnableSplashScreenAsObject==0){
					$items_text=$warn.$items_text;
				}				
				
			}

			if($GLOBALS["VERBOSE"]){echo "Group Type: {$ligne["GroupType"]} ".__LINE__."<br>\n";}
			
			if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $ligne["GroupType"],$re)){
				$ligne3=$q->mysqli_fetch_array("SELECT * FROM sessions_objects WHERE ID='{$re[2]}'");
				$GroupName="$GroupName&nbsp;&raquo;&nbsp;{session_tracking} - {$re[1]} - {$ligne3["objectname"]}";
			}

            if($items_text<>null){
                $finalItemText=$finalItemText.$items_text;
            }
            if($template<>null){
                $finalItemText=$finalItemText.$template;
            }
			

			if($finalItemText<>null){
                $finalItemText="($finalItemText)";
            }

			
			$href="<a href=\"javascript:blur();\" 
			OnClick=\"Loadjs('fw.rules.items.php?groupid={$ligne['ID']}$tableorg$jsafter$function',true);\" 
			style=\"text-decoration:underline;\">";		

			if($webfilters_sqacllinks=="dnsdist_sqacllinks"){
                if(isset($dnsdist_acls_errors[$ligne["GroupType"]])){
                    $error_object="$error_object <span style='color:#A71A05'>{$dnsdist_acls_errors[$ligne["GroupType"]]}</span>";
                }
            }
			
			$f[]="$PREFIX_TEXT$butnot&laquo;$href$GroupName</a>&raquo;$error_object $ports_text <span><small>$finalItemText</small>";
		}
		
		
		$GLOBALS["getobjectsNameFromAclrule$aclid"]=$f;
		return $f;
		
	}
	
	public function FlexArray($gpid,$enable=1,$jsafetr=null){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$qlProxy=new mysql_squid_builder();

		if($jsafetr==null){
		    if(isset($GLOBALS["ACL_OBJECTS_JS_AFTER"])){
                $jsafetr = $GLOBALS["ACL_OBJECTS_JS_AFTER"];
            }
        }

		$jsafetr=urlencode(base64_encode($jsafetr));
		$items=0;
		$color="black";
		if($enable==0){$color="#8a8a8a";}
		$tpl=new templates();
		$rules=$tpl->_ENGINE_parse_body("{rules}");
		
		
		$ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType,enabled FROM webfilters_sqgroups WHERE ID='$gpid'");
		$GroupName=null;
		$GroupType=$ligne["GroupType"];
		$GroupEnabled=intval($ligne["enabled"]);
		
		
		if($GroupType=="proxy_auth_ldap"){
			$p=new ldap_extern();
			preg_match("#^ExtLDAP:(.+?):(.+)#", $ligne['GroupName'],$re);
			$GroupName=$re[1];
			$DN=base64_decode($re[2]);
			$items=$p->CountDeUsersByGroupDN($DN);
		}
		
		$GroupTypeName=$tpl->_ENGINE_parse_body($q->acl_GroupType[$GroupType]);
		if($GroupName==null){$GroupName=$tpl->utf8_encode($ligne["GroupName"]);}
		if($GroupType=="ndpi"){$GroupTypeName="Deep packet inspection";}
		

		if($items==0){
			$ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$gpid'");
			$items=$ligne2["tcount"];
		}
				

		
		
		
		
		if($GroupType=="dynamic_acls"){
			$ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilter_aclsdynamic WHERE gpid='$gpid'");
			$items="<a href=\"javascript:blur();\" 
			
			OnClick=\"Loadjs('squid.acls.dynamics.php?ByJs=yes&gpid=$gpid');\"
			style='font-weight:bold;text-decoration:underline;color:$color'>{$ligne2["tcount"]}</a>";
			$additional_text="&nbsp;&nbsp;<a href=\"javascript:blur();\" 
			OnClick=\"Loadjs('squid.acls.dynamics.php?ByJs=yes&gpid=$gpid');\"
			style='font-weight:bold;text-decoration:underline;color:$color'>[$rules]</a>";
		}
		
		
		
		

		if(preg_match("#time_quota:([0-9]+)#", $GroupType,$re)){
			$ligne2=$q->mysqli_fetch_array("SELECT * FROM quota_objects WHERE ID='{$re[1]}'");
			$GroupTypeName=$tpl->_ENGINE_parse_body("{time_quota} {$ligne2["objectname"]}");
		}
		if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $GroupType,$re)){
			$qlProxy->acl_ARRAY_NO_ITEM[$GroupType]=true;
			$ligne2=$q->mysqli_fetch_array("SELECT * FROM sessions_objects WHERE ID='{$re[2]}'");
			$GroupTypeName=$tpl->_ENGINE_parse_body("{session_tracking} - {$re[1]} - {$ligne2["objectname"]}");
		}		
	
		
		
		$tpl=new template_admin();
		$hrefT=$tpl->td_href($GroupName. " ($GroupTypeName)","{click_to_edit}","Loadjs('fw.rules.items.php?groupid=$gpid&js-after=$jsafetr')");

		
		if(isset($qlProxy->acl_ARRAY_NO_ITEM[$GroupType])){unset($hrefAdd);}
		if($items==0){
			if(isset($qlProxy->acl_ARRAY_NO_ITEM[$GroupType])){$items='-';}
		}
		if($GroupType=="all"){$items="*";}
		

		
		return array("ROW"=>"$hrefT&nbsp;&nbsp;$additional_text",
				"ITEMS"=>$items,"ENABLED"=>$GroupEnabled
				
				);
		
		
	}
	
	
}