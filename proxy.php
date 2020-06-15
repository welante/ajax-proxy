<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

/**
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * FILE DOCUMENTATION
 *
 * More extensive documentation is located in README.markdown, which is
 *  contained in this package.
 *
 * This file contains a class and script to execute a proxy requests from AJAX
 *  scripts. If this file was live on a server at example.com/proxy.php, and we
 *  wanted to make AJAX requests to subdomain.example.com/other/resource, we'd:
 *      1. Add lines at the bottom of this file to say
 *          $proxy = new AjaxProxy('http://subdomain.example.com');
 *          $proxy->execute();
 *      2. From our javascript, make requests to
 *          http://example.com/proxy.php?route=/other/resource
 * The heart of the functionality of this script is self-contained, reusable
 *  proxy class. This class could very easily be incorporated into an MVC
 *  framework or set of libraries.
 *
 * @todo Finalize licensing above
 * @package IceCube
 * @copyright Copyright (c) 2010 HUGE LLC (http://hugeinc.com)
 * @license New BSD License
 * @author Kenny Katzgrau <kkatzgrau@hugeinc.com>
 * @version 1.0.0
 */

/**
 * This class handles all of the functionality of the proxy server. The only
 *  public method is AjaxProxy::execute(), so once the class is constructed, the
 *  only option is to execute the proxy request. It will throw exceptions when
 *  something isn't right, the message of which will be dumped to the output
 *  stream.
 *
 * There is an option to restrict requests so that they can only be made from
 *  certain hostnames or ips in the constructor
 *
 * @author Kenny Katzgrau <kkatzgrau@hugeinc.com>
 */
class AjaxProxy
{
    const REQUEST_METHOD_POST    = 1;
    const REQUEST_METHOD_GET     = 2;
    const REQUEST_METHOD_PUT     = 3;
    const REQUEST_METHOD_DELETE  = 4;

    /**
     * Will hold the host where proxy requests will be forwarded to
     * @var string
     */
    protected $_forwardHost       = NULL;

    /**
     * Will hold the HTTP request method of the proxy request
     * @var string
     */
    protected $_requestMethod     = NULL;

    /**
     * Will hold the body of the request submitted by the client
     * @var string
     */
    protected $_requestBody       = NULL;

    /**
     * Will hold the user-agent string submitted by the client
     * @var string
     */
    protected $_requestUserAgent  = NULL;

    /**
     * Will hold the response body sent back by the server that the proxy
     *  request was made to
     * @var string
     */
    protected $_responseBody      = NULL;

    /**
     * Will hold parsed HTTP headers sent back by the server that the proxy
     *  request was made to in key-value form
     * @var array
     */
    protected $_responseHeaders   = NULL;

    /**
     * Will hold headers in key-value array form that were sent by the client
     * @var array
     */
    protected $_rawHeaders        = NULL;

    /**
     * Will hold the route for the proxy request submitted by the client in
     *  the query string's 'route' parameter
     * @var string
     */
    protected $_route             = NULL;

    /**
     * Initializes the Proxy object
     *
     * @param string $forward_host The base address that all requests will be
     *  forwarded to. Must not end in a trailing slash.
     *
     */
    public function  __construct($forward_host)
    {
        $this->_forwardHost = $forward_host;
    }

    /**
     * Execute the proxy request. This method sets HTTP headers and write to the
     *  output stream. Make sure that no whitespace or headers have already been
     *  sent.
     */
    public function execute()
    {
            $this->_gatherRequestInfo();
            $this->_makeCurlRequest($this->_forwardHost . $this->_route);
            $this->_parseResponseData();
            $this->_buildAndExecuteProxyResponse();
    }

    /**
     * Return the string form of the request method constant
     * @param int $type A request method type constant, like
     *  self::REQUEST_METHOD_POST
     * @return string The string form of the passed constant, like POST
     */
    protected static function _getStringFromRequestType($type)
    {
        $name = '';

        if($type === self::REQUEST_METHOD_POST)
            $name = "POST";
        elseif($type === self::REQUEST_METHOD_GET)
            $name = "GET";
        elseif($type === self::REQUEST_METHOD_PUT)
            $name = "PUT";
        elseif($type === self::REQUEST_METHOD_DELETE)
            $name = "DELETE";
        else
            throw new Exception("Unknown request method constant ($type) passed as a parameter");

        return $name;
    }

