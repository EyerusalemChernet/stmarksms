{{-- ═══════════════════════════════════════════════════════════════════════
     St. Mark AI Chat Widget
     Floating button → slide-up chat panel
     Works for all roles: admin, teacher, parent, hr_manager
═══════════════════════════════════════════════════════════════════════ --}}

<style>
/* ── Floating button ─────────────────────────────────────────────────────── */
#ai-chat-btn {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #fff;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(79,70,229,.45);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    z-index: 9999;
    transition: transform .2s, box-shadow .2s;
}
#ai-chat-btn:hover { transform: scale(1.08); box-shadow: 0 6px 28px rgba(79,70,229,.55); }
#ai-chat-btn .ai-badge {
    position: absolute;
    top: -3px; right: -3px;
    width: 18px; height: 18px;
    background: #ef4444;
    border-radius: 50%;
    font-size: 10px;
    font-weight: 700;
    display: none;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
}

/* ── Chat panel ──────────────────────────────────────────────────────────── */
#ai-chat-panel {
    position: fixed;
    bottom: 96px;
    right: 28px;
    width: 380px;
    max-width: calc(100vw - 40px);
    height: 520px;
    max-height: calc(100vh - 120px);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0,0,0,.18);
    display: flex;
    flex-direction: column;
    z-index: 9998;
    overflow: hidden;
    transform: translateY(20px) scale(.97);
    opacity: 0;
    pointer-events: none;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1), opacity .2s;
}
#ai-chat-panel.open {
    transform: translateY(0) scale(1);
    opacity: 1;
    pointer-events: all;
}

