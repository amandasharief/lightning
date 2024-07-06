# Hydrator

Hydration is the process of filling an object with data. This hydrator uses the reflection API and has a built in cache mechanism. The goal of this project was to seperation the hydration process from within the other components and create a small reusable library.

## Usage

Create an object class

```php
class ProductEntity
{
    private int|null $id = null;
    private string $name;
    private ?string $description = null;
}
```

Create the `hydrator` object and use `hydrate` to fill the object and `extract` to extract the data from the object.

```php
use Lightning\Hydrator\Hydrator;

$hydrator new Hydrator();

$hydrator->hydrate($product,[
    'name' => 'Laptop',
    'description' => 'none'
]);

$data = $hydrator->extract($product); // extracts data as an array from the object
// [
//     'name' => 'Laptop',
//     'description' => 'none'
// ]
```