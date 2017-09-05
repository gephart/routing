<?php

namespace Gephart\Routing\Configuration;

use Gephart\Configuration\Configuration;

/**
 * Routing configuration
 *
 * @package Gephart\Routing\Configuration
 * @author Michal Katuščák <michal@katuscak.cz>
 * @since 0.2
 */
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

    /**
     * @param Configuration $configuration
     */
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

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function get(string $key)
    {
        return isset($this->routing[$key]) ? $this->routing[$key] : false;
    }

    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }
}