    /**
     * Gather any information we need about the request and
     *  store them in the class properties
     */
    protected function _gatherRequestInfo()
    {
        $this->_loadRequestMethod();
        $this->_loadRawHeaders();
        $this->_loadRoute();

        if($this->_requestMethod === self::REQUEST_METHOD_POST
            || $this->_requestMethod === self::REQUEST_METHOD_PUT)
        {
            $this->_loadRequestBody();
        }
    }

    /**
     * Get the path to where the request will be made. This will be prepended
     *  by PROXY_HOST
     * @throws Exception When there is no 'route' parameter
     */
    protected function _loadRoute()
    {
        if(!key_exists('route', $_GET)) {
            throw new Exception("You must supply a 'route' parameter in the request");
        }

        $this->_route = $_GET['route'];
    }

    /**
     * Get the request body raw from the PHP input stream and store it in the
     *  _requestBody property.
     *
     * There have been concerns with blindly reading the entirety of an input
     *  stream with no maximum length, but this is limited with the maximum
     *  request size in php.ini. Additionally, Zend_Amf_Request_Http does the
     *  same.
     *
     */
    protected function _loadRequestBody()
    {
        $this->_requestBody = @file_get_contents('php://input');
    }

    /**
     * Examine the request and load the HTTP request method
     *  into the _requestMethod property
     * @throws Exception When there is no request method
     */
    protected function _loadRequestMethod()
    {
        if($this->_requestMethod !== NULL) return;

        if(! key_exists('REQUEST_METHOD', $_SERVER))
            throw new Exception("Request method unknown");

        $method = strtolower($_SERVER['REQUEST_METHOD']);

        if($method == "get")
            $this->_requestMethod = self::REQUEST_METHOD_GET;
        elseif($method == "post")
            $this->_requestMethod = self::REQUEST_METHOD_POST;
        elseif($method == "put")
            $this->_requestMethod = self::REQUEST_METHOD_PUT;
        elseif($method == "delete")
            $this->_requestMethod = self::REQUEST_METHOD_DELETE;
        else
            throw new Exception("Request method ($method) invalid");
    }

    /**
     * Fetch all HTTP request headers
     * @return array of all HTTP headers
     */
    protected function _getallheaders()
    {
        if (!function_exists('getallheaders'))
        {
           $headers = '';
           foreach ($_SERVER as $name => $value)
           {
               if (substr($name, 0, 5) == 'HTTP_')
               {
                   $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
               }
           }
           return $headers;
        }
        else
        {
            return getallheaders();
        }
    }

    /**
     * Load raw headers into the _rawHeaders property.
     *  This method REQUIRES APACHE
     * @throws Exception When we can't load request headers (perhaps when Apache
     *  isn't being used)
     */
    protected function _loadRawHeaders()
    {
        if($this->_rawHeaders !== NULL) return;

        $this->_rawHeaders = $this->_getallheaders();

        if($this->_rawHeaders === FALSE)
            throw new Exception("Could not get request headers");
    }

    private function isJson($string)
    {
        json_decode($string, true);
        return json_last_error() == JSON_ERROR_NONE;
    }

