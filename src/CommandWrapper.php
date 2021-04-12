<?php

namespace Rutay\CCMD;

use Illuminate\Console\Command;
use ReflectionMethod;

/**
 * This class has the functionality of interfacing the command's method to Laravel.
 */
class CommandWrapper extends Command
{
    protected $signature;
    protected $description;

    private $class;
    private $method;

    function __construct(
        string $signature,
        string $description,
        string $class,
        ReflectionMethod $method
    )
    {
        $this->signature = $signature;
        $this->description = $description;

        $this->class = $class;
        $this->method = $method;
        
        parent::__construct();
    }

    function handle()
    {
        // The command's class is instantiated through the service container.
        $instance = app()->make($this->class);

        // For every parameter of the command's method, tries to get an argument (instance) out of it.
        $args = [];
        foreach ($this->method->getParameters() as $param) {
            $type = (object) $param->getType();
            $type = method_exists($type, 'getName') ? $type->getName() : null;
            $arg = null;

            // If the parameter is a Command type, then feed it with $this.
            if ($type === Command::class) {
                $arg = $this;
            }
        
            // Try to resolve the parameter through the Laravel's service container.
            if (!$arg) {
                if ($type && class_exists($type)) {
                    $arg = app()->make($type);
                }
            }

            // If the parameter is still not solved, it's an argument or an option.
            // It's suggested to access these parameters through the Command class to be more clear.


            $name = $param->getName();

            if (!$arg && $this->hasArgument($name)) {
                $arg = $this->argument($name);
            } else if (!$arg && $this->hasOption($name)) {
                $arg = $this->option($name);
            }
            
            $args[] = $arg;
        }

        $instance->{$this->method->getName()}(...$args);
    }

}
