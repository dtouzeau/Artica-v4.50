<?php
if(!defined("PROGRESS_DIR")){define("PROGRESS_DIR","/usr/share/artica-postfix/ressources/logs/web");}
ini_set('log_errors','On');ini_set('display_errors','Off');ini_set('error_reporting', E_ALL );
if(!isset($GLOBALS["FOOTABLE_PSIZE"])){$GLOBALS["FOOTABLE_PSIZE"]=100;}
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('html_errors',0);$GLOBALS["VERBOSE"]=true;}
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
if(!isset($GLOBALS["DEBUG_INCLUDES"])){$GLOBALS["DEBUG_INCLUDES"]=false;}
$includes=array();
$includes[]="class.icons.inc";
$includes[]="class.templates.inc";
$includes[]="class.tcpip.inc";
$includes[]="class.mysql.squid.builder.php";
$includes[]="class.dansguardian.inc";
$includes[]="class.squid.reverse.inc";
$includes[]="class.system.network.inc";
$includes[]="class.postgres.inc";
$includes[]="class.system.nics.inc";
$includes[]="class.ndpi.services.inc";
$includes[]="class.openvpn.inc";
$includes[]="class.strongswan.inc";
$includes[]="class.squid.dynamic-acls.inc";
$includes[]="class.html.tools.inc";
$includes[]="class.sqlite.inc";
$includes[]="class.memcached.inc";
$includes[]="class.mysql.catz.inc";
$includes[]="class.chartjs.inc";

foreach ($includes as $classfile){
    if(!is_file(dirname(__FILE__)."/$classfile")) {continue;}
    if($GLOBALS["DEBUG_INCLUDES"]){echo "$classfile<br>";}
    include_once(dirname(__FILE__)."/$classfile");
}

class template_admin{
    private $TRCLASS=null;
    private $switchjs=null;
    private $TABLE_FORM_PRIV="";
    private $TABLE_FORM=array();
    private $TABLE_FORM_BUTTONS=array();
    private $FieldTable=array();
    private $FieldTableJs=array();
    private $FieldTableBack=array();
    public $CLUSTER_CLI=false;
    public $KEEPALIVED_CLI = false;
    public  $FORM_IN_ARRAY=false;
    public  $DynaAcls=false;
    private $buttons=array();
    private $button_title=array();
    public $title=null;
    public $this_form_locked_explain=null;
    public $FORM_LOCKED=false;
    private $content;
    private $XHR=array();
    private $XHRHIDDEN;
    private $JS=array();
    public $cssScript=array();
    private $JS_FUNCs=array();
    private $DISABLE_ALL=array();
    private $CHOSENS=array();
    private $LABELS=array();
    private $FormStartupScripts=array();
    private $form_id;
    public $id_temp="";
    public $init_error=null;
    public $form_privileges=null;
    public $suffixid=null;
    public $formwizard=false;
    public $search_box_function=null;
    public $language=null;
    public $label_class=null;
    private $button_id;
    public $ICON_SCRIPTS=array();

    public function __construct($title=null,$content=null){
        $this->title=$title;
        if(is_array($content)){
            $this->content=@implode("\n",$content);
        }else {
            $this->content = $content;
        }
        $this->XHRHIDDEN=array();
        $this->suffixid=md5(microtime(true).rand(0, time()));
        $tpl=new templates();
        $this->language=$tpl->language;

    }
    public function BoolToInteger($value):int{
        if(is_null($value)){
            return 0;
        }

        if(is_bool($value)){
            if($value){return 1;}
            return 0;
        }
        if(is_numeric($value)){return $value;}
        if(strtolower($value)=="yes"){return 1;}
        if(strtolower($value)=="true"){return 1;}
        return 0;
    }
    private function table_form_ActiveInactif($val,$urljs=null):string{
	$tpl=new template_admin();
    $val=$this->BoolToInteger($val);
        $colorDisabled = "rgb(185, 182, 182)";
    if($urljs==null){
        if($val==1){
            return "<i class='fa-solid fa-toggle-on' style='color:#333'></i>&nbsp;{active2}";
        }
        return "<span style='color:#$colorDisabled'><i class='fa-solid fa-toggle-off'></i>&nbsp;{inactive}</span>";
    }
        if($val==1){
            return $tpl->td_href("<i class='fa-solid fa-toggle-on' style='color:#333'></i>&nbsp;{active2}",null,$urljs);
        }
        return $tpl->td_href("<span style='color:#$colorDisabled'><i class='fa-solid fa-toggle-off'></i>&nbsp;{inactive}</span>",null,$urljs);

    }
    public function IsBase64($value): bool {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        $value = trim($value);

        // Length must be multiple of 4
        if (strlen($value) % 4 !== 0) {
            return false;
        }

        // Only valid base64 characters
        if (!preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $value)) {
            return false;
        }

        // Decode and re-encode to confirm
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $value;
    }

    public function TarListFiles($fullpath):array{
        include_once(dirname(__FILE__).'/class.tar.inc');
        $tar_object = new Archive_Tar($fullpath);
        $results=array();
        if (($v_list  =  $tar_object->listContent()) != 0) {
            foreach ($v_list as $key => $value) {
                if($GLOBALS["VERBOSE"]){echo "$key:\n";}
                if (!is_array($value)) {
                    continue;
                }
                if (!isset($value["filename"])) {
                    continue;
                }
                $results[] = $value["filename"];
            }
        }
        return $results;
    }


    public function table_form_section($title=null,$explain=null):bool{

        $INT[] = "<tr class='' style='height: 80px'>";
        $INT[] = "<td colspan=4 style='padding-top:15px'><H1 style='text-transform:capitalize;'>$title</H1>";

        if($explain<>null){ $INT[] = "<p style='font-size:16px;margin-left:20px'>$explain</p>"; }
        $INT[] = "</td>";
        $INT[] = "</tr>";

        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;
    }




    private function table_form_style_data($error=false):string{

        if($this->switchjs==null){
            return "style='width:99%;font-size:large;padding-left: 10px'";
        }
        $js="OnClick=\"$this->switchjs\"";

        $color="#333";
        if($error){
            $color="#ed5565";
        }


        return "style='width:99%;font-size:large;padding-left: 10px' OnMouseOver=\";this.style.cursor='pointer';this.style.color='#21b9bb'\" 
        OnMouseOut=\";this.style.cursor='default';this.style.color='$color';\" $js";
    }

    private function table_form_style($error=false):string{

        if($this->switchjs==null){
            $color="#333";
            if($error){
                $color="#ed5565";
            }
            return " OnMouseOver=\";this.style.color='$color';\" OnMouseOut=\"this.style.color='$color';\"";
        }
        $js="OnClick=\"$this->switchjs\"";

        $color="#333";
        if($error){
            $color="#ed5565";
        }


        return "OnMouseOver=\";this.style.cursor='pointer';this.style.color='#21b9bb'\" 
        OnMouseOut=\";this.style.cursor='default';this.style.color='$color';\" $js";
    }
    public function table_form_field_certificate($CertificateName):bool{
        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        if(preg_match("#SUB:([0-9]+)#",$CertificateName,$re)){
            $SUBID=$re[1];
            $ligne=$q->mysqli_fetch_array("SELECT certid FROM subcertificates WHERE ID=$SUBID");
            $CertID=intval($ligne["certid"]);
            $ligne=$q->mysqli_fetch_array("SELECT CommonName FROM sslcertificates WHERE ID=$CertID");
            $CertificateName="Server:&nbsp;".$ligne["CommonName"]."&nbsp;($SUBID)";
        }
        $this->table_form_field_info("{ssl_certificate}",$CertificateName,ico_certificate);
        return true;

    }

    public function SecondsToTime($inputSeconds):string {
    $secondsInAMinute = 60;
    $secondsInAnHour = 60 * $secondsInAMinute;
    $secondsInADay = 24 * $secondsInAnHour;

    // Extract days
    $days = floor($inputSeconds / $secondsInADay);

    // Extract hours
    $hourSeconds = $inputSeconds % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);

    // Extract minutes
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);

    // Extract the remaining seconds
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);

    // Format and return
    $timeParts = [];
    $sections = array(
        '{day' => (int)$days,
        '{hour' => (int)$hours,
        '{minute' => (int)$minutes,
        '{second' => (int)$seconds
    );

    foreach ($sections as $name => $value){
        if ($value > 0){
            $timeParts[] = $value. ' '.$name.($value == 1 ? '' : 's}');
        }
}

return implode(', ', $timeParts);
}


    private function table_form_field_text_button($value,$ico=""){
        $color="#333";
        if(!is_array($value)){
            return array("",$value,$color);
        }

        if(!isset($value["BUTTON"])){
            return array("",$value,$color);
        }

        $Grey["{not_installed}"]=true;
        $Grey["{disabled}"]=true;
        $Grey["{inactive2}"]=true;
        $Grey["{none}"]=true;
        $NewVal="";


        if(isset($value["BUTTON"]["VALUE"])){
            $NewVal=$value["BUTTON"]["VALUE"];

        }
        if(isset($Grey[$NewVal])){
            $color="rgb(185, 182, 182)";
        }
        $labelbtn="";
        $class="btn-primary";
        $btnjs="blur()";
        if(isset($value["BUTTON"]["BT_CLASS"])){
            $class=$value["BUTTON"]["BT_CLASS"];
        }
        if(isset($value["BUTTON"]["LABEL"])){
            $labelbtn=$value["BUTTON"]["LABEL"];
        }
        if(isset($value["BUTTON"]["JS"])){
            $btnjs=$value["BUTTON"]["JS"];
        }
        if($ico==ico_trash){$class="btn-danger";}
        $Button=$this->button_autnonome($labelbtn,$btnjs, $ico, null,0,$class);


        return array($Button,$NewVal,$color);
    }
    public function table_form_field_warn($text):bool{
        $ico="fas fa-exclamation-triangle";
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $style=$this->table_form_style();
        $style_value=$this->table_form_style_data();
        $INT=array();
        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px'>";
        $INT[] = "<td style='width:1%;padding-left:10px;' $style class='text-warning'>
                    <i class='$ico fa-2x' class='text-warning'></i>
                </td>";
        $INT[] = "<td style='width:1%;font-size:large;text-align:left;text-transform:capitalize;padding-left:10px' nowrap $style colspan='3' class='text-warning'>$text</td>";
        $INT[] = "</tr>";

        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;
    }

    public function table_form_field_text($field,$value,$ico=null,$error=false):bool{

        if($ico==null){$ico="fa-regular fa-ellipsis";}
        list($Button,$value,$color)=$this->table_form_field_text_button($value,$ico);

        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $edit=null;
        if($this->switchjs<>null){
            $edit=$this->icon_edit_field($this->switchjs,$this->TABLE_FORM_PRIV);

        }
        $ico_check=ico_check_double;
        if(!is_null($value)) {
            $value = str_replace("[OK]", "<i class='$ico_check' style='color:#1ab394'></i>", $value);
        }

        $icon_help=null;
        if(preg_match("#^http#",$error)){
            $icon_help="&nbsp;&nbsp;".$this->td_href("<i class='text-warning ".ico_support."'></i>",
                    "Help on Wiki",
                    "s_PopUpFull('$error','1024','900');"
                );
            $error=false;

        }


        if($error){
            $color="#ed5565";
        }
        $style=$this->table_form_style($error);

        $style_value=$this->table_form_style_data($error);
        $INT=array();
        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px'>";
        $INT[] = "<td style='width:1%;padding-left:10px;color:$color' $style>
                    <i class='$ico fa-2x' style='color:$color'></i>
                </td>";
        $INT[] = "<td style='width:1%;font-size:large;text-align:left;text-transform:capitalize;padding-left:10px;color:$color' nowrap $style>$field:</td>";
        $INT[] = "<!-- Button: ".strlen($Button)." -->";

        if( strlen($Button)==0 ) {
            $INT[] = "<td $style_value >";
            $INT[] = "<strong style='text-transform:capitalize;color:$color'>$value</strong>$icon_help";
        }else{
            $INT[] = "<td>";
            $INT[] = "<!-- Button: BUILD -->";
            $INT[] = "<table style='width:100%'>";
            $INT[] = "<tr>";
            $INT[] = "<td $style_value >";
            $INT[] = "<strong style='text-transform:capitalize;color:$color'>$value</strong>$icon_help</td>";
            $INT[] = "<td style='width:1%'>$Button</td>";
            $INT[] = "</tr>";
            $INT[] = "</table>";
        }
        $INT[] = "</td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px;color:$color'>$edit</td>";
        $INT[] = "</tr>";

        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;

    }

    public function table_form_compile():string{
        $tbl=@implode("",$this->TABLE_FORM);
        $html[]="<table style='margin-top:10px;margin-left:20px'>";
        $html[]=$tbl;
        $btns=count($this->TABLE_FORM_BUTTONS);
        if($btns>0){
            if($btns==1){
                $html[] = "<tr class='$this->TRCLASS' style='height: 60px'>";
                $html[] = "<td colspan=4 style='text-align:right;border-top:1px solid #CCCCCC'>".$this->TABLE_FORM_BUTTONS[0]."</td>";
                $html[] = "</tr>";
            }
            if($btns>1){
                $percent=round(100/$btns);
                $html[] = "<tr class='$this->TRCLASS' style='height: 60px'>";
                $html[] = "<td colspan=4 style='text-align:right;border-top:1px solid #CCCCCC'>";
                $html[] = "<table>";
                $html[] = "<tr>";
                foreach ($this->TABLE_FORM_BUTTONS as $btn){
                    $html[] = "<td style='padding-left:5px;width:$percent%'>$btn</td>";
                    
                }
                $html[] = "</tr>";
                $html[] = "</table>";
                $html[] = "</tr>";
            }

        }

        $html[]="</table>";
        $this->TABLE_FORM=array();
        $this->TABLE_FORM_BUTTONS=array();
        return $this->_ENGINE_parse_body($html);
    }

    public function table_form_field_js($js,$privilege=null):bool{
        $this->TABLE_FORM_PRIV=$privilege;
        if($privilege<>null){
            if(!$this->IsSecurity($privilege)){
                $this->switchjs="";
                return false;
            }
        }

        $this->switchjs=$js;
        return true;
    }

    public function table_form_field_install($field,$value,$json,$jsoff):bool{
        $style=$this->table_form_style();

        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px'>";


        if($value==0){
           $ico=ico_cd;
            $text="{install}";
            $text_rows="{not_installed}";
            $button="<button class='btn btn-primary btn-sm' 
            OnClick=\"$json\"
            type='button'><i class='$ico'></i>&nbsp;$text</button>";

        }else{
            $ico=ico_trash;
            $text_rows="{installed}";
            $text="{uninstall}";
            $button="<button class='btn btn-danger btn-sm' 
            OnClick=\"$jsoff\"
            type='button'><i class='$ico'></i>&nbsp;$text</button>";
        }

        $INT[] = "<td style='width:1%;padding-left:10px' $style>
                    <i class='$ico fa-2x' style='color:#333'></i>
                </td>";
        $INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px;color:#333' nowrap $style>$field:</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$text_rows</strong></td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$button</td>";
        $INT[] = "</tr>";
        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;

    }

    public function table_form_button($button_text,$js=null,$security=null,$ico=null){
        if($ico==null){$ico="fa-regular fa-ellipsis";}
        $class="btn-primary";
        if($ico==ico_trash){$class="btn-danger";}
        $this->TABLE_FORM_BUTTONS[]= $this->button_autnonome($button_text,$js, $ico, $security,300,$class);
    }

    public function table_form_field_buttons($buttons=array(),$security=null):bool{
        if(!is_array($buttons)){
            VERBOSE("NO ARRAY = FALSE",__LINE__);
            return false;
        }
        if(count($buttons)==0){
            VERBOSE("NO BUTTONS = FALSE",__LINE__);
            return false;
        }
        if(!is_null($security)) {
            if (!$this->IsSecurity($security)) {
                VERBOSE("Disabled ! $security = FALSE",__LINE__);
                return false;
            }
        }
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $style=$this->table_form_style();
        $ico=ico_field;
        VERBOSE("Disabled ! $security = FALSE",__LINE__);
        $btns=$this->th_buttons($buttons);
        if(strlen($btns)==0){
            VERBOSE("BTN length()==0 FALSE",__LINE__);
            return false;
        }

        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px'>";
        $INT[] = "<td style='width:1%;padding-left:10px' $style>
                    <i class='$ico fa-2x' style='color:#333'></i>
                </td>";
        $INT[] = "<td style='width:1%;font-size:large;text-align:right;padding-left:10px;padding-right:10px;color:#333' nowrap $style colspan='3'>$btns</td>";
        $INT[] = "</tr>";

        VERBOSE("class.template-admin.inc: OK....",__LINE__);
        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;
    }

    public function table_form_field_info($field,$value,$ico=null,$buttons=array()):bool{
        if($ico==null){$ico="fa-regular fa-ellipsis";}
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $edit=null;
        if($this->switchjs<>null){
            $edit=$this->icon_edit_field($this->switchjs,$this->TABLE_FORM_PRIV);

        }
        $style=$this->table_form_style();
        $value="<strong>$value</strong>";
        $btns=$this->th_buttons($buttons);
        if(strlen($btns)>2){
            $value="<table style='width:100%;padding:0;margin:0'>
            <tr>
            <td style='width:100%'>$value</td>
            <td style='width:1%;text-align:right;padding-right:10px' nowrap>$btns</td>
            </tr>
            </table>";


        }

        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px'>";
        $INT[] = "<td style='width:1%;padding-left:10px' $style>
                    <i class='$ico fa-2x' style='color:#333'></i>
                </td>";
        $INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px;color:#333' nowrap $style>$field:</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'>$value</td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
        $INT[] = "</tr>";

        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;
    }

    public function table_form_field_warning($text):bool{
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $text=$this->_ENGINE_parse_body($text);

        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px'>";
        $INT[] = "<td style='width:1%;padding-left:10px'>
                    <i class='text-warning fa-solid fa-triangle-exclamation fa-2x' style='color:#f8ac59'></i>
                </td>";
        $INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px;color:#f8ac59' nowrap>{warning}:</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong class='text-warning'>$text</strong></td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>&nbsp;</td>";
        $INT[] = "</tr>";

        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;

    }

    public function table_form_field_button($field,$value,$ico=null):bool{
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $edit=null;
        if($ico==null){$ico="fa-regular fa-ellipsis";}

        $style=$this->table_form_style();
        $class="btn-primary";
        if($ico==ico_trash){
            $class="btn-danger";
        }
        if(preg_match("#^class:(.+?):(.+)#",$field,$re)){
            $class=$re[1];
            $field=$re[2];
        }

        $button=$this->button_autnonome($value,$this->switchjs,$ico,null,250,$class,75);
        if($this->switchjs==null){
            $button=$this->button_autnonome($value,$this->switchjs,$ico,null,250,"btn-default",75);
        }

        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px'>";
        $INT[] = "<td style='width:1%;padding-left:10px' $style>
                    <i class='$ico fa-2x' style='color:#333'></i>
                </td>";
        $INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px;color:#333' nowrap $style>$field:</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$button</strong></td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
        $INT[] = "</tr>";

        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;
    }

    public function table_form_field_bool($field,$value,$ico=null,$helpuri=null):bool{
        $Button=null;
        $color="#333";
        $Grey[0]=true;


        if(is_array($value)){
            if(isset($value["BUTTON"])){
                if(isset($Grey[$value["BUTTON"]["VALUE"]])){
                    $color="rgb(185, 182, 182)";
                }
            }
        }else {
            if (isset($Grey[$value])) {
                $color = "rgb(185, 182, 182)";
            } else {
                VERBOSE("$value not in grey", __LINE__);
            }
        }


        if(is_array($value)){
            if(isset($value["BUTTON"])){
                $array=$value;
                $value=$array["BUTTON"]["VALUE"];
                if($ico==null){$ico="fa-regular fa-ellipsis";}
                $class="btn-primary";
                if($ico==ico_trash){$class="btn-danger";}
                $Button=$this->button_autnonome($array["BUTTON"]["LABEL"],$array["BUTTON"]["JS"], $ico, null,0,$class);
            }
        }



        $value=$this->table_form_ActiveInactif($value,$this->switchjs);
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $edit=null;
        if($ico==null){$ico="fa-regular fa-ellipsis";}

        if($this->switchjs<>null){
            $edit=$this->icon_edit_field($this->switchjs,$this->TABLE_FORM_PRIV);

        }
        $style=$this->table_form_style();
        $icon_help=null;
        if($helpuri<>null){
            $icon_help="&nbsp;&nbsp;".$this->td_href("<i class='text-warning ".ico_support."'></i>",
                "Help on Wiki",
                "s_PopUpFull('$helpuri','1024','900');"
            );

        }

        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px'>";
        $INT[] = "<td style='width:1%;padding-left:10px' $style>
                    <i class='$ico fa-2x' style='color:$color'></i>
                </td>";
        $INT[] = "<td style='width:1%;font-size:large;text-transform:capitalize;text-align:left;padding-left:10px;color:#333' nowrap $style><span style='color:$color'>$field:</span></td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'>";
        if($Button==null) {
            $INT[] = "<strong style='text-transform:capitalize;color:$color'>$value</strong>$icon_help</td>";
        }else{
            $INT[] = "<table style='width:100%'>";
            $INT[] = "<tr>";
            $INT[] = "<td style='width:99%'>";
            $INT[] = "<strong style='text-transform:capitalize;color:$color'>$value</strong>$icon_help</td>";
            $INT[] = "<td style='width:1%'>$Button</td>";
            $INT[] = "</tr>";
            $INT[] = "</table>";
        }
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px;color:$color'>$edit</td>";
        $INT[] = "</tr>";

        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;

    }


    function xPrivs(){
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=new $GLOBALS["CLASS_USERS"];
        if(isset($_SESSION["MANAGE_CATEGORIES"])) {
            if (is_array($_SESSION["MANAGE_CATEGORIES"])) {
                if (count($_SESSION["MANAGE_CATEGORIES"]) > 0) {
                    return true;
                }
            }
        }
        if(count($users->NGINX_SERVICES)>0){
            $EnableNginx=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx");
            if($EnableNginx==1){
                return true;
            }
        }
        if(property_exists($users,"SIMPLE_ACLS")) {
            if (is_array($users->SIMPLE_ACLS)) {
                if (count($users->SIMPLE_ACLS) > 0) {
                    return true;
                }
            }
        }


        if($users->AsDatabaseAdministrator){return true;}
        if($users->AsCertifsManager){return true;}
        if($users->AsFirewallManager){return true;}
        if($users->AsVPNManager){return true;}
        if($users->AsProxyMonitor){return true;}
        if($users->AsAnAdministratorGeneric){return true;}
        if($users->AsPostfixAdministrator){return true;}
        if($users->AsMessagingOrg){return true;}
        if($users->AsSambaAdministrator){return true;}
        if($users->AsDnsAdministrator){return true;}
        if($users->AsWebStatisticsAdministrator){return true;}
        if($users->AllowAddUsers){return true;}
        if($users->AllowAddGroup){return true;}
        if($users->AsDockerAdmin){return true;}
        if($users->AsDockerReverse){return true;}
        if($users->AsSquidPersonalCategories){return true;}
        if($users->AsHamrpAdmin){return true;}
        if($users->AsDansGuardianAdministrator){return true;}
        if(isset($_SESSION["SQUID_DYNAMIC_ACLS"])){
            if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){return true;}
        }

        VERBOSE("NO PRIVILEGES !!!",__LINE__);
        return false;
    }


    public function build_heads():string{
        include_once(dirname(__FILE__)."/class.heads.builder.inc");
        $this->title=$this->javascript_parse_text($this->title);
        $f[]="<!DOCTYPE html>";
        $f[]="<html lang=\"en\">";
        $f[]= "<head>";
        $f[]="    <title>$this->title</title>";
        $f[]="    <meta charset=\"utf-8\">";
        $f[]="    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
        $f[]="    <link rel=\"icon\" href=\"/ressources/templates/default/favicon.ico\" type=\"image/x-icon\" />";
        $f[]="    <link rel=\"shortcut icon\" href=\"/ressources/templates/default/favicon.ico\" type=\"image/x-icon\" />";
        $f[]="    <link href=\"/css/pace-theme-big-counter.css\" rel=\"stylesheet\">";
        $HeadBuilder=new HeadsBuilder();
        $HeadBuilder->BuildLoader();
        $f[]=$HeadBuilder->f;
        $this->cssScript[]=$HeadBuilder->cssScript;

        $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
        $f[]="<script>";
        $f[]="\t$.address.strict(false);";
        $f[]="\tfunction ArtPing() {";
        $f[]="\t\tif( document.getElementById('sysmemory') ){";
            $f[]="\t\t\tLoadAjaxTiny('sysmemory', 'fw.system.status.php?sysmemory=yes');";
            $f[]="\t\t}";
            $f[]="";
            $f[]="\t\t$.ajax({";
            $f[]="\t\t\ttype: \"GET\",";
            $f[]="\t\t\turl: 'fw.ping.php?SessionCookieLifetime=$SessionCookieLifetime',";
            $f[]="\t\t\tcache: false,";
            $f[]="\t\t\ttimeout: 60000,";
            $f[]="\t\t\tsuccess: function(html, status, req) {";
            $f[]="\t\t\t\tif (req.status == 200 ) {";
            $f[]="\t\t\t\t\t$(\"#faclock\").html(html);";
            $f[]="\t\t\t\t} else {";
            $f[]="\t\t\t\talert(status);";
            $f[]="\t\t\t\t}";
            $f[]="\t\t\t},";
            $f[]="\t\t\terror: function(req, status, error) {";
            $f[]="\t\t\t\t";
            $f[]="\t\t\t}";
            $f[]="\t\t});";
            $f[]="\t}";
            $f[]="\tvar timer = $.timer(function() { ArtPing(); });";
            $f[]="\ttimer.set({ time : 30000, autostart : true });";

        $f[]="</script>";
        $f[]="</head>";

        return @implode("\n", $f);
    }

    public function MonthToInteger($month):string{
        $month=strtoupper($month);
        $zText=$month;
        $zText=str_replace('JAN', '01',$zText);
        $zText=str_replace('FEB', '02',$zText);
        $zText=str_replace('MAR', '03',$zText);
        $zText=str_replace('APR', '04',$zText);
        $zText=str_replace('MAY', '05',$zText);
        $zText=str_replace('JUN', '06',$zText);
        $zText=str_replace('JUL', '07',$zText);
        $zText=str_replace('AUG', '08',$zText);
        $zText=str_replace('SEP', '09',$zText);
        $zText=str_replace('OCT', '10',$zText);
        $zText=str_replace('NOV', '11',$zText);
        $zText=str_replace('DEC', '12',$zText);
        return $zText;
    }

    public function logs_color($line=""):string{
        if($line==null){return "";}
        if(preg_match("#(mounted filesystem|\s+registered|\s+registering)#i", $line)){
            return "<span class='text-info'>$line</span>";
        }

        if(preg_match("#(Failed|Invalid|error|Traceback)#i", $line)){
            return "<span class='text-danger'>$line</span>";
        }
        if(preg_match("#Accepted password#i", $line)){
            return "<span class='text-success'>$line</span>";
        }
        if(preg_match("#(Server listening|Accepted keyboard-interactive)#i", $line)){
            return "<span class='text-success'>$line</span>";
        }

        if(preg_match("#(warning|restarting|disabled|removed|authentication failures|authentication failure|Did not receive identification string)#i", $line)){
            return "<span class='text-warning'>$line</span>";
        }
        return $line;

    }

    public function ArticaEdition(){
        $VERSION    = trim(@file_get_contents("VERSION"));
        $CURPATCH   = intval(@file_get_contents("SP/$VERSION"));
        $FINAL_TIME = 0;
        $GOLD       = False;
        $Eval       = null;
        if($CURPATCH>0){$VERSION="$VERSION&nbsp;Service Pack $CURPATCH";}

        if($GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()){
            return "v$VERSION Gold Edition";
        }


        if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){

            $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
            $LicenseINGP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseINGP"));
            if(isset($LicenseInfos["FINAL_TIME"])){$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}

            if(isset($LicenseInfos["license_number"])){
                if($GLOBALS["CLASS_SOCKETS"]->IsGoldKey($LicenseInfos["license_number"])){
                    $FINAL_TIME=0;
                    $Eval="&nbsp;({gold_license})";
                }
            }
            if($FINAL_TIME>0){
                $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
                if($ExpiresSoon<31){

                    $Eval="&nbsp;<span class='label label-warning'>{trial_mode}</span>";
                    $Eval=$this->td_href($Eval,"","Loadjs('fw.index.php?trial-explain=yes')");
                }
                if($ExpiresSoon<1){
                    $Eval="&nbsp;<span class='label label-danger'>{expired}</span>";
                }
            }

            if($LicenseINGP>0){
                if($LicenseINGP>time()) {
                    $Eval = "&nbsp;<span class='label label-warning'>{grace_period}</span>";
                }
            }
           return "v$VERSION Enterprise Edition$Eval";
        }

        return "v$VERSION Community Edition$Eval";

    }

    public function build_firewall($token="nothing=none"){
        VERBOSE("BuildFireWall OK", __LINE__);
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}

        $f[]=$this->build_heads();
        $f[]="<body id=\"page-top\" class='pace-done mini-navbar'>";
        $f[]="<input type='hidden' id='cronos-id' value='0'>";
        $f[]="<input type='hidden' id='cronos-key' value='0'>";
        $f[]="<div class='pace pace-active' id='pace-progress' style='font-size: 88px'></div>";
        $f[]="</div><div id=\"wrapper\">";
        $f[]="\t<div id='left-barr'></div>";
        $f[]="\t\t<div id=\"page-wrapper\" class=\"gray-bg\">";
        $f[]="\t\t\t<div id='top-barr'></div>";
        $f[]="\t\t\t<div class=\"wrapper wrapper-content animated fadeInRight\" id='MainContent' style='min-height:610px;padding-top:3px;margin-top:-9px;margin-left:-9px'>";
        $f[]=$this->content;

        $f[]="\t\t\t</div>";
        $f[]="\t\t\t<div class=\"footer\" style='margin-left:-3px;margin-right:-3px'>";
        $f[]="\t\t\t\t<div class=\"pull-right\">";
        $f[]=$this->ArticaEdition();
        $UPTIME_TEXT=null;
        $UPTIME=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/UPTIME"));
        $EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
        if($UPTIME>0){
            $UPTIME_TEXT="&nbsp;&nbsp;|&nbsp;&nbsp;{uptime}:&nbsp;".distanceOfTimeInWords($UPTIME,time());
            if($EnableMunin==1){$UPTIME_TEXT="&nbsp;&nbsp;|&nbsp;&nbsp;{uptime}:&nbsp;".$this->td_href(distanceOfTimeInWords($UPTIME,time()),"{statistics}","Loadjs('fw.system.status.uptime.php')");}
        }
        $f[]=$UPTIME_TEXT;
        $f[]="\t\t\t\t</div>";
        $f[]="\t\t\t\t<div>";
        $f[]="\t\t\t\t\t<strong>Copyright</strong> Artica Tech &copy; 2004-" .date("Y");
        $f[]="\t\t\t\t</div>";
        $f[]="\t\t\t</div>";
        $f[]="\t\t</div>";
        $f[]="</div>";
        $f[]="<div id='right-sidebar'>
				<div class='sidebar-container' id='artica-right-sidebarr'>
			</div>
		</div>";
        $f[]="<div id='artica-modal-dialog'></div><div id='modal-windows' class='modal inmodal fade'  data-keyboard=\"false\" data-backdrop=\"static\">";
        $LEFT=false;
        $f[]="<script src=\"/angular/js/plugins/pace/pace.min.js\"></script>";
        $f[]=@implode("\n",$this->cssScript);
        $f[]="<script src=\"/angular/js/plugins/chartJs/Chart.min.js\"></script>";
        $f[]="<script src=\"/angular/js/plugins/chartJs/angles.js\"></script>";
        $f[]="<!-- Custom and plugin javascript -->";

        $f[]="<script>";
        $f[]="";

        $f[]="function RefreshSecondInterfaceBarrs(){";
        if($this->DynaAcls){
            $LEFT=true;
            $f[]="LoadAjaxSilent('left-barr','fw-left-menus.php?dynacls=yes');";
        }
        if(!$LEFT){
            $f[]="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes&$token');";
        }

        $me=CurrentPageName();
        $f[]="LoadAjaxSilent('top-barr','fw-top-bar.php?by=$me&$token');";
        $f[]="Loadjs('fw.icon.top.php');";
        $f[]="}";

        $f[]="RefreshSecondInterfaceBarrs();";
        $f[]="</script>";
        VERBOSE("BuildFireWall OK", __LINE__);
        $tpl=new templates();
        return $tpl->_ENGINE_parse_body(@implode("\n",$f));

    }

    public function widget_style2($background="lazur-bg",$icon="fa fas fa-database",$text=null){
        //navy-bg/lazur-bg/blue-bg/red-bg/yellow-bg/gray-bg/black-bg

        //fa-thumbs-up
        return "
		<div class=\"widget style1 $background\">
			<div class=\"row\">
				<div class=\"col-xs-4\">
					<i class=\"$icon fa-5x\"></i>
				</div>
				<div class=\"col-xs-8 text-right\">
					$text
				</div>
			</div>
		</div>";

    }

    public function widget_micro($background="lazur-bg",$icon="fa fas fa-database",$text="",$subtext=""):string{

        if($background=="green-bg"){
            $background="navy-bg";
        }
        $color="white";
        if($background=="grey-bg"){
            $color="black";
        }
        if($background=="gray-bg"){
            $color="black";
        }
        $btnclass["navy-bg"]="btn-primary";
        $btnclass["lazur-bg"]="btn-info";
        $btnclass["red-bg"]="btn-danger";
        $btnclass["yellow-bg"]="btn-warning";
        $btnclass["gray-bg"]="btn-default";
        $btnclass["black-bg"]="btn-default";
        $btnclass["grey-bg"]="btn-default";

        if(strlen($subtext)>2){
            $subtext="<small style='color:$color;font-size:11px;line-height: 2px
'>$subtext</small>";
        }

        $html="<div class=\"widget style1 $background\">
                        <div class=\"row vertical-align\">
                            <div class=\"col-xs-3\">
                                <i class=\"$icon fa-3x\"></i>
                            </div>
                            <div class=\"col-xs-9 text-right\">
                                <h2 class=\"font-bold\">$text</h2>$subtext
                            </div>
                        </div>
                    </div>";

        return $this->_ENGINE_parse_body($html);
    }

    public function widget_style1($background="lazur-bg",$icon="fa fas fa-database",$title=null,$text=null,$button=array()):string{
        //navy-bg/lazur-bg/blue-bg/red-bg/yellow-bg/gray-bg/black-bg
        //fa-thumbs-up parse_button
        $btn="";

        if($background=="green-bg"){
            $background="navy-bg";
        }
        if($background=="bg-red"){
            $background="red-bg";
        }


        $btnclass["blue-bg"]="blue";
        $btnclass["navy-bg"]="btn-primary";
        $btnclass["lazur-bg"]="btn-info";
        $btnclass["red-bg"]="btn-danger";
        $btnclass["bg-red"]="btn-danger";
        $btnclass["yellow-bg"]="btn-warning";
        $btnclass["gray-bg"]="btn-default";
        $btnclass["black-bg"]="btn-default";
        $btnclass["grey-bg"]="btn-default";
        $btn="<div style='margin-top:30px;visibility: hidden' id='0'><button type='button' class='btn btn-blue'>&nbsp;Modify</button></div></div>";
        if(is_array($button)){
            if(count($button)>0){
                $sbtncalss="";
                if(!isset($button["color"])){
                    $sbtncalss=$btnclass[$background];
                }
                $btn=$this->parse_button($button,$sbtncalss);
            }
        }
        return "<div class=\"widget style1 $background\" style='min-height: 122px'>
			<div class=\"row\">
				<div class=\"col-xs-4\">
					<i class=\"$icon fa-5x\"></i>
				</div>
				<div class=\"col-xs-8 text-right\">
					<span> $title </span>
					<h2 class=\"font-bold\">$text</h2>
					$btn
				</div>
			</div>
		</div>";

    }

    public function decode_mime_string($subject){


        if(function_exists("imap_mime_header_decode")){
            $elements = imap_mime_header_decode($subject);
            if(isset($elements[0])){
                if($elements[0]->text<>null){
                    $subject=htmlentities($elements[0]->text);
                    $subject=replace_accents($subject);
                    $subject=str_replace("'", "`", $subject);
                    return $subject;
                }

            }
        }
        if(function_exists("mb_convert_encoding")) {
            $subject= mb_convert_encoding($subject, 'UTF-8');
            $subject=str_replace("'", "`", $subject);
            return $subject;
        }

        if(function_exists("imap_utf8")) {
            $subject=imap_utf8($subject);
            $subject=str_replace("'", "`", $subject);
            return $subject;
        }
        return $subject;
    }


    public function RefreshInterval_js($div,$page,$Query,$Interval=3):string{
        $Target="fw.status.interval.php";
        $Main["DIV"]=$div;
        $Main["PAGE"]=$page;
        $Main["QUERY"]=$Query;
        $Main["INTERVAL"]=$Interval;
        $Serialize=base64_encode(serialize($Main));
        return "Loadjs('$Target?Start=$Serialize');";
    }
    public function RefreshInterval_Loadjs($div,$page,$Query,$Interval=3):string{
        $Target="fw.status.interval.php";
        $Main["DIV"]=$div;
        $Main["PAGE"]=$page;
        $Main["QUERY"]=$Query;
        $Main["INTERVAL"]=$Interval;
        $Serialize=base64_encode(serialize($Main));
        return "Loadjs('$Target?Loadjs=$Serialize');";
    }


    public function CLEAN_POST($exclude=null,$encode=false):array{
        $_SESSION["SESSARTTIMEOUT"] = time();
        $array=array();
        foreach ($_POST as $key=>$val){
            if($key==$exclude){continue;}
            $valsqz=url_decode_special_tool($val);
            if($encode){
                $valsqz=$this->utf8_encode($valsqz);
            }
            $valsql=sqlite_escape_string2($valsqz);
            $array["FIELDS_ADD"][]="`$key`";
            $array["VALUES_ADD"][]="'$valsql'";
            $array["EDIT"][]="`$key`='$valsql'";
            $_POST[$key]=trim(url_decode_special_tool($val));
        }

        return $array;
    }

    #[Pure]
    public function utf8_decode($string):string{
        if(is_null($string)){
            return "";
        }
        if(function_exists("mb_convert_encoding") ){
            return strval(mb_convert_encoding($string, 'ISO-8859-1'));
        }

        if(function_exists("utf8_decode")){
            $ret=utf8_decode($string);
            return strval($ret);
        }
        return strval(mb_convert_encoding($string, 'ISO-8859-1'));
    }

    public function utf8_encode($string):string{

        $phpver=phpversion();
        if(strpos(" $phpver","8.2")){
            return strval(mb_convert_encoding($string, 'UTF-8'));
        }
        if(function_exists("utf8_encode")){
            return utf8_encode($string);
        }
        return strval(mb_convert_encoding($string, 'UTF-8'));
    }
    public function CLEAN_GET_XSS():bool{
        foreach ($_GET as $key=>$val){
            $_GET[$key]=$this->CLEAN_BAD_XSS(url_decode_special_tool($val));
            }


        return true;
    }

    public function CLEAN_POST_XSS($exclude=null):array{
        if(!isset($_POST)){return array();}
        if(count($_POST)==0){return array();}
        $array=array();
        foreach ($_POST as $key=>$val){
            if($key<>$exclude){
                $valsql=sqlite_escape_string2($this->CLEAN_BAD_XSS(url_decode_special_tool($val)));
                $array["FIELDS_ADD"][]="`$key`";
                $array["VALUES_ADD"][]="'$valsql'";
                $array["EDIT"][]="`$key`='$valsql'";
                if(strtolower($key)=="password"){
                    $_POST[$key]=url_decode_special_tool($val);
                    continue;
                }
                $_POST[$key]=$this->CLEAN_BAD_XSS(url_decode_special_tool($val));
            }
        }

        return $array;
    }

    public function MYSQL_ENCOSE($array){
        $newarray=array();
        foreach ($array as $values){
            $newarray[]="'{$values}'";
        }
        return $newarray;
    }
    public function SAVE_Admin_track($admintrcktext){
        $this->CLEAN_POST();
        $admintrack=array();

        foreach ($_POST as $key=>$val){
                $admintrack[]="$key: $val";
        }

        if($admintrcktext<>null){
            admin_tracks($admintrcktext ."(". @implode(", ",$admintrack).")");
        }

    }

    public function SAVE_POSTs($exclude=null,$admintrcktext=null):array{
        $this->CLEAN_POST($exclude);
        $admintrack=array();
        $sock=new sockets();
        foreach ($_POST as $key=>$val){
            if($key<>$exclude){
                $admintrack[]="$key: $val";
                $sock->SET_INFO($key, $val);
            }
        }
        if($admintrcktext<>null){
            admin_tracks($admintrcktext ."(". @implode(", ",$admintrack).")");
        }
        reset($_POST);
        $sock->REST_API("/reset/cache");
        return $_POST;
    }

    public function framework_buildjs($cmd,$progress,$logfile,$divid,$after=null,$onfailed=null,$confirm=null,$privileges=null):string{
        if(!is_null($onfailed)) {
            if (preg_match("#style:high#", $onfailed)) {
                $ARRAY["BIG"] = true;
            }
        }

        if($privileges<>null){
            if(!$this->IsSecurity($privileges)){
                return "Loadjs('fw.progress.php?badprivs=yes')";
            }
        }
        $progress=trim($progress);
        $logfile=trim($logfile);
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/$progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/$logfile";
        $ARRAY["CMD"]=$cmd;
        $ARRAY["TITLE"]=$this->_ENGINE_parse_body("{ComputerRunning}");
        if($after<>null) {
            $ARRAY["AFTER"] = $after;
        }
        if($onfailed<>null) {
            $ARRAY["AFTER_FAILED"] =$onfailed;
        }
        $prgress=base64_encode(serialize($ARRAY));
        $js="Loadjs('fw.progress.php?content=$prgress&mainid=$divid')";
        if($confirm<>null){
            $confirm=$this->_ENGINE_parse_body($confirm);
            $sencoded=urlencode(base64_encode($js));
            $confirmencoded=urlencode(base64_encode($confirm));
            $js="Loadjs('fw.progress.php?confirm-before=$confirmencoded&jscode=$sencoded')";
        }

        return $js;
    }


    function SESSION_POST($exclude=null){
        foreach ($_POST as $key=>$val){
            if($key<>$exclude){
                $_SESSION[$key]=$val;
            }
        }
        $_POST=array();
    }

    function CLEAN_BAD_CHARSNET($string){
        if(is_null($string)){return "";}
        $string=str_replace(['|','+','²','^','°', '\\', '*',' ','(',')','{','}','[',']','%','$',';',
            ',','?','=','#','&','`','"','>','<',"@",'”',"'"], '', $string);
        return trim(strtolower($string));


    }
    public function CLEAN_BAD_XSS_SQL($string):string{
        if(is_null($string)){return "";}
        $string=str_replace(['|','+','²','^','°', '\\', '(',')','{','}','%','$',';',
            '#','&','`','"','”',"'"], '', $string);
        return trim($string);
    }


    public function CLEAN_BAD_XSS($string):string{
        if(is_null($string)){return "";}
        $string=str_replace(['|','+','²','^','°', '\\', '(',')','{','}','%','$',';',
            '#','&','`','"','>','<','”',"'"], '', $string);
        return trim($string);
    }

    function CLEAN_BAD_CHARMAIL($string){
        if(is_null($string)){return "";}
        $string=str_replace(['|','+','²','^','°', '\\', '/', '*',' ','(',')','{','}','[',']','%','$',';',
            ',','?','=','#','&','`','"','>','<',"'",'”'], '', $string);
        return trim(strtolower($string));
    }

    public function MYSQL_POST(){
        foreach ($_POST as $eth=>$none){
            $_POST[$eth]=mysql_escape_string2($none);
        }
        reset($_POST);
    }

    public function time_to_date($xtime,$time=false){
        if(!isset($GLOBALS["TPL"])){$GLOBALS["TPL"]=new templates();}
        return $this->_ENGINE_parse_body($GLOBALS["TPL"]->time_to_date($xtime,$time));
    }

    public function time_to_week($xtime){
        $monday = strtotime("last monday",$xtime);
        $monday = date('w', $monday)==date('w',$xtime) ? $monday+7*86400 : $monday;
        $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
        $this_week_sd = date("Y-m-d",$monday);
        $this_week_ed = date("Y-m-d",$sunday);
        return array($this_week_sd,$this_week_ed);
    }

    public function time_diff_min($xtime){
        return $this->time_diff_min_interval(time(),$xtime);
    }

    public function time_diff_min_interval_hours($fromtime,$totime){
        $mins=$this->time_diff_min_interval($fromtime,$totime);
        $results=$mins/60;
        VERBOSE("$fromtime - $totime = $mins / 60 = $results Hours");
        return round($results);
    }

    public function time_diff_min_interval($fromtime,$totime):int{
        if($fromtime>$totime){$difference = ($fromtime - $totime);}else {$difference = ($totime - $fromtime);}
        $results=intval(round($difference/60));
        if($results<0){$results=1;}
        VERBOSE("$fromtime - $totime = $difference = $results minutes");
        return $results;
    }

    public function ACTIVE_DIRECTORY_LDAP_CONNECTIONS():array{
        if(!class_exists("ActiveDirectory")){
            include_once("/usr/share/artica-postfix/ressources/class.ActiveDirectory.inc");
        }
        $MAIN                       = array();
        $active                     = new ActiveDirectory();
        $EnableKerbAuth             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $ActiveDirectoryConnections = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

        if($EnableKerbAuth==1){
            $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
            if($array["LDAP_SERVER"]==null){
                if($array["fullhosname"]<>null){$array["LDAP_SERVER"]=$array["fullhosname"];}
            }
            if(!isset($array["LDAP_DN"])){
                if(isset($array["WINDOWS_SERVER_ADMIN"])){$array["LDAP_DN"]=$array["WINDOWS_SERVER_ADMIN"];}
            }
            if(!isset($array["LDAP_PORT"])){$array["LDAP_PORT"]=null;}

            if(!isset($array["LDAP_PASSWORD"])){
                if(isset($array["WINDOWS_SERVER_PASS"])){$array["LDAP_PASSWORD"]=$array["WINDOWS_SERVER_PASS"];}
            }
            if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
            if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
            if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
            if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
            if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
            if($array["LDAP_SSL"]==null){$array["LDAP_SSL"]=$active->ldap_ssl;}
            if(preg_match("#^(.+?)@(.+?)@$#",trim($array["LDAP_DN"]),$re)){$array["LDAP_DN"]="$re[1]@$re[2]";}
            if(intval($array["LDAP_SSL"])==1){$array["LDAP_SERVER"]="ldaps://{$array["LDAP_SERVER"]}:636";}
            $md5=md5(serialize($array));
            $MAIN[$md5]=$array;
        }

        foreach ($ActiveDirectoryConnections as $index=>$ligne) {
            if (!is_numeric($index)) {continue;}
            if ($EnableKerbAuth == 1) {if ($index == 0) {continue;}}
            if (!isset($ligne["LDAP_PORT"])) {$ligne["LDAP_PORT"] = 389;}
            if (!isset($ligne["LDAP_SSL"])) {$ligne["LDAP_SSL"] = 0;}
            if (!isset($ligne["WINDOWS_SERVER_ADMIN"])) {
                if (isset($ligne["LDAP_DN"])) {
                    $ligne["WINDOWS_SERVER_ADMIN"] = $ligne["LDAP_DN"];
                }
            }

            $md5=md5(serialize($ligne));
            $MAIN[$md5]=$ligne;

        }

        return $MAIN;


    }






    public function kernel_binary_ver():int{
        $kernver = php_uname("r");
        if (!preg_match("#([0-9\.-]+)-amd64#", $kernver, $re)) {
            VERBOSE("ALERT !!!!! Current Kernel: NO MATCHES", __LINE__);
            return 0;
        }
        $kernver2 = $re[1];
        $kernbin = str_replace(".", "", $kernver2);
        $kernbin = str_replace("-", "", $kernbin);
        VERBOSE("Current Kernel: $kernver -- $kernbin", __LINE__);
        return intval($kernbin);
    }

    private function _translateVersionString($version):string{
        if(preg_match("#(.+?)-#",$version,$re)){$version=$re[1];}
        if(preg_match("#(.+?)\.$#",$version,$re)){$version=$re[1];}
        $curver_array=explode(".",$version);
        if(!isset($curver_array[1])){$curver_array[1]=0;}
        if(!isset($curver_array[2])){$curver_array[2]=0;}
        if(!isset($curver_array[3])){$curver_array[3]=0;}

        foreach ($curver_array as $index=>$vv){
            $vv=intval($vv);
            if(strlen($vv)==1){
                $vv="00$vv";
                $curver_array[$index]=$vv;
                continue;
            }
            if(strlen($vv)==2){
                $vv="0$vv";
                $curver_array[$index]=$vv;
                continue;
            }
            $curver_array[$index]=$vv;
        }
        return @implode(".",$curver_array);

    }

    function LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$key,$curver):int{
        $AVAILABLE_KEY=0;
        if(!isset($UPDATES_ARRAY[$key])){return 0;}
        if(trim($curver)==null){return 0;}
        VERBOSE("$key: $curver",__LINE__);


        $curver=$this->_translateVersionString($curver);
        $curverbin=str_replace(".","",$curver);
        $AVAILABLE=0;
        VERBOSE("$key: Current version $curver -- $curverbin",__LINE__);
        krsort($UPDATES_ARRAY[$key]);
        foreach ($UPDATES_ARRAY[$key] as $vbin=>$array) {
            $VERSION=$array["VERSION"];
            $VERSION_recovered=$this->_translateVersionString($VERSION);
            VERBOSE("$key: avai: $VERSION - recovered:$VERSION_recovered check $curver",__LINE__);
            $vc = intval(str_replace(".","",$VERSION_recovered));
            if ($vc <= $curverbin) {
                continue;
            }
            if ($vc > $AVAILABLE) {
                $AVAILABLE_KEY = $vbin;
                $AVAILABLE = $vc;
            }
        }
        VERBOSE("LAST $key VERSION = $AVAILABLE_KEY");
        return $AVAILABLE_KEY;
    }

    function NGINX_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_NGINX"])){return 0;}
        $APP_NGINX_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_NGINX",$APP_NGINX_VERSION);
    }
    function PDNS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_PDNS"])){return 0;}
        $PDNS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_PDNS",$PDNS_VERSION);
    }
    /**
     * Return array if a new version is available
     * @param array $function <p>
     * The function name, as a string.
     * </p>
     * @return array current version and available version <i>Product</i> new version exists and is a
     * , empty otherwise.
     * Array must include
     * NOTIF_ARRAY(Array("UPDATES_ARRAY"=>$UPDATES_ARRAY,"TOKEN_UPDATE_ARRAY"=>"APP_GO_SHIELD_SERVER",
     *  "TOKEN_VER"=>"APP_GO_SHIELD_VERSION","TOKEN_ENABLED"=>"Go_Shield_Server_Enable"));
     * </p>
     * <p>
     */
    function NOTIF_ARRAY($MAINSETTINGS=array(),$HideNewVerSwitch=true):array{
        $ERR                = array();
        if(!isset($MAINSETTINGS["TOKEN_ENABLED"])){$MAINSETTINGS["TOKEN_ENABLED"]="";}
        $UPDATES_ARRAY      = $MAINSETTINGS["UPDATES_ARRAY"];
        $TokenCurVer        = $MAINSETTINGS["TOKEN_VER"];
        $TokenCurEnabled    = $MAINSETTINGS["TOKEN_ENABLED"];
        $UPDATES_ARRAY_KEY  = $MAINSETTINGS["TOKEN_UPDATE_ARRAY"];
        if($TokenCurEnabled<>"ALWAYS") {
            if ($TokenCurEnabled <> null) {
                $EnableFeature = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($TokenCurEnabled));
            } else {
                $EnableFeature = 1;
            }
        }else{
            $EnableFeature=1;
        }
        if($EnableFeature==0) {return $ERR;}
        $CUR_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($TokenCurVer);
        $TokenHide             = "Hide{$UPDATES_ARRAY_KEY}VerIco$CUR_VERSION";
        if($HideNewVerSwitch) {
            $HideNewer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($TokenHide));
            if ($HideNewer == 1) {
                return $ERR;
            }
        }
        $AVAILABLE_VER = $this->ICON_TOP_AVAILABLE_VERSION($UPDATES_ARRAY,$UPDATES_ARRAY_KEY,$CUR_VERSION);
        if ($AVAILABLE_VER >0 ) {
            $NewVer = $UPDATES_ARRAY[$UPDATES_ARRAY_KEY][$AVAILABLE_VER]["VERSION"];
            return array("CUR_VER"=>$CUR_VERSION,"NEW_VER"=>$NewVer,"HIDE_TOKEN"=>$TokenHide);
        }
        return $ERR;
    }

    function ICON_TOP_AVAILABLE_VERSION($UPDATES_ARRAY,$ARRAY_KEY,$CURVER):int{
        if(!isset($UPDATES_ARRAY[$ARRAY_KEY])){return 0;}
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$ARRAY_KEY,$CURVER);
    }
    function DNSDIST_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_DNSDIST"])){return 0;}
        $MAINVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_DNSDIST",$MAINVERSION);
    }

    function APP_NGINX_CONSOLE_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_NGINX_CONSOLE"])){return 0;}
        $MAINVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_VERSION");
        VERBOSE("APP_NGINX_CONSOLE: Current version = $MAINVERSION",__LINE__);
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_NGINX_CONSOLE",$MAINVERSION);
    }
    function REDIS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_REDIS_SERVER"])){return 0;}
        $MAINVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_REDIS_SERVER",$MAINVERSION);
    }
    function WP_CLI_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if (!isset($UPDATES_ARRAY["WP_CLIENT"])) {return 0;}
        $MAINVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WP_CLIENT_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"WP_CLIENT",$MAINVERSION);
    }
    function CICAP_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_C_ICAP"])){return 0;}
        $CicapVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_C_ICAP",$CicapVersion);
    }
    function SYSLOG_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_SYSLOGD"])){return 0;}
        $APP_RSYSLOGD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_SYSLOGD",$APP_RSYSLOGD_VERSION);
    }


    function PRDPROXY_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_RDPPROXY"])){return 0;}
        $APP_RDPPROXY_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_RDPPROXY",$APP_RDPPROXY_VERSION);
    }

    //
    function MEMCACHED_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_MEMCACHED"])){return 0;}
        $CrVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MEMCACHED_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_MEMCACHED",$CrVersion);
    }
    function CLAMAV_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_CLAMAV"])){return 0;}
        $clamd_version = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_CLAMAV",$clamd_version);
    }
    function HAPROXY_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_HAPROXY";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }

    function MONIT_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_MONIT";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MONIT_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }
    function NAGIOS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_NAGIOS_CLIENT";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NAGIOS_CLIENT_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }
    function AUTOFS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_AUTOFS";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_AUTOFS_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }
    function SAMBA_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_SAMBA";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }
    function NETDATA_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_NETDATA";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NETDATA_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }

    function SQUID_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_SQUID"])){return 0;}
        $realsquidversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_SQUID",$realsquidversion);
    }
    function SQUID6_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_SQUID6"])){return 0;}
        $realsquidversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_SQUID6",$realsquidversion);
    }

    public function time_key_10mn(){

        $minutesTo10["00"]="00";
        $minutesTo10["01"]="00";
        $minutesTo10["02"]="00";
        $minutesTo10["03"]="00";
        $minutesTo10["04"]="00";
        $minutesTo10["05"]="00";
        $minutesTo10["06"]="00";
        $minutesTo10["07"]="00";
        $minutesTo10["08"]="00";
        $minutesTo10["09"]="00";
        $minutesTo10["10"]="10";
        $minutesTo10["11"]="10";
        $minutesTo10["12"]="10";
        $minutesTo10["13"]="10";
        $minutesTo10["14"]="10";
        $minutesTo10["15"]="10";
        $minutesTo10["16"]="10";
        $minutesTo10["17"]="10";
        $minutesTo10["18"]="10";
        $minutesTo10["19"]="10";
        $minutesTo10["20"]="20";
        $minutesTo10["21"]="20";
        $minutesTo10["22"]="20";
        $minutesTo10["23"]="20";
        $minutesTo10["24"]="20";
        $minutesTo10["25"]="20";
        $minutesTo10["26"]="20";
        $minutesTo10["27"]="20";
        $minutesTo10["28"]="20";
        $minutesTo10["29"]="20";
        $minutesTo10["30"]="30";
        $minutesTo10["31"]="30";
        $minutesTo10["32"]="30";
        $minutesTo10["33"]="30";
        $minutesTo10["34"]="30";
        $minutesTo10["35"]="30";
        $minutesTo10["36"]="30";
        $minutesTo10["37"]="30";
        $minutesTo10["38"]="30";
        $minutesTo10["39"]="30";
        $minutesTo10["40"]="40";
        $minutesTo10["41"]="40";
        $minutesTo10["42"]="40";
        $minutesTo10["43"]="40";
        $minutesTo10["44"]="40";
        $minutesTo10["45"]="40";
        $minutesTo10["46"]="40";
        $minutesTo10["47"]="40";
        $minutesTo10["48"]="40";
        $minutesTo10["49"]="40";
        $minutesTo10["50"]="50";
        $minutesTo10["51"]="50";
        $minutesTo10["52"]="50";
        $minutesTo10["53"]="50";
        $minutesTo10["54"]="50";
        $minutesTo10["55"]="50";
        $minutesTo10["56"]="50";
        $minutesTo10["57"]="50";
        $minutesTo10["58"]="50";
        $minutesTo10["59"]="50";
        $min=$minutesTo10[date("i")];
        return date("Y-m-d-").intval(date("H"))."-".$min;
    }

    public function cluster_text(){

        $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
        if($PowerDNSEnableClusterMaster==1){
            return $this->_ENGINE_parse_body("{this_server_is_a_master_cluster}");
        }
        $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
        if($PowerDNSEnableClusterSlave==1){
            $PowerDNSClusterMasterAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
            $PowerDNSClusterMasterPort=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterPort"));
            $addr="$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort";
            $this_server_is_a_client_cluster=$this->_ENGINE_parse_body("{this_server_is_a_client_cluster}");
            $this_server_is_a_client_cluster=str_replace("%s",$addr,$this_server_is_a_client_cluster);
            return $this_server_is_a_client_cluster;
        }

        return null;

    }

    public function TimeToDays($timestamp):int{
        $t=time();
        if($timestamp<$t){
            return 0;
        }
        $reste_seconds  = intval($timestamp) -intval($t);
        $days           = intval(intval($reste_seconds) / (3600*24));
        if($days<0){$days=0;}
        return intval($days);
    }

    public function _ENGINE_parse_body($html):string{
        if(!isset($GLOBALS["TPL"])){$GLOBALS["TPL"]=new templates();}
        if(is_array($html)){$html=@implode("\n", $html);}
        return strval($GLOBALS["TPL"]->_ENGINE_parse_body($html));
    }
    public function javascript_parse_text($html){
        if(!isset($GLOBALS["TPL"])){$GLOBALS["TPL"]=new templates();}
        return $GLOBALS["TPL"]->javascript_parse_text($html);

    }
    public function add_decimal($version){
        $versions=explode(".",$version);
        if(!isset($versions[2])){return $version;}
        if(intval($versions[2])<10){$versions[2]="{$versions[2]}0";}
        return @implode(".",$versions);
    }

    public function uptime(){
        $str   = @file_get_contents('/proc/uptime');
        $num   = floatval($str);
        $num   = round($num);
        $num = intdiv($num, 60);
        $mins  = $num % 60;      $num = intdiv($num, 60);
        $hours = $num % 24;      $num = intdiv($num, 24);
        $days  = $num;
        $t=array();

        if($days>2555) {
            $t[]="7 {years}";
            $days=$days-2555;
        }

        if($days>2190) {
            $t[]="6 {years}";
            $days=$days-2190;
        }
        if($days>1825) {
            $t[]="5 {years}";
            $days=$days-1825;
        }
        if($days>1460) {
            $t[]="4 {years}";
            $days=$days-1460;
        }
        if($days>1095) {
            $t[]="3 {years}";
            $days=$days-1095;
        }
        if($days>720) {
            $t[]="2 {years}";
            $days=$days-720;
        }
        if($days>360) {
            $t[]="1 {year}";
            $days=$days-360;
        }
        if($days>330) {
            $t[]="11 {months}";
            $days=$days-210;
        }
        if($days>300) {
            $t[]="10 {months}";
            $days=$days-210;
        }
        if($days>270) {
            $t[]="9 {months}";
            $days=$days-210;
        }
        if($days>240) {
            $t[]="8 {months}";
            $days=$days-210;
        }
        if($days>210) {
            $t[]="7 {months}";
            $days=$days-210;
        }
        if($days>180) {
            $t[]="6 {months}";
            $days=$days-180;
        }
        if($days>90) {
            $t[]="3 {months}";
            $days=$days-90;
        }
        if($days>60) {
            $t[]="2 {months}";
            $days=$days-60;
        }
        if($days>30) {
            $t[]="1 {month}";
            $days=$days-60;
        }
        if($days>15) {
            $t[]="2 {weeks}";
            $days=$days-15;
        }
        if($days>7) {
            $t[]="1 {week}";
            $days=$days-7;
        }
        if($days==1) {
            $t[]="$days {day}";
        }
        if($days>1){
            $t[]="$days {days}";
        }
        if($hours==1){
            $t[]="$hours {hour}";
        }
        if($hours>1){
            $t[]="$hours {hours}";
        }
        if($mins==1){
            $t[]="$mins {minute}";
        }
        if($mins>1){
            $t[]="$mins {minutes}";
        }
        return @implode(", ",$t);

    }

    public function masquerading_alreadyset($interface){
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM pnic_bridges 
            WHERE masquerading=1 AND enabled=1 AND nic_to='$interface'");
        if(intval($ligne["ID"])>0){return true;}


        $ligne=$q->mysqli_fetch_array("SELECT ID FROM firehol_masquerade 
                                WHERE nic='$interface' AND enabled=1");
        if(intval($ligne["ID"])>0){return true;}

        if(!class_exists("system_nic")){
            include_once(dirname(__FILE__)."/class.system.nics.inc");
        }
        $znic=new system_nic($interface);
        if($znic->firewall_masquerade==1){
            return true;
        }

        return false;
    }

    public function MacToVendor($MacAddr):string{

        if(!preg_match("#^([a-z0-9]+):([a-z0-9]+):([a-z0-9]+)#",$MacAddr,$re)){return "";}
        $mac_prefix     = strtoupper("{$re[1]}{$re[2]}{$re[3]}");
        if(isset($GLOBALS["MacToVendor"][$mac_prefix])){return strval($GLOBALS["MacToVendor"][$mac_prefix]);}

        $memcached      =new lib_memcached();
        $Vendor         = $memcached->getKey("HARDWAREVENDOR:$mac_prefix");
        if($memcached->MemCachedFound){
            if(strlen(trim($Vendor))>2) {
                $GLOBALS["MacToVendor"][$mac_prefix] = $Vendor;
                return $Vendor;
            }
        }

        $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $ligne2         = $q->mysqli_fetch_array("SELECT description FROM dhcpd_MacsList WHERE MacID='$mac_prefix' LIMIT 1");

        if(!is_array($ligne2) OR !isset($ligne2["description"])){
            $ligne2["description"]="";
        }
        if(!is_null($ligne2["description"])) {
            $description = trim($ligne2["description"]);
            if (strlen($description) > 108) {
                $description = substr($description, 0, 105) . "...";
            }
        }
        $GLOBALS["MacToVendor"][$mac_prefix] = $description;
        $memcached->saveKey("HARDWAREVENDOR:$mac_prefix",$description,1800);
        return strval($GLOBALS["MacToVendor"][$mac_prefix] );
    }
public function FormatSeconds($seconds):string {
    $hours = floor($seconds / 3600);
    $minutes = floor(((int)$seconds % 3600) / 60);
    $remainingSeconds = (int)$seconds % 60;

    $result = '';

    if ($hours > 0) {
        $result .= $hours . ' {hour' . ($hours > 1 ? 's}' : '}') . ' ';
    }

    if ($minutes > 0) {
        $result .= $minutes . ' {minute' . ($minutes > 1 ? 's}' : '}') . ' ';
    }

    if ($remainingSeconds > 0) {
        $result .= $remainingSeconds . ' {second' . ($remainingSeconds > 1 ? 's}' : '}');
    }

    return trim($result);
}

    public function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

    public function icon_up($js=null,$security=null){

        if(!$this->IsSecurity($security)){$js=null;}

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $js=null;
            }
        }


        if($js==null){return
            "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" ><i class='fa fa-arrow-up' style='color:#c2ccd1'></i></button>";}

        $md=md5($js);
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"trup$md(this);$js\" >
        <i class='fa fa-arrow-up' style='color:#18a689'></i></button>


		<script>
		function trup$md(e){
			var row = $(e).parents(\"tr:first\");
         	row.insertBefore(row.prev());
        }
         </script>
		";

    }
    public function icon_password($js=null,$security=null):string{
        $ico="fa-solid fa-user-lock";
        if(!$this->IsSecurity($security)){$js=null;}
        if($js==null){return "<button type='button' class='btn btn-white btn-bitbucket'>
        <i class='$ico' style='color:#c2ccd1'></i></button>";}
        $md=md5($js);
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"trdnw$md(this);$js\" >
        <i class='$ico' style='color:#18a689'></i></button>
        ";

    }

    public function icon_down($js=null,$security=null):string{
        $ico="fa fa-arrow-down";
        if(!$this->IsSecurity($security)){$js=null;}
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $js=null;
            }
        }


        if($js==null){return "<button type='button' class='btn btn-white btn-bitbucket'>
        <i class='$ico' style='color:#c2ccd1'></i></button>";}

        $md=md5($js);
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"trdnw$md(this);$js\" >
        <i class='$ico' style='color:#18a689'></i></button>
            <script>
                function trdnw$md(e){
		            var row = $(e).parents(\"tr:first\");
		            row.insertAfter(row.next());
                }
            </script>
        ";


        //return "<a href=\"#\" OnClick=\"$js\">$icon</a>";
    }
    public function icon_arrow_right($js=null){
        $icon="<i class='fa fa-arrow-right'></i>";
        if($js==null){return $icon;}
        return "<a href=\"#\" OnClick=\"$js\">$icon</a>";

    }


    public function table_menu($actions=array(),$CurrentPage=null,$addons=null,$forceFunc=null){
        $id=md5(time());
        $t=time();
        $search_text="{search_items}";

        $f[]="\t<div style='margin-bottom:15px;margin-top:5px'><table style='width:100%'>";
        $f[]="\t<tr>";
        $f[]="\t<td style='vertical-align:middle;width:99%'>";
        $f[]="\t<div class=\"input-group\">
				<input type=\"text\" class=\"form-control\" id='$id' value=\"\" placeholder=\"$search_text\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
				<span class=\"input-group-btn\">
					<button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
				</span>
			</div>";
        $f[]="\t</td>";
        $f[]="\t<td style='vertical-align:middle;' nowrap>";
        $f[]="\t<table style='width:100%'><tr>";
        $f[]="\t<td style='vertical-align:middle;padding-left:8px;width:1%'>";
        $f[]="\t\t\t<a href=\"javascript:blur();\" OnClick=\"ss$t()\">";
        $f[]="\t\t\t\t<i class=\"fas fa-sync\"></i>";
        $f[]="\t\t\t</a>";
        $f[]="\t</td>";



        foreach ($actions as $array){

            $icon=$array["ICON"];
            $js=$array["JS"];
            $js=str_replace("%func",base64_encode("ss$t()"),$js);
            $f[]="\t<td style='padding-left:8px;vertical-align: middle;width:1%'>";
            $f[]="\t\t\t<a href=\"javascript:blur();\" OnClick=\"$js\">";
            $f[]="\t\t\t\t<i class=\"$icon\"></i>";
            $f[]="\t\t\t</a>";
            $f[]="</td>";
        }


        $f[]="</tr></table>";
        $f[]="</td></tr></table></div>";
        $f[]="<div id='div-$t' style='background-color: #ffffff;margin:0;padding:0'></div>";
        $f[]="<script>";
        $f[]="\tfunction Search$t(e){";
        $f[]="\t\tif(!checkEnter(e) ){return false;}";
        $f[]="\tss$t();";
        $f[]="\t}
	
function ss$t(){
	var ss=encodeURIComponent(document.getElementById('$id').value);
	LoadAjax('div-$t','$CurrentPage?search='+ss+'&function=ss$t{$addons}');
}
	
function Start$t(){
	ss$t();
}
Start$t();";

if($forceFunc<>null){
    $f[]="function $forceFunc(){  ss$t();}";
}
        
        $f[]="</script>";
        return @implode("\n",$f);

    }


    public function table_ibox($js,$title){
        $id=md5(microtime(true).$js);
        $html[]="<div class='ibox' style='margin-top:10px'>";
        $html[]="	<div class='ibox-title'>
						<div class=\"ibox-tools\">
							<a href=\"#\" class=\"collapse-link\" OnClick=\"Collapse$id()\" >
								<i class=\"fa fa-chevron-up\" id='ico-$id'></i>
							</a>
							<a href=\"#\" class=\"collapse-link\" OnClick=\"Load$id();\" jsFunctionTable='Load$id'>
								<i class=\"fal fa-sync-alt\" id='ico-$id'></i>
							</a>
						</div>";
        $html[]="	<H3 style='font-weight:bolder'>$title</H3>";
        $html[]="	</div>";
        $html[]="<div class='ibox-content' id='table-$id'></div>";
        $html[]="</div>";
        $html[]="<script>";
        $html[]="function Collapse$id(){";
        $html[]="\t$('#table-$id').slideToggle('fast');";
        $html[]="\t$('#ico-$id').toggleClass('fa-chevron-up fa-chevron-down');";
        $html[]="}";
        $html[]="function Load$id(){";
        $html[]="\tLoadAjaxTiny('table-$id','$js');";
        $html[]="}";
        $html[]="Load$id();";

        $html[]="";

        $html[]="</script>";

        return @implode("\n", $html);
    }

    public function UnBoundVersionArray(){
        $ARRAY=array();
        $UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");
        $version=explode(".",$UnboundVersion);
        $ARRAY["MAJOR"]=intval($version[0]);
        $ARRAY["MINOR"]=intval($version[1]);
        $ARRAY["REVISION"]=$version[2];
        return $ARRAY;

    }

    public function ClickMouse($js,$bold=false){
        $initial="this.style.fontWeight='initial'";
        if(preg_match("#pop:(.+)#",$js,$re)){
            $js="s_PopUpFull('{$re[1]}','1024','900');";
        }
        if($bold){
            $initial="this.style.fontWeight='bold'";
        }

        $ClickMouse="onMouseOver=\"this.style.cursor='pointer';this.style.fontWeight='bold';this.style.textDecoration=underline\" 
        OnMouseOut=\"this.style.cursor='default';$initial;this.style.textDecoration=none\"
        OnClick=\"$js\"
        ";
        return $ClickMouse;
    }

    public function DropDownMultiple($array,$CurrentValue,$placeholder,$Loadjs,$width=0){
        $stylewidth="";
        if($width>0){
            $stylewidth="width: '{$width}px'";
        }
        $placeholder=$this->_ENGINE_parse_body($placeholder);
        $t=md5(microtime(true).$placeholder.$Loadjs."Multiple");
        $f[]="<div class=\"btn-group\">";
        $f[]="<select data-placeholder=\"{$placeholder}\" id='DropDown$t' class=\"form-control\" multiple=\"multiple\">";
        if (is_null($CurrentValue)){
            $CurrentValue="";
        }
        $SEL=array();
        if( strpos($CurrentValue,",")>0){
            $tb=explode(",",$CurrentValue);
            foreach ($tb as $zz){
                $SEL[]="\"$zz\"";
            }
        }else{
            $SEL[]="\"$CurrentValue\"";
        }
        $defaults=@implode(",",$SEL);

        foreach ($array as $value=>$text){

            $f[]="<option value=\"$value\">$text</option>";
        }
        $f[]="</select>";
        $f[]="</div>";
        $f[]="<script>";
        $f[]="$(\"#DropDown$t\").select2({".$stylewidth."});";
        $f[]="$(\"#DropDown$t\").val([$defaults]).trigger(\"change\");";
        $f[]="$(\"#DropDown$t\").on('change', function() {";
        $f[]="var selectedValue = encodeURIComponent($(this).val());";
        $f[]="let js='$Loadjs';";
        $f[]="let uri = js.replace(\"%s\", selectedValue);";
        $f[]="Loadjs(uri)";
        $f[]="});";
        $f[]="</script>";

        return @implode("\n",$f);
    }
    public function DropDown($array,$CurrentValue,$placeholder,$Loadjs){
        $placeholder=$this->_ENGINE_parse_body($placeholder);
        $t=md5(microtime(true).$placeholder.$Loadjs);
        $f[]="<div class=\"btn-group\">";
        $f[]="<select data-placeholder=\"{$placeholder}\" id='DropDown$t' class=\"form-control\">";
        if (is_null($CurrentValue)){
            $CurrentValue="";
        }

        if($CurrentValue==""){
            $f[]="<option value=\"\" selected>{select}</option>";
        }


        foreach ($array as $value=>$text){
            $selected="";
            if($value==$CurrentValue){
                $selected="selected";
            }
            $f[]="<option value=\"$value\" $selected>$text</option>";
        }
        $f[]="</select>";
        $f[]="</div>";
        $f[]="<script>";
        $f[]="$(\"#DropDown$t\").select2({ allowClear: true });";
        $f[]="$(\"#DropDown$t\").on('change', function() {";
        $f[]="var selectedValue = encodeURIComponent($(this).val());";
        $f[]="let js='$Loadjs';";
        $f[]="let uri = js.replace(\"%s\", selectedValue);";
        $f[]="Loadjs(uri)";
        $f[]="});";
        $f[]="</script>";

        return @implode("\n",$f);
    }

    public function DropDownButton($text,$array,$class="btn-primary"){
        $text=$this->_ENGINE_parse_body($text);
        $f[]="<div class=\"btn-group\">";
        $f[]="<button data-toggle=\"dropdown\" class=\"btn $class dropdown-toggle\">$text <span class=\"caret\"></span></button>";
        $f[]="<ul class=\"dropdown-menu\">";

        foreach ($array as $text=>$js){
            if($text=="<hr>"){
                $f[]="<li class=\"divider\"></li>";
                continue;
            }
            $f[]="\t<li><a href=\"javascript:blur()\" OnClick=\"$js\">$text</a></li>";

        }

        $f[]="</ul>";
        $f[]="</div>";
        return @implode("\n", $f);

    }

    public function GetCloudLastversion($APP_KEY,$Curver){
        VERBOSE("[$APP_KEY]",__LINE__);
        $ZCurver        = explode(".",$Curver);
        $CountOfCurver  = count($ZCurver);
        VERBOSE("Current version: $Curver",__LINE__);



        $IntCurVer      = intval(@implode("",$ZCurver));
        if($IntCurVer==0){
            VERBOSE("$APP_KEY=[$Curver] -> CountOf($CountOfCurver) ==0 , Aborting",__LINE__);
            return array(0,0);
        }
        $NEWMAIN        = array();
        $FINAL          = array();

        $UPDATES_ARRAY               = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("v4softsRepo")));
        if(!isset($UPDATES_ARRAY[$APP_KEY])){
            VERBOSE("UPDATES_ARRAY[$APP_KEY] ->!isset",__LINE__);
            return array(0,0);
        }

        $ISARRAY=$UPDATES_ARRAY[$APP_KEY];
        krsort($ISARRAY);
        foreach ($ISARRAY as $integ=>$array){
            $zVERSION=explode(".",$array["VERSION"]);
            $CountOfVersion=count($zVERSION);
            //VERBOSE("$integ: {$array["VERSION"]} $CountOfVersion!=$CountOfCurver",__LINE__);

            if($CountOfVersion!=$CountOfCurver){
                $xVer=array();
                for($i=0;$i<$IntCurVer;$i++){
                    $xVer[]=$zVERSION[$i];
                }
            }else{
                $xVer=explode(".",$array["VERSION"]);
            }

            $BinVer=intval(@implode("",$xVer));
            //VERBOSE("$BinVer: {$array["VERSION"]}",__LINE__);
            $NEWMAIN[$BinVer]=$array["VERSION"];
        }

        krsort($NEWMAIN);

        foreach ($NEWMAIN as $bin=>$ver){
            $FINAL[]=$bin;

        }

        $Latest=$FINAL[0];
        $Latesta=$Latest/100;
        $Latestb=$IntCurVer/100;
        if($Latesta>10){
            VERBOSE("Latest: remove leading 0 in $Latest",__LINE__);
            if(preg_match("#^([0-9]+)0$#",$Latest,$re)){
                $Latest=intval($re[1]);
            }
        }


        VERBOSE("Latest: $Latest question is : $Latest>$IntCurVer $Latesta/$Latestb ?",__LINE__);
        if($Latest>$IntCurVer){
            if(!isset($NEWMAIN[$Latest])){
                return array(0,0);
            }
            return array($Latest,$NEWMAIN[$Latest]);
        }
        return array(0,0);

    }

    public function icon_nothing($js=null){
        $icon="<i class='fa fa-minus'></i>";
        if($js==null){return $icon;}
        return "<a href=\"#\" OnClick=\"$js\">$icon</a>";

    }

    public function icon_list($js=null,$security=null){
        $pic="fad fa-list-ul";
        return $this->__icon_defaults($pic,$js,$security);

    }

    private function __icon_defaults($pic,$js,$security){
        $color="#18a689";
        if(preg_match("#color:(.+?):(.+)#",$pic,$re)){
            $color=$re[1];
            $pic=$re[2];
        }
        $btclass="btn btn-default btn-bitbucket";
        if(!$this->IsSecurity($security)){return "
        <button type='button' 
            class='$btclass' OnClick=\"Loadjs('fw.error.noprivs.php');\" >
            <i class='$pic' style='color:#c2ccd1'></i>
        </button>";}

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                return "<button type='button' 
                class='$btclass' OnClick=\"blur()\" >
                <i class='$pic' style='color:#c2ccd1'></i></button>";
            }
        }

        if($js==null){
            $color="#c2ccd1";
            $js="Blur();";
        }
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
            return "<button type='button' class='$btclass' $jsav >
            <i class='$pic' style='color:$color'></i>
        </button>";
        }
        $md=md5("$jsav$pic".microtime(true));
        $jsav="OnClick=\"function$md();\"";
        $html[]="<button type='button' class='$btclass' $jsav >";
        $html[]="<i class='$pic' style='color:$color' id='btn-$md'></i>";
        $html[]="</button>";
        $html[]="<script>";
        $html[]="function functionStop$md(){";
        $html[]="\tif ( document.getElementById('btn-$md') ){";
        $html[]="\tdocument.getElementById('btn-$md').classList.remove('fa-spin');";
        $html[]="\t}";
        $html[]="}";
        $html[]="function functionRun$md(){";
        $html[]="\t$js;";
        $html[]="\tsetTimeout(\"functionStop$md()\",1000);";
        $html[]="}";
        $html[]="function function$md(){";
        $html[]="\tdocument.getElementById('btn-$md').classList.add('fa-spin');";
        $html[]="\tsetTimeout(\"functionRun$md()\",200);";
        $html[]="}";
        $html[]="</script>";
        return @implode("\n",$html);
    }

    public function icon_copy($js=null,$security=null){
        $pic="fa fa-copy";
        return $this->__icon_defaults($pic,$js,$security);
    }

    public function icon_select($js=null,$security=null){
        $pic="fas fa-arrow-right";
        return $this->__icon_defaults($pic,$js,$security);
    }

    public function FATAL_ERROR_SHOW_128($text,$js=null){
        $text=$this->_ENGINE_parse_body($text);
        $button=null;
        if($js<>null){
            $button="<p><div style='text-align:right;margin:30px'>".$this->button_autnonome("{click_to_fix}",$js,"fas fa-tools")."</div></p>";
        }

        $f=$this->div_warning("$text$button");
        $data=$this->_ENGINE_parse_body($f);
        if(strpos("$data", "%email%")){
            $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
            $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
            if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
            if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
            $data=str_replace("%email%", $LicenseInfos["EMAIL"], $data);
        }

        return $data;

    }


    public function td_href($text,$tooltip="{click_to_edit}",$js=null,$security=null):string{
        if(!is_null($text)) {
            $text = trim($text);
        }
        if(is_null($text)){
            return "";
        }

        if(is_null($tooltip)){$tooltip="";}
        if($text==null){$text="?????";}
        $fromtooltip=$tooltip;
        $memmd=md5("$text$tooltip$js");
        if(isset($GLOBALS[__CLASS__][__FUNCTION__][$memmd])){return $GLOBALS[__CLASS__][__FUNCTION__][$memmd];}
        $id=md5("$text$tooltip$js".microtime(true).rand(0,time()));
        $text=$this->_ENGINE_parse_body($text);
        $border="border-bottom:1px solid #CCCCCC;";
        if(preg_match("#fa-exclamation-square#",$text)){$border=null;}


        if(!$this->IsSecurity($security)){
            $js="";
            $border="";
            $tooltip="<strong>{ERROR_NO_PRIVS}</strong><hr>$tooltip";
        }

        if(strlen($tooltip)<3){
            $f[]="<span OnMouseOver=\"this.style.cursor='pointer';this.style.color='#337AB7'\"";
            $f[]="OnMouseOut=\"this.style.cursor='default';this.style.color=''\"";
            $f[]="style=\"{$border}font-weight:bold\"";
            $f[]="OnClick=\"$js\" id=\"$id\">$text</span>";
            $GLOBALS[__CLASS__][__FUNCTION__][$memmd]=@implode(" ", $f);
            return @implode(" ", $f);

        }
        $tooltip=$this->_ENGINE_parse_body($tooltip);

        $f[]="<span OnMouseOver=\"this.style.cursor='pointer';this.style.color='#337AB7'\"";
        $f[]="OnMouseOut=\"this.style.cursor='default';this.style.color=''\"";
        $f[]="style=\"{$border}font-weight:bold\"";
        $f[]="data-toggle=\"popover\"";
        $f[]="data-placement=\"auto right\"";
        $f[]="data-content=\"$tooltip\"";
        $f[]="OnClick=\"$js\" id=\"$id\">$text</span>
		<script type=\"text/javascript\">$(\"#$id\").popover({ trigger: 'hover',html: true,});</script>";
        if($GLOBALS["VERBOSE"]){echo "->td_href($text,$fromtooltip,...)<br>\n";}
        $GLOBALS[__CLASS__][__FUNCTION__][$memmd]=@implode(" ", $f);
        return @implode(" ", $f);

    }

    public function js_confirm_empty($text,$field,$value,$jsafter=null):bool{
        $tt=time();
        $page=CurrentPageName();
        $jsafter=$this->jsToTry($jsafter);
        $confirm=$this->javascript_parse_text("{empty_all_data_from} $text ?");
        $title=$this->javascript_parse_text("{are_you_sure}");
        $yes_delete_it=$this->javascript_parse_text("{yes_empty}");
        $success=$this->javascript_parse_text("{success}");
        $success_text=$this->javascript_parse_text("{all_entries_was_removed}");
        $Cancel=$this->javascript_parse_text("{cancel}");
        header("content-type: application/x-javascript");
        echo "
		var xDelete$tt= function (obj) {
		    var results=obj.responseText;
		    if(results.length>3){
		        swal({title: \"Oops...\", text: results, html: true, type: \"warning\" });
	        }else{
	            swal(\"$success!\", '$success_text: $value', \"success\");
	            $jsafter
	            LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
	        }
		}
	
		function Delete$tt(){
		    var XHR = new XHRConnection();
		    XHR.appendData('$field','$value');
		    XHR.sendAndLoad('$page', 'POST',xDelete$tt);
		}
	
	swal({
	title: \"$title\",
	text: \"$confirm\",
	type: \"warning\",
	showCancelButton: true,
	confirmButtonColor: \"#DD6B55\",
	confirmButtonText: \"$yes_delete_it\",
	cancelButtonText:\"$Cancel\",
	closeOnConfirm: false
	},
		function(){
		Delete$tt();
	});";
        return true;
    }

    public function js_display_results($html,$false=false,$title=null):bool{
        $type="success";
        if($false){$type="error";}
        header("content-type: application/x-javascript");
        if($title==null){$title=$this->javascript_parse_text("{results}");}
        $html=$this->_ENGINE_parse_body($html);
        $html=str_replace('"', "'", $html);
        $html=str_replace("\n", "<br>", $html);
        $html=str_replace("<br><br>", "<br>", $html);
        echo "swal({
		title: \"$title\",
		text: \"$html\",
		html: true,
		type: \"$type\"
	});";
        return true;
    }

    public function js_confirm_execute($text,$field,$value,$jsafter=null):bool{
        $tt=time();
        $page=CurrentPageName();
        $confirm=$this->_ENGINE_parse_body("{execute_this_task}: $text ?");
        $confirm=str_replace("\n","<br>",$confirm);
        $confirm=str_replace("'","`",$confirm);
        $title=$this->_ENGINE_parse_body("{are_you_sure}");
        $title=str_replace("\n"," ",$title);
        $title=str_replace("'","`",$title);

        $yes_delete_it=$this->javascript_parse_text("{yes_execute_it}");
        $success=$this->javascript_parse_text("{success}");
        $success_text=$this->javascript_parse_text("{item_executed}: {apply_upgrade_help}");
        $Cancel=$this->javascript_parse_text("{cancel}");
        header("content-type: application/x-javascript");
        echo "
	var xDelete$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){
		swal({
		title: \"Oops...\",
		text: results,
		html: true,
		type: \"warning\"
	});
	
	}else{
	
		swal({
            title: '$success!',
              text: '$success_text',
              type: \"success\",
              timer: 2000
           });

	$jsafter
	LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
	}
	
	}
	
	function Delete$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('$field','$value');
	XHR.sendAndLoad('$page', 'POST',xDelete$tt);
	
	}
	
	swal({
	title: \"$title\",
	text: \"$confirm\",
	html: true,
	type: \"warning\",
	showCancelButton: true,
	confirmButtonColor: \"#DD6B55\",
	confirmButtonText: \"$yes_delete_it\",
	cancelButtonText:\"$Cancel\",
	closeOnConfirm: false
	},
	function(){
	Delete$tt();
	});";
        return "";
    }

    public function popup_no_privs(){
        $this->popup_error("{ERROR_NO_PRIVS2}");
    }

    public function popup_error($text):bool{
        $text=$this->_ENGINE_parse_body($text);
        $text=str_replace("\n", "<br>", $text);
        $text=base64_encode($text);

        header("content-type: application/x-javascript");
        $error=$this->_ENGINE_parse_body("{error}");


        $jsa[]="$.alert({";
        $jsa[]="theme: 'red-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"$error...\",";
        $jsa[]="content: base64_decode('$text'),";
        $jsa[]="icon: 'fas fa-exclamation',";
        $jsa[]="defaultButtons: {";
        $jsa[]="    ok: {";
        $jsa[]="        action: function (){ }";
        $jsa[]="    }";
        $jsa[]="        }";
        $jsa[]="});";
        echo @implode("\n",$jsa);
        return true;
    }
    public function popup_info($text,$js=null){
        $text=$this->_ENGINE_parse_body($text);
        $text=str_replace("'", "`", $text);
        $text=str_replace("\n", "<br>", $text);
        header("content-type: application/x-javascript");
        echo "swal( {title:'OK', text:'$text', html: true,type:'info'});\n$js";
    }

    function ParseFileRegex($path,$regex):array{

        if(!is_file($path)){
            writelogs("$path no such file",__FUNCTION__,__FILE__,__LINE__);
            return array(false,array());
        }
        $t=explode("\n",@file_get_contents($path));
        foreach ($t as $line){
            $line=trim($line);
            if($line==null){continue;}
            VERBOSE("[$line] == /$regex/",__LINE__);
            if(preg_match("#$regex#i",$line,$re)){return array(true,$re);}
        }
        return array(false,array());
    }


    public function js_mysql_alert($text):bool{
        header("content-type: application/x-javascript");
        $f[]=$text;
        $trace=debug_backtrace();

        foreach ($trace as $debug){
            $f[]="Called by ". basename($debug["file"])." function {$debug["function"]}() Line {$debug["line"]}()";
        }

        $text=@implode("<br>",$f);
        $text=$this->_ENGINE_parse_body($text);
        $text=str_replace("'", "\'", $text);
        $text=str_replace("\n", "<br>", $text);
        $error=$this->_ENGINE_parse_body("{error}");


        $jsa[]="$.alert({";
        $jsa[]="theme: 'red-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"$error: SQL...\",";
        $jsa[]="content: '$text',";
        $jsa[]="icon: 'fas fa-exclamation',";
        $jsa[]="defaultButtons: {";
        $jsa[]="    ok: {";
        $jsa[]="        action: function (){ }";
        $jsa[]="    }";
        $jsa[]="        }";
        $jsa[]="});";
        echo @implode("\n",$jsa);
        return true;


    }
    public function js_executed_background($text=null):bool{
        header("content-type: application/x-javascript");
        $operation_in_background=$this->_ENGINE_parse_body("{operation_in_background}");
        $text=$this->_ENGINE_parse_body($text);
        $text=str_replace("'", "\'", $text);
        $text=str_replace("\n", "<br>", $text);


        $jsa[]="$.alert({";
        $jsa[]="theme: 'white-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"<p style='margin-top:20px'>$operation_in_background</p>\",";
        $jsa[]="content: '<H1 class=center>&laquo;&nbsp;$text&nbsp;&raquo;</H1>',";
        $jsa[]="icon: 'fas fa-info-circle fa-4x',";
        $jsa[]="defaultButtons: {";
        $jsa[]="    ok: {";
        $jsa[]="        action: function (){ }";
        $jsa[]="    }";
        $jsa[]="        }";
        $jsa[]="});";
        echo @implode("\n",$jsa);
        return true;


    }
    public function js_ok($text=null):bool{
        header("content-type: application/x-javascript");
        $success=$this->_ENGINE_parse_body("{success}");
        $text=$this->_ENGINE_parse_body($text);
        $text=str_replace("'", "\'", $text);
        $text=str_replace("\n", "<br>", $text);
        $jsa[]="$.alert({";
        $jsa[]="theme: 'white-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"<p style='margin-top:20px;margin-left:5px'>$success</p>\",";
        $jsa[]="content: '<H3 class=center>&laquo;&nbsp;$text&nbsp;&raquo;</H3>',";
        $jsa[]="icon: 'fas fa-thumbs-up fa-4x',";
        $jsa[]="defaultButtons: {";
        $jsa[]="    ok: {";
        $jsa[]="        action: function (){ }";
        $jsa[]="    }";
        $jsa[]="        }";
        $jsa[]="});";
        echo @implode("\n",$jsa);
        return true;
    }
    public function js_config_applied($text=null):bool{
        header("content-type: application/x-javascript");
        $success=$this->_ENGINE_parse_body("{success}");
        $text=$this->_ENGINE_parse_body("{settings_applied}");
        $text=str_replace("'", "\'", $text);
        $text=str_replace("\n", "<br>", $text);
        $jsa[]="$.alert({";
        $jsa[]="theme: 'white-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"<p style='margin-top:20px;margin-left:5px'>$success</p>\",";
        $jsa[]="content: '<H3 class=center>&laquo;&nbsp;$text&nbsp;&raquo;</H3>',";
        $jsa[]="icon: 'fas fa-thumbs-up fa-4x',";
        $jsa[]="defaultButtons: {";
        $jsa[]="    ok: {";
        $jsa[]="        action: function (){ }";
        $jsa[]="    }";
        $jsa[]="        }";
        $jsa[]="});";
        echo @implode("\n",$jsa);
        return true;
    }

    public function validate_url($url) {
        $path = parse_url($url, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        $url = str_replace($path, implode('/', $encoded_path), $url);

        return (bool)filter_var($url, FILTER_VALIDATE_URL);
    }

    public function post_error($text):string{
        $text=$this->javascript_parse_text($text);
        $text=str_replace("\n","<br>",$text);
        $text=str_replace("<br><br>","<br>",$text);
        $text=str_replace("'","`",$text);

        return "jserror:$text";
    }



    public function js_error($html,$FUNCTION=null,$FILE=null,$LINE=null):bool{
        $added=array();
        $html=$this->_ENGINE_parse_body($html);
        if($FUNCTION<>null){
            $added[]="In function :$FUNCTION";
        }
        if($LINE<>null){
            $added[]="In Line :$LINE";
        }
        if($FILE<>null){
            $added[]="In File :".basename($FILE);
        }

        return $this->popup_error($html."<hr>".@implode("<br>",$added));


    }

    public function js_error_stop($text){
        header("content-type: application/x-javascript");

        $text=str_replace("<br>", "\\n", $text);
        $f[]=$text;

        $trace=debug_backtrace();
        foreach ($trace as $debug){
            $f[]="Called by ". basename($debug["file"])." function {$debug["function"]}() Line {$debug["line"]}()";
        }


        $this->popup_error(@implode("<br>",$f));
        exit;


    }

    public function js_confirm_delete_from_id($id,$field,$main_id=0,$jsafter=null){
        $tt=time();
        $page=CurrentPageName();
        $confirm=$this->_ENGINE_parse_body("{delete_from_query}: ");
        $title=$this->javascript_parse_text("{are_you_sure}");
        $title=str_replace("%C3%8A","Ê",$title);
        $title=str_replace("%C3%BB","û",$title);
        $yes_delete_it=$this->javascript_parse_text("{yes_delete_it}");
        $success=$this->javascript_parse_text("{success}");
        $success_text=$this->javascript_parse_text("{item_deleted}");
        $Cancel=$this->javascript_parse_text("{cancel}");
        header("content-type: application/x-javascript");
        echo "

		
var xDelete$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){
			swal({
			title: \"Oops...\",
			text: results,
			html: true,
			type: \"warning\"
		});
		
	}else{
		var zvalue=document.getElementById('$id').value;	
		swal({
		title:\"$success!\",
		text:\"$success_text: <b>\"+zvalue+\"</b>\",
		html: true,
		type: \"success\"});
		LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		$jsafter
	}
	
}
	
function Delete$tt(){
	var zvalue=document.getElementById('$id').value;	
	var XHR = new XHRConnection();
	XHR.appendData('$field',zvalue);
	XHR.appendData('main_id','$main_id');
	XHR.sendAndLoad('$page', 'POST',xDelete$tt);
	
}
function swallStart(){	
	var zvalue=document.getElementById('$id').value;	
	if(zvalue.length==0){zvalue='*';}
	
	swal({
		title: \"$title\",
		text: \"$confirm\" +zvalue,
		type: \"warning\",
		html: true,
		showCancelButton: true,
		confirmButtonColor: \"#DD6B55\",
		confirmButtonText: \"$yes_delete_it\",
		cancelButtonText:\"$Cancel\",
		closeOnConfirm: false
	},
	function(){
		Delete$tt();
	});
			
		
}
		swallStart();";
    }
    public function js_dialog_confirm_action($text,$field,$value,$jsafter=null,$freetext=false):bool{
        $tt=time();
        $valueSrc=$value;
        if($value=="none"){$value=null;}else{$value=": <b>$value</b>";}
        $page=CurrentPageName();
        $confirm=$this->_ENGINE_parse_body($text);
        $confirm=str_replace("\n","",$confirm);
        $confirm=str_replace(".<br>",".",$confirm);
        $confirm=str_replace(".",".<br>",$confirm);
        $title=$this->javascript_parse_text("{are_you_sure}");
        $title=str_replace("%C3%8A","Ê",$title);
        $yes_delete_it=$this->javascript_parse_text("{yes_execute_task}");
        $success=$this->javascript_parse_text("{success}");
        $success_text=$this->javascript_parse_text("{task_executed}");
        $Cancel=$this->javascript_parse_text("{cancel}");
        header("content-type: application/x-javascript");
        echo "
var xDelete$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){
		swal({
 			title: \"Oops...\",
  			text: results,
  			html: true,
  			type: \"warning\"
		});

	}else{
		swal({
			title:\"$success!\", 
			text:\"$success_text{$value}\",
			html: true,
			type: \"success\"});
		LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		$jsafter
		}
		
	}
	
	function Delete$tt(){
		var XHR = new XHRConnection();
		XHR.appendData('$field','$valueSrc');
		XHR.sendAndLoad('$page', 'POST',xDelete$tt);
	
	}
				
		swal({
			title: \"$title\",
			text: \"$confirm\",
			type: \"info\",
			html: true,
			showCancelButton: true,
			confirmButtonColor: \"#DD6B55\",
			confirmButtonText: \"$yes_delete_it\",
			cancelButtonText:\"$Cancel\",
			closeOnConfirm: false
		},
		function(){
			Delete$tt();
		});";

        return true;
    }

    public function js_confirm_delete($text,$field,$value,$jsafter=null,$freetext=false):bool{
        $tt                 = time();
        $page               = CurrentPageName();
        $delete_object      = $this->_ENGINE_parse_body("{delete_object}");
        $Cancel             = $this->javascript_parse_text("{cancel}");
        $yes_delete_it      = $this->javascript_parse_text("{yes_delete_it}");
        $title              = $this->javascript_parse_text("{are_you_sure}");
        $title              = str_replace("%C3%8A","Ê",$title);
        $title              = str_replace("%C2%BF","?",$title);
        $title              = str_replace("%C3%C2¡","á",$title);
        $title              = str_replace("'","`",$title);

        if(!$freetext){
            $confirm=$this->_ENGINE_parse_body("{delete_this_item}:<br> <strong style=\"font-size:16px\">$text ?</strong>");
        }else{
            $confirm=$this->_ENGINE_parse_body($text);
        }
        $confirm=str_replace("\n","<br>",$confirm);
        $confirm=base64_encode("<div id=\"error_$tt\"></div>$confirm<br>$title?");
        header("content-type: application/x-javascript");

        $jsa[]="$.confirm({";
        $jsa[]="theme: 'red-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"$delete_object...\",";
        $jsa[]="content: base64_decode('$confirm'),";
        $jsa[]="icon: 'fas fa-trash-alt',";
        $jsa[]="    buttons: {
        confirm: {
            text: '$yes_delete_it',
            action: function () { Delete$tt(); }
        },
        cancel: {
            text: '$Cancel',
            
        },
    }";
        $jsa[]="});";
        echo @implode("\n",$jsa);

        $error=base64_encode($this->_ENGINE_parse_body("{error}"));
    $jsb[]="var xDelete$tt = function (obj) {";
    $jsb[]="\tvar results=obj.responseText;";
    $jsb[]="\tif(results.length>3){";
        $jsb[]="\t\t$.alert({";
        $jsb[]="\t\ttheme: 'red-theme',";
        $jsb[]="\t\tcontainerFluid: true,";
        $jsb[]="\t\ttitle: base64_decode('$error'),";
        $jsb[]="\t\tcontent: results,";
        $jsb[]="\t\ticon: 'fas fa-exclamation',";
        $jsb[]="\t\tdefaultButtons: {";
        $jsb[]="\t\t    ok: {";
        $jsb[]="\t\t        action: function (){ }";
        $jsb[]="\t\t    }";
        $jsb[]="\t\t        }";
        $jsb[]="\t\t});";
    $jsb[]="\treturn;";
    $jsb[]="\t}";
	$jsb[]="\t$jsafter;";
    $jsb[]="\t}";

        $jsb[]="function Delete$tt(){";
    $jsb[]="var XHR = new XHRConnection();";
	$jsb[]="XHR.appendData('$field','$value');";
	$jsb[]="XHR.sendAndLoad('$page', 'POST',xDelete$tt);";
$jsb[]="}";
        echo @implode("\n",$jsb);
        return true;
    }




    public function button_ids_reconfigure():string{

        $jsrestart=$this->framework_buildjs(
            "/suricata/restart",
            "suricata.progress",
            "suricata.progress.txt","reconfigure-service-div"
        );


        return $this->_ENGINE_parse_body("<div class=\"btn-group\" data-toggle=\"buttons\">
				<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_changes} </label>
			</div>
			<div id='reconfigure-service-div'></div>
		");
    }

    public function StringToFonts($text,$add=null):string{
        if(is_null($text)) {
            if (is_null($add)) {
                return "";
            }
        }
        if($add<>null){$add=" $add";}
        $text=str_replace("&nbsp;"," ",$text);
        $period="<i class='fa-solid fa-period$add'></i>";
        $str_split=str_split($text);
        $CURVERz=array();
        foreach ($str_split as $char) {
            if ($char == null) {
                continue;
            }
            if ($char == " ") {
                $char=".";
            }

            if ($char == ".") {
                $CURVERz[] = $period;
                continue;
            }
            if(is_numeric($char)) {
                $CURVERz[] = "<i class='fa-solid fa-$char$add'></i>";
                continue;
            }
            $CURVERz[] = "<i class='fa-solid fa-$char$add'></i>";
        }
        if(count($CURVERz)==0){return "";}
        return @implode("",$CURVERz);
    }



    public function button_upload($btname="{upload_a_file}",$currentpage=null,$classname=null,$addedparams=null,$field_name=null):string{
        $style=null;

        //height:46px;padding-top:10px;font-size:18px;

        if($classname<>null){

            if(preg_match("#style=(.+?);class=(.+)#",$classname,$re)){
                $style="style='$re[1]'";
                $classname=$re[2];
            }

            if(preg_match("#BYFORM:(.+)#", $classname,$re)){
                $style="style='height:46px;padding-top:10px;font-size:18px;'";
                $classname=$re[1];
            }
            $classtext=$classname;}else{$classtext="btn-success";}
        $ffname="file-uploaded";
        if($field_name<>null){$ffname=$field_name;}
        $btid=md5(time().$btname.$currentpage);
        $btname=$this->javascript_parse_text($btname);
        $Uploading=$this->javascript_parse_text("{uploading}");
        return "
		<span class=\"btn $classtext fileinput-button\" $style>
			<i class=\"fa fa-upload\"></i>
			<span id='$btid'>$btname</span>
			<!-- The file input field used as target for the file upload widget -->
			<input id=\"fileupload$btid\" type=\"file\" name=\"files\">
		</span>
		<script>
    $('#fileupload$btid').fileupload({
    	url: '/fw.upload.php',
        dataType: 'json',
        add: function (e, data) {
        	document.getElementById('$btid').innerHTML='$Uploading....';
            data.submit()
            
        },
    progressall: function (e, data) {
        var progress = parseInt(data.loaded / data.total * 100, 10);
        document.getElementById('$btid').innerHTML='$Uploading '+progress+'%';
 
    },        
        done: function (e, data) {
        	document.getElementById('$btid').innerHTML='$btname'
        	Loadjs('$currentpage?$ffname='+data.result.files[0].name+'$addedparams');
    	}
    });
    </script>
	";

    }



    public function button_openvpn_reconfigure($Onlyjs=false){
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/openvpn.enable.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/openvpn.enable.log";
        $ARRAY["CMD"]="openvpn.php?enable=yes";
        $ARRAY["TITLE"]="{reconfigure_service} {APP_OPENVPN}";
        $prgress=base64_encode(serialize($ARRAY));
        $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";
        if($Onlyjs){return $jsrestart;}
        return "<div class=\"btn-group\" data-toggle=\"buttons\">
		<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {reconfigure_service} </label>
		</div>
		<div id='reconfigure-service-div'></div>
		";
    }

    public function button_strongswan_reconfigure($Onlyjs=false){
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.build.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/strongswan.build.log";
        $ARRAY["CMD"]="strongswan.php?reconfigure=yes";
        $ARRAY["TITLE"]="{reconfigure_service} {APP_STRONGSWAN}";
        $prgress=base64_encode(serialize($ARRAY));
        $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";
        if($Onlyjs){return $jsrestart;}
        return "<div class=\"btn-group\" data-toggle=\"buttons\">
		<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {reconfigure_service} </label>
		</div>
		<div id='reconfigure-service-div'></div>
		";
    }

    public function progress_barr_static($currentvalue,$textbarr):string{
        $currentvalue=round($currentvalue,2);
        $class="progress-bar-default";
        if($currentvalue>80){$class="progress-bar-warning";}
        if($currentvalue>95){$class="progress-bar-danger";}
        $html[]="<div class=\"progress\" style='margin-bottom:0;background-color: #00000024 !important'>";
        $html[]="\t<div style=\"width: {$currentvalue}%\" aria-valuemax=\"100\" aria-valuemin=\"0\" aria-valuenow=\"$currentvalue\" role=\"progressbar\" class=\"progress-bar $class\">";
        $html[]="\t\t<span class=\"\">{$currentvalue}% $textbarr</span>";
        $html[]="\t</div>";
        $html[]="</div>";

        return $this->_ENGINE_parse_body(@implode("\n", $html));

    }

    public function icon_edit_field($js=null,$security=null):string{
        $faloup="";
        if(preg_match("#^([0-9]+)x:(.+)#",$js,$re)){
            $faloup=" fa-$re[1]x";
            $js=$re[2];
        }

        if(!$this->IsSecurity($security)){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fa-solid fa-pen-field$faloup'></i></button>";}
        if($js==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='far fa-tools'></i></button>";
        }


        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" ><i class='fa-solid fa-pen-field$faloup' style='color:#21b9bb'></i></button>";
    }

    public function icon_parameters($js=null,$security=null):string{

        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='far fa-tools'></i></button>";}
        if($js==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='far fa-tools'></i></button>";
        }


        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" ><i class='far fa-tools' style='color:#21b9bb'></i></button>";
    }


    public function icon_refresh($js=null){
        $ico="<i class='fa fa-repeat' style='color:#21b9bb'></i>";

        if($js==null){
            $js="blur();";
            $ico="<i class='fa fa-repeat' style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";
    }


    public function button_dropdown_table($title,$array=array(),$security=null,$btclass=null){
        if($this->label_class==null){$this->label_class="btn-warning";}
        if($this->label_class=="btn-primary"){$this->label_class="btn-warning";}else{$this->label_class="btn-primary";}
        if(!is_null($btclass)) {
            if (strlen($btclass) > 3) {
                $this->label_class = $btclass;
            }
        }
        $id=md5(serialize($array));
        $idbtn="Btn$id";
        $bntn_xs="";
        $CaretAddon="";
        $textClor="";
        if(isset($array["BUTTON"])){
            $PARAMS=$array["BUTTON"];
            unset($array["BUTTON"]);
            if(isset($PARAMS["type"])){
                if($PARAMS["type"]=="xs"){
                    $bntn_xs=" btn-xs";
                }
            }
            if(isset($PARAMS["NoCleanJs"])){
                $CaretAddon="&clean=yes";
            }
            if(isset($PARAMS["GLOBAL_CLASS"])){
                $this->label_class=$PARAMS["GLOBAL_CLASS"];
            }
            if(isset($PARAMS["ID"])){
                $idbtn=$PARAMS["ID"];
            }
        }

        $jsSTOP=null;
        if(!$this->IsSecurity($security)){
            $this->label_class="btn-default";
            $this_form_locked_privileges=$this->javascript_parse_text("{this_form_locked_privileges}");
            $jsSTOP="alert('$this_form_locked_privileges')";
        }

        $f[]="<div class=\"btn-group\" id='$id'>";
        $f[]="\t<button data-toggle=\"dropdown\" class=\"btn$bntn_xs $this->label_class dropdown-toggle\" id='$idbtn'
        style='text-transform: capitalize'  OnClick=\"Loadjs('fw.caret.php?id=$id$CaretAddon');\">$title <span class=\"caret\"></span></button>";
        $f[]="\t<ul class=\"dropdown-menu\" onmouseleave=\"Loadjs('fw.caret.php?id=$id&leave=yes$CaretAddon');\">";
        foreach ($array as $label=>$js) {
            $ico=null;
            if($label=="SPACER"){$f[] = "\t\t<li style='height: 2px;margin-bottom: -5px;margin-top:-9px'><hr></li>";continue;}
            if($jsSTOP<>null){$js=$jsSTOP;}
            if(strpos($js,"||")>0){
                $js2=explode("||",$js);
                $IconClass=$js2[0];
                $js=$js2[1].";Loadjs('fw.caret.php?id=$id$CaretAddon');";

                list($textClor,$ico)=$this->button_dropdown_icon($IconClass);

                if(isset($js2[2])){
                    $Perms=$js2[2];
                    if($Perms<>null){
                        if(!$this->IsSecurity($Perms)){
                            VERBOSE("Perms FALSE = $Perms",__LINE__);
                            $js="blur()";
                        }
                    }
                }

            }
            $f[] = "\t\t<li><a href=\"#\" OnClick=\"$js\" style='text-transform: capitalize;color:$textClor'>$ico$label</a></li>";
        }
        $f[]="\t</ul>";
        $f[]="</div>";

        return @implode("\n",$f);
    }
    private function button_dropdown_icon($Class):array{
        $IconType=null;
        $IconValue="";
        $text="currentColor";
        if(preg_match("#^(.+?):(.+?):(.+)#",$Class,$re)){
            $Class=$re[1];
            $IconType=$re[2];
            $IconValue=$re[3];
        }


        if(!is_null($IconType)){
            if($IconType=="color"){
                if($IconValue=="red"){
                    $text="#ed5565";
                    $Class="text-danger $Class";
                }
                if($IconValue=="yellow"){
                    $text="#f8ac59";
                    $Class="text-warning $Class";
                }
                if($IconValue=="green"){
                    $text="#1ab394";
                    $Class="text-primary $Class";
                }
                if($IconValue=="grey"){
                    $text="#888888";
                    $Class="text-muted $Class";
                }
                if($IconValue=="black"){
                    $text="#000000";
                    $Class="font-bold $Class";
                }
                if($IconValue=="blue"){
                    $text="#1c84c6";
                    $Class="text-success $Class";
                }


            }
        }

        return array($text,"<i class='$Class'></i>&nbsp;");
    }
    public function label_click($color=null,$label=null,$js=null){
        $array_bf["green"]="label-primary";
        $array_bf["yellow"]="label-warning";
        $array_bf["lazur"]="label-info";
        $array_bf["red"]="label-danger";
        $array_bf["blue"]="label-info";
        $array_bf["gray"]="label-default";
        $array_bf["black"]="black-bg";
        $array_bf["grey"]="label-default";
        if($js==null){$js="Blur();";}
        $over="onMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='default';\"";
        $click="OnClick=\"$js\"";
        $labelclass="label ".$array_bf[$color];
        $label=$this->_ENGINE_parse_body($label);
        return "<span class='$labelclass' $over $click>$label</span>";
    }

    public function button_label_table($title,$js,$class=null,$security=null,$disable=false){
        $fa="fa";
        if(preg_match("#^(.+?)\s+(.+)#", $class,$re)){$fa=$re[1];$class=$re[2];}

        if($this->label_class==null){$this->label_class="btn-info";}
        if($this->label_class=="btn-primary"){$this->label_class="btn-info";}else{$this->label_class="btn-primary";}

        if(!$this->IsSecurity($security)){
            $this->label_class="btn-default";
            $this_form_locked_privileges=$this->javascript_parse_text("{this_form_locked_privileges}");
            $js="alert('$this_form_locked_privileges')";
        }
        if($disable){
            $this->label_class="btn-default";
            $backup_disabled=$this->javascript_parse_text("{backup_disabled}");
            $js="alert('$backup_disabled')";
        }

        return "<label class=\"btn btn $this->label_class\" OnClick=\"$js;\">
			<i class='$fa $class'></i> $title </label>";

    }


    private function IsAsSecurityLabel($value=null){
        if($value==null){return false;}

        $data["AsDnsAdministrator"]=true;
        $data["AsSquidAdministrator"]=true;
        $data["ASDCHPAdmin"]=true;
        $data["AsSystemAdministrator"]=true;
        $data["AsDansGuardianAdministrator"]=true;
        $data["computer"]=true;
        $data["AsProxyMonitor"]=true;
        $data["AsVPNManager"]=true;
        $data["AsFirewallManager"]=true;
        $data["AllowAddUsers"]=true;
        $data["AsOrgAdmin"]=true;
        $data["AllowAddGroup"]=true;
        $data["AsPostfixAdministrator"]=true;
        $data["AsMailBoxAdministrator"]=true;
        $data["AsMessagingOrg"]=true;
        $data["AsHotSpotManager"]=true;
        if(!isset($data[$value])){
            VERBOSE("$value not found",__LINE__);
            return false;
        }
        return $data[$value];
    }

    public function IsSecurity($security=null){
        if($security==null){return true;}
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];

        if(isset($_SESSION["HARMPID"])){
            if($_SESSION["HARMPID"]>0){
                $users->AsCertifsManager=true;
            }
        }
        if($security=="FalseLicense"){return false;}
        if($security=="AsDatabaseAdministrator"){if(!$users->AsDatabaseAdministrator){return false;}}
        if($security=="AsCertifsManager"){if(!$users->AsCertifsManager){return false;}}
        if($security=="AsDnsAdministrator"){if(!$users->AsDnsAdministrator){return false;}}
        if($security=="AsSquidAdministrator"){if(!$users->AsSquidAdministrator){return false;}}
        if($security=="ASDCHPAdmin"){if(!$users->ASDCHPAdmin){return false;}}
        if($security=="AsSystemAdministrator"){if(!$users->AsSystemAdministrator){return false;}}
        if($security=="AsDansGuardianAdministrator"){if(!$users->AsDansGuardianAdministrator){return false;}}
        if($security=="AsDatabaseAdministrator"){if(!$users->AsDatabaseAdministrator){return false;}}
        if($security=="computer"){if(!$this->computers_rights()){return false;}}
        if($security=="AsProxyMonitor"){if($users->AsSquidAdministrator){return true;}}
        if($security=="AsProxyMonitor"){if($users->AsSystemAdministrator){return true;}}
        if($security=="AsProxyMonitor"){if($users->AsSquidAdministrator){return true;}}
        if($security=="AsProxyMonitor"){if(!$users->AsProxyMonitor){return false;}}
        if($security=="AllowAddUsers"){if(!$users->AllowAddUsers){return false;}}
        if($security=="AsOrgAdmin"){if(!$users->AsOrgAdmin){return false;}}
        if($security=="AsVPNManager"){if(!$users->AsVPNManager){return false;}}
        if($security=="AsFirewallManager"){if(!$users->AsFirewallManager){return false;}}
        if($security=="AllowAddGroup"){if(!$users->AllowAddGroup){return false;}}
        if($security=="AsPostfixAdministrator"){if(!$users->AsPostfixAdministrator){return false;}}
        if($security=="AsMailBoxAdministrator"){if(!$users->AsMailBoxAdministrator){return false;}}
        if($security=="AsMessagingOrg"){if(!$users->AsMessagingOrg){return false;}}
        if($security=="AsHotSpotManager"){if(!$users->AsHotSpotManager){return false;}}
        if($security=="AsDockerAdmin"){if(!$users->AsDockerAdmin){return false;}}
        if($security=="AsDockerReverse"){if(!$users->AsDockerReverse){return false;}}

        return true;
    }

    function is_regex($pattern):bool{
        $f[]="{";
        $f[]="[";
        $f[]="+";
        $f[]="\\";
        $f[]="?";
        $f[]="$";
        $f[]=".*";

        foreach ($f as $val){
            if(strpos(" $pattern", $val)>0){return true;}
        }
        return false;
    }

    public function icon_reply($js=null,$security=null){
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-reply'></i></button>";}
        if($js==null){$js="blur();";}
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='fas fa-reply'></i></button>";
    }

    public function icon_repeat($js=null,$security=null){
        $ico="fad fa-repeat-alt";
        $iconoff="fal fa-repeat-alt";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_add($js=null,$security=null){
        $icon="<i class=\"fas fa-plus-circle\" style='color:#CCCCCC'></i>";
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$icon</button>";
            }
        }


        $icon="<i class=\"fas fa-plus-circle\" style='color:#18a689'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$icon</button>";}
        if($js==null){
            $js="blur();";
            $icon="<i class=\"fas fa-plus-circle\" style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$icon</button>";
    }
    public function icon_cd($js=null,$security=null):string{

        $ico="fad fa-compact-disc";
        $iconoff="fal fa-compact-disc";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }
    public function icon_bash($js=null,$security=null):string{

        $ico="fad fa-rectangle-terminal";
        $iconoff="fal fa-rectangle-terminal";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_pause_error($js=null,$security=null):string{
        $ico="fas fa-pause-circle";
        $iconoff="fal fa-pause-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#ed5565");
    }
    public function icon_pause($js=null,$security=null):string{
        $ico="fas fa-pause-circle";
        $iconoff="fal fa-pause-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }
    public function icon_pause_warning($js=null,$security=null):string{
        $ico="fas fa-pause-circle";
        $iconoff="fal fa-pause-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#f0ad4e");
    }
    public function icon_run($js=null,$security=null):string{
        $ico="fas fa-arrow-to-right";
        $iconoff="fal fa-arrow-to-right";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(!is_null($security)) {
            if (strlen($security) > 2) {
                if (!$this->IsSecurity($security)) {
                    return $this->icon_gen($ico, $iconoff, $js, false);
                }
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_start($js=null,$security=null):string{
        $ico="fas fa-play-circle";
        $iconoff="fal fa-play-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#1ab394");
    }
    public function icon_start_error($js=null,$security=null):string{
        $ico="fas fa-play-circle";
        $iconoff="fal fa-play-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#ed5565");
    }

    public function icon_run_warn($js=null,$security=null):string{
        $ico="fas fa-play-circle";
        $iconoff="fal fa-play-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#f0ad4e");
    }
    public function icon_history($js=null,$security=null):string{
        $ico="fad fa-clock-rotate-left";
        $iconoff="fal fa-clock-rotate-left";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_stop($js=null,$security=null){

        $ico="fas fa-stop";
        $iconoff="fal fa-stop";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}
        if(!is_null($security)) {
            if (strlen($security) > 2) {
                if (!$this->IsSecurity($security)) {
                    return $this->icon_gen($ico, $iconoff, $js, false);
                }
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_restore($js=null,$security=null){
        $pic    = "fad fa-file-import";
        $color  = "18a689";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}

        $ico="<i class='$pic' style='color:#{$color}'></i>";
        if($js==null){
            $js="blur();";
            $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";


    }
    public function icon_erase($js=null,$security=null){
        $ico = "<i class='fa-solid fa-eraser'></i>";

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";
            }
        }
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}

        $ico="<i class='fa-solid fa-eraser' style='color:#ec4758'></i>";
        if($js==null){
            $js="blur();";
            $ico="<i class='fa-solid fa-eraser' style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";

    }

    public function icon_delete($js=null,$security=null){
        $icon_class=ico_trash;
        $ico="<i class='$icon_class' style='color:#CCCCCC'></i>";
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";
            }
        }



        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}

        $ico="<i class='$icon_class' style='color:#ec4758'></i>";
        if($js==null){
            $js="blur();";
            $ico="<i class='$icon_class' style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";
    }

    public function icon_support($url=null):string{
        $ico=ico_support;
        if($url==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";
        }

        $js="s_PopUpFull('$url',1024,768,'Support');";
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='$ico'></i></button>";
    }
    public function icon_wiki($url=null):string{
        $ico="fa-solid fa-book-open";
        if($url==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";
        }

        $js="s_PopUpFull('$url',1024,768,'Support');";
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='$ico'></i></button>";
    }
    public function icon_interface($js=null,$security=null):string{
        $ico=ico_interface;
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";}
        if($js==null){$js="blur();";}
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='$ico'></i></button>";
    }

    public function icon_loupe($enabled=false,$js=null,$id=null,$security=null):string{

        if(!$enabled){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" id='$id'><i class='fas fa-search'></i></button>";}
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-search'></i></button>";}
        if($js==null){$js="blur();";}
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='fas fa-search'></i></button>";
    }



    public function icon_recycle($js=null,$security=null){
        $ico="fa fa-recycle";
        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$ico,$js,false);}
        if($js==null){return $this->icon_gen($ico,$ico,$js,false);}
        return $this->icon_gen($ico,$ico,$js,true);
    }
    public function icon_recycle_green($js=null,$security=null){
        $ico="fa fa-recycle";
        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$ico,$js,false);}
        if($js==null){return $this->icon_gen($ico,$ico,$js,false);}
        return $this->icon_gen($ico,$ico,$js,true);
    }


    public function icon_shield_yellow($js=null,$security=null,$ForceDisabled=false):string{
        $ico="fad fa-shield-alt";
        $iconff="fal fa-shield";


        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js,true,"f7a54a");
    }
    public function icon_shield($js=null,$security=null,$ForceDisabled=false):string{
        $ico="fad fa-shield-alt";
        $iconff="fal fa-shield";


        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js);
    }

    public function icon_optimize($js=null,$security=null,$ForceDisabled=false):string{
        $ico="fad fa-gauge-circle-bolt";
        $iconff="fal fa-gauge-circle-bolt";

        if($js==null){
            $ForceDisabled=true;
        }
        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js);
    }
    public function icon_optimize_grey($js=null,$security=null,$ForceDisabled=false):string{
        $ico="fad fa-gauge-circle-bolt";
        $iconff="fal fa-gauge-circle-bolt";

        if($js==null){
            $ForceDisabled=true;
        }
        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js,true,"d1dade");
    }

    public function icon_shield_grey($js=null,$security=null,$ForceDisabled=false){
        $ico="fad fa-shield-alt";
        $iconff="fal fa-shield";


        if(!$this->IsSecurity($security)){
            return $this->icon_gen($ico,$iconff,$js,false);
        }

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js,false);
    }

    public function icon_bug($js=null,$security=null,$ForceDisabled=false){
        $ico="fad  fa-bug";
        $iconff="fal fa-bug";


        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js);
    }


    private function icon_gen($ico=null,$icoff=null,$js=null,$enabled=true,$color=null):string{
        if($color==null){
            $color="18a689";
        }
        $color=str_replace("#","",$color);
        if($icoff==null){$icoff=$ico;}
        if(!$enabled){
            $js="blur();";
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$icoff'></i>
                    </button>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' 
            OnClick=\"$js\" >
                <i class='$ico' style='color:#$color'></i>
            </button>";

    }


    public function icon_unlink($js=null,$security=null){
        $ico="<i class='fa fa-unlink' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}

        $ico="<i class='fa fa-unlink' style='color:#ec4758'></i>";

        if($js==null){
            $js="blur();";
            $ico="<i class='fa fa-unlink' style='color:#CCCCCC'></i>";


        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";

    }


    public function icon_attach($js=null,$security=null){
        $pic="fas fa-paperclip";
        return $this->__icon_defaults($pic,$js,$security);
    }
    public function icon_archive($js=null,$security=null){
        $pic="fas fa-file-archive";
        return $this->__icon_defaults($pic,$js,$security);
    }
    public function icon_construction($value,$js,$security){
        $pic="fas fa-construction";
        $color="#c2ccd1";
        if($value==1){
            $color="#f7a54a";
        }
        return $this->__icon_defaults("color:$color:$pic",$js,$security);
    }

    public function icon_nic($js=null,$security=null){
        $pic="fad fa-ethernet";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $class="btn btn-white btn-bitbucket";
        $clasw="btn btn-white btn-bitbucket";

        if(!$this->IsSecurity($security)){
            return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";
        }
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='$clasw' $jsav >$ico</button>";

    }




    public function icon_user_lock($js=null,$security=null){
        $pic="fad fa-user-lock";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $class="btn btn-white btn-bitbucket";
        $clasw="btn btn-white btn-bitbucket";

        if(!$this->IsSecurity($security)){return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){ return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>"; }
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='$clasw' $jsav >$ico</button>";

    }
    public function icon_user($js=null,$security=null){
        $pic="fad fa-user";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $class="btn btn-white btn-bitbucket";
        $clasw="btn btn-white btn-bitbucket";

        if(!$this->IsSecurity($security)){
            return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";
        }
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='$clasw' $jsav >$ico</button>";

    }
    public function icon_group($js=null,$security=null){
        $pic="fad fa-users";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $class="btn btn-white btn-bitbucket";
        $clasw="btn btn-white btn-bitbucket";

        if(!$this->IsSecurity($security)){
            return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";
        }
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='$clasw' $jsav >$ico</button>";

    }

    public function icon_download_artica($local_path){
        $pic="fad fa-download";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $local_path=str_replace("/usr/share/artica-postfix/","",$local_path);
        if(!is_file("/usr/share/artica-postfix/$local_path")){
            VERBOSE("[/usr/share/artica-postfix/$local_path] no such file",__LINE__);
            return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" >$ico</button>";
        }

        $jsav="OnClick=\"window.location='fw.download.php?fname=".base64_encode($local_path)."'\"";
        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='btn btn-white btn-bitbucket' $jsav >$ico</button>";
    }

    public function icon_download($js=null,$security=null){

        $pic="fad fa-download";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";}
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='btn btn-white btn-bitbucket' $jsav >$ico</button>";

    }
    public function icon_upload($js=null,$security=null){

        $pic="fa-solid fa-upload";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";}
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='btn btn-white btn-bitbucket' $jsav >$ico</button>";

    }



//<i class="fas fa-file-export"></i>
    public function icon_export($js=null,$security=null){
        $ico="<i class='fas fa-file-export' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";}
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }
        $ico="<i class='fas fa-file-export' style='color:#18a689'></i>";
        return "<button type='button' class='btn btn-white btn-bitbucket' $jsav >$ico</button>";

    }

    public function icon_pie($js=null,$security=null){



        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-chart-pie'></i></button>";}
        if($js==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-chart-pie'></i></button>";
        }
        return "<button type='button' class='btn btn-info btn-bitbucket' OnClick=\"$js\" ><i class='fas fa-chart-pie'></i></button>";


    }

    public function icon_stats($js = null, $security = null, $disable = false):string{
        if (!$this->IsSecurity($security)) {
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-chart-bar'></i></button>";
        }
        if ($js == null) {
            $js = "blur();";
        }
        if ($disable == true) {
            $js = "blur();";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" ><i class='fas fa-chart-bar' style='color:#1ab394'></i></button>";


    }
    public function icon_chart($js = null, $security = null, $disable = false):string{
        $ico="fas fa-chart-pie";

        if (!$this->IsSecurity($security)) {
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";
        }
        if ($js == null) {
            $js = "blur();";
        }
        if ($disable) {
            $js = "blur();";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" ><i class='$ico' style='color:#1ab394'></i></button>";


    }
    //end keepalived

    public function js_prompt($title,$subtitle,$icon,$js,$fieldname=null,$jsafter=null,$defaultvalue=null,$KeyName=null):bool{
        header("content-type: application/x-javascript");

        $field_type = "text";
        $js         = $this->_ENGINE_parse_body($js);
        $title      = $this->_ENGINE_parse_body($title);
        $subtitle   = $this->_ENGINE_parse_body($subtitle);
        $id         = md5(time());
        $cancel     = $this->_ENGINE_parse_body("{cancel}");
        $fill_form  = $this->_ENGINE_parse_body("{fill_form}");
        $save       = $this->_ENGINE_parse_body("{save}");
        $onKeyPress = "onkeypress=\"SaveCheck{$id}(event);\"";
        if(!is_null($fieldname)) {
            if (preg_match("#password#i", $fieldname)) {
                $field_type = "password";
            }
        }

        $title      = str_replace("'","`",$title);
        $subtitle   = str_replace("'","`",$subtitle);
        $cancel     = str_replace("'","`",$cancel);
        $subtitle   = str_replace("'","\'",$subtitle);
        $subtitle   = str_replace("\n","<br>",$subtitle);

        $messages[] = "<div style=\"padding: 15px 15px;text-align: center;border-bottom: 1px solid #e5e5e5;\">";
        $messages[] = "<i class=\"$icon\" style=\"font-size: 84px;color:#e2e3e3;\"></i>";
        $messages[] = "<h3 style=\"text-align: center;\">$title</h3>";
        $messages[] = "<div id=\"modal-inside-alert\"></div>";
        $messages[] = "<div style=\"margin-top:-10px;margin-bottom:10px\"><small class=\"font-bold\">$subtitle</small></div>";
        $messages[] = "</div>";

        if($fieldname==null){
            $messages_final=base64_encode(@implode("",$messages));
            $jsa[]="$.confirm({";
            $jsa[]="theme: 'supervan',";
            $jsa[]="containerFluid: true,";
            $jsa[]="title: \"$title\",";
            $jsa[]="content: base64_decode('$messages_final'),";
            $jsa[]="icon: '$icon',";
            $jsa[]="buttons: {";
            $jsa[]="confirm: {
                text: 'OK',
            action: function () { $jsafter; }
        },
            cancel: {
                text: '$cancel',
            
        },},";

            $jsa[]="});";
            echo @implode("\n",$jsa);
            return true;

        }

        if($fieldname<>null) {
            $messages[] ="<div class=\"form-group\" style=\"margin-top:8px\">";
            $messages[] = "    <input type=\"$field_type\" id=\"form$id\" $onKeyPress class=\"form-control\" style=\"z-index:999999;\" value=\"$defaultvalue\">";
            $messages[] = " </div>";
        }


        $messages_final=base64_encode(@implode("",$messages));

        $jsa[]="var x_Save$id= function (obj) {";
        $jsa[]="  var results=obj.responseText;";
        $jsa[]="  if(results.length>3){";
        $jsa[]="        if(document.getElementById('modal-inside-alert')){";
        $jsa[]="        document.getElementById('modal-inside-alert').innerHTML='<div class=\"alert alert-danger\"><strong>'+results+'</strong></div>';";
        $jsa[]="     }else{";
        $jsa[]="            alert(results);";
        $jsa[]="     }";
        $jsa[]="     return;";
        $jsa[]="   }";
        $jsa[]="    Boot$id.close();";
        $jsa[]="    $jsafter";
        $jsa[]="}";
        $jsa[]="";
        $jsa[]="";
        $jsa[]="function SaveCheck{$id}(e){";
        $jsa[]="    if(checkEnter(e)){ Save{$id}();}";
        $jsa[]="}";
        $jsa[]="function Save{$id}(){";
        $jsa[]="\tvar XHR = new XHRConnection();";
        $jsa[]="\tvar one='';";
        $jsa[]="\tif(document.getElementById('modal-inside-alert')){document.getElementById('modal-inside-alert').innerHTML='';}";
        $jsa[]="\tif(!document.getElementById('form$id')){alert('form$id !!');return;}";
        $jsa[]="\tone=document.getElementById('form$id').value;";
        $jsa[]="\tif(one.length==0){";
        $jsa[]="\tdocument.getElementById('modal-inside-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$fill_form</strong></div>';";
        $jsa[]="\treturn;}";

        if($KeyName<>null){
            $jsa[]="\tXHR.appendData('KeyID','$KeyName');";

        }

        $jsa[]="\tXHR.appendData('$fieldname',one);";
        $jsa[]="\tXHR.sendAndLoad('$js', 'POST',x_Save$id);";
        $jsa[]="}";
        $jsa[]="";
        $jsa[]="";
        $jsa[]="var Boot$id=BootstrapDialog.show({";
        $jsa[]="id: '$id',";
        $jsa[]="size: BootstrapDialog.SIZE_SMALL,";
        $jsa[]="title:'$title',";
        $jsa[]="message: base64_decode('$messages_final'),";
        $jsa[]="closable: false,";
        $jsa[]="closeByBackdrop: false,";
        $jsa[]="closeByKeyboard: false,";
        $jsa[]="buttons: [";
        $jsa[]="{
                label: '$cancel',
                icon: 'far fa-window-close',
                action: function(dialogRef){ dialogRef.close(); }
            },";

        if($fieldname<>null) {
            $jsa[] = "{
                    label: '$save',
                    icon: 'fas fa-save',
                    cssClass: 'btn-primary',
                    action: function(dialogRef){ Save{$id}(); }
                },";
        }

        $jsa[]="],";
        $jsa[]="onshown: function(dialogRef){ ";
        $jsa[]="    $('#{$id}_title').css({ 'font-size' : '13px' });";
        $jsa[]="    $('#{$id}_title').css({ 'padding' : '5px' });";
        $jsa[]="},";
        $jsa[]="});";

        echo @implode("\n",$jsa);
        return true;
    }
    public function js_show_pic($title,$subtitle,$image_path){
        header("content-type: application/x-javascript");
        $image_path=urlencode(base64_encode($this->_ENGINE_parse_body($image_path)));
        $title=urlencode(base64_encode($this->_ENGINE_parse_body($title)));
        $subtitle=urlencode(base64_encode($this->_ENGINE_parse_body($subtitle)));
        echo "LoadAjax('artica-modal-dialog','fw.modal-pic.php?title=$title&subtitle=$subtitle&image=$image_path')";
    }
    public function js_show_video($title,$subtitle,$videoid){
        header("content-type: application/x-javascript");
        $title=urlencode(base64_encode($this->_ENGINE_parse_body($title)));
        $subtitle=urlencode(base64_encode($this->_ENGINE_parse_body($subtitle)));
        return "LoadAjax('artica-modal-dialog','fw.modal-video.php?title=$title&subtitle=$subtitle&video=$videoid')";
    }



    public function icon_excel($js=null){
        if($js==null){$js="blur();";}
        return "<div class='center'><button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\"><i class='fas fa-file-spreadsheet'></i></button></div>";

    }

    public function format_search_protocol($search,$standard_date=false,$mysql_date=false,$green_date=false,$nodate=false):array{
        $c              = 0;
        $date           = null;
        $PROTO          = null;
        $max            = 0;

        if(!is_null($search)) {
            $search=trim(strtolower($search));
            VERBOSE("SEARCH [$search]",__LINE__);
            $search = str_replace(['+', '²', '°', '\\', '%', '$', ';', '#', '&', '`', '"', '>', '<', '”', "'"], '', $search);

            if(preg_match("#this hour#", $search)){
                $search=str_replace("this hour", "", $search);
                $time="\s+".date("H");
            }
            if(preg_match("#yesterday#", $search)){
                $search=str_replace("yesterday", "", $search);
                strtotime('-1 day', "node()");
                $time="\s+".date("H");
            }
            if(preg_match("#with categories#", $search)){
                $search=str_replace("with categories", "", $search);
                $MAIN["CATEGORIES"]=true;
            }
            if(preg_match("#with destinations#", $search)){
                $search=str_replace("with destinations", "", $search);
                $MAIN["DESTINATIONS"]=true;
            }
            if(preg_match("#last hour#", $search)){
                $search=str_replace("last hour", "", $search);
                $time="\s+".date("H",strtotime( '-1 hours' ));
            }
            if(preg_match("#today#", $search)){
                $date=date("M")."\s+".intval(date("d")).$time;
                if($standard_date){$date=date("m/d");}
                if($mysql_date){$date=date("m-d");}
                if($green_date){$date=date("d/M");}
                $search=str_replace("today", "", $search);
            }
            if(preg_match("#everything#", $search)){
                $search=str_replace("everything", "*", $search);
            }
            if(preg_match("#last\s+([0-9]+)#", $search,$re)){
                $search=str_replace("last {$re[1]}", "", $search);
                $max=$re[1];
            }
            if(preg_match("#yesterday#", $search)){
                $date=date("M", strtotime( '-1 days' ) )."\s+".intval(date("d",strtotime( '-1 days' ))).$time;
                if($standard_date){$date=date("m/d",strtotime( '-1 days' ));}
                if($mysql_date){$date=date("m-d",strtotime( '-1 days' ));}
                $search=str_replace("yesterday", "", $search);
            }
            if(preg_match("#protocol\s+([a-z\*]+)#",$search,$re)){
                $PROTO=strtoupper($re[1]);
                $c++;
            }
            if(preg_match("#(^|\s)(max|limit)\s+([0-9]+)#",$search,$re)){
                $search=str_replace("$re[2] $re[3]", "", $search);
                $max=strtoupper($re[3]);
            }
            if(preg_match("#([0-9]+)\s+events#",$search,$re)){
                VERBOSE("Found filtered events [$re[1]]",__LINE__);
                $search=str_replace("$re[1] events", "", $search);
                $max=intval($re[1]);
            }
            if(preg_match("#^max=([0-9]+)#i",$search,$re)){
                VERBOSE("Found max events [$re[1]]",__LINE__);
                $search=str_replace("max=$re[1]", "", $search);
                $max=$re[1];
            }
            if(preg_match("#(max)(\s+|=)([0-9]+)#i",$search,$re)){
                VERBOSE("Found max events [$re[1]]",__LINE__);
                $search=str_replace("$re[1]$re[2]$re[3]", "", $search);
                $max=$re[2];
            }
            if(preg_match("#src\s+([0-9\.\*]+)#",$search,$re)){
                $MAIN["SRC"]=$re[1];
                $c++;
                $search=str_replace("src {$re[1]}", "", $search);
            }
            if(preg_match("#dst\s+([0-9\.\*]+)#",$search,$re)){
                $MAIN["DST"]=$re[1];
                $c++;
                $search=str_replace("dst {$re[1]}", "", $search);
            }
            if(preg_match("#srcport\s+([0-9\*]+)#",$search,$re)){
                $MAIN["SRCPORT"]=$re[1];
                $search=str_replace("src {$re[1]}", "", $search);
            }
            if(preg_match("#dstport\s+([0-9\*]+)#",$search,$re)){
                $MAIN["DSTPORT"]=$re[1];
                $c++;
                $search=str_replace("dstport {$re[1]}", "", $search);
            }
            if(preg_match("#mac\s+([0-9\:\-\*]+)#",$search,$re)){
                $MAIN["MAC"]=$re[1];
                $MAIN["MAC"]=str_replace("-", ":", $MAIN["MAC"]);
                $c++;
                $search=str_replace("mac {$re[1]}", "", $search);
            }
            if(preg_match("#pid(=|\s+)([0-9]+)#",$search,$re)){
                $MAIN["PID"]=$re[2];
                $c++;
                $search=str_replace("pid{$re[1]}{$re[2]}", "", $search);
            }
        }
        if($max==0){$max=50;}
        if(!$nodate) {
            if ($date == null) {
                $date = date("M") . "\s+";
                if ($standard_date) {
                    $date = date("m/d") . "\s+";
                }
                if ($mysql_date) {
                    $date = date("m-d") . "\s+";
                }
            }
        }
        if(!is_null($search)) {
            if (preg_match("#all events#", $search)) {
                $MAIN["MAX"] = $max;
                $MAIN["DATE"] = null;
                $MAIN["TERM"] = null;
                $MAIN["C"] = $c;
                return $MAIN;
            }
            $search=trim($search);
        }

        $MAIN["MAX"]=$max;
        $MAIN["DATE"]=$date;
        if($PROTO<>null){
            $MAIN["PROTO"]=$PROTO;
        }


        $MAIN["TERM"]=$search;
        $MAIN["C"]=$c;
        return $MAIN;
    }


    public function icon_link($enabled=false,$js=null,$id=null){

        $square_class="text-navy";
        $square="fa-external-link-square";
        $text_class=null;
        if(!$enabled){
            $text_class=" text-muted";
            $square_class=null;

        }

        if($id==null){$id=md5(microtime());}
        if($js==null){return "<div class='center'><span class='$text_class'><i class='fa $square ' id='$id'></i></span></div>";}
        return "<span class='$text_class'><a href=\"#\" OnClick=\"$js\"><i class='fa $square' id='$id'></i></a></span>";


    }



    public function icon_check($enabled=false,$js=null,$id=null,$security=null):string{
        $checked="";
        $opts=null;
        $disabled=0;
        if(is_bool($enabled)){if($enabled){$enabled=1;}else{$enabled=0;} }

        if($this->IsAsSecurityLabel($id)){$id=null;}
        if($id==null){$id=md5(microtime(true).date("Ymdhsisu")."$js");}
        $id=str_replace("-", "_", $id);

        if($security<>null){
            if(!$this->IsSecurity($security)){
                $js=null;
                $disabled=1;
                $color="#CCCCCC";
            }
        }

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $js=null;
                $color="#CCCCCC";
                $disabled=1;
            }
        }
        if($js==null){$color="#CCCCCC";$Over=null;$disabled=1;}

        if($disabled==1){

            if($enabled==1){
                $ico="<i class=\"fas fa-check\" style=\"color:$color\" id=\"ico-$id\"></i>";
                return  "<button type=\"button\" class=\"btn btn-white btn-bitbucket\" id=\"btn-$id\" >$ico</button>";
            }

            if($enabled==0){
                $ico="<i class=\"fas fa-check\" style=\"color:white\" id=\"ico-$id\"></i>";
                return  "<button type=\"button\" class=\"btn btn-white btn-bitbucket\" id=\"btn-$id\" >$ico</button>";
            }
        }


        if($disabled==0) {

            $color = "#EEEEEE";
            if ($enabled == 1) {
                $color = "#18a689";
            }

        }else{
            if($disabled==0) {
                $color = "#EEEEEE";
            }
        }


        $Over="OnMouseOver=\"BtSwitchHover$id(this)\" OnMouseOut=\"BtSwitchOut$id(this)\"";


        $ico="<i class=\"fas fa-check\" style=\"color:$color\" id=\"ico-$id\"></i>";
        $f[]="
        <input type=\"hidden\" id=\"hide-$id\" value=\"$enabled\">
        <button type=\"button\" class=\"btn btn-white btn-bitbucket\" id=\"btn-$id\" OnClick=\"BtSwitch$id(this)\" $Over onclick=\"BtSwitch$id(this)\">$ico</button>";




        $this->ICON_SCRIPTS[]="
    function BtSwitch$id(e){
        var disabled=$disabled;
        if(disabled==1){return;}
        var value=document.getElementById(\"hide-$id\").value;

        if(value==1){
            document.getElementById(\"btn-$id\").innerHTML=\"<i class='fas fa-check' style='color:#EEEEEE' id='ico-$id'></i>\";
            document.getElementById(\"hide-$id\").value=0;
            $js;
            return;
        }
        
        document.getElementById(\"btn-$id\").innerHTML=\"<i class='fas fa-check' style='color:#18a689' id='ico-$id'></i>\";
        document.getElementById(\"hide-$id\").value=1;
        
        $js;
    }
            
    function BtSwitchHover$id(e){
        e.style.cursor='pointer';
        $(e).addClass('hover');
    }
    function BtSwitchOut$id(e){
        e.style.cursor='default';
        $(e).removeClass('hover');
    }
    ";

        return @implode("\n",$f);
    }






    public function query_field($search,$LOWER=true){
        $search=trim($search);
        $xs=array();


        $COM=null;
        if(preg_match("#^IPADDRLIKE(.+)#", $search,$re)){
            $COM='>>=';
            if(isset($_GET["QueryIsSqlite"])){$COM="LIKE";}
            $search="{$re[1]}";
        }

        if(preg_match("#^~(.+)#", $search,$re)){
            $COM='~';
            if(isset($_GET["QueryIsSqlite"])){$COM="LIKE";}
            $search=$re[1];
        }
        if(preg_match("#^>=(.+)#", $search,$re)){
            $COM='>=';
            $search=$re[1];
        }
        if(preg_match("#^>(.+)#", $search,$re)){
            $COM='>';
            $search=$re[1];
        }

        if(preg_match("#^=<(.+)#", $search,$re)){
            $COM='=<';
            $search=$re[1];
        }

        if(preg_match("#^<(.+)#", $search,$re)){
            $COM='<';
            $search=$re[1];
        }
        if(preg_match("#^LIKE(.+)#i", $search,$re)){

            $COM='~';
            if( (isset($_GET["QueryIsSqlite"])) OR (isset($_GET["QueryIsMySQL"]))  ){$COM="LIKE";}
            $search=$re[1];

        }
        if(preg_match("#^=(.+)#", $search,$re)){
            $COM='=';
            $search=$re[1];
        }
        if($COM=="="){
            if(strpos(" $search", '*')){$COM='LIKE';}
        }

        if(strpos(" $search", "*")>0){
            if( (isset($_GET["QueryIsSqlite"])) OR (isset($_GET["QueryIsMySQL"]))  ){
                $COM="LIKE";
                $search=str_replace("*", "%", $search);
            }else{
                $COM='~';$LOWER=false;
                $search=str_replace(".", "\.", $search);
                $search=str_replace("*", ".*?", $search);
            }
        }
        if(preg_match('#"(.+?)"#', $search,$re)){$search=$re[1];}





        if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>LINE.".__LINE__." COM:$COM For =[$search]</span><br>\n";}

        if($COM==">>="){return " $COM '$search'";}
        if($COM==null){$COM="=";}
        if(is_numeric(trim($search))){
            return " $COM $search";
        }

        if($LOWER){
            return " $COM LOWER('$search')";
        }
        return " $COM '$search'";
    }


    public function query_postgres_ipaddrmac($search=null,$ipaddr_field="ipaddr",$mac_field="mac",$hostname_field="hostname"){

        if(preg_match("#^[0-9:]+$#",$search)){
            return "WHERE $mac_field = '$search'";
        }
        if(preg_match("#^[0-9\.]+$#",$search)){
            return "WHERE $ipaddr_field = '$search'";
        }

        if(preg_match("#^[0-9\.]+\/[0-9]+#",$search)){
            return "WHERE $ipaddr_field << inet '$search'";
        }

        if(preg_match("#^[a-z0-9\-\_\.\*]+#",$search)){
            $search=strtolower($search);
            if(strpos("   $search","*")>0){
                $search=str_replace("*","%",$search);

                return "WHERE LOWER($hostname_field) LIKE '$search'";
            }
            return "WHERE LOWER($hostname_field) = '$search'";
        }
        return "";
    }

    public function query_pattern($search=null,$aliases=array(),$forceThis=null){
        $search=$this->CLEAN_BAD_XSS_SQL($search);
        $xdate=null;
        if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>ANALYZE [$search]</span><br>\n";}
        $MaxORG=200;
        if(preg_match("#([0-9]+)(\s+|=)events#i",$search,$re)){
            $search=str_ireplace("{$re[1]}{$re[2]}events", "", $search);
            $MaxORG=intval($re[1]);
        }

        if(preg_match("#limit(\s+|=)([0-9]+)#i",$search,$re)){
            $search=str_ireplace("limit{$re[1]}{$re[2]}", "", $search);
            $MaxORG=intval($re[2]);
        }
        if(strpos(" $search","today")>0){
            VERBOSE("FOUND TODAY!!",__LINE__);
            if(isset($aliases["date"])) {
                $field_date = $aliases["date"];
                $xdate = "date_trunc('day', NOW() ) = date_trunc('day', $field_date)";
            }
            $search=str_replace("today ","",$search);
            VERBOSE("SEARCH: $search",__LINE__);
        }
        if(strpos(" $search","yesterday")>0){
            VERBOSE("FOUND YESTERDAY!!",__LINE__);
            if(isset($aliases["date"])) {
                $field_date = $aliases["date"];
                $xdate = "date_trunc('day', NOW() - INTERVAL '1 day') = date_trunc('day', $field_date)";
            }
            $search=str_replace("yesterday ","",$search);
            VERBOSE("SEARCH: $search",__LINE__);
        }
        if(strpos(" $search","yesteray")>0){
            VERBOSE("FOUND YESTERDAY!!",__LINE__);
            if(isset($aliases["date"])) {
                $field_date = $aliases["date"];
                $xdate = "date_trunc('day', NOW() - INTERVAL '1 day') = date_trunc('day', $field_date)";
            }
            $search=str_replace("yesteray ","",$search);
            VERBOSE("SEARCH: $search",__LINE__);
        }



        if(preg_match("#max(\s+|=)([0-9]+)#i",$search,$re)){
            VERBOSE("OK FOUND #max(\s+|=)([0-9]+)#i in $search",__LINE__);
            VERBOSE("{$re[0]} =='max{$re[1]}{$re[2]}",__LINE__);
            $search=str_ireplace("max{$re[1]}{$re[2]}", "", $search);
            $MaxORG=intval($re[2]);
            VERBOSE("MAX:$MaxORG search=[$search]",__LINE__);
        }

        if(stripos($search, " OR ")>0){
            $Zsearch=preg_split("/\s+OR\s+/i",$search);
            foreach ($Zsearch as $line){

                $query_pattern=$this->query_pattern($line,$aliases,$forceThis);
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>Q [{$query_pattern["Q"]}]</span><br>\n";}
                $query_pattern["Q"]=str_ireplace("WHERE ", "", $query_pattern["Q"]);

                $pp[]=$query_pattern["Q"];
                if(isset($query_pattern["MAX"])){
                    $MAX=$query_pattern["MAX"];
                }


            }
            VERBOSE("MaxORG=$MaxORG",__LINE__);
            if($MaxORG>0){$MAX=$MaxORG;}
            if($MAX==0){$MAX=150;}
            return array("MAX"=>$MAX,"Q"=>"WHERE $xdate ".@implode(" OR ", $pp),"S"=>$search);

        }

        if(is_array($aliases)) {
            if (count($aliases) == 0) {
                $aliases["src_ip"] = "src_ip";
                $aliases["ipaddr"] = "src_ip";
                $aliases["mac"] = "mac";
                $aliases["website"] = "website";
                $aliases["city"] = "city";
                $aliases["ville"] = "city";
                $aliases["country"] = "country";
                $aliases["pays"] = "country";
                $aliases["hostname"] = "hostname";
            }
        }

        if(preg_match("#(^|\s)src\s+(.+?)([\s+|$])#",$search,$re)){
            VERBOSE("FOUND SRC!!",__LINE__);
            if(isset($aliases["src"])){
                $value=$re[2];
                VERBOSE("FOUND SRC!! VALUE:$value",__LINE__);
                if($value=="*"){
                    $search=str_replace("{$re[1]}src $re[2]","",$search);
                }else {
                    $search = str_replace("{$re[1]}src $re[2]", "{$re[1]}{$aliases["src"]}=$value", $search);
                }
                VERBOSE("$search",__LINE__);
            }
        }
        if(preg_match("#(^|\s)dst\s+(.+?)([\s+|$])#",$search,$re)){
            VERBOSE("FOUND DST!!",__LINE__);
            if(isset($aliases["dst"])){
                $value=$re[2];
                VERBOSE("FOUND DST!! VALUE:$value",__LINE__);
                if($value=="*"){
                    $search=str_replace("{$re[1]}dst $re[2]","",$search);
                }else {
                    $search = str_replace("{$re[1]}dst $re[2]", "{$re[1]}{$aliases["dst"]}=$value", $search);
                }
                VERBOSE("$search",__LINE__);
            }
        }
        if(preg_match("#(^|\s)dstport\s+#",$search,$re)){
            if(isset($aliases["dstport"])){
                $search=str_replace("{$re[1]}dstport","{$re[1]}{$aliases["dstport"]}",$search);
            }
        }
        if(preg_match("#src_ip\s+\*#",$search,$re)){
            $search=str_replace("src_ip *","",$search);
        }
        if(preg_match("#all events#i", $search)){
            return array("MAX"=>200,"Q"=>"*","L"=>__LINE__,"S"=>"*");
        }


        $IPClass=new IP();
        $max=0;
        $query=array();

        VERBOSE("COUNT of query=[".count($query)."] MaxORG=$MaxORG",__LINE__);
        if($IPClass->IsvalidMAC($search)){
            VERBOSE("$search is a valid MAC address",__LINE__);
            if(isset($_GET["types"])){
                $ztypes=unserialize(base64_decode($_GET["types"]));
                if(isset($ztypes["mac"])){
                    $search="mac=$search";
                }
            }


        }

        if($IPClass->isValid($search)){
            VERBOSE("$search is a valid IP address",__LINE__);
            if(isset($_GET["types"])){
                $ztypes = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["types"]);
                if(isset($ztypes["ipaddr"])){
                    $search="ipaddr=$search";
                }
            }

        }

        if($forceThis<>null){
            $query[]="$forceThis";
        }

        if($search==null){
            if($MaxORG>0){$MAX=$MaxORG;}
            return array("MAX"=>$MAX,"Q"=>null,"L"=>__LINE__,"S"=>$search);
        }
        $ztypes=array();
        if(isset($_GET["types"])) {
            $ztypes = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["types"]);
        }
        if(isset($ztypes["zdate"])) {
            VERBOSE("DATE ==> {$ztypes["zdate"]}",__LINE__);
            if (preg_match("#today#", $search)) {
                $query[] = "zdate > '" . date("Y-m-d 00:00:00") . "'";
                $search = str_replace("today", "", $search);

            }
            if (preg_match("#yesterday#", $search)) {
                $query[] = "zdate > '" . date("Y-m-d 00:00:00", strtotime('-1 days')) . "' AND zdate < '" . date("Y-m-d 00:00:00") . "'";
                $search = str_replace("yesterday", "", $search);
            }
            if (preg_match("#this week#", $search)) {
                $query[] = "zdate > '" . date("Y-m-d 00:00:00", strtotime('-7 days')) . "'";
                $search = str_replace("this week", "", $search);
            }

        }else{
            if($xdate<>null){
                $query[]=$xdate;
            }
            VERBOSE("ZTYPE DATE NOT SET !",__LINE__);
        }


        VERBOSE("COUNT of query=[".count($query)."] Max=$MaxORG",__LINE__);
        if(stripos($search, " AND ")>0){
            VERBOSE("EXPLODE FOR {AND} [$search]",__LINE__);
            $ttr=preg_split("/\s+AND\s+/i",$search);
            foreach ($ttr as $line){
                $line=trim($line);
                VERBOSE("SPLIT (AND): $line",__LINE__);
                if(preg_match("#^field:(.+?)(=|<|>|LIKE|IPADDRLIKE)(\s+|)(.+?)(\s|$)#i", $line,$re)){
                    VERBOSE("MATCH ($search)",__LINE__);
                    $search=str_ireplace("field:{$re[1]}{$re[2]}{$re[3]}{$re[4]}", "", $search);
                    $query[]="{$re[1]}".$this->query_field("{$re[2]}{$re[4]}",false);
                    continue;
                }
                if(preg_match("#^(.+?)(=|<|>|LIKE|IPADDRLIKE)(\s+|)(.+?)(\s|$)#i", $line,$re)){
                    if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>LINE.".__LINE__." <strong>MATCH</strong> $search</span><br>\n";}
                    $search=str_ireplace("{$re[1]}{$re[2]}{$re[3]}{$re[4]}", "", $search);
                    $query[]="{$re[1]}".$this->query_field("{$re[2]}{$re[4]}",false);
                    continue;
                }

            }

        }

        if(preg_match("#^field:(.+?)(=|<|>|LIKE|IPADDRLIKE)(\s+|)(.+?)(\s|$)#i", $search,$re)){
            VERBOSE("COUNT of query=[".count($query)."] Max=$MaxORG MATCH $search",__LINE__);
            $search=str_ireplace("field:{$re[1]}{$re[2]}{$re[3]}{$re[4]}", "", $search);
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>LINE.".__LINE__." query:{$re[2]}{$re[4]} newline=[$search]</span><br>\n";}
            $query[]="{$re[1]}".$this->query_field("{$re[2]}{$re[4]}",false);

        }
        if(preg_match("#^(.+?)(=|<|>|LIKE|IPADDRLIKE)(\s+|)(.+?)(\s|$)#i", $search,$re)){
            VERBOSE("MATCH: $search",__LINE__);
            VERBOSE("query:{$re[2]}{$re[4]} newline=[$search]",__LINE__);
            $search=str_ireplace("{$re[1]}{$re[2]}{$re[3]}{$re[4]}", "", $search);
            $query[]="{$re[1]}".$this->query_field("{$re[2]}{$re[4]}",false);

        }

        if(preg_match("#proto\s+([a-z]+)#",$search,$re)){
            VERBOSE("MATCH $search",__LINE__);
            $search=str_replace("proto {$re[1]}", "", $search);
            $query[]="proto".$this->query_field($re[1],false);
        }
        if(preg_match("#uid(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("uid{$re[1]}{$re[2]}", "", $search);
            $query[]="LOWER(uid)".$this->query_field($re[2]);
        }
        if(preg_match("#ipaddr(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            VERBOSE("#ipaddr(\s+|=)(.+?)(\s+|$)# match!! $search",__LINE__);
            $search=str_replace("ipaddr{$re[1]}{$re[2]}", "", $search);
            $query[]=$aliases["ipaddr"].$this->query_field($re[2],false);
        }else{
            VERBOSE("#ipaddr(\s+|=)(.+?)(\s+|$)# no match",__LINE__);
        }
        if(preg_match("#mac(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("mac{$re[1]}{$re[2]}", "", $search);
            $query[]=$aliases["mac"].$this->query_field($re[2]);
        }
        if(preg_match("#src_ip(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("src_ip{$re[1]}{$re[2]}", "", $search);
            $query[]=$aliases["src_ip"].$this->query_field($re[2]);
        }

        if(preg_match("#(website|site|sitename)(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $search);
            $query[]="LOWER(".$aliases["website"].")".$this->query_field($re[3]);
        }
        if(preg_match("#(ville|city|town)(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $search);
            $query[]="LOWER(".$aliases["city"].")".$this->query_field($re[3]);
        }
        if(preg_match("#(country|pays)(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $search);
            $query[]="LOWER(".$aliases["country"].")".$this->query_field($re[3]);
        }
        if(preg_match("#(computer|host|hostname|fqdn)(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $search);
            $query[]="LOWER(".$aliases["hostname"].")".$this->query_field($re[3]);
        }

        if(preg_match("#signature\s+([0-9\<\>\-]+)#",$search,$re)){
            $search=str_replace("signature {$re[1]}", "", $search);
            $query[]="signature".$this->query_field($re[1],false);
        }
        if(strpos($search, "everything")){
            $search=str_replace("everything", "", $search);
        }

        if(preg_match("#src(\s+|=)([0-9\.\*\<\>]+)#",$search,$re)){
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>LINE.".__LINE__." MATCH $search</span><br>\n";}
            if(trim($re[2])<>"*"){
                $query[]=$aliases["src_ip"].$this->query_field($re[2],false);
                $search=str_replace("src{$re[1]}{$re[2]}", "", $search);
            }
        }

        if(preg_match("#src ip(\s+|=)([0-9\.\*\<\>]+)#",$search,$re)){
            $query[]="src_ip".$this->query_field($re[2],false);
            $search=str_replace("src ip{$re[1]}{$re[2]}", "", $search);
        }

        if(preg_match("#srcport(\s+|=)([0-9\.\*\<\>\-]+)#",$search,$re)){
            $query[]="src_port".$this->query_field($re[2],false);
            $search=str_replace("srcport{$re[1]}{$re[2]}", "", $search);
        }

        if(preg_match("#dstport(\s+|=)([0-9\*\<\>\-]+)#",$search,$re)){
            $query[]="dst_port".$this->query_field($re[2],false);
            $search=str_replace("dstport{$re[1]}{$re[2]}", "", $search);
        }

        if(preg_match("#date\s+([0-9\/\-:\*\<\>]+)#",$search,$re)){
            $query[]="zdate".$this->query_field($re[1],false);
            $search=str_replace("date {$re[1]}", "", $search);
        }

        $search=trim($search);
        if($max==0){$max=300;}
        if(trim($search)<>null){
            VERBOSE("FINAL [$search]",__LINE__);
            if( ( !isset($aliases["text"]) ) && ( isset($_GET["types"]) ) ){
                $types=unserialize(base64_decode($_GET["types"]));
                if(!is_array($types)){$types=array();}
                $taliases=array();
                foreach ($types as $field_name=>$field_type){
                    $zfield_name=trim(strtolower($field_name));
                    if($zfield_name=="md5"){continue;}
                    if($zfield_name=="zmd5"){continue;}
                    if($field_type=="TEXT"){$taliases[]=$field_name;}
                }
                if(count($taliases)>0){
                    $search=str_replace("*","%",$search);
                    $search=str_replace("%%","%",$search);
                    $aliases["text"]=@implode(",",$taliases);}
            }



            $xs=array();
            if(isset($aliases["text"])){
                $textes=explode(",",$aliases["text"]);
                foreach ($textes as $field){
                    if(strpos(" $search","%")==0){$search="%$search%";}
                    $xs[]="($field LIKE '$search')";

                }
            }
            if(count($xs)>0){
                $query[]="(".@implode(" OR ", $xs).")";
            }
        }
        $query_finale=array();
        if(count($query)>0){
            VERBOSE("CLEANING....",__LINE__);
            foreach ($query as $line){
                $line=trim($line);
                $line5=md5($line);
                VERBOSE("$line",__LINE__);
                $FFF[$line5]=$line;}
            $query_finale=array();
            foreach ($FFF as $index=>$line2){
                VERBOSE("ADD....[$line2]",__LINE__);
                $query_finale[]=$line2;
            }
        }
        VERBOSE("COUNT of query=[".count($query_finale)."]",__LINE__);


        if(count($query)>0){
            return array("MAX"=>$MaxORG,"Q"=>" WHERE (".@implode(" AND ", $query_finale).") ","S"=>$search);
        }
        $search="%$search%";
        $search=str_replace("*","%",$search);
        $search=str_replace("%%","%",$search);
        $search=str_ireplace("and and","and",$search);
        return array("MAX"=>$MaxORG,"L"=>__LINE__,"S"=>$search,"Q"=>$search);
    }




    public function table_object($gpid,$link_table=null){
        $tpl=new template_admin();
        if(!isset($_GET["DnsDist"])){$DnsDist=0;}else{$DnsDist=intval($_GET["DnsDist"]);}
        if(isset($_GET["dnsfw"])){$DnsDist=$_GET["dnsfw"];}
        $SMTP=0;
        VERBOSE("gpid=$gpid, link_table=$link_table",__LINE__);
        if(isset($_GET["TableLink"])) {
            if ($_GET["TableLink"] == "webfilters_sqacllinks") {
                $acl_allowdeny = true;
            }
            if ($_GET["TableLink"] == "dnsfw_acls_link") {
                $DnsDist = 1;
            }
            if ($_GET["TableLink"] == "dnsdist_sqacllinks") {
                $DnsDist = 1;
            }
            if ($_GET["TableLink"] == "wpad_sources_link") {
                $ProxyPac = 1;
            }
            if ($_GET["TableLink"] == "postfix_sqacllinks") {
                $SMTP = 1;
            }
        }

        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $qProxy=new mysql_squid_builder(true);
        $qProxy->acl_GroupType["doh"]="{APP_DOH_BACKEND}";
        $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
        if(!$q->ok){echo $q->mysql_error;}
        $GroupName=null;


        $GroupType=$ligne["GroupType"];
        $acl_GroupType=array();
        foreach ($qProxy->acl_GroupType as $index=>$val){
            $acl_GroupType[$index]=$val;
        }
        foreach ($qProxy->acl_GroupType_DNSFW as $index=>$val){
            $acl_GroupType[$index]=$val;
        }


        foreach ($qProxy->acl_GroupType_DNSDIST as $index=>$val){
            $acl_GroupType[$index]=$val;
        }
        foreach ($qProxy->acl_GroupType_SMTP as $index=>$val){
            if(!isset($acl_GroupType[$index])) {
                $acl_GroupType[$index] = $val;
            }
        }

        if($DnsDist==0){
            $acl_GroupType["dst"]="{dst}";
        }

        $acl_GroupType["geoip"]="{geoip_location}";

        if(isset($acl_GroupType[$GroupType])){
            $GroupTypeName=$acl_GroupType[$GroupType];
        }else{
            $GroupTypeName=$qProxy->GroupTypeToString($GroupType);
        }


        $GroupTypeName=$this->_ENGINE_parse_body($GroupTypeName);
        $items=0;
        VERBOSE("$gpid - $GroupType - $GroupTypeName - {$ligne["GroupName"]}",__LINE__);

        $icon=$qProxy->acl_GroupTypeIcon[$GroupType];

        if($GroupType=="AclsGroup"){
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $qNet=new lib_sqlite("/home/artica/SQLITE/acls.db");
            $ligneNet=$qNet->mysqli_fetch_array("SELECT count(*) as tcount FROM webfilters_gpslink WHERE groupid=$gpid");
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>$ligneNet["tcount"],"TYPE"=>$GroupTypeName,
                "NOITEMS"=>False,"ICO"=>$icon);
        }


        if($GroupType=="localnet"){
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $GroupTypeName=$this->_ENGINE_parse_body($qProxy->acl_GroupType[$GroupType]);
            $qNet=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
            $ligneNet=$qNet->mysqli_fetch_array("SELECT count(*) as tcount FROM networks_infos WHERE enabled=1");
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>$ligneNet["tcount"],"TYPE"=>$GroupTypeName,
                "NOITEMS"=>False,"ICO"=>$icon);
        }

        if($GroupType=="url_db"){
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $GroupTypeName=$this->_ENGINE_parse_body($qProxy->acl_GroupType[$GroupType]);
            $WorkDir="/etc/squid3/acls/urlsdb/$gpid";
            $Count=intval(@file_get_contents("$WorkDir/COUNT"));
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>$Count,"TYPE"=>$GroupTypeName,"NOITEMS"=>true,"ICO"=>$icon);

        }
        if($GroupType=="the_shields"){
            $ERROR=null;
            $license=true;
            $kInfos            =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()){
                if (!isset($kInfos["enable"])) {$kInfos["enable"] = 0;}
                if (!isset($kInfos["status"])) {$kInfos["status"] = null;}
                VERBOSE("enable = {$kInfos["enable"]}, status= {$kInfos["status"]}",__LINE__);
                if ($kInfos["enable"] == 0) {$license = false;}
                if ($kInfos["enable"] == 0 && $kInfos["status"] == null) {$license = false;}

                if ($kInfos["enable"] == 1) {
                    $license=true;
                    if (intval($kInfos["expire"]) > 0) {
                        $reste_days = $this->TimeToDays($kInfos["expire"]);
                        if ($reste_days == 0) {$license = false;}
                    }
                }
            }
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $GroupTypeName=$this->_ENGINE_parse_body($qProxy->acl_GroupType[$GroupType]);
            $KSRNAsACls      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNAsACls"));
            $KSRNEnable      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
            $KSRNEmergency  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEmergency"));
            if($link_table=="dnsfw_acls_link"){$KSRNAsACls=1;}
            if(!$license){$ERROR="{license_error}";}
            if($KSRNEmergency==1){$ERROR="{emergency_mode}";}
            if(!$GLOBALS["CLASS_SOCKETS"]->TheShieldEnabled()){
                $ERROR="{external_module_disabled}";
            }
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>0,
                "TYPE"=>$GroupTypeName,"NOITEMS"=>true,"ERROR"=>$ERROR,"ICO"=>$icon);
        }

        if($GroupType=="dnsquerytype"){
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $GroupTypeName=$this->_ENGINE_parse_body("{dnsquerytype}");
            $ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$gpid'");
            $items=$ligne2["tcount"];
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>$items,
                "TYPE"=>$GroupTypeName,"NOITEMS"=>true,"ERROR"=>null,"ICO"=>$icon);
        }

        if($GroupType=="proxy_auth_ldap"){
            include_once(dirname(__FILE__).'/class.ldap-extern.inc');
            $p=new ldap_extern();
            preg_match("#^ExtLDAP:(.+?):(.+)#", $ligne['GroupName'],$re);
            $GroupName=$re[1];
            $DN=base64_decode($re[2]);
            $items=$p->CountDeUsersByGroupDN($DN);
        }
        if(preg_match("#time_quota:([0-9]+)#", $GroupType,$re)){
            $ligne2=$q->mysqli_fetch_array("SELECT * FROM quota_objects WHERE ID='{$re[1]}'");
            $GroupTypeName=$this->_ENGINE_parse_body("{time_quota} {$ligne2["objectname"]}");
        }
        if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $GroupType,$re)){
            $qProxy->acl_ARRAY_NO_ITEM[$GroupType]=true;
            $ligne2=$q->mysqli_fetch_array("SELECT * FROM sessions_objects WHERE ID='{$re[2]}'");
            $GroupTypeName=$this->_ENGINE_parse_body("{session_tracking} - {$re[1]} - {$ligne2["objectname"]}");
        }


        if($GroupName==null){$GroupName=$this->utf8_encode($ligne["GroupName"]);}
        if($GroupType=="all"){$items="*";}

    if (isset($qProxy->acl_ARRAY_NO_ITEM[$GroupType])) {
        return array("GROUPNAME" => "$GroupName", "ITEMS" => $items, "TYPE" => $GroupTypeName, "NOITEMS" => True, "ICO" => $icon);
    }


        if($items==0){
            $ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$gpid'");
            $items=$ligne2["tcount"];
        }


        return array("GROUPNAME"=>"$GroupName","ITEMS"=>$items,"TYPE"=>$GroupTypeName,"NOITEMS"=>False,"ICO"=>$icon);


    }

    public function CleanServiceName($source){
        $source=utf8_decode($source);
        $source=str_replace("_", "", $source);
        $source=str_replace(" ", "", $source);
        $source=str_replace("-", "", $source);
        $source=str_replace("%24", "", $source);
        $source=str_replace(" ", "", $source);
        $source=str_replace("\r ", "", $source);
        $source=str_replace("\n ", "", $source);
        $source=str_replace('\\', "", $source);
        $source=str_replace('/', "", $source);
        $source=str_replace('#', "", $source);
        $source=str_replace('"', "", $source);
        $source=str_replace('"', "", $source);
        $source=str_replace('&', "", $source);
        $source=str_replace('(', "", $source);
        $source=str_replace(')', "", $source);
        $source=str_replace('@', "", $source);
        $source=str_replace('[', "", $source);
        $source=str_replace(']', "", $source);
        $source=str_replace('{', "", $source);
        $source=str_replace('}', "", $source);
        $source=str_replace('à', "", $source);
        $source=str_replace('é', "", $source);
        $source=str_replace('^', "", $source);
        $source=str_replace('²', "", $source);
        $source=str_replace('=', "", $source);
        $source=str_replace(";", "", $source);
        $source=str_replace(",", "", $source);
        $source=str_replace("!", "", $source);
        $source=str_replace("§", "", $source);
        $source=str_replace("€", "", $source);
        $source=str_replace("£", "", $source);
        $source=str_replace("*", "", $source);
        $source=str_replace("%", "", $source);
        $source=str_replace("ù", "", $source);
        $source=str_replace("°", "", $source);
        $source=str_replace("|", "", $source);
        $source=str_replace("+", "", $source);
        $source=str_replace(")", "", $source);
        $source=str_replace("(", "", $source);
        $source=replace_accents($source);
        $source=trim(strtolower($source));
        return $source;
    }

    public function js_microdaliog_danger($title,$ajax):bool{
        header("content-type: application/x-javascript");
        $title=$this->_ENGINE_parse_body($title);
        $title=replace_accents($title);
        $title=$this->javascript_parse_text($title);

        //$draggable="draggable: false,";
        //$closable="closable: false,";
        $close_button="\n\tbuttons: [{ label: 'Close', action: function(dialogRef) { dialogRef.close(); } }],";


        echo "
if(typeof dialogInstance == 'object'){ dialogInstance.close();}	
		dialogInstance = new BootstrapDialog({
		draggable: false,
		closable: false,
		size: BootstrapDialog.SIZE_NORMAL,
		title: '$title',
		type: BootstrapDialog.TYPE_DANGER,
		message: function(dialog) {
		var \$message = $('<div><img src=\"img/wait-200.gif\" alt=\"\" class=centerimg></div>');
		var pageToLoad = dialog.getData('pageToLoad');
		\$message.load(pageToLoad);
		return \$message;
	},
	$close_button
	data: {
	'pageToLoad': '$ajax'
	}
	});
	
if(typeof dialogInstance == 'object'){ 
	dialogInstance.open();
	LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
}";
        return true;
    }
    public function js_no_license():bool{
        return $this->popup_error("{license_invalid}");

    }
    public function js_no_privileges():bool{
        return $this->popup_error("{ERROR_NO_PRIVS2}");
    }

    public function js_dialog1($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
        return $this->buildADialog($title,$ajax,$size,1);
    }

    public function js_restart_api($endpoint,$after=null):string{
        $MAIN["ENDPOINT"]=$endpoint;
        $MAIN["AFTER"]=$after;
        $bond=base64_encode(serialize($MAIN));
        return "Loadjs('fw.restartapi.php?restart-via-api=$bond');";
    }

    public function js_dialog2($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
        return $this->buildADialog($title,$ajax,$size,2);
    }
    public function js_dialog3($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
        return $this->buildADialog($title,$ajax,$size,3);
    }
    public function js_dialog4($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
       return  $this->buildADialog($title,$ajax,$size,4);
    }
    public function js_dialog5($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
       return  $this->buildADialog($title,$ajax,$size,5);
    }


    private function js_dialogsize($size=null){
        $SIZE_WIDE="SIZE_WIDE";
        if(is_null($size)){$size=650;}
        if($size=="small"){$SIZE_WIDE="SIZE_SMALL";}
        if($size<=500){$SIZE_WIDE="SIZE_SMALL";}
        if($size>=500){$SIZE_WIDE="SIZE_NORMAL";}

        $cssClass="size: BootstrapDialog.$SIZE_WIDE,";
        if($size>649){$cssClass="cssClass: 'dialog650',";}
        if($size>699){$cssClass="cssClass: 'dialog700',";}
        if($size>749){$cssClass="cssClass: 'dialog750',";}
        if($size>809){$cssClass="cssClass: 'dialog810',";}
        if($size>849){$cssClass="cssClass: 'dialog850',";}
        if($size>879){$cssClass="cssClass: 'dialog880',";}
        if($size>900){$cssClass="cssClass: 'dialog900',";}
        if($size>945){$cssClass="cssClass: 'dialog950',";}
        if($size>970){$cssClass="cssClass: 'dialog980',";}
        if($size>1029){$cssClass="cssClass: 'dialog1030',";}
        if($size>1099){$cssClass="cssClass: 'dialog1100',";}
        if($size>1140){$cssClass="cssClass: 'dialog1500',";}
        if($size>1199){$cssClass="cssClass: 'dialog1200',";}
        if($size>1369){$cssClass="cssClass: 'dialog1370',";}
        return $cssClass;
    }
    public function js_dialog6($title,$ajax,$size=null,$onclose=null):bool{return $this->buildADialog($title,$ajax,$size,6,$onclose);}
    public function js_dialog7($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,7);}
    public function js_dialog8($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,8);}
    public function js_dialog9($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,9);}
    public function js_dialog10($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,10);}
    public function js_dialog11($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,11);}
    public function js_dialog12($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,12);}
    public function js_dialog13($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,13);}

    private function buildADialog($title,$ajax,$size,$number,$onhidejs=null):bool{
        $cssClass=$this->js_dialogsize($size);
        $mODAL=false;
        if(!is_null($title)) {
            if (preg_match("#modal:(.+)#", $title, $re)) {
                $title = $re[1];
                $mODAL = true;
            }
        }
        if(preg_match("#'(.+?)'#",$ajax,$re)){$ajax=$re[1];}

        $array[0]="TYPE_WARNING";
        $array[1]="TYPE_SUCCESS";
        $array[2]="TYPE_INFO";
        $array[3]="TYPE_PRIMARY";

        $type=rand(0, 3);
        $sied=md5(rand(0,65000).time());
        header("content-type: application/x-javascript");
        $title=$this->_ENGINE_parse_body($title);
        $title=replace_accents($title);
        $title=$this->javascript_parse_text($title);
        $draggable="draggable: true,";
        $closable="closable: true,";
        $close_button=null;
        if($mODAL){
            $draggable="draggable: true,";
            $closable="closable: true,";

        }
        echo "
	//size=$size dialog number $number;
	if(typeof dialogInstance{$number} == 'object'){ dialogInstance$number.close();}
	dialogInstance{$number} = new BootstrapDialog({
		$draggable
		$closable
		$cssClass
		title: '$title',
		type: BootstrapDialog.{$array[$type]},
		autodestroy:true,
		closeByBackdrop: false,
		message: function(dialog) {
		    var \$message = $('<div><img src=\"img/wait-200.gif\" class=centerimg alt=\"\"></div>');
		    var pageToLoad = dialog.getData('pageToLoad');
		    \$message.load(pageToLoad);
		    return \$message;
	    },
		data: { 'pageToLoad': '$ajax','id':'$sied' },
		onshow: function(dialogRef){ $('body').Wload(); },
		onshown: function(dialogRef){ $('body').Wload('hide',{time:800});},
		onhide: function(dialogRef){
		        var id=$(this).attr('id');
		        $('#'+id).remove();
                $onhidejs
            },$close_button
	});
	
		if(typeof dialogInstance{$number} == 'object'){
			dialogInstance{$number}.open();
			LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		}";
        return true;
    }



    public function item_explain($GroupType):string{
        $explain=null;
        if($GroupType=="all"){$explain="{acl_all_text}";}
        if($GroupType=="src"){$explain="{acl_src_text}";}
        if($GroupType=="dst"){$explain="{acl_dst_text}";}
        if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
        if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
        if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
        if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
        if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
        if($GroupType=="req_mime_type"){$explain="{req_mime_type_explain}";}
        if($GroupType=="rep_mime_type"){$explain="{rep_mime_type_explain}";}
        if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
        if($GroupType=="srcdomain"){$explain="{acl_squid_srcdomain_explain}";}
        if($GroupType=="url_regex_extensions"){$explain="{url_regex_extensions_explain}";}
        if($GroupType=="max_user_ip"){$explain="<b>{acl_max_user_ip_title}</b><br>{acl_max_user_ip_text}";}
        //if($GroupType=="quota_time"){$explain="{acl_quota_time_text}";}
        if($GroupType=="quota_size"){$explain="{acl_quota_size_text}";}
        if($GroupType=="ssl_sni"){$explain="{acl_ssl_sni_text}";}
        if($GroupType=="myportname"){$explain="{acl_myportname_text}";}
        if($GroupType=="rep_header_filename"){$explain="{rep_header_filename_explain}";}
        if($GroupType=="ldap_group"){$explain="{ldap_group}";}
        if($GroupType=="server_cert_fingerprint"){$explain="{server_cert_fingerprint_explain}";}
        if($GroupType=="weekrange"){$explain="{weekranges}";}
        if($GroupType=="AclsGroup"){$explain="{new_group_of_objects_explain}";}
        if($GroupType=="envfrom"){$explain="{acl_envfrom_explain}";}
        if($GroupType=="envto"){$explain="{acl_envto_explain}";}


        if($explain==null){
            $Sql=new mysql_squid_builder();
            if(isset($Sql->acl_GroupType_explain[$GroupType])){
                $explain=$Sql->acl_GroupType_explain[$GroupType];
            }

        }


        return strval($explain);
    }

    public function js_dialog_modal($title,$ajax,$size=650,$OnClose=null):bool{
        $cssClass=$this->js_dialogsize($size);
        $array[0]="TYPE_WARNING";
        $array[1]="TYPE_SUCCESS";
        $array[2]="TYPE_INFO";
        $array[3]="TYPE_PRIMARY";
        $Close=$this->javascript_parse_text("{cancel}");
        $type=rand(0, 3);
        header("content-type: application/x-javascript");
        $title=$this->_ENGINE_parse_body($title);
        $title=trim($title);
        $title=replace_accents($title);

        echo "DialogModal = new BootstrapDialog({
		draggable: false,
		title: '$title',
		$cssClass
		type: BootstrapDialog.$array[$type],
		autodestroy: true,
		closable: false,
		buttons: [{ label: '$Close', action: function(dialogRef) { dialogRef.close(); } }],
		message: function(dialog) {
		var \$message = $('<div><img src=\"img/wait-200.gif\" class=centerimg alt=\"\"></div>');
		var pageToLoad = dialog.getData('pageToLoad');
		\$message.load(pageToLoad);
		
		return \$message;
		},
		data: { 'pageToLoad': '$ajax' },
        onshow: function(dialogRef){ $('body').Wload(); },
        onshown: function(dialogRef){ $('body').Wload('hide',{time:800});}
		});
        DialogModal.realize();
        DialogModal.getModalHeader().hide();
        DialogModal.getModalFooter().hide();
        DialogModal.getModalBody().css('background-color', '#FFFFFF');
        DialogModal.getModalBody().css('border', '0px');
		DialogModal.open();";
        return true;
    }

    public function js_dialog_confirm($title,$ajax){
        header("content-type: application/x-javascript");
        $title=$this->_ENGINE_parse_body($title);
        $title=trim($title);
        $title=replace_accents($title);
        $Close=$this->javascript_parse_text("{cancel}");
        $title=$this->_ENGINE_parse_body($title);
        $title=replace_accents($title);
        $title=$this->javascript_parse_text($title);
        echo "DialogConfirm = new BootstrapDialog({
		draggable: false,
		size: BootstrapDialog.SIZE_NORMAL,
		title: '$title',
		type: BootstrapDialog.TYPE_DANGER,
		buttons: [{ label: '$Close', action: function(dialogRef) { dialogRef.close(); } }],
		message: function(dialog) {
		var \$message = $('<div><img src=\"img/wait-200.gif\" class=centerimg></div>');
		var pageToLoad = dialog.getData('pageToLoad');
		\$message.load(pageToLoad);
		
		return \$message;
		},
		data: { 'pageToLoad': '$ajax' },
        onshow: function(dialogRef){ $('body').Wload(); },
        onshown: function(dialogRef){ $('body').Wload('hide',{time:800});}
		});
		
		
		DialogConfirm.open();";


    }

    public function CleanWebSite($site){
        $IP=new IP();
        $site=trim(strtolower($site));
        if($IP->isIPAddressOrRange($site)){return $site;}
        if(preg_match("#^http#", $site)){
            $arrayURI=parse_url($site);
            $site=$arrayURI["host"];
        }

        if(strpos($site, "/")>0){
            $site="http://$site";
            $arrayURI=parse_url($site);
            $site=$arrayURI["host"];
        }

        if(preg_match("#(.+.):([0-9]+)", $site,$re)){
            $site=$re[1];
        }
        return $site;
    }


    public function js_dialog($title,$ajax,$onhidejs=null):bool{
        header("content-type: application/x-javascript");
        $title=$this->_ENGINE_parse_body($title);
        $title=replace_accents($title);
        $title=$this->javascript_parse_text($title);
        echo "var types = [BootstrapDialog.TYPE_DEFAULT,
		BootstrapDialog.TYPE_INFO,
		BootstrapDialog.TYPE_PRIMARY,
		BootstrapDialog.TYPE_SUCCESS,
		BootstrapDialog.TYPE_WARNING,
		BootstrapDialog.TYPE_DANGER];
		

		var BootstrapDialog1 = BootstrapDialog.show({
			draggable: true,
			size: BootstrapDialog.SIZE_WIDE,
			title: '$title',
			type: BootstrapDialog.TYPE_SUCCESS,
            message: function(dialog) {
                var \$message = $('<div><img src=\"img/wait-200.gif\" class=centerimg alt=\"\"></div>');
                var pageToLoad = dialog.getData('pageToLoad');
                \$message.load(pageToLoad);
        
                return \$message;
            },
		data: { 'pageToLoad': '$ajax' },
        onshow: function(dialogRef){ $('body').Wload(); },
        onshown: function(dialogRef){ $('body').Wload('hide',{time:800});},
        onhide: function(dialogRef){
                $onhidejs
            },
        });";

        return true;


    }
    public function StripSpecialsChars($pattern){

        $html=new htmltools_inc();
        return $html->StripSpecialsChars($pattern);
    }

    public function form_add_button_upload($btname,$page=null,$security=null):string{
        if($security<>null){if(!$this->IsSecurity()){return false;}}
        $this->buttons["UPLOAD::$btname"]=$page;
        return "";
    }


    public function form_add_button($btname,$js):string{
        $btname=$this->_ENGINE_parse_body($btname);
        $this->buttons[$btname]=$js;
        return "";
    }

    public function form_add_button_title($btname,$js):string{
        $this->button_title[$btname]=$js;
        return "";
    }

    private function form_button($btname,$js,$classname=null,$security=null):string{
        if($security<>null){if(!$this->IsSecurity()){return $this->form_button_disabled($btname);}}

        if(preg_match("#^UPLOAD::(.+)#", $btname,$re)){
            return $this->button_upload($re[1],$js,"BYFORM:$classname");
        }




        $css="btn-lg";
        if($classname<>null){$classtext=$classname;}else{$classtext="btn-success";}
        if($this->FORM_IN_ARRAY){
            $css="btn btn-w-m";
        }
        $this->button_id=md5("$btname$js");


        return "\n<!-- BUTTON class = $classname / $classtext  -->\n<button class=\"btn $classtext $css\" type=\"button\" id='$this->button_id' style=\"text-transform: capitalize;\" OnClick=\"$js\"><strong style=\"text-transform: capitalize;\">&laquo;&nbsp;$btname&nbsp;&raquo;</strong></button>";

    }

    private function form_button_disabled($btname):string{

        if(preg_match("#^UPLOAD::(.+)#", $btname,$re)){$btname=$re[1];}

        return "\n<!-- class form_button_disabled --><button class=\"btn btn-w-m btn-default\" style=\"text-transform: capitalize;\"  type=\"button\"  OnClick=\"blur()\"><strong style=\"text-transform: capitalize;\">&laquo;&nbsp;$btname&nbsp;&raquo;</strong></button>";


    }

    //"btn-primary","small"
    public function form_button_upload($btname,$page,$explain=null,$security=null):string{
        $bt=$this->button_upload($btname,$page);
        if($security<>null){if(!$this->IsSecurity($security)){$bt=$this->form_button_disabled($btname);}}
        return $this->_field_intr("&nbsp;",$bt,$explain);
    }

    public function button_medium($btname,$class=null,$js=null):string{
        $btname=$this->_ENGINE_parse_body($btname);
        $btgb=$this->btn_array();
        $sclass=$btgb[$class];
        if($js==null){$js="blur()";}
        return "<button class='btn $sclass btn-xs' type='button' OnClick=\"$js\">$btname</button>";
    }
    
    public function button_tooltip($btname,$js=null,$icon_class=null,$security=null,$class="label-primary"):string{



        $over="onMouseOver=\"this.style.cursor='pointer';this.style.fontWeight='bold'\" OnMouseOut=\"this.style.cursor='default';this.style.fontWeight='600'\"";
        
        if($js==null){
            if(!$this->IsSecurity($security)){
                $btns[] = "<label class=\"label $class\" OnClick=\"blur();\">";
                if($icon_class<>null){$btns[] = "<i class='$icon_class'></i>";}
                $btns[]= " $btname </label>";
                return @implode("",$btns);
            }
        }

        if($security<>null){
            if(!$this->IsSecurity($security)){
                $btns[] = "<label class=\"label label-default\" OnClick=\"blur();\">";
                if($icon_class<>null){$btns[] = "<i class='$icon_class'></i>";}
                $btns[]= " $btname </label>";
                return @implode("",$btns);
            }
        }

        $btns[] = "<label class=\"label $class\" OnClick=\"$js\" $over>";
        if($icon_class<>null){$btns[] = "<i class='$icon_class'></i>";}
        $btns[]= " $btname </label>";
        return @implode("",$btns);

    }
    
    public function button_inline($btname,$js,$icon_class,$security=null,$width=0,$class="btn-primary",$btsize=null):string{
        $btcnass_size="btn";
        if($class==null){$class="btn-primary";}
        if($btsize=="small"){$btcnass_size="btn-xs";}
        if($security<>null){
            if(!$this->IsSecurity($security)){
                $btns[] = "<label class=\"btn $btcnass_size btn-default\" OnClick=\"blur();\">";
                $btns[] = "<i class='$icon_class'></i> $btname </label>";
                return @implode("",$btns);
            }
        }

        $btns[] = "<label class=\"btn $btcnass_size $class\" OnClick=\"$js;\">";
        $btns[] = "<i class='$icon_class'></i> $btname </label>";
        return @implode("",$btns);
    }
    public function button_wiki($url){
        $url="s_PopUpFull('$url','1024','900');";
        return $this->button_autnonome("{online_help}",$url,ico_support,null,null,"btn-info");
    }

    public function button_autnonome($btname,$js,$icon_class,$security=null,$width=0,$class="btn-primary",$btsize=null):string{
        $fa="fa";
        $btsize_class="";
        // fas fa-ban
        // fas fa-check-circle
        // fas fa-sync-alt
        // fas fa-shield-check
        // fas fa-file-certificate
        // fas fa-trash-alt
        // btn-primary
        // btn-default
        // fad fa-hat-wizard
        $xlog[]="<!-- class=$class -->";
        if($icon_class==null){$icon_class="fas fa-save";}


        if(preg_match("#^(.+?)\s+(.+)#", $icon_class,$re)){
            $fa=$re[1];
            $icon_class=$re[2];
        }else{
            if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>$icon_class no match ^(.+?)\s+(.+)</span><br>\n";}
        }

        if($btsize=="normal" OR $btsize==null){$btsize_class="";}
        if($btsize=="small"){$btsize_class=" btn-sm";}


        VERBOSE("icon = $fa and $icon_class btsize=$btsize --> $btsize_class security=$security",__LINE__);

        // btn-default,btn-primary,btn-success,btn-info,btn-warning,btn-danger,btn-link
        $i_class=null;
        if($security<>null){if(!$this->IsSecurity($security)){return $this->form_button_disabled($btname);}}
        $widthcss=null;
        if(is_numeric($width)) {
            if ($width > 0) {
                $widthcss = "width:{$width}px";
            }
        }
        if(strpos($width,"%")>0){
            $widthcss = "width:{$width}";
        }

        $insideButton="<i class=\"$fa $icon_class $i_class\"></i>&nbsp;$btname";

        if($width>289) {
            if ($width < 300) {
                $button_wdith = "9";
                $insideButton = "<div style='text-align:left;padding-top:0'>
				<i class='$fa $icon_class' style='width:{$button_wdith}%;text-align:left'></i>
				<div style='float:right;text-align:right;width:100%;margin-top:-8%'>$btname</div>
			</div>";
            }
        }

        if($width>500){
            if($width<651) {
                $widthcss = $widthcss . ";height:95px";
            }
            $button_wdith="19";

            $insideButton="<div style='text-align:left;padding-top:0'>
				<i class='$fa $icon_class fa-4x' style='width:{$button_wdith}%;text-align:left'></i>
				<div style='font-size:22px;float:right;text-align:right;width:90%;margin-top:-6%'>$btname</div>
			</div>";

        }
        if($width>650){
            $widthcss=$widthcss.";height:85px";
            $insideButton="<div style='text-align:left;padding-top:0'>
				<i class='$fa $icon_class fa-5x' style='width:11%;text-align:left'></i>
				<div style='font-size:33px;float:right;text-align:right;width:90%;margin-top:-9%'>$btname</div>
			</div>";

        }
        $log=@implode("\n",$xlog);
        return "$log<button type=\"button\" class=\"btn $class{$btsize_class}\" OnClick=\"$js\" style='text-transform: capitalize;$widthcss'>".$this->_ENGINE_parse_body($insideButton)."</button>";
    }

    public function javascript_senderror($text,$div):bool{
        $text=$this->_ENGINE_parse_body($this->div_error($text));
        $text=base64_encode($text);
        $f[]="if (document.getElementById('$div')){";
        $f[]="document.getElementById('$div').innerHTML=base64_decode('$text');";
        $f[]="}";
        echo @implode("",$f);
    return true;
    }


    public function ArrayNics($notun=false){

        $net=new networking();
        $interfaces=$net->Local_interfaces();
        unset($interfaces["lo"]);

        foreach ($interfaces as $eth){
            if($notun){if(preg_match("#^tun[0-9]+#", $eth)){continue;}}
            $nic=new system_nic($eth);
            if($nic->enabled==0){continue;}
            $array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        }
        $array[null]="{none}/{default}";
        return $array;
    }


    public function is_firewall(){
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
        $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
        if($users->SURICATA_INSTALLED){
            $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
        }

        if($EnableSuricata==1){ return true;}
        if($FireHolEnable==1){return true;}



        return false;


    }

    public function jsToTry($pattern):string{
        if($pattern==null) {return "";}
        $html=array();
        $jsss=array();
        if( strpos(" $pattern","if(") ) {
            $jsss[]=$pattern;

        }else{
            $jsss = explode(";", $pattern);
        }


            foreach ($jsss as $func) {
                $func=trim($func);
                if($func==null){continue;}
                if($func=="()"){continue;}
                $html[] = "//\t\tjsToTry";
                $html[] = "\t\ttry {";
                $html[] = "\t\t\t$func;";
                $html[] = "\t\t} catch (err) {";
                $html[] = "//\t\t\talert(err);";
                $html[] = "\t\t}";
                $html[] = "";
            }
        return @implode("\n",$html);
    }


    private function computers_rights():bool{

        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
        if($users->AsSystemAdministrator){return true;}
        if($users->ASDCHPAdmin){return true;}
        if($users->AsSambaAdministrator){return true;}
        if($users->AsDnsAdministrator){return true;}
        return false;
    }


    public function XHR_BUILD($page,$afterPost_function=null):string{
        $html[]="\tvar XHR = new XHRConnection();";
        $html[]="//\t".count($this->XHRHIDDEN)." hidden fields";

        foreach ($this->XHRHIDDEN as $XHRHIDDEN){
            $html[]="\t$XHRHIDDEN";
        }
        $html[]="//\t".count($this->XHR)." XHR fields";
        foreach ($this->XHR as $XHR){
            $html[]="\t$XHR";

        }
        if($afterPost_function<>null) {
            $html[] = "\tXHR.sendAndLoad('$page', 'POST',$afterPost_function);	";
            return @implode("\n",$html);
        }
        $html[] = "\tXHR.sendAndLoad('$page', 'POST');";
        return @implode("\n",$html);
    }


    public function form_outside($title,$content,$explain=null,$btname=null,$jsafter=null,$security=null,$CheckLicense=false,$disabled_form=false,$iconclass=null){
        if($btname==null){$btname="{apply}";}
        if($btname=="remove-button"){$btname="";}
        $help=null;
        $margin_top=null;
        $notitle="notitle:";$explainico="";

        if(is_array($explain)){
            $explain2=implode(" ",$explain);
            unset($explain);
            $explain=$explain2;
        }
        if(!is_null($explain)) {
            if (strpos($explain, "|") > 0) {
                $iconclass = "fa-solid fa-headset";
                $tt = explode("|", $explain);
                $explain = $tt[0];
                $help = $tt[1];
                $margin_top = "25";
                if(isset($tt[2])){
                    $explainico="ico:$tt[2];";
                    $explain =$tt[1];
                    $notitle=$tt[0]."||";
                    $margin_top = null;
                    $help="";
                    $iconclass="";
                }

            }

            if (preg_match("#^http.*wiki\.articatech\.com#", trim($explain))) {
                $help = $explain;
                $explain = null;
                $iconclass = "fa-solid fa-headset";
                $margin_top = "25";
            }
        }

        $page=CurrentPageName();
        if(is_array($content)){$content=@implode("\n", $content);}
        $LIMIT=false;
        $bts=array();
        $ERROR_PRIVS=null;
        $button=null;
        if($btname<>null){$btname=$this->_ENGINE_parse_body($btname);}
        $SaveIntro=null;
        $LOCKBTS=false;
        if($explain<>null){$explain=$this->div_explain("{$notitle}$explainico$explain");}
        $id=md5($content.serialize($this->XHRHIDDEN).serialize($this->XHR).time().rand(0,65535));
        if($GLOBALS["VERBOSE"]){VERBOSE("BTNAME DEFINED AS [$btname]", __LINE__);}
        if($GLOBALS["VERBOSE"]){VERBOSE("JSAFTER DEFINED AS [$jsafter]", __LINE__);}
        if($GLOBALS["VERBOSE"]){VERBOSE("SECURITY DEFINED AS [$security]", __LINE__);}
        if($GLOBALS["VERBOSE"]){VERBOSE("LICENSE DEFINED AS [$CheckLicense]", __LINE__);}
        if(!is_null($jsafter)) {
            $jsafter = str_replace(";!!;", ";", $jsafter);
        }

        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
        if($security=="license"){

            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
                if($GLOBALS["VERBOSE"]){VERBOSE("license FALSE in security", __LINE__);}
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_license}</div>");
                $SaveIntro="return;";
            }
        }
        if($security=="nolicense"){
            $LOCKBTS=true;
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_speciallicense}</div>");
            $SaveIntro="return;";
        }
        if($CheckLicense){

            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
                if($GLOBALS["VERBOSE"]){VERBOSE("license FALSE in CheckLicense", __LINE__);}
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_license}</div>");
                $SaveIntro="return;";
            }
        }
        if($disabled_form){
            $LOCKBTS=true;
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked}</div>");
            $SaveIntro="return;";
        }
        if($SaveIntro==null){
            if($security=="disabled"){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){VERBOSE("FALSE in security=\"disabled\"", __LINE__);}
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_disabled}</div>");
                $SaveIntro="return;";
            }}
        if($SaveIntro==null){if($security=="notinstalled"){
            $LOCKBTS=true;
            if($GLOBALS["VERBOSE"]){VERBOSE("license FALSE in security=notinstalled", __LINE__);}
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_not_installed}</div>");
            $SaveIntro="return;";
        }}
        if($SaveIntro==null){if($security=="dhcpfixed"){
            $LOCKBTS=true;
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_not_dhcpfixed}</div>");
            $SaveIntro="return;";
        }}


        if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: $security</span><br>\n";}

        if($SaveIntro==null){
            if($security=="computer"){
                if(!$this->computers_rights()){
                    $LOCKBTS=true;
                    if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: computer -> False</span><br>\n";}
                    $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                    $SaveIntro="return;";
                }
            }}
        if($SaveIntro==null){if($security=="AsSquidAdministrator"){

            if(!$users->AsSquidAdministrator){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AsSquidAdministrator -> False</span><br>\n";}
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsDnsAdministrator"){

            if(!$users->AsDnsAdministrator){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AsDnsAdministrator -> False</span><br>\n";}
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsDatabaseAdministrator"){

            if(!$users->AsDatabaseAdministrator){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AsDatabaseAdministrator -> False</span><br>\n";}
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsFirewallManager"){

            if(!$users->AsFirewallManager){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AsFirewallManager -> False</span><br>\n";}
                $ERROR_PRIVS="<div class='alert alert-danger'>{this_form_locked_privileges}</div>";
                $SaveIntro="return;";
            }

        }	}
        if($SaveIntro==null){if($security=="AllowAddUsers"){
            if(!$users->AllowAddUsers){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AllowAddUsers -> False</span><br>\n";}
                $ERROR_PRIVS="<div class='alert alert-danger'>{this_form_locked_privileges}</div>";
                $SaveIntro="return;";
            }
        }}
        if($SaveIntro==null){
            if($security=="AllowAddGroup"){
                if(!$users->AllowAddGroup){
                    $LOCKBTS=true;
                    if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AllowAddGroup -> False</span><br>\n";}
                    $ERROR_PRIVS="<div class='alert alert-danger'>{this_form_locked_privileges}</div>";
                    $SaveIntro="return;";
                }

            }
        }
        if($SaveIntro==null){if($security=="AsProxyMonitor"){
            if(!$users->AsProxyMonitor){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsDnsAdministrator"){
            if(!$users->AsDnsAdministrator){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsPostfixAdministrator"){
            if(!$users->AsPostfixAdministrator){
                if($GLOBALS["VERBOSE"]){VERBOSE("FALSE in AsPostfixAdministrator=NONE", __LINE__);}
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsSystemAdministrator"){
            if(!$users->AsSystemAdministrator){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsWebStatisticsAdministrator"){
            if(!$users->AsWebStatisticsAdministrator){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsDansGuardianAdministrator"){
            if(!$users->AsDansGuardianAdministrator){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($this->FORM_LOCKED){
            $LOCKBTS=true;
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-warning'>{$this->this_form_locked_explain}</div>");
            $SaveIntro="return;";
        }
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-warning'>{formlock_clutser_slave}</div>");
                $LOCKBTS=true;
                $SaveIntro="return;";
            }
        }
        if ($this->KEEPALIVED_CLI) {
            $ERROR_PRIVS = $this->_ENGINE_parse_body("<div class='alert alert-warning'>{formlock_clutser_slave}</div>");
            $LOCKBTS = true;
            $SaveIntro = "return;";
        }
        if(!$LOCKBTS){
            if($btname<>null){$button=$this->form_button($btname,"Save$id()");}
        }else{
            if($btname<>null){$button=$this->form_button_disabled($btname);}
        }
        if(preg_match("#Remove:Button#",$btname)){
            $button=null;
        }
        if($btname=="NO"){$button=null;}

        $ccl[0]="btn-warning";
        $ccl[1]="btn-info";
        $ccl[2]="btn-success";
        $title_buttons_final    = null;
        $title_buttons          = array();

        if(count($this->buttons)>0){
            $c=0;
            foreach ($this->buttons as $btname2=>$js){

                if(!$LOCKBTS){
                    if($c>2){$c=0;}
                    VERBOSE("GENERATE BUTTON -$btname2- -> $js",__LINE__);
                    $bts[]=$this->form_button($btname2,$js,$ccl[$c]);
                    $c++;
                }else{
                    $bts[]=$this->form_button_disabled($btname2);
                }

            }


            if(!$LOCKBTS){
                VERBOSE("GENERATE BUTTON -$btname- -> Save$id()",__LINE__);
                if($btname<>null){$bts[]=$this->form_button($btname,"Save$id()");}
            }else{
                $bts[]=$this->form_button_disabled($btname);
            }
            $button=@implode("\n", $bts);
        }
        if(is_null($this->form_id)){
            $this->form_id="FRM".time();
            if(!is_null($content)){
                $this->form_id="FRM_".md5($content);
            }
        }
        if($iconclass<>null){
            if($margin_top==null){$margin_top="-21";}
            if(preg_match("#^margin:(.+?):(.+)#",$iconclass,$re)){
                $iconclass=$re[2];
                $margin_top=$re[1];

            }
            $html[]="<table style='width:100%'>";
            $html[]="<tbody><tr>";
            $html[]="<td style='padding-right: 10px;vertical-align:top;width:1%'  nowrap>";
            $html[]="<div style='margin-top:{$margin_top}px'>";
            $html[]="<i class='fa-8x $iconclass'></i>";

            $html[]="</div>";

            if($help<>null){
                $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top: 15px'>";
                $jshelp="s_PopUpFull('$help',1024,768,'$title')";
                $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$jshelp\"><i class='$iconclass'></i> {online_help} </label>";
                $html[]="</div>";


            }
            $html[]="</td>";
            $html[] = "<td style='vertical-align:top;border-left: 3px solid;border-left-color: #F4F4F4; padding-left:10px;'><div style='margin-top:0'>";

        }

        $html[]="<div id='inside-form-alert'>$this->init_error</div>";
        $html[]="<div class=\"row\" style='width:100%;margin-left:10px'>";

        if(count($this->button_title)>0){
            $c=0;
            foreach ($this->button_title as $name=>$js){
                if($c>2){$c=0;}
                $title_buttons[]="<button type=\"button\" class=\"btn {$ccl[$c]} btn-sm\" OnClick=\"$js\" >$name</button>";
                $c++;

            }

            $title_buttons_final=@implode("&nbsp;",$title_buttons);
            $title_buttons_final="<div style='text-align:right;float:right;width:50%'>$title_buttons_final</div>";
        }

        if($title<>null){
            $title= "<div class=ibox-title style='border-top:0;text-align:left'><H3>$title_buttons_final$title</H3></div>";
        }else{
            if($title_buttons_final<>null) {
                $title = "<div class=ibox-title style='border-top:0;text-align:left'>$title_buttons_final</div>";
            }
        }

        $background="none";
        $border="#EAEAEA";
        $shadow="#EAEAEA";
        $inset="inset -1px -1px 0px";
        $class_form="margin-top:10px !important;border-radius: 4px;-webkit-border-radius: 4px;-moz-border-radius: 10px;margin: 0px 0px 10px 0px;border: 1px solid $border;background:$background;box-shadow: $inset $shadow;-moz-box-shadow: $inset $shadow; -webkit-box-shadow: $inset $shadow;padding-bottom:0;border-top:0px;border-left:0px";
        $html[]="<div class=\"col-lg-12\" style='$class_form'>";
        $html[]="<div class='ibox float-e-margins'>$title";
        $html[]="$ERROR_PRIVS	
						<div id='form-disabled-$this->form_id'></div>				
						$explain
						<form role='form' id='$this->form_id' name='$this->form_id'>
						<table style='width:100%'>
							$content
						<tr>
							<td colspan=2 style='text-align: right'><hr>
								<div style='width:100%;text-align:right'>$button</div>
							</td>
						</table>
						</form>
					</div>
				</div>
			";

       // if(!$this->formwizard){$html[]="</div>";}
        if($iconclass<>null){$html[]="</td></tr></table>";}

        if($this->FORM_IN_ARRAY){
            $html=array();
            $html[]="<td><span id='inside-form-alert'>$this->init_error</span>".$this->form_button($btname,"Save$id()");
        }
        $before_save=null;
        $reconfigure_service_forget=$this->javascript_parse_text("{reconfigure_service_forget}");

        if($this->form_privileges<>null){
            if($this->form_privileges=="AsSquidAdministrator"){
                if(!$users->AsSquidAdministrator){$LIMIT=true;}
            }

        }
        if($LIMIT){
            $ERROR_NO_PRIVS2=$this->javascript_parse_text("{ERROR_NO_PRIVS2}");
            $before_save="alert('$ERROR_NO_PRIVS2');return;";
        }

        $success_form_submited=$this->javascript_parse_text("{success_form_submited}");
        $html[]="
<script>
// JS HEAD == ". count($this->JS)." lines";
        $html[]="// JS Funcs == ". count($this->JS_FUNCs)." lines";
        $html[]="function FieldHTMLError(id){";
        $html[]="\tNoSpinner();";
        $html[]="\t$('#'+id).focus();";
        $html[]="\t$('#'+id).css('border-color', '#ED5565');";
        $html[]="\t$('#'+id).css('color', '#ED5565');";
        $html[]="\t$('#'+id).css('font-weight', 'bold');";
        $html[]="}\n\n";
        $html[]="var emailIsValid =  function(value){";
        $html[]="\tvar regex = new RegExp (\"^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,3}$\");";
        $html[]="\treturn regex.test(value);";
        $html[]="\t}\n\n";
        $html[]="// JS();";
        $html[]="// JS_FUNCs();";
        $html[]=@implode("\n", $this->JS);
        $html[]=@implode("\n",$this->JS_FUNCs);
        $html[]="var xSave$id= function (obj) {";
        $html[]="\t\$('body').Wload('hide',{time:800});";
        $html[]="\t$SaveIntro";
        $html[]="\t$before_save\n";


        reset($this->DISABLE_ALL);
        foreach ($this->DISABLE_ALL as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            $html[]="\t\t$('#$ids').css('border-color', '#E5E6E7');";
            $html[]="\t\t$('#$ids').css('color', 'inherit');";
            $html[]="\t\t$('#$ids').css('font-weight', 'normal');";
            $html[]="\t\t}";
        }


        $html[]="\tvar res=obj.responseText;";
        $html[]="\tif (res.length>3){";
        $html[]="\tvar regex = new RegExp('jserror:(.+)');";
        $html[]="\t		if( regex.exec(res) ){";
        $html[]="\tvar sText=RegExp.$1;";
        $html[]="\tcr = String.raw`\\n`;";
        $html[]="\tsText=sText.replace(/[\\r\\n|\\r|\\n|#]/g, '<br>');";
        $html[]="\tsText=sText.replace(/cr/g, '<br>');";
        $html[]="\t//		    sText=sText.replace('#', '<br>');";
        $html[]="\tswal( {title:'Oops...', text:'<strong>'+sText+'</strong>', html: true,type:'error'});";
        $html[]="\t//LoadAjax('artica-modal-dialog','fw.popup.php?title='+encodeURIComponent('{post_error}')+'&subtitle='+encodeURIComponent(RegExp.$1))";
        $html[]="\treturn;";
        $html[]="\t}";
        $html[]="\tNoSpinner();";
        $html[]="\tdocument.getElementById('inside-form-alert').style.marginTop='6px';";
        $html[]="\tdocument.getElementById('inside-form-alert').className='alert alert-warning alert-dismissable';";
        $html[]="\tdocument.getElementById('inside-form-alert').innerHTML='<strong style=font-size:18px>'+res+'</strong>';";
        $html[]="\treturn;";
        $html[]="\t}";

        $html[]="\tif(document.getElementById('reconfigure-service-div') ){";
        $html[]="\tdocument.getElementById('reconfigure-service-div').style.marginTop='6px';";
        $html[]="\tdocument.getElementById('reconfigure-service-div').className='alert alert-warning alert-dismissable';";
    $html[]="\tdocument.getElementById('reconfigure-service-div').innerHTML='$reconfigure_service_forget';";
    $html[]="\t}";
    $html[]="\t\ttoastr.options.timeOut = 2000;";
    $html[]="\t\ttoastr.options.progressBar = true;";
    $html[]="\t\ttoastr.success('$success_form_submited');";
	$html[]="\t\tLoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
	$html[]="\t\tNoSpinner();";
    $html[]=$this->jsToTry($jsafter);
    $html[] = "\t}";
    $html[]="\tfunction Save$id(){";
	$html[]="\t$('body').Wload();";
	$html[]="\tdocument.getElementById('inside-form-alert').className='';";
	$html[]="\tdocument.getElementById('inside-form-alert').innerHTML='';";
	$html[]="\tvar XHR = new XHRConnection();";
    $html[]="//\t".count($this->XHRHIDDEN)." hidden fields";
    foreach ($this->XHRHIDDEN as $XHRHIDDEN){
            $html[]="\t$XHRHIDDEN";
    }
        $html[]="//\t".count($this->XHR)." XHR fields";
        foreach ($this->XHR as $XHR){
            $html[]="\t$XHR";

        }

    $html[]="\tXHR.sendAndLoad('$page', 'POST',xSave$id);	";
    $html[]="}";
    $html[]="function CheckFormCheckBox(vid){";
    $html[]="\tif(document.getElementById(vid).checked){";
    $html[]="\t\tSwitchFormInsteadOf(vid,0);";
    $html[]="\t\treturn;";
    $html[]="\t}";
    $html[]="\tSwitchFormInsteadOf(vid,1);";
    $html[]="}";
    $html[]="function SwitchFormInsteadOf(vid,direction){";
    $html[]="\tif(direction==1){";
        foreach ($this->DISABLE_ALL as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            $html[]="\t\t\tdocument.getElementById('$ids').disabled=true;";
            $html[]="\t\t}";

        }
        foreach ($this->LABELS as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            $html[]="\t\t\tdocument.getElementById('$ids').className = \"labelform_disabled\";";
            $html[]="\t\t}";
        }
        foreach ($this->CHOSENS as $ids) {
            $html[] = "$('#$ids').prop('disabled',true).trigger('chosen:updated').chosen('destroy').chosen()";
        }
        $form_disabled=$this->javascript_parse_text("{feature_disabled}");
        if(!$LOCKBTS){
            $html[]="\tdocument.getElementById('form-disabled-$this->form_id').className='alert alert-warning alert-dismissable';";
            $html[]="\tdocument.getElementById('form-disabled-$this->form_id').innerHTML='$form_disabled';";
            $html[]="\t\tif(vid.length>0){";
            $html[]="\t\t\tif(document.getElementById(vid)){";
            $html[]="\t\t\t\tdocument.getElementById(vid).disabled=false;";
            $html[]="\t\t\t\t}";
            $html[]="\t\t\t}";
        }
        $html[]="\t}";
        $html[]="\tif(direction==0){";
        reset($this->DISABLE_ALL);
        foreach ($this->DISABLE_ALL as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            $html[]="\t\t\tdocument.getElementById('$ids').disabled=false;";
            $html[]="\t\t}";
        }
        foreach ($this->LABELS as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            $html[]="\t\t\tdocument.getElementById('$ids').className = \"labelform\";";
            $html[]="\t\t}";
        }
        foreach ($this->CHOSENS as $ids) {
            $html[] = "$('#$ids').prop('disabled',false).trigger('chosen:updated').chosen('destroy').chosen()";
        }
        $html[]="\tif( document.getElementById('form-disabled-$this->form_id') ){";
        $html[]="\t\tdocument.getElementById('form-disabled-$this->form_id').style.marginTop='0px';";
        $html[]="\t\tdocument.getElementById('form-disabled-$this->form_id').className='';";
        $html[]="\t\tdocument.getElementById('form-disabled-$this->form_id').innerHTML='';";
        $html[]="\t\t}";
        $html[]="\t}";
        $html[]="}";
        $html[]="$(document).ready(function(){";
        $html[]="\t$('[data-toggle=\"popover\"]').popover({ trigger: 'hover', html: true, });";
        $html[]="});";
        $html[]="// -->  FormStartupScripts";
        $html[]=@implode("\n", $this->FormStartupScripts);
        $html[]="function FormCheckError(){";
        $html[]="\tNoSpinner();";
        $html[]="\tvar zalert=document.getElementById('inside-form-alert').innerHTML;";
        $html[]="\tif (zalert.length>3){";
        $html[]="\t\tdocument.getElementById('inside-form-alert').style.marginTop='6px';";
        $html[]="\t\tdocument.getElementById('inside-form-alert').className='alert alert-warning alert-dismissable';";
        $html[]="\t\tdocument.getElementById('inside-form-alert').innerHTML='<strong style=font-size:18px>'+zalert+'</strong>';";
        $html[]="\treturn;";
        $html[]="\t}";
        $html[]="}";
        $html[]="function Save$this->form_id(){";
        $html[]="\tSave$id();";
        $html[]="}";
        $html[]="FormCheckError();";
        if($LOCKBTS){
            $t=time();
            $html[]="function LockForm{$t}(){";
            $html[]="$"."('#{$this->form_id}').find('input').filter(";
            $html[]="\tfunction(){";
            $html[]="\t\tdocument.getElementById( $(this).attr('id')).disabled=true;";
            $html[]="\t});";
            $html[]="SwitchFormInsteadOf('',1);";
            $html[]="}";
            $html[]="LockForm{$t}();";

        }
        $html[]="\tvar postFormFields$id = document.querySelectorAll('.PostForm');";
        $html[]="\tpostFormFields$id.forEach(function(field) {";
        $html[]="\t\tfield.addEventListener('keydown', function(event) {";
        $html[]="\t\t\tif (event.keyCode === 13) {";
        $html[]="\t\t\t\tSave$id();";
        $html[]="\t\t\t\tif (event.target.tagName === 'INPUT') {  event.preventDefault(); }";
        $html[]="\t\t\t}";
        $html[]="\t\t});";
        $html[]="\t});";
        $html[]="</script>";

        if($this->FORM_IN_ARRAY){
            $html[]="</td>";
            $content=$this->_ENGINE_parse_body(@implode("\n", $html));
            return $content;
        }
        $html[]="";
        $content=$this->_ENGINE_parse_body(@implode("\n", $html));
        return $content;
    }



    public function field_hidden($name,$value){
        if(!is_null($value)) {
            $value = str_replace("\n", "\\" . "\n", $value);
            $value = str_replace("'", "\'", $value);
        }
        $this->XHRHIDDEN[]="XHR.appendData('$name',  encodeURIComponent('$value'));";
        return "<input type='hidden' name='$name' id='$name' value='$value'>";
    }

    public function field_div_section($name,$ajax){
        $id=md5("$name$this->suffixid".time());
        $ReloadFunction="LoadAjax('$id','$ajax')";
        $ReloadFunctionEnc=base64_encode($ReloadFunction);
        $this->JS[]="LoadAjax('$id','$ajax&reload-function=$ReloadFunctionEnc');";
        return $this->_field_intr("TABLE!","\n<!-- DIV SECTION START -->\n<div id='$id' style='width:100%'></div>\n<!-- DIV SECTIOn END -->\n",null);

    }



    public function field_textareacode($name,$label,$value,$explain=null):string{
        $id=md5("$name$this->suffixid");
        $this->XHR[]="editor{$id}.save();";
        $this->DISABLE_ALL[]=$id;
        $width=null;
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(editor{$id}.getValue()));";
        $this->JS[]="var editor{$id};";
        $this->JS[]="function CodeMirror$id(){";
        $this->JS[]="editor{$id} = CodeMirror.fromTextArea(document.getElementById('$id'), { lineNumbers: true, matchBrackets: true, lineWrapping: true,autofocus:true });";
        $this->JS[]="}";
        $this->JS[]="setTimeout ('CodeMirror$id()',800);";

        if($label==null){$width=";width:100%";}
        return $this->_field_intr($label,
            "<textarea id='$id' name='$id' style='min-height:50px;$width' rows='5'>$value</textarea>",$explain);
    }
    public function field_textarea_normal($name,$label,$value=null,$width="100%",$height="450px"):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr2($label,"<textarea id='$id' name='$id' style='width:$width;min-height: {$height};font-size: 15px;'>$value</textarea>");
    }
    public function field_textarea($name,$label,$value=null,$width="100%",$height="450px"):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        $ThisFieldHidden=$this->ThisFieldHidden($id,$value,$height,$width);
        if(preg_match("#^TextareaCert:(.+)#",$label,$re)){
            $height=406;

            if(strlen($value)>1500){
                $height=550;
            }

            if(strlen($value)>1600){
                $height=600;
            }

            if(strlen($value)>2000){
                $height=780;
            }
            return $this->_field_intr2("","<textarea id='$id' name='$id' style='width: 98%;min-height: {$height}px;font-size: 15px;'>$value</textarea>");
        }
        return $this->_field_intr2($label,"<textarea id='$id' name='$id' style='width:$width;height:$height;overflow:auto;visibility:collapse;max-width:640px' class='InForms'>$value</textarea>$ThisFieldHidden");
    }

    private function ThisFieldHidden($id,$value,$height=null,$width=null):string{
        $ClickMouse="onMouseOver=\"this.style.cursor='pointer';this.style.fontWeight='bold';this.style.textDecoration='underline';\" 
        OnMouseOut=\"this.style.cursor='default';this.style.fontWeight='normal';this.style.textDecoration='none';\"";
        $length=strlen( (string) $value);
        if($length>75){
            $sb=explode("\n",$value);
            $bb=array();
            foreach ($sb as $line){
                $bb[]=htmlspecialchars($line);
            }
            $value=@implode("<br>",$bb);
            $value=substr($value,0,75);

            $value=htmlspecialchars($value);

        }
        if($value==null){$value="{no_data}: {click_here}";}
        $htm[]="<div id='associates-$id' class='OutForms' $ClickMouse>$value</div>";
        $htm[]="<script>";
        $htm[]="$('#$id').css('visibility', 'collapse');";
        $htm[] = "$('#$id').css('height', '0');";
        $htm[]="$('#$id').css('width', '0');";
        $htm[]="$('#associates-$id').css('margin-top', '-6px');";
        $htm[]="$('#associates-$id').click(function() {";

         $htm[]="$( '.InForms' ).each(function() {";
         $htm[]="id='associates-'+this.id;";
         $htm[]="value=$('#'+this.id).val();";
         $htm[]="value=value.substring(0,75);";
         $htm[]="$('#'+id).text(value).html();";
         $htm[]="});";
        
        
        $htm[]="$('.OutForms').css('visibility', 'visible');";
        $htm[]="$('.InForms').css('height', '0');";
        $htm[]="$('.InForms').css('width', '0');";
        $htm[]="$('.InForms').css('visibility', 'collapse');";
        $htm[]="$('#associates-$id').css('visibility', 'collapse');";
        $htm[]="$('#$id').css('visibility', 'visible');";
        if($height<>null) {
            $htm[] = "$('#$id').css('height', '$height');";
        }
        if($width<>null) {
            $htm[]="$('#$id').css('width', '$width');";
        }
        $htm[]="} );";
        $htm[]="</script>";
        return @implode(" ",$htm);
    }


    public function field_textareaP($name,$label,$value){
        return $this->field_textarea($name,$label,$value,"100%","125px");

    }

    public function field_info_ERROR($name,$label,$value,$explain=null,$id=null){
        if($id==null){$id=md5("$name$this->suffixid");}
        $button=null;
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        if(is_array($value)){
            $mvalue=$value["VALUE"];
            if($value["BUTTON"]==true){
                if($value["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$value["BUTTON_JS"]}\"";}
                $button="&nbsp;&nbsp;&nbsp;<span class=\"label label-primary\" $pointers $bt_js>{$value["BUTTON_CAPTION"]}...</span></div>";

            }

        }else{
            $mvalue=$value;
        }

        $mvalue=str_replace("'","\'",$mvalue);
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent('$mvalue'));";
        return $this->_field_intr($label,"<label id='$id' class='text-danger'>$mvalue</label>$button",$explain);
    }


    public function field_info($name,$label,$value,$explain=null,$id=null){
        if($id==null){$id=md5("$name$this->suffixid");}
        $button=null;
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
        // = array("VALUE"=>$value,"BUTTON_JS"=>javascript,"BUTTON_CAPTION"=>button name,"BUTTON"=>true)
        //
        if(is_array($value)){
            $mvalue=$value["VALUE"];
            if($value["BUTTON"]==true){
                if($value["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$value["BUTTON_JS"]}\"";}
                $button="&nbsp;&nbsp;&nbsp;<span class=\"label label-primary\" $pointers $bt_js>{$value["BUTTON_CAPTION"]}...</span></div>";

            }

        }else{
            $mvalue=$value;
        }

        $mvalue=str_replace("'","\'",$mvalue);
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent('$mvalue'));";
        return $this->_field_intr($label,"<label id='$id'>$mvalue</label>$button",$explain);
    }


    public function field_interfaces_choose($name,$label,$value,$explain=null,$notnull=false){
        $id=md5("$name$this->suffixid");
        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-interfaces.php?field-id=$id&title=$labeljs')";

        if(preg_match("#^vs:(.+)#",$label,$re)){
            $label=$re[1];
        }

        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{select}...</button> </span>
		</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){

            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);
    }

    public function TheShieldsLicenseStatus():string
    {
        $Val = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos");
        $Val = base64_decode($Val);
        $kInfos = unserialize($Val);
        if (!isset($kInfos["enable"])) {
            $kInfos["enable"] = 0;
        }
        if (!isset($kInfos["status"])) {
            $kInfos["status"] = null;
        }


        if ($kInfos["enable"] == 0 && $kInfos["status"] == null) {
            $btn[0]["name"] = "{activate}";
            $btn[0]["icon"] = "far fa-shield-check";
            $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
            return $this->widget_grey("{license_status}", "{license_invalid}", $btn);
        }
        if ($kInfos["enable"] == 0 && $kInfos["NoLic"] == 1) {
            $btn[0]["name"] = "{activate}";
            $btn[0]["icon"] = "far fa-shield-check";
            $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
            return $this->widget_grey("{license_status}", "{no_license}", $btn);
        }


        if ($kInfos["enable"] == 0) {
            if (intval($kInfos["expire"]) > 0) {
                $reste_days = $this->TimeToDays($kInfos["expire"]);
                if ($reste_days < 1) {
                    $btn[0]["name"] = "{license}";
                    $btn[0]["icon"] = "far fa-shield-check";
                    $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
                    return $this->widget_rouge("{license_status}", "{expired}", $btn);
                }
            }

            $btn[0]["name"] = "{activate}";
            $btn[0]["icon"] = "far fa-shield-check";
            $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
            return $this->widget_grey("{license_status}", "{license_invalid}", $btn);
        }

        if (intval($kInfos["expire"]) > 0) {
            $reste_days = $this->TimeToDays($kInfos["expire"]);

            if ($reste_days < 1) {
                $btn[0]["name"] = "{license}";
                $btn[0]["icon"] = "far fa-shield-check";
                $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
                return $this->widget_rouge("{license_status}", "{expired}", $btn);
            }

            if ($reste_days < 15) {
                $btn[0]["name"] = "{license}";
                $btn[0]["icon"] = "far fa-shield-check";
                $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
                return $this->widget_jaune("{license_status}", "{ExpiresSoon}: $reste_days {days}", $btn);
            }

            if($kInfos["ispaid"]==0) {
                $btn[0]["name"] = "{license}";
                $btn[0]["icon"] = "far fa-shield-check";
                $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
                return $this->widget_jaune("{license_status}", "{active2}/{not_paid_license}", $btn);
            }
        }


        $btn[0]["name"] = "{license}";
        $btn[0]["icon"] = "far fa-shield-check";
        $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
        return $this->widget_vert("{license_status}", "{active2}", $btn);
    }

    public function NGINX_ID_TO_HOSTNAME($ID=0):string{
        if($ID==0){return "{all_websites}";}
        if(isset($GLOBALS["NGINX_ID_TO_HOSTNAME"][$ID])){
            return $GLOBALS["NGINX_ID_TO_HOSTNAME"][$ID];
        }
        $q = new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $sql = "SELECT servicename FROM nginx_services WHERE ID=$ID";
        $ligne=$q->mysqli_fetch_array($sql);
        $GLOBALS["NGINX_ID_TO_HOSTNAME"][$ID]=strval($ligne["servicename"]);
        return $GLOBALS["NGINX_ID_TO_HOSTNAME"][$ID];
    }

    public function NGINX_HOSTNAME_TO_ID($strsearch=null):int{
        $strsearch=trim(strtolower($strsearch));
        if(isset($GLOBALS["NGINX_HOSTNAME_TO_ID"][$strsearch])){return $GLOBALS["NGINX_HOSTNAME_TO_ID"][$strsearch];}
        if($strsearch==null){return 0;}
        $q = new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $sql = "SELECT ID,hosts FROM nginx_services ORDER BY zorder";
        $results=$q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne) {
            $hosts = $ligne["hosts"];
            $ID=intval($ligne["ID"]);
            if (strpos($hosts, "||") > 0) {
                $Zhosts = explode("||", $hosts);
                foreach ($Zhosts as $www) {
                    $www=trim(strtolower($www));
                    if(preg_match("#^(.+?)>(.+)#",$www,$re)){$www=$re[1];}
                    if($www==null){continue;}
                    $GLOBALS["NGINX_HOSTNAME_TO_ID"][$www]=$ID;
                    if ($www == $strsearch){return $ID;}
                }
                continue;
            }
            $www=trim(strtolower($hosts));
            $GLOBALS["NGINX_HOSTNAME_TO_ID"][$www]=$ID;
            if ($www == $strsearch){return $ID;}
        }
        return 0;
    }

    public function
    field_choose_websites($name,$label,$value=null,$explain=null,$notnull=false){
        $id=md5("$name$this->suffixid");
        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-websites.php?field-id=$id&title=$labeljs')";
        if(!is_numeric($value)) { $value=$this->NGINX_HOSTNAME_TO_ID($value);}
        $string_value=$this->NGINX_ID_TO_HOSTNAME($value);
        $F="
        <input type='hidden' id='$id' value='$value'>
        <div class='input-group'><input type='text' class='form-control' id='$id-string' value='$string_value' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{select}...</button> </span>
		</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id-string'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);
    }


    public function nginxHostToInt($sitename){
        $sitename=trim(strtolower($sitename));
        if(!isset($GLOBALS["nginxHostToInt"])){
            $q = new lib_sqlite("/home/artica/SQLITE/nginx.db");
            $sql = "SELECT ID,hosts,servicename FROM nginx_services ORDER BY zorder";
            $results=$q->QUERY_SQL($sql);
            foreach ($results as $index=>$ligne){
                $ID=$ligne["ID"];
                $Hosts=strtolower(trim($ligne["hosts"]));
                if($Hosts==null){continue;}

                if (strpos($Hosts, "||") > 0) {
                    $Zhosts = explode("||", $Hosts);
                    foreach ($Zhosts as $www) {
                        $www=trim($www);
                        VERBOSE("$www == > $ID",__LINE__);
                        $GLOBALS["nginxHostToInt"][$www]=$ID;
                    }
                    continue;
                }
                VERBOSE("$Hosts == > $ID",__LINE__);
                $GLOBALS["nginxHostToInt"][$Hosts]=$ID;
            }
        }
        if(isset($GLOBALS["nginxHostToInt"][$sitename])){return intval($GLOBALS["nginxHostToInt"][$sitename]);}
        VERBOSE("DID NOT FOUND IN [$sitename]",__LINE__);
        return 0;
    }



    public function field_browse_UserAgent($name,$label,$value,$ModeRegex=0,$notnull=false){
        $id=md5("$name$this->suffixid");

        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-usersagents.php?field-id=$id&title=$labeljs&ModeRegex=$ModeRegex')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value'>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }
    public function field_browse_waf_scope($name,$label,$value,$explain=null,$notnull=false){
        $id=md5("$name$this->suffixid");
        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-waf-scope.php?field-id=$id&title=$labeljs')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";



        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);
    }

    public function field_browse_smtp_header($name,$label,$value,$explain=null,$notnull=false){
        $id=md5("$name$this->suffixid");
        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-smtpheaders.php?field-id=$id&title=$labeljs')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value'>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";



        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }

    public function field_browse_directory($name,$label,$value,$rootpath=null,$notnull=false):string{
        $id=md5("$name$this->suffixid");


        $labeljs=urlencode($this->javascript_parse_text($label));
        if(!is_null($rootpath)) {
            $rootpath = urlencode($rootpath);
        }
        $js="Loadjs('fw.browse-directory2.php?field-id=$id&title=$labeljs&basepath=$rootpath')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";



        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){

            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }

    public function field_browse_suffix($name,$label,$value,$explain=null,$idOfServer=null,$idOfPort=null){
        $id=md5("$name$this->suffixid");
        $js="Loadjs('fw.browse-ldap-suffix.php?field-id=$id&idOfServer=$idOfServer&idOfPort=$idOfPort')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value'>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }

    public function field_array_select($HashList,$name,$label,$value,$explain=null,$jsafter=null){
        $id=md5("$name$this->suffixid");
        $id2=md5("{$name}2$this->suffixid");
        $value2=$HashList[$value];

        $Encoded=base64_encode(serialize($HashList));
        $DefaultValue=urlencode($value);
        $jsafter=urlencode(base64_encode($jsafter));
        $labelenc=urlencode($label);
        $page=CurrentPageName();
        $js="Loadjs('fw.browse-select.php?&page=$page&name=$name&field-id=$id&Hash=$Encoded&field-id2=$id2&value=$DefaultValue&js=$jsafter&label=$labelenc')";

        $F="<div class='input-group'>
            <input type='hidden' id='$id' value='$value'> 
            <input type='text' class='form-control' id='$id2' value='$value2' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{select}...</button> </span>
			</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);
    }
    public function field_browse_ldapgroups($name,$label,$value,$explain=null){
        $id=md5("$name$this->suffixid");
        $js="Loadjs('fw.browse-localldap.php?field-id=$id')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
			</div>";
        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }
    public function field_browse_ldap_user($name,$label,$value,$explain=null){
        $id=md5("$name$this->suffixid");
        $js="Loadjs('fw.browse-ldapuser.php?field-id=$id')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
			</div>";
        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }

    public function field_browse_adgroups($name,$label,$value=null,$explain=null){
        $id=md5("$name$this->suffixid");$opts=null;$addon=null;
        if(preg_match("#(.+?):(.+)#",$label,$re)){
            $opts=$re[2];
            $label=$re[1];
        }
        if($opts=="by-dn"){
            $addon="&bydn=yes";
        }
        $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
        if ($EnableExternalACLADAgent==1){
            $js="Loadjs('fw.browse-activedirectory-adagent.php?field-id=$id{$addon}')";
            $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
			</div>";
        }
        else {
            $js="Loadjs('fw.browse-activedirectory.php?field-id=$id{$addon}')";
            $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
			</div>";
        }

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }

    public function field_charset($name,$label,$value,$explain=null){
        $CHARSETS["Arabic (ASMO 708)"]="ASMO-708";
        $CHARSETS["Arabic (DOS)"]="DOS-720";
        $CHARSETS["Arabic (ISO)"]="iso-8859-6";
        $CHARSETS["Arabic (Mac)"]="x-mac-arabic";
        $CHARSETS["Arabic (Windows)"]="windows-1256";
        $CHARSETS["Baltic (DOS)"]="ibm775";
        $CHARSETS["Baltic (ISO)"]="iso-8859-4";
        $CHARSETS["Baltic (Windows)"]="windows-1257";
        $CHARSETS["Central European (DOS)"]="ibm852";
        $CHARSETS["Central European (ISO)"]="iso-8859-2";
        $CHARSETS["Central European (Mac)"]="x-mac-ce";
        $CHARSETS["Central European (Windows)"]="windows-1250";
        $CHARSETS["Chinese Simplified (EUC)"]="EUC-CN";
        $CHARSETS["Chinese Simplified (GB2312)"]="gb2312";
        $CHARSETS["Chinese Simplified (HZ)"]="hz-gb-2312";
        $CHARSETS["Chinese Simplified (Mac)"]="x-mac-chinesesimp";
        $CHARSETS["Chinese Traditional (Big5)"]="big5";
        $CHARSETS["Chinese Traditional (CNS)"]="x-Chinese-CNS";
        $CHARSETS["Chinese Traditional (Eten)"]="x-Chinese-Eten";
        $CHARSETS["Chinese Traditional (Mac)"]="x-mac-chinesetrad";
        $CHARSETS["Cyrillic (DOS)"]="cp866";
        $CHARSETS["Cyrillic (ISO)"]="iso-8859-5";
        $CHARSETS["Cyrillic (KOI8-R)"]="koi8-r";
        $CHARSETS["Cyrillic (KOI8-U)"]="koi8-u";
        $CHARSETS["Cyrillic (Mac)"]="x-mac-cyrillic";
        $CHARSETS["Cyrillic (Windows)"]="windows-1251";
        $CHARSETS["Europa"]="x-Europa";
        $CHARSETS["German (IA5)"]="x-IA5-German";
        $CHARSETS["Greek (DOS)"]="ibm737";
        $CHARSETS["Greek (ISO)"]="iso-8859-7";
        $CHARSETS["Greek (Mac)"]="x-mac-greek";
        $CHARSETS["Greek (Windows)"]="windows-1253";
        $CHARSETS["Greek, Modern (DOS)"]="ibm869";
        $CHARSETS["Hebrew (DOS)"]="DOS-862";
        $CHARSETS["Hebrew (ISO-Logical)"]="iso-8859-8-i";
        $CHARSETS["Hebrew (ISO-Visual)"]="iso-8859-8";
        $CHARSETS["Hebrew (Mac)"]="x-mac-hebrew";
        $CHARSETS["Hebrew (Windows)"]="windows-1255";
        $CHARSETS["IBM EBCDIC (Arabic)"]="x-EBCDIC-Arabic";
        $CHARSETS["IBM EBCDIC (Cyrillic Russian)"]="x-EBCDIC-CyrillicRussian";
        $CHARSETS["IBM EBCDIC (Cyrillic Serbian-Bulgarian)"]="x-EBCDIC-CyrillicSerbianBulgarian";
        $CHARSETS["IBM EBCDIC (Denmark-Norway)"]="x-EBCDIC-DenmarkNorway";
        $CHARSETS["IBM EBCDIC (Denmark-Norway-Euro)"]="x-ebcdic-denmarknorway-euro";
        $CHARSETS["IBM EBCDIC (Finland-Sweden)"]="x-EBCDIC-FinlandSweden";
        $CHARSETS["IBM EBCDIC (Finland-Sweden-Euro)"]="x-ebcdic-finlandsweden-euro";
        $CHARSETS["IBM EBCDIC (France-Euro)"]="x-ebcdic-france-euro";
        $CHARSETS["IBM EBCDIC (Germany)"]="x-EBCDIC-Germany";
        $CHARSETS["IBM EBCDIC (Germany-Euro)"]="x-ebcdic-germany-euro";
        $CHARSETS["IBM EBCDIC (Greek Modern)"]="x-EBCDIC-GreekModern";
        $CHARSETS["IBM EBCDIC (Greek)"]="x-EBCDIC-Greek";
        $CHARSETS["IBM EBCDIC (Hebrew)"]="x-EBCDIC-Hebrew";
        $CHARSETS["IBM EBCDIC (Icelandic)"]="x-EBCDIC-Icelandic";
        $CHARSETS["IBM EBCDIC (Icelandic-Euro)"]="x-ebcdic-icelandic-euro";
        $CHARSETS["IBM EBCDIC (International-Euro)"]="x-ebcdic-international-euro";
        $CHARSETS["IBM EBCDIC (Italy)"]="x-EBCDIC-Italy";
        $CHARSETS["IBM EBCDIC (Italy-Euro)"]="x-ebcdic-italy-euro";
        $CHARSETS["IBM EBCDIC (Japanese and Japanese Katakana)"]="x-EBCDIC-JapaneseAndKana";
        $CHARSETS["IBM EBCDIC (Japanese and Japanese-Latin)"]="x-EBCDIC-JapaneseAndJapaneseLatin";
        $CHARSETS["IBM EBCDIC (Japanese and US-Canada)"]="x-EBCDIC-JapaneseAndUSCanada";
        $CHARSETS["IBM EBCDIC (Japanese katakana)"]="x-EBCDIC-JapaneseKatakana";
        $CHARSETS["IBM EBCDIC (Korean and Korean Extended)"]="x-EBCDIC-KoreanAndKoreanExtended";
        $CHARSETS["IBM EBCDIC (Korean Extended)"]="x-EBCDIC-KoreanExtended";
        $CHARSETS["IBM EBCDIC (Multilingual Latin-2)"]="CP870";
        $CHARSETS["IBM EBCDIC (Simplified Chinese)"]="x-EBCDIC-SimplifiedChinese";
        $CHARSETS["IBM EBCDIC (Spain)"]="X-EBCDIC-Spain";
        $CHARSETS["IBM EBCDIC (Spain-Euro)"]="x-ebcdic-spain-euro";
        $CHARSETS["IBM EBCDIC (Thai)"]="x-EBCDIC-Thai";
        $CHARSETS["IBM EBCDIC (Traditional Chinese)"]="x-EBCDIC-TraditionalChinese";
        $CHARSETS["IBM EBCDIC (Turkish Latin-5)"]="CP1026";
        $CHARSETS["IBM EBCDIC (Turkish)"]="x-EBCDIC-Turkish";
        $CHARSETS["IBM EBCDIC (UK)"]="x-EBCDIC-UK";
        $CHARSETS["IBM EBCDIC (UK-Euro)"]="x-ebcdic-uk-euro";
        $CHARSETS["IBM EBCDIC (US-Canada)"]="ebcdic-cp-us";
        $CHARSETS["IBM EBCDIC (US-Canada-Euro)"]="x-ebcdic-cp-us-euro";
        $CHARSETS["Icelandic (DOS)"]="ibm861";
        $CHARSETS["Icelandic (Mac)"]="x-mac-icelandic";
        $CHARSETS["ISCII Assamese"]="x-iscii-as";
        $CHARSETS["ISCII Bengali"]="x-iscii-be";
        $CHARSETS["ISCII Devanagari"]="x-iscii-de";
        $CHARSETS["ISCII Gujarathi"]="x-iscii-gu";
        $CHARSETS["ISCII Kannada"]="x-iscii-ka";
        $CHARSETS["ISCII Malayalam"]="x-iscii-ma";
        $CHARSETS["ISCII Oriya"]="x-iscii-or";
        $CHARSETS["ISCII Panjabi"]="x-iscii-pa";
        $CHARSETS["ISCII Tamil"]="x-iscii-ta";
        $CHARSETS["ISCII Telugu"]="x-iscii-te";
        $CHARSETS["Japanese (EUC)"]="euc-jp";
        $CHARSETS["Japanese (JIS)"]="iso-2022-jp";
        $CHARSETS["Japanese (JIS-Allow 1 byte Kana - SO/SI)"]="iso-2022-jp";
        $CHARSETS["Japanese (JIS-Allow 1 byte Kana)"]="csISO2022JP";
        $CHARSETS["Japanese (Mac)"]="x-mac-japanese";
        $CHARSETS["Japanese (Shift-JIS)"]="shift_jis";
        $CHARSETS["Korean"]="ks_c_5601-1987";
        $CHARSETS["Korean (EUC)"]="euc-kr";
        $CHARSETS["Korean (ISO)"]="iso-2022-kr";
        $CHARSETS["Korean (Johab)"]="Johab";
        $CHARSETS["Korean (Mac)"]="x-mac-korean";
        $CHARSETS["Latin 3 (ISO)"]="iso-8859-3";
        $CHARSETS["Latin 9 (ISO)"]="iso-8859-15";
        $CHARSETS["Norwegian (IA5)"]="x-IA5-Norwegian";
        $CHARSETS["OEM United States"]="IBM437";
        $CHARSETS["Swedish (IA5)"]="x-IA5-Swedish";
        $CHARSETS["Thai (Windows)"]="windows-874";
        $CHARSETS["Turkish (DOS)"]="ibm857";
        $CHARSETS["Turkish (ISO)"]="iso-8859-9";
        $CHARSETS["Turkish (Mac)"]="x-mac-turkish";
        $CHARSETS["Turkish (Windows)"]="windows-1254";
        $CHARSETS["Unicode"]="unicode";
        $CHARSETS["Unicode (Big-Endian)"]="unicodeFFFE";
        $CHARSETS["Unicode (UTF-7)"]="utf-7";
        $CHARSETS["Unicode (UTF-8)"]="utf-8";
        $CHARSETS["US-ASCII"]="us-ascii";
        $CHARSETS["Vietnamese (Windows)"]="windows-1258";
        $CHARSETS["Western European (DOS)"]="ibm850";
        $CHARSETS["Western European (IA5)"]="x-IA5";
        $CHARSETS["Western European (ISO)"]="iso-8859-1";
        $CHARSETS["Western European (Mac)"]="macintosh";
        $CHARSETS["Western European (Windows)"]="Windows-1252";
        $CF=array();
        foreach ($CHARSETS as $zexplain=>$key){
            $CF[strtoupper($key)]=$zexplain;
        }
        if($explain==null or is_numeric($explain)){
            $explain="$value (".$CF[strtoupper($value)].")";
        }
        return $this->field_array_hash($CF,$name,$label,$value,false,$explain);

    }


    public function field_schedule($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $each=$this->javascript_parse_text("{each}");
        $day=$this->javascript_parse_text("{day}");
        $Hours=$this->javascript_parse_text("{hours}");
        $saturday=$this->javascript_parse_text("{saturday}");
        $sunday=$this->javascript_parse_text("{sunday}");
        $minutes=$this->javascript_parse_text("{minutes}");
        $F[]="<div id='Sched$id'></div><input type='hidden' id='$id' value='$value'>
		<script>
		     $('#Sched$id').cron({";


        $F[]="customValues: {";
        $F[]="\"2 $minutes\" : \"0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * *\",";
        $F[]="\"2 $minutes (0-20)\" : \"0,2,4,6,8,10,12,14,16,18,20,22 * * *\",";
        $F[]="\"3 $minutes\" : \"3,6,9,11,13,15,17,19,21,23,25,27,29,31,33,35,37,39,41,43,45,47,49,51,55,57,59 * * * *\",";
        $F[]="\"5 $minutes\" : \"*/5 * * * *\",";
        $F[]="\"5 $minutes start at 2mn\" : \"2,7,12,17,22,27,32,37,42,47,52,57 * * * *\",";
        $F[]="\"5 $minutes start at 5mn\" : \"5,10,15,20,25,30,35,40,45,50,55,59 * * * *\",";
        $F[]="\"5 $minutes start at 7mn\" : \"7,22,37 * * * *\",";

        $F[]="\"$each 15 $minutes\" : \"0,15,30,45 * * * *\",";
        $F[]="\"$each 05,20,35 $minutes\" : \"5,20,35 * * * *\",";
        $F[]="\"$each 00,15,30 $minutes\" : \"0,15,30 * * * *\",";
        $F[]="\"$each 10 $minutes\" : \"10,20,30,40,50 * * * *\",";
        $F[]="\"$each 30 $minutes\" : \"0,30 * * * *\",";
        $F[]="\"$each $day 4h30 \" : \"30 4,8,12,16,20 * * *\",";
        $F[]="\"$each $day 23h15 \" : \"15 23 2,4,6,8,10,12,14,16,18,20,22,24,26,28,30 * *\",";
        $F[]="\"non-productive hours\" : \"30 3,6,12,18,22 * * *\",";
        $F[]="\"$each 2 $Hours $each $saturday/$sunday\" : \"0 */2 * * 5,6\",";
        $F[]="\"$each 2 $Hours $each $day\" : \"0 */2 * * *\",";
        $F[]="\"$each 3 $Hours $each $day\" : \"0 0,3,5,7,9,11,13,15,17,19,23 * * *\",";
        $F[]="\"3 $Hours 14 $minutes\" : \"14 0,3,6,9,12,15,18,21 * * *\",";
        $F[]="\"4 $Hours 00 $minutes\" : \"0 4,8,12,16,20 * * *\",";
        $F[]="\"5 $Hours 30 $minutes\" : \"30 0,5,10,15,20 * * *\"";
        $F[]="},";

        if($value<>null){    $F[]="initial: \"$value\",";}

        $F[]=" onChange: function() {
            	document.getElementById('$id').value=$(this).cron('value')
            },
            useGentleSelect: true
        });
		</script>
		";
        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,@implode("\n", $F),$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,@implode("\n", $F),$explain);
    }

    public function GlancesInfos():array{
        $EnableGlances  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
        if($EnableGlances==0) {return array();}
        $glances_dump   = $this->glances_dump();
        if(!isset($glances_dump)){return array();}
        if (!property_exists($glances_dump, "cpu")) {return array();}
        $cpu            = $glances_dump->cpu->total;
        $mem            = $glances_dump->mem->percent;
        $mem_total      = FormatBytes($glances_dump->mem->total / 1024);

        return array(
            "cpu"=>$cpu,"mem"=>$mem,"mem_total"=>$mem_total

        );
    }

    public function GlancesTop(){
        $EnableGlances  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
        if($EnableGlances==0) {return null;}
        $glances_dump   = $this->glances_dump();
        if(!is_object($glances_dump)){return null;}
        if (!property_exists($glances_dump, "cpu")) {return null;}
        $cpu            = $glances_dump->cpu->total;
        $mem            = $glances_dump->mem->percent;
        $mem_total      = FormatBytes($glances_dump->mem->total / 1024);
        $mem_color      = null;
        $cpu_color      = null;

        if ($mem > 70) {
            $mem_color = "text-info";
        }
        if ($mem > 80) {
            $mem_color = "text-warning";
        }
        if ($mem > 90) {
            $mem_color = "text-danger";
        }

        if ($cpu > 70) {
            $cpu_color = "text-info";
        }
        if ($cpu > 80) {
            $cpu_color = "text-warning";
        }
        if ($cpu > 90) {
            $cpu_color = "text-danger";
        }

        $f[]='$("#top-cpu").html("<span class='.$cpu_color.'>Cpu:'.$cpu.'%</span>");';
        $f[]='$("#top-memory").html("<span class='.$mem_color.'>Mem:'."{$mem}%/{$mem_total}".'</span>");';
        return @implode("\n",$f);
    }


    public function glances_dump(){
        if(!function_exists("curl_init")){return array();}
        if(!function_exists("json_decode")){return array();}
        $GLANCES_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GLANCES_VERSION");
        preg_match("#^([0-9]+)\.#",$GLANCES_VERSION,$re);
        $v=2;
        if(intval($re[1])>2){$v=3;}

        $uri="http://127.0.0.1:61208/api/$v/all";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_URL, "$uri");
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        $dataIndex=curl_exec($ch);
        $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if($GLOBALS["VERBOSE"]){echo "<strong>$uri: $CURLINFO_HTTP_CODE</strong><br>\n";}

        return json_decode($dataIndex);
    }

    public function page_header($title,$ico,$explain=null,$js=null,$url=null,$force_progress=null,$searchbox=false,$forceloadingdiv=null):string{
        $t=time();$additional_progress=null;$jstiny=null;
        $iddiv="table-$t";
        if($forceloadingdiv<>null){$iddiv=$forceloadingdiv;}
        $jsH="LoadAjax('$iddiv','$js');";
        $title_text=$this->javascript_parse_text($title);
        if(!isset($_GET["tiny-page"])){

            if($title<>null) {
                $html[] = "	<div class=\"row border-bottom white-bg dashboard-header\">";
                $html[] = "<table style='width:100%'>";
                $html[] = "<tr>";
                if(preg_match("#\.png$#",$ico)){
                    $html[] = "<td style='vertical-align:top;padding-right: 10px;width:1%' nowrap>
                    <div id='tiny-ico'>
                    <img src='img/$ico' alt=\"\">
                    </div>
                    </td>";
                }else {
                    $html[] = "<td style='vertical-align:top;padding-right: 10px;width:1%' nowrap>
                        <div id='tiny-ico'>
                            <i class='fa-8x $ico'></i>
                       </div> 
                       </td>";
                }
                $html[] = "<td style='vertical-align:top;'><div class=\"col-sm-12\" style='margin-top:-26px'>";
                $html[]="<h1 class=ng-binding>";
                $html[]="<div id='tiny-title'>$title</div></h1>";
                $html[] = "<p>";

                if ($explain <> null) {

                    $html[]="<span id='tiny-explain'>$explain</span>
                        <div id='tiny-button'></div>
                        ";
                }else{
                    $explain="<span id='none'></span>
                    <span id='tiny-explain'></span>
                    <div id='tiny-button'></div>";
                }
                $html[] = "</p>";
                $html[] = "</div></td>";
                $html[] = "</tr>";
                $html[] = "</table>";
                $html[] = "</div>";

            }
        }
        if(isset($_GET["tiny-page"])){
            $TINY_ARRAY["TITLE"]=$title;
            $TINY_ARRAY["ICO"]=$ico;
            $TINY_ARRAY["EXPL"]=$explain;
            $TINY_ARRAY["URL"]=$url;
            $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

        }

        $html[]="<div class='row'>";
        $html[]="<div id='progress-$t'></div>";

        if(strpos($force_progress,",")>0){
            $tb=explode(",",$force_progress);
            $force_progress=$tb[0];
            $additional_progress=$tb[1];
        }

        if($force_progress<>null){
            $html[]="<div id='$force_progress' style='margin-left:15px'></div>";
        }
        if($additional_progress<>null){
            $html[] = "<div id='$additional_progress' style='margin-left:15px'></div>";
        }
        $animatedDiv="ibox-content";
        $html[]="<div class='ibox-content' style='min-height:600px'>";
        if($searchbox){
            $jsH=null;
            $urlToadd=null;
            $page=CurrentPageName();
            if(!is_bool($searchbox)){
                $urlToadd=$searchbox;
                if(strpos($urlToadd,0,1)<>"&"){$urlToadd="&$urlToadd";}
            }
            $html[]=$this->search_block($page,"file",null,null,$urlToadd,$js);
        }else {
            $animatedDiv="$iddiv";
            $html[] = "<div id='$iddiv'></div>";
        }

        $html[]="</div>";
        $html[]="</div>";
        $html[]="<script>";
        $BrowserItem["title"]=$title_text;

        if($url<>null) {
            $BrowserItem["url"]=$url;
        }
       
        if($js<>null) {
            $html[] = "$jsH";
        }
        if($jstiny<>null){
            $html[]=$jstiny;
        }

        $html[]="Loadjs('fw.progress.php?browser-items=".base64_encode(serialize($BrowserItem))."');";
        
        $t=time();


        $divs[]="tiny-ico:flip";

        $t=time();

        $html[] = "function Animated$t(){";
        foreach ($divs as $animatedDiv) {
            $tt=explode(":",$animatedDiv);
            $xdiv=$tt[0];
            $animation=$tt[1];
            $html[] = "if(document.getElementById('$xdiv')){";
            $html[] = "$('#$xdiv').removeAttr('class').attr('class', '');";
            $html[] = "$('#$xdiv').addClass('animated');";
            $html[] = "$('#$xdiv').addClass('$animation');";
            $html[] = "}\n";
        }
        $html[] = "}\n";


        $html[]="Animated$t();";
        $html[]="</script>";
        $html_final=@implode("\n",$html);

        if($searchbox){
            $html_final=str_replace("%sfunction",$this->search_box_function,$html_final);

        }
        return $html_final;

    }

    public function field_acls_groups($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $TableLink=null;
        if(isset($_GET["TableLink"])) {
            $TableLink = $_GET["TableLink"];
        }
        if($TableLink==null){$TableLink="webfilters_sqacllinks";}
        $function="";
        if(isset($_GET["function"])){$function=$_GET["function"];}
        if(!isset($_GET["ProxyPac"])){$_GET["ProxyPac"]=0;}
        if(!isset($_GET["DnsDist"])){$_GET["DnsDist"]=0;}
        if(!isset($_GET["firewall"])){$_GET["firewall"]=0;}
        $ProxyPac=intval($_GET["ProxyPac"]);
        $DnsDist=intval($_GET["DnsDist"]);
        $firewall=intval($_GET["firewall"]);
        $fastacls=intval($_GET["fastacls"]);
        $F="<div class='input-group'>
		<input type='text' id='$id' class='form-control' disabled> <span class='input-group-btn'> <button 
			class='btn btn-primary' 
			type='button' 
			OnClick=\"Loadjs('fw.proxy.acls.new-object.php?id=$id&ProxyPac=$ProxyPac&DnsDist=$DnsDist&firewall=$firewall&TableLink=$TableLink&fastacls=$fastacls&function=$function');\">{select}</button> </span>
		</div>";

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);

    }

    public function field_choose_adgroupDN($name,$label=null,$value=null,$explain=null){

        return $this->field_activedirectorygrp($name,$label,$value,false,$explain,false,true);
    }


    public function field_ldap_group($name,$label,$value,$notnull=false,$explain=null){
        $ldap = new clladp();
        $ou=null;
        $EnableMultipleOrganizations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));

        if( $EnableMultipleOrganizations==0){
            if($EnableMultipleOrganizations==0) {
                $hash = $ldap->hash_get_ou(false);
                $ou=$hash[0];
            }
        }

        $groups=$ldap->hash_groups($ou,1);
        return $this->field_array_hash($groups,$name,$label,$value,$notnull,$explain);


    }

    public function field_remote_ldap_groups($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        include_once(dirname(__FILE__)."/class.ldap-extern.inc");
        $ldap=new ldap_extern();
        $SearchGroups=$ldap->SearchGroups("*");
        foreach ($SearchGroups as $DN=>$GroupName){
            $DN=base64_encode($DN);
            $Key="ExtLDAP:$GroupName:$DN";
            $HASH[$Key]=$GroupName;

        }
        return $this->field_array_hash($HASH,$name,"nonull:$label",$value,$notnull,$explain);

    }

    public function field_activedirectory_cnxs($name,$label,$value,$explain=null):string{
        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}

        foreach ($ActiveDirectoryConnections as $index=>$ligne) {
            if (!is_numeric($index)) {continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}
            if (!isset($ligne["LDAP_PORT"])) {$ligne["LDAP_PORT"] = 389;}
            if (!isset($ligne["LDAP_SSL"])) {$ligne["LDAP_SSL"] = 0;}
            if($ligne["LDAP_SSL"]==1){$ligne["LDAP_PORT"]=636;}
            $hash[$index]="{$ligne["LDAP_SERVER"]}:{$ligne["LDAP_PORT"]}";
        }

        return $this->field_array_hash($hash,$name,"nonull:$label",$value,true,$explain);
    }


    public function field_activedirectorygrp($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$UseDN=false){
        $ext=null;if($UseDN){$ext="&UseDN=1";}
        $id=md5("$name$this->suffixid");
        $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$EnableActiveDirectoryFeature=0;}

        if($EnableActiveDirectoryFeature==0){
            $F="<div class='text-danger'>{disabled}</div>";
            $value=str_replace("'","\'",$value);
            $this->XHR[]="XHR.appendData('$name',encodeURIComponent('$value'));";
            return $this->_field_intr($label,$F,$explain);
        }

        $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
        if ($EnableExternalACLADAgent==1){
            $F = "<div class='input-group'>
		<input type='text' id='$id' class='form-control' value='$value' disabled> <span class='input-group-btn'> <button
		class='btn btn-primary'
		type='button'
		OnClick=\"Loadjs('fw.BrowseActiveDirectoryGroups.ad.agent.php?id=$id$ext');\">{browse}...</button> </span>
		</div>";
        }
        else {
            $F = "<div class='input-group'>
		<input type='text' id='$id' class='form-control' value='$value' disabled> <span class='input-group-btn'> <button
		class='btn btn-primary'
		type='button'
		OnClick=\"Loadjs('fw.BrowseActiveDirectoryGroups.php?id=$id$ext');\">{browse}...</button> </span>
		</div>";
        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);


    }


    public function td_button($label,$bt_label, $bt_js=null,$value=null,$explain=null,$labelstyle="label-primary"){
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        if($bt_js<>null){$bt_js="OnClick=\"$bt_js\"";}


        $button="&nbsp;&nbsp;&nbsp;<span class=\"label $labelstyle\" $pointers $bt_js>{$bt_label}...</span>";

        $F="<label>$value</label>$button
		
		";


        return $this->_ENGINE_parse_body($this->_field_intr($label,$F,$explain));

    }

    public function table_td1prc_Left($text_class=null):string{
        return "width=1% class='$text_class' style='vertical-align:middle;text-align:left' nowrap";
    }

    public function table_td1prc($text_class=null):string{
        return "width=1% class='$text_class center' style='vertical-align:middle' nowrap";
    }
    public function table_td1prcLeft($text_class=null):string{
        return "width=1% class='$text_class' style='vertical-align:middle;text-algign:left' nowrap";
    }

    public function table_tdfree($text_class=null):string{
        return "class='$text_class' style='vertical-align:middle'";
    }

    public function table_head($heads=array(),$id=null):string{
    $f[]="<table class=\"table table-hover\" id='$id' style='margin-top:10px'>";
        $f[]="<thead>";
        $f[]="<tr>";
        foreach ($heads as $head){
            $f[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$head</th>";
        }
        $f[]="</tr>";
        $f[]="</thead>";
        return $this->_ENGINE_parse_body(@implode("\n",$f));

    }

    public function th_buttons_label($caption,$js,$icon_class,$security=null,$label_class=null):string{
        if($label_class==null){
            $label_class="btn";
        }

        if($security<>null){
            if(!$this->IsSecurity($security)){
                $label_class="btn";
                $js="blur();";
            }
        }

        return "<label class=\"btn btn-xs $label_class\" OnClick=\"$js;\" style='margin-top:2px'>
                <i class='$icon_class'></i> $caption </label>";

    }

    public function th_buttons($topbuttons=array()):string{

        $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
        $colors[]="btn-primary";
        $colors[]="btn-warning";
        $colors[]="btn-info";
        $colors[]="btn-success";
        $i=0;



        foreach ($topbuttons as $ARRAY){
            if(!isset($ARRAY[2])){continue;}
            $Perms      = null;
            $class      = $colors[$i];
            $jsrestart  = $ARRAY[0];
            $icon       = $ARRAY[1];
            $label      = $ARRAY[2];
            if(isset($ARRAY[3])){
                if($ARRAY[3]=="OFF"){
                    $class="btn-default";
                }else {
                    $Perms = strval($ARRAY[3]);
                }
            }

            if($icon=="fa fa-trash"){
                $class="label-danger";
                $i=$i-1;
            }
            if($icon==ico_ban){
                $class="label-danger";
                $i=$i-1;
            }
            if(preg_match("#(OFF|\{disabled\}|\{inactive2\})#",$label)){
                $class="btn-default";
            }
            if(strlen($jsrestart)==0){
                $class="btn-default";
            }
            if($Perms=="green"){
                $class="btn-primary";
            }

            $html[]=$this->th_buttons_label($label,$jsrestart,$icon,$Perms,$class);
            $html[]="";
            $i++;
            if($i==3){$i=0;}
        }
        $html[]="</div>";
        return $this->_ENGINE_parse_body($html);
    }

    public function table_buttons($topbuttons=array(),$security=null):string{
        $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
        $colors[]="btn-primary";
        $colors[]="btn-warning";
        $colors[]="btn-info";
        $colors[]="btn-blue";
        $colors[]="btn-primary";
        $colors[]="btn-warning";
        $colors[]="btn-info";
        $colors[]="btn-blue";
        $colors[]="btn-primary";
        $colors[]="btn-warning";
        $colors[]="btn-info";
        $colors[]="btn-success";
        $i=0;$btnxs=null;

        if(isset($topbuttons["SMALL"])){
            $btnxs=" btn-xs";
            unset($topbuttons["SMALL"]);
        }

        if(is_array($topbuttons)) {
            foreach ($topbuttons as $ARRAY) {


                if (!isset($ARRAY[2])) {
                    continue;
                }
                $Perms = null;
                if (!isset($colors[$i])) {
                    $i = 0;
                }
                $class = $colors[$i];
                $jsrestart = $ARRAY[0];
                $icon = $ARRAY[1];
                $label = $ARRAY[2];
                if (isset($ARRAY[3])) {
                    $Perms = strval($ARRAY[3]);
                }

                if ($icon == "fa fa-trash") {
                    $class = "btn-danger";
                }
                if (preg_match("#red:(.+)#", $label, $re)) {
                    $label = $re[1];
                    $class = "btn-danger";
                }

                if ($Perms <> null) {
                    if (!$this->IsSecurity($Perms)) {
                        VERBOSE("Perms FALSE = $Perms", __LINE__);
                        $jsrestart = "blur()";
                        $class = "bt-default";
                    }
                }
                if (strlen($jsrestart) < 2) {
                    $jsrestart = "blur()";
                    $class = "bt-default";
                }

                $html[] = "<label class=\"btn btn $class$btnxs\" OnClick=\"$jsrestart\">";
                $html[] = "\t<i class='$icon'></i> $label";
                $html[] = "</label>";
                $html[] = "";
                $i++;
                if (!isset($colors[$i])) {
                    $i = 0;
                }
            }
        }

        $Addon=null;
        if(isset($topbuttons["SUB"])){
            $i++;
            if(!isset($colors[$i])){$i=0;}
            $class      = $colors[$i];
            $Addon=$this->button_dropdown_table("{options}",$topbuttons["SUB"],$security,$class);
        }

        $html[]=$Addon;

        $html[]="</div>";
        return $this->_ENGINE_parse_body($html);
    }

    public function table_footer($id,$rows,$search=true):string{
        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='$rows'>";
        $html[]="<ul class='pagination pull-right'></ul>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</tfoot>";
        $html[]="</table>";
        $html[]=$this->table_footable_footer($id,$search);
        return @implode("\n",$html);
    }

    public function table_footable_footer($id,$search=true):string{
        $html[]="<script>";
        $html[]="NoSpinner();";
        $html[]=@implode("\n",$this->ICON_SCRIPTS);
        $html[]="$(document).ready(function() { ";
        $html[]="\t$('#$id').footable( {";
        if($search) {
            $html[] = "\"filtering\": { \"enabled\": true },";
        }else{
            $html[] = "\"filtering\": { \"enabled\": false },";
        }

        $html[]="\"sorting\": { \"enabled\": true";
        $html[]="},\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
        $html[]="</script>";
        return @implode(" ",$html);
    }


    public function search_block($pageToPost,$type="postgres",$table=null,$idToFill=null,$urlToadd=null,$options=null):string{
        $second_button=null;
        $t=time();
        $value=null;
        $width=null;
        $DropDownFinal="";
        if(!is_null($urlToadd)) {
            $urlToadd = str_replace("%s", "ss$t", $urlToadd);
        }

        if(is_array($options)){
            if(isset($options["WIDTH"])){
                $width="width:{$options["WIDTH"]}px;";
            }

            if(isset($options["WRENCH"])){
                $optionsZ=str_replace("%s","ss$t",$options["WRENCH"]);
                $second_button="<button style=\"text-transform: capitalize;\" class=\"btn btn -default\" type=\"button\" OnClick=\"$optionsZ;\"><i class='fa fa-wrench'></i></button>";
            }
            if(isset($options["UPLOAD"])){
                $ico=ico_upload;
                $optionsZ=str_replace("%s","ss$t",$options["UPLOAD"]);
                $second_button="<button style=\"text-transform: capitalize;\" class=\"btn btn -default\" type=\"button\" OnClick=\"$optionsZ;\"><i class='$ico'></i></button>";
            }

            if(isset($options["DROPDOWN"])){
                $DropDown[]="<div class=\"input-group-btn\">";
                $DropDownTitle=$options["DROPDOWN"]["TITLE"];
                $DropDown[]="<button data-toggle=\"dropdown\" class=\"btn btn-white dropdown-toggle\" type=\"button\" aria-expanded=\"true\"><span id='SearchBlockDropDownTitle'>$DropDownTitle</span> <span class=\"caret\"></span></button>";
                $DropDown[]="<ul class=\"dropdown-menu pull-right\">";
                foreach ($options["DROPDOWN"]["CONTENT"] as $title=>$js){
                    $js=str_replace("%s","ss$t",$js);
                    $DropDown[]="<li><a href=\"#\" onclick=\"$js\"\">$title</a></li>";
                }
                $DropDown[]="</ul>";
                $DropDown[]="</div>";
                $DropDownFinal=@implode("\n",$DropDown);
            }
            $options="";
        }


        if($options<>null){
            $options=str_replace("%s","ss$t",$options);
            $second_button="<button style=\"text-transform: capitalize;\" class=\"btn btn -default\" type=\"button\" OnClick=\"$options;\"><i class='fa fa-wrench'></i></button>";
        }

        if(!is_null($idToFill)) {
            if (preg_match("#value=(.+)#", $idToFill, $re)) {
                $value = $re[1];
            }
        }

        $types=null;
        $id=md5("$pageToPost,$t");
        $addPattrn = null;
        $this->search_box_function="Search$t";
        VERBOSE("search_block(): Type: $type...", __LINE__);
        $search_text=$this->_ENGINE_parse_body("{search}");

        $html[]="<div class=\"row\" style='margin-right: 0;margin-left: 0;'>
		<div class='ibox-content' style='border:0;padding-top:0;'>
			<div class=\"input-group\" style='$width'>
				<input type=\"text\" class=\"form-control\" id='$id' value=\"$value\" placeholder=\"$search_text\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
				$DropDownFinal
				<span class=\"input-group-btn\">
					<button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
					
					$second_button
				</span>
				
			</div>
			<div id='results-$id' style='margin-top:10px'></div>
		</div></div>";


        $availableTags=array();
        if($table<>null) {
            $tags[] = "LIKE";
            $tags[] = "MAX";
            $tags[] = "AND";
            $tags[] = "=";
            $tags[] = ">";
            $tags[] = "< ";
            $tags[] = "IPADDRLIKE ";

            if ($type == "postgres") {
                $q = new postgres_sql();
                $tags = $q->FIELDS_LIST($table, $tags);

            }

            if (preg_match("#sqlite:(.+)$#", $type, $re)) {
                $addPattrn = "&QueryIsSqlite=yes";
                VERBOSE("sqlite database {$re[1]}...", __LINE__);
                $q = new lib_sqlite($re[1]);
                $tags = $q->FIELDS_LIST($table, $tags);
                $types=base64_encode(serialize($q->FIELDS_TYPES($table)));

            }

            if ($type == "mysql_green") {
                VERBOSE("mysql_green database...", __LINE__);
                $q = new green_sql();
                $tags = $q->FIELDS_LIST($table, $tags);
                $addPattrn = "&QueryIsMySQL=yes";

            }


            foreach ($tags as $line) {
                $availableTags[] = "\"$line\"";
            }
        }
        $VaravailableTags=@implode(",", $availableTags);
        $html[]="<script>";

        $html[]="$( function() {";
        $html[]="var availableTags = [$VaravailableTags];";
        $html[]="function split( val ) {";
        $html[]="return val.split( / / );";
        $html[]="}";
        $html[]="function extractLast( term ) {";
        $html[]="return split( term ).pop();";
        $html[]="}";
        $html[]="$( '#$id' )";
        $html[]=".on( 'keydown', function( event ) {";
        $html[]="if ( event.keyCode === $.ui.keyCode.TAB &&";
        $html[]="$( this ).autocomplete( 'instance' ).menu.active ) {";
        $html[]="event.preventDefault();";
        $html[]="}";
        $html[]="})";
        $html[]=".autocomplete({";
        $html[]="minLength: 0,";
        $html[]="source: function( request, response ) {";
        $html[]="// delegate back to autocomplete, but extract the last term";
        $html[]="response( $.ui.autocomplete.filter(";
        $html[]="availableTags, extractLast( request.term ) ) );";
        $html[]="},";
        $html[]="focus: function() {";
        $html[]="// prevent value inserted on focus";
        $html[]="return false;";
        $html[]="},";
        $html[]="select: function( event, ui ) {";
        $html[]="var terms = split( this.value );";
        $html[]="// remove the current input";
        $html[]="terms.pop();";
        $html[]="// add the selected item";
        $html[]="terms.push( ui.item.value );";
        $html[]="// add placeholder to get the comma-and-space at the end";
        $html[]="terms.push( ' ' );";
        $html[]="this.value = terms.join( ' ' );";
        $html[]="return false;";
        $html[]="}";
        $html[]="});";
        $html[]="} );";
        $html[]="function Search$t(e){";
        $html[]="\tif(!checkEnter(e) ){return false;}";
        $html[]="\tss$t();";
        $html[]="}";
        $html[]="";
        $html[]="function ss$t(){";
        $html[]="\tvar ss=encodeURIComponent(document.getElementById('$id').value);";
        $html[]="\tLoadAjaxTiny('results-$id','$pageToPost?search='+ss+'$urlToadd&function=ss$t&$addPattrn&types=$types&div-id=results-$id');";
        $html[]="}";

        $html[]="function Start$t(){";
        $html[]="\tss$t();";
        $html[]="}";

        $html[]="setTimeout (\"Start$t()\", 800);";
        $html[]="</script>";
        return strval(@implode("\n", $html));

    }

    public function percentage_block($title=null,$percent=0,$subtext=null){
        $f[]="<div class=\"ibox-content\">";
        $f[]="\t<h5>{$title}</h5>";
        $f[]="\t<h2>{$percent}%</h2>";
        $f[]="\t<div class=\"progress progress-mini\">";
        $f[]="\t\t<div class=\"progress-bar\" style=\"width: {$percent}%;\"></div>";
        $f[]="\t</div>";
        $f[]="";
        $f[]="\t<div class=\"m-t-sm small\">$subtext</div>";
        $f[]="</div>";

        return @implode("\n",$f);
    }

    public function field_link($label=null,$explain=null,$js=null):string{

        $bnts=array();
        if(is_array($label)){

            foreach ($label as $index=>$text){
                $button_js=$js[$index];
                $bnts[]="<button data-toggle=\"button\" OnClick=\"$button_js\" 
                            class=\"btn btn-primary btn-outline\" type=\"button\" style='margin-left:5px;'>{$text}</button>";
            }

            if($this->FORM_IN_ARRAY){return "<td>".@implode("&nbsp;&nbsp;",$bnts)."</td>";}

            return "
		<tr>
		<td style='width:30%;text-align:left;vertical-align:middle;padding-bottom:10px;' nowrap>&nbsp;</span></td>
		<td style='width:70%;padding-bottom:10px;padding-left:19px;text-align:right'>".@implode("",$bnts)."
		</td>
		</tr>";

        }


        $id=md5("$label,$explain,$js".time());
        $zlabel[]="<a href=\"javascript:$js\"";

        if($explain<>null){
            $explain=$this->_ENGINE_parse_body($explain);
            $explain=str_replace('"', "`", $explain);

            $zlabel[]="data-toggle=\"popover\"";
            $zlabel[]="data-placement=\"auto right\"";
            $zlabel[]="data-content=\"$explain\"";


        }

        $zlabel[]=">$label</a>";


        if($this->FORM_IN_ARRAY){return "<td>
		<button data-toggle=\"button\" OnClick=\"$js\" class=\"btn btn-primary btn-outline\" type=\"button\">{$label}</button></td>";}


        return "
		<tr>
		<td style='width:30%;text-align:left;vertical-align:middle;padding-bottom:10px;' nowrap>&nbsp;</span></td>
		<td style='width:70%;padding-bottom:10px;padding-left:19px;text-align:right'><span class=labelform id='$id'>
		<button data-toggle=\"button\" OnClick=\"$js\" class=\"btn btn-primary btn-outline\" type=\"button\">{$label}</button>
		</span></td>
		</tr>
		";
    }

    public function field_email($name,$label,$value,$notnull=false,$explain=null,$disabled=false):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if(!is_null($value)) {
            $value = str_replace('"', '\"', $value);
        }
        $F="<input type='email' name=\"$name\" placeholder='user@domain.tld' id='$id' class='form-control' value=\"$value\">";

        if($disabled){$notnull=false;}



        $this->XHR[]="var email=document.getElementById('$id').value;";


        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="if (!emailIsValid(email)) {  FieldHTMLError('$id');document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> Invalid email address</div>';return;}";
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);

    }
    public function field_button_upload($label=null,$field="file-uploaded",$bt_name=null,$explain=null):string{
        $page=CurrentPageName();
        $F=$this->button_upload($bt_name,$page,null,null,$field);
        return $this->_field_intr($label,$F,$explain);

    }



    public function field_button($label,$bt_name,$js,$explain=null):string{

        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
        $bt_js="OnClick=\"$js\"";
        $button="<span class=\"label label-primary\" $pointers $bt_js>$bt_name</span>";
        $F="
        <div class='input-group'>
       $button
        </div>";

        return $this->_field_intr($label,$F,$explain);

    }

    public function field_none_bt($name,$label,$value,$BtName,$js=null,$explain=null){
        $id=md5("$name$this->suffixid");
        if($js<>null){$js="OnClick=\"$js\"";}

        $F="
        <div class='input-group'>
        <input type='text' class='form-control' id='$id' value='$value' disabled>
        <span class='input-group-btn'>
        <button type='button' class='btn btn-primary' $js>$BtName</button>
        </span>
        </div>";

        return $this->_field_intr($label,$F,$explain);

    }

    public function field_ad_suffix($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        $value=str_replace('"', '\"', $value);
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $labeljs=$this->javascript_parse_text($label_text);

        $js="Loadjs('fw.browse-suffix.php?js=yes&field-id=$id&title=$labeljs&suffixid=$this->suffixid')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='$value' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);


    }

    public function sshurl_toarray($string):array{
        $port       = 22;
        $username   = null;
        $password   = null;

        if($string==null){
            return array("HOST"=>null,"PORT"=>22,"USER"=>null,"PASS"=>null);
        }

        if(preg_match("#ssh:\/\/(.+?)@(.+?)$#",$string,$re)){
            $hostname=$re[2];
            $username=$re[1];

            if(strpos($username,":")>0){
                $tbhost=explode(":",$username);
                $username = $tbhost[0];
                $password = $tbhost[1];
            }


            if(strpos($hostname,":")>0){
                $tbhost=explode(":",$hostname);
                $hostname   = $tbhost[0];
                $port       = $tbhost[1];
            }

            return array("HOST"=>$hostname,"PORT"=>$port,"USER"=>$username,"PASS"=>$password);

        }

        if(preg_match("#ssh:\/\/(.+)$#",$string,$re)){
            $hostname=$re[1];
            if(strpos($hostname,":")>0){
                $tbhost=explode(":",$hostname);
                $hostname=$tbhost[0];
                $port=intval($tbhost[1]);
            }
            return array("HOST"=>$hostname,"PORT"=>$port,"USER"=>$username,"PASS"=>$password);
        }
        return array();
    }
    public function field_color($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false){
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        $value=str_replace('"', '\"', $value);
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        if($value<>null){
            $color_option="color: \"$value\",";
        }

        $F="<input type='text' name='$name' placeholder='{$label_text}' id='$id' class='form-control' value=\"$value\">";

        $this->JS[]="$(\"#$id\").colorpicker();";

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }
        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readOnly=true;";
            return $this->_field_intr($label,$F,$explain);
        }


        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }

    public function field_upload_image($name,$label,$value,$notnull=false,$explain=null){
        $id=md5("$name$label$explain"."$this->suffixid");
        $id2=md5("$name$label$explain$value"."$this->suffixid");
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $basename=basename($value);
        $encoded_string=urlencode($value);
        $F="
       <div class='input-group'>
            <input type='hidden' id='$id' value='$value'>
            <input type='text' placeholder='' id='$id2' class='form-control' value=\"$basename\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('fw.image.validator.php?js=yes&id=$id&id2=$id2&path=$encoded_string');\">
                    <i class='fad fa-image'></i></button> 
            </span>
    </div>";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr("$label",$F,$explain);

    }

    public function field_ftp_params($array){
        $id=md5(serialize($array)."$this->suffixid");
        $label_text=$this->javascript_parse_text("{ftp_server}");
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $encoded_string=urlencode(base64_encode(serialize($array)));
        $F="
       <div class='input-group'>
            <input type='text' placeholder='{$label_text}' id='$id' class='form-control' value=\"{$array["FTP_SERVER"]["VALUE"]}\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('fw.ftp.validator.php?js=yes&id=$id&data=$encoded_string');\">
                    <i class='fas fa-cogs'></i></button> 
            </span>
    </div>";

        return $this->_field_intr("{ftp_server}",$F,null);

    }

    private function field_text_button_internal($name,$label,$value,$notnull=false,$explain=null,$text_button=null,$Loadjs=null,$prfixicon=null){
        $id=md5("$name$this->suffixid");
        $button_text="{change}";
        $rpfx="";
        if($text_button<>null){
            $button_text=$text_button;
        }
        $this->DISABLE_ALL[]=$id;
        if(!is_null($value)) {
            $value = str_replace('"', '\"', $value);
        }
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $valueEnc=urlencode($value);

        if ($prfixicon<>null){
            $rpfx="<i style='color:white' class='$prfixicon'></i>&nbsp;";
        }
        $F="
       <div class='input-group'>
            <input type='text' name='$name' placeholder='{$label_text}' id='$id' class='form-control' value=\"$value\" OnClick=\"this.disabled=true;\" OnMouseOver=\"this.disabled=true;\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('$Loadjs&id=$id&value=$valueEnc&suffix-form=$this->suffixid');\">
                   $rpfx$button_text</button> 
            </span>
    </div>";



        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }
        $this->FormStartupScripts[] = "document.getElementById('$id').disabled=true;";
        $this->FormStartupScripts[] = "document.getElementById('$id').readOnly=true;";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }
    private function field_text_button_certificateid($name,$label,$value,$notnull=false,$explain=null,$text_button=null,$Loadjs=null,$prfixicon=null){
        $id=md5("$name$this->suffixid");
        $button_text="{change}";

        if($text_button<>null){
            $button_text=$text_button;
        }
        $this->DISABLE_ALL[]=$id;
        if(!is_null($value)) {
            $value = str_replace('"', '\"', $value);
        }
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $valueEnc="";
        if(!is_null($value)) {
            $valueEnc = urlencode($value);
        }
        $rpfx="";
        if ($prfixicon<>null){
            $rpfx="<i style='color:white' class='$prfixicon'></i>&nbsp;";
        }
        $text_nic="{none}";
        if(!is_null($value)) {
            if (strlen($value) > 1) {
                include_once(dirname(__FILE__) . "/class.squid.reverse.inc");
                $squid_reverse = new squid_reverse();
                $sslcertificates = $squid_reverse->ssl_certificates_list();
                $text_nic = $sslcertificates[$value];
            }
        }
        $style="style='width:100%;text-align:left'";
        $Click="OnClick=\"Loadjs('$Loadjs&id=$id&value=$valueEnc&suffix-form=$this->suffixid');\"";
        $F="
       <div class='input-group'>
            <span class='input-group-btn'><button type=\"button\" $style $Click class=\"btn btn-default font-bold\" id='btnlabel-$id'>$text_nic</button></span>
            <input type='hidden' name='$name' placeholder='$label_text' id='$id' value=\"$value\">
            <span class=\"input-group-btn\"> 
                <button type=\"button\" class=\"btn btn-primary\" $Click\">$rpfx$button_text</button> 
            </span>
    </div>";



        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }
        $this->FormStartupScripts[] = "document.getElementById('$id').disabled=true;";
        $this->FormStartupScripts[] = "document.getElementById('$id').readOnly=true;";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }
    private function field_text_button_Interface($name,$label,$value,$notnull=false,$explain=null,$text_button=null,$Loadjs=null,$prfixicon=null){
        $id=md5("$name$this->suffixid");
        $button_text="{change}";

        if($text_button<>null){
            $button_text=$text_button;
        }
        $this->DISABLE_ALL[]=$id;
        if(!is_null($value)) {
            $value = str_replace('"', '\"', $value);
        }
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $valueEnc=urlencode($value);
        $rpfx="";
        if ($prfixicon<>null){
            $rpfx="<i style='color:white' class='$prfixicon'></i>&nbsp;";
        }
        $text_nic="{default}";
        if(strlen($value)>1){
            $nic=new system_nic($value);
            $text_nic=$nic->NICNAME." (".$nic->IPADDR.")";
        }
        $style="style='min-width:175px;text-align:left'";
        $Click="OnClick=\"this.style.background='#FFFFFF'\"";
        $F="
       <div class='input-group'>
            <span class='input-group-btn'><button type=\"button\" $style $Click class=\"btn btn-default\" id='btnlabel-$id'>$text_nic</button></span>
            <input type='text' name='$name' placeholder='$label_text' id='$id' class='form-control' value=\"$value\" OnClick=\"this.disabled=true;\" OnMouseOver=\"this.disabled=true;\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('$Loadjs&id=$id&value=$valueEnc&suffix-form=$this->suffixid');\">
                   $rpfx$button_text</button> 
            </span>
    </div>";



        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }
         $this->FormStartupScripts[] = "document.getElementById('$id').disabled=true;";
         $this->FormStartupScripts[] = "document.getElementById('$id').readOnly=true;";
         $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }

    public function field_text_button($name,$label,$value,$notnull=false,$explain=null,$text_button=null){
        $id=md5("$name$this->suffixid");
        $page=CurrentPageName();
        $button_text="{change}";
        $LOCK=true;
        if($text_button<>null){
            $button_text=$text_button;
            $LOCK=false;
        }
        $this->DISABLE_ALL[]=$id;
        $value=str_replace('"', '\"', $value);
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $valueEnc=urlencode($value);
        $F="
       <div class='input-group'>
            <input type='text' name='$name' placeholder='$label_text' id='$id' class='form-control' value=\"$value\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('$page?$name-js=yes&id=$id&value=$valueEnc&suffix-form=$this->suffixid');\">
                   $button_text</button> 
            </span>
    </div>";



        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($LOCK) {
            $this->FormStartupScripts[] = "document.getElementById('$id').disabled=true;";
            $this->FormStartupScripts[] = "document.getElementById('$id').readOnly=true;";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }


    public function field_tags($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        $value=str_replace('"', '\"', $value);
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $typeField="input type='text'";
        $option_value=null;

        if(preg_match("#^force:(.+)#",$explain,$re)){
            $tb=explode(",",$re[1]);
            $option_values=array();
            $explain="";
            $typeField="select multiple";
            foreach ($tb as $zval){
                if(trim($zval)==null){continue;}
                $option_values[]="<option value=\"$zval\">$zval</option>";
            }
            $option_value=@implode("\n",$option_values)."\n</select>";
        }


        $F="<$typeField name='$name' placeholder='$label_text' id='$id' class='form-control' value=\"$value\" data-role='tagsinput' >$option_value
        <script>
        $('#$id').tagsinput({ tagClass: 'label label-primary',trimValue: true });
        </script>";

        if(preg_match("#url:(.+?);(.+)#",$explain,$re)){
            $explain=$re[2];
            $jsbut="s_PopUpFull('{$re[1]}','1024','900')";
            $F="<div class='input-group'>$F<span class='input-group-btn'><button class='btn btn-default' type='button' OnClick=\"$jsbut\"><i class='fas fa-info-circle'></i></button></span></div><script>
        $('#$id').tagsinput({ tagClass: 'label label-primary',trimValue: true });
        </script>";
        }

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=$('#$id').val();";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }
        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readOnly=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent($('#$id').val()));";
        return $this->_field_intr($label,$F,$explain);
    }


    public function field_daterange($name,$label,$value,$MinDate,$MaxDate):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if(!is_null($value)) {
            $value = str_replace('"', '\"', $value);
        }
        if(!preg_match("#^([0-9]+)-([0-9]+)-([0-9]+) ([0-9]+):([0-9]+)#",$value)){
        $value=date("Y-m-d H:i:00",strtotime('yesterday')) ." - ".date("Y-m-d H:i:00");
        }

        if(preg_match("#^(.+?)\s+([0-9:]+)\s+-\s+(.+?)\s+([0-9:]+)#",$value,$re)){
            $StartDate="$re[1] $re[2]";
            $EndDate="$re[3] $re[4]";
        }

        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $F="<input type='text' name='$name' id='$id' class='form-control' value=\"$value\">";
        $this->JS_FUNCs[] = " // Date Range picker for $id default: $value\n";
        $this->JS_FUNCs[] = "$('#$id').daterangepicker({";
        $this->JS_FUNCs[] = "\ttimePicker: true,";
        $this->JS_FUNCs[] = "\tautoUpdateInput: true,";
        $this->JS_FUNCs[] = "\tstartDate: '$StartDate',";
        $this->JS_FUNCs[] = "\tendDate: '$EndDate',";
        $this->JS_FUNCs[] = "\tminDate: '$MinDate',";
        $this->JS_FUNCs[] = "\tmaxDate: '$MaxDate',";
        $this->JS_FUNCs[] = "\tlocale: {";
        $this->JS_FUNCs[] = "\t\t\tformat: 'YYYY-MM-DD hh:mm'";
        $this->JS_FUNCs[] = "\t}";
        $this->JS_FUNCs[] = "});";


        //$this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F,null);
    }
    
    public function field_text($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if(!is_null($value)) {
            $value = str_replace('"', '\"', $value);
        }
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $F="<input type='text' name='$name' placeholder='{$label_text}' id='$id' class='form-control' value=\"$value\">";

        if(!is_null($explain)) {
            if (preg_match("#url:(.+?);(.*)#", $explain, $re)) {
                $explain = $re[2];
                $jsbut = "s_PopUpFull('{$re[1]}','1024','900')";
                $F = "<div class='input-group'>$F<span class='input-group-btn'><button class='btn btn-default' type='button' OnClick=\"$jsbut\"><i class='fas fa-info-circle'></i></button></span></div>";
            }
        }



        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }
        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readOnly=true;";
            return $this->_field_intr($label,$F,$explain);
        }


        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }

    public function field_text_big($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if(!is_null($value)) {
            $value = str_replace('"', '\"', $value);
        }
        $this->FormStartupScripts[]="$(document).ready(function(){";
        $this->FormStartupScripts[]="\tvar field$id = document.getElementById('$id');";
        $this->FormStartupScripts[]="\tif (field$id) {";
        $this->FormStartupScripts[]="\t\tsetTimeout(function() {";
        $this->FormStartupScripts[]="\t\t\tfield$id.focus();";
        $this->FormStartupScripts[]="\t\t},800);";
        $this->FormStartupScripts[]="\t}";
        $this->FormStartupScripts[]="});";

        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $F="<input type='text' placeholder='{$label_text}...' class='PostForm' id='$id' style='width: 99%; padding: 20px; font-size: 2.5em; border: 2px solid #007445; border-radius: 8px; color: #333; box-sizing: border-box;' value=\"$value\">";

        if(!is_null($explain)) {
            if (preg_match("#url:(.+?);(.*)#", $explain, $re)) {
                $explain = $re[2];
                $jsbut = "s_PopUpFull('{$re[1]}','1024','900')";
                $F = "<div class='input-group'>$F<span class='input-group-btn'><button class='btn btn-default' type='button' OnClick=\"$jsbut\"><i class='fas fa-info-circle'></i></button></span></div>";
            }
        }
        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr("",$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr("",$F,$explain);
        }
        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readOnly=true;";
            return $this->_field_intr("",$F,$explain);
        }


        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr("",$F,$explain);
    }

    public function field_ipv4($name,$label,$value,$notnull=false,$explain=null,$disabled=false):string{
        return $this->field_ipaddr($name,$label,$value,$notnull,$explain,$disabled);
    }


    public function field_MacAddress($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false){
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        $F="
		<input type=\"text\" id='$id'  placeholder=\"00:0c:29:7f:c3:bb\" class=\"form-control\" value=\"$value\">
		<script>$('#$id').setMask('**:**:**:**:**:**').val('$value');</script>";

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readonly=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }

    public function field_cdir($name,$label,$value,$notnull=false,$explain=null,$disabled=false):string{

        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        $F="
		<input type=\"text\" id='$id'  placeholder=\"0.0.0.0/24\" class=\"form-control\" value=\"$value\">
		<script>$('#$id').mask('099.099.099.099/09');</script>";
        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }
    public function field_password2($name,$label,$value=null,$notnull=false,$explain=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $id2=md5("2$name$this->suffixid");

        if(!isset($GLOBALS["EnableShowPasswords"])){
            $GLOBALS["EnableShowPasswords"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableShowPasswords"));
        }

        $users=new usersMenus();
        if(!$users->AsAnAdministratorGeneric){
            $GLOBALS["EnableShowPasswords"]=0;
        }



        $view_pass1="<span class=\"input-group-btn\"> 
        <button type=\"button\" class=\"btn btn\" OnClick=\"my{$id}Passwitch('$id')\">
            <i class=\"fas fa-eye\" id='passhide{$id}'></i>
        </button> 
        </span>";

        $view_pass2="<span class=\"input-group-btn\"> 
        <button type=\"button\" class=\"btn btn\" OnClick=\"my{$id}Passwitch('$id2')\">
            <i class=\"fas fa-eye\" id='passhide{$id2}'></i>
        </button> 
        </span>";

        if($GLOBALS["EnableShowPasswords"]==0){
            $view_pass1=null;
            $view_pass2=null;
        }

        $F="<div class=\"input-group\"><input type='password' id='$id' class='form-control' value='$value' style='font-size:14px'>$view_pass1</div>";
        $F2="<div class=\"input-group\"><input type='password' id='$id2' class='form-control' value='$value' style='font-size:14px'>$view_pass2</div>";

        $NoSpinner="NoSpinner()";

        if($GLOBALS["EnableShowPasswords"]==1) {
            $this->JS_FUNCs[] = " // Password displays\n";
            $this->JS_FUNCs[] = "function my{$id}Passwitch(id) {";
            $this->JS_FUNCs[] = "\tvar x = document.getElementById(id);";
            $this->JS_FUNCs[] = "\tif (x.type === 'password') {";
            $this->JS_FUNCs[] = "\t\tx.type = 'text';";
            $this->JS_FUNCs[] = "\t\t$('#passhide'+id).toggleClass(\"fas fa-eye-slash\");";
            $this->JS_FUNCs[] = "\t} else {";
            $this->JS_FUNCs[] = "\t\tx.type = 'password';";
            $this->JS_FUNCs[] = "\t\t$('#passhide'+id).toggleClass(\"fas fa-eye\");";
            $this->JS_FUNCs[] = "\t}";
            $this->JS_FUNCs[] = "}";
        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";


            $this->XHR[]="var aF$id=document.getElementById('$id').value;";
            $this->XHR[]="var bF$id=document.getElementById('$id2').value;";
            $this->XHR[]="if ( aF$id.length > 0 ){";
            $this->XHR[]="\tif( aF$id.length < 5 ){ $NoSpinner;alert('Password is too short (minimum five characters)!');return;}";
            $this->XHR[]="\tif( aF$id == bF$id ){ }else{ $NoSpinner;alert('Passwords Do Not Match!');return;}";
            $this->XHR[]="}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            $tt[]=$this->_field_intr($label,$F,$explain);
            $tt[]=$this->_field_intr("&nbsp;",$F2);
            return @implode("\n", $tt);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="var aF$id=document.getElementById('$id').value;";
        $this->XHR[]="var bF$id=document.getElementById('$id2').value;";
        $this->XHR[]="if( aF$id.length > 0 ){";
        $this->XHR[]="\tif( aF$id.length < 5 ){ $NoSpinner;alert('Password is too short (minimum five characters)!');return;}";
        $this->XHR[]="\tif( aF$id == bF$id ){ }else{ $NoSpinner;alert('Passwords Do Not Match!');return;}";
        $this->XHR[]="}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        $tt[]=$this->_field_intr($label,$F,$explain);
        $tt[]=$this->_field_intr("&nbsp;",$F2);
        return @implode("\n", $tt);

    }
    public function field_password($name,$label,$value=null,$notnull=false,$explain=null,$disabled=false){

        if(!isset($GLOBALS["EnableShowPasswords"])){
            $GLOBALS["EnableShowPasswords"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableShowPasswords"));
        }

        $users=new usersMenus();
        if(!$users->AsAnAdministratorGeneric){
            $GLOBALS["EnableShowPasswords"]=0;
        }


        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;

        $view_pass1="<span class=\"input-group-btn\"> 
        <button type=\"button\" class=\"btn btn\" OnClick=\"my{$id}Passwitch('$id')\">
            <i class=\"fas fa-eye\" id='passhide{$id}'></i>
        </button> 
        </span>";
        if($GLOBALS["EnableShowPasswords"]==1) {
            $this->JS[] = "
			function my{$id}Passwitch(id) {
                var x = document.getElementById(id);
                if (x.type === 'password') {
                    x.type = 'text';
                    $('#passhide'+id).toggleClass(\"fas fa-eye-slash\");
                } else {
                    x.type = 'password';
                     $('#passhide'+id).toggleClass(\"fas fa-eye\");
                }
            }";
        }


        if($GLOBALS["EnableShowPasswords"]==0){$view_pass1=null;}


        $F="<div class=\"input-group\">
            <input type='password' name='{$name}' placeholder='{$label}' id='$id' class='form-control' value='$value' style='font-size:14px'>
            $view_pass1
            </div>";

        if($disabled){$notnull=false;$view_pass1=null;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);


    }
    public function field_maskcdir($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        $MASK["32"]="255.255.255.255 (/32)";
        $MASK["31"]="255.255.255.254 (/31)";
        $MASK["30"]="255.255.255.252 (/30)";
        $MASK["29"]="255.255.255.248 (/29)";
        $MASK["28"]="255.255.255.240 (/28)";
        $MASK["27"]="255.255.255.224 (/27)";
        $MASK["26"]="255.255.255.192 (/26)";
        $MASK["25"]="255.255.255.128 (/25)";
        $MASK["24"]="255.255.255.0 (/24)";
        $MASK["23"]="255.255.254.0 (/23)";
        $MASK["22"]="255.255.252.0 (/22)";
        $MASK["21"]="255.255.248.0 (/21)";
        $MASK["20"]="255.255.240.0 (/20)";
        $MASK["19"]="255.255.224.0 (/19)";
        $MASK["18"]="255.255.192.0 (/18)";
        $MASK["17"]="255.255.128.0 (/17)";
        $MASK["16"]="255.255.0.0 (/16)";
        $MASK["15"]="255.254.0.0 (/15)";
        $MASK["14"]="255.252.0.0 (/14)";
        $MASK["13"]="255.248.0.0 (/13)";
        $MASK["12"]="255.240.0.0 (/12)";
        $MASK["11"]="255.224.0.0 (/11)";
        $MASK["10"]="255.192.0.0 (/10)";
        $MASK["9"]="255.128.0.0 (/9)";
        $MASK["8"]="255.0.0.0 (/8)";
        $MASK["7"]="254.0.0.0 (/7)";
        $MASK["6"]="252.0.0.0 (/6)";
        $MASK["5"]="248.0.0.0 (/5)";
        $MASK["4"]="240.0.0.0 (/4)";
        $MASK["3"]="224.0.0.0 (/3)";
        $MASK["2"]="192.0.0.0 (/2)";
        $MASK["1"]="128.0.0.0 (/1)";

        return $this->field_array_hash($MASK, $name, $label, $value,$explain);
    }
    public function field_text_online($name,$placeholder="0.0.0.0",$value=null,$width=null,$Loadjs=null){
        if(!is_null($placeholder)){
            $placeholder=$this->_ENGINE_parse_body($placeholder);
        }
        $width_text="";
        if(!is_null($width)){
            $width_text="style='width:{$width}px;'";
        }


        $t=md5(microtime(true).$name);
        $f[]="<div class=\"btn-group\">";
        $f[]="<input type=\"text\" id=\"$name\" placeholder='$placeholder' name=\"$name\" class=\"form-control\" value=\"$value\" $width_text>";
        $f[]="</div>";
        
        $f[]="<script>";
        $f[]="function handleEnterKeyPress$t(event){";
        $f[]="alert(event.key);";
        $f[]="if (event.key === \"Enter\") { ";
        $f[]="alert('enterKey');";
        $f[]="}";
        $f[]="}";
        $f[]=" $( '#$name' ).on( 'keydown', function( event ) {
        if ( event.keyCode === 13 ) {
            value=encodeURIComponent(document.getElementById('$name').value);";
        if(!is_null($Loadjs)){
            $f[]="let js='$Loadjs';";
            $f[]="let uri = js.replace(\"%s\", value);";
            $f[]="Loadjs(uri)";
        }
        $f[]="}";
        $f[]="})";
        $f[]="</script>";
        
        return @implode("",$f);
    }
    public function field_ipaddr($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$addjs=null):string{
        $id=md5("$name$this->suffixid");
        $button2=null;$style=null;
        $big=false;
        if($addjs<>null){
            $button2 ="<button class='btn' type='button' OnClick=\"$addjs\" >
            <span class='fas fa-plus-square'></span>&nbsp;</button>";

        }

        if(preg_match("#^big:(.*)#",$label,$re)){
            $big=true;
            $label=$re[1];

        }

        if($big){
            $style=" style='font-size:40px;padding:40px'";
        }

        $this->DISABLE_ALL[]=$id;
        $F="
		      <div class='input-group'>
                <input type=\"text\" id=\"$id\" placeholder='0.0.0.0' name=\"$id\" class=\"form-control\" value=\"$value\"$style>
                <span class='input-group-btn'>
                   $button2
                </span>
            </div>	
            <script>  
            $(document).ready(function () { 
                var ipv4_address = $('#ipv4');
          	    ipv4_address.inputmask({alias: \"ip\",\"placeholder\": \"\" });
            }); 
            
            </script>
		";
        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }
    public function field_div($html){
        return "<tr>
		<td colspan=2>$html</td>
		</tr>
		";

    }
    function div_error($text):string{
        $text=$this->_ENGINE_parse_body($text);
        $title="{SERVFAIL}";
        $button=null;
        if(strpos($text,"||")>3){
            $tb=explode("||",$text);
            $title=$tb[0];
            $text=$tb[1];
            if(isset($tb[2])){
                $button=$this->button_autnonome("{howto}: {online_help}", $tb[2], "fas fa-user-headset",null,0,"btn-danger");
            }

        }

        return $this->_ENGINE_parse_body(" <div class=\"panel panel-danger\" style='margin:10px;margin-top:20px'>
                                        <div class=\"panel-heading\">
                                            $title
                                        </div>
                                        <div class=\"panel-body\">
                                            <p>$text</p>$button
                                        </div>
                                    </div>
                                </div>");

    }
    function div_warning($text):string{
        $LICENCE=false;
        if(strpos(" $text","{license_error}")>0){
            $LICENCE=true;
        }
        $license_ico=null;
        $title="{Warning}";
        $button=null;
        if(strpos($text,"||")>0){
            $tb=explode("||",$text);
            $title=$tb[0];
            $text=$tb[1];
            if(isset($tb[2])){
                if(preg_match("#wiki:(.+)#",$tb[2],$re)){
                    $url="s_PopUpFull('{$re[1]}','1024','900');";
                    $button=$this->button_autnonome("{howto}: {online_help}", $url, "fas fa-user-headset",null,0,"btn-warning");
                }else{
                    $button=$this->button_autnonome("{howto}: {online_help}", $tb[2], "fas fa-user-headset",null,0,"btn-warning");
                }


            }
        }

        if($LICENCE){
            $license_ico="<div style='float:left;margin:10px;'><i class=' text-warning ".ico_certificate." fa-6x'></i></div>";
        }

        return $this->_ENGINE_parse_body(" <div class=\"panel panel-warning\" style='margin:10px'>
                                        <div class=\"panel-heading\">
                                          $title
                                        </div>
                                        <div class=\"panel-body\">
                                            $license_ico<p>$text</p>
                                            <div style='text-align:right'>
                                            $button
                                            </div>
                                        </div>
                                    </div>
                                </div>");
    }
    function div_wizard($text):string{
        $title="{wizard}";
        if(strpos($text,"||")>3) {
            $tb = explode("||", $text);
            $title = $tb[0];
            $text = $tb[1];
        }

        $html[]="<div class='well'>";
        $html[]="    <div style='float:left;min-height:89px;margin-right:15px'>";
        $html[]="        <i class='fa-solid fa-hat-wizard fa-5x'></i>";
        $html[]="    </div>";
        $html[]="    <H2 style='margin-top:0'>$title</H2>";
        $html[]="    <div>$text</div>";
        $html[]="</div>";



        return @implode("\n",$html);
        
    }
    private function div_explain_bt($text):array{
        if(is_numeric($text)){
            return array("",$text);
        }

        if(preg_match("#wiki:(.+)#",$text,$re)) {
            $url = "s_PopUpFull('{$re[1]}','1024','900');";
            $button = $this->button_autnonome("{howto}: {online_help}", $url, "fas fa-user-headset", null, 0, "btn-info");
            return array("$button", 0);
        }

        $button=$this->button_autnonome("{howto}: {online_help}", $text, "fas fa-user-headset",null,0,"btn-info");
        return array("$button", 0);
    }
    public function div_explain($text,$nomargin=false):string{
        $button=null;
        $fasize=5;
        $icoclass="fa-solid fa-square-info";
        $title="{about2}";
        if(preg_match("#^notitle:(.+)#",$text,$re)){
            $title="";
            $text=$re[1];

        }
        if(preg_match("#ico:(.+?);#",$text,$re)){
            $icoclass=$re[1];
            $text=str_replace("ico:$icoclass;","",$text);
        }


        $text=$this->_ENGINE_parse_body($text);


        if(strpos($text,"||")>3){
            $tb=explode("||",$text);
            $title=$tb[0];
            $text=$tb[1];
            if(isset($tb[2])){
                $html[]="<!-- $tb[2] -->";
                list($button,$fasize)=$this->div_explain_bt($tb[2]);
            }
        }

        if($fasize==0){$fasize=5;}
        $html[]="<div class='well'>";
        $html[]="    <table style='width:100%;margin-top:-10px'>";
        $html[]="        <tr>";
        $html[]="            <td style='vertical-align:top;width:1%;' nowrap>";
        $html[]="                <div>";
        $html[]="                    <i class='$icoclass fa-{$fasize}x'></i>";
        $html[]="                </div>";
        $html[]="            </td>";
        $html[]="            <td style='vertical-align:top;padding-left:15px;width:99%'>";
        if(strlen($title)>2) {
            $html[] = "            <H2 style='margin-top:0'>$title</H2>";
        }
        $html[]="            <div>$text</div>$button";
        $html[]="            </td>";
        $html[]="        </tr>";
        $html[]="    </table>";
        $html[]="</div>";
        return @implode("\n",$html);
    }
    public function field_onoff_newlayer($label,$value,$js,$explain){
        $a="<a href=\"javascript:blur();\" OnClick=\"$js\">";
        if($value==1){
            $span="$a<span class='label label-primary'>{active2}</span></a>";
        }else{
            $span="$a<span class='label label'>{inactive}</span></a>";
        }
        return $this->_field_intr($label,$span,$explain);
    }
    public function field_section($title=null,$explain=null,$warn=false):string{

        if($explain==null){
            $subtext="";
            if(strpos($title,"||")>0){
                $tb=explode("||",$title);
                $title=$tb[0];
                $subtext="<div style='font-size:11px'><i>$tb[1]</i></div>";
            }

            $f[]="<tr>";
            $f[]="<td colspan='2'>";
            $f[]="<H2 style='border-bottom:1px solid #CCCCCC;padding-bottom:5px'>$title</H2>$subtext";
            $f[]="</td>";
            $f[]="</tr>";
            return @implode("\n",$f);
        }


        $f[]="<tr>";
        $f[]="<td colspan='2'>";
        if($warn){
            $f[]=$this->div_warning("$title||$explain");
        }else {
            $f[] = $this->div_explain("$title||$explain", $warn);
        }
        $f[]="</td>";
        $f[]="</tr>";
        return @implode("\n",$f);



    }
    public function field_date($name,$label=null,$value=null,$explain=null){
        if($value==null){$value=date("Y-m-d");}
        $id=md5("$name$this->suffixid");
        $t=microtime();

        $F= "<div class='form-group' id='data_$id'>
			<div class='input-group date'>
				<span class='input-group-addon'><i class='fa fa-calendar'></i></span>
					<input type='text' class='form-control' value='$value' id='$id'>
			</div>
		</div>
		<script>
$('#data_$id .input-group.date').datepicker({
		todayBtn: \"linked\",
		keyboardNavigation: false,
		forceParse: false,
		calendarWeeks: true,
		autoclose: true,
		format: \"yyyy-mm-dd\"
	});
</script>		
		";



        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,$F,$explain);



    }
    public function field_squid_ports($name,$label,$value,$explain=null){
        $sql="SELECT * FROM proxy_ports";
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){echo "<div class='alert alert-danger' style='margin-top:10px'>$q->mysql_error<br><strong>$sql</strong></div>";}
        $ALL_TEXT=$this->_ENGINE_parse_body("{all_interfaces}");
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            if( ($ligne["transparent"]==1) OR ($ligne["TProxy"]==1)){continue;}
            $PortName=$this->javascript_parse_text($ligne["PortName"]);
            $ipaddr=trim($ligne["ipaddr"]);
            $port=$ligne["port"];
            $PORTS[$ID]="$port: $PortName - $ipaddr:$port";
        }
        return $this->field_array_hash($PORTS, $name, $label, $value,false,$explain);
    }

    public function field_localips($name,$label,$value,$explain=null){
        if(preg_match("#no default#", $value)){$value=null;$ASDEF=false;}
        include_once(dirname(__FILE__).'/class.system.nics.inc');
        $nic=new networking();
        $nicZ=$nic->Local_interfaces();
        if($ASDEF){$NICS["0.0.0.0"]="{all_interfaces}";}
        $NICS["127.0.0.1"]="Loopback";
        $EnableIwConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));
        foreach ($nicZ as $yinter=>$line){
            if(preg_match("#^dummy#", $yinter)){continue;}
            if(preg_match("#-ifb$#", $yinter)){continue;}
            if($EnableIwConfig==0){ if(preg_match("#^wlan[0-9]+#", $yinter)){continue;} }
            $znic=new system_nic($yinter);
            if($znic->Bridged==1){continue;}
            if($znic->enabled==0){continue;}
            $NICS[$znic->IPADDR]="$znic->NICNAME ($yinter)";
        }
        return $this->field_array_hash($NICS, $name, $label, $value,false,$explain);

    }

    public function field_interfaces_vlan($eth,$name,$label,$value,$explain=null){
        $ASDEF=true;
        $loopback=true;
        $NICS=array();
        if(preg_match("#nooloopNoDef:(.+)#",$label,$re)){$label=$re[1];$loopback=false;$ASDEF=false;}
        if(preg_match("#nooloop:(.+)#",$label,$re)){$label=$re[1];$loopback=false;}
        if(preg_match("#nodef:(.+)#",$label,$re)){$label=$re[1];$ASDEF=false;}
        if(preg_match("#no default#", $value)){$value=null;$ASDEF=false;}

        if($ASDEF){$NICS[null]="{none}";}
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $sql="SELECT * FROM nics_vlan WHERE nic='{$eth}' ORDER BY ID";
        $results=$q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne) {
            $ID=$ligne["ID"];
            $vlanid = $ligne["vlanid"];
            $eth_text = "vlan{$ligne["ID"]}";
            $ipaddr=$ligne["ipaddr"];
            $NICS[$ID]="$eth_text $ipaddr (TAG $vlanid)";
            $InterfacesCount++;
        }

        if($InterfacesCount==0){
            return $this->field_hidden($name,null);
        }

        return $this->field_array_hash($NICS, $name, $label, $value,false,$explain,null,$ASDEF);


    }

    public function InterfaceTOIP($ifname):string{
        include_once(dirname(__FILE__).'/class.system.nics.inc');
        $nic=new networking();
        $nic->ifconfig($ifname);
        return $nic->tcp_addr;

    }

    public function list_interfaces($ONLYPHYS=false,$NICS=array()):array{
        $EnableIwConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));
        include_once(dirname(__FILE__).'/class.system.nics.inc');
        $nic=new networking();
        $nicZ=$nic->Local_interfaces();
        foreach ($nicZ as $yinter=>$line){
            if($yinter=="lo"){continue;}
            if(preg_match("#^dummy#", $yinter)){continue;}
            if(preg_match("#-ifb$#", $yinter)){continue;}
            if(preg_match("#^(dummy|teql|ip6tnl|tunl|gre|ifb|sit|gretap|erspan)[0-9]+#",$yinter)){continue;}
            if($ONLYPHYS){
                if(!preg_match("#^(eth|wlan|ens)#",$yinter)){continue;}
            }
            if($EnableIwConfig==0){ if(preg_match("#^wlan[0-9]+#", $yinter)){continue;} }
            $znic=new system_nic($yinter);
            if($znic->Bridged==1){continue;}
            if($znic->enabled==0){continue;}
            $NICS[$yinter]="$znic->NICNAME $znic->IPADDR ($yinter)";

        }
        return $NICS;
    }

    public function field_certificate($name,$label,$value=null,$explain=null,$ServerCert=0):string{
        $NoRoots="";

        if($ServerCert==1){
            $NoRoots="&NoRoots=yes";
        }
        return $this->field_text_button_certificateid($name,$label,$value,false,$explain,"{select}","fw.field.certificates.php?name=$name&current=$value$NoRoots",ico_certificate);
    }

    public function field_interfaces($name,$label,$value=null,$explain=null):string{
        $ASDEF=true;
        $loopback=true;
        $ONLYPHYS=false;$ASDEFNone=false;
        $NICS=array();
        if(preg_match("#nooloopNoDefphy:(.+)#",$label,$re)){$label=$re[1];$loopback=false;$ASDEF=false;$ONLYPHYS=true;}
        if(preg_match("#nooloopNoDef:(.+)#",$label,$re)){$label=$re[1];$loopback=false;$ASDEF=false;}
        if(preg_match("#nooloop:(.+)#",$label,$re)){$label=$re[1];$loopback=false;}
        if(preg_match("#nodef:(.+)#",$label,$re)){$label=$re[1];$ASDEF=false;}
        if(preg_match("#phy:(.+)#",$label,$re)){$label=$re[1];$ONLYPHYS=false;}
        if(!is_null($value)) {
            if (preg_match("#no default#", $value)) {
                $value = null;
                $ASDEF = false;
            }
        }
        if(preg_match("#nooloopNone:(.+)#",$label,$re)){$label=$re[1];$loopback=false;$ASDEF=true;$ASDEFNone=true;}
        if($ASDEF){
            $NICS[null]="{all_interfaces}";
            if($ASDEFNone){
                $NICS[null]="{none}";
            }

        }
        if($loopback) {
            $NICS["lo"] = "Loopback (127.0.0.1)";
        }
        $NICS=$this->list_interfaces($ONLYPHYS,$NICS);

        if(count($NICS)==0){
            return $this->field_hidden($name,null);
        }
        $Base64Nics=base64_encode(serialize($NICS));
        return $this->field_text_button_Interface($name,$label,$value,false,$explain,"{select}","fw.field.interfaces.php?name=$name&nics=$Base64Nics",ico_nic);

    }


    public function field_templates($name,$label,$value,$explain=null){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $results = $q->QUERY_SQL("SELECT TemplateName,ID FROM templates_manager");
        foreach ($results as $index=>$ligne){
            $TemplateName=$this->utf8_encode($ligne["TemplateName"]);
            $ID=$ligne["ID"];
            $XTPLS[$ID]=$TemplateName;
        }
        return $this->field_array_hash($XTPLS, $name, $label, $value,false,$explain);
    }

    public function field_webfiltering_rules($name,$label,$value,$explain=null,$RULES_ADD=array()){
        $memcached=new lib_memcached();
        $UFDB_RULES_LISTS=$memcached->getKey("UFDB_RULES_LISTS");
        if($memcached->MemCachedFound){
            $RULES=unserialize($UFDB_RULES_LISTS);
        }else{
            $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
            $sql="SELECT ID,groupname FROM webfilter_rules WHERE enabled=1";
            $results = $q->QUERY_SQL($sql);
            foreach ($results as $index=>$ligne){$RULES[$ligne["ID"]]="{$ligne["groupname"]}";}
            $memcached->SetSetting("UFDB_RULES_LISTS", serialize($RULES),300);

        }
        if(count($RULES_ADD)>0){
            foreach ($RULES_ADD as $ID=>$ulename){
                $RULES[$ID]=$ulename;
            }
        }

        return $this->field_array_hash($RULES, $name, $label, $value,false,$explain);

    }
    public function field_dnsfiltering_rules($name,$label,$value,$explain=null):string{
        $memcached=new lib_memcached();
        $RULES=array();
        $UFDB_RULES_LISTS=$memcached->getKey("DNSUFDB_RULES_LISTS");
        if($memcached->MemCachedFound){
            $RULES=unserialize($UFDB_RULES_LISTS);
        }else{
            $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
            $sql="SELECT ID,groupname FROM webfilter_rules WHERE enabled=1";
            $results = $q->QUERY_SQL($sql);
            if($results) {
                foreach ($results as $index => $ligne) {
                    $RULES[$ligne["ID"]] = "{$ligne["groupname"]}";
                }
            }
            $memcached->SetSetting("DNSUFDB_RULES_LISTS", serialize($RULES),300);

        }
        return $this->field_array_hash($RULES, $name, $label, $value,false,$explain);

    }


    public function CategoryidToName($category_id):string{
        $q=new postgres_sql();
        if(preg_match("#^P([0-9]+)#i", $category_id,$re)){$category_id=intval($re[1]);}
        $sql="SELECT categoryname FROM personal_categories WHERE category_id=$category_id";
        $ligne=$q->mysqli_fetch_array($sql);
        if($ligne["categoryname"]==null){return $this->_ENGINE_parse_body("{unknown}");}
        return $this->utf8_decode($ligne["categoryname"]);
    }

    public function field_personal_categories_list($name,$label,$value,$explain=null):string{
        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        if($DisablePostGres==1){return "";}

        $q=new postgres_sql();
        if(preg_match("#(.+?):ALL#", $name,$re)){
            $name=$re[1];
            $newcat2[0]="{all_categories}";
        }
        if($q->TABLE_EXISTS("personal_categories")) {
            $sql = "SELECT * FROM personal_categories WHERE enabled=1 AND official_category=0 and free_category=0 order by categoryname";
            $results = $q->QUERY_SQL($sql);

            while ($ligne = pg_fetch_assoc($results)) {
                $categoryname = $ligne["categoryname"];
                $category_id = $ligne["category_id"];
                $newcat2[$category_id] = $categoryname;
            }
        }

        return $this->field_array_hash($newcat2, $name, $label, $value,false,$explain);
    }


    public function array_categories_list():array{
        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        if($DisablePostGres==1){return array();}
        $newcat2=array();
        $q=new postgres_sql();

        if(!$q->TABLE_EXISTS("personal_categories")){
            include_once(dirname(__FILE__)."/class.categories.inc");
            $cate=new categories();
            $cate->initialize();
        }

        $sql="SELECT * FROM personal_categories order by categoryname";
        $results=$q->QUERY_SQL($sql);
        if(!$results){
            return $newcat2;
        }


        while ($ligne = pg_fetch_assoc($results)) {
            $categoryname=$ligne["categoryname"];
            $category_id=$ligne["category_id"];
            $newcat2[$category_id]=$categoryname;
        }

        return $newcat2;
    }


    public function field_categories_list($name,$label,$value,$explain=null){

        $q=new postgres_sql();
        if(preg_match("#(.+?):ALL#", $name,$re)){
            $name=$re[1];
            $newcat2[0]="{all_categories}";
        }

        $ManageOfficialsCategories = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
        $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by categoryname";
        if($ManageOfficialsCategories==1){
            $sql="SELECT * FROM personal_categories WHERE official_category=1 order by category_id";
        }



        $results=$q->QUERY_SQL($sql);
        while ($ligne = pg_fetch_assoc($results)) {
            $categoryname=$ligne["categoryname"];
            if(preg_match("#^reserved#",$categoryname)){continue;}
            $category_id=$ligne["category_id"];
            $newcat2[$category_id]=$categoryname;
        }
        $newcat2["999999999"]="The Shields";
        return $this->field_array_hash($newcat2, $name, $label, $value,false,$explain);
    }



    public function field_choose_periods($name,$label,$value,$explain=null){
        $Timez[0]="{unlimited}";
        $Timez[5]="5 {minutes}";
        $Timez[10]="10 {minutes}";
        $Timez[15]="15 {minutes}";
        $Timez[30]="30 {minutes}";
        $Timez[60]="1 {hour}";
        $Timez[120]="2 {hours}";
        $Timez[240]="4 {hours}";
        $Timez[720]="12 {hours}";
        $Timez[1440]="1 {day}";
        $Timez[2880]="2 {days}";
        $Timez[10080]="1 {week}";
        $Timez[20160]="2 {weeks}";
        $Timez[43200]="1 {month}";
        $Timez[129600]="3 {months}";
        $Timez[518400]="1 {year}";

        return $this->field_array_hash($Timez, $name, $label, $value,false,$explain);
    }


    public function field_url($label,$uri,$explain=null):string{

        $F="<a href=\"javascript:blur();\" OnClick=\"s_PopUpFull('$uri','1024','1024');\" class='btn btn-sm btn-outline btn-primary' style='font-size:14px'>$uri</a>";

        return $this->_field_intr($label,$F,$explain);

    }

    public function field_certificate_old($name,$label,$value=null,$explain=null):string{

        include_once(dirname(__FILE__)."/class.squid.reverse.inc");
        $squid_reverse=new squid_reverse();
        $sslcertificates=$squid_reverse->ssl_certificates_list();
        return $this->field_array_hash($sslcertificates,$name,$label,$value,false,$explain);
    }

    public function field_multiple_64($name,$label,$srcvalue=0,$explain=null):string{
        $start=200000/196;

        if($srcvalue>0){
            $main[$srcvalue]=$this->FormatNumber($srcvalue);

        }

        for($i=$start;$i<10000;$i+=64){
            $value=64*$i;
            $main[$value]=$this->FormatNumber($value);

        }
        return $this->field_array_hash($main,$name,$label,$srcvalue,$explain);
    }

    public function field_multiple_2($name,$label,$srcvalue=0,$explain=null):string{
        $start=512;

        if($srcvalue>0){
            $main[$srcvalue]=$this->FormatNumber($srcvalue);

        }

        for($i=$start;$i<10000;$i+=512){
            $value=2*$i;
            $main[$value]=$this->FormatNumber($value);

        }
        return $this->field_array_hash($main,$name,$label,$srcvalue,$explain);
    }

    public function field_picklist($hash,$name,$label,$value):string{
        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $def=null;
        $selected=false;
        $java=null;
        $javaend=null;
        if(empty($value)){$def="selected";$selected=true;}
        //if($OnChange<>null){$javaend="$('#$id').chosen().change( $OnChange );";}
        $h[]="<!-- Value sent:  [$value] Advanced DropDown -->";
        $x= $name."[]";
        $h[]="<select data-values=\"\" data-placeholder=\"{$label}...\" class=\"chosen-select\" id='$id' multiple=\"multiple\" tabindex=\"2\" $java>";

        foreach ($hash as $val=>$key){
            $def=null;
            if(strpos($key, "}")>0){$key=$this->_ENGINE_parse_body($key);}
            $lenght=strlen($key);
            if($lenght>70){$key=substr($key, 0,67)."...";}
            $h[]="<!-- $key,$val:$value  -->";

            if(!$selected){


                    $h[]="<!-- $val  -->";
                        if (array_key_exists($key, $value)) {
                            $h[]="<!-- OK  -->";
                            $def="selected";
                            //$selected=true;
                            $h[]="\t<option value=\"$val\" selected>$key</option>";

                            continue;
        }
//                    if(strtolower($val)==strtolower($selectedvals[$i])){
//                        $h[]="<!-- OK  -->";
//                        $def="selected";
//                        //$selected=true;
//                        $h[]="\t<option value=\"$val\" selected>$key</option>";
//                        $i++;
//                        continue;
//                    }


            }
            $h[]="\t<option value=\"$val\">$key</option>";
            $i++;
        }

        $h[]="</select>";


        $h[]="<script>";
        $h[]="\t$(function() {";
        $h[]="\t$('#$id').pickList({\"afterRefresh\": eventHandler,});";
        $h[]="\tfunction eventHandler() {val = $('#$id').val();document.getElementById('$id').setAttribute('data-values', val);}";
        $h[]="\t});";

        $h[]="</script>";


        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $h=array();
                $h[]= "<div class=\"input-group\"><input type=\"hidden\" id='$id' value=\"$value\">
                <strong>$value</strong>
                </div>";

            }
        }

        $explain="";


        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').getAttribute('data-values'));";
        return $this->_field_intr($label,@implode("\n",$h),$explain);

    }

    public function field_numeric($name,$label=null,$value=0,$explain=null,$js=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $jsinside=null;
        $return_single=false;
        $style=" style='font-size:14px'";
        if(!is_null($explain)) {
            if (preg_match("#xsingle:(.+)#", $explain, $re)) {
                $return_single = true;
                $explain = $re[1];
                $style = " style='font-size:large !important'";
            }
        }
        if(!is_null($value)) {
            if (preg_match("#^sysctl:(.+)#", $value, $re)) {
                $value=$GLOBALS["CLASS_SOCKETS"]->KERNEL_GET($re[1]);
                VERBOSE("sysctl Value *$value*", __LINE__);
            }
        }
        if($js<>null){
            $jsinside=" OnChange=\"$js($(this).attr('id'),$(this).val());\"";
        }
        $disable="";
        if($disabled){
            $disable="disabled=\"\"";
        }
        $this->DISABLE_ALL[]=$id;
        if(!is_numeric($value)){$value=1;}
        $t=microtime();
        $F= "
			        <div class=\"input-group\">
			              <span class=\"input-group-btn\">
			                  <button type=\"button\" class=\"btn btn-default\" data-value=\"-2\" data-target=\"#$id\" 
			                  	data-toggle=\"spinner\" OnClick=\"SpinnerLess('$id')\">
			                      <span class=\"glyphicon glyphicon-minus\"></span>
			                  </button>
			              </span>
			              <input type=\"number\" name=\"$name\" data-ride=\"spinner\" id=\"$id\" class=\"form-control input-number\"$style value=\"$value\" data-min=\"1\" data-max=\"9999999999999999999999\" autocomplete=\"on\"$jsinside $disable>
			              <span class=\"input-group-btn\">
			                  <button type=\"button\" class=\"btn btn-default\" data-value=\"1\" data-target=\"#$id\" 
			                  OnClick=\"SpinnerHigh('$id')\"
			                  data-toggle=\"spinner\" data-on=\"mousehold\">
			                      <span class=\"glyphicon glyphicon-plus\"></span>
			                  </button>
			              </span>
			          </div>
			          <!-- DEFAULT = $value -->
			       ";
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $F= "<div class=\"input-group\"><input type=\"hidden\" id='$id' value=\"$value\">
                <strong>$value</strong>
                </div>";

            }
        }

        if($return_single){
            return $F;
        }

        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,$F,$explain);
    }


    public function field_kbps($name,$label=null,$value=0,$explain=null){
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if(!is_numeric($value)){$value=1;}
        $value_mbps=round($value/125);
        $unit="Mpbps";
        if($value_mbps>1000){
            $unit="Gbps";
            $value_mbps=round($value/125000);}

        $t=microtime();
        $html[]="<span class=\"input-group-addon\">KO/s</span>";
        $html[]="<input type=\"text\" name=\"$name\" id=\"$id\" class=\"form-control\"  onkeyup=\"kbps$id()\" onchange='kbps$id()' style='font-size:14px' value=\"$value\">";
        $html[]="<span class='input-group-addon' id='mbps-$id'>{$value_mbps}$unit</span>";
        $html[]="</div>";
        $html[]="<script>";
        $html[]="function kbps$id(){";
        $html[]="\tvalue=document.getElementById('$id').value;";
        $html[]="\tif(value==''){";
        $html[]="\t\tdocument.getElementById('$id').value='$value';";
        $html[]="\treturn false;";
        $html[]="\t}";
        $html[]="\tif(isNaN(value)){";
        $html[]="\t\tdocument.getElementById('$id').value='$value';";
        $html[]="\t\treturn false;";
        $html[]="\t}";
        $html[]="\tmbps=Math.round(value/125);";
        $html[]="\tif(mbps>1000){";
        $html[]="\tmbps=Math.round(value/125000);";
        $html[]="\tdocument.getElementById('mbps-$id').innerHTML=mbps+'Gbps';";
        $html[]="\treturn true;";
        $html[]="}";
        $html[]="\tdocument.getElementById('mbps-$id').innerHTML=mbps+'Mbps';";
        $html[]="";
        $html[]="\t}";
        $html[]="</script>";

        $F=@implode("\n",$html);

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $F= "<div class=\"input-group\"><input type=\"hidden\" id='$id' value=\"$value\">
                <strong>$value</strong>
                </div>";

            }
        }

        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,$F,$explain);
    }

    private function _field_intr2($label,$formatted_field,$explain=null,$Checkbox=false){
        $labelid=$label;
        if($labelid==null){$labelid=rand(0,time());}
        $id=md5("$labelid$this->suffixid");
        if($explain<>null){
            if($label<>null) {
                $explain = $this->_ENGINE_parse_body($explain);
                $explain = str_replace('"', "`", $explain);
                $zlabel[] = "<a href=\"javascript:blur();\"";
                $zlabel[] = "data-toggle=\"popover\"";
                $zlabel[] = "data-placement=\"auto left\"";
                $zlabel[] = "data-content=\"$explain\"";
                $zlabel[] = ">$label</a>";
                $label = @implode(" ", $zlabel);
            }
        }
        if($this->FORM_IN_ARRAY){return "<td>$formatted_field</td>";}
        if(is_bool($Checkbox)){
            if(!$Checkbox){$this->LABELS[]=$id;}
        }

        if($label==null){
            return "
		<tr>
		<td style='padding-bottom:10px;text-align:left;padding-left:19px' colspan='2'>$formatted_field</td>
		</tr>
		";

        }


        return "
		<tr>
		<td style='text-align:right !important;vertical-align:top;width:10%' nowrap><span class=labelform id='$id'>{$label}</span>:</td>
		<td style='padding-bottom:10px;text-align:left;padding-left:19px'>$formatted_field</td>
		</tr>
		";


    }

    private function js_help($label,$explain=null,$URL=null):array{
        if($explain==null){return array($label,"");}
        $array_help["TITLE"]=$label;
        $array_help["content"]=$explain;
        $array_help["ico"]="fa fa-question";
        $array_help["URL"]=$URL;
        $ico="{click_to} <strong>{view_message}</strong><br><p>";
        $scontent=base64_encode(serialize($array_help));
        $js="LoadAjax('artica-modal-dialog','fw.popup.php?array=$scontent')";

        $explain=$this->_ENGINE_parse_body($ico.$explain);
        $explain=str_replace('"', "`", $explain);
        $explain=str_replace("\n", "<br>", $explain);
        if(strlen($explain)>200){$explain=substr($explain,0,197)."...";}
        $zid=md5($explain.microtime());
        $zlabel[]="<a href=\"javascript:$js;\"";
        $zlabel[]="style='color:#676a6c;text-decoration:underline;text-decoration-style: dashed;'";
        $zlabel[]="data-toggle=\"popover\"";
        $zlabel[]="data-placement=\"auto right\"";
        $zlabel[]="data-content=\"$explain</p>\" id='$zid'>";
        $ahref=@implode(" ",$zlabel);
        return array($label,$ahref,"$(\"#$zid\").popover({ trigger: 'hover',html: true,});");
        //
    }

    private function _field_intr($label,$formatted_field,$explain=null,$Checkbox=false,$URL=null):string{

        if($label=="TABLE!"){
            return " <!-- _field_intr 1 --><tr><td style='width:100%;padding-bottom:10px;' colspan='2'>$formatted_field</td></tr>";
        }

        if($label==null){$explain=null;}
        $point=null;
        $id=md5("$label$this->suffixid");
        $padding="padding-left:19px";
        if($label<>null){$point=":";}
        if($label=="&nbsp;"){$point=null;}
        $label=$label.$point;
        $js="blur()";        $ico="&nbsp;";

        list($label,$href_label)=$this->js_help($label,$explain,$URL);
        if($href_label<>null){
            $label="$href_label$label</a>";
        }

        if($this->FORM_IN_ARRAY){return "<td>$formatted_field</td>";}
        if(is_bool($Checkbox)){
            if(!$Checkbox){$this->LABELS[]=$id;}
        }
        $label=trim($label);
        $labelstr=strlen($label);
        $width_right="70%";
        if($label==null){
            $width_right="100%";
            $padding="padding-left:0";
            return " <!-- _field_intr 2 --><tr><td style='width:100%;padding-bottom:10px;$padding;'>$formatted_field</td></tr>";
        }

        $trace=@debug_backtrace();
        $last=$trace[1]["function"];

        return "
            <!-- _field_intr 3 -->
            <!-- Label: $labelstr $last -->
			<tr>
				<td style='width:30%;text-align:right;vertical-align:middle;padding-bottom:10px;' nowrap><span class=labelform id='$id' style='text-transform:capitalize !important'>$label</span></td>
				<td style='width:$width_right;padding-bottom:10px;{$padding}'>$formatted_field</td>
			</tr>
			 <!-- END Label: $labelstr -->";
    }

    public function field_slider($name,$label,$value,$explain=null,$MAIN_ARRAY=array(),$OnUpdatejs=null,$direction="ltr"){
        $label_js=$this->javascript_parse_text($label);
        $label=$this->_ENGINE_parse_body($label);
        $label=str_replace(" ", "&nbsp;", $label);
        if($OnUpdatejs<>null){$OnUpdatejs="$OnUpdatejs(zvalues);";}
        $id=md5("$name$this->suffixid");
        $h=array();
        $h[]="<input type='hidden' value='$value' id='$id' name='$id'>";
        $h[]="<div id='slide_$id' class='noUi-target noUi-horizontal' style='margin-bottom:30px'>";
        $h[]="<script>";
        $h[]="var slide_$id = document.getElementById('slide_$id');";

        $h[]="noUiSlider.create(slide_$id, {
			start: $value,
			behaviour: 'snap',
			connect: 'upper',
			direction: '$direction',
			snap:true,
			step: {$MAIN_ARRAY["STEP"]},
			tooltips: true,
			range: {";

        foreach ($MAIN_ARRAY["RANGE"] as $key=>$val){$r[]="'$key':  $val";}
        $h[]=@implode(",", $r);

        $h[]="},
				pips: {
		mode: 'values',
		values: [".@implode(",", $MAIN_ARRAY["VALUES"])."],
		density: 4
		}
		});
		slide_$id.noUiSlider.on('change', function ( zvalues, handle ) {
		    if(!zvalues){return;}
			document.getElementById('$id').value=zvalues;
			$OnUpdatejs
		});
		$OnUpdatejs
		";
        $h[]="</script>";

        if($this->FORM_IN_ARRAY){return "<td>".@implode("\n", $h)."</td>";}
        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,@implode("\n",$h),$explain);

    }

    public function field_array_hash_simple($hash,$name,$label,$value,$notnull=false,$js=null):string{
        $label_js=$this->javascript_parse_text($label);
        $value=trim($value);
        $label=$this->_ENGINE_parse_body($label);
        $label=str_replace(" ", "&nbsp;", $label);
        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $t=microtime();
        $OnChange=null;
        $def=null;
        $selected=false;
        if($value==null){$def="selected";$selected=true;}
        if($js<>null){
            if(strpos($js,"(")==0){
                $js="$js('$id')";
            }
            $OnChange="OnChange=\"javascript:$js\"";
        }
        $h[]="<!-- javascript : ". strlen($js)." value $value -->";
        $h[]="<select name=\"$name\" class=\"form-control m-b\" id='$id' style='font-size:14px' $OnChange> ";
        $h[]="\t<option value=\"\" $def>".$this->javascript_parse_text("{select} - {none}")."</option>";

        foreach ($hash as $val=>$key){
            $def=null;
            $val=trim($val);
            if(strpos($key, "}")>0){$key=$this->javascript_parse_text($key);}
            $lenght=strlen($key);
            if($lenght>50){$key=substr($key, 0,47)."...";}

            if(!$selected){
                if(strtolower($val)==strtolower($value)){
                    $def="selected";
                    $selected=true;
                    $h[]="\t<option value=\"$val\" selected>$key</option>";
                    continue;
                }
            }
            $h[]="\t<option value=\"$val\">$key</option>";
        }

        $h[]="</select>";


        $h[]="";

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var {$name}value=document.getElementById('$id').value;";
            $this->XHR[]="if({$name}value.length==0){ document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_js</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,@implode("\n",$h));
    }

    public function field_array_checkboxes2Columns($hash,$name,$value,$notnull=false){
        if(!is_array($hash)){$hash=array();}
        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $t=microtime();
        $def=null;
        $selected=false;
        $java=null;
        $javaend=null;
        if(trim($value)==null){$def="selected";$selected=true;}
        $this->XHR[]="var _{$name}_value='';";
        $icheck_style="font-weight:normal;";

       $border="border-bottom: 5px solid transparent; ";
       $slice="padding-bottom:10px;margin-bottom:10px;border-bottom: 1px solid transparent;border-image: linear-gradient(0.25turn, rgba(2,0,36,1) 0%, rgba(255,255,255,1) 80%); border-image-slice: 1;";

        $table_style="margin-bottom:10px;";

        $FIELDSCHK=array();
        $switch=1;
        $i=0;
        foreach ($hash as $val=>$explain){
            if(is_array($explain)){continue;}
            if($val=="DISABLED_FORMS"){continue;}
            $checked=null;
            $i++;
            $id=md5($id.$val.$i);
            VERBOSE("field_array_checkboxes checked='$checked'", __LINE__);
            if("$val"=="$value"){$checked="checked";}
            if(is_numeric($val)){
                if(is_numeric($value)){
                    if($val==$value){$checked="checked";}
                }
            }else{
                if("$val"=="$value"){$checked="checked";}
            }

            if($switch==1){$switch=0;}else{$switch=1;}

            $swithsA=array();
            $swithsA[]="<!-- TABLE SWITCH $switch -->";
            $swithsA[]="<table style='$table_style'>";
            $swithsA[]="<tr>";
            $swithsA[]="<td style='width:1%;padding-right:15px;vertical-align:top'>";
            $swithsA[]="<div class='i-checks' style='$icheck_style'><input type='radio' id='$id' value='$val' name='$name' $checked></div>";
            $swithsA[]="</td>";
            $swithsA[]="<td style='width:99%'><div style='$slice'><label style='font-weight:normal'>$explain</label></div></td>";
            $swithsA[]="</tr>";
            $swithsA[]="</table>";
            $FIELDSCHK[$switch][]=@implode("\n",$swithsA);

           /* $FIELDSCHK[$switch][]="<div class='i-checks' style='margin-bottom:10px;font-weight:normal'><label> <input type='radio' id='$id' value='$val' name='$name' $checked> <i></i> <span style='font-weight:normal'>$explain </span></label></div>";
           */
            $this->XHR[]="if(document.getElementById('$id').checked){ _{$name}_value='$val';}";

        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="if( _{$name}_value.length ==0){ document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>{lists}</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }
        $this->XHR[]="XHR.appendData('$name',  _{$name}_value);";
        $html[]="
		<script>
			$('.i-checks input').iCheck( {
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green' 
			});
		</script>";
        if(!is_array($FIELDSCHK[0])){$FIELDSCHK[0]=array();}
        if(!is_array($FIELDSCHK[1])){$FIELDSCHK[1]=array();}
        $html1[]="<table style='margin-top:10px'>";
        $html1[]="<tr>";
        $html1[]="<td style='width:50%;padding-right:5px;border-right:2px solid #CCCCCC;vertical-align:top;'>".@implode("\n",$FIELDSCHK[0])."</td>";
        $html1[]="<td style='width:50%;padding-left:5px;vertical-align:top;'>".@implode("\n",$FIELDSCHK[1])."</td>";
        $html1[]="</tr>";
        $html1[]="</table>";
        $html1[]=@implode("\n",$html);
        return $this->_field_intr("TABLE!",@implode("\n",$html1));
    }

    public function field_array_checkboxes($hash,$name,$value,$notnull=false){
        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $t=microtime();
        $def=null;
        $selected=false;
        $java=null;
        $javaend=null;
        if(trim($value)==null){$def="selected";$selected=true;}
        $this->XHR[]="var _{$name}_value='';";

        $html[]="<div style='margin-top:20px'>";
        $i=0;
        foreach ($hash as $val=>$explain){
            if(is_array($explain)){continue;}
            if($val=="DISABLED_FORMS"){continue;}
            $checked=null;
            $i++;
            $id=md5("$id.$val.$i");
            VERBOSE("field_array_checkboxes checked='$checked'", __LINE__);
            if("$val"=="$value"){$checked="checked";}
            if(is_numeric($val)){
                if(is_numeric($value)){
                    if($val==$value){$checked="checked";}
                }
            }else{
                if("$val"=="$value"){$checked="checked";}
            }


            VERBOSE("field_array_checkboxes $val==$value ? $checked", __LINE__);
            $html[]="<div class='i-checks' style='margin-bottom:10px;font-weight:normal'><label> <input type='radio' id='$id' value='$val' name='$name' $checked> <i></i> <span style='font-weight:normal'>$explain </span></label></div>";
            $this->XHR[]="if(document.getElementById('$id').checked){ _{$name}_value='$val';}";

        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="if( _{$name}_value.length ==0){ document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>{lists}</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }
        $this->XHR[]="XHR.appendData('$name',  _{$name}_value);";
        $html[]="</div>
		<script>
			$('.i-checks input').iCheck( {
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green' 
			});
		</script>";
        return $this->_field_intr("&nbsp;",@implode("\n",$html));

    }

    public function ProxyPortsDirections(){
        $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        $HaClusterProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyPort"));

        $PortDirectionS[0]="{all_methods}";
        if($HaClusterClient==0) {
            $PortDirectionS[1] = "{standard_method}";
            $PortDirectionS[2] = "{transparent_method}";
            $PortDirectionS[3] = "{smartphones_port}";
        }
        $ip=new networking();
        $interfaces=$ip->Local_interfaces();
        unset($interfaces["lo"]);

        $arrayNICZ[null]="0.0.0.0";


        foreach ($interfaces as $eth){
            if(preg_match("#^gre#", $eth)){continue;}
            $nic=new system_nic($eth);
            if($nic->enabled==0){continue;}
            $arrayNICZ[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        }

        $ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $sql="SELECT * FROM proxy_ports WHERE enabled=1";
        $resultsPorts = $ql->QUERY_SQL($sql);
        foreach ($resultsPorts as $index=>$lignePorts){
            $eth=$lignePorts["nic"];
            $ipaddr="";
            if(isset($arrayNICZ[$eth])) {
                $ipaddr = $arrayNICZ[$eth];
            }
            $port=$lignePorts["port"];
            $IDPort=$lignePorts["ID"];
            $PortDirectionS[$IDPort]="{port} $port [$ipaddr]";
            if($HaClusterClient==1){
                if($port==$HaClusterProxyPort){
                    $PortDirectionS[$IDPort]="HaCluster: {port} $port {standard_method}";
                }
            }
        }
        if($HaClusterClient==1) {
            $HaClusterTproxy = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTproxy");
            $HaClusterTproxyEnabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode");
            if($HaClusterTproxyEnabled==1){
                $PortDirectionS[500]="HaCluster: {port} $HaClusterTproxy {transparent_method}";

            }

        }

        return $PortDirectionS;
    }

    public function field_proxy_ports($name,$label,$value,$notnull=false,$explain=null,$OnChange=null,$defnull=true){
        $PortDirectionS=$this->ProxyPortsDirections();
        return $this->field_array_hash($PortDirectionS,$name,$label,$value,$notnull,$explain,$OnChange,$defnull);

    }

    public function field_geoip_countries($name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true){
        include_once(dirname(__FILE__)."/class.geoip-db.inc");
        $COUNTRIES=GEO_IP_COUNTRIES_LIST();
        return $this->field_array_hash($COUNTRIES,$name,$label,$value,$notnull,$explain,$OnChange,$defnull);
    }

    public function field_read_get_value($pattern,$hash=array()):string{
        $valueText="";
        $valueInside=null;
        VERBOSE("Pattern = [$pattern]",__LINE__);
        if(preg_match("#^sysctl:(.+)#",$pattern,$re)){
            $valueInside=$GLOBALS["CLASS_SOCKETS"]->KERNEL_GET($re[1]);
            VERBOSE("valueInside of $re[1] = [$valueInside]",__LINE__);
        }
        if(count($hash)>0){
            VERBOSE("valueInside of hash count = [".count($hash)."]",__LINE__);
            $valueText=$hash[$valueInside];
        }else{
            $valueText=$valueInside;
        }

        return $this->_ENGINE_parse_body($valueText);

    }

    public function field_read_numeric_setinfo($name,$label=null,$value=0,$explain=null,$security=null,$frmwcmd=null){
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $id=md5("field-read-$name");
        $URL=null;
        $label_text=$label;
        if(preg_match("#^nonull:(.+)#",$label_text,$re)){
            $label_text=$re[1];
        }
        $edit=$this->icon_edit_field("2x:Stx$id();",$security);
        list($label_text,$href_label,$over)=$this->js_help($label_text,$explain,$URL);
        if($href_label<>null){
            $this->FieldTableBack[]=$over;
            $label_text="$href_label$label_text</a>";
        }
        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px' id='$id'>";
        $INT[] = "\t<td style='width:1%;padding-left:10px'><i class='fa-duotone fa-solid fa-check fa-2x'></i></td>";
        $INT[] = "\t<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>";
        $INT[] = "<span style='color:#676a6c;font-size:large'>$label_text:</span>";
        $INT[] = "\t</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'>";
        $INT[]="<span id='f-$id'></span>";
        $INT[]="</td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
        $INT[] = "</tr>";
        $this->FieldTable[]=@implode($INT);

        $params[]=array();
        $params[]=$name;
        $params[]=$value;
        $params[]=false;
        $params[]="xsingle:$explain";
        $params[]="id:Stx{$id}Save";
        $params[]=$security;
        $params[]=$frmwcmd;
        $params_encode=base64_encode(serialize($params));

        $this->FieldTableJs[]="function Stx$id(){";
        if(!$this->IsSecurity($security)){$this->FieldTableJs[]="return;";}
        $this->FieldTableJs[]="UnloadCurrentForm%form();";
        $this->FieldTableJs[]="Loadjs('fw.fields.php?type=setinfo&field=field_numeric&params=$params_encode&id=f-$id');";
        $this->FieldTableJs[]="}";
        $this->FieldTableJs[]="";
        $this->FieldTableJs[]="function Stx{$id}Save(e,val){";
        $this->FieldTableJs[]="Loadjs('fw.fields.php?post-it=$params_encode&f-id=$id&id='+e+'&saved='+val+'&OnOpen=Stx$id')";
        $this->FieldTableJs[]="}";
        $this->FieldTableBack[]="LoadAjaxSilent('f-$id','fw.fields.php?value-decode=$params_encode&OnOpen=Stx$id');";

        return true;
    }

    public function field_read_array_hash_setinfo($hash,$name,$label,$value=null,$notnull=false,$explain=null,$security=null,$frmwcmd=null):bool{
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $id=md5("field-read-$name");
        $URL=null;
        $label_text=$label;
        if(preg_match("#^nonull:(.+)#",$label_text,$re)){
            $label_text=$re[1];
        }
        $edit=$this->icon_edit_field("2x:Stx$id();",$security);

        list($label_text,$href_label,$over)=$this->js_help($label_text,$explain,$URL);

        if($href_label<>null){
            $this->FieldTableBack[]=$over;
            $label_text="$href_label$label_text</a>";
        }
        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px' id='$id'>";
        $INT[] = "\t<td style='width:1%;padding-left:10px'><i class='fa-duotone fa-solid fa-check fa-2x'></i></td>";
        $INT[] = "\t<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>";
        $INT[] = "<span style='color:#676a6c;font-size:large'>$label_text:</span>";
        $INT[] = "\t</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'>";
        $INT[]="<span id='f-$id'></span>";
        $INT[]="</td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
        $INT[] = "</tr>";
        $this->FieldTable[]=@implode($INT);

        $params[]=$hash;
        $params[]=$name;
        $params[]=$value;
        $params[]=$notnull;
        $params[]="xsingle:$explain";
        $params[]="id:Stx{$id}Save";
        $params[]="$security";
        $params[]=$frmwcmd;
        $params_encode=base64_encode(serialize($params));

        $this->FieldTableJs[]="function Stx$id(){";
        if(!$this->IsSecurity($security)){$this->FieldTableJs[]="return;";}
        $this->FieldTableJs[]="UnloadCurrentForm%form();";
        $this->FieldTableJs[]="Loadjs('fw.fields.php?type=setinfo&field=field_array_hash&params=$params_encode&id=f-$id');";
        $this->FieldTableJs[]="}";
        $this->FieldTableJs[]="";
        $this->FieldTableJs[]="function Stx{$id}Save(e,val){";
        $this->FieldTableJs[]="Loadjs('fw.fields.php?post-it=$params_encode&f-id=$id&id='+e+'&saved='+val+'&OnOpen=Stx$id')";
        $this->FieldTableJs[]="}";
        $this->FieldTableBack[]="LoadAjaxSilent('f-$id','fw.fields.php?value-decode=$params_encode&OnOpen=Stx$id');";

        return true;
    }

    public function field_read_compile():string{

        $INT[]="<table class='table table-stripped' style='width:90%;margin-top:20px;margin-left:20px;'>";
        foreach ($this->FieldTable as $row){
            $INT[]=$row;
        }
        $INT[]="</table>";
        $INT[]="<script>";
        foreach ($this->FieldTableJs as $row){
            $INT[]=$row;
        }
        $INT[]="function UnloadCurrentForm%form(){";
        foreach ($this->FieldTableBack as $row){
            $INT[]=$row;
        }
        $INT[]="}";
        $INT[]="UnloadCurrentForm%form();";
        $INT[]="</script>";
        $data= @implode("\n",$INT);
        $data=str_replace("%form",time(),$data);
        return $data;
    }

    public function field_radio_hash($hash,$name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true):string{

        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $NameID=md5("$name$this->suffixid".serialize($hash));

        if($this->CLUSTER_CLI) {
            $PowerDNSEnableClusterSlave = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if ($PowerDNSEnableClusterSlave == 1) {
                $disabled = true;
            }
        }

        $h=array();
        foreach ($hash as $zvalue=>$text){
            $checked="";
            $disabledt="";
            if($zvalue==$value){
                $checked="checked=\"\"";
            }
            if($disabled){
                $disabledt="disabled=\"\"";
            }
            $h[]="<div class=\"i-checks\"><label> <input type=\"radio\" value=\"$zvalue\" $checked $disabledt name=\"$NameID\"><i></i> $text </label></div>";
        }

        $this->XHR[]="if(!document.getElementsByName('$NameID')){alert('$name:$NameID !!');return;};";
        $this->XHR[]="var radios$NameID = document.getElementsByName('$NameID');";
        $this->XHR[]="for(var i = 0; i < radios$NameID.length; i++) {";
        $this->XHR[]="\tif(radios$NameID"."[i].checked) {";
        $this->XHR[]="\t\tXHR.appendData('$name',  radios$NameID"."[i].value);";
        $this->XHR[]="\t\tbreak;";
        $this->XHR[]="\t}";
        $this->XHR[]="}";
        return $this->_field_intr($label,@implode("\n",$h),$explain);

    }

    public function field_array_hash_categories($name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true):string{

        if(!isset($_SESSION["field_array_hash_categories"])) {
            $q = new postgres_sql();
            $sql = "SELECT * FROM personal_categories WHERE free_category=0 order by categoryname";

            $results = $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo "<div class='alert alert-danger'>$q->mysql_error</div>";
                return false;
            }


            while ($ligne = pg_fetch_assoc($results)) {
                $category_id = $ligne["category_id"];
                if ($category_id == "234") {
                    $ligne["categoryname"] = "CloudFlare";
                }
                $categoryname = $ligne["categoryname"];
                if (preg_match("#^reserved#", $categoryname)) {
                    continue;
                }
                $_SESSION["field_array_hash_categories"][$category_id] = $categoryname;
            }

        }
        return $this->field_array_hash( $_SESSION["field_array_hash_categories"], $name, $label, $value,$notnull,$explain,$OnChange,$defnull);
    }

    public function field_array_buttons($hash,$name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true):string{
        if(!is_array($hash)){$hash=array();}
        $icon1=ico_check;
        $icon2="far fa-square-full";
        $id=md5("$name$this->suffixid");

        $topbuttons[]= "<input type=hidden name=\"$name\" value=\"$value\" id='$id'>";
        $scr=array();
        $scr[]="<script>";
        $scr[]="function Set$id(value,Currid){";
        if($this->CLUSTER_CLI){
            $scr[]="return;";
        }
        $theok=base64_encode("<i class='$icon1'></i>");
        $topbuttons[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
        foreach ($hash as $Arrvalue=>$Arrlabel){
            $icon_class=$icon2;
            $btnid=md5("$name$this->suffixid$Arrlabel$Arrvalue");
            $label_class="btn-default";
            $icondef="<i class='$icon_class'></i>";
            $icok=$icondef;
            if($Arrvalue==$value){
                $label_class="btn-primary";
                $icok="<i class='$icon1'></i>";
            }
            
            $topbuttons[]= "<label class=\"btn btn-xs $label_class\" id=\"$btnid\" OnClick=\"Set$id('$Arrvalue','$btnid')\" style='margin-top:2px'>
                <span id='ico-$btnid'>$icok</span> $Arrlabel </label>";
            $scr[]="document.getElementById('$btnid').className = 'btn btn-xs btn-default';";
            $scr[]="document.getElementById('ico-$btnid').innerHTML = base64_decode('".base64_encode($icondef)."');";


        }
        $topbuttons[]="</div>";
        $scr[]="document.getElementById(Currid).className = 'btn btn-xs btn-primary';";
        $scr[]="document.getElementById('ico-'+Currid).innerHTML = base64_decode('$theok');";
        $scr[]="document.getElementById('$id').value=value;";
        $scr[]="}";
        $scr[]="</script>";
        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        $full=@implode("\n",$topbuttons)."\n".@implode("\n",$scr);
        return $this->_field_intr($label,$full,$explain);
    }


    public function field_array_hash($hash,$name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true):string{
        if(!isset($GLOBALS["StandardDropDown"])){
            $GLOBALS["StandardDropDown"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StandardDropDown"));
        }
        $StandardDropDown=$GLOBALS["StandardDropDown"];
        if(isset($_COOKIE["StandardDropDown"])){$StandardDropDown=intval($_COOKIE["StandardDropDown"]);}

        $return_single=false;
        $style=null;
        if(!is_null($explain)) {
            if (preg_match("#xsingle:(.+)#", $explain, $re)) {
                $return_single = true;
                $explain = $re[1];
                $style = " style='font-size:large !important'";
            }
        }
        if(!is_null($value)) {
            if(is_array($value)){
                $value=serialize($value);
            }
            if (preg_match("#^sysctl:(.+)#", $value, $re)) {
                $value=$GLOBALS["CLASS_SOCKETS"]->KERNEL_GET($re[1]);
                VERBOSE("sysctl Value *$value*", __LINE__);
            }
        }


        if(!isset($_SERVER["HTTP_USER_AGENT"])){$_SERVER["HTTP_USER_AGENT"]="unknown";}
        $FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]).".StandardDropDown";
        if(is_file("/etc/artica-postfix/settings/Daemons/$FileCookyKey")){$StandardDropDown=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("$FileCookyKey"));}


        if(!is_array($hash)){$hash=array();}
        if(isset($hash[-1])){$defnull=false;}

        
        if($StandardDropDown==1){
            return $this->field_array_hash_simple($hash, $name, $label, $value,$OnChange);
        }

        if(preg_match("#nonull:(.+)#",$label,$re)){
            $label=$re[1];
            $defnull=false;

        }

        $label_js=$this->javascript_parse_text($label);
        $label=$this->_ENGINE_parse_body($label);
        $label=str_replace(" ", "&nbsp;", $label);

        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $def=null;
        $selected=false;
        $java=null;
        if(!is_null($value)){
            $value=trim($value);
        }
        if($value==null){$def="selected";$selected=true;}

        $h[]="<select data-placeholder=\"{$label}...\" class=\"chosen-select\"$style id='$id' tabindex=\"2\" $java>";

        if($defnull) {
            if (!isset($hash[null])) {
                $h[] = "\t<option value=\"\"$style $def>" . $this->_ENGINE_parse_body("{default_or_null}") . "</option>";
            }
        }

        foreach ($hash as $val=>$key){
            $def=null;
            if(strpos($key, "}")>0){$key=$this->_ENGINE_parse_body($key);}
            $lenght=strlen($key);
            if($lenght>70){$key=substr($key, 0,67)."...";}
            //$h[]="<!-- $key,$val:$value  -->";
            if(!$selected){
                if(strtolower($val)==strtolower($value)){
                    $h[]="<!-- OK  -->";
                    $def="selected";
                    $selected=true;
                    $h[]="\t<option value=\"$val\"$style selected>$key</option>";
                    continue;
                }
            }
            $h[]="\t<option value=\"$val\"$style>$key</option>";
        }

        $h[]="</select>";


        $h[]="<script>";
        $h[]="function Choosen$id(){";
        $h[]="\t$('#$id').chosen({width: \"99%\"});";
        if($return_single) {
            $h[] = "\t$(\".chosen-single span\").css( \"font-size\",\"large\");";
        }
        if($OnChange<>null){
            if(preg_match("#id:(.+)#",$OnChange)){
                $h[]="\t$('#$id').chosen().change(function(){";
                $h[]="\t\tvar id = $(this).val();";
                $h[]="\t\t$OnChange('$id',id);";
                $h[]="\t } );";
            }else {
                $h[] = "\t$('#$id').chosen().change(function(){";
                $h[] = "\t\tvar id = $(this).val();";
                $h[] = "\t\t$OnChange(id);";
                $h[] = "\t } );";
            }
        }
        $this->CHOSENS[]=$id;
        //$h[]="$('#$id').chosen().trigger('chosen:updated');";
        $h[]="}";
        $h[]="setTimeout (\"Choosen$id()\", 1000);";
        $h[]="</script>";


        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $h=array();
                $h[]= "<div class=\"input-group\"><input type=\"hidden\" id='$id' value=\"$value\">
                <strong>$value</strong>
                </div>";

            }
        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var {$name}value=document.getElementById('$id').value;";
            $this->XHR[]="if({$name}value.length==0){ document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_js</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        if($return_single){return @implode("\n",$h);}
        return $this->_field_intr($label,@implode("\n",$h),$explain);
    }

    public function field_checkbox_disbaleON($name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null){
        $URL            = null;
        $checked        = null;
        $status_label   = null;
        $button         = null;
        $OnClick        = null;
        $orgval         = $value;
        $AUTOSAVE       = false;
        $button_opts    = null;
        $re             = array();
        $button_help=null;
        if(is_array($disabled)){
            $explain=$disabled;
            $disabled=false;

        }
        if(preg_match("#^url:(.+?);(.*)#",trim($label),$re)){
            $label=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }

        if(preg_match("#^span:(.+)#",$label,$re)){
            $label=$re[1];
            preg_match("#\{(.+?)\}#",$label,$re);
            $status_label="<span id='{$re[1]}'></span>";
        }

        if(preg_match("#(.+?):js:(.+)#",$label,$re)){
            $label=$re[1];
            $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
            $bt_js="OnClick=\"{$re[2]}\"";

            $button_opts="<span class=\"label label-primary\" $pointers $bt_js>{options}</span>";
        }



        if(!is_numeric($value)){
            if($value=="yes"){$value=1;}
            if($value=="no"){$value=0;}
        }
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        if(is_array($explain)){
            if(isset($explain["AUTOSAVE"])){$AUTOSAVE=$explain["AUTOSAVE"];}
            if($explain["BUTTON"]==true){
                if($explain["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$explain["BUTTON_JS"]}\"";}
                $button="<span class=\"label label-primary\" $pointers $bt_js>{$explain["BUTTON_CAPTION"]}...</span>";

            }
            if(isset($explain["EXPLAIN"])){$explain=$explain["EXPLAIN"];}
        }

        if(preg_match("#^url:(.+?);(.*)#",trim($explain),$re)){
            $explain=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";
        }
        if(preg_match("#^url:(.+)#",$explain,$re)){
            $explain=null;
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }


        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if($value==1){$checked="checked";}
        if(!is_bool($disable_form)){
            $this->JS[]="// $name: disable_form Not boolean";
            $this->JS[]="function CheckBox$id(){";
            if(strpos($disable_form, ",")>0){
                $FieldsTocheckZ=explode("," ,$disable_form);
            }else{
                $FieldsTocheckZ[]=$disable_form;
            }

            foreach ($FieldsTocheckZ as $fieldz){
                $diz=md5($fieldz.$this->suffixid);
                // $this->JS[]="alert('$diz->'+$('#$diz').hasClass('chosen-select'));";
                $this->JS[]="\tif(!document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',false).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";

                $this->JS[]="\tif(document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',true).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";


                $this->JS[]="\tif(!document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=false;}}";
                $this->JS[]="\tif(document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=true;}}";
            }

            $this->JS[]="}";
            $disable_form=false;
            $OnClick=" OnClick=\"CheckBox$id();\"";
            $this->FormStartupScripts[]="CheckBox$id();";
        }else{
            $this->JS[]="// $name: disable_form ($disable_form) boolean";
        }

        if($disabled){$disable_form=false;}

        if($disable_form){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"CheckFormCheckBox('$id')\"";
            $this->JS[]="CheckFormCheckBox('$id');";

        }

        if($AUTOSAVE){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"Save{$this->form_id}();\"";

        }
        $xtable=array();
        if(is_null($status_label)){$status_label="";}
        if(is_null($button_opts)){$button_opts="";}
        if(is_null($button)){$button="";}

        $strlen=strlen($status_label)+strlen($button_opts)+strlen($button);

        if($strlen>3){
            $xtable[]="<div id='xtable' style='width:7%;float:right;margin-top:-36px'>";
            $xtable[]="<table style='width:100%'>";
            $xtable[]="<tr>";
            if(strlen($status_label)>3){
                $xtable[]="<td style='width:33%'>$status_label</td>";
            }
            if(strlen($button_opts)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button_opts</td>";
            }
            if(strlen($button)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button</td>";
            }
            $xtable[]="</tr>";
            $xtable[]="</table>";
            $xtable[]="</div>";
        }

        $css=null;
        //	if($this->FORM_IN_ARRAY){$css=" style='padding-top:5px'";}
        $html="
			<!-- name=$name value == '$orgval/$value' -->
			<div class=\"switch\"$css>
					<div class=\"onoffswitch\">
						<input type=\"checkbox\" name=\"$name\" $checked class=\"onoffswitch-checkbox\" id=\"$id\" $OnClick>
						<label class=\"onoffswitch-label\" for=\"$id\">
							<span class=\"onoffswitch-inner\"></span>
							<span class=\"onoffswitch-switch\"></span>
						</label>
					</div>
				</div>
    <!-- options label -->
   ". @implode("\n",$xtable)."
	<!-- options buttons OFF -->
	";

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $disabled=true;

            }
        }


        if($security<>null){
            if(!$this->IsSecurity($security)){$disabled=true;}
        }
        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$html,$explain,false,$URL);

        }



        $this->XHR[]="\tif(document.getElementById('$id').checked){XHR.appendData('$name',  1);}else{XHR.appendData('$name',  0);}";
        return $this->_field_intr($label,$html,$explain,$disable_form,$URL);
    }
    public function field_checkbox($name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null): string
    {
        $URL            = null;
        $checked        = null;
        $status_label   = null;
        $button         = null;
        $OnClick        = null;
        $orgval         = $value;
        $AUTOSAVE       = false;
        $button_opts    = null;
        $re             = array();
        $button_help=null;
        if(is_array($disabled)){
            $explain=$disabled;
            $disabled=false;

        }
        if(preg_match("#^url:(.+?);(.*)#",trim($label),$re)){
            $label=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }

        if(preg_match("#^span:(.+)#",$label,$re)){
            $label=$re[1];
            preg_match("#\{(.+?)\}#",$label,$re);
            $status_label="<span id='{$re[1]}'></span>";
        }

        if(preg_match("#(.+?):js:(.+)#",$label,$re)){
            $label=$re[1];
            $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
            $bt_js="OnClick=\"{$re[2]}\"";

            $button_opts="<span class=\"label label-primary\" $pointers $bt_js>{options}</span>";
        }



        if(!is_numeric($value)){
            if($value=="yes"){$value=1;}
            if($value=="no"){$value=0;}
        }
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        if(is_array($explain)){
            if(isset($explain["AUTOSAVE"])){$AUTOSAVE=$explain["AUTOSAVE"];}
            if($explain["BUTTON"]==true){
                if($explain["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$explain["BUTTON_JS"]}\"";}
                $button="<span class=\"label label-primary\" $pointers $bt_js>{$explain["BUTTON_CAPTION"]}...</span>";

            }
            if(isset($explain["EXPLAIN"])){$explain=$explain["EXPLAIN"];}
        }

        if(!is_null($explain)){
            $explain=trim($explain);
        }else{
            $explain="";
        }

        if(preg_match("#^url:(.+?);(.*)#",$explain,$re)){
            $explain=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";
        }
        if(preg_match("#^url:(.+)#",$explain,$re)){
            $explain=null;
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }


        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if($value==1){$checked="checked";}
        if(!is_bool($disable_form)){
            $this->JS[]="// $name: disable_form Not boolean";
            $this->JS[]="function CheckBox$id(){";
            if(strpos($disable_form, ",")>0){
                $FieldsTocheckZ=explode("," ,$disable_form);
            }else{
                $FieldsTocheckZ[]=$disable_form;
            }

            foreach ($FieldsTocheckZ as $fieldz){
                $diz=md5($fieldz.$this->suffixid);
               // $this->JS[]="alert('$diz->'+$('#$diz').hasClass('chosen-select'));";
                $this->JS[]="\tif(document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',false).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";

                $this->JS[]="\tif(!document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',true).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";


                $this->JS[]="\tif(document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=false;}}";
                $this->JS[]="\tif(!document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=true;}}";
            }

            $this->JS[]="}";
            $disable_form=false;
            $OnClick=" OnClick=\"CheckBox$id();\"";
            $this->FormStartupScripts[]="CheckBox$id();";
        }else{
            $this->JS[]="// $name: disable_form ($disable_form) boolean";
        }

        if($disabled){$disable_form=false;}

        if($disable_form){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"CheckFormCheckBox('$id')\"";
            $this->JS[]="CheckFormCheckBox('$id');";

        }

        if($AUTOSAVE){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"Save{$this->form_id}();\"";

        }
        $xtable=array();
        if(is_null($status_label)){$status_label=""; }
        if(is_null($button_opts)){$button_opts="";}
        if(is_null($button)){$button="";}



        $strlen=strlen($status_label)+strlen($button_opts)+strlen($button);

        if($strlen>3){
            $xtable[]="<div id='xtable' style='width:7%;float:right;margin-top:-36px'>";
            $xtable[]="<table style='width:100%'>";
            $xtable[]="<tr>";
            if(strlen($status_label)>3){
                $xtable[]="<td style='width:33%'>$status_label</td>";
            }
            if(strlen($button_opts)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button_opts</td>";
            }
            if(strlen($button)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button</td>";
            }
            $xtable[]="</tr>";
            $xtable[]="</table>";
            $xtable[]="</div>";
        }

        $css=null;
        //	if($this->FORM_IN_ARRAY){$css=" style='padding-top:5px'";}
        $html="
			<!-- name=$name value == '$orgval/$value' -->
			<div class=\"switch\"$css>
					<div class=\"onoffswitch\">
						<input type=\"checkbox\" name=\"$name\" $checked class=\"onoffswitch-checkbox\" id=\"$id\" $OnClick>
						<label class=\"onoffswitch-label\" for=\"$id\">
							<span class=\"onoffswitch-inner\"></span>
							<span class=\"onoffswitch-switch\"></span>
						</label>
					</div>
				</div>
    <!-- options label -->
   ". @implode("\n",$xtable)."
	<!-- options buttons OFF -->
	";

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $disabled=true;

            }
        }


        if($security<>null){
            if(!$this->IsSecurity($security)){$disabled=true;}
        }
        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$html,$explain,false,$URL);

        }



        $this->XHR[]="\tif(document.getElementById('$id').checked){XHR.appendData('$name',  1);}else{XHR.appendData('$name',  0);}";
        return $this->_field_intr($label,$html,$explain,$disable_form,$URL);
    }

    public function field_checkbox_negative($name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null):string{
        $URL            = null;
        $checked        = null;
        $status_label   = null;
        $button         = null;
        $OnClick        = null;
        $orgval         = $value;
        $AUTOSAVE       = false;
        $button_opts    = null;
        $re             = array();
        $button_help=null;
        if(is_array($disabled)){
            $explain=$disabled;
            $disabled=false;

        }
        if(preg_match("#^url:(.+?);(.*)#",trim($label),$re)){
            $label=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }

        if(preg_match("#^span:(.+)#",$label,$re)){
            $label=$re[1];
            preg_match("#\{(.+?)\}#",$label,$re);
            $status_label="<span id='{$re[1]}'></span>";
        }

        if(preg_match("#(.+?):js:(.+)#",$label,$re)){
            $label=$re[1];
            $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
            $bt_js="OnClick=\"{$re[2]}\"";

            $button_opts="<span class=\"label label-primary\" $pointers $bt_js>{options}</span>";
        }



        if(!is_numeric($value)){
            if($value=="yes"){$value=1;}
            if($value=="no"){$value=0;}
        }
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        if(is_array($explain)){
            if(isset($explain["AUTOSAVE"])){$AUTOSAVE=$explain["AUTOSAVE"];}
            if($explain["BUTTON"]==true){
                if($explain["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$explain["BUTTON_JS"]}\"";}
                $button="<span class=\"label label-primary\" $pointers $bt_js>{$explain["BUTTON_CAPTION"]}...</span>";

            }
            if(isset($explain["EXPLAIN"])){$explain=$explain["EXPLAIN"];}
        }

        if(!is_null($explain)){
            $explain=trim($explain);
        }else{
            $explain="";
        }

        if(preg_match("#^url:(.+?);(.*)#",$explain,$re)){
            $explain=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";
        }
        if(preg_match("#^url:(.+)#",$explain,$re)){
            $explain=null;
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }


        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if($value==1){$checked="checked";}
        if(!is_bool($disable_form)){
            $this->JS[]="// $name: disable_form Not boolean";
            $this->JS[]="function CheckBox$id(){";
            if(strpos($disable_form, ",")>0){
                $FieldsTocheckZ=explode("," ,$disable_form);
            }else{
                $FieldsTocheckZ[]=$disable_form;
            }

            foreach ($FieldsTocheckZ as $fieldz){
                $diz=md5($fieldz.$this->suffixid);
                // $this->JS[]="alert('$diz->'+$('#$diz').hasClass('chosen-select'));";
                $this->JS[]="\tif(!document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',false).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";

                $this->JS[]="\tif(document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',true).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";



                $this->JS[]="\t}";


                $this->JS[]="\tif(!document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=false;}}";
                $this->JS[]="\tif(document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=true;}}";
            }

            $this->JS[]="}";
            $disable_form=false;
            $OnClick=" OnClick=\"CheckBox$id();\"";
            $this->FormStartupScripts[]="CheckBox$id();";
        }else{
            $this->JS[]="// $name: disable_form ($disable_form) boolean";
        }

        if($disabled){$disable_form=false;}

        if($disable_form){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"CheckFormCheckBox('$id')\"";
            $this->JS[]="CheckFormCheckBox('$id');";

        }

        if($AUTOSAVE){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"Save{$this->form_id}();\"";

        }
        $xtable=array();
        if(is_null($status_label)){$status_label=""; }
        if(is_null($button_opts)){$button_opts="";}
        if(is_null($button)){$button="";}



        $strlen=strlen($status_label)+strlen($button_opts)+strlen($button);

        if($strlen>3){
            $xtable[]="<div id='xtable' style='width:7%;float:right;margin-top:-36px'>";
            $xtable[]="<table style='width:100%'>";
            $xtable[]="<tr>";
            if(strlen($status_label)>3){
                $xtable[]="<td style='width:33%'>$status_label</td>";
            }
            if(strlen($button_opts)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button_opts</td>";
            }
            if(strlen($button)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button</td>";
            }
            $xtable[]="</tr>";
            $xtable[]="</table>";
            $xtable[]="</div>";
        }

        $css=null;
        //	if($this->FORM_IN_ARRAY){$css=" style='padding-top:5px'";}
        $html="
			<!-- name=$name value == '$orgval/$value' -->
			<div class=\"switch\"$css>
					<div class=\"onoffswitch\">
						<input type=\"checkbox\" name=\"$name\" $checked class=\"onoffswitch-checkbox\" id=\"$id\" $OnClick>
						<label class=\"onoffswitch-label\" for=\"$id\">
							<span class=\"onoffswitch-inner\"></span>
							<span class=\"onoffswitch-switch\"></span>
						</label>
					</div>
				</div>
    <!-- options label -->
   ". @implode("\n",$xtable)."
	<!-- options buttons OFF -->
	";

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $disabled=true;

            }
        }


        if($security<>null){
            if(!$this->IsSecurity($security)){$disabled=true;}
        }
        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$html,$explain,false,$URL);

        }



        $this->XHR[]="\tif(document.getElementById('$id').checked){XHR.appendData('$name',  1);}else{XHR.appendData('$name',  0);}";
        return $this->_field_intr($label,$html,$explain,$disable_form,$URL);
    }


    public function field_checkbox_toogle($name,$label,$value,$array){

        $checked=null;
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if($value==$array[1]["VALUE"]){

            $checked="checked";
            $js="$('#$id').bootstrapToggle('on')";
        }else{
            $js="$('#$id').bootstrapToggle('off')";
        }
        $html="
		<!-- Current data = '$value' ==> {$array[1]["VALUE"]} check:$checked -->
		<input checked data-toggle=\"toggle\" id='$id'
		data-on=\"{$array[1]["LABEL"]}\" data-off=\"{$array[0]["LABEL"]}\" 
		data-onstyle='success' 
		data-size='mini'
		data-offstyle='danger' type='checkbox'>
		<script> $(function() { 
			$('#$id').bootstrapToggle();
			$js
		 }
		); </script>";
        $this->XHR[]="if(document.getElementById('$id').checked){XHR.appendData('$name',  '{$array[1]["VALUE"]}');}else{XHR.appendData('$name',  '{$array[0]["VALUE"]}');}";
        return $this->_field_intr($label,$html);
    }


    public function field_clock($name,$label,$value,$id=null){
        if($id==null){$id=md5("$name$this->suffixid");}
        $this->DISABLE_ALL[]=$id;
        $html="
<div class='input-group' id='clock-$id' data-autoclose='true'>
	<input type='time' class='form-control' min=\"00:00:00\" max=\"23:59:59\" value='$value' id='$id' style='line-height: 20px !important;'>
	<span class='input-group-addon'><span class=\"fa fa-clock\"></span></span>
</div>";
        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,$html);

    }

    public function LeftMenu($array):string{
        $PAGE=trim($array["PAGE"]);
        $FILENAME=$PAGE;
        if(preg_match("#^(.+?)\?#",$PAGE,$re)){$FILENAME=$re[1];}
        if(!is_file("/usr/share/artica-postfix/$FILENAME")){return "<!-- $FILENAME  no such file -->";}


        $ICON=$array["ICO"];
        $TEXT=$array["TEXT"];
        $fa="fa";
        if(preg_match("#^(.+?)\s+(.+)#", $ICON,$re)){$fa=$re[1];$ICON=$re[2];}
        $tt[]="<a href='#' OnClick=\"MenuRoot( $(this),'$PAGE'";

        if(isset($array["LEVEL3"])){
            $tt[]=",'{$array["LEVEL3"]["PAGE"]}'";
            $tt[]=",'{$array["LEVEL3"]["ID"]}'";

        }
        $tt[]=");\"><i class=\"$fa {$ICON}\"></i>&nbsp;";
        $tt[]="<span class=\"nav-label\">{$TEXT}</span></a>";
        $f[]="                <li>";
        $f[]="                  ".@implode("", $tt);
        if(isset($array["LEVEL3"])){
            $f[]="					<div id='{$array["LEVEL3"]["ID"]}'></div>";
        }
        $f[]="				 </li>";
        return @implode("\n",$f);
    }


    public function tabs_default($array):string{
        $js=null;
        $tabs=array();
        $content=array();
        $HIDE=array();
        if(!is_array($array)){
            $array=array();
        }
        $id=md5(serialize($array).microtime());
        $html[]="<div class=\"tabs-container\" id='$id'>";
        $html[]="<ul class=\"nav nav-tabs\" id='{$id}Tabs'>";


        $c=0;

        foreach ($array as $title => $url){
            $title=$this->_ENGINE_parse_body($title);
            if($c==0){
                $tabs[]="<li class=\"active\"><a data-toggle=\"tab\" href=\"#tab-$id-$c\" data-url=\"$url\"> $title</a></li>";
                $content[]="<div id=\"tab-$id-$c\" class=\"tab-pane active\"></div>";
                $HIDE[]="\tdocument.getElementById('tab-$id-$c').innerHTML=''";
                $js="\tLoadAjaxTiny('tab-$id-$c','$url')";
                $c++;
                continue;
            }
            $c++;

            if(preg_match("#javascript:#",$url)){
                $tabs[]="<li class=\"\"><a data-url='0' OnClick=\"$url\"> $title</a></li>";
                continue;
            }


            $tabs[]="<li class=\"\"><a data-toggle=\"tab\" href=\"#tab-$id-$c\" data-url=\"$url\"> $title</a></li>";
            $content[]="<div id=\"tab-$id-$c\" class=\"tab-pane\"></div>";
            $HIDE[]="\tdocument.getElementById('tab-$id-$c').innerHTML='';";
        }

        $html[]=@implode("\n", $tabs)."</ul>";
        $html[]=@implode("\n", $content);
        $html[]="</div>";
        $html[]="<script>";
        $html[]="$('#$id').on('click','.tablink,#{$id}Tabs a',function (e) {";
        $html[]="\te.preventDefault();";
        $html[]="\tvar url = $(this).attr(\"data-url\");";
        $html[]="\tif (typeof url !== \"undefined\") {";
        $html[]="\tif(url=='0'){ return;}";
        $html[]=@implode("\n", $HIDE);
        $html[]="\tvar pane = $(this), href = this.hash;";
        $html[]="\t$(href).load(url,function(result){ pane.tab('show');  });";
        $html[]="\t} else {";
        $html[]="\t$(this).tab('show');";
        $html[]="\t}";
        $html[]="});";
        $html[]=$js;
        $html[]="</script>";
        return 	$this->_ENGINE_parse_body(@implode("\n", $html));

    }

    public function status_array($title,$text=null,$status=true,$disabled=false,$version="-"):string{
        $status_text="{running}";
        if($disabled){
            if(preg_match("#^v[0-9\.]+#", $text)){
                $version=$text;
                $text="-";
            }
        }

        $status_class="label-primary";
        if(!$status){
            $status_class="label-danger";
            $status_text="{stopped}";
        }

        if($disabled){
            $status_class="";
            $status_text="{disabled}";

        }

        return $this->_ENGINE_parse_body("<tr>
				<td><span class='label $status_class'>$status_text</span></td>
				<td style='padding-left:3px'>$title</td>
				<td style='padding-left:3px'>$version</td>
				<td style='padding-left:3px'>$text</td>
				</tR>");


    }


    public function DAEMON_STATUS_ROW($key,$bsini,$additionnal):string{
        $uptime=null;
        $memory=null;
        $version=null;
        if(!isset($bsini->_params[$key]["service_name"])){
            return $this->status_array("{{$key}}","{unknown}",false,true);
        }
        if(!isset($bsini->_params[$key]["master_pid"])){$bsini->_params[$key]["master_pid"]=null;}
        if(!isset($bsini->_params[$key]["uptime"])){$bsini->_params[$key]["uptime"]=null;}
        if(!isset($bsini->_params[$key]["processes_number"])){$bsini->_params[$key]["processes_number"]=0;}
        if(!isset($bsini->_params[$key]["running"])){$bsini->_params[$key]["running"]=0;}
        if(!isset($bsini->_params[$key]["installed"])){$bsini->_params[$key]["installed"]=1;}
        if(!isset($bsini->_params[$key]["pattern_version"])){$bsini->_params[$key]["pattern_version"]=null;}
        if(!isset($bsini->_params[$key]["pattern_date"])){$bsini->_params[$key]["pattern_date"]=null;}
        if(!isset($bsini->_params[$key]["license_error"])){$bsini->_params[$key]["license_error"]=null;}
        if(!isset($bsini->_params[$key]["why"])){$bsini->_params[$key]["why"]=null;}
        if(!isset($bsini->_params[$key]["other"])){$bsini->_params[$key]["other"]=null;}
        if(!isset($bsini->_params[$key]["threads_socket"])){$bsini->_params[$key]["threads_socket"]=null;}
        if(!isset($bsini->_params[$key]["master_memory"])){$bsini->_params[$key]["master_memory"]=null;}
        if(!isset($bsini->_params[$key]["master_cached_memory"])){$bsini->_params[$key]["master_cached_memory"]=null;}
        $master_title="{{$bsini->_params["$key"]["service_name"]}}";
        if(strlen($bsini->_params["$key"]["master_version"])>1){
            $version="v{$bsini->_params["$key"]["master_version"]}";
        }

        //$installed=$bsini->_params[$key]["installed"];

        if($bsini->_params[$key]["running"]==0){
            return $this->status_array("$master_title","",false,false,$version);

        }



        if(trim($bsini->_params["$key"]["master_memory"])<>null){
            $memory="{memory}: ".FormatBytes($bsini->_params["$key"]["master_memory"]);
        }

        if($bsini->_params["$key"]["uptime"]<>null){$uptime="<br><i>{since}: {$bsini->_params["$key"]["uptime"]}</i>";}
        $additionnal_text=null;
        if($additionnal<>null){$additionnal_text="<br><strong>$additionnal</strong>";}
        return $this->status_array("$master_title","$memory$uptime$additionnal_text",true,false,$version);

    }



    public function graph_date_line_sizeMB($data,$id){
        $f=array();
        foreach ($data as $time=>$size){
            if($GLOBALS["VERBOSE"]){echo "$time -> $size";}
            $timeZ=strtotime($time." UTC") * 1000;
            $size=$size/1024;
            $size=$size/1024;
            $size=round($size);
            if($GLOBALS["VERBOSE"]){echo "$timeZ -> $size\n";}
            $f[]="['$timeZ',$size]";

        }
        header("content-type: application/x-javascript");
        $fin[]="$(function() {";
        $fin[]="\tvar data=[\n".@implode(",", $f)."\n];";
        $fin[]="\t$.plot('#{$id}', [ data ], {";
        $fin[]="\t\tgrid: {	color: '#999999',";
        $fin[]="\t\ttickColor: '#D4D4D4',";
        $fin[]="\t\tborderWidth:0,";
        $fin[]="\t\tminBorderMargin: 20,";
        $fin[]="\t\tlabelMargin: 10,";
        $fin[]="\t\tbackgroundColor: { colors: ['#ffffff', '#ffffff'] },";
        $fin[]="\t\tmargin: { top: 8, bottom: 20, left: 20 }";
        $fin[]="\t\t},";
        $fin[]="\tcolors: ['#1ab394'],";
        $fin[]="\tseries: {";
        $fin[]="\t\tlines: { fill: true,show: true, barWidth: 0.6,align: 'center' }";
        $fin[]="\t},";
        $fin[]="\txaxis: {";
        $fin[]="\t\tmode: 'time',";
        $fin[]="\t\ttickLength: 0";
        $fin[]="\t}";
        $fin[]="});";



        $fin[]="});";
        echo @implode("\n",$fin);

    }

    public function graph_date_line_sizeKB($data,$id){

        foreach ($data as $time=>$size){
            if($GLOBALS["VERBOSE"]){echo "$time -> $size";}
            $timeZ=strtotime($time." UTC") * 1000;
            $size=$size/1024;
            $size=round($size);
            if($GLOBALS["VERBOSE"]){echo "$timeZ -> $size\n";}
            $f[]="['$timeZ',$size]";

        }
        header("content-type: application/x-javascript");
        $fin[]="$(function() {";
        $fin[]="\tvar data=[\n".@implode(",\n", $f)."\n];";
        $fin[]="\t$.plot('#{$id}', [ data ], {";
        $fin[]="\t\tgrid: {	color: '#999999',";
        $fin[]="\t\ttickColor: '#D4D4D4',";
        $fin[]="\t\tborderWidth:0,";
        $fin[]="\t\tminBorderMargin: 20,";
        $fin[]="\t\tlabelMargin: 10,";
        $fin[]="\t\tbackgroundColor: { colors: ['#ffffff', '#ffffff'] },";
        $fin[]="\t\tmargin: { top: 8, bottom: 20, left: 20 }";
        $fin[]="\t\t},";
        $fin[]="\tcolors: ['#1ab394'],";
        $fin[]="\tseries: {";
        $fin[]="\t\tlines: { fill: true,show: true, barWidth: 0.6,align: 'center' }";
        $fin[]="\t},";
        $fin[]="\txaxis: {";
        $fin[]="\t\tmode: 'time',";
        $fin[]="\t\ttickLength: 0";
        $fin[]="\t}";
        $fin[]="});";



        $fin[]="});";
        echo @implode("\n",$fin);

    }



    public function graph_date_line_int($data,$id){

        foreach ($data as $time=>$size){
            $timeZ=strtotime($time." UTC") * 1000;
            if($GLOBALS["VERBOSE"]){echo "$time - $timeZ Size:$size\n";}
            $f[]="['$timeZ',$size]";

        }
        header("content-type: application/x-javascript");
        $fin[]="$(function() {";
        $fin[]="\tvar data=[\n".@implode(",", $f)."\n];";
        $fin[]="\t$.plot('#{$id}', [ data ], {";
        $fin[]="\t\tgrid: {	color: '#999999',";
        $fin[]="\t\ttickColor: '#D4D4D4',";
        $fin[]="\t\tborderWidth:0,";
        $fin[]="\t\tminBorderMargin: 20,";
        $fin[]="\t\tlabelMargin: 10,";
        $fin[]="\t\tbackgroundColor: { colors: ['#ffffff', '#ffffff'] },";
        $fin[]="\t\tmargin: { top: 8, bottom: 20, left: 20 }";
        $fin[]="\t\t},";
        $fin[]="\tcolors: ['#1ab394'],";
        $fin[]="\tseries: {";
        $fin[]="\t\tlines: { fill: true,show: true, barWidth: 0.6,align: 'center' }";
        $fin[]="\t},";
        $fin[]="\txaxis: {";
        $fin[]="\t\tmode: 'time',";
        $fin[]="\t\ttickLength: 0";
        $fin[]="\t}";
        $fin[]="});";



        $fin[]="});";
        echo @implode("\n",$fin);

    }

    public function widget_install($explain=null,$LICENSE=false,$config=array()):string{
        $t=time();
        $BUTTON=true;
        $text_button="{launch_install}&nbsp;&raquo;&raquo;";
        $bg="navy-bg";
        $btbg="btn-primary";
        if($LICENSE){
            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
                $bg="gray-bg";
                $text_button="{license_invalid}";
                $btbg="btn-default";
            }
        }

        if(count($config)==0){
            $BUTTON=false;
        }
        $js=$this->framework_buildjs($config["CMD_ON"],
            $config["PROGRESS_FILE"],$config["LOG_FILE"],$t,$config["AFTER"]);

        if(isset($config["SECURITY"])){
            if(!$this->IsSecurity($config["SECURITY"])){$js=null;}
        }

        $html[]="<div style='margin:30px 30px 30px 30px;width:60%' class='center'>";
        $html[]="<div class=\"widget $bg p-lg text-center\">";
        $html[]="<table style='width:100%'>";
        $html[]="<tr>";
        $html[]="<td style='vertical-align:top;width:1%;' nowrap><i class=\"".ico_cd." fa-8x\"></i></td>";
        $html[]="<td style='vertical-align:top;padding-left:15px'><h1 class=\"m-xs\" style='text-align:left'>{install_the_feature}</h1>";
        $html[]="<p class=\"font-bold\" style='text-align:left !important;margin-left:30px;margin-right:30px'>$explain</p>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="<tr>";
        $html[]="<td colspan=2 style='text-align:right;margin-right:20px;border-top:1px solid #FFFFFF'>";
        $html[]="<div id='$t' style='margin-top:10px;margin-bottom:10px'></div>";
       if($BUTTON){ $html[]="<button type=\"button\" class=\"btn $btbg btn-lg\" style='text-transform: capitalize;' OnClick=\"$js\" >$text_button</button>";}
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</table>";
        $html[]="</div>";
        $html[]="</div>";

        return @implode("\n",$html);
    }

    private function widget_engine($title,$text,$button=array(),$opt_ico=null,$size=0,$color=null){

        $csize=335;
        if($size>0){$csize=$size;}
        $bg_color="gray-bg";
        $bt_color=null;
        $ico="fa far fa-times-circle";

        if($color=="red"){
            $bg_color="red-bg";
            $bt_color="red";
            $ico="fas fa-exclamation-triangle";
        }
        if($color=="yellow"){
            $bg_color="yellow-bg";
            $bt_color="yellow";
            $ico="fas fa-exclamation-triangle";
        }
        if($color=="green"){
            $bg_color="navy-bg";
            $bt_color="green";
            $ico="fas fa-thumbs-up";
        }

        if($opt_ico<>null){$ico=$opt_ico;}
        $html[]="<!-- ICO: $ico / $opt_ico -->";
        
        if(!is_array($button)) {
            if(!is_null($button)) {
                if (preg_match("#ico:(.+)#", $button, $re)) {
                    if (strpos($re[1], ",") > 0) {
                        $icoz = explode(",", $re[1]);
                        $rdom = rand(0, count($icoz) - 1);
                        $re[1] = $icoz[$rdom];
                    }
                    $ico = $re[1];
                    $button = array();
                }
            }
        }
        $h_text="2";
        if(strlen($text)<4){$h_text="1";}
        //$btn=array("name"=>"{download}","js"=>$js,"icon"=>ico,"color"=>optional);

        $html[]="<div style='vertical-align:top;width:{$csize}px'>
			<div class='widget $bg_color p-lg text-center' style='min-height:257px;margin-top:2px'>
			<i class='$ico fa-4x'></i>
			<H3 class='font-bold no-margins' style='padding-top:10px;padding-bottom:10px'>$title</H3>
			<H$h_text class='font-bold no-margins'>$text</H$h_text>
			".$this->parse_button($button,$bt_color)."
			</div>
			</div>
			";
        return $this->_ENGINE_parse_body(@implode("\n", $html))."<script>NoSpinner()</script>";

    }

    public function widget_vert($title,$text,$button=array(),$opt_ico=null,$size=0):string{
        return $this->widget_engine($title,$text,$button,$opt_ico,$size,"green");
    }

    public function widget_rouge($title,$text,$button=array(),$opt_ico=null,$size=0):string{
        return $this->widget_engine($title,$text,$button,$opt_ico,$size,"red");
    }

    public function widget_jaune($title,$text,$button=array(),$opt_ico=null,$size=0):string{
        return $this->widget_engine($title,$text,$button,$opt_ico,$size,"yellow");
    }

    public function widget_grey($title,$text,$button=array(),$opt_ico=null,$size=0):string{
        return $this->widget_engine($title,$text,$button,$opt_ico,$size);
    }

    private function parse_button($array,$forceColor=null):string{
        $btbg=$this->btn_array();
        $button_array=array();
        if(!is_array($array)){$array=array();}
        if(count($array)==0){return "";}
        foreach ($array as $index=>$main){
            $margin=30;
            if(!isset($main["name"])){continue;}
            if(isset($main["margin"])){
                $margin=$main["margin"];
            }

            $bg=null;
            if($forceColor<>null){$bg=$btbg[$forceColor];}
            if(isset($main["color"])){$bg=$btbg[$main["color"]];}
            //$btn=array("name"=>"{download}","js"=>$js,"icon"=>ico,"color"=>optional);
            //$btname,$js,$icon_class,$security=null,$width=0,$class="btn-primary",$btsize=null
            $button1=$this->button_autnonome($main["name"], $main["js"], $main["icon"],null,0,$bg);
            $button1="<!-- color=[$bg/$forceColor] --><div style='margin-top:{$margin}px' id='$index'>$button1</div>";
            $button_array[]=$button1;
        }

        if(count($button_array)==0){return "";}

        if(count($button_array)==1){return @implode("\n",$button_array);}
        $s[]="<div class='center'><table style='width:5%'>";

        $i=0;
        foreach ($button_array as $btn){
            $i++;
            if($i==1){$s[]="<tr>";}
            $s[]="<td>$btn</td>";
            if($i==3){
                $i=0;
                $s[]="</tr>";
            }
        }
        if($i<4){
            $colspan=3-$i;
            if($colspan>1) {
                $s[] = "<td colspan='$colspan'>&nbsp;</td>";
                $s[] = "</tr>";
            }else{
                $s[] = "<td>&nbsp;</td>";
                $s[] = "</tr>";
            }
        }
        $s[]="</table></div>";
        return @implode("\n",$s);
    }



    public function spinner(){
        return "<div class='lds-roller'><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>";
    }
    public function widget_wizard($title,$subtitle,$explain,$cmdon=null,$checkliscense=false){

        $id=md5(time()."$cmdon$title");
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/wizard.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/wizard.log";
        $ARRAY["CMD"]=$cmdon;
        $ARRAY["TITLE"]=$title;
        $ARRAY["AFTER"]="document.location.href='/features';";
        $prgress=base64_encode(serialize($ARRAY));
        $js="Loadjs('fw.progress.php?content=$prgress&mainid=progress-$id')";

        $button="<button style=\"text-transform: capitalize;\" class=\"btn btn-primary btn-sm\" OnClick=\"$js\">
            <i class='fas fa-magic'></i> {run_the_wizard}</button>";

        if($checkliscense){
            $users=new usersMenus();
            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
                $button="<button style=\"text-transform: capitalize;\" class=\"btn btn-white btn-sm\" OnClick=\"blur()\">
            <i class='fas fa-magic'></i> {license_invalid}</button>";
            }

        }


        $explain=$this->_ENGINE_parse_body($explain);
        $explain=str_replace("%s",$_SERVER["SERVER_NAME"],$explain);

        $html="<div class=\"row\">
                    <div class=\"col-md-7 b-r-xl bg-muted\" style='padding:20px'>

                                    <h2 class=\"font-bold m-b-xs\">
                                        $title
                                    </h2>
                                    <p>$subtitle</p>
                                    <div class=\"small text-muted\">$explain</div>
                                    <div id='progress-$id' style='margin:5px'></div>
                                    <hr>

                                    <div>
                                        <div class=\"btn-group\">
                                        $button
                                            
                                            
                                        </div>
                                    </div>



                                </div>
                            </div>

                    </div>
                    <div class=\"ibox-footer\">&nbsp;</div>
            </div>";

        return $html;


    }


    public function squid_admin_mysql($severity,$subject,$text=null,$file=null,$line=0){

        $ArticaNotifsMaxTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaNotifsMaxTime"));
        if($ArticaNotifsMaxTime==0){$ArticaNotifsMaxTime=7;}
        $removeafter=strtotime("+$ArticaNotifsMaxTime day");

        $function=null;
        $traces=array();
        $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
        if($severity<2){

            $error_get_last=error_get_last();
            if(isset($error_get_last["line"])) {
                if(intval($error_get_last["line"])>0) {
                    $traces[] = "Last error {$error_get_last["message"]} line {$error_get_last["line"]} in file " .
                        basename($error_get_last["file"]);
                }
            }

            if(function_exists("debug_backtrace")){
                $trace=debug_backtrace();
                foreach ($trace as $index=>$ligne){$file=basename($ligne["file"]);$line=$ligne["line"];
                    $function=$ligne["function"];$traces[]="Line $line in $function() in file $file";}
            }

        }

        $text=$text."\n".@implode("\n", $traces)."\n";
        $text=str_replace("'","`",$text);
        $text=$q->sqlite_escape_string2($text);
        $subject=$q->sqlite_escape_string2($subject);
        $zdate = time();
        $file=basename(__FILE__);
        $q->QUERY_SQL("INSERT OR IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`removeafter`) VALUES ('$zdate','$text','$subject','$function','$file','$line','$severity','$removeafter')");
        if(!$q->ok){writelogs("SQL ERROR $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
    }

    public function SERVICE_STATUS_DISABLED($title):string{

        if(preg_match("#APP_SHELLINABOX_#",$title)){
            $title="{APP_SHELLINABOX}";
        }

        return "<div style='vertical-align:top;width:335px'>
				<div class='widget gray-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
				<i class='fa-blink far fa-times-circle fa-4x'></i>
				<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>$title</H3>
				<H2 class='font-bold no-margins'>{disabled}</H2>
				</div>
				</div>";
        
    }
    public function SERVICE_STATUS_STOPPED($title,$button=null):string{
        return "<div style='vertical-align:top;width:335px'>
				<div class='widget red-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
				<i class='fa-blink fas fa-exclamation-triangle fa-4x'></i>
				<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>$title</H3>
				<H2 class='font-bold no-margins'>{stopped}</H2>
				$button
				</div>
				</div>
			
				";

    }

    private function SERVICE_STATUS_RRD($servicename,$servicekey):string{
        if(!is_file("squid/img/$servicename-cpu-hourly.png")) {
            return $servicename;
        }
        VERBOSE("squid/img/$servicename-cpu-hourly.png NOT FOUND",__LINE__);
        if(is_file("squid/img/$servicekey-cpu-hourly.png")){
            return $servicekey;
        }
        VERBOSE("squid/img/$servicekey-cpu-hourly.png NOT FOUND",__LINE__);
        return "";
    }

    public function SERVICE_STATUS($ini,$MASTER_KEY,$restartbutton_js=null,$forceversion=null,$refreshjs=null):string{
        $styleKey=null;
        $styleKeydata=null;
        $dashjs=null;
        $users=new usersMenus();
        if(!$users->AsSystemAdministrator){$restartbutton_js=null;}


        if(preg_match("#^(.+?):(.+)#",$MASTER_KEY,$re)){
            $MASTER_KEY=$re[1];
            $opts=$re[2];
            if(preg_match("#^(.+?)=(.+)#",$opts,$re)){
                $styleKey=strtolower($re[1]);
                $styleKeydata=$re[2];
            }
        }


        $button=null;$version=null;$refreshbutton=null;
        VERBOSE("SERVICE_STATUS:: Checking $MASTER_KEY",__LINE__);
        if($restartbutton_js<>null){
            $button=$this->button_autnonome("{restart}", $restartbutton_js, "fas fa-sync-alt");

        }

        if(!isset($ini->_params[$MASTER_KEY])){
            VERBOSE("SERVICE_STATUS:: $MASTER_KEY did not found on ini->_params[", __LINE__);
            if($GLOBALS["VERBOSE"]){print_r($ini->_params);}
            return "";
        }

        $ARRAY=$ini->_params[$MASTER_KEY];
        if($GLOBALS["VERBOSE"]) {
            foreach ($ARRAY as $key => $val) {
                VERBOSE("SERVICE_STATUS:: $MASTER_KEY $key=$val",__LINE__);
            }
        }

        if($MASTER_KEY=="C-ICAP"){$MASTER_KEY="APP_C_ICAP";}

        VERBOSE("SERVICE_STATUS:: $MASTER_KEY OK",__LINE__);
        if($ARRAY["service_disabled"]==0){
            $html[]=$this->SERVICE_STATUS_DISABLED("{".$MASTER_KEY."}");
            return $this->_ENGINE_parse_body(@implode("\n", $html))."<script>NoSpinner()</script>";
        }

        if($forceversion<>null){
            $version="&nbsp;v{$forceversion}";
        }



        if($ARRAY["running"]==0){
            if($restartbutton_js<>null){
                $button=$this->button_autnonome("{restart}", $restartbutton_js,
                    "fas fa-sync-alt",null,0,"btn-danger");

                if($refreshjs<>null){
                    $button2=$this->button_autnonome("{refresh}", $refreshjs,
                        "fas fa-sync-alt",null,0,"btn-danger");
                    $button="<div style='margin-top:30px'>
                    <table style='width:100%'>
                        <tr>
                         <td>$button</td>
                         <td style='padding-left:15px'>$button2</td>
                        </tr>
                    </table>";
                }else{
                    $button="<div style='margin-top:30px'>$button</div>";
                }

            }
            $MASTER_TITLE=$MASTER_KEY;
            if(preg_match("#APP_SHELLINABOX_#",$MASTER_TITLE)){
                $MASTER_TITLE="APP_SHELLINABOX";
            }
            if(strlen($ini->ChangetitleTo)>1){
                $MASTER_TITLE=$ini->ChangetitleTo;
                $html[]=$this->SERVICE_STATUS_STOPPED("$MASTER_TITLE$version",$button);
            }else {
                $html[] = $this->SERVICE_STATUS_STOPPED("{{$MASTER_TITLE}}$version", $button);
            }

        }else{
            $fd="";
            $mem=$ARRAY["master_memory"];
            $ARRAY["uptime"]=str_replace("week", "{week}", $ARRAY["uptime"]);
            $ARRAY["uptime"]=str_replace("day", "{day}", $ARRAY["uptime"]);
            $ARRAY["uptime"]=str_replace("hour", "{hour}", $ARRAY["uptime"]);
            $ARRAY["uptime"]=str_replace("months", "{months}", $ARRAY["uptime"]);

            if($refreshjs<>null){
                $button2=$this->button_autnonome("{refresh}", $refreshjs, "fas fa-sync-alt");
                $button="<div style='margin-top:30px'>
                    <table style='width:100%'>
                        <tr>
                         <td>$button</td>
                         <td style='padding-left:15px'>$button2</td>
                        </tr>
                    </table>";
            }else{
                $button="<div style='margin-top:30px'>$button</div>";
            }

            $petty_conf="{ width:280,fill: [\"#18a689\"],stroke:\"#FFFFFF\",strokeWidth: 2 }";
            $dash_div=null;
            $dash_div_title="";
            $service_name=$ARRAY["service_name"];
            if(!isset($ARRAY["Nograph"])) {
                $rrdkey = $this->SERVICE_STATUS_RRD($service_name, $MASTER_KEY);
                VERBOSE("RRD KEY: $rrdkey", __LINE__);
                if (strlen($rrdkey) > 3) {
                     $topbuttons[] = array("Loadjs('fw.rrd.micro.php?key=$rrdkey');", ico_mem, "{graph} {memory}");
                    $topbuttons[] = array("Loadjs('fw.rrd.micro.php?key=$rrdkey');", ico_cpu, "{graph} {cpu}", "green");
                    $bbtn = $this->th_buttons($topbuttons);

                    $dash_div_title = " <div class='text-white' style='margin-bottom:5px;margin-top:10px'>$bbtn</div>";
                }
            }


            if(is_file("/usr/share/artica-postfix/ressources/pmemory/$service_name")){
                $data_mem=explode(";",@file_get_contents("/usr/share/artica-postfix/ressources/pmemory/$service_name"));
                if(is_array($data_mem)) {
                    if(strlen($dash_div_title)==0) {
                        $dash_div_title = "<div class='text-white' style='margin-bottom:5px;margin-top:10px'>{graph}: {memory}</div>";
                    }
                    $dash_div = "<span id=\"dashboard-$service_name-line\">" .
                        @implode(",", $data_mem) . "</span>";
                    $dashjs = "\n$(\"#dashboard-$service_name-line\").peity(\"line\",$petty_conf);";
                }
            }else{
                $dashjs="\n// /usr/share/artica-postfix/ressources/pmemory/$service_name no such file";
            }

            $MASTER_TITLE="{{$MASTER_KEY}}";
            if(isset($ARRAY["service_name"])){
                if(preg_match("#^APP_#",$ARRAY["service_name"])){
                    $MASTER_TITLE="{{$ARRAY["service_name"]}}";
                }
            }

            if(isset($ARRAY["maxfd"])){
                $fd="<H3 class='font-bold no-margins'>{files}: {$ARRAY["curfd"]}/{$ARRAY["maxfd"]}</H3>";
            }

            $mem=FormatBytes($mem);
            $widh="335px";
            if($styleKey=="width"){$widh=$styleKeydata;}


            if(preg_match("#APP_SHELLINABOX_#",$MASTER_TITLE)){
                $MASTER_TITLE="{APP_SHELLINABOX}";
            }
            if(property_exists($ini,"ChangetitleTo")) {
                if (!is_null($ini->ChangetitleTo)) {
                    if (strlen($ini->ChangetitleTo) > 1) {
                        $MASTER_TITLE = $ini->ChangetitleTo;
                    }
                }
            }


            $html[]="<div style='vertical-align:top;width:$widh'>
			<div class='widget navy-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
			<i class='fas fa-thumbs-up fa-4x'></i>
			<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>$MASTER_TITLE$version</H3>
			<H2 class='font-bold no-margins'>{running}</H2>
			<H3 class='font-bold no-margins' style='padding-top:10px'>{since} {$ARRAY["uptime"]}</H3>
			<H3 class='font-bold no-margins'>{memory_used}: $mem</H3>$fd
			$dash_div_title$dash_div
			$button
			</div>
			</div>
			
			";

        }

        return $this->_ENGINE_parse_body(@implode("\n", $html))."<script>NoSpinner();\n$dashjs</script>";

    }


    function widget_h($background="green",$icon="fa-cloud",$number="0 {items}",$explain=null,$button=array(),$button2=array()){
        //fas fa-engine-warning
        //fas fa-database
        //fas fa-thumbs-down
        //fas fa-thumbs-up
        //fas fa-exclamation-circle
        //fas fa-database // <i class="fad fa-hdd"></i>
        //far fa-ethernet
        //fas fa-users
        //fas fa-percent
        //fas fa-signal
        // fad fa-shield-alt
        // fa fa-wrench
        // fas fa-plug
        $min_height=null;
        $min_width=null;

        if(preg_match("#minheight:(.+?):(.+)#",$background,$re)){
            $min_height="min-height: $re[1]";
            $background=$re[2];
        }
        if(preg_match("#minwidth:([0-9]+):(.+)#",$background,$re)){
            $min_width=";min-width: $re[1]px";
            $background=$re[2];
        }

        $button_html=null;
        $button_help=null;
        $explain=$this->_ENGINE_parse_body($explain);
        $array_bf["none"]=null;
        $array_bf["green"]="navy-bg";
        $array_bf["yellow"]="yellow-bg";
        $array_bf["lazur"]="lazur-bg";
        $array_bf["red"]="red-bg";
        $array_bf["blue"]="blue-bg";
        $array_bf["gray"]="gray-bg";
        $array_bf["black"]="black-bg";
        $array_bf["grey"]="gray-bg";
        $array_bf[null]=null;


        if(!is_array($button)){
            if(preg_match("#minheight:(.+)#",$button,$re)){
                $min_height="min-height: $re[1]";
                $button=array();
            }
        }
        if(!is_array($button2)){
            if(!is_null($button2)){
                if(preg_match("#minheight:(.+)#",$button2,$re)) {
                $min_height = "min-height: $re[1]";
                }
            }
            $button2=array();
        }

        if(isset($button["help"]) AND count($button)>1 ){
            $btbg=$this->btn_array();
            $js="s_PopUpFull('{$button["help"]}','1024','900');";
            $button_help=$this->button_autnonome("{online_help}", $js, "fas fa-user-headset",null,0,$btbg[$background]);
            unset($button["help"]);

        }

        if(isset($button["help"])){
            $btbg=$this->btn_array();
            $js="s_PopUpFull('{$button["help"]}','1024','900');";
            $button_html=$this->button_autnonome("{online_help}", $js, "fas fa-user-headset",null,0,$btbg[$background]);
            $button_html="<div style='margin-top:30px'>$button_html</div>";
            unset($button);
        }
        if(!is_array($button2)){
            $button2=array();
        }
        
        if(!isset($button)){
            $button=array();
        }
        
        if(!is_array($button)){
            $button=array();
        }

        if(count($button)>0 AND count($button2)==0){
            $btbg=$this->btn_array();
            $button_htmlsrc=null;
            $icon1=null;
            if(isset($button["css"])){
                if(preg_match("#minheight:(.+)#",$button["css"],$re)){
                    $min_height="min-height: $re[1]";

                }
            }

            if($button_help==null && isset($button["text-left"])){
                $button_help=$button["text-left"];
            }

            if(isset($button["ico"])){$icon1=$button["ico"];}

            if($button["js"]=="UPLOAD"){
                VERBOSE("button_js: {$button["js"]}",__LINE__);
                $page=CurrentPageName();
                $button_htmlsrc=$this->button_upload($button["name"],$page,$btbg[$background]);
            }
            if($button_htmlsrc==null) {
                $button_htmlsrc = $this->button_autnonome($button["name"], $button["js"], $icon1, null, 0, $btbg[$background]);
            }
            $button_html="<div style='margin-top:30px'>$button_htmlsrc</div>";
            if($button_help<>null){
                $button_html="<div style='margin-top:30px;text-align:right'>
                <div style='text-align:right;float:right'>
                <table><tr><td>$button_htmlsrc</td><td>$button_help</td></tr></table></div>
                </div>";
            }

        }

        if(count($button)>0 AND count($button2)>0){
            $btbg=$this->btn_array();
            $ico2=$icon;
            $ico1=$icon;
            $button_html1=null;
            if(isset($button2["ico"])){$ico2=$button2["ico"];}
            if(isset($button["ico"])){$ico1=$button["ico"];}

            if(isset($button["css"])){
                if(preg_match("#minheight:(.+)#",$button["css"],$re)){
                    $min_height="min-height: $re[1]";

                }
            }


            if(isset($button2["css"])){
                if(preg_match("#minheight:(.+)#",$button2["css"],$re)){
                    $min_height="min-height: $re[1]";

                }
            }

            if($button["js"]=="UPLOAD"){
                $page=CurrentPageName();
                $button_html1=$this->button_upload($button["name"],$page,$btbg[$background]);
            }

            if($button_html1==null) {
                $button_html1 = $this->button_autnonome(
                    $button["name"], $button["js"], $ico1, null, 0, $btbg[$background]);
            }
            $button_html2=$this->button_autnonome($button2["name"], $button2["js"], $ico2,null,0,$btbg[$background]);
            $button_html="<div style='margin-top:30px;text-align:right'>
                <div style='text-align:right;float:right'>
                <table><tr><td>$button_html1</td><td>$button_html2</td></tr></table></div>
                </div>";

        }


        $html[]="<div class=\"widget style1 {$array_bf[$background]}\" style='$min_height$min_width'>";
        $html[]="\t<div class=\"row\">";
        $html[]="\t\t<div class=\"col-xs-4\">";
        $html[]="\t\t\t<i class=\"fa $icon fa-5x\"></i>";
        $html[]="\t\t</div>";
        $html[]="\t<div class=\"col-xs-8 text-right\">";
        $html[]="\t\t<span> $explain </span>";
        $html[]="\t\t<h2 class=\"font-bold\">$number</h2>";
        $html[]=$button_html;
        $html[]="\t</div>";
        $html[]="\t</div>";
        $html[]="</div>";
        return @implode("\n", $html);
    }

    private function btn_array():array{
        $btbg[null]="btn-primary";
        $btbg["green"]="btn-primary";
        $btbg["ok"]="btn-primary";
        $btbg[true]="btn-primary";
        $btbg["lazur"]="btn-info";
        $btbg["warning"]="btn-warning";
        $btbg["yellow"]="btn-warning";
        $btbg["red"]="btn-danger";
        $btbg["danger"]="btn-danger";
        $btbg[false]="btn-danger";
        $btbg["error"]="btn-danger";
        $btbg["blue"]="btn-blue";
        $btbg["disabled"]="btn-default";
        $btbg["default"]="btn-default";
        $btbg["none"]="btn-default";
        $btbg["white"]="btn-default";
        $btbg["gray"]="btn-default";
        $btbg["grey"]="btn-default";
        $btbg["black"]="btn-default";
        $btbg["btn-danger"]="btn-danger";
        $btbg["btn-default"]="btn-default";
        $btbg["btn-warning"]="btn-warning";
        $btbg["btn-info"]="btn-info";
        $btbg["btn-primary"]="btn-primary";
        return $btbg;
    }
}

function browser_realip():string{
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){return $_SERVER["HTTP_X_FORWARDED_FOR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){ return $_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["REMOTE_ADDR"])){return $_SERVER["REMOTE_ADDR"];}
    return "0.0.0.0";
}
function _xss_clean($data):string{
    // Fix &entity\n;
    $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
    $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
    $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
    $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

    // Remove any attribute starting with "on" or xmlns
    $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

    // Remove javascript: and vbscript: protocols
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

    // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

    // Remove namespaced elements (we do not need them)
    $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

    do
    {
        // Remove really unwanted tags
        $old_data = $data;
        $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
    }
    while ($old_data !== $data);

    // we are done...
    return strval($data);
}

function _cleanvars($dat){
    $dat=_xss_clean($dat);
    $dat=str_ireplace("<script","",$dat);
    return $dat;
}

function _debugtraceback($trace){
        if(count($trace)==0){return "";}
        $f=array();
        foreach ($trace as $line){
            $sourcefile=basename($line["file"]);
            $sourcefunction=$line["function"];
            $sourceline=$line["line"];
            $f[]="$sourcefile/$sourcefunction/L.$sourceline";
        }
        return @implode("| ",$f);
}

//XSS Protection
$NoXSSProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoXSSProtection"));
//List of forbidden words / tags
$xss_words = array("''","'","&apos;","\"><",'"',";",'";',"javascript:","OnKeyPress","OnClick","</script>", "alert(","'"," OR "," AND ","<script>","confirm(","onfocus","prompt(","[","]","<ScRiPt>","document.location=","</ScRiPt >","<img","onerror","document.title",'<a b=c>',"(a)","<a>");
if($NoXSSProtection==1) {
//Clean xss tags from $_COOKIE
    foreach ($_COOKIE as $key => $val) {
        if (strpos($key, "'") !== false) {
            unset($_COOKIE[$key]);
            continue;
        }
        $val = trim($val);
        //Remove bad tags xss
        $val = str_replace($xss_words, "", $val);
        //Clean byte array xss
        $val = preg_replace('/[\x00-\x1f]/', '', $val);
        //Clean <a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean (a-zA-Z0-9_.-) xss
        $val = preg_replace("/(\([a-zA-Z0-9_.-]*\))$/", '', $val);
        //Clean 111=222 xss
        $val = preg_replace("/(\d+=\d+)/", '', $val);
        $_COOKIE[$key] = $val;
    }
//Unset Cookie artica-language if > 2
    if(isset($_COOKIE["artica-language"])) {
        if (strlen($_COOKIE["artica-language"]) > 2) {
            unset($_COOKIE["artica-language"]);
        }
    }
//Unset POST that contain ' in key
    foreach ($_POST as $key => $val) {
        //echo md5($val)."-$key\n";

        //Unset GET that contain ' in key
        if (strpos($key, "'") !== false) {
            unset($_POST[$key]);
            continue;
        }
        $val = trim($val);

        //Remove bad tags xss
        //$val = str_replace($xss_words, "", $val);

        //Clean byte array xss
        $val = preg_replace('/[\x00-\x1f]/', '', $val);
        //Clean <a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean (a-zA-Z0-9_.-) xss
        $val = preg_replace("/(\([a-zA-Z0-9_.-]*\))$/", '', $val);
        //Clean 111=222 xss
        $val = preg_replace("/(\d+=\d+)/", '', $val);
        //$val = preg_replace('<[a-zA-Z]+(\s+[a-zA-Z]+\s*=\s*("([^"])"|\'([^\'])\'))\s/>', '', $val);
        //$val = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $val);
        //echo md5($val)."-$key\n";
        $_POST[$key] = $val;
    }
//Clean xss tags from $_GET
    foreach ($_GET as $key => $val) {
        //Unset GET that contain ' in key
        if (strpos($key, "'") !== false) {
            unset($_GET[$key]);
            continue;
        }
        $val = trim($val);
        //Remove bad tags xss
        $val = str_replace($xss_words, "", $val);
        //Clean byte array xss
        $val = preg_replace('/[\x00-\x1f]/', '', $val);
        //Clean <a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean (a-zA-Z0-9_.-) xss
        $val = preg_replace("/(\([a-zA-Z0-9_.-]*\))$/", '', $val);
        //Clean 111=222 xss
        $val = preg_replace("/(\d+=\d+)/", '', $val);
        //$val = preg_replace('<[a-zA-Z]+(\s+[a-zA-Z]+\s*=\s*("([^"])"|\'([^\'])\'))\s/>', '', $val);
        //$val = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $val);
        $_GET[$key] = $val;
    }
}
function clean_xss_deep(){
    $trace=array();
    if(function_exists("debug_backtrace")){$trace=debug_backtrace();}
    foreach ($_POST as $key=>$val){
        if(!is_null($val)) {
            $val = trim($val);
        }
        if($val==null){
            if($key=="search"){continue;}
            unset($_POST[$key]);
            continue;
        }
        $oldval=$val;
        $val = _cleanvars($val);

        if($oldval<>$val){
            admin_tracks("XSS Detected on value POST !" ._debugtraceback($trace));
        }

        $newKey=$key;
        $newKey = _cleanvars($newKey);
        if($newKey<>$key){
            admin_tracks("XSS Detected on key POST !" ._debugtraceback($trace));
            unset($_POST[$key]);
            $_POST[$newKey]=$val;
            continue;
        }
        $_POST[$key]=$val;
    }
    foreach ($_GET as $key=>$val){
        $val=trim($val);
        if($val==null){
            unset($_GET[$key]);
            continue;
        }
        $oldval=$val;
        $val = _cleanvars($val);

        if($oldval<>$val){
            admin_tracks("XSS Detected on value GET !" ._debugtraceback($trace));
        }

        $newKey=$key;
        $newKey = _cleanvars($newKey);

        if($newKey<>$key){
            admin_tracks("XSS Detected on key GET !" ._debugtraceback($trace));
            unset($_GET[$key]);
            $_GET[$newKey]=$val;
            continue;
        }
        $_GET[$key]=$val;
    }
}
function admin_tracks_post($text):bool{
    $tt=array();
    foreach ($_POST as $key=>$val){
        $tt[]=" $key=[$val]";
    }
    $text=$text." with values ".@implode(", ",$tt);
    return admin_tracks($text);
}

function unserializeb64($data):array{
    if(is_null($data)){
        return array();
    }
    if(strlen($data)<3){
        return array();
    }
    $Decoded=base64_decode($data);
    if(!$Decoded){
        return array();
    }
    $Unser=unserialize($Decoded);
    if(!$Unser){
        return array();
    }
    if(!is_array($Unser)){
        return array();
    }

    return $Unser;
}

function admin_tracks($text):bool{
    $uid="unknown";
    $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
    if($PowerDNSEnableClusterMaster==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/server/stamp");
    }
    $TrackAdmins=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TrackAdmins"));
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$TrackAdmins=0;}
    $time=time();
    if(isset($GLOBALS["ROOT_UID"])){
        $uid=$GLOBALS["ROOT_UID"];
    }else {
        if(isset($_SESSION["uid"])) {
            $uid = $_SESSION["uid"];
        }
    }
    if($uid==-100){$uid="Manager";}
    $ipaddr=browser_realip();
    if($TrackAdmins==1) {
        $q = new lib_sqlite("/home/artica/SQLITE/admins.db");
        $text=str_replace("'","`",$text);
        $text = $q->sqlite_escape_string2($text);
        $text=str_replace('"',"`",$text);
        $sql = "INSERT INTO admintracks (time,ipaddr,username,operation) VALUES ('$time','$ipaddr','$uid','$text')";
        $q->QUERY_SQL($sql);
        if (!function_exists("syslog")) {
            return false;
        }
        @openlog("ArticaTrackAdmins", LOG_PID, LOG_SYSLOG);
        @syslog(LOG_INFO, "[$uid] [$ipaddr] $text: ");
        @closelog();
    }
    return true;
}
function ExecTAtime($start,$text,$line){
    if(!isset($GLOBALS["VERBOSE"])){return;}
    $end = microtime(true);
    $executionTime = $end - $start;
    VERBOSE_TEMPLATE("($text) Execution time ".number_format($executionTime, 4) . " seconds",$line);
}
function VERBOSE_TEMPLATE($text,$line=0,$forceFunc=""):bool{
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