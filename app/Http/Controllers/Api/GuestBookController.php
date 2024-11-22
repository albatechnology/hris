<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Http\Requests\Api\GuestBook\StoreRequest;
use App\Http\Requests\Api\GuestBook\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\GuestBook;
use Exception;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class GuestBookController extends BaseController
{
    // public function __construct()
    // {
    //     parent::__construct();
    //     $this->middleware('permission:guest_book_access', ['only' => ['restore']]);
    //     $this->middleware('permission:guest_book_read', ['only' => ['index', 'show']]);
    //     $this->middleware('permission:guest_book_create', ['only' => 'store']);
    //     $this->middleware('permission:guest_book_edit', ['only' => 'update']);
    //     $this->middleware('permission:guest_book_delete', ['only' => ['destroy', 'forceDelete']]);
    // }

    public function index()
    {
        $data = QueryBuilder::for(GuestBook::tenanted())
            ->allowedIncludes([
                AllowedInclude::callback('client', function ($query) {
                    $query->select('id', 'name');
                }),
                AllowedInclude::callback('user', function ($query) {
                    $query->select('id', 'name', 'last_name');
                }),
                AllowedInclude::callback('checkOutBy', function ($query) {
                    $query->select('id', 'name', 'last_name');
                })
            ])
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('client_id'),
                AllowedFilter::scope('created_at_start', 'createdAtStart'),
                AllowedFilter::scope('created_at_end', 'createdAtEnd'),
                'is_check_out',
                'name',
                'address',
                'location_destination',
                'room',
                'person_destination',
                'description',
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'client_id',
                'is_check_out',
                'name',
                'address',
                'location_destination',
                'room',
                'person_destination',
                'description',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(GuestBook $guestBook)
    {
        return new DefaultResource($guestBook->load([
            'client' => fn($q) => $q->select('id', 'name'),
            'user' => fn($q) => $q->select('id', 'name', 'last_name'),
            'checkOutBy' => fn($q) => $q->select('id', 'name', 'last_name'),
        ]));
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $guestBook = GuestBook::create($request->validated());

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    if ($file->isValid()) {
                        $guestBook->addMedia($file)->toMediaCollection(MediaCollection::GUEST_BOOK_CHECK_IN->value);
                    }
                }
            }
            DB::commit();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
            DB::rollBack();
        }

        return $this->createdResponse();
    }

    public function update(GuestBook $guestBook, UpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $guestBook->update([
                'is_check_out' => true,
                'check_out_at' => now(),
                'check_out_by' => auth()->id(),
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    if ($file->isValid()) {
                        $guestBook->addMedia($file)->toMediaCollection(MediaCollection::GUEST_BOOK_CHECK_OUT->value);
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
            DB::rollBack();
        }

        return $this->updatedResponse();
    }

    public function destroy(GuestBook $guestBook)
    {
        $guestBook->delete();

        return $this->deletedResponse();
    }

    // public function forceDelete($id)
    // {
    //     $guestBook = GuestBook::withTrashed()->findOrFail($id);
    //     $guestBook->forceDelete();

    //     return $this->deletedResponse();
    // }

    // public function restore($id)
    // {
    //     $guestBook = GuestBook::withTrashed()->findOrFail($id);
    //     $guestBook->restore();

    //     return new DefaultResource($guestBook);
    // }
}