<?php

namespace Tests;

use Meng\Soap\HttpBinding\HttpBinding;
use Meng\Soap\HttpBinding\RequestBuilder;
use Meng\Soap\HttpBinding\RequestException;
use Meng\Soap\Interpreter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class HttpBindingTest extends TestCase
{
    /** @test */
    public function soap11(): void
    {
        $interpreter = new Interpreter('http://www.webservicex.net/airport.asmx?WSDL', ['soap_version' => SOAP_1_1]);
        $builder = new RequestBuilder();
        $httpBinding = new HttpBinding($interpreter, $builder);

        $request = $httpBinding->request('GetAirportInformationByCountry', [['country' => 'United Kingdom']]);
        self::assertInstanceOf(RequestInterface::class, $request);

        $response = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetAirportInformationByCountryResponse xmlns="http://www.webserviceX.NET">
      <GetAirportInformationByCountryResult>string</GetAirportInformationByCountryResult>
    </GetAirportInformationByCountryResponse>
  </soap:Body>
</soap:Envelope>
EOD;

        $stream = new Stream('php://memory', 'r+');
        $stream->write($response);
        $stream->rewind();
        $response = new Response($stream, 200, ['Content-Type' => 'text/xml; charset=utf-8']);
        $response = $httpBinding->response($response, 'GetAirportInformationByCountry');
        self::assertObjectHasAttribute('GetAirportInformationByCountryResult', $response);
    }

    /** @test */
    public function soap12(): void
    {
        $interpreter = new Interpreter('http://www.webservicex.net/uszip.asmx?WSDL', ['soap_version' => SOAP_1_2]);
        $builder = new RequestBuilder();
        $httpBinding = new HttpBinding($interpreter, $builder);

        $request = $httpBinding->request('GetInfoByCity', [['USCity' => 'New York']]);
        self::assertInstanceOf(RequestInterface::class, $request);

        $response = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <GetInfoByCityResponse xmlns="http://www.webserviceX.NET">
      <GetInfoByCityResult>some information</GetInfoByCityResult>
    </GetInfoByCityResponse>
  </soap12:Body>
</soap12:Envelope>
EOD;

        $stream = new Stream('php://memory', 'r+');
        $stream->write($response);
        $stream->rewind();
        $response = new Response($stream, 200, ['Content-Type' => 'Content-Type: application/soap+xml; charset=utf-8']);
        $response = $httpBinding->response($response, 'GetInfoByCity');
        self::assertObjectHasAttribute('GetInfoByCityResult', $response);
    }

    /** @test */
    public function requestBindingFailed(): void
    {
        $this->expectException(RequestException::class);
        $interpreter = new Interpreter(null, ['uri' => '', 'location' => '']);
        $builderMock = $this->getMockBuilder(RequestBuilder::class)
            ->getMock();
        $builderMock->method('getSoapHttpRequest')->willThrowException(new RequestException());

        $httpBinding = new HttpBinding($interpreter, $builderMock);
        $httpBinding->request('some-function', []);
    }
}
