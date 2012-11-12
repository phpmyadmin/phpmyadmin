from bs4 import BeautifulSoup, NavigableString, Comment
import sys
import re
import textwrap

whitespace = re.compile('[ \r\n]+')

def get_id_from_cfg(text):
    '''
    Formats anchor ID from config option.
    '''
    if text[:6] == '$cfg[\'':
        text = text[6:]
    if text[-2:] == '\']':
        text = text[:-2]
    text = text.replace('[$i]', '')
    parts = text.split("']['")
    return 'cfg_%s' % '_'.join(parts)

def format_content(tag, ignore_links = False, skip = (), document_mode = False):
    '''
    Parses inline html content.
    '''
    out = []
    for item in tag:
        if isinstance(item, NavigableString):
            text = whitespace.sub(' ', item.string)
            if text != '':
                out.append(text.replace('*', '\\*').replace('_', '\\_'))
            continue

        # skip comments
        if isinstance(item, Comment):
            continue

        # skip breaks, they are mostly invalid anyway
        if item.name == 'br':
            continue

        # skip images
        if item.name == 'img':
            continue

        if item.name in skip:
            continue

        if item.name == 'a' and 'href' in item.attrs:
            content = format_content(item)
            if ignore_links:
                out.append(content)
                continue
            href = item.attrs['href']
            if href[0] == '#':
                if content == 'details' or 'see' in content:
                    out.append('see :ref:`%s`' % href[1:])
                elif 'FAQ' in content:
                    out.append(':ref:`%s`' % href[1:])
                else:
                    out.append(':ref:`%s`' % href[1:])
            else:
                out.append('`%s <%s>`_' % (content, href))
            continue
        if item.name == 'code':
            out.append('``%s``' % format_content(item))
            continue
        if item.name == 'strong' or (item.name == 'span' and 'class' in item.attrs and 'important' in item.attrs['class']):
            out.append('**%s**' % format_content(item))
            continue
        if item.name == 'em':
            out.append('*%s*' % format_content(item))
            continue
        if item.name == 'abbr':
            out.append(':abbr:`%s (%s)`' % (format_content(item), item.attrs['title']))
            continue
        if item.name == 'sup':
            out.append(':sup:`%s`' % format_content(item))
            continue
        if item.name == 'sub':
            out.append(':sub:`%s`' % format_content(item))
            continue
        if item.name == 'span':
            out.append(format_content(item))
            continue

        if document_mode:
            print textwrap.fill(''.join(out).strip()).encode('utf-8')
            print
            out = []
            parse_block(item)
            continue

        print item.name
        print item.attrs
        raise Exception('Unknown tag')
    if document_mode:
        print textwrap.fill(''.join(out).strip()).encode('utf-8')
        print
    ret = ''.join(out)
    return ret.strip()

def print_id(tag):
    tagid = tag.get('id')
    if tagid is not None:
        print '.. _%s:' % tagid
    print

