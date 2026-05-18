# phpMyAdmin Ask AI

Adds an **Ask AI** button next to the existing **Get auto-saved query** button on the phpMyAdmin SQL tab. Click it, describe what you want in natural language, and the model returns a SQL query you can review and run.

No phpMyAdmin core files are modified. The plugin uses phpMyAdmin's official `customFooterFile` extension point.

## Features

- One-click button on the SQL tab. Works at server, database, and table levels.
- Survives phpMyAdmin's internal AJAX navigation (clicking SQL from another tab, switching tables, etc.).
- Schema-aware prompts: sends the full database schema plus full `SHOW CREATE TABLE` and 3 sample rows for the currently open table, with a hint that the open table is just where the user happened to be.
- The model can return either SQL or a clarification question. If it asks a question, you reply inline and the conversation continues until you get SQL.
- Multi-provider settings UI with curated SQL-strong model dropdowns per provider, plus a Custom tab for anything else.
  - Anthropic Claude (native API)
  - OpenAI
  - OpenRouter
  - Groq
  - DeepSeek
  - Ollama (local) with auto-detection of installed models
  - Custom (any OpenAI-compatible or Anthropic-shaped endpoint - LM Studio, vLLM, Azure OpenAI, etc.)
- Per-provider profile storage. Switching tabs doesn't wipe your saved API keys.
- Generated SQL drops straight into phpMyAdmin's SQL editor (CodeMirror-aware).

## Requirements

