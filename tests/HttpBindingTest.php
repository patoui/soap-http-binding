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
        $interpreter = new Interpreter('https://www.crcind.com/csp/samples/SOAP.Demo.CLS?WSDL=1', ['soap_version' => SOAP_1_2]);
        $builder = new RequestBuilder();
        $httpBinding = new HttpBinding($interpreter, $builder);

        $request = $httpBinding->request('LookupCity', [['zip' => '90210']]);
        self::assertInstanceOf(RequestInterface::class, $request);

        $response = <<<EOD
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:s="http://www.w3.org/2001/XMLSchema">
   <SOAP-ENV:Body>
      <LookupCityResponse xmlns="http://tempuri.org">
         <LookupCityResult>
            <City>Beverly Hills</City>
            <State>CA</State>
            <Zip>90210</Zip>
         </LookupCityResult>
      </LookupCityResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
EOD;

        $stream = new Stream('php://memory', 'r+');
        $stream->write($response);
        $stream->rewind();
        $response = new Response($stream, 200, ['Content-Type' => 'Content-Type: application/soap+xml; charset=utf-8']);
        $response = $httpBinding->response($response, 'LookupCity');
        self::assertObjectHasAttribute('LookupCityResult', $response);
    }

    /** @test */
    public function soap12(): void
    {
        self::markTestSkipped('Find sample API using Soap 1.2');
    }

    /** @test */
    public function requestBindingFailed(): void
    {
        $this->expectException(RequestException::class);
        $interpreter = new Interpreter(null, ['uri' => '', 'location' => '']);
        $builderMock = $this->getMockBuilder(RequestBuilder::class)->getMock();
        $builderMock->method('getSoapHttpRequest')->willThrowException(new RequestException());

        $httpBinding = new HttpBinding($interpreter, $builderMock);
        $httpBinding->request('some-function', []);
    }
}
