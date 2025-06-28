# WPCS Poll Plugin - Complete Documentation

## 🎯 Overview

WPCS Poll is a modern, interactive WordPress polling plugin that brings TikTok-style poll browsing to your website. It features a comprehensive admin panel, real-time voting, and multiple display options for engaging user interaction.

## ✨ Key Features

### 🗳️ **Interactive Polling System**
- **TikTok-Style Interface**: Swipeable poll cards with smooth animations
- **Real-Time Voting**: Instant vote recording with live result updates
- **Multiple Navigation**: Keyboard shortcuts, touch gestures, and button controls
- **Visual Results**: Animated progress bars with vote percentages
- **Category Organization**: Polls organized by customizable categories
- **Search & Filter**: Advanced filtering options for poll discovery

### 👤 **User Management**
- **User Registration**: Simple email/password authentication
- **User Dashboard**: Personal voting history and statistics
- **Poll Creation**: Users can submit polls for approval
- **Bookmark System**: Save favorite polls for later
- **Profile Management**: User profiles with activity tracking

### 🔧 **Comprehensive Admin Panel**
- **Dashboard Overview**: System statistics and analytics
- **Poll Management**: Full CRUD operations for all polls
- **User Management**: View and manage user accounts
- **Approval System**: Review and approve user-submitted polls
- **Bulk Operations**: Import polls via CSV/JSON files
- **Analytics**: Detailed voting statistics and engagement metrics
- **Settings**: Configurable plugin options and permissions

### 🎨 **Display Options**
- **Multiple Styles**: TikTok-style, grid, list, and card layouts
- **Responsive Design**: Mobile-first with desktop optimization
- **Customizable**: Various shortcode options for different use cases
- **Accessibility**: Keyboard navigation and screen reader support

## 📋 Installation & Setup

### **1. Installation**
1. Upload the plugin files to `/wp-content/plugins/wpcs-poll/`
2. Activate the plugin through the WordPress admin
3. The plugin will automatically create necessary database tables

### **2. Initial Configuration**
1. Go to **WPCS Polls > Settings** in your WordPress admin
2. Configure basic settings:
   - Guest voting permissions
   - Auto-approval settings
   - Maximum options per poll
   - Default categories

### **3. Create Your First Poll**
1. Navigate to **WPCS Polls > All Polls**
2. Click "Add New Poll" or use the bulk upload feature
3. Fill in poll details and options
4. Activate the poll for public display

## 🚀 How to Use

### **Frontend Display Options**

#### **1. Display Random Polls (TikTok Style)**
```php
[wpcs_poll]
```
**Features:**
- Shows random polls from all categories
- TikTok-style swipeable interface
- Keyboard navigation (Arrow keys, Space)
- Touch/swipe gestures on mobile
- Auto-play option available

**Advanced Options:**
```php
[wpcs_poll style="tiktok" limit="10" category="all" autoplay="true" show_navigation="true"]
```

#### **2. Display Single Poll**
```php
[wpcs_poll id="123"]
```
**Features:**
- Shows specific poll by ID
- Card-style layout
- Voting interface
- Results display after voting

**Advanced Options:**
```php
[wpcs_poll_single id="123" show_results="after_vote" style="card"]
```

#### **3. Display Polls by Category**
```php
[wpcs_polls cat="Technology,Sports"]
```
**Features:**
- Filter polls by specific categories
- Grid or list layout options
- Pagination support
- Category-specific browsing

**Advanced Options:**
```php
[wpcs_polls cat="Technology" style="grid" limit="12" show_pagination="true" per_page="6"]
```

#### **4. User Dashboard**
```php
[wpcs_poll_user_dashboard]
```
**Features:**
- User voting statistics
- Created polls management
- Bookmarked polls
- Activity history

**Advanced Options:**
```php
[wpcs_poll_user_dashboard show_stats="true" show_recent_votes="true" show_created_polls="true" show_bookmarks="true"]
```

#### **5. Poll Submission Form**
```php
[wpcs_poll_submit_form]
```
**Features:**
- Frontend poll creation
- Category selection
- Multiple options support
- Tag system

**Advanced Options:**
```php
[wpcs_poll_submit_form max_options="10" show_description="true" show_tags="true" show_category="true"]
```

### **Shortcode Parameters**

