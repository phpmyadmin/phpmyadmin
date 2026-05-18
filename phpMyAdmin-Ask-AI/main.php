<?php

/**
 * AI Query - injection hook.
 *
 * phpMyAdmin includes this file at the bottom of every rendered page (the
 * customFooterFile extension point in libraries/vendor_config.php). We use it
 * to inject an "Ask AI" button next to the existing "Get auto-saved query"
 * button on the SQL editor, plus a modal that talks to ai_query.php.
 *
 * Zero core file edits required.
 */

if (! defined('PHPMYADMIN')) {
  return;
}

// Render the modal + injection script on every page. We can't restrict to SQL
// routes only, because phpMyAdmin uses AJAX navigation: a user might land on a
// non-SQL page first, then click SQL in the nav (which swaps #page_content via
// AJAX without re-running this footer). The injection script gates itself on
// the presence of #saved, so on non-SQL pages it adds zero visible UI.
?>
<style>
  #aiQueryModal .modal-dialog {
    max-width: 760px;
  }

  #aiQueryModal textarea {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  }

  #aiQueryModal .aiq-section {
    margin-bottom: 1rem;
  }

  #aiQueryModal .aiq-status {
    font-style: italic;
    color: #6c757d;
    min-height: 1.5em;
  }

  #aiQueryModal .aiq-error {
    color: #b02a37;
    white-space: pre-wrap;
  }

  #aiQueryModal .aiq-settings {
    background: #f8f9fa;
    padding: .75rem;
    border-radius: .375rem;
  }

  #aiQueryModal .aiq-hidden {
    display: none !important;
  }

  #aiQueryModal .aiq-tabs .nav-link {
    font-size: .85em;
    padding: .3rem .5rem;
    cursor: pointer;
  }

  #aiQueryModal .aiq-tabs .nav-link.active-saved::after {
    content: "";
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #198754;
    margin-left: .35rem;
    vertical-align: middle;
  }

  #aiQueryModal .aiq-key-help {
    font-size: .8em;
  }

  #aiQueryModal .aiq-key-help a {
    text-decoration: underline;
  }

  #aiQueryModal .aiq-turns {
    max-height: 240px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: .375rem;
    padding: .5rem;
    background: #fff;
  }

  #aiQueryModal .aiq-turn {
    padding: .35rem .5rem;
    border-radius: .25rem;
    margin-bottom: .35rem;
    font-size: .9em;
  }

  #aiQueryModal .aiq-turn:last-child {
    margin-bottom: 0;
  }

  #aiQueryModal .aiq-turn-user {
    background: #e7f1ff;
  }

  #aiQueryModal .aiq-turn-ai {
    background: #fff3cd;
  }

  #aiQueryModal .aiq-turn-role {
    font-weight: 600;
    font-size: .75em;
    text-transform: uppercase;
    opacity: .7;
    margin-right: .35rem;
  }

  #askAi {
    margin-left: .5rem;
  }
</style>

