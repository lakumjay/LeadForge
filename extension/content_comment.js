/**
 * LeadForge AI — LinkedIn Automated Post Comment Content Script
 * Finds the post comment box, inputs the AI Authority comment & submits.
 */

(async function () {
  const data = await chrome.storage.local.get("pendingComment");
  if (!data || !data.pendingComment || !data.pendingComment.commentText) return;

  const commentText = data.pendingComment.commentText;
  console.log("⚡ LeadForge AI: Injecting comment on post:", commentText.slice(0, 50) + "...");

  setTimeout(async () => {
    // Look for comment box or comment trigger button
    let commentBox = document.querySelector('.ql-editor[contenteditable="true"]') ||
                     document.querySelector('div[contenteditable="true"][role="textbox"]') ||
                     document.querySelector('textarea[name="message"]');

    if (!commentBox) {
      // Try to click "Comment" action button on post to open editor
      const commentBtn = document.querySelector('button[aria-label*="Comment"], button.comment-button');
      if (commentBtn) {
        commentBtn.click();
        await new Promise(r => setTimeout(r, 1200));
        commentBox = document.querySelector('.ql-editor[contenteditable="true"]') ||
                     document.querySelector('div[contenteditable="true"][role="textbox"]');
      }
    }

    if (commentBox) {
      commentBox.focus();
      // Insert text
      document.execCommand("insertText", false, commentText);

      // Trigger input events
      commentBox.dispatchEvent(new Event("input", { bubbles: true }));
      commentBox.dispatchEvent(new Event("change", { bubbles: true }));

      console.log("✍️ Comment inserted into box. Submitting in 2 seconds...");

      setTimeout(() => {
        // Look for the Submit/Post button
        const submitBtn = document.querySelector('button.comments-comment-box__submit-button') ||
                          document.querySelector('button.comments-comment-box__submit-button--cr') ||
                          document.querySelector('button[type="submit"]');

        if (submitBtn && !submitBtn.disabled) {
          submitBtn.click();
          console.log("🚀 Comment submitted successfully!");
        }

        // Clear pending comment and notify background
        chrome.storage.local.remove("pendingComment");
        setTimeout(() => {
          chrome.runtime.sendMessage({ action: "post_comment_complete" });
        }, 1500);

      }, 2000);
    }
  }, 3000);
})();
