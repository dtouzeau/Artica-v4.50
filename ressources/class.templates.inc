<?php
$GLOBALS["posix_getuid"]=1000;
if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}
CheckVerbose();
if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
if(!isset($GLOBALS["SESSION_ID"])){$GLOBALS["SESSION_ID"]=session_id();}
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
if(!isset($GLOBALS["DEBUG_LANG"])){$GLOBALS["DEBUG_LANG"]=false;}
if(!isset($GLOBALS["DEBUG_MEM"])){$GLOBALS["DEBUG_MEM"]=false;}
$start = microtime(true);
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once(dirname(__FILE__)."/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
ExecTBtime($start,"CLASS_SOCKETS",__LINE__);
$start = microtime(true);
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");}
if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; }
if(isset($_GET["debug-langs"])){$GLOBALS["DEBUG_LANG"]=true;}
@header("Pragma: no-cache");
@header("Expires: 0");
@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
@header("Cache-Control: no-cache, must-revalidate");
if(isset($GLOBALS["DEBUG_PROCESS"])){include_once(dirname(__FILE__)."/logs.inc");}
if(!isset($GLOBALS["EXECUTED_AS_ROOT"])){ if($GLOBALS["posix_getuid"]==0){ $GLOBALS["EXECUTED_AS_ROOT"]=true; }else{ $GLOBALS["EXECUTED_AS_ROOT"]=false;} }


