CodeMirror.defineMode("mysql", function(config, parserConfig) {
  var indentUnit       = config.indentUnit,
      keywords         = parserConfig.keywords,
      functions        = parserConfig.functions,
      types            = parserConfig.types,
      attributes            = parserConfig.attributes,
      multiLineStrings = parserConfig.multiLineStrings;
  var isOperatorChar   = /[+\-*&%=<>!?:\/|]/;
  function chain(stream, state, f) {
    state.tokenize = f;
    return f(stream, state);
  }

  var type;
  function ret(tp, style) {
    type = tp;
    return style;
  }

  function tokenBase(stream, state) {
    var ch = stream.next();
    // start of string?
    if (ch == '"' || ch == "'" || ch == '`')
      return chain(stream, state, tokenString(ch));
    // is it one of the special signs []{}().,;? Seperator?
    else if (/[\[\]{}\(\),;\.]/.test(ch))
      return ret(ch, "separator");
    // start of a number value?
    else if (/\d/.test(ch)) {
      stream.eatWhile(/[\w\.]/);
      return ret("number", "number");
    }
    // multi line comment or simple operator?
    else if (ch == "/") {
      if (stream.eat("*")) {
        return chain(stream, state, tokenComment);
      }
      else {
        stream.eatWhile(isOperatorChar);
        return ret("operator", "operator");
      }
    }
    // single line comment or simple operator?
    else if (ch == "-") {
      if (stream.eat("-")) {
        stream.skipToEnd();
        return ret("comment", "comment");
      }
      else {
        stream.eatWhile(isOperatorChar);
        return ret("operator", "operator");
      }
    }
    // pl/sql variable?
    else if (ch == "@" || ch == "$") {
      stream.eatWhile(/[\w\d\$_]/);
      return ret("word", "variable");
    }
    // is it a operator?
    else if (isOperatorChar.test(ch)) {
      stream.eatWhile(isOperatorChar);
      return ret("operator", "operator");
    }
    else {
      // get the whole word
      stream.eatWhile(/[\w\$_]/);
      // is it one of the listed keywords?
      if (keywords && keywords.propertyIsEnumerable(stream.current().toLowerCase())) return ret("keyword", "keyword");
      // is it one of the listed functions?
      if (functions && functions.propertyIsEnumerable(stream.current().toLowerCase())) return ret("keyword", "builtin");
      // is it one of the listed types?
      if (types && types.propertyIsEnumerable(stream.current().toLowerCase())) return ret("keyword", "variable-2");
      // is it one of the listed attributes?
      if (attributes && attributes.propertyIsEnumerable(stream.current().toLowerCase())) return ret("keyword", "variable-3");
      // default: just a "word"
      return ret("word", "mysql-word");
    }
  }

  function tokenString(quote) {
    return function(stream, state) {
      var escaped = false, next, end = false;
      while ((next = stream.next()) != null) {
        if (next == quote && !escaped) {end = true; break;}
        escaped = !escaped && next == "\\";
      }
      if (end || !(escaped || multiLineStrings))
        state.tokenize = tokenBase;
      return ret("string", "mysql-string");
    };
  }

  function tokenComment(stream, state) {
    var maybeEnd = false, ch;
    while (ch = stream.next()) {
      if (ch == "/" && maybeEnd) {
        state.tokenize = tokenBase;
        break;
      }
      maybeEnd = (ch == "*");
    }
    return ret("comment", "mysql-comment");
  }

  // Interface

  return {
    startState: function(basecolumn) {
      return {
        tokenize: tokenBase,
        startOfLine: true
      };
    },

    token: function(stream, state) {
      if (stream.eatSpace()) return null;
      var style = state.tokenize(stream, state);
      return style;
    }
  };
});

