<?php

namespace Gephart\Routing;

use Gephart\DependencyInjection\Container;
use Gephart\EventManager\Event;
use Gephart\EventManager\EventManager;
use Gephart\Request\Request;
use Gephart\Response\ResponseInterface;
use Gephart\Routing\Configuration\RoutingConfiguration;
use Gephart\Routing\Exception\NotFoundRouteException;
use Gephart\Routing\Exception\NotValidRouteException;
use Gephart\Routing\Exception\RouterException;
use Gephart\Routing\Generator\UrlGenerator;
use Gephart\Routing\Loader\AnnotationLoader;

class Router
{

    const START_RUN_EVENT = __CLASS__ . "::START_RUN_EVENT";
    const RESPONSE_RENDER_EVENT = __CLASS__ . "::RESPONSE_RENDER_EVENT";

    /**
     * @var RoutingConfiguration
     */
    private $routing_configuration;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var AnnotationLoader
     */
    private $annotation_loader;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RouteCollection
     */
    private $routes;

    /**
     * @var Route
     */
    private $actual_route;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var UrlGenerator
     */
    private $url_generator;

    public function __construct(
        RoutingConfiguration $routing_configuration,
        Container $container,
        AnnotationLoader $annotation_loader,
        Request $request,
        EventManager $event_manager,
        UrlGenerator $url_generator
    )
    {
        $this->routing_configuration = $routing_configuration;
        $this->container = $container;
        $this->request = $request;
        $this->event_manager = $event_manager;
        $this->annotation_loader = $annotation_loader;
        $this->url_generator = $url_generator;

        $this->routes = new RouteCollection();
    }

    public function addRoute(Route $route)
    {
        if (!$route->isValid()) {
            throw new NotValidRouteException("Route is not valid.");
        }

        $this->routes[] = $route;
    }

    public function addRoutes(RouteCollection $routes)
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }
    }

    public function getRoutes(): array
    {
        $routes = (array) $this->routes;

        usort($routes, function (Route $a, Route $b) {
            return $a->getPriority() >= $b->getPriority() ? false : true;
        });

        return $routes;
    }

    public function getRoute(string $route_name): Route
    {
        $routes = $this->routes;

        /** @var Route $route */
        foreach ($routes as $route) {
            if ($route->getName() === $route_name) {
                return $route;
            }
        }

        throw new NotFoundRouteException("Router: Not found route '$route_name'.");
    }

    public function run()
    {
        $this->event_manager->trigger(self::START_RUN_EVENT);

        $_route = "/" . $this->request->get("_route");

        if ($autoload = $this->routing_configuration->get("autoload")) {
            $this->autoload($autoload);
        }

        $route = $this->getMatchedRoute($_route);
        $this->setActualRoute($route);

        $values = $route->getValuesByMatch($_route);
        $controller_name = $route->getController();
        $action_name = $route->getAction();

        $route_parameters = $this->getRouteParameters($values, $controller_name, $action_name);

        $controller = $this->container->get($controller_name);
        $response = $controller->$action_name(...$route_parameters);

        if ($response instanceof ResponseInterface) {
            $response = $response->render();
        }

        $event = $this->triggerEvent(self::RESPONSE_RENDER_EVENT, [
            "response" => $response
        ]);

        $response = $event->getParam("response");

        if (!is_string($response)) {
            throw new RouterException("Router expected valid response from $controller_name::$action_name");
        }

        echo $response;
    }

    public function generateUrl(string $route_name, array $parameters = []): string
    {
        $route = $this->getRoute($route_name);
        $base_uri = $this->getBaseUri();
        return $base_uri . $this->url_generator->generate($route, $parameters);
    }

    public function actualUrl(): string
    {
        $base_uri = $this->getBaseUri();
        return $base_uri . "/" . $this->request->get("_route");
    }

    public function getBaseUri(): string
    {
        if (!empty($_SERVER["HTTP_HOST"])) {
            return "//".$_SERVER["HTTP_HOST"].str_replace("/index.php","",$_SERVER["SCRIPT_NAME"]);
        }
        return "";
    }

    private function triggerEvent(string $event_name, array $parameters = [])
    {
        $event = new Event();
        $event->setName($event_name);
        $event->setParams($parameters);

        $this->event_manager->trigger($event);

        return $event;
    }

    private function getRouteParameters(array $data, string $controller_name, string $action_name): array
    {
        $reflection_class = new \ReflectionClass($controller_name);

        $parameters = $reflection_class->getMethod($action_name)->getParameters();
        $route_parameters = [];

        foreach ($parameters as $parameter) {
            $parameter_name = $parameter->getName();

            if (empty($data[$parameter_name])) {
                throw new RouterException("Unknown parameter '$parameter_name' in $controller_name::$action_name");
            }

            $route_parameters[] = $data[$parameter_name];
        }
        return $route_parameters;
    }

    private function getMatchedRoute(string $_route): Route
    {
        $routes = $this->getRoutes();

        /** @var Route $route */
        foreach ($routes as $route) {
            if ($route->isMatch($_route)) {
                return $route;
            }
        }

        throw new NotFoundRouteException("Router: Not found route on '$_route'.");
    }

    private function autoload(string $autoload)
    {
        $dir = $this->routing_configuration->getDirectory() . "/../" . $autoload;

        if (!is_dir($dir)) {
            throw new RouterException("'$dir' is not directory.");
        }

        $routes = $this->annotation_loader->loadRoutesFromControllers($dir);

        $this->addRoutes($routes);
    }

    /**
     * @return Route
     */
    public function getActualRoute()
    {
        return $this->actual_route;
    }

    /**
     * @param Route $actual_route
     */
    public function setActualRoute(Route $actual_route)
    {
        $this->actual_route = $actual_route;
    }

}