package main

import (
	"categorization"
	b64 "encoding/base64"
	"encoding/json"
	_ "expvar"
	"fmt"
	"github.com/facebookgo/pidfile"
	"github.com/fasthttp/router"
	"github.com/oschwald/geoip2-golang"
	"github.com/techoner/gophp"
	"github.com/valyala/fasthttp"
	"handlers"

	"github.com/sirupsen/logrus"
	"log"
	"log/syslog"
	"net"
	urlparse "net/url"
	"os"
	"regexp"
	"runtime/debug"
	"shields"
	"storage"
	"strconv"
	"strings"
	"time"
	"ufdbguard"
)

var (
	addr     = "127.0.0.1"
	port int = 3333
	//CONN_TYPE = "tcp"
	version = "1.0.11"

	strContentType                   = []byte("Content-Type")
	strApplicationJSON               = []byte("application/json")
	isDebug                     bool = false
	timeOut                     int  = 5
	Selfsitename                string
	SelfSourceline              string
	SelfSquidUrgency            bool
	SelfKsrnEmergency           bool
	SelfEnableUfdbGuard         bool
	SelfExternalAclFirstRequest int
	SelfTokenOutput             string
	SelfProtocol                string
	SelfVirtualUser             string
	SelfMac                     string
	SelfLocalCache              map[string]string
	SelfmethodProto             string
	SelfUsername                string
	Selfipaddr                  string
	SelfShieldMac               string
	SelfShieldipaddr            string
	SelfShieldUsername          string
	SelfVirtualUserCache        map[string]string
	SelfEnableStrongswanServer  int
	SelfEnableITChart           bool
	SelfWebfilterRuleName       string
	Selfcategory                int
	SelfcategoryName            string
	SelfWebPages                []interface{}
	SelfMaxItemsInMemory        int
	SelfprepareDataText         []byte
	SelfKSRNOnlyCategorization  = true
	SelfCacheTime               int
	SelfResolvedHosts           map[string]bool
)

func init() {
	// create pid file
	pidfile.SetPidfilePath("/var/run/go-shield-server.pid")

	err_pid := pidfile.Write()
	if err_pid != nil {
		panic("Could not write pid file")
	}
	log.SetFlags(log.LstdFlags | log.Lshortfile)
	//log.SetOutput(os.Stderr) // it's default
	if _slog, err := syslog.New(syslog.LOG_DEBUG, "go-shield-server"); err == nil {
		log.SetOutput(_slog)
	}
}

func main() {
	f, err := os.OpenFile("/var/log/go-shield/server-stack.log", os.O_RDWR|os.O_CREATE|os.O_APPEND, 0666)

	logrus.SetOutput(f)
	defer func() {
		if r := recover(); r != nil {
			logrus.Errorf("Panic: %v,\n%s", r, debug.Stack())
			os.Exit(1)
		}
	}()
	isDebug = handlers.GetSocketInfoBool("Go_Shield_Server_Debug")

	categorization.DebugCategorization = isDebug
	handlers.DebugHandlers = isDebug
	storage.DebugStorage = isDebug

	addr = handlers.GetSocketInfoString("Go_Shield_Server_Addr")
	port = handlers.GetSocketInfoInt("Go_Shield_Server_Port")

	timeOut = handlers.GetSocketInfoInt("Go_Shield_Connector_TimeOut")
	SelfSquidUrgency = handlers.GetSocketInfoBool("SquidUrgency")
	SelfEnableUfdbGuard = handlers.GetSocketInfoBool("EnableUfdbGuard")
	SelfKsrnEmergency = handlers.GetSocketInfoBool("KSRNEmergency")
	SelfExternalAclFirstRequest = handlers.GetSocketInfoInt("ExternalAclFirstRequest")
	SelfEnableStrongswanServer = handlers.GetSocketInfoInt("EnableStrongswanServer")
	SelfEnableITChart = handlers.GetSocketInfoBool("EnableITChart")
	SelfTokenOutput = "OK"
	SelfProtocol = "GET"

	SelfMaxItemsInMemory = handlers.GetSocketInfoInt("TheShieldMaxItemsInMemory")
	SelfKSRNOnlyCategorization = handlers.GetSocketInfoBool("KSRNOnlyCategorization")
	WebErrorPagesCompiled := handlers.GetSocketInfoString("WebErrorPagesCompiled")
	SelfCacheTime = handlers.GetSocketInfoInt("TheShieldServiceCacheTime")
	if SelfEnableUfdbGuard {
		ufdbguard.DebugUfdbguard = isDebug
	}

	if SelfEnableITChart {
		//itchart.DebugITChart = isDebug
	}

	shields.DebugShields = isDebug

	SelfVirtualUserCache = make(map[string]string)
	if addr == "" {
		addr = "127.0.0.1"
	}
	if port == 0 {
		port = 3333
	}
	if SelfCacheTime == 0 {
		SelfCacheTime = 84600
	}
	if timeOut == 0 {
		timeOut = 5
	}
	out, err := gophp.Unserialize([]byte(WebErrorPagesCompiled))

	if err != nil {
		if isDebug {
			log.Printf("[BUILD_PAGE]: Loading Client Engine Web error pages failed to unserialize: ", err)
		}
	}
	SelfWebPages = out.([]interface{})

	log.Printf("Starting Go Shield Server => addr=%s port=%d debug=%t ", addr, port, isDebug)

	/*TCP*/
	//l, err := net.Listen(CONN_TYPE, addr+":"+port)
	//if err != nil {
	//	log.Printf("Error listening:", err.Error())
	//	os.Exit(1)
	//}
	//// Close the listener when the application closes.
	//defer l.Close()
	//log.Printf("Listening on " + addr + ":" + port)
	/*FASTHTTP*/
	//log.Printf("Try connect to ListenAndServe: %s %d", addr, port)
	//h := requestHandler
	//h = fasthttp.CompressHandler(h)
	r := router.New()
	r.GET("/get-categories/{website}", getCategories)
	r.GET("/get-version", getVersion)
	r.GET("/category/{website}/{ids}", category)
	r.GET("/geo/{source-ip}/{iso-code}", geo)
	r.GET("/db/stats", dbStats)
	r.GET("/db/flush", dbFlush)
	r.GET("/db/capacity", dbCapacity)
	r.GET("/db/len", dbLen)
	r.GET("/external-acl-first/{line}", processRequest)
	if err := fasthttp.ListenAndServe(addr+":"+strconv.Itoa(port), r.Handler); err != nil {
		log.Fatalf("Error in ListenAndServe: %s", err)
	}
}

