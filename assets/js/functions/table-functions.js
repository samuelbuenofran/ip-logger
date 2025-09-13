/**
 * Table Functions
 * Handles resizable and sortable table functionality
 */

/**
 * Initialize resizable tables
 */
function initResizableTables() {
    console.log('initResizableTables called');
    const tables = document.querySelectorAll('.resizable-table');
    console.log('Found tables:', tables.length);

    tables.forEach((table, tableIndex) => {
        console.log(`Processing table ${tableIndex}:`, table);
        const headers = table.querySelectorAll('th');
        console.log(`Found ${headers.length} headers in table ${tableIndex}`);
        let isResizing = false;
        let currentHeader = null;
        let startX = 0;
        let startWidth = 0;

        // Drag and drop variables
        let draggedColumn = null;
        let draggedIndex = -1;

        // Create tooltip element
        const tooltip = document.createElement('div');
        tooltip.className = 'table-tooltip';
        document.body.appendChild(tooltip);

        headers.forEach((header, index) => {
            // Skip if already has resize handle
            if (header.querySelector('.resize-handle')) {
                return;
            }

            console.log(`Processing header ${index}:`, header);

            // Create resize handle
            const resizeHandle = document.createElement('div');
            resizeHandle.className = 'resize-handle';
            header.appendChild(resizeHandle);

            // Add draggable attribute for column reordering
            header.setAttribute('draggable', 'true');
            header.style.position = 'relative';

            // Resize functionality
            resizeHandle.addEventListener('mousedown', function(e) {
                console.log('Resize started');
                isResizing = true;
                currentHeader = header;
                startX = e.clientX;
                startWidth = parseInt(window.getComputedStyle(header).width, 10);
                
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
                
                e.preventDefault();
                e.stopPropagation();
            });

            // Drag and drop functionality
            header.addEventListener('dragstart', function(e) {
                console.log('Drag started');
                draggedColumn = header;
                draggedIndex = index;
                header.style.opacity = '0.5';
            });

            header.addEventListener('dragend', function(e) {
                console.log('Drag ended');
                header.style.opacity = '1';
                draggedColumn = null;
                draggedIndex = -1;
            });

            header.addEventListener('dragover', function(e) {
                e.preventDefault();
            });

            header.addEventListener('drop', function(e) {
                e.preventDefault();
                if (draggedColumn && draggedColumn !== header) {
                    const table = header.closest('table');
                    const tbody = table.querySelector('tbody');
                    
                    // Get all rows
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    
                    // Swap columns in all rows
                    rows.forEach(row => {
                        const cells = Array.from(row.children);
                        const draggedCell = cells[draggedIndex];
                        const targetCell = cells[index];
                        
                        if (draggedCell && targetCell) {
                            row.insertBefore(draggedCell, targetCell);
                        }
                    });
                    
                    console.log('Columns swapped');
                }
            });
        });

        // Mouse move for resizing
        document.addEventListener('mousemove', function(e) {
            if (isResizing && currentHeader) {
                const newWidth = startWidth + (e.clientX - startX);
                if (newWidth > 50) { // Minimum width
                    currentHeader.style.width = newWidth + 'px';
                    
                    // Update tooltip
                    tooltip.textContent = newWidth + 'px';
                    tooltip.style.left = e.clientX + 10 + 'px';
                    tooltip.style.top = e.clientY - 30 + 'px';
                    tooltip.style.display = 'block';
                }
            }
        });

        // Mouse up to stop resizing
        document.addEventListener('mouseup', function(e) {
            if (isResizing) {
                console.log('Resize ended');
                isResizing = false;
                currentHeader = null;
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
                tooltip.style.display = 'none';
            }
        });

        console.log(`Table ${tableIndex} initialized with ${headers.length} headers`);
    });
}

// Initialize tables when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initResizableTables();
});
