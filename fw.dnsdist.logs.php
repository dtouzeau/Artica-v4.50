<?php
include_once(dirname(__FILE__)."/ressources/externals/GeoIP2/vendor/autoload.php");
use GeoIp2\Database\Reader;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
$EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["main-page"])){page();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["opts"])){search_opts_js();exit;}
if(isset($_GET["search-opts-popup"])){search_opts_popup();exit;}
if(isset($_GET["search-opts-reset"])){search_opts_reset();exit;}
if(isset($_POST["remote_addr"])){search_opts_save();exit;}

page();


function rule_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $rulename="{default}";
    $ID=intval($_GET["rule-js"]);
    if($ID>0) {
        $sql = "SELECT rulename FROM dnsdist_rules WHERE ID=$ID";
        $ligne = $q->mysqli_fetch_array($sql);
        $rulename = $ligne["rulename"];
    }
    $tpl->js_dialog6("$rulename: {events}","$page?filter-by-rule=$ID",1200);
    return true;
}

function page(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t          = time();
    $addPLUS    = null;
    if(isset($_GET["filter-by-rule"])){
        $addPLUS="&filter-by-rule=".intval($_GET["filter-by-rule"]);
    }
    $options["WRENCH"]="Loadjs('$page?opts=yes&function=%s')";
    $html[]="<div style='margin-top:15px'>";
    $html[]=$tpl->search_block($page,null,null,null,$addPLUS,$options);
    $html[]="</div>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
       echo $tpl->_ENGINE_parse_body($html);

}
function search_opts_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog4("{options}","$page?search-opts-popup=yes&function=$function");
}
function search_opts_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID,rulename FROM dnsdist_rules ORDER BY rulename");

    foreach ($results as $index=>$ligne){
        $rrNames[$ligne["ID"]]=$ligne["rulename"];
    }

    $categories=categories_list();

    $form[]=$tpl->field_array_hash($rrNames,"ruleid","{rulename}",$_SESSION["DNSFWSEARCH"]["ruleid"]);

    $form[]=$tpl->field_array_hash($categories,"category","{category}",$_SESSION["DNSFWSEARCH"]["category"]);

    if(!isset($_SESSION["DNSFWSEARCH"]["DateFrom"])){
        $_SESSION["DNSFWSEARCH"]["DateFrom"]="0000-00-00";
    }

    $form[]=$tpl->field_ipaddr("remote_addr","{src}",$_SESSION["DNSFWSEARCH"]["remote_addr"]);
    $form[]=$tpl->field_date("DateFrom","{from_date}",$_SESSION["DNSFWSEARCH"]["DateFrom"]);
    $form[]=$tpl->field_clock("TimeFrom","{from_time}",$_SESSION["DNSFWSEARCH"]["TimeFrom"]);
    $js="dialogInstance4.close();$function()";
    $tpl->form_add_button("{reset}","Loadjs('$page?search-opts-reset=yes&function=$function')");
    echo $tpl->form_outside("{search}",$form,null,"{save}",$js);
    return true;
}
function search_opts_reset():bool{
    $function=$_GET["function"];
    unset($_SESSION["DNSFWSEARCH"]);
    header("content-type: application/x-javascript");
    echo "dialogInstance4.close();$function()";
    return true;
}
function search_opts_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["DNSFWSEARCH"]=$_POST;
    return true;
}
function categories_list(){
    $qPos=new postgres_sql();
    $tpl=new template_admin();
    $Ccategories=new categories();
    $Ccategories->patches_categories();
    $dans=new dansguardian_rules();
    $HASH[0]="{not_categorized}";
    if($qPos->COUNT_ROWS("personal_categories")==0){$dans->CategoriesTableCache();}

    if(!$qPos->TABLE_EXISTS("personal_categories")){
        $Ccategories->initialize();
    }

    $sql="SELECT *  FROM personal_categories ORDER BY categoryname ASC";
    $results = $qPos->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        if (preg_match("#^reserved#", $ligne['categoryname'])) {
            continue;
        }
        $ligne['category_description'] = $tpl->utf8_encode($ligne['category_description']);
        $category_id = $ligne["category_id"];
        $HASH[$category_id]=$ligne['categoryname'];
    }

    return $HASH;
}

function GetRules():array{
    $MAINB[0]="<span class='label label-primary'>{RESOLVED}</span>";
    $MAINB[1]="<span class='label label-primary'>{RESOLVED}</span>";
    $MAINB[2]="<span class='label label-danger'>{deny}</span>";
    $MAINB[3]="<span class='label label-warning'>Spoofing</span>";
    $MAINB[4]="<span class='label label-warning'>Spoofing</span>";
    $MAINB[6]="<span class='label label-primary'>{RESOLVED}</span>";
    $MAINB[9]="<span class='label label-warning'>SafeSearch</span>";
    $MAINB[10]="<span class='label label-primary'>Active Directory</span>";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT * FROM dnsdist_rules");
    $MAIN[0]=$MAINB[0];
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $ruletype=$ligne["ruletype"];
        $MAIN[$ID]=$MAINB[$ruletype];
        $RULES[$ID]=$ligne["rulename"];
    }
    return array($MAIN,$RULES);

}


