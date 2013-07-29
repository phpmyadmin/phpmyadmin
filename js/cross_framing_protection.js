/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Conditionally included if third-party framing is not allowed
 *
 */

try {
    if (top != self) {
        top.location.href = self.location.href;
    }
} catch(e) {
    alert("Redirecting... (error: " + e);
    top.location.href = self.location.href;
}
