<?php
class analyze_page{
	var $url;
	var $results=array();
	
	public function __construct($url=null,$referer=null){
		if($url<>null){$this->url=$url;}
		
		
	}
	
	
	public function parse(){
		$var = $this->fread_url($this->url);
		
		$doc = new DOMDocument();
		$doc->loadHTML($var); //helps if html is well formed and has proper use of html entities!
		$xpath = new DOMXpath($doc);
		$nodes = $xpath->query('//a');
		
		foreach($nodes as $node) {
			$href=$node->getAttribute('href');
			if(!preg_match("#^(http|ftp)#", $href)){continue;}
			$parse=parse_url($href);
			$www=$parse["host"];
			if(strpos($www, ":")>0){$t=explode(":", $www);$www=$t[0];}
			if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
			if(strpos($www,"/")>0){$tb=explode("/",$www);$www=$tb[0];}
			if(preg_match("#google\.[a-z]+#", $www)){continue;}
			if(preg_match("#facebook\.[a-z]+#", $www)){continue;}
			if(preg_match("#twitter\.[a-z]+#", $www)){continue;}
			if(preg_match("#pinterest\.[a-z]+#", $www)){continue;}
			if(preg_match("#instagram\.[a-z]+#", $www)){continue;}
			$this->results[$www]=true;
		}
		
		
		
		
	}
	
	
	private function fread_url($url,$ref=null){
		$cookie=dirname(__FILE__)."/web/logs/cookie.txt";
		
		
		if(!function_exists("curl_init")){$this->error=true;}
			$ch = curl_init($url);
			$user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt( $ch, CURLOPT_HTTPGET, 1 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION , 1 );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION , 1 );
			curl_setopt( $ch, CURLOPT_URL, $url );
			if($ref<>null){
				curl_setopt( $ch, CURLOPT_REFERER, $ref );
			}
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie);
			$html = curl_exec($ch);
			if(curl_errno($ch)){
				if($GLOBALS["VERBOSE"]){echo curl_error($ch)."<br>\n";}
				return false;
			}
			
			curl_close($ch);
			return $html;
		
	}
	
	
}



// The fread_url function allows you to get a complete
// page. If CURL is not installed replace the contents with
// a fopen / fget loop


