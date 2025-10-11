<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.html2text.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

$GLOBALS["DSHIELD"]=true;
shell_exec("/etc/init.d/firehol stop");

if($argv[1]=="--ntrck"){notrack_blocklist();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--adsfile"){adsfile($argv[2]);shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--tracker"){trackerfile($argv[2]);shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--tracker-clean"){clean_trackers();shell_exec("/etc/init.d/firehol start");exit;}



if($argv[1]=="--malware"){malwares_all();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--malwares"){malwares_all();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--quidsup"){quidsup();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--fussle"){fussle_de();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--tests"){tests();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--ioc"){sparc_ioc();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--cleanwww"){Cleanwww();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--spamlist"){SpamList($argv[2]);shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--cleanspam"){CleanSpam();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--cleanporn"){CleanPorn();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--cleansmart"){CleanSmartPhones();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--porn-toulouse"){porn_capitol();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--localspam"){local_spamdb();joewein_domains();joewein_from();spam_blacklist_forum_sih();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--joewein1"){joewein_domains();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--joewein2"){joewein_from();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--sih"){spam_blacklist_forum_sih();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--FullistsAdv"){FullistsAdv();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--sync"){sync_categories();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--ads"){ManyAds();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--sslinfo"){sslinfo_newage();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--discapitol"){discapitol();shell_exec("/etc/init.d/firehol start");exit;}
if($argv[1]=="--dcore"){dcore();shell_exec("/etc/init.d/firehol start");exit;}

bundle();
shell_exec("/etc/init.d/firehol start");

function dcore(){
    $unix=new unix();
    $workdir="/home/artica/download.lists";
    $tfile="$workdir/phish_score.csv";

    if(is_file($tfile)){
        $md51=md5_file($tfile);
    }

    $catz               = new mysql_catz();
    $redis = new Redis();

    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        squid_admin_mysql(0,"Redis error",$e->getMessage(),__FILE__,__LINE__);
        echo $e->getMessage() . "\n";
        return false;
    }

    $curl=new ccurl("https://phishstats.info/phish_score.csv");
    $curl->NoHTTP_POST=true;
    if(!$curl->GetFile("$workdir/phish_score.csv")) {
        echo $curl->error . "\n";
        squid_admin_mysql(0,"$curl->error on phishstats.info",null,__FILE__,__LINE__);
        return false;
        }

    $md52=md5_file($tfile);
    if($md51==$md52){
        echo "https://phishstats.info/phish_score.csv $md52 [SKIP]\n";
        return true;
    }
    $Max=$unix->COUNT_LINES_OF_FILE($tfile);


    $handle = fopen($tfile, "r");
    $Fam=new familysite();
    $FORCED=array('freetcp.com'=>true,'taplink.cc'=>true,'secureserver.net'=>true,'web.app'=>true,'s.id'=>true,'dns04.com'=>true,'serveusers.com'=>true,'cprapid.com'=>true,'edu.co'=>true,'weeblysite.com'=>true,'google.com'=>true,'amplifyapp.com'=>true,'cutt.ly'=>true,'nieruchomosci.pl'=>true,'dweb.link'=>true,'cfolks.pl'=>true,'sibforms.com'=>true,'ow.ly'=>true,'epizy.com'=>true,'swtest.ru'=>true,'tinyurl.com'=>true,'crazydomains.com'=>true,'onyx-sites.io'=>true,'glitch.me'=>true,'wpengine.com'=>true,'gotdns.com'=>true,'wpenginepowered.com'=>true,'000webhostapp.com'=>true,'duckdns.org'=>true,'mrslove.com'=>true,'firebaseapp.com'=>true,'ipfs.io'=>true,'isasecret.com'=>true,'vizvaz.com'=>true,'square.site'=>true,'2waky.com'=>true,'workers.dev'=>true,'afaba.org'=>true,'improved-protec-users575511.click'=>true,'my.id'=>true,'acmetoy.com'=>true,'page.link'=>true,'myqcloud.com'=>true,'tg9655.xyz'=>true,'clickbankqueen.com'=>true,'jungleheart.com'=>true,'dumb1.com'=>true,'youdontcare.com'=>true,'urlz.fr'=>true,'mystrikingly.com'=>true,'wikaba.com'=>true,'github.io'=>true,'worldwidecleaningsupport.com'=>true,'steamcommounity.com'=>true,'globinhonoticias.com'=>true,'saintroc.com'=>true,'hoster-test.ru'=>true,'my03.com'=>true,'itemdb.com'=>true,'rebrand.ly'=>true,'r2.dev'=>true,'codeanyapp.com'=>true,'appspot.com'=>true,'windows.net'=>true,'crabdance.com'=>true,'voltriz.com.br'=>true,'amazonaws.com'=>true,'instanthq.com'=>true,'justdied.com'=>true,'dsmtp.com'=>true,'longmusic.com'=>true,'nftstorage.link'=>true,'abcdkuat7.cyou'=>true,'1258731.com'=>true,'littleyellowstoneresort.com'=>true,'scalaproject.io'=>true,'infura-ipfs.io'=>true,'faqserv.com'=>true,'itsaol.com'=>true,'jkub.com'=>true,'shorturl.at'=>true,'fartit.com'=>true,'hindusthan.com'=>true,'zyns.com'=>true,'qpoe.com'=>true,'gmbh-security-transit.com'=>true,'pinata.cloud'=>true,'web.id'=>true,'dns05.com'=>true,'freefiregarena.vn'=>true,'ewp.live'=>true,'fluorochemin.uk'=>true,'os.tc'=>true,'jetos.com'=>true,'my-vigor.de'=>true,'almostmy.com'=>true,'edu.vn'=>true,'cloudflare-ipfs.com'=>true,'pages.dev'=>true,'xxnxl.cfd'=>true,'cf-ipfs.com'=>true,'b0tnet.com'=>true,'sulbarprov.go.id'=>true,'qhigh.com'=>true,'4everland.io'=>true,'asociacionalhmer.com'=>true,'mrface.com'=>true,'apple-gsx2-portal-online.com'=>true,'cleverapps.io'=>true,'antiamendes.com'=>true,'18plusaccess.com'=>true,'wcomhost.com'=>true,'atwebpages.com'=>true,'megaaaglisse.com'=>true,'pantheonsite.io'=>true,'tw1.ru'=>true,'webador.com'=>true,'myshopify.com'=>true,'zpr.io'=>true,'repl.co'=>true,'godaddysites.com'=>true,'systeme.io'=>true,'dynnamn.ru'=>true,'wixsite.com'=>true,'newskinandcare.com'=>true,'esenyurt-escort.tk'=>true,'circuitoguimaraesrosa.com.br'=>true,'blackdesertplanet.ru'=>true,'snapdealt.com'=>true,'digitalfxminning.com'=>true,'filesusr.com'=>true,'amex-card.at'=>true,'cloudwaysapps.com'=>true,'myvnc.com'=>true,'serveuser.com'=>true,'tsaritsyno-museum.ru'=>true,'steamcormmuntiy.com'=>true,'bvcx.cfd'=>true,'renovatoluxe.com'=>true,'auth57.app'=>true,'netlify.app'=>true,'pl8y3o.cyou'=>true,'caricom.org'=>true,'weebly.com'=>true,'nazmus-sakibb.com'=>true,'linodeobjects.com'=>true,'blogspot.sn'=>true,'foneworld-woking.co.uk'=>true,'x2-vip.icu'=>true,'session5.app'=>true,'authsession.app'=>true,'tempurl.host'=>true,'bit.ly'=>true,'w3s.link'=>true,'amopicpay.com'=>true,'2r3-mi.cloud'=>true,'edns.biz'=>true,'gitbook.io'=>true,'teiegeam.fit'=>true,'blogspot.com'=>true,'t.co'=>true,'plesk.page'=>true,'ezua.com'=>true,'titanpoolbuilders.com'=>true,'stocksboss.in'=>true,'v.ht'=>true,'liveblog365.com'=>true,'sendsmtp.com'=>true,'is.gd'=>true,'freeddns.com'=>true,'appclassting343.com'=>true,'ath.cx'=>true,'usrfiles.com'=>true,'bankersstage.ml'=>true,'thenursingsociety.com'=>true,'ml-bgan.com'=>true,'ltibet.net'=>true,'formacionarte.com'=>true,'myaccess-partner-portal-apple.com'=>true,'webmailproy.com'=>true,'telegram-web-chat-pro.com'=>true,'viral221.com'=>true,'wsend.co'=>true,'elfsig.ht'=>true,'webcindario.com'=>true,'yourtrap.com'=>true,'user-validate.top'=>true,'telegram-has.com'=>true,'vercel.app'=>true,'start.page'=>true,'romaniadeliveryguide.com'=>true,'wcxhzuno.com'=>true,'carrd.co'=>true,'okten.com'=>true,'newmarmi.com'=>true,'brizy.site'=>true,'4pu.com'=>true,'jemi.so'=>true,'hotm.art'=>true,'blogspot.tw'=>true,'webnode.page'=>true,'express.adobe.com'=>true,'edu.pe'=>true,'cancungas.com'=>true,'atallahpark.com'=>true,'sajerealty.com'=>true,'bulungan.go.id'=>true,'neocities.org'=>true,'panellku-id.cfd'=>true,'eurasianews.md'=>true,'keremsahinn.com'=>true,'blogspot.mx'=>true,'bitly.ws'=>true,'bigdatafinance.tw'=>true,'royalwebhosting.net'=>true,'pantek88.com'=>true,'googleusercontent.com'=>true,'gotdns.ch'=>true,'x24hr.com'=>true,'6102457.com'=>true,'oraclecloud.com'=>true,'verify-i.cloud'=>true,'scottelectricalsolution.com'=>true,'wvvw-kucoin.com'=>true,'organiccrap.com'=>true,'zypofl.xyz'=>true,'webfun.website'=>true,'multitechpatna.com'=>true,'coinbasevalidation.com'=>true,'webflow.io'=>true,'thienquy.vn'=>true,'telegranni.net'=>true,'xvcz.me'=>true,'sixweb.com.br'=>true,'auth63.app'=>true,'auth14.app'=>true,'diskstation.org'=>true,'line.pm'=>true,'telgramlive.asia'=>true,'inipanel.cfd'=>true,'builderallwppro.com'=>true,'auth39.app'=>true,'eventp.cfd'=>true,'blogspot.mk'=>true,'mx-lcloud.info'=>true,'wafaicloud.com'=>true,'xsph.ru'=>true,'odns.fr'=>true,'ru.com'=>true,'vppasss-n.com'=>true,'vppass-mi.com'=>true,'onlineseedsbank.com'=>true,'misecure.com'=>true,'vanstenusa.com'=>true,'telegram-ji.com'=>true,'paediatricphysiotherapyassociates.com'=>true,'mcsharepoint.com'=>true,'attemplate.com'=>true,'onedfgd4wczx56.com'=>true,'lflink.com'=>true,'publicvm.com'=>true,'idverifyappleidpayauthorise.com'=>true,'ygto.com'=>true,'utangard.ai'=>true,'flazio.com'=>true,'shary.io'=>true,'4dq.com'=>true,'4322985.com'=>true,'dns-dns.com'=>true,'proxydns.com'=>true,'lflinkup.com'=>true,'eventosasincopp.com'=>true,'onedumb.com'=>true,'sexidude.com'=>true,'sytes.net'=>true,'dynamic-dns.net'=>true,'healthyfoodeats.com'=>true,'webwave.dev'=>true,'zya.me'=>true,'backblazeb2.com'=>true,'t.ly'=>true,'zzux.com'=>true,'ikwb.com'=>true,'rf.gd'=>true,'ukit.me'=>true,'live-website.com'=>true,'hopp.to'=>true,'leonie-vonlieres.de'=>true,'125743.com'=>true,'23log.biz.id'=>true,'xxuz.com'=>true,'toythieves.com'=>true,'toshibanetcam.com'=>true,'selfip.org'=>true,'short.gy'=>true,'sharestion.com'=>true,'deefrent.com'=>true,'cloudclusters.net'=>true,'tielegramn.xyz'=>true,'fleek.co'=>true,'steamcommnurtiy.com'=>true,'sunnylandingpages.com'=>true,'salalocura.com'=>true,'jimdofree.com'=>true,'sa.com'=>true,'onlinebdophbank.in'=>true,'aegmedia.com.br'=>true,'lashroomandbrows.com'=>true,'emailmeform.com'=>true,'csb.app'=>true,'devoncarlstrom.com'=>true,'derkingboss.tk'=>true,'corporacionfrm.org'=>true,'autolicytacjapolska.net.pl'=>true,'audio2hand.com'=>true,'allwoodbuilderinc.com'=>true,'nxcli.io'=>true,'webspace.re'=>true,'nue0id.com'=>true,'jeffkoretke.com'=>true,'yuzxn.xyz'=>true,'centerforchristiandevelopment.org'=>true,'telegram66.com'=>true,'namxingrid.com'=>true,'mileumsites.com.br'=>true,'whitepaperland.com'=>true,'stemsupplies.com.au'=>true,'renm4maxys.com'=>true,'inmotionhosting.com'=>true,'yziudb.xyz'=>true,'prdcto-actva.com'=>true,'onx.la'=>true,'scw.cloud'=>true,'blogspot.rs'=>true,'blogspot.pe'=>true,'blogspot.md'=>true,'blogspot.com.co'=>true,'blogspot.co.id'=>true,'nataliabeautyct.com.au'=>true,'helpp-buissiness.click'=>true,'flattinfigures.com'=>true,'lovcis.com'=>true,'edu.my'=>true,'jumpingcrab.com'=>true,'myportfolio.com'=>true,'cvsolarsaver.com'=>true,'cadastrar.app'=>true,'mavi-station.com'=>true,'fixitecuador.com'=>true,'bbua-mx.com'=>true,'telegrram.com.cn'=>true,'omenmy.ru'=>true,'taplink.ws'=>true,'warszawa.pl'=>true,'staffasestenza.co'=>true,'bio.site'=>true,'pay-by-sella.com'=>true,'amedidabox.com'=>true,'zendesk.com'=>true,'spotifyvault.com'=>true,'com-skt.asia'=>true,'abcdkuat8.cyou'=>true,'caferkiyak.com.tr'=>true,'sharession.com'=>true,'sharesbyte.com'=>true,'tielegramn.cyou'=>true,'steampunkantique.com'=>true,'sklep.pl'=>true,'webhop.me'=>true,'netfimarketing.com'=>true,'mapangroup.com'=>true,'ihrpakeit.com'=>true,'mielcarz.info.pl'=>true,'codeyx.com'=>true,'beautybodyproducts.com'=>true,'bamztech.com'=>true,'fleek.cool'=>true,'autosprzedaz-ilkowscy.pl'=>true,'apple-online-portal-partner.com'=>true,'agri-stockvilliers.com'=>true,'staffrennovo.com'=>true,'apple-dt-portal-online.com'=>true,'5608720.vip'=>true,'mine.nu'=>true,'eckohogar.com'=>true,'restaurantassociatescorp.com'=>true,'blogspot.com.br'=>true,'blogspot.al'=>true,'mkceu.ru'=>true,'pagedemo.co'=>true,'servehttp.com'=>true,'smileofindia.co.in'=>true,'nico-id.com'=>true,'app.link'=>true,'cloudlets.zone'=>true,'mipaginaweb.us'=>true,'dpddiesel.com.br'=>true,'eshost.com.ar'=>true,'helpp-buissiiness.click'=>true,'telegram-yn.com'=>true,'upotps.com'=>true,'srv-icloud.co'=>true,'shareholds.com'=>true,'lnstagramhelp.services'=>true,'inc-icloud.cloud'=>true,'telegram-of.net'=>true,'telegram-bo.net'=>true,'southern-az-golden-retriever-rescue.org'=>true,'noww.se'=>true,'minstorafamilj.se'=>true,'yolasite.com'=>true,'lumenicons.nz'=>true,'webydo.com'=>true,'ondigitalocean.app'=>true,'benefitpe.com'=>true,'birdc.ug'=>true,'edu.ng'=>true,'certifica-ora.eu'=>true,'snip.ly'=>true,'dynv6.net'=>true,'04320432.com'=>true,'marcukesh.top'=>true,'lcioud-support.cloud'=>true,'idmsa-lcloud.live'=>true,'ialert-support.com'=>true,'apps-i.cloud'=>true,'apple-idhelp.com'=>true,'3r2-mi.cloud'=>true,'3r2-i.cloud'=>true,'rb.gy'=>true,'sp-hilfezentrum.de'=>true,'online-kunden.com.de'=>true,'bgmi-india.com'=>true,'apple-partner-portal-login.com'=>true,'blogspot.ie'=>true,'blogspot.bg'=>true,'login-bank.com.de'=>true,'kunden-online.com.de'=>true,'blogspot.com.by'=>true,'webnode.es'=>true,'afg.mx'=>true,'blogspot.co.za'=>true,'laflowstore.com'=>true,'firstsightlovers.com'=>true,'blogspot.ae'=>true,'joaoleitao.org'=>true,'blogspot.cl'=>true,'blogspot.hu'=>true,'telegram77.com'=>true,'work.gd'=>true,'tripura.ind.in'=>true,'azurewebsites.net'=>true,'intertecqatar.com'=>true,'discoverglobal.biz'=>true,'uzywaneimportauto.biz.pl'=>true,'ddns.net'=>true,'komisautopoland.pl'=>true,'aragon.network'=>true,'za.com'=>true,'myftpupload.com'=>true,'glwec.in'=>true,'2ww.me'=>true,'dospena-sklep.pl'=>true,'sviluppo.host'=>true,'topsporteng.com.br'=>true,'webnkngmtb.sbs'=>true,'mybluehost.me'=>true,'help-buisines.click'=>true,'terroncolorado.com'=>true,'transotech.net'=>true,'tdh75.cfd'=>true,'flywheelsites.com'=>true,'hgdsa.com'=>true,'bitbucket.io'=>true,'lcbconected.com'=>true,'green-mail.me'=>true,'fox21.com.au'=>true,'capitaleyedevelopment.com'=>true,'terbaru-indo.live'=>true,'lhr.life'=>true,'surge.sh'=>true,'run.place'=>true,'budowebsite.com'=>true,'temp-site.link'=>true,'mjt.lu'=>true,'solutionfun.info'=>true,'officence.com'=>true,'mesharepoint.com'=>true,'vietnamyounglions.net'=>true,'stera.co.ke'=>true,'spk-push-tan-aktualisierung.de'=>true,'simplymillionaire.com'=>true,'goo.su'=>true,'gatewaycables.com.au'=>true,'filedn.com'=>true,'credappmaisws.com'=>true,'cmkselect.xyz'=>true,'franklloydwrights.org'=>true,'jimdosite.com'=>true,'run.app'=>true,'clicketcloud.com'=>true,'gamingcontrol.de'=>true,'codesandbox.io'=>true,'urszulaprzymuszala.net.pl'=>true,'soede.net'=>true,'hugy-wugyy.ru'=>true,'e-processmed.com'=>true,'z.com'=>true,'melpoczta.pl'=>true,'anz-verifyaccount.com'=>true,'grupo-ls.com.br'=>true,'groupbbwa.rocks'=>true,'portailamendes-gouv.com'=>true,'morav.ro'=>true,'flowcode.com'=>true,'elizapopa.com'=>true,'autoplas.com.pk'=>true,'colegiomarianomoreno.com'=>true,'dlly.com.br'=>true,'appurl.io'=>true,'amazon5s.com'=>true,'sakyrade.net'=>true,'mefound.com'=>true,'tielegramn.top'=>true,'steamconmmuutiy.ru'=>true,'reserdiesel.pe'=>true,'myaccess-external-portal-apple.com'=>true,'mrbasic.com'=>true,'myddns.com'=>true,'yourl.jp'=>true,'forms.gle'=>true,'fastsugo.com'=>true,'mvpexchange.com.br'=>true,'ecopharmasas.co'=>true,'dysonturkey.com.tr'=>true,'ngrok.app'=>true,'carfect.com'=>true,'axis3d.ro'=>true,'skyfencenet.com'=>true,'gruppa99.com'=>true,'mobilcepdnzgris.site'=>true,'paiement-infraction-gouv.com'=>true,'mojadrukarnia.online'=>true,'gungchill.com'=>true,'easy.co'=>true,'sharepointen.com'=>true,'trial.rocks'=>true,'contaboserver.net'=>true,'skompasem.cz'=>true,'reurl.cc'=>true,'mahachol.com'=>true,'lulumarin.com'=>true,'kampunginggris.online'=>true,'ifaf.asia'=>true,'nodefil.xyz'=>true,'seenseen.co'=>true,'livelo.app'=>true,'bml.co.mw'=>true,'bngbiiz.xyz'=>true,'pictmy1.com'=>true,'blogspot.am'=>true,'mmxico.com'=>true,'forms.app'=>true,'digitaloceanspaces.com'=>true,'29932923.com'=>true,'echo.com.ng'=>true,'instagram-center.com'=>true,'vidio-viral-disini.cfd'=>true,'advanced-timber.com'=>true,'steanconmmuunity.com'=>true,'vip-crop6.cfd'=>true,'bercemhanoglu.com.tr'=>true,'safefile.icu'=>true,'quickbooks-online.net'=>true,'anakembok.de'=>true,'aisluv.com'=>true,'mrbonus.com'=>true,'sxnz.xyz'=>true,'concordiaproperty.com'=>true,'bet-365.bet'=>true,'telegraem-online.com'=>true,'parcel-dhlexpress-qa.com'=>true,'tripod.com'=>true,'download-baru.cfd'=>true,'gamaimobiliare.ro'=>true,'themecloud.dev'=>true,'blogspot.fr'=>true,'sikotrikoufathiz.website'=>true,'coin-dapp.com'=>true,'3659771.com'=>true,'expertwinereviews.com'=>true,'jp-findmy.id'=>true,'shorturl.asia'=>true,'seemat.net'=>true,'kinman.com'=>true,'intim-poisk.ru'=>true,'dnset.com'=>true,'mylftv.com'=>true,'mynetav.com'=>true,'00m-i.cloud'=>true,'vastserve.com'=>true,'vip-crop192.xyz'=>true,'blogspot.com.tr'=>true,'blogspot.lt'=>true,'gettrials.com'=>true,'zbj777.top'=>true,'uqr.to'=>true,'bernatgj.pl'=>true,'appstirr.com'=>true,'tabascologistics.com'=>true,'c3b6y5z6.xyz'=>true,'dyn-o-saur.com'=>true,'torinkony.com'=>true,'telegramae.com'=>true,'southindiajewels.co.in'=>true,'sisli-escort.tk'=>true,'ogloszenia-uzywane.pl'=>true,'ogloszenia-dzis.pl'=>true,'ogloszenia-dlaciebie.pl'=>true,'madeinepice.com'=>true,'gepeto.cl'=>true,'blogspot.cz'=>true,'myraidbox.de'=>true,'bonillaconsultores.com'=>true,'asiyaart.com'=>true,'apple-premier-partner-login.com'=>true,'blogspot.nl'=>true,'blogspot.li'=>true,'1gb.ru'=>true,'trycloudflare.com'=>true,'hireree.com'=>true,'telegram-bj.org'=>true,'cabinetbiorezonanta.ro'=>true,'icloudfind-my.info'=>true,'uzywane-ogloszenia.pl'=>true,'thomasmmcnamara.com'=>true,'statutsticket.com'=>true,'piscinaveronza.com'=>true,'pex-app.com'=>true,'mufgjp.com'=>true,'jccbcp.icu'=>true,'jccbcp.cyou'=>true,'inegypt.app'=>true,'ocry.com'=>true,'chasehiggins.com'=>true,'budsmokerz.com'=>true,'hgvfss.icu'=>true,'blogspot.com.es'=>true,'mcdonaldmechanical.com'=>true,'pikanobi.com'=>true,'blogspot.de'=>true,'blogspot.jp'=>true,'blogspot.ro'=>true,'blogspot.com.au'=>true,'badeneg.pl'=>true,'ridebeside.com'=>true,'3656nuld.xyz'=>true,'thenewsman.in'=>true,'blogspot.fi'=>true,'samochodowe-forum.pl'=>true,'pagesbythesea.net'=>true,'blogspot.be'=>true,'okazje-razem.pl'=>true,'nnpp.org.ng'=>true,'karosltd.com'=>true,'123ddns.com'=>true,'icskhi.com.pk'=>true,'hopperr.com'=>true,'46133644.com'=>true,'gw3.io'=>true,'gimtoman.com'=>true,'empressapatagonia-ar.com'=>true,'blogspot.it'=>true,'blogspot.lu'=>true,'bbgbyroya.com'=>true,'blogspot.ch'=>true,'nord-enhet.com'=>true,'induszaptron.com'=>true,'com-n.one'=>true,'blogspot.co.uk'=>true,'blogspot.ca'=>true,'cindra.software'=>true,'3656cc.vip'=>true,'otzo.com'=>true,'aepl.org.au'=>true,'zggxpaper.com'=>true,'zabgc.ru'=>true,'wsipv6.com'=>true,'usoptos.com'=>true,'cluberesgatedepontos.com'=>true,'bet810d.com'=>true,'tielegram.xyz'=>true,'takken-wakasa.org'=>true,'spirlttrucklines.com'=>true,'servicesamendes.info'=>true,'25u.com'=>true,'blogspot.no'=>true,'balticpipeinvest.com'=>true,'africandecorholdings.co.za'=>true,'smbc-card-zaq.tokyo'=>true,'translate.goog'=>true,'smbc-cardhfg.tokyo'=>true,'hyperphp.com'=>true,'hostingrd.pl'=>true,'0112-mx.com'=>true,'templatent.com'=>true,'telegrunn.pro'=>true,'telegramnet.vip'=>true,'tan-update.com.de'=>true,'edyta.info.pl'=>true,'l-panda.com'=>true,'pietrzyk.net.pl'=>true,'lapiemy-okazje.pl'=>true,'wiljot.pl'=>true,'blogspot.qa'=>true,'crichton.app'=>true,'axonsoftware.co.za'=>true,'aloverdao.com.br'=>true,'sansoftwares.com'=>true,'blogspot.kr'=>true,'blogspot.com.ng'=>true,'blogspot.co.ke'=>true,'mm247.cn'=>true,'getresponse.com'=>true,'httpimproved-protec-users643544.click'=>true,'gkvaismedziai.lt'=>true,'uzywane-oddam.pl'=>true,'spb.ru'=>true,'minshulltrading.com'=>true,'kimherman.nl'=>true,'dynssl.com'=>true,'x10.mx'=>true,'blogspot.dk'=>true,'gtw3.link'=>true,'amazon-developments.com'=>true,'blogspot.is'=>true,'a365ok.com'=>true,'blogspot.com.uy'=>true,'blogspot.hr'=>true,'taxnxabc.cn'=>true,'dvrlists.com'=>true,'credsuportapp.com'=>true,'brevardrvrepair.com'=>true,'derekremitz.com'=>true,'supporticloud-apple.cloud'=>true,'com-hgt.asia'=>true,'36jfg56.xyz'=>true,'login-appleid.cloud'=>true,'bet988s.com'=>true,'tosca-tosca.com'=>true,'skipper-spb.ru'=>true,'maisonceleste.net'=>true,'komis-autoo.pl'=>true,'drhatemeleishi.com'=>true,'gardenun.com'=>true,'aweber.com'=>true,'flnd-my.cloud'=>true,'blogspot.co.il'=>true,'greensphereltd.com'=>true,'valgasa.com'=>true,'telegram-u.icu'=>true,'telegram-uj.com'=>true,'nexyyy.xyz'=>true,'uzywane-okazje.pl'=>true,'tielegram.net'=>true,'telegram-tyk.com'=>true,'oddaje-uzywane.pl'=>true,'linkpc.net'=>true,'kendingkamias.com'=>true,'lv3q2u.com'=>true,'moralis.io'=>true,'yqchl.com'=>true,'automaxx-sw.com'=>true,'0hi.me'=>true,'blogspot.sg'=>true,'mediqboy.com'=>true,'laogangtea.com'=>true,'jtqiheng.com'=>true,'gaozhongjp.com'=>true,'ukredeliver.com'=>true,'view-locations.com'=>true,'gstr-prsna.com'=>true,'insolvency-development.co.uk'=>true,'drwskinesia.com'=>true,'ansprak.se'=>true,'padepdefusac.info'=>true,'telegram-si.org'=>true,'telegram-ji.org'=>true,'pervasiveinsights.com'=>true,'openvrshop.com'=>true,'online-login.com.de'=>true,'onlinebdouninbankph.com'=>true,'jebs.com.br'=>true,'noor.jp'=>true,'contraventions-antai-gouv.com'=>true,'dunde-xarge.com'=>true,'gouv-infractionantai.com'=>true,'ebay-mexico.com'=>true,'dns.army'=>true,'careportal-coinbase.com'=>true,'buildmybrandz.com'=>true,'bamumarks.com'=>true,'vhs-flintbek.de'=>true,'anthoc.org'=>true,'eczechow.net.pl'=>true,'help-protect-user216345.click'=>true,'telegram-io.com'=>true,'operador-atualizado.com'=>true,'apple-external-portal-login.com'=>true,'n3w-id.xyz'=>true,'espdasms.top'=>true,'efbpinatar.com'=>true,'co-id.cfd'=>true,'oddaje-zadarmo.pl'=>true,'darmowe-uzywane.pl'=>true,'iceiy.com'=>true,'blogspot.com.ar'=>true,'solfinance.de'=>true,'moto-auta.pl'=>true,'elevation-development.com'=>true,'rosyscom.com'=>true,'bre.is'=>true,'tallykhata.com'=>true,'helpuserprotect2347821.click'=>true,'help-protect-user243260.click'=>true,'helpcentral532.click'=>true,'do-paris.com'=>true,'forouzbeauty.com'=>true,'westonwall.com'=>true,'verzet.com.uy'=>true,'taxist.org'=>true,'loudesglis.com'=>true,'galatea.rs'=>true,'blogspot.co.at'=>true,'aegtecnoservice.it'=>true,'vdlhid.bond'=>true,'n31.com.au'=>true,'infoforubd.com'=>true,'cyclic.app'=>true,'konubinix.eu'=>true,'dissocia-dispositivo-isp.com'=>true,'umrah.ac.id'=>true,'dailyprizehub.com'=>true,'ayastudio.eu'=>true,'edu.bd'=>true,'website-276-91-gk.biz.id'=>true,'vxq-site.com'=>true,'faturaatrasada.net'=>true,'estudiomlucero.com.ar'=>true,'duartemobilerepair.com'=>true,'tqniait.com'=>true,'coinbase-supportcenter.com'=>true,'help-protect-user35794851.click'=>true,'help-protect-user021413.click'=>true,'help-protect-user021412.click'=>true,'adrianaverdinofitness.com'=>true,'iammazhar.com'=>true,'cleanandsoberlove.com'=>true,'piggieflures.com'=>true,'jewelsandabove.com'=>true,'blogspot.pt'=>true,'edu.au'=>true,'campusprconsulting.com'=>true,'blogspot.sk'=>true,'blogspot.gr'=>true,'secure-verify-help.com'=>true,'yaforlove.com'=>true,'telegrpm.cyou'=>true,'blogspot.ug'=>true,'blogspot.my'=>true,'cursosejaluz.com.br'=>true,'centralinn.qa'=>true,'blogspot.si'=>true,'blogspot.com.cy'=>true,'azure.com'=>true,'koncolteamygy.click'=>true,'acasadasmassas.com.br'=>true,'xyz-bca.cfd'=>true,'degradable.com.ar'=>true,'blazebakery.co'=>true,'a2hosted.com'=>true,'prfd.aero'=>true,'blogspot.se'=>true,'edu.in'=>true,'snackchatsandwiches.com'=>true,'steamncommurntiy.com'=>true,'staemcommurity.com'=>true,'musculacaofaixapreta.com'=>true,'metamaskio.cn'=>true,'meta-case-report-256467645.buzz'=>true,'marketly.tk'=>true,'vrl2023.com'=>true,'fikrmag.com'=>true,'dnzsmdndencklizebsvrykszskmkmoanan.net'=>true,'cplnk.com'=>true,'pythonanywhere.com'=>true,'andishmandaniran.ir'=>true,'acutecons.co'=>true,'oxigenospa.com'=>true,'atsnx.com'=>true,'disabilitytransportnsw.com.au'=>true,'blogspot.hk'=>true,'progressives.media'=>true,'mobiletekcomputer.com.au'=>true,'mybdosecures.com'=>true,'telectron.net'=>true,'secure-help-verify.com'=>true,'rivertownadvisors.com'=>true,'online-verarbeitung-telekom.site'=>true,'myifegovi.click'=>true,'kesartea.com'=>true,'2367524.com'=>true,'mooo.com'=>true,'diractclicker.com'=>true,'waw.pl'=>true,'bestschool.vn'=>true,'apple-uat-portal-online.com'=>true,'shopifypreview.com'=>true,'vip-crop1.cfd'=>true,'australieposttracking.com'=>true,'allenrileymckee.com'=>true,'free.fr'=>true,'registroresgatiatacadaoser.com'=>true,'com-krh.asia'=>true,'westchesterwoodfloors.com'=>true,'royparker.org'=>true,'revrsa-prcso.com'=>true,'oicompras.com'=>true,'linkt-code.cyou'=>true,'301254.com'=>true,'1023545.com'=>true,'docomo-bakuage-selection.com'=>true,'darmowo-razem.pl'=>true,'cepat.art'=>true,'aetrackemirates.com'=>true,'aei-servimetales.com'=>true,'psychedelicscience.org'=>true,'11ckjiscoinbasesecure.com'=>true,'myifegov.click'=>true,'adv.br'=>true,'futurestellic.com'=>true,'vipgiz37.live'=>true,'cretehorizon.com'=>true,'linktube.com'=>true,'difarmafarmaceutica.com.br'=>true,'thewaxcollab.com'=>true,'themerescribbler.co.uk'=>true,'tgone.com.mx'=>true,'pancake.vn'=>true,'soalegriamultimarcas.com'=>true,'smsoenazno-woejnrop.com'=>true,'shatterthebox.org'=>true,'myhuaweicloud.com'=>true,'perniktermo.com'=>true,'rrqscoinbase.com'=>true,'postovaasistentkapostaskk.shop'=>true,'nanosmat-global.org'=>true,'internacionalvet.com'=>true,'fishingrods-shop.com'=>true,'8onthepoint.au'=>true,'rakshapackaging.in'=>true,'sidegabe.cfd'=>true,'credappmaiswsite.com'=>true,'abrir.link'=>true,'a-ub.com'=>true,'wethemovers.com'=>true,'zaned-authonline.com'=>true,'surkont.net'=>true,'infinityvehicles.in'=>true,'paiement-contravention-gouvfr.com'=>true,'officested.com'=>true,'multibyte.my'=>true,'com-htr.asia'=>true,'suissetrackpost.net'=>true,'steancommnuunity.com'=>true,'sklep-auto.pl'=>true,'sklep-automobil.pl'=>true,'rrrainbowseniors.org'=>true,'rakutenff.com'=>true,'justcellz.co.uk'=>true,'iigcoinbase.com'=>true,'ihld.org.in'=>true,'20130214.com'=>true,'10web.cloud'=>true,'funnelsmadeeazy.com'=>true,'coinhantubelau.com'=>true,'center-regain.com'=>true,'calze.it'=>true,'caiji-shop.com'=>true,'asb-network-secure.com'=>true,'antai-service-paiement.com'=>true,'angonfurniture.com'=>true,'amende-paiements-gouv.com'=>true,'901werock.com'=>true,'bero-webspace.de'=>true,'swisspostfinance.com'=>true,'yannan.us'=>true,'4mfformats.com'=>true,'10web.site'=>true,'sseshareprice.com'=>true,'seventually.info'=>true,'needhelporahand.com'=>true,'0320189.com'=>true,'shenghuoshishang.com'=>true,'playseriesfilme.com'=>true,'lizhouxs.com'=>true,'hihengshui.com'=>true,'ncasie.cc'=>true,'dream.press'=>true,'servebbs.com'=>true,'detmir.top'=>true,'com-fok.asia'=>true,'aewdistro.com'=>true,'weilogin.com'=>true,'xinzezhihui.com'=>true,'suzukipalmas.com.mx'=>true,'scoutsbarcelona.es'=>true,'nusantarajayakonveksi.com'=>true,'mkzuanji.com'=>true,'lloyd-gwilt.co.uk'=>true,'kingofbupt.com'=>true,'huitianpay.com'=>true,'0321478.com'=>true,'bajavfh85.nedfile.eu.org'=>true,'badabazaars.com'=>true,'luxusnatury.pl'=>true,'brpn.cloud'=>true,'mikegwynhill.co.uk'=>true,'labforest.in'=>true,'elmanarcopier.com'=>true,'w-i-se-security.top'=>true,'colegiogilgalosorno.cl'=>true,'getapprovedwithangela.com'=>true,'starte-jetzt.com.de'=>true,'kunden-update.com.de'=>true,'anmelden.com.de'=>true,'aktualisierung2023.com.de'=>true,'com-iso.asia'=>true,'eth.limo'=>true,'shopthongthai.com'=>true,'nawomain.top'=>true,'pmrresistencias.com.br'=>true,'luxorea.com'=>true,'leahstravelist.com'=>true,'delvadigital.id'=>true,'blogspot.com.eg'=>true,'blogspot.ba'=>true,'blogspot.ru'=>true,'packageinfos.com'=>true,'aaronleyland.com'=>true,'niib-baneseportal.com'=>true,'imobiliariasulminas.com.br'=>true,'hawored.top'=>true,'thuonghao.com'=>true,'shopnaildrip.com'=>true,'secure-mntb-account.com'=>true,'rs2infiniteclean.com'=>true,'olliespettoys.com'=>true,'oddam-dlaciebie.pl'=>true,'reqored.top'=>true,'lafrancephotographie.com'=>true,'3-2.services'=>true,'ctr-hosting.net'=>true,'ablackfridayoffres.com'=>true,'kstyledesigns.com'=>true,'shuaihu99.com'=>true,'lspower.xyz'=>true,'best-practice.se'=>true,'ingenieriadelagua.uy'=>true,'podosugih.com'=>true,'osweetburger.com'=>true,'ziu-online.org'=>true,'kingoffserver.com'=>true,'icloud-latam.com'=>true,'com-viewlocation.site'=>true,'ndaatgal.mn'=>true,'havlumat.com'=>true,'news1.cfd'=>true,'getting-married.co.il'=>true,'iwowjsndns.cfd'=>true,'keystoneuniformcap.com'=>true,'paneldanznih.cfd'=>true,'teleporthq.app'=>true,'edupress.uz'=>true,'blogspot.in'=>true,'blogspot.co.nz'=>true,'blogspot.com.mt'=>true,'blogspot.com.ee'=>true,'donasdonegociosicredi.com.br'=>true,'domotec.es'=>true,'serenitybirthstudio.com'=>true,'justns.ru'=>true,'wellsfargoreport.com'=>true,'conaireg.com'=>true,'compliance-central.com'=>true,'ccelp.bo'=>true,'boacc.ch'=>true,'bigdogdomains.co'=>true,'baru1.biz.id'=>true,'autogielda-alkinscy.pl'=>true,'audit-expert.by'=>true,'apple-care.cloud'=>true,'antai-services-public.com'=>true,'10web.me'=>true,'alexirish.com'=>true,'dandikorea.com'=>true,'verenavondergoenna.de'=>true,'bdosn.org'=>true,'poconokosher.com'=>true,'packinfos.com'=>true,'dreamwp.com'=>true,'mycoinbasescan.com'=>true,'murospremoldado.com.br'=>true,'mrkrxlx.click'=>true,'melindafolse.com'=>true,'sculptaway.com'=>true,'kolea-construct.be'=>true,'bblcreams.com'=>true,'asistente-find.com'=>true,'aptmentors.in'=>true,'wroclaw.pl'=>true,'fenixtapetes.com.br'=>true,'holyjala.com.au'=>true,'piotrborek.pl'=>true,'welovehtml.com'=>true,'holonventures.com'=>true,'expresseuropatransport.com'=>true,'bristleconeanalytics.com'=>true,'brookeleesmith.com'=>true,'jeremiaszg.pl'=>true,'steamncommnutiy.com'=>true,'croatiatravelplanner.com'=>true,'meta-case-9238123912.buzz'=>true,'natored.top'=>true,'mamaines.xyz'=>true,'qutodiamum.pl'=>true,'a1frames.co.uk'=>true,'kamcia.com.pl'=>true,'rachempharma.com'=>true,'mottashedelite.com'=>true,'30122548.com'=>true,'lntesa-sp-it.digital'=>true,'abgubhfsa.nl'=>true,'abgbgyra.nl'=>true,'helpco.com.co'=>true,'refreshing-massage.com'=>true,'strangled.net'=>true,'opole.pl'=>true,'thebareblueprintskin.com'=>true,'shorelineconciergecare.com'=>true,'oddajemy-razem.pl'=>true,'falconeducacao.com.br'=>true,'engepiso.com.br'=>true,'selftaughtbook.com'=>true,'zahlung-onlines.info'=>true,'ff4lapp.com'=>true,'bedlam-hair.com'=>true,'indigoartpapers.com'=>true,'gzerosolucoes.com.br'=>true,'cleaningspot.co.uk'=>true,'drr.ac'=>true,'steamcommuurntiiy.com'=>true,'sancity.cl'=>true,'mtsnegeri3grobogan.sch.id'=>true,'mcis.mx'=>true,'manageoneconnectsways.com'=>true,'azaniafreshfoodindustries.com'=>true,'zhuyaedu.cn'=>true,'weimei365.cn'=>true,'shpingyi.cn'=>true,'qudou007.cn'=>true,'jzftyn.cn'=>true,'fushigroup.cn'=>true,'fenduole.cn'=>true,'domain444.cn'=>true,'cqxineng.cn'=>true,'yugedianzi.cn'=>true,'huborform.com.pl'=>true,'pesc.pw'=>true,'kiyanalmuhtarifayn.com'=>true,'toura-reborn.com'=>true,'stepsiblingvr.com'=>true,'simiaestudio.com'=>true,'ohpoddamn.com'=>true,'newscheck.cloud'=>true,'handypro.co.zw'=>true,'getpaid2grow.com'=>true,'fetchprompt.com'=>true,'skogtradgard.se'=>true,'pui-thai.com'=>true,'petriggio.com.mx'=>true,'j-communication.info'=>true,'albion.cl'=>true,'sallycooper.com.au'=>true,'comunicadoras.com'=>true,'amendes-antai-gouvs.com'=>true,'ar-ledtech.de'=>true,'tataras.com.pl'=>true,'darekfurtak.pl'=>true,'krakowiak.org.pl'=>true,'hardwarecheck.net'=>true,'discusit.com'=>true,'emailsys2a.net'=>true,'alwaysdata.net'=>true,'hrwgjutillwatkfbb.com'=>true,'hotelvictoriamw.com'=>true,'megaluizapromocoes.com'=>true,'magasempre.com'=>true,'ngaburimiijetes.al'=>true,'304925.com'=>true,'xpressdhl-no.com'=>true,'eagleeast-eg.com'=>true,'dhlxpress-qa.com'=>true,'naka.news'=>true,'nutmixes.com'=>true,'lospits.com.mx'=>true,'sindbadtravel.net'=>true,'baronemperorgt.com'=>true);
    $hits=0;
    $c=0;
    $q=new postgres_sql();$added=0;$alcatz=0;
    while (($data = fgetcsv($handle)) !== FALSE) {
        if(!isset($data[2])){continue;}
        $url=$data[2];
        $c++;
        $url_p = parse_url ($url);
        if (!isset ($url_p["host"])){continue;}
        $www=$url_p["host"];
        if(preg_match("#^[0-9\.]+$#",$www)){continue;}
        if(preg_match("#^www\.(.+)#",$www,$re)){$www=$re[1];}
        if($www=="docs.google.com"){continue;}
        if($www=="apis.google.com"){continue;}
        if(preg_match("#\.google\.com$#",$www)){continue;}

        $CACHE=intval($redis->get("DomainToInt:$www"));
        if($CACHE>0){
            $hits++;
            continue;
        }

        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$www)){
            $redis->set("DomainToInt:$www",999);
            continue;
        }

        $pprc=($c/$Max)*100;
        $prc=round($pprc,2);
        $family=$Fam->GetFamilySites($www);
        if(isset($FORCED[$family])){
            $q->QUERY_SQL("INSERT INTO category_phishing (sitename) VALUES('$www') ON CONFLICT DO NOTHING");
            $redis->set("DomainToInt:$www",999);
            $added++;
            $GLOBALS["ADDEDPLUS"]++;
            echo "ADD:phishstats:($prc): Hits:$hits, already:$alcatz  added:$added: $www\n";
            continue;
        }

        if($catz->GET_CATEGORIES($www)>0){
            $alcatz++;
            $redis->set("DomainToInt:$www",999);
            continue;
        }

        $GLOBALS["ADDEDPLUS"]++;
        $q->QUERY_SQL("INSERT INTO category_phishing (sitename) VALUES('$www') ON CONFLICT DO NOTHING");
        $redis->set("DomainToInt:$www",999);
        $added++;
        echo "ADD:phishstats:($prc): Hits:$hits, already:$alcatz  added:$added: $www\n";

    }
    if($added>1){
        squid_admin_mysql(1,"$added domains into phishing from phishstats.info",null,__FILE__,__LINE__);
    }
    return true;

}

