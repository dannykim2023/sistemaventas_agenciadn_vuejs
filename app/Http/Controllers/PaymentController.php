<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Payment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $r)
    {
        $q = Payment::query()
            ->with(['sale.customer']) // para mostrar serie-número y cliente
            ->when($r->search, function ($qq) use ($r) {
                $term = $r->search;
                $qq->where('reference', 'like', "%{$term}%")
                   ->orWhereHas('sale.customer', function ($qc) use ($term) {
                       $qc->where('name', 'like', "%{$term}%");
                   });
            });

        $payments = $q->latest('payment_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters'  => [
                'search' => $r->search,
            ],
        ]);
    }

    public function create(Request $r)
    {
        $customers = Customer::orderBy('name')
            ->get(['id','name','document_number']);

        // Ventas con saldo pendiente
        $sales = Sale::with('customer')
            ->withSum('payments', 'amount')
            ->get()
            ->map(function ($s) {
                $paid    = $s->payments_sum_amount ?? 0;
                $balance = max(0, $s->total - $paid);

                return [
                    'id'        => $s->id,
                    'series'    => $s->series,
                    'number'    => $s->number,
                    'issue_date'=> $s->issue_date,
                    'total'     => $s->total,
                    'paid'      => $paid,
                    'balance'   => $balance,
                    'customer'  => [
                        'id'   => $s->customer_id,
                        'name' => $s->customer->name ?? '—',
                    ],
                ];
            })
            ->filter(fn ($s) => $s['balance'] > 0)
            ->values();

        return Inertia::render('Payments/Create', [
            'customers' => $customers,
            'sales'     => $sales,
        ]);
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'payment_date' => ['required', 'date'],
        'amount'       => ['required', 'numeric', 'min:0.01'],
        'method'       => ['nullable', 'string', 'max:30'],
        'reference'    => ['nullable', 'string', 'max:191'],
        'notes'        => ['nullable', 'string'],

        'sale_id'      => ['nullable', 'exists:sales,id'],
        // NO existe customer_id ni currency, así que no se validan
    ]);

    return DB::transaction(function () use ($data) {
        $sale = null;

        if (!empty($data['sale_id'])) {
            $sale = Sale::withSum('payments', 'amount')->findOrFail($data['sale_id']);

            $paid    = $sale->payments_sum_amount ?? 0;
            $balance = $sale->total - $paid;

            if ($data['amount'] > $balance + 0.01) {
                throw new \Exception('El monto no puede ser mayor al saldo pendiente.');
            }
        }

        // asegurarse que NO llegue currency
        unset($data['currency']);
        unset($data['customer_id']);

        Payment::create($data);

        if ($sale) {
            $sale->refreshPaymentStatus();
        }

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pago registrado correctamente.');
    });
}

    
    public function edit(Payment $payment)
    {
        $payment->load('sale.customer');

        $customers = Customer::orderBy('name')
            ->get(['id','name','document_number']);

        $sales = Sale::with('customer')
            ->withSum('payments', 'amount')
            ->get()
            ->map(function ($s) {
                $paid    = $s->payments_sum_amount ?? 0;
                $balance = max(0, $s->total - $paid);

                return [
                    'id'        => $s->id,
                    'series'    => $s->series,
                    'number'    => $s->number,
                    'issue_date'=> $s->issue_date,
                    'total'     => $s->total,
                    'paid'      => $paid,
                    'balance'   => $balance,
                    'customer'  => [
                        'id'   => $s->customer_id,
                        'name' => $s->customer->name ?? '—',
                    ],
                ];
            });

        return Inertia::render('Payments/Edit', [
            'payment'   => $payment,
            'customers' => $customers,
            'sales'     => $sales,
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'payment_date'   => ['required', 'date'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'method'         => ['nullable', 'string', 'max:30'],
            'reference'      => ['nullable', 'string', 'max:191'],
            'notes'          => ['nullable', 'string'],
            'currency'       => ['required', 'string', 'max:3'],
            'customer_id'    => ['nullable', 'exists:customers,id'],
            'sale_id'        => ['nullable', 'exists:sales,id'],
        ]);

        return DB::transaction(function () use ($data, $payment) {
            $oldSale = $payment->sale_id ? Sale::find($payment->sale_id) : null;
            $newSale = null;

            if (!empty($data['sale_id'])) {
                $newSale = Sale::with('payments')->findOrFail($data['sale_id']);

                // recalculamos saldo sin este pago (por si estaba asociado)
                $paidWithoutThis = $newSale->payments()
                    ->where('id', '!=', $payment->id)
                    ->sum('amount');

                $balance = max(0, $newSale->total - $paidWithoutThis);

                if ($data['amount'] > $balance + 0.01) {
                    return back()
                        ->withErrors([
                            'amount' => 'El monto no puede ser mayor al saldo pendiente (S/ ' . number_format($balance, 2) . ').',
                        ])
                        ->withInput();
                }

                $data['customer_id'] = $newSale->customer_id;
            }

            $payment->update($data);

            if ($oldSale) {
                $oldSale->refreshPaymentStatus();
            }
            if ($newSale && (!$oldSale || $oldSale->id !== $newSale->id)) {
                $newSale->refreshPaymentStatus();
            }

            return redirect()
                ->route('payments.index')
                ->with('success', 'Pago actualizado correctamente.');
        });
    }

    public function destroy(Payment $payment)
    {
        $sale = $payment->sale;

        $payment->delete();

        if ($sale) {
            $sale->refreshPaymentStatus();
        }

        return back()->with('success', 'Pago eliminado.');
    }
}
