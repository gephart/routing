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

/**
 * Router
 *
 * @package Gephart\Routing
 * @author Michal Katuščák <michal@katuscak.cz>
 * @since 0.2
 * @since 0.5 - Implements Gephart\Collections\Collection
 */
class Router
{

    const START_RUN_EVENT = __CLASS__ . "::START_RUN_EVENT";
    const BEFORE_CALL_EVENT = __CLASS__ . "::BEFORE_CALL_EVENT";
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

    /**
     * @param RoutingConfiguration $routing_configuration
     * @param Container $container
     * @param AnnotationLoader $annotation_loader
     * @param Request $request
     * @param EventManager $event_manager
     * @param UrlGenerator $url_generator
     */
    public function __construct(
        RoutingConfiguration $routing_configuration,
        Container $container,
        AnnotationLoader $annotation_loader,
        Request $request,
        EventManager $event_manager,
        UrlGenerator $url_generator
    ) {
        $this->routing_configuration = $routing_configuration;
        $this->container = $container;
        $this->request = $request;
        $this->event_manager = $event_manager;
        $this->annotation_loader = $annotation_loader;
        $this->url_generator = $url_generator;

        $this->routes = new RouteCollection();
    }

    /**
     * @param Route $route
     */
    public function addRoute(Route $route)
    {
        $this->routes->add($route);
    }

    /**
     * @param Route[] $routes
     */
    public function addRoutes(array $routes)
    {
        $this->routes->collect($routes);
    }

    /**
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes->sortRoutes();
    }

    /**
     * @param string $route_name
     * @return Route
     * @throws NotFoundRouteException
     */
    public function getRoute(string $route_name): Route
    {
        $routes = $this->getRoutes()->filter(function (Route $route) use ($route_name) {
            return $route->getName() === $route_name;
        })->all();

        if (count($routes) > 0) {
            return $routes[0];
        }

        throw new NotFoundRouteException("Router: Not found route '$route_name'.");
    }

    /**
     * @since 0.5
     */
    private function getResponse()
    {
        $_route = "/" . $this->request->get("_route");

        $route = $this->getMatchedRoute($_route);
        $this->setActualRoute($route);

        $values = $route->getValuesByMatch($_route);
        $controller_name = $route->getController();
        $action_name = $route->getAction();

        $this->triggerEvent(self::BEFORE_CALL_EVENT, [
            "controller" => $controller_name,
            "action" => $action_name
        ]);

        $route_parameters = $this->getRouteParameters($values, $controller_name, $action_name);

        $controller = $this->container->get($controller_name);
        $response = $controller->$action_name(...$route_parameters);

        return $response;
    }

    /**
     * @throws RouterException
     */
    public function run()
    {
        $this->event_manager->trigger(self::START_RUN_EVENT);

        if ($autoload = $this->routing_configuration->get("autoload")) {
            $this->autoload($autoload);
        }

        $response = $this->getResponse();

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

    /**
     * @param string $route_name
     * @param array $parameters
     * @return string
     */
    public function generateUrl(string $route_name, array $parameters = []): string
    {
        $route = $this->getRoute($route_name);
        $base_uri = $this->getBaseUri();
        return $base_uri . $this->url_generator->generate($route, $parameters);
    }

    /**
     * @since 0.3
     *
     * @param array ...$params
     * @return string
     */
    public function redirectTo(...$params): string
    {
        $url = $this->generateUrl(...$params);
        header("location: $url");
        exit;
    }

    /**
     * @return string
     */
    public function actualUrl(): string
    {
        $base_uri = $this->getBaseUri();
        return $base_uri . "/" . $this->request->get("_route");
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        if (!empty($_SERVER["HTTP_HOST"])) {
            return "//".$_SERVER["HTTP_HOST"].str_replace("/index.php", "", $_SERVER["SCRIPT_NAME"]);
        }
        return "";
    }

    /**
     * @param string $event_name
     * @param array $parameters
     * @return Event
     */
    private function triggerEvent(string $event_name, array $parameters = [])
    {
        $event = new Event();
        $event->setName($event_name);
        $event->setParams($parameters);

        $this->event_manager->trigger($event);

        return $event;
    }

    /**
     * @param array $data
     * @param string $controller_name
     * @param string $action_name
     * @return array
     * @throws RouterException
     */
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

    /**
     * @param string $_route
     * @return Route
     * @throws NotFoundRouteException
     */
    private function getMatchedRoute(string $_route): Route
    {
        $routes = $this->getRoutes()->filter(function (Route $route) use ($_route) {
            return $route->isMatch($_route);
        })->all();

        if (count($routes) > 0) {
            return $routes[0];
        }

        throw new NotFoundRouteException("Router: Not found route on '$_route'.");
    }

    /**
     * @param string $autoload
     * @throws RouterException
     */
    private function autoload(string $autoload)
    {
        $dir = $this->routing_configuration->getDirectory() . "/../" . $autoload;

        if (!is_dir($dir)) {
            throw new RouterException("'$dir' is not directory.");
        }

        $routes = $this->annotation_loader->loadRoutesFromControllers($dir);

        $this->routes->collect($routes->all());
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
