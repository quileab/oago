<div x-data="{ mobileMenuOpen: false }" class="w-full relative font-sans z-50 bg-cover bg-center" style="background-image: url('{{ asset('themes/encendidorqta/backgroundnavbar.webp') }}');">
    <!-- Navbar Container -->
    <div class="w-full pt-0 pb-2 md:pb-4 min-h-[80px] md:min-h-[150px]">
        
        <div class="container mx-auto px-4 flex h-full">
            
            <!-- ====== DESKTOP LAYOUT (Oculto en móviles) ====== -->
            <div class="hidden md:flex items-center justify-center w-full gap-2 xl:gap-8 pb-2 md:pb-3">
                
                <!-- Logo ER -->
                <a href="/" class="flex flex-col items-center md:mt-2">
                    <img src="{{ asset('themes/encendidorqta/erlogo.png') }}" alt="ER" class="h-16 lg:h-20 object-contain drop-shadow-md" />
                    <img src="{{ asset('themes/encendidorqta/desde1975.png') }}" alt="Desde 1975" class="h-8 lg:h-10 object-contain mt-2 drop-shadow-md" />
                </a>

                <!-- Link Productos ER -->
                <a href="/?store=er" class="text-white hover:text-gray-300 transition-colors uppercase font-bold text-xs xl:text-sm tracking-wide whitespace-nowrap self-start pt-3">PRODUCTOS</a>
                
                <!-- Centro Gris (PEGADO AL TOP) -->
                <div class="bg-[#cccccc] rounded-b-xl shadow-lg px-3 xl:px-8 py-3 w-fit self-start order-first md:order-none mb-4 md:mb-0">
                    <div class="flex flex-nowrap justify-center items-center gap-3 xl:gap-8">
                        <a href="/" class="text-[#8c8c8c] hover:text-white transition-colors uppercase font-bold text-xs xl:text-sm tracking-wide whitespace-nowrap">Home</a>
                        <a href="/about" class="text-[#8c8c8c] hover:text-white transition-colors uppercase font-bold text-xs xl:text-sm tracking-wide whitespace-nowrap">Nosotros</a>
                        <a href="/locales" class="text-[#8c8c8c] hover:text-white transition-colors uppercase font-bold text-xs xl:text-sm tracking-wide whitespace-nowrap">Locales</a>
                        
                        @if(Auth::guest())
                            <a href="/login" class="text-[#8c8c8c] hover:text-white transition-colors uppercase font-bold text-xs xl:text-sm tracking-wide whitespace-nowrap">Login</a>
                        @else
                            @php $user = current_user(); @endphp
                            <div class="flex items-center gap-2 dropdown dropdown-bottom dropdown-end">
                                <label tabindex="0" class="btn btn-ghost btn-sm text-[#8c8c8c] hover:text-white uppercase font-bold hover:bg-transparent cursor-pointer">
                                    <x-icon name="o-user" class="w-4 h-4 mr-1"/> PANEL
                                </label>
                                <ul tabindex="0" class="dropdown-content z-[100] menu p-2 shadow-xl bg-[#cccccc] rounded-b-xl border-t border-gray-300 w-52 mt-3 text-[#8c8c8c]">
                                    <li><a href="/user/profile" class="hover:text-white transition-colors uppercase font-bold text-xs">Mi Perfil</a></li>
                                    <li><a href="/orders" class="hover:text-white transition-colors uppercase font-bold text-xs">Ordenes</a></li>
                                    @if($user->role->value === 'customer')
                                        <li><a href="/my-sales-agents" class="hover:text-white transition-colors uppercase font-bold text-xs">Mis Vendedores</a></li>
                                    @endif
                                    <li><a href="/logout" class="text-[#a6282e] hover:text-red-700 transition-colors uppercase font-bold text-xs">SALIR</a></li>
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Link Productos CIBAT -->
                <a href="/?store=cibat" class="text-white hover:text-gray-300 transition-colors uppercase font-bold text-xs xl:text-sm tracking-wide whitespace-nowrap self-start pt-3">PRODUCTOS</a>

                <!-- Logo CIBAT -->
                <a href="/?store=cibat" class="flex flex-col items-center md:mt-2">
                    <img src="{{ asset('themes/encendidorqta/cibatlogo.png') }}" alt="CIBAT" class="h-16 lg:h-20 object-contain drop-shadow-md" />
                    <img src="{{ asset('themes/encendidorqta/llavecodificada.png') }}" alt="Llaves Codificadas" class="h-8 lg:h-10 object-contain mt-2 drop-shadow-md z-50 lg:scale-[2.5] lg:origin-top" />
                </a>
                
            </div>

            <!-- ====== MOBILE LAYOUT (Oculto en Desktop) ====== -->
            <div class="flex md:hidden items-center w-full py-2 relative min-h-[5rem]">
                
                <!-- Botón Hamburguesa (Izquierda) -->
                <div class="absolute left-0 z-20">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-white hover:text-gray-300 p-2 focus:outline-none">
                        <x-icon name="o-bars-3" class="w-8 h-8" />
                    </button>
                </div>

                <!-- Logos Mobile (Centrados) -->
                <div class="absolute w-full left-0 flex justify-center items-center gap-16 z-10">
                    <a href="/" class="flex flex-col items-center">
                        <img src="{{ asset('themes/encendidorqta/erlogo.png') }}" alt="ER" class="h-12 object-contain drop-shadow-md" />
                    </a>
                    <a href="/" class="flex flex-col items-center">
                        <img src="{{ asset('themes/encendidorqta/cibatlogo.png') }}" alt="CIBAT" class="h-12 object-contain drop-shadow-md" />
                    </a>
                </div>
                
            </div>

        </div>
    </div>

    <!-- Menú Desplegable Mobile -->
    <div x-show="mobileMenuOpen" x-transition x-cloak class="md:hidden absolute top-full left-0 w-full bg-[#cccccc] shadow-2xl rounded-b-xl border-t border-gray-300 z-50">
        <div class="flex flex-col px-6 py-6 gap-5">
            <!-- Sección ER -->
            <a href="/?store=er" class="text-[#a6282e] uppercase font-extrabold text-sm tracking-wide">Productos ER</a>
            
            <hr class="border-gray-400">
            
            <!-- Links Centrales -->
            <a href="/" class="text-[#8c8c8c] hover:text-[#a6282e] transition-colors uppercase font-bold text-sm tracking-wide">Home</a>
            <a href="/about" class="text-[#8c8c8c] hover:text-[#a6282e] transition-colors uppercase font-bold text-sm tracking-wide">Nosotros</a>
            <a href="/locales" class="text-[#8c8c8c] hover:text-[#a6282e] transition-colors uppercase font-bold text-sm tracking-wide">Locales</a>
            
            @if(Auth::guest())
                <a href="/login" class="text-[#8c8c8c] hover:text-[#a6282e] transition-colors uppercase font-bold text-sm tracking-wide">Login o Registrarte</a>
            @else
                <div class="flex flex-col gap-3 pt-2 border-t border-gray-400">
                    <span class="text-gray-600 uppercase font-bold text-xs tracking-wide">Mi Panel</span>
                    <a href="/user/profile" class="text-[#8c8c8c] hover:text-[#a6282e] transition-colors uppercase font-bold text-sm tracking-wide ml-4">Mi Perfil</a>
                    <a href="/orders" class="text-[#8c8c8c] hover:text-[#a6282e] transition-colors uppercase font-bold text-sm tracking-wide ml-4">Ordenes</a>
                    @if(current_user()->role->value === 'customer')
                        <a href="/my-sales-agents" class="text-[#8c8c8c] hover:text-[#a6282e] transition-colors uppercase font-bold text-sm tracking-wide ml-4">Mis Vendedores</a>
                    @endif
                    <a href="/logout" class="text-[#a6282e] hover:text-red-700 uppercase font-bold text-sm tracking-wide ml-4">Salir</a>
                </div>
            @endif
            
            <hr class="border-gray-400">
            
            <!-- Sección CIBAT -->
            <a href="/?store=cibat" class="text-[#3d548f] hover:text-blue-800 uppercase font-extrabold text-sm tracking-wide">Productos CIBAT</a>
        </div>
    </div>
</div>
