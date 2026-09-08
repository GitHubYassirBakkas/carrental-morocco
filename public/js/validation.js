// JavaScript Validation //

document.addEventListener('DOMContentLoaded', function() {
    const pickupDate = document.getElementById('pickup_date');
    const returnDate = document.getElementById('return_date');
    
    // Set min date to today
    const today = new Date().toISOString().split('T')[0];
    pickupDate.setAttribute('min', today);
    pickupDate.value = today;
    
    // Update return date min when pickup changes
    pickupDate.addEventListener('change', function() {
        const nextDay = new Date(this.value);
        nextDay.setDate(nextDay.getDate() + 1);
        const minReturn = nextDay.toISOString().split('T')[0];
        returnDate.setAttribute('min', minReturn);
        returnDate.value = minReturn;
    });
    
    // Initial setup
    pickupDate.dispatchEvent(new Event('change'));
});

