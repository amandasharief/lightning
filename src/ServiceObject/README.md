# Service Object

Service objects help keep your models and controllers skinny, whilst keeping your code clean and testable. The service object also helps you seperate your application business logic from the framework and also makes it easy to test in isolation. 

`Service Objects` must have all dependencies added to the `__constructor` method.

The `Service Object` is based upon the command pattern and follows the [single responsibility principle](https://en.wikipedia.org/wiki/Single-responsibility_principle), with the protected method `execute` where the application business logic goes and it must always return a `Result` object, standardizing the result is also an important part of this design.

## Usage

Create a class with depdencies in the `__construct` method and place the busines logic in the `execute` method, which must return a `ResultInterface` object.

```php
class RegisterUserService extends AbstractServiceObject
{
    public function __construct(private UserModel $user, private LoggerInterface $logger) 
    {
    }

    // hook will be called before execute here you can setup stuff
    protected function initialize() : void 
    {
    }

    protected function execute(Arguments $args) : Result
    {
        if($args->get('registered') === true){
            return new Result(false, ['message' =>'User already registered']);
        }
        // do some stuff
        return new Result(true);
    }
}
```

When you run the `ServiceObject` you pass an arguments array which be converted to an [Arguments](../Arguments/README.md) object and passed to the `execute` method.

```php
$result = (new RegisterUserService($model, $logger))
    ->dispatch(['name' => 'fred', 'email' => 'fred@example.com']);
```

## Result Object

Depending what the service layer is doing sometimes you will need to just return a simple `true` or `false` and other times you will need a richer result. 

Some of the methods available on `Result` object:

```php
// check status
$result->isSuccess();

// work with data
$result->hasData();
$result->getData();
$result->get('message');
$string = (string) $result;
```