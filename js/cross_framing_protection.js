/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Conditionally called from libraries/header_scripts.inc.php
 * if third-party framing is not allowed
 *
 */

try {
    // can't access this if on a different domain
    var topdomain = top.document.domain;
    // double-check just for sure
    if (topdomain != self.document.domain) {
        alert("Redirecting...");
        top.location.replace(self.document.URL.substring(0, self.document.URL.lastIndexOf("/")+1));
    }
}
catch(e) {
    alert("Redirecting... (error: " + e);
    top.location.replace(self.document.URL.substring(0, self.document.URL.lastIndexOf("/")+1));
}
