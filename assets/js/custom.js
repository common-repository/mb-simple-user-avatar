(function($) {

	$(document).on("click", ".mb_sua_remove", function() {
		var button = $(this);

		button.addClass("mb_sua_disabled");

    	var data = {
    		action: "mb_sua_remove",
		};

		console.log(data);

        $.post(localizations.siteUrl + '/wp-admin/admin-ajax.php', data, function(response) {
			response = $.parseJSON(response);
			console.log(response);

			button.removeClass("mb_sua_disabled");

			if (response.success) {
				$("#mb_sua_avatar_wrap").slideUp();
			}
		});
		
		return false;
	})

	$('.mb_sua_submitFile').on('click',function(){
		var button = $(this);
		var form = document.getElementById('mb_sua_submitFileForm');
		var formData = new FormData(form);
        formData.append('action', 'mb_sua_submitFile');

		$("#mb_sua_submitFileForm").addClass('mb_sua_disabled');
		button.addClass('mb_sua_disabled');

        jQuery.ajax({
            type:'POST',
            url: localizations.siteUrl + '/wp-admin/admin-ajax.php',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            success:function(data){
                console.log("success");
                var response = $.parseJSON(data);
                console.log(response);

				$("#mb_sua_submitFileForm").removeClass('mb_sua_disabled');
				button.removeClass('mb_sua_disabled');
                
                if (response.status == "success") {
					$("#mb_sua_avatar_wrap .mb_sua_avatar img").attr("src", response.imgUrl);
					$("#mb_sua_avatar_wrap").slideDown("fast");
	
					$('#mb_sua_submitFileForm').trigger("reset");
				}
				else {
	                alert(response.msg);
				}

            },
            error: function(data){
				button.removeClass('disabled');
 				$("#mb_sua_submitFileForm").removeClass('disabled');

 				console.log("error");
                alert(localizations.Problem);
                console.log(data);
            }
        });
		
		return false;
    });

})(jQuery);
