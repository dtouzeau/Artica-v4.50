<?php


class HeadsBuilder{
    var $RemoveHeadjs=0;
    private $headjs="type=\"text/javascript\" language=\"javascript\"";
    private $jsroot="/angular/js";
    private $pluginRoot="";
    private $cScript=array();
    private $InTopHead=array();
    private $MerginJs=0;
    public $f="";
    public $cssScript="";

    function __construct() {
        $this->RemoveHeadjs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoveHeadjs"));
        $this->pluginRoot="$this->jsroot/plugins";
    }

    function BuildLoader(){

        $this->MerginJs=0;
        $this->CssCripts();
        $this->EndPart();

    }

    public function EndPart():bool{
        $headjs=$this->headjs;
        $starter=$this->Starter();
        $jsCode103=$this->Jcode103();
        if($this->RemoveHeadjs==0) {
            $this->InTopHead[] = "    <script rel=\"preload\"  $headjs src=\"/js/head.min.js\" ></script>";
        }
        foreach ($starter as $script){
            if(!is_file("/usr/share/artica-postfix/$script")){
                $this->InTopHead[]="<!-- /usr/share/artica-postfix/$script no such file. -->";
                continue;
            }
            $this->InTopHead[]="<script $headjs src=\"$script\"></script>";
        }

        foreach ($jsCode103 as $jsf){
            if(!is_file("/usr/share/artica-postfix/$jsf")){
                $this->InTopHead[]="<!-- /usr/share/artica-postfix/$jsf no such file. -->";
                continue;
            }
            $this->InTopHead[]="<script $headjs src=\"$jsf\"></script>";
        }

        $this->f=@implode("\n", $this->InTopHead);
        return true;
    }

    private function MergeAll():bool{
        $jsCode104=$this->Jcode104();
        $this->cScript[]="<script>";
        $Content=array();
        $Cssss=array();
        foreach ($jsCode104 as $jsf) {
            if(!is_file("/usr/share/artica-postfix/$jsf")){
                $this->InTopHead[]="<!-- CssCripts() /usr/share/artica-postfix/$jsf no such file. -->";
                continue;
            }

            $Content[]=file_get_contents("/usr/share/artica-postfix/$jsf");

        }
        $cssCode103=$this->cSSCode103();
        foreach ($cssCode103 as $jsf){
            if(!is_file("/usr/share/artica-postfix/$jsf")){
                $this->InTopHead[]="<!-- MergeAll() /usr/share/artica-postfix/$jsf no such file. -->";
                continue;
            }
            $Cssss[]=file_get_contents("/usr/share/artica-postfix/$jsf");
        }


        $this->cScript[]="</script>";
        @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/Merged.js",@implode("",$Content));
        @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/Merged.css",@implode("",$Cssss));
        $this->InTopHead[]="<link href=\"/ressources/logs/web/Merged.css\" rel=\"stylesheet\" type=\"text/css\" />";
        $this->InTopHead[]="<script defer src=\"/ressources/logs/web/Merged.js\"></script>";
        return true;
    }

    private function CssCripts():bool{
        if($this->MerginJs==1){
            return $this->MergeAll();
        }
        $jsCode104=$this->Jcode104();
        $this->cScript[]="<script>";
        $Headjs=array();
        $loadssJs=array();

            foreach ($jsCode104 as $jsf) {
                if(!is_file("/usr/share/artica-postfix/$jsf")){
                    $this->InTopHead[]="<!-- CssCripts() /usr/share/artica-postfix/$jsf no such file. -->";
                    continue;
                }
                $Headjs[] = "\"$jsf\"";
                $loadssJs[]="loadssJs('$jsf')";
            }


        if($this->RemoveHeadjs==0) {
            $this->cScript[] = "head.load(" . @implode(",", $Headjs) . ");";
        }else{
            $this->cScript[] = @implode("\n",$loadssJs);
        }
        $cssCode103=$this->cSSCode103();
        foreach ($cssCode103 as $jsf){
            $this->cScript[]="loadssfile(\"$jsf\");";
        }
        $this->cScript[]="    </script>";
        $this->cssScript=@implode("\n", $this->cScript);
        return true;
    }

