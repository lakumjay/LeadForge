/**
 * LeadForge AI - Frontend Interactive Controller
 * Lightning-Fast, Zero-Dependency Architecture
 */

let soundAlertsEnabled = true;
let currentTab = 'radar';
let radarAutoTimer = null;
let selectedOutreachType = 'bug_catcher';

// Web Audio API Synthesizer for Zero-File Sound Alerts
function playDingSound() {
    if (!soundAlertsEnabled) return;
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
        osc.frequency.exponentialRampToValueAtTime(1760, audioCtx.currentTime + 0.15); // A6
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.4);
    } catch (e) {
        console.warn('Audio play error:', e);
    }
}

function playErrorAlarmSound() {
    if (!soundAlertsEnabled) return;
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(440, audioCtx.currentTime);
        osc.frequency.setValueAtTime(220, audioCtx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.5);
    } catch (e) {
        console.warn('Error audio play error:', e);
    }
}

function toggleSoundAlert() {
    soundAlertsEnabled = !soundAlertsEnabled;
    const icon = document.getElementById('sound-icon');
    if (soundAlertsEnabled) {
        icon.setAttribute('data-lucide', 'volume-2');
        icon.classList.remove('text-slate-500');
        icon.classList.add('text-emerald-400');
        showToast('Sound alerts enabled', 'success');
        playDingSound();
    } else {
        icon.setAttribute('data-lucide', 'volume-x');
        icon.classList.remove('text-emerald-400');
        icon.classList.add('text-slate-500');
        showToast('Sound alerts muted', 'info');
    }
    if (window.lucide) lucide.createIcons();
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    const color = type === 'success' ? 'border-emerald-500 bg-dark-900 text-emerald-300' : (type === 'error' ? 'border-red-500 bg-dark-900 text-red-300' : 'border-sky-500 bg-dark-900 text-sky-300');
    toast.className = `border px-4 py-2.5 rounded-xl shadow-2xl text-xs font-medium flex items-center space-x-2 transition-all duration-300 transform translate-y-2 opacity-0 ${color}`;
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    }, 10);

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

function copyToClipboard(elementIdOrText) {
    let text = '';
    const el = document.getElementById(elementIdOrText);
    if (el) {
        text = el.value || el.innerText;
    } else {
        text = elementIdOrText;
    }

    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Copy failed, please select manually.', 'error');
    });
}

// Tab Switcher
function switchTab(tab) {
    currentTab = tab;
    const tabs = ['autopilot', 'radar', 'mass', 'auditor', 'upwork', 'outreach', 'closer', 'crm', 'safety'];
    tabs.forEach(t => {
        const view = document.getElementById(`view-${t}`);
        const btn = document.getElementById(`tab-btn-${t}`);
        if (view) view.classList.toggle('hidden', t !== tab);
        if (btn) {
            if (t === tab) {
                btn.className = 'nav-tab active-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 transition';
            } else {
                btn.className = 'nav-tab flex items-center space-x-2 px-4 py-2.5 text-xs sm:text-sm font-medium rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition';
            }
        }
    });

    if (tab === 'autopilot') fetchAutopilotStatus();
    if (tab === 'radar') loadRadarJobs();
    if (tab === 'mass' && currentMassLeads.length === 0) generateMassLeads();
    if (tab === 'auditor') loadAgencies();
    if (tab === 'outreach') loadLinkedInProspects();
    if (tab === 'crm') loadCrmLeads();
    if (tab === 'safety') loadSafetyShield();

    if (window.lucide) lucide.createIcons();
}

// ----------------------------------------------------
// TAB 0: 100% HANDS-FREE AUTO-PILOT
// ----------------------------------------------------
let autopilotTimer = null;
let isAutopilotRunning = false;

async function fetchAutopilotStatus() {
    try {
        const [apRes, daemonRes] = await Promise.all([
            fetch('api/autopilot.php?action=status'),
            fetch('api/daemon.php?action=status')
        ]);
        const apData = await apRes.json();
        const daemonData = await daemonRes.json();

        let merged = (apData.status === 'success' && apData.data) ? apData.data : {};
        if (daemonData.status === 'success' && daemonData.data) {
            const d = daemonData.data;
            merged.daemon_running = d.is_running;
            merged.uptime_hours = d.uptime_hours;
            merged.cycle_count = d.cycle_count;
            merged.current_country = d.current_country;
            merged.current_category = d.current_category;
            merged.total_leads_in_crm = d.total_leads_in_crm;
            if (d.recent_logs && d.recent_logs.length > 0) {
                merged.logs = d.recent_logs;
            }
        }
        updateAutopilotUI(merged);
    } catch (e) {
        console.error(e);
    }
}

function updateAutopilotUI(data) {
    const isDaemonActive = !!data.daemon_running || !!data.is_running;
    isAutopilotRunning = isDaemonActive;

    // Header Button
    const headerBtn = document.getElementById('btn-autopilot-header');
    const headerLabel = document.getElementById('autopilot-header-label');
    const headerDot = document.getElementById('autopilot-dot');

    if (isDaemonActive) {
        headerBtn.className = 'px-3 py-1.5 rounded-lg border text-xs font-semibold flex items-center space-x-1.5 transition bg-emerald-500/20 text-emerald-400 border-emerald-500/40 shadow-lg shadow-emerald-500/20';
        headerLabel.innerText = '🟢 24/7 Daemon: ACTIVE';
        headerDot.className = 'w-2 h-2 rounded-full bg-emerald-400 animate-ping';
    } else {
        headerBtn.className = 'px-3 py-1.5 rounded-lg border text-xs font-semibold flex items-center space-x-1.5 transition bg-slate-800 text-slate-300 border-slate-700 hover:border-emerald-500';
        headerLabel.innerText = '🤖 Auto-Pilot: OFF';
        headerDot.className = 'w-2 h-2 rounded-full bg-slate-500';
    }

    // Main View
    const badgeStatus = document.getElementById('ap-badge-status');
    const textStatus = document.getElementById('ap-text-status');
    const indicatorDot = document.getElementById('ap-indicator-dot');
    const mainBtn = document.getElementById('btn-main-autopilot');
    const btnLabel = document.getElementById('btn-ap-label');
    const btnIcon = document.getElementById('btn-ap-icon');

    if (badgeStatus && mainBtn) {
        if (isDaemonActive) {
            badgeStatus.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center space-x-1.5';
            textStatus.innerText = `🟢 24/7 AUTONOMOUS DAEMON ACTIVE (${data.current_country || 'Global'} • ${data.current_category || 'All Channels'})`;
            indicatorDot.className = 'w-2 h-2 rounded-full bg-emerald-400 animate-ping';

            mainBtn.className = 'w-full sm:w-auto px-8 py-4 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-teal-600 shadow-xl shadow-emerald-600/30 flex items-center justify-center space-x-2.5 transition transform hover:scale-[1.02]';
            btnLabel.innerText = `RUNNING 24/7 (Cycle #${data.cycle_count || 1})`;
            btnIcon.setAttribute('data-lucide', 'activity');
        } else {
            badgeStatus.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700 flex items-center space-x-1.5';
            textStatus.innerText = 'AUTO-PILOT PAUSED';
            indicatorDot.className = 'w-2 h-2 rounded-full bg-slate-500';

            mainBtn.className = 'w-full sm:w-auto px-8 py-4 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-brand-600 hover:from-emerald-500 hover:to-brand-500 shadow-xl shadow-brand-600/30 flex items-center justify-center space-x-2.5 transition transform hover:scale-[1.02]';
            btnLabel.innerText = 'START AUTO-PILOT';
            btnIcon.setAttribute('data-lucide', 'play');
        }
    }

    // Counters
    const totalEl = document.getElementById('ap-stat-total');
    if (totalEl) totalEl.innerText = data.total_leads_in_crm || data.total_dispatched || 0;

    // Logs
    const logBox = document.getElementById('autopilot-terminal-logs');
    if (logBox && data.logs && data.logs.length > 0) {
        logBox.innerHTML = data.logs.map(l => `<div>${escapeHtml(l)}</div>`).join('');
        logBox.scrollTop = logBox.scrollHeight;
    }

    if (window.lucide) lucide.createIcons();
}

