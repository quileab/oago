<div class="w-full bg-neutral text-neutral-content shadow-lg border-b-[3px] border-primary sticky top-0 z-50 transition-all duration-300">
  <div class="max-w-7xl mx-auto flex justify-between items-center px-4 py-2">
      <a href="/" class="flex-shrink-0 hover:scale-105 transition-transform">
        <img src="{{ asset('imgs/Logos/ER50.png') }}" class="w-auto h-14 p-1 hidden md:block logo-glow rounded-lg" alt="Encendido Reconquista" />
        <img src="{{ asset('imgs/Logos/ER50.png') }}" class="w-auto h-12 p-1 md:hidden logo-glow rounded-lg" alt="Encendido Reconquista" />
      </a>

      <div class="flex items-center gap-1 sm:gap-4">
        <div class="hidden md:flex items-center gap-2">
          <a href="/" class="font-bold uppercase tracking-wider text-xs px-3 py-2 rounded-md hover:bg-white/10 hover:text-primary transition-colors">Inicio</a>
          <a href="/about" class="font-bold uppercase tracking-wider text-xs px-3 py-2 rounded-md hover:bg-white/10 hover:text-primary transition-colors">Nosotros</a>
          @if(Auth::guest())
              <a href="/registrate" class="font-bold uppercase tracking-wider text-xs px-3 py-2 rounded-md hover:bg-white/10 hover:text-primary transition-colors">Regístrate</a>
          @endif
        </div>

        <div class="flex items-center gap-2 border-l border-white/10 pl-2 sm:pl-4">
          @if(count($salesCustomers) > 0 || $searchCustomer)
            <x-dropdown label="{{ $actingAsName ? 'Cliente: ' . $actingAsName : 'Seleccionar Cliente' }}" class="btn-sm btn-outline border-primary/50 text-primary-content hover:bg-primary hover:border-primary"
              icon="o-users">
              <div class="p-2 bg-base-100" @click.stop>
                <x-input placeholder="Buscar..." wire:model.live.debounce="searchCustomer" icon="o-magnifying-glass"
                  class="input-sm bg-base-200 text-base-content" />
              </div>
              <div class="bg-base-100 text-base-content max-h-60 overflow-y-auto">
                @foreach($salesCustomers as $customer)
                  @php
                    $cId = is_object($customer) ? ($customer->id ?? 0) : ($customer['id'] ?? 0);
                    $cName = is_object($customer)
                        ? ($customer->full_name ?? 'ID: ' . $cId)
                        : (trim(($customer['lastname'] ?? '') . ', ' . ($customer['name'] ?? ''), ', ') ?: 'ID: ' . $cId);
                  @endphp
                  <x-menu-item title="{{ $cName }}" wire:click="setActingCustomer({{ $cId }})" class="hover:bg-primary hover:text-primary-content" />
                @endforeach
              </div>
            </x-dropdown>
          @endif

          @if(Auth::guest())
            <x-button label="INGRESAR" icon="o-lock-closed" class="btn btn-sm btn-primary shadow-lg shadow-primary/20 font-black" link="/login" />
          @else
            @php $user = current_user(); @endphp
            <div class="flex items-center gap-2">
              <x-dropdown label="{{ $user->name }}" class="btn-sm btn-ghost hover:bg-white/10 text-neutral-content font-bold" title="{{ $user->role->value }}">
                <div class="bg-base-100 text-base-content rounded-lg shadow-xl border border-base-200">
                    <x-menu-item title="Mi Perfil" icon="o-user" link="/user/profile" class="hover:bg-base-200" />
                    <x-menu-item title="Ordenes de Compra" icon="o-archive-box" link="/orders" class="hover:bg-base-200" />
                    @if($user->role->value === 'customer')
                      <x-menu-item title="Mis Vendedores" icon="o-users" link="/my-sales-agents" class="hover:bg-base-200" />
                    @endif
                    <div class="border-t border-base-200 my-1"></div>
                    <x-menu-item title="SALIR" icon="o-arrow-right-start-on-rectangle" link="/logout" no-wire-navigate class="hover:bg-error hover:text-error-content text-error font-bold" />
                </div>
              </x-dropdown>

              @if($user instanceof \App\Models\AltUser && isset($trial_days_remaining))
                <div class="tooltip tooltip-bottom tooltip-warning" data-tip="Días de prueba restantes">
                  <span class="badge badge-warning badge-sm font-bold cursor-help drop-shadow-md">{{ $trial_days_remaining }}</span>
                </div>
              @endif
            </div>
          @endif
        </div>
      </div>
  </div>
</div>