function sslinfo_newage(){
    $GLOBALS["ADDEDPLUS"]=0;
    $q=new postgres_sql();
    $raw="raw.githubusercontent.com";
    $workdir="/home/artica/download.lists";

    $array["HoleCertPL_malware"]["MD5"]=null;
    $array["HoleCertPL_malware"]["URI"]="https://hole.cert.pl/domains/domains_hosts.txt";
    $array["HoleCertPL_malware"]["TABLE"]="category_malware";

    $array["SquidPhishtank"]["MD5"]="http://sslinfo.newage.ovh/cats/SquidPhishtank.md5.txt";
    $array["SquidPhishtank"]["URI"]="http://sslinfo.newage.ovh/cats/SquidPhishtank.txt";
    $array["SquidPhishtank"]["TABLE"]="category_phishing";

    $array["SquidPurePhishing"]["MD5"]="http://sslinfo.newage.ovh/cats/Squid0xPurePhishing.md5.txt";
    $array["SquidPurePhishing"]["URI"]="http://sslinfo.newage.ovh/cats/Squid0xPurePhishing.txt";
    $array["SquidPurePhishing"]["TABLE"]="category_phishing";

    $array["SquidGlobalantiscam"]["MD5"]="http://sslinfo.newage.ovh/cats/SquidGlobalantiscam.md5.txt";
    $array["SquidGlobalantiscam"]["URI"]="http://sslinfo.newage.ovh/cats/SquidGlobalantiscam.txt";
    $array["SquidGlobalantiscam"]["TABLE"]="category_phishing";

    $array["SquidNotracking"]["MD5"]="http://sslinfo.newage.ovh/cats/SquidNotracking.md5.txt";
    $array["SquidNotracking"]["URI"]="http://sslinfo.newage.ovh/cats/SquidNotracking.txt";
    $array["SquidNotracking"]["TABLE"]="category_spyware";

    $array["ZeroHosts"]["MD5"]="";
    $array["ZeroHosts"]["URI"]="https://someonewhocares.org/hosts/zero/hosts";
    $array["ZeroHosts"]["TABLE"]="category_spyware";

    $array["MyPDNS_Spyware"]["MD5"]="";
    $array["MyPDNS_Spyware"]["URI"]="https://raw.githubusercontent.com/mypdns/matrix/master/source/spyware/wildcard.list";
    $array["MyPDNS_Spyware"]["TABLE"]="category_spyware";


    $urlPorns["mhxion"]="https://$raw/mhxion/pornaway/master/hosts/porn_sites.txt";
    $urlPorns["4skinSkywalker"]="https://$raw/4skinSkywalker/anti-porn-hosts-file/master/HOSTS.txt";
    $urlPorns["waltercross79"]="https://$raw/waltercross79/pihole-porn-blocklist/master/block_p_1.list";
    $urlPorns["thisisu"]="https://$raw/thisisu/hosts_adultxxx/master/hosts";
    $urlPorns["cbuijs"]="https://$raw/cbuijs/accomplist/master/adult-themed/plain.black.domain.list";

    foreach ($urlPorns as $subkey=>$url){
        $array["{$subkey}_porn"]["MD5"]=null;
        $array["{$subkey}_porn"]["URI"]=$url;
        $array["{$subkey}_porn"]["TABLE"]="category_porn";

    }

    $array["HackProtect_slophish"]["MD5"]=null;
    $array["HackProtect_slophish"]["URI"]="https://$raw/HackProtect/slophish/main/malicious_domains_from_slophish_list.txt";
    $array["HackProtect_slophish"]["TABLE"]="category_malware";

    $array["accomplist_malwares"]["MD5"]=null;
    $array["accomplist_malwares"]["URI"]="https://$raw/cbuijs/accomplist/master/malicious-dom/optimized.black.top-n.domain.list";
    $array["accomplist_malwares"]["TABLE"]="category_malware";



    $array["SquidWeb3malware"]["MD5"]="http://sslinfo.newage.ovh/cats/SquidWeb3malware.md5.txt";
    $array["SquidWeb3malware"]["URI"]="http://sslinfo.newage.ovh/cats/SquidWeb3malware.txt";
    $array["SquidWeb3malware"]["TABLE"]="category_malware";

    $array["pornaway"]["MD5"]=null;
    $array["pornaway"]["URI"]="https://raw.githubusercontent.com/mhxion/pornaway/master/hosts/porn_sites.txt";
    $array["pornaway"]["TABLE"]="category_porn";

    $array["Sinfonietta"]["MD5"]=null;
    $array["Sinfonietta"]["URI"]="https://raw.githubusercontent.com/Sinfonietta/hostfiles/master/pornography-hosts";
    $array["Sinfonietta"]["TABLE"]="category_porn";

    $array["blocklistproject_porn"]["MD5"]=null;
    $array["blocklistproject_porn"]["URI"]="https://blocklistproject.github.io/Lists/alt-version/porn-nl.txt";
    $array["blocklistproject_porn"]["TABLE"]="category_porn";

    $array["domainswild_porn"]["MD5"]=null;
    $array["domainswild_porn"]["URI"]="https://raw.githubusercontent.com/sjhgvr/oisd/main/domainswild_nsfw.txt";
    $array["domainswild_porn"]["TABLE"]="category_porn";

    $array["PrigentPorn"]["MD5"]=null;
    $array["PrigentPorn"]["URI"]="https://v.firebog.net/hosts/Prigent-Adult.txt";
    $array["PrigentPorn"]["TABLE"]="category_porn";

    $array["blocklistproject_drugs"]["MD5"]=null;
    $array["blocklistproject_drugs"]["URI"]="https://blocklistproject.github.io/Lists/alt-version/drugs-nl.txt";
    $array["blocklistproject_drugs"]["TABLE"]="category_drugs";

    $array["blocklistproject_gambling"]["MD5"]=null;
    $array["blocklistproject_gambling"]["URI"]="https://blocklistproject.github.io/Lists/alt-version/gambling-nl.txt";
    $array["blocklistproject_gambling"]["TABLE"]="category_gamble";

    $array["blocklistproject_ads"]["MD5"]=null;
    $array["blocklistproject_ads"]["URI"]="https://blocklistproject.github.io/Lists/alt-version/ads-nl.txt";
    $array["blocklistproject_ads"]["TABLE"]="category_publicite";



    $array["filtridns"]["MD5"]=null;
    $array["filtridns"]["URI"]="https://filtri-dns.ga/filtri.txt";
    $array["filtridns"]["TABLE"]="category_publicite";

    $array["sysctl"]["MD5"]=null;
    $array["sysctl"]["URI"]="http://sysctl.org/cameleon/hosts";
    $array["sysctl"]["TABLE"]="category_publicite";



    $array["ShadowWisperer_malware"]["MD5"]=null;
    $array["ShadowWisperer_malware"]["URI"]="https://$raw/ShadowWisperer/BlockLists/master/Lists/Malware";
    $array["ShadowWisperer_malware"]["TABLE"]="category_malware";




    $array["csirtgadgets"]["MD5"]=null;
    $array["csirtgadgets"]["URI"]="https://$raw/csirtgadgets/tf-domains-example/master/data/blacklist.txt";
    $array["csirtgadgets"]["TABLE"]="category_malware";


    $array["ShadowWisperer_dating"]["MD5"]=null;
    $array["ShadowWisperer_dating"]["URI"]="https://raw.githubusercontent.com/ShadowWisperer/BlockLists/master/Lists/Dating";
    $array["ShadowWisperer_dating"]["TABLE"]="category_dating";

    $array["ShadowWisperer_porn"]["MD5"]=null;
    $array["ShadowWisperer_porn"]["URI"]="https://raw.githubusercontent.com/ShadowWisperer/BlockLists/master/Lists/Adult";
    $array["ShadowWisperer_porn"]["TABLE"]="category_porn";


    $array["ShadowWisperer_remote"]["MD5"]=null;
    $array["ShadowWisperer_remote"]["URI"]="https://raw.githubusercontent.com/ShadowWisperer/BlockLists/master/Lists/Remote";
    $array["ShadowWisperer_remote"]["TABLE"]="category_remote_control";

    $array["ShadowWisperer_proxy"]["MD5"]=null;
    $array["ShadowWisperer_proxy"]["URI"]="https://raw.githubusercontent.com/ShadowWisperer/BlockLists/master/Lists/Tunnels";
    $array["ShadowWisperer_proxy"]["TABLE"]="category_proxy";

    $array["ShadowWisperer_typo"]["MD5"]=null;
    $array["ShadowWisperer_typo"]["URI"]="https://$raw/ShadowWisperer/BlockLists/master/Lists/Typo";
    $array["ShadowWisperer_typo"]["TABLE"]="category_phishing";

    $array["accomplist_nrd"]["MD5"]=null;
    $array["accomplist_nrd"]["URI"]="https://$raw/cbuijs/accomplist/master/nrd/adblock.txt";
    $array["accomplist_nrd"]["TABLE"]="category_phishing";

    $array["HackProtect_PhishShield"]["MD5"]=null;
    $array["HackProtect_PhishShield"]["URI"]="https://$raw/HackProtect/PhishShield/main/PhishShield.txt";
    $array["HackProtect_PhishShield"]["TABLE"]="category_phishing";


    $array["HackProtect_slophishphish"]["MD5"]=null;
    $array["HackProtect_slophishphish"]["URI"]="https://$raw/HackProtect/slophish/main/slophish.txt";
    $array["HackProtect_slophishphish"]["TABLE"]="category_phishing";


    $array["MyPDNS_Phishing"]["MD5"]=null;
    $array["MyPDNS_Phishing"]["URI"]="https://$raw/mypdns/matrix/master/source/phishing/wildcard.list";
    $array["MyPDNS_Phishing"]["TABLE"]="category_phishing";


    $array["MyPDNS_Religion"]["MD5"]=null;
    $array["MyPDNS_Religion"]["URI"]="https://$raw/mypdns/matrix/master/source/religion/wildcard.list";
    $array["MyPDNS_Religion"]["TABLE"]="category_religion";

    $array["MyPDNS_Movies"]["MD5"]=null;
    $array["MyPDNS_Movies"]["URI"]="https://$raw/mypdns/matrix/master/source/movies/wildcard.list";
    $array["MyPDNS_Movies"]["TABLE"]="category_movies";

    $array["MyPDNS_News"]["MD5"]=null;
    $array["MyPDNS_News"]["URI"]="https://$raw/mypdns/matrix/master/source/news/wildcard.list";
    $array["MyPDNS_News"]["TABLE"]="category_news";


    $array["MyPDNS_Weapons"]["MD5"]=null;
    $array["MyPDNS_Weapons"]["URI"]="https://$raw/mypdns/matrix/master/source/weapons/wildcard.list";
    $array["MyPDNS_Weapons"]["TABLE"]="category_weapons";


    $array["justdomains_adv"]["MD5"]=null;
    $array["justdomains_adv"]["URI"]="https://$raw/justdomains/blocklists/d552d43e58beff8fe2a5c7c8401991d7a632eb56/lists/easylist-justdomains.txt";
    $array["justdomains_adv"]["TABLE"]="category_publicite";

    $array["GetAdmiralDomains"]["MD5"]=null;
    $array["GetAdmiralDomains"]["URI"]="https://raw.githubusercontent.com/LanikSJ/ubo-filters/main/filters/getadmiral-domains.txt";
    $array["GetAdmiralDomains"]["TABLE"]="category_publicite";




    $array["yhonay_ads"]["MD5"]=null;
    $array["yhonay_ads"]["URI"]="https://$raw/yhonay/antipopads/master/hosts";
    $array["yhonay_ads"]["TABLE"]="category_publicite";

    $array["smed79"]["MD5"]=null;
    $array["smed79"]["URI"]="https://$raw/smed79/blacklist/master/hosts.txt";
    $array["smed79"]["TABLE"]="category_publicite";

    $array["jdlingyu"]["MD5"]=null;
    $array["jdlingyu"]["URI"]="https://$raw/jdlingyu/ad-wars/master/hosts";
    $array["jdlingyu"]["TABLE"]="category_publicite";

    $array["alexyangjie"]["MD5"]=null;
    $array["alexyangjie"]["URI"]="https://$raw/alexyangjie/domains-blacklist/master/domains-blacklist.txt";
    $array["alexyangjie"]["TABLE"]="category_publicite";

    $array["tiuxo"]["MD5"]=null;
    $array["tiuxo"]["URI"]="https://$raw/tiuxo/hosts/master/ads";
    $array["tiuxo"]["TABLE"]="category_publicite";


    $urlsTrackers["blocklistproject_track"]="https://blocklistproject.github.io/Lists/alt-version/tracking-nl.txt";
    $urlsTrackers["FireBogTracker"]="https://v.firebog.net/hosts/Easyprivacy.txt";
    $urlsTrackers["frogeye_tracker"]="https://hostfiles.frogeye.fr/firstparty-trackers.txt";
    $urlsTrackers["MyPDNS_Tracker"]="https://$raw/mypdns/matrix/master/source/tracking/wildcard.list";
    $urlsTrackers["cbuijs"]="https://$raw/cbuijs/accomplist/master/trackers/plain.black.domain.list";
    $urlsTrackers["badmojr_Xtra"]="https://$raw/badmojr/1Hosts/master/Xtra/domains.wildcards";

    foreach ($urlsTrackers as $subkey=>$url){
        $array["{$subkey}"]["MD5"]=null;
        $array["{$subkey}"]["URI"]=$url;
        $array["{$subkey}"]["TABLE"]="category_tracker";

    }



    $array["blocklistproject_malware"]["MD5"]=null;
    $array["blocklistproject_malware"]["URI"]="https://blocklistproject.github.io/Lists/alt-version/malware-nl.txt";
    $array["blocklistproject_malware"]["TABLE"]="category_malware";

    $array["DandelionSprout"]["MD5"]=null;
    $array["DandelionSprout"]["URI"]="https://$raw/DandelionSprout/adfilt/master/Alternate%20versions%20Anti-Malware%20List/AntiMalwareDomains.txt";
    $array["DandelionSprout"]["TABLE"]="category_malware";


    $array["quidsup_malware"]["MD5"]=null;
    $array["quidsup_malware"]["URI"]="https://gitlab.com/quidsup/notrack-blocklists/-/raw/master/notrack-malware.txt";
    $array["quidsup_malware"]["TABLE"]="category_malware";

    $array["cbuijs_malware"]["MD5"]=null;
    $array["cbuijs_malware"]["URI"]="https://$raw/cbuijs/accomplist/master/malicious-dom/plain.black.domain.list";
    $array["cbuijs_malware"]["TABLE"]="category_malware";

    $array["scafroglia93_bitdefender"]["MD5"]=null;
    $array["scafroglia93_bitdefender"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-bitdefender.txt";
    $array["scafroglia93_bitdefender"]["TABLE"]="category_malware";

    $array["scafroglia93_kaspersky"]["MD5"]=null;
    $array["scafroglia93_kaspersky"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-kaspersky.txt";
    $array["scafroglia93_kaspersky"]["TABLE"]="category_malware";

    $array["scafroglia93_maltraffic"]["MD5"]=null;
    $array["scafroglia93_maltraffic"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-malware-traffic.txt";
    $array["scafroglia93_maltraffic"]["TABLE"]="category_malware";

    $array["scafroglia93_sentinelone"]["MD5"]=null;
    $array["scafroglia93_sentinelone"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-sentinelone.txt";
    $array["scafroglia93_sentinelone"]["TABLE"]="category_malware";

    $array["scafroglia93_drweb"]["MD5"]=null;
    $array["scafroglia93_drweb"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-drweb.txt";
    $array["scafroglia93_drweb"]["TABLE"]="category_malware";



    $array["scafroglia93_zscaler"]["MD5"]=null;
    $array["scafroglia93_zscaler"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-zscaler.txt";
    $array["scafroglia93_zscaler"]["TABLE"]="category_malware";

    $array["scafroglia93_checkpoint"]["MD5"]=null;
    $array["scafroglia93_checkpoint"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-checkpoint.txt";
    $array["scafroglia93_checkpoint"]["TABLE"]="category_malware";

    $array["scafroglia93_orangecyber"]["MD5"]=null;
    $array["scafroglia93_orangecyber"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-orangecyber.txt";
    $array["scafroglia93_orangecyber"]["TABLE"]="category_malware";

    $array["scafroglia93_microsoftr"]["MD5"]=null;
    $array["scafroglia93_microsoft"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-microsoft.txt";
    $array["scafroglia93_microsoft"]["TABLE"]="category_malware";





    $array["blocklistproject_fraud"]["MD5"]=null;
    $array["blocklistproject_fraud"]["URI"]="https://blocklistproject.github.io/Lists/alt-version/fraud-nl.txt";
    $array["blocklistproject_fraud"]["TABLE"]="category_suspicious";


    $array["ut1-blacklists"]["MD5"]=null;
    $array["ut1-blacklists"]["URI"]="https://$raw/olbat/ut1-blacklists/master/blacklists/phishing/domains";
    $array["ut1-blacklists"]["TABLE"]="category_phishing";

    $array["RedFlagDomains"]["MD5"]=null;
    $array["RedFlagDomains"]["URI"]="https://dl.red.flag.domains/red.flag.domains.txt";
    $array["RedFlagDomains"]["TABLE"]="category_phishing";


    $array["Dawsey21"]["MD5"]=null;
    $array["Dawsey21"]["URI"]="https://$raw/Dawsey21/Lists/master/adblock-list.txt";
    $array["Dawsey21"]["TABLE"]="category_phishing";

    $array["scafroglia93_certagid"]["MD5"]=null;
    $array["scafroglia93_certagid"]["URI"]="https://$raw/scafroglia93/blocklists/master/blocklists-certagid.txt";
    $array["scafroglia93_certagid"]["TABLE"]="category_phishing";




    $array["AbuseMalware"]["MD5"]=null;
    $array["AbuseMalware"]["URI"]="https://urlhaus.abuse.ch/downloads/rpz/";
    $array["AbuseMalware"]["TABLE"]="category_malware";

    $array["easylist_adservers"]["MD5"]=null;
    $array["easylist_adservers"]["URI"]="https://$raw/easylist/easylist/master/easylist/easylist_adservers.txt";
    $array["easylist_adservers"]["TABLE"]="category_publicite";

    $array["KADhosts"]["MD5"]=null;
    $array["KADhosts"]["URI"]="https://$raw/azet12/KADhosts/master/KADhosts.txt";
    $array["KADhosts"]["TABLE"]="category_spyware";





    $catz               = new mysql_catz();
    $redis = new Redis();

    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        squid_admin_mysql(0,"Redis error",$e->getMessage(),__FILE__,__LINE__);
        echo $e->getMessage() . "\n";
        return null;
    }

    $EASYLISTS["GetAdmiralDomains"]=true;
    $EASYLISTS["easylist_adservers"]=true;
    $EASYLISTS["Dawsey21"]=true;
    $EASYLISTS["accomplist_nrd"]=true;


    foreach ($array as $MainKey=>$zSettings){
        $added=0;
        $localmd5=null;
        $urimd5=$zSettings["MD5"];
        $uri=$zSettings["URI"];
        $table=$zSettings["TABLE"];
        $md5_old=null;
        $md5_new=null;
        if($urimd5<>null) {
            $localmd5 = $workdir . "/" . basename($urimd5);
        }else{
            $localmd5 = $workdir . "/" . basename($uri).".md5";
        }
        $localtfile=$workdir."/".basename($uri);

        if(is_file($localmd5)){
            $md5_old=@file_get_contents($localmd5);
        }
        if($urimd5<>null){
            $curl=new ccurl($urimd5);
            $curl->NoHTTP_POST=true;
            if(!$curl->get()) {
                echo $curl->error . "\n";
                squid_admin_mysql(0,"$curl->error on $MainKey",null,__FILE__,__LINE__);
                continue;
            }
            $md5_new=trim($curl->data);

            if($md5_old==$md5_new){
                echo "$MainKey: $md5_old==$md5_new SKIP\n";
                continue;
            }
        }


        $curl=new ccurl($uri);
        echo "\n\nDownloading $uri\n";
        if(!$curl->GetFile($localtfile)){
            echo $curl->error . "\n";
            squid_admin_mysql(0,"$curl->error on $MainKey",null,__FILE__,__LINE__);
            continue;
        }
        $fsize=DshieldFormatBytes(filesize($localtfile)/1024);
        echo "$localtfile = $fsize\n";

        if($md5_new==null){
            $md5_new=md5_file($localtfile);
            if($md5_old==$md5_new){
                echo "$MainKey: $md5_old==$md5_new SKIP\n";
                continue;
            }
        }


        $f=explode("\n",@file_get_contents($localtfile));
        $sMax=count($f);
        $zl=0;$zout=0;$hits=0;$alcatz=0;
        $time=time();
        foreach ($f as $line){
            $zl++;
            $zout++;
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#^(\#|:|;)#",$line)){continue;}

            if($MainKey=="AbuseMalware"){
                if (!preg_match("#^(.+?)\s+CNAME.*?Malware\s+#", $line, $re)) {continue;}
                $line=$re[1];
            }
            if(isset($EASYLISTS[$MainKey])){
                if(!preg_match("#^\|\|(.+?)\^$#",$line,$re)){continue;}
                $line=$re[1];
            }


            $line=str_replace("*.","",$line);
            if(preg_match("#^\.(.+)#",$line,$re)){$line=$re[1];}
            if(preg_match("#^(.+?)\##",$line,$re)){$line=$re[1];}
            if(preg_match("#^0\.0\.0\.0\s+(.+)#",$line,$re)){$line=$re[1];}
            if(preg_match("#^[0-9\.]+\s+(.+)#",$line,$re)){$line=$re[1];}
            if(preg_match("#^[0-9\.]+\s+.*?\##",$line,$re)){continue;}
            if(preg_match("#^www\.(.+)#",$line,$re)){$line=$re[1];}
            if(preg_match("#^ww1\.(.+)#",$line,$re)){$line=$re[1];}
            if(preg_match("#^ww2\.(.+)#",$line,$re)){$line=$re[1];}
            if(preg_match("#^www\.www\.(.+)#",$line,$re)){$line=$re[1];}
            if(preg_match("#^[a-z0-9]+:[a-z0-9]+:[a-z0-9]+#",$line,$re)){continue;}
            if(preg_match("#\s+(ip6-localnet|ip6-mcastprefix|ip6-allnodes|ip6-allrouters|ip6-allhosts)#",$line,$re)){continue;}
            if(strpos("  $line","|")>0){continue;}
            if($line=="localhost"){continue;}
            if($line=="net.ar"){continue;}
            if($line=="me.net"){continue;}
            if($line=="da.ru"){continue;}
            if($line=="be"){continue;}
            if($line=="com"){continue;}
            if($line=="usa.cc"){continue;}

            $prc=round(($zl/$sMax)*100,2);

            if($zout>5000){
                $mins=0;
                $seconds=time()-$time;
                if($seconds>60){
                    $mins=round($mins/60);
                }
                $sitesSec=round($zout/$seconds,2);
                echo "$MainKey:($prc) $zl $sitesSec/sec ($seconds seconds - $mins minutes) Hits:$hits Already categoryzed:$alcatz\n";
                $zout=0;
                $time=time();
            }

            $CACHE=intval($redis->get("DomainToInt:$line"));
            if($CACHE>0){
                $hits++;
                continue;}
            if(preg_match("#ipfs.cf-ipfs.com|ipfs.nftstorage.link|ipfs.dweb.link|p.temp-site.link|ipfs.fleek.cool|promericaain.repl.co|ipfs.w3s.link|auth0rizesharedo.workers.dev|comcast-xfinity.mallboxz.club|vcikkso.dstech.us.com#",$line)){
                set_DomainToIntredis("$line",999);
                continue;

            }
            if($catz->GET_CATEGORIES($line)>0){
                $alcatz++;
                $redis->set("DomainToInt:$line",999);
                continue;
            }

            if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$line)){
                $redis->set("DomainToInt:$line",999);
                continue;
            }

            echo "ADD:$MainKey:($prc): $added/$zl:$line\n";
            $q->QUERY_SQL("INSERT INTO $table (sitename) VALUES('$line') ON CONFLICT DO NOTHING");
            $redis->set("DomainToInt:$line",999);
            $GLOBALS["ADDEDPLUS"]=$GLOBALS["ADDEDPLUS"]+1;
            $added++;
        }
        @file_put_contents($localmd5,$md5_new);
        if($added>0){
            squid_admin_mysql("sssl:$MainKey $added new sites",null,__FILE__,__LINE__);
        }

    }
    $redis->close();
    discapitol();
    dcore();
    if($GLOBALS["ADDEDPLUS"]>0){
        squid_admin_mysql(0,$GLOBALS["ADDEDPLUS"]." New websites added in categories",null,__FILE__,__LINE__);
    }
}

function DshieldFormatBytes($kbytes,$nohtml=true){

    $spacer="&nbsp;";
    if($nohtml){$spacer="";}

    if($kbytes>1048576){
        $value=round($kbytes/1048576, 2);
        if($value>1000){
            $value=round($value/1000, 2);
            return "$value{$spacer}TB";
        }
        return "$value{$spacer}GB";
    }
    elseif ($kbytes>=1024){
        $value=round($kbytes/1024, 2);
        return "$value{$spacer}MB";
    }
    else{
        $value=round($kbytes, 2);
        return "$value{$spacer}KB";
    }
}
function tests(){
    ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$catz=new mysql_catz();
	$category=$catz->GET_CATEGORIES("iphoneresult.top");
	echo "iphoneresult.top ---> $category\n";
    echo "DomainToIntredis == " .DomainToIntredis("iphoneresult.top");
    set_DomainToIntredis("iphoneresult.top",$category);

}
function set_DomainToIntredis($strdomain,$id){
    $redis = new Redis();

    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        return null;
    }
    $redis->set("DomainToInt:$strdomain",$id,2880);
    $redis->close();
}

function DomainToIntredis($strdomain){
    $redis = new Redis();

    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        if ($GLOBALS["VERBOSE"]) {echo $e->getMessage() . "\n";}
        return null;
    }

    $value=$redis->get("DomainToInt:$strdomain");
    $redis->close();
    if(!$value){return 0;}
    return intval($value);

}

function dhshield_logs($text){

    $lineToSave=date('H:i:s')." $text";
    $f = @fopen("/var/log/dhshield.log", 'a');
    @fwrite($f, "$lineToSave\n");
    @fclose($f);
}

function move_bad_catz($sitename,$from_category_table,$category){
    $q=new postgres_sql();

    if($from_category_table<>"category_malware") {
        if ($category == 92) {
            $q->QUERY_SQL("DELETE FROM category_malware WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO $from_category_table (sitename) VALUES('$sitename') ON CONFLICT DO NOTHING");
            return true;
        }
    }
    if($category==8){
        $q->QUERY_SQL("DELETE FROM category_shopping WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO $from_category_table (sitename) VALUES('$sitename') ON CONFLICT DO NOTHING");
        return true;
    }
    if($category==49){
        $q->QUERY_SQL("DELETE FROM category_filehosting WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO $from_category_table (sitename) VALUES('$sitename') ON CONFLICT DO NOTHING");
        return true;
    }
    if($category==64){
        $q->QUERY_SQL("DELETE FROM category_hacking WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO $from_category_table (sitename) VALUES('$sitename') ON CONFLICT DO NOTHING");
        return true;
    }
    if($category==100){
        $q->QUERY_SQL("DELETE FROM category_movies WHERE sitename='$sitename'");
        $q->QUERY_SQL("INSERT INTO $from_category_table (sitename) VALUES('$sitename') ON CONFLICT DO NOTHING");
        return true;
    }
    if($from_category_table<>"category_suspicious") {
        if ($category == 140) {
            $q->QUERY_SQL("DELETE FROM category_suspicious WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO $from_category_table (sitename) VALUES('$sitename') ON CONFLICT DO NOTHING");
            return true;
        }
    }
    if($from_category_table<>"category_science_computing") {
        if ($category == 126) {
            $q->QUERY_SQL("DELETE FROM category_science_computing WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO $from_category_table (sitename) VALUES('$sitename') ON CONFLICT DO NOTHING");
            return true;
        }
    }


    return false;
}




function notrack_blocklist(){
    $URL="https://gitlab.com/quidsup/notrack-blocklists/raw/master/notrack-blocklist.txt";
    $q=new postgres_sql();
    $unix=new unix();
    $ROOT_DIR="/root/sophos-xg";
    if(!is_dir($ROOT_DIR)){@mkdir($ROOT_DIR);}
    $BASENAME=basename($URL);
    $SourceFileMD5     = "$ROOT_DIR/".md5($URL).".md5";
    $TempFile = $unix->FILE_TEMP();
    if(is_file($SourceFileMD5)){$LastMD5=trim(@file_get_contents($SourceFileMD5));}
    $curl=new ccurl($URL);
    echo "$BASENAME: Downloading $URL\n";
    if(!$curl->GetFile($TempFile)){
        echo __FUNCTION__.": $BASENAME --> Error Downloading \"$curl->error\"\n";
        squid_admin_mysql(0,"$URL failed",$curl->error,@implode("\n",$curl->errors),__FILE__,__LINE__);
        return;
    }
    if(!is_dir("/home/artica/download.lists")){@mkdir("/home/artica/download.lists",0755);}
    shell_exec("cp $TempFile /home/artica/download.lists/dshield.".__FUNCTION__);
    $NextMD5=@md5_file($TempFile);

    if($NextMD5==$LastMD5){
        echo "$BASENAME: Nothing to do ($SourceFileMD5)\n";
        @unlink($TempFile);
        return;
    }
    echo "$BASENAME: Parsing $TempFile\n";


    if(!is_dir("/home/artica/download.lists")){@mkdir("/home/artica/download.lists",0755);}
    shell_exec("cp $TempFile /home/artica/download.lists/dshield.".__FUNCTION__);

    $data=explode("\n",@file_get_contents($TempFile));
    $catz               = new mysql_catz();
    $Max=count($data);
    $qq=0;
    $c=0;$known=0;$unknown=0;

    $redis = new Redis();

    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        squid_admin_mysql(0,"Redis error",$e->getMessage(),__FILE__,__LINE__);
        echo $e->getMessage() . "\n";
        return null;
    }


    foreach ($data as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^\##",$line)){continue;}
        $dest_table="category_publicite";
        $c++;
        $qq++;
        if($qq>500){echo "$c/$Max\n";$qq=0;}
        if(preg_match("#(.+?)\#(.+)#",$line,$re)){
            $SiteName=trim(strtolower($re[1]));
            if(preg_match("#tracker#i",$re[2])){
                $dest_table="category_tracker";
            }
            }else{
                $SiteName=trim(strtolower($line));
            }

        $CACHE=intval($redis->get("DomainToInt:$SiteName"));
        if($CACHE>0){continue;}

        if($catz->GET_CATEGORIES($SiteName)>0){
            $redis->set("DomainToInt:$SiteName",999);
            continue;
        }
        $unknown++;
        $q->QUERY_SQL("INSERT INTO $dest_table (sitename) VALUES('$SiteName') ON CONFLICT DO NOTHING");
        $redis->set("DomainToInt:$SiteName",999);
        if(!$q->ok){@unlink($TempFile);return;}

    }

    $redis->close();

    if($unknown>0) {
        squid_admin_mysql(0, "$unknown Added sites from notrack-blocklist.txt",
                __FILE__,__LINE__);
    }
}

function arraysites(){
    $array[3]="category_society";
    $array[5]="category_publicite";
    $array[8]="category_shopping";
    $array[24]="category_automobile_bikes";
    $array[29]="category_bicycle";
    $array[39]="category_cosmetics";
    $array[44]="category_downloads";
    $array[54]="category_finance_realestate";
    $array[55]="category_financial";
    $array[58]="category_games";
    $array[66]="category_health";
    $array[67]="category_hobby_arts";
    $array[70]="category_hobby_pets";
    $array[81]="category_industry";
    $array[87]="category_justice";
    $array[27]="category_automobile_cars";
    $array[51]="category_finance_insurance";
    $array[91]="category_mailing";
    $array[96]="category_medical";
    $array[98]="category_smartphones";
    $array[103]="category_press";
    $array[109]="category_porn";
    $array[112]="category_reaffected";
    $array[114]="category_recreation_nightout";
    $array[116]="category_recreation_sports";
    $array[119]="category_recreation_travel";
    $array[126]="category_science_computing";
    $array[129]="category_searchengines";
    $array[140]="category_suspicious";
    $array[143]="category_tracker";
    $array[152]="category_webmail";
    $array[156]="category_webtv";




    return $array;
}

function trPhishingList(){
    $unix=new unix();
    $LastMD5=null;
    $q=new postgres_sql();
    $ROOT_DIR="/root/sophos-xg";
    if(!is_dir($ROOT_DIR)){@mkdir($ROOT_DIR);}
    $URL="https://raw.githubusercontent.com/HorusTeknoloji/TR-PhishingList/master/url-lists.txt";
    $BASENAME=basename($URL);
    $SourceFileMD5     = "$ROOT_DIR/".md5($URL).".md5";
    $TempFile = $unix->FILE_TEMP();
    if(is_file($SourceFileMD5)){$LastMD5=trim(@file_get_contents($SourceFileMD5));}

    $curl=new ccurl($URL);
    echo "$BASENAME: Downloading $URL\n";
    if(!$curl->GetFile($TempFile)){
        echo __FUNCTION__.": $BASENAME --> Error Downloading \"$curl->error\"\n";
        squid_admin_mysql(0,"$URL failed",$curl->error,@implode("\n",$curl->errors),__FILE__,__LINE__);
        return;
    }

    $NextMD5=@md5_file($TempFile);
    if($NextMD5==$LastMD5){
        echo "$BASENAME: Nothing to do ($SourceFileMD5)\n";
        @unlink($TempFile);
        return;
    }
    if(!is_dir("/home/artica/download.lists")){@mkdir("/home/artica/download.lists",0755);}
    shell_exec("cp $TempFile /home/artica/download.lists/dshield.".__FUNCTION__);
    echo "$BASENAME: Parsing $TempFile\n";
    $data=explode("\n",@file_get_contents($TempFile));
    $catz               = new mysql_catz();
    $Max=count($data);
    $qq=0;
    $c=0;$known=0;$unknown=0;

    $array=arraysites();

    foreach ($data as $line){
        if($line==null){continue;}
        if(preg_match("#^\##",$line,$re)){echo "$line no match\n";continue;}
        $www=trim($line);
        $www=str_replace("www.","",$www);
        $www=str_replace("*.","",$www);
        $category=$catz->GET_CATEGORIES($www);
        $qq++;
        $c++;
        if($category==5){continue;}
        if($category==57){continue;}
        if($category==92){continue;}
        if($category==64){continue;}
        if($category==135){continue;}
        if($category==105){continue;}
        if($category==148){continue;}
        if($category==181){$category=0;}

        if(isset($array[$category])){
            $q->QUERY_SQL("DELETE FROM {$array[$category]} WHERE sitename='$www'");
            $category=0;
        }

        if($category>0){
            echo "WARN: $www in [$category] ".$catz->CategoryIntToStr($category)."\n";
            $known++;
            continue;
        }
        $unknown++;
        if($qq>500){
            $reste=$Max-$c;
            echo "$BASENAME: Parsing $reste Known: $known, unknown: $unknown\n";
            $qq=0;
        }
        $q->QUERY_SQL("INSERT INTO category_phishing (sitename) VALUES('$www') ON CONFLICT DO NOTHING");
        if(!$q->ok){
            @unlink($TempFile);
            return;}

    }
    if($unknown>0) {
        squid_admin_mysql(1, "Adding $unknown $BASENAME in category phishing", null, __FILE__, __LINE__);
    }

    @unlink($TempFile);
    echo "$BASENAME: Parsing Down: Known: $known, unknown: $unknown\n";
    @file_put_contents($SourceFileMD5,$NextMD5);
}







//



function malwares_all(){

    $urls[]="https://blocklistproject.github.io/Lists/malware.txt";
    $urls[]="https://blocklistproject.github.io/Lists/ransomware.txt";

$c=0;
    foreach ($urls as $url){
        dhshield_logs("$url...");
        $c=$c+insert_in_malwares($url);
    }

    dhshield_logs("$c malwares items added");

}







function CleanPorn(){

    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT * FROM category_hacking  where sitename LIKE '0%.ve'");
    if(!$q->ok){echo $q->mysql_error."\n";}
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern = $ligne["sitename"];
        $q->QUERY_SQL("DELETE FROM category_hacking where sitename='$pattern'");
        $q->QUERY_SQL("INSERT INTO category_malware (sitename) VALUES('$pattern') ON CONFLICT DO NOTHING");
        echo __FUNCTION__.":  $pattern\n";
    }


}



function FullistsAdv(){
    if(!testEngine()){return;}

    $unix=new unix();
    $catz=new mysql_catz();
    $q=new postgres_sql();
    $ipClass=new IP();
    $fam=new familysite();

    $fz[]="https://v.firebog.net/hosts/AdguardDNS.txt";
    $fz[]="https://adaway.org/hosts.txt";
    $fz[]="https://raw.githubusercontent.com/GATmyIT/pihole-lists/master/yoyo-ad-list.txt";
    $fz[]="https://raw.githubusercontent.com/justdomains/blocklists/master/lists/adguarddns-justdomains.txt";
    $fz[]="https://hosts-file.net/ad_servers.txt";
    $d=0;
    foreach ($fz as $URL){
        $md5file="/root/".__FUNCTION__.".".md5($URL).".md5";
        $lastmd5=@file_get_contents($md5file);
        $targetpath=$unix->FILE_TEMP();
        echo __FUNCTION__.": Checking $URL\n";$curl=new ccurl($URL);
        if(!$curl->GetFile($targetpath)){echo __FUNCTION__.":  $curl->error\n";return false;}
        $currentmd5=md5_file($targetpath);
        echo __FUNCTION__.":  $md5file: $lastmd5 --- $currentmd5\n";
        if($lastmd5==$currentmd5){continue;}
        $f=explode("\n",@file_get_contents($targetpath));
        foreach ($f as $index=>$line) {
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#^\##",$line,$re)){continue;}
            if(strpos($line,"*")>0){continue;}
            if(preg_match("#^127\.0\.0\.1\s+(.+)#",$line,$re)){$line=$re[1];}
            if(preg_match("#^0\.0\.0\.0\s+(.+)#",$line,$re)){$line=$re[1];}
            if(preg_match("#^(.+?)\##",$line,$re)){$line=$re[1];}
            $line=trim($line);
            if(preg_match("#(localhost|localdomain|broadcasthost|0)$#",$line,$re)){continue;}
            if(preg_match("#^www\.(.+)#",$line,$re)){$line=trim($re[1]);}
            if(strpos($line,"*")>0){continue;}
            if(strpos($line,"?")>0){continue;}
            $line=trim($line);
            if(strlen($line)<4){continue;}
            if($ipClass->isValid($line)){continue;}

            $familysite=$fam->GetFamilySites($line);
            if($familysite<>$line){continue;}

            $category=$catz->GET_CATEGORIES($familysite);
            if($category>0){continue;}


            echo __FUNCTION__.":  $line OK\n";
            $d++;
            $q->QUERY_SQL("INSERT INTO category_publicite (sitename) VALUES('$familysite') ON CONFLICT DO NOTHING");

        }
        @file_put_contents($md5file, $currentmd5);



    }

    if($d>0){
        squid_admin_mysql("dhsield adding $d sites from FullistsAdv",null,__FILE__,__LINE__);
    }



}
function porn_capitol(){

    if(!testEngine()){return;}
    $md5file="/root/".__FUNCTION__.".md5";
    $unix=new unix();

    $URL="http://dsi.ut-capitole.fr/blacklists/download/porn.tar.gz";
    $tar=$unix->find_program("tar");
    $catz=new mysql_catz();

    echo __FUNCTION__.": Checking $URL\n";$curl=new ccurl($URL);
    $lastmd5=@file_get_contents($md5file);
    $targetpath=$unix->FILE_TEMP();
    if(!$curl->GetFile($targetpath)){echo __FUNCTION__.":  $curl->error\n";return false;}
    $currentmd5=md5_file($targetpath);
    echo __FUNCTION__.":  $md5file: $lastmd5 --- $currentmd5\n";
    if($lastmd5==$currentmd5){return false;}
    $pos=new postgres_sql();
    echo __FUNCTION__.":  $tar xf $targetpath -C /root/ \n";
    if(is_file("/root/adult/domains")){@unlink("/root/adult/domains");}
    exec("$tar xf $targetpath -C /root/",$f);

    $ipClass=new IP();
    $TRAC=array();
    $f=explode("\n",@file_get_contents("/root/adult/domains"));
    foreach ($f as $index=>$line){

        $line=trim(strtolower($line));
        if($line==null){continue;}
        if(strpos("/",$line)>0){continue;}
        if(strpos(" $line","*")>0){continue;}


        $category=$catz->GET_CATEGORIES($line);
        if($category>0){
            echo __FUNCTION__.":  $line SKIP\n";
            continue;
        }
        if($ipClass->isValid($line)){$line=ip2long($line).".addr";}
        echo __FUNCTION__.":  $line OK\n";

        $TRAC[]=$line;
        $pos->QUERY_SQL("INSERT INTO category_porn (sitename) VALUES('$line') ON CONFLICT DO NOTHING");

    }
    @file_put_contents("/root/pornToAdd.txt", @implode("\n",$TRAC));
    @file_put_contents($md5file, $currentmd5);
    @unlink("$targetpath");
    @unlink("/root/adult/domains");


}

function Cleanwww(){

    $tables[]="category_suspicious";
    $tables[]="category_malware";
    $tables[]="category_spyware";
    $tables[]="category_publicite";
    $tables[]="category_tracker";
    
    $q=new postgres_sql();

    foreach ($tables as $tablename) {
        $results = $q->QUERY_SQL("SELECT * FROM $tablename WHERE sitename ~ '^www.'");
        $max = pg_num_rows($results);
        $c = 0;
        $lastprc = 0;
        while ($ligne = pg_fetch_assoc($results)) {
            $c++;
            $prc = ($c / $max) * 100;
            $prc = round($prc);
            if ($lastprc <> $prc) {
                echo "{$prc}% $c/$max\n";
                $lastprc = $prc;
            }
            $sitename = $ligne["sitename"];
            if (!preg_match("#^www\.(.+)#", $sitename, $re)) {
                continue;
            }
            $newsite = $re[1];
            $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename='$sitename'");
            $q->QUERY_SQL("INSERT INTO $tablename (sitename) VALUES('$newsite') ON CONFLICT DO NOTHING");
            if (!$q->ok) {
                echo $q->mysql_error . "\n";
                return;
            }
        }
    }



}


function local_spamdb(){
    $md5file="/root/".__FUNCTION__.".md5";
    $unix=new unix();
    $targetpath=$unix->FILE_TEMP();
    $URL="https://raw.githubusercontent.com/vellezz/postfix_spam/09ce583af088074fb429d5c99de950b5c8510e6e/local-spamdb";
    $lastmd5=@file_get_contents($md5file);
    echo __FUNCTION__.": Checking $URL\n";$curl=new ccurl($URL);
    if(!$curl->GetFile($targetpath)){echo __FUNCTION__.":  $curl->error\n";return false;}
    $currentmd5=md5_file($targetpath);
    echo __FUNCTION__.":  $md5file: $lastmd5 --- $currentmd5\n";
    if($lastmd5==$currentmd5){return false;}
    $pos=new postgres_sql();
    $ligne=$pos->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM rbl_emails");
    $Count=intval($ligne["tcount"]);

    $f=explode("\n",@file_get_contents($targetpath));
    foreach ($f as $index=>$line) {
        $line = trim(strtolower($line));
        if ($line == null) {
            continue;
        }
        if (preg_match("#<(.+?)>#", $line, $re)) {
            $line = $re[1];
        }
        if (preg_match("#^(.+?)\s+#", $line, $re)) {
            $line = $re[1];
        }
        echo $line . "\n";

        $sdate=date("Y-m-d H:i:s");
        echo __FUNCTION__.":  $line\n";
        $pos->QUERY_SQL("INSERT INTO rbl_emails (pattern,description,zDate) VALUES('$line','Imported from jweyrich','$sdate') ON CONFLICT DO NOTHING");
    }
    @file_put_contents($md5file, $currentmd5);
    $ligne=$pos->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM rbl_emails");
    $Count2=intval($ligne["tcount"]);
    $Sum=$Count2-$Count;
    if($Sum>0){
        echo __FUNCTION__.":  $Sum blacklisted emails added to rbl_emails\n";
        squid_admin_mysql(1,"$Sum blacklisted emails added to rbl_emails",null,__FILE__,__LINE__);
    }
}

function joewein_domains(){
    $md5file="/root/".__FUNCTION__.".md5";
    $unix=new unix();
    $targetpath=$unix->FILE_TEMP();
    $URL="https://www.joewein.net/dl/bl/dom-bl.txt";

    $lastmd5=@file_get_contents($md5file);
    echo __FUNCTION__.": Checking $URL\n";$curl=new ccurl($URL);
    if(!$curl->GetFile($targetpath)){echo __FUNCTION__.":  $curl->error\n";return false;}
    $currentmd5=md5_file($targetpath);
    echo __FUNCTION__.":  $md5file: $lastmd5 --- $currentmd5\n";
    if($lastmd5==$currentmd5){return false;}
    $pos=new postgres_sql();
    $ligne=$pos->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM rbl_emails");
    $Count=intval($ligne["tcount"]);

    $f=explode("\n",@file_get_contents($targetpath));
    foreach ($f as $line) {
        $line = trim(strtolower($line));
        if ($line == null) {
            continue;
        }
        if(preg_match("#(notification@facebook)\.#",$line)){continue;}
        if(preg_match("#(linkedin|groupemoniteur|groups\.yahoo|sogelink|\.gouv|showroomprive|cdiscount|\.booking|facebookmail)\.#",$line)){continue;}

        if (preg_match("#(.+?);#", $line, $re)) {
            $line = $re[1];
        }
       echo $line . "\n";

        $sdate=date("Y-m-d H:i:s");
        echo __FUNCTION__.":  $line\n";
        $pos->QUERY_SQL("INSERT INTO rbl_emails (pattern,description,zDate) VALUES('$line','Imported from joewein/domains','$sdate') ON CONFLICT DO NOTHING");
    }
    @file_put_contents($md5file, $currentmd5);
    $ligne=$pos->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM rbl_emails");
    $Count2=intval($ligne["tcount"]);
    $Sum=$Count2-$Count;
    if($Sum>0){
        echo __FUNCTION__.":  $Sum blacklisted emails added to rbl_emails\n";
        squid_admin_mysql(1,"[joewein/Domains] $Sum blacklisted emails added to rbl_emails",null,__FILE__,__LINE__);
    }
}

function spam_blacklist_forum_sih(){
$md5file="/root/".__FUNCTION__.".md5";
    $unix=new unix();
    $targetpath=$unix->FILE_TEMP();
    $URL="https://www.forum-sih.fr/spam/spam_blacklist_forum-sih.txt";
    $lastmd5=@file_get_contents($md5file);
    echo __FUNCTION__.": Checking $URL\n";$curl=new ccurl($URL);
    if(!$curl->GetFile($targetpath)){echo __FUNCTION__.":  $curl->error\n";return false;}
    $currentmd5=md5_file($targetpath);
    echo __FUNCTION__.":  $md5file: $lastmd5 --- $currentmd5\n";
    if($lastmd5==$currentmd5){return false;}
    $pos=new postgres_sql();
    $ligne=$pos->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM rbl_emails");
    $Count=intval($ligne["tcount"]);

    $f=explode("\n",@file_get_contents($targetpath));
    foreach ($f as $index=>$line) {
        $line = trim(strtolower($line));
        if ($line == null) {
            continue;
        }
        if (preg_match("#(.+?);#", $line, $re)) {
            $line = $re[1];
        }

        $line=str_replace("*.","",$line);

        if(strpos(" $line","'")>0){continue;}
        if(strpos(" $line","(")>0){continue;}
        if(strpos(" $line",")")>0){continue;}

        echo $line . "\n";

        $sdate=date("Y-m-d H:i:s");
        echo __FUNCTION__.":  $line\n";
        $pos->QUERY_SQL("INSERT INTO rbl_emails (pattern,description,zDate) VALUES('$line','Imported from SIH Forum','$sdate') ON CONFLICT DO NOTHING");
    }
    @file_put_contents($md5file, $currentmd5);
    $ligne=$pos->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM rbl_emails");
    $Count2=intval($ligne["tcount"]);
    $Sum=$Count2-$Count;
    if($Sum>0){
        echo __FUNCTION__.":  $Sum blacklisted emails added to rbl_emails\n";
        squid_admin_mysql(1,"[joewein/Domains] $Sum blacklisted emails added to rbl_emails",null,__FILE__,__LINE__);
    }
}





function joewein_from(){
    $md5file="/root/".__FUNCTION__.".md5";
    $unix=new unix();
    $targetpath=$unix->FILE_TEMP();
    $URL="https://www.joewein.net/dl/bl/from-bl.txt";
    $lastmd5=@file_get_contents($md5file);
    echo __FUNCTION__.": Checking $URL\n";$curl=new ccurl($URL);
    if(!$curl->GetFile($targetpath)){echo __FUNCTION__.":  $curl->error\n";return false;}
    $currentmd5=md5_file($targetpath);
    echo __FUNCTION__.":  $md5file: $lastmd5 --- $currentmd5\n";
    if($lastmd5==$currentmd5){return false;}
    $pos=new postgres_sql();
    $ligne=$pos->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM rbl_emails");
    $Count=intval($ligne["tcount"]);

    $f=explode("\n",@file_get_contents($targetpath));
    foreach ($f as $index=>$line) {
        $line = trim(strtolower($line));
        if ($line == null) {
            continue;
        }
        if (preg_match("#(.+?);#", $line, $re)) {
            $line = $re[1];
        }

        if(preg_match("#(notification@facebook)\.#",$line)){continue;}
        if(preg_match("#(linkedin|groupemoniteur|groups\.yahoo|sogelink|\.gouv|showroomprive|cdiscount|\.booking|facebookmail)\.#",$line)){continue;}

        if(strpos(" $line","'")>0){continue;}
        if(strpos(" $line","(")>0){continue;}
        if(strpos(" $line",")")>0){continue;}

        echo $line . "\n";

        $sdate=date("Y-m-d H:i:s");
        echo __FUNCTION__.":  $line\n";
        $pos->QUERY_SQL("INSERT INTO rbl_emails (pattern,description,zDate) VALUES('$line','Imported from joewein/from','$sdate') ON CONFLICT DO NOTHING");
    }
    @file_put_contents($md5file, $currentmd5);
    $ligne=$pos->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM rbl_emails");
    $Count2=intval($ligne["tcount"]);
    $Sum=$Count2-$Count;
    if($Sum>0){
        echo __FUNCTION__.":  $Sum blacklisted emails added to rbl_emails\n";
        squid_admin_mysql(1,"[joewein/Domains] $Sum blacklisted emails added to rbl_emails",null,__FILE__,__LINE__);
    }
}




function SpamList($sourcefile=null){
    $md5file="/root/".__FUNCTION__.".md5";
    $unix=new unix();
    $ascurl=true;
    $URL="https://raw.githubusercontent.com/vjacobs/spammy-fundraisers/master/spammy-fundraisers.txt";
    //$URL=" https://raw.githubusercontent.com/kargig/greek-spammers/master/grrbl_blacklist.cf";

    echo __FUNCTION__.": Checking $URL\n";$curl=new ccurl($URL);
    $lastmd5=@md5_file($md5file);
    $targetpath=$unix->FILE_TEMP();
    if($sourcefile<>null) {
        if (is_file($sourcefile)) {
            $targetpath = $sourcefile;
            $ascurl=false;
        }
    }
    if($ascurl){ if(!$curl->GetFile($targetpath)){echo __FUNCTION__.":  $curl->error\n";return false;}}
    $currentmd5=md5_file($targetpath);
    echo __FUNCTION__.":  $md5file: $lastmd5 --- $currentmd5\n";
    if($lastmd5==$currentmd5){return false;}
    $pos=new postgres_sql();
    $f=explode("\n",@file_get_contents($targetpath));
   foreach ($f as $index=>$line){
        $line=trim(strtolower($line));
        if($line==null){continue;}
        if(preg_match("#blacklist_from\s+(.+)#",$line,$re)){$line=$re[1];}
        $line=str_replace("*@","",$line);
        if(preg_match("#^@(.+?)OR#",$line,$re)){$line=trim($re[1]);}
        if(preg_match("#^(.+?)\s+#",$line,$re)){$line=trim($re[1]);}
        if(preg_match("#^@(.+)#",$line,$re)){$line=trim($re[1]);}
        if(strpos(" $line","*")>0){continue;}
        $sdate=date("Y-m-d H:i:s");
        echo __FUNCTION__.":  $line\n";
        $pos->QUERY_SQL("INSERT INTO rbl_emails (pattern,description,zDate) VALUES('$line','Imported from jweyrich','$sdate') ON CONFLICT DO NOTHING");
    }
    @file_put_contents($md5file, $currentmd5);


}


function CleanSmartPhones(){

    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT sitename FROM category_mobile_phone");
    $total=pg_num_rows($results);
    $c=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern = $ligne["sitename"];
        $c++;
        $q->QUERY_SQL("DELETE FROM category_smalladds WHERE pattern='$pattern'");
        $q->QUERY_SQL("DELETE FROM category_industry WHERE pattern='$pattern'");
        $q->QUERY_SQL("DELETE FROM category_tracker WHERE pattern='$pattern'");
        $q->QUERY_SQL("DELETE FROM category_publicite WHERE pattern='$pattern'");
        $q->QUERY_SQL("DELETE FROM category_suspicious WHERE pattern='$pattern'");
        $q->QUERY_SQL("DELETE FROM category_science_computing WHERE pattern='$pattern'");
        $reste = $total-$c;
        echo "$reste -  \"$pattern\"\n";
    }

}

function CleanSpam(){
    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT * FROM rbl_emails where pattern LIKE '@%'");
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern=$ligne["pattern"];

        if(preg_match("#^@(.+)#",$pattern,$re)){
            echo __FUNCTION__.":  $pattern -> {$re[1]}\n";
            $q->QUERY_SQL("UPDATE rbl_emails SET pattern='{$re[1]}' where pattern='$pattern'");

        }
    }


}


