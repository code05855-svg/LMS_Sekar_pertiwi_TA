{{--
    Tabel responsif (gulir mendatar di layar sempit).
    <x-tabel-data :kolom="['Nama', 'Email', ['label' => 'Aksi', 'kelas' => 'text-right']]"> ...<tr> </x-tabel-data>
--}}
@props(['kolom' => []])

<div {{ $attributes->merge(['class' => 'scroll-tipis -mx-1 overflow-x-auto px-1']) }}>
    <table class="tabel">
        @if ($kolom)
            <thead>
                <tr>
                    @foreach ($kolom as $k)
                        @php($k = is_array($k) ? $k : ['label' => $k])
                        <th scope="col" class="{{ $k['kelas'] ?? '' }}">{{ $k['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
