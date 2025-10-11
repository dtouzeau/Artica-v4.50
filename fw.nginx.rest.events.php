<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){events_searcher();exit;}
if(isset($_GET["start"])){events_popup();exit;}
page();


function page():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $addon      = null;
    if (isset($_GET["request"])) {
        $addon="&request=yes";
    }

    $ARTICAREST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReverseProxyVersion");


    $html=$tpl->page_header("{APP_REVERSE_PROXY} {events} v$ARTICAREST_VERSION",
        ico_eye,"{APP_REVERSE_PROXY_ARTICA_EXPLAIN}","$page?start=yes$addon","nginx-restapi","progress-appreverse-restart",false,"table-loader-appreverse-service");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_REVERSE_PROXY} {events} v$ARTICAREST_VERSION",$html);
        echo $tpl->build_firewall();
        return true;
    }

    if (isset($_GET["request-page"])) {
        $tpl=new template_admin("{artica_license}", $html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function events_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&events-searcher=yes");
    echo "</div>";

    $ARTICAREST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaReverseProxyVersion");
    $TINY_ARRAY["TITLE"]="{APP_REVERSE_PROXY} {events} v$ARTICAREST_VERSION";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_REVERSE_PROXY_ARTICA_EXPLAIN}";
    $TINY_ARRAY["URL"]="nginx-restapi";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";
    return true;
}