    private function Starter():array{
        $pluginRoot=$this->pluginRoot;
        $jsroot=$this->jsroot;
        $jqueryToUse=$GLOBALS["CLASS_SOCKETS"]->jQueryToUse();
        $starter=array();
        $starter[]="/angular/js/jquery/$jqueryToUse";
        $starter[]="$pluginRoot/jquery-ui/jquery-ui.min.js";
        $starter[]="/XHRConnection.js";
        $starter[]="$jsroot/bootstrap/bootstrap.min.js";
        $starter[]="$jsroot/angular/angular.min.js";
        $starter[]="$jsroot/inspinia.js";
        $starter[]="$jsroot/form-builder.min.js";
        $starter[]="$jsroot/adbdetector.js";
        $starter[]="$jsroot/jquery-picklist.js";
        // DropDownlist v2
        $starter[]="$jsroot/select2.full.min.js";
        $starter[]="$jsroot/apexcharts/apexcharts.min.js";
        $starter[]="/fingerprint/query";
        return $starter;
    }

    private function Jcode103():array{
        $pluginRoot=$this->pluginRoot;
        $jsroot=$this->jsroot;
        $jsCode103[]="/mouse.js";
        $jsCode103[]="/js/timer.js";
        $jsCode103[]="$pluginRoot/jasny/jasny-bootstrap.min.js";
        $jsCode103[]="$pluginRoot/nouslider/nouislider.min.js";
        $jsCode103[]="$pluginRoot/toastr/toastr.min.js";
        $jsCode103[]="$pluginRoot/validate/jquery.validate.min.js";
        $jsCode103[]="$pluginRoot/steps/jquery.steps.min.js";
        $jsCode103[]="$pluginRoot/slimscroll/jquery.slimscroll.min.js";
        $jsCode103[]="/js/meiomask.js";
        $jsCode103[]="$jsroot/cleave.js";
        $jsCode103[]="$jsroot/file-upload/jquery.ui.widget.js";
        $jsCode103[]="$pluginRoot/jquery-address/jquery.address-1.5.min.js";
        $jsCode103[]="js/jquery.Wload.js";
        $jsCode103[]="$pluginRoot/BootstrapDialog/bootstrap-dialog.min.js";
        $jsCode103[]="$pluginRoot/toogle/bootstrap-toggle.min.js";
        $jsCode103[]="$pluginRoot/slimscroll/jquery.slimscroll.min.js";
        $jsCode103[]="$pluginRoot/footable/footable.all.min.js";
        $jsCode103[]="$pluginRoot/metisMenu/jquery.metisMenu.js";
        $jsCode103[]="$jsroot/plugins/daterangepicker/moment.min.js";
        $jsCode103[]="$jsroot/plugins/daterangepicker/daterangepicker.3.1.min.js";
        $jsCode103[]="/js/jquery.peity.min.js";
        $jsCode103[]="/default.js";
        return $jsCode103;
    }

