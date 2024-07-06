# Hydrator

Hydration is the process of filling an object with data. This hydrator uses the reflection API and has a built in cache mechanism. The goal of this is to seperate the hydration process and create a small easy and reusuable component to prevent having to add convience methods to places where they dont belong.

## Usage

Create an object and set properties.

```php
class Product
{
    /**
     * ID in this example represents an auto-generated field from the database and this should 
     * remain as uninitalized
     */
    private int $id; 
    private string $name;
    private ?string $description = null;

    public function getId(): ?int
    {
        return isset($this->id) ? $this->id : null;
    }

    // no setter for ID 

    // reset of setters and getters
}
```

Create the `hydrator` object and use `hydrate` to fill the object and `extract` to extract the data from the object.

```php
use Lightning\Hydrator\Hydrator;

$hydrator new Hydrator();

$hydrator->hydrate($product,[
    'name' => 'CRM Software',
]);

$data = $hydrator->extract($product); // extracts data as an array from the object
// [
//   "name" => "CRM Software"
//   "description" => null
// ]

$hydrator->hydrate($product,[
    'id' => 1234,
    'name' => 'CRM Software',
    'description' => 'Web based CRM software'
]);
// [
//   "id" => 1234
//   "name" => "CRM Software"
//   "description" => "Web based CRM software"
// ]
```