ExecTBtime($start,"",__LINE__);
$start = microtime(true);
define("BECALL",base64_decode("T05MWV9DT1JQX0hUTUw="));
debug_mem();
if(isset($_GET["debug-page"])){	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
debug_mem();
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
include_once(dirname(__FILE__) . "/class.sockets.inc");
debug_mem();
if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
getLocalTimezone();
CheckSessions();
ExecTBtime($start,"CheckSessions",__LINE__);
debug_mem();
include_once(dirname(__FILE__) . '/class.ldap.inc');
debug_mem();
if(!function_exists("writelogs")){include_once(dirname(__FILE__) . '/logs.inc');}
debug_mem();
if(is_file(dirname(__FILE__) . '/class.users.menus.inc')){include_once(dirname(__FILE__) . '/class.users.menus.inc'); }
debug_mem();
include_once(dirname(__FILE__).  '/class.os.system.inc');
include_once(dirname(__FILE__).  '/class.ini.inc');
include_once(dirname(__FILE__).  '/class.tcpip.inc');
include_once(dirname(__FILE__).  '/class.mysql.inc');
include_once(dirname(__FILE__).  '/class.sqlite.inc');
include_once(dirname(__FILE__)."/class.memcached.inc");
debug_mem();

$GLOBALS['_DLANG']='en';
$GLOBALS['_LANG'] = array(
    'af',	//	afrikaans.
    'ar',	//	arabic.
    'bg',	//	bulgarian.
    'ca',	//	catalan.
    'cs',	//	czech.
    'da',	//	danish.
    'de',	//	german.
    'el',	//	greek.
    'en',	//	english.
    'es',	//	spanish.
    'et',	//	estonian.
    'fi',	//	finnish.
    'fr',	//	french.
    'gl',	//	galician.
    'he',	//	hebrew.
    'hi',	//	hindi.
    'hr',	//	croatian.
    'hu',	//	hungarian.
    'id',	//	indonesian.
    'it',	//	italian.
    'ja',	//	japanese.
    'ko',	//	korean.
    'ka',	//	georgian.
    'lt',	//	lithuanian.
    'lv',	//	latvian.
    'ms',	//	malay.
    'nl',	//	dutch.
    'no',	//	norwegian.
    'pl',	//	polish.
    'pt',	//	portuguese.
    'ro',	//	romanian.
    'ru',	//	russian.
    'sk',	//	slovak.
    'sl',	//	slovenian.
    'sq',	//	albanian.
    'sr',	//	serbian.
    'sv',	//	swedish.
    'th',	//	thai.
    'tr',	//	turkish.
    'uk',	//	ukrainian.
    'zh' 	//	chinese.
);

function CurrentPageName(){
    $phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
    return $phpPage;}
class templates{
    var $head;
    var $body;
    var $web_page;
    var $language;
    var $title;
    var $js_add;
    var $GLOBAL_CONFIG;
    var $array_config;

    function __construct($title=null,$body=null,$js_add=null,$do_nothing=0,$arrayConfig=array()){
        $GLOBALS["CURRENT_PAGE"]=CurrentPageName();
        if(!isset($GLOBALS["DEBUG_TEMPLATE"])){$GLOBALS["DEBUG_TEMPLATE"]=false;}
        $this->SettingsInc();
        if(isset($_COOKIE["artica-language"])){ $this->language=$_COOKIE["artica-language"]; }

        $this->array_config=$arrayConfig;
        if($do_nothing==1){return false;}

        $this->title=$title;
        $this->js_add=$js_add;

        if($this->language==null){
            $this->language=$this->_detect_lang();
        }

        $this->headers();
        $this->Body($body);
        $this->web_page="$this->head $this->body</html>";
        $this->_parse_body();
    }

    private function SettingsInc(){
        if(is_file(dirname(__FILE__) . '/settings.inc')){return false;}
        if(!$GLOBALS["EXECUTED_AS_ROOT"]){return false;}
        if(!class_exists("unix")){return false;}
        $unix=new unix();
        $unix->Process1(true);
        return true;
    }

    function headers($array=array()){}

    function _parse_body($content=null){
        return $this->_ENGINE_parse_body($content);
    }




    function javascript_parse_text($content,$noremovecrlf=0){
        if(is_null($content)){
            return "";
        }
        if(strlen($content)==0){
            return "";
        }

        $content=$this->_ENGINE_parse_body($content);
        if(is_null($content)){
            return "";
        }
        $content=str_replace("<li>", "-", $content);
        $content=str_replace("</li>", "", $content);
        $content=str_replace("<ul>", "", $content);
        $content=str_replace("</ul>", "", $content);
        $content=str_replace("<br>", "\n", $content);
        $content=str_replace("\n\n", "\n", $content);
        $content=str_replace("\n\n", "\n", $content);
        $content=str_replace("&nbsp;", " ", $content);
        $content=str_replace("<strong>", " ", $content);
        $content=str_replace("</strong>", " ", $content);
        $content=str_replace("%C3%8A", "\u00ca", $content);
        $content=str_replace("%C2%BF", "\u003F", $content);
        $content=str_ireplace("%C3%A4","\u00e4",$content);
        $content=str_ireplace("%C3%BC","\u00fc",$content);
        $content=str_ireplace("%C3%B6","\u00f6",$content);
        $content=str_ireplace("%C3%9F", "\u00df",$content);
        $content=str_ireplace("%C3%8D","\u00ed",$content);
        $content=str_ireplace("%C3%84","\u00c4",$content);
        $content=str_ireplace("%C3%9C","\u00dc",$content);
        $content=str_ireplace("%C3%AC","\u00ec",$content);
        $content=str_ireplace("%C3%88","\u00c8",$content);
        $content=str_ireplace("%C3²","\u00f2",$content);

        $content=str_ireplace("%C5%82","\u0142",$content);
        $content=str_ireplace("%C5%BC","\u017c",$content);
        $content=str_ireplace("%C4%87","\u0107",$content);
        $content=str_ireplace("%C5%BA","\u017a",$content);
        $content=str_ireplace("%C5%9A","\u015a",$content);
        $content=str_ireplace("%C4%99","\u0119",$content);
        $content=str_ireplace("%C5%84","\u0144",$content);
        $content=str_ireplace("%C4%85","\u0105",$content);
        $content=str_ireplace("%C5%9B","\u015b",$content);
        $content=str_ireplace("%C5%B9","\u0179",$content);
        $content=str_ireplace("%C5%9A","\u015a",$content);
        $content=str_ireplace("%C5%A0","\u0160",$content);
        $content=str_ireplace("%u0142","\u0142",$content);
        $content=str_ireplace("%u0105","\u0105",$content);
        $content=str_ireplace("%u0107","\u0107",$content);
        $content=str_ireplace("%u017C","\u017c",$content);
        $content=str_ireplace("%u0119","\u0119",$content);
        $content=str_ireplace("%u015B","\u015B",$content);
        $content=str_ireplace("%u0144","\u0144;",$content);

        //$content=replace_accents($content);
        $content=html_entity_decode($content);
        $content=replace_accents($content);
        if(!isset($_SESSION["DoNotutf8EncodeJS"])){
            $sock=new sockets();
            $_SESSION["DoNotutf8EncodeJS"]=$sock->GET_INFO("DoNotutf8EncodeJS");
            if(!is_numeric($_SESSION["DoNotutf8EncodeJS"])){$_SESSION["DoNotutf8EncodeJS"]=0;}
        }

        $DoNotutf8EncodeJS=$_SESSION["DoNotutf8EncodeJS"];
        if($this->language=="fr"){$DoNotutf8EncodeJS=1;}

        if($DoNotutf8EncodeJS==0){
            $content=$this->utf8_encode($content);
        }

        if($noremovecrlf==0){
            $content=nl2br($content);
            $content=str_replace("<br>",'\n',$content);
            $content=str_replace("<br />",'\n',$content);
            $content=str_replace("\n\n",'\n',$content);
            $content=str_replace("\n",'\\n',$content);


        }else{
            $content=str_replace("<br />",'',$content);
            $content=str_replace("\\n","\n",$content);

        }
        $content=str_replace("'","`",$content);
        $content=str_replace("\`","`",$content);
        $content=str_replace("\\n\\n","\\n",$content);

        return $content;
    }

    public function utf8_decode($string):string{
        if(function_exists("mb_convert_encoding")){
            return strval(mb_convert_encoding($string, 'ISO-8859-1'));

        }
        return utf8_decode($string);
    }

    public function utf8_encode($string):string{
        if(function_exists("mb_convert_encoding")){
            return strval(mb_convert_encoding($string, 'UTF-8'));

        }
        return utf8_encode($string);
    }

    private function repairLanguage():string{
        if(isset($_SESSION["detected_lang"])){
            return $_SESSION["detected_lang"];
        }
        $lang=$this->_detect_lang();
        if(strlen($lang)>1){
            $_SESSION["detected_lang"]=$lang;
            return $lang;
        }
        $_SESSION["detected_lang"]="en";
        return "en";
    }



    private function get_cached_text($text){
        if(strpos(" $text", " document.getElementById")>0){return "";}
        if(strpos(" $text", " if(")>0){return "";}
        if(strpos(" $text", "$('#")>0){return "";}
        if(strpos(" $text", ".qtip(")>0){return "";}
        if(strpos(" $text", "RefreshTab(")>0){return "";}
        if(strpos(" $text", "alert('")>0){return "";}
        if(strpos(" $text", "XHRConnection")>0){return "";}
        if(strpos(" $text", "document.getElementById(")>0){return "";}
        if(strpos(" $text", "LoadAjax('")>0){return "";}

        if(!isset($GLOBALS["CLASS_SOCKETS"])){
            $GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}

        if($this->language=="undefined" || $this->language==null || strlen($this->language)<2 ){
            writelogs("{warning} language = undefined !!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $this->language=$this->repairLanguage();
        }

        $keyText="$this->language$text";
        $KeyMD5="TRANSLATE_".md5($keyText);
        $memcached=new lib_memcached();
        $value=$memcached->getKey($KeyMD5);

        if($memcached->MemCachedFound){
            //writelogs($KeyMD5." $text FOUND -->$value",__FUNCTION__,__FILE__,__LINE__);
            return $value;
        }
        //writelogs($KeyMD5." $text NOT FOUND",__FUNCTION__,__FILE__,__LINE__);
        $_SESSION["detected_lang"]=$this->language;
        if(!isset($GLOBALS["translation"])){$GLOBALS["translation"]=array();}
        if(!isset($GLOBALS["translation-en"])){$GLOBALS["translation-en"]=array();}

        if(count($GLOBALS["translation"])<100){
            $GLOBALS["translation"]=$GLOBALS["CLASS_SOCKETS"]->LANGUAGE_DUMP($this->language);
        }

        $value=null;


        if(isset($GLOBALS["translation"][$text])){
            $value=$GLOBALS["translation"][$text];
        }

        if($value==null){
            if(count($GLOBALS["translation-en"])<100){
                $GLOBALS["translation-en"]=$sock->LANGUAGE_DUMP("en");
            }
            if(isset($GLOBALS["translation-en"][$text])){
                $value=$GLOBALS["translation-en"][$text];
            }
        }

        $memcached->saveKey($KeyMD5, $value,1880);
        return $value;
    }



    private function hour_to_human($xtime):string{
        $Hour=date("H",$xtime);
        $Mins=date("i",$xtime);
        $secs=date("s",$xtime);

        if("$Hour:$Mins:$secs"=="00:00:00"){return "{midnight}";}
        if("$Hour:$Mins:$secs"=="12:00:00"){return "{midday}";}


        if($secs<>"00"){
            if($Hour=="00"){
                return "{midnight} $Mins:$secs";
            }
            if($Hour=="12"){
                return "{midday} $Mins:$secs";
            }

            return "$Hour:$Mins:$secs";
        }
        if($secs=="00"){
            if($Mins=="00"){
                return "{$Hour}h";
            }
        }
        if($secs=="00"){
            if($Mins<>"00"){

                if($Hour=="00"){
                    return "{midnight} {$Mins}mn";
                }
                if($Hour=="12"){
                    return "{midday} {$Mins}mn";
                }

                return "{$Hour}h {$Mins}mn";
            }
        }


    return "";

    }
    function time_to_date($xtime,$time=false){
        if(!is_numeric($xtime)){return "-";}
        if(intval($xtime)==0){return "-";}
        $xtime=intval($xtime);

        if($time){
            if(date("Y-m-d")==date("Y-m-d",$xtime)){
                return $this->_ENGINE_parse_body($this->hour_to_human($xtime));
            }
            $yesterday=date("Y-m-d",strtotime('Yesterday'));
            if(date("Y-m-d",$xtime)==$yesterday){
                return $this->_ENGINE_parse_body("{yesterday} ".$this->hour_to_human($xtime));
            }
        }
        $dateT=date("{l} {F} d",$xtime);
        if(date("Y",$xtime)<>date("Y")) {
            $dateT = date("Y {l} {F} d", $xtime);
        }

        if($time){$dateT=$dateT." ".$this->hour_to_human($xtime);}
        if($this->language=="fr"){

            $dateT = date("{l} d {F}", $xtime);

            if(date("Y",$xtime)<>date("Y")) {
                $dateT = date("{l} d {F} Y", $xtime);
            }

            if($time){$dateT=$dateT." ".$this->hour_to_human($xtime);}
        }

        return $this->_ENGINE_parse_body($dateT);

    }

    private function replace_strings($text){
        $text=str_replace('"',"&quot;",$text);
        $text=str_replace("'","`",$text);
        $text=str_ireplace("[br][br]","[br]",$text);
        $text=str_ireplace("[br].",".[br]",$text);
        $text=str_ireplace('[code]','<code>',$text);
        $text=str_ireplace('[/code]','</code>',$text);
        $text=str_ireplace('|br]','<br>',$text);
        $text=str_ireplace('[br]','<br>',$text);
        $text=str_ireplace('[/br]','</strong><br>',$text);
        $text=str_ireplace('[\br]','</strong><br>',$text);
        $text=str_ireplace('[b]','<b>',$text);
        $text=str_ireplace('[nob]','<span style=font-weight:normal>',$text);
        $text=str_ireplace('[/nob]','</span>',$text);
        $text=str_ireplace('[/b]','</b>',$text);
        $text=str_ireplace('[ul]','<ul>',$text);
        $text=str_ireplace('[/ul]','</ul>',$text);
        $text=str_ireplace('[li]','<li>',$text);
        $text=str_ireplace('[/li]','</li>',$text);
        $text=str_ireplace('[/center]','</center>',$text);
        $text=str_ireplace('[center]','<center>',$text);
        $text=str_ireplace('[/strong]','</strong>',$text);
        $text=str_ireplace('[strong]','<strong>',$text);
        $text=str_ireplace('[/h4]','</h4>',$text);
        $text=str_ireplace('[h4]','<h4>',$text);
        $text=str_ireplace('[blk]','<blockquote>',$text);
        $text=str_ireplace('[/blk]','</blockquote>',$text);
        $text=str_ireplace('[h5]','<h5>',$text);
        $text=str_ireplace('[h5r]','<h5 style="color:#d32d2d">',$text);
        $text=str_ireplace('[/h5r]','</h5>',$text);
        $text=str_ireplace('[/h5]','</h5>',$text);
        $text=str_ireplace('[i]','<i>',$text);
        $text=str_ireplace('[/i]','</i>',$text);
        $text=str_ireplace('[/a]','</a>',$text);
        $text=str_ireplace('[/u]','</u>',$text);
        $text=str_ireplace('[b12]','<strong style="font-size:12px">',$text);
        $text=str_ireplace('[/b12]','</strong>',$text);
        $text=str_replace('-&amp;raquo;','-&raquo;',$text);
        $text=str_replace('[h3]','<H3>',$text);
        $text=str_replace('[/h3]','</H3>',$text);
        $text=str_replace('\"',"&quot;",$text);
        if(isset($_SESSION["ou"])){
            $text=str_replace('%ORGA',$_SESSION["ou"],$text);
        }


        $text=str_ireplace('%u0153','oe',$text);
        $text=str_ireplace('%u201C','&laquo;',$text);
        $text=str_ireplace('%u201D','&raquo;',$text);
        $text=str_ireplace('%u2019','`',$text);
        $text=str_replace('%C3§','&ccedil;',$text);
        $text=str_replace('%C3%C2¡','&aacute;',$text);

        $text=str_ireplace('[u]','<u>',$text);

        $text=str_ireplace('[blk]','<blockquote>',$text);
        $text=str_ireplace('[/blk]','</blockquote>',$text);

        $text=str_ireplace('<b>','<strong>',$text);
        $text=str_ireplace('</b>','</strong>',$text);
        return $text;
    }

    function _ENGINE_parse_body($content=null,$currpage=null){
        if(isset($GLOBALS["AS_ROOT"])){if($GLOBALS["AS_ROOT"]){return $content;}}
        if(!isset($GLOBALS["FIXED_LANGUAGE"])){$GLOBALS["FIXED_LANGUAGE"]=null;}
        if(is_array($content)){return ;}
        if(!isset($content)){return;}
        if(empty($content)){return;}
        if(trim($content)==null){return;}
        if(strpos(" $content", "{")==0){return $content;}
        if(strpos(" $content", "}")==0){return $content;}
        $FixedLanguage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FixedLanguage"));

        $key=md5($content);
        if(strlen($content)<500){if(isset($GLOBALS["_ENGINE_parse_body"][$key])){
            return $GLOBALS["_ENGINE_parse_body"][$key];}}

        if(isset($_COOKIE["artica-language"])){
            $this->language=$_COOKIE["artica-language"];
            $GLOBALS["FIXED_LANGUAGE"]=$this->language;
        }
        if($FixedLanguage<>null){
            $this->language=$FixedLanguage;
            $GLOBALS["FIXED_LANGUAGE"]=$FixedLanguage;
        }

        if(isset($_GET["articalang"])){
            if($_GET["articalang"]<>null){$GLOBALS["FIXED_LANGUAGE"]=$_GET["articalang"];}
            $this->language=$_GET["articalang"];
        }

        $content=str_replace("javascript:blur()", "javascript:Blurz()", $content);

        if($GLOBALS["posix_getuid"]==0){
            if($GLOBALS["VERBOSE"]){echo "[DEBUG: ".__LINE__."] posix_getuid = 0 -> abort\n" ;}
            return $content;}

        if($this->language==null){
            if($GLOBALS["FIXED_LANGUAGE"]<>null){$this->language=$GLOBALS["FIXED_LANGUAGE"];}
        }

        if($this->language==null){
            if($GLOBALS["DEBUG_TEMPLATE"]){writelogs("Language is null...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
        }

        if(is_array($content)){$content=implode(" ",$content);}

        if(preg_match_all('#{(.+?)}#is',$content,$regs)){
            foreach ($regs[1] as $num=>$val){
                $text=$this->get_cached_text($val);
                if($text<>null){
                    try {
                        $text = $this->replace_strings($text);
                    } catch (Exception $e) {
                        writelogs("Fatal Exception ". $e->getMessage,__CLASS__
                            ."/".__FUNCTION__,__FILE__,__LINE__);
                    }



                    if(preg_match_all('#\[a\s+(.+?)\]#',$text,$r)){
                        foreach ($r[1] as $a=>$b){
                            $text=str_replace($r[0][$a],"<a href=\"$b\">",$text);}
                    }
                    if(isset($_COOKIE["change-artica-name"])){
                        $text=str_ireplace("d'Artica",$_COOKIE["change-artica-name"],$text);
                        $text=str_ireplace("d`Artica",$_COOKIE["change-artica-name"],$text);
                        $text=str_ireplace("Artica",$_COOKIE["change-artica-name"],$text);
                        $text=str_ireplace("artica",$_COOKIE["change-artica-name"],$text);
                    }
                    if(isset($_SESSION["change-artica-name"])){
                        $text=str_ireplace("d'Artica",$_SESSION["change-artica-name"],$text);
                        $text=str_ireplace("d`Artica",$_SESSION["change-artica-name"],$text);
                        $text=str_ireplace("Artica",$_SESSION["change-artica-name"],$text);
                        $text=str_ireplace("artica",$_SESSION["change-artica-name"],$text);
                    }

                    $content=str_replace("{{$val}}",$text,$content);
                }
            }}




        if(preg_match_all('#\[url\](.+?)\[\/url\]#is',$content,$regs)){
            foreach ($regs[1] as $num=>$val){
                $content=str_replace($regs[0][$num],"<a href=\"$val\">$val</a>",$content);
            }

        }


        $content=str_replace("<input type='button'","<input type='button' class='button'",$content);
        $content=str_replace("reverse-porxy","Reverse-Proxy",$content);
        $content=str_replace("web sites","websites",$content);
        $content=str_replace("\`","`",$content);
        $content=str_replace('\"',"&quot;",$content);
        $content=str_replace('%C3%89',"&Eacute;",$content);
        $content=str_replace('%C3§','&ccedil;',$content);
        $content=str_ireplace('%C3§','&ccedil;',$content);
        $content=str_ireplace('â%80%99','`',$content);
        $content=str_ireplace("â%80%A2","&bull;",$content);
        $content=str_ireplace('â%80%94','&mdash;',$content);
        $content=str_ireplace('â%80%A6','&hellip;',$content);
        $content=str_ireplace('â%86%92',"&rightarrow;",$content);
        $content=str_ireplace('%E2%80%94','&mdash;',$content);
        $content=str_ireplace('%E2%86%92','&rightarrow;',$content);
        $content=str_ireplace('%E2%80%A6','&hellip;',$content);
        $content=str_ireplace('%C3%A3','&atilde;',$content);
        $content=str_ireplace('%C3£','&atilde;',$content);
        $content=str_ireplace('%C3¡','&aacute;',$content);
        $content=str_ireplace('%C3%B3','&oacute;',$content);
        $content=str_ireplace('%C3%AD','&iacute;',$content);
        $content=str_ireplace('%C3µ','&otilde;',$content);
        $content=str_ireplace('%C3%BA','&uacute;',$content);
        $content=str_ireplace('%C3%8A','&Ecirc;',$content);
        $content=str_ireplace('%C3%9A','&uacute;',$content);
        $content=str_ireplace('%C3%B1','&ntilde;',$content);
        $content=str_ireplace("%C2%BF","&iquest;",$content);
        $content=str_ireplace("%C3%C2¡","&aacute;",$content);
        $content=str_ireplace("%C3²","&ograve;",$content);
        $content=str_ireplace("%C3%AC","&igrave;",$content);
        $content=str_ireplace("%C3%A4","&auml;",$content);
        $content=str_ireplace("%C3%BB","&ucirc;",$content);
        $content=str_ireplace("%C3%BC","&uuml;",$content);
        $content=str_ireplace("%C3%B6","&ouml;",$content);
        $content=str_ireplace("%C3%9F","&szlig;",$content);
        $content=str_ireplace("%C3%8D","&iacute;",$content);
        $content=str_ireplace("%C3%84","&Auml;",$content);
        $content=str_ireplace("%C3%9C","&Uuml;",$content);
        $content=str_ireplace("%C3%88","&Egrave;",$content);
        $content=str_ireplace("%C5%BC","&#x142;",$content);
        $content=str_ireplace("%C5%82","&#x17C;",$content);
        $content=str_ireplace("%C4%87","&#x107;",$content);
        $content=str_ireplace("%C5%BA","&#x17A;",$content);
        $content=str_ireplace("%C5%9A","&#x15A;",$content);
        $content=str_ireplace("%C4%99","&#x119;",$content);
        $content=str_ireplace("%C4%85","&#x105;",$content);
        $content=str_ireplace("%C5%84","&#x144;",$content);
        $content=str_ireplace("%C5%9B","&#x15B;",$content);
        $content=str_ireplace("%C5%B9","&#x179;",$content);
        $content=str_ireplace("%C5%9A","&#x15A;",$content);
        $content=str_ireplace("%C5%A0","&Scaron;",$content);

        $content=str_ireplace("%u0142","&#x142;",$content);
        $content=str_ireplace("%u0105","&#x105;",$content);
        $content=str_ireplace("%u0107","&#x107;",$content);
        $content=str_ireplace("%u017C","&#x17C;",$content);
        $content=str_ireplace("%u0119","&#x119;",$content);
        $content=str_ireplace("%u015B","&#x15B;",$content);
        $content=str_ireplace("%u0144","&#x144;",$content);
        $content=str_ireplace("%C3%80","&Agrave;",$content);

        
        //$content=str_replace("Loadjs(",'$.getScript(',$content);
        if(strlen($content)<500){$GLOBALS["_ENGINE_parse_body"][$key]=$content;}
        return $content;
    }

    function _readfile($filepath){
        if(isset($GLOBALS[md5($filepath)])){return $GLOBALS[md5($filepath)];}
        $file = @fopen($filepath, "r");
        if ($file<>null){
            $content=@fread($file,filesize($filepath));
            fclose($file);
            $GLOBALS[md5($filepath)]=$content;
            return $content;
        }else{
            if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}::".__FUNCTION__." Error while open $filepath  in " . basename(__FILE__). " line ".__LINE__);}
        }

    }


    function BuildMenusBarr($menus){}
    function Build_high_menus(){}
    function Body($content=null){}

    function _get_env_var($Var){
        if(empty($GLOBALS[$Var])) {
            if (!empty($GLOBALS['_SERVER'][$Var])) {
                $GLOBALS[$Var] = $GLOBALS['_SERVER'][$Var];
                return true;
            }
            if (!empty($GLOBALS['HTTP_SERVER_VARS'][$Var])) {
                $GLOBALS[$Var] = $GLOBALS['HTTP_SERVER_VARS'][$Var];
                return true;
            }
        }
        return false;
    }


    function _detect_lang_forced(){
        if(isset($_SESSION["DetectedLanguage"])){
            $this->language=$_SESSION["DetectedLanguage"];
            return $_SESSION["DetectedLanguage"];
        }
        if(!class_exists("articaLang")){include_once(dirname(__FILE__)."/class.langages.inc");}
        $langAutodetect=new articaLang();
        $DetectedLanguage=$langAutodetect->get_languages();

        $langfile=dirname(__FILE__)  ."/language/{$_SESSION["detected_lang"]}.db";
        if(!is_file($langfile)){$DetectedLanguage="en";}


        $this->language=$DetectedLanguage;
        return $DetectedLanguage;
    }

    function _detect_lang(){
        if(!$GLOBALS["AS_ROOT"]){
            $currentPageName=CurrentPageName();
            if($currentPageName=="exec.squidguard.php"){$this->_detect_lang_forced();}
        }
        if(!isset($GLOBALS["CURRENT_PAGE"])){$GLOBALS["CURRENT_PAGE"]=CurrentPageName();}
        if(isset($_GET["articalang"])){
            if($_GET["articalang"]<>null){$GLOBALS["FIXED_LANGUAGE"]=$_GET["articalang"];}
            $this->language=$_GET["articalang"];
            $_SESSION["detected_lang"]=$_GET["articalang"];
            setcookie("artica-language",$_GET["articalang"],time()+1000000);
        }

        $uid=null;
        $CURRENT_PAGE=null;
        $detected_lang=null;
        if(isset($_SESSION["uid"])){$uid=$_SESSION["uid"];}
        if(isset($_SESSION["detected_lang"])){	$detected_lang=$_SESSION["detected_lang"];}
        if(isset($GLOBALS["CURRENT_PAGE"])){$CURRENT_PAGE=$GLOBALS["CURRENT_PAGE"];}
        $logprefix="[$uid]:::LANG:[{$detected_lang}]::".__FUNCTION__."::";

        if(!isset($_COOKIE["artica-language"])){$_COOKIE["artica-language"]=null;}

        if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
        if($GLOBALS["EXECUTED_AS_ROOT"]){return;}

        if(!isset($GLOBALS["FixedLanguage"])){$GLOBALS["FixedLanguage"]=$sock->GET_INFO("FixedLanguage"); }
        if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong>FixedLanguage {$GLOBALS["FixedLanguage"]}</strong><br>\n";}
        if(trim($GLOBALS["FixedLanguage"])<>null){return $GLOBALS["FixedLanguage"];}
        if($GLOBALS["DEBUG_LANG"]){VERBOSE("$logprefix artica-language {$_COOKIE["artica-language"]}</strong>",__LINE__);}
        if(isset($_COOKIE["artica-language"])){return $_COOKIE["artica-language"];}

        if($GLOBALS["DEBUG_TEMPLATE"]){VERBOSE("$logprefix => language: _COOKIE=`{$_COOKIE["artica-language"]}` function:".__FUNCTION__." in " . basename(__FILE__),__LINE__);}
        if(!isset($_SESSION["detected_lang"])){$_SESSION["detected_lang"]=null;}



        if($_SESSION["detected_lang"]<>null){
            $this->language=$_SESSION["detected_lang"];
            if($GLOBALS["DEBUG_TEMPLATE"]){error_log("return _SESSION[detected_lang] {$_SESSION["detected_lang"]} function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
            return $_SESSION["detected_lang"];
        }
        if($_COOKIE["artica-language"]<>null){
            if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::return _COOKIE[artica-language] {$_COOKIE["artica-language"]} function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
            $this->language=$_COOKIE["artica-language"];
            return $_COOKIE["artica-language"];
        }


        if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong>language $this->language</strong><br>\n";}
        if($this->language==null){
            if(!class_exists("articaLang")){include_once(dirname(__FILE__)."/class.langages.inc");}
            if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::this->language is null function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
            if(!$GLOBALS["EXECUTED_AS_ROOT"]){
                if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
                $disk_session_language=$sock->GET_INFO("session_language");
                if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::disk_session_language return `$disk_session_language` function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
                if($disk_session_language==null){
                    $langAutodetect=new articaLang();
                    $DetectedLanguage=$langAutodetect->get_languages();
                    if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::->get_languages() return `$DetectedLanguage` function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
                    if($DetectedLanguage<>null){$disk_session_language=$DetectedLanguage;}
                }

                if($disk_session_language==null){
                    if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::-disk_session_language return NULL, force to EN LANG function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
                    $disk_session_language="en";
                }
                $this->language=$disk_session_language;
                return $disk_session_language;
            }}


        if(isset($_SESSION["detected_lang"])){
            $langfile=dirname(__FILE__)  ."/language/{$_SESSION["detected_lang"]}.db";
            if(!is_file($langfile)){
                if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::LANGUAGE:Unable to stat $langfile, return EN  has default language function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
                writelogs("LANGUAGE:Unable to stat $langfile, return en language has default",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                return "en";}else{return $_SESSION["detected_lang"];
            }
        }


        if(isset($_SESSION["OU_LANG"])){
            if(trim($_SESSION["OU_LANG"])<>null){
                if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}::".__FUNCTION__." OU_LANG=`{$_SESSION["OU_LANG"]}` function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
                $_SESSION["detected_lang"]=$_SESSION["OU_LANG"];
                return $_SESSION["OU_LANG"];
            }
        }
        if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::LANGUAGE:Unable to define language.... function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}


        include_once(dirname(__FILE__)."/class.langages.inc");
        $lang=new articaLang();
        $_SESSION["detected_lang"]=$lang->get_languages();
        if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]:::LANG:[{$_SESSION["detected_lang"]}]::LANGUAGE: lang->get_languages() return {$_SESSION["detected_lang"]} function:".__FUNCTION__." in " . basename(__FILE__). " line ".__LINE__);}
        writelogs("LANGUAGE: lang->get_languages() return {$_SESSION["detected_lang"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        setcookie("artica-language", $_SESSION["detected_lang"], time()+172800);
        if(strlen($_SESSION["detected_lang"])>1){return $_SESSION["detected_lang"];}

        // Detect HTTP_ACCEPT_LANGUAGE & HTTP_USER_AGENT.
        $this->_get_env_var('HTTP_ACCEPT_LANGUAGE');
        $this->_get_env_var('HTTP_USER_AGENT');

        $_AL=strtolower($GLOBALS['HTTP_ACCEPT_LANGUAGE']);
        $_UA=strtolower($GLOBALS['HTTP_USER_AGENT']);

        // Try to detect Primary language if several languages are accepted.
        if(!is_array($GLOBALS['_LANG'])){
            writelogs("LANGUAGE: GLOBALS['_LANG'] is not an array, return en",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return 'en';
        }
        foreach($GLOBALS['_LANG'] as $K){
            if(strpos($_AL, $K) === 0){
                if (!is_dir(dirname(__FILE__)  ."/language/$K")){return "en";}
                return $K;}
        }

        // Try to detect any language if not yet detected.
        foreach($GLOBALS['_LANG'] as $K){
            if(strpos($_AL, $K) !== false){
                if (!is_dir(dirname(__FILE__)  ."/language/$K")){return "en";}
                return $K;}
        }
        foreach($GLOBALS['_LANG'] as $K){
            if(preg_match("/[\[\( ]{$K}[;,_\-\)]/",$_UA)){
                if (!is_dir(dirname(__FILE__)  ."/language/$K")){return "en";}
                return $K;}
        }

        if (!is_dir(dirname(__FILE__)  ."/language/{$GLOBALS['_DLANG']}")){return "en";}
        return $GLOBALS['_DLANG'];
    }





}
function writequeries($function=null,$file=null){
    writelogs($_SERVER['REMOTE_ADDR'] . "->" . CurrentPageName() . "?{$_SERVER['QUERY_STRING']}",__FUNCTION__,__FILE__);


}
function Field_hidden($name,$value){
    $GLOBALS["AUTO_FORM"][$name]="text";
    return "<input type='hidden' name='$name' id='$name' value=\"$value\">";}
function help_icon($help,$no_float=false,$additional_langfile=null){
    return "<H1>DEPRECIATED</h1>";

}
function Field_button_table($name,$size=22){
    if($GLOBALS["AUTO_FORM_LOCK"]==true){return null;}
    $t=time();
    $GLOBALS["AUTO_FORM_FUNC"]="Save$t";
    return "<tr><td colspan=2 style='text-align:right'><hr>".button($name,"Save$t()",$size)."</td></tr>";
}
function imgsimple($img,$tooltip=null,$js=null,$align=null,$imgid=null){
    $size=null;
    if(preg_match("#-48\.#", $img)){$size=";width:48px;max-width:48px;";}
    if(preg_match("#-64\.#", $img)){$size=";width:64px;max-width:64px;";}
    if(preg_match("#-32\.#", $img)){$size=";width:32px;max-width:32px;";}
    if(preg_match("#-24\.#", $img)){$size=";width:24px;max-width:24px;";}
    if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}
    if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}
    $md5=md5("$img$tooltip$js$align$imgid".time());
    if($imgid<>null){$id="id=\"$imgid\"";}else{$id=null;}
    $image_path="img/$img";
    if(preg_match('#images\.listener\.php#',$img)){$image_path=$img;}
    if(preg_match('#^ressources#',$img)){$image_path=$img;}
    if(preg_match('#img\/(.+)#',$img,$re)){$image_path=$img;}
    if(is_file($img)){$image_path=$img;}
    if($js<>null){$js="OnClick=\"javascript:$js;\"";}
    if($js==null){$js="OnClick=\"javascript:Blurz();\"";}
    $IMAGE="<a href=\"javascript:Blurz();\" $js><img src=\"$image_path\" style=\"border:0px;$size\" $id></a>";
    return $IMAGE;



}
function string_to_regex($pattern){
    if(trim($pattern)==null){return null;}
    $pattern=str_replace("/", "\/", $pattern);
    $pattern=str_replace(".", "\.", $pattern);
    //$pattern=str_replace("-", "\-", $pattern);
    $pattern=str_replace("[", "\[", $pattern);
    $pattern=str_replace("]", "\]", $pattern);
    $pattern=str_replace("(", "\(", $pattern);
    $pattern=str_replace(")", "\)", $pattern);
    $pattern=str_replace("$", "\$", $pattern);
    $pattern=str_replace("?", "\?", $pattern);
    $pattern=str_replace("#", "\#", $pattern);
    $pattern=str_replace("{", "\{", $pattern);
    $pattern=str_replace("}", "\}", $pattern);
    $pattern=str_replace("^", "\^", $pattern);
    $pattern=str_replace("!", "\!", $pattern);
    $pattern=str_replace("+", "\+", $pattern);
    $pattern=str_replace("*", ".*?", $pattern);
    $pattern=str_replace("|", "\|", $pattern);
    return $pattern;

}
function string_to_sql_search($pattern,$STRICT=false){
    $pattern=trim($pattern);
    if(is_numeric($pattern)){return $pattern;}
    if(!$STRICT){$pattern="*$pattern*";}
    $pattern=str_replace("**", "*", $pattern);
    $pattern=str_replace("**", "*", $pattern);
    $pattern=str_replace("*", "%", $pattern);
    return $pattern;
}




function PostFixTimeToPhp($PostFixDate){
    $months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");
    if(preg_match('#(\w+),\s+([0-9]+)\s+(\w+)\s+([0-9]+)\s+([0-9]+):([0-9]+):([0-9]+)#',$PostFixDate,$ar)){
        if(strlen($ar[2])==1){$ar[2]="0{$ar[2]}";}
        $date=$ar[2] . "/" . $months[$ar[3]] . "/" . $ar[4];
        return $date. ' ' . $ar[5].":".$ar[6];

    }
    if(preg_match('#(\w+)\s+(\w+)\s+([0-9]+)\s+([0-9\:]+)\s+([0-9]+)#',$PostFixDate,$ar)){
        if(strlen($ar[3])==1){$ar[3]="0{$ar[3]}";}
        $date=$ar[3] . '/' . $months[$ar[2]] . '/' . $ar[5] . ' ' . $ar[4];
        return $date;
    }
    //

    return $PostFixDate;

}
function PasswordGenerator(){

    $totalChar = 7; // number of chars in the password
    $salt = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789";  // salt to select chars from
    srand((double)microtime()*1000000); // start the random generator
    $password=""; // set the inital variable
    for ($i=0;$i<$totalChar;$i++)  // loop and create password
        $password = $password . substr ($salt, rand() % strlen($salt), 1);
// *************************
// Display Password
// *************************
    return $password;

}

function icon_help($help,$nofloat=0){
    $f="float:right";
    $page=CurrentPageName();
    $text="{" . $help . "}<br>{click_for_help}";
    if($nofloat==1){$f=null;}
    $id=md5($help);
    $img="
	<input type='hidden' id='Text_$id' value='$text<br><hr><div style=text-align:right><a href=\"javascript:Blurz();\" OnClick=javascript($(\"$id\").qtip(\"toggle\", false); style=\"font-size:11px\">{close}</a></div>'>
	<div style='$f;width:1%;margin-top:-5px;'>
		<img src='img/help.png' OnClick=\"javascript:LoadHelp('$help','$page');\" id='$id'>
	</div>
	<script>
	function qtyp_{$id}(){
			$(document).ready(function(){		
				$('#$id').qtip({
		  			content: $('#Text_$id').val(),
		  			show: 'mouseover',
		   			hide: 'mouseout',
		   			style: 'cream',
		   			hide: {event: 'click mouseleave',inactive: 3000},
		   			tip: true
					});	
		});
	}
	qtyp_{$id}();
	</script>\n";
    return "$img&nbsp;";

}
function texttooltip($text,$tooltip=null,$js=null,$dbclk=null,$NOHREF=0,$style=null,$forcejs=0){


    if(preg_match("#^fw\.#", CurrentPageName())){
        $tpl=new templates();
        if($js<>null){$js="OnClick=\"javascript:$js;\"";}
        $explain=$tpl->_ENGINE_parse_body($tooltip);
        if(!is_null($explain)) {
            $explain = str_replace('"', "`", $explain);
        }
        $id=md5($text.$explain.$js);
        $zlabel[]="<a href=\"javascript:blur();\" $js id='$id'";
        $zlabel[]="data-toggle=\"popover\"";
        $zlabel[]="data-placement=\"auto left\"";
        $zlabel[]="data-content=\"$explain\"";
        $zlabel[]="style=\"color: #7C7777;font-size: 14px;font-weight: bold; text-decoration:underline;text-decoration-style:dotted;\"";
        $zlabel[]=">$text</span>
		<script>$('#$id').popover({trigger: 'hover', html: true,});</script>";
        return @implode(" ", $zlabel);


    }


    $lang=null;
    $DisableToolTips=0;
    $curs="OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='auto'\"";

    if(preg_match("#SwitchPassword#",$js)){$float="float:right;"; }
    $tooltip=str_replace("'", "\"", $tooltip);


    if($tooltip==null){
        if($js<>null){$js="OnClick=\"javascript:$js;\"";}
        return "<span style='background-image: linear-gradient(to right, black 33%, rgba(255,255,255,0) 0%);
		background-position: bottom;
		background-size: 3px 1px;
		background-repeat: repeat-x;$style' $curs $js >$text</span>";

    }


    if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}
    $tpl=new templates();
    $tooltip_pos=null;
    if(preg_match("#^position:(.+?):(.+)#",$tooltip,$re)){
        $tooltip_pos="position:'{$re[1]}',";
        $tooltip=$re[2];
    }

    $tooltipencode=base64_encode($tooltip);
    $float=null;
    $n = rand(time(), time()*10);
    $rand=base_convert($n, 10, 36);
    if(preg_match("#javascript:(.+)#", $js,$re)){$js=$re[1];}



    $filecache=null;
    if($tooltip==null){$tooltip="&nbsp;";}
    $tooltip=str_replace("'", "`", $tooltip);
    $tooltip=str_replace('"', "`", $tooltip);
    $md5=md5("$text$tooltip$js$n".time());
    if($js<>null){$js="OnClick=\"javascript:$js;\"";}


    if(isset($_SESSION["detected_lang"])){
        $lang=$_SESSION["detected_lang"];

    }
    if($lang==null){
        if(isset($_COOKIE["artica-language"])){
            $lang= $_COOKIE["artica-language"];
        }
    }

    $time=microtime();


    $md5=md5($tooltipencode.$lang.$time);
    if(!is_file(PROGRESS_DIR."/help/$md5")){
        $tpl=new templates();
        $content=$tpl->_ENGINE_parse_body($tooltip);

        @file_put_contents(PROGRESS_DIR."/help/$md5",$content );
    }else{
        $content=@file_get_contents(PROGRESS_DIR."/help/$md5");
    }

    $content=str_replace("\n", "", $content);
    $content=htmlspecialchars($content);

    return "<span 
		title='$content'
		style='background-image: linear-gradient(to right, black 33%, rgba(255,255,255,0) 0%);
		background-position: bottom;
		background-size: 3px 1px;
		background-repeat: repeat-x;$style' $js id='$md5' $curs>$text</span>
		<script>
		$('#$md5').tooltipster({
			$tooltip_pos
			contentAsHTML: true,
			onlyOne:true,
			functionReady: function(){
				$('#$md5').attr('aria-hidden', false);
			},
			functionAfter: function(){
				$('#$md5').attr('aria-hidden', true);
			}
		});
		</script>";



}
function ParseTooltip($tooltip){
    if($GLOBALS["EXECUTED_AS_ROOT"]){return $tooltip;}
    if($tooltip<>null){
        $tpl=new templates();
        $tooltip=$tpl->_ENGINE_parse_body($tooltip);
        $tooltip=str_replace("\n",'',$tooltip);
        $tooltip=str_replace("\r",'',$tooltip);
        $tooltip=str_replace("\r\n"," ",$tooltip);
        $tooltip=str_replace("'","`",$tooltip);
        $tooltip=trim($tooltip);

    }$tooltip=ASCII_TO_HTML($tooltip);return $tooltip;
}
function Field_yesno_checkbox_img($name,$value,$tooltip=null){
    $value=strtolower($value);
    if($tooltip==null){$tooltip='{click_enable_disable}';}
    $tooltip=ParseTooltip($tooltip);
    if($value==null){$value="no";}
    if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this, 100);\" OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
    if($value=='yes'){$img='img/status_ok.png';}else{$img='img/status_critical.png';}
    $html="
	<input type='hidden' name='$name' id='$name' value='$value'><a href=\"javascript:SwitchOnOff('$name');\"><img src='$img' id='img_$name' $tooltip></a>";
    return $html;

}
function Field_onoff_checkbox_img($name,$value,$tooltip=null){
    $value=strtolower($value);
    if($tooltip==null){$tooltip='{click_enable_disable}';}
    $tooltip=ParseTooltip($tooltip);
    if($value==null){$value="off";}
    if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this, 100);\" OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}
    if($value=='on'){$img='img/status_ok.png';}else{$img='img/status_critical.png';}
    $html="
	<input type='hidden' name='$name' id='$name' value='$value'><a href=\"javascript:SwitchOnOff_on('$name');\"><img src='$img' id='img_$name' $tooltip></a>";
    return $html;

}
function Field_numeric_checkbox_img($name,$value,$tooltip=null,$OnClick=null){

    if($tooltip==null){$tooltip='{click_enable_disable}';}
    $tooltip=ParseTooltip($tooltip);
    if($value==null){$value="0";}
    if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');this.style.cursor='pointer'\" OnMouseOut=\"javascript:HideBulle();this.style.cursor='default'\"";}

    if($OnClick<>null){$click="$OnClick('$name');";}

    if($value=='1'){$img='img/status_ok.png';}else{$img='img/status_critical.png';}
    $html="
	<div OnClick=\"javascript:SwitchNumeric('$name');{$click}\" $tooltip style='width:22px;height:22px'>
		<input type='hidden' name='$name' id='$name' value='$value'>
		<img src='$img' id='img_$name' >
	</div>
	";
    return $html;

}

