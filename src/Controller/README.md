# PSR-7 / PSR-14 Controller

A PSR-7 and PSR-14 `Controller` with `TemplateRenderer`, and a couple of important methods `render`, `renderJson` ,`renderFile` and `redirect` to keep code dry when working with `ResponseInterface`.

Create your controller with the factory method `createResponse`

```php
use Lightning\Controller\AbstractController as BaseController;

class AppController extends BaseController
{
    public function createResponse(): ResponseInterface
    {
        return new Response(); // A factory method 
    }
}
```

Now create your controllers

```php
class ArticlesController extends AppController
{
    public function index(ServerRequest $request): ResponseInterface
    {
        return $this->render('articles/index', [
            'title' => 'foo',
        ]);
    }
}
```

## Rendering

To a render a `View`

```php
return $this->render('articles/index', [
    'title' => 'foo',
]);
```

To render JSON

```php
return $this->renderJson([
    'title' => 'foo',
]);
```

## Redirecting

To handle redirects

```php
$this->redirect('/articles/view/123'); // or e.g. https://www.example.com
```

## File Response

To render a file in a Response

```php
return $this->renderFile('/var/www/downloads/2021.pdf');
return $this->renderFile('/var/www/downloads/2021.txt',['download' => 'false']); // To not force download
return $this->renderFile('/var/www/downloads/2021.pdf',['name' =>'important.pdf']); // To give the file a different name
```

##  `PSR-14` Events

To use PSR-14 implement the `EventDispatcherAwareInterface` on your controller and add the `EventDispatcher` object as a dependency, ideally this should be the second dependency.

```php
class BaseController extends AbstractController implements EventDispatcherAwareInterface
{
    public function __construct(
        protected TemplateRendererInterface $view,
        protected EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($view, $eventDispatcher);
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): static
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function dispatchEvent(object $event): object
    {
        return $this->eventDispatcher->dispatch($event);
    }

}
```

The following events are dispatched:

- `BeforeRender` - On this event you can set a Response object, if you do then this response will be returned by the render method.
- `AfterRender`
- `BeforeRedirect` - On this event you can set a Response object, if you do then this response will be returned by the render method.
- `AfterRedirect`