#!/usr/bin/python
'''
Script to automatically merge pending translations from Pootle into po files.

It only accepts those, which are not translated or fuzzy.
'''

import polib
import sys

po = polib.pofile(sys.argv[1])
poupdate = polib.pofile(sys.argv[2])

for updateentry in poupdate:
    msgid = updateentry.msgid.split('\n', 1)[1]
    if msgid == updateentry.msgstr:
        continue
    origentry = po.find(msgid)
    if origentry is None:
        continue
    if origentry.msgstr == '' or 'fuzzy' in origentry.flags:
        origentry.msgstr = updateentry.msgstr
        try:
            origentry.msgstr_plural = updateentry.msgstr_plural
        except AttributeError:
            pass
        if 'fuzzy' in origentry.flags:
            origentry.flags.remove('fuzzy')

po.save()

