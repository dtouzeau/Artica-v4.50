package ufdbguard

import (
	"bufio"
	"encoding/binary"
	"errors"
	"fmt"
	"log"
	"net"
	"net/url"
	"os"
	"regexp"
	"server/categorization"
	"server/internal"
	"strconv"
	"strings"
	"time"
)

var (
	isDebugUfdbguard                   bool
	SelfUfdbGuardMaxUrisize            int
	SelfFinalRedirdectCode             int
	SelfFinalRedirectUrl               string
	SelfRedirectKey                    string
	SelfProxyProto                     string
	SelfClientMac                      string
	SelfMimik                          bool
	SelfWebfilterRuleName              string
	SelfIsSSNI                         bool
	SelfRemoteIP                       string
	SelfRemotePort                     int
	SelfUseRemoteUfdbguardService      bool
	SelfSquidGuardRedirectHTTPCode     int
	SelfSquidGuardWebUseExternalUri    int
	SelfSquidGuardWebExternalUri       string
	SelfSquidGuardWebExternalUriSSL    string
	SelfHttps                          bool
	SelfUfdbGuardWebFilteringCacheTime int
	SelfCached                         bool
	SelfInactiveService                bool
	SelfUfdbgclientSockTimeOut         time.Duration
	SelfCategory                       int
	SelfWebfilteringToken              string
	SelfRuleID                         int
	SelfCategoryName                   string
	SelfToken                          string
)

