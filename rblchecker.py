import sys
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
from optparse import OptionParser
import requests, re
import logging
from postgressql import *
import datetime
import socket
import traceback as tb
global whywhy

try:
    import dns.resolver
    resolver = dns.resolver.Resolver()
    resolver.nameservers = ['1.1.1.1','8.8.8.8']
    resolver.timeout = 0.90
    resolver.lifetime = 0.90
except:
    print('Error : Unable To Load dns Module.')
    print('For python3 : pip3 install dnspython3')
    print('For python2 : pip install dnspython3')
    sys.exit(0)



def rblcheck(searchIp):

    rblDict = {'b.barracudacentral.org': 'b.barracudacentral.org',
               'bl.spamcop.net': 'bl.spamcop.net',
               'zen.spamhaus.org': 'zen.spamhaus.org',
               'dnsbl.cobion.com': 'dnsbl.cobion.com',
               'hostkarma.junkemailfilter.com':'hostkarma.junkemailfilter.com',
               'bl.suomispam.net':'bl.suomispam.net',
               'bl.drmx.org':'bl.drmx.org',
               'spam.spamrats.com':'spam.spamrats.com',
               'bl.nosolicitado.org':' bl.nosolicitado.org',
               'dnsbl-1.uceprotect.net':'dnsbl-1.uceprotect.net'
               }


    for rblOrg in rblDict:

        ipRev = '.'.join(searchIp.split('.')[::-1])
        searchQuery = ipRev + '.' + rblOrg
        try:
            resolver.query(searchQuery, 'A')
            print("[ FOUND ] %s in %s" % (searchIp, rblOrg))
            return True
        except:
            continue

    print("%s Nothing found in RBLs" % searchIp)


def CheckWhitelist(hostname):
    if len(hostname) == 0: return False

    matches = re.search('\.(idcontact|atos|mcdlv|mcsv|rsgsv|secureserver|sendgrid)\.net', hostname)
    if matches: return True
    matches = re.search('\.activemailer\.pro', hostname)
    if matches: return True
    matches = re.search('\.(laredoute|groupemoniteur|iroquois|certilience)\.fr$', hostname)
    if matches: return True
    matches = re.search('\.(mail\..*?\.yahoo|medallia|srvsaas|stoneshot|credit-suisse|5asec|lufthansa|twitter|google|capgemini|lastminute|mimecast|msgapp|smtp25|bnpparibas)\.com$', hostname)
    if matches: return True
    matches = re.search('\.(expertsender|planetinfos)\.(fr|com)', hostname)
    if matches: return True
    matches = re.search('\.(smtpcorp.com|smtp2go|teamviewer|wetransfer|lafourchette|jd|aa|linkedin|placedestendances|ags-backup|meyclub|exaprobe|saint-gobain|3m|worldline)\.com$', hostname)
    if matches: return True
    matches = re.search('\.(mailin|cap-audience|leboncoin|espace-feminin|aspinfo|planetinfos|powermail|idweb|sgautorepondeur|services\.sfr|easiware|la-boite-immo|allianz)\.fr', hostname)
    if matches: return True
    matches = re.search('\.(volvo|sendlabs|mlflow|263xmail|fedex|trendmicro|messagingengine|hostedemail)\.com', hostname)
    if matches: return True
    matches = re.search('\.(marketvolt|iphmx)\.com', hostname)
    if matches: return True
    matches = re.search('\.(mail-out\.ovh|msg\.oleane|kickboxio|odiso|materiel|neolane|security-mail|emsecure|mail\.maxns|open-mailing|emm29|oxi-dedi)\.(net|de)', hostname)
    if matches: return True
    matches = re.search('\.(virtual-expo|indeed|epsl1|data-crypt|iphmx)\.com', hostname)
    if matches: return True
    matches = re.search('\.(emarsys|publicisgroupe)\.net', hostname)
    if matches: return True
    matches = re.search('\.outspot\.be', hostname)
    if matches: return True
    matches = re.search('mail\.(maxony|gandi|1e100|hubspotstarter)\.net', hostname)
    if matches: return True
    matches = re.search('\.(victorypackaging|salesforce|sparkpost|hubspotemail|hubspot|mailinblack|outbound\.protection\.outlook|canva|bitrix24|fnac|bazarchic)\.com', hostname)
    if matches: return True
    matches = re.search('\.(meteoconsult|pulsation|net-entreprises|caissedesdepots|groupe-rueducommerce|promod|agoramail)\.fr', hostname)
    if matches: return True
    matches = re.search('(advice\.hmrc\.gov\.uk|avonandsomerset\.police\.uk|blaby\.gov\.uk|braintree\.gov\.uk|bromley\.gov\.uk|bromsgroveandredditch\.gov\.uk|buckfastleigh\.gov\.uk|calderbridge\.n-lanark\.sch\.uk|calderdale\.gov\.uk|cambridgeshire\.gov\.uk|carmarthenshire\.gov\.uk|causewaycoastandglens\.gov\.uk|cheshire\.gov\.uk|cheshireeast\.gov\.uk|cheshiresharedservices\.gov\.uk|cheshirewestandchester\.gov\.uk|chesterfield\.gov\.uk|cjsm\.net|colerainebc\.gov\.uk|companieshouse\.gov\.uk|crawley\.gov\.uk|cumbria\.police\.uk|daventrydc\.gov\.uk|dumgal\.gov\.uk|dyobmr\.btconnect\.com|dyslmr\.btconnect\.com|eastbourne\.gov\.uk|eastdunbarton\.gov\.uk|eastleigh\.gov\.uk|eastrenfrewshire\.gov\.uk|eastriding\.gov\.uk|email\.education\.gov\.uk|epims\.ogc\.gov\.uk|eppingforestdc\.gov\.uk|eprompts\.hmrc\.gov\.uk|ext\.ons\.gov\.uk|gloucester\.gov\.uk|greenwich\.gov\.uk|gro-extranet\.homeoffice\.gov\.uk|halton\.gov\.uk|hanover\.org\.uk|harrogate\.gov\.uk|herefordshire\.gov\.uk|hinckley-bosworth\.gov\.uk|ipswich\.gov\.uk|kettering\.gov\.uk|leeds\.gov\.uk|lewes-eastbourne\.gov\.uk|lookinglocal\.gov\.uk|luton\.gov\.uk|medway\.gov\.uk|metoffice\.gov\.uk|milton-keynes\.gov\.uk|mk-mx-1\.mail\.tiscali\.co\.uk|mk-mx-2\.mail\.tiscali\.co\.uk|moray-edunet\.gov\.uk|nafn\.gov\.uk|newport\.gov\.uk|news\.local\.gov\.uk|nics\.gov\.uk|northampton\.gov\.uk|northamptonshire\.gov\.uk|north-herts\.gov\.uk|northlan\.gov\.uk|oldham\.gov\.uk|orkney\.gov\.uk|outmail\.warwickdc\.gov\.uk|plymouth\.gcsx\.gov\.uk|plymouthmuseum\.gov\.uk|qualitylifestyleltd\.co\.uk|rdobmr\.btconnect\.com|rdslmr\.btconnect\.com|redditchbc\.gov\.uk|resilience\.gov\.uk|rother\.gov\.uk|royalgreenwich\.gov\.uk|shropshire\.gov\.uk|smtp-out\.passportapplication\.service\.gov\.uk|southend\.gov\.uk|southribble\.gov\.uk|southsomerset\.gov\.uk|sstaffs\.gov\.uk|stalbans\.gov\.uk|tameside\.gov\.uk|telford\.gov\.uk|tendringdc\.gov\.uk|tfl\.gov\.uk|torridge\.gov\.uk|trafford\.gov\.uk|ukho\.gov\.uk|walthamabbey-tc\.gov\.uk|wirral\.gov\.uk|woking\.gov\.uk|worcestershire\.gov\.uk)$',hostname)
    if matches: return True
    matches = re.search('(c05-ltd\.co\.uk|gdfsuezenergiesfrance2\.fr|gdfsuezenergiesfrance1\.fr|gdfsuezpro-formulaireopposition\.fr|amigoscartujacenter\.com|comunicacionsilviamarso\.com|conews04\.com|conew03\.com|eservices-laposte\.fr|cabestan\.de|cabestan\.eu|selectour-voyages\.fr|services-bpifrance\.fr|comunicacion-golflamoraleja\.com|lapieshoppeuse\.com|emailccmb\.com|cealiberico\.info|offre-oseo\.com|newslettereducacion\.com|newsletter-aprendemas\.com|newsbarceloviajes\.com|purpleparking-offers\.com|information-oseo\.com|dedicated-marketing\.com|com-jepenseauxautres\.com|enquete-emailetvous\.com|enquetecourrier\.com|emm1\.com|dms39\.com|dms38\.com|dms37\.com|dms36\.com|dms35\.com|dms34\.com|dms33\.com|dms32\.com|dms31\.com|dms30\.com|clubconsommateur\.com|fournisseursexpress\.com|astuclicmail\.com|astucliccourriel\.com|blue4mobility\.com|bleu-ciel-edf\.com|axm1\.com|fondationarc\.org|fondationarc\.net|florajet-news\.com|email-buyshopping\.com|sociabilimel\.com|services-euroquity\.com|events-euroquity\.com|contacts-euroquity\.com|email-rossellbooks\.com|email-wuachin\.com|envio-emails\.info|news-selectour-afat\.com|performingway\.com|dskbank\.info|gdfsuezdolcevita3\.com|gdfsuezpro-formulaireopposition\.net|gdfsuezpro-formulaireopposition\.com|3w-mistergooddeal\.com|actuassurances\.com|emailrtarget\.com|chainedelespoir\.info|ubepro\.com|news-t-a-o\.com|iloveroommate\.com|gdfsuezenergiesfrance1\.com|gdfsuezenergiesfrance\.com|gdfsuezdolcevita1\.com|gdfsuezdolcevita\.com|gdfsuezcegibat2\.com|gdfsuezcegibat\.com|emailetvous\.com|dms-04\.net|crm-citroen-retail\.com|com-emailetvous\.com|cab04\.net|cab02\.net|air-austral\.net|yvesrocher\.ci|xpoonlinepro\.com|magique-promo\.com|melodie-des-offres\.com|monenviedujour\.com|mhm-email\.com|atrevia\.info|psa-corporate-solutions\.com|actu-assurances\.com|3w-webrivage\.com|3w-wanimo\.com|3w-tf1\.com|3w-privileges\.com|3w-cdiscount\.com|crm-peugeot-retail\.com|news-selectour\.com|news-reactivpub\.com|communication-edf\.com)$',hostname)
    if matches: return True
    matches = re.search('(hm1315\.locaweb|voegol)\.com.br', hostname)
    if matches: return True
    matches = re.search('(yapikredi)\.com\.tr$', hostname)
    if matches: return True


    matches = re.search('\.contactlab\.it', hostname)
    if matches: return True
    matches = re.search('\.(dms-01|avgcloud)\.net$', hostname)
    if matches: return True

    matches = re.search('mail\.business\.static\.orange\.', hostname)
    if matches: return True
    matches = re.search('\.smtp-out\.amazonses\.com', hostname)
    if matches: return True
    matches = re.search('\.(gouv|alcatraz)\.fr$', hostname)
    if matches: return True
    matches = re.search('\.pphosted\.com$', hostname)
    if matches: return True
    matches = re.search('\.mail\.[a-z][a-z][0-9]\.yahoo\.com$', hostname)
    if matches: return True
    matches = re.search('\.smtp-out\..*?\.amazonses\.com', hostname)
    if matches: return True



    matches = re.search('smtp\.gateway[0-9]+\.(negloo|visoox)\.com$', hostname)
    if matches: return True
    matches = re.search('\.(acceo-info)\.eu$', hostname)
    if matches: return True
    matches = re.search('sonic.*?\.mail\.bf[0-9]+\.yahoo\.com', hostname)
    if matches: return True
    matches = re.search('\.(saremail|atosorigin|indeed|pimkie|service-now|junomx|myaccessweb)\.com', hostname)
    if matches: return True
    matches = re.search('\.(azimailing|hubspotemail|mcsv|emd01|francite|clonemail|rp01|emailverify|rsgsv|jsmtp|emm23|emm24|e-i)\.net$', hostname)
    if matches: return True
    matches = re.search('\.citobi\.be', hostname)
    if matches: return True
    matches = re.search('eu-smtp-delivery-[0-9]+\.mimecast\.com', hostname)
    if matches: return True

    # ************* COM WHITE
    matches = re.search('\.(aprilasia|b2wdigital|messagestream|adobesystems|cision|scaleway|apple|vimeo|websitewelcome|mailchimp|camaieu|devisprox|soopix|cisco|mycloudmailbox|jobtomealert|playstation)\.com', hostname)
    if matches: return True
    matches = re.search('\.(joob24|dms31|ryanairemail|blablacar|hugavenue|envois-emailing|portablenorthpole|nespresso|jobrapidoalert|mon-financier|inbound\.protection\.outlook|bandcamp|mpsa)\.com$', hostname)
    if matches: return True
    matches = re.search('\.(sparkpostmail|me|tabeci|newsletter-wbp|bilendi|acquia|alibaba|booking|voyageprive|dpam|aquaray|lexisnexis|sonicwall|zdsys|mailissimo|socgen|messagelabs|motorolasolutions|ebay|siteprotect)\.com$', hostname)
    if matches: return True
    matches = re.search('\.(oxi-dedi|mailjet|journaldunet|magical-ears|pitneybowes|msgfocus|hivebrite|hpe|exacttarget|mcafee|enews-airfrance|antispamcloud|eventbrite|ups|dinaserver|pokerstars|fazae|barracuda|gm)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(immo-facile|fiatgroup|key|rbs|sailthru|altospam|vdm|uswitch|volagratis|sodexo|sage|appriver|microsoftemail|antispameurope|dell)\.com$',hostname)
    if matches: return True
    # *********** FR WHITE
    matches = re.search('\.(ikea|eroutage|fiducial|iliad|conforama|bernard|total|idline|maildata|happymails|ingdirect|banque-france|sncf)\.fr$',hostname)
    if matches: return True
    # *************** COM.BR WHITE
    matches = re.search('\.(hoteldaweb)\.com\.br$',hostname)
    if matches: return True

    matches = re.search('\.(avaaz)\.org$', hostname)
    if matches: return True
    matches = re.search('\.santor\.biz', hostname)
    if matches: return True
    matches = re.search('\.rightmove\.co\.uk', hostname)
    if matches: return True
    matches = re.search('\.(jpg|ancv|meteo|inbox|paris|credit-agricole|pro-smtp|contact-everyone|asso|jabatus|artfmprod|evercard|newstank|gpsante|img-adtrans)\.fr$', hostname)
    if matches: return True
    matches = re.search('\.(hub-score|smile-hosting|eml-vinc|eventsoftware|axevision|01m|02m|03m|ecoledirecte|cachecache|renater)\.fr$', hostname)
    if matches: return True
    matches = re.search('\.(groupemagiconline|carrefour|cornut|lcl|newsco|vtech|bva|bnpparibas|lexisnexis|bdv|emailvalide|e-marketing|cartecitroenexclusive)\.fr$',hostname)
    if matches: return True
    matches = re.search('\.(bayer|ispgateway|inxserver|rmx|smtp\.rzone|rapidsoft|volkswagen)\.de$', hostname)
    if matches: return True
    matches = re.search('\.(be-mail|register)\.it$', hostname)
    if matches: return True
    matches = re.search('\.mail\.ru$', hostname)
    if matches: return True
    matches = re.search('\.(mgrt|efm-solution)\.net$', hostname)
    if matches: return True
    matches = re.search('\.(mailcamp)\.nl$',hostname)
    if matches: return True
    matches = re.search('\.(gls-group|flexmail|opentext)\.eu$', hostname)
    if matches: return True
    matches = re.search('\.(gls-group|flexmail|jm-bruneau)\.be$', hostname)
    if matches: return True
    matches = re.search('\.(ate)\.info$', hostname)
    if matches: return True
    matches = re.search('.socketlabs.', hostname)
    if matches: return True
    matches = re.search('\.(briteverify|onvasortir)\.com$', hostname)
    if matches: return True
    matches = re.search('\.sinamail\.sina\.com\.cn$', hostname)
    if matches: return True

    matches = re.search('\.(rakuten)\.tv$',hostname)
    if matches: return True

    matches = re.search('\.(protonmail)\.ch$', hostname)
    if matches: return True
    matches = re.search('dvs[0-9]+\.produhost\.net$', hostname)
    if matches: return True
    matches = re.search('smtp[0-9]+\.msg\.oleane\.net$', hostname)
    if matches: return True
    matches = re.search('\.(exchangedefender|spamtitan|schneider-electric|vadesecure|phplist|cybercartes|accor-mail|jetairways|msn|premierinn|f-secure|arconic|avg|dowjones|marksandspencer|azuresend|yellohvillage|gardnerweb|ctrip|yotpo|xwiki|jpmchase|microsoft|softwaregrp)\.com$', hostname)
    if matches: return True
    matches = re.search('\.static\.cnode\.io', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.cab01\.net', hostname)
    if matches: return True
    matches = re.search('mail-out\.ovh\.net', hostname)
    if matches: return True
    matches = re.search('\.(neolane|everyone|ods2|turbo-smtp|pvmailer|mailchannels|electric|gorgu|as8677|1e100)\.net$', hostname)
    if matches: return True
    matches = re.search('mta[0-9\-]+\.(teneo|msdp1)\.(be|com)$', hostname)
    if matches: return True
    return False


def CheckHostnames(hostname):
    global whywhy
    whywhy=""
    if len(hostname)==0: return False
    whywhy="Line 217"
    matches = re.search('^[0-9\.]+$', hostname)
    if matches: return True

    matches = re.search('\.(excellent-bonuses)\.biz$',hostname)
    if matches: return True
    matches = re.search('^mx.*?\.ml$',hostname)
    if matches: return True

    matches = re.search('\.(mailmnet20)\.co\.uk$',hostname)
    if matches: return True

    # * * * COM * * *
    whywhy = "Line 230"
    matches = re.search('\.(acemsa4|companyorientedchannel|companyorientedgrid|companyorientedroad|businessorientedchannel|companyorientedstream|my-addr|example|qz301|prelieve|kiwoox|vrbtw|jbdgl[0-9]+|crdgl[0-9]+|vf-deal|compute-1\.amazonaws|poweredbycms|instant-souvenir|marketing-premium|majestichelicopter|lesoffresdefou|glorybringerdragon|offresprivilege|pentonfinancialservices|web-plaisir|marionettemaster|lb-lead-client|emsmtp|vf-boutique|ecm87|nextuntil|ecm-cluster|adport02|mktomail|nonstoponline|la-top-reduction13|mdrctr|chien29|dotmailer)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(pullfromthedeep|mirrorinthehouse|hubspotservicehub|monowheelracing|funkytownexpress|somewheredeepinside|gowithplanb|jurassicuniversity|ma-presence-en-ligne|grabandpack-[0-9]+|dc-manager|elbbusinessmail|info-diffusion|crdgl6|reponses-enquetes|breakforthnews|messagesmail|quidditchmasters|mailerinteractive|muchend|lecoindubonplan|mktdns|a2webhosting|infooptinhives)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(eastcoastsynergy|bixfox|beneaththesands|bp-newsletter|bbqplan|mail2deal|mediacomlead|netgoodchallenge|clientoffresdujour|peruvirtual|wtcmlq|takeeffort|mascarteras|easyvidiopro|baywerik|amicisifol|tobkidzanian|mirrorwingdragon|ikexpress)\.com$',hostname)
    if matches: return True

    matches = re.search('\.[0-9a-z]anonymous\.[a-z]+$',hostname)
    if matches: return True

    matches = re.search('\.yrtinsdemoi\.re$', hostname)
    if matches: return True
    matches = re.search('\.whatarewedoingnow\.cf$', hostname)
    if matches: return True

    matches = re.search('\.indigoglobal\.site$', hostname)
    if matches: return True

    matches = re.search('\.thechecker\.co$', hostname)
    if matches: return True

    matches = re.search('\.xprofiler\.ch$',hostname)
    if matches: return True

    matches = re.search('\.srv\.cat$',hostname)
    if matches: return True

    matches = re.search('\.(essai-auto|cote-voiture)\.biz$',hostname)
    if matches: return True
    matches = re.search('\.(pemacom\.re|oxeva\.biz|oxv\.fr|as39104\.net|lefrenchcloud\.com|reagi\.info|oxeva\.com|reagi\.com|oxeva\.info|reagi\.net|oxeva\.net)$',hostname)
    if matches: return True
    # .INFO
    matches = re.search('\.(newsletterversand|shtrawberries|mailgun|artaujourdhui|art-of-the-day|artoftheday|artineurope|arteneurope|ventes-flash|cote-voiture)\.info$',hostname)
    if matches: return True
    matches = re.search('\.(x-mailer|srv2|arcor-ip|tierphysiotherapeut|digitalallstars|waipai|huwo|soservices|rzone\.de|quicksrv)\.de$',hostname)
    if matches: return True
    matches = re.search('\.(monoffre|dusexe|opennews|zingnews|categorydistri|groupnews|silurian|newsviews|tlg006send|newsdigital|newsbio|wp02|mange-vis-profite|newshot|timenews|optimum-fr)\.eu$',hostname)
    if matches: return True
    matches = re.search('\.(vhs)\.club$', hostname)
    if matches: return True
    matches = re.search('\.(kivbf)\.be$', hostname)
    if matches: return True
    matches = re.search('\.(shodan)\.io$', hostname)
    if matches: return True
    matches = re.search('\.(tlet4466)\.us', hostname)
    if matches: return True
    matches = re.search('\.(linkapp|ex10)\.biz', hostname)
    if matches: return True
    matches = re.search('\.(showmonth|lr004|nlmp01|celia-voyance)\.net$', hostname)
    if matches: return True
    matches = re.search('\.(rcforce|vcforce|ybforce)\.pl$', hostname)
    if matches: return True
    matches = re.search('\.(ecoinform)\.pro$', hostname)
    if matches: return True
    matches = re.search('\.(immortalhabits|aroundheresomewhere|hospedagemweb|associationofbusinesstraining|puttingtrashtogooduse|mail[0-9]+mailer|tpm01|mailvalue|istlondon|humand)\.net$', hostname)
    if matches: return True
    matches = re.search('\.(pserver|49mos-pr|ros-now|myjino)\.ru$', hostname)
    if matches: return True
    matches = re.search('\.(anonymer-hacker)\.ml$', hostname)
    if matches: return True
    matches = re.search('\.(sewra5|echit|smartu5s6|mailforce|viamail|sev8fa|hdc2)\.cz$', hostname)
    if matches: return True
    matches = re.search('\.(static\.clientes\.euskaltel)\.es$', hostname)
    if matches: return True
    matches = re.search('\.(ocn\.ad)\.jp$', hostname)
    if matches: return True
    matches = re.search('\.(m9)\.network$', hostname)
    if matches: return True

    matches = re.search('\.(contact-pro|toclearlysee|timetoreflect|checkingthelist|smalltownobject)\.org', hostname)
    if matches: return True
    matches = re.search('\.(yourcrm|cpcloud|dnsentries|tgml1)\.co\.uk$', hostname)
    if matches: return True

    # EU * * * * * * *
    matches = re.search('\.(flynews|newsbio|newshot|newsair|inetadmin|newsstudio|newscreative|newsstar|newsnetwork|tlg[0-9]+send|newsbio|newsdigital|terranews|newseasy|ems01|newsstudio|newsred|newssky)\.eu$', hostname)
    if matches: return True
    matches = re.search('\.(locaweb|dinapw|dialhost)\.com\.br$', hostname)
    if matches: return True

    #PL
    matches = re.search('\.(home)\.pl$',hostname)
    if matches: return True

    # TK * * * * * * *

    matches = re.search('\.(hackerzgroupnet)\.tk$', hostname)
    if matches: return True

    # OVH * * * * * *
    matches = re.search('\.(worker-[0-9]+|pml[a-z]+-[a-z]+-[0-9]+)\.ovh$',hostname)
    if matches: return True

    matches = re.search('\.(trwww|[0-9]+skygoldshop|easymanagedns|thinkaboutlastsummer|super-remise24|soonmovie|mail-decofinder)\.com$', hostname)
    if matches: return True

    # TOP * * * * * *
    matches = re.search('\.(goldchannel|yourfooddeal|primechannel|masante|mesnouvelles|x1|goleads|nosdeals|mesvacances|nosassurdeals|nextpromo|assurdeals|nextpromo)\.top$', hostname)
    if matches: return True

    # PRO * * * * * *
    matches = re.search('\.(gazettedepeche|gazetteshop|gazettegrandspace|ecolivraison|officepratiques|officepremiere|gazetteforce|gazettemin|gazettemax|gazetteinnovation|gazettecarte|gazetteinform|gazettegrossiste|gazettegalerie|gazetteenligne|gazetteextra|gazetteexposition)\.pro$',hostname)
    if matches: return True

    # FR * * * * * *
    matches = re.search('\.(argentplus79|rock38|jeux-de-mains|eugtm|corporabase|visite-digital|am[0-9]+|mybodi|shmail|totalgp|caroline-offres)\.fr$',hostname)

    matches = re.search('^dd*?\.kasserver\.com$',hostname)
    if matches: return True

    whywhy = "Line 341"
    matches = re.search('\.(passionconso\.fr|bargainsdownunder\.com|bargains-downunder\.com|bargainsdown-under\.com|misterworldwide\.net|lestendancesdeflorence\.fr|promotionsboulevard\.com|laplaceauxbonnesaffaires\.fr|jouwinkelwensen\.com|uwwinkelwensen\.com|uwwinkelwens\.com|galaxiedessoldes\.fr|lesastucesenfolie\.fr|trackvm\.net|mijnshoppingadviezen\.com|mijnshoppingadvies\.com|mijnshopingadvies\.com|mondoprivilegi\.com|mailboutique2\.com|newsdelamoda\.com|sorpresaspara-todos\.com|sorpresasparatodos\.com|uwshoppinggids\.com|tusrebajaspremium\.com|vertigoaroundtheworld\.com|aankopvandedag\.com|godsavethedeal\.com|godsaveourdeal\.com|godsavemydeal\.com|vrtagency\.net|vertigotracktrackworld\.com|svertigomes\.com|vertigotrackww\.com|demagencyvma\.com|vmaitaly\.net|vmaitaly\.com|nyetilbudidag\.com|trankingspainvmes\.com|uusiakampanjoitaatanaan\.com|bestchoicesday\.com|choicesofdtoday\.com|toptilbudfordig\.com|toptilbudfor\.com|toptilbuddig\.com|vertigotrackit\.com|uusiakampanjoitatanaan\.com|uusiakampanjoitaaa\.com|daspping\.com|uniikittarjouk-set\.com|uniikit-tarjouk-set\.com|vmptrack\.com|estracking-vmes\.com|yourdaily-surprise\.com|deter-dagendin\.com|det-er-dagendin\.com|deterdagendin\.com|matesratestoday\.com|vmaffiliation\.com|lavenue-desreves\.com|tu-chollo-alerta\.com|tu-cholloalerta\.com|tucholloalerta\.com|your-daily-surprise\.com|your-dailysurprise\.com|beste-kampanjer\.com|bestekampanjer\.com|kampanjer-nya\.com|nya-kampanjer\.com|nyakampanjer\.com|tilbud-seneste\.com|uniikit-tarjoukset\.com|uniikittarjoukset\.com|lespepitesdujour\.com|nye-nyheder\.com|trackingit-vmp\.com|trackinges-vmp\.com|caprichos-en-laweb\.com|caprichos-enlaweb\.com|caprichosenlaweb\.com|la-ventaja-deldia\.com|la-ventajadeldia\.com|laventajadeldia\.com|reductionrealm\.com|newsletter1-aushopping\.com|tips-idag\.com|volanosconti\.it|aciertosdigitales\.com|propuestaclic\.com|tipsvananne\.com|thankyou-leclercvoyages\.com|kampanj-toppen\.com|annons-torget\.com|quejaimemesdeals\.com|tribaloffers\.com|relationnel-go-sport\.com|relationnel1-go-sport\.com|newsletter2-oney\.com|newsletter1-oney\.com|promotionsexclus\.com|shoptesdeals\.com|yourdailysurprise\.com|newsdemoda\.com|online-erbjudanden\.com|rebajaspremium\.com|smart-avenue\.com|shop-inst-1\.com|vosoffrespremium\.com|annieadvises\.com|discountsdistrict\.com|suggestionshack\.com|thebargainparlour\.com|vickivalues\.com|monshoppingrelax\.com|monparadisdushopping\.com|mestopspromos\.com|mespromosdujours\.com|mesopportunitesduweb\.com|lesnouveautesduweb\.com|lavenuedesopportunites\.com|monexclusiviteduweb\.com|mesdealsdunet\.com|vertigodata\.com|dagenstipsning\.com|mailtorget\.com|online-tilbyder\.com|rabat-tilbyder\.com|sophiesuggests\.com|deal-selection\.com|yourdailysavings\.com|event-torget\.com|unhommeunjour\.com|korting-club\.com|karuselli-tarjous\.com|cd-reactivation2\.com|cd-reactivation1\.com|mavoyanceofferte\.com|lhoroscopedelajournee\.com|larencontredujour\.com|monsigneduzodiaque\.com|unflirtdunjour\.com|unerencontreunflirt\.com|unereductionunjour\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(securegatewayaccess\.com|5tf\.de|lma1\.de|meinvideo\.de|nikefree2016\.de|worldfitness\.de|digitalprintprofi\.de|motorsport-coubique\.de|feedbate\.com|mmpt\.us|greco-group\.de|uberbate\.com|ki81\.de|godsaves\.de|straddle\.de|maecenas\.de|chaturbatestore\.com|chaturbate\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(vos-revetements-en-resines\.fr|clickexperts\.com|dyndash\.com|fuellifeministries\.com|mailtrackpro\.com|clickexperts\.net|top-kreditvergleiche\.de|tiempi\.de|cecotex\.net|flexionworking\.com|mariachigallosdeaguascalientes\.com|mariachigalleros\.com|hartotrabajo\.com|fconstruccion\.com|edytaecuador\.com|wolfcorpec\.com|bmaadvising\.com|rockchangenow\.com|quintalavalentina\.com|myroyalteam\.com|modify-car\.com|mtplauncher\.com|scortsafrodita\.com|360businessgroupec\.com|clipitpro\.com|cobrotodo\.com|aandlpoolmaintenance\.com|passenger-assistance\.com|circulodesuboficialespn\.org|asadasenlinea\.com|acryvega\.com|spamartina\.com|vf-layers\.com|fuellifedavie\.com|mailtrackpro\.net|eddievegaa\.com|bonitabeachsantamarianitamanta\.com|cooking-by-kids\.net|cooking-by-kids\.info|cooking-by-kids\.com|grupotrax\.com|dinnegocios\.com|ev2l\.com|fuellifeyouth\.info|fuellifestudios\.info|fuellifemusic\.info|fuellifeministries\.info|fuellifemedia\.info|fuellifechurch\.info|fuellifefellowship\.net|fuellife\.net|darksundisruption\.net|epicyouthrally\.net|soulquestfoundation\.com|soulquestfoundation\.org|fuellifeyouth\.net|fuellifeyouth\.com|fuellifestudios\.net|fuellifestudios\.com|fuellifemusic\.net|fuellifemusic\.com|fuellifeministries\.net|fuellifemedia\.net|fuellifemedia\.com|fuellifechurch\.net|fuellifechurch\.com|fuellifecamp\.com|fuellifefellowship\.com|fuelfellowship\.net|epicyouthrally\.info|woodshopco-op\.com|verticalexistence\.com|vigilservant\.com|u3id\.com|tumecanicoencasa\.com|thejaggedcross\.com|taleof2souls\.com|seethecontrast\.com|radiocentromiami\.com|qpago\.com|projectioncore\.net|omega-health\.com|malaoja\.com|hi-utsuri\.com|hiutsuri\.com|grupo-mayo\.com|ginrin-asagi\.com|ginrinasagi\.com|fuellife\.com|dynamicscapes\.net|designercasework\.com|deadmansparadise\.com|contrastministry\.com|christisthecontrast\.com|axontrade\.com|akame-kigoi\.com|akamekigoi\.com|bio-kinesis\.com|benigoi\.com|mariachisondemitierra\.com|soulquestfoundation\.net|katzenkratzbaum-test\.de|gestionmoda\.com|cecotex\.com|congresomundialovino\.com|greenfieldser\.com|by-sm\.com|kigoi\.com|dyndash\.net|eduardovegamusic\.com|facturaelectronicacr\.com|vegasolawebsolutions\.com|unmillondemarcas\.com|alxse\.com|mariachijarabetapatio\.com|epicyouthrally\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(vm[0-9]+\.cust\.ignum\.cz|buzzinator\.com|mydedicated\.website|coucou-networks\.fr|brillantissime75\.fr|bilans\.com|clubvagos\.com|extfrrdotwelvetwelve\.com|editiabev\.com|scleanermx\.com|shoppa-online\.com|levillagedushopping\.com|sceltopervoi\.it|selectedtoday\.com|tuwebvip\.com|happyshopping\.fr|lesmeilleuresoffres\.fr|checonsigli\.it|carrelloweb\.it|newsbeta\.it|despromospourlui\.com|mesreductionsdujour\.com|thematicstats\.com|lamaisondesshops\.com|guidedesreductions\.com|lagaleriedesoffres\.com|offresetreductionsduweb\.com|lesexclusdunet\.com|dslrwinnen\.com|bonnespromotions\.com|lestoppromos\.com|lesexclusivitesduweb\.com|lemeilleurdescredits\.com|lapromodelajournee\.com|monobjectifminceur\.com|monjeudujour\.com|mesideesvoyages\.com|shoppingavantage\.fr|promotionetreduction\.com|toutpourmavoiture\.com|lavenuedesoffres\.fr|lavostrapromozione\.it|lavostrapromozione\.com|lavenuedesoffres\.com|lesoffresdusiecle\.com|cosmetys\.fr|mavoyancefacile\.com|lemarchedesoffres\.com|lassociationdesconsommateurs\.com|mesoffresshopping\.com|unjourunepromotion\.com|galaxieduweb\.fr|novitaperte\.it|vrouwonly\.com|dinekampagner2\.com|dagensnyheder2\.com|compra-adictos\.com|explorarutas\.com|exclusivoworld\.com|shoppingastucieux\.com|tuboletinvip\.com|todoaciertos\.com|clubpremium\.it|vantaggionline\.it|dine-kampagner\.com|propostadelgiorno\.it|mina-tilbud\.com|angolodellamoda\.it|slip11-bat\.net|e11mslip\.net|dagens-erbjudanden\.com|sinun-tarjouksesi\.com|shopmetvoordeel\.com|modevanvandaag\.com|dailymailsforyou\.com|smart-mails\.com|todaysmails\.com|voordeelvip\.com|dailyaussiedeals\.com|femmededemain\.fr|dagens-nyheder\.com|daglige-tilbud\.com|online-tarjoukset\.com|nyheter-online\.com|convenientissimo\.it|lesopportunitesduweb\.com|privateshophall\.com|ventasextraordinarias\.com|lesfoliesduweb\.fr|aubonmarcheduweb\.com|lesbellesoccases\.com|rutasdecompras\.com|xn--callejerosespaa-crb\.com|exclumode\.com|exclubeaute\.com|topconvenienza\.it|hetbestevanhetweb\.com|leparadisdushopping\.com|catalogoderebajas\.com|dina-tilbud\.com|kampanje-fordeler\.com|lesbonsdealsdunet\.com|promozioniesclusive\.it|discountsoftoday\.com|goodsoftoday\.com|slip-software\.com|najlepszeoferty\.com|maillkuppet\.com|idea-click\.it|hillsediti\.com|novedadesvip\.com|news-training\.com|e10mslip\.net|lesdealsdujour\.com|hora-do-desconto\.com|mesideesshopping\.com|webtilbud\.com|intmsvonefour\.com|quspidke\.com|intkobtentwelve\.com|lesplansdunet\.com|kiosquinho\.com|hombre-shopping\.com|leustame\.com|slip-cleaner\.com|rabatuglen\.com|vosoffresvip\.com|track77\.com|topofpromo\.com|myventeprivee\.com|promocoes-e-ofertas\.com|ralfndis\.com|meine-netzinfo\.com|mas-ofertas\.com|mailfordeler\.com|mailagora\.com|hola-descuentos\.net|tuoferta-del-dia\.com|siempre-descuentos\.com|homem-shopping\.com|paseodelasrebajas\.com|paseodelasofertas\.com|emslip\.com|novedadesdiarias\.com|vinkkiapajat\.com|nieuwtjevandedag\.com|senzia-ad\.com|daodmduw\.com|operacionprimavera2013\.com|informacaododia\.com|gununanlasmasi\.com|topdesaffaires\.com|tipp-des-tages\.com|promocaobr\.com|dentedeleao\.com|yourshoppingideas\.com|twojeoferty\.com|occasioneweb\.com|tu-ofertadeldia\.com|tarjouskaruselli\.com|bhebcn\.com|oportunidadesclub\.com|ventajasenlinea\.com|occasionispeciali\.com|zakupoholicy\.com|rabattdagens\.net|zakupownia\.net|livedata-solutions\.com|najnowszeoferty\.com|wyspapromocji\.com|slip10-bat\.net|swiatofert\.com|dgcspam\.com|instant-mail\.com|beverlyhr\.com|jouwvoordeel\.com|mailenfolie\.com|annoncesshopping\.com|univers-feminin\.com|fantasticasofertas\.com|ditesnoustout\.com|beverlymarketing\.com|lemondedelamaison\.com|conso-etude\.com|ilclubdeiconsigli\.com|interubeve\.com|soernfyh\.com|bd2010\.com|extdkbtseventhree\.com|avenuedesaffaires\.com|femme-shopping\.com|mundo-vantagens\.com|slip5-bat\.net|miscomprasvip\.com|mijndeals\.com|offerte-perte\.com|mymatch-leclub\.com|lemondedespromos\.com|pakjevoordeel\.net|fostuveb\.com|descontosloucos\.com|promo-vip\.com|sorteo-capraboacasa\.com|diamantmeilleurami\.com|bid-max-pt\.net)$',hostname)
    if matches: return True
    matches = re.search('\.(e-radin\.com|gimeek\.net|gimeek\.com|meetaffiliate\.biz|orchilove\.com|sexxcam\.es|nawhosting\.com|gimeek\.biz|youswiss\.biz|sopeople\.com|so-people\.com|sopeople\.net|adswiss\.info|gimeek\.info|youswiss\.info|yoodate\.com|akdlv\.com|promonautes\.net|promonautes\.fr|affiloo\.com|affiliation-serveur\.com|iffiliation\.com|affandgo\.com|ohmyaff\.com|mediaffiliation\.fr|market-lead\.net|urlxs\.fr|club-affiliation\.com|clubaffiliation\.com|leadency-affiliation\.com|larossettisserie\.com|mediaaffiliation\.com|affiliationserveur\.com|affiliationserver\.com|comandcie\.fr|media-affiliation\.com|superfilou\.com|voyage-facile\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(nlbonjourinfos\.com|malettreinfo\.com|coindestravaux\.fr|infofanactu\.com|lesfrenchics\.fr|lesfrenchics\.com|maparfaitenews\.com|laparfaitenews\.com|nlmalettreinfo\.com|coindelinfo\.com|flashactualites\.com|leflashactu\.com|lecoindesinfos\.com|clubdesactus\.com|leclubactu\.com|bonjourinfo\.com|bjrinfo\.com|hbwkmedia\.com|newsmixinfos\.com|mixinfos\.com|nlparfaitenews\.fr|newsvisionconso\.com|newsinfprecieuse\.com|madameprivee\.com|lactupromo\.com|nlactupromo\.com|nlbjrinformatie\.com|mmeprivee\.com|actu-mme\.fr|linforock\.fr|newsprecieuse\.fr|consolive\.fr|nlrockinfo\.fr|consoenlive\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(neoenergy-np-industrial\.com|adminos-info-dns\.com|comptoirinfo\.fr|linfoplace\.com|toutlemag\.com|actifsol\.com|digilettre\.com|escanews\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(cyousoon\.com|marevid\.com|gemailing\.com|domain-ns\.com|cyoumore\.com|clic-reduction\.com|clicreduc\.com|mailing-mutuelle\.com|isegroup\.com|aslnre\.com|dcomre\.com|datnre\.com|jmsidn\.com|lipnre\.com|ph-secure\.com|neeredn\.com|ncardn\.com|dom-ecom\.com|pascorev\.com|retrapas\.com|pharmacien21\.com|clicity\.com|gulliver\.com|teleacting\.com|r7g\.com|pharmacie-et-parapharmacie\.com|mobeo\.net|mobeo\.com|mailing-mep\.com|joliclic\.com|emailing-emailing\.com|clic-reduc\.com|nr2jd\.com|nosrev\.com|ngalre\.com|revcomgrp\.com|gulmail\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(offredefolie\.com|amourdumonde\.fr|circuitafriquedusud\.com|lesdealsdeouf\.com|ledealdeouf\.com|lesoffresinmanquables\.com|lesoffrespremium\.com|mailsagogo\.com|newsendelire\.com|lespromosdemalade\.com|uneofffredefou\.com|lespromosanepasrater\.com|lespromosendelire\.com|cocktaildenews\.com|lesdealsenor\.com|ledealduweb\.com|ledealdunet\.com|lesoffresagogo\.com|promosenstock\.fr|voya-neo\.com|voyage-motion\.com|zetopnews\.com|zebigmail\.com|zebestoffre\.com|lesachatsendelire\.com|dealdemalade\.com|espacereducs\.com|espace-promos\.com|lexpertdespromos\.com|lasupernews\.com|lapromosfantastique\.com|lapromodeouf\.com|lafoliedesoffres\.com|laffaireenor\.com|loffrextradujour\.com|loffrextra\.com|loffrequotidienne\.com|loffrenor\.com|loffrefantastic\.com|loffredusiecle\.com|loffreanepasrater\.com|lesoffresmagics\.com|ledealquotidien\.com|ledealfantastic\.com|ledealenor\.com|undealmagique\.com|thenewsenor\.com|zepromoextraordinaire\.com|zeoffreneor\.com|zemagicoffre\.com|zemagicdeal\.com|zegoldnews\.com|zedealextra\.com|lesfollesaffairesdujour\.com|lesdealsimmanquables\.com|lesaffairesenfolie\.com|ledealdefou\.com|lanewsdesinmanquables\.com|lanewsdesaffaires\.com|lanewsdefolie\.com|thecoindesbonnesaffaires\.com|cadoenfolie\.com|cadodujour\.com|letopofthedeal\.com|letopcado\.com|lemaildusiecle\.com|lecadodujour\.com|lebondealdujour\.com|latopaffaire\.com|lasuperoffre\.com|lagoldnews\.com|laffaire26000\.com|unkadoparjour\.com|uneoffredeouf\.com|unenewsdefou\.com|zedealoftheday\.com|comdeouf\.com|mail1260\.com|loffrendelire\.com|newsenfolie\.com|news26000\.com|unmailparday\.com|uneoffreparmail\.com|unenewsparday\.com|imieiprivilegipresenti\.com|adictacompras\.com|proxi-mail\.com|zedealdunet\.com|unjourunmail\.com|unmailunepromo\.com|unmailundeal\.com|uneoffreparjour\.com|umailunepromo\.com|thebonplandujour\.com|theoffreasaisir\.com|zebonplandujour\.com|uneaffaireenor\.com|loffreasaisir\.com|unjourunenews\.com|zeaffaire\.com|super-news\.fr|lapromosdujour\.com|newsexpert\.fr|mailunique\.com|superkado\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(skolazeny\.cz|sev8fa\.cz|friedricecompany\.cz|smarts7d6\.cz|avatarmarketing\.cz|businessaccelerator\.cz|se3ure\.cz|smartfaktura\.cz|zenonaslovicko\.cz|podnikamesrdcem\.cz|blogacademy\.cz|skyflow\.cz|qualia\.cz|expertbusiness\.cz|davidkirs\.info|experteconomy\.cz|lovemarketing\.cz|prodejsvujtalent\.com|skyparty\.cz|smartselling\.cz|ssbc\.cz|digitalageuniversity\.com|skyspirit\.cz|talkingstick\.cz|expertmarketing\.cz|simplefood\.cz|stastnecesko\.com|smartcluster\.net|expertbusinessacademy\.com|se5ruj\.cz|smartk5k8\.cz|sec6es\.cz|se5pax\.cz|setr7p\.cz|onlinerevolution\.cz|experthouse\.cz|sedes6\.cz|se7ubr\.cz|sebru4\.cz|luckakolarikova\.cz|skyflowmedia\.cz|consciousness\.cz|smarth5h6\.cz|lifeflow\.cz|skyspace\.cz|skyhub\.cz|atthetop\.cz|theleaders\.cz|automarketing\.cz|smartflow\.cz|smartuniversity\.cz|mioapp\.cz|skybusiness\.cz|flowgroup\.cz|flowcapital\.cz|traffictoprofit\.cz)$',hostname)
    if matches: return True
    matches = re.search('\.(wwpartnerships\.com|iottechlab\.info|ytd\.pw|wac-2016\.com|rahatyolculuk\.com|direksiyonozelegitim\.com|ceptvizle\.com|cepradyodinle\.com|ayimp3\.com|ayifilm\.com|begumkaplan\.net|begumkaplan\.com|erbilkara\.com|cepmuzikindir\.com|begumkaplan\.info|izmir-araba-kiralama\.biz|nintendoemulator\.com|iottechlab\.net|bestagario\.com|iottechlab\.com|ersinkara\.com|kanalvizyon\.com|olgunsikis\.biz|iot-eurasia\.com|dunyaotomotivkonferansi\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(location-fichier\.com|webpack\.fr|fichier-proprietaires-immobiliers\.com|location-fichier-sms\.fr|email-fichier-entreprise\.com|fichier-mairies\.net|prospectionsms\.com|fichier-comites-entreprises\.com|solutions-fichier\.fr|solution-fichiers\.fr|solution-fichier\.fr|solutionsfichiers\.fr|solutionsfichier\.fr|solutions-fichiers\.fr|solutionsfichiers\.com|solutions-fichier\.com|solutionsfichier\.com|solution-fichiers\.com|solution-fichier\.com|fichiersms\.com|smsfichier\.com|email-routage\.net|fichier-entreprises\.com|fichier-drh\.com|assuramax\.com|fichier-directeur-marketing\.com|fichier-directeur-general\.com|fichier-decideurs\.com|nje-diffusion\.com|njediffusion\.com|fichier-motards\.com|fichier-automobilistes\.com|location-fichier-sms\.com|solutionfichier\.fr|fichier-concessionnaires\.com|job-mailing\.com|lebarde-karaoke\.com|solutionfichiers\.com|solutionfichiers\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(mta21\.com|tr-pass\.com|idees-actives\.fr|jae-lab\.fr|ideesactives\.fr|le-petit-marche-de-sylvie\.com|sac-shopping\.fr|tr-repere\.com|tr-no-asp\.com|tr-dag-sys\.com|tr-technic\.com|tr-isinfo\.com|tr-pvision\.com|tr-pass-st\.com|tr-ln3\.com|tr-link4\.com|tr-waw-stat\.com|tr-unip\.com|tr-ip-rev\.com|tr-waw\.com|tr-passman\.com|tr-lnk2\.com|tr-isi\.com|lyon-web\.fr|alwaysinbeta\.info|mta-d5\.com|mta-n3\.com|mta-n2\.com|mta-n1\.com|mta-medias\.com|tr-medias\.com|tr-uni\.com|eds-industrie\.fr|tr-unit4\.com|anthemis\.fr|spa-aquadolce\.fr|solutions-netop\.com|tr-ess\.com|tr-lnk\.com|trlink1\.com|trlevel1\.com|tr-adt1\.com|to-trlnk\.com|one-tr\.com|anthemis-entreprise\.com|le-cloitre\.net|jardindesplantesacouleurs\.com|solution-netop\.com|emailing-solution\.info|logiciel-emailing\.info|tr-ech\.com|tr-adts\.com|tr-adt\.com|tr-arc\.com|tr-idis\.com|tr-ide\.com|tr-ofr\.com|tr-mn\.com|tr-mes\.com|trl-lnk\.com|tr-lea\.com|tr-jdg\.com|tr-dyn\.com|tr-dpe\.com|tr-cp\.com|tr-ci\.com|tr-cdp\.com|tr-bdmr\.com|tr-gpi\.com|tr-evt\.com|tr-esc\.com|tr-trans\.com|tr-test\.com|tr-sh\.com|tr-rm\.com|tr-rd\.com|tr-am\.com|tr-alvs\.com|tr-alm\.com|tr-prv\.com|tr-pci\.com|tck-web\.com|securite-protection-loisirs\.net|securite-protection-loisirs\.com|mta10\.com|mege-amo\.com|ferreiradb\.com|gbisolation\.com|decoroc\.com|del-et-co\.com|copieursystem\.com|blog-emailing\.com|anthtech\.net|anthmta\.com|3s-concept-ingenierie\.com|3s-concept\.com|dponews\.info|anthemis-haren\.com|chute-libre\.pro|cb-menuiseries\.pro|trfollow\.com|tr-track\.com|mailforyou\.info|stc\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(emaildata\.cz|em2service\.cz|emailkampane\.cz|em4service\.cz|go-mail\.cz|emailsystem\.cz|commerce-media\.cz|onlineemail\.cz|crmdata\.cz|goemail\.cz)$',hostname)
    if matches: return True
    matches = re.search('\.(eol-001\.com|etarget-emailing\.com|eol-leclerc\.com|mon-serveur-smtp\.com|rev-eta-04\.com|rev-eta-03\.com|rev-eta-02\.com|rev-eta-01\.com|astime-relais-2\.com|wrk-relay\.com|koala-srv-9\.com|koala-srv-8\.com|koala-srv-7\.com|koala-srv-6\.com|koala-srv-5\.com|koala-srv-4\.com|koala-srv-3\.com|koala-srv-2\.com|koala-srv-1\.com|red-express-08\.com|red-express-07\.com|red-express-06\.com|red-express-05\.com|red-express-04\.com|red-express-03\.com|red-express-02\.com|red-express-01\.com|motad-1d\.com|motac-1c\.com|motab-1b\.com|rev-r-srv-18\.com|rev-q-srv-17\.com|rev-n-srv-14\.com|rev-m-srv-13\.com|rev-l-srv-12\.com|rev-k-srv-11\.com|rev-j-srv-10\.com|rev-i-srv-9\.com|rev-h-srv-8\.com|rev-g-srv-7\.com|rev-f-srv-6\.com|rev-e-srv-5\.com|rev-d-srv-4\.com|rev-srv-1117-1\.com|rev-c-srv-3\.com|rev-b-srv-2\.com|rev-a-srv-1\.com|news-lilotcalins\.com|news-capital-place\.com|astime-news\.com|plmo1609\.com|srv-kol\.com|rev-05-srv\.com|rev-04-srv\.com|rev-03-srv\.com|rev-02-srv\.com|rev-01-srv\.com|matec-01\.com|gofto-fr\.com|goeto-fr\.com|gocto-fr\.com|gobto-fr\.com|goato-fr\.com|apjai-r\.com|aokah-r\.com|anlag-r\.com|ajpac-r\.com|aiqab-r\.com|ahraa-r\.com|agsaz-r\.com|aftay-r\.com|aeuax-r\.com|advav-r\.com|acwau-r\.com|abxat-r\.com|aayas-r\.com|ayaar-r\.com|axbaq-r\.com|awcap-r\.com|avdao-r\.com|auean-r\.com|atfam-r\.com|asgal-r\.com|arhak-r\.com|aqiaj-r\.com|sender-smtp40\.com|sender-smtp39\.com|sender-x14\.com|sender-x13\.com|sender-x12\.com|sender-smtp27\.com|sender-smtp24\.com|sender-smtp23\.com|sender-smtp19\.com|sender-smtp16\.com|sender-smtp15\.com|sender-smtp01\.com|sender-smtp36\.com|sender-smtp35\.com|sender-smtp34\.com|abc10mail\.com|promoled-rev4\.com|promoled-rev1\.com|godto-fr\.com|redirection-express-02\.com|redirection-express-03\.com|sl2o\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(am9\.fr|production-video-france\.fr|production-video-france\.com|productionvideofrance\.com|am7\.fr|be-a-boss\.com|reworldmediafactory\.com|am6\.fr|at-home-energy\.com|at-home-energie\.com|productionvideofrance\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(programme-malin\.fr|consotop\.com|voslettres\.com|actu-courrier\.com|conso-courrier\.com|elleraconte-courrier\.com|deco-courrier\.com|lettreo\.com|kiwi-actu\.com|nl-milch-kaffee\.com|ed-super-promocao\.com|elixisnetwork\.com|ed-elleraconte\.com|ed-decointerieur\.com|ed-consomeo-nl\.com|ed-123actu\.com|ed-wimea\.com|ed-voitureo\.com|mes-concours\.com|concours-vip\.com|malettreconso\.com|consolettre\.com|meine-promo\.com|lalettre-elleraconte\.com|wim-ea-lettre\.com|w-ime-a\.com|voiture-actu\.com|ma-lettre-ouimea\.com|ma-lettre-deco\.com|lettre-voiture\.com|lettre-elle-raconte\.com|lettre-conso\.com|elle-raconte-news\.com|deco-lettre\.com|actu321\.com|321actu\.com|infos-bienvenue\.com|email-bienvenue\.com|ma-lettre-voiture\.com|lettre-deco\.com|conso-news\.com|321actu123\.com|immo-lettre-immo\.com|courrier-conso\.com|courrier-actu\.com|noslettres\.com|courrier-wim\.com|courrier-voitureo\.com|courrier-elleraconte\.com|courrier-deco\.com|lettresdujour\.com|voiture-courrier\.com|sixile\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(je-plaisante95\.fr|eprogresse\.com|oba32\.com|cible-affaires\.com|infos-reduc\.com|nini16\.com|email-boxes\.net|lut20\.com|uju21\.com|lil11\.com|alerte-votre-argent\.fr|alerte-immobilier\.fr|image77\.fr|uau95\.com|cloitre45\.com|ohbonplans\.com|ohbonneidee\.com|top-monnaie\.com|une-adresse-unique\.com|club-opportunites\.com|rire60\.com|alsomatia\.com|selection-affaires\.com|poup21\.com|pile21\.com|bur19\.com|oriane45\.com|rigole29\.com|nouvelles-offres-ecommerce\.com|optin-adresse\.fr|vive-le-plaisir\.fr|sport19\.fr|gla10\.com|tac128\.com|elle30\.com|lamp75\.com|ouioui17\.com|aj-data\.fr|espritmalin\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(conso-panels\.com|certificat-immatriculation-vehicule\.com|boutique-flash\.com|avis-concessionnaire-automobile\.com|le-bento\.com|conseil-info\.com|enquete-auto\.com|solde-outlet\.com|welcome-data\.fr|encuestaoficial\.com|encuestaconsumidor\.com|granencuesta\.com|enquete-info\.com|enquete-avis\.com|avis-infos\.com|idee-conseil\.com|projet-avis\.com|speed-chain\.com|enquetenews\.com|enqueteinfo\.com|enqueteconsos\.com|contactvoitures\.com|info-enquetes\.com|news-voitures\.com|cardatapro\.pro|info-enquete\.com|newsenquete\.com|monassurancevoiture\.fr|enquete-privee\.fr|pixooo\.fr|xn--location-longue-dure-t2b\.com|echantillons-gratuits\.fr|clientavis\.fr|subhakamana\.fr|nippycar\.fr|nippysearch\.com|nippycar\.com|flash-boutique\.fr|onedata\.fr|otopix\.fr|chatbotsystem\.fr|chatbot-solution\.fr|chatbotsystem\.com|chatbot-solution\.com|e-chatbot\.com|shoppingenquete\.fr|webstreetshopping\.fr|shoppingenquete\.com|trouver-ma-banque\.com|dataquest\.fr|essai-voiture-neuve\.fr|cote-auto-officielle\.fr|switchter\.fr|franprixxmaviedechat\.com|cost0\.net|renthiscar\.com|entretenirsonauto\.fr|revisersonautomobile\.fr|revisersonautomobile\.com|databee\.fr|sondageofficiel\.com|conso-enq\.fr|pour-ou-contre\.fr|virtualcheat\.net|les-ventes-flash\.fr|venteflashmoins50\.fr|creepxel\.net|gopixel\.fr|gopixelnetwork\.fr|gopixel\.net|consolead\.fr|mandatairesautomobiles\.fr|cardatapro\.com|tu-opinion\.com|6999\.fr|autokoopro\.com|vente-flash-swarovski-elements\.com|venteflashswarovskielements\.com|vente-flash-swarovski\.com|venteflashswarovski\.com|vente-flash-noel\.com|venteflashnoel\.com|essayez-une-voiture-electrique\.com|essayezunevoitureelectrique\.com|essayer-une-voiture-electrique\.com|bobys\.net|creditkoo\.com|outletmajor\.com|major-outlet\.com|certificatdecession\.fr|certificat-de-non-gage\.fr|soldes-premium\.com|solde-info\.com|outlet-solde\.com|outletselection\.com|outletliner\.com|outlet-grand\.com|last-outlet\.com|lady-outlet\.com|venteflashprive\.net|autobandeannonce\.com|otopix\.com|neuf-euros\.com|occasionsautos\.net|neufeuros\.com|meilleure-occasion\.com|switchter\.com|demanderunessai\.com|0-cost\.com|cost-0\.com|19francs\.com|auto-koo\.com|10byday\.com|6-euros\.com|10atwork\.com|votreavisgarage\.com|fishandships\.fr|premiere-vente\.com|vente-privee-voiture\.com|solde-flash\.com|vente-flash-prive\.com|venteflashprive\.com|venteflashmoins50\.com|flashdiscount\.net|cote-voiture\.biz|ventes-flash\.info|cote-voiture\.info|ventes-flash-du-mois\.com|mon-essai-voiture\.com|les-ventes-du-mois\.com|essai-voiture-neuve\.com|cote-auto-officielle\.com|conso-avis\.com|consoavis\.com|auto-cote-officielle\.com|autocote\.net|prospectsauto\.com|adopteunecaisse\.com|essai-auto\.biz|xn--actualit-auto-ihb\.com|datawork\.fr|brasilinhas\.com|demander1essai\.com)$',hostname)
    if matches: return True
    matches = re.search('(dns-oid\.com|armen-education\.net|rhone-chimie-industrie\.com|oid\.fr|objectif0spam\.com|tech-cc\.com|objectifweb\.com|dreaweb\.com|spare-sgcc\.com|objectif-internet\.net)$',hostname)
    if matches: return True
    matches = re.search('(vehicleoffersdirect\.uk|printbysplash\.uk|ukvehiclesonline\.uk|chimpaddme\.com|eshotguru\.com|intromail\.uk)$',hostname)
    if matches: return True
    matches = re.search('(o-lion\.net|filosof-v\.net|107gam\.com|narodnaya-medicina\.net|lyoul\.net)$',hostname)
    if matches: return True
    matches = re.search('\.(hello-gerarddarel\.com|avent-group\.com|info-gerarddarel\.com|offre-cafe-royal\.com|mesprono2018\.com|deals-prives\.com|offreprivilege-swarovski\.com|news-kenzoparfums\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(kry36\.com|cap-enquete\.com|tango-small\.com|le-meilleur-de-la-mode\.fr|lumiere32\.com|rouge31\.com|miracle80\.com|meilleurs-catalogues\.com|plans55\.com|plaisir95\.com|papillon75\.com|regard45\.com|super-reduction14\.com|super-mail75\.com|super-affaires95\.com|vert49\.com|top-de-la-joie\.com|voyage-remise92\.com|capmail\.fr|reactif-email\.fr|reactif-email\.com|bon32\.com|beaute90\.com|cadeau95\.com|equipe37\.com|herakles80\.com|passion75\.com|topgain75\.com|topcommerce32\.com|amer30\.com|bou22\.com|ble95\.com|arg41\.com|esx89\.com|liane17\.com|habile16\.com|jos49\.com|lit49\.com|ole92\.com|ohla32\.com|pur39\.com|plo85\.com|plais93\.com|taf45\.com|vil19\.com|classe17\.com|billet31\.com|bilan120\.com|bien40\.com|crac13\.com|cor75\.com|la-belle-economie77\.com|gratuit49\.com|gain97\.com|marche13\.com|lia100\.com|prixbas17\.com|oups24\.com|rigolo17\.com|troc90\.com|bleue22\.com|joie24\.com|hum45\.com|gri26\.com|lune22\.com|lio97\.com|roi39\.com|sysmail\.fr|bling38\.com|anis21\.com|elle12\.com|jaime47\.com|jadore28\.com|lune17\.com|lui40\.com|rire16\.com|bri47\.com|direct-offres\.com|direct-economie\.com|gru13\.com|gros-gains\.com|jess12\.com|loulou38\.com|perform-affaires\.com|resa14\.com|cap-connect\.fr|bari41\.com|lion29\.com|tri26\.com|exp95\.com|meilleur-des-sens\.com|nouvelles-offres\.com|oui45\.com|rui12\.com|la-meilleure-cagnotte\.com|ouvrez-vos-yeux\.com|ouvrez-vos-gains\.com|plus-de-cagnotte\.com|ameliorer-votre-carriere\.com|gain-mail\.com|starcamp\.fr|offreunique\.fr|starmail\.fr|mailvip\.fr|jeminforme\.fr|plus-go\.com|anansky\.com|ananroot\.com|ananfly\.com|ananboard\.com|anansoftt\.com|dim03\.com|cdt7\.com|pgr9\.com|mauricethedog\.com|caligula\.fr|minhbird\.com|minhup\.com|images875\.com|anancut\.com|cd1-fly\.com|ng-nc\.com|boutique-en-or\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(bmsend\.com|benchmarkemail\.de|preferredhospitalitydsr\.com|apibig\.com|benchmarkfamilybiz\.com|benchmarkgetstarted\.com|benchmarkstarter\.com|whitelabelemailing\.com|tubegenre\.com|spreadsheetreview\.com|smbmsend\.com|plethnet\.com|mdbmsend\.com|mailrelay95\.com|mailrelay85\.com|mailrelay75\.com|mailrelay65\.com|mailrelay60\.com|mailrelay100\.com|lgbmsend\.com|ivoryemail\.com|instamailing\.com|hatlifebook\.com|free-emailmarketing-info\.com|email-newsletter-service\.com|emailmarketing-sanjose\.com|emailmarketing-detroit\.com|emailmarketing-columbus\.com|emailmarketing-chicago\.com|curtiskeller\.com|benchurls\.com|benchmarksocialtools\.com|benchmarksocialmagnet\.com|benchmarkmails6\.com|benchmarkmails30\.com|benchmarkmails25\.com|benchmarkmails24\.com|benchmarkmails23\.com|benchmarkmails22\.com|benchmarkmails21\.com|benchmarkmails15\.com|benchmarkmails14\.com|benchmarkeventmarketing\.com|benchmarkemailchina\.com|benchmarkapps\.com|benchindiaurls\.com|bmeurl\.com|bmesrv20\.com|bmelabs\.com|bmeconfirm\.com|bme2\.net|bmdedamails9\.com|bmdedamails8\.com|bmdedamails7\.com|bmdedamails6\.com|bmdedamails5\.com|bmdedamails4\.com|bmdedamails2\.com|bmdedamails10\.com|bmdedamails1\.com|bmdeda9\.com|bmdeda8\.com|bmdeda7\.com|bmdeda6\.com|bmdeda5\.com|bmdeda4\.com|bmdeda3\.com|bmdeda2\.com|bmdeda10\.com|bmdeda1\.com|bmdeda\.com|betestonly\.com|tatooleggings\.com|bme1\.net|digitalmarketingtour\.com|benchadmin\.com|benchmarkmails4\.com|benchmarkmails3\.com|benchmarkmails\.com|bmetrack\.com|benchmarkupdates\.com|simplypowerful\.us|tatoobottoms\.com|nachofreak\.com|bmarkemail\.com|bmcrmtest\.com|bmarksend\.com|bmsendverify1\.com|techsupportindia\.in|benchmarkheartofbusiness\.com|streetsweepingreminder\.com|mailrelay70\.com|eurekaeureka\.com|emailmarketing-fortworth\.com|emailmarketing-baltimore\.com|benchmarkmails7\.com|benchmarkmails29\.com|benchmarkmails28\.com|benchmarkfree\.com|emailmarketing-losangeles\.com|benchmarktst\.com|vrmail5\.com|vrmail1\.com|benchmarkcrm\.com|benchmarkmails13\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(speoffi\.com|365-privilege\.net|livresenfete\.biz|mister-grappin\.com|mister-jackpot\.com|misterjackpot\.com|mister-grattage\.com|mistergrattage\.com|mistergrappin\.com|mister-gewinner\.com|mistergewinner\.com|mister-gewinn\.com|mistergewinn\.com|mister-ganador\.com|mister-galibi\.com|mistergalibi\.com|mister-gagnants\.com|mister-gagnant\.com|mister-castigatorul\.com|mistercastigatorul\.com|of-cli\.com|of-cgo\.com|privileges-banque\.com|privileges-auto\.com|tele-essentielle\.com|teleessentielle\.com|livres-en-fetes\.com|livresenfetes\.com|livres-en-fete\.biz|livre-en-fetes\.com|livreenfetes\.com|livre-en-fete\.com|livre-en-fete\.biz|livreenfete\.biz|tous-les-deals\.com|prem-im\.com|os-365-privileges\.com|os365privileges\.com|os-365p\.com|os365p\.com|newsletter-officiel-du-shopping\.com|only-voyages\.com|onlyvoyages\.biz|only-voyage\.biz|mon-coin-deco\.com|moncoindeco\.com|meilleurgain\.com|lafilaturedesvosges\.com|kelpromo-interactif\.com|kelpromointeractif\.com|lefiloo\.com|journal-du-shopping\.com|journaldushopping\.com|journal-des-bons-plans\.com|journaldesbonsplans\.com|journaldesbonsplans\.biz|envies-de-detentes\.com|enviesdedetentes\.com|envies-de-detente\.com|enviesdedetente\.com|envie-de-detentes\.com|enviededetentes\.com|envie-de-detente\.com|enviededetente\.com|365-privileges\.com|365privileges\.com|365-privilege\.com|365privilege\.com|email-selections\.com|email-selection\.com|essai-sur-routes\.com|essaisurroutes\.com|essai-sur-route\.com|essaisurroute\.com|essais-sur-routes\.com|essaissurroutes\.com|essais-sur-route\.com|essaissurroute\.com|arpegemedia\.com|spe-365pri\.info|spe-365pri\.com|reda-privilege\.info|reda-privilege\.com|os-eddet\.info|os-eddet\.com|edi-detente\.info|edi-detente\.com|os-tdj\.net|os-tdj\.info|os-tdj\.com|monguidedefisc\.org|monguidedefisc\.net|monguidedefisc\.info|monguidedefisc\.com|mon-guide-defisc\.net|mon-guide-defisc\.info|mon-guide-defisc\.com|allegervosmensualites\.org|allegervosmensualites\.net|allegervosmensualites\.info|allegervosmensualites\.com|pari-du-jour\.com|un-gagnant-chaque-jour\.com|privilege-autos\.com|clickandlead\.com|tirage-du-jour\.fr|officiel-du-shopping\.biz|officieldushopping\.biz|vontade-de-relaxamento\.info|tendance-des-marques\.info|tendancedesmarques\.info|paris-du-jour\.info|parisdujour\.info|os-tdm\.info|cotegourmandise\.info|os-cgour\.info|allegermesmensualites\.info|alleger-vos-mensualites\.info|alleger-mes-mensualites\.info|cote-conso\.com|m-gagnant\.com|vontade-de-relaxamento\.net|vontadederelaxamento\.net|votreconcessionnaireauto\.net|tous-les-deals\.net|prova-na-estrada\.net|provanaestrada\.net|lado-guloseima\.net|ladoguloseima\.net|journaldesbonsplans\.net|tv-do-dia\.net|tvdodia\.net|spe-envdd\.net|votreconcessionnaireauto\.info|tous-les-deals\.info|privileges-banque\.info|privilegesauto\.info|pari-du-jour\.info|prova-na-estrada\.info|privilege-autos\.info|privilegeauto\.info|privileges-auto\.info|ladoguloseima\.info|lado-guloseima\.info|essayezuneauto\.info|essayez-1-auto\.info|essayez1auto\.info|essayer-1-auto\.info|essayer-une-auto\.info|24-osho\.com|privipart\.com|zendil\.com|privilegesauto\.com|tiragedujour\.fr|essayeruneauto\.info|essayez-une-auto\.info|choisir-son-deal\.info|choisirsondeal\.info|tirage-du-jour\.biz|1gagnantchaquejour\.biz|choisir-immo\.com|choisirimmo\.com|dakelp\.com|astucesmode\.com|astucemode\.com|consotendance\.com|instantsmeteo\.net|selection-des-jeux\.info|tv-du-jour\.net|cote-gourmandises\.com|privilege-autos\.net|mistervincitore\.com|bh-creation\.com|bhcreation\.com|pari-du-jour\.net|astuce-mode\.com|instants-meteo\.com|casinogratos\.com|beauteprivilege\.com|privilege-auto\.com|l-officiel-du-shopping\.net|lofficieldushopping\.net|bhcreations\.net|bh-creation\.net|mister-vencedor\.com|mistervencedor\.com|365-privileges\.net|guide-officiel-du-shopping\.com|guideofficieldushopping\.com|choisirsondeal\.com|avantage-privilege\.net|avantageprivilege\.net|1-gagnant-chaque-jour\.com|batimentprive\.com|chacun-son-deal\.net|chacunsondeal\.net|choisir-son-deal\.net|choisirsondeal\.net|click-and-lead\.com|votre-tablette\.com|partessais\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(brokentwillight\.com|areenaasiakasedut\.com|dagensnyheter365\.com|harrytilbud\.com|cutepizzaslice\.com|stardustchaos\.com|ozesisters\.com|hyacinthsdaily\.net|offersthisday\.com|tbofy\.com|todaysnewsau\.com|heureusesnouvelles\.com|ecatilbud\.net|dailycoffeebreak\.net|trendywendys\.net|czmsend\.com|grousegames\.com|ameliamcgee\.net|happymoes\.com|greataustraliaoffers\.com|itsusanditsnow\.com|sirioffer\.com|scrabmles\.com|redschoice\.com|aboutthinkit\.com|jabboitnow\.com|theglobal155\.com|bobhabbo\.com|habberbob\.com|aussiedozzie\.com|minde400\.com|torbasleed\.com|timeoffice365\.com|personalcareer\.net|chickbock\.com|happyington\.com|snuptiinews\.com|amigospeaks\.com|24greatnews\.com|siljesbeste\.com|lmwn\.net|luckythedonkey\.com|woopwoopmail\.com|jessicadeals\.net|downunderdeals\.net|zoedeals\.net|globalquizacademy\.com|theresaperso-deals\.net|karenpsmith\.com|douglashowardtreats\.com|kengurumail\.com|anikasmail\.com|offerservice247\.com|jimjimmyoffer\.com|aussieveralind\.com|godepremier\.net|norskepremier\.com|godebonuser\.com|splitmails\.com|woop365\.com|gullepost\.com|luksusinfo\.com|aanbieding-365\.net|gullfunn\.net|design-og-bolig\.com|freepokiespins\.com|to-hjul\.com|jeg-reiser\.com|tarjoaauutisia\.com|nuttytracker\.com|sinunmahdollisuus\.com|valuuttalaskuri-suomi\.com|teetastapaivasi\.com|quizzell\.com|mrdealdeal\.com|mettesoffers\.com|nett-tilbud\.com|kilpailuja-kaikille\.com|magiccompetitions\.com|lahjakeskus\.com|dealtoprize\.com|gevinstboksen\.com|artydelivery\.com|mailpork\.com|godabonusar\.net|artymail\.com|roligaerbjudanden\.com|prinsessaposti\.com|paketmail\.net|salamaposti\.com|svenskapriser\.com|ditisvoorjou\.com|letsplayandwin\.com|dagelijks-voordeel\.net|wewillfindthemforyou\.com|vipnorge\.net|viimeinentarjous\.com|ukvipmail\.com|vinnkontanter\.net|tonesfunn\.com|wayne-casino\.com|jenniferdeals\.com|sinunonni\.com|vinsthuset\.com|valueoffers\.info|fun969\.com|singoffers\.com|timezoneglobal\.com|jackscorner\.net|valuuttalaskin\.net|caitlinluck\.com|billytips\.com|toptenoffers\.net|valittu\.com|nyhetsinfo\.com|pandasender\.com|itwaits\.com|mailwayne\.com|winnerjack\.com|superspins\.net)$',hostname)
    if matches: return True
    matches = re.search('\.(bp-clients\.com|bp-deal\.com|vf-newsletter\.com|vf-infos\.com|vf-information\.com|vf-deals\.com|vf-deal\.com|vf-couriel\.com|vf-clients\.com|vf-boutique\.com|bp-newsletter\.com|bp-infos\.com|bp-information\.com|bp-deals\.com|bp-couriel\.com|bp-boutique\.com|vf-service\.com|vf-offres\.com|bp-offres\.com|bp-servives\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(altofiche\.eu|isowatt-lead\.eu|suppression-fiche\.eu|toptelecom-lead\.com|ankaziadev\.com|conjuguer-facile\.com|netwash\.eu)$',hostname)
    if matches: return True
    matches = re.search('\.(wtcmqd\.com|ebidtech\.com|ebidcloud\.com|wondeostage\.com|ebpub1\.com|wondeotech\.com|ebiadskit\.com|ebiads1\.com|benchiebox\.com|emailfeeding\.com|ebtst\.com|fr-ebdpost\.com|emailbidding\.com|foskmail\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(laventajadeldia\.com|la-ventajadeldia\.com|la-ventaja-deldia\.com|toptilbudfor\.com|toptilbudfordig\.com|galaxiedessoldes\.fr|laplaceauxbonnesaffaires\.fr|jouwinkelwensen\.com|uwwinkelwensen\.com|uwwinkelwens\.com|mesenviesbeaute\.fr|lesastucesenfolie\.fr|trackvm\.net|mijnshoppingadviezen\.com|mijnshoppingadvies\.com|mijnshopingadvies\.com|mondoprivilegi\.com|mailboutique2\.com|online-erbjudanden\.com|newsdelamoda\.com|sorpresaspara-todos\.com|sorpresasparatodos\.com|uwshoppinggids\.com|tusrebajaspremium\.com|vertigoaroundtheworld\.com|aankopvandedag\.com|godsavethedeal\.com|godsaveourdeal\.com|godsavemydeal\.com|vmwwtracking\.com|vrtagency\.net|vertigotracktrackworld\.com|estrakingvmes\.com|svertigomes\.com|vertigotrackww\.com|demagencyvma\.com|vmaitaly\.net|vmaitaly\.com|vertigotrackit\.net|nyetilbudidag\.com|trankingspainvmes\.com|uusiakampanjoitaatanaan\.com|bestchoicesday\.com|nyetilbudida\.com|nyetilbuddag\.com|trendshoptoday\.com|trendingshoppingtoday\.com|toptilbuddig\.com|vertigotrackit\.com|uusiakampanjoitatanaan\.com|uusiakampanjoitaaa\.com|daspping\.com|uniikittarjouk-set\.com|aankoopvandedag\.com|uniikit-tarjouk-set\.com|vmptrack\.com|affiliationes-vmes\.com|estracking-vmes\.com|web-tilbud\.com|yourdaily-surprise\.com|el-chollo-fresco\.com|deter-dagendin\.com|det-er-dagendin\.com|deterdagendin\.com|el-chollofresco\.com|elchollofresco\.com|matesratestoday\.com|dandy-shopping-today\.com|dandy-shoppingtoday\.com|dandyshoppingtoday\.com|solo-para-el\.com|solo-parael\.com|todo-para-ella\.com|todo-paraella\.com|finestdealstoday\.com|vmaffiliation\.com|lavenue-desreves\.com|tu-chollo-alerta\.com|tu-cholloalerta\.com|tucholloalerta\.com|your-daily-surprise\.com|your-dailysurprise\.com|beste-kampanjer\.com|bestekampanjer\.com|kampanjer-nya\.com|nya-kampanjer\.com|nyakampanjer\.com|tilbud-seneste\.com|uniikit-tarjoukset\.com|uniikittarjoukset\.com|lespepitesdujour\.com|nye-nyheder\.com|vrtrackdem\.net|vrtrackdem\.com|trackingit-vmp\.com|trackinges-vmp\.com|vertigomediaperformanceprivacypolicy\.com|caprichos-en-laweb\.com|caprichos-enlaweb\.com|caprichosenlaweb\.com|rebajaspremium\.com|dagenstipsning\.com|mailtorget\.com|online-tilbyder\.com|rabat-tilbyder\.com|sophiesuggests\.com|korting-club\.com|cd-reactivation2\.com|cd-reactivation1\.com|mavoyanceofferte\.com|newsdemoda\.com|smart-avenue\.com|tribaloffers\.com|shop-inst-1\.com|vosoffrespremium\.com|annieadvises\.com|discountsdistrict\.com|suggestionshack\.com|thebargainparlour\.com|vickivalues\.com|monshoppingrelax\.com|monparadisdushopping\.com|mestopspromos\.com|mespromosdujours\.com|mesopportunitesduweb\.com|lesnouveautesduweb\.com|lavenuedesopportunites\.com|monexclusiviteduweb\.com|mesdealsdunet\.com|vertigodata\.com|deal-selection\.com|yourdailysavings\.com|event-torget\.com|unhommeunjour\.com|karuselli-tarjous\.com|lhoroscopedelajournee\.com|larencontredujour\.com|monsigneduzodiaque\.com|unflirtdunjour\.com|unerencontreunflirt\.com|unereductionunjour\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(info-ga-media\.fr|crazy-promo\.com|galaxysportku\.com|celebrityhostage\.com|baticelik\.com|crazy-conso\.fr|credit-malin\.net|gemlabarben\.fr|gemlabarben\.net|gemlabarben\.com|promo-folie\.com|ekjut\.com|gemlabarben\.info|gemlabarben\.biz)$',hostname)
    if matches: return True
    matches = re.search('\.(nl-consomeo\.com|afj9jh\.com|afjdh8\.com|afhvgh\.com|afhshd8\.com|afh8jjn\.com|afh8hv\.com|afh7hh\.com|aff6hdh\.com|afdhf5g\.com|afdh4dgf\.com|afdgf5\.com|afdf5h\.com|afd8jub\.com|afd8jh\.com|afd5gd\.com|af9jdjf\.com|af9fdh\.com|af7hduh\.com|af7hdhfh\.com|af6fdfd\.com|af5fvdg\.com|af4kjhp\.com|af4fdg\.com|af3hgd\.com|af0klh\.com|lecoindestesteurs\.net|lecoindestesteurs\.com|credit-rembourse\.com|cheque-2017euros\.com|mesmeubles-offerts\.com|mavoiture-offerte\.com|mon-telephone-offert\.com|wisu\.fr|affaires-du-moment\.fr|affaires-exclusives\.fr|jolice\.fr|elixis-worldpanel\.fr|elixis-panel\.fr|elixispanel\.fr|elixisworldpanel\.fr|witrk-ing\.com|nl-consomeo\.fr|witrking\.com|puericultrices\.fr|finebird\.cz|finebird\.net|realtys\.fr|argent-a-gagner\.fr|chequeagagner\.com|jeu-jackpot\.com|jeu-gourmand\.com|jeu-videobuzz\.com|sondage-banque\.com|retrack\.fr|luxehotel\.fr|zaola\.fr|pass-cinema2\.com|justcook\.de|ange-marie\.fr|anys\.fr|1andecafe\.fr|3500euros\.com|16000euros\.com|cheque-2016euros\.com|jeudes3erreurs\.com|jeu-champagne\.com|roue-gagnante\.com|sondage-officiel\.com|isya\.cz|isya\.fr|zofia\.fr|cheque3000euros\.com|elyxis\.de|elyxis\.at|chrys\.fr|lifestylemedia\.fr|sondage-noel2\.com|sondage-noel\.com|repasdefetes\.fr|citys\.fr|elixisagency\.fr|elixis-agency\.fr|destock-experts\.fr|destockexpert\.fr|destock-expert\.fr|destockexperts\.fr|lidys\.fr|make-up-line\.fr|smartphoneoffert\.com|aniss\.fr|laedis\.fr|leadis\.fr|meilleurespromos\.fr|2100\.fr|macoachbeaute\.com|jeu-hifi\.com|mon-robot-patissier\.com|monparcanimalier\.com|tabletteofferte\.com|seona\.fr|zinder\.fr|o2o\.fr|saffrondiscount\.com|yihii\.net|sandie\.fr|2015euros\.com|wunderwerkzeug\.com|belita\.fr|leadi\.fr|anisse\.fr|matcher\.fr|productesting\.net|testingfreebies\.com|wirprodukttester\.com|produktetesten\.net|pruebaproductos\.com|yihi\.fr|waldorf\.fr|sondage-actu\.com|avenue-shopping\.com|elixis-consult\.com|german-kunst\.com|bijoudumois\.com|elixis-event\.com|laubenheimer\.info|riltis\.com|dnsriff\.com|gagnant-express\.com|xn--chimra-eua\.com|sanah\.fr|wimea\.fr|premium-service-hosting\.com|ysia\.com|elixispanel\.com|elixis-panel\.com|germankunst\.com|voscourses\.fr|makeup-line\.fr|makeup-line\.net|make-up-line\.com|vod-illimite\.com|guap\.net|elixis-worldpanel\.com|laedis\.com|mysqlguide\.com|conso-mieux\.com|jeu-gps\.com|elixisleads\.com|madame\.com|make-up-line\.net|shetell\.fr|retrotrend\.biz|jeu-chequecadeaux\.com|avis-officiel2\.com|galaxydesjeux\.com|meilleurdubuzz\.com|united-systemics\.com|unitedsystemics\.com|elixis\.info|kiwiactu\.com|silver-clope\.com|silverclope\.com|institutronflement\.com|douceurdebulles\.com|institut-ronflement\.com|elx03\.com|elx02\.com|monsacdecreateur\.com|destockexpert\.com|jeu-matablette\.com|jeu-cheque2000euros\.com|1andecroquettes\.com|ela-conta\.com|elixisprevention\.com|leivale\.com|jeu-couturier\.com|123uomo\.com|mailkraft\.de|senxuel\.com|salia\.fr|ysia\.cz)$',hostname)
    if matches: return True
    matches = re.search('\.(jeu-cartes\.com|konsumeo\.com|plumepil\.com|afjdk9\.com|afk7hdh\.com|afkkjd9\.com|afld9fjd\.com|afjdh8\.com|afj9jh\.com|afhvgh\.com|afhshd8\.com|afh8jjn\.com|afh8hv\.com|afh7hh\.com|afdf5h\.com|afd8jub\.com|afd8jh\.com|af9jdjf\.com|af9fdh\.com|af7hduh\.com|af7hdhfh\.com|af6fdfd\.com|credit-rembourse\.com|cheque-2017euros\.com|mesmeubles-offerts\.com|mavoiture-offerte\.com|mon-telephone-offert\.com|wisu\.fr|affaires-du-moment\.fr|affaires-exclusives\.fr|jolice\.fr|elixis-worldpanel\.fr|elixis-panel\.fr|elixispanel\.fr|elixisworldpanel\.fr|witrk-ing\.com|witrking\.com|puericultrices\.fr|mycosmeticbox\.fr|finebird\.cz|finebird\.fr|zynder\.fr|finebird\.net|realtys\.fr|chequeagagner\.com|jeu-jackpot\.com|jeu-gourmand\.com|jeu-videobuzz\.com|sondage-banque\.com|retrack\.fr|luxehotel\.fr|zaola\.fr|pass-cinema2\.com|justcook\.de|ange-marie\.fr|anys\.fr|1andecafe\.fr|3500euros\.com|16000euros\.com|cheque-2016euros\.com|jeudes3erreurs\.com|jeu-champagne\.com|roue-gagnante\.com|sondage-officiel\.com|isya\.cz|isya\.fr|zofia\.fr|cheque3000euros\.com|elyxis\.de|elyxis\.at|chrys\.fr|lifestylemedia\.fr|sondage-noel2\.com|sondage-noel\.com|repasdefetes\.fr|citys\.fr|elixisagency\.fr|elixis-agency\.fr|destock-experts\.fr|destockexpert\.fr|destock-expert\.fr|destockexperts\.fr|lidys\.fr|make-up-line\.fr|smartphoneoffert\.com|aniss\.fr|laedis\.fr|leadis\.fr|meilleurespromos\.fr|2100\.fr|macoachbeaute\.com|jeu-hifi\.com|mon-robot-patissier\.com|monparcanimalier\.com|tabletteofferte\.com|mesaccessoiressmartphone\.com|mavoitureofferte\.com|maconsoleofferte\.com|mon-championnat-football\.com|sanah\.fr|productesting\.net|gagnant-express\.com|waldorf\.fr|matcher\.fr|united-systemics\.com|elixis-worldpanel\.com|sandie\.fr|make-up-line\.net|shetell\.fr|zinder\.fr|belita\.fr|saffrondiscount\.com|institut-ronflement\.com|elx03\.com|elx02\.com|1andecroquettes\.com|wunderwerkzeug\.com|leadi\.fr|anisse\.fr|testingfreebies\.com|wirprodukttester\.com|produktetesten\.net|pruebaproductos\.com|yihi\.fr|salia\.fr|sondage-actu\.com|seona\.fr|avenue-shopping\.com|elixis-consult\.com|german-kunst\.com|bijoudumois\.com|argent-a-gagner\.fr|elixis-event\.com|laubenheimer\.info|riltis\.com|dnsriff\.com|xn--chimra-eua\.com|wimea\.fr|premium-service-hosting\.com|ysia\.com|yihii\.net|elixispanel\.com|elixis-panel\.com|germankunst\.com|voscourses\.fr|senxuel\.com|mysqlguide\.com|leivale\.com|unitedsystemics\.com|o2o\.fr|jeu-couturier\.com|123uomo\.com|mailkraft\.de|ysia\.cz|makeup-line\.fr|makeup-line\.net|make-up-line\.com|vod-illimite\.com|guap\.net|laedis\.com|conso-mieux\.com|jeu-gps\.com|elixisprevention\.com|elixisleads\.com|madame\.com|ela-conta\.com|retrotrend\.biz|jeu-chequecadeaux\.com|avis-officiel2\.com|galaxydesjeux\.com|meilleurdubuzz\.com|elixis\.info|kiwiactu\.com|silver-clope\.com|silverclope\.com|institutronflement\.com|douceurdebulles\.com|2015euros\.com|monsacdecreateur\.com|destockexpert\.com|jeu-matablette\.com|jeu-cheque2000euros\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(guardian-angel-messenger\.com|christin-chiaroveggenza\.com|christin-hellsehen\.com|christin-medium\.com|christin-voyance\.com|quantum-haschich\.com|quantumhaschich\.com|mental-waves-for-happiness\.net|mentalwavesforhappiness\.net|mentalwavesforhappiness\.com|happiness-waves\.com|mind-of-quantum\.com|wave-meditation\.com|waves-meditation\.com|quantum-attraction-law\.com|quantumattraction\.com|mentalwaves\.com|hypno-force\.net|hypnoforce\.net|hypno-force\.com|hypnoforce\.com|harvest-direct\.com|easy-coaching\.net|easy-coaching\.com|angels-reading\.com|angelical-reading\.com|golden-psychic-association\.net|world-psychic-society\.net|world-psychic-circle\.net|golden-psychic-association\.com|world-psychic-society\.com|world-psychic-circle\.com|harvestdirectltd\.com|christin-vidente\.com|christin-videncia\.com|mental-waves-for-happiness\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(programme-media|anne-exclu|amf-envoi|kama|strategiegagnante|promotionindustries|les-plans-voyages|la-vie-healthy|netreduc|dpmail|newsconso|onlinehome)\.fr$',hostname)
    if matches: return True
    matches = re.search('\.(wew151\.com|wew158\.com|wewm135\.com|wewmanager\.biz|bi-access\.us|select-nws\.com|info-bradycorp\.com|easybusinessmail\.com|cfe-contact01\.fr|ifyouexport\.com|post-reseaux-sociaux\.com|dom-send\.com|foreverliving-reunion\.com|ems-prem\.com|apave-news\.com|apave-link\.com|ems-send\.com|ma-newsletter-selectaux\.com|ma-lettre-selectaux\.com|vos-selections-privees\.com|vos-offres-selectaux\.com|vos-offres-reservees\.com|ems-strat\.com|lfb-subscribe\.com|lfb-interactive\.com|email-kom\.com|departements-envois\.com|departements-email\.com|departements-couriel\.com|ems-clouds\.com|email-stra\.com|information-newsletter\.com|information-couriel\.com|services-newsletters\.com|services-envois\.com|services-couriel\.com|wewcloud\.com|rp-prismamedia\.com|edimetainfo\.com|lemeilleuredesplansvoyages\.com|belacom-one\.com|emailstrategie-crm\.com|mail-strat\.com|thegoodtaget\.com|actu-apave\.com|multi-avantagesacef\.com|wewnotif\.com|nettoyage-base-email\.com|mutyder\.com|info-avantagesacef\.com|info-foreverliving-fr\.com|tumtum09\.com|clu-roch\.com|emy-send\.com|em-new\.com|domcloud19\.com|mail-mut\.com|mailin2be\.com|neam-send\.com|sendem10\.com|maileasybusiness\.com|batwew\.com|mut3envoi\.com|mut2send\.com|sendmut\.com|info-emb\.com|adf09\.com|clu-send917\.com|ppctsend09\.com|oksend0917\.com|sendy09\.com|send--ems-9\.com|route9-em\.com|newsemb\.com|location-email-b2b\.com|workday-ccmp\.com|worday-ccmp\.com|news-chateaudeau\.com|foreverliving-tu\.com|foreverliving-ma\.com|foreverliving-fr\.com|usnews-b2b\.com|peugeot-mail\.info|crh-cgos\.info|eurazeohappynewyear2017\.com|lecafepro\.com|newsmairies\.com|newsmairie\.com|bds-infos\.com|gamage-paint\.com|btocmail\.com|infobtoc\.com|manageo-mkt\.com|actu-trane\.com|1-id-mkt\.com|oneid-uk\.com|kompass-news\.com|info-royalcanin\.com|news-royalcanin\.com|royalcanin-news\.com|apavenews\.com|nws-emb\.com|nws-b2b\.com|wewmail\.biz|myoptin-list\.com|newsletter-omega\.com|subscribers-lists\.com|pro-nws\.com|mail-al09\.com|wew160\.com|biaccess\.com|info-apave\.com|appsyougo\.com|appsyougo\.net|mailkoa\.us|stingup\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(crazy-conso\.fr|ekjut\.com|celebrityhostage\.com|baticelik\.com|credit-malin\.net|gemlabarben\.fr|gemlabarben\.net|gemlabarben\.com|promo-folie\.com|gemlabarben\.info|galaxysportku\.com|crazy-promo\.com|gemlabarben\.biz)$',hostname)
    if matches: return True
    matches = re.search('\.(anti-spam-premium\.com|metaconcepto\.com|manzanan\.com|lige\.ro|agridulce\.com\.mx|365historias\.com|hospedando\.pro)$',hostname)
    if matches: return True
    matches = re.search('\.(newsprecieuse\.fr|actu-mme\.fr|newsinfprecieuse\.com|mmeprivee\.com|madameprivee\.com|lactupromo\.com|nlactupromo\.com|infofanactu\.com|nlbjrinformatie\.com|lesfrenchics\.fr|lesfrenchics\.com|maparfaitenews\.com|malettreinfo\.com|laparfaitenews\.com|nlmalettreinfo\.com|coindelinfo\.com|flashactualites\.com|leflashactu\.com|lecoindesinfos\.com|clubdesactus\.com|leclubactu\.com|bonjourinfo\.com|bjrinfo\.com|nlbonjourinfos\.com|hbwkmedia\.com|newsmixinfos\.com|mixinfos\.com|newsvisionconso\.com|linforock\.fr|nlparfaitenews\.fr|nlrockinfo\.fr|consoenlive\.fr|consolive\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(effetincroyable\.fr|best-renov\.com|asl-dns\.com|mediawam\.com|big-fute\.fr|multinano\.fr|skybyweb\.fr|send321\.net|prom657\.com|touix22\.com|red654\.com|pm220\.com|blue34\.net|cd355\.com|magiquecrayon\.fr|activiteactu\.fr|mm6-4410\.com|reseausurlenet\.fr|autonow\.fr|voyage-du-net\.fr|newsgroup-asexp\.com|life-actu-ap\.com|stat-asmd\.com|life-asnb\.com|le-best-des-offres\.net|la-plus-grande-ambition\.com|abricot1240\.com|sp-assu\.fr|feminabuzz\.com|manomino\.fr|time-crayon\.fr|innovationnature\.fr|newexpertise\.fr|horscommun\.fr|creation-fute\.fr|uneffort\.fr|service-creation\.fr|la-mode-au-soleil\.fr|mondialmedia\.fr|bonnelife\.fr|vision-touch\.fr|les-bons-delais\.fr|voyage-evasion\.fr|kits-deco\.fr|simu-immo\.fr|cookcookies\.fr|geolocwhite\.fr|coindesdeals\.fr|info-invest\.fr|immofutur\.fr|feminabuzz\.fr|web-solution\.fr|world-asmx\.com|twins-of\.fr|bonjour-aline\.fr|lagrandefete\.fr|minounou\.fr|lebonformulaire\.fr|stationinfo\.fr|par-isnice\.fr|lanewsdutemps\.fr|typetype\.fr|news-center\.fr|claptoc\.fr|lebonpouvoir\.fr|elodiemarie\.fr|oh-soleil\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(malin-kdo\.fr|look-sales\.com|stop-to-spam\.com|decouverte-naturelle\.com|discount-element\.com|thedigitlabs\.com|offresflash\.com|themininglabs\.com|themailinglabs\.com|monactufacile\.com|tdl-bdd\.com|express-actu\.com|thesocialmining\.com|ledgerprice\.com|promoccasion\.com|lesmeilleursoffres\.com|free-conso\.fr|mesnewsinfos\.com|turbo-infos\.com|boulevarddespromos\.com|penser-eco\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(daily-stars\.com|chic-world\.com|faitesvotremarche\.com|azodt\.com|targetemailing\.com|your-daily-deal\.com|infos-web\.fr|c-est-un-spam\.com|signaler-un-spam\.com|smart-shopping\.us|webgift\.fr|silver-actu\.com|webbest\.fr|perles-rares\.com|mon-choix\.fr|web-best\.fr|net-best\.fr|gold-privilege\.com|coffres-aux-nouvelles\.fr|deal-to-day\.fr|webcadeaux\.fr|better-net\.fr|meilleurs-du-net\.fr|mediamail\.fr|executive-services\.fr|azaudience\.com|meilleures-astucesweb\.fr|azmktdata\.com|aztracklink\.com|webinallnews\.com|missiweb\.com|illico-novelties\.com|azorica\.biz|azorica\.net|azorica\.fr|web-bonsplans\.fr|espace-sante-bien-etre\.fr|les-meilleurs-du-web\.fr|coffre-aux-affaires\.fr|net-bonsplans\.fr|fortune-teller\.us|so-bright-idea\.us|net-affaires\.fr|linvincible\.fr|somuchweb\.fr|magazine-info\.fr|azclics\.com|initializ\.fr|lecornerderosalie\.com|lecornerderosalie\.fr|lecornerdeglantine\.com|lecornerdeglantine\.fr|deal-italia\.it|masterpromo\.fr|meilleurs-du-web\.fr|italead\.it|esperance-fidelite\.fr|service-consomateurs\.fr|plansdunet\.fr|usine-chic\.fr|infos-online\.fr|plansduweb\.fr|lhommevirile\.fr|webinall\.fr|webpourtoi\.fr|meilleurs-moment\.fr|direction-web\.fr|accord-bien-etre\.fr|time-to-chic\.com|qualite-news\.com|sante-et-sciences\.com|pepite-du-web\.com|news-famous\.com|gold-actu\.com|famous-corner\.com|famous-privilege\.com|domaine-exclu\.com|domaine-privilege\.com|corner-first\.com|corner-promo\.com|corner-top\.com|daily-corner\.com|actu-top\.com|q-v-c\.net|sante-et-sciences\.net|concept-orchydee\.fr|les-meilleurs-du-net\.fr|mon-concept-sante\.fr|procede-minceur\.fr|selection-pour-vous\.com|cest-pour-vous\.fr|mes-coups-de-coeur\.fr|chic-nouvelles\.fr|azaout\.com|azandclic\.com|azanddes\.com|un-don-en-or\.fr|ma-malle-aux-tresors\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(summitcnty\.com|dtsbusiness\.com|istanbulbigdatasummit\.world|yourwebevent\.com|bogazicibasin\.com|business2017\.club|kajuncompany\.com|eventuniver\.com|ghcertificate\.com|naruniversity\.com|oneventreg\.com|perfpress\.com|bioscompany\.com|mybriteideas\.com|clockworkeventz\.com|meramecevents\.com|gmatalent\.com|university2business\.info|trizuniversity\.com|summitoncor\.com|summitmb\.com|summitsup\.com|nytcollege\.com|magescompany\.com|greateventsinc\.com|contacts4business\.com|bmsnews\.com|triosevents\.com|certificationsolution\.com|educationinter\.net|inteccompany\.com|cfievents\.com|ctbusinesstoday\.com|inmethodist\.net|businessounds\.com|intelconference\.com|fbstatuspics\.in|whatsappimages\.us|worldaidsday2015quotes\.com|gandhijayanti2015sms\.in|summitcoe\.com|eventden\.net|optsevents\.com|peoplevents\.com|vciglobal\.com|tcfevents\.com|ieventnet\.com|rsiuniversity\.com|happynewyear2016quotesf\.com|eventssouth\.com|sanuniversity\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(web-solution\.fr|mondialmedia\.fr|best-renov\.com|asl-dns\.com|mediawam\.com|big-fute\.fr|multinano\.fr|skybyweb\.fr|send321\.net|prom657\.com|touix22\.com|red654\.com|pm220\.com|blue34\.net|cd355\.com|magiquecrayon\.fr|activiteactu\.fr|mm6-4410\.com|reseausurlenet\.fr|autonow\.fr|voyage-du-net\.fr|world-asmx\.com|newsgroup-asexp\.com|life-actu-ap\.com|stat-asmd\.com|life-asnb\.com|le-best-des-offres\.net|la-plus-grande-ambition\.com|abricot1240\.com|sp-assu\.fr|feminabuzz\.com|manomino\.fr|time-crayon\.fr|lebonpouvoir\.fr|innovationnature\.fr|newexpertise\.fr|horscommun\.fr|creation-fute\.fr|uneffort\.fr|effetincroyable\.fr|service-creation\.fr|la-mode-au-soleil\.fr|bonnelife\.fr|typetype\.fr|vision-touch\.fr|les-bons-delais\.fr|news-center\.fr|voyage-evasion\.fr|kits-deco\.fr|simu-immo\.fr|cookcookies\.fr|geolocwhite\.fr|coindesdeals\.fr|info-invest\.fr|immofutur\.fr|feminabuzz\.fr|twins-of\.fr|bonjour-aline\.fr|lagrandefete\.fr|minounou\.fr|lebonformulaire\.fr|elodiemarie\.fr|stationinfo\.fr|par-isnice\.fr|lanewsdutemps\.fr|claptoc\.fr|oh-soleil\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(toptilbudfor\.com|toptilbudfordig\.com|galaxiedessoldes\.fr|mesenviesbeaute\.fr|laplaceauxbonnesaffaires\.fr|jouwinkelwensen\.com|uwwinkelwensen\.com|uwwinkelwens\.com|lesastucesenfolie\.fr|trackvm\.net|mijnshoppingadviezen\.com|mijnshoppingadvies\.com|mijnshopingadvies\.com|mondoprivilegi\.com|mailboutique2\.com|online-erbjudanden\.com|newsdelamoda\.com|sorpresaspara-todos\.com|sorpresasparatodos\.com|uwshoppinggids\.com|tusrebajaspremium\.com|vertigoaroundtheworld\.com|aankopvandedag\.com|godsavethedeal\.com|godsaveourdeal\.com|godsavemydeal\.com|vmwwtracking\.com|vrtagency\.net|vertigotracktrackworld\.com|estrakingvmes\.com|svertigomes\.com|vertigotrackww\.com|demagencyvma\.com|vmaitaly\.net|vmaitaly\.com|vertigotrackit\.net|nyetilbudidag\.com|trankingspainvmes\.com|uusiakampanjoitaatanaan\.com|bestchoicesday\.com|nyetilbudida\.com|nyetilbuddag\.com|trendshoptoday\.com|trendingshoppingtoday\.com|toptilbuddig\.com|vertigotrackit\.com|uusiakampanjoitatanaan\.com|uusiakampanjoitaaa\.com|daspping\.com|uniikittarjouk-set\.com|aankoopvandedag\.com|uniikit-tarjouk-set\.com|affiliationes-vmes\.com|estracking-vmes\.com|web-tilbud\.com|el-chollo-fresco\.com|deter-dagendin\.com|det-er-dagendin\.com|deterdagendin\.com|el-chollofresco\.com|elchollofresco\.com|matesratestoday\.com|dandy-shopping-today\.com|dandy-shoppingtoday\.com|dandyshoppingtoday\.com|solo-para-el\.com|solo-parael\.com|todo-para-ella\.com|todo-paraella\.com|finestdealstoday\.com|vmaffiliation\.com|lavenue-desreves\.com|tu-chollo-alerta\.com|tu-cholloalerta\.com|tucholloalerta\.com|your-daily-surprise\.com|your-dailysurprise\.com|beste-kampanjer\.com|bestekampanjer\.com|kampanjer-nya\.com|nya-kampanjer\.com|nyakampanjer\.com|tilbud-seneste\.com|uniikit-tarjoukset\.com|uniikittarjoukset\.com|vrtrackdem\.net|vrtrackdem\.com|trackingit-vmp\.com|trackinges-vmp\.com|vertigomediaperformanceprivacypolicy\.com|caprichos-en-laweb\.com|caprichos-enlaweb\.com|caprichosenlaweb\.com|chauffage-aterno-vmp\.com|allt-for-dig\.com|allt-fordig\.com|la-ventaja-deldia\.com|la-ventajadeldia\.com|laventajadeldia\.com|paseo-de-las-ventajas\.com|dagenstipsning\.com|mailtorget\.com|rabat-tilbyder\.com|event-torget\.com|unhommeunjour\.com|karuselli-tarjous\.com|monsigneduzodiaque\.com|unereductionunjour\.com|lavenuedesopportunites\.com|vertigodata\.com|sophiesuggests\.com|deal-selection\.com|smart-avenue\.com|tribaloffers\.com|vosoffrespremium\.com|lesnouveautesduweb\.com|cd-reactivation2\.com|cd-reactivation1\.com|unflirtdunjour\.com|unerencontreunflirt\.com|monshoppingrelax\.com|monparadisdushopping\.com|online-tilbyder\.com|yourdailysavings\.com|newsdemoda\.com|mestopspromos\.com|monexclusiviteduweb\.com|mavoyanceofferte\.com|mespromosdujours\.com|mesopportunitesduweb\.com|mesdealsdunet\.com|larencontredujour\.com|rebajaspremium\.com|shop-inst-1\.com|korting-club\.com|lhoroscopedelajournee\.com|discountsdistrict\.com|suggestionshack\.com|annieadvises\.com|thebargainparlour\.com|vickivalues\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(emailstunter\.com|lavenuedesopportunites\.com|toptilbudfor\.com|toptilbudfordig\.com|galaxiedessoldes\.fr|jouwinkelwensen\.com|uwwinkelwens\.com|mesenviesbeaute\.fr|lesastucesenfolie\.fr|trackvm\.net|mijnshoppingadviezen\.com|mijnshoppingadvies\.com|mijnshopingadvies\.com|mondoprivilegi\.com|mailboutique2\.com|online-erbjudanden\.com|newsdelamoda\.com|sorpresaspara-todos\.com|sorpresasparatodos\.com|uwshoppinggids\.com|tusrebajaspremium\.com|vertigoaroundtheworld\.com|aankopvandedag\.com|godsavethedeal\.com|godsaveourdeal\.com|godsavemydeal\.com|vmwwtracking\.com|vrtagency\.net|vertigotracktrackworld\.com|estrakingvmes\.com|svertigomes\.com|vertigotrackww\.com|demagencyvma\.com|vmaitaly\.net|vmaitaly\.com|vertigotrackit\.net|nyetilbudidag\.com|uusiakampanjoitaatanaan\.com|bestchoicesday\.com|choicesofdtoday\.com|nyetilbudida\.com|nyetilbuddag\.com|trendshoptoday\.com|trendingshoppingtoday\.com|toptilbuddig\.com|vertigotrackit\.com|uusiakampanjoitatanaan\.com|uusiakampanjoitaaa\.com|daspping\.com|uniikittarjouk-set\.com|aankoopvandedag\.com|uniikit-tarjouk-set\.com|vmptrack\.com|affiliationes-vmes\.com|estracking-vmes\.com|web-tilbud\.com|yourdaily-surprise\.com|el-chollo-fresco\.com|deter-dagendin\.com|det-er-dagendin\.com|deterdagendin\.com|el-chollofresco\.com|elchollofresco\.com|matesratestoday\.com|dandy-shopping-today\.com|dandy-shoppingtoday\.com|dandyshoppingtoday\.com|solo-para-el\.com|solo-parael\.com|todo-para-ella\.com|todo-paraella\.com|finestdealstoday\.com|vmaffiliation\.com|lavenue-desreves\.com|tu-chollo-alerta\.com|tu-cholloalerta\.com|tucholloalerta\.com|your-daily-surprise\.com|your-dailysurprise\.com|beste-kampanjer\.com|bestekampanjer\.com|kampanjer-nya\.com|nya-kampanjer\.com|nyakampanjer\.com|tilbud-seneste\.com|uniikit-tarjoukset\.com|uniikittarjoukset\.com|lespepitesdujour\.com|nye-nyheder\.com|vrtrackdem\.net|vrtrackdem\.com|trackingit-vmp\.com|trackinges-vmp\.com|vertigomediaperformanceprivacypolicy\.com|caprichos-en-laweb\.com|caprichos-enlaweb\.com|caprichosenlaweb\.com|chauffage-aterno-vmp\.com|allt-for-dig\.com|allt-fordig\.com|newsdemoda\.com|rebajaspremium\.com|tribaloffers\.com|shop-inst-1\.com|vosoffrespremium\.com|lesnouveautesduweb\.com|monexclusiviteduweb\.com|mesdealsdunet\.com|vertigodata\.com|dagenstipsning\.com|mailtorget\.com|online-tilbyder\.com|rabat-tilbyder\.com|sophiesuggests\.com|deal-selection\.com|yourdailysavings\.com|lhoroscopedelajournee\.com|larencontredujour\.com|unereductionunjour\.com|smart-avenue\.com|annieadvises\.com|discountsdistrict\.com|suggestionshack\.com|thebargainparlour\.com|vickivalues\.com|monshoppingrelax\.com|monparadisdushopping\.com|mestopspromos\.com|mespromosdujours\.com|mesopportunitesduweb\.com|event-torget\.com|unhommeunjour\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(dcenter-mta\.com|eml-ago\.com|radegonde295\.com|vml-174\.com|aprem-rs\.com|aprem-pr\.com|aprem-op\.com|aprem-no\.com|aprem-mn\.com|aprem-lm\.com|aprem-kl\.com|aprem-jk\.com|aprem-ij\.com|aprem-hi\.com|aprem-gh\.com|aprem-fg\.com|aprem-ef\.com|aprem-az\.com|plateougazeuse-news\.com|aprem-de\.com|aprem-cd\.com|aprem-bc\.com|aprem-ab\.com|aprem-02\.com|aprem-01\.com|ozbiosciences-news\.com|aproxeml40\.com|aproxeml39\.com|aproxeml38\.com|aproxeml37\.com|aproxeml36\.com|aproxeml35\.com|aproxeml34\.com|aproxeml33\.com|aproxeml32\.com|aproxeml31\.com|aproxeml30\.com|aproxeml29\.com|aproxeml28\.com|aproxeml27\.com|vml-elm\.com|md-de\.com|md-cd\.com|aproxeml26\.com|aproxeml25\.com|aproxeml24\.com|aproxeml23\.com|aproxeml22\.com|aproxeml21\.com|aproxeml20\.com|aproxeml19\.com|aproxeml18\.com|aproxeml17\.com|aproxeml16\.com|aproxeml15\.com|aproxeml14\.com|aproxeml13\.com|c-doc\.fr|pm-rev\.net|vnsidu\.info|vasotap\.info|vmdiop\.info|vetapoda\.info|vtirufjg\.info|vtieeu\.info|vitypotu\.info|vtrudh\.info|vtsizord\.info|vitudip\.info|bpmd04\.info|vustopedia\.info|vtqrsedf\.info|vturigog\.info|vtrydfuf\.info|vmodisuq\.info|vtqraez\.info|vastop-alpha\.info|vnp45\.info|vmisudy\.info|vtrueyf\.info|vmfdserr\.info|vtfdcfh\.info|vmdisu\.info|bpmd03\.info|visto-meta\.info|vmpaoei\.info|dcenter-mta\.info|vtroxci\.info|vip-sitam\.info|vutopeta\.info|vtarse\.info|vip-opb\.info|vtqrefg\.info|vtrueio\.info|vytispod\.info|vtrozpsi\.info|vtrufyg\.info|vtqrsed\.info|vtrtyuiu\.info|vtrsydf\.info|vmle-eif\.com|vml-pdf\.com|vml-745\.com|vmlty\.com|vml895\.com|vml541\.com|vml0345\.com|aproxeml10\.com|vmltc\.com|bpmd03\.net|veml02\.com|vml-165\.com|vml-145\.com|vml-123\.com|vml04\.com|v-sitam\.com|barnabe295\.com|vieor23\.com|vml-ext7\.com|vml-ext6\.com|vmle-ext7\.com|vmle-ext6\.com|bpm06\.net|bpm039\.com|vmlta\.com|vml-pat\.com|vmle-rts\.com|vmleo\.com|vmlek\.com|vmleg\.com|vmle445\.com|tpworks\.com|vml-yaz\.com|vml-210\.com|vml-103\.com|vml-09\.com|vml03\.com|vme-uyt\.com|vme-124\.com|nandi410\.com|power-mta\.com|machaire295\.com|distri-mta\.com|mail0121\.com|trk-beta\.net|vml-xaw\.com|vmle985\.com|vmle784\.com|vrtfygu34\.info|vme-147\.com|vmlez\.com|vtiruf\.com|dcenter-meta\.com|smart-emailing\.com|bpmd02\.info|vmsjchw\.info|vtryfdug\.info|vmghbvy\.info)$',hostname)
    if matches: return True
    matches = re.search('\.(centraldirecte\.pro|premiereza\.pro|premierepo\.pro|gazettegamme\.pro|completenews\.pro|completela\.pro|completego\.pro|completegi\.pro|completege\.pro|ifvl\.pro|gazettefacile\.pro|premierese\.pro|premieresite\.pro|premiereza\.pro|premierepo\.pro|premiereope\.pro|premiereok\.pro|premierenews\.pro|premierenew\.pro|premierene\.pro|premierena\.pro|pratiquedes\.pro|pratiquecop\.pro|pratiquecie\.pro|pratiqueces\.pro|pratiqueca\.pro|pratiquefr\.pro|enlignema\.pro|enlignem\.pro|enlignelo\.pro|enligneca\.pro|enligneces\.pro|enlignecie\.pro|enlignecop\.pro|enlignedes\.pro|enlignefr\.pro|enlignega\.pro|enlignege\.pro|enlignegi\.pro|enlignego\.pro|enlignela\.pro|enligneles\.pro|enligneli\.pro|enlignemo\.pro|enlignena\.pro|enlignene\.pro|enlignenew\.pro|enlignenews\.pro|enligneope\.pro|enlignepo\.pro|enlignese\.pro|enlignesite\.pro|enligneza\.pro|gazetteachat\.pro|gazetteannonce\.pro|pratiquega\.pro|pratiquege\.pro|pratiquegi\.pro|pratiquego\.pro|pratiquela\.pro|pratiqueles\.pro|pratiqueli\.pro|pratiquelo\.pro|pratiquem\.pro|pratiquema\.pro|pratiquemo\.pro|pratiquena\.pro|pratiquene\.pro|pratiquenew\.pro|pratiquenews\.pro|pratiqueok\.pro|pratiqueope\.pro|pratiquepo\.pro|pratiquese\.pro|pratiquesite\.pro|pratiqueza\.pro|enligneok\.pro|officedepeche\.pro|serviceoffre\.pro)$',hostname)
    if matches: return True
    matches = re.search('\.(wr01|mkt-synd\.com|mkt-base\.com|mkt-ags\.com|txnmail\.com|nosuchmail\.com|xland\.cz|xmail\.cz|mailkit\.cz|free2mail\.us|ajsdhasd\.com|invalidomain\.com|emailnight\.cz|cust\.cz|simak\.cz)$',hostname)
    if matches: return True
    matches = re.search('\.(bp-surf\.fr|laminuteconso\.fr|infoprivilege\.com|avantage-conso\.com|devis-conso\.com|projet-conso\.com|afficl\.com|optin-box\.biz|optin-box\.info|conso-minute\.net|conso-minute\.com|consominute\.com|conso-minute\.biz|consominute\.biz|consominute\.info|minute-conso\.net|minuteconso\.net|maminuteconso\.net|laminuteconso\.net|minute-conso\.info|minuteconso\.info|maminuteconso\.info|laminuteconso\.info|laminuteconso\.com|minute-conso\.com|minuteconso\.com|minute-conso\.biz|minuteconso\.biz|maminuteconso\.biz|laminuteconso\.biz|mediazix\.biz|mediazix\.net|mediazix\.info|mediazix\.com|crr234\.com|consoreduc\.com|consoreduc\.net|clubdesmalins\.net|consoreduc\.biz|clubdesmalins\.biz|m-a67\.com|astu-club\.com|affinilead\.net|affinilead\.info|affinilead\.com|affinilead\.biz|affini-lead\.net|affini-lead\.info|affini-lead\.com|affini-lead\.biz|conso-surf\.info|consosurf\.info|consoreduc\.info|mediazeen\.com|mediazeen\.biz|conso-surf\.net|consosurf\.net|conso-surf\.com|consosurf\.com|conso-surf\.biz|consosurf\.biz|affinilead\.org|affini-lead\.org|consominute\.fr|minuteconso\.fr|maminuteconso\.fr|minute-conso\.fr|cs-surf\.fr|red-34\.fr|mdzn\.fr|astcb\.fr|appwego\.fr|dealwego\.fr|optin-box\.fr|ldaffi\.fr|ldaffi\.com|affild\.com|optin-box\.net|optin-box\.com|institut-consosurf\.com|institutconsosurf\.com|conso-minute\.fr|cdm56\.fr|consominute\.net|clubdesmalins\.info|affild\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(entraide-mistergooddeal\.com|communaute-matelsom\.com|communaute-carredeboeuf\.com|communaute-maisonsdumonde\.com|communaute-m6boutique\.com|communaute-accorhotels\.com|communaute-darty\.com|communaute-sonovente\.com|apreslachat\.info|weebeelong\.com|afterbuying\.com|produitpedia\.net|produitpedia\.info|produitpedia\.com|produitpedia\.biz|support-singerfrance\.com|wibilong\.net|weebeelong\.net|mongpsenregle\.com|nachdemkauf\.com|dopolacquisto\.com|before-buying\.com|apreslachat\.net|afterbuying\.net|entraide-grosbill\.com|apreslachat\.com|communaute-ambassadeurs-darty\.com|communaute-clubmakers\.com|communaute-aramisauto\.com|productpedia\.fr|communaute-ponant\.com|apreslachat\.fr|ambassadeurs-irobot-darty\.com|idgarages-entraide\.com|produitpedia\.fr|communaute-direct-energie\.com|communaute-lerobert\.com|clients-testeurs-weldom\.com|clubmed-community\.com|productpedia\.it|accorhotel-community\.com|communaute-lg\.com|communaute-parapharmacie-leclerc\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(teamwinwin\.net|santenews-dz\.com|badyn\.fr|partelier\.com|cabinetdentaire\.pro|arabdn\.tech|adncm\.com|teamwinwin\.biz|teamwinwin\.info|totravelkeys\.com|copprofesores\.com|hotelduval-dz\.com|adnhost1\.net|nuochoa\.fr|beaurivagezelfana\.com|mbahguru\.net|8018598\.com|velopubalgerie\.com|emediasol\.net|emediasol\.info|shilohgroup\.in|barbarosse\.com|international-adn\.com|ikoneinbox\.fr|ikoneinbox\.com|adnstudio\.fr|beaurivage-zelfana\.com|httplachatdelsorriso\.com|onetservices\.net|architecturedisciplinenetwork\.net|architecturedisciplinenetwork\.com|adn-multigaming\.net|echraq\.com|belyel\.com|usgrainstz\.net|parentucelli\.com|paie-btp\.com|tanbreed\.com|mail2dar\.com|allxhtmlneeds\.com|adntt\.com|adn-soft\.com|adn-markhor\.com|adn-luxembourg\.com|adneurope\.com|mzizimamotorsports\.com|msimbazieyecentre\.com|ademonice06\.com|sudokeys-openerp\.com|sudokeys\.com|adntz\.com|adn-m\.com|polaris-ktm\.com|angoradesneiges\.com|q7creations\.com|adn-stp\.com|rafimit\.com|adnmarcas\.com|mistertec\.us|voceador\.com|spacetraveltz\.com|planning-btp\.com|peinturesmultipro\.com|open-source-formation\.com|my-open-shop\.com|interchicktz\.com|fondation-adn\.net|gasiglia\.com|firevoip\.net|dreamamins\.com|cheminee-design\.com|bwr-iota\.com|atemitz\.com|alinea5\.com|art-quantique\.com|adnonline\.net|adn-genetic\.com|adn-cannes\.net|adnarchi\.net|adnaccess\.com|agenciaadn\.com|directorioforense\.com|vijaycosmeticsurgeon\.com|sudokey\.com|open-payroll\.com|arquitecturadenegocios\.com|gysel\.fr|email-dentaire\.com|sedyl\.fr|adndisplay\.com|adiaznavarro\.net|sarlazlogistics\.com|almakteb\.com|agencecit\.com|alldesignneeds\.com|tawisa\.com|dental-kingdom\.com|emediasol\.com|melanyrestaurant\.com|quicksaver\.com|allgraphicneeds\.com|dhsale\.com|itinvent\.net|fyrecoin\.com|halalrest\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(officepratique\.pro|servicepronote\.pro|officelivre\.pro|gazettegarantie\.pro|supportpratiques\.pro|supportpratique\.pro|supportplanete\.pro|supportoffres\.pro|supportoffre\.pro|supportoffice\.pro|supportnote\.pro|supportmois\.pro|supportmobile\.pro|supportmin\.pro|supportmessage\.pro|supportmedia\.pro|supportmax\.pro|supportmarche\.pro|supportmarchande\.pro|supportmagasin\.pro|lesavis\.pro|ecoclub\.pro|doubleticket\.pro|doublesupport\.pro|doublesuite\.pro|doublesolution\.pro|doublesmart\.pro|doublesilver\.pro|doubleshop\.pro|doubleservices\.pro|doubleservice\.pro|doubleregistre\.pro|doubleprox\.pro|doublepros\.pro|doublepronote\.pro|doublepro\.pro|doubleprivilege\.pro|doublepremiere\.pro|doublepratiques\.pro|doublepratique\.pro|doubleplanete\.pro|doubleoffres\.pro|doubleoffre\.pro|doubleoffice\.pro|doublenote\.pro|doublemois\.pro|doublemobile\.pro|doublemin\.pro|doublemessage\.pro|doublemedia\.pro|doublemax\.pro|doublemarche\.pro|doublemarchande\.pro|doublemagasin\.pro|doublelivre\.pro|doublelivraison\.pro|doublelignes\.pro|doubleligne\.pro|doublelibre\.pro|doubleleasing\.pro|doublelabels\.pro|doublelabel\.pro|doublejournal\.pro|doublejour\.pro|doubleinternet\.pro|doubleinnovation\.pro|doubleinnov\.pro|doubleinfos\.pro|doubleinform\.pro|doubleinfo\.pro|doublegrossiste\.pro|doublegrandspace\.pro|doublegrandmag\.pro|doublegarantie\.pro|doublegamme\.pro|doublegalerie\.pro|doublefree\.pro|doublefranchise\.pro|doubleforce\.pro|doubleflash\.pro|doublefacile\.pro|doubleextras\.pro|doubleextra\.pro|doubleexposition\.pro|doubleexcellence\.pro|doubleeurope\.pro|doubleespace\.pro|doubleequipe\.pro|doubleenseigne\.pro|doubleenligne\.pro|doubledirecte\.pro|doubledirect\.pro|doubledepeche\.pro|doublecompte\.pro|doubleclubs\.pro|doubleclub\.pro|doublecentre\.pro|doublecarte\.pro|doublebulletin\.pro|doubleboutique\.pro|doublebillet\.pro|doublebilan\.pro|doubleavis\.pro|doubleannonce\.pro|ecoinnovation\.pro|ecoinnov\.pro|ecoinfos\.pro|ecoinform\.pro|ecoclubs\.pro|ecocentre\.pro|evoluciel-immobilier\.net|unpasapreslautre\.com|koph\.fr|capitaleo\.net|ts268\.com|monsupport\.pro|ecosgarantie\.pro|ecosinfo\.pro|ecoslabel\.pro|staarnet\.com|ecoachat\.pro|ecoannonce\.pro|ecoavis\.pro|ecobilan\.pro|ecobillet\.pro|ecoboutique\.pro|ecobulletin\.pro|ecocarte\.pro|ecocompte\.pro|ecodepeche\.pro|ecodirect\.pro|ecodirecte\.pro|ecoenligne\.pro|ecoenseigne\.pro|ecoequipe\.pro|ecoespace\.pro|ecoeurope\.pro|ecoexcellence\.pro|ecoexposition\.pro|ecoextra\.pro|ecoextras\.pro|ecofacile\.pro|ecoflash\.pro|ecoforce\.pro|ecofranchise\.pro|ecofree\.pro|ecogalerie\.pro|ecogamme\.pro|ecograndmag\.pro|ecograndspace\.pro|ecogrossiste\.pro|ecointernet\.pro|ecojour\.pro|ecojournal\.pro|ecolabels\.pro|ecoleasing\.pro|ecolibre\.pro|ecoligne\.pro|ecolignes\.pro|masuite\.pro|monpro\.pro|monshop\.pro|monticket\.pro|mpratiques\.pro|mpremiere\.pro|mprivilege\.pro|mpronote\.pro|mpros\.pro|mprox\.pro|mregistre\.pro|mservices\.pro|msilver\.pro|msolution\.pro|msservice\.pro|mssmart\.pro|rhynoceros\.com|cldnet\.net)$',hostname)
    if matches: return True
    matches = re.search('\.(journalza\.pro|journalsite\.pro|journalse\.pro|journalnews\.pro|premieremo\.pro|premierema\.pro|premierem\.pro|premierelo\.pro|premiereli\.pro|premiereles\.pro|premierela\.pro|premierego\.pro|premieregi\.pro|premierege\.pro|premierega\.pro|premierefr\.pro|premieredes\.pro|premierecop\.pro|premierecie\.pro|premiereces\.pro|premiereca\.pro|journalgi\.pro|journalpo\.pro|journalope\.pro|journalok\.pro|journalnew\.pro|journalne\.pro|journalna\.pro|journalla\.pro|journalgo\.pro|journalge\.pro|innovza\.pro|journalces\.pro|journalcie\.pro|journalcop\.pro|journaldes\.pro|journalfr\.pro|journalga\.pro|journalles\.pro|journalli\.pro|journallo\.pro|journalm\.pro|journalma\.pro|journalmo\.pro|innovnews\.pro|innovok\.pro|innovope\.pro|innovpo\.pro|innovse\.pro|innovsite\.pro|journalca\.pro|innovgi\.pro|innovgo\.pro|innovla\.pro|innovles\.pro|innovli\.pro|innovlo\.pro|innovm\.pro|innovma\.pro|innovmo\.pro|innovna\.pro|innovne\.pro|innovnew\.pro|innovfr\.pro|innovga\.pro|innovge\.pro)$',hostname)
    if matches: return True
    matches = re.search('\.(ultimatedietpill\.com|relationeer\.com|yourshoppingdirect\.com|thewrestlingshop\.com|meetalady\.com|kipkul\.com|digsmail\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(gazettefree|gazettelivraison|servicemin|servicemessage|servicemedia|servicemax|servicemarche|servicemarchande|servicemagasin|servicelivre|servicelivraison|gazetteflash|completenews|completela|completego|completegi|completege)\.pro$',hostname)
    if matches: return True
    matches = re.search('\.(nmem01\.com|netvisio-mail\.com|nm-reply\.com|onm59\.com|nmem03\.fr|obnm62\.com|grdf-mail\.com|gcd-notification\.com|gcd-mkg\.com|gcd-fid\.com|lead-clients\.com|email-netmessage\.com|sms3\.fr|ma-notification\.com|img-lb-client\.com|img-lbc\.com|mtsk\.fr|cnm02\.com|clic-ici\.fr|videosmart\.fr|oanm49\.com|nmsau\.com|snm80\.com|snm54\.com|snm36\.com|packmulticanal\.com|obnm75\.com|obnm74\.com|obnm72\.com|obnm71\.com|obnm70\.com|obnm67\.com|obnm64\.com|oanm79\.com|oanm78\.com|oanm71\.com|nmf05\.com|nmf04\.com|nmf03\.com|nmf02\.com|nmf01\.com|email-groupeinseec\.com|cliquez\.biz|arcaneo-actu\.com|cg-prosp\.com|cg-fid\.com|netmessage\.com|relay-nm\.com|oenm60\.com|ocnm61\.com|odnm60\.com|nmem03\.com|snm52\.com|boostluxe\.fr|sms-responsive\.net|sms-responsive\.com|responsive-sms\.net|responsive-sms\.com|ouestfi\.com|onm75\.com|onm74\.com|onm73\.com|onm72\.com|onm70\.com|onm35\.com|onm34\.com|fi44\.com|videopersonalisee\.com|video4people\.com|video4crm\.com|video4contact\.com|video-onetoone\.com|video-one2one\.com|video-one-to-one\.com|video-1to1\.com|onm77\.com|netmessage\.fr|onm36\.com|nmpe\.fr|mon-offre\.com|snm37\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(acheter-iptv\.com|understandstyle\.com|navapakethajiumroh\.com|blocsante\.com|sacspourelle\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(avantage-sondage\.fr|avantages-exclusifs\.fr|vmvshopping\.com|vmvmta\.com|vmv-produit\.com|offre-seniors\.fr|viewclic\.com|offre-privilege\.fr|vmv-shopping\.com|outmme\.com|bon-filon\.fr|panel-24\.fr|produit-du-jour\.fr|monetizheat\.com|acces-facile\.fr|fidelite-privilege\.fr|adn-du-jour\.fr|aubaine-conso\.fr)$',hostname)
    if matches: return True
    matches = re.search('(ats01\.net|edatisagency\.com|edatis\.fr|xemlt\.net|xeodata\.com|xeofolio\.com|weova\.com|xeosmail\.com|xeomail\.com|xn--cooprons-e1a\.com|macagnotteapepites\.com|vocationfinance\.com|odeance\.com|emp01\.net|edt04\.net|edt02\.net|emfo01\.net|emf01\.net|emd01\.net|emw01\.net|edatisdialog\.com|edatis\.net|edatis\.com|edatis\.biz|emtracking\.net|emtr01\.net|ema01\.net|cooperons\.com|fondative\.com|heraos\.com|pagiq\.com|edatis\.info)$',hostname)
    if matches: return True
    matches=re.search('(mkpbeta\.cz|cleversender\.com|boneym-revival\.com|cardeal\.cz|mkbusiness\.cz|mkpalfa\.cz|vinops\.cz|perfectyou\.cz|dennoc\.cz|ukz\.cz)$',hostname)
    if matches: return True
    matches=re.search('(bachespiscines-news|alerte-votre-argent\.fr|vive-le-plaisir\.fr|sport19\.fr|gla10\.com|poup21\.com|pile21\.com|tac128\.com|bur19\.com|elle30\.com|lamp75\.com|ouioui17\.com|oriane45\.com|rire60\.com|rigole29\.com|nouvelles-offres-ecommerce\.com|alsomatia\.com|top-monnaie\.com|eprogresse\.com|une-adresse-unique\.com|oba32\.com|club-opportunites\.com|cible-affaires\.com|infos-reduc\.com|selection-affaires\.com|nini16\.com|email-boxes\.net|lut20\.com|uju21\.com|lil11\.com|alerte-immobilier\.fr|image77\.fr|uau95\.com|cloitre45\.com|ohbonplans\.com|ohbonneidee\.com|espritmalin\.fr|optin-adresse\.fr|aj-data\.fr)$',hostname)
    if matches:return True
    matches=re.search('\.(mail101mailer\.com|mail189mailer\.com|dualis\.info|subscribedmailer\.info|cestri\.info|subscribedmailings\.net|mail105mailer\.com|subscribedmail\.net|emailflow\.net|vosita\.info|expris\.info|tantam\.info|subscribedmail\.com|canobi\.info)$',hostname)
    if matches: return True
    matches=re.search('\.(info-autobiz\.com|news-autobiz\.com|isiteasyperf1532\.com|cname-clients\.com|btob-autobiz\.com|htcmikolight8510\.com|ryilfami7740\.com|joreca\.com|my-autobiz\.com|rakbedsmi7195\.com|info04-autobiz\.com|info03-autobiz\.com|giulia-autobiz\.net|olivia-autobiz\.net|autobiz-confidential\.com|joreca\.it|campaigns-autobiz\.com|emailing-autobiz\.com|gtmromano5896\.com|ev2keojibdadzendeg\.com|ev1pioljinikabitibag\.com|joreca\.info|autobiz-market\.in|functiunopi5963\.com|newsletter2-autobiz\.com|newsletter1-autobiz\.com|joreca\.fr|autobiz-market\.uk|luivrabik\.com|newsletter4-autobiz\.com|newsletter3-autobiz\.com|autobiz-market\.co|bizdeviko8501\.com|joreca\.net|joreca\.biz|autobiz-market-china\.com|service-autobiz\.com)$',hostname)
    if matches: return True
    matches=re.search('\.(standardtel\.info|tradefair-trips\.com|tradefair-trips\.info|tradefair-trips\.net|go-fair\.net|go-fair\.info)$',hostname)
    if matches: return True
    matches=re.search('\.(digital-high-potentials\.com|ebginsights\.com|ebgnews\.net|ebgnews\.com|tech-benchmark\.com|digitalperformances\.net|digitalmood\.biz|ebg\.net|principesderealite\.com|ebg-digital-innovation\.com|franceleaders\.fr|the-digital-benchmark\.com|thedigitalbenchmark\.com|the-intrapreneur-group\.com|theintrapreneurgroup\.com|frenchleaders\.net|digital-jobs\.fr|cipango\.biz|cipango\.com)$',hostname)
    if matches: return True
    matches=re.search('(\.mix\.lextradeal\.com|smtp[0-9]+\.mix\.lextradeal\.com|\.espacebeautemarine\.fr|\.espacebeautemarine\.fr|mailuss9\.espacebeautemarine\.fr|\.aprgh\.fr|\.aprgh\.fr|srv1\.aprgh\.fr|f265-3\.lesmails\.world|\.lesmails\.world|\.lesmails\.world|\.mail0121\.com|\.mail0121\.com|smtp707\.mail0121\.com|\.presidentialinvitations\.info|server\.presidentialinvitations\.info|\.monthlymeetings\.info|server\.monthlymeetings\.info|\.info\.corporabase\.com|\.info\.corporabase\.com|mailm130\.info\.corporabase\.com|\.aproxeml\.com|srv3\.aproxeml\.com|\.aproxeml\.com|\.aproxeml18\.com|\.aproxeml18\.com|srv3\.aproxeml18\.com|srv23\.obchoice\.info|\.obchoice\.info|\.obchoice\.info|srv24\.obchoice\.info|\.newslove\.eu|\.newslove\.eu|tm6h\.newslove\.eu|\.gomboconline\.com|\.gomboconline\.com|webgalamb3\.gomboconline\.com|\.newsfree\.eu|\.newsfree\.eu|cvzp\.newsfree\.eu|\.news-relaxation\.net|\.news-relaxation\.net|mx62\.news-relaxation\.net|condor2242\.startdedicated\.com|\.yesnews\.eu|\.yesnews\.eu|zqvj\.yesnews\.eu|validatedforbiz\.com|hundredsroad\.com|f265-2\.lesmails\.world|\.qwarknews\.eu|\.qwarknews\.eu|zfkz\.qwarknews\.eu|\.cloudaiiot\.com|\.cloudaiiot\.com|d\.cloudaiiot\.com|\.ecolignes\.pro|\.ecolignes\.pro|vous\.ecolignes\.pro|\.ecolivre\.pro|\.ecolivre\.pro|vous\.ecolivre\.pro|clusterstream\.top|\.ecojour\.pro|\.ecojour\.pro|ope\.ecojour\.pro|\.prise-de-rendez-vous\.fr|\.prise-de-rendez-vous\.fr|emailing-2\.prise-de-rendez-vous\.fr|\.ecoinnov\.pro|\.ecoinnov\.pro|ope\.ecoinnov\.pro|\.officeequipe\.pro|\.officeequipe\.pro|aqb\.officeequipe\.pro|\.ecoligne\.pro|\.ecoligne\.pro|ope\.ecoligne\.pro|\.officeespace\.pro|\.officeespace\.pro|blk\.officeespace\.pro|reddivision\.fr|firstrouting\.top|vousenfete\.top|\.elisting\.date|\.elisting\.date|tempo20\.elisting\.date|nousenfete\.top|\.mailpower\.eu|\.mailpower\.eu|kr\.mailpower\.eu|\.simpleenterprise\.top|\.simpleenterprise\.top|solution4\.simpleenterprise\.top|retraite-prevoyance\.center|\.feltani\.fr|\.feltani\.fr|email\.feltani\.fr|\.tronami\.fr|\.tronami\.fr|email\.tronami\.fr|\.lirtivu\.fr|\.lirtivu\.fr|mail\.lirtivu\.fr|\.uranews\.eu|\.uranews\.eu|tr\.uranews\.eu|\.actu-man\.fr|\.actu-man\.fr|78\.actu-man\.fr|suivifacture\.com|onerouting\.top|\.bloknews\.eu|\.bloknews\.eu|me\.bloknews\.eu|impactroutea\.top|\.veryfresh\.eu|\.veryfresh\.eu|en\.veryfresh\.eu|\.zcsend\.net|\.zcsend\.net|sendera14\.zcsend\.net|\.blogonews\.eu|\.blogonews\.eu|l\.blogonews\.eu|\.smtp190\.com|\.smtp190\.com|ip-45-40\.smtp190\.com|\.vml541\.com|\.vml541\.com|srv3\.vml541\.com|ota198\.pro2aut\.com|\.pro2aut\.com|\.pro2aut\.com|concordvocals\.com|\.vml-xaw\.com|\.vml-xaw\.com|srv3\.vml-xaw\.com|\.centrescentre\.pro|\.centrescentre\.pro|mao\.centrescentre\.pro|twoinfotain\.top|mx\.net\.incontro\.ml|\.mailmyads\.eu|\.mailmyads\.eu|ri\.mailmyads\.eu|\.digitalefun\.pro|\.digitalefun\.pro|iel\.digitalefun\.pro|ag\.earthnews\.eu)$',hostname)
    if matches: return True
    matches=re.search('\.(galadmin-info-dns\.com|actuvoie\.com|pressebouquet\.com|revueline\.com|alireinfo\.com|galliad\.com|editonews\.com|actoprime\.com|nouvellelettre\.fr)$',hostname)
    if matches: return True
    matches=re.search('\.(les-avantages-pme\.com|comptoir-business\.com|comptoir-des-particuliers\.com|1001-business\.com|auto-moto-infos\.com|entreprises-du-web\.com|idees-du-jour\.com|mailody-particuliers\.com|mailody-business\.com|les-offres-mailody\.com|les-annonces-du-jour\.com|le-journee-des-affaires\.com|pratique-business\.com|offres-up\.com|voyages-de-la-semaine\.com|auto-idee\.com|actualites-immo\.com|abonnement-des-particuliers\.com|conso-actu\.com|entreprises-selection\.com|deco-particuliers\.com|garanties-offres\.com|mode-pratique\.com|voyages-top\.com|achat-unique\.com|finance-france\.com|bonnes-affaires-du-web\.com|alerte-abonnement\.com|achat-particuliers\.com)$',hostname)
    if matches: return True
    matches=re.search('\.(standardtel\.info|tradefair-trips\.com|tradefair-trips\.info|tradefair-trips\.net|go-fair\.net|go-fair\.info)$',hostname)
    if matches: return True
    matches=re.search('\.(gazetteservices\.pro|gazetteshop\.pro|gazettesilver\.pro|supportlabels\.pro|supportlabel\.pro|supportjournal\.pro|supportjour\.pro|supportinternet\.pro|supportinnovation\.pro|supportinnov\.pro|supportinfos\.pro|supportinform\.pro|supportflash\.pro|supportfacile\.pro|supportextras\.pro|supportextra\.pro|supportexposition\.pro|supportexcellence\.pro|supporteurope\.pro|supportespace\.pro|supportequipe\.pro|supportenseigne\.pro|supportenligne\.pro|supportdirecte\.pro|supportdirect\.pro|supportdepeche\.pro|supportcompte\.pro|supportclubs\.pro|supportclub\.pro|supportcentre\.pro|supportcarte\.pro|supportbulletin\.pro|supportboutique\.pro|supportbillet\.pro|supportbilan\.pro|supportavis\.pro|supportannonce\.pro|supportachat\.pro|gazetteticket\.pro|gazettesupport\.pro|gazettesuite\.pro|gazettesolution\.pro|gazettesmart\.pro|gazetteservice\.pro|gazetteregistre\.pro|gazetteprox\.pro|gazettepros\.pro|gazettepronote\.pro|gazettepro\.pro|gazetteprivilege\.pro|visuh\.info|fisrt\.info|enghy\.info|wwel\.info|supportlivre\.pro|supportlivraison\.pro|supportlignes\.pro|supportligne\.pro|supportlibre\.pro|supportleasing\.pro|supportinfo\.pro|supportgrossiste\.pro|supportgrandspace\.pro|supportfranchise\.pro|supportforce\.pro|supportgrandmag\.pro|supportgarantie\.pro|supportgamme\.pro|supportgalerie\.pro|supportfree\.pro|nexxt\.info)$',hostname)
    if matches: return True
    matches=re.search('\.(info3pro\.com|geniedeslieux\.info|visitezgoias\.com|rueilscope\.com|netshopper6\.com|netshopper4\.com|netshopper3\.com|netshopper2\.com|info2pro\.net|diapologic\.com|pronews-online\.info|pronews-online\.biz|info2pro\.com|info4pro\.com|chrysaleadtrappes\.fr|cycles-pena\.com|re-veille\.com|legeniedeslieux\.com|chrysaleadtrappes\.biz|netshopper5\.com|info5pro\.com|visitezpanama\.com|legeniedeslieux\.pro|european-accreditation\.com|tropicaleza\.com|007voyages\.com|netshopper7\.com)$',hostname)
    if matches: return True
    matches=re.search('\.(lacledesmails\.fr|loffremagique\.fr|espace-offres\.fr|lanewsendelire\.fr|lecadodujour\.fr|lesdealsdujour\.fr|leparcdesdeals\.fr|thecado\.fr|laruedesoffres\.fr|lanewsdeparis\.fr|mailincroyable\.fr|lamagiedesoffres\.fr|boulevarddesaffaires\.fr|promos-news\.fr|lavenuedesaffaires\.fr|lebestdeal\.fr|lecercledesaffaires\.fr|thecoin\.fr|deals-magiques\.fr|comptoir-offres\.fr|cercle-offres\.fr|espace-deals\.fr|espace-offres\.com|offre-conso\.fr|deals-promos\.fr|loffreirreelle\.fr|latopdesoffres\.fr)$',hostname)
    if matches: return True
    matches=re.search('\.(dzetasend\.fr|airtelawesomeyou\.com|geometrykenya\.com|diaz-foundation\.com|kqconnections\.com|equitygroupholdings\.com|switchontanzania\.com|ogilvylounge\.com|squaddigital\.info|squadlab\.com|firstcapitalbank\.net|wilfridosalas\.com|digitalsquad\.us|cpdcsn\.com|rtzsend\.com|rtxsend\.com|rtvsend\.com|rtabsend\.com|rtaasend\.com|boostmydata\.net|boostdata\.net|boostmydata\.com|wakeupdata\.net|easycdp\.net|liberationbyinesfly\.com|epsilonsend\.fr|thetasend\.fr|mutuelle-conseil-er\.fr|easyvoyage-er\.fr|spartoo-er\.fr|boostdata\.fr|boostmydata\.fr|wakeupdata\.fr|rtisend\.com|rtesend\.com|rtasend\.com|firstcapitalbank\.biz|timiza\.net|timiza\.info|youvsnjaanuary\.com|mpesafoundationacacdemy\.com|firstcapitalbank\.info|cytonnaire\.com|sprayforchange\.com|kfcsocial\.com|airtelfursa\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(dsl\.ovh|roi-de-versailles78|mange-vieprofite|droleries-de-dingue|pleine28|domainepardefaut|salditu|netastuce|leservicedesastuces|ce-moment|nouvelleanne|merveilles-de-france|mon-ideal-web|brillance59|mailvip95|offre-privilege|actu-denfer|groupetowsdeal)\.fr$',hostname)
    if matches: return True
    matches = re.search('\.(shopping-de-marque\.fr|le-meilleur-pour-vous\.fr|offres1968\.fr|le-top-des-gains\.com|renouveau-ecommerce\.com|reduction-verte\.fr|lesinfosdujours\.fr|le-meilleur-des-femmes\.com|votre-panel-conso\.fr|boost-ton-achat\.fr|auto-francaise\.com|economie-du-jour\.com|la-meilleure-offre-du-jour\.com|plans-de-oufs\.fr|focusnews\.fr|planmalin\.fr|anne-offres\.fr|newsprecieuse\.fr|lactupromo\.com|nlactupromo\.com|infofanactu\.com|nlbjrinformatie\.com|lesfrenchics\.fr|lesfrenchics\.com|maparfaitenews\.com|malettreinfo\.com|laparfaitenews\.com|nlmalettreinfo\.com|coindelinfo\.com|flashactualites\.com|leflashactu\.com|lecoindesinfos\.com|clubdesactus\.com|leclubactu\.com|bonjourinfo\.com|bjrinfo\.com|nlbonjourinfos\.com|newsmixinfos\.com|mixinfos\.com|newsvisionconso\.com|newsinfprecieuse\.com|mmeprivee\.com|madameprivee\.com|nlparfaitenews\.fr|nlrockinfo\.fr|linforock\.fr|hbwkmedia\.com|consolive\.fr|consoenlive\.fr)$',hostname)
    if matches: return True
    whywhy = "Line 496"
    matches = re.search('smarthost-[0-9]+\.bouyguestelecom\.com',hostname)
    if matches: return True
    matches = re.search('out[0-9]+spamexpert1\.hoster\.kz$',hostname)
    if matches: return True
    matches = re.search('^ip[0-9]+\.ip-[0-9\-]+\.eu$',hostname)
    if matches: return True
    matches = re.search('static-qvn-qvt-[0-9]+\.business\.bouyguestelecom\.com',hostname)
    if matches: return True
    matches = re.search('\.(cgml1\.com|gtml3\.com|purplelicous\.com|vincentvoelkel\.com|americanfoundationsgroup\.com|tddhqclk\.com|livefromdasipp\.com|sanmarcosphoto\.com|txpsv\.com|sgml2\.com|sgml1\.com|kerrvilleunited\.com|kerrvilledrafting\.com|kerrcountysurveyor\.com|daddymats\.com|aaronyates\.net|arthurschmidtcustomhomes\.net|yatesmultimedia\.com|texasprosound\.com|spacemanstu\.com|lonestarcoeds\.com|kerrvillerumors\.com|kerrvilleranchforsale\.com|kerrvilleranchesforsale\.com|kerrvillephoto\.com|kerrvillenightlife\.com|kerrvideo\.com|kerrunited\.com|kerrcountysurveyor\.net|kerrcountynews\.com|kerrcalendar\.com|ipsstuff\.com|hillcountryweddingcinema\.com|hillcountrycattlewomen\.com|hillcountrybaseball\.com|cybersecurity-training\.com|crookedtees\.com|cyber-ami\.com|celebritywishbone\.com|ajyates\.com|yates-digital\.com|tuscanyranch\.com|kerrvilleonlinegaragesale\.com|buysellkerrville\.com|votebillblackburn\.com|sustaininc\.biz|nevilleforsenate\.net|hq103\.info|hq102\.info|tddhqtracks\.info|tddhqclk\.info|tddhqclck\.info|tddhqclicks\.info|tddhqclick\.info|thedailydealhq\.info|tddhqsupport\.info|tddhqemail\.info|tddhq\.info|aaronsrpgtable\.com|wolfie713\.com|westcreektech\.com|tddhqmail\.com|tddhqemail\.com|tddhqtrk\.info|tddhqtrak\.info|tddhqtrack\.info|hq100\.info|yatesstudio\.com|firstbaptistbpk\.com|vcab\.info|wolfieonline\.com|elderlyerrands\.com|sustainlabor\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(faxparmail\.com|gelscom\.fr|plenfax\.com|campagne-emailing\.com|envoi-de-fax\.com|envoyez-sms\.com|rddiffusion\.com|communider\.com|sendingbox\.net|switmail\.com|gtnett\.com|outilemailing\.com|voice-sms\.com|plensoft\.com|les-sms\.net|mes-emailings\.com|123fichier\.com|123fichiers\.com|altomail\.net|apifax\.com|api-fax\.com|annumedia\.com|base-de-donnees-fax\.com|base-de-donnees-sms\.com|base-de-prospects\.com|axafax\.com|campagne-fax\.com|campagnes-fax\.com|cdmailing\.com|chrono-fax\.com|cheap-mail\.com|coin-presse\.com|copifax\.net|consoliste\.com|consolistes\.com|diffubox\.net|diffusion-fax\.com|diffusion-fax-mailing\.com|delrinafax\.com|delrina-fax\.com|delrinafaxpro\.com|delrina-fax-pro\.com|emailclic\.com|email-clic\.com|email-club\.com|email-club\.net|email-fax-sms\.com|emailing-asp\.com|emailing-envoi\.com|emailing-fax-sms\.com|emailing-logiciel\.com|emailing-network\.com|e-mailing-solution\.com|emails-express\.com|email-strategie\.com|envoi-fax\.net|envoyer-emailing\.com|envoyer-un-emailing\.com|envoyer-un-fax-mailing\.com|envoyerunmailing\.com|envoyer-un-mailing\.com|efax-express\.com|efax-mailing\.com|e-fax-service\.com|effibase\.com|effibases\.com|effiroutage\.com|effiroutage\.net|euro-mailing\.com|expert-fax\.com|express-sms\.com|expressemailing\.com|express-emailing\.com|express-fax\.com|express-faxing\.com|express-fichier\.com|express-fichiers\.com|express-mailing\.net|express-mailing-pro\.com|fiamail\.net|force-mail\.com|force-mail\.net|france-email\.net|france-mailing\.com|france-newsletter\.com|franceoptin\.com|france-prospection\.com|fax-2-email\.net|fax-box\.net|fax-direct\.com|fax-envoi\.net|fax-expert\.com|fax-facile\.com|fax-facile\.net|fax-france\.com|faxinternet\.net|fax-logiciel\.com|faxmailer\.net|faxmailing-france\.com|fax-mailing-logiciel\.com|winimail\.com|packsms\.com|pubfax\.com|mymailingfax\.com|clickimedia\.net|smsparinternet\.com|marketingfax\.com|posturologue-val-de-marne\.com|smsvocal\.com|emailing-web\.com|vnumail\.com|news-ism-energie\.com|expressmail\.fr|solutionsms\.fr|viamail\.fr|equifax\.fr|datalist\.fr|technomarketing\.fr|envoifax\.fr|expressmailing\.fr|faxim\.fr|email-reference\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(autoadwords\.com|businessglobe\.com|businessviewer\.com|disruptjournal\.com|gibot\.com|leadsindex\.com|statistio\.com|analyticsfeeder\.com|analyticsupdates\.com|leadsmessage\.com|londonstocknewsletter\.com|londonstockinsider\.com|analytics-ua[0-9]+\.com|businesscontact360\.com|premiumwebanalytics\.com|premiumleadsalert\.com|premiumbusinesscontact\.com|premiumbusinessanalytics\.com|webmasterwebtool\.com|webanalyticsalert\.com|googlewebleads\.com|leadssupport\.com|saleswebleads\.com|webleadsanalytics\.com|adwordsoptimize\.com|erhvervsnyt\.com|premiumadwords\.com|xn--brsenonline-ggb\.com|predictedvisitor\.com|predictedleads\.com|aimstockjournal\.com|globalstockwire\.com|globalstockinsider\.com|nasdaqinsider\.com|wallstreetstockpost\.com|usstocksjournal\.com|usstocksinsider\.com|adwordsinsigths\.com|visitinsigths\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(regie-shopandgo1\.com|regie-shopandgo2\.com|regie-shopandgo3\.com|de-bonmatin-shop\.com|etiic-formation\.com|lauracoindushopping\.com|vis-le-shopping\.com|shopping-institut-email\.com|shopping-institut-emaiing\.com|shopping-institut-astuces\.com|objetmoderne2-shopping\.com|newsco-offredujour2\.com|mojanadryzakupy-shopping\.com|jeconomise2-astuceshopping\.com|institut2-shopping\.com|ilmondodellamoda-shopping\.com|elrincondelasmarcas-shopping\.com|de-bonmatin-emailing\.com|de-bonmatin-bonplan\.com|de-bonmatin-astuces\.com|debonmatin2-shopping\.com|leshoppingonline\.com|alerteshoppingmalin\.com|regip2-shopping\.com|regie-reducetbonnesaffaires\.com|regie-comparereteconomiser\.com|regie-codespromotions\.com|regie-achetermalin\.com|panel2-shopping\.com|lesmeilleursplan2-shopping\.com|le-shopping-online2\.com|good2-shopping\.com|azorica2-shopping\.com|shopping-onlinte2-spb\.com|objermoderne2-spb\.com|newsco-offredujour\.com|jeconomise-astuceshopping\.com|ilmondodellamoda-emailing\.com|vis-leshopping\.com|vis-lesastuces\.com|vis-lapromotion\.com|regie-newsletter\.com|regie-mediadeal\.com|regie-jourdechance\.com|regie-jourdebonplan\.com|regie-economisateur\.com|regie-deals\.com|regie-bonplan\.com|emailingco-es\.com|dev-malin\.com|devenez-mal\.com|latiendadelasesquina\.com|ilmondodellamoda\.com|lusinedesmarques-emilie\.com|lesmeilleursplans-duweb\.com|chicplanete-claire\.com|lcdushop\.com|panelconso-shopping\.com|panelconso-emailing\.com|panelconso-bonplan\.com|panelconso-actu\.com|moj-madry-zakupy-emailing\.com|lecoindela-mode\.com|pierre-roi-des-bonnesaffaires\.com|marie-reine-des-bonsplans\.com|luniversdushopping\.com|lexpert-du-gain-dargent\.com|lesconseilsdujour\.com|letemps-desbonnesaffaires-duweb\.com|goodplan-day\.com|gooddealday-wm\.com|dealdujour-bonplan\.com|vis-tonshopping\.com|vis-tondeal\.com|vis-tonbonplan\.com|lesmeilleurs-plansduweb\.com|lesastuces-duweb\.com|lesastucesdu-web\.com|les-bons-plan-de-caroline\.com|lesbonplans-de-caroline\.com|lc-du-shop\.com|es-emailingco\.com|news-lusinedesmarques\.com|news-chicplanete\.com|shopping-pl-udm\.com|unjourun-caddie\.com|un-jour-un-caddie\.com|radiateur-energie\.com|vipconcours-newsletter\.com|energy-eco2\.com|energy-eco1\.com|wmrmta\.com|alerte-smalin3\.com|alerte-smalin2\.com|alerte-smalin\.com|chicoslaplanete\.com|chicos3laplanete\.com|chicos2laplanete\.com|gdd-dujour\.com|gdd3dujour\.com|vis-leplandujour\.com|vis-leconomie\.com|gooddeal-wm\.com|lejourdeseconomies\.com|alerte-shopping-malin\.com|lecoindesachats\.com|vis2-shopping\.com|unjouruncaddie\.com|vis-lapromodujour\.com|bingo2-shopping\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(lanewsdeparis\.fr|espace-offres\.fr|espace-offres\.com|lacledesmails\.fr|lanewsendelire\.fr|lecadodujour\.fr|lesdealsdujour\.fr|leparcdesdeals\.fr|thecado\.fr|laruedesoffres\.fr|mailincroyable\.fr|lamagiedesoffres\.fr|boulevarddesaffaires\.fr|promos-news\.fr|lavenuedesaffaires\.fr|lebestdeal\.fr|lecercledesaffaires\.fr|thecoin\.fr|deals-magiques\.fr|comptoir-offres\.fr|cercle-offres\.fr|espace-deals\.fr|offre-conso\.fr|deals-promos\.fr|latopdesoffres\.fr|loffreirreelle\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(e2ma.net|lr004|trend47|relai-smtp|mta1|mta2|mailgun|stonyslope|newobserve|hop-digital|b2b-mail|mailobj|dplrtrack|mizbanfa)\.net$', hostname)
    if matches: return True
    matches = re.search('\.(phonereflex\.com|betreflex\.info|alertefiscale\.com|alerte-fiscale\.com|alerteimpots\.com|newsletter-reflexpatrmoine\.fr|cosse-defiscalisation\.com|reflexepatrimoine\.fr|wr02\.net|wr02\.fr|mediainvest\.pro|magazineinvest\.pro|mediaimmos\.net|magazineinvest\.net|magazineimmos\.net|mediaimmos\.com|magazineinvest\.com|magazineimmos\.com|immobilierdefrance\.biz|vitrineimmo\.net|globaldefisc\.net|globaldefisc\.com|wremtu\.net|wremtu\.com|zeroimpots\.net|zeroimpots\.biz|zeroimpots\.info|lexpert-immo\.xyz|investir-immo\.pro|lexpert-immo\.net|wp02\.net|infos-habitat\.xyz|infos-habitat\.pro|infos-habitat\.com|investpinel\.xyz|investpinel\.pro|wm01\.xyz|wm02\.xyz|francepromoteurs\.com|francepromoteur\.com|defiscalisez\.xyz|toutsurlinvestissement\.net|wr01\.(net|eu)|infos-capitalya\.net|infos-capitalya\.com|info-capitalya\.net|info-capitalya\.com|elegantiagroup\.fr|myterre1\.com|myter1\.com|info-loi-mezard\.net|informations-loi-mezard\.com|guide-loi-mezrad\.com|guide-loi-mezard\.com|guideloimezard\.com|madouchesurmesure\.com|dispositiflemaire\.fr|dispositiflemaire\.com|lameilleurechaudiere\.fr|laemeilleureenergie\.fr|lameilleurechaudiere\.com|laemeilleureenergie\.com|institut-francais-de-defiscalisation\.fr|institutfrancaisdedefiscalisation\.fr|institut-francais-de-defiscalisation\.com|institutfrancaisdedefiscalisation\.com|mareducfisacle\.com|reflexepatrimoineinfos\.com|reflexepatrimoineinfo\.com|reflexemails\.com|monmaintienadomicile\.fr|monmaintienadomicile\.com|syndicetgestionpascher\.fr|syndic-gestion-pascher\.fr|syndic-gestion-pascher\.com|cosseancien\.fr|laloicosseancien\.fr|cosseancien\.com|laloicosseancien\.com|reductionfiscale\.fr|reductionfiscale\.com|newsreflexepatrimoine\.com|informationpatrimoine\.com|emails-reflexepatrimoine\.fr|emails-reflexepatrimoine\.net|info-reflexpatrimoine\.net|info-reflexepatrimoine\.fr|info-reflexepatrimoine\.net|info-reflexepatrimoine\.com|email-reflexeimpots\.fr|email-refleximpots\.fr|email-reflexpatrimoine\.fr|email-reflexepatrimoine\.fr|email-reflexpatrimoine\.net|email-reflexepatrimoine\.net|email-reflexpatrimoine\.com|email-refleximpots\.com|email-reflexhabitat\.com|email-reflexepatrimoine\.com|email-reflexeimpots\.com|alerte-fiscale\.info|reflexpatrimoine\.net|reflexepatrimoine\.net|reflexpatrimoine\.info|reflexepatrimoine\.info|laloi-cosse\.fr|newsletter-reflexepatrimoine\.fr|reflexecredit\.com|exclusiveimmo\.net|laventeimmobilier\.net|immovente\.net|immovente\.fr|newsletter-reflexepatrimoine\.net|newsletter-reflexepatrimoine\.com|investirloipinel\.com|reflexcredit\.com|reflexeassurance\.com|reflexhabitat\.com|reflexpatrimoine\.fr|syndicetgestionpascher\.com|callreflexe\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(faxparmail\.com|gelscom\.fr|plenfax\.com|campagne-emailing\.com|envoi-de-fax\.com|envoyez-sms\.com|rddiffusion\.com|communider\.com|sendingbox\.net|switmail\.com|gtnett\.com|outilemailing\.com|voice-sms\.com|plensoft\.com|les-sms\.net|mes-emailings\.com|123fichier\.com|123fichiers\.com|altomail\.net|apifax\.com|api-fax\.com|annumedia\.com|base-de-donnees-fax\.com|base-de-donnees-sms\.com|base-de-prospects\.com|axafax\.com|campagne-fax\.com|campagnes-fax\.com|cdmailing\.com|chrono-fax\.com|cheap-mail\.com|coin-presse\.com|copifax\.net|consoliste\.com|consolistes\.com|diffubox\.net|diffusion-fax\.com|diffusion-fax-mailing\.com|delrinafax\.com|delrina-fax\.com|delrinafaxpro\.com|delrina-fax-pro\.com|emailclic\.com|email-clic\.com|email-club\.com|email-club\.net|email-fax-sms\.com|emailing-asp\.com|emailing-envoi\.com|emailing-fax-sms\.com|emailing-logiciel\.com|emailing-network\.com|e-mailing-solution\.com|emails-express\.com|email-strategie\.com|envoi-fax\.net|envoyer-emailing\.com|envoyer-un-emailing\.com|envoyer-un-fax-mailing\.com|envoyerunmailing\.com|envoyer-un-mailing\.com|efax-express\.com|efax-mailing\.com|e-fax-service\.com|effibase\.com|effibases\.com|effiroutage\.com|effiroutage\.net|euro-mailing\.com|expert-fax\.com|express-sms\.com|expressemailing\.com|express-emailing\.com|express-fax\.com|express-faxing\.com|express-fichier\.com|express-fichiers\.com|express-mailing\.net|express-mailing-pro\.com|fiamail\.net|force-mail\.com|force-mail\.net|france-email\.net|france-mailing\.com|france-newsletter\.com|franceoptin\.com|france-prospection\.com|fax-2-email\.net|fax-box\.net|fax-direct\.com|fax-envoi\.net|fax-expert\.com|fax-facile\.com|fax-facile\.net|fax-france\.com|faxinternet\.net|fax-logiciel\.com|faxmailer\.net|faxmailing-france\.com|fax-mailing-logiciel\.com|winimail\.com|packsms\.com|pubfax\.com|mymailingfax\.com|clickimedia\.net|smsparinternet\.com|marketingfax\.com|posturologue-val-de-marne\.com|smsvocal\.com|emailing-web\.com|vnumail\.com|news-ism-energie\.com|expressmail\.fr|solutionsms\.fr|viamail\.fr|equifax\.fr|datalist\.fr|technomarketing\.fr|envoifax\.fr|expressmailing\.fr|faxim\.fr|email-reference\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(icpbounce\.com|icptrack\.com|wwwettend\.com|treesend\.com|theofficialemailblog\.com|officialemailblog\.com|mailpuma\.com|icptrack\.net|icpsc-cdn\.com|icontactsilver\.com|icontactplus\.com|icontactplatinum\.com|icontactgold\.com|iconnect\.com|intellicontactpro\.com|intellicontact\.com|howtomanageanevent\.com|ic4sf\.com|ic4salesforce\.com|ettendz\.com|ettends\.net|ettends\.com|ettendee\.com|ettended\.com|ettend\.net|etendz\.com|emailmarketingstandards\.net|emailmarketingstandards\.com|icontact-archive\.com|icontactmail09\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(icontactmail08\.com|icontactmail07\.com|icontactmail06\.com|icontactmail05\.com|icontactmail04\.com|icontactmail03\.com|icontactmail02\.com|icontactmail01\.com|44roi\.com|icprobounce\.com|icontactmail9\.com|icontactmail8\.com|icontactmail12\.com|icontactmail11\.com|icontactmail10\.com|icontactimg\.com|icontact\.com|icontact-free-edition\.net|icontactfreeedition\.net|icontact-free-edition\.com|icontactfreeedition\.com|icontactfree\.net|icontactfree\.com|freeicontact\.net|freeicontact\.com|icpsc\.com|yomayo\.de|ettnd\.com|emailmarketingreviewsite\.com|attendz\.net|icontactarchive\.com|ettned\.com|broadwick\.com|ettend\.com|icontactltd\.com|smallbusinessgrowthkit\.com|onlineeventsoftware\.com|onlineeventpromotion\.com|atends\.com|intellicampaign\.com|onlineeventmanagementsoftware\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(optimiza-recurso|infusionmail|net-results|emails-makemytrip|vx-email|getresponse-mail|susanmartone|coachingempresariales)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(bfi0\.com|solutionset\.com|interactconnect\.us|databasemarketing\.us|epsilon\.us|customervalue\.us|webanalytics\.us|bigfootinteractive\.com|bigfootforlife\.com|bigfootdirectory\.com|epsilon\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(pacte-isolation\.fr|napdidong\.com|pubfacebook\.fr|facebooklocal\.fr|sunitaartsvr\.com|vos-energie\.fr|leads-premium\.fr|simulation-energie-solaire\.com|kannadausefulinformation\.com|theportableapps\.com|phoenixgraphicx\.com|ameco-electricite\.com|allforspin\.com|intervention-urgente\.com|safwans\.com|infos-energie\.com|pub-google\.fr|mechguruji\.com|stop-arnaque-serrurier\.com|envoyer-mailing\.com|mycoolfactory\.com|prosiddigi\.com|localbusiness\.fr|driversdownloadforum\.com|compagnon-de-france-serrurerie\.com|serrurerie-lyonnaise\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(tra12\.com|yank-bird\.com|02m26\.com|amdb-news\.com|herakles80\.com|papillon75\.com|rouge31\.com|regard45\.com|super-reduction14\.com|super-mail75\.com|super-affaires95\.com|vert49\.com|top-de-la-joie\.com|voyage-remise92\.com|capmail\.fr|reactif-email\.com|bon32\.com|beaute90\.com|cadeau95\.com|equipe37\.com|lumiere32\.com|passion75\.com|topgain75\.com|topcommerce32\.com|bou22\.com|ble95\.com|arg41\.com|esx89\.com|habile16\.com|jos49\.com|lit49\.com|ole92\.com|ohla32\.com|pur39\.com|plo85\.com|plais93\.com|vil19\.com|classe17\.com|billet31\.com|bilan120\.com|bien40\.com|crac13\.com|cor75\.com|gratuit49\.com|gain97\.com|marche13\.com|lia100\.com|prixbas17\.com|oups24\.com|rigolo17\.com|troc90\.com|bleue22\.com|joie24\.com|hum45\.com|gri26\.com|lune22\.com|lio97\.com|roi39\.com|sysmail\.fr|bling38\.com|anis21\.com|elle12\.com|jaime47\.com|jadore28\.com|lune17\.com|lui40\.com|rire16\.com|bri47\.com|direct-offres\.com|direct-economie\.com|gru13\.com|gros-gains\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(jess12\.com|loulou38\.com|perform-affaires\.com|bari41\.com|lion29\.com|tri26\.com|exp95\.com|investir-au-mieux\.com|meilleur-des-sens\.com|nouvelles-offres\.com|oui45\.com|rui12\.com|le-meilleur-parti\.com|le-meilleur-de-vos-gains\.com|la-meilleure-cagnotte\.com|ouvrez-vos-yeux\.com|ouvrez-vos-gains\.com|votre-meilleur-partenaire\.com|votre-meilleure-economie\.com|votre-finance\.com|votre-cresus\.com|plus-de-cagnotte\.com|ameliorer-votre-carriere\.com|gain-mail\.com|forte-economie\.com|le-meilleur-du-mail\.com|sensationnelle-opportunite\.com|sensationnel-gain\.com|starmail\.fr|plus-go\.com|minhup\.com|minhbird\.com|anansky\.com|ananroot\.com|ananfly\.com|ananboard\.com|starcamp\.fr|offreunique\.fr|mailvip\.fr|jeminforme\.fr|boutique-en-or\.com|ng-nc\.com|cdt7\.com|mauricethedog\.com|anancut\.com|anansoftt\.com|dim03\.com|pgr9\.com|images875\.com|caligula\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(omktmail\.com|outmarketqa\.com|omktmail6\.com|omktmail4\.com|omktmail2\.com|omktemail\.com|outmarketco\.com|envoc\.us|vocusqarelcampgn2\.com|vocusqarelcampgn1\.com|vocusqaintcampgn2\.com|vocusqaintcampgn1\.com|vocusqaemail4\.com|vocusqaemail3\.com|vocusqaemail2\.com|vocusqaemail1\.com|vocusqaemail\.com|vocusmarketing\.com|vocusemail7\.com|vocusemail6\.com|vocusemail5\.com|vocusemail4\.com|vocusemail3\.com|vocusemail2\.com|vocusemail1\.com|vocusemail\.com|vocus-bounce\.net|invoc\.us|campgn8\.com|campgn7\.com|campgn5\.com|campgn4\.com|campgn3\.com|campgn2\.com|campgn11\.com|campgn10\.com|campgn1\.com|omqamail\.com|omktmail1\.com|omktmail5\.com|omktmail3\.com|outmarket\.us|onvoc\.us|vocus-bounce\.com|outmarket\.info|buyingsignals\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(mmsend55\.com|mmsend54\.com|mmsend53\.com|mmsend52\.com|mmsend51\.com|mmsend50\.com|mmsend5\.com|mmsend49\.com|mmsend48\.com|mmsend47\.com|mmsend46\.com|mmsend45\.com|mmsend44\.com|mmsend43\.com|mmsend42\.com|mmsend41\.com|mmsend4\.com|mmsend39\.com|mmsend38\.com|mmsend37\.com|mmsend36\.com|mmsend35\.com|mmsend34\.com|mmsend33\.com|mmsend32\.com|mmsend31\.com|mmsend30\.com|mmsend3\.com|mmsend29\.com|mmsend28\.com|mmsend27\.com|mmsend26\.com|mmsend24\.com|mmsend23\.com|mmsend101\.com|mmsend100\.com|sdiemailsdirect\.com|xpressmagnet\.com|njg-media\.com|mmsend101\.com|mmsend100\.com|maforhealthcare\.com|realmagnethelp\.com|realmagnethelp\.net|eandponline\.com|realmagnetusergroup\.com|cwhoswho\.com|realmagnetusergroup\.net|douglaspubs\.com|realmagnetusergroup\.info|testmyemail\.com|streetauthorityfinancial\.com|eimng\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(sears\.ca|puntopy\.com|gtdinternet\.com|jpksummits\.com|yrtech\.|independentamericanparty\.org|palcomweb\.net)$',hostname)
    if matches: return True
    matches = re.search('\.(mmsend85\.com|realmagnetimages\.net|quirp-5\.com|quirp-4\.com|quirp-3\.com|quirp-2\.com|quirp-1\.com|mapaincalculator\.com|b2i-email\.net|mchemails\.com|quirpidev\.com|quirpi\.net|mmsend105\.com|mmsend104\.com|mmsend103\.com|mmsend102\.com|rm-unsubscribe\.com|quirpi\.com|realmagnetsender\.com|maforretail\.com|maforpublishers\.com|maforlegal\.com|maforlaw\.com|maforhighered\.com|maforfinancialservices\.com|maforfinance\.com|maforeducation\.com|maforassociations\.com|maforagencies\.com|mmsend91\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(mmsend90\.com|mmsend9\.com|mmsend89\.com|mmsend88\.com|mmsend87\.com|mmsend84\.com|mmsend82\.com|mmsend81\.com|mmsend80\.com|mmsend8\.com|mmsend79\.com|mmsend78\.com|mmsend77\.com|mmsend76\.com|mmsend75\.com|mmsend74\.com|mmsend73\.com|mmsend72\.com|mmsend71\.com|mmsend70\.com|mmsend7\.com|mmsend69\.com|mmsend68\.com|mmsend67\.com|mmsend66\.com|mmsend65\.com|mmsend64\.com|mmsend63\.com|mmsend62\.com|mmsend61\.com|mmsend60\.com|mmsend6\.com|mmsend59\.com|mmsend58\.com|mmsend57\.com|mmsend56\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(tripolis\.info|tripolis\.com|tripolis\.it|smtp-p\.com|trd50\.com|realcampaign\.info|tridle\.info|layerbox\.info|dialogueplus\.info|tribalised\.info|dragdropmarketing\.com|draganddropmarketing\.com|utopay\.com|veritate\.net|vedm\.net|trustecard\.com|ticketcalls\.com|tribemagic\.com|tribalwork\.net|trd46\.com|trd45\.com|tribalorange\.com|tribalnoize\.com|tribalised\.net|tribalised\.com|tripolicious\.com|tarocana\.com|realcampaign\.net|pyxilon\.com|picsylon\.com|pixylon\.net|pixylon\.com|layerbox\.net|layerbox\.com|kinder\.net|jelevenlanggratisinternet\.com|jllgi\.com|inteldialogue\.net|inteldialogue\.com|dlgpls\.net|dlgpls\.com|dialoguemail\.com|dialoguedesk\.com|dialoguecompany\.net|dialoguecompany\.com|dialogcompany\.net|dialogcompany\.com|debitycard\.com|debity\.com|campnomads\.com|buzztribal\.com|aicampaign\.com|247realtime\.com|abcpress\.com|abcampaign\.com|accountlive\.net|accountlive\.com|dialogueplus\.com|tripoliscloud\.com|kortingscasino\.com|dialoguework\.com|dialoguesoftware\.com|tridle\.com|lammm\.com|dialoguesolutions\.net)$',hostname)
    if matches: return True
    matches = re.search('\.(monjournalideal\.com|ofertas-business\.com|experimente\.club|offertainitalia\.it|mavoyancedujour\.fr|marketing-generation\.com|bestof-deal\.com|journaldudeal\.com|melleprivilege\.com|lesprivileges\.com|lesplusbellesmarques\.fr|new-shoppingonline\.com|mapetitereduc\.fr|bingo-facile\.com|les-coins-de-marques\.com|vrai-tarot\.com|vision-espace\.com|top-plans-voyages\.fr|votre-vie-saine\.fr|monpetitciel\.fr|lafoireauxaffaires\.com|votre-detox\.fr|astucesbeauty\.com|lesptitsplans\.com|btob-autobiz\.com|my\.autobiz\.fr|tonerflash\.fr|tonerflash\.eu|ruedubonplan\.fr|sociabiliweb2\.com|topaffairesduweb\.com|monmaildujour\.com|infosquotidiennes\.com|planqdirect\.com|infoplanaffaires\.com|infobonplan\.com|offredumoment\.com|la-bonneoffre\.com|com-du-jour\.com|monbeausecret\.fr|digitalvibes\.fr|thegood-choice\.fr|petitesatuces\.fr|labijouteriederita\.fr|safmails\.com|offres-pour-toi\.com|web-offers\.com|reducsweekend\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(acheterlouer-immo\.fr|newsletter1-autobiz\.com|newsletter2-autobiz\.com|campaigns-autobiz\.com|emailing-autobiz\.com|service-autobiz\.com|yourofferstoday\.com|affairesetcompagnie\.fr|financebuzz\.com|monsieur-max\.com|ruedeshommes\.com|mon-shop-prive\.com|thedealsforyou\.com|yourshopaffair\.co\.uk|votresuperette\.com|gamado\.fr|flibor\.fr|kerolud\.fr|mangacom\.eu|lefoyouseu\.eu|learnybox\.com|agram\.fr|newsletterofferonline\.com|picodi\.com|leboninvestissement6\.com|email\.freeserve\.com\.au|dailyoffersforyou\.com|epostwizz\.com|exp95\.com|topoffresgaming\.com|savethedeals\.com|topventes-privees-du-jour\.com|wi-su\.fr|100-newsletter\.com|dealprime\.fr|maconfidence\.com|mestrucsetastuces\.com|mesachatspremium\.com|club-web\.com|leswebivore\.fr|letter\.fashionmia\.net|prizesdelivery\.com|maison-des-marques\.com|sucredorge\.com|salagh\.com|maneliv\.com|joicetip\.com|theentertertainmenthub\.com|wisu\.fr|cosmetrol\.com|topswingo\.com|letter\.berrylook\.com|openfinancial\.co|lespetitsmarches\.fr|leboninvestissement7\.com|supermalin\.com|economax\.com\.br|monchoixdujour\.com|lagendadesventesprivees\.com|es\.myonlineshoppingbag\.com|coquelux\.com\.br|chicoutlet\.com\.br|msg\.earlymoments\.com|iharbormarkdowns\.com|sandviks\.com|topdunet\.info)$',hostname)
    if matches: return True
    matches = re.search('\.(proentreprise\.fr|supramarketing\.fr|swapchanges\.info|netroad\.fr|netfile\.fr|filepro\.fr|businessmark\.fr|contactserver\.fr|mailfast\.fr|securitysafe\.fr|datalog\.fr|youraccount\.fr|managementdata\.fr|prowe\.fr|interdomain\.fr|companyindustry\.fr|webdatas\.fr|proproject\.fr|webclient\.fr|propafirm\.info|gigasat\.info|propaweb\.info|cabinetmap\.com|hm-agency\.com|cyclecenter\.fr|communicationadserver\.fr|strategyadvantage\.fr|highpart\.fr|tradesofts\.fr|ultratool\.fr|marketingsoft\.fr|safemarketing\.fr|serverpro\.fr|managementsoft\.info|spacedata\.info|securiweb\.info)$',hostname)
    if matches: return True
    matches = re.search('\.(decoration-shopping\.fr|compaignemail89\.net|compaignemail90\.net|compaignemail91\.net|bagiinfo\.net|bonabrestaurant\.com|annuairemalimusow\.com|hondawiyung\.com|hondaimsi\.com|oparaisodoacai\.com|kevinbrandsuarez\.com|lawacreative\.com|lzrmakeup\.com|smart-putovanja\.com|avengers-media\.com|bestplaytv\.net|belbin\.it|belbin\.in|zeynabaneh\.com|smartmediahk\.com|gubahcipta\.com|straitswerkscafe\.com|pvpsuriname\.com|jenniferwongswiesan\.com|smartmda\.com|kindercampuscoyoacan\.com|awaken2rc\.com|smartonlinemagazine\.us|gandp-law\.com|chismanshelbyelectric\.com|estilismo\.info|smartmediaserver\.info|pecivo\.info|tuviajealmundialbrasil\.com|davirjie\.com|shanesmart\.com|smartmedialinks\.com|smlfacebookmarketing\.com|weboptimizada\.com|grywyscigowe\.biz|conversionesonline\.com|adamcruse\.com|grykucykipony\.com|grydiamenty\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(nextemailserv2\.com|pyramidedelamusique\.com|yuzu-london\.com|elva-listverify\.com|dalilaghazel\.com|gryharrypotter\.com|atlantic-pedia\.com|michelventura\.com|blueberryfactory\.net|eligebelleza\.com|grysamochody\.com|traveleuropetips\.com|kst-iran\.com|popuphk\.net|gryhellokitty\.com|inasoft\.net|grystrategiczne\.com|compositegostar\.com|gryben10\.com|waitandsee\.net|search4ashop\.com|kerriandjonathan\.com|heyanobooking\.com|solutelit\.com|nextemailserv1\.com|theoreolcorporation\.com|emailscheck\.com|emaillistverify\.net|nextemailserv3\.com|yuzulondon\.com|pointbeaute\.net|que-des-bons-plans\.com|ouiaumariage\.com|smartmediaresearch\.com|theportedor\.com|smartmedia-eg\.com|lpaudiovisuel\.info|zuwainatour\.com|wisatamaroko\.com|apasc\.info|flvplayerdownload\.us|palmappartclub\.com|thesmart-media\.com|lamalif-lighting\.com|noithatcnm\.com|octotracking\.com|transportlogistiek\.com|firstpresbyterian\.info|worldhalalconference\.com|tamankolamminimalis\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(gryzrecznosciowe\.com|smartmediaaward\.com|eghlimsaffron\.com|focuswonen\.com|metroauto-indo\.com|pandp-law\.com|chronisch-ziek\.com|prettigedagen\.com|allesvoordevrouw\.com|allesoveruwkind\.com|allesoveruwhuis\.com|onbeperktleven\.com|gezond-bedrijf\.com|adamandjennifer\.us|zreapexpower\.com|worldhalalweekforum\.com|workflowspecialists\.com|workflowspecialist\.com|wopcyouth\.com|torontodogwalking\.com|smartmediaweddings\.com|smartmediaservices\.net|smartmedia-la\.com|slimstudios\.net|parrotcare\.com|jumachannel\.com|igaware\.com|extranetspecialists\.com|extranetguru\.com|extranetexperts\.com|dat-box\.com|guiaviajes\.net|smartmediaplanning\.com|viptrace\.net|boysbiggestconversation\.com|loanbang\.kr|xn--hittaboln-d3a\.net|fakturna\.com|dittbolan\.com|dorayainfo\.com|findyourshea\.com|alyamama-ksar-el-kebir\.com|kairosmutuelle\.com|wisatamaroko\.net|harnoksp\.com|groupehorizontelerp\.com|yahyasayedomar\.com|baraayahya\.com|syriaculturaldays\.net|azizqebibo\.com|rakaraka\.net|syriadev\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(marketingonlineu\.com|imgweb\.net|pagarcanopyrumah\.com|hymnsofdevotion\.com|autodeetailz\.com|jonathancruse\.com|grywyscigowe\.net|smartmediastudio\.com|oneeleven\.info|irfruit\.com|jig5aw\.com|gryspongebob\.com|aismeth\.com|hetmenselijklichaam\.com|trurich\.info|kasehdiaventures\.com|grymylittlepony\.com|lamalifdiffusion\.com|chohra\.com|djgonflablerabat\.com|homastore\.com|smartmediapress\.info|medialiveplayer\.com|giochimylittlepony\.com|marocbenevolat\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(unerencontreunflirt\.com|lavenuedesopportunites\.com|laplaceauxbonnesaffaires\.fr|mesenviesbeaute\.fr|galaxiedessoldes\.fr|lesastucesenfolie\.fr|trackvm\.net|mijnshoppingadviezen\.com|mijnshoppingadvies\.com|mijnshopingadvies\.com|mondoprivilegi\.com|mailboutique2\.com|online-erbjudanden\.com|newsdelamoda\.com|sorpresaspara-todos\.com|sorpresasparatodos\.com|uwshoppinggids\.com|tusrebajaspremium\.com|vertigoaroundtheworld\.com|aankopvandedag\.com|godsavethedeal\.com|godsaveourdeal\.com|godsavemydeal\.com|vmwwtracking\.com|vrtagency\.net|vertigotracktrackworld\.com|estrakingvmes\.com|svertigomes\.com|vertigotrackww\.com|demagencyvma\.com|vmaitaly\.net|vmaitaly\.com|vertigotrackit\.net|nyetilbudidag\.com|trankingspainvmes\.com|uusiakampanjoitaatanaan\.com|bestchoicesday\.com|choicesofdtoday\.com|nyetilbudida\.com|nyetilbuddag\.com|trendshoptoday\.com|trendingshoppingtoday\.com|toptilbudfordig\.com|toptilbudfor\.com|toptilbuddig\.com|vertigotrackit\.com|uusiakampanjoitatanaan\.com|uusiakampanjoitaaa\.com|daspping\.com|uniikittarjouk-set\.com|aankoopvandedag\.com|uniikit-tarjouk-set\.com|vmptrack\.com|affiliationes-vmes\.com|estracking-vmes\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(yourdaily-surprise\.com|el-chollo-fresco\.com|deter-dagendin\.com|det-er-dagendin\.com|deterdagendin\.com|matesratestoday\.com|dandy-shopping-today\.com|dandy-shoppingtoday\.com|dandyshoppingtoday\.com|todo-para-ella\.com|todo-paraella\.com|finestdealstoday\.com|vmaffiliation\.com|lavenue-desreves\.com|tu-chollo-alerta\.com|tu-cholloalerta\.com|tucholloalerta\.com|your-daily-surprise\.com|your-dailysurprise\.com|beste-kampanjer\.com|bestekampanjer\.com|kampanjer-nya\.com|nya-kampanjer\.com|nyakampanjer\.com|tilbud-seneste\.com|uniikit-tarjoukset\.com|uniikittarjoukset\.com|nye-nyheder\.com|vrtrackdem\.net|vrtrackdem\.com|trackingit-vmp\.com|trackinges-vmp\.com|vertigomediaperformanceprivacypolicy\.com|caprichos-en-laweb\.com|caprichos-enlaweb\.com|caprichosenlaweb\.com|chauffage-aterno-vmp\.com|allt-for-dig\.com|allt-fordig\.com|la-ventaja-deldia\.com|la-ventajadeldia\.com|laventajadeldia\.com|paseo-de-las-ventajas\.com|paseo-de-lasventajas\.com|paseodelasventajas\.com|lesnouveautesduweb\.com|monexclusiviteduweb\.com|mesdealsdunet\.com|dagenstipsning\.com|mailtorget\.com|online-tilbyder\.com|rabat-tilbyder\.com|sophiesuggests\.com|deal-selection\.com|cd-reactivation2\.com|cd-reactivation1\.com|mavoyanceofferte\.com|newsdemoda\.com|rebajaspremium\.com|smart-avenue\.com|tribaloffers\.com|shop-inst-1\.com|vosoffrespremium\.com|annieadvises\.com|discountsdistrict\.com|suggestionshack\.com|thebargainparlour\.com|vickivalues\.com|monshoppingrelax\.com|monparadisdushopping\.com|mestopspromos\.com|mespromosdujours\.com|mesopportunitesduweb\.com|vertigodata\.com|yourdailysavings\.com|event-torget\.com|unhommeunjour\.com|korting-club\.com|karuselli-tarjous\.com|lhoroscopedelajournee\.com|larencontredujour\.com|monsigneduzodiaque\.com|unflirtdunjour\.com|unereductionunjour\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(acemlni|acemlnh|acemlng|acemlnf|acemlne|acemlnd|acemlnc|acemlnb|acemlna|acsd9|acsd8|acsd7|acsd6|acsd5|acsd13|acsd12|acsd11|acsd10|lexml5|lexml4|lexml3|lexml2|s290lnk|lnk140|img-us5|img-us3|img-us2|img-us1|emlnk5|emlnk4|emlnk3|emlnk2|emlnk1|emlnk|s8lnk|s280lnk|s260lnk|s130lnk|emsend8lnk|emsend7lnk|emsend6lnk|emsend5lnk|emsend4lnk|emsend3lnk|emsend1lnk|astirxlnk|imgus5|imgus4|imgus3|imgus2|imgus1|mailersys|img-us4|astirx|acems2|active-campaign|imgus14|imgus13|acemsa1|acemsd1|acemsc5|acemsc4|acemsc3|acemsc2|acemsc1|acemsb4|acemsb3|acemsb2|acemsb1|acemsa5|acemd6|acemd5|acemd4|acemd1|acemsd5|acemsd4|acemsd3|acemsd2)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(12all|acoptinc|acoptind|acoptine|acoptinf|acopting|acoptinh|acoptini|acemsa2|acemsa3|acemsa4|acemdns|acemsrvk|acemsrvj|acemsrvi|acemsrvh|acemsrvg|acemsrvf|acemsrve|acemsrvd|acemsrvc|acemsrvb|acemsrva|acemsrv|acemsend|acemwi|activecampaignsd2|acoptinj|acoptinb|acoptina|acdlsr|acdlsq|acdlsp|acdlso|acdlsn|imgus9|imgus8|imgus7|imgus6|imgus10|acemlnj)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(net-streams\.com|mencop\.fr|mencop\.com|cyberdome\.fr|documetrie\.com|techintgroup\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(oekla|artaujourdhui|vml-pdf|ceoofrance|shockitect|papillon75|soundcheckworld|veml02|crdgl8|envoi-email|ia-think-tank|benchmark-fr|think-tank1|bluepepite|aprem-01)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(oekla|plezi-marketing|clickdimensions|moneycontrolmarket|prototypingbusiness|expression-actu-pro-immobilier|mailengine1|lanewsdesaffaires)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(bilan27|trend47|lan2net|lesuperdeal|automobileplus45|jaime97|jamaisperdant|monideeneuve|dieti-natura)\.fr$',hostname)
    if matches: return True
    matches = re.search('\.(pack2inbox|pay2baar|kostmsg|inboxwallah|fligth4inbox|door2credit|door2promo|dost4inbox|reliancetail|relretail|rommg|gropmg|samacharjgat|ns8tld6|ns3tld6|mrptourandtravel|createchsgroup|hrconsultingindia|criptno|zeppgrelrl|gorblive|demleval|jobparks)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(jrcc-mail|jrcc-match|jrcc-cast|jrcc-blast|jrcc-task|jrcc-splash|dmdelivery|premium-arg|online-consoclient|jour-des-fous|actuel-probleme|peur-du-lendemain|home-des-tendances|suivi-de-lettre|nuits-de-la-folie|jour-de-fou|offres-du-soir|complication-du-jour|chic-esprits|delivr-france|france-premium-fr|premium-de-france|lettres-du-jour|folies-du-soir|folies-du-jour)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(emstechnology2|ems7|emv1|emv2|emv3|emv4|emv5|emv8|emv9|apsispro|email-newsletter|sispa)\.net$', hostname)
    if matches: return True
    matches = re.search('\.(ibys\.com\.br|ebbcm\.com|musvc\.com|musvc\.net|mailupgroup\.com|mailupclient\.com|laghetto\.biz|5ee\.me|mailup\.in|mailupcommerce\.com|mailup\.info|5ee\.us|mailup\.us|spedizionesms\.com|sistemanewsletter\.com|modellinewsletter\.com|mlp-list-manager\.com|mailup\.com|mailup\.biz|invioemail\.com|gestirenewsletter\.com|emailsp\.com|clientmailup\.com|bestdeliverability\.net|emailsp\.net|webteppiche\.com|mailup\.fr|allmsgs\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(email-success\.net|mnmailer\.com|ya113\.com|banzaidirect\.it|banzaimailer\.it|futurmailer\.it|futuresender\.it|emailsuccess\.in|antitsender\.com|futurmailerde\.com|educortex\.net|hiffimailer\.com|mag-news\.us|emailsuccess\.us|diennea\.net|magnews\.it|dhlshipwin\.com|docetproject\.com|dhlconnection\.com|mnt04\.com|mnstage01\.com|mnsms01\.com|mns05\.com|mno14\.com|mno12\.com|dvcasender\.com|email-magnews\.com|emailmagnews\.com|magnews-email\.com|magnewsemail\.com|mailermn\.com|mailmnta\.com|mailmnsa\.com|herd-db\.com|herddb\.com|mno11\.com|mno10\.com|mno09\.com|blazingcache\.com|seobmesmailer\.com|mnt02\.com|mnt01\.com|mns02\.com|mno07\.com|mno06\.com|mnd02\.com|mno92\.com|mno05\.com|mn293\.com|mn203\.com|mn193\.com|mn103\.com|emailsuccess\.cn|czmsender\.com|prsgasender\.com|prsgamailer\.com|mndsender\.com|tvisender\.com|inasender\.com|bismsender\.com|bismmailer\.com|majordodo\.com|subitomailer\.com|sbmmailer\.com|coigsender\.com|indimailer\.com|antbrsender\.com|yrmailer\.com|ventiquattromedia\.com|senderway\.com|waymailer\.com|strongmta\.com|mailmta\.com|mnwelcome\.com|mntarget\.com|mnsendway\.com|mnsend\.com|mnpostal\.com|futurmailer\.com|banzaimailer\.com|atvnsender\.com|mncertified\.com|ammsender\.com|setupemailsuccess\.com|checkemailsuccess\.com|banzaidirect\.com|mnemail\.com|marketingsafari\.net|mno13\.com|yamailer\.com|testmysuccess\.com|shadowsender\.com|optmta\.com|mnsaas\.com|mndelivery\.com|mxmta\.com|mercuryasender\.com|mercuryamailer\.com|ignitionsender\.com|ignitionmailer\.com|cpmktgsender\.com|dmlsmailer\.com|abamktgsender\.com|wpsender\.com|fpsupmailer\.com|wobieurope\.com|opsender\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(lookiday.com|smile1018.com|actutravel.com|kidi0803.com|onldr0818.com|weloveourpublishers.com|mpjdd1117.com|dr28c.com|dbty5469.com|md1017.com|bestastuces.com|lookmonshopping.com|morning-shopping.com|superkaly.com|look-i-ya.com|look09.com|lanews-dusourire.com|cds0617.com|lanewsdusourire.com|fil-notif.com|mesalertesmode.com|mom-tracking.com|amazingnewsletterbymorse.com|morse4thewin.com|bfd28.com|4lenpri2.com|mesactuces.com|kidylux.com|kdx402.com|beofd45.com|jdd45.com|monjournalideal.com|mp0617.com|kalystaa.com|kalistaa.com|k284ta.com)$',hostname)
    if matches: return True
    matches = re.search('\.(chasseur-de-sourire.com|c520ko.com|crakoo.com|crakeo.com|signorinaconsigli.com|unmundopiubello.com|mademoiselle-privilege.com|conseil-malin.com|conseil-dujour.com|ifodl.com|coinprivilege.com|fhlop52.com|mpvlge.com|manewsbio.com|astucesbeauty.com|misterinfos.com|mom01.com|mellereduc.com|mellebestof.com|mademoisellepromo.com|mademoiselleprivilege.com|look-promo.com|letopdesreduc.com|mydailyreduc.com|info-weekly.com|moninfos.com|lookiyo.com|malinday.com|so-reduc.com|bestof-deal.com|clicletters.com|daily-reduc.com|journaldudeal.com|infos-deal.com|melleprivilege.com|mellenewdeal.com|ohmygoodeal.com|mycityplans.com|topireduc.com|top-addict.com|topa-vantage.com|mlledeal.com|mademoiselle-deal.com|avantage-top.com|mademoiselledeal.com|webonews.com|ohmgd.com|goodmorningshopping.com|topbonplans.com|top-avantage.com|panelavenue.com|missprivilege.com|label-idee.com|conseil-beaute.com|kidilux.com|lookiya.com|top-bonsplans.com|yadubon.com|gms01.com|malettredinfo.com|dybty.com|kdlx01.com|communautebeaute-by-dpr.com|cds01.com|bofdl.com|box-alert.com)$',hostname)
    if matches: return True
    matches = re.search('\.(anpasia|anpdm|apsisforms|email-marketing-evolved|anpwidget|apsisautomation|zoomio|soekmotorer|mmm02|marknadsundersokning|enkaet|emailserver3|emailserver2|apsispro|apsis-newsletter-pro|apsisczech|anptr|anppub|anpmail|anpimg|apsis|apsis-rnd|apsisrnd|anptaf|apsisprospecteye|anpasia1|dme2019|dme2018|apsis1|apsisone|eme2017|apsislead|one-lnk)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(retrack\.com|anninces\.net|bijoudumois\.net|jeu-cheque2\.com|leadys\.net|elixis-worldpanel\.com|makeup-line\.fr|macamerasportive\.com|shetell\.fr|shetells\.fr|2100\.fr|elyxis\.at|jolice\.fr|afkkjd9\.com|cadeau-exceptionnel\.com|afk7hdh\.com|afjdk9\.com|afhvgh\.com|afhshd8\.com|apolon\.fr|afh8jjn\.com|afh8hv\.com|afh7hh\.com|aff6hdh\.com|afdhf5g\.com|afdh4dgf\.com|afdgf5\.com|afdf5h\.com|afd8jub\.com|afd8jh\.com|afd5gd\.com|af9jdjf\.com|af9fdh\.com|af7hduh\.com|af7hdhfh\.com|af6fdfd\.com|af5fvdg\.com|af4kjhp\.com|af4fdg\.com|af3hgd\.com|af0klh\.com|lecoindestesteurs\.net|lecoindestesteurs\.com|credit-rembourse\.com|cheque-2017euros\.com|mesmeubles-offerts\.com|mavoiture-offerte\.com|mon-telephone-offert\.com|wisu\.fr|affaires-du-moment\.fr|affaires-exclusives\.fr|elixis-worldpanel\.fr|elixis-panel\.fr|elixispanel\.fr|elixisworldpanel\.fr|witrk-ing\.com|witrking\.com|puericultrices\.fr|mycosmeticbox\.fr|finebird\.cz|finebird\.fr|finebird\.net|realtys\.fr|argent-a-gagner\.fr|chequeagagner\.com|jeu-jackpot\.com|jeu-gourmand\.com|jeu-videobuzz\.com|sondage-banque\.com|retrack\.fr|luxehotel\.fr|zaola\.fr|pass-cinema2\.com|justcook\.de|ange-marie\.fr|anys\.fr|1andecafe\.fr|3500euros\.com|16000euros\.com|cheque-2016euros\.com|jeudes3erreurs\.com|jeu-champagne\.com|roue-gagnante\.com|sondage-officiel\.com|isya\.cz|isya\.fr|zofia\.fr|cheque3000euros\.com|elyxis\.de|chrys\.fr|lifestylemedia\.fr|sondage-noel2\.com|sondage-noel\.com|repasdefetes\.fr|citys\.fr|elixisagency\.fr|elixis-agency\.fr|destock-experts\.fr|destockexpert\.fr|destock-expert\.fr|belita\.fr|galaxydesjeux\.com|wimea\.fr|wunderwerkzeug\.com|sandie\.fr|mon-robot-patissier\.com|tabletteofferte\.com|anisse\.fr|matcher\.fr|wirprodukttester\.com|produktetesten\.net|pruebaproductos\.com|yihi\.fr|waldorf\.fr|salia\.fr|sondage-actu\.com|seona\.fr|mailkraft\.de|avenue-shopping\.com|elixis-consult\.com|meilleurespromos\.fr|german-kunst\.com|zinder\.fr|bijoudumois\.com|elixis-event\.com|o2o\.fr|laubenheimer\.info|riltis\.com|dnsriff\.com|gagnant-express\.com|xn--chimra-eua\.com|sanah\.fr|saffrondiscount\.com|premium-service-hosting\.com|ysia\.com|123uomo\.com|senxuel\.com|mysqlguide\.com|yihii\.net|united-systemics\.com|unitedsystemics\.com|elixispanel\.com|elixis-panel\.com|germankunst\.com|voscourses\.fr|lidys\.fr|make-up-line\.fr|makeup-line\.net|make-up-line\.com|vod-illimite\.com|leadis\.fr|guap\.net|laedis\.com|smartphoneoffert\.com|aniss\.fr|monparcanimalier\.com|conso-mieux\.com|jeu-gps\.com|elixisleads\.com|madame\.com|make-up-line\.net|retrotrend\.biz|jeu-chequecadeaux\.com|avis-officiel2\.com|meilleurdubuzz\.com|elixis\.info|kiwiactu\.com|silver-clope\.com|silverclope\.com|institutronflement\.com|douceurdebulles\.com|2015euros\.com|institut-ronflement\.com|elx03\.com|elx02\.com|monsacdecreateur\.com|destockexpert\.com|jeu-matablette\.com|jeu-cheque2000euros\.com|1andecroquettes\.com|leadi\.fr|productesting\.net|testingfreebies\.com|ela-conta\.com|elixisprevention\.com|leivale\.com|jeu-couturier\.com|ysia\.cz)$',hostname)
    if matches: return True

    whywhy = "Line 596"
    matches = re.search('[0-9\.\-]+(static\.claro\.com\.uy|ipberlin\.com|vps\.masterhost\.ru|netvisao\.pt|foxnet-imt\.akn\.ca|atvci\.net|dyn\.telefonica\.de|enitel\.net\.ni|netways\.de|vshosting\.cz|net\.il|eurotel\.cz|personal\.net\.py|arn\.eastex\.net|koba\.pl|reverse\.destiny\.be|ibara\.ne\.jp)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(in-addr\.btopenworld\.com|doruk\.net\.tr|globo\.com|ysk\.scts\.tv|hostsrv\.org|rev\.nazwa\.pl|static\.turkishost\.com|timetovisitthecaverns\.net|routergate\.com|mobile\.tre\.se)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(dyn\.prod-infinitum\.com\.mx|squareflow\.net|sta\.prod-empresarial\.com\.mx|dhcp\.fibianet\.dk|hosting\.magiconline\.fr|mksnet\.com\.br|mytele\.com\.ua|hrnet\.fr|altel\.kz)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(static\.internetadresi\.com|static\.turkns\.net|dri-services\.net|server\.dyd\.es|ip\.idealhosting\.net\.tr|nat\.tls1a-cgn[0-9]+\.myaisfibre\.com|static\.a2hosting.com|fixnet\.cz|vds\.myihor\.ru|msk-ovz\.ru|inter\.net\.il|connect\.az)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(.dynamic-dsl-ip\.omantel\.net\.om|pool\.telenor\.hu|cable\.cybercable\.net\.mx|clickturbo\.com\.br|bscom\.ru|insideprovider\.com\.br|static\.fprt\.com|tigo\.bo|accesshaiti\.net|lesmails\.world|egs63\.ru|reverse-dns\.chicago|primatecmt\.com\.br|dyn\.vf\.net\.nz|tmg\.md|internet04\.ru|compute\.hwclouds-dns\.com|in-addr\.arpa\.mkanet\.com\.br|port\.east\.myfairpoint\.net|host\.redstation\.co\.uk|in2cable\.com|broad\.bj\.bj\.dynamic\.163data\.com\cn|pg-nat-pool\.mts-nn\.ru|ds\.superofertapara\.es|mxt\.net\.br|rev\.cloud\.scaleway\.com|static\.megawifinet\.com\.br|static\.as40244\.net|cust.tele2.se|irenala\.edu\.mg|mp-dhrd\.com|mail\.wx01\.net)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(static\.only\.fr|-red-servicios\.onlycable\.es|homesystemsnet\.com|reallifetelecom\.com\.br|ip\.tps\.uz|broadband\.tenet\.odessa\.ua|dyn\.eolo\.it|megalinkcorp\.com\.br|o\.kg|xdsl\.murphx\.net|uacity\.net)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(zuknet\.com|static\.tie\.cl|-cl-dyn-nat\.kitenet\.ru|gprs\.simobil\.net|mittum\.com)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(iplannetworks\.net|hsi2\.kabelbw\.de|une\.net\.co|codetel\.net\.do|telecel\.com\.py|ecutel\.net|nat\.spd-mgts\.ru|br1-DYNAMIC-dsl\.cwjamaica\.com|fibernet\.hu|xdsl\.primorye\.ru|lytzenitmail\.dk)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(skybandalarga\.com\.br|freenet\.com\.ua|mobile\.spark\.co\.nz|airtel\.in|digitalprovedor\.com\.br|mpst\.net\.br|siberianet\.ru|novotelecom\.ru|fr-south\.jaguar-network\.net|terabit\.net\.id)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(dyn\.as[0-9]+\.net|f\.sta\.codetel\.net\.do|pppoe\.chelny\.ertelecom\.ru|fidro\.pl|milleni\.com\.tr|mma\.com\.br|hsi[0-9]+\.kabel-badenwuerttemberg\.de|lte\.broadband\.is|clients\.tlt\.100megabit\.ru|)$',hostname)
    if matches: return True

    whywhy = "Line 618"
    matches = re.search('\.(certificat-immatriculation-vehicule\.com|mon-enquete\.com|avis-concessionnaire-automobile\.com|entretenirsonauto\.com|auto-bande-annonce\.com|essayer-1-voiture\.com|cote-auto-officielle\.com|vente-flash-swarovski-elements\.com|pour-ou-contre\.fr|welcome-data\.fr|xn--location-longue-dure-t2b\.com|avis-infos\.com|encuestaoficial\.com|encuestaconsumidor\.com|granencuesta\.com|enquete-info\.com|enquete-avis\.com|idee-conseil\.com|projet-avis\.com|speed-chain\.com|enquetenews\.com|enqueteinfo\.com|enqueteconsos\.com|contactvoitures\.com|info-enquetes\.com|news-voitures\.com|cardatapro\.pro|newsenquete\.com|monassurancevoiture\.fr|contactenquete\.fr|enquete-privee\.fr|pixooo\.fr|2fois\.fr|echantillons-gratuits\.fr|clientavis\.fr|subhakamana\.fr|nippycar\.fr|nippysearch\.com|nippycar\.com|flash-boutique\.fr|onedata\.fr|otopix\.fr|chatbotsystem\.fr|chatbot-solution\.fr|chatbotsystem\.com|chatbot-solution\.com|e-chatbot\.com|shoppingenquete\.fr|webstreetshopping\.fr|shoppingenquete\.com|trouver-ma-banque\.com|dataquest\.fr|essai-voiture-neuve\.fr|cote-auto-officielle\.fr|switchter\.fr|franprixxmaviedechat\.com|cost0\.net|renthiscar\.com|entretenirsonauto\.fr|revisersonautomobile\.fr|revisersonautomobile\.com|databee\.fr|sondageofficiel\.com|conso-enq\.fr|virtualcheat\.net|les-ventes-flash\.fr|venteflashmoins50\.fr|creepxel\.net|gopixel\.fr|gopixelnetwork\.fr|gopixel\.net|consolead\.fr|mandatairesautomobiles\.fr|cardatapro\.com|tu-opinion\.com|6999\.fr|autokoopro\.com|venteflashswarovskielements\.com|vente-flash-swarovski\.com|venteflashswarovski\.com|vente-flash-noel\.com|venteflashnoel\.com|essayez-une-voiture-electrique\.com|essayezunevoitureelectrique\.com|essayer-une-voiture-electrique\.com|bobys\.net|creditkoo\.com|outletmajor\.com|major-outlet\.com|certificatdecession\.fr|certificat-de-non-gage\.fr|soldes-premium\.com|solde-outlet\.com|solde-info\.com|outlet-solde\.com|outletselection\.com|outletliner\.com|outlet-grand\.com|last-outlet\.com|lady-outlet\.com|cote-voiture\.info|conseil-info\.com|otopix\.com|neuf-euros\.com|occasionsautos\.net|neufeuros\.com|meilleure-occasion\.com|switchter\.com|demanderunessai\.com|0-cost\.com|cost-0\.com|19francs\.com|auto-koo\.com|10byday\.com|demander1essai\.com|6-euros\.com|xn--actualit-auto-ihb\.com|autobandeannonce\.com|10atwork\.com|votreavisgarage\.com|fishandships\.fr|premiere-vente\.com|vente-privee-voiture\.com|brasilinhas\.com|solde-flash\.com|venteflashprive\.net|vente-flash-prive\.com|venteflashprive\.com|venteflashmoins50\.com|flashdiscount\.net|cote-voiture\.biz|ventes-flash\.info|ventes-flash-du-mois\.com|mon-essai-voiture\.com|les-ventes-du-mois\.com|essai-voiture-neuve\.com|conso-avis\.com|consoavis\.com|auto-cote-officielle\.com|autocote\.net|prospectsauto\.com|adopteunecaisse\.com|essai-auto\.biz|datawork\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(look-sales\.com|express-actu\.com|stop-to-spam\.com|decouverte-naturelle\.com|discount-element\.com|monactufacile\.com|turbo-infos\.com|thesocialmining\.com|promoccasion\.com|themininglabs\.com|themailinglabs\.com|thedigitlabs\.com|lesmeilleursoffres\.com|offresflash\.com|mesnewsinfos\.com|tdl-bdd\.com|boulevarddespromos\.com|penser-eco\.com|ledgerprice\.com|free-conso\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(wowmex\.com|uwz\.info|dominatedlive\.com|systemgain\.com|frodosring\.com|postpartumpedia\.com|tqsolo\.com|sdspecialist\.com|atlantaweddingrentals\.com|onlinecasinovirtual\.com|chargeitall\.com|goldenpensions\.com|retirement-4-u\.com|contentname\.com|denturedirectory\.com|standupnepal\.com|befinite\.com|medicaltraveltaiwan\.net|healthonline24\.com|makeproxy\.com|charlesdickensmuseum\.com|onlinedomainappraisal\.com|labesk\.com|northernjewels\.com|vulvapedia\.com|timelessac\.com|haocq668\.com|bryceshop\.com|epaitg\.com|hexarelin\.net|coastalcarolinahomeinspections\.com|cellphonestores\.org|snuglr\.com|livejamis\.com|gasrangereviews\.org|sanantoniomarketingfirm\.com|pdfmanuals\.org|terramay\.com|619life\.com|unegouttealafois\.com|handiroom\.com|leobia\.com|goldmantra\.com|professionalliquidators\.com|hangxianlou\.com|celiacpedia\.com|fitnessproductsstore\.com|opalls\.com|fundwanted\.com|dogbuilt\.com|lottery-vwd\.com|fastppl\.com|cucadas\.com|route168\.com|hotspot411\.com|nixyourmortgage\.com|tatacapitol\.com|idoparts\.com|maikuraki\.org|jiaozhid\.com|fordseo\.com|scrantoninvestments\.com|scaleandspeed\.com|templelake\.com|darksideofcats\.com|liminvest\.com|hljcapital\.com|mtlcz\.com|bo499\.com|import-export-international\.com|daisyboots\.com|apps-world\.org|buzzardguard\.com|truecontents\.com|biquyetchamsocsuckhoe\.com|locksmith-ottawa\.com|genesis-image\.com|kienthuconline\.info|giagntits\.com|ggantits\.com|googlehosts\.com|irepaircells\.com|jobfist\.com|britemed\.com|trumovement\.org|cutebabydolls\.com|buyonwholesale\.com|solife\.info|goldkilos\.com|awesomelyfit\.com|hukukcafe\.com|tobiasherbst\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(happy-offres\.com|avantages-internautes\.com|affaire-de-diil\.com|cible-du-web\.com|business--garantie\.com|diil-du-jour\.com|garanti-pratique\.com|les-immanquables-du-net\.com|solutions-qualite\.com|solutions-francetpe\.com|solution-francepme\.com|webnet-idees\.com|votre-abo-net\.com|travel-particuliers\.com|agence-des-deal\.com|ddil-hebdo\.com|fournisseur-particuliers\.com|fournisseur-entreprises\.com|entreprises-idees\.com|hebergement-travaux\.com|machine-a-rever\.com|palatine-journee\.com|offres-chic\.com|safe-envoi\.com|swed-business\.com|ab-deal\.com|deal-envoi\.com|deal-dif\.com|fichier-deal\.com|diil-sender\.com|diil-business\.com|aventure-shopping\.com|autempsdespromos\.com|autantpromo\.com|aretylo\.com|alominare\.com|fikilou\.com|figilitempo\.com|fasilavy\.com|entituto\.com|emotionaffaire\.com|grimiulo\.com|gojuliurtik\.com|gikilomine\.com|kiritout\.com|kiloupa\.com|kilerium\.com|kijulio\.com|kankandunet\.com|juliumpro\.com|izikiri\.com|istripoint\.com|ilyvatou\.com|iliporu\.com|ilimonou\.com|ikloutonium\.com|laiplans\.com|koulumotu\.com|kolijuri\.com|ovniduweb\.com|oustaoupromo\.com|oulimono\.com|oulimona\.com|ouistikir\.com|primiko\.com|polytroptik\.com|polytrikiom\.com|polytricite\.com|polymorite\.com|polimorite\.com|polimonium\.com|tankaffaire\.com|stadiolu\.com|vazipami\.com|tempoparadis\.com|youille\.com|yojulium\.com|deal-electromenager\.com|voyage-lounge\.com|deal-agency\.com|mode-addict\.com|affprom\.com|deco-lounge\.com|dag-partner\.com|dag-info\.com|dag-business\.com|deal-agency-group\.com|affimode\.com|affaires-promo\.com|affaire-reduce\.com|communication-www\.com|c-laffaire\.com|cestaprendre\.com|extension-duweb\.com|expe-france\.com|existanceone\.com|excellence-promo\.com|great-affaire\.com|grany-style\.com|grany-merci\.com|kiyva\.com|gojuli\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(cardatapro|tu-opinion|autokoopro|vente-flash-swarovski-elements|venteflashswarovskielements|vente-flash-swarovski|venteflashswarovski|vente-flash-noel|venteflashnoel|essayez-une-voiture-electrique|essayezunevoitureelectrique|essayer-une-voiture-electrique|creditkoo|outletmajor|major-outlet|soldes-premium|solde-outlet|solde-info|outlet-solde|outletselection|outletliner|outlet-grand|last-outlet|lady-outlet|venteflashprive.net|vente-flash-prive|venteflashprive|venteflashmoins50)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(mon-essai|caxirolaofficiel|ladecote|venteflashauto|voiturespro|demanderunessai|demander1essai|credit-voitures|otopix|19francs|10byday|6-euros|autobandeannonce|votreavisgarage|cote-auto-officielle|conso-avis|consoavis|auto-cote-officielle|prospectsauto|adopteunecaisse|xn--actualit-auto-ihb|conseil-info|neuf-euros|cost-0|meilleure-occasion|neufeuros|switchter|0-cost|auto-koo|10atwork|premiere-vente|vente-privee-voiture|brasilinhas|ventes-flash-du-mois|mon-essai-voiture|les-ventes-du-mois|essai-voiture-neuve)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(shoppingenquete|trouver-ma-banque|renthiscar|franprixxmaviedechat|mail-orange|deseguin|crowdstrength|billionworkers|cortexsupport|photo-media-management|media-licensing|karl-f|crowdforall|royaltyguide|simpleforall|royaltyengine)\.com$', hostname)
    if matches: return True
    matches = re.search('\.(virtualcheat|gopixel|bobys|flashdiscount|occasionsautos|autocote|cost0)\.net$', hostname)
    if matches: return True
    matches = re.search('\.(solde-flash|contactvoitures|encuestaoficial|encuestaconsumidor|enquete-info|enquete-avis|idee-conseil|speed-chain|enquetenews|enqueteinfo|enqueteconsos|info-enquetes|news-voitures|info-enquete|newsenquete|revisersonautomobile|sondageofficiel)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(databee|monassurancevoiture|contactenquete|pixooo|xn--location-longue-dure-t2b|echantillons-gratuits|clientavis|subhakamana|nippycar|nippysearch|nippycar|welcome-data|flash-boutique|onedata|chatbotsystem|chatbot-solution|e-chatbot|shoppingenquete|webstreetshopping|dataquest|essai-voiture-neuve|cote-auto-officielle|switchter|entretenirsonauto|revisersonautomobile|conso-enq|pour-ou-contre|les-ventes-flash|venteflashmoins50|gopixel|gopixelnetwork|consolead|mandatairesautomobiles|certificatdecession|certificat-de-non-gage|6999|fishandships|datawork)\.fr$',hostname)
    if matches: return True
    matches=re.search('\.(dsl\.candw\.ky|anc24\.com|cafe24dns\.net|fmsalon\.com|trendpalette\.com|wehost24\.com|nodesign\.kr|mart24\.cn|cafe24inc\.com|cafe24-corp\.com|cafe24-corp\.biz|cafe24corp\.kr|cafe24corp\.us|cafe24corp\.biz|cafe24corp\.cn|cafe24corp\.net|bystyle\.kr|ecqa030\.com|ecqa029\.com|ecqa028\.com|ecqa027\.com|ecqa026\.com|ecqa025\.com|ecqa024\.com|ecqa023\.com|ecqa022\.com|ecqa021\.com|cafe24app-qms\.com|eintrittsarmband\.de|cafe24net\.com|cafe24dreamlounge\.com|cafe24ceobank\.com|smartcafe24\.com|simplexcafe24\.com|asianfashionfans\.com|fashandstuff\.com|cafe24blog\.com|k-stylebrandshub\.com|kstylebrandshub\.com|k-stylebrands\.com|kstylebrands\.com|k-stylebrand\.com|kstylebrand\.com|cafe-24\.kr|cafe24solution\.com|simplexicorp\.com|cafe24platform\.com|cafe24ec\.com|cafe24group\.com|cafe24corp\.com|globalcafe24\.com|fancyphony\.com|tomskee\.de|totalhts\.com|hansms\.com|koreafushi\.com|askreal\.com|shopif\.com|saleif\.com|kspashop\.net|kspashop\.com|kspamall\.net|kspamall\.com|cafe24\.com|nandawears\.com|simplexi\.com|shoppickme\.com|pickist-ranking\.com|modadecorea\.com|welovestylekorea\.com|kfuku\.com|u-rankedin\.com|kstyle-ranking\.com|koreafashionmall-ranking\.com|ecranking\.com|kstylelove\.com|oshyareblogger\.com|myclothespin\.com|kstyleholic\.com|all-thevogue\.com|poxo\.com|hotxia\.com|cafe24\.us|ycdragon\.com|xn--9t4b19so9bwxb65a\.com|widechat\.com|webagencyinc\.com|wikicafe24\.com|user24\.com|uscafe24test\.com|usacafe24\.net|usacafe24\.com|ultrastudy\.net|ultrastudy\.com|siminfra\.com|slinemodel\.com|simplexinternet\.com|shopcafe24\.com|romancechat\.net|realir\.com|realhts\.com|xn--p89anzg24betd54dkw2abqa\.kr|fta24\.com|envylook\.jp|sice\.kr|spaofkorea\.net|xn--24-he3kz3o\.net|domaintest\.kr|waku\.kr|allook\.kr|brrm\.de|fancy24\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(bonjour57|data-de-folie|kso2web|voyageskso|viraneo|wheresiton|vml01|offre-shopping|lesspayroll|rackap|yourhostingaccount|saitenthouse)\.com$', hostname)
    if matches: return True
    matches = re.search('\.(unerencontreunflirt|swwea|com-serveur|shoppingenquete|news-shopping|vos-actualites|thetopdesnews|zebestoffre|newsendelire|helptotal)\.com$', hostname)
    if matches: return True
    matches = re.search('\.(assurdunet|dlems4|moment-digital|lancerai|ml127ura|focusbignews|plansvoyages|top-plans-voyages|ecommerce-hosting|lamegaoffre|objectif01)\.fr$', hostname)
    if matches: return True
    matches = re.search('\.(compaignmail[0-9]+|emw01|trk-beta|mail-kitchen|salesgine|lr001|wm02|bpm06|bpmd01|bpmd02|bpmd03|bpmd05|logiciel-mailing)\.net', hostname)
    if matches: return True
    matches = re.search('\.(mixinfos|lactupromo|nlbjrinformatie|nlparfaitenews|newsvisionconso|newsinfprecieuse|mmeprivee|nlactupromo|madameprivee|consoenlive|consolive|linforock)\.(fr|com)$',hostname)
    if matches: return True
    matches = re.search('\.(newsmixinfos|nlrockinfo|infofanactu|lesfrenchics|lesfrenchics|maparfaitenews|malettreinfo|laparfaitenews|nlmalettreinfo)\.(fr|com)$',hostname)
    if matches: return True
    matches = re.search('\.(flashactualites|lanews-delouise|leflashactu|lecoindesinfos|clubdesactus|leclubactu|bonjourinfo|bjrinfo|nlbonjourinfos|hbwkmedia)\.(fr|com)$',hostname)
    if matches: return True
    matches=re.search('\.(lacledesmails|lanewsendelire|lecadodujour|lesdealsdujour|laruedesoffres|lanewsdeparis|mailincroyable|lamagiedesoffres|boulevarddesaffaires|promos-news|lavenuedesaffaires|lecercledesaffaires|thecoin|deals-magiques|comptoir-offres|cercle-offres|offre-conso|deals-promos|loffreirreelle|latopdesoffres|leparcdesdeals|thecado|lebestdeal|espace-deals)\.fr',hostname)
    if matches: return True
    matches = re.search('\.(coindelinfo|vnorbert|vehiculeinfo|espace-offres|news-voitures|clubdespromos|vieor23|la-bonne-economie95|getresponse|rouge31|smtp190)\.com$',hostname)
    if matches: return True
    matches=re.search('\.(habitat-conso|emk02|emk04|fuanis|it-news-media|monet-easy|infowebconso1|mynewsconso3|okotrk|opetrk|woahtrk|daily4kso|actutocharmes|actu2charmes|actu2charme|creditpourlakonso|creditalakonso|eshopkso|ksoweb|ksotoweb|ksodevice|ksobeaute|kso2web|kso2voyage|kso2device|konsodevice|konso2web|konso2voyage|konso2device|news2mode|vacanceskso|vacancesakonso)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(vacances2kso|vacances2konso|tonner2konso|voyageskso|voyages2kso|voyage-kso|vieux-rhum|contactobiz|expertisemail)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(emailtrackingserver|emailsensorsystem|emailsensorserver|emailanalyticssystem|emailanalyticsserver|mailtrackingserver|mailsensorserver|mailanalyticsserver)\.com$',hostname)
    if matches: return True
    matches=re.search('\.(bigudx|emailtrackingsystem|tritrk|pus2011|meilleuresoffresduweb|mail-kitchen|it-newsmedia|itnewsmedia|it-news-info|clubymail|cca10|audiensis|itnewsinfo|consoweb4|consoweb3|consoweb2|consoweb1|infowebconso4)\.com$',hostname)
    if matches: return True
    matches=re.search('\.(mynewsconso1|myconso2web4|myconso2web3|myconso2web2|privcadeau|coregme|adthink|mobilierkonso|konsonet|qzdeal|cpl-traffic|blog-digital4cast)\.com$',hostname)
    if matches: return True
    matches=re.search('\.(infowebconso3|infowebconso2|myshopconso4|myshopconso3|myshopconso2|myshopconso1|myshop2conso4|myshop2conso3|myshop2conso2|myshop2conso1|mynewskso4|mynewskso3|mynewskso2|mynewskso1|mynewsconso4|mynewsconso2)\.com$',hostname)
    if matches: return True
    matches=re.search('\.(aproxtrack1|aproxtrack2|aproxtrack3|aproxtrack4|aproxtrack5|aproxtrack6|aproxeml10|aproxeml11|aproxeml12|aproxeml8|aproxeml9|aproxtrack7|aproxeml|vml-pml|barnabe295|alexis295|carg-track|bpm039|codes-cadware|vml-art|bpm294)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(mangesvis-profites|mangevieprofite|mange-vieprofite|lanews-letter-delouise|lanews-letterdelouise|la-newsletter-de-louise|mangevie-profite|mange-vie-profite)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(manges-vis-profites|manges-visprofites|mangesvisprofites|la-newsdelouise|mange-visprofite|lanewsde-louise|lanews-delouise)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(mangevis-profite|mangevisprofite|lanewsletterdujour|la-news-letterdelouise|la-newsletterdelouise|lanewsletter-delouise)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(la-newsdelouise|lanewsdelouise|lanews-delouise|bonplanbonnenews|config-manewsdelouise|astucedumoment)\.fr$',hostname)
    if matches: return True
    matches = re.search('\.(meilleure-vie95|promodemalade|lavenuedesaffaires|adaccess|think-manstyle|think-women|think-kid|think-cooking|think-maternity|think-woman|think-men|think-families|itnewsinfo|think-about|think-menstyle)\.fr',hostname)
    if matches: return True
    matches = re.search('\.(machaire295|lanewsdelouise|mascainfodeux|mascainfoun|mascameldeux|mascamelun|theatredetours|mascabase|mascaline)\.com$',hostname)
    if matches: return True
    matches=re.search('\.(usedater|femmeseductrice|celiavoyance|regimesmartin|allo-bonheur|apprentiseducteur|youratlas|ceemino)\.com$',hostname)
    if matches: return True
    matches=re.search('\.(start-chat|ebooking-ac|banderolepromail|banderoleecomail|wwwbanecomail|mailbanderolepro|banderole-ecom|banderolepubm)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(classesforseniors|theinnovations|tootor|bigbendcatering|futbaltv|xn--magntoscopes-eeb|bostonvoters|alabamavoters|martialartfriends|meatingcafe|eddielehmann)\.com$', hostname)
    if matches: return True
    matches = re.search('\.(albanianwireless|petroleumworker|incidencerate|systematicsampling|standardhtml|lpbiopharma|lpiawards|lpifund|cognitiveneuro|phillydentist|energytrapping|onlinecookbook|onelovesurf|thestlouisblog|thestockholder|southernpinestv|pleasurecompanion)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(violatedasians|generationstrust|comparateurdeprixelectromenager|giantdumptruck|gloucestertv|365words|kornalbum|bankruptcyfilingfee|goldsputtering|bailbondsincleveland|vaunted|daysinsider|townees|towliterv|topekabankruptcy|tinypickles|educationology|logicaldesigninc|logicalreference|logicalarrangement|softdrinker|logicalcatalog|theusconstitution|sanantoniopodcast)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(fixmyhost|cebumedicalholiday|carscoverage|coralspringspersonalinjury|obamabrewery|christmas-gift-guide|provillusdealorganicwhitechedderpopcorn|organicgourmetpopcorn|redstateradio|poblanopepper|carleaseswapper|internetmovietheatre|goldcoastclubs|dctee|ditmarcompany|drmarcola|fminiclip|girots|gik2|ggpiftcard|getaawaytoday|geolauterer|geocasche|gencirlces|gemfare)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(garrysullivanonline|gargeners|gardonline|gandband|gamefreake|gambados|galvastoncruises|gainscoconnet|futrivia|furnaturefair|fuderstanding|fryriglet|frionastar|frindlyplanet|fretress|freezumagame|freeomathometeam|freedomtofascim|freeadicting|fredrickross|freakhos|fpandl|fourweller|forenews|forecloserhouse|foodloocker|fnborville|flypitt|flyingbleu|flyarina|floyordie|floydhallareana|floridalotteri|floridaccess|flordahospital|floop-show|flfootball|fistnight|fingagrave|fimhongkong|fidfs|fhnfuneral|ferruci|fantasyjewlry|fantasybasball|mybusinessblogging|famlylife|famlyeducation)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(familychritian|fahrneyspen|factorymatress|explotedblackteen|exoticfragances|exloitedteensasia|exchangeccboe|excellenergycenter|evaailine|ethiomedi|ethiomeadia|ethioianreview|estudiaespanol|esrvice|espeida|esdds|esconinst|escapede|eruorail|erosguige|ernestpugh|elmontery|ellsisland|eletion|elemantryschools|electrbike|el-leadies|ekantupur|eitienneaigner|eglisenouvellevie|eglinks|eeuroautoparts|eesticket|ecommuity|ebbyhollidayrealtors|eatonmall|easy-fundrasing-ideas|eastenairlines|easibib)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(hothdmovies|symbcraft|fulterstmail|daytraderbooks|minibits4hclub|reasonablecharges|news-adk|greatwritings|iphoneplan|chakraportals|emailwithus|anewrepublic|gasreservoir|digitalnewsradio|bowlingballpaintjob|esisters|cruisejobstoday|blogmentor|virtbio)\.com$',hostname)
    if matches: return True
    matches=re.search('\.(rtcoach|usedater|rt1-solar|chichs|russiancurrency|houstonduilawyer|checkmath|bestairlines|militaryvoters|coloradopersonalinjury|federalreport|babymooning|houstonairporthotels|secondaryenergy|secondarypower|totalproxy|toobaroo|babymoonideas|besthomeschool)\.net$',hostname)
    if matches: return True
    matches = re.search('\.(mangevie-profite|promodiscovery|emlsrv|mabanderolebache|votre-bache|laffairedujour)\.fr$',hostname)
    if matches: return True
    matches = re.search('\.(koala-srv-6|koala-srv-7|rev-srv-b04|rev-srv-b03|rev-srv-b01|rev-srv-a03|rev-eta-04|astime-relais-2|wrk-relay|koala-srv-9|koala-srv-8|koala-srv-5|koala-srv-4|koala-srv-3|koala-srv-2|koala-srv-1|motad-1d|motac-1c|motab-1b|rev-r-srv-18|rev-q-srv-17|rev-n-srv-14|rev-m-srv-13|rev-l-srv-12|rev-k-srv-11|rev-j-srv-10|rev-i-srv-9|rev-h-srv-8|rev-g-srv-7|rev-f-srv-6|promoled-rev3|promoled-rev2|plmo1609|rev-05-srv)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(rev-04-srv|rev-03-srv|rev-02-srv|rev-01-srv|matec-01|aj2m-rvs|gofto-fr|goeto-fr|gocto-fr|gobto-fr|goato-fr|apjai-r|aokah-r|anlag-r|ajpac-r|aiqab-r|ahraa-r|agsaz-r|aftay-r|aeuax-r|advav-r|acwau-r|abxat-r|aayas-r|ayaar-r|axbaq-r|awcap-r|avdao-r|auean-r|atfam-r|gotab-0b|redirection-008|redirection-007|redirection-006|redirection-005|redirection-004|redirection-003|redirection-002|redirection-001|rev-srv-a09|rev-srv-a08|rev-srv-a07|comparatif-loigiciel-emailing|rev-srv-a06|rev-srv-a05|rev-srv-a04|rev-srv-a02|rev-srv-a01|rev-eta-03|rev-eta-02|rev-eta-01|red-express-08|red-express-07|red-express-06|red-express-05|red-express-04|red-express-03|red-express-02|red-express-01|rev-e-srv-5|rev-d-srv-4|rev-srv-1117-1|rev-c-srv-3|rev-b-srv-2|rev-a-srv-1|asgal-r|arhak-r|aqiaj-r|promoled-rev4|promoled-rev1|redirection-express-02|redirection-express-03|godto-fr)\.com$',hostname)
    if matches: return True
    matches = re.search('\.(ccemails\.(com|net)|ccmdcampaigns\.net|cible-direct\.com|.com\.(br|ar))$', hostname)
    if matches: return True
    matches = re.search('\.(mountng\.kundenserver\.de|consoproduit\.com|dokyto\.com|hinet\.net|deliver-emails\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(emfo01\.net|tr-2lm\.com|ro-marketing\.com|infoshebdo\.com|exacttarget\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(se[0-9]*-zemail\.fr|wincib[0-9]*\.com|cdb-tracking\.com|linkhprinting\.com|riversidedomain\.net)$',hostname)
    if matches: return True
    matches = re.search('\.(brokeragentnews\.net|ixopio\.com|yupigroup\.com|milkywaystar\.com|clickwork\.ro)$',hostname)
    if matches: return True
    matches = re.search('\.(nierle\.com|minufcort\.net|promos-max\.com|lideur\.com|wnywebservice\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(umel\.fr|wangoo\.fr|horinews\.com|hooganet\.com|mult-e-media\.net|bp06\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(maileto\.com|newstroll\.de|onm6[0-9]*\.com|procontact\.us|wew(m)?[0-9]*\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(puissance3\.fr|zoramail\.com|zorpia\.com|coreproactive\.com|agenceweb\.net|tamoudahost\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(reachyu(-.*)?\.(fr|com)|kagoya\.net|emr174\.net|ec[cm]luster\.com|buzzee\.fr|heb-prce\.com|beid3\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(lb-clients\.com|appli-lb\.com|macronim\.dk|oanm71\.com|libina[0-9]+\.com|obnm[0-9]+\.com)$',hostname)
    if matches: return True
    matches = re.search(
        '\.((fr-eco|allez-)discount\.fr|rglina[0-9][0-9]\.com|xlsystem\.dk|edt02\.net|sitedam\.pro|chat-femme\.net)$',
        hostname)
    if matches: return True
    matches = re.search('\.(kasamarketing\.com|message-business\.com|freferencement\.com|8866\.org|tr-pdf\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(lb-lead-client\.com|discount-club-en-ligne\.fr|info-club-direct\.fr|data0(2|4)\.net)$',
                        hostname)
    if matches: return True
    matches = re.search(
        '\.(emaildiff\.com|ymlp([0-9]*).(net|com)|mesplansmalins\.fr|direct-club-prive\.fr|verticalresponse\.com)$',
        hostname)
    if matches: return True
    matches = re.search('\.(partners\.domeus\.com|discount-info-club\.fr|onedirect\.fr|club-affaire-privee\.fr)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(anews[1-3]\.info|upmessage\.com|emailing-manager\.pro|amassi-network\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(yoolight\.fr|o[b]fm4[0-9]\.com|le-club-plus\.fr|createsend\.com|algodata\.fr|votremag\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(vmle698\.com|mailclub\.pro|proxeditmaroc\.com|e-mailink\.fr|switchcall\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(message-business\.net|yosmail\.com|maiyn\.ma|rd-campagnes01\.net|infocart\.dk|du-comparateur\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(ldipromo\.net|md0[12]\.com|mc10\.fr|mlsend\.com|votre-univers\.fr|adwerticum\.com|qc-direct-emails\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(ifacteur\.com|ec(m)?(-)?cluster\.com|sitedim\.pro|rayinformations\.com|puc-rio\.br|venamail\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(dms3[0-9]\.com|massivemedia\.eu|ytek-web\.net|votre-kdo\.fr|club-bonne-affaire\.fr|rayspro\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(offres-exclu\.com|cumer\.ru|vos-tendances\.fr|bdwebmarketingshop\.com|webexclu\.com|connectmailing\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(executivemailingservices\.com|emailing-vdm\.alienor\.net|soloads4u\.net|\.vietguys\.|mailmela\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(growbiznew\.com|adzbymawaqaa\.com|weblogi\.dk|paysinsurance\.com|offrediscounts\.|mtae\.fr|easyreduceri\.ro)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(nextwebmaster\.com|addpriceqoute\.com|pole-envoi\.com|automailer\.se|technical12\.info|algomail\.com|cloudmailer\.se)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(tendance-du-jour\.fr|dermocosmetice\.ro|hotaffili\.com|admsend\.com|turbosmtp\.net|spmailing\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(propection\.com|mail-works\.com|corpmail\.net\.br|entregador-mkt\.net|info-club-prive\.fr|marketingbox\.ro)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(raysnew\.com|club-mieux-acheter\.fr|newsofts\.eu|actionengineering\.com|delosmail\.com|free3\.pro|haykmedia\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(mwf02\.com|bkns\.com\.vn|spd\.co\.il|lacontessa\.net|enviosrapidos\.net|mktlionbrasil103\.info|vpsdez\.net)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(dualoportunidade\.com|frdcrp\.com|encoreplusdereduc\.com|ukrynet\.net|warydrup\.net|mkt-emailsdobrasil\.net)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(sucosfree\.com|cab[01][0-9]\.net|toocamp\.eu|trubadyr\.net|drbrain\.info|nevvsletter\.com|smtp-mailing\.ikoula\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(interzet\.ru|info-(internet-vision|defidujour)\.com|futurssofts\.eu|pacific\.net\.hk|commemail\.net|(news-)?grodeal\.fr)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(idealsempre\.com|programaparaenviomasivo\.com|archofficegroup\.|votreunivers\.fr|melaction\.eu|bwz\.se|tvldelvr\.info)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(promosender\.in|net-infos\.fr|wecall10(1|2)\.|gdw365\.fr|rpro247\.fr|mymarketing\.co\.il|rml247\.fr)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(rpm(l77|smtp|ail)\.fr|sudns\.com|chroniques-du-web\.com|rp365\.fr|sabelecuador\.com|qpro\.fr|votre-selection\.fr|melaction\.org)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(novaetapaworld\.com|eml1sender\.info|chroniques-du-net\.com|formatic2000\.net|reachyu-stratus\.com|discoverion\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(facemprofit\.ro|hab073\.eu|toservers\.com|worldwider\.in|ed-dir\.com|fichiers-b2c\.fr|tr-track2l\.com|mailingdental\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(fr-econoplus\.fr|marketingadm\.com|emstechnology(2)?\.net|koviser\.com|imv0(1|3)\.net|ordenadoresgolz\.com|melhordesempenho\.info)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(masterinform\.com|fvs699\.com|pur3\.net|mailchicken\.co\.za|chegouemail105\.info|infosoftwebmarketing\.net|index-m\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(unitead\.(eu|info)|culture-chronique\.com|eml89\.net|galdarie\.com|vendendosaude\.info|realgoodfriend\.info|traveldelux\.info)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(neosagon\.com|pflash019\.com|club-eco-net\.fr|commews\.net|we\.bs|ventasfullmail\.com|isave-solar\.com|jls593\.eu|eco-bravo\.fr)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(soler-future\.net|crsend\.com|cleverreach\.com|le-clic-fute\.fr|dumanajans\.com|kupondiszkont\.com|mercadeoeficaz-cr\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(midiamail\.net|info01pro10\.info|sentyou\.net|sauti\.in|hen660\.info|sndamerica\.com|surfmailpro\.in|mailingtime\.org)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(ec0budget\.fr|super-club-eco\.fr|bca979\.eu|mediamaster-pt\.com|yoursavings\.us|infodivio\.com|pubprosud\.fr|selectionperso\.fr)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(https443\.org|yzqji\.net|ecomut-info\.fr|presente\.co\.in|gefirerak\.com|homelan\.bg|echhar\.com|piramkt\.net|osimail4\.us)$',
        hostname)
    if matches: return True
    matches = re.search('\.(ns(382514|382664|3292287)\.ovh\.net|decideurs-entreprises\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(newsale\.me|super-eco-club\.fr|tivipro\.tv|direct-solar\.net|enviobr\.com|dedeogluhost\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(fmo4[01234]|tr1-tr2l\.com|mailservisim\.info|thesecretdeal\.info|maxxglobal\.com|mailingmilenium\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(lojapremiumcontas\.com|sinoedm\.net|brukodan\.net|businessmailers\.biz|jba085\.com|crmercadeoonline\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(xtf184\.eu|libredeal\.ma|directproprietar\.info|programaparaenviomasivo\.net|buzinessethics\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(luckml\.com|op3out\.com|homeinfoam\.com|mohamed-nagah\.com|ec0discount\.fr|eco-info-direct\.fr|gardenll\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(promomailmarket\.com|tillions\.com|ispfr\.net|fr-ecoplus\.fr|anunturidemicapublicitate\.ro|salamnews\.org)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(plataformas-privadas\.com|gomedia\.co|softmailmarket[0-9]*\.com|affaire-du-mois\.fr|rezultavka\.com|offersb\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(sakura\.ne\.jp|precisomail\.net|bcf785\.eu|f-tradsmith\.com|emailing-consulting\.com|heberjahiz\.com|tr-so\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.([a-z][0-9]*-(losangeles|gauteng|osakakyoto|b148-tokyo.eu)\.(fr|eu)|mxserver\.ro|dmdelivery\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(upcontact\.com|upcrt109\.com|intranet-enterprise\.com|rundare\.com|promoserious\.com|aok835\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(onlinehome-server\.info|hartamasedm\.com|planosmarketing\.com|marketselectsa\.info|pro-smtp\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(ody[a-z][0-9]\.com|abnews\.org|marketingtht\.com|loyaltycampaign\.com|agencemd\.(fr|com)|marishas\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(communicatoremail\.com|(emu|oel)[0-9]{3}\.eu|casabill|fr-ecobudget\.fr|ocfm4[123]\.com|ylh677\.eu|u766-gauteng\.eu)$',hostname)
    if matches: return True
    matches = re.search('\.(thestmp\.com|s-ingress\.com|icontact\.vn|ecta113\.info|vmleg\.com|econolaser\.com|dolcelife\.fr|affairedujour\.info)$',hostname)
    if matches: return True
    matches = re.search('\.(magvision\.com|cartouches-en-stock\.com|aprendiendomas\.com|fr-consofrance\.fr|dolce-life\.fr|serverout\.com)$',hostname)
    if matches: return True
    matches = re.search('\.(bestof-du-web\.com|mktg-(cirrostratus|cloud|venus)\.com|citizenkane-mail\.com|sgccommunications\.net|rockgrill\.org)$',hostname)
    if matches: return True
    matches = re.search('\.(efenv(2)?\.com|lagserv\.info|bhallinfo\.com|zonaventascr\.com|stonesoverthecliff\.biz|e-dbasemarketing\.com)$',hostname)
    if matches: return True
    matches = re.search(
        '\.(rte\.fr|hipermailing\.com|switchcucumber\.biz|spmlr\.com|emailunlimited-online\.net|sabren\.com|paderka\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(mktmail01\.com|receiptstain\.biz|saleuniversal\.com|atticjudo\.biz|pillowsalary\.biz|poisonpumpkin\.biz)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(fichier-adresses-mail\.net|lindeika\.com|fm0[1-6]\.eu|quadeli\.net|dns\.com\.cn|scairox\.us)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(emthtpmy1\.net|mailbus\.cc|dptagent\.net|falconfinancing\.com|blueboxgrid\.com|anothelen\.com|teleproza\.net)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(bibsugar\.biz|lemonreward\.biz|cashmerethinker\.com|crystals-and-gemstones\.com|wholeclic\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(green-fuji\.com|vml-pml\.com|uglyboogly\.com|fr-ecopoly\.fr|qbusanrante\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(danhbathuonghieu\.vn|tiny(hungry|general)\.biz|simdeve\.us|inprocr\.info|conteudoinformativo\.com|cdmc\.fr)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(vrpvirtuel\.com|mawebox\.com|otake\.fr|distribe\.com|ags-groupe\.fr|climprimerie\.eu|powersmtp\.in)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(elb-media\.com|embluejet\.com|ybox\.fr|1001-a\.ro|diverseafaceri\.ro|neolink-perf\.com|jxq265\.eu)$',
        hostname)
    if matches: return True
    matches = re.search('\.(smartemail24\.com|lesmails\.info|cn4e\.com|ehoo\.info|vgol606\.com|fast-business\.ro|green-ad\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(blastofftoday\.pw|partylikearockstar\.pw|power-smtp\.info|wess-media\.com|ajansfatanitim\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(resolutionbig\.com|bodyclient\.com|hctlhasu\.com|en25\.com|emkt-direct\.biz|md-connecting\.com|atitok\.eu)$',hostname)
    if matches: return True
    matches = re.search(
        '\.(weservit\.nl|la-souris-futee\.fr|cognix-systems\.net|club-info-du-mois\.fr|topregionfr\.com|info-events\.fr)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(ecofrance-eco\.fr|turkrdns\.com|xmailsender\.com|underdc\.net|envio76\.com|mackdelf\.us|top-lan101\.com)$',
        hostname)
    if matches: return True
    matches = re.search('\.(mailsolo\.eu|ns383282\.ip-94-23-251\.eu|qualityenvio\.info|secureserver\.net|nexxt\.info)$',
                        hostname)
    if matches: return True
    matches = re.search(
        '\.(sendmkt\.cu\.cc|lemondedu-shopping\.com|euromsg\.net|ip-pool\.com|clues\.ro|queryfoundry\.net|MeltingWordsPC)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(interbat\.com|as42926\.net|ocnm61\.com|hostnoc\.net|sightreview\.com|adr-ol-school\.com|topmomed\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(securehostdns\.com|signals\.fr|scalabledns\.com|mlsend2\.com|steadyenglish\.com|canal-vip\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(stratoserver\.net|gonard-press\.fr|avvdmail\.org|planosweb\.info|rsvpsv\.net|emailmanager\.com|colocrossing\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(fichiers-adresses-mails\.net|grupointerspire\.info|cbanner3\.info|nksmtp[1-9]*.com|uploadsunucu\.com)$',
        hostname)
    if matches: return True
    matches = re.search('\.(periodicnetwork\.com|career-email\.info|atendvamt\.info|sanalofisreklam\.info)$', hostname)
    if matches: return True
    matches = re.search('\.(inter-nautes\.com|rejorai\.com|server2silhouette\.info)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(les-violettes\.com|opencamp\.fr|rus-costa\.com|comcastbusiness\.net|mailclickmarketing\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(efectienvio\.com|topserver1\.com|snakecontact\.com|rsgsv\.net|(rw)chservidor\.com|viettel\.vn)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(vphservidor\.com|toplumailhizmeti\.com|mailkitchen\.com|windowspecialist[0-9]*\.us|isvtec\.net)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(supereficaz\.info|ueberiblick\.com|croatia-networks\.com|emarketingdns\.com|serverbrsp\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(serverbrsp\.com|mailing-srv-[0-9]*.com|us-commerce-help\.biz|turiglobal\.net)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(node[0-9]*-priorityhosting\.com|aprenderparatrabajar\.com|elb\.comalis\.net|lamejorformacion\.com)$',
        hostname)
    if matches: return True
    matches = re.search('\.(parisolde\.com|cont\.ro|mxwse\.fr)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(trsonlinehostingsolutions\.com|btcentralplus\.com|simpel-mailinglijst\.com|superslickydeals\.com)$',
        hostname)
    if matches: return True
    matches = re.search('\.(email-master\.net|cavesdebordeaux\.com|itarget\.vn|emaili\.biz|place-des-affaires\.net)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(toprem\.com|xclv\.net|vcc-simplehosting[0-9]*\.us|trsonlinemediav-[0-9]*\.us|yeah\.net)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(ahobaviagens\.tur\.br|turbo-smtp\.net|compbox\.biz|championleads\.info|mysecretfb\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(oshabasics\.com|mmstools\.asia|smtp\.com|mon-bonplan\.fr|lucklytradee\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(targetmail\.ro|praktikustermekek\.com|qumana\.net|bccdui\.com|dcdcapital\.com|steptwin\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(alotaman\.com|amelab\.com|devtravels\.asia|simulacao(001)?\.info|charter\.com|allkabum\.net)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(e-zine-factory\.com|toplumailreklam\.info|inmotionhosting\.com|500bonsplans\.com|ax0[0-9]+\.fr)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(emailpal\.biz|igbn73\.eu|mschosting\.com|performemymail\.net|servifast\.info|dejedevender\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(gonard-rp\.fr|maililetisim\.com|mailingon\.com|iranweblogs\.ir|entregainmediata\.net|imprimissimo\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(maxw\.info|sd-france\.net|dvmta\.info|celularcomercial\.net|onebrserver\.com|cre-finance\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(scoop-achat\.com|emarketingsa\.co\.za|nationalcablenetworks\.ru|supermailsend\.com|monopost\.com)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(sup2deal\.com|yourhostingaccount\.com|vpsgroups\.us|wpengine\.com|iol\.cz|top-du-jour\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(3qbo\.net|bradsweb\.info|routedudeal\.com|less-coupon\.eu|emc4v1\.com)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(mkrapido\.com|argeser\.info|formacion-vip\.com|informesaude\.info|ksc\.net|smtpauthorized\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(track-marketing\.com|datacom\.net\.au|accessdomain\.com|250serveurenvoi\.info)$', hostname)
    if matches: return True
    matches = re.search('\.(ni\.net\.tr|doulcam\.com|steadfastdns\.net|arvixevps\.com|crmhiper\.com|bltncr\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(nodo50\.org|site-mania\.net|vanzariatxcomputers\.ro|techvelux\.com|nantimantos\.com)$',
                        hostname)
    if matches: return True
    matches = re.search(
        '\.(greatnightsrest01\.us|thinkaboutyourcredit04\.us|vegiaitri\.(vn|info|net)|evucz\.com|ipteen\.co)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(undercloud\.net|celektronmx\.com|espacalamar\.com|technomegas\.com|ktp\.net|adosme\.co|hostam\.info)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(justox\.com|zoophalupa\.com|contagalatix\.com|codero\.net|crm-erp\.net|congr2014\.info|bvmta\.info)$',
        hostname)
    if matches: return True
    matches = re.search('\.(server10\.biz|feel-thevibe\.com|tarevqua\.com|cwbleads\.info|10030\.info|bltnsti\.com)$',
                        hostname)
    if matches: return True
    matches = re.search(
        '\.(informeplanos[0-6]*.info|mailingon\.net|syedfaisal\.org|salesmanago\.pl|amdserver\.org|toledo\.br)$',
        hostname)
    if matches: return True
    matches = re.search(
        '\.(wtrregister\.com|austin-power\.eu|shared-server\.net|faronray\.com|omurtech\.com|crhdllc\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(entregascr\.com|hostsb\.info|va-solutions\.fr|dart-eshot\.com|bxmg\.info)$', hostname)
    if matches: return True
    matches = re.search('\.(qualyofertas\.info|estacaocursos\.in|a6telecom\.fr|hostma\.info|sidoumail\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(franquiaahobaviagens\.tur\.br|traditiondesvosges\.eu|spiremails\.net|inovacursos\.in)$',
                        hostname)
    if matches: return True
    matches = re.search(
        '\.(iprimus\.net\.au|mentorhealth\.com|oxsendgo\.com|vision5\.biz|ipirangahost\.com|2727836\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(prymelead\.info|spiremail\.net|lionleads\.info|e-adsonweb\.com|sirketbul\.org)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(uv817\.com|cobithost\.net|emarsys\.net|mandikhost\.com|planos-especiais\.com|freshmail\.pl)$', hostname)
    if matches: return True
    matches = re.search('\.(acquathermas\.net|vins-(dexcellence|extras|de-folie|les-caves)\.(com|fr)|setrow\.com)$',
                        hostname)
    if matches: return True
    matches = re.search(
        '\.(parfum-de-charme\.fr|large-choix-de-vin\.fr|omdajo\.com|powermta\.pl|ecommerce-hosting\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.((selections|fabuleux|super|grandissimes)-?vins\.(com|fr)|hostsmtp\.pl|ex2-p11\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(hosteur\.com|iqelite\.com|mf4\.me|cloudsigma\.com|lightdeliver\.com|egitimicerik\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(firstheberg\.net|e-international33\.com|pufeby\.com|rediffmail\.com|probeens\.com)$',
                        hostname)
    if matches: return True
    whywhy = "Line 1121"
    matches = re.search(
        '\.(axmedias\.com|precoequalidade\.com|duyuruonline\.com|easysend-web\.com|efecti-contactocr\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(8800\.org|sadecehosting\.net|clicaserver.*\.biz|ymlps(r)?v\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(nusindo\.co\.id|gob\.pe|efecticontacto\.com|bestofmarket\.hu|em0[23]\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(stellarinfo\.com|bnhost\.pl|relaymta\.net|opaltelecom\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(xobnicampaigns\.co|tvmc\.info|crmbulten\.com|ameateconme\.info|avantagedujour\.be)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(scientificevents\.info|opennews\.fr|r2jmarketing\.fr|r2jcompany\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(cdspid\.pl|mktdigitalvale\.eti\.br|xnanomailing\.com|nuvox\.net|presenteslindos\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(kazintercom\.kz|wolfcontact\.com|relais-thr\.com|icu\.ac\.kr)$', hostname)
    if matches: return True
    matches = re.search('\.(bestplan\.fr|niceinfo\.net|spaandbodyculture\.com|engineonline\.net|directbite\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(ofeet\.com|nwdbtscdls2702\.com|targetmail-news\.com|198\.101\.8\.121)$', hostname)
    if matches: return True
    matches = re.search('\.(pro-healthlife\.com|zerobleu\.com|gapzo\.com|profilapprouvee\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(mailscale1021\.ru|sendtome\.fr|nuranozsozhaber\.com|bul21\.com|clubdesmarchands\.fr)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(mailengine[0-9]+\.com|info-emailer\.com|axmr\.fr|chro\.ro)$', hostname)
    if matches: return True
    matches = re.search('\.(flipmailer\.com|pourvousfaireconnaitre\.com|order-vault\.net|turbo-smtp\.org)$', hostname)
    if matches: return True
    matches = re.search('\.(cjspid\.pl|gregory-auguste\.fr|m2-out\.com|vtr\.net|dryface\.net|bateriamax4\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(canalnews-vip\.com|al3s2\.info|shsend\.com|dmdata\.ro|milliondollare47\.co\.vu)$', hostname)
    if matches: return True
    matches = re.search('\.(boletines-cr\.com|kitsend\.ga|cpnew\.info|fiberstorm\.net|assistenciamedica\.info)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(revtacli\.com|votre-detente\.com|oceaniatravel\.ro)$', hostname)
    if matches: return True
    matches = re.search('\.(bghost\.pl|abac\.net|oticari\.ga|agava\.net|grand-j\.eu|centertel\.pl)$', hostname)
    if matches: return True
    matches = re.search(
        '\.(proreff\.com|123-top-mailing\.com|mailingconcept\.com|emailvoox\.com|enfocosnoticias\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(mailgm\.com|ibratim\.com|submail\.cn|canal-notification\.com|funlabeurope\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(ebhost9\.com|send-server\.com|planosdeenvio\.com|gcpseminars\.com|cheesemailer\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(publi-online\.net|dedicadohost\.net|mameilleureselection\.com|listingdomainreg\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(boletinmailinkcr\.com|ventasinfo\.com|rol\.ro|jungleinc\.net|reachextended\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(accessstars\.com|iktomi\.fr|news-diffusion-[0-9]+.fr|sohu\.com|asmidia\.com|prwah\.net)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(negocios-inteligentes\.info|mailservercr\.com|news-kingmailing\.com|emsmtp\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(salezeo\.com|oksenddirect\.info|corporate-mail-sp\.com|mysmtp\.mobi|ejecom\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(nossoproduto\.com|emailsolar\.be|e-nautia\.com|mailing-sol-[0-9]+-[0-9]+.com)$', hostname)
    if matches: return True
    matches = re.search('\.(informationtilldig\.net|relais[0-9]+mail\.com|192\.254\.74\.11(2|3))$', hostname)
    if matches: return True
    matches = re.search('\.(chickenkiller\.com|notificador\.net|enviocr\.com|mailing-sol-trh\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(foroabogados\.ec|softlayer\.com|sendfree\.fr|uaedubaimarketing\.com|dfdbulb\.pl)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(chtah\.net|trackdp\.com|stringsending\.biz|case-mails\.com|avenidabrasilfm\.net)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(multi-lead\.org|dispatchemails\.org|casaplan\.me|wincamp\.fr|port25\.pl|mta1\.net)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(fly-c2\.com|websitewelcome\.com|ml127ura\.fr|emailmidiaserver\.com|serverprofi24\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(pretorian\.cl|boletines-pymes\.com|atomiclayer\.com|forwardemails\.biz)$', hostname)
    if matches: return True
    matches = re.search('\.(odiso\.net|barmaillist\.org|myhostcenter\.com|comunicadoscostarica\.com|fhnet\.fr)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(cloudapp\.net|rambler\.ru|apostasena\.com|easysending\.biz|transmitmail\.biz)$', hostname)
    if matches: return True
    matches = re.search('\.(marketing-francais\.com|mediazone-befr\.com|xobnicampaigns\.net|tradetracker\.net)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(netvigator\.com|nadi7yalgat\.com|bernama\.com|bonpouraccor\.|thor1\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(banderoleecomail\.com|forwardmaillist\.com|755507\.com|pharmalat\.com\.gt)$', hostname)
    if matches: return True
    matches = re.search('\.(grizli\.ml|jerusalemarcaeology\.com|diffusions28\.com|ist\.lt|newspartner[0-9]*\.fr)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(abc[0-9]+mail\.com|buzo\.cc|mumin1\.net|sevenjo7sevenjo\.com|jimprimecommejaime\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(ofnm\.fr|ods(3|4)\.net|vertigocreativo\.info|ddns\.net|plan-du\.in|noticia\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(comoganhardinheirodn\.com|serverlet\.com|anbmkt\.net|kubaturseyahat\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(probif\.com|leclerc-ads\.com|sexy-discount\.ch|rootmaster\.info|picsrv\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(acropolistelecom\.net|fichiers-prospection.*\.fr|diffusions-aout\.com|sbase2\.se)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(doobez\.com|dnsprotect\.com|growmail\.co\.uk|econnectee\.com|emk[0-9]+\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(vietbrandname\.com|publienv\.com|mailgostarco\.com|mailmass[0-9]*\.ru)$', hostname)
    if matches: return True
    matches = re.search('\.(comunicados-cr\.com|mailing-bin-okt\.com|sogedev\.com|eurowh\.com|boletinmailink\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(mailbanderolepro\.com|relay-se1\.net|mcontact\.it|edmpoint\.com|suvinca\.gob\.ve)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(ddns\.me|investmentsend\.info|boletincostarica\.com|rspmail-apn[0-9]*\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(dotmailer\.com|e-reklam\.org|mdlnk\.se|spready\.com|izeecloud\.com|irtaglobal\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(dmmail\.se|mout\.kundenserver\.de|kingstar14\.eu|tikoo14\.eu)$', hostname)
    if matches: return True
    matches = re.search('\.(bodhost\.pl|rp[0-9]+.net|clickpays\.net|comverter\.fr|bursadecontabilitate\.ro)$', hostname)
    if matches: return True
    matches = re.search('\.(cible-b2c\.fr|cargorapido\.com|smtppakistan\.com|muziklidavetiye\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(nayloncumail\.com|goguavamedia\.com|est-le-patron\.com|fichiersemails\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(creasoft51\.com|sending2lm\.pro|authoring\.fr|abplusz\.hu|lws-hosting\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(happymail\.vn|duyuruetkinlik\.com|ahdsend\.pl|e-marketinglocal\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(mktvietsun\.com|perfora\.net|cnode\.io|216\.158\.85\.34)$', hostname)
    if matches: return True
    matches = re.search('\.(deliverabilitymanager\.net|telenor\.se|unifiedlayer\.com|mta3\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(icpbounce\.com|proyag\.com|somenteagora\.com|mxlogic\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(serviciosib\.info|mcsv\.net|exactcc\.ro|forpsi\.net|b2bnewseveryday\.com|envmas\.com)$',
                        hostname)
    if matches: return True
    matches = re.search('\.(odvn\.vn|yeni34\.com|fotoramkiforyou\.ru|ono\.com|mrbasic\.com|mundivia\.es)$', hostname)
    if matches: return True
    matches = re.search('\.(ibonline\.info|mchsi\.com|leadpen\.fr|aidsend\.pl|info-entreprise\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(newsgratos\.com|bigpond\.com|firat\.edu\.tr|cooperate-with\.xyz)$', hostname)
    if matches: return True
    matches = re.search('\.(achat-base-email\.fr|freanky\.com|sofialambert\.ru|aliyun\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(modulosolar\.com\.mx|emailsrvr\.com|minorisa\.net|naomi485\.eu|solluces\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(163data\.com\.cn|labelleaffaire\.fr|triolan\.net\.ua|ventasymarketing\.email)$', hostname)
    if matches: return True
    matches = re.search('\.(envcr\.com|proposta\.work|contabo\.host|realmktsolutions\.com|fanbridge\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(gcmworldwidegroupasia\.info|direct-fournitures\.com|globalpromo\.fr|mysmtp\.com)$',
                        hostname)
    if matches: return True
    whywhy = "Line 1290"
    matches = re.search('\.(out01\.smtpout\.orange\.fr|go\.id|wwi\.dk|zirvebilgi\.com|epmailer\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(inbox2informatinos\.xyz|zirvebilgim\.com|hostyfy\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(envcostarica\.com|nl2go\.com|is-a-cpa\.com|macespeakers\.(se|com)|pochta\.ru)$', hostname)
    if matches: return True
    matches = re.search('\.(saigoninbox\.com|gnet\.tn|digitalmailmx\.com|swishmail\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(unmul\.ac\.id|wikaba\.com|aproxi\.info|erdemdilhizmetleri\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(active24\.cz|beneficiosplus\.com|valuezon\.com|geniusort\.ru)$', hostname)
    if matches: return True
    matches = re.search('\.(avo\.ru|duyuru1\.com|mmm\.it|aaamercadeo\.net|securenewsmta\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(marketingmasivocr\.com|bmsend\.com|getmagine\.com|85\.25\.199\.170)$', hostname)
    if matches: return True
    matches = re.search('\.(daum\.net|xqueue\.com|prowog\.com|geniusmailing\.ru)$', hostname)
    if matches: return True
    matches = re.search('\.(obteniendolo\.com|dichvuthudientu\.biz|dentexia\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(ebrdirectory\.com|pro-smtp\.co\.za|onlinehome-server\.com|babbleism\.pl)$', hostname)
    if matches: return True
    matches = re.search('\.(fastwebnet\.it|zml\.fr|suivi-mailing\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(hostiran\.name|smtp\.rentals)$', hostname)
    if matches: return True
    matches = re.search('\.(openlandvn\.com|vietnamvps\.com|mailtuat\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(ilan-1\.com|informacionvalida\.com|info-viptik\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(vosnews-6\.es|centromatriz\.com|tipimail\.com|realnet\.co\.sz)$', hostname)
    if matches: return True
    matches = re.search('\.(smtp589\.com|vnpt\.vn|email-com\.net|c4fsoft\.biz|netgocio\.pt)$', hostname)
    if matches: return True
    matches = re.search('\.(serveurs-mail\.net|dattaweb\.com|3plog\.net|openvps\.info|aeromaritimo\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(meilleur-coupon\.com|flash-de-ventes\.com|emailservername\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(comparer-les-placements\.com)$', hostname)
    whywhy = "Line 1330"
    if matches: return True
    matches = re.search('\.(sendwizz\.com|embestway\.com|redirex-10\.com|irrsel\.com|sendtk2\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(rgp-enquete\.com|oakwood\.org|superdata\.vn|icontact\.com\.vn|singnet\.com\.sg)$', hostname)
    if matches: return True
    matches = re.search('\.(rgp-shopping\.com|liberynws\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(syrahost\.com|messagelabs\.com|emsd8\.com|sendtck1\.com|sendck3\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(hyytxb\.com|corporates-fraternity\.com|app-2x\.com|investsaigon\.com|axe-net\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(lideedujour\.fr|tienganh24h\.|wahlglobal\.com|bongdaso\.online|(news|info-enquete)40\.fr)$',hostname)
    if matches: return True
    matches = re.search('\.(vingroupmiennam\.net|maserativn\.info|postacionline\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(postamonline\.com|lespromosduweb\.be|marketingtoucan\.co\.uk|infoadomos\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(club-vente-privee\.com|unlp\.edu\.ar|uninet-ide\.com\.mx)$', hostname)
    if matches: return True
    matches = re.search('\.(biz\.net\.id|evanzo-server\.de|netangels\.ru|efficient-mail\.link)$', hostname)
    if matches: return True
    matches = re.search('\.(smart-?e?mail-?data\.site|news-enquete40\.fr|dataagreaabil\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(nalrabatten\.se|nitech\.ro)$', hostname)
    if matches: return True
    matches = re.search('\.((-marketing|mailexpress)\.click|mailerbulk\.info|send3214\.co\.uk)$', hostname)
    if matches: return True
    matches = re.search('\.(dream\.io|consultorpc.\com|cantv\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(photobox\.(com|fr)|wicam\.com\.kh|euronotification\.com|activesoft\.ro)$', hostname)
    if matches: return True
    matches = re.search('\.(arobases\.fr|culture-chronique\.com|telmexla\.net\.co|mailp01\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(opentransfer\.com|txmsv\.com|mta4\.net|emailmarketingpromos\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(bietthubiennova\.info|emailcskh\.com|offre-les-caves\.fr|alkipage\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(aqua-luxury\.info|mktdns\.com|proleeg\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(indosat\.net\.id|expert-ar3f\.com|linksvr100\.com|accessiblediffusion\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(enews\.click|webhostbox\.net|wiseup\.com|jeantet\.fr|is-certified\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(bgzcltsm\.com|nobopredun\.fr|smtp9873\.com|vps-10\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(mochahost\.com|htt\.co\.kr)$', hostname)
    if matches: return True
    matches = re.search('\.(wow\.lk|singaporeinbox\.com|162\.244\.12\.|epsismtp\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(epsitracking\.com|mysmtp3\.com|virginie\.cf|mexi-aventure\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(\.website|konferansbilgi\.site|mailchannels\.net|nguyenthingocgiau\.com\.vn)$', hostname)
    if matches: return True
    matches = re.search('\.(104\.129\.56\.181\|correio\.biz|phos\.com\.ec)$', hostname)
    if matches: return True
    matches = re.search('\.(belacom\.fr|offresautop\.fr|marketing-alfa\.ru)$', hostname)
    if matches: return True
    matches = re.search('\.(bonplansdujour\.in|differenthighlight\.com|pthread\.org)$', hostname)
    if matches: return True
    matches = re.search('\.(anteldata\.net\.uy|mittsup\.com|zirvebilgi\.site|mtw\.ru)$', hostname)
    if matches: return True
    matches = re.search('\.(vuduydong\.name\.vn|opendigital(3|5)\.com|superchamp\.fr|konferansinfo\.club)$', hostname)
    if matches: return True
    matches = re.search('\.(mta-server5(2|9)\.com|severleadsm\.co|serwervps\.pl|regnetwork8854\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(endres-gmbh\.top|ewtqdtx\.tk|ssxaphbz\.tk|regrdfvte\.us)$', hostname)
    if matches: return True
    matches = re.search('\.(eokaiy\.tk|topvinsetchamp\.fr|codejock\.com|emirates\.net\.ae|gazseti\.ru)$', hostname)
    if matches: return True
    matches = re.search('\.(searchnet\.org|mktomail\.com|solutionpourlespros\.net|cdesigns\.org|cdcard\.org)$',hostname)
    if matches: return True
    matches = re.search('\.(masmaestria\.com|net\.vn|retarus\.com|universidad-oficial\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(prokuv\.com|postgradoonline\.com|ghst\.net|ip-[0-5]+-[0-5]+-[0-5]+\.eu)$', hostname)
    if matches: return True
    matches = re.search('\.(gamma-mta\.com|frstore\.fr|tpm02\.net|emailmarketing-tools\.info|kfmm\.org)$', hostname)
    if matches: return True
    matches = re.search('\.(smip-beta\.com|revenew\.nl|adviceforme\.net|labelmail\.fr|mindenaneten\.hu)$', hostname)
    if matches: return True
    matches = re.search('\.(morefuncougar\.com|host-h\.net|placermonepargne\.com|magiris\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(aimcom\.org|accessplus\.fr|winoc\.org|itir\.org|mailin\.fr|aecus\.org)$', hostname)
    if matches: return True
    matches = re.search('\.(superbe-adulterines\.com|nbbq\.org|phlfound\.org|theflats\.org|perlamail\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(bmaxcare\.com|tanzaniaports\.com|chooseyourboss\.com|tanzaniaports\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(telkom\.net|connecteedflux\.com|etkinlikhaber\.site|fr-mail\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(mindgrid\.org|giaremoiluc\.net|ptempresas\.pt|cloud\.z\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(ninhthuansoft\.com\.vn|nytcollege\.com|taopen\.eu|thememorablepeople\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(opendigital9\.com|bizimagewebdesign\.co\.uk|yahoo\.co\.jp|comdigicom\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(212medya\.com|ithardwarex\.ro|capaweb\.org|bulk\.vn|mediasoft4u\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(volz-gmbh\.info|smrtmsg\.com|thehost\.com\.ua|proqaz\.com|emailctgroup1\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(rrgallery\.com\.mx|postier\.site|consofirst\.fr|netart\.pl|avantel\.net\.mx)$', hostname)
    if matches: return True
    matches = re.search('\.(emailopendigital\.com|bagsnzz\.com|topenvoi\.site|123mailing\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(mailgun\.us|blogsbusiness\.org|emailengine\.com|routages-pros\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(marketingentreprises\.services|emarketingworks\.net|sirajhost\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(rzone\.de|vnemarketing\.net|contacts4business\.com|e-combox\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(gansend5\.com|akreditifuygulamasi\.com|envoicamp\.site|vnemarketing\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(malazlar\.com|walla\.co\.il|batelco\.com\.bh)$', hostname)
    if matches: return True
    matches = re.search('\.(hosteur\.net|kratos\.net\.ua|ods2\.net|maximumasp\.com|zzux\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(mail-carrier\.com|echos-news\.fr|fgbnet\.com|aabbcnews\.com|bilensoft\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(hitelplus\.com|codiec\.bid|expcougar\.com|\.trade|sbr[0-9]*\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(emailingmpw\.eu|leadfox\.co|incrm\.net\.br|shopintimate\.com|\.bid|\.stream)$', hostname)
    if matches: return True
    matches = re.search('\.(thewrestlingshop\.com|formatmix\.fr|laplacedesbitcoins\.com|bnpi\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(notificacione\.com|takenes\.eu|canhovinsg\.com|welcomewin\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(silhouetteinternational\.info|consultorpc\.com|couponmarketsystem\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(aexpertys\.com|quedebons\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(huron769\.com|maximusone\.com|threadloom\.news|lr002\.net|forcemktg\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(waitg-0g\.com|madecaindonesia\.com|bacheetpanenau\.fr|infusionmail\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(wefightfat\.org|sabirsut\.com|sudiplomadoenlinea\.com|curiouswebdesign\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(aproximeo\.be|ohayvay\.com|pharmastoreline\.com|djv-com\.net)$', hostname)
    if matches: return True
    matches = re.search('\.(elmejormaestroenlinea\.com|mauritiusinabox\.com|mail-string\.online)$', hostname)
    if matches: return True
    matches = re.search('\.(smartbazar\.eu|smart-news\.fr|backash\.de|takecheapgeo\.de)$', hostname)
    if matches: return True
    matches = re.search('\.(newlifestyleguide\.com|inforh\.pt|tele-coach\.fr|joliesfleursdanslepre\.local)$', hostname)
    if matches: return True
    matches = re.search('\.(easymailsolutions\.com|ma-plateforme-vtc\.com|infos-capitalya\.eu|wx01\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(worldclassministries\.com|infoprodata\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(premierjourdessoldes\.be|\.date|\.win|\.review|cerisoft\.re)$', hostname)
    if matches: return True
    matches = re.search('\.(eonconsultores\.org|wr02\.(net|eu))$', hostname)
    if matches: return True
    matches = re.search('\.(familyroomdecor\.com|alabtekar\.com)$', hostname)
    if matches: return True
    matches = re.search('\.(veryfresh\.eu|mailpower\.eu|investirresidenceetudiants\.fr)$', hostname)
    if matches: return True
    matches = re.search('\.(emsd2\.com|w(m|p)0(1|2)\.(eu|fr|org)|contact-mkg\.fr|etsnews\.eu)$', hostname)
    if matches: return True
    matches = re.search('\.(francephi\.com|dlems[0-9]\.fr|mcdlv\.net|msems[0-9]\.net|wp0[0-9]\.net|wx0[0-9]\.eu)$',hostname)
    if matches: return True
    matches = re.search('dedicated-aim[0-9]+\.rev\.nazwa\.pl', hostname)
    whywhy = "Line 1491"
    if matches: return True
    matches = re.search('ppp-[0-9\.\-]+\.nsk\.rt\.ru', hostname)
    if matches: return True
    matches = re.search('roterdao[0-9]+\.bikebbc\.com$', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.(pw-ivai\.ibys\.com\.br|entelchile\.net|pool\.ovpn\.com|static\.lyse\.net|\.rev\.datatower\.net|altel\.kz|fix\.netvision\.net\.il)$',hostname)
    if matches: return True
    matches = re.search('dslc-[0-9\.\-]+\.pools\.arcor-ip\.net', hostname)
    if matches: return True
    matches = re.search('cbo-[0-9\.\-]+\.cbo\.ras\.cantv\.net', hostname)
    if matches: return True
    matches = re.search('\.cloud\.flynet\.pro', hostname)
    if matches: return True
    matches = re.search('-smile\.com\.bd', hostname)
    if matches: return True
    whywhy = "Line 1507"
    matches = re.search('[a-z][a-z][a-z]-[0-9]+-[0-9]+-[a-z]+-cli\.une\.net\.co', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(macronet\.cz|cpe\.cableonda\.net|pool\.ovpn\.com|kpn-gprs\.nl|spbmts\ru|dsl\.sura\.ru|unity-media\.net|pel\.cz|iconnect\.zm|assim\.net|redecompunet\.com\.br|mail\.artfmproduction\.com|static\.oxid\.cz|inetcom\.ru)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(res-cmts\.hzl2\.ptd\.net|lnk\.telstra\.net|comnet\.bg|itcsa\.net|unknown\.m1\.com\.sg|static\.astinet\.telkom\.net\.id)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(arsim\.one\.pl|mailhostbox\.com|shirazhamyar\.ir|robi\.com\.bd|bestel\.com\.mx|reserved\.voxility\.com|static\.albacom\.net)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(customer\.tdatabrasil\.net\.br|static\.anycast\.cnt-grms\.ec|planaltonet\.net\.br|exe-net\.net|global63\.net|dts\.mg|redfoxtelecom\.com\.br)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(3g\.claro\.net\.br|untc\.net|nzcomms\.co\.nz|ip\.airmobile\.co\.za|net\.internetunion\.pl|hsi5\.kabel-badenwuerttemberg\.de|ibercom\.com)$',hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(mobilinkinfinity\.net\.pk|kcell\.kz|telkomsa\.net|v4\.ngi\.it|flashcable\.ch|tempusnet\.com\.py|klimovsk\.net|retail\.ttk\.ru|fornex\.org|parsonline\.net|digitalnet\.co\.hu|in-addr\.arpa\.celeste\.fr)$', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+(static\.metrored\.net\.mx|rev\.cloudwatt\.com|apn\.mobile\.telkomsa\.net|static\.ziggozakelijk\.nl|xnet\.hr|revip\.asianet\.co\.th|in-addr\.btcentralplus\.com|wilnet\.com\.ar|fahrenwal\.de|nile-online\.net|adsl-dyn\.4u\.com\.gh|host\.uzzy\.com\.br|wananchi\.com|as[0-9]+\.net|mobile\.dk\.customer\.tdc\.net|dyn\.cable\.fcom\.ch|afbus-jhb\.activefibre\.co\.za)$',hostname)
    if matches: return True

    whywhy = "Line 1525"
    matches = re.search('[0-9\.\-]+-cg-nat\.san\.ru$', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+static\.(albacom\.net|ef-service\.nl)$', hostname)
    if matches: return True
    matches = re.search('^adsl\..*?\.vn$', hostname)
    if matches: return True
    matches = re.search('ip-[0-9\.\-]+\.sn[0-9]\.clouditalia.com', hostname)
    if matches: return True
    matches = re.search('mail\.static\.[0-9\.\-]+\.lr001.net', hostname)
    if matches: return True
    matches = re.search('dhcp-[0-9\.\-]+\.chello\.', hostname)
    if matches: return True
    matches = re.search('host[0-9\.\-]+\.iconnect\.zm', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.nat\.cwdc-cgn[0-9]+\.myaisfibre\.com', hostname)
    if matches: return True
    matches = re.search('host[0-9\.\-]+\.static\.arubacloud.', hostname)
    if matches: return True
    matches = re.search('host[0-9]+\.twko\.com\.ar', hostname)
    if matches: return True
    matches = re.search('\.lesmail\.world$', hostname)
    if matches: return True
    matches = re.search('vpn-port[0-9]+\.istv\.uz', hostname)
    if matches: return True

    whywhy = "Line 1550"
    matches = re.search('host-[0-9\.\-]+\.etisalat\.com\.eg', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.pg-nat-pool[0-9]+\.mts-nn\.ru', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.[0-9]+\.nat\.sila[0-9]+-cgn[0-9]+\.myaisfibre\.com', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.(in-addr\.ttk-su\.ru|cablevision\.net\.mx|mayaknet\.ru|home\.aster\.pl|nat\.highway\.telekom\.at)$',hostname)
    if matches: return True
    matches = re.search('adsl-[a-z][0-9\.\-]+\.t-com\.sk', hostname)
    if matches: return True
    matches = re.search('customers-[0-9\.\-]+\.kazintercom\.kz', hostname)
    if matches: return True
    matches = re.search('mta-[0-9\.\-]+\.nyc\.rr\.com', hostname)
    if matches: return True

    whywhy = "Line 1567"
    matches = re.search('^catv[0-9]+\.aikis\.or\.jp', hostname)
    if matches: return True
    matches = re.search('^vps\..*?\.kylos\.net\.pl', hostname)
    if matches: return True
    matches = re.search('^cgw-[0-9\.\-]+\.bbrtl\.time\.net\.my', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.(skybroadband|jks988)\.com', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.broadband\.progtech\.ru', hostname)
    if matches: return True
    matches = re.search('dsl-[0-9\.\-]+\.avtlg\.ru', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.connect\.netcom\.no', hostname)
    if matches: return True
    matches = re.search('\.dyn\.bashtel\.ru', hostname)
    if matches: return True
    matches = re.search('ip-[0-9\.\-]\.corp\.langate\.ua', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.(aamranetworks|wic-net)\.(com|cz)$', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.[a-z]+-[0-9\.\-]+\.customer\.static\.[a-z]+\.telefonica\.net', hostname)
    if matches: return True
    matches = re.search('PPPoE-.*?\.san\.ru', hostname)
    if matches: return True
    matches = re.search('.in-addr\.arpa$', hostname)
    if matches: return True
    matches = re.search('unused-.*?\.clara\.net', hostname)
    if matches: return True
    matches = re.search('-ip[0-9]+\.networx-bg\.com', hostname)
    if matches: return True
    matches = re.search('xtp-[0-9]+-ip[0-9]+\.atel76\.ru', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.broadband\.progtech\.ru', hostname)
    if matches: return True
    matches = re.search('mue-[0-9\.\-]+\.dsl\.tropolys\.de', hostname)
    if matches: return True
    matches = re.search('atl[0-9]+\.myeedom\.com', hostname)
    if matches: return True
    matches = re.search('vps[0-9]+\.lws-hosting\.com', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.rewacorp\.com', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.emome-ip\.hinet\.net', hostname)
    if matches: return True
    matches = re.search('host[0-9\.\-]+\.static\.arubacloud\.com', hostname)
    if matches: return True
    matches = re.search('edc0\.[a-z]+\.info', hostname)
    if matches: return True
    matches = re.search('cpe-[0-9\.\-]+\.static\.vic\.bigpond\.net\.au', hostname)
    if matches: return True
    matches = re.search('b-internet[0-9\.\-]+\.nsk\.sibirtelecom\.ru', hostname)
    if matches: return True
    matches = re.search('\.hdsl\.highway\.telekom\.at', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.mrse\.com\.ar', hostname)
    if matches: return True
    matches = re.search('user-[0-9\.\-]+\.inova\.net\.br', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.public\..*?\.myaisfibre\.com', hostname)
    if matches: return True
    matches = re.search('host-.*?.tedata.net', hostname)
    if matches: return True
    matches = re.search('probe[0-9]+\.onyphe\.io', hostname)
    if matches: return True
    matches = re.search('\.koala-srv-[0-9]+\.com', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.procono\.es', hostname)
    if matches: return True
    matches = re.search('host-[0-9\.\-]+\.static\.metrored\.net\.mx', hostname)
    if matches: return True
    matches = re.search('broadband-[0-9\.\-]+\.atc\.tvcom\.ru', hostname)
    if matches: return True
    matches = re.search('static-.*?.ipcom\.comunitel\.net', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+-bbc-dynamic\.kuzbass.net', hostname)
    if matches: return True
    matches = re.search('cpe-[0-9\.\-]+\.telecentro-reversos\.com\.ar', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.sta.broadband\.slt\.lk', hostname)
    if matches: return True
    matches = re.search('lvps[0-9\.\-]+\.dedicated\.hosteurope\.', hostname)
    if matches: return True
    matches = re.search('porta[0-9]+\.[a-z][0-9]+\.internettelecom\.com\.br', hostname)
    if matches: return True
    matches = re.search('IP-[0-9\.\-]+\.static\.fibrenoire\.ca', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.host\.ifxnetworks.com', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.warszawa\.vectranet\.pl', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.netfulltelecomunicacoes\.com\.br', hostname)
    if matches: return True
    matches = re.search('mta-.*?.sentinbox\.com', hostname)
    if matches: return True
    matches = re.search('static.[0-9\.]+\.tmg\.md', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.static\.rozabg\.com', hostname)
    if matches: return True
    matches = re.search('static-[0-9\.\-]+\.rdsnet.ro', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.xmaxtelecom\.com\.br', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]telecom\.com\.br', hostname)
    if matches: return True
    matches = re.search('djo-bw-dcomm[0-9]+\.ipive\.net', hostname)
    if matches: return True
    matches = re.search('static-[0-9\.\-]+\.globaltelecombr\.com\.br', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.resources\.indosat\.com', hostname)
    if matches: return True
    matches = re.search('ip[0-9]+-jotanet\.[a-z]+\.digi\.pl', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.uzpak\.uz', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.ctinets\.com', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.HINET-IP\.hinet\.net', hostname)
    if matches: return True
    matches = re.search('static-[0-9\.\-]+\.acelerate\.net', hostname)
    if matches: return True
    matches = re.search('\.bb\.sky\.com', hostname)
    if matches: return True
    matches = re.search('\.serverprofi[0-9]+\.eu', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+-static\.bbbell\.com', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.static\.ttnet\.com\.tr', hostname)
    if matches: return True
    matches = re.search('user-[0-9\.\-]+\.inova\.net\.br', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.inetehno\.md', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.hff\.mweb\.co\.za', hostname)
    if matches: return True
    matches = re.search('bband-dyn[0-9\.\-]+\t-com\.sk', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\cab\.prima\.com\.ar', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+dedicated\.4u\.com\.gh', hostname)
    if matches: return True
    matches = re.search('host-[0-9\.\-]+\mikrotti\.hu', hostname)
    if matches: return True
    matches = re.search('0-9\.\-]+\.enitel\.net\.ni', hostname)
    if matches: return True
    matches = re.search('cli-.*?\.wholesale\.adamo\.es', hostname)
    if matches: return True
    matches = re.search('adsl-[0-9\.\-]+\.tellas\.gr', hostname)
    if matches: return True
    matches = re.search('\.adsl-pool\.jlccptt\.net\.cn', hostname)
    if matches: return True
    matches = re.search('client-.*?\.imovil\.entelpcs\.cl', hostname)
    if matches: return True
    matches = re.search('ip-[0-9\.\-]+\.network\.lviv\.ua', hostname)
    if matches: return True
    matches = re.search('assigned-[0-9\.\-]+\.tisice\.net', hostname)
    if matches: return True
    matches = re.search('ip-address-pool-.*?\.fpt\.vn', hostname)
    if matches: return True
    matches = re.search('[0-9]+-adsl\.ntc\.net\.np', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.btc-net\.bg', hostname)
    if matches: return True
    matches = re.search('edc[0-9]\.emiara\.info', hostname)
    if matches: return True
    matches = re.search('host-.*?-c[0-9]+\.net\.pl', hostname)
    if matches: return True
    matches = re.search('host.*?\.wilnet\.com\.ar', hostname)
    if matches: return True
    matches = re.search('dhcp.*?gsm\.econet\.co\.ls', hostname)
    if matches: return True
    matches = re.search('ip.*?\.ct\.co\.cr', hostname)
    if matches: return True
    matches = re.search('cmr-.*?\.cr\.net\.cable.rogers\.com', hostname)
    if matches: return True
    matches = re.search('maxfibra-.*?\.yune\.com\.br', hostname)
    if matches: return True
    matches = re.search('cpc[0-9]+.*?\.cable\.virginm\.net', hostname)
    if matches: return True
    matches = re.search('host-static-[0-9\-]+\.moldtelecom\.md', hostname)
    if matches: return True
    matches = re.search('ip.[0-9\.]+\.stat\.volia\.net', hostname)
    if matches: return True
    matches = re.search('adsl[0-9\-]+.adsl[0-9\-]+\.iam\.net\.ma', hostname)
    if matches: return True
    matches = re.search('\.penza\.com\.ru', hostname)
    if matches: return True
    matches = re.search('host.*?\-static\.[0-9\-]+-[a-z]\.business\.telecomitalia\.it', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.forpsi\.net', hostname)
    if matches: return True
    matches = re.search('[0-9\.]+..*?.dyn.claro.net.do', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.static\.networldindia\.com', hostname)
    if matches: return True
    matches = re.search('broadband-.*?\.ip\.moscow\.[a-z]+\.ru', hostname)
    if matches: return True
    matches = re.search('\.client\.mchsi\.com', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.maaanishhh\.com', hostname)
    if matches: return True
    matches = re.search('[0-9\-]\.jerenet\.com\.br', hostname)
    if matches: return True
    matches = re.search('[0-9\.]\.wananchi\.com', hostname)
    if matches: return True
    matches = re.search('ip[0-9\.]+\.kzn\.tbt\.ru', hostname)
    if matches: return True
    matches = re.search('smtp[0-9]+.[a-z0-9]+\.emailsrvr\.com', hostname)
    if matches: return True
    matches = re.search('pppoe-[0-9\-]+\.elcity\.ru', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.datec\.net\.pg', hostname)
    if matches: return True
    matches = re.search('cpe-[0-9\-]+\.st[0-9]+\.cable\.xnet\.hr', hostname)
    if matches: return True
    matches = re.search('host-[0-9\-]\.opaltelecom\.net', hostname)
    if matches: return True
    matches = re.search('pool-[0-9]+\.datec\.net\.pg', hostname)
    if matches: return True
    matches = re.search('v-[0-9]+-[a-z]+\.vpn\.mgn\.ru', hostname)
    if matches: return True
    matches = re.search('ip-[0-9\.]+\.danieltel\.com\.br', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.colo\.transip\.net', hostname)
    if matches: return True
    matches = re.search('din-s[0-9\.\-]+\.ipcom\.comunitel\.net', hostname)
    if matches: return True
    matches = re.search('hsdpa-[0-9\-]+\.edatel\.net\.co', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.rev\.numericable\.fr', hostname)
    if matches: return True
    matches = re.search('[0-9\-]\.bam\.movistar.cl', hostname)
    if matches: return True
    matches = re.search('\.rev\.poneytelecom\.eu', hostname)
    if matches: return True
    matches = re.search('apn-.*?.vodafone\.hu', hostname)
    if matches: return True
    matches = re.search('ucmail.*?\.sendcloud\.org', hostname)
    if matches: return True
    matches = re.search('adsl.*?\.romtelecom\.net', hostname)
    if matches: return True
    matches = re.search('cm-.*?\.getinternet\.no', hostname)
    if matches: return True
    matches = re.search('[0-9\.]+\.satcom-systems\.net', hostname)
    if matches: return True
    matches = re.search('\.cust-[0-9]+\.exponential-e\.net', hostname)
    if matches: return True
    matches = re.search('shpd-.*?\.vologda\.ru', hostname)
    if matches: return True
    matches = re.search('\.range[0-9]+-[0-9]+\.btcentralplus\.com', hostname)
    if matches: return True
    matches = re.search('ip\.btc-net\.bg', hostname)
    if matches: return True
    matches = re.search('Adsl-.*?\.aviso\.ci', hostname)
    if matches: return True
    matches = re.search('\.pool\.ukrtel\.net', hostname)
    if matches: return True
    matches = re.search('ip-.*?.maiqvox\.net\.br', hostname)
    if matches: return True
    matches = re.search('\.(rierke|ghemur|gipout)\.info', hostname)
    if matches: return True
    matches = re.search('\.(zaural|baltnet)\.ru', hostname)
    if matches: return True
    matches = re.search('nca\.lanset\.com', hostname)
    if matches: return True
    matches = re.search('host.*?\.colsecor\.net\.ar', hostname)
    if matches: return True
    matches = re.search('cli-.*?\.ast\.adamo\.es', hostname)
    if matches: return True
    matches = re.search('\.cust\.a3fiber\.se', hostname)
    if matches: return True

    whywhy = "Line 1839"
    matches = re.search('\.(regard45|beaute90|lesaffairesenfolie|laparfaitenews|lespepitesdujour|stretchoid|monsigneduzodiaque|newsletter\.brandalley)\.com', hostname)
    if matches: return True
    matches = re.search('\.(papillon75|rudesconsommateurs|emailsender|loffrededingue)\.fr', hostname)
    if matches: return True
    matches = re.search('\.(yoogotadate|aproxtrack3|rnmk|wixshoutout|md02|vdslsoldes|comgbmed|veml01|emk02)\.com', hostname)
    if matches: return True
    matches = re.search('\.qqenglish\.com\.br', hostname)
    if matches: return True
    matches = re.search('[a-z]+\.thervspace\.info', hostname)
    if matches: return True
    matches = re.search('dedi\.server-hosting\.expert', hostname)
    if matches: return True
    matches = re.search('dedicated-.*?\.dri-services\.net', hostname)
    if matches: return True
    matches = re.search('wlan-.*?.last-mile\.hu', hostname)
    if matches: return True
    matches = re.search('host-[0-9\.]-.*?.net.pl', hostname)
    if matches: return True
    matches = re.search('lh[0-9]+\.voxility\.net$', hostname)
    if matches: return True
    matches = re.search('WimaxUser.*?\.wateen\.net$', hostname)
    if matches: return True
    matches = re.search('-static\..*?\.customer\.tdc\.net', hostname)
    if matches: return True
    matches = re.search('static-.*?\..*?\.rdsnet\.ro', hostname)
    if matches: return True
    matches = re.search('outbound\.createsend\.com', hostname)
    if matches: return True
    matches = re.search('ip-.*?\.chunkhost\.com', hostname)
    if matches: return True
    matches = re.search('Fiberlink\..*?\.lynx\.net\.lb', hostname)
    if matches: return True
    matches = re.search('\.sta\.dodo\.net\.au', hostname)
    if matches: return True
    matches = re.search('[0-9]+.sub-.*?\.myvzw\.com', hostname)
    if matches: return True
    matches = re.search('dynamic\.kabel-deutschland\.de', hostname)
    if matches: return True
    matches = re.search('\.home\.otenet\.gr', hostname)
    if matches: return True
    matches = re.search('\.dynamic\.163data\.com\.cn', hostname)
    if matches: return True
    matches = re.search('adsl\.anteldata\.net\.uy', hostname)
    if matches: return True
    matches = re.search('dynamic\..* ?\.telecomitalia\.it', hostname)
    if matches: return True
    matches = re.search('\.ip\.afrihost\.co\.za', hostname)
    if matches: return True
    matches = re.search('\.res\.rr\.com', hostname)
    if matches: return True
    matches = re.search('\.(cccampaigns|message-business|rmh2)\.net', hostname)
    if matches: return True
    matches = re.search('static\.vnpt\.vn', hostname)
    if matches: return True
    matches = re.search('\.dynamic.*?\.ertelecom\.ru', hostname)
    if matches: return True
    matches = re.search('dynamic-ip\.hinet\.net', hostname)
    if matches: return True
    matches = re.search('dynamic-.*?.airtelbroadband', hostname)
    if matches: return True
    matches = re.search('\.(mpfra01|ne\.earthlink)\.net', hostname)
    if matches: return True
    matches = re.search('\.(smtp|aritz-nws|kama|media-digital|la-select-charlie|guide-info|lebonmomentnet|patationa|cercle-offres|lanewsendelire|lavenuemagique|dlems5|dlems3|promonautes|promoschocs)\.fr$', hostname)
    if matches: return True
    matches = re.search('\dyn\.cableonline\.', hostname)
    if matches: return True
    matches = re.search('\.broadband\.hu', hostname)
    if matches: return True
    matches = re.search('\.(offersbuy|emk03|linktrenvr|mm-send|e-radin|gamma-mta|sacspourelle|mandrillapp|shop-newsletters|aproxeml[0-9]+|lanewsdudecideur)\.com', hostname)
    if matches: return True
    matches = re.search('\.(avangarddsl|ranetka)\.ru', hostname)
    if matches: return True
    matches = re.search('\.(healthsshop|newsletter-web|appli-lb|ami95)\.com', hostname)
    if matches: return True
    matches = re.search('adsl.*?\.une\.net\.co', hostname)
    if matches: return True
    matches = re.search('\.wireless\.telus\.com', hostname)
    if matches: return True
    matches = re.search('live\.vodafone\.', hostname)
    if matches: return True
    matches = re.search('netfacil\.center', hostname)
    if matches: return True
    matches = re.search('adsl\.anteldata\.net', hostname)
    if matches: return True
    matches = re.search('\.(pjv01|superonline|mediaworksit)\.net', hostname)
    if matches: return True
    matches = re.search('\.dhcp\..*?\.charter\.com', hostname)
    if matches: return True
    matches = re.search('pppoe\.irtel\.ru', hostname)
    if matches: return True
    matches = re.search('dsl\.static\.turk\.net', hostname)
    if matches: return True
    matches = re.search('\.express\.com\.ar', hostname)
    if matches: return True
    matches = re.search('\.clients\.your-server\.de', hostname)
    if matches: return True
    matches = re.search('\.mktfreedom\.com', hostname)
    if matches: return True
    matches = re.search('\.pppoe\.byfly\.by', hostname)
    if matches: return True
    matches = re.search('\.rackap\.com', hostname)
    if matches: return True
    matches = re.search('\.(ccemails|planenact|ymlpsvr|ceemino|la-bonne-economie[0-9]+|actu-pro|mygooddealday)\.com', hostname)
    if matches: return True
    matches = re.search('\.cust\.tele2\.kz', hostname)
    if matches: return True
    matches = re.search('\.conexaovip\.net\.br', hostname)
    if matches:return True
    matches = re.search('\.dsl\.telepac\.pt', hostname)
    if matches: return True
    matches = re.search('\.mp[0-9]+\.com$', hostname)
    if matches: return True
    matches = re.search('dsl.*?\.netvision\.net\.il$', hostname)
    if matches:return True
    matches = re.search('\.ip[0-9]+\.fastwebnet\.it$', hostname)
    if matches: return True
    matches = re.search('\.retail\.telecomitalia\.it$', hostname)
    if matches: return True
    matches = re.search('\.adsl\.net\.t-com\.hr', hostname)
    if matches: return True
    matches = re.search('\.ertelecom\.ru', hostname)
    if matches: return True
    matches = re.search('\.telkomadsl\.co\.za', hostname)
    if matches: return True
    matches = re.search('\.vodafonedsl\.it', hostname)
    if matches: return True

    whywhy = "Line 1967"
    matches = re.search('[0-9\.]+\.megaline\.telecom\.kz', hostname)
    if matches: return True
    matches = re.search('\.ucom\.am', hostname)
    if matches: return True
    matches = re.search('\.dynamic\.reverse-mundo-r\.com', hostname)
    if matches: return True
    matches = re.search('\.emsmtp\.us$', hostname)
    if matches: return True
    matches = re.search('\.dsl\.billi\.be$', hostname)
    if matches: return True
    matches = re.search('\.(promos-news|espace-dom|votre-detox)\.fr$', hostname)
    if matches: return True
    matches = re.search('\.onlinehome-server\.info$', hostname)
    if matches: return True
    matches = re.search('\.dynamic\.(starweb\.net\.br|\.customer\.lanta\.me)$', hostname)
    if matches: return True
    matches = re.search('\.pools\.(vodafone-ip|atnet.ru)\.[a-z]+$', hostname)
    if matches: return True
    matches = re.search('static[0-9\.\-]+\.countryonline\.ru', hostname)
    if matches: return True
    matches = re.search('host-[0-9]+-net-[0-9]+\.bisnet\.od\.ua', hostname)
    if matches: return True
    matches = re.search('dhcp-dynamic-.*?\.broadband\.nlink\.ru', hostname)
    if matches: return True
    matches = re.search('[0-9\.\-]+\.dsl\.bell\.ca', hostname)
    if matches: return True
    matches = re.search('[a-z][0-9]+-[0-9]+\.impsat\.com\.co', hostname)
    if matches: return True
    matches = re.search('\.dyn\.plus\.net$', hostname)
    if matches: return True
    matches = re.search('\.baf\.movistar\.cl$', hostname)
    if matches: return True
    matches = re.search('\.t-ipconnect\.', hostname)
    if matches: return True
    matches = re.search('\.adsl-pool\.', hostname)
    if matches: return True
    matches = re.search('\.static\.intelnet\.net\.gt$', hostname)
    if matches: return True
    matches = re.search('\.adsl\.tie\.cl$', hostname)
    if matches: return True
    matches = re.search('\.(pppoe|adsl|dynamic|dialup)\.', hostname)
    if matches: return True
    matches = re.search('customer-.*?\.uninet-ide\.com\.mx', hostname)
    if matches: return True
    matches = re.search('\.cable\.starman\.ee', hostname)
    if matches: return True
    matches = re.search('net.*?\..*?\.telenor\.rs', hostname)
    if matches: return True
    matches = re.search('host.*?(static\.tedata\.net|telecom\.net\.ar)', hostname)
    if matches: return True
    matches = re.search('\.megared\.net\.mx$', hostname)
    if matches: return True
    matches = re.search('dynamic-ip.*?\.cable\.net.\co$', hostname)
    if matches: return True
    matches = re.search('\.digi\.net\.my', hostname)
    if matches: return True
    matches = re.search('\.dynamic\.clientes\.euskaltel\.es', hostname)
    if matches: return True
    matches = re.search('dyn-.*?\.fast\.net\.id', hostname)
    if matches: return True
    matches = re.search('\.unifiedlayer\.com', hostname)
    if matches: return True
    matches = re.search('\.myt\.mu', hostname)
    if matches: return True
    matches = re.search('-static\.tedata\.net', hostname)
    if matches: return True
    matches = re.search('\.bezeqint\.net', hostname)
    if matches: return True
    matches = re.search('\.umts\.vodacom\.co\.za', hostname)
    if matches: return True
    matches = re.search('\.customers\.adc\.am', hostname)
    if matches: return True
    matches = re.search('\.icpnet\.pl', hostname)
    if matches: return True
    matches = re.search('\.cabletel\.com\.mk', hostname)
    if matches: return True
    matches = re.search('\.cm\.vtr\.net', hostname)
    if matches: return True
    matches = re.search('pool\.powernet\.com\.ru', hostname)
    if matches: return True
    matches = re.search('\.domolink\.elcom\.ru', hostname)
    if matches: return True
    matches = re.search('\.cpe\.netcabo\.pt', hostname)
    if matches: return True
    matches = re.search('^adsl-[a-z0-9]+-[0-9]+-', hostname)
    if matches: return True
    matches = re.search('\.nash\.net\.ua', hostname)
    if matches: return True
    matches = re.search('\.mastercabo\.com\.br', hostname)
    if matches: return True
    matches = re.search('ip-.*?\.machanet\.com\.br', hostname)
    if matches: return True
    matches = re.search('customer-.*?\.tcm10\.com\.br', hostname)
    if matches: return True
    matches = re.search('\.fanaptelecom\.net', hostname)
    if matches: return True
    matches = re.search('\.broadband[0-9]+\.iol\.cz', hostname)
    if matches: return True

    whywhy = "Line 2067"
    matches = re.search('\.myrepublic\.com\.sg', hostname)
    if matches: return True
    matches = re.search('(dynamic-adsl-|dynamicip-)', hostname)
    if matches: return True
    matches = re.search('\.widsl\.net', hostname)
    if matches: return True
    matches = re.search('\.centertel\.pl', hostname)
    if matches: return True
    matches = re.search('\.cable\.globalnet\.hr', hostname)
    if matches: return True
    matches = re.search('\.bms[0-9]+\.bmsend\.com', hostname)
    if matches: return True
    matches = re.search('\.hellovps\.in\.th', hostname)
    if matches: return True
    matches = re.search('\.biz\.rr\.com', hostname)
    if matches: return True
    matches = re.search('\.vodafonedsl\.it', hostname)
    if matches: return True
    matches = re.search('\.adsl\.anteldata\.net\.uy', hostname)
    if matches: return True
    matches = re.search('\.ros\.express\.com\.ar', hostname)
    if matches: return True
    matches = re.search('\.tpgi\.com\.au', hostname)
    if matches: return True
    matches = re.search('\.actus-pme', hostname)
    if matches: return True
    matches = re.search('\.info-pros\.com', hostname)
    if matches: return True
    matches = re.search('\.solution-pme\.com', hostname)
    if matches: return True
    matches = re.search('\.dsl\.dyn\.forthnet\.gr', hostname)
    if matches: return True
    matches = re.search('\.virtua\.com\.br', hostname)
    if matches: return True
    matches = re.search('\.cable\.net\.co', hostname)
    if matches: return True
    matches = re.search('\.elcity\.ru', hostname)
    if matches: return True
    matches = re.search('\.online\.dn\.ua', hostname)
    if matches: return True
    matches = re.search('\.PMPL-Broadband\.net', hostname)
    if matches: return True
    matches = re.search('\.planeta\.tc', hostname)
    if matches: return True
    matches = re.search('\.play-internet\.pl', hostname)
    if matches: return True
    matches = re.search('\.net\.upcbroadband\.cz', hostname)
    if matches: return True
    matches = re.search('\.versanet\.de', hostname)
    if matches: return True
    matches = re.search('\.static\.belltele\.in', hostname)
    if matches: return True
    matches = re.search('\.donpac\.ru', hostname)
    if matches: return True
    matches = re.search('\.(asianet|techg)\.co\.in', hostname)
    if matches: return True
    matches = re.search('\.starhub\.net\.sg', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.wia\.cz', hostname)
    if matches: return True
    matches = re.search('\.safaricombusiness\.co\.ke', hostname)
    if matches: return True
    matches = re.search('vps-*?\.firstfind\.nl', hostname)
    if matches: return True
    matches = re.search('host-.*?\.cloudsigma\.net', hostname)
    if matches: return True
    matches = re.search('ip-[a-z]+\..*?.telefonica-ca\.net', hostname)
    if matches: return True
    matches = re.search('\.fibra\.movistar\.cl', hostname)
    if matches: return True
    matches = re.search('\.(fjcut|actu-pme|factor-tool)\.com', hostname)
    if matches: return True
    matches = re.search('\.dsl\.tropolys\.de', hostname)
    if matches: return True
    matches = re.search('-[0-9]+\.ipb\.na', hostname)
    if matches: return True
    matches = re.search('\.ipv4\.supernova\.orange\.pl', hostname)
    if matches: return True
    matches = re.search('node-[0-9\-]+\.alliancebroadband\.in', hostname)
    if matches: return True
    matches = re.search('\.pool\.ic\.km\.ua', hostname)
    if matches: return True
    matches = re.search('\.meilleurclic\.com', hostname)
    if matches: return True
    matches = re.search('\.core\.ttnet\.cz', hostname)
    if matches: return True
    matches = re.search('\.cust\.ikc\.cz', hostname)
    if matches: return True
    matches = re.search('\.clicknettelecom\.com\.br', hostname)
    if matches: return True
    matches = re.search('[0-9\.]+\.static\.gvt\.net\.br', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.west\.com\.br', hostname)
    if matches: return True
    matches = re.search('\.dynamicip\.rima-tde\.net', hostname)
    if matches: return True
    matches = re.search('-dyn\.spydernet\.hu', hostname)
    if matches: return True
    matches = re.search('\.c3\.net\.pl', hostname)
    if matches: return True
    matches = re.search('\.dsl\.brasiltelecom\.net\.br', hostname)
    if matches: return True
    matches = re.search('\.pool\.sknt\.ru', hostname)
    if matches: return True
    matches = re.search('\.cps\.com\.ar', hostname)
    if matches: return True
    matches = re.search('\.ppp\.twt\.it', hostname)
    if matches: return True
    matches = re.search('\.static\.imsbiz\.com', hostname)
    if matches: return True
    matches = re.search('\.cust\.hvfree\.net', hostname)
    if matches: return True
    matches = re.search('host[0-9]+\.netdc\.net', hostname)
    if matches: return True
    matches = re.search('\.zone[0-9]+\.zaural\.ru', hostname)
    if matches: return True
    matches = re.search('\.ml\.wish\.com', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.nfrance\.com', hostname)
    if matches: return True
    matches = re.search('\.dms30\.com', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.anetvm\.cz$', hostname)
    if matches: return True
    matches = re.search('\.[a-z]+\.velton\.ua', hostname)
    if matches: return True
    matches = re.search('\.[a-z]+\.netbynet\.ru', hostname)
    if matches: return True
    matches = re.search('ppp-.*?\.wind\.it', hostname)
    if matches: return True
    matches = re.search('\.hostresolver\.net', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.orange\.net\.il', hostname)
    if matches: return True
    matches = re.search('cust.*?\.tvcabo\.ao', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.shatel\.ir', hostname)
    if matches: return True
    matches = re.search('usr-[0-9\-]+.telfy.com', hostname)
    if matches: return True
    matches = re.search('unallocated\.sta\.synapse\.net\.uam', hostname)
    if matches: return True
    matches = re.search('smtp[0-9]+\.gamma-mta\.com', hostname)
    if matches: return True
    matches = re.search('atl[0-9]+\.urbear\.net', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.rev\.pro-internet\.pl', hostname)
    if matches: return True
    matches = re.search('[a-z][0-9]+\.gorlice\.zicom\.pl', hostname)
    if matches: return True
    matches = re.search('\.customer\.fiber\.supratelecom\.com\.br', hostname)
    if matches: return True
    matches = re.search('\.[0-9]+\.ccc\.net\.il', hostname)
    if matches: return True
    matches = re.search('\.xdsl\.ab\.ru', hostname)
    if matches: return True
    matches = re.search('ppp-.*?revip[0-9]+\.asianet\.co\.th', hostname)
    if matches: return True
    matches = re.search('\.constantcontact\.com', hostname)
    if matches: return True
    matches = re.search('static-.*?\.mykris\.net', hostname)
    if matches: return True
    matches = re.search('\.sooncukor\.com', hostname)
    if matches: return True
    matches = re.search('host[0-9\-]+\.serverdedicati\.aruba\.', hostname)
    if matches: return True
    matches = re.search('adsl([0-9\-]+)\.epm\.net\.co', hostname)
    if matches: return True
    matches = re.search('ip-.*?\.multi\.internet\.cyfrowypolsat\.pl', hostname)
    if matches: return True
    matches = re.search('pool-.*?\.is[0-9]+\.ru', hostname)
    if matches: return True
    matches = re.search('\.adsl\.net\.t-com\.hr', hostname)
    if matches: return True
    matches = re.search('\.wifi-dyn\.isp\.', hostname)
    if matches: return True
    matches = re.search('\.enquete-en-or\.com', hostname)
    if matches: return True
    matches = re.search('ntl-.*?\.nayatel\.com', hostname)
    if matches: return True
    matches = re.search('smtp[0-9]+\.goodtrack\.pl', hostname)
    if matches: return True
    matches = re.search('sub[0-9]+\.dexwl\.com', hostname)
    if matches: return True
    matches = re.search('\.hwccustomers\.com', hostname)
    if matches: return True
    matches = re.search('BASIC\.ikexpress\.com', hostname)
    if matches: return True
    matches = re.search('\.dm\.aliyun\.com', hostname)
    if matches: return True
    matches = re.search('ppp.*?\.access\.hol\.gr', hostname)
    if matches: return True
    matches = re.search('\.dynamic\.stcable\.net', hostname)
    if matches: return True
    matches = re.search('\.nat\.pool\.telekom\.hu', hostname)
    if matches: return True
    matches = re.search('\.st3\.cable\.xnet\.hr', hostname)
    if matches: return True
    matches = re.search('\.newssocial\.eu$', hostname)
    if matches: return True
    matches = re.search('\.xd-dynamic\.algarnetsuper\.com\.br', hostname)
    if matches: return True
    matches = re.search('\.static\.pacific\.net\.hk', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.wiz\.pwr\.edu\.pl', hostname)
    if matches: return True
    matches = re.search('\.radiocom\.ro', hostname)
    if matches: return True
    matches = re.search('\.dsl\.telepac\.pt', hostname)
    if matches: return True
    matches = re.search('\.clicplan\.', hostname)
    if matches: return True
    matches = re.search('\.speedy\.com\.ar', hostname)
    if matches: return True
    matches = re.search('ip-.*?\.hsi[0-9]+\.unitymediagroup\.de', hostname)
    if matches: return True
    matches = re.search('customer.*?\.megared\.net\.mx', hostname)
    if matches: return True
    matches = re.search('\.setardsl\.aw', hostname)
    if matches: return True
    matches = re.search('bmc-ds[0-9]+\.galaxydata\.ru', hostname)
    if matches: return True
    matches = re.search('ip.*?\.zaindata\.jo', hostname)
    if matches: return True
    matches = re.search('\.net\.prima\.net\.ar', hostname)
    if matches: return True
    matches = re.search('\.mobile\.kyivstar\.net', hostname)
    if matches: return True
    matches = re.search('WimaxUser([0-9\-]+)\.wateen\.net', hostname)
    if matches: return True
    matches = re.search('static-([0-9\-]+)\..*?\.frontiernet\.net', hostname)
    if matches: return True
    matches = re.search('dsl\.telkomsa\.net', hostname)
    if matches: return True
    matches = re.search('\.the-fresher\.fr', hostname)
    if matches: return True
    matches = re.search('\.red-acceso\.airtel\.net', hostname)
    if matches: return True
    matches = re.search('dyndsl-.*?.ewe-ip-backbone\.de', hostname)
    if matches: return True
    matches = re.search('\.dyn\.telefonica\.', hostname)
    if matches: return True
    matches = re.search('clients-pools\.pz\.cooolbox\.bg', hostname)
    if matches: return True
    matches = re.search('\.(solic|vespanet)\.com\.br', hostname)
    if matches: return True
    matches = re.search('\.staticip\.rima-tde\.net', hostname)
    if matches: return True
    matches = re.search('\.dsl\.telesp\.net\.br', hostname)
    if matches: return True
    matches = re.search('\.dyn\.user\.ono\.com', hostname)
    if matches: return True
    matches = re.search('\.cable\.dyn\.cableonline\.com\.mx', hostname)
    if matches: return True
    matches = re.search('host[0-9]+\.telnetropczyce\.pl', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\..*?\.ulink\.ru', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.service\.infuturo\.it', hostname)
    if matches: return True
    matches = re.search('mobile-.*?\.mycingular\.net', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.members\.linode\.com', hostname)
    if matches: return True
    matches = re.search('\.ip\.orionnet\.ru', hostname)
    if matches: return True
    matches = re.search('dynamic\.163data\.', hostname)
    if matches: return True
    matches = re.search('\.movil\.vtr\.net', hostname)
    if matches: return True
    matches = re.search('\.dynamic\.reverse-mundo-r\.com', hostname)
    if matches: return True
    matches = re.search('\.ros\.express\.com\.ar', hostname)
    if matches: return True
    matches = re.search('\.static\.upcbusiness\.at', hostname)
    if matches: return True
    matches = re.search('\.adsl-pool\.sx\.cn', hostname)
    if matches: return True
    matches = re.search('host-.*?\-static\.tedata\.net', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.rev\.vodafone\.', hostname)
    if matches: return True
    matches = re.search('[0-9\.]+\.static\.fiberpipe\.in', hostname)
    if matches: return True
    matches = re.search('[0-9]+\.static\.bangmod-idc\.com', hostname)
    if matches: return True
    matches = re.search('localhost', hostname)
    if matches: return True
    matches = re.search('static-[0-9\-]+\.rev\.webside\.pt', hostname)
    if matches: return True
    matches = re.search('\.elisting\.date', hostname)
    if matches: return True
    matches = re.search('free-[0-9\-]+\.mediaworksit\.net', hostname)
    if matches: return True
    matches = re.search('[0-9\-]+\.agc\.net\.za', hostname)
    if matches: return True
    matches = re.search('[0-9]+-static\.hfc\.comcastbusiness\.net', hostname)
    if matches: return True
    matches = re.search('dsl.*?\.permonline\.ru', hostname)
    if matches: return True
    matches = re.search('host-.*?\.adc\.net\.ar', hostname)
    if matches: return True
    matches = re.search('dsl-.*?\.pld\.com', hostname)
    if matches: return True
    matches = re.search('host.*?\.prov\.ru', hostname)
    if matches: return True
    matches = re.search('host.*?\.redcrs\.com\.ar', hostname)
    if matches: return True
    matches = re.search('host-.*?\.adc\.net\.ar', hostname)
    if matches: return True

    whywhy = "Line 2379"
    matches = re.search('\.icodia\.host', hostname)
    if matches: return True
    matches = re.search('\.jon.cz', hostname)
    if matches: return True
    matches = re.search('pool\.dsl\.gol\.net\.gy', hostname)
    if matches: return True
    matches = re.search('\.dyn\.suddenlink\.net', hostname)
    if matches: return True
    matches = re.search('pbx-hosting\.ru', hostname)
    if matches: return True
    matches = re.search('\.candw\.ag', hostname)
    if matches: return True
    matches = re.search('\.csclux.lu', hostname)
    if matches: return True
    matches = re.search('static-.*?\.earthlinkbusiness\.net', hostname)
    if matches: return True
    matches = re.search('\.mta10\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-jdg\.com', hostname)
    if matches: return True
    matches = re.search('\.securite-protection-loisirs\.net', hostname)
    if matches: return True
    matches = re.search('\.securite-protection-loisirs\.com', hostname)
    if matches: return True
    matches = re.search('\.colonie-vacance\.pro', hostname)
    if matches: return True
    matches = re.search('\.colonies-vacances\.pro', hostname)
    if matches: return True
    matches = re.search('\.colonies-de-vacances\.pro', hostname)
    if matches: return True
    matches = re.search('\.colonies\.pro', hostname)
    if matches: return True
    matches = re.search('\.colonie-de-vacances\.pro', hostname)
    if matches: return True
    matches = re.search('\.colonie\.pro', hostname)
    if matches: return True
    matches = re.search('\.chute-libre\.pro', hostname)
    if matches: return True
    matches = re.search('\.cb-menuiseries\.pro', hostname)
    if matches: return True
    matches = re.search('\.cbmenuiseries\.pro', hostname)
    if matches: return True
    matches = re.search('\.cb-menuiseries\.com', hostname)
    if matches: return True
    matches = re.search('\.cbmenuiseries\.com', hostname)
    if matches: return True
    matches = re.search('\.renesola\.fr', hostname)
    if matches: return True
    matches = re.search('\.netcomm-dev\.com', hostname)
    if matches: return True
    matches = re.search('\.netcommdev\.com', hostname)
    if matches: return True
    matches = re.search('\.informer\.fr', hostname)
    if matches: return True
    matches = re.search('\.le-petit-marche-de-sylvie\.com', hostname)
    if matches: return True
    matches = re.search('\.diffusinfo\.fr', hostname)
    if matches: return True
    matches = re.search('\.tr-repere\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-no-asp\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-dag-sys\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-technic\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-isinfo\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-pvision\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-pass-st\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-ln3\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-link4\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-waw-stat\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-unip\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-ip-rev\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-waw\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-passman\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-lnk2\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-isi\.com', hostname)
    if matches: return True
    matches = re.search('\.mta-d5\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-medias\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-uni\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-unit4\.com', hostname)
    if matches: return True
    matches = re.search('\.lesideesrestos\.fr', hostname)
    if matches: return True
    matches = re.search('\.solutions-netop\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-ess\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-lnk\.com', hostname)
    if matches: return True
    matches = re.search('\.trlink1\.com', hostname)
    if matches: return True
    matches = re.search('\.trlevel1\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-adt1\.com', hostname)
    if matches: return True
    matches = re.search('\.to-trlnk\.com', hostname)
    if matches: return True
    matches = re.search('\.one-tr\.com', hostname)
    if matches: return True
    matches = re.search('\.coursetstages\.fr', hostname)
    if matches: return True
    matches = re.search('\.solution-netop\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-ech\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-adts\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-adt\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-arc\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-idis\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-ide\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-ofr\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-mn\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-mes\.com', hostname)
    if matches: return True
    matches = re.search('\.trl-lnk\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-lea\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-dyn\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-dpe\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-cp\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-ci\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-cdp\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-bdmr\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-gpi\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-evt\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-esc\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-trans\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-test\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-sh\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-rm\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-rd\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-am\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-alvs\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-alm\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-prv\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-pass\.com', hostname)
    if matches: return True
    matches = re.search('\.tck-web\.com', hostname)
    if matches: return True
    matches = re.search('\.nippon-kempo\.com', hostname)
    if matches: return True
    matches = re.search('\.mta21\.com', hostname)
    if matches: return True
    matches = re.search('\.mege-amo\.com', hostname)
    if matches: return True
    matches = re.search('\.ferreiradb\.com', hostname)
    if matches: return True
    matches = re.search('\.gbisolation\.com', hostname)
    if matches: return True
    matches = re.search('\.decoroc\.com', hostname)
    if matches: return True
    matches = re.search('\.del-et-co\.com', hostname)
    if matches: return True
    matches = re.search('\.copieursystem\.com', hostname)
    if matches: return True
    matches = re.search('\.blog-emailing\.com', hostname)
    if matches: return True
    matches = re.search('\.anthtech\.net', hostname)
    if matches: return True
    matches = re.search('\.anthmta\.com', hostname)
    if matches: return True
    matches = re.search('\.3s-concept-ingenierie\.com', hostname)
    if matches: return True
    matches = re.search('\.3s-concept\.com', hostname)
    if matches: return True
    matches = re.search('\.mta-n3\.com', hostname)
    if matches: return True
    matches = re.search('\.mta-n2\.com', hostname)
    if matches: return True
    matches = re.search('\.mta-n1\.com', hostname)
    if matches: return True
    matches = re.search('\.mta-medias\.com', hostname)
    if matches: return True
    matches = re.search('\.trfollow\.com', hostname)
    if matches: return True
    matches = re.search('\.tr-track\.com', hostname)
    if matches: return True
    matches = re.search('\.aboml21\.com$', hostname)
    if matches: return True

    matches = re.search('\.achatsdirects\.fr$', hostname)
    if matches: return True

    matches = re.search('\.actuca\.com$', hostname)
    if matches: return True

    matches = re.search('\.ad-marketer\.com$', hostname)
    if matches: return True

    matches = re.search('\.addop\.com$', hostname)
    if matches: return True

    matches = re.search('\.address\.com$', hostname)
    if matches: return True

    matches = re.search('\.advd69\.net$', hostname)
    if matches: return True

    matches = re.search('\.advertsengine\.com$', hostname)
    if matches: return True

    matches = re.search('\.affil1\.fr$', hostname)
    if matches: return True

    matches = re.search('\.agence-diffusion\.fr$', hostname)
    if matches: return True

    matches = re.search('\.agenceweb\.net$', hostname)
    if matches: return True

    matches = re.search('\.agencysight\.com$', hostname)
    if matches: return True

    matches = re.search('\.all\.depannepc\.com$', hostname)
    if matches: return True

    matches = re.search('\.angelbc-mail.co.uk$', hostname)
    if matches: return True

    matches = re.search('\.anonym.tm\.fr$', hostname)
    if matches: return True

    matches = re.search('\.aspmail\.info$', hostname)
    if matches: return True

    matches = re.search('\.asturian\.network.ch$', hostname)
    if matches: return True

    matches = re.search('\.aurega\.fr$', hostname)
    if matches: return True

    matches = re.search('\.auxilog\.com$', hostname)
    if matches: return True

    matches = re.search('\.axalone\.com$', hostname)
    if matches: return True

    matches = re.search('\.axw02\.com$', hostname)
    if matches: return True

    matches = re.search('\.b2b-emailing\.com$', hostname)
    if matches: return True

    matches = re.search('\.b2bturkishtextile\.com$', hostname)
    if matches: return True

    matches = re.search('\.basylo\.com$', hostname)
    if matches: return True

    matches = re.search('\.battle-technology\.com$', hostname)
    if matches: return True

    matches = re.search('\.bctr14p\.com$', hostname)
    if matches: return True

    matches = re.search('\.bell-news\.de$', hostname)
    if matches: return True

    matches = re.search('\.benchmark\.fr$', hostname)
    if matches: return True

    matches = re.search('\.best-choice\.fr$', hostname)
    if matches: return True

    matches = re.search('\.betsacom\.com$', hostname)
    if matches: return True

    matches = re.search('\.blast-email\.com$', hostname)
    if matches: return True

    matches = re.search('\.bmesrv2\.com$', hostname)
    if matches: return True

    matches = re.search('\.boitaweb\.net$', hostname)
    if matches: return True

    whywhy = "Line 2699"
    matches = re.search('\.bottingourmand\.com$', hostname)
    if matches: return True

    matches = re.search('\.boupen\.net$', hostname)
    if matches: return True

    matches = re.search('\.bp01\.net$', hostname)
    if matches: return True

    matches = re.search('\.bp06\.net$', hostname)
    if matches: return True

    matches = re.search('\.bpm0104\.com$', hostname)
    if matches: return True

    matches = re.search('\.br\.derie-mail\.com$', hostname)
    if matches: return True

    matches = re.search('\.bscomputer\.fr$', hostname)
    if matches: return True

    matches = re.search('\.bxmg\.info$', hostname)
    if matches: return True

    matches = re.search('\.cab01\.net$', hostname)
    if matches: return True

    matches = re.search('\.cab04\.net$', hostname)
    if matches: return True

    matches = re.search('\.cachet\.fr$', hostname)
    if matches: return True

    matches = re.search('\.caloga\.com$', hostname)
    if matches: return True

    matches = re.search('\.caradisiac\.com$', hostname)
    if matches: return True

    matches = re.search('\.carbonell-communications\.com$', hostname)
    if matches: return True

    matches = re.search('\.cashcartouches\.com$', hostname)
    if matches: return True

    matches = re.search('\.cccampaigns\.com$', hostname)
    if matches: return True

    matches = re.search('\.cccampaigns\.net$', hostname)
    if matches: return True

    matches = re.search('\.ccemails\.com$', hostname)
    if matches: return True

    matches = re.search('\.ccemails\.net$', hostname)
    if matches: return True

    matches = re.search('\.ccmdcampaigns\.net$', hostname)
    if matches: return True

    matches = re.search('\.choice-easy\.net$', hostname)
    if matches: return True

    matches = re.search('\.chtah\.net$', hostname)
    if matches: return True

    matches = re.search('\.cible-affaires\.com$', hostname)
    if matches: return True

    matches = re.search('\.clct\.fr$', hostname)
    if matches: return True

    matches = re.search('\.clicksite\.com$', hostname)
    if matches: return True

    matches = re.search('\.club-ajdata\.fr$', hostname)
    if matches: return True

    matches = re.search('\.cn4e\.com$', hostname)
    if matches: return True

    matches = re.search('\\.comcastbusiness\.net$', hostname)
    if matches: return True

    matches = re.search('\\.commercial-database\.net$', hostname)
    if matches: return True

    matches = re.search('\\.compa\.net\.fr$', hostname)
    if matches: return True

    matches = re.search('\.crm\.com$', hostname)
    if matches: return True

    matches = re.search('\.cust-cluster\.com$', hostname)
    if matches: return True

    matches = re.search('\.d4e5f6\.france\.fr$', hostname)
    if matches: return True

    matches = re.search('\.data-sendos\.com$', hostname)
    if matches: return True

    matches = re.search('\\.deal-server\.com$', hostname)
    if matches: return True

    matches = re.search('\\.desaimail\.com$', hostname)
    if matches: return True

    matches = re.search('\.distrisoft\.com$', hostname)
    if matches: return True

    matches = re.search('\.distrisoft\.fr$', hostname)
    if matches: return True

    matches = re.search('\.dms-01\.net$', hostname)
    if matches: return True

    matches = re.search('\.dnco\.info$', hostname)
    if matches: return True

    matches = re.search('\.dolist\.net$', hostname)
    if matches: return True

    matches = re.search('\.doyousoft\.com$', hostname)
    if matches: return True

    matches = re.search('\.e-kolay\.net$', hostname)
    if matches: return True

    matches = re.search('\.e-visibilite\.com$', hostname)
    if matches: return True

    matches = re.search('\.easyciscotton\.com$', hostname)
    if matches: return True

    matches = re.search('\.easytel\.com$', hostname)
    if matches: return True

    matches = re.search('\.ec-cluster\.com$', hostname)
    if matches: return True

    matches = re.search('\.ec-messenger\.com$', hostname)
    if matches: return True

    matches = re.search('\.eccluster\.com$', hostname)
    if matches: return True

    matches = re.search('\.ecircle-ag\.com$', hostname)
    if matches: return True

    matches = re.search('\.ecmcluster\.com$', hostname)
    if matches: return True

    matches = re.search('\.economie-mauve\.net$', hostname)
    if matches: return True

    matches = re.search('\.edt02\.net$', hostname)
    if matches: return True

    matches = re.search('\.effibases\.com$', hostname)
    if matches: return True

    matches = re.search('\.efml\.org$', hostname)
    if matches: return True

    matches = re.search('\.efyellow\.com$', hostname)
    if matches: return True

    matches = re.search('\.elabs10\.com$', hostname)
    if matches: return True

    matches = re.search('\.elb-media\.com$', hostname)
    if matches: return True

    matches = re.search('\.elit\.info\.net$', hostname)
    if matches: return True

    matches = re.search('\.email-cible\.net$', hostname)
    if matches: return True

    matches = re.search('\.emailcible\.net$', hostname)
    if matches: return True

    matches = re.search('\.emailer\.fr$', hostname)
    if matches: return True

    matches = re.search('\.emailstrategie-s12\.com$', hostname)
    if matches: return True

    matches = re.search('\.emailstrategie\.com$', hostname)
    if matches: return True

    matches = re.search('\.emarket2012\.com$', hostname)
    if matches: return True

    matches = re.search('\.emfo01\.net$', hostname)
    if matches: return True

    matches = re.search('\.eml-conseils\.com$', hostname)
    if matches: return True

    matches = re.search('\.emm04\.net$', hostname)
    if matches: return True

    matches = re.search('\.emm21\.net$', hostname)
    if matches: return True

    matches = re.search('\.emp01\.net$', hostname)
    if matches: return True

    matches = re.search('\.ems7\.net$', hostname)
    if matches: return True




    whywhy = "Line 2916"
    matches = re.search('\.en\.net\.com$', hostname)
    if matches: return True

    matches = re.search('\.en3clics\.fr$', hostname)
    if matches: return True

    matches = re.search('\.enterprisetower\.com$', hostname)
    if matches: return True

    matches = re.search('\.eolium\.fr$', hostname)
    if matches: return True

    matches = re.search('\.ergograph\.com$', hostname)
    if matches: return True

    matches = re.search('\.espace-mo\.eurs\.com$', hostname)
    if matches: return True

    matches = re.search('\.espacebusiness\.com$', hostname)
    if matches: return True

    matches = re.search('\.espacemo\.eurs\.com$', hostname)
    if matches: return True

    matches = re.search('\.esqualearning\.com$', hostname)
    if matches: return True

    matches = re.search('\.etdx66\.com$', hostname)
    if matches: return True

    matches = re.search('\.excuria\.net$', hostname)
    if matches: return True

    matches = re.search('\.express-mailing\.com$', hostname)
    if matches: return True

    matches = re.search('\.fashionmag\.fr$', hostname)
    if matches: return True

    matches = re.search('\.f\.derh\.com$', hostname)
    if matches: return True

    matches = re.search('\.fil-actu\.com$', hostname)
    if matches: return True

    matches = re.search('\.fil-news\.com$', hostname)
    if matches: return True

    matches = re.search('\.firstfun.tv$', hostname)
    if matches: return True

    matches = re.search('\.fisrt\.info$', hostname)
    if matches: return True

    matches = re.search('\.fm03\.eu$', hostname)
    if matches: return True

    matches = re.search('\.fm04\.eu$', hostname)
    if matches: return True

    matches = re.search('\\.french.connexion\.com$', hostname)
    if matches: return True

    matches = re.search('\\.frte\.info$', hostname)
    if matches: return True

    matches = re.search('\.gamma-mta\.com$', hostname)
    if matches: return True

    matches = re.search('\.gerardmanvussa\.com$', hostname)
    if matches: return True

    matches = re.search('\.gladiatorarenas\.net$', hostname)
    if matches: return True

    matches = re.search('\.goto\.fr$', hostname)
    if matches: return True

    matches = re.search('\.grioulu\.net$', hostname)
    if matches: return True

    matches = re.search('\.groupe\.dec\.\.eur\.com$', hostname)
    if matches: return True

    matches = re.search('\.gu\.deconseil\.com$', hostname)
    if matches: return True

    matches = re.search('\.gulliver\.com$', hostname)
    if matches: return True

    matches = re.search('\.harelcom\.com$', hostname)
    if matches: return True

    matches = re.search('\.hebergex\.com$', hostname)
    if matches: return True

    matches = re.search('\.hexillion\.com$', hostname)
    if matches: return True

    matches = re.search('\.horizon-lointain\.com$', hostname)
    if matches: return True

    matches = re.search('\.hos\.eur\.com$', hostname)
    if matches: return True

    matches = re.search('\.hps05\.net$', hostname)
    if matches: return True

    matches = re.search('\.ibase\.fr$', hostname)
    if matches: return True

    matches = re.search('\.imatique\.net$', hostname)
    if matches: return True

    matches = re.search('\.imp-40\.com$', hostname)
    if matches: return True

    matches = re.search('\.inbox\.ru$', hostname)
    if matches: return True

    matches = re.search('\.inda\.org$', hostname)
    if matches: return True

    matches = re.search('\.industrialmachinery\.net$', hostname)
    if matches: return True

    matches = re.search('\\.infojef\.com$', hostname)
    if matches: return True

    matches = re.search('\\.inforeg4you\.com$', hostname)
    if matches: return True

    matches = re.search('\\.infos-reduc\.com$', hostname)
    if matches: return True

    matches = re.search('\.instantdomainnotify\.com$', hostname)
    if matches: return True

    matches = re.search('\.inwoodhotel\.com$', hostname)
    if matches: return True

    matches = re.search('\.ixmask\.com$', hostname)
    if matches: return True

    matches = re.search('\.j\.fra\.net$', hostname)
    if matches: return True

    matches = re.search('\.jouve-hdi\.com$', hostname)
    if matches: return True

    matches = re.search('\.jouve\.fr$', hostname)
    if matches: return True

    matches = re.search('\.kan.be$', hostname)
    if matches: return True

    matches = re.search('\.kiwost\.net$', hostname)
    if matches: return True

    matches = re.search('\.kiwost1\.net$', hostname)
    if matches: return True

    matches = re.search('\.ks389579.kimsufi\.com$', hostname)
    if matches: return True

    matches = re.search('\.lasupero\.fre\.com$', hostname)
    if matches: return True

    matches = re.search('\.lesitedumariage\.com$', hostname)
    if matches: return True

    matches = re.search('\.letopdutop\.com$', hostname)
    if matches: return True

    matches = re.search('\.lianla\.net$', hostname)
    if matches: return True

    matches = re.search('\.libernautes\.com$', hostname)
    if matches: return True

    matches = re.search('\.m3.newgoods07.in$', hostname)
    if matches: return True

    matches = re.search('\.ma81\.fr$', hostname)
    if matches: return True

    matches = re.search('\.ma85\.fr$', hostname)
    if matches: return True

    matches = re.search('\.maconventioncollective\.com$', hostname)
    if matches: return True

    matches = re.search('\.macrocom.dk$', hostname)
    if matches: return True

    matches = re.search('\.macrozim.dk$', hostname)
    if matches: return True

    matches = re.search('\.maido\.com$', hostname)
    if matches: return True


    matches = re.search('\.mailkitchen\.com$', hostname)
    if matches: return True

    matches = re.search('\.maillingbase\.com$', hostname)
    if matches: return True

    matches = re.search('\.mailman.e\.net.co.uk$', hostname)
    if matches: return True

    matches = re.search('\.mailstorm.it$', hostname)
    if matches: return True

    matches = re.search('\.marketlligence\.com$', hostname)
    if matches: return True

    matches = re.search('\.matesa\.com.tr$', hostname)
    if matches: return True

    matches = re.search('\.mcdlv\.net$', hostname)
    if matches: return True

    matches = re.search('\.mdplus\.com$', hostname)
    if matches: return True

    matches = re.search('\.med-lan404\.com$', hostname)
    if matches: return True

    matches = re.search('\.media-contacts\.com$', hostname)
    if matches: return True

    matches = re.search('\.mediacash\.com$', hostname)
    if matches: return True

    matches = re.search('\.medias-direct\.com$', hostname)
    if matches: return True


    matches = re.search('\.menara.ma$', hostname)
    if matches: return True

    matches = re.search('\.message-business\.com$', hostname)
    if matches: return True

    matches = re.search('\.messagereach\.com$', hostname)
    if matches: return True

    matches = re.search('\.messaging-master\.com$', hostname)
    if matches: return True

    matches = re.search('\.metalbulletin\.com$', hostname)
    if matches: return True

    matches = re.search('\.mistergadget\.net$', hostname)
    if matches: return True

    matches = re.search('\.monbonplan-f\.de\.fr$', hostname)
    if matches: return True

    matches = re.search('\.mrsend.it$', hostname)
    if matches: return True

    matches = re.search('\.mtonom\.net$', hostname)
    if matches: return True

    matches = re.search('\.multi-fax\.fr$', hostname)
    if matches: return True

    matches = re.search('\.multi-fax\.net$', hostname)
    if matches: return True

    matches = re.search('\.mx82\.fr$', hostname)
    if matches: return True

    matches = re.search('\.mx86\.fr$', hostname)
    if matches: return True

    matches = re.search('\.mxeemails\.com$', hostname)
    if matches: return True

    matches = re.search('\.mxeemails\.com$', hostname)
    if matches: return True

    matches = re.search('\.mysmtp\.com$', hostname)
    if matches: return True

    matches = re.search('\.mysmtp\.eu$', hostname)
    if matches: return True

    matches = re.search('\.ndscar\.net$', hostname)
    if matches: return True

    matches = re.search('\\.net6\.fr$', hostname)
    if matches: return True

    matches = re.search('\.new-fields\.com$', hostname)
    if matches: return True

    matches = re.search('\.newsfast\.eu$', hostname)
    if matches: return True

    matches = re.search('\.newsletter.raja\.fr$', hostname)
    if matches: return True

    matches = re.search('\.newws\.info$', hostname)
    if matches: return True

    matches = re.search('\.noplato\.net$', hostname)
    if matches: return True

    matches = re.search('\.ns1.datapipe\.net$', hostname)
    if matches: return True

    matches = re.search('\.nttech.dk$', hostname)
    if matches: return True

    matches = re.search('\.(selection-affaires|reduction-prix|news-one-place|out-mid20a|out-mid23|ok2mail|oanm65|oanm66|oanm67|parisplayground)\.com$', hostname)
    if matches: return True

    matches = re.search('\.oksenddirect\.info$', hostname)
    if matches: return True

    matches = re.search('\.(oxi\.dedi|oxim13|perfora|openmail|ontracktransport|nmp1|mail2005|mamof|mail2009|mail2018|mediationtelecom|pprnl|pur3|ristrettomedia)\.net$', hostname)
    if matches: return True

    matches = re.search('\.(fre-coinshopping|fre-vetements|one-place-com|operationc\.deaux|passado|permanmas|perspectives-direct)\.fr$', hostname)
    if matches: return True

    matches = re.search('\.(centroban\.eu|hoodstyle\.bg|femalespot\.com|tupacbg\.com|jiniy-shop\.com|pitaine\.net|pleskpmail1\.com|pmafj\.com|pn1961ttm\.com)$', hostname)
    if matches: return True

    matches = re.search('\.pwer\.info$', hostname)
    if matches: return True
    matches = re.search('\.queql67\.fr$', hostname)
    if matches: return True
    matches = re.search('\.raoul-schneewind\.de$', hostname)
    if matches: return True
    matches = re.search('\.rbk-25\.com$', hostname)
    if matches: return True
    matches = re.search('\.red-ingress\.com$', hostname)
    if matches: return True
    matches = re.search('\.rediffmail\.com$', hostname)
    if matches: return True
    matches = re.search('\.researchandmarkets\.com$', hostname)
    if matches: return True
    matches = re.search('\.routage-[0-9]+\.com$', hostname)
    if matches: return True
    matches = re.search('\.rr1968nje\.com$', hostname)
    if matches: return True
    matches = re.search('\.s[0-9]+\.onlinehome-server\.info$', hostname)
    if matches: return True
    matches = re.search('\.sbr[0-9]+\.net$', hostname)
    if matches: return True
    matches = re.search('\.scoqi\.com$', hostname)
    if matches: return True
    matches = re.search('\.sd-[0-9]+\.dedibox\.fr$', hostname)
    if matches: return True
    matches = re.search('\.sendnes\.fr$', hostname)
    if matches: return True
    matches = re.search('\.seridisc.be$', hostname)
    if matches: return True
    matches = re.search('\.services\.network\.net$', hostname)
    if matches: return True
    matches = re.search('\.sightspeed\.com$', hostname)
    if matches: return True
    matches = re.search('\.sigler-consulting\.com$', hostname)
    if matches: return True
    matches = re.search('\.silux.angelbc.co.uk$', hostname)
    if matches: return True
    matches = re.search('\.sipdf\.fr$', hostname)
    if matches: return True
    matches = re.search('\.siteground273\.com$', hostname)
    if matches: return True
    matches = re.search('\.soramex\.com$', hostname)
    if matches: return True
    matches = re.search('\.splio\.com$', hostname)
    if matches: return True
    matches = re.search('\.srv.untit\.net$', hostname)
    if matches: return True
    matches = re.search('\.stami\.france\.com$', hostname)
    if matches: return True
    matches = re.search('\.stami\.com$', hostname)
    if matches: return True
    matches = re.search('\.sympatico\.ca$', hostname)
    if matches: return True
    matches = re.search('\.synactis\.com$', hostname)
    if matches: return True
    matches = re.search('\.talnamis\.net$', hostname)
    if matches: return True

    matches = re.search('\.technical12\.info$', hostname)
    if matches: return True

    matches = re.search('\.technologiemarketing$', hostname)
    if matches: return True

    matches = re.search('\.technomarketing\.fr$', hostname)
    if matches: return True

    matches = re.search('\.telepacific\.net$', hostname)
    if matches: return True

    matches = re.search('\.texti\.net$', hostname)
    if matches: return True

    matches = re.search('\.textileegu\.de\.com$', hostname)
    if matches: return True

    matches = re.search('\.textilesintelligence\.com$', hostname)
    if matches: return True

    matches = re.search('\.thecrmcompany\.com$', hostname)
    if matches: return True

    matches = re.search('\.tradingtextile\.com$', hostname)
    if matches: return True

    matches = re.search('\.tv-news\.fr$', hostname)
    if matches: return True

    matches = re.search('\.ulamis\.net$', hostname)
    if matches: return True

    matches = re.search('\.upcrt1\.com$', hostname)
    if matches: return True

    matches = re.search('\.verifiedbyvisa\.com$', hostname)
    if matches: return True

    matches = re.search('\.vertical-mail\.com$', hostname)
    if matches: return True

    matches = re.search('\.vinsfantastiques\.fr$', hostname)
    if matches: return True

    matches = re.search('\.virgilio.it$', hostname)
    if matches: return True

    matches = re.search('\.visuh\.info$', hostname)
    if matches: return True

    matches = re.search('\.vmle-tyt\.com$', hostname)
    if matches: return True

    matches = re.search('\.volumeek\.com$', hostname)
    if matches: return True

    matches = re.search('\.votremessage\.net$', hostname)
    if matches: return True

    matches = re.search('\.vtrtyuiu\.info$', hostname)
    if matches: return True

    matches = re.search('\.vueling\.com$', hostname)
    if matches: return True

    matches = re.search('\.wasnh\.net$', hostname)
    if matches: return True

    matches = re.search('\.welcomeoffice\.com$', hostname)
    if matches: return True

    matches = re.search('\.wew103\.com$', hostname)
    if matches: return True

    matches = re.search('\.wew115\.com$', hostname)
    if matches: return True

    matches = re.search('\.wew196\.com$', hostname)
    if matches: return True

    matches = re.search('\.wewmail\.com$', hostname)
    if matches: return True
    matches = re.search('\.world-textile\.net$', hostname)
    if matches: return True
    matches = re.search('\.wwel\.info$', hostname)
    if matches: return True
    matches = re.search('\.xmr3\.com$', hostname)
    if matches: return True
    matches = re.search('\.xy70p\.com$', hostname)
    if matches: return True
    matches = re.search('\.yarnsandfibers\.com$', hostname)
    if matches: return True
    matches = re.search('\.ymlp41\.net$', hostname)
    if matches: return True
    matches = re.search('\.ymlpsrv\.net$', hostname)
    if matches: return True
    matches = re.search('\.zinqmedia\.com$', hostname)
    if matches: return True
    print( "%s, nothing to say for blacklist..." %hostname)
    return False




def lookup(addr):
    try:
        results=socket.gethostbyaddr(addr)
        return results[0]
    except socket.error:
        return ""

pidfile_path = '/var/run/rblchecker.pid'
logger = logging.getLogger("DaemonLog")
logger.setLevel(logging.INFO)
formatter = logging.Formatter("[%(asctime)s]: %(message)s")
handler = logging.FileHandler("/var/log/iptrack.log")
handler.setFormatter(formatter)
logger.addHandler(handler)
POSTGRES=Postgres()
POSTGRES.log=logger

if is_running_from_pidpath(pidfile_path):
    print ("Already running, aborting")
    exit



#141.145.10.218

sql="alter table ip_reputation add column if not exists isparsedrbl smallint NOT NULL DEFAULT 0;"
POSTGRES.QUERY_SQL(sql)

sql="SELECT hostname,ipaddr FROM ip_reputation WHERE isparsedrbl=0"
#sql="SELECT hostname,ipaddr FROM ip_reputation WHERE isUnknown=1"
rows=POSTGRES.QUERY_SQL(sql)
for row in rows:
    hostname=row[0]
    ipaddr=row[1]
    global whywhy

    rows2=POSTGRES.QUERY_SQL("SELECT ipaddr FROM rbl_blacklists WHERE ipaddr='%s'" % ipaddr)
    if len(rows2) > 0:
        if hostname == None:
            hostname = lookup(ipaddr)
        print( "%s (%s) Already blacklisted " % (ipaddr, hostname))
        sql = "UPDATE ip_reputation SET isUnknown=0,isparsedrbl=1,hostname='%s' WHERE ipaddr='%s'" % (hostname,ipaddr)
        POSTGRES.QUERY_SQL(sql)
        if not POSTGRES.ok:
            print (POSTGRES.sql_error)
            logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
            sys.exit(0)
        continue



    if hostname==None:
        hostname=lookup(ipaddr)
        if len(hostname)>3:
            POSTGRES.QUERY_SQL("UPDATE ip_reputation SET hostname='%s' WHERE ipaddr='%s'" % (hostname,ipaddr))
            if not POSTGRES.ok:
                logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
                sys.exit(0)


    if len(hostname)==0:
        hostname=lookup(ipaddr)
        if len(hostname)>3:
            POSTGRES.QUERY_SQL("UPDATE ip_reputation SET hostname='%s' WHERE ipaddr='%s'" % (hostname,ipaddr))
            if not POSTGRES.ok:
                logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
                sys.exit(0)





    if CheckWhitelist(hostname):
        print( "%s [%s] is listed is Whitelisted! " % (hostname,ipaddr))
        logger.info("%s is listed is whitelisted! " % ipaddr)
        logzdate = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        description = "%s Detected in whitelist by regex" % hostname
        sql = "INSERT INTO rbl_whitelists (ipaddr,description,zDate) VALUES ('%s','%s','%s')" % (ipaddr, description, logzdate)
        POSTGRES.QUERY_SQL(sql)
        sql = "DELETE FROM rbl_blacklists WHERE ipaddr='%s'" % ipaddr
        POSTGRES.QUERY_SQL(sql)
        sql = "UPDATE ip_reputation SET isUnknown=0 WHERE ipaddr='%s'" % ipaddr
        POSTGRES.QUERY_SQL(sql)
        if not POSTGRES.ok:
            logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
            sys.exit(0)

        sql = "UPDATE ip_reputation SET isUnknown=0,isparsedrbl=1 WHERE ipaddr='%s'" % ipaddr
        POSTGRES.QUERY_SQL(sql)
        if not POSTGRES.ok:
            logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
            sys.exit(0)
        continue


    if CheckHostnames(hostname):
        logger.info("%s is listed in CheckHostnames %s! " % (ipaddr,whywhy ))
        logzdate = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        description = "%s Detected in regex by rblchecked %s" % (hostname,whywhy )
        sql = "INSERT INTO rbl_blacklists (ipaddr,description,zDate) VALUES ('%s','%s','%s')" % (ipaddr, description, logzdate)
        POSTGRES.QUERY_SQL(sql)
        sql="UPDATE ip_reputation SET isUnknown=0 WHERE ipaddr='%s'" % ipaddr
        POSTGRES.QUERY_SQL(sql)
        if not POSTGRES.ok:
            logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
            sys.exit(0)

        sql="UPDATE ip_reputation SET isUnknown=0,isparsedrbl=1 WHERE ipaddr='%s'" % ipaddr
        POSTGRES.QUERY_SQL(sql)
        if not POSTGRES.ok:
            logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
            sys.exit(0)
        continue





    if rblcheck(ipaddr):
        print( "%s is listed in RBL! " % ipaddr)
        logger.info("%s is listed in RBL! " % ipaddr)
        logzdate = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        description="%s Detected in RBLs by rblchecked" % hostname
        sql="INSERT INTO rbl_blacklists (ipaddr,description,zDate) VALUES ('%s','%s','%s')" % (ipaddr,description,logzdate)

        POSTGRES.QUERY_SQL(sql)

        if not POSTGRES.ok:
            matches=re.search('duplicate key value violates',POSTGRES.sql_error)
            if not matches:
                logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
                sys.exit(0)

        sql="UPDATE ip_reputation SET isUnknown=0 WHERE ipaddr='%s'" % ipaddr
        POSTGRES.QUERY_SQL(sql)
        if not POSTGRES.ok:
            logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
            sys.exit(0)

    sql = "UPDATE ip_reputation SET isparsedrbl=1 WHERE ipaddr='%s'" % ipaddr
    POSTGRES.QUERY_SQL(sql)
    if not POSTGRES.ok:
        logger.info("PostgreSQL error %s" % POSTGRES.sql_error)
        sys.exit(0)