/*FASTHTTP*/
func getVersion(ctx *fasthttp.RequestCtx) {
	ctx.Response.Header.SetCanonical(strContentType, strApplicationJSON)
	ctx.Response.SetStatusCode(200)
	response := map[string]string{"version": version}
	if err := json.NewEncoder(ctx).Encode(response); err != nil {
		log.Fatal(err)
	}
}

func getCategories(ctx *fasthttp.RequestCtx) {
	catid, catname := categorization.GetCategories(ctx.UserValue("website").(string))
	ctx.Response.Header.SetCanonical(strContentType, strApplicationJSON)
	ctx.Response.SetStatusCode(200)
	response := map[string]string{"category_id": strconv.Itoa(catid), "category_name": catname}
	if err := json.NewEncoder(ctx).Encode(response); err != nil {
		log.Fatal(err)
	}
}

func category(ctx *fasthttp.RequestCtx) {
	catid, _ := categorization.GetCategories(ctx.UserValue("website").(string))
	//ctx.Response.Header.SetCanonical(strContentType, strApplicationJSON)
	ctx.Response.SetStatusCode(200)
	if catid == 0 {
		ctx.WriteString("FALSE")
	}
	split := strings.Split(ctx.UserValue("ids").(string), "-")
	results := false
	for _, line := range split {
		matches, _ := strconv.Atoi(line)
		if catid == matches {
			results = true
			ctx.WriteString("TRUE")
			break

		}
	}
	if !results {
		ctx.WriteString("FALSE")

	}
}
func dbStats(ctx *fasthttp.RequestCtx) {
	ctx.Response.SetStatusCode(200)
	ctx.Response.Header.SetCanonical(strContentType, strApplicationJSON)
	stats, _ := json.Marshal(storage.InMemoryCache.Stats())
	ctx.Write(stats)
	//	defer conn.Close()
	//	stats, _ := json.Marshal(InMemoryCache.Stats())
	//	conn.Write(stats)
}

func dbFlush(ctx *fasthttp.RequestCtx) {
	ctx.Response.SetStatusCode(200)
	storage.InMemoryCache.Reset()
	ctx.WriteString("OK")

}

func dbLen(ctx *fasthttp.RequestCtx) {
	ctx.Response.SetStatusCode(200)
	ctx.Response.Header.SetCanonical(strContentType, strApplicationJSON)
	stats, _ := json.Marshal(storage.InMemoryCache.Len())
	ctx.Write(stats)
	//	defer conn.Close()
	//	stats, _ := json.Marshal(InMemoryCache.Stats())
	//	conn.Write(stats)
}

func dbCapacity(ctx *fasthttp.RequestCtx) {
	ctx.Response.SetStatusCode(200)
	ctx.Response.Header.SetCanonical(strContentType, strApplicationJSON)
	stats, _ := json.Marshal(storage.InMemoryCache.Capacity())
	ctx.Write(stats)
	//	defer conn.Close()
	//	stats, _ := json.Marshal(InMemoryCache.Stats())
	//	conn.Write(stats)
}

func geo(ctx *fasthttp.RequestCtx) {
	ctx.Response.SetStatusCode(200)
	db, err := geoip2.Open("/usr/local/share/GeoIP/GeoLite2-City.mmdb")
	if err != nil {
		ctx.WriteString(err.Error())
		return
	}
	defer db.Close()
	// If you are using strings that may be invalid, check that ip is not nil
	ip := net.ParseIP(ctx.UserValue("source-ip").(string))
	record, err := db.City(ip)
	if err != nil {
		ctx.WriteString(err.Error())
		return
	}

	//ctx.Response.Header.SetCanonical(strContentType, strApplicationJSON)

	split := strings.Split(ctx.UserValue("iso-code").(string), "-")
	results := false
	for _, line := range split {
		if line == record.Country.IsoCode {
			results = true
			ctx.WriteString("TRUE")
			break

		}
	}
	if !results {
		ctx.WriteString("FALSE")

	}
}

