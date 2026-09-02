<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-synoria-green border border-transparent rounded-md font-semibold text-sm text-white hover:bg-synoria-green-dark focus:bg-synoria-green-dark active:bg-synoria-green-deeper focus:outline-none focus:ring-2 focus:ring-synoria-yellow focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
