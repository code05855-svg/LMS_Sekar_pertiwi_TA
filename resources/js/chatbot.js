import Alpine from 'alpinejs';

/*
 * Chatbot Sekar AI. Dipakai halaman Chatbot dan widget melayang.
 * Pertanyaan dikirim lewat fetch ke ChatbotController@kirim, yang mengembalikan
 * potongan HTML (chatbot/partials/pesan) berisi pertanyaan, jawaban, dan sumber.
 */
Alpine.data('sekarAi', ({ url, muatUrl = null, muatAwal = true }) => ({
    pertanyaan: '',
    memuat: false,
    maks: 500,
    sudahMuat: muatAwal,

    /** Widget: muat percakapan aktif (sesi yang sama dengan halaman Chatbot) saat pertama dibuka. */
    async muat() {
        if (this.sudahMuat || !muatUrl) return;
        this.sudahMuat = true;
        try {
            const res = await fetch(muatUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = res.ok ? (await res.text()).trim() : '';
            if (html) {
                this.$refs.kosong?.remove();
                this.$refs.saran?.classList.add('hidden');
                this.$refs.daftar.insertAdjacentHTML('beforeend', html);
                this.$nextTick(() => this.gulir());
            }
        } catch (e) { /* abaikan: widget tetap bisa dipakai tanpa riwayat */ }
    },

    init() {
        if (this.sudahMuat) this.$nextTick(() => this.gulir());
    },

    gulir() {
        const daftar = this.$refs.daftar;
        if (daftar) daftar.scrollTo({ top: daftar.scrollHeight, behavior: 'smooth' });
    },

    tanyaSaran(teks) {
        this.pertanyaan = teks;
        this.kirim();
    },

    escape(teks) {
        const d = document.createElement('div');
        d.textContent = teks;
        return d.innerHTML;
    },

    async kirim() {
        const teks = this.pertanyaan.trim();
        if (teks.length < 3 || this.memuat) return;

        this.memuat = true;
        this.pertanyaan = '';
        this.$refs.kosong?.remove();
        this.$refs.saran?.classList.add('hidden');

        const daftar = this.$refs.daftar;
        const penanda = document.createElement('div');
        penanda.className = 'space-y-3';
        penanda.innerHTML = `
            <div class="gelembung-saya whitespace-pre-line">${this.escape(teks)}</div>
            <div class="gelembung-bot"><span class="titik-mengetik inline-flex gap-1" aria-label="Sekar AI sedang mengetik"><span></span><span></span><span></span></span></div>`;
        daftar.appendChild(penanda);
        this.gulir();

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/html;q=0.9',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ pertanyaan: teks }),
            });

            if (res.ok) {
                penanda.outerHTML = await res.text();
            } else {
                penanda.lastElementChild.innerHTML = this.pesanGalat(res.status, await res.json().catch(() => ({})));
                penanda.lastElementChild.classList.add('bg-danger-soft', 'text-danger');
            }
        } catch (e) {
            penanda.lastElementChild.textContent = 'Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.';
            penanda.lastElementChild.classList.add('bg-danger-soft', 'text-danger');
        } finally {
            this.memuat = false;
            this.$nextTick(() => {
                this.gulir();
                this.$refs.masukan?.focus();
            });
        }
    },

    pesanGalat(status, data) {
        if (status === 422) return this.escape(Object.values(data.errors || {}).flat()[0] || 'Pertanyaan tidak valid.');
        if (status === 429) return 'Terlalu banyak pertanyaan dalam satu menit. Tunggu sebentar lalu tanya lagi ya.';
        if (status === 419) return 'Sesi habis. Muat ulang halaman, lalu coba lagi.';
        if (status === 401 || status === 403) return 'Kamu tidak punya akses ke chatbot. Silakan masuk kembali.';
        return 'Terjadi gangguan di server. Coba lagi beberapa saat lagi.';
    },
}));
