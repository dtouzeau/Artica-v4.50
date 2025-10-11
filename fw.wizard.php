<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.langages.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.privileges.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-logon.inc');
include_once(dirname(__FILE__).'/ressources/class.invalid.mail.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.identity.inc');
include_once(dirname(__FILE__).'/class.html.tools.inc');

if(isset($_POST["SaveNic"])){save_nic();exit;}
if(isset($_POST["timezones"])){step1_save();exit;}
if(isset($_POST["smtp_domainname"])){step2_save();exit;}
if(isset($_POST["artica_method"])){choose_products_save();exit;}
if(isset($_POST["main-choose"])){step3_save();exit;}
if(isset($_GET["interface-tables"])){interfaces_table();exit;}
if(isset($_GET["interface-js"])){interface_js();exit;}
if(isset($_GET["interface-popup"])){interface_popup();exit;}
if(isset($_GET["choosed"])){choosed_products();exit;}
if(isset($_GET["stepChoose"])){choose_products();exit;}
if(isset($_GET["step1"])){step1();exit;}
if(isset($_GET["step2"])){step2();exit;}
if(isset($_GET["step3"])){step3();exit;}
if(isset($_GET["change-lang"])){change_lang();exit;}

if(isset($_GET["step-final"])){step_final();exit;}
page();


function page(){

    if(is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")){
        echo header('location:fw.login.php');
        exit;
    }

    $tpl=new template_admin();
    $page=CurrentPageName();
    $addong=null;
    $tpl->title=$tpl->_ENGINE_parse_body("{WELCOME_ON_ARTICA_PROJECT}");
    $WELCOME_WIZARD_ARC=$tpl->_ENGINE_parse_body("{WELCOME_WIZARD_ARC1}");
    if($GLOBALS["VERBOSE"]){$addong="&verbose=yes";}
    $f[]=$tpl->build_heads(true);
    $f[]="<body class=\"gray-bg\">
    <div class=\"loginColumns animated fadeInDown\">
        <div class=\"row\">
			<div class=\"ibox-content\">
			<H1 id='wizard-h1'>$tpl->title</H1>
			<p id='p-wizard'>$WELCOME_WIZARD_ARC</p>
			<div id=\"wizard\">
			
		</div>
	</div>
	
<script>
	LoadAjax('wizard','$page?step1=yes$addong');

</script>";

    $f[]=@implode("\n",$tpl->cssScript);
    $f[]="</body>";
    $f[]="</html>";
    echo @implode("\n", $f);

}
function change_lang(){
    $page=CurrentPageName();
    $language=$_GET["change-lang"];
    setcookie("artica-language", $language, time()+31536000);
    $xtime=time()+31536000;
    $f[]="Delete_Cookie('artica-language', '/', '');";
    $f[]="Set_Cookie('artica-language', '$language', '$xtime', '/', '', '');";
    $f[]="LoadAjaxSilent('wizard','$page?step1=yes');";


    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
}


function  step1(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.langages.inc");
    $GLOBALS["DEBUG_TEMPLATE"]=true;
    $langAutodetect=new articaLang();
    if(!isset($_COOKIE["artica-language"])) {
        $DetectedLanguage = $langAutodetect->get_languages();

    }else{
        $DetectedLanguage=$_COOKIE["artica-language"];
    }
    $GLOBALS["FIXED_LANGUAGE"] = $DetectedLanguage;
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));
    $dhcpd=null;$SERVICES_TITLE=null;$error=null;
    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));

    $htmltools_inc  = new htmltools_inc();
    $lang           = $htmltools_inc->LanguageArray();



    $arrayTime      = array();
    $page=CurrentPageName();
    $sock=new sockets();
    $domainname=null;

    if(!is_array($savedsettings)){$savedsettings=array();}

    if(count($savedsettings)<3){
        if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>[".__LINE__."] network.php?fqdn=yes</span><br>\n";}
        $hostname=base64_decode($sock->getFrameWork("network.php?fqdn=yes"));
        if($hostname==null){$users=new usersMenus();$hostname=$users->fqdn;}
        $arrayNameServers=GetNamesServers();

        if(strpos($hostname, '.')>0){
            $Thostname=explode(".", $hostname);
            $netbiosname=$Thostname[0];
            unset($Thostname[0]);
            $domainname=@implode(".", $Thostname);
        }else{
            $netbiosname=$hostname;
        }

        if(preg_match("#[A-Za-z]+\s+[A-Za-z]+#", $netbiosname)){$netbiosname=null;}


    }else{
        $netbiosname=$savedsettings["netbiosname"];
        $domainname=$savedsettings["domain"];
        $arrayNameServers[0]=$savedsettings["DNS1"];
        $arrayNameServers[1]=$savedsettings["DNS2"];
    }

    if($netbiosname==null){
        $hostname=base64_decode($sock->getFrameWork("network.php?fqdn=yes"));
        if($hostname==null){$users=new usersMenus();$hostname=$users->fqdn;}
        if(strpos($hostname, '.')>0){
            $Thostname=explode(".", $hostname);
            $netbiosname=$Thostname[0];
            unset($Thostname[0]);
            $domainname=@implode(".", $Thostname);
        }else{
            $netbiosname=$hostname;
        }
    }

    if($arrayNameServers[0]==null){$arrayNameServers=GetNamesServers();}
    if(trim($arrayNameServers[0])==null){$arrayNameServers[0]="8.8.8.8";}
    if(trim($arrayNameServers[1])==null){$arrayNameServers[1]="1.1.1.1";}


    $resolv=new resolv_conf(true);
    if(!$resolv->resolvTypeA($arrayNameServers[0],$arrayNameServers[1],"www.google.com")){
        $error=$tpl->_ENGINE_parse_body("{WIZARD_NO_RESOLV}");
        $error=str_replace("%1",$arrayNameServers[0],$error);
        $error=str_replace("%2",$arrayNameServers[1],$error);
        $error=str_replace("%err",$resolv->mysql_error,$error);

    }


    $timezone=timezonearray();
    $timezone_def=getLocalTimezone();
    if(isset($savedsettings["timezones"])){
        if($savedsettings["timezones"]<>null){
            $timezone_def=$savedsettings["timezones"];
        }
    }


    for($i=0;$i<count($timezone);$i++){
        $arrayTime[$timezone[$i]]=$timezone[$i];
    }

    $jsafter="LoadAjax('wizard','$page?step2=yes');";

    $form[]=$tpl->field_array_hash_simple($arrayTime, "timezones", "{timezone}", $timezone_def);
    $form[]=$tpl->field_text("netbiosname", "{netbiosname}", $netbiosname,true);

    if($domainname==null){$domainname="company.tld";}
    $form[]=$tpl->field_text("domain", "{DomainOfThisserver}", $domainname,true);
    $form[]=$tpl->field_array_hash_simple($lang, "lang2", "{mylanguage}", $DetectedLanguage,false,"Changelang");

    if($DisableNetworking==0){
        $form[]=$tpl->field_section("{network}","{network_settings_will_be_applied_after_reboot}");
        $form[]=$tpl->field_div("<div id='wizard-interfaces'></div>");
    }
    if($arrayNameServers[0]=="127.0.0.55"){
        $arrayNameServers[0]=$arrayNameServers[1];
        $arrayNameServers[1]=$arrayNameServers[2];
    }
    if($arrayNameServers[1]==null){$arrayNameServers[1]="8.8.8.8";}
    $form[]=$tpl->field_ipaddr("DNS1", "{primary_dns}", $arrayNameServers[0]);
    $form[]=$tpl->field_ipaddr("DNS2", "{secondary_dns}", $arrayNameServers[1]);
    $html[]=$tpl->form_outside("{serveretdom}", @implode("\n", $form),$error,"{next}",$jsafter);

    $html[]="<!-- DisableNetworking: '$DisableNetworking' -->";
    $html[]="";
    if($DisableNetworking==0) {
        $html[] = "<script>LoadAjax('wizard-interfaces','$page?interface-tables=yes');</script>";
    }
    $t=time();
    $final[]=$tpl->_ENGINE_parse_body($html);
    $final[]="<script>";
    $final[]="var xSave$t= function (obj) {";
    $final[]="\t\$('body').Wload('hide',{time:1});";
    $final[]="}";
    $final[]="function Changelang(id){";
    $final[]="var lang=document.getElementById(id).value;";
    $final[]=$tpl->XHR_BUILD($page,"xSave$t");
    $final[]="Loadjs('$page?change-lang='+lang);";
    $final[]="}";
    $WELCOME_ON_ARTICA_PROJECT=base64_encode($tpl->_ENGINE_parse_body("{WELCOME_ON_ARTICA_PROJECT}"));
    $WELCOME_WIZARD_ARC=base64_encode($tpl->_ENGINE_parse_body("{WELCOME_WIZARD_ARC1}"));
    $final[]="document.getElementById('wizard-h1').innerHTML=base64_decode('$WELCOME_ON_ARTICA_PROJECT');";
    $final[]="document.getElementById('p-wizard').innerHTML=base64_decode('$WELCOME_WIZARD_ARC');";
    $final[]="</script>";
    echo @implode("\n",$final);

}

function interface_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $nic=$_GET["interface-js"];
    $tpl->js_dialog1($nic, "$page?interface-popup=$nic");


}

