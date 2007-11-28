<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * print css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Arctic_Ocean
 */

    $pma_fsize = $_SESSION['PMA_Config']->get('fontsize');
    $pma_fsize = preg_replace("/[^0-9]/", "", $pma_fsize);
    $pma_fsize = @($pma_fsize / 100);
    if ( isset($GLOBALS['cfg']['FontSize']) && !empty($GLOBALS['cfg']['FontSize']) ) {
        $usr_fsize = preg_replace("/[^0-9]/", "", $GLOBALS['cfg']['FontSize']);
        $fsize     = ceil($usr_fsize * $pma_fsize)
                   . ( (isset($GLOBALS['cfg']['FontSizePrefix']) && !empty($GLOBALS['cfg']['FontSizePrefix'])) ? $GLOBALS['cfg']['FontSizePrefix'] : 'pt' );
    } else
        $fsize = $_SESSION['PMA_Config']->get('fontsize');
?>
html {
    font-size:           <?php echo $fsize; ?>;
}
body, table, th, td {
    color:               #000000;
    background-color:    #ffffff;
<?php if (!empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:         <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    font-size:           <?php echo $fsize; ?>;
}


a:link, a:visited, a:active {
    color:               #bb3902;
    font-weight:         bold;
    text-decoration:     none;
}
a:hover {
    color:               #bb3902;
    text-decoration:     underline;
}

h1, h2, h3    { font-weight: bold;    }
h1            { font-size:   130%;    }
h2            { font-size:   120%;    }
h3            { font-size:   110%;    }

img { border: none; }

table, th, td {
    border-width:        1px;
    border-color:        #000000;
    border-style:        solid;
}
table {
    border-collapse:     collapse;
    border-spacing:      0;
}
th, td { padding: 2px; }
th {
    background-color:    #e5e5e5;
    color:               #bb3902;
    font-weight:         bold;
}
table tr.odd th, table tr.odd td,
table tr.even th, table tr.even td, .even {
    text-align:          <?php echo $left; ?>;
}
table tr.hover th, table tr.odd:hover th, table tr.even:hover th {
    background:          <?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    color:               <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}
table tr.hover td, table tr.odd:hover td, table tr.even:hover td {
    color:               #000000;
    background-color:    <?php echo $GLOBALS['cfg']['BgOne']; ?>;
}
table td table {
    margin:              0px;
    padding:             0px;
    width:               auto;
}
table td table, table td table td, table td table th {
    border:              1px none #999999;
}
table td table td, table td table th {
    font-size:           95%;
    white-space:         nowrap;
}

#serverinfo {
    background-color:    #ffffff;
    font-weight:         bold;
    padding:             5px 5px 5px 5px;
    margin-top:          0px;
    white-space:         nowrap;
    vertical-align:      middle;
    border-bottom:       1px solid #bb3902;
    height:              16px;
}
#serverinfo .item { white-space: nowrap;          }
#serverinfo img   { margin:      0px 1px 0px 1px; }
#serverinfo .separator img {
    width:               9px;
    height:              11px;
    margin:              0px 2px 0px 2px;
    vertical-align:      middle;
}

#selflink { display: none; }
