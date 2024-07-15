<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\DataMapper\Entity;

class PostTag
{
    private int $post_id;
    private int $tag_id;

    public function getPostId(): int
    {
        return $this->post_id;
    }

    public function setPostId(int $post_id): self
    {
        $this->post_id = $post_id;

        return $this;
    }

    public function getTagId(): int
    {
        return $this->tag_id;
    }

    public function setTagId(int $tag_id): self
    {
        $this->tag_id = $tag_id;

        return $this;
    }
}
