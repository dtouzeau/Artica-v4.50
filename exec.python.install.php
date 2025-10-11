<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
xstart();


function python_psycopg2(){
	if(is_file("/usr/lib/python2.7/dist-packages/psycopg2/_psycopg.so")){return true;}
	if(is_file("/usr/lib/pyshared/python2.7/psycopg2/_psycopg.so")){return true;}
}

function xstart(){
	$unix=new unix();
	$array=GetArray();
	$TESTPOS=true;
	
	if(!is_dir("/usr/local/lib/python2.7")){@mkdir("/usr/local/lib/python2.7",0755,true);}
	
	if(is_dir("/usr/local/lib/python2.7/dist-packages/psycopg2")){
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rf /usr/local/lib/python2.7/dist-packages/psycopg2");
	}
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Python: Checking Python package\n";
	
	while (list ($filename, $none) = each ($array) ){
		if(!is_file($filename)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Python: $filename no such file...\n";
			$TESTPOS=false;
			break;
		}
		
	}
	
	if($TESTPOS){
		echo "Starting......: ".date("H:i:s")." [INIT]: Python: Checking Python package [OK]\n";
		exit();
	}
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Python: installing Python package\n";
	system("/bin/tar xvf /usr/share/artica-postfix/bin/install/python.tar.gz -C /");
	
	if($unix->DEBIAN_VERSION()>7){
		echo "Starting......: ".date("H:i:s")." [INIT]: Python: installing Python package for debian 8\n";
		system("/bin/tar xvf /usr/share/artica-postfix/bin/install/python-debian8.tar.gz -C /");
	}
	
	
	
	
	reset($array);
	$ldconfig=$unix->find_program("ldconfig");
	system($ldconfig);

	foreach ($array as $filename=>$none){
		if(!is_file($filename)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Python(failed): \"$filename\" no such file...\n";
			$TESTPOS=false;
			break;
		}
	
	}
	

	
	if(!python_psycopg2()){$unix->DEBIAN_INSTALL_PACKAGE("python-psycopg2");}
	if(!is_file("/usr/lib/python2.7/dist-packages/_ldap.so")){$unix->DEBIAN_INSTALL_PACKAGE("python-ldap");}
	if(!is_file("/usr/lib/pyshared/python2.7/pycurl.so")){$unix->DEBIAN_INSTALL_PACKAGE("python-pycurl");}
	if(!is_file("/usr/lib/python2.7/dist-packages/_mysql.so")){$unix->DEBIAN_INSTALL_PACKAGE("python-mysqldb");}
	if(!is_file("/usr/lib/python2.7/dist-packages/_ldap.so")){$unix->DEBIAN_INSTALL_PACKAGE("python-ldap");}
	
	
	$python=$unix->find_program("python");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$python /usr/share/artica-postfix/exec.testlibraries.py");

	if(!is_file("/usr/local/bin/tldextract")){
        shell_exec("$php ".ARTICA_ROOT."/exec.pdns.php --tldextract");
		shell_exec("/usr/local/bin/tldextract --update >/dev/null 2>&1 &");
	}
	
}



function GetArray(){
$f["/usr/local/lib/python2.7/dist-packages/tailer/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/mocks.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/util_test.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/charts_test.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/nameserver.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/base_ui.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/selectors_test.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/util.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/geoip.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/config.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/tk.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/nameserver_list.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/benchmark_test.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/version.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/addr_util.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/selectors.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/cli.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/benchmark.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/charts.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/reporter.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/conn_quality.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/site_connector.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/better_webbrowser.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/nameserver_test.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/health_checks.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/url_map.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/libnamebench/data_sources.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/six-1.10.0-py2.7.egg/six.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/line_chart.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/pie_chart.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/util.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/common.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/bar_chart.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/backends/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/backends/google_chart_api/util.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/backends/google_chart_api/encoders.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/backends/google_chart_api/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/graphy/formatters.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/simplejson/ordered_dict.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/simplejson/scanner.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/simplejson/decoder.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/simplejson/tool.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/simplejson/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/simplejson/encoder.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/set.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdataset.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/opcode.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rcode.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/query.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdatatype.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/tokenizer.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/tsig.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/e164.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/nsbase.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/sigbase.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/txtbase.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/dsbase.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/IPSECKEY.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/PX.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/NSAP.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/DHCID.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/NAPTR.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/AAAA.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/NSAP_PTR.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/A.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/KX.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/WKS.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/SRV.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/IN/APL.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/keybase.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/mxbase.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/TXT.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/PTR.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/SSHFP.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/RRSIG.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/NSEC3PARAM.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/NXT.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/SPF.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/ISDN.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/NS.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/AFSDB.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/NSEC3.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/KEY.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/DNAME.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/GPOS.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/RT.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/DLV.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/SIG.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/DNSKEY.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/RP.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/HIP.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/X25.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/DS.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/CNAME.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/CERT.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/HINFO.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/NSEC.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/MX.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/LOC.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdtypes/ANY/SOA.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/name.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/node.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/reversename.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/resolver.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/ttl.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/version.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/flags.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/message.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/edns.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/renderer.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/entropy.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/ipv4.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rrset.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/dnssec.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/namedict.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/inet.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/exception.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/update.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/tsigkeyring.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/ipv6.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdata.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/rdataclass.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/dns/zone.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/utils.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/defaults.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/sandbox.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/constants.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/lexer.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/ext.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/optimizer.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/parser.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/filters.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/visitor.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/runtime.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/_stringdefs.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/meta.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/_ipysupport.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/environment.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/nodes.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/tests.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/loaders.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/compiler.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/debug.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/exceptions.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/bccache.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/jinja2/pkg_resources.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/httplib2/iri2uri.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/nb_third_party/httplib2/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/lockfile-0.12.2-py2.7.egg/lockfile/linklockfile.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/lockfile-0.12.2-py2.7.egg/lockfile/mkdirlockfile.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/lockfile-0.12.2-py2.7.egg/lockfile/symlinklockfile.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/lockfile-0.12.2-py2.7.egg/lockfile/pidlockfile.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/lockfile-0.12.2-py2.7.egg/lockfile/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/lockfile-0.12.2-py2.7.egg/lockfile/sqlitelockfile.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/utils.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/certs.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/status_codes.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/compat.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/hooks.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/sessions.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/structures.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/cookies.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/auth.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/adapters.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/api.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/models.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/connectionpool.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/connection.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/request.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/filepost.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/contrib/appengine.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/contrib/socks.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/contrib/ntlmpool.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/contrib/pyopenssl.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/contrib/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/_collections.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/poolmanager.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/fields.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/util/connection.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/util/request.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/util/ssl_.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/util/retry.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/util/timeout.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/util/url.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/util/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/util/response.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/packages/ordered_dict.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/packages/six.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/packages/ssl_match_hostname/_implementation.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/packages/ssl_match_hostname/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/packages/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/exceptions.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/urllib3/response.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/jisfreq.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/langgreekmodel.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/jpcntx.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/constants.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/escsm.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/chardetect.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/charsetgroupprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/langhebrewmodel.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/compat.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/cp949prober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/euckrfreq.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/chardistribution.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/langhungarianmodel.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/big5prober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/hebrewprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/mbcharsetprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/gb2312freq.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/latin1prober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/euctwprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/big5freq.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/sbcsgroupprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/mbcsgroupprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/utf8prober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/escprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/sbcharsetprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/charsetprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/euckrprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/codingstatemachine.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/mbcssm.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/langcyrillicmodel.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/gb2312prober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/eucjpprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/langthaimodel.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/sjisprober.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/euctwfreq.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/langbulgarianmodel.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/chardet/universaldetector.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/packages/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/exceptions.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests-2.10.0-py2.7.egg/requests/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/requests_file-1.4-py2.7.egg/requests_file.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/python_daemon-2.1.1-py2.7.egg/daemon/daemon.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/python_daemon-2.1.1-py2.7.egg/daemon/_metadata.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/python_daemon-2.1.1-py2.7.egg/daemon/pidfile.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/python_daemon-2.1.1-py2.7.egg/daemon/runner.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/python_daemon-2.1.1-py2.7.egg/daemon/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rst2odt_prepstyles.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rstpep2html.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rst2xml.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rst2odt.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rst2pseudoxml.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rst2latex.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rst2xetex.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rst2s5.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rst2man.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/EGG-INFO/scripts/rst2html.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/core.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/s5_html/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/docutils_xml.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/pseudoxml.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/manpage.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/latex2e/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/odf_odt/pygmentsformatter.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/odf_odt/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/xetex/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/html4css1/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/pep_html/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/writers/null.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/frontend.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/examples.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/readers/pep.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/readers/standalone.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/readers/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/readers/doctree.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/_compat.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/nodes.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/statemachine.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/states.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/roles.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/he.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/zh_tw.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/en.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/eo.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/fr.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/zh_cn.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/af.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/fi.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/ca.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/da.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/es.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/ru.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/it.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/pl.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/de.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/pt_br.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/sk.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/nl.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/sv.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/lt.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/cs.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/ja.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/languages/gl.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/tableparser.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/directives/admonitions.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/directives/references.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/directives/misc.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/directives/body.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/directives/images.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/directives/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/directives/html.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/directives/parts.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/rst/directives/tables.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/parsers/null.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/he.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/zh_tw.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/en.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/eo.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/fr.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/zh_cn.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/af.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/fi.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/ca.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/da.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/es.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/ru.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/it.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/pl.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/de.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/pt_br.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/sk.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/nl.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/sv.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/lt.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/cs.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/ja.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/languages/gl.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/error_reporting.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/punctuation_chars.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/code_analyzer.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/urischemes.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/roman.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/math/unichar2tex.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/math/tex2unichar.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/math/latex2mathml.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/math/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/math/math2html.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/utils/smartquotes.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/io.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/transforms/universal.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/transforms/references.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/transforms/misc.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/transforms/peps.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/transforms/writer_aux.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/transforms/components.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/transforms/frontmatter.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/transforms/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/docutils-0.12-py2.7.egg/docutils/transforms/parts.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/validate_email-1.3-py2.7.egg/validate_email.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/idna-2.1-py2.7.egg/idna/compat.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/idna-2.1-py2.7.egg/idna/core.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/idna-2.1-py2.7.egg/idna/idnadata.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/idna-2.1-py2.7.egg/idna/uts46data.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/idna-2.1-py2.7.egg/idna/codec.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/idna-2.1-py2.7.egg/idna/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/idna-2.1-py2.7.egg/idna/intranges.py"]=true;
$f["/usr/lib/pyshared/python2.7/pycurl.so"]=true;
$f["/usr/share/pyshared/curl/__init__.py"]=true;
$f["/usr/share/pyshared/pycurl-7.19.0.egg-info"]=true;
$f["/usr/local/bin/virtualenv"]=true;
$f["/usr/local/bin/flask"]=true;
$f["/usr/local/lib/python2.7/dist-packages/phpserialize-1.3-py2.7.egg/phpserialize.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/psutil-4.1.0-py2.7-linux-x86_64.egg/psutil/_psutil_linux.so"]=true;
$f["/usr/local/lib/python2.7/dist-packages/pycron-0.40-py2.7.egg/pycron/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/dnspython-1.14.0-py2.7.egg/dns/__init__.py"]=true;
$f["/usr/lib/python2.7/dist-packages/_mysql.so"]=true;
$f["/usr/local/lib/python2.7/dist-packages/CherryPy-5.4.0.post_20160602-py2.7.egg/cherrypy/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/pyOpenSSL-16.0.0-py2.7.egg/OpenSSL/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/netaddr/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/python_dateutil-2.5.3-py2.7.egg/dateutil/__init__.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/pyOpenSSL-16.2.0-py2.7.egg/OpenSSL/crypto.py"]=true;
$f["/usr/local/lib/python2.7/dist-packages/PyWebDAV-0.9.8-py2.7.egg/pywebdav/__init__.pyc"]=true;
return $f;
}