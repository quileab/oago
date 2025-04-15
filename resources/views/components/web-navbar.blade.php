<div class="w-full bg-gray-800 text-gray-100 flex justify-between items-center px-4 py-1">
  <img src="https://oagostini.com.ar/wp-content/uploads/logo1-1-1300x315.png"
    class="w-auto h-16 p-2 mx-3 hidden md:block" />
  <img src="{{ asset('imgs/oago.png') }}" class="w-auto h-16 p-2 mx-3 md:hidden" />

  <div>
    <div class="inline-flex items-center">
      <a href="/" class="hover:bg-gray-400 hover:text-black transition-all duration-300 p-4">Inicio</a>
      <a href="#" class="hover:bg-gray-400 hover:text-black transition-all duration-300 p-4">Nosotros</a>
      <a href="#" class="hover:bg-gray-400 hover:text-black transition-all duration-300 p-4">Contactos</a>

      <div class="inline-flex items-center">
        @if(Auth::guest())
      <x-button icon="o-user" class="btn-circle btn-outline ml-1" link="/login" />
    @else
    <x-dropdown label="{{ Auth::user()->name }}" class="btn-ghost z-55">
      <x-menu-item title="Ordenes de Compra" icon="o-archive-box" link="/orders" />
      <x-menu-item title="Option 2" icon="o-trash" />
      <x-menu-item title="Option 3" icon="o-arrow-path" />
      <x-button icon="o-arrow-right-start-on-rectangle" label="LOGOUT" link="/logout" class="btn-ghost btn-sm" />
    </x-dropdown>
  @endif
      </div>
    </div>
  </div>
</div>