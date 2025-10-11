#!/usr/bin/env python
import sys
import os
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import traceback as tb
import syslog
import logging
import re
from unix import *
from goldlic import *
from classartmem import *
from activedirectoryclass import *
from netaddr import IPNetwork, IPAddress
import time

from datetime import datetime


ITChartVerbose      = 0
whitelist_dom   = set()
whitelist_dst   = set()



def main():
    global          debug
    global          whitelist_dst
    global          ITChartVerbose
    global          InScreen
    global          mem
    pid             = os.getpid()
    debug           = False
    InScreen        = False
    ITChartClusterMaster = ""
    ITChartVerbose  = GET_INFO_INT("ITChartVerbose")
    ITChartClusterEnabled = GET_INFO_INT("ITChartClusterEnabled")
    if ITChartClusterEnabled==1: ITChartClusterMaster = GET_INFO_STR("ITChartClusterMaster")
    SquidUrgency    = GET_INFO_INT("SquidUrgency")
    mem             = art_memcache(ITChartClusterMaster)
    license         = False
    version         = "1.5"
    ITChartVerbose=1
    if ITChartVerbose == 1: debug=True
    gold = isGoldkey()
    if gold.is_corp_license(): license =True
    count_of_lic    = 0
    count_sleep     = 0
    NetworksExcludeLine = mem.redis_get("config.network.exclude")
    RedirectPage=mem.redis_get("config.redirect.url")
    RedirectPage=RedirectPage.lower()

    sendlogs("[INFO]: Proxy itCharter v%s database in [%s] verbose:%s:%s" % (version,ITChartClusterMaster,ITChartVerbose,debug))
    sendlogs("[INFO]: Proxy itCharter Redirected page to %s" % RedirectPage)
    if not license: sendlogs("[ERROR]: Not a Corporate license or a gold license assume all yes")
    if debug:ilog_populate(mem.INTERNAL_LOGS)
    UseSSL=strtoint(mem.redis_get("config.allow.ssl"))
    sendlogs("[INFO]: Proxy itCharter handle SSL protocol:%s" % UseSSL)

    while True:
        concurrency         = 0
        count_of_lic        = count_of_lic + 1
        line                = sys.stdin.readline().strip()
        if debug: sendlogs("[DEBUG]: Receive [%s] count=%s" % (line,count_of_lic))
        if len(line)<2:
            if debug: sendlogs("[DEBUG]: Sleeping 1s "+str(count_sleep)+"/2")
            time.sleep( 1 )
            count_sleep=count_sleep+1
            if count_sleep>0:
                if debug:sendlogs("[DEBUG]: Terminate Process...")
                raise SystemExit(0)
            continue


        if len(line)<3:
            if debug:sendlogs("[DEBUG]: Nothing returned, continue")
            continue

        main_array       = line.split(" ")
        count_of_rows    = len(main_array)
        count_sleep      = 0



        if count_of_lic > 20:
            if not license: license = gold.is_corp_license()
            count_of_lic=0


        try:
            concurrency = int(main_array[0])
            username    = main_array[1]
            ipaddr      = main_array[2]
            mac         = main_array[3]
            xforward    = main_array[4]
            sitename    = main_array[5]
            method      = main_array[6]
            sitename    = sitename.lower()
            if xforward=="-": xforward=""
            if len(xforward)>0: ipaddr=xforward

        except:
            if debug:
                i = 0
                sendlogs("[ERROR]: wrong parameters provided out of bound")
                for line in main_array:
                    sendlogs("[DEBUG]: array of "+str(count_of_rows)+" Collection["+str(i)+ "] = ["+line+"]")
                    i = i + 1
            sys.stdout.write(str(concurrency) + " ERR itchart=ERROR\n")
            sys.stdout.flush()
            continue

        if not license:
            if debug: sendlogs("[DEBUG]: License error, this query return false")
            line_to_send = str(concurrency) + " OK itchart=LICENCE\n"
            sys.stdout.write(line_to_send)
            sys.stdout.flush()
            continue

        if count_of_rows<8:
            if debug: sendlogs("[DEBUG]: FATAL! array of "+str(count_of_rows)+" did not matches the expected rows (8)")
            line_to_send = str(concurrency) + " OK itchart=ERROR\n"
            sys.stdout.write(line_to_send)
            sys.stdout.flush()
            continue

        if SquidUrgency==1:
            if debug: sendlogs("[DEBUG]: WARNING... Emergency Enabled")
            sys.stdout.write(str(concurrency) + " OK itchart=PASS\n")
            sys.stdout.flush()
            continue


        if RedirectPage.find(sitename) > -1:
            if debug: sendlogs("[DEBUG]: %s is the redirected page" % sitename)
            sys.stdout.write(str(concurrency) + " OK itchart=PASS\n")
            sys.stdout.flush()
            continue



        if method=="CONNECT":
            if UseSSL==0:
                if debug: sendlogs("[DEBUG]: [%s] method excluded, PASS" % method)
                sys.stdout.write(str(concurrency) + " OK itchart=PASS\n")
                sys.stdout.flush()
                continue

        if isNetExcluded(NetworksExcludeLine,ipaddr,mac):
            if debug: sendlogs("[DEBUG]: [%s] is excluded, PASS" % ipaddr)
            sys.stdout.write(str(concurrency) + " OK itchart=PASS\n")
            sys.stdout.flush()
            continue


        KeyAccount=FindKeyAccount(username,ipaddr,mac)
        if debug: sendlogs("[DEBUG]: [%s]: username=%s ipaddr=%s mac=%s sitename=%s method=%s" % (KeyAccount,username, ipaddr, mac, sitename, method))
        itcharts_ids = get_itcharts_ids()
        if len(itcharts_ids)==0:
            if debug: sendlogs("[DEBUG]: [%s] PASS: No IT Chart available in configuration" % KeyAccount)
            sys.stdout.write(str(concurrency) + " OK itchart=ERROR\n")
            sys.stdout.flush()
            continue

        block=False
        for id in itcharts_ids:
            keymem="%s|%s" % (KeyAccount,id)
            key_ad="itchart.activedirtectory.%s" % id
            keyToError = base64_encode("%s|%s|%s|%s" % (KeyAccount, id, method, sitename))
            timestamp=strtoint(mem.redis_get(keymem))
            if debug: sendlogs("[DEBUG]: %s Checking ITCharter id [%s] = %s" % (id, KeyAccount,timestamp))
            if timestamp > 0: continue

            if len(username)>0:
                ad_filters=mem.redis_get(key_ad)
                if ad_filters is None: ad_filters=""

                if len(ad_filters)>0:
                    if debug: sendlogs("[DEBUG]: %s Checking ITCharter AD filters [%s] = %s" % (KeyAccount, key_ad, len(ad_filters)))
                    if not check_ad_filters(username,ad_filters):
                        if debug: sendlogs("[DEBUG]: %s Checking  ITCharter id [%s] AD filters NONE, NEXT" % (KeyAccount,id))
                        continue
                    if debug: sendlogs("[DEBUG]: %s Checking ITCharter id [%s] = Active Directory -> ASK %s" % (id, username, keyToError))

            if debug: sendlogs("[DEBUG]: %s Checking ITCharter id [%s] = ASK %s" % (id, KeyAccount, keyToError))
            sys.stdout.write(str(concurrency) + " ERR message=\"%s\" itchart=ASK\n" % keyToError)
            sys.stdout.flush()
            block=True
            break


        if block: continue
        if debug: sendlogs("[DEBUG]: %s -> %s NO RULE MATCHES THIS ACCOUNT" % (KeyAccount,sitename))
        try:
            line_to_send=str(concurrency)+" OK itchart=PASS\n"
            sys.stdout.write(line_to_send)
            sys.stdout.flush()
            continue
        except:
            sendlogs(tb.format_exc())
            sys.stdout.write(str(concurrency)+" OK itchart=ERROR\n")
            sys.stdout.flush()
            continue