func processRequest(ctx *fasthttp.RequestCtx) {
	//f, err := os.OpenFile("/var/log/go-shield/server-stack.log", os.O_RDWR|os.O_CREATE|os.O_APPEND, 0666)
	//
	//logrus.SetOutput(f)
	defer func() {
		if r := recover(); r != nil {
			log.Printf("Panic: %v,\n%s", r, debug.Stack())
			os.Exit(1)
		}
	}()
	start := time.Now()
	request := ctx.UserValue("line").(string)

	decode, _ := b64.URLEncoding.DecodeString(request)
	line := string(decode)
	Selfsitename = ""
	SelfSourceline = line
	lparts := strings.Split(strings.TrimRight(line, "\n"), " ")
	id := lparts[0]
	if isDebug {
		log.Printf("Receive <%s>", line)
	}
	if strings.Index(line, "webfilter:%20pass") > 10 {
		if isDebug {
			log.Printf("WEBFILTER = PASS")
		}
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(id + " OK\n")
		return
	}
	if strings.Index(line, "/squid-internal-dynamic/") > 10 {
		if isDebug {
			log.Printf("INTERNAL-DYNAMIC = PASS")
		}
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(id + " OK\n")
		return
	}
	if strings.Index(line, "/squid-internal-mgr/") > 10 {
		if isDebug {
			log.Printf("INTERNAL-DYNAMIC = PASS")
		}
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(id + " OK\n")
		return
	}
	if strings.Index(line, "cache_object:/") > 0 {
		if isDebug {
			log.Printf("INTERNAL-DYNAMIC = PASS")
		}
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(id + " OK\n")
		return
	}
	SelfTokenOutput = "OK"
	parseArray := parseLine(line)
	if len(parseArray) == 0 {
		if isDebug {
			log.Printf("Error parseLine array exception, arry is empty ")
		}
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(fmt.Sprintf("%s OK %s first=ERROR\n", id, SelfTokenOutput))
		return
	}
	logPrefix := ""
	//CountOfRows := len(parseArray)
	aclType, _ := strconv.Atoi(parseArray["acl"])
	if aclType == 1 {
		SelfTokenOutput = "OK"
	}
	var tokens []string
	//Sni := ""
	//UserCert := ""
	//categoryOrder := true
	//tokenscategoryAdded := false
	//Rblpass := false
	categoryName := ""
	category := 0
	method := "GET"
	choose := ""
	url := ""
	results := ""
	var blockit bool
	var white bool
	_ = white
	//SaveCache := false
	var action string
	SelfVirtualUser = ""
	var countryCode string
	var itChart string
	_ = itChart
	var itChartInfo string
	var errorMsg string
	_ = itChartInfo
	SelfMac = ""
	xForward := ""
	sitename := ""
	clientHostname := ""
	//ITChartInfo := ""
	//CacheMessage := "MISS"
	var cachedService int
	_ = cachedService
	proxyPort := 3128
	proxyIP := "127.0.0.1"
	var logQuery []string
	countOfInternalCache := len(SelfLocalCache)
	logQuery = append(logQuery, "Items in array: "+strconv.Itoa(countOfInternalCache))
	//WebfilteringFound := false
	//WebfilteringChecked := false
	asACL := 0
	askToShields := true //Todo Set askToShields
	modeBack := false
	SelfmethodProto = ""
	SelfUsername = parseArray["username"]
	ipaddr := parseArray["ipaddr"]
	if _, ok := parseArray["mac"]; ok {
		SelfMac = parseArray["mac"]
	}
	if _, ok := parseArray["forwardedfor"]; ok {
		xForward = parseArray["forwardedfor"]
	}
	//if _, ok := parseArray["sni"]; ok {
	//	Sni = parseArray["sni"]
	//}
	if _, ok := parseArray["hostname"]; ok {
		clientHostname = parseArray["hostname"]
	}
	if _, ok := parseArray["domain"]; ok {
		sitename = parseArray["domain"]
	}
	if _, ok := parseArray["acl"]; ok {
		asACL, _ = strconv.Atoi(parseArray["acl"])
	}
	if _, ok := parseArray["proto"]; ok {
		method = parseArray["proto"]
	}
	url = parseArray["url"]
	proxyPort, _ = strconv.Atoi(parseArray["myport"])
	proxyIP = parseArray["myip"]
	if len(sitename) == 0 {
		if len(url) > 0 {
			sitename = url
		}
	}
	u, _ := urlparse.Parse(sitename)
	sitename = u.Host
	if len(sitename) == 0 {
		sitename = u.Path
	}
	if sitename[0:4] == "www." {
		sitename = sitename[4:]
	}
	sitename = strings.ToLower(sitename)

	proxyUrl := url
	//Sourceurl := url
	//urlDomain := sitename
	clientIp := ipaddr
	Selfipaddr = ipaddr
	clientMac := SelfMac
	SelfVirtualUser = virtualUsers()

	Selfsitename = sitename
	SelfmethodProto = method
	if SelfMac == "00:00:00:00:00:00" {
		SelfMac = ""
	}
	logPrefix = fmt.Sprintf("%s %s %s %s %s", sitename, method, SelfMac, Selfipaddr, SelfUsername)
	logQuery = append(logQuery, logPrefix)
	shields.SelfShieldIpaddr = Selfipaddr
	shields.SelfShieldMac = SelfMac
	shields.SelfShieldUsername = SelfUsername
	log.Printf("Executing shields.CountUsers()")
	shields.CountUsers()
	if len(SelfVirtualUser) > 0 {
		SelfUsername = SelfVirtualUser
	}
	if xForward == "-" {
		xForward = ""
	}
	if len(xForward) > 0 {
		ipaddr = xForward
	}
	//TODO check if make sense
	//if len(SelfUsername) < 3 {
	//	if len(UserCert) > 2 {
	//		SelfUsername = UserCert
	//	}
	//}
	if Selfsitename == "127.0.0.1" {
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(fmt.Sprintf("%s OK %s first=NONE\n", id, SelfTokenOutput))
		return
	}
	if SelfSquidUrgency {
		if isDebug {
			log.Printf("WARNING... Emergency Enabled")
		}
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(fmt.Sprintf("%s OK %s first=EMERGENCY webfilter=pass\n", id, SelfTokenOutput))

		return
	}
	if asACL == 0 {
		modeBack = true
		askToShields = false
	}

	if modeBack {
		if isDebug {
			log.Printf("%s [DEBUG]: [BACK_MODE] Web-Filtering:%t | ItCharter:%t", Selfsitename, SelfEnableUfdbGuard, SelfEnableITChart)
		}
		if strings.Index(SelfSourceline, "shieldsblock:%20yes") > 10 {
			SelfWebfilterRuleName = strconv.Itoa(0)
			Selfcategory = 999999999
			SelfcategoryName = "theshields"
			re := regexp.MustCompile("cinfo:([0-9]+)-(.+?);")
			matches := re.MatchString(SelfSourceline)
			rs := re.FindStringSubmatch(SelfSourceline)
			if matches {
				Selfcategory, _ = strconv.Atoi(rs[1])
				SelfcategoryName = rs[2]
			}
			redirect := buildErrorPage()
			ctx.Response.SetStatusCode(200)
			ctx.WriteString(fmt.Sprintf("%s OK %s %s\n", id, SelfTokenOutput, redirect))
			return
		}
		if SelfEnableUfdbGuard {
			ufdbGuardClientLine := ufdbGuardClient(id, line, proxyUrl, Selfsitename, clientIp, clientHostname, clientMac, proxyIP, proxyPort)
			//WebfilteringChecked = true
			if isDebug {
				log.Printf("%s [DEBUG] return <%s>", Selfsitename, ufdbGuardClientLine)
			}
			if len(ufdbGuardClientLine) > 0 {
				log.Printf("%s [DEBUG] return <%s>", Selfsitename, ufdbGuardClientLine)
				ctx.Response.SetStatusCode(200)
				ctx.WriteString(ufdbGuardClientLine)
				return
			}
			tokens = append(tokens, "webfilter=pass")
		}
		//if SelfEnableITChart {
		//	if isDebug {
		//		log.Printf("%s [DEBUG][ITCHART] Ask to itchart_client()", Selfsitename)
		//	}
		//	itChartLine := itChartClient(id)
		//	if len(itChartLine) > 0 {
		//		if isDebug {
		//			log.Printf("%s [DEBUG][ITCHART] return <%s>", Selfsitename, itChartLine)
		//		}
		//		ctx.Response.SetStatusCode(200)
		//		ctx.WriteString(itChartLine)
		//		return
		//	}
		//	tokens = append(tokens, "itchart=PASS")
		//}
	}
	if isWhitelist(Selfsitename, SelfMac, Selfipaddr) {
		if isDebug {
			log.Printf("%s: %s[%s] WHITELISTED", Selfsitename, Selfipaddr, SelfMac)
		}
		category, categoryName = categorization.GetCategories(Selfsitename)
		tokens = append(tokens, fmt.Sprintf("category=%d category-name=%s clog=cinfo:%d-%s; ", category, categoryName, category, categoryName))
		tokens = append(tokens, "srn=WHITE rblpass=yes webfilter=pass")
		SelfTokenOutput = strings.Join(tokens, " ")
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(fmt.Sprintf("%s OK %s \n", id, SelfTokenOutput))
		return
	}
	if isBlacklisted(Selfsitename) {
		if isDebug {
			log.Printf("%s: %s[%s] BLACKLISTED", Selfsitename, Selfipaddr, SelfMac)
		}
		category, categoryName = categorization.GetCategories(Selfsitename)
		tokens = append(tokens, fmt.Sprintf("category=%d category-name=%s clog=cinfo:%d-%s; ", category, categoryName, category, categoryName))
		tokens = append(tokens, "srn=BLACK shieldsblock=yes")
		SelfTokenOutput = strings.Join(tokens, " ")
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(fmt.Sprintf("%s OK %s \n", id, SelfTokenOutput))
		return
	}
	choose = findKeyAccount()
	prepareData := make(map[string]string)
	prepareData["ACTION"] = "THESHIELDS"
	prepareData["choose"] = choose
	prepareData["USERNAME"] = SelfUsername
	prepareData["ipaddr"] = ipaddr
	prepareData["mac"] = SelfMac
	prepareData["sitename"] = Selfsitename
	prepareData["method"] = method
	prepareData["LOG_QUERY"] = "0"
	if SelfExternalAclFirstRequest == 1 {
		logQuery = append(logQuery, "Log-query: Yes")
		prepareData["LOG_QUERY"] = "1"
	}
	//TODO Check if make sense to keep
	//SelfprepareDataText, _ = gophp.Serialize(prepareData)
	//TODO Check if make sense to keep smd5
	smd5 := storage.ShieldsFullCache(SelfUsername, ipaddr, SelfMac, Selfsitename, method)
	if _, ok := SelfLocalCache[smd5]; ok {
		if isDebug {
			log.Printf("%s: HIT [%s] Client-array", Selfsitename, smd5)
		}
		results = SelfLocalCache[smd5]
	}

	if results == "" {
		if value, _ := storage.Fetch(smd5); value != nil {
			results = string(*value)
			if results != "" {
				if isDebug {
					log.Printf("%s HIT [%s] Client-memcache", Selfsitename, smd5)
				}
				SelfLocalCache[smd5] = results
			}
		}
	}
	if results != "" {
		askToShields = false
		logQuery = append(logQuery, "Client sock time: -")
	}
	log.Printf("RESULTS ARE %s and TOKEN IS %t", results, SelfKSRNOnlyCategorization)
	if results == "" {
		if isDebug {
			log.Printf("%s MISS ARRAY [%v] KSRNOnlyCategorization=%t", Selfsitename, smd5, SelfKSRNOnlyCategorization)
		}
		if SelfKSRNOnlyCategorization {
			if len(SelfUsername) < 2 {
				if len(SelfVirtualUser) > 0 {
					SelfUsername = SelfVirtualUser
					choose = findKeyAccount()
				}
			}
			if len(SelfUsername) > 1 {
				tokens = append(tokens, fmt.Sprintf("user=%s", urlparse.QueryEscape(SelfUsername)))
			}
			category, categoryName = categorization.GetCategories(sitename)
			tokens = append(tokens, fmt.Sprintf("category=%d category-name=%s clog=cinfo:%d-%s; ", category, categoryName, category, categoryName))

			SelfTokenOutput = strings.Join(tokens, " ")
			elapsed := time.Since(start).Microseconds()
			log.Printf("FINISH PARSING - took %d μs", elapsed)
			ctx.Response.SetStatusCode(200)
			ctx.WriteString(fmt.Sprintf("%s OK %s\n", id, SelfTokenOutput))
			return
		}
	}
	if isDebug {
		log.Printf("[DEBUG]:ASK_TO_SHIELDS [%t] MODE_BACK [%t]", askToShields, modeBack)
	}
	if askToShields {
		results = processTheShields()
	}
	if len(results) < 5 {
		if isDebug {
			log.Printf("%s ERROR LEN ARRAY", logPrefix)
		}
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(fmt.Sprintf("%s OK %s first=ERROR\n", id, SelfTokenOutput))
		return
	}
	//TODO check SaveCache
	if isDebug {
		log.Printf("%s [DEBUG]: Receive [%s]", sitename, results)
	}
	out, err := gophp.Unserialize([]byte(results))
	if err != nil {
		if isDebug {
			log.Printf("%s: [ERROR]: Unserialize error", sitename)
		}
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(fmt.Sprintf("%s OK %s first=ERROR\n", id, SelfTokenOutput))
		return
	}

	resultsArray := out.(map[string]interface{})
	if resultsArray["error"] != nil {
		errorMsg = resultsArray["error"].(string)
	}

	if category == 0 {
		category = resultsArray["categoy_id"].(int)
	}
	if len(categoryName) == 0 {
		if resultsArray["categoy_name"] != nil {
			categoryName = resultsArray["categoy_name"].(string)
		}
	}
	if resultsArray["ACTION"] != nil {
		action = resultsArray["ACTION"].(string)
	}
	if resultsArray["VIRTUAL_USER"] != nil {
		SelfVirtualUser = resultsArray["VIRTUAL_USER"].(string)
	}
	if resultsArray["COUNTRY_CODE"] != nil {
		countryCode = resultsArray["COUNTRY_CODE"].(string)
	}

	if _, ok := resultsArray["ITCHART"]; ok {
		itChart = resultsArray["ITCHART"].(string)
	}
	if _, ok := resultsArray["ITCHART_INFO"]; ok {
		itChartInfo = resultsArray["ITCHART_INFO"].(string)
	}
	if _, ok := resultsArray["CACHED_SERVICE"]; ok {
		cachedService = resultsArray["CACHED_SERVICE"].(int)
	}

	if _, ok := resultsArray["CACHED_TIME"]; ok {
		logQuery = append(logQuery, fmt.Sprintf("Service cached time: %d", resultsArray["CACHED_TIME"].(int)))
	}
	if _, ok := resultsArray["SHIELD_TIMES"]; ok {
		logQuery = append(logQuery, fmt.Sprintf("Time details: %d", resultsArray["SHIELD_TIMES"].(int)))
	}
	if _, ok := resultsArray["SHIELD_DURATION"]; ok {
		logQuery = append(logQuery, fmt.Sprintf("The Shield duration: %d", resultsArray["SHIELD_DURATION"].(int)))
	}
	if _, ok := resultsArray["TOTAL_DURATION"]; ok {
		logQuery = append(logQuery, fmt.Sprintf("The Shield Total: %d", resultsArray["TOTAL_DURATION"].(int)))
	}

	if action == "WHITELIST" {
		tokens = append(tokens, "srn=WHITE rblpass=yes")
		white = true
	}
	blockit = true
	if isDebug {
		log.Printf("%s The Shield, Answering [ -%s- ] [%s]", sitename, errorMsg, action)
	}
	if action == "WHITELIST" {
		tokens = append(tokens, "srn=WHITE rblpass=yes")
		blockit = false
	}
	if action == "PASS" {
		tokens = append(tokens, "srn=PASS")
		blockit = false
	}
	if action == "WHITE" {
		tokens = append(tokens, "srn=WHITE rblpass=yes")
		blockit = false
	}
	if len(action) == 0 {
		if isDebug {
			log.Printf("%s The Shield, ERROR OCCURED NO ACTION %s %s", sitename, errorMsg, action)
		}
		tokens = append(tokens, "srn=ERROR")
		blockit = false
	}
	if blockit {
		if isDebug {
			log.Printf("%s The Shield, BLOCK [ -%s- ] [%s]", sitename, errorMsg, action)
		}
		tokens = append(tokens, "shieldsblock=yes")
		tokens = append(tokens, fmt.Sprintf("TheShields:%s", action))
		Selfcategory = category
	}
	if category > 0 {
		categoryName = strings.Replace(categoryName, " ", "_", -1)
		categoryName = strings.Replace(categoryName, "/", "_", -1)
		tokens = append(tokens, fmt.Sprintf("category=%d category-name=%s clog=cinfo:%d-%s;", category, categoryName, category, categoryName))
		logQuery = append(logQuery, fmt.Sprintf("Category: %s", categoryName))
	} else {
		tokens = append(tokens, "category=0 category-name=Unknown clog=cinfo:0-unknown;")
		logQuery = append(logQuery, "Category: Unknown")
	}
	if isDebug {
		log.Printf("[DEBUG]: [%s]:", sitename)
	}
	if len(SelfUsername) < 2 {
		if SelfVirtualUser == "" {
			SelfVirtualUser = ""
		}
		if len(SelfVirtualUser) > 0 {
			SelfUsername = SelfVirtualUser
			choose = findKeyAccount()
		}
	}
	if len(SelfUsername) > 1 {
		tokens = append(tokens, fmt.Sprintf("user=%s", urlparse.QueryEscape(SelfUsername)))
	}
	if len(countryCode) > 0 {
		tokens = append(tokens, fmt.Sprintf("fromgeo=%s", countryCode))
	}
	if blockit {
		SelfTokenOutput = strings.Join(tokens, ", ")
		if isDebug {
			log.Printf("%s: [DEBUG]: [DENIED]: FINAL[%s] (processing %s)", sitename, SelfTokenOutput, handlers.TimeTrack(start))
		}
		ctx.Response.SetStatusCode(200)
		ctx.WriteString(fmt.Sprintf("%s OK %s\n", id, SelfTokenOutput))
		return
	}
	if isDebug {
		log.Printf("[DEBUG]: [%s]: self.TOKEN_OUPUT [%s]", sitename, SelfTokenOutput)
	}
	SelfTokenOutput = strings.Join(tokens, ", ")
	if isDebug {
		log.Printf("%s: [DEBUG] FINAL[%s] (processing %s)", sitename, SelfTokenOutput, handlers.TimeTrack(start))
	}
	ctx.Response.SetStatusCode(200)
	ctx.WriteString(fmt.Sprintf("%s OK %s\n", id, SelfTokenOutput))
	return
	//ctx.Response.SetStatusCode(200)
	//ctx.WriteString(id + " OK first=ERROR\n")

	////OLD OLD OLD ////

}

