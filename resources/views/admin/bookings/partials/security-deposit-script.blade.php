<script>
document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('release-security-deposit-button');
    const captureForm = document.getElementById('security-deposit-capture-form');

    if (!button && !captureForm) {
        return;
    }

    const feedback = document.getElementById('security-deposit-feedback');
    const statusText = document.getElementById('security-deposit-status-text');
    const statusBlock = document.getElementById('security-deposit-status-block');
    const actions = document.getElementById('security-deposit-actions');
    const releasedTemplate = document.getElementById('security-deposit-released-template');
    const refundModal = document.getElementById('security-deposit-refund-modal');
    const refundModalPenalty = refundModal?.querySelector('[data-refund-modal-penalty]');
    const refundModalRefund = refundModal?.querySelector('[data-refund-modal-refund]');
    const refundModalCancel = refundModal?.querySelector('[data-refund-modal-cancel]');
    const refundModalConfirm = refundModal?.querySelector('[data-refund-modal-confirm]');

    const icon = button?.querySelector('[data-release-icon]');
    const spinner = button?.querySelector('[data-release-spinner]');
    const label = button?.querySelector('[data-release-label]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || button?.dataset.csrf
        || '';
    let pendingRefundAction = null;

    const money = (amount) => new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Math.max(0, Number(amount) || 0));

    const closeRefundModal = () => {
        pendingRefundAction = null;
        refundModal?.classList.add('hidden');
        refundModal?.classList.remove('flex');
    };

    const openRefundModal = (penaltyAmount, refundAmount, onConfirm) => {
        if (!refundModal) {
            onConfirm();
            return;
        }

        pendingRefundAction = onConfirm;

        if (refundModalPenalty) {
            refundModalPenalty.textContent = money(penaltyAmount);
        }

        if (refundModalRefund) {
            refundModalRefund.textContent = money(refundAmount);
        }

        refundModal.classList.remove('hidden');
        refundModal.classList.add('flex');
    };

    refundModalCancel?.addEventListener('click', closeRefundModal);
    refundModal?.addEventListener('click', (event) => {
        if (event.target === refundModal) {
            closeRefundModal();
        }
    });
    refundModalConfirm?.addEventListener('click', () => {
        const action = pendingRefundAction;
        closeRefundModal();

        if (action) {
            action();
        }
    });

    const showFeedback = (type, message) => {
        if (!feedback) {
            window.alert(message);
            return;
        }

        const isSuccess = type === 'success';
        feedback.className = isSuccess
            ? 'mb-4 bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3'
            : 'mb-4 bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-start gap-3';
        feedback.innerHTML = `
            <svg class="w-5 h-5 ${isSuccess ? 'text-emerald-400' : 'text-red-400'} flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${isSuccess ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'}"/>
            </svg>
            <p class="${isSuccess ? 'text-emerald-300' : 'text-red-300'} text-sm">${message}</p>
        `;
    };

    const setLoading = (isLoading) => {
        if (!button) {
            return;
        }

        button.disabled = isLoading;

        if (icon) {
            icon.classList.toggle('hidden', isLoading);
        }

        if (spinner) {
            spinner.classList.toggle('hidden', !isLoading);
        }

        if (label) {
            label.textContent = isLoading
                ? (button.dataset.loadingLabel || 'Processing...')
                : (button.dataset.idleLabel || 'Release Hold');
        }
    };

    const submitRelease = async () => {
        if (!button) {
            return;
        }

        if (!button.dataset.url) {
            showFeedback('error', 'Release URL is missing.');
            return;
        }

        if (!csrfToken) {
            showFeedback('error', 'CSRF token is missing. Add <meta name="csrf-token" content="@{{ csrf_token() }}"> inside the layout head.');
            return;
        }

        setLoading(true);

        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json')
                ? await response.json()
                : {};
            
            if (!response.ok || !data.success) {
                throw new Error(data.error || data.message || 'Release failed');
            }

            showFeedback('success', data.message || 'Security deposit refunded successfully');

            if (statusText) {
                statusText.textContent = 'Refunded';
                statusText.className = 'font-bold text-xl text-emerald-400';
            }

            if (releasedTemplate && statusBlock) {
                statusBlock.innerHTML = releasedTemplate.innerHTML;
                statusBlock.classList.add('opacity-0');
                requestAnimationFrame(() => statusBlock.classList.remove('opacity-0'));
            }

            button.remove();

            if (actions) {
                actions.classList.remove('md:grid-cols-2');
            }
        } catch (error) {
            showFeedback('error', error.message || 'Failed to refund security deposit');
            setLoading(false);
        }
    };

    button?.addEventListener('click', () => {
        if (button.dataset.confirmRefund === '1') {
            openRefundModal(
                Number(button.dataset.penaltyAmount || 0),
                Number(button.dataset.refundAmount || 0),
                submitRelease
            );
            return;
        }

        submitRelease();
    });

    captureForm?.addEventListener('submit', (event) => {
        if (captureForm.dataset.refundConfirmed === '1') {
            delete captureForm.dataset.refundConfirmed;
            return;
        }

        if (!captureForm.checkValidity()) {
            return;
        }

        event.preventDefault();

        const penaltyInput = captureForm.querySelector('[name="penalty_amount"]');
        const penaltyAmount = Number(penaltyInput?.value || 0);
        const depositAmount = Number(captureForm.dataset.depositAmount || 0);
        const refundAmount = Math.max(0, depositAmount - penaltyAmount);

        openRefundModal(penaltyAmount, refundAmount, () => {
            captureForm.dataset.refundConfirmed = '1';
            captureForm.requestSubmit();
        });
    });
});
</script>
