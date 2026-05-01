# Div PHP Ways

[![Latest Stable Version](https://poser.pugx.org/divengine/ways/v)](https://packagist.org/packages/divengine/ways)
[![Total Downloads](https://poser.pugx.org/divengine/ways/downloads)](https://packagist.org/packages/divengine/ways)
[![Latest Unstable Version](https://poser.pugx.org/divengine/ways/v/unstable)](https://packagist.org/packages/divengine/ways)
[![License](https://poser.pugx.org/divengine/ways/license)](https://packagist.org/packages/divengine/ways)
[![PHP Version Require](https://poser.pugx.org/divengine/ways/require/php)](https://packagist.org/packages/divengine/ways)

Div PHP Ways is a small routing and control-flow library for PHP applications. A
"way" is different from a traditional route: it identifies a control point that
can execute work, register more control points, exchange data, run hooks, and be
called from HTTP, CLI, or directly from PHP code.

## Features

- Register closures or class methods as control points.
- Match HTTP and CLI requests with URL arguments.
- Invoke ways directly from application code.
- Attach hooks before include, before run, before output, or after run.
- Guard control points with reusable rules.
- Keep the library easy to embed: one class, one source file.

## Installation

With Composer:

```bash
composer require divengine/ways
```

Without Composer, include the class directly:

```php
<?php

include "path/to/divengine/ways.php";
```

## Basic Usage

```php
<?php

use divengine\ways;

ways::listen("get://home", function ($data) {
    echo "Hello {$data['user']}";
}, "home");

ways::hook(DIV_WAYS_BEFORE_RUN, "home", function ($data) {
    $data['user'] = "Peter";
    return $data;
});

$data = ways::bootstrap("_url", "home");
```

## Static Method Controllers

`ways::register()` can read controller metadata from PHP comments.

```php
<?php

#id = home
#listen = /home

class Home
{
    public static function Run()
    {
        echo "Hello world";
    }

    public static function About()
    {
        echo "About us";
    }

    #listen@Contact = get://contact
    public static function Contact()
    {
        echo "Contact us";
    }
}
```

```php
<?php

use divengine\ways;

ways::register("app/control/Home.php");
ways::listen("/about", "home@About");

ways::bootstrap("_url", "home");
```

## Rules

Rules can prevent execution when a condition is not met.

```php
<?php

use divengine\ways;

ways::rule("is-admin-section", function ($data, $args) {
    return $args["section"] === "admin";
});

ways::listen("/{section}/login", function () {
    echo "admin login";
}, [
    ways::PROPERTY_RULES => ["is-admin-section"],
]);
```

## CLI

Ways also works from command line scripts.

```php
<?php

use divengine\ways;

ways::listen("/hello/{name}", function ($data = [], $args = []) {
    echo "Hello {$args['name']}\n";
});

ways::bootstrap();
```

```bash
php one_script.php hello Peter
```

## Documentation

The Markdown documentation lives in `docs/`. Release builds generate a PDF with
`scripts/build_pdf.py`.

## License

GPL-3.0-or-later.

--

Rafa Rodriguez  
https://rafageist.com
