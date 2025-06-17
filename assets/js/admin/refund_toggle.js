document.addEventListener('DOMContentLoaded', () => {
    const statusField = document.querySelector('#Order_orderStatus');
    const internalNote = document.querySelector('#Order_internal_note');
    const refundCheckbox = document.querySelector('#Order_refund');
    const refundAmountField = document.querySelector('#Order_refund_amount');
    const refundStatusField = document.querySelector('#Order_refund_status');
    const refundNoteField = document.querySelector('#Order_refund_note');

    const toggleRefundFields = (enabled) => {
        [refundAmountField, refundStatusField, refundNoteField].forEach(field => {
            if (field) field.closest('.form-group').style.display = enabled ? 'block' : 'none';
        });
    };

    if (statusField) {
        const updateFromStatus = () => {
            const value = statusField.value;

            if (value === "Reembolso") {
                if (refundCheckbox) refundCheckbox.checked = true;
                toggleRefundFields(true);
                if (internalNote) internalNote.value = "A encomenda foi cancelada e reembolsada.";
            } else if (value === "Entregue e finalizado") {
                if (refundCheckbox) refundCheckbox.checked = false;
                toggleRefundFields(false);
                if (internalNote) internalNote.value = "Todos os produtos foram entregues com sucesso.";
            } else {
                if (refundCheckbox) refundCheckbox.checked = false;
                toggleRefundFields(false);
                if (internalNote) internalNote.value = "";
            }
        };

        statusField.addEventListener('change', updateFromStatus);
        updateFromStatus(); // exécution initiale
    } else {
        console.warn('⚠️ #Order_orderStatus introuvable');
    }
});
