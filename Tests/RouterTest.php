<?php

use Gephart\Configuration\Configuration;
use Gephart\DependencyInjection\Container;
use Gephart\Http\RequestFactory;
use Gephart\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

require_once __DIR__ . '/../vendor/autoload.php';

class RouterTest extends \PHPUnit\Framework\TestCase
{
    /** @var Container */
    public $container;

    public function setUp()
    {
        $this->setSuperglobals();

        $this->container = new Container();

        /** @var Configuration $configuration */
        $configuration = $this->container->get(Configuration::class);
        $configuration->setDirectory(__DIR__ . "/config");

        $this->container->register((new RequestFactory())->createFromGlobals(), ServerRequestInterface::class);
    }

    public function testRouteFromAnnotation()
    {
        /** @var Router $router */
        $router = $this->container->get(Router::class);
        /** @var \Gephart\Http\Response $response */
        $response = $router->run();
        $stream = $response->getBody();
        $stream->rewind();
        $response = $stream->getContents();

        $expected_response = "t-e_s.t-10-20";

        $this->assertTrue($response === $expected_response);
    }

    public function testGenerationUrl()
    {
        /** @var Router $router */
        $router = $this->container->get(Router::class);

        $router->addRoute((new \Gephart\Routing\Route())
            ->setName("testing_route")
            ->setController("Test")
            ->setAction("Test")
            ->setRule("/admin/{entity}/{action}")
        );

        $url = $router->generateUrl("testing_route", [
            "action" => "post",
            "entity" => "article",
            "id" => 21
        ]);

        $expected_url = "/admin/article/post?id=21";

        $this->assertTrue($url === $expected_url);
    }

    public function setSuperglobals()
    {
        $_GET = ["test" => "get", "_route" => "admin/page/t-e_s.t/10/20"];
        $_POST = ["test" => "post"];
        $_COOKIE = ["test" => "cookie"];
        $_SERVER['SERVER_PROTOCOL'] = "HTTP/1.0";
        $_SERVER['SERVER_PORT'] = "80";
        $_SERVER['SERVER_NAME'] = "www.gephart.cz";
        $_SERVER['REQUEST_URI'] = "/index.html";
        $_SERVER['REQUEST_METHOD'] = "GET";
    }
}
