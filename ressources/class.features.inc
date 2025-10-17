<?php


include_once(dirname(__FILE__).'/class.os.system.tools.inc');

class features{
    private $users;
    public $OnlyActive=false;
    private $MEMORY;
    private $sockets;
    private $cmd;
    public $json=false;
    public $CMDLINES=false;
    private $JSAFTER=null;

    private $ASReverseProxyAppliance=false;


    function __construct(){
        $tt=array();
        foreach ($_GET as $xkey=>$value){$tt[]="$xkey=$value";}
        $this->cmd=@implode("&", $tt);

        $this->users=new usersMenus();
        $os_system=new os_system();
        $results=$os_system->memory();
        $this->sockets=new sockets();
        $this->MEMORY=intval($results['ram']['total']/1024)*1.02;
        if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
            $this->ASReverseProxyAppliance=true;
        }
    }

    private function isSecurity($privileges=null){
        if($privileges==null){return true;}
        if($privileges=="AsSystemAdministrator"){if($this->users->AsSystemAdministrator){return true;}}
        if($privileges=="AsSquidAdministrator"){if($this->users->AsSquidAdministrator){return true;}}
        if($privileges=="AsDansGuardianAdministrator"){if($this->users->AsDansGuardianAdministrator){return true;}}
        if($privileges=="AsDnsAdministrator"){if($this->users->AsDnsAdministrator){return true;}}

        if($privileges=="AsPostfixAdministrator"){if($this->users->AsPostfixAdministrator){return true;}}
        if($privileges=="AsFirewallManager"){if($this->users->AsFirewallManager){return true;}}
        if($privileges=="AsSystemWebMaster"){if($this->users->AsSystemWebMaster){return true;}}
        if($privileges=="AsMailBoxAdministrator"){if($this->users->AsMailBoxAdministrator){return true;}}
        return false;

    }

    private function build_paragraph($config){
        $this->JSAFTER      = "LoadAjax('table-loader','fw.system.services.php?$this->cmd');RefreshSecondInterfaceBarrs();RefreshSecondInterfaceBarrs();";
        $this->JSAFTER      = "location.reload(true);";
        $classbt            = "btn-primary";
        $color              = "#000000";
        $enable_feature     = "{install2}";
        $disable_feature    = "{uninstall}";
        $TOKEN_INSTALLED    = 1;
        $label              = null;
        $TOKEN              = $config["TOKEN"];
        $KEY_JSON           = "";
        $moreinfo          = null;
        $wiki_ico          = null;

        if(isset( $config["MORE_INFOS"])){
            $tpl=new template_admin();
            $jsmoreinfos="s_PopUpFull('{$config["MORE_INFOS"]}','1024','900');";
            $moreinfo=$tpl->td_href("<span class='label label-info'>{more_infos}</span>",null,$jsmoreinfos);
        }



        if(preg_match("#\{(.+?)\}#",$config["TITLE"],$re)){$KEY_JSON=$re[1];}
        if(!isset($config["WIKI"])){$config["WIKI"]=null;}
        if(!isset($config["TOKEN_REQUIRE"])){$config["TOKEN_REQUIRE"]=null;}
        if(!isset($config["SECURITY"])){$config["SECURITY"]=null;}
        $HideCorporateFeatures=intval($this->sockets->GET_INFO("HideCorporateFeatures"));
        if($this->json){
            $HideCorporateFeatures=0;
            $this->OnlyActive=false;
        }
        if(!$this->json) {
            if (!$this->isSecurity($config["SECURITY"])) {
                return $this->build_paragraphe_failed(
                    "{wrong_privileges}",$config["TITLE"] ,
                    $config["EXPLAIN"],$config["WIKI"]);
            }
        }

        if(isset($config["KERNERROR"])){
            if($config["KERNERROR"]==1) {
                return $this->build_paragraphe_failed(
                    "{wrong_kernel_version}",$config["TITLE"] ,
                    $config["EXPLAIN"],$config["WIKI"]);
            }
        }

        if(isset($config["LICENSE"])){
            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
                VERBOSE("feature: CORP_LICENSE = False", __LINE__);
                return $this->build_paragraphe_failed("{require} <strong>Entreprise License</strong>", $config["TITLE"], $config["EXPLAIN"],$config["WIKI"]);
            }
        }

        if(isset($config["CONFLICT"])){
            if($config["CONFLICT"]==1){
                return $this->build_paragraphe_failed("{port_conflicts}", $config["TITLE"], $config["EXPLAIN"],$config["WIKI"]);
            }
        }

        $INVALIDVA=false;
        if(isset($config["INVALIDVA"])){
            $users=new usersMenus();
            if($users->HYPERV_HOST){$INVALIDVA=true;}
            if($users->VPS_OPENVZ){$INVALIDVA=true;}
            if($users->VMWARE_HOST){$INVALIDVA=true;}
            if($users->XEN_HOST){$INVALIDVA=true;}
        }

        if(isset($config["REBOOT"])){
            $this->JSAFTER=$this->JSAFTER."Loadjs('fw.system.restart.php');";
        }

        if($INVALIDVA){
            return $this->build_paragraphe_failed("{incompatible}",$config["TITLE"],$config["EXPLAIN"]);
        }

        if(isset($config["TOKEN_INSTALLED"])){
            $TOKEN_INSTALLED=intval($this->sockets->GET_INFO("{$config["TOKEN_INSTALLED"]}"));
            VERBOSE("feature: {$config["TOKEN_INSTALLED"]} == $TOKEN_INSTALLED", __LINE__);
            if(!isset($config["DEBIAN"])){
                if($TOKEN_INSTALLED==0){
                    return $this->build_paragraphe_failed("{not_installed}",
                        $config["TITLE"]."&nbsp;$moreinfo",$config["EXPLAIN"],$config["WIKI"]);
                }

            }else{
                $TOKEN_INSTALLED=1;
            }
        }
        if(isset($config["TOKENS_REQUIRE_INSTALLED"])){
            foreach ($config["TOKENS_REQUIRE_INSTALLED"] as $xkey=>$ProductName){
                $TOKEN_REQUIRE_INSTALLED=intval($this->sockets->GET_INFO("$xkey"));
                if($TOKEN_REQUIRE_INSTALLED==0){
                    if($this->OnlyActive){return "";}
                    return $this->build_paragraphe_failed("{require_installed} {{$ProductName}}",$config["TITLE"],$config["EXPLAIN"],$config["WIKI"]);
                }
            }

        }

        if(isset($config["TOKEN_REQUIRE_INSTALLED"])){
            $TOKEN_REQUIRE_INSTALLED=intval($this->sockets->GET_INFO("{$config["TOKEN_REQUIRE_INSTALLED"]}"));
            if($TOKEN_REQUIRE_INSTALLED==0){
                if($this->OnlyActive){return "";}
                return $this->build_paragraphe_failed("{require_installed} {$config["TOKEN_REQUIRE"]}",
                    $config["TITLE"]."&nbsp;$moreinfo",$config["EXPLAIN"],$config["WIKI"]);
            }

        }

        if(isset($config["TOKEN_REQUIRE_ENABLED"])){
            $TOKEN_REQUIRE_ENABLED=intval($this->sockets->GET_INFO("{$config["TOKEN_REQUIRE_ENABLED"]}"));
            if($TOKEN_REQUIRE_ENABLED==0){
                if($this->OnlyActive){return "";}
                return $this->build_paragraphe_failed("{require_enabled} {$config["TOKEN_REQUIRE"]}",
                    $config["TITLE"]."&nbsp;$moreinfo",$config["EXPLAIN"],$config["WIKI"]);
            }
        }

        if(isset($config["ON_CONFLICTS"])) {
            if (is_array($config["ON_CONFLICTS"])) {
                foreach ($config["ON_CONFLICTS"] as $xkey => $ProductName) {
                    $TOKEN_ON_CONFLICT = intval($this->sockets->GET_INFO("$xkey"));
                    if ($TOKEN_ON_CONFLICT == 1) {
                        if ($this->OnlyActive) {
                            return "";
                        }
                        return $this->build_paragraphe_failed("{conflict_with} {{$ProductName}}",
                            $config["TITLE"]."&nbsp;$moreinfo", $config["EXPLAIN"],$config["WIKI"]);
                    }
                }

            }
        }


        $ARRAY["PROGRESS_FILE"]=$config["PROGRESS_FILE"];
        $ARRAY["LOG_FILE"]=$config["LOG_FILE"];

        $ENABLE=intval($this->sockets->GET_INFO($TOKEN));
        if($ENABLE==0){
            if($this->OnlyActive){
                VERBOSE("OnlyActive == TRUE: SKIP",__LINE__);
                return "";
            }
            if($color=="#000000"){$color="#ABABAB";}
            $application_status="<span class='label label-default'>{uninstalled}</span>";
            $ARRAY["CMD"]=$config["CMD_ON"];
            $ARRAY["TITLE"]="{$config["TITLE"]}:{enable_feature}";
            $ARRAY["AFTER"]=$this->JSAFTER;
            $prgress=base64_encode(serialize($ARRAY));
            $jsrestart="Loadjs('fw.progress.modal.lock.php?content=$prgress&mainid=progress-$TOKEN-restart')";
            $label="<a href=\"javascript:blur()\" OnClick=\"$jsrestart\" class='btn $classbt btn-sm'>
			<i class='fas fa-check'></i> $enable_feature </a>";

            if($this->json){
                $tpl=new template_admin();
                $array[$KEY_JSON]["TITLE"]=$tpl->javascript_parse_text($config["TITLE"]);
                $array[$KEY_JSON]["AVAILABLE"]=true;
                $array[$KEY_JSON]["INSTALLED"]=false;
                $array[$KEY_JSON]["INFO"]="Uninstalled";
                if($this->CMDLINES){$array[$KEY_JSON]["FRAMEWORK"]=$config["CMD_ON"];}
                $array[$KEY_JSON]["EXPLAIN"]=$tpl->javascript_parse_text($config["EXPLAIN"]);
                return $array;
            }



        }else{
            $application_status="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;{installed}&nbsp;&nbsp;&nbsp;</span>";
            $ARRAY["CMD"]=$config["CMD_OFF"];
            $ARRAY["TITLE"]="{$config["TITLE"]}:{disable_feature}";
            $ARRAY["AFTER"]=$this->JSAFTER;
            $prgress=base64_encode(serialize($ARRAY));
            $jsrestart="Loadjs('fw.progress.modal.lock.php?content=$prgress&mainid=progress-$TOKEN-restart')";
            if($disable_feature<>null){
                $label="<a href=\"javascript:blur()\" OnClick=\"$jsrestart\" class='btn btn-warning btn-sm'>
			<i class='fas fa-check'></i> $disable_feature </a>";
            }


            if($this->json){
                $tpl=new template_admin();
                $array[$KEY_JSON]["TITLE"]=$tpl->javascript_parse_text($config["TITLE"]);
                $array[$KEY_JSON]["AVAILABLE"]=true;
                $array[$KEY_JSON]["INSTALLED"]=true;
                $array[$KEY_JSON]["INFO"]="Installed";
                if($this->CMDLINES){$array[$KEY_JSON]["FRAMEWORK"]=$config["CMD_OFF"];}
                $array[$KEY_JSON]["EXPLAIN"]=$tpl->javascript_parse_text($config["EXPLAIN"]);
                return $array;
            }

        }

        if($TOKEN_INSTALLED==0){$application_status="&nbsp;";}
        if($classbt=="btn-default"){$application_status="&nbsp;";}

        $tpl=new template_admin();
        $expand=0;
        if(isset($_GET["expand"])){
            $expand=intval($_GET["expand"]);
        }
        $EXPLAIN_TEXT=null;
        if($expand==1){
            $EXPLAIN_TEXT=$tpl->_ENGINE_parse_body("<br><small style='color:$color'>{$config["EXPLAIN"]}</small>");

        }

        $tpl=new template_admin();
        if($config["WIKI"]<>null){
            $wiki_ico=$tpl->icon_wiki($config["WIKI"]);
        }
        $strong="<strong style='color:$color'>";
        $width1="style='width:1%;color:$color'";

        if(isset($config["MEMMIN"])){
            if($this->MEMORY<$config["MEMMIN"]){
                $config["MEMMIN"]=$config["MEMMIN"]/1000;
                $label="<a href=\"javascript:blur()\" OnClick=\"blur()\" class='btn btn-default btn-sm'>
			<i class='fas fa-exclamation-triangle'></i> {require} ". $config["MEMMIN"]."GB {memory}</a>";
                $color="#CCCCCC";
                $html[]="<tr>
			<td class='project-status' $width1 nowrap class='center'></td>
			<td class='project-title' >$strong{$config["TITLE"]}</strong>&nbsp;$moreinfo$EXPLAIN_TEXT</td>
			<td class='project-actions' $width1 nowrap class='center'>$wiki_ico</td>
			<td class='project-actions' $width1 nowrap>$label</td>
			</tr>";
                return @implode("\n", $html);
            }

        }



        $html[]="<tr>
		<td class='project-status' $width1 class='center' nowrap>$application_status</td>
		<td class='project-title'>$strong{$config["TITLE"]}</strong>&nbsp;$moreinfo$EXPLAIN_TEXT</td>
		<td class='project-actions' $width1 class='center' nowrap>$wiki_ico</td>
		<td class='project-actions' $width1  nowrap>$label</td>
		</tr>";

        return @implode("\n", $html);

    }

    private function build_paragraphe_failed($button_name,$title,$explain,$wiki=null){
        $KEY_JSON       = "";
        if(preg_match("#\{(.+?)\}#",$title,$re)){$KEY_JSON=$re[1];}

        if($this->json){
            $tpl=new template_admin();
            $array[$KEY_JSON]["TITLE"]=$tpl->javascript_parse_text($title);
            $array[$KEY_JSON]["AVAILABLE"]=false;
            $array[$KEY_JSON]["INFO"]=$button_name;
            $array[$KEY_JSON]["INSTALLED"]=false;
            $array[$KEY_JSON]["EXPLAIN"]=$tpl->javascript_parse_text($explain);
            return $array;

        }


        if($this->OnlyActive){return "";}
        $color="#CCCCCC";
        $wiki_ico="&nbsp;";
        $label="<a href=\"javascript:Blur()\" OnClick=\"Blur()\" class='btn btn-default btn-sm'>
				<i class='fas fa-exclamation-triangle'></i> $button_name</strong></a>";

        $explain_text=null;
        if(isset($_GET["expand"])) {
            if ($_GET["expand"] == 1) {
                $explain_text = "<br><small style='color:$color'>$explain</small>";
            }
        }
        $tpl=new template_admin();
        if($wiki<>null){
            $wiki_ico=$tpl->icon_wiki($wiki);
        }


        $html[]="<tr>
		<td class='project-status' style='width:1%;color:$color' nowrap class='center'></td>
		<td class='project-title'><strong style='color:$color'>$title</strong>$explain_text</td>
		<td class='project-status' style='width:1%;color:$color' class='center' nowrap>$wiki_ico</td>
		<td class='project-actions' style='width:1%' nowrap>$label</td>
		</tr>";
        return $tpl->_ENGINE_parse_body(@implode("\n", $html));

    }


    public function speedtest(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{EnableBandwithCalculation}";
        $config["EXPLAIN"]="{EnableBandwithCalculation_explain}";
        $config["TOKEN"]="EnableBandwithCalculation";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/speedtests.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/speedtests.log";
        $config["CMD_ON"]="speedtests.php?install=yes";
        $config["CMD_OFF"]="speedtests.php?uninstall=yes";
        return $this->build_paragraph($config);

    }


    public function OPENLDAP(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_OPENLDAP}";
        $config["EXPLAIN"]="{APP_OPENLDAP_EXPLAIN}";
        $config["TOKEN"]="EnableOpenLDAP";
        $config["TOKEN_INSTALLED"]="openldap_installed";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/openldap.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/openldap.log";
        $config["CMD_ON"]="/openldap/install";
        $config["CMD_OFF"]="/openldap/uninstall";
        return $this->build_paragraph($config);
    }

    public function OPENLDAP_REST(){
        $config["LICENSE"]=true;
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_OPENLDAP_RESTFUL}";
        $config["EXPLAIN"]="{APP_OPENLDAP_RESTFUL_EXPLAIN}";
        $config["TOKEN"]="EnableOpenLDAPRestFul";
        $config["TOKEN_REQUIRE_ENABLED"]="EnableOpenLDAP";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/openldap.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/openldap.log";
        $config["CMD_ON"]="openldap.php?rest-enable=yes";
        $config["CMD_OFF"]="openldap.php?rest-disable=yes";
        return $this->build_paragraph($config);
    }

    public function APP_PRIVOXY(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["TITLE"]="{APP_PRIVOXY}";
        $config["EXPLAIN"]="{privoxy_explain}";
        $config["TOKEN_INSTALLED"]="APP_PRIVOXY_INSTALLED";
        $config["TOKEN_REQUIRE_INSTALLED"]="SQUID_INSTALLED";
        $config["TOKEN_REQUIRE_ENABLED"]="SQUIDEnable";
        $config["TOKEN"]="PrivoxyEnabled";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.txt";
        $config["CMD_ON"]="privoxy.php?install=yes";
        $config["CMD_OFF"]="privoxy.php?disable=yes";
        return $this->build_paragraph($config);
    }

    public function APP_ZABBIX_AGENT(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["TITLE"]="{APP_ZABBIX_AGENT}";
        $config["EXPLAIN"]="{APP_ZABIX_SERVER_TEXT}";
        $config["TOKEN_INSTALLED"]="APP_ZABBIX_AGENT_INSTALLED";
        $config["TOKEN"]="EnableZabbixAgent";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/zabbix.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/zabbix.txt";
        $config["CMD_ON"]="/zabbix/install";
        $config["CMD_OFF"]="/zabbix/uninstall";
        return $this->build_paragraph($config);


    }

    public function APP_RDPPROXY(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_RDPPROXY}";
        $config["EXPLAIN"]="{APP_RDPPROXY_EXPLAIN}";
        $config["TOKEN_INSTALLED"]="APP_RDPPROXY_INSTALLED";
        $config["TOKEN"]="EnableRDPProxy";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/squid.rdpproxy.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/squid.rdpproxy.progress.txt";
        $config["CMD_ON"]="/rdpproxy/install";
        $config["CMD_OFF"]="/rdpproxy/uninstall";
        return $this->build_paragraph($config);
    }



    public function APP_WORDPRESS(){
        $config["TOKENS_REQUIRE_INSTALLED"]["APP_NGINX_INSTALLED"]="APP_NGINX";
        $config["TOKENS_REQUIRE_INSTALLED"]["APP_MYSQL_INSTALLED"]="APP_MYSQL";

        $config["SECURITY"]="AsSystemWebMaster";
        $config["TITLE"]="{wordpress_websites}";
        $config["EXPLAIN"]="{APP_WORDPRESS_ARTICA_TEXT}";
        $config["TOKEN_INSTALLED"]="WP_CLIENT_INSTALLED";
        $config["TOKEN"]="EnableWordpressManagement";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.install.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.install.progress.txt";
        $config["CMD_ON"]="wordpress.php?install=yes";
        $config["CMD_OFF"]="wordpress.php?uninstall=yes";
        return $this->build_paragraph($config);
    }

    public function APP_PHP_REVERSE():string{
        return "";
        $config["TOKENS_REQUIRE_INSTALLED"]["EnableNginx"]="APP_NGINX";
        $config["SECURITY"]="AsSystemWebMaster";
        $config["TITLE"]="{APP_PHP_REVERSE}";
        $config["EXPLAIN"]="{APP_PHP_REVERSE_ABOUT}";
        $config["TOKEN_INSTALLED"]="APP_PHP_REVERSE_INSTALLED";
        $config["TOKEN_REQUIRE_ENABLED"]="EnableNginx";
        $config["TOKEN"]="PHPReverseEnabled";
        $config["CMD_ON"]="/phpreverse/nohup/install";
        $config["CMD_OFF"]="phpreverse/nohup/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/phpreverse.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/phpreverse.log";
        return $this->build_paragraph($config);
    }

    public function MOD_SECURITY():string{
        $config["TOKENS_REQUIRE_INSTALLED"]["EnableNginx"]="APP_NGINX";
        $config["SECURITY"]="AsSystemWebMaster";
        $config["TITLE"]="Web Application Firewall (reverse-proxy)";
        $config["EXPLAIN"]="{ModSecurityExplain}";
        $config["TOKEN_INSTALLED"]="MODSECURITY_INSTALLED";
        $config["TOKEN_REQUIRE_ENABLED"]="EnableNginx";
        $config["TOKEN"]="EnableModSecurityIngix";
        $config["CMD_ON"]="nginx.php?modsecurity-install=yes";
        $config["CMD_OFF"]="nginx.php?modsecurity-uninstall=yes";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/modsecurity.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/modsecurity.progress.log";
        return $this->build_paragraph($config);
    }



    public function APP_SNMPD(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["DEBIAN"]=true;
        $config["TITLE"]="{APP_SNMPD}";
        $config["TOKEN_INSTALLED"]="SNMPD_INSTALLED";
        $config["TOKEN"]="EnableSNMPD";
        $config["EXPLAIN"]="{SNMPD_ABOUT}";
        $config["CMD_ON"]="/snmpd/install";
        $config["CMD_OFF"]="/snmpd/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/snmpd.service.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/snmpd.service.progress.txt";
        return $this->build_paragraph($config);

    }

    public function APP_DNSFILTERD(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["ON_CONFLICTS"]["EnableUfdbGuard"]="APP_UFDBGUARD";
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TOKEN_REQUIRE_ENABLED"]="UnboundEnabled";
        $config["TOKEN_REQUIRE_INSTALLED"]="UnboundInstalled";
        $config["TOKEN_REQUIRE"]="{APP_UNBOUND}";
        $config["TOKEN_INSTALLED"]="APP_DNSFILTERD_INSTALLED";
        $config["TOKEN"]="EnableDNSFilterd";
        $config["EXPLAIN"]="{APP_DNSFILTERD_EXPLAIN}";
        $config["TITLE"]="{APP_DNSFILTERD}";
        $config["CMD_ON"]="dnsfilterd.php?install=yes";
        $config["CMD_OFF"]="dnsfilterd.php?uninstall=yes";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/dnsfilterd.service.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/dnsfilterd.service.progress.txt";
        return $this->build_paragraph($config);
    }
    public function APP_DNS_FIREWALL(){
        return "";
    }

    public function APP_CYBERCRIMEIPFEEDS(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["DEBIAN"]=true;
        $config["TITLE"]="{CybercrimeIPFeeds}";
        $config["TOKEN"]="ArticaPSnifferDaemon";
        $config["EXPLAIN"]="{CybercrimeIPFeeds_explain}";
        $config["CMD_ON"]="firehol.php?articapcap-install=yes";
        $config["CMD_OFF"]="firehol.php?articapcap-uninstall=yes";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/articapcap.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/articapcap.log";
        return $this->build_paragraph($config);

    }
    public function APP_DNSFILTERD_REST(){
        return "";
        /*
         *
         $config["LICENSE"]=true;
        $config["TOKEN_REQUIRE_INSTALLED"]="APP_DNSFILTERD_INSTALLED";
        $config["TOKEN_REQUIRE_ENABLED"]="EnableDNSFilterd";
        $config["TOKEN_REQUIRE"]="{APP_DNSFILTERD}";
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TITLE"]="{APP_DNSFILTERD_REST}";
        $config["EXPLAIN"]="{APP_DNSFILTERD_REST_EXPLAIN}";
        $config["TOKEN"]="EnableDNSFilterdRest";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/dnsfilterd.service.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/dnsfilterd.service.progress.txt";
        $config["CMD_ON"]="dnsfilterd.php?rest-enable=yes";
        $config["CMD_OFF"]="dnsfilterd.php?rest-disable=yes";
        return $this->build_paragraph($config);
        */
    }
    public function APP_RUSTDESK():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_RUSTDESK}";
        $config["TOKEN_INSTALLED"]="APP_RUSTDESK_INSTALLED";
        $config["TOKEN"]="EnableRustDeskServer";
        $config["EXPLAIN"]="{APP_RUSTDESK_EXPLAIN}";
        $config["CMD_ON"]="rustdesk.php?install=yes";
        $config["CMD_OFF"]="rustdesk.php?uninstall=yes";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/rustdesk.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/rustdesk.install.progress.log";
        return $this->build_paragraph($config);
    }


    public function APP_WAZHU():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_WAZHU}";
        $config["TOKEN_INSTALLED"]="APP_WAZHU_INSTALLED";
        $config["TOKEN"]="EnableWazhuCLient";
        $config["EXPLAIN"]="{APP_WAZHU_EXPLAIN}";
        $config["CMD_ON"]="wazhu.client.php?install=yes";
        $config["CMD_OFF"]="wazhu.client.php?uninstall=yes";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/wazhu.client.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/wazhu.client.progress.log";
        return $this->build_paragraph($config);
    }

    public function APP_VNSTAT():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_VNSTAT}";
        $config["TOKEN_INSTALLED"]="APP_VNSTAT_INSTALLED";
        $config["TOKEN"]="EnableVnStat";
        $config["EXPLAIN"]="{vnstat_service_explain}";
        $config["CMD_ON"]="/vnstat/install";
        $config["CMD_OFF"]="/vnstat/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/vnstat.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/vnstat.progress.log";
        return $this->build_paragraph($config);
    }

    function APP_SYSLOG_SERVER():string{
        $LegallogServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegallogServer"));
        if($LegallogServer==1){return "";}
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_SYSLOGD}";
        $config["TOKEN"]="ActAsASyslogServer";
        $config["EXPLAIN"]="{APP_SYSLOG_SERVER_EXPLAIN}";
        $config["CMD_ON"]="/syslog/server/install";
        $config["CMD_OFF"]="/syslog/server/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/syslog.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/syslog.install.progress.log";
        return $this->build_paragraph($config);
    }

    function APP_LEGAL_LOGS_SERVER():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["LICENSE"]=true;
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_LEGAL_LOGS_SERVER}";
        $config["TOKEN"]="LegallogServer";
        $config["EXPLAIN"]="{APP_SYSLOG_SERVER_EXPLAIN}";
        $config["CMD_ON"]="/syslog/server/install";
        $config["CMD_OFF"]="/syslog/server/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/syslog.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/syslog.install.progress.log";
        return $this->build_paragraph($config);
    }



    function APP_FRONTAIL_LINUX():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_FRONTAIL_LINUX}";
        $config["TOKEN_INSTALLED"]="FRONTAIL_LINUX_INSTALLED";
        $config["TOKEN"]="EnableFrontail";
        $config["EXPLAIN"]="{APP_FRONTAIL_LINUX_EXPLAIN}";
        $config["CMD_ON"]="/frontail/install";
        $config["CMD_OFF"]="/frontail/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/frontail.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/frontail.install.progress";
        return $this->build_paragraph($config);
    }
    function APP_TAILON():string{

        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_TAILON}";
        $config["TOKEN_INSTALLED"]="TAILON_INSTALLED";
        $config["TOKEN"]="EnableTailon";
        $config["EXPLAIN"]="{APP_FRONTAIL_LINUX_EXPLAIN}";
        $config["CMD_ON"]="/tailon/install";
        $config["CMD_OFF"]="/tailon/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/tailon.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/tailon.progress.log";
        return $this->build_paragraph($config);
    }

    function APP_IMAPBOX():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
        if($DisableMessaging==1){return "";}
        $config["SECURITY"]="AsMailBoxAdministrator";
        $config["TITLE"]="{APP_IMAPBOX}";
        $config["TOKEN_INSTALLED"]="APP_IMAPBOX_INSTALLED";
        $config["TOKEN"]="EnableImapBox";
        $config["EXPLAIN"]="{APP_IMAPBOX_EXPLAIN}";
        $config["CMD_ON"]="imapbox.php?install=yes";
        $config["CMD_OFF"]="imapbox.php?uninstall=yes";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/imapbox.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/imapbox.progress.log";
        VERBOSE("build_paragraph()",__LINE__);
        return $this->build_paragraph($config);
    }




    function APP_MULTIPATH_TCP():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_MULTIPATH_TCP}";
        $config["TOKEN"]="APP_MULTIPATH_TCP_ENABLED";
        $config["EXPLAIN"]="{APP_MULTIPATH_TCP_EXPLAIN}";
        $config["CMD_ON"]="tcpmultipath.php?install=yes";
        $config["CMD_OFF"]="tcpmultipath.php?uninstall=yes";
        $config["REBOOT"]=true;
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/tcpmultipath.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/tcpmultipath.progress.log";
        return $this->build_paragraph($config);
    }
    function APP_IPAUDIT():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_IPAUDIT}";
        $config["TOKEN_INSTALLED"]="IPAUDIT_INSTALLED";
        $config["TOKEN"]="IpAuditEnabled";
        $config["EXPLAIN"]="{IPAUDIT_EXPLAIN}";
        $config["CMD_ON"]="/ipaudit/install";
        $config["CMD_OFF"]="/ipaudit/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/ipaudit.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/ipaudit.progress.log";
        $text=$this->build_paragraph($config);
        VERBOSE("APP_IPAUDIT: ".strlen($text)."bytes",__LINE__);
        return $text;
    }


    function APP_MUNIN():string{
        $config["SECURITY"]="AsSystemAdministrator";

        $config["TITLE"]="{APP_MUNIN}";
        $config["TOKEN_INSTALLED"]="MUNIN_CLIENT_INSTALLED";
        $config["TOKEN"]="EnableMunin";
        $config["EXPLAIN"]="{munin_service_explain}";
        $config["CMD_ON"]="munin.php?install=yes";
        $config["CMD_OFF"]="munin.php?uninstall=yes";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/munin.install.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/munin.install.progress.log";
        return $this->build_paragraph($config);
    }

    public function APP_FREERADIUS():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_FREERADIUS}";
        $config["TOKEN_INSTALLED"]="FREERADIUS_INSTALLED";
        $config["TOKEN"]="EnableFreeRadius";
        $config["EXPLAIN"]="{activate_freeradius_explain}";
        $config["CMD_ON"]="freeradius.php?install=yes";
        $config["CMD_OFF"]="freeradius.php?uninstall=yes";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/freeradius.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/freeradius.progress";
        return $this->build_paragraph($config);
    }

    public function ELASTICSEARCH():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["LICENSE"]=true;
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_ELASTICSEARCH}";
        $config["EXPLAIN"]="{APP_ELASTICSEARCH_EXPLAIN}";
        $config["TOKEN_INSTALLED"]="ELASTICSEARCH_INSTALLED";
        $config["TOKEN"]="EnableElasticSearch";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/ElasticSearch.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/ElasticSearch.log";
        $config["CMD_ON"]="elasticsearch.php?install=yes";
        $config["CMD_OFF"]="elasticsearch.php?uninstall=yes";
        $config["MEMMIN"]="3000";
        return $this->build_paragraph($config);

    }

    public function ELASTICSEARCH_CLIENT():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["LICENSE"]=true;
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_ELASTICSEARCH_CLIENT}";
        $config["EXPLAIN"]="{APP_ELASTICSEARCH_CLIENT_EXPLAIN}";
        $config["TOKEN"]="AsElasticClient";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/AsElasticClient.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/AsElasticClient.log";
        $config["CMD_ON"]="elasticsearch.php?artica-install=yes";
        $config["CMD_OFF"]="elasticsearch.php?artica-uninstall=yes";
        return $this->build_paragraph($config);

    }

    public function APP_VMTOOLS():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_VMTOOLS}";
        $config["EXPLAIN"]="{INSTALL_VMWARE_TOOLS_TEXT}";
        $config["TOKEN_INSTALLED"]="VMWARE_TOOLS_INSTALLED";
        $config["TOKEN"]="EnableVMWareTools";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/vmware.install.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/vmware.install.log";
        $config["CMD_ON"]="/vmtools/install";
        $config["CMD_OFF"]="/vmtools/uninstall";
        return $this->build_paragraph($config);
    }


    public function KIBANA():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["LICENSE"]=true;
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_KIBANA}";
        $config["EXPLAIN"]="{APP_KIBANA_EXPLAIN}";
        $config["TOKEN_INSTALLED"]="APP_KIBANA_INSTALLED";
        $config["TOKEN"]="EnableKibana";
        $config["TOKEN_REQUIRE"]="{APP_ELASTICSEARCH}";
        $config["TOKEN_REQUIRE_INSTALLED"]="ELASTICSEARCH_INSTALLED";
        $config["TOKEN_REQUIRE_ENABLED"]="EnableElasticSearch";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/kibana.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/kibana.log";
        $config["CMD_ON"]="kibana.php?install=yes";
        $config["CMD_OFF"]="kibana.php?uninstall=yes";
        $config["MEMMIN"]="3000";
        return $this->build_paragraph($config);
    }
    public function APP_CROWDSEC():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_CROWDSEC}";
        $config["EXPLAIN"]="{APP_CROWDSEC_EXPLAIN}";
        $config["TOKEN"]="EnableCrowdSec";
        $config["TOKEN_INSTALLED"]="APP_CROWDSEC_INSTALLED";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/crowdsec.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/crowdsec.progress.log";
        $config["CMD_ON"]="/crowdsec/install";
        $config["CMD_OFF"]="/crowdsec/uninstall";
        return $this->build_paragraph($config);

    }
    public function FAIL2BAN():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_FAIL2BAN}";
        $config["EXPLAIN"]="{APP_FAIL2BAN_EXPLAIN}";
        $config["TOKEN"]="EnableFail2Ban";
        $config["TOKEN_INSTALLED"]="FAIL2BAN_INSTALLED";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fail2ban.install.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/fail2ban.install.progress.txt";
        $config["CMD_ON"]="fail2ban.php?install=yes";
        $config["CMD_OFF"]="fail2ban.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function PROFTPD():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_PROFTPD}";
        $config["EXPLAIN"]="{APP_PROFTPD_EXPLAIN}";
        $config["TOKEN"]="EnableProFTPD";
        $config["TOKEN_INSTALLED"]="ProFTPDInstalled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/proftpd.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/proftpd.log";
        $config["CMD_ON"]="/proftpd/install";
        $config["CMD_OFF"]="/proftpd/uninstall";
        return $this->build_paragraph($config);
    }
    public function APP_QAT():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="Intel QuickAssist";
        $config["EXPLAIN"]="{APP_QAT_EXPLAIN}";
        $config["TOKEN"]="IntelQATEnabled";
        $config["TOKEN_INSTALLED"]="IntelQATInstalled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/IntelQAT.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/IntelQAT.log";
        $config["CMD_ON"]="/intelqat/install";
        $config["CMD_OFF"]="/intelqat/uninstall";
        return $this->build_paragraph($config);

    }
    public function UBOUND():string{
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TITLE"]="{APP_UNBOUND}";
        $config["EXPLAIN"]="{didyouknow_unbound}";
        $config["TOKEN"]="UnboundEnabled";
        $config["TOKEN_INSTALLED"]="UnboundInstalled";
        $config["WIKI"]="https://wiki.articatech.com/en/dns/dns-cache-service";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/unbound.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/unbound.install.log";
        $config["CMD_ON"]="/unbound/service/install";
        $config["CONFLICT"]=$this->IFLOCALCACHE_CONFLICTS();
        $config["CMD_OFF"]="/unbound/service/uninstall";
        return $this->build_paragraph($config);
    }
    public function NETWORKD():string{
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TITLE"]="{APP_NETWORKD}";
        $config["EXPLAIN"]="{networkd_explain}";
        $config["TOKEN"]="SYSTEMD_NETWORK_ENABLED";
        $config["TOKEN_INSTALLED"]="SYSTEMD_INSTALLED";
        //$config["WIKI"]="https://wiki.articatech.com/en/dns/dns-cache-service";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/systemd.network.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/systemd.network.progress.log";
        $config["CMD_ON"]="/system/network/systemd/install";
        $config["CMD_OFF"]="/system/network/systemd/uninstall";
        return $this->build_paragraph($config);
    }
    public function APP_LOKI():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_LOKI}";
        $config["EXPLAIN"]="{APP_LOKI_EXPLAIN}";
        $config["TOKEN"]="EnableLokiDB";
        $config["TOKEN_INSTALLED"]="LokiInstalled";
        $config["WIKI"]="https://wiki.articatech.com/en/system/loki-grafana";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/loki.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/loki.progress.log";
        $config["CMD_ON"]="/grafana/install";
        $config["CMD_OFF"]="/grafana/uninstall";
        return $this->build_paragraph($config);
    }
    public function UBOUND_DNS():string{
        return "";
    }



    public function APP_DOH_SERVER():string{

            return "";
        $config["TOKENS_REQUIRE_INSTALLED"]["EnableNginx"]="APP_NGINX";
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TITLE"]="{APP_DOH_SERVER}";
        $config["EXPLAIN"]="{APP_DOH_SERVER_EXPLAIN}";
        $config["TOKEN"]="DOHServerEnabled";
        $config["TOKEN_INSTALLED"]="APP_DOH_SERVER_INSTALLED";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/doh.install";
        $config["LOG_FILE"]=PROGRESS_DIR."/doh.install.log";
        $config["CMD_ON"]="doh.php?install=yes";
        $config["CMD_OFF"]="doh.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function APP_DNSCRYPT_PROXY():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["TOKENS_REQUIRE_INSTALLED"]["UnboundEnabled"]="APP_UNBOUND";
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TOKEN_INSTALLED"]="APP_DNSCRYPT_PROXY_INSTALLED";
        $config["TITLE"]="{APP_DNSCRYPT_PROXY}";
        $config["EXPLAIN"]="{APP_DNSCRYPT_PROXY_ABOUT}";
        $config["TOKEN"]="EnableDNSCryptProxy";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.progress.log";
        $config["CMD_ON"]="dnscrypt-proxy.php?install=yes";
        $config["CMD_OFF"]="dnscrypt-proxy.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function APP_HAMRP():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_HAMRP}";
        $config["EXPLAIN"]="{APP_HAMRP_ABOUT}";
        $config["TOKEN"]="EnableManagedReverseProxy";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/hamrp.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/hamrp.progress.log";
        $config["CMD_ON"]="hamrp.php?install=yes";
        $config["CMD_OFF"]="hamrp.php?uninstall=yes";
        return $this->build_paragraph($config);
    }


    FUNCTION APP_GEOIP_UPDATES():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_GEOIPUPDATE}";
        $config["EXPLAIN"]="{APP_GEOIPUPDATE_ABOUT}";
        $config["TOKEN"]="EnableGeoipUpdate";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/GeoipUpdate.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/GeoipUpdate.progress.log";
        $config["CMD_ON"]="geoipupdate.php?install=yes";
        $config["CMD_OFF"]="geoipupdate.php?uninstall=yes";
        return $this->build_paragraph($config);

    }
    public function APP_PDNS():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $MantiCoreSearchEnabled     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreSearchEnabled"));
        if($MantiCoreSearchEnabled==0) {
            $config["TOKENS_REQUIRE_INSTALLED"]["EnableMySQL"] = "APP_MYSQL";
        }
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TITLE"]="{APP_PDNS}";
        $config["EXPLAIN"]="{APP_PDNS_EXPLAIN}";
        $config["TOKEN"]="EnablePDNS";
        $config["TOKEN_INSTALLED"]="PDNSInstalled";
        $config["CONFLICT"]=$this->IFLOCALCACHE_CONFLICTS();
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.first.install";
        $config["LOG_FILE"]=PROGRESS_DIR."/pdns.first.install.log";
        $config["CMD_ON"]="pdns.php?first-install=yes";
        $config["CMD_OFF"]="pdns.php?remove=yes";
        return $this->build_paragraph($config);
    }
    public function APP_POWERDNS_RESTFUL():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["TOKENS_REQUIRE_INSTALLED"]["EnablePDNS"]="APP_PDNS";
        $config["LICENSE"]=true;
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TITLE"]="{APP_POWERDNS_RESTFUL}";
        $config["EXPLAIN"]="{APP_POWERDNS_RESTFUL_EXPLAIN}";
        $config["TOKEN"]="EnablePDNSRESTFul";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.first.install";
        $config["LOG_FILE"]=PROGRESS_DIR."/pdns.first.install.log";
        $config["CMD_ON"]="pdns.php?rest-install=yes";
        $config["CMD_OFF"]="pdns.php?rest-remove=yes";
        return $this->build_paragraph($config);
    }
    public function APP_DSC():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TITLE"]="{APP_DSC}";
        $config["EXPLAIN"]="{activate_dnsstats_explain}<br>{APP_DSC_EXPLAIN}";
        $config["TOKEN"]="PDNSStatsEnabled";
        $config["TOKEN_INSTALLED"]="PDNSStatsInstalled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.dsc.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/pdns.dsc.progress.txt";
        $config["CMD_ON"]="pdns.php?activate-dsc=yes";
        $config["CMD_OFF"]="pdns.php?disable-dsc=yes";
        return $this->build_paragraph($config);
    }
    public function APP_PDNS_RECURSOR():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TITLE"]="{APP_PDNS_RECURSOR}";
        $config["EXPLAIN"]="{APP_PDNS_RECURSOR_EXPLAIN}";
        $config["TOKEN_INSTALLED"]="PDNSInstalled";
        $config["TOKEN"]="PowerDNSEnableRecursor";
        $config["TOKEN_REQUIRE"]="{APP_PDNS}";
        $config["CONFLICT"]=$this->IFLOCALCACHE_CONFLICTS();
        $config["TOKEN_REQUIRE_ENABLED"]="EnablePDNS";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.recursor.install";
        $config["LOG_FILE"]=PROGRESS_DIR."/pdns.recursor.install.log";
        $config["CMD_ON"]="pdns.php?recursor-install=yes";
        $config["CMD_OFF"]="pdns.php?recursor-uninstall=yes";
        return $this->build_paragraph($config);
    }

    public function APP_DNSDIST(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsDnsAdministrator";
        $config["TITLE"]="{APP_DNSDIST}";
        $config["EXPLAIN"]="{APP_DNSDIST_EXPLAIN}";
        $config["TOKEN_INSTALLED"]="APP_DNSDIST_INSTALLED";
        $config["TOKEN"]="EnableDNSDist";
        $config["WIKI"]="https://wiki.articatech.com/en/dns/load-balancer";
        $config["CONFLICT"]=$this->IFLOCALCACHE_CONFLICTS();
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/dnsdist.install";
        $config["LOG_FILE"]=PROGRESS_DIR."/dnsdist.install.log";
        $config["CMD_ON"]="/dnsfw/service/install";
        $config["CMD_OFF"]="/dnsfw/service/uninstall";
        return $this->build_paragraph($config);
    }

    private function IFLOCALCACHE_CONFLICTS(){
                $DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));
                if($DoNotUseLocalDNSCache==1){return 0;}
            $DNSCacheListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCacheListenInterface"));
            if($DNSCacheListenInterface==null){return 0;}
            return 1;
    }

    public function APP_CYRUS(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
        if($DisableMessaging==1){return null;}
        $config["SECURITY"]="AsMailBoxAdministrator";
        $config["TITLE"]="{APP_CYRUS}";
        $config["EXPLAIN"]="{about_cyrus}";
        $config["TOKEN_INSTALLED"]="APP_CYRUS_INSTALLED";
        $config["TOKEN"]="EnableCyrusImap";
        $config["TOKEN_REQUIRE"]="{APP_OPENLDAP}";
        $config["TOKEN_REQUIRE_ENABLED"]="EnableOpenLDAP";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/cyrus.install";
        $config["LOG_FILE"]=PROGRESS_DIR."/cyrus.install.log";
        $config["CMD_ON"]="cyrus.php?install=yes";
        $config["CMD_OFF"]="cyrus.php?uninstall=yes";
        return $this->build_paragraph($config);
    }

    public function APP_OSPFD():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_OSPF}";
        $config["EXPLAIN"]="{APP_OSPF_EXPLAIN}";
        $config["TOKEN"]="EnableOSPFD";
        $config["TOKEN_INSTALLED"]="OSPFD_INSTALLED";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/ospfd.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/ospfd.log";
        $config["CMD_ON"]="ospfd.php?install=yes";
        $config["CMD_OFF"]="ospfd.php?uninstall=yes";
        return $this->build_paragraph($config);

    }

    public function APP_CGROUPS(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_CGROUPS}";
        $config["EXPLAIN"]="{howto_cgroups}";
        $config["TOKEN"]="cgroupsEnabled";
        $config["TOKEN_INSTALLED"]="CGROUPS_INSTALLED";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/cgroups.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/cgroups.install.progress.txt";
        $config["CMD_ON"]="cgroup.php?install=yes";
        $config["CMD_OFF"]="cgroup.php?uninstall=yes";
        return $this->build_paragraph($config);
    }

    public function APP_POSTGRESQL():string{
        $VirtualEnablePostGres=1;
        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        if($DisablePostGres==1){$VirtualEnablePostGres=0;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("VirtualEnablePostGres",$VirtualEnablePostGres);
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_POSTGRES}";
        $config["EXPLAIN"]="{APP_POSTGRES}";
        $config["TOKEN"]="VirtualEnablePostGres";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/postgres.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/postgres.log";
        $config["CMD_ON"]="/postgresql/install";
        $config["CMD_OFF"]="/postgresql/uninstall";
        return $this->build_paragraph($config);

    }


    public function APP_KLNAGENT(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_KLNAGENT}";
        $config["EXPLAIN"]="{APP_KLNAGENT_EXPLAIN}";
        $config["TOKEN"]="EnableKlnagent";
        $config["TOKEN_INSTALLED"]="KLNAGENT_INSTALLED";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/klnagent.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/klnagent.progress.txt";
        $config["CMD_ON"]="klnagent.php?install=yes";
        $config["CMD_OFF"]="klnagent.php?uninstall=yes";
        return $this->build_paragraph($config);
    }




    public function APP_SMOKEPING(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="SMOKEPING_INSTALLED";
        $config["TITLE"]="{APP_SMOKEPING}";
        $config["EXPLAIN"]="{APP_SMOKEPING_EXPLAIN}";
        $config["TOKEN"]="EnableSmokePing";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/smokeping.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/smokeping.log";
        $config["CMD_ON"]="smokeping.php?install=yes";
        $config["CMD_OFF"]="smokeping.php?uninstall=yes";
        return $this->build_paragraph($config);
    }



    public function SURICATA(){

        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{IDS} (IDS)";
        $config["EXPLAIN"]="{about_ids}";
        $config["TOKEN"]="EnableSuricata";
        $config["TOKEN_INSTALLED"]="SURICATA_INSTALLED";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/suricata.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/suricata.progress.txt";
        $config["CMD_ON"]="/suricata/install";
        $config["CMD_OFF"]="/suricata/uninstall";
        $config["MEMMIN"]="1999";
        return $this->build_paragraph($config);

    }
    public function ISCSI_CLIENT(){

        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_IETD}";
        $config["EXPLAIN"]="{iscsi_explain}";
        $config["TOKEN"]="EnableISCSI";
        $config["TOKEN_INSTALLED"]="ISCSI_CLIENT_INSTALLED";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/iscsi.install.prg";
        $config["LOG_FILE"]=PROGRESS_DIR."/iscsi.install.prg.log";
        $config["CMD_ON"]="iscsi.php?install=yes";
        $config["CMD_OFF"]="iscsi.php?uninstall=yes";
        return $this->build_paragraph($config);


    }
    public function NTPD_SERVER():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_NTPD}";
        $config["EXPLAIN"]="{ntp_about}";
        $config["TOKEN"]="ChronydEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/ntpd.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/ntpd.progress.log";
        $config["CMD_ON"]="/ntpd/install";
        $config["CMD_OFF"]="/ntpd/uninstall";
        return $this->build_paragraph($config);
    }


    public function RSYNC(){

        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="RSYNC_INSTALLED";
        $config["TITLE"]="{APP_RSYNC_SERVER}";
        $config["EXPLAIN"]="{APP_RSYNC_SERVER_EXPLAIN}";
        $config["TOKEN"]="EnableRsyncDaemon";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/rsync.install.prg";
        $config["LOG_FILE"]=PROGRESS_DIR."/rsync.install.log";
        $config["CMD_ON"]="/rsyncd/install";
        $config["CMD_OFF"]="/rsyncd/uninstall";
        return $this->build_paragraph($config);
    }

    public function HAPROXY(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="HAPROXY_INSTALLED";
        $config["TITLE"]="{APP_HAPROXY_SERVICE2}";
        $config["EXPLAIN"]="{APP_HAPROXY_ABOUT}";
        $config["TOKEN"]="EnableHaProxy";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/haproxy.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/haproxy.progress.txt";
        $config["CMD_ON"]="haproxy.php?install=yes";
        $config["CMD_OFF"]="haproxy.php?uninstall=yes";
        $config["MEMMIN"]="1900";
        return $this->build_paragraph($config);
    }
    public function HAPROXY_EXCHANGE(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="HAPROXY_INSTALLED";
        $config["TITLE"]="{APP_HAPROXY_EXCHANGE} (beta)";
        $config["EXPLAIN"]="{APP_HAPROXY_EXCHANGE_TEXT}";
        $config["TOKEN"]="EnableHaExchange";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/haexchnage.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/haexchnage.progress.txt";
        $config["CMD_ON"]="haexchange.php?install=yes";
        $config["CMD_OFF"]="haexchange.php?uninstall=yes";
        return $this->build_paragraph($config);
    }



    public function HA_CLUSTER(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="HAPROXY_INSTALLED";
        $config["TITLE"]="{APP_HAPROXY_CLUSTER} (HaCluster)";
        $config["EXPLAIN"]="{APP_HAPROXY_CLUSTER_EXPLAIN}";
        $config["TOKEN"]="Enablehacluster";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/hacluster.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/hacluster.progress.txt";
        $config["CMD_ON"]="/hacluster/server/install";
        $config["CMD_OFF"]="/hacluster/server/uninstall";
        $config["MEMMIN"]="1900";
        $config["LICENSE"]=True;
        return $this->build_paragraph($config);

    }

    public function APP_CISCO_REPORTER(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="FLOWD_INSTALLED";
        $config["TITLE"]="{APP_CISCO_REPORTER}";
        $config["EXPLAIN"]="{APP_CISCO_REPORTER_EXPLAIN}";
        $config["TOKEN"]="EnableCISCOReporter";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/cisco.reporter.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/cisco.reporter.progress.txt";
        $config["CMD_ON"]="cisco.php?install=yes";
        $config["CMD_OFF"]="cisco.php?uninstall=yes";
        $config["MEMMIN"]="1900";
        $config["LICENSE"]=True;
        return $this->build_paragraph($config);

    }



    public function WSUSOFFLINE(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="WSUSOFFLINE_INSTALLED";
        $config["TITLE"]="{APP_WSUSOFFLINE}";
        $config["EXPLAIN"]="{APP_WSUSOFFLINE_EXPLAIN}";
        $config["TOKEN"]="EnableWsusOffline";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/wsusoffline.install.prg";
        $config["LOG_FILE"]=PROGRESS_DIR."/wsusoffline.install.log";
        $config["CMD_ON"]="wsusoffline.php?install=yes";
        $config["CMD_OFF"]="wsusoffline.php?uninstall=yes";
        return $this->build_paragraph($config);

    }
    public function INSTANTSEARCH(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="XAPIAN_PHP_INSTALLED";
        $config["TITLE"]="{InstantSearch}";
        $config["EXPLAIN"]="{InstantSearch_explain}";
        $config["TOKEN"]="EnableXapianSearch";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/xapian.install.prg";
        $config["LOG_FILE"]=PROGRESS_DIR."/xapian.install.log";
        $config["CMD_ON"]="xapian.php?install=yes";
        $config["CMD_OFF"]="xapian.php?uninstall=yes";
        return $this->build_paragraph($config);
    }

    public function SPLUNK(){

        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_SPLUNK_FORWARDER_INSTALLED";
        $config["TITLE"]="{APP_SPLUNK_FORWARDER}";
        $config["EXPLAIN"]="{APP_SPLUNK_FORWARDER_EXPLAIN}";
        $config["TOKEN"]="SplunkForwarderEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/splunk.install.prg";
        $config["LOG_FILE"]=PROGRESS_DIR."/splunk.install.log";
        $config["CMD_ON"]="/splunk-uf/install";
        $config["CMD_OFF"]="/splunk-uf/uninstall";
        $config["MEMMIN"]="1500";
        return $this->build_paragraph($config);
    }

    public function FIREHOL(){
        $config["SECURITY"]="AsFirewallManager";
        $config["TITLE"]="{your_firewall}";
        $config["EXPLAIN"]="{your_firewall_explain}";
        $config["TOKEN"]="FireHolEnable";
        $config["PROGRESS_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.progress";
        $config["LOG_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.log";
        $config["CMD_ON"]="/firewall/install";
        $config["CMD_OFF"]="/firewall/uninstall";
        return $this->build_paragraph($config);
    }

    public function APP_LINK_BALANCER(){
        $config["TOKENS_REQUIRE_INSTALLED"]["FireHolEnable"]="your_firewall";
        $config["SECURITY"]="AsFirewallManager";
        $config["TOKEN_INSTALLED"]="APP_LINK_BALANCER_INSTALLED";
        $config["TITLE"]="{APP_LINK_BALANCER}";
        $config["EXPLAIN"]="{APP_LINK_BALANCER_EXPLAIN}";
        $config["TOKEN"]="EnableLinkBalancer";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/LinkBalancer.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/LinkBalancer.progress.log";
        $config["CMD_ON"]="firehol.php?install-link-balancer=yes";
        $config["CMD_OFF"]="firehol.php?uninstall-link-balancer=yes";
        return $this->build_paragraph($config);
    }

    public function APP_NDPI(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_NDPI_INSTALLED";


        $config["TITLE"]="{APP_NDPI}";
        $config["EXPLAIN"]="{traffic_inspection_explain}";
        $config["TOKEN"]="EnablenDPI";
        $config["PROGRESS_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.progress";
        $config["LOG_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.log";
        $config["CMD_ON"]="/firewall/ndpi/enable";
        $config["CMD_OFF"]="/firewall/ndpi/disable";
        return $this->build_paragraph($config);
    }

    public function VLANS(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_VLAN_INSTALLED";
        $config["TITLE"]="{APP_VLAN}";
        $config["EXPLAIN"]="{APP_VLAN_EXPLAIN}";
        $config["TOKEN"]="EnableVLANs";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/vlans.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/vlans.install.log";
        $config["CMD_ON"]="/system/network/vlan/install";
        $config["CMD_OFF"]="/system/network/vlan/uninstall";
        return $this->build_paragraph($config);
    }

    public function APP_3PROXY():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsFirewallManager";
        $config["TOKEN_INSTALLED"]="APP_3PROXY_INSTALLED";
        $config["TITLE"]="{APP_3PROXY}";
        $config["EXPLAIN"]="{APP_3PROXY_EXPLAIN}";
        $config["TOKEN"]="Enable3Proxy";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/3proxy.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/3proxy.progress.log";
        $config["CMD_ON"]="/3proxy/install";
        $config["CMD_OFF"]="/3proxy/uninstall";
        return $this->build_paragraph($config);

    }

    public function APP_REDSOCKS():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["TOKEN_REQUIRE_ENABLED"]="FireHolEnable";
        $config["TOKEN_REQUIRE"]="{your_firewall}";
        $config["SECURITY"]="AsFirewallManager";
        $config["TOKEN_INSTALLED"]="APP_REDSOCKS_INSTALLED";
        $config["TITLE"]="{APP_REDSOCKS}";
        $config["EXPLAIN"]="{APP_REDSOCKS_EXPLAIN}";
        $config["TOKEN"]="EnableRedSocks";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/redsocks.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/redsocks.install.log";
        $config["CMD_ON"]="/redsocks/install";
        $config["CMD_OFF"]="/redsocks/uninstall";
        return $this->build_paragraph($config);

    }
    public function APP_SSHPORTAL():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_SSHPORTAL_INSTALLED";
        $config["TITLE"]="{APP_SSHPORTAL}";
        $config["EXPLAIN"]="{APP_SSHPORTAL_EXPLAIN}";
        $config["TOKEN"]="EnableSSHPortal";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/sshportal.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/sshportal.log";
        $config["CMD_ON"]="sshd.php?install-portal=yes";
        $config["CMD_OFF"]="sshd.php?uninstall-portal=yes";
        return $this->build_paragraph($config);
    }
    public function APP_SSHPROXY():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_SSHPROXY}";
        $config["EXPLAIN"]="{APP_SSHPROXY_EXPLAIN}";
        $config["TOKEN"]="EnableSSHProxy";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/sshproxy.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/sshproxy.log";
        $config["CMD_ON"]="/ssh2proxy/install";
        $config["CMD_OFF"]="/ssh2proxy/uninstall";
        $config["WIKI"]="https://wiki.articatech.com/en/ssh/sshproxy";
        return $this->build_paragraph($config);
    }


    public function OPENSSH(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_OPENSSH}";
        $config["EXPLAIN"]="{OPENSSH_EXPLAIN}";
        $config["TOKEN"]="EnableOpenSSH";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/sshd.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/sshd.log";
        $config["CMD_ON"]="/ssh/install";
        $config["CMD_OFF"]="/ssh/uninstall";
        $config["WIKI"]="https://wiki.articatech.com/en/ssh";
        return $this->build_paragraph($config);

    }
    public function APP_META_SERVER():string{
        $ShowMeta=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ShowMeta"));
        if($ShowMeta==0){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{artica_meta_server}";
        $config["EXPLAIN"]="{artica_meta_server_explain}";
        $config["TOKEN"]="EnableArticaMetaServer";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/meta.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/meta.progress.log";
        $config["CMD_ON"]="/metasrv/install";
        $config["CMD_OFF"]="/metasrv/uninstall";
        $config["WIKI"]="https://wiki.articatech.com/metasrv";
        return $this->build_paragraph($config);

    }



    public function DHCPD(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="DHCPD_INSTALLED";
        $config["TITLE"]="{APP_DHCP}";
        $config["EXPLAIN"]="{EnableDHCPServer_text}";
        $config["TOKEN"]="EnableDHCPServer";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/dhcpd.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/dhcpd.progress.txt";
        $config["CMD_ON"]="/dhcpd/service/install";
        $config["CMD_OFF"]="/dhcpd/service/uninstall";
        $config["WIKI"]="https://wiki.articatech.com/en/network/dhcp";
        return $this->build_paragraph($config);
    }



    public function NTOPNG():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="NtopNGInstalled";
        $config["TITLE"]="{APP_NTOPNG} (ntopng)";
        $config["EXPLAIN"]="{enable_ntopng_text}";
        $config["TOKEN"]="Enablentopng";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/ntopng.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/ntopng.progress.txt";
        $config["CMD_ON"]="ntopng.php?install=yes";
        $config["CMD_OFF"]="ntopng.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function ARPD():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_ARPD}";
        $config["EXPLAIN"]="{EnableArpDaemon_explain}";
        $config["TOKEN"]="EnableArpDaemon";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/arpd.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/arpd.log";
        $config["CMD_ON"]="arpd.php?install=yes";
        $config["CMD_OFF"]="arpd.php?uninstall=yes";
        return $this->build_paragraph($config);
    }


    public function GLANCES(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        return "";
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="GLANCES_INSTALLED";
        $config["TITLE"]="{APP_GLANCES}";
        $config["EXPLAIN"]="{GLANCES_EXPLAIN}";
        $config["TOKEN"]="EnableGlances";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/glances.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/glances.progress.txt";
        $config["CMD_ON"]="glances.php?install=yes";
        $config["CMD_OFF"]="glances.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function POSTFIX():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
        if($DisableMessaging==1){return "";}
        $config["ON_CONFLICTS"]["SQUIDEnable"]="proxy_service";
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="POSTFIX_INSTALLED";
        $config["TITLE"]="{APP_POSTFIX}";
        $config["EXPLAIN"]="{APP_POSTFIX_TEXT}";
        $config["TOKEN"]="EnablePostfix";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/postfix.install";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/postfix.install.txt";
        $config["CMD_ON"]="postfix.php?install=yes";
        $config["CMD_OFF"]="postfix.php?uninstall=yes";
        $config["WIKI"]="https://wiki.articatech.com/en/smtp-service";
        return $this->build_paragraph($config);
    }


    public function APP_MAIL_SPY(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
        if($DisableMessaging==1){return null;}
        $config["TOKENS_REQUIRE_INSTALLED"]["PROCMAIL_INSTALLED"]="APP_PROCMAIL";
        $config["TOKENS_REQUIRE_INSTALLED"]["POSTFIX_INSTALLED"]="APP_POSTFIX";
        $config["TOKEN_REQUIRE_ENABLED"]="EnablePostfix";

        $config["SECURITY"]="AsPostfixAdministrator";
        $config["TOKEN_INSTALLED"]="MILTER_SPY_INSTALLED";
        $config["TITLE"]="{APP_MAILSPY}";
        $config["EXPLAIN"]="{APP_MAILSPY_EXPLAIN}";
        $config["TOKEN"]="EnableMilterSpyDaemon";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mailspy.install";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mailspy.install.txt";
        $config["CMD_ON"]="mailspy.php?install=yes";
        $config["CMD_OFF"]="mailspy.php?uninstall=yes";
        return $this->build_paragraph($config);


    }

    public function APP_FILEBEAT(){

        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_FILEBEAT_INSTALLED";
        $config["TITLE"]="{APP_FILEBEAT}";
        $config["EXPLAIN"]="{APP_FILEBEAT_EXPLAIN}";
        $config["TOKEN"]="EnableFileBeat";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/filebeat.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/filebeat.log";
        $config["CMD_ON"]="filebeat.php?install=yes";
        $config["CMD_OFF"]="filebeat.php?uninstall=yes";
        $config["LICENSE"]=true;
        return $this->build_paragraph($config);
    }


    public function APP_MIMEDEFANG(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
        if($DisableMessaging==1){return null;}
        $config["TOKENS_REQUIRE_INSTALLED"]["POSTFIX_INSTALLED"]="APP_POSTFIX";
        $config["TOKEN_REQUIRE_ENABLED"]="EnablePostfix";
        $config["SECURITY"]="AsPostfixAdministrator";
        $config["TOKEN_INSTALLED"]="MimeDefangInstalled";
        $config["TITLE"]="{APP_VALVUAD}";
        $config["EXPLAIN"]="{MIMEDEFANG_TEXT}";
        $config["TOKEN"]="MimeDefangEnabled";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress.log";
        $config["CMD_ON"]="mimedefang.php?install=yes";
        $config["CMD_OFF"]="mimedefang.php?uninstall=yes";
        $config["LICENSE"]=true;
        return $this->build_paragraph($config);
    }

    public function APP_MILTER_REGEX(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["TOKENS_REQUIRE_INSTALLED"]["POSTFIX_INSTALLED"]="APP_POSTFIX";
        $config["TOKEN_REQUIRE_ENABLED"]="EnablePostfix";

        $config["SECURITY"]="AsPostfixAdministrator";
        $config["TOKEN_INSTALLED"]="APP_MILTER_REGEX_INSTALLED";
        $config["TITLE"]="{APP_MILTER_REGEX}";
        $config["EXPLAIN"]="{milter_regex_explain}";
        $config["TOKEN"]="EnableMilterRegex";

        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-regex.reconfigure.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-regex.reconfigure.progress.log";
        $config["CMD_ON"]="milter-regex.php?install=yes";
        $config["CMD_OFF"]="milter-regex.php?uninstall=yes";
        $config["LICENSE"]=true;
        return $this->build_paragraph($config);
    }

    public function APP_MILTERGREYLIST(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["TOKENS_REQUIRE_INSTALLED"]["POSTFIX_INSTALLED"]="APP_POSTFIX";
        $config["TOKEN_REQUIRE_ENABLED"]="EnablePostfix";
        $config["SECURITY"]="AsPostfixAdministrator";
        $config["TITLE"]="{APP_MILTERGREYLIST}";
        $config["EXPLAIN"]="{APP_MILTERGREYLIST_TEXT}";
        $config["TOKEN"]="MilterGreyListEnabled";
        $config["TOKEN_INSTALLED"]="APP_MILTER_GREYLIST_INSTALLED";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.log";
        $config["CMD_ON"]="milter-greylist.php?install=yes";
        $config["CMD_OFF"]="milter-greylist.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function APP_OPENDKIM(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["TOKENS_REQUIRE_INSTALLED"]["POSTFIX_INSTALLED"]="APP_POSTFIX";
        $config["TOKEN_REQUIRE_ENABLED"]="EnablePostfix";
        $config["SECURITY"]="AsPostfixAdministrator";
        $config["TITLE"]="{APP_OPENDKIM}";
        $config["EXPLAIN"]="{APP_OPENDKIM_TEXT}";
        $config["TOKEN"]="EnableDKFilter";
        $config["TOKEN_INSTALLED"]="OPENDKIM_INSTALLED";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/opendkim.install.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/opendkim.install.log";
        $config["CMD_ON"]="opendkim.php?install=yes";
        $config["CMD_OFF"]="opendkim.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function APP_SAMBA(){

        $SAMBA_APPLIANCE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SAMBA_APPLIANCE"));
        if($SAMBA_APPLIANCE==0){
            return "";
        }
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="SAMBA_INSTALLED";
        $config["TITLE"]="{APP_SAMBA}";
        $config["EXPLAIN"]="{APP_SAMBA_TEXT}";
        $config["TOKEN"]="EnableSamba";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/samba.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/samba.install.log";
        $config["CMD_ON"]="/samba/install";
        $config["CMD_OFF"]="/samba/uninstall";
        return $this->build_paragraph($config);
    }


    public function APP_RBLDNSD(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_RBLDNSD_INSTALLED";
        $config["TITLE"]="{APP_RBLDNSD}";
        $config["EXPLAIN"]="{APP_RBLDNSD_EXPLAIN}";
        $config["TOKEN"]="APP_RBLDNSD_ENABLED";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/rbldnsd.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/rbldnsd.progress.log";
        $config["CMD_ON"]="/rbldnsd/install";
        $config["CMD_OFF"]="/rbldnsd/uninstall";
        return $this->build_paragraph($config);
    }




    public function FIREQOS(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="FIREQOS_INSTALLED";
        $config["TITLE"]="{traffic_shapping}";
        $config["EXPLAIN"]="{traffic_shapping_explain}";
        $config["TOKEN"]="EnableFireQOS";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fireqos.reconfigure.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/fireqos.reconfigure.progress.txt";
        $config["CMD_OFF"]="firehol.php?qos-remove=yes";
        $config["CMD_ON"]="firehol.php?qos-install=yes";
        return $this->build_paragraph($config);
    }

    public function OPENVPN(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="OPENVPN_INSTALLED";
        $config["TITLE"]="{APP_OPENVPN}";
        $config["EXPLAIN"]="{APP_OPENVPN_TEXT}";
        $config["TOKEN"]="EnableOpenVPNServer";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/openvpn.enable.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/openvpn.enable.log";
        $config["CMD_ON"]="openvpn.php?enable=yes";
        $config["CMD_OFF"]="openvpn.php?disable=yes";
        return $this->build_paragraph($config);
    }
    public function APP_TAILSCALE(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_TAILSCALE_INSTALLED";
        $config["TITLE"]="{APP_TAILSCALE}";
        $config["EXPLAIN"]="{APP_TAILSCALE_ABOUT}";
        $config["TOKEN"]="EnableTailScaleService";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/tailscale.enable.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/tailscale.enable.log";
        $config["CMD_ON"]="tailscale.php?install=yes";
        $config["CMD_OFF"]="tailscale.php?uninstall=yes";
        $config["WIKI"]="https://wiki.articatech.com/en/network/vpn/tailscale-setup";
        return $this->build_paragraph($config);
    }
    public function APP_SYNO_BACKUP(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_SYNO_BACKUP_INSTALLED";
        $config["TITLE"]="{APP_SYNO_BACKUP}";
        $config["EXPLAIN"]="{APP_SYNO_BACKUP_ABOUT}";
        $config["TOKEN"]="EnableSynoBackup";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/synobackup.enable.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/synobackup.enable.log";
        $config["CMD_ON"]="/syno/install";
        $config["CMD_OFF"]="/syno/uninstall";
        $APP_SYNO_BACKUP_NOKERN=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYNO_BACKUP_NOKERN"));
        $config["KERNERROR"]=$APP_SYNO_BACKUP_NOKERN;
        $config["WIKI"]="https://wiki.articatech.com/en/system/backup/synology";

        return $this->build_paragraph($config);
    }


    public function APP_URBACKUP(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_URBACKUP_INSTALLED";
        $config["TITLE"]="{APP_URBACKUP}";
        $config["EXPLAIN"]="{APP_URBACKUP_EXPLAIN}";
        $config["TOKEN"]="EnableURBackup";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/urbackup.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/urbackup.install.log";
        $config["CMD_ON"]="urbackup.php?install=yes";
        $config["CMD_OFF"]="urbackup.php?uninstall=yes";
        return $this->build_paragraph($config);
   }


    public function STRONGSWAN():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="STRONGSWAN_INSTALLED";
        $config["TITLE"]="{APP_STRONGSWAN}";
        $config["EXPLAIN"]="{APP_STRONGSWAN_TEXT}";
        $config["TOKEN"]="EnableStrongswanServer";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.enable.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/strongswan.enable.log";
        $config["CMD_ON"]="strongswan.php?enable=yes";
        $config["CMD_OFF"]="strongswan.php?disable=yes";
        $config["WIKI"]="https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec";
        return $this->build_paragraph($config);
    }

    //KEEPALIVED
    public function KEEPALIVED()
    {
        $config["SECURITY"] = "AsSystemAdministrator";
        $config["TOKEN_INSTALLED"] = "APP_KEEPALIVED_INSTALLED";
        $config["TITLE"] = "{APP_KEEPALIVED} {keepalived_master}";
        $config["EXPLAIN"] = "{keepalived_what_is}";
        $config["TOKEN"] = "APP_KEEPALIVED_ENABLE";
        $config["PROGRESS_FILE"] = PROGRESS_DIR."/keepalived.progress";
        $config["LOG_FILE"] = PROGRESS_DIR."/keepalived.log";
        $config["LICENSE"] = true;
        $config["CMD_ON"] = "keepalived.php?enable=yes";
        $config["CMD_OFF"] = "keepalived.php?disable=yes";
        return $this->build_paragraph($config);
    }
    //END KEEPALIVED

    //KEEPALIVED
    public function KEEPALIVED_SLAVE()
    {
        $config["SECURITY"] = "AsSystemAdministrator";
        $config["TOKEN_INSTALLED"] = "APP_KEEPALIVED_INSTALLED";
        $config["TITLE"] = "{APP_KEEPALIVED} {keepalived_secondary_nodes}";
        $config["EXPLAIN"] = "{keepalived_what_is}";
        $config["TOKEN"] = "APP_KEEPALIVED_ENABLE_SLAVE";
        $config["PROGRESS_FILE"] = PROGRESS_DIR."/keepalived.progress";
        $config["LOG_FILE"] = PROGRESS_DIR."/keepalived.log";
        $config["LICENSE"] = true;
        $config["CMD_ON"] = "keepalived.php?enable-secondary_node=yes";
        $config["CMD_OFF"] = "keepalived.php?disable-secondary_node=yes";
        return $this->build_paragraph($config);
    }

    //END KEEPALIVED

    public function AUTOFS()
    {
        $config["SECURITY"] = "AsSystemAdministrator";
        $config["TOKEN_INSTALLED"] = "AutoFSInstalled";
        $config["TITLE"] = "{automount_center}";
        $config["EXPLAIN"] = "{autofs_about}";
        $config["TOKEN"] = "AutoFSEnabled";
        $config["PROGRESS_FILE"] = PROGRESS_DIR."/autofs.install.progress";
        $config["LOG_FILE"] = PROGRESS_DIR."/autofs.install.progress.txt";
        $config["CMD_ON"] = "/autofs/install";
        $config["CMD_OFF"] = "/autofs/uninstall";
        return $this->build_paragraph($config);
    }

    public function NETDATA(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="NetDataInstalled";
        $config["TITLE"]="{APP_NETDATA} (NetData)";
        $config["EXPLAIN"]="{APP_NETDATA_EXPLAIN}";
        $config["TOKEN"]="NetDATAEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/netdata.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/netdata.install.log";
        $config["CMD_ON"]="/netdata/install";
        $config["CMD_OFF"]="/netdata/uninstall";
        return $this->build_paragraph($config);
    }

    public function DARKSTAT(){
        $config["SECURITY"]="AsSystemAdministrator";
        //$config["TOKEN_INSTALLED"]="NetDataInstalled";
        $config["TITLE"]="{APP_DARKSTAT}";
        $config["EXPLAIN"]="{APP_DARKSTAT_EXPLAIN}";
        $config["TOKEN"]="EnableDarkStat";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/darkstat.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/darkstat.progress.txt";
        $config["CMD_ON"]="darkstat.php?enable=yes";
        $config["CMD_OFF"]="darkstat.php?disable=yes";
        return $this->build_paragraph($config);
    }

    public function APP_IWCONFIG(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="IWCONFIG_INSTALLED";
        $config["TITLE"]="{APP_IWCONFIG}";
        $config["EXPLAIN"]="{APP_IWCONFIG_EXPLAIN}";
        $config["TOKEN"]="EnableIwConfig";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/iwlwifi.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/iwlwifi.progress.txt";
        $config["CMD_ON"]="iwconfig.php?install=yes";
        $config["CMD_OFF"]="iwconfig.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function APP_PRADS(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_PRADS_INSTALLED";
        $config["TITLE"]="{APP_PRADS}";
        $config["EXPLAIN"]="{APP_PRADS_EXPLAIN}";
        $config["TOKEN"]="EnablePrads";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/prads.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/prads.log";
        $config["CMD_ON"]="prads.php?install=yes";
        $config["CMD_OFF"]="prads.php?uninstall=yes";
        return $this->build_paragraph($config);
    }

    public function APP_STATS_COMMUNICATOR(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TITLE"]="{APP_STATS_COMMUNICATOR}";
        $config["EXPLAIN"]="{APP_STATS_COMMUNICATOR_EXPLAIN}";
        $config["TOKEN"]="EnableStatsCommunicator";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/stats-com.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/stats-com.log";
        $config["CMD_ON"]="statscom.php?install=yes";
        $config["CMD_OFF"]="statscom.php?uninstall=yes";
        return $this->build_paragraph($config);
    }



    public function IWLWIFI():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="IWLWIFI_INSTALLED";
        $config["TITLE"]="{APP_WIFI_INTEL}";
        $config["EXPLAIN"]="{APP_WIFI_INTEL_EXPLAIN}";
        $config["TOKEN"]="EnableIwlwifi";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/iwlwifi.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/iwlwifi.progress.txt";
        $config["CMD_ON"]="iwlwifi.php?install=yes";
        $config["CMD_OFF"]="iwlwifi.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function PULSEREVERSE():string{
        $ShowPulseReverse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ShowPulseReverse"));
        if($ShowPulseReverse==0){return "";}
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="HAPROXY_INSTALLED";
        $config["TITLE"]="{APP_PULSE_REVERSE}";
        $config["EXPLAIN"]="{APP_PULSE_REVERSE_EXPLAIN}";
        $config["TOKEN"]="EnablePulseReverse";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/pulsereverse.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/pulsereverse.progress.txt";
        $config["CMD_ON"]="/pulsereverse/install";
        $config["CMD_OFF"]="/pulsereverse/uninstall";
        $config["WIKI"]="https://wiki.articatech.com/en/pulsereverse";
        $config["MEMMIN"]="1000";
        return $this->build_paragraph($config);
    }

    public function SQUID():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="SQUID_INSTALLED";
        $config["TITLE"]="{proxy_service}";
        $config["EXPLAIN"]="{proxy_service_about}";
        $config["TOKEN"]="SQUIDEnable";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/squid.disable.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/squid.disable.progress.txt";
        $config["CMD_ON"]="/proxy/install";
        $config["CMD_OFF"]="/proxy/uninstall";
        $config["WIKI"]="https://wiki.articatech.com/en/proxy-service";
        $config["MEMMIN"]="1000";
        return $this->build_paragraph($config);
    }


    public function APP_NGINX():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_NGINX_INSTALLED";
        $config["TITLE"]="{APP_NGINX2}";
        $config["EXPLAIN"]="{enable_nginx_text}";
        $config["TOKEN"]="EnableNginx";
        $config["CMD_ON"]="/nginx/install";
        $config["CMD_OFF"]="/nginx/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/nginx-enable.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/nginx-enable.log";
        $config["WIKI"]="https://wiki.articatech.com/en/reverse-proxy";
        $config["MEMMIN"]="2000";
        return $this->build_paragraph($config);
    }




    public function APP_CENTRAL_NODE(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_NGINX_INSTALLED";
        $config["TITLE"]="{central_node}";
        $config["EXPLAIN"]="{central_node_explain}";
        $config["TOKEN"]="EnableCentralNode";
        $config["CMD_ON"]="cluster.php?enable-central-node=yes";
        $config["CMD_OFF"]="cluster.php?disable-central-node=yes";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/central-node.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/disable-central-node.log";
        $config["MEMMIN"]="2000";
        return $this->build_paragraph($config);


    }

    public function APP_NETMONIX():string{
        $ShowNetMonix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ShowNetMonix"));
        if($ShowNetMonix==0){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="NetMonixInstalled";
        $config["TITLE"]="NetMonix";
        $config["EXPLAIN"]="{NetMonix_explain}";
        $config["TOKEN"]="EnableNetMonix";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/netmonix.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/netmonix.log";
        $config["CMD_ON"]="/netmonix/install";
        $config["CMD_OFF"]="/netmonix/uninstall";
        return $this->build_paragraph($config);

    }

    public function APP_CLAMAV(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="ClamAVDaemonInstalled";
        $config["TITLE"]="{APP_CLAMAV}";
        $config["EXPLAIN"]="{APP_CLAMAV_TEXT}";
        $config["TOKEN"]="EnableClamavDaemon";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/clamd.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/clamd.progress.logs";
        $config["CMD_ON"]="/clamd/install";
        $config["CMD_OFF"]="/clamd/uninstall";
        $config["MEMMIN"]="2000";
        return $this->build_paragraph($config);
    }
    public function APP_DOCKER():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $APP_DOCKER_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_INSTALLED"));
        if($APP_DOCKER_INSTALLED==0){return "";}
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_DOCKER_INSTALLED";
        $config["TITLE"]="{APP_DOCKER}";
        $config["EXPLAIN"]="{APP_DOCKER_EXPLAIN}";
        $config["TOKEN"]="EnableDockerService";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/docker.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/docker.progress.logs";
        $config["CMD_ON"]="docker.php?install=yes";
        $config["CMD_OFF"]="docker.php?uninstall=yes";
        $config["MEMMIN"]="2000";
        return $this->build_paragraph($config);

    }
    public function APP_REDIS_SERVER(){
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_REDIS_SERVER_INSTALLED";
        $config["TITLE"]="{APP_REDIS_SERVER} (Redis)";
        $config["EXPLAIN"]="{APP_REDIS_SERVER_TEXT}";
        $config["TOKEN"]="EnableRedisService";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/redis.install";
        $config["LOG_FILE"]=PROGRESS_DIR."/redis.progress.logs";
        $config["CMD_ON"]="/redis/install";
        $config["CMD_OFF"]="/redis/uninstall";
        return $this->build_paragraph($config);

    }
    public function APP_KEA_DHCPD4(){
        $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
        if($DEBIAN_VERSION<12){return "";}
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="KEA_INSTALLED";
        $config["TITLE"]="{APP_KEA_DHCPD4}";
        $config["EXPLAIN"]="{APP_KEA_DHCPD4_TEXT}";
        $config["TOKEN"]="EnableKEA";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/kea.service.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/kea.service.progress.log";
        $config["CMD_ON"]="/kea/install";
        $config["CMD_OFF"]="/kea/uninstall";
        return $this->build_paragraph($config);
    }



    public function APP_DHCP_RELAY():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_DHCP_RELAY_INSTALLED";
        $config["TITLE"]="{APP_DHCP_RELAY}";
        $config["EXPLAIN"]="{APP_DHCP_RELAY_EXPLAIN}";
        $config["TOKEN"]="EnableDHCPRelay";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/dhcrelay.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/dhcrelay.progress.logs";
        $config["CMD_ON"]="/dhcpd/relay/install";
        $config["CMD_OFF"]="/dhcpd/relay/uninstall";
        $config["WIKI"]="https://wiki.articatech.com/network/dhcp/dhcp-relay";
        return $this->build_paragraph($config);

    }


    public function CLOUD_CATEGORIES(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        return null;
        $config["LICENSE"]=true;
        $config["CMD_ON"]="filebeat.php?cloud-install=yes";
        $config["CMD_OFF"]="filebeat.php?cloud-uninstall=yes";
        $config["TOKEN"]="UseCloudArticaCategories";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/filebeat.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/filebeat.log";
        $config["TITLE"]="{UseCloudArticaCategories} ({categories})";
        $config["EXPLAIN"]="{UseCloudArticaCategories_explain}";
        return $this->build_paragraph($config);
    }

    public function APP_ARTICALOGGER(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $SquidInRouterMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
        if($SquidInRouterMode==1){return;}
        $config["LICENSE"]=true;
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="SQUIDEnable";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["TITLE"]="{APP_ARTICALOGGER}";
        $config["EXPLAIN"]="{APP_ARTICALOGGER_EXPLAIN}";
        $config["TOKEN"]="EnableSquidLogger";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/SquidLogger.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/SquidLogger.progress.log";
        $config["CMD_ON"]="squidlogger.php?install=yes";
        $config["CMD_OFF"]="squidlogger.php?uninstall=yes";
        return $this->build_paragraph($config);
    }

    public function SQUID_MIKROTIK(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="SQUIDEnable";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["TITLE"]="{APP_MIKROTIK_SUPPORT}";
        $config["EXPLAIN"]="{APP_MIKROTIK_SUPPORT_EXPLAIN}";
        $config["TOKEN"]="SquidMikrotikEnabled";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress.log";
        $config["CMD_ON"]="mikrotik.php?install=yes";
        $config["CMD_OFF"]="mikrotik.php?uninstall=yes";
        return $this->build_paragraph($config);

    }

    public function SQUID_ACLS():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="SQUIDEnable";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["TITLE"]="{WAF_LONG} (Proxy ACLs)";
        $config["EXPLAIN"]="{PROXY_ACLS_EXPLAIN}";
        $config["TOKEN"]="SQUIDACLsEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.enable.acls.progress";
        $config["LOG_FILE"]=PROGRESS_DIR ."/squid.enable.acls.progress.log";
        $config["CMD_ON"]="/proxy/acls/enable";
        $config["CMD_OFF"]="/proxy/acls/disable";
        return $this->build_paragraph($config);
    }
    public function IT_CHARTERS():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        return "";
    }








    public function APP_CLUSTER_INFRA(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["LICENSE"]=true;
        $config["TOKENS_REQUIRE_INSTALLED"]["SQUIDEnable"]="APP_SQUID";
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TITLE"]="{APP_CLUSTER_INFRA}";
        $config["EXPLAIN"]="{APP_CLUSTER_INFRA_EXPLAIN}";
        $config["TOKEN"]="EnableClusterInfra";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/ClusterInfra.progress";
        $config["CMD_ON"]="cluster.php?enable-infra=yes";
        $config["CMD_OFF"]="cluster.php?disable-infra=yes";
        return $this->build_paragraph($config);
    }





    public function SQUID_RESTFULL(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["LICENSE"]=true;
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="SQUIDEnable";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["TITLE"]="{PROXY_RESTFULL}";
        $config["EXPLAIN"]="{PROXY_RESTFULL_EXPLAIN}";
        $config["TOKEN"]="SQUIDRESTFulEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
        $config["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
        $config["CMD_ON"]="squid2.php?enable-restful-service=yes";
        $config["CMD_OFF"]="squid2.php?disable-restful-service=yes";
        return $this->build_paragraph($config);
    }


    public function DWAGENT():string{

        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_DWAGENT}";
        $config["EXPLAIN"]="{APP_DWAGENT_EXPLAIN}";
        $config["TOKEN"]="DWAgentEnable";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/dwagent.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/dwagent.log";
        $config["CMD_ON"]="dwagent.php?install=yes";
        $config["CMD_OFF"]="dwagent.php?uninstall=yes";
        return $this->build_paragraph($config);

}

    public function SYSTEM_RESTFULL(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["LICENSE"]=true;
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{SYSTEM_RESTFULL}";
        $config["EXPLAIN"]="{SYSTEM_RESTFULL_EXPLAIN}";
        $config["TOKEN"]="EnableRESTFulSystem";
        $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/system.restful";
        $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/system.restful.log";
        $config["CMD_ON"]="netdata.php?restfull-install=yes";
        $config["CMD_OFF"]="netdata.php?restfull-uninstall=yes";
        return $this->build_paragraph($config);
    }

    public function SQUID_PARENTS(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="SQUIDEnable";
        $config["TITLE"]="{squid_parent_feature}";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["EXPLAIN"]="{squid_parent_feature_explain}";
        $config["TOKEN"]="EnableParentProxy";
        $config["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.parents.progress";
        $config["LOG_FILE"]=PROGRESS_DIR ."/squid.parent.progress.txt";
        $config["CMD_ON"]="/proxy/parents/install"; //
        $config["CMD_OFF"]="/proxy/parents/uninstall";
        return $this->build_paragraph($config);
    }

    public function LM_SENSORS(){

    }

    public function APP_WANPROXY(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="WANPROXY_INSTALLED";
        $config["TOKEN"]="WanProxyEnabled";
        $config["TITLE"]="{APP_WANPROXY}";
        $config["EXPLAIN"]="{WANPROXY_ABOUT}";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/wanproxy.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/wanproxy.progress.log";
        $config["CMD_ON"]="wanproxy.php?install=yes";
        $config["CMD_OFF"]="wanproxy.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function APP_GOSHIELD(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN"]="Go_Shield_Server_Enable";
        $config["TITLE"]="{filtering_service}";
        $config["EXPLAIN"]="{filtering_service_explain}";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/go.shield.server.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/go.shield.server.log";
        $config["CMD_ON"]="/goshield/install";
        $config["CMD_OFF"]="/goshield/uninstall";
        return $this->build_paragraph($config);
    }
    public function APP_OPENVSWITCH():string{
        $config["TOKEN_INSTALLED"]="APP_OPENVSWITCH_INSTALLED";
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN"]="OpenVswitchEnable";
        $config["TITLE"]="{APP_OPENVSWITCH}";
        $config["EXPLAIN"]="{APP_OPENVSWITCH_EXPLAIN}";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/OpenVswitch.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/OpenVswitch.log";
        $config["CMD_ON"]="/openvswitch/install";
        $config["CMD_OFF"]="/openvswitch/uninstall";
        return $this->build_paragraph($config);
    }
    public function APP_FIRECRACKER():string{
        $config["TOKEN_INSTALLED"]="APP_FIRECRACKER_INSTALLED";
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN"]="EnableFirecracker";
        $config["TITLE"]="{APP_FIRECRACKER}";
        $config["EXPLAIN"]="{APP_FIRECRACKER_EXPLAIN}";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/firecracker.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/firecracker.progress";
        $config["CMD_ON"]="/firecracker/install";
        $config["CMD_OFF"]="/firecracker/uninstall";
        return $this->build_paragraph($config);
    }

    function APP_ZIPROXY():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $SquidInRouterMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
        if($SquidInRouterMode==1){return "";}
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="ZIPROXY_INSTALLED";
        $config["TOKEN"]="EnableProxyCompressor";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["TOKEN_REQUIRE_INSTALLED"]="SQUID_INSTALLED";
        $config["TOKEN_REQUIRE_ENABLED"]="SQUIDEnable";
        $config["TITLE"]="{APP_ZIPROXY}";
        $config["EXPLAIN"]="{APP_ZIPROXY_ABOUT}";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/zipproxy.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/zipproxy.progress.log";
        $config["CMD_ON"]="zipproxy.php?install=yes";
        $config["CMD_OFF"]="zipproxy.php?uninstall=yes";
        return $this->build_paragraph($config);

    }
    public function PROXY_PAC():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TITLE"]="{APP_PROXY_PAC} (wpad,proxy.pac)";
        $config["TOKEN"]="EnableProxyPac";
        $config["EXPLAIN"]="{wpad_service_explain}";
        $config["CMD_ON"]="/proxypac/install";
        $config["CMD_OFF"]="/proxypac/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/squid-autoconf.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/autoconfiguration.apply.log";
        return $this->build_paragraph($config);
    }

    public function ACTIVEDIRECTORY():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["LICENSE"]=true;
        $config["TITLE"]="Active Directory";
        $config["TOKEN_REQUIRE"]="{APP_MSKTUTIL}";
        $config["TOKEN_REQUIRE_INSTALLED"]="MSKTUTIL_INSTALLED";
        $config["EXPLAIN"]="{feature_active_directory_explain}";
        $config["TOKEN"]="EnableActiveDirectoryFeature";
        $config["CMD_ON"]="/activedirectory/install";
        $config["CMD_OFF"]="/activedirectory/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/ActiveDirectoryFeature.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/ActiveDirectoryFeature.log";
        $config["WIKI"]="https://wiki.articatech.com/active-directory";
        return $this->build_paragraph($config);
    }

    public function ACTIVEDIRECTORY_AD_AGENT():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }

        if(!is_file("/usr/local/sbin/ad-agent-lbl")){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ADAGENT_INSTALLED", "0");
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["LICENSE"]=true;
        $config["TITLE"]="{APP_ADAGENT_LBL}";
        $config["TOKEN_REQUIRE"]="Active Directory";
        $config["TOKEN_INSTALLED"]="ADAGENT_INSTALLED";
        $config["TOKEN_REQUIRE_ENABLED"]="EnableActiveDirectoryFeature";
        $config["TOKEN"]="EnableExternalACLADAgent";
        $config["EXPLAIN"]="{AD_GROUP_SEARCH_SIMPLE_EXPLAIN}";
        $config["CMD_ON"]="adagent.php?install=yes";
        $config["CMD_OFF"]="adagent.php?uninstall=yes";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/adagent.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/adagent.log";
        $config["WIKI"]="https://wiki.articatech.com/en/proxy-service/authentication/ad-agent";
        return $this->build_paragraph($config);
    }

    public function APP_MYSQL():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_MYSQL_INSTALLED";
        $config["TITLE"]="{APP_MYSQL}";
        $config["EXPLAIN"]="{APP_MYSQL_ABOUT}";
        $config["TOKEN"]="EnableMySQL";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/mysql.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/mysql.install.progress.log";
        $config["CMD_ON"]="mysql.php?install=yes";
        $config["CMD_OFF"]="mysql.php?uninstall=yes";
        return $this->build_paragraph($config);
    }
    public function APP_MANTICORE():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_MANTICORE_INSTALLED";
        $config["TITLE"]="{APP_MANTICORE}";
        $config["EXPLAIN"]="{APP_MANTICORE_ABOUT}";
        $config["TOKEN"]="MantiCoreSearchEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/manticore.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/manticore.install.progress.log";
        $config["CMD_ON"]="manticore.php?install=yes";
        $config["CMD_OFF"]="manticore.php?uninstall=yes";
        $config["WIKI"]="https://wiki.articatech.com/en/system/manticoresearch";
        return $this->build_paragraph($config);
    }




    public function APP_GREENSQL():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["TOKENS_REQUIRE_INSTALLED"]["EnableMySQL"]="APP_MYSQL";
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TOKEN_INSTALLED"]="APP_GREENSQL_INSTALLED";
        $config["TITLE"]="{APP_GREENSQL}";
        $config["EXPLAIN"]="{APP_GREENSQL_ABOUT}";
        $config["TOKEN"]="EnableGreenSQL";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/greensql.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/greensql.install.progress.log";
        $config["CMD_ON"]="greensql.php?install=yes";
        $config["CMD_OFF"]="greensql.php?uninstall=yes";
        return $this->build_paragraph($config);
    }

    public function APP_SYNCTHING():string{
        return "";
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_SYNCTHING}";
        $config["TOKEN_INSTALLED"]="SyncThingInstalled";
        $config["TOKEN_REQUIRE"]="Web Console 1.28.x";
        $config["TOKEN_REQUIRE_ENABLED"]="APP_NGINX_CONSOLE_SUB_MODULE";
        $config["EXPLAIN"]="{enable_syncthing_text}";
        $config["TOKEN"]="EnableSyncThing";
        $config["CMD_ON"]="/syncthing/install";
        $config["CMD_OFF"]="/syncthing/uninstall";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/syncthing.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/syncthing.progress";
       // $config["WIKI"]="https://wiki.articatech.com/en/reverse-proxy";
        $config["MEMMIN"]="1500";
        return $this->build_paragraph($config);
    }

    public function SQUID_WCCP():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="SQUIDEnable";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["TITLE"]="{WCCP_NAME} (WCCP)";
        $config["EXPLAIN"]="{WCCP_NAME_EXPLAIN}";
        $config["TOKEN"]="SquidWCCPEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/squid.wccp.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/squid.wccp.log";
        $config["CMD_ON"]="/proxy/wccp/install";
        $config["CMD_OFF"]="/proxy/wccp/uninstall";
        return $this->build_paragraph($config);
    }


    public function SQUID_CACHE():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $SquidInRouterMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
        if($SquidInRouterMode==1){return "";}
        $config["SECURITY"]="AsSquidAdministrator";
        $config["TOKEN_INSTALLED"]="SQUID_INSTALLED";
        $config["TOKEN_REQUIRE"]="{proxy_service}";
        $config["TITLE"]="{PROXY_CACHE_FEATURE}";
        $config["LICENSE"]=true;
        $config["EXPLAIN"]="{PROXY_CACHE_FEATURE_EXPLAIN}";
        $config["TOKEN"]="SquidCachesProxyEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
        $config["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
        $config["CMD_ON"]="squid2.php?install-cache-service=yes";
        $config["CMD_OFF"]="squid2.php?disable-cache-service=yes";
        $config["MEMMIN"]="1500";
        return $this->build_paragraph($config);
    }


    public function UFDBGUARD():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $SquidInRouterMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
        if($SquidInRouterMode==1){return "";}
        $config["SECURITY"]="AsDansGuardianAdministrator";
        $config["TOKEN_INSTALLED"]="APP_UFDBGUARD_INSTALLED";
        $config["TITLE"]="{APP_UFDBGUARD}";
        $config["EXPLAIN"]="{ufdbgdb_explain}";
        $config["TOKEN"]="EnableUfdbGuard";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/ufdb.enable.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/ufdb.enable.progress.log";

        $config["CMD_ON"]="/ufdb/install";
        $config["CMD_OFF"]="/ufdb/uninstall";
        $config["MEMMIN"]="2500";
        return $this->build_paragraph($config);
    }




    public function PERSONAL_CATEGORIES():string{
       return "";
    }

    public function ADMIN_TRACK():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{track_administrators}";
        $config["EXPLAIN"]="{log_administrators_operations}";
        $config["LICENSE"]=true;
        $config["TOKEN"]="TrackAdmins";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/TrackAdmins.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/TrackAdmins.log";
        $config["CMD_ON"]="system.php?TrackAdmins-install=yes";
        $config["CMD_OFF"]="system.php?TrackAdmins-uninstall=yes";
        return $this->build_paragraph($config);

    }

    public function APP_CLUSTER_MASTER():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="Cluster ({master_mode})";
        $config["EXPLAIN"]="{pdns_master_mode_explain}";
        $config["LICENSE"]=true;
        $config["TOKEN"]="PowerDNSEnableClusterMaster";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/artica.cluster.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/artica.cluster.log";
        $config["CMD_ON"]="/cluster/server/install";
        $config["CMD_OFF"]="/cluster/server/uninstall";
        return $this->build_paragraph($config);
    }
    public function APP_CLUSTER_SLAVE():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="Cluster ({slave_mode})";
        $config["EXPLAIN"]="{pdns_slave_mode_explain}";
        $config["LICENSE"]=true;
        $config["TOKEN"]="PowerDNSEnableClusterSlave";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/artica.cluster.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/artica.cluster.log";
        $config["CMD_ON"]="/cluster/client/install";
        $config["CMD_OFF"]="/cluster/client/uninstall";
        return $this->build_paragraph($config);
    }

    public function APP_NAGIOS_CLIENT():string{
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_NAGIOS_CLIENT}";
        $config["EXPLAIN"]="{APP_NAGIOS_CLIENT_EXPLAIN}";
        $config["TOKEN_INSTALLED"]="APP_NAGIOS_CLIENT_INSTALLED";
        $config["WIKI"]="https://wiki.articatech.com/en/system/monitoring/nagios";
        $config["TOKEN"]="EnableNagiosClient";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/nagios.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/nagios.progress.log";
        $config["CMD_ON"]="/nagios/install";
        $config["CMD_OFF"]="/nagios/uninstall";
        return $this->build_paragraph($config);
    }
    public function CICAP():string{
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $SquidInRouterMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
        if($SquidInRouterMode==1){return "";}
        $config["SECURITY"]="AsDansGuardianAdministrator";
        $config["TOKEN_INSTALLED"]="C_ICAP_INSTALLED";
        $config["TITLE"]="{SERVICE_WEBAVEX}";
        $config["EXPLAIN"]="{ACTIVATE_ICAP_AV_TEXT}";
        $config["TOKEN"]="CicapEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/cicap.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/cicap.install.log";
        $config["CMD_ON"]="/cicap/install";
        $config["CMD_OFF"]="/cicap/uninstall";
        $config["MEMMIN"]="1200";
        return $this->build_paragraph($config);

    }

    public function APP_KWTS_CONNECTOR(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsDansGuardianAdministrator";
        $config["TOKENS_REQUIRE_INSTALLED"]["SQUIDEnable"]="proxy_service";
        $config["TOKEN_INSTALLED"]="SQUID_INSTALLED";
        $config["TITLE"]="{APP_KWTS_CONNECTOR}";
        $config["EXPLAIN"]="{APP_KWTS_CONNECTOR_ABOUT}";
        $config["TOKEN"]="KWTSEnabled";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/kwts.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/kwts.log";
        $config["CMD_ON"]="cicap.php?kwts-install=yes";
        $config["CMD_OFF"]="cicap.php?kwts-uninstall=yes";
        return $this->build_paragraph($config);

    }

    public function DNSCATS(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsDansGuardianAdministrator";
        $config["TITLE"]="{APP_UFDBCAT}";
        $config["EXPLAIN"]="{APP_UFDBCAT_EXPLAIN}";
        $config["TOKEN"]="EnableLocalUfdbCatService";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/dnscatz.install.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/dnscatz.install.progress.log";
        $config["CMD_ON"]="/dnscatz/install";
        $config["CMD_OFF"]="/dnscatz/uninstall";
        $config["MEMMIN"]="1500";
        $config["LICENSE"]=true;
        return $this->build_paragraph($config);
    }

    public function APP_PPTP_CLIENT(){
        if($this->ASReverseProxyAppliance){
            return "";
        }
        $config["SECURITY"]="AsSystemAdministrator";
        $config["TITLE"]="{APP_PPTP_CLIENT}";
        $config["EXPLAIN"]="{APP_PPTP_CLIENT_EXPLAIN}";
        $config["TOKEN"]="EnablePPTPClient";
        $config["PROGRESS_FILE"]=PROGRESS_DIR."/pptp-client.progress";
        $config["LOG_FILE"]=PROGRESS_DIR."/pptp-client.progress.log";
        $config["CMD_ON"]="pptp.client.php?install=yes";
        $config["CMD_OFF"]="pptp.client.php?uninstall=yes";
        $config["MEMMIN"]="550";
        $config["LICENSE"]=false;
        return $this->build_paragraph($config);

    }
}