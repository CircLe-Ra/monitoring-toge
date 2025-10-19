<?php

use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    public $month;
    public $year;

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function downloadReport()
    {
        $this->dispatch('download-report', [
            'month' => $this->month,
            'year' => $this->year
        ]);
    }
};
?>

<div class="-mt-4">
    <h2 class="text-lg font-semibold border p-1 rounded-lg text-center">Laporan Pertumbuhan Toge</h2>

    <div class="flex items-center justify-between gap-3 mt-2 p-4 rounded-lg border">
        <div>
            <label class="text-sm text-gray-600">Bulan</label>
            <flux:select wire:model="month" class="select select-bordered w-full">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}">
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </flux:select>
        </div>

        <div>
            <label class="text-sm text-gray-600">Tahun</label>
            <flux:select wire:model="year" class="select select-bordered w-full">
                @foreach(range(now()->year - 3, now()->year + 1) as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </flux:select>
        </div>

        <flux:button
            variant="primary"
            wire:click="downloadReport"
            class="mt-6">
            Unduh PDF
        </flux:button>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('download-report', ([data]) => {
            const { month, year } = data;
            const url = `/reports/planting/pdf?month=${month}&year=${year}`;
            window.open(url, '_blank');
        });
    });
</script>
