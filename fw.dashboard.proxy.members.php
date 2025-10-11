<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}

$t=md5(time());
$tpl=new template_admin();
$page=CurrentPageName();
echo "<div id='$t'></div>
<script>LoadAjaxSilent('$t','$page?table=yes&t=$t');</script>
";


function table()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $t = $_GET["t"];
    $html[] = "<table style='width:100%;margin-top:10px' class='table table-striped'>";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th>{members} <small>{period_10mn}</small></th>";
    $html[] = "<th>{ipaddr}</th>";
    $html[] = "<th>{hits}</th>";
    $html[] = "<th>{size}</th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";
    $xkey = $tpl->time_key_10mn();
    $KeyUsersList = "WebStats:$xkey:CurrentUsers";
    $ipClass=new IP();

    $redis = new Redis();
    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        echo "<div class='alert alert-danger' style='margin-top:30px'>" . $e->getMessage() . "</div>";
        exit;
    }

    $page = CurrentPageName();
    VERBOSE("sMembers($KeyUsersList)", __LINE__);
    try {
        $Members = $redis->sMembers($KeyUsersList);
    } catch (Exception $e) {
        echo "<div class='alert alert-danger' style='margin-top:30px'>" . $e->getMessage() . "</div>";
        exit;
    }
    $jsfunc = array();
    $c = 0;
    foreach ($Members as $UserName) {
        $c++;
        if ($c > 500) {
            break;
        }


        $UserNameencode=urlencode($UserName);



        $html[] = "<tr>";

        $RQSKey = "WebStats:$xkey:CurrentUser:RQS:$UserName";
        $SizeKey = "WebStats:$xkey:CurrentUser:Size:$UserName";
        $KeyUserIP="WebStats:$xkey:CurrentUserIP:$UserName";
        $rqs = FormatNumber($redis->get($RQSKey));
        $size = $redis->get($SizeKey);
        $KeyUserIPVal=$redis->get($KeyUserIP);
        $size = FormatBytes($size / 1024);
        $KeyUserIPEnc=urlencode($KeyUserIPVal);

        if($ipClass->IsvalidMAC($UserName)){
           $usrmac=$redis->get("usrmac:$UserName");
            if($usrmac<>null){
                if($usrmac<>$UserName){
                    $UserName="$UserName ($usrmac)";
                }
            }
        }



        $UserName=$tpl->td_href($UserName,"{view} $UserName","Loadjs('fw.dashboard.proxy.members.zoom.php?uid=$UserNameencode&ipaddr=$KeyUserIPEnc')");
        $html[] = "<td><strong>$UserName</strong></td>";
        $html[] = "<td style='width:1%' nowrap><strong>$KeyUserIPVal</strong></td>";
        $html[] = "<td style='width:1%' nowrap><strong>$rqs</strong></td>";
        $html[] = "<td style='width:1%' nowrap><strong>$size</strong></td>";
        $html[] = "</tr>";


    }
    $html[] = "</tbody>";
    $html[] = "</table>";
    $html[] = "<script>";
    $html[] = "
function ParseSquidMembersStats(){
    if(!document.getElementById('$t')){return False;}
    LoadAjaxSilent('$t','$page?table=yes&t=$t');
}


</script>";
    $redis->close();
    echo $tpl->_ENGINE_parse_body($html);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}