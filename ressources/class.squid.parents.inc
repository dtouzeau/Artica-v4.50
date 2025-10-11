<?php


	class squid_parents{
		var $SquidUrgency=0;
		var $prefer_direct=0;
		var $nonhierarchical_direct=0;
        var $SquidVersion=null;
        var $isSquid5=false;
		
		function __construct(){
			$ini=new Bs_IniHandler();
			$this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
			$this->prefer_direct=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("prefer_direct"));
			$this->nonhierarchical_direct=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nonhierarchical_direct"));
            $this->SquidVersion=$this->GET_SQUID_VERSION();
            if(preg_match("#^(5|6|7|8|9)\.#",$this->SquidVersion)){$this->isSquid5=true;}
			
			
		}
		
		private function find_program($binaryname):string{
			$f["/usr/lib/squid3"]=true;
			$f["/usr/lib64/squid3"]=true;
			$f["/lib/squid3"]=true;
			$f["/lib64/squid3"]=true;
			$f["/usr/lib/squid"]=true;
			$f["/usr/lib64/squid"]=true;
			$f["/usr/local/lib/squid"]=true;
			$f["/usr/local/lib64/squid"]=true;
			$f["/opt/artica/libexec"]=true;
            foreach ($f as $dir=>$none){if(is_file("$dir/$binaryname")){return "$dir/$binaryname";}}
			return "";
				
		}



        private function GET_SQUID_VERSION():string{
            if(isset($GLOBALS["GET_SQUID_VERSION"])){return $GLOBALS["GET_SQUID_VERSION"];}
            exec("/usr/sbin/squid -v 2>&1",$results);
            foreach ($results as $line){
                if(preg_match("#Squid Cache: Version\s+([0-9\.]+)#",$line,$re)){
                    $GLOBALS["GET_SQUID_VERSION"]=$re[1];
                    return $GLOBALS["GET_SQUID_VERSION"];
                }
            }
            $GLOBALS["GET_SQUID_VERSION"]=strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion"));
            return $GLOBALS["GET_SQUID_VERSION"];
        }
		

		
		public function cache_peer():bool{
            $pac="cache_peer_access";
            $never_direct_PP=array();
			$EnableParentProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableParentProxy"));
			$PrivoxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyEnabled"));
            if(!is_file("/etc/artica-postfix/settings/Daemons/SquidCacheLevel")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidCacheLevel", 2);}
            if($PrivoxyEnabled==1){$EnableParentProxy=0;}
			$f=array();
			$HyperCache="\n";

			if($EnableParentProxy==0){
				@file_put_contents("/etc/squid3/acls_peer.php.conf" ,"");
				return true;
			}

            $sql="SELECT * FROM squid_parents_acls WHERE enabled=1 order by zorder";
			$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
			$results=$q->QUERY_SQL($sql);
			$squid_acls_groups=new squid_acls_groups();
            $always_direct_rules=array();
            $PP_PARENTS=array();
            $PP_ACCESS=array();
			foreach ($results as $index=>$ligne){
                    if(!isset($ligne["proxyproxy"])){$ligne["proxyproxy"]=0;}
					$rulename       = $ligne["rulename"];
					$aclid          = $ligne["aclid"];
                    $FINAL_WHITE    = null;
					$never_direct   = intval($ligne["never_direct"]);
					$always_direct  = intval($ligne["always_direct"]);
                    $MAIN=array();
                    if(!is_null($ligne["proxies"])) {
                        $MAIN = unserialize(base64_decode($ligne["proxies"]));
                    }
                    $proxyproxy     = intval($ligne["proxyproxy"]);
                    $use_lb         = intval($ligne["use_lb"]);
                    $f[]="#\t$aclid]/$index $rulename Load-balancer=[$use_lb], Proxy-To-Proxy=$proxyproxy";

					if($always_direct==1){
                        $f[]="#\t$aclid] $rulename is a Always Direct Rule";
                        $always_direct_rules[]=$aclid;
                        continue;
                    }

					if(count($MAIN)==0){
						$f[]="#\t$aclid] $rulename no parent as been defined";
						continue;
					}
					$acls_array=$squid_acls_groups->buildacls_bytype_items($aclid,false,"parents_sqacllinks");
					$acl_array_white=$squid_acls_groups->buildacls_bytype_items($aclid,false,"parents_white_sqacllinks");
                    if(count($acl_array_white)>0){
                        $AnnotateAlwaysDirect="";
                        if($this->isSquid5) {
                            $f[]="acl AnnotateAlwaysDirect$aclid annotate_transaction acl_peer_white=$aclid";
                            $AnnotateAlwaysDirect=" AnnotateAlwaysDirect$aclid";
                        }

                        $FINAL_WHITE=@implode("  ",$acl_array_white);
                        $FINAL_WHITE=str_replace("!!", "!", $FINAL_WHITE);
                        $FINAL_WHITE=trim($FINAL_WHITE);
                        $f[]="always_direct allow $FINAL_WHITE$AnnotateAlwaysDirect";
                    }

					if(count($acls_array)==0){$acls_array[]="all";}
					$f[]="#\t$aclid] $rulename ". count($acls_array). " items";
					$FINAL=@implode(" ", $acls_array);
					$FINAL=str_replace("!!", "!", $FINAL);
					$parents=$this->explodeParent($MAIN,$aclid,$FINAL,$proxyproxy,$use_lb);
					if(count($parents)==0){
						$f[]="#\t$aclid] $rulename no exploded parents";
						continue;
					}
                    $AnnotatePeer=null;
                    if($this->isSquid5) {
                        $PP_ACCESS[] = "acl AnnotatePeer$aclid annotate_transaction acl_peer=$aclid";
                        $AnnotatePeer=" AnnotatePeer$aclid";
                    }
					$PP_ACCESS[]="\n#\t$aclid] $rulename rules (whitelisted :".count($acl_array_white).")";
					$never_direct_PP=array();
                    if($FINAL_WHITE=="all"){$FINAL_WHITE=null;}


					foreach ($parents as $key=>$RETURN){
						$PP_PARENTS[]="cache_peer\t{$RETURN["HOSTNAME"]}\tparent\t{$RETURN["PORTNAME"]}\t0\tname={$RETURN["PEERNAME"]} {$RETURN["OPTS"]} ";
                        if(!isset($RETURN["ACLS"])){$RETURN["ACLS"]=null;}


                        $PP_ACCESS[]="$pac {$RETURN["PEERNAME"]} deny NotPeerLocalDomain";
                        $PP_ACCESS[]="$pac {$RETURN["PEERNAME"]} deny NotPeerLocal";
                        $PP_ACCESS[]="$pac {$RETURN["PEERNAME"]} deny internal_icons";
                        $PP_ACCESS[]="$pac {$RETURN["PEERNAME"]} deny MgrInfoUri";

						if($FINAL_WHITE<>null AND $RETURN["ACLS"]==null){
                            $PP_ACCESS[]="$pac {$RETURN["PEERNAME"]} deny $FINAL_WHITE";
                        }



						$PP_ACCESS[]="$pac {$RETURN["PEERNAME"]} allow {$RETURN["ACLS"]}$AnnotatePeer";
						if($never_direct==1){
						    $rule_text1="never_direct allow {$RETURN["ACLS"]}$AnnotatePeer";
                            $rule_text2="always_direct deny {$RETURN["ACLS"]}$AnnotatePeer";
						    $never_direct_PP[$rule_text1]=$rule_text1;
                            $never_direct_PP[$rule_text2]=$rule_text2;
						}
						
					}
					reset($parents);

                    foreach ($parents as $key=>$RETURN){
						$PP_ACCESS[]="$pac {$RETURN["PEERNAME"]} deny all";
					}
					
					
				}
				
            $f[]=@implode("\n", $PP_PARENTS);
            $f[]=@implode("\n", $PP_ACCESS);
            $f[]="#--------- RULES THAT BYPASS trough parent proxies";

            $always_direct=array();
            $sql="SELECT * FROM squid_parents_acls WHERE enabled=1 AND always_direct=1 order by zorder";
            $results=$q->QUERY_SQL($sql);

            foreach ($results as $index=>$ligne){
            $aclid=$ligne["aclid"];
            $acl_array_white=$squid_acls_groups->buildacls_bytype_items($aclid,false,"parents_sqacllinks");
            if(count($acl_array_white)>0) {
                $always_direct[] = @implode(" ", $acl_array_white);
            }
        }

            $sql="SELECT * FROM squid_parents_acls WHERE enabled=1 AND never_direct=1 order by zorder";
            $results=$q->QUERY_SQL($sql);

            foreach ($results as $index=>$ligne){
            $aclid=$ligne["aclid"];
            $acl_array_white=$squid_acls_groups->buildacls_bytype_items($aclid,false,"parents_sqacllinks");
            if(count($acl_array_white)>0) {
                $return_acls = @implode(" ", $acl_array_white);
                $never_direct_PP["never_direct allow $return_acls"]="never_direct allow $return_acls";
            }
        }


            $count_always_direct=count($always_direct);
            $f[]="#---------  Rules on always_direct = $count_always_direct";
            $f[]="always_direct allow internal_icons";
            $f[]="always_direct allow NotPeerLocal";
            $f[]="always_direct allow NotPeerLocalDomain";
            $f[]="always_direct allow MgrInfoUri";
            $f[]="never_direct deny NotPeerLocal";
            $f[]="never_direct deny NotPeerLocalDomain";

            if(count($always_direct)>0){
            foreach ($always_direct as $line){
                $f[]="always_direct allow $line";
            }
        }

            if(count($always_direct_rules)>0){
            foreach ($always_direct_rules as $aclid) {
                $acls_array = $squid_acls_groups->buildacls_bytype_items($aclid, false, "parents_sqacllinks");
                if(count($acls_array)==0){continue;}
                $acls_textes=@implode(" ",$acls_array);
                $f[]="never_direct deny $acls_textes";
                $f[]="always_direct allow $acls_textes";
            }
        }
				
		    if(count($never_direct_PP)>0){
		    foreach ($never_direct_PP  as $index=>$line ) {
                $f[] = $line;
            }
		}
             $f[]="";
		    @file_put_contents("/etc/squid3/acls_peer.php.conf", @implode("\n", $f).$HyperCache);
            return true;
    }
		
		function explodeParent($MAIN,$aclid,$acls,$proxyproxy=0,$use_lb=0):array{
			$RETURN=array();
            $options["tls"]="tls";
			$options["proxy-only"]="proxy-only";
			$options["Weight"]="Weight=nn";
			$options["ttl"]="ttl=nn";
			$options["no-query"]="no-query";
			$options["default"]="default";
			$options["round-robin"]="round-robin";
			$options["multicast-responder"]="multicast-responder";
			$options["closest-only"]="closest-only";
			$options["no-digest"]="no-digest";
			$options["no-tproxy"]="no-tproxy";
			$options["no-netdb-exchange"]="no-netdb-exchange";
			$options["no-delay"]="no-delay";
			$options["userpassword"]="login=user:password";
			$options["login_PASSTHRU"]="login=PASSTHRU";
			$options["login_PASS"]="login=PASS";
			$options["connect-timeout"]="connect-timeout=nn";
            if($proxyproxy==1){
                unset($options["userpassword"]);
                unset($options["login_PASSTHRU"]);
                unset($options["login_PASS"]);
            }

            if($use_lb==1){
                $OPTS[]="no-query";
                $OPTS[]="default";
                $OPTS[]="connect-timeout=3";
                $OPTS[]="connect-fail-limit=5";
                $OPTS[]="no-tproxy";

                if($proxyproxy==1){ $OPTS[]="login=*:pass";}
                $RETURN["127.0.0.1:2320"]["HOSTNAME"]="127.0.0.1";
                $RETURN["127.0.0.1:2320"]["PORTNAME"]=2320;
                $RETURN["127.0.0.1:2320"]["PEERNAME"]="Peer{$aclid}1";
                $RETURN["127.0.0.1:2320"]["OPTS"]=@implode(" ", $OPTS);
                $RETURN["127.0.0.1:2320"]["ACLS"]=$acls;
                return $RETURN;
            }
			
			$c=0;
            foreach ($MAIN as $parentIP=>$ARRAY){
				$OPTS=array();
				$c++;
				if(!preg_match("#(.+?):([0-9]+)#", $parentIP,$re)){continue;}
				$RETURN[$parentIP]["HOSTNAME"]=$re[1];
				$RETURN[$parentIP]["PORTNAME"]=$re[2];
				$RETURN[$parentIP]["PEERNAME"]="Peer{$aclid}$c";
				
				reset($options);
                foreach ($options as $key=>$val){
					$mykey="OPT_{$key}";
					if(!isset($MAIN[$parentIP][$mykey])){continue;}
					if($GLOBALS["VERBOSE"]){echo "MAIN[{$parentIP}][$mykey] -> YES\n";}
					$default_enabled=intval($MAIN[$parentIP][$mykey]["ENABLED"]);
					if(!isset($MAIN[$parentIP][$mykey]["VALUE"])){$MAIN[$parentIP][$mykey]["VALUE"]=1;}
					$default_value=$MAIN[$parentIP][$mykey]["VALUE"];
					if($GLOBALS["VERBOSE"]){echo "$mykey= {$MAIN[$parentIP][$mykey]["VALUE"]}/ {$MAIN[$parentIP][$mykey]["ENABLED"]}\n";}
					
					if($default_enabled==0){continue;}

					if(preg_match("#=nn#", $val)){
						$OPTS[]=strtolower($key)."=".$default_value;
						continue;
					}
					
					if(preg_match("#user:password#", $val)){
                        if($proxyproxy==1){continue;}
						$OPTS[]="login={$MAIN[$parentIP][$mykey]["USR_{$key}"]}:{$MAIN[$parentIP][$mykey]["PASS_{$key}"]}";
						continue;
							
					}
                    if($mykey=="OPT_login_PASSTHRU"){
                        $OPTS[]="login=PASSTHRU";
                        continue;
                    }
                    if($mykey=="OPT_login_PASS"){
                        $OPTS[]="login=PASS";
                        continue;
                    }


					$OPTS[]="$key";
					
				}
				
				
				if(count($OPTS)==0){$OPTS[]="default";}
                if($proxyproxy==1){$OPTS[]="login=*:pass";}
				$RETURN[$parentIP]["OPTS"]=@implode(" ", $OPTS);
				$RETURN[$parentIP]["ACLS"]=$acls;
			}	
			
		return $RETURN;}
		
	}