function bundle(){


    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}
    $pid=@file_get_contents($pidfile);
    if($unix->process_exists($pid)){return false;}

    if(!testEngine()){return;}
	if(is_file("/var/log/dhshield.log")){@unlink("/var/log/dhshield.log");}
    sslinfo_newage();
    notrack_blocklist();
    sync_categories();
    dhshield_logs("End...");
	
}







function ManyAds(){


    $URLS[]="https://asc.hk/adplus.txt";
    $URLS[]="https://raw.githubusercontent.com/austinheap/sophos-xg-block-lists/e191eb981ae044fc2f0d55ce5a515218cbe655c4/easylist.txt";
    $URLS[]="https://filtri-dns.ga/filtri.txt";

    $URLS[]="https://hblock.molinero.dev/hosts";
    foreach ($URLS as $URL){
        sophos_xg_easylist($URL);
    }

}








//



function clean_trackers(){

    $pos=new postgres_sql();
    $pos->QUERY_SQL("DELETE FROM category_tracker WHERE sitename LIKE '%<%'");
    $pos->QUERY_SQL("DELETE FROM category_weapons WHERE sitename LIKE '%<%'");


}

//https://raw.githubusercontent.com/Dawsey21/Lists/master/adblock-list.txt






function fussle_de(){
	
	if(!testEngine()){return false;}
	$md5_file="/root/fussle.md5";
	$catz=new mysql_catz();
	$q=new postgres_sql();
	$category=$catz->GET_CATEGORIES("google.com");
	if($category<>17){exit();}
	$category=$catz->GET_CATEGORIES("carrefour.com");
	if($category<>8){exit();}
	$fam=new squid_familysite();
	echo __FUNCTION__.":  Downloading http://www.fussle.de/block.txt\n";
	$curl=new ccurl("http://www.fussle.de/block.txt");
	$lastmd5=@file_get_contents($md5_file);
    echo __FUNCTION__.":  Downloading done...\n";
	$unix=new unix();
	$targetpath=$unix->FILE_TEMP();
	if(!$curl->GetFile($targetpath)){
		return false;
	}
	$currentmd5=md5_file($targetpath);
	echo __FUNCTION__.":  $lastmd5 --- $currentmd5\n";
	//if($currentmd5==$lastmd5){exit();}
	$memcached=new lib_memcached();
	$f=explode("\n",@file_get_contents($targetpath));
	foreach ($f as $line){
		$toremove=true;
		if(strpos($line, "localhost")>0){continue;}
		if(substr($line, 0,1)=="#"){continue;}
		if(strpos($line, ".")==0){continue;}
		$line=trim($line);
		if(preg_match("#(.+?)\##", $line,$re)){$line=trim($re[1]);}
		if(preg_match("#www\.(.+)#", $line,$re)){$line=trim($re[1]);}
		$familysite=$fam->GetFamilySites($line);
		if($familysite=="lswcdn.net"){continue;}
		if(strpos(" $familysite",".")==0){$familysite=$line;}
        if(strpos(" $familysite","$")>0){continue;}
        if(preg_match("#^ac\.(in|ug|ir|ru|be)$#",$familysite)){
            $familysite=$line;
        }
        if($familysite=="com.cn"){$familysite=$line;}
        if($familysite=="de.tc"){$familysite=$line;}

		$category=$catz->GET_CATEGORIES($familysite);
		

		if($category>0){continue;}
		$fz = @fopen("/root/fussle_de.log", 'a');
		@fwrite($fz, "$familysite,ADDED\n");
		@fclose($fz);
		$q->QUERY_SQL("INSERT INTO category_suspicious (sitename) VALUES('$familysite') ON CONFLICT DO NOTHING");
		if(!$q->ok){echo $q->mysql_error;return;}
		$memcached->saveKey("CATEGORY:$line", 140,86400);
		echo __FUNCTION__.": $familysite -> ADD\n";
	}

	@file_put_contents($md5_file,$currentmd5);
}









