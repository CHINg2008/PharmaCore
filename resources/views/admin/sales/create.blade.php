@extends('admin.layouts.app')

@push('page-header')
<div class="col-sm-12">
	<h3 class="page-title">Create Sale</h3>
	<ul class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('sales.index')}}">Sales</a></li>
		<li class="breadcrumb-item active">Add Sale</li>
	</ul>
</div>
@endpush

@section('content')
<div class="row">
	<div class="col-sm-12">
		<div class="card">
			<div class="card-body custom-edit-service">
                <!-- Create Sale -->
                <form method="POST" action="{{route('sales.store')}}" id="create_sale_form">
					@csrf
					<div class="row form-row">
						<div class="col-12 col-md-6">
							<div class="form-group">
								<label class="font-weight-bold text-dark">Product <span class="text-danger">*</span></label>
								<select class="select2 form-select form-control" name="product" id="create_sale_product_select" required> 
									<option value="" disabled selected>-- Select Product --</option>
									@foreach ($products as $product)
										@if (!empty($product->purchase))
											@if (!($product->purchase->quantity <= 0))
												<option value="{{$product->id}}" data-stock="{{$product->purchase->quantity}}" data-name="{{$product->purchase->product}}">
													{{$product->purchase->product}} (In Stock: {{$product->purchase->quantity}} units | ${{number_format($product->price, 2)}})
												</option>
											@endif
										@endif
									@endforeach
								</select>
								<div id="create_stock_badge" class="mt-2" style="display:none;"></div>
							</div>
						</div>
						<div class="col-12 col-md-6">
							<div class="form-group">
								<label class="font-weight-bold text-dark">Quantity <span class="text-danger">*</span></label>
								<input type="number" value="{{old('quantity', 1)}}" min="1" class="form-control font-weight-bold" name="quantity" id="create_sale_qty_input" required>
								<small id="create_qty_feedback" class="form-text mt-1"></small>
							</div>
						</div>
					</div>
					<button type="submit" id="create_sale_submit_btn" class="btn btn-primary btn-lg font-weight-bold">
						<i class="fas fa-check mr-1"></i> Save Changes & Complete Sale
					</button>
				</form>
                <!--/ Create Sale -->
			</div>
		</div>
	</div>			
</div>
@endsection	

@push('page-js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const prodSelect = document.getElementById('create_sale_product_select');
        const qtyInput = document.getElementById('create_sale_qty_input');
        const stockBadge = document.getElementById('create_stock_badge');
        const qtyFeedback = document.getElementById('create_qty_feedback');
        const saleForm = document.getElementById('create_sale_form');

        function checkStock(triggerPopupOnExceed) {
            if (!prodSelect || !qtyInput) return true;
            const selectedOpt = prodSelect.options[prodSelect.selectedIndex];
            if (!selectedOpt || selectedOpt.disabled || !selectedOpt.value) {
                if (stockBadge) stockBadge.style.display = 'none';
                return true;
            }

            const stock = parseInt(selectedOpt.getAttribute('data-stock') || '0', 10);
            const prodName = selectedOpt.getAttribute('data-name') || selectedOpt.text.split('(')[0].trim();
            const enteredQty = parseInt(qtyInput.value || '0', 10);

            if (stockBadge) {
                stockBadge.style.display = 'block';
                stockBadge.innerHTML = `<span class="badge badge-pill badge-info py-1 px-2" style="font-size:0.85rem;"><i class="fas fa-boxes mr-1"></i> Current In-Stock: <b>${stock}</b> units</span>`;
            }

            if (enteredQty > stock) {
                qtyInput.classList.add('is-invalid');
                if (qtyFeedback) {
                    qtyFeedback.innerHTML = `<span class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Entered quantity (${enteredQty}) exceeds in-stock quantity (${stock} available)!</span>`;
                }

                if (triggerPopupOnExceed) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: "Quantity Exceeds In-Stock!",
                            text: `You requested ${enteredQty} units, but only ${stock} units are currently in stock for "${prodName}". Please enter a quantity less than or equal to ${stock}.`,
                            type: "warning",
                            confirmButtonText: '<i class="fe fe-check mr-1"></i> Adjust Quantity',
                            confirmButtonClass: "btn btn-danger mt-2",
                            buttonsStyling: false
                        });
                    } else if (typeof Snackbar !== 'undefined') {
                        Snackbar.show({
                            text: `Warning: Sales quantity (${enteredQty}) exceeds in-stock quantity (${stock})!`,
                            pos: 'top-right',
                            actionTextColor: '#fff',
                            backgroundColor: '#e7515a',
                            duration: 4000
                        });
                    } else {
                        alert(`Warning: Requested quantity (${enteredQty}) exceeds the available in-stock quantity (${stock}) for "${prodName}".`);
                    }
                }
                return false;
            } else {
                qtyInput.classList.remove('is-invalid');
                if (qtyFeedback) {
                    if (enteredQty > 0) {
                        qtyFeedback.innerHTML = `<span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Valid quantity. ${stock - enteredQty} units will remain in stock.</span>`;
                    } else {
                        qtyFeedback.innerHTML = '';
                    }
                }
                return true;
            }
        }

        if (prodSelect) {
            $(prodSelect).on('change', function () {
                checkStock(false);
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('input', function () {
                checkStock(false);
            });
        }

        if (saleForm) {
            saleForm.addEventListener('submit', function (e) {
                const isValid = checkStock(true);
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush