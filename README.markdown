# welante ajax-proxy

An extensively easy-to-use proxy class and script for facilitating cross-domain
ajax calls that **supports cookies** and has minimal dependencies
(**works without cURL!**)

Written and maintained by [Kenny Katzgrau](http://codefury.net) @ [HUGE](http://hugeinc.com)

## Overview

This package is a PHP class and script to proxy AJAX request accross domains.
It is intended to work around the cross-domain request restrictions found in
most browsers.

This proxy does not simply forward request and response bodies, it also forwards
cookies, user-agent strings, and content-types. This makes is extensively useful
for performing operations like ajax-based logins accross domains (which usually
rely on cookies).

Additionally, it will use cURL by default, and fall back to using the slower,
but native fopen() functionality when cURL isn't available.

The class' functionality is encapsulated in a single, standalone class, so
incorporation into larger applications or frameworks can be easily done. It may
also be used alone.

## Scenario

Suppose we were writing the client-side portion of the customers website www.example.com. If the site
creates ajax-based requests to welante eg. at subdoamin.example.com, then we're going to have trouble
making requests work accross domains.

This is where the proxy comes in. We want to have ajax requests sent to
www.example.com, and then routed, or 'proxied' to subdomain.example.com

## Setup

In this package, proxy.php is the standalone class and script. We would
place this script at a location on www.example.com, perhaps at
www.example.com/welante-api/proxy.php together with .htaccess and index.php

In index.php, there are is a variable $subdomain. Enter the welante subdomain there
so the proxy creates the correct calls.

## Dependencies

This class can use two different methods to make it's requests: cURL, and the
native fopen(). Since cURL is known to be quite a bit faster, the class first
checks for the availability of cURL, and will fallback to fopen() if needed.

At least one of these must be true:

1. fopen() requests are enabled via the `allow_url_fopen` option in
   php.ini. For more on that: [see the php manual](http://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen)
2. cURL is installed and loaded as a PHP module. The official docs are 
   [here](http://php.net/manual/en/book.curl.php),
   but sane people will probably do something like `$ sudo apt-get install php5-curl`

## Why Use ajax-proxy?

Okay, an ajax proxy isn't the most complicated bit to write. Most client-side
developers have had to write one before, and there are a number of examples on
the internet.

But proxy examples found online rarely handle the passing of cookies, and
almost every single one that is written in PHP uses cURL. If you are on a shared
host, and cURL isn't enabled, you're out of luck. But with ajax-proxy, both the
passing of headers and a fallback to fopen (non-cURL) are incorporated.
Additionally, it was written to be reused and extended.

ajax-proxy it written as a standalone class which can be used by itself or
incorporated into a larger framework. Accordingly, there are constructor options
to handle it's own errors and exceptions (standalone) or let the errors and
exceptions bubble up to the application.

Writing a proxy tends to be a quick-and-dirty thing that most client-side
developers don't want to spend more than a few hours writing. If you need cookie
or non-cURL support, some extra time will be tacked on for handling some of the
unexpected bugs and nuances of HTTP. ajax-proxy underwent 2 weeks of part-time
development with numerous feature additions and code reviews. Additionally, it's
fully documented with PHPDoc.

With ajax-proxy, you'll get a more powerful, rock-solid proxy and less time than
it would have taken you to write a basic one yourself.

Oh, and it's incredibly easy to use.

## Special Thanks

A special thanks several key players on the HUGE team that offered valuable
feedback, feature suggestions, and/or their time in a code review:

* Brett Mayen @ NY
* Daryl Bowden @ LA
* [Sankho Mallik](http://sankhomallik.com/) @ NY
* Martin Olsen @ NY
* [Patrick O'Neill](http://misteroneill.com/) @ NY
* [Tim McDuffie](http://www.tmcduffie.com/) @ NY
* [Sean O'Connor](http://seanoc.com) @ NY
* John Grogan @ NY

## Maintainer

Maintained by [Kenny Katzgrau](http://codefury.net) @ [HUGE](http://hugeinc.com)

## License

Copyright (c) 2010, HUGE LLC
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice,
  this list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

* Neither the name of HUGE LLC nor the names of its
  contributors may be used to endorse or promote products derived from this
  software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.