<?php

use App\Models\Device;
use Livewire\Attributes\{Layout, Title, On};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Kontrol Perangkat')]
class extends Component
{
    public $relay1 = false;
    public $relay2 = false;
    public $servo_cover = false;

    public function mount()
    {
        $this->relay1 = Device::where('name', 'relay_1')->value('state') ?? false;
        $this->relay2 = Device::where('name', 'relay_2')->value('state') ?? false;
        $this->servo_cover = Device::where('name', 'servo_cover')->value('state') ?? false;
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
};
?>

<div class="space-y-4 -mt-3" wire:poll.5s>
    <h3 class="text-lg font-semibold mb-3">Kontrol Perangkat</h3>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        <!-- Relay 1 -->
        <div wire:click="toggle('relay_1')"
             class="p-5 rounded-xl border cursor-pointer
                    bg-white/80 dark:bg-zinc-900
                    hover:ring-2 hover:ring-sky-500
                    flex flex-col items-center justify-center">
            <flux:icon.spray-can class="{{ $relay1 ? 'text-sky-500' : 'text-gray-400' }} mb-2" />
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
            <flux:icon.fan class="{{ $relay2 ? 'text-red-500' : 'text-gray-400' }} mb-2" />
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
            <flux:icon.sun class="{{ $servo_cover ? 'text-amber-500' : 'text-gray-400' }} mb-2" />
            <h3 class="text-lg font-semibold">Penutup</h3>
            <p class="mt-2 text-sm {{ $servo_cover ? 'text-amber-600' : 'text-gray-500' }}">
                {{ $servo_cover ? 'Terbuka' : 'Tertutup' }}
            </p>
        </div>
    </div>
</div>
