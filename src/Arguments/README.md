# Arguments Object

The `Arguments` object is for passing arguments and working a set of arguments to an object which can be used, contracted etc.  

If `get` is called and the parameter was not supplied it will throw `UnkownArgumentException`, therefore, for optional arguments check with `has` first.

```php
$args = new Arguments(['name' => 'fred', 'email' => 'fred@example.com']);
$name = $args->get('name');
$bool = $args->has('surname');
```
