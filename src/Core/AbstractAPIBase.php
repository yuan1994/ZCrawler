<?php
/**
 * yuan1994/ZCrawler
 *
 * @author        yuan1994 <tianpian0805@gmail.com>
 * @link          https://github.com/yuan1994/ZCrawler
 * @documentation http://zcrawler.yuan1994.com
 * @copyright     2017 yuan1994 all rights reserved.
 * @license       http://www.apache.org/licenses/LICENSE-2.0
 */

namespace ZCrawler\Core;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ZCrawler\Core\Exceptions\HttpException;
use ZCrawler\Support\Log;
use ZCrawler\Support\Parser;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\Cache;

abstract class AbstractAPIBase
{
    /**
     * Guzzle http client
     *
     * @var Http
     */
    protected $http;

    /**
     * Cache handle
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Cache prefix
     *
     * @var string
     */
    protected $cachePrefix = 'yuan1994.ZCrawler.';

    /**
     * Cache key
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Get the request cookies
     *
     * @return CookieJar
     */
    abstract public function getCookie();

    /**
     * Set new cookies
     *
     * @param CookieJar $cookies
     *
     * @return bool
     */
    abstract public function setCookie(CookieJar $cookies);

    /**
     * Get the student number
     *
     * @return mixed
     */
    abstract public function getUserName();

    /**
     * @param Http $http
     *
     * @return $this
     */
    public function setHttp(Http $http)
    {
        $this->http = $http;

        return $this;
    }

    /**
     * @return Http
     */
    public function getHttp()
    {
        return $this->http ?: $this->http = Http::instance(Config::get('http', []));
    }

    /**
     * Send a request with some options
     *
     * @param string $method
     * @param array  $args
     *
     * @return ResponseInterface
     */
    protected function request($method, array $args)
    {
        $http = $this->getHttp();
        // If didn`t set $args then set $args to the empty array
        $args = array_replace(['', [], []], $args);
        // Set the cookies
        if (!isset($args[2]['cookies'])) {
            $args[2]['cookies'] = $this->getCookie();
            // Set the query string xh
            $args[2]['query']['xh'] = $this->getUserName();
        }
        // Set the proxy and cross the campus network vpn
        if (Config::get('proxy_status', false)) {
            $args[2]['proxy'] = Config::get('proxy', '');
        }
        // Set the request headers
        if ($headers = Config::get('http_headers')) {
            $args[2]['headers'] = $headers;
        }
        // Get the viewstate field
        if (isset($args[1]['__VIEWSTATE']) && true === $args[1]['__VIEWSTATE']) {
            $args[1]['__VIEWSTATE'] = $this->getViewsState($args[0]);
        }
        // Set the uri prefix for the cookies in url
        $args[0] = Config::get('param.url_prefix', '') . $args[0];
        // Try to get the response
        $response = call_user_func_array([$http, $method], $args);
        // Check whether need to request again with the url prefix
        $body = (string)$response->getBody();
        // The case of "Object moved to <a href='/(palpi545qhgbee55fvfekz45)/path.aspx" retry to new uri
        if (preg_match('/Object\ moved\ to\ <a\ href=[\'"]?\/(\(\w+\))/i', $body, $matches)) {
            // Set the new retry request`s uri
            $uriPrefix = $matches[1] . '/';
            Config::set('param.url_prefix', $uriPrefix);
            // Reset the request url path and request again
            $args[0] = $uriPrefix . $args[0];
            $response = call_user_func_array([$http, $method], $args);
        }

        return $response;
    }


    /**
     * Log the request.
     *
     * @return \Closure
     */
    protected function logMiddleware()
    {
        return Middleware::tap(function (RequestInterface $request, $options) {
            Log::debug("Request: {$request->getMethod()} {$request->getUri()} :" . json_encode($options, JSON_UNESCAPED_UNICODE));
            Log::debug('Request headers: ' . json_encode($request->getHeaders(), JSON_UNESCAPED_UNICODE));
        });
    }

    /**
     * Return retry middleware.
     *
     * @return \Closure
     */
    protected function retryMiddleware()
    {
        return Middleware::retry(function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null,
            RequestException $exception = null
        ) {
            // Max retry count is 3
            if ($retries > 2) {
                return false;
            }

            // Connection failed
            if ($exception instanceof ConnectException) {
                return true;
            }

            return false;
        });
    }

    /**
     * Get the param __VIEWSTATE
     *
     * @param string $url
     * @param bool   $force
     * @param string $selector
     *
     * @return string
     * @throws HttpException
     */
    protected function getViewsState($url = '', $force = false, $selector = '__VIEWSTATE')
    {
        $url = $url ?: Config::get('url.main_page');
        $cacheKey = 'view_state-' . $this->getUserName() . '-' . md5($url);
        if ($force || !$viewState = $this->getCache()->fetch($cacheKey)) {
            $response = $this->request('get', [$url]);

            if (!$response) {
                throw new HttpException('get the param v failed', 10001);
            }

            if (!$viewState = Parser::fieldName($response->getBody(), $selector)) {
                throw new HttpException('get the param v failed', 10001);
            }

            $this->getCache()->save($cacheKey, $viewState, Config::get('cache.expire', 100));
        }

        return $viewState;
    }

    /**
     * Get the Guzzle Client request headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return Config::get('http_headers', []);
    }

    /**
     * Set the Guzzle Client request header
     *
     * @param string            $name
     * @param string|null|array $value
     *
     * @return $this
     */
    public function setHeader($name, $value)
    {
        Config::set('http_headers.' . $name, $value);

        return $this;
    }

    /**
     * Unset the Guzzle Client request header
     *
     * @param string $name
     *
     * @return $this
     */
    public function deleteHeader($name)
    {
        Config::delete('http_headers.' . $name);

        return $this;
    }


    /**
     * Set cache instance.
     *
     * @param \Doctrine\Common\Cache\Cache $cache
     *
     * @return $this
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Return the cache manager.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache ?: $this->cache = new FilesystemCache(sys_get_temp_dir());
    }

    /**
     * Set the access token prefix.
     *
     * @param string $prefix
     *
     * @return $this
     */
    public function setCachePrefix($prefix)
    {
        $this->cachePrefix = $prefix;

        return $this;
    }

    /**
     * Set access token cache key.
     *
     * @param string $cacheKey
     *
     * @return $this
     */
    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;

        return $this;
    }

    /**
     * Get access token cache key.
     *
     * @return string $this->cacheKey
     */
    public function getCacheKey()
    {
        if (is_null($this->cacheKey)) {
            return $this->cachePrefix . $this->getUserName();
        }

        return $this->cacheKey;
    }
}