function quidsup(){
	$catz=new mysql_catz();
	$q=new mysql_squid_builder();
	$category=$catz->GET_CATEGORIES("google.com");
	if($category<>17){exit();}
	$category=$catz->GET_CATEGORIES("carrefour.com");
	if($category<>8){exit();}
	$URL="https://raw.githubusercontent.com/quidsup/notrack/master/trackers.txt";
	$fam=new squid_familysite();
	echo __FUNCTION__.": Downloading $URL\n";
	$curl=new ccurl($URL);
	$md5_file="/root/quidsup.md5";
	$lastmd5=@file_get_contents($md5_file);
	$unix=new unix();
	$targetpath=$unix->FILE_TEMP();
	if(!$curl->GetFile($targetpath)){
		return false;
	}
	$currentmd5=md5_file($targetpath);
	echo __FUNCTION__.":  $lastmd5 --- $currentmd5\n";
	if($currentmd5==$lastmd5){exit();}
	$ipClass=new IP();
	$f=explode("\n",@file_get_contents($targetpath));
	foreach ($f as $line){
		$toremove=true;
		$B=null;
		$line=trim($line);
		if(strpos($line, "localhost")>0){continue;}
		if(substr($line, 0,1)=="#"){continue;}
		if(strpos($line, ".")==0){continue;}
		$line=trim($line);
		if(preg_match("#(.+?)\##", $line,$re)){$line=trim($re[1]);}
		if(preg_match("#www\.(.+)#", $line,$re)){$line=trim($re[1]);}
		$familysite=$fam->GetFamilySites($line);
		$category=$catz->GET_CATEGORIES($line);
		if($category>0){continue;}
		
		
		if($ipClass->isValid($line)){$line=ip2long($line).".addr";}
		$line=utf8_encode($line);
		$pos=new postgres_sql();
		$pos->QUERY_SQL("INSERT INTO category_tracker (sitename) VALUES('$line') ON CONFLICT DO NOTHING");
		echo $line." [OK]\n";
	}

	@file_put_contents($md5_file,$currentmd5);

}

