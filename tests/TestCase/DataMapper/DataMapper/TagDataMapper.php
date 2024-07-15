<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\DataMapper\DataMapper;

use Lightning\DataMapper\AbstractDataMapper;
use Lightning\Test\TestCase\DataMapper\Entity\Tag;

class TagDataMapper extends AbstractDataMapper
{
    protected string|array $primaryKey = 'id';
    protected string $table = 'tags';
    protected array $fields = [
        'id', 'name','created_at','updated_at'
    ];

    public function createEntity(): Tag
    {
        return new Tag();
    }
}
