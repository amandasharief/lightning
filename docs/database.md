# Database

Database components are `Connection` a PDO wrapper with a custom `Statement` object. There is also a `PdoFactory` and `Row` a custom object for fetching data from the database.


## Connection

Works with PDO and PSR-3 Logger, also allows you to connect and disconnect as needed.

### Usage

```php
$db = new Connection('mysql:host=mysql;port=3306;dbname=lightning', 'root', 'root');
$db->connect();
$db->disconnect();
```

```php
$statement = $db->execute('SELECT * FROM articles')
foreach($statement as $row){
    dd($row);
}
```

> Placholders values don't have to be quoted

To fetch a single record, using positional `?` placeholders

```php
$row = $db->execute('SELECT * FROM articles WHERE id = ? LIMIT 1', [1000])
        ->fetch();
```

To fetch multiple records, using named `:name` placeholders

```php
$rows = $db->execute('SELECT * FROM articles WHERE id = :id ', ['id' => 1000])
        ->fetchAll();
```

You can also pass any query object that implements `Stringable`, such as the `QueryBuilder` object.

```php
$query = (new QueryBuilder())->select('*')
                ->from('users')
                ->where('id = :id','active = :active');
                
$users = $db->execute($query, ['id' => 1000,'status' => 'active'])->fetchAll();
```

### Insert/Update/Delete

To `insert` a row into the database

```php
$db->insert('articles', [
    'title' => 'This is an article'
]);
```

To `update` a row or rows in the database, with the id values

```php
$db->update('articles', [
    'title' => 'This is an article'
], ['id' => 1234]);
```

To `delete` a row or rows in the database, with the id values

```php
$db->delete('articles',['id' => 1234]);
```

## Row

The `Row` object can be used with `PDO`, this is an object with array access, and some other handy features
when working with a result from the database.

```php
$row = $connection->execute('SELECT * FROM articles')->fetchObject(Row::class);
$title = $row->title;
$title = $row['title'];
```

## PDO Factory

Creates and configures `PDO` instance to in a standard and secure way, ideal when you don't want to use a PDO wrapper or any object that requires a PDO object.

```php
use Lightning\Database\PdoFactory;
$pdoFactory = new PdoFactory();
$pdo = $pdoFactory->create('mysql:host=127.0.0.1;port=3306;dbname=lightning', 'root', 'secret');
```

The default fetch mode for the `PDO` object created from the `PDOFactory` is an associative array.

## Resources

- [https://phpdelusions.net/pdo](https://phpdelusions.net/pdo Great content on PDO)
