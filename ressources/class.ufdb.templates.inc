<?php

include_once (dirname(__FILE__)."/class.templates.manager.inc");

class ufdb_templates{
	private $head_content;
	private $ruleid;
	private $zmd5=null;
	private $headerAdd;
	public $MainTitle;
	public $FORM_HEAD;
	public $backgroundColor=null;
	
	public $FontSize=null;
	public $FieldsStyle=null;
	public $LegendsStyle=null;
	public $HeadsPlus=null;
	
	
	
	
	public $BackgroundRepeat=null;
	public $WelcomeMessage=null;
	public $WelcomeMessageActiveDirectory=null;
	public $LabelVoucher=null;
	public $TERMS_EXPLAIN=null;
    public $TERMS_TITLE=null;
	public $TERMS_CONDITIONS=null;
	public $REGISTER_MESSAGE_EXPLAIN=null;
	public $REGISTER_MESSAGE_SUCCESS=null;
	public $REGISTER_MESSAGE=null;
	public $REGISTER_GENERIC_PASSWORD=0;
	public $REGISTER_GENERIC_LABEL=null;
	public $REGISTER_GENERIC_PASSTXT=null;
	public $REGISTER_GENERIC_PASSERR=null;
	public $REGISTER_SUBJECT=null;
	public $CONFIRM_MESSAGE=null;
	public $RegisterTitle=null;
    public $RegisterButton=null;
	
	public $LostPasswordLink=null;
	public $ConnectionButton=null;
	public $AcceptButton=null;
	public $ArticaSplashHotSpotRedirectText=null;
	public $LabelUsername=null;
	public $LabelPassword=null;
	public $SMS_SMTP_SUBJECT=null;
	public $SMS_SMTP_BODY=null;
	public $SMS_CONFIRM_MESSAGE=null;
	public $SMS_BUTTON=null;
	public $SMS_INTRO=null;
	public $SMS_FIELD=null;
	public $SMS_FONT_SIZE=0;
	public $FooterText=null;
	public $RECOVER_MESSAGE=null;
	
	public $authentication_failed=null;
	public $LabelEmail=null;
	public $SubmitButton=null;
	public $MobileLabel=null;
	public $LabelConfirm=null;
	public $CancelButton=null;
	public $PasswordMismatch=null;
	public $ErrorThisAccountExists=null;
	public $ErrorInvalidMail=null;
	public $ALL_METHODS_LABELS=null;
	public $TemplateID=0;
	public $RootDir=null;
	public $FixedPath=false;
	public $Noutf8=false;
	
