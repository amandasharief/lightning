# DataMapper

DataMapper component implements the [Data Mapper Pattern](https://martinfowler.com/eaaCatalog/dataMapper.html), this uses the `Entity`, `Collection` and `QueryBuilder` components.

Recently i thought to myself how much code is in an ORM or Data Mapper to save the programmer a few seconds when coding, but then on each script run its doing all kinds of checks and trying to figure out things out, it is totally unnessary. This datamapper, you set the initial configuration, it uses minimal
magic.

## Example

Create your `DataMapper`, ensuring that you add the `table`, `fields` and the factory method `createEntity`.  

> **_NOTE:_** If you wish to use custom mapping such as `fromState` and `toState` in your entity classes you can override the `mapDataToEntity` and `mapEntityToData` methods.

```php
/**
 * Article Mapper
 * 
 * @method ?ArticleEntity find(QueryObject $query)
 * @method ?ArticleEntity findBy(array $criteria, array $options = [])
 * @method ArticleEntity[] findAll(QueryObject $query)
 * @method ArticleEntity[] findAllBy(array $criteria, array $options = [])
 */
class ArticleMapper extends AbstractDataMapper
{
    protected $primaryKey = 'id';
    protected string $table = 'articles';

    // fields to work with
    protected array $fields = [
        'id', 'title','body','author_id','created_at','updated_at'
    ];

    public function createEntity(): ArticleEntity
    {
        return new ArticleEntity();
    }   
}
```

The `DataMapper` will use the `Hydrator` to set the properties on your `Entity`.

Create your entity class (a Plain Old PHP Object (POPO)).

1. Only make a property nullable if the data storage is set to `nullable`.
2. properties should be `private`
3. the primary key should not have a setter method, the datamapper will use reflection to set this
4. the `DataMapper` does not call the setter or getter methods, it uses reflection to set or get values, and properties value should match the fields is/will used in the datasource.

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

Finding records, this under the hood uses the `QueryBuilder` component.

```php
$entity = $article->findBy(['id' => 1000]);
$entities = $article->findAllBy(['status' => 'new']);
$count = $article->findCountBy(['status' => 'new']);
$ids = $article->findListBy(['status <>' => 'draft']);
$statuses = $article->findListBy(['status <>' => 'draft'],[
    'keyField'=> 'id', 'valueField' => 'status'
]);
$grouped = $article->findListBy(['status <>' => 'draft'],[
    'keyField'=> 'id', 'valueField' => 'title' ,'groupField' => 'status' 
    ]);
```

You can carry out bulk operations, remember these don't trigger `events` or `hooks`.

```php
$count = $article->updateAllBy(
    ['status'=>'pending','owner'=> 1234], 
    ['status'=>'approved']
);
$count = $aritcle->deleteAllBy([
    'status'=>'draft',
    'created_date <' => date('Y-m-d H:i:s',strtotime('- 3 months'))
]);
```

## Query Object

Under the hood, the find methods use the `QueryObject`, which is passed to the callbacks. This `QueryObject` represents an SQL query. See [P of EAA Query Object](https://www.martinfowler.com/eaaCatalog/queryObject.html).

```php
$query = new QueryObject(['status' => 'pending'],['order' => 'title DESC']);
$result = $mapper->find($query);
$result = $mapper->findAll($query);
$result = $mapper->findCount($query);
$mapper->deleteAll($query);
$mapper->updateAll($query, ['status'=> 'approved']);
```

## Callbacks

> The design of this deliberately does not include a specific event implementation e.g. PSR-14 events. These methods are provided as the first point of call for getting the desired behavior. Note to myself, the Data Mapper design is not suppose to implement other designs, but rather be used to implement by other designs. Try to keep this as independant as possible.

The following callbacks methods are called allowing you modify the behavior of the `DataMapper`, you can create different versions of the `DataMapper` using these methods to carry out different actions such as triggering `PSR-14 events` etc or using hooks or quite simply just placing the logic in the methods.

- `initialize` - This is triggered when the data mapper is constructed
- `beforeSave`  - triggered before beforeCreate or beforeUpdate
- `beforeCreate` - triggered on save if the operation is a create
- `beforeUpdate` - triggered on save if the operation is an update
- `beforeDelete`
- `afterCreate` - triggered on save if the operation was a create
- `aterUpdate` - triggered on save if the operation was an update
- `afterSave` - triggered after afterCreate or afterUpdate
- `afterDelete`
- `beforeFind` - triggered on find, findCount and findList
- `afterFind` - triggered on find and findList.

For example 

```php
abstract AppDataMapper extends AbstractDataMapper
{
    protected function beforeCreate(object $entity): bool
    {
        return true;
    }

    protected function afterCreate(object $entity): void
    {
    }

    protected function beforeUpdate(object $entity): bool
    {
        return true;
    }

    protected function afterUpdate(object $entity): void
    {
    }

    protected function beforeSave(object $entity): bool
    {
        return true;
    }

    protected function afterSave(object $entity): void
    {
    }

    protected function beforeDelete(object $entity): bool
    {
        return true;
    }

    protected function afterDelete(object $entity): void
    {
    }

    protected function beforeFind(QueryObject $query): bool
    {
        return true;
    }

    protected function afterFind(array $resultSet, QueryObject $query): array
    {
        return $resultSet;
    }
}
```

## Entity Lifecycle Callbacks

The `DataMapper` also works with entity life cycle callbacks. Create your entity with the class attribute `Entity` 
so that the `DataMapper` knows that there is metadata on this to read on the entity

The entity lifecycle callbacks are the same names you are familar with if you have used other PHP or java solutions, which are `PrePersist`,`PostPersist`,`PreUpdate`,`PostUpdate`,`PreRemove`,`PostRemove` and `PostLoad`.

> **_NOTE:_**  Since `FindList` returns values from the database only and not entities, therefore consider this when using the `PostLoad`


```php
#[Entity]
class Article
{
    private int $id;
    private string $title;
    private string $body;
    private ?int $author_id = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // getters and setters would go here

    #[PrePersist]
    public function onCreate()
    {
        $this->created_at = date('Y-m-d H:i:s');
    }

    #[PrePersist]
    #[PreUpdate]
    public function onCreateOrUpdate()
    {
         $this->updated_at = date('Y-m-d H:i:s');
    }
}
```

## Executing Raw Queries

Sometimes you may need to execute a query directly

```php
$pdoStatement = $mapper->getDataSource()->execute('SELECT * FROM articles', $params);
foreach($pdoStatement as $row){
    // do something
}