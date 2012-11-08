from bs4 import BeautifulSoup, NavigableString, Comment
import sys
import re
import textwrap

whitespace = re.compile('[ \r\n]+')

def format_content(tag, ignore_links = False, skip = ()):
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

        content = format_content(item)

        if item.name == 'a' and 'href' in item.attrs:
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
                out.append('`%s <%s>`_' % (content, href))
            continue
        if item.name == 'code':
            out.append('``%s``' % content)
            continue
        if item.name == 'strong' or (item.name == 'span' and 'class' in item.attrs and 'important' in item.attrs['class']):
            out.append('**%s**' % content)
            continue
        if item.name == 'em':
            out.append('*%s*' % content)
            continue
        if item.name == 'abbr':
            out.append(':abbr:`%s (%s)`' % (content, item.attrs['title']))
            continue
        if item.name == 'sup':
            out.append(':sup:`%s`' % content)
            continue
        if item.name == 'sub':
            out.append(':sub:`%s`' % content)
            continue

        print item.name
        print item.attrs
        raise Exception('Unknown tag')
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
                    print indent + '.. code-block:: none'
                    print
                    for line in tag.text.splitlines():
                        print indent + '    ', line.strip().encode('utf-8')
                    print

                    print
                elif item.name == 'p':
                    text = format_content(tag)
                    print textwrap.fill(text, initial_indent = indent).encode('utf-8')
                    print
        print

    elif tag.name == 'dl':
        for li in tag:
            # skip empty
            if isinstance(li, NavigableString) and li.string.strip() == '':
                continue

            # skip comments
            if isinstance(li, Comment):
                continue

            if li.name == 'dd':
                pass
            elif li.name == 'dt':
                pass
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