//func itChartClient(id string) string {
//	if !SelfEnableITChart {
//		if isDebug {
//			log.Printf("%s: [DEBUG][ITCHART] return NONE", Selfsitename)
//		}
//
//		return ""
//	}
//	itChartRedirectURL := handlers.GetSocketInfoString("ITChartRedirectURL")
//	if len(itChartRedirectURL) == 0 {
//		if isDebug {
//			log.Printf("%s: [DEBUG][ITCHART] return NONE", Selfsitename)
//			log.Printf("[ERROR]: Redirect URL is not set, please add the redirect URL in configuration")
//		}
//		return ""
//	}
//	itChartRedirectURLArray := handlers.GetSocketInfoString("ITChartRedirectURLArray")
//	if len(itChartRedirectURLArray) == 0 {
//		if isDebug {
//			log.Printf("%s: [DEBUG][ITCHART] return NONE", Selfsitename)
//			log.Printf("[ERROR]: Redirect URL Array is not set, please add the redirect URL in configuration")
//		}
//		return ""
//	}
//	if isDebug {
//		log.Printf("%s [ITCHART]: %s %s %s %s", Selfsitename, Selfipaddr, SelfMac, SelfUsername, SelfProtocol)
//	}
//	//TODO Check if make sense keep unserialzation
//	if !itchart.ChartThis(Selfipaddr, SelfMac, SelfUsername, SelfProtocol, Selfsitename) {
//		if isDebug {
//			log.Printf("%s: [DEBUG][ITCHART] return NONE", Selfsitename)
//		}
//		return ""
//	}
//	var proto []string
//	proto = append(proto, fmt.Sprintf("%d OK status=302", id))
//	proto = append(proto, fmt.Sprintf("url=%s?Token=%s", itChartRedirectURL, itchart.SelfITChartMessage))
//	proto = append(proto, "itchart=ASK\n")
//	return strings.Join(proto, " ")
//
//}