function search(){
    $tpl                        = new template_admin();
    $GLOBALS["TPLZ"]            = $tpl;
    $filename                   = "/usr/share/artica-postfix/ressources/logs/dnsdist.log.tmp";
      $qr                         = new mysql_catz();
      if(!isset($_SESSION["DNSFWSEARCH"])){$_SESSION["DNSFWSEARCH"]=array();}
    $OPTS=$_SESSION["DNSFWSEARCH"];
    if(isset($_GET["filter-by-rule"])){
        $OPTS["ruleid"]=intval($_GET["filter-by-rule"]);
    }

    if($OPTS["DateFrom"]="0000-00-00"){
        unset($OPTS["DateFrom"]);
        unset($OPTS["TimeFrom"]);
    }
    if($OPTS["TimeFrom"]="00:00"){
        unset($OPTS["TimeFrom"]);
    }

    if($_GET["search"]==null){$_GET["search"]="50 events";}
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    //print_r($MAIN);
    $zopts=base64_encode(serialize($OPTS));
    if(strlen($MAIN["TERM"])==0){
        $MAIN["TERM"]="NONE";
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/accesses/{$MAIN["MAX"]}/{$MAIN["TERM"]}/$zopts");

    $badcats["46"]=true;
    $badcats["135"]=true;
    $badcats["92"]=true;
    $badcats["105"]=true;
    $badcats["111"]=true;
    $badcats["5027"]=true;
    $badcats["5010"]=true;
    $badcats["5024"]=true;
    $badcats["5094"]=true;
    list($icons,$zrulesNames)=GetRules();
    $zrulesNames[9999999]="{global}";
    $icons[9999999]="<span class='label label-default'>Default</span>";

    $search_text=array();
    if(isset($OPTS["ruleid"])){
        if(intval($OPTS["ruleid"])>0) {
            $search_text[] = "{rulename} {$zrulesNames[$OPTS["ruleid"]]}";
        }
    }
    if(isset($OPTS["category"])){
        if(intval($OPTS["category"])>0) {
            $search_text[] = "{category} ".$qr->CategoryIntToStr($OPTS["category"]);
        }
    }
    if(isset($OPTS["DateFrom"])){
        if(strlen($OPTS["DateFrom"])>0) {
            $search_text[] = "{from_date} {$OPTS["DateFrom"]} {$OPTS["TimeFrom"]}";
        }
    }
    if(isset($OPTS["remote_addr"])){
        if(strlen($OPTS["remote_addr"])>0) {
            $search_text[] = "{src} {$OPTS["remote_addr"]}";
        }
    }

    $filter=" <i style='font-weight: normal'>({filter}:{all_events})</i>";
    if(count($search_text)>0){
        $filter=" <i style='font-weight: normal'>({filter}: ".@implode(", ",$search_text).")</i>";
    }

    $html[]="<table class=\"table table-hover\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{time}</th>";
    $html[]="<th></th>";
    $html[]="<th nowrap>{src}</th>";
    $html[]="<th nowrap>{country}</th>";
    $html[]="<th>{DNS_QUERIES}$filter</th>";
    $html[]="<th>{type}</th>";
    $html[]="<th>{duration}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $MONTHS=array("Jan"=>1,"Feb"=>2,"Mar"=>3,"Apr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Aug"=>8,"Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);
    $data=explode("\n",@file_get_contents($filename));


    $ip2host=new ip2host();
    foreach ($data as $line){
        $color=null;$type=null;
        VERBOSE("---- [$line] ----",__LINE__);
        $duration="&nbsp;";
        $edns="";
        if(strpos($line,"edns=none")>0){
            $line=str_replace(" edns=none", "", $line);
        }else {
            if (preg_match("# edns=([0-9\.]+)#", $line, $ri)) {
                $line = str_replace("edns=$ri[1]", "", $line);
                $edns = $ri[1];
                if ($edns == "none") {
                    $edns = "";
                }
                VERBOSE("---- [$line] ----[edns=$ri[1]]", __LINE__);
            }
        }

        if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?:\s+rule=([0-9]+)\s+src=(.+?)\s+domain=(.+?)\s+q=(.*?)\s+category=([0-9a-z]+)#", $line,$re)){

            $Month = $re[1];
            $Day = $re[2];
            $time = $re[3];
            $type=$re[6];
            $sday = date("Y") . "-" . $MONTHS[$Month] . "-$Day $time";
            $sdate = $tpl->time_to_date(strtotime($sday), true);
            $ruleid=intval($re[4]);
            $ipsrc=$re[5];
            $domain=$re[6];
            $QTYPE=$re[7];
            if(!is_numeric($re[8])){$re[8]=0;}
            $Category=intval($re[8]);
            if($Category_name="None");
            $rulename=null;
            $style_category=null;

            if($Category>0){
                $Category_name=$qr->CategoryIntToStr($Category);
            }

            $HrefRule=" ({default})";
            if(preg_match("#rulename=(.+)#",$line,$ri)){$rulename=$ri[1];}




            if($ruleid>0){
                if(is_null($rulename)){$rulename="";}
                if(strlen($rulename)<3){
                    if(!isset($zrulesNames[$ruleid])){
                        $zrulesNames[$ruleid]="";
                    }
                    $rulename=$zrulesNames[$ruleid];
                }
                $HrefRule="(".$tpl->td_href($rulename,null,
                        "Loadjs('fw.dns.dnsdist.rules.php?rule-id-js=$ruleid')").")";
            }

            $relay=$domain.$HrefRule;

        }

        if($type==null){continue;}

        if($Category>0){
            if(isset($badcats[$Category])){
                $style_category=" style='color:#ed5565;font-weight:bold'";
            }
        }


        $ipinfoApi=$ip2host->ipinfoApi($ipsrc);
        $countryName="";
        if($ipinfoApi["flag"]==null){$ipinfoApi["flag"]="flags/info.png";}
        $flag="<img src='/img/{$ipinfoApi["flag"]}'>";

        if(isset($ipinfoApi["country"])) {
            $ipinfos[] = "<strong>{country}</strong>:&nbsp;{$ipinfoApi["country"]}/{$ipinfoApi["countryName"]}";
        }
        if(isset($ipinfoApi["city"])) {
            $ipinfos[] = "<strong>{city}</strong>:&nbsp;{$ipinfoApi["city"]}";
        }
        if(isset($ipinfoApi["isp"])) {
            $ipinfos[] = "<strong>ISP</strong>:&nbsp;{$ipinfoApi["isp"]}";
        }
        if(strlen($edns)>1){
            $edns="<br><small><i>$edns</i></small>";
        }
        if(isset($ipinfoApi["countryName"])){
            $countryName=$ipinfoApi["countryName"];
        }

        $TD1="style='width:1%' nowrap";
        $ico=$icons[$ruleid];
        $html[]="<td $TD1><span style='color:$color' >$sdate</span></td>
                <td $TD1><span style='color:$color'>$ico</td>
                <td $TD1><span style='color:$color'>$ipsrc$edns</td>
                 <td $TD1><span style='color:$color'>$flag $countryName</td>
                <td><span style='color:$color'>$relay - <span $style_category>$Category_name</span></td>
                <td $TD1><span style='color:$color'>$QTYPE</span></td>  
                <td $TD1><span style='color:$color'>$duration</span></td>    
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
    $html[]="<div style='font-size:10px'>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/dnsdist.log.cmd")."</div>";
    $html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);



}