    private function cSSCode103():array{
        $pluginRoot=$this->pluginRoot;
        $cssCode103[]="/angular/js/plugins/jquery-ui/jquery-ui.min.css";
        $cssCode103[]="/angular/bootstrap.min.css";
        $cssCode103[]="/angular/style.css";
        $cssCode103[]="/angular/fonts.css";
        $cssCode103[]="/angular/plugins/colorpicker/bootstrap-colorpicker.min.css";
        $cssCode103[]="/angular/animate.css";
        $cssCode103[]="/angular/plugins/footable/footable.core.css";
        $cssCode103[]="/angular/plugins/toogle/bootstrap-toggle.css";
        $cssCode103[]="/angular/bootstrap-dialog.min.css";
        $cssCode103[]="/angular/plugins/chosen/bootstrap-chosen.css";
        $cssCode103[]="/angular/plugins/codemirror/ambiance.css";
        $cssCode103[]="/angular/plugins/codemirror/codemirror.css";
        $cssCode103[]="/angular/plugins/clockpicker/clockface.css";
        $cssCode103[]="/angular/plugins/bootstrap-ipaddress/bootstrap-ipaddress.css";
        $cssCode103[]="/angular/plugins/iCheck/custom.css";
        $cssCode103[]="/angular/js/plugins/datapicker/bootstrap-datepicker.min.css";
        $cssCode103[]="/angular/js/plugins/nouslider/nouislider.min.css";
        $cssCode103[]="/angular/js/plugins/jsTree/style.min.css";
        $cssCode103[]="/angular/js/plugins/toastr/toastr.min.css";
        $cssCode103[]="/angular/js/plugins/sweetalert/sweetalert.css";
        $cssCode103[]="/angular/js/plugins/cron/jquery-gentleSelect.css";
        $cssCode103[]="/angular/js/plugins/morris/morris.css";
        $cssCode103[]="/angular/plugins/steps/jquery.steps.css";
        $cssCode103[]="/angular/plugins/iCheck/custom.css";
        $cssCode103[]="/angular/js/plugins/jquery-ui/jquery-ui.min.css";
        $cssCode103[]="/css/jquery-confirm.css";
        $cssCode103[]="/angular.css.php";
        $cssCode103[]="/angular/font-awesome/css/all.min.css";
        $cssCode103[]="/angular/plugins/scheduler.css";
        $cssCode103[]="/angular/jquery-picklist.css";
        $cssCode103[]="/angular/plugins/daterangepicker/daterangepicker.css";
        $cssCode103[]="$pluginRoot/tags/bootstrap-tagsinput.css";

        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleCharts"))==1){
            $cssCode103[]="https://www.gstatic.com/charts/loader.js";
        }
        return $cssCode103;
    }

    private function Jcode104():array{
        $pluginRoot=$this->pluginRoot;
        $jsroot=$this->jsroot;
        $jsCode104[]="/js/highcharts.js";
        $jsCode104[]="/js/highcharts-more.js";
        $jsCode104[]="/js/exporting.js";
        $jsCode104[]="/js/cookies.js";
        $jsCode104[]="$pluginRoot/nouslider/wNumb.js";
        $jsCode104[]="$pluginRoot/jsTree/jstree.min.js";
        $jsCode104[]="$pluginRoot/jsTree/ngJsTree.min.js";
        $jsCode104[]="$pluginRoot/sweetalert/sweetalert.min.js";
        $jsCode104[]="$pluginRoot/cron/jquery-gentleSelect-min.js";
        $jsCode104[]="$pluginRoot/cron/jquery-cron-min.js";
        $jsCode104[]="$pluginRoot/colorpicker/bootstrap-colorpicker.min.js";
        $jsCode104[]="$pluginRoot/smoothie/smoothie.js";
        $jsCode104[]="$jsroot/file-upload/jquery.fileupload.js";
        $jsCode104[]="$jsroot/file-upload/jquery.iframe-transport.js";
        $jsCode104[]="/angular/js/plugins/blockUI/jquery.blockUI.js";
        $jsCode104[]="/angular/js/plugins/iCheck/icheck.min.js";
        $jsCode104[]="/angular/font-awesome/js/all.min.js";
        $jsCode104[]="js/jquery-confirm.js";
        $jsCode104[]="js/moment.js";
        $jsCode104[]="js/Chart.bundle.min.js";
        $jsCode104[]="/angular/js/plugins/chartJs/Chart.min.4.4.7.js";
        $jsCode104[]="/angular/js/plugins/chartJs/chartjs-plugin-datalabels.min.js";
        $jsCode104[]="$pluginRoot/chosen/chosen.jquery.js";
        $jsCode104[]="$pluginRoot/flot/jquery.flot.js";
        $jsCode104[]="$pluginRoot/flot/angular-flot.js";
        $jsCode104[]="$pluginRoot/flot/jquery.flot.pie.js";
        $jsCode104[]="$pluginRoot/flot/jquery.flot.time.js";
        $jsCode104[]="$pluginRoot/flot/curvedLines.js";
        $jsCode104[]="$pluginRoot/flot/jquery.flot.resize.js";
        $jsCode104[]="$pluginRoot/flot/jquery.flot.tooltip.min.js";
        $jsCode104[]="$pluginRoot/flot/excanvas.min.js";
        $jsCode104[]="$pluginRoot/flot/jquery.flot.spline.js";
        $jsCode104[]="$pluginRoot/flot/jquery.flot.symbol.js";
        $jsCode104[]="$pluginRoot/codemirror/codemirror.js";
        $jsCode104[]="$pluginRoot/codemirror/mode/xml/xml.js";
        $jsCode104[]="$pluginRoot/clockpicker/clockface.js";
        $jsCode104[]="$pluginRoot/sparkline/jquery.sparkline.min.js";
        $jsCode104[]="$pluginRoot/datapicker/bootstrap-datepicker.js";
        $jsCode104[]="$pluginRoot/morris/raphael-min.js";
        $jsCode104[]="$pluginRoot/morris/morris.min.js";
        $jsCode104[]="$pluginRoot/tags/bootstrap-tagsinput.min.js";
        $jsCode104[]="/angular/plugins/bootstrap-ipaddress/bootstrap-ipaddress.js";
        $jsCode104[]="js/pdf.min.js";
        $jsCode104[]="/angular/plugins/scheduler.js";
        return $jsCode104;
    }




}