| Parameter | Description | Default | Options |
|-----------|-------------|---------|---------|
| `id` | Specific poll ID | - | Any poll ID |
| `style` | Display style | `tiktok` | `tiktok`, `grid`, `list`, `card` |
| `category` | Filter by category | `all` | Category name or `all` |
| `limit` | Number of polls | `10` | Any number |
| `autoplay` | Auto-advance polls | `false` | `true`, `false` |
| `show_navigation` | Show nav buttons | `true` | `true`, `false` |
| `show_results` | When to show results | `after_vote` | `always`, `after_vote`, `never` |
| `show_pagination` | Enable pagination | `true` | `true`, `false` |
| `per_page` | Items per page | `6` | Any number |

## 🎮 User Interaction

### **Voting Process**
1. **Browse Polls**: Use navigation or swipe through polls
2. **Select Option**: Click/tap on preferred option
3. **View Results**: See real-time vote percentages
4. **Continue**: Navigate to next poll

### **Keyboard Controls**
- **Arrow Up/Down**: Navigate between polls
- **Space**: Go to next poll
- **Enter**: Vote on selected option
- **Escape**: Close modals

### **Mobile Gestures**
- **Swipe Up**: Next poll
- **Swipe Down**: Previous poll
- **Tap**: Vote on option
- **Long Press**: Bookmark poll

## 🔧 Admin Features

### **Dashboard Overview**
- Total polls, votes, and users
- Recent activity feed
- Popular categories
- Quick action buttons

### **Poll Management**
- **View All Polls**: Sortable list with filters
- **Edit Polls**: Modify title, options, category
- **Bulk Actions**: Activate, deactivate, delete multiple polls
- **Search**: Find polls by title or content

### **User Management**
- **User List**: View all registered users
- **Activity Tracking**: See user voting patterns
- **Role Management**: Assign user roles
- **Statistics**: User engagement metrics

### **Pending Approval**
- **Review Queue**: User-submitted polls awaiting approval
- **Quick Actions**: Approve or reject with one click
- **Bulk Processing**: Handle multiple submissions
- **Preview**: See how polls will appear before approval

### **Bulk Upload**
- **CSV Import**: Upload polls from spreadsheet
- **JSON Import**: Import structured poll data
- **Progress Tracking**: Real-time upload status
- **Error Reporting**: Detailed failure logs

**CSV Format Example:**
```csv
title,description,category,option1,option2,option3,option4,tags
"Favorite Color?","Choose your preferred color","General","Red","Blue","Green","Yellow","color,preference"
```

**JSON Format Example:**
```json
[
  {
    "title": "Favorite Color?",
    "description": "Choose your preferred color",
    "category": "General",
    "options": ["Red", "Blue", "Green", "Yellow"],
    "tags": ["color", "preference"]
  }
]
```

### **Analytics**
- **Vote Statistics**: Total votes, popular polls
- **User Engagement**: Active users, participation rates
- **Category Performance**: Most popular categories
- **Time-based Data**: Voting trends over time

### **Settings Configuration**

#### **General Settings**
- **Guest Voting**: Allow non-logged users to vote
- **Auto-Approve**: Automatically publish user polls
- **Login Required**: Require login for poll creation
- **Max Options**: Limit options per poll (2-20)
- **Default Category**: Set default poll category

#### **Feature Settings**
- **Poll Comments**: Enable/disable comments (future feature)
- **Social Sharing**: Enable share buttons
- **Analytics**: Track detailed user interactions

#### **Data Management**
- **Delete on Uninstall**: Remove all data when plugin is deleted
- **Export Settings**: Download configuration
- **Import Settings**: Restore from backup

## 🎨 Customization

### **CSS Customization**
The plugin includes comprehensive CSS classes for customization:

```css
/* Poll container */
.wpcs-poll-container { }

/* Individual poll cards */
.wpcs-poll-card { }

/* Poll options */
.poll-option { }
.poll-option:hover { }
.poll-option.selected { }

/* Results display */
.option-progress { }
.progress-bar { }
.option-percentage { }

/* Navigation */
.poll-navigation { }
.nav-btn { }
.poll-indicators { }
```

### **Color Schemes**
Customize the appearance by overriding CSS variables:

```css
:root {
  --wpcs-primary-color: #0073aa;
  --wpcs-secondary-color: #666;
  --wpcs-accent-color: #667eea;
  --wpcs-success-color: #28a745;
  --wpcs-error-color: #dc3545;
}
```

## 🔒 Security Features

### **Data Protection**
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: All output properly escaped
- **CSRF Protection**: Nonce verification for all actions
- **Input Validation**: Server-side validation for all inputs