<div class="modal fade" id="aiQueryModal" tabindex="-1" aria-labelledby="aiQueryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="aiQueryModalLabel">Ask AI</h5>
        <button type="button" class="btn-link aiq-gear" id="aiqGear" title="Settings" style="border:0;background:none;margin-left:auto;margin-right:.5rem;">&#9881;</button>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="aiq-section aiq-settings aiq-hidden" id="aiqSettings">
          <ul class="nav nav-tabs nav-fill mb-2 aiq-tabs" id="aiqProviderTabs" role="tablist"></ul>

          <div class="aiq-tab-help small text-muted mb-2" id="aiqTabHelp"></div>

          <div class="mb-2 aiq-row aiq-row-providerType aiq-hidden">
            <label class="form-label">Provider type</label>
            <select class="form-select form-select-sm" id="aiqProviderType">
              <option value="openai_compatible">OpenAI compatible (OpenAI API shape)</option>
              <option value="anthropic">Anthropic native (/v1/messages)</option>
            </select>
          </div>

          <div class="mb-2 aiq-row aiq-row-baseUrl">
            <label class="form-label">Base URL</label>
            <input type="text" class="form-control form-control-sm" id="aiqBaseUrl">
          </div>

          <div class="mb-2 aiq-row aiq-row-model">
            <label class="form-label">Model</label>
            <div class="input-group input-group-sm">
              <select class="form-select" id="aiqModelSelect"></select>
              <button type="button" class="btn btn-outline-secondary aiq-hidden" id="aiqDetect">Detect installed</button>
            </div>
            <input type="text" class="form-control form-control-sm mt-1 aiq-hidden" id="aiqModelCustom"
              placeholder="Type a model name (e.g. mistral-large-latest)">
          </div>

          <div class="mb-2 aiq-row aiq-row-apiKey">
            <label class="form-label">API key</label>
            <input type="password" class="form-control form-control-sm" id="aiqApiKey"
              placeholder="paste your API key" autocomplete="off">
            <div class="form-text aiq-key-help"></div>
          </div>

          <button type="button" class="btn btn-sm btn-primary" id="aiqSaveCfg">Save settings</button>
          <span class="aiq-status ms-2" id="aiqCfgStatus"></span>
        </div>

        <div class="aiq-section aiq-hidden" id="aiqConversation">
          <label class="form-label">Conversation</label>
          <div class="aiq-turns" id="aiqTurns"></div>
        </div>

        <div class="aiq-section">
          <label class="form-label" for="aiqPrompt" id="aiqPromptLabel">Describe what SQL you need</label>
          <textarea class="form-control" id="aiqPrompt" rows="4"
            placeholder="e.g. List the 10 customers with the highest total order amount in the last 30 days"></textarea>
        </div>

        <div class="aiq-section">
          <button type="button" class="btn btn-primary" id="aiqGenerate">Generate SQL</button>
          <button type="button" class="btn btn-link btn-sm aiq-hidden" id="aiqReset">Start over</button>
          <span class="aiq-status ms-2" id="aiqStatus"></span>
        </div>

        <div class="aiq-section aiq-hidden" id="aiqResultWrap">
          <label class="form-label" for="aiqResult">Generated SQL (edit before inserting if needed)</label>
          <textarea class="form-control" id="aiqResult" rows="8"></textarea>
        </div>

        <div class="aiq-section aiq-error" id="aiqError"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="aiqInsert" disabled>Insert into editor</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    'use strict';

    function onReady(fn) {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn);
      } else {
        fn();
      }
    }

    onReady(function() {
      // Modal HTML lives in #pma_footer (outside #page_content) and survives
      // phpMyAdmin's AJAX navigation. We set it up ONCE on initial load.
      var modalEl = document.getElementById('aiQueryModal');
      if (!modalEl || typeof bootstrap === 'undefined') return;
      var modal = new bootstrap.Modal(modalEl);

      var $ = function(id) {
        return document.getElementById(id);
      };

      // Read phpMyAdmin's CSRF token + active DB from the existing SQL form.
      function pmaToken() {
        var t = document.querySelector('input[name="token"]');
        return t ? t.value : '';
      }

      function activeDb() {
        // The SQL form renders a hidden <input name="db">. Fall back to URL param.
        var inp = document.querySelector('#sqlqueryform input[name="db"]') ||
          document.querySelector('input[name="db"]');
        if (inp && inp.value) return inp.value;
        var m = location.search.match(/[?&]db=([^&]+)/);
        return m ? decodeURIComponent(m[1]) : '';
      }

      function activeTable() {
        var inp = document.querySelector('#sqlqueryform input[name="table"]') ||
          document.querySelector('input[name="table"]');
        if (inp && inp.value) return inp.value;
        var m = location.search.match(/[?&]table=([^&]+)/);
        return m ? decodeURIComponent(m[1]) : '';
      }

      function setStatus(msg) {
        $('aiqStatus').textContent = msg || '';
      }

      function setError(msg) {
        $('aiqError').textContent = msg || '';
      }

      function setCfgStatus(msg) {
        $('aiqCfgStatus').textContent = msg || '';
      }

      function showSettings() {
        $('aiqSettings').classList.remove('aiq-hidden');
      }

      function hideSettings() {
        $('aiqSettings').classList.add('aiq-hidden');
      }

      function settingsShown() {
        return !$('aiqSettings').classList.contains('aiq-hidden');
      }

      function callEndpoint(action, fields) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('token', pmaToken());
        Object.keys(fields || {}).forEach(function(k) {
          fd.append(k, fields[k]);
        });
        // Endpoint lives in the plugin folder. If you rename the folder, change this path.
        return fetch('phpMyAdmin-Ask-AI/ai_query.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
          })
          .then(function(r) {
            var ct = r.headers.get('content-type') || '';
            if (ct.indexOf('application/json') === -1) {
              return r.text().then(function(t) {
                console.error('[AI Query] Non-JSON response. Status=' + r.status + ', Content-Type=' + ct + ', Body (first 800 chars):', t.substring(0, 800));
                throw new Error('Endpoint returned ' + ct + ' instead of JSON (HTTP ' + r.status + '). See console for body.');
              });
            }
            return r.json().then(function(data) {
              function strip(s) {
                return (s || '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
              }
              if (data && data.error) {
                throw new Error(strip(data.error) || 'Server error');
              }
              if (data && data.success === false) {
                throw new Error(strip(data.message) || 'Server returned an error.');
              }
              if (!r.ok) {
                throw new Error('HTTP ' + r.status);
              }
              return data;
            });
          });
      }

      // ----- Provider catalog ---------------------------------------------------
      // Curated SQL-capable models per provider. Defaults are picked for a good
      // quality-vs-cost (or quality-vs-RAM) starting point.
      var PROVIDERS = {
        ollama: {
          label: 'Ollama (local)',
          providerType: 'openai_compatible',
          baseUrl: 'http://localhost:11434/v1',
          baseUrlEditable: true,
          needsKey: false,
          detect: true,
          help: 'Run models locally with Ollama. No API key needed. Install models with e.g. <code>ollama pull qwen2.5-coder:7b</code>.',
          models: [{
              id: 'qwen2.5-coder:7b',
              label: 'Qwen 2.5 Coder 7B (recommended for SQL, ~4 GB RAM)'
            },
            {
              id: 'qwen2.5-coder:14b',
              label: 'Qwen 2.5 Coder 14B (~9 GB RAM)'
            },
            {
              id: 'sqlcoder',
              label: 'SQLCoder 7B (SQL-specialised)'
            },
            {
              id: 'sqlcoder:15b',
              label: 'SQLCoder 15B'
            },
            {
              id: 'deepseek-coder-v2',
              label: 'DeepSeek Coder V2 16B'
            },
            {
              id: 'codellama',
              label: 'Code Llama 7B'
            },
            {
              id: 'llama3.1',
              label: 'Llama 3.1 8B (general)'
            },
            {
              id: 'llama3.3',
              label: 'Llama 3.3 70B (large, general)'
            }
          ],
          defaultModel: 'qwen2.5-coder:7b'
        },
        openai: {
          label: 'OpenAI',
          providerType: 'openai_compatible',
          baseUrl: 'https://api.openai.com/v1',
          baseUrlEditable: false,
          needsKey: true,
          keyHelp: 'Get an API key at <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">platform.openai.com/api-keys</a>.',
          models: [{
              id: 'gpt-4o-mini',
              label: 'GPT-4o mini (recommended - cheap & fast)'
            },
            {
              id: 'gpt-4o',
              label: 'GPT-4o (higher quality)'
            },
            {
              id: 'gpt-4.1-mini',
              label: 'GPT-4.1 mini'
            },
            {
              id: 'gpt-4.1',
              label: 'GPT-4.1 (best quality)'
            },
            {
              id: 'o3-mini',
              label: 'o3-mini (reasoning model)'
            }
          ],
          defaultModel: 'gpt-4o-mini'
        },
        anthropic: {
          label: 'Anthropic',
          providerType: 'anthropic',
          baseUrl: 'https://api.anthropic.com',
          baseUrlEditable: false,
          needsKey: true,
          keyHelp: 'Get an API key at <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener">console.anthropic.com</a>.',
          models: [{
              id: 'claude-sonnet-4-6',
              label: 'Claude Sonnet 4.6 (recommended)'
            },
            {
              id: 'claude-haiku-4-5-20251001',
              label: 'Claude Haiku 4.5 (fastest)'
            },
            {
              id: 'claude-opus-4-7',
              label: 'Claude Opus 4.7 (highest quality)'
            }
          ],
          defaultModel: 'claude-sonnet-4-6'
        },
        openrouter: {
          label: 'OpenRouter',
          providerType: 'openai_compatible',
          baseUrl: 'https://openrouter.ai/api/v1',
          baseUrlEditable: false,
          needsKey: true,
          keyHelp: 'Get an API key at <a href="https://openrouter.ai/keys" target="_blank" rel="noopener">openrouter.ai/keys</a>. Many models, one bill.',
          models: [{
              id: 'anthropic/claude-3.5-sonnet',
              label: 'Claude 3.5 Sonnet (recommended)'
            },
            {
              id: 'openai/gpt-4o-mini',
              label: 'GPT-4o mini'
            },
            {
              id: 'openai/gpt-4o',
              label: 'GPT-4o'
            },
            {
              id: 'qwen/qwen-2.5-coder-32b-instruct',
              label: 'Qwen 2.5 Coder 32B'
            },
            {
              id: 'deepseek/deepseek-chat',
              label: 'DeepSeek Chat'
            },
            {
              id: 'meta-llama/llama-3.3-70b-instruct',
              label: 'Llama 3.3 70B'
            },
            {
              id: 'google/gemini-2.0-flash-exp:free',
              label: 'Gemini 2.0 Flash (free tier)'
            }
          ],
          defaultModel: 'anthropic/claude-3.5-sonnet'
        },
        groq: {
          label: 'Groq',
          providerType: 'openai_compatible',
          baseUrl: 'https://api.groq.com/openai/v1',
          baseUrlEditable: false,
          needsKey: true,
          keyHelp: 'Get an API key at <a href="https://console.groq.com/keys" target="_blank" rel="noopener">console.groq.com/keys</a>. Very fast inference.',
          models: [{
              id: 'llama-3.3-70b-versatile',
              label: 'Llama 3.3 70B Versatile (recommended)'
            },
            {
              id: 'llama-3.1-8b-instant',
              label: 'Llama 3.1 8B Instant (fastest)'
            },
            {
              id: 'qwen-2.5-coder-32b',
              label: 'Qwen 2.5 Coder 32B'
            },
            {
              id: 'mixtral-8x7b-32768',
              label: 'Mixtral 8x7B'
            }
          ],
          defaultModel: 'llama-3.3-70b-versatile'
        },
        deepseek: {
          label: 'DeepSeek',
          providerType: 'openai_compatible',
          baseUrl: 'https://api.deepseek.com/v1',
          baseUrlEditable: false,
          needsKey: true,
          keyHelp: 'Get an API key at <a href="https://platform.deepseek.com/api_keys" target="_blank" rel="noopener">platform.deepseek.com</a>.',
          models: [{
              id: 'deepseek-chat',
              label: 'DeepSeek Chat (recommended)'
            },
            {
              id: 'deepseek-coder',
              label: 'DeepSeek Coder'
            }
          ],
          defaultModel: 'deepseek-chat'
        },
        custom: {
          label: 'Custom',
          custom: true,
          help: 'Use any OpenAI-compatible or Anthropic-shaped endpoint - LM Studio, vLLM, Azure OpenAI, self-hosted, etc.',
          models: [],
          defaultModel: ''
        }
      };
      var TAB_ORDER = ['ollama', 'openai', 'anthropic', 'openrouter', 'groq', 'deepseek', 'custom'];

      // Server state plus the tab the user is currently editing in the UI.
      var serverConfig = {
        active: null,
        profiles: {}
      };
      var currentTab = null;

      // ----- Tab rendering ------------------------------------------------------
      var tabsEl = $('aiqProviderTabs');
      TAB_ORDER.forEach(function(tab) {
        var li = document.createElement('li');
        li.className = 'nav-item';
        var a = document.createElement('a');
        a.className = 'nav-link';
        a.dataset.tab = tab;
        a.textContent = PROVIDERS[tab].label;
        li.appendChild(a);
        tabsEl.appendChild(li);
        a.addEventListener('click', function(ev) {
          ev.preventDefault();
          // Stop the click from bubbling to phpMyAdmin's document-level handler
          // ($(document).on('click', 'a', AJAX.requestHandler)) which would
          // otherwise show "You have unsaved changes..." whenever there's any
          // dirty lock-page input on the host page. We don't want our modal's
          // own tab switches to trigger that warning.
          ev.stopPropagation();
          selectTab(tab);
        });
      });

      function markActiveTab() {
        Array.prototype.forEach.call(tabsEl.querySelectorAll('.nav-link'), function(el) {
          el.classList.toggle('active', el.dataset.tab === currentTab);
          el.classList.toggle('active-saved', el.dataset.tab === serverConfig.active);
        });
      }

      function rebuildModelSelect(tab, preselect) {
        var sel = $('aiqModelSelect');
        sel.innerHTML = '';
        var def = PROVIDERS[tab];
        (def.models || []).forEach(function(m) {
          var opt = document.createElement('option');
          opt.value = m.id;
          opt.textContent = m.label;
          sel.appendChild(opt);
        });
        var optCustom = document.createElement('option');
        optCustom.value = '__custom__';
        optCustom.textContent = 'Custom...';
        sel.appendChild(optCustom);

        var found = (def.models || []).some(function(m) {
          return m.id === preselect;
        });
        if (preselect && !found) {
          sel.value = '__custom__';
          $('aiqModelCustom').value = preselect;
          $('aiqModelCustom').classList.remove('aiq-hidden');
        } else {
          sel.value = preselect || def.defaultModel || '__custom__';
          $('aiqModelCustom').value = '';
          $('aiqModelCustom').classList.add('aiq-hidden');
        }
      }

      function selectTab(tab) {
        currentTab = tab;
        var def = PROVIDERS[tab];
        var profile = serverConfig.profiles[tab] || {};

        // Provider type row only for the Custom tab.
        $('aiqSettings').querySelector('.aiq-row-providerType').classList.toggle('aiq-hidden', !def.custom);
        $('aiqProviderType').value = profile.provider || def.providerType || 'openai_compatible';

        // Base URL
        var baseInput = $('aiqBaseUrl');
        baseInput.value = profile.base_url || def.baseUrl || '';
        baseInput.readOnly = def.custom ? false : (def.baseUrlEditable === false);
        baseInput.placeholder = def.baseUrl || '';

        // Models
        rebuildModelSelect(tab, profile.model || def.defaultModel || '');

        // Detect button (Ollama)
        $('aiqDetect').classList.toggle('aiq-hidden', !def.detect);

        // API key row
        var keyRow = $('aiqSettings').querySelector('.aiq-row-apiKey');
        var needsKey = def.custom ? true : !!def.needsKey;
        keyRow.classList.toggle('aiq-hidden', !needsKey && !profile.has_key);
        var keyInput = $('aiqApiKey');
        keyInput.value = '';
        keyInput.placeholder = profile.has_key ? '(leave blank to keep saved key)' : 'paste your API key';
        var keyHelp = $('aiqSettings').querySelector('.aiq-key-help');
        keyHelp.innerHTML = def.keyHelp || '';

        // Help text
        $('aiqTabHelp').innerHTML = def.help || '';

        markActiveTab();
      }

      // Toggle custom-model text field when the dropdown switches.
      $('aiqModelSelect').addEventListener('change', function() {
        var isCustom = this.value === '__custom__';
        $('aiqModelCustom').classList.toggle('aiq-hidden', !isCustom);
        if (isCustom) $('aiqModelCustom').focus();
      });

      // ----- Config sync -------------------------------------------------------
      function applyConfig(cfg) {
        serverConfig = {
          active: cfg.active || null,
          profiles: cfg.profiles || {}
        };
        // Choose which tab to land on: the saved active one, else any with a model, else ollama.
        var preferred = serverConfig.active;
        if (!preferred) {
          for (var i = 0; i < TAB_ORDER.length; i++) {
            if (serverConfig.profiles[TAB_ORDER[i]] && serverConfig.profiles[TAB_ORDER[i]].model) {
              preferred = TAB_ORDER[i];
              break;
            }
          }
        }
        if (!preferred) preferred = 'ollama';
        selectTab(currentTab && cfg.profiles[currentTab] ? currentTab : preferred);

        if (!cfg.configured) {
          showSettings();
        } else {
          hideSettings();
        }
      }

      function refreshConfig() {
        return callEndpoint('get_config', {}).then(function(cfg) {
          applyConfig(cfg);
          return cfg;
        }).catch(function(e) {
          console.error('[AI Query] get_config failed:', e);
          showSettings();
          // Render the default tab UI so the user can still configure.
          if (!currentTab) selectTab('ollama');
          setError(e.message);
          throw e;
        });
      }

      refreshConfig().catch(function() {
        /* surfaced */
      });

      function openAskAiModal() {
        setStatus('');
        setError('');
        conversation = [];
        $('aiqTurns').innerHTML = '';
        $('aiqConversation').classList.add('aiq-hidden');
        $('aiqResultWrap').classList.add('aiq-hidden');
        $('aiqResult').value = '';
        $('aiqInsert').disabled = true;
        $('aiqGenerate').textContent = 'Generate SQL';
        $('aiqPromptLabel').textContent = 'Describe what SQL you need';
        $('aiqReset').classList.add('aiq-hidden');
        modal.show();
        refreshConfig().catch(function() {
          /* surfaced */
        });
      }

      // Inject the "Ask AI" button into the SQL toolbar. Idempotent: bails if
      // we're not on the SQL editor or the button is already present. Called
      // on initial load AND from a MutationObserver so it works after AJAX nav.
      function attachAskAiButton() {
        var savedBtn = document.getElementById('saved');
        if (!savedBtn) return;
        if (document.getElementById('askAi')) return;
        var b = document.createElement('input');
        b.type = 'button';
        b.value = 'Ask AI';
        b.id = 'askAi';
        b.className = 'btn btn-secondary button sqlbutton';
        b.addEventListener('click', openAskAiModal);
        savedBtn.insertAdjacentElement('afterend', b);
      }

      attachAskAiButton();

      // phpMyAdmin's AJAX nav does `$('#page_content').replaceWith(...)`, which
      // DETACHES the original node - any observer bound to it stops firing after
      // the first swap. So we observe its parent (document.body) for direct-child
      // changes; each page_content swap shows up as one childList mutation here.
      // No subtree, so unrelated DOM activity inside the page doesn't trigger us.
      if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(function() {
          attachAskAiButton();
        }).observe(document.body, {
          childList: true
        });
      }

      $('aiqGear').addEventListener('click', function() {
        if (settingsShown()) hideSettings();
        else showSettings();
      });

      function currentModel() {
        var sel = $('aiqModelSelect').value;
        if (sel === '__custom__') return $('aiqModelCustom').value.trim();
        return sel;
      }

      $('aiqDetect').addEventListener('click', function() {
        setCfgStatus('Asking Ollama...');
        setError('');
        callEndpoint('detect_ollama', {
            base_url: $('aiqBaseUrl').value
          })
          .then(function(data) {
            var sel = $('aiqModelSelect');
            var models = (data.models || []).slice().sort();

            if (models.length === 0) {
              setCfgStatus('');
              setError('Ollama is reachable but no models are installed. Run e.g. "ollama pull qwen2.5-coder:7b" in a terminal, then click Detect again.');
              return;
            }

            // Replace the dropdown with ONLY the installed models, so the user
            // sees exactly what's available. Keep "Custom..." at the bottom for
            // overrides. Pre-select the previously-saved model if still present,
            // else our recommended default if installed, else the first model.
            var prevValue = sel.value;
            var defaultModel = PROVIDERS.ollama.defaultModel;
            sel.innerHTML = '';
            models.forEach(function(name) {
              var opt = document.createElement('option');
              opt.value = name;
              opt.textContent = name + ' (installed)';
              sel.appendChild(opt);
            });
            var optCustom = document.createElement('option');
            optCustom.value = '__custom__';
            optCustom.textContent = 'Custom...';
            sel.appendChild(optCustom);

            if (models.indexOf(prevValue) !== -1) {
              sel.value = prevValue;
            } else if (models.indexOf(defaultModel) !== -1) {
              sel.value = defaultModel;
            } else {
              sel.value = models[0];
            }
            $('aiqModelCustom').classList.add('aiq-hidden');

            setCfgStatus('Found ' + models.length + ' installed: ' + models.join(', '));
            setTimeout(function() {
              setCfgStatus('');
            }, 6000);
          })
          .catch(function(e) {
            setCfgStatus('');
            setError(e.message);
          });
      });

      $('aiqSaveCfg').addEventListener('click', function() {
        if (!currentTab) return;
        var def = PROVIDERS[currentTab];
        var model = currentModel();
        if (!model) {
          setError('Pick a model (or select Custom and type one).');
          return;
        }
        var provider = def.custom ? $('aiqProviderType').value : def.providerType;
        setError('');
        setCfgStatus('Saving...');
        callEndpoint('save_config', {
          tab: currentTab,
          provider: provider,
          base_url: $('aiqBaseUrl').value,
          model: model,
          api_key: $('aiqApiKey').value
        }).then(function() {
          setCfgStatus('Saved.');
          return refreshConfig();
        }).then(function() {
          setTimeout(function() {
            setCfgStatus('');
          }, 2000);
        }).catch(function(e) {
          setCfgStatus('');
          setError(e.message);
        });
      });

      // ----- Conversation state ------------------------------------------------
      // conversation = [{role:'user'|'assistant', content:'...'}, ...]
      // Empty array means "next click starts a fresh request".
      var conversation = [];

      function renderTurns() {
        var box = $('aiqTurns');
        box.innerHTML = '';
        if (conversation.length === 0) {
          $('aiqConversation').classList.add('aiq-hidden');
          return;
        }
        $('aiqConversation').classList.remove('aiq-hidden');
        conversation.forEach(function(turn) {
          var div = document.createElement('div');
          div.className = 'aiq-turn aiq-turn-' + (turn.role === 'assistant' ? 'ai' : 'user');
          var role = document.createElement('span');
          role.className = 'aiq-turn-role';
          role.textContent = turn.role === 'assistant' ? 'AI' : 'You';
          div.appendChild(role);
          div.appendChild(document.createTextNode(turn.content));
          box.appendChild(div);
        });
        box.scrollTop = box.scrollHeight;
      }

      function inReplyMode() {
        return conversation.length > 0;
      }

      function refreshGenerateUi() {
        if (inReplyMode()) {
          $('aiqGenerate').textContent = 'Send reply';
          $('aiqPromptLabel').textContent = 'Your reply to the AI';
          $('aiqPrompt').placeholder = 'Answer the AI’s question...';
          $('aiqReset').classList.remove('aiq-hidden');
        } else {
          $('aiqGenerate').textContent = 'Generate SQL';
          $('aiqPromptLabel').textContent = 'Describe what SQL you need';
          $('aiqPrompt').placeholder = 'e.g. List the 10 customers with the highest total order amount in the last 30 days';
          $('aiqReset').classList.add('aiq-hidden');
        }
      }

      function resetConversation() {
        conversation = [];
        renderTurns();
        refreshGenerateUi();
        $('aiqResultWrap').classList.add('aiq-hidden');
        $('aiqResult').value = '';
        $('aiqInsert').disabled = true;
        $('aiqPrompt').value = '';
      }

      $('aiqReset').addEventListener('click', function() {
        resetConversation();
        setStatus('');
        setError('');
      });

      $('aiqGenerate').addEventListener('click', function() {
        var input = $('aiqPrompt').value.trim();
        if (!input) {
          setError(inReplyMode() ? 'Type your reply first.' : 'Enter a prompt first.');
          return;
        }
        var db = activeDb();
        if (!db) {
          setError('No database selected. Pick a database in phpMyAdmin first.');
          return;
        }
        var table = activeTable();

        // Append this user turn locally so the conversation history shows it.
        conversation.push({
          role: 'user',
          content: input
        });
        renderTurns();
        $('aiqPrompt').value = '';
        setError('');
        setStatus('Reading schema and asking the model...');
        $('aiqGenerate').disabled = true;
        $('aiqInsert').disabled = true;

        var payload = {
          db: db,
          table: table,
          conversation: JSON.stringify(conversation)
        };

        callEndpoint('generate', payload)
          .then(function(data) {
            setStatus('');
            if (data && data.kind === 'question' && data.text) {
              conversation.push({
                role: 'assistant',
                content: 'QUESTION: ' + data.text
              });
              renderTurns();
              refreshGenerateUi();
              $('aiqPrompt').focus();
              return;
            }
            // SQL path - record the assistant's SQL turn (in case the user wants
            // to ask for a refinement after seeing it), then show in the editor.
            var sql = (data && data.sql) || '';
            conversation.push({
              role: 'assistant',
              content: sql
            });
            renderTurns();
            refreshGenerateUi();
            $('aiqResultWrap').classList.remove('aiq-hidden');
            $('aiqResult').value = sql;
            $('aiqInsert').disabled = !sql;
          })
          .catch(function(e) {
            setStatus('');
            setError(e.message);
            // Roll back the user turn we optimistically appended so they can retry.
            if (conversation.length && conversation[conversation.length - 1].role === 'user') {
              var rolled = conversation.pop();
              $('aiqPrompt').value = rolled.content;
              renderTurns();
              refreshGenerateUi();
            }
          })
          .finally(function() {
            $('aiqGenerate').disabled = false;
          });
      });

      $('aiqInsert').addEventListener('click', function() {
        var sql = $('aiqResult').value;
        if (!sql) return;
        var ta = document.getElementById('sqlquery');
        if (ta) {
          ta.value = sql;
          // phpMyAdmin wraps the textarea with CodeMirror when enabled; sync it.
          if (ta.CodeMirror) {
            ta.CodeMirror.setValue(sql);
          } else if (window.codeMirrorEditor && typeof window.codeMirrorEditor.setValue === 'function') {
            window.codeMirrorEditor.setValue(sql);
          }
          ta.dispatchEvent(new Event('input', {
            bubbles: true
          }));
        }
        modal.hide();
      });
    });
  })();
</script>
