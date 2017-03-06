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

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use ZCrawler\Core\Exceptions\HttpException;
use ZCrawler\Support\Log;
use GuzzleHttp\HandlerStack;

class Http
{
    /*
     * the instance
     *
     * @var Http
     */
    protected static $instance;

    /**
     * Client
     *
     * @var Client
     */
    protected $client;

    /**
     * middlewares
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Guzzle client default settings.
     *
     * @var array
     */
    protected static $defaults = [];

    /**
     * new instance
     *
     * @param array $config
     *
     * @return static
     */
    public static function instance($config = [])
    {
        if (null === self::$instance) {
            self::$instance = new static($config);
        }

        return self::$instance;
    }

    /**
     * Set guzzle default settings.
     *
     * @param array $defaults
     */
    public static function setDefaultOptions($defaults = [])
    {
        self::$defaults = $defaults;
    }

    /**
     * Return current guzzle default settings.
     *
     * @return array
     */
    public static function getDefaultOptions()
    {
        return self::$defaults;
    }

    /**
     * Http constructor.
     * @param array $defaults
     */
    public function __construct($defaults = [])
    {
        self::setDefaultOptions($defaults);
    }

    /**
     * GET request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return ResponseInterface
     *
     * @throws HttpException
     */
    public function get($url, array $params = [], array $options = [])
    {
        return $this->request($url, 'GET', ['query' => $params], $options);
    }

    /**
     * POST request.
     *
     * @param string $url
     * @param array|string $params
     * @param array $options
     *
     * @return ResponseInterface
     *
     * @throws HttpException
     */
    public function post($url, $params = [], array $options = [])
    {
        $key = is_array($params) ? 'form_params' : 'body';

        return $this->request($url, 'POST', [$key => $params], $options);
    }

    /**
     * JSON request.
     *
     * @param string $url
     * @param string|array $params
     * @param array $options
     * @param int $encodeOption
     *
     * @return ResponseInterface
     *
     * @throws HttpException
     */
    public function json($url, $params = [], array $options, $encodeOption = JSON_UNESCAPED_UNICODE)
    {
        is_array($params) && $params = json_encode($params, $encodeOption);

        return $this->request($url, 'POST', ['body' => $options, 'headers' => ['content-type' => 'application/json']], $options);
    }

    /**
     * Upload file.
     *
     * @param string $url
     * @param array $form
     * @param array $options
     * @param array $files
     * @param array $queries
     *
     * @return ResponseInterface
     *
     * @throws HttpException
     */
    public function upload($url, array $form = [], $options = [], array $files = [], $queries = [])
    {
        $multipart = [];

        foreach ($files as $name => $path) {
            $file['name'] = $name;
            if (is_array($file)) {
                $file['contents'] = fopen($path[0], 'r');
                $file['filename'] = $path[1];
            } else {
                $file['contents'] = fopen($path, 'r');
            }
            $multipart[] = $file;
        }

        foreach ($form as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }

        return $this->request($url, 'POST', ['query' => $queries, 'multipart' => $multipart], $options);
    }

    /**
     * Set Client.
     *
     * @param Client $client
     *
     * @return Http
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Return Client instance.
     *
     * @return Client
     */
    public function getClient()
    {
        if (!($this->client instanceof Client)) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * Add a middleware.
     *
     * @param callable $middleware
     *
     * @return $this
     */
    public function addMiddleware(callable $middleware)
    {
        array_push($this->middlewares, $middleware);

        return $this;
    }

    /**
     * Return all middlewares.
     *
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Make a request.
     *
     * @param string $url
     * @param string $method
     * @param array $options
     *
     * @return ResponseInterface
     *
     * @throws HttpException
     */
    public function request($url, $method = 'GET', $params = [], $options = [])
    {
        $method = strtoupper($method);

        $options = array_merge(self::$defaults, $params, $options);

        Log::debug('Client Request:', compact('url', 'method', 'options'));

        $options['handler'] = $this->getHandler();

        $response = $this->getClient()->request($method, $url, $options);

        Log::debug('API response:', [
            'Status' => $response->getStatusCode(),
            'Reason' => $response->getReasonPhrase(),
            'Headers' => $response->getHeaders(),
            'Body' => strval($response->getBody()),
        ]);

        return $response;
    }

    /**
     * Build a handler.
     *
     * @return HandlerStack
     */
    protected function getHandler()
    {
        $stack = HandlerStack::create();

        foreach ($this->middlewares as $middleware) {
            $stack->push($middleware);
        }

        return $stack;
    }
}