async function toggleAutopilot() {
    try {
        const res = await fetch('api/autopilot.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=toggle&enable=${isAutopilotRunning ? 0 : 1}`
        });
        const data = await res.json();
        if (data.status === 'success') {
            updateAutopilotUI(data.data);
            if (data.data.is_running) {
                showToast('🚀 Auto-Pilot Engaged! Monitoring & pitching leads in background...', 'success');
                playDingSound();
            } else {
                showToast('⏸️ Auto-Pilot Paused', 'info');
            }
            loadCrmLeads();
        }
    } catch (e) {
        showToast('Auto-Pilot toggle error', 'error');
    }
}

// ----------------------------------------------------
// TAB 1: LIVE JOB RADAR
// ----------------------------------------------------
let lastKnownJobCount = 0;

async function loadRadarJobs(forceRefresh = false) {
    const filter = document.getElementById('radar-filter')?.value || 'all';
    const grid = document.getElementById('radar-jobs-grid');
    const refreshIcon = document.getElementById('radar-refresh-icon');

    if (refreshIcon) refreshIcon.classList.add('animate-spin');

    try {
        const res = await fetch(`api/radar.php?action=fetch&filter=${filter}&max_age=30&refresh=${forceRefresh ? 1 : 0}`);
        const data = await res.json();

        if (refreshIcon) refreshIcon.classList.remove('animate-spin');

        if (data.status === 'success') {
            document.getElementById('radar-last-sync').innerText = data.last_updated;
            document.getElementById('tab-radar-count').innerText = data.count;

            if (data.count > lastKnownJobCount && lastKnownJobCount > 0) {
                playDingSound();
                showToast(`🔔 ${data.count - lastKnownJobCount} new live jobs under 30 mins!`, 'success');
            }
            lastKnownJobCount = data.count;

            renderRadarJobs(data.jobs);
        }
    } catch (err) {
        if (refreshIcon) refreshIcon.classList.remove('animate-spin');
        console.error(err);
    }
}

function renderRadarJobs(jobs) {
    const grid = document.getElementById('radar-jobs-grid');
    if (!jobs || jobs.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-16 bg-dark-900 border border-slate-800 rounded-2xl p-6">
                <i data-lucide="inbox" class="w-10 h-10 mx-auto text-slate-600 mb-2"></i>
                <p class="text-sm text-slate-400">No jobs posted in the last 30 minutes for this filter.<br>The live radar is listening and will alert you with a Ding sound the moment a new post arrives!</p>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
        return;
    }

    grid.innerHTML = jobs.map(j => {
        const isUrgent = j.is_urgent == 1;
        const urgencyBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500/20 text-red-400 border border-red-500/30 pulse-urgent flex items-center space-x-1"><i data-lucide="flame" class="w-3 h-3"></i><span>${j.posted_ago}</span></span>`;

        const sourceColor = j.source.includes('Reddit') ? 'text-orange-400 bg-orange-500/10' : (j.source.includes('Google') ? 'text-emerald-400 bg-emerald-500/10' : 'text-sky-400 bg-sky-500/10');

        return `
            <div class="bg-dark-900 border ${isUrgent ? 'border-red-500/40 shadow-lg shadow-red-500/5' : 'border-slate-800'} rounded-2xl p-5 flex flex-col justify-between space-y-4 hover:border-slate-700 transition">
                <div>
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-lg ${sourceColor} border border-slate-800 flex items-center space-x-1">
                            <span>${j.source}</span>
                            <span class="text-slate-500">/</span>
                            <span>${j.channel}</span>
                        </span>
                        ${urgencyBadge}
                    </div>

                    <!-- Title -->
                    <h3 class="text-sm font-bold text-white leading-snug hover:text-emerald-400 cursor-pointer transition" onclick="window.open('${j.url}', '_blank')">
                        ${j.title}
                    </h3>

                    <!-- Description -->
                    <p class="text-xs text-slate-400 mt-2 line-clamp-3 leading-relaxed">
                        ${j.description}
                    </p>

                    <!-- Contact & Budget Details -->
                    <div class="mt-3 pt-3 border-t border-slate-800/80 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between text-slate-300">
                            <span class="text-slate-500">Est. Budget:</span>
                            <span class="font-bold text-emerald-400 font-mono">${j.budget}</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-300">
                            <span class="text-slate-500">Access:</span>
                            <span class="text-emerald-400 font-medium text-[11px] flex items-center space-x-1">
                                <i data-lucide="unlock" class="w-3 h-3"></i>
                                <span>No Login Needed</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
                    <button onclick="quickPitchFromRadar('${escapeHtml(j.title)}', '${escapeHtml(j.description)}', '${escapeHtml(j.author)}', '${escapeHtml(j.budget)}', '${escapeHtml(j.url)}')" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs py-2 rounded-xl flex items-center justify-center space-x-1.5 shadow-md shadow-emerald-600/20 transition">
                        <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                        <span>1-Click Pitch</span>
                    </button>
                    <a href="${j.url}" target="_blank" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium rounded-xl border border-slate-700 flex items-center space-x-1.5 transition" title="Open Job Directly (No Login Required)">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Open Link</span>
                    </a>
                </div>
            </div>
        `;
    }).join('');

    if (window.lucide) lucide.createIcons();
}

function quickPitchFromRadar(title, desc, client, budget, url) {
    switchTab('upwork');
    document.getElementById('upwork-input-title').value = title;
    document.getElementById('upwork-input-desc').value = desc;
    document.getElementById('upwork-input-client').value = client !== 'Anonymous' ? client : '';
    document.getElementById('upwork-input-budget').value = budget;
    generateUpworkProposal();
}

// ----------------------------------------------------
// TAB 1.5: MASS LEAD MULTIPLIER (100x SCALE)
// ----------------------------------------------------
let currentMassLeads = [];

async function generateMassLeads() {
    const category = document.getElementById('mass-filter-category')?.value || 'all';
    const country = document.getElementById('mass-filter-country')?.value || 'United States';
    const limit = document.getElementById('mass-filter-limit')?.value || '50';
    const btn = document.getElementById('btn-gen-mass');

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i><span>Generating Database...</span>`;
        if (window.lucide) lucide.createIcons();
    }

    try {
        const res = await fetch(`api/mass_scanner.php?action=generate&category=${category}&country=${encodeURIComponent(country)}&limit=${limit}`);
        const data = await res.json();

        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="sparkles" class="w-3.5 h-3.5"></i><span>Generate Mass Leads</span>`;
        }

        if (data.status === 'success' && data.leads) {
            currentMassLeads = data.leads;
            renderMassLeadsTable(data.leads);

            // Update stats
            let totalUsd = 0;
            data.leads.forEach(l => totalUsd += (l.deal_value_usd || 150));
            document.getElementById('mass-stat-potential-usd').innerText = `$${totalUsd.toLocaleString()}`;
            document.getElementById('mass-stat-potential-inr').innerText = `₹${Math.round(totalUsd * 86.5).toLocaleString('en-IN')}`;
            document.getElementById('mass-results-counter').innerText = `Generated ${data.leads.length} high-ticket opportunities in ${country}`;

            showToast(`🚀 Generated ${data.leads.length} high-ticket leads in ${country}!`, 'success');
            playDingSound();
        }
    } catch (e) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="sparkles" class="w-3.5 h-3.5"></i><span>Generate Mass Leads</span>`;
        }
        showToast('Failed to generate mass leads', 'error');
    }
    if (window.lucide) lucide.createIcons();
}

function renderMassLeadsTable(leads) {
    const tbody = document.getElementById('mass-leads-tbody');
    if (!tbody) return;

    if (!leads || leads.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-10 text-slate-500">
                    No leads generated yet. Select country and click "Generate Mass Leads" above.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = leads.map((l, idx) => {
        const flawColor = l.tech_flaw.includes('🔴') ? 'text-red-400 bg-red-500/10 border-red-500/20' : (l.tech_flaw.includes('🟠') ? 'text-amber-400 bg-amber-500/10 border-amber-500/20' : 'text-sky-400 bg-sky-500/10 border-sky-500/20');

        return `
            <tr class="hover:bg-slate-800/20 transition" id="mass-row-${idx}">
                <td class="py-3 px-4">
                    <div class="font-bold text-white text-xs">${escapeHtml(l.name)}</div>
                    <div class="text-[10px] text-slate-400 font-mono">${escapeHtml(l.niche)} • ${escapeHtml(l.rating)}</div>
                    ${l.website !== 'None (Google Profile Only)' ? `<a href="${l.website}" target="_blank" class="text-[11px] text-emerald-400 hover:underline block truncate max-w-xs">${escapeHtml(l.website)}</a>` : '<span class="text-[10px] text-red-400 font-bold">🚫 NO WEBSITE (Goldmine Opportunity)</span>'}
                </td>
                <td class="py-3 px-4">
                    <div class="text-slate-200 font-medium">${escapeHtml(l.city)}, ${escapeHtml(l.country)}</div>
                    <span class="text-[10px] px-2 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700 mt-1 inline-block">${escapeHtml(l.outreach_channel)}</span>
                </td>
                <td class="py-3 px-4 max-w-sm">
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded border ${flawColor} block mb-1 w-fit">${escapeHtml(l.tech_flaw)}</span>
                    <p class="text-slate-400 text-[11px] line-clamp-2">${escapeHtml(l.opportunity_angle)}</p>
                </td>
                <td class="py-3 px-4 font-mono">
                    <span class="text-emerald-400 font-bold">$${l.deal_value_usd}</span>
                    <span class="text-slate-500 text-[10px] block font-mono">₹${Math.round(l.deal_value_inr).toLocaleString('en-IN')}</span>
                </td>
                <td class="py-3 px-4 text-right space-y-1.5 min-w-[130px]">
                    <button id="btn-mass-dispatch-${idx}" onclick="autoDispatchSingleMassLead(${idx})" class="w-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-black px-2.5 py-1.5 rounded-lg text-[11px] font-bold flex items-center justify-center space-x-1 shadow-sm transition">
                        <i data-lucide="zap" class="w-3 h-3 fill-current"></i>
                        <span>Auto-Send Pitch</span>
                    </button>
                    <div class="flex items-center space-x-1">
                        <button onclick="copyMassPitch(${idx})" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-200 px-2 py-1 rounded-md border border-slate-700 text-[10px] font-medium flex items-center justify-center space-x-0.5 transition" title="Copy Pitch">
                            <i data-lucide="copy" class="w-2.5 h-2.5"></i>
                            <span>Pitch</span>
                        </button>
                        <button onclick="saveMassLeadSingleToCrm(${idx})" class="flex-1 bg-emerald-600/80 hover:bg-emerald-600 text-white px-2 py-1 rounded-md text-[10px] font-medium flex items-center justify-center space-x-0.5 transition" title="Save to CRM">
                            <i data-lucide="plus" class="w-2.5 h-2.5"></i>
                            <span>CRM</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    if (window.lucide) lucide.createIcons();
}

