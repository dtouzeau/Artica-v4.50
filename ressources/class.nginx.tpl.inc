<?php
class NginxTemplates{
    public $mysql_error="";
    public $TemplateName="";
    private $tplid=0;
    public $serviceid=0;

    function __construct($tplid=0){
        $this->tplid=$tplid;
        $this->LoadData();
    }
    private function LoadData():void{
        if($this->tplid==0){
            return;
        }
        $tplid=intval($this->tplid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/template/$tplid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"])){
            $this->mysql_error=is_array($data)&&isset($data["Error"])?$data["Error"]:"bad JSON response";
            return;
        }
        $this->TemplateName=isset($data["Tpname"])?$data["Tpname"]:"";
    }

    public function CreateSite($servicename,$domains=""):bool{
        $tplid=intval($this->tplid);
        if($tplid==0){
            $this->mysql_error="Template ID is 0";
            return false;
        }
        if(strlen($servicename)==0){
            $this->mysql_error="servicename is required";
            return false;
        }
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/template/create-site/$tplid",array(
            "servicename"=>$servicename,"domains"=>$domains
        ));
        $data=json_decode($resp,true);
        if(!is_array($data)){
            $this->mysql_error="bad JSON response: ".substr($resp,0,500);
            return false;
        }
        if(empty($data["Status"])){
            $this->mysql_error=isset($data["Error"])?$data["Error"]:"unknown";
            return false;
        }
        $this->serviceid=intval($data["ID"]);
        if($this->serviceid==0){
            $this->mysql_error="API returned ID=0";
            return false;
        }
        return true;
    }


}