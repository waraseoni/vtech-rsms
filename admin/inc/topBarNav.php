<style>
  .user-img{
        position: absolute;
        height: 27px;
        width: 27px;
        object-fit: cover;
        left: -7%;
        top: -12%;
  }
  .btn-rounded{
        border-radius: 50px;
  }
  .nav-link i.fa-sync-alt:active {
    transform: rotate(180deg);
    transition: 0.3s;
  }
  /* Hamburger button - only visible on mobile */
  .hamburger-btn {
    display: none !important;
    background: none !important;
    border: none !important;
    color: inherit !important;
    font-size: 20px !important;
    padding: 5px 10px !important;
    cursor: pointer !important;
  }
  /* Hide hamburger container on desktop */
  .hamburger-mobile-only {
    display: none !important;
  }
  /* Floating Profile Button - Mobile */
  .fab-profile {
    display: none;
    position: relative;
  }
  @media (max-width: 991px) {
    /* Show hamburger on mobile */
    .hamburger-mobile-only {
      display: inline-flex !important;
    }
    /* Hamburger button */    /* Mobile topbar styling */
    .main-header {
        background: #343a40 !important;
        z-index: 1030 !important;
        min-height: 35px !important;
        height: 35px !important;
        padding: 0 !important;
        width: 100% !important;
        margin-left: 0 !important;
        left: 0 !important;
        position: fixed !important;
        top: 0 !important;
        display: block !important;
    }
    .main-header .navbar {
        width: 100% !important;
        display: flex !important;
        justify-content: space-between !important;
        padding: 0 !important;
        margin: 0 !important;
        min-height: 35px !important;
        height: 35px !important;
        border: none !important;
    }
    .main-header .navbar-nav {
        width: 100% !important;
        flex-direction: row !important;
        display: flex !important;
        align-items: center !important;
        padding: 0 5px !important;
        height: 35px !important;
    }
    .main-header .nav-link {
        padding: 0 10px !important;
        color: #fff !important;
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        height: 35px !important;
    }
    .main-header .nav-link i {
        font-size: 16px !important;
    }
    .hamburger-btn {
        color: #fff !important;
        display: inline-flex !important;
        padding: 0 8px !important;
        min-width: 35px !important;
        min-height: 35px !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .hamburger-btn i {
        font-size: 18px !important;
    }
    /* Floating profile button on mobile */
    .fab-profile {
      display: inline-flex !important;
      position: fixed !important;
      bottom: 20px !important;
      right: 20px !important;
      z-index: 99999 !important;
    }
    .fab-profile > img {
      width: 56px !important;
      height: 56px !important;
      border-radius: 50% !important;
      border: none !important;
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5) !important;
    }
    /* Profile Dropdown */
    .profile-dropdown {
      display: none;
      position: absolute;
      bottom: 70px;
      right: 0;
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      min-width: 160px;
      overflow: hidden;
      z-index: 100000 !important;
    }
    .profile-dropdown.show {
      display: block;
    }
    .profile-dropdown-header {
      padding: 10px 12px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
    }
    /* Universal Search Styles - Enhanced Desktop */
    .universal-search-container {
        position: relative;
        flex: 1;
        max-width: 500px;
        margin: 0 15px;
        z-index: 1001;
    }
    .universal-search-input {
        width: 100%;
        padding: 8px 15px 8px 40px !important;
        border-radius: 20px !important;
        border: 1px solid #444 !important;
        font-size: 0.95rem !important;
        background: #3a3a3a !important;
        color: #fff !important;
        height: 38px !important;
    }

    /* Floating Search Button (FAB) - Mobile Only (Bottom Left) */
    .fab-search {
        display: none;
        position: fixed;
        bottom: 25px; 
        left: 20px;
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, #007bff 0%, #00d2ff 100%);
        color: white;
        border-radius: 50%;
        box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        cursor: pointer;
        border: none;
        animation: slideUp 0.5s ease-out;
    }

    /* Full-Screen Search Overlay */
    .mobile-search-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.98);
        z-index: 200000;
        padding: 20px;
        animation: fadeIn 0.3s ease;
    }
    .overlay-search-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }
    .overlay-search-input {
        flex: 1;
        background: #f1f3f5;
        border: 2px solid #007bff;
        border-radius: 12px;
        padding: 12px 15px;
        font-size: 1.1rem;
        outline: none;
    }
    .close-search-overlay {
        background: none;
        border: none;
        font-size: 24px;
        color: #666;
    }

    @media (max-width: 991px) {
        .fab-search { display: flex !important; }
        .desktop-search-container { display: none !important; }
        .main-header { min-height: 50px !important; }
    }
  }

  /* Universal Search Styles - Enhanced */
  .universal-search-container {
      position: relative;
      flex: 1;
      max-width: 600px;
      margin: 0 15px;
      z-index: 1001;
  }
  .universal-search-input {
      width: 100%;
      padding: 8px 15px 8px 40px !important;
      border-radius: 8px !important;
      border: 1px solid #444 !important;
      font-size: 0.95rem !important;
      transition: all 0.3s !important;
      background: #4a4a4a !important;
      color: #fff !important;
      height: 38px !important;
  }
  .universal-search-input::placeholder {
      color: #bbb !important;
  }
  .universal-search-input:focus {
      outline: none;
      background: #fff !important;
      color: #333 !important;
      border-color: #007bff !important;
      box-shadow: 0 0 15px rgba(0,0,0,0.3) !important;
      max-width: 800px;
  }
  .search-icon-nav {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #bbb;
      pointer-events: none;
      z-index: 1002;
      font-size: 1.1rem;
  }
  .universal-search-input:focus + .search-icon-nav {
      color: #007bff;
  }
    .search-results-dropdown {
        position: fixed;
        top: 60px;
        /* Start after sidebar by default */
        left: 260px; 
        right: 10px;
        width: auto !important;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3) !important;
        display: none;
        z-index: 100000;
        max-height: calc(100vh - 80px);
        overflow-y: auto;
        border: 1px solid #dee2e6;
        animation: fadeInRight 0.3s ease-out;
        transition: left 0.3s ease;
    }
    
    /* Handle Sidebar Collapse in AdminLTE */
    body.sidebar-collapse .search-results-dropdown {
        left: 80px;
    }

    @keyframes fadeInRight {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @media (max-width: 991px) {
        .search-results-dropdown {
            left: 10px !important;
            right: 10px !important;
            top: 55px !important;
            width: auto !important;
            max-height: 85vh;
        }
    }
  .search-result-item {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      border-bottom: 1px solid #f0f0f0;
      text-decoration: none !important;
      color: #333 !important;
      transition: background 0.2s;
  }
  .search-result-item:hover {
      background: #f8f9fa;
      color: #007bff !important;
  }
  .result-icon {
      width: 32px;
      height: 32px;
      background: #e9ecef;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 12px;
      color: #007bff;
      flex-shrink: 0;
  }
  .result-details {
      flex: 1;
      min-width: 0;
  }
  .result-title {
      font-weight: 700 !important;
      font-size: 1.05rem !important;
      display: block;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      text-align: left;
  }
  .result-subtitle {
      font-size: 0.85rem !important;
      color: #555 !important;
      display: block;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      text-align: left;
  }
  .result-type {
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      color: #adb5bd;
      margin-left: 10px;
  }
</style>
<!-- Navbar -->
      <nav class="main-header navbar navbar-expand navbar-light shadow text-sm">
        <!-- Left navbar links -->
        <ul class="navbar-nav w-100 d-flex align-items-center justify-content-between">
          <li class="nav-item d-flex align-items-center">
            <!-- 1. Back and Refresh -->
            <a href="javascript:void(0)" onclick="history.back()" class="nav-link">
              <i class="fas fa-arrow-left"></i>
            </a>
            <a href="javascript:void(0)" onclick="location.reload()" class="nav-link text-primary">
              <i class="fas fa-sync-alt"></i>
            </a>
          </li>

          <!-- 2. Desktop Universal Search Bar (Hidden on Mobile) -->
          <li class="nav-item flex-grow-1 desktop-search-container">
            <div class="universal-search-container">
                <i class="fas fa-search search-icon-nav"></i>
                <input type="text" id="universalSearchInput" class="universal-search-input" placeholder="Search (Jobs, Clients, Products...)" autocomplete="off">
                <div id="universalSearchResults" class="search-results-dropdown shadow"></div>
            </div>
          </li>

          <!-- 4. Hamburger Menu (Mobile/Desktop) -->
          <li class="nav-item">
            <button class="hamburger-btn" onclick="toggleSidebar(event)" type="button">
              <i class="fas fa-bars"></i>
            </button>
          </li>
        </ul>
        <!-- Right navbar links (Desktop Only) -->
        <ul class="navbar-nav ml-auto d-none d-md-flex">
          <li class="nav-item user-dropdown-mobile">
            <div class="btn-group nav-link">
                  <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    <span><img src="<?php echo validate_image($_settings->userdata('avatar'), true) ?>" class="img-circle elevation-2 user-img" alt="User Image" onerror="this.src='<?php echo base_url ?>uploads/avatars/default-avatar.jpg'"></span>
                    <span class="ml-3"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
                    <span class="sr-only">Toggle Dropdown</span>
                  </button>
                  <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="<?php echo base_url.'admin/?page=user' ?>"><span class="fa fa-user"></span> My Account</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo base_url.'/classes/Login.php?f=logout' ?>"><span class="fas fa-sign-out-alt"></span> Logout</a>
                </div>
              </div>
          </li>
          <li class="nav-item">
            
          </li>
          <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
            <i class="fas fa-th-large"></i>
            </a>
          </li> 
        </ul>
      </nav>
      <!-- /.navbar -->

<!-- Mobile Search Float Button -->
<button class="fab-search" onclick="openSearchOverlay()">
    <i class="fa fa-search"></i>
</button>

<!-- Full-Screen Mobile Search Overlay -->
<div class="mobile-search-overlay" id="mobileSearchOverlay">
    <div class="overlay-search-header">
        <button class="close-search-overlay" onclick="closeSearchOverlay()"><i class="fa fa-arrow-left"></i></button>
        <input type="text" class="overlay-search-input" id="overlaySearchInput" placeholder="Type name, job id, products...">
    </div>
    <div id="overlaySearchResults" class="mt-3"></div>
</div>

<!-- Floating Profile Button for Mobile with Dropdown -->
<div class="fab-profile">
    <img src="<?php echo validate_image($_settings->userdata('avatar'), true) ?>" alt="Profile" onclick="toggleProfileMenu()" onerror="this.src='<?php echo base_url ?>uploads/avatars/default-avatar.jpg'">
    <div class="profile-dropdown" id="profileDropdown">
        <div class="profile-dropdown-header">
            <div class="name"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></div>
            <div class="role"><?php echo $_settings->userdata('type') == 1 ? 'Administrator' : 'Staff' ?></div>
        </div>
        <a href="<?php echo base_url.'admin/?page=user' ?>" class="profile-dropdown-item">
            <i class="fa fa-user"></i> My Profile
        </a>
        <a href="<?php echo base_url.'/classes/Login.php?f=logout' ?>" class="profile-dropdown-item logout">
            <i class="fa fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<script>
function toggleSidebar(e) {
    var sidebar = document.querySelector('.main-sidebar');
    var body = document.body;
    if(!sidebar) return;
    
    // Toggle both body class and sidebar show class
    if (body.classList.contains('sidebar-open')) {
        body.classList.remove('sidebar-open');
        sidebar.classList.remove('show');
        sidebar.style.transform = 'translateX(-100%)';
    } else {
        body.classList.add('sidebar-open');
        sidebar.classList.add('show');
        sidebar.style.transform = 'translateX(0)';
    }
    
    if(e) {
        e.preventDefault();
        e.stopPropagation();
    }
}

// Initialize: On mobile, remove show class to hide sidebar by default
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth <= 991) {
        var sidebar = document.querySelector('.main-sidebar');
        var body = document.body;
        if (sidebar) {
            sidebar.classList.remove('show');
            sidebar.style.transform = 'translateX(-100%)';
        }
        body.classList.remove('sidebar-open');
    }
});

