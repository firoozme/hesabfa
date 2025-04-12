{{-- <!-- resources/views/components/invoice-summary.blade.php -->
<div>
    <div class="flex justify-between items-center">
        <span>تعداد کل:</span>
        <span id="total-quantity">0</span>
    </div>
    <div class="flex justify-between items-center">
        <span>مجموع:</span>
        <span id="total-amount">0 ریال</span>
    </div>
    <div class="flex justify-between items-center">
        <span>تخفیف:</span>
        <span id="total-discount">0 ریال</span>
    </div>
    <div class="flex justify-between items-center">
        <span>مالیات:</span>
        <span id="total-tax">0 ریال</span>
    </div>
    <div class="flex justify-between items-center font-bold">
        <span>مبلغ کل:</span>
        <span id="final-amount">0 ریال</span>
    </div>
</div>

<script>
       function updateInvoiceSummary() {
        let quantity = 0, amount = 0, discount = 0, tax = 0, tax_toRial=0, discount_toRial=0,finalAmount=0;

        document.querySelectorAll('.fi-fo-repeater-item').forEach((el) => {
            const qty = parseFloat(el.querySelector('.quantityInput input').value) || 0;
            const price = parseFloat(el.querySelector('.feePriceInput input').value) || 0;
            const disc = parseFloat(el.querySelector('.discountInput input').value) || 0;
            const tx = parseFloat(el.querySelector('.taxInput input').value) || 0;

            // محاسبه جمع مبلغ
                //const sumPrice = qty * price;

                // محاسبه تخفیف
                const discountPrice = sumPrice * (disc / 100);

                // محاسبه مالیات
                const taxableAmount = sumPrice - discountPrice;
                const taxPrice = taxableAmount * (tx / 100);

                // محاسبه جمع کل
                const totalPrice = (sumPrice - discountPrice) + taxPrice;

                // ثبت نتایج در فیلدهای مربوطه
                //el.querySelector('.sumPrice input').value = sumPrice.toLocaleString();
                el.querySelector('.discountPrice input').value = discountPrice.toLocaleString();
                el.querySelector('.taxPrice input').value = taxPrice.toLocaleString();

                // ثبت نتیجه در فیلد مربوطه
                el.querySelector('.totalPrice input').value = totalPrice.toLocaleString();

            quantity += qty;
            amount += qty * price;
            discount += disc;
            tax += tx;
            tax_toRial += taxPrice;
            discount_toRial += discountPrice;
            finalAmount += totalPrice;
        });

        // console.log(amount, discount, );
        // tax_toRial = (Math.round((tax/100) * 100) / 100).toFixed(2);
        // discount_toRial = (Math.round((discount/100) * 100) / 100).toFixed(2);
        document.getElementById('total-quantity').innerText = quantity;
        document.getElementById('total-amount').innerText = amount.toLocaleString() + ' ریال';
        document.getElementById('total-discount').innerText = discount_toRial.toLocaleString() + ' ریال';
        document.getElementById('total-tax').innerText = tax_toRial.toLocaleString() + ' ریال';
        document.getElementById('final-amount').innerText = finalAmount.toLocaleString() + ' ریال';
    }

    document.querySelectorAll('.quantityInput input, .feePriceInput input, .discountInput input, .taxInput input').forEach(input => {
        input.addEventListener('input', updateInvoiceSummary);
    });

    setTimeout(() => {
        updateInvoiceSummary();

    }, 1000);

</script> --}}
