package shields

import (
	"fmt"
	"github.com/d3mondev/resolvermt"
	"github.com/techoner/gophp"
	"log"
	"reflect"
	"regexp"
	"server/categorization"
	"server/internal"
	"strconv"
	"strings"
	"time"
	"unicode"
)

var (
	SelfKsrnPorn                bool
	SelfDisableAdvert           bool
	SelfHatredAndDiscrimination bool
	isDebugShields              bool
	SelfShieldIpaddr            string
	SelfShieldUsername          string
	SelfShieldMac               string
	SelfError                   string
	SelfCategory                int
	SelfCategoryName            string
	SelfAction                  string
	SelfSitename                string
	SelfStartTime               time.Time
	SelfLocalCache              map[string]string
	SelfKsrnLicense             bool
	SelfKsrnEnable              bool
	SelfQueryIPAddr             bool
	SelfKSRNEmergency           bool
	SelfTheShieldLogsQueries    bool
	SelfDurationText            string
	SelfHit                     int
	SelfLocalCacheCount         int
	SelfTheShieldsCguard        bool
)

func InitShileds(debub bool) {
	SelfKsrnPorn = internal.GetSocketInfoBool("KsrnPornEnable")
	SelfDisableAdvert = internal.GetSocketInfoBool("KsrnDisableAdverstising")
	SelfHatredAndDiscrimination = internal.GetSocketInfoBool("KsrnHatredEnable")
	isDebugShields = debub
	SelfKsrnEnable = internal.GetSocketInfoBool("KSRNEnable")
	SelfQueryIPAddr = internal.GetSocketInfoBool("KsrnQueryIPAddr")
	SelfKSRNEmergency = internal.GetSocketInfoBool("KSRNEmergency")
	SelfKsrnLicense = internal.GetSocketInfoBool("KSRN_LICENSE")
	SelfTheShieldsCguard = internal.GetSocketInfoBool("TheShieldsCguard")
	SelfLocalCache = make(map[string]string)
	if internal.GetArticaGoldLicense() {
		SelfKsrnLicense = true
	}
	if isDebugShields {
		SelfTheShieldLogsQueries = true
	}
}

//TODO Change DNS System
func GetHost(sitename string) string {
	resolvers := []string{
		categorization.SelfDns1,
		categorization.SelfDns2,
	}
	url := strings.TrimSuffix(sitename, "\n")
	domains := []string{
		url,
	}

	client := resolvermt.New(resolvers, 3, 1000, 50)
	defer client.Close()
	results := client.Resolve(domains, resolvermt.TypeA)

	for _, record := range results {
		if isDebugShields {
			log.Printf("%s: * * * HOST Resolution: [%s] * * *", sitename, record.Answer)
		}
		return record.Answer
	}
	return ""
}

