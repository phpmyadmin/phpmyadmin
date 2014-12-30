CodeMirror.extendMode("sql", {
    commentStart: "/*",
    commentEnd: "*/",
    wordWrapChars: [";", "\\(", "\\)"],
    autoFormatLineBreaks: function (text) {
      return text.replace(/(;|\(|\))([^\r\n])/g, "$1\n$2");
    }
});
