<?php

class DockerClient {


    private $curlClient;
    private $socketPath="/var/run/docker.sock";
    private $curlError = null;
    public $ok=false;
    public $mysql_error=null;
    public $headers=array();
    public $bodyContent="";

    public function __construct(string $socketPath=null){
       if($socketPath<>null){
           $this->socketPath=$socketPath;
       }

    }

    public function __destruct(){
        if(!$this->curlClient){return ;}
        curl_close($this->curlClient);
    }



    private function GetJsonData($uri):bool{
        $this->ok=true;
        $socket = fsockopen('unix:///var/run/docker.sock', -1, $errno, $errstr);
        if (!$socket) {
            $this->mysql_error="Error connecting to Docker socket: $errstr ($errno)";
            $this->ok=false;
            return false;
        }
        $request = "GET $uri HTTP/1.1\r\n";
        $request .= "Host: localhost\r\n";
        $request .= "Connection: close\r\n";
        $request .= "\r\n";

        fwrite($socket, $request);

        $response = '';

        while (!feof($socket)) {
            $response .= fgets($socket);
        }

        fclose($socket);
        $this->headers=array();
        $contentLength=0;

        list($headers, $body) = explode("\r\n\r\n", $response, 2);
        foreach (explode("\r\n", $headers) as $header) {
            if (strpos($header, 'Content-Length:') === 0) {
                $contentLength = (int)trim(substr($header, 15));
                continue;
            }

            if(strpos($header,":")==0){continue;}
            $tr=explode(":",$header);
            $this->headers[trim($tr[0])]=$tr[1];

        }

        if($contentLength>0) {
            $this->bodyContent = substr($body, 0, $contentLength);
            return true;
        }

        $jsonStart = strpos($response, "\r\n\r\n") + 4;
        $bodyContent = substr($response, $jsonStart);
        $bb=explode("\n",$bodyContent);
        $final=array();

        foreach ($bb as $line){
            if(is_null($line)){continue;}
            if(strpos(" $line","{")==0){continue;}
            if(is_numeric($line)){continue;}
            $final[]=$line;

        }

        $this->bodyContent=trim(@implode("\n",$final));
        return true;

    }

    public function dispatchCommand(string $endpoint, array $parameters = null): array{

        if(!$this->GetJsonData($endpoint)){
            if(function_exists("VERBOSE")){
                VERBOSE("$this->mysql_error",__LINE__);
            }
            return array();
        }

        $json=json_decode( $this->bodyContent, true);
        if(is_null($json)){
            $this->mysql_error="Error json: ".json_last_error();
            $this->ok = false;
            return array();
        }
        if(!$json){
            $this->mysql_error="Error json: ".json_last_error();
            $this->ok = false;
            return array();
        }

        return $json;
    }

}
