<?php

namespace Gephart\Routing\Generator;

use Gephart\Routing\Exception\RouteException;
use Gephart\Routing\Route;

class UrlGenerator
{
    public function generate(Route $route, array $parameters = []): string
    {
        $rule = $route->getRule();

        $url = preg_replace_callback(
            "|\{(\w+)\}|",
            function ($matches) use (&$parameters, $route) {
                $match = $matches[1];

                if (empty($parameters[$match])) {
                    throw new RouteException("Route '{$route_name->getName()}' needed '$match'");
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
}