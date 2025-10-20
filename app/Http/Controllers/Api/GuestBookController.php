<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\GuestBook\StoreRequest;
use App\Http\Requests\Api\GuestBook\UpdateRequest;
use App\Http\Requests\Api\GuestBook\ExportRequest;
use App\Http\Resources\DefaultResource;
use App\Models\GuestBook;
use Exception;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

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
                AllowedInclude::callback('branch', function ($query) {
                    $query->select('id', 'name');
                }),
                AllowedInclude::callback('user', function ($query) {
                    $query->select('id', 'name');
                }),
                AllowedInclude::callback('checkOutBy', function ($query) {
                    $query->select('id', 'name');
                })
            ])
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('branch_id'),
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
                'branch_id',
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

    public function show(int $id)
    {
        $guestBook = GuestBook::findTenanted($id);

        return new DefaultResource($guestBook->load([
            'branch' => fn($q) => $q->select('id', 'name'),
            'user' => fn($q) => $q->select('id', 'name'),
            'checkOutBy' => fn($q) => $q->select('id', 'name'),
        ]));
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $guestBook = GuestBook::create($request->validated());

            // if ($request->hasFile('files')) {
            //     foreach ($request->file('files') as $file) {
            //         if ($file->isValid()) {
            //             $guestBook->addMedia($file)->toMediaCollection(MediaCollection::GUEST_BOOK_CHECK_IN->value);
            //         }
            //     }
            // }

            if ($request->hasFile('files')) {
                $manager = new ImageManager(new Driver()); // ✅ pakai GD atau Imagick

                foreach ($request->file('files') as $file) {
                    if ($file->isValid()) {
                        // Resize & compress
                        $optimized = $manager->read($file)
                            ->scaleDown(1280)
                            ->encode(new \Intervention\Image\Encoders\JpegEncoder(quality: 60));
                        // dd($optimized);

                        // Upload hasil optimized langsung ke S3
                        $guestBook
                            ->addMediaFromStream($optimized)
                            ->usingFileName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg')
                            ->toMediaCollection(\App\Enums\MediaCollection::GUEST_BOOK_CHECK_IN->value);
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

    public function update(int $id, UpdateRequest $request)
    {
        $guestBook = GuestBook::findTenanted($id);

        DB::beginTransaction();
        try {
            $guestBook->update([
                'is_check_out' => true,
                'check_out_at' => now(),
                'check_out_by' => auth()->id(),
            ]);

            if ($request->hasFile('files')) {
                $manager = new ImageManager(new Driver()); // ✅ pakai GD atau Imagick

                foreach ($request->file('files') as $file) {
                    if ($file->isValid()) {
                        // Resize & compress
                        $optimized = $manager->read($file)
                            ->scaleDown(1280)
                            ->encode(new \Intervention\Image\Encoders\JpegEncoder(quality: 60));
                        // dd($optimized);

                        // Upload hasil optimized langsung ke S3
                        $guestBook
                            ->addMediaFromStream($optimized)
                            ->usingFileName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg')
                            ->toMediaCollection(\App\Enums\MediaCollection::GUEST_BOOK_CHECK_OUT->value);
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

    public function destroy(int $id)
    {
        $guestBook = GuestBook::findTenanted($id);
        $guestBook->delete();

        return $this->deletedResponse();
    }

    public function export(ExportRequest $request)
    {
        return (new \App\Exports\GuestBook\ExportGuestBook($request))->download('guest-books.xlsx');
    }

    // public function forceDelete(int $id)
    // {
    //     $guestBook = GuestBook::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
    //     $guestBook->forceDelete();

    //     return $this->deletedResponse();
    // }

    // public function restore(int $id)
    // {
    //     $guestBook = GuestBook::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
    //     $guestBook->restore();

    //     return new DefaultResource($guestBook);
    // }
}
