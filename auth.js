// Authentication script to protect pages
(function() {
  'use strict';
  
  // Check if user is authenticated
  function isAuthenticated() {
    const authFlag = sessionStorage.getItem('tms_authenticated');
    const loginTime = sessionStorage.getItem('tms_login_time');
    
    if (!authFlag || authFlag !== 'true' || !loginTime) {
      return false;
    }
    
    // Check if session is still valid (24 hours)
    const currentTime = Date.now();
    const sessionTime = parseInt(loginTime);
    const sessionDuration = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
    
    if (currentTime - sessionTime > sessionDuration) {
      // Session expired
      logout();
      return false;
    }
    
    return true;
  }
  
  // Logout function
  function logout() {
    sessionStorage.removeItem('tms_authenticated');
    sessionStorage.removeItem('tms_login_time');
    window.location.href = 'login.html';
  }
  
  // Protect page
  function protectPage() {
    if (!isAuthenticated()) {
      window.location.href = 'login.html';
      return;
    }
  }
  
  // Add logout functionality to pages
  function addLogoutButton() {
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
      const header = document.querySelector('header');
      if (header) {
        // Create logout button
        const logoutBtn = document.createElement('button');
        logoutBtn.textContent = 'Logout';
        logoutBtn.style.cssText = `
          background: #e53e3e;
          color: white;
          border: none;
          padding: 8px 16px;
          border-radius: 6px;
          cursor: pointer;
          font-weight: 600;
          margin-left: 10px;
          transition: background 0.3s;
        `;
        
        logoutBtn.addEventListener('mouseenter', function() {
          this.style.background = '#c53030';
        });
        
        logoutBtn.addEventListener('mouseleave', function() {
          this.style.background = '#e53e3e';
        });
        
        logoutBtn.addEventListener('click', function() {
          if (confirm('Are you sure you want to logout?')) {
            logout();
          }
        });
        
        // Add logout button to controls div if it exists, otherwise to header
        const controls = header.querySelector('.controls');
        if (controls) {
          controls.appendChild(logoutBtn);
        } else {
          header.appendChild(logoutBtn);
        }
      }
    });
  }
  
  // Initialize protection
  function init() {
    const currentPage = window.location.pathname.split('/').pop();
    
    // Don't protect login page
    if (currentPage === 'login.html' || currentPage === '') {
      return;
    }
    
    // Protect all other pages
    protectPage();
    addLogoutButton();
  }
  
  // Run initialization
  init();
  
  // Export functions for manual use
  window.TMS_Auth = {
    logout: logout,
    isAuthenticated: isAuthenticated
  };
})();