function interface_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $nic=$_GET["interface-popup"];
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));
    $savedsettings=$savedsettings["NET_INTERFACES"][$nic];
    $IPADDR=$savedsettings["IPADDR"];
    $NETMASK=$savedsettings["NETMASK"];
    $GATEWAY=$savedsettings["GATEWAY"];
    $metric=$savedsettings["metric"];
    $BROADCAST=$savedsettings["BROADCAST"];
    $system_nic=new system_nic($nic);
    if($IPADDR==null){$IPADDR=$system_nic->IPADDR;}
    if($NETMASK==null){$NETMASK=$system_nic->NETMASK;}
    if($GATEWAY==null){$GATEWAY=$system_nic->GATEWAY;}
    if($BROADCAST==null){$BROADCAST=$system_nic->BROADCAST;}
    if($metric==null){$metric=$system_nic->metric;}
    if(!is_numeric($metric)){$metric=100;}
    if($metric<2){$metric=100;}

    $form[]=$tpl->field_hidden("SaveNic", $nic);
    $form[]=$tpl->field_ipaddr("IPADDR", "{tcp_address}", $IPADDR);
    $form[]=$tpl->field_ipaddr("NETMASK", "{netmask}", $NETMASK);
    $form[]=$tpl->field_ipaddr("GATEWAY", "{gateway}", $GATEWAY);
    $form[]=$tpl->field_ipaddr("BROADCAST", "{broadcast}", $BROADCAST);
    $form[]=$tpl->field_numeric("metric", "{metric}", $metric);
    $jsafter="LoadAjax('wizard-interfaces','$page?interface-tables=yes');dialogInstance1.close();";
    echo $tpl->form_outside("{interface} {$nic}", @implode("\n", $form),null,"{apply}",$jsafter);





}

function save_nic(){
    $tpl=new template_admin();

    $tpl->CLEAN_POST();
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));

    $IPADDR=explode(".",$_POST["IPADDR"]);
    $a192=$IPADDR[0];
    $b168=$IPADDR[1];
    $c1=$IPADDR[2];
    $c2=$IPADDR[3];

    if($c2==0){echo "{$_POST["IPADDR"]} invalid!";return;}
    if($c2==null){echo "{$_POST["IPADDR"]} invalid!";return;}
    if($c2==255){echo ".$c2 invalid!";return;}


    $system_nic=new system_nic($_POST["SaveNic"]);
    $system_nic->IPADDR=$_POST["IPADDR"];
    $system_nic->NETMASK=$_POST["NETMASK"];
    $system_nic->GATEWAY=$_POST["GATEWAY"];
    $system_nic->BROADCAST=$_POST["BROADCAST"];
    $system_nic->metric=$_POST["metric"];


    foreach ($_POST as $num=>$ligne){
        $ligne=url_decode_special_tool($ligne);
        $savedsettings["NET_INTERFACES"][$_POST["SaveNic"]][$num]=$ligne;

    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempWizard",base64_encode(serialize($savedsettings)));

}

function interfaces_table(){
    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
    if($DisableNetworking==1){return;}
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));
    $tpl=new template_admin();
    $page=CurrentPageName();
    $NICS=new networking();
    $Local_interfaces=$NICS->Local_interfaces(true);
    if(count($Local_interfaces)==0){echo $tpl->FATAL_ERROR_SHOW_128("{unable_to_retreive_network_information_refresh}");return;}

    $html[]="<table class='table table-bordered'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{interface}</th>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>{MAC}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $sock=new sockets();



    foreach ($Local_interfaces as $nic){

        $MAIN=$savedsettings["NET_INTERFACES"][$nic];



        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nicstatus/$nic"));
        $nicinfos=$data->Info;
        $tbl=explode(";",$nicinfos);

        $IPADDR=$MAIN["IPADDR"];
        $NETMASK=$MAIN["NETMASK"];
        $GATEWAY=$MAIN["GATEWAY"];
        $metric=$MAIN["metric"];
        $BROADCAST=$MAIN["BROADCAST"];
        $KEEPNET=$MAIN["KEEPNET"];
        $VPS_COMPATIBLE=$MAIN["VPS_COMPATIBLE"];

        $system_nic=new system_nic($nic);
        if($IPADDR==null){$IPADDR=$system_nic->IPADDR;}
        if($NETMASK==null){$NETMASK=$system_nic->NETMASK;}
        if($GATEWAY==null){$GATEWAY=$system_nic->GATEWAY;}
        if($BROADCAST==null){$BROADCAST=$system_nic->BROADCAST;}
        if($metric==null){$metric=$system_nic->metric;}
        if(!is_numeric($metric)){$metric=100;}
        $MAC=$tbl[1];

        if(!$system_nic->IsConfigured()){
            $system_nic->IPADDR=$IPADDR;
            $system_nic->NETMASK=$NETMASK;
            $system_nic->GATEWAY=$GATEWAY;
            $system_nic->BROADCAST=$BROADCAST;
            $system_nic->metric=$metric;
            $system_nic->SaveNic();

        }


        if($metric<2){$metric=100;}
        $html[]="<tr>";
        $html[]="<td width=1%>".$tpl->td_href($nic,"<strong>$NETMASK, {gateway} $GATEWAY</strong>","Loadjs('$page?interface-js=$nic')")."</td>";
        $html[]="<td width=1%>".$tpl->td_href($IPADDR,"<strong>$NETMASK, {gateway} $GATEWAY</strong>","Loadjs('$page?interface-js=$nic')")."</td>";
        $html[]="<td width=1%>".$tpl->td_href($MAC,"<strong>$NETMASK, {gateway} $GATEWAY</strong>","Loadjs('$page?interface-js=$nic')")."</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function hostclean($hostname){

    $hostname=str_replace([':','|','@','<','>','+','²','^','°', '\\', '/', '*',' ','(',')','{','}','[',']','%','$',';',',','?','=','#','&','`','"'], '', $hostname);
    return trim(strtolower($hostname));
}

function step1_save(){

    $sock=new sockets();
    $sidentity=new sidentity();
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));

    if(isset($_POST["netbiosname"])) {



        $sidentity->SET("myhostname",hostclean($_POST["netbiosname"]) . "." . hostclean($_POST["domain"]));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("myhostname", hostclean($_POST["netbiosname"]) . "." . hostclean($_POST["domain"]));
    }


    if(isset($_POST["DNS1"])) {

        $MainArray = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolvConf")));
        if ($_POST["DNS1"] <> null) {
            $MainArray["DNS1"] = $_POST["DNS1"];
        }

        if ($_POST["DNS2"] <> null) {
            $MainArray["DNS2"] = $_POST["DNS2"];
        }

        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("resolvConf",base64_encode(serialize($MainArray)));

    }





    foreach ($_POST as $num=>$ligne){
        $ligne=url_decode_special_tool($ligne);
        $savedsettings[$num]=$ligne;
        $_POST[$num]=$ligne;
        $sidentity->SET($num,$ligne);

    }

    $GLOBALS["TIMEZONES"]=$_POST["timezones"];
    $_SESSION["TIMEZONES"]=$_POST["timezones"];
    if(isset($_POST["timezones"])){$sock->SET_INFO("timezones",$_POST["timezones"]);}
    $timezoneenc=urlencode(base64_encode(trim($_POST["timezone"])));
    $data=$sock->getFrameWork("system.php?zoneinfo-set=$timezoneenc");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempWizard",base64_encode(serialize($savedsettings)));

}

function step2_save(){
    $sock=new sockets();
    $users=new usersMenus();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));

    if($_POST["company_www"]<>null){

        $_POST["company_www"]=url_decode_special_tool(hostclean($_POST["company_www"]));
        if(preg_match("#^http#", $_POST["company_www"])){
            $parse_url=parse_url( $_POST["company_www"]);
            if(isset($parse_url["host"])){$_POST["company_www"]=$parse_url["host"];}
        }
    }

    $sidentity=new sidentity();
    $_POST["company_www"]=$tpl->CLEAN_BAD_CHARSNET($_POST["company_www"]);


    foreach ($_POST as $num=>$ligne){
        $ligne=url_decode_special_tool($ligne);
        $savedsettings[$num]=$ligne;
        $sidentity->SET($num,$ligne);
        $_POST[$num]=$ligne;
    }



    if(!invalid_mail($_POST["mail"])){
        echo "<br>{$_POST["mail"]} INVALID !!!!! ";
        return;
    }
    $_POST["mail"]=$tpl->CLEAN_BAD_CHARMAIL($_POST["mail"]);

    if($users->SQUID_INSTALLED){
        $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("vm.swappiness",5);
        $sock->SET_INFO("EnableUfdbGuard", "0");
        $sock->SET_INFO("PDSNInUfdb", "0");
        $savedsettings["EnableWebFiltering"]=0;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempWizard",base64_encode(serialize($savedsettings)));
    $sock->SET_INFO("EnableArpDaemon", 0);
    $sock->SET_INFO("EnablePHPFPM",0);
    $sock->SET_INFO("EnableFreeWeb",0);
    $sock->SET_INFO("SlapdThreads", 2);
    $sock->SET_INFO("NewLicServer", 1);
    $sock->SET_INFO('Migration',1);

}

