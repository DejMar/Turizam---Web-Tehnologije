document.addEventListener('DOMContentLoaded', function () {
    initReservationForm();
    initRegisterForm();
    initDateValidation();
});

function initReservationForm() {
    const form = document.getElementById('reservationForm');
    if (!form) return;

    const datumOd = document.getElementById('datum_od');
    const datumDo = document.getElementById('datum_do');
    const pricePreview = document.getElementById('pricePreview');
    const totalPrice = document.getElementById('totalPrice');

    if (!datumOd || !datumDo || !pricePreview) return;

    const cijenaPoNoci = parseFloat(pricePreview.dataset.price) || 0;

    function updatePrice() {
        if (!datumOd.value || !datumDo.value) {
            totalPrice.textContent = '—';
            return;
        }

        const od = new Date(datumOd.value);
        const doDat = new Date(datumDo.value);
        const diffMs = doDat - od;
        const nocenja = Math.max(1, Math.ceil(diffMs / (1000 * 60 * 60 * 24)));

        if (diffMs <= 0) {
            totalPrice.textContent = 'Neispravan period';
            return;
        }

        const ukupno = nocenja * cijenaPoNoci;
        totalPrice.textContent = formatPrice(ukupno) + ' (' + nocenja + ' noć' + (nocenja > 1 ? 'i' : '') + ')';
    }

    datumOd.addEventListener('change', function () {
        if (datumOd.value) {
            const minDo = new Date(datumOd.value);
            minDo.setDate(minDo.getDate() + 1);
            datumDo.min = minDo.toISOString().split('T')[0];
            if (datumDo.value && datumDo.value <= datumOd.value) {
                datumDo.value = '';
            }
        }
        updatePrice();
    });

    datumDo.addEventListener('change', updatePrice);
    updatePrice();
}

function initRegisterForm() {
    const form = document.querySelector('.auth-form');
    if (!form || !form.querySelector('#password_confirm')) return;

    form.addEventListener('submit', function (e) {
        const password = form.querySelector('#password');
        const confirm = form.querySelector('#password_confirm');

        if (password && confirm && password.value !== confirm.value) {
            e.preventDefault();
            alert('Lozinke se ne poklapaju.');
        }
    });
}

function initDateValidation() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"][min]').forEach(function (input) {
        if (!input.min || input.min < today) {
            input.min = today;
        }
    });
}

function formatPrice(amount) {
    return amount.toFixed(2).replace('.', ',') + ' €';
}
