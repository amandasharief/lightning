# Lightning Router

A lightweight PSR-7 and PSR-15 router with support for PSR-11, PSR-14.

## Usage

Create the `Router` object, configure routing then dispatch with the `ServerRequestInterface` request.

```php
$router = new Router();
$router->get('/articles/index', function (ServerRequestInterface $request) : ResponseInterface {
    $response = new Response();
    $response->getBody()->write(json_encode(['foo'=>'bar']));
    return $response->withStatus(200);
});
$router->dispatch($request); // Psr\Http\Message\ServerRequestInterface
```

To configure a route to use a `closure`:

```php
$router->get('/articles/index', function (ServerRequestInterface $request) : ResponseInterface  {
    return new Response();
});
```

To configure a route to call a method on a class. 

```php
$router->get('/articles/index', 'App\Controller\ArticlesController::index');
```

To configure a route to call the `__invoke` magic method on a class.

```php
final class ArticlesHomeController
{
    public function __invoke(ServerRequestInterface $request) : ResponseInterface 
    {
        return new Response();
    }
}

$router->get('/articles/home', ArticlesHomeController::class);
```

You can also use a callable array which will also be lazy loaded.

```php
$router->get('/articles/index', [ArticlesController::class, 'index']);
```


## URL Variables

When you need to get a dynamic value from the URI, you can set a variable name, e.g `:id`

```php
$router->get('/articles/:id', 'App\Controller\ArticlesController::show');
```

The values will be added to the `ServerRequest` object as an attribute.

```php
class ArticlesController
{
    public function show(ServerRequestInterface $request) : ResponseInterface
    {
        $id = (int) $request->getAttribute('id');

        $response = new Response();
        $response->getBody()->write("<h1>Article #<small>{$id}</small></h1>");
        return $response->withStatus(200);
    }
}
```

For security purposes you can make sure that the data that is being passed in the URI matches a regular expression pattern. 

```php
/**
 * SLUG: /^[a-zA-Z+]+$/
 * WORD: /^[a-zA-Z+]+$/
 * NUMBER: /^[0-9]+$/
 */

$router->delete('/articles/:id', 'App\Controller\ArticlesController::delete', [
    'id' => '/^[0-9]+$/'
]);
```

## Router Groups

You can group your route definitions together, these routes will only be processed if there is a match on the prefix.

```php
$router->group('/admin', function (RoutesInterface $routes) {
    $routes->get('/dashboard', 'App\Controller\AdminController::dashboard'); // GET /admin/dashboard
});
```

## Middleware

To add `Middleware` on all routes

```php
$router->middleware(new FooMiddleware);
```

To add a `Middleware` to the start of the queue

```php
$router->prependMiddleware(new FooMiddleware);
```

To add for an individual `Route`

```php
$router->get('/articles', [new ArticlesController,'index'])->middleware(new AuthMiddleware);
```

To add `Middleware` for all `Routes` in a group.

```php
$router->group('/admin', function (RoutesInterface $routes) {
    $routes->get('/dashboard', 'App\Controller\AdminController::dashboard'); // GET /admin/dashboard
})->middleware(new AuthMiddleware);
```

## Resources

Here is an example for reference on a sample configuration for a typical MVC controller

```php
$router->get('/articles/new', [ArticlesController::class,'new']);
$router->get('articles', [ArticlesController::class,'index']);
$router->post('articles', [ArticlesController::class,'create']);
$router->get('/articles/:id/edit', [ArticlesController::class,'edit'], ['id' => '/^[0-9]+$/']);
$router->get('/articles/:id', [ArticlesController::class,'show'], ['id' => '/^[0-9]+$/']);
$router->patch('/articles/:id', [ArticlesController::class,'update'], ['id' => '/^[0-9]+$/']);
$router->delete('/articles/:id', [ArticlesController::class,'destroy'], ['id' => '/^[0-9]+$/']);
```

## PSR-11: DI Container

When creating the Router object add a `Container` object to use when creating the object from the matched route proxy.
