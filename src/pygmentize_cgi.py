#!/usr/bin/env python
""" A python CGI serving as a syntax highlighting engine. Based on Pygments.
Input parameters:

- url       The resource which provides the code to highlight
- lang      The language of the code (python, css, etc.). See Pygments
            documentation for all supported languages.
- linenos   Whether to turn line numbers on or off. 

Output is formatted in HTML. Styles are included. This is likely to change with
future versions.

You can customize the CGI's behaviour by editing a configuration file. Its name
is 'pygmentize_cgi.cfg' and it should be located under the same directory where
this CGI resides. Valid sections and properties are listed in the following using
a pseudo-notation. For default values, see the code.

[security]
# If true the script is executed only if REMOTE_ADDR and
# SERVER_ADDR match.
allowRemoteAcess = True | False
# If specified only URLs that match a whitelist regular expression
# are allowed as input for the highlighter. Empty string allows
# everything. Allows any URLs by default. You might change it to
# something like file:///var/www/files/([a-zA-Z0-9_i\\-]+(\\.[a-zA-Z0-9_\\-]+)*\\.?/?)*
whitelistRegex = <REGEX>
"""

import cgi
import cgitb
cgitb.enable()

from pygments import highlight
from pygments.lexers import get_lexer_by_name
from pygments.formatters import HtmlFormatter

import urllib2
import os
import re
import ConfigParser

class _SecurityException(Exception):
    def __init__(self, msg):
        self.msg = msg

def _read_cfg():
    cfg = ConfigParser.SafeConfigParser({
        'allowRemoteAccess' : 'False',
        'whitelistRegex' : ''
    })
    cfg.add_section('security')
    cfg.read('pygmentize_cgi.cfg')
    return cfg

def _security_check(cfg, url):
    allowRemoteAccess = cfg.getboolean('security', 'allowRemoteAccess')
    whitelistRegex = cfg.get('security', 'whitelistRegex')
    if not allowRemoteAccess:
        if os.environ['REMOTE_ADDR'] != os.environ['SERVER_ADDR']:
            raise SecurityException('Error: Remote address must match server address.')
    if not whitelistRegex == '':
        m = re.match(whitelistRegex, url)
        if not m or m.group(0) != url:
            raise _SecurityException('Error: Illegal URL, not matched by whitelist RE.')

def main():
    cfg = _read_cfg()
    form = cgi.FieldStorage()
    lang = form.getvalue('lang')
    url = form.getvalue('url')
    css = form.getvalue('css', 'true')
    linenos = form.getvalue('linenos')

    code = urllib2.urlopen(url).read()
    lexer = get_lexer_by_name(lang)
    formatter = HtmlFormatter(linenos=linenos)
    print 'Content-type: text/html'
    print

    _security_check(cfg, url)
    if not css == 'false':
        print '<style><!--'
        print formatter.get_style_defs('.highlight')
        print '--></style>'
    print
    print highlight(code, lexer, formatter)
    print

if __name__ == '__main__':
    main()

