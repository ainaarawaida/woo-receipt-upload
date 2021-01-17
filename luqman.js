

jQuery(window).load(function () {
   
	
	 jQuery(function($) {
		 
		 
	
		$('body').on('change', '#myFiles', function() {
	  
			 
   var file_data = $("#myFiles").prop('files')[0];
            var form_data = new FormData();
            form_data.append('file', file_data);
            form_data.append('action', 'myaction');
			var iluq = 0;
            jQuery.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
				cache: true,
                contentType: false,
                processData: false,
                data: form_data,
                beforeSend: function() {
					// setting a timeout
					$('#luquploadfield').addClass('loading');
					iluq++;
				},
				success: function(response) {
					if($("#myFiles").val() != ''){ 
					$("#luquploadfield").html(response);   
					}
				},
				error: function(xhr) { // if error occured
					alert("Error occured.please try again");
					$('#luquploadfield').append(xhr.statusText + xhr.responseText);
					$('#luquploadfield').removeClass('loading');
				},
				complete: function() {
					iluq--;
					if (iluq <= 0) {
						$('#luquploadfield').removeClass('loading');
					}
				}
							
			  });
	
	
	
});
			  
			  
   
  
   
  });

});