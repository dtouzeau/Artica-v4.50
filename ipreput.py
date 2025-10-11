#! /usr/bin/env python
# -*- coding: utf-8 -*-
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
from optparse import OptionParser
import requests, re
from phpserialize import serialize, unserialize
import logging
import codecs
from postgressql import *

#python-mysqldb

def setencoding():
    """Set the string encoding used by the Unicode implementation.  The
    default is 'ascii', but if you're willing to experiment, you can
    change this."""
    encoding = "ascii" # Default value set by _PyUnicode_Init()
    if 0:
        # Enable to support locale aware default string encodings.
        import locale
        loc = locale.getdefaultlocale()
        if loc[1]:
            encoding = loc[1]
    if 0:
        # Enable to switch off string to Unicode coercion and implicit
        # Unicode to string conversion.
        encoding = "undefined"
    if encoding != "ascii":
        # On Non-Unicode builds this will raise an AttributeError...
        sys.setdefaultencoding(encoding) # Needs Python Unicode build !


def main(search_string):
    sql_error=""
    logger.info("[%s]" % search_string)
    r_geo=requests.get('https://api.ipdata.co/%s?api-key=6d08d6613aa483df1e3100b13960991ee15c9bec8bb805a5b91583e0' % search_string,
                       headers={

                           'User-Agent': 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:64.0) Gecko/20100101 Firefox/64.0'
                       }).json()

    if not 'is_eu' in r_geo:
        if "message" in r_geo: sql_error=r_geo["message"]
        print "main(): api.ipdata.co failed ("+sql_error+")"
        data = { "FAILED":1,"MESSAGE":sql_error}
        return data

    try:
        r_details = requests.get('https://talosintelligence.com/sb_api/query_lookup',
                             headers={
                                 'Referer': 'https://talosintelligence.com/reputation_center/lookup?search=%s' % search_string,
                                 'User-Agent': 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:64.0) Gecko/20100101 Firefox/64.0'
                             },
                             params={
                                 'query': '/api/v2/details/ip/',
                                 'query_entry': search_string
                             }).json()
    except:
        logger.info(type(r_details))
        data = {"FAILED": 1, "MESSAGE": "talosintelligence.com json error"}
        return data




    r_wscore = requests.get('https://talosintelligence.com/sb_api/remote_lookup',
                            headers={
                                'Referer': 'https://talosintelligence.com/reputation_center/lookup?search=%s' % search_string,
                                'User-Agent': 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:64.0) Gecko/20100101 Firefox/64.0'
                            },
                            params={'hostname': 'SDS',
                                    'query_string': '/score/wbrs/json?url=%s' % search_string}).json()

    r_talos_blacklist = requests.get('https://www.talosintelligence.com/sb_api/blacklist_lookup',
                                     headers={
                                         'Referer': 'https://talosintelligence.com/reputation_center/lookup?search=%s' % search_string,
                                         'User-Agent': 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:64.0) Gecko/20100101 Firefox/64.0'
                                     },
                                     params={'query_type': 'ipaddr', 'query_entry': search_string}).json()







    Blacklisted_count=0;
    if "blacklists" in r_details:
        for blk in r_details["blacklists"]:
            Blacklisted_count=Blacklisted_count+1



    talos_blacklisted = {'status': False}
    if 'classifications' in r_talos_blacklist['entry']:
        talos_blacklisted['status'] = True
        talos_blacklisted['classifications'] = ", ".join(r_talos_blacklist['entry']['classifications'])
        talos_blacklisted['first_seen'] = r_talos_blacklist['entry']['first_seen'] + "UTC"
        talos_blacklisted['expiration'] = r_talos_blacklist['entry']['expiration'] + "UTC"

    data = {
        'is_eu': "1" if r_geo['is_eu']  else "0",
        "city": r_geo['city'] if 'city' in r_geo else "",
        "region":r_geo['region'] if 'region' in r_geo else "",
        "region_code": r_geo['region_code'] if 'region_code' in r_geo else "",
        "country_name": r_geo['country_name'] if 'country_name' in r_geo else "",
        "country_code": r_geo['country_code'] if 'country_code' in r_geo else "",
        "continent_name": r_geo['continent_name'] if 'continent_name' in r_geo else "",
        "continent_code": r_geo['continent_code'] if 'continent_code' in r_geo else "",
        "emoji_unicode": r_geo['emoji_unicode'] if 'emoji_unicode' in r_geo else "",
        'address': search_string,
        'organization': r_details['organization'] if 'organization' in r_details else "",
        'hostname': r_details['hostname'] if 'hostname' in r_details else "",
        'volume_change': r_details['daychange'] if 'daychange' in r_details else "",
        'lastday_volume': r_details['daily_mag'] if 'daily_mag' in r_details else "",
        'month_volume': r_details['monthly_mag'] if 'monthly_mag' in r_details else "",
        'email_reputation': r_details['email_score_name'] if 'email_score_name' in r_details else "",
        'web_reputation': r_details['web_score_name'] if 'web_score_name' in r_details else "",
        'weighted_reputation_score': r_wscore['response'],
        'talos_blacklisted': "1" if talos_blacklisted['status'] else "0",
        'count_of_blacklists': Blacklisted_count
        # 'weighted_reputation_score':r_wscore[0]['response']['wbrs']['score'],
        # 'volumes':zip(*r_volume['data'])
    }

    return data

