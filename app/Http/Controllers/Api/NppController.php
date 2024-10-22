<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Npp\StoreRequest;
use App\Http\Requests\Api\Npp\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Npp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NppController extends BaseController
{
  public function __construct()
  {
    parent::__construct();

    // $this->middleware('permission:npp_access', ['only' => ['restore']]);
    // $this->middleware('permission:npp_read', ['only' => ['index', 'show']]);
    // $this->middleware('permission:npp_create', ['only' => 'store']);
    // $this->middleware('permission:npp_edit', ['only' => 'update']);
    // $this->middleware('permission:npp_delete', ['only' => ['destroy', 'forceDelete']]);
  }

  public function index(): ResourceCollection
  {
    $data = QueryBuilder::for(Npp::tenanted())
      ->allowedFilters([
        AllowedFilter::exact('id'),
        AllowedFilter::exact('company_id'),
      ])
      ->allowedIncludes(['company'])
      ->allowedSorts([
        'id',
        'company_id',
        'name',
        'number',
        'jkk_tier',
        'created_at',
      ])
      ->paginate($this->per_page);

    return DefaultResource::collection($data);
  }

  public function show(Npp $npp): DefaultResource
  {
    return new DefaultResource($npp);
  }

  public function store(StoreRequest $request): DefaultResource|JsonResponse
  {
    DB::beginTransaction();
    try {
      $npp = Npp::create($request->validated());

      DB::commit();
    } catch (\Exception $th) {
      DB::rollBack();

      return $this->errorResponse($th->getMessage());
    }

    return new DefaultResource($npp->refresh());
  }

  public function update(Npp $npp, UpdateRequest $request): DefaultResource|JsonResponse
  {
    DB::beginTransaction();
    try {
      $npp->update($request->validated());

      DB::commit();
    } catch (\Exception $th) {
      DB::rollBack();

      return $this->errorResponse($th->getMessage());
    }

    return (new DefaultResource($npp->refresh()))->response()->setStatusCode(Response::HTTP_ACCEPTED);
  }

  public function destroy(Npp $npp): JsonResponse
  {
    DB::beginTransaction();
    try {
      $npp->delete();

      DB::commit();
    } catch (\Exception $th) {
      DB::rollBack();

      return $this->errorResponse($th->getMessage());
    }

    return $this->deletedResponse();
  }
}
