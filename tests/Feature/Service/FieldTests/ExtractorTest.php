<?php

namespace Tests\Feature\Service\FieldTests;

use App\Services\Processor\ExtractorService;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExtractorTest extends TestCase
{

    protected $data = null;

    public function setUp() : void
    {
        parent::setUp();
        $this->data = $this->getData();
    }

    public function getData() : array
    {
        return include "ExtractorTestData.php";
    }

    public function getRules(string $name) : array
    {
        if (empty($this->data[$name])) return [];
        return $this->data[$name]['rules'];
    }

    public function getProduct(string $name) : array
    {
        if (empty($this->data[$name])) return [];
        return json_decode($this->data[$name]['product_json'], true);
    }

    public function getExpectedResult(string $name) : array
    {
        if (empty($this->data[$name])) return [];
        return $this->data[$name]['expected'];
    }

    /**
     * @test
     *
     * Partner tele failing extraction
     *
     * @return void
     */
    public function can_extract_value()
    {
        $testName = 'one';
        $extractor = new ExtractorService($this->getRules($testName));
        $extracted = $extractor->extract($this->getProduct($testName));

        $this->assertEquals($this->getExpectedResult($testName), $extracted);
    }

    /**
     * @test
     *
     * Partner tele can have a null price
     *
     * @return void
     */
    public function can_gracefully_handle_unreachable_value()
    {
        $testName = 'two';
        $extractor = new ExtractorService($this->getRules($testName));
        $extracted = $extractor->extract($this->getProduct($testName));

        $this->assertEquals($this->getExpectedResult($testName), $extracted);
    }
}
