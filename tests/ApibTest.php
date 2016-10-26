<?php

namespace Hmaus\Spas\Parser\Apib\Tests;

use Hmaus\Spas\Parser\Apib;
use Hmaus\Spas\Parser\SpasResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;

class ApibTest extends \PHPUnit_Framework_TestCase
{
    public function testProvider()
    {
        $fixture = json_decode(
            file_get_contents(__DIR__ . '/fixtures/09. Advanced Attributes.md.refract.json'), true
        );

        $provider = new Apib();
        $parsedRequests = $provider->parse($fixture);

        $this->assertCount(3, $parsedRequests);

        foreach ($parsedRequests as $request) {
            $this->assertInstanceOf(ParameterBag::class, $request->getParams());
            $this->assertInstanceOf(HeaderBag::class, $request->getHeaders());
            $this->assertNotEmpty($request->getName());
            $this->assertEmpty($request->getBaseUrl());
            $this->assertNotEmpty($request->getHref());
            $this->assertInternalType('string', $request->getContent());
            $this->assertNotEmpty($request->getMethod());
            $this->assertTrue($request->isEnabled());
            $this->assertInstanceOf(SpasResponse::class, $request->getResponse());

            $response = $request->getResponse();
            $this->assertInstanceOf(HeaderBag::class, $response->getHeaders());
            $this->assertNotEmpty($response->getSchema());
            $this->assertNotEmpty($response->getStatusCode());
            $this->assertNotEmpty($response->getBody());
        }
    }
}
