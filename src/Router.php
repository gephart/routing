<?php

namespace Gephart\Routing;

use Gephart\Annotation\Reader;
use Gephart\DependencyInjection\Container;
use Gephart\Request\Request;
use Gephart\Routing\Configuration\RoutingConfiguration;
use Gephart\Routing\Exception\NotFoundRouteException;
use Gephart\Routing\Exception\NotValidRouteException;
use Gephart\Routing\Exception\RouterException;

class Router
{

    /**
     * @var RoutingConfiguration
     */
    private $routing_configuration;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var Reader
     */
    private $annotation_reader;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RouteCollection
     */
    private $routes;

    public function __construct(
        RoutingConfiguration $routing_configuration,
        Container $container,
        Reader $annotation_reader,
        Request $request
    )
    {
        $this->routing_configuration = $routing_configuration;
        $this->container = $container;
        $this->annotation_reader = $annotation_reader;
        $this->request = $request;
        $this->routes = new RouteCollection();
    }

    public function addRoute(Route $route)
    {
        if (!$route->isValid()) {
            throw new NotValidRouteException("Route is not valid.");
        }

        $this->routes[] = $route;
    }

    public function run(): void
    {
        $_route = $this->request->get("_route") ?: "/";

        if ($autoload = $this->routing_configuration->get("autoload")) {
            $this->autoload($autoload);
        }

        $route = $this->getMatchedRoute($_route);

        $values = $route->getValuesByMatch($_route);
        $controller_name = $route->getController();
        $action_name = $route->getAction();

        $parameters_bag = $this->getParametersBag($values, $controller_name, $action_name);

        $controller = $this->container->get($controller_name);
        $controller->$action_name(...$parameters_bag);
    }

    public function generateUrl(string $route_name, array $parameters = []): string
    {
        $route = $this->getRoute($route_name);
        $rule = $route->getRule();
        $url = preg_replace_callback(
            "|\{(\w+)\}|",
            function ($matches) use (&$parameters, $route_name) {
                $match = $matches[1];
                if (empty($parameters[$match])) {
                    throw new RouterException("Router: Route '$route_name' needed '$match'")
                }
                $parameter = $parameters[$match];
                unset($parameters[$match]);
                return $parameter;
            },
            $rule);

        if (empty($parameters)) {
            return $url;
        }

        return $url . "?" . http_build_query($parameters);
    }

    private function getParametersBag(array $data, string $controller_name, string $action_name): array
    {
        $reflection_class = new \ReflectionClass($controller_name);

        $parameters = $reflection_class->getMethod($action_name)->getParameters();
        $parameters_bag = [];
        foreach ($parameters as $parameter) {
            $parameter_name = $parameter->getName();
            if (empty($data[$parameter_name])) {
                throw new RouterException("Unknown parameter '$parameter_name'");
            }
            $parameters_bag[] = $data[$parameter_name];
        }
        return $parameters_bag;
    }

    private function getRoute(string $route_name): Route
    {
        /** @var Route $route */
        foreach ($this->routes as $route) {
            if ($route->getName() === $route_name) {
                return $route;
            }
        }

        throw new NotFoundRouteException("Router: Not found route '$route_name'.");
    }

    private function getMatchedRoute(string $_route): Route
    {
        /** @var Route $route */
        foreach ($this->routes as $route) {
            if ($route->isMatch($_route)) {
                return $route;
            }
        }

        throw new NotFoundRouteException("Router: Not found route.");
    }

    private function autoload(string $autoload): void
    {
        $dir = $this->routing_configuration->getDirectory() . "/../" . $autoload;

        if (!is_dir($dir)) {
            throw new RouterException("'$dir' is not directory.");
        }

        $controllers = $this->getControllers($dir);

        foreach ($controllers as $controller_name) {
            $rc = new \ReflectionClass($controller_name);
            foreach ($rc->getMethods() as $action) {
                if ($action->isPublic()) {
                    $this->addRouteFromAnnotation($controller_name, $action->name);
                }
            }
        }
    }

    private function addRouteFromAnnotation($controller_name, $action_name): void
    {
        $prefix = $this->annotation_reader->get("RoutePrefix", $controller_name);
        $route_data = $this->annotation_reader->get("Route", $controller_name, $action_name);

        if (!$route_data || is_array($route_data) && empty($route_data["rule"])) {
            return;
        }

        $route = new Route();
        $route->setName(strtolower($controller_name . "_" . $action_name));
        $route->setController($controller_name);
        $route->setAction($action_name);

        if (is_string($route_data)) {
            $route->setRule($prefix . $route_data);
        }

        if (is_array($route_data)) {
            $route->setRule($prefix . $route_data["rule"]);

            if (!empty($route_data["name"])) {
                $route->setName($route_data["name"]);
            }

            if (!empty($route_data["requirements"])) {
                $route->setRequirements($route_data["requirements"]);
            }
        }

        $this->addRoute($route);
    }

    private function getControllers($dir): array
    {
        $this->loadControllers($dir);

        return array_filter(get_declared_classes(), function ($class_name) {
            if (substr($class_name, -10) == "Controller") {
                return $class_name;
            }
        });
    }

    private function loadControllers($dir): void
    {
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry == "." || $entry == "..") continue;

                if (is_dir($dir . "/" . $entry)) {
                    $this->loadControllers($dir . "/" . $entry);
                }
                if (strpos($entry, "Controller.php") > 1) {
                    include_once $dir . "/" . $entry;
                }
            }
        }
    }

}