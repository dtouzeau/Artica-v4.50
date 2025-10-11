<?php


function certificate_center_parse_array($results){
	
	foreach ($results as $num=>$ligne){
		if(preg_match("#Subject:\s+(.+)#", $ligne,$re)){
			$MAIN["Subject"]=$re[1];
		}
		if(preg_match("#Public-Key:\s+\(([0-9]+) bit#", $ligne,$re)){
			$MAIN["levelenc"]=$re[1];
		}
	
	}
	
	$SUBJECT_TR=explode(",",$MAIN["Subject"]);
	while (list ($num, $ligne) = each ($SUBJECT_TR) ){
		if(preg_match("#(.*?)=(.*)#", $ligne,$re)){
			$SUBJECT_ARRAY[trim(strtoupper($re[1]))]=trim($re[2]);
			
		}
	}
	
	
	$MAIN["CountryName"]=$SUBJECT_ARRAY["C"];
	
	
	if(isset($SUBJECT_ARRAY["L"])){$MAIN["localityName"]=$SUBJECT_ARRAY["L"];}
	if(isset($SUBJECT_ARRAY["ST"])){$MAIN["stateOrProvinceName"]=$SUBJECT_ARRAY["ST"];}
	$MAIN["OrganizationalUnit"]=$SUBJECT_ARRAY["OU"];
	$MAIN["OrganizationName"]=$SUBJECT_ARRAY["O"];
	
	
	
	if(isset($SUBJECT_ARRAY["CN"])){
		if(preg_match("#^(.+?)\/#", $SUBJECT_ARRAY["CN"],$re)){
			$MAIN["CommonName"]=$re[1];
		}else{
			$MAIN["CommonName"]=$SUBJECT_ARRAY["CN"];
		}
	
		if(preg_match("#emailAddress=(.*?)($|\s+)#", $SUBJECT_ARRAY["CN"],$re)){
			$MAIN["emailAddress"]=$re[1];
		}
	
	}
	
	return $MAIN;

	
	
}