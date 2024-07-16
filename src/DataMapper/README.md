# DataMapper

DataMapper component implements the [Data Mapper Pattern](https://martinfowler.com/eaaCatalog/dataMapper.html).

Recently i thought to myself how much code is in an ORM or Data Mapper to save the programmer a few seconds when coding, but then on each script run its doing all kinds of checks and trying to figure out things out, it is totally unnessary. This datamapper, you set the initial configuration, it uses minimal magic.

> Naming conventions : find for a single record, findAll for finding multiple records

## Example

Create your `DataMapper`, ensuring that you add the `table`, `fields` and the factory method `createEntity`. By default the Data Mapper will set the primary key to `id`. If you want to use something else then override the `primaryKey` property with the name of the field or an array of fields, if it is a composite primary key.

> **_NOTE:_** If you wish to use custom mapping such as `fromState` and `toState` in your entity classes you can override the `mapDataToEntity` and `mapEntityToData` methods

```php
/**
 * Article Data Mapper
 * 
 * @method Article[] findAll(array $options = [])
 * @method Article[] findAllBy(array criteria array $options = [])
 * @method ?Article findBy(array $criteria = [], array $options = [])
 */
class ArticleDataMapper extends AbstractDataMapper
{
   /**
    * Set the table name
    */
    protected string $table = 'articles';

   /**
    * Set the fields for the mapper to work with in the database
    */
    protected array $fields = [
        'id', 'title', 'body','author_id','created_at','updated_at'
    ];

   /**
    * Create a factory method to
    */
    public function createEntity(): Article
    {
        return new Article();
    }   
}
```

The `DataMapper` will use the `Hydrator` to set the properties on your `Entity`.

Create your entity class (a Plain Old PHP Object (POPO)).

1. Only make a property nullable if the data storage is set to `nullable`.
2. Properties should be `private`.
3. The primary key should not have a setter method, `DataMapper` will use reflection to set this. The getter method should
check if the variable has been initialized before getting it.
4. The `DataMapper` does not call the setter or getter methods, it uses reflection to set or get values, and property names should match the fields that are used in the datasource.

```php
final class Article
{
    private int $id;
    private string $title;
    private string $body;
    private string $created_at;
    private string $updated_at;

    /**
     * Can be null before being persisted
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at ?? null;
    }

    public function setCreatedAt(string $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at ?? null;
    }

    public function setUpdatedAt(string $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
```

## Finding Records

Finding records, this under the hood uses the `QueryBuilder` component, whose readme contains more examples on using different criteria to create different conditions.

```php
# Count
$count = $mapper->count();
$count = $mapper->count(['status' => 'approved']);

$mapper->delete($entity); 

$entity = $mapper->find(['status' => ['approved','published'],'owner_id !=' => 1234]);

$entity = $mapper->get(1234); // same as findById but will throw an entity not found exception if no record is found

$articles = $mapper->findAll();
$articles = $mapper->findAll([
    'title LIKE' => '%foo', // LIKE or NOT LIKE
    'status' => ['approved','published'], 
    'created_at BETWEEN' => ['2024-01-01 12:00:00', '2024-06-01 12:00:00']
]);
```

The `DataMapper` no longer has bulk methods such as `update all` or `delete all`, since whilst convinent it does not have anything to do with the mapper. Therefore you should create a query method in your Repository (an abstraction layer that uses the datamapper) which uses the PDO object. (The DatabaseDataSource object has update and delete methods as well, but i think perhaps for these type of operations you should use something native)

See [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)

```php
// just an example not tested
class UserRepository
{
    public function __construct(private UserDataMapper $user) {

    }

    public function deleteAllByCategoryDraft() : int 
    {
        $pdo = $this->user->getDataSource()->getPDO();
        $stmt = $pdo->prepare('DELETE FROM articles WHERE category = ?')
        $stmt->execute(['draft']);
        return $stmt->rowCount(); // number of records deleted
    }

    public function updateAllInactiveUsersToActive()
    {
        $pdo = $this->user->getDataSource()->getPDO();
        $pdo->prepare('UPDATE users SET status = ? WHERE status = ?')
            ->execute(['active', 'inactive']);
    }
}
```

By default the Data Mapper returns collection of entities in an array, however sometimes you might prefer this in a collection style object, simply override the internal factory method `createCollection` to create the collection object of your choice.

## Callbacks (PSR-14)

The follow callbacks are supported and the `EventManger` which is a tiny and highly efficient `PSR-14` implementation, using a single object to register, unregister and dispatch events. The `EventManagerInterface` is an extension to the `EventDispatcherInterface` offer methods to standardize how to register and unregister events, as well methods to create a more efficient dispatch process which is extremly imporant in classes where there could many events dispatched (e.g Database)

- `beforeSave`  - triggered before beforeCreate or beforeUpdate. By stopping the Event you abort the save operation
- `beforeCreate` - triggered on save if the operation is a create. By stopping the Event you abort the create operation
- `beforeUpdate` - triggered on save if the operation is an update. By stopping the Event you abort the update operation
- `beforeDelete` - triggered on delete. By stopping the Event you abort the delete operation
- `afterCreate` - triggered on save if the operation was a create
- `aterUpdate` - triggered on save if the operation was an update
- `afterSave` - triggered after afterCreate or afterUpdate
- `afterDelete`
- `beforeFind` - triggered on all find operations including count. By stopping the Event you abort the find operation
- `afterFind` - triggered on all find operations including count.

To register a callback pass the name of the event class name and then a callable

```php
$eventManager->addListener(BeforeFind::class, function(BeforeFind $event)){
    // do something
}
$eventManager->addListener(BeforeFind::class, [$this, 'beforeFind']);
$eventManager->addListener(BeforeFind::class, new MyListener()); 
```

Example Listener class:

```php
class MyListener
{
    public function __invoke(BeforeFind $event) : void 
    {

    }
}
```