# PSR-15 compliant validation middleware


## Usage

### Basic setup in Slim 4

This package provides a PSR-15 compliant `Validator` class. The `Validator` must be given an array of validation rules upon instantiation.

```php
$validationRules = [
    'id' => 'integer',
    'name' => 'string'
];

$mw = new Validator($validationRules);

$app->post('/example', function($req, $res) {
    // Do stuff
})->add($mw);
```

### Validation rules

Validation rules must be passed into the `Validator` upon instantiation in the format of an associative array.

The array keys should be the field names, and the array values should be the validation rules. 

At the moment this package only support validation based on built in simple datatypes.

```php
$validationRules = [
    'id' => 'integer',
    'name' => 'string',
    'active' => 'boolean'
];

$mw = new Validator($validationRules);
```