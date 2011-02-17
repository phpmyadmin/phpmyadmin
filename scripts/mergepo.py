#!/usr/bin/python

import polib
import sys

po = polib.pofile(sys.argv[1])
poupdate = polib.pofile(sys.argv[2])

for origentry in po.fuzzy_entries():
    for updateentry in poupdate.translated_entries():
        if origentry.msgctxt is None and origentry.msgid == updateentry.msgid:
            origentry.msgstr = updateentry.msgstr
            origentry.flags.remove('fuzzy')
            break
        if origentry.msgctxt == updateentry.msgctxt and origentry.msgid == updateentry.msgid:
            origentry.msgstr = updateentry.msgstr
            origentry.flags.remove('fuzzy')
            break
for origentry in po.untranslated_entries():
    for updateentry in poupdate.translated_entries():
        if origentry.msgctxt is None and origentry.msgid == updateentry.msgid:
            origentry.msgstr = updateentry.msgstr
            break
        if origentry.msgctxt == updateentry.msgctxt and origentry.msgid == updateentry.msgid:
            origentry.msgstr = updateentry.msgstr
            break

po.save()

