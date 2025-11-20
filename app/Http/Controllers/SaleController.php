<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    public function index(Request $r)
    {
        $q = Sale::query()
            ->with('customer')
            ->withSum('payments', 'amount')
            ->when($r->status, function ($qq) use ($r) {
                if ($r->status === 'pending') {
                    // Por cobrar: emitidas o parciales
                    $qq->whereIn('status', ['issued', 'partial']);
                } else {
                    $qq->where('status', $r->status);
                }
            })

            ->when($r->search, function ($qq) use ($r) {
                $qq->where(function ($w) use ($r) {
                    $w->where('number', 'like', "%{$r->search}%")
                        ->orWhereHas('customer', function ($qc) use ($r) {
                            $qc->where('name', 'like', "%{$r->search}%");
                        });
                });
            });

        $sales = $q->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales'   => $sales,
            'filters' => [
                'search' => $r->search,
                'status' => $r->status,
            ],
        ]);
    }

    /**
     * FORMULARIO NUEVA VENTA
     */
    public function create(Request $r)
    {
        $quotationId = $r->get('quotation_id');
        $quotation   = null;

        if ($quotationId) {
            $quotation = Quotation::with(['items.product', 'customer'])
                ->findOrFail($quotationId);
        }

        $customers = Customer::orderBy('name')
            ->get(['id', 'name', 'document_number', 'phone', 'email']);

        $products = Product::orderBy('name')
            ->get(['id', 'name', 'unit', 'price', 'tax_pct']);

        $quotations = Quotation::with('customer')
            ->orderByDesc('issue_date')
            ->take(30)
            ->get(['id', 'number', 'issue_date', 'customer_id', 'total', 'status']);

        // 👉 Generar siguiente número para la serie fija F001
        $series = 'F001';
        $lastNumber = Sale::where('series', $series)->max('number');
        $nextNumber = $lastNumber ? (int) $lastNumber + 1 : 1;
        $nextNumberFormatted = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        return Inertia::render('Sales/Create', [
            'customers'     => $customers,
            'quotation'     => $quotation,
            'quotations'    => $quotations,
            'products'      => $products,
            'defaultSeries' => $series,
            'nextNumber'    => $nextNumberFormatted,
        ]);
    }

    /**
     * GUARDAR NUEVA VENTA
     */
    public function store(Request $r)
    {
        // 1) Validar — ya NO validamos series/number porque los genera el back
        $validator = Validator::make($r->all(), [
            'customer_id'          => 'required|exists:customers,id',
            'quotation_id'         => 'nullable|exists:quotations,id',

            'issue_date'           => 'required|date',
            'due_date'             => 'nullable|date|after_or_equal:issue_date',
            'payment_term'         => 'nullable|string|max:50',
            'currency'             => 'required|string|max:3',

            'items'                => 'required|array|min:1',
            'items.*.description'  => 'required|string|max:255',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit_price'   => 'required|numeric|min:0.01',
            'items.*.discount'     => 'nullable|numeric|min:0',
            'items.*.tax_percent'  => 'nullable|numeric|min:0',

            'notes'                => 'nullable|string',

              // datos de pago opcionales
            'register_payment'  => ['nullable', 'boolean'],
            'payment_date'      => ['nullable', 'date'],
            'payment_amount'    => ['nullable', 'numeric', 'min:0'],
            'payment_method'    => ['nullable', 'string', 'max:30'],
            'payment_reference' => ['nullable', 'string', 'max:191'],
            'payment_notes'     => ['nullable', 'string'],

        ]);

        // 2) Regla extra: total > 0
        $validator->after(function ($v) use ($r) {
            $items = $r->input('items', []);
            $total = 0;

            foreach ($items as $it) {
                $qty   = (float)($it['quantity'] ?? 0);
                $price = (float)($it['unit_price'] ?? 0);
                $disc  = (float)($it['discount'] ?? 0);
                $taxP  = (float)($it['tax_percent'] ?? 18);

                $base    = max(0, $qty * $price - $disc);
                $lineTax = $base * ($taxP / 100);
                $total  += $base + $lineTax;
            }

            if ($total <= 0) {
                $v->errors()->add('items', 'El total de la venta debe ser mayor a 0.');
            }
        });

        $data = $validator->validate();

        // 3) Transacción
        return DB::transaction(function () use ($data) {
            $series = 'F001';

            $lastNumber = Sale::where('series', $series)->max('number');
            $nextNumber = $lastNumber ? (int) $lastNumber + 1 : 1;

            $subtotal     = 0;
            $taxTotal     = 0;
            $itemsPayload = [];

            foreach ($data['items'] as $item) {
                $qty        = $item['quantity'];
                $price      = $item['unit_price'];
                $discount   = $item['discount'] ?? 0;
                $taxPercent = $item['tax_percent'] ?? 18;

                $lineBase = ($qty * $price) - $discount;
                if ($lineBase < 0) {
                    $lineBase = 0;
                }

                $lineTax   = $lineBase * $taxPercent / 100;
                $lineTotal = $lineBase + $lineTax;

                $subtotal += $lineBase;
                $taxTotal += $lineTax;

                $itemsPayload[] = [
                    'description'  => $item['description'],
                    'quantity'     => $qty,
                    'unit_price'   => $price,
                    'discount'     => $discount,
                    'tax_percent'  => $taxPercent,
                    'total'        => $lineTotal,
                ];
            }

            $sale = Sale::create([
                'customer_id'  => $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? null,
                'series'       => $series,
                'number'       => $nextNumber,
                'issue_date'   => $data['issue_date'],
                'due_date'     => $data['due_date'] ?? null,
                'payment_term' => $data['payment_term'] ?? null,
                'currency'     => $data['currency'],
                'subtotal'     => $subtotal,
                'tax'          => $taxTotal,
                'total'        => $subtotal + $taxTotal,
                'status'       => 'issued',
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($itemsPayload as $itemData) {
                $sale->items()->create($itemData);
            }

            // Pago inicial opcional
            if (!empty($data['register_payment']) && $data['payment_amount'] !== null) {
                $amount = (float) $data['payment_amount'];

                if ($amount <= 0) {
                    // no creamos pago, pero seguimos normal
                } else {
                    if ($amount > $sale->total + 0.01) {
                        throw new \Exception('El monto del pago inicial no puede ser mayor al total de la venta.');
                    }

                    \App\Models\Payment::create([
                        'sale_id'      => $sale->id,
                        'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                        'amount'       => $amount,
                        'method'       => $data['payment_method'] ?? null,
                        'reference'    => $data['payment_reference'] ?? null,
                        'notes'        => $data['payment_notes'] ?? null,
                    ]);

                    $sale->refreshPaymentStatus();
                }
            }



            return redirect()
                ->route('sales.show', $sale)
                ->with('success', 'Venta creada correctamente.');
        });
    }

    public function show(Sale $sale)
    {
        $sale->load([
            'customer',
            'items',
            'payments',
        ]);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
        ]);
    }

    /**
     * EDITAR VENTA
     */
    public function edit(Sale $sale)
    {
        $sale->load(['customer', 'items', 'payments']);

        $customers = Customer::orderBy('name')
            ->get(['id', 'name', 'document_number', 'phone', 'email']);

        $products = Product::orderBy('name')
            ->get(['id', 'name', 'unit', 'price', 'tax_pct']);

        return Inertia::render('Sales/Edit', [
            'sale'      => $sale,
            'customers' => $customers,
            'products'  => $products,
        ]);
    }

    /**
     * ACTUALIZAR VENTA
     */
    public function update(Request $r, Sale $sale)
    {
        $validator = Validator::make($r->all(), [
            'customer_id'          => 'required|exists:customers,id',
            'issue_date'           => 'required|date',
            'due_date'             => 'nullable|date|after_or_equal:issue_date',
            'payment_term'         => 'nullable|string|max:50',
            'currency'             => 'required|string|max:3',

            'items'                => 'required|array|min:1',
            'items.*.description'  => 'required|string|max:255',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit_price'   => 'required|numeric|min:0.01',
            'items.*.discount'     => 'nullable|numeric|min:0',
            'items.*.tax_percent'  => 'nullable|numeric|min:0',

            'notes'                => 'nullable|string',
        ]);

        $validator->after(function ($v) use ($r) {
            $items = $r->input('items', []);
            $total = 0;

            foreach ($items as $it) {
                $qty   = (float)($it['quantity'] ?? 0);
                $price = (float)($it['unit_price'] ?? 0);
                $disc  = (float)($it['discount'] ?? 0);
                $taxP  = (float)($it['tax_percent'] ?? 18);

                $base    = max(0, $qty * $price - $disc);
                $lineTax = $base * ($taxP / 100);
                $total  += $base + $lineTax;
            }

            if ($total <= 0) {
                $v->errors()->add('items', 'El total de la venta debe ser mayor a 0.');
            }
        });

        $data = $validator->validate();

        DB::transaction(function () use ($data, $sale) {
            $subtotal     = 0;
            $taxTotal     = 0;
            $itemsPayload = [];

            foreach ($data['items'] as $item) {
                $qty        = $item['quantity'];
                $price      = $item['unit_price'];
                $discount   = $item['discount'] ?? 0;
                $taxPercent = $item['tax_percent'] ?? 18;

                $lineBase = ($qty * $price) - $discount;
                if ($lineBase < 0) {
                    $lineBase = 0;
                }

                $lineTax   = $lineBase * $taxPercent / 100;
                $lineTotal = $lineBase + $lineTax;

                $subtotal += $lineBase;
                $taxTotal += $lineTax;

                $itemsPayload[] = [
                    'description'  => $item['description'],
                    'quantity'     => $qty,
                    'unit_price'   => $price,
                    'discount'     => $discount,
                    'tax_percent'  => $taxPercent,
                    'total'        => $lineTotal,
                ];
            }

            // actualizar cabecera (serie y número no se tocan)
            $sale->update([
                'customer_id'  => $data['customer_id'],
                'issue_date'   => $data['issue_date'],
                'due_date'     => $data['due_date'] ?? null,
                'payment_term' => $data['payment_term'] ?? null,
                'currency'     => $data['currency'],
                'subtotal'     => $subtotal,
                'tax'          => $taxTotal,
                'total'        => $subtotal + $taxTotal,
                'notes'        => $data['notes'] ?? null,
            ]);

            // reemplazar items
            $sale->items()->delete();
            foreach ($itemsPayload as $itemData) {
                $sale->items()->create($itemData);
            }
        });

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Venta actualizada correctamente.');
    }

    public function pdf(Sale $sale)
    {
        // Cargamos relaciones necesarias
        $sale->load(['customer', 'items', 'payments']);

        // Renderizamos la vista que ya hiciste: resources/views/pdf/sale-contract.blade.php
        $pdf = Pdf::loadView('pdf.sale-contract', [
            'sale' => $sale,
        ])->setPaper('a4');

        // stream() => abre en nueva pestaña, el navegador ya muestra botón de descargar
        $fileName = 'Venta-' . $sale->series . '-' . str_pad($sale->number, 6, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->stream($fileName);
    }


    public function destroy(Sale $sale)
{
    DB::transaction(function () use ($sale) {
        // Borrar pagos relacionados (si existe la relación payments)
        if (method_exists($sale, 'payments')) {
            $sale->payments()->delete();
        }

        // Borrar ítems relacionados (detalle de la venta)
        if (method_exists($sale, 'items')) {
            $sale->items()->delete();
        }

        // Finalmente borrar la venta
        $sale->delete();
    });

    return redirect()
        ->route('sales.index')
        ->with('success', 'Venta eliminada correctamente.');
}




}
