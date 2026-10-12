<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GraphAnalyticsController extends Controller
{
    public function index()
    {
        $nodes = [];
        $edges = [];
        $nodeId = 1;
        $idMap = [];
        $today = Carbon::today();

        // Precompute counts to avoid N+1 queries
        $catPurchaseCounts = Purchase::select('category_id', DB::raw('count(*) as count'))
            ->groupBy('category_id')
            ->pluck('count', 'category_id')
            ->toArray();

        $supPurchaseCounts = Purchase::select('supplier_id', DB::raw('count(*) as count'))
            ->groupBy('supplier_id')
            ->pluck('count', 'supplier_id')
            ->toArray();

        // 1. Categories
        $categories = Category::all();
        foreach ($categories as $cat) {
            $id = $nodeId++;
            $idMap['cat_'.$cat->id] = $id;
            $connCount = $catPurchaseCounts[$cat->id] ?? 0;
            $nodes[] = [
                'id' => $id,
                'label' => "📁 " . $cat->name,
                'group' => 'category',
                'title' => "<b>Category:</b> " . htmlspecialchars($cat->name),
                'properties' => [
                    'Entity' => 'Category',
                    'ID' => (string)$cat->id,
                    'Name' => $cat->name,
                    'Connected Products' => (string)$connCount
                ]
            ];
        }

        // 2. Suppliers
        $suppliers = Supplier::all();
        foreach ($suppliers as $sup) {
            $id = $nodeId++;
            $idMap['sup_'.$sup->id] = $id;
            $supCount = $supPurchaseCounts[$sup->id] ?? 0;
            $nodes[] = [
                'id' => $id,
                'label' => "🏭 " . $sup->company . "\n(" . $sup->name . ")",
                'group' => 'supplier',
                'title' => "<b>Supplier:</b> " . htmlspecialchars($sup->company),
                'properties' => [
                    'Entity' => 'Supplier',
                    'ID' => (string)$sup->id,
                    'Company' => $sup->company,
                    'Representative' => $sup->name,
                    'Email' => $sup->email ?? 'N/A',
                    'Phone' => $sup->phone ?? 'N/A',
                    'Address' => $sup->address ?? 'N/A',
                    'Supplied Batches' => (string)$supCount
                ]
            ];
        }

        // 3. Products & Purchases
        $products = Product::with(['purchase.supplier', 'purchase.category'])->get();
        $expiredCount = 0;
        $outOfStockCount = 0;

        foreach ($products as $prod) {
            $id = $nodeId++;
            $idMap['prod_'.$prod->id] = $id;

            $purchase = $prod->purchase;
            $prodName = $purchase->product ?? 'Unknown Medicine';
            $qty = $purchase->quantity ?? 0;
            $cost = $purchase->cost_price ?? 0;
            $price = $prod->price ?? 0;
            $expDate = $purchase->expiry_date ? Carbon::parse($purchase->expiry_date) : null;
            $isExpired = $expDate && $expDate->isPast();

            if ($isExpired) {
                $expiredCount++;
            }
            if ($qty == 0) {
                $outOfStockCount++;
            }

            $badge = $isExpired ? '⚠️ ' : ($qty == 0 ? '❌ ' : '💊 ');
            $statusStr = $isExpired ? 'Expired' : ($qty == 0 ? 'Out of Stock' : 'In Stock');

            $nodes[] = [
                'id' => $id,
                'label' => $badge . $prodName . "\n(Qty: {$qty})",
                'group' => $isExpired ? 'product_expired' : ($qty == 0 ? 'product_outstock' : 'product'),
                'title' => "<b>Product:</b> {$prodName}<br>Price: \${$price}<br>Stock: {$qty}<br>Status: {$statusStr}",
                'properties' => [
                    'Entity' => 'Product',
                    'ID' => (string)$prod->id,
                    'Medicine Name' => $prodName,
                    'Selling Price' => '$' . number_format($price, 2),
                    'Cost Price' => '$' . number_format($cost, 2),
                    'Discount' => ($prod->discount ?? 0) . '%',
                    'Available Stock' => $qty . ' units',
                    'Status' => $statusStr,
                    'Expiry Date' => $expDate ? $expDate->format('d M, Y') : 'N/A',
                    'Is Expired' => $isExpired ? 'YES' : 'NO',
                    'Supplier' => $purchase->supplier->company ?? 'N/A',
                    'Category' => $purchase->category->name ?? 'N/A'
                ]
            ];

            // Link Product to Category: (:Product)-[:BELONGS_TO]->(:Category)
            if ($purchase && isset($purchase->category_id) && isset($idMap['cat_'.$purchase->category_id])) {
                $edges[] = [
                    'id' => 'e_' . count($edges),
                    'from' => $id,
                    'to' => $idMap['cat_'.$purchase->category_id],
                    'label' => 'BELONGS_TO',
                    'arrows' => 'to',
                    'title' => "Product belongs to Category"
                ];
            }

            // Link Supplier to Product: (:Supplier)-[:SUPPLIES]->(:Product)
            if ($purchase && isset($purchase->supplier_id) && isset($idMap['sup_'.$purchase->supplier_id])) {
                $edges[] = [
                    'id' => 'e_' . count($edges),
                    'from' => $idMap['sup_'.$purchase->supplier_id],
                    'to' => $id,
                    'label' => 'SUPPLIES',
                    'arrows' => 'to',
                    'title' => "Supplier supplies this Product batch"
                ];
            }
        }

        // 4. Sales Transactions
        $sales = Sale::with('product.purchase.supplier')->latest()->take(15)->get();
        foreach ($sales as $sale) {
            $id = $nodeId++;
            $saleProdName = $sale->product->purchase->product ?? 'Medicine';
            $saleSup = $sale->product->purchase->supplier->company ?? 'Unknown';

            $nodes[] = [
                'id' => $id,
                'label' => "🧾 Sale #{$sale->id}\n(\${$sale->total_price})",
                'group' => 'sale',
                'title' => "<b>Sale #{$sale->id}</b><br>Amount: \${$sale->total_price}<br>Qty: {$sale->quantity}",
                'properties' => [
                    'Entity' => 'Sale',
                    'ID' => (string)$sale->id,
                    'Total Amount' => '$' . number_format($sale->total_price, 2),
                    'Quantity Sold' => (string)$sale->quantity,
                    'Sold Medicine' => $saleProdName,
                    'Supplier Origin' => $saleSup,
                    'Transaction Date' => Carbon::parse($sale->created_at)->format('d M, Y H:i')
                ]
            ];

            // Link Sale to Product: (:Sale)-[:SOLD_PRODUCT]->(:Product)
            if (isset($idMap['prod_'.$sale->product_id])) {
                $edges[] = [
                    'id' => 'e_' . count($edges),
                    'from' => $id,
                    'to' => $idMap['prod_'.$sale->product_id],
                    'label' => 'SOLD_PRODUCT (Qty: '.$sale->quantity.')',
                    'arrows' => 'to',
                    'title' => "Sale transaction sold this product"
                ];
            }
        }

        $stats = [
            'total_nodes' => count($nodes),
            'total_edges' => count($edges),
            'categories_count' => $categories->count(),
            'suppliers_count' => $suppliers->count(),
            'products_count' => $products->count(),
            'sales_count' => $sales->count(),
            'expired_count' => $expiredCount,
            'out_of_stock_count' => $outOfStockCount
        ];

        return view('admin.graph.index', compact('nodes', 'edges', 'stats'));
    }
}
