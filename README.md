Gephart Routing
===

[![Build Status](https://travis-ci.org/gephart/configuration.svg?branch=master)](https://travis-ci.org/gephart/configuration)

Dependencies
---
 - PHP >= 7.0

Instalation
---

```bash
composer require gephart/routing
```

Basic using
---

/index.php

```php
<?php

// $_GET["_route"] = "/admin/article/edit";

$container = new \Gephart\DependencyInjection\Container();

$configuration = $container->get(\Gephart\Configuration\Configuration::class);
$configuration->setDirectory(__DIR__ . "/config");

$router = $container->get(\Gephart\Routing\Router::class);

$route = new \Gephart\Routing\Route();
$route->setName("testing_route");
$route->setController("Test");
$route->setAction("index");
$route->setRule("/admin/{entity}/{action}");

$router->addRoute($route);

$router->run(); // Run controller Test and action method index

// /admin/article/post?id=21
$url = $router->generateUrl("testing_route", [
    "action" => "post",
    "entity" => "article",
    "id" => 21
]);
```

Annotation
---

/config/routing.json

```json
{
  "autoload": "Controller"
}
```

/Controller/AdminController.php

```php
<?php

/**
 * @RoutePrefix /admin
 */
class AdminController
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
    public function page($limit, $offset, $slug)
    {
        echo "OK";
    }
}
```
