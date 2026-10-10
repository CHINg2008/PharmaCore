<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;

class GraphAnalyticsController extends Controller
{
    public function index()
    {
        $nodes = [];
        $edges = [];
        $nodeId = 1;
        $idMap = [];

        // Categories
        $categories = Category::all();
        foreach ($categories as $cat) {
            $id = $nodeId++;
            $idMap['cat_'.$cat->id] = $id;
            $nodes[] = ['id' => $id, 'label' => $cat->name, 'group' => 'category', 'title' => 'Category'];
        }

        // Suppliers
        $suppliers = Supplier::all();
        foreach ($suppliers as $sup) {
            $id = $nodeId++;
            $idMap['sup_'.$sup->id] = $id;
            $nodes[] = ['id' => $id, 'label' => $sup->name . "\n(" . $sup->company . ")", 'group' => 'supplier', 'title' => 'Supplier'];
        }

        // Products and Purchases
        $products = Product::with('purchase')->get();
        foreach ($products as $prod) {
            $id = $nodeId++;
            $idMap['prod_'.$prod->id] = $id;
            $nodes[] = ['id' => $id, 'label' => $prod->purchase->product ?? 'Unknown', 'group' => 'product', 'title' => 'Product'];

            // Link Product to Category
            if (isset($prod->purchase->category_id)) {
                $edges[] = [
                    'from' => $id,
                    'to' => $idMap['cat_'.$prod->purchase->category_id],
                    'label' => 'BELONGS_TO'
                ];
            }

            // Link Supplier to Product
            if (isset($prod->purchase->supplier_id)) {
                $edges[] = [
                    'from' => $idMap['sup_'.$prod->purchase->supplier_id],
                    'to' => $id,
                    'label' => 'SUPPLIES'
                ];
            }
        }

        // Sales (limit to latest 10 for visibility)
        $sales = Sale::latest()->take(10)->get();
        foreach ($sales as $sale) {
            $id = $nodeId++;
            $nodes[] = ['id' => $id, 'label' => 'Sale #'.$sale->id, 'group' => 'sale', 'title' => 'Sale'];
            
            // Link Sale to Product
            if (isset($idMap['prod_'.$sale->product_id])) {
                $edges[] = [
                    'from' => $id,
                    'to' => $idMap['prod_'.$sale->product_id],
                    'label' => 'INCLUDES (Qty: '.$sale->quantity.')'
                ];
            }
        }

        return view('admin.graph.index', compact('nodes', 'edges'));
    }
}