async function autoDispatchSingleMassLead(idx) {
    const lead = currentMassLeads[idx];
    if (!lead) return;

    const btn = document.getElementById(`btn-mass-dispatch-${idx}`);
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i data-lucide="loader" class="w-3 h-3 animate-spin"></i><span>Sending...</span>`;
        if (window.lucide) lucide.createIcons();
    }

    try {
        const res = await fetch('api/mass_scanner.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'dispatch_single',
                lead: lead
            })
        });

        const data = await res.json();
        if (data.status === 'success' && data.result) {
            const r = data.result;
            if (btn) {
                if (r.smtp_delivered) {
                    btn.className = "w-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1.5 rounded-lg text-[11px] font-bold flex items-center justify-center space-x-1";
                    btn.innerHTML = `<span>✅ SMTP Sent!</span>`;
                } else {
                    btn.className = "w-full bg-sky-500/20 text-sky-300 border border-sky-500/40 px-2.5 py-1.5 rounded-lg text-[11px] font-bold flex items-center justify-center space-x-1";
                    btn.innerHTML = `<span>📂 CRM Saved</span>`;
                }
            }
            showToast(`🚀 Outreach logged for ${lead.name} ($${lead.deal_value_usd})!`, 'success');
            playDingSound();
            loadCrmLeads();
        } else {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `<span>Retry</span>`;
            }
            showToast('Dispatch failed', 'error');
        }
    } catch (e) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<span>Retry</span>`;
        }
        showToast('Network error on dispatch', 'error');
    }
    if (window.lucide) lucide.createIcons();
}

let isMassAutoDispatching = false;

async function autoDispatchAllMassLeads() {
    if (!currentMassLeads || currentMassLeads.length === 0) {
        showToast('Please click "Generate Mass Leads" first!', 'error');
        return;
    }

    if (isMassAutoDispatching) {
        showToast('Auto-dispatch is already actively running...', 'info');
        return;
    }

    const progressBox = document.getElementById('mass-dispatch-progress-box');
    const progressBar = document.getElementById('mass-progress-bar');
    const progressCounts = document.getElementById('mass-progress-counts');
    const currentTargetEl = document.getElementById('mass-progress-current-target');
    const statSmtpSent = document.getElementById('mass-stat-smtp-sent');
    const statCrmSaved = document.getElementById('mass-stat-crm-saved');
    const mainBtn = document.getElementById('btn-mass-auto-dispatch');

    isMassAutoDispatching = true;
    if (mainBtn) {
        mainBtn.disabled = true;
        mainBtn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i><span>Autonomous Engine Running...</span>`;
    }

    progressBox.classList.remove('hidden');
    progressBar.style.width = '0%';
    let total = currentMassLeads.length;
    let processed = 0;
    let totalSmtpSent = 0;
    let totalCrmSaved = 0;

    statSmtpSent.innerText = '0';
    statCrmSaved.innerText = '0';
    progressCounts.innerText = `0 / ${total} Processed`;

    const chunkSize = 5;
    for (let i = 0; i < total; i += chunkSize) {
        const chunk = currentMassLeads.slice(i, i + chunkSize);
        currentTargetEl.innerText = `Target: Auditing & Pitching ${chunk.map(c => c.name).join(', ')}`;

        try {
            const res = await fetch('api/mass_scanner.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'batch_dispatch',
                    leads: chunk
                })
            });

            const data = await res.json();
            if (data.status === 'success') {
                totalSmtpSent += data.smtp_sent_count || 0;
                totalCrmSaved += data.crm_saved_count || 0;
                statSmtpSent.innerText = totalSmtpSent;
                statCrmSaved.innerText = totalCrmSaved;

                // Update individual buttons in the table
                chunk.forEach((l, chunkIdx) => {
                    const rowIdx = i + chunkIdx;
                    const rowBtn = document.getElementById(`btn-mass-dispatch-${rowIdx}`);
                    if (rowBtn) {
                        const leadRes = data.results && data.results[chunkIdx];
                        if (leadRes && leadRes.smtp_delivered) {
                            rowBtn.className = "w-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1.5 rounded-lg text-[11px] font-bold flex items-center justify-center space-x-1";
                            rowBtn.innerHTML = `<span>✅ SMTP Sent!</span>`;
                        } else {
                            rowBtn.className = "w-full bg-sky-500/20 text-sky-300 border border-sky-500/40 px-2.5 py-1.5 rounded-lg text-[11px] font-bold flex items-center justify-center space-x-1";
                            rowBtn.innerHTML = `<span>📂 CRM Saved</span>`;
                        }
                    }
                });
            }
        } catch (e) {
            console.error('Batch error:', e);
        }

        processed += chunk.length;
        const pct = Math.round((processed / total) * 100);
        progressBar.style.width = `${pct}%`;
        progressCounts.innerText = `${processed} / ${total} Processed (${pct}%)`;

        // Safe human pacing delay (300ms)
        await new Promise(r => setTimeout(r, 300));
    }

    isMassAutoDispatching = false;
    currentTargetEl.innerText = `✅ Completed! All ${total} opportunities processed autonomously.`;
    if (mainBtn) {
        mainBtn.disabled = false;
        mainBtn.innerHTML = `<i data-lucide="check-circle" class="w-4 h-4 text-emerald-300"></i><span>100% Batch Completed!</span>`;
    }

    showToast(`🎉 100% Batch Complete! ${totalSmtpSent} Real SMTP sent, ${totalCrmSaved} added to CRM!`, 'success');
    playDingSound();
    loadCrmLeads();

    setTimeout(() => {
        if (mainBtn && !isMassAutoLoopRunning) {
            mainBtn.innerHTML = `<i data-lucide="zap" class="w-4 h-4 fill-current"></i><span>⚡ 1-Click Auto-Dispatch ALL</span>`;
            if (window.lucide) lucide.createIcons();
        }
    }, 6000);
}

// ----------------------------------------------------
// INFINITE 24/7 AUTO-LOOP CONTROLLER
// ----------------------------------------------------
let isMassAutoLoopRunning = false;
let massAutoLoopTimer = null;
const massCountriesList = ['United States', 'United Kingdom', 'Canada', 'Australia'];
const massCategoriesList = ['all', 'ecommerce', 'no_website', 'agency'];
let massCountryPointer = 0;
let massCategoryPointer = 0;

function toggleMassAutoLoop() {
    isMassAutoLoopRunning = !isMassAutoLoopRunning;
    const badge = document.getElementById('mass-autoloop-badge');
    const label = document.getElementById('mass-autoloop-label');
    const btn = document.getElementById('btn-mass-autoloop');

    if (isMassAutoLoopRunning) {
        if (badge) badge.classList.remove('hidden');
        if (label) label.innerText = '⏸️ Stop Infinite Auto-Loop';
        if (btn) btn.className = 'bg-gradient-to-r from-red-600 to-rose-600 text-white font-extrabold text-xs px-4 py-2 rounded-xl shadow-lg shadow-red-500/20 flex items-center space-x-1.5 transition';
        showToast('🔄 24/7 Infinite Auto-Loop Started! Generating, pitching & rotating non-stop...', 'success');
        playDingSound();
        runNextMassAutoLoopCycle();
    } else {
        if (badge) badge.classList.add('hidden');
        if (label) label.innerText = '🔄 Turn ON Infinite Auto-Loop';
        if (btn) btn.className = 'bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white font-extrabold text-xs px-4 py-2 rounded-xl shadow-lg shadow-indigo-500/20 flex items-center space-x-1.5 transition transform hover:scale-[1.02]';
        if (massAutoLoopTimer) clearTimeout(massAutoLoopTimer);
        showToast('⏸️ Infinite Auto-Loop Paused', 'info');
    }
    if (window.lucide) lucide.createIcons();
}

async function runNextMassAutoLoopCycle() {
    if (!isMassAutoLoopRunning) return;

    // Rotate Country and Category
    const nextCountry = massCountriesList[massCountryPointer % massCountriesList.length];
    const nextCat = massCategoriesList[massCategoryPointer % massCategoriesList.length];
    massCountryPointer++;
    massCategoryPointer++;

    const countrySelect = document.getElementById('mass-filter-country');
    const catSelect = document.getElementById('mass-filter-category');
    if (countrySelect) countrySelect.value = nextCountry;
    if (catSelect) catSelect.value = nextCat;

    showToast(`🔄 [Auto-Loop] Generating next batch for ${nextCountry} (${nextCat})...`, 'info');
    await generateMassLeads();
    
    // Auto-dispatch all leads
    await autoDispatchAllMassLeads();

    // Schedule next cycle in 15 seconds
    if (isMassAutoLoopRunning) {
        showToast(`⏳ [Auto-Loop] Batch complete. Next automated country rotation in 15s...`, 'info');
        massAutoLoopTimer = setTimeout(() => {
            runNextMassAutoLoopCycle();
        }, 15000);
    }
}

function copyMassPitch(idx) {
    const lead = currentMassLeads[idx];
    if (!lead) return;
    copyToClipboard(lead.ready_pitch);
    showToast(`Copied winning pitch for ${lead.name}!`, 'success');
}

async function saveMassLeadSingleToCrm(idx) {
    const lead = currentMassLeads[idx];
    if (!lead) return;

    try {
        await fetch('api/pipeline.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'add',
                title: `${lead.name} (${lead.tech_flaw.replace(/[🔴🟠🟡]/g, '').trim()})`,
                platform: lead.outreach_channel.split('/')[0].trim(),
                status: 'new',
                deal_value_usd: lead.deal_value_usd,
                client_name: lead.name,
                notes: `Opportunity: ${lead.opportunity_angle}\nPitch:\n${lead.ready_pitch}`
            })
        });
        showToast(`Saved ${lead.name} ($${lead.deal_value_usd}) to CRM pipeline!`, 'success');
        loadCrmLeads();
    } catch (e) {
        showToast('Failed to save to CRM', 'error');
    }
}

async function addAllMassLeadsToCrm() {
    if (!currentMassLeads || currentMassLeads.length === 0) {
        showToast('Please generate leads first', 'error');
        return;
    }

    showToast(`Adding ${currentMassLeads.length} leads to CRM...`, 'info');

    for (const lead of currentMassLeads.slice(0, 50)) {
        try {
            await fetch('api/pipeline.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'add',
                    title: `${lead.name} (${lead.niche})`,
                    platform: lead.outreach_channel.split('/')[0].trim(),
                    status: 'new',
                    deal_value_usd: lead.deal_value_usd,
                    client_name: lead.name,
                    notes: `Opportunity: ${lead.opportunity_angle}\nPitch:\n${lead.ready_pitch}`
                })
            });
        } catch (e) {}
    }

    loadCrmLeads();
    showToast(`Batch added to ₹50k Goal CRM!`, 'success');
    playDingSound();
}

function exportMassLeadsCsv() {
    if (!currentMassLeads || currentMassLeads.length === 0) {
        showToast('Please generate leads first before exporting', 'error');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,ID,Name,Type,Country,City,Niche,Website,Rating,Tech_Flaw,Outreach_Channel,Deal_USD,Deal_INR,Pre_Written_Pitch\n";

    currentMassLeads.forEach(l => {
        const cleanPitch = (l.ready_pitch || '').replace(/"/g, '""').replace(/\n/g, ' ');
        const row = [
            l.id,
            `"${(l.name || '').replace(/"/g, '""')}"`,
            `"${(l.type || '').replace(/"/g, '""')}"`,
            `"${(l.country || '').replace(/"/g, '""')}"`,
            `"${(l.city || '').replace(/"/g, '""')}"`,
            `"${(l.niche || '').replace(/"/g, '""')}"`,
            `"${(l.website || '').replace(/"/g, '""')}"`,
            `"${(l.rating || '').replace(/"/g, '""')}"`,
            `"${(l.tech_flaw || '').replace(/"/g, '""')}"`,
            `"${(l.outreach_channel || '').replace(/"/g, '""')}"`,
            l.deal_value_usd || 150,
            l.deal_value_inr || 12975,
            `"${cleanPitch}"`
        ].join(',');
        csvContent += row + "\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `LeadForge_Mass_Campaign_${currentMassLeads.length}_Leads.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast(`CSV with ${currentMassLeads.length} leads exported!`, 'success');
}

// ----------------------------------------------------
// TAB 2: BUG HUNTER & WEBSITES
// ----------------------------------------------------
async function runSiteAudit(prefillUrl = null) {
    const urlInput = document.getElementById('audit-target-url');
    const url = prefillUrl || urlInput.value.trim();
    if (!url) {
        showToast('Please enter a website URL to audit', 'error');
        return;
    }

    const btn = document.getElementById('btn-run-audit');
    const resultsBox = document.getElementById('audit-results-box');

    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i><span>Auditing website...</span>`;
    resultsBox.classList.remove('hidden');
    resultsBox.innerHTML = `
        <div class="text-center py-8 text-slate-400 space-y-2">
            <i data-lucide="loader" class="w-8 h-8 mx-auto animate-spin text-emerald-400"></i>
            <p class="text-xs">Scanning SSL, server response speed, Laravel debug flags, and security headers...</p>
        </div>
    `;
    if (window.lucide) lucide.createIcons();

    try {
        const res = await fetch('api/audit.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ url })
        });
        const data = await res.json();

        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="activity" class="w-4 h-4"></i><span>Scan & Generate Pitch</span>`;

        if (data.status === 'success' && data.data) {
            renderAuditReport(data.data);
        } else {
            resultsBox.innerHTML = `
                <div class="bg-red-500/10 border border-red-500/30 p-4 rounded-xl text-red-300 text-xs">
                    ${data.message || 'Audit failed. Target site might be down.'}
                </div>
            `;
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="activity" class="w-4 h-4"></i><span>Scan & Generate Pitch</span>`;
        resultsBox.innerHTML = `<div class="text-red-400 text-xs">Network error during audit.</div>`;
    }
    if (window.lucide) lucide.createIcons();
}

function renderAuditReport(report) {
    const resultsBox = document.getElementById('audit-results-box');
    const techBadges = report.tech_stack.map(t => `<span class="px-2 py-0.5 bg-slate-800 text-slate-200 border border-slate-700 rounded-md text-[11px] font-mono">${t}</span>`).join(' ');

    const issuesHtml = report.issues.map(iss => {
        const severityColor = iss.severity === 'Critical' ? 'text-red-400 bg-red-500/10 border-red-500/30' : (iss.severity === 'High' ? 'text-orange-400 bg-orange-500/10 border-orange-500/30' : 'text-amber-400 bg-amber-500/10 border-amber-500/30');
        return `
            <div class="bg-dark-950 border border-slate-800 p-3.5 rounded-xl space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-white">${iss.title}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border ${severityColor}">${iss.severity}</span>
                </div>
                <p class="text-xs text-slate-400">${iss.detail}</p>
                <p class="text-xs text-emerald-400 font-mono"><span class="text-slate-500">Fix:</span> ${iss.solution}</p>
            </div>
        `;
    }).join('');

    resultsBox.innerHTML = `
        <div class="space-y-4">
            <!-- Metrics Summary -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-dark-950 border border-slate-800 p-3 rounded-xl">
                    <span class="text-[11px] text-slate-400">Target Host:</span>
                    <p class="text-xs font-bold text-white truncate">${report.domain}</p>
                </div>
                <div class="bg-dark-950 border border-slate-800 p-3 rounded-xl">
                    <span class="text-[11px] text-slate-400">Response Speed:</span>
                    <p class="text-xs font-bold text-emerald-400 font-mono">${report.response_time}</p>
                </div>
                <div class="bg-dark-950 border border-slate-800 p-3 rounded-xl">
                    <span class="text-[11px] text-slate-400">HTTP Status:</span>
                    <p class="text-xs font-bold text-sky-400 font-mono">${report.http_code} OK</p>
                </div>
                <div class="bg-dark-950 border border-slate-800 p-3 rounded-xl">
                    <span class="text-[11px] text-slate-400">Actionable Opportunities:</span>
                    <p class="text-xs font-bold text-amber-400">${report.total_issues_found} Found</p>
                </div>
            </div>

            <!-- Detected Tech -->
            <div class="flex items-center space-x-2 text-xs">
                <span class="text-slate-400">Detected Tech Stack:</span>
                <div class="flex flex-wrap gap-1">${techBadges}</div>
            </div>

            <!-- Issues Found -->
            <div class="space-y-2">
                <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Technical Observations:</h4>
                <div class="space-y-2">${issuesHtml}</div>
            </div>

            <!-- 1-Click Value Pitch Generated -->
            <div class="bg-emerald-500/10 border border-emerald-500/20 p-4 rounded-xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-300 flex items-center space-x-1.5">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span>Zero-Competition Direct Pitch (Ready to Send)</span>
                    </span>
                    <button onclick="copyToClipboard('audit-generated-pitch')" class="text-xs bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-3 py-1 rounded-lg transition">
                        Copy Pitch
                    </button>
                </div>
                <textarea id="audit-generated-pitch" rows="5" class="w-full bg-dark-950 border border-slate-700 text-slate-200 text-xs rounded-lg p-3 font-mono focus:outline-none">${report.pitch_preview.email_body}</textarea>
            </div>
        </div>
    `;

    if (window.lucide) lucide.createIcons();
}

async function loadAgencies() {
    const country = document.getElementById('agency-country-filter')?.value || 'all';
    const container = document.getElementById('agencies-table-container');

    try {
        const res = await fetch(`api/agency.php?country=${country}`);
        const data = await res.json();

        if (data.status === 'success' && data.agencies) {
            container.innerHTML = `
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-800/50 text-slate-400 border-b border-slate-800 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Agency / Location</th>
                            <th class="py-3 px-4">Focus & Size</th>
                            <th class="py-3 px-4">Identified Dev Gap</th>
                            <th class="py-3 px-4 text-right">Outreach Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/50">
                        ${data.agencies.map(a => `
                            <tr class="hover:bg-slate-800/20 transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white">${a.name}</div>
                                    <a href="${a.website}" target="_blank" class="text-emerald-400 hover:underline text-[11px]">${a.website.replace('https://www.', '')}</a>
                                    <div class="text-slate-500 text-[10px]">${a.city} (${a.country})</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-slate-200">${a.category}</div>
                                    <div class="text-slate-500 text-[11px]">${a.size}</div>
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <p class="text-slate-300 line-clamp-2">${a.tech_gap}</p>
                                    <span class="text-emerald-400/80 text-[10px] block mt-0.5">Angle: ${a.outreach_angle}</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button onclick="auditAgencyDirect('${a.website}', '${a.name}')" class="bg-slate-800 hover:bg-emerald-600 text-slate-200 hover:text-white px-3 py-1.5 rounded-lg border border-slate-700 text-xs font-medium transition">
                                        Scan & Pitch
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
    } catch (e) {
        console.error(e);
    }
}

function auditAgencyDirect(website, name) {
    document.getElementById('audit-target-url').value = website;
    runSiteAudit(website);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ----------------------------------------------------
// TAB 3: UPWORK PROPOSAL STUDIO
// ----------------------------------------------------
let lastGeneratedProposal = null;

async function generateUpworkProposal() {
    const title = document.getElementById('upwork-input-title').value.trim();
    const desc = document.getElementById('upwork-input-desc').value.trim();
    const client = document.getElementById('upwork-input-client').value.trim();
    const budget = document.getElementById('upwork-input-budget').value.trim();

    if (!title && !desc) {
        showToast('Please enter a job title or description', 'error');
        return;
    }

    const btn = document.getElementById('btn-gen-upwork');
    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4 animate-spin"></i><span>Analyzing Requirements & Writing Fix...</span>`;
    if (window.lucide) lucide.createIcons();

    try {
        const res = await fetch('api/generate.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                type: 'upwork',
                job_title: title,
                job_description: desc,
                client_name: client,
                budget: budget
            })
        });

        const data = await res.json();
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i><span>Generate Winning Proposal & Code Fix</span>`;

        if (data.status === 'success' && data.data) {
            lastGeneratedProposal = {
                title: title || 'Upwork Job',
                client: client,
                budget: budget || '$100',
                proposal: data.data.proposal
            };

            document.getElementById('upwork-output-empty').classList.add('hidden');
            document.getElementById('upwork-output-content').classList.remove('hidden');
            document.getElementById('upwork-output-actions').classList.remove('hidden');

            document.getElementById('upwork-out-hook').innerText = data.data.golden_hook;
            document.getElementById('upwork-out-full').value = data.data.proposal;
            document.getElementById('upwork-out-hint').innerText = data.data.reverse_search_hint || 'Check past Upwork review names.';
            document.getElementById('proposal-word-count').innerText = `${data.data.word_count || 100} words`;

            showToast('Proposal & Code Snippet Generated!', 'success');
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i><span>Generate Winning Proposal</span>`;
        showToast('Generation failed. Please try again.', 'error');
    }
    if (window.lucide) lucide.createIcons();
}

function saveProposalToCrm() {
    if (!lastGeneratedProposal) return;
    openAddLeadModal({
        title: lastGeneratedProposal.title,
        platform: 'Upwork',
        client_name: lastGeneratedProposal.client,
        deal_value_usd: parseInt(lastGeneratedProposal.budget.replace(/[^0-9]/g, '')) || 100,
        notes: lastGeneratedProposal.proposal.substring(0, 150) + '...'
    });
}

// ----------------------------------------------------
// TAB 4: COLD OUTREACH STUDIO
// ----------------------------------------------------
function selectOutreachTemplate(type) {
    selectedOutreachType = type;
    document.querySelectorAll('.outreach-card').forEach(c => {
        c.classList.remove('border-emerald-500', 'border-2');
        c.classList.add('border-slate-800');
    });
    event.currentTarget.classList.add('border-emerald-500', 'border-2');
    event.currentTarget.classList.remove('border-slate-800');
    generateCustomOutreach();
}

async function generateCustomOutreach() {
    const client = document.getElementById('outreach-client-name').value.trim() || 'Alex';
    const company = document.getElementById('outreach-company-name').value.trim() || 'your agency';
    const website = document.getElementById('outreach-website').value.trim() || company;

    try {
        const res = await fetch('api/generate.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                type: 'linkedin',
                client_name: client,
                company: company,
                website: website,
                job_title: `${selectedOutreachType} outreach`
            })
        });

        const data = await res.json();
        if (data.status === 'success' && data.data) {
            document.getElementById('outreach-linkedin-text').value = data.data.connection_note || `Hey ${client}, saw your work at ${company}. I specialize in on-demand Laravel & backend sprints for agencies. Thought I'd connect in case your dev team ever needs extra overflow capacity!`;
            document.getElementById('outreach-email-text').value = data.data.proposal;
            showToast('Outreach templates formatted!', 'success');
        }
    } catch (e) {
        console.error(e);
    }
}

