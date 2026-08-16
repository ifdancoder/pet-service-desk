<?php

namespace App\Services;

use App\DataTransferObjects\TagData;
use App\Models\Tag;
use Illuminate\Support\Str;

class TagService
{
    public function create(TagData $data): Tag
    {
        return Tag::create([
            'name' => $data->name,
            'slug' => Str::slug($data->name),
        ]);
    }

    public function update(Tag $tag, TagData $data): Tag
    {
        $tag->update([
            'name' => $data->name,
            'slug' => Str::slug($data->name),
        ]);

        return $tag;
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }
}
