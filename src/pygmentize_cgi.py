#!/usr/bin/env python

import cgi
import cgitb

cgitb.enable()

from pygments import highlight
from pygments.lexers import get_lexer_by_name 
from pygments.formatters import HtmlFormatter

import urllib2

def main():
    print "Content-type: text/html"
    print
    print "<html><head>"

    form = cgi.FieldStorage()

    lang = form.getvalue('lang')
    url = form.getvalue('url')

    lexer = get_lexer_by_name(lang)
    formatter = HtmlFormatter()
    code = urllib2.urlopen(url).read() 

    print "<style><!--"
    print formatter.get_style_defs('.highlight')
    print "--></style>"
    print "</head><body>"
    print highlight(code, lexer, formatter)
    print "</body></html>"
    print

if __name__ == "__main__":
    main()

