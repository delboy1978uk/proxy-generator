<?php

namespace DelTesting\ProxyGenerator;

use Codeception\TestCase\Test;
use Del\ProxyGenerator\Service\ProxyGeneratorService;

class ProxyGeneratorServiceTest extends Test
{
   /**
    * @var \UnitTester
    */
    protected $tester;

    /**
     * @var ProxyGeneratorService
     */
    protected $proxyGenerator;

    protected function _before()
    {
        $this->proxyGenerator = new ProxyGeneratorService();
    }

    protected function _after()
    {
        unset($this->proxyGenerator);
    }

    /**
     * Check tests are working
     */
    public function testGenerateThrowsException()
    {
        $svc = new ProxyGeneratorService();
        $this->expectException('InvalidArgumentException');
        $svc->generate();
    }


}
