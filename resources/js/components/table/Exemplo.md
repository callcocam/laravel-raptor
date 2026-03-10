<!DOCTYPE html>

<html class="dark" lang="pt-br"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3c80f6",
                        "background-light": "#f5f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
<style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .material-symbols-outlined {
            font-size: 20px;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen">
<!-- Top Navigation Bar -->
<header class="sticky top-0 z-50 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md px-6 py-3">
<div class="max-w-7xl mx-auto flex items-center justify-between gap-8">
<div class="flex items-center gap-4">
<div class="bg-primary p-1.5 rounded-lg flex items-center justify-center">
<span class="material-symbols-outlined text-white">grid_view</span>
</div>
<h2 class="text-slate-900 dark:text-white text-lg font-bold leading-tight tracking-tight">Supermarket Planogram</h2>
</div>
<div class="flex-1 max-w-xl">
<div class="relative group">
<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
<span class="material-symbols-outlined">search</span>
</div>
<input class="block w-full bg-slate-100 dark:bg-slate-800/50 border-transparent focus:border-primary focus:ring-0 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-500" placeholder="Search by SKU, ERP code or product name..." type="text"/>
</div>
</div>
<nav class="flex items-center gap-6">
<a class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary transition-colors" href="#">Dashboard</a>
<a class="text-sm font-medium text-primary" href="#">Products</a>
<a class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary transition-colors" href="#">Stores</a>
<div class="h-6 w-px bg-slate-200 dark:bg-slate-800"></div>
<button class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors relative">
<span class="material-symbols-outlined">notifications</span>
<span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white dark:border-background-dark"></span>
</button>
<div class="size-9 rounded-full bg-gradient-to-tr from-primary to-blue-400 border-2 border-slate-200 dark:border-slate-700" data-alt="User profile avatar with blue gradient"></div>
</nav>
</div>
</header>
<main class="max-w-7xl mx-auto px-6 py-8">
<!-- Header Section -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
<div>
<h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Product Management</h1>
<p class="text-slate-500 dark:text-slate-400 mt-1">Manage dimensions and ERP synchronization for active planograms</p>
</div>
<div class="flex gap-3">
<button class="flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg font-semibold text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
<span class="material-symbols-outlined">filter_list</span>
                    Filtros
                </button>
<button class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-semibold text-sm hover:opacity-90 transition-opacity">
<span class="material-symbols-outlined">add</span>
                    Novo Produto
                </button>
</div>
</div>
<!-- Filter Chips -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
<button class="px-4 py-1.5 rounded-full bg-primary/10 text-primary border border-primary/20 text-xs font-bold uppercase tracking-wider">Todos</button>
<button class="px-4 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-transparent text-xs font-bold uppercase tracking-wider hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Com Dimensões</button>
<button class="px-4 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-transparent text-xs font-bold uppercase tracking-wider hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Sem Dimensões</button>
<button class="px-4 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-transparent text-xs font-bold uppercase tracking-wider hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Estoque Baixo</button>
</div>
<!-- Product Cards Stack -->
<div class="space-y-4">
<!-- Card 1 -->
<div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-xl p-4 flex flex-col lg:flex-row items-start lg:items-center gap-6 hover:border-primary/50 transition-all shadow-sm">
<!-- Image Section -->
<div class="flex-shrink-0 relative">
<div class="w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden" data-alt="Product placeholder showing soft drinks">
<span class="material-symbols-outlined text-slate-400 text-4xl">inventory_2</span>
</div>
<div class="absolute -top-2 -right-2 bg-green-500 w-4 h-4 rounded-full border-4 border-white dark:border-slate-900 shadow-sm"></div>
</div>
<!-- Status Toggle -->
<div class="flex flex-col items-center gap-1.5 px-4 border-r border-slate-200 dark:border-slate-800 hidden lg:flex">
<span class="text-[10px] font-bold text-slate-500 uppercase">Status</span>
<label class="relative inline-flex items-center cursor-pointer">
<input checked="" class="sr-only peer" type="checkbox"/>
<div class="w-11 h-6 bg-slate-200 peer-focus:outline-none dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
</label>
</div>
<!-- Info Grid -->
<div class="flex-grow grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-x-6 gap-y-4">
<div class="col-span-2">
<h3 class="text-base font-bold text-slate-900 dark:text-white">Coca-Cola Lata 350ml - Original</h3>
<div class="flex gap-2 mt-1">
<span class="text-xs font-medium px-2 py-0.5 bg-green-500/10 text-green-600 dark:text-green-400 rounded-md flex items-center gap-1">
<span class="material-symbols-outlined !text-xs">check_circle</span>
                                Com dimensão
                            </span>
<span class="text-xs text-slate-400 font-mono">ERP: 1002934</span>
</div>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">SKU</span>
<span class="text-sm font-medium dark:text-slate-200">COKE-350-LAT</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Stock</span>
<span class="text-sm font-bold text-primary">1,240 un</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dimensões (cm)</span>
<span class="text-sm font-medium dark:text-slate-200">12.5 x 6.5 x 6.5</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Peso</span>
<span class="text-sm font-medium dark:text-slate-200">350g</span>
</div>
</div>
<!-- Action Buttons -->
<div class="flex gap-2 mt-4 lg:mt-0 w-full lg:w-auto">
<button class="flex-1 lg:flex-none p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-primary hover:text-white hover:border-primary transition-all flex items-center justify-center gap-2 lg:gap-0" title="Editar">
<span class="material-symbols-outlined">edit</span>
<span class="lg:hidden text-sm font-semibold">Editar</span>
</button>
<button class="flex-1 lg:flex-none p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center justify-center gap-2 lg:gap-0" title="Copiar Dados">
<span class="material-symbols-outlined">content_copy</span>
<span class="lg:hidden text-sm font-semibold">Copiar</span>
</button>
<button class="flex-1 lg:flex-none p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-red-500/10 hover:text-red-500 hover:border-red-500/30 transition-all flex items-center justify-center gap-2 lg:gap-0" title="Excluir">
<span class="material-symbols-outlined">delete</span>
<span class="lg:hidden text-sm font-semibold">Excluir</span>
</button>
</div>
</div>
<!-- Card 2 (Missing Dimensions) -->
<div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-xl p-4 flex flex-col lg:flex-row items-start lg:items-center gap-6 hover:border-primary/50 transition-all shadow-sm">
<!-- Image Section -->
<div class="flex-shrink-0 relative">
<div class="w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden" data-alt="Product placeholder for cleaning supplies">
<span class="material-symbols-outlined text-slate-400 text-4xl">cleaning_services</span>
</div>
<div class="absolute -top-2 -right-2 bg-amber-500 w-4 h-4 rounded-full border-4 border-white dark:border-slate-900 shadow-sm"></div>
</div>
<!-- Status Toggle -->
<div class="flex flex-col items-center gap-1.5 px-4 border-r border-slate-200 dark:border-slate-800 hidden lg:flex">
<span class="text-[10px] font-bold text-slate-500 uppercase">Status</span>
<label class="relative inline-flex items-center cursor-pointer">
<input class="sr-only peer" type="checkbox"/>
<div class="w-11 h-6 bg-slate-200 peer-focus:outline-none dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
</label>
</div>
<!-- Info Grid -->
<div class="flex-grow grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-x-6 gap-y-4">
<div class="col-span-2">
<h3 class="text-base font-bold text-slate-900 dark:text-white">Detergente Líquido Limpol Neutro 500ml</h3>
<div class="flex gap-2 mt-1">
<span class="text-xs font-medium px-2 py-0.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-md flex items-center gap-1">
<span class="material-symbols-outlined !text-xs">warning</span>
                                Sem dimensão
                            </span>
<span class="text-xs text-slate-400 font-mono">ERP: 2005881</span>
</div>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">SKU</span>
<span class="text-sm font-medium dark:text-slate-200">LIM-NEU-500</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Stock</span>
<span class="text-sm font-bold text-slate-700 dark:text-slate-300">450 un</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dimensões (cm)</span>
<span class="text-sm font-medium text-slate-400">---</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Peso</span>
<span class="text-sm font-medium text-slate-400">---</span>
</div>
</div>
<!-- Action Buttons -->
<div class="flex gap-2 mt-4 lg:mt-0 w-full lg:w-auto">
<button class="flex-1 lg:flex-none p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-primary hover:text-white hover:border-primary transition-all flex items-center justify-center gap-2 lg:gap-0" title="Editar">
<span class="material-symbols-outlined">edit</span>
<span class="lg:hidden text-sm font-semibold">Editar</span>
</button>
<button class="flex-1 lg:flex-none p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center justify-center gap-2 lg:gap-0" title="Copiar Dados">
<span class="material-symbols-outlined">content_copy</span>
<span class="lg:hidden text-sm font-semibold">Copiar</span>
</button>
<button class="flex-1 lg:flex-none p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-red-500/10 hover:text-red-500 hover:border-red-500/30 transition-all flex items-center justify-center gap-2 lg:gap-0" title="Excluir">
<span class="material-symbols-outlined">delete</span>
<span class="lg:hidden text-sm font-semibold">Excluir</span>
</button>
</div>
</div>
<!-- Card 3 -->
<div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/60 rounded-xl p-4 flex flex-col lg:flex-row items-start lg:items-center gap-6 hover:border-primary/50 transition-all shadow-sm">
<!-- Image Section -->
<div class="flex-shrink-0 relative">
<div class="w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden" data-alt="Product placeholder showing cereal box">
<span class="material-symbols-outlined text-slate-400 text-4xl">breakfast_dining</span>
</div>
<div class="absolute -top-2 -right-2 bg-green-500 w-4 h-4 rounded-full border-4 border-white dark:border-slate-900 shadow-sm"></div>
</div>
<!-- Status Toggle -->
<div class="flex flex-col items-center gap-1.5 px-4 border-r border-slate-200 dark:border-slate-800 hidden lg:flex">
<span class="text-[10px] font-bold text-slate-500 uppercase">Status</span>
<label class="relative inline-flex items-center cursor-pointer">
<input checked="" class="sr-only peer" type="checkbox"/>
<div class="w-11 h-6 bg-slate-200 peer-focus:outline-none dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
</label>
</div>
<!-- Info Grid -->
<div class="flex-grow grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-x-6 gap-y-4">
<div class="col-span-2">
<h3 class="text-base font-bold text-slate-900 dark:text-white">Cereal matinal Sucrilhos Kellogg's 240g</h3>
<div class="flex gap-2 mt-1">
<span class="text-xs font-medium px-2 py-0.5 bg-green-500/10 text-green-600 dark:text-green-400 rounded-md flex items-center gap-1">
<span class="material-symbols-outlined !text-xs">check_circle</span>
                                Com dimensão
                            </span>
<span class="text-xs text-slate-400 font-mono">ERP: 3001142</span>
</div>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">SKU</span>
<span class="text-sm font-medium dark:text-slate-200">SUC-KEL-240</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Stock</span>
<span class="text-sm font-bold text-primary">82 un</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dimensões (cm)</span>
<span class="text-sm font-medium dark:text-slate-200">22.0 x 15.0 x 4.5</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Peso</span>
<span class="text-sm font-medium dark:text-slate-200">240g</span>
</div>
</div>
<!-- Action Buttons -->
<div class="flex gap-2 mt-4 lg:mt-0 w-full lg:w-auto">
<button class="flex-1 lg:flex-none p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-primary hover:text-white hover:border-primary transition-all flex items-center justify-center gap-2 lg:gap-0" title="Editar">
<span class="material-symbols-outlined">edit</span>
<span class="lg:hidden text-sm font-semibold">Editar</span>
</button>
<button class="flex-1 lg:flex-none p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center justify-center gap-2 lg:gap-0" title="Copiar Dados">
<span class="material-symbols-outlined">content_copy</span>
<span class="lg:hidden text-sm font-semibold">Copiar</span>
</button>
<button class="flex-1 lg:flex-none p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-red-500/10 hover:text-red-500 hover:border-red-500/30 transition-all flex items-center justify-center gap-2 lg:gap-0" title="Excluir">
<span class="material-symbols-outlined">delete</span>
<span class="lg:hidden text-sm font-semibold">Excluir</span>
</button>
</div>
</div>
</div>
<!-- Pagination -->
<div class="mt-8 flex items-center justify-between">
<p class="text-sm text-slate-500 dark:text-slate-400">Exibindo <span class="font-bold dark:text-white">1 - 3</span> de <span class="font-bold dark:text-white">482</span> produtos</p>
<div class="flex gap-1">
<button class="p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
<span class="material-symbols-outlined">chevron_left</span>
</button>
<button class="px-3.5 py-1 text-sm font-bold bg-primary text-white rounded-lg">1</button>
<button class="px-3.5 py-1 text-sm font-bold hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">2</button>
<button class="px-3.5 py-1 text-sm font-bold hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">3</button>
<button class="p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
<span class="material-symbols-outlined">chevron_right</span>
</button>
</div>
</div>
</main>
<!-- Footer Meta -->
<footer class="max-w-7xl mx-auto px-6 py-10 border-t border-slate-200 dark:border-slate-800 mt-12 flex justify-between items-center text-xs text-slate-500">
<div>
<span class="font-bold uppercase tracking-widest text-primary/80">Planogram Admin</span>
<span class="ml-4">Versão 4.2.0-stable</span>
</div>
<div class="flex gap-6">
<a class="hover:text-primary transition-colors" href="#">Privacy Policy</a>
<a class="hover:text-primary transition-colors" href="#">Support Center</a>
<a class="hover:text-primary transition-colors" href="#">Documentation</a>
</div>
</footer>
</body></html>