pidfile_path = '/var/run/ipreput.pid'
logger = logging.getLogger("DaemonLog")
logger.setLevel(logging.INFO)
formatter = logging.Formatter("[%(asctime)s]: %(message)s")
handler = logging.FileHandler("/var/log/iptrack.log")
handler.setFormatter(formatter)
logger.addHandler(handler)
POSTGRES=Postgres()
POSTGRES.log=logger

if is_running_from_pidpath(pidfile_path):
    print "Already running, aborting"
    exit

UTF8Writer = codecs.getwriter('utf8')
pid = os.getpid()
file_put_contents(pidfile_path,pid)
setencoding()
sql = "SELECT ipaddr FROM ip_reputation WHERE is_parsed=0"
rows = POSTGRES.QUERY_SQL(sql)
for row in rows:
    ipaddr=row[0]
    fields=[]
    data=main(ipaddr)
    print data

    if 'MESSAGE' in data:
        matches = re.search("private IP address", data["MESSAGE"].encode("utf8"))
        if matches:
            print ipaddr+" is a private IP address"
            sql = "UPDATE ip_reputation SET is_parsed=1"
            POSTGRES.QUERY_SQL(sql)
            continue

        print " __ MAIN __ Failed for "+ipaddr+" ("+data["MESSAGE"].encode("utf8")+")"
        logger.info("Failed for "+ipaddr+" ("+data["MESSAGE"]+")")
        sys.exit(0)

    city=data["city"].replace("'", "`")
    city.encode('utf8')

    region=data["region"].replace("'", "`")
    region.encode('utf8')




    country_name=data["country_name"].replace("'", "`")
    country_name.encode('utf8')



    organization=data["organization"].replace("'", "`")
    organization.encode('utf8')


    print "ipaddr: "+ipaddr

    fields.append("is_parsed=1")
    fields.append("is_eu='%s'" % str(data["is_eu"]))
    fields.append("city='%s'" % city)
    fields.append("region='%s'" % region)
    fields.append("region_code='%s'" % str(data["region_code"]))
    fields.append("country_name='%s'" % country_name)
    fields.append("country_code='%s'" % str(data["country_code"]))
    fields.append("continent_name='%s'" % str(data["continent_name"]))
    fields.append("continent_code='%s'" % str(data["continent_code"]))
    fields.append("emoji_unicode='%s'" % str(data["emoji_unicode"]))
    fields.append("organization='%s'" % organization)
    fields.append("hostname='%s'" % str(data["hostname"]))
    fields.append("email_reputation='%s'" % str(data["email_reputation"]))
    fields.append("web_reputation='%s'" % str(data["web_reputation"]))
    fields.append("weighted_reputation_score='%s'" % str(data["weighted_reputation_score"]))
    fields.append("talos_blacklisted='%s'" % str(data["talos_blacklisted"]))
    fields.append("count_of_blacklists='%s'" % str(data["count_of_blacklists"]))

    sqlAdd=",".join(fields)

    sql="UPDATE ip_reputation SET "+sqlAdd+" WHERE ipaddr='"+ipaddr+"'"
    POSTGRES.QUERY_SQL(sql)
    if not POSTGRES.ok:
        print POSTGRES.sql_error
        sys.exit(0)
