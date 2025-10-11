<?php
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
/**
 * changes suffix of an ldap ldif dumpfile
 *
 * @example    ~# slapcat | changesuffix > changed.ldif
 * @author    Cornelius Weiss <nelius@cwtech.de>
 * @copyright Copyright 2008 (c) CWTech (www.cwtech.de)
 * @license   http://www.gnu.org/licenses/agpl.html
 * @version   0.2
 */

/**
 * destination suffix
 */
$sock=new sockets();
$to=utf8_encode(base64_decode($sock->GET_INFO("ChangeLDAPSuffixTo")));
$from=utf8_encode(base64_decode($sock->GET_INFO("ChangeLDAPSuffixFrom")));
/**
 * line length of ldif file
 */
define("LDIF_LINELENGTH", 78);

/* ---------- script startes here ---------- */

while ($next = fgets(STDIN)) {
  // linebreak
  if(preg_match('/^ /', $next)) {
    $line = preg_replace('/\n/', '', $line) . substr($next, 1);
    continue;
  }

  // tranform dc of Top entry
  if (preg_match('/^dc: /', $line)) {
    preg_match('/dc=(.+),/', $to, $dc);
    $line = 'dc: ' . $dc[1] . "\n";
  }

  // base64 encoded dn
  elseif(preg_match('/^dn: /', $line)) {
  
    $line =preg_replace("/$from/", $to, $line);
  }
  
  if(preg_match("#$from#", $line)){
  	//fwrite(STDOUT, "** FOUND `$from` in $line ***\n");
  }
  

  // NOTE: not only the dn needs to be transformed, also fields 
  // like member, creator, ... needs must be handled
  $line = preg_replace("/$from/", $to, $line);

  // write to stdout
  fwrite(STDOUT, ldifwrap($line));
  
  $line = $next;
}

/**
 * wraps input string into mulitiple lines
 * for ldif files
 *
 * @param string _instr
 * @return string wrapped string
 */
function ldifwrap($_instr) 
{
  $strlen = strlen($_instr);
  if ($strlen <= LDIF_LINELENGTH) return $_instr;
  
  $out = substr($_instr, 0 , LDIF_LINELENGTH) . "\n"; 
  $i = LDIF_LINELENGTH;
  
  while (true) {
    if (($i + LDIF_LINELENGTH + 1) > $strlen) {
      $out .= ' ' . substr($_instr, $i);
      break;
    }
    $out .= ' ' . substr($_instr, $i, LDIF_LINELENGTH-1) . "\n";
    $i += LDIF_LINELENGTH - 1;
  }

  return $out;
}

