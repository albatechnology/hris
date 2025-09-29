<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Level\LevelRequest;
use App\Http\Requests\Api\Level\LevelUpdateRequest;
use App\Http\Resources\Level\LevelResource;
use App\Interfaces\Services\Level\LevelServiceInterface;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends BaseController
{
    public function __construct(protected LevelServiceInterface $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $levels = $this->service->findAll();
         return LevelResource::collection($levels);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LevelRequest $request)
    {
        // dump($this->per_page);
        // dd($request->validated());
        $level = $this->service->create($request->validated());

       return (new LevelResource($level))
        ->response()
        ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $level = $this->service->findById($id);
        return new LevelResource($level);
    }

   

    /**
     * Update the specified resource in storage.
     */
    public function update(LevelUpdateRequest $request, int $id)
    {
        $this->service->findById($id);
        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->service->findById($id);
        $this->service->delete($id);
        return $this->deletedResponse();
    }
}
