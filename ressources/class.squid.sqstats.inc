<?php
define("SQSTAT_SHOWLEN",58);
include_once(dirname(__FILE__)."/class.ip2host.inc");

class squidstat{
    var $fp;
    var $errstr;
    var $errno;
    public $ReturnOnlyTitle=false;

    var $use_sessions=false;
    function __construct(){
            $this->use_sessions=true;

    }



    function showError(){}
    function connect(){
        $this->fp = false;
        $SquidMgrListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
        $this->fp = @fsockopen("127.0.0.1", $SquidMgrListenPort, $this->errno, $this->errstr, 10);
        if (!$this->fp) {
            // failed to connect
            return false;
        }
        return true;
    }

    // based @ (c) moritz at barafranca dot com
    function duration ($seconds) {
        $takes_time = array(604800,86400,3600,60,0);
        $suffixes = array("w","d","h","m","s");
        $output = "";
        foreach ($takes_time as $key=>$val) {
            ${$suffixes[$key]} = ($val == 0) ? $seconds : floor(($seconds/$val));
            $seconds -= ${$suffixes[$key]} * $val;
            if (${$suffixes[$key]} > 0) {
                $output .=  ${$suffixes[$key]};
                $output .= $suffixes[$key]." ";
            }
        }
        return trim($output);
    }
    /**
     * Format a number of bytes into a human readable format.
     * Optionally choose the output format and/or force a particular unit
     *
     * @param   int     $bytes      The number of bytes to format. Must be positive
     * @param   string  $format     Optional. The output format for the string
     * @param   string  $force      Optional. Force a certain unit. B|KB|MB|GB|TB
     * @return  string              The formatted file size
     */
    function filesize_format($bytes, $format = '', $force = '')
    {
        $force = strtoupper($force);
        $defaultFormat = '%01d %s';
        if (strlen($format) == 0)
            $format = $defaultFormat;
        $bytes = max(0, (int) $bytes);
        $units = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');
        $power = array_search($force, $units);
        if ($power === false)
            $power = $bytes > 0 ? floor(log($bytes)/log(1024)) : 0;
        return sprintf($format, $bytes / pow(1024, $power), $units[$power]);
    }
    function makeQuery($pass=""){
        $raw=array();
        // sending request
        if(!$this->fp) die("Please connect to server");
        $out = "GET cache_object://localhost/active_requests HTTP/1.0\r\n";
        if($pass!="") $out.="Authorization: Basic ".base64_encode("cachemgr:$pass")."\r\n";
        $out.="\r\n";
        fwrite($this->fp, $out);

        while (!feof($this->fp)) {
            $raw[]=trim(fgets($this->fp, 2048));
        }
        fclose($this->fp);

        if($raw[0]!="HTTP/1.0 200 OK" && $raw[0]!="HTTP/1.1 200 OK"){
            $this->errno=1;
            $this->errstr="Cannot get data. Server answered: $raw[0]";
            return false;
        }
        // parsing output;
        $header=1;
        $connection=0;
        $parsed["server_version"]="Unknown";
        foreach($raw as $key=>$v){
            // cutoff http header
            if($header==1 && $v=="") $header=0;
            if($header){
                if(substr(strtolower($v),0,7)=="server:"){ // parsing server version
                    $parsed["server_version"]=substr($v,8);
                }
            }
            else {
                if(substr($v,0,11)=="Connection:"){ // parsing connection
                    $connection=substr($v,12);
                }
                if($connection){
                    /* username field is avaible in Squid 2.6 stable */
                    if(substr($v,0,9)=="username ") $parsed["con"][$connection]["username"]=substr($v,9);
                    if(substr($v,0,5)=="peer:") $parsed["con"][$connection]["peer"]=substr($v,6);
                    if(substr($v,0,3)=="me:") $parsed["con"][$connection]["me"]=substr($v,4);
                    if(substr($v,0,4)=="uri ") $parsed["con"][$connection]["uri"]=substr($v,4);
                    if(substr($v,0,10)=="delay_pool") $parsed["con"][$connection]["delay_pool"]=substr($v,11);
                    /* "local:" and "remote:" fields replaced "me:" and "peer:" fields presumably in Squid 3. */
                    if(substr($v,0,7)=="remote:") $parsed["con"][$connection]["peer"]=substr($v,8);
                    if(substr($v,0,6)=="local:") $parsed["con"][$connection]["me"]=substr($v,7);

                    if(preg_match('/out.offset \d+, out.size (\d+)/',$v,$matches)){
                        $parsed["con"][$connection]["bytes"]=$matches[1];
                    }
                    if(preg_match('/start \d+\.\d+ \((\d+).\d+ seconds ago\)/',$v,$matches)){
                        $parsed["con"][$connection]["seconds"]=$matches[1];
                    }
                }
            }
        }
        return $parsed;
    }
    function implode_with_keys($array, $glue) {
        foreach ($array as $key=>$v){
            $ret[]=$key.'='.htmlspecialchars($v);
        }
        return implode($glue, $ret);
    }
    function makeHtmlReport($data,$resolveip=false,$hosts_array=array(),$group_by="username_or_host"){
        ;
        $tpl=new template_admin();

        $total_avg = $total_curr = 0;
        // resort data array
        $users=array();
        switch($group_by){
            case "host":
                $group_by_name="Host";
                $group_by_key='return $ip;';
                break;
            case "username":
                $group_by_name="User";
                $group_by_key='return isset($v["username"]) ? $v["username"] : "N/A";';
                break;
            case "username_or_host":
                $group_by_name="User or Host";
                $group_by_key='return isset($v["username"]) ? $v["username"] : $ip;';
                break;
            case "username_and_host":
                $group_by_name="{member_or_host}";
                $group_by_key='return (isset($v["username"]) ? $v["username"] : "Unknown") . "/" . long2ip($ip);';
                break;
            default:
                die("wrong group_by!");
        }

        foreach($data["con"] as $key => $v){
            if(substr($v["uri"],0,13)=="cache_object:") continue; // skip myself

            $ip=substr($v["peer"],0,strpos($v["peer"],":"));


            if(isset($hosts_array[$ip])){
                $ip=$hosts_array[$ip];
            }
            // i use ip2long() to make ip sorting work correctly
            elseif($resolveip){
                //$hostname=gethostbyaddr($ip);
                //if($hostname==$ip) $ip=ip2long($ip);// resolve failed
                //else $ip=$hostname;
                $host= new ip2host($ip);
                $ip= $host->output;
            }
            else{
                $ip=ip2long(substr($v["peer"],0,strpos($v["peer"],":")));
            }
            $v['connection'] = $key;
            $group=eval($group_by_key);
            if($group=="-"){$group=$ip;}
            $users[$group][]=$v;
        }
        ksort($users);
        $refresh=0;
        if(isset($_GET["refresh"]) && !isset($_GET["stop"])) $refresh=(int)$_GET["refresh"];
        $text=array();



        $text[]='<table class="table table-striped">';
        $text[]='<thead>';
        $text[]='<tr>';
        $text[]='<th nowrap>'.$group_by_name.'</th>';
        $text[]='<th>URI</th>';
        if($this->use_sessions){
            $text[]='<th nowrap>Curr. Speed</th>';
            $text[]='<th nowrap>Avg. Speed</th>';
        }
        $text[]='<th>{size}</th>';
        $text[]='<th>{time}</th>';
        $text[]='</tr>';
        $text[]='</thead>';
        $text[]='<tbody>';
        $ausers=$acon=0;
        unset($session_data);
        if (isset($_SESSION['time']) && ((time() - $_SESSION['time']) < 3*60) && isset($_SESSION['sqdata']) && is_array($_SESSION['sqdata'])) {
            //only if the latest data was less than 3 minutes ago
            $session_data = $_SESSION['sqdata'];
        }
        $table=array();
        foreach($users as $key=>$v){
            $ausers++;
            $table[]='<tr>';
            $table[]='<td colspan="2" style=\'border-top:2px solid #CCCCCC\'><i class="fas fa-users"></i>&nbsp;<b>'.(is_int($key)?long2ip($key):$key).'</b></td>';
            $table[]='<td colspan="5" style=\'border-top:2px solid #CCCCCC\'>&nbsp;</td>';
            $table[]='</tr>';

            $user_avg = $user_curr = $con_color =  0;
            foreach ($v as $con){
                if(strlen($con["uri"])>70){$con["uri"]=substr($con["uri"],0,70)."...";}
                $stlen=strlen($con["uri"]);
                if(substr($con["uri"],0,7)=="http://" || substr($con["uri"],0,6)=="ftp://"){
                    if($stlen>SQSTAT_SHOWLEN){
                        $uritext=htmlspecialchars(substr($con["uri"],0,SQSTAT_SHOWLEN))."...";
                    }
                    else {
                        $uritext=htmlspecialchars($con["uri"]);

                    }
                    $uri=$tpl->td_href("$uritext",htmlspecialchars($con["uri"]));
                }
                else {
                    $uri = htmlspecialchars($con["uri"]);
                }
                $acon++;
                //speed stuff
                $con_id = $con['connection'];
                $is_time = time();
                $curr_speed=0;
                $avg_speed=0;
                if (isset($session_data[$con_id]) && $con_data = $session_data[$con_id] ) {
                    // if we have info about current connection, we do analyze its data
                    // current speed
                    $was_time = $con_data['time'];
                    $was_size = $con_data['size'];
                    if ($was_time && $was_size) {
                        $delta = $is_time - $was_time;
                        if ($delta == 0) {
                            $delta = 1;
                        }
                        if ($con['bytes'] >= $was_size) {
                            $curr_speed = ($con['bytes'] - $was_size) / 1024 / $delta;
                        }
                    } else {
                        $curr_speed = $con['bytes'] / 1024;
                    }

                    //avg speed
                    $avg_speed = $con['bytes'] / 1024;
                    if ($con['seconds'] > 0) {
                        $avg_speed /= $con['seconds'];
                    }
                }

                $new_data[$con_id]['time'] = $is_time;
                $new_data[$con_id]['size'] = $con['bytes'];

                //sum speeds
                $total_avg += $avg_speed;
                $user_avg += $avg_speed;
                $total_curr += $curr_speed;
                $user_curr += $curr_speed;
                $infos=$this->implode_with_keys($con,'<br/>');


                $table[]='<tr>';
                $table[]='<td>&nbsp;</td>';
                $table[]='<td nowrap width="80%" ><i class="fas fa-globe"></i>&nbsp;'.$uri.'</td>';
                if($this->use_sessions){
                    $table[]='<td nowrap align="right">'.( (round($curr_speed, 2) > 0) ? sprintf("%01.2f KB/s", $curr_speed) : '' ).'</td>';
                        $table[]='<td nowrap align="right">'.( (round($avg_speed, 2) > 0) ? sprintf("%01.2f KB/s", $avg_speed) : '' ). '</td>';
                }
                $table[]='<td nowrap align="right">'.$this->filesize_format($con["bytes"]).'</td>';
                $table[]='<td nowrap align="right">'.$this->duration($con["seconds"],"short").'</td>';
                $table[]='</tr>';
            }
            if($this->use_sessions){
                $table[]=sprintf("<tr>
                    <td colspan=\"2\" style='border-top:2px solid #CCCCCC'></td>
                    <td align=\"right\" style='border-top:2px solid #CCCCCC'>%01.2f KB/s</td>
                    <td align=\"right\" style='border-top:2px solid #CCCCCC'>%01.2f KB/s</td>
                    <td align=\"right\" style='border-top:2px solid #CCCCCC'>".$tpl->icon_nothing()."</td>
                    <td align=\"right\" style='border-top:2px solid #CCCCCC'>".$tpl->icon_nothing()."</td>",
                    $user_curr, $user_avg);
            }

            $table[]="<tr><td colspan='7' style='background-color:white;border-top:0px solid white'>&nbsp;</td></tr>";

        }
        $_SESSION['time'] = time();
        if(isset($new_data)) $_SESSION['sqdata'] = $new_data;
        $stat_row='';
        if($this->use_sessions){
            if($this->ReturnOnlyTitle){return "$ausers {members} <small style='color:white'>($acon {connections})</small>";}
            $stat_row.=sprintf("<H2>Total:<b>%d</b> {members} and <b>%d</b> {connections} @ <b>%01.2f/%01.2f</b> KB/s (CURR/AVG)</H2>", $ausers, $acon, $total_curr, $total_avg);
        }
        else {
            if($this->ReturnOnlyTitle){return "$ausers {members} <small style='color:white'>($acon {connections})</small>";}
            $stat_row.=sprintf("<H2>Total:</b>%d</b> users and <b>%d</b> connections",$ausers, $acon);
        }
        if($ausers==0){
            $text[]='<tr><td colspan=6><b>No active connections</b></td></tr>';
        }
        else {
            $text[]=$stat_row.@implode("\n",$table);
        }
        $text[]= '</table>';

        return @implode("\n",$text);
    }
}
?>