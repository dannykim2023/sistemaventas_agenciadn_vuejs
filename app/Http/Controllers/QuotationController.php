<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\{Quotation, QuotationItem, Customer, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuotationController extends Controller
{
    /* =========================================================
     * LISTADO
     * ======================================================= */
    public function index(Request $r)
    {
        $q = Quotation::with('customer')
            ->when($r->status, fn ($qq) => $qq->where('status', $r->status))
            ->when($r->search, function ($qq) use ($r) {
                $search = $r->search;

                $qq->where(function($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")   // Buscar por correlativo
                    ->orWhereHas('customer', function($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%")              // Nombre del cliente
                            ->orWhere('document_number', 'like', "%{$search}%"); // DNI/RUC
                    });
                });
            });

        return Inertia::render('Quotations/Index', [
            'quotations' => $q->latest()->paginate(15)->withQueryString(),
            'filters'    => [
                'search' => $r->search,
                'status' => $r->status,
            ],
            // Para filtros en el modal de edición simple
            'customers'  => Customer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /* =========================================================
     * FORMULARIO: CREAR
     * ======================================================= */
    public function create()
    {
        // Clientes con alias que espera Vue
        $customers = Customer::query()
            ->select([
                'id',
                'name',
                'document_number as tax_id', // RUC/DNI
                'phone',
            ])
            ->orderBy('name')
            ->get();

        // Productos usados en el formulario
        $products = Product::query()
            ->select([
                'id',
                'name',
                'unit',
                'price',
                'tax_pct', // decimal: 0.18 = 18%
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('Quotations/Create', [
            'customers' => $customers,
            'products'  => $products,
        ]);
    }

    /* =========================================================
     * GUARDAR NUEVA COTIZACIÓN
     * ======================================================= */
    public function store(Request $request)
    {
        // 1) Validación básica
        $data = $request->validate([
            'customer_id'          => ['required', 'exists:customers,id'],
            'issue_date'           => ['required', 'date'],
            'valid_until'          => ['nullable', 'date'],
            'currency'             => ['required', 'in:PEN,USD'],
            'exchange_rate'        => ['nullable', 'numeric'],
            'notes'                => ['nullable', 'string'],
            'terms'                => ['nullable', 'string'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['nullable', 'exists:products,id'],
            'items.*.description'  => ['required', 'string'],
            'items.*.unit'         => ['required', 'string', 'max:10'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0'],
            'items.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'items.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'], // 0–100 %
            'items.*.tax_pct'      => ['nullable', 'numeric', 'min:0', 'max:1'],   // 0–1 (0.18)
        ]);

        DB::transaction(function () use ($data) {

            /* 2) Generamos un correlativo simple para `number`
             *    Ej: COT-0001, COT-0002, etc.
             *    (Puedes mejorar este generador luego con series por año, etc.)
             */
            $nextNumber = 'COT-' . str_pad((Quotation::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);

            // 3) Creamos la cabecera con totales en 0 (se recalculan luego)
            $quotation = Quotation::create([
                'number'         => $nextNumber,
                'customer_id'    => $data['customer_id'],
                'issue_date'     => $data['issue_date'],
                'valid_until'    => $data['valid_until'] ?? $data['issue_date'],
                'currency'       => $data['currency'],
                'exchange_rate'  => $data['exchange_rate'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'terms'          => $data['terms'] ?? null,
                'subtotal'       => 0,
                'discount_total' => 0,
                'tax_total'      => 0,
                'total'          => 0,
                'status'         => 'draft', // estado inicial
            ]);

            // 4) Calculamos totales mientras creamos los items
            $subtotal = 0;
            $discount = 0;
            $tax      = 0;

            foreach ($data['items'] as $item) {
                $qty   = $item['quantity']     ?? 0;
                $price = $item['unit_price']   ?? 0;
                $discP = $item['discount_pct'] ?? 0;   // porcentaje 0–100
                $igvP  = $item['tax_pct']      ?? 0;   // decimal, ejemplo 0.18

                $base    = $qty * $price;              // cantidad * precio
                $disc    = $base * ($discP / 100);     // descuento en dinero
                $after   = $base - $disc;              // base después del desc
                $lineTax = $after * $igvP;             // IGV línea (0.18 = 18%)

                $subtotal += $base;
                $discount += $disc;
                $tax      += $lineTax;

                QuotationItem::create([
                    'quotation_id'    => $quotation->id,
                    'product_id'      => $item['product_id'] ?? null,
                    'description'     => $item['description'],
                    'unit'            => $item['unit'],
                    'quantity'        => $qty,
                    'unit_price'      => $price,
                    'discount_pct'    => $discP,
                    'discount_amount' => $disc,        // si tienes este campo
                    'tax_pct'         => $igvP,
                    'tax_amount'      => $lineTax ?? 0,
                    'total'           => $after + $lineTax, // total línea con IGV
                ]);
            }

            $total = $subtotal - $discount + $tax;

            // 5) Actualizamos totales en la cabecera (ya con IGV)
            $quotation->update([
                'subtotal'       => $subtotal,
                'discount_total' => $discount,
                'tax_total'      => $tax,
                'total'          => $total,
            ]);
        });

        return redirect()
            ->route('quotations.index')
            ->with('success', 'Cotización creada correctamente.');
    }

    /* =========================================================
     * ELIMINAR
     * ======================================================= */
    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return redirect()
            ->route('quotations.index')
            ->with('success', 'Cotización eliminada');
    }

    /* =========================================================
     * PDF
     * ======================================================= */
    public function pdf(Quotation $quotation)
    {
        // Cargar relaciones necesarias
        $quotation->load(['customer', 'items']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
        ])->setPaper('a4');

        $filename = 'Cotizacion-' . ($quotation->number ?? $quotation->id) . '.pdf';

        return $pdf->download($filename);
        // Si prefieres mostrarlo:
        // return $pdf->stream($filename);
    }

    /* =========================================================
     * FORMULARIO: EDITAR
     * ======================================================= */
    public function edit(Quotation $quotation)
    {
        // Clientes en el mismo formato que create()
        $customers = Customer::query()
            ->select([
                'id',
                'name',
                'document_number as tax_id',
                'phone',
            ])
            ->orderBy('name')
            ->get();

        // Productos en el mismo formato que create()
        $products = Product::query()
            ->select([
                'id',
                'name',
                'unit',
                'price',
                'tax_pct',
            ])
            ->orderBy('name')
            ->get();

        // Items ordenados + cliente
        $quotation->load([
            'items' => function ($q) {
                $q->orderBy('sort_order'); // si no tienes sort_order, cambia a id
            },
            'customer',
        ]);

        return Inertia::render('Quotations/Edit', [
            'quotation' => $quotation,
            'customers' => $customers,
            'products'  => $products,
        ]);
    }

    /* =========================================================
     * ACTUALIZAR COTIZACIÓN
     * ======================================================= */
    public function update(Request $request, Quotation $quotation)
    {
        // 1) Validar igual que en store()
        $data = $request->validate([
            'customer_id'          => ['required', 'exists:customers,id'],
            'issue_date'           => ['required', 'date'],
            'valid_until'          => ['nullable', 'date'],
            'currency'             => ['required', 'in:PEN,USD'],
            'exchange_rate'        => ['nullable', 'numeric'],
            'notes'                => ['nullable', 'string'],
            'terms'                => ['nullable', 'string'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['nullable', 'exists:products,id'],
            'items.*.description'  => ['required', 'string'],
            'items.*.unit'         => ['required', 'string', 'max:10'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0'],
            'items.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'items.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_pct'      => ['nullable', 'numeric', 'min:0', 'max:1'], // 0–1
        ]);

        DB::transaction(function () use ($data, $quotation) {

            // 2) Actualizamos la cabecera (reseteando totales)
            $quotation->update([
                'customer_id'    => $data['customer_id'],
                'issue_date'     => $data['issue_date'],
                'valid_until'    => $data['valid_until'] ?? $data['issue_date'],
                'currency'       => $data['currency'],
                'exchange_rate'  => $data['exchange_rate'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'terms'          => $data['terms'] ?? null,
                'subtotal'       => 0,
                'discount_total' => 0,
                'tax_total'      => 0,
                'total'          => 0,
            ]);

            // 3) Borramos items anteriores y recalculamos
            $quotation->items()->delete();

            $subtotal = 0;
            $discount = 0;
            $tax      = 0;

            foreach ($data['items'] as $item) {
                $qty   = $item['quantity']     ?? 0;
                $price = $item['unit_price']   ?? 0;
                $discP = $item['discount_pct'] ?? 0;   // 0–100
                $igvP  = $item['tax_pct']      ?? 0;   // decimal 0–1

                $base    = $qty * $price;
                $disc    = $base * ($discP / 100);
                $after   = $base - $disc;
                $lineTax = $after * $igvP;            // igual que en store()

                $subtotal += $base;
                $discount += $disc;
                $tax      += $lineTax;

                $quotation->items()->create([
                    'product_id'      => $item['product_id'] ?? null,
                    'description'     => $item['description'],
                    'unit'            => $item['unit'],
                    'quantity'        => $qty,
                    'unit_price'      => $price,
                    'discount_pct'    => $discP,
                    'discount_amount' => $disc,
                    'tax_pct'         => $igvP,
                    'tax_amount'      => $lineTax,
                    'total'           => $after + $lineTax,
                ]);
            }

            $total = $subtotal - $discount + $tax;

            // 4) Guardamos totales finales
            $quotation->update([
                'subtotal'       => $subtotal,
                'discount_total' => $discount,
                'tax_total'      => $tax,
                'total'          => $total,
            ]);
        });

        return redirect()
            ->route('quotations.index')
            ->with('success', 'Cotización actualizada correctamente.');
    }
}
