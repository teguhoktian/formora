import Sortable from 'sortablejs';

window.Sortable = Sortable;

document.addEventListener('input', (event) => {
    const input = event.target;
    if (input instanceof HTMLInputElement && input.matches('[data-digits-only]')) {
        input.value = input.value.replace(/\D/g, '');
    }
});

const addressLevels = ['province', 'regency', 'district', 'village'];
const addressChildren = { province: 'regency', regency: 'district', district: 'village' };
const addressEndpoints = { province: 'regencies', regency: 'districts', district: 'villages' };
const addressPlaceholders = {
    regency: 'Pilih provinsi lebih dahulu',
    district: 'Pilih kabupaten/kota lebih dahulu',
    village: 'Pilih kecamatan lebih dahulu',
};

document.addEventListener('change', async (event) => {
    const parent = event.target;
    if (!(parent instanceof HTMLSelectElement) || !parent.matches('[data-address-region]')) {
        return;
    }

    const level = parent.dataset.addressRegion;
    const address = parent.closest('[data-smart-address]');
    const levelIndex = addressLevels.indexOf(level);
    if (!address || levelIndex < 0) {
        return;
    }

    for (const descendantLevel of addressLevels.slice(levelIndex + 1)) {
        const descendant = address.querySelector(`[data-address-region="${descendantLevel}"]`);
        if (descendant instanceof HTMLSelectElement) {
            descendant.replaceChildren(new Option(addressPlaceholders[descendantLevel], ''));
            descendant.disabled = true;
        }
    }

    const childLevel = addressChildren[level];
    if (!childLevel) {
        return;
    }

    const child = address.querySelector(`[data-address-region="${childLevel}"]`);
    if (!(child instanceof HTMLSelectElement)) {
        return;
    }

    const requestKey = `${level}RequestId`;
    const requestId = Number(address.dataset[requestKey] ?? '0') + 1;
    address.dataset[requestKey] = String(requestId);
    if (!parent.value) {
        child.replaceChildren(new Option(addressPlaceholders[childLevel], ''));
        child.disabled = true;
        return;
    }

    const selectedCode = parent.value;
    child.replaceChildren(new Option('Memuat pilihan…', ''));
    child.disabled = true;

    try {
        const response = await fetch(`${address.dataset.regionsUrl}/${encodeURIComponent(selectedCode)}/${addressEndpoints[level]}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error('Data wilayah gagal dimuat.');
        }
        const result = await response.json();
        if (address.dataset[requestKey] !== String(requestId) || parent.value !== selectedCode) {
            return;
        }
        child.replaceChildren(new Option(`Pilih ${childLevel === 'regency' ? 'kabupaten/kota' : childLevel === 'district' ? 'kecamatan' : 'desa/kelurahan'}`, ''));
        for (const region of result.data) {
            child.add(new Option(region.name, region.code));
        }
        child.disabled = result.data.length === 0;
    } catch {
        if (address.dataset[requestKey] === String(requestId)) {
            child.replaceChildren(new Option('Data wilayah gagal dimuat', ''));
        }
    }
});
