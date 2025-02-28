(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var dateField = document.getElementById(dateFieldId);
        var dateFieldDisplay = document.getElementById(dateFieldId + '_display');

        $(dateFieldDisplay).persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true,
            initialValue: false,
            onSelect: function(unix) {
                var date = new persianDate(unix);
                var gregorianDate = date.toCalendar('gregorian').format('YYYY-MM-DD');
                dateField.value = gregorianDate;
            }
        });

        // Set initial value
        if (isEdit) {
            var initialDateObj = new Date(initialDate);
            var persianInitialDate = new persianDate(initialDateObj);
            dateFieldDisplay.value = persianInitialDate.format('YYYY/MM/DD');
        } else {
            var today = new persianDate();
            dateFieldDisplay.value = today.format('YYYY/MM/DD');
            dateField.value = today.toCalendar('gregorian').format('YYYY-MM-DD');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const assetSelect = document.getElementById('asset');
        const unitLabel = document.getElementById('unit-label');
        const stockWrapper = document.getElementById('stock-wrapper');
        const stockInput = document.getElementById('stock');
    
        function handleAssetChange() {
            const selectedValue = assetSelect.value;
            const selectedOption = assetSelect.options[assetSelect.selectedIndex];
            const unit = selectedOption.dataset.unit;
            
            // Update unit label
            unitLabel.textContent = unit;
            
            // Show/hide stock wrapper
            if (selectedValue === '12') {
                stockWrapper.style.display = 'flex';
                stockInput.required = true;
            } else {
                stockWrapper.style.display = 'none';
                stockInput.required = false;
                stockInput.value = '';
            }
        }
    
        assetSelect.addEventListener('change', handleAssetChange);
    
        // Call handleAssetChange initially to set up the correct state
        handleAssetChange();
    });


})();
