<?php

namespace Gephart\Routing\Loader;

use Gephart\Annotation\Reader;
use Gephart\Routing\Exception\NotFoundRouteException;
use Gephart\Routing\Exception\NotValidRouteException;
use Gephart\Routing\Route;
use Gephart\Routing\RouteCollection;

class AnnotationLoader
{
    /**
     * @var Reader
     */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadRoutesFromControllers(string $dir): RouteCollection
    {
        $routes = new RouteCollection();
        $controllers = $this->getControllers($dir);

        foreach ($controllers as $controller_name) {
            $rc = new \ReflectionClass($controller_name);
            foreach ($rc->getMethods() as $action) {
                if (!$action->isPublic()) {
                    continue;
                }

                try {
                    $route = $this->generateRouteFromAnnotation($controller_name, $action->name);
                    $routes[] = $route;
                } catch (\Exception $e) {}
            }
        }

        return $routes;
    }

    private function getControllers(string $dir): array
    {
        $this->loadControllers($dir);

        return array_filter(get_declared_classes(), function ($class_name) {
            if (substr($class_name, -10) == "Controller") {
                return $class_name;
            }
        });
    }

    private function loadControllers(string $dir)
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

    private function generateRouteFromAnnotation(string $controller_name, string $action_name): Route
    {
        $prefix = $this->reader->get("RoutePrefix", $controller_name);
        $route_data = $this->reader->get("Route", $controller_name, $action_name);

        if (!$route_data || is_array($route_data) && empty($route_data["rule"])) {
            throw new NotValidRouteException("Router: $controller_name::$action_name hasn't valid route.");
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

            if (!empty($route_data["priority"])) {
                $route->setPriority($route_data["priority"]);
            }

            if (!empty($route_data["requirements"])) {
                $route->setRequirements($route_data["requirements"]);
            }
        }

        return $route;
    }
}