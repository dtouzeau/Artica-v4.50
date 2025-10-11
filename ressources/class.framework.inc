<?php
define('fcgiclient',dirname(__FILE__).'/externals/hollodotme/FastCGI');


include_once(fcgiclient.'/Interfaces/EncodesNameValuePair.php');
include_once(fcgiclient.'/Interfaces/EncodesPacket.php');
include_once(fcgiclient.'/Interfaces/ProvidesRequestData.php');
include_once(fcgiclient.'/Interfaces/ProvidesResponseData.php');

include_once(fcgiclient.'/Constants/RequestMethod.php');
include_once(fcgiclient.'/Constants/ServerProtocol.php');

include_once(fcgiclient.'/Encoders/NameValuePairEncoder.php');
include_once(fcgiclient.'/Encoders/PacketEncoder.php');

include_once(fcgiclient.'/Sockets/Socket.php');
include_once(fcgiclient.'/Sockets/SocketId.php');
include_once(fcgiclient.'/Sockets/SocketCollection.php');

include_once(fcgiclient.'/Responses/Response.php');
include_once(fcgiclient.'/Requests/AbstractRequest.php');
include_once(fcgiclient.'/Requests/PostRequest.php');
include_once(fcgiclient.'/Requests/GetRequest.php');

include_once(fcgiclient.'/Client.php');
include_once(fcgiclient.'/Interfaces/ConfiguresSocketConnection.php');
include_once(fcgiclient.'/SocketConnections/UnixDomainSocket.php');

include_once(fcgiclient.'/Exceptions/FastCGIClientException.php');
include_once(fcgiclient.'/Exceptions/ConnectException.php');
include_once(fcgiclient.'/Exceptions/TimedoutException.php');
include_once(fcgiclient.'/Exceptions/WriteFailedException.php');
include_once(fcgiclient.'/Exceptions/ReadFailedException.php');

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Requests\GetRequest;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
class fcgi_framework{
    public $ok=true;
    private $uri;
    private $phppage=null;
    private $QUERY_STRING;
    public $IOERROR;

    public function __construct($uri){

        $this->uri=$uri;
    }

    public function Get():string{
        $connection = new UnixDomainSocket(
            '/var/run/php-fpm-framework.sock',     # Socket path
            5000,                               # Connect timeout in milliseconds (default: 5000)
            5000                                # Read/write timeout in milliseconds (default: 5000)
        );
        $this->ok=true;
        $this->ParseQuery();
        $client     = new Client();

        $request    = new GetRequest($this->phppage,'');
        $request->setCustomVar('QUERY_STRING', $this->QUERY_STRING);
        try {
            $response = $client->sendRequest( $connection, $request );
            return $response->getBody();
        }catch(Exception $e){
            $this->IOERROR=$e->getMessage();
            $this->syslog($this->IOERROR);
            $this->ok=false;
            return "";
        }
    }

    private function ParseQuery(){
        $this->QUERY_STRING=null;
        $uri="http://127.0.0.1/$this->uri";
        $parse=parse_url($uri);
        $this->phppage=basename($parse["path"]);
        if(isset($parse["query"])) {
            $this->QUERY_STRING = "&" . $parse["query"];
        }
    }
    function syslog($text):bool{
        if(!function_exists("syslog")){return false;}
        openlog("artica-framework", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
        return true;
    }

}