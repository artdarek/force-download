# Force File Download for PHP
If on your page you place a link to file like "pdf" browser will automaticly
open it, but what if you don't want that file to be opened, instead you just
want user to be able to download this file. If so this package is a solution
for you :D

---

- [Installation](#installation)
- [Usage](#usage)

## Installation

Add artdarek/force-download to your composer.json file:

```
"require": {
  "artdarek/force-download": "dev-master"
}
```

Use [composer](http://getcomposer.org) to install this package.

```
$ composer update
```

## Usage

Create file called ie. 'download.php'.

Include artdarek/force-download library:

```php
  // include composer autoloader 
  require 'ForceDownload.php';
```

or if you are using composer autoloader include this instead:

```php
  // include composer autoloader 
  require 'vendor/autoload.php';
```

after this simply add:

```php
  // initialize download
  $force = new Artdarek\ForceDownload();
  $force->download();
```

Done... now you can create a url that will directly start
downloding specified file instead of opening it in the browser.

```html
  <a href="download.php?dir=download_folder&file=example.pdf">Download</a>
```

See ``examples`` directory for more examples :D