// ----------------------------------------------------
// LINKEDIN SAFE AUTO-CONNECTOR
// ----------------------------------------------------
let currentLinkedInProspects = [];

async function loadLinkedInProspects() {
    const container = document.getElementById('linkedin-prospects-container');
    if (!container) return;

    try {
        const res = await fetch('api/linkedin_helper.php?action=list');
        const data = await res.json();

        if (data.status === 'success') {
            currentLinkedInProspects = data.prospects || [];
            
            // Update Quota display
            const quotaDisplay = document.getElementById('li-quota-display');
            if (quotaDisplay && data.stats) {
                quotaDisplay.innerText = `${data.stats.sent_today} / ${data.stats.daily_limit} Sent`;
            }

            renderLinkedInProspects(currentLinkedInProspects);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderLinkedInProspects(prospects) {
    const container = document.getElementById('linkedin-prospects-container');
    if (!container) return;

    if (!prospects || prospects.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-8 text-slate-500">
                No LinkedIn prospects loaded.
            </div>
        `;
        return;
    }

    container.innerHTML = prospects.map((p, idx) => {
        return `
            <div class="bg-dark-950 border border-slate-800 rounded-xl p-4 flex flex-col justify-between space-y-3 hover:border-slate-700 transition">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-xs font-bold text-white">${escapeHtml(p.name)}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-sky-500/10 text-sky-400 border border-sky-500/20 font-mono">Verified Lead</span>
                    </div>
                    <div class="text-[11px] text-slate-400">${escapeHtml(p.role)} • <strong class="text-slate-300">${escapeHtml(p.company)}</strong></div>
                    <div class="text-[10px] text-slate-500 mb-2">${escapeHtml(p.location)} • ${escapeHtml(p.niche)}</div>

                    <div class="bg-dark-900 border border-slate-800 rounded-lg p-2.5 text-[11px] font-mono text-slate-300 relative group">
                        <span class="text-[10px] font-bold text-slate-500 block uppercase mb-1">Tailored Connection Note (&lt;300 chars):</span>
                        <p id="li-note-${idx}" class="line-clamp-3">${escapeHtml(p.connection_note)}</p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between gap-2">
                    <button onclick="copyToClipboard('li-note-${idx}')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs py-2 rounded-lg border border-slate-700 flex items-center justify-center space-x-1 transition">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span>Copy Note</span>
                    </button>
                    <button onclick="launchLinkedInConnect(${idx})" class="flex-1 bg-gradient-to-r from-sky-600 to-blue-600 hover:from-sky-500 hover:to-blue-500 text-white text-xs font-semibold py-2 rounded-lg shadow-sm flex items-center justify-center space-x-1 transition">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Connect</span>
                    </button>
                </div>
            </div>
        `;
    }).join('');

    if (window.lucide) lucide.createIcons();
}

async function launchLinkedInConnect(idx) {
    const p = currentLinkedInProspects[idx];
    if (!p) return;

    // 1. Copy note automatically
    navigator.clipboard.writeText(p.connection_note);
    showToast(`Copied note for ${p.name}! Opening LinkedIn...`, 'success');
    playDingSound();

    // 2. Open LinkedIn in Chrome
    window.open(p.profile_url, '_blank');

    // 3. Log to backend and CRM
    try {
        await fetch('api/linkedin_helper.php?action=log_connection', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                name: p.name,
                company: p.company,
                role: p.role,
                note: p.connection_note,
                deal_usd: 200
            })
        });
        loadLinkedInProspects();
        loadCrmLeads();
    } catch (e) {
        console.error(e);
    }
}

async function autoConnectAllLinkedIn() {
    const btn = document.getElementById('btn-li-auto-all');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i><span>Auto-Connecting...</span>`;
        if (window.lucide) lucide.createIcons();
    }

    try {
        const res = await fetch('api/linkedin_helper.php?action=auto_connect_all', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast(`🤖 ${data.message}`, 'success');
            playDingSound();
            loadLinkedInProspects();
            loadCrmLeads();
        } else {
            showToast(data.message || 'Auto-connect limit reached', 'info');
        }
    } catch (e) {
        showToast('Auto-connect request failed', 'error');
    }

    if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="bot" class="w-4 h-4"></i><span>🤖 1-Click Auto-Connect ALL</span>`;
        if (window.lucide) lucide.createIcons();
    }
}

// ----------------------------------------------------
// TAB 4.5: AI DEAL CLOSER & NEGOTIATION ASSISTANT
// ----------------------------------------------------
let lastCloserResult = null;

async function generateDealCloser() {
    const msg = document.getElementById('closer-client-msg').value.trim();
    const service = document.getElementById('closer-service-type').value;
    const dealSize = parseFloat(document.getElementById('closer-deal-size').value) || 150;
    const client = document.getElementById('closer-client-name').value.trim();
    const platform = document.getElementById('closer-platform').value;

    if (!msg) {
        showToast('Please paste the client message or inquiry first', 'error');
        return;
    }

    const btn = document.getElementById('btn-generate-closer');
    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4 animate-spin"></i><span>Analyzing Intent & Formulating Strategy...</span>`;
    if (window.lucide) lucide.createIcons();

    try {
        const res = await fetch('api/chat_closer.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                client_message: msg,
                service_type: service,
                target_price_usd: dealSize,
                client_name: client
            })
        });

        const data = await res.json();
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i><span>Analyze & Craft Closing Response</span>`;

        if (data.status === 'success' && data.data) {
            const d = data.data;
            lastCloserResult = {
                client_name: client,
                platform: platform,
                service_type: service,
                deal_size: dealSize,
                reply: d.ready_reply
            };

            document.getElementById('closer-output-empty').classList.add('hidden');
            document.getElementById('closer-output-content').classList.remove('hidden');
            document.getElementById('closer-output-actions').classList.remove('hidden');

            document.getElementById('closer-intent-badge').innerText = d.detected_intent;
            document.getElementById('closer-price-badge').innerText = d.recommended_price;
            document.getElementById('closer-strategy-text').innerText = d.closing_strategy;
            document.getElementById('closer-reply-text').value = d.ready_reply;
            document.getElementById('closer-word-count').innerText = `${d.word_count || 0} words`;

            showToast('Winning tactical reply generated!', 'success');
            playDingSound();
        } else {
            showToast(data.message || 'Analysis failed', 'error');
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i><span>Analyze & Craft Closing Response</span>`;
        showToast('Deal Closer error', 'error');
    }
    if (window.lucide) lucide.createIcons();
}

function saveCloserLeadToCrm() {
    if (!lastCloserResult) return;
    openAddLeadModal({
        title: `${lastCloserResult.service_type} (${lastCloserResult.client_name || 'Client'})`,
        platform: lastCloserResult.platform,
        client_name: lastCloserResult.client_name || 'Client',
        deal_value_usd: lastCloserResult.deal_size,
        notes: lastCloserResult.reply.substring(0, 160) + '...'
    });
}

// ----------------------------------------------------
// TAB 5: CRM & ₹50k GOAL TRACKER
// ----------------------------------------------------
let currentLeadsCache = [];