function events_searcher():bool{

    $tpl        = new template_admin();
    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}

    $tooltips["paused"]="<label class='label label-warning'>{paused}</label>";
    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["warn"]="<label class='label label-warning'>{warn}</label>";
    $tooltips["stop"]="<label class='label label-warning'>{stopping}</label>";
    $tooltips["error"]="<label class='label label-danger'>{error}</label>";
    $tooltips["start"]="<label class='label label-primary'>{starting}</label>";
    $tooltips["stats"]="<label class='label label-info'>{statistics}</label>";
    $tooltips["update"]="<label class='label label-primary'>{update2}</label>";

    $text["error"]="text-danger";
    $text["warn"]="text-warning font-bold";
    $text["info"]="text-muted";

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{level}</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    $search=urlencode(base64_encode($search));
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/events/$search/$rp");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }

    foreach ($json->Logs as $line){
        $textclass=null;
        if(strlen($line)<5){
            continue;
        }
        $json=json_decode($line);
        if (json_last_error()> JSON_ERROR_NONE) {
            continue;
        }
        if(!property_exists($json,"level")){continue;}

        $level=$json->level;
        $FTime=$tpl->time_to_date($json->time,true);
        $level_label="<label class='label label-default'>$level</label>";
        $message=$json->message;
        if(isset($tooltips[$level])){
            $level_label=$tooltips[$level];
        }
        if(isset($text[$level])){
            $textclass=$text[$level];
        }

        if (strpos("    $message","[START]:")>0){
            $level="start";
            $message=str_replace("[START]:","",$message);
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];

        }



        if(strpos("    $message","[UPDATE]:")>0){
            $level="update";
            $message=str_replace("[UPDATE]:","",$message);
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
        }
        
        if(strpos("    $message",".Start:")>0){
            $level="start";
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
        }
        if(strpos("    $message"," Starting ")>0){
            $level="start";
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
        }

        if(strpos("    $message"," Unable to ")>0){
            $level="error";
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
        }


        if(strpos("    $message",".RunStart")>0){
            $level="start";
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
        }
        if(strpos("    $message","UpdateMySelf")>0){
            $level="update";
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
        }

        if(strpos("    $message","[STOP]:")>0){
            $level="stop";
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
            $message=str_replace("[STOP]:","",$message);
        }

        if(strpos("    $message",".Stop:")>0){
            $level="stop";
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
        }
        if(strpos("    $message","LoadStats:")>0){
            $level="stats";
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
        }
        if(strpos("    $message","rsyslogQueueSize")>0){
            $level="stats";
            if(isset($text[$level])) {
                $textclass = $text[$level];
            }
            $level_label=$tooltips[$level];
        }
        if(strpos("    $message","[FIREWALL]:")>0){
            $message=str_replace("[FIREWALL]:","",$message);
            $message="<span class='label label-info'>{firewall}</span>&nbsp;$message";
        }
        if(strpos("    $message","[NETWORKING]:")>0){
            $message=str_replace("[NETWORKING]:","",$message);
            $message="<span class='label label-info'>{network}</span>&nbsp;$message";
        }

        if(strpos("    $message","openssh.go[openssh.Restart:")>0){
            $message=str_replace("openssh.go[openssh.Restart:","[",$message);
            $message="<span class='label label-info'>{APP_OPENSSH}</span>&nbsp;$message";
        }
        if(strpos("    $message","openssh.go[openssh.Stop:")>0){
            $message=str_replace("openssh.go[openssh.Stop:","[",$message);
            $message="<span class='label label-info'>{APP_OPENSSH}</span>&nbsp;$message";
        }
        if(strpos("    $message","openssh.go[openssh.Start:")>0){
            $message=str_replace("openssh.go[openssh.Start:","[",$message);
            $message="<span class='label label-info'>{APP_OPENSSH}</span>&nbsp;$message";
        }
        if(strpos("    $message","openssh.go[openssh.BuildSystemd:")>0){
            $message=str_replace("openssh.go[openssh.BuildSystemd:","[",$message);
            $message="<span class='label label-info'>{APP_OPENSSH}</span>&nbsp;$message";
        }
        if(strpos("    $message","openssh.go[openssh.killGhosts:")>0){
            $message=str_replace("openssh.go[openssh.killGhosts:","[",$message);
            $message="<span class='label label-info'>{APP_OPENSSH}</span>&nbsp;$message";
        }
        if(strpos("    $message","sshd.go[main.restSshdRestart:")>0){
            $message=str_replace("sshd.go[main.restSshdRestart:","[",$message);
            $message="<span class='label label-info'>{APP_OPENSSH}</span>&nbsp;$message";
        }

        if(strpos("    $message","HaClusterClientPing.go[hacluster/HaClusterClientPing.PING:")>0){
            $message=str_replace("HaClusterClientPing.go[hacluster/HaClusterClientPing.PING:","[",$message);
            $message="<span class='label label-info'>HaCluster Client</span>&nbsp;$message";
        }

        if(strpos("    $message","DecisionIP.go[DecisionIP.EmptyBuffer:")>0){
            $message=str_replace("DecisionIP.go[DecisionIP.EmptyBuffer:","[",$message);
            $message="<span class='label label-primary'>DecisionIP</span>&nbsp;$message";
        }

        if(strpos("    $message","RedisSrv.go[RedisSrv.RedisBuildConf:")>0){
            $message=str_replace("RedisSrv.go[RedisSrv.RedisBuildConf:","[",$message);
            $message="<span class='label label-info'>Valkey Server</span>&nbsp;$message";
        }
        if(strpos("    $message","RedisSrv.go[RedisSrv.Stop:")>0){
            $message=str_replace("RedisSrv.go[RedisSrv.Stop:","[",$message);
            $message="<span class='label label-info'>Valkey Server</span>&nbsp;$message";
        }
        if(strpos("    $message","RedisSrv.go[RedisSrv.Start:")>0){
            $message=str_replace("RedisSrv.go[RedisSrv.Start:","[",$message);
            $message="<span class='label label-info'>Valkey Server</span>&nbsp;$message";
        }

        if(strpos("    $message","SyncthingInstances.go[SyncThing/SyncthingInstances.(*SyncthingInstance).Start:")>0){
            $message=str_replace("SyncthingInstances.go[SyncThing/SyncthingInstances.(*SyncthingInstance).Start:","[",$message);
            $message="<span class='label label-info'>{APP_SYNCTHING}</span>&nbsp;$message";
        }
        if(strpos("    $message","SyncthingInstances.go[SyncThing/SyncthingInstances.(*SyncthingInstance).StartSyncthingBin:")>0){
            $message=str_replace("SyncthingInstances.go[SyncThing/SyncthingInstances.(*SyncthingInstance).StartSyncthingBin:","[",$message);
            $message="<span class='label label-info'>{APP_SYNCTHING}</span>&nbsp;$message";
        }

        if(strpos("    $message","SyncthingInstances.go[SyncThing/SyncthingInstances.(*SyncthingInstance).")>0){
            $message=str_replace("SyncthingInstances.go[SyncThing/SyncthingInstances.(*SyncthingInstance).","[",$message);
            $message="<span class='label label-info'>{APP_SYNCTHING}</span>&nbsp;$message";
        }

        if(strpos("    $message","LogSinkStatsReceiver.go[LogSinkStatsReceiver.")>0){
            $message=str_replace("LogSinkStatsReceiver.go[LogSinkStatsReceiver.","[",$message);
            $message="<span class='label label-info'>{statistics}</span>&nbsp;$message";
        }

        if(strpos("    $message","main.go[main.main:1501] Success Initialize")>0){
            $message=str_replace("main.go[main.main:1501] Success Initialize","",$message);
        }


        if(strpos("    $message","dnscache.go[dnscache.Start:")>0){
            $message=str_replace("dnscache.go[dnscache.Start:","[",$message);
            $message="<span class='label label-info'>{APP_LOCAL_DNSCACHE}</span>&nbsp;$message";
        }
        if(strpos("    $message","dnscache.go[dnscache.BinaryOpts:")>0){
            $message=str_replace("dnscache.go[dnscache.BinaryOpts:","[",$message);
            $message="<span class='label label-info'>{APP_LOCAL_DNSCACHE}</span>&nbsp;$message";
        }
        if(strpos("    $message","dnscache.go[dnscache.BuildConf:")>0){
            $message=str_replace("dnscache.go[dnscache.BuildConf:","[",$message);
            $message="<span class='label label-info'>{APP_LOCAL_DNSCACHE}</span>&nbsp;$message";
        }
        if(strpos("    $message","dnscache.go[dnscache.Status:")>0){
            $message=str_replace("dnscache.go[dnscache.Status:","[",$message);
            $message="<span class='label label-info'>{APP_LOCAL_DNSCACHE}</span>&nbsp;$message";
        }
        if(strpos("    $message","dnscache.go[dnscache.RunDNSCache:")>0){
            $message=str_replace("dnscache.go[dnscache.RunDNSCache:","[",$message);
            $message="<span class='label label-info'>{APP_LOCAL_DNSCACHE}</span>&nbsp;$message";
        }


        if(strpos("    $message","Rsyslogs.go[Rsyslogs.")>0){
            $message=str_replace("Rsyslogs.go[Rsyslogs.","[",$message);
            $message="<span class='label label-info'>{APP_SYSLOG}</span>&nbsp;$message";
        }
        if(strpos("    $message","SyslogUnix.go[SyslogUnix.Start:")>0){
            $message=str_replace("SyslogUnix.go[SyslogUnix.Start:","[",$message);
            $message="<span class='label label-info'>{APP_SYSLOG}</span>&nbsp;$message";
        }
        if(strpos("    $message","HTTPServ.go[HTTPServ.InitHTTPRouter.func")>0){
            $message=str_replace("HTTPServ.go[HTTPServ.InitHTTPRouter.func","[",$message);
            $message="<span class='label label-info'>Web service</span>&nbsp;$message";
        }
        if(strpos("    $message","main.go[main.main:")>0){
            $message=str_replace("main.go[main.main:","[",$message);
            $message="<span class='label label-info'>{APP_REVERSE_PROXY}</span>&nbsp;$message";
        }


        if(strpos("    $message","syslogUnix.go[main.StartSyslogServerUnixSocket:")>0){
            $message=str_replace("syslogUnix.go[main.StartSyslogServerUnixSocket:","[",$message);
            $message="<span class='label label-info'>{APP_SYSLOG}</span>&nbsp;$message";
        }

        if(strpos("    $message","SyncThing.go[SyncThing.Start")>0){
            $message=str_replace("SyncThing.go[SyncThing.Start","[",$message);
            $message="<span class='label label-info'>{APP_SYNCTHING}</span>&nbsp;$message";
        }
        if(strpos("    $message","SyncThing.go[SyncThing.startSyncthingBin")>0){
            $message=str_replace("SyncThing.go[SyncThing.startSyncthingBin","[",$message);
            $message="<span class='label label-info'>{APP_SYNCTHING}</span>&nbsp;$message";
        }
        if(strpos("    $message","SyncThing.go[SyncThing.Stop")>0){
            $message=str_replace("SyncThing.go[SyncThing.Stop","[",$message);
            $message="<span class='label label-info'>{APP_SYNCTHING}</span>&nbsp;$message";
        }
        if(strpos("    $message","SyncThing.go[SyncThing.MonitConfig")>0){
            $message=str_replace("SyncThing.go[SyncThing.MonitConfig","[",$message);
            $message="<span class='label label-info'>{APP_SYNCTHING}</span>&nbsp;$message";
        }



        if(strpos("    $message","[networking.")>0){
            $message=str_replace("[networking.","[",$message);
            $message="<span class='label label-info'>{network}</span>&nbsp;$message";
        }
        if(strpos("    $message","Uninstall.go")>0){
            $message=str_replace("Uninstall.go","",$message);
            $message="<span class='label label-danger'>{uninstall}</span>&nbsp;$message";
        }
        if(strpos("    $message","[squid.Uninstall")>0){
            $message=str_replace("[squid.Uninstall","[",$message);
            $message="<span class='label label-danger'>{APP_SQUID}</span>&nbsp;$message";
        }
        if(strpos("    $message","SquidConfTools.go[squid/SquidConfTools.SquidKReconfigure:")>0){
            $message=str_replace("SquidConfTools.go[squid/SquidConfTools.SquidKReconfigure:","[",$message);
            $message="<span class='label label-danger'>{APP_SQUID}</span>&nbsp;$message";
        }

        if(strpos("    $message","unbound.go[unbound.")>0){
            $message=str_replace("unbound.go[unbound.","[",$message);
            $message="<span class='label label-info'>{APP_UNBOUND}</span>&nbsp;$message";
        }


        if(strpos("    $message","cronservice.go")>0){
            $message=str_replace("cronservice.go","",$message);
            $message="<span class='label label-info'>Scheduler</span>&nbsp;$message";
        }




        if(strpos("    $message","syslogserver.go")>0){
            $message=str_replace("syslogserver.go","",$message);
            $message="<span class='label label-info'>{APP_SYSLOG}</span>&nbsp;$message";
        }
        if(strpos("    $message","dnstapsrv.go")>0){
            $message=str_replace("dnstapsrv.go","",$message);
            $message="<span class='label label-info'>DNS Statistics</span>&nbsp;$message";
        }
        if(strpos("    $message","webunix.go")>0){
            $message=str_replace("webunix.go","",$message);
            $message="<span class='label label-info'>Web Unix</span>&nbsp;$message";
        }
        if(strpos("    $message","aFirewallTools.go")>0){
            $message=str_replace("aFirewallTools.go","",$message);
            $message="<span class='label label-info'>{firewall}</span>&nbsp;$message";
        }
        if(strpos("    $message","ClusterServicePort.go")>0){
            $message=str_replace("ClusterServicePort.go","",$message);
            $message="<span class='label label-info'>Cluster</span>&nbsp;$message";
        }
        if(strpos("    $message","help.go")>0){
            $message=str_replace("help.go","",$message);
            $message="<span class='label label-default'>Cmdline</span>&nbsp;$message";
        }
        if(strpos("    $message","Cpu.go")>0){
            $message=str_replace("Cpu.go","",$message);
            $message="<span class='label label-default'>Monitor</span>&nbsp;$message";
        }
        if(strpos("    $message","monit.go")>0){
            $message=str_replace("monit.go","",$message);
            $message="<span class='label label-default'>Monitor</span>&nbsp;$message";
        }

        if(strpos("    $message","phpfpm.go[phpfpm.Start")>0){
            $message=str_replace("phpfpm.go[phpfpm.Start","[",$message);
            $message="<span class='label label-success'>Web Console</span>&nbsp;$message";
        }
        if(strpos("    $message","webconsole.go[webconsole.Start")>0){
            $message=str_replace("webconsole.go[webconsole.Start","[",$message);
            $message="<span class='label label-success'>Web Console</span>&nbsp;$message";
        }
        if(strpos("    $message","ssh2proxy.go[ssh2proxy.Stop")>0){
            $message=str_replace("ssh2proxy.go[ssh2proxy.Stop","[",$message);
            $message="<span class='label label-success'>SSH Proxy</span>&nbsp;$message";
        }

        if(strpos("    $message","ssh2proxy.go[ssh2proxy.Start")>0){
            $message=str_replace("ssh2proxy.go[ssh2proxy.Start","[",$message);
            $message="<span class='label label-success'>SSH Proxy</span>&nbsp;$message";
        }

        if(strpos("    $message","webconsole.go[webconsole.Reconfigure:")>0){
            $message=str_replace("webconsole.go[webconsole.Reconfigure","[",$message);
            $message="<span class='label label-warning'>Web Console</span>&nbsp;$message";
        }

        if(strpos("    $message","rest_hacluster.go[main.restHaClusterReload")>0){
            $message=str_replace("rest_hacluster.go[main.restHaClusterReload","[",$message);
            $message="<span class='label label-success'>HaCluster</span>&nbsp;$message";
        }


        if(strpos("    $message","SquidServiceTools.go")>0){
            $message=str_replace("SquidServiceTools.go","",$message);
            $message="<span class='label label-success'>Proxy</span>&nbsp;$message";
        }
        if(strpos("    $message","ExternalAclFirst.go")>0){
            $message=str_replace("ExternalAclFirst.go","",$message);
            $message="<span class='label label-success'>Proxy</span>&nbsp;$message";
        }
        if(strpos("    $message","nginx.go")>0){
            $message=str_replace("nginx.go","",$message);
            $message="<span class='label label-success'>Reverse-Proxy</span>&nbsp;$message";
        }
        if(strpos("    $message","NginxStatsMem.go")>0){
            $message=str_replace("NginxStatsMem.go","",$message);
            $message="<span class='label label-success'>Reverse-Proxy</span>&nbsp;$message";
        }
        if(strpos("    $message","NginxLocalConf.go")>0){
            $message=str_replace("NginxLocalConf.go","",$message);
            $message="<span class='label label-success'>Reverse-Proxy</span>&nbsp;$message";
        }
        if(strpos("    $message","weberrorpage.go")>0){
            $message=str_replace("weberrorpage.go","",$message);
            $message="<span class='label label-success'>Web Error Page</span>&nbsp;$message";
        }
        if(strpos("    $message","service.go[postfix.GetVersion")>0){
            $message=str_replace("service.go[postfix.GetVersion","[",$message);
            $message="<span class='label label-success'>{APP_POSTFIX}</span>&nbsp;$message";
        }
        if(strpos("    $message","SquidStats.go[squid.stats5Mins")>0){
            $message=str_replace("SquidStats.go[squid.stats5Mins","[",$message);
            $message="<span class='label label-success'>{APP_SQUID} {statistics}</span>&nbsp;$message";
        }
        if(strpos("    $message","cron.go")>0){
            $message=str_replace("cron.go","",$message);
            $message="<span class='label label-success'>Scheduler</span>&nbsp;$message";
        }
        if(strpos("    $message","arplistener.go")>0){
            $message=str_replace("arplistener.go","",$message);
            $message="<span class='label label-success'>ARP Scanner</span>&nbsp;$message";
        }
       if(strpos("    $message","postgresqld.go")>0){
            $message=str_replace("postgresqld.go","",$message);
            $message="<span class='label label-success'>PostgreSQL</span>&nbsp;$message";
        }
        if(strpos("    $message","[nginxsites.NginxDumpInfos:")>0){
            $message=str_replace("[nginxsites.NginxDumpInfos:","[",$message);
            $message="<span class='label label-success'>Reverse-Proxy</span>&nbsp;$message";
        }
        if(strpos("    $message","RulesBuilder.go[weberrorpage.")>0){
            $message=str_replace("RulesBuilder.go[weberrorpage.","[",$message);
            $message="<span class='label label-success'>Web Error Page</span>&nbsp;$message";
        }

        if(strpos("    $message","DBConf.go[nginxsites.NginxDumpInfosForced:")>0){
            $message=str_replace("DBConf.go[nginxsites.NginxDumpInfosForced:","[",$message);
            $message="<span class='label label-success'>Reverse-Proxy</span>&nbsp;$message";
        }

        if(strpos("    $message","Configurator.go[postgresqld.BuildConfig:")>0){
            $message=str_replace("Configurator.go[postgresqld.BuildConfig:","[",$message);
            $message="<span class='label label-success'>PostgreSQL</span>&nbsp;$message";
        }
        if(strpos("    $message","TuneKernel.go")>0){
            $message=str_replace("TuneKernel.go","",$message);
            $message="<span class='label label-success'>{OS}</span>&nbsp;$message";
        }
        if(strpos("    $message","Optimize.go")>0){
            $message=str_replace("Optimize.go","",$message);
            $message="<span class='label label-success'>{OS}</span>&nbsp;$message";
        }
        if(strpos("    $message","Watchdog.go[squid.SwapCache")>0){
            $message=str_replace("Watchdog.go[squid.SwapCache","[",$message);
            $message="<span class='label label-success'>{OS}</span>&nbsp;$message";
        }
        if(strpos("    $message","GlobalsValues.go[GlobalsValues.StartSystemd:")>0){
            $message=str_replace("GlobalsValues.go[GlobalsValues.StartSystemd","[",$message);
            $message="<span class='label label-success'>{OS}</span>&nbsp;$message";
        }
        if(strpos("    $message","GlobalsValues.go[GlobalsValues.StopBySystemd:")>0){
            $message=str_replace("GlobalsValues.go[GlobalsValues.StopBySystemd","[",$message);
            $message="<span class='label label-success'>{OS}</span>&nbsp;$message";
        }

        if(strpos("    $message","SquidStats.go[squid.StatsClients")>0){
            $message=str_replace("SquidStats.go[squid.StatsClients","[",$message);
            $message="<span class='label label-success'>{APP_SQUID}</span>&nbsp;$message";
        }
        if(strpos("    $message","[squid/SquidServiceTools.KCheck:")>0){
            $message=str_replace("[squid/SquidServiceTools.KCheck:","[",$message);
            $message="<span class='label label-info'>{starting}</span>&nbsp;$message";
        }
        if(strpos("    $message","[squid/SquidServiceTools.StartSystemd:")>0){
            $message=str_replace("[squid/SquidServiceTools.StartSystemd:","[",$message);
            $message="<span class='label label-info'>{configuring}</span>&nbsp;$message";
        }
        if(strpos("    $message","squidclient.go[squidclient.SquidClient")>0){
            $message=str_replace("squidclient.go[squidclient.SquidClient","[",$message);
            $message="<span class='label label-info'>API</span>&nbsp;$message";
        }
        if(strpos("    $message","httpclient.go[httpclient.GetHeaders:")>0){
            $message=str_replace("httpclient.go[httpclient.GetHeaders:","[",$message);
            $message="<span class='label label-info'>Web Client</span>&nbsp;$message";
        }




        if(strpos("    $message","phpfpm.go[phpfpm.ReplicateCommandLines")>0){
            $message=str_replace("phpfpm.go[phpfpm.ReplicateCommandLines","[",$message);
            $message="<span class='label label-success'>Web Console</span>&nbsp;$message";
        }
        if(strpos("    $message","squid.go[squid.Start:")>0){
            $message=str_replace("squid.go[squid.Start:","[",$message);
            $message="<span class='label label-success'>{APP_SQUID}</span>&nbsp;$message";
        }

        if(strpos("    $message","ipscan.go")>0){
            $message=str_replace("ipscan.go","",$message);
            $message="<span class='label label-success'>IP Scanner</span>&nbsp;$message";
        }
        if(strpos("    $message","sockets.go")>0){
            $message=str_replace("sockets.go","",$message);
            $message="<span class='label label-default'>Internal</span>&nbsp;$message";
        }
        if(strpos("    $message","suricata.go")>0){
            $message=str_replace("suricata.go","",$message);
            $message="<span class='label label-success'>IDS</span>&nbsp;$message";
        }
        if(strpos("    $message","license.go")>0){
            $message=str_replace("license.go","",$message);
            $message="<span class='label label-warning'>{license}</span>&nbsp;$message";
        }
        if(strpos("    $message","myprocess.go")>0){
            $message=str_replace("myprocess.go","",$message);
            $message="<span class='label label-default'>Internal</span>&nbsp;$message";
        }
        if(strpos("    $message","Tools.go[aptget.")>0){
            $message=str_replace("Tools.go[aptget.","[",$message);
            $message="<span class='label label-success'>{update2}</span>&nbsp;$message";
        }
        if(strpos("    $message","clamavDaemon.go")>0){
            $message=str_replace("clamavDaemon.go","[",$message);
            $message="<span class='label label-success'>{APP_CLAMAV}</span>&nbsp;$message";
        }
        if(strpos("    $message","resolv.go")>0){
            $message=str_replace("resolv.go","",$message);
            $message="<span class='label label-default'>DNS</span>&nbsp;$message";
        }
        if(strpos("    $message","LocalDomains.go[unbound.GetLocalDomains:")>0){
            $message=str_replace("LocalDomains.go[unbound.GetLocalDomains:","[",$message);
            $message="<span class='label label-default'>DNS</span>&nbsp;$message";
        }
        if(strpos("    $message","ResponsesPolicyZones.go[unbound/UnboundRPZ")>0){
            $message=str_replace("LocalDomains.go[unbound.GetLocalDomains:","[",$message);
            $message="<span class='label label-default'>DNS RPZ</span>&nbsp;$message";
        }


        if(strpos("    $message","NetMonixCacheDB.go")>0){
            $message=str_replace("NetMonixCacheDB.go","",$message);
            $message="<span class='label label-success'>NetMonix</span>&nbsp;$message";
        }
        if(strpos("    $message","logsink.go")>0){
            $message=str_replace("logsink.go","",$message);
            $message="<span class='label label-success'>{APP_RSYSLOG}</span>&nbsp;$message";
        }
        if(strpos("    $message","sysctl.go[sysctl.Build:")>0){
            $message=str_replace("sysctl.go[sysctl.Build:","",$message);
            $message="<span class='label label-success'>{optimize}</span>&nbsp;$message";
        }
        if(strpos("    $message","settings.go[main.DelOldCrons:")>0){
            $message=str_replace("settings.go[main.DelOldCrons:","",$message);
            $message="<span class='label label-success'>{optimize}</span>&nbsp;$message";
        }




        if(strpos("    $message","[main.InitHTTPRouter")>0){
            $message=str_replace("[main.InitHTTPRouter","[",$message);
            $message="<span class='label label-success'>HTTP Service</span>&nbsp;$message";
        }
        if (strpos("    $message","Corporate License")>0){
            $level_label="<span class='label label-success'>{license}</span>";
            $textclass="text-success font-bold";
        }
        $message=str_replace("httprouter.go","",$message);
        $message=str_replace("networking.go","",$message);
        $message=str_replace("articanotifs.go","",$message);
        $message=str_replace("[main.LoadNginxParams:","[",$message);
        $message=str_replace("squid/SquidServiceTools.Reload","",$message);
        $message=str_replace("NginxStatsMem.","",$message);
        $message=str_replace("nginx/NginxLocalConf.","",$message);
        $html[]="<tr>
				<td style='width:1%;' nowrap class='$textclass'>$FTime</td>
				<td style='width:1%;' nowrap class='$textclass'>$level_label</td>
    			<td class='$textclass'>$message</td>
				</tr>";

    }
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}