func Operate(sitename string) bool {
	log.Printf("THE SHIELDS DEBUG MODE IS %t", isDebugShields)
	SelfStartTime = time.Now()
	SelfError = ""
	SelfCategory = 0
	SelfAction = ""
	SelfSitename = sitename
	if categorization.IsArpa(sitename) {
		SelfSitename = categorization.SelfStripaddr
		if categorization.IsPrivateIp(SelfSitename) {
			SelfCategory = 82
			SelfCategoryName = categorization.CategoryIntToString(SelfCategory)
			return false
		}
	}
	fullCache := fmt.Sprint("SRNRESULTS:%s", sitename)
	scacheKey := fmt.Sprint("SRN_CACHE_WHITE:%s", sitename)
	//internal.Increment("KSRN_REQUESTS")
	internal.Inc("KSRN_REQUESTS", "1")
	if isDebugShields {
		log.Printf("* * * * * * * * * * * * * * * * O P E R A T E * * * * * * * * * * * * *")
		log.Printf("%s ANALYZE...", sitename)
	}
	memWhite := getCached(sitename)
	if memWhite != "" {
		SelfError = ""
		SelfAction = "WHITELIST"
		localCategories(sitename)
		SelfCategory, _ = categorization.GetCategories(sitename)
		if isDebugShields {
			log.Printf("%s [DEBUG]: PASS  HIT whitelisted (Cache), aborting %s ", sitename, internal.TimeTrack(SelfStartTime))

		}
		return false
	}
	if categorization.AdminWhitelist(sitename, false) {
		if isDebugShields {
			log.Printf("%s: [DEBUG] MISS whitelisted, aborting", sitename)

		}
		setCache(scacheKey, "1")
		SelfError = ""
		SelfAction = "WHITELIST"
		localCategories(sitename)
		if SelfCategory == 0 {
			SelfCategory, _ = categorization.GetCategories(sitename)
		}
		if isDebugShields {
			log.Printf("%s: [DEBUG] MISS whitelisted, aborting %s", sitename, internal.TimeTrack(SelfStartTime))

		}
		return true
	}
	if !SelfKsrnLicense {
		SelfError = "LICENSE_ERROR"
		SelfAction = "PASS"
		localCategories(sitename)
		if isDebugShields {
			log.Printf("%s: [ERROR]: Not a valid license %s", sitename, internal.TimeTrack(SelfStartTime))
		}
		return false
	}
	if !SelfKsrnEnable {
		SelfError = "DISABLED"
		SelfAction = "PASS"
		localCategories(sitename)
		if isDebugShields {
			log.Printf("%s: [ERROR]: Module is Disabled  %s", sitename, internal.TimeTrack(SelfStartTime))
		}
		return false
	}

	if !SelfQueryIPAddr {
		matches, _ := regexp.MatchString("^[0-9\\.]+$", sitename)
		if matches {
			SelfError = "IPADDR"
			SelfAction = "PASS"
			localCategories(sitename)
			if isDebugShields {
				log.Printf("%s: [DEBUG]: is an IP Address  %s", sitename, internal.TimeTrack(SelfStartTime))
			}
			return false
		}
	}

	if SelfKSRNEmergency {
		SelfError = "EMERGENCY"
		SelfAction = "PASS"
		localCategories(sitename)
		if isDebugShields {
			log.Printf("%s: [DEBUG]: WARNING... Emergency Enabled %s", sitename, internal.TimeTrack(SelfStartTime))
		}
		return false
	}

	if categorization.FixedWhitelist(sitename) {
		//internal.Append(scacheKey, "1")
		setCache(scacheKey, "1")
		SelfError = ""
		SelfAction = "PASS"
		localCategories(sitename)
		if SelfCategory == 0 {
			SelfCategory, _ = categorization.GetCategories(sitename)
		}
		if isDebugShields {
			log.Printf("%s: [DEBUG]: MISS whitelisted, aborting %s", sitename, internal.TimeTrack(SelfStartTime))
		}
		return true
	}

	resultIP := getCached(fullCache)
	if resultIP != "" {
		if isDebugShields {
			log.Printf("%s: [DEBUG]: get_cache(%s) HIT = %s", sitename, fullCache, internal.TimeTrack(SelfStartTime))
		}
	}
	SelfHit = 0
	if resultIP == "" {
		category := localCategories(sitename)
		if isDebugShields {
			log.Printf("%s MISS Local category=[%d] %s", sitename, category, internal.TimeTrack(SelfStartTime))
		}
		if category > 0 {
			resultIP = fmt.Sprintf("127.12.%s.1", sitename)
			setCache(fullCache, resultIP)
			results := understandIP(resultIP)
			runStats(sitename)
			if isDebugShields {
				log.Printf("%s ENGINE=%s category[%d] %s %s after The Shields Query", sitename, SelfAction, SelfCategory, results, internal.TimeTrack(SelfStartTime))
			}
		}
		if isDebugShields {
			log.Printf("%s IP=%s", sitename, resultIP)
		}
		if resultIP == "" {
			resultIP = theShieldQuery(sitename)
			if isDebugShields {
				log.Printf("%s CLOUD --> the_shield_query(%s) = %s", sitename, sitename, resultIP)
			}
		}
		if resultIP == "" {
			SelfAction = "PASS"
			if isDebugShields {
				log.Printf("%s result_ip is None", sitename)
			}
			return false
		}
		if isDebugShields {
			log.Printf("%s theshield.operate HIT [%s]", sitename, resultIP)
		}
		setCache(fullCache, resultIP)
	} else {
		SelfHit = 1
		if isDebugShields {
			log.Printf("%s theshield.operate HIT [%s]", sitename, resultIP)
		}
	}
	statTime := time.Now()
	_ = understandIP(resultIP)
	runStats(sitename)
	if isDebugShields {
		log.Printf("FINAL:%s", internal.TimeTrack(statTime))
	}
	return true
}

