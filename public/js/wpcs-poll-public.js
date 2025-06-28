class WPCSPollContainer {
  constructor(container) {
    this.container = container;
    this.currentIndex = 0;
    this.polls = [];
    this.touchStartY = 0;
    this.touchEndY = 0;
    this.isAnimating = false;

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

    fetch(`${wpcs_poll_ajax.rest_url}polls?category=${category}&limit=${limit}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((polls) => {
        console.log('Polls loaded:', polls);
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
                            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                        </svg>
                    </button>
                    <button class="nav-btn next-btn" aria-label="Next poll">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
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
    // Handle different formats of poll.options
    let options = [];
    
    if (typeof poll.options === 'string') {
      try {
        options = JSON.parse(poll.options);
      } catch (e) {
        console.error('Failed to parse poll options JSON:', e);
        options = [];
      }
    } else if (Array.isArray(poll.options)) {
      options = poll.options;
    } else if (poll.options && typeof poll.options === 'object') {
      // If it's already an object, convert to array
      options = Object.values(poll.options);
    }

    const totalVotes = this.getTotalVotes(poll);
    const userVote = poll.user_vote;

    if (!Array.isArray(options) || options.length === 0) {
      return '<p class="no-options">No options available</p>';
    }

    return options
      .map((option) => {
        // Handle different option formats
        const optionId = option.id || option.option_id || `option_${Math.random()}`;
        const optionText = option.text || option.option_text || option.title || 'Unknown option';
        const voteCount = parseInt(option.votes || option.vote_count || 0);
        
        const percentage = totalVotes > 0 ? (voteCount / totalVotes) * 100 : 0;
        const isSelected = userVote === optionId;
        const showResults = userVote !== null;

        return `
                <div class="poll-option ${isSelected ? "selected" : ""} ${showResults ? "show-results" : ""}" 
                     data-option-id="${optionId}" 
                     onclick="wpcsVoteOnPoll(${poll.id}, '${optionId}')">
                    <div class="option-content">
                        <span class="option-text">${this.escapeHtml(optionText)}</span>
                        ${showResults ? `<span class="option-percentage">${percentage.toFixed(1)}%</span>` : ''}
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
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
  }

  setupAutoplay() {
    setInterval(() => {
      if (!this.isAnimating) {
        this.nextPoll();
      }
    }, 5000); // Change poll every 5 seconds
  }

  bindEvents() {
    // Touch events for mobile swiping
    this.container.addEventListener("touchstart", (e) => {
      this.touchStartY = e.touches[0].clientY;
    });

    this.container.addEventListener("touchend", (e) => {
      this.touchEndY = e.changedTouches[0].clientY;
      this.handleSwipe();
    });

    // Navigation buttons
    this.container.addEventListener("click", (e) => {
      if (e.target.closest(".prev-btn")) {
        this.previousPoll();
      } else if (e.target.closest(".next-btn")) {
        this.nextPoll();
      } else if (e.target.closest(".indicator")) {
        const index = parseInt(e.target.dataset.index);
        this.goToPoll(index);
      }
    });
  }

  setupKeyboardNavigation() {
    document.addEventListener("keydown", (e) => {
      if (!this.container.matches(":hover")) return;

      switch (e.key) {
        case "ArrowUp":
          e.preventDefault();
          this.previousPoll();
          break;
        case "ArrowDown":
          e.preventDefault();
          this.nextPoll();
          break;
        case " ":
          e.preventDefault();
          this.nextPoll();
          break;
      }
    });
  }

  handleSwipe() {
    const swipeThreshold = 50;
    const diff = this.touchStartY - this.touchEndY;

    if (Math.abs(diff) > swipeThreshold) {
      if (diff > 0) {
        this.nextPoll();
      } else {
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
    const currentCard = this.container.querySelector(
      `[data-index="${this.currentIndex}"]`
    );
    const nextCard = this.container.querySelector(`[data-index="${index}"]`);

    if (currentCard) currentCard.classList.remove("active");
    if (nextCard) nextCard.classList.add("active");

    // Update indicators
    this.container.querySelectorAll(".indicator").forEach((indicator, i) => {
      indicator.classList.toggle("active", i === index);
    });

    this.currentIndex = index;

    setTimeout(() => {
      this.isAnimating = false;
    }, 300);
  }

  getTotalVotes(poll) {
    let options = [];
    
    if (typeof poll.options === 'string') {
      try {
        options = JSON.parse(poll.options);
      } catch (e) {
        options = [];
      }
    } else if (Array.isArray(poll.options)) {
      options = poll.options;
    } else if (poll.options && typeof poll.options === 'object') {
      options = Object.values(poll.options);
    }

    if (!Array.isArray(options)) {
      return 0;
    }

    return options.reduce((total, option) => {
      const votes = parseInt(option.votes || option.vote_count || 0);
      return total + votes;
    }, 0);
  }
}

// Global voting function
window.wpcsVoteOnPoll = function (pollId, optionId) {
  if (!wpcs_poll_ajax.nonce) {
    showNotification("Please log in to vote", 'error');
    return;
  }

  const formData = new FormData();
  formData.append("action", "wpcs_submit_vote");
  formData.append("_ajax_nonce", wpcs_poll_ajax.nonce);
  formData.append("poll_id", pollId);
  formData.append("option_id", optionId);

  fetch(wpcs_poll_ajax.ajax_url, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Update UI with new vote counts
        updatePollResults(pollId, data.data.vote_counts);
        showNotification(data.data.message, 'success');
      } else {
        showNotification(data.data ? data.data.message : "Voting failed", 'error');
      }
    })
    .catch((error) => {
      console.error("Voting error:", error);
      showNotification("Network error occurred", 'error');
    });
};

// Global bookmark function
window.wpcsBookmarkPoll = function(pollId) {
  if (!wpcs_poll_ajax.nonce) {
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
        showNotification(data.data.message, 'success');
      } else {
        showNotification(data.data ? data.data.message : "Bookmark action failed", 'error');
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
    navigator.clipboard.writeText(url).then(() => {
      showNotification('Poll URL copied to clipboard!', 'success');
    }).catch(() => {
      showNotification('Could not copy URL', 'error');
    });
  }
};

// Update poll results after voting
function updatePollResults(pollId, voteCounts) {
  const pollCard = document.querySelector(`[data-poll-id="${pollId}"]`);
  if (!pollCard) return;

  const options = pollCard.querySelectorAll('.poll-option');
  const totalVotes = Object.values(voteCounts).reduce((sum, count) => sum + count, 0);

  options.forEach(option => {
    const optionId = option.dataset.optionId;
    const voteCount = voteCounts[optionId] || 0;
    const percentage = totalVotes > 0 ? (voteCount / totalVotes) * 100 : 0;

    option.classList.add('show-results');
    
    const percentageSpan = option.querySelector('.option-percentage');
    if (percentageSpan) {
      percentageSpan.textContent = percentage.toFixed(1) + '%';
    } else {
      // Add percentage span if it doesn't exist
      const optionContent = option.querySelector('.option-content');
      if (optionContent) {
        optionContent.innerHTML += `<span class="option-percentage">${percentage.toFixed(1)}%</span>`;
      }
    }

    const progressBar = option.querySelector('.progress-bar');
    if (progressBar) {
      progressBar.style.width = percentage + '%';
    } else {
      // Add progress bar if it doesn't exist
      option.innerHTML += `
        <div class="option-progress">
          <div class="progress-bar" style="width: ${percentage}%"></div>
        </div>
        <div class="option-votes">${voteCount} votes</div>
      `;
    }

    const votesSpan = option.querySelector('.option-votes');
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

// Show notification
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `wpcs-notification wpcs-notification-${type}`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  
  // Show notification
  setTimeout(() => notification.classList.add('show'), 100);
  
  // Hide notification
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Initialize poll containers when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  const pollContainers = document.querySelectorAll(".wpcs-poll-container");
  pollContainers.forEach((container) => {
    new WPCSPollContainer(container);
  });
});