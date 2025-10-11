#!/usr/bin/env python
import socket
from unix import *
import re
import os.path

class Categories():
    
    def __init__(self):
        self.remote_ip=''
        self.remote_port=0
        self.AsCategoriesAppliance=0
        self.UfdbCatEnabled=0
        self.LocalUfdbCatEnabled=0
        self.SquidPerformance=0
        self.log=object
        self.Debug=False
        self.OuputScreen=False
        self.GetConfig()

        self.MEM={}
        pass

    def GetConfig(self):
        self.SquidPerformance=GET_INFO_INT("SquidPerformance")
        
        IsRemote=self.isRemoteSockets()
        if IsRemote:
            self.remote_ip=GET_INFO_STR("ufdbCatInterface")
            self.remote_port=GET_INFO_INT("ufdbCatPort");
            self.UfdbCatEnabled=1
            return
        
        
        if os.path.exists('/etc/artica-postfix/STATS_APPLIANCE'):
            self.AsCategoriesAppliance=1
            
            
        
        if self.AsCategoriesAppliance ==1:
            self.UfdbCatEnabled=1
            self.LocalUfdbCatEnabled=1
            self.RemoteUfdbCat=1
            self.remote_ip=GET_INFO_STR("ufdbCatInterface")
            self.remote_port=GET_INFO_INT("ufdbCatPort")
            self.UfdbCatEnabled=1
            if not is_valid_ip(self.remote_ip):
                self.remote_ip='127.0.0.1'
            return
        
        if self.SquidPerformance >0:
            if self.OuputScreen: print "GET_CATEGORY: Performance="+str(self.SquidPerformance)+" Disable feature."
            self.UfdbCatEnabled=0
            return
        
        self.UfdbCatEnabled=1
        if len(self.remote_ip) < 4:
            self.remote_ip='127.0.0.1'
        
        if self.remote_port==0:
            self.remote_port=3978


        if self.remote_ip=='127.0.0.1':
            self.verifLocalConfig()
        
        pass
    
    def isRemoteSockets(self):
        self.AsCategoriesAppliance=GET_INFO_INT("AsCategoriesAppliance")
        self.RemoteUfdbCat=GET_INFO_INT("RemoteUfdbCat")
        if self.AsCategoriesAppliance==1:
            return True
        
        if self.RemoteUfdbCat == 1:
            self.remote_ip=GET_INFO_STR("ufdbCatInterface")
            if len(self.remote_ip) > 4:
                return True
        
        return False

    def verifLocalConfig(self):
        if not os.path.exists("/etc/squid3/ufdbGuard.conf"):
            return

        for line in open('/etc/ufdbcat/ufdbGuard.conf', 'r').readlines():
            matches = re.search("^interface\s+(.+)", line)
            if matches:
                self.remote_ip = str(matches.group(1))
                if self.remote_ip == "all": self.remote_ip = "127.0.0.1"

            matches = re.search("^port\s+([0-9]+)", line)
            if matches:
                self.remote_port = int(matches.group(1))
    
#--------------------------------------------------------------------------------------------------------------------------------------------------------    
    def SendToUfdb(self,sitename_ask):
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(10)
        category=""
        response=""

        if self.OuputScreen: print "Connect to %s:%s" %(self.remote_ip,self.remote_port)

        
        try:
            sock.connect((self.remote_ip,self.remote_port))
        except socket.error as msg:
            self.log.info('Categories Error: Unable to connect to '+str(self.remote_ip)+':'+str(self.remote_port)+' ! - '+ str(msg[0]))
            return ''
        
        if self.Debug:
            self.log.info("SEND : http://"+sitename_ask +" 192.168.1.158/- - GET myip=192.168.1.238 myport=3128")
        
        try:
            sock.send("http://"+sitename_ask+" 192.168.1.158/- - GET myip=192.168.1.238 myport=3128\n");
        except socket.error as msg:
            sock.close()
            self.log.info('Categories Error: Unable to connect to '+str(self.remote_ip)+':'+str(self.remote_port)+' ! - '+ str(msg[0]))
            return ''
        
        try:
            response = sock.recv(1024)
            sock.close()
        except socket.error as msg:
            self.log.info('Categories Error: minor error on receive/close socket '+str(self.remote_ip)+':'+str(self.remote_port)+' ! - '+ str(msg[0]))
            if len(response)==0: return ''
            

        if self.Debug: self.log.info('RESPONSE: "'+response+'"')
            
        matches=re.search('\/\/none\/(.*?)\s+',response)
        if matches:
            category=matches.group(1)
            category=unquote(category)
            category=category.replace('"',"")
            sf=re.search('^category_(.+)',category)
            if sf: category=self.tablename_tocat(category)
        
        
        return category
    pass
