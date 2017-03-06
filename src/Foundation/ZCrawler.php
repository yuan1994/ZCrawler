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

namespace ZCrawler\Foundation;

use Doctrine\Common\Cache\Cache as CacheInterface;
use Doctrine\Common\Cache\FilesystemCache;
use Pimple\Container;
use ZCrawler\Core\Http;
use ZCrawler\Core\Config;
use ZCrawler\Support\Log;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Class ZCrawler
 *
 * @package ZCrawler\Foundation
 * @property \ZCrawler\Login\Login       $login
 * @property \ZCrawler\Schedule\Schedule $schedule
 * @property \ZCrawler\User\User         $user
 * @property \ZCrawler\User\Main         $user_main
 * @property \ZCrawler\User\Notice       $user_notice
 * @property \ZCrawler\Exam\Exam         $exam
 * @property \ZCrawler\Grade\Grade       $grade
 * @property \ZCrawler\Grade\Cet         $cet
 * @property \ZCrawler\Schedule\School   $schedule_school
 * @property \ZCrawler\Schedule\Select   $schedule_select
 */
class ZCrawler extends Container
{
    protected $providers = [
        ServiceProviders\LoginServiceProvider::class,
        ServiceProviders\ScheduleServiceProvider::class,
        ServiceProviders\UserServiceProvider::class,
        ServiceProviders\UserNoticeServiceProvider::class,
        ServiceProviders\UserMainServiceProvider::class,
        ServiceProviders\ExamServiceProvider::class,
        ServiceProviders\GradeServiceProvider::class,
        ServiceProviders\GradeCetServiceProvider::class,
        ServiceProviders\ScheduleSchoolServiceProvider::class,
        ServiceProviders\ScheduleSelectServiceProvider::class,
    ];


    /**
     * ZCrawler constructor.
     *
     * @param array  $username
     * @param string $password
     * @param array  $config
     */
    public function __construct($username, $password, $config = [])
    {
        parent::__construct();

        Config::init($config);

        if (Config::get('debug')) {
            error_reporting(E_ALL);
        }
        $this['username'] = $username;
        $this['password'] = $password;
        $this['cookie'] = 'ZCrawler-cookie-' . $username;

        $this->registerProviders();
        $this->registerBase();
        $this->initializeLogger();

        Http::setDefaultOptions(Config::get('http'));
    }

    /**
     * Add a provider.
     *
     * @param string $provider
     *
     * @return $this
     */
    public function addProvider($provider)
    {
        array_push($this->providers, $provider);

        return $this;
    }

    /**
     * Set providers.
     *
     * @param array $providers
     */
    public function setProviders(array $providers)
    {
        $this->providers = [];

        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    /**
     * Return all providers.
     *
     * @return array
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Magic get access.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function __get($id)
    {
        return $this->offsetGet($id);
    }

    /**
     * Magic set access.
     *
     * @param string $id
     * @param mixed  $value
     */
    public function __set($id, $value)
    {
        $this->offsetSet($id, $value);
    }

    /**
     * Register providers.
     */
    private function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $this->register(new $provider());
        }
    }

    /**
     * Register basic providers.
     */
    private function registerBase()
    {
        if (!empty(Config::get('cache')) && Config::get('cache') instanceof CacheInterface) {
            $this['cache'] = Config::get('cache');
        } else {
            $this['cache'] = function () {
                return new FilesystemCache(sys_get_temp_dir());
            };
        }
    }

    /**
     * Initialize logger.
     */
    private function initializeLogger()
    {
        if (Log::hasLogger()) {
            return;
        }

        $logger = new Logger('ZCrawler');

        if (!Config::get('debug') || defined('PHPUNIT_RUNNING')) {
            $logger->pushHandler(new NullHandler());
        } elseif (Config::get('log.handler') instanceof HandlerInterface) {
            $logger->pushHandler(Config::get('log.handler'));
        } elseif ($logFile = Config::get('log.file')) {
            $logger->pushHandler(new StreamHandler($logFile, Config::get('log.level', Logger::WARNING)));
        }

        Log::setLogger($logger);
    }
}
