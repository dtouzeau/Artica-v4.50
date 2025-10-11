<?php


class UFDBGUARD_TEMPLATES{
	
	
	private function BuildTemplatesConfig_from_mysql($ARRAY){
		if(!$GLOBALS["CORP_LICENSE"]){if($GLOBALS["VERBOSE"]){echo "<h1 style='color:yellow'>NO LICENSE!\n<br></H1>";}return;}
		 
		$md5=$this->BuildTemplatesConfig_find_rule($ARRAY);
		if($md5==null){if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>NOT TEMPLATE MATCH $md5</span>\n<br>";}return;}
			 
			if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>TEMPLATE MATCH $md5</span>\n<br>";}
	
			$q=new mysql_squid_builder();
			$sql="SELECT * FROM ufdb_design WHERE zmd5='$md5'";
			if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>$sql</span>\n<br>";}
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
			 
			$GLOBALS["UfdbGuardHTTP"]["AllowUnblock"]=intval($this->GET_INFO("UfdbGuardHTTPAllowUnblock"));
			$SquidHTTPTemplateLogoPath=$ligne["SquidHTTPTemplateLogoPath"];
			$SquidHTTPTemplateLogoEnable=intval($ligne["SquidHTTPTemplateLogoEnable"]);
			$SquidHTTPTemplateLogoPositionH=$ligne["SquidHTTPTemplateLogoPositionH"];
			$SquidHTTPTemplateLogoPositionL=$ligne["SquidHTTPTemplateLogoPositionL"];
			$SquidHTTPTemplateSmiley=intval($ligne["SquidHTTPTemplateSmiley"]);
			$SquidHTTPTemplateSmileyEnable=intval($ligne["SquidHTTPTemplateSmileyEnable"]);
			if($SquidHTTPTemplateSmiley==0){$SquidHTTPTemplateSmiley=2639;}
			$picturealign=$ligne["picturealign"];
	
			$UfdbGuardHTTPNoVersion=$ligne["UfdbGuardHTTPNoVersion"];
	
			if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white'>";}
			if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":UfdbGuardHTTPNoVersion: $UfdbGuardHTTPNoVersion</li>";}
			if($GLOBALS["VERBOSE"]){echo "</div>";}
	
	
			$UfdbGuardHTTPBackgroundColor=$ligne["UfdbGuardHTTPBackgroundColor"];
			$UfdbGuardHTTPFamily=$ligne["UfdbGuardHTTPFamily"];
			$UfdbGuardHTTPFontColor=$ligne["UfdbGuardHTTPFontColor"];
			$UFDBGUARD_TITLE_1=$ligne["UFDBGUARD_TITLE_1"];
			$UFDBGUARD_PARA1=$ligne["UFDBGUARD_PARA1"];
			$UFDBGUARD_TITLE_2=$ligne["UFDBGUARD_TITLE_2"];
			$UFDBGUARD_PARA2=$ligne["UFDBGUARD_PARA2"];
			$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];
	
			$UFDBGUARD_UNLOCK_LINK=$ligne["UFDBGUARD_UNLOCK_LINK"];
			$UFDBGUARD_TICKET_LINK=$ligne["UFDBGUARD_TICKET_LINK"];
	
