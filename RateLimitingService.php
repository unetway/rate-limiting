<?php

namespace Unetway\RatesLimiting;

use Redis;

class RateLimitingService
{
    /**
     * @var Redis
     */
    protected $storage;

    /**
     * @var string
     */
    protected $host = 'localhost';

    /**
     * @var int
     */
    protected $maxCallsLimit = 5;

    /**
     * @var int
     */
    protected $timePeriod = 86400;

    /**
     * @var bool
     */
    protected $userAgent = false;

    /**
     * RateLimitingService constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['host'])) {
            $this->host = $options['host'];
        }

        if (isset($options['maxCallsLimit'])) {
            $this->maxCallsLimit = $options['maxCallsLimit'];
        }

        if (isset($options['timePeriod'])) {
            $this->timePeriod = $options['timePeriod'];
        }

        if (isset($options['userAgent'])) {
            $this->userAgent = $options['userAgent'];
        }

        $this->storage = new Redis();
        $this->storage->connect($this->host);
    }

    /**
     * @return bool
     */
    public function check(): bool
    {
        $hash = $this->getHash();

        if (!$this->storage->exists($hash)) {
            $this->storage->set($hash, 1);
            $this->storage->expire($hash, $this->timePeriod);
        } else {
            $this->storage->incr($hash);
            $totalUserCalls = $this->storage->get($hash);

            if ($totalUserCalls >= $this->maxCallsLimit) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return int
     */
    public function total(): int
    {
        $hash = $this->getHash();

        if ($this->storage->exists($hash)) {
            return $this->storage->get($hash);
        }

        return 0;
    }

    /**
     * @return string
     */
    private function getHash(): string
    {
        $str = $this->getClientIp();

        if ($this->userAgent) {
            $str .= $this->getClientAgent();
        }

        return md5($str);
    }

    /**
     * @return string
     */
    private function getClientAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * @return string
     */
    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}