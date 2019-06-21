<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Creates common.css.php file
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\ThemeGenerator;

/**
 * Function to create Common.inc.php in phpMyAdmin
 *
 * @package PhpMyAdmin
 */
class Layout
{
    /**
     * Creates navigation.css.php
     *
     * @param string $name name of new theme
     *
     * @return null
     */
    public function createLayoutFiles($name)
    {
        $file = fopen("themes/" . $name . "/scss/_direction.scss", "w");

        $txt = '$direction: ltr !default;

        @if $direction != ltr and $direction != rtl {
          $direction: ltr;
        }

        $left: left;
        $right: right;

        @if $direction == rtl {
          $left: right;
          $right: left;
        }';
        if ($file) {
            fwrite($file, $txt);
            fclose($file);
        } else {
            trigger_error("The _direction.php file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        }

        $file = fopen("themes/" . $name . "/scss/_codemirror.scss", "w");

        $txt = '$textarea-cols: 40;
        $textarea-rows: 15;

        .CodeMirror {
          height: ceil($textarea-rows * 1.2em);
          direction: ltr;
        }

        #inline_editor_outer .CodeMirror {
          height: ceil($textarea-rows * 0.4em);
        }

        .insertRowTable .CodeMirror {
          height: ceil($textarea-rows * 0.6em);
          width: ceil($textarea-cols * 0.6em);
          border: 1px solid #a9a9a9;
        }

        #pma_console .CodeMirror-gutters {
          background-color: initial;
          border: none;
        }

        span {
          &.cm-keyword,
          &.cm-statement-verb {
            color: #909;
          }

          &.cm-variable {
            color: black;
          }

          &.cm-comment {
            color: #808000;
          }

          &.cm-mysql-string {
            color: #008000;
          }

          &.cm-operator {
            color: fuchsia;
          }

          &.cm-mysql-word {
            color: black;
          }

          &.cm-builtin {
            color: #f00;
          }

          &.cm-variable-2 {
            color: #f90;
          }

          &.cm-variable-3 {
            color: #00f;
          }

          &.cm-separator {
            color: fuchsia;
          }

          &.cm-number {
            color: teal;
          }
        }

        .autocomplete-column-name {
          display: inline-block;
        }

        .autocomplete-column-hint {
          display: inline-block;
          float: $right;
          color: #666;
          margin-#{$left}: 1em;
        }

        .CodeMirror-hints {
          z-index: 999;
        }

        .CodeMirror-lint-tooltip {
          z-index: 200;
          font-family: inherit;

          code {
            font-family: monospace;
            font-weight: bold;
          }
        }';
        if ($file) {
            fwrite($file, $txt);
            fclose($file);
        } else {
            trigger_error("The _codemirror.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        }

        $file = fopen("themes/" . $name . "/scss/_designer.scss", "w");

        $txt = '$header-img: \'theme/pmahomme/img/designer/Header.png\';
        $header-linked-img: \'theme/pmahomme/img/designer/Header_Linked.png\';
        $minus-img: \'theme/pmahomme/img/designer/minus.png\';
        $plus-img: \'theme/pmahomme/img/designer/plus.png\';
        $left-panel-button-img: \'theme/pmahomme/img/designer/left_panel_butt.png\';
        $top-panel-img: \'theme/pmahomme/img/designer/top_panel.png\';
        $small-tab-img: \'theme/pmahomme/img/designer/small_tab.png\';
        $frams1-img: \'theme/pmahomme/img/designer/1.png\';
        $frams2-img: \'theme/pmahomme/img/designer/2.png\';
        $frams3-img: \'theme/pmahomme/img/designer/3.png\';
        $frams4-img: \'theme/pmahomme/img/designer/4.png\';
        $frams5-img: \'theme/pmahomme/img/designer/5.png\';
        $frams6-img: \'theme/pmahomme/img/designer/6.png\';
        $frams7-img: \'theme/pmahomme/img/designer/7.png\';
        $frams8-img: \'theme/pmahomme/img/designer/8.png\';
        $resize-img: \'theme/pmahomme/img/designer/resize.png\';

        /* Designer */
        .input_tab {
          background-color: #a6c7e1;
          color: #000;
        }

        .content_fullscreen {
          position: relative;
          overflow: auto;
        }

        #canvas_outer {
          position: relative;
          width: 100%;
          display: block;
        }

        #canvas {
          background-color: #fff;
          color: #000;
        }

        canvas.designer {
          display: inline-block;
          overflow: hidden;
          text-align: $left;

          * {
            behavior: url(#default#VML);
          }
        }

        .designer_tab {
          background-color: #fff;
          color: #000;
          border-collapse: collapse;
          border: 1px solid #aaa;
          z-index: 1;
          -moz-user-select: none;

          .header {
            background-image: url($header-img);
            background-repeat: repeat-x;
          }
        }

        .tab_zag {
          text-align: center;
          cursor: move;
          padding: 1px;
          font-weight: bold;
        }

        .tab_zag_2 {
          background-image: url($header-linked-img);
          background-repeat: repeat-x;
          text-align: center;
          cursor: move;
          padding: 1px;
          font-weight: bold;
        }

        .tab_field {
          background: #fff;
          color: #000;
          cursor: default;

          &:hover {
            background-color: #cfc;
            color: #000;
            background-repeat: repeat-x;
            cursor: default;
          }
        }

        .tab_field_3 {
          background-color: #ffe6e6;
          color: #000;
          cursor: default;

          &:hover {
            background-color: #cfc;
            color: #000;
            background-repeat: repeat-x;
            cursor: default;
          }
        }

        #designer_hint {
          white-space: nowrap;
          position: absolute;
          background-color: #9f9;
          color: #000;
          z-index: 3;
          border: #0c6 solid 1px;
          display: none;
        }

        .scroll_tab {
          overflow: auto;
          width: 100%;
          height: 500px;
        }

        .designer_Tabs {
          cursor: default;
          color: #05b;
          white-space: nowrap;
          text-decoration: none;
          text-indent: 3px;
          font-weight: bold;
          margin-#{$left}: 2px;
          text-align: $left;
          background-color: #fff;
          background-image: url($left-panel-button-img);
          border: #ccc solid 1px;

          &:hover {
            cursor: default;
            color: #05b;
            background: #fe9;
            text-indent: 3px;
            font-weight: bold;
            white-space: nowrap;
            text-decoration: none;
            border: #99f solid 1px;
            text-align: $left;
          }
        }

        .owner {
          font-weight: normal;
          color: #888;
        }

        .option_tab {
          padding-#{$left}: 2px;
          padding-#{$right}: 2px;
          width: 5px;
        }

        .select_all {
          vertical-align: top;
          padding-#{$left}: 2px;
          padding-#{$right}: 2px;
          cursor: default;
          width: 1px;
          color: #000;
          background-image: url($header-img);
          background-repeat: repeat-x;
        }

        .small_tab {
          vertical-align: top;
          background-color: #0064ea;
          color: #fff;
          background-image: url($small-tab-img);
          cursor: default;
          text-align: center;
          font-weight: bold;
          padding-#{$left}: 2px;
          padding-#{$right}: 2px;
          width: 1px;
          text-decoration: none;

          &:hover {
            vertical-align: top;
            color: #fff;
            background-color: #f96;
            cursor: default;
            padding-#{$left}: 2px;
            padding-#{$right}: 2px;
            text-align: center;
            font-weight: bold;
            width: 1px;
            text-decoration: none;
          }
        }

        .small_tab_pref {
          background-image: url($header-img);
          background-repeat: repeat-x;
          text-align: center;
          width: 1px;

          &:hover {
            vertical-align: top;
            color: #fff;
            background-color: #f96;
            cursor: default;
            text-align: center;
            font-weight: bold;
            width: 1px;
            text-decoration: none;
          }
        }

        .butt {
          border: #47a solid 1px;
          font-weight: bold;
          height: 19px;
          width: 70px;
          background-color: #fff;
          color: #000;
          vertical-align: baseline;
        }

        .L_butt2_1 {
          padding: 1px;
          text-decoration: none;
          vertical-align: middle;
          cursor: default;

          &:hover {
            padding: 0;
            border: #09c solid 1px;
            background: #fe9;
            color: #000;
            text-decoration: none;
            vertical-align: middle;
            cursor: default;
          }
        }

        /* --------------------------------------------------------------------------- */
        .bor {
          width: 10px;
          height: 10px;
        }

        .frams1 {
          background: url($frams1-img) no-repeat $right bottom;
        }

        .frams2 {
          background: url($frams2-img) no-repeat $left bottom;
        }

        .frams3 {
          background: url($frams3-img) no-repeat $left top;
        }

        .frams4 {
          background: url($frams4-img) no-repeat $right top;
        }

        .frams5 {
          background: url($frams5-img) repeat-x center bottom;
        }

        .frams6 {
          background: url($frams6-img) repeat-y $left;
        }

        .frams7 {
          background: url($frams7-img) repeat-x top;
        }

        .frams8 {
          background: url($frams8-img) repeat-y $right;
        }

        #osn_tab {
          position: absolute;
          background-color: #fff;
          color: #000;
        }

