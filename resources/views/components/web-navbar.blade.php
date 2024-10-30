<div class="w-full bg-gray-800 text-gray-100 flex justify-between items-center px-4 py-2">
  <img src="https://oagostini.com.ar/wp-content/uploads/logo1-1-1300x315.png" class="w-auto h-16 p-2 mx-3" />
  <div class="inline-flex items-center gap-x-3">
    <a href="/" class="mx-2">Inicio</a>
    <a href="#">Nosotros</a>
    <a href="#">Contactos</a>
    <a href="#">Opción 1</a>
    <a href="#">Opción 2</a>
  </div>
  <div class="inline-flex items-center gap-x-3">
    @if(Auth::guest())
      <x-button icon="o-user" class="btn-circle btn-outline" link="/login" />  
    @else
    <x-dropdown label="{{ Auth::user()->name }}" class="btn-ghost">
      <x-menu-item title="Ordenes de Compra" icon="o-archive-box" link="/orders" />
      <x-menu-item title="Option 2" icon="o-trash" />
      <x-menu-item title="Option 3" icon="o-arrow-path" />
      <x-button icon="o-arrow-right-start-on-rectangle" label="LOGOUT" link="/logout" class="btn-ghost btn-sm" />
    </x-dropdown>
    @endif
  </div>
</div>