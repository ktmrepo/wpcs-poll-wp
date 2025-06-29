/**
 * WPCS Poll Public JavaScript - Enhanced TikTok Style
 */

class WPCSPollContainer {
  constructor(container) {
    this.container = container;
    this.currentIndex = 0;
    this.polls = [];
    this.touchStartX = 0;
    this.touchEndX = 0;
    this.isAnimating = false;
    this.autoAdvanceTimer = null;
    this.countdownTimer = null;
    this.countdownSeconds = 5;
    this.userHasVoted = false;

    this.init();
  }

  init() {
    this.loadPolls();
    this.bindEvents();
    this.setupKeyboardNavigation();
  }

  loadPolls() {
    const category = this.container.dataset.category || "all";
    const limit = this.container.dataset.limit || 10;

    // Show loading state
    this.showLoading();

    // Add debug logging
    console.log('WPCS Poll Debug: Loading polls from:', `${wpcs_poll_ajax.rest_url}polls?category=${category}&limit=${limit}`);

    fetch(`${wpcs_poll_ajax.rest_url}polls?category=${category}&limit=${limit}`)
      .then((response) => {
        console.log('WPCS Poll Debug: Response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((polls) => {
        console.log('WPCS Poll Debug: Raw polls data:', polls);
        this.polls = polls || [];
        this.renderPolls();
      })
      .catch((error) => {
        console.error("Error loading polls:", error);
        this.showError(error.message);
      });
  }

  showLoading() {
    this.container.innerHTML = `
      <div class="wpcs-polls-loading">
        <div class="loading-spinner"></div>
        <p>Loading polls...</p>
      </div>
    `;
  }

  showError(errorMessage = '') {
    this.container.innerHTML = `
      <div class="wpcs-polls-error">
        <h3>Failed to Load Polls</h3>
        <p>We couldn't load the polls right now. ${errorMessage ? `Error: ${errorMessage}` : 'Please try again.'}</p>
        <button class="retry-btn" onclick="this.parentNode.parentNode.wpcsContainer.loadPolls()">Retry</button>
      </div>
    `;
    
    // Store reference for retry
    this.container.wpcsContainer = this;
  }

  renderPolls() {
    if (!this.polls || this.polls.length === 0) {
      this.container.innerHTML = `
        <div class="no-polls-message">
          <h3>No Polls Available</h3>
          <p>There are no polls to display at the moment. Check back later!</p>
        </div>
      `;
      return;
    }

    const pollsHTML = this.polls
      .map(
        (poll, index) => `
            <div class="wpcs-poll-card ${index === 0 ? "active" : ""}" 
                 data-poll-id="${poll.id}" 
                 data-index="${index}">
                <div class="poll-content">
                    <h3 class="poll-title">${this.escapeHtml(poll.title)}</h3>
                    ${
                      poll.description
                        ? `<p class="poll-description">${this.escapeHtml(poll.description)}</p>`
                        : ""
                    }
                    <div class="poll-options">
                        ${this.renderPollOptions(poll)}
                    </div>
                    <div class="poll-meta">
                        <span class="poll-category">${this.escapeHtml(poll.category)}</span>
                        <span class="poll-votes">${this.getTotalVotes(poll)} votes</span>
                    </div>
                </div>
                <div class="auto-advance-timer" style="display: none;">
                  <div class="timer-circle">
                    <div class="timer-text">5</div>
                    <svg class="timer-progress" width="60" height="60">
                      <circle cx="30" cy="30" r="25" stroke="#ffffff40" stroke-width="3" fill="none"/>
                      <circle cx="30" cy="30" r="25" stroke="#ffffff" stroke-width="3" fill="none" 
                              stroke-dasharray="157" stroke-dashoffset="157" class="progress-circle"/>
                    </svg>
                  </div>
                  <div class="timer-label">Next poll in</div>
                </div>
            </div>
        `
      )
      .join("");

    this.container.innerHTML = `
            <div class="wpcs-polls-wrapper">
                ${pollsHTML}
                ${this.container.dataset.showNavigation === 'true' ? `
                <div class="poll-navigation">
                    <button class="nav-btn prev-btn" aria-label="Previous poll">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/>
                        </svg>
                    </button>
                    <button class="nav-btn next-btn" aria-label="Next poll">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                        </svg>
                    </button>
                </div>
                <div class="poll-indicators">
                    ${this.polls
                      .map(
                        (_, index) => `
                        <span class="indicator ${
                          index === 0 ? "active" : ""
                        }" data-index="${index}"></span>
                    `
                      )
                      .join("")}
                </div>
                ` : ''}
            </div>
        `;

    // Setup autoplay if enabled
    if (this.container.dataset.autoplay === 'true') {
      this.setupAutoplay();
    }
  }

  renderPollOptions(poll) {
    console.log('WPCS Poll Debug: Rendering options for poll:', poll.id, 'Options type:', typeof poll.options, 'Options:', poll.options);
    
    // Handle different formats of poll.options - NO JSON PARSING
    let options = [];
    
    if (Array.isArray(poll.options)) {
      // Already an array - use directly
      options = poll.options;
      console.log('WPCS Poll Debug: Options is already an array');
    } else if (poll.options && typeof poll.options === 'object') {
      // It's an object, convert to array
      options = Object.values(poll.options);
      console.log('WPCS Poll Debug: Options is object, converted to array');
    } else if (typeof poll.options === 'string') {
      // Only try JSON parsing if it's a string
      try {
        const parsed = JSON.parse(poll.options);
        if (Array.isArray(parsed)) {
          options = parsed;
        } else if (parsed && typeof parsed === 'object') {
          options = Object.values(parsed);
        }
        console.log('WPCS Poll Debug: Successfully parsed JSON string');
      } catch (e) {
        console.error('WPCS Poll Debug: Failed to parse options JSON:', e, 'Raw options:', poll.options);
        options = [];
      }
    } else {
      console.warn('WPCS Poll Debug: Unknown options format:', typeof poll.options, poll.options);
      options = [];
    }

    console.log('WPCS Poll Debug: Final processed options:', options);

    const totalVotes = this.getTotalVotes(poll);
    const userVote = poll.user_vote;
    const hasVoted = userVote !== null && userVote !== undefined;

    if (!Array.isArray(options) || options.length === 0) {
      console.warn('WPCS Poll Debug: No valid options found for poll', poll.id);
      return '<p class="no-options">No options available</p>';
    }

    return options
      .map((option, index) => {
        console.log('WPCS Poll Debug: Processing option:', index, option);
        
        // Handle different option formats with more robust checking
        const optionId = option.id || option.option_id || `option_${index + 1}`;
        const optionText = option.text || option.option_text || option.title || option.name || `Option ${index + 1}`;
        const voteCount = parseInt(option.votes || option.vote_count || option.count || 0);
        
        const percentage = totalVotes > 0 ? (voteCount / totalVotes) * 100 : 0;
        const isSelected = userVote === optionId;
        const showResults = hasVoted;

        console.log('WPCS Poll Debug: Option processed:', {
          id: optionId,
          text: optionText,
          votes: voteCount,
          percentage: percentage,
          isSelected: isSelected,
          showResults: showResults
        });

        return `
                <div class="poll-option ${isSelected ? "selected user-voted" : ""} ${showResults ? "show-results" : ""}" 
                     data-option-id="${optionId}" 
                     onclick="wpcsVoteOnPoll(${poll.id}, '${optionId}', this)">
                    <div class="option-content">
                        <span class="option-text">${this.escapeHtml(optionText)}</span>
                        ${showResults ? `<span class="option-percentage">${percentage.toFixed(1)}%</span>` : ''}
                        ${isSelected ? '<span class="vote-indicator">✓</span>' : ''}
                    </div>
                    ${showResults ? `
                    <div class="option-progress">
                        <div class="progress-bar" style="width: ${percentage}%"></div>
                    </div>
                    <div class="option-votes">${voteCount} votes</div>
                    ` : ''}
                </div>
            `;
      })
      .join("");
  }

  escapeHtml(text) {
    if (!text) return '';
    const map = {
      '&': '&',
      '<': '<',
      '>': '>',
      '"': '"',
      "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
  }

  setupAutoplay() {
    // Auto-advance is now triggered after voting, not on a timer
    console.log('WPCS Poll Debug: Autoplay enabled - will advance after voting');
  }

  startAutoAdvanceTimer() {
    this.clearTimers();
    this.countdownSeconds = 5;
    this.userHasVoted = true;
    
    const currentCard = this.container.querySelector(`[data-index="${this.currentIndex}"]`);
    const timerElement = currentCard?.querySelector('.auto-advance-timer');
    const timerText = currentCard?.querySelector('.timer-text');
    const progressCircle = currentCard?.querySelector('.progress-circle');
    
    if (timerElement && timerText && progressCircle) {
      timerElement.style.display = 'block';
      
      // Start countdown
      this.countdownTimer = setInterval(() => {
        this.countdownSeconds--;
        timerText.textContent = this.countdownSeconds;
        
        // Update progress circle
        const progress = ((5 - this.countdownSeconds) / 5) * 157;
        progressCircle.style.strokeDashoffset = 157 - progress;
        
        if (this.countdownSeconds <= 0) {
          this.clearTimers();
          timerElement.style.display = 'none';
          this.nextPoll();
        }
      }, 1000);
    }
  }

  clearTimers() {
    if (this.autoAdvanceTimer) {
      clearTimeout(this.autoAdvanceTimer);
      this.autoAdvanceTimer = null;
    }
    if (this.countdownTimer) {
      clearInterval(this.countdownTimer);
      this.countdownTimer = null;
    }
    
    // Hide timer display
    const timerElements = this.container.querySelectorAll('.auto-advance-timer');
    timerElements.forEach(timer => timer.style.display = 'none');
  }

  bindEvents() {
    // Touch events for mobile swiping (horizontal)
    this.container.addEventListener("touchstart", (e) => {
      this.touchStartX = e.touches[0].clientX;
    });

    this.container.addEventListener("touchend", (e) => {
      this.touchEndX = e.changedTouches[0].clientX;
      this.handleSwipe();
    });

    // Navigation buttons
    this.container.addEventListener("click", (e) => {
      if (e.target.closest(".prev-btn")) {
        this.clearTimers();
        this.previousPoll();
      } else if (e.target.closest(".next-btn")) {
        this.clearTimers();
        this.nextPoll();
      } else if (e.target.closest(".indicator")) {
        const index = parseInt(e.target.dataset.index);
        this.clearTimers();
        this.goToPoll(index);
      }
    });
  }

  setupKeyboardNavigation() {
    document.addEventListener("keydown", (e) => {
      if (!this.container.matches(":hover")) return;

      switch (e.key) {
        case "ArrowLeft":
          e.preventDefault();
          this.clearTimers();
          this.previousPoll();
          break;
        case "ArrowRight":
          e.preventDefault();
          this.clearTimers();
          this.nextPoll();
          break;
        case " ":
          e.preventDefault();
          this.clearTimers();
          this.nextPoll();
          break;
      }
    });
  }

  handleSwipe() {
    const swipeThreshold = 50;
    const diff = this.touchStartX - this.touchEndX;

    if (Math.abs(diff) > swipeThreshold) {
      this.clearTimers();
      if (diff > 0) {
        // Swiped left - next poll
        this.nextPoll();
      } else {
        // Swiped right - previous poll
        this.previousPoll();
      }
    }
  }

  nextPoll() {
    if (this.isAnimating || this.polls.length <= 1) return;
    const nextIndex = (this.currentIndex + 1) % this.polls.length;
    this.goToPoll(nextIndex);
  }

  previousPoll() {
    if (this.isAnimating || this.polls.length <= 1) return;
    const prevIndex =
      this.currentIndex === 0 ? this.polls.length - 1 : this.currentIndex - 1;
    this.goToPoll(prevIndex);
  }

  goToPoll(index) {
    if (this.isAnimating || index === this.currentIndex || !this.polls[index]) return;

    this.isAnimating = true;
    this.userHasVoted = false;
    
    const currentCard = this.container.querySelector(
      `[data-index="${this.currentIndex}"]`
    );
    const nextCard = this.container.querySelector(`[data-index="${index}"]`);

    // Slide animation for horizontal movement
    if (currentCard) {
      currentCard.classList.remove("active");
      currentCard.style.transform = index > this.currentIndex ? 'translateX(-100%)' : 'translateX(100%)';
    }
    
    if (nextCard) {
      nextCard.style.transform = index > this.currentIndex ? 'translateX(100%)' : 'translateX(-100%)';
      nextCard.classList.add("active");
      
      // Animate to center
      setTimeout(() => {
        nextCard.style.transform = 'translateX(0)';
      }, 50);
    }

    // Update indicators
    this.container.querySelectorAll(".indicator").forEach((indicator, i) => {
      indicator.classList.toggle("active", i === index);
    });

    this.currentIndex = index;

    setTimeout(() => {
      this.isAnimating = false;
      // Reset transform for non-active cards
      this.container.querySelectorAll('.wpcs-poll-card:not(.active)').forEach(card => {
        card.style.transform = '';
      });
    }, 300);
  }

  getTotalVotes(poll) {
    let options = [];
    
    if (Array.isArray(poll.options)) {
      options = poll.options;
    } else if (poll.options && typeof poll.options === 'object') {
      options = Object.values(poll.options);
    } else if (typeof poll.options === 'string') {
      try {
        const parsed = JSON.parse(poll.options);
        options = Array.isArray(parsed) ? parsed : Object.values(parsed || {});
      } catch (e) {
        options = [];
      }
    }

    if (!Array.isArray(options)) {
      return 0;
    }

    return options.reduce((total, option) => {
      const votes = parseInt(option.votes || option.vote_count || option.count || 0);
      return total + votes;
    }, 0);
  }

  // Method to trigger auto-advance after voting
  onUserVoted() {
    if (this.container.dataset.autoplay === 'true' && !this.userHasVoted) {
      this.startAutoAdvanceTimer();
    }
  }
}

// Enhanced global voting function with better error handling
window.wpcsVoteOnPoll = function (pollId, optionId, optionElement) {
  console.log('WPCS Poll Debug: Voting on poll', pollId, 'option', optionId);
  
  // Enhanced debugging for AJAX object
  console.log('WPCS Poll Debug: wpcs_poll_ajax object:', wpcs_poll_ajax);
  console.log('WPCS Poll Debug: AJAX URL:', wpcs_poll_ajax?.ajax_url);
  console.log('WPCS Poll Debug: Nonce:', wpcs_poll_ajax?.nonce);
  
  // Check if AJAX object exists
  if (!wpcs_poll_ajax) {
    console.error('WPCS Poll Debug: wpcs_poll_ajax object not found');
    showNotification("Configuration error: AJAX object not found", 'error');
    return;
  }

  // Check if nonce exists
  if (!wpcs_poll_ajax.nonce) {
    console.error('WPCS Poll Debug: Nonce not found in wpcs_poll_ajax');
    showNotification("Security token missing. Please refresh the page.", 'error');
    return;
  }

  // Check if user already voted on this poll
  const pollCard = document.querySelector(`[data-poll-id="${pollId}"]`);
  if (pollCard && pollCard.querySelector('.poll-option.user-voted')) {
    showNotification("You have already voted on this poll", 'info');
    return;
  }

  // Disable all options temporarily to prevent double-clicking
  const allOptions = pollCard?.querySelectorAll('.poll-option');
  if (allOptions) {
    allOptions.forEach(option => {
      option.style.pointerEvents = 'none';
      option.style.opacity = '0.7';
    });
  }

  const formData = new FormData();
  formData.append("action", "wpcs_submit_vote");
  formData.append("_ajax_nonce", wpcs_poll_ajax.nonce);
  formData.append("poll_id", pollId);
  formData.append("option_id", optionId);

  console.log('WPCS Poll Debug: Sending vote data:', {
    action: "wpcs_submit_vote",
    poll_id: pollId,
    option_id: optionId,
    nonce: wpcs_poll_ajax.nonce,
    ajax_url: wpcs_poll_ajax.ajax_url
  });

  fetch(wpcs_poll_ajax.ajax_url, {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      console.log('WPCS Poll Debug: Vote response status:', response.status);
      console.log('WPCS Poll Debug: Vote response headers:', response.headers);
      
      if (!response.ok) {
        // Enhanced error handling for different status codes
        let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
        if (response.status === 403) {
          errorMessage = 'Access denied. Please check your login status and try again.';
        } else if (response.status === 400) {
          errorMessage = 'Invalid request. Please refresh the page and try again.';
        } else if (response.status === 500) {
          errorMessage = 'Server error. Please try again later.';
        }
        throw new Error(errorMessage);
      }
      
      return response.text().then(text => {
        console.log('WPCS Poll Debug: Raw response text:', text);
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error('WPCS Poll Debug: Failed to parse JSON response:', e);
          console.error('WPCS Poll Debug: Response text was:', text);
          throw new Error('Invalid response format from server');
        }
      });
    })
    .then((data) => {
      console.log('WPCS Poll Debug: Parsed vote response:', data);
      
      if (data.success) {
        // Mark the selected option as voted
        if (optionElement) {
          optionElement.classList.add('selected', 'user-voted');
          const voteIndicator = optionElement.querySelector('.vote-indicator');
          if (!voteIndicator) {
            const optionContent = optionElement.querySelector('.option-content');
            if (optionContent) {
              optionContent.innerHTML += '<span class="vote-indicator">✓</span>';
            }
          }
        }
        
        // Update UI with new vote counts
        if (data.data && data.data.vote_counts) {
          updatePollResults(pollId, data.data.vote_counts);
        }
        
        const message = (data.data && data.data.message) ? data.data.message : 'Vote recorded successfully!';
        showNotification(message, 'success');
        
        // Trigger auto-advance timer if enabled
        const container = pollCard?.closest('.wpcs-poll-container');
        if (container && container.wpcsContainer) {
          container.wpcsContainer.onUserVoted();
        }
      } else {
        const errorMessage = (data.data && data.data.message) ? data.data.message : 'Voting failed';
        console.error('WPCS Poll Debug: Vote failed:', errorMessage);
        showNotification(errorMessage, 'error');
      }
    })
    .catch((error) => {
      console.error("WPCS Poll Debug: Voting error:", error);
      showNotification(`Network error: ${error.message}`, 'error');
    })
    .finally(() => {
      // Re-enable all options
      if (allOptions) {
        allOptions.forEach(option => {
          option.style.pointerEvents = '';
          option.style.opacity = '';
        });
      }
    });
};

// Global bookmark function
window.wpcsBookmarkPoll = function(pollId) {
  if (!wpcs_poll_ajax || !wpcs_poll_ajax.nonce) {
    showNotification("Please log in to bookmark polls", 'error');
    return;
  }

  const formData = new FormData();
  formData.append("action", "wpcs_poll_bookmark");
  formData.append("nonce", wpcs_poll_ajax.nonce);
  formData.append("poll_id", pollId);

  fetch(wpcs_poll_ajax.ajax_url, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const message = data.data && data.data.message ? data.data.message : 'Bookmark action completed';
        showNotification(message, 'success');
      } else {
        const errorMessage = data.data && data.data.message ? data.data.message : 'Bookmark action failed';
        showNotification(errorMessage, 'error');
      }
    })
    .catch((error) => {
      console.error("Bookmark error:", error);
      showNotification("Network error occurred", 'error');
    });
};