        .designer_header {
          background-color: #eaeef0;
          color: #000;
          text-align: center;
          font-weight: bold;
          margin: 0;
          padding: 0;
          background-image: url($top-panel-img);
          background-position: top;
          background-repeat: repeat-x;
          border-#{$right}: #999 solid 1px;
          border-#{$left}: #999 solid 1px;
          height: 28px;
          z-index: 101;
          width: 100%;
          position: fixed;

          a,
          span {
            display: block;
            float: $left;
            margin: 3px 1px 4px;
            height: 20px;
            border: 1px dotted #fff;
          }

          .M_bord {
            display: block;
            float: $left;
            margin: 4px;
            height: 20px;
            width: 2px;
          }

          a {
            &.first {
              margin-#{$right}: 1em;
            }

            &.last {
              margin-#{$left}: 1em;
            }
          }
        }

        a {
          &.M_butt_Selected_down_IE,
          &.M_butt_Selected_down {
            border: 1px solid #c0c0bb;
            background-color: #9f9;
            color: #000;

            &:hover {
              border: 1px solid #09c;
              background-color: #fe9;
              color: #000;
            }
          }

          &.M_butt:hover {
            border: 1px solid #09c;
            background-color: #fe9;
            color: #000;
          }
        }

        #layer_menu {
          z-index: 98;
          position: relative;
          float: $right;
          background-color: #eaeef0;
          border: #999 solid 1px;

