<div class="w-full bg-gray-800 text-gray-100 flex justify-between items-center px-4 py-2">
  <img src="https://oagostini.com.ar/wp-content/uploads/logo1-1-1300x315.png" class="w-auto h-16 p-2 mx-3" />
  <div class="inline-flex items-center gap-x-3">
    <a href="#" class="mx-2">Inicio</a>
    <a href="#">Nosotros</a>
    <a href="#">Contactos</a>
    <a href="#">Opción 1</a>
    <a href="#">Opción 2</a>
  </div>
  <div class="inline-flex items-center gap-x-3">
    @if(Auth::guest())
      <x-button icon="o-user" class="btn-circle btn-outline" link="/login" />  
    @else
    <x-button icon-right="o-arrow-right-start-on-rectangle" label="{{ Auth::user()->name }}" link="/logout" class="btn-ghost" />
      <x-button icon="o-shopping-cart" class="btn-circle btn-outline" />
    @endif
  </div>
</div>