// Also handle window resize
window.addEventListener('resize', function() {
    var sidebar = document.querySelector('.main-sidebar');
    var body = document.body;
    if (window.innerWidth <= 991) {
        if (sidebar && !body.classList.contains('sidebar-open')) {
            sidebar.style.transform = 'translateX(-100%)';
        }
    } else {
        // Desktop: Reset sidebar to default state
        if (sidebar) {
            sidebar.classList.remove('show');
            sidebar.style.transform = '';
            sidebar.style.position = '';
            sidebar.style.left = '';
            sidebar.style.top = '';
            sidebar.style.height = '';
            sidebar.style.width = '';
        }
        body.classList.remove('sidebar-open');
    }
});

function toggleProfileMenu() {
    var dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    var fab = document.querySelector('.fab-profile');
    var dropdown = document.getElementById('profileDropdown');
    if (fab && !fab.contains(e.target)) {
        dropdown.classList.remove('show');
    }
    
    // Close search results
    if (!$(e.target).closest('.universal-search-container').length) {
        $('#universalSearchResults').hide();
    }
});

/**
 * Universal Search Logic
 */
$(document).ready(function() {
    let searchTimer;
    
    $('#universalSearchInput').on('input', function() {
        const query = $(this).val().trim();
        clearTimeout(searchTimer);
        
        if (query.length < 2) {
            $('#universalSearchResults').hide();
            return;
        }
        
        searchTimer = setTimeout(function() {
            $.ajax({
                url: _base_url_ + 'admin/ajax/universal_search.php',
                method: 'POST',
                data: { search: query },
                dataType: 'json',
                success: function(resp) {
                    let html = '';
                    if (resp.length > 0) {
                        resp.forEach(function(item) {
                            html += `
                                <a href="${item.link}" class="search-result-item">
                                    <div class="result-icon">
                                        <i class="fas ${item.icon}"></i>
                                    </div>
                                    <div class="result-details">
                                        <span class="result-title">${item.title}</span>
                                        <span class="result-subtitle">${item.subtitle}</span>
                                    </div>
                                    <span class="result-type">${item.type}</span>
                                </a>
                            `;
                        });
                    } else {
                        html = '<div class="p-3 text-center text-muted">No results found for "' + query + '"</div>';
                    }
                    $('#universalSearchResults').html(html).show();
                }
            });
        }, 300);
    });

    $('#universalSearchInput').on('focus', function() {
        if ($(this).val().trim().length >= 2) {
            $('#universalSearchResults').show();
        }
    });

    /** Mobile Overlay Search **/
    $('#overlaySearchInput').on('input', function() {
        const query = $(this).val().trim();
        if (query.length < 2) {
            $('#overlaySearchResults').empty();
            return;
        }
        
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            $.ajax({
                url: _base_url_ + 'admin/ajax/universal_search.php',
                method: 'POST',
                data: { search: query },
                dataType: 'json',
                success: function(resp) {
                    let html = '';
                    if (resp.length > 0) {
                        resp.forEach(function(item) {
                            html += `
                                <a href="${item.link}" class="search-result-item shadow-sm mb-2 rounded border-0" style="background:#fff">
                                    <div class="result-icon"> <i class="fas ${item.icon}"></i> </div>
                                    <div class="result-details">
                                        <span class="result-title">${item.title}</span>
                                        <span class="result-subtitle text-muted">${item.subtitle}</span>
                                    </div>
                                    <span class="badge badge-light text-primary">${item.type}</span>
                                </a>
                            `;
                        });
                    } else {
                        html = '<div class="p-3 text-center text-muted">No results found</div>';
                    }
                    $('#overlaySearchResults').html(html);
                }
            });
        }, 300);
    });
});

function openSearchOverlay() {
    $('#mobileSearchOverlay').fadeIn(200);
    $('#overlaySearchInput').focus();
    $('body').css('overflow', 'hidden'); // Prevent scroll
}

function closeSearchOverlay() {
    $('#mobileSearchOverlay').fadeOut(200);
    $('body').css('overflow', 'auto');
}
</script>