#--------------------------------------------------------------------------------------------------------------------------------------------------------

    def QuickCategorize(self,sitename):
        if(len(sitename)==0):return None

        computing="science/computing"
        Google="google"
        Schools="recreation/schools"
        cleaning="Cleaning"
        games="games"
        microsoft="microsoft"
        malwares="malware"

        matches = re.search("(^|\.)(msftncsi|microsoft|windows|windowsupdate)\.(com|fr|net)$",sitename)
        if matches: return microsoft

        matches=re.search("(^|\.)(matchware|developpez|samsungcloudprint|github|rawgithub|ugwdevice|mozilla||java|firefox|oracle|ptc|opera|adobedtm)\.(com|fr|net)$",sitename)
        if matches: return computing
        matches = re.search("(^|\.)(lemnia)\.box$",sitename)
        if matches: return computing

        matches = re.search("(^|\.)(giprecia|recia)\.(com|fr|net)$",sitename)
        if matches: return Schools

        matches = re.search("(^|\.)(google|googleapis)\.(com|fr)$",sitename)
        if matches: return Google

        matches = re.search("(^|\.)(avcdn)\.net$",sitename)
        if matches: return cleaning

        matches = re.search("(^|\.)(o10c\.eu)$",sitename)
        if matches: return games

        matches = re.search("(^|\.)(a63t9o1azf|reussissonsensemble)\.(com|fr)$",sitename)
        if matches: return malwares
        return None


    def GET_CATEGORY(self,sitename):
        if len(sitename)==0:return ""
        szcat=self.QuickCategorize(sitename)
        if szcat is not None:
            if self.OuputScreen: print "QuickCategorize: "+szcat
            return szcat


        if self.UfdbCatEnabled==0:
            if self.OuputScreen: print "GET_CATEGORY: disabled: SKIP"
            if self.Debug:
                self.log.info('GET_CATEGORY: disabled: SKIP')
            return ''



        
        sitename_ask=sitename
        if self.Debug:
            self.log.info('GET_CATEGORY: '+str(len(self.MEM))+' elements in memory')
            
        if len(self.MEM) > 5000: self.MEM={}
            
        
        if is_valid_ip(sitename):
            sitename_ask=str(ip2long(sitename))+'.addr'
            
            if self.MEM.has_key(sitename_ask):
                if self.Debug: self.log.info('GET_CATEGORY: memory for '+sitename_ask+' -> '+self.MEM[sitename_ask])
                return self.MEM[sitename_ask]
            
            
            matches=re.search('^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)',sitename)
            if matches:
                CDIR_TO_CHECK=matches.group(1)+'.'+matches.group(2)+'.'+matches.group(3)+'.cdir'
                category=self.SendToUfdb(CDIR_TO_CHECK)
                if len(category)>4:
                    self.MEM[sitename_ask]=category
                    return category
                    
                
        if self.MEM.has_key(sitename_ask):
            if self.Debug: self.log.info('GET_CATEGORY: memory for '+sitename_ask+' -> '+self.MEM[sitename_ask])
            return self.MEM[sitename_ask]
        
        category=self.SendToUfdb(sitename_ask)
        self.MEM[sitename_ask]=category
        return category
    pass