func processTheShields() string {
	SelfResolvedHosts = make(map[string]bool)
	actionPass := []string{"WHITELIST", "PASS", "WHITE", "ERROR"}
	if isDebug {
		log.Printf("%s: [DEBUG]: [THE_SHIELD] Ask To the shield", Selfsitename)
	}
	category := 0
	var categoryName string
	var host string
	action := "PASS"
	ksrnPorn := shields.SelfKsrnPorn
	disableAdvert := shields.SelfDisableAdvert
	HatredAndDiscrimination := shields.SelfHatredAndDiscrimination
	category, categoryName = categorization.GetCategories(Selfsitename)
	if category > 0 {
		if isDebug {
			log.Printf("%s: [DEBUG]: [THE_SHIELD] Fix category answering [%d]", Selfsitename, category)
		}
		badCatz := []int{6, 7, 10, 72, 92, 105, 111, 135, 132, 109, 5, 143}
		hatred := []int{130, 148, 149, 150, 140}
		sPorn := []int{109, 132}
		advert := []int{5, 143}
		SelfResolvedHosts[Selfsitename] = true

		for _, val := range sPorn { // Loop
			if val == category {
				if ksrnPorn {
					action = "ARTICA"
				}
				break
			}
		}
		for _, val := range advert { // Loop
			if val == category {
				if !disableAdvert {
					action = "ARTICA"
				}
				break
			}
		}
		for _, val := range hatred { // Loop
			if val == category {
				if HatredAndDiscrimination {
					action = "ARTICA"
				}
				break
			}
		}
		for _, val := range badCatz { // Loop
			if val == category {
				action = "ARTICA"
				break
			}
		}
		if isDebug {
			log.Printf("%s: [DEBUG]: [THE_SHIELD] Shield result Scanner=%s", Selfsitename, action)
		}
	}
	if _, ok := SelfResolvedHosts[Selfsitename]; ok {
		// the key 'elliot' exists within the map
		if SelfResolvedHosts[Selfsitename] {
			host = "1"
		}
		if !SelfResolvedHosts[Selfsitename] {
			if isDebug {
				log.Printf("%s: [ERROR]: Unable to resolv host", Selfsitename)
			}
			if category == 0 {
				category = 112
				categoryName = categorization.CategoryIntToString(112)
			}
			resultsArray := make(map[string]interface{})
			resultsArray["error"] = "UNKNOWN_HOST"
			resultsArray["categoy_id"] = category
			resultsArray["categoy_name"] = categoryName
			resultsArray["ACTION"] = "PASS"
			resultsArray["TOTAL_DURATION"] = 0
			resultsArray["VIRTUAL_USER"] = SelfVirtualUser
			resultsArray["COUNTRY_CODE"] = ""
			resultsArray["HOSTIP"] = ""
			Selfcategory = category
			SelfcategoryName = categoryName
			results, _ := gophp.Serialize(resultsArray)
			return string(results)
		}
	}

	if host == "" {
		host = shields.GetHost(Selfsitename)
		if isDebug {
			log.Printf("%s: [DEBUG]: [THE_SHIELD] IP:<%s>", Selfsitename, host)
		}
	}
	if host == "" {
		SelfResolvedHosts[Selfsitename] = false
		if isDebug {
			log.Printf("%s: [ERROR]: Unable to resolv host", Selfsitename)
		}
		if category == 0 {
			category = 112
			categoryName = categorization.CategoryIntToString(112)
		}
		resultsArray := make(map[string]interface{})
		resultsArray["error"] = "UNKNOWN_HOST"
		resultsArray["categoy_id"] = category
		resultsArray["categoy_name"] = categoryName
		resultsArray["ACTION"] = "PASS"
		resultsArray["TOTAL_DURATION"] = 0
		resultsArray["VIRTUAL_USER"] = SelfVirtualUser
		resultsArray["COUNTRY_CODE"] = ""
		resultsArray["HOSTIP"] = ""
		Selfcategory = category
		SelfcategoryName = categoryName
		results, _ := gophp.Serialize(resultsArray)
		return string(results)
	}
	SelfResolvedHosts[Selfsitename] = true
	if len(SelfResolvedHosts) > 5000 {
		SelfResolvedHosts = make(map[string]bool)
	}
	shields.SelfShieldIpaddr = Selfipaddr
	shields.SelfShieldUsername = SelfUsername
	shields.SelfShieldMac = SelfMac
	log.Printf("SHILDS OPERATE CAT %d", category)
	if category == 0 {
		log.Printf("SHILDS OPERATE")
		shields.Operate(Selfsitename)
		category = shields.SelfCategory
		action = shields.SelfAction
		categoryName = categorization.CategoryIntToString(category)
	}
	resultsArray := make(map[string]interface{})
	resultsArray["error"] = shields.SelfError
	resultsArray["categoy_id"] = category
	resultsArray["categoy_name"] = categoryName
	resultsArray["ACTION"] = action
	resultsArray["TOTAL_DURATION"] = 0
	resultsArray["VIRTUAL_USER"] = SelfVirtualUser
	resultsArray["COUNTRY_CODE"] = ""
	resultsArray["HOSTIP"] = host
	Selfcategory = category
	if isDebug {
		log.Printf("%s: [DEBUG]: [LOCAL] ACTION=%s ERROR=%s", Selfsitename, action, shields.SelfError)
	}
	SelfcategoryName = categoryName
	for _, val := range actionPass { // Loop
		if val != action {
			go shields.WriteStats(category, Selfsitename, action, 0)
			if isDebug {
				log.Printf("[THREAT_DETECTED]: site=%s addr=%s self.USERNAME=%s mac=%s category=%d/%s scanner=%s", Selfsitename, Selfipaddr, SelfUsername, SelfMac, category, categoryName, action)
			}
			//break
		}
	}
	if isDebug {
		log.Printf("Serializeing Results")
	}
	results, _ := gophp.Serialize(resultsArray)
	if isDebug {
		log.Printf("[SHIELDS RETURN]: results=%s", results)
	}
	return string(results)

}

