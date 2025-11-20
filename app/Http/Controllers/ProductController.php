<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Muestra el listado de productos con paginación y filtro por búsqueda.
     */
    public function index(Request $r)
    {
        // 🔍 Filtro de búsqueda: busca por nombre o SKU
        $q = Product::query()
            ->when($r->search, fn($qq) => $qq
                ->where('name', 'like', "%{$r->search}%")
                ->orWhere('sku', 'like', "%{$r->search}%"));

        // 📦 Retorna la vista Inertia con productos y filtros activos
        return Inertia::render('Products/Index', [
            'products' => $q->orderBy('name')->paginate(15)->withQueryString(),
            'filters'  => ['search' => $r->search],
        ]);
    }

    /**
     * Guarda un nuevo producto en base de datos.
     */
    public function store(Request $r)
    {
        // ✅ Validación de datos (coincide con la tabla products)
        $data = $r->validate([
            'sku'        => 'nullable|string|max:255',
            'name'       => 'required|string|max:255',
            'unit'       => 'required|string|max:10',
            'price'      => 'required|numeric|min:0',
            'tax_pct'    => 'required|numeric|min:0|max:1',
            'is_service' => 'boolean',
        ]);

        // 💾 Crea el registro
        Product::create($data);

        // ⚡ Flash de éxito: Inertia lo leerá y mostrará toast
        return back()->with('success', 'Producto creado correctamente');
    }

    /**
     * Actualiza un producto existente.
     */
    public function update(Request $r, Product $product)
    {
        // ✅ Validación igual que en store
        $data = $r->validate([
            'sku'        => 'nullable|string|max:255',
            'name'       => 'required|string|max:255',
            'unit'       => 'required|string|max:10',
            'price'      => 'required|numeric|min:0',
            'tax_pct'    => 'required|numeric|min:0|max:1',
            'is_service' => 'boolean',
        ]);

        // 🧩 Actualiza el producto
        $product->update($data);

        // ⚡ Flash de notificación
        return back()->with('success', 'Producto actualizado con éxito');
    }

    /**
     * Elimina un producto.
     */
    public function destroy(Product $product)
    {
        // 🗑️ Elimina el registro
        $product->delete();

        // ⚡ Flash de confirmación
        return back()->with('success', 'Producto eliminado correctamente');
    }
}
