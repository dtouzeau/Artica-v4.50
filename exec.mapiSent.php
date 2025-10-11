<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include('/usr/share/php/mapi/mapi.util.php');
include('/usr/share/php/mapi/mapidefs.php');
include('/usr/share/php/mapi/mapicode.php');
include('/usr/share/php/mapi/mapitags.php');
include('/usr/share/php/mapi/mapiguid.php');
include('/usr/share/php/mapi/class.recurrence.php');
include('/usr/share/php/mapi/class.freebusypublish.php');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

ScanMailBox("david");

function ScanMailBox($uid){
	$ct=new user($uid);
	$sock=new sockets();
	$username = $uid;
	$password = $ct->password;
	$zarafa_sock = "file:///var/run/zarafa";
	$ZarafaServerListenIP=$sock->GET_INFO("ZarafaServerListenIP");
	if($ZarafaServerListenIP==null){$ZarafaServerListenIP="127.0.0.1";}
	if($ZarafaServerListenIP=="0.0.0.0"){$ZarafaServerListenIP="127.0.0.1";}
	$ZarafaServerListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaServerListenPort"));
	if($ZarafaServerListenPort==0){$ZarafaServerListenPort=236;}
	$zarafaServer = "http://$ZarafaServerListenIP:$ZarafaServerListenPort/zarafa";
	$session = mapi_logon_zarafa($username, $password, $zarafaServer);
	$hard_delete_messages = true;
	$folder_to_process = 'Sent Items';
	$total_deleted = 0;
	if(!$session) { print "Unable to open session $username@$zarafaServer `$password`\n"; return; }
	$msgstorestable = mapi_getmsgstorestable($session);
	if(!$msgstorestable) { print "Unable to open message stores table\n"; return; }
	$msgstores = mapi_table_queryallrows($msgstorestable, array(PR_DEFAULT_STORE, PR_ENTRYID));

	foreach ($msgstores as $row) {
		if($row[PR_DEFAULT_STORE]) {
			$default_store_entry_id = $row[PR_ENTRYID];
	
		}
	}

	$default_store = mapi_openmsgstore($session, $default_store_entry_id );
	if(!$default_store) { print "Unable to open default store\n"; exit(1); }
	$root = mapi_msgstore_openentry($default_store);
	// get folders
	$folders = mapi_folder_gethierarchytable($root, CONVENIENT_DEPTH);
	// loop over every folder
while(1) {
	$rows = mapi_table_queryrows($folders, array(PR_DISPLAY_NAME, PR_FOLDER_TYPE, PR_ENTRYID), 0, 100);

	if(count($rows) == 0)
		break;

	foreach($rows as $row) {
		// skip searchfolders
		if(isset($row[PR_FOLDER_TYPE]) && $row[PR_FOLDER_TYPE] == FOLDER_SEARCH) continue;

		// operate only on folders, whose name is specified in the config section.
		// Like 'Sent Objects'.
		if( $row[PR_DISPLAY_NAME] == $folder_to_process ) {
			parse_messages( $default_store, $row[PR_ENTRYID] );
		}
	}
}


}

