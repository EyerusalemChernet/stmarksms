{{-- ═══════════════════════════════════════════════════════════════════════
     St. Mark AI Chat — Sidebar Panel
═══════════════════════════════════════════════════════════════════════ --}}
<style>
/* ── Floating trigger button ─────────────────────────────────────────────── */
#ai-chat-btn {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #fff;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(79,70,229,.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    z-index: 10000;
    transition: transform .2s, box-shadow .2s;
}
#ai-chat-btn:hover { transform: scale(1.1); box-shadow: 0 6px 28px rgba(79,70,229,.6); }
#ai-unread-badge {
    position: absolute;
    top: -2px; right: -2px;
    width: 18px; height: 18px;
    background: #ef4444;
    border-radius: 50%;
    font-size: 10px; font-weight: 700;
    display: none;
    align-items: center; justify-content: center;
    border: 2px solid #fff;
    color: #fff;
}

/* ── Overlay ─────────────────────────────────────────────────────────────── */
#ai-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.35);
    z-index: 10001;
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s;
}
#ai-overlay.open { opacity: 1; pointer-events: all; }

/* ── Sidebar panel ───────────────────────────────────────────────────────── */
#ai-sidebar {
    position: fixed;
    top: 0; right: 0;
    width: 420px;
    max-width: 100vw;
    height: 100vh;
    background: #fff;
    z-index: 10002;
    display: flex;
    flex-direction: column;
    box-shadow: -6px 0 40px rgba(0,0,0,.18);
    transform: translateX(100%);
    transition: transform .3s cubic-bezier(.4,0,.2,1);
}
#ai-sidebar.open { transform: translateX(0); }