function Field_TRUEFALSE_checkbox_img($name,$value,$tooltip=null){
    if(is_bool($value)) {
        if ($value) {
            $value = 'TRUE';
        }else{
            $value = 'FALSE';
        }
    }
    if(is_numeric($value)){
        if ($value==1) {
            $value = 'TRUE';
        }else{
            $value = 'FALSE';
        }
    }

    if(is_null($value)){$value="FALSE";}
    if($tooltip==null){$tooltip='{click_enable_disable}';}
    $tooltip=ParseTooltip($tooltip);
    $value=strtoupper($value);
    if($value=='TRUE'){$img='img/status_ok.png';}
    if($value=='FALSE'){$img='img/status_critical.png';}
    if($tooltip<>null){$tooltip="onMouseOver=\"javascript:AffBulle('$tooltip');lightup(this, 100);\" OnMouseOut=\"javascript:HideBulle();lightup(this, 50);\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;border:0px;\"";}


    $html="
	<input type='hidden' name='$name' id='$name' value='$value'><a href=\"javascript:SwitchTRUEFALSE('$name');\" ><img src=\"$img\" id='img_$name' $tooltip></a>";
    return $html;

}
function array_move_element_flat_moveUp($input,$index) {
    $new_array = $input;

    if((count($new_array)>$index) && ($index>0)){
        array_splice($new_array, $index-1, 0, $input[$index]);
        array_splice($new_array, $index+1, 1);
    }

    return $new_array;
}
function array_move_element_flat_moveDown($input,$index) {
    $new_array = $input;

    if(count($new_array)>$index) {
        array_splice($new_array, $index+2, 0, $input[$index]);
        array_splice($new_array, $index, 1);
    }

    return $new_array;
}
function array_move_element_flat($array,$index,$direction='up'){
    if($direction=="up"){
        return 	array_move_element_flat_moveUp($array,$index);
    }else{
        return 	array_move_element_flat_moveDown($array,$index);
    }
}

function element_rollover($js){
    $js=str_replace("javascript:",'',$js);
    $html="
	OnMouseOver=\"this.style.cursor='pointer';this.style.backgroundImage='url(img/bg_jaune-deg.png)';this.style.backgroundRepeat='repeat-y'\"
	OnMouseOut=\"this.style.cursor='auto';this.style.backgroundImage='';\"
	OnClick=\"javascript:$js\"";

    return $html;

}

function is_array_key($key,$array){
    $key=strtolower($key);
    $r=array_change_key_case($array,CASE_LOWER);
    if(array_key_exists($key,$r)){return true;}return false;
}
function  _FileExt($ext){
    if(is_file("img/file_ico/$ext.gif")){ return "img/file_ico/$ext.gif";}
    if(is_file("img/file_ico/$ext.png")){ return "img/file_ico/$ext.png";}
}
function get_img_ext($value){
    if(preg_match('#\.([a-zA-Z0-9]+)$#',$value,$reg)){
        $ext=$reg[1];
        $img_path=_FileExt($ext);
        if($img_path==null){$img_path="img/file_ico/unknown.gif";}
        return "<img src=\"$img_path\">";

    }else{
        return '<img src="img/file_ico/unknown.gif">';
    }
}
function Get_extension($filename){
    $parts = explode('.',$filename);
    $last = count($parts) - 1;
    $ext = $parts[$last];
    return $ext;
}
function replace_accents($s) {
    if(!class_exists("htmltools_inc")){include_once(dirname(__FILE__)."/class.html.tools.inc");}
    $t=new htmltools_inc();
    return $t->replace_accents($s);
}
function CleanEscapes($html){
    $html=str_replace("\n",'',$html);
    $html=str_replace("\t",'',$html);
    $html=str_replace("\r",'',$html);
    return $html;
}
function my_mkdir($dir, $chmod = 0777){
    if (!empty($dir)) {
        $array = explode('/', $dir);
        $base_dir = '';

        foreach ($array as $key => $value) {
            if ($key){
                $base_dir .= '/' . $value;
                $base_dir=str_replace("\\","",$base_dir);
                //WriteLogs("Fichiers:my_mkdir:base_dir=$base_dir");
            }
            else{
                $base_dir .= $value;
            }


            if (!file_exists($base_dir) && !empty($value))
                if (!mkdir($base_dir, $chmod))
                    echo 'Unable to create folder "' . $base_dir . '"<br />'; }

    }
}
function hide_ressources($pattern){
    if(preg_match("#smb://(.+?):(.+?)@#",$pattern,$re)){
        return str_replace($re[2],'*****',$pattern);
    }
    return $pattern;
}
function ip2cidr($ip_start,$ip_end) {
    if(long2ip(ip2long($ip_start))!=$ip_start or long2ip(ip2long($ip_end))!=$ip_end) return NULL;
    $ipl_start=(int)ip2long($ip_start);
    $ipl_end=(int)ip2long($ip_end);
    if($ipl_start>0 && $ipl_end<0) $delta=($ipl_end+4294967296)-$ipl_start;
    else $delta=$ipl_end-$ipl_start;
    $netmask=str_pad(decbin($delta),32,"0","STR_PAD_LEFT");
    if(ip2long($ip_start)==0 && substr_count($netmask,"1")==32) return "0.0.0.0/0";
    if($delta<0 or ($delta>0 && $delta%2==0)) return NULL;
    for($mask=0;$mask<32;$mask++) if($netmask[$mask]==1) break;
    if(substr_count($netmask,"0")!=$mask) return NULL;
    return "$ip_start/$mask";
}
function ae_detect_ie(){
    //return false;
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)){return true;}


}
function TinyMce($FiledName,$datas,$nojs=false,$csscontent=null){

    $js="<script language=\"javascript\" type=\"text/javascript\" src=\"js/tiny_mce/tinymce.min.js\"></script>";
    if($nojs){$js=null;}
    if($csscontent<>null){

        @file_put_contents(PROGRESS_DIR."/$FiledName.css", $csscontent);
        $contentcss="content_css : '/ressources/logs/web/$FiledName.css', ";
    }

    $html="
<textarea id=\"$FiledName\" name=\"$FiledName\" rows=\"15\" cols=\"80\" style=\"width: 100%\">$datas</textarea>		
$js
<script language=\"javascript\" type=\"text/javascript\">
tinymce.baseURL = \"/js/tiny_mce\";
	tinymce.init({
		selector: \"textarea\",
		mode : \"textareas\",
		plugins : \"pagebreak,layer,table,save,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,template code\",
		theme_advanced_buttons1 : \"newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect\",
		theme_advanced_buttons2 : \"cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor\",
		theme_advanced_buttons3 : \"tablecontrols,|,hr,removeformat,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen\",
		theme_advanced_buttons4 : \"insertlayer,moveforward,movebackward,absolute,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak\",
		theme_advanced_toolbar_location : \"top\",
		theme_advanced_toolbar_align : \"left\",
		theme_advanced_statusbar_location : \"bottom\",
		theme_advanced_resizing : true,
		$contentcss
	});
</script>
";
    return $html;
}
function applysettings($module_name,$js=null){
    return "<H1>DEPRECIATED</H1>";

}
function applysettings_postfix($float=false){

    return Paragraphe('system-64.png','{apply config}','{APPLY_SETTINGS_POSTFIX}','javascript:Default_ApplyConfigPostfix()','APPLY_SETTINGS_POSTFIX',210,null,0,$float);
}
function iframe($content,$refresh=20,$bodywidth=null){
    if($bodywidth<>null){$bodywidth="width:$bodywidth";}else{$bodywidth="width:auto";}
    if($refresh>0){$refresh="<META HTTP-EQUIV='REFRESH' CONTENT='$refresh'>";}else{$refresh=null;}
    $tpl=new templates();

    $html="<html style='$bodywidth;margin:0px;padding:0px'>
		
		<head>$tpl->head
			<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
			<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
			<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
			<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />
			$refresh
		</head><body style='margin:0px;padding:0px;background:transparent;$bodywidth'>$content</body></html>";

    return  $html;

}
function BuildSessionAuth(){
    $debug=false;
    $page=CurrentPageName();

    if($page=="miniadm.index.php"){
        if($GLOBALS["VERBOSE"]){
            $debug=true;
            if($GLOBALS["VERBOSE"]){echo "Page:$page -> DEBUG\n";}
        }
    }
    error_log("[{$_SESSION["uid"]}]::BuildSessionAuth.. in".__FUNCTION__." line ".__LINE__);
    if(!isset($_SERVER['PHP_AUTH_USER'])){
        if($debug){echo "<li>Verify ->PHP_AUTH_USER'] -> FALSE</li>\n";}
        return false;
    }
    if(!function_exists('ldap_connect')){
        if($debug){echo "<li>ldap_connect -> FALSE</li>\n";}
        return;

    }
    $ldap=new clladp();
    if($debug){echo "<li>Verify ->ldap->UserFromAuth({$_SERVER['PHP_AUTH_USER']}) line:".__LINE__."</li>\n";}
    $GLOBALS["UserFromAuth_debug"]=true;

    if($_SESSION["uid"]=="-100"){
        return;

    }

    if($ldap->IsKerbAuth()){
        BuildSession($_SERVER['PHP_AUTH_USER']);
        return;
    }

    $hash=$ldap->UserFromAuth($_SERVER['PHP_AUTH_USER']);

    if($hash[0]["uid"][0]==null){$hash[0]["uid"][0]=$hash[0]["cn"][0];}
    $uid=$hash[0]["uid"][0];

    if($debug){echo "<li>uid:$uid line:".__LINE__."</li>\n";}

    if(trim(strtolower($uid))==trim(strtolower($ldap->ldap_admin))){
        if($_SERVER["PHP_AUTH_PW"]==$ldap->ldap_password){
            $uid="-100";
            $_SESSION["passwd"]=$_SERVER["PHP_AUTH_PW"];
        }
    }
    if(trim($uid)<>null){
        if($debug){echo "<li>uid -> $uid</li>\n";}
        $_SESSION["uid"]=$uid;
        $_SESSION["passwd"]=$_SERVER["PHP_AUTH_PW"];
        return true;
    }

}
function BuildSession($uid,$nominiadm=false){
    $DebugWebConsoleAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugWebConsoleAuth"));

    if($DebugWebConsoleAuth==1) {
        writelogs("Building session for [$uid]", __FUNCTION__, __FILE__, __LINE__);
    }
    if(isset($GLOBALS[__FILE__][__FUNCTION__])){return;}
    $GLOBALS[__FILE__][__FUNCTION__]=true;
    if($uid==null){
        if($GLOBALS["VERBOSE"]){
            echo "<strong>BuildSession() UID is null, aborting...</strong><br>\n";
            return;
        }
    }
    if(!isset($_SESSION["VirtAclUser"])){$_SESSION["VirtAclUser"]=false;}

    if($_SESSION["VirtAclUser"]){return;}
    if(!class_exists("user")){include_once(dirname(__FILE__)."/class.user.inc");}
    if($GLOBALS["VERBOSE"]){echo "<strong>BuildSession() -> `$uid`</strong><br>\n";}
    $ldap=new clladp();
    $users=new usersMenus();
    $privs=new privileges($uid);

    $u=new user($uid);
    $sock=new sockets();
    if(!class_exists("articaLang")){include_once(dirname(__FILE__)."/class.langages.inc");}
    if(isset($GLOBALS["FixedLanguage"])){$GLOBALS["FixedLanguage"]=$sock->GET_INFO("FixedLanguage");}
    $privileges_array=$privs->privs;
    if(isset($_POST["artica_username"])) {
        setcookie("mem-logon-user", $_POST["artica_username"], time() + 172800);
    }
    $_SESSION["privileges_array"]=$privs->privs;
    foreach ($privs->privs as $key=>$val){
        if($DebugWebConsoleAuth==1) {
            writelogs("Privilege: $key = $val", __FUNCTION__, __FILE__,__LINE__);
        }
    }
    if(!isset($privileges_array["ForceLanguageUsers"])){
        $privileges_array["ForceLanguageUsers"]=null;
    }
    $_SESSION["privs"]=$privileges_array;
    $_SESSION["OU_LANG"]=$privileges_array["ForceLanguageUsers"];
    $_SESSION["uid"]=$uid;
    if(isset($_POST["artica_password"])){
        $_SESSION["passwd"]=$_POST["artica_password"];
    }

    $_SESSION["groupid"]=$ldap->UserGetGroups($uid,1);
    $_SESSION["DotClearUserEnabled"]=$u->DotClearUserEnabled;
    $_SESSION["MailboxActive"]=$u->MailboxActive;
    if(!$nominiadm){$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";}
    if(!is_null($u->UsersInterfaceDatas)){$u->UsersInterfaceDatas=trim($u->UsersInterfaceDatas);}
    $_SESSION["UsersInterfaceDatas"]=$u->UsersInterfaceDatas;
    $_SESSION["CORP"]=$users->CORP_LICENSE;
    $_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
    if(!isset($_SESSION["SESSARTSTART"])) {$_SESSION["SESSARTSTART"] = time();}

    $_SESSION["DotClearUserEnabled"]=$u->DotClearUserEnabled;
    $_SESSION["MailboxActive"]=$u->MailboxActive;
    if(!$nominiadm){$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";}
    $_SESSION["ou"]=$u->ou;
    $_SESSION["AsWebStatisticsAdministrator"]=$users->AsWebStatisticsAdministrator;
    if($_SESSION["OU_LANG"]==null){
        if(!isset($GLOBALS["FixedLanguage"])){$GLOBALS["FixedLanguage"]=null;}
        if($GLOBALS["FixedLanguage"]<>null){
            $_SESSION["OU_LANG"]=$GLOBALS["FixedLanguage"];
        }
    }
    if(!isset($_POST["artica_username"])){$_POST["artica_username"]=null;}
    $lang=new articaLang();
    writelogs("Default organization language={$_SESSION["OU_LANG"]}",__FUNCTION__,__FILE__);
    if(!isset($_SESSION["OU_LANG"])){$_SESSION["OU_LANG"]="";}
    if(is_null($_SESSION["OU_LANG"])){$_SESSION["OU_LANG"]="";}

    if(trim($_SESSION["OU_LANG"])<>null){
        $_SESSION["detected_lang"]=$_SESSION["OU_LANG"];
        setcookie("artica-language", $_SESSION["OU_LANG"], time()+172800);
    }else{
        if(isset($_POST["lang"])) {
            setcookie("artica-language", $_POST["lang"], time() + 172800);
        }
        $_SESSION["detected_lang"]=$lang->get_languages();
    }

    writelogs("----------------------- Building session done -----------------------",__FUNCTION__,__FILE__,__LINE__);

}
function noback(){

    $noback["squid.hostspot.emergency.disable.progress.php"]=true;
    $noback["squid.hostspot.emergency.enable.progress.php"]=true;
    $noback["squid.hostspot.restart.web.progress.php"]=true;
    $noback["squid.webauth.maintenance.php"]=true;
    $noback["itcharter.php"]=true;
    $noback["ufdbguard.php"]=true;
    $noback["public.logon.php"]=true;
    $noback["squid.categories.url.export.php"]=true;
    $noback["squid.categories.export.php"]=true;
    $noback["squid.categories.urls.php"]=true;
    $noback["squid.categories.php"]=true;
    $noback["admin.top.menus.php"]=true;
    $noback["TestsClasses.php"]=true;
    $noback["angular.css.php"]=true;
    $noback["logon.php"]=true;
    $noback["squid.urgency.php"]=true;
    $noback["404.php"]=true;
    $noback["auto-account.php"]=true;
    $noback["exec.scan-networks.php"]=true;
    $noback["exec.vacationtime.php"]=true;
    $noback["dansguardian.users.index.php"]=true;
    $noback["cyrus.murder.listener.php"]=true;
    $noback["haproxy.listener.php"]=true;
    $noback["cluster.listener.php"]=true;
    $noback["exec.squidguard.php"]=true;
    $noback["ufdbguardd.php"]=true;
    $noback["proxy.pac.php"]=true;
    $noback["logon.form.php"]=true;
    $noback["squid.logon.php"]=true;
    $noback["miniadm.php"]=true;
    $noback["squid.access-sql.php"]=true;
    $noback["exec.shm.php"]=true;
    $noback["change.title.php"]=true;
    $noback["Inotify.php"]=true;
    $noback["squid.stats.listener.php"]=true;
    $noback["squid.blocks.listener.php"]=true;
    $noback["import.users.listener.php"]=true;
    $noback["external_acl_quota.php"]=true;
    $noback["nodes.listener.php"]=true;
    $noback["streamget.php"]=true;
    $noback["proxy-certificate.php"]=true;
    $noback["proxy-post-aliases.php"]=true;
    $noback["proxy-certificate-infos.php"]=true;
    $noback["squid.users.logon.php"]=true;
    $noback["squid-helper.php"]=true;
    $noback["exec.articaget.php"]=true;
    $noback["mysqldefrag.php"]=true;
    $noback["wizard.install.php"]=true;
    $noback["wizard.hotspot.install.php"]=true;
    $noback["xapian.index.php"]=true;
    $noback["android.logon.php"]=true;
    $noback["fonts.css.php"]=true;
    $noback["mailattach.php"]=true;
    $noback["authenticator.php"]=true;
    $noback["nginx.error.php"]=true;
    $noback["admin.help.php"]=true;
    $noback["jabberauth.php"]=true;
    $noback["public.squid.error.php"]=true;
    $noback["exec.squid.sslpassword_program.php"]=true;
    $noback["external_acl_dynamic.php"]=true;
    $noback["external_acl_first.php"]=true;
    $noback["external_acl_gsb.php"]=true;
    $noback["external_acl_squid.php"]=true;
    $noback["execdomainlist.php"]=true;
    $noback["external_acl_squid_quota.php"]=true;
    $noback["external_acl_squid.php"]=true;
    $noback["hotspot.php"]=true;
    $noback["squid.access.log.php"]=true;
    $noback["wizard.automationscript.progress.php"]=true;
    $noback["import.squid.zip.php"]=true;
    $noback["squid.access.webfilter.log.php"]=true;
    $noback["artica.meta.listener.php"]=true;
    $noback["listen.snapshots.php"]=true;
    $noback["pfx.php"]=true;
    $noback["fw.login.php"]=true;
    $noback["fw.captcha.php"]=true;
    $noback["fw.login.acls.php"]=true;
    $noback["fw.login.gateway.php"]=true;
    $noback["fw.icon.top.php"]=true;
    $noback["fw.wizard.php"]=true;
    $noback["fw.wizard.intro.php"]=true;
    $noback["fw.wizard.progress.php"]=true;
    $noback["fw.hack.php"]=true;
    $noback["smtp.autoconfig.php"]=true;
    $noback["public.categories.personnal.php"]=true;
    $noback["xapian.search.php"]=true;
    $noback["fw.ping.php"]=true;
    return $noback;
}

function _noback($page){
    $noback=noback();
    if(preg_match('#^smtpurls.php#',$page)){$noback[$page]=true;}
    if(preg_match('#^exec\..+\.php$#',$page)){$noback[$page]=true;}
    if(preg_match('#^rest\..+\.php$#',$page)){$noback[$page]=true;}
    if(preg_match('#listener\.php$#',$page)){$noback[$page]= true;}
    if(preg_match("#^prxy\..*?\.php$#", $page)){$noback[$page]=true;}
    if(preg_match("#^external_acl.*\.php#", $page)){$noback[$page]=true;}
    if(isset($noback[$page])){
        if($noback[$page]){return true;}
    }
    return false;
}
function session_time_min($firstTime):int{
    if(!is_numeric($firstTime)){return 0;}
    $data1 = $firstTime;
    $data2 = time();
    $difference = ($data2 - $data1);
    $results=intval(round($difference/60));
    if($results<0){$results=1;}
    return intval($results);
}
function _sessions_syslog($text):bool{
    $DebugWebConsoleSession=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugWebConsoleSession"));
    if($DebugWebConsoleSession==0){return true;}
    if(!function_exists("openlog")){return true;}
    openlog("artica-sessions", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function CheckSessions(){
    $suffixLogoff=null;
    $noback=array();
    $start = microtime(true);
    if($GLOBALS["EXECUTED_AS_ROOT"]){return true;}
    if(isset($GLOBALS["KAV4PROXY_NOSESSION"])){ if(!$GLOBALS["KAV4PROXY_NOSESSION"]){ return true; } }

    $SessionInactivitytime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionInactivitytime"));
    if(!isset($GLOBALS["DEBUG_TEMPLATE"])){$GLOBALS["DEBUG_TEMPLATE"]=false;}
    $page=CurrentPageName();
    ExecTBtime($start,"CheckSessions",__LINE__);
    $start = microtime(true);
    $getmypid="";
    $PAGEJS["nodes.popup.php"]=true;
    $HTTP_X_ARTICA_SUBFOLDER="";
    $logoffpage="/{$HTTP_X_ARTICA_SUBFOLDER}logoff.php";
    if(preg_match("#^fw\.#", $page)){$logoffpage="/{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php?disconnect=yes";}
    if(preg_match("#^wiz\.#", $page)){
        safe_session_start();
        return true;
    }
    $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    $NOBACK=_noback($page);
    if($SessionCookieLifetime==0){$SessionInactivitytime=0;}

    if($page=="fw.ping.php"){
        safe_session_start();
        $curtimeout=0;
        if(isset($_SESSION["SESSARTTIMEOUT"])){$curtimeout=$_SESSION["SESSARTTIMEOUT"];}

        _sessions_syslog("[".__LINE__."]:[$page]: SessionCookieLifetime:$SessionCookieLifetime SessionInactivitytime:$SessionInactivitytime timeout:$curtimeout");

        if($SessionInactivitytime>0){
            if($curtimeout>0) {
                $minutes=session_time_min($curtimeout);
                _sessions_syslog("[".__LINE__."]:[$page]: {$minutes}mn max=$SessionInactivitytime");
                if($minutes>=$SessionInactivitytime){
                    echo "<script>document.location.href='{$suffixLogoff}{$logoffpage}';</script>";
                    die();
                }
            }
        }
        return true;
    }

    VERBOSE("CHECKING SESSION for [$page]...",__LINE__);

    if(!isset($_SESSION["SESSARTTIMEOUT"])){$SessionInactivitytime=0;}
    if($SessionInactivitytime==0) {
        if (@session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
    }


    if(!class_exists("usersMenus")){
        include_once(dirname(__FILE__)."/class.users.menus.inc");
    }

    if($SessionInactivitytime==0) {
        $start = microtime(true);
        safe_session_start();
        ExecTBtime($start, "safe_session_start()", __LINE__);
    }

    $sesslen=strlen($GLOBALS["SESSION_ID"]);
    if($sesslen<2){
        $GLOBALS["SESSION_ID"]=session_id();
    }
    if(!isset($_SESSION["uid"])){$_SESSION["uid"]=null;}

    if($GLOBALS["DEBUG_TEMPLATE"]){
        $called="";
        if(function_exists("getmypid")){$getmypid="PID:".getmypid()." ";}
        $session_detected_lang=null;
        if(isset($_SESSION["detected_lang"])){$session_detected_lang=$_SESSION["detected_lang"];}
        $trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
        VERBOSE("[{$_SESSION["uid"]}]::$page:{$getmypid}CheckSessions() language=\"{$session_detected_lang}\" $called function:".__FUNCTION__." in " . basename(__FILE__),__LINE__);
    }

    $NOTIMEOUT["fw.system.status.php"]=true;

    if($_SESSION["uid"]<>null) {
        if(!isset($NOTIMEOUT[$page])) {
            $_SESSION["SESSARTTIMEOUT"] = time();
            _sessions_syslog("[" . __LINE__ . "]:[$page]: SESSARTTIMEOUT:{$_SESSION["SESSARTTIMEOUT"]}");
        }
    }

    if($NOBACK){return true;}
    $start = microtime(true);
    if($_SESSION["uid"]==null){
        if(isset($_GET["session"])){
            if(!class_exists("user")) {
                include_once(dirname(__FILE__) . '/class.user.inc');
            }
            $users=new user($_GET["uid"]);
            if(md5($users->password)==$_GET["session"]){
                if(function_exists("admin_tracks")) {
                    admin_tracks("Creating new session for uid={$_GET["uid"]}");
                }
                $_SESSION["uid"]=$_GET["uid"];
                if(!isset($_SESSION["SESSARTSTART"])) {
                    $_SESSION["SESSARTSTART"] = time();
                }
                if(!isset($_SESSION["SESSARTTIMEOUT"])) {
                    $_SESSION["SESSARTTIMEOUT"] = time();
                }
                return true;
            }
        }
    }
    if($SessionInactivitytime>0){
        if(!isset($_SESSION["SESSARTTIMEOUT"])) {
            if($page<>"fw.ping.php") {
                $_SESSION["SESSARTTIMEOUT"] = time();
            }
        }
    }
    ExecTBtime($start,__FUNCTION__,__LINE__);

    if($SessionCookieLifetime>0){
        if(!isset($_SESSION["SESSARTSTART"])){
            error_log("Session time doesn't exists, remove any session information L.".__LINE__);
            admin_tracks("Shutdown current session, no start time defined.");
            if(isset($_SERVER["PHP_AUTH_USER"])){unset($_SERVER["PHP_AUTH_USER"]);}
            unset($_SESSION);
            $_SESSION["uid"]=null;
        }else{
            $start = microtime(true);
            $EndTime=$_SESSION["SESSARTSTART"]+($SessionCookieLifetime*60);
            if(time()>$EndTime){
                admin_tracks("Session timeout, automatic logoff after $SessionCookieLifetime minutes");
                unset($_SESSION);
                if(isset($_SERVER["PHP_AUTH_USER"])){unset($_SERVER["PHP_AUTH_USER"]);}
                $_SESSION["uid"]=null;
            }
            ExecTBtime($start,"SESSARTSTART",__LINE__);
        }
    }

    if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::`{$_SESSION["uid"]}`<br>\n";}
    if($_SESSION["uid"]==null){
        if(isset($_SERVER["PHP_AUTH_USER"])){
            if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::PHP_AUTH_USER -> `{$_SERVER["PHP_AUTH_USER"]}`<br>\n";}
            BuildSession($_SERVER["PHP_AUTH_USER"]);
        }
    }

    if($_SESSION["uid"]<>-100){
        if(!isset($_SESSION["privileges_array"])){
            if($_SESSION["uid"]<>null){
                BuildSession($_SESSION["uid"]);
                if(!isset($_SESSION["privileges_array"])){$_SESSION["privileges_array"]=null;}
            }
        }}
    $start = microtime(true);
    $currentdir=basename(dirname($_SERVER['SCRIPT_FILENAME']));
    if($currentdir<>'smartsieve'){
        //session END --------------------------------------------
        if(!isset($_SESSION["uid"])){
            if(!isset($noback[$page])){$noback[$page]=false;}
            if(!$noback[$page]){
                $user=new usersMenus();
                if($user->LIGHTTPD_LDAP_AUTH){BuildSessionAuth();}
            }
        }

        if(!isset($_SESSION["uid"])){

            if(!$noback[$page]){
                if(isset($_GET["jQueryLjs"])){
                    header("content-type: application/x-javascript");
                    echo "document.location.href='$logoffpage'";
                    exit;
                }

                if(!isset($PAGEJS[$page])){$PAGEJS[$page]=false;}

                if($PAGEJS[$page]){
                    header("content-type: application/x-javascript");
                    echo "document.location.href='$logoffpage'";
                    exit;
                }
                if(isset($GLOBALS["STOP_SESSION_JS"])){echo "document.location.href='$logoffpage'";die("DIE " .__FILE__." Line: ".__LINE__);}
                if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::".dirname(__FILE__)."/logs.inc\n";}
                include_once(dirname(__FILE__).'/logs.inc');
                error_log("[{$_SESSION["uid"]}]::$page is not allowed, no session, `{$_SESSION["uid"]}` die.. in ".__FILE__." line ". __LINE__);
                if(isset($_GET["in-front-ajax"])){
                    if(preg_match("#^fw\.#", $page)){echo "document.location.href='/{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php?disconnect=yes'";exit;}
                    echo "document.location.href='$logoffpage'";exit;}

                if(preg_match("#^android\.#", $page)){$suffixLogoff="android.";}
                $QUERYS=array();$zQueries="";
                foreach ($_REQUEST as $a=>$b){ $QUERYS[]="$a=$b"; }
                if(is_array($QUERYS)){
                    if(count($QUERYS)>0){
                        $zQueries=@implode("&", $QUERYS);
                    }
                }
                if(!is_null($zQueries)) {
                    if (preg_match("#-js=#", $zQueries)) {
                        header("content-type: application/x-javascript");
                        if (preg_match("#^fw\.#", $page)) {
                            echo "document.location.href='/{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php?disconnect=yes'";
                            die("DIE " . __FILE__ . " Line: " . __LINE__);
                        }
                        echo "alert('Session end...');\n";
                        echo "document.location.href='$suffixLogoff$logoffpage';";
                        exit;
                    }
                }



                echo "<html><!-- $page  -->\n<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$logoffpage\"> </head>
						<body>
						<script>document.location.href='$suffixLogoff$logoffpage';</script>
						</body>
						</html>";
                echo header('location:'.$suffixLogoff.$logoffpage);exit;

            }


        }}
    ExecTBtime($start,"smartsieve block",__LINE__);
}
function FormatBytes($kbytes,$nohtml=false):string{

    $spacer="&nbsp;";
    $value=0;
    if($nohtml){$spacer="";}

    if($kbytes>1048576){
        $value=round($kbytes/1048576, 2);
        if($value>1000){
            $value=round($value/1000, 2);
            return "$value{$spacer}TB";
        }
        return "$value{$spacer}GB";
    }
    elseif ($kbytes>=1024){
        $value=round($kbytes/1024, 2);
        return "$value{$spacer}MB";
    }
    else{
        if(is_float($kbytes)) {
            $value = round($kbytes, 2);
        }
        return "$value{$spacer}KB";
    }
}
function ParseBytes($bytes){
    $kb=$bytes/1024;
    return FormatBytes($kb);
}
class filesClass{

    function FindFileInFolder($dir,$extension){
        $f=new filesClass();
        $array=$f->DirListTable($dir,true);
        foreach ($array as $num=>$val){
            if($f->Get_extension($val)==$extension){return $val;}
        }
    }

    function Get_extension($filename){
        $parts = explode('.',$filename);
        $last = count($parts) - 1;
        $ext = $parts[$last];
        return $ext;


    }


    function DirListTable($dir,$onlyfiles=false){
        $Files = array();
        $It=  @opendir($dir);
        if (! $It){return null;}
        while ($Filename = readdir($It)){
            if ($Filename == '.' || $Filename == '..')
                continue;

            if($onlyfiles==true){
                $ext=$this->Get_extension($Filename);
                if($ext<>null)
                    $Files[]=$Filename;
            }
            else{$Files[]=$Filename;}
        }

        return $Files;
    }


    function isMounted($mountpoint){
        $mountpoint=str_replace('/','\/',$mountpoint);
        $mountpoint=str_replace('.','\.',$mountpoint);
        $datas=explode("\n",file_get_contents('/proc/mounts'));

        if(!is_array($datas)){return false;}
        foreach ($datas as $num=>$line){
            if(preg_match("#\s+$mountpoint#",$line)){
                return true;
            }else{

            }

        }


    }


}
function browserTree($target_field){
    return "
		<div style='width:100%;text-align:right'>
				<input type='button' OnClick=\"Loadjs('TreeBrowse.php?t=$target_field');\" value='{browse}...&nbsp;&raquo;'>
			</div>
		";

}



function ASCII_TO_HTML($data){
    $data=str_replace("'","&#39",$data);
    $data=str_replace('"',"&#34",$data);
    $data=str_replace('"',"&#34",$data);
    $data=str_replace('�',"&eacute;",$data);
    $data=str_replace('�',"&egrave;",$data);
    $data=str_replace('�',"&agrave;",$data);
    $data=str_replace('�',"&acirc;",$data);
    $data=str_replace("\rn","<br>",$data);
    $data=str_replace("\n","<br>",$data);
    $data=str_replace("\r","<br>",$data);

    return $data;
}
function CalendarPickup(){
    $page=CurrentPageName();
    $field=$_GET["field"];
    $html="
	loadssfile('js/dhtmlxcalendar/codebase/dhtmlxcalendar.css');
	Loadjs('js/dhtmlxcalendar/dhtmlxcalendar.js');
	Loadjs('js/dhtmlxcalendar/dhtmlxcommon.js');
	window.dhx_globalImgPath='js/dhtmlxcalendar/codebase/imgs/';
	function LoadAll(){
		 if (typeof dhtmlxCalendarObject=='undefined'){	
			Loadjs('$page?CalendarPickup=yes&field=$field');
			return null;
		}
	
	var cal1 = new dhtmlxCalendarObject('$field',true,{isYearEditable: true,headerButtons: 'TMX'});
	cal1.show();
	//cal1.curDate=document.getElementById('$field').value;
	
	}
	

	LoadAll();

	
	";

    echo $html;

}
function button_browse($field_name){}
function button_browse_ldap_group($function){
    $button="&nbsp;<input type='button' OnClick=\"Loadjs('browse-ldap-groups.php?function=$function');\" value='{browse}...&nbsp;'>";
    $button=button("{browse}...&nbsp;", "Loadjs('browse-ldap-groups.php?function=$function');");
    return $button;

}
function button_browse_computer($computer,$field_name){
    $button="&nbsp;<input type='button' OnClick=\"Loadjs('ComputerBrowse.php?&computer=$computer&field=$field_name');\" value='{browse}...&nbsp;'>";
    return $button;
}
function formatQueryResultsTable($ligne,$count=null,$memory=true,$js=null,$bgcolor=null){
    if($memory){
        if($_GET["ID"][$ligne["MessageID"]]=="OK"){return null;}
        $_GET["ID"][$ligne["MessageID"]]="OK";
    }

    if($js==null){$js="ShowBackupMail('{$ligne["MessageID"]}')";}
    $title=$ligne["subject"];
    $title=utf8_decode($title);
    if(strlen($title)>50){
        $title=texttooltip(substr($title,0,47).'...',$title,$js,null,0,"text-decoration:underline;font-size:11px");
    }else{
        $title="<div OnClick=\"javascript:$js\" style='text-decoration:underline;font-size:11px'>$title</div>";
    }
    $body=$ligne["MessageBody"];
    if(preg_match('#<!--X-Body-of-Message-->(.+?)<!--X-Body-of-Message-End-->#is',$body,$re)){
        $body=$re[1];


    }

    $y=date('Y');
    $today=date('Y-m-d');
    $ligne["zDate"]=str_replace("$today",'{today}',$ligne["zDate"]);
    $ligne["zDate"]=str_replace("-$y",'',$ligne["zDate"]);


    if(is_numeric($count)){$number="$count.&nbsp;";}
    $body=strip_tags($body);
    $body=substr($body,0,300).'...';
    $body=wordwrap($body, 100, "<br />\n");
    $html="
	<tr style='background-color:$bgcolor'>
		<td style='padding:4px'>$number</td>
		<td style='padding:4px'>". round($ligne["pertinence"],2)."</td>
		<td style='padding:4px'>$title</td>
		<td style='padding:4px'>{$ligne["mailfrom"]}</td>
		<td nowrap style='padding:4px'><strong>{$ligne["zDate"]}</strong></td>
	</tR>";

    return $html;



    $tt="
	<table style='width:99%;margin-top:6px'>
	<tr>
		<td>$number$title</td>
	</tr>
	<tr>
	<td><span style='font-size:small;color:#676767'>&laquo&nbsp;{$ligne["mailfrom"]}&nbsp;&raquo;&nbsp;-&nbsp;{$ligne["zDate"]}</span></td>
	</tr>
	<tr>
	<td>
		<table style='width:100%'>
		<tr>
			<td valign='top' width=1%><img src='img/outicon_1002.gif'></td>
			<td width=99%><span style='font-size:small;color:#676767'>&laquo&nbsp;{$ligne["mailto"]}&nbsp;&raquo;</span></td>
		</tr>
		</table>
	</tr>	
	<tr>
	<td style='font-size:small'>$body</td>
	</tr>
	<tr>
	<td style='font-size:small;color:green' align='left'>{$ligne["MessageID"]} (".round($ligne["pertinence"],2).")</td>
	</tr>	
	</table>
	";

    return $html;


}
function formatQueryResultsAsGoogle($ligne,$count=null,$memory=true,$js=null){
    if($memory){
        if($_GET["ID"][$ligne["MessageID"]]=="OK"){return null;}
        $_GET["ID"][$ligne["MessageID"]]="OK";
    }

    if($js==null){$js="ShowBackupMail('{$ligne["MessageID"]}')";}
    $title=$ligne["subject"];

    if(strlen($title)>65){
        $title=texttooltip(substr($title,0,61).'...',$title,$js,null,0,"color:#0000CC;text-decoration:underline;font-size:medium");
    }else{
        $title="<a href='#' OnClick=\"javascript:$js\" style='color:#0000CC;text-decoration:underline;font-size:medium'>$title</a>";
    }
    $body=$ligne["MessageBody"];
    if(preg_match('#<!--X-Body-of-Message-->(.+?)<!--X-Body-of-Message-End-->#is',$body,$re)){
        $body=$re[1];


    }
    if(is_numeric($count)){$number="$count.&nbsp;";}
    $body=strip_tags($body);
    $body=substr($body,0,300).'...';
    $body=wordwrap($body, 100, "<br />\n");
    $html="
	
	<table style='width:99%;margin-top:6px'>
	<tr>
		<td>$number$title</td>
	</tr>
	<tr>
	<td><span style='font-size:small;color:#676767'>&laquo&nbsp;{$ligne["mailfrom"]}&nbsp;&raquo;&nbsp;-&nbsp;{$ligne["zDate"]}</span></td>
	</tr>
	<tr>
	<td>
		<table style='width:100%'>
		<tr>
			<td valign='top' width=1%><img src='img/outicon_1002.gif'></td>
			<td width=99%><span style='font-size:small;color:#676767'>&laquo&nbsp;{$ligne["mailto"]}&nbsp;&raquo;</span></td>
		</tr>
		</table>
	</tr>	
	<tr>
	<td style='font-size:small'>$body</td>
	</tr>
	<tr>
	<td style='font-size:small;color:green' align='left'>{$ligne["MessageID"]} (".round($ligne["pertinence"],2).")</td>
	</tr>	
	</table>
	";

    return $html;


}
function CleanMail($data){
    $data=str_replace("<hr>",'',$data);
    $data=str_replace("<o:p></o:p>","",$data);
    $data=str_replace('face="Times New Roman"',"",$data);
    $data=str_replace('face="Calibri"',"",$data);
    $data=str_replace('face="Arial"',"",$data);
    $data=str_replace('face="Tahoma"',"",$data);
    $data=str_replace('face=Tahoma',"",$data);
    $data=str_replace('face=Arial',"",$data);
    $data=str_replace('face=Calibri',"",$data);
    $data=str_replace('font-family:Arial','',$data);
    $data=str_replace('font-family:Calibri','',$data);
    $data=str_replace('font-family:Tahoma','',$data);
    $data=str_replace('size="2"','',$data);
    $data=str_replace('size="1"','',$data);
    $data=str_replace('size="3"','',$data);
    $data=str_replace('size=2','',$data);
    $data=str_replace('size=1','',$data);
    $data=str_replace('size=3','',$data);
    $data=str_replace('FONT-FAMILY: Verdana','',$data);
    $data=str_replace('10pt','',$data);
    $data=str_replace('10.0pt','',$data);
    $data=str_replace('11.0pt','',$data);
    $data=str_replace('12.0pt','',$data);

    $data=str_replace('<o:p>&nbsp;</o:p>','',$data);

    $data=str_replace("<h1>","<p style='font-size:15px;font-weight:bold;
	border-bottom:1px solid #CCCCCC;margin-bottom:5px;padding:5px;'>",$data);
    $data=str_replace("</h1>","</p>",$data);
    return $data;
}







function br2nl($string){
    return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}

function IsIPValid($ip) {
    $ip_segments = explode('.', $ip);

    if (count($ip_segments) != 4)
        return FALSE;

    if ($ip_segments[0] == 0)
        return FALSE;

    foreach ($ip_segments as $segment) {
        if (!ctype_digit($segment))
            return FALSE;
        if (strlen($segment) > 3)
            return FALSE;
        if ($segment > 255)
            return FALSE;
    }

    return TRUE;
}
function isBuildPid($file){
    $pid=getmypid();
    $file=basename($file);
    $pidfile="/etc/artica-postfix/croned.1/$file.pid";
    if(file_exists($pidfile)){
        $currentpid=trim(@file_get_contents($pidfile));
        write_syslog("NewPID PID: $pid");
        write_syslog("Current PID: $currentpid");
        if($currentpid<>$pid){
            if(is_dir('/proc/'.$currentpid)){
                write_syslog("Already instance executed");
                return false;
            }else{
                write_syslog("$currentpid is not executed continue...");
                return true;
            }

        }

    }

    return true;
}
function system_alreadyExecuted_events($text,$filepath=null){
    if($filepath==null){return null;}
    if(!is_file($filepath)){return null;}
    $filename=basename(__FILE__);
    $pid=getmypid();
    $date=date("H:i:s");
    $logFile=$filepath;
    $size=filesize($logFile);
    if($size>1000000){unlink($logFile);}
    $f = @fopen($logFile, 'a');
    @fwrite($f, "$date [$pid] $filename system_alreadyExecuted(): $text\n");
    @fclose($f);
}

function distanceMinStrings($lastSeen){
    $toTime=time();
    $fromTime=strtotime($lastSeen);
    $distanceInSeconds = round(abs($toTime - $fromTime));
    $distanceInMinutes = round($distanceInSeconds / 60);
    return $distanceInMinutes;
}
function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
    if(!is_numeric($fromTime)){return "Unknown";}
    if(!is_numeric($toTime)){return "Unknown";}

    $distanceInSeconds = round(abs($toTime - $fromTime));
    $distanceInMinutes = round($distanceInSeconds / 60);

    if ( $distanceInMinutes <= 1 ) {
        if ( !$showLessThanAMinute ) {
            return ($distanceInMinutes == 0) ? 'less than a minute' : '1 {minute}';
        } else {
            if ( $distanceInSeconds < 5 ) {
                return '{lessthan} 5 {seconds} ('.$distanceInSeconds.'s)';
            }
            if ( $distanceInSeconds < 10 ) {
                return '{lessthan} 10 {seconds} ('.$distanceInSeconds.'s)';
            }
            if ( $distanceInSeconds < 20 ) {
                return '{lessthan} 20 {seconds} ('.$distanceInSeconds.'s) ';
            }
            if ( $distanceInSeconds < 40 ) {
                return '{abouttime} {halfaminute} ('.$distanceInSeconds.'s)';
            }
            if ( $distanceInSeconds < 60 ) {
                return '{lessthanaminute}';
            }

            return '1 minute';
        }
    }
    if ( $distanceInMinutes < 45 ) {
        return $distanceInMinutes . ' {minutes}';
    }
    if ( $distanceInMinutes < 90 ) {
        return '{abouttime} 1 {hour}';
    }
    if ( $distanceInMinutes < 1440 ) {
        return '{abouttime} ' . round(floatval($distanceInMinutes) / 60.0) . ' {hours}';
    }
    if ( $distanceInMinutes < 2880 ) {
        return '1 {day}';
    }
    if ( $distanceInMinutes < 43200 ) {
        return '{abouttime} ' . round(floatval($distanceInMinutes) / 1440) . ' {days}';
    }
    if ( $distanceInMinutes < 86400 ) {
        return '{abouttime} 1 {month}';
    }
    if ( $distanceInMinutes < 525600 ) {
        return round(floatval($distanceInMinutes) / 43200) . ' {months}';
    }
    if ( $distanceInMinutes < 1051199 ) {
        return '{abouttime} 1 {year}';
    }

    return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' {years}';

}
function file_ext($filename)
{
    $filename = strtolower($filename) ;
    $exts = explode(".", $filename) ;
    $n = count($exts)-1;
    $exts = $exts[$n];
    return $exts;
}
function file_get_time_min($path){
    if(!is_file($path)){if(!is_dir($path)){return 10000;}}
    $last_modified = filemtime($path);

    $data1 = $last_modified;

    $data2 = time();
    $difference = ($data2 - $data1);
    return round($difference/60);
}
function send_email_events($subject,$text,$context,$date=null,$attached_files=array(),$recipient=null){
    if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/../framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
    if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/../framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
    $trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
    $text=$called."\n".$text;
    $GLOBALS["CLASS_UNIX"]->send_email_events($subject,$text,$context,$date,$attached_files,$recipient);
}
function THREAD_COMMAND_SET($zcommands){
    if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/../framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
    if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/../framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
    $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($zcommands);
}
function _admin_event($text,$function,$file,$line,$category){
    $mem=round(((memory_get_usage(true)/1024)/1000),2);
    $array_load=sys_getloadavg();
    $internal_load=$array_load[0];
    $text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";

    if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
    if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
    $array["zdate"]=date("Y-m-d H:i:s");
    $array["text"]=$text;
    $array["function"]=$function;
    $array["file"]=basename($file);
    $array["line"]=$line;
    $array["category"]=$category;
    $array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
    $serialize=serialize($array);

    if($GLOBALS["EXECUTED_AS_ROOT"]){
        $md5=md5($serialize);
        if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events",0755,true);}
        @file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events/$md5.log", $serialize);
        return;
    }

    $sock=new sockets();
    $sock->getFrameWork("services.php?admin-events=".base64_encode($serialize));

}
function _udfbguard_admin_events($text,$function,$file,$line,$category){}
function infected_queue($TaskName,$InfectedPath,$ComputerName,$virusname){
    $date=date('Y-m-d H:i:s');
    $md5=md5($date.$TaskName.$InfectedPath.$virusname);
    $filename="{$GLOBALS["ARTICALOGDIR"]}/infected-queue/$md5.sql";
    if(is_file($filename)){return null;}
    if($TaskName=="kavmilter"){$TaskName="eMail Scan Kaspersky";}
    $sql="INSERT INTO antivirus_events (zDate,TaskName,VirusName,InfectedPath,ComputerName,zmd5) VALUES('$date','$TaskName','$virusname','$InfectedPath','$ComputerName','$md5');";
    @file_put_contents($filename,$sql);
}




function GET_CACHED($file,$function,$token=null,$return=false,$maxmin=0){

    if($GLOBALS["VERBOSE"]){return;}
    if($GLOBALS["AS_ROOT"]){return false;}
    $MD5=GET_CACHED_KEY($file,$function,$token);


    $tpl=new templates();
    if(class_exists("Memcache")){
        if(_is_socket("/var/run/memcached.sock")){
            $memcache = new Memcache();
            if($memcache->connect('unix:///var/run/memcached.sock', 0)){
                $data=$memcache->get($MD5);
                if(trim($data)<>null){
                    if($return){ return $tpl->_ENGINE_parse_body($data);  }
                    echo $tpl->_ENGINE_parse_body($data);
                    return true;
                }
            }
        }
    }



    $dir=dirname(__FILE__)."/logs/web/cache";
    $targetFile="$dir/$MD5.cache";
    if(!is_file($targetFile)){return false;}
    $data=trim(@file_get_contents($targetFile));
    if(trim($data)==null){return false;}
    if($return){return $tpl->_ENGINE_parse_body($data);}
    echo $tpl->_ENGINE_parse_body($data);
    return true;



}
function _alt_stat($file) {
    $ss=@stat($file);
    if(!$ss) return false;
    $ts=array( 0140000=>'ssocket', 0120000=>'llink', 0100000=>'-file', 0060000=>'bblock', 0040000=>'ddir', 0020000=>'cchar', 0010000=>'pfifo');
    $t=decoct($ss['mode'] & 0170000); // File Encoding Bit
    $s=array(	'filetype'=>array( 'type'=>substr($ts[octdec($t)],1)));
    return $s;
}
function _is_socket($socketpath){
    if(isset($GLOBALS["_is_socket"][$socketpath])){return $GLOBALS["_is_socket"][$socketpath];}
    $stat=_alt_stat($socketpath);
    $type=$stat["filetype"]["type"];
    if($type=="socket"){
        $GLOBALS["_is_socket"][$socketpath]=true;
        return true;
    }
    $GLOBALS["_is_socket"][$socketpath]=false;
    return false;
}
function GET_CACHED_KEY($file,$function,$token){
    if(trim($token)==null){$token=$function;}
    $file=basename($file);
    $token=md5($token);

    if($GLOBALS["AS_ROOT"]){$uid=-100;}else{
        if(!isset($_SESSION["uid"])){$_SESSION["uid"]=null;}
        $uid=$_SESSION["uid"];
    }
    if($uid==-100){$uid="RootMaster";}
    return md5("$uid$file$function$token");

}
function SET_CACHED($file,$function=null,$token=null,$datas=null){
    if(strlen($datas)<5){return;}
    if($GLOBALS["AS_ROOT"]){$uid=-100;}


    if(trim($token)==null){$token=$function;}
    $file=basename($file);
    $token=md5($token);
    $MD5=GET_CACHED_KEY($file,$function,$token);



    if(class_exists("Memcache")){
        if(_is_socket("/var/run/memcached.sock")){
            $memcache = new Memcache();
            if($memcache->connect('unix:///var/run/memcached.sock', 0)){
                if($memcache->set($MD5, $datas, 0, 300)){ return; }
            }
        }
    }

    $dir=dirname(__FILE__)."/logs/web/cache";
    if(!is_dir($dir)){@mkdir($dir,0755,true);}
    $targetFile="$dir/$MD5.cache";
    @file_put_contents($targetFile,$datas);
    @chmod($targetFile, 0777);
}
function REMOVE_CACHED($file=null){
    $sizetot=0;
    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="RootMaster";}
    $dir=dirname(__FILE__)."/logs/web/cache/$uid";
    foreach (glob("$dir/".basename($file)."*.*") as $filename) {
        writelogs("removing $filename",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $size=@filesize($filename)/1024;
        $sizetot=$sizetot+$size;
        unlink($filename);
    }

    if(class_exists("Memcache")){
        if(_is_socket("/var/run/memcached.sock")){
            $memcache = new Memcache();
            $memcache->connect('unix:///var/run/memcached.sock', 0);
            $ARRAY=unserialize($memcache->get('ARTICACACHEARRAY'));
            $size=strlen(serialize($ARRAY))/1024;
            $sizetot=$sizetot+$size;
            //writelogs("Memcache SET ".strlen(serialize($ARRAY))." bytes length",__FUNCTION__,__FILE__,__LINE__);
            $memcache->set('ARTICACACHEARRAY', array(), 0, 300);
        }
    }
    if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
    unset($_SESSION["cached-pages"]);
    return $sizetot;
}
function finduser_format($array,$faltomde=false){
    $tpl=new templates();
    $organization=$tpl->_ENGINE_parse_body("{organization}");
    if(strpos($array["sn"], ',')>0){$array["sn"]=null;}

    $uid=$array["uid"];

    $disp="{$array["givenname"]} {$array["sn"]}";
    if(trim($disp)=="{unknown}"){$disp=null;}

    if(trim($disp)==null){
        $disp=$array["displayname"];
    }
    $keyimg="uid={$array["uid"]}";
    if($array["JS"]<>null){
        if(isset($array["employeenumber"])){
            $array["JS"]="javascript:Loadjs('contact.php?employeeNumber={$array["employeenumber"]}')";
            if($array["img"]=="img/contact-unknown-user.png"){$array["img"]="img/contact-60.png";}
            $keyimg="employeeNumber={$array["employeenumber"]}";

        }

    }
    if(is_numeric($array["uidNumber"])){
        $uidn="ID:&nbsp;<b>{$array["uidNumber"]}</b><br>";
    }
    if(trim($array["ou"])<>null){
        $ou="$organization:<b>{$array["ou"]}</b><br>";
    }

    if($array["mail"]<>null){
        $mail=$array["mail"];
    }
    if($array["phone"]<>null){
        if($array["mobile"]<>null){$array["mobile"]="&nbsp|&nbsp;{$array["mobile"]}";}

        $phone="{$array["phone"]}{$array["mobile"]}<br>";
    }

    if($array["title"]<>null){
        $title=$array["title"]."<br>";
    }

    if($faltomde){
        $html="
		<div style='width:98%' class=form>		
		<table style='width:100%'>
		<tr>
			<td valign='top' width=1%><a href=\"javascript:Blurz();\" OnClick=\"javascript:s_PopUp('edit.thumbnail.php?$keyimg',550,250);\"><img src='{$array["img"]}'></a></td>
			<td valign='top'>
				<table>
				<tr>
					<td><span style='font-size:14px;font-weight:bold'>$disp</span>
					<div style='font-size:11px'><i>$mail</i></div>
					<div style='font-size:11px'><i>$ou$title
					<a href=\"javascript:Blurz();\" 
						OnClick=\"Loadjs('domains.edit.user.phones.php?$keyimg')\" style='font-size:11px;text-decoration:underline'>
						$phone</a>
						$uidn</i></div>
				</td>
			</tr>
			</table>
			</td>
		</tr>
		</table>
		</div>
		";
        return $html;


    }

    $color="black";
    return array(
        'id' => "$uidn",
        'cell' => array(
            "<a href=\"javascript:Blurz();\" 
				OnClick=\"javascript:s_PopUp('edit.thumbnail.php?$keyimg',550,250);\">
				<img src='{$array["img"]}'></a>",
            "<a href=\"javascript:Blurz();\"  OnClick=\"{$array["JS"]};\" 
		style='font-size:20px;text-decoration:underline;color:$color'>$uid</a>
		<br><span style='font-size:18px;margin-bottom:-10px;text-transform:capitalize'>$disp</span>
		<br><span style='font-size:16px'><i>$ou$title$phone$uidn</i>",
            "<a href=\"javascript:Blurz();\"  OnClick=\"{$array["JS"]};\" 
			style='font-size:20px;text-decoration:underline;color:$color'>$mail</a></span>",


        )
    );



}
function is_base64_encoded($encodedString) {
    if($encodedString==null){return false;}
    $fincaracts=substr($encodedString,strlen($encodedString)-2,2);
    writelogs("\"$encodedString\" fin = \"$fincaracts\"",__FUNCTION__,__FILE__,__LINE__);
    //writelogs("$encodedString =\"".substr($encodedString,strlen($encodedString)-2,2)."\"",__FUNCTION__,__FILE__,__LINE__);
    if(substr($encodedString,strlen($encodedString)-2,2)=="=="){return true;}
    $arr["1"]=true;
    $arr["2"]=true;
    $arr["3"]=true;
    $arr["4"]=true;
    $arr["6"]=true;
    $arr["16"]=true;
    $arr["17"]=true;
    $arr["18"]=true;
    $arr["19"]=true;
    $arr["20"]=true;
    $arr["21"]=true;
    $arr["22"]=true;
    $arr["23"]=true;
    $arr["24"]=true;
    $arr["25"]=true;
    $arr["26"]=true;
    $arr["27"]=true;
    $arr["28"]=true;
    $arr["29"]=true;
    $arr["30"]=true;
    $arr["31"]=true;
    $arr["28"]=true;
    $arr["126"]=true;
    $arr["127"]=true;
    $arr["128"]=true;
    $arr["129"]=true;
    $arr["131"]=true;
    $arr["132"]=true;
    $arr["133"]=true;
    $arr["134"]=true;
    $arr["135"]=true;
    $arr["136"]=true;
    $arr["137"]=true;
    $arr["138"]=true;
    $arr["139"]=true;
    $arr["140"]=true;
    $arr["146"]=true;
    $arr["150"]=true;
    $arr["154"]=true;
    $arr["157"]=true;
    $arr["155"]=true;
    $arr["158"]=true;
    $arr["159"]=true;
    $arr["181"]=true;
    $arr["182"]=true;
    $arr["183"]=true;
    $arr["164"]=true;
    $arr["166"]=true;
    $arr["169"]=true;
    $arr["179"]=true;
    $arr["177"]=true;
    $arr["171"]=true;
    $arr["173"]=true;
    $arr["186"]=true;
    $arr["187"]=true;
    $arr["198"]=true;
    $arr["202"]=true;
    $arr["211"]=true;
    $arr["214"]=true;
    $arr["221"]=true;
    $arr["222"]=true;
    $arr["225"]=true;
    $arr["227"]=true;
    $arr["229"]=true;
    $arr["231"]=true;
    $arr["232"]=true;
    $arr["247"]=true;
    $arr["250"]=true;
    $arr["128"]=true;
    $arr["129"]=true;
    $arr["130"]=true;
    $arr["131"]=true;
    $arr["132"]=true;
    $arr["133"]=true;
    $arr["134"]=true;
    $arr["135"]=true;
    $arr["136"]=true;
    $arr["137"]=true;
    $arr["138"]=true;
    $arr["139"]=true;
    $arr["140"]=true;
    $arr["141"]=true;
    $arr["144"]=true;
    $arr["145"]=true;
    $arr["146"]=true;
    $arr["147"]=true;
    $arr["148"]=true;
    $arr["149"]=true;
    $arr["150"]=true;
    $arr["151"]=true;
    $arr["152"]=true;
    $arr["153"]=true;
    $arr["154"]=true;
    $arr["155"]=true;
    $arr["156"]=true;
    $arr["157"]=true;
    $arr["158"]=true;
    $arr["159"]=true;
    $arr["160"]=true;
    $arr["161"]=true;
    $arr["162"]=true;
    $arr["163"]=true;
    $arr["164"]=true;
    $arr["165"]=true;
    $arr["166"]=true;
    $arr["167"]=true;
    $arr["168"]=true;
    $arr["169"]=true;
    $arr["170"]=true;
    $arr["171"]=true;
    $arr["172"]=true;
    $arr["173"]=true;
    $arr["174"]=true;
    $arr["175"]=true;
    $arr["176"]=true;
    $arr["177"]=true;
    $arr["178"]=true;
    $arr["179"]=true;
    $arr["180"]=true;
    $arr["181"]=true;
    $arr["182"]=true;
    $arr["183"]=true;
    $arr["184"]=true;
    $arr["185"]=true;
    $arr["186"]=true;
    $arr["187"]=true;
    $arr["188"]=true;
    $arr["189"]=true;
    $arr["190"]=true;
    $arr["191"]=true;
    $arr["192"]=true;
    $arr["193"]=true;
    $arr["194"]=true;
    $arr["195"]=true;
    $arr["196"]=true;
    $arr["197"]=true;
    $arr["198"]=true;
    $arr["199"]=true;
    $arr["200"]=true;
    $arr["201"]=true;
    $arr["202"]=true;
    $arr["203"]=true;
    $arr["204"]=true;
    $arr["205"]=true;
    $arr["206"]=true;
    $arr["207"]=true;
    $arr["208"]=true;
    $arr["209"]=true;
    $arr["210"]=true;
    $arr["211"]=true;
    $arr["212"]=true;
    $arr["213"]=true;
    $arr["214"]=true;
    $arr["215"]=true;
    $arr["216"]=true;
    $arr["217"]=true;
    $arr["218"]=true;
    $arr["219"]=true;
    $arr["220"]=true;
    $arr["221"]=true;
    $arr["222"]=true;
    $arr["223"]=true;
    $arr["224"]=true;
    $arr["225"]=true;
    $arr["226"]=true;
    $arr["227"]=true;
    $arr["228"]=true;
    $arr["229"]=true;
    $arr["230"]=true;
    $arr["231"]=true;
    $arr["232"]=true;
    $arr["233"]=true;
    $arr["234"]=true;
    $arr["235"]=true;
    $arr["236"]=true;
    $arr["237"]=true;
    $arr["238"]=true;
    $arr["239"]=true;
    $arr["240"]=true;
    $arr["241"]=true;
    $arr["242"]=true;
    $arr["243"]=true;
    $arr["244"]=true;
    $arr["245"]=true;
    $arr["246"]=true;
    $arr["247"]=true;
    $arr["248"]=true;
    $arr["249"]=true;

    $datas=base64_decode($encodedString);
    for($i=0;$i<strlen($datas);$i++){
        $chr=getChr($datas[$i]);
        if($arr[$chr]){
            writelogs("{$datas[$i]} == NOT ENCODED",__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        writelogs("{$datas[$i]}  -> \"".getChr($datas[$i])."\"",__FUNCTION__,__FILE__,__LINE__);
    }
    return true;
}
function getChr($car){
    for($i=0;$i<256;$i++){
        if($car==chr($i)){return $i;}
    }
}
function IsPhysicalAddress($address){
    $address=strtoupper(trim($address));
    if($address=="UNKNOWN"){return null;}
    if($address=="00:00:00:00:00:00"){return false;}
    $address=str_replace(":","-",$address);

    If(strlen($address) > 18){
        writelogs("$address too many caracters, exceed 18 ",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    If($address == ""){
        writelogs("$address is null !",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    If(!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$address)){
        writelogs("$address did not match pattern ",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    $Array=explode("-",$address);
    If(strlen($Array[0]) != 2){
        writelogs("$address: array 0 did not match 2",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    If(strlen($Array[1]) != 2){
        writelogs("$address:array 1 did not match 2",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    If(strlen($Array[2]) != 2){
        writelogs("$address:array 2 did not match 2",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    If(strlen($Array[3]) != 2){
        writelogs("$address:array 3 did not match 2",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    If(strlen($Array[4]) != 2){
        writelogs("$address:array 4 did not match 2",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    If(strlen($Array[5]) != 2){
        writelogs("$address:array 5 did not match 2",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    return true;
}
function getLocalTimezone(){
    if(!class_exists("sockets")){return;}
    $content=trim(@file_get_contents("/etc/timezone"));
    if($content==null){
        $content= $GLOBALS["CLASS_SOCKETS"]->GET_INFO("system.php?etc-timezone=yes");
    }
    return $content;

}
function artica_get_memory_usage() {
    writelogs("memory : "._artica_get_memory_usage());

}
function _artica_get_memory_usage() {
    $mem_usage = memory_get_usage(true);
    if ($mem_usage < 1024){return $mem_usage." bytes";}
    if ($mem_usage < 1048576){return round($mem_usage/1024,2)." kilobytes";}
    return round($mem_usage/1048576,2)." megabytes";
}

function ParseSpecialsCharacters($content){
    $f["%u0413"]="Е";
    $f["%u0435"]="в";
    $f["%u0440"]="г";
    $f["%u0433"]="е";
    $f["%u0435"]="н";
    $f["%u0440"]="и";
    $f["%u0435"]="й";
    $f["%u0439"]="Г";
    $f["%u0434"]="е";
    $f["%u0435"]="р";
    $f["%u0440"]="г";
    $f["%u0415"]="е";
    $f["%u0432"]="р";
    $f["%u0433"]="е";
    $f["%u043D"]="й";
    $f["%u0438"]="д";
    $f["%u0439"]="р";
    foreach ($f as $key=>$line){
        $content=str_replace($key,$line,$content);
    }
    return $content;
}
function artica_mysql_events($severity=0,$subject=null,$text=null,$file=null,$line=0){
    $hostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    $zdate=date("Y-m-d H:i:s");
    $content=$text;


    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            if($file==null){
                $file=basename($trace[1]["file"]);
            }
            $function=$trace[1]["function"];
            if($line==0){
                $line=$trace[1]["line"];
            }
        }

    }
    $content=str_replace("'", "`", $content);
    $subject=str_replace("'", "`", $subject);


    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $subject=$q->sqlite_escape_string2($subject);
    $text=$q->sqlite_escape_string2($text);
    $ArticaNotifsMaxTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaNotifsMaxTime"));
    if($ArticaNotifsMaxTime==0){$ArticaNotifsMaxTime=7;}
    $removeafter=strtotime("+$ArticaNotifsMaxTime day");

    $q->QUERY_SQL("INSERT OR IGNORE INTO `squid_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`removeafter`) VALUES
			('$zdate','$content','$subject','$function','$file','$line','$severity','$removeafter')");

    if(!$q->ok){writelogs("SQL ERROR $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}

}
function numberFormat  ($number  , $decimals = 2 , $dec_point = '.' , $sep = ',', $group=3   ){
    $num = sprintf("%0.{$decimals}f",$number);
    $num = explode('.',$num);

    while (strlen($num[0]) % $group) $num[0]= ' '.$num[0];
    $num[0] = str_split($num[0],$group);
    $num[0] = join($sep[0],$num[0]);
    $num[0] = trim($num[0]);
    $num = @join($dec_point,$num);

    return $num;
}
function file_time_min_Web($path){
    if(!is_file($path)){
        if($GLOBALS["VERBOSE"]){echo "file_time_min() -> unable to stat $path\n";}
        return 100000;
    }
    $last_modified = filemtime($path);
    $data1 = $last_modified;
    $data2 = time();
    $difference = ($data2 - $data1);
    return round($difference/60);
}
function file_time_sec_Web($path){
    if(!is_file($path)){
        if($GLOBALS["VERBOSE"]){echo "file_time_sec_Web() -> unable to stat $path\n";}
        return 100000;
    }
    $last_modified = filemtime($path);
    $data1 = $last_modified;
    $data2 = time();
    $difference = ($data2 - $data1);
    return $difference;
}
function time_between_day_Web($xtime){
    $now = time(); // or your date as well
    $your_date = $xtime;
    $datediff = $your_date - $now ;
    return floor($datediff/(60*60*24));
}
function TCP_LIST_NICS_W(){
    $datas=explode("\n",@file_get_contents("/proc/net/dev"));
    foreach ( $datas as $num=>$line ){
        if($GLOBALS["VERBOSE"]){echo "<br>TCP_LIST_NICS: Found: $line\n";}
        if(preg_match("#^(.+?):#",$line,$re)){
            if(trim($re[1])=="lo"){continue;}
            if(preg_match("#pan[0-9]+#",$re[1])){continue;}
            if(preg_match("#tun[0-9]+#",$re[1])){continue;}
            if(preg_match("#docker[0-9]+#",$re[1])){continue;}
            if(preg_match("#vboxnet[0-9]+#",$re[1])){continue;}
            if(preg_match("#wmaster[0-9]+#",$re[1])){continue;}
            if(preg_match('#master#',$re[1])){continue;}
            if(preg_match("#^veth.+?#",$re[1])){continue;}
            if(preg_match("#^tunl[0-9]+#",$re[1])){continue;}
            if(preg_match("#^dummy[0-9]+#",$re[1])){continue;}
            if(preg_match("#^gre[0-9]+#",$re[1])){continue;}
            if(preg_match("#^ip6tnl[0-9]+#",$re[1])){continue;}
            if(preg_match("#^sit[0-9]+#",$re[1])){continue;}
            if(preg_match("#^vlan[0-9]+#",$re[1])){continue;}
            if(preg_match("#^virt[0-9]+#",$re[1])){continue;}
            if(preg_match("#-ifb$#", $re[1])){continue;}
            if(preg_match("#^mirror#", $re[1])){continue;}
            if(preg_match("#^spaneth#", $re[1])){continue;}
            $re[1]=trim($re[1]);
            $array[]=trim($re[1]);
        }
    }
    return	$array;

}
function CACHE_SESSION_GET($function,$file,$maxtimeMin=30){
    if(!isset($_SESSION[$file][$function])){return CACHE_SESSION_GETF("$function",$file,$maxtimeMin);}
    $time=$_SESSION[$file][$function]["time"];
    $datas=$_SESSION[$file][$function]["datas"];
    if(!is_numeric($time)){return;}
    if(strlen($datas)==0){return;}
    $distanceInSeconds = round(abs(time() - $time));
    $distanceInMinutes = round($distanceInSeconds / 60);
    if($distanceInMinutes>$maxtimeMin){unset($_SESSION[$file][$function]);return false;}
    echo $datas;
    return true;

}
function CACHE_SESSION_GETF($function,$file,$maxtimeMin=30){
    $md5=md5("$file$function{$_SESSION["uid"]}");
    $cachefile=PROGRESS_DIR."/cache/{$_SESSION["uid"]}.$md5.cache";
    if(!is_file($cachefile)){return false;}
    if(file_get_time_min($cachefile)>$maxtimeMin){@unlink($cachefile);return false;}
    $datas=@file_get_contents($cachefile);
    if(strlen($datas)<5){return false;}
    echo $datas;return true;
}
function table_error_showZoom($error,$underline=1){
    $full=base64_encode($error);
    if($underline==1){$underline_js="text-decoration:underline";}
    return "<a href=\"javascript:Blurz();\" 
	OnClick=\"Loadjs('admin.index.php?json-error-js=$full');\" 
	style=\"$underline_js\">$error<a>";
}
function json_error_show($error,$indexColumn=0,$full=false){

    $trace=@debug_backtrace();
    if(is_array($trace)){
        $filename=basename($trace[1]["file"]);
        $function=$trace[1]["function"];
        $line=$trace[0]["line"];
    }

    $error=str_replace("no data", "{this_request_contains_no_data}", $error);
    $error=str_replace("No data", "{this_request_contains_no_data}", $error);
    $error=str_replace("Not found...", "{this_request_contains_no_data}", $error);
    $error=str_replace("no item", "{this_request_contains_no_data}", $error);
    $error=str_replace("no schedule", "{no_schedule}", $error);
    $error=str_replace("QUERY_SQL","MySQL Error<br>Script:$filename<br>function $function in line $line<br>", $error);

    $tpl=new templates();
    $id=md5($error);
    $error=$tpl->javascript_parse_text($error,1);
    $error=str_replace("\n", "<br>", $error);
    if(strlen($error)>10){
        $full=urlencode(base64_encode($error));
        if(!$full){$error="<a href=\"javascript:Blurz();\" OnClick=\"Loadjs('admin.index.php?json-error-js=$full');\" style=\"font-size:14px;color:#AC0909;text-decoration:underline\">".
            substr($error, 0,10)."...";
        }else{
            $error="<span style=\"font-size:12px;color:#AC0909;\">$error</span>";
        }

    }
    if(!is_numeric($indexColumn)){$indexColumn=0;}
    for($i=0;$i<13;$i++){
        $tf[$i]="&nbsp;";
    }
    $tf[$indexColumn]="<strong style=\"font-size:12px;color:#AC0909\">$error</a></strong>";
    for($i=0;$i<13;$i++){
        $finalArray[$i]=$tf[$i];

    }

    $cellerrornodata=array('id' => 1,'cell' => $finalArray);
    $data['page'] = 1;$data['total'] = 1;$data['rows'][] = $cellerrornodata;echo json_encode($data);
    die("DIE " .__FILE__." Line: ".__LINE__);


}
function CACHE_SESSION_SET($function,$file,$datas){
    $_SESSION[$file][$function]["time"]=time();
    $_SESSION[$file][$function]["datas"]=$datas;
    $md5=md5("$file$function{$_SESSION["uid"]}");
    @file_put_contents(PROGRESS_DIR."/cache/$md5.cache", $datas);
    echo $datas;
}
function internal_load(){
    if(!function_exists('sys_getloadavg')){return 0;}
    $array_load=sys_getloadavg();
    return $array_load[0];
}
function SimpleXMLElementToArray(SimpleXMLElement $xml) {
    $array = (array)$xml;

    foreach ( array_slice($array, 0) as $key => $value ) {
        if ( $value instanceof SimpleXMLElement ) {
            $array[$key] = empty($value) ? NULL : SimpleXMLElementToArray($value);
        }
    }
    return $array;
}
function FATAL_ERROR_SHOW_128($text,$noform=false,$nomargin=false){
    $tpl=new templates();
    $margin=null;
    $width="95%";
    $classform="class=form";
    if($noform){$classform=null;$width="99%";}
    if(!$nomargin){$margin="margin:30px";}
    $text=$tpl->_ENGINE_parse_body($text);
    $html="
	<center style='$margin'>
	<div style='width:95%' $classform>
	<table style='width:{$width}'>
	<tr>
		<td valign='top' style='width:1%'>
			<img src='img/error-128.png'>
		</td>
		<td><div style='font-size:18px;color:#d32d2d'>$text</div></td>
	</tr>
	</table>
	</div>
	</center>
	";
    return $tpl->_ENGINE_parse_body($html);


}
function FATAL_ERROR_SHOW_128_DESIGN($title,$text,$javascript=null){
    $tpl=new templates();
    $margin=null;
    $width="95%";
    $button=null;

    if($javascript<>null){
        $button="<div style='text-align:right;margin-bottom:20px'>".button("{goto}",$javascript,28)."</div>";

    }

    $text=$tpl->_ENGINE_parse_body($text);
    $html="
	<center style='margin:10px'>
	<div style='width:95%;border: 2px solid #d32d2d;border-radius: 5px 5px 5px 5px;'>
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:140px'>
	<center style='margin:5px'><img src='img/att-red-128.png'></center>
	</td>
	<td style='vertical-align:top;padding:10px'>
		<div style='font-size:28px;color:#d32d2d;font-weight:bold;margin-bottom:20px'>$title</div>
		<div style='font-size:22px;color:#d32d2d;font-weight:bold;margin-bottom:20px'>$text</div>
		$button
	</td>
	</tr>
	</table>
	</div>
	</center>
	";
    return $tpl->_ENGINE_parse_body($html);


}
function CheckVerbose(){
    if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
    if(isset($_GET["verbose"]) OR $GLOBALS["VERBOSE"]){
        if($GLOBALS["posix_getuid"]>0) {
            if (!is_file(dirname(__FILE__) . "/VERBOSE")) {
                if (!isset($_SESSION["uid"])) {
                    http_response_code(403);
                    echo "<H1 style='margin-top:50px;margin-left:50px'>403 Forbidden: You do not have permission to access this resource.</H1>";
                    die();
                }
                echo file_get_contents("ressources/databases/verbose.txt");
                die();
            }
        }
    }
}
function debug_mem($text=null){
    if(!function_exists("writelogs")){include_once(dirname(__FILE__) . '/logs.inc');}



    $calledscript=null;$functioncript=null;$linescript=null;
    if(!isset($GLOBALS["DEBUG_MEM"])){return;}
    if(!$GLOBALS["DEBUG_MEM"]){return;}
    $memory_get_usage=memory_get_usage(true);
    $ko=round($memory_get_usage/1024,2);
    $trace=debug_backtrace();

    $line=$trace[0]["line"];
    $file=basename($trace[0]["file"]);
    if(isset($trace[1]["file"])){$calledscript=basename($trace[1]["file"]);}
    if(isset($trace[1]["line"])){$linescript=basename($trace[1]["line"]);}
    if(isset($trace[1]["function"])){$functioncript=basename($trace[1]["function"]);}
    unset($trace);

    if(isset($GLOBALS["DEBUG_INCLUDES"])){
        writelogs("Memory: {$ko}Ko line:$line of $file (called by $calledscript in $linescript func: $functioncript())\n",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
    }


    echo "Memory: {$ko}Ko line:$line of $file (called by $calledscript in $linescript func: $functioncript())\n<br>";




}
function lic_generate_string($string){
    $ascii=NULL;
    $serial=NULL;
    $secret_num=5;
    for ($i = 0; $i < strlen($string); $i++)
    {
        $ascii .= $secret_num+ ord($string[$i]);
    }
    $ascii=substr($ascii,0,20);
    for ($i = 0; $i < strlen($ascii); $i+=2)
    {
        $string=substr($ascii,$i,2);
        switch($string)
        {
            case $string>122:
                $string-=40;
                break;
            case $string<=48:
                $string+=40;
                break;
        }
        $serial .= chr($string);
    }
    return $serial;
}
function senderrors($text){sendserrors($text);}
function senderror($text){sendserrors($text);}
function sendserrors($text){
    if(strtolower(trim($text))=="no data"){$text="{this_request_contains_no_data}";}
    if(strtolower(trim($text))=="no rights"){$text="{ERROR_NO_PRIVS}";}
    if(strtolower(trim($text))=="no privs"){$text="{ERROR_NO_PRIVS}";}
    if(strtolower(trim($text))=="no priv"){$text="{ERROR_NO_PRIVS}";}
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body("<p class=text-error style='margin:20px'>$text</p>");exit();
}

function ip2Long2($dottedFormatAddress){
    return  sprintf("%u", ip2long($dottedFormatAddress));

}
function strip_bad_characters($servername){
    $servername=replace_accents($servername);
    $servername=str_replace('/', '.', $servername);
    $servername=str_replace('\\', '.', $servername);
    $servername=str_replace(' ', '.', $servername);
    $servername=str_replace('$', '.', $servername);
    $servername=str_replace('!', '.', $servername);
    $servername=str_replace('(', '.', $servername);
    $servername=str_replace(')', '.', $servername);
    $servername=str_replace('[', '.', $servername);
    $servername=str_replace(']', '.', $servername);
    $servername=str_replace('}', '.', $servername);
    $servername=str_replace('{', '.', $servername);
    $servername=str_replace('&', '.', $servername);
    $servername=str_replace('|', '.', $servername);
    $servername=str_replace('^', '.', $servername);
    $servername=str_replace('@', '.', $servername);
    $servername=str_replace('=', '.', $servername);
    $servername=str_replace('+', '.', $servername);
    $servername=str_replace('?', '.', $servername);
    $servername=str_replace('*', '.', $servername);
    $servername=str_replace('#', '.', $servername);
    $servername=str_replace('"', '.', $servername);
    $servername=str_replace('%', '.', $servername);
    $servername=str_replace('*', '.', $servername);
    $servername=str_replace(',', '.', $servername);
    $servername=str_replace(',', '.', $servername);
    $servername=str_replace(';', '.', $servername);
    $servername=str_replace(':', '.', $servername);
    $servername=str_replace('..', '.', $servername);
    $servername=str_replace('..', '.', $servername);
    $servername=str_replace('..', '.', $servername);
    $servername=str_replace('..', '.', $servername);
    $servername=str_replace('...', '.', $servername);
    return $servername;

}
function squid_stats_default_values(){
    if(!isset($_SESSION["SQUID_STATS_MEMBER"])){$_SESSION["SQUID_STATS_MEMBER"]="IPADDR";}
    if(!isset($_SESSION["SQUID_STATS_MEMBER_SEARCH"])){$_SESSION["SQUID_STATS_MEMBER_SEARCH"]="*";}
    if(!isset($_SESSION["SQUID_STATS_DATE1"])){$_SESSION["SQUID_STATS_DATE1"]=date("Y-m-d");}
    if(!isset($_SESSION["SQUID_STATS_TIME1"])){$_SESSION["SQUID_STATS_TIME1"]="00:00";}
    if(!isset($_SESSION["SQUID_STATS_DATE2"])){$_SESSION["SQUID_STATS_DATE2"]=date("Y-m-d");}
    if(!isset($_SESSION["SQUID_STATS_TIME2"])){$_SESSION["SQUID_STATS_TIME2"]="23:00";}
    if(!isset($_SESSION["SQUID_STATS_MAX_LINES"])){$_SESSION["SQUID_STATS_MAX_LINES"]=100;}
}
function InfluxToTime($influxtime){
    if(preg_match("#^(.+?)T(.+?)\.[0-9]+Z$#", $influxtime,$re)){return QueryFromUTC(strtotime("{$re[1]} {$re[2]}"));}
    if(preg_match("#^(.+?)T(.+?)Z$#", $influxtime,$re)){return QueryFromUTC(strtotime("{$re[1]} {$re[2]}"));}

}
function QueryFromUTC($time){
    if(!isset($GLOBALS["QueryFromUTC"])){$GLOBALS["QueryFromUTC"]=_QueryFromUTC();}
    return strtotime(date("Y-m-d H:i:s",$time)." {$GLOBALS["QueryFromUTC"]} seconds");


}
function _QueryFromUTC(){
    $UTC_TIME=strtotime(gmdate("Y-m-d H:i:s"));
    $CURRENTIME=time();
    if($CURRENTIME>$UTC_TIME){
        $diff=$CURRENTIME-$UTC_TIME;
        $sep="+";
    }
    if($UTC_TIME>$CURRENTIME){
        $diff=$UTC_TIME-$CURRENTIME;
        $sep="-";
    }

    return "$sep$diff";

}
function InfluxQueryFromUTC($time){return QueryToUTC($time);}
function QueryToUTC($time){
    $timestamp =$time+date("Z");
    return strtotime(gmdate("Y-m-d H:i:s",$timestamp));
}


function webconsole_syslog($text=null,$file=null,$error=false){
    if($text==null){return false;}
    $LOG_SEV=LOG_INFO;
    if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
    $text="[$IPADDR]: $text";
    if(function_exists("openlog")){openlog("webconsole", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
    return true;
}
function ExecTBtime($start,$text,$line){
    if(!isset($GLOBALS["VERBOSE"])){return;}
    $end = microtime(true);
    $executionTime = $end - $start;
    VERBOSE_SRCTEMPLATE("($text) - class.templates.inc Execution time ".number_format($executionTime, 4) . " seconds",$line);
}
function VERBOSE_SRCTEMPLATE($text,$line=0,$forceFunc=""):bool{
    $function=null;$file=null;
    if(!$GLOBALS["VERBOSE"]){return false;}
    if(!isset($GLOBALS["AS_ROOT"])){$GLOBALS["AS_ROOT"]=false;}
    if(!isset($GLOBALS["posix_getuid"])){
        $GLOBALS["posix_getuid"]=1000;
        if(function_exists("posix_getuid")){
            $GLOBALS["posix_getuid"]=posix_getuid();
        }
    }

    if($GLOBALS["posix_getuid"]==0){$GLOBALS["AS_ROOT"]=true;}


    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            $file=basename($trace[1]["file"]);
            if(isset($trace[1]["function"])){$function=$trace[1]["function"];}
            if(isset($trace[1]["class"])){$function="{$trace[1]["class"]}/$function";}
        }
    }

    if($GLOBALS["AS_ROOT"]){echo "[L.$line]: $text\n";return true;}
    $date=date("I:s:ss");


    $func="{$function}[$line]";
    if(strlen($forceFunc)>2){
        $func=$forceFunc;
    }
    echo "<div style='font-size:14px;font-weight:bold;background-color:white;color:black'><span style=color:red>$date&nbsp;$func:</span>&nbsp;$text&nbsp;<small>($file)</small></div>\n";
    return true;
}

function safe_session_start():bool{
    static $hasLoggedRedisError = false;
    if (session_status() == PHP_SESSION_ACTIVE) {
        return true;
    }
    $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    $cookie_lifetime = $SessionCookieLifetime == 0 ? 0 : $SessionCookieLifetime * 60;

    try {
        ini_set("session.save_handler", "redis");
        ini_set("session.gc_probability", 1);
        ini_set("session.gc_divisor", 1000); // avoid frequent GC
        ini_set("session.gc_maxlifetime", 1440);
        ini_set("session.save_path", "unix:///run/redis/redis.sock?persistent=1&database=0&timeout=2.5");
        if (!session_start(['cookie_httponly' => 1,
            'use_only_cookies' => 1, 'cookie_lifetime' => $cookie_lifetime])) {
            error_log("Redis session start failed");
            throw new Exception("Redis session start failed");
        }
    } catch (Throwable $e) {
        ini_set("session.save_handler", "files");
        ini_set("session.save_path", "/var/lib/php5");
        session_start(['cookie_httponly' => 1,
            'use_only_cookies' => 1, 'cookie_lifetime' => $cookie_lifetime]);
        if (!$hasLoggedRedisError) {
            error_log("Redis down, fallback to file sessions: " . $e->getMessage());
            $hasLoggedRedisError = true;
        }

    }
    return true;

}