func theShieldQuery(sitename string) string {
	statTime := time.Now()
	if SelfTheShieldsCguard {
		if isDebugShields {
			log.Printf("[DEBUG]: --> query_cguard(%s)", sitename)
		}
		increaseStatsLine()
		resultIP := queryCguard(sitename)
		if resultIP != nil {
			return *resultIP
		}
	}
	encodedPart := encryptUpper(sitename)
	searchQuery := fmt.Sprintf("%s.%s", encodedPart, "crdf.artica.center")
	if isDebugShields {
		log.Printf("[DNS]: %s --> %s %s", sitename, searchQuery, "[QUERY]")
	}
	increaseStatsLine()
	//TODO Change DNS System
	resolvers := []string{
		categorization.SelfDns1,
		categorization.SelfDns2,
	}
	url := strings.TrimSuffix(searchQuery, "\n")
	domains := []string{
		url,
	}

	client := resolvermt.New(resolvers, 3, 1000, 50)
	defer client.Close()
	results := client.Resolve(domains, resolvermt.TypeA)
	for _, record := range results {
		if isDebugShields {
			log.Printf("DNS %s category=%s MISS shield.query_cloud %s", sitename, record.Answer, internal.TimeTrack(statTime))
		}
		return record.Answer

	}
	return ""
}

func encryptUpper(plaintext string) string {
	shift := 3
	encryption := ""
	text := plaintext
	text = strings.ToUpper(text)
	text = strings.ReplaceAll(text, ".", "chr2")
	//fmt.Println(text)
	for _, c := range text {
		if unicode.IsUpper(c) {
			//c_unicode := int(c)
			c_index := int(c) - int('A')
			new_index := (c_index + shift) % 26
			new_unicode := new_index + int('A')
			new_character := rune(new_unicode)
			//fmt.Printf("%c\n", new_character)
			encryption = encryption + string(new_character)
		} else {
			encryption += string(c)
		}

	}
	return strings.ToLower(encryption)
}

func queryCguard(sitename string) *string {
	key := fmt.Sprintf("query_cguard:%s", sitename)
	detects := []int{5026, 5066, 5113, 5001, 5058, 5048, 5035, 5096, 5019, 5043, 5045, 5017, 5010, 5002, 5036, 5042, 5005, 5003, 5029, 5030, 5024, 5027, 5107, 5111, 5093, 5033, 5104, 5101, 5114}
	category := getCached(key)
	if category != "" {
		if isDebugShields {
			log.Printf("DNS %s category=%s HIT shield.query_cguard", sitename, category)
		}
		categoryID, _ := strconv.Atoi(category)
		if categoryID == 0 {
			return nil
		}
		for _, val := range detects { // Loop
			if val == categoryID {

				break
				result := fmt.Sprintf("127.96.0.%s", category)
				return &result
			}
		}
		return nil
	}
	categoryID := categorization.GetCategoriesCguard(sitename)
	if isDebugShields {
		log.Printf("DNS %s category=%d MISS shield.query_cguard", sitename, categoryID)
	}
	setCache(key, string(categoryID))
	if categoryID == 0 {
		return nil
	}
	for _, val := range detects { // Loop
		if val == categoryID {
			break
			result := fmt.Sprintf("127.96.0.%s", category)
			return &result
		}
	}
	if isDebugShields {
		log.Printf("[DEBUG]: --> %s < == %s", sitename, "SKIP")
	}
	return nil

}

func increaseStatsLine() {
	statsKey := fmt.Sprintf("SRNSTATSLINE:%s", internal.GetTimeDelta(10, 1))
	//internal.Increment(statsKey)
	internal.Inc(statsKey, "1")

}
func runStats(sitename string) bool {
	delta := internal.GetTimeDelta(10, 1)
	time10mn := internal.GetTimeDelta(10, 2)
	if SelfAction == "REAFFECTED" {
		return true
	}
	if SelfAction == "PASS" {
		return true
	}
	if SelfCategory == 0 || SelfCategoryName == "" {
		SelfCategory, SelfCategoryName = categorization.GetCategories(sitename)
	}
	SelfShieldUsername = ""
	SelfShieldMac = ""
	duration := time.Since(SelfStartTime).Milliseconds()
	filename := fmt.Sprintf("/var/log/squid/%s.ksrn", delta)
	msg := fmt.Sprintf("%s|%s|%s|%s|%d|%s|%s|%d", time10mn, SelfShieldUsername, SelfShieldIpaddr, SelfShieldMac, SelfCategory, sitename, SelfAction, duration)
	internal.Logfile(filename, msg)
	internal.Inc("KSRN_DETECTED", "1")
	if isDebugShields {
		log.Printf("[DETECTED]: From %s [%s] (%s) category: %s (%s) to website %s", SelfShieldUsername, SelfShieldIpaddr, SelfShieldMac, SelfCategoryName, SelfAction, sitename)
	}
	return true
}

