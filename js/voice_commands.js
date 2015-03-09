"use strict";

// first we make sure annyang started succesfully
function voice_init() {
    if (annyang) {
            var sessionInstance = window.sessionStorage;
            var params = 'ajax_request=true&ajax_page_request=true';
            params += AJAX.cache.menus.getRequestParam();
            console.log("checkbox clicked");
            // define the functions our commands will run.
            var hello = function() {
                console.log("hello there");
            };

            var database = function() {
                redirect_url = 'server_databases.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            };

            var sql = function() {
                redirect_url = 'server_sql.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            var status = function() {
                redirect_url = 'server_status.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            var users = function() {
                redirect_url = 'server_privileges.php?db=&token='+ sessionInstance.token;
                params += '&viewing_mode=server';
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            var tab_export = function() {
                redirect_url = 'server_export.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            var tab_import = function() {
                redirect_url = 'server_import.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            var settings = function() {
                redirect_url = 'prefs_manage.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            var replication = function() {
                redirect_url = 'server_replication.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            var variables = function() {
                redirect_url = 'server_variables.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            var charsets = function() {
                redirect_url = 'server_collations.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            var engines = function() {
                redirect_url = 'server_engines.php?db=&token='+ sessionInstance.token;
                $.get(redirect_url, params, AJAX.responseHandler);
            }

            // define our commands.
            // * The key is what you want your users to say say.
            // * The value is the action to do.
            //   You can pass a function, a function name (as a string), or write your function as part of the commands object.
            var commands = {
                'hello (there)': hello,
                'show databases': database,
                'show sql' : sql,
                'show status' : status,
                'show users' : users,
                'show export' : tab_export,
                'show import' : tab_import,
                'show settings' : settings,
                'show replication' : replication,
                'show variables' : variables,
                'show charsets' : charsets,
                'show engines' :  engines
            };

            // OPTIONAL: activate debug mode for detailed logging in the console
            annyang.debug();

            // Add voice commands to respond to
            annyang.addCommands(commands);

            // OPTIONAL: Set a language for speech recognition (defaults to English)
            annyang.setLanguage('en');

            // Start listening. You can call this here, or attach this call to an event, button, etc.
            annyang.start();
    } else {

    }
}