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

    fetch(`${wpcs_poll_ajax.rest_url}polls?category=${category}&limit=${limit}`)
      .then((response) => response.json())
      .then((polls) => {
        this.polls = polls;
        this.renderPolls();
      })
      .catch((error) => console.error("Error loading polls:", error));
  }

  renderPolls() {
    const pollsHTML = this.polls
      .map(
        (poll, index) => `
            <div class="wpcs-poll-card ${index === 0 ? "active" : ""}" 
                 data-poll-id="${poll.id}" 
                 data-index="${index}">
                <div class="poll-content">
                    <h3 class="poll-title">${poll.title}</h3>
                    ${
                      poll.description
                        ? `<p class="poll-description">${poll.description}</p>`
                        : ""
                    }
                    <div class="poll-options">
                        ${this.renderPollOptions(poll)}
                    </div>
                    <div class="poll-meta">
                        <span class="poll-category">${poll.category}</span>
                        <span class="poll-votes">${this.getTotalVotes(
                          poll
                        )} votes</span>
                    </div>
                </div>
            </div>
        `
      )
      .join("");

    this.container.innerHTML = `
            <div class="wpcs-polls-wrapper">
                ${pollsHTML}
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
            </div>
        `;
  }

  renderPollOptions(poll) {
    const options = JSON.parse(poll.options);
    const totalVotes = this.getTotalVotes(poll);
    const userVote = this.getUserVote(poll.id);

    return options
      .map((option) => {
        const voteCount = option.votes || 0;
        const percentage = totalVotes > 0 ? (voteCount / totalVotes) * 100 : 0;
        const isSelected = userVote === option.id;

        return `
                <div class="poll-option ${isSelected ? "selected" : ""}" 
                     data-option-id="${option.id}" 
                     onclick="wpcsVoteOnPoll(${poll.id}, '${option.id}')">
                    <div class="option-content">
                        <span class="option-text">${option.text}</span>
                        <span class="option-percentage">${percentage.toFixed(
                          1
                        )}%</span>
                    </div>
                    <div class="option-progress">
                        <div class="progress-bar" style="width: ${percentage}%"></div>
                    </div>
                    <div class="option-votes">${voteCount} votes</div>
                </div>
            `;
      })
      .join("");
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
    if (this.isAnimating) return;
    const nextIndex = (this.currentIndex + 1) % this.polls.length;
    this.goToPoll(nextIndex);
  }

  previousPoll() {
    if (this.isAnimating) return;
    const prevIndex =
      this.currentIndex === 0 ? this.polls.length - 1 : this.currentIndex - 1;
    this.goToPoll(prevIndex);
  }

  goToPoll(index) {
    if (this.isAnimating || index === this.currentIndex) return;

    this.isAnimating = true;
    const currentCard = this.container.querySelector(
      `[data-index="${this.currentIndex}"]`
    );
    const nextCard = this.container.querySelector(`[data-index="${index}"]`);

    // Animation logic
    currentCard.classList.remove("active");
    nextCard.classList.add("active");

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
    const options = JSON.parse(poll.options);
    return options.reduce((total, option) => total + (option.votes || 0), 0);
  }

  getUserVote(pollId) {
    // Check if user has voted on this poll
    // This would be populated from server-side data
    return null;
  }
}

// Global voting function
window.wpcsVoteOnPoll = function (pollId, optionId) {
  if (!wpcs_poll_ajax.nonce) {
    alert("Please log in to vote");
    return;
  }

  const formData = new FormData();
  formData.append("action", "wpcs_poll_vote");
  formData.append("nonce", wpcs_poll_ajax.nonce);
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
        location.reload(); // Temporary - implement live updates
      } else {
        alert(data.data.message || "Voting failed");
      }
    })
    .catch((error) => {
      console.error("Voting error:", error);
      alert("Network error occurred");
    });
};

// Initialize poll containers when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  const pollContainers = document.querySelectorAll(".wpcs-poll-container");
  pollContainers.forEach((container) => {
    new WPCSPollContainer(container);
  });
});