func InitUfdbguard(debug bool) {
	//Self := &UFDBDefaultVars{}
	//log.SetFlags(log.LstdFlags | log.Lshortfile)
	//if _slog, err := syslog.New(syslog.LOG_DEBUG, "go-shield-connector-ufdb"); err == nil {
	//	log.SetOutput(_slog)
	//}
	SelfRemoteIP = internal.GetSocketInfoString("PythonUfdbServer")
	SelfRemotePort = internal.GetSocketInfoInt("PythonUfdbPort")
	SelfUseRemoteUfdbguardService = internal.GetSocketInfoBool("UseRemoteUfdbguardService")
	SelfSquidGuardRedirectHTTPCode = internal.GetSocketInfoInt("SquidGuardRedirectHTTPCode")
	SelfUfdbGuardMaxUrisize = internal.GetSocketInfoInt("UfdbGuardMaxUrisize")
	SelfSquidGuardWebUseExternalUri = internal.GetSocketInfoInt("SquidGuardWebUseExternalUri")
	SelfSquidGuardWebExternalUri = internal.GetSocketInfoString("SquidGuardWebExternalUri")
	SelfSquidGuardWebExternalUriSSL = internal.GetSocketInfoString("SquidGuardWebExternalUriSSL")
	SelfUfdbGuardWebFilteringCacheTime = internal.GetSocketInfoInt("UfdbGuardWebFilteringCacheTime")
	SelfUfdbgclientSockTimeOut = time.Duration(internal.GetSocketInfoInt("UfdbgclientSockTimeOut")) * time.Second
	if SelfUfdbgclientSockTimeOut == 0 {
		SelfUfdbgclientSockTimeOut = time.Duration(120) * time.Second
	}
	if SelfUfdbGuardWebFilteringCacheTime == 0 {
		SelfUfdbGuardWebFilteringCacheTime = 300
	}
	if SelfSquidGuardRedirectHTTPCode < 300 {
		SelfSquidGuardRedirectHTTPCode = 302
	}
	if SelfUfdbGuardMaxUrisize == 0 {
		SelfUfdbGuardMaxUrisize = 640
	}

	isDebugUfdbguard = debug

	isBumped()
	if !SelfUseRemoteUfdbguardService {
		checkLocalConfig()
		if SelfRemoteIP == "all" {
			if isDebugUfdbguard {
				log.Printf("[UFDB_CLASS]: Warning, unable to found the remote TCP Addr config, assume 127.0.0.1")
			}
			SelfRemoteIP = "127.0.0.1"
		}
		if len(SelfRemoteIP) == 0 {
			if isDebugUfdbguard {
				log.Printf("[UFDB_CLASS]: Warning, unable to found the remote TCP Addr config, assume 127.0.0.1")
			}
			SelfRemoteIP = "127.0.0.1"
		}
		if SelfRemotePort == 0 {
			if isDebugUfdbguard {
				log.Printf("[UFDB_CLASS]: Warning, unable to found the remote port config, assume 3977 port")
			}
			SelfRemotePort = 3977
		}
	}
	if SelfRemotePort > 0 {
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: Redirect Code..............: %d", SelfSquidGuardRedirectHTTPCode)
			log.Printf("[UFDB_CLASS]: Connect to.................: ufdb://%s:%d", SelfRemoteIP, SelfRemotePort)
			log.Printf("[UFDB_CLASS]: SelfSquidGuardWebUseExternalUri: %d", SelfSquidGuardWebUseExternalUri)
			log.Printf("[UFDB_CLASS]: SelfSquidGuardWebExternalUri...: %s", SelfSquidGuardWebExternalUri)
			log.Printf("[UFDB_CLASS]: SelfSquidGuardWebExternalUriSSL: %s", SelfSquidGuardWebExternalUriSSL)
			log.Printf("[UFDB_CLASS]: Listen port: %s:%d", SelfRemoteIP, SelfRemotePort)
			if SelfMimik {
				log.Printf("[UFDB_CLASS]:MIMIK = %t", SelfMimik)
			}
		}
	}

}
func isBumped() {
	if _, err := os.Stat("/etc/squid3/listen_ports.conf"); errors.Is(err, os.ErrNotExist) {
		if isDebugUfdbguard {
			log.Printf("/etc/squid3/listen_ports.conf not exit: ", err)
		}
		return
	}
	f, err := os.Open("/etc/squid3/listen_ports.conf")

	if err != nil {
		if isDebugUfdbguard {
			log.Printf("error reading /etc/squid3/listen_ports.conf: ", err)
		}
		return
	}

	defer f.Close()
	scanner := bufio.NewScanner(f)
	for scanner.Scan() {
		reSSLBump := regexp.MustCompile("^http_port.*?ssl-bump")
		if reSSLBump.MatchString(scanner.Text()) {
			if isDebugUfdbguard {
				log.Printf("[UFDB_CLASS]:MIMIK Found SSL port in proxy configuration")
			}
			SelfMimik = true
			return
		}
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]:MIMIK * not * Found SSL port in proxy configuration")
		}

	}
	if err := scanner.Err(); err != nil {
		if isDebugUfdbguard {
			log.Printf("error scanning /etc/squid3/listen_ports.conf: ", err)
		}
		return
	}
}
func checkLocalConfig() {
	if _, err := os.Stat("/etc/squid3/ufdbGuard.conf"); errors.Is(err, os.ErrNotExist) {
		if isDebugUfdbguard {
			log.Printf("/etc/squid3/ufdbGuard.conf not exit: ", err)
		}
		return
	}
	f, err := os.Open("/etc/squid3/ufdbGuard.conf")

	if err != nil {
		if isDebugUfdbguard {
			log.Printf("error reading /etc/squid3/ufdbGuard.conf: ", err)
		}
		return
	}

	defer f.Close()
	scanner := bufio.NewScanner(f)
	for scanner.Scan() {
		reInterface := regexp.MustCompile("^interface\\s+(.+)")
		if reInterface.MatchString(scanner.Text()) {
			rs := reInterface.FindStringSubmatch(scanner.Text())
			if isDebugUfdbguard {
				log.Printf("[UFDB_CLASS]: Found Interface %s in ufdbGuard.conf", rs[1])
			}
			SelfRemoteIP = rs[1]
			if SelfRemoteIP == "all" {
				SelfRemoteIP = "127.0.0.1"
			}
		}
		rePort := regexp.MustCompile("^port\\s+([0-9]+)")
		if rePort.MatchString(scanner.Text()) {
			rs := rePort.FindStringSubmatch(scanner.Text())
			if isDebugUfdbguard {
				log.Printf("[UFDB_CLASS]: Found Port %s in ufdbGuard.conf", rs[1])
			}
			SelfRemotePort, _ = strconv.Atoi(rs[1])
		}

	}
	if err := scanner.Err(); err != nil {
		if isDebugUfdbguard {
			log.Printf("error scanning /etc/squid3/ufdbGuard.conf: ", err)
		}
		return
	}

}
func Process(proxyUrl string, urlDomain string, clientIp string, clientHostname string, username string, clientMacAddr string, proxyIP string, proxyPort int) bool {
	if isDebugUfdbguard {
		log.Printf("Starting UFDB Process")
	}
	sourceUrl := proxyUrl
	categoryName := "Unknown"
	toUfdbCdir := ""
	toUfdb := ""
	proto := ""
	cdirToCheck := ""
	SelfCategoryName = categoryName
	SelfToken = ""
	var Tokens []string
	if len(username) == 0 {
		username = "-"
	}
	if len(clientHostname) == 0 {
		clientHostname = "-"
	}
	if len(proxyIP) == 0 {
		proxyIP = "127.0.0.1"
	}
	if proxyPort == 0 {
		proxyPort = 3128
	}

	matches, _ := regexp.MatchString("\\/ufdbguard\\.php\\?rule-id=[0-9]+", proxyUrl)
	if matches {
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: [CLIENT]: Loop to Web-filtering error page")
		}
		return false
	}

	if checkIPAddressType(urlDomain, isDebugUfdbguard) {
		re := regexp.MustCompile("^([0-9]+)\\.([0-9]+)\\.([0-9]+)\\.([0-9]+)")
		if re.MatchString(urlDomain) {
			rs := re.FindStringSubmatch(urlDomain)
			cdirToCheck := rs[1] + "." + rs[2] + "." + rs[3] + ".cdir"
			toUfdbCdir = fmt.Sprintf("http://%s %s/%s %s GET myip=%s myport=%s\n", cdirToCheck, clientIp, clientHostname, username, proxyIP, strconv.Itoa(proxyPort))
		}
		ip2LongDomain := binary.BigEndian.Uint32(net.ParseIP(urlDomain)[12:16])
		newDomain := strconv.FormatUint(uint64(ip2LongDomain), 10) + ".addr"
		proxyUrl = strings.ReplaceAll(proxyUrl, urlDomain, newDomain)
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: [CLIENT] replace [' + %s + '] to [' + %s + ']:", urlDomain, newDomain)
		}
		SelfProxyProto = "GET"
	}
	proxyUrl = strings.ReplaceAll(proxyUrl, "https", "http")
	proxyUrl = strings.ReplaceAll(proxyUrl, ":443", "")
	if strings.Index(proxyUrl, "http://") == -1 {
		proto = "http://"
	}
	if SelfUfdbGuardMaxUrisize == 0 {
		SelfUfdbGuardMaxUrisize = 640
	}
	if len(proxyUrl) > SelfUfdbGuardMaxUrisize {
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: [CLIENT] ALERT!...: URL %s exceed %d bytes, cut it!", urlDomain, SelfUfdbGuardMaxUrisize)
		}
		proxyUrl = proxyUrl[0:SelfUfdbGuardMaxUrisize] + "..."
	}
	if len(toUfdbCdir) > 0 {
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: [CLIENT] Pass to Web-Filtering service (CDIR)")
		}
		SelfClientMac = clientMacAddr
		if sendToUfdb(toUfdbCdir, sourceUrl, clientIp, username, cdirToCheck) {
			categoryName = "Unknown"
			finalRedirdectCode := SelfFinalRedirdectCode
			finalRedirectUrl := SelfFinalRedirectUrl
			finalRedirectKey := SelfRedirectKey
			category := SelfCategory
			if category > 0 {
				categoryName = categorization.CategoryIntToString(category)
				SelfCategoryName = categoryName
			}
			if isDebugUfdbguard {
				log.Printf("[UFDB_CLASS]: SelfCategory %d Name: %s", category, categoryName)
				log.Printf("[UFDB_CLASS]: [CLIENT] CATEGORY=%d", SelfCategory)
			}
			ap1 := fmt.Sprintf("status=%s %s=%s", finalRedirdectCode, finalRedirectKey, finalRedirectUrl)
			Tokens = append(Tokens, ap1)
			Tokens = append(Tokens, "shieldsblock=yes")
			Tokens = append(Tokens, SelfWebfilteringToken)
			ap2 := fmt.Sprintf("category=%s category-name=%s clog=cinfo:%s-%s;", strconv.Itoa(category), categoryName, strconv.Itoa(category), categoryName)
			Tokens = append(Tokens, ap2)
			SelfToken = strings.Join(Tokens, " ")
			return true

		}
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: FATAL! Exception while requesting CDIR to Web-Filtering Engine service")
		}
		return false
	}
	toUfdb = fmt.Sprintf("%s%s %s/%s %s GET myip=%s myport=%s\n", proto, proxyUrl, clientIp, clientHostname, username, proxyIP, strconv.Itoa(proxyPort))

	if sendToUfdb(toUfdb, sourceUrl, clientIp, username, clientHostname) {
		category := SelfCategory
		if category > 0 {
			categoryName = categorization.CategoryIntToString(category)
			SelfCategoryName = categoryName
		}
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: SelfCategory %d Name: %s", category, categoryName)
		}
		return true
	}
	if isDebugUfdbguard {
		log.Printf("[UFDB_CLASS]: FATAL! Exception while requesting Web-Filtering Engine service")
	}
	return false
}

