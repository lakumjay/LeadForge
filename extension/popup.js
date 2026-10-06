/**
 * LeadForge AI — Extension Popup Handler
 */

document.addEventListener("DOMContentLoaded", async () => {
  const toggleAutopilot = document.getElementById("toggle-autopilot");
  const statusPill = document.getElementById("status-pill");
  const statViews = document.getElementById("stat-views");
  const statComments = document.getElementById("stat-comments");
  const serverUrlInput = document.getElementById("server-url-input");
  const btnTriggerNow = document.getElementById("btn-trigger-now");
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

  // Manual Trigger Action
  btnTriggerNow.addEventListener("click", async () => {
    btnTriggerNow.innerText = "⏳ Processing task...";
    btnTriggerNow.disabled = true;

    chrome.runtime.sendMessage({
      action: "manual_trigger_task",
      serverUrl: serverUrlInput.value.trim()
    }, (res) => {
      setTimeout(() => {
        btnTriggerNow.innerText = "🚀 Run Warm-Up Action Now";
        btnTriggerNow.disabled = false;
        // Refresh counts
        chrome.storage.local.get(["dailyViewsCount", "dailyCommentsCount"], (data) => {
          statViews.innerText = data.dailyViewsCount || 0;
          statComments.innerText = data.dailyCommentsCount || 0;
        });
      }, 3000);
    });
  });

  // Open Dashboard
  btnOpenDashboard.addEventListener("click", () => {
    const url = (serverUrlInput.value.trim() || "https://leadsflow.snwebkarma.in") + "/linkedin.php";
    chrome.tabs.create({ url: url });
  });

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
