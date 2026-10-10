<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use QCod\AppSettings\Setting\AppSettings;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $title = 'products';
        if ($request->ajax()) {
            $products = Product::latest();
            return DataTables::of($products)
                ->addColumn('product',function($product){
                    $image = '';
                    if(!empty($product->purchase)){
                        $image = null;
                        if(!empty($product->purchase->image)){
                            $image = '<span class="avatar avatar-sm mr-2">
                            <img class="avatar-img" src="'.asset("storage/purchases/".$product->purchase->image).'" alt="image">
                            </span>';
                        }
                        return $product->purchase->product. ' ' . $image;
                    }                 
                })
                
                ->addColumn('category',function($product){
                    $category = null;
                    if(!empty($product->purchase->category)){
                        $category = $product->purchase->category->name;
                    }
                    return $category;
                })
                ->addColumn('price',function($product){                   
                    return settings('app_currency','$').' '. $product->price;
                })
                ->addColumn('quantity',function($product){
                    if(!empty($product->purchase)){
                        return $product->purchase->quantity;
                    }
                })
                ->addColumn('manufacturing_date',function($product){
                    if(!empty($product->purchase) && $product->purchase->manufacturing_date){
                        return date_format(date_create($product->purchase->manufacturing_date),'d M, Y');
                    }
                    return '<span class="text-muted">—</span>';
                })
                ->addColumn('expiry_date',function($product){
                    if(!empty($product->purchase)){
                        $expiry = $product->purchase->expiry_date;
                        $isExpired = \Carbon\Carbon::parse($expiry)->isPast();
                        $badge = $isExpired ? '<span class="badge badge-danger">Expired</span>' : '';
                        return date_format(date_create($expiry),'d M, Y').' '.$badge;
                    }
                })
                ->addColumn('action', function ($row) {
                    $editbtn = '<a href="'.route("products.edit", $row->id).'" class="editbtn"><button class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></button></a>';
                    $deletebtn = '<a data-id="'.$row->id.'" data-route="'.route('products.destroy', $row->id).'" href="javascript:void(0)" id="deletebtn"><button class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button></a>';

                    $qty = $row->purchase ? $row->purchase->quantity : 1;
                    $isOutStock = $qty <= 0;
                    $isExpired = $row->purchase && \Carbon\Carbon::parse($row->purchase->expiry_date)->isPast();

                    $outstockbtn = '';
                    $expiredbtn  = '';

                    if (!$isOutStock) {
                        $outstockbtn = '
                            <form method="POST" action="'.route('products.mark-outstock', $row->id).'" style="display:inline;">
                                '.csrf_field().'
                                <button type="submit" class="btn btn-warning btn-sm" title="Mark as Out of Stock" onclick="return confirm(\'Mark this item as Out of Stock?\')">
                                    <i class="fas fa-box-open"></i>
                                </button>
                            </form>';
                    }
                    if (!$isExpired) {
                        $expiredbtn = '
                            <form method="POST" action="'.route('products.mark-expired', $row->id).'" style="display:inline;">
                                '.csrf_field().'
                                <button type="submit" class="btn btn-secondary btn-sm" title="Mark as Expired" onclick="return confirm(\'Mark this item as Expired?\')">
                                    <i class="fas fa-calendar-times"></i>
                                </button>
                            </form>';
                    }

                    if (!auth()->user()->hasPermissionTo('edit-product')) {
                        $editbtn = '';
                    }
                    if (!auth()->user()->hasPermissionTo('destroy-purchase')) {
                        $deletebtn = '';
                    }
                    return $editbtn.' '.$outstockbtn.' '.$expiredbtn.' '.$deletebtn;
                })
                ->rawColumns(['product','action','expiry_date','manufacturing_date'])
                ->make(true);
        }
        return view('admin.products.index',compact(
            'title'
        ));
    }

    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'add product';
        $purchases = Purchase::get();
        return view('admin.products.create',compact(
            'title','purchases'
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
            'product'=>'required|max:200',
            'price'=>'required|min:1',
            'discount'=>'nullable',
            'description'=>'nullable|max:255',
        ]);
        $price = $request->price;
        if($request->discount >0){
           $price = $request->discount * $request->price;
        }
        Product::create([
            'purchase_id'=>$request->product,
            'price'=>$price,
            'discount'=>$request->discount,
            'description'=>$request->description,
        ]);
        $notification = notify("Product has been added");
        return redirect()->route('products.index')->with($notification);
    }

    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \app\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $title = 'edit product';
        $purchases = Purchase::get();
        return view('admin.products.edit',compact(
            'title','product','purchases'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \app\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $this->validate($request,[
            'product'=>'required|max:200',
            'price'=>'required',
            'discount'=>'nullable',
            'description'=>'nullable|max:255',
        ]);
        
        $price = $request->price;
        if($request->discount >0){
           $price = $request->discount * $request->price;
        }
       $product->update([
            'purchase_id'=>$request->product,
            'price'=>$price,
            'discount'=>$request->discount,
            'description'=>$request->description,
        ]);
        $notification = notify('product has been updated');
        return redirect()->route('products.index')->with($notification);
    }

     /**
     * Display a listing of expired resources.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function expired(Request $request){
        $title = "expired Products";
        if($request->ajax()){
            $products = Product::with('purchase')->whereHas('purchase', function($q) {
                $q->whereDate('expiry_date', '<', Carbon::now());
            })->get();
            return DataTables::of($products)
                ->addColumn('product',function($product){
                    $image = '';
                    if(!empty($product->purchase)){
                        $image = null;
                        if(!empty($product->purchase->image)){
                            $image = '<span class="avatar avatar-sm mr-2">
                            <img class="avatar-img" src="'.asset("storage/purchases/".$product->purchase->image).'" alt="image">
                            </span>';
                        }
                        return $product->purchase->product. ' ' . $image;
                    }                 
                })
                
                ->addColumn('category',function($product){
                    $category = null;
                    if(!empty($product->purchase->category)){
                        $category = $product->purchase->category->name;
                    }
                    return $category;
                })
                ->addColumn('price',function($product){                   
                    return settings('app_currency','$').' '. $product->price;
                })
                ->addColumn('discount',function($product){                   
                    return $product->discount;
                })
                ->addColumn('quantity',function($product){
                    if(!empty($product->purchase)){
                        return $product->purchase->quantity;
                    }
                })
                ->addColumn('expiry_date',function($product){
                    if(!empty($product->purchase)){
                        return date_format(date_create($product->purchase->expiry_date),'d M, Y');
                    }
                })
                ->addColumn('action', function ($row) {
                    $editbtn = '<a href="'.route("products.edit", $row->id).'" class="editbtn"><button class="btn btn-primary" title="Edit / Restock"><i class="fas fa-edit"></i> Edit</button></a>';
                    if (!auth()->user()->hasPermissionTo('edit-product')) {
                        $editbtn = '';
                    }
                    return $editbtn;
                })
                ->rawColumns(['product','action'])
                ->make(true);
        }

        return view('admin.products.expired',compact(
            'title',
        ));
    }

    /**
     * Display a listing of out of stock resources.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function outstock(Request $request){
        $title = "outstocked Products";
        if($request->ajax()){
            $products = Product::with('purchase')->whereHas('purchase', function($q) {
                $q->where('quantity', '<=', 0);
            })->get();

            // Filter out products if there is another batch of the same name with quantity > 0
            $products = $products->filter(function ($product) {
                if (!$product->purchase) return false;
                $name = $product->purchase->product;
                $hasStock = \App\Models\Purchase::where('product', $name)->where('quantity', '>', 0)->exists();
                return !$hasStock;
            });

            return DataTables::of($products)
                ->addColumn('product',function($product){
                    $image = '';
                    if(!empty($product->purchase)){
                        $image = null;
                        if(!empty($product->purchase->image)){
                            $image = '<span class="avatar avatar-sm mr-2">
                            <img class="avatar-img" src="'.asset("storage/purchases/".$product->purchase->image).'" alt="image">
                            </span>';
                        }
                        return $product->purchase->product. ' ' . $image;
                    }                 
                })
                
                ->addColumn('category',function($product){
                    $category = null;
                    if(!empty($product->purchase->category)){
                        $category = $product->purchase->category->name;
                    }
                    return $category;
                })
                ->addColumn('price',function($product){                   
                    return settings('app_currency','$').' '. $product->price;
                })
                ->addColumn('discount',function($product){                   
                    return $product->discount;
                })
                ->addColumn('quantity',function($product){
                    if(!empty($product->purchase)){
                        return $product->purchase->quantity;
                    }
                })
                ->addColumn('expiry_date',function($product){
                    if(!empty($product->purchase)){
                        return date_format(date_create($product->purchase->expiry_date),'d M, Y');
                    }
                })
                ->addColumn('action', function ($row) {
                    $editbtn = '<a href="'.route("products.edit", $row->id).'" class="editbtn"><button class="btn btn-primary" title="Edit / Restock"><i class="fas fa-edit"></i> Edit</button></a>';
                    if (!auth()->user()->hasPermissionTo('edit-product')) {
                        $editbtn = '';
                    }
                    return $editbtn;
                })
                ->rawColumns(['product','action'])
                ->make(true);
        }
        $product = Product::with('purchase')->whereHas('purchase', function($q) {
            $q->where('quantity', '<=', 0);
        })->first();        
        return view('admin.products.outstock',compact(
            'title',
        ));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        return Product::findOrFail($request->id)->delete();
    }

    /**
     * Mark a product as Out of Stock (sets purchase quantity to 0).
     */
    public function markOutStock(Product $product)
    {
        if ($product->purchase) {
            $product->purchase->update(['quantity' => 0]);
        }
        $notification = notify('Product marked as Out of Stock successfully.');
        return redirect()->route('products.index')->with($notification);
    }

    /**
     * Mark a product as Expired (sets purchase expiry_date to yesterday).
     */
    public function markExpired(Product $product)
    {
        if ($product->purchase) {
            $product->purchase->update([
                'expiry_date' => \Carbon\Carbon::yesterday()->toDateString()
            ]);
        }
        $notification = notify('Product marked as Expired successfully.');
        return redirect()->route('products.index')->with($notification);
    }
}
