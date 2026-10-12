@extends('admin.layouts.app')

<x-assets.datatables />

@push('page-css')
	
@endpush

@push('page-header')
<div class="col-sm-12">
	<h3 class="page-title text-danger"><i class="fas fa-box-open mr-2"></i>Out of Stock Products</h3>
	<ul class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{route('products.index')}}">Products</a></li>
		<li class="breadcrumb-item active">Out of Stock</li>
	</ul>
</div>
@endpush

@section('content')
<div class="row">
	<div class="col-md-12">
	
		<!-- Outstock Products -->
		<div class="card">
			<div class="card-body">
				<div class="table-responsive">
					<table id="outstock-product" class=" table table-hover table-center mb-0">
						<thead>
							<tr>
								<th>Brand Name</th>
								<th>Category</th>
								<th>Price</th>
								<th>Quantity</th>
								<th>Discount</th>
								<th>Expire</th>
								<th class="action-btn">Action</th>
							</tr>
						</thead>
						<tbody>
							
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<!-- /Outstock Products-->
		
	</div>
</div>

<!-- Restock Product Modal -->
<div class="modal fade" id="restockProductModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-truck-loading mr-2"></i>Buy & Restock Medicine</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="restockProductForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size: 0.85rem;">
                        <i class="fas fa-info-circle mr-1"></i> Purchasing inventory will immediately add new stock units to this product and remove it from Out of Stock.
                    </div>

                    <div class="form-group">
                        <label class="text-muted font-weight-bold mb-1">Medicine Name</label>
                        <input type="text" id="restock_prod_name" class="form-control" readonly style="background-color: #f8f9fa; font-weight: 600;">
                    </div>

                    <div class="row">
                        <div class="col-6 form-group">
                            <label class="text-muted font-weight-bold mb-1">Current Stock</label>
                            <input type="text" id="restock_current_qty" class="form-control" readonly style="background-color: #f8f9fa;">
                        </div>
                        <div class="col-6 form-group">
                            <label class="text-muted font-weight-bold mb-1">Supplier</label>
                            <input type="text" id="restock_supplier" class="form-control" readonly style="background-color: #f8f9fa;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold text-dark mb-1">Units to Buy / Add <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="restock_add_qty" min="1" value="50" class="form-control form-control-lg font-weight-bold text-success" required>
                    </div>

                    <div class="row">
                        <div class="col-6 form-group">
                            <label class="text-muted font-weight-bold mb-1">Unit Cost Price ($)</label>
                            <input type="number" step="0.01" name="cost_price" id="restock_cost_price" class="form-control">
                        </div>
                        <div class="col-6 form-group">
                            <label class="text-muted font-weight-bold mb-1">New Expiry Date</label>
                            <input type="date" name="expiry_date" id="restock_expiry_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success font-weight-bold">
                        <i class="fas fa-check mr-1"></i> Confirm Purchase & Restock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('page-js')
<script>
    $(document).ready(function() {
        var table = $('#outstock-product').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{route('outstock')}}",
            columns: [
                {data: 'product', name: 'product'},
                {data: 'category', name: 'category'},
                {data: 'price', name: 'price'},
                {data: 'quantity', name: 'quantity'},
                {data: 'discount', name: 'discount'},
				{data: 'expiry_date', name: 'expiry_date'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        // Restock modal trigger handler
        $(document).on('click', '.restock-trigger-btn', function() {
            var route = $(this).data('route');
            var name = $(this).data('name');
            var qty = $(this).data('qty');
            var cost = $(this).data('cost');
            var supplier = $(this).data('supplier');
            var expiry = $(this).data('expiry');

            $('#restockProductForm').attr('action', route);
            $('#restock_prod_name').val(name);
            $('#restock_current_qty').val(qty + ' units');
            $('#restock_supplier').val(supplier);
            $('#restock_cost_price').val(cost);
            $('#restock_expiry_date').val(expiry);
            $('#restock_add_qty').val(50);

            $('#restockProductModal').modal('show');
        });
        
    });
</script> 
@endpush