<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die();}
clean_xss_deep();
xgen();


function xgen(){
    $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    $users=new usersMenus();
    $tpl=new template_admin();
    $login="fw.login.php";

    $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
    if($SQUIDEnable==0){$users->SQUID_INSTALLED=false;}
    $SquidInRouterMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));


    $f[]="<div class=\"row border-bottom\">";
    $f[]="            <nav class=\"navbar navbar-static-top white-bg\" role=\"navigation\" style=\"margin-bottom: 0\">";
    $f[]="                <div class=\"navbar-header\">";
    $f[]="                    <a class=\"navbar-minimalize minimalize-styl-2 btn btn-primary\" href=\"#\" OnClick=\"$('body').toggleClass('mini-navbar');SmoothlyMenu();\">
							<i class=\"fa fa-bars\"></i> </a>";
    $SEARCHBARR=true;
    if($users->isWebStatisticsAdministratorOnly()){$SEARCHBARR=false;}

    if($SEARCHBARR) {
        $f[] = "
<div class='navbar-form-custom' role='search'>
    <div class='form-group'>
        <input type='text' id='top-search' name='top-search' class='form-control' placeholder='{search}' 
       OnKeyPress=\"GlobalSearchEngine(event);\">
    </div>
    </div>";
    }



    $f[]="<script>
function GlobalSearchEngine(e){
	if(!checkEnter(e)){return;}
	var Search=encodeURIComponent(document.getElementById('top-search').value);
	LoadAjax('MainContent','fw.top.search.php?search='+Search);
		
}
</script>
";
    $f[]="                    ";
    $f[]="                </div>";
    $f[]="                <ul class=\"nav navbar-top-links navbar-right\">";

    $glances_url=null;$glances_urlend=null;
    $EnableGlances=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
    $EnableWebHTOP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebHTOP"));
    if($EnableGlances==1) {
        $OnMouse[]= "OnClick=\"s_PopUpFull('/glances/',1024,768,'Monitor');\"";
        $OnMouse[]="OnMouseOver=\";this.style.cursor='pointer';\"";
        $OnMouse[]="OnMouseOut=\";this.style.cursor='default';\"";
        $glances_url=@implode(" ", $OnMouse);
    }
    if($EnableWebHTOP==1){
        $OnMouse[]= "OnClick=\"s_PopUp('/htop/',1024,768,'Monitor');\"";
        $OnMouse[]="OnMouseOver=\";this.style.cursor='pointer';\"";
        $OnMouse[]="OnMouseOut=\";this.style.cursor='default';\"";
        $glances_url=@implode(" ", $OnMouse);

    }

    $f[] = "<li $glances_url style=\"list-style: none;\">
    <div id=\"mini-bars\" style=\"display: flex; align-items: center; gap: 10px; color:#999c9e;font-weight:bold\">
        <div class=\"bar-line\"  style=\"display: flex; align-items: center; gap: 5px;\">
            <div style=\"width: 30px; text-align: right;\" id='top-cpu-text' class='text-muted'>CPU</div>
            <div>|</div>
            <div class=\"bar\" style=\"width: 80px; background: #eee; border-radius: 4px; overflow: hidden; height: 14px; position: relative; display: flex; align-items: center; justify-content: center;\">
                <div id=\"cpu-fill\" class=\"fill\" style=\"height: 100%; background: #4caf50; width: 0%; position: absolute; left: 0; top: 0; transition: width 0.3s;\"></div>
                <div id=\"cpu-percent\" class=\"percentage\" style=\"position: relative; z-index: 2; font-weight: bold; font-size: 10px; color: #000;\">0%</div>
            </div>
        </div>
        <div class=\"bar-line\" style=\"display: flex; align-items: center; gap: 5px;\">
            <div style=\"width: 30px; text-align: right;\" id='top-ram-text' class='text-muted'>RAM</div>
            <div>|</div>
            <div class=\"bar\" style=\"width: 80px; background: #eee; border-radius: 4px; overflow: hidden; height: 14px; position: relative; display: flex; align-items: center; justify-content: center;\">
                <div id=\"mem-fill\" class=\"fill\" style=\"height: 100%; background: #4caf50; width: 0%; position: absolute; left: 0; top: 0; transition: width 0.3s;\"></div>
                <div id=\"mem-percent\" class=\"percentage\" style=\"position: relative; z-index: 2; font-weight: bold; font-size: 10px; color: #000;\">0%</div>
            </div>
        </div>
    </div>
</li>";
    
    
    $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
    $EnableDNSFilterd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterd"));
    $UnboundInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundInstalled"));
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $EnableUnboundLogQueries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundLogQueries"));
    $EnableSquidAnalyzer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidAnalyzer"));
    $EnablePrads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePrads"));
    $DHCPDInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInstalled"));
    $EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $LegallogServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegallogServer"));
    $EnableDNSFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFirewall"));
    $EnableSmokePing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSmokePing"));

    $SHOW_REALTIME_PROXY=true;
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
    $LogsWarninStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop"));
    $EnablePulseReverse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePulseReverse"));
    $HaClusterRemoveRealtimeLogs=0;

    if($HaClusterClient==1) {
        $HaClusterGBConfig = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
        if(!is_array($HaClusterGBConfig)){$HaClusterGBConfig=array();}

        if(!isset($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"])){
            $HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]=0;
        }
        $HaClusterRemoveRealtimeLogs=intval($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]);
        if($HaClusterRemoveRealtimeLogs==1){$SHOW_REALTIME_PROXY=false;}
    }
    if($SquidNoAccessLogs==1){$SHOW_REALTIME_PROXY=false;}
    if($LogsWarninStop==1){$SHOW_REALTIME_PROXY=false;}
    if($SQUIDEnable==0){$SHOW_REALTIME_PROXY=false;}



    if($DHCPDInstalled==0){$EnableDHCPServer=0;}
    if($UnboundInstalled==0){$UnboundEnabled=0;}
    if($UnboundEnabled==0){$EnableDNSFilterd=0;}
    if($UnboundEnabled==0){$EnableUnboundLogQueries=0;}
    $SQUID_RT=false;

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$LegallogServer=0;}

    if($users->AsProxyMonitor){
        if($EnableSmokePing==1) {
            $f[] = "<li>";
            $f[] = "<a href=\"javascript:s_PopUp('smokeping/smokeping.cgi?target=_charts','1024','800')\">";
            $f[] = "<i class=\"".ico_health_check."\"></i> {latency} </a>";
            $f[] = "</li>";
        }

        if($Enablehacluster==1) {
            $f[] = "<!-- L.".__LINE__." -->";
            $f[] = "<li>";
            $f[] = "<a href=\"javascript:LoadAjax('MainContent','fw.proxy.relatime.php')\">";
            $f[] = "<i class=\"fa fa-eye\"></i> {requests}";
            $f[] = "</a>";
            $f[] = "</li>";
            $SQUID_RT=true;
        }
        if($EnablePulseReverse==1){
            $f[] = "<!-- L.".__LINE__." -->";
            $f[] = "<li>";
            $f[] = "<a href=\"javascript:LoadAjax('MainContent','fw.pulsereverser.requests.php')\">";
            $f[] = "<i class=\"fa fa-eye\"></i> {requests}";
            $f[] = "</a>";
            $f[] = "</li>";
        }


        if($SQUIDEnable==1){

                $f[] = "<li>";
                $f[] = "<a href=\"javascript:Loadjs('fw.proxy.active_requests.php')\">";
                $f[] = "<i class=\"".ico_health_check."\"></i> {active_requests}";
                $f[] = "</a>";
                $f[] = "</li>";


            if($SquidInRouterMode==0){
                if($SHOW_REALTIME_PROXY) {
                    if(! $SQUID_RT) {
                        $f[] = "<!-- HaClusterClient = $HaClusterClient HaClusterRemoveRealtimeLogs = $HaClusterRemoveRealtimeLogs -->";
                        $f[] = "<!-- L.".__LINE__." -->";
                        $f[] = "<li>";
                        $f[] = "<a href=\"javascript:LoadAjax('MainContent','fw.proxy.relatime.php')\">";
                        $f[] = "<i class=\"fa fa-eye\"></i> {requests}";
                        $f[] = "</a>";
                        $f[] = "</li>";
                        $SQUID_RT=true;
                    }
                }
            }



            if($EnableSquidAnalyzer==1){
                $f[]="<li>";
                $f[]="<a href=\"javascript:LoadAjax('MainContent','fw.squid.analyzer.table.php')\">";
                $f[]="<i class=\"fas fa-user-chart\"></i> {statistics}";
                $f[]="</a>";
                $f[]="</li>";
            }
        }

        if($LegallogServer==1){
            if(! $SQUID_RT) {
                $f[] = "<!-- L.".__LINE__." -->";
                $f[] = "<li>";
                $f[] = "<a href=\"javascript:LoadAjax('MainContent','fw.proxy.relatime.php')\">";
                $f[] = "<i class=\"fa fa-eye\"></i> {requests}";
                $f[] = "</a>";
                $f[] = "</li>";
                $SQUID_RT=true;
            }
        }

    }
    if($users->AsDnsAdministrator){
        $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
        if($EnableDNSDist==1){
            if(! $SQUID_RT) {
                $f[] = "<!-- L.".__LINE__." -->";
                $f[] = "<li>";
                $f[] = "<a href=\"javascript:LoadAjax('MainContent','fw.proxy.relatime.php')\">";
                $f[] = "<i class=\"fa fa-eye\"></i> {requests}";
                $f[] = "</a>";
                $f[] = "</li>";
                $SQUID_RT=true;
            }
        }
    }

    if($users->AsWebMaster){
        $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
        if($EnableNginx==1){
            if(! $SQUID_RT) {
                $f[] = "<!-- L.".__LINE__." -->";
                $f[] = "<li>";
                $f[] = "<a href=\"javascript:LoadAjax('MainContent','fw.proxy.relatime.php')\">";
                $f[] = "<i class=\"fa fa-eye\"></i> {requests}";
                $f[] = "</a>";
                $f[] = "</li>";
                $SQUID_RT=true;
            }

        }
    }


    if($EnableDNSFilterd==1){
        if($users->AsDnsAdministrator){
            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:LoadAjax('MainContent','fw.dns.filterd.stats.php?with-events=yes')\">";
            $f[]="                            <i class=\"fa fa-eye\"></i> {DNS_FILTERING}";
            $f[]="                        </a>";
            $f[]="                    </li>";

        }
    }
    if($UnboundEnabled==1){
        if($users->AsDnsAdministrator){
            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:LoadAjax('MainContent','fw.dns.unbound.queries.php')\">";
            $f[]="                            <i class=\"fa fa-eye\"></i> {DNS_QUERIES}";
            $f[]="                        </a>";
            $f[]="                    </li>";

        }
    }

    $EnableModSecurityIngix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    if($EnableModSecurityIngix==1){
        if($users->AsWebMaster){
            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:LoadAjax('MainContent','fw.modsecurity.threats.php')\">";
            $f[]="                            <i class=\"fa fa-eye\"></i> Web Firewall Audit</a>";
            $f[]="                    </li>";

        }

    }

    /*
    if($EnableDNSFirewall==1){
        if($users->AsDnsAdministrator){
            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:LoadAjax('MainContent','fw.dns.firewall.queries.php')\">";
            $f[]="                            <i class=\"fa fa-eye\"></i> {APP_DNS_FIREWALL}";
            $f[]="                        </a>";
            $f[]="                    </li>";
        }

    }
    */





    if($users->AsPostfixAdministrator OR $users->AsMessagingOrg){
        $EnableMilterSpyDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterSpyDaemon"));
        if($EnableMilterSpyDaemon==1){
            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:LoadAjax('MainContent','fw.postfix.milterspy.relatime.php')\">";
            $f[]="                            <i class=\"fa fa-eye\"></i> {forwarded_messages}";
            $f[]="                        </a>";
            $f[]="                    </li>";
        }

        if($EnablePostfix==1){
            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:LoadAjax('MainContent','fw.postfix.maillog.php')\">";
            $f[]="                            <i class=\"fa fa-eye\"></i> {smtp_transactions}";
            $f[]="                        </a>";
            $f[]="                    </li>";
        }

    }

    if($users->AsWebStatisticsAdministrator){
        $EnableKibana=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKibana"));
        if($EnableKibana==1){
            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:s_PopUpFull('/kibana/',1024,768,'Kibana Statistics');\">";
            $f[]="                            <i class=\"fas fa-chart-area\"></i> {statistics}";
            $f[]="                        </a>";
            $f[]="                    </li>";
        }

    }





    if($users->AsAnAdministratorGeneric){
        $EnableDarkStat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDarkStat"));
        $DarkStatWebInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatWebInterface");
        $DarkStatWebPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatWebPort"));
        if($EnableDarkStat==1){
            if($DarkStatWebPort==0){$DarkStatWebPort=663;}
            if($DarkStatWebInterface==null){$DarkStatWebInterface="eth0";}
            $nicz=new system_nic($DarkStatWebInterface);
            $IPClass=new IP();
            if(!$IPClass->isValid($nicz->IPADDR)){
                $nicz->IPADDR=$_SERVER["SERVER_ADDR"];
            }
            $url="http://$nicz->IPADDR:$DarkStatWebPort/";

            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:s_PopUpFull('$url',1024,768,'Network Monitor');\">";
            $f[]="                            <i class=\"fas fa-chart-area\"></i> {network_monitor}";
            $f[]="                        </a>";
            $f[]="                    </li>";
        }


        if($users->APP_NETDATA_INSTALLED){
            $NetDATAEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDATAEnabled"));
            if($NetDATAEnabled==1){
                $NetDataListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDataListenPort"));
                $MAIN_URI="https://{$_SERVER["SERVER_ADDR"]}:".$_SERVER["SERVER_PORT"]."/netdata/";
                $f[]="                    <li>";
                $f[]="                        <a href=\"javascript:s_PopUpFull('$MAIN_URI',1024,768,'Monitor');\">";
                $f[]="                            <i class=\"fa fa-heart\"></i> {monitor}";
                $f[]="                        </a>";
                $f[]="                    </li>";

            }
        }

        $NtopNGInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtopNGInstalled"));
        if($NtopNGInstalled==1){
            $Enablentopng=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablentopng"));
            if($Enablentopng==1){
                $MAIN_URI="https://{$_SERVER["SERVER_ADDR"]}:".$_SERVER["SERVER_PORT"]."/ntopng/";
                $f[]="                    <li>";
                $f[]="                        <a href=\"javascript:s_PopUpFull('$MAIN_URI',1024,768,'Monitor');\">";
                $f[]="                            <i class=\"fa fa-heart\"></i> {network_monitor}";
                $f[]="                        </a>";
                $f[]="                    </li>";


            }
        }

        $clocklink="Loadjs('fw.system.clock.php')";
        if(!$users->AsSystemAdministrator){$clocklink="blur();";}
        $f[]="                    <li>";
        $f[]="                        <a href=\"javascript:blur()\" OnClick=\"$clocklink\">";
        $f[]="                            <i class=\"far fa-clock\"></i> <span id='faclock'></span>";
        $f[]="                        </a>";
        $f[]="                    </li>";

        if($users->isCategorizeAdmin()) {
            $f[] = "                    <li>";
            $f[] = "                        <a href=\"javascript:blur()\" OnClick=\"Loadjs('fw.ufdb.categorize.php')\">";
            $f[] = "                            <i class=\"fa fa-book\"></i> {categorize}";
            $f[] = "                        </a>";
            $f[] = "                    </li>";
        }

    }

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XAPIAN_PHP_INSTALLED"))==1){
        $EnableXapianSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableXapianSearch"));
        if($EnableXapianSearch==1){
            $XapianSearchPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchPort"));
            $XapianSearchInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchInterface"));
            if($XapianSearchPort==0){$XapianSearchPort=5600;}
            $url="http://".$_SERVER["SERVER_ADDR"].":$XapianSearchPort";
            $f[]="                    <li>";
            $f[]="                         <a href=\"javascript:s_PopUpFull('$url',1024,768,'InstantSearch');\">";
            $f[]="                            <i class=\"fa fa-search\"></i> {InstantSearch}";
            $f[]="                        </a>";
            $f[]="                    </li>";
        }
    }


    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"))==1){
        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}
        if(count($ActiveDirectoryConnections)>0){$EnableKerbAuth=1;}
    }
    if($users->AllowAddUsers OR $users->AllowAddGroup) {
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            if($EnableKerbAuth==1){
                $link=$tpl->td_href("{members}","{no_license}","blur()");
                $f[]="                    <li>";
                $f[]="                        <a href=\"javascript:blur();\">";
                $f[]="                            <i class=\"far fa-users text-danger\"></i><span class=text-danger> $link</span>";
                $f[]="                        </a>";
                $f[]="                    </li>";
            }
            $EnableKerbAuth=0;
        }

        $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));

        if ($EnableKerbAuth == 1 || $EnableExternalACLADAgent==1) {

            if ($EnableExternalACLADAgent==1){
                $EnableOpenLDAP = 0;
                $f[] = "                    <li>";
                $f[] = "                        <a href=\"javascript:LoadAjaxSilent('MainContent','fw.members.activedirectory.ad.agent.php');\">";
                $f[] = "                            <i class=\"far fa-users\" ></i> {members}";
                $f[] = "                        </a>";
                $f[] = "                    </li>";
            }
            else{
                $EnableOpenLDAP = 0;
                $f[] = "                    <li>";
                $f[] = "                        <a href=\"javascript:LoadAjaxSilent('MainContent','fw.members.activedirectory.php');\">";
                $f[] = "                            <i class=\"far fa-users\" ></i> {members}";
                $f[] = "                        </a>";
                $f[] = "                    </li>";
            }

        }


        if($EnableOpenLDAP==1){
            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:LoadAjaxSilent('MainContent','fw.members.ldap.php');\">";
            $f[]="                            <i class=\"far fa-users\"></i> {members}";
            $f[]="                        </a>";
            $f[]="                    </li>";

        }

        if($SQUIDEnable==1){
            $SquidExternLDAPAUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternLDAPAUTH"));
            if($SquidExternLDAPAUTH==1){
                $f[]="                    <li>";
                $f[]="                        <a href=\"javascript:LoadAjaxSilent('MainContent','fw.members.externldap.php');\">";
                $f[]="                            <i class=\"far fa-users\"></i> {members}";
                $f[]="                        </a>";
                $f[]="                    </li>";

            }
        }

        if(($EnablePrads==1) OR ($EnableDHCPServer==1) ){
            $q=new postgres_sql();
            $Computers=$q->COUNT_ROWS("hostsnet");
            $f[]="                    <li>";
            $f[]="                        <a href=\"javascript:LoadAjaxSilent('MainContent','fw.computers.php');\">";
            $f[]="                            <i class=\"fa-solid fa-computer\"></i>&nbsp;$Computers {computers}";
            $f[]="                        </a>";
            $f[]="                    </li>";

        }
    }
    $htmltools_inc  = new htmltools_inc();
    $lang           = $htmltools_inc->LanguageArray();
    if($_COOKIE["artica-language"]==null){$_COOKIE["artica-language"]=$tpl->language;}
    $langtext = $lang[$_COOKIE["artica-language"]];
    $js="Loadjs('fw.account.php?change-language-js=yes')";

    $f[]="                    <li>";
    $f[]="                        <a href=\"javascript:$js;\">";
    $f[]="                            <i class=\"fas fa-language\"></i> $langtext";
    $f[]="                        </a>";
    $f[]="                    </li>";


    $f[]="<li class=\"dropdown\" id='artica-notifs-barr'></li>";

    $f[]="                    <li>";
    $f[]="                        <a href=\"$login?disconnect=yes\">";
    $f[]="                            <i class=\"far fa-power-off\"></i> {logoff}";
    $f[]="                        </a>";
    $f[]="                    </li>";


    if($users->AsAnAdministratorGeneric) {
        $f[] = "                    <li>
						<a class='right-sidebar-toggle'>";
        $f[] = "						<i class='fa fa-tasks'></i>";
        $f[] = "					</a></li>";
    }

    $f[]="                </ul>";
    $f[]="";
    $f[]="            </nav>";
    $f[]="        </div>
		
<script>
	$('body').toggleClass('mini-navbar');
	SmoothlyMenu();";
    $f[]="$('.right-sidebar-toggle').on('click', function () {";
    $f[]="\tLoadjs('fw.sidebar.php?call=yes');";
    $f[]="});";
    $f[]="$('.sidebar-container').slimScroll({";
    $f[]="height: '100%',";
    $f[]="railOpacity: 0.4,";
    $f[]="wheelStep: 10";
    $f[]="});";
    //ABDEV 1/1
    $f[]="
    var ads_detected=0
        justDetectAdblock.detectAnyAdblocker().then(function(detected){
            if(detected){
                ads_detected=1;
            }
        })
        setTimeout(() => Loadjs('fw.icon.top.php?adblock='+ads_detected), 1); 
    ";
    //$f[]="LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
    //END ABDEV

    $f[]="</script>";


    echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}