# Autowire

You can create the `Autowire` object with or without a PSR-11 Container.

```php
$autowire = new Autowire()
$autowire = new Autowire($diContainer);
```

You can autowire a class, a method of an object or a closure

To autowire a class
```php 
$object = $autowire->class(ArticlesController::class); 
```

To autowire a method of an object
```php
$response = $autowire->method($object, 'index'); // you can also pass a 3rd argument for additional params
```

To autowire a closure

```php
$result = $autowire->function(function(Session $session){
    return $session->get('foo');
});
```