func understandIP(ip string) bool {
	sitename := SelfSitename
	var matches bool
	var rs []string
	var re *regexp.Regexp
	if isDebugShields {
		log.Printf("%s [%s]: Check Porn ?: %t", sitename, ip, SelfKsrnPorn)
	}
	re = regexp.MustCompile("^127\\.96\\.0\\.([0-9]+)")
	matches = re.MatchString(ip)
	if matches {
		if isDebugShields {
			log.Printf("%s: [DEBUG] [%s] CGuard detected", sitename, ip)
		}
		rs = re.FindStringSubmatch(ip)
		sporn := []int{5113, 5001, 5058, 5048}
		shaines := []int{5033, 5104, 5101, 5114}
		resultCat, _ := strconv.Atoi(rs[1])
		if SelfDisableAdvert {
			if resultCat == 5026 || resultCat == 5066 {
				if isDebugShields {
					log.Printf("%s: [DEBUG] [%d] Exclude (Privacy Shield disabled)", sitename, resultCat)
				}
				SelfAction = "PASS"
				return true
			}
		}
		if !SelfKsrnPorn {
			for _, val := range sporn { // Loop
				if val == resultCat {
					SelfAction = "PASS"
					if isDebugShields {
						log.Printf("%s: [DEBUG] [%d] Exclude (Porn Shield disabled)", sitename, resultCat)
					}
					break
					return true
				}
			}
		}
		if !SelfHatredAndDiscrimination {
			for _, val := range shaines { // Loop
				if val == resultCat {
					SelfAction = "PASS"
					if isDebugShields {
						log.Printf("%s: [DEBUG] [%d] Exclude (Hate and discrimination)", sitename, resultCat)
					}
					break
					return true
				}
			}
		}
		SelfAction = "CGUARD"
		return true
	}
	re = regexp.MustCompile("127\\.12\\.([0-9]+)\\.1")
	matches = re.MatchString(ip)
	if matches {
		SelfAction = "PASS"
		badCarz := []int{6, 7, 10, 72, 92, 105, 111, 135, 132, 109, 5, 143}
		hatred := []int{130, 148, 149, 150, 140}
		rs = re.FindStringSubmatch(ip)
		resultCat, _ := strconv.Atoi(rs[1])
		if SelfCategory == 0 {
			SelfCategory, SelfCategoryName = categorization.GetCategories(sitename)
			if isDebugShields {
				log.Printf("%s category==0 ??? with [%s] retreive it ! -> %d", sitename, ip, SelfCategory)
			}
		}
		if isDebugShields {
			log.Printf("%s [%d]: ARTICA", sitename, resultCat)
		}
		if SelfCategory > 0 {
			if !SelfKsrnPorn {
				if resultCat == 109 || resultCat == 132 {
					if isDebugShields {
						log.Printf("%s [%d]: ARTICA PORN EXCLUDE", sitename, resultCat)
					}
					SelfAction = "PASS"
					if isDebugShields {
						log.Printf("%s: [DEBUG] [%d] Exclude (Porn Shield disabled)", sitename, resultCat)
					}
					return true
				}
			}
			if SelfDisableAdvert {
				if resultCat == 5 || resultCat == 143 {
					if isDebugShields {
						log.Printf("%s: [DEBUG] [%d] Exclude (Privacy Shield disabled)", sitename, resultCat)
					}
					SelfAction = "PASS"
					return true
				}
			}
			if !SelfHatredAndDiscrimination {
				for _, val := range hatred { // Loop
					if val == resultCat {
						SelfAction = "PASS"
						if isDebugShields {
							log.Printf("%s: [DEBUG] [%d] Exclude (Hate and discrimination)", sitename, resultCat)
						}
						break
						return true
					}
				}
			}
			for _, val := range badCarz { // Loop
				if val == resultCat {
					SelfAction = "ARTICA"
					if isDebugShields {
						log.Printf("%s [%d]: ARTICA DETECTED", sitename, resultCat)
					}
					break
					return true
				}
			}
		}
		return true
	}

	if ip == "127.10.1.0" {
		SelfCategory = 92
		SelfAction = "MALWAREURL_MALWARES"
		return true
	}

	if ip == "127.10.2.0" {
		SelfCategory = 105
		SelfAction = "MALWAREURL_PHISHING"
		return true
	}
	re = regexp.MustCompile("127\\.10\\.1\\.([0-9]+)")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "CLOUDFLARE"
		return true
	}

	re = regexp.MustCompile("127\\.10\\.2\\.([0-9]+)")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "CLOUDFLARE"
		return true
	}

	if ip == "127.3.1.0" {
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "CLOUDFLARE"
		return true
	}
	if ip == "127.3.2.0" {
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "CLOUDFLARE"
		return true
	}
	re = regexp.MustCompile("127\\.3\\.1\\.([0-9]+)")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "CLOUDFLARE"
		return true
	}
	re = regexp.MustCompile("127\\.3\\.2\\.([0-9]+)")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "REAFFECTED"
		return true
	}
	if ip == "127.4.0.0" {
		SelfCategory = 92
		SelfAction = "GENERIC"
		return true
	}
	re = regexp.MustCompile("127\\.4\\.0\\.([0-9]+)")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "GENERIC"
		return true
	}
	if ip == "127.2.0.0" {
		SelfCategory = 92
		SelfAction = "GOOGLE"
		return true
	}

	re = regexp.MustCompile("127\\.2\\.0\\.([0-9]+)")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "GOOGLE"
		return true
	}

	re = regexp.MustCompile("^127\\.254\\.0\\.([0-9]+)$")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "KASPERSKY"
		return true
	}
	if ip == "127.5.0.0" {
		if SelfDisableAdvert {
			SelfCategory = 5
			SelfAction = "PASS"
			return true
		}
		SelfCategory = 5
		SelfAction = "ADGUARD"
		return true
	}
	re = regexp.MustCompile("^127\\.5\\.0\\.([0-9]+)$")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		category, _ := strconv.Atoi(rs[1])
		if SelfDisableAdvert {
			if category == 5 || category == 143 {
				SelfCategory = 5
				SelfAction = "PASS"
				return true
			}
		}
		SelfCategory = category
		SelfAction = "ADGUARD"
		return true
	}
	if ip == "127.253.0.0" {
		SelfCategory = 92
		SelfAction = "QUAD9"
		return true
	}
	re = regexp.MustCompile("^127\\.253\\.0\\.([0-9]+)$")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "ADGUARD"
		return true
	}
	re = regexp.MustCompile("^0\\.0\\.0\\.([0-9]+)$")
	matches = re.MatchString(ip)
	if matches {
		rs = re.FindStringSubmatch(ip)
		SelfCategory, _ = strconv.Atoi(rs[1])
		SelfAction = "PASS"
		return true
	}
	SelfAction = "PASS"
	return false
}