function testEngine(){
	$catz=new mysql_catz();
	$category=$catz->GET_CATEGORIES("google.com");
	if($category<>17){
		echo "TEST FAILED FOR google.com\n";
		return false;
	}
	$category=$catz->GET_CATEGORIES("carrefour.com");
	if($category<>8){
		echo "TEST FAILED FOR carrefour.com\n";
		return false;
	}
	$category=$catz->GET_CATEGORIES("pornhub.com");
	if($category<>109){
		echo "TEST FAILED FOR pornhub.com\n";
		return false;
	}
	
	
	return true;
}






function adsfile($targetpath=null){
	$unix=new unix();
	
	$catz=new mysql_catz();
	$category=$catz->GET_CATEGORIES("google.com");
	if($category<>17){exit();}
	$category=$catz->GET_CATEGORIES("carrefour.com");
	if($category<>8){exit();}
	
	if(!is_file($targetpath)){
		echo "\"$targetpath\" No such file\n";
		$curl=new ccurl("http://pgl.yoyo.org/adservers/serverlist.php?hostformat=hosts&showintro=0&mimetype=plaintext");
		$targetpath=$unix->FILE_TEMP();
		if(!$curl->GetFile($targetpath)){
			return false;
		}
		
	}
	
	
	$q=new mysql_squid_builder();
	$family=new familysite();
	echo "OPEN \"$targetpath\"\n";
	$f=explode("\n",@file_get_contents($targetpath));
	
	$catz=new mysql_catz();
	foreach ($f as $line){
	$line=trim(strtolower($line));
	$line=str_replace("127.0.0.1 ","",$line);
	$line=trim($line);
	if(strpos($line, "localhost")>0){continue;}
	if(substr($line, 0,1)=="#"){continue;}
	if(strpos($line, ".")==0){continue;}
	if(preg_match("#^www\.(.+)#", $line,$re)){$line=$re[1];}
	
	$Dize=strpos($line, "#");
	if($Dize>2){
		$tf=explode("#",$line);
		$line=trim($tf[0]);
		$line=trim(strtolower($line));
	
	}
	
	if(strpos($line, ".")==0){continue;}
	$FAMZ=$family->GetFamilySites($line);
	if($FAMZ=="co.cc"){continue;}
	if($FAMZ=="com.com"){continue;}
	if($FAMZ==".com"){continue;}
	if($FAMZ<>$line){continue;}
	
	$category=$catz->GET_CATEGORIES($FAMZ);
	$md5=md5("publicite".$FAMZ);

		if($category==null){
			echo __FUNCTION__.":  $FAMZ: NONE\tADD\n";
			$q->QUERY_SQL("INSERT INTO category_publicite (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'publicite','$FAMZ','1636b7346f2e261c5b21abfcaef45a69')");
			continue;
		}
		
		
		
	
	}
	
}
function insert_in_malwares($url){

    if(!testEngine()){return;}
    $submd=md5($url);
	$md5_file="/root/malwaredomains-$submd.md5";
	$unix=new unix();
	$catz=new mysql_catz();
	$curl=new ccurl($url);
	$targetpath=$unix->FILE_TEMP();
	if(!$curl->GetFile($targetpath)){
	    echo "$url failed\n";
		return false;
	}
	
	$lastmd5=@file_get_contents($md5_file);
    $currentmd5=md5_file($targetpath);
	if($lastmd5==$currentmd5){
	    echo __FUNCTION__." $lastmd5==$currentmd5 SKIP function\n";
	    return false;}

    if(!is_dir("/home/artica/download.lists")){@mkdir("/home/artica/download.lists",0755);}
    shell_exec("cp $targetpath /home/artica/download.lists/dshield.".__FUNCTION__.".".md5($url));

	$fp = @fopen($targetpath, "r");
	if(!$fp){
		if($GLOBALS["DEBUG_GREP"]){echo __FUNCTION__.":  $targetpath BAD FD\n";}
		return array();
	}
	$ipClass=new IP();
	$c=0;
	$t=array();
    $pos=new postgres_sql();
    $sqlperf="INSERT INTO category_malware";

    while(!feof($fp)){
		$line = trim(fgets($fp));
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
        $srcline=$line;
		if(strpos($line, "localhost")>0){continue;}
		if(substr($line, 0,1)=="#"){continue;}
		if(strpos($line, ".")==0){continue;}

        if(preg_match("#^\##",$line,$re)){continue;}


        if (preg_match("#^PRIMARY\s+(.+?)\s+blockeddomain#i", $line, $re)) {$line=$re[1];}

        if(preg_match("#^(http|https|ftp|ftps):\/#",$line)){
            $parsed=parse_url($line);
            $line=$parsed["host"];
            if(preg_match("#^(.+?):([0-9]+)#",$line,$re)){$line=$re[1];}
        }

        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\/[0-9]+$#",$line)){
            continue;
        }


        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\s+(.+)#",
            $line,$re)){$line=trim($re[1]);}

        if(preg_match("#^www\.(.+)#", $line,$re)){$line=$re[1];}


        if($ipClass->isValid($line)){continue;}

        if(strlen($line)<4){
            echo "{warning} $line ($srcline)\n";
            continue;
        }

        if(preg_match("#^(.+?)\.$#",$line,$re)){
            echo "{warning} $line ($srcline)\n";
            continue;
        }
        if (strpos(" $line", "*") > 0) {continue;}
        if(isset($GLOBALS["ALCATZ"][$line])){continue;}
        if(isset($GLOBALS["ADDED"][$line])){continue;}

        $GLOBALS["ADDED"][$line]=true;
        $category = $catz->GET_CATEGORIES($line);
        if($category>0){
            $GLOBALS["ALCATZ"][$line]=1;
            continue;
        }
        if(preg_match("#\.com\.cn$#", $line)){$category=0;}

        $SKIPz[105]="Phishing";
        $SKIPz[92]="Malware";
        $SKIPz[46]="Dynamic DHCP ISP";
        $SKIPz[5]="category_publicite";
        $SKIPz[143]="category_tracker";

        if($category==7){$category=0;}

        if(strpos($line,"/")>0){continue;}

        if(isset($SKIPz[$category])){continue;}

        $GLOBALS["ALCATZ"][$line]=1;
        $CATZREP[140]="category_suspicious";
        $CATZREP[119]="category_travel";
        $CATZREP[81]="category_industry";


        if(isset($CATZREP[$category])){
            $tbl="{$CATZREP[$category]}";
            if($tbl<>"category_suspicious") {
                echo "MOVE $line from \"$tbl\" to \"category_malware\"\n";
            }
            $GLOBALS["ADDED"][$line]=true;
            $pos->QUERY_SQL("DELETE FROM $tbl WHERE sitename='$line'");
            $pos->QUERY_SQL("$sqlperf VALUES('$line') ON CONFLICT DO NOTHING");
            continue;
        }

        if($category>0){
            $categoryname=$catz->CategoryIntToStr($category);
            echo "$line --> SKIP $categoryname ($category)\n";
            continue;
        }
        $c++;
        echo "$line --> ADD Malwares\n";
        $pos->QUERY_SQL("$sqlperf VALUES ('$line') ON CONFLICT DO NOTHING");
		
		
	}
	
	@fclose($fp);

    echo "Added $c items\n";
	echo __FUNCTION__." Saving $currentmd5 in $md5_file\n";
	@file_put_contents($md5_file, $currentmd5);
	return $c;
	
}





