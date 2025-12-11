<div class="w-full bg-gray-800 text-gray-100 flex justify-between items-center px-2 py-0 my-0 sticky z-20">
  <img src="{{ asset('imgs/oago-big.png') }}" class="w-auto h-16 p-2 mx-3 hidden md:block" />
  <img src="{{ asset('imgs/oago.png') }}" class="w-auto h-16 p-2 mx-3 md:hidden" />

  <div>
    <div class="inline-flex flex-wrap items-right align-middle justify-end">
      <a href="/" class="hover:bg-gray-400 hover:text-black transition-all duration-300 p-4">Inicio</a>
      <a href="/about" class="hover:bg-gray-400 hover:text-black transition-all duration-300 p-4">Nosotros</a>
      <a href="/contact" class="hover:bg-gray-400 hover:text-black transition-all duration-300 p-4">Contactos</a>

      <div class="inline-flex items-center">
        @if(count($salesCustomers) > 0 || $searchCustomer)
          <x-dropdown label="{{ $actingAsName ? 'Cliente: ' . $actingAsName : 'Seleccionar Cliente' }}" class="btn-ghost"
            icon="o-users">
            <div class="p-2" @click.stop>
              <x-input placeholder="Buscar..." wire:model.live.debounce="searchCustomer" icon="o-magnifying-glass"
                class="input-sm" />
            </div>
            @foreach($salesCustomers as $customer)
              <x-menu-item title="{{ $customer->full_name }}" wire:click="setActingCustomer({{ $customer->id }})" />
            @endforeach
          </x-dropdown>
        @endif

        @if(Auth::guest())
          <x-button label="INGRESAR" icon="o-lock-closed" class="btn btn-ghost ml-1" link="/login" />
          <span class="opacity-50">|</span>
          <x-button label="REGISTRARSE" icon="o-check-circle" class="btn btn-ghost ml-1" link="/register" />
        @else
          <x-dropdown label="{{ Auth::user()->name }}" class="btn-ghost" title="{{ Auth::user()->role->value }}">
            <x-menu-item title="Ordenes de Compra" icon="o-archive-box" link="/orders" />
            @if(Auth::user()->role->value === 'customer')
              <x-menu-item title="Mis Vendedores" icon="o-users" link="/my-sales-agents" />
            @endif
            <x-menu-item title="SALIR" icon="o-arrow-right-start-on-rectangle" link="/logout" no-wire-navigate />
          </x-dropdown>
        @endif
        @if(Auth::check() && Auth::user()->role->value == 'guest')
          @if($trial_days_remaining)
            <span class="text-sm opacity-50">Dias pendientes: {{ $trial_days_remaining }}</span>
          @endif
        @endif
      </div>
    </div>
  </div>
</div>