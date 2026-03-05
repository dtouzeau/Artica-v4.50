<?php
/**
 * Remote Terminal Console for Network Agents
 * Opens a full-screen xterm.js terminal in a NEW browser tab.
 *
 * Entry point (from parent page):  Loadjs('fw.netagents.shell.php?id={agent_id}')
 * Standalone page (new tab):       fw.netagents.shell.php?terminal={agent_id}
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

if(isset($_GET["terminal"])){terminal();exit;}
js();

/**
 * js() — called via Loadjs() from the parent page.
 * Validates privileges then opens the terminal in a new browser tab.
 */
function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsArticaMetaAdmin){
        return $tpl->js_error("{no_privileges}");
    }
    $id=intval($_GET["id"]);
    if($id<1){
        return $tpl->js_error("Invalid Agent ID");
    }
    header("content-type: application/x-javascript");
    echo "s_PopUpFull('$page?terminal=$id',1024,768,'Unix Console')";
    return true;

}

/**
 * terminal() — standalone full-page terminal.
 * Self-contained HTML document (no parent-page dependencies).
 * Session is validated server-side by the Go WebSocket handler via PHPSESSID cookie.
 */
function terminal(){
    session_start();
    if(!isset($_SESSION["uid"]) || $_SESSION["uid"]===null){
        header("Location: fw.login.php");
        exit;
    }

    $id=intval($_GET["terminal"]);
    if($id<1){
        echo "<!DOCTYPE html><html><body><h2>Invalid Agent ID</h2></body></html>";
        return;
    }

    // Verify agent exists
    $status_raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id");
    $status=json_decode($status_raw);
    $net=new ArticaNetAgents($id);
    $hostname=$net->GetAgentHostname();
    if(empty($hostname)) $hostname="Agent #$id";
    $hostnameJs=addslashes($hostname);

    $error=null;
    if(isset($status->Status) && !$status->Status){
        $error=$status->Error ?? "Agent not found";
    }

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Terminal - <?php echo htmlspecialchars($hostname);?></title>
    <link rel="stylesheet" href="angular/font-awesome/css/all.min.css"/>
    <link rel="stylesheet" href="angular/js/plugins/xterm/xterm.min.css"/>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        html,body{width:100%;height:100%;overflow:hidden;background:#1e1e1e;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,monospace;}
        #status-bar{
            height:32px;padding:0 12px;background:#252526;color:#888;font-size:12px;
            display:flex;align-items:center;justify-content:space-between;
            border-bottom:1px solid #333;user-select:none;
        }
        #status-bar .left{display:flex;align-items:center;gap:8px;}
        #status-bar .right{display:flex;align-items:center;gap:12px;}
        #status-bar .hostname{color:#4ec9b0;font-weight:600;}
        #status-bar .status-dot{font-size:9px;}
        #status-bar .dim{color:#555;}
        #terminal-container{position:absolute;top:32px;left:0;right:0;bottom:0;background:#1e1e1e;}
        #reconnect-bar{
            display:none;position:fixed;bottom:0;left:0;right:0;z-index:10;
            padding:8px 0;background:rgba(40,20,20,0.95);text-align:center;
            border-top:1px solid #5a2020;
        }
        #reconnect-bar span{color:#e77;font-size:13px;margin-right:12px;}
        #reconnect-bar button{
            background:#f0ad4e;color:#000;border:none;border-radius:3px;
            padding:4px 14px;font-size:12px;cursor:pointer;
        }
        #reconnect-bar button:hover{background:#ec971f;}
        #error-page{
            display:flex;align-items:center;justify-content:center;height:100vh;
            color:#ccc;font-size:16px;flex-direction:column;gap:10px;
        }
        #error-page i{font-size:48px;color:#e74c3c;}
    </style>
</head>
<body>
<?php if($error): ?>
    <div id="error-page">
        <i class="fas fa-exclamation-triangle"></i>
        <div><?php echo htmlspecialchars($error);?></div>
        <div style="color:#666;font-size:13px"><?php echo htmlspecialchars($hostname);?></div>
    </div>
