<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\MassDestroyRequest;
use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Models\User;
use App\View\Components\Datatables\DatatableAction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use DataTables;

class UserController extends Controller
{
    // constructor
    public function __construct(public User $model)
    {
        $this->middleware('permission:user_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:user_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:user_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:user_delete', ['only' => ['destroy', 'massDestroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $query = $this->model->query();
            $table = DataTables::of($query);

            $table->addColumn('checkbox', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                return Blade::renderComponent(new DatatableAction(
                    showRoute: route('users.show', $row->id),
                    editRoute: route('users.edit', $row->id),
                    destroyRoute: route('users.destroy', $row->id),
                    access: 'user_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('users.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('users.create', [
            'model' => $this->model,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->model->create($request->all());

            $alert['success'] = self::CREATED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('users.index')->with(key($alert), current($alert));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        return view('users.show', [
            'model' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        return view('users.edit', [
            'model' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, User $user): RedirectResponse
    {
        try {
            $user->update($request->except('password'));

            if ($request->password) {
                $user->update(['password' => Hash::make($request->password)]);
            }

            $alert['success'] = self::UPDATED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('users.index')->with(key($alert), current($alert));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            $user->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('users.index')->with(key($alert), current($alert));
    }

    /**
     * Remove the mass resource from storage.
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        try {
            $this->model->whereIn('id', $request->ids)->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return $this->jsonResponse(key($alert), current($alert));
    }
}
