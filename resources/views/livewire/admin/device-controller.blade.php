<?php

use App\Models\Device;
use Livewire\Attributes\{Layout, Title, On, Computed};
use Livewire\Volt\Component;
use App\Models\Config;
use App\Models\Schedule;

new
#[Layout('components.layouts.app')]
#[Title('Kontrol Perangkat')]
class extends Component {
    public bool $relay1 = false;
    public bool $relay2 = false;
    public bool $servo_cover = false;
    public bool $communication = false;
    public int $fan_temp_limit = 32;
    public string $schedule = '';

    public function boot()
    {
        $this->communication = Config::where('key', 'communication')->value('value') ?? false;
    }

    public function mount()
    {
        $this->relay1 = Device::where('name', 'relay_1')->value('state') ?? false;
        $this->relay2 = Device::where('name', 'relay_2')->value('state') ?? false;
        $this->servo_cover = Device::where('name', 'servo_cover')->value('state') ?? false;
        $this->fan_temp_limit = Config::where('key', 'fan_temp_limit')->value('value') ?? 32;
    }

    #[On('refreshDevices')]
    public function refreshStates()
    {
        $this->mount();
    }

    public function toggle($device)
    {
        switch ($device) {
            case 'relay_1':
                $this->relay1 = !$this->relay1;
                Device::where('name', 'relay_1')->update(['state' => $this->relay1]);
                break;

            case 'relay_2':
                $this->relay2 = !$this->relay2;
                Device::where('name', 'relay_2')->update(['state' => $this->relay2]);
                break;

            case 'servo_cover':
                $this->servo_cover = !$this->servo_cover;
                Device::where('name', 'servo_cover')->update(['state' => $this->servo_cover]);
                break;
        }

        $this->dispatch('refreshDevices');
    }

    public function updated($property)
    {
        if ($property === 'communication') {
            try {
                Config::updateOrCreate([
                    'key' => 'communication'
                ], [
                    'value' => $this->communication
                ]);
                Device::where('name', 'relay_1')->update(['state' => false]);
                Device::where('name', 'relay_2')->update(['state' => false]);
                Device::where('name', 'servo_cover')->update(['state' => false]);
                $this->dispatch('toast', message: 'Mode berhasil diubah');
                $this->dispatch('refreshDevices');
            }catch (\Exception $e){
                $this->dispatch('refreshDevices');
                $this->dispatch('toast', type: 'error', message: 'Gagal mengubah mode' . $e->getMessage());
            }
        }
    }

    public function saveMaxTemperature()
    {
        try {
            Config::updateOrCreate([
                'key' => 'fan_temp_limit'
            ], [
                'value' => $this->fan_temp_limit
            ]);
            $this->dispatch('toast', message: 'Berhasil disimpan');
            $this->dispatch('refreshDevices');
        }catch (\Exception $e){
            $this->dispatch('refreshDevices');
            $this->dispatch('toast', type: 'error', message: 'Gagal menyimpan ' . $e->getMessage());
        }
    }

    public function saveSchedule()
    {
        try {
            Schedule::create([
                'time' => $this->schedule
            ]);
            $this->dispatch('toast', message: 'Berhasil disimpan');
            $this->dispatch('refreshDevices');
            $this->schedule = '';
        }catch (\Exception $e){
            $this->dispatch('refreshDevices');
            $this->dispatch('toast', type: 'error', message: 'Gagal menyimpan ' . $e->getMessage());
        }
    }

    #[Computed]
    public function schedules()
    {
        return Schedule::all();
    }

    public function setActiveSchedule($id)
    {
        $schedule = Schedule::findOrFail($id);
        try {
            Schedule::where('id', $id)->update([
                'active' => !$schedule->active
            ]);
//            $this->dispatch('toast', message: 'Status berhasil diubah');
            $this->dispatch('refreshDevices');
        }catch (\Exception $e){
            $this->dispatch('refreshDevices');
            $this->dispatch('toast', type: 'error', message: 'Gagal diubah ' . $e->getMessage());
        }
    }

