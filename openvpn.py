#!/usr/bin/env python
# Available Key are: X509_1_OU,common_name,route_gateway_1,ifconfig_remote,untrusted_ip,ifconfig_local,proto_1,tls_serial_1,
# tls_serial_0,tun_mtu,X509_1_emailAddress,tls_id_0,X509_1_L,tls_id_1,X509_1_O,password,script_type,verb,username,local_port_1,config,X509_0_CN,dev,auth_control_file,X509_1_C,X509_1_ST,route_network_1,remote_port_1,PWD,route_net_gateway,daemon,X509_1_name,untrusted_port,SHLVL,script_context,route_vpn_gateway,route_netmask_1,daemon_start_time,X509_0_ST,daemon_pid,X509_1_CN,X509_0_OU,X509_0_emailAddress,daemon_log_redirect,X509_0_C,X509_0_L,link_mtu,X509_0_O=
import sys
import os
import syslog
import base64
import json
import string
import re
import traceback as tb
import hashlib
import requests
sysDaemon = syslog
ApiUri=""

def plugin_up(envp):
    s = str(envp)
    global ApiUri
    global sysDaemon
    sysDaemon = syslog
    sysDaemon.openlog("openvpn", syslog.LOG_PID)
    sysDaemon.syslog(syslog.LOG_INFO, "[PLUGIN]: %s" % "Starting plugin")
    try:
        Proto = "http"
        Address = getkey("ActiveDirectoryRestBindPattern")
        ActiveDirectoryRestSSL = getkey_int("ActiveDirectoryRestSSL")
        if int(ActiveDirectoryRestSSL) == 1:
            Proto = "https"

        ApiUri = "%s://%s" % (Proto, Address)
        sysDaemon.syslog(syslog.LOG_INFO, "[PLUGIN]: RestAPI Address %s" % ApiUri)
    except:
        zLog(tb.format_exc())
    return True

def get_json_data(endpoint):

    url="%s%s" % (ApiUri,endpoint)
    try:
        response = requests.get(url, timeout=10, verify=False)
        if response.status_code == 200:
            try:
                return response.json()
            except:
                zLog("-------------------------------------------------------------------------------")
                zLog("Error.1: Endpoint [%s]" % url)
                zLog("Error.1: Unable to fetch data. Status Code: %s <%s>" % (response.status_code,response))
                zLog("Error.1: <%s>"% tb.format_exc())
                zLog("-------------------------------------------------------------------------------")
        else:
            zLog("Error.2: Unable to fetch data. Status Code: %s %s" % (response.status_code,response))
            return None
    except requests.RequestException as e:
        zLog("-------------------------------------------------------------------------------")
        zLog("Error.3: get_json_data: Error: %s" % e)
        zLog("Error.1: get_json_data: Endpoint [%s]" % url)
        zLog("-------------------------------------------------------------------------------")
        return None
    except:
        zLog(tb.format_exc())
        return None

def base64_encode(s):
    data_bytes = s.encode('utf-8')
    return base64.b64encode(data_bytes)

def plugin_down(envp):
    s = str(envp)
    zLog('Shutdown plugin')
    return True


def zLog(text):
    sysDaemon.syslog(syslog.LOG_INFO, "[PLUGIN]: %s" % text)

def getkey_int(Key):
    if not os.path.exists("/etc/artica-postfix/settings/Daemons/%s" % Key):
        return 0
    with open("/etc/artica-postfix/settings/Daemons/%s" % Key) as f:
        try:
            return int(f.read())
        except:
            return 0

def getkey(Key):
    if os.path.exists("/etc/artica-postfix/settings/Daemons/%s" % Key):
        with open("/etc/artica-postfix/settings/Daemons/%s" % Key) as f:
            return f.read()

def auth_user_pass_verify(envp):
    s = str(envp)
    password = ''
    try:
        password = base64_encode(str(envp["password"]))
        username = base64_encode(str(envp["username"]))
        untrusted_ip = str(envp["untrusted_ip"])
        CommonName = str(envp["common_name"])
        X5090Cn=str(envp["X509_0_CN"])
        if X5090Cn is not None:
            zLog("[AUTH]: X509_0_CN is set, change to %s CommonName=%s" % (X5090Cn,CommonName))
            CommonName =X5090Cn
        else:
            zLog("[AUTH]: X509_0_CN is undef keep %s" % CommonName)

        CommonName=base64_encode(CommonName)

        endpoint="/openvpn/authenticate/%s/%s/%s/%s" % (untrusted_ip,CommonName,username,password)
        data = get_json_data(endpoint)

        if data is None:
            zLog("auth_user_pass_verify: Fatal ! API failed")
        try:
            status = data["Status"]
            if status:
                return True
        except:
            zLog(tb.format_exc())
    except:
        zLog("Verify Authenticate: Fatal error on envp")
        zLog(tb.format_exc())
        return False

    return False


def client_connect(envp):
    s = str(envp)
    untrusted_ip = ''
    try:
        untrusted_ip = str(envp["untrusted_ip"])
        username = base64_encode(str(envp["username"]))
        ifconfig_pool_remote_ip = str(envp["ifconfig_pool_remote_ip"])
        CommonName=str(envp["common_name"])
        ConfigFile=str(envp["client_connect_config_file"])
        if ConfigFile is None:
            ConfigFile="NONE"

        zLog("[CNX]: client_connect_config_file=%s" % (ConfigFile))

        X5090Cn=str(envp["X509_0_CN"])
        if X5090Cn is not None:
            zLog("[CNX]: X509_0_CN is set, change to %s CommonName=%s" % (X5090Cn,CommonName))
            CommonName =X5090Cn
        else:
            zLog("[CNX]: X509_0_CN is undef keep %s" % CommonName)

        CommonName = base64_encode(CommonName)
        ConfigFile = base64_encode(ConfigFile)
        endpoint = "/openvpn/connect/%s/%s/%s/%s/%s" % (untrusted_ip, ifconfig_pool_remote_ip, CommonName, username,ConfigFile)
        data = get_json_data(endpoint)
        try:
            status = data["Status"]
            if status:
                return True
        except:
            zLog(tb.format_exc() )

    except:
        zLog(tb.format_exc())
    return True

def learn_address(envp):
    s=str(envp)
    zLog('learn_address: ' + s)
    return True


def client_disconnect(envp):
    s=str(envp)
    untrusted_ip=''

    untrusted_ip = str(envp["untrusted_ip"])
    username = base64_encode(str(envp["username"]))
    ifconfig_pool_remote_ip = str(envp["ifconfig_pool_remote_ip"])
    CommonName = str(envp["common_name"])

    X5090Cn = str(envp["X509_0_CN"])
    if X5090Cn is not None:
        zLog("[LOGOFF]: X509_0_CN is set, change to %s CommonName=%s" % (X5090Cn, CommonName))
        CommonName = X5090Cn
    else:
        zLog("[LOGOFF]: X509_0_CN is undef keep %s" % CommonName)

    CommonName = base64_encode(CommonName)
    endpoint = "/openvpn/disconnect/%s/%s/%s/%s" % (untrusted_ip, ifconfig_pool_remote_ip, CommonName, username)
    data = get_json_data(endpoint)
    try:
        status = data["Status"]
        if status:
            return True
    except:
        zLog(tb.format_exc())

    return True
