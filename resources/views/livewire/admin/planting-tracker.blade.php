<?php

use App\Models\GrowthStage;
use App\Models\Planting;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Carbon\Carbon;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.app')]
#[Title('Siklus Penanaman Toge')]
class extends Component {
    use WithFileUploads;

    public $plantings;
    public $form = [
        'plant_name' => '',
        'planted_at' => '',
        'estimated_days_to_harvest' => 3,
    ];
    public $showFormModal = false;
    public $showCycleModal = false;
    public $showCycleDetailModal = false;
    public $editId = null;
    public $plant;

    public $cycleForm = [
        'photo' => null,
    ];
    public $stageNames = [
        ['stage_name' => 'Perendaman', 'day_start' => 0, 'day_end' => 1],
        ['stage_name' => 'Perkecambahan', 'day_start' => 1, 'day_end' => 2],
        ['stage_name' => 'Pertumbuhan Daun', 'day_start' => 2, 'day_end' => 3],
        ['stage_name' => 'Siap Panen', 'day_start' => 3, 'day_end' => 4],
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->plantings = Planting::with('growthStages')->latest()->get();
    }

    public function openForm($id = null)
    {
        $this->resetValidation();
        $this->editId = $id;

        if ($id) {
            $planting = Planting::findOrFail($id);
            $this->form = [
                'plant_name' => $planting->plant_name,
                'planted_at' => $planting->planted_at->format('Y-m-d'),
                'estimated_days_to_harvest' => $planting->estimated_days_to_harvest,
            ];
        } else {
            $this->form = ['plant_name' => '', 'planted_at' => '', 'estimated_days_to_harvest' => 3];
        }

        $this->showFormModal = true;
    }

    public function save()
    {
        $this->validate([
            'form.plant_name' => 'required|string|max:255',
            'form.planted_at' => 'required|date',
            'form.estimated_days_to_harvest' => 'required|integer|min:1',
        ]);

        Planting::updateOrCreate(
            ['id' => $this->editId],
            $this->form
        );

        $this->showFormModal = false;
        $this->loadData();
        $this->dispatch('toast', message: 'Data penanaman berhasil disimpan.');
    }

    public function delete($id)
    {
        Planting::findOrFail($id)->delete();
        $this->loadData();
        $this->dispatch('toast', message: 'Data penanaman berhasil dihapus.');
    }

    public function addCycle($id)
    {
        $this->editId = $id;
        $plant = Planting::with('growthStages')->findOrFail($id);
        $this->cycleCount = $plant->growthStages->count();

        if ($this->cycleCount >= count($this->stageNames)) {
            $this->dispatch('toast', message: 'Semua tahapan pertumbuhan sudah ditambahkan.');
            return;
        }

        $this->cycleForm = ['photo' => null];
        $this->showCycleModal = true;
    }

    public function saveCycle()
    {
        $plant = Planting::with('growthStages')->findOrFail($this->editId);
        $this->cycleCount = $plant->growthStages->count();

        if ($this->cycleCount >= count($this->stageNames)) {
            $this->dispatch('toast', message: 'Semua tahapan pertumbuhan sudah ditambahkan.');
            return;
        }

        $stage = $this->stageNames[$this->cycleCount];

        $this->validate([
            'cycleForm.photo' => 'nullable|image|max:10240',
        ]);

        $photoPath = null;
        if ($this->cycleForm['photo']) {
            $photoPath = $this->cycleForm['photo']->store('growth_stages', 'public');
        }

        GrowthStage::create([
            'planting_id' => $this->editId,
            'stage_name' => $stage['stage_name'],
            'day_start' => $stage['day_start'],
            'day_end' => $stage['day_end'],
            'photo' => $photoPath,
        ]);

        $this->showCycleModal = false;
        $this->loadData();
        $this->dispatch('toast', message: 'Tahapan pertumbuhan berhasil ditambahkan.');
    }

    public function deleteCycle()
    {
        $stage = GrowthStage::find($this->editId);
        if ($stage->photo){
            Storage::delete($stage->photo);
        }
        $stage->delete();
        $this->__reset();
        $this->loadData();
        $this->showCycleDetailModal = false;
        $this->dispatch('toast', message: 'Tahapan pertumbuhan berhasil dihapus.');
    }

