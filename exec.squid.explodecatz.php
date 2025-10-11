<?php


if($argv[1]=='--file'){explodeCatz($argv[2]);exit();}




function explodeCatz($filename){
	
	
	$dir=dirname($filename);
	$f=file($filename);
	
	foreach ( $f as $index=>$line ){
		$line=trim($line);
		if(empty($line)){continue;}
		if(sex($line)){
			$sex[]=$line;
			unset($f[$index]);
			continue;
		}
		
		if(mixed_adult($line)){
			$mixed[]=$line;
			unset($f[$index]);
			continue;			
			
		}
		
		if(gamble($line)){
			$gamble[]=$line;
			unset($f[$index]);
			continue;			
		}
		
		if(games($line)){
			$games[]=$line;
			unset($f[$index]);
			continue;
		}			
		
		if(radio($line)){
			$radio[]=$line;
			unset($f[$index]);
			continue;	
		}
		
		if(music($line)){
			$music[]=$line;
			unset($f[$index]);
			continue;
		}
		
		if(webtv($line)){
			$webtv[]=$line;
			unset($f[$index]);
			continue;			
		}
		
		if(movies($line)){
			$movies[]=$line;
			unset($f[$index]);
			continue;
		}

		
		
		
	}
	
	@file_put_contents($filename, @implode($f));
	@file_put_contents("$dir/".time().".music.txt", @implode("\n", $music));
	@file_put_contents("$dir/".time().".radio.txt", @implode("\n", $radio));
	@file_put_contents("$dir/".time().".sex.txt", @implode("\n", $sex));
	@file_put_contents("$dir/".time().".movies.txt", @implode("\n", $movies));
	@file_put_contents("$dir/".time().".games.txt", @implode("\n", $games));
	@file_put_contents("$dir/".time().".webtv.txt", @implode("\n", $webtv));
	@file_put_contents("$dir/".time().".gamble.txt", @implode("\n", $gamble));
	@file_put_contents("$dir/".time().".mixed_adult.txt", @implode("\n", $mixed));
	
	
}

function mixed_adult($line){
	if(preg_match("#flirt#", $line)){return true; }
	if(preg_match("#girl#", $line)){return true; }
}

function radio($line){
	if(preg_match("#radio#", $line)){return true;}
}

function webtv($line){
	if(preg_match("#podcastv#", $line)){return false;}
	if(preg_match("#tv[\.-]#", $line)){return true;}
	if(preg_match("#livetv#", $line)){return true; }
	if(preg_match("#webtv#", $line)){return true; }
}

function games($line){
if(preg_match("#game#", $line)){return true;}
if(preg_match("#xbox#", $line)){return true;}
}

function gamble($line){
if(preg_match("#gambling#", $line)){return true;}
if(preg_match("#poker#", $line)){return true;}
if(preg_match("#casino#", $line)){return true;}	
	
}

function movies($line){
	if(preg_match("#movie#", $line)){return true;}
	if(preg_match("#movies#", $line)){return true;}
	if(preg_match("#film#", $line)){return true;}
}

function sex($line){
	if(preg_match("#videoxx#", $line)){return true;}
	if(preg_match("#hotvideo#", $line)){return true;}
	if(preg_match("#hot.*?video#", $line)){return true;}
	if(preg_match("#-hot-#", $line)){return true;}
	if(preg_match("#suck#", $line)){return true;}
	if(preg_match("#fuck#", $line)){return true;}
	if(preg_match("#camgirl#", $line)){return true;}
	if(preg_match("#playgurl#", $line)){return true;}
	if(preg_match("#playgirl#", $line)){return true;}
	if(preg_match("#lesbian#", $line)){return true;}
	if(preg_match("#playboy#", $line)){return true;}
	if(preg_match("#sex#", $line)){return true;}
	if(preg_match("#adult#", $line)){return true;}
	if(preg_match("#porn#", $line)){return true;}
	if(preg_match("#^x-#", $line)){return true;}
	if(preg_match("#xxx#", $line)){return true;}
	if(preg_match("#gay#", $line)){return true;}
	if(preg_match("#hot.*?girl#", $line)){return true;}
	if(strpos($line, "-x-")>0){return true;}
	if(strpos($line, "-cum-")>0){return true;}
	if(strpos($line, "videosx")>0){return true;}
	if(strpos($line, "lesbian")>0){return true;}
	
}

function music($line){
	if(preg_match("#^mtv#", $line)){return true;}
	if(preg_match("#music#", $line)){
		if(preg_match("#video#", $line)){return false;}
		return true;
	}
	if(preg_match("#mp[3|4|2]#", $line)){return true;}
}