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

namespace ZCrawler\Login;

use Doctrine\Common\Cache\Cache;
use GuzzleHttp\Cookie\CookieJar;
use ZCrawler\Core\AbstractAPIBase;
use ZCrawler\Core\Exceptions\HttpException;
use ZCrawler\Core\Config;


class Login extends AbstractAPIBase
{
    /**
     * Student number
     *
     * @var string
     */
    private $username;

    /**
     * Student`s password
     *
     * @var string
     */
    private $password;

    /**
     * Login constructor.
     *
     * @param string     $username
     * @param string     $password
     * @param Cache|null $cache
     */
    public function __construct($username, $password, Cache $cache = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->cache = $cache;

        $this->setHeader('Referer', Config::get('http.base_uri'));
    }

    /**
     * Get the cookies for login.
     *
     * @param bool         $forceRefresh
     * @param string|array $method
     *
     * @return mixed
     */
    public function getCookie($forceRefresh = false, $method = 'noCode')
    {
        $cacheKey = $this->getCacheKey();
        $cookie = $this->getCache()->fetch($cacheKey);

        if ($forceRefresh || empty($cookie)) {
            if (is_array($method)) {
                $cookie = call_user_func_array([$this, $method['method']], $method['param']);
            } else {
                $cookie = call_user_func([$this, $method]);
            }

            $this->setCookie($cookie);
        }

        return $cookie;
    }

    /**
     * Set new cookies
     *
     * @param CookieJar $cookie
     *
     * @return bool
     */
    public function setCookie(CookieJar $cookie)
    {
        return $this->getCache()->save($this->getCacheKey(), $cookie, Config::get('cache.expire', 100));
    }

    /**
     * Login without the captcha.
     *
     * @return CookieJar
     * @throws HttpException
     */
    public function noCode()
    {
        $param['TextBox1'] = $this->username;
        $param['TextBox2'] = $this->password;
        $param['RadioButtonList1_2'] = '学生';
        $param['Button1'] = "";

        $url = Config::get('url.login_no_code');
        $cookie = $this->getCookieOrNew();
        $response = $this->request('post', [$url, $param, ['cookies' => $cookie]]);

        $body = (string)$response->getBody();
        // Check the request whether have any error
        if (preg_match("/err=4/", $body)) {
            throw new HttpException("account or password error", 10002);
        } elseif (!preg_match("/xs_main/", $body)) {
            throw new HttpException("login failed!", 10003);
        }

        return $cookie;
    }

    /**
     * Login without the captcha.
     *
     * @param string $code
     *
     * @return CookieJar
     */
    public function withCode($code)
    {
        $url = Config::get('url.login_with_code');
        $cookie = $this->getCookieOrNew();

        $param['__VIEWSTATE'] = true;
        $param['txtUserName'] = $this->username;
        $param['TextBox2'] = $this->password;
        $param['txtSecretCode'] = $code;
        $param['RadioButtonList1'] = '学生';
        $param['Button1'] = '';

        $response = $this->request('post', [$url, $param, ['cookies' => $cookie]]);
        $body = (string)$response->getBody();
        // Login success
        if (preg_match("/xs_main/", $body)) {
            return $cookie;
        }
        // Get the error
        $content = mb_convert_encoding($body, "utf-8", "gb2312");
        if (preg_match('/alert\((.*?)\)/', $content, $matches)) {
            throw new HttpException(trim($matches[1], '\'"'), 10004);
        }

        throw new HttpException("login failed!", 10003);
    }

    /**
     * Get the login captcha.
     *
     * @param string $codePath
     *
     * @return string
     */
    public function getCaptcha($path)
    {
        $url = Config::get('url.login_captcha');
        $cookie = new CookieJar();
        $codePath = $path . sha1($this->getUsername() . '-' . microtime(true)) . '.gif';
        $options = ['cookies' => $cookie, 'save_to' => $codePath];

        $response = $this->request('get', [$url, [], $options]);

        // Check the StatusCode
        if ($response->getStatusCode() == 200) {
            throw new HttpException('get the captcha failed!', 10005);
        }

        return $codePath;
    }

    /**
     * Get current cookies or new a cookies
     *
     * @return CookieJar
     */
    protected function getCookieOrNew()
    {
        return $this->getCache()->fetch($this->getCacheKey()) ?: new CookieJar();
    }

    /**
     * Get the student number
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get the student`s password
     *
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Register Guzzle middleware.
     */
    protected function registerHttpMiddleware()
    {
        // log
        $this->http->addMiddleware($this->logMiddleware());
        $this->http->addMiddleware($this->retryMiddleware());
    }
}
