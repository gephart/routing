<?php

namespace Gephart\Routing;

use Gephart\Collections\Collection;
use Gephart\Routing\Exception\NotValidRouteException;

/**
 * Route
 *
 * @package Gephart\Routing
 * @author Michal Katuščák <michal@katuscak.cz>
 * @since 0.2
 * @since 0.5 - Extends Gephart\Collections\Collection
 */
final class RouteCollection extends Collection
{

    public function __construct()
    {
        parent::__construct(Route::class);
    }

    /**
     * @param Route $route
     * @throws NotValidRouteException
     * @return RouteCollection
     */
    public function add($route): RouteCollection
    {
        if (!$route->isValid()) {
            throw new NotValidRouteException("Route is not valid.");
        }

        return parent::add($route);
    }

    /**
     * @return RouteCollection
     */
    public function sortRoutes(): RouteCollection
    {
        return $this->sort(function (Route $a, Route $b) {
            return $a->getPriority() < $b->getPriority();
        });
    }

}