def check_ad_filters(username,ad_filters):
    global debug
    AD=ActiveDirectory()
    AD.PrintScreen=True
    username=username.lower()

    if len(ad_filters) == 0: return False
    dn_lists=ad_filters.split("|||")

    for dn in dn_lists:
        if debug: sendlogs("[DEBUG]: %s Checking ITCharter AD group [%s]" % (username, dn))
        connection_id=AD.GetConnectioIDFromDn(dn)

        if connection_id is None:
            if debug: sendlogs("[DEBUG]: %s Error, no Connection ID for [%s]" % (username, dn))
            continue

        if debug: sendlogs("[DEBUG]: %s connection id [%s] " % (username, connection_id))
        Users=AD.GetUsersFromDNGroup(dn,connection_id)
        if len(Users)==0:
            if debug: sendlogs("[DEBUG]: %s [%s] have no user" % (username, dn))
            continue

        if username in Users:
            if debug: sendlogs("[DEBUG]: %s Success!! is a member of [%s]" % (username, dn))
            AD.Close()
            return True

    AD.Close()







    return False


def ilog_populate(the_dic):
    for line in  the_dic:
        sendlogs("[INFO]: %s" % str(line))


def isNetExcluded(NetworksExcludeLine,ipaddr,mac):
    if NetworksExcludeLine is None: return False
    if len(NetworksExcludeLine)<3: return False
    sNets=NetworksExcludeLine.splitlines()
    for cdir in sNets:
        if mac == cdir: return True
        if ipaddr == cdir: return True
        try:
            if IPAddress(ipaddr) in IPNetwork(cdir): return True
        except:
            continue
    return False