			$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];
			$UfdbGuardHTTPBackgroundColorBLK=$ligne["UfdbGuardHTTPBackgroundColorBLK"];
	
			if($GLOBALS["VERBOSE"]){echo "<h1 style='color:yellow'>UfdbGuardHTTPBackgroundColorBLK = $UfdbGuardHTTPBackgroundColorBLK\n<br></H1>";}
	
			$UfdbGuardHTTPBackgroundColorBLKBT=$ligne["UfdbGuardHTTPBackgroundColorBLKBT"];
			$UfdbGuardHTTPDisableHostname=intval($ligne["UfdbGuardHTTPDisableHostname"]);
	
			if($UFDBGUARD_TITLE_1==null){$UFDBGUARD_TITLE_1="{UFDBGUARD_TITLE_1}";}
			if($UFDBGUARD_PARA1==null){$UFDBGUARD_PARA1="{UFDBGUARD_PARA1}";}
			if($UFDBGUARD_PARA2==null){$UFDBGUARD_PARA2="{UFDBGUARD_PARA2}";}
			if($UFDBGUARD_TITLE_2==null){$UFDBGUARD_TITLE_2="{UFDBGUARD_TITLE_2}";}
	
	
			if(!is_numeric($UfdbGuardHTTPEnablePostmaster)){$UfdbGuardHTTPEnablePostmaster=1;}
			if($UfdbGuardHTTPBackgroundColor==null){$UfdbGuardHTTPBackgroundColor="#8c1919";}
			if($UfdbGuardHTTPBackgroundColorBLK==null){$UfdbGuardHTTPBackgroundColorBLK="#0300AC";}
			if($UfdbGuardHTTPBackgroundColorBLKBT==null){$UfdbGuardHTTPBackgroundColorBLKBT="#625FFD";}
			if($UfdbGuardHTTPFontColor==null){$UfdbGuardHTTPFontColor="#FFFFFF";}
	
			if(substr($UfdbGuardHTTPFontColor,0 ,1)<>"#"){$UfdbGuardHTTPFontColor="#$UfdbGuardHTTPFontColor";}
			if(substr($UfdbGuardHTTPBackgroundColor,0 ,1)<>"#"){$UfdbGuardHTTPBackgroundColor="#$UfdbGuardHTTPBackgroundColor";}
			if(substr($UfdbGuardHTTPBackgroundColorBLK,0 ,1)<>"#"){$UfdbGuardHTTPBackgroundColorBLK="#$UfdbGuardHTTPBackgroundColorBLK";}
			if(substr($UfdbGuardHTTPBackgroundColorBLKBT,0 ,1)<>"#"){$UfdbGuardHTTPBackgroundColorBLKBT="#$UfdbGuardHTTPBackgroundColorBLKBT";}
	
			if($UfdbGuardHTTPFamily==null){$UfdbGuardHTTPFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
	
	
	
			if(!is_numeric($SquidHTTPTemplateLogoPositionH)){$SquidHTTPTemplateLogoPositionH=10;}
			if(!is_numeric($SquidHTTPTemplateLogoPositionL)){$SquidHTTPTemplateLogoPositionL=10;}
	
			if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}
	
	
	
			$GLOBALS["UfdbGuardHTTP"]["picturename"]=$ligne["picturename"];
			$GLOBALS["UfdbGuardHTTP"]["picturemode"]=$ligne["picturemode"];
			$GLOBALS["UfdbGuardHTTP"]["picturealign"]=$ligne["picturealign"];
			$GLOBALS["UfdbGuardHTTP"]["UfdbGuardHTTPDisableHostname"]=$UfdbGuardHTTPDisableHostname;
	
	
	
			$GLOBALS["UfdbGuardHTTP"]["Family"]=$UfdbGuardHTTPFamily;
			$GLOBALS["UfdbGuardHTTP"]["md5"]=$md5;
			$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoEnable"]=$SquidHTTPTemplateLogoEnable;
			$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoPositionH"]=$SquidHTTPTemplateLogoPositionH;
			$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoPositionL"]=$SquidHTTPTemplateLogoPositionL;
	
			if(!is_file("/usr/share/artica-postfix/ressources/logs/web/$md5{$ligne["picturename"]}")){
				@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/$md5{$ligne["picturename"]}", $ligne["picture"]);
					
			}
			$GLOBALS["UfdbGuardHTTP"]["picture_path"]="ressources/logs/web/$md5{$ligne["picturename"]}";
			$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=$UfdbGuardHTTPNoVersion;
	
			if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white'>";}
			if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":UfdbGuardHTTPNoVersion: {$GLOBALS["UfdbGuardHTTP"]["NoVersion"]}</li>";}
			if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":SquidHTTPTemplateSmileyEnable: $SquidHTTPTemplateSmileyEnable / $SquidHTTPTemplateSmiley</li>";}
			if($GLOBALS["VERBOSE"]){echo "</div>";}
	
	
			$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]=$UfdbGuardHTTPBackgroundColor;
			$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"]=$UfdbGuardHTTPEnablePostmaster;
			$GLOBALS["UfdbGuardHTTP"]["FontColor"]=$UfdbGuardHTTPFontColor;
			$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]=$UFDBGUARD_TITLE_1;
			$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]=$UFDBGUARD_PARA1;
			$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]=$UFDBGUARD_TITLE_2;
			$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]=$UFDBGUARD_PARA2;
			$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]=$UfdbGuardHTTPBackgroundColorBLK;
			$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]=$UfdbGuardHTTPBackgroundColorBLKBT;
			$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"]=$UFDBGUARD_UNLOCK_LINK;
			$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TICKET_LINK"]=$UFDBGUARD_TICKET_LINK;
			$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]=$SquidHTTPTemplateSmiley;
			$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"]=$SquidHTTPTemplateSmileyEnable;
	
	
	
	
	
	
	
			 
	}
}