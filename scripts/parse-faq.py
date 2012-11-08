from bs4 import BeautifulSoup, NavigableString, Comment
import sys
import re
import textwrap

whitespace = re.compile('[ \r\n]+')

def format_text(text):
    '''
    Preprocess text to be rst friendly.
    '''
    return whitespace.sub(' ', text).strip().replace('*', '\\*')

print 'Frequently asked questions'
print '=========================='
print

s = BeautifulSoup(file(sys.argv[1]).read())

for tag in s.html.body:

    # skip empty
    if isinstance(tag, NavigableString) and tag.string.strip() == '':
        continue

    # skip comments
    if isinstance(tag, Comment):
        continue

    if tag.name == 'h3':
        print '.. _%s:' % tag.get('id').replace('faq', 'faq_')
        print
        print tag.text
        print '+' * len(tag.text)
        print
    elif tag.name in ('h4', 'h5'):
        print '.. _%s:' % tag.get('id').replace('faq', 'faq_')
        print
        text = format_text(tag.text)
        print text.encode('utf-8')
        print '-' * len(text)
        print
    elif tag.name == 'p':
        text = format_text(tag.text)
        print textwrap.fill(text).encode('utf-8')
        print
    elif tag.name in ('ul', 'ol'):
        for li in tag:
            # skip empty
            if isinstance(li, NavigableString) and li.string.strip() == '':
                continue

            # skip comments
            if isinstance(li, Comment):
                continue

            if li.name != 'li':
                raise Exception('UL contains %s' % li.name)
            text = format_text(li.text)
            if tag.name == 'ul':
                print '*',
                print '\n  '.join(textwrap.wrap(text)).encode('utf-8')
            else:
                print '#.',
                print '\n   '.join(textwrap.wrap(text)).encode('utf-8')
        print
    elif tag.name == 'dl':
        pass
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

