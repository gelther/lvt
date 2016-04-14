<?php

namespace DvK\Tests\Laravel\Vat;

use DvK\Laravel\Vat\Rates;

use DvK\Laravel\Vat\VatClients\JsonVatClient;
use PHPUnit_Framework_TestCase;

/**
 * Class RatesTest
 * @package DvK\Tests\Laravel\Vat
 *
 * TODO: Inject client or response so tests do not hit jsonvat.com
 */
class RatesTest extends PHPUnit_Framework_TestCase
{
    /**
     * Mock JsonVatClient so remote API is not hit
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getClientMock() {
        $mock = self::getMockBuilder(JsonVatClient::class)
            ->getMock();
       return $mock;
    }

    /**
     * Mock Cache clientso we can test whether put and get methods are being called without depending on Laravel's Cache class.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getCacheMock() {
        $mock = self::getMockBuilder(Mocks\Cache::class)
            ->getMock();

        return $mock;
    }

    /**
     * @throws \Exception
     *
     * @covers Rates::country
     */
    public function test_country() {
        $data = array(
            'NL' => (object) [
                'standard' => 21,
                'reduced' => 15
            ]
        );
        $mock = $this->getClientMock();
        $mock
            ->method('fetch')
            ->will(self::returnValue( $data ));

        $rates = new Rates(null, $mock);

        // Exception when supplying country code for which we have no rate
        self::expectException( 'Exception' );
        $rates->country('US');

        // Return correct VAT rates
        self::assertEquals(  $rates->country('NL'), 21 );
        self::assertEquals(  $rates->country('NL', 'reduced'), 15 );
    }

    /**
     * @covers Rates::all()
     */
    public function test_all() {
        $data = array(
            'NL' => (object) [
                'standard' => 21,
                'reduced' => 15
            ]
        );
        $mock = $this->getClientMock();
        $mock
            ->method('fetch')
            ->will(self::returnValue( $data ));

        $rates = new Rates(null, $mock);
        self::assertEquals( $data, $rates->all());
    }

    /**
     * @throws \Exception
     *
     * @covers Rates::load
     */
    public function test_ratesAreLoadedFromCache() {
        $mock = $this->getCacheMock();
        $data = array( 'NL' => (object) [ 'standard' => 21, 'reduced' => 15 ]);

        $mock
            ->method('get')
            ->with('vat-rates')
            ->will(self::returnValue($data));

        $rates = new Rates( $mock );

        self::assertNotEmpty($rates->all());
        self::assertEquals($rates->all(), $data);

        self::assertEquals($rates->country('NL'), 21);
        self::assertEquals($rates->country('NL', 'reduced'), 15);
    }

    /**
     *  @covers Rates::load
     */
    public function test_ratesAreStoredInCache() {
        $cacheMock = self::getMockBuilder(Mocks\Cache::class)
            ->getMock();
        $clientMock = $this->getClientMock();

        $cacheMock
            ->expects(self::once())
            ->method('put');

        $rates = new Rates( $cacheMock, $clientMock );
    }


}