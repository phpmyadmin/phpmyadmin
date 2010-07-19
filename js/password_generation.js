/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for libraries/display_change_password.lib.php 
 *
 */

$(document).ready(function() {
    $('#tr_element_before_generate_password').parent().append('<tr><td>' + PMA_messages['strGeneratePassword'] + '</td><td><input type="button" id="button_generate_password" value="' + PMA_messages['strGenerate'] + '" onclick="suggestPassword(this.form)" /><input type="text" name="generated_pw" id="generated_pw" /></td></tr>');
    $('#div_element_before_generate_password').parent().append('<div class="item"><label for="button_generate_password">' + PMA_messages['strGeneratePassword'] + ':</label><span class="options"><input type="button" id="button_generate_password" value="' + PMA_messages['strGenerate'] + '" onclick="suggestPassword(this.form)" /></span><input type="text" name="generated_pw" id="generated_pw" /></div>');
});
