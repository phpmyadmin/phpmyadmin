# This is a little hack to get around the fact that sass doesn't build sheets from .scss files with embedded php statements, I'm sure there is a better way, this one has worked, It might also depend on your sass config and ver,
# It will work as long as long your sass version or config (I used sass 3.1.12 default config  ) make it place this comment /*sass*/ on a line of its own, and the closing bracket } at the same line as the last declaration or comment of a rule.
# the script use these two facts to uncomment php statements and delete the superfluous ones marked by /*sass*/

# it removes [/*php], [php*/], [...\n/*sass*/] markers,
# and change -right- -left-, right: left: into pma php statements for rtl support.
# Not that great as a solution but, couldn't come up with a better one at this time.


# Activating php-ed statements by removing /*php and php*/ (php-ed statements cause errors during sass compilation, that's why they are commented out with /*php ... php*/ to allow sass compilation )
sed -i 's|\/\*php||g;s|php\*\/||g' ../css/theme_left.css.php
sed -i 's|\/\*php||g;s|php\*\/||g' ../css/theme_right.css.php

# changing /*pp into <?php - a fix for php statements that have /**/ comments in them
sed -i 's|\/\*pp|\<\?php|g' ../css/theme_left.css.php
sed -i 's|\/\*pp|\<\?php|g' ../css/theme_right.css.php

# Displacing the closing bracket } to the next line, which (this version of sass or config) is always at the same line as the last property, to avoid loosing it when removing /*sass*/ tagged lines
sed -i 's| \}|\n\}|g' ../css/theme_left.css.php
sed -i 's| \}|\n\}|g' ../css/theme_right.css.php

# removing the /*sass*/ line and the one above/before it
sed -n -i '/\/\*sass\*\//{n;x;d;};x;1d;p;${x;p;}' ../css/theme_left.css.php
sed -n -i '/\/\*sass\*\//{n;x;d;};x;1d;p;${x;p;}' ../css/theme_right.css.php

# fixing left over border-radius:
sed -i 's|\-left\-radius|\-<?php echo $left?>\-radius|g' ../css/theme_left.css.php
sed -i 's|\-right\-radius|\-<?php echo $right?>\-radius|g' ../css/theme_left.css.php

sed -i 's|\-left\-radius|\-<?php echo $left?>\-radius|g' ../css/theme_right.css.php
sed -i 's|\-right\-radius|\-<?php echo $right?>\-radius|g' ../css/theme_right.css.php

# phpfying left and right properties:
sed -i 's| left:| <?php echo $left?>:|g' ../css/theme_left.css.php
sed -i 's| right:| <?php echo $right?>:|g' ../css/theme_left.css.php

sed -i 's| left:| <?php echo $left?>:|g' ../css/theme_right.css.php
sed -i 's| right:| <?php echo $right?>:|g' ../css/theme_right.css.php

