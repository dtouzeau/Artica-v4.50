<?php
if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}


class wazhu_rest{
    private $cURLConnection;
    private $WazhuClientServer;
    private $WazhuClientApiPort;
    private $WazhuClientApiUser;
    private $WazhuClientApiPassword;
    private $Token=null;
    public $error=null;
    public function __construct(){
        $this->WazhuClientServer = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientServer"));
        $this->WazhuClientApiPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientApiPort"));
        if($this->WazhuClientApiPort ==0){$this->WazhuClientApiPort = 55000;}
        $this->WazhuClientApiUser= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientApiUser"));
        $this->WazhuClientApiPassword= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientApiPassword"));
        if(  $this->WazhuClientApiUser==null){  $this->WazhuClientApiUser="wazuh";}
        if(  $this->WazhuClientApiPassword==null){  $this->WazhuClientApiPassword="wazuh";}
    }

    public function agents_delete($nodeid):bool{
        $this->CurlInit();
        $URL="https://$this->WazhuClientServer:$this->WazhuClientApiPort/agents?older_than=0s&agents_list=$nodeid&status=all";
        curl_setopt($this->cURLConnection, CURLOPT_URL, $URL);
        curl_setopt($this->cURLConnection, CURLOPT_CUSTOMREQUEST, "DELETE");

        $resp = curl_exec($this->cURLConnection);
        $errno = curl_errno($this->cURLConnection);

        if($errno>0){
            $this->error="Connection Error $errno ".curl_strerror($errno);
            $this->ok=false;
            return false;
        }

        if($this->http_error($resp)){
            $this->ok=false;
            return false;
        }

        curl_close($this->cURLConnection);

       return true;
    }

    private function http_error_build($content):bool{
        $errornumbner = 0;
        $errortext = "unknown";

        $responseCode = curl_getinfo($this->cURLConnection, CURLINFO_HTTP_CODE);
        if (property_exists($content, "error")) {
            $errornumbner = $content->error;
        }
        if (property_exists($content, "detail")) {
            $errortext = $content->detail;
        }
        $this->error = "($this->WazhuClientServer:$this->WazhuClientApiPort) HTTP Error code $responseCode Err.$errornumbner $errortext";
        return true;
    }

    private function http_error($resp):bool{

        $resposeLenght=strlen($resp);
        $responseCode = curl_getinfo($this->cURLConnection, CURLINFO_HTTP_CODE);
        $content=json_decode($resp);

        if($responseCode>200){
            $this->http_error_build($content);
            return true;
        }
        if(!property_exists($content,"error")) {
            $this->error = "Protocol error HTTP Code: $responseCode Response: $resposeLenght bytes";
            $this->ok = false;
            curl_close($this->cURLConnection);
            return true;
        }
        if($content->error>0){
            $this->http_error_build($content);
            curl_close($this->cURLConnection);
            $this->ok=false;
            return true;
        }
        return false;
    }

    public function agents_list($search=null):array{
        $this->CurlInit();
        $this->ok=true;

        $tt="agents_list";
        if($search<>null){$tt="search=".urlencode($search);}

        $URL="https://$this->WazhuClientServer:$this->WazhuClientApiPort/agents?$tt";
        curl_setopt($this->cURLConnection, CURLOPT_URL, $URL);
        $resp = curl_exec($this->cURLConnection);
        $errno=curl_errno($this->cURLConnection);

        if($errno>0){
            $this->error="Error $errno ".curl_strerror($errno);
            $this->ok=false;
            return array();
        }

        if($this->http_error($resp)){
            $this->ok=false;
            return array();
        }
        curl_close($this->cURLConnection);
        $array=json_decode($resp);

        $results=array();
        foreach ($array->data->affected_items as $index=>$stdclass){
            $id=$stdclass->id;
            $name=$stdclass->name;
            $results[$id]=array($name,$stdclass->ip,$stdclass->status,$stdclass->lastKeepAlive);
        }
        return $results;
    }

    public function Auth():bool{
        $this->CurlInit();
        curl_setopt($this->cURLConnection,
            CURLOPT_USERPWD,
            "$this->WazhuClientApiUser:$this->WazhuClientApiPassword");

        $URL="https://$this->WazhuClientServer:$this->WazhuClientApiPort/security/user/authenticate";
        curl_setopt($this->cURLConnection, CURLOPT_URL, $URL);

        $resp = curl_exec($this->cURLConnection);
        $errno=curl_errno($this->cURLConnection);

        if($errno>0){
            $this->error="Error $errno ".curl_strerror($errno);
            return false;
        }
        if($this->http_error($resp)){
            $this->ok=false;
            return false;
        }
        curl_close($this->cURLConnection);
        $array=json_decode($resp);
        if(!property_exists($array,"error")){
            $this->error="Protocol error";
            return false;
        }

        if($array->error>0){
            $this->error="API return error code $this->error";
            return false;
        }
        if(!property_exists($array,"data")){
            $this->error="Protocol error (no data)";
            return false;
        }
        $this->Token=$array->data->token;
        return true;
    }

    private function CurlInit():bool{
        $this->error="";
        $this->cURLConnection = curl_init();
        curl_setopt($this->cURLConnection, CURLOPT_NOPROXY,"*");
        curl_setopt($this->cURLConnection,CURLOPT_SSL_VERIFYHOST,0);
        curl_setopt($this->cURLConnection,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($this->cURLConnection, CURLOPT_SSLVERSION,'all');
        curl_setopt($this->cURLConnection, CURLOPT_RETURNTRANSFER, true);

        if($this->Token<>null) {
            $headers = [
                "Accept: text/html,application/json",
                "Accept-Encoding: gzip, deflate",
                "Accept-Language: en-US,en;q=0.5",
                "Cache-Control: no-cache",
                "Authorization: Bearer $this->Token",
                "User-Agent: Artica Wazhu Api Client 1.0"
            ];

            curl_setopt($this->cURLConnection, CURLOPT_HTTPHEADER, $headers);
        }

        return true;

    }


}