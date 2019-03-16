<?php
/**
 * Created by PhpStorm.
 * User: hasee
 * Date: 16/3/2019
 * Time: 上午 11:05
 */
namespace Lt\Weather\Test;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Lt\Weather\Exceptions\HttpException;
use Lt\Weather\Exceptions\InvalidArgumentException;
use Lt\Weather\Weather;
use Mockery\Matcher\AnyArgs;
use PHPUnit\Framework\TestCase;

class WeatherTest extends TestCase
{
    public function testGetWeather()
    {
        //创建模拟接口响应值
        $response = new Response(200,[], '{"success":true}'); //这是设置假的response

        //创建 http Client
        $client = \Mockery::mock("这TM随便填的，只是起个名字而已");

        //指定将会产生的行为（在后续的测试中将会按下面的参数调用）
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'output' => 'json',
                'extensions' => 'base',
            ]
        ])->andReturn($response);
        //到目前为止，上面的三步都是创建一个假的返回对象，
        //这个假的对象有一个get方法，get方法呢，传入两个参数，并且返回一个$response
        //这个假的对象就是用来替换掉Weather类的getHttpClient返回的那个Client的，
        //因为我们会调用真实Client的get方法并获取返回值，所以这里我们直接全部模拟出来


        //接下来这句应该是创建一个Weather类的模拟对象，它具有Weather类的所有属性和方法，几乎一样的，
        //第一个参数是要模拟的类，第二个是传入构造方法的参数，最后的makePartial没看懂
        //关键就在于，创建出来的这个类是可以调的
        $w = \Mockery::mock(Weather::class,['mock-key'])->makePartial();
        //下面就开始调了，将Weather类里的原本的
        //getHttpClient方法替换一个新的方法，并且返回值为之前模拟创建的$client，都TM是假的我草
        $w->allows()->getHttpClient()->andReturn($client);

        //然后调用getweather方法，并断言返回值为模拟的返回值，这个getWeather方法没有被替换，所以会调用
        //Weather类中原有的方法，这里期望的返回值其实就是和最上面设置的response进行比对
        $this->assertSame('"{\"success\":true}"', $w->getWeather("深圳"));

        // xml
        $response = new Response(200, [], '<hello>content</hello>');
        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'extensions' => 'all',
                'output' => 'xml',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>content</hello>', $w->getWeather('深圳', 'all', 'xml'));
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs()) // 由于上面的用例已经验证过参数传递，所以这里就不关心参数了。
            ->andThrow(new \Exception('request timeout')); // 当调用 get 方法时会抛出异常。

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        // 接着需要断言调用时会产生异常。
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $w->getWeather('深圳');
    }


    public function testGetHttpClient()
    {
        $w = new Weather('mock-key');

        // 断言返回结果为 GuzzleHttp\ClientInterface 实例
        $this->assertInstanceOf(ClientInterface::class, $w->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $w = new Weather('mock-key');

        // 设置参数前，timeout 为 null
        $this->assertNull($w->getHttpClient()->getConfig('timeout'));

        // 设置参数
        $w->setGuzzleOptions(['timeout' => 5000]);

        // 设置参数后，timeout 为 5000
        $this->assertSame(5000, $w->getHttpClient()->getConfig('timeout'));
    }

    public function testGetWeatherWithInvalidType()
    {
        $w = new Weather("mock-key");

        //断言会抛出异常
        $this->expectException(InvalidArgumentException::class);

        //断言异常消息为
        $this->expectExceptionMessage("Invalid type value(base/all): foo");

        $w->getWeather("深圳", "foo");

        //如果没有抛出异常，会运行到这一行，标记为测试失败
        $this->fail("Fail to assert getWeather throw exception with invalid argument.");
    }

    public function testGetWeatherWithInvalidFormat()
    {
        $w = new Weather('mock-key');

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage("Invalid response format: array");

        $w->getWeather("深圳", "base", "array");

        $this->fail("Fail to assert getWeather throw exception with invalid argument.");
    }

}