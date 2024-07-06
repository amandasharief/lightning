<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Hydrator;

use ReflectionClass;
use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use Lightning\Hydrator\Hydrator;

class ProductEntity
{
    private int|null $id = null;
    private string $name;
    private ?string $description = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setId(int|null $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
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
        $product = new ProductEntity();

        $data = [
            'name' => 'product name',
            'description' => 'product description',
            'unkown_property' => 'foo'
        ];

        (new Hydrator())->hydrate($product, $data);

        $this->assertEquals('product name', $product->getName());
        $this->assertEquals('product description', $product->getDescription());
    }

    public function testCachePropertyIsNotEmpty(): void
    {
        $reflectionClass = new ReflectionClass(Hydrator::class);
        $prop = $reflectionClass->getStaticPropertyValue('cache');

        $this->assertNotEmpty($prop);
        $this->assertArrayHasKey(ProductEntity::class, $prop);

        $this->assertArrayHasKey('id', $prop[ProductEntity::class]);
        $this->assertInstanceOf(ReflectionProperty::class, $prop[ProductEntity::class]['id']);

        $this->assertArrayHasKey('name', $prop[ProductEntity::class]);
        $this->assertInstanceOf(ReflectionProperty::class, $prop[ProductEntity::class]['name']);

        $this->assertArrayHasKey('description', $prop[ProductEntity::class]);
        $this->assertInstanceOf(ReflectionProperty::class, $prop[ProductEntity::class]['description']);
    }

    public function testExtract(): void
    {
        $product = new ProductEntity();

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

    public function testExtractCached(): void
    {
        $product = new ProductEntity();

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

        $product = new ProductEntity();
        $hydrator->hydrate($product, [
            'name' => 'Laptop',
            'description' => 'A nice laptop'
        ]);

        $prop = $reflectionClass->getStaticPropertyValue('cache');
        $this->assertNotEmpty($prop);
    }
}