func sendToUfdb(query string, sourceUrl string, clientIp string, uid string, hostname string) bool {
	if isDebugUfdbguard {
		log.Printf("Starting SentToUfdb")
	}
	connected := false
	redirection := ""
	key := "url"
	categoryFound := ""
	SelfRuleID = 0
	SelfWebfilterRuleName = ""
	response := ""
	var matches bool

	matches, _ = regexp.MatchString("\\s+CONNECT\\s+", query)
	if matches {
		connected = true
	}
	if SelfProxyProto == "CONNECT" {
		connected = true
	}
	matches, _ = regexp.MatchString("^([0-9\\.]+)$", hostname)
	if !matches {
		SelfIsSSNI = true
	}
	//To Improve Later
	if SelfMimik {
		if SelfHttps {
			if !SelfIsSSNI {
				if isDebugUfdbguard {
					log.Printf("[UFDB_CLASS]: %s: MIMIK but SNI not set, return false", hostname)
					log.Printf("[UFDB_CLASS]: OK: PASS")
					return false
				}
			}
		}
	}
	if connected {
		if SelfMimik {
			matches, _ = regexp.MatchString("^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])$", hostname)
			if matches {
				if isDebugUfdbguard {
					log.Printf("[UFDB_CLASS]: [%s]:IPv4 Connect received, but mimiked proxy, waiting bumped session..", hostname)
					log.Printf("[UFDB_CLASS]: OK: PASS")
				}
				return false
			}
			matches, _ = regexp.MatchString("^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])$", hostname)
			if matches {
				if isDebugUfdbguard {
					log.Printf("[UFDB_CLASS]: [%s]:IPv6 Connect received, but mimiked proxy, waiting bumped session..", hostname)
					log.Printf("[UFDB_CLASS]: OK: PASS")
				}
				return false
			}
		}
	}
	if isDebugUfdbguard {
		log.Printf("[UFDB_CLASS][222]: query: [%s]", strings.TrimSpace(query))
	}
	SelfCached = false
	md5query := internal.Md5string("UFDBCACHE_" + strings.TrimSpace(query))
	if SelfUfdbGuardWebFilteringCacheTime > 1 {
		//val, err := internal.MC.Get(md5query)
		//if err != nil {
		//	if isDebugUfdbguard {
		//		log.Printf("Error Getting MD5 from memcache: %s", err)
		//	}
		//	panic(err)
		//}
		//if val != nil {
		//	SelfCached = true
		//	response = string(val.Value)
		//
		//}
		if val, err := internal.Fetch(md5query); val != nil {
			SelfCached = true
			response = *val
		} else {
			if isDebugUfdbguard {
				log.Printf("Error Getting MD5 from in memory cache: %s", err)
			}
		}
	}

	if !SelfCached {

		response = sendSocket(query)
		if isDebugUfdbguard {
			log.Printf("####### RESPONSE FROM UFDB WITH VAL %s##############", response)
		}
	} else {
		if isDebugUfdbguard {
			log.Printf("####### RESPONSE FROM CACHE WITH VAL %s##############", response)
		}
	}
	if isDebugUfdbguard {
		log.Printf("[UFDB_CLASS][360]: response: %s", response)
	}
	if SelfInactiveService {
		return false
	}
	if response == "OK" {
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: OK: PASS")
		}
		if !SelfCached {
			if SelfUfdbGuardWebFilteringCacheTime > 1 {
				//internal.MC.Set(&memcache.Item{key: md5query, Value: []byte(response), Expiration: int32(SelfUfdbGuardWebFilteringCacheTime)})
				internal.Append(md5query, response)
			}
		}
		return false
	}
	if len(response) == 0 {
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS][238]: UNKNOWN: \"PASS\"")
		}
		return false
	}
	if !SelfCached {
		if SelfUfdbGuardWebFilteringCacheTime > 1 {
			//internal.MC.Set(&memcache.Item{key: md5query, Value: []byte(response), Expiration: int32(SelfUfdbGuardWebFilteringCacheTime)})
			internal.Append(md5query, response)
		}
	}
	re := regexp.MustCompile("rewrite-url=\"(.*?)\"")
	matches = re.MatchString(response)
	key = "rewrite"
	rs := re.FindStringSubmatch(response)
	if !matches {
		re2 := regexp.MustCompile("url=\"(.*?)\"")
		matches = re2.MatchString(response)
		key = "url"
		rs = re2.FindStringSubmatch(response)
	}
	if matches {
		redirection = rs[1]

	}
	if len(redirection) == 0 {
		redirection = response
	}
	redirection = strings.ReplaceAll(redirection, "??", "?")
	if isDebugUfdbguard {
		log.Printf("[UFDB_CLASS][297]: redirection = %s (299)", redirection)
	}
	RedirectionSource := redirection
	re3 := regexp.MustCompile("rule-id=([0-9]+).*?targetgroup=(.+?)&")
	matches = re3.MatchString(RedirectionSource)
	if matches {
		rs = re3.FindStringSubmatch(RedirectionSource)
		categoryFound = rs[2]
		categoryFound = strings.ReplaceAll(categoryFound, "P", "")
		SelfRuleID, _ = strconv.Atoi(rs[1])
	}
	re4 := regexp.MustCompile("clientgroup=(.+?)&")
	matches = re4.MatchString(RedirectionSource)
	if matches {
		rs = re4.FindStringSubmatch(RedirectionSource)
		SelfWebfilterRuleName = rs[1]
	}
	if isDebugUfdbguard {
		log.Printf("[UFDB_CLASS]: categoryFound = %s ruleid=%d", categoryFound, SelfRuleID)
	}

	if strings.Index(redirection, "=%a") > 0 {
		redirection = strings.ReplaceAll(redirection, "clientaddr=%a", "clientaddr="+clientIp)
	}

	if strings.Index(redirection, "=%i") > 0 {
		redirection = strings.ReplaceAll(redirection, "clientuser=%i", "clientuser="+uid)
	}

	if strings.Index(redirection, "=%u") > 0 {
		redirection = strings.ReplaceAll(redirection, "url=%u", "url="+url.PathEscape(sourceUrl))
	}
	if isDebugUfdbguard {
		log.Printf("[UFDB_CLASS]: redirection %s", redirection)
	}

	redirection = strings.ReplaceAll(redirection, "\"", "")
	SelfFinalRedirectUrl = redirection
	SelfRedirectKey = key
	SelfCategory, _ = strconv.Atoi(categoryFound)
	SelfWebfilteringToken = fmt.Sprintf("webfiltering=block,%s,%s  srcurl=\"%s\"", strconv.Itoa(SelfRuleID), categoryFound, url.PathEscape(sourceUrl))
	return true
}