(function() {
  function keywords(str) {
    var obj = {}, words = str.split(" ");
    for (var i = 0; i < words.length; ++i) obj[words[i]] = true;
    return obj;
  }
  var cKeywords = "accessible add all alter analyze and as asc asensitive before between bigint binary blob both by call cascade case change char character check collate column condition constraint continue convert create cross current_date current_time current_timestamp current_user cursor database databases day_hour day_microsecond day_minute day_second dec decimal declare default delayed delete desc describe deterministic distinct distinctrow div double drop dual each else elseif enclosed escaped exists exit explain false fetch float float4 float8 for force foreign from fulltext grant group having high_priority hour_microsecond hour_minute hour_second if ignore in index infile inner inout insensitive insert int int1 int2 int3 int4 int8 integer interval into is iterate join key keys kill leading leave left like limit linear lines load localtime localtimestamp lock long longblob longtext loop low_priority master_ssl_verify_server_cert match maxvalue mediumblob mediumint mediumtext middleint minute_microsecond minute_second mod modifies natural not no_write_to_binlog null numeric on optimize option optionally or order out outer outfile precision primary procedure purge range read reads read_write real references regexp release rename repeat replace require resignal restrict return revoke right rlike schema schemas second_microsecond select sensitive separator set show signal smallint spatial specific sql sqlexception sqlstate sqlwarning sql_big_result sql_calc_found_rows sql_small_result ssl starting straight_join table terminated then tinyblob tinyint tinytext to trailing trigger true undo union unique unlock unsigned update usage use using utc_date utc_time utc_timestamp values varbinary varchar varcharacter varying when where while with write xor year_month zerofill";

  var cFunctions = "abs acos adddate addtime aes_decrypt aes_encrypt area asbinary ascii asin astext atan atan2 avg bdmpolyfromtext bdmpolyfromwkb bdpolyfromtext bdpolyfromwkb benchmark bin bit_and bit_count bit_length bit_or bit_xor boundary buffer cast ceil ceiling centroid char character_length charset char_length coalesce coercibility collation compress concat concat_ws connection_id contains conv convert convert_tz convexhull cos cot count crc32 crosses curdate current_date current_time current_timestamp current_user curtime database date datediff date_add date_diff date_format date_sub day dayname dayofmonth dayofweek dayofyear decode default degrees des_decrypt des_encrypt difference dimension disjoint distance elt encode encrypt endpoint envelope equals exp export_set exteriorring extract extractvalue field find_in_set floor format found_rows from_days from_unixtime geomcollfromtext geomcollfromwkb geometrycollection geometrycollectionfromtext geometrycollectionfromwkb geometryfromtext geometryfromwkb geometryn geometrytype geomfromtext geomfromwkb get_format get_lock glength greatest group_concat group_unique_users hex hour if ifnull inet_aton inet_ntoa insert instr interiorringn intersection intersects interval isclosed isempty isnull isring issimple is_free_lock is_used_lock last_day last_insert_id lcase least left length linefromtext linefromwkb linestring linestringfromtext linestringfromwkb ln load_file localtime localtimestamp locate log log10 log2 lower lpad ltrim makedate maketime make_set master_pos_wait max mbrcontains mbrdisjoint mbrequal mbrintersects mbroverlaps mbrtouches mbrwithin md5 microsecond mid min minute mlinefromtext mlinefromwkb mod month monthname mpointfromtext mpointfromwkb mpolyfromtext mpolyfromwkb multilinestring multilinestringfromtext multilinestringfromwkb multipoint multipointfromtext multipointfromwkb multipolygon multipolygonfromtext multipolygonfromwkb name_const now nullif numgeometries numinteriorrings numpoints oct octet_length old_password ord overlaps password period_add period_diff pi point pointfromtext pointfromwkb pointn pointonsurface polyfromtext polyfromwkb polygon polygonfromtext polygonfromwkb position pow power quarter quote radians rand related release_lock repeat replace reverse right round row_count rpad rtrim schema second sec_to_time session_user sha sha1 sign sin sleep soundex space sqrt srid startpoint std stddev stddev_pop stddev_samp strcmp str_to_date subdate substr substring substring_index subtime sum symdifference sysdate system_user tan time timediff timestamp timestampadd timestampdiff time_format time_to_sec touches to_days trim truncate ucase uncompress uncompressed_length unhex unique_users unix_timestamp updatexml upper user utc_date utc_time utc_timestamp uuid variance var_pop var_samp version week weekday weekofyear within x y year yearweek";

  var cTypes = "bigint binary bit blob bool boolean char character date datetime dec decimal double enum float float4 float8 geometry geometrycollection int int1 int2 int3 int4 int8 integer linestring long longblob longtext mediumblob mediumint mediumtext middleint multilinestring multipoint multipolygon nchar numeric point polygon real serial set smallint text time timestamp tinyblob tinyint tinytext varbinary varchar year";

  var cAttributes = "archive ascii auto_increment bdb berkeleydb binary blackhole csv default example federated heap innobase innodb isam maria memory merge mrg_isam mrg_myisam myisam national ndb ndbcluster precision undefined unicode unsigned varying zerofill";

  CodeMirror.defineMIME("text/x-mysql", {
    name: "mysql",
    keywords: keywords(cKeywords),
    functions: keywords(cFunctions),
    types: keywords(cTypes),
    attributes: keywords(cAttributes)
  });
}());
