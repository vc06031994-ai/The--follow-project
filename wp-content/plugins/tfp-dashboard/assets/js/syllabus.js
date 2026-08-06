document.addEventListener('DOMContentLoaded', function() {
    const syllabusHeaders = document.querySelectorAll('.tfp-syllabus-header');
    
    syllabusHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const currentItem = header.parentElement;
            const isActive = currentItem.classList.contains('is-active');
            
            // Close all other items
            const allItems = document.querySelectorAll('.tfp-syllabus-item');
            allItems.forEach(item => {
                item.classList.remove('is-active');
            });
            
            // If the clicked item wasn't active, open it
            if (!isActive) {
                currentItem.classList.add('is-active');
            }
        });
    });
});
