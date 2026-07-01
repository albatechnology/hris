<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\GuestBook\StoreRequest;
use App\Http\Requests\Api\GuestBook\UpdateRequest;
use App\Http\Requests\Api\GuestBook\ExportRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\GuestBook\GuestBookServiceInterface;
use App\Models\GuestBook;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;

class GuestBookController extends BaseController
{
    public function __construct(private GuestBookServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return [
            AllowedInclude::callback('branch', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('user', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('checkOutBy', function ($query) {
                $query->select('id', 'name');
            })
        ];
    }

    public function index()
    {
        Gate::authorize('viewAny', GuestBook::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->tenanted(),
            [
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
            ],
            $this->allowedIncludes(),
            [
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
            ],
            ['id', 'name'],
        );

        return DefaultResource::collection($datas);
    }

    public function show(string $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new DefaultResource($data->load([
            'branch' => fn($q) => $q->select('id', 'name'),
            'user' => fn($q) => $q->select('id', 'name'),
            'checkOutBy' => fn($q) => $q->select('id', 'name'),
        ]));
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', GuestBook::class);

        $data = $request->validated();
        $data['files'] = $request->file('files');

        $this->service->create($data);

        return $this->createdResponse();
    }

    public function update(string $id, UpdateRequest $request)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $updateData = [];
        $updateData['files'] = $request->file('files');

        $this->service->update($id, $updateData);

        return $this->updatedResponse();
    }

    public function destroy(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $data);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $data);

        $this->service->restore($id);

        return $this->restoredResponse();
    }

    public function export(ExportRequest $request)
    {
        $filters = $this->request['filter'] ?? [];
        $guestBooks = $this->service->export($filters);

        $html = view('api.exports.guest-book.guest-book', compact('guestBooks'))->render();

        $filename = 'guest books.xls';
        if (isset($filters['check_in_start_date']) && isset($filters['check_in_end_date'])) {
            $filename = 'guest books ' . $filters['check_in_start_date'] . ' to ' . $filters['check_in_end_date'] . '.xls';
        }

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }
}
