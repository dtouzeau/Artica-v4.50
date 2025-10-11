<?php
include_once(dirname(__FILE__) . "/class.templates.manager.inc");
include_once(dirname(__FILE__) . "/class.webconsole.params.inc");
include_once(dirname(__FILE__)."/class.template-admin.inc");
class template_simple
{
    public $SquidHTTPTemplateNoVersion = 0;
    public $SquidHTTPTemplateBackgroundColor = "#8c1919";
    public $SquidHTTPTemplateFamily = "Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
    public $SquidHTTPTemplateFontColor = "#FFFFFF";
    public $SquidHTTPTemplateEnablePostmaster = 1;
    public $SquidHTTPTemplateDisableHostname;
    public $SquidHTTPTemplateLanguage;
    public $SquidHTTPTemplateNoProxyVersion = 0;

    public $SquidFTPTemplateNoVersion = 0;
    public $SquidFTPTemplateBackgroundColor = "#FFFFF";
    public $SquidFTPTemplateFamily = "Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
    public $SquidFTPTemplateFontColor = "#000000";
    public $SquidFTPTemplateEnablePostmaster = 1;
    public $SquidFTPTemplateDisableHostname;
    public $SquidFTPTemplateLanguage;
    public $SquidFTPTemplateSmiley = "0040";


    public $SquidADSTemplateNoVersion = 0;
    public $SquidADSTemplateBackgroundColor = "#8c1919";
    public $SquidADSTemplateFamily = "Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
    public $SquidADSTemplateFontColor = "#FFFFFF";
    public $SquidADSTemplateEnablePostmaster = 1;
    public $SquidADSTemplateDisableHostname;
    public $SquidADSTemplateLanguage;
    public $SquidADSTemplateSmiley = "2639";

    public $SquidCICAPTemplateNoVersion = 0;
    public $SquidCICAPTemplateBackgroundColor = "#8c1919";
    public $SquidCICAPTemplateFamily = "Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
    public $SquidCICAPTemplateFontColor = "#FFFFFF";
    public $SquidCICAPTemplateEnablePostmaster = 1;
    public $SquidCICAPTemplateDisableHostname;
    public $SquidCICAPTemplateLanguage;
    public $SquidCICAPTemplateSmiley = "2639";

    public $SquidCicapBlockTemplateNoVersion = 0;
    public $SquidCicapBlockTemplateBackgroundColor = "#8c1919";
    public $SquidCicapBlockTemplateFamily = "Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
    public $SquidCicapBlockTemplateFontColor = "#FFFFFF";
    public $SquidCicapBlockTemplateEnablePostmaster = 1;
    public $SquidCicapBlockTemplateDisableHostname;
    public $SquidCicapBlockTemplateLanguage;
    public $SquidCicapBlockTemplateSmiley = "2639";


    public $TITLE = null;
    public $BODY = null;
    public $TEMPLATE_NAME = null;
    public $lang = null;
    public $arrayxLangs = array();
    private $TemplateConfig = array();


    function __construct($TEMPLATE_NAME = null, $lang = null)
    {
        $this->languages();
        $this->OthersTemplates();
        if ($TEMPLATE_NAME <> null) {
            $this->TEMPLATE_NAME = $TEMPLATE_NAME;
            $this->lang = $lang;
            $this->loadTemplate();
        }

    }