function parserules($gpid,$ruleid){
    if(isset($GLOBALS["$gpid-$ruleid"])){return $GLOBALS["$gpid-$ruleid"];}
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM dnsdist_rules WHERE ID='$ruleid'");
    $f[]=$tpl->td_href($ligne["rulename"],null,"Loadjs('fw.dns.dnsdist.rules.php?rule-id-js=$ruleid')");

    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups  WHERE ID='$gpid'");


    $edit_js="Loadjs('fw.rules.items.php?groupid=$gpid&js-after=&TableLink=&RefreshTable=&ProxyPac=0&firewall=0&RefreshFunction=&fastacls=')";
    $f[]=$tpl->td_href($ligne["GroupName"],null,$edit_js);
    $results="(".@implode(" - ",$f).")";
    $GLOBALS["$gpid-$ruleid"]=$results;
    return $results;

}


function GeoIPCountry($ipadddr){

    if(preg_match("#^(.+?):[0-9]+#",$ipadddr,$re)){$ipadddr=$re[1];}

    $mem=new lib_memcached();
    if(!isset($GLOBALS["PHP_GEOIP_INSTALLED"])){
        $GLOBALS["PHP_GEOIP_INSTALLED"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"));
    }
    if(!isset($GLOBALS["EnableGeoipUpdate"])){
        $GLOBALS["EnableGeoipUpdate"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    }

    if($GLOBALS["PHP_GEOIP_INSTALLED"]==0){return null;}
    if($GLOBALS["EnableGeoipUpdate"]==0){return null;}
    if (!extension_loaded("maxminddb")) {return null;}



    $value=unserialize($mem->getKey("GEOIP:$ipadddr"));
    if(!is_array($value)){$value=array();}

    if(!isset($value["countryCode"])) {
        try {
            $reader = new Reader('/usr/local/share/GeoIP/GeoLite2-Country.mmdb');
            $record = $reader->country($ipadddr);
            $value["countryCode"] = $record->country->isoCode;
            $value["countryName"] = $record->country->name;
            $mem->saveKey("GEOIP:$ipadddr",serialize($value),16000);
        } catch (Exception $e) {

            return null;
        }
    }


    if ($value["countryCode"] == null) {return null;}
    $countryCode=$value["countryCode"];


    return $countryCode;


}