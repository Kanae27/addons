//
Fix
for
removed
searchSignatory

// Fix for removed searchSignatory and other elements
document.addEventListener('DOMContentLoaded', function() {
    // Override the event listeners with safe versions
    const addEventListenerSafe = function(elementId, eventType, handler) {
        const element = document.getElementById(elementId);
        if (element) {
            element.addEventListener(eventType, handler);
        } else {
            console.log(`Element ${elementId} not found, event listener not added`);
        }
    };

    // Replace the problematic event listener with a safe one
    // addEventListenerSafe('searchSignatory', 'input', function() {
    //     const campusFilter = document.getElementById('campusFilter');
    //     if (!campusFilter) return;
    //     const campusValue = campusFilter.tagName.toLowerCase() === 'select' ?
    //         campusFilter.value : campusFilter.value;
    //     if (typeof filterSignatories === 'function') {
    //         filterSignatories(this.value, campusValue, 'view');
    //     }
    // });

    // Initialize any other required listeners
    console.log('Fixed event listeners initialized');
});