	function __construct($zmd5=null,$headerAdd=null){
		$this->zmd5=$zmd5;
		$this->headerAdd=$headerAdd;
		$this->default_values();
		
	}
	
	
	function default_values(){
			
			if($this->MainTitle==null){$this->MainTitle="HotSpot System";}
			
			if($this->zmd5<>null){
				$q=new mysql_squid_builder();
				$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM ufdb_page_rules WHERE zmd5='$this->zmd5'"));
				$this->TemplateID=$ligne["templateid"];
				echo "[{$this->zmd5}]...Template ID: $this->TemplateID\n";
			}else{
				$ligne["templateid"]=1;
				$this->TemplateID=1;
			}
			
			
			
			if(!isset($ligne["UFDBGUARD_TITLE_1"])){$ligne["UFDBGUARD_TITLE_1"]=null;}
			if($ligne["UFDBGUARD_TITLE_1"]==null){$ligne["UFDBGUARD_TITLE_1"]="This Web Page is Blocked";}
			
			if(!isset($ligne["UFDBGUARD_TITLE_2"])){$ligne["UFDBGUARD_TITLE_2"]=null;}
			if($ligne["UFDBGUARD_TITLE_2"]==null){$ligne["UFDBGUARD_TITLE_2"]="How to fix this issue ?";}
			
			if(!isset($ligne["UFDBGUARD_PARA1"])){$ligne["UFDBGUARD_PARA1"]=null;}
			if($ligne["UFDBGUARD_PARA1"]==null){$ligne["UFDBGUARD_PARA1"]="Sorry, access to requested page had to be blocked by our policy.[br]
Requested page contains inappropriate material for a correct production environment.[br]
Such pages are considered unacceptable in our network.";}
			
			if(!isset($ligne["UFDBGUARD_PARA2"])){$ligne["UFDBGUARD_PARA2"]=null;}
			if($ligne["UFDBGUARD_PARA2"]==null){$ligne["UFDBGUARD_PARA2"]="Please contact your network administrator to clarify the reasons for blocking.[br]
The following technical information may prove to be helpful.";}
			
			$this->MainTitle=$ligne["UFDBGUARD_TITLE_1"];
			
	}
	
	public function char($text){
		if($this->Noutf8){return $text;}
		$text = htmlentities($text, ENT_NOQUOTES, "UTF-8");
		$text = htmlspecialchars_decode($text);
		return utf8_encode($text);
	}
	
	
	private function SavePic($fileSource){
		echo "[$fileSource]..: SavePic()\n";
		if(trim($fileSource)==null){return;}
		if($this->RootDir==null){$this->RootDir="/home/ufdb-templates";}
		
		$SubFolder=$this->zmd5;
		if($SubFolder==null){$SubFolder="default";}
		
		$template_path="$this->RootDir/$SubFolder/files";
		@mkdir($template_path,0755,true);
		
		if(is_file("$template_path/$fileSource")){
			echo "$template_path/$fileSource..: Already exists..\n";
			return;
		}
		
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM templates_files WHERE filename='$fileSource'"));
		
		$contentfile=$ligne["contentfile"];
		$contenttype=$ligne["contenttype"];
		$contentsize=$ligne["contentsize"];
		$content=base64_decode($contentfile);
		
		if($contenttype=="text/css"){
			if(preg_match_all("#\[file=(.*?)\]#s", $content, $re)){
				foreach ($re[1] as $num=>$filename){
					echo "[$filename]..: SavePic()\n";
					$this->SavePic($filename);
					if($this->FixedPath){
						$content=str_replace($re[0][$num], "/$SubFolder/files/$filename", $content);
						continue;
					}
					$content=str_replace($re[0][$num], "/resource?zmd5=$SubFolder&fname=".urlencode($filename), $content);
				}
			}
		
		}
		
		@file_put_contents("$template_path/$fileSource.type", $contenttype);
		@file_put_contents("$template_path/$fileSource", $content);
		echo "$template_path/$fileSource..: [SAVED]..\n";
	}
	
	
	function build($content){
		if($this->RootDir==null){$this->RootDir="/home/ufdb-templates";}
		@mkdir($this->RootDir,0755,true);
		$SubFolder=$this->zmd5;
		if($SubFolder==null){$SubFolder="default";}
		
		$jquery="\n<!-- Template ID = $this->TemplateID -->\n$this->headerAdd";
		
		if(!$this->FixedPath){
			$jquery=$jquery."<script type=\"text/javascript\" language=\"javascript\" src=\"/js?script=jquery-1.8.3.js\"></script>\n<script type=\"text/javascript\" language=\"javascript\" src=\"/js?script=jquery-ui-1.8.22.custom.min.js\"></script>";
			$css="<link href=\"/css?zmd5=$SubFolder\" rel=\"stylesheet\"  type=\"text/css\"/>";
		}
		$tpl=new templates_manager($this->TemplateID);
		$template_path="$this->RootDir/$SubFolder/files";
		@mkdir($template_path,0755,true);

if(!$this->FixedPath){		
$css="\n<link rel=\"apple-touch-icon\" sizes=\"57x57\" href=\"/images?picture=apple-icon-57x57.png\">
<link rel=\"apple-touch-icon\" sizes=\"60x60\" href=\"/images?picture=apple-icon-60x60.png\">
<link rel=\"apple-touch-icon\" sizes=\"72x72\" href=\"/images?picture=apple-icon-72x72.png\">
<link rel=\"apple-touch-icon\" sizes=\"76x76\" href=\"/images?picture=apple-icon-76x76.png\">
<link rel=\"apple-touch-icon\" sizes=\"114x114\" href=\"/images?picture=apple-icon-114x114.png\">
<link rel=\"apple-touch-icon\" sizes=\"120x120\" href=\"/images?picture=apple-icon-120x120.png\">
<link rel=\"apple-touch-icon\" sizes=\"144x144\" href=\"/images?picture=apple-icon-144x144.png\">
<link rel=\"apple-touch-icon\" sizes=\"152x152\" href=\"/images?picture=apple-icon-152x152.png\">
<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/images?picture=apple-icon-180x180.png\">
<link rel=\"icon\" type=\"image/png\" sizes=\"192x192\"  href=\"/images?picture=android-icon-192x192.png\">
<link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"/images?picture=favicon-32x32.png\">
<link rel=\"icon\" type=\"image/png\" sizes=\"96x96\" href=\"/images?picture=favicon-96x96.png\">
<link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"/images?picture=favicon-16x16.png\">
<link rel=\"manifest\" href=\"/images?picture=manifest.json\">
<meta name=\"msapplication-TileColor\" content=\"#ffffff\">
<meta name=\"msapplication-TileImage\" content=\"/images?picture=ms-icon-144x144.png\">
<meta name=\"theme-color\" content=\"#ffffff\">";}			

		$css=$css."\n<style type=\"text/css\">\n\t$tpl->CssContent\n</style>\n$this->HeadsPlus\n";
		
		echo "[{$this->zmd5}]...Template ID: $this->MainTitle\n";
		echo "[{$this->zmd5}]...Template ID: $this->TemplateID - Header (".strlen($tpl->headContent).")\n";
		echo "[{$this->zmd5}]...Template ID: $this->TemplateID - Body   (".strlen($tpl->BodyContent).")\n";
		
		$template=$tpl->headContent."\n".$tpl->BodyContent;
		$TITLE=$this->char($this->MainTitle);
		
		

		if(preg_match_all("#\[file=(.*?)\]#s", $template, $re)){
			foreach ($re[1] as $num=>$filename){
				echo "Template: Found PIC.... $filename\n";
				$this->SavePic($filename);
				
				if($this->FixedPath){
					$template=str_replace($re[0][$num], "/$SubFolder/files/$filename", $template);
					continue;
				}
				$template=str_replace($re[0][$num], "/resource?zmd5=$SubFolder&fname=".urlencode($filename), $template);
			}
		
		}		
		if(preg_match_all("#\[file=(.*?)\]#s", $this->FooterText, $re)){
			foreach ($re[1] as $num=>$filename){
				echo "Footer: Found PIC.... $filename\n";
				$this->SavePic($filename);
				
				if($this->FixedPath){
					$this->FooterText=str_replace($re[0][$num], "/$SubFolder/files/$filename", $this->FooterText);
					continue;
				}
				
				$this->FooterText=str_replace($re[0][$num], "/resource?zmd5=$SubFolder&fname=".urlencode($filename), $this->FooterText);
			}
		
		}		
	
		$f[]=$content;
		$footer="<center class='footer' style='width:100%'>".$this->char($this->FooterText)."</center></div>";
		$f[]="</body>";
		$f[]="</html>";
		$final_content=@implode("\n", $f);
		$template=str_replace("%DYNAMIC_CONTENT%", utf8_encode($final_content), $template);
		$template=str_replace("%FOOTER%", $footer, $template);
		$template=str_replace("%CSS%", utf8_encode($css), $template);
		$template=str_replace("%TITLE_HEAD%", utf8_encode($TITLE), $template);
		$template=str_replace("%JQUERY%", utf8_encode($jquery), $template);
		
		return $template;
	}
	
	private function replace_accents($data){
			return htmlentities(utf8_encode($data), 0, "UTF-8");
	}
	
	
	function css(){
		$SubFolder=$this->zmd5;
		if($SubFolder==null){$SubFolder="default";}
		$tpl=new templates_manager($this->TemplateID);
		
		if(preg_match_all("#\[file=(.*?)\]#s", $tpl->CssContent, $re)){
			foreach ($re[1] as $num=>$filename){
				echo "CSS: Found PIC.... $filename\n";
				$this->SavePic($filename);
				
				if($this->FixedPath){
					$this->CssContent=str_replace($re[0][$num], "/$SubFolder/files/$filename", $this->CssContent);
					continue;
				}
				
				$tpl->CssContent=str_replace($re[0][$num], "/resource?zmd5=$this->zmd5&fname=".urlencode($filename), $tpl->CssContent);
			}
		
		}
		
		return $tpl->CssContent;
	}
	
	
}