func findKeyAccount() string {
	if SelfUsername == "-" {
		SelfUsername = ""
	}
	if Selfipaddr == "-" {
		Selfipaddr = ""
	}
	if Selfipaddr == "127.0.0.1" {
		Selfipaddr = ""
	}
	if SelfMac == "-" {
		SelfMac = ""
	}
	if SelfMac == "00:00:00:00:00:00" {
		SelfMac = ""
	}
	if len(SelfUsername) > 3 {
		return SelfUsername
	}
	if len(SelfMac) > 3 {
		return SelfMac
	}
	if len(Selfipaddr) > 3 {
		return Selfipaddr
	}
	return ""
}

func isBlacklisted(sitename string) bool {
	if len(sitename) < 3 {
		return false
	}
	if categorization.AdminBlacklist(sitename, true) {
		return true
	}
	return false
}

func isWhitelist(sitename string, mac string, ipaddr string) bool {
	if sitename == "" {
		return false
	}
	if mac == "" {
		return false
	}
	if ipaddr == "" {
		return false
	}
	if len(sitename) > 3 {
		scache := fmt.Sprintf("DOMWHITE:%s", sitename)
		sf := getCacheItem(scache)
		if sf != "" {
			return true
		}
		if categorization.AdminWhitelist(sitename, true) {
			saveCachedItem(scache, "1")
			return true
		}
	}
	if len(mac) > 3 {
		if categorization.AdminWhitelistMac(mac) {
			return true
		}
	}
	if len(ipaddr) > 3 {
		if categorization.AdminWhitelistSrc(ipaddr) {
			return true
		}
	}
	return false
}

func saveCachedItem(smd5 string, sValue string) bool {
	smd5 = fmt.Sprintf("SHIELD.serv.%s", smd5)
	SelfLocalCache[smd5] = sValue
	if len(SelfLocalCache) > SelfMaxItemsInMemory {
		SelfLocalCache = map[string]string{}
	}
	storage.Append(smd5, sValue)
	return true

}

