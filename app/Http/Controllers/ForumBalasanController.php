<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBalasanForumRequest;
use App\Models\ForumBalasan;
use App\Models\ForumTopik;
use Illuminate\Http\RedirectResponse;

class ForumBalasanController extends Controller
{
    private const PER_HALAMAN = 15;

    public function store(StoreBalasanForumRequest $request, ForumTopik $topik): RedirectResponse
    {
        $this->authorize('balas', $topik);

        $balasan = $topik->balasan()->create($request->validated() + ['user_id' => $request->user()->id]);

        return $this->kembaliKe($topik, $balasan, 'Balasan terkirim.');
    }

    public function update(StoreBalasanForumRequest $request, ForumBalasan $balasan): RedirectResponse
    {
        $this->authorize('update', $balasan);

        $balasan->update($request->validated());

        return $this->kembaliKe($balasan->topik, $balasan, 'Balasan diperbarui.');
    }

    public function destroy(ForumBalasan $balasan): RedirectResponse
    {
        $this->authorize('delete', $balasan);

        $topik = $balasan->topik;
        $balasan->delete();

        return redirect()->route('forum.topik.show', $topik)->with('sukses', 'Balasan dihapus.');
    }

    /** Arahkan ke halaman dan posisi balasan itu berada. */
    private function kembaliKe(ForumTopik $topik, ForumBalasan $balasan, string $pesan): RedirectResponse
    {
        $urutan = $topik->balasan()->where('id', '<=', $balasan->id)->count();
        $halaman = (int) ceil($urutan / self::PER_HALAMAN);

        return redirect()
            ->to(route('forum.topik.show', ['topik' => $topik, 'page' => $halaman]).'#balasan-'.$balasan->id)
            ->with('sukses', $pesan);
    }
}
