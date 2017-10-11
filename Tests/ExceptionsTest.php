<?php

use Gephart\Http\RequestFactory;
use Psr\Http\Message\ServerRequestInterface;

require_once __DIR__ . '/../vendor/autoload.php';

class ExceptionsTest extends \PHPUnit\Framework\TestCase
{
    private $container;

    public function setUp()
    {
        $this->setSuperglobals();

        $this->container = new \Gephart\DependencyInjection\Container();

        /** @var \Gephart\Configuration\Configuration $configuration */
        $configuration = $this->container->get(\Gephart\Configuration\Configuration::class);
        $configuration->setDirectory(__DIR__ . "/config");

        $this->container->register((new RequestFactory())->createFromGlobals(), ServerRequestInterface::class);
    }

    public function testNotFoundRoute()
    {
        $caught = false;

        try {
            $router = $this->container->get(\Gephart\Routing\Router::class);
            $router->run();
        } catch (\Gephart\Routing\Exception\NotFoundRouteException $e) {
            $caught = true;
        }

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

    public function setSuperglobals()
    {
        $_GET = ["test" => "get"];
        $_POST = ["test" => "post"];
        $_COOKIE = ["test" => "cookie"];
        $_SERVER['SERVER_PROTOCOL'] = "HTTP/1.0";
        $_SERVER['SERVER_PORT'] = "80";
        $_SERVER['SERVER_NAME'] = "www.gephart.cz";
        $_SERVER['REQUEST_URI'] = "/index.html";
        $_SERVER['REQUEST_METHOD'] = "GET";
    }
}
