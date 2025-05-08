<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DefaultResource;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MediaController extends BaseController
{
    public function index()
    {
        $data = QueryBuilder::for(Media::query())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'model_type',
                'model_id',
                'uuid',
                'collection_name',
                'name',
            ])
            ->allowedSorts([
                'id',
                'model_type',
                'model_id',
                'uuid',
                'collection_name',
                'name',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $media = Media::findOrFail($id);
        return new DefaultResource($media);
    }

    public function destroy(int $id)
    {
        $media = Media::findOrFail($id);
        $media->delete();

        return $this->deletedResponse();
    }

    public function bulkDestroy(Request $request)
    {
        $ids = explode(',', $request->ids);
        foreach ($ids as $id) {
            $media = Media::find($id);
            $media?->delete();
        }

        return $this->deletedResponse();
    }
}
