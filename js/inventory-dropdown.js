// Inventory Dropdown Toggle
(function() {
    'use strict';
    
    function toggleInventoryDropdown(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        var submenu = document.getElementById('inventory-submenu');
        var arrow = document.getElementById('inventory-arrow');
        
        if (!submenu || !arrow) return;
        
        var isOpen = submenu.classList.contains('active');
        
        if (isOpen) {
            submenu.classList.remove('active');
            submenu.classList.add('hidden');
            arrow.textContent = '▼';
            arrow.classList.remove('active');
        } else {
            submenu.classList.remove('hidden');
            submenu.classList.add('active');
            arrow.textContent = '▲';
            arrow.classList.add('active');
        }
    }
    
    window.toggleInventoryDropdown = toggleInventoryDropdown;
    
    function init() {
        var submenu = document.getElementById('inventory-submenu');
        var arrow = document.getElementById('inventory-arrow');
        
        if (submenu && arrow) {
            submenu.classList.remove('active');
            submenu.classList.add('hidden');
            arrow.textContent = '▼';
            arrow.classList.remove('active');
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    document.addEventListener('click', function(e) {
        var submenu = document.getElementById('inventory-submenu');
        var arrow = document.getElementById('inventory-arrow');
        var link = document.querySelector('a[onclick*="toggleInventoryDropdown"]');
        
        if (!submenu || !arrow || !link) return;
        
        if (!link.contains(e.target) && !submenu.contains(e.target) && submenu.classList.contains('active')) {
            submenu.classList.remove('active');
            submenu.classList.add('hidden');
            arrow.textContent = '▼';
            arrow.classList.remove('active');
        }
    });
})();