          &.left {
            float: $left;
          }
        }

        #layer_upd_relation {
          position: absolute;
          #{$left}: 637px;
          top: 224px;
          z-index: 100;
        }

        #layer_new_relation,
        #designer_optionse {
          position: absolute;
          #{$left}: 636px;
          top: 85px;
          z-index: 100;
          width: 153px;
        }

        #layer_menu_sizer {
          background-image: url($resize-img);
          cursor: ew-resize;

          .icon {
            margin: 0;
          }
        }

        .panel {
          position: fixed;
          top: 60px;
          #{$right}: 0;
          width: 350px;
          max-height: 500px;
          display: none;
          overflow: auto;
          padding-top: 34px;
          z-index: 102;
        }

        a {
          &.trigger {
            position: fixed;
            text-decoration: none;
            top: 60px;
            #{$right}: 0;
            color: #fff;
            padding: 10px 40px 10px 15px;
            background: #333 url($plus-img) 85% 55% no-repeat;
            border: 1px solid #444;
            display: block;
            z-index: 102;

            &:hover {
              color: #080808;
              background: #fff696 url($plus-img) 85% 55% no-repeat;
              border: 1px solid #999;
            }
          }

          &.active.trigger {
            background: #222 url($minus-img) 85% 55% no-repeat;
            z-index: 999;

            &:hover {
              background: #fff696 url($minus-img) 85% 55% no-repeat;
            }
          }
        }

        .toggle_container .block {
          background-color: #dbe4e8;
          border-top: 1px solid #999;
        }

        .history_table {
          text-align: center;
          cursor: pointer;
          background-color: #dbe4e8;

          &:hover {
            background-color: #99c;
          }
        }

        #ab {
          min-width: 300px;

          .ui-accordion-content {
            padding: 0;
          }
        }

        #box {
          display: none;
        }

        #foreignkeychk {
          text-align: $left;
          position: absolute;
          cursor: pointer;
        }

        .side-menu {
          float: $left;
          position: fixed;
          width: auto;
          height: auto;
          background: #efefef;
          border: 1px solid grey;
          overflow: hidden;
          z-index: 50;
          padding: 2px;

          &.right {
            float: $right;
            #{$right}: 0;
          }

          .hide {
            display: none;
          }

          a {
            display: block;
            float: none;
            overflow: hidden;
          }

          img,
          .text {
            float: $left;
          }
        }

        #name-panel {
          border-bottom: 1px solid grey;
          text-align: center;
          background: #efefef;
          width: 100%;
          font-size: 1.2em;
          padding: 10px;
          font-weight: bold;
        }

        #container-form {
          width: 100%;
          position: absolute;
          #{$left}: 0;
        }
        ';

        if ($file) {
            fwrite($file, $txt);
            fclose($file);
        } else {
            trigger_error("The _designer.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        }

        $file = fopen("themes/" . $name . "/scss/_enum-editor.scss", "w");

        $txt = '/**
        * ENUM/SET editor styles
        */
       p.enum_notice {
         margin: 5px 2px;
         font-size: 80%;
       }

       #enum_editor {
         p {
           margin-top: 0;
           font-style: italic;
         }

         .values {
           width: 100%;
         }

         .add {
           width: 100%;

           td {
             vertical-align: middle;
             width: 50%;
             padding: 0 0 0;
             padding-#{$left}: 1em;
           }
         }

         .values {
           td.drop {
             width: 1.8em;
             cursor: pointer;
             vertical-align: middle;
           }

           input {
             margin: 0.1em 0;
             padding-#{$right}: 2em;
             width: 100%;
           }

           img {
             width: 1.8em;
             vertical-align: middle;
           }
         }

         input.add_value {
           margin: 0;
           margin-#{$right}: 0.4em;
         }
       }

       #enum_editor_output textarea {
         width: 100%;
         float: $right;
         margin: 1em 0 0 0;
       }

       /**
        * ENUM/SET editor integration for the routines editor
        */
       .enum_hint {
         position: relative;

         a {
           position: absolute;
           #{$left}: 81%;
           bottom: 0.35em;
         }
       }';

       if ($file) {
        fwrite($file, $txt);
        fclose($file);
        } else {
            trigger_error("The _enum-editor.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        };

    $file = fopen("themes/" . $name . "/scss/_gis.scss", "w");

    $txt = '/**
        * GIS data editor styles
        */
       a.close_gis_editor {
         float: $right;
       }

       #gis_editor {
         display: none;
         position: fixed;
         z-index: 1001;
         overflow-y: auto;
         overflow-x: hidden;
       }

       #gis_data {
         min-height: 230px;
       }

       #gis_data_textarea {
         height: 6em;
       }

       #gis_data_editor {
         background: #d0dce0;
         padding: 15px;
         min-height: 500px;

         .choice {
           display: none;
         }

         input[type="text"] {
           width: 75px;
         }
       }';

       if ($file) {
        fwrite($file, $txt);
        fclose($file);
        } else {
            trigger_error("The _gis.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        };

        $file = fopen("themes/" . $name . "/scss/_icons.scss", "w");
        $txt = '.icon {
            margin: 0;
            margin-#{$left}: 0.3em;
            padding: 0 !important;
            width: 16px;
            height: 16px;
          }

          .icon_fulltext {
            width: 50px;
            height: 19px;
          }

          .ic_asc_order {
            background-image: url("theme/pmahomme/img/asc_order.png");
          }

          .ic_b_bookmark {
            background-image: url("theme/pmahomme/img/b_bookmark.png");
          }

          .ic_b_browse {
            background-image: url("theme/pmahomme/img/b_browse.png");
          }

          .ic_b_calendar {
            background-image: url("theme/pmahomme/img/b_calendar.png");
          }

          .ic_b_chart {
            background-image: url("theme/pmahomme/img/b_chart.png");
          }

          .ic_b_close {
            background-image: url("theme/pmahomme/img/b_close.png");
          }

          .ic_b_column_add {
            background-image: url("theme/pmahomme/img/b_column_add.png");
          }

          .ic_b_comment {
            background-image: url("theme/pmahomme/img/b_comment.png");
          }

          .ic_b_dbstatistics {
            background-image: url("theme/pmahomme/img/b_dbstatistics.png");
          }

          .ic_b_deltbl {
            background-image: url("theme/pmahomme/img/b_deltbl.png");
          }

          .ic_b_docs {
            background-image: url("theme/pmahomme/img/b_docs.png");
          }

          .ic_b_docsql {
            background-image: url("theme/pmahomme/img/b_docsql.png");
          }

          .ic_b_drop {
            background-image: url("theme/pmahomme/img/b_drop.png");
          }

          .ic_b_edit {
            background-image: url("theme/pmahomme/img/b_edit.png");
          }

          .ic_b_empty {
            background-image: url("theme/pmahomme/img/b_empty.png");
          }

          .ic_b_engine {
            background-image: url("theme/pmahomme/img/b_engine.png");
          }

          .ic_b_event_add {
            background-image: url("theme/pmahomme/img/b_event_add.png");
          }

          .ic_b_events {
            background-image: url("theme/pmahomme/img/b_events.png");
          }

          .ic_b_export {
            background-image: url("theme/pmahomme/img/b_export.png");
          }

          .ic_b_favorite {
            background-image: url("theme/pmahomme/img/b_favorite.png");
          }

          .ic_b_find_replace {
            background-image: url("theme/pmahomme/img/b_find_replace.png");
          }

          .ic_b_firstpage {
            background-image: url("theme/pmahomme/img/b_firstpage.png");
          }

          .ic_b_ftext {
            background-image: url("theme/pmahomme/img/b_ftext.png");
          }

          .ic_b_globe {
            background-image: url("theme/pmahomme/img/b_globe.gif");
          }

          .ic_b_group {
            background-image: url("theme/pmahomme/img/b_group.png");
          }

          .ic_b_help {
            background-image: url("theme/pmahomme/img/b_help.png");
          }

          .ic_b_home {
            background-image: url("theme/pmahomme/img/b_home.png");
          }

          .ic_b_import {
            background-image: url("theme/pmahomme/img/b_import.png");
          }

          .ic_b_index {
            background-image: url("theme/pmahomme/img/b_index.png");
          }

          .ic_b_index_add {
            background-image: url("theme/pmahomme/img/b_index_add.png");
          }

          .ic_b_info {
            background-image: url("theme/pmahomme/img/b_info.png");
            width: 11px;
            height: 11px;
          }

          .ic_b_inline_edit {
            background-image: url("theme/pmahomme/img/b_inline_edit.png");
          }

          .ic_b_insrow {
            background-image: url("theme/pmahomme/img/b_insrow.png");
          }

          .ic_b_lastpage {
            background-image: url("theme/pmahomme/img/b_lastpage.png");
          }

          .ic_b_minus {
            background-image: url("theme/pmahomme/img/b_minus.png");
          }

          .ic_b_more {
            background-image: url("theme/pmahomme/img/b_more.png");
          }

          .ic_b_move {
            background-image: url("theme/pmahomme/img/b_move.png");
          }

          .ic_b_newdb {
            background-image: url("theme/pmahomme/img/b_newdb.png");
          }

          .ic_b_newtbl {
            background-image: url("theme/pmahomme/img/b_newtbl.png");
          }

          .ic_b_nextpage {
            background-image: url("theme/pmahomme/img/b_nextpage.png");
          }

          .ic_b_no_favorite {
            background-image: url("theme/pmahomme/img/b_no_favorite.png");
          }

          .ic_b_pdfdoc {
            background-image: url("theme/pmahomme/img/b_pdfdoc.png");
          }

          .ic_b_plugin {
            background-image: url("theme/pmahomme/img/b_plugin.png");
          }

          .ic_b_plus {
            background-image: url("theme/pmahomme/img/b_plus.png");
          }

          .ic_b_prevpage {
            background-image: url("theme/pmahomme/img/b_prevpage.png");
          }

          .ic_b_primary {
            background-image: url("theme/pmahomme/img/b_primary.png");
          }

          .ic_b_print {
            background-image: url("theme/pmahomme/img/b_print.png");
          }

          .ic_b_props {
            background-image: url("theme/pmahomme/img/b_props.png");
          }

          .ic_b_relations {
            background-image: url("theme/pmahomme/img/b_relations.png");
          }

          .ic_b_report {
            background-image: url("theme/pmahomme/img/b_report.png");
          }

          .ic_b_routine_add {
            background-image: url("theme/pmahomme/img/b_routine_add.png");
          }

          .ic_b_routines {
            background-image: url("theme/pmahomme/img/b_routines.png");
          }

          .ic_b_save {
            background-image: url("theme/pmahomme/img/b_save.png");
          }

          .ic_b_saveimage {
            background-image: url("theme/pmahomme/img/b_saveimage.png");
          }

          .ic_b_sbrowse {
            background-image: url("theme/pmahomme/img/b_sbrowse.png");
          }

          .ic_b_sdb {
            background-image: url("theme/pmahomme/img/b_sdb.png");
            width: 10px;
            height: 10px;
          }

          .ic_b_search {
            background-image: url("theme/pmahomme/img/b_search.png");
          }

          .ic_b_select {
            background-image: url("theme/pmahomme/img/b_select.png");
          }

          .ic_b_snewtbl {
            background-image: url("theme/pmahomme/img/b_snewtbl.png");
          }

          .ic_b_spatial {
            background-image: url("theme/pmahomme/img/b_spatial.png");
          }

          .ic_b_sql {
            background-image: url("theme/pmahomme/img/b_sql.png");
          }

          .ic_b_sqldoc {
            background-image: url("theme/pmahomme/img/b_sqldoc.png");
          }

          .ic_b_sqlhelp {
            background-image: url("theme/pmahomme/img/b_sqlhelp.png");
          }

          .ic_b_table_add {
            background-image: url("theme/pmahomme/img/b_table_add.png");
          }

          .ic_b_tblanalyse {
            background-image: url("theme/pmahomme/img/b_tblanalyse.png");
          }

          .ic_b_tblexport {
            background-image: url("theme/pmahomme/img/b_tblexport.png");
          }

          .ic_b_tblimport {
            background-image: url("theme/pmahomme/img/b_tblimport.png");
          }

          .ic_b_tblops {
            background-image: url("theme/pmahomme/img/b_tblops.png");
          }

          .ic_b_tbloptimize {
            background-image: url("theme/pmahomme/img/b_tbloptimize.png");
          }

          .ic_b_tipp {
            background-image: url("theme/pmahomme/img/b_tipp.png");
          }

          .ic_b_trigger_add {
            background-image: url("theme/pmahomme/img/b_trigger_add.png");
          }

          .ic_b_triggers {
            background-image: url("theme/pmahomme/img/b_triggers.png");
          }

          .ic_b_undo {
            background-image: url("theme/pmahomme/img/b_undo.png");
          }

          .ic_b_unique {
            background-image: url("theme/pmahomme/img/b_unique.png");
          }

          .ic_b_usradd {
            background-image: url("theme/pmahomme/img/b_usradd.png");
          }

          .ic_b_usrcheck {
            background-image: url("theme/pmahomme/img/b_usrcheck.png");
          }

          .ic_b_usrdrop {
            background-image: url("theme/pmahomme/img/b_usrdrop.png");
          }

          .ic_b_usredit {
            background-image: url("theme/pmahomme/img/b_usredit.png");
          }

          .ic_b_usrlist {
            background-image: url("theme/pmahomme/img/b_usrlist.png");
          }

          .ic_b_versions {
            background-image: url("theme/pmahomme/img/b_versions.png");
          }

          .ic_b_view {
            background-image: url("theme/pmahomme/img/b_view.png");
          }

          .ic_b_view_add {
            background-image: url("theme/pmahomme/img/b_view_add.png");
          }

          .ic_b_views {
            background-image: url("theme/pmahomme/img/b_views.png");
          }

          .ic_b_left {
            background-image: url("theme/pmahomme/img/b_left.png");
          }

          .ic_b_right {
            background-image: url("theme/pmahomme/img/b_right.png");
          }

          .ic_bd_browse {
            background-image: url("theme/pmahomme/img/bd_browse.png");
          }

          .ic_bd_deltbl {
            background-image: url("theme/pmahomme/img/bd_deltbl.png");
          }

          .ic_bd_drop {
            background-image: url("theme/pmahomme/img/bd_drop.png");
          }

          .ic_bd_edit {
            background-image: url("theme/pmahomme/img/bd_edit.png");
          }

          .ic_bd_empty {
            background-image: url("theme/pmahomme/img/bd_empty.png");
          }

          .ic_bd_export {
            background-image: url("theme/pmahomme/img/bd_export.png");
          }

          .ic_bd_firstpage {
            background-image: url("theme/pmahomme/img/bd_firstpage.png");
          }

          .ic_bd_ftext {
            background-image: url("theme/pmahomme/img/bd_ftext.png");
          }

          .ic_bd_index {
            background-image: url("theme/pmahomme/img/bd_index.png");
          }

          .ic_bd_insrow {
            background-image: url("theme/pmahomme/img/bd_insrow.png");
          }

          .ic_bd_lastpage {
            background-image: url("theme/pmahomme/img/bd_lastpage.png");
          }

          .ic_bd_nextpage {
            background-image: url("theme/pmahomme/img/bd_nextpage.png");
          }

          .ic_bd_prevpage {
            background-image: url("theme/pmahomme/img/bd_prevpage.png");
          }

          .ic_bd_primary {
            background-image: url("theme/pmahomme/img/bd_primary.png");
          }

          .ic_bd_routine_add {
            background-image: url("theme/pmahomme/img/bd_routine_add.png");
          }

          .ic_bd_sbrowse {
            background-image: url("theme/pmahomme/img/bd_sbrowse.png");
          }

          .ic_bd_select {
            background-image: url("theme/pmahomme/img/bd_select.png");
          }

          .ic_bd_spatial {
            background-image: url("theme/pmahomme/img/bd_spatial.png");
          }

          .ic_bd_unique {
            background-image: url("theme/pmahomme/img/bd_unique.png");
          }

          .ic_centralColumns {
            background-image: url("theme/pmahomme/img/centralColumns.png");
          }

          .ic_centralColumns_add {
            background-image: url("theme/pmahomme/img/centralColumns_add.png");
          }

          .ic_centralColumns_delete {
            background-image: url("theme/pmahomme/img/centralColumns_delete.png");
          }

          .ic_col_drop {
            background-image: url("theme/pmahomme/img/col_drop.png");
          }

          .ic_console {
            background-image: url("theme/pmahomme/img/console.png");
          }

          .ic_database {
            background-image: url("theme/pmahomme/img/database.png");
          }

          .ic_eye {
            background-image: url("theme/pmahomme/img/eye.png");
          }

          .ic_eye_grey {
            background-image: url("theme/pmahomme/img/eye_grey.png");
          }

          .ic_hide {
            background-image: url("theme/pmahomme/img/hide.png");
          }

          .ic_item {
            background-image: url("theme/pmahomme/img/item.png");
            width: 9px;
            height: 9px;
          }

          .ic_lightbulb {
            background-image: url("theme/pmahomme/img/lightbulb.png");
          }

          .ic_lightbulb_off {
            background-image: url("theme/pmahomme/img/lightbulb_off.png");
          }

          .ic_more {
            background-image: url("theme/pmahomme/img/more.png");
            width: 13px;
          }

          .ic_new_data {
            background-image: url("theme/pmahomme/img/new_data.png");
          }

          .ic_new_data_hovered {
            background-image: url("theme/pmahomme/img/new_data_hovered.png");
          }

          .ic_new_data_selected {
            background-image: url("theme/pmahomme/img/new_data_selected.png");
          }

          .ic_new_data_selected_hovered {
            background-image: url("theme/pmahomme/img/new_data_selected_hovered.png");
          }

          .ic_new_struct {
            background-image: url("theme/pmahomme/img/new_struct.png");
          }

          .ic_new_struct_hovered {
            background-image: url("theme/pmahomme/img/new_struct_hovered.png");
          }

          .ic_new_struct_selected {
            background-image: url("theme/pmahomme/img/new_struct_selected.png");
          }

          .ic_new_struct_selected_hovered {
            background-image: url("theme/pmahomme/img/new_struct_selected_hovered.png");
          }

          .ic_normalize {
            background-image: url("theme/pmahomme/img/normalize.png");
          }

          .ic_pause {
            background-image: url("theme/pmahomme/img/pause.png");
          }

          .ic_php_sym {
            background-image: url("theme/pmahomme/img/php_sym.png");
          }

          .ic_play {
            background-image: url("theme/pmahomme/img/play.png");
          }

          .ic_s_asc {
            background-image: url("theme/pmahomme/img/s_asc.png");
          }

          .ic_s_asci {
            background-image: url("theme/pmahomme/img/s_asci.png");
          }

          .ic_s_attention {
            background-image: url("theme/pmahomme/img/s_attention.png");
          }

          .ic_s_cancel {
            background-image: url("theme/pmahomme/img/s_cancel.png");
          }

          .ic_s_cancel2 {
            background-image: url("theme/pmahomme/img/s_cancel2.png");
          }

          .ic_s_cog {
            background-image: url("theme/pmahomme/img/s_cog.png");
          }

          .ic_s_db {
            background-image: url("theme/pmahomme/img/s_db.png");
          }

          .ic_s_desc {
            background-image: url("theme/pmahomme/img/s_desc.png");
          }

          .ic_s_error {
            background-image: url("theme/pmahomme/img/s_error.png");
          }

          .ic_s_host {
            background-image: url("theme/pmahomme/img/s_host.png");
          }

          .ic_s_info {
            background-image: url("theme/pmahomme/img/s_info.png");
          }

          .ic_s_lang {
            background-image: url("theme/pmahomme/img/s_lang.png");
          }

          .ic_s_link {
            background-image: url("theme/pmahomme/img/s_link.png");
          }

          .ic_s_lock {
            background-image: url("theme/pmahomme/img/s_lock.png");
          }

          .ic_s_loggoff {
            background-image: url("theme/pmahomme/img/s_loggoff.png");
          }

          .ic_s_notice {
            background-image: url("theme/pmahomme/img/s_notice.png");
          }

          .ic_s_okay {
            background-image: url("theme/pmahomme/img/s_okay.png");
          }

          .ic_s_passwd {
            background-image: url("theme/pmahomme/img/s_passwd.png");
          }

          .ic_s_process {
            background-image: url("theme/pmahomme/img/s_process.png");
          }

          .ic_s_really {
            background-image: url("theme/pmahomme/img/s_really.png");
            width: 11px;
            height: 11px;
          }

          .ic_s_reload {
            background-image: url("theme/pmahomme/img/s_reload.png");
          }

          .ic_s_replication {
            background-image: url("theme/pmahomme/img/s_replication.png");
          }

          .ic_s_rights {
            background-image: url("theme/pmahomme/img/s_rights.png");
          }

          .ic_s_sortable {
            background-image: url("theme/pmahomme/img/s_sortable.png");
          }

          .ic_s_status {
            background-image: url("theme/pmahomme/img/s_status.png");
          }

          .ic_s_success {
            background-image: url("theme/pmahomme/img/s_success.png");
          }

          .ic_s_sync {
            background-image: url("theme/pmahomme/img/s_sync.png");
          }

          .ic_s_tbl {
            background-image: url("theme/pmahomme/img/s_tbl.png");
          }

          .ic_s_theme {
            background-image: url("theme/pmahomme/img/s_theme.png");
          }

          .ic_s_top {
            background-image: url("theme/pmahomme/img/s_top.png");
          }

          .ic_s_unlink {
            background-image: url("theme/pmahomme/img/s_unlink.png");
          }

          .ic_s_vars {
            background-image: url("theme/pmahomme/img/s_vars.png");
          }

          .ic_s_views {
            background-image: url("theme/pmahomme/img/s_views.png");
          }

          .ic_show {
            background-image: url("theme/pmahomme/img/show.png");
          }

          .ic_window-new {
            background-image: url("theme/pmahomme/img/window-new.png");
          }

          .ic_ajax_clock_small {
            background-image: url("theme/pmahomme/img/ajax_clock_small.gif");
          }

          .ic_s_partialtext {
            background-image: url("theme/pmahomme/img/s_partialtext.png");
          }

          .ic_s_fulltext {
            background-image: url("theme/pmahomme/img/s_fulltext.png");
          }
          ';

        if ($file) {
        fwrite($file, $txt);
        fclose($file);
        } else {
            trigger_error("The _icons.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        };

        $file = fopen("themes/" . $name . "/scss/_jqplot.scss", "w");
        $txt = '/* jqPlot */
        // rules for the plot target div. These will be cascaded down to all plot elements according to css rules
        .jqplot-target {
          position: relative;
          color: #222;
          font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
          font-size: 1em;
        }

        // rules applied to all axes
        .jqplot-axis {
          font-size: 0.75em;
        }

        .jqplot-xaxis {
          margin-top: 10px;
        }

        .jqplot-x2axis {
          margin-bottom: 10px;
        }

        .jqplot-yaxis {
          margin-#{$right}: 10px;
        }

        .jqplot-y2axis,
        .jqplot-y3axis,
        .jqplot-y4axis,
        .jqplot-y5axis,
        .jqplot-y6axis,
        .jqplot-y7axis,
        .jqplot-y8axis,
        .jqplot-y9axis,
        .jqplot-yMidAxis {
          margin-#{$left}: 10px;
          margin-#{$right}: 10px;
        }

        // rules applied to all axis tick divs
        .jqplot-axis-tick,
        .jqplot-xaxis-tick,
        .jqplot-yaxis-tick,
        .jqplot-x2axis-tick,
        .jqplot-y2axis-tick,
        .jqplot-y3axis-tick,
        .jqplot-y4axis-tick,
        .jqplot-y5axis-tick,
        .jqplot-y6axis-tick,
        .jqplot-y7axis-tick,
        .jqplot-y8axis-tick,
        .jqplot-y9axis-tick,
        .jqplot-yMidAxis-tick {
          position: absolute;
          white-space: pre;
        }

        .jqplot-xaxis-tick {
          top: 0;
          // initial position untill tick is drawn in proper place
          #{$left}: 15px;
          vertical-align: top;
        }

        .jqplot-x2axis-tick {
          bottom: 0;
          // initial position untill tick is drawn in proper place
          #{$left}: 15px;
          vertical-align: bottom;
        }

        .jqplot-yaxis-tick {
          #{$right}: 0;
          // initial position untill tick is drawn in proper place
          top: 15px;
          text-align: $right;

          &.jqplot-breakTick {
            #{$right}: -20px;
            margin-#{$right}: 0;
            padding: 1px 5px 1px;
            z-index: 2;
            font-size: 1.5em;
          }
        }

        .jqplot-y2axis-tick,
        .jqplot-y3axis-tick,
        .jqplot-y4axis-tick,
        .jqplot-y5axis-tick,
        .jqplot-y6axis-tick,
        .jqplot-y7axis-tick,
        .jqplot-y8axis-tick,
        .jqplot-y9axis-tick {
          #{$left}: 0;
          // initial position untill tick is drawn in proper place
          top: 15px;
          text-align: $left;
        }

        .jqplot-yMidAxis-tick {
          text-align: center;
          white-space: nowrap;
        }

        .jqplot-xaxis-label {
          margin-top: 10px;
          font-size: 11pt;
          position: absolute;
        }

        .jqplot-x2axis-label {
          margin-bottom: 10px;
          font-size: 11pt;
          position: absolute;
        }

        .jqplot-yaxis-label {
          margin-#{$right}: 10px;
          font-size: 11pt;
          position: absolute;
        }

        .jqplot-yMidAxis-label {
          font-size: 11pt;
          position: absolute;
        }

        .jqplot-y2axis-label,
        .jqplot-y3axis-label,
        .jqplot-y4axis-label,
        .jqplot-y5axis-label,
        .jqplot-y6axis-label,
        .jqplot-y7axis-label,
        .jqplot-y8axis-label,
        .jqplot-y9axis-label {
          font-size: 11pt;
          margin-#{$left}: 10px;
          position: absolute;
        }

        .jqplot-meterGauge-tick {
          font-size: 0.75em;
          color: #999;
        }

        .jqplot-meterGauge-label {
          font-size: 1em;
          color: #999;
        }

        table {
          &.jqplot-table-legend {
            margin-top: 12px;
            margin-bottom: 12px;
            margin-#{$left}: 12px;
            margin-#{$right}: 12px;
            background-color: rgba(255, 255, 255, 0.6);
            border: 1px solid #ccc;
            position: absolute;
            font-size: 0.75em;
          }

          &.jqplot-cursor-legend {
            background-color: rgba(255, 255, 255, 0.6);
            border: 1px solid #ccc;
            position: absolute;
            font-size: 0.75em;
          }
        }

        td {
          &.jqplot-table-legend {
            vertical-align: middle;
          }

          &.jqplot-seriesToggle {
            &:hover,
            &:active {
              cursor: pointer;
            }
          }
        }

        .jqplot-table-legend .jqplot-series-hidden {
          text-decoration: line-through;
        }

        div {
          &.jqplot-table-legend-swatch-outline {
            border: 1px solid #ccc;
            padding: 1px;
          }

          &.jqplot-table-legend-swatch {
            width: 0;
            height: 0;
            border-top-width: 5px;
            border-bottom-width: 5px;
            border-left-width: 6px;
            border-right-width: 6px;
            border-top-style: solid;
            border-bottom-style: solid;
            border-left-style: solid;
            border-right-style: solid;
          }
        }

        .jqplot-title {
          top: 0;
          #{$left}: 0;
          padding-bottom: 0.5em;
          font-size: 1.2em;
        }

        table.jqplot-cursor-tooltip {
          border: 1px solid #ccc;
          font-size: 0.75em;
        }

        .jqplot-cursor-tooltip,
        .jqplot-highlighter-tooltip,
        .jqplot-canvasOverlay-tooltip {
          border: 1px solid #ccc;
          font-size: 0.75em;
          white-space: nowrap;
          background: rgba(208, 208, 208, 0.5);
          padding: 1px;
        }

        .jqplot-point-label {
          font-size: 0.75em;
          z-index: 2;
        }

        td.jqplot-cursor-legend-swatch {
          vertical-align: middle;
          text-align: center;
        }

        div.jqplot-cursor-legend-swatch {
          width: 1.2em;
          height: 0.7em;
        }

        .jqplot-error {
          // Styles added to the plot target container when there is an error go here.
          text-align: center;
        }

        .jqplot-error-message {
          // Styling of the custom error message div goes here.
          position: relative;
          top: 46%;
          display: inline-block;
        }

        div {
          &.jqplot-bubble-label {
            font-size: 0.8em;
            padding-#{$left}: 2px;
            padding-#{$right}: 2px;
            color: rgb(20%, 20%, 20%);

            &.jqplot-bubble-label-highlight {
              background: rgba(90%, 90%, 90%, 0.7);
            }
          }

          &.jqplot-noData-container {
            text-align: center;
            background-color: rgba(96%, 96%, 96%, 0.3);
          }
        }
        ';
        if ($file) {
            fwrite($file, $txt);
            fclose($file);
            } else {
                trigger_error("The _jqplot.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
            };
        $file = fopen("themes/" . $name . "/scss/_resizable-menu.scss", "w");
        $txt = 'ul.resizable-menu {
            a,
            span {
              display: block;
              margin: 0;
              padding: 0;
              white-space: nowrap;
            }

            .submenu {
              display: none;
              position: relative;
            }

            .shown {
              display: inline-block;
            }

            ul {
              margin: 0;
              padding: 0;
              position: absolute;
              list-style-type: none;
              display: none;
              border: 1px #ddd solid;
              z-index: 2;
              #{$right}: 0;
            }

            li:hover {
              background: linear-gradient(#fff, #e5e5e5);

              ul {
                display: block;
                background: #fff;
              }
            }

            .submenuhover ul {
              display: block;
              background: #fff;
            }

            ul li {
              width: 100%;
            }
        }';
        if ($file) {
            fwrite($file, $txt);
            fclose($file);
        } else {
            trigger_error("The _resizeable-menu.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        };
        $file = fopen("themes/" . $name . "/scss/_rte.scss", "w");
        $txt = '.rte_table {
            table-layout: auto;
            width: 100%;

            td {
              vertical-align: middle;
              padding: 0.2em;
              width: 20%;
            }

            tr td:nth-child(1) {
              font-weight: bold;
            }

            input,
            select,
            textarea {
              width: 100%;
              margin: 0;
              box-sizing: border-box;
            }

            input {
              &[type=button],
              &[type=checkbox],
              &[type=radio] {
                width: auto;
                margin-#{$right}: 6px;
              }
            }

            .routine_params_table {
              width: 100%;
            }

            .half_width {
              width: 49%;
            }
          }

          .isdisableremoveparam_class {
            color: gray;
          }

          .ui_tpicker_time_input {
            width: 100%;
          }
          ';
          if ($file) {
            fwrite($file, $txt);
            fclose($file);
        } else {
            trigger_error("The _rte.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        };

    return null;
    }
}
