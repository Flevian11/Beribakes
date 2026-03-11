 <!-- Custom Styles -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #b45309 0%, #e6b17e 100%);
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 20px 40px rgba(180, 83, 9, 0.04);
            --hover-shadow: 0 10px 30px rgba(180, 83, 9, 0.08);
        }

        body {
            font-feature-settings: "ss01", "ss02", "cv01", "cv02";
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            letter-spacing: -0.011em;
            background-color: #fff7ed;
        }

        .dark body {
            background-color: #2c0e02;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(180, 83, 9, 0.05);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #b45309, #e6b17e);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #92400e, #c49462);
        }

        /* Selection */
        ::selection {
            background: rgba(180, 83, 9, 0.2);
            color: #1e293b;
        }

        /* Focus States */
        .focus-ring {
            @apply focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white;
        }

        .dark .focus-ring {
            @apply focus:ring-offset-slate-900;
        }

        /* Loading Animation */
        .loading-dots {
            display: inline-flex;
            align-items: center;
        }

        .loading-dots span {
            animation: loading 1.4s infinite both;
            background-color: currentColor;
            border-radius: 50%;
            display: inline-block;
            height: 4px;
            margin: 0 1px;
            width: 4px;
        }

        .loading-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes loading {

            0%,
            80%,
            100% {
                transform: scale(0);
            }

            40% {
                transform: scale(1);
            }
        }

        /* Ripple Effect */
        .ripple-container {
            position: relative;
            overflow: hidden;
        }

        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            position: absolute;
            z-index: 100;
            background: rgba(15, 23, 42, 0.95);
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Bakery Card Styles */
        .bakery-card {
            background: linear-gradient(135deg, rgba(180, 83, 9, 0.03), rgba(230, 177, 126, 0.03));
            border: 1px solid rgba(180, 83, 9, 0.1);
        }

        .dark .bakery-card {
            background: linear-gradient(135deg, rgba(180, 83, 9, 0.1), rgba(230, 177, 126, 0.1));
            border: 1px solid rgba(180, 83, 9, 0.2);
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            background: linear-gradient(135deg, #b45309, #f5b56c);
            color: white;
            font-size: 0.5rem;
            font-weight: 700;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .dark .notification-badge {
            border-color: #0f172a;
        }

        /* QR Code Display */
        .qr-code-container {
            padding: 20px;
            background: white;
            border-radius: 12px;
            display: inline-block;
        }

        .dark .qr-code-container {
            background: #1e293b;
        }

        /* Calendar Custom */
        .fc-event {
            border: none !important;
            border-radius: 6px !important;
            padding: 2px 6px !important;
            margin: 1px 0 !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        .fc-event:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }

        /* Chat Message Animation */
        .chat-message {
            animation: slideUp 0.3s ease-out;
        }

        /* Animation for Bakery Processing */
        @keyframes processing {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .processing-animation {
            position: relative;
            overflow: hidden;
        }

        .processing-animation::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(180, 83, 9, 0.2), transparent);
            animation: processing 2s infinite;
        }

        /* Dark Mode Fixes */
        .dark .bg-white {
            background-color: #0f172a !important;
        }

        .dark .text-slate-800 {
            color: #f1f5f9 !important;
        }

        .dark .text-slate-600 {
            color: #cbd5e1 !important;
        }

        .dark .border-slate-200 {
            border-color: #334155 !important;
        }

        /* Section Backgrounds */
        .section-bg {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23b45309' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>