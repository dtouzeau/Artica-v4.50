#!/usr/bin/python -O
# SP 131
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import re
import traceback as tb
from datetime import datetime
from categorizeclass import *
from theshieldsclass import *
from classartmem import *
from unix import *
global debug
import pycurl
from StringIO import StringIO

try:
    from phpserialize import serialize, unserialize
except:
    print("phpserialize not found")




def sendlogs(text):
    print(text)


def SendSocket(remote_ip, remote_port, query, domain=''):
    global debug
    logssrv = "[%s] %s:%s" % (domain, remote_ip, remote_port)
    uri_to_test = "http://%s:%s/query/%s" % (remote_ip, remote_port, query)

    try:

        curl_obj = pycurl.Curl()
        curl_obj.setopt(pycurl.URL, uri_to_test)
        curl_obj.setopt(pycurl.CONNECTTIMEOUT, 30)
        curl_obj.setopt(pycurl.TCP_KEEPALIVE, 1)
        curl_obj.setopt(pycurl.TCP_KEEPIDLE, 30)
        curl_obj.setopt(pycurl.TCP_KEEPINTVL, 15)
        curl_obj.setopt(pycurl.POST, 0)
    except:
        print("Unable to construct Client HTTP engine object [%s]" % tb.format_exc())
        return ""

    buffer = StringIO()
    curl_obj.setopt(curl_obj.WRITEDATA, buffer)


    try:
        curl_obj.perform()
    except pycurl.error as exc:
        pycurl_error(exc, tb, domain, uri_to_test)
        return ""

    status = int(curl_obj.getinfo(pycurl.RESPONSE_CODE))
    response = buffer.getvalue()
    response = response.strip()
    if len(response) == 0: print("TheShields(450) Receive no data from %s" % (logssrv))
    return response


def pycurl_error(exc, tb, domain, uri_to_test):
    try:
        curlerr = exc[0]
    except:
        print("pycurl_error: %s Error %s" % (domain, tb.format_exc()))
        return False

    understand_error = False
    if curlerr == 3:
        print("pycurl_error: %s Unable to reach %s (%s)" % (domain, uri_to_test, "CURLE_URL_MALFORMAT"))
        understand_error = True
    if curlerr == 7:
        print("pycurl_error: %s Unable to reach %s (%s)" % (
            domain, uri_to_test, "Network connection error"))
        understand_error = True

    if not understand_error:  self.syserror("pycurl_error: %s Error code %s %s" % (
        domain, curlerr, tb.format_exc()))
    if understand_error:
        print("pycurl_error: %s Error code %s %s" % (domain, curlerr, tb.format_exc()))
    return True

def cloud_shields(domain):
    catz = categorize()
    TheShieldClass=theshields(catz)
    TheShieldClass.TheShieldsCguard=0
    TheShieldClass.debug=True
    TheShieldClass.OutScreen = True

    TheShieldClass.cloud_t1=True
    Result = TheShieldClass.the_shield_query(domain)
    print("Load-balancer: 137.74.217.146 * * * * * * %s * * * * * *" % Result)

    TheShieldClass.cloud_t2=True
    Result = TheShieldClass.the_shield_query(domain)
    print("DNS: 137.74.217.147 backup: * * * * * * %s * * * * * *" % Result)

    TheShieldClass.cloud_t4=True
    Result = TheShieldClass.the_shield_query(domain)
    print("DNS:37.59.247.71 backup: * * * * * * %s * * * * * *" % Result)

    TheShieldClass.cloud_t3 = True
    Result = TheShieldClass.the_shield_query(domain)
    print("DNS:37.59.247.72 Backup * * * * * * %s * * * * * *" % Result)

