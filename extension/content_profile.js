/**
 * LeadForge AI — LinkedIn Profile View Warm-Up Content Script
 * Simulates human-like scrolling & engagement to trigger "Jay viewed your profile" notification.
 */

(function () {
  console.log("⚡ LeadForge AI: Profile Warm-Up script active on:", window.location.href);

  // Anti-bot detection random delays
  const initialDelay = Math.floor(Math.random() * 2000) + 1500;

  setTimeout(() => {
    // Step 1: Smooth scroll down to About / Experience
    window.scrollBy({ top: 450, behavior: "smooth" });

    setTimeout(() => {
      // Step 2: Smooth scroll down to Activity / Recommendations
      window.scrollBy({ top: 600, behavior: "smooth" });

      setTimeout(() => {
        // Step 3: Gentle scroll back up
        window.scrollBy({ top: -300, behavior: "smooth" });

        setTimeout(() => {
          console.log("✅ LeadForge AI: Profile View Warm-Up registered!");
          // Notify background service worker that profile view is complete
          try {
            chrome.runtime.sendMessage({ action: "profile_view_complete" });
          } catch (e) {}
        }, 3000);

      }, 4000);

    }, 3500);

  }, initialDelay);
})();
