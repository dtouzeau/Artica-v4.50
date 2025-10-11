#!/usr/bin/env python
import pycurl
from StringIO import StringIO
import traceback as tb


class ccurl:
    def __init__(self, url=None,timeout=5):
        self.url=url
        self.timeout=timeout
        self.curl_obj=None
        self.ok=False
        self.buffer = StringIO()
        self.error=""
        self.ok=self.LoadEngine()



    def LoadEngine(self):
        if self.timeout==0: self.timeout=5
        CONNECTTIMEOUT = round(self.timeout / 2)
        if CONNECTTIMEOUT < 2: CONNECTTIMEOUT = 2
        try:
            self.curl_obj = pycurl.Curl()
            self.curl_obj.setopt(pycurl.URL, self.url)
            self.curl_obj.setopt(pycurl.CONNECTTIMEOUT, int(CONNECTTIMEOUT))
            self.curl_obj.setopt(pycurl.TIMEOUT, self.timeout)
            self.curl_obj.setopt(pycurl.TCP_KEEPALIVE, 1)
            self.curl_obj.setopt(pycurl.TCP_KEEPIDLE, 30)
            self.curl_obj.setopt(pycurl.TCP_KEEPINTVL, 15)
            self.curl_obj.setopt(pycurl.POST, 0)
            self.curl_obj.setopt(pycurl.NOPROXY, "*")
            self.curl_obj.setopt(self.curl_obj.WRITEDATA, self.buffer)
        except:
            self.ok=False
            self.error="Unable to construct Client HTTP engine object [%s] L.34" % tb.format_exc()
            return False
        return True

    def get(self,url=""):

        if len(url)>3:
            self.url=url
            if not self.LoadEngine(): return None

        if not self.ok: return None

        try:
            self.curl_obj.perform()
            self.ok = False
        except pycurl.error as exc:
            self.pycurl_error(exc, tb)
            return None

        status = int(self.curl_obj.getinfo(pycurl.RESPONSE_CODE))
        response = self.buffer.getvalue()
        response = response.strip()
        if len(response) == 0:
            self.error = "%s Status code [%s] Receive no data L.59" % (self.url, status)
            self.ok=False
            return None

        self.ok=True
        return response


    def pycurl_error(self,exc, tb):
        try:
            curlerr = exc[0]
        except:
            self.error = "Error <%s> L.69 <%s>" % (self.url, tb.format_exc())
            return False

        understand_error = False
        if curlerr == 3:
            self.error ="%s Error Unable to reach %s L.65" % (self.url, "CURLE_URL_MALFORMAT")
            understand_error = True

        if curlerr == 7:
            self.error = "%s Error Connection refused L.69" % self.url
            return True

        if not understand_error:  self.error = "%s Error code %s <%s> L.437" % (self.url, curlerr, tb.format_exc())
        if understand_error:
            self.error ="%s Error code %s %s L.74" % (self.url, curlerr, tb.format_exc())
        return True

