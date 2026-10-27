#!/usr/bin/env node
/**
 * Simulator n8n untuk LMS SMP Sekar Pertiwi.
 *
 * Tujuannya menguji sisi Laravel (unggah -> indeks -> tanya jawab) tanpa
 * n8n dan tanpa kunci Gemini. Simulator membuka dua webhook yang sama
 * dengan workflow asli:
 *
 *   POST /webhook/indeks-dokumen   (Workflow 1: Indeks Dokumen)
 *   POST /webhook/tanya-jawab      (Workflow 2: Tanya Jawab)
 *
 * Kode node "Potong Teks", "Laporan Gagal", "Susun Prompt", "Olah Jawaban"
 * dan "Jawaban Cadangan" TIDAK ditulis ulang di sini. Simulator membacanya
 * langsung dari berkas ../01-indeks-dokumen.json dan ../02-tanya-jawab.json,
 * jadi yang diuji adalah kode yang sama dengan yang akan berjalan di n8n.
 * Yang ditiru hanya node bawaan n8n (Webhook, Set, HTTP Request, Extract
 * From File, Respond to Webhook) dan Gemini.
 *
 * Pemakaian:   node n8n-tiruan.mjs
 * Pengaturan (env atau berkas .env Laravel di dua folder di atas):
 *   N8N_API_TOKEN       token yang diminta /api/n8n/*   (wajib)
 *   N8N_WEBHOOK_SECRET  isi header X-N8N-Secret         (wajib)
 *   LARAVEL_URL         alamat Laravel, default APP_URL pada .env
 *   PORT                default 5678
 *   GEMINI_API_KEY      bila diisi, memanggil Gemini sungguhan
 *   GEMINI_MODEL        default model pada node Konfigurasi
 *   SIMULASI_GEMINI_GAGAL=1   memaksa jalur "Jawaban Cadangan"
 */
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const FOLDER = path.dirname(fileURLToPath(import.meta.url));
const FOLDER_WORKFLOW = path.resolve(FOLDER, '..');

