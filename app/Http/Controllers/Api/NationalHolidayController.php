<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\NationalHoliday\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\NationalHoliday\NationalHolidayServiceInterface;
use App\Models\NationalHoliday;
use Illuminate\Support\Facades\Gate;

class NationalHolidayController extends BaseController
{
    public function __construct(private NationalHolidayServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', NationalHoliday::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [],
            [],
            ['id', 'name', 'date', 'created_at'],
        );

        return DefaultResource::collection($datas);
    }

    public function show(string $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', NationalHoliday::class);

        $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(string $id, StoreRequest $request)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }
}
