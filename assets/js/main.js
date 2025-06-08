// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Quiz navigation
    const prevBtn = document.querySelector('.btn-prev');
    const nextBtn = document.querySelector('.btn-next');
    const submitBtn = document.querySelector('.btn-submit');
    const quizOptions = document.querySelectorAll('.quiz-option');

    if (quizOptions.length > 0) {
        quizOptions.forEach(option => {
            option.addEventListener('click', () => {
                // Remove selected class from all options
                quizOptions.forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                option.classList.add('selected');
            });
        });
    }

    // Performance chart
    const performanceChart = document.querySelector('#performanceChart');
    if (performanceChart) {
        // You can add your preferred charting library here
        // For example: Chart.js implementation
    }

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });
}); 