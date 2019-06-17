<?php

namespace App\Helper;

use GuzzleHttp\Client;

/**
 * Class WeatherHelper
 *
 * @package App\Helper
 */
class WeatherHelper
{
    /**
     * @var string
     */
    private $openWeatherApiKey;

    /**
     * @var Client
     */
    private $client;

    /**
     * WeatherHelper constructor.
     *
     * @param string $openWeatherApiKey
     */
    public function __construct(string $openWeatherApiKey)
    {
        $this->openWeatherApiKey = $openWeatherApiKey;
        $this->client = new Client([
            'base_uri' => 'api.openweathermap.org/data/2.5/',
            'timeout'  => 10.0,
        ]);
    }

    /**
     * @param string $lat
     * @param string $lon
     *
     * @return array
     */
    public function weatherRequestForCoords(string $lat, string $lon): array
    {
        $response = $this->client->request('GET', 'weather', [
            'query' => [
                'appid' => $this->openWeatherApiKey,
                'lat' => $lat,
                'lon' => $lon
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function weatherRequestForPlace(string $place): array
    {
        $response = $this->client->request('GET', 'weather', [
            'query' => [
                'appid' => $this->openWeatherApiKey,
                'q' => $place
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
