<?php

namespace Gephart\Routing;

/**
 * Route
 *
 * @package Gephart\Routing
 * @author Michal Katuščák <michal@katuscak.cz>
 * @since 0.2
 */
class Route
{
    /** @var string */
    private $rule;

    /** @var string */
    private $name;

    /** @var array */
    private $requirements = [];

    /** @var string */
    private $controller;

    /** @var string */
    private $action;

    /** @var string */
    private $regex;

    /** @var int */
    private $priority = 0;

    /**
     * @param $subject
     * @return bool
     */
    public function isMatch(string $subject)
    {
        preg_match("|^".$this->getRegex()."$|", $subject, $matches);

        if (count($matches) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $subject
     * @return array
     */
    public function getValuesByMatch($subject)
    {
        $rule = $this->getRule();
        $regex = $this->getRegex();

        preg_match_all("|{(\w+)}|", $rule, $rule_matches);
        preg_match("|^".$regex."$|", $subject, $matches);

        if (count($rule_matches) != 2 || count($rule_matches[1]) != count($matches)-1) {
            return [];
        }

        $keys = $rule_matches[1];
        $values = array_splice($matches, 1, count($matches)-1);

        return array_combine($keys, $values);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if (
            $this->rule === null
            || $this->name === null
            || $this->controller === null
            || $this->action === null
        ) {
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getRule(): string
    {
        return $this->rule;
    }

    /**
     * @param string $rule
     * @return Route
     */
    public function setRule(string $rule): Route
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Route
     */
    public function setName(string $name): Route
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * @param array $requirements
     * @return Route
     */
    public function setRequirements(array $requirements): Route
    {
        $this->requirements = $requirements;

        return $this;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     * @return Route
     */
    public function setController(string $controller): Route
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return Route
     */
    public function setAction(string $action): Route
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegex(): string
    {
        if ($this->regex === null) {
            $this->regex = $this->generateRegex();
        }

        return $this->regex;
    }

    private function generateRegex(): string
    {
        $rule = $this->getRule();
        $requirements = $this->getRequirements();

        return preg_replace_callback(
            "|\{(\w+)\}|",
            function ($matches) use ($requirements) {
                $match = $matches[1];
                if (!empty($requirements[$match])) {
                    return "(".$requirements[$match].")";
                }
                return "([-\.\w]+)";
            },
            $rule);
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }
}