<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\DataMapper\DataMapper;

use Lightning\DataMapper\AbstractDataMapper;
use Lightning\Test\TestCase\DataMapper\Entity\PostTag;

class PostTagDataMapper extends AbstractDataMapper
{
    protected string|array $primaryKey = ['post_id','tag_id'];
    protected string $table = 'posts_tags';
    protected array $fields = [
        'post_id','tag_id'
    ];

    public function createEntity(): PostTag
    {
        return new PostTag();
    }
}
