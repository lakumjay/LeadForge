<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeadForge AI — Freelance Client Acquisition & ₹50k Goal Accelerator</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            900: '#14532d'
                        },
                        dark: {
                            800: '#1e293b',
                            850: '#172033',
                            900: '#0f172a',
                            950: '#090d16'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.02); }
        }
        .pulse-urgent {
            animation: pulse-slow 2s infinite ease-in-out;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>
</head>
<body class="bg-dark-950 text-slate-100 font-sans min-h-screen flex flex-col antialiased selection:bg-brand-500 selection:text-black">

    <!-- Top Navigation Bar -->
    <header class="bg-dark-900 border-b border-slate-800 sticky top-0 z-50 backdrop-blur-md bg-opacity-90">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand Logo -->
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center shadow-lg shadow-brand-500/20">
                    <i data-lucide="zap" class="w-6 h-6 text-black font-bold"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-lg tracking-tight text-white">LeadForge <span class="text-brand-500">AI</span></span>
                        <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full font-mono border border-emerald-500/30">1-Day Work Engine</span>
                    </div>
                    <p class="text-xs text-slate-400">Target: ₹50,000/mo Freelance Growth System</p>
                </div>
            </div>

            <!-- Live Status, Auto-Pilot Toggle & Sound Toggle -->
            <div class="flex items-center space-x-3">
                <!-- Auto-Pilot Toggle in Header -->
                <button id="btn-autopilot-header" onclick="toggleAutopilot()" class="px-3 py-1.5 rounded-lg border text-xs font-semibold flex items-center space-x-1.5 transition bg-slate-800 text-slate-300 border-slate-700 hover:border-emerald-500">
                    <span class="w-2 h-2 rounded-full bg-slate-500" id="autopilot-dot"></span>
                    <span id="autopilot-header-label">🤖 Auto-Pilot: OFF</span>
                </button>

                <div class="hidden md:flex items-center space-x-2 text-xs bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span class="text-slate-300">Radar: <span id="radar-last-sync" class="text-emerald-400 font-mono">Live</span></span>
                </div>

                <button id="btn-sound-toggle" onclick="toggleSoundAlert()" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg border border-slate-700 transition flex items-center space-x-1.5 text-xs">
                    <i data-lucide="volume-2" id="sound-icon" class="w-4 h-4 text-emerald-400"></i>
                    <span class="hidden sm:inline">Sound</span>
                </button>

                <button onclick="switchTab('crm')" class="flex items-center space-x-2 bg-gradient-to-r from-emerald-600 to-brand-600 hover:from-emerald-500 hover:to-brand-500 text-white font-medium text-xs px-3.5 py-2 rounded-lg shadow-md shadow-brand-600/20 transition">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                    <span>Goal: <span id="nav-goal-progress" class="font-bold text-white">0%</span></span>
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-1 overflow-x-auto border-t border-slate-800/60 scrollbar-none py-1">
            <button onclick="switchTab('autopilot')" id="tab-btn-autopilot" class="nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 transition">
                <i data-lucide="bot" class="w-4 h-4"></i>
                <span class="font-bold">🤖 Hands-Free Auto-Pilot</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse ml-1"></span>
            </button>

            <button onclick="switchTab('radar')" id="tab-btn-radar" class="nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition">
                <i data-lucide="radar" class="w-4 h-4"></i>
                <span>⚡ Live Radar</span>
                <span id="tab-radar-count" class="bg-emerald-500 text-black font-bold text-[10px] px-1.5 py-0.2 rounded-full ml-1">0</span>
            </button>

            <button onclick="switchTab('mass')" id="tab-btn-mass" class="nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition">
                <i data-lucide="layers" class="w-4 h-4 text-emerald-400"></i>
                <span>🚀 Mass Scale Multiplier</span>
                <span class="text-[10px] bg-red-500/20 text-red-400 border border-red-500/30 px-1.5 py-0.2 rounded-full font-bold">1,000x</span>
            </button>

            <button onclick="switchTab('auditor')" id="tab-btn-auditor" class="nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition">
                <i data-lucide="bug" class="w-4 h-4"></i>
                <span>🔍 Bug Hunter & Agencies</span>
            </button>

            <button onclick="switchTab('upwork')" id="tab-btn-upwork" class="nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition">
                <i data-lucide="file-code" class="w-4 h-4"></i>
                <span>✍️ Upwork Proposal Studio</span>
            </button>

            <button onclick="switchTab('outreach')" id="tab-btn-outreach" class="nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition">
                <i data-lucide="send" class="w-4 h-4"></i>
                <span>📩 Cold Outreach Studio</span>
            </button>

            <button onclick="switchTab('closer')" id="tab-btn-closer" class="nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition">
                <i data-lucide="handshake" class="w-4 h-4"></i>
                <span>🤝 AI Deal Closer</span>
                <span class="text-[10px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-1.5 py-0.2 rounded-full font-bold">Auto</span>
            </button>

            <button onclick="switchTab('crm')" id="tab-btn-crm" class="nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                <span>📊 ₹50k Goal CRM</span>
            </button>

            <button onclick="switchTab('safety')" id="tab-btn-safety" class="nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>🛡️ Anti-Ban & Settings</span>
            </button>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- ==========================================
             TAB 0: 100% HANDS-FREE AUTONOMOUS AUTO-PILOT
        ========================================== -->
        <div id="view-autopilot" class="space-y-6">
            <!-- Hero Auto-Pilot Status Card -->
            <div class="bg-gradient-to-r from-dark-900 via-dark-850 to-dark-900 border border-emerald-500/40 rounded-2xl p-6 shadow-2xl relative overflow-hidden">
                <div class="absolute right-0 top-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <span id="ap-badge-status" class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700 flex items-center space-x-1.5">
                                <span class="w-2 h-2 rounded-full bg-slate-500" id="ap-indicator-dot"></span>
                                <span id="ap-text-status">AUTO-PILOT PAUSED</span>
                            </span>
                            <span class="text-xs bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono">100% Zero-Effort Mode</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black text-white">Autonomous Client Acquisition Engine</h2>
                        <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                            Turn on Auto-Pilot and relax. The bot will automatically discover US/UK tech agencies, audit their websites for bugs/latency, generate custom value-first proposals, and add deals to your CRM while you wait for client replies.
                        </p>
                    </div>

                    <!-- Big Toggle Switch -->
                    <div class="w-full lg:w-auto flex flex-col sm:flex-row gap-3">
                        <button id="btn-main-autopilot" onclick="toggleAutopilot()" class="w-full sm:w-auto px-8 py-4 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-brand-600 hover:from-emerald-500 hover:to-brand-500 shadow-xl shadow-brand-600/30 flex items-center justify-center space-x-2.5 transition transform hover:scale-[1.02]">
                            <i data-lucide="play" id="btn-ap-icon" class="w-5 h-5"></i>
                            <span id="btn-ap-label">START AUTO-PILOT</span>
                        </button>
                    </div>
                </div>

                <!-- Live Metrics Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-800">
                    <div class="bg-dark-950/80 border border-slate-800 p-4 rounded-xl">
                        <span class="text-xs text-slate-400 block mb-1">Pitched Automatically</span>
                        <span id="ap-stat-total" class="text-2xl font-bold text-emerald-400 font-mono">0</span>
                        <span class="text-[11px] text-slate-500 block">Deals Dispatched</span>
                    </div>

                    <div class="bg-dark-950/80 border border-slate-800 p-4 rounded-xl">
                        <span class="text-xs text-slate-400 block mb-1">Human Delay Cooldown</span>
                        <span id="ap-stat-cooldown" class="text-2xl font-bold text-sky-400 font-mono">Safe</span>
                        <span class="text-[11px] text-slate-500 block">Anti-Ban Protection</span>
                    </div>

                    <div class="bg-dark-950/80 border border-slate-800 p-4 rounded-xl">
                        <span class="text-xs text-slate-400 block mb-1">Target Geographies</span>
                        <span class="text-sm font-bold text-white block truncate">🇺🇸 US &amp; 🇬🇧 UK Agencies</span>
                        <span class="text-[11px] text-slate-500 block">High $ Payers</span>
                    </div>

                    <div class="bg-dark-950/80 border border-slate-800 p-4 rounded-xl">
                        <span class="text-xs text-slate-400 block mb-1">Client Reply Alert</span>
                        <span class="text-sm font-bold text-emerald-400 flex items-center space-x-1">
                            <i data-lucide="volume-2" class="w-4 h-4"></i>
                            <span>macOS Chime ON</span>
                        </span>
                        <span class="text-[11px] text-slate-500 block">Desktop Notification</span>
                    </div>
                </div>
            </div>

            <!-- Live Autonomous Activity Console -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                        <i data-lucide="terminal" class="w-4 h-4 text-emerald-400"></i>
                        <span>Live Auto-Pilot Execution Logs</span>
                    </h3>
                    <span class="text-xs text-slate-500 font-mono">Live Activity Stream</span>
                </div>

                <div id="autopilot-terminal-logs" class="bg-dark-950 border border-slate-800 rounded-xl p-4 font-mono text-xs text-emerald-400/90 h-48 overflow-y-auto space-y-1.5 scrollbar-thin">
                    <div class="text-slate-500">[System] Auto-Pilot engine ready. Click "START AUTO-PILOT" to begin hands-free client acquisition...</div>
                </div>
            </div>

            <!-- Outbox & 100% Sent Applications Proof History -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center space-x-2">
                            <i data-lucide="check-check" class="w-5 h-5 text-emerald-400"></i>
                            <span>100% Sent Applications &amp; Outbox History</span>
                        </h3>
                        <p class="text-xs text-slate-400">Verify every single proposal and cold email sent automatically with exact timestamp, client link, and message copy.</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button onclick="exportSentLeadsCsv()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs px-3.5 py-2 rounded-xl border border-slate-700 flex items-center space-x-1.5 transition">
                            <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>Download CSV Proof</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-800/50 text-slate-400 border-b border-slate-800 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Company / Target</th>
                                <th class="py-3 px-4">Platform &amp; Service</th>
                                <th class="py-3 px-4">Value ($ / ₹)</th>
                                <th class="py-3 px-4">Dispatched At</th>
                                <th class="py-3 px-4">Status Proof</th>
                                <th class="py-3 px-4 text-right">View Message</th>
                            </tr>
                        </thead>
                        <tbody id="autopilot-outbox-tbody" class="divide-y divide-slate-800/50">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 1: LIVE JOB RADAR (Sub-Minute Feed)
        ========================================== -->
        <div id="view-radar" class="hidden space-y-6">
            <!-- Top Controls & Urgency Ticker -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center space-x-3 w-full md:w-auto">
                    <div class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-white flex items-center space-x-2">
                            <span>Live Internet Bounties & Job Radar</span>
                            <span class="text-xs bg-red-500/20 text-red-400 border border-red-500/30 px-2 py-0.5 rounded-md font-normal">Sub-60s Action Mode</span>
                        </h2>
                        <p class="text-xs text-slate-400">Scans Reddit, RemoteOK, HackerNews, Upwork & Twitter feeds for urgent Laravel & PHP work.</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2 w-full md:w-auto justify-end flex-wrap gap-2">
                    <!-- Multi-Service Filters -->
                    <select id="radar-filter" onchange="loadRadarJobs()" class="bg-slate-800 border border-slate-700 text-slate-200 text-xs rounded-lg px-3 py-2 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                        <option value="all">🔥 All Services (Web, SEO, Google Ads)</option>
                        <option value="laravel">🐘 Laravel &amp; PHP Dev</option>
                        <option value="seo">📈 Technical SEO &amp; Rankings</option>
                        <option value="ads">🎯 Google Ads &amp; GA4 Tracking</option>
                        <option value="bugfix">🐛 Quick Bug Fixes</option>
                        <option value="urgent">🚨 Urgent Bounties (&lt; 10 mins)</option>
                        <option value="reddit">👾 Reddit Direct (No Login)</option>
                    </select>

                    <button onclick="loadRadarJobs(true)" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs px-3 py-2 rounded-lg border border-slate-700 flex items-center space-x-1.5 transition">
                        <i data-lucide="refresh-cw" id="radar-refresh-icon" class="w-3.5 h-3.5"></i>
                        <span>Scan Now</span>
                    </button>
                </div>
            </div>

            <!-- Job Stream Grid -->
            <div id="radar-jobs-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Skeleton Loader -->
                <div class="bg-dark-900 border border-slate-800 rounded-2xl p-5 animate-pulse space-y-4">
                    <div class="h-4 bg-slate-800 rounded w-1/3"></div>
                    <div class="h-6 bg-slate-800 rounded w-4/5"></div>
                    <div class="h-16 bg-slate-800 rounded w-full"></div>
                    <div class="h-8 bg-slate-800 rounded w-full"></div>
                </div>
                <div class="bg-dark-900 border border-slate-800 rounded-2xl p-5 animate-pulse space-y-4">
                    <div class="h-4 bg-slate-800 rounded w-1/3"></div>
                    <div class="h-6 bg-slate-800 rounded w-4/5"></div>
                    <div class="h-16 bg-slate-800 rounded w-full"></div>
                    <div class="h-8 bg-slate-800 rounded w-full"></div>
                </div>
                <div class="bg-dark-900 border border-slate-800 rounded-2xl p-5 animate-pulse space-y-4">
                    <div class="h-4 bg-slate-800 rounded w-1/3"></div>
                    <div class="h-6 bg-slate-800 rounded w-4/5"></div>
                    <div class="h-16 bg-slate-800 rounded w-full"></div>
                    <div class="h-8 bg-slate-800 rounded w-full"></div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 1.5: MASS LEAD MULTIPLIER (100x SCALE)
        ========================================== -->
        <div id="view-mass" class="hidden space-y-6">
            <!-- Hero Banner -->
            <div class="bg-gradient-to-r from-dark-900 via-dark-850 to-dark-900 border border-emerald-500/40 rounded-2xl p-6 shadow-2xl relative overflow-hidden">
                <div class="absolute right-0 top-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/30 flex items-center space-x-1.5 font-mono">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                <span>100x High-Velocity Scale</span>
                            </span>
                            <span class="text-xs bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono">Multi-Channel Goldmine</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black text-white">Mass Lead Multiplier &amp; Multi-Channel Engine</h2>
                        <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                            Beyond standard cold email, tap into massive untapped channels: Google Maps businesses running with <strong class="text-white">NO website</strong> ($250 1-day build), Shopify stores with <strong class="text-white">broken Meta/GA4 tracking</strong> ($150 fix), and digital agencies needing <strong class="text-white">overflow dev capacity</strong>.
                        </p>
                    </div>

                    <!-- Pipeline Metrics -->
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <div class="bg-dark-950 border border-slate-800 p-4 rounded-xl text-center min-w-[140px]">
                            <span class="text-[11px] text-slate-400 block mb-0.5">Potential Pipeline</span>
                            <span id="mass-stat-potential-usd" class="text-2xl font-bold text-emerald-400 font-mono">$12,500</span>
                            <span id="mass-stat-potential-inr" class="text-[10px] text-slate-500 block font-mono">₹10,81,250</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & Bulk Generation Bar -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Target Strategy Channel</label>
                        <select id="mass-filter-category" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                            <option value="all">🔥 All High-Ticket Streams (Combined)</option>
                            <option value="no_website">🔴 Google Maps Businesses (No Website - $250 Pitch)</option>
                            <option value="ecommerce">🟠 Shopify &amp; E-Com (Missing Meta/GA4 - $150 Pitch)</option>
                            <option value="agency">🟡 US &amp; UK Agencies (Backend Overflow - $300 Pitch)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Target Country</label>
                        <select id="mass-filter-country" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                            <option value="United States">🇺🇸 United States</option>
                            <option value="United Kingdom">🇬🇧 United Kingdom</option>
                            <option value="Australia">🇦🇺 Australia</option>
                            <option value="Canada">🇨🇦 Canada</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Batch Volume</label>
                        <select id="mass-filter-limit" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none font-mono">
                            <option value="50">50 High-Intent Targets</option>
                            <option value="100">100 High-Intent Targets</option>
                            <option value="250">250 High-Intent Targets</option>
                            <option value="500">500 High-Intent Targets</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button onclick="generateMassLeads()" id="btn-gen-mass" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-2.5 rounded-xl shadow-lg shadow-emerald-600/20 flex items-center justify-center space-x-1.5 transition">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            <span>Generate Mass Leads</span>
                        </button>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800/80 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                    <div class="flex items-center space-x-2">
                        <span id="mass-results-counter" class="text-xs text-slate-400 font-mono">Showing 0 high-ticket opportunities</span>
                        <span id="mass-autoloop-badge" class="hidden text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full animate-pulse">🔄 Infinite Loop: Active</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Infinite Auto-Loop Toggle -->
                        <button onclick="toggleMassAutoLoop()" id="btn-mass-autoloop" class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white font-extrabold text-xs px-4 py-2 rounded-xl shadow-lg shadow-indigo-500/20 flex items-center space-x-1.5 transition transform hover:scale-[1.02]">
                            <i data-lucide="repeat" class="w-4 h-4"></i>
                            <span id="mass-autoloop-label">🔄 Turn ON Infinite Auto-Loop</span>
                        </button>
                        <!-- Auto-Dispatch All Button -->
                        <button onclick="autoDispatchAllMassLeads()" id="btn-mass-auto-dispatch" class="bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 hover:from-amber-400 hover:to-rose-400 text-black font-extrabold text-xs px-4 py-2 rounded-xl shadow-lg shadow-orange-500/20 flex items-center space-x-2 transition transform hover:scale-[1.02]">
                            <i data-lucide="zap" class="w-4 h-4 fill-current"></i>
                            <span>⚡ 1-Click Auto-Dispatch ALL</span>
                        </button>
                        <button onclick="addAllMassLeadsToCrm()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs px-3.5 py-2 rounded-xl border border-slate-700 flex items-center space-x-1.5 transition">
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>Add All to CRM</span>
                        </button>
                        <button onclick="exportMassLeadsCsv()" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-sm flex items-center space-x-1.5 transition">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            <span>Export CSV</span>
                        </button>
                    </div>
                </div>

                <!-- Live Auto-Dispatch Progress Banner -->
                <div id="mass-dispatch-progress-box" class="hidden bg-dark-950 border border-amber-500/40 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                            <span class="text-xs font-bold text-amber-300" id="mass-progress-title">🤖 Auto-Dispatching Mass Campaigns...</span>
                        </div>
                        <span id="mass-progress-counts" class="text-xs font-mono text-slate-300 font-bold">0 / 0 Processed</span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div id="mass-progress-bar" class="bg-gradient-to-r from-amber-500 to-emerald-400 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span id="mass-progress-current-target">Target: Ready</span>
                        <div class="flex items-center space-x-3 font-mono">
                            <span class="text-emerald-400">✉️ SMTP Sent: <strong id="mass-stat-smtp-sent">0</strong></span>
                            <span class="text-sky-400">📂 CRM Saved: <strong id="mass-stat-crm-saved">0</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leads Table -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-800/50 text-slate-400 border-b border-slate-800 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Business / Prospect</th>
                                <th class="py-3 px-4">Location &amp; Channel</th>
                                <th class="py-3 px-4">Identified Technical Gap</th>
                                <th class="py-3 px-4">Deal ($ / ₹)</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="mass-leads-tbody" class="divide-y divide-slate-800/50">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 2: OUTBOUND BUG HUNTER & AGENCIES
        ========================================== -->
        <div id="view-auditor" class="hidden space-y-6">
            <!-- Website Live Scanner Box -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Live Agency & Website Bug Auditor</h2>
                        <p class="text-xs text-slate-400">Scan any live business or agency website in seconds to uncover speed bottlenecks, security issues, and Laravel errors to pitch them with zero competition.</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <input type="text" id="audit-target-url" placeholder="Enter website URL (e.g. clientagency.com or https://example.com)" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-3 pl-10 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <i data-lucide="globe" class="w-4 h-4 text-slate-500 absolute left-3.5 top-3.5"></i>
                    </div>
                    <button onclick="runSiteAudit()" id="btn-run-audit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-6 py-3 rounded-xl flex items-center justify-center space-x-2 text-sm shadow-lg shadow-emerald-600/20 transition">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                        <span>Scan & Generate Pitch</span>
                    </button>
                </div>

                <!-- Audit Results Area -->
                <div id="audit-results-box" class="hidden mt-6 pt-6 border-t border-slate-800 space-y-4">
                    <!-- Populated via JS -->
                </div>
            </div>

            <!-- Curated US/UK Agencies Directory -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center space-x-2">
                            <span>US & UK Agency White-Label Opportunities</span>
                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/30">Recurring ₹50k Strategy</span>
                        </h3>
                        <p class="text-xs text-slate-400">Digital agencies with frequent backend overflows. 1-Click to audit their website and send a partnership pitch.</p>
                    </div>

                    <div class="flex items-center space-x-2 w-full sm:w-auto">
                        <select id="agency-country-filter" onchange="loadAgencies()" class="bg-slate-800 border border-slate-700 text-slate-200 text-xs rounded-lg px-3 py-2 focus:ring-1 focus:ring-emerald-500">
                            <option value="all">🌍 All Countries</option>
                            <option value="United States">🇺🇸 United States</option>
                            <option value="United Kingdom">🇬🇧 United Kingdom</option>
                            <option value="Canada">🇨🇦 Canada</option>
                            <option value="Australia">🇦🇺 Australia</option>
                        </select>
                    </div>
                </div>

                <div id="agencies-table-container" class="overflow-x-auto">
                    <!-- Agency rows populated by JS -->
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 3: UPWORK SMART PROPOSAL STUDIO
        ========================================== -->
        <div id="view-upwork" class="hidden space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left Column: Input Form -->
                <div class="lg:col-span-6 bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                            <i data-lucide="file-edit" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-white">Upwork Fast Proposal Generator</h2>
                            <p class="text-xs text-slate-400">Generates high-converting, human-level cover letters with code snippets in under 3 seconds.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Job Title</label>
                        <input type="text" id="upwork-input-title" placeholder="e.g. Urgent: Fix Laravel 11 Stripe Webhook 500 error" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Job Description / Requirements</label>
                        <textarea id="upwork-input-desc" rows="5" placeholder="Paste the job description from Upwork here..." class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl p-4 text-sm focus:border-emerald-500 focus:outline-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Client Name (if known)</label>
                            <input type="text" id="upwork-input-client" placeholder="e.g. Alex" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Budget ($)</label>
                            <input type="text" id="upwork-input-budget" placeholder="e.g. $100 Fixed" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <button onclick="generateUpworkProposal()" id="btn-gen-upwork" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl flex items-center justify-center space-x-2 text-sm shadow-lg shadow-emerald-600/20 transition">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Generate Winning Proposal & Code Fix</span>
                    </button>
                </div>

                <!-- Right Column: Generated Output -->
                <div class="lg:col-span-6 bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-200 flex items-center space-x-2">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
                                <span>Tailored Human Proposal</span>
                            </h3>
                            <span id="proposal-word-count" class="text-xs text-slate-400 bg-slate-800 px-2 py-0.5 rounded border border-slate-700 font-mono">0 words</span>
                        </div>

                        <div id="upwork-output-empty" class="text-center py-16 text-slate-500 space-y-3">
                            <i data-lucide="terminal" class="w-12 h-12 mx-auto text-slate-600"></i>
                            <p class="text-xs">Fill in job details on the left and click "Generate".<br>Your custom response with exact code snippets will appear here.</p>
                        </div>

                        <div id="upwork-output-content" class="hidden space-y-4">
                            <!-- Golden Hook -->
                            <div class="bg-emerald-500/10 border border-emerald-500/20 p-3 rounded-xl">
                                <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider block mb-1">⚡ First 2 Lines (Mobile Hook):</span>
                                <p id="upwork-out-hook" class="text-xs text-emerald-200 font-medium"></p>
                            </div>

                            <!-- Proposal Text Area -->
                            <div>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Full Human Cover Letter:</span>
                                <textarea id="upwork-out-full" rows="9" class="w-full bg-dark-950 border border-slate-700 text-slate-100 rounded-xl p-3 text-xs font-mono focus:outline-none"></textarea>
                            </div>

                            <!-- Reverse Search Hint -->
                            <div class="bg-slate-800/80 border border-slate-700 p-3 rounded-xl">
                                <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider block mb-1">🔍 Client Reverse Search Hint:</span>
                                <p id="upwork-out-hint" class="text-xs text-slate-300"></p>
                            </div>
                        </div>
                    </div>

                    <div id="upwork-output-actions" class="hidden pt-4 mt-4 border-t border-slate-800 flex items-center justify-between gap-3">
                        <button onclick="copyToClipboard('upwork-out-full')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium py-2.5 rounded-xl border border-slate-700 flex items-center justify-center space-x-1.5 transition">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            <span>Copy Proposal</span>
                        </button>
                        <button onclick="saveProposalToCrm()" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold py-2.5 rounded-xl flex items-center justify-center space-x-1.5 shadow-md shadow-emerald-600/20 transition">
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            <span>Save to CRM Pipeline</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 4: COLD OUTREACH STUDIO (LinkedIn & Email)
        ========================================== -->
        <div id="view-outreach" class="hidden space-y-6">
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white">Cold Outreach Studio (LinkedIn & Email)</h2>
                        <p class="text-xs text-slate-400">Psychology-backed, value-first messaging templates designed to trigger client curiosity without feeling like spam.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Framework 1 -->
                    <div onclick="selectOutreachTemplate('bug_catcher')" class="outreach-card active-outreach bg-dark-950 border-2 border-emerald-500 p-4 rounded-xl cursor-pointer hover:border-emerald-400 transition">
                        <div class="flex items-center space-x-2 text-emerald-400 font-bold text-xs mb-1">
                            <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            <span>1. The Helpful Bug Catcher</span>
                        </div>
                        <p class="text-xs text-slate-400">Find an issue on their site and send a polite notice with the exact fix. Highest response rate!</p>
                    </div>

                    <!-- Framework 2 -->
                    <div onclick="selectOutreachTemplate('agency_overflow')" class="outreach-card bg-dark-950 border border-slate-800 p-4 rounded-xl cursor-pointer hover:border-slate-700 transition">
                        <div class="flex items-center space-x-2 text-sky-400 font-bold text-xs mb-1">
                            <i data-lucide="users" class="w-4 h-4"></i>
                            <span>2. Agency White-Label Partner</span>
                        </div>
                        <p class="text-xs text-slate-400">Offer on-demand backend capacity when their in-house team is overloaded with client tickets.</p>
                    </div>

                    <!-- Framework 3 -->
                    <div onclick="selectOutreachTemplate('emergency_fix')" class="outreach-card bg-dark-950 border border-slate-800 p-4 rounded-xl cursor-pointer hover:border-slate-700 transition">
                        <div class="flex items-center space-x-2 text-amber-400 font-bold text-xs mb-1">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                            <span>3. 15-Minute Emergency Fix</span>
                        </div>
                        <p class="text-xs text-slate-400">Offer to solve a single annoying bug in 15 minutes to prove technical competence.</p>
                    </div>
                </div>

                <!-- Template Customizer Inputs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Prospect Name</label>
                        <input type="text" id="outreach-client-name" placeholder="e.g. David" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Company / Agency Name</label>
                        <input type="text" id="outreach-company-name" placeholder="e.g. Apex Digital" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Their Website URL</label>
                        <input type="text" id="outreach-website" placeholder="e.g. apexdigital.com" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex justify-end mb-6">
                    <button onclick="generateCustomOutreach()" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-5 py-2.5 rounded-xl flex items-center space-x-2 transition">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Generate Formatted Messages</span>
                    </button>
                </div>

                <!-- Generated Variations -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- LinkedIn Note -->
                    <div class="bg-dark-950 border border-slate-800 p-4 rounded-xl space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-sky-400 flex items-center space-x-1.5">
                                <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                <span>LinkedIn Connection Note (< 300 chars)</span>
                            </span>
                            <button onclick="copyToClipboard('outreach-linkedin-text')" class="text-xs text-slate-400 hover:text-white bg-slate-800 px-2 py-1 rounded border border-slate-700">Copy</button>
                        </div>
                        <textarea id="outreach-linkedin-text" rows="4" class="w-full bg-dark-900 border border-slate-800 text-slate-200 text-xs rounded-lg p-3 font-mono focus:outline-none"></textarea>
                    </div>

                    <!-- Full Cold Email -->
                    <div class="bg-dark-950 border border-slate-800 p-4 rounded-xl space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-400 flex items-center space-x-1.5">
                                <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                <span>Full Cold Email</span>
                            </span>
                            <button onclick="copyToClipboard('outreach-email-text')" class="text-xs text-slate-400 hover:text-white bg-slate-800 px-2 py-1 rounded border border-slate-700">Copy</button>
                        </div>
                        <textarea id="outreach-email-text" rows="4" class="w-full bg-dark-900 border border-slate-800 text-slate-200 text-xs rounded-lg p-3 font-mono focus:outline-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- LinkedIn Safe Auto-Connector Panel (Chrome Integrated) -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-bold text-white flex items-center space-x-2">
                                <i data-lucide="linkedin" class="w-5 h-5 text-sky-400"></i>
                                <span>LinkedIn Safe Auto-Connector (Chrome Integrated)</span>
                            </h3>
                            <span class="text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full">Anti-Ban Safe</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Directly launch targeted Founders &amp; CTOs in your logged-in Chrome with pre-filled high-converting connection notes.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button onclick="autoConnectAllLinkedIn()" id="btn-li-auto-all" class="bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-xs px-3.5 py-2 rounded-xl shadow-md shadow-sky-500/20 flex items-center space-x-1.5 transition transform hover:scale-[1.02]">
                            <i data-lucide="bot" class="w-4 h-4"></i>
                            <span>🤖 1-Click Auto-Connect ALL</span>
                        </button>
                        <div class="bg-dark-950 border border-slate-800 px-3 py-1.5 rounded-xl text-xs font-mono">
                            <span class="text-slate-400">Today: </span>
                            <span id="li-quota-display" class="font-bold text-sky-400">0 / 20 Sent</span>
                        </div>
                        <button onclick="loadLinkedInProspects()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs px-3 py-1.5 rounded-xl border border-slate-700 flex items-center space-x-1.5 transition">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>

                <div id="linkedin-prospects-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Populated via JS -->
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 4.5: AI DEAL CLOSER & NEGOTIATOR
        ========================================== -->
        <div id="view-closer" class="hidden space-y-6">
            <div class="bg-gradient-to-r from-dark-900 via-dark-850 to-dark-900 border border-emerald-500/30 rounded-2xl p-6 shadow-2xl relative overflow-hidden">
                <div class="absolute right-0 top-0 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider">🤝 Deal Closing Engine</span>
                            <span class="text-xs text-slate-400 font-mono">Target: $150 – $300 / deal</span>
                        </div>
                        <h2 class="text-2xl font-black text-white">AI Deal Closer &amp; Client Reply Negotiator</h2>
                        <p class="text-xs sm:text-sm text-slate-300 max-w-2xl">
                            Paste incoming client messages from Upwork, LinkedIn, or Email. The AI detects their exact buying intent, handles budget objections, quotes defensible flat-rates, and crafts high-converting closing replies in seconds.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left Column: Input Panel -->
                <div class="lg:col-span-6 bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center space-x-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                            <i data-lucide="message-square-plus" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Client Inquiry &amp; Deal Parameters</h3>
                            <p class="text-xs text-slate-400">Provide the client message to formulate the winning response.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Client Name (if known)</label>
                            <input type="text" id="closer-client-name" placeholder="e.g. David / Sarah" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Platform / Channel</label>
                            <select id="closer-platform" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                                <option value="Cold Email">Cold Email Reply</option>
                                <option value="Upwork">Upwork Chat / Proposal</option>
                                <option value="LinkedIn">LinkedIn InMail / DM</option>
                                <option value="WhatsApp">WhatsApp / Slack</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Service Type</label>
                            <select id="closer-service-type" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                                <option value="Web Development & Bug Fix">🐘 Web Development &amp; Bug Fix</option>
                                <option value="Technical SEO & Schema Optimization">📈 Technical SEO &amp; Rankings</option>
                                <option value="Google Ads & GA4 Tracking Setup">🎯 Google Ads &amp; GA4 Tracking</option>
                                <option value="E-Commerce Store Optimization">🛍️ E-Commerce &amp; Speed Boost</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Target Deal Value ($ USD)</label>
                            <div class="flex items-center space-x-1">
                                <input type="number" id="closer-deal-size" value="150" min="50" max="2000" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none font-mono">
                            </div>
                        </div>
                    </div>

                    <!-- Quick Pricing Preset Pills -->
                    <div class="flex items-center space-x-2 text-xs">
                        <span class="text-slate-400 text-[11px]">Quick Quotes:</span>
                        <button type="button" onclick="document.getElementById('closer-deal-size').value=100" class="px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-mono">$100</button>
                        <button type="button" onclick="document.getElementById('closer-deal-size').value=150" class="px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-mono">$150</button>
                        <button type="button" onclick="document.getElementById('closer-deal-size').value=200" class="px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-mono">$200</button>
                        <button type="button" onclick="document.getElementById('closer-deal-size').value=300" class="px-2 py-0.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-mono">$300</button>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Client Message / Objection</label>
                        <textarea id="closer-client-msg" rows="5" placeholder="Paste the client's email or message here (e.g. 'Hey, how much would you charge for this and when can you start?')" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl p-3 text-xs focus:border-emerald-500 focus:outline-none font-mono"></textarea>
                    </div>

                    <button onclick="generateDealCloser()" id="btn-generate-closer" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl flex items-center justify-center space-x-2 text-sm shadow-lg shadow-emerald-600/20 transition">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Analyze &amp; Craft Closing Response</span>
                    </button>
                </div>

                <!-- Right Column: Strategic Analysis & Ready Reply -->
                <div class="lg:col-span-6 bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                                <i data-lucide="check-check" class="w-4 h-4 text-emerald-400"></i>
                                <span>Psychological Closing Response</span>
                            </h3>
                            <span id="closer-word-count" class="text-xs text-slate-400 bg-slate-800 px-2 py-0.5 rounded border border-slate-700 font-mono">0 words</span>
                        </div>

                        <!-- Empty State -->
                        <div id="closer-output-empty" class="text-center py-16 text-slate-500 space-y-3">
                            <i data-lucide="handshake" class="w-12 h-12 mx-auto text-slate-600"></i>
                            <p class="text-xs">Paste incoming client message on the left and click "Analyze &amp; Craft Closing Response".<br>Your tactical response will appear here.</p>
                        </div>

                        <!-- Content State -->
                        <div id="closer-output-content" class="hidden space-y-4">
                            <!-- Tactical Strategy Card -->
                            <div class="bg-dark-950 border border-slate-800 rounded-xl p-3.5 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold">Detected Intent:</span>
                                        <span id="closer-intent-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Pricing &amp; Scope</span>
                                    </div>
                                    <span id="closer-price-badge" class="text-xs font-bold text-emerald-400 font-mono">$150 fixed</span>
                                </div>
                                <p id="closer-strategy-text" class="text-xs text-slate-300 italic"></p>
                            </div>

                            <!-- Full Ready-to-Send Reply -->
                            <div>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tailored Closing Reply (Ready to Send):</span>
                                <textarea id="closer-reply-text" rows="9" class="w-full bg-dark-950 border border-slate-700 text-slate-100 rounded-xl p-3 text-xs font-mono focus:outline-none scrollbar-thin"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div id="closer-output-actions" class="hidden pt-4 mt-4 border-t border-slate-800 flex items-center justify-between gap-3">
                        <button onclick="copyToClipboard('closer-reply-text')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium py-2.5 rounded-xl border border-slate-700 flex items-center justify-center space-x-1.5 transition">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            <span>Copy Reply</span>
                        </button>
                        <button onclick="saveCloserLeadToCrm()" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold py-2.5 rounded-xl flex items-center justify-center space-x-1.5 shadow-md shadow-emerald-600/20 transition">
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            <span>Save to CRM (In Discussion)</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 5: ₹50,000 / MONTH CRM & GOAL TRACKER
        ========================================== -->
        <div id="view-crm" class="hidden space-y-6">
            <!-- Revenue Goal Progress Card -->
            <div class="bg-gradient-to-r from-dark-900 via-dark-850 to-dark-900 border border-emerald-500/30 rounded-2xl p-6 shadow-2xl relative overflow-hidden">
                <div class="absolute right-0 top-0 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                    <div>
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">🎯 Monthly Revenue Goal</span>
                        <h2 class="text-2xl sm:text-3xl font-black text-white mt-1">₹<span id="crm-current-inr">0</span> <span class="text-slate-400 text-lg font-normal">/ ₹50,000</span></h2>
                        <p class="text-xs text-slate-400 mt-0.5">Equivalent: $<span id="crm-current-usd">0</span> USD Won</p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button onclick="openAddLeadModal()" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-4 py-2.5 rounded-xl shadow-lg shadow-emerald-600/20 flex items-center space-x-2 transition">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Add New Deal / Lead</span>
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-slate-800/80 rounded-full h-4 p-0.5 border border-slate-700">
                    <div id="crm-goal-bar" class="bg-gradient-to-r from-emerald-500 to-brand-500 h-3 rounded-full transition-all duration-700" style="width: 0%"></div>
                </div>
                <div class="flex justify-between items-center text-xs text-slate-400 mt-2 font-mono">
                    <span>Progress: <span id="crm-goal-percent" class="text-emerald-400 font-bold">0%</span></span>
                    <span>Remaining Gap: ₹<span id="crm-gap-inr">50,000</span> ($<span id="crm-gap-usd">600</span>)</span>
                </div>
            </div>

            <!-- Leads Pipeline Table -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-white">Active Client Deals Pipeline</h3>
                    <div class="flex items-center space-x-2">
                        <select id="crm-filter-status" onchange="loadCrmLeads()" class="bg-slate-800 border border-slate-700 text-slate-200 text-xs rounded-lg px-3 py-1.5 focus:ring-1 focus:ring-emerald-500">
                            <option value="all">All Statuses</option>
                            <option value="won">🏆 Won Deals</option>
                            <option value="discussing">💬 In Discussion</option>
                            <option value="contacted">✉️ Contacted</option>
                            <option value="new">🆕 New Leads</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-800/50 text-slate-400 border-b border-slate-800 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Deal / Opportunity</th>
                                <th class="py-3 px-4">Platform</th>
                                <th class="py-3 px-4">Client</th>
                                <th class="py-3 px-4">Value ($ / ₹)</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="crm-leads-tbody" class="divide-y divide-slate-800/50">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 6: ANTI-BAN SHIELD & SETTINGS
        ========================================== -->
        <div id="view-safety" class="hidden space-y-6">
            <!-- Anti-Ban Quota Shield -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                        <i data-lucide="shield" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white">Anti-Ban Safety Shield & Daily Limits</h2>
                        <p class="text-xs text-slate-400">Protects your LinkedIn and Upwork accounts from spam flags by enforcing strict safe daily quotas.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- LinkedIn Quota -->
                    <div class="bg-dark-950 border border-slate-800 p-4 rounded-xl">
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="font-bold text-sky-400">LinkedIn Connections</span>
                            <span id="shield-linkedin-ratio" class="font-mono text-slate-400">0 / 20</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2">
                            <div id="shield-linkedin-bar" class="bg-sky-500 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">Safe Limit: Max 20 connection requests/day.</p>
                    </div>

                    <!-- Email Quota -->
                    <div class="bg-dark-950 border border-slate-800 p-4 rounded-xl">
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="font-bold text-emerald-400">Direct Cold Emails</span>
                            <span id="shield-email-ratio" class="font-mono text-slate-400">0 / 30</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2">
                            <div id="shield-email-bar" class="bg-emerald-500 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">Safe Limit: Max 30 customized emails/day.</p>
                    </div>

                    <!-- Upwork Quota -->
                    <div class="bg-dark-950 border border-slate-800 p-4 rounded-xl">
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="font-bold text-amber-400">Upwork Proposals</span>
                            <span id="shield-upwork-ratio" class="font-mono text-slate-400">0 / 15</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2">
                            <div id="shield-upwork-bar" class="bg-amber-500 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">Safe Limit: Max 15 targeted bids/day.</p>
                    </div>
                </div>
            </div>

            <!-- Profile & Free API Configuration -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Your Freelance Profile &amp; AI Engine</h3>
                        <p class="text-xs text-slate-400">Used for customizing all proposals, cold emails, and closing responses.</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Your Name</label>
                        <input type="text" id="settings-name" placeholder="Jay" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Primary Title</label>
                        <input type="text" id="settings-title" placeholder="Laravel & Full-Stack Web Developer" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Monthly Goal (INR ₹)</label>
                        <input type="number" id="settings-goal" placeholder="50000" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Google Gemini Free API Key (Optional)</label>
                        <input type="password" id="settings-gemini-key" placeholder="AIzaSy... (Leave empty to use built-in engine)" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none font-mono">
                    </div>
                </div>
            </div>

            <!-- Real SMTP Email Dispatcher Setup (0% Spam, Direct Primary Inbox Delivery) -->
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center">
                        <i data-lucide="mail-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-bold text-white">Real SMTP Email Dispatcher Setup</h3>
                            <span class="text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full">0% Spam / Primary Inbox</span>
                        </div>
                        <p class="text-xs text-slate-400">Connect your Gmail (via Google App Password) or custom SMTP to send real socket-level emails directly to clients.</p>
                    </div>
                </div>

                <!-- Gmail App Password Guide Alert -->
                <div class="bg-dark-950 border border-slate-800 rounded-xl p-4 text-xs text-slate-300 space-y-2">
                    <div class="flex items-center space-x-2 font-bold text-amber-400">
                        <i data-lucide="info" class="w-4 h-4"></i>
                        <span>How to get your Gmail 16-character App Password (Free &amp; 100% Safe):</span>
                    </div>
                    <ol class="list-decimal list-inside space-y-1 text-slate-400 text-[11px] pl-1">
                        <li>Go to your Google Account: <a href="https://myaccount.google.com/security" target="_blank" class="text-emerald-400 underline">myaccount.google.com/security</a></li>
                        <li>Ensure <span class="text-white font-semibold">2-Step Verification</span> is enabled.</li>
                        <li>Search for <span class="text-white font-semibold">"App Passwords"</span> in the top search bar.</li>
                        <li>Create a new app named <span class="text-emerald-400 font-mono">LeadForge</span> and copy the 16-character code (e.g. <span class="text-slate-300 font-mono">xxxx xxxx xxxx xxxx</span>).</li>
                        <li>Paste that code in the "Gmail App Password" field below. That's it!</li>
                    </ol>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">SMTP Host</label>
                        <input type="text" id="settings-smtp-host" placeholder="smtp.gmail.com" value="smtp.gmail.com" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">SMTP Port</label>
                        <input type="number" id="settings-smtp-port" placeholder="587" value="587" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Gmail / SMTP Username</label>
                        <input type="email" id="settings-smtp-user" placeholder="yourname@gmail.com" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Gmail 16-char App Password</label>
                        <input type="password" id="settings-smtp-pass" placeholder="•••• •••• •••• ••••" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Sender Display Name</label>
                        <input type="text" id="settings-smtp-from-name" placeholder="Jay | Web & SEO Specialist" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Sender From Email</label>
                        <input type="email" id="settings-smtp-from-email" placeholder="yourname@gmail.com" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <!-- Test Email Section -->
                <div class="bg-dark-950 border border-slate-800 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-white flex items-center space-x-1.5">
                            <i data-lucide="send" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>Test Live SMTP Email Delivery:</span>
                        </span>
                        <span class="text-[11px] text-slate-400">Verifies TLS handshake &amp; mailbox receipt</span>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="email" id="settings-test-to" placeholder="Enter test recipient email (e.g. your personal email)" class="flex-1 bg-dark-900 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                        <button onclick="sendTestEmail()" id="btn-test-smtp" class="bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30 px-5 py-2 rounded-xl text-xs font-semibold flex items-center justify-center space-x-2 transition">
                            <i data-lucide="mail-forward" class="w-3.5 h-3.5"></i>
                            <span>Send Test Email</span>
                        </button>
                    </div>

                    <div id="smtp-test-result" class="hidden text-xs p-3 rounded-lg border font-mono"></div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex justify-end space-x-3">
                    <button onclick="saveSettings()" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-8 py-3 rounded-xl transition shadow-lg shadow-emerald-600/20 flex items-center space-x-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Profile &amp; SMTP Configuration</span>
                    </button>
                </div>
            </div>
        </div>

    </main>

    <!-- Modal: Add Lead to CRM -->
    <div id="modal-add-lead" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-dark-900 border border-slate-800 w-full max-w-lg rounded-2xl p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white">Add Deal to ₹50k Goal Pipeline</h3>
                <button onclick="closeAddLeadModal()" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Deal Title</label>
                <input type="text" id="modal-lead-title" placeholder="e.g. Laravel Stripe Integration" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Platform</label>
                    <select id="modal-lead-platform" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:outline-none">
                        <option value="Upwork">Upwork</option>
                        <option value="LinkedIn">LinkedIn</option>
                        <option value="Reddit">Reddit</option>
                        <option value="Direct Email">Direct Email</option>
                        <option value="Twitter/X">Twitter/X</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Status</label>
                    <select id="modal-lead-status" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:outline-none">
                        <option value="new">🆕 New</option>
                        <option value="contacted">✉️ Contacted</option>
                        <option value="discussing">💬 In Discussion</option>
                        <option value="won">🏆 Won (Earned)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Deal Value ($ USD)</label>
                    <input type="number" id="modal-lead-usd" placeholder="100" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Client Name</label>
                    <input type="text" id="modal-lead-client" placeholder="e.g. Alex" class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Notes / Pitch Reference</label>
                <textarea id="modal-lead-notes" rows="3" placeholder="Additional notes or link..." class="w-full bg-dark-950 border border-slate-700 text-white rounded-xl p-3 text-xs focus:outline-none"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end space-x-2">
                <button onclick="closeAddLeadModal()" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs rounded-xl hover:bg-slate-700">Cancel</button>
                <button onclick="submitNewLead()" class="px-5 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-500 shadow-md shadow-emerald-600/20">Save Deal</button>
            </div>
        </div>
    </div>

    <!-- Modal: View Exact Sent Proposal / Pitch -->
    <div id="modal-view-sent-pitch" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-dark-900 border border-slate-800 w-full max-w-2xl rounded-2xl p-6 shadow-2xl space-y-4 max-h-[90vh] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full uppercase tracking-wider">✅ 100% Dispatched Proof</span>
                        <h3 id="modal-pitch-title" class="text-base font-bold text-white mt-1">Pitch Copy</h3>
                        <p id="modal-pitch-meta" class="text-xs text-slate-400"></p>
                    </div>
                    <button onclick="closeSentPitchModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="mt-4 space-y-2">
                    <span class="text-xs font-semibold text-slate-300">Exact Message / Cold Email Sent:</span>
                    <textarea id="modal-pitch-content" rows="11" readonly class="w-full bg-dark-950 border border-slate-800 text-slate-200 rounded-xl p-4 text-xs font-mono focus:outline-none scrollbar-thin"></textarea>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
                <a id="modal-pitch-link" href="#" target="_blank" class="text-xs text-emerald-400 hover:underline flex items-center space-x-1">
                    <span>Open Target Client Website</span>
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                </a>
                <div class="flex space-x-2">
                    <button onclick="copyToClipboard('modal-pitch-content')" class="px-4 py-2 bg-slate-800 text-slate-200 text-xs rounded-xl hover:bg-slate-700 border border-slate-700 flex items-center space-x-1.5">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span>Copy Pitch</span>
                    </button>
                    <button onclick="closeSentPitchModal()" class="px-5 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-500">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 space-y-2 pointer-events-none"></div>

    <!-- Frontend Core Application Script -->
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
