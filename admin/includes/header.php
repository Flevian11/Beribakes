<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeriBakes · Bakery Management System</title>

    <!-- Favicon (croissant) -->
    <link rel="icon" type="image/x-icon" href="https://cdn.jsdelivr.net/npm/emoji-datasource-apple/img/apple/64/1f950.png">

    <!-- Font Awesome -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">

    <!-- Tailwind CSS with Custom Bakery Theme -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#b45309', // Rich brown - crust
                            600: '#92400e',
                            700: '#78350f',
                            800: '#451a03',
                            900: '#2c0e02',
                        },
                        secondary: {
                            50: '#fdf8f2',
                            100: '#fcf1e6',
                            200: '#f9e3cd',
                            300: '#f5d5b4',
                            400: '#f2c79b',
                            500: '#e6b17e', // Light caramel - pastry
                            600: '#c49462',
                            700: '#a1774a',
                            800: '#7f5b37',
                            900: '#5c3f26',
                        },
                        accent: {
                            50: '#fff9f0',
                            100: '#fff3e1',
                            200: '#ffe7c3',
                            300: '#ffdba5',
                            400: '#ffcf87',
                            500: '#f5b56c', // Honey glaze
                            600: '#d49154',
                            700: '#b37040',
                            800: '#92502c',
                            900: '#713218',
                        },
                        bakery: {
                            crust: '#b45309',
                            crumb: '#fef3c7',
                            butter: '#fde68a',
                            cream: '#fff7ed',
                            chocolate: '#422006',
                            honey: '#fbbf24',
                            sugar: '#f5f5f4',
                        },
                        slate: {
                            850: '#1e293b',
                            950: '#0f172a',
                        }
                    },
                    fontFamily: {
                        'inter': ['Inter', 'system-ui', 'sans-serif'],
                        'system': ['-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif']
                    },
                    fontSize: {
                        '2xs': '0.625rem',
                        '3xs': '0.5rem',
                    },
                    spacing: {
                        '18': '4.5rem',
                        '88': '22rem',
                        '128': '32rem',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'slide-in': 'slideIn 0.3s ease-out',
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'bounce-slow': 'bounce 2s infinite',
                        'ripple': 'ripple 1.5s linear infinite',
                        'gradient': 'gradient 3s ease infinite',
                        'shimmer': 'shimmer 2s infinite',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'dough-float': 'doughFloat 8s ease-in-out infinite',
                        'pulse-warm': 'pulseWarm 2s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        doughFloat: {
                            '0%, 100%': { transform: 'translateY(0) rotate(0deg)' },
                            '33%': { transform: 'translateY(-15px) rotate(3deg)' },
                            '66%': { transform: 'translateY(-8px) rotate(-3deg)' },
                        },
                        pulseWarm: {
                            '0%, 100%': { opacity: 1 },
                            '50%': { opacity: 0.7 },
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        ripple: {
                            '0%': { transform: 'scale(0.8)', opacity: '1' },
                            '100%': { transform: 'scale(2.4)', opacity: '0' },
                        },
                        gradient: {
                            '0%, 100%': { backgroundPosition: '0% 50%' },
                            '50%': { backgroundPosition: '100% 50%' },
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-1000px 0' },
                            '100%': { backgroundPosition: '1000px 0' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(100%)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                        'gradient-conic': 'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
                        'grid-pattern': 'linear-gradient(to right, #e5e7eb 1px, transparent 1px), linear-gradient(to bottom, #e5e7eb 1px, transparent 1px)',
                        'bakery-pattern': 'url("data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23b45309\' fill-opacity=\'0.03\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")',
                    },
                    backdropBlur: {
                        'xs': '2px',
                    }
                }
            },
            plugins: [
                function({ addUtilities }) {
                    addUtilities({
                        '.text-tiny': {
                            'font-size': '0.625rem',
                            'line-height': '1rem',
                        },
                        '.text-micro': {
                            'font-size': '0.5rem',
                            'line-height': '0.75rem',
                        },
                        '.glass': {
                            'background': 'rgba(255, 255, 255, 0.7)',
                            'backdrop-filter': 'blur(10px)',
                            '-webkit-backdrop-filter': 'blur(10px)',
                        },
                        '.glass-dark': {
                            'background': 'rgba(15, 23, 42, 0.7)',
                            'backdrop-filter': 'blur(10px)',
                            '-webkit-backdrop-filter': 'blur(10px)',
                        },
                        '.hide-scrollbar': {
                            '-ms-overflow-style': 'none',
                            'scrollbar-width': 'none',
                        },
                        '.hide-scrollbar::-webkit-scrollbar': {
                            'display': 'none',
                        },
                        '.gradient-border': {
                            'border': 'double 2px transparent',
                            'background-image': 'linear-gradient(white, white), linear-gradient(135deg, #b45309, #e6b17e)',
                            'background-origin': 'border-box',
                            'background-clip': 'padding-box, border-box',
                        },
                        '.gradient-text': {
                            'background': 'linear-gradient(135deg, #b45309 0%, #e6b17e 100%)',
                            '-webkit-background-clip': 'text',
                            '-webkit-text-fill-color': 'transparent',
                            'background-clip': 'text',
                        },
                        '.card-hover': {
                            'transition': 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                            'transform': 'translateY(0)',
                        },
                        '.card-hover:hover': {
                            'transform': 'translateY(-4px)',
                            'box-shadow': '0 20px 40px rgba(180, 83, 9, 0.1)',
                        },
                        '.animate-gradient': {
                            'background-size': '200% 200%',
                            'animation': 'gradient 3s ease infinite',
                        },
                        '.bakery-icon': {
                            'background': 'radial-gradient(circle at 30% 30%, #b45309, #92400e)',
                            'border-radius': '40% 60% 60% 40% / 70% 50% 50% 30%',
                        }
                    })
                }
            ]
        }
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- FullCalendar -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>

    <!-- Luxon for Date Handling -->
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.2.1/build/global/luxon.min.js"></script>

   <?php include 'styles.php'; ?>

</head>

<body class="font-inter bg-bakery-cream text-primary-800 dark:bg-primary-900 dark:text-bakery-cream transition-colors duration-200 section-bg">
    <!-- Loading Screen -->
    <div id="loadingScreen"
        class="fixed inset-0 z-[9999] bg-bakery-cream dark:bg-primary-900 flex items-center justify-center transition-opacity duration-500">
        <div class="text-center">
            <div class="relative w-24 h-24 mx-auto mb-6">
                <div class="absolute inset-0 bakery-icon animate-dough-float"></div>
                <div class="absolute inset-4 bakery-icon animate-dough-float" style="animation-delay: -2s;"></div>
                <div class="absolute inset-8 bakery-icon animate-dough-float" style="animation-delay: -4s;"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <i class="fas fa-bread-slice text-3xl text-primary-500 dark:text-bakery-cream animate-pulse"></i>
                </div>
            </div>
            <div class="text-lg font-semibold text-primary-700 dark:text-bakery-cream mb-2">BeriBakes Bakery Management</div>
            <div class="text-sm text-primary-600/70 dark:text-bakery-cream/70 font-medium">Loading your dashboard...</div>
            <div class="mt-4 loading-dots">
                <span class="bg-primary-600 dark:bg-bakery-cream"></span>
                <span class="bg-primary-600 dark:bg-bakery-cream"></span>
                <span class="bg-primary-600 dark:bg-bakery-cream"></span>
            </div>
        </div>
    </div>

    <!-- App Container -->
    <div class="flex min-h-screen relative">