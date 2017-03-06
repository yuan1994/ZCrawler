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
use Psr\Http\Message\ResponseInterface;
use ZCrawler\Core\Exceptions\DataInvalidException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use ZCrawler\Core\Config;
use ZCrawler\Login\Login;
use ZCrawler\Support\Log;
use ZCrawler\Support\Parser;

class AbstractAPI extends AbstractAPIBase
{
    /**
     * @var Login
     */
    protected $login;

    /**
     * AbstractAPI constructor.
     *
     * @param Login $login
     */
    public function __construct(Login $login)
    {
        $this->login = $login;

        // Set the request referrer
        $this->setHeader('Referer',
            Config::get('http.base_uri')
            . Config::get('url_prefix', '')
            . Config::get('url.main_page')
            .'?xh=' . $this->login->getUsername()
        );

        $this->registerHttpMiddleware();
    }

    /**
     * Register Guzzle middleware.
     */
    protected function registerHttpMiddleware()
    {
        // log
        $this->getHttp()->addMiddleware($this->logMiddleware());
        // retry
        $this->getHttp()->addMiddleware($this->retryMiddleware());
    }

    /**
     * Check the array data errors, and Throw exception when the contents contains error.
     *
     * @param ResponseInterface $response
     *
     */
    protected function checkAndThrow(ResponseInterface $response)
    {
//        if ($response->getStatusCode() >= 400) {
//            $this->errMsg = $response->getReasonPhrase();
//            $this->errCode = $response->getStatusCode();
//
//            return false;
//        }

        $body = (string) $response->getBody();

        return $body;
    }

    /**
     * Get the request Cookies
     *
     * @return CookieJar
     */
    public function getCookie()
    {
        return $this->login->getCookie();
    }

    /**
     * Set new cookies
     *
     * @param CookieJar $cookies
     *
     * @return bool
     */
    public function setCookie(CookieJar $cookies)
    {
        return $this->login->setCookie($cookies);
    }

    /**
     * Get the student number
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->login->getUsername();
    }
}