function choose_products_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));
    $savedsettings["artica_method"]=$_POST["artica_method"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempWizard",base64_encode(serialize($savedsettings)));
}
function choosed_products():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $choosed=intval($_GET["choosed"]);
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));
    $savedsettings["artica_method"]=$choosed;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempWizard",base64_encode(serialize($savedsettings)));
    $jsafter="LoadAjax('wizard','$page?step3=yes');";
    echo "$jsafter\n";
    return true;
}

function choose_products(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.langages.inc");
    $GLOBALS["DEBUG_TEMPLATE"]=true;
    $langAutodetect=new articaLang();
    $DetectedLanguage=$langAutodetect->get_languages();
    $GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));
    $artica_method=intval($savedsettings["artica_method"]);


    $jsback="LoadAjax('wizard','$page?step2=yes');";

   $backbutton=$tpl->button_autnonome("{back}",$jsback,"fa-solid fa-chevrons-left",null,240,"btn-warning");

    $html[]="<H2>{aks_articasrv_type}</H2>";
    $html[]="<p>{aks_articasrv_type_explain}</p>";

    $js="Loadjs('$page?choosed=0')";

    $html[]=" <div class='jumbotron'>
                        <h1>{APP_SQUID}</h1>
                        <p>{simple_proxy_and_webfiltering}</p>
                        <p><a href='javascript:blur();' OnClick=\"javascript:$js\" class='btn btn-primary btn-lg' role='button'>{select} {APP_SQUID}</a>
                        </p>
                    </div>
                </div>";

    $js="Loadjs('$page?choosed=1')";
    $html[]=" <div class='jumbotron'>
                        <h1>{APP_DNS_FIREWALL}</h1>
                        <p>{APP_DNS_FIREWALL_ABOUT}</p>
                        <p><a href='javascript:blur()' OnClick=\"javascript:$js\" class='btn btn-primary btn-lg' role='button'>{select} {APP_DNS_FIREWALL}</a>
                        </p>
                    </div>
                </div>";

    $js="Loadjs('$page?choosed=2')";
    $html[]=" <div class='jumbotron'>
                        <h1>{minimalist_gateway}</h1>
                        <p>{minimalist_gateway_short}</p>
                        <p><a href='javascript:blur()' OnClick=\"javascript:$js\" class='btn btn-primary btn-lg' role='button'>{select} {minimalist_gateway}</a>
                        </p>
                    </div>
                </div>";


    $html[]=$backbutton;
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}

function  step2(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.langages.inc");
    $GLOBALS["DEBUG_TEMPLATE"]=true;
    $langAutodetect=new articaLang();
    $DetectedLanguage=$langAutodetect->get_languages();
    $GLOBALS["FIXED_LANGUAGE"]=$DetectedLanguage;
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));
    $company_name_txtjs=$tpl->javascript_parse_text("{company_name}");
    $domain=$savedsettings["domain"];
    $GoldKey=$savedsettings["GoldKey"];
    $organization=$savedsettings["organization"];
    $employees=$savedsettings["employees"];
    $company_name=$savedsettings["company_name"];
    $country=$savedsettings["country"];
    $city=$savedsettings["city"];
    $mail=$savedsettings["mail"];
    $telephone=$savedsettings["telephone"];
    $UseServerV=$savedsettings["UseServer"];
    $smtp_domainname=$savedsettings["smtp_domainname"];
    $company_www=$savedsettings["company_www"];

    if($country==null){
        $tpl=new templates();
        if($tpl->language=="fr"){$country="France";}
        if($tpl->language=="en"){$country="United States";}
        if($tpl->language=="br"){$country="Brazil";}
        if($tpl->language=="pt"){$country="Portugal";}
        if($tpl->language=="de"){$country="Germany";}
        if($country==null){$country="United States";}
    }

    $tpl=new template_admin();
    $form[]=$tpl->field_hidden("smtp_domainname", $domain);
    $form[]=$tpl->field_text("mail", "{your_email_address}", $mail,true);
    $form[]=$tpl->field_text("organization", "{organization}", $organization,true);

    $jsback="LoadAjax('wizard','$page?step1=yes');";
    $jsafter="LoadAjax('wizard','$page?stepChoose=yes');";
    $tpl->form_add_button("{back}", $jsback);
    $html[]=$tpl->form_outside("{virtual_company}", @implode("\n", $form),null,"{next}",$jsafter);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}

function step3(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));

    $users=new usersMenus();
    $sock=new sockets();
    $CPU=$users->CPU_NUMBER;
    $memory=intval($sock->getFrameWork("services.php?total-memory=yes"));
    if($memory==0){$memory=intval($sock->getFrameWork("services.php?total-memory=yes"));}
    if($memory==0){$memory=round($users->MEM_TOTAL_INSTALLEE/1024);}

    $main_array[0]="{webproxy_service} {or} {transparent_mode}";
    //$main_array[1]="{hotspot_service}";
    //$main_array[2]="{categories_appliance}";
    $main_array[3]="{LOAD_BALACING_SERVICE}";
    if($users->NGINX_INSTALLED){
        $main_array[4]="{reverse_proxy_service}";
    }
    //$main_array[5]="{artica_meta_server}";
    $main_array[6]="{gateway_mode}";



    $array[0]="{full_features}";
    $array[1]="{medium_features}";
    $array[2]="{minimal_features}";
    $array[3]="{router_feature}";


    $ARRAYF[null]="{no_web_filtering}";
    $ARRAYF[0]="{block_unproductive_websites}";
    $ARRAYF[1]="{block_sexual_websites}";
    $ARRAYF[2]="{block_susp_websites}";
    $ARRAYF[3]="{block_multi_websites}";

    $WIZMEM=false;
    $wizard_warn_memory=$tpl->_ENGINE_parse_body("{wizard_warn_memory}");
    if($users->PROXYTINY_APPLIANCE){
        if($memory<1000){
            $wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
            $WIZMEM=true;
        }
    }
    if($users->SAMBA_APPLIANCE){
        if($memory<1000){
            $wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
            $WIZMEM=true;
        }
    }
    if($users->SMTP_APPLIANCE){
        if($memory<1000){
            $wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
            $WIZMEM=true;
        }
    }

    if($users->LOAD_BALANCE_APPLIANCE){
        if($memory<750){
            $wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%F", "750M", $wizard_warn_memory);
            $WIZMEM=true;
        }
    }

    if($users->LOAD_BALANCE_APPLIANCE){
        if($memory<1000){
            $wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%F", "1G", $wizard_warn_memory);
            $WIZMEM=true;
        }
    }




    if(!$WIZMEM){
        if( ($memory<2450) OR ($CPU<2)){
            $wizard_warn_memory=str_replace("%M", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%s", $memory."MB", $wizard_warn_memory);
            $wizard_warn_memory=str_replace("%F", "2.5G", $wizard_warn_memory);
            $WIZMEM=true;
        }
    }

    if($WIZMEM){$html[]="<div class='alert alert-danger'>$wizard_warn_memory</div>";}
    if($savedsettings["administrator"]==null){$savedsettings["administrator"]="Manager";}
    if($savedsettings["administratorpass"]==null){$savedsettings["administratorpass"]="secret";}


    if($users->SQUID_INSTALLED){
        if($savedsettings["main-choose"]==null){$savedsettings["main-choose"]=0;}
        if( $savedsettings["WizardWebFilteringLevel"]==null){ $savedsettings["WizardWebFilteringLevel"]=0;}
        if( $savedsettings["SquidPerformance"]==null){ $savedsettings["SquidPerformance"]=1;}

    }

    $form[]=$tpl->field_hidden("main-choose", 0);
    $form[]=$tpl->field_text("administrator", "{username}", $savedsettings["administrator"],true);
    $form[]=$tpl->field_password("administratorpass", "{password}", $savedsettings["administratorpass"]);
    $form[]=$tpl->field_password("administratorpass2", "{confirm}", $savedsettings["administratorpass"]);


    $jsback="LoadAjax('wizard','$page?step2=yes');";
    $jsafter="LoadAjax('wizard','$page?step-final=yes');";

    $tpl->form_add_button("{back}", $jsback);


    $html[]=$tpl->form_outside("{artica_manager}", @implode("\n", $form),"{miniadm_wizard_admin_explain}","{build_parameters}",$jsafter);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}