<?php else: ?>
    <div id="status-bar">
        <div class="left">
            <i class="fas fa-terminal" style="color:#4ec9b0"></i>
            <span class="hostname"><?php echo htmlspecialchars($hostname);?></span>
            <span class="dim">|</span>
            <span id="status-text">Connecting...</span>
        </div>
        <div class="right">
            <span id="status-dot" class="status-dot"><i class="fas fa-circle-notch fa-spin" style="color:#f0ad4e"></i></span>
            <span class="dim" id="dimensions"></span>
        </div>
    </div>
    <div id="terminal-container"></div>
    <div id="reconnect-bar">
        <span><i class="fas fa-exclamation-triangle"></i> Connection lost</span>
        <button onclick="shellReconnect()"><i class="fas fa-redo"></i> Reconnect</button>
    </div>

    <script src="angular/js/plugins/xterm/xterm.min.js"></script>
    <script src="angular/js/plugins/xterm/xterm-addon-fit.min.js"></script>
    <script>
    (function(){
        var AGENT_ID   = <?php echo $id;?>;
        var HOSTNAME   = '<?php echo $hostnameJs;?>';
        var term       = null;
        var ws         = null;
        var fitAddon   = null;

        var elStatus   = document.getElementById('status-text');
        var elDot      = document.getElementById('status-dot');
        var elDims     = document.getElementById('dimensions');
        var elReconn   = document.getElementById('reconnect-bar');

        function setStatus(text, icon, color){
            if(elStatus) elStatus.textContent = text;
            if(elDot)    elDot.innerHTML = "<i class='"+icon+"' style='color:"+color+"'></i>";
        }

        function updateDimensions(){
            if(term && elDims) elDims.textContent = term.cols + ' x ' + term.rows;
        }

        function initTerminal(){
            if(term){ term.dispose(); term = null; }

            term = new Terminal({
                cursorBlink: true,
                fontSize: 14,
                fontFamily: 'Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
                theme: {
                    background:          '#1e1e1e',
                    foreground:          '#d4d4d4',
                    cursor:              '#aeafad',
                    selectionBackground: '#264f78',
                    black:   '#1e1e1e', red:     '#f44747', green:   '#6a9955', yellow:  '#d7ba7d',
                    blue:    '#569cd6', magenta: '#c586c0', cyan:    '#4ec9b0', white:   '#d4d4d4',
                    brightBlack: '#808080', brightRed:   '#f44747', brightGreen: '#6a9955',
                    brightYellow:'#d7ba7d', brightBlue:  '#569cd6', brightMagenta:'#c586c0',
                    brightCyan:  '#4ec9b0', brightWhite: '#ffffff'
                }
            });

            fitAddon = new FitAddon.FitAddon();
            term.loadAddon(fitAddon);

            var container = document.getElementById('terminal-container');
            if(!container) return;
            term.open(container);

            try{ fitAddon.fit(); }catch(e){}
            updateDimensions();
        }

        function connect(){
            if(elReconn) elReconn.style.display = 'none';
            initTerminal();
            setStatus('Connecting to ' + HOSTNAME + '...', 'fas fa-circle-notch fa-spin', '#f0ad4e');
            document.title = 'Connecting - ' + HOSTNAME;

            var proto = (location.protocol === 'https:') ? 'wss:' : 'ws:';
            var wsUrl = proto + '//' + location.host + '/netagents/shell/' + AGENT_ID;

            ws = new WebSocket(wsUrl);
            ws.binaryType = 'arraybuffer';

            ws.onopen = function(){
                setStatus('Connected', 'fas fa-circle', '#27ae60');
                document.title = HOSTNAME;
                term.focus();
                sendResize();
            };

            ws.onmessage = function(evt){
                var data = new Uint8Array(evt.data);
                if(data.length < 1) return;
                // 0x31 = '1' = stdout from agent
                if(data[0] === 0x31){
                    term.write(data.slice(1));
                }
            };

            ws.onclose = function(evt){
                setStatus('Disconnected (code ' + evt.code + ')', 'fas fa-circle', '#e74c3c');
                document.title = '[Closed] ' + HOSTNAME;
                if(elReconn) elReconn.style.display = 'block';
                if(term) term.write('\r\n\x1b[31m--- Connection closed ---\x1b[0m\r\n');
            };

            ws.onerror = function(){
                setStatus('Connection error', 'fas fa-exclamation-circle', '#e74c3c');
            };

            // Keyboard → agent stdin (prefix 0x30 = '0')
            term.onData(function(data){
                if(!ws || ws.readyState !== WebSocket.OPEN) return;
                var buf = new Uint8Array(data.length + 1);
                buf[0] = 0x30;
                for(var i = 0; i < data.length; i++) buf[i+1] = data.charCodeAt(i);
                ws.send(buf.buffer);
            });

            // Terminal resize → agent (prefix 0x32 = '2')
            term.onResize(function(){
                updateDimensions();
                sendResize();
            });
        }

        function sendResize(){
            if(!ws || ws.readyState !== WebSocket.OPEN || !term) return;
            var json = JSON.stringify({cols: term.cols, rows: term.rows});
            var buf  = new Uint8Array(json.length + 1);
            buf[0] = 0x32;
            for(var i = 0; i < json.length; i++) buf[i+1] = json.charCodeAt(i);
            ws.send(buf.buffer);
        }

        // Fit terminal on window resize
        var resizeTimer = null;
        window.addEventListener('resize', function(){
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function(){
                if(fitAddon) try{ fitAddon.fit(); }catch(e){}
                updateDimensions();
            }, 150);
        });

        // Clean up on tab/window close
        window.addEventListener('beforeunload', function(){
            if(ws) try{ ws.close(); }catch(e){}
        });

        // Expose reconnect for button
        window.shellReconnect = function(){
            if(ws) try{ ws.close(); }catch(e){}
            connect();
        };

        // Go
        connect();
    })();
    </script>
<?php endif; ?>
</body>
</html>
<?php } ?>
