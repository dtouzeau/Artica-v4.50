<?php


if(isset($_GET["uri"])){
	
	
	
	echo "<center  >
	
	<iframe style='margin:5px;background-color:black;border:3px solid #A0A0A0;
			padding:5px;margin;5px;border-radius:5px 5px 5px 5px;-moz-border-radius:5px;
			-webkit-border-radius:5px;'width='853' height='480' 
	src='{$_GET["uri"]}' 
	frameborder='0' allowfullscreen></iframe></center>";
}