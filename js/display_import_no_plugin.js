/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * display import when no plugins loaded
 */

var finished = false;
var percent  = 0.0;
var total    = 0;
var complete = 0;
var original_title = parent && parent.document ? parent.document.title : false;
var import_start;

var perform_upload = function () {
	new $.getJSON(
			ajax_url,
			{},
			function(response) {
				finished = response.finished;
				percent = response.percent;
				total = response.total;
				complete = response.complete;

				if (total==0 && complete==0 && percent==0) {
					$('#upload_form_status_info').html('<img src='+ pmaThemeImage + 'ajax_clock_small.gif" width="16" height="16" alt="ajax clock" /> ' + promotStr);
					$('#upload_form_status').css("display", "none");
				} else {
					var now = new Date();
					now = Date.UTC(
							now.getFullYear(), now.getMonth(), now.getDate(),
							now.getHours(), now.getMinutes(), now.getSeconds())
							+ now.getMilliseconds() - 1000;
					var statustext = $.sprintf(statustext_str,
							formatBytes(complete, 1, PMA_messages.strDecimalSeparator),
							formatBytes(total, 1, PMA_messages.strDecimalSeparator)
					);

					if ($('#importmain').is(':visible')) {
						// show progress UI
						$('#importmain').hide();
						$('#import_form_status')
						.html('<div class="upload_progress"><div class="upload_progress_bar_outer"><div class="percentage"></div><div id="status" class="upload_progress_bar_inner"><div class="percentage"></div></div></div><div><img src='+ pmaThemeImage + 'ajax_clock_small.gif" width="16" height="16" alt="ajax clock" /> ' + import_str + '</div><div id="statustext"></div></div>')
						.show();
						import_start = now;
					}
					else if (percent > 9 || complete > 2000000) {
						// calculate estimated time
						var used_time = now - import_start;
						var seconds = parseInt(((total - complete) / complete) * used_time / 1000);
						var speed = $.sprintf(second_str
								, formatBytes(complete / used_time * 1000, 1, PMA_messages.strDecimalSeparator));

						var minutes = parseInt(seconds / 60);
						seconds %= 60;
						var estimated_time;
						if (minutes > 0) {
							estimated_time = remaining_str1
							.replace('%MIN', minutes).replace('%SEC', seconds);
						}
						else {
							estimated_time = remaining_str2
							.replace('%SEC', seconds);
						}

						statustext += '<br />' + speed + '<br /><br />' + estimated_time;
					}

					var percent_str = Math.round(percent) + '%';
					$('#status').animate({width: percent_str}, 150);
					$('.percentage').text(percent_str);

					// show percent in window title
					if (original_title !== false) {
						parent.document.title = percent_str + ' - ' + original_title;
					}
					else {
						document.title = percent_str + ' - ' + original_title;
					}
					$('#statustext').html(statustext);
				} // else

				if (finished == true) {
					if (original_title !== false) {
						parent.document.title = original_title;
					}
					else {
						document.title = original_title;
					}
					$('#importmain').hide();
					$('#import_form_status')
					.html('<img src="' + pmaThemeImage + 'ajax_clock_small.gif" width="16" height="16" alt="ajax clock" />' + proceed_str)
					.show();
					$('#import_form_status').load('import_status.php?message=true&' + import_url); // loads the message, either success or mysql error