def parse_block(tag):
    '''
    Parses block tag.
    '''
    if tag.name == 'h2':
        sys.stdout.close()
        sys.stdout = open('%s.rst' % tag.get('id'), 'w')
        print_id(tag)
        print tag.text
        print '=' * len(tag.text)
        print
    elif tag.name == 'h3':
        print_id(tag)
        print tag.text
        print '+' * len(tag.text)
        print
    elif tag.name in ('h4', 'h5'):
        print_id(tag)
        text = format_content(tag, True)
        print text.encode('utf-8')
        print '-' * len(text)
        print
    elif tag.name == 'p':
        text = format_content(tag)
        print textwrap.fill(text).encode('utf-8')
        print
    elif tag.name in ('ul', 'ol'):
        if tag.name == 'ul':
            header = '*'
        else:
            header = '#.'
        for li in tag:
            # skip empty
            if isinstance(li, NavigableString) and li.string.strip() == '':
                continue

            # skip comments
            if isinstance(li, Comment):
                continue

            if li.name != 'li':
                raise Exception('UL contains %s' % li.name)
            text = format_content(li, skip = ('ul', 'li', 'pre', 'p'))
            print header,
            indent = ' ' * (len(header) + 1)
            joiner = '\n%s' % indent
            print joiner.join(textwrap.wrap(text)).encode('utf-8')
            for item in li:
                if isinstance(item, NavigableString):
                    # Already handle above
                    continue
                if item.name == 'ul':
                    print
                    for lii in item:
                        if isinstance(lii, NavigableString) and lii.string.strip() == '':
                            continue
                        if lii.name != 'li':
                            raise Exception('UL contains %s' % lii.name)
                        text = format_content(lii)
                        print indent + '*',
                        joiner = '\n%s  ' % indent
                        print joiner.join(textwrap.wrap(text)).encode('utf-8')
                        print
                elif item.name == 'pre':
                    print
                    print indent + '.. code-block:: none'
                    print
                    for line in item.text.splitlines():
                        print indent + '    ', line.strip().encode('utf-8')
                    print

                    print
                elif item.name == 'p':
                    text = format_content(item)
                    print textwrap.fill(text, initial_indent = indent).encode('utf-8')
                    print
        print

    elif tag.name == 'dl':
        cfg = False
        for li in tag:
            # skip empty
            if isinstance(li, NavigableString) and li.string.strip() == '':
                continue

            # skip comments
            if isinstance(li, Comment):
                continue

            if li.name == 'dt':
                dt_id = li.get('id')
                cfg = dt_id is not None and ('cfg' in dt_id or 'servers' in dt_id or 'control' in dt_id or 'bookmark' in dt_id or 'table' in dt_id or 'pmadb' in dt_id or 'relation' in dt_id or 'col_com' in dt_id or 'history' in dt_id or 'recent' in dt_id or 'tracking' in dt_id or 'designer' in dt_id or 'Arbitrary' in dt_id or 'userconfig' in dt_id)
                if cfg:
                    # Extract all IDs
                    ids = [dt_id]
                    for subtag in li:
                        if not isinstance(subtag, NavigableString) and subtag.get('id') is not None:
                            ids.append(subtag.get('id'))
                else:
                    # Print all IDs
                    print_id(li)
                    for subtag in li:
                        if not isinstance(subtag, NavigableString) and subtag.get('id') is not None:
                            print_id(subtag)
                # Extract text
                if cfg:
                    options = []
                    text = ''
                    for subtag in li:
                        if isinstance(subtag, NavigableString):
                            text += subtag.string
                        elif subtag.name == 'span':
                            text += subtag.text
                        elif subtag.name == 'br':
                            options.append(text)
                            text = ''
                    if text != '':
                        options.append(text)
                    ids = set(ids)
                    config_options = []
                    for option in options:
                        if option.strip() == '':
                            continue
                        try:
                            optname, opttype = option.split(' ', 1)
                        except:
                            optname = option
                            opttype = ''
                        optname = optname.strip()
                        opttype = opttype.strip()
                        config_options.append((optname, opttype))
                        newid = get_id_from_cfg(optname)
                        if newid in ids:
                            ids.remove(newid)

                    for anchor in ids:
                        print '.. _%s:' % anchor

                    for optname, opttype in config_options:
                        print '.. config:option:: %s' % optname
                        print
                        print '    :type: %s' % opttype
                        print '    :default:'
                        print
                else:
                    text = format_content(li).encode('utf-8')
                    print text
                    print '-' * len(text)
                    print
            elif li.name == 'dd':
                format_content(li, document_mode = True)
            else:
                print li.name
                print li.attrs
                raise Exception('Unknown tag')
    elif tag.name == 'pre':
        print '.. code-block:: none'
        print
        for line in tag.text.splitlines():
            print '   ', line.strip().encode('utf-8')
        print
    else:
        print tag.name
        print tag.attrs
        raise Exception('Unknown tag')


s = BeautifulSoup(file(sys.argv[1]).read())

for tag in s.html.body.find(id = 'body'):

    # skip empty
    if isinstance(tag, NavigableString) and tag.string.strip() == '':
        continue

    # skip comments
    if isinstance(tag, Comment):
        continue

    parse_block(tag)
