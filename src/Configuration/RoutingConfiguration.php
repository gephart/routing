<?php

namespace Gephart\Routing\Configuration;

use Gephart\Configuration\Configuration;

class RoutingConfiguration
{
    /**
     * @var array
     */
    private $routing;

    /**
     * @var string
     */
    private $directory;

    public function __construct(Configuration $configuration)
    {
        try {
            $routing = $configuration->get("routing");

            if (!is_array($routing)) {
                $routing = [];
            }
        } catch (\Exception $e) {
            $routing = [];
        }

        $this->routing = $routing;
        $this->directory = $configuration->getDirectory();
    }

    public function get(string $key)
    {
        return isset($this->routing[$key]) ? $this->routing[$key] : false;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }
}