// Global share function
window.wpcsSharePoll = function(pollId) {
  if (navigator.share) {
    navigator.share({
      title: 'Check out this poll!',
      url: window.location.href + '#poll-' + pollId
    });
  } else {
    // Fallback to copying URL
    const url = window.location.href + '#poll-' + pollId;
    if (navigator.clipboard) {
      navigator.clipboard.writeText(url).then(() => {
        showNotification('Poll URL copied to clipboard!', 'success');
      }).catch(() => {
        showNotification('Could not copy URL', 'error');
      });
    } else {
      showNotification('Sharing not supported on this device', 'error');
    }
  }
};

// Update poll results after voting
function updatePollResults(pollId, voteCounts) {
  console.log('WPCS Poll Debug: Updating poll results for poll', pollId, 'with counts:', voteCounts);
  
  const pollCard = document.querySelector(`[data-poll-id="${pollId}"]`);
  if (!pollCard) {
    console.warn('WPCS Poll Debug: Poll card not found for ID', pollId);
    return;
  }

  const options = pollCard.querySelectorAll('.poll-option');
  const totalVotes = Object.values(voteCounts).reduce((sum, count) => sum + parseInt(count || 0), 0);

  console.log('WPCS Poll Debug: Total votes calculated:', totalVotes);

  options.forEach(option => {
    const optionId = option.dataset.optionId;
    const voteCount = parseInt(voteCounts[optionId] || 0);
    const percentage = totalVotes > 0 ? (voteCount / totalVotes) * 100 : 0;

    console.log('WPCS Poll Debug: Updating option', optionId, 'with', voteCount, 'votes,', percentage.toFixed(1) + '%');

    option.classList.add('show-results');
    
    // Update or add percentage display
    let percentageSpan = option.querySelector('.option-percentage');
    if (percentageSpan) {
      percentageSpan.textContent = percentage.toFixed(1) + '%';
    } else {
      const optionContent = option.querySelector('.option-content');
      if (optionContent && !optionContent.querySelector('.vote-indicator')) {
        optionContent.innerHTML += `<span class="option-percentage">${percentage.toFixed(1)}%</span>`;
      }
    }

    // Update or add progress bar
    let progressBar = option.querySelector('.progress-bar');
    if (progressBar) {
      progressBar.style.width = percentage + '%';
    } else {
      option.innerHTML += `
        <div class="option-progress">
          <div class="progress-bar" style="width: ${percentage}%"></div>
        </div>
        <div class="option-votes">${voteCount} votes</div>
      `;
    }

    // Update vote count display
    let votesSpan = option.querySelector('.option-votes');
    if (votesSpan) {
      votesSpan.textContent = voteCount + ' votes';
    }
  });

  // Update total votes display
  const votesDisplay = pollCard.querySelector('.poll-votes');
  if (votesDisplay) {
    votesDisplay.textContent = totalVotes + ' votes';
  }
}

// Show notification with improved styling
function showNotification(message, type = 'info') {
  // Remove any existing notifications
  const existingNotifications = document.querySelectorAll('.wpcs-notification');
  existingNotifications.forEach(notification => notification.remove());

  const notification = document.createElement('div');
  notification.className = `wpcs-notification wpcs-notification-${type}`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  
  // Show notification
  setTimeout(() => notification.classList.add('show'), 100);
  
  // Hide notification
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 300);
  }, 4000);
}

// Initialize poll containers when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  console.log('WPCS Poll Debug: DOM loaded, initializing poll containers');
  console.log('WPCS Poll Debug: wpcs_poll_ajax object:', wpcs_poll_ajax);
  
  const pollContainers = document.querySelectorAll(".wpcs-poll-container");
  console.log('WPCS Poll Debug: Found', pollContainers.length, 'poll containers');
  
  pollContainers.forEach((container, index) => {
    console.log('WPCS Poll Debug: Initializing container', index);
    const containerInstance = new WPCSPollContainer(container);
    // Store reference for external access
    container.wpcsContainer = containerInstance;
  });
});