import { AJAX } from './ajax.js';
PMA_commonParams.setAll({
    common_query: "",
    opendb_url: "db_structure.php",
    collation_connection: "utf8mb4_unicode_ci",
    lang: "pt_BR",
    server: "1",
    table: "",
    db: "",
    token: "BUnCWVz|X**O/(@L",
    text_dir: "ltr",
    show_databases_navigation_as_tree: "1",
    pma_text_default_tab: "Visualizar",
    pma_text_left_default_tab: "Estrutura",
    pma_text_left_default_tab2: "",
    LimitChars: "50",
    pftext: "",
    confirm: "1",
    LoginCookieValidity: "86400",
    session_gc_maxlifetime: "1440",
    logged_in: "",
    is_https: "",
    rootPath: "/",
    PMA_VERSION: "4.8.0-dev",
    auth_type: "cookie",
    user: "root"
});
ConsoleEnterExecutes = false;
AJAX.scriptHandler.add("vendor/jquery/jquery.min.js", 0).add("vendor/jquery/jquery-migrate-3.0.0.js", 0).add("whitelist.js", 1).add("vendor/sprintf.js", 1).add("ajax.js", 0).add("keyhandler.js", 1).add("vendor/jquery/jquery-ui.min.js", 0).add("vendor/js.cookie.js", 1).add("vendor/jquery/jquery.mousewheel.js", 0).add("vendor/jquery/jquery.event.drag-2.2.js", 0).add("vendor/jquery/jquery-ui-timepicker-addon.js", 0).add("vendor/jquery/jquery.ba-hashchange-1.3.js", 0).add("vendor/jquery/jquery.debounce-1.0.5.js", 0).add("menu-resizer.js", 1).add("cross_framing_protection.js", 0).add("rte.js", 1).add("messages.js", 1).add("get_image.js", 1).add("config.js", 1).add("doclinks.js", 1).add("functions.js", 1).add("navigation.js", 1).add("indexes.js", 1).add("common.js", 1).add("page_settings.js", 1).add("shortcuts_handler.js", 1).add("vendor/codemirror/lib/codemirror.js", 0).add("vendor/codemirror/mode/sql/sql.js", 0).add("vendor/codemirror/addon/runmode/runmode.js", 0).add("vendor/codemirror/addon/hint/show-hint.js", 0).add("vendor/codemirror/addon/hint/sql-hint.js", 0).add("vendor/codemirror/addon/lint/lint.js", 0).add("codemirror/addon/lint/sql-lint.js", 0).add("console.js", 1);
$(function() {
    AJAX.fireOnload("whitelist.js");
    AJAX.fireOnload("vendor/sprintf.js");
    AJAX.fireOnload("keyhandler.js");
    AJAX.fireOnload("vendor/js.cookie.js");
    AJAX.fireOnload("menu-resizer.js");
    AJAX.fireOnload("rte.js");
    AJAX.fireOnload("messages.js");
    AJAX.fireOnload("get_image.js");
    AJAX.fireOnload("config.js");
    AJAX.fireOnload("doclinks.js");
    AJAX.fireOnload("functions.js");
    AJAX.fireOnload("navigation.js");
    AJAX.fireOnload("indexes.js");
    AJAX.fireOnload("common.js");
    AJAX.fireOnload("page_settings.js");
    AJAX.fireOnload("shortcuts_handler.js");
    AJAX.fireOnload("console.js");
});