function parse_messages($store, $entryid){
	global $total_deleted;
	$folder = mapi_msgstore_openentry($store, $entryid);
	if(!$folder) { print "Unable to open folder."; return false; }
	$table = mapi_folder_getcontentstable($folder);
	if(!$table) { print "Unable to open table."; return false; }

	$org_hash = null;
	$dup_messages = array();
	$dup_count = 0;

	$result = mapi_table_sort( $table, array( PR_CLIENT_SUBMIT_TIME => TABLE_SORT_DESCEND ) );
	if( $result == false ) {echo "Could not sort table\n";return;}

	while(1) {
		// query messages from folders content table
		$filters=array(PR_MESSAGE_SIZE, PR_CLIENT_SUBMIT_TIME, PR_MESSAGE_RECIPIENTS,PR_EMAIL_ADDRESS, PR_BODY, PR_HTML, PR_ENTRYID, PR_SUBJECT , PR_SMTP_ADDRESS);
		$rows = mapi_table_queryrows($table,$filters,0,50);

		
		if(count($rows) == 0) break;

		// we got the messages
		foreach($rows as $row) {
			// hash message body (plaintext + html + subject)
			$md5_subject = md5( $row[PR_SUBJECT] );
			$md5_body    = md5( $row[PR_BODY] );
			$md5_html    = md5( $row[PR_HTML] );
			$md5_eid     = $row[PR_ENTRYID];
			$PR_EMAIL_ADDRESS     = $row[PR_EMAIL_ADDRESS];
			$PR_SMTP_ADDRESS     = $row[PR_SMTP_ADDRESS];
		}
		$message = mapi_msgstore_openentry($store, $md5_eid);
		$recipTable = mapi_message_getrecipienttable ($message);
		$oldRecipients = mapi_table_queryallrows($recipTable, array(PR_ENTRYID, PR_DISPLAY_NAME, PR_EMAIL_ADDRESS, PR_RECIPIENT_ENTRYID, PR_RECIPIENT_TYPE, PR_SEND_INTERNET_ENCODING, PR_SEND_RICH_INFO, PR_RECIPIENT_DISPLAY_NAME, PR_ADDRTYPE, PR_DISPLAY_TYPE, PR_RECIPIENT_TRACKSTATUS, PR_RECIPIENT_FLAGS, PR_ROWID));
		print_r($oldRecipients);
	}

}
/*
 * Begin functions 
 */

function delete_messages( $folder, $messages ) 
{
  global $hard_delete_messages;

  if( $hard_delete_messages ) {
	$result = mapi_folder_deletemessages( $folder, $messages, DELETE_HARD_DELETE );
  }
  else {
	$result = mapi_folder_deletemessages( $folder, $messages );
  }
  
  if( $result == false ) {
 	echo " [-] Failed to delete message\n";
  }
}


function delete_duplicate_messages($store, $entryid)
{
    global $total_deleted; 

    $folder = mapi_msgstore_openentry($store, $entryid);
    if(!$folder) { print "Unable to open folder."; return false; }

    $table = mapi_folder_getcontentstable($folder);
    if(!$table) { print "Unable to open table."; return false; }

    $org_hash = null;
    $dup_messages = array();
    $dup_count = 0;

    $result = mapi_table_sort( $table, array( PR_SUBJECT => TABLE_SORT_ASCEND ) );
    if( $result == false ) {
      die( "Could not sort table\n" );
    }

    while(1) {
	// query messages from folders content table
        $rows = mapi_table_queryrows($table, array(PR_MESSAGE_SIZE, PR_CLIENT_SUBMIT_TIME, PR_BODY, PR_HTML, PR_ENTRYID, PR_SUBJECT ), 0, 50 );

       	if(count($rows) == 0) break;
	
	// we got the messages 
        foreach($rows as $row) {
		// hash message body (plaintext + html + subject)
		$md5_subject = md5( $row[PR_SUBJECT] );
		$md5_body    = md5( $row[PR_BODY] ); 
		$md5_html    = md5( $row[PR_HTML] );

		
		// concat hashes, just in case there are messages with 
		// no HTML or plaintext content.
		$cur_hash = $md5_body . $md5_html . $md5_subject;
		
		// when we have accumulated enough messages, perform a burst delete 
		if( $dup_count == 50 ) {
			echo " [i] Deleting $dup_count duplicates...";
			delete_messages( $folder, $dup_messages );

			// reset the delete-queue
			$dup_messages = array();
			$dup_count    = 0;
			$total_deleted += 100;

			echo "done.\n";
			echo "Deleted $total_deleted messages so far.\n\n";
		}
		
		// duplicate messages are adjacent, so we push the first message with
		// a distinct hash and mark all following messages with this hash 
		// for deletion.
		if( $org_hash != $cur_hash ) {
			$org_hash = $cur_hash;
		}
		else {
			$dup_messages[] = $row[PR_ENTRYID];
			$dup_count++;
			echo " [i] For {$org_hash} adding DUP $md5_eid to delete list\n"; 
		}
        }
    }

    // final cleanup
    $dup_count = count( $dup_messages );
    if( $dup_count ) {
	$total_deleted += $dup_count;
        echo " [i] Finally deleting $dup_count duplicates. \n";
	delete_messages( $folder, $dup_messages );
        $dup_messages = array();
	echo "Deleted $total_deleted messages so far.\n\n";
   }

}


/*
 *  END FUNCTIONS
 */



// done
?>