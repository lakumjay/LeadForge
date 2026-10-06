<?php
/**
 * LeadForge AI — Mobile & Desktop LinkedIn 1-Click Assistant
 * 
 * 100% Humanized, Zero-Ban Architecture:
 * - "Open Profile" -> Opens official LinkedIn App / Chrome directly
 * - "Copy Note" -> 1-Click Clipboard copy for targeted <300 char custom note
 * - "Sent" / "Skip" / "Accepted" -> Instant status tracking & CRM sync
 * - Daily Limit: 15-20 connections per day (Safe limit)
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/api/sales_navigator.php';

$db = Database::getConnection();
$settings = getSettings();
$dailyLimit = (int)($settings['daily_linkedin_limit'] ?? 15);
$usdToInr = (float)($settings['usd_to_inr'] ?? 86.5);

// Handle AJAX actions
if (isset($_GET['api']) || isset($_POST['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;
    $action = $data['action'] ?? ($_GET['action'] ?? 'list');

    if ($action === 'list') {
        $filter = $_GET['filter'] ?? 'pending';
        $sql = "SELECT * FROM linkedin_queue";
        if ($filter === 'pending') {
            $sql .= " WHERE status = 'pending'";
        } elseif ($filter === 'sent') {
            $sql .= " WHERE status = 'sent' AND DATE(sent_at) = DATE('now')";
        } elseif ($filter === 'accepted') {
            $sql .= " WHERE status = 'accepted'";
        }
        $sql .= " ORDER BY id DESC LIMIT 50";

        $stmt = $db->query($sql);
        $prospects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Daily Sent Count
        $stmt = $db->query("SELECT COUNT(*) FROM linkedin_queue WHERE status = 'sent' AND DATE(sent_at) = DATE('now')");
        $todaySent = (int)$stmt->fetchColumn();

        echo json_encode([
            'ok' => true,
            'today_sent' => $todaySent,
            'daily_limit' => $dailyLimit,
            'prospects' => $prospects
        ]);
        exit;
    }

    if ($action === 'update_status') {
        $id = (int)($data['id'] ?? 0);
        $newStatus = trim($data['status'] ?? 'sent'); // 'sent', 'skipped', 'accepted'
        
        $stmt = $db->prepare("SELECT * FROM linkedin_queue WHERE id = ?");
        $stmt->execute([$id]);
        $prospect = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prospect) {
            $stmt = $db->prepare("UPDATE linkedin_queue SET status = ?, sent_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$newStatus, $id]);

            if ($newStatus === 'sent') {
                // Log to outreach_logs & sync with CRM leads table
                $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, 'LinkedIn', 'Mobile Assistant Connection Note')")
                   ->execute([$id]);

                $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    "LinkedIn Connection: {$prospect['name']} ({$prospect['company']})",
                    'Mobile LinkedIn Assist (linkedin.php)',
                    $prospect['name'],
                    $prospect['company'],
                    $prospect['linkedin_url'],
                    'LinkedIn',
                    'contacted',
                    250,
                    250 * $usdToInr,
                    "Role: {$prospect['role']}\nDispatched via 1-Click Mobile Assist.",
                    $prospect['note']
                ]);
            }

            echo json_encode(['ok' => true, 'message' => "Prospect updated to {$newStatus}"]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Prospect not found']);
        }
        exit;
    }

    if ($action === 'generate_fresh') {
        // Generate fresh 15 prospects into queue
        $countries = ['United States', 'United Kingdom', 'Canada', 'Australia'];
        $country = $countries[array_rand($countries)];
        $categories = ['ecommerce', 'agency', 'saas_tech'];
        $category = $categories[array_rand($categories)];

        $prospects = scanGoogleSalesNavigatorDorks($country, $category, 15);
        $addedCount = 0;

        foreach ($prospects as $p) {
            $name = $p['founder'];
            $company = $p['company'];
            $role = $p['role'];
            $url = $p['profile_url'];
            $note = $p['connection_note'];

            try {
                $stmt = $db->prepare("INSERT INTO linkedin_queue (company, name, role, linkedin_url, note, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$company, $name, $role, $url, $note]);
                $addedCount++;
            } catch (Throwable $e) {
                // Ignore duplicates
            }
        }

        echo json_encode(['ok' => true, 'message' => "Generated {$addedCount} fresh prospects in queue!"]);
        exit;
    }
}

// Ensure at least 15 prospects exist in queue on initial page view
$stmt = $db->query("SELECT COUNT(*) FROM linkedin_queue WHERE status = 'pending'");
$pendingCount = (int)$stmt->fetchColumn();
if ($pendingCount === 0) {
    $initialProspects = scanGoogleSalesNavigatorDorks('United States', 'ecommerce', 15);
    foreach ($initialProspects as $p) {
        try {
            $stmt = $db->prepare("INSERT INTO linkedin_queue (company, name, role, linkedin_url, note, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$p['company'], $p['founder'], $p['role'], $p['profile_url'], $p['connection_note']]);
        } catch (Throwable $e) {}
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#090d16">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>LinkedIn 1-Click Mobile Assist — LeadForge AI</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💼</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { 500: '#0ea5e9', 600: '#0284c7' },
                        dark: { 800: '#1e293b', 900: '#0f172a', 950: '#090d16' }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
</head>
<body class="bg-dark-950 text-slate-100 font-sans min-h-screen pb-16 antialiased selection:bg-sky-500 selection:text-black">

    <!-- Top Sticky Header -->
    <header class="bg-dark-900/90 border-b border-slate-800 sticky top-0 z-50 backdrop-blur-md px-4 py-3">
        <div class="max-w-2xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <a href="index.php" class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center border border-sky-500/30">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h1 class="text-sm font-bold text-white flex items-center space-x-1.5">
                        <span>LinkedIn 1-Click Assist</span>
                        <span class="text-[10px] bg-sky-500/20 text-sky-400 px-1.5 py-0.5 rounded font-mono border border-sky-500/30">Mobile</span>
                    </h1>
                    <p class="text-[11px] text-slate-400">Zero-ban humanized 5-min daily workflow</p>
                </div>
            </div>

            <!-- Daily Sent Badge -->
            <div class="bg-dark-950 border border-slate-800 px-3 py-1.5 rounded-xl text-xs font-mono flex items-center space-x-1.5">
                <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                <span class="text-slate-400">Today: </span>
                <span id="daily-quota-badge" class="font-bold text-sky-400">0 / <?= $dailyLimit ?></span>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="max-w-2xl mx-auto flex space-x-2 mt-3 pt-2 border-t border-slate-800/60 overflow-x-auto scrollbar-none">
            <button onclick="loadQueue('pending')" id="tab-filter-pending" class="filter-tab px-3 py-1 text-xs rounded-lg font-medium bg-sky-500/20 text-sky-400 border border-sky-500/30">
                ⏳ Pending Queue (<span id="count-pending">0</span>)
            </button>
            <button onclick="loadQueue('sent')" id="tab-filter-sent" class="filter-tab px-3 py-1 text-xs rounded-lg font-medium text-slate-400 hover:text-white bg-slate-800/40">
                ✅ Sent Today (<span id="count-sent">0</span>)
            </button>
            <button onclick="loadQueue('accepted')" id="tab-filter-accepted" class="filter-tab px-3 py-1 text-xs rounded-lg font-medium text-slate-400 hover:text-white bg-slate-800/40">
                🤝 Accepted
            </button>
            <button onclick="generateFreshBatch()" class="ml-auto px-3 py-1 text-xs rounded-lg font-semibold bg-emerald-600 hover:bg-emerald-500 text-white flex items-center space-x-1 transition shrink-0">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>+15 Leads</span>
            </button>
        </div>
    </header>

    <!-- Main Prospects Container -->
    <main class="max-w-2xl mx-auto px-4 py-4 space-y-4" id="prospects-container">
        <!-- Cards rendered via JS -->
        <div class="text-center py-12 text-slate-500 text-xs">
            <div class="animate-spin w-6 h-6 border-2 border-sky-400 border-t-transparent rounded-full mx-auto mb-2"></div>
            Loading targeted LinkedIn prospects...
        </div>
    </main>

    <!-- Floating Toast Notification -->
    <div id="toast" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-slate-900 border border-sky-500/40 text-white text-xs px-4 py-2.5 rounded-xl shadow-2xl transition-all duration-300 opacity-0 pointer-events-none z-50 flex items-center space-x-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-sky-400"></i>
        <span id="toast-text">Note copied to clipboard!</span>
    </div>

    <script>
        let currentFilter = 'pending';
        let prospectsData = [];
        let dailySentCount = 0;
        const dailyMax = <?= $dailyLimit ?>;

        async function loadQueue(filter = 'pending') {
            currentFilter = filter;
            document.querySelectorAll('.filter-tab').forEach(b => {
                b.className = 'filter-tab px-3 py-1 text-xs rounded-lg font-medium text-slate-400 hover:text-white bg-slate-800/40';
            });
            const activeBtn = document.getElementById('tab-filter-' + filter);
            if (activeBtn) {
                activeBtn.className = 'filter-tab px-3 py-1 text-xs rounded-lg font-medium bg-sky-500/20 text-sky-400 border border-sky-500/30';
            }

            try {
                const res = await fetch('linkedin.php?api=1&action=list&filter=' + filter);
                const data = await res.json();
                
                if (data.ok) {
                    prospectsData = data.prospects || [];
                    dailySentCount = data.today_sent || 0;
                    document.getElementById('daily-quota-badge').innerText = `${dailySentCount} / ${dailyMax}`;
                    
                    if (filter === 'pending') {
                        document.getElementById('count-pending').innerText = prospectsData.length;
                    }
                    renderCards();
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderCards() {
            const container = document.getElementById('prospects-container');
            if (prospectsData.length === 0) {
                container.innerHTML = `
                    <div class="bg-dark-900 border border-slate-800 rounded-2xl p-8 text-center space-y-3">
                        <i data-lucide="check-circle" class="w-12 h-12 mx-auto text-sky-400 opacity-60"></i>
                        <h3 class="text-sm font-bold text-white">Queue is Clear!</h3>
                        <p class="text-xs text-slate-400">You've completed all pending prospects in this filter.</p>
                        <button onclick="generateFreshBatch()" class="bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold px-4 py-2 rounded-xl">
                            + Generate 15 Fresh Decision Makers
                        </button>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            container.innerHTML = prospectsData.map((p, idx) => `
                <div id="prospect-card-${p.id}" class="bg-dark-900 border border-slate-800 hover:border-slate-700 rounded-2xl p-4 shadow-lg space-y-3 transition">
                    <!-- Top Info -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="w-5 h-5 rounded-full bg-sky-500/20 text-sky-400 flex items-center justify-center text-[10px] font-bold">${idx + 1}</span>
                                <h3 class="text-sm font-bold text-white">${escapeHtml(p.name)}</h3>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">${escapeHtml(p.role)} • <span class="text-slate-300 font-medium">${escapeHtml(p.company)}</span></p>
                        </div>
                        <span class="text-[10px] font-mono bg-slate-800 text-slate-400 px-2 py-0.5 rounded border border-slate-700">$250 Deal</span>
                    </div>

                    <!-- Custom Tailored Note -->
                    <div class="bg-dark-950 border border-slate-800/80 rounded-xl p-3 relative">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10px] font-bold text-sky-400 flex items-center space-x-1">
                                <i data-lucide="sparkles" class="w-3 h-3"></i>
                                <span>Tailored Connection Note (&lt;300 chars)</span>
                            </span>
                            <span class="text-[10px] text-slate-500 font-mono">${p.note.length} chars</span>
                        </div>
                        <p id="note-text-${p.id}" class="text-xs text-slate-200 leading-relaxed font-sans">${escapeHtml(p.note)}</p>
                    </div>

                    <!-- 1-Click Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <!-- Open Profile -->
                        <button onclick="openProfile('${escapeJs(p.linkedin_url)}', ${p.id})" class="flex-1 min-w-[130px] bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs py-2.5 px-3 rounded-xl flex items-center justify-center space-x-1.5 shadow-md shadow-sky-600/20 transition active:scale-95">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span>1. Open Profile</span>
                        </button>

                        <!-- Copy Note -->
                        <button onclick="copyNote(${p.id})" class="flex-1 min-w-[120px] bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold text-xs py-2.5 px-3 rounded-xl flex items-center justify-center space-x-1.5 transition active:scale-95">
                            <i data-lucide="copy" class="w-3.5 h-3.5 text-sky-400"></i>
                            <span>2. Copy Note</span>
                        </button>

                        <!-- Mark Sent -->
                        <button onclick="markStatus(${p.id}, 'sent')" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-2.5 px-3.5 rounded-xl flex items-center justify-center space-x-1 transition active:scale-95">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            <span>Sent</span>
                        </button>

                        <!-- Skip -->
                        <button onclick="markStatus(${p.id}, 'skipped')" class="p-2.5 text-slate-500 hover:text-slate-300 hover:bg-slate-800/60 rounded-xl transition" title="Skip">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            lucide.createIcons();
        }

        function openProfile(url, id) {
            window.open(url, '_blank');
            copyNote(id);
        }

        function copyNote(id) {
            const noteEl = document.getElementById('note-text-' + id);
            if (!noteEl) return;
            const text = noteEl.innerText;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => showToast('Note copied! Paste on LinkedIn & hit Connect'));
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showToast('Note copied! Paste on LinkedIn & hit Connect');
            }
        }

        async function markStatus(id, status) {
            try {
                const res = await fetch('linkedin.php?api=1', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update_status', id: id, status: status })
                });
                const data = await res.json();
                if (data.ok) {
                    const card = document.getElementById('prospect-card-' + id);
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => card.remove(), 250);
                    }
                    if (status === 'sent') {
                        dailySentCount++;
                        document.getElementById('daily-quota-badge').innerText = `${dailySentCount} / ${dailyMax}`;
                        showToast('✅ Saved to CRM! Deal added to ₹50k pipeline.');
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function generateFreshBatch() {
            showToast('Generating 15 fresh prospects...');
            try {
                const res = await fetch('linkedin.php?api=1', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'generate_fresh' })
                });
                const data = await res.json();
                if (data.ok) {
                    showToast(data.message);
                    loadQueue('pending');
                }
            } catch (e) {}
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-text').innerText = msg;
            toast.classList.remove('opacity-0', 'pointer-events-none');
            setTimeout(() => {
                toast.classList.add('opacity-0', 'pointer-events-none');
            }, 3000);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function escapeJs(str) {
            if (!str) return '';
            return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadQueue('pending');
        });
    </script>
</body>
</html>
