<?php

namespace Rutay\CCMD;

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Schedule;
use ReflectionClass;

class CommandLoader
{
    function __construct() {}

    protected function parseDocComment($doc_comment)
    {
        preg_match_all('/@(?<name>[^\s]+)(?:[ \t]+(?<value>.+))?\n/', $doc_comment, $result);

        $names  = $result["name"];
        $values = $result["value"];

        $count = count($result["name"]);

        $map = [];
        for ($i = 0; $i < $count; $i++) {
            $map[$names[$i]] = $values[$i];
        }
        return $map;
    }

    protected function foreachCommandOfClass($class, $callback)
    {
        foreach ((new ReflectionClass($class))->getMethods() as $method)
        {
            $doc_comment = $method->getDocComment();
            if ($doc_comment === false) {
                continue;
            }

            $parsed = $this->parseDocComment($doc_comment);
            if (!isset($parsed["command"])) {
                continue;
            }

            if (!isset($parsed["signature"])) {
                throw new \ParseError(sprintf("@signature is required within the definition of a ccmd. It wasn't found at: %s->%s.", $class, $method->getName()));
            }

            $signature = $parsed["signature"];
            $description = $parsed["description"] ?? null;

            $repeat = $parsed["repeat"] ?? null;
            if ($repeat !== null) {
                $repeat = str_replace(['*\\'], ['*/'], $repeat);
                $repeat = eval("return $repeat;");
            }

            $callback($signature, $description, $repeat, $class, $method);
        }
    }

    protected function injectCommandInLaravel(CommandWrapper $command)
    {
        Application::starting(function ($artisan) use ($command) {
            $artisan->add($command);
        });
    }

    /**
     * Load and create all of the fcmd found in the given class.
     * 
     * @param string|array $class
     */
    function load($classes)
    {
        if (is_string($classes)) {
            $classes = [$classes];
        }

        foreach ($classes as $class) {
            $this->foreachCommandOfClass($class, function ($signature, $description, $repeat, $class, $method) {
                $command = new CommandWrapper($signature, $description, $class, $method);
                $this->injectCommandInLaravel($command);
            });
        }
    }

    /**
     * Schedule all of the fcmd found in the given class.
     * 
     * @param string|array $class
     * @param Schedule     $schedule
     */
    function schedule($classes, Schedule $schedule)
    {
        if (is_string($classes)) {
            $classes = [$classes];
        }

        foreach ($classes as $class) {
            $this->foreachCommandOfClass($class, function ($signature, $description, $repeat, $class, $method) use ($schedule) {
                if ($repeat !== null) {
                    $schedule->command($signature)->cron($repeat);
                }
            });
        }
    }
}