async function loadCrmLeads() {
    const statusFilter = document.getElementById('crm-filter-status')?.value || 'all';

    try {
        const res = await fetch(`api/pipeline.php?action=list&status=${statusFilter}`);
        const data = await res.json();

        if (data.status === 'success') {
            const stats = data.stats;
            currentLeadsCache = data.leads || [];

            // Update Goal Card
            document.getElementById('crm-current-inr').innerText = Number(stats.total_won_inr).toLocaleString('en-IN');
            document.getElementById('crm-current-usd').innerText = Number(stats.total_won_usd).toLocaleString();
            document.getElementById('crm-goal-percent').innerText = `${stats.progress_percentage}%`;
            document.getElementById('nav-goal-progress').innerText = `${stats.progress_percentage}%`;
            document.getElementById('crm-goal-bar').style.width = `${Math.min(100, stats.progress_percentage)}%`;

            const gapInr = Math.max(0, stats.monthly_goal_inr - stats.total_won_inr);
            const gapUsd = Math.round(gapInr / 86.5);
            document.getElementById('crm-gap-inr').innerText = gapInr.toLocaleString('en-IN');
            document.getElementById('crm-gap-usd').innerText = gapUsd.toLocaleString();

            renderCrmTable(data.leads);
            renderOutboxTable(data.leads);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderCrmTable(leads) {
    const tbody = document.getElementById('crm-leads-tbody');
    if (!leads || leads.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-8 text-slate-500">
                    No deals yet. Add a lead from the button above or save one directly from the Job Radar!
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = leads.map(l => {
        const statusBadge = l.status === 'won' 
            ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">🏆 WON</span>`
            : (l.status === 'discussing' 
                ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-sky-500/20 text-sky-400 border border-sky-500/30">💬 Discussing</span>`
                : (l.status === 'contacted'
                    ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-500/20 text-amber-400 border border-amber-500/30">✉️ Contacted</span>`
                    : `<span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-400">🆕 New</span>`));

        return `
            <tr class="hover:bg-slate-800/20 transition">
                <td class="py-3 px-4">
                    <div class="font-bold text-white">${l.title}</div>
                    <div class="text-[10px] text-slate-500">${l.created_at}</div>
                </td>
                <td class="py-3 px-4">
                    <span class="text-slate-300 font-mono text-[11px]">${l.platform}</span>
                </td>
                <td class="py-3 px-4 text-slate-300">
                    ${l.client_name || 'Prospect'}
                </td>
                <td class="py-3 px-4 font-mono">
                    <span class="text-emerald-400 font-bold">$${l.deal_value_usd}</span>
                    <span class="text-slate-500 text-[10px]"> (₹${Math.round(l.deal_value_inr || l.deal_value_usd * 86.5).toLocaleString('en-IN')})</span>
                </td>
                <td class="py-3 px-4">
                    ${statusBadge}
                </td>
                <td class="py-3 px-4 text-right space-x-1">
                    <button onclick="updateLeadStatusPrompt(${l.id}, '${l.status}', ${l.deal_value_usd})" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded text-[11px] border border-slate-700">
                        Update
                    </button>
                    <button onclick="deleteLead(${l.id})" class="px-2 py-1 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded text-[11px] border border-red-500/20">
                        ✕
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    if (window.lucide) lucide.createIcons();
}

function renderOutboxTable(leads) {
    const tbody = document.getElementById('autopilot-outbox-tbody');
    if (!tbody) return;

    // Filter leads that have been pitched/contacted
    const sentLeads = (leads || []).filter(l => l.status === 'contacted' || l.status === 'won' || (l.pitch_sent && l.pitch_sent.length > 0));

    if (sentLeads.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-8 text-slate-500">
                    No autonomous pitches dispatched yet. Click "START AUTO-PILOT" above to begin dispatching!
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = sentLeads.map(l => {
        const companyName = l.company || l.client_name || l.title;
        const targetUrl = l.url || '#';

        return `
            <tr class="hover:bg-slate-800/20 transition">
                <td class="py-3 px-4">
                    <div class="font-bold text-white">${escapeHtml(companyName)}</div>
                    ${targetUrl !== '#' ? `<a href="${targetUrl}" target="_blank" class="text-[11px] text-emerald-400 hover:underline truncate block max-w-xs">${escapeHtml(targetUrl.replace('https://', ''))}</a>` : ''}
                </td>
                <td class="py-3 px-4">
                    <span class="px-2 py-0.5 bg-slate-800 text-slate-300 border border-slate-700 rounded text-[11px] font-mono">${l.platform || 'Email'}</span>
                </td>
                <td class="py-3 px-4 font-mono">
                    <span class="text-emerald-400 font-bold">$${l.deal_value_usd || 150}</span>
                    <span class="text-slate-500 text-[10px]"> (₹${Math.round(l.deal_value_inr || (l.deal_value_usd || 150) * 86.5).toLocaleString('en-IN')})</span>
                </td>
                <td class="py-3 px-4 text-slate-400 font-mono text-[11px]">
                    ${l.created_at || 'Just now'}
                </td>
                <td class="py-3 px-4">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center space-x-1 w-fit">
                        <i data-lucide="check-check" class="w-3 h-3"></i>
                        <span>100% Dispatched</span>
                    </span>
                </td>
                <td class="py-3 px-4 text-right">
                    <button onclick="viewSentPitch(${l.id})" class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs px-3 py-1.5 rounded-lg shadow-sm transition flex items-center space-x-1 ml-auto">
                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                        <span>View Pitch</span>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    if (window.lucide) lucide.createIcons();
}

function viewSentPitch(leadId) {
    const lead = currentLeadsCache.find(l => Number(l.id) === Number(leadId));
    if (!lead) return;

    document.getElementById('modal-pitch-title').innerText = lead.company || lead.title || 'Client Opportunity';
    document.getElementById('modal-pitch-meta').innerText = `Dispatched: ${lead.created_at} | Platform: ${lead.platform} | Value: $${lead.deal_value_usd}`;
    
    // Set pitch text
    const pitchText = lead.pitch_sent || lead.notes || "Hey,\n\nI noticed an optimization opportunity on your website...\n\nBest regards,\nJay";
    document.getElementById('modal-pitch-content').value = pitchText;

    const linkEl = document.getElementById('modal-pitch-link');
    if (lead.url && lead.url !== '#') {
        linkEl.href = lead.url;
        linkEl.classList.remove('hidden');
    } else {
        linkEl.classList.add('hidden');
    }

    document.getElementById('modal-view-sent-pitch').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function closeSentPitchModal() {
    document.getElementById('modal-view-sent-pitch').classList.add('hidden');
}

function exportSentLeadsCsv() {
    if (!currentLeadsCache || currentLeadsCache.length === 0) {
        showToast('No sent leads to export yet', 'info');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,ID,Title,Company,Website,Platform,Status,Deal_USD,Deal_INR,Sent_At,Pitch_Sent\n";

    currentLeadsCache.forEach(l => {
        const cleanPitch = (l.pitch_sent || l.notes || '').replace(/"/g, '""').replace(/\n/g, ' ');
        const row = [
            l.id,
            `"${(l.title || '').replace(/"/g, '""')}"`,
            `"${(l.company || '').replace(/"/g, '""')}"`,
            `"${(l.url || '').replace(/"/g, '""')}"`,
            `"${l.platform || ''}"`,
            `"${l.status || ''}"`,
            l.deal_value_usd || 0,
            l.deal_value_inr || 0,
            `"${l.created_at || ''}"`,
            `"${cleanPitch}"`
        ].join(',');
        csvContent += row + "\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `LeadForge_Sent_Applications_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast('CSV Report Downloaded!', 'success');
}

function openAddLeadModal(prefill = null) {
    document.getElementById('modal-add-lead').classList.remove('hidden');
    if (prefill) {
        document.getElementById('modal-lead-title').value = prefill.title || '';
        document.getElementById('modal-lead-platform').value = prefill.platform || 'Upwork';
        document.getElementById('modal-lead-client').value = prefill.client_name || '';
        document.getElementById('modal-lead-usd').value = prefill.deal_value_usd || 100;
        document.getElementById('modal-lead-notes').value = prefill.notes || '';
    }
}

function closeAddLeadModal() {
    document.getElementById('modal-add-lead').classList.add('hidden');
}

async function submitNewLead() {
    const title = document.getElementById('modal-lead-title').value.trim();
    const platform = document.getElementById('modal-lead-platform').value;
    const status = document.getElementById('modal-lead-status').value;
    const usd = parseFloat(document.getElementById('modal-lead-usd').value) || 50;
    const client = document.getElementById('modal-lead-client').value.trim();
    const notes = document.getElementById('modal-lead-notes').value.trim();

    if (!title) {
        showToast('Please enter a deal title', 'error');
        return;
    }

    try {
        const res = await fetch('api/pipeline.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'add',
                title: title,
                platform: platform,
                status: status,
                deal_value_usd: usd,
                client_name: client,
                notes: notes
            })
        });
        const data = await res.json();
        if (data.status === 'success') {
            closeAddLeadModal();
            loadCrmLeads();
            showToast('Deal saved to pipeline!', 'success');
        }
    } catch (e) {
        showToast('Failed to save deal', 'error');
    }
}

async function updateLeadStatusPrompt(id, currentStatus, currentUsd) {
    const newStatus = prompt("Update status to (new, contacted, discussing, won):", currentStatus);
    if (!newStatus) return;

    let dealUsd = currentUsd;
    if (newStatus.toLowerCase() === 'won') {
        const val = prompt("Enter final deal value earned in $ USD:", currentUsd);
        if (val) dealUsd = parseFloat(val);
    }

    try {
        await fetch('api/pipeline.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'update_status',
                id: id,
                status: newStatus.toLowerCase(),
                deal_value_usd: dealUsd
            })
        });
        loadCrmLeads();
        showToast('Deal status updated!', 'success');
    } catch (e) {
        showToast('Update failed', 'error');
    }
}

async function deleteLead(id) {
    if (!confirm('Are you sure you want to delete this lead?')) return;
    try {
        await fetch(`api/pipeline.php?action=delete&id=${id}`);
        loadCrmLeads();
        showToast('Lead deleted', 'info');
    } catch (e) {
        showToast('Delete failed', 'error');
    }
}

// ----------------------------------------------------
// TAB 6: ANTI-BAN SHIELD & SETTINGS
// ----------------------------------------------------
async function loadSafetyShield() {
    try {
        const [pipeRes, setRes] = await Promise.all([
            fetch('api/pipeline.php?action=list'),
            fetch('api/settings.php')
        ]);
        const pipeData = await pipeRes.json();
        const setData = await setRes.json();

        if (pipeData.status === 'success') {
            const quota = pipeData.stats.daily_quota;
            
            // LinkedIn
            document.getElementById('shield-linkedin-ratio').innerText = `${quota.linkedin.used} / ${quota.linkedin.limit}`;
            document.getElementById('shield-linkedin-bar').style.width = `${Math.min(100, (quota.linkedin.used / quota.linkedin.limit) * 100)}%`;

            // Email
            document.getElementById('shield-email-ratio').innerText = `${quota.email.used} / ${quota.email.limit}`;
            document.getElementById('shield-email-bar').style.width = `${Math.min(100, (quota.email.used / quota.email.limit) * 100)}%`;

            // Upwork
            document.getElementById('shield-upwork-ratio').innerText = `${quota.upwork.used} / ${quota.upwork.limit}`;
            document.getElementById('shield-upwork-bar').style.width = `${Math.min(100, (quota.upwork.used / quota.upwork.limit) * 100)}%`;
        }

        if (setData.status === 'success') {
            const s = setData.settings;
            document.getElementById('settings-name').value = s.user_name || 'Jay';
            document.getElementById('settings-title').value = s.title || 'Laravel & Full-Stack Web Developer';
            document.getElementById('settings-goal').value = s.monthly_goal_inr || 50000;
            if (s.gemini_api_key_masked) {
                document.getElementById('settings-gemini-key').placeholder = `Active (${s.gemini_api_key_masked})`;
            }

            // Populate SMTP settings
            document.getElementById('settings-smtp-host').value = s.smtp_host || 'smtp.gmail.com';
            document.getElementById('settings-smtp-port').value = s.smtp_port || 587;
            document.getElementById('settings-smtp-user').value = s.smtp_user || '';
            if (s.smtp_pass_masked) {
                document.getElementById('settings-smtp-pass').placeholder = s.smtp_pass_masked;
            }
            document.getElementById('settings-smtp-from-name').value = s.smtp_from_name || 'Jay | Web & SEO Specialist';
            document.getElementById('settings-smtp-from-email').value = s.smtp_from_email || (s.smtp_user || '');
        }
    } catch (e) {
        console.error(e);
    }
}

async function saveSettings() {
    const name = document.getElementById('settings-name').value.trim();
    const title = document.getElementById('settings-title').value.trim();
    const goal = parseFloat(document.getElementById('settings-goal').value) || 50000;
    const geminiKey = document.getElementById('settings-gemini-key').value.trim();

    const smtpHost = document.getElementById('settings-smtp-host').value.trim();
    const smtpPort = parseInt(document.getElementById('settings-smtp-port').value) || 587;
    const smtpUser = document.getElementById('settings-smtp-user').value.trim();
    const smtpPass = document.getElementById('settings-smtp-pass').value.trim();
    const smtpFromName = document.getElementById('settings-smtp-from-name').value.trim();
    const smtpFromEmail = document.getElementById('settings-smtp-from-email').value.trim();

    try {
        const payload = {
            user_name: name,
            title: title,
            monthly_goal_inr: goal,
            gemini_api_key: geminiKey,
            smtp_host: smtpHost,
            smtp_port: smtpPort,
            smtp_user: smtpUser,
            smtp_from_name: smtpFromName,
            smtp_from_email: smtpFromEmail
        };

        if (smtpPass) {
            payload.smtp_pass = smtpPass;
        }

        const res = await fetch('api/settings.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('Profile & SMTP Configuration Saved!', 'success');
            loadCrmLeads();
            loadSafetyShield();
            playDingSound();
        } else {
            showToast(data.message || 'Failed to save settings', 'error');
        }
    } catch (e) {
        showToast('Failed to save settings', 'error');
    }
}

async function sendTestEmail() {
    let testEmail = document.getElementById('settings-test-to').value.trim();
    if (!testEmail) {
        testEmail = document.getElementById('settings-smtp-user').value.trim();
    }

    if (!testEmail) {
        showToast('Please enter an email address to send the test message to', 'error');
        return;
    }

    const btn = document.getElementById('btn-test-smtp');
    const resultBox = document.getElementById('smtp-test-result');
    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i><span>Connecting via TLS Socket...</span>`;
    if (window.lucide) lucide.createIcons();

    resultBox.classList.add('hidden');
    resultBox.className = 'text-xs p-3 rounded-lg border font-mono';

    try {
        const res = await fetch('api/settings.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'test_smtp',
                test_email: testEmail
            })
        });

        const data = await res.json();
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="mail-forward" class="w-3.5 h-3.5"></i><span>Send Test Email</span>`;

        resultBox.classList.remove('hidden');
        if (data.status === 'success') {
            resultBox.classList.add('bg-emerald-500/10', 'border-emerald-500/30', 'text-emerald-300');
            resultBox.innerHTML = `✅ <strong>SMTP SUCCESS:</strong> Test email delivered directly to <code>${escapeHtml(testEmail)}</code>!<br><span class="text-slate-400 text-[11px]">${escapeHtml(data.message)}</span>`;
            showToast('Test email delivered successfully!', 'success');
            playDingSound();
        } else {
            resultBox.classList.add('bg-red-500/10', 'border-red-500/30', 'text-red-300');
            resultBox.innerHTML = `❌ <strong>SMTP DELIVERY NOTICE:</strong> ${escapeHtml(data.message)}<br><span class="text-slate-400 text-[11px]">Tip: Check your Gmail App Password and ensure 2-Step Verification is active.</span>`;
            showToast('SMTP Test Delivery issue: ' + data.message, 'error');
            playErrorAlarmSound();
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="mail-forward" class="w-3.5 h-3.5"></i><span>Send Test Email</span>`;
        resultBox.classList.remove('hidden');
        resultBox.classList.add('bg-red-500/10', 'border-red-500/30', 'text-red-300');
        resultBox.innerText = 'Network error while attempting SMTP test transmission.';
        showToast('Test failed', 'error');
    }
    if (window.lucide) lucide.createIcons();
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// Initial Boot
document.addEventListener('DOMContentLoaded', () => {
    switchTab('autopilot');
    fetchAutopilotStatus();
    loadRadarJobs();
    loadCrmLeads();

    // Auto-refresh live radar every 35 seconds
    radarAutoTimer = setInterval(() => {
        if (currentTab === 'radar') {
            loadRadarJobs();
        }
    }, 35000);

    // Auto-Pilot continuous execution loop (every 40 seconds)
    setInterval(() => {
        if (isAutopilotRunning) {
            fetch('api/autopilot.php?action=trigger_cycle')
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success' && d.data) {
                        updateAutopilotUI(d.data);
                        loadCrmLeads();
                    }
                })
                .catch(err => console.error('Auto-pilot cycle err:', err));
        } else if (currentTab === 'autopilot') {
            fetchAutopilotStatus();
        }
    }, 40000);

    if (window.lucide) lucide.createIcons();
});
