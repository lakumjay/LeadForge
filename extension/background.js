/**
 * LeadForge AI — LinkedIn Extension Background Service Worker
 * Handles 24/7 background tab rotation, profile warm-up views & automated comments.
 */

const DEFAULT_SERVER_URL = "https://leadsflow.snwebkarma.in";

// Initialize state
chrome.runtime.onInstalled.addListener(() => {
  chrome.storage.local.get(["serverUrl", "autoPilotEnabled", "dailyViewsCount", "dailyCommentsCount", "lastResetDate"], (res) => {
    const today = new Date().toISOString().slice(0, 10);
    const updates = {};
    if (!res.serverUrl) updates.serverUrl = DEFAULT_SERVER_URL;
    if (res.autoPilotEnabled === undefined) updates.autoPilotEnabled = true;
    if (res.lastResetDate !== today) {
      updates.dailyViewsCount = 0;
      updates.dailyCommentsCount = 0;
      updates.lastResetDate = today;
    }
    chrome.storage.local.set(updates);
  });

  // Setup periodic alarm for auto-pilot tasks (every 1 minute)
  chrome.alarms.create("leadforge_autopilot_tick", { periodInMinutes: 1 });
});

// Alarm Listener
chrome.alarms.onAlarm.addListener(async (alarm) => {
  if (alarm.name === "leadforge_autopilot_tick") {
    const { autoPilotEnabled, serverUrl } = await chrome.storage.local.get(["autoPilotEnabled", "serverUrl"]);
    if (autoPilotEnabled) {
      await processNextLeadForgeTask(serverUrl || DEFAULT_SERVER_URL);
    }
  }
});

// Message Listener from Popup and Content Scripts
chrome.runtime.onMessage.addListener((req, sender, sendResponse) => {
  if (req.action === "manual_trigger_task") {
    processNextLeadForgeTask(req.serverUrl || DEFAULT_SERVER_URL).then(sendResponse);
    return true;
  }
  if (req.action === "profile_view_complete") {
    if (sender.tab && sender.tab.id) {
      setTimeout(() => {
        chrome.tabs.remove(sender.tab.id).catch(() => {});
      }, 2000);
    }
    // Increment local stats
    chrome.storage.local.get(["dailyViewsCount"], (data) => {
      chrome.storage.local.set({ dailyViewsCount: (data.dailyViewsCount || 0) + 1 });
    });
    sendResponse({ ok: true });
    return true;
  }
  if (req.action === "post_comment_complete") {
    if (sender.tab && sender.tab.id) {
      setTimeout(() => {
        chrome.tabs.remove(sender.tab.id).catch(() => {});
      }, 3000);
    }
    chrome.storage.local.get(["dailyCommentsCount"], (data) => {
      chrome.storage.local.set({ dailyCommentsCount: (data.dailyCommentsCount || 0) + 1 });
    });
    sendResponse({ ok: true });
    return true;
  }
});

let isTaskRunning = false;

async function processNextLeadForgeTask(serverUrl) {
  if (isTaskRunning) return { ok: false, error: "Task already in progress" };
  isTaskRunning = true;

  try {
    const apiUrl = `${serverUrl.replace(/\/+$/, '')}/api/linkedin_ai_engine.php?action=get_extension_task`;
    const res = await fetch(apiUrl, { cache: "no-store" });
    const task = await res.json();

    if (!task || !task.ok || !task.task_type) {
      isTaskRunning = false;
      return { ok: false, message: "No tasks ready" };
    }

    if (task.task_type === "profile_view" && task.url) {
      // Open background tab to view profile
      const tab = await chrome.tabs.create({ url: task.url, active: false });
      
      // Notify backend that view was initiated
      fetch(`${serverUrl.replace(/\/+$/, '')}/api/linkedin_ai_engine.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "log_extension_view_success",
          prospect_id: task.prospect_id,
          name: task.name,
          company: task.company
        })
      }).catch(() => {});

      // Fallback close after 20 seconds
      setTimeout(() => {
        if (tab && tab.id) {
          chrome.tabs.remove(tab.id).catch(() => {});
        }
        isTaskRunning = false;
      }, 20000);

      return { ok: true, task: "profile_view", target: task.name };
    }

    if (task.task_type === "post_comment" && task.post_url) {
      const tab = await chrome.tabs.create({ url: task.post_url, active: false });
      
      // Store pending comment info for content script
      await chrome.storage.local.set({
        pendingComment: {
          tabId: tab.id,
          commentText: task.comment_text,
          author: task.author
        }
      });

      setTimeout(() => {
        if (tab && tab.id) {
          chrome.tabs.remove(tab.id).catch(() => {});
        }
        isTaskRunning = false;
      }, 25000);

      return { ok: true, task: "post_comment", author: task.author };
    }

  } catch (err) {
    console.error("LeadForge extension task error:", err);
  } finally {
    setTimeout(() => { isTaskRunning = false; }, 5000);
  }

  return { ok: false };
}
