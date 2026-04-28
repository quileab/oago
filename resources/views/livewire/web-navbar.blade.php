<div class="w-full bg-gray-800 text-gray-100 flex justify-between items-center px-2 py-0 my-0 sticky z-20">
  <img src="{{ asset('imgs/brand.webp') }}" class="w-auto h-16 p-2 mx-3 hidden md:block" />
  <img src="{{ asset('imgs/brand-logo.webp') }}" class="w-auto h-16 p-2 mx-3 md:hidden" />

  <div>
    <div class="inline-flex flex-wrap items-right align-middle justify-end">
      <a href="/" class="hover:bg-gray-400 hover:text-black transition-all duration-300 p-4">Inicio</a>
      <a href="/about" class="hover:bg-gray-400 hover:text-black transition-all duration-300 p-4">Nosotros</a>
      <a href="/registrate" class="hover:bg-gray-400 hover:text-black transition-all duration-300 p-4">Regístrate</a>

      <div class="inline-flex items-center">
        @if(count($salesCustomers) > 0 || $searchCustomer)
          <x-dropdown label="{{ $actingAsName ? 'Cliente: ' . $actingAsName : 'Seleccionar Cliente' }}" class="btn-ghost"
            icon="o-users">
            <div class="p-2" @click.stop>
              <x-input placeholder="Buscar..." wire:model.live.debounce="searchCustomer" icon="o-magnifying-glass"
                class="input-sm" />
            </div>
            @foreach($salesCustomers as $customer)
              <x-menu-item title="{{ is_object($customer) ? ($customer->full_name ?? 'ID: ' . ($customer->id ?? 'Unknown')) : 'ID: ' . $customer }}" wire:click="setActingCustomer({{ is_object($customer) ? ($customer->id ?? 0) : $customer }})" />
            @endforeach
          </x-dropdown>
        @endif

        @if(Auth::guest())
          <x-button label="INGRESAR" icon="o-lock-closed" class="btn btn-ghost ml-1" link="/login" />
        @else
          @php $user = current_user(); @endphp
          <div class="flex items-center gap-2">
            <x-dropdown label="{{ $user->name }}" class="btn-ghost" title="{{ $user->role->value }}">
              <x-menu-item title="Mi Perfil" icon="o-user" link="/user/profile" />
              <x-menu-item title="Ordenes de Compra" icon="o-archive-box" link="/orders" />
              @if($user->role->value === 'customer')
                <x-menu-item title="Mis Vendedores" icon="o-users" link="/my-sales-agents" />
              @endif
              <x-menu-item title="SALIR" icon="o-arrow-right-start-on-rectangle" link="/logout" no-wire-navigate />
            </x-dropdown>

            @if($user instanceof \App\Models\AltUser && isset($trial_days_remaining))
              <div class="tooltip tooltip-bottom tooltip-warning" data-tip="Días de prueba restantes">
                <span class="badge badge-warning badge-sm font-bold cursor-help">{{ $trial_days_remaining }}</span>
              </div>
            @endif
          </div>
        @endif
      </div>
    </div>
  </div>
</div>