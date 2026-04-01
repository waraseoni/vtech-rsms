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
    /* Hamburger button */
    .hamburger-btn {
        color: #fff !important;
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
      cursor: pointer;
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
    .profile-dropdown-header .name {
      font-weight: bold;
      font-size: 0.9rem;
    }
    .profile-dropdown-header .role {
      font-size: 0.7rem;
      opacity: 0.8;
    }
    .profile-dropdown-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      color: #333;
      text-decoration: none;
      font-size: 0.85rem;
      border-bottom: 1px solid #eee;
    }
    .profile-dropdown-item:last-child {
      border-bottom: none;
    }
    .profile-dropdown-item:hover {
      background: #f5f5f5;
    }
    .profile-dropdown-item.logout {
      color: #dc3545;
    }
    /* Mobile topbar - thin bar with big icons */
    .main-header {
        background: #343a40 !important;
        z-index: 100000 !important;
        min-height: 20px !important;
        padding: 0 !important;
    }
    .main-header .navbar {
        min-height: 18px !important;
        padding: 0 4px !important;
        margin: 0 !important;
    }
    .main-header .navbar-nav {
        margin: 0 !important;
        padding: 0 !important;
    }
    .main-header .nav-link {
        padding: 0 4px !important;
        min-height: 18px !important;
        display: flex !important;
        align-items: center !important;
    }
    .main-header .nav-link i {
        font-size: 18px !important;
    }
    .main-header .nav-link,
    .main-header .navbar-brand {
        color: #fff !important;
    }
    /* Hamburger button styles for mobile */
    .hamburger-btn {
        display: inline-flex !important;
        color: #fff !important;
        padding: 0 4px !important;
        min-width: 28px !important;
        min-height: 28px !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .hamburger-btn i {
        font-size: 18px !important;
    }
  }
</style>
<!-- Navbar -->
      <nav class="main-header navbar navbar-expand navbar-light shadow text-sm">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
		<li class="nav-item d-md-none d-flex align-items-center">
			<a href="javascript:void(0)" onclick="history.back()" class="nav-link px-2">
				<i class="fas fa-arrow-left"></i>
			</a>
      
			<a href="javascript:void(0)" onclick="location.reload()" class="nav-link px-2 text-primary">
				<i class="fas fa-sync-alt"></i>
			</a>
		</li>

        <!-- Mobile Hamburger Button -->
        <li class="nav-item hamburger-mobile-only">
          <button class="hamburger-btn" onclick="toggleSidebar(event)" type="button">
            <i class="fas fa-bars"></i>
          </button>
        </li>
        
          <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo base_url ?>" class="nav-link"><?php echo (!isMobileDevice()) ? $_settings->info('name'):$_settings->info('short_name'); ?> - Admin</a>
          </li>
        </ul>
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
          <!-- Desktop: Full dropdown -->
          <li class="nav-item user-dropdown-mobile d-none d-md-block">
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
});
</script>
