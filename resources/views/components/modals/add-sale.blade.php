<!-- Add Sale Modal -->
<div class="modal fade" id="add_sales" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold text-white"><i class="fas fa-cart-plus mr-2"></i>Sell Product</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{route('sales.store')}}" id="add_sale_modal_form">
                    @csrf
                    <div class="row form-row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="font-weight-bold text-dark">Product <span class="text-danger">*</span></label>
                                <select class="select2 form-select form-control" name="product" id="modal_sale_product_select" required> 
                                    <option value="" disabled selected>-- Select Product to Sell --</option>
                                    @foreach (\App\Models\Product::with('purchase')->get() as $product)
                                        @if (!empty($product->purchase))
                                            @if (!($product->purchase->quantity <= 0))
                                                <option value="{{$product->id}}" data-stock="{{$product->purchase->quantity}}" data-name="{{$product->purchase->product}}" data-price="{{$product->price}}">
                                                    {{$product->purchase->product}} (In Stock: {{$product->purchase->quantity}} units | ${{number_format($product->price, 2)}})
                                                </option>
                                            @endif
                                        @endif
                                    @endforeach
                                </select>
                                <div id="modal_stock_badge" class="mt-2" style="display:none;"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="font-weight-bold text-dark">Quantity <span class="text-danger">*</span></label>
                                <input type="number" value="1" min="1" class="form-control form-control-lg font-weight-bold" name="quantity" id="modal_sale_qty_input" required>
                                <small id="modal_qty_feedback" class="form-text mt-1"></small>
                            </div>
                        </div>
                    </div>
                    <button type="submit" id="modal_sale_submit_btn" class="btn btn-primary btn-block btn-lg font-weight-bold">
                        <i class="fas fa-check mr-1"></i> Save Changes & Complete Sale
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /ADD Sale Modal -->

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const prodSelect = document.getElementById('modal_sale_product_select');
        const qtyInput = document.getElementById('modal_sale_qty_input');
        const stockBadge = document.getElementById('modal_stock_badge');
        const qtyFeedback = document.getElementById('modal_qty_feedback');
        const saleForm = document.getElementById('add_sale_modal_form');
        const submitBtn = document.getElementById('modal_sale_submit_btn');

        function checkModalStock(triggerPopupOnExceed) {
            if (!prodSelect || !qtyInput) return true;
            const selectedOpt = prodSelect.options[prodSelect.selectedIndex];
            if (!selectedOpt || selectedOpt.disabled || !selectedOpt.value) {
                if (stockBadge) stockBadge.style.display = 'none';
                return true;
            }

            const stock = parseInt(selectedOpt.getAttribute('data-stock') || '0', 10);
            const prodName = selectedOpt.getAttribute('data-name') || selectedOpt.text.split('(')[0].trim();
            const enteredQty = parseInt(qtyInput.value || '0', 10);

            // Display in-stock badge
            if (stockBadge) {
                stockBadge.style.display = 'block';
                stockBadge.innerHTML = `<span class="badge badge-pill badge-info py-1 px-2" style="font-size:0.8rem;"><i class="fas fa-boxes mr-1"></i> Current In-Stock: <b>${stock}</b> units</span>`;
            }

            if (enteredQty > stock) {
                // Highlight error state
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
                checkModalStock(false);
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('input', function () {
                checkModalStock(false);
            });
        }

        if (saleForm) {
            saleForm.addEventListener('submit', function (e) {
                const isValid = checkModalStock(true);
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>