<?php

namespace App\Http\Controllers;

use App\Http\Requests\MassDestroyRequest;
use App\Http\Requests\Api\Subscription\StoreRequest;
use App\Http\Requests\Api\Subscription\UpdateRequest;
use App\Http\Services\Subscription\SubscriptionService;
use App\Models\Subscription;
use App\View\Components\Datatables\DatatableAction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use DataTables;

class SubscriptionController extends Controller
{

    public function __construct(public Subscription $model)
    {
        $this->middleware('permission:subscription_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:subscription_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:subscription_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:subscription_delete', ['only' => ['destroy', 'massDestroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $query = $this->model->query()->with([
                'group' => fn($q) => $q->select('id', 'name'),
                'user' => fn($q) => $q->select('id', 'name'),
            ]);

            $table = DataTables::of($query);

            $table->addColumn('checkbox', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                return Blade::renderComponent(new DatatableAction(
                    showRoute: route('subscriptions.show', $row->id),
                    editRoute: route('subscriptions.edit', $row->id),
                    // destroyRoute: route('subscriptions.destroy', $row->id),
                    access: 'subscription_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('subscriptions.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('subscriptions.create', [
            'model' => $this->model,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            app(SubscriptionService::class)->create($request->validated());

            $alert['success'] = self::CREATED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('subscriptions.index')->with(key($alert), current($alert));
    }

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription): View
    {
        $subscription->load([
            'user' => fn($q) => $q->select('id', 'name', 'email'),
            'group' => fn($q) => $q->select('id', 'name'),
            'payments' => fn($q) => $q->orderByDesc('id'),
        ]);

        return view('subscriptions.show', [
            'model' => $subscription,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription): View
    {
        return view('subscriptions.edit', [
            'model' => $subscription->load('group', 'user'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Subscription $subscription)
    {
        try {
            $subscription->update($request->validated());

            $alert['success'] = self::UPDATED_MESSAGE;
        } catch (Exception $th) {
            $alert['error'] = $th->getMessage();
        }

        return to_route('subscriptions.index')->with(key($alert), current($alert));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription): RedirectResponse
    {
        try {
            $subscription->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('subscriptions.index')->with(key($alert), current($alert));
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