func getCacheItem(md5 string) string {
	//TODO Check if LocalCache Array is correct
	if _, ok := SelfLocalCache[md5]; ok {
		return SelfLocalCache[md5]
	}
	md5 = fmt.Sprintf("SHIELD.serv.%s", md5)

	if val, err := storage.Fetch(md5); val != nil {
		if len(*val) < 5 {
			return ""
		}
		return *val

	} else {
		if isDebug {
			log.Printf("Error Getting MD5 from memcache: %s", err)
		}
		//panic(err)
	}
	return ""
}

func buildErrorPage() string {
	//ufdb := &ufdbguard.UFDBDefaultVars{}
	var tokens []string
	sitename := Selfsitename
	category := 0
	categoryName := ""
	ruleID := 0
	protocol := SelfProtocol
	http := "http"
	var protocolID int
	if SelfEnableUfdbGuard {
		category = Selfcategory
		categoryName = SelfcategoryName
	}
	ruleID = ufdbguard.SelfRuleID

	if category == 0 {
		re := regexp.MustCompile("cinfo:([0-9]+)-(.+?);")
		matches := re.MatchString(SelfSourceline)
		rs := re.FindStringSubmatch(SelfSourceline)
		if matches {
			Selfcategory, _ = strconv.Atoi(rs[1])
			SelfcategoryName = rs[2]
		}
	}

	if protocol == "CONNECT" {
		protocolID = 1
		http = "https"
	}
	if protocol == "GET" {
		protocolID = 2
	}
	if protocol == "POST" {
		protocolID = 3
	}

	srcUrl := urlparse.QueryEscape(fmt.Sprintf("%s://%s", http, Selfsitename))
	parameters := fmt.Sprintf("rule-id=%d&clientaddr=%s&clientname=%s&clientgroup=%s&targetgroup=%d&url=%s", ruleID, Selfipaddr, SelfUsername, SelfWebfilterRuleName, category, srcUrl)
	lenOfRules := len(SelfWebPages)
	finalredirectCode := 302
	finalredirectType := 0
	finalredirectKey := "url"
	finalredirecturl := "http://articatech.net/block.html"
	var ufdbgParameters string
	if SelfEnableUfdbGuard {
		ufdbgParameters = ufdbguard.SelfFinalRedirectUrl
	}
	if isDebug {
		log.Printf("%s [DEBUG]: [BUILD_PAGE] final parameters <%s>", sitename, ufdbgParameters)
	}
	matched := false
	parsed := ""
	if lenOfRules > 0 {
		for _, data := range SelfWebPages {

			for k, v := range data.(map[string]interface{}) {

				rCategory := 0
				rRuleID := 0
				rRedirectType := 0
				rUrl := ""
				rParsed := ""
				rProto := 0
				if k == "category" {
					rCategory = v.(int)
				}
				if k == "ruleid" {
					rRuleID = v.(int)
				}
				if k == "redirtype" {
					rRedirectType = v.(int)
				}
				if k == "PARSED" {
					if v.(map[string]interface{})["path"] == nil {
						rParsed = ""
					} else {
						rParsed = v.(map[string]interface{})["path"].(string)
					}

				}
				if k == "url" {
					rUrl = v.(string)
				}
				if k == "protocol" {
					rProto = v.(int)
				}
				if isDebug {
					var slogs []string
					slogs = append(slogs, fmt.Sprintf("Rule[%d]/%d", rRuleID, ruleID))
					slogs = append(slogs, fmt.Sprintf("protocol[%d]/%d", rProto, protocolID))
					slogs = append(slogs, fmt.Sprintf("category[%d]/%d", rCategory, category))
					slogs = append(slogs, fmt.Sprintf("redirect[%s] type[%d]", rUrl, rRedirectType))
					SlogsText := strings.Join(slogs, ", ")
					log.Printf("%s [BUILD_PAGE]: index:%s must match %s", sitename, k, SlogsText)
				}
				if rRuleID == 0 {
					if rCategory == 0 {
						if rProto == 0 {
							if isDebug {
								log.Printf("%s [BUILD_PAGE]: !MATCHES! Detected url <%s>", sitename, rUrl)
							}
							finalredirecturl = rUrl
							finalredirectType = rRedirectType
							parsed = rParsed
							matched = true
							break
						}
						if rProto == protocolID {
							if isDebug {
								log.Printf("%s [BUILD_PAGE]: !MATCHES! Detected url <%s>", sitename, rUrl)
							}
							finalredirecturl = rUrl
							finalredirectType = rRedirectType
							parsed = rParsed
							matched = true
							break
						}
					}
				}
				if rRuleID == ruleID {
					if rCategory == 0 {
						if rProto == 0 {
							if isDebug {
								log.Printf("%s [BUILD_PAGE]: !MATCHES! Detected url <%s>", sitename, rUrl)
							}
							finalredirecturl = rUrl
							finalredirectType = rRedirectType
							parsed = rParsed
							matched = true
							break
						}
						if rProto == protocolID {
							if isDebug {
								log.Printf("%s [BUILD_PAGE]: !MATCHES! Detected url <%s>", sitename, rUrl)
							}
							finalredirecturl = rUrl
							finalredirectType = rRedirectType
							parsed = rParsed
							matched = true
							break
						}
					}
					if rCategory == category {
						if rProto == 0 {
							if isDebug {
								log.Printf("%s [BUILD_PAGE]: !MATCHES! Detected url <%s>", sitename, rUrl)
							}
							finalredirecturl = rUrl
							finalredirectType = rRedirectType
							parsed = rParsed
							matched = true
							break
						}
						if rProto == protocolID {
							if isDebug {
								log.Printf("%s [BUILD_PAGE]: !MATCHES! Detected url <%s>", sitename, rUrl)
							}
							finalredirecturl = rUrl
							finalredirectType = rRedirectType
							parsed = rParsed
							matched = true
							break
						}
					}
				} else {
					if isDebug {
						log.Printf("%s [BUILD_PAGE]: False for %d is not %d", sitename, rRuleID, ruleID)
					}
				}
			}
		}
	}
	if isDebug {
		log.Printf("parsed (%s)", parsed)
		if !matched {
			log.Printf("%s [BUILD_PAGE]: NO_MATCHES!! rule[%d] category[%d] Proto[%d] (%s)", sitename, ruleID, category, protocolID, protocol)
		}
	}
	if finalredirectType == 0 {
		finalredirectCode = 302
	}
	if finalredirectType == 1 {
		finalredirectCode = 301
	}
	re := regexp.MustCompile("^(http|https:)")
	matches := re.MatchString(finalredirecturl)
	if matches {
		finalredirecturl = fmt.Sprintf("%s?%s", finalredirecturl, parameters)
	}
	tmpStr := fmt.Sprintf("status=%d %s=%s", finalredirectCode, finalredirectKey, finalredirecturl)
	if finalredirectType == 2 {
		tmpStr = fmt.Sprintf("rewrite-url=%s", finalredirecturl)
	}
	if finalredirectType == 2 {
		tmpStr = fmt.Sprintf("status=%d %s=%s", 302, "url", "http://artica.me")
	}
	if len(tmpStr) > 3 {
		tokens = append(tokens, tmpStr)
	}
	tokens = append(tokens, fmt.Sprintf("category=%d category-name=%s clog=cinfo:%d-%s;", category, categoryName, category, categoryName))
	if SelfEnableUfdbGuard {
		tokens = append(tokens, ufdbguard.SelfWebfilteringToken)
	}
	final := strings.Join(tokens, " ")
	return final
}

