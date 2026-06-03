<template>
  <div>
    <!-- ── FAB ─────────────────────────────────────── -->
    <button
      class="ai-fab"
      :class="{ open: isOpen }"
      @click="toggleChat"
      aria-label="AI Assistant"
    >
      <transition name="ai-icon-swap" mode="out-in">
        <i v-if="!isOpen" key="o" class="ri-sparkling-2-fill"></i>
        <i v-else          key="c" class="ri-close-line"></i>
      </transition>
      <span v-if="!isOpen && unreadCount > 0" class="ai-badge">{{ unreadCount }}</span>
    </button>

    <!-- ── Panel ─────────────────────────────────────── -->
    <transition name="ai-slide">
      <div v-if="isOpen" class="ai-panel">

        <!-- Header -->
        <header class="ai-header">
          <div class="ai-header-left">
            <div class="ai-hdr-orb">
              <i class="ri-sparkling-2-fill"></i>
            </div>
            <div>
              <p class="ai-hdr-name">AI Assistant</p>
              <p class="ai-hdr-sub">
                <span class="ai-online-dot" :class="{ active: settings.has_key }"></span>
                {{ settings.has_key ? (settings.provider_label + ' · ' + settings.model) : 'Local mode · no API key' }}
              </p>
            </div>
          </div>
          <div class="ai-hdr-actions">
            <button v-if="view === 'chat'" class="ai-hdr-btn" title="Clear chat" @click="clearChat">
              <i class="ri-delete-bin-line"></i>
            </button>
            <button
              class="ai-hdr-btn"
              :class="{ active: view === 'settings' }"
              title="Settings"
              @click="openSettings"
            >
              <i class="ri-settings-3-line"></i>
            </button>
            <button
              class="ai-hdr-btn"
              :class="{ active: view === 'info' }"
              :title="view === 'info' ? 'Back to chat' : 'Capabilities'"
              @click="view = view === 'chat' ? 'info' : 'chat'"
            >
              <i :class="view === 'info' ? 'ri-arrow-left-line' : 'ri-information-line'"></i>
            </button>
            <button class="ai-hdr-btn" title="Close" @click="isOpen = false">
              <i class="ri-close-line"></i>
            </button>
          </div>
        </header>

        <!-- ═══ CHAT ═══════════════════════════════════ -->
        <template v-if="view === 'chat'">
          <div class="ai-body" ref="bodyEl">
            <div v-if="!messages.length" class="ai-welcome">
              <div class="ai-welcome-orb">
                <i class="ri-sparkling-2-fill"></i>
              </div>
              <h6 class="ai-welcome-title">How can I help?</h6>
              <p class="ai-welcome-desc">
                {{ settings.has_key
                  ? 'Powered by ' + settings.provider_label + '. I understand natural language and query your live database.'
                  : 'Connected to your live database. Configure an AI key in Settings for smarter responses.' }}
              </p>
              <div class="ai-suggestions">
                <button v-for="s in suggestions" :key="s" class="ai-suggestion" @click="submit(s)">
                  <i class="ri-corner-down-right-line"></i>{{ s }}
                </button>
              </div>
            </div>

            <template v-else>
              <div v-for="(msg, i) in messages" :key="i" class="ai-msg" :class="msg.role">
                <div v-if="msg.role === 'assistant'" class="ai-msg-avatar">
                  <i class="ri-sparkling-2-fill"></i>
                </div>
                <div class="ai-bubble" :class="msg.role">
                  <div v-if="msg.role === 'assistant'" class="ai-md" v-html="md(msg.content)"></div>
                  <p v-else class="ai-user-text">{{ msg.content }}</p>
                  <time class="ai-timestamp">{{ msg.time }}</time>
                </div>
              </div>
              <div v-if="loading" class="ai-msg assistant">
                <div class="ai-msg-avatar"><i class="ri-sparkling-2-fill"></i></div>
                <div class="ai-bubble assistant">
                  <div class="ai-typing"><span></span><span></span><span></span></div>
                </div>
              </div>
            </template>
          </div>

          <div v-if="messages.length && !loading" class="ai-chips-bar">
            <button v-for="q in quickChips" :key="q" class="ai-chip-btn" @click="submit(q)">{{ q }}</button>
          </div>

          <footer class="ai-input-footer">
            <div class="ai-input-box" :class="{ focused }">
              <textarea
                ref="inputEl"
                v-model="text"
                class="ai-textarea"
                placeholder="Ask anything about your data…"
                rows="1"
                :disabled="loading"
                @focus="focused = true"
                @blur="focused = false"
                @keydown.enter.exact.prevent="send"
                @input="autoResize"
              ></textarea>
              <button class="ai-send-btn" :disabled="loading || !text.trim()" @click="send">
                <i v-if="loading" class="ri-loader-4-line ai-spin"></i>
                <i v-else class="ri-send-plane-fill"></i>
              </button>
            </div>
            <p class="ai-footer-label">
              {{ settings.has_key ? settings.provider_label + ' · ' + settings.model : 'Local engine · add API key for smarter AI' }}
            </p>
          </footer>
        </template>

        <!-- ═══ INFO ════════════════════════════════════ -->
        <template v-else-if="view === 'info'">
          <div class="ai-info-body">
            <div class="ai-info-card">
              <div class="ai-info-orb"><i class="ri-sparkling-2-fill"></i></div>
              <div>
                <strong>{{ settings.has_key ? settings.provider_label + ' AI' : 'Local AI Engine' }}</strong>
                <p>{{ settings.has_key
                  ? 'Connected to ' + settings.provider_label + ' using ' + settings.model + '. Understands natural language and queries your live database.'
                  : 'Built-in rule-based engine. No API key needed. Go to Settings to connect an AI provider for smarter responses.' }}</p>
              </div>
            </div>
            <p class="ai-info-section-title">What I can do</p>
            <div class="ai-cap-grid">
              <div v-for="cap in capabilities" :key="cap.label" class="ai-cap">
                <span class="ai-cap-emoji">{{ cap.icon }}</span>
                <div><strong>{{ cap.label }}</strong><span>{{ cap.desc }}</span></div>
              </div>
            </div>
            <p class="ai-info-section-title">Try these</p>
            <div class="ai-sample-list">
              <button v-for="s in sampleQuestions" :key="s" class="ai-sample" @click="runSample(s)">
                <i class="ri-chat-1-line"></i>{{ s }}
              </button>
            </div>
          </div>
        </template>

        <!-- ═══ SETTINGS ════════════════════════════════ -->
        <template v-else-if="view === 'settings'">
          <div class="ai-info-body">

            <!-- Back button -->
            <button class="ai-back-btn" @click="view = 'chat'">
              <i class="ri-arrow-left-line"></i> Back to chat
            </button>

            <!-- Provider card -->
            <div class="ai-set-section">
              <p class="ai-info-section-title">AI Provider</p>
              <div class="ai-set-grid">
                <div
                  v-for="(p, key) in providers"
                  :key="key"
                  class="ai-provider-card"
                  :class="{ selected: form.provider === key }"
                  @click="selectProvider(key)"
                >
                  <strong>{{ p.name }}</strong>
                  <span>{{ Object.values(p.models)[0] }}</span>
                </div>
              </div>
            </div>

            <!-- Model -->
            <div class="ai-set-section" v-if="currentProviderModels.length">
              <p class="ai-info-section-title">Model</p>
              <select class="ai-select" v-model="form.model">
                <option v-for="(label, key) in providers[form.provider]?.models" :key="key" :value="key">
                  {{ label }}
                </option>
              </select>
            </div>

            <!-- API Key -->
            <div class="ai-set-section">
              <p class="ai-info-section-title">
                API Key
                <a v-if="providers[form.provider]?.key_url" :href="providers[form.provider].key_url" target="_blank" class="ai-get-key-link">
                  Get free key →
                </a>
              </p>
              <div class="ai-key-row">
                <input
                  :type="showKey ? 'text' : 'password'"
                  class="ai-input"
                  v-model="form.api_key"
                  :placeholder="settings.has_key ? '••••••••  (leave blank to keep current)' : 'Paste your API key here'"
                />
                <button class="ai-eye-btn" @click="showKey = !showKey">
                  <i :class="showKey ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                </button>
              </div>
            </div>

            <!-- Test + Save -->
            <div class="ai-set-row">
              <button class="ai-btn-outline" :disabled="settingsBusy" @click="testConnection">
                <i v-if="testing" class="ri-loader-4-line ai-spin"></i>
                <i v-else class="ri-wifi-line"></i>
                Test
              </button>
              <button class="ai-btn-primary" :disabled="settingsBusy" @click="saveSettings">
                <i v-if="saving" class="ri-loader-4-line ai-spin"></i>
                <i v-else class="ri-save-line"></i>
                Save
              </button>
            </div>

            <div v-if="testResult" class="ai-test-result" :class="testResult.ok ? 'ok' : 'err'">
              <i :class="testResult.ok ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill'"></i>
              {{ testResult.message }}
            </div>

            <!-- Modules -->
            <div class="ai-set-section">
              <p class="ai-info-section-title">Data Modules
                <span class="ai-info-hint">Toggle which tables the AI can query</span>
              </p>
              <div v-if="modulesLoading" class="ai-modules-loading">
                <i class="ri-loader-4-line ai-spin"></i> Loading…
              </div>
              <div v-else class="ai-module-list">
                <div v-for="m in modules" :key="m.module_key" class="ai-module-row">
                  <div class="ai-module-info">
                    <span class="ai-module-icon">{{ m.icon }}</span>
                    <div>
                      <strong>{{ m.label }}</strong>
                      <span>{{ m.description }}</span>
                    </div>
                  </div>
                  <label class="ai-toggle">
                    <input type="checkbox" :checked="m.is_enabled" @change="toggleModule(m, $event)" />
                    <span class="ai-toggle-slider"></span>
                  </label>
                </div>
              </div>
            </div>

          </div>
        </template>

      </div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AIChatbot',

  data() {
    return {
      isOpen:      false,
      view:        'chat',
      text:        '',
      loading:     false,
      focused:     false,
      unreadCount: 0,
      messages:    [],

      // Settings state
      settings: {
        provider: 'groq',
        provider_label: 'Groq',
        model: '',
        has_key: false,
        is_active: true,
      },
      providers:      {},
      form:           { provider: 'groq', model: '', api_key: '' },
      showKey:        false,
      saving:         false,
      testing:        false,
      testResult:     null,
      modules:        [],
      modulesLoading: false,

      suggestions: [
        'Inventory summary',
        'Show pending PPMP',
        'Low stock items',
        'Procurement overview',
      ],
      quickChips: ['Low stock', 'PPMP', 'RIS records', 'Suppliers'],
      capabilities: [
        { icon: '📦', label: 'Inventory',    desc: 'Items, stock levels, categories, total value' },
        { icon: '🟡', label: 'Low Stock',    desc: 'Alerts for items running low or out of stock' },
        { icon: '📑', label: 'PPMP',         desc: 'Procurement plans filtered by status' },
        { icon: '🛒', label: 'Procurement',  desc: 'Purchase records and status breakdown' },
        { icon: '📋', label: 'RIS',          desc: 'Requisition and Issue Slips' },
        { icon: '💰', label: 'Finance',      desc: 'Finance requests and amounts' },
        { icon: '🏭', label: 'Suppliers',    desc: 'Accredited supplier/vendor list' },
      ],
      sampleQuestions: [
        'Inventory summary',
        'Show pending PPMP',
        'Low stock items',
        'Total inventory value',
        'Recent receivings',
        'Approved purchase orders',
        'Finance requests',
      ],
    };
  },

  computed: {
    settingsBusy() { return this.saving || this.testing; },
    currentProviderModels() {
      return Object.keys(this.providers[this.form.provider]?.models ?? {});
    },
  },

  methods: {
    toggleChat() {
      this.isOpen = !this.isOpen;
      if (this.isOpen) {
        this.unreadCount = 0;
        this.loadSettings();
        this.$nextTick(() => {
          this.scrollBottom();
          if (this.view === 'chat') this.$refs.inputEl?.focus();
        });
      }
    },

    async loadSettings() {
      try {
        const { data } = await axios.get('/ai-settings');
        this.providers = data.providers ?? {};
        const providerKey = data.provider ?? 'groq';
        this.settings = {
          provider:       providerKey,
          provider_label: this.providers[providerKey]?.name ?? providerKey,
          model:          data.model ?? '',
          has_key:        data.has_key ?? false,
          is_active:      data.is_active ?? true,
        };
        this.form.provider = providerKey;
        this.form.model    = data.model ?? '';
        this.form.api_key  = '';
      } catch { /* silent */ }
    },

    async openSettings() {
      this.view       = 'settings';
      this.testResult = null;
      this.modulesLoading = true;
      try {
        const { data } = await axios.get('/ai-modules');
        this.modules = data.modules ?? [];
      } catch { this.modules = []; }
      finally { this.modulesLoading = false; }
    },

    selectProvider(key) {
      this.form.provider = key;
      const models = this.providers[key]?.models ?? {};
      this.form.model = Object.keys(models)[0] ?? '';
    },

    async saveSettings() {
      this.saving     = true;
      this.testResult = null;
      try {
        await axios.post('/ai-settings', {
          provider:   this.form.provider,
          model:      this.form.model,
          api_key:    this.form.api_key || undefined,
          max_tokens: 1500,
        });
        await this.loadSettings();
        this.form.api_key = '';
        this.testResult = { ok: true, message: 'Settings saved.' };
      } catch (e) {
        this.testResult = { ok: false, message: e.response?.data?.message ?? 'Failed to save.' };
      } finally {
        this.saving = false;
      }
    },

    async testConnection() {
      this.testing    = true;
      this.testResult = null;
      try {
        const { data } = await axios.post('/ai-settings/test');
        this.testResult = { ok: data.ok, message: data.message };
      } catch (e) {
        this.testResult = { ok: false, message: e.response?.data?.message ?? 'Connection failed.' };
      } finally {
        this.testing = false;
      }
    },

    async toggleModule(module, event) {
      const enabled = event.target.checked;
      try {
        await axios.patch(`/ai-modules/${module.module_key}`, { is_enabled: enabled });
        module.is_enabled = enabled;
      } catch {
        event.target.checked = !enabled; // revert
      }
    },

    clearChat()  { this.messages = []; },
    runSample(q) { this.view = 'chat'; this.$nextTick(() => this.submit(q)); },
    submit(q)    { this.text = q; this.send(); },

    async send() {
      const msg = this.text.trim();
      if (!msg || this.loading) return;

      this.text = '';
      this.$nextTick(() => {
        const el = this.$refs.inputEl;
        if (el) el.style.height = 'auto';
      });

      this.messages.push({ role: 'user', content: msg, time: this.ts() });
      this.loading = true;
      this.$nextTick(this.scrollBottom);

      try {
        const { data } = await axios.post('/ai-chat', {
          messages: this.messages.map(m => ({ role: m.role, content: m.content })),
        });
        this.messages.push({ role: 'assistant', content: data.reply ?? '…', time: this.ts() });
        if (!this.isOpen) this.unreadCount++;
      } catch {
        this.messages.push({
          role: 'assistant',
          content: '⚠️ Could not reach the AI service. Please try again.',
          time: this.ts(),
        });
      } finally {
        this.loading = false;
        this.$nextTick(this.scrollBottom);
      }
    },

    scrollBottom() {
      const el = this.$refs.bodyEl;
      if (el) el.scrollTop = el.scrollHeight;
    },

    autoResize(e) {
      const el = e.target;
      el.style.height = 'auto';
      el.style.height = Math.min(el.scrollHeight, 110) + 'px';
    },

    ts() {
      return new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    },

    md(raw) {
      if (!raw) return '';
      const blocks = [];
      let s = raw.replace(/```[\s\S]*?```/g, m => {
        blocks.push(`<pre class="aicb-pre"><code>${this.esc(m.replace(/^```\w*\n?/, '').replace(/```$/, ''))}</code></pre>`);
        return `\x00B${blocks.length - 1}\x00`;
      });
      s = s.replace(/`([^`]+)`/g, (_, c) => `<code class="aicb-code">${this.esc(c)}</code>`);
      s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
      s = s.replace(/\*(.+?)\*/g,     '<em>$1</em>');
      const lines = s.split('\n');
      const out = [];
      let tRows = [];
      const flushTable = () => {
        if (!tRows.length) return;
        const html = tRows.map((r, ri) => {
          const cells = r.split('|').map(c => c.trim()).filter((_, i, a) => i > 0 && i < a.length - 1);
          const tag = ri === 0 ? 'th' : 'td';
          return `<tr>${cells.map(c => `<${tag}>${c}</${tag}>`).join('')}</tr>`;
        });
        out.push(`<table class="aicb-table">${html.join('')}</table>`);
        tRows = [];
      };
      lines.forEach(line => {
        if (/^\|.+\|$/.test(line.trim())) {
          if (/^[\| \-:]+$/.test(line.trim().replace(/[^|\-: ]/g, ''))) return;
          tRows.push(line.trim());
        } else { flushTable(); out.push(line); }
      });
      flushTable();
      s = out.join('\n');
      s = s.replace(/^### (.+)$/gm, '<h6 class="aicb-h">$1</h6>');
      s = s.replace(/^## (.+)$/gm,  '<h5 class="aicb-h">$1</h5>');
      s = s.replace(/^# (.+)$/gm,   '<h4 class="aicb-h">$1</h4>');
      s = s.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');
      s = s.replace(/(<li>.*?<\/li>\n?)+/gs, m => `<ol class="aicb-ol">${m}</ol>`);
      s = s.replace(/^[•\-\*] (.+)$/gm, '<li>$1</li>');
      s = s.replace(/(<li>.*?<\/li>\n?)+/gs, m => m.includes('<ol') ? m : `<ul class="aicb-ul">${m}</ul>`);
      s = s.split(/\n{2,}/).map(b => {
        b = b.trim();
        if (!b || /^<(h[1-6]|ul|ol|table|pre)/.test(b)) return b;
        return `<p class="aicb-p">${b.replace(/\n/g, '<br>')}</p>`;
      }).join('');
      s = s.replace(/\x00B(\d+)\x00/g, (_, i) => blocks[i]);
      return s;
    },

    esc(s) {
      return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    },
  },
};
</script>

<style scoped>
/* ══ FAB ═════════════════════════════════════════════ */
.ai-fab {
  position: fixed; bottom: 1.75rem; right: 1.75rem; z-index: 9900;
  width: 52px; height: 52px; border-radius: 50%; border: 0;
  background: linear-gradient(135deg, #4b5b93, #2d3f8a);
  color: #fff; font-size: 1.3rem;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 8px 28px rgba(45,63,138,.45); cursor: pointer;
  transition: transform .2s, box-shadow .2s, background .2s;
}
.ai-fab:hover { transform: scale(1.1); box-shadow: 0 12px 36px rgba(45,63,138,.55); }
.ai-fab.open  { background: linear-gradient(135deg, #1f2937, #374151); }
.ai-badge {
  position: absolute; top: -4px; right: -4px;
  min-width: 18px; height: 18px; padding: 0 4px; border-radius: 999px;
  background: #ef4444; color: #fff; font-size: .62rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center; border: 2px solid #fff;
}
.ai-icon-swap-enter-active, .ai-icon-swap-leave-active { transition: opacity .15s, transform .15s; }
.ai-icon-swap-enter-from { opacity: 0; transform: rotate(-90deg) scale(.7); }
.ai-icon-swap-leave-to   { opacity: 0; transform: rotate(90deg) scale(.7); }

/* ══ Panel ═══════════════════════════════════════════ */
.ai-panel {
  position: fixed; bottom: 5.5rem; right: 1.75rem; z-index: 9899;
  width: 420px; height: 620px; background: #fff; border-radius: 20px;
  box-shadow: 0 32px 72px rgba(15,23,42,.2), 0 0 0 1px rgba(15,23,42,.07);
  display: flex; flex-direction: column; overflow: hidden;
}
.ai-slide-enter-active, .ai-slide-leave-active { transition: opacity .2s, transform .2s; }
.ai-slide-enter-from, .ai-slide-leave-to { opacity: 0; transform: translateY(14px) scale(.97); }

/* ── Header ─────────────────────────────────────────── */
.ai-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: .85rem 1rem .85rem 1.1rem;
  background: linear-gradient(135deg, #1a2a68, #2d3f8a 55%, #38467a); flex-shrink: 0;
}
.ai-header-left { display: flex; align-items: center; gap: .65rem; }
.ai-hdr-orb {
  width: 36px; height: 36px; border-radius: 11px;
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; color: #fff; flex-shrink: 0;
}
.ai-hdr-name { font-size: .87rem; font-weight: 800; color: #fff; margin: 0 0 .1rem; line-height: 1; }
.ai-hdr-sub {
  display: flex; align-items: center; gap: .3rem;
  font-size: .67rem; color: rgba(255,255,255,.65); margin: 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;
}
.ai-online-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #94a3b8; flex-shrink: 0;
}
.ai-online-dot.active { background: #34d399; box-shadow: 0 0 6px #34d39977; }
.ai-hdr-actions { display: flex; gap: .25rem; }
.ai-hdr-btn {
  width: 30px; height: 30px; border-radius: 8px; border: 0;
  background: rgba(255,255,255,.1); color: rgba(255,255,255,.75);
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem; cursor: pointer; transition: background .15s;
}
.ai-hdr-btn:hover, .ai-hdr-btn.active { background: rgba(255,255,255,.22); color: #fff; }

/* ── Body ──────────────────────────────────────────── */
.ai-body {
  flex: 1 1 0; min-height: 0; overflow-y: auto;
  padding: 1rem; display: flex; flex-direction: column; gap: .85rem; scroll-behavior: smooth;
}
.ai-body::-webkit-scrollbar { width: 4px; }
.ai-body::-webkit-scrollbar-thumb { background: #dce4f2; border-radius: 4px; }

/* Welcome */
.ai-welcome {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; text-align: center; padding: .5rem .25rem;
}
.ai-welcome-orb {
  width: 56px; height: 56px; border-radius: 18px;
  background: linear-gradient(135deg, #1a2a68, #38467a);
  color: #fff; font-size: 1.5rem;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: .85rem; box-shadow: 0 8px 24px rgba(45,63,138,.3);
}
.ai-welcome-title { font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0 0 .3rem; }
.ai-welcome-desc  { font-size: .78rem; color: #64748b; margin: 0 0 1rem; max-width: 290px; line-height: 1.55; }
.ai-suggestions { display: flex; flex-direction: column; gap: .35rem; width: 100%; }
.ai-suggestion {
  display: flex; align-items: center; gap: .45rem;
  padding: .5rem .85rem; border-radius: 12px;
  border: 1px solid #dce4f2; background: #f8fbff;
  color: #38467a; font-size: .8rem; font-weight: 600;
  cursor: pointer; text-align: left; transition: all .15s;
}
.ai-suggestion i { color: #4b5b93; font-size: .8rem; flex-shrink: 0; }
.ai-suggestion:hover { background: #edf1fb; border-color: #4b5b93; }

/* Messages */
.ai-msg { display: flex; align-items: flex-end; gap: .45rem; }
.ai-msg.user { flex-direction: row-reverse; }
.ai-msg-avatar {
  width: 27px; height: 27px; border-radius: 9px;
  background: linear-gradient(135deg, #4b5b93, #2d3f8a);
  color: #fff; font-size: .72rem; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.ai-bubble { max-width: 82%; padding: .6rem .88rem .4rem; border-radius: 16px; font-size: .83rem; line-height: 1.5; }
.ai-bubble.assistant { background: #f1f5ff; border: 1px solid #dce4f2; color: #0f172a; border-bottom-left-radius: 4px; }
.ai-bubble.user { background: linear-gradient(135deg, #4b5b93, #2d3f8a); color: #fff; border-bottom-right-radius: 4px; }
.ai-user-text { margin: 0; word-break: break-word; }
.ai-timestamp { display: block; font-size: .6rem; margin-top: .25rem; text-align: right; color: rgba(0,0,0,.28); }
.ai-bubble.user .ai-timestamp { color: rgba(255,255,255,.4); }

.ai-typing { display: flex; align-items: center; gap: 4px; padding: .15rem .05rem; }
.ai-typing span { width: 7px; height: 7px; border-radius: 50%; background: #94a3b8; animation: ai-bounce 1.2s ease-in-out infinite; }
.ai-typing span:nth-child(2) { animation-delay: .15s; }
.ai-typing span:nth-child(3) { animation-delay: .3s; }
@keyframes ai-bounce { 0%,60%,100% { transform: translateY(0); opacity: .55; } 30% { transform: translateY(-7px); opacity: 1; } }

/* Chips + input */
.ai-chips-bar {
  display: flex; flex-wrap: wrap; gap: .3rem;
  padding: .4rem .85rem; border-top: 1px solid #f1f5ff; background: #fafbff; flex-shrink: 0;
}
.ai-chip-btn {
  padding: .2rem .6rem; border-radius: 999px; border: 1px solid #dce4f2; background: #fff;
  color: #4b5b93; font-size: .7rem; font-weight: 700; cursor: pointer; transition: all .15s; white-space: nowrap;
}
.ai-chip-btn:hover { background: #edf1fb; border-color: #4b5b93; }
.ai-input-footer { padding: .6rem .85rem .4rem; border-top: 1px solid #e8eef6; background: #fff; flex-shrink: 0; }
.ai-input-box {
  display: flex; align-items: flex-end; gap: .45rem;
  border: 1.5px solid #dce4f2; border-radius: 14px;
  padding: .4rem .4rem .4rem .72rem; background: #f8fbff; transition: border-color .15s, box-shadow .15s;
}
.ai-input-box.focused { border-color: #4b5b93; box-shadow: 0 0 0 3px rgba(75,91,147,.11); background: #fff; }
.ai-textarea {
  flex: 1; resize: none; border: 0; outline: none;
  background: transparent; font-size: .84rem; line-height: 1.45;
  font-family: inherit; color: #0f172a; max-height: 110px; overflow-y: auto;
}
.ai-textarea::placeholder { color: #94a3b8; }
.ai-textarea:disabled { opacity: .6; }
.ai-send-btn {
  width: 34px; height: 34px; border-radius: 10px; border: 0;
  background: linear-gradient(135deg, #4b5b93, #2d3f8a); color: #fff; font-size: .95rem;
  flex-shrink: 0; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: opacity .15s;
}
.ai-send-btn:disabled { opacity: .35; cursor: not-allowed; }
.ai-send-btn:not(:disabled):hover { opacity: .82; }
.ai-footer-label { font-size: .6rem; text-align: center; color: #cbd5e1; margin: .28rem 0 0; }

/* ══ Info view ═══════════════════════════════════════ */
.ai-info-body {
  flex: 1 1 0; min-height: 0; overflow-y: auto;
  padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: .85rem;
}
.ai-info-body::-webkit-scrollbar { width: 4px; }
.ai-info-body::-webkit-scrollbar-thumb { background: #dce4f2; border-radius: 4px; }
.ai-info-card {
  display: flex; align-items: flex-start; gap: .75rem; padding: .9rem 1rem;
  border-radius: 16px; background: linear-gradient(135deg, #edf1fb, #e8f0fe); border: 1px solid #c7d7f0;
}
.ai-info-orb {
  width: 42px; height: 42px; border-radius: 13px; flex-shrink: 0;
  background: linear-gradient(135deg, #4b5b93, #2d3f8a); color: #fff; font-size: 1.2rem;
  display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(45,63,138,.28);
}
.ai-info-card strong { display: block; font-size: .87rem; color: #1a2a68; margin-bottom: .2rem; }
.ai-info-card p { font-size: .74rem; color: #38467a; margin: 0; line-height: 1.45; }
.ai-info-section-title {
  font-size: .7rem; font-weight: 800; color: #38467a;
  text-transform: uppercase; letter-spacing: .08em; margin: 0;
  display: flex; align-items: center; gap: .5rem;
}
.ai-info-hint { font-size: .68rem; font-weight: 400; text-transform: none; letter-spacing: 0; color: #94a3b8; }
.ai-cap-grid { display: flex; flex-direction: column; gap: .35rem; }
.ai-cap {
  display: flex; align-items: flex-start; gap: .55rem; padding: .5rem .65rem;
  border-radius: 10px; background: #f8fbff; border: 1px solid #e8eef6;
}
.ai-cap-emoji { font-size: 1rem; flex-shrink: 0; margin-top: .05rem; }
.ai-cap strong { display: block; font-size: .78rem; color: #0f172a; line-height: 1.25; }
.ai-cap span   { font-size: .72rem; color: #64748b; line-height: 1.35; }
.ai-sample-list { display: flex; flex-direction: column; gap: .3rem; }
.ai-sample {
  display: flex; align-items: center; gap: .45rem; padding: .45rem .75rem;
  border-radius: 10px; border: 1px solid #dce4f2; background: #fff;
  color: #38467a; font-size: .78rem; font-weight: 600; cursor: pointer; text-align: left; transition: all .15s;
}
.ai-sample i { color: #4b5b93; flex-shrink: 0; font-size: .8rem; }
.ai-sample:hover { background: #edf1fb; border-color: #4b5b93; color: #1a2a68; }

/* ══ Settings view ═══════════════════════════════════ */
.ai-back-btn {
  display: inline-flex; align-items: center; gap: .35rem;
  font-size: .78rem; font-weight: 700; color: #38467a;
  background: none; border: none; cursor: pointer; padding: 0; margin-bottom: -.2rem;
}
.ai-back-btn:hover { color: #1a2a68; }
.ai-set-section { display: flex; flex-direction: column; gap: .45rem; }
.ai-set-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; }
.ai-provider-card {
  padding: .6rem .75rem; border-radius: 12px; border: 1.5px solid #dce4f2;
  background: #f8fbff; cursor: pointer; transition: all .15s;
}
.ai-provider-card:hover  { border-color: #4b5b93; background: #edf1fb; }
.ai-provider-card.selected { border-color: #2d3f8a; background: #edf1fb; box-shadow: 0 0 0 3px rgba(45,63,138,.1); }
.ai-provider-card strong { display: block; font-size: .8rem; color: #0f172a; margin-bottom: .15rem; }
.ai-provider-card span   { font-size: .68rem; color: #64748b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ai-get-key-link { font-size: .68rem; font-weight: 600; color: #4b5b93; text-decoration: none; }
.ai-get-key-link:hover { text-decoration: underline; }
.ai-key-row { display: flex; gap: .4rem; }
.ai-input, .ai-select {
  width: 100%; padding: .48rem .7rem; border-radius: 10px;
  border: 1.5px solid #dce4f2; background: #f8fbff; font-size: .82rem;
  color: #0f172a; font-family: inherit; outline: none; transition: border-color .15s;
}
.ai-input:focus, .ai-select:focus { border-color: #4b5b93; box-shadow: 0 0 0 3px rgba(75,91,147,.1); }
.ai-eye-btn {
  width: 36px; flex-shrink: 0; border-radius: 10px; border: 1.5px solid #dce4f2;
  background: #f8fbff; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.ai-eye-btn:hover { background: #edf1fb; color: #38467a; }
.ai-set-row { display: flex; gap: .5rem; }
.ai-btn-outline, .ai-btn-primary {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: .35rem;
  padding: .5rem; border-radius: 10px; font-size: .8rem; font-weight: 700; cursor: pointer; border: 0; transition: all .15s;
}
.ai-btn-outline  { background: #f1f5ff; border: 1.5px solid #dce4f2; color: #38467a; }
.ai-btn-outline:hover { background: #edf1fb; }
.ai-btn-primary  { background: linear-gradient(135deg, #4b5b93, #2d3f8a); color: #fff; }
.ai-btn-primary:hover  { opacity: .88; }
.ai-btn-outline:disabled, .ai-btn-primary:disabled { opacity: .45; cursor: not-allowed; }
.ai-test-result {
  display: flex; align-items: center; gap: .4rem;
  padding: .5rem .75rem; border-radius: 10px; font-size: .78rem; font-weight: 600;
}
.ai-test-result.ok  { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.ai-test-result.err { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }

/* Modules */
.ai-modules-loading { font-size: .8rem; color: #94a3b8; display: flex; align-items: center; gap: .4rem; }
.ai-module-list { display: flex; flex-direction: column; gap: .3rem; }
.ai-module-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: .55rem .75rem; border-radius: 12px;
  background: #f8fbff; border: 1px solid #e8eef6;
}
.ai-module-info { display: flex; align-items: flex-start; gap: .5rem; flex: 1; min-width: 0; }
.ai-module-icon { font-size: 1rem; flex-shrink: 0; margin-top: .05rem; }
.ai-module-info strong { display: block; font-size: .78rem; color: #0f172a; line-height: 1.2; }
.ai-module-info span   { display: block; font-size: .69rem; color: #94a3b8; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }

/* Toggle switch */
.ai-toggle { position: relative; display: inline-block; width: 36px; height: 20px; flex-shrink: 0; }
.ai-toggle input { opacity: 0; width: 0; height: 0; }
.ai-toggle-slider {
  position: absolute; inset: 0; border-radius: 999px;
  background: #cbd5e1; cursor: pointer; transition: background .2s;
}
.ai-toggle-slider::before {
  content: ''; position: absolute;
  width: 14px; height: 14px; border-radius: 50%;
  left: 3px; bottom: 3px; background: #fff;
  transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.ai-toggle input:checked + .ai-toggle-slider { background: #2d3f8a; }
.ai-toggle input:checked + .ai-toggle-slider::before { transform: translateX(16px); }

/* ══ Markdown styles ═════════════════════════════════ */
.ai-md :deep(p.aicb-p) { margin: 0 0 .5rem; word-break: break-word; }
.ai-md :deep(p.aicb-p:last-child) { margin-bottom: 0; }
.ai-md :deep(.aicb-h) { margin: .5rem 0 .2rem; font-weight: 800; color: #0f172a; }
.ai-md :deep(.aicb-ul), .ai-md :deep(.aicb-ol) { padding-left: 1.2rem; margin: .25rem 0 .4rem; }
.ai-md :deep(li) { margin-bottom: .1rem; }
.ai-md :deep(strong) { font-weight: 700; }
.ai-md :deep(.aicb-table) { width: 100%; border-collapse: collapse; font-size: .75rem; margin: .5rem 0; }
.ai-md :deep(.aicb-table th),
.ai-md :deep(.aicb-table td) { border: 1px solid #dce4f2; padding: .28rem .5rem; text-align: left; }
.ai-md :deep(.aicb-table th) { background: #edf1fb; font-weight: 700; color: #38467a; }
.ai-md :deep(.aicb-pre) {
  background: #1e293b; color: #e2e8f0; border-radius: 8px;
  padding: .6rem .8rem; margin: .4rem 0; overflow-x: auto;
}
.ai-md :deep(.aicb-pre code) { font-size: .73rem; font-family: ui-monospace, monospace; white-space: pre; background: none; color: inherit; padding: 0; }
.ai-md :deep(.aicb-code) {
  background: rgba(75,91,147,.1); color: #38467a;
  padding: .1rem .35rem; border-radius: 5px;
  font-family: ui-monospace, monospace; font-size: .82em;
}

/* ══ Responsive ══════════════════════════════════════ */
@media (max-width: 480px) {
  .ai-panel { width: calc(100vw - 2rem); right: 1rem; bottom: 5rem; height: calc(100dvh - 8rem); }
  .ai-fab { bottom: 1.25rem; right: 1.25rem; }
}
</style>