def get_itcharts_ids():
    global debug
    global mem
    zarray={}
    data = base64_decode(mem.redis_get("itcharts.ids"))
    if len(data)<3:
        if debug: sendlogs("[DEBUG]: get_itcharts_ids no more data")
        return zarray
    try:
        zarray = unserialize(data)
    except:
        if debug: sendlogs("[DEBUG]: %s" % tb.format_exc)
        return zarray

    if debug: sendlogs("[DEBUG]: get_itcharts_ids array of %s items" % len(zarray))
    return zarray










def log_filename_round(dt=None, dateDelta=timedelta(minutes=1)):
    roundTo = dateDelta.total_seconds()
    if dt is None : dt = datetime.now()
    seconds = (dt - dt.min).seconds
    rounding = (seconds+roundTo/2) // roundTo * roundTo
    return dt + timedelta(0,rounding-seconds,-dt.microsecond)

def log_filename():
    return log_filename_round(datetime.now(),timedelta(minutes=10)).strftime('%Y%m%d%H%M')

def log_filename_time():
    return log_filename_round(datetime.now(), timedelta(minutes=10)).strftime('%Y-%m-%d %H:%M:%S')




def sendlogs(text):
    global ITChartVerbose
    global InScreen
    if InScreen:
        print(text)
        return True
    syslog.openlog("ItCharter", syslog.LOG_PID)
    syslog.syslog(syslog.LOG_INFO,text)
    if ITChartVerbose==0: return True
    LOG_LEVEL = logging.INFO
    logging.basicConfig(format='%(asctime)s [%(levelname)s] [%(process)d] %(message)s %(lineno)d',
                        filename='/var/log/squid/itchart.debug', filemode='a', level=LOG_LEVEL)
    logging.raiseExceptions = False
    logging.info(text)

def FindKeyAccount(username,ipaddr,mac):
    if username=="-": username=""
    if ipaddr=="-":ipaddr=""
    if ipaddr == "127.0.0.1": ipaddr = ""
    if mac=="-":mac=""
    if mac=="00:00:00:00:00:00": mac=""
    if len(username)>3: return username
    if len(mac)>3: return mac
    if len(ipaddr) > 3: return ipaddr

def encrypt_upper(text):
    shift = 3  # defining the shift count
    encryption = ""
    text = text.upper()
    text = text.replace('.','chr2')
    for c in text:
        if c.isupper():
            c_unicode = ord(c)
            c_index = ord(c) - ord("A")
            new_index = (c_index + shift) % 26
            new_unicode = new_index + ord("A")
            new_character = chr(new_unicode)
            encryption = encryption + new_character

        else:
            encryption += c

    return  encryption.lower()
def str2bool(v):
    if v is None: return False
    return v.lower() in ("yes", "true", "t", "1","oui","si")


def check_user_debug(username):
    global debug
    global mem
    global InScreen
    debug =True
    InScreen=True
    ITChartClusterEnabled = GET_INFO_INT("ITChartClusterEnabled")
    ITChartClusterMaster = GET_INFO_STR("ITChartClusterMaster")
    if ITChartClusterEnabled==1: ITChartClusterMaster = GET_INFO_STR("ITChartClusterMaster")
    mem = art_memcache(ITChartClusterMaster)

    itcharts_ids = get_itcharts_ids()
    if len(itcharts_ids) == 0:
        print("itcharts_ids: no chart list..")
        return False

    for id in itcharts_ids:
        key_ad="itchart.activedirtectory.%s" % id
        ad_filters=mem.redis_get(key_ad)
        if ad_filters is None: ad_filters = ""
        print("[DEBUG]: %s Checking ITCharter AD filters [%s] = %s" % (username,key_ad, len(ad_filters)))
        if not check_ad_filters(username,ad_filters): continue





if __name__ == '__main__':
    if len(sys.argv) != 1:
        if(sys.argv[1]=="--adgrp"):
            check_user_debug(sys.argv[2])
            sys.exit(0)

    main()
    sys.exit(0)

