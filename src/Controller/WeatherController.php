<?php

namespace App\Controller;

use App\Helper\WeatherHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

class WeatherController
{
    /**
     * @var WeatherHelper
     */
    private $weatherHelper;

    /**
     * WeatherController constructor.
     *
     * @param WeatherHelper $weatherHelper
     */
    public function __construct(WeatherHelper $weatherHelper)
    {
        $this->weatherHelper = $weatherHelper;
    }

    public function weatherReport($place): JsonResponse
    {
        $weatherData = $this->weatherHelper->weatherRequestForPlace($place);

        return new JsonResponse($weatherData);
    }
}
