<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $r)
    {
        // 1) Armamos el query base
        $q = Customer::query()
            ->when($r->search, function ($qq) use ($r) {
                $qq->where(function ($w) use ($r) {
                    $w->where('name', 'like', "%{$r->search}%")
                      ->orWhere('document_number', 'like', "%{$r->search}%");
                });
            });

        // 2) Aplicamos el orden "más recientes primero" y paginamos
        $customers = $q->orderBy('created_at', 'desc') // 👈 más recientes
                       ->paginate(15)
                       ->withQueryString();

        // 3) Enviamos a Vue
        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters'   => [
                'search' => $r->search,
            ],
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'type'            => 'required|in:person,company',
            'document_type'   => 'nullable|in:DNI,RUC,CE,PAS',
            'document_number' => 'nullable|string|max:15',
            'name'            => 'required|string|max:255',
            'trade_name'      => 'nullable|string|max:255',
            'email'           => 'nullable|email',
            'phone'           => 'nullable|string|max:30',
            'address'         => 'nullable|string|max:255',
            'district'        => 'nullable|string|max:255',
            'province'        => 'nullable|string|max:255',
            'department'      => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        Customer::create($data);

        return back()->with('success', 'Cliente creado');
    }

    public function update(Request $r, Customer $customer)
    {
        $data = $r->validate([
            'type'            => 'required|in:person,company',
            'document_type'   => 'nullable|in:DNI,RUC,CE,PAS',
            'document_number' => 'nullable|string|max:15',
            'name'            => 'required|string|max:255',
            'trade_name'      => 'nullable|string|max:255',
            'email'           => 'nullable|email',
            'phone'           => 'nullable|string|max:30',
            'address'         => 'nullable|string|max:255',
            'district'        => 'nullable|string|max:255',
            'province'        => 'nullable|string|max:255',
            'department'      => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $customer->update($data);

        return back()->with('success', 'Cliente actualizado');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return back()->with('success', 'Cliente eliminado');
    }
}
