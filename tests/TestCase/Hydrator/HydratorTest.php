<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Hydrator;

use ReflectionClass;
use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use Lightning\Hydrator\Hydrator;

trait EntityTrait
{
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getId(): ?int
    {
        return isset($this->id) ? $this->id : null;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }
}


class Product
{
    use EntityTrait;

    private ?int $id = null;
    private string $name;
    private ?string $description = null;
}

class Person
{
    use EntityTrait;

    private int $id;
    private string $name;
    private ?string $description = null;    
}

final class HydratorTest extends TestCase
{
    public function testCachePropertyIsEmpty(): void
    {
        $reflectionClass = new ReflectionClass(Hydrator::class);
        $reflectionClass->setStaticPropertyValue('cache', []);
        $this->assertEquals([], $reflectionClass->getStaticPropertyValue('cache'));
    }

    public function testHydrate(): void
    {
        $product = new Product();

        $data = [
            'name' => 'product name',
            'description' => 'product description',
            'unkown_property' => 'foo'
        ];

        (new Hydrator())->hydrate($product, $data);
        $this->assertEquals('product name', $product->getName());
        $this->assertEquals('product description', $product->getDescription());
        $this->assertFalse((new ReflectionClass($product))->hasProperty('unkown_property'));
    }

    public function testCachePropertyIsNotEmpty(): void
    {
        $reflectionClass = new ReflectionClass(Hydrator::class);
        $prop = $reflectionClass->getStaticPropertyValue('cache');

        $this->assertNotEmpty($prop);
        $this->assertArrayHasKey(Product::class, $prop);

        $this->assertArrayHasKey('id', $prop[Product::class]);
        $this->assertInstanceOf(ReflectionProperty::class, $prop[Product::class]['id']);

        $this->assertArrayHasKey('name', $prop[Product::class]);
        $this->assertInstanceOf(ReflectionProperty::class, $prop[Product::class]['name']);

        $this->assertArrayHasKey('description', $prop[Product::class]);
        $this->assertInstanceOf(ReflectionProperty::class, $prop[Product::class]['description']);
    }

    public function testExtract(): void
    {
        $product = new Product();

        $product->setName('Linux Laptop')
            ->setDescription('A linux laptop');

        $expected = [
            'id' => null,
            'name' => 'Linux Laptop',
            'description' => 'A linux laptop'
        ];

        $result = (new Hydrator())->extract($product);


        $this->assertEquals($expected, $result);
    }

    public function testExtractInitializedOnly(): void
    {
        $product = new Person();

        $product->setName('Amanda');

        $expected = [
            'name' => 'Amanda',
            'description' => null
        ];
        
        $result = (new Hydrator())->extract($product);
       
        $this->assertEquals($expected, $result);
    }

    public function testExtractCached(): void
    {
        $product = new Product();

        $product->setId(1000)
            ->setName('Linux Laptop')
            ->setDescription('A linux laptop');

        $expected = [
            'id' => 1000,
            'name' => 'Linux Laptop',
            'description' => 'A linux laptop'
        ];

        $result = (new Hydrator())->extract($product);

        $this->assertEquals($expected, $result);
    }

    public function testGetPropertiesCache(): void
    {
        $reflectionClass = new ReflectionClass(Hydrator::class);
        $reflectionClass->setStaticPropertyValue('cache', []);

        $hydrator = new Hydrator();
        $this->assertEquals([], $reflectionClass->getStaticPropertyValue('cache'));

        $product = new Product();
        $hydrator->hydrate($product, [
            'name' => 'Laptop',
            'description' => 'A nice laptop'
        ]);

        $prop = $reflectionClass->getStaticPropertyValue('cache');
        $this->assertNotEmpty($prop);
    }
}
