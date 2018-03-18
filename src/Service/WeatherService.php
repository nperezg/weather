<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class WeatherService
 * @package App\Service
 */
class WeatherService
{
    const DARK_SKY_OBSERVED_URL = 'https://api.darksky.net/forecast/%s/%s,%s,%s';
    const DARK_SKY_FORECAST_URL = 'https://api.darksky.net/forecast/%s/%s,%s';
    const DARK_SKY_PARAMETERS = '?exclude=minutely,hourly,currently,alerts,flags&units=si';
    const PAST_DAYS = 30;

    private $cache;
    private $serializer;
    private $darkSkyApiKey;

    /**
     * WeatherService constructor.
     * @param CacheItemPoolInterface $cache
     * @param SerializerInterface $serializer
     * @param string $darkSkyApiKey
     */
    public function __construct(CacheItemPoolInterface $cache, SerializerInterface $serializer, string $darkSkyApiKey)
    {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->darkSkyApiKey = $darkSkyApiKey;
    }

    /**
     * Returns the observed weather conditions for the last 30 days at the current local time for a given location
     * DarkSky API calls are concurrent and asynchronous
     * The cache decreases the number of calls to the API and speeds up the app
     *
     * @param float $latitude
     * @param float $longitude
     * @param $callbackFunction
     */
    public function getObserved(float $latitude, float $longitude, $callbackFunction)
    {
        $cache = $this->cache;

        // Current date and time
        $date = new \DateTime();

        $promises = (function () use ($cache, $latitude, $longitude, &$date) {
            $client = new Client();

            for ($i = 0; $i < $this::PAST_DAYS; $i++) {
                $date->modify('-1 day');
                $cacheKey = $latitude . $longitude . $date->format('Y-m-d');

                if ($cache->hasItem($cacheKey)) {
                    $value = $cache->getItem($cacheKey)->get();

                    yield new FulfilledPromise($value);
                    continue;
                }

                yield $client->requestAsync(
                    'GET',
                    sprintf(
                        $this::DARK_SKY_OBSERVED_URL,
                        $this->darkSkyApiKey,
                        $latitude,
                        $longitude,
                        $date->getTimestamp()
                    ) . $this::DARK_SKY_PARAMETERS
                )->then(function (ResponseInterface $response) use ($cache, $cacheKey) {
                        $profile = json_decode($response->getBody(), true);
                        $cache->save($cache->getItem($cacheKey)->set($profile));
                        return $profile;
                });
            }
        })();

        ($promise = new EachPromise($promises, [
            'concurrency' => 4,
            'fulfilled' => function (array $profile) use ($callbackFunction) {
                $callbackFunction($profile);
            }]))->promise()->wait();
    }

    /**
     * Returns the forecasted weather for the next week for the given latitud and longitude
     * No asynchronous call or cache. Just one direct call to the API
     *
     * @param float $latitude
     * @param float $longitude
     * @return mixed
     */
    public function getForecast(float $latitude, float $longitude)
    {
        $client = new Client();
        $response = $client->get(
            sprintf(
                $this::DARK_SKY_FORECAST_URL,
                $this->darkSkyApiKey,
                $latitude,
                $longitude
            ) . $this::DARK_SKY_PARAMETERS
        );
        $profile = json_decode($response->getBody(), true);
        return $profile;
    }
}
