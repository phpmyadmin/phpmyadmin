/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *  Used for replication support
 *
 * @version $Id$
 */

function divShowHideFunc(ahref, id) {
      $(ahref).addEvent('click', function() {
      if ($(id).getStyle('display')=="none")
	$(id).tween('display', 'block');
      else
	$(id).tween('display', 'none');
    });
}