// ------------------------------------------------------------ pengaturan
function bacaEnvLaravel() {
  const hasil = {};
  const berkas = path.resolve(FOLDER, '../../.env');
  if (!fs.existsSync(berkas)) return hasil;
  for (const baris of fs.readFileSync(berkas, 'utf8').split(/\r?\n/)) {
    const m = baris.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/);
    if (m) hasil[m[1]] = m[2].replace(/^["']|["']$/g, '');
  }
  return hasil;
}
const envLaravel = bacaEnvLaravel();
const cfg = {
  port: Number(process.env.PORT || 5678),
  token: process.env.N8N_API_TOKEN || envLaravel.N8N_API_TOKEN || '',
  rahasia: process.env.N8N_WEBHOOK_SECRET || envLaravel.N8N_WEBHOOK_SECRET || '',
  laravelUrl: (process.env.LARAVEL_URL || envLaravel.APP_URL || 'http://lms-sekolah.test').replace(/\/+$/, ''),
  geminiKey: process.env.GEMINI_API_KEY || '',
  geminiModel: process.env.GEMINI_MODEL || '',
  gagalGemini: process.env.SIMULASI_GEMINI_GAGAL === '1',
};
if (!cfg.token || !cfg.rahasia) {
  console.error('N8N_API_TOKEN dan N8N_WEBHOOK_SECRET belum diisi (env atau .env Laravel).');
  process.exit(1);
}

// ------------------------------------------------------------ membaca workflow
function muatWorkflow(nama) {
  return JSON.parse(fs.readFileSync(path.join(FOLDER_WORKFLOW, nama), 'utf8'));
}
const wfIndeks = muatWorkflow('01-indeks-dokumen.json');
const wfTanya = muatWorkflow('02-tanya-jawab.json');
const nodeDari = (wf, nama) => {
  const n = wf.nodes.find((x) => x.name === nama);
  if (!n) throw new Error(`Node "${nama}" tidak ada di workflow "${wf.name}"`);
  return n;
};
const kodeDari = (wf, nama) => nodeDari(wf, nama).parameters.jsCode;
const nilaiSet = (wf, kolom) => nodeDari(wf, 'Konfigurasi').parameters.assignments.assignments.find((a) => a.name === kolom)?.value;
const webhookPath = (wf) => wf.nodes.find((n) => n.type === 'n8n-nodes-base.webhook').parameters.path;

// ------------------------------------------------------------ menjalankan node Code
const AsyncFunction = Object.getPrototypeOf(async () => {}).constructor;

/**
 * Menjalankan isi node Code (mode "Run Once for All Items") dengan
 * $input dan $('Nama Node') seperti di n8n.
 * @param keluaran  peta nama node -> daftar item keluarannya
 */
async function jalankanKode(kode, masuk, keluaran) {
  const items = masuk.map((json) => ({ json }));
  const $input = { all: () => items, first: () => items[0], last: () => items[items.length - 1], item: items[0] };
  const $ = (nama) => {
    if (!(nama in keluaran)) throw new Error(`Node "${nama}" belum berjalan`);
    const daftar = keluaran[nama].map((json) => ({ json }));
    return { first: () => daftar[0], last: () => daftar[daftar.length - 1], all: () => daftar, item: daftar[0] };
  };
  const hasil = await new AsyncFunction('$input', '$', kode)($input, $);
  if (!Array.isArray(hasil)) throw new Error('Node Code harus mengembalikan array item');
  return hasil.map((i) => i.json);
}

// ------------------------------------------------------------ Laravel API
const headerLaravel = { Authorization: `Bearer ${cfg.token}`, Accept: 'application/json' };

async function laravelJson(urlPath, { method = 'GET', query, body } = {}) {
  const url = new URL(cfg.laravelUrl + urlPath);
  for (const [k, v] of Object.entries(query || {})) url.searchParams.set(k, String(v));
  const res = await fetch(url, {
    method,
    headers: { ...headerLaravel, ...(body ? { 'Content-Type': 'application/json' } : {}) },
    body: body ? JSON.stringify(body) : undefined,
    signal: AbortSignal.timeout(30_000),
  });
  const teks = await res.text();
  if (!res.ok) throw new Error(`HTTP ${res.status} dari ${urlPath}: ${teks.slice(0, 200)}`);
  return JSON.parse(teks);
}

// ------------------------------------------------------------ Extract From File (PDF)
async function ekstrakPdf(buffer) {
  const pdfjs = await import('pdfjs-dist/legacy/build/pdf.mjs');
  const dokumen = await pdfjs.getDocument({ data: new Uint8Array(buffer), useSystemFonts: true, verbosity: 0 }).promise;
  const halaman = [];
  for (let i = 1; i <= dokumen.numPages; i++) {
    const isi = await (await dokumen.getPage(i)).getTextContent();
    halaman.push(isi.items.map((t) => t.str + (t.hasEOL ? '\n' : ' ')).join(''));
  }
  return { numpages: dokumen.numPages, text: halaman };
}

// ------------------------------------------------------------ Workflow 1
async function indeksDokumen(body) {
  const label = `[indeks berkas ${body.berkas_id}]`;
  const keluaran = { Konfigurasi: [{ laravel_url: cfg.laravelUrl, body }] };
  let hasil;
  try {
    const res = await fetch(`${cfg.laravelUrl}/api/n8n/berkas-materi/${body.berkas_id}`, { headers: headerLaravel, signal: AbortSignal.timeout(30_000) });
    if (!res.ok) throw new Error(`Ambil PDF: HTTP ${res.status}`);
    const pdf = Buffer.from(await res.arrayBuffer());
    console.log(label, `PDF diunduh (${pdf.length} byte)`);
    const teks = await ekstrakPdf(pdf);
    keluaran['Ekstrak Teks'] = [teks];
    [hasil] = await jalankanKode(kodeDari(wfIndeks, 'Potong Teks'), [teks], keluaran);
  } catch (e) {
    console.log(label, 'jalur gagal:', e.message);
    [hasil] = await jalankanKode(kodeDari(wfIndeks, 'Laporan Gagal'), [{}], keluaran);
  }
  const kirim = { status: hasil.status, pesan: hasil.pesan, jumlah_halaman: hasil.jumlah_halaman, bagian: hasil.bagian };
  const balasan = await laravelJson(`/api/n8n/berkas-materi/${body.berkas_id}/bagian`, { method: 'POST', body: kirim });
  console.log(label, `status=${hasil.status}`, `bagian=${hasil.bagian.length}`, `-> Laravel:`, JSON.stringify(balasan));
}

// ------------------------------------------------------------ Gemini tiruan
/** Jawaban ekstraktif sederhana: kalimat awal bagian teratas yang dikirim Laravel. */
function geminiTiruan(payload) {
  const teks = payload.contents[0].parts[0].text;
  const blok = teks.match(/<materi>\n([\s\S]*?)\n<\/materi>/)?.[1] ?? '';
  const pertanyaan = teks.match(/<pertanyaan>\n([\s\S]*?)\n<\/pertanyaan>/)?.[1] ?? '';
  let isi;
  if (!blok || blok.startsWith('(tidak ada materi')) {
    isi = { jawaban: 'Maaf, aku tidak menemukan jawabannya di materi yang tersedia. Coba tanyakan ke gurumu.', ditemukan: false, sumber_dipakai: [] };
  } else {
    const teratas = blok.split(/\n\n(?=\[\d+\] )/)[0];
    const isiBagian = teratas.split('\n').slice(1).join(' ');
    const kalimat = isiBagian.split(/(?<=[.!?])\s+/).filter((k) => k.length > 20).slice(0, 2).join(' ');
    isi = { jawaban: `${kalimat || isiBagian.slice(0, 280)} [1]`, ditemukan: true, sumber_dipakai: [1] };
  }
  const keluar = JSON.stringify(isi);
  return {
    candidates: [{ content: { parts: [{ text: keluar }] } }],
    usageMetadata: { promptTokenCount: Math.ceil(teks.length / 4), candidatesTokenCount: Math.ceil(keluar.length / 4), thoughtsTokenCount: 0 },
    _tiruan: pertanyaan.length > 0,
  };
}

async function panggilGemini({ model, payload }) {
  if (cfg.gagalGemini) throw new Error('Gemini sengaja digagalkan (SIMULASI_GEMINI_GAGAL=1)');
  if (!cfg.geminiKey) return geminiTiruan(payload);
  const res = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/${cfg.geminiModel || model}:generateContent`, {
    method: 'POST',
    headers: { 'x-goog-api-key': cfg.geminiKey, 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
    signal: AbortSignal.timeout(45_000),
  });
  const teks = await res.text();
  if (!res.ok) throw new Error(`Gemini HTTP ${res.status}: ${teks.slice(0, 300)}`);
  return JSON.parse(teks);
}

// ------------------------------------------------------------ Workflow 2
async function tanyaJawab(body) {
  const label = `[tanya user ${body.user_id}]`;
  const keluaran = { Konfigurasi: [{ laravel_url: cfg.laravelUrl, model: nilaiSet(wfTanya, 'model'), body }] };

  // Node "Cari Materi" dan "Ambil FAQ" memakai On Error: Continue
  const aman = async (fn) => fn().catch((e) => ({ error: { message: e.message } }));
  keluaran['Cari Materi'] = [await aman(() => laravelJson('/api/n8n/pencarian-materi', { query: { user_id: body.user_id, q: body.pertanyaan, limit: 5 } }))];
  keluaran['Ambil FAQ'] = [await aman(() => laravelJson('/api/n8n/faq'))];
  const jumlah = keluaran['Cari Materi'][0].data?.length ?? 0;
  console.log(label, `"${String(body.pertanyaan).slice(0, 60)}"`, `-> ${jumlah} potongan materi`);

  const [prompt] = await jalankanKode(kodeDari(wfTanya, 'Susun Prompt'), [{}], keluaran);
  keluaran['Susun Prompt'] = [prompt];

  let jawaban;
  try {
    const gemini = await panggilGemini(prompt);
    [jawaban] = await jalankanKode(kodeDari(wfTanya, 'Olah Jawaban'), [gemini], keluaran);
  } catch (e) {
    console.log(label, 'jalur cadangan:', e.message);
    [jawaban] = await jalankanKode(kodeDari(wfTanya, 'Jawaban Cadangan'), [{}], keluaran);
  }
  console.log(label, `status=${jawaban.status}`, `ditemukan=${jawaban.ditemukan}`, `sumber=${jawaban.sumber.length}`);
  return jawaban;
}

// ------------------------------------------------------------ server webhook
const rute = {
  [webhookPath(wfIndeks)]: 'indeks',
  [webhookPath(wfTanya)]: 'tanya',
};

function balas(res, status, data) {
  res.writeHead(status, { 'Content-Type': 'application/json; charset=utf-8' });
  res.end(JSON.stringify(data));
}

http.createServer(async (req, res) => {
  const m = req.url.match(/^\/webhook(?:-test)?\/([^/?]+)/);
  const jenis = m && rute[m[1]];
  if (req.method !== 'POST' || !jenis) return balas(res, 404, { code: 404, message: 'Webhook tidak terdaftar' });
  if (req.headers['x-n8n-secret'] !== cfg.rahasia) return balas(res, 403, { message: 'Authorization data is wrong!' });

  let body;
  try {
    const potongan = [];
    for await (const p of req) potongan.push(p);
    body = JSON.parse(Buffer.concat(potongan).toString('utf8') || '{}');
  } catch {
    return balas(res, 400, { message: 'JSON tidak valid' });
  }

  try {
    if (jenis === 'indeks') {
      // "Respond Immediately": balas dulu, kerjakan di belakang
      balas(res, 200, { message: 'Workflow was started' });
      indeksDokumen(body).catch((e) => console.log(`[indeks berkas ${body.berkas_id}] GALAT:`, e.message));
    } else {
      balas(res, 200, await tanyaJawab(body));
    }
  } catch (e) {
    console.log('GALAT:', e.message);
    if (!res.headersSent) balas(res, 500, { message: e.message });
  }
}).listen(cfg.port, '0.0.0.0', () => {
  console.log(`Simulator n8n berjalan di http://localhost:${cfg.port}`);
  console.log(`  Laravel   : ${cfg.laravelUrl}`);
  console.log(`  Gemini    : ${cfg.gagalGemini ? 'dipaksa gagal' : cfg.geminiKey ? 'SUNGGUHAN' : 'tiruan (jawaban ekstraktif)'}`);
  console.log(`  Webhook   : /webhook/${webhookPath(wfIndeks)}  dan  /webhook/${webhookPath(wfTanya)}`);
});
