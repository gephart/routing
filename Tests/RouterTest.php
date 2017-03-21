<?php

require_once __DIR__ . '/../vendor/autoload.php';

class RouterTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->container = new \Gephart\DependencyInjection\Container();

        /** @var \Gephart\Configuration\Configuration $configuration */
        $configuration = $this->container->get(\Gephart\Configuration\Configuration::class);
        $configuration->setDirectory(__DIR__ . "/config");
    }

    public function testRouteFromAnnotation()
    {
        $_GET["_route"] = "admin/page/t-e_s.t/10/20";

        ob_start();
        /** @var \Gephart\Routing\Router $router */
        $router = $this->container->get(\Gephart\Routing\Router::class);
        $router->run();
        $response = ob_get_contents();
        ob_end_clean();

        $expected_response = "t-e_s.t-10-20";

        $this->assertTrue($response === $expected_response);
    }

    public function testGenerationUrl()
    {
        /** @var \Gephart\Routing\Router $router */
        $router = $this->container->get(\Gephart\Routing\Router::class);

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
}
