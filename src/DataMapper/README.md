# DataMapper

DataMapper component implements the [Data Mapper Pattern](https://martinfowler.com/eaaCatalog/dataMapper.html), this uses the `Entity`, `Collection` and `QueryBuilder` components.

Recently i thought to myself how much code is in an ORM or Data Mapper to save the programmer a few seconds when coding, but then on each script run its doing all kinds of checks and trying to figure out things out, it is totally unnessary. This datamapper, you set the initial configuration, it uses minimal
magic.

## Example

Create your `DataMapper`, ensuring that you add the `table`, `fields` properties and the `mapDataToEntity` method.

```php
/**
 * Article Mapper
 * 
 * @method ?ArticleEntity find(QueryObject $query)
 * @method ?ArticleEntity findBy(array $criteria, array $options = [])
 * @method Collection|ArticleEntity[] findAll(QueryObject $query)
 * @method Collection|ArticleEntity[] findAllBy(array $criteria, array $options = [])
 */
class Article extends AbstractDataMapper
{
    protected $primaryKey = 'id';
    protected string $table = 'articles';

    // fields to work with
    protected array $fields = [
        'id', 'title','body','author_id','created_at','updated_at'
    ];
    protected string $entityClass = ArticleEntity::class;
   
}
```

The `DataMapper` will use `Reflection` to set the properties on your `Entity`. 

Create your entity class (a Plain Old PHP Object (POPO)).

1. Only make a property nullable if the data storage is set to `nullable`.
2. properties should be `private`
3. the primary key should not have a setter method, the datamapper will use reflection to set this
4. the `DataMapper` does not call the setter or getter methods, it uses reflection to set or get values, and properties value should match what is/will be stored in the datasource.

```php
final class ArticleEntity
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

Under the hood, the find methods use the `QueryObject`, this object is passed to the callbacks.

```php
$query = new QueryObject(['status' => 'pending'],['order' => 'title DESC']);
$result = $mapper->find($query);
$result = $mapper->findAll($query);
$result = $mapper->findCount($query);
$mapper->deleteAll($query);
$mapper->updateAll($query, ['status'=> 'approved']);
```

## Collection

The `Utility\Collection` object is used to store the results from find operations and this is passed to the callback methods.

## Callbacks

> The design of this deliberately does not include a specific event implementation e.g. PSR-14 event or Hooks. These methods are provided as the first point of call for getting the desired behavior.

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
- `afterFind` - triggered on find and findList

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

    protected function afterFind(Collection $collection, QueryObject $query): void
    {
    }
}
```