function trackerfile($filename){
	if(!is_file($filename)){echo __FUNCTION__.":  $filename no such file\n";}
	
	$catz=new mysql_catz();
	$category=$catz->GET_CATEGORIES("google.com");
	if($category<>17){exit();}
	$category=$catz->GET_CATEGORIES("carrefour.com");
	if($category<>8){exit();}
	
	
	$unix=new unix();
	$q=new mysql_squid_builder();
	$family=new familysite();
	$f=explode("\n",@file_get_contents($filename));
	
	
	

	$q=new mysql_squid_builder();
	$catz=new mysql_catz();
	foreach ($f as $line){
		$line=trim(strtolower($line));
		$line=str_replace("127.0.0.1 ","",$line);
		$line=trim($line);
		if(strpos($line, "localhost")>0){continue;}
		if(substr($line, 0,1)=="#"){continue;}
		if(strpos($line, ".")==0){continue;}
		if(preg_match("#^www\.(.+)#", $line,$re)){$line=$re[1];}
		$Dize=strpos($line, "#");
		if($Dize>2){
			$tf=explode("#",$line);
			$line=trim($tf[0]);
			$line=trim(strtolower($line));
			
		}
		if($line==null){continue;}
		
		$FAMZ=$family->GetFamilySites($line);
		if($FAMZ==null){continue;}
		if($FAMZ=="co.cc"){continue;}
		if($FAMZ=="com.com"){continue;}
		if($FAMZ=="googletagservices.com"){continue;}
		if($FAMZ<>$line){continue;}
		$category=$catz->GET_CATEGORIES($FAMZ);
		$md5=md5("tracker".$FAMZ);
		if($category==null){
			echo __FUNCTION__.":  $FAMZ: NONE\tADD\n";
			$q->QUERY_SQL("INSERT INTO category_tracker (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'tracker','$FAMZ','1636b7346f2e261c5b21abfcaef45a69')");
			continue;
		}

		
		
	}

}


