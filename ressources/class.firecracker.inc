<?php

class firecracker{
    private $uuid="";

    public function __construct($uuid=null){
        $this->uuid = $uuid;
    }


    public function ImagesList():array{
        $zimages=array();
        $sock=new sockets();
        $json=json_decode($sock->REST_API("/firecracker/images"));
        foreach($json->Images as $fname=>$time){
            if (is_numeric($fname)){
                continue;
            }
            $zimages[$fname]=$time;
        }
        return $zimages;
    }
    public function GetContainerName($uuid):string{
        $q=new lib_sqlite("/home/artica/SQLITE/firecracker.db");
        $ligne=$q->mysqli_fetch_array("SELECT machinename FROM firecracker WHERE uuid='$uuid'");
        return $ligne['machinename'];
    }

    private function GetContainerAddr():string{
        if(isset($_SESSION["FIRECRACKER_API"][$this->uuid]["ADDR"])){
            return $_SESSION["FIRECRACKER_API"][$this->uuid]["ADDR"];
        }
        $q=new lib_sqlite("/home/artica/SQLITE/firecracker.db");
        $ligne=$q->mysqli_fetch_array("SELECT ipaddr FROM firecracker WHERE uuid='$this->uuid'");
        $ipadr=$ligne['ipaddr'];
        $_SESSION["FIRECRACKER_API"][$this->uuid]["ADDR"]="$ipadr:9503";
        return $_SESSION["FIRECRACKER_API"][$this->uuid]["ADDR"];
    }

    public function ImagesListNames():array{
        $zimages=array();
        $sock = new sockets();
        $json = json_decode($sock->REST_API_FIRECR("/firecracker/images"));

        foreach ($json->Images as $fname => $ImageInfo) {
            $Size = FormatBytes($ImageInfo->FileSize / 1024);
            $zimages[$fname] = "$fname Debian $ImageInfo->OperatingSystem ($Size)";
        }

        return $zimages;
    }




    public function REST_API($endpoint){
        $socket = stream_socket_client(
            "tcp://".$this->GetContainerAddr(),
            $errno,
            $errstr,
            20
        );

        if (!$socket) {
            $array["Status"]=false;
            $array["Error"]="$errno - $errstr";
            return json_encode($array);
        }
        $request = "GET $endpoint HTTP/1.1\r\n";
        $request .= "Connection: close\r\n";
        $request .= "Accept: application/json\r\n";
        $request .= "\r\n";
        fwrite($socket, $request);

        $response = '';
        while (!feof($socket)) {
            $response .= fgets($socket, 1024);
        }
        fclose($socket);
        list($headers, $body) = explode("\r\n\r\n", $response, 2);

        $json = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $body;
        } else {
            $array["Status"]=false;
            $array["Error"]="Failed to parse JSON response: " . json_last_error_msg();
            return json_encode($array);
        }
        return $body;

    }




}