func getCached(domain string) string {
	if _, ok := SelfLocalCache[domain]; ok {
		return SelfLocalCache[domain]
	}
	smd5 := fmt.Sprint("SHIELD.class.%s", internal.Md5string(domain))
	if val, _ := internal.Fetch(smd5); val != nil {
		str := *val
		return str
	}
	return ""
}

func setCache(domain string, category string) {
	SelfLocalCache[domain] = category
	SelfLocalCacheCount = SelfLocalCacheCount + 1
	smd5 := fmt.Sprintf("SHIELD.class.%s", internal.Md5string(domain))
	internal.Append(smd5, category)
	if SelfLocalCacheCount > 1500 {
		SelfLocalCache = make(map[string]string)
		SelfLocalCacheCount = 0
	}
}

func localCategories(sitename string) int {
	if len(sitename) == 0 {
		return 0
	}
	category, _ := categorization.GetCategories(sitename)
	SelfCategory = category
	if SelfCategory == 0 {
		return 0
	}
	return SelfCategory
	//TODO Confirm rest of func
}

func WriteStats(category int, sitename string, provider string, duration int) {
	delta := internal.GetTimeDelta(10, 1)
	time10mn := internal.GetTimeDelta(10, 2)
	filename := fmt.Sprintf("/var/log/squid/%s.ksrn", delta)
	msg := fmt.Sprintf("%s|%s|%s|%s|%d|%s|%s|%d", time10mn, SelfShieldUsername, SelfShieldIpaddr, SelfShieldMac, category, sitename, provider, duration)
	internal.Logfile(filename, msg)
}

