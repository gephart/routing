<?php

namespace Gephart\Routing\Generator;

use Gephart\Routing\Exception\RouteException;
use Gephart\Routing\Route;

/**
 * URL generator
 *
 * @package Gephart\Routing\Generator
 * @author Michal Katuščák <michal@katuscak.cz>
 * @since 0.2
 */
class UrlGenerator
{
    /**
     * @param Route $route
     * @param array $parameters
     * @return string
     */
    public function generate(Route $route, array $parameters = []): string
    {
        $rule = $route->getRule();

        $url = preg_replace_callback(
            "|\{(\w+)\}|",
            function ($matches) use (&$parameters, $route) {
                $match = $matches[1];

                if (empty($parameters[$match])) {
                    throw new RouteException("Route '{$route->getName()}' needed '$match'");
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