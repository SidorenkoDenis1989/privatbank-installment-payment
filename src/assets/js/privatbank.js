(function($){
	const resetPrivatbankForm = () => {
		$('#privatbank-overlay input[name=price]').val("");
		$('#privatbank-overlay input[name=course]').val("");
	}

	$('.privatbank-payment_installments').on("click", function(e){
		e.preventDefault();
		const course = $(this).attr('data-course');
		const price = $(this).attr('data-price');
		$("body").addClass("body__hidden");
		$('#privatbank-overlay input[name=price]').val(price);
		$('#privatbank-overlay input[name=course]').val(course);
		$('#privatbank-overlay').addClass("privatbank-overlay__active");

	});

	$("#privatbank-container button").on("click", function(e){
		e.preventDefault();
		$('#privatbank-popup form')[0].reset();
		$("body").removeClass("body__hidden");
		$("#privatbank-overlay .wpcf7-response-output").text("");
		resetPrivatbankForm();
		$('#privatbank-overlay').removeClass("privatbank-overlay__active");
	});

	$("#privatbank-overlay input[type=submit]").on('click', function(e){
		e.preventDefault();

		const name = $("#privatbank-overlay input[name='privatbank-name']").val();
		const phone = $("#privatbank-overlay input[name='privatbank-phone']").val();
		const price = $('#privatbank-overlay input[name=price]').val();
		const course = $('#privatbank-overlay input[name=course]').val();

		let errorsQty = 0;

		if (name.length === 0) {
			$("#privatbank-overlay input[name='privatbank-name']").addClass("wpcf7-not-valid");
			errorsQty++;
		} else {
			$("#privatbank-overlay input[name='privatbank-name']").removeClass("wpcf7-not-valid");
		}

		if (phone.replace('_', '').length < 19) {
			$("#privatbank-overlay input[name='privatbank-phone']").addClass("wpcf7-not-valid");
			errorsQty++;
		} else {
			$("#privatbank-overlay input[name='privatbank-phone']").removeClass("wpcf7-not-valid");
		}

		if (price <= 0) {
			errorsQty++;
		}

		if (course.length === 0) {
			errorsQty++;
		}

		if (errorsQty > 0) {
			return;
		}	
		
		const data = {
			'action': 'privatbank_payment',
			'name': name,
			'phone': phone,
			'price': price,
			'course': course,
			'lang': general_scripts_vars.language,
		};
 
		$.ajax({
			url : privatbank_scripts_vars.ajaxurl,
			data : data,
			dataType: 'json',
			type : 'POST',
			beforeSend : function ( xhr ) {
				$(this).attr('disabled', true);
			},
			success : function( data ){
				$(this).attr('disabled', false);
				if (data.status === "SUCCESS") {
				    window.location.href = data.redirect_url
				} else {
					alert(data.message);
				}
			}
		});
	});
})(jQuery);