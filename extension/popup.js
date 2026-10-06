/**
 * LeadForge AI — Extension Popup Handler (Synchronized with Server)
 */

document.addEventListener("DOMContentLoaded", async () => {
  const toggleAutopilot = document.getElementById("toggle-autopilot");
  const statusPill = document.getElementById("status-pill");
  const statViews = document.getElementById("stat-views");
  const statComments = document.getElementById("stat-comments");
  const serverUrlInput = document.getElementById("server-url-input");
  const btnTriggerView = document.getElementById("btn-trigger-view");
  const btnTriggerComment = document.getElementById("btn-trigger-comment");
  const btnOpenDashboard = document.getElementById("btn-open-dashboard");

  // Load saved settings
  const state = await chrome.storage.local.get([
    "autoPilotEnabled",
    "dailyViewsCount",
    "dailyCommentsCount",
    "serverUrl"
  ]);

  if (state.autoPilotEnabled !== undefined) {
    toggleAutopilot.checked = state.autoPilotEnabled;
    updateStatusPill(state.autoPilotEnabled);
  }
  if (state.dailyViewsCount !== undefined) {
    statViews.innerText = state.dailyViewsCount;
  }
  if (state.dailyCommentsCount !== undefined) {
    statComments.innerText = state.dailyCommentsCount;
  }
  if (state.serverUrl) {
    serverUrlInput.value = state.serverUrl;
  }

  // Fetch live stats directly from server endpoint to ensure 100% sync
  const endpoint = (serverUrlInput.value.trim() || "https://leadsflow.snwebkarma.in").replace(/\/+$/, '');
  try {
    const res = await fetch(`${endpoint}/api/linkedin_ai_engine.php?action=get_today_summary`);
    const summary = await res.json();
    if (summary.ok && summary.counts) {
      statViews.innerText = summary.counts.warmups || state.dailyViewsCount || 0;
      statComments.innerText = summary.counts.comments || state.dailyCommentsCount || 0;
      chrome.storage.local.set({
        dailyViewsCount: summary.counts.warmups || 0,
        dailyCommentsCount: summary.counts.comments || 0
      });
    }
  } catch (e) {}

  // Toggle Auto-Pilot
  toggleAutopilot.addEventListener("change", (e) => {
    const isEnabled = e.target.checked;
    chrome.storage.local.set({ autoPilotEnabled: isEnabled });
    updateStatusPill(isEnabled);
  });

  // Server URL update
  serverUrlInput.addEventListener("change", (e) => {
    chrome.storage.local.set({ serverUrl: e.target.value.trim() });
  });

  // Trigger Profile View Warm-Up
  btnTriggerView.addEventListener("click", async () => {
    btnTriggerView.innerText = "⏳ Viewing profile...";
    btnTriggerView.disabled = true;

    chrome.runtime.sendMessage({
      action: "manual_trigger_task",
      task_type: "profile_view",
      serverUrl: serverUrlInput.value.trim()
    }, () => {
      setTimeout(refreshCounts, 4000);
    });
  });

  // Trigger Post Comment
  btnTriggerComment.addEventListener("click", async () => {
    btnTriggerComment.innerText = "⏳ Generating & posting comment...";
    btnTriggerComment.disabled = true;

    chrome.runtime.sendMessage({
      action: "manual_trigger_task",
      task_type: "post_comment",
      serverUrl: serverUrlInput.value.trim()
    }, () => {
      setTimeout(refreshCounts, 5000);
    });
  });

  // Open Dashboard
  btnOpenDashboard.addEventListener("click", () => {
    const url = (serverUrlInput.value.trim() || "https://leadsflow.snwebkarma.in") + "/linkedin.php";
    chrome.tabs.create({ url: url });
  });

  async function refreshCounts() {
    btnTriggerView.innerText = "🚀 Run Profile Warm-Up Now";
    btnTriggerView.disabled = false;
    btnTriggerComment.innerText = "💬 Post 1-Click AI Comment Now";
    btnTriggerComment.disabled = false;

    try {
      const ep = (serverUrlInput.value.trim() || "https://leadsflow.snwebkarma.in").replace(/\/+$/, '');
      const res = await fetch(`${ep}/api/linkedin_ai_engine.php?action=get_today_summary`);
      const summary = await res.json();
      if (summary.ok && summary.counts) {
        statViews.innerText = summary.counts.warmups || 0;
        statComments.innerText = summary.counts.comments || 0;
      }
    } catch (e) {
      chrome.storage.local.get(["dailyViewsCount", "dailyCommentsCount"], (data) => {
        statViews.innerText = data.dailyViewsCount || 0;
        statComments.innerText = data.dailyCommentsCount || 0;
      });
    }
  }

  function updateStatusPill(active) {
    if (active) {
      statusPill.className = "status-badge status-on";
      statusPill.innerText = "● AUTO ACTIVE";
    } else {
      statusPill.className = "status-badge status-off";
      statusPill.innerText = "● PAUSED";
    }
  }
});
