<?php
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.html2text.inc");
include_once(dirname(__FILE__)."/ressources/class.");

$q=new mysql_squid_builder();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);

ParseUri("http://www.el-ladies.com");
return;
ParseUri("http://www.elephantlistsearch.com/fetish-porn/");
return;


echo " **** elephantlist **** \n";
$curl=new ccurl("http://www.elephantlist.com");
$curl->NoHTTP_POST=true;
if(!$curl->get()){echo "Failed\n";}
$CACHE=unserialize(@file_get_contents("/root/porn-cache.db"));
//echo $curl->data;

$table=ClickTextdirect($curl->data);

if(count($table)>0){
	foreach ($table as $num=>$ligne){
		$f[]=$ligne;
	}
}

$table=elephantlist_Redirected($curl->data);

if(count($table)>0){
	foreach ($table as $num=>$ligne){
		$f[]=$ligne;
	}
}

if(preg_match_all("#elephantlist\.com\/archives\/(.+?)\.html#is", $curl->data, $re)){
	while (list ($num, $ligne) = each ($re[1]) ){
		$uri="http://www.elephantlist.com/archives/$ligne.html";
		echo " **** $uri **** \n";
		$table=elephantlist_fullparse($uri);
		if(count($table)>0){
			foreach ($table as $num=>$ligne){
				$f[]=$ligne;
			}
		}
	}
}



@file_put_contents("/root/porn-cache.db", serialize($CACHE));

$f[]="http://www.pinkworld.com/index.html";
$f[]="http://www.pinkworld.com/porn/amateur-porn.html";
$f[]="http://www.pinkworld.com/porn/anal-sex.html";
$f[]="http://www.pinkworld.com/porn/anime-porn.html";
$f[]="http://www.pinkworld.com/porn/asian-porn.html";
$f[]="http://www.pinkworld.com/porn/sexy-babes.html";
$f[]="http://www.pinkworld.com/porn/big-tits.html";
$f[]="http://www.pinkworld.com/porn/free-blowjobs.html";
$f[]="http://www.pinkworld.com/porn/celebrity-sex.html";
$f[]="http://www.pinkworld.com/porn/nude-cheerleaders.html";
$f[]="http://www.pinkworld.com/porn/ebony-porn.html";
$f[]="http://www.pinkworld.com/porn/fat-sex.html";
$f[]="http://www.pinkworld.com/porn/fetish-sex.html";
$f[]="http://www.pinkworld.com/porn/foot-sex.html";
$f[]="http://www.pinkworld.com/porn/group-sex.html";
$f[]="http://www.pinkworld.com/porn/hairy-girls.html";
$f[]="http://www.pinkworld.com/porn/free-handjobs.html";
$f[]="http://www.pinkworld.com/porn/hardcore-sex.html";
$f[]="http://www.pinkworld.com/porn/indian-porn.html";
$f[]="http://www.pinkworld.com/porn/interracial-porn.html";
$f[]="http://www.pinkworld.com/porn/latina-sex.html";
$f[]="http://www.pinkworld.com/porn/lesbian-porn.html";
$f[]="http://www.pinkworld.com/porn/erotic-lingerie.html";
$f[]="http://www.pinkworld.com/porn/mature-porn.html";
$f[]="http://www.pinkworld.com/porn/sexy-panties.html";
$f[]="http://www.pinkworld.com/porn/pantyhose-girls.html";
$f[]="http://www.pinkworld.com/porn/free-pornstars.html";
$f[]="http://www.pinkworld.com/porn/pregnant-sex.html";
$f[]="http://www.pinkworld.com/porn/nude-redheads.html";
$f[]="http://www.pinkworld.com/porn/shemale-sex.html";
$f[]="http://www.pinkworld.com/porn/small-tits.html";
$f[]="http://www.pinkworld.com/porn/teen-porn.html";
$f[]="http://www.pinkworld.com/porn/free-voyeur.html";
$f[]="http://www.worldsex.com";
$f[]="http://www.al4a.com";

while (list ($num, $ligne) = each (	$f)){
	ParseUri($ligne);
}