    private function languages()
    {
        $this->arrayxLangs["fr"][] = "fr-ca";
        $this->arrayxLangs["fr"][] = "fr-mc";
        $this->arrayxLangs["fr"][] = "fr-fr";
        $this->arrayxLangs["fr"][] = "fr-lu";
        $this->arrayxLangs["fr"][] = "fr-be";
        $this->arrayxLangs["fr"][] = "fr-ch";
        $this->arrayxLangs["fr"][] = "French";
        $this->arrayxLangs["es"][] = "es-ve";
        $this->arrayxLangs["es"][] = "es-gt";
        $this->arrayxLangs["es"][] = "es-ar";
        $this->arrayxLangs["es"][] = "es-py";
        $this->arrayxLangs["es"][] = "es-cr";
        $this->arrayxLangs["es"][] = "es-ni";
        $this->arrayxLangs["es"][] = "es-pe";
        $this->arrayxLangs["es"][] = "es-pa";
        $this->arrayxLangs["es"][] = "es-pr";
        $this->arrayxLangs["es"][] = "es-uy";
        $this->arrayxLangs["es"][] = "es-sv";
        $this->arrayxLangs["es"][] = "es-es";
        $this->arrayxLangs["es"][] = "es-bo";
        $this->arrayxLangs["es"][] = "es-mx";
        $this->arrayxLangs["es"][] = "es-hn";
        $this->arrayxLangs["es"][] = "es-cl";
        $this->arrayxLangs["es"][] = "es-do";
        $this->arrayxLangs["es"][] = "es-co";
        $this->arrayxLangs["es"][] = "es-ec";

        $this->arrayxLangs["en"][] = "en-us";
        $this->arrayxLangs["en"][] = "en-au";
        $this->arrayxLangs["en"][] = "en-sg";
        $this->arrayxLangs["en"][] = "en-nz";
        $this->arrayxLangs["en"][] = "en-bz";
        $this->arrayxLangs["en"][] = "en-gb";
        $this->arrayxLangs["en"][] = "en-ie";
        $this->arrayxLangs["en"][] = "en-uk";
        $this->arrayxLangs["en"][] = "en-tt";
        $this->arrayxLangs["en"][] = "en-jm";
        $this->arrayxLangs["en"][] = "en-za";
        $this->arrayxLangs["en"][] = "en-ph";
        $this->arrayxLangs["en"][] = "en-ca";
        $this->arrayxLangs["en"][] = "en-in";
        $this->arrayxLangs["en"][] = "en-zw";
        $this->arrayxLangs["en"][] = "uk";
        $this->arrayxLangs["en"][] = "uk-ua";

        $this->arrayxLangs["ar"][] = "ar-dz";
        $this->arrayxLangs["ar"][] = "ar-tn";
        $this->arrayxLangs["ar"][] = "es-ar";
        $this->arrayxLangs["ar"][] = "ar-ma";
        $this->arrayxLangs["ar"][] = "ar-iq";
        $this->arrayxLangs["ar"][] = "ar-ae";
        $this->arrayxLangs["ar"][] = "ar-ly";
        $this->arrayxLangs["ar"][] = "ar-jo";
        $this->arrayxLangs["ar"][] = "ar-sa";
        $this->arrayxLangs["ar"][] = "ar-sy";
        $this->arrayxLangs["ar"][] = "ar-ye";
        $this->arrayxLangs["ar"][] = "ar-eg";
        $this->arrayxLangs["ar"][] = "ar-bh";
        $this->arrayxLangs["ar"][] = "ar-om";
        $this->arrayxLangs["ar"][] = "ar-kw";
        $this->arrayxLangs["ar"][] = "ar-qa";
        $this->arrayxLangs["ar"][] = "ar-lb";
        $this->arrayxLangs["zh"][] = "zh-mo";
        $this->arrayxLangs["zh"][] = "zh-hant-mo";
        $this->arrayxLangs["zh"][] = "zh-hant-tw";
        $this->arrayxLangs["zh"][] = "zh-hans-cn";
        $this->arrayxLangs["zh"][] = "zh-hans";
        $this->arrayxLangs["zh"][] = "zh-tw";
        $this->arrayxLangs["zh"][] = "zh-hant-hk";
        $this->arrayxLangs["zh"][] = "zh-cn";
        $this->arrayxLangs["zh"][] = "zh-hans-sg";
        $this->arrayxLangs["zh"][] = "zh-sg";
        $this->arrayxLangs["zh"][] = "zh-hk";
        $this->arrayxLangs["zh"][] = "zh-hant";
        $this->arrayxLangs["pt"][] = "pt-br";
        $this->arrayxLangs["pt"][] = "pt-pt";
        $this->arrayxLangs["pt"][] = "pt-bz";
        $this->arrayxLangs["sr"][] = "sr-cyrl-rs";
        $this->arrayxLangs["sr"][] = "sr-latn-rs";
        $this->arrayxLangs["sr"][] = "sr-sp";
        $this->arrayxLangs["sr"][] = "sr-cyrl";
        $this->arrayxLangs["sr"][] = "sr-latn-me";
        $this->arrayxLangs["sr"][] = "sr-rs";
        $this->arrayxLangs["sr"][] = "sr-latn-cs";
        $this->arrayxLangs["sr"][] = "sr-latn";
        $this->arrayxLangs["sr"][] = "sr-cyrl-me";
        $this->arrayxLangs["sr"][] = "sr-cyrl-cs";
        $this->arrayxLangs["nl"][] = "nl-nl";
        $this->arrayxLangs["pl"][] = "pl-pl";
        $this->arrayxLangs["ru"][] = "ru-ru";
        $this->arrayxLangs["hu"][] = "hu-hu";
        $this->arrayxLangs["id"][] = "id-id";
        $this->arrayxLangs["th"][] = "th-th";
        $this->arrayxLangs["tr"][] = "tr-tr";
        $this->arrayxLangs["vi"][] = "vi-vn";
        $this->arrayxLangs["fi"][] = "fi-fi";
        $this->arrayxLangs["lt"][] = "lt-lt";
        $this->arrayxLangs["lv"][] = "lv-lv";
        $this->arrayxLangs["az"][] = "az-az";
        $this->arrayxLangs["bg"][] = "bg-bg";

        $this->arrayxLangs["sl"][] = "sl-si";
        $this->arrayxLangs["sk"][] = "sk-sk";
        $this->arrayxLangs["hy"][] = "hy-am";
        $this->arrayxLangs["hy"][] = "hy-armn";

        $this->arrayxLangs["it"][] = "it-it";
        $this->arrayxLangs["it"][] = "it-ch";

        $this->arrayxLangs["sv"][] = "sv-fi";
        $this->arrayxLangs["sv"][] = "sv-se";

        $this->arrayxLangs["ro"][] = "ro-md";
        $this->arrayxLangs["ro"][] = "ro-ro";

        $this->arrayxLangs["fa"][] = "fa-ir";
        $this->arrayxLangs["fa"][] = "fa-fa";

        $this->arrayxLangs["ja"][] = "ja-jp";
        $this->arrayxLangs["ja"][] = "ja-ja";

        $this->arrayxLangs["ko"][] = "ko-kp";
        $this->arrayxLangs["ko"][] = "ko-kr";
        $this->arrayxLangs["ko"][] = "ko-ko";

        $this->arrayxLangs["et"][] = "et-ee";
        $this->arrayxLangs["et"][] = "et-et";

        $this->arrayxLangs["he"][] = "he-il";
        $this->arrayxLangs["he"][] = "he-he";

        $this->arrayxLangs["ms"][] = "ms-my";
        $this->arrayxLangs["ms"][] = "ms-ms";

        $this->arrayxLangs["de"][] = "de-at";
        $this->arrayxLangs["de"][] = "de-de";
        $this->arrayxLangs["de"][] = "de-lu";
        $this->arrayxLangs["de"][] = "de-ch";
        $this->arrayxLangs["de"][] = "de-li";

        $this->arrayxLangs["el"][] = "el-gr";
        $this->arrayxLangs["el"][] = "el-el";

        $this->arrayxLangs["cs"][] = "cs-cz";
        $this->arrayxLangs["cs"][] = "cs-cs";

        $this->arrayxLangs["da"][] = "da-dk";
        $this->arrayxLangs["da"][] = "da-da";
    }

