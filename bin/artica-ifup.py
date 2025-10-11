import re
import logging,os,getopt,sys
import psutil,time,pyroute2,socket
from pyroute2.netlink import NetlinkError
from pyroute2 import IPDB

def showroutes():

    with IPDB() as ipdb:
        print(ipdb.routes.tables[262])

    sys.exit(0)

    ipr = pyroute2.IPRoute()
    routes = ipr.get_routes(family=socket.AF_INET)
    IP_RULE_TYPES = {0: 'unspecified',
                     1: 'unicast',
                     6: 'blackhole',
                     7: 'unreachable',
                     8: 'prohibit',
                     10: 'nat'}


    for x in routes:
        dst=""
        oif=""
        src=""
        family=x["family"]
        subnets=x["dst_len"]
        event=x["event"]

        for attr in x['attrs']:
            if attr[0] == 'RTA_TABLE': table=attr[1]
            if attr[0] == 'RTA_DST': dst = attr[1]
            if attr[0] == 'RTA_OIF': oif = attr[1]
            if attr[0] == 'RTA_PREFSRC': src = attr[1]

        print(table,":",event," family=",family,"subnets= ",subnets," dst=", dst,' oif=',oif," src=",src)
        print(x)




def create_interface(lease):
    ipr = IPRoute()
    try:
        index = ipr.link_lookup(ifname=lease.interface)[0]
    except IndexError as e:
        logger.error('Interface %s not found, can not set IP.',
                     lease.interface)
    try:
        ipr.addr('add', index, address=lease.address,
                 mask=int(lease.subnet_mask_cidr))
    except NetlinkError as e:
        if ipr.get_addr(index=index)[0].\
                get_attrs('IFA_ADDRESS')[0] == lease.address:
            logger.debug('Interface %s is already set to IP %s' %
                         (lease.interface, lease.address))
        else:
            logger.error(e)
    else:
        logger.debug('Interface %s set to IP %s' %
                     (lease.interface, lease.address))
    try:
        ipr.route('add', dst='0.0.0.0', gateway=lease.router, oif=index)
    except NetlinkError as e:
        if ipr.get_routes(table=254)[0].\
                get_attrs('RTA_GATEWAY')[0] == lease.router:
            logger.debug('Default gateway is already set to %s' %
                         (lease.router))
        else:
            logger.error(e)
    else:
        logger.debug('Default gateway set to %s', lease.router)
    ipr.close()







def main(argv):
    if "--routes" in argv:
        showroutes()
        sys.exit(1)
    if "stop" in argv:
        sbserver_stop()
        sys.exit(1)

    if "restart" in argv:
        sbserver_stop()
        sbserver_start()
        sys.exit(1)
if __name__ == "__main__":
   main(sys.argv[1:])