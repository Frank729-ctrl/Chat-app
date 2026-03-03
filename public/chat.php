<?php
require_once "../includes/session.php";
require_once "../config/database.php";
require_once "../config/websocket.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.html");
    exit;
}

$conversationId = (int)($_GET['conv'] ?? 0);
$isEmbed        = isset($_GET['embed']);

if (!$conversationId) {
    header("Location: /public/dashboard.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.profile_image, u.is_online, u.last_seen
    FROM participants p
    JOIN users u ON u.id = p.user_id
    WHERE p.conversation_id = ? AND p.user_id != ?
    LIMIT 1
");
$stmt->execute([$conversationId, $_SESSION['user_id']]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partner) {
    header("Location: /public/dashboard.php");
    exit;
}

$meStmt = $pdo->prepare("SELECT id, full_name, profile_image FROM users WHERE id = ?");
$meStmt->execute([$_SESSION['user_id']]);
$currentUser = $meStmt->fetch(PDO::FETCH_ASSOC);

$msgStmt = $pdo->prepare("
    SELECT m.id, m.content, m.created_at, m.is_read,
           m.sender_id,
           u.full_name AS sender_name,
           (m.sender_id = :me) AS is_mine
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.conversation_id = :cid
    ORDER BY m.created_at DESC
    LIMIT 60
");
$msgStmt->execute(['me' => $_SESSION['user_id'], 'cid' => $conversationId]);
$initialMessages = array_reverse($msgStmt->fetchAll(PDO::FETCH_ASSOC));

$pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0")
    ->execute([$conversationId, $_SESSION['user_id']]);

$partnerAvatar = $partner['profile_image'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($partner['full_name']) . '&background=137fec&color=fff';
$myAvatar      = $currentUser['profile_image'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['full_name']) . '&background=6366f1&color=fff';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Chat – <?= htmlspecialchars($partner['full_name']) ?> | ChatConnect</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { primary: '#137fec', 'background-light': '#f6f7f8', 'background-dark': '#101922' },
                    fontFamily: { display: ['Plus Jakarta Sans', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.4); border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(100,116,139,0.4); }
        @keyframes fadeUp { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform: none; } }
        .msg-anim { animation: fadeUp 0.18s ease forwards; }
        .dot-pulse span { animation: dotPulse 1.4s infinite ease-in-out; }
        .dot-pulse span:nth-child(2) { animation-delay: 0.2s; }
        .dot-pulse span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dotPulse { 0%,80%,100% { transform:scale(0.7); opacity:0.4; } 40% { transform:scale(1); opacity:1; } }
        #ws-status { transition: all 0.3s; }

        /* ── Bubble sizing fix: shrink to content, cap at 75% ── */
        .msg-row        { display: flex; align-items: flex-end; gap: 0.5rem; }
        .msg-row.mine   { flex-direction: row-reverse; margin-left: auto; }
        .msg-inner      { display: flex; flex-direction: column; gap: 2px; max-width: min(75vw, 420px); }
        .msg-row.mine .msg-inner { align-items: flex-end; }
        .msg-row.theirs .msg-inner { align-items: flex-start; }
        .bubble         { display: inline-block; padding: 10px 16px; font-size: 0.875rem;
                          line-height: 1.55; white-space: pre-wrap; word-break: break-word;
                          border-radius: 1.25rem; }
        .bubble.mine    { background: #137fec; color: #fff; border-bottom-right-radius: 4px;
                          box-shadow: 0 2px 8px rgba(19,127,236,0.18); }
        .bubble.theirs  { background: #fff; color: #0f172a; border: 1px solid #f1f5f9;
                          border-bottom-left-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .dark .bubble.theirs { background: #1e293b; color: #f1f5f9; border-color: #334155; }
        .msg-meta       { display: flex; align-items: center; gap: 3px; padding: 0 4px; }
        .msg-meta span  { font-size: 0.7rem; color: #94a3b8; }

        /* Emoji picker */
        #emoji-picker { display:none; position:absolute; bottom:60px; left:0;
                        background:#fff; border:1px solid #e2e8f0; border-radius:12px;
                        padding:10px; box-shadow:0 8px 24px rgba(0,0,0,0.12);
                        display:none; flex-wrap:wrap; gap:4px; width:260px; z-index:50; }
        .dark #emoji-picker { background:#1e293b; border-color:#334155; }
        #emoji-picker button { font-size:1.4rem; padding:4px 6px; border-radius:6px;
                               cursor:pointer; background:none; border:none;
                               transition:background 0.1s; }
        #emoji-picker button:hover { background:rgba(0,0,0,0.07); }
        .dark #emoji-picker button:hover { background:rgba(255,255,255,0.1); }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 antialiased h-screen overflow-hidden">

<div class="flex flex-col h-screen bg-white dark:bg-slate-950">

    <!-- Header -->
    <header class="h-16 border-b border-slate-200 dark:border-slate-800 px-4 md:px-5 flex items-center justify-between bg-white/95 dark:bg-slate-950/95 backdrop-blur-md z-10 shrink-0">
        <div class="flex items-center gap-3">
            <?php if(!$isEmbed): ?>
            <a href="/public/dashboard.php" class="p-2 -ml-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <?php endif; ?>
            <div class="relative">
                <img class="w-10 h-10 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700"
                     src="<?= htmlspecialchars($partnerAvatar) ?>"
                     alt="<?= htmlspecialchars($partner['full_name']) ?>"/>
                <div id="online-dot" class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white dark:border-slate-950 transition-colors <?= $partner['is_online'] ? 'bg-green-500' : 'bg-slate-300 dark:bg-slate-600' ?>"></div>
            </div>
            <div>
                <h2 class="font-bold text-base leading-tight"><?= htmlspecialchars($partner['full_name']) ?></h2>
                <p id="status-text" class="text-xs font-medium <?= $partner['is_online'] ? 'text-green-600 dark:text-green-400' : 'text-slate-400' ?>">
                    <?= $partner['is_online'] ? 'Online' : 'Last seen ' . date('g:i A', strtotime($partner['last_seen'])) ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <div id="ws-status" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span>
                <span id="ws-status-text">Connecting…</span>
            </div>
        </div>
    </header>

    <!-- Messages -->
    <div id="messages-container" class="flex-1 overflow-y-auto px-4 md:px-6 py-4 space-y-2 custom-scrollbar bg-slate-50 dark:bg-slate-950">
        <?php
        $lastDate  = '';
        $lastSender = null;
        foreach ($initialMessages as $msg):
            $isMine      = (bool)$msg['is_mine'];
            $msgDate     = date('Y-m-d', strtotime($msg['created_at']));
            $today       = date('Y-m-d');
            $yesterday   = date('Y-m-d', strtotime('-1 day'));
            $displayDate = match($msgDate) {
                $today     => 'Today',
                $yesterday => 'Yesterday',
                default    => date('F j, Y', strtotime($msg['created_at']))
            };
            $timeStr     = date('g:i A', strtotime($msg['created_at']));
            $showAvatar  = !$isMine && $lastSender !== $msg['sender_id'];
        ?>
        <?php if($msgDate !== $lastDate): $lastDate = $msgDate; ?>
            <div class="flex justify-center my-4">
                <span class="px-4 py-1.5 bg-slate-200/80 dark:bg-slate-800/80 rounded-full text-xs font-semibold text-slate-500 dark:text-slate-400"><?= $displayDate ?></span>
            </div>
        <?php endif; ?>

        <div class="msg-row <?= $isMine ? 'mine' : 'theirs' ?>" data-msg-id="<?= $msg['id'] ?>">
            <?php if(!$isMine): ?>
            <img src="<?= htmlspecialchars($partnerAvatar) ?>"
                 class="w-8 h-8 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700 <?= $showAvatar ? '' : 'opacity-0 pointer-events-none' ?>"
                 alt="<?= htmlspecialchars($partner['full_name']) ?>"/>
            <?php endif; ?>
            <div class="msg-inner">
                <div class="bubble <?= $isMine ? 'mine' : 'theirs' ?>"><?= htmlspecialchars($msg['content']) ?></div>
                <div class="msg-meta">
                    <span><?= $timeStr ?></span>
                    <?php if($isMine): ?>
                        <span class="material-symbols-outlined <?= $msg['is_read'] ? 'text-primary' : '' ?> read-tick"
                              style="font-variation-settings:'FILL' 1; font-size:14px; color:<?= $msg['is_read'] ? '#137fec' : '#94a3b8' ?>">done_all</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php $lastSender = $msg['sender_id']; endforeach; ?>
    </div>

    <!-- Typing indicator -->
    <div id="typing-indicator" class="hidden px-6 py-2 shrink-0">
        <div class="flex items-center gap-2">
            <img src="<?= htmlspecialchars($partnerAvatar) ?>" class="w-6 h-6 rounded-full object-cover"/>
            <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl rounded-bl-sm px-4 py-2.5 shadow-sm">
                <div class="dot-pulse flex gap-1.5 items-center h-4">
                    <span class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500 inline-block"></span>
                    <span class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500 inline-block"></span>
                    <span class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500 inline-block"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Input -->
    <footer class="shrink-0 px-3 md:px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950">
        <div class="max-w-5xl mx-auto flex items-end gap-2">
            <div class="flex items-center gap-0.5 pb-1.5 relative">
                <!-- Emoji picker -->
                <div id="emoji-picker">
                    <?php
                    $emojis = ['😀','😂','😍','🥰','😎','🤔','😅','🙏','👍','👏','🔥','❤️','💯','🎉','😭','😊','🤣','😇','🥳','😴','😡','🤯','👀','💪','✨','🌟','🎯','💬','🚀','🌈'];
                    foreach($emojis as $e) echo "<button type='button' onclick=\"insertEmoji('$e')\">$e</button>";
                    ?>
                </div>
                <button id="emoji-btn" type="button" class="p-2.5 rounded-full text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Emoji">
                    <span class="material-symbols-outlined">mood</span>
                </button>
            </div>
            <div class="flex-1">
                <textarea id="message-input"
                    class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-2xl px-4 py-3 text-sm placeholder:text-slate-500 dark:text-slate-100 focus:ring-2 focus:ring-primary/40 resize-none min-h-[44px] max-h-36 custom-scrollbar outline-none"
                    placeholder="Type a message…" rows="1" autocomplete="off"></textarea>
            </div>
            <button id="send-btn"
                    class="mb-1.5 p-3 bg-primary text-white rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 active:scale-95 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    disabled title="Send">
                <span class="material-symbols-outlined">send</span>
            </button>
        </div>
        <p class="mt-1 text-center text-xs text-slate-400 hidden md:block">
            <kbd class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 rounded text-[10px]">Enter</kbd> send &bull;
            <kbd class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 rounded text-[10px]">Shift+Enter</kbd> new line
        </p>
    </footer>
</div>

<script>
const WS_URL        = <?= json_encode(WS_URL) ?>;
const MY_ID         = <?= (int)$_SESSION['user_id'] ?>;
const CONV_ID       = <?= $conversationId ?>;
const MY_AVATAR     = <?= json_encode($myAvatar) ?>;
const PARTNER_NAME  = <?= json_encode($partner['full_name']) ?>;
const PARTNER_AVATAR= <?= json_encode($partnerAvatar) ?>;
const PARTNER_ID    = <?= (int)$partner['id'] ?>;

const container    = document.getElementById('messages-container');
const input        = document.getElementById('message-input');
const sendBtn      = document.getElementById('send-btn');
const typingEl     = document.getElementById('typing-indicator');
const statusDot    = document.getElementById('online-dot');
const statusText   = document.getElementById('status-text');
const wsStatus     = document.getElementById('ws-status');
const wsStatusText = document.getElementById('ws-status-text');
const emojiBtn     = document.getElementById('emoji-btn');
const emojiPicker  = document.getElementById('emoji-picker');

// Dark mode
try {
    const theme = (window.parent !== window ? window.parent.localStorage : localStorage).getItem('cc-theme') || 'light';
    if (theme === 'dark') document.documentElement.classList.add('dark');
} catch(e) {}

// Emoji picker toggle
emojiBtn.addEventListener('click', e => {
    e.stopPropagation();
    const isOpen = emojiPicker.style.display === 'flex';
    emojiPicker.style.display = isOpen ? 'none' : 'flex';
});
document.addEventListener('click', () => emojiPicker.style.display = 'none');
emojiPicker.addEventListener('click', e => e.stopPropagation());

function insertEmoji(emoji) {
    const pos = input.selectionStart;
    input.value = input.value.slice(0, pos) + emoji + input.value.slice(pos);
    input.focus();
    input.selectionStart = input.selectionEnd = pos + emoji.length;
    input.dispatchEvent(new Event('input'));
    emojiPicker.style.display = 'none';
}

// WebSocket
let ws, wsReconnectDelay = 1000, typingTimer, isTyping = false, lastDate = '';

function connect() {
    ws = new WebSocket(WS_URL);
    ws.onopen = () => {
        wsReconnectDelay = 1000;
        setWsStatus('connected');
        ws.send(JSON.stringify({ type: 'auth', user_id: MY_ID }));
        ws.send(JSON.stringify({ type: 'read', conversation_id: CONV_ID }));
    };
    ws.onclose = () => {
        setWsStatus('disconnected');
        sendBtn.disabled = true;
        setTimeout(connect, wsReconnectDelay);
        wsReconnectDelay = Math.min(wsReconnectDelay * 2, 15000);
    };
    ws.onerror = () => setWsStatus('error');
    ws.onmessage = ({ data }) => {
        let msg; try { msg = JSON.parse(data); } catch { return; }
        handleServerMessage(msg);
    };
}

function setWsStatus(state) {
    const c = {
        connecting:   { text:'Connecting…',   cls:'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', dot:'bg-yellow-500 animate-pulse' },
        connected:    { text:'Connected',     cls:'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',    dot:'bg-green-500' },
        disconnected: { text:'Reconnecting…', cls:'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',            dot:'bg-red-500 animate-pulse' },
        error:        { text:'Error',         cls:'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',            dot:'bg-red-500' },
    }[state];
    wsStatus.className = `flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold transition-all ${c.cls}`;
    wsStatusText.textContent = c.text;
    wsStatus.querySelector('span').className = `w-1.5 h-1.5 rounded-full ${c.dot}`;
}

function handleServerMessage(msg) {
    switch(msg.type) {
        case 'auth_ok':
            sendBtn.disabled = false;
            input.focus();
            break;

        case 'message':
            if (msg.conversation_id == CONV_ID) {
                if (msg.sender_id == MY_ID) {
                    // Stamp the optimistic bubble with real ID — don't re-render
                    const opt = container.querySelector('[data-optimistic="1"]');
                    if (opt) { opt.dataset.msgId = msg.id; opt.dataset.optimistic = '0'; }
                } else {
                    const existing = container.querySelector(`[data-msg-id="${msg.id}"]`);
                    if (!existing) {
                        hideTyping();
                        appendMessage(msg);
                        scrollToBottom();
                    }
                    ws.send(JSON.stringify({ type: 'read', conversation_id: CONV_ID }));
                }
                // Tell dashboard to refresh sidebar preview
                notifyDashboard(msg);
            }
            break;

        case 'typing':
            if (msg.conversation_id == CONV_ID && msg.user_id != MY_ID) {
                msg.is_typing ? showTyping() : hideTyping();
            }
            break;

        case 'read':
            if (msg.conversation_id == CONV_ID) {
                container.querySelectorAll('.read-tick').forEach(el => {
                    el.style.color = '#137fec';
                });
            }
            break;

        case 'presence':
            if (msg.user_id == PARTNER_ID) {
                if (msg.is_online) {
                    statusDot.style.background = '#22c55e';
                    statusText.textContent = 'Online';
                    statusText.className = 'text-xs font-medium text-green-600 dark:text-green-400';
                } else {
                    statusDot.style.background = '';
                    statusText.textContent = 'Offline';
                    statusText.className = 'text-xs font-medium text-slate-400';
                }
            }
            break;
    }
}

// Tell the parent dashboard to update the conversation preview in real time
function notifyDashboard(msg) {
    try {
        if (window.parent && window.parent !== window) {
            window.parent.updateConvPreview(CONV_ID, msg.content || '');
        }
    } catch(e) {}
}

function appendMessage(msg, optimistic = false) {
    const isMine  = msg.is_mine === true || msg.is_mine == 1 || msg.sender_id == MY_ID;
    const created = new Date((msg.created_at || '').replace(' ', 'T'));
    const timeStr = isNaN(created) ? '' : created.toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });
    const dateStr = isNaN(created) ? '' : created.toLocaleDateString([], { month:'short', day:'numeric', year:'numeric' });
    const today   = new Date().toLocaleDateString([], { month:'short', day:'numeric', year:'numeric' });
    const dispDate = dateStr === today ? 'Today' : dateStr;

    if (dateStr && dispDate !== lastDate) {
        lastDate = dispDate;
        const sep = document.createElement('div');
        sep.className = 'flex justify-center my-4';
        sep.innerHTML = `<span class="px-4 py-1.5 bg-slate-200/80 dark:bg-slate-800/80 rounded-full text-xs font-semibold text-slate-500 dark:text-slate-400">${dispDate}</span>`;
        container.appendChild(sep);
    }

    const row = document.createElement('div');
    row.className = `msg-row msg-anim ${isMine ? 'mine' : 'theirs'}`;
    if (msg.id) row.dataset.msgId = msg.id;
    if (optimistic) row.dataset.optimistic = '1';

    if (!isMine) {
        const img = document.createElement('img');
        img.src = PARTNER_AVATAR; img.alt = PARTNER_NAME;
        img.className = 'w-8 h-8 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700';
        row.appendChild(img);
    }

    const inner = document.createElement('div');
    inner.className = 'msg-inner';

    const bubble = document.createElement('div');
    bubble.className = `bubble ${isMine ? 'mine' : 'theirs'}`;
    bubble.textContent = msg.content;

    const meta = document.createElement('div');
    meta.className = 'msg-meta';
    meta.innerHTML = `<span>${timeStr}</span>
        ${isMine ? `<span class="material-symbols-outlined read-tick" style="font-variation-settings:'FILL' 1; font-size:14px; color:#94a3b8;">done_all</span>` : ''}`;

    inner.append(bubble, meta);
    row.appendChild(inner);
    container.appendChild(row);
}

function showTyping() { typingEl.classList.remove('hidden'); scrollToBottom(); }
function hideTyping()  { typingEl.classList.add('hidden'); }

function sendMessage() {
    const content = input.value.trim();
    if (!content || !ws || ws.readyState !== WebSocket.OPEN) return;
    appendMessage({ content, sender_id: MY_ID, is_mine: true, created_at: new Date().toISOString().replace('T',' ').slice(0,19) }, true);
    scrollToBottom();
    ws.send(JSON.stringify({ type: 'message', conversation_id: CONV_ID, content }));
    sendTyping(false);
    input.value = '';
    input.style.height = 'auto';
}

function sendTyping(typing) {
    if (!ws || ws.readyState !== WebSocket.OPEN || typing === isTyping) return;
    isTyping = typing;
    ws.send(JSON.stringify({ type: 'typing', conversation_id: CONV_ID, is_typing: typing }));
}

function scrollToBottom(smooth = true) {
    container.scrollTo({ top: container.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
}

sendBtn.addEventListener('click', sendMessage);
input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
input.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 144) + 'px';
    if (this.value.trim()) {
        sendTyping(true);
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => sendTyping(false), 2500);
    } else {
        sendTyping(false);
    }
    sendBtn.disabled = !this.value.trim() || !ws || ws.readyState !== WebSocket.OPEN;
});

document.addEventListener('visibilitychange', () => {
    if (!document.hidden && ws && ws.readyState === WebSocket.CLOSED) connect();
});

scrollToBottom(false);
connect();
</script>
</body>
</html>