func sendSocket(query string) string {
	SelfInactiveService = false
	response := ""
	if SelfRemotePort == 0 {
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: Configuration Error, no port set... Aborting!")
		}
		return ""
	}
	if isDebugUfdbguard {
		log.Printf("[UFDB_CLASS]: Send to service %s", query)
	}

	d := net.Dialer{Timeout: SelfUfdbgclientSockTimeOut}
	conn, err := d.Dial("tcp", SelfRemoteIP+":"+strconv.Itoa(SelfRemotePort))
	if err != nil {

		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: Connection Error: Unable to connect to %s %d ERROR: %s", SelfRemoteIP, SelfRemotePort, err)
		}
		return ""
	}
	fmt.Fprintf(conn, query+"\n")
	message, errB := bufio.NewReader(conn).ReadString('\n')
	if errB != nil {
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]: Connection Error:  Unable to receive data from %s %d ERROR: %s", SelfRemoteIP, SelfRemotePort, errB)
		}
		return ""
	}
	response = strings.TrimSpace(message)
	if isDebugUfdbguard {
		log.Printf("[UFDB_CLASS]: RESPONSE: %s", response)
	}
	if strings.Index(response, "?loading-database=yes") == 0 {
		SelfInactiveService = true
	}
	if strings.Index(response, "?fatalerror=yes") == 0 {
		SelfInactiveService = true
	}
	if SelfInactiveService {
		if isDebugUfdbguard {
			log.Printf("[UFDB_CLASS]:FATAL Error Load-database or Web-Filtering error!!")
		}
	}
	return response
}
func checkIPAddressType(ip string, isDebugUfdbguard bool) bool {
	if net.ParseIP(ip) == nil {
		if isDebugUfdbguard {
			log.Printf("Invalid IP Address: %s\n", ip)
		}
		return false
	}
	for i := 0; i < len(ip); i++ {
		switch ip[i] {
		case '.':
			if isDebugUfdbguard {
				log.Printf("Given IP Address %s is IPV4 type\n", ip)
			}
			return true
		case ':':
			if isDebugUfdbguard {
				log.Printf("Given IP Address %s is IPV6 type\n", ip)
			}
			return true
		}
	}
	return false
}