function sync_categories(){


	$q=new postgres_sql();
	$results=$q->QUERY_SQL("SELECT category_id,categorytable FROM personal_categories");
	while ($ligne = pg_fetch_assoc($results)) {
		$category_id=$ligne["category_id"];
		$categorytable=$ligne["categorytable"];
		echo "Table name: {$ligne["categorytable"]}\n";
        cleanTable($ligne["categorytable"]);
		$ligne2=pg_fetch_array($q->QUERY_SQL("SELECT count(*) as tcount FROM $categorytable"));
		$Number=$ligne2["tcount"];
		echo __FUNCTION__.":  $categorytable: $Number elements\n";
		$q->QUERY_SQL("UPDATE personal_categories SET items=$Number WHERE category_id=$category_id");

	}

}

function cleanTable($tablename){

    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%;%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%)%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%{%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%}%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%(%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%!%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%@%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%§%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%#%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%^%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%¥%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%<%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%>%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%,%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%|%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%$%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%=%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%-moz-transition:%'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.addr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.cdir'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.ua'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.ar'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='..com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='*.com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co.uk'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.co.uk'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='info'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='biz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='cdir'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='addr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='fr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='xyz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pw'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.co'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='www.co'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='gs'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='paris'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='tools'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='life'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='online'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='de.tc'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='da.ru'");

    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='net'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='biz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='de'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.id'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.in'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ir'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.jp'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.org.ar'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.th'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ug'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.cn'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.bo'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='family'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='network'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='media'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='club'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='info'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='live'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='today'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='stream'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='at'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='be'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='bid'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='biz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ch'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='cz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='cl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='de'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='dk'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='es'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='eu'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='eu.org'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='fr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='name'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='no'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='nl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='net.ar'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='me'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='org'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pw'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ro'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='it'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='is'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='us'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='kr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='xyz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='me.net'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='gdn'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='kim'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pics'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='gd'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='im'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='on'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ps'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='la'");


}