    public function showCycleDetail($id)
    {
        $this->editId = $id;
        $this->plant = GrowthStage::find($id);
        $this->showCycleDetailModal = true;
    }

    public function __reset(){
        $this->reset(['editId', 'plant']);
        $this->form = ['plant_name' => '', 'planted_at' => '', 'estimated_days_to_harvest' => 3];
        $this->dispatch('pond-reset');
    }
};
?>

<div class="-mt-4">
    <flux:button icon="plus" size="sm" class="w-full sm:w-auto mb-2" wire:click="openForm">Tambah</flux:button>
    <div class="grid grid-cols-1 sm:grid-cols-2">
        @forelse ($this->plantings as $plant)
            <div class="border rounded-xl p-2 hover:shadow-lg hover:scale-[1.02] duration-200">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        <img src="https://cdn-icons-png.flaticon.com/512/7669/7669449.png"
                             alt="Toge Icon"
                             class="w-10 h-10 sm:w-12 sm:h-12 object-contain"/>
                        <div class="w-full">
                            <h3 class="font-semibold text-base sm:text-lg">{{ $plant->plant_name }}</h3>
                            <table class="w-full text-xs text-zinc-500 mt-1 ">
                                <tbody>
                                <tr>
                                    <td class="py-0.5 w-1/3">🌾 Ditanam</td>
                                    <td class="py-0.5 w-full">: {{ $plant->planted_at_formatted }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 w-1/3">&nbsp;⏱&nbsp;Usia</td>
                                    <td class="py-0.5 w-full">: {{ $plant->age_harvest }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 w-1/3">🥬 Panen</td>
                                    <td class="py-0.5 w-full">: {{ $plant->estimated_harvest_date_formatted }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Progress Pertumbuhan --}}
                    @php
                        $progress = 0;
                        if ($plant->estimated_days_to_harvest > 0) {
                            $progress = (int)round(($plant->days_since_planted / $plant->estimated_days_to_harvest) * 100);
                            if ($progress < 0) $progress = 0;
                            if ($progress > 100) $progress = 100;
                        }
                    @endphp
                    <div class="w-full bg-zinc-300 dark:bg-zinc-700 rounded-full h-2.5 mt-2">
                        <div class="bg-green-500 h-2.5 rounded-full transition-all"
                             style="width: {{ $progress }}%"></div>
                    </div>

                    {{-- Status --}}
                    <div class="flex justify-between items-center mt-1 mb-2">
                        @if ($plant->is_ready_to_harvest)
                            <span class="badge bg-green-700 text-white px-2">✅ Siap Panen</span>
                        @else
                            <span class="badge bg-yellow-500 text-white px-2">🌿 Tahap Pertumbuhan</span>
                        @endif
                        <span class="text-xs text-zinc-500">{{ $progress }}%</span>
                    </div>

                    {{-- Info Growth Stage --}}
                    @if ($plant->growthStages && $plant->growthStages->count() > 0)
                        <div class=" border-t border-zinc-300 dark:border-zinc-700 pt-2">
                            <h4 class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">📈 Tahapan
                                Pertumbuhan:</h4>
                            <ul class="text-xs space-y-1">
                                @php $nowDay = $plant->days_since_planted; @endphp
                                @foreach ($plant->growthStages as $stage)
                                    @php
                                        $isCurrent = ($nowDay >= $stage->day_start && $nowDay < $stage->day_end);
                                    @endphp
                                    <li class="{{ $isCurrent ? 'text-green-600 font-semibold' : 'text-zinc-700' }}">
                                        <div class="flex items-center justify-between">
                                            <div>
                                            • {{ $stage->stage_name }} <span class="text-zinc-400"> (Hari {{ $stage->day_start }}–{{ $stage->day_end }})</span>
                                            @if ($isCurrent)
                                                <span class="text-xs text-green-500 ml-1">← Tahap Saat Ini</span>
                                            @endif
                                            </div>
                                            <flux:button size="xs" icon="eye" variant="ghost" :loading="false" wire:click="showCycleDetail({{ $stage->id }})" />
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- Tombol aksi --}}
                <div class="flex justify-between gap-2 border-t mt-2 border-base-300 p-3 bg-base-100">
                    <div>
                        @if($plant->growthStages->count() < 4)
                            <flux:button size="xs" color="blue" variant="primary" icon="plus" class="cursor-pointer" wire:click="addCycle({{ $plant->id }})">
                                Siklus
                            </flux:button>
                        @else
                            <flux:button size="xs" color="gray" variant="primary" icon="plus" disabled class="cursor-not-allowed">
                                Siklus
                            </flux:button>
                        @endif
                    </div>
                    <div>
                        <flux:button size="xs" icon="pencil" wire:click="openForm({{ $plant->id }})">Edit</flux:button>
                        <flux:button size="xs" icon="trash" wire:click="delete({{ $plant->id }})" variant="danger">
                            Hapus
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-sm text-zinc-500 p-6 border rounded-md">Belum ada data penanaman 🌱</p>
        @endforelse
    </div>

    {{-- Modal Form --}}
    <flux:modal class="w-[calc(100%-10px)] md:w-96" wire:model.self="showFormModal" @close="__reset()">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $editId ? 'Edit Penanaman' : 'Tambah Penanaman' }}</flux:heading>
                <flux:text class="">Tambah atau Ubah Data Penanaman disini.</flux:text>
            </div>
            <flux:input label="Nama Tanaman" wire:model.defer="form.plant_name"/>
            <flux:input label="Tanggal Tanam" type="date" wire:model.defer="form.planted_at"/>
            <flux:input label="Estimasi Hari Panen" type="number" min="1"
                        wire:model.defer="form.estimated_days_to_harvest"/>
        </div>
        <flux:button wire:click="save" icon="check" variant="primary" class="w-full sm:w-auto mt-3">Simpan</flux:button>
    </flux:modal>
    <flux:modal class="w-[calc(100%-10px)] md:w-96" wire:model.self="showCycleModal" @close="__reset()">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Tambah Siklus</flux:heading>
                <flux:text class="">Tambahkan siklus penanaman disini.</flux:text>
            </div>

            @php
                $plant = $this->plantings->firstWhere('id', $this->editId);
                $count = $plant ? $plant->growthStages->count() : 0;
                $stages = ['Perendaman', 'Perkecambahan', 'Pertumbuhan Daun', 'Siap Panen'];
            @endphp

            <div class="space-y-2">
                @if ($count < count($stages))
                    <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded-md text-sm">
                        <p>Tahap ke-{{ $count + 1 }}: <strong>{{ $stages[$count] }}</strong></p>
                        <p class="text-xs text-zinc-500">Upload foto untuk tahap ini.</p>
                    </div>

                    <x-filepond label="Foto Tahapan" wire:model="cycleForm.photo" />

                    <flux:button wire:click="saveCycle" icon="check" variant="primary" class="w-full sm:w-auto mt-3">
                        Simpan Tahapan
                    </flux:button>
                @else
                    <div class="text-center text-sm text-zinc-500">
                        Semua tahapan sudah ditambahkan ✅
                    </div>
                @endif
            </div>

        </div>
    </flux:modal>
    <flux:modal class="w-[calc(100%-10px)] md:w-96" wire:model.self="showCycleDetailModal" @close="__reset()">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Detail Siklus</flux:heading>
                <flux:text class="">Informasi siklus penanaman toge.</flux:text>
            </div>

            <div class="space-y-2">
                @if ($this->plant)
                    <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded-md text-sm">
                        <p>Tahap : <strong>{{ $this->plant->stage_name ?? '-' }}</strong></p>
                        <p class="text-xs text-zinc-500">{{ ($this->plant->photo ? 'Foto telah diunggah.':'Tidak ada foto yang diuanggah.') ?? '-' }}</p>
                    </div>

                    @if ($this->plant->photo)
                        <img src="{{ asset('storage/' . $this->plant->photo) }}" alt="Foto Tahapan" class="size-full object-cover">
                    @endif

                    <flux:button wire:click="deleteCycle" icon="trash" variant="danger" class="w-full sm:w-auto mt-3">
                        Hapus Siklus
                    </flux:button>
                @else
                    <div class="text-center text-sm text-zinc-500">
                        Semua tahapan sudah ditambahkan ✅
                    </div>
                @endif
            </div>

        </div>
    </flux:modal>
</div>