    public function deleteSchedule($id)
    {
        $schedule = Schedule::findOrFail($id);
        try {
            $schedule->delete();
            $this->dispatch('toast', message: 'Berhasil dihapus');
            $this->dispatch('refreshDevices');
        }catch (\Exception $e){
            $this->dispatch('refreshDevices');
            $this->dispatch('toast', type: 'error', message: 'Gagal dihapus ' . $e->getMessage());
        }
    }

};
?>

<div class="space-y-4 -mt-3" wire:poll.5s>
    <h3 class="text-lg font-semibold mb-3">Kontrol Perangkat</h3>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        @if ($this->communication)
            <div class="p-5 rounded-xl border cursor-pointer
                    bg-gray-200/80 dark:bg-zinc-900
                    hover:ring-2 hover:ring-zinc-500
                    flex flex-col items-center justify-center">
                <flux:icon.spray-can class="{{ $relay1 ? 'text-zinc-500' : 'text-gray-400' }} mb-2"/>
                <h3 class="text-lg font-semibold">Penyiraman</h3>
                <p class="mt-2 text-sm {{ $relay1 ? 'text-zinc-600' : 'text-gray-500' }}">
                    {{ $relay1 ? 'Menyala' : 'Mati' }}
                </p>
            </div>
            <div class="p-5 rounded-xl border cursor-pointer
                    bg-gray-200/80 dark:bg-zinc-900
                    hover:ring-2 hover:ring-red-500
                    flex flex-col items-center justify-center">
                <flux:icon.fan class="{{ $relay2 ? 'text-red-500' : 'text-gray-400' }} mb-2"/>
                <h3 class="text-lg font-semibold">Kipas</h3>
                <p class="mt-2 text-sm {{ $relay2 ? 'text-red-600' : 'text-gray-500' }}">
                    {{ $relay2 ? 'Menyala' : 'Mati' }}
                </p>
            </div>
            <div class="p-5 rounded-xl border cursor-pointer
                    bg-gray-200/80 dark:bg-zinc-900
                    hover:ring-2 hover:ring-amber-500
                    flex flex-col items-center justify-center col-span-2 md:col-span-1">
                <flux:icon.sun class="{{ $servo_cover ? 'text-amber-500' : 'text-gray-400' }} mb-2"/>
                <h3 class="text-lg font-semibold">Penutup</h3>
                <p class="mt-2 text-sm {{ $servo_cover ? 'text-amber-600' : 'text-gray-500' }}">
                    {{ $servo_cover ? 'Terbuka' : 'Tertutup' }}
                </p>
            </div>
        @else
            <div wire:click="toggle('relay_1')"
                 class="p-5 rounded-xl border cursor-pointer
                    bg-white/80 dark:bg-zinc-900
                    hover:ring-2 hover:ring-sky-500
                    flex flex-col items-center justify-center">
                <flux:icon.spray-can class="{{ $relay1 ? 'text-sky-500' : 'text-gray-400' }} mb-2"/>
                <h3 class="text-lg font-semibold">Penyiraman</h3>
                <p class="mt-2 text-sm {{ $relay1 ? 'text-sky-600' : 'text-gray-500' }}">
                    {{ $relay1 ? 'Menyala' : 'Mati' }}
                </p>
            </div>
            <!-- Relay 2 -->
            <div wire:click="toggle('relay_2')"
                 class="p-5 rounded-xl border cursor-pointer
                    bg-white/80 dark:bg-zinc-900
                    hover:ring-2 hover:ring-red-500
                    flex flex-col items-center justify-center">
                <flux:icon.fan class="{{ $relay2 ? 'text-red-500' : 'text-gray-400' }} mb-2"/>
                <h3 class="text-lg font-semibold">Kipas</h3>
                <p class="mt-2 text-sm {{ $relay2 ? 'text-red-600' : 'text-gray-500' }}">
                    {{ $relay2 ? 'Menyala' : 'Mati' }}
                </p>
            </div>
            <!-- Servo Cover -->
            <div wire:click="toggle('servo_cover')"
                 class="p-5 rounded-xl border cursor-pointer
                    bg-white/80 dark:bg-zinc-900
                    hover:ring-2 hover:ring-amber-500
                    flex flex-col items-center justify-center col-span-2 md:col-span-1">
                <flux:icon.sun class="{{ $servo_cover ? 'text-amber-500' : 'text-gray-400' }} mb-2"/>
                <h3 class="text-lg font-semibold">Penutup</h3>
                <p class="mt-2 text-sm {{ $servo_cover ? 'text-amber-600' : 'text-gray-500' }}">
                    {{ $servo_cover ? 'Terbuka' : 'Tertutup' }}
                </p>
            </div>
        @endif
    </div>

    <div wire:cloak>
        <flux:fieldset>
            <h3 class="text-lg font-semibold mb-3">Kendali Otomatis</h3>

            <div class="space-y-4">
                <flux:switch wire:model.live="communication" label="Otomatisasi Pembacaan"
                             description="Pembacaan dilakukan otomatis tanpa kontrol manual dari pengguna."
                             data-checked="{{ $this->communication }}"/>
                <flux:separator variant="subtle"/>
                <flux:field>
                    <flux:label>Suhu °C</flux:label>
                    <flux:description>Kipas akan aktif saat suhu mencapai batas maksimum.</flux:description>
                    <flux:input.group>
                        <flux:input :disabled="!$this->communication" class="!focus:outline-none !focus:ring-0" kbd="°C" mask="999" wire:model="fan_temp_limit" type="number" min="0" max="100"/>
                        <flux:button :disabled="!$this->communication" icon="save" variant="primary" wire:click="saveMaxTemperature">Simpan</flux:button>
                    </flux:input.group>
                    <flux:error name="fan_temp_limit"/>
                </flux:field>
                <flux:separator variant="subtle"/>
                <flux:field>
                    <flux:label>Jadwal</flux:label>
                    <flux:description>Penjadwalan penyiraman otomatis.</flux:description>
                    <flux:input.group>
                        <flux:input :disabled="!$this->communication" class="!focus:outline-none !focus:ring-0" wire:model="schedule" type="time"/>
                        <flux:button :disabled="!$this->communication" icon="save" variant="primary" wire:click="saveSchedule">Simpan</flux:button>
                    </flux:input.group>
                    <flux:error name="schedule"/>
                </flux:field>
                <flux:separator variant="subtle"/>
                <flux:field>
                    <flux:label>Jadwal Penyiraman Otomatis</flux:label>
                    <flux:description>Sistem akan menyiram tanaman sesuai jadwal yang ditentukan</flux:description>
                    <small class="text-red-500 -mt-2 -mb-2">*Tekan status untuk mengubah aktif/tidak aktif</small>
                    <div class="overflow-x-auto rounded-lg shadow">
                        <table class="w-full text-sm text-left rtl:text-right text-zinc-500 dark:text-zinc-400">
                            <thead class="text-xs text-zinc-700 uppercase bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-100">
                            <tr>
                                <th class="p-3 text-nowrap text-center">No</th>
                                <th class="p-3 text-nowrap text-center">Waktu</th>
                                <th class="p-3 text-nowrap text-center">Status</th>
                                <th class="p-3 text-nowrap text-center">Aksi</th>
                            </tr>
                            </thead>
                            <tbody>
                                @forelse($this->schedules as $index => $schedule)
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 border-b border-gray-200 dark:border-zinc-700 text-center">{{ $loop->iteration }}</td>
                                    <td class="p-3 border-b border-gray-200 dark:border-zinc-700 text-center">{{ \Carbon\Carbon::parse($schedule->time)->format('H:i') }}</td>
                                    <td @class([
                                        'text-green-600 font-medium' => $schedule->active,
                                        'text-red-600 font-medium' => !$schedule->active,
                                        'w-30 p-3 border-b border-gray-200 dark:border-zinc-700 text-center'
                                        ])>
                                        <flux:button size="xs" :disabled="!$this->communication" wire:click="setActiveSchedule({{ $schedule->id }})" variant="primary" color="{{ $schedule->active ? 'green' : 'amber' }}">
                                            {{ $schedule->active ? 'Aktif' : 'Tidak Aktif' }}
                                        </flux:button>
                                    </td>
                                    <td class="text-center">
                                        <flux:button icon="trash" variant="danger" size="xs" :disabled="!$this->communication" wire:click="deleteSchedule({{ $schedule->id }})"/>
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center">
                                            Data tidak ditemukan
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </flux:field>
            </div>
        </flux:fieldset>
    </div>

</div>
