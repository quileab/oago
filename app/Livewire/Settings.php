<?php

namespace App\Livewire;

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Setting;
use App\Helpers\SettingsHelper;

class Settings extends Component
{
    use Toast;

    public $settings_values = [];
    public $settingDetails = [];

    public function mount()
    {
        $settings = Setting::all();
        foreach ($settings as $setting) {
            $this->settingDetails[$setting->key] = $setting->toArray();
            $value = SettingsHelper::settings($setting->key);

            if ($setting->type === 'json' && is_array($value)) {
                $this->settings_values[$setting->key] = implode(',', $value);
            } else {
                $this->settings_values[$setting->key] = $value;
            }
        }
    }

    public function saveSetting(string $key)
    {
        $setting = Setting::where('key', $key)->first();
        if (!$setting) {
            $this->error('Setting not found');
            return;
        }

        $value = $this->settings_values[$key];

        if ($setting->type === 'json') {
            $value = array_map('trim', explode(',', $value));
        }

        SettingsHelper::update_setting($key, $value);

        $this->success('¡Guardado!', 'La configuración ha sido actualizada.');
    }

    public function render()
    {
        return view('livewire.settings');
    }
}