func CountUsers() {
	if len(SelfShieldIpaddr) == 0 {
		if isDebugShields {
			log.Printf("SelfShieldIpaddr null")
		}
		return
	}
	if SelfShieldIpaddr == "127.0.0.1" {
		log.Printf("SelfShieldUsername is 127.0.0.1")
		return
	}

	time10min := internal.GetTimeDelta(10, 2)
	time10min = strings.ReplaceAll(time10min, " ", "_")
	results := map[string]map[string]int{}
	//results["IPADDR"] = map[string]int{}
	//results["mac"] = map[string]int{}
	//results["username"] = map[string]int{}
	skey := fmt.Sprintf("CountUsers.%s", time10min)
	if isDebugShields {
		log.Printf("%s [CountUsers]: %s", time10min, skey)
	}
	var cc int
	if val, err := internal.Get(skey); err == nil {
		mount, err := gophp.Unserialize([]byte(val))
		if err != nil {
			if isDebugShields {
				log.Printf("[CountUsers]: Failed to unserialize: ", err)
				results = make(map[string]map[string]int)
			}
		}
		log.Printf("Array is %v", mount)
		if dataList, ok := mount.(map[string]interface{}); ok {
			for x, data := range dataList {
				if reflect.ValueOf(data).Len() == 0 {
					continue
				}
				for k, v := range data.(map[string]interface{}) {
					if x == "IPADDR" {
						if _, ok := results["IPADDR"]; !ok {
							if isDebugShields {
								log.Printf("%s [CountUsers]: creating IPADDR key", time10min)
							}
							results["IPADDR"] = map[string]int{}
						}
					}

					if x == "mac" {
						if _, ok := results["mac"]; !ok {
							if isDebugShields {
								log.Printf("%s [CountUsers]: creating Mac key", time10min)
							}
							results["mac"] = map[string]int{}
						}
					}
					if x == "username" {
						if _, ok := results["username"]; !ok {
							if isDebugShields {
								log.Printf("%s [CountUsers]: creating Username key", time10min)
							}
							results["username"] = map[string]int{}
						}
					}
					results[x][k] = v.(int)
				}
			}
		} else {
			log.Printf("[CountUsers]: Failed to map Array: ", err)
			results = make(map[string]map[string]int)
		}

	} else {
		results = make(map[string]map[string]int)
	}
	if _, ok := results["IPADDR"]; !ok {
		if isDebugShields {
			log.Printf("%s [CountUsers]: creating IPADDR key", time10min)
		}
		results["IPADDR"] = map[string]int{}
	}

	if _, ok := results["IPADDR"][SelfShieldIpaddr]; !ok {
		cc = 1
		results["IPADDR"][SelfShieldIpaddr] = cc
	} else {
		cc = results["IPADDR"][SelfShieldIpaddr]
		cc = cc + 1
		results["IPADDR"][SelfShieldIpaddr] = cc
	}
	if isDebugShields {
		log.Printf("%s [CountUsers]: ipaddr=%s count=%d", time10min, SelfShieldIpaddr, cc)
	}

	if len(SelfShieldUsername) > 1 {
		if _, ok := results["username"]; !ok {
			if isDebugShields {
				log.Printf("%s [CountUsers]: creating Username key", time10min)
			}
			results["username"] = map[string]int{}
		}
		if _, ok := results["username"][SelfShieldUsername]; !ok {
			cc = 1
			results["username"][SelfShieldUsername] = cc
		} else {
			cc = results["username"][SelfShieldUsername]
			cc = cc + 1
			results["username"][SelfShieldUsername] = cc
		}
		if isDebugShields {
			log.Printf("%s [CountUsers]: username=%s count=%d", time10min, SelfShieldUsername, cc)
		}
	}

	if len(SelfShieldMac) > 0 {
		if _, ok := results["mac"]; !ok {
			if isDebugShields {
				log.Printf("%s [CountUsers]: creating Mac key", time10min)
			}
			results["mac"] = map[string]int{}
		}
		if _, ok := results["mac"][SelfShieldMac]; !ok {
			cc = 1
			results["mac"][SelfShieldMac] = cc
		} else {
			cc = results["mac"][SelfShieldMac]
			cc = cc + 1
			results["mac"][SelfShieldMac] = cc
		}
		if isDebugShields {
			log.Printf("%s [CountUsers]: mac=%s count=%d", time10min, SelfShieldMac, cc)
		}
	}
	out, _ := gophp.Serialize(results)
	internal.Set(skey, string(out), 3600)
	if isDebugShields {
		log.Printf("%s [CountUsers]: %s SAVED SUCCESS", time10min, skey)
	}

}
