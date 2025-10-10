<?php

namespace App\Http\Controllers;

use App\Http\Requests\MassDestroyRequest;
use App\Http\Requests\Payment\StoreRequest;
use App\Http\Requests\Payment\UpdateRequest;
use App\Models\Payment;
use App\Models\Subscription;
use App\View\Components\Datatables\DatatableAction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use DataTables;

class PaymentController extends Controller
{

    public function __construct(public Payment $model)
    {
        $this->middleware('permission:payment_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:payment_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:payment_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:payment_delete', ['only' => ['destroy', 'massDestroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $query = $this->model->query()->with([
                'subscription' => fn($q) => $q->with([
                    'group' => fn($q) => $q->select('id', 'name'),
                    'user' => fn($q) => $q->select('id', 'name'),
                ]),
            ]);

            $table = DataTables::of($query);

            $table->addColumn('checkbox', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                return Blade::renderComponent(new DatatableAction(
                    // showRoute: route('payments.show', $row->id),
                    // editRoute: route('payments.edit', $row->id),
                    destroyRoute: route('payments.destroy', $row->id),
                    access: 'payment_access',
                ));
            });

            $table->rawColumns(['checkbox', 'actions']);

            return $table->make(true);
        }

        return view('payments.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('payments.create', [
            'model' => $this->model,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->model->create($request->validated());
            Subscription::where('id', $request->subscription_id)->update(['active_end_date' => $request->active_end_date]);

            $alert['success'] = self::CREATED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }
        return to_route('payments.index')->with(key($alert), current($alert));
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment): View
    {
        return view('payments.show', [
            'model' => $payment,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment): View
    {
        return view('payments.edit', [
            'model' => $payment,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Payment $payment)
    {
        try {
            $payment->update($request->validated());

            $alert['success'] = self::UPDATED_MESSAGE;
        } catch (\Exception $th) {
            $alert['error'] = $th->getMessage();
        }

        return to_route('payments.index')->with(key($alert), current($alert));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        try {
            $payment->delete();

            $alert['success'] = self::DELETED_MESSAGE;
        } catch (Exception $e) {
            $alert['error'] = $e->getMessage();
        }

        return to_route('payments.index')->with(key($alert), current($alert));
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
