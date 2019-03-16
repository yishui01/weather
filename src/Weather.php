<?php
/**
 * Created by PhpStorm.
 * User: hasee
 * Date: 16/3/2019
 * Time: 上午 10:28
 */
namespace Lt\Weather;

use GuzzleHttp\Client;
use Lt\Weather\Exceptions\Exception;
use Lt\Weather\Exceptions\HttpException;
use Lt\Weather\Exceptions\InvalidArgumentException;

class Weather
{
    protected $key;
    protected $guzzleOptions = [];

    public function __construct(string $key)
    {
        $this->key =$key;
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    public function getWeather($city, string $type = "base", string $format = "json")
    {
        $url = 'https://restapi.amap.com/v3/weather/weatherInfo';

        if (!\in_array(\strtolower($format), ["xml","json"])) {
            throw new InvalidArgumentException("Invalid response format: ".$format);
        }
        if (!\in_array(\strtolower($type), ['base', 'all'])) {
            throw new InvalidArgumentException("Invalid type value(base/all): ".$type);
        }
        $query = array_filter([
            'key'=>$this->key,
            'city'=>$city,
            'output'=>$format,
            'extensions'=>$type
        ]);

        try {
            $response = $this->getHttpClient()->get($url,[
                'query' => $query
            ])->getBody()->getContents();

            return 'json' === $format ? \json_encode($response, true) : $response;
        }catch (\Exception $exception){
            throw new HttpException($exception->getMessage(), $exception->getCode(), $exception);
        }

    }
}
//require __DIR__.'/../vendor/autoload.php';
//$obj = new Weather('e1db0bbdb88078139dffb4c07a31996e');
//var_dump(json_decode($obj->getWeather("长沙")));