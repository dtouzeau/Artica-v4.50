//package itchart
//
//import (
//	"encoding/base64"
//	"fmt"
//	"github.com/techoner/gophp"
//	"log"
//	"net/netip"
//	"server/internal"
//	"strconv"
//	"strings"
//)
//
//var (
//	isDebugITChart          bool
//	SelfITChartMessage      string
//	SelfITChartSitename     string
//	SelfITChartRedirectPage string
//	SelfNetworksExcludeLine string
//)
//
//func IntITChart(debug bool) {
//	isDebugITChart = debug
//	SelfITChartRedirectPage = internal.GetSocketInfoString("ITChartRedirectURL")
//	SelfITChartRedirectPage = strings.ToLower(SelfITChartRedirectPage)
//	SelfNetworksExcludeLine = internal.GetSocketInfoString("ITChartNetworkExclude")
//}
//
//func ChartThis(ipaddr, mac, username, protocol, sitename string) bool {
//	SelfITChartSitename = sitename
//	if strings.Index(SelfITChartRedirectPage, sitename) > -1 {
//		if isDebugITChart {
//			log.Printf("%s: [DEBUG]: <%s> is the redirected page, no sense to block it", ipaddr, sitename)
//		}
//		return false
//	}
//	if protocol == "CONNECT" {
//		if isDebugITChart {
//			log.Printf("%s: %s: [DEBUG] [%s] method excluded, PASS", ipaddr, sitename, protocol)
//		}
//		return false
//	}
//	if isNetExcluded(SelfNetworksExcludeLine, ipaddr, mac) {
//		log.Printf("%s: [DEBUG] isNetExcluded report True, PASS", ipaddr)
//		return false
//	}
//	keyAccount := findKeyAccount(username, ipaddr, mac)
//	if isDebugITChart {
//		log.Printf("%s: [DEBUG] [%s]: username=%s ipaddr=%s mac=%s sitename=%s method=%s ->get_itcharts_ids()", sitename, keyAccount, username, ipaddr, mac, sitename, protocol)
//	}
//
//	itChartIDs := getITChartIDs()
//	if len(itChartIDs) == 0 {
//		if isDebugITChart {
//			log.Printf("%s: [DEBUG] [%s] PASS: No IT Chart available in configuration", sitename, keyAccount)
//		}
//		return false
//	}
//	for id, _ := range itChartIDs {
//		keyMem := fmt.Sprintf("%s|%s", keyAccount, id)
//		keyAD := fmt.Sprintf("itchart.activedirtectory.%s", id)
//		keyToError := base64.StdEncoding.EncodeToString([]byte(fmt.Sprintf("%s|%s|%s|%s", keyAccount, id, protocol, sitename)))
//		timestamp, _ := strconv.Atoi(internal.RedisGet(keyMem))
//		if timestamp > 0 {
//			continue
//		}
//		if len(username) > 0 {
//			adFilters := internal.RedisGet(keyAD)
//			if len(adFilters) > 0 {
//				if isDebugITChart {
//					log.Printf("%s: [DEBUG] Checking ITCharter AD filters [%s] = %s", keyAccount, keyAD, len(adFilters))
//				}
//			}
//
//		}
//
//	}
//	return false
//}
//
//func getITChartIDs() map[string]bool {
//	//TODO finish it
//	results := map[string]bool{}
//	data, _ := base64.StdEncoding.DecodeString(internal.RedisGet("itcharts.ids"))
//	if len(data) < 3 {
//		if isDebugITChart {
//			log.Printf("%s: [DEBUG]: get_itcharts_ids no more data [SKIP]", SelfITChartSitename)
//		}
//		return results
//	}
//	out, err := gophp.Unserialize([]byte(data))
//	if err != nil {
//		if isDebugITChart {
//			log.Printf("error Unserialize itcharts.ids: ", err)
//		}
//		return results
//	}
//	if mout, ok := out.(map[string]interface{}); ok {
//		for x, data := range mout {
//			results[x] = data.(bool)
//		}
//	} else {
//		if isDebugITChart {
//			log.Printf("%s: [DEBUG] ERROR <%s>", SelfITChartSitename, err)
//		}
//		return results
//	}
//	if isDebugITChart {
//		log.Printf("%s: [DEBUG]: get_itcharts_ids array of %d items", SelfITChartSitename, len(results))
//	}
//	return results
//}
//
//func findKeyAccount(username, ipaddr, mac string) string {
//	if username == "-" {
//		username = ""
//	}
//	if ipaddr == "-" {
//		ipaddr = ""
//	}
//	if ipaddr == "127.0.0.1" {
//		ipaddr = ""
//	}
//	if mac == "-" {
//		mac = ""
//	}
//	if mac == "00:00:00:00:00:00" {
//		mac = ""
//	}
//	if len(username) > 3 {
//		return username
//	}
//	if len(mac) > 3 {
//		return mac
//	}
//	if len(ipaddr) > 3 {
//		return ipaddr
//	}
//	return ""
//}
//
//func isNetExcluded(network, ipaddr, mac string) bool {
//	if network == "" {
//		if isDebugITChart {
//			log.Printf("%s: [DEBUG]: isNetExcluded None, aborting", SelfITChartSitename)
//		}
//		return false
//	}
//	if len(network) < 3 {
//		if isDebugITChart {
//			log.Printf("%s: [DEBUG]: isNetExcluded Not configured, aborting", SelfITChartSitename)
//		}
//		return false
//	}
//	net := strings.Split(network, "\n")
//	for _, cdir := range net {
//		if isDebugITChart {
//			log.Printf("%s: [DEBUG]: isNetExcluded checking %s against %s %s", SelfITChartSitename, cdir, ipaddr, mac)
//		}
//		if mac == cdir {
//			return true
//		}
//		if ipaddr == cdir {
//			return true
//		}
//		network, err := netip.ParsePrefix(cdir)
//		if err != nil {
//			panic(err)
//		}
//		ip, err := netip.ParseAddr(ipaddr)
//		if err != nil {
//			panic(err)
//		}
//		if network.Contains(ip) {
//			if isDebugITChart {
//				log.Printf("%s: [DEBUG]: isNetExcluded checking %s matches %s", SelfITChartSitename, cdir, ipaddr)
//			}
//			return true
//		}
//	}
//	return false
//}
