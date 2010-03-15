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

po.save()