/* ── Header ──────────────────────────────────────────────────────────────── */
.ai-header {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #fff;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.ai-header-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.ai-header-info { flex: 1; min-width: 0; }
.ai-header-name { font-size: 14px; font-weight: 700; }
.ai-header-status { font-size: 11px; opacity: .8; display: flex; align-items: center; gap: 4px; }
.ai-status-dot { width: 7px; height: 7px; border-radius: 50%; background: #4ade80; display: inline-block; }
.ai-status-dot.offline { background: #f87171; }
.ai-header-actions { display: flex; gap: 6px; }
.ai-header-btn {
    background: rgba(255,255,255,.15);
    border: none; color: #fff;
    width: 28px; height: 28px;
    border-radius: 6px;
    cursor: pointer; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.ai-header-btn:hover { background: rgba(255,255,255,.25); }

/* ── Messages area ───────────────────────────────────────────────────────── */
.ai-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #f8f7ff;
}
.ai-messages::-webkit-scrollbar { width: 4px; }
.ai-messages::-webkit-scrollbar-thumb { background: #c4b5fd; border-radius: 2px; }

/* ── Message bubbles ─────────────────────────────────────────────────────── */
.ai-msg { display: flex; gap: 8px; max-width: 100%; }
.ai-msg.user { flex-direction: row-reverse; }
.ai-msg-avatar {
    width: 28px; height: 28px; border-radius: 50%;
    background: #e0e7ff; color: #4f46e5;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.ai-msg.user .ai-msg-avatar { background: #4f46e5; color: #fff; }
.ai-msg-bubble {
    max-width: 78%;
    padding: 9px 13px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
}
.ai-msg.assistant .ai-msg-bubble {
    background: #fff;
    color: #111827;
    border-radius: 4px 14px 14px 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
}
.ai-msg.user .ai-msg-bubble {
    background: #4f46e5;
    color: #fff;
    border-radius: 14px 4px 14px 14px;
}
.ai-msg-time { font-size: 10px; opacity: .5; margin-top: 3px; }

/* ── Typing indicator ────────────────────────────────────────────────────── */
.ai-typing { display: flex; gap: 4px; align-items: center; padding: 10px 13px; }
.ai-typing span {
    width: 7px; height: 7px; border-radius: 50%;
    background: #a5b4fc; display: inline-block;
    animation: aiTyping 1.2s infinite;
}
.ai-typing span:nth-child(2) { animation-delay: .2s; }
.ai-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes aiTyping { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

/* ── Quick suggestions ───────────────────────────────────────────────────── */
.ai-suggestions {
    padding: 8px 16px;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    background: #f8f7ff;
    border-top: 1px solid #ede9fe;
    flex-shrink: 0;
}
.ai-suggestion-btn {
    background: #ede9fe;
    color: #4f46e5;
    border: none;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    cursor: pointer;
    transition: background .15s;
    white-space: nowrap;
}
.ai-suggestion-btn:hover { background: #ddd6fe; }

/* ── Input area ──────────────────────────────────────────────────────────── */
.ai-input-area {
    padding: 12px 14px;
    border-top: 1px solid #ede9fe;
    display: flex;
    gap: 8px;
    align-items: flex-end;
    background: #fff;
    flex-shrink: 0;
}
#ai-input {
    flex: 1;
    border: 1px solid #e0e7ff;
    border-radius: 10px;
    padding: 9px 12px;
    font-size: 13px;
    resize: none;
    outline: none;
    max-height: 100px;
    min-height: 38px;
    line-height: 1.4;
    font-family: inherit;
    transition: border-color .15s;
}
#ai-input:focus { border-color: #4f46e5; }
#ai-send-btn {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: #4f46e5;
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
    transition: background .15s;
}
#ai-send-btn:hover { background: #4338ca; }
#ai-send-btn:disabled { background: #a5b4fc; cursor: not-allowed; }

/* ── Clear chat button ───────────────────────────────────────────────────── */
.ai-clear-btn {
    background: none; border: none;
    color: rgba(255,255,255,.7);
    cursor: pointer; font-size: 12px;
    padding: 2px 6px; border-radius: 4px;
    transition: color .15s;
}
.ai-clear-btn:hover { color: #fff; }

@media (max-width: 480px) {
    #ai-chat-panel { right: 12px; bottom: 80px; width: calc(100vw - 24px); }
    #ai-chat-btn { right: 16px; bottom: 16px; }
}
</style>

{{-- Floating button --}}
<button id="ai-chat-btn" title="AI Assistant" onclick="toggleAIChat()">
    <i class="bi bi-robot"></i>
    <span class="ai-badge" id="ai-unread-badge"></span>
</button>

{{-- Chat panel --}}
<div id="ai-chat-panel">

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
            <button class="ai-header-btn" title="Clear chat" onclick="clearAIChat()"><i class="bi bi-trash3"></i></button>
            <button class="ai-header-btn" title="Close" onclick="toggleAIChat()"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>

    {{-- Messages --}}
    <div class="ai-messages" id="ai-messages"></div>

    {{-- Quick suggestions --}}
    <div class="ai-suggestions" id="ai-suggestions">
        @if(App\Helpers\Qs::userIsTeamSA())
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">How many students?</button>
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">Upcoming events</button>
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">Current academic year</button>
        @elseif(App\Helpers\Qs::userIsTeacher())
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">My subjects</button>
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">Upcoming exams</button>
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">School calendar</button>
        @elseif(App\Helpers\Qs::userIsParent())
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">My children's classes</button>
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">Upcoming events</button>
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">School schedule</button>
        @else
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">Upcoming events</button>
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">School calendar</button>
        @endif
        <button class="ai-suggestion-btn" onclick="aiSuggest(this)">Help</button>
    </div>

    {{-- Input --}}
    <div class="ai-input-area">
        <textarea id="ai-input" placeholder="Ask me anything about the school..." rows="1"
                  onkeydown="aiInputKeydown(event)" oninput="aiInputResize(this)"></textarea>
        <button id="ai-send-btn" onclick="aiSend()" title="Send">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>

</div>

<script>
(function() {
    var chatOpen    = false;
    var aiHistory   = [];   // [{role, content}]
    var isTyping    = false;
    var chatUrl     = '{{ route("ai.chat") }}';
    var statusUrl   = '{{ route("ai.chat.status") }}';
    var csrfToken   = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var userName    = '{{ Auth::user()->name ?? "User" }}';
    var userInitial = userName.charAt(0).toUpperCase();

    // ── Open / close ──────────────────────────────────────────────────────────
    window.toggleAIChat = function() {
        chatOpen = !chatOpen;
        var panel = document.getElementById('ai-chat-panel');
        panel.classList.toggle('open', chatOpen);
        if (chatOpen) {
            document.getElementById('ai-unread-badge').style.display = 'none';
            if (aiHistory.length === 0) showWelcome();
            checkStatus();
            setTimeout(function() { document.getElementById('ai-input').focus(); }, 300);
        }
    };

    // ── Welcome message ───────────────────────────────────────────────────────
    function showWelcome() {
        addMessage('assistant', 'Hello, ' + userName.split(' ')[0] + '! 👋 I\'m your St. Mark School AI Assistant. I can answer questions about students, events, the academic calendar, and more. How can I help you today?');
    }

    // ── Status check ──────────────────────────────────────────────────────────
    function checkStatus() {
        fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var dot  = document.getElementById('ai-status-dot');
            var text = document.getElementById('ai-status-text');
            if (d.ok) {
                dot.classList.remove('offline');
                text.textContent = 'Online · ' + (d.model || 'AI');
            } else {
                dot.classList.add('offline');
                text.textContent = 'Offline — run: ollama serve';
            }
        })
        .catch(function() {
            document.getElementById('ai-status-dot').classList.add('offline');
            document.getElementById('ai-status-text').textContent = 'Offline';
        });
    }

    // ── Send message ──────────────────────────────────────────────────────────
    window.aiSend = function() {
        var input = document.getElementById('ai-input');
        var msg   = input.value.trim();
        if (!msg || isTyping) return;

        input.value = '';
        aiInputResize(input);
        document.getElementById('ai-suggestions').style.display = 'none';

        addMessage('user', msg);
        aiHistory.push({ role: 'user', content: msg });

        showTyping();
        isTyping = true;
        document.getElementById('ai-send-btn').disabled = true;

        fetch(chatUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ message: msg, history: aiHistory.slice(0, -1) }),
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            hideTyping();
            isTyping = false;
            document.getElementById('ai-send-btn').disabled = false;
            var reply = d.reply || 'Sorry, I could not generate a response.';
            addMessage('assistant', reply);
            aiHistory.push({ role: 'assistant', content: reply });
            // Keep history manageable
            if (aiHistory.length > 20) aiHistory = aiHistory.slice(-20);
        })
        .catch(function() {
            hideTyping();
            isTyping = false;
            document.getElementById('ai-send-btn').disabled = false;
            addMessage('assistant', 'Sorry, something went wrong. Please try again.');
        });
    };

    // ── Quick suggestion ──────────────────────────────────────────────────────
    window.aiSuggest = function(btn) {
        document.getElementById('ai-input').value = btn.textContent.trim();
        aiSend();
    };

    // ── Clear chat ────────────────────────────────────────────────────────────
    window.clearAIChat = function() {
        aiHistory = [];
        document.getElementById('ai-messages').innerHTML = '';
        document.getElementById('ai-suggestions').style.display = 'flex';
        showWelcome();
    };

    // ── Add message bubble ────────────────────────────────────────────────────
    function addMessage(role, text) {
        var container = document.getElementById('ai-messages');
        var now = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        var avatarHtml = role === 'user'
            ? '<div class="ai-msg-avatar">' + userInitial + '</div>'
            : '<div class="ai-msg-avatar"><i class="bi bi-robot" style="font-size:13px;"></i></div>';

        var div = document.createElement('div');
        div.className = 'ai-msg ' + role;
        div.innerHTML = avatarHtml
            + '<div>'
            + '<div class="ai-msg-bubble">' + escapeHtml(text).replace(/\n/g, '<br>') + '</div>'
            + '<div class="ai-msg-time">' + now + '</div>'
            + '</div>';

        container.appendChild(div);
        container.scrollTop = container.scrollHeight;

        // Show unread badge if panel is closed
        if (!chatOpen && role === 'assistant') {
            var badge = document.getElementById('ai-unread-badge');
            badge.style.display = 'flex';
        }
    }

    // ── Typing indicator ──────────────────────────────────────────────────────
    function showTyping() {
        var container = document.getElementById('ai-messages');
        var div = document.createElement('div');
        div.className = 'ai-msg assistant';
        div.id = 'ai-typing-indicator';
        div.innerHTML = '<div class="ai-msg-avatar"><i class="bi bi-robot" style="font-size:13px;"></i></div>'
            + '<div class="ai-msg-bubble" style="padding:0;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.07);">'
            + '<div class="ai-typing"><span></span><span></span><span></span></div>'
            + '</div>';
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function hideTyping() {
        var el = document.getElementById('ai-typing-indicator');
        if (el) el.remove();
    }

    // ── Input helpers ─────────────────────────────────────────────────────────
    window.aiInputKeydown = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            aiSend();
        }
    };

    window.aiInputResize = function(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
    };

    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Check status on page load (silently)
    checkStatus();
})();
</script>
