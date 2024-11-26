<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\NationalHoliday\StoreRequest;
use App\Http\Resources\NationalHoliday\NationalHolidayResource;
use App\Models\NationalHoliday;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NationalHolidayController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:national_holiday_access', ['only' => 'restore']);
        $this->middleware('permission:national_holiday_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:national_holiday_create', ['only' => 'store']);
        $this->middleware('permission:national_holiday_edit', ['only' => 'update']);
        $this->middleware('permission:national_holiday_delete', ['only' => 'destroy']);
    }

    public function index()
    {
        $nationalHoliday = QueryBuilder::for(NationalHoliday::class)
            ->allowedSorts([
                'id', 'name', 'date', 'created_at',
            ])
            ->paginate($this->per_page);

        return NationalHolidayResource::collection($nationalHoliday);
    }

    public function show(NationalHoliday $nationalHoliday)
    {
        return new NationalHolidayResource($nationalHoliday);
    }

    public function store(StoreRequest $request)
    {
        $nationalHoliday = NationalHoliday::create($request->validated());

        return new NationalHolidayResource($nationalHoliday);
    }

    public function update(NationalHoliday $nationalHoliday, StoreRequest $request)
    {
        $nationalHoliday->update($request->validated());

        return (new NationalHolidayResource($nationalHoliday))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(NationalHoliday $nationalHoliday)
    {
        $nationalHoliday->delete();

        return $this->deletedResponse();
    }
}
