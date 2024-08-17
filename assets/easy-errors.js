jQuery(document).ready(function($) {

    // Fetch and update debug mode status when the page loads
    $.ajax({
        url: ajax_object.ajax_url,
        type: 'post',
        data: {
            action: 'get_debug_mode_status'
        },
        success: function(response) {
            var statusElement = $('#debug-mode-status');
            if (response === 'ON') {
                statusElement.css('color', 'red');
            } else if (response === 'OFF') {
                statusElement.css('color', 'green');
            }
            statusElement.text(response);
        }
    });

    // AJAX call to toggle debug mode
    $('#toggle-debug-mode').on('click', function() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'post',
            data: {
                action: 'toggle_debug_mode'
            },
            success: function(response) {
                // alert(response);
                // Update debug mode status
                var statusElement = $('#debug-mode-status');
                if (response === 'ON') {
                    statusElement.css('color', 'red');
                } else if (response === 'OFF') {
                    statusElement.css('color', 'green');
                }
                statusElement.text(response);
            }
        });
    });
    
    // AJAX call to display error log
    $('#refresh-debug-log').on('click', function() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'post',
            data: {
                action: 'display_error_log'
            },
            success: function(response) {
                $('#error-log-container').empty(); // reset and show the latest error on log 
                // console.log(response); 
                $('#error-log-container').append(response);
            }
        });
    });

     // Fetch error count via AJAX
    $.ajax({
        url: ajax_object.ajax_url,
        type: 'post',
        data: {
            action: 'get_error_count'
        },
        cache: false, // Prevent caching
        success: function(response) {
            var error_count = parseInt(response);
            var error_count_html = "<span style='color:red;font-weight:bold;' class='update-plugins count-" + error_count + "'><span class='update-count'>" + error_count + "</span></span>";
            $('#wp-admin-bar-my-errors-page .ab-item').html("WP Errors-" + error_count_html);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching error count:", error);
        }
    });

    // AJAX call to clean debug log
    $('#clean-debug-log').on('click', function() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'post',
            data: {
                action: 'clean_debug_log'
            },
            success: function(response) {
                // alert(response);
                // Refresh error log after cleaning
                $('#refresh-debug-log').trigger('click');

                // Update error count after cleaning
                $.ajax({
                    url: ajax_object.ajax_url,
                    type: 'post',
                    data: {
                        action: 'get_error_count'
                    },
                    success: function(response) {
                        var error_count = parseInt(response);
                        var error_count_html = "<span style='color:red;font-weight:bold;' class='update-plugins count-" + error_count + "'><span class='update-count'>" + error_count + "</span></span>";
                        $('#wp-admin-bar-my-errors-page .ab-item').html("WP Errors-" + error_count_html);
                    }
                });
                
            }
        });
    });

    

    // AJAX call to download debug log
    $('form#download-debug-log').on('submit', function(event) {
        event.preventDefault();
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'post',
            data: $(this).serialize(), // Serialize form data
            success: function(response) {
                // JavaScript-based download
                var downloadLink = document.createElement('a');
                downloadLink.href = response; // Debug log URL
                downloadLink.download = 'debug.log';
                downloadLink.style.display = 'none';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
        });
    });

    //Reset 
    $('#reset-constant').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'reset_debug_constant', // Action name to trigger the AJAX callback
                // Add any additional data if needed
            },
            success: function(response) {
                alert(response); // Show success message
                // You can also reload the page or update any UI elements as needed
            },
            error: function(error) {
                console.error('Error:', error); // Log any errors to the console
            }
        });
    });


    // Function to fetch and update error count
    function updateErrorCount() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'post',
            data: {
                action: 'get_error_count'
            },
            cache: false, // Prevent caching
            success: function(response) {
                var error_count = parseInt(response);
                var error_count_html = "<span style='color:red;font-weight:bold;' class='update-plugins count-" + error_count + "'><span class='update-count'>" + error_count + "</span></span>";
                $('#wp-admin-bar-my-errors-page .ab-item').html("WP Errors-" + error_count_html);
            },
            error: function(xhr, status, error) {
                console.error("Error fetching error count:", error);
            }
        });
    }

    // Function to fetch and update error log // Hide it with condition
    function updateErrorLog() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'post',
            data: {
                action: 'display_error_log'
            },
            success: function(response) {
                $('#error-log-container').html(response);
            }
        });
    }


    function updateDebugStatus() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'check_debug_constants_status',
            },
            success: function(response) {
                if (response.success) {
                    const wpDebugStatus = response.data.WP_DEBUG;
                    const wpDebugLogStatus = response.data.WP_DEBUG_LOG;

                    const wpDebugElement = $('.constant-status.wp-debug');
                    const wpDebugLogElement = $('.constant-status.wp-debug-log');

                    wpDebugElement.text(wpDebugStatus === true || wpDebugStatus === 'true' ? 'Found' : 'Not Found');
                    wpDebugLogElement.text(wpDebugLogStatus === true || wpDebugLogStatus === 'true' ? 'Found' : 'Not Found');

                    wpDebugElement.css('color', wpDebugStatus === true || wpDebugStatus === 'true' ? 'green' : 'red');
                    wpDebugLogElement.css('color', wpDebugLogStatus === true || wpDebugLogStatus === 'true' ? 'green' : 'red');
                }
            }
        });
    }

    // Initial load of error count and error log
    updateErrorCount();
    updateErrorLog();
    updateDebugStatus();

    // Set intervals to update error count and error log periodically
    var errorCountInterval = setInterval(updateErrorCount, 5000); // Update every 5 seconds
    var updateDebugConstStatus = setInterval(updateDebugStatus, 5000); // Update every 5 seconds
    // var errorLogInterval = setInterval(updateErrorLog, 5000); // To Update every 10 seconds 10000

    // Clean up intervals when the page is unloaded
    $(window).on('unload', function() {
        clearInterval(errorCountInterval);
        clearInterval(updateDebugConstStatus);
        // clearInterval(errorLogInterval);
    });


    // Initial load of error log
    $('#refresh-debug-log').trigger('click');


    // Tabs 
    const tabs = document.querySelectorAll('.nav-tab');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            tabPanes.forEach(p => p.style.display = 'none');

            this.classList.add('nav-tab-active');
            document.querySelector(this.getAttribute('href')).style.display = 'block';
        });
    });

    // Copy button 
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetCode = document.querySelector(targetId);

            // Create a temporary textarea to copy the content
            const tempInput = document.createElement('textarea');
            tempInput.value = targetCode.textContent;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            // Change button text to "Copied"
            const originalText = this.textContent;
            this.textContent = 'copied';

            // Revert text back after 1 second
            setTimeout(() => {
                this.textContent = originalText;
            }, 1000);
        });
    });



    // AJAX call to toggle fe debug mode widgets
    $('#toggle-fe-mode').on('click', function() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'post',
            data: {
                action: 'toggle_widgets_mode'
            },
            success: function(response) {
                var widgets_mode = response.data.widgets_mode;
                $('#debug-fe-status').text(widgets_mode === 'true' ? 'ON' : 'OFF');
                $('#debug-fe-status').css('color', widgets_mode === 'true' ? 'red' : 'green');
            }
        });
    });

    // Set the initial status based on the stored option value
    $.ajax({
        url: ajax_object.ajax_url,
        type: 'post',
        data: {
            action: 'get_widgets_mode_status'
        },
        success: function(response) {
            var widgets_mode = response.data.widgets_mode;
            $('#debug-fe-status').text(widgets_mode === 'true' ? 'ON' : 'OFF');
            $('#debug-fe-status').css('color', widgets_mode === 'true' ? 'red' : 'green');
        }
    });



});