#--------------------------------------------------------------------------------------------------------------------------------------------------------
            
        
        
    def tablename_tocat(self,category):
        TRANS={}
        TRANS["category_society"]="society"
        TRANS["category_association"]="associations"
        TRANS["category_publicite"]="publicite"
        TRANS["category_phishtank"]="phishtank"
        TRANS["category_shopping"]="shopping"
        TRANS["category_abortion"]="abortion"
        TRANS["category_agressive"]="agressive"
        TRANS["category_alcohol"]="alcohol"
        TRANS["category_animals"]="animals"
        TRANS["category_associations"]="associations"
        TRANS["category_astrology"]="astrology"
        TRANS["category_audio_video"]="audio-video"
        TRANS["category_youtube"]="youtube"
        TRANS["category_automobile_bikes"]="automobile/bikes"
        TRANS["category_automobile_boats"]="automobile/boats"
        TRANS["category_automobile_carpool"]="automobile/carpool"
        TRANS["category_automobile_cars"]="automobile/cars"
        TRANS["category_automobile_planes"]="automobile/planes"
        TRANS["category_bicycle"]="bicycle"
        TRANS["category_blog"]="blog"
        TRANS["category_books"]="books"
        TRANS["category_browsersplugins"]="browsersplugins"
        TRANS["category_celebrity"]="celebrity"
        TRANS["category_chat"]="chat"
        TRANS["category_children"]="children"
        TRANS["category_cleaning"]="cleaning"
        TRANS["category_clothing"]="clothing"
        TRANS["category_converters"]="converters"
        TRANS["category_cosmetics"]="cosmetics"
        TRANS["category_culture"]="culture"
        TRANS["category_dangerous_material"]="dangerous_material"
        TRANS["category_dating"]="dating"
        TRANS["category_dictionaries"]="dictionaries"
        TRANS["category_downloads"]="downloads"
        TRANS["category_drugs"]="drugs"
        TRANS["category_dynamic"]="dynamic"
        TRANS["category_electricalapps"]="electricalapps"
        TRANS["category_electronichouse"]="electronichouse"
        TRANS["category_filehosting"]="filehosting"
        TRANS["category_finance_banking"]="finance/banking"
        TRANS["category_finance_insurance"]="finance/insurance"
        TRANS["category_finance_moneylending"]="finance/moneylending"
        TRANS["category_finance_other"]="finance/other"
        TRANS["category_finance_realestate"]="finance/realestate"
        TRANS["category_financial"]="financial"
        TRANS["category_forums"]="forums"
        TRANS["category_gamble"]="gamble"
        TRANS["category_games"]="games"
        TRANS["category_genealogy"]="genealogy"
        TRANS["category_gifts"]="gifts"
        TRANS["category_governements"]="governements"
        TRANS["category_governments"]="governments"
        TRANS["category_green"]="green"
        TRANS["category_hacking"]="hacking"
        TRANS["category_handicap"]="handicap"
        TRANS["category_health"]="health"
        TRANS["category_hobby_arts"]="hobby/arts"
        TRANS["category_hobby_cooking"]="hobby/cooking"
        TRANS["category_hobby_other"]="hobby/other"
        TRANS["category_hobby_pets"]="hobby/pets"
        TRANS["category_paytosurf"]="paytosurf"
        TRANS["category_terrorism"]="terrorism"
        TRANS["category_hobby_fishing"]="hobby/fishing"
        TRANS["category_hospitals"]="hospitals"
        TRANS["category_houseads"]="houseads"
        TRANS["category_housing_accessories"]="housing/accessories"
        TRANS["category_housing_doityourself"]="housing/doityourself"
        TRANS["category_housing_builders"]="housing/builders"
        TRANS["category_humanitarian"]="humanitarian"
        TRANS["category_imagehosting"]="imagehosting"
        TRANS["category_industry"]="industry"
        TRANS["category_internal"]="internal"
        TRANS["category_isp"]="isp"
        TRANS["category_smalladds"]="smalladds"
        TRANS["category_jobsearch"]="jobsearch"
        TRANS["category_jobtraining"]="jobtraining"
        TRANS["category_justice"]="justice"
        TRANS["category_learning"]="learning"
        TRANS["category_liste_bu"]="liste_bu"
        TRANS["category_luxury"]="luxury"
        TRANS["category_mailing"]="mailing"
        TRANS["category_malware"]="malware"
        TRANS["category_manga"]="manga"
        TRANS["category_maps"]="maps"
        TRANS["category_marketingware"]="marketingware"
        TRANS["category_medical"]="medical"
        TRANS["category_mixed_adult"]="mixed_adult"
        TRANS["category_mobile_phone"]="mobile-phone"
        TRANS["category_models"]="models"
        TRANS["category_movies"]="movies"
        TRANS["category_music"]="music"
        TRANS["category_nature"]="nature"
        TRANS["category_news"]="news"
        TRANS["category_passwords"]="passwords"
        TRANS["category_phishing"]="phishing"
        TRANS["category_photo"]="photo"
        TRANS["category_pictureslib"]="pictureslib"
        TRANS["category_politic"]="politic"
        TRANS["category_porn"]="porn"
        TRANS["category_press"]="news"
        TRANS["category_proxy"]="proxy"
        TRANS["category_reaffected"]="reaffected"
        TRANS["category_recreation_humor"]="recreation/humor"
        TRANS["category_recreation_nightout"]="recreation/nightout"
        TRANS["category_recreation_schools"]="recreation/schools"
        TRANS["category_recreation_sports"]="recreation/sports"
        TRANS["category_getmarried"]="getmarried"
        TRANS["category_police"]="police"
        TRANS["category_recreation_travel"]="recreation/travel"
        TRANS["category_recreation_wellness"]="recreation/wellness"
        TRANS["category_redirector"]="redirector"
        TRANS["category_religion"]="religion"
        TRANS["category_remote_control"]="remote-control"
        TRANS["category_sciences"]="sciences"
        TRANS["category_science_astronomy"]="science/astronomy"
        TRANS["category_science_computing"]="science/computing"
        TRANS["category_science_weather"]="science/weather"
        TRANS["category_science_chemistry"]="science/chemistry"
        TRANS["category_searchengines"]="searchengines"
        TRANS["category_sect"]="sect"
        TRANS["category_sexual_education"]="sexual_education"
        TRANS["category_sex_lingerie"]="sex/lingerie"
        TRANS["category_smallads"]="smallads"
        TRANS["category_socialnet"]="socialnet"
        TRANS["category_spyware"]="spyware"
        TRANS["category_sslsites"]="sslsites"
        TRANS["category_stockexchange"]="stockexchange"
        TRANS["category_strict_redirector"]="strict_redirector"
        TRANS["category_strong_redirector"]="strong_redirector"
        TRANS["category_suspicious"]="suspicious"
        TRANS["category_teens"]="teens"
        TRANS["category_tobacco"]="tobacco"
        TRANS["category_tracker"]="tracker"
        TRANS["category_translators"]="translators"
        TRANS["category_transport"]="transport"
        TRANS["category_tricheur"]="tricheur"
        TRANS["category_updatesites"]="updatesites"
        TRANS["category_violence"]="violence"
        TRANS["category_warez"]="warez"
        TRANS["category_weapons"]="weapons"
        TRANS["category_webapps"]="webapps"
        TRANS["category_webmail"]="webmail"
        TRANS["category_webphone"]="webphone"
        TRANS["category_webplugins"]="webplugins"
        TRANS["category_webradio"]="webradio"
        TRANS["category_webtv"]="webtv"
        TRANS["category_wine"]="wine"
        TRANS["category_womanbrand"]="womanbrand"	
        TRANS["category_horses"]="horses"	
        TRANS["category_meetings"]="meetings"	
        TRANS["category_tattooing"]="tattooing"	
        TRANS["category_advertising"]="publicite"	
        TRANS["category_getmarried"]="getmarried"	
        TRANS["category_literature"]="literature"
        TRANS["category_police"]="police"
        TRANS["category_search"]="searchengines"
        
        if TRANS.has_key(category):
            return TRANS[category]
        
        return category