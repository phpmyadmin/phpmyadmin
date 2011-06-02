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
      return ret(ch);
    // start of a number value?
    else if (/\d/.test(ch)) {
      stream.eatWhile(/[\w\.]/)
      return ret("number", "mysql-number");
    }
    // multi line comment or simple operator?
    else if (ch == "/") {
      if (stream.eat("*")) {
        return chain(stream, state, tokenComment);
      }
      else {
        stream.eatWhile(isOperatorChar);
        return ret("operator", "mysql-operator");
      }
    }
    // single line comment or simple operator?
    else if (ch == "-") {
      if (stream.eat("-")) {
        stream.skipToEnd();
        return ret("comment", "mysql-comment");
      }
      else {
        stream.eatWhile(isOperatorChar);
        return ret("operator", "mysql-operator");
      }
    }
    // pl/sql variable?
    else if (ch == "@" || ch == "$") {
      stream.eatWhile(/[\w\d\$_]/);
      return ret("word", "mysql-var");
    }
    // is it a operator?
    else if (isOperatorChar.test(ch)) {
      stream.eatWhile(isOperatorChar);
      return ret("operator", "mysql-operator");
    }
    else {
      // get the whole word
      stream.eatWhile(/[\w\$_]/);
      // is it one of the listed keywords?
      if (keywords && keywords.propertyIsEnumerable(stream.current().toLowerCase())) return ret("keyword", "mysql-keyword");
      // is it one of the listed functions?
      if (functions && functions.propertyIsEnumerable(stream.current().toLowerCase())) return ret("keyword", "mysql-function");
      // is it one of the listed types?
      if (types && types.propertyIsEnumerable(stream.current().toLowerCase())) return ret("keyword", "mysql-type");
      // is it one of the listed attributes?
      if (attributes && attributes.propertyIsEnumerable(stream.current().toLowerCase())) return ret("keyword", "mysql-attribute");
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
        indented: 0,
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
  var cKeywords = "accessible action add after against aggregate algorithm all alter analyse analyze and as asc autocommit auto_increment avg_row_length backup begin between binlog both by cascade case change changed charset check checksum collate collation column columns comment commit committed compressed concurrent constraint contains convert create cross current_timestamp database databases day day_hour day_minute day_second definer delayed delay_key_write delete desc describe deterministic distinct distinctrow div do drop dumpfile duplicate dynamic else enclosed end engine engines escape escaped events execute exists explain extended fast fields file first fixed flush for force foreign from full fulltext function gemini gemini_spin_retries global grant grants group having heap high_priority hosts hour hour_minute hour_second identified if ignore in index indexes infile inner insert insert_id insert_method interval into invoker is isolation join key keys kill last_insert_id leading left like limit linear lines load local lock locks logs low_priority maria master master_connect_retry master_host master_log_file master_log_pos master_password master_port master_user match max_connections_per_hour max_queries_per_hour max_rows max_updates_per_hour max_user_connections medium merge minute minute_second min_rows mode modify month mrg_myisam myisam names natural no not null offset on open optimize option optionally or order outer outfile pack_keys page page_checksum partial partition partitions password primary privileges procedure process processlist purge quick raid0 raid_chunks raid_chunksize raid_type range read read_only read_write references regexp reload rename repair repeatable replace replication reset restore restrict return returns revoke right rlike rollback row rows row_format second security select separator serializable session share show shutdown slave soname sounds sql sql_auto_is_null sql_big_result sql_big_selects sql_big_tables sql_buffer_result sql_cache sql_calc_found_rows sql_log_bin sql_log_off sql_log_update sql_low_priority_updates sql_max_join_size sql_no_cache sql_quote_show_create sql_safe_updates sql_select_limit sql_slave_skip_counter sql_small_result sql_warnings start starting status stop storage straight_join string striped super table tables temporary terminated then to trailing transactional truncate type types uncommitted union unique unlock update usage use using values variables view when where with work write xor year_month";

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
