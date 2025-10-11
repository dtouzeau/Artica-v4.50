<?php
include_once(dirname(__FILE__)."/class.tcpip.inc");
class firehol_rules{
	private $eth;
	private $route=0;
	public $logs;
	private $iptablesNDPI=0;
	private $FireHoleLogAllEvents=0;
	private $POSTFIX=false;
	private $DEBUG_PORTS=array();
	
	
	public function __construct($eth=null,$route=0){
		$this->eth=$eth;
		$this->route=$route;
		$this->iptablesNDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));
		$this->FireHoleLogAllEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleLogAllEvents"));
		
		
	}



    public function link_balancer(){

        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $sql = "SELECT ID,mark FROM link_balance WHERE enabled=1";
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne){
            $CNMARK[$ligne["ID"]]=$ligne["mark"];
            $rules[]="# Balance rule id{$ligne["ID"]} mark:{$ligne["mark"]}";
        }


        $sql = "SELECT * FROM iptables_main WHERE accepttype='lINK' AND enabled=1 ORDER BY zOrder";
        $results = $q->QUERY_SQL($sql);

        if(!$q->ok){return "#$q->mysql_error\n";}
        if (count($results) == 0) {
            return "# No personal rules...\n";
        }

        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $TimeEnabled=$ligne["enablet"];
            $restriction=null;
            $isClient=intval($ligne["isClient"]);
            $interface=null;
            $jlog=$ligne["jlog"];
            $eth=$ligne["eth"];
            $inface="interface";
            $ACTION=null;
            $FORWARD="";
            $MARK_BALANCE=intval($ligne["MARK_BALANCE"]);
            $mark=intval($CNMARK[$MARK_BALANCE]);
            if($isClient==1){$FORWARD=" OUTPUT";$inface="outface";}
            if($mark==0){
                $rules[]="# Rule $ID ID balance $MARK_BALANCE mark=0!";
                continue;
            }
            if($this->FireHoleLogAllEvents==1){$jlog=1;}
            if($this->iptablesNDPI==1){$restriction=$this->mDPI($ID); }
            if($jlog==1){$ACTION=" loglimit \"RULE-$ID\"";}
            if($TimeEnabled==1){$restriction=$this->parse_time($ligne["time_restriction"],$restriction);}
            if($eth<>null){$interface=" $inface $eth";}
            $rule_prefix="connmark4 0x{$mark}{$FORWARD}{$interface}";

            $services_container=unserialize(base64_decode($ligne["services_container"]));
            if(!is_array($services_container)){$services_container=array();}
            if(trim($ligne["service"])<>null){$services_container[$ligne["service"]]=true;}
            if(count($services_container)>0){
                foreach ($services_container as $serviceName=>$none){
                    $zports=$this->parse_service($serviceName);
                    $rules[]="# Rule $ID / $rule_prefix service $serviceName logging=$jlog";
                    $rules[]=@implode("\n",$this->DEBUG_PORTS);
                    if(is_array($zports)){
                        $rule_prefix_port=$rule_prefix .$zports[0];
                        $rules[] = $this->build_objects($ID, $rule_prefix_port,
                            $ACTION, $restriction, array());

                        $rule_prefix_port=$rule_prefix .$zports[1];
                        $rules[] = $this->build_objects($ID, $rule_prefix_port,
                            $ACTION, $restriction, array());

                    }else{
                        $rule_prefix_port=$rule_prefix .$zports;
                        $rules[] = $this->build_objects($ID, $rule_prefix_port,
                            $ACTION, $restriction, array());

                    }


                }
            }else {
                $rules[] = "# Rule $ID / $rule_prefix logging=$jlog";
                $rules[] = $this->build_objects($ID, $rule_prefix,
                    $ACTION, $restriction, array());
            }
        }

        return @implode("\n",$rules);
    }


    private function parse_service($servicename){
        $this->DEBUG_PORTS=array();
	    if(!is_file("/etc/firehol/services/$servicename.conf")){
	        $this->DEBUG_PORTS[]="# parse_service: $servicename.conf no such file";
	        return null;}
	    $f=explode("\n",@file_get_contents("/etc/firehol/services/$servicename.conf"));
	    foreach ($f as $index=>$line){
            if(preg_match("#^server_{$servicename}_ports=(.+)#",$line,$re)){
                $services=$re[1];
                $services=str_replace("'","",$services);
                $services=str_replace('"',"",$services);
                break;
            }

        }

	    if($services==null){
            $this->DEBUG_PORTS[]="# server_{$servicename}_ports= Not found";
            return null;
        }

        $servicesR=explode(" ",$services);
	    foreach ($servicesR as $line){
	        if(preg_match("#(.+?)\/([0-9]+)#",$line,$re)){
	            $ARRAY[$re[1]][]=$re[2];
            }
        }

        $line_tcp=null;
	    $line_udp=null;
	    if(isset($ARRAY["tcp"])){
	        if(count($ARRAY["tcp"])>0){
	            $line_tcp=" proto tcp dport ".@implode(",",$ARRAY["tcp"]);
            }
        }
        if(isset($ARRAY["udp"])){
            if(count($ARRAY["udp"])>0){
                $line_udp=" proto udp dport ".@implode(",",$ARRAY["udp"]);
            }
        }

        if($line_udp<>null AND $line_tcp<>null){
            return array($line_tcp,$line_udp);
        }

        if($line_tcp<>null){return $line_tcp;}
        if($line_udp<>null){return $line_udp;}

    }




	
	public function buildrules(){
		$sql="SELECT * FROM iptables_main WHERE eth='$this->eth' AND enabled=1 ORDER BY zOrder";

		$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){$this->logs[]="# $this->eth $q->mysql_error";}
		$rules=array();
		foreach ($results as $index=>$ligne){
			$prefix="server";
			if($this->route==1){$prefix="route";}
			if($ligne["isClient"]==1){$prefix="client";}
			if($ligne["accepttype"]=="MARK"){continue;}
            if($ligne["accepttype"]=="lINK"){continue;}
			
			$ID=$ligne["ID"];
			$TimeEnabled=$ligne["enablet"];
			$restriction=null;
			$jlog=$ligne["jlog"];
			if($this->FireHoleLogAllEvents==1){$jlog=1;}
			
			$PREFIXES[]="#\tRule $ID ($prefix) TimeEnabled:$TimeEnabled";
			if($this->iptablesNDPI==1){$restriction=$this->mDPI($ID); }
			
			if($ligne["accepttype"]=="ACCEPT"){$ACTION="accept";}
			if($ligne["accepttype"]=="DROP"){$ACTION="deny";}
			
			if($jlog==1){$ACTION=$ACTION." loglimit \"RULE-$ID\"";}
			
			
			if($TimeEnabled==1){$restriction=$this->parse_time($ligne["time_restriction"],$restriction);}
			
			$services_container=unserialize(base64_decode($ligne["services_container"]));
			if(!is_array($services_container)){$services_container=array();}
			if(trim($ligne["service"])<>null){$services_container[$ligne["service"]]=true;}
			if(count($services_container)==0){$services_container["any"]=true;}
			
			$this->logs[]="# $this->eth rule ID: #$ID";
			$rule_prefix="\t$prefix %SERVICE% $ACTION";
            $ACTION=null;
			if($GLOBALS["VERBOSE"]){$rules[]="#Rule id $ID application:{$ligne["application"]} ($this->iptablesNDPI); prefix=$rule_prefix ACTION=$ACTION, restriction=$restriction, LOG=$jlog";}
			$rules[]=$this->build_objects($ID,$rule_prefix,$ACTION,$restriction,$services_container);
		}
		
		if(count($rules)>0){
			return @implode("\n", $PREFIXES)."\n".@implode("\n",$rules);
		}
		
	}
	
	public function parse_time_iptables($content=null){
		if($content==null){return null;}
		$TTIME=unserialize($content);
		if(!is_array($TTIME)){return null;}
		$array_days=array(1=>"monday",2=>"tuesday",3=>"wednesday",4=>"thursday",5=>"friday",6=>"saturday",7=>"sunday");
		$DDS=array();
		
		foreach ($array_days as $num=>$maks){
			if($TTIME["D{$num}"]==1){$DDS[]=$num;}
		}
		
		if(count($DDS)>0){
			$f[]="--weekdays ".@implode(",", $DDS);
		}
		
		if( (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ftime"])) AND  (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ttime"]))  ){
			$f[]="--timestart {$TTIME["ftime"]} --timestop {$TTIME["ttime"]}";
		}
		
		if(count($f)==0){return null;}
		return " -m time --kerneltz ".@implode(" ", $f)." ";
	}
	
	

	
	
	private function build_objects($ID,$rule_prefix,$ACTION=null,$time_restriction="",$services_container=array()){
		$ObjectsAll=0;
		$rules=array();
		if(count($services_container)==0){$services_container["any"]=true;}
		$inbound_objects=$this->objects($ID,0);
		$outbound_objects=$this->objects($ID,1);
		$rules=array();
		$this->logs[]="# $this->eth Inbound Objects: ".count($inbound_objects);
		$this->logs[]="# $this->eth Outbound Objects: ".count($outbound_objects);
		
		$ObjectsAll=count($inbound_objects)+count($outbound_objects);
		
		
		if($ObjectsAll==0){
            foreach ($services_container as $service=>$none) {
				if(trim($service)==null){continue;}
				$rule_prefix2=str_replace("%SERVICE%", $service, $rule_prefix);
				$rules[]="$rule_prefix2 $time_restriction {$ACTION}";
			}
			return @implode("\n", $rules);
		}
		
		if(count($inbound_objects)>0){
            foreach ($inbound_objects as $objects_inbound) {
				if(count($outbound_objects)>0){
                    foreach ($outbound_objects as $objects_outbound) {
						reset($services_container);
                        foreach ($services_container as $service=>$none) {
							if(trim($service)==null){continue;}
							$rule_prefix2=str_replace("%SERVICE%", $service, $rule_prefix);
							$rules[]="$rule_prefix2 $objects_inbound $objects_outbound $time_restriction {$ACTION}";
						}
					}
					return @implode("\n", $rules);
				}
				
				
				reset($services_container);
                foreach ($services_container as $service=>$none) {
					if(trim($service)==null){continue;}
					$rule_prefix2=str_replace("%SERVICE%", $service, $rule_prefix);
					$rules[]="$rule_prefix2 $objects_inbound $time_restriction {$ACTION}";
				}
				
			}
			
			return @implode("\n", $rules);
		}
		
		if(count($outbound_objects)>0){
            foreach ($outbound_objects as $objects_outbound) {
				reset($services_container);
                foreach ($services_container as $service=>$none) {
					if(trim($service)==null){continue;}
					$rule_prefix2=str_replace("%SERVICE%", $service, $rule_prefix);
					$rules[]="$rule_prefix2 $objects_outbound $time_restriction {$ACTION}";
				}
			}
			
			return @implode("\n", $rules);
		}
		
		
		
		reset($services_container);
        foreach ($services_container as $service=>$none) {
			if(trim($service)==null){continue;}
			$rule_prefix2=str_replace("%SERVICE%", $service, $rule_prefix);
			$rules[]="$rule_prefix2 $time_restriction {$ACTION}";
		}
		return @implode("\n", $rules);
		
		
	}
	
	public function iptables_outbound_objects($ID,$MASTER_RULE,$CONF=array()){
		if($GLOBALS["VERBOSE"]){ echo "iptables_outbound_objects receive for $ID in line ".__LINE__."\n";}
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$sql="SELECT firewallfilter_sqacllinks.gpid,firewallfilter_sqacllinks.negation,
		firewallfilter_sqacllinks.zOrder,firewallfilter_sqacllinks.zmd5 as mkey,
		webfilters_sqgroups.ID as gpid,
		webfilters_sqgroups.GroupName as GroupName,
		webfilters_sqgroups.GroupType FROM firewallfilter_sqacllinks,webfilters_sqgroups
		WHERE firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID
		AND firewallfilter_sqacllinks.aclid=$ID
		AND firewallfilter_sqacllinks.direction='1'
		AND webfilters_sqgroups.enabled='1'
		ORDER BY firewallfilter_sqacllinks.zOrder";
		$array=array();
		$results = $q->QUERY_SQL($sql);
		$CountOfresults=count($results);
		if($GLOBALS["VERBOSE"]){ echo "Objects of ID $ID == $CountOfresults in line ".__LINE__."\n";}
	
		if(count($results)==0){
			$NEWRULE=str_replace("%%d", "", $MASTER_RULE);
			
			$CONF[]=$NEWRULE;
			if($GLOBALS["VERBOSE"]){ echo "Return Arrayof(".count($CONF).") in line ".__LINE__."\n";}
			return $CONF;
		}
		
		$IPClass=new IP();
		
		foreach ($results as $index=>$ligne){
			$negation=intval($ligne["negation"]);$not="";if($negation==1){$not="!";}
			$GroupName=$ligne["GroupName"];
			if($GLOBALS["VERBOSE"]){ echo "GroupName = '$GroupName' in line ".__LINE__."\n";}
			$gpid=$ligne["gpid"];
			$negation=$ligne["negation"];
			$GroupType=$ligne["GroupType"];
			$GroupAlias[]="{$not}ipset:Group{$gpid}";
		}
		$NEWRULE=str_replace("%%d", "\"".@implode(" ", $GroupAlias)."\"", $MASTER_RULE);
		$CONF[]=$NEWRULE;
		return $CONF;
		
		
	}
	
	public function iptables_inbound_objects($ID,$MASTER_RULE,$CONF=array()){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$sql="SELECT firewallfilter_sqacllinks.gpid,firewallfilter_sqacllinks.negation,
		firewallfilter_sqacllinks.zOrder,firewallfilter_sqacllinks.zmd5 as mkey,
		webfilters_sqgroups.ID as gpid,
		webfilters_sqgroups.GroupName as GroupName,
		webfilters_sqgroups.GroupType FROM firewallfilter_sqacllinks,webfilters_sqgroups
		WHERE firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID
		AND firewallfilter_sqacllinks.aclid=$ID
		AND firewallfilter_sqacllinks.direction='0'
		AND webfilters_sqgroups.enabled='1'
		ORDER BY firewallfilter_sqacllinks.zOrder";
		$array=array();
		$results = $q->QUERY_SQL($sql);
		
		if(count($results)==0){
			
			$NEWRULE=str_replace("%%s", "", $MASTER_RULE);
			if($GLOBALS["VERBOSE"]){ "Objects of ID $ID == 0 '$NEWRULE'\n";}
			$CONF=$this->iptables_outbound_objects($ID,$NEWRULE,$CONF);
			return $CONF;
		}
		$IPClass=new IP();
		
		foreach ($results as $index=>$ligne){
			$GroupName=$ligne["GroupName"];
			if($GLOBALS["VERBOSE"]){ "Objects of ID $ID ==  '$GroupName'\n";}
			$gpid=$ligne["gpid"];
			$negation=$ligne["negation"];
			$GroupType=$ligne["GroupType"];
			$prefix=null;
			$not="";
			if($negation==1){$not="!";}
			$GroupAlias[]="{$not}ipset:Group{$gpid}";
		
		}
		$NEWRULE=str_replace("%%s", "\"".@implode(" ", $GroupAlias)."\"", $MASTER_RULE);
		$CONF[]=$NEWRULE;
		return $CONF;
	}
	
	
	public function objects($ID,$direction){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$sql="SELECT firewallfilter_sqacllinks.gpid,firewallfilter_sqacllinks.negation,
		firewallfilter_sqacllinks.zOrder,firewallfilter_sqacllinks.zmd5 as mkey,
		webfilters_sqgroups.ID as gpid,
		webfilters_sqgroups.GroupName as GroupName,
		webfilters_sqgroups.GroupType FROM firewallfilter_sqacllinks,webfilters_sqgroups
		WHERE firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID
		AND firewallfilter_sqacllinks.aclid=$ID
		AND firewallfilter_sqacllinks.direction='$direction'
		AND webfilters_sqgroups.enabled='1'
		ORDER BY firewallfilter_sqacllinks.zOrder";
		$array=array();
		$results = $q->QUERY_SQL($sql);
		
		foreach ($results as $index=>$ligne){
			$negation=intval($ligne["negation"]);$not="";if($negation==1){$not="!";}
			$GroupName=$ligne["GroupName"];
			$gpid=$ligne["gpid"];
			$GroupType=$ligne["GroupType"];
			$array[$GroupType][]="{$not}ipset:Group{$gpid}";
			
		}
		
		if(count($array)==0){return;}
		$f=array();
		$FINAL_GROUPS=array();
		foreach ($array as $GroupType=>$items){
			if($GroupType=="src"){$prefix="src";}
			if($GroupType=="dst"){$prefix="dst";}
			if($GroupType=="arp"){$prefix="mac";}
			$FINAL_GROUPS[]="$prefix \"".@implode(" ", $items)."\"";
		}
		
		if(count($FINAL_GROUPS)>0){return $FINAL_GROUPS;}
		
	}
	
	

	

	
	
	
	
}