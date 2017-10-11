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
        $body = $slug . "-" . $limit . "-" . $offset;

        $stream = new \Gephart\Http\Stream("php://temp", "rw");
        $stream->write($body);

        return new \Gephart\Http\Response($stream);
    }
}