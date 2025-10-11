<?
	$challenge = $_REQUEST['challenge'];
	$userurl   = $_REQUEST['userurl'];
	$res	   = $_REQUEST['res'];
	$qs        = $_SERVER["QUERY_STRING"];

    $uamip     = $_REQUEST['uamip'];
    $uamport   = $_REQUEST['uamport'];


    //--There is a bug that keeps the logout in a loop if userurl is http%3a%2f%2f1.0.0.0 ---/
    //--We need to remove this and replace it with something we want
    if (preg_match("/1\.0\.0\.0/i", $userurl)) {
        $default_site = 'google.com';
        $pattern = "/1\.0\.0\.0/i";
        $userurl = preg_replace($pattern, $default_site, $userurl);
    }
    //---------------------------------------------------------

	if($res == 'success'){

		header("Location: $userurl");
		print("\n</html>");
	}

	if($res == 'failed'){

		header("Location: fail.php?".$qs);
		print("\n</html>");

	}

    //-- cookie add on -------------------------------
    if($res == 'notyet'){

        if(isset($_COOKIE['hs'])){

                $uamsecret  = 'greatsecret';
                $dir        = '/logon';
                $userurl    = $_REQUEST['userurl'];
                $redir      = urlencode($userurl);

                $username   = $_COOKIE['hs']['username'];
                $password   = $_COOKIE['hs']['password'];
                $enc_pwd    = return_new_pwd($password,$challenge,$uamsecret);
                $target     = "http://$uamip".':'.$uamport.$dir."?username=$username&password=$enc_pwd&userurl=$redir";
                header("Location: $target");
                print("\n</html>");
        }
    }
    //Function to do the encryption thing of the password
    function return_new_pwd($pwd,$challenge,$uamsecret){
            $hex_chal   = pack('H32', $challenge);                  //Hex the challenge
            $newchal    = pack('H*', md5($hex_chal.$uamsecret));    //Add it to with $uamsecret (shared between chilli an this script)
            $response   = md5("\0" . $pwd . $newchal);              //md5 the lot
            $newpwd     = pack('a32', $pwd);                //pack again
            $password   = implode ('', unpack('H32', ($newpwd ^ $newchal))); //unpack again
            return $password;
    }

    
    $IMAGE_HEADERS=$_SERVER["SERVER_NAME"]."/img";
    
    $DEFAULT_SPLASH=unserialize(@file_get_contents("default.cfg"));
    $TITLE_HTML="{$DEFAULT_SPLASH["HS_LOC_NAME"]}";
    $LINK_HTML="<a href=\"{$DEFAULT_SPLASH["HS_PROVIDER_LINK"]}\">{$DEFAULT_SPLASH["HS_PROVIDER"]}</a>";
    $DEFAULT_LOGO="img/default-logo.png";
    
    //-- End Cookie add on ------------

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><? echo($TITLE_HTML) ?></title>
        <link href="img/logo.ico" type="image/x-icon" rel="icon"/><link href="img/logo.ico" type="image/x-icon" rel="shortcut icon"/>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="pragma" content="no-cache" />
        <meta http-equiv="expires" content="-1" />
        <script type="text/javascript" src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
       
        <style type="text/css">

			body{
				font: 10pt Arial, Helvetica, sans-serif;
				background: #fffff;
			}
			#sum{
				width: 485px;
				height: 221px;
				margin: 50px auto;
			}
			h1{
				width: 401px;
				height: 127px;
				background: transparent url('<? echo($DEFAULT_LOGO) ?>') no-repeat;
				margin: 0 27px 21px;
			}
	
			h1 span{
				display: none;
			}
			#content{
				width: 485px;
				height: 221px;
				background: url('<? echo($IMAGE_HEADERS) ?>/form.png') no-repeat;	
			}
			.f{
				padding: 45px 50px 45px 38px;	
				overflow: hidden;
			}
			.field{
				clear:both;
				text-align: right;
				margin-bottom: 15px;
			}
			.field label{
				float:left;
				font-weight: bold;
				line-height: 42px;
			}
			.field input{
				background: #fff url('<? echo($IMAGE_HEADERS) ?>/input.png') no-repeat;
				outline: none;
				border: none;
				font-size: 10pt;
				padding: 7px 9px 8px;
				width: 279px;
				height: 25px;
				font-size: 18px;
				font-weight:bolder;
				color:#444444;
			}
			.field input.active{
				background: url('<? echo($IMAGE_HEADERS) ?>/input_act.png') no-repeat;
			}
			.button{
				width: 297px;
				float: right;
			}
			.button input{
				width: 69px;
				background: url('<? echo($IMAGE_HEADERS) ?>/btn_bg.png') no-repeat;
				border: 0;
				font-weight: bold;
				height: 27px;
				float: left;
				padding: 0;
			}
        
		</style>

</head>

<body>
<div style="postition:absolute;top:0px;left:80%;width:100%">
<table style='width:100%;padding:0px;margin:0px'>
<tbody><tr>
<td width=100%>&nbsp;<td>
<td width=1% nowrap><div id="user_info" style='text-align:right;width:90px'>
 <div id="langs" style="text-align:right;">

 </div>
</div>
</td>
</tr>
</tbody>
</table>
</div>

  <div id="sum">
    <div id="header">
      <h1><span>{TEMPLATE_TITLE_HEAD}</span></h1>
    </div>




    <div id="content">

			 <form name="login" action="login.php" method="post">
			   <input type="hidden" name="uamip" value="<? echo($uamip) ?>" />
               <input type="hidden" name="uamport" value="<? echo($uamport) ?>" />
               <input type="hidden" name="challenge" value="<? echo($challenge) ?>" />
               <input type="hidden" name="userurl" value="<? echo(urlencode($userurl)) ?>" />
               <input type="hidden" name="userurl" id='sel_lang' value="en" />
			 
				<div class="f">
					<div class="field">
						<label for="username">User name:</label> <input type="text" name="username" id="l_username" onfocus="this.setAttribute('class','active')" onblur="this.removeAttribute('class');" OnKeyPress="javascript:SendLogon(event)">
		
					</div>
					<div class="field">
						<label for="fpassword">Password:</label> <input type="password" name="password" id="l_password" onfocus="this.setAttribute('class','active')" onblur="this.removeAttribute('class');" OnKeyPress="javascript:SendLogon(event)">
						<div id='lostpassworddiv'></div>
					</div>
					<div class="field button">
						<input type="submit" value="submit"/>
					</div>
				</div>
		
			</form>			
    </div><!-- /#content -->

    <div class="footer">
    	<center style='font-size:13px;font-weight:bold;color:black'><? echo($LINK_HTML) ?><br>Copyright <? echo(date("Y")) ?></center>
    </div><!-- /#footer -->
  </div>
  
</body>
<script type="text/javascript">
  document.login.username.focus();
</script>
</html>

