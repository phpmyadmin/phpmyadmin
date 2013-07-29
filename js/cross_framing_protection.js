/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Conditionally included if framing is not allowed
 */
if(self == top) {
    document.documentElement.style.display = 'block' ;
} else {
    top.location = self.location ;
}
