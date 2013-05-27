# PHP Simple Template Class

This class is a simple PHP wrapper for a Template pattern. PHP already does an excellent job of jumping 
in and out of Markup, this class need only be a small wrapper. Its purpose is to contain the scope of 
variables, support template inclusion and optionally capture rendered template for storage/caching.

## Usage
*some-page.php*
```
<?php

require_once('template.class.php');

$t = new Template();
$t->set('title', 'The page title');
$t->display('my-template.php');
```

*my-template.php*
```
<html>
<head>
	<title><?php echo $title ?></title>
</head>
<body>
</body>
</html>
```