    /**
     * Given the object's current settings, make a request to the given url
     *  using the cURL library
     * @param string $url The url to make the request to
     * @return string The full HTTP response
     */
    protected function _makeCurlRequest($url)
    {
        $curl_handle = curl_init($url);

        /**
         * Check to see if this is a POST request
         */
        if($this->_requestMethod === self::REQUEST_METHOD_POST)
        {
            curl_setopt($curl_handle, CURLOPT_POST, true);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $this->_requestBody);
        }
        if($this->_requestMethod === self::REQUEST_METHOD_PUT)
        {
            // Using a PUT method i.e. -XPUT
            curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ( $this->_requestBody )
            {
                $requestquery = $this->isJson($this->_requestBody) ? http_build_query(json_decode($this->_requestBody, true)) : $this->_requestBody;
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $requestquery);
            }
        }
        if($this->_requestMethod === self::REQUEST_METHOD_DELETE)
        {
            curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
        // don't log in apache error log
        curl_setopt($curl_handle, CURLOPT_VERBOSE, false);
        curl_setopt($curl_handle, CURLOPT_HEADER, true);

        curl_setopt($curl_handle, CURLOPT_COOKIE, $this->_buildProxyRequestCookieString());

        $headers = $this->_generateProxyRequestHeaders();

        $headers[] = 'REMOTE_ADDR: ' . getenv('REMOTE_ADDR');
        $headers[] = 'X-Forwarded-For: ' . getenv('REMOTE_ADDR');
        $headers[] = 'X-app-ip: ' . getenv('REMOTE_ADDR');
        $headers[] = 'X-app-hostname: ' . getenv('HTTP_HOST');
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);

        $ret = curl_exec($curl_handle);

        // get headers and body
        $header_size = curl_getinfo($curl_handle, CURLINFO_HEADER_SIZE);
        curl_close($curl_handle);
        $respheaders = substr($ret, 0, $header_size);
        $content = substr($ret, $header_size);

        $this->_responseHeaders = $respheaders;
        $this->_responseBody = $content;

        return $ret;
    }

    /**
     * Given an associative array returned by PHP's methods to get stream meta,
     *  extract the HTTP response header from it
     * @param array $meta The associative array contianing stream information
     * @return array
     */
    protected function _buildResponseHeaderFromMeta($meta)
    {
        if(! array_key_exists('wrapper_data', $meta))
            throw new Exception("Did not receive a valid response from the server");

        $headers = $meta['wrapper_data'];

        /**
         * When using stream_context_create, if the socket is redirected via a
         *  302, PHP just adds the 302 headers onto the wrapper_data array
         *  in addition to the headers from the redirected page. We only
         *  want the redirected page's headers.
         */
        $last_status = 0;
        for($i = 0; $i < count($headers); $i++)
        {
            if(strpos($headers[$i], 'HTTP/') === 0)
            {
                $last_status = $i;
            }
        }

        # Get the applicable portion of the headers
        $headers = array_slice($headers, $last_status);

        return implode("\n", $headers);
    }

    protected function _parseResponseData()
    {
        $parsed = array();
        foreach(explode("\r\n", $this->_responseHeaders) as $header)
        {
            $field_end = strpos($header, ':');

            if($field_end === FALSE && $header)
            {
                /* Cover the case where we're at the first header, the HTTP
                 *  status header
                 */
                $field = 'status';
                $value = $header;

                preg_match("/^HTTP.+ (?P<status>\d{3})/i", $header, $matches);
                if ( isset($matches['status']) && is_numeric($matches['status']) )
                {
                    // set HTTP Status
                    http_response_code(intval($matches['status']));
                    $parsed[$field] = trim($matches[0]);
                }
            }
            else
            {
                $field = substr($header, 0, $field_end);
                $value = substr($header, $field_end + 1);
                if ( $field && $value )
                {
                    $parsed[$field] = trim($value);
                }
            }
        }

        if ( isset($parsed['Transfer-Encoding']) ) {
            unset($parsed['Transfer-Encoding']);
        }

        $this->_responseHeaders = $parsed;
    }

    /**
     * Generate and return any headers needed to make the proxy request
     * @param  bool $as_string Whether to return the headers as a string instead
     *  of an associative array
     * @return array|string
     */
    protected function _generateProxyRequestHeaders()
    {
        $this->_loadRawHeaders();

        $headers = array();
        $ignores = [
            'Host',
            'Connection',
            'Upgrade-Insecure-Requests',
        ];
        foreach ( $this->_rawHeaders as $key => $value ) {
            if ( in_array($key, $ignores) ) {
                continue;
            }
            $headers[] = $key . ': ' . $value;
        }

        return $headers;
    }

    /**
     * From the global $_COOKIE array, rebuild the cookie string for the proxy
     *  request
     * @return string
     */
    protected function _buildProxyRequestCookieString()
    {
        $this->_loadRawHeaders();

        if(key_exists('Cookie', $this->_rawHeaders)) {
            $cookie = $this->_rawHeaders['Cookie'];
            unset($this->_rawHeaders['Cookie']);
            return $cookie;
        }

        return '';
    }

    /**
     * Generate headers to send back to the broswer/client based on what the
     *  server sent back
     */
    protected function _generateProxyResponseHeaders()
    {
        foreach($this->_responseHeaders as $name => $value)
        {
            if($name != 'status' && $name != 'Content-Length')
                header("$name: $value");
        }
    }

    /**
     * Generate the headers and send the final response to the output stream
     */
    protected function _buildAndExecuteProxyResponse()
    {
        $this->_generateProxyResponseHeaders();

        echo $this->_responseBody;
    }
}
