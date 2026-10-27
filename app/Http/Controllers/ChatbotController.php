<?php

namespace App\Http\Controllers;

use App\Http\Requests\KirimPertanyaanRequest;
use App\Models\Faq;
use App\Models\RiwayatChat;
use App\Services\ChatbotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Chatbot akademik (proses 6): tanya jawab dari materi guru dan FAQ LMS. */
class ChatbotController extends Controller
{
    private const KUNCI_SESI = 'chatbot_sesi_id';

    public function index(Request $request): View
    {
        $sesiId = $request->session()->get(self::KUNCI_SESI);

        $percakapan = $sesiId
            ? RiwayatChat::milik($request->user())->sesi($sesiId)->oldest()->limit(50)->get()
            : collect();

        // Widget melayang memuat percakapan aktif lewat fetch (?fragmen=1).
        if ($request->boolean('fragmen')) {
            return view('chatbot.partials.percakapan', ['percakapan' => $percakapan]);
        }

        return view('chatbot.index', [
            'percakapan' => $percakapan,
            'saran' => Faq::aktif()->orderBy('id')->limit(4)->pluck('pertanyaan'),
        ]);
    }

    /** Dipanggil lewat fetch(): mengembalikan potongan HTML jawaban (partials/pesan). */
    public function kirim(KirimPertanyaanRequest $request, ChatbotService $chatbot): View
    {
        // Satu percakapan = satu UUID di session; "Percakapan baru" menghapusnya.
        $sesiId = $request->session()->get(self::KUNCI_SESI);

        if (! $sesiId) {
            $sesiId = (string) Str::uuid();
            $request->session()->put(self::KUNCI_SESI, $sesiId);
        }

        // Tutup session sebelum menunggu n8n supaya permintaan lain dari pengguna
        // yang sama (mis. membuka halaman lain) tidak antre menunggu kunci session.
        $request->session()->save();

        $riwayat = $chatbot->tanya($request->user(), $request->validated('pertanyaan'), $sesiId);

        return view('chatbot.partials.pesan', ['riwayat' => $riwayat]);
    }

    public function baru(Request $request): RedirectResponse
    {
        $request->session()->forget(self::KUNCI_SESI);

        return redirect()->route('chatbot.index');
    }

    /** Semua pertanyaan milik pengguna, dikelompokkan per percakapan. */
    public function riwayat(Request $request): View
    {
        $daftar = RiwayatChat::milik($request->user())
            ->latest()
            ->paginate(15);

        return view('chatbot.riwayat', ['daftar' => $daftar]);
    }
}
