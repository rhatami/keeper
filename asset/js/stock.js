 document.addEventListener('DOMContentLoaded', function() {
        // Delete transaction functionality
        document.querySelectorAll('.delete-transaction').forEach(function(button) {
            button.addEventListener('click', function() {
                var type = this.getAttribute('data-type');
                var id = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this ' + type + ' transaction?')) {
                    fetch('delete.php?id=' + id + '&type=' + type, {
                        method: 'DELETE',
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message);
                            location.reload();
                        } else {
                            throw new Error(data.message || 'Error deleting ' + type + ' transaction');
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
                }
            });
        });
    });

    function toggleTransactions(stock) {
        const container = document.getElementById('transactions-' + stock);
        container.classList.toggle('active');
    }