def main(argv):
    global debug
    start_time = datetime.now()
    debug=False
    domain = argv[0]
    TheShieldsIP = GET_INFO_STR("TheShieldsIP")
    TheShieldsPORT = GET_INFO_INT("TheShieldsPORT")
    KSRNRemote = GET_INFO_INT("KSRNRemote")
    print("%s]: The Shields query tool v2.8 execute command <%s> Use Remote [%s]" % (domain, argv[0],KSRNRemote))
    catz = categorize()
    if argv[0] == "cloud-shields":
        cloud_shields(argv[1])
        return True;

    if argv[0]=="--bulk":
        if not os.path.exists(argv[1]):
            print("%s no such file" % argv[1])
            return False
        RESPONSE={}
        with open(argv[1], "r") as f:
            for txt in f:
                domain = txt.rstrip('\n')
                domain = domain.rstrip('\r')
                if len(domain) == 0: continue
                category_id = catz.get_category(domain)
                RESPONSE[domain]=category_id
        MAIN = {}
        RESPONSE_TEXT=serialize(RESPONSE)
        MAIN["STATUS"] = 1
        MAIN["RESPONSE"]=RESPONSE_TEXT
        print("<serialized>%s</serialized>\n\n" % serialize(MAIN))
        return True


    if KSRNRemote == 0:
        print("%s]: KSRNRemote is disabled "% domain)
        if argv[0] == "reset":
            pickle_path = "/home/artica/SQLITE/categories.caches.db"
            dbm_cache = "/var/log/squid/categories.dbm"
            if os.path.exists(pickle_path): os.unlink(pickle_path)
            if os.path.exists(dbm_cache): os.unlink(dbm_cache)
            return True

        if argv[0] == "RESET":
            dbm_cache = "/var/log/squid/categories.dbm"
            pickle_path = "/home/artica/SQLITE/categories.caches.db"
            if os.path.exists(pickle_path): os.unlink(pickle_path)
            if os.path.exists(dbm_cache): os.unlink(dbm_cache)
            return True

        zMain={}
        ACTION="ARTICA"
        catz.set_output()
        category_id=catz.get_category(domain)
        end_time = datetime.now()
        time_diff = (end_time - start_time)
        execution_time = time_diff.total_seconds() * 1000
        zMain["result"] = "1"
        zMain["provider"] = ""
        zMain["category"] = category_id
        zMain["time"] = execution_time
        categorystr=catz.category_int_to_string(category_id)
        print("<serialized>%s</serialized>\n\n" % serialize(zMain))
        print("%s: [%s] (%s) (time: %sms)\n" % (domain,ACTION,categorystr, execution_time))
        return True


    if KSRNRemote == 1:
        TheShieldsIP = GET_INFO_STR("KSRNRemoteAddr")
        TheShieldsPORT = GET_INFO_INT("KSRNRemotePort")


    if TheShieldsPORT == 0: TheShieldsPORT = 2004
    if len(TheShieldsIP) == 0: TheShieldsIP = "127.0.0.1"
    server_ip   = TheShieldsIP
    server_port=TheShieldsPORT




    if KSRNRemote == 1:
        print("%s]: Use remote the Shields server" % domain)
        print("%s]: Use The Shields Server adress: %s:%s" % (domain,TheShieldsIP,TheShieldsPORT))



    prepare_data = {}
    prepare_data["sitename"] = domain
    prepare_data["method"] = "GET"

    if argv[0] == "local":
        cat=categorize()
        categoryname=""
        category = cat.get_category_local(argv[1])
        if category > 0 : categoryname=cat.category_int_to_string(category)
        print(argv[1],"=",category,"(",categoryname,")")
        return True

    if argv[0] == "dump-local":
        cat = categorize()
        cat.dump_local(argv[1])
        return True


    if argv[0] == "cloud-t4":
        TheShieldClass.cloud_t4=True
        Result = TheShieldClass.the_shield_query(argv[1])
        print("DNS:37.59.247.71 backup: * * * * * * %s * * * * * *" % Result)




    if argv[0]=="cloud-ts":
        TheShieldClass=theshields(catz)
        TheShieldClass.TheShieldsCguard=0
        TheShieldClass.debug=True
        TheShieldClass.cloud_direct=True
        TheShieldClass.cloud_ts = True
        TheShieldClass.OutScreen=True
        Result = TheShieldClass.the_shield_query(argv[1])
        print("* * * * * * %s * * * * * *" % Result)
        sys.exit(0)


    if argv[0]=="reset":
        pickle_path = "/home/artica/SQLITE/categories.caches.db"
        prepare_data["ACTION"] = "RESET"
        prepare_data_text = serialize(prepare_data)
        data = SendSocket(server_ip, server_port, prepare_data_text)
        print("Reseting The Shield memory done...[%s]" % data)
        sys.exit(0)

    if argv[0]=="RESET":
        prepare_data["ACTION"] = "RESET"
        prepare_data_text = serialize(prepare_data)
        data = SendSocket(server_ip, server_port, prepare_data_text)
        print("Reseting The Shield memory done...[%s]" % data)
        sys.exit(0)

    if argv[0]=="stats":
        mem=art_memcache()
        prepare_data["ACTION"] = "STATS"
        prepare_data_text = serialize(prepare_data)
        data = SendSocket(server_ip, server_port, prepare_data_text)
        print("Stats The Shield of [%s:%s]" % (server_ip,server_port) )
        try:
            main=unserialize(data)
        except:
            print(data)
            sys.exit(0)
        print("Cached items for the Shield Service........: %s" % main["THE_SHIELD_CACHE"])
        print("Cached items for the Categories............: %s" % main["CATEGORIES_CACHE"])
        print("Number of queries..........................: %s" % main["QUERIES"])
        print("Number of Cached queries...................: %s" % main["HITS"])
        sys.exit(0)

    if argv[0]=="logqueries":
        prepare_data["ACTION"] = "LOG-QUERIES"
        prepare_data_text = serialize(prepare_data)
        data = SendSocket(server_ip, server_port, prepare_data_text)
        print("Stats The Shield of [%s:%s]" % (server_ip, server_port))
        print(data)
        sys.exit(0)



    if len(argv) >= 2:

        cmdimp = " ".join(argv)
        if cmdimp.find('--verbose'):
            debug=True
            prepare_data["DEBUG"]="YES"

        if argv[1] == "redis":
            check_redis(domain)
            sys.exit(0)

        if argv[1] == "artica-local":
            white="not a whitelist";
            category_id=catz.get_category(domain)
            if catz.admin_whitelist(domain):white="is a whitelisted site"
            print("%s: Category: %s (%s) %s" % (domain,category_id,catz.category_int_to_string(category_id),white))
            for line in catz.INTERNAL_LOGS: print(line)
            sys.exit(0)



        if argv[1]=="artica":
            prepare_data["ACTION"] = "ARTICA"
            prepare_data_text = serialize(prepare_data)
            data = SendSocket(server_ip, server_port, prepare_data_text)
            result = unserialize(data)
            categoy_name = result["categoy_name"]
            print("Artica categories server report %s is on category [%s]"  % (domain,categoy_name) )
            sys.exit(0)

        if argv[1]=="cguard":
            prepare_data["ACTION"] = "CGUARD"
            prepare_data_text = serialize(prepare_data)
            data = SendSocket(server_ip, server_port, prepare_data_text)
            result = unserialize(data)
            categoy_name = result["categoy_name"]
            print("CGuard categories server report %s is on category [%s]" % (domain, categoy_name))
            sys.exit(0)


    prepare_data["ACTION"] = "THESHIELDS"
    prepare_data_text = serialize(prepare_data)
    data = SendSocket(server_ip, server_port, prepare_data_text)
    end_time = datetime.now()
    try:
        result=unserialize(data)
    except:
        print("unserialize error")
        sys.exit(0)

    error= result["error"]
    categoy_id= result["categoy_id"]
    categoy_name= result["categoy_name"]
    categorystr = categoy_name
    ACTION= result["ACTION"]
    zMain={}
    if(len(error)>0):
        print("%s: [ERROR]\nThe Shields reputation return %s" % (domain, error))
        zMain["ERROR"] = 1
        zMain["result"] = 0
        zMain["description"] = error
        print("<serialized>%s</serialized>\n\n" % serialize(zMain))
        sys.exit(0)

    time_diff = (end_time - start_time)
    execution_time = time_diff.total_seconds() * 1000
    if debug: print("Result: %s" % ACTION)

    zMain["result"] = "1"
    zMain["provider"] = ACTION
    zMain["category"] = categoy_id
    zMain["time"] = execution_time
    print("<serialized>%s</serialized>\n\n" % serialize(zMain))
    print("%s: [%s] (%s) (time: %sms)\n" % (domain,ACTION,categorystr, execution_time))
    sys.exit(0)


if __name__ == "__main__":
   main(sys.argv[1:])