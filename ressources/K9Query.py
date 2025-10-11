#!/usr/bin/env python
import traceback as tb
from StringIO import StringIO
import pycurl,re


class K9Query:
    def __init__(self):
        self.debug          = False
        self.curl_obj       = None
        self.apikey         = "Q8N4T-9PPL4"
        self.curl_obj       = None
        self.curl_status    = 0
        self.errorstr       = ""
        self.error          = False
        self.icat          = 0
        self.zcat           =""
        self.curlobj()

    def query(self,szdomain):
        self.error = False

        if self.curl_obj is None:
            self.error=True
            self.errorstr="Unable to load curl_obj"
            return 0

        zurl = "http://sp.cwfservice.net/1/R/%s/BLUAGENT/0/GET/HTTP/%s/80" % (self.apikey,szdomain)
        #zurl    ="https://sp.cwfservice.net/1/R/%s/K9-00006/0/GET/HTTP/%s/80/" % (self.apikey,szdomain)
        buffer  = StringIO()

        self.curl_obj.setopt(self.curl_obj.WRITEDATA, buffer)
        self.curl_obj.setopt(pycurl.URL, zurl)

        try:
            if self.debug: print("%s ..." % zurl)
            self.curl_obj.perform()

        except pycurl.error as exc:
            self.error = True
            self.errorstr=tb.format_exc()
            return 0

        self.status     = int(self.curl_obj.getinfo(pycurl.RESPONSE_CODE))
        resp            = buffer.getvalue()
        header_len      = self.curl_obj.getinfo(pycurl.HEADER_SIZE)
#        header          = resp[0: header_len]
        body            = resp[header_len:]

        if self.debug: print("Status: %s" % self.status)

        if self.status == 503:
            self.error = True
            self.errorstr =" Status Code 503"
            return 0

        if self.status != 200:
            self.error = True
            self.errorstr="Status Code %s width Error [%s]" % (self.status,body)
            return 0

        if self.debug: print("Body:\n-----------------\n%s\n-----------------\n" % body)
        zcode=0;
        matches = re.search('<Code>([0-9]+)</Code>', body)
        if matches: zcode=str(matches.group(1))
        if self.debug: print("Code: %s" % zcode)

        DirC=""
        matches = re.search('<DirC>([A-Z0-9]+)<\/DirC>', body)
        if matches: DirC = str(matches.group(1))
        if DirC == "25": return 0
        if DirC == "5A": return 0
        self.zcat=DirC

        matches=re.search('<DomC>([A-Z0-9]+)<\/DomC>',body)
        if matches: self.zcat=matches.group(1)
        icat = int(str(self.zcat), 16)

        self.icat=icat

        ayK9Cats={}

        # 3 Pornographie (Pornography)
        ayK9Cats[3]     = 109

        # Suspect (Suspicious) et Pornographie (Pornography)
        ayK9Cats[860]   = 109

        # UnKnown ??
        ayK9Cats[71]  = 0

        # Clips audio/video (Audio/Video Clips) et Contenu mixte/potentiellement destine aux adultes (Mixed Content/Potentially Adult)
        ayK9Cats[12884]= 15

        #Espaces reserves (Placeholders) et Suspect (Suspicious) 
        ayK9Cats[23650] = 105

        # 15723 Contenu informatif (Informational) et Societe/vie quotidienne (Society/Daily Living)
        ayK9Cats[15723] = 3

        # Travel Spectacles et divertissements (Entertainment) NO TRUST
        ayK9Cats[14]    = 119

        ayK9Cats[15]    = 148

        # 18 Phishing
        ayK9Cats[18]    = 105

        # Spectacles et divertissements (Entertainment) NO TRUST
        ayK9Cats[20]    = 119

        # Industry - Affaires/economie (Business/Economy) NO TRUST
        ayK9Cats[21]    = 81



        ayK9Cats[25]    = 45

        # School Educational OK
        ayK9Cats[27]    = 115

        # Jeux (Games)
        ayK9Cats[33] = 58

        # Administration/juridique (Government/Legal) - Governements OK
        ayK9Cats[34] = 62

        # 37 Sante (Health)
        ayK9Cats[37]    = 66

        #38 Technologie/Internet (Technology/Internet)
        ayK9Cats[38]    = 126

        # 40 Moteurs/portails de recherche (Search Engines/Portals)
        ayK9Cats[40] = 129

        # Sources malveillantes/malnets (Malicious Sources/Malnets)
        ayK9Cats[43]    = 92


        ayK9Cats[44]    = 135

        # 54 Religion
        ayK9Cats[54]    = 122

        # 58 SHopping NO TRUST
        ayK9Cats[58] = 8

        # Travel - OK
        ayK9Cats[66]    = 119

        # 88 Publicités web/analyse (Web Ads/Analytics) OK
        ayK9Cats[88]    = 5

        # UnKnown       = 0
        ayK9Cats[90] = 9999

        # 92 - Suspect - Phishing OK
        ayK9Cats[92] = 105

        # 46 Actualites (News) OK
        ayK9Cats[46]    = 103

        # 98 / Espaces reserves (Placeholders) / Reaffected OK
        ayK9Cats[98]    = 112

        # 117 / Cryptomonnaie (Cryptocurrency) / Finance / Other OK
        ayK9Cats[117] = 53


        if icat in ayK9Cats: return ayK9Cats[icat]
        return 0


    def OK_CATZ(self):
        ayK9Cats={}
        ayK9Cats[1]     = True
        ayK9Cats[3]     = True
        ayK9Cats[860]   = True
        ayK9Cats[18]    = True
        ayK9Cats[27]    = True
        ayK9Cats[32]    = True
        ayK9Cats[33]    = True
        ayK9Cats[37]    = True
        ayK9Cats[38]    = True
        ayK9Cats[40]    = True
        ayK9Cats[43]    = True
        ayK9Cats[46]    = True
        ayK9Cats[66]    = True
        ayK9Cats[88]    = True
        ayK9Cats[92]    = True
        ayK9Cats[98]    = True
        ayK9Cats[117]   = True
        ayK9Cats[12884] = True
        ayK9Cats[23650] = True


    def curlobj(self):
        headers=[]
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
            self.curl_obj.setopt(pycurl.USERAGENT, 'ClientLibs Session')
            self.curl_obj.setopt(pycurl.POST, 0)
        except:
            print(tb.format_exc())
            return False
        return True
