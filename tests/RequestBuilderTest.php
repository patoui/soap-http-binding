<?php

namespace Tests;

use Meng\Soap\HttpBinding\RequestBuilder;
use Meng\Soap\HttpBinding\RequestException;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Stream;

class RequestBuilderTest extends TestCase
{
    /** @test */
    public function soap11Request(): void
    {
        $builder = new RequestBuilder();
        $request = $builder->isSOAP11()
            ->setEndpoint('http://www.endpoint.com')
            ->setSoapAction('http://www.soapaction.com')
            ->setSoapMessage(new Stream(fopen('php://temp', 'rb')))
            ->getSoapHttpRequest();

        self::assertEquals('POST', $request->getMethod());
        self::assertEquals('text/xml; charset="utf-8"', $request->getHeader('Content-Type')[0]);
        self::assertTrue($request->hasHeader('Content-Length'));
        self::assertTrue($request->hasHeader('SOAPAction'));
        self::assertEquals('http://www.soapaction.com', $request->getHeader('SOAPAction')[0]);
        self::assertEquals('http://www.endpoint.com', (string)$request->getUri());
    }

    /** @test */
    public function soap11RequestHttpGetBinding(): void
    {
        $this->expectException(RequestException::class);
        $builder = new RequestBuilder();
        $builder->setHttpMethod('GET')
            ->setEndpoint('http://www.endpoint.com')
            ->setSoapAction('http://www.soapaction.com')
            ->setSoapMessage(new Stream(fopen('php://temp', 'rb')))
            ->getSoapHttpRequest();
    }

    /** @test */
    public function soap12Request()
    {
        $builder = new RequestBuilder();
        $request = $builder->isSOAP12()
            ->setEndpoint('http://www.endpoint.com')
            ->setSoapAction('http://www.soapaction.com')
            ->setSoapMessage(new Stream(fopen('php://temp', 'rb')))
            ->getSoapHttpRequest();

        self::assertEquals('POST', $request->getMethod());
        self::assertTrue($request->hasHeader('Content-Type'));
        self::assertEquals('application/soap+xml; charset="utf-8"; action="http://www.soapaction.com"', $request->getHeader('Content-Type')[0]);
        self::assertTrue($request->hasHeader('Content-Length'));
        self::assertFalse($request->hasHeader('SOAPAction'));
        self::assertEquals('http://www.endpoint.com', (string)$request->getUri());
    }

    /**@test */
    public function soap12RequestPutMethod(): void
    {
        $this->expectException(RequestException::class);
        $builder = new RequestBuilder();
        $builder->isSOAP12()
            ->setEndpoint('http://www.endpoint.com')
            ->setSoapAction('http://www.soapaction.com')
            ->setHttpMethod('PUT')
            ->setSoapMessage(new Stream(fopen('php://temp', 'rb')))
            ->getSoapHttpRequest();
    }

    /** @test */
    public function soap12RequestGetMethod(): void
    {
        $stream = fopen('php://temp', 'wb');
        fwrite($stream, 'some string');
        $builder = new RequestBuilder();
        $request = $builder->isSOAP12()
            ->setHttpMethod('GET')
            ->setEndpoint('http://www.endpoint.com')
            ->setSoapAction('http://www.soapaction.com')
            ->setSoapMessage(new Stream($stream, 'r'))
            ->getSoapHttpRequest();

        self::assertEquals('GET', $request->getMethod());
        self::assertFalse($request->hasHeader('Content-Type'));
        self::assertEquals('application/soap+xml', $request->getHeader('Accept')[0]);
        self::assertFalse($request->hasHeader('Content-Length'));
        self::assertFalse($request->hasHeader('SOAPAction'));
        self::assertEquals('http://www.endpoint.com', (string)$request->getUri());
        self::assertEquals('', $request->getBody()->getContents());
    }

    /** @test */
    public function soapNoEndpoint(): void
    {
        $this->expectException(RequestException::class);
        $builder = new RequestBuilder();
        $builder->setSoapMessage(new Stream(fopen('php://temp', 'rb')))->getSoapHttpRequest();
    }

    /** @test */
    public function soapNoMessage()
    {
        $this->expectException(RequestException::class);
        $builder = new RequestBuilder();
        $builder->setEndpoint('http://www.endpoint.com')->getSoapHttpRequest();
    }

    /** @test */
    public function resetAllAfterFailure(): void
    {
        $this->expectException(RequestException::class);
        $builder = new RequestBuilder();
        try {
            $builder->isSOAP12()->setEndpoint('http://www.endpoint.com')->getSoapHttpRequest();
        } catch (RequestException $e) {
        }
        $builder->setHttpMethod('GET')->getSoapHttpRequest();
    }
}
