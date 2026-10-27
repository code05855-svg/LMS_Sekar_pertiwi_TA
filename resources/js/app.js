import Alpine from 'alpinejs';
import './chatbot';

window.Alpine = Alpine;

/* ------------------------------------------------------------------
 * Konfirmasi hapus: <form data-konfirmasi="Hapus materi ini?"> membuka
 * modal konfirmasi global (layouts/app) sebelum dikirim.
 * ------------------------------------------------------------------ */
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;

    const pesan = form.dataset.konfirmasi;
    if (pesan && !form.dataset.terkonfirmasi) {
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('minta-konfirmasi', {
            detail: {
                form,
                pesan,
                judul: form.dataset.judul || 'Konfirmasi',
                tombol: form.dataset.tombol || 'Ya, lanjutkan',
                bahaya: form.dataset.bahaya !== 'tidak',
            },
        }));
        return;
    }

    // Cegah klik ganda: nonaktifkan tombol kirim SETELAH formulir terkirim
    // (ditunda agar nilai tombol "submitter" tetap ikut terkirim).
    setTimeout(() => {
        form.querySelectorAll('button[type="submit"]:not([data-tetap])').forEach((tombol) => {
            tombol.disabled = true;
            if (tombol.dataset.memuat) tombol.textContent = tombol.dataset.memuat;
        });
    }, 0);
});

/* Komponen Alpine kecil yang dipakai berulang */
Alpine.data('konfirmasi', () => ({
    terbuka: false,
    form: null,
    pesan: '',
    judul: '',
    tombol: '',
    bahaya: true,
    buka(detail) {
        Object.assign(this, detail, { terbuka: true });
    },
    lanjut() {
        if (!this.form) return;
        this.form.dataset.terkonfirmasi = '1';
        this.terbuka = false;
        this.form.requestSubmit();
    },
}));

/* Pilih berkas: tampilkan nama dan ukuran, dukung seret-dan-lepas */
Alpine.data('inputBerkas', (maksMb = 10) => ({
    daftar: [],
    seret: false,
    galat: '',
    ubah(event) {
        this.setDaftar(Array.from(event.target.files));
    },
    lepas(event) {
        this.seret = false;
        const input = this.$refs.input;
        const dt = new DataTransfer();
        Array.from(event.dataTransfer.files).forEach((f) => dt.items.add(f));
        input.files = dt.files;
        this.setDaftar(Array.from(dt.files));
    },
    setDaftar(files) {
        this.galat = '';
        const terlalubesar = files.find((f) => f.size > maksMb * 1024 * 1024);
        if (terlalubesar) {
            this.galat = `"${terlalubesar.name}" lebih dari ${maksMb} MB.`;
        }
        this.daftar = files.map((f) => ({ nama: f.name, ukuran: this.format(f.size) }));
    },
    format(byte) {
        if (byte < 1024) return `${byte} B`;
        if (byte < 1024 * 1024) return `${(byte / 1024).toFixed(0)} KB`;
        return `${(byte / 1024 / 1024).toFixed(1)} MB`;
    },
}));

Alpine.start();
