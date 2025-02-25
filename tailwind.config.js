import defaultTheme from 'tailwindcss/defaultTheme';
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        // You will probably also need these lines
        "./resources/**/**/*.blade.php",
        "./resources/**/**/*.js",
        "./app/View/Components/**/**/*.php",
        "./app/Livewire/**/**/*.php",

        // Add mary
        "./vendor/robsontenorio/mary/src/View/Components/**/*.php",
        // pagination
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    // Add daisyUI
    plugins: [
        require("daisyui")
    ],
    daisyui: {
        themes: [
        {
            mytheme: {
                ...require("daisyui/src/theming/themes")["business"],                "--rounded-box": "1rem", // border radius rounded-box utility class, used in card and other large boxes
                "--rounded-btn": "0.5rem", // border radius rounded-btn utility class, used in buttons and similar element
                "--rounded-badge": "1.9rem", // border radius rounded-badge utility class, used in badges and similar
                "--animation-btn": "0.25s", // duration of animation when you click on button
                "--animation-input": "0.2s", // duration of animation for inputs like checkbox, toggle, radio, etc
                "--btn-focus-scale": "0.95", // scale transform of button when you focus on it
                "--border-btn": "1px", // border width of buttons
                "--tab-border": "1px", // border width of tabs
                "--tab-radius": "0.5rem", // border radius of tabs
            },
        },
            "business"],
    },

}
