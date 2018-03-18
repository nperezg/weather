<?php

namespace App\Controller;

use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WeatherController
 * @package App\Controller
 */
class WeatherController extends Controller
{
    /**
     * @var WeatherService
     */
    private $weatherService;

    /**
     * WeatherController constructor.
     * @param WeatherService $weatherService
     */
    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Index page
     */
    public function index()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * Gets the observed weather conditions during the past 30 days
     *
     * @param float $latitude
     * @param float $longitude
     * @param string $city
     * @return Response
     */
    public function getObserved(float $latitude, float $longitude, string $city)
    {
        $result = [];

        $this->weatherService->getObserved($latitude, $longitude, function ($profile) use (&$result) {
            $result[] = $profile;
        });

        return $this->render('default/observed.html.twig', [
            'observed' => $result,
            'city' => $city,
        ]);
    }

    /**
     * Gets the forecast weather conditions for the next week
     *
     * @param float $latitude
     * @param float $longitude
     * @param string $city
     * @return Response
     */
    public function getForecast(float $latitude, float $longitude, string $city)
    {
        $result = $this->weatherService->getForecast($latitude, $longitude);

        return $this->render('default/forecast.html.twig', [
            'forecast' => $result,
            'city' => $city,
        ]);
    }
}
