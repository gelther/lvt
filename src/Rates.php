<?php

namespace DvK\Laravel\Vat;

use DvK\Laravel\Vat\VatClients\JsonVatClient;
use DvK\Laravel\Vat\VatClients\VatClient;
use Exception;
use Illuminate\Contracts\Cache\Repository as Cache;


class Rates
{

    /**
     * @var array
     */
    protected $map = array();

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var VatClients\VatClient
     */
    protected $client;

    /**
     * VatValidator constructor.
     *
     * @param Cache     $cache  (optional)
     * @param VatClient $client (optional)
     */
    public function __construct($cache = null, VatClient $client = null)
    {
        $this->cache  = $cache;
        $this->client = $client;
        $this->map    = $this->load();
    }

    protected function load()
    {
        // load from cache
        if ($this->cache) {
            $map = $this->cache->get('vat-rates');
        }

        // fetch from jsonvat.com
        if (empty($map)) {
            $map = $this->fetch();

            // store in cache
            if ($this->cache) {
                $this->cache->put('vat-rates', $map, 86400);
            }
        }

        return $map;
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    protected function fetch()
    {
        if (! $this->client) {
            $this->client = new JsonVatClient();
        }

        return $this->client->fetch();
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->map;
    }

    /**
     * @param  string $country
     * @param  string $rate
     *
     * @return double
     *
     * @throws Exception
     */
    public function country($country, $rate = 'standard')
    {
        $country = strtoupper($country);
        $country = $this->getCountryCode($country);

        if (! isset($this->map[$country])) {
            throw new Exception('Invalid country code.');
        }

        if (! isset($this->map[$country]->$rate)) {
            throw new Exception('Invalid rate.');
        }

        return $this->map[$country]->$rate;
    }

    /**
     * Get normalized country code
     *
     * Fixes ISO-3166-1-alpha2 exceptions
     *
     * @param  string $country
     * @return string
     */
    protected function getCountryCode($country)
    {
        if ($country == 'UK') {
            $country = 'GB';
        }

        if ($country == 'EL') {
            $country = 'GR';
        }

        return $country;
    }


}