/* ── Header ──────────────────────────────────────────────────────────────── */
.ai-header {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #fff;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.ai-header-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.ai-header-info { flex: 1; min-width: 0; }
.ai-header-name { font-size: 15px; font-weight: 700; }
.ai-header-status { font-size: 11px; opacity: .8; display: flex; align-items: center; gap: 5px; margin-top: 2px; }
.ai-status-dot { width: 7px; height: 7px; border-radius: 50%; background: #4ade80; display: inline-block; flex-shrink: 0; }
.ai-status-dot.offline { background: #f87171; }
.ai-header-actions { display: flex; gap: 6px; }
.ai-hbtn {
    background: rgba(255,255,255,.15);
    border: none; color: #fff;
    width: 32px; height: 32px;
    border-radius: 8px;
    cursor: pointer; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.ai-hbtn:hover { background: rgba(255,255,255,.28); }

/* ── Messages area ───────────────────────────────────────────────────────── */
.ai-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: #f5f3ff;
}
.ai-messages::-webkit-scrollbar { width: 5px; }
.ai-messages::-webkit-scrollbar-thumb { background: #c4b5fd; border-radius: 3px; }

/* ── Bubbles ─────────────────────────────────────────────────────────────── */
.ai-msg { display: flex; gap: 10px; max-width: 100%; }
.ai-msg.user { flex-direction: row-reverse; }
.ai-msg-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: #ede9fe; color: #4f46e5;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; flex-shrink: 0; margin-top: 2px;
}
.ai-msg.user .ai-msg-avatar { background: #4f46e5; color: #fff; }
.ai-msg-body { max-width: 82%; display: flex; flex-direction: column; }
.ai-msg.user .ai-msg-body { align-items: flex-end; }
.ai-msg-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.55;
    word-break: break-word;
}
.ai-msg.assistant .ai-msg-bubble {
    background: #fff;
    color: #111827;
    border-radius: 4px 16px 16px 16px;
    box-shadow: 0 1px 5px rgba(0,0,0,.08);
}
.ai-msg.user .ai-msg-bubble {
    background: #4f46e5;
    color: #fff;
    border-radius: 16px 4px 16px 16px;
}
.ai-msg-time { font-size: 10px; color: #9ca3af; margin-top: 4px; padding: 0 2px; }

/* ── Typing dots ─────────────────────────────────────────────────────────── */
.ai-typing { display: flex; gap: 5px; align-items: center; padding: 12px 14px; }
.ai-typing span {
    width: 8px; height: 8px; border-radius: 50%;
    background: #a5b4fc; display: inline-block;
    animation: aiDot 1.2s infinite;
}
.ai-typing span:nth-child(2) { animation-delay: .2s; }
.ai-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes aiDot { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-7px)} }

/* ── Suggestions ─────────────────────────────────────────────────────────── */
.ai-suggestions {
    padding: 10px 18px;
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
    background: #f5f3ff;
    border-top: 1px solid #ede9fe;
    flex-shrink: 0;
}
.ai-sug-btn {
    background: #ede9fe; color: #4f46e5;
    border: none; padding: 5px 12px;
    border-radius: 20px; font-size: 12px;
    cursor: pointer; transition: background .15s;
    white-space: nowrap;
}
.ai-sug-btn:hover { background: #ddd6fe; }

/* ── Input area ──────────────────────────────────────────────────────────── */
.ai-input-area {
    padding: 14px 18px;
    border-top: 1px solid #ede9fe;
    display: flex;
    gap: 10px;
    align-items: flex-end;
    background: #fff;
    flex-shrink: 0;
}
#ai-input {
    flex: 1;
    border: 1.5px solid #e0e7ff;
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 13.5px;
    resize: none;
    outline: none;
    max-height: 120px;
    min-height: 42px;
    line-height: 1.45;
    font-family: inherit;
    color: #111827;
    transition: border-color .15s;
    background: #fafbff;
}
#ai-input:focus { border-color: #4f46e5; background: #fff; }
#ai-send-btn {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: #4f46e5; color: #fff;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
    transition: background .15s;
}
#ai-send-btn:hover { background: #4338ca; }
#ai-send-btn:disabled { background: #a5b4fc; cursor: not-allowed; }

/* ── Powered by footer ───────────────────────────────────────────────────── */
.ai-footer {
    text-align: center;
    font-size: 10px;
    color: #9ca3af;
    padding: 6px;
    background: #fff;
    border-top: 1px solid #f3f4f6;
    flex-shrink: 0;
}

@media (max-width: 480px) {
    #ai-sidebar { width: 100vw; }
    #ai-chat-btn { right: 16px; bottom: 16px; }
}
</style>

{{-- Overlay --}}
<div id="ai-overlay" onclick="closeAIChat()"></div>

{{-- Floating trigger --}}
<button id="ai-chat-btn" title="AI Assistant" onclick="toggleAIChat()">
    <i class="bi bi-robot"></i>
    <span id="ai-unread-badge"></span>
</button>

{{-- Sidebar --}}
<div id="ai-sidebar">

    {{-- Header --}}
    <div class="ai-header">
        <div class="ai-header-avatar"><i class="bi bi-robot"></i></div>
        <div class="ai-header-info">
            <div class="ai-header-name">St. Mark AI Assistant</div>
            <div class="ai-header-status">
                <span class="ai-status-dot" id="ai-status-dot"></span>
                <span id="ai-status-text">Checking...</span>
            </div>
        </div>
        <div class="ai-header-actions">
            <button class="ai-hbtn" title="Clear chat" onclick="clearAIChat()">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
            <button class="ai-hbtn" title="Close" onclick="closeAIChat()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    {{-- Messages --}}
    <div class="ai-messages" id="ai-messages"></div>

    {{-- Quick suggestions --}}
    <div class="ai-suggestions" id="ai-suggestions">
        @if(App\Helpers\Qs::userIsTeamSA())
            <button class="ai-sug-btn" onclick="aiSuggest(this)">How many students?</button>
            <button class="ai-sug-btn" onclick="aiSuggest(this)">Upcoming events</button>
            <button class="ai-sug-btn" onclick="aiSuggest(this)">Current academic year</button>
            <button class="ai-sug-btn" onclick="aiSuggest(this)">Total teachers</button>
        @elseif(App\Helpers\Qs::userIsTeacher())
            <button class="ai-sug-btn" onclick="aiSuggest(this)">My subjects</button>
            <button class="ai-sug-btn" onclick="aiSuggest(this)">Upcoming exams</button>
            <button class="ai-sug-btn" onclick="aiSuggest(this)">School calendar</button>
        @elseif(App\Helpers\Qs::userIsParent())
            <button class="ai-sug-btn" onclick="aiSuggest(this)">My children's classes</button>
            <button class="ai-sug-btn" onclick="aiSuggest(this)">Upcoming events</button>
            <button class="ai-sug-btn" onclick="aiSuggest(this)">School schedule</button>
        @else
            <button class="ai-sug-btn" onclick="aiSuggest(this)">Upcoming events</button>
            <button class="ai-sug-btn" onclick="aiSuggest(this)">School calendar</button>
        @endif
        <button class="ai-sug-btn" onclick="aiSuggest(this)">Help</button>
    </div>

    {{-- Input --}}
    <div class="ai-input-area">
        <textarea id="ai-input"
                  placeholder="Ask anything about the school..."
                  rows="1"
                  onkeydown="aiKeydown(event)"
                  oninput="aiResize(this)"></textarea>
        <button id="ai-send-btn" onclick="aiSend()" title="Send (Enter)">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>

    <div class="ai-footer">Powered by Ollama · Responses may not always be accurate</div>
</div>

<script>
(function () {
    var open      = false;
    var history   = [];
    var busy      = false;
    var chatUrl   = '{{ route("ai.chat") }}';
    var statusUrl = '{{ route("ai.chat.status") }}';
    var csrf      = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var userName  = {!! json_encode(Auth::user()->name ?? 'User') !!};
    var initial   = userName.charAt(0).toUpperCase();

    // ── Open / close ──────────────────────────────────────────────────────────
    window.toggleAIChat = function () { open ? closeAIChat() : openAIChat(); };

    window.openAIChat = function () {
        open = true;
        document.getElementById('ai-sidebar').classList.add('open');
        document.getElementById('ai-overlay').classList.add('open');
        document.getElementById('ai-unread-badge').style.display = 'none';
        if (history.length === 0) showWelcome();
        checkStatus();
        setTimeout(function () { document.getElementById('ai-input').focus(); }, 320);
    };

    window.closeAIChat = function () {
        open = false;
        document.getElementById('ai-sidebar').classList.remove('open');
        document.getElementById('ai-overlay').classList.remove('open');
    };

    // ── Welcome ───────────────────────────────────────────────────────────────
    function showWelcome() {
        var first = userName.split(' ')[0];
        addMsg('assistant', 'Hello, ' + first + '! 👋 I\'m your St. Mark School AI Assistant.\n\nI can answer questions about students, events, the academic calendar, and more. How can I help you today?');
    }

    // ── Status ────────────────────────────────────────────────────────────────
    function checkStatus() {
        fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            var dot  = document.getElementById('ai-status-dot');
            var txt  = document.getElementById('ai-status-text');
            if (d.ok) {
                dot.classList.remove('offline');
                txt.textContent = 'Online · ' + (d.model || 'AI');
            } else {
                dot.classList.add('offline');
                txt.textContent = 'Offline — run: ollama serve';
            }
        })
        .catch(function () {
            document.getElementById('ai-status-dot').classList.add('offline');
            document.getElementById('ai-status-text').textContent = 'Offline';
        });
    }

    // ── Send ──────────────────────────────────────────────────────────────────
    window.aiSend = function () {
        var input = document.getElementById('ai-input');
        var msg   = input.value.trim();
        if (!msg || busy) return;

        input.value = '';
        aiResize(input);
        document.getElementById('ai-suggestions').style.display = 'none';

        addMsg('user', msg);
        history.push({ role: 'user', content: msg });

        showTyping();
        busy = true;
        document.getElementById('ai-send-btn').disabled = true;

        fetch(chatUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ message: msg, history: history.slice(0, -1) }),
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            hideTyping();
            busy = false;
            document.getElementById('ai-send-btn').disabled = false;
            var reply = d.reply || 'Sorry, I could not generate a response.';
            addMsg('assistant', reply);
            history.push({ role: 'assistant', content: reply });
            if (history.length > 20) history = history.slice(-20);
            // Show unread badge if sidebar is closed
            if (!open) {
                var badge = document.getElementById('ai-unread-badge');
                badge.style.display = 'flex';
            }
        })
        .catch(function () {
            hideTyping();
            busy = false;
            document.getElementById('ai-send-btn').disabled = false;
            addMsg('assistant', 'Sorry, something went wrong. Please check that Ollama is running and try again.');
        });
    };

    window.aiSuggest = function (btn) {
        document.getElementById('ai-input').value = btn.textContent.trim();
        aiSend();
    };

    window.clearAIChat = function () {
        history = [];
        document.getElementById('ai-messages').innerHTML = '';
        document.getElementById('ai-suggestions').style.display = 'flex';
        showWelcome();
    };

    // ── Add message ───────────────────────────────────────────────────────────
    function addMsg(role, text) {
        var box = document.getElementById('ai-messages');
        var now = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        var avatarHtml = role === 'user'
            ? '<div class="ai-msg-avatar">' + initial + '</div>'
            : '<div class="ai-msg-avatar"><i class="bi bi-robot" style="font-size:14px;"></i></div>';

        var div = document.createElement('div');
        div.className = 'ai-msg ' + role;
        div.innerHTML = avatarHtml
            + '<div class="ai-msg-body">'
            + '<div class="ai-msg-bubble">' + esc(text).replace(/\n/g, '<br>') + '</div>'
            + '<div class="ai-msg-time">' + now + '</div>'
            + '</div>';

        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
    }

    // ── Typing indicator ──────────────────────────────────────────────────────
    function showTyping() {
        var box = document.getElementById('ai-messages');
        var div = document.createElement('div');
        div.className = 'ai-msg assistant';
        div.id = 'ai-typing';
        div.innerHTML = '<div class="ai-msg-avatar"><i class="bi bi-robot" style="font-size:14px;"></i></div>'
            + '<div class="ai-msg-bubble" style="padding:0;background:#fff;box-shadow:0 1px 5px rgba(0,0,0,.08);">'
            + '<div class="ai-typing"><span></span><span></span><span></span></div>'
            + '</div>';
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
    }

    function hideTyping() {
        var el = document.getElementById('ai-typing');
        if (el) el.remove();
    }

    // ── Input helpers ─────────────────────────────────────────────────────────
    window.aiKeydown = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); aiSend(); }
    };

    window.aiResize = function (el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    };

    function esc(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Silent status check on load
    checkStatus();
})();
</script>