function ParseUri($uri,$next=false){
	if(isset($GLOBALS["URI_PARSED"][$uri])){
		echo " **** $uri  ALREADY CHECKED ". count($GLOBALS["URI_PARSED"]). " checked uris **** \n";
		return;
	}
	$GLOBALS["URI_PARSED"][$uri]=true;
	if(preg_match("#\/tp\/out\.php\?g=.*?&p=.*?&link=(.+?)#", $uri)){return;}
	$CACHE=unserialize(@file_get_contents("/root/porn-cache.db"));
	$URLAR=parse_url($uri);
	$source_sitename=$URLAR["host"];
	$GLOBALS["PARSED_HOST"][$source_sitename]=true;
	
	$q=new mysql_squid_builder();
	
	
	
	
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){echo "Failed\n";}
	$h2t =new html2text($curl->data);
	$h2t->get_text();
	
	echo " **** $uri / $source_sitename **** CHECKS ". count($GLOBALS["URI_PARSED"]). " checked uris\n";
	
	echo " **** $uri ". count($h2t->_link_array). " Links, strating loop\n";
	
	while (list ($num, $ligne) = each (	$h2t->_link_array)){
		$ligne=trim(strtolower($ligne));
		if($ligne==null){continue;}
		if(preg_match("#\/tp\/out\.php\?to=(.+?)&link=#", $ligne,$re)){$ligne="http://{$re[1]}";}
		
		if(preg_match("#click\.php\?id=.*?&s=.*?&c=.*?&l=.*?&u=(.+)#",$ligne,$re)){
			$re[1]=urldecode($re[1]);
			$ligne="http://{$re[1]}";
		}
		
		$ligne=str_replace("http://http://","http://",$ligne);
		
		if(preg_match("#^\/#", $ligne)){continue;}
		
		
		
		
		if($next){
			echo "ParseUri($ligne)\n";
			ParseUri($ligne);
		}
		
		$URLAR=parse_url($ligne);
		if(!isset($URLAR["host"])){continue;}
		$sitename=$URLAR["host"];

		if(!preg_match("#elephantlist#", $ligne)){
			if(!isset($GLOBALS["PARSED_HOST"][$sitename])){
				echo "ParseUri($ligne)\n";
				ParseUri($ligne);
			}else{
				echo "$sitename ALREADY PARSED\n";
			}
		}
		
		
		
		$f[]=$ligne;

		
		if($sitename==null){
			echo "$sitename, Already scanned\n";
			continue;
		}
		
		if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		$familysite=$q->GetFamilySites($sitename);
		if(isset($CACHE[$sitename])){continue;}
		if(isset($CACHE[$familysite])){continue;}
		$q->categorize($sitename, "porn");
		$CACHE[$sitename]=true;
		if($familysite<>$sitename){$q->categorize($familysite, "porn");}
		echo "$sitename - $familysite -> PORN\n";
		$CACHE[$familysite]=true;
	}
	@file_put_contents("/root/porn-cache.db", serialize($CACHE));
	return $f;
}


function ClickTextdirect($data){
	$CACHE=unserialize(@file_get_contents("/root/porn-cache.db"));
	$q=new mysql_squid_builder();
	if(preg_match_all("#href=\"clicktextdirect.php\?id=[0-9]+.*?&u=(.+?)\"#s", $data, $re)){
	
	
		while (list ($num, $ligne) = each ($re[1]) ){
			$URLAR=parse_url(urldecode($ligne));
			if(!isset($URLAR["host"])){continue;}
			$sitename=$URLAR["host"];
			if(preg_match("#^www\.(.+)#", $sitename,$ri)){$sitename=$ri[1];}
			$familysite=$q->GetFamilySites($sitename);
			$f[]="http://$sitename";
			if(isset($CACHE[$sitename])){continue;}
			if(isset($CACHE[$familysite])){continue;}
	
			$q->categorize($sitename, "porn");
			$CACHE[$sitename]=true;
			if($familysite<>$sitename){$q->categorize($familysite, "porn");}
			echo "$sitename - $familysite \n";
			$CACHE[$familysite]=true;
		}
	
	}else{
		echo "ClickTextdirect - nothing found\n";
		return array();
	}

	@file_put_contents("/root/porn-cache.db", serialize($CACHE));
	return $f;
}

function elephantlist_Redirected($data){
	$CACHE=unserialize(@file_get_contents("/root/porn-cache.db"));
	$q=new mysql_squid_builder();
	if(preg_match_all("#elephantlist\.com\/tp\/out\.php\?to=(.+?)&#is",$data, $re)){
	
		while (list ($num, $ligne) = each ($re[1]) ){
			$sitename=$ligne;
			$familysite=$q->GetFamilySites($ligne);
			if(!isset($CACHE[$sitename])){
				$q->categorize($sitename, "porn");
				$CACHE[$sitename]=true;
			}
	
			if(!isset($CACHE[$familysite])){
				$q->categorize($familysite, "porn");
				$CACHE[$familysite]=true;
			}
	
			$f[]="http://$sitename";
		}
	
	}else{
		echo "elephantlist_Redirected - nothing found\n";
		return array();
	}
	@file_put_contents("/root/porn-cache.db", serialize($CACHE));
	return $f;
}

function elephantlist_fullparse($uri){
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){echo "$uri -> Failed\n";}
	$f=array();
	$table=ClickTextdirect($curl->data);
	if(count($table)>0){
		foreach ($table as $num=>$ligne){
			$f[]=$ligne;
		}
	}

	$table=elephantlist_Redirected($curl->data);

	if(count($table)>0){
		foreach ($table as $num=>$ligne){
			$f[]=$ligne;
		}
	}
	
	$table=ParseUri($uri);
	if(count($table)>0){
		foreach ($table as $num=>$ligne){
			echo "elephantlist_fullparse - Parse URI \"$ligne\"\n";
			ParseUri("$ligne",true);
			$f[]=$ligne;
		}
	}
	return $f;
}
