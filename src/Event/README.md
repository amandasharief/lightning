# Event

A lightweight [PSR-14: Event Dispatcher](https://www.php-fig.org/psr/psr-14/) implementation.

## Usage

Create the `EventDispatcher` object

```php
$eventDispatcher = new EventDispatcher();
```

Dispatch an event

```php
$event = new CreditCardPaymentAccepted();
$eventDispatcher->dispatch($event);
```

To register and event

```php
$eventDispatcher->addListener(AfterOrder::class, [$this, 'afterOrder']);
```

```php
$eventDispatcher->addListener(AfterOrder::class, function(AfterOrder $order){
    // do something
});
```

You can remove a listener like so

```php
$registry->removeListener(AfterOrder::class, [$this, 'afterOrder']);
```

# Event Exception

An `EventException` class is marker exception to throw within your Events, they have no special purpose other than identification.