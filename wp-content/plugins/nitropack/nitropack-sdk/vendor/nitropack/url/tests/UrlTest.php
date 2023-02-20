<?php

use NitroPack\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testValidUrl()
    {
        $urlString = 'https://nitropack.io:80/test-page/?query=true&query2=false#top';

        $url = new Url($urlString);
        $this->assertEquals($urlString, $url->getUrl());
        $this->assertEquals('https', $url->getScheme());
        $this->assertEquals('nitropack.io', $url->getHost());
        $this->assertEquals('/test-page/', $url->getPath());
        $this->assertEquals('top', $url->getHash());
        $this->assertEquals('query=true&query2=false', $url->getQuery());
        $this->assertEquals('80', $url->getPort());
    }

    public function testSchemeToLower()
    {
        $url = new Url('HTTPS://nitropack.io/');
        $this->assertEquals('https', $url->getScheme());
    }

    public function testHostToLower()
    {
        $url = new Url('https://NITROPACK.IO/');
        $this->assertEquals('nitropack.io', $url->getHost());
    }

    public function testDetectAsHost()
    {
        $url = new Url('nitropack.io');
        $this->assertEquals('nitropack.io', $url->getHost());
    }

    public function testDetectAsPath()
    {
        $url = new Url('/test-page');
        $this->assertEquals('/test-page', $url->getPath());
    }

    public function testNormalization()
    {
        $url = new Url('test-page');
        $url->setBaseUrl('nitropack.io');

        $this->assertEquals('http://nitropack.io/', $url->getBaseUrl());
        $this->assertEquals('http://nitropack.io/test-page', $url->getNormalized());
        $this->assertEquals('test-page', $url->getPath());
    }
}