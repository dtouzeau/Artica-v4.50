<?php


class GoogleSafeAPI{
    public $last_errorstring='';
    public $last_errorcode=0;
    protected $base_api_url;
    protected $protocol_version;
    protected $our_version;
    protected $our_id;


    function __construct($api_key){
        $this->api_key=$api_key;
        $this->base_api_url='https://safebrowsing.googleapis.com/v4/';
        $this->protocol_version="4.0";
        $this->our_version='2.0';
        $this->our_id='';

    }

    protected function buildAPIURL($command){
        return $this->base_api_url.$command.'?key='.urlencode($this->api_key);
    }

    protected function getClientRequestData(){
        return array(
            'clientId' => $this->our_id,
            'clientVersion' => $this->our_version
        );
    }


    function query(){

        $curl = curl_init();

        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_POST, $post );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_BINARYTRANSFER, true );// not needed from PHP 5.2 onwards
        if($data){
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
        }
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json') );

        $content = curl_exec( $curl );
        if($this->last_errorcode = curl_errno($curl)) {
            // curl errors are 1-89, but some might be added in future
            $this->last_errorstring = curl_error($curl);
        }else{
            $this->last_errorstring = '';
            $this->last_errorcode = 0;
        }
        $response = curl_getinfo( $curl );
        curl_close( $curl );



    }



}