### **User Permissions**
- **Role-Based Access**: Different permissions for users/admins
- **Vote Limiting**: Prevent multiple votes from same user
- **Rate Limiting**: Prevent spam submissions
- **IP Tracking**: Monitor voting patterns

### **Admin Security**
- **Capability Checks**: Verify admin permissions
- **Secure File Uploads**: Validate uploaded files
- **Audit Logging**: Track admin actions
- **Data Sanitization**: Clean all user inputs

## 📱 Mobile Experience

### **Responsive Design**
- **Mobile-First**: Optimized for mobile devices
- **Touch Gestures**: Native swipe and tap interactions
- **Adaptive Layout**: Adjusts to screen size
- **Fast Loading**: Optimized for mobile networks

### **Progressive Enhancement**
- **Core Functionality**: Works without JavaScript
- **Enhanced Experience**: Rich interactions with JS enabled
- **Offline Capability**: Basic functionality when offline
- **Performance**: Lazy loading and optimization

## 🔧 Troubleshooting

### **Common Issues**

#### **Polls Not Loading**
1. Check if polls exist in admin panel
2. Verify shortcode syntax
3. Check browser console for errors
4. Ensure JavaScript is enabled

#### **Voting Not Working**
1. Verify user is logged in (if required)
2. Check if poll is active
3. Confirm user hasn't already voted
4. Check for JavaScript errors

#### **Admin Panel Issues**
1. Verify user has admin permissions
2. Check for plugin conflicts
3. Ensure database tables exist
4. Review error logs

### **Debug Mode**
Enable debugging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for detailed error information.

## 🚀 Performance Optimization

### **Caching**
- **Database Queries**: Optimized with proper indexing
- **Static Assets**: CSS/JS minification and compression
- **Image Optimization**: Lazy loading for poll images
- **Browser Caching**: Proper cache headers

### **Scalability**
- **Efficient Queries**: Pagination and limiting
- **Database Optimization**: Proper indexing strategy
- **CDN Ready**: Static assets can be served from CDN
- **Load Balancing**: Compatible with multiple servers

## 📊 Analytics & Reporting

### **Built-in Analytics**
- **Vote Tracking**: Real-time vote counting
- **User Engagement**: Participation rates and patterns
- **Popular Content**: Most voted polls and categories
- **Time-based Analysis**: Voting trends over time

### **Export Options**
- **CSV Export**: Download voting data
- **JSON Export**: Structured data export
- **PDF Reports**: Formatted analytics reports
- **API Access**: Programmatic data access

## 🔄 Updates & Maintenance

### **Automatic Updates**
- **WordPress Updates**: Compatible with WordPress auto-updates
- **Database Migrations**: Automatic schema updates
- **Backward Compatibility**: Maintains compatibility with older versions
- **Settings Preservation**: Maintains configuration during updates

### **Backup Recommendations**
- **Database Backup**: Regular backups of poll data
- **Settings Export**: Save configuration settings
- **File Backup**: Include plugin files in site backups
- **Testing**: Test updates on staging environment

## 🤝 Support & Community

### **Documentation**
- **Online Help**: Comprehensive documentation website
- **Video Tutorials**: Step-by-step video guides
- **FAQ**: Common questions and answers
- **Code Examples**: Implementation examples

### **Support Channels**
- **Support Forum**: Community-driven support
- **Email Support**: Direct technical support
- **Bug Reports**: GitHub issue tracking
- **Feature Requests**: Community voting on new features

## 📈 Future Roadmap

### **Upcoming Features**
- **Advanced Poll Types**: Ranked choice, multiple selection
- **Social Integration**: Share to social media platforms
- **Advanced Analytics**: Detailed user behavior tracking
- **Mobile App**: Native mobile applications
- **API Expansion**: RESTful API for third-party integrations

### **Long-term Vision**
- **Enterprise Features**: Advanced user management and branding
- **Multi-site Support**: Network-wide poll management
- **Internationalization**: Multi-language support
- **Accessibility**: Enhanced accessibility features

---

## 📝 Quick Start Checklist

- [ ] Install and activate plugin
- [ ] Configure basic settings
- [ ] Create sample polls
- [ ] Add shortcode to page/post
- [ ] Test voting functionality
- [ ] Customize appearance (optional)
- [ ] Set up user permissions
- [ ] Enable analytics tracking

**Need Help?** Check the troubleshooting section or contact support for assistance.

---

*WPCS Poll Plugin - Making polling interactive and engaging for WordPress websites.*