    private function loadTemplate()
    {
        $cache_mgr_user = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("cache_mgr_user");
        $SquidHTTPTemplateNoVersion = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateNoVersion"));
        $SquidHTTPTemplateBackgroundColor = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateBackgroundColor");
        $SquidHTTPTemplateDisableHostname = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateDisableHostname"));
        $SquidHTTPTemplateEnablePostmaster = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateEnablePostmaster");
        $SquidHTTPTemplateFontColor = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateFontColor");
        $SquidHTTPTemplateFamily = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateFamily");
        $SquidHTTPTemplateNoProxyVersion = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateNoProxyVersion"));


        $SquidFTPTemplateEnablePostmaster = 1;
        $SquidFTPTemplateNoVersion = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPTemplateNoVersion"));
        $SquidFTPTemplateBackgroundColor = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPTemplateBackgroundColor");
        $SquidFTPTemplateDisableHostname = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPTemplateDisableHostname"));
        $SquidFTPTemplateEnablePostmaster = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPTemplateEnablePostmaster");
        $SquidFTPTemplateFontColor = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPTemplateFontColor");
        $SquidFTPTemplateFamily = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPTemplateFamily");
        $SquidFTPTemplateSmiley = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFTPTemplateSmiley"));
        $SquidADSTemplateEnablePostmaster = 1;
        $SquidADSTemplateNoVersion = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidADSTemplateNoVersion"));
        $SquidADSTemplateBackgroundColor = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidADSTemplateBackgroundColor");
        $SquidADSTemplateDisableHostname = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidADSTemplateDisableHostname"));
        $SquidADSTemplateEnablePostmaster = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidADSTemplateEnablePostmaster");
        $SquidADSTemplateFontColor = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidADSTemplateFontColor");
        $SquidADSTemplateFamily = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidADSTemplateFamily");
        $SquidADSTemplateSmiley = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidADSTemplateSmiley"));

        $SquidCICAPTemplateEnablePostmaster = 1;
        $SquidCICAPTemplateNoVersion = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCICAPTemplateNoVersion"));
        $SquidCICAPTemplateBackgroundColor = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCICAPTemplateBackgroundColor");
        $SquidCICAPTemplateDisableHostname = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCICAPTemplateDisableHostname"));
        $SquidCICAPTemplateEnablePostmaster = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCICAPTemplateEnablePostmaster");
        $SquidCICAPTemplateFontColor = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCICAPTemplateFontColor");
        $SquidCICAPTemplateFamily = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCICAPTemplateFamily");
        $SquidCICAPTemplateSmiley = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCICAPTemplateSmiley"));


        if ($SquidHTTPTemplateBackgroundColor == null) {
            $SquidHTTPTemplateBackgroundColor = "#8c1919";
        }
        if ($SquidHTTPTemplateFamily == null) {
            $SquidHTTPTemplateFamily = "Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
        }
        if (!is_numeric($SquidHTTPTemplateEnablePostmaster)) {
            $SquidHTTPTemplateEnablePostmaster = 1;
        }
        if ($SquidHTTPTemplateFontColor == null) {
            $SquidHTTPTemplateFontColor = "#FFFFFF";
        }
        if (!preg_match("#^\##", $SquidHTTPTemplateFontColor)) {
            $SquidHTTPTemplateFontColor = "#$SquidHTTPTemplateFontColor";
        }

        $this->SquidHTTPTemplateNoVersion = $SquidHTTPTemplateNoVersion;
        $this->SquidHTTPTemplateNoProxyVersion = $SquidHTTPTemplateNoProxyVersion;
        $this->SquidHTTPTemplateBackgroundColor = $SquidHTTPTemplateBackgroundColor;
        $this->SquidHTTPTemplateFamily = $SquidHTTPTemplateFamily;
        $this->SquidHTTPTemplateFontColor = $SquidHTTPTemplateFontColor;
        $this->SquidHTTPTemplateEnablePostmaster = $SquidHTTPTemplateEnablePostmaster;
        $this->SquidHTTPTemplateDisableHostname = $SquidHTTPTemplateDisableHostname;


        if ($SquidFTPTemplateBackgroundColor == null) {
            $SquidFTPTemplateBackgroundColor = "#FFFFF";
        }
        if ($SquidFTPTemplateFamily == null) {
            $SquidFTPTemplateFamily = "Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
        }
        if (!is_numeric($SquidFTPTemplateEnablePostmaster)) {
            $SquidFTPTemplateEnablePostmaster = 1;
        }
        if ($SquidFTPTemplateFontColor == null) {
            $SquidFTPTemplateFontColor = "#00000";
        }
        if (!preg_match("#^\##", $SquidFTPTemplateFontColor)) {
            $SquidFTPTemplateFontColor = "#$SquidFTPTemplateFontColor";
        }
        if ($SquidFTPTemplateSmiley == null) {
            $SquidFTPTemplateSmiley = "0040";
        }


        if ($SquidADSTemplateBackgroundColor == null) {
            $SquidADSTemplateBackgroundColor = "#8c1919";
        }
        if ($SquidADSTemplateFamily == null) {
            $SquidADSTemplateFamily = "Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
        }
        if (!is_numeric($SquidADSTemplateEnablePostmaster)) {
            $SquidADSTemplateEnablePostmaster = 1;
        }
        if ($SquidADSTemplateFontColor == null) {
            $SquidADSTemplateFontColor = "#FFFFFF";
        }
        if (!preg_match("#^\##", $SquidADSTemplateFontColor)) {
            $SquidADSTemplateFontColor = "#$SquidADSTemplateFontColor";
        }
        if ($SquidADSTemplateSmiley == null) {
            $SquidADSTemplateSmiley = "2639";
        }

        if ($SquidCICAPTemplateBackgroundColor == null) {
            $SquidCICAPTemplateBackgroundColor = "#8c1919";
        }
        if ($SquidCICAPTemplateFamily == null) {
            $SquidCICAPTemplateFamily = "Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
        }
        if (!is_numeric($SquidCICAPTemplateEnablePostmaster)) {
            $SquidCICAPTemplateEnablePostmaster = 1;
        }
        if ($SquidCICAPTemplateFontColor == null) {
            $SquidCICAPTemplateFontColor = "#FFFFFF";
        }
        if (!preg_match("#^\##", $SquidCICAPTemplateFontColor)) {
            $SquidCICAPTemplateFontColor = "#$SquidCICAPTemplateFontColor";
        }
        if ($SquidCICAPTemplateSmiley == null) {
            $SquidCICAPTemplateSmiley = "2639";
        }


        $this->SquidFTPTemplateNoVersion = $SquidFTPTemplateNoVersion;
        $this->SquidFTPTemplateBackgroundColor = $SquidFTPTemplateBackgroundColor;
        $this->SquidFTPTemplateFamily = $SquidFTPTemplateFamily;
        $this->SquidFTPTemplateFontColor = $SquidFTPTemplateFontColor;
        $this->SquidFTPTemplateEnablePostmaster = $SquidFTPTemplateEnablePostmaster;
        $this->SquidFTPTemplateDisableHostname = $SquidFTPTemplateDisableHostname;
        $this->SquidFTPTemplateSmiley = $SquidFTPTemplateSmiley;

        $this->SquidADSTemplateNoVersion = $SquidADSTemplateNoVersion;
        $this->SquidADSTemplateBackgroundColor = $SquidADSTemplateBackgroundColor;
        $this->SquidADSTemplateFamily = $SquidADSTemplateFamily;
        $this->SquidADSTemplateFontColor = $SquidADSTemplateFontColor;
        $this->SquidADSTemplateEnablePostmaster = $SquidADSTemplateEnablePostmaster;
        $this->SquidADSTemplateDisableHostname = $SquidADSTemplateDisableHostname;
        $this->SquidADSTemplateSmiley = $SquidADSTemplateSmiley;

        $this->SquidCICAPTemplateNoVersion = $SquidCICAPTemplateNoVersion;
        $this->SquidCICAPTemplateBackgroundColor = $SquidCICAPTemplateBackgroundColor;
        $this->SquidCICAPTemplateFamily = $SquidCICAPTemplateFamily;
        $this->SquidCICAPTemplateFontColor = $SquidCICAPTemplateFontColor;
        $this->SquidCICAPTemplateEnablePostmaster = $SquidCICAPTemplateEnablePostmaster;
        $this->SquidCICAPTemplateDisableHostname = $SquidCICAPTemplateDisableHostname;
        $this->SquidCICAPTemplateSmiley = $SquidCICAPTemplateSmiley;

        $this->TemplateConfig = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TemplateConfig"));

        if(!is_array($this->TemplateConfig)){
            $this->TemplateConfig=array();
        }

        if (!isset($this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"])) {
            $this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"] = null;
        }
        if (!isset($this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"])) {
            $this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"] = null;
        }

        if (!isset($this->TemplateConfig[$this->lang]["ERR_CICAP_VIRUS"]["TITLE"])) {
            $this->TemplateConfig[$this->lang]["ERR_CICAP_VIRUS"]["TITLE"] = null;
        }
        if (!isset($this->TemplateConfig[$this->lang]["ERR_CICAP_VIRUS"]["BODY"])) {
            $this->TemplateConfig[$this->lang]["ERR_CICAP_VIRUS"]["BODY"] = null;
        }


        if (!isset($this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["TITLE"])) {
            $this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["TITLE"] = null;
        }
        if (!isset($this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["BODY"])) {
            $this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["BODY"] = null;
        }


        $this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"] = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidADSTemplateTitle");
        $this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"] = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidADSTemplateBody");

        $this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["TITLE"] = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCicapBlockTemplateTitle");
        $this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["BODY"] = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCicapBlockTemplateBody");


        if ($this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"] == null) {
            $this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["TITLE"] = "Request blocked";
        }
        if ($this->TemplateConfig[$this->lang]["ERR_CICAP_VIRUS"]["TITLE"] == null) {
            $this->TemplateConfig[$this->lang]["ERR_CICAP_VIRUS"]["TITLE"] = "Malware %VVN found in document";
        }

        if ($this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["TITLE"] == null) {
            $this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["TITLE"] = "Access Denied";
        }


        if ($this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"] == null) {
            $this->TemplateConfig[$this->lang]["ERR_ADS_BLOCK"]["BODY"] = "
			<h1>ERROR</h1>
			<h2>Request blocked</h2>
			</div>
			<hr>
			
			<div id=\"content\">
			<p>The following error was encountered while trying to retrieve the URL: <a href=\"@protocol@@hostport@@path@\">@protocol@@hostport@@path@</a></p>
			
			<blockquote id=\"error\">
			<p><b>Access Denied by &laquo;@block-reason@&raquo;</b></p>
			</blockquote>
			
			<p>Access control configuration prevents your request from being allowed at this time.
			Please contact your service provider if you feel this is incorrect.</p>
			
			<p>Your cache administrator is <a href=\"mailto:$cache_mgr_user\">$cache_mgr_user</a>.</p>
			<br>
			</div>
			
			<hr>
			<div id=\"footer\">
			<p>AntiAds Version number @version@</p>
			<!-- %c -->
			</div>";

        }


        if ($this->TemplateConfig[$this->lang]["ERR_CICAP_VIRUS"]["BODY"] == null) {
            $this->TemplateConfig[$this->lang]["ERR_CICAP_VIRUS"]["BODY"] = "
			<h1>Malware</h1>
			<h2>virus found in document</h2>
			</div>
			<hr>
				
			<div id=\"content\">
			<p>The requested URL cannot be provided: %VU</p>
				
			<blockquote id=\"error\">
			<p><b>Virus <strong>%VVN</strong> found by &laquo; %VVV &raquo;</b></p>
			</blockquote>
				
			<p>Access control configuration prevents your request from being allowed at this time.
			Please contact your service provider if you feel this is incorrect.</p>
			<br>
			</div>
				
			<hr>
			<div id=\"footer\">
			<center>Antivirus Version number %VVV</center>
			<!-- %c -->
			";

        }


        if ($this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["BODY"] == null) {
            $this->TemplateConfig[$this->lang]["ERR_CICAP_BLOCK"]["BODY"] = "
			<h1>Access Denied</h1>
			<h2>%UU</h2>
			</div>
			<hr>
		
			<div id=\"content\">
			<p>The requested URL cannot be provided: %VU</p>
		
			<blockquote id=\"error\">
			<p><b>Virus <strong>%UM</strong> %UB (%UD)</b></p>
			</blockquote>
		
			<p>Access control configuration prevents your request from being allowed at this time.
			Please contact your service provider if you feel this is incorrect.</p>
			<br>
			</div>
		
			<hr>
			<div id=\"footer\">
			<center>Web-filtering Version number %iu</center>
			<!-- %c -->
			";

        }


        if (!isset($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME])) {
            if ($GLOBALS["VERBOSE"]) {
                echo "<span style='color:#d32d2d'>Load default content</span><br>\n";
            }
            $this->defaultContent();
            return;
        }

