<?php

/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2013 César Rodas                                                  |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/

namespace crodas\InfluxPHP;

class BaseHTTP
{
    protected $host;
    protected $port;
    protected $user;
    protected $pass;
    protected $base;
    protected $timePrecision = 's';
    protected $children = array();
    protected $xurl;

    const SECOND        = 's';
    const MILLISECOND   = 'm';
    const MICROSECOND   = 'u';
    const S     = 's';
    const MS    = 'm';
    const US    = 'u';

    protected function inherits(BaseHTTP $c)
    {
        $this->user   = $c->user;
        $this->pass   = $c->pass;
        $this->port   = $c->port;
        $this->host   = $c->host;
        $this->timePrecision = $c->timePrecision;
        $c->children[] = $this;
    }

    protected function getCurl($url, array $args = array())
    {
    	if($GLOBALS["VERBOSE"]){echo "getCurl({$this->host}:{$this->port})\n";}
        $url  = "http://{$this->host}:{$this->port}/{$this->base}{$url}";
        $url .= "?" . http_build_query($args);
        if($GLOBALS["VERBOSE"]){echo "getCurl exec: $url\n";}
        $ch   = curl_init($url);
        $this->xurl=$url;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOPROXY,"127.0.0.1,localhost");
        curl_setopt($ch, CURLOPT_PROXY,null);
        return $ch;
    }

    protected function execCurl($ch, $json = false){
        $response = curl_exec ($ch);
        $status   = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //$type     = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        
        curl_close($ch);
        
        
    	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: ".basename(__FILE__)." RESULT:{$status[0]}\n";}
        
        if ($status[0] != 2) {
        	 $trace=debug_backtrace();
        	 $CountDeTrace=count($trace);
        	 $this->Error("**********************************************************************",__FUNCTION__,__LINE__);
        	 $this->Error($this->xurl,__FUNCTION__,__LINE__);
             $this->Error("Error:$response - $status - Traces:$CountDeTrace",__FUNCTION__,__LINE__);
             
             $GLOBALS['LAST_ERROR_INFLUX']="$response - $status";
             if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: ".basename(__FILE__)." RESULT:$response - $status\n";}
             
             if(isset($GLOBALS["TRACE_INFLUX"])){
             	if($GLOBALS["TRACE_INFLUX"]<>null){
             		$this->Error("Trace {$GLOBALS["TRACE_INFLUX"]}",__FUNCTION__,__LINE__);
             		$GLOBALS["TRACE_INFLUX"]=null;
             	}
             }
             
             
             for($i=0;$i<$CountDeTrace;$i++){
             	$file=basename($trace[$i]["file"]);
             	$function=$trace[$i]["function"];
             	$line=$trace[$i]["line"];
             	$this->Error("Trace $i/$CountDeTrace: $file $function $line",__FUNCTION__,__LINE__);
             	
             }
             return json_decode($response, true);
        }
        $GLOBALS["TRACE_INFLUX"]=null;
        if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: ".basename(__FILE__)." RESULT:$response - $status\n";}
        return $json ? json_decode($response, true) : $response;
    }

    protected function delete($url){
        $ch = $this->getCurl($url);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => "DELETE",
        ));

        return $this->execCurl($ch);
    }

    public function getTimePrecision()
    {
        return $this->timePrecision;
    }

    public function setTimePrecision($p)
    {
        switch ($p) {
        case 'm':
        case 's':
        case 'u':
            $this->timePrecision = $p;
            if ($this instanceof Client) {
                foreach ($this->children as $children) {
                    $children->timePrecision = $p;
                }
            }
            return $this;
        }

        throw new \InvalidArgumentException("Expecting s, m or u as time precision");
    }

    protected function get($url, array $args = array())
    {
        $ch = $this->getCurl($url, $args);
        return $this->execCurl($ch, true);
    }
    
    
    private function Error($text,$function,$line){
    	if(isset($GLOBALS["VERBOSE"])){
    		if($GLOBALS["VERBOSE"]){$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;}
    	}
    	if(!isset($GLOBALS["DEBUG_INFLUX_VERBOSE"])){$GLOBALS["DEBUG_INFLUX_VERBOSE"]=false;}
    	if($GLOBALS["DEBUG_INFLUX"]){
    		if(function_exists("events")){events("$function/$line $text");}
    	}
    	 
    	 
    	$REBUILD=false;
    	$filename=basename(__FILE__);
    	$date=date("Y-m-d H:i:s");
    	if(function_exists("getmypid")){$pid=getmypid();}
    	$line="$date [$pid] $filename: [$function/$line] $text\n";
    	if($GLOBALS["DEBUG_INFLUX_VERBOSE"]){echo $line;}
    	$common="/var/log/influx.client.log";
    	$size=@filesize($common);
    	if($size>100000){@unlink($common);$REBUILD=true;}
    	$h = @fopen($common, 'a');
    	@fwrite($h,$line);
    	@fclose($h);
    	if($REBUILD){@chmod($common,0777);}
    	 
    }    

    
    protected function post($url, array $body, array $args = array())
    {
        
    	
    	$ch = $this->getCurl($url, $args);
        curl_setopt_array($ch, array(
            CURLOPT_POST =>  1,
            CURLOPT_POSTFIELDS => json_encode($body),
        ));

        return $this->execCurl($ch);
    }

}
