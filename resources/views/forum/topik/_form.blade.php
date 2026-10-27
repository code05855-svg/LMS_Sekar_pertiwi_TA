<div class="space-y-5">
    <x-field name="judul" label="Judul topik" :value="old('judul', $topik->judul)" placeholder="Contoh: Bagaimana cara menyelesaikan persamaan linear?" required maxlength="200" />
    <x-field type="textarea" name="isi" label="Isi pertanyaan atau diskusi" :value="old('isi', $topik->isi)" rows="8" required maxlength="5000"
             placeholder="Jelaskan pertanyaanmu sejelas mungkin. Sebutkan materi atau bagian yang kamu tanyakan." />
</div>
