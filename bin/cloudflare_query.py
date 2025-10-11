#!/usr/bin/env python
import sys
import pycurl
import time
from StringIO import StringIO
import json
import memcache
import traceback as tb
import syslog
import re

class CloudflareQuery:

    def __init__(self,debug=False,syslogC=None):
        self.curl_obj   = None
        self.syslog     = None
        self.debug      = debug
        self.mc         = None
        self.check_porn = True
        self.output     = False


        if syslogC is None:
            self.syslog=syslog
            self.syslog.openlog("ksrn", syslog.LOG_PID)
        else:
            self.syslog=syslogC

        if self.load_engine(): self.sysdbg("Loaded engine success...")
        pass

    def load_engine(self):
        headers = ["accept: application/dns-json"]
        self.curl_obj = None
        self.mc       = None

        try:
            self.mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        except:
            self.syslog.syslog(syslog.LOG_INFO, "[ERROR]: FATAL: Loading Memcache Engine Failed")
            self.syslog.syslog(syslog.LOG_INFO, "[ERROR]: " + tb.format_exc())

        try:
            self.curl_obj = pycurl.Curl()
            self.curl_obj.setopt(pycurl.CONNECTTIMEOUT, 30)
            self.curl_obj.setopt(pycurl.TCP_KEEPALIVE, 1)
            self.curl_obj.setopt(pycurl.TCP_KEEPIDLE, 30)
            self.curl_obj.setopt(pycurl.TCP_KEEPINTVL, 15)
            self.curl_obj.setopt(pycurl.SSL_VERIFYHOST, 0)
            self.curl_obj.setopt(pycurl.SSL_VERIFYPEER, False)
            self.curl_obj.setopt(pycurl.SSLVERSION, pycurl.SSLVERSION_TLSv1)
            self.curl_obj.setopt(pycurl.HEADER, True)
            self.curl_obj.setopt(pycurl.HTTPHEADER, headers)
            self.curl_obj.setopt(pycurl.POST, 0)
        except:
            if self.debug: self.syslog.syslog(self.syslog.LOG_INFO, "[DEBUG]: Unable to construct Cloudflare HTTP engine object")
            self.syslog.syslog(syslog.LOG_INFO, "[ERROR]: " + tb.format_exc())
            return False

        return True

    def query(self,domain):
        global ksrn_porn
        matches = re.search('^[0-9\.]+$', domain)
        if matches: return 0

        key_to_find = str("KSRN-CLOUDFLARE:" + domain)
        xcache = self.mc_get(key_to_find)

        if xcache is not None:
            self.sysdbg("%s Memory cache [%s]" % (domain, xcache))
            return int(xcache)

        uri_to_test = "https://security.cloudflare-dns.com/dns-query?type=A&name=%s" % domain
        if self.check_porn:
            self.sysdbg("%s Malwares + Adult categories" % domain)
            uri_to_test = "https://family.cloudflare-dns.com/dns-query?type=A&name=%s" % domain

        self.curl_obj.setopt(pycurl.URL, uri_to_test)

        buffer = StringIO()
        self.curl_obj.setopt(self.curl_obj.WRITEDATA, buffer)

        try:
            self.sysdbg("%s Query the cloud repository" % domain)
            self.curl_obj.perform()
        except pycurl.error as exc:
            self.pycurl_error(exc, tb, domain, uri_to_test)
            return 0

        status = int(self.curl_obj.getinfo(pycurl.RESPONSE_CODE))
        resp = buffer.getvalue()

        header_len = self.curl_obj.getinfo(pycurl.HEADER_SIZE)
        header = resp[0: header_len]
        body = resp[header_len:]

        self.sysdbg("Status code %s" % status)

        if status == 503:
            self.syserror("%s receive status code: " + str(status) + " Remote Service Unavailable" % domain)
            return 0

        if status != 200:
            self.syserror("Receive status code: " + str(status) + " headers:" + header)
            return 0

        try:
            json_decoded = json.loads(body)
        except:
            self.syserror("%s L.103 unable to decode answer [%s]" % (domain, body))
            self.syserror(tb.format_exc())
            return 0

        if self.debug:
            if self.output: print(json_decoded)


        if not "Status" in json_decoded:
            self.sysdbg("Err.116; No answer in response")
            return 0

        self.sysdbg("Status [%s]" % int(json_decoded["Status"]))
        if int(json_decoded["Status"]) > 0:
            self.sysdbg("Err.120; Domain %s doesn't exists" % domain)
            self.mc_set(key_to_find,"0",3600)
            return 0


        if not "Answer" in json_decoded:
            self.sysdbg("Err.126; No answer in response")
            return 0

        try:
            result=json_decoded["Answer"][0]["data"]
            self.sysdbg("%s == [%s]" % (domain,result))
        except:
            self.sysdbg(tb.format_exc())
            return 0

        if result=="0.0.0.0":
            self.mc_set(key_to_find, 1, 3600)
            return 1

        self.mc_set(key_to_find, 0, 3600)
        return 0

    def mc_get(self,key):
        if self.mc is None:
            self.sysdbg("Error.113 mc not initialized")
            return None
        try:
            value = self.mc.get(key)
            if value is not None:
                self.sysdbg("HIT %s = [%s]" % (key, value))
                return value
        except:
            self.syserror("Memcache engine return false")
            syserror(tb.format_exc())

        self.sysdbg("%s = NULL" % key)
        return None

    def mc_set(self,key, value, time=0):
        if type(value) is not int:
            if len(value) == 0: return True

        self.sysdbg("mc_set(%s,%s,%s)" % (key, value, time))

        try:
            if time > 0: self.mc.set(key, str(value), time)
            if time == 0: self.mc.set(key, str(value))

        except:
            self.sysdbg("mc_set(%s,%s)" % (key, value))
            return False

        return True


    def sysdbg(self,text):
        if not self.debug: return False
        if self.output: print("[DEBUG]: [CloudFlare] %s" % text)
        self.syslog.syslog(self.syslog.LOG_INFO, "[DEBUG]: [CloudFlare] %s" % text)

    def syserror(self,text):
        if self.output: print("[ERROR]: [CloudFlare] %s" % text)
        self.syslog.syslog(self.syslog.LOG_INFO, "[ERROR]: [CloudFlare] %s" % text)

    def pycurl_error(self,exc, tb, domain, uri_to_test):
        try:
            curlerr = exc[0]
        except:
            self.syserror("pycurl_error: %s Error %s" % (domain, tb.format_exc()))
            return False

        understand_error = False
        if curlerr == 3:
            self.syserror("pycurl_error: %s Unable to reach %s (%s)" % (
                domain, uri_to_test, "CURLE_URL_MALFORMAT"))
            understand_error = True
        if curlerr == 7:
            self.syserror("pycurl_error: %s Unable to reach %s (%s)" % (
                domain, uri_to_test, "Network connection error"))
            understand_error = True

        if not understand_error:  self.syserror("pycurl_error: %s Error code %s %s" % (
            domain, curlerr, tb.format_exc()))
        if understand_error:
            self.sysdbg("pycurl_error: %s Error code %s %s" % (domain, curlerr, tb.format_exc()))
        return True