func virtualUsers() string {
	ipStrongSwan := ""
	if strings.Index(SelfSourceline, "user:%20") > 10 {
		re := regexp.MustCompile("user:%20(.+?)%0D%0A")
		matches := re.MatchString(SelfSourceline)
		if matches {
			rs := re.FindStringSubmatch(SelfSourceline)
			SelfUsername = rs[1]
			return SelfUsername
		}
	}
	Key := fmt.Sprintf("%s.%s", SelfMac, Selfipaddr)
	if len(SelfVirtualUserCache) > 5000 {
		SelfVirtualUserCache = map[string]string{}
	}
	if _, ok := SelfVirtualUserCache[Key]; ok {
		return SelfVirtualUserCache[Key]
	}
	if SelfEnableStrongswanServer == 1 {
		ipStrongSwan = Selfipaddr
	}
	sresult := storage.UserAliases(SelfMac, Selfipaddr, ipStrongSwan, isDebug)
	if sresult == "" {
		SelfVirtualUserCache[Key] = ""
	}
	SelfVirtualUserCache[Key] = sresult
	return sresult

}
func parseLine(line string) map[string]string {

	results := map[string]string{}
	lparts := strings.Split(strings.TrimRight(line, "\n"), " ")
	if isDebug {
		log.Printf("Parsing entity <%s>", lparts[1])
	}
	if lparts[1] == "MacToUid_acl" {
		return parseLineAcl(line)
	}
	results["acl"] = strconv.Itoa(0)
	results["url"] = strings.TrimSpace(lparts[1])
	iphost := strings.Split(lparts[2], "/")
	results["ipaddr"] = iphost[0]
	results["hostname"] = iphost[1]
	results["username"] = strings.TrimSpace(lparts[3])
	if isDebug {
		log.Printf("[WEBFILTERING] - - - parseLine - - - PROTO <%s>", strings.TrimSpace(lparts[4]))
	}
	results["proto"] = strings.TrimSpace(lparts[4])
	SelfProtocol = results["proto"]
	for _, lines := range lparts {
		if strings.Index(lines, "=") > 2 {
			chop := strings.Split(lines, "=")
			results[chop[0]] = chop[1]
		}
	}
	return results

}
func parseLineAcl(line string) map[string]string {
	results := map[string]string{}
	lparts := strings.Split(strings.TrimRight(line, "\n"), " ")
	results["acl"] = strconv.Itoa(1)
	results["username"] = strings.TrimSpace(lparts[2])
	results["ipaddr"] = strings.TrimSpace(lparts[3])
	results["mac"] = strings.TrimSpace(lparts[4])
	results["forwardedfor"] = strings.TrimSpace(lparts[5])
	xForward := strings.TrimSpace(lparts[5])
	results["domain"] = strings.TrimSpace(lparts[6])
	results["sni"] = strings.TrimSpace(lparts[7])
	ssni := strings.TrimSpace(lparts[7])
	//user_cert := strings.TrimSpace(lparts[8])
	//notes := strings.TrimSpace(lparts[9])
	//server_ip := strings.TrimSpace(lparts[10])
	//server_fqdn := strings.TrimSpace(lparts[11])
	results["proto"] = strings.TrimSpace(lparts[12])
	SelfProtocol = results["proto"]
	if len(xForward) > 3 {
		results["ipaddr"] = xForward
	}
	if len(ssni) > 3 {
		results["domain"] = ssni
	}
	results["myport"] = strconv.Itoa(3128)
	results["myip"] = "127.0.0.1"
	results["hostname"] = ""
	results["url"] = strings.TrimSpace(lparts[6])

	return results
}

func ufdbGuardClient(id string, line string, proxyUrl string, sitename string, clientIp string, clientHostname string, clientMac string, proxyIP string, proxyPort int) string {
	isBreak := false
	if strings.Index(line, "srn=WHITE") > 10 {
		isBreak = true
	}
	if strings.Index(line, "rblpass=yes") > 10 {
		isBreak = true
	}
	if strings.Index(line, "webfilter=pass") > 10 {
		isBreak = true
	}
	category := 0
	categoryName := ""
	//var LogText string
	if isBreak {
		if isDebug {
			log.Printf("%s [WEBFILTERING] Breakable!", sitename)
		}
		return ""
	}
	if isDebug {
		log.Printf("%s [WEBFILTERING] rules with %s [%s] user=%s", sitename, proxyUrl, sitename, SelfUsername)
	}
	//LogText = fmt.Sprintf("sitename=\"%s\" src=\"%s\" host=\"%s\" user=\"%s\" mac=\"%s\" proxy=\"%s:%d\"", sitename, clientIp, clientHostname, SelfUsername, clientMac, proxyIP, proxyPort)
	if ufdbguard.Process(proxyUrl, sitename, clientIp, clientHostname, SelfUsername, clientMac, proxyIP, proxyPort) {
		if ufdbguard.SelfInactiveService {
			//LogText = ""
			if isDebug {
				log.Printf("%s [WEBFILTERING] INACTIVE SERVICE!", sitename)
				return ""
			}

		}
		SelfWebfilterRuleName = ufdbguard.SelfWebfilterRuleName
		if ufdbguard.SelfCategory == 0 {
			category, categoryName = categorization.GetCategories(sitename)
		}
		if category == 0 {
			category = ufdbguard.SelfCategory
			categoryName = ufdbguard.SelfCategoryName
		}
		Selfcategory = category
		SelfcategoryName = categoryName
		redirect := buildErrorPage()
		if isDebug {
			log.Printf("%s [WEBFILTERING] OUT OF <%s>", sitename, fmt.Sprintf("%s %s %s \n", id, SelfTokenOutput, redirect))
		}
		return fmt.Sprintf("%s %s %s \n", id, SelfTokenOutput, redirect)
	}
	return ""
}