function step3_save(){
    $sock=new sockets();
    $tpl=new template_admin();
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));

    $sidentity=new sidentity();
    foreach ($_POST as $num=>$ligne){
        $ligne=url_decode_special_tool($ligne);
        $savedsettings[$num]=$ligne;
        $_POST[$num]=$ligne;
        $sidentity->SET($num,$ligne);

    }

    $choosen=intval($_POST["main-choose"]);

    if($_POST["administratorpass"]<>$_POST["administratorpass2"]){
        echo $tpl->_ENGINE_parse_body("{error}: {password_mismatch}");
        return;
    }

    if( $choosen == 0 ){
        $sock->SET_INFO('AsHotSpotAppliance','0');
        $sock->SET_INFO('AsHapProxyAppliance','0');
        $sock->SET_INFO('AsReverseProxyAppliance','0');
        $sock->SET_INFO('AsTransparentProxy','0');
        $sock->SET_INFO('AsMetaServer','0');
        $sock->SET_INFO('AsDNSDCHPServer','0');

    }

    if( $choosen == 1 ){
        $sock->SET_INFO('AsHotSpotAppliance','1');
        $sock->SET_INFO('AsHapProxyAppliance','0');
        $sock->SET_INFO('AsCategoriesAppliance','0');
        $sock->SET_INFO('AsReverseProxyAppliance','0');
        $sock->SET_INFO('AsTransparentProxy','0');
        $sock->SET_INFO('AsMetaServer','0');
        $sock->SET_INFO('AsDNSDCHPServer','0');
    }

    if( $choosen == 2 ){
        $sock->SET_INFO('AsHotSpotAppliance','0');
        $sock->SET_INFO('AsHapProxyAppliance','0');
        $sock->SET_INFO('AsReverseProxyAppliance','0');
        $sock->SET_INFO('AsTransparentProxy','0');
        $sock->SET_INFO('AsMetaServer','0');
        $sock->SET_INFO('AsDNSDCHPServer','0');
    }
    if( $choosen == 3 ){
        $sock->SET_INFO('AsHotSpotAppliance','0');
        $sock->SET_INFO('AsHapProxyAppliance','1');
        $sock->SET_INFO('AsReverseProxyAppliance','0');
        $sock->SET_INFO('AsCategoriesAppliance','0');
        $sock->SET_INFO('AsTransparentProxy','0');
        $sock->SET_INFO('AsMetaServer','0');
        $sock->SET_INFO('AsDNSDCHPServer','0');
        $sock->SET_INFO('WizardNoWebFiltering','1');
    }
    if( $choosen == 4 ){
        $sock->SET_INFO('AsHotSpotAppliance','0');
        $sock->SET_INFO('AsHapProxyAppliance','0');
        $sock->SET_INFO('AsReverseProxyAppliance','1');
        $sock->SET_INFO('AsTransparentProxy','0');
        $sock->SET_INFO('AsMetaServer','0');
        $sock->SET_INFO('AsDNSDCHPServer','0');
        $sock->SET_INFO('WizardNoWebFiltering','1');

    }

    if( $choosen == 5 ){
        $sock->SET_INFO('AsHotSpotAppliance','0');
        $sock->SET_INFO('AsHapProxyAppliance','0');
        $sock->SET_INFO('AsCategoriesAppliance','0');
        $sock->SET_INFO('AsReverseProxyAppliance','0');
        $sock->SET_INFO('AsTransparentProxy','0');
        $sock->SET_INFO('AsMetaServer','1');
        $sock->SET_INFO('AsDNSDCHPServer','0');

    }

    if( $choosen == 6 ){
        $sock->SET_INFO('AsHotSpotAppliance','0');
        $sock->SET_INFO('AsHapProxyAppliance','0');
        $sock->SET_INFO('AsReverseProxyAppliance','0');
        $sock->SET_INFO('AsTransparentProxy','0');
        $sock->SET_INFO('AsMetaServer','0');
        $sock->SET_INFO('AsDNSDCHPServer','1');
        $sock->SET_INFO('WizardNoWebFiltering','1');
    }
    $sock->SET_INFO("SquidPerformance", $_POST["SquidPerformance"]);
    $sock->SET_INFO("WizardWebFilteringLevel", $_POST["WizardWebFilteringLevel"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempWizard",base64_encode(serialize($savedsettings)));
    @file_put_contents("/etc/artica-postfix/settings/Daemons/TempWizardback", serialize($savedsettings));


}

function step_final(){
    $sock=new sockets();
    $tpl=new template_admin();
    $t=time();
    $pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");


    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TempWizard")));
    $sock->SaveConfigFile(base64_encode(serialize($savedsettings)), "WizardSavedSettings");
    $passwordtoshow=substr($savedsettings["administratorpass"],0,4).'...';
    $settings_final_show=$tpl->_ENGINE_parse_body("{settings_final_show}");
    $settings_final_show=str_replace("%a", "<strong style='color:#C91111'>{$savedsettings["administrator"]}</strong>", $settings_final_show);
    $settings_final_show=str_replace("%p", "<strong  style='color:#C91111'>{$passwordtoshow}</strong>", $settings_final_show);
    $sock->getFrameWork("system.php?wizard-execute=yes");


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/wizard.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/wizard.log";
    $ARRAY["CMD"]="system.php?wizard-execute=yes";
    $ARRAY["TITLE"]="{build_parameters}";
    $ARRAY["AFTER"]="document.location.href='fw.login.php'";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.wizard.progress.php?content=$prgress&mainid=progress-firehol-restart')";


    $html[]="<div id='progress-firehol-restart'></div>
	<div class='alert alert-success'>$settings_final_show</div>
	<script>
		$jsrestart
	</script>
	
	";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}


function GetNamesServers(){

    $resolv_conf=explode("\n",@file_get_contents("/etc/resolv.conf"));
    foreach ($resolv_conf as $lines){
        $lines=trim($lines);
        if($lines==null){continue;}
        if(preg_match("#127\.0\.0\.1#",$lines)){continue;}
        if(preg_match("#^nameserver\s+(.+)#",$lines,$re)){
            $g=trim($re[1]);
            if($g=="127.0.0.1"){continue;}
            $arrayNameServers[]=$g;
        }
    }

    if(count($arrayNameServers)==0){
        $resolv_conf=file("/etc/resolvconf/resolv.conf.d/original");
        foreach ($resolv_conf as $lines){
            $lines=trim($lines);
            if(preg_match("#127\.0\.0\.1#",$lines)){continue;}
            if(preg_match("#^nameserver\s+(.+)#",$lines,$re)){
                $g=trim($re[1]);
                if($g=="127.0.0.1"){continue;}
                $arrayNameServers[]=$g;
            }
        }

    }
    return $arrayNameServers;
}
function timezonearray(){

    $timezone[]="Africa/Abidjan";                 //,0x000000 },
    $timezone[]="Africa/Accra";                   //,0x000055 },
    $timezone[]="Africa/Addis_Ababa";             //,0x0000FD },
    $timezone[]="Africa/Algiers";                 //,0x000153 },
    $timezone[]="Africa/Asmara";                  //,0x00027E },
    $timezone[]="Africa/Asmera";                  //,0x0002D4 },
    $timezone[]="Africa/Bamako";                  //,0x00032A },
    $timezone[]="Africa/Bangui";                  //,0x000395 },
    $timezone[]="Africa/Banjul";                  //,0x0003EA },
    $timezone[]="Africa/Bissau";                  //,0x000461 },
    $timezone[]="Africa/Blantyre";                //,0x0004C7 },
    $timezone[]="Africa/Brazzaville";             //,0x00051C },
    $timezone[]="Africa/Bujumbura";               //,0x000571 },
    $timezone[]="Africa/Cairo";                   //,0x0005B5 },
    $timezone[]="Africa/Casablanca";              //,0x00097C },
    $timezone[]="Africa/Ceuta";                   //,0x000A58 },
    $timezone[]="Africa/Conakry";                 //,0x000D5F },
    $timezone[]="Africa/Dakar";                   //,0x000DCA },
    $timezone[]="Africa/Dar_es_Salaam";           //,0x000E30 },
    $timezone[]="Africa/Djibouti";                //,0x000E9D },
    $timezone[]="Africa/Douala";                  //,0x000EF2 },
    $timezone[]="Africa/El_Aaiun";                //,0x000F47 },
    $timezone[]="Africa/Freetown";                //,0x000FAD },
    $timezone[]="Africa/Gaborone";                //,0x0010BC },
    $timezone[]="Africa/Harare";                  //,0x001117 },
    $timezone[]="Africa/Johannesburg";            //,0x00116C },
    $timezone[]="Africa/Kampala";                 //,0x0011DA },
    $timezone[]="Africa/Khartoum";                //,0x001259 },
    $timezone[]="Africa/Kigali";                  //,0x00136C },
    $timezone[]="Africa/Kinshasa";                //,0x0013C1 },
    $timezone[]="Africa/Lagos";                   //,0x00141C },
    $timezone[]="Africa/Libreville";              //,0x001471 },
    $timezone[]="Africa/Lome";                    //,0x0014C6 },
    $timezone[]="Africa/Luanda";                  //,0x00150A },
    $timezone[]="Africa/Lubumbashi";              //,0x00155F },
    $timezone[]="Africa/Lusaka";                  //,0x0015BA },
    $timezone[]="Africa/Malabo";                  //,0x00160F },
    $timezone[]="Africa/Maputo";                  //,0x001675 },
    $timezone[]="Africa/Maseru";                  //,0x0016CA },
    $timezone[]="Africa/Mbabane";                 //,0x001732 },
    $timezone[]="Africa/Mogadishu";               //,0x001788 },
    $timezone[]="Africa/Monrovia";                //,0x0017E3 },
    $timezone[]="Africa/Nairobi";                 //,0x001849 },
    $timezone[]="Africa/Ndjamena";                //,0x0018C8 },
    $timezone[]="Africa/Niamey";                  //,0x001934 },
    $timezone[]="Africa/Nouakchott";              //,0x0019A7 },
    $timezone[]="Africa/Ouagadougou";             //,0x001A12 },
    $timezone[]="Africa/Porto-Novo";              //,0x001A67 },
    $timezone[]="Africa/Sao_Tome";                //,0x001ACD },
    $timezone[]="Africa/Timbuktu";                //,0x001B22 },
    $timezone[]="Africa/Tripoli";                 //,0x001B8D },
    $timezone[]="Africa/Tunis";                   //,0x001C87 },
    $timezone[]="Africa/Windhoek";                //,0x001EB1 },
    $timezone[]="America/Adak";                   //,0x0020F8 },
    $timezone[]="America/Anchorage";              //,0x00246E },
    $timezone[]="America/Anguilla";               //,0x0027E2 },
    $timezone[]="America/Antigua";                //,0x002837 },
    $timezone[]="America/Araguaina";              //,0x00289D },
    $timezone[]="America/Argentina/Buenos_Aires"; //,0x0029F8 },
    $timezone[]="America/Argentina/Catamarca";    //,0x002BA6 },
    $timezone[]="America/Argentina/ComodRivadavia";  //,0x002D67 },
    $timezone[]="America/Argentina/Cordoba";      //,0x002F0D },
    $timezone[]="America/Argentina/Jujuy";        //,0x0030E2 },
    $timezone[]="America/Argentina/La_Rioja";     //,0x003296 },
    $timezone[]="America/Argentina/Mendoza";      //,0x00344E },
    $timezone[]="America/Argentina/Rio_Gallegos"; //,0x00360E },
    $timezone[]="America/Argentina/Salta";        //,0x0037C3 },
    $timezone[]="America/Argentina/San_Juan";     //,0x00396F },
    $timezone[]="America/Argentina/San_Luis";     //,0x003B27 },
    $timezone[]="America/Argentina/Tucuman";      //,0x003E05 },
    $timezone[]="America/Argentina/Ushuaia";      //,0x003FC1 },
    $timezone[]="America/Aruba";                  //,0x00417C },
    $timezone[]="America/Asuncion";               //,0x0041E2 },
    $timezone[]="America/Atikokan";               //,0x0044C7 },
    $timezone[]="America/Atka";                   //,0x00459D },
    $timezone[]="America/Bahia";                  //,0x004903 },
    $timezone[]="America/Barbados";               //,0x004A8C },
    $timezone[]="America/Belem";                  //,0x004B26 },
    $timezone[]="America/Belize";                 //,0x004C21 },
    $timezone[]="America/Blanc-Sablon";           //,0x004D9D },
    $timezone[]="America/Boa_Vista";              //,0x004E51 },
    $timezone[]="America/Bogota";                 //,0x004F5A },
    $timezone[]="America/Boise";                  //,0x004FC6 },
    $timezone[]="America/Buenos_Aires";           //,0x00535D },
    $timezone[]="America/Cambridge_Bay";          //,0x0054F6 },
    $timezone[]="America/Campo_Grande";           //,0x00581E },
    $timezone[]="America/Cancun";                 //,0x005B0D },
    $timezone[]="America/Caracas";                //,0x005D4F },
    $timezone[]="America/Catamarca";              //,0x005DB6 },
    $timezone[]="America/Cayenne";                //,0x005F5C },
    $timezone[]="America/Cayman";                 //,0x005FBE },
    $timezone[]="America/Chicago";                //,0x006013 },
    $timezone[]="America/Chihuahua";              //,0x00652A },
    $timezone[]="America/Coral_Harbour";          //,0x006779 },
    $timezone[]="America/Cordoba";                //,0x00680B },
    $timezone[]="America/Costa_Rica";             //,0x0069B1 },
    $timezone[]="America/Cuiaba";                 //,0x006A3B },
    $timezone[]="America/Curacao";                //,0x006D19 },
    $timezone[]="America/Danmarkshavn";           //,0x006D7F },
    $timezone[]="America/Dawson";                 //,0x006EC3 },
    $timezone[]="America/Dawson_Creek";           //,0x0071E0 },
    $timezone[]="America/Denver";                 //,0x0073BA },
    $timezone[]="America/Detroit";                //,0x007740 },
    $timezone[]="America/Dominica";               //,0x007A9F },
    $timezone[]="America/Edmonton";               //,0x007AF4 },
    $timezone[]="America/Eirunepe";               //,0x007EAC },
    $timezone[]="America/El_Salvador";            //,0x007FBF },
    $timezone[]="America/Ensenada";               //,0x008034 },
    $timezone[]="America/Fort_Wayne";             //,0x0084DB },
    $timezone[]="America/Fortaleza";              //,0x00839D },
    $timezone[]="America/Glace_Bay";              //,0x008745 },
    $timezone[]="America/Godthab";                //,0x008ABC },
    $timezone[]="America/Goose_Bay";              //,0x008D80 },
    $timezone[]="America/Grand_Turk";             //,0x00923D },
    $timezone[]="America/Grenada";                //,0x0094EC },
    $timezone[]="America/Guadeloupe";             //,0x009541 },
    $timezone[]="America/Guatemala";              //,0x009596 },
    $timezone[]="America/Guayaquil";              //,0x00961F },
    $timezone[]="America/Guyana";                 //,0x00967C },
    $timezone[]="America/Halifax";                //,0x0096FD },
    $timezone[]="America/Havana";                 //,0x009C13 },
    $timezone[]="America/Hermosillo";             //,0x009F86 },
    $timezone[]="America/Indiana/Indianapolis";   //,0x00A064 },
    $timezone[]="America/Indiana/Knox";           //,0x00A2F5 },
    $timezone[]="America/Indiana/Marengo";        //,0x00A68C },
    $timezone[]="America/Indiana/Petersburg";     //,0x00A932 },
    $timezone[]="America/Indiana/Tell_City";      //,0x00AE7F },
    $timezone[]="America/Indiana/Vevay";          //,0x00B118 },
    $timezone[]="America/Indiana/Vincennes";      //,0x00B353 },
    $timezone[]="America/Indiana/Winamac";        //,0x00B607 },
    $timezone[]="America/Indianapolis";           //,0x00AC15 },
    $timezone[]="America/Inuvik";                 //,0x00B8C0 },
    $timezone[]="America/Iqaluit";                //,0x00BBB7 },
    $timezone[]="America/Jamaica";                //,0x00BED9 },
    $timezone[]="America/Jujuy";                  //,0x00BF9E },
    $timezone[]="America/Juneau";                 //,0x00C148 },
    $timezone[]="America/Kentucky/Louisville";    //,0x00C4C6 },
    $timezone[]="America/Kentucky/Monticello";    //,0x00C8E4 },
    $timezone[]="America/Knox_IN";                //,0x00CC69 },
    $timezone[]="America/La_Paz";                 //,0x00CFDA },
    $timezone[]="America/Lima";                   //,0x00D041 },
    $timezone[]="America/Los_Angeles";            //,0x00D0E9 },
    $timezone[]="America/Louisville";             //,0x00D4FA },
    $timezone[]="America/Maceio";                 //,0x00D8EF },
    $timezone[]="America/Managua";                //,0x00DA29 },
    $timezone[]="America/Manaus";                 //,0x00DADC },
    $timezone[]="America/Marigot";                //,0x00DBDE },
    $timezone[]="America/Martinique";             //,0x00DC33 },
    $timezone[]="America/Mazatlan";               //,0x00DC9F },
    $timezone[]="America/Mendoza";                //,0x00DF0C },
    $timezone[]="America/Menominee";              //,0x00E0C0 },
    $timezone[]="America/Merida";                 //,0x00E441 },
    $timezone[]="America/Mexico_City";            //,0x00E67C },
    $timezone[]="America/Miquelon";               //,0x00E8F7 },
    $timezone[]="America/Moncton";                //,0x00EB69 },
    $timezone[]="America/Monterrey";              //,0x00F000 },
    $timezone[]="America/Montevideo";             //,0x00F247 },
    $timezone[]="America/Montreal";               //,0x00F559 },
    $timezone[]="America/Montserrat";             //,0x00FA6F },
    $timezone[]="America/Nassau";                 //,0x00FAC4 },
    $timezone[]="America/New_York";               //,0x00FE09 },
    $timezone[]="America/Nipigon";                //,0x010314 },
    $timezone[]="America/Nome";                   //,0x010665 },
    $timezone[]="America/Noronha";                //,0x0109E3 },
    $timezone[]="America/North_Dakota/Center";    //,0x010B13 },
    $timezone[]="America/North_Dakota/New_Salem"; //,0x010EA7 },
    $timezone[]="America/Panama";                 //,0x011250 },
    $timezone[]="America/Pangnirtung";            //,0x0112A5 },
    $timezone[]="America/Paramaribo";             //,0x0115DB },
    $timezone[]="America/Phoenix";                //,0x01166D },
    $timezone[]="America/Port-au-Prince";         //,0x01171B },
    $timezone[]="America/Port_of_Spain";          //,0x011936 },
    $timezone[]="America/Porto_Acre";             //,0x011837 },
    $timezone[]="America/Porto_Velho";            //,0x01198B },
    $timezone[]="America/Puerto_Rico";            //,0x011A81 },
    $timezone[]="America/Rainy_River";            //,0x011AEC },
    $timezone[]="America/Rankin_Inlet";           //,0x011E24 },
    $timezone[]="America/Recife";                 //,0x01210A },
    $timezone[]="America/Regina";                 //,0x012234 },
    $timezone[]="America/Resolute";               //,0x0123F2 },
    $timezone[]="America/Rio_Branco";             //,0x0126EB },
    $timezone[]="America/Rosario";                //,0x0127EE },
    $timezone[]="America/Santarem";               //,0x012994 },
    $timezone[]="America/Santiago";               //,0x012A99 },
    $timezone[]="America/Santo_Domingo";          //,0x012E42 },
    $timezone[]="America/Sao_Paulo";              //,0x012F08 },
    $timezone[]="America/Scoresbysund";           //,0x013217 },
    $timezone[]="America/Shiprock";               //,0x013505 },
    $timezone[]="America/St_Barthelemy";          //,0x013894 },
    $timezone[]="America/St_Johns";               //,0x0138E9 },
    $timezone[]="America/St_Kitts";               //,0x013E3C },
    $timezone[]="America/St_Lucia";               //,0x013E91 },
    $timezone[]="America/St_Thomas";              //,0x013EE6 },
    $timezone[]="America/St_Vincent";             //,0x013F3B },
    $timezone[]="America/Swift_Current";          //,0x013F90 },
    $timezone[]="America/Tegucigalpa";            //,0x0140B1 },
    $timezone[]="America/Thule";                  //,0x014130 },
    $timezone[]="America/Thunder_Bay";            //,0x014377 },
    $timezone[]="America/Tijuana";                //,0x0146C0 },
    $timezone[]="America/Toronto";                //,0x014A35 },
    $timezone[]="America/Tortola";                //,0x014F4C },
    $timezone[]="America/Vancouver";              //,0x014FA1 },
    $timezone[]="America/Virgin";                 //,0x0153DE },
    $timezone[]="America/Whitehorse";             //,0x015433 },
    $timezone[]="America/Winnipeg";               //,0x015750 },
    $timezone[]="America/Yakutat";                //,0x015B90 },
    $timezone[]="America/Yellowknife";            //,0x015EFB },
    $timezone[]="Antarctica/Casey";               //,0x01620B },
    $timezone[]="Antarctica/Davis";               //,0x016291 },
    $timezone[]="Antarctica/DumontDUrville";      //,0x01631B },
    $timezone[]="Antarctica/Mawson";              //,0x0163AD },
    $timezone[]="Antarctica/McMurdo";             //,0x016429 },
    $timezone[]="Antarctica/Palmer";              //,0x01672B },
    $timezone[]="Antarctica/Rothera";             //,0x016A47 },
    $timezone[]="Antarctica/South_Pole";          //,0x016ABD },
    $timezone[]="Antarctica/Syowa";               //,0x016DC5 },
    $timezone[]="Antarctica/Vostok";              //,0x016E33 },
    $timezone[]="Arctic/Longyearbyen";            //,0x016EA8 },
    $timezone[]="Asia/Aden";                      //,0x0171DA },
    $timezone[]="Asia/Almaty";                    //,0x01722F },
    $timezone[]="Asia/Amman";                     //,0x0173AE },
    $timezone[]="Asia/Anadyr";                    //,0x01766E },
    $timezone[]="Asia/Aqtau";                     //,0x01795C },
    $timezone[]="Asia/Aqtobe";                    //,0x017B5B },
    $timezone[]="Asia/Ashgabat";                  //,0x017D13 },
    $timezone[]="Asia/Ashkhabad";                 //,0x017E30 },
    $timezone[]="Asia/Baghdad";                   //,0x017F4D },
    $timezone[]="Asia/Bahrain";                   //,0x0180C2 },
    $timezone[]="Asia/Baku";                      //,0x018128 },
    $timezone[]="Asia/Bangkok";                   //,0x018410 },
    $timezone[]="Asia/Beirut";                    //,0x018465 },
    $timezone[]="Asia/Bishkek";                   //,0x018772 },
    $timezone[]="Asia/Brunei";                    //,0x01891E },
    $timezone[]="Asia/Calcutta";                  //,0x018980 },
    $timezone[]="Asia/Choibalsan";                //,0x0189F9 },
    $timezone[]="Asia/Chongqing";                 //,0x018B72 },
    $timezone[]="Asia/Chungking";                 //,0x018C61 },
    $timezone[]="Asia/Colombo";                   //,0x018D10 },
    $timezone[]="Asia/Dacca";                     //,0x018DAC },
    $timezone[]="Asia/Damascus";                  //,0x018E4D },
    $timezone[]="Asia/Dhaka";                     //,0x01919D },
    $timezone[]="Asia/Dili";                      //,0x01923E },
    $timezone[]="Asia/Dubai";                     //,0x0192C7 },
    $timezone[]="Asia/Dushanbe";                  //,0x01931C },
    $timezone[]="Asia/Gaza";                      //,0x01941F },
    $timezone[]="Asia/Harbin";                    //,0x019768 },
    $timezone[]="Asia/Ho_Chi_Minh";               //,0x01984F },
    $timezone[]="Asia/Hong_Kong";                 //,0x0198C7 },
    $timezone[]="Asia/Hovd";                      //,0x019A93 },
    $timezone[]="Asia/Irkutsk";                   //,0x019C0B },
    $timezone[]="Asia/Istanbul";                  //,0x019EF2 },
    $timezone[]="Asia/Jakarta";                   //,0x01A2DF },
    $timezone[]="Asia/Jayapura";                  //,0x01A389 },
    $timezone[]="Asia/Jerusalem";                 //,0x01A40D },
    $timezone[]="Asia/Kabul";                     //,0x01A73C },
    $timezone[]="Asia/Kamchatka";                 //,0x01A78D },
    $timezone[]="Asia/Karachi";                   //,0x01AA72 },
    $timezone[]="Asia/Kashgar";                   //,0x01AC3F },
    $timezone[]="Asia/Kathmandu";                 //,0x01AD10 },
    $timezone[]="Asia/Katmandu";                  //,0x01AD76 },
    $timezone[]="Asia/Kolkata";                   //,0x01ADDC },
    $timezone[]="Asia/Krasnoyarsk";               //,0x01AE55 },
    $timezone[]="Asia/Kuala_Lumpur";              //,0x01B13E },
    $timezone[]="Asia/Kuching";                   //,0x01B1FB },
    $timezone[]="Asia/Kuwait";                    //,0x01B2E9 },
    $timezone[]="Asia/Macao";                     //,0x01B33E },
    $timezone[]="Asia/Macau";                     //,0x01B479 },
    $timezone[]="Asia/Magadan";                   //,0x01B5B4 },
    $timezone[]="Asia/Makassar";                  //,0x01B897 },
    $timezone[]="Asia/Manila";                    //,0x01B950 },
    $timezone[]="Asia/Muscat";                    //,0x01B9D5 },
    $timezone[]="Asia/Nicosia";                   //,0x01BA2A },
    $timezone[]="Asia/Novokuznetsk";              //,0x01BD12 },
    $timezone[]="Asia/Novosibirsk";               //,0x01C015 },
    $timezone[]="Asia/Omsk";                      //,0x01C309 },
    $timezone[]="Asia/Oral";                      //,0x01C5F1 },
    $timezone[]="Asia/Phnom_Penh";                //,0x01C7C1 },
    $timezone[]="Asia/Pontianak";                 //,0x01C839 },
    $timezone[]="Asia/Pyongyang";                 //,0x01C8FA },
    $timezone[]="Asia/Qatar";                     //,0x01C967 },
    $timezone[]="Asia/Qyzylorda";                 //,0x01C9CD },
    $timezone[]="Asia/Rangoon";                   //,0x01CBA3 },
    $timezone[]="Asia/Riyadh";                    //,0x01CC1B },
    $timezone[]="Asia/Saigon";                    //,0x01CC70 },
    $timezone[]="Asia/Sakhalin";                  //,0x01CCE8 },
    $timezone[]="Asia/Samarkand";                 //,0x01CFE8 },
    $timezone[]="Asia/Seoul";                     //,0x01D11E },
    $timezone[]="Asia/Shanghai";                  //,0x01D1C2 },
    $timezone[]="Asia/Singapore";                 //,0x01D2A2 },
    $timezone[]="Asia/Taipei";                    //,0x01D359 },
    $timezone[]="Asia/Tashkent";                  //,0x01D471 },
    $timezone[]="Asia/Tbilisi";                   //,0x01D5A2 },
    $timezone[]="Asia/Tehran";                    //,0x01D75C },
    $timezone[]="Asia/Tel_Aviv";                  //,0x01D9CA },
    $timezone[]="Asia/Thimbu";                    //,0x01DCF9 },
    $timezone[]="Asia/Thimphu";                   //,0x01DD5F },
    $timezone[]="Asia/Tokyo";                     //,0x01DDC5 },
    $timezone[]="Asia/Ujung_Pandang";             //,0x01DE4E },
    $timezone[]="Asia/Ulaanbaatar";               //,0x01DECA },
    $timezone[]="Asia/Ulan_Bator";                //,0x01E025 },
    $timezone[]="Asia/Urumqi";                    //,0x01E172 },
    $timezone[]="Asia/Vientiane";                 //,0x01E239 },
    $timezone[]="Asia/Vladivostok";               //,0x01E2B1 },
    $timezone[]="Asia/Yakutsk";                   //,0x01E59E },
    $timezone[]="Asia/Yekaterinburg";             //,0x01E884 },
    $timezone[]="Asia/Yerevan";                   //,0x01EB90 },
    $timezone[]="Atlantic/Azores";                //,0x01EE94 },
    $timezone[]="Atlantic/Bermuda";               //,0x01F397 },
    $timezone[]="Atlantic/Canary";                //,0x01F678 },
    $timezone[]="Atlantic/Cape_Verde";            //,0x01F94E },
    $timezone[]="Atlantic/Faeroe";                //,0x01F9C7 },
    $timezone[]="Atlantic/Faroe";                 //,0x01FC6B },
    $timezone[]="Atlantic/Jan_Mayen";             //,0x01FF0F },
    $timezone[]="Atlantic/Madeira";               //,0x020241 },
    $timezone[]="Atlantic/Reykjavik";             //,0x02074A },
    $timezone[]="Atlantic/South_Georgia";         //,0x020903 },
    $timezone[]="Atlantic/St_Helena";             //,0x020C1B },
    $timezone[]="Atlantic/Stanley";               //,0x020947 },
    $timezone[]="Australia/ACT";                  //,0x020C70 },
    $timezone[]="Australia/Adelaide";             //,0x020F8D },
    $timezone[]="Australia/Brisbane";             //,0x0212B9 },
    $timezone[]="Australia/Broken_Hill";          //,0x021380 },
    $timezone[]="Australia/Canberra";             //,0x0216BE },
    $timezone[]="Australia/Currie";               //,0x0219DB },
    $timezone[]="Australia/Darwin";               //,0x021D0E },
    $timezone[]="Australia/Eucla";                //,0x021D94 },
    $timezone[]="Australia/Hobart";               //,0x021E69 },
    $timezone[]="Australia/LHI";                  //,0x0221C7 },
    $timezone[]="Australia/Lindeman";             //,0x022462 },
    $timezone[]="Australia/Lord_Howe";            //,0x022543 },
    $timezone[]="Australia/Melbourne";            //,0x0227EE },
    $timezone[]="Australia/North";                //,0x022B13 },
    $timezone[]="Australia/NSW";                  //,0x022B87 },
    $timezone[]="Australia/Perth";                //,0x022EA4 },
    $timezone[]="Australia/Queensland";           //,0x022F7C },
    $timezone[]="Australia/South";                //,0x023028 },
    $timezone[]="Australia/Sydney";               //,0x023345 },
    $timezone[]="Australia/Tasmania";             //,0x023682 },
    $timezone[]="Australia/Victoria";             //,0x0239C7 },
    $timezone[]="Australia/West";                 //,0x023CE4 },
    $timezone[]="Australia/Yancowinna";           //,0x023D9A },
    $timezone[]="Brazil/Acre";                    //,0x0240BC },
    $timezone[]="Brazil/DeNoronha";               //,0x0241BB },
    $timezone[]="Brazil/East";                    //,0x0242DB },
    $timezone[]="Brazil/West";                    //,0x0245B8 },
    $timezone[]="Canada/Atlantic";                //,0x0246B0 },
    $timezone[]="Canada/Central";                 //,0x024B98 },
    $timezone[]="Canada/East-Saskatchewan";       //,0x0254A2 },
    $timezone[]="Canada/Eastern";                 //,0x024FB2 },
    $timezone[]="Canada/Mountain";                //,0x02562B },
    $timezone[]="Canada/Newfoundland";            //,0x0259A1 },
    $timezone[]="Canada/Pacific";                 //,0x025ECC },
    $timezone[]="Canada/Saskatchewan";            //,0x0262E5 },
    $timezone[]="Canada/Yukon";                   //,0x02646E },
    $timezone[]="CET";                            //,0x026771 },
    $timezone[]="Chile/Continental";              //,0x026A7A },
    $timezone[]="Chile/EasterIsland";             //,0x026E15 },
    $timezone[]="CST6CDT";                        //,0x027157 },
    $timezone[]="Cuba";                           //,0x0274A8 },
    $timezone[]="EET";                            //,0x02781B },
    $timezone[]="Egypt";                          //,0x027ACE },
    $timezone[]="Eire";                           //,0x027E95 },
    $timezone[]="EST";                            //,0x0283A6 },
    $timezone[]="EST5EDT";                        //,0x0283EA },
    $timezone[]="Etc/GMT";                        //,0x02873B },
    $timezone[]="Etc/GMT+0";                      //,0x028807 },
    $timezone[]="Etc/GMT+1";                      //,0x028891 },
    $timezone[]="Etc/GMT+10";                     //,0x02891E },
    $timezone[]="Etc/GMT+11";                     //,0x0289AC },
    $timezone[]="Etc/GMT+12";                     //,0x028A3A },
    $timezone[]="Etc/GMT+2";                      //,0x028B55 },
    $timezone[]="Etc/GMT+3";                      //,0x028BE1 },
    $timezone[]="Etc/GMT+4";                      //,0x028C6D },
    $timezone[]="Etc/GMT+5";                      //,0x028CF9 },
    $timezone[]="Etc/GMT+6";                      //,0x028D85 },
    $timezone[]="Etc/GMT+7";                      //,0x028E11 },
    $timezone[]="Etc/GMT+8";                      //,0x028E9D },
    $timezone[]="Etc/GMT+9";                      //,0x028F29 },
    $timezone[]="Etc/GMT-0";                      //,0x0287C3 },
    $timezone[]="Etc/GMT-1";                      //,0x02884B },
    $timezone[]="Etc/GMT-10";                     //,0x0288D7 },
    $timezone[]="Etc/GMT-11";                     //,0x028965 },
    $timezone[]="Etc/GMT-12";                     //,0x0289F3 },
    $timezone[]="Etc/GMT-13";                     //,0x028A81 },
    $timezone[]="Etc/GMT-14";                     //,0x028AC8 },
    $timezone[]="Etc/GMT-2";                      //,0x028B0F },
    $timezone[]="Etc/GMT-3";                      //,0x028B9B },
    $timezone[]="Etc/GMT-4";                      //,0x028C27 },
    $timezone[]="Etc/GMT-5";                      //,0x028CB3 },
    $timezone[]="Etc/GMT-6";                      //,0x028D3F },
    $timezone[]="Etc/GMT-7";                      //,0x028DCB },
    $timezone[]="Etc/GMT-8";                      //,0x028E57 },
    $timezone[]="Etc/GMT-9";                      //,0x028EE3 },
    $timezone[]="Etc/GMT0";                       //,0x02877F },
    $timezone[]="Etc/Greenwich";                  //,0x028F6F },
    $timezone[]="Etc/UCT";                        //,0x028FB3 },
    $timezone[]="Etc/Universal";                  //,0x028FF7 },
    $timezone[]="Etc/UTC";                        //,0x02903B },
    $timezone[]="Etc/Zulu";                       //,0x02907F },
    $timezone[]="Europe/Amsterdam";               //,0x0290C3 },
    $timezone[]="Europe/Andorra";                 //,0x029501 },
    $timezone[]="Europe/Athens";                  //,0x02977D },
    $timezone[]="Europe/Belfast";                 //,0x029AC0 },
    $timezone[]="Europe/Belgrade";                //,0x029FF7 },
    $timezone[]="Europe/Berlin";                  //,0x02A2C0 },
    $timezone[]="Europe/Bratislava";              //,0x02A616 },
    $timezone[]="Europe/Brussels";                //,0x02A948 },
    $timezone[]="Europe/Bucharest";               //,0x02AD7F },
    $timezone[]="Europe/Budapest";                //,0x02B0A9 },
    $timezone[]="Europe/Chisinau";                //,0x02B41C },
    $timezone[]="Europe/Copenhagen";              //,0x02B7AA },
    $timezone[]="Europe/Dublin";                  //,0x02BAB4 },
    $timezone[]="Europe/Gibraltar";               //,0x02BFC5 },
    $timezone[]="Europe/Guernsey";                //,0x02C41C },
    $timezone[]="Europe/Helsinki";                //,0x02C953 },
    $timezone[]="Europe/Isle_of_Man";             //,0x02CC09 },
    $timezone[]="Europe/Istanbul";                //,0x02D140 },
    $timezone[]="Europe/Jersey";                  //,0x02D52D },
    $timezone[]="Europe/Kaliningrad";             //,0x02DA64 },
    $timezone[]="Europe/Kiev";                    //,0x02DDC7 },
    $timezone[]="Europe/Lisbon";                  //,0x02E0DE },
    $timezone[]="Europe/Ljubljana";               //,0x02E5E2 },
    $timezone[]="Europe/London";                  //,0x02E8AB },
    $timezone[]="Europe/Luxembourg";              //,0x02EDE2 },
    $timezone[]="Europe/Madrid";                  //,0x02F238 },
    $timezone[]="Europe/Malta";                   //,0x02F5FE },
    $timezone[]="Europe/Mariehamn";               //,0x02F9B7 },
    $timezone[]="Europe/Minsk";                   //,0x02FC6D },
    $timezone[]="Europe/Monaco";                  //,0x02FF78 },
    $timezone[]="Europe/Moscow";                  //,0x0303B3 },
    $timezone[]="Europe/Nicosia";                 //,0x030705 },
    $timezone[]="Europe/Oslo";                    //,0x0309ED },
    $timezone[]="Europe/Paris";                   //,0x030D1F },
    $timezone[]="Europe/Podgorica";               //,0x031165 },
    $timezone[]="Europe/Prague";                  //,0x03142E },
    $timezone[]="Europe/Riga";                    //,0x031760 },
    $timezone[]="Europe/Rome";                    //,0x031AA5 },
    $timezone[]="Europe/Samara";                  //,0x031E68 },
    $timezone[]="Europe/San_Marino";              //,0x032194 },
    $timezone[]="Europe/Sarajevo";                //,0x032557 },
    $timezone[]="Europe/Simferopol";              //,0x032820 },
    $timezone[]="Europe/Skopje";                  //,0x032B4B },
    $timezone[]="Europe/Sofia";                   //,0x032E14 },
    $timezone[]="Europe/Stockholm";               //,0x03311C },
    $timezone[]="Europe/Tallinn";                 //,0x0333CB },
    $timezone[]="Europe/Tirane";                  //,0x033705 },
    $timezone[]="Europe/Tiraspol";                //,0x033A0B },
    $timezone[]="Europe/Uzhgorod";                //,0x033D99 },
    $timezone[]="Europe/Vaduz";                   //,0x0340B0 },
    $timezone[]="Europe/Vatican";                 //,0x034343 },
    $timezone[]="Europe/Vienna";                  //,0x034706 },
    $timezone[]="Europe/Vilnius";                 //,0x034A33 },
    $timezone[]="Europe/Volgograd";               //,0x034D72 },
    $timezone[]="Europe/Warsaw";                  //,0x03507B },
    $timezone[]="Europe/Zagreb";                  //,0x03545C },
    $timezone[]="Europe/Zaporozhye";              //,0x035725 },
    $timezone[]="Europe/Zurich";                  //,0x035A66 },
    $timezone[]="Factory";                        //,0x035D15 },
    $timezone[]="GB";                             //,0x035D86 },
    $timezone[]="GB-Eire";                        //,0x0362BD },
    $timezone[]="GMT";                            //,0x0367F4 },
    $timezone[]="GMT+0";                          //,0x0368C0 },
    $timezone[]="GMT-0";                          //,0x03687C },
    $timezone[]="GMT0";                           //,0x036838 },
    $timezone[]="Greenwich";                      //,0x036904 },
    $timezone[]="Hongkong";                       //,0x036948 },
    $timezone[]="HST";                            //,0x036B14 },
    $timezone[]="Iceland";                        //,0x036B58 },
    $timezone[]="Indian/Antananarivo";            //,0x036D11 },
    $timezone[]="Indian/Chagos";                  //,0x036D85 },
    $timezone[]="Indian/Christmas";               //,0x036DE7 },
    $timezone[]="Indian/Cocos";                   //,0x036E2B },
    $timezone[]="Indian/Comoro";                  //,0x036E6F },
    $timezone[]="Indian/Kerguelen";               //,0x036EC4 },
    $timezone[]="Indian/Mahe";                    //,0x036F19 },
    $timezone[]="Indian/Maldives";                //,0x036F6E },
    $timezone[]="Indian/Mauritius";               //,0x036FC3 },
    $timezone[]="Indian/Mayotte";                 //,0x037039 },
    $timezone[]="Indian/Reunion";                 //,0x03708E },
    $timezone[]="Iran";                           //,0x0370E3 },
    $timezone[]="Israel";                         //,0x037351 },
    $timezone[]="Jamaica";                        //,0x037680 },
    $timezone[]="Japan";                          //,0x037745 },
    $timezone[]="Kwajalein";                      //,0x0377CE },
    $timezone[]="Libya";                          //,0x037831 },
    $timezone[]="MET";                            //,0x03792B },
    $timezone[]="Mexico/BajaNorte";               //,0x037C34 },
    $timezone[]="Mexico/BajaSur";                 //,0x037F9D },
    $timezone[]="Mexico/General";                 //,0x0381E2 },
    $timezone[]="MST";                            //,0x038440 },
    $timezone[]="MST7MDT";                        //,0x038484 },
    $timezone[]="Navajo";                         //,0x0387D5 },
    $timezone[]="NZ";                             //,0x038B4E },
    $timezone[]="NZ-CHAT";                        //,0x038ECC },
    $timezone[]="Pacific/Apia";                   //,0x0391B4 },
    $timezone[]="Pacific/Auckland";               //,0x039232 },
    $timezone[]="Pacific/Chatham";                //,0x0395BE },
    $timezone[]="Pacific/Easter";                 //,0x0398B5 },
    $timezone[]="Pacific/Efate";                  //,0x039C13 },
    $timezone[]="Pacific/Enderbury";              //,0x039CD9 },
    $timezone[]="Pacific/Fakaofo";                //,0x039D47 },
    $timezone[]="Pacific/Fiji";                   //,0x039D8B },
    $timezone[]="Pacific/Funafuti";               //,0x039E01 },
    $timezone[]="Pacific/Galapagos";              //,0x039E45 },
    $timezone[]="Pacific/Gambier";                //,0x039EBD },
    $timezone[]="Pacific/Guadalcanal";            //,0x039F22 },
    $timezone[]="Pacific/Guam";                   //,0x039F77 },
    $timezone[]="Pacific/Honolulu";               //,0x039FCD },
    $timezone[]="Pacific/Johnston";               //,0x03A061 },
    $timezone[]="Pacific/Kiritimati";             //,0x03A0B3 },
    $timezone[]="Pacific/Kosrae";                 //,0x03A11E },
    $timezone[]="Pacific/Kwajalein";              //,0x03A17B },
    $timezone[]="Pacific/Majuro";                 //,0x03A1E7 },
    $timezone[]="Pacific/Marquesas";              //,0x03A246 },
    $timezone[]="Pacific/Midway";                 //,0x03A2AD },
    $timezone[]="Pacific/Nauru";                  //,0x03A337 },
    $timezone[]="Pacific/Niue";                   //,0x03A3AF },
    $timezone[]="Pacific/Norfolk";                //,0x03A40D },
    $timezone[]="Pacific/Noumea";                 //,0x03A462 },
    $timezone[]="Pacific/Pago_Pago";              //,0x03A4F2 },
    $timezone[]="Pacific/Palau";                  //,0x03A57B },
    $timezone[]="Pacific/Pitcairn";               //,0x03A5BF },
    $timezone[]="Pacific/Ponape";                 //,0x03A614 },
    $timezone[]="Pacific/Port_Moresby";           //,0x03A669 },
    $timezone[]="Pacific/Rarotonga";              //,0x03A6AD },
    $timezone[]="Pacific/Saipan";                 //,0x03A789 },
    $timezone[]="Pacific/Samoa";                  //,0x03A7EC },
    $timezone[]="Pacific/Tahiti";                 //,0x03A875 },
    $timezone[]="Pacific/Tarawa";                 //,0x03A8DA },
    $timezone[]="Pacific/Tongatapu";              //,0x03A92E },
    $timezone[]="Pacific/Truk";                   //,0x03A9BA },
    $timezone[]="Pacific/Wake";                   //,0x03AA13 },
    $timezone[]="Pacific/Wallis";                 //,0x03AA63 },
    $timezone[]="Pacific/Yap";                    //,0x03AAA7 },
    $timezone[]="Poland";                         //,0x03AAEC },
    $timezone[]="Portugal";                       //,0x03AECD },
    $timezone[]="PRC";                            //,0x03B3C9 },
    $timezone[]="PST8PDT";                        //,0x03B47A },
    $timezone[]="ROC";                            //,0x03B7CB },
    $timezone[]="ROK";                            //,0x03B8E3 },
    $timezone[]="Singapore";                      //,0x03B987 },
    $timezone[]="Turkey";                         //,0x03BA3E },
    $timezone[]="UCT";                            //,0x03BE2B },
    $timezone[]="Universal";                      //,0x03BE6F },
    $timezone[]="US/Alaska";                      //,0x03BEB3 },
    $timezone[]="US/Aleutian";                    //,0x03C21C },
    $timezone[]="US/Arizona";                     //,0x03C582 },
    $timezone[]="US/Central";                     //,0x03C610 },
    $timezone[]="US/East-Indiana";                //,0x03D01A },
    $timezone[]="US/Eastern";                     //,0x03CB1B },
    $timezone[]="US/Hawaii";                      //,0x03D284 },
    $timezone[]="US/Indiana-Starke";              //,0x03D312 },
    $timezone[]="US/Michigan";                    //,0x03D683 },
    $timezone[]="US/Mountain";                    //,0x03D9BA },
    $timezone[]="US/Pacific";                     //,0x03DD33 },
    $timezone[]="US/Pacific-New";                 //,0x03E138 },
    $timezone[]="US/Samoa";                       //,0x03E53D },
    $timezone[]="UTC";                            //,0x03E5C6 },
    $timezone[]="W-SU";                           //,0x03E8BD },
    $timezone[]="WET";                            //,0x03E60A },
    $timezone[]="Zulu";                           //,0x03EBF8 },
    return $timezone;}
