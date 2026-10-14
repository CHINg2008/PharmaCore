<?php

namespace App\Http\Controllers\Admin;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Events\PurchaseOutStock;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $title = 'sales';
        if($request->ajax()){
            $sales = Sale::latest();
            return DataTables::of($sales)
                    ->addIndexColumn()
                    ->addColumn('product',function($sale){
                        $image = '';
                        if(!empty($sale->product)){
                            $image = null;
                            if(!empty($sale->product->purchase->image)){
                                $image = '<span class="avatar avatar-sm mr-2">
                                <img class="avatar-img" src="'.asset("storage/purchases/".$sale->product->purchase->image).'" alt="image">
                                </span>';
                            }
                            return $sale->product->purchase->product. ' ' . $image;
                        }                 
                    })
                    ->addColumn('total_price',function($sale){                   
                        return settings('app_currency','$').' '. $sale->total_price;
                    })
                    ->addColumn('date',function($row){
                        return date_format(date_create($row->created_at),'d M, Y');
                    })
                    ->addColumn('action', function ($row) {
                        $editbtn = '<a href="'.route("sales.edit", $row->id).'" class="editbtn"><button class="btn btn-primary"><i class="fas fa-edit"></i></button></a>';
                        $deletebtn = '<a data-id="'.$row->id.'" data-route="'.route('sales.destroy', $row->id).'" href="javascript:void(0)" id="deletebtn"><button class="btn btn-danger"><i class="fas fa-trash"></i></button></a>';
                        if (!auth()->user()->hasPermissionTo('edit-sale')) {
                            $editbtn = '';
                        }
                        if (!auth()->user()->hasPermissionTo('destroy-sale')) {
                            $deletebtn = '';
                        }
                        $btn = $editbtn.' '.$deletebtn;
                        return $btn;
                    })
                    ->rawColumns(['product','action'])
                    ->make(true);

        }
        $products = Product::get();
        return view('admin.sales.index',compact(
            'title','products',
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'create sales';
        $products = Product::get();
        return view('admin.sales.create',compact(
            'title','products'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'product'=>'required',
            'quantity'=>'required|integer|min:1'
        ]);
        $sold_product = Product::with('purchase')->findOrFail($request->product);
        $purchased_item = $sold_product->purchase;
        $availableStock = $purchased_item ? $purchased_item->quantity : 0;
        $prodName = $purchased_item ? $purchased_item->product : 'Medicine';

        // Check if sales item quantity is more than the in-stock quantity
        if ($request->quantity > $availableStock) {
            $notification = notify("Cannot complete sale: Requested quantity ({$request->quantity}) exceeds the available in-stock quantity ({$availableStock}) for '{$prodName}'!", 'danger');
            return back()->withInput()->with($notification);
        }

        $new_quantity = $availableStock - $request->quantity;

        $purchased_item->update([
            'quantity'=>$new_quantity,
        ]);

        /**
         * calculating item's total price
        **/
        $total_price = ($request->quantity) * ($sold_product->price);
        Sale::create([
            'product_id'=>$request->product,
            'quantity'=>$request->quantity,
            'total_price'=>$total_price,
        ]);

        $notification = notify("Product '{$prodName}' has been sold successfully!");

        if($new_quantity <=1 && $new_quantity !=0){
            // send notification to system notification bell
            $product = Purchase::where('quantity', '<=', 1)->first();
            event(new PurchaseOutStock($product));
        }

        return redirect()->route('sales.index')->with($notification);
    }

    

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \app\Models\Sale $sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $sale)
    {
        $title = 'edit sale';
        $products = Product::get();
        return view('admin.sales.edit',compact(
            'title','sale','products'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \app\Models\Sale $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $sale)
    {
        $this->validate($request,[
            'product'=>'required',
            'quantity'=>'required|integer|min:1'
        ]);
        $sold_product = Product::with('purchase')->findOrFail($request->product);
        $purchased_item = $sold_product->purchase;
        $prodName = $purchased_item ? $purchased_item->product : 'Medicine';

        // Calculate available stock including previously sold units if same product
        $currentStock = $purchased_item ? $purchased_item->quantity : 0;
        $availableStock = $currentStock;
        if ($sale->product_id == $sold_product->id) {
            $availableStock += $sale->quantity;
        }

        // Check if sales item quantity is more than the in-stock quantity
        if ($request->quantity > $availableStock) {
            $notification = notify("Cannot update sale: Requested quantity ({$request->quantity}) exceeds the available in-stock quantity ({$availableStock}) for '{$prodName}'!", 'danger');
            return back()->withInput()->with($notification);
        }

        // If product changed, return stock to old product and deduct from new product
        if ($sale->product_id != $sold_product->id && $sale->product && $sale->product->purchase) {
            $sale->product->purchase->increment('quantity', $sale->quantity);
            $new_quantity = $currentStock - $request->quantity;
            $purchased_item->update(['quantity' => $new_quantity]);
        } else {
            $diff = $request->quantity - $sale->quantity;
            $new_quantity = $currentStock - $diff;
            $purchased_item->update(['quantity' => $new_quantity]);
        }

        $total_price = ($request->quantity) * ($sold_product->price);
        $sale->update([
            'product_id'=>$request->product,
            'quantity'=>$request->quantity,
            'total_price'=>$total_price,
        ]);

        $notification = notify("Sale for '{$prodName}' has been updated successfully!");

        if($new_quantity <=1 && $new_quantity !=0){
            // send notification to system notification bell
            $product = Purchase::where('quantity', '<=', 1)->first();
            event(new PurchaseOutStock($product));
        }
        return redirect()->route('sales.index')->with($notification);
    }

    /**
     * Generate sales reports index
     *
     * @return \Illuminate\Http\Response
     */
    public function reports(Request $request){
        $title = 'sales reports';
        $sales = Sale::whereBetween('created_at', [
            \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') . ' 00:00:00',
            \Carbon\Carbon::now()->format('Y-m-d') . ' 23:59:59'
        ])->get();
        return view('admin.sales.reports',compact(
            'title', 'sales'
        ));
    }

    /**
     * Generate sales report form post
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateReport(Request $request){
        $this->validate($request,[
            'from_date' => 'required',
            'to_date' => 'required',
        ]);
        $title = 'sales reports';
        $sales = Sale::whereBetween('created_at', [
            $request->from_date . ' 00:00:00', 
            $request->to_date . ' 23:59:59'
        ])->get();
        return view('admin.sales.reports',compact(
            'sales','title'
        ));
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        return Sale::findOrFail($request->id)->delete();
    }
}
