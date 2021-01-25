# laravel-ccmd

The CCMD (Commented CoMmanD) package is a Laravel extension that permits you to write and schedule commands easier.

## Installation

`composer require rutay/laravel-ccmd`

## Usage

#### Definition

A CCMD (= a command defined through this package), can be defined like this:

```php
// App\SomeClass.php

/**
 * @command
 *
 * @signature   your_app:greet {person}
 * @description A simple command that greets a person.
 *
 * @repeat      '*\5 * * * *'
 */
function helloWorld(Command $command) // The argument $person could even be injected through function parameters.
{
    $person = $command->argument('person');

    // ...
}
```

With the following rules:
* `@command` and `@signature` must be defined (the other fields are optional).
* `@repeat` could be any valid PHP string (it's resolved using `eval()`). If using a cron expression, remember to provide ''.
* The command function can have any name.
* The parameters of the command function function are resolved with this sequence:
    * Type-hint is a `Illuminate\Console\Command`, then is fed with the current (auto-generated) Laravel command.
    * Type-hint could be resolved through the Laravel's ServiceContainer.
    * Parameter's name matches a Command's argument or option.
    * Invalid function prototype definition.
    
#### Subscription

Like you would do with any other Laravel command, you need to register CCMDs in `App\Console\Kernel.php`.

I suggest the following approach:

```php

protected $ccmds = [ // Fill this array with classes that have CCMDs.
    SomeClass::class,
    // ...
];

protected function commands()
{
    (new CCMDLoader())->load($this->ccmds); // There it creates the Laravel's commands.
    // ...
}
    
protected function schedule(Schedule $schedule)
{
    (new CCMDLoader())->schedule($this->ccmds, $schedule); // There it creates the schedules that call the Laravel's commands.
    // ...
}
```

## Why

The necessity of writing this package came up when I needed to develop a platform that had to handle multiple tasks.
For each of these tasks, I wanted to create a command for it (that's useful also for testing) and a it also had to be schedule through cron.

So I found myself writing the same redundant code too many times:
```php

// App\Platform.php

function executeTask1() { /* ... */ }
function executeTask2() { /* ... */ }
function executeTask3() { /* ... */ }

// App\Console\Kernel.php

Artisan::command("platform:task1", function (Platform $platform) {
    $platform->executeTask1();
})->purpose("desc1");

Artisan::command("platform:task2", function (Platform $platform) {
    $platform->executeTask2();
})->purpose("desc2");

Artisan::command("platform:task3", function (Platform $platform) {
    $platform->executeTask3();
})->purpose("desc3");

$schedule->command("platform:task1")->cron('*/5 * * * *');
$schedule->command("platform:task2")->cron('*/6 * * * *');
$schedule->command("platform:task3")->cron('*/7 * * * *');
```

Thanks to this system, now I'm able to solve this issue like this:

```php
/**
 * @command
 *
 * @signature   platform:task1
 * @description desc1
 *
 * @repeat      '*\5 * * * *'
 */
function executeTask1() { /* ... */ }

/**
 * @command
 *
 * @signature   platform:task2
 * @description desc2
 *
 * @repeat      '*\6 * * * *'
 */
function executeTask2() { /* ... */ }

/**
 * @command
 *
 * @signature   platform:task3
 * @description desc3
 *
 * @repeat      '*\7 * * * *'
 */
function executeTask3() { /* ... */ }
```
