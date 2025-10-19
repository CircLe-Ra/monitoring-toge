<?php

use App\Models\Sensor;
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Dashboard Sensor')]
class extends Component {

    #[Computed]
    public function sensors()
    {
        return Sensor::orderByDesc('measured_at')->take(10)->get();
    }

    #[Computed]
    public function latest()
    {
        return Sensor::orderByDesc('measured_at')->first();
    }
};
?>

<div>
    <h3 class="text-lg font-semibold mb-3 -mt-3">Sensor</h3>
    <div class="grid grid-cols-2 gap-2 mb-3" wire:poll.10s>
        <div class="p-5 rounded-xl border bg-white/80 dark:bg-zinc-900 flex flex-col items-center">
            <flux:icon.thermometer class="text-red-500 mb-2" />
            <h3 class="text-lg font-semibold">Suhu</h3>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">
                {{ $this->latest?->temperature ?? '--' }} °C
            </p>
        </div>

        <div class="p-5 rounded-xl border bg-white/80 dark:bg-zinc-900 flex flex-col items-center">
            <flux:icon.droplet class="text-blue-500 mb-2" />
            <h3 class="text-lg font-semibold">Kelembapan</h3>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                {{ $this->latest?->humidity ?? '--' }} %
            </p>
        </div>

        <div class="p-5 col-span-2 rounded-xl border bg-white/80 dark:bg-zinc-900 flex flex-col items-center">
            <flux:icon.sun class="text-yellow-500 mb-2" />
            <h3 class="text-lg font-semibold">Intensitas Cahaya</h3>
            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                {{ $this->latest?->light_level ?? '--' }} lx
            </p>
        </div>
    </div>

    <h3 class="text-lg font-semibold mb-3">Riwayat Pembacaan</h3>
    <!-- Tabel Riwayat -->
    <div class="border rounded-xl p-4 bg-white/70 dark:bg-zinc-900 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b border-gray-300 dark:border-zinc-700">
            <tr class="text-left">
                <th class="py-2 px-3">#</th>
                <th class="py-2 px-3 text-center">Suhu (°C)</th>
                <th class="py-2 px-3 text-center">Kelembapan (%)</th>
                <th class="py-2 px-3 text-center">Cahaya</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($this->sensors as $index => $sensor)
                <tr class="border-b border-gray-200 dark:border-zinc-700">
                    <td class="py-2 px-3">{{ $index + 1 }}</td>
                    <td class="py-2 px-3 text-center">{{ $sensor->temperature }}</td>
                    <td class="py-2 px-3 text-center">{{ $sensor->humidity }}</td>
                    <td class="py-2 px-3 text-center">{{ $sensor->light_level == '1' ? 'Tidak Ada' : 'Ada' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-3 text-center text-gray-500">Belum ada data sensor.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
