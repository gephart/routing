<?php

/**
 * @RoutePrefix /admin
 */
class TestController
{
    /**
     * @Route {
     *  "rule": "/page/{slug}/{limit}/{offset}",
     *  "name": "homepage",
     *  "requirements": {
     *      "limit": "[0-9]+",
     *      "offset": "[0-9]+"
     *  }
     * }
     */
    public function index($limit, $offset, $slug)
    {
        return $slug . "-" . $limit . "-" . $offset;
    }
}