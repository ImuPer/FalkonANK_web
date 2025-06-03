document.addEventListener('DOMContentLoaded', function () {
    const refundCheckbox = document.querySelector('#Order_refund');

    const selectors = [
        '#Order_refund_amount',
        '#Order_refund_status',
        '#Order_refund_note'
    ];

    const toggleRefundFields = (visible) => {
        selectors.forEach(selector => {
            const element = document.querySelector(selector);
            if (element) {
                const wrapper = element.closest('.form-group, .form-widget, .field'); // s'adapte à EasyAdmin
                if (wrapper) {
                    if (visible) {
                        wrapper.classList.remove('refund-hidden');
                    } else {
                        wrapper.classList.add('refund-hidden');
                    }
                }
            }
        });
    };

    if (refundCheckbox) {
        // Masque au chargement si non coché
        toggleRefundFields(refundCheckbox.checked);

        refundCheckbox.addEventListener('change', () => {
            toggleRefundFields(refundCheckbox.checked);
        });
    } else {
        console.warn('⚠️ Élément #Order_refund introuvable');
    }
});