        if ($GLOBALS["VERBOSE"]) {
            echo "$this->TEMPLATE_NAME: Build defaults -1- \n";
        }

        $TemplateChoose = $this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME];

        if (isset($TemplateChoose["SquidHTTPTemplateNoVersion"])) {
            if (is_numeric($TemplateChoose["SquidHTTPTemplateNoVersion"])) {
                $this->SquidHTTPTemplateNoVersion = $TemplateChoose["SquidHTTPTemplateNoVersion"];
                if ($GLOBALS["VERBOSE"]) {
                    echo "SquidHTTPTemplateNoVersion: $this->SquidHTTPTemplateNoVersion\n";
                }
            }
        }

        if (isset($TemplateChoose["SquidHTTPTemplateNoProxyVersion"])) {
            if (is_numeric($TemplateChoose["SquidHTTPTemplateNoProxyVersion"])) {
                $this->SquidHTTPTemplateNoVersion = $TemplateChoose["SquidHTTPTemplateNoProxyVersion"];
                if ($GLOBALS["VERBOSE"]) {
                    echo "SquidHTTPTemplateNoProxyVersion: $this->SquidHTTPTemplateNoProxyVersion\n";
                }
            }
        }


        if (isset($TemplateChoose["SquidHTTPTemplateEnablePostmaster"])) {
            if (is_numeric($TemplateChoose["SquidHTTPTemplateEnablePostmaster"])) {
                $this->SquidHTTPTemplateEnablePostmaster = $TemplateChoose["SquidHTTPTemplateEnablePostmaster"];
                if ($GLOBALS["VERBOSE"]) {
                    echo "SquidHTTPTemplateEnablePostmaster: $this->SquidHTTPTemplateEnablePostmaster\n";
                }
            }
        }

        if (isset($TemplateChoose["SquidHTTPTemplateDisableHostname"])) {
            if (is_numeric($TemplateChoose["SquidHTTPTemplateDisableHostname"])) {
                $this->SquidHTTPTemplateDisableHostname = $TemplateChoose["SquidHTTPTemplateDisableHostname"];
                if ($GLOBALS["VERBOSE"]) {
                    echo "SquidHTTPTemplateDisableHostname: $this->SquidHTTPTemplateNoVersion\n";
                }
            }
        }

        if (isset($TemplateChoose["SquidHTTPTemplateBackgroundColor"])) {
            if (trim($TemplateChoose["SquidHTTPTemplateBackgroundColor"]) <> null) {
                $this->SquidHTTPTemplateBackgroundColor = $TemplateChoose["SquidHTTPTemplateBackgroundColor"];
                if ($GLOBALS["VERBOSE"]) {
                    echo "SquidHTTPTemplateBackgroundColor: $this->SquidHTTPTemplateBackgroundColor\n";
                }
            }
        }
        if (isset($TemplateChoose["SquidHTTPTemplateFamily"])) {
            if (trim($TemplateChoose["SquidHTTPTemplateFamily"]) <> null) {
                $this->SquidHTTPTemplateFamily = $TemplateChoose["SquidHTTPTemplateFamily"];
                if ($GLOBALS["VERBOSE"]) {
                    echo "SquidHTTPTemplateFamily: $this->SquidHTTPTemplateFamily\n";
                }
            }
        }

        if (isset($TemplateChoose["SquidHTTPTemplateFontColor"])) {
            if (trim($TemplateChoose["SquidHTTPTemplateFontColor"]) <> null) {
                $this->SquidHTTPTemplateFontColor = $TemplateChoose["SquidHTTPTemplateFontColor"];
                if ($GLOBALS["VERBOSE"]) {
                    echo "SquidHTTPTemplateFontColor: $this->SquidHTTPTemplateFontColor\n";
                }
            }
        }


        if (!isset($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME])) {
            $this->defaultContent();
            return;
        }

        if (trim($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["TITLE"]) == null) {
            $this->defaultContent();
            return;
        }

        if (trim($this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["BODY"]) == null) {
            $this->defaultContent();
            return;
        }

        $this->TITLE = $this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["TITLE"];
        $this->BODY = $this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["BODY"];

    }

    public function OthersTemplates()
    {
        if (isset($GLOBALS["XTPL_SQUID_DEFAULT_OthersTemplates"])) {
            return;
        }
        $GLOBALS["XTPL_SQUID_DEFAULT_OthersTemplates"] = true;
        if (!isset($GLOBALS["XTPL_SQUID_DEFAULT"])) {
            $GLOBALS["XTPL_SQUID_DEFAULT"] = unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
        }
        $langs = $this->arrayxLangs;
        $langs["en-us"] = true;
        foreach ($langs as $lang => $line) {

            if (!isset($GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_PROTOCOL_UNKNOWN"]["TITLE"])){
                $GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_PROTOCOL_UNKNOWN"]["TITLE"]="ERROR: Unknown protocol";
            }
            if (!isset($GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_PROTOCOL_UNKNOWN"]["BODY"])){
                $GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_PROTOCOL_UNKNOWN"]["BODY"]="<h1>ERROR</h1><h2>The requested URL could not be retrieved</h2></div><hr><div id=\"content\"><p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p><blockquote id=\"error\"><p><b>Unsupported Protocol</b></p></blockquote><p>Squid does not support some access protocols. For example, the SSH protocol is currently not supported.</p><p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p><br></div><hr><div id=\"footer\"><p>Generated %T by %h (%s)</p><!-- %c --></div>";
            }

            if (!isset($GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["TITLE"])) {
                $GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["TITLE"] = "ERROR: Blacklisted Website";
            }
            if (!isset($GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["BODY"])) {
                $GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_BLACKLISTED_SITE"]["BODY"] = "<div id=\"titles\"><h1>ERROR</h1><h2>Blacklisted Website</h2></div><hr><div id=\"content\"><p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p><blockquote id=\"error\"><p><b>Access Denied by the global policy company</b></p></blockquote><p>Access control configuration prevents your request from being allowed at this time.Please contact your service provider if you feel this is incorrect.</p><p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p><br></div><hr><div id=\"footer\"><p>Generated %T by %h (%s)</p><!-- %c --></div>";
            }

            $f = array();
            $GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_PARANOID"]["TITLE"] = "ERROR: Banned access";
            $f[] = "<div id=\"titles\">";
            $f[] = "<h1>Banned Access</h1>";
            $f[] = "<h2>The requested URL could not be retrieved</h2>";
            $f[] = "</div>";
            $f[] = "<hr>";
            $f[] = "	";
            $f[] = "<div id=\"content\">";
            $f[] = "<p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>";
            $f[] = "	";
            $f[] = "<blockquote id=\"error\">";
            $f[] = "<p><b>Paranoid mode, banned access to <q>%H</q></b></p>";
            $f[] = "</blockquote>";
            $f[] = "	";
            $f[] = "<p>Error returned:</p>";
            $f[] = "<blockquote id=\"data\">";
            $f[] = "<pre>- Too many blocking operations - </pre>";
            $f[] = "</blockquote>";
            $f[] = "	";
            $f[] = "<p>This means that too many events has been blocked for your computer or this web site.</p>";
            $f[] = "<p>Please contact your Administrator to fix this issue..</p>";
            $f[] = "	";
            $f[] = "<p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p>";
            $f[] = "<br>";
            $f[] = "</div>";
            $f[] = "	";
            $f[] = "<hr>";
            $f[] = "<div id=\"footer\">";
            $f[] = "<p>Generated %T by %h (%s)</p>";
            $f[] = "<!-- %c -->";
            $f[] = "</div>";
            $GLOBALS["XTPL_SQUID_DEFAULT"][$lang]["ERR_PARANOID"]["BODY"] = @implode("\n", $f);


        }

    }


    private function defaultContent(){
        $tpl=new template_admin();
        if (!isset($GLOBALS["XTPL_SQUID_DEFAULT"])) {
            $GLOBALS["XTPL_SQUID_DEFAULT"] = unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
        }


        if ($this->lang <> null) {
            if ($this->TEMPLATE_NAME <> null) {
                $this->TITLE = $tpl->utf8_encode(trim($GLOBALS["XTPL_SQUID_DEFAULT"][$this->lang][$this->TEMPLATE_NAME]["TITLE"]));
                $this->BODY = $tpl->utf8_encode(trim($GLOBALS["XTPL_SQUID_DEFAULT"][$this->lang][$this->TEMPLATE_NAME]["BODY"]));
            }
        }

    }

    private function v4css()
    {


        $f[] = "/usr/share/artica-postfix/angular/bootstrap.min.css";
        $f[] = "/usr/share/artica-postfix/angular/font-awesome/css/all.min.css";
        $f[] = "/usr/share/artica-postfix/angular/animate.css";
        $f[] = "/usr/share/artica-postfix/angular/style.css";

        foreach ($f as $path) {

            if (!readfile($path)) {
                $f[] = "/*!\n*$path No such file\n*/\n";
                continue;
            }
            $f[] = @file_get_contents($path);
        }

        return $css = "<style type=\"text/css\">\n" . @implode("\n", $f) . "\n</style>\n";

    }


    public function Save(){
        $tpl=new template_admin();
        if (function_exists("admin_tracks")) {
            admin_tracks("Saving template error $this->TEMPLATE_NAME Language $this->lang");
        }


        if ($this->TITLE == null) {
            $MAINDEF = unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
            $this->TITLE = $MAINDEF[$this->lang][$this->TEMPLATE_NAME]["TITLE"];
        }
        if ($this->BODY == null) {
            if (function_exists("writelogs")) {
                writelogs("Saving $this->TEMPLATE_NAME LANG $this->lang BODY==NULL!!!", __FUNCTION__, __FILE__, __LINE__);
            }
            $MAINDEF = unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
            $this->BODY = $MAINDEF[$this->lang][$this->TEMPLATE_NAME]["BODY"];
        }
        $this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["TITLE"] = $tpl->utf8_encode($this->TITLE);
        $this->TemplateConfig[$this->lang][$this->TEMPLATE_NAME]["BODY"] = $tpl->utf8_encode($this->BODY);
        $sock = new sockets();
        if (function_exists("writelogs")) {
            writelogs("Saving $this->TEMPLATE_NAME LANG $this->lang", __FUNCTION__, __FILE__, __LINE__);
        }
        $sock->SaveConfigFile(serialize($this->TemplateConfig), "TemplateConfig");
        $sock->getFrameWork("squid.php?single-templates=yes");
    }

    private function imageinline($img_file){
        $imgData = base64_encode(file_get_contents($img_file));
        $src = 'data: '.mime_content_type($img_file).';base64,'.$imgData;
        return $src;
    }
    private function jsinline($jsfile):string{
        if(!is_file($jsfile)){return "";}
        $Data = base64_encode(file_get_contents($jsfile));
        $src = "data:text/javascript;base64,$Data";
        return $src;
    }

    public function buildTemplate($templateid, $title, $content, $log1 = null, $cssinclude = false)
    {

        $tpl=new template_admin();
        $FOOTER = null;
        if (!isset($GLOBALS["CORP_LICENSE"])) {
            $users = new usersMenus();
            $GLOBALS["CORP_LICENSE"] = $users->CORP_LICENSE;
        }
        if (!isset($GLOBALS["ARTICA_VERSION"])) {
            $GLOBALS["ARTICA_VERSION"] = @file_get_contents("/usr/share/artica-postfix/VERSION");
        }

        if (!$GLOBALS["CORP_LICENSE"]) {
            $errpr = null;
            if ($templateid > 3) {
                $errpr = "In community mode, only the template 1,2,3,4 or 5 can be used<br>";
                $templateid = 1;
            }
            $picfoot=$this->imageinline("/usr/share/artica-postfix/img/logo-artica-64.png");
            $FOOTER = "
			<table style='width:75%;border-top:1px solid white;margin-top:15px'>
			<tr><td colspan=2>&nbsp;</td></tr>
			<tr>
			<td width=64px><img src='$picfoot'></td>
			<td style='font-size:14px;padding-left:10px' width=99%>$errpr
			You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
			<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</i>
			</td>
			</tr>
			</table>
			</div>";
        }

        if ($templateid == 0) {
            $templateid = 1;
        }
        $tplmgr = new templates_manager($templateid);
        $log1 = null;
        $title = $tpl->utf8_decode($title);
        $content = $tpl->utf8_decode($content);

        $fa[] = "js/jquery-1.8.3.js";
        $fa[] = "js/jquery-ui-1.8.22.custom.min.js";
        $fa[] = "js/jquery.blockUI.js";
        $fa[] = "mouse.js";
        $fa[] = "default.js";
        $fa[] = "XHRConnection.js";

        $SquidVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        $SquidVersion = explode(".",$SquidVersion);

        foreach ($fa as $fname){
            $src=$this->jsinline("/usr/share/artica-postfix/$fname");
            $scripts[] = "<script type=\"text/javascript\" language=\"javascript\" src=\"$src\"></script>";
        }


            if($SquidVersion[0]>4){
                $scripts[] = "<script type=\"text/javascript\">
                function insertAfter(referenceNode, newNode) {
                  referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
                }
                document.addEventListener(\"DOMContentLoaded\", function(event) { 
                    var cat =\"@Squid{%note}\";
                    cat = decodeURI(cat);
                    cat=cat.split(\"category-name:\");
                    cat = cat[1].replace(/^clog.*$/gm, '');
                    cat = cat.replace(/^accessrule.*$/gm, '');
                    cat=cat.replace(/\s/g,'')
                    cat=cat.replace(/_/g, ' ');
                    console.log(cat);
                    var el = document.createElement(\"blockquote\");
                    el.innerHTML = \"<p style='text-transform: capitalize'><b>Categorie:</b> \" +cat+\"</p>\";
                    var div = document.getElementById(\"error\");
                    insertAfter(div, el);

                });
                </script>";
            


        }
        $css = "\n<!-- SquidTemplateid=$templateid; LANG:'$this->SquidHTTPTemplateLanguage' - '$this->TEMPLATE_NAME' " . date("Y-m-d H:i:s") . " -->
		<link href=\"/squid-internal-static/icons/silk/main{$templateid}.css\" rel=\"stylesheet\"  type=\"text/css\" />";
        $css = "<style type=\"text/css\">\n{$tplmgr->CssContent}\n</style>\n";


        $tplmgr->headContent = str_replace("%TITLE_HEAD%", $title, $tplmgr->headContent);
        if (strpos($tplmgr->headContent, "%V4HEADS%") > 0) {
            $tplmgr->headContent = str_replace("%V4HEADS%", $this->v4css(), $tplmgr->headContent);
        }
        $tplmgr->headContent = str_replace("%CSS%", $css, $tplmgr->headContent);
        $tplmgr->headContent = str_replace("%JQUERY%", @implode("\n", $scripts), $tplmgr->headContent);


        $tplmgr->BodyContent = str_ireplace("</body>", "", $tplmgr->BodyContent);
        $tplmgr->BodyContent = str_ireplace("%TITLE_HEAD%", "$title", $tplmgr->BodyContent);
        $tplmgr->BodyContent = str_ireplace("</BODY>", "", $tplmgr->BodyContent);
        $tplmgr->BodyContent = str_ireplace("</html>", "", $tplmgr->BodyContent);


        if ($this->SquidHTTPTemplateNoProxyVersion == 1) {
            $content = str_ireplace("<p>Generated %T by %h (%s)</p>", "<!-- <p>Generated %T by %h (%s)</p> -->", $content);
        }

        if ($this->SquidHTTPTemplateNoVersion == 0) {
            $content = $content . "<center>Artica Proxy, version {$GLOBALS["ARTICA_VERSION"]}</center>";
        }

        $tplmgr->BodyContent = str_replace("%DYNAMIC_CONTENT%", $content, $tplmgr->BodyContent);
        $tplmgr->BodyContent = str_replace("%FOOTER%", "", $tplmgr->BodyContent);

        $f[] = $tplmgr->headContent;
        $f[] = $tplmgr->BodyContent;
        $f[] = $FOOTER;
        $f[] = "</body>";
        $f[] = "<!-- ";
        $f[] = "    Head size = " . strlen($tplmgr->headContent) . " template ID: $templateid";
        $f[] = "    $log1";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
        $f[] = "-->";
        $f[] = "</html>";
        $FINAL_TEMPLATE = @implode("\n", $f);
        $BASP = "/usr/share/squid3/icons/tplfiles";

        if (preg_match_all("#\[file=(.*?)\]#s", $FINAL_TEMPLATE, $re)) {
            foreach ($re[1] as $num => $filename) {
                $image = "$BASP/$filename";
                $imageData = base64_encode(file_get_contents($image));
                $src = 'data: ' . mime_content_type($image) . ';base64,' . $imageData;
                $FINAL_TEMPLATE = str_replace($re[0][$num], $src, $FINAL_TEMPLATE);
            }

        }

        return $FINAL_TEMPLATE;
    }


    public function TemplatesDesign()
    {
        $tpl=new template_admin();
        $SquidTemplateid = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateid"));
        if ($SquidTemplateid == 0) {
            $SquidTemplateid = 1;
        }
        if (!isset($GLOBALS["CORP_LICENSE"])) {
            $users = new usersMenus();
            $GLOBALS["CORP_LICENSE"] = $users->CORP_LICENSE;
        }

        if (!$GLOBALS["CORP_LICENSE"]) {
            $SquidTemplateid = 1;
        }

        $log1 = null;
        $title = $this->TITLE;
        $content = $this->BODY;

        if ($this->TEMPLATE_NAME == "ERR_DIR_LISTING") {
            $log1 = "<!-- Use specific FTP template -->";
            $FTPTemplateid = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FTPTemplateid"));
            if ($FTPTemplateid == 0) {
                $FTPTemplateid = 2;
            }
            if (!$GLOBALS["CORP_LICENSE"]) {
                $FTPTemplateid = 2;
            }
            return $this->buildTemplate($FTPTemplateid, $title, $content, $log1, true);
        }
        if ($this->TEMPLATE_NAME == "ERR_ADS_BLOCK") {
            $title = $tpl->utf8_decode($this->TITLE);
            $content = $tpl->utf8_decode($this->BODY);
            $log1 = "<!-- Use specific ADS template -->";
        }
        if ($this->TEMPLATE_NAME == "ERR_CICAP_VIRUS") {
            $title = $tpl->utf8_decode($this->TITLE);
            $content = $tpl->utf8_decode($this->BODY);
            $log1 = "<!-- Use specific C-ICAP AV template -->";
        }
        if ($this->TEMPLATE_NAME == "ERR_CICAP_BLOCK") {
            $title = $tpl->utf8_decode($this->TITLE);
            $content = $tpl->utf8_decode($this->BODY);
            $log1 = "<!-- Use specific C-ICAP AV template -->";
        }

        return $this->buildTemplate($SquidTemplateid, $title, $content, $log1);
    }
}