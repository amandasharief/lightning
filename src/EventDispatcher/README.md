# PSR-14: Event Dispatcher

A lightweight [PSR-14: Event Dispatcher](https://www.php-fig.org/psr/psr-14/) implementation.

## Usage

### Event Dispatcher

Create the `EventDispatcher` object with a PSR 14 `ListenerProviderInterface` object.

```php
$listenerProvider = new ListenerProvider(); 
$eventDispatcher = new EventDispatcher($listenerProvider);
```

To dispatch an event

```php
$event = new CreditCardPaymentAccepted();
$eventDispatcher->dispatch($event);
```

To get the listener provider from the dispatcher, as this returns and any object with the PSR `ListenerProviderInterface` you will probably want to add the comment below so your IDE can code complete
when you are programming.

```php
 /**
  * @var ListenerProvider $provider
  */
$provider = $eventDispatcher->getListenerProvider();
```

To work with any `ListenerProviderInterface` object from the code that wishes to emit an Event e.g. controller, using the `Event Dispatcher` call the `configure` method which will pass its `ListenerProviderInterface` object.

```php
$eventDispatcher->configure(function (ListenerProvider $provider){
        $provider->add(Event::class, [$this, 'beforeSave']);
    });
```

## Listener Providers

Listener providers provide the listeners to the `Event Dispatcher`, there is the `ListenerProvider` which is the standard one and provides listeners in the order they were defined. Then there is the `PriorityListener` provider, this allows you to set a priorities. I recommend you start with the `ListenerProvider` first, and only use the `PriorityListenerProvider`version, if you need too. Priority has extra overhead due to sorting yet is not needed in most projects. The listener provider default priorty is `0` and can accept positive or negative numbers, the higher the number, the higher the priorty. 

**_NOTE:_** For performance reasons, 3 consts have been defined `HIGH_PRIORITY`, `NORMAL_PRIORITY` and `LOW_PRIORITY` using these  keeps sorting to the minimal as opposed 

To add a closure use the event class name (PSR) and a callable.

```php
class OrderListener
{
    public function __invoke(Event $object) : void 
    {

    }
}
```

```php
$listenerProvider->add(AfterOrder::class, [$this, 'afterOrder'];
$listenerProvider->add(AfterOrder::class, function(AfterOrder $order){
    // do something
});
$listenerProvider->add(AfterOrder::class, new OrderListener());
```

You can remove a listener like so

```php
$listenerProvider->removeListener(AfterOrder::class, [$this, 'afterOrder']);
```