function sparc_ioc(){
	$catz=new mysql_catz();
	$q=new mysql_squid_builder();
	$category=$catz->GET_CATEGORIES("google.com");
	if($category<>17){exit();}
	$category=$catz->GET_CATEGORIES("carrefour.com");
	if($category<>8){exit();}
	$fam=new squid_familysite();
	$f=array();
	$today=date("Y-m-d");
	$today="2018-01-07";
	$today=date("Y-m-d");
	
	
	
	$ipClass=new IP();
	$pos=new postgres_sql();
	for($i=0;$i<35;$i++){
		$url="https://sparkioc.com/historylist?n=$today&z=$i";
		echo __FUNCTION__.":  $url\n";

		echo __FUNCTION__.": Checking $url\n";$curl=new ccurl($url);
		$unix=new unix();
		$targetpath=$unix->FILE_TEMP();
		if(!$curl->GetFile($targetpath)){echo "FAILED GETFAILE\n";continue;}
		$pp=explode("\n",@file_get_contents($targetpath));
		@unlink($targetpath);
		
		echo count($pp)." lines\n";
		$c=0;
		foreach ($pp as $ligne){
			if(trim($ligne)==null){continue;}
			$ligne=strtolower($ligne);
			if(!preg_match("#sparkhistory\?ioc=(.+?)\"#", $ligne,$re)){continue;}
			$www=$re[1];
			if(preg_match("#www\.(.+)#", $www,$re)){$www=$re[1];}
			if(strpos($www, ".")==0){continue;}
			$category=$catz->GET_CATEGORIES($www);
			if($category>0){
				echo __FUNCTION__.":  $www SKIP -> $category\n";
				continue;}
			if($ipClass->isValid($www)){$www=ip2long($www).".addr";}
			$www=utf8_encode($www);
			$pos=new postgres_sql();
			$c++;
			echo __FUNCTION__.":  $www\n";
			$f[]="('$www')";
		}
			
		if(count($f)>0){
			$pos->QUERY_SQL("INSERT INTO category_suspicious (sitename) VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING");
			if(!$pos->ok){echo $pos->mysql_error."\n";return;}
			$f=array();
		}
			
			
	}
	
	if(count($f)>0){
		$pos->QUERY_SQL("INSERT INTO category_suspicious (sitename) VALUES ".@implode(",", $f)." ON CONFLICT DO NOTHING");
		if(!$pos->ok){echo $pos->mysql_error."\n";return;}
		$f=array();
	}

}

function discapitol(){
    $localdir="/root/blacklists";
    shell_exec("rsync -ar rsync://ftp.ut-capitole.fr/blacklist $localdir/");
    $dbs["webmail"]="category_webmail";
    $dbs["ddos"]="category_hacking";
    $dbs["mixed_adult"]="category_mixed_adult";
    $dbs["drogue"]="category_drugs";
    //$dbs["exceptions_liste_bu"]="";
    $dbs["bank"]="category_finance_banking";
    $dbs["phishing"]="category_phishing";
    $dbs["bitcoin"]="";
    $dbs["local"]="category_internal";
    $dbs["forums"]="category_forums";
    $dbs["sexual_education"]="category_sexual_education";
    $dbs["tricheur"]="";
    $dbs["malware"]="category_malware";
    $dbs["cryptojacking"]="";
    $dbs["sports"]="category_recreation_sports";
    $dbs["publicite"]="category_publicite";
    $dbs["games"]="category_games";
    $dbs["astrology"]="category_astrology";
    $dbs["stalkerware"]="category_tracker";
    $dbs["doh"]="category_science_computing";
    $dbs["radio"]="category_webradio";
    $dbs["update"]="category_updatesites";
    $dbs["examen_pix"]="";
    $dbs["strict_redirector"]="";
    $dbs["vpn"]="category_proxy";
    $dbs["blog"]="category_blog";
    $dbs["manga"]="category_manga";
    $dbs["redirector"]="category_proxy";
    $dbs["strong_redirector"]="category_redirector";
    $dbs["cooking"]="category_hobby_cooking";
    $dbs["child"]="category_children";
    $dbs["indisponible"]="";
    $dbs["lingerie"]="category_sex_lingerie";
    $dbs["jstor"]="";
    $dbs["financial"]="category_financial";
    $dbs["shortener"]="category_redirector";
    $dbs["special"]="";
    $dbs["marketingware"]="category_marketingware";
    $dbs["audio-video"]="category_audio_video";
    $dbs["hacking"]="category_hacking";
    $dbs["arjel"]="";
    $dbs["cleaning"]="category_cleaning";
    $dbs["filehosting"]="category_filehosting";
    $dbs["agressif"]="category_violence";
    $dbs["dangerous_material"]="category_dangerous_material";
    $dbs["mobile-phone"]="category_smartphones";
    $dbs["press"]="category_news";
    $dbs["download"]="category_downloads";
    $dbs["chat"]="category_chat";
    $dbs["shopping"]="category_shopping";
    $dbs["warez"]="category_warez";
    $dbs["dating"]="category_dating";
    $dbs["verisign"]="";
    $dbs["sect"]="category_sect";
    $dbs["jobsearch"]="category_jobsearch";
    $dbs["catalogue-biu-toulouse"]="";
    $dbs["associations_religieuses"]="category_religion";
    $dbs["dialer"]="";
    $dbs["celebrity"]="category_celebrity";
    $dbs["gambling"]="category_gamble";
    $dbs["adult"]="category_publicite";
    $dbs["social_networks"]="category_socialnet";
    $dbs["reaffected"]="category_reaffected";
    $dbs["educational_games"]="category_children";
    $dbs["translation"]="category_translators";
    $dbs["remote-control"]="category_remote_control";

    $workdir="/home/artica/download.lists";
    $hash=unserialize(@file_get_contents("$workdir/ftp.ut-capitole.fr.hash"));
    $q=new postgres_sql();

    $catz               = new mysql_catz();
    $redis = new Redis();

    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        squid_admin_mysql(0,"Redis error",$e->getMessage(),__FILE__,__LINE__);
        echo $e->getMessage() . "\n";
        return null;
    }
    $added=0;
    foreach ($dbs as $directory=>$tablename){
        if($tablename==null){continue;}
        $domain_path="$localdir/dest/$directory/domains";
        if(!is_file($domain_path)){continue;}
        $md5=md5_file($domain_path);
        echo "$domain_path = $md5 ( $tablename )\n";
        if(isset($hash[$directory])){
            if($hash[$directory] == $md5){
                continue;
            }
        }

        $f=explode("\n",@file_get_contents($domain_path));
        $sMax=count($f);
        $zl=0;$zout=0;$hits=0;$alcatz=0;
        $time=time();
        foreach ($f as $line){
            $zl++;
            $zout++;
            $line=cleanline(trim($line));
            if($line==null){continue;}

            $prc=round(($zl/$sMax)*100,2);

            if($zout>5000){
                $mins=0;
                $seconds=time()-$time;
                if($seconds>60){
                    $mins=round($mins/60);
                }
                $sitesSec=round($zout/$seconds,2);
                echo "$directory:($prc) $zl $sitesSec/sec ($seconds seconds - $mins minutes) Hits:$hits Already categoryzed:$alcatz\n";
                $zout=0;
                $time=time();
            }

            $CACHE=intval($redis->get("DomainToInt:$line"));
            if($CACHE>0){
                $hits++;
                continue;
            }
            if(preg_match("#ipfs.cf-ipfs.com|ipfs.nftstorage.link|ipfs.dweb.link|p.temp-site.link|ipfs.fleek.cool|promericaain.repl.co|ipfs.w3s.link|auth0rizesharedo.workers.dev|comcast-xfinity.mallboxz.club|vcikkso.dstech.us.com#",$line)){
                continue;

            }
            if($catz->GET_CATEGORIES($line)>0){
                $alcatz++;
                $redis->set("DomainToInt:$line",999);
                continue;
            }

            if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$line)){
                $redis->set("DomainToInt:$line",999);
                continue;
            }

            echo "ADD:$directory:($prc): $added/$zl:$line\n";
            $q->QUERY_SQL("INSERT INTO $tablename (sitename) VALUES('$line') ON CONFLICT DO NOTHING");
            $redis->set("DomainToInt:$line",999);
            $GLOBALS["ADDEDPLUS"]=$GLOBALS["ADDEDPLUS"]+1;
            $added++;
        }
        $hash[$directory] = $md5;


    }
    @file_put_contents("$workdir/ftp.ut-capitole.fr.hash",serialize($hash));
    echo "$added new websites....\n\n";

}

function cleanline($line):string{
    if(preg_match("#^(\#|:|;)#",$line)){return "";}
    $line=str_replace("*.","",$line);
    if(preg_match("#^\.(.+)#",$line,$re)){$line=$re[1];}
    if(preg_match("#^(.+?)\##",$line,$re)){$line=$re[1];}
    if(preg_match("#^0\.0\.0\.0\s+(.+)#",$line,$re)){$line=$re[1];}
    if(preg_match("#^[0-9\.]+\s+(.+)#",$line,$re)){$line=$re[1];}
    if(preg_match("#^[0-9\.]+\s+.*?\##",$line,$re)){return "";}
    if(preg_match("#^www\.(.+)#",$line,$re)){$line=$re[1];}
    if(preg_match("#^ww1\.(.+)#",$line,$re)){$line=$re[1];}
    if(preg_match("#^ww2\.(.+)#",$line,$re)){$line=$re[1];}
    if(preg_match("#^www\.www\.(.+)#",$line,$re)){$line=$re[1];}
    if(preg_match("#^[a-z0-9]+:[a-z0-9]+:[a-z0-9]+#",$line,$re)){return "";}
    if(preg_match("#\s+(ip6-localnet|ip6-mcastprefix|ip6-allnodes|ip6-allrouters|ip6-allhosts)#",$line,$re)){return "";}
    if(strpos("  $line","|")>0){return "";}
    if($line=="localhost"){return "";}
    if($line=="net.ar"){return "";}
    if($line=="me.net"){return "";}
    if($line=="da.ru"){return "";}
    if($line=="be"){return "";}
    if($line=="com"){return "";}
    if($line=="usa.cc"){return "";}
    return $line;
}