- phpMyAdmin 5.x (tested on 5.2.1)
- PHP 7.2.5+ with the `curl` extension enabled
- A web server (Apache, Nginx, etc.). The `.htaccess` uses Apache directives; on Nginx see [Security](#security) for the equivalent location block.

## Install

The plugin lives entirely inside its own subdirectory inside your phpMyAdmin install. Only one tiny stub file needs to sit at phpMyAdmin's root, because phpMyAdmin hardcodes the path `<phpmyadmin>/config.footer.inc.php` for its footer extension point.

### 1. Place the plugin folder

Clone or copy this repository so the folder `phpMyAdmin-Ask-AI/` lives inside your phpMyAdmin install root.

```
<phpmyadmin>/
    index.php
    config.inc.php
    libraries/
    ...
    phpMyAdmin-Ask-AI/        <-- this folder
        ai_query.php
        main.php
        install/
            config.footer.inc.php
        .htaccess
        README.md
```

For XAMPP on Windows that's typically `C:\xampp\phpMyAdmin\phpMyAdmin-Ask-AI\`. On Linux it's commonly `/usr/share/phpmyadmin/phpMyAdmin-Ask-AI/` or `/var/www/html/phpmyadmin/phpMyAdmin-Ask-AI/`.

### 2. Install the stub at phpMyAdmin's root

Copy `phpMyAdmin-Ask-AI/install/config.footer.inc.php` to your phpMyAdmin root (next to `index.php` and `config.inc.php`).

That's the entire install. Reload phpMyAdmin and the **Ask AI** button appears on any SQL editor view.

#### If you already have a `config.footer.inc.php` at the root

phpMyAdmin only loads one footer file. Don't overwrite an existing one - just append this line to it:

```php
include __DIR__ . '/phpMyAdmin-Ask-AI/main.php';
```

### Nginx instead of Apache

The bundled `.htaccess` denies web access to `ai_query.config.json` (which holds your API key). On Nginx, add an equivalent block to your phpMyAdmin server block:

```nginx
location ~ /phpMyAdmin-Ask-AI/ai_query\.config\.json$ {
    deny all;
    return 403;
}
```

## Usage

1. Open any database in phpMyAdmin and click the **SQL** tab.
2. Click **Ask AI**. On first run, the settings panel auto-opens.

<img width="1064" height="499" alt="image" src="https://github.com/user-attachments/assets/710316ed-b0d1-4553-9800-aa82e52b1151" />

3. Pick a provider tab, choose a model, paste an API key (Ollama needs no key), click **Save settings**.
4. Type what you want, e.g. _"List the 10 customers with the most orders in the last 30 days"_, and click **Generate SQL**.
5. Review the SQL in the editor area. Edit it if needed, then click **Insert into editor** - the SQL drops into phpMyAdmin's main query editor.

<img width="1083" height="801" alt="image" src="https://github.com/user-attachments/assets/a3b20e56-48ad-4513-8568-b5483d34eb6a" />

6. Use phpMyAdmin's existing **Go** button to run it.

7. If the model needs more information, it returns a `QUESTION:` instead of SQL. Type your reply in the same prompt area and click **Send reply**. The full conversation is shown above. Click **Start over** to reset.

<img width="1083" height="801" alt="image" src="https://github.com/user-attachments/assets/fd29d7b2-a1c9-48a3-af7f-9df92bf00ca9" />

## Configuration

API keys and provider settings are stored in `phpMyAdmin-Ask-AI/ai_query.config.json`. The file is created on first save, is gitignored, and is blocked from direct web access by the `.htaccess` in the same folder.

To switch providers, open the settings (gear icon in the modal header), pick another tab, and save. Each provider tab has its own saved profile, so flipping between providers doesn't lose your keys.

<img width="1260" height="737" alt="image" src="https://github.com/user-attachments/assets/fe79080c-9295-4b9a-8350-fa97f8abc196" />

### Ollama setup

The Ollama tab pre-fills `http://localhost:11434/v1` as the base URL. Click **Detect installed** to see exactly what models you've pulled - the dropdown is replaced with only what's available on your machine.

If no models are installed yet, pull one in a terminal first:

```
ollama pull qwen2.5-coder:7b
```

Then click **Detect installed** again. Recommended SQL-strong local models: `qwen2.5-coder:7b`, `sqlcoder`, `deepseek-coder-v2`.

## Security

- **API key storage**: keys are stored in plaintext in `phpMyAdmin-Ask-AI/ai_query.config.json`. The file is gitignored and `.htaccess`-blocked. On Linux the install applies `0600` permissions automatically; on Windows file ACLs are at the OS default.
- **CSRF**: the AJAX endpoint validates phpMyAdmin's session CSRF token on every request.
- **Auth**: the endpoint requires an active phpMyAdmin login - it bootstraps phpMyAdmin and inherits the existing session. Anonymous requests are rejected.
- **Schema disclosure**: the database schema and 3 sample rows from the currently open table are sent to your configured AI provider on every Generate. Be mindful for sensitive data; prefer a local provider (Ollama) for confidential schemas.
- **SQL execution**: the plugin never auto-executes queries. Generated SQL only runs when the user clicks phpMyAdmin's own **Go** button.

### MySQL privileges and what the AI sees

The plugin always sends a request, degrading gracefully based on what the logged-in MySQL user is allowed to see. `information_schema` is privilege-filtered by the server, so the AI only ever receives schema for objects the user already has access to.

| User's MySQL privileges                  | What the AI receives                                                                                                                                                            |
| ---------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Full DB access                           | Complete schema + DDL for the open table + 3 sample rows                                                                                                                        |
| Partial (some tables grant-visible)      | Privilege-filtered schema (only visible tables) + DDL/samples if the open table is readable, silently skipped otherwise                                                         |
| Open table not readable                  | Filtered schema for other tables; DDL and sample rows for the open table are dropped (best effort)                                                                              |
| Zero readable objects in the active DB   | Context block carries a sentinel: `(no tables visible to current MySQL user in 'X' - database may be empty, or user lacks privileges on its objects)`. Request is still sent.   |
| Active database is actually empty        | Same sentinel as above. Request is still sent; the model will typically reply with a `QUESTION:` asking for table details.                                                      |

## Updating

Pull the latest changes inside `phpMyAdmin-Ask-AI/`. Your `ai_query.config.json` is gitignored and left untouched. If the stub file at the phpMyAdmin root changes, copy the new one from `install/config.footer.inc.php`.

## Uninstalling

1. Delete `<phpmyadmin>/phpMyAdmin-Ask-AI/` entirely.
2. Delete `<phpmyadmin>/config.footer.inc.php` (or, if you'd merged it with your own, just remove the `include __DIR__ . '/phpMyAdmin-Ask-AI/main.php';` line).

No phpMyAdmin core files were touched, so there's nothing else to revert.

## Renaming the plugin folder

If you want a different folder name than `phpMyAdmin-Ask-AI`:

1. Rename the folder on disk.
2. Update the `include __DIR__ . '/phpMyAdmin-Ask-AI/main.php';` line in your root-level `config.footer.inc.php` to match the new folder name.
3. Update the `fetch('phpMyAdmin-Ask-AI/ai_query.php', ...)` call inside the plugin's `main.php` to match.

## How it works

- **`config.footer.inc.php` (at root)** is just a 3-line stub that `include`s the real file from this plugin's subfolder. phpMyAdmin reads this path from its hardcoded `customFooterFile` setting in `libraries/vendor_config.php`.
- **`phpMyAdmin-Ask-AI/main.php`** outputs a hidden Bootstrap modal plus an inline `<script>` that injects the **Ask AI** button into the SQL toolbar and wires up the modal handlers. A `MutationObserver` on `document.body` reattaches the button whenever phpMyAdmin AJAX-replaces `#page_content`.
- **`phpMyAdmin-Ask-AI/ai_query.php`** is the AJAX endpoint. It bootstraps phpMyAdmin via `Common::run()` so it inherits the session, CSRF token, and `$dbi` handle. Three actions: `get_config`, `save_config`, and `generate`. Schema collection uses `INFORMATION_SCHEMA` plus `SHOW CREATE TABLE` for the focused table. Provider calls go via cURL.
- **`phpMyAdmin-Ask-AI/.htaccess`** denies direct web access to `ai_query.config.json` in the same folder.

## Troubleshooting

**The button doesn't appear**

- Check that the stub `config.footer.inc.php` exists at the phpMyAdmin root.
- Check that the `phpMyAdmin-Ask-AI/` folder is a sibling of `index.php`.
- Open browser devtools console and look for `[AI Query]` messages.
- Confirm `$cfg['customFooterFile']` hasn't been overridden in your `config.inc.php`.

**The endpoint returns HTML instead of JSON**

- Earlier versions could hit this when phpMyAdmin's `ResponseRenderer` tried to render a full page; the plugin now forces AJAX mode early. If you still see it, check Apache/PHP error log for fatal errors in `phpMyAdmin-Ask-AI/ai_query.php`.

**"CSRF token mismatch" errors**

- Your phpMyAdmin session probably expired. Reload phpMyAdmin and try again.

**Ollama "cannot reach Ollama at ..."**

- Verify Ollama is running: `ollama list` in a terminal.
- Verify the URL: `curl http://localhost:11434/api/tags` should return JSON.

**Model returns prose alongside SQL**

- The plugin extracts the first ` ```sql ` fenced block from the response. If the model refuses to fence its SQL and only emits prose, you'll see prose in the editor. Try a stronger model.

## License

MIT. The plugin lives in `phpMyAdmin/`'s directory but is otherwise independent of phpMyAdmin's own license.
