// /home/sistema/contas-pagar/public/assets/js/app.js

// === Mascaras de input ===

function maskCNPJ(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 14);
    if (v.length <= 11) {
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        v = v.replace(/^(\d{2})(\d)/, '$1.$2');
        v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
    }
    input.value = v;
}

function maskCPF(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    input.value = v;
}

function maskCEP(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 8);
    v = v.replace(/(\d{5})(\d)/, '$1-$2');
    input.value = v;
}

function maskPhone(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) {
        v = v.replace(/(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
    } else {
        v = v.replace(/(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{5})(\d)/, '$1-$2');
    }
    input.value = v;
}

function maskMoney(input) {
    let v = input.value.replace(/\D/g, '');
    v = (parseInt(v || 0, 10) / 100).toFixed(2);
    v = v.replace('.', ',');
    v = v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = 'R$ ' + v;
}

// === Vinculos (checkbox habilita select) ===
function toggleVinculo(checkbox, empresaId) {
    const select = document.getElementById('vinculo_' + empresaId);
    if (select) select.disabled = !checkbox.checked;
}

// === Confirm dialogs genericos ===
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-confirm]');
    forms.forEach(f => {
        f.addEventListener('submit', function(e) {
            if (!confirm(f.dataset.confirm)) e.preventDefault();
        });
    });
});