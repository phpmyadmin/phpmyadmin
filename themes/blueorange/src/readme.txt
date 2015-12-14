run sass.sh script to watch and apply the changes to theme/css/ directory as you work on your .scss files

Everything works as a normal sass project, until you include php statements which are needed by pma for customization and RTL support, sass would stop and throw errors at them.

The hack around it, is to wrap the php statements with /*php php*/ and append any declaration not needed later with /*sass*/ on the same line or the next line below it,

Once the css files are generated/updated, run the sass-php.sh script, to activate php statements by removing /*php php*/ markers, and to delete the no longer needed rules marked by /*sass*/ below them.

This hack has been built according to the way sass (3.1.12) handles } and /**/ when building the sheets. Results may differ based on sass version or config


you can check by making sure the  sheet files built by sass have their closing bracket "}" on the same line as the last declaration  of each rule
and a /*sass*/ (or any /**/) is always in a line of its own. like below
.rule1{
color: red;
/*sass*/
padding: 10px; }
