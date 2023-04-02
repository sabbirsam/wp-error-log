/* 
JavaScript one 

window.addEventListener('load', function() {

    // Define a function to update the error count
    function updateErrorCount() {
        // Call the Ajax endpoint to get the current error count
        var xhr = new XMLHttpRequest();
        xhr.open('POST', myErrorsAjax.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                var errorCount = response.data.count;
                var errorNode = document.querySelector('#my-errors-page .update-count');
                errorNode.textContent = errorCount;
                if (errorCount > 0) {
                    errorNode.parentNode.classList.add('update-plugins');
                } else {
                    errorNode.parentNode.classList.remove('update-plugins');
                }
            }
        };
        xhr.send('action=' + myErrorsAjax.action);
    }

    // Call the updateErrorCount function initially
    updateErrorCount();

    // Call the updateErrorCount function every X milliseconds
    setInterval(updateErrorCount, myErrorsAjax.interval);

}); */



// Jquery one 


 /* jQuery(document).ready(function($) {

    // Define a function to update the error count
    function updateErrorCount() {
        // Call the Ajax endpoint to get the current error count
        $.ajax({
            url: myErrorsAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: myErrorsAjax.action,
            },
            dataType: 'json',
            success: function(response) {
                // Update the error count in the admin bar node
                var errorCount = response.data.count;
                // var errorNode = $('#my-errors-page .update-count');
                var errorNode = $('span.update-count');
                errorNode.text(errorCount);

                // Update the class of the error count node
                if (errorCount > 0) {
                    errorNode.parent().addClass('update-plugins');
                } else {
                    errorNode.parent().removeClass('update-plugins');
                }
            },
        });
    }

    // Call the updateErrorCount function initially
    updateErrorCount();

    // Call the updateErrorCount function every X milliseconds
    setInterval(updateErrorCount, myErrorsAjax.interval);

}); 

*/
