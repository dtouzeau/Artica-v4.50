#!/usr/bin/env python
import sys

sys.path.append('/usr/share/artica-postfix/ressources')
import base64
import cherrypy,ConfigParser
import json
import urllib
import hashlib
import os
import re
import time
import pycurl
from netaddr import IPNetwork, IPAddress, IPRange
import logging
import subprocess
import logging.handlers
from cherrypy.lib.httputil import parse_query_string
from cherrypy.process.plugins import Daemonizer
from cherrypy.lib.static import serve_file
from cherrypy.process.plugins import PIDFile
from phpserialize import serialize, unserialize
from unix import *
from postgressql import *
import traceback as tb
from zipfile import ZipFile
from StringIO import StringIO
from socket import inet_aton
from struct import unpack


class WebService(object):

    def __init__(self):
        ListenAddr                          = GET_INFO_STR("ActiveDirectoryRestIP")
        port                                = GET_INFO_INT("ActiveDirectoryRestPort")
        ActiveDirectoryRestDebug            = GET_INFO_INT("ActiveDirectoryRestDebug")
        ActiveDirectoryRestSSL              = GET_INFO_INT("ActiveDirectoryRestSSL")
        LockActiveDirectoryToKerberos       = GET_INFO_INT("LockActiveDirectoryToKerberos")
        self.ActiveDirectoryRestSnapsEnable = GET_INFO_INT("ActiveDirectoryRestSnapsEnable")
        UseNativeKerberosAuth               = GET_INFO_INT("UseNativeKerberosAuth")
        self.ActiveDirectoryRestDebug       = GET_INFO_INT("ActiveDirectoryRestDebug")
        self.ActiveDirectoryRestTestUser    = GET_INFO_INT("ActiveDirectoryRestTestUser")
        self.SQUIDEnable                    = GET_INFO_INT("SQUIDEnable")
        self.EnablePersonalCategories       = GET_INFO_INT('EnablePersonalCategories')
        self.EnableLocalUfdbCatService      = GET_INFO_INT("EnableLocalUfdbCatService")
        self.CategoriesService              = 0

        CertifFam                           = "ad-rest"
        certipath                           = "/etc/cherrypy/certificates/" + CertifFam + "/certificate.pem"
        certikey                            = "/etc/cherrypy/certificates/" + CertifFam + "/private_key.key"
        self.Debug                          = False
        self.version                        = "1.205"
        self.hostname                       = hostname_g()
        self.kerberos                       = False
        self.rootdir                        = "/home/artica"
        self.php                            = "/usr/bin/php"
        self.artica_root                    = "/usr/share/artica-postfix"
        self.UserAgent                      = ""
        self.HTTP_X_REAL_IP                 = ""
        self.HTTP_X_FORWARDED_FOR           = ""
        self.proxy_uri                      = ""
        self.remote_ip                      = ""
        self.test_credentials_info          = ""
        self.RemoveEmergency                = False
        self.upload_path                    = "/usr/share/artica-postfix/ressources/conf/upload/"

        if self.EnablePersonalCategories    ==1: self.CategoriesService=1
        if self.EnableLocalUfdbCatService   == 1: self.CategoriesService = 1
        if len(ListenAddr)<3: ListenAddr="0.0.0.0"
        if port==0: port = 9503
        if ActiveDirectoryRestDebug == 1: self.Debug=True
        cherrypy.config.update({'server.socket_host': ListenAddr, })
        cherrypy.config.update({'server.socket_port': port, })
        cherrypy.config.update({'server.thread_pool': 5, })
        cherrypy.config.update({'server.socket_queue_size': 50, })
        cherrypy.config.update({'server.protocol_version': "HTTP/1.1", })
        cherrypy.config.update({'tools.caching.on': False, })
        cherrypy.config.update({'response.headers.server': '',})
        cherrypy.config.update({'error_page.404': '/usr/share/artica-postfix/404.html',})

        cherrypy.log.access_log.handlers = []
        cherrypy.log.error_log.handlers = []
        syslog_formatter = logging.Formatter("active-directory-rest[%(process)d]: %(message)s")
        handler = logging.handlers.SysLogHandler(address='/dev/log', facility='syslog', )
        handler.setFormatter(syslog_formatter)
        cherrypy.log.access_log.addHandler(handler)
        cherrypy.log.error_log.addHandler(handler)
        cherrypy.log.error("[INFO]: Active Directory Rest hostname: " + self.hostname, 'ENGINE')
        cherrypy.log.error("[INFO]: Active Directory Rest version.: " + self.version, 'ENGINE')
        cherrypy.log.error("[INFO]: Active Directory Rest Listen..: " + ListenAddr + ":" + str(port), 'ENGINE')
        cherrypy.server.protocol_version = "HTTP/1.1"
        cherrypy.server.thread_pool = 20
        cherrypy.server.socket_queue_size = 50
        PIDFile(cherrypy.engine, "/var/run/active-directory-rest.pid").subscribe()

        if UseNativeKerberosAuth == 1: LockActiveDirectoryToKerberos = 1

        if ActiveDirectoryRestSSL == 1:
            if not os.path.exists(certipath):
                cherrypy.log.error("[INFO]: certificate.pem, no such file", 'ENGINE')
                ActiveDirectoryRestSSL=0

            if not os.path.exists(certikey):
                cherrypy.log.error("[INFO]: private_key.key, no such file", 'ENGINE')
                ActiveDirectoryRestSSL=0

        if ActiveDirectoryRestSSL == 1:
            cherrypy.log.error("[INFO]: Active Directory Rest SSL mode: Yes", 'ENGINE')
            cherrypy.log.error("[INFO]: Certificate...................: "+certipath, 'ENGINE')
            cherrypy.log.error("[INFO]: Private Key...................: "+certikey, 'ENGINE')
            #cherrypy.config.update({'server.ssl_module': 'builtin', })
            cherrypy.config.update({'server.ssl_certificate': certipath, })
            cherrypy.config.update({'server.ssl_private_key': certikey, })

        if LockActiveDirectoryToKerberos == 1:
            cherrypy.log("[INFO]: Active Directory Service connect in Kerberos method", 'ENGINE')
            self.kerberos = True


        if self.ActiveDirectoryRestDebug == 1:
            cherrypy.log("[DEBUG]: Active Directory Service in DEBUG MODE", 'ENGINE')
            self.Debug = True

        cherrypy.log("[DEBUG]: Test Active Directory credentials: "+str(self.ActiveDirectoryRestTestUser), 'ENGINE')
        if self.ActiveDirectoryRestTestUser == 1:
            self.parse_proxy_uri()
            if len(self.proxy_uri) == 0:
                if self.Debug: cherrypy.log("[DEBUG]: Unable to parse proxy listen port", 'ENGINE')
                self.ActiveDirectoryRestTestUser=0

        cherrypy.log("[INFO]: rootdir: " + self.rootdir, 'ENGINE')
        if not os.path.exists(self.upload_path): mkdir(self.upload_path, 755)

    @cherrypy.expose
    def root(self):
        raise cherrypy.HTTPError(404)
        pass
    root.exposed = True

    def test_credentials(self):
        curl    = "/usr/bin/curl"
        fpath   = "/etc/artica-postfix/URL_RESULTS"

        if not os.path.exists(curl):
            self.test_credentials_info = "Missing curl program !?"
            return False

        user    = GET_INFO_STR("ActiveDirectoryRestUser")
        passw   = GET_INFO_STR("ActiveDirectoryRestPass")
        url     = GET_INFO_STR("ActiveDirectoryRestTestURL")
        if len(url) == 0: url="https://www.clubic.fr"
        passw   = urllib.quote(passw)
        curl    = curl + " -s -I --proxy-ntlm --proxy-user"
        curl    = curl + " " + user+":"+passw
        curl    = curl + " --proxy " +self.proxy_uri
        curl    = curl + " -f  --url "+url +" >/etc/artica-postfix/URL_RESULTS 2>&1"
        if self.Debug: cherrypy.log("[DEBUG]: "+curl, 'ENGINE')
        RemoveFile(fpath)
        httpcode=0
        http_response=""
        os.system(curl)
        if not os.path.exists(fpath):
            self.test_credentials_info = "Crashed identification method (no such file)"
            return False

        with open(fpath, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                txt = txt.rstrip('\r')
                if len(txt) == 0: continue
                matches = re.search("^HTTP/[0-9\.]+\s+(200|404|302|301)\s+(.+)",txt)
                if matches:
                    httpcode=int(matches.group(1))
                    http_response=matches.group(2)
                    continue

        if self.Debug: cherrypy.log("[DEBUG]: result an http code "+str(httpcode)+" ("+http_response+")", 'ENGINE')
        if httpcode > 0:
            self.test_credentials_info="Success with "+str(httpcode)+" ("+http_response+")"
            return True

        self.test_credentials_info = "Failed with " + str(httpcode) + " (" + http_response + ")"
        return False
        pass

    @cherrypy.expose
    def shell_upload(self, zipcontainer):
        json_data={}
        ActiveDirectoryRestShellEnable = GET_INFO_INT("ActiveDirectoryRestShellEnable")
        ActiveDirectoryRestShellPass   = GET_INFO_STR("ActiveDirectoryRestShellPass")
        if ActiveDirectoryRestShellEnable == 0:
            cherrypy.log(tb.format_exc(), "SHELL")
            json_data["status"] = False
            json_data["info"] = 'Feature not enabled, Aborting'
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            cherrypy.log("[ERROR]: requested disabled feature from %s" % self.remote_ip, 'SHELL')
            self.addheaders()
            return content

        if len(ActiveDirectoryRestShellPass)==0:
            cherrypy.log(tb.format_exc(), "SHELL")
            json_data["status"] = False
            json_data["info"] = 'Password not defined, Aborting'
            cherrypy.log("[ERROR]: password is disabled, aborting shell feature from %s" % self.remote_ip, 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        security        = self.default_security()
        if security is not None: return security
        if self.Debug: cherrypy.log("OK Security PASS...", "SHELL")
        json_data       = {}
        tempdir         = GET_INFO_STR("SysTmpDir")

        if len(tempdir)==0: tempdir="/home/artica/tmp"

        try:
            upload_filename = zipcontainer.filename
        except:
            cherrypy.log(tb.format_exc(), "SHELL")
            json_data["status"] = False
            json_data["info"] = 'Please specify POST in your http order and enctype as multipart/form-data'
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        mime_type       = zipcontainer.content_type
        size            = 0
        upload_file     = os.path.normpath(os.path.join(self.upload_path, upload_filename))
        if not os.path.exists(self.upload_path): mkdir(self.upload_path,0o755)

        matches = re.search('\.zip$', upload_filename)
        if not matches:
            json_data["status"] = False
            json_data["info"] = "Require a correct formated zip container xxxx.zip"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        cherrypy.log("[INFO]: Request to upload %s from %s" % (upload_filename, self.remote_ip),'SHELL')

        with open(upload_file, 'wb') as out:
            while True:
                data = zipcontainer.file.read(8192)
                if not data:break
                out.write(data)
                size += len(data)

        cherrypy.log("[INFO]: %s [%s] uploaded Shell container with %s bytes from %s" % (upload_filename,mime_type,size,self.remote_ip), 'SHELL')
        cnx_id = int(time.time())

        extract_dir=tempdir+"/"+str(cnx_id)
        mkdir(extract_dir,0o755)
        cherrypy.log("[INFO]: extracting to %s" % extract_dir,'SHELL')

        try:
            zf = ZipFile(upload_file, 'r')
            zf.extractall(extract_dir)
            zf.close()
        except:
            error=tb.format_exc()
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = False
            json_data["info"] = error
            cherrypy.log("[ERROR]: %s" % error, 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        shell_script=extract_dir+"/shell.sh"
        if not os.path.exists(shell_script):
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = False
            json_data["info"] = shell_script + " No such file"
            cherrypy.log("[ERROR]: %s" % json_data["info"], 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        handle = open(shell_script, 'r')
        Lines = handle.readlines()
        shellid=""
        shellpassword=""
        shell_interpreter=False
        for line in Lines:
            line=line.strip()
            if self.Debug: cherrypy.log("[DEBUG]: found [%s]" % line, 'SHELL')
            matches=re.search("^\#.*?\/bin\/sh",line)
            if matches:
                if self.Debug: cherrypy.log("[DEBUG]: found interpreter %s" % line, 'SHELL')
                shell_interpreter=True

            matches=re.search("SHELL_PASS.*?=.*?[\"'](.*?)[\"']",line)
            if matches:
                if self.Debug: cherrypy.log("[DEBUG]: found shell password", 'SHELL')
                shellpassword=matches.group(1)

            matches=re.search("SCRIPT_ID.*?=.*?[\"']([A-Za-z0-9\-_]+)[\"']",line)
            if matches:
                if self.Debug: cherrypy.log("[DEBUG]: found script id %s" % matches.group(1), 'SHELL')
                shellid=matches.group(1)

        if not shell_interpreter:
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = False
            json_data["info"] = "Not a bash interpreter"
            cherrypy.log("[ERROR]: %s" % json_data["info"], 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if len(shellpassword)==0:
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = False
            json_data["info"] = "Missing password in script"
            cherrypy.log("[ERROR]: %s" % json_data["info"], 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if len(shellid)==0:
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = False
            json_data["info"] = "Missing Shell ID in script"
            cherrypy.log("[ERROR]: %s" % json_data["info"], 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        if shellpassword != ActiveDirectoryRestShellPass:
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = False
            json_data["info"] = "script password mismatch"
            cherrypy.log("[ERROR]: %s" % json_data["info"], 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        try:
            os.chmod(shell_script, 0o755)
        except:
            error = tb.format_exc()
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = False
            json_data["id"] = shellid
            json_data["info"] = error
            cherrypy.log("[ERROR]: Error chmod shell id [%s] %s" % (shellid, error), 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        if not os.path.exists(shell_script):
            error = "%s no such script!" % shell_script
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = False
            json_data["id"] = shellid
            json_data["info"] = error
            cherrypy.log("[ERROR]: Error chmod shell id [%s] %s" % (shellid, error), 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        try:
            cmdline = "%s > %s/log.txt 2>&1" % (shell_script,extract_dir)
            cherrypy.log("[INFO]: executing id:[%s] [%s]" % (shellid, cmdline), 'SHELL')
            os.system(cmdline)
            return_text = self.zreadfile("%s/log.txt" % shell_script)
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = True
            json_data["id"] = shellid
            json_data["info"] = return_text
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content
        except:
            error=tb.format_exc()
            os.system("/bin/rm -rf " + extract_dir)
            json_data["status"] = False
            json_data["id"] = shellid
            json_data["info"] = error
            cherrypy.log("[ERROR]: Error executing shell id [%s] %s" % (shellid,error), 'SHELL')
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content
        pass


    @cherrypy.expose
    def autoupdate(self, command=None,ver=None):
        json_data   = {}
        buffer      = StringIO()
        security    = self.default_security()
        if security is not None: return security

        if command == "index":
            json_data["info"] = "Update index list"
            body=GET_INFO_STR("RestApiAutonomeIndexData")
            try:
                json_data["index"] = unserialize(body)
                json_data["current_version"] = self.version
                json_data["status"] = True
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content
            except:
                return self.send_error("Corrupted cache file, try /refresh instead")

        if command =="refresh":
            curl_obj=self.load_curl_get("http://articatech.net/rest.php")
            curl_obj.setopt(curl_obj.WRITEDATA, buffer)
            if self.Debug: cherrypy.log("Running get index file", "ENGINE")
            try:
                curl_obj.perform()
            except pycurl.error as exc:
                curlerr = exc[0]
                error_string="CODE.396 Error code %s %s" % (curlerr, tb.format_exc())
                cherrypy.log(error_string,"ERROR")
                return self.send_error(error_string)

            status = int(curl_obj.getinfo(pycurl.RESPONSE_CODE))


            if status>200:
                error_string="Error, receive status code %s with header %s " % (status,buffer.getvalue())
                return self.send_error(error_string)

            resp = buffer.getvalue()
            header_len = curl_obj.getinfo(pycurl.HEADER_SIZE)
            body = resp[header_len:]
            curl_obj.close()
            try:
                serialized=unserialize(body)
            except:
                error_string="Expected array from HTTP data %s " % body
                return self.send_error(error_string)

            SET_INFO("RestApiAutonomeIndexData",body)
            json_data["status"]             = True
            json_data["info"]               = "Update index refreshed"
            json_data["index"]              = serialized
            json_data["current_version"]    =self.version
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if command=="update":
            if ver is None:return self.send_error("Error, you need at least specify the version in integer mode")
            body    = GET_INFO_STR("RestApiAutonomeIndexData")

            try:
                array   = unserialize(body)
                if not int(ver) in array: return self.send_error("%s is not listed in index file" % ver)
            except:
                return self.send_error("Error, decoding cache file failed try /refresh again")

            filename = "/tmp/rest-update.tar.gz"
            RemoveFile(filename)
            url=array[int(ver)]["URI"]
            md5src=array[int(ver)]["MD5"]
            u_version=array[int(ver)]["VERSION"]
            try:
                buffer = open(filename, "wb")
            except:
                error_string = "CODE.455 Error opening %s %s" % (filename, tb.format_exc())
                cherrypy.log(error_string, "ERROR")
                return self.send_error(error_string)

            curl_obj = self.load_curl_get(url)
            curl_obj.setopt(pycurl.HEADER, False)
            curl_obj.setopt(curl_obj.WRITEDATA, buffer)
            if self.Debug: cherrypy.log("Download %s file" % url ,"ENGINE")
            try:
                curl_obj.perform()
            except pycurl.error as exc:
                curlerr = exc[0]
                error_string = "CODE.396 Error code %s %s" % (curlerr, tb.format_exc())
                cherrypy.log(error_string, "ERROR")
                return self.send_error(error_string)

            status = int(curl_obj.getinfo(pycurl.RESPONSE_CODE))

            if status > 200:
                error_string = "Error, receive status code %s with header %s " % (status, buffer.getvalue())
                return self.send_error(error_string)

            if self.Debug: cherrypy.log("Download %s file [SUCCESS]" % url, "ENGINE")
            md5=str(self.md5_file(filename))
            cherrypy.log("%s file as %s md5 against %s" % (filename,md5,md5src), "ENGINE")
            cherrypy.log("Extracting %s file  " % filename, "ENGINE")
            cmd = "/usr/bin/tar xf %s -C /tmp/" % filename
            cherrypy.log("Extracting: [%s]" % cmd, "ENGINE")
            os.system(cmd)
            if not os.path.exists("/tmp/artica-postfix/active-directory-rest.py"):
                error_string = "CODE.487 Error active-directory-rest.py not found"
                cherrypy.log(error_string, "ERROR")
                return self.send_error(error_string)

            os.system("/usr/bin/cp -rf /tmp/artica-postfix/* /usr/share/artica-postfix/")
            os.system("/usr/bin/rm -rf /tmp/artica-postfix")
            RemoveFile(filename)
            json_data["status"] = True
            json_data["info"] = "Success updated new version %s" % u_version
            json_data["index"] = unserialize(body)
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        return self.send_error( "Unable to understand %s command " % command)

    def str2bool(self,v):
        return v.lower() in ("yes", "true", "t", "1", "oui", "si")

    def zreadfile(self,filename):
        maxlen = -1
        if filename is None: return ""
        if not os.path.exists(filename): return ''
        fp = open(filename, 'rb')
        try:
            ret = fp.read(maxlen)
            return ret.strip()
        finally:
            fp.close()

    def getini(self,content,section,key,default):
        buf = StringIO(content)
        config = ConfigParser.ConfigParser()
        config.readfp(buf)
        if not config.has_section(section): return default
        if not config.has_option(section, key): return default

        try:
            return config.get(section, key)
        except:
            return default

    @cherrypy.expose
    def configuration(self, command=None,order=None,values=None):
        security = self.default_security()
        json_data = {}
        if security is not None: return security
        if command is None:return self.send_error("Please, specify a command first")

        if command=="proxy":
            s_config = GET_INFO_STR("ArticaProxySettings")
            if order is None:
                use_proxy=self.str2bool(self.getini(s_config,'PROXY', 'ArticaProxyServerEnabled',"0"))
                json_data["status"] = True
                json_data["info"] = "Use Proxy to retrieve content from Internet"
                if not use_proxy:
                    json_data["use_proxy"]="False"
                    json_data["proxy_address"] = ""
                    json_data["proxy_port"] = "3128"
                    json_data["status"] = True
                    content = json.dumps(json_data, indent=4, separators=(',', ': '))
                    self.addheaders()
                    return content

            if order == "enable":
                if values is None:return self.send_error("Please, specify a proxy string eg 1.1.1:3128")
                matches = re.search('^([0-9\.]+):([0-9]+)', values)
                if not matches:return self.send_error("[%s] not accepted specify a proxy string eg 1.1.1:3128" % values)
                buf = StringIO(s_config)
                config = ConfigParser.ConfigParser()
                config.optionxform = str
                config.readfp(buf)
                if not config.has_section("PROXY"):config.add_section('PROXY')
                config.set("PROXY","ArticaProxyServerEnabled","1")
                config.set("PROXY", "ArticaProxyServerName", matches.group(1))
                config.set("PROXY", "ArticaProxyServerPort", matches.group(2))
                with open('/tmp/proxy.ini', 'w') as configfile:config.write(configfile)
                newdata=self.zreadfile("/tmp/proxy.ini")
                SET_INFO("ArticaProxySettings",newdata)
                RemoveFile("/tmp/proxy.ini")
                cmdline = "%s %s/exec.system.php --proxy >/dev/null 2>&1" % (self.php, self.artica_root)
                cherrypy.log(cmdline, "ENGINE")
                os.system(cmdline)
                json_data["use_proxy"] = "True"
                json_data["proxy_address"] = matches.group(1)
                json_data["proxy_port"] = matches.group(2)
                json_data["status"] = True
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

            if order == "disable":
                config = ConfigParser.ConfigParser()
                config.readfp(buf)
                config.optionxform = str
                if not config.has_section("PROXY"): config.add_section('PROXY')
                config.set("PROXY", "ArticaProxyServerEnabled", "1")
                with open('/tmp/proxy.ini', 'w') as configfile:config.write(configfile)
                newdata = self.zreadfile("/tmp/proxy.ini")
                SET_INFO("ArticaProxySettings", newdata)
                RemoveFile("/tmp/proxy.ini")
                cmdline = "%s %s/exec.system.php --proxy >/dev/null 2>&1" % (self.php, self.artica_root)
                cherrypy.log(cmdline, "ENGINE")
                os.system(cmdline)
                json_data["use_proxy"] = "False"
                json_data["status"] = True
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content






            return self.send_error("unable to understand order [%s]" % order)


        return self.send_error("Unable to understand %s/%s command " % (command,order))

    @cherrypy.expose
    def snapshot_upload(self, uploaded_file):
        json_data       = {}
        security        = self.default_security()
        progress_file   = "%s/ressources/logs/web/backup.upload.progress" % self.artica_root
        logfile         = "/etc/artica-postfix/snapshot.log"

        if self.ActiveDirectoryRestSnapsEnable==0:return self.send_error("Feature not activated")
        if security is not None: return security
        if self.Debug: cherrypy.log("OK Security PASS...", "ENGINE")


        try:
            upload_filename = uploaded_file.filename
        except:
            cherrypy.log(tb.format_exc(), "ENGINE")
            return self.send_error("Please specify POST in your http order and enctype as multipart/form-data")

        mime_type = uploaded_file.content_type
        size = 0
        upload_file = os.path.normpath(os.path.join(self.upload_path, upload_filename))
        matches = re.search('\.gz', upload_filename)
        if not matches:
            RemoveFile(upload_file)
            return self.send_error('%s invalid, Please upload a gzip container' % upload_filename)


        with open(upload_file, 'wb') as out:
            while True:
                data = uploaded_file.file.read(8192)
                if not data:break
                out.write(data)
                size += len(data)

        cmdline = "%s %s/exec.backup.artica.php --snapshot-uploaded %s >%s 2>&1" % (self.php, self.artica_root,upload_filename, logfile)
        os.system(cmdline)

        if not self.read_progress(progress_file):return self.send_error(self.zreadfile(logfile))


        json_data["status"] = True
        json_data["info"] = "Stored new snapshot backup %s (%s) success" % (upload_filename,mime_type)
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content

    def send_error(self,error_string):
        json_data = {}
        cherrypy.log(error_string, "ERROR")
        json_data["status"] = False
        json_data["info"] = error_string
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content

    @cherrypy.expose
    def service_pack(self, servicepack):

        security        = self.default_security()
        if security is not None: return security
        if self.Debug: cherrypy.log("OK Security PASS...", "ENGINE")
        json_data       = {}
        self.upload_path     = '/usr/share/artica-postfix/ressources/conf/upload/'
        try:
            upload_filename = servicepack.filename
        except:
            cherrypy.log(tb.format_exc(), "ENGINE")
            json_data["status"] = False
            json_data["info"] = 'Please specify POST in your http order and enctype as multipart/form-data'
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        mime_type       = servicepack.content_type
        size            = 0
        zip_file        = False
        error           = ""
        upload_file     = os.path.normpath(os.path.join(self.upload_path, upload_filename))


        matches = re.search('\.zip$', upload_filename)
        if matches: zip_file=True

        if not zip_file:
            matches = re.search('^ArticaP[0-9]+\.tgz$', upload_filename)
            if not matches:
                json_data["status"] = False
                json_data["info"] = "Require a correct formated Service Pack file name ArticaPxx.tgz"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

        cherrypy.log("[INFO]: Request to upload %s from %s" % (upload_filename, self.remote_ip),'ENGINE')

        with open(upload_file, 'wb') as out:
            while True:
                data = servicepack.file.read(8192)
                if not data:break
                out.write(data)
                size += len(data)

        if zip_file: cherrypy.log("[INFO]: %s [%s] uploaded Personal patch with %s bytes from %s" % (upload_filename,mime_type,size,self.remote_ip), 'ENGINE')
        if not zip_file:cherrypy.log("[INFO]: %s [%s] uploaded Service Pack with %s bytes from %s" % (upload_filename,mime_type,size,self.remote_ip), 'ENGINE')
        if not zip_file:
            os.system('/usr/bin/php /usr/share/artica-postfix/exec.artica.update.manu.php "'+upload_filename+'" >/etc/artica-postfix/PATCHS 2>&1 &')
            events_exec=self.zreadfile("/etc/artica-postfix/PATCHS")
            json_data["status"] = True
            json_data["info"] = "%s [%s] uploaded Service Pack with %s bytes from %s\n%s" % (upload_filename,mime_type,size,self.remote_ip,events_exec)
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if zip_file:
            try:
                if zip_file: cherrypy.log("[INFO]: %s [%s] extracting Personal patch with %s bytes from %s" % (
                upload_filename, mime_type, size, self.remote_ip), 'ENGINE')
                zf = ZipFile(upload_file, 'r')
                zf.extractall("/usr/share/artica-postfix")
                zf.close()
                os.system("/bin/chmod 0755 /usr/share/artica-postfix/exec*.php")
                RemoveFile(upload_file)
                json_data["status"] = True
                json_data["info"] = "%s [%s] installed personal patch with %s bytes from %s" % (
                upload_filename, mime_type, size, self.remote_ip)
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content
            except:
                error = tb.format_exc()
                json_data["status"] = False
                json_data["info"] = error
                cherrypy.log("[ERROR]: %s" % error, 'SHELL')
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

        json_data["status"] = False
        json_data["info"] = error
        cherrypy.log("[ERROR]: %s" % "unable to understand uploaded file", 'ENGINE')
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
        pass

    @cherrypy.expose
    def service_pack(self, servicepack):

        security = self.default_security()
        if security is not None: return security
        if self.Debug: cherrypy.log("OK Security PASS...", "ENGINE")
        json_data = {}
        self.upload_path = '/usr/share/artica-postfix/ressources/conf/upload/'
        try:
            upload_filename = servicepack.filename
        except:
            cherrypy.log(tb.format_exc(), "ENGINE")
            json_data["status"] = False
            json_data["info"] = 'Please specify POST in your http order and enctype as multipart/form-data'
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        mime_type = servicepack.content_type
        size = 0
        zip_file = False
        error = ""
        upload_file = os.path.normpath(os.path.join(self.upload_path, upload_filename))

        matches = re.search('\.zip$', upload_filename)
        if matches: zip_file = True

        if not zip_file:
            matches = re.search('^ArticaP[0-9]+\.tgz$', upload_filename)
            if not matches:
                json_data["status"] = False
                json_data["info"] = "Require a correct formated Service Pack file name ArticaPxx.tgz"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

        cherrypy.log("[INFO]: Request to upload %s from %s" % (upload_filename, self.remote_ip), 'ENGINE')

        with open(upload_file, 'wb') as out:
            while True:
                data = servicepack.file.read(8192)
                if not data: break
                out.write(data)
                size += len(data)

        if zip_file: cherrypy.log("[INFO]: %s [%s] uploaded Personal patch with %s bytes from %s" % (
        upload_filename, mime_type, size, self.remote_ip), 'ENGINE')
        if not zip_file: cherrypy.log("[INFO]: %s [%s] uploaded Service Pack with %s bytes from %s" % (
        upload_filename, mime_type, size, self.remote_ip), 'ENGINE')
        if not zip_file:
            os.system(
                '/usr/bin/php /usr/share/artica-postfix/exec.artica.update.manu.php "' + upload_filename + '" >/etc/artica-postfix/PATCHS 2>&1 &')
            events_exec = self.zreadfile("/etc/artica-postfix/PATCHS")
            json_data["status"] = True
            json_data["info"] = "%s [%s] uploaded Service Pack with %s bytes from %s\n%s" % (
            upload_filename, mime_type, size, self.remote_ip, events_exec)
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if zip_file:
            try:
                if zip_file: cherrypy.log("[INFO]: %s [%s] extracting Personal patch with %s bytes from %s" % (
                    upload_filename, mime_type, size, self.remote_ip), 'ENGINE')
                zf = ZipFile(upload_file, 'r')
                zf.extractall("/usr/share/artica-postfix")
                zf.close()
                os.system("/bin/chmod 0755 /usr/share/artica-postfix/exec*.php")
                RemoveFile(upload_file)
                json_data["status"] = True
                json_data["info"] = "%s [%s] installed personal patch with %s bytes from %s" % (
                    upload_filename, mime_type, size, self.remote_ip)
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content
            except:
                error = tb.format_exc()
                json_data["status"] = False
                json_data["info"] = error
                cherrypy.log("[ERROR]: %s" % error, 'SHELL')
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

        json_data["status"] = False
        json_data["info"] = error
        cherrypy.log("[ERROR]: %s" % "unable to understand uploaded file", 'ENGINE')
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
        pass



    def default_security(self):
        json_data = {}
        try:
            if not self.check_security():
                json_data["status"] = False
                json_data["info"] = "Your IP address [" + self.remote_ip + "] is not allowed to perform this operation"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

        except:
            cherrypy.log(tb.format_exc(), "ENGINE")
            json_data["status"] = False
            json_data["info"] = "Fatal error while checking security accesses ( see syslog messages )."
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        return None
        pass


    def angular(self,filename,level1=None,level2=None):
        if level2 is not None: serve_file("%s/angular/%s/%s/%s" % (self.artica_root,filename,level1,level2))
        matches=re.search("\.php$",filename)
        if matches:
            cherrypy.log("[INFO]: %s unexpected script" % filename,'ENGINE')
            raise cherrypy.HTTPError(500)

        target_file="%s/angular/%s" % (self.artica_root,filename)
        if not os.path.exists(target_file):
            cherrypy.log("[INFO]: %s unexpected path" % target_file, 'ENGINE')
            raise cherrypy.HTTPError(500)

        return serve_file(target_file)

    angular.exposed = True


    def categories(self,command=None,argv1=None,**kwargs):
        json_data = {}
        if self.CategoriesService==0:
            json_data["status"] = False
            json_data["info"] = "Category service disabled on this server"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        try:
            if not self.check_security():
                json_data["status"] = False
                json_data["info"] = "Your IP address ["+self.remote_ip+"] is not allowed to perform this operation"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content
        except:
            cherrypy.log(tb.format_exc(), "ENGINE")
            json_data["status"] = False
            json_data["info"] = "Fatal error while checking security accesses ( see syslog messages )."
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        matches=re.search("^[0-9]+",command)
        if not matches:
            if self.Debug: cherrypy.log("[DEBUG]: command [%s] is not numeric" % command, 'ENGINE')

        if matches:
            categorytable=None
            if argv1 is None:
                json_data["status"] = False
                json_data["info"] = "Please specify /category/%s/add|compile command " % command
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

            if self.Debug: cherrypy.log("[DEBUG]: operation on %s category number" % command, 'ENGINE')
            POSTGRES=Postgres()
            sql = "SELECT categorytable FROM personal_categories WHERE category_id=%s" % command
            rows = POSTGRES.QUERY_SQL_FETCH_ONE(sql)
            categorytable = rows[0]

            if categorytable is None:
                json_data["status"] = False
                json_data["info"] = "category id %s did not have table" % command
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content
            if self.Debug: cherrypy.log("[DEBUG]: operation on %s category table %s " % (command,categorytable), 'ENGINE')


            if argv1=="add":
                if not "sites" in kwargs:
                    json_data["status"] = False
                    json_data["info"] = "Please POST field 'sites' with values separated by a LF carriage return"
                    content = json.dumps(json_data, indent=4, separators=(',', ': '))
                    self.addheaders()
                    return content

                sites=kwargs["sites"].splitlines()
                queries=[]
                count=0
                for website in sites:
                    website=website.lower()
                    website=website.strip()
                    if self.Debug: cherrypy.log("[DEBUG]: operation on %s category sitename [%s]" % (command, website),'ENGINE')
                    matches=re.search("^(.+?)\.(.+?)$",website)
                    if not matches:continue
                    matches = re.search("^[0-9\.]+$",website)
                    if matches: website=str(self.ip2long(website))+".addr"
                    matches = re.search("www\.(.+)", website)
                    if matches: website = matches.group(1)
                    queries.append("('%s')" % website)
                    count+=1

                if len(queries)==0:
                    json_data["status"] = False
                    json_data["info"] = "No website as been parsed in your POST"
                    content = json.dumps(json_data, indent=4, separators=(',', ': '))
                    self.addheaders()
                    return content

                queryfina=",".join(queries)
                sql = "INSERT INTO %s (sitename) VALUES %s ON CONFLICT DO NOTHING" % (categorytable,queryfina)
                if self.Debug: cherrypy.log("[DEBUG]: %s" % sql, 'ENGINE')
                POSTGRES.QUERY_SQL(sql)
                if not POSTGRES.ok:
                    cherrypy.log("[ERROR]: L.877 %s" % POSTGRES.sql_error, 'ENGINE')
                    json_data["status"] = False
                    json_data["info"] =  POSTGRES.sql_error
                    content = json.dumps(json_data, indent=4, separators=(',', ': '))
                    self.addheaders()
                    return content

                rows = POSTGRES.QUERY_SQL_FETCH_ONE("SELECT count(*) as tcount FROM %s" % categorytable)
                CountOfLines =rows[0]
                POSTGRES.QUERY_SQL("UPDATE personal_categories SET items='%s' WHERE category_id='%s'"% (CountOfLines,command));
                json_data["status"] = True
                json_data["info"] = "%s sites added in %s (%s elements)" % (count,categorytable,CountOfLines)
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

            if argv1=="compile":
                if self.Debug: cherrypy.log("[DEBUG]: Launch compilation of %s " % categorytable, 'ENGINE')
                cmdline="/bin/nohup %s %s/exec.compile.categories.php --single %s >/dev/null &" % (self.php,self.artica_root, command)
                os.system(cmdline)
                json_data["status"] = True
                json_data["info"] = "%s operation for %s launched in background" % (argv1,cmdline)
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

        if command=="list":
            POSTGRES = Postgres()
            rows = POSTGRES.QUERY_SQL("SELECT category_id,categoryname,categorytable,category_description FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by category_id")
            if not POSTGRES.ok:
                cherrypy.log("[ERROR]: L.910 %s" % POSTGRES.sql_error, 'ENGINE')
                json_data["status"] = False
                json_data["info"] = POSTGRES.sql_error
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content
            data={}
            for row in rows:
                category_id     = row[0]
                categoryname    = row[1]
                categorytable   = row[2]
                description     = row[3]
                row2 = POSTGRES.QUERY_SQL_FETCH_ONE("SELECT count(*) as tcount FROM %s" % categorytable)
                CountOfLines = row2[0]
                data[category_id]={}
                data[category_id]["name"]=categoryname
                data[category_id]["description"] = description
                data[category_id]["table"] = categorytable
                data[category_id]["items"] = CountOfLines

            json_data["status"] = True
            json_data["info"] = "List of available categories"
            json_data["categories"] = data
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        if self.Debug: cherrypy.log("[DEBUG]: Receive /categories/%s/%s" % (command,argv1), 'ENGINE')
        json_data["status"] = False
        json_data["info"] = "Could not understand commands /%s/%s/%s" % (command,argv1,argv2)
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
        pass

    categories.exposed = True



    def softwares(self,command,app=None,ver=None):
        json_data = {}
        try:
            if not self.check_security():
                json_data["status"] = False
                json_data["info"] = "Your IP address ["+self.remote_ip+"] is not allowed to perform this operation"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

        except:
            cherrypy.log(tb.format_exc(), "ENGINE")
            json_data["status"] = False
            json_data["info"] = "Fatal error while checking security accesses ( see syslog messages )."
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        self.addheaders()
        if command == "list": return self.softwares_update_list()
        if command == "index": return self.softwares_update_index()
        if command == "upgrade": return self.sofwares_install(app,ver)
        if command == "install": return self.sofwares_install(app, ver)

        json_data["status"] = False
        json_data["info"] = "Unable to understand the %s command" % command
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
    softwares.exposed=True

    def snapshots(self, order, value=None):
        json_data = {}
        try:
            if not self.check_security():
                json_data["status"] = False
                json_data["info"] = "Your IP address ["+self.remote_ip+"] is not allowed to perform this operation"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

        except:
            cherrypy.log(tb.format_exc(), "ENGINE")
            json_data["status"] = False
            json_data["info"] = "Fatal error while checking security accesses ( see syslog messages )."
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        if self.ActiveDirectoryRestSnapsEnable==0:
            json_data["status"] = False
            json_data["info"]   = "Feature not activated"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if order == "list": return self.snapshots_list()
        if order == "execute": return self.snapshots_backup()
        if order == "restore": return self.snapshots_restore(value)
        if order == "remove": return self.snapshots_remove(value)
        if order == "download": return self.snapshots_download(value)

        json_data["status"] = False
        json_data["info"] = "Unable to understand the %s command" % order
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content

    snapshots.exposed=True


    def snapshots_list(self):
        json_data = {}
        serialized  = GET_INFO_STR("SnapShotList")

        if len(serialized)<3:
            json_data["status"]  = False
            json_data["info"]    = "No container listed on this server"
            json_data["content"] = {}
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        try:
            serialized_content = unserialize(serialized)
        except:
            if self.Debug: cherrypy.log("[DEBUG]: FATAL cannot read array from SnapShotList",'ENGINE')
            cherrypy.log(tb.format_exc(), "ENGINE")
            json_data["status"]  = False
            json_data["info"]    = "Failed to decode SnapShotList"
            json_data["content"] = {}
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"]  = True
        json_data["info"]    = "List of available backup containers (snapshots)"
        json_data["content"] = serialized_content
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
        pass

    def snapshots_backup(self):
        json_data       = {}
        progress_file   = "%s/ressources/logs/web/backup.artica.progress" % self.artica_root
        logfile         = "/etc/artica-postfix/snapshot.log"
        cmdline         = "%s %s/exec.backup.artica.php --snapshot >%s 2>&1" % (self.php,self.artica_root,logfile)
        os.system(cmdline)
        if not self.read_progress(progress_file):
            json_data["status"] = False
            json_data["info"] = self.zreadfile(logfile)
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"] = True
        json_data["info"] = "Configuration backup executed with success"
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
        pass

    def snapshots_restore(self,filename):
        json_data       = {}
        progress_file   = "%s/ressources/logs/web/backup.artica.progress" % self.artica_root
        logfile         = "/etc/artica-postfix/snapshot.log"

        if filename is None:
            json_data["status"] = False
            json_data["info"] = "No defined snapshot container filename"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        cmdline = "%s %s/exec.backup.artica.php --snapshot-file %s >%s 2>&1" % (self.php, self.artica_root,filename, logfile)
        os.system(cmdline)

        if not self.read_progress(progress_file):
            json_data["status"] = False
            json_data["info"] = self.zreadfile(logfile)
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"] = True
        json_data["info"] = "Restoring backup %s success" % filename
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
    


    def snapshots_download(self,filename):
        json_data       = {}
        progress_file   = "%s/ressources/logs/web/backup.artica.progress" % self.artica_root
        logfile         = "/etc/artica-postfix/snapshot.log"
        destfile        = "%s/ressources/web/logs/%s" % (self.artica_root,filename)

        if filename is None:
            json_data["status"] = False
            json_data["info"] = "No defined snapshot container filename"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        cmdline = "%s %s/exec.backup.artica.php --prepare-download %s >%s 2>&1" % (self.php, self.artica_root,filename, logfile)
        os.system(cmdline)

        if not self.read_progress(progress_file):
            json_data["status"] = False
            json_data["info"] = self.zreadfile(logfile)
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if not os.path.exists(destfile):
            json_data["status"] = False
            json_data["info"] = "%s expected but unable to stat" % destfile
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        return serve_file(destfile, '', "attachment", filename)


    def snapshots_remove(self,filename):
        json_data = {}
        progress_file = "%s/ressources/logs/web/backup.artica.progress" % self.artica_root
        logfile = "/etc/artica-postfix/snapshot.log"

        if filename is None:
            json_data["status"] = False
            json_data["info"] = "No defined snapshot container filename"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        cmdline = "%s %s/exec.backup.artica.php --snapshot-remove %s >%s 2>&1" % (self.php, self.artica_root,filename, logfile)
        os.system(cmdline)

        if not self.read_progress(progress_file):
            json_data["status"] = False
            json_data["info"] = self.zreadfile(logfile)
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"] = True
        json_data["info"] = "Removing backup %s success" % filename
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content


    def read_progress(self,path):
        data=self.zreadfile(path)
        try:
            array=unserialize(data)
            prc=int(array["POURC"])
            if prc==0: return False
            if prc==100: return True
            if prc>100: return False

        except:
            if self.Debug: cherrypy.log("[DEBUG]: FATAL cannot read array from %s" % path,'ENGINE')
            cherrypy.log(tb.format_exc(), "ENGINE")
            return False
        pass





    def sofwares_install(self,APP_PRODUCT,INT_VER):
        json_data = {}
        if APP_PRODUCT == None:
            json_data["status"] = False
            json_data["info"] = "Fatal error 205 You need to define a Product Key (like APP_UNBOUND for DNS Cache)"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if INT_VER == None:
            json_data["status"] = False
            json_data["info"] = "Fatal error 212 You need to define a integer version"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        INT_VER = unicode(INT_VER, 'utf-8')
        if not INT_VER.isnumeric():
            json_data["status"] = False
            json_data["info"] = "Fatal error 219 You need to define a integer version"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if int(INT_VER) < 10:
            json_data["status"] = False
            json_data["info"] = "Fatal error 227 You need to define a integer version, "+str(INT_VER)+" is not allowed"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        autnpath = "/etc/artica-postfix/"+APP_PRODUCT
        tmpfile  = "/tmp/"+APP_PRODUCT+".sh"
        if os.path.exists(tmpfile): RemoveFile(tmpfile)
        file = open(tmpfile, "w")
        file.write("!#/bin/sh\n")
        file.write("logger -i -t active-directory-rest \"Executing upgrade of %s in %s binary version\" || true\n" % (APP_PRODUCT,INT_VER))
        file.write("/usr/bin/php ")
        file.write("/usr/share/artica-postfix/exec.installv2.php ")
        file.write("--install %s %s >%s 2>&1 || true\n" % (APP_PRODUCT,INT_VER,autnpath))
        file.close()

        if not os.path.exists(tmpfile):
            json_data["status"] = False
            json_data["info"] = "Fatal error 553 no space left on device"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        os.chmod(tmpfile, 0o755)
        os.system(tmpfile)


        scontent=self.zreadfile(autnpath)
        json_data["status"] = True
        if len(scontent)<240:
            json_data["status"] = False

        json_data["info"] = scontent
        json_data["command"]="Installing "+APP_PRODUCT+" binary version:"+str(INT_VER)
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content


    def softwares_update_index(self):
        autnpath = "/etc/artica-postfix/CHECK_REPO"
        json_data = {}
        fsize = 0

        RemoveFile(autnpath)
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.check.repositories.php >" + autnpath + " 2>&1")
        if os.path.exists(autnpath): fsize = os.path.getsize(autnpath)
        if fsize < 10:
            json_data["status"] = False
            json_data["info"] = "An error occures while generating the index file."
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"] = True
        json_data["info"] = self.zreadfile(autnpath)
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
        pass


    def softwares_update_list(self):
        v4softsrepo = GET_INFO_STR("v4softsRepo")
        if self.Debug: cherrypy.log("[DEBUG]: v4softsrepo: " + str(v4softsrepo),
                                    'ENGINE')

        if len(v4softsrepo)<128:
            return self.softwares_update_list_erro(175)

        try:
            v4softsrepo_decoded=base64.b64decode(v4softsrepo)
        except:
            return self.softwares_update_list_erro(180)

        try:
            json_data   = unserialize(v4softsrepo_decoded)
        except:
            return self.softwares_update_list_erro(185)

        json_data["status"] = True
        json_data["info"] = "List of available softwares that can be updated"
        json_data["hostname"] = self.hostname
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content

    def softwares_update_list_erro(self,error_code):
        json_data={}
        json_data["status"] = False
        json_data["hostname"] = self.hostname
        json_data["info"]   = "Error number "+str(error_code)+ "An error occurs while generating the list, please send a refresh command in order to force this server to refresh the index pattern"
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content


    def kerberos_status(self):
        autnpath = "/etc/artica-postfix/MONIT_NTLM_AUTH"
        self.parse_proxy_uri()
        json_data={}
        json_data["hostname"] = self.hostname
        json_data["tested_proxy"] = self.proxy_uri

        if not self.klist_cmd():
            json_data["status"] = False
            json_data["info"] = "missing HTTP/"+self.hostname +" in keytab"
            json_data["hostname"] = self.hostname
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if not self.negotiate_kerberos_auth_test():
            json_data["status"] = False
            json_data["info"] = self.test_credentials_info
            json_data["hostname"] = self.hostname
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        self.ntlmauthenticator()


        if os.path.exists(autnpath):
            if self.Debug: cherrypy.log("[DEBUG]: Open " + autnpath, "ENGINE")
            with open(autnpath, 'r') as reader:
                for line in reader:
                    if self.Debug: cherrypy.log("[DEBUG]: [" + line.strip() + "]", "ENGINE")
                    matches = re.search('number active: ([0-9]+) of ([0-9]+)', line.strip())
                    if matches:
                        json_data["current_daemon"] = matches.group(1)
                        json_data["max_daemons"] = matches.group(2)
                        continue

                    matches = re.search('requests sent:\s+([0-9]+)', line.strip())
                    if matches:
                        json_data["requests_sent"] = matches.group(1)
                        continue

                    matches = re.search('replies received:\s+([0-9]+)', line.strip())
                    if matches:
                        json_data["replies_received"] = matches.group(1)
                        continue

                    matches = re.search('queue length:\s+([0-9]+)', line.strip())
                    if matches:
                        json_data["queue_length"] = matches.group(1)
                        continue

                    matches = re.search('avg service time:\s+(.+)', line.strip())
                    if matches:
                        json_data["avg_service_time"] = matches.group(1)
                        continue

        json_data["info"] = "The Active Directory (Kerberos) Connection seems OK"
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content







    def negotiate_kerberos_auth_test(self):
        filesrc = "/etc/artica-postfix/TGT"
        binary  = "/lib/squid3/negotiate_kerberos_auth_test"
        ticket  = ""
        if not os.path.exists(binary):
            self.test_credentials_info=binary+" not found on your server"
            return False

        cmdline=binary+ " "+ self.hostname+ " >" +filesrc +" 2>&1"
        if self.Debug: cherrypy.log("[DEBUG]: "+cmdline, 'ENGINE')
        os.system(cmdline)
        if not os.path.exists(filesrc):
            self.test_credentials_info = " No output given or crashed system"
            return False

        with open(filesrc, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                if len(txt) == 0: continue
                if self.Debug: cherrypy.log("[DEBUG]: [" + txt+"]", 'ENGINE')

                matches = re.search("failed:",txt)
                if matches:
                    self.test_credentials_info = txt
                    return False

                matches = re.search("Token:.*?NULL",txt)
                if matches:
                    self.test_credentials_info = "Null ticket provided"
                    return False


                matches = re.search("Token:\s+(.+)",txt)
                if matches:
                    ticket=matches.group(1)
                    break


        if len(ticket)==0:
            self.test_credentials_info = "No ticket parsed during the test"
            return False

        if len(ticket) > 128: return True
        self.test_credentials_info = "Perhaps a bug, but unable to understand result"
        return False









    def klist_cmd(self):
        if not os.path.exists("/etc/squid3/PROXY.keytab"): return "Keytab missing (/etc/squid3/PROXY.keytab)"
        srcfile = "/etc/artica-postfix/KLIST"
        cmd="/usr/bin/klist -k /etc/squid3/PROXY.keytab >"+srcfile+" 2>&1"

        os.system(cmd)
        shostname=self.hostname
        shostname.replace(".","\.")
        pattern="HTTP\/"+shostname
        with open(srcfile, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                if len(txt) == 0: continue
                matches = re.search(pattern,txt)
                if matches: return True
        return False
        pass

    def artica_hotfix(self):
        srcfile = "/usr/share/artica-postfix/fw.updates.php"
        with open(srcfile, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                if len(txt) == 0: continue
                matches = re.search("HOTFIX.*?\].*?([0-9]+)", txt)
                if matches:
                    return matches.group(1)

        return 0
        pass


    def kerberos_gcc_no_name(self):
        srcfile="/etc/squid3/authenticate.conf"
        with open(srcfile, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                if len(txt) == 0: continue
                matches = re.search("GSS_C_NO_NAME")
                if matches: return True
        return False
        pass

    def ip2long(self,ip_addr):
        ip_packed = inet_aton(ip_addr)
        ip = unpack("!L", ip_packed)[0]
        return ip

    def parse_proxy_uri(self):
        srcfile= "/etc/squid3/listen_ports.conf"
        with open(srcfile, "r") as f:
            for txt in f:
                txt = txt.rstrip('\n')
                if len(txt) == 0: continue
                matches = re.search("^(http|https)_port\s+([0-9\.]+):([0-9]+).*?name=MyPortNameID",txt)
                if matches:
                    lprot = matches.group(1)
                    laddr = matches.group(2)
                    lport = matches.group(3)
                    if laddr == "0.0.0.0": laddr="127.0.0.1"
                    if self.Debug: cherrypy.log("[DEBUG]: Found proxy uri "+lprot+"://"+laddr+":"+lport, 'ENGINE')
                    self.proxy_uri=lprot+"://"+laddr+":"+lport
                    return True
        return false
        pass


    def generic_error(self):
        SquidUrgency = GET_INFO_INT("SquidUrgency")
        EnableKerbAuth = GET_INFO_INT("EnableKerbAuth")
        EnableKerbNTLM = GET_INFO_INT("EnableKerbNTLM")
        ActiveDirectoryEmergency = GET_INFO_INT("ActiveDirectoryEmergency")
        LockActiveDirectoryToKerberos = GET_INFO_INT("LockActiveDirectoryToKerberos")
        if LockActiveDirectoryToKerberos == 1:
            EnableKerbAuth = 1
            EnableKerbNTLM = 1

        if SquidUrgency == 1: return "Proxy in Emergency mode!"
        if self.RemoveEmergency==False:
            if ActiveDirectoryEmergency == 1: return "Active Directory in Emergency mode!"

        if EnableKerbAuth == 0: return  "Active Directory feature is disabled! (EnableKerbAuth)"
        if EnableKerbNTLM == 0: return "NTLM Authentication Disabled!"
        try:
            if not self.check_security():
                return "Your IP address ["+self.remote_ip+"] is not allowed to perform this operation"
        except:
            cherrypy.log(tb.format_exc(), "ENGINE")
            return "Fatal error while checking security accesses ( see syslog messages )."
        return ""

    def md5_file(self,filename):
        crc = hashlib.md5()
        fp = open(filename, 'rb')
        for i in fp:
            crc.update(i)
        fp.close()
        return crc.hexdigest()

    def check_security(self):
        self.GetInfos()
        IPfrom = self.remote_ip
        ActiveDirectoryRestRestrict = GET_INFO_STR("ActiveDirectoryRestRestrict")
        splitted=ActiveDirectoryRestRestrict.splitlines()
        if len(splitted)==0:
            if self.Debug: cherrypy.log("[DEBUG]: No security defined, using defaults 127.0.0.0/8, 192.168.0.0/16, 10.0.0.0/8, 172.16.0.0/12", "ENGINE")
            splitted.append("192.168.0.0/16")
            splitted.append("10.0.0.0/8")
            splitted.append("172.16.0.0/12")
            splitted.append("127.0.0.0/8")

        for mynet in splitted:
            if len(mynet)==0: continue
            if self.Debug: cherrypy.log("[DEBUG]: Checking [" + mynet + "] against ["+IPfrom+"]", "ENGINE")
            if IPAddress(IPfrom) in IPNetwork(mynet):
                if self.Debug: cherrypy.log("[DEBUG]: [" + IPfrom + "] is a part of [" + mynet + "] OK", "ENGINE")
                return True
        return False

    def ntlmauthenticator(self):
        binary              = "/usr/bin/squidclient"
        SquidMgrListenPort  = GET_INFO_INT("SquidMgrListenPort")
        plugin="ntlmauthenticator"
        if self.kerberos: plugin="negotiateauthenticator"
        cmdline=binary+" -h 127.0.0.1 -p "+str(SquidMgrListenPort)+" mgr:"+plugin+" >/etc/artica-postfix/MONIT_NTLM_AUTH 2>&1"
        if self.Debug: cherrypy.log("[DEBUG]: [" + cmdline + "]", "ENGINE")
        os.system(cmdline)

    @cherrypy.expose
    def artica_version(self):
        json_data={}
        error = self.generic_error()
        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        ARTICAVER=self.zreadfile("/usr/share/artica-postfix/VERSION")
        SERVICEPACK=self.zreadfile("/usr/share/artica-postfix/SP/"+ARTICAVER)
        HOTFIX=self.artica_hotfix()
        json_data["status"]         = True
        json_data["version"]        = "Artica version "+ARTICAVER+" Service Pack "+ SERVICEPACK
        json_data["major"]          = ARTICAVER
        json_data["hotfix"] = HOTFIX
        json_data["service_pack"]   = SERVICEPACK
        json_data["latest_upgrade"] = self.zreadfile("/etc/artica-postfix/PATCHS")
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content



    @cherrypy.expose
    def status(self):
        json_data={}
        fsize       = 0
        autnpath    = "/etc/artica-postfix/MONIT_NTLM_AUTH"
        error       = self.generic_error()


        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        if self.kerberos:
            if self.Debug: cherrypy.log("[DEBUG]: Using Kerberos method", "ENGINE")
            return self.kerberos_status()



        RemoveFile("/etc/artica-postfix/MONIT_NTLM_LOG")
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.monit.ntlm.php >/etc/artica-postfix/MONIT_NTLM_LOG")
        tests_result = self.zreadfile("/etc/artica-postfix/MONIT_NTLM")

        if os.path.exists("/etc/artica-postfix/MONIT_NTLM_LOG"):
            fsize=os.path.getsize("/etc/artica-postfix/MONIT_NTLM_LOG")


        if self.Debug: cherrypy.log("[DEBUG]: Returned:["+tests_result+"], Running authenticator", "ENGINE")
        if len(tests_result) == 0:
            json_data["status"] = False
            json_data["info"] = "Process crashed! size:"+str(fsize)+" bytes "+tests_result
            json_data["hostname"] = self.hostname
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        if int(tests_result) == 0:
            if self.Debug: cherrypy.log("[DEBUG]: Status:True, Running authenticator (1)", "ENGINE")
            self.ntlmauthenticator()

            json_data["status"] = True
            json_data["hostname"] = self.hostname
            if self.Debug: cherrypy.log("[DEBUG]: Status:True, testing credentials ? = "+ str(self.ActiveDirectoryRestTestUser), "ENGINE")
            if self.ActiveDirectoryRestTestUser ==1:

                url = GET_INFO_STR("ActiveDirectoryRestTestURL")
                json_data["tested_url"] = url
                json_data["tested_proxy"] = self.proxy_uri
                try:
                    if not self.test_credentials():
                        json_data["status"] = False
                        json_data["test_credentials_result"]=self.test_credentials_info
                        json_data["info"] = "The Active Directory credentials failed"
                        content = json.dumps(json_data, indent=4, separators=(',', ': '))
                        self.addheaders()
                        return content
                except:
                    cherrypy.log(tb.format_exc(), "ENGINE")


            json_data["test_credentials_result"] = self.test_credentials_info

            if not os.path.exists(autnpath):
                if self.Debug: cherrypy.log("[DEBUG]: "+autnpath+" no such file", "ENGINE")
                json_data["status"] = False
                json_data["test_credentials_result"] = autnpath+ "no such file"
                json_data["info"] = "The Active Directory test failed"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content


            if os.path.exists(autnpath):
                if self.Debug: cherrypy.log("[DEBUG]: Open "+autnpath, "ENGINE")
                with open(autnpath, 'r') as reader:
                    for line in reader:
                        if self.Debug: cherrypy.log("[DEBUG]: [" + line.strip() + "]", "ENGINE")
                        matches = re.search('number active: ([0-9]+) of ([0-9]+)', line.strip())
                        if matches:
                            json_data["current_daemon"]=matches.group(1)
                            json_data["max_daemons"] = matches.group(2)
                            continue

                        matches = re.search('requests sent:\s+([0-9]+)', line.strip())
                        if matches:
                            json_data["requests_sent"]=matches.group(1)
                            continue

                        matches = re.search('replies received:\s+([0-9]+)', line.strip())
                        if matches:
                            json_data["replies_received"]=matches.group(1)
                            continue

                        matches = re.search('queue length:\s+([0-9]+)', line.strip())
                        if matches:
                            json_data["queue_length"]=matches.group(1)
                            continue

                        matches = re.search('avg service time:\s+(.+)', line.strip())
                        if matches:
                            json_data["avg_service_time"] = matches.group(1)
                            continue

            json_data["info"]="The Active Directory (NTLM) Connection seems OK"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        if int(tests_result) == 1:
            json_data["status"] = False
            json_data["hostname"] = self.hostname
            json_data["info"] = self.zreadfile("/etc/artica-postfix/MONIT_NTLM_LOG")

        content=json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
        pass

    @cherrypy.expose
    def index(self):
        raise cherrypy.HTTPError(404)
        pass



    @cherrypy.expose
    def emergency_enable(self):
        json_data={}
        error=self.generic_error()
        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = Error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        os.system("/usr/bin/php /usr/share/artica-postfix/exec.msktutils.php --emergency >/etc/artica-postfix/NTLM_LOG")
        json_data["status"] = True
        json_data["info"] = self.zreadfile("/etc/artica-postfix/NTLM_LOG")
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content

    @cherrypy.expose
    def emergency_disable(self):
        json_data = {}
        self.RemoveEmergency = True
        error=self.generic_error()
        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        os.system("/usr/bin/php /usr/share/artica-postfix/exec.msktutils.php --emergency-remove >/etc/artica-postfix/NTLM_LOG")
        json_data["status"] = True
        json_data["info"] = self.zreadfile("/etc/artica-postfix/NTLM_LOG")
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content


    @cherrypy.expose
    def ntlm_disconnect(self):
        json_data = {}
        error = self.generic_error()
        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        os.system("/usr/bin/php /usr/share/artica-postfix/exec.nltm.disconnect.php >/etc/artica-postfix/NTLM_DIS")
        json_data["status"] = True
        json_data["info"] = self.zreadfile("/etc/artica-postfix/NTLM_DIS")
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content

    @cherrypy.expose
    def kerberos_connect(self):
        json_data = {}
        autnpath = "/etc/artica-postfix/NTLM_LOG"

        error=self.generic_error()
        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        os.system("/usr/bin/php /usr/share/artica-postfix/exec.msktutils.php --run >"+autnpath+" 2>&1")
        json_data["status"] = False
        json_data["info"] = "Kerberos connection: operation completed\n%s " % self.zreadfile(autnpath)
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content


    @cherrypy.expose
    def kerberos_disconnect(self):
        json_data={}
        autnpath = "/etc/artica-postfix/NTLM_LOG"

        error=self.generic_error()
        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        os.system("/usr/bin/php /usr/share/artica-postfix/exec.msktutils.php --off >"+autnpath+" 2>&1")
        json_data["status"] = False
        json_data["info"] = "Kerberos disconnection: operation completed\n%s" % self.zreadfile(autnpath)
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content

    @cherrypy.expose
    def ssh(self,command,subcommand=None,value=None):
        json_data = {}
        try:
            if not self.check_security():
                json_data["status"] = False
                json_data["info"] = "Your IP address ["+self.remote_ip+"] is not allowed to perform this operation"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

        except:
            cherrypy.log(tb.format_exc(), "ENGINE")
            json_data["status"] = False
            json_data["info"] = "Fatal error while checking security accesses ( see syslog messages )."
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        if command=="status":
            if not os.path.exists("/etc/init.d/ssh"):
                json_data["status"] = False
                json_data["installed"] = 0
                json_data["info"] = "SSH service is not installed"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content
            logfile = "/tmp/ssh.install.log"
            cmdline = "%s %s/exec.status.php --openssh --json >%s 2>&1" % (self.php, self.artica_root, logfile)
            os.system(cmdline)
            f = open(logfile)
            try:
                data=json.load(f)
            except:
                json_data["status"] = False
                json_data["installed"] = -1
                json_data["info"] = "The status is only available with Artica 4.30 Service pack 209 or above"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

            json_data["content"]  = data
            json_data["status"] = True
            json_data["installed"] = 1
            json_data["info"] = "Status of OpenSSH service"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if command=="install":
            if os.path.exists("/etc/init.d/ssh"):
                json_data["status"] = False
                json_data["installed"] = 1
                json_data["info"] = "SSH service is already installed"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content
            logfile = "/tmp/ssh.install.log"
            cmdline = "%s %s/exec.sshd.php --install >%s 2>&1" % (self.php, self.artica_root, logfile)
            os.system(cmdline)
            log_content = self.zreadfile(logfile)
            json_data["status"] = true
            json_data["installed"] = 1
            json_data["info"] = log_content
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if command == "restart":
            if not os.path.exists("/etc/init.d/ssh"):
                json_data["status"] = False
                json_data["installed"] = 0
                json_data["info"] = "SSH service is not installed"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content

            logfile = "/tmp/ssh.install.log"
            cmdline = "%s %s/exec.sshd.php --progress >%s 2>&1" % (self.php, self.artica_root, logfile)
            os.system(cmdline)
            os.system("/etc/init.d/ssh restart >>%s 2>&1" % logfile)
            log_content = self.zreadfile(logfile)

            json_data["status"] = True
            json_data["installed"] = 1
            json_data["info"] = log_content

            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content



        if command == "uninstall":
            if not os.path.exists("/etc/init.d/ssh"):
                json_data["status"] = False
                json_data["installed"] = 1
                json_data["info"] = "SSH service is already uninstalled"
                content = json.dumps(json_data, indent=4, separators=(',', ': '))
                self.addheaders()
                return content
            logfile = "/tmp/ssh.install.log"
            cmdline = "%s %s/exec.sshd.php --uninstall >%s 2>&1" % (self.php, self.artica_root, logfile)
            os.system(cmdline)

            log_content = self.zreadfile(logfile)
            json_data["status"] = True
            json_data["installed"] = 0
            json_data["info"] = log_content
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"] = False
        json_data["info"] = "did not understand %s ( use status/install/uninstall/restart)" % command
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content


    @cherrypy.expose
    def proxy(self,command,subcommand=None,value=None):
        json_data = {}

        if self.SQUIDEnable==0:
            json_data["status"] = False
            json_data["info"] = "Proxy Service is not enabled"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        error=self.generic_error()
        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if command=="auth":
            if subcommand=="status":
                return self.proxy_auth_config_verif()

            if subcommand=="reconfigure":
                return self.proxy_auth_config_build()

        if command=="general":
            if subcommand=="reconfigure":
                return self.proxy_general_config_build()

            if subcommand=="reset":
                return self.proxy_general_config_reset()


        json_data["status"] = False
        json_data["info"] = "Could not understand command %s/%s/%s" % (command,subcommand,value)
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content

        pass




    @cherrypy.expose
    def ntlm_connect(self):
        json_data   = {}
        error       = self.generic_error()
        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.nltm.disconnect.php >/etc/artica-postfix/NTLM_LOG 2>&1")
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.nltm.connect.php >>/etc/artica-postfix/NTLM_LOG 2>&1")
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.monit.ntlm.php >>/etc/artica-postfix/NTLM_LOG 2>&1")

        tests_result = self.zreadfile("/etc/artica-postfix/MONIT_NTLM")

        if len(tests_result) == 0:
            json_data["status"] = False
            json_data["info"] = "Process crashed!"
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        if tests_result == 1:
            json_data["status"]=True
            json_data["info"]="The Active Directory re-connection seems OK"

        if tests_result == 0:
            json_data["status"] = False
            json_data["info"] = self.zreadfile("/etc/artica-postfix/NTLM_LOG")

        content=json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content
        pass

    @cherrypy.expose
    def emergency_remove(self):
        json_data = {}
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.squid.urgency.remove.php")
        self.RemoveEmergency=True
        error=self.generic_error()
        if len(error)>0:
            json_data["status"] = False
            json_data["info"] = error
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"] = True
        json_data["info"] = "Success returning the proxy back to production"

    def proxy_general_config_reset(self):
        json_data       = {}
        logfile         = "/etc/artica-postfix/cmd.log"
        progress_file   = "%sressources/logs/squid.complete-rebuild.progress" % self.artica_root
        cmdline         = "%s %s/exec.squid.rebuild-restart.php >%s 2>&1" % (self.php,self.artica_root,logfile)

        RemoveFile("/root/squid-good.tgz")

        if self.Debug: cherrypy.log("[DEBUG]: Execute %s" % cmdline, 'ENGINE')
        os.system(cmdline)
        json_data["status"] = True
        json_data["info"]   = "Success reseting all proxy parameters and build a new one"
        json_data["traces"] = self.zreadfile(logfile)
        RemoveFile(logfile)
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content


    def proxy_general_config_build(self):
        json_data       = {}
        logfile         = "/etc/artica-postfix/cmd.log"
        progress_file   = "%s/ressources/logs/squid.access.center.progress" % self.artica_root
        cmdline         = "%s %s/exec.squid.global.access.php >%s 2>&1" % (self.php,self.artica_root,logfile)

        if self.Debug: cherrypy.log("[DEBUG]: Execute %s" % cmdline, 'ENGINE')
        os.system(cmdline)

        if not self.read_progress(progress_file):
            json_data["status"] = False
            json_data["info"] = self.zreadfile(logfile)
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"] = True
        json_data["info"]   = "Success reconfiguring all proxy parameters"
        json_data["traces"] = self.zreadfile(logfile)

        RemoveFile(logfile)

        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content


    def proxy_auth_config_build(self):
        json_data       = {}
        logfile         = "/etc/artica-postfix/cmd.log"
        progress_file   = "%s/ressources/logs/squid.access.center.progress" % self.artica_root
        cmdline         = "%s %s/exec.squid.global.access.php --auth >%s 2>&1" % (self.php,self.artica_root,logfile)

        if self.Debug: cherrypy.log("[DEBUG]: Execute %s" % cmdline, 'ENGINE')
        os.system(cmdline)

        if not self.read_progress(progress_file):
            json_data["status"] = False
            json_data["info"] = self.zreadfile(logfile)
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"] = True
        json_data["info"]   = "Success reconfiguring authenticate settings in proxy"
        json_data["traces"] = self.zreadfile(logfile)

        RemoveFile(logfile)

        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content



    def proxy_auth_config_verif(self):
        authenticate        = "/etc/squid3/authenticate.conf"
        http_access_final   = "/etc/squid3/http_access_final.conf"
        found_token_1       = False
        found_token_2       = False
        json_data           = {}
        if not os.path.exists(authenticate):
            json_data["status"] = False
            json_data["info"] = "%s no such file " % authenticate
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        if not os.path.exists(authenticate):
            json_data["status"] = False
            json_data["info"] = "%s no such file " % http_access_final
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        with open(authenticate, 'r') as reader:
            for line in reader:
                matches = re.search('^auth_param (negotiate|ntlm) program', line.strip())
                if matches:
                    found_token_1=True
                    break

        if not found_token_1:
            json_data["status"] = False
            json_data["info"] = "%s Proxy is not connected to any authentication scheme in " % authenticate
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content


        with open(http_access_final, 'r') as reader:
            for line in reader:
                matches = re.search('^http_access deny.*?AUTHENTICATED', line.strip())
                if matches:
                    found_token_2=True
                    break


        if not found_token_2:
            json_data["status"] = False
            json_data["info"] = "%s Proxy is not connected to any authentication scheme in " % http_access_final
            content = json.dumps(json_data, indent=4, separators=(',', ': '))
            self.addheaders()
            return content

        json_data["status"] = True
        json_data["info"] = "Proxy is correctly configured"
        content = json.dumps(json_data, indent=4, separators=(',', ': '))
        self.addheaders()
        return content

    def load_curl_get(self,url):
        headers = ["Content-Type:application/json"]
        curl_obj = None

        s_config = GET_INFO_STR("ArticaProxySettings")
        curl_useragent=GET_INFO_STR("CurlUserAgent")
        useragent_default="Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0"
        use_proxy = self.str2bool(self.getini(s_config, 'PROXY', 'ArticaProxyServerEnabled', "0"))
        if len(curl_useragent)==0:curl_useragent=useragent_default




        try:
            curl_obj = pycurl.Curl()
            curl_obj.setopt(pycurl.USERAGENT,curl_useragent)
            curl_obj.setopt(pycurl.URL, url)
            curl_obj.setopt(pycurl.CONNECTTIMEOUT, 30)
            curl_obj.setopt(pycurl.TCP_KEEPALIVE, 1)
            curl_obj.setopt(pycurl.TCP_KEEPIDLE, 30)
            curl_obj.setopt(pycurl.TCP_KEEPINTVL, 15)
            curl_obj.setopt(pycurl.SSL_VERIFYHOST, 0)
            curl_obj.setopt(pycurl.SSL_VERIFYPEER, False)
            curl_obj.setopt(pycurl.SSLVERSION, pycurl.SSLVERSION_TLSv1)
            curl_obj.setopt(pycurl.HEADER, True)
            curl_obj.setopt(pycurl.HTTPAUTH, pycurl.HTTPAUTH_BASIC)
            curl_obj.setopt(pycurl.HTTPHEADER, headers)
            curl_obj.setopt(pycurl.POST, 0)

            if use_proxy:
                proxy_addr=self.getini(s_config, 'PROXY',"ArticaProxyServerName","0.0.0.0")
                proxy_port=int(self.getini(s_config, 'PROXY',"ArticaProxyServerPort","3128"))
                curl_obj.setopt(pycurl.PROXY, proxy_addr)
                curl_obj.setopt(pycurl.PROXYPORT, proxy_port)
                curl_obj.setopt(pycurl.PROXYTYPE, pycurl.PROXYTYPE_HTTP)


        except:
            cherrypy.log("[ERROR]: "+tb.format_exc(), "ENGINE")
            return None
        return curl_obj



    def addheaders(self):
        cherrypy.response.headers['Content-Type'] = 'application/json'
        cherrypy.response.headers['Content-Transfer-Encoding'] = 'binary'
        pass

    def GetInfos(self):
        self.UserAgent = ""
        self.HTTP_X_REAL_IP = ""
        self.HTTP_X_FORWARDED_FOR = ""
        self.remote_ip = ""


        if self.ActiveDirectoryRestDebug == 1:
            cherrypy.log("[DEBUG]: Retrieve infos", 'ENGINE')

        if self.ActiveDirectoryRestDebug == 1:
            for key in cherrypy.request.headers:
                cherrypy.log("[DEBUG]: Head '" + key + "'" + cherrypy.request.headers[key], "ENGINE")

        try:
            self.remote_ip = cherrypy.request.headers["Remote-Addr"]
        except:
            cherrypy.log("[WARNING]: cherrypy.request.remote_addr Failed!")
            cherrypy.log(tb.format_exc())

        try:
            if self.ActiveDirectoryRestDebug == 1: cherrypy.log(
                "[DEBUG]: Header User-Agent = '" + cherrypy.request.headers["User-Agent"] + "'", "ENGINE")
            self.UserAgent = cherrypy.request.headers["User-Agent"]
        except:
            cherrypy.log("[WARNING]: Unable to find the UserAgent in headers...", "PAC", logging.DEBUG)

        try:
            self.HTTP_X_FORWARDED_FOR = cherrypy.request.headers["HTTP_X_FORWARDED_FOR"]
        except:
            cherrypy.log("[WARNING]: Unable to find the HTTP_X_FORWARDED_FOR in headers...", "PAC",
                         logging.DEBUG)

        try:
            self.HTTP_X_REAL_IP = cherrypy.request.headers["HTTP_X_REAL_IP"]
        except:
            cherrypy.log("[WARNING]: Unable to find the HTTP_X_REAL_IP in headers...", "PAC", logging.DEBUG)

        if 'X-Real-Ip' in cherrypy.request.headers:
            self.HTTP_X_REAL_IP = cherrypy.request.headers['X-Real-Ip']

        if 'X-Forwarded-For' in cherrypy.request.headers:
            self.HTTP_X_FORWARDED_FOR = cherrypy.request.headers["X-Forwarded-For"]

        # cherrypy.log("HTTP_X_FORWARDED_FOR: " + self.HTTP_X_FORWARDED_FOR + "[" + self.UserAgent + "]")
        # cherrypy.log("HTTP_X_REAL_IP: " + self.HTTP_X_REAL_IP + "[" + self.UserAgent + "]")
        if len(self.HTTP_X_FORWARDED_FOR) > 3: self.remote_ip = self.HTTP_X_FORWARDED_FOR
        if len(self.HTTP_X_REAL_IP) > 3: self.remote_ip = self.HTTP_X_REAL_IP

        if self.UserAgent.find('Monit/5') != -1:
            if self.ActiveDirectoryRestDebug == 1: cherrypy.log(
                "[DEBUG]: Will not serve proxy.pac for local monitor, Aborting'", "ENGINE")
            return True

        cherrypy.log("[" + self.remote_ip + "][INFO]: Connexion with UserAgent:[" + self.UserAgent + "]", 'CONNEX')

    index.exposed = False

    @cherrypy.expose
    def default(self):
        self.GetInfos()
        pass

    default.exposed = True


d = Daemonizer(cherrypy.engine)
d.subscribe()
cherrypy.quickstart(WebService(), '/')
