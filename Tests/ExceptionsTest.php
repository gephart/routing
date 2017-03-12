<?php

require_once __DIR__ . '/../vendor/autoload.php';

class ExceptionsTest extends \PHPUnit\Framework\TestCase
{
    private $container;

    public function setUp()
    {
        $this->container = new \Gephart\DependencyInjection\Container();

        /** @var \Gephart\Configuration\Configuration $configuration */
        $configuration = $this->container->get(\Gephart\Configuration\Configuration::class);
        $configuration->setDirectory(__DIR__ . "/config");
    }

    public function testNotFoundRoute()
    {
        $caught = false;

        try {
            $router = $this->container->get(\Gephart\Routing\Router::class);
            $router->run();
        } catch (\Gephart\Routing\Exception\NotFoundRouteException $e) {
            $caught = true;
        } catch (Exception $e) {die($e->getMessage())}

        $this->assertTrue($caught);
    }

    public function testNotValidRoute()
    {
        $caught = false;

        $route = new \Gephart\Routing\Route();

        try {
            $router = $this->container->get(\Gephart\Routing\Router::class);
            $router->addRoute($route);
            $router->run();
        } catch (\Gephart\Routing\Exception\NotValidRouteException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    public function testRouter()
    {
        $configuration = $this->container->get(\Gephart\Configuration\Configuration::class);
        $configuration->setDirectory(__DIR__ . "/config_non_valid"); // With not existing autoload dir

        $caught = false;

        try {
            $router = $this->container->get(\Gephart\Routing\Router::class);
            $router->run();
        } catch (